<?php

/**
 * This file is part of GRAMC (Computing Ressource Granting Software)
 * GRAMC stands for : Gestion des Ressources et de leurs Attributions pour Mésocentre de Calcul
 *
 * GRAMC is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 *  GRAMC is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with GRAMC.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  authors : Miloslav Grundmann - C.N.R.S. - UMS 3667 - CALMIP
 *            Emmanuel Courcelle - C.N.R.S. - UMS 3667 - CALMIP
 *            Nicolas Renon - Université Paul Sabatier - CALMIP
 **/

namespace AppBundle\AffectationExperts;


use AppBundle\Entity\Projet;
use AppBundle\Entity\Version;
use AppBundle\Entity\CollaborateurVersion;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use AppBundle\Form\ChoiceList\ExpertChoiceLoader;

use AppBundle\Entity\Thematique;
use AppBundle\AppBundle;
use AppBundle\Utils\Etat;
use AppBundle\Utils\Functions;

/****************************************
 * AffectationExperts: cette classe encapsule les algorithmes utilisés par les pages d'affectation
 * des experts (versions, projets tests, rallonges)
 **********************************************************/

class AffectationExperts
{
	// Constructeur: Certains objets sont protégés dans les controleurs, 
	// donc on les passe séparément à affectationExperts
	// Arguments: $request   
	//			  $versions	 La liste des versions
	//            $ff        Form factory
	//            $dct       getDoctrine()

	function __construct (Request $request, $versions, $ff, $dct)
	{
		$this->formFactory = $ff;
		$this->doctrine    = $dct;
		$this->request     = $request;
		$this->versions    = $versions;
		$this->form_buttons= null;
		$this->thematiques = null;
	}
	
	// Renvoie le formulaire des boutons principaux 
	public function getFormButtons()
	{
		if ($this->form_buttons==null)
		{
			$this->form_buttons = 
				$this->formFactory->createNamedBuilder('BOUTONS', FormType::class, null, ['csrf_protection' => false ])
					 ->add( "sub1",SubmitType::Class, ['label' => 'Affecter les experts', 'attr' => ['title' => 'Les experts seront affectés incognito'] ] )
					 ->add( "sub2",SubmitType::Class, ['label' => 'Affecter et notifier les experts', 'attr' => ['title' => 'Les experts affectés recevront une notification par courriel'] ] )
					 ->add( "sub3",SubmitType::Class, ['label' => 'Ajouter une expertise', 'attr' => ['title' => 'Ajouter un expert si possible'] ] )
					 ->add( "sub4",SubmitType::Class, ['label' => 'Supp expertise sans expert', 'attr' => ['title' => 'ATTENTION - Risque de perte de données'] ] )
					 ->getForm();
		}
		return $this->form_buttons;
	}
	
	/*********************************************
	 * getTableauThematiques = Calcule et renvoie le tableau des thématiques, 
	 * avec pour chacune la liste des experts associés et 
	 * le nombre de projets affectés à la thématique
	 * 
	 * return: Le tableau des thématiques
	 * 
	 ***************************************************/
	public function getTableauThematiques()
	{
		if ($this->thematiques==null)
		{
			// Construction du tableau des thématiques
		    $thematiques = [];
		    foreach( AppBundle::getRepository(Thematique::class)->findAll() as $thematique )
	        {
		        foreach( $thematique->getExpert() as $expert )
		        {
		            if( $expert->getExpert() == false )
	                {
		                Functions::warningMessage( __METHOD__ . ':' . __LINE__ . " $expert" . " est supprimé de la thématique pour ce projet" . $thematique);
		                Functions::noThematique( $expert );
	                }
				}
		        $thematiques[ $thematique->getIdThematique() ] =
		            ['thematique' => $thematique, 'experts' => $thematique->getExpert(), 'projets' => 0 ];
	        }
	        
	        // Remplissage avec le nb de versions par thématiques
	        $versions = $this->versions;
	        foreach( $versions as $version )
			{
	            $etatVersion    =   $version->getEtat();
	            if( $etatVersion == Etat::EDITION_DEMANDE || $etatVersion == Etat::ANNULE ) continue; 
	        
				if( $version->getPrjThematique() != null )
	            {
	                $thematiques[ $version->getPrjThematique()->getIdThematique() ]['projets']++;
				}
			}
			$this->thematiques = $thematiques;
		}
        return $this->thematiques;
	}

	/*********************************************
	 * traitementFormulaires
	 * Traite les formulaires d'affectation des experts pour les versions sélectionnées
	 *    
	 ********/
	public function traitementFormulaires()
	{
		$request  = $this->request;
		$versions = $this->versions;
        foreach( $versions as $version )
		{
            $etatVersion    =   $version->getEtat();
            if( $etatVersion == Etat::EDITION_DEMANDE || $etatVersion == Etat::ANNULE ) continue; // on n'affiche pas de version en cet état

			// La version est-elle sélectionnée ? - Si non on ignore
			$selform = $this->getSelForm($version);
			$selform->handleRequest($request);
			if ($selform->getData()['sel']==false)
			{
	            continue;
			}

			// traitement du formulaire d'affectation
			$forms   = $this->getExpertForms($version);

			$experts_affectes = [];
			foreach ($forms as $f)
			{
				$f->handleRequest($request);
				$experts_affectes[] = $f->getData()['expert'];
			}

			// Traitements différentiés suivant le bouton sur lequel on a cliqué
			$form_buttons = $this->getFormButtons();
			if ($form_buttons->get('sub1')->isClicked())
			{
				$this->affecterExpertsToVersion($experts_affectes,$version);
			}
			elseif ($form_buttons->get('sub2')->isClicked())
			{
				$this->affecterExpertsToVersion($experts_affectes,$version);
				$this->notifierExperts($experts_affectes,$version);
			}
			elseif ($form_buttons->get('sub3')->isClicked())
			{
				$this->addExpertiseToVersion($version);
			}
			elseif ($form_buttons->get('sub4')->isClicked())
			{
				$this->affecterExpertsToVersion($experts_affectes,$version);
				$this->remExpertiseFromVersion($version);
			}
			else
			{
				continue;
			}
		}
	}

	/**
	* Ajoute une expertise à la version
	* Si on atteint le paramètre max_expertises_nb, ne fait rien
	* TODO - Si on atteint le paramètre max_expertises_nb, envoyer un message d'erreur !
	*
	* param = $version
	* Return= rien
	*
	****/
	private function addExpertiseToVersion($version)
	{
		$expertises = $version->getExpertise()->toArray();
		if (count($expertises)<AppBundle::getParameter('max_expertises_nb'))
		{
			$expertise  =   new Expertise();
			$expertise->setVersion( $version );

			// Attention, l'algorithme de proposition des experts dépend du type de projet
			// TODO Actuellement on ne propose pas d'expertise à ce moment
			//      Il faudra améliorer l'algorithme de proposition
			//$expert = $version->getProjet()->proposeExpert();
			//if ($expert != null)
			//{
			//	$expertise->setExpert( $expert );
			//}
	        Functions::sauvegarder( $expertise );
		}
	}

	/**
	* Retire les expertises sans experts de la version, sauf la première
	* car il doit rester au moins une expertise
	*
	* TODO - Plutôt que de ne rien faire, envoyer un message d'erreur !
	*
	* param = $version
	* Return= rien
	*
	****/
	private function remExpertiseFromVersion($version)
	{
		$expertises = $version->getExpertise()->toArray();
		$em = $this->getDoctrine()->getManager();

		// On travaille en deux temps pour ne pas supprimer un tableau tout en itérant
		// 1/ Identifier les id d'expertises à supprimer
		// 2/ Les supprimer
		$first = true;
		$to_rem= [];
		foreach($expertises as $e)
		{
			if ($first)
			{
				$first = false;
				continue;
			}
			if ($e->getExpert()==null)
			{
				$to_rem[]=$e->getid();
			}
		}
		if (count($to_rem)>0)
		{
			foreach($to_rem as $e_id)
			{
				$em->remove($em->getRepository(Expertise::class)->find($e_id));
			}
			$em->flush();
		}
	}

	/**
	 * Sauvegarde les experts associés à une version
	 *
	 ***/
	protected function affecterExpertsToVersion($experts, $version)
	{
		$em = $this->doctrine->getManager();
		$expertises = $version->getExpertise()->toArray();
		usort($expertises,['self','cmpExpertises']);

		if (count($experts)>1)
		{
			// On vérifie qu'il n'y a pas deux experts identiques
			// TODO Dans ce cas il faudrait envoyer un message d'erreur !
			// TODO - Trouver un truc plus élégant que ça !
			$id_experts=[];
			$cnt_null = 0;
			foreach ($experts as $e)
			{
				$id_experts[] = $e==null ? $cnt_null++ : $e->getIdIndividu();
			}
			//Functions::debugMessage( __METHOD__ . ' experts uniques -> '.count(array_unique($id_experts)) .'  experts -> '.count($id_experts));
			if (count(array_unique($id_experts)) != count($id_experts)) return;
		}

		foreach ($expertises as $e)
		{
			$e->setExpert(array_shift($experts));
			$em->persist($e);
		}
		// Je n'utilise pas Functions::sauvegarder car je sauvegarde plusieurs objets à la fois !
		$em->flush();
	}

	/*************************************************************************
	 * getExpertsForms
	 * Génère les formulaires d'affectation des experts pour chaque version
	 * 
	 * return:  Un tableau de formulaire, indexé par l'id de la version
	 * 
	 ****************************************************************************/
	public function getExpertsForms()
	{
		$versions = $this -> versions;
        $forms    = [];
        foreach( $versions as $version )
		{
            $etatVersion    =   $version->getEtat();
            
            // Pas de formulaire sauf pour ces états
            if( $etatVersion != Etat::EDITION_EXPERTISE && $etatVersion != Etat::EXPERTISE_TEST ) continue; 

            $exp = $version->getExperts();

			// Formulaire pour la sélection (case à cocher)
			$sform = $this->getSelForm($version)->createView();
			$forms['selection_'.$version->getId()] = $sform;

			// Génération des formulaires de choix de l'expert
			$eforms  = $this->getExpertForms($version);
			foreach ($eforms as &$f) $f=$f->createView();
			$forms[$version->getId()] = $eforms;
		}
		if (count($forms) > 0)
		{
			$forms['BOUTONS'] = $this->getFormButtons()->createView();
		}

		return $forms;
	}

	/*************************************************************************
	 * getStats
	 * Génère différentes statistiques sur les attributions
	 * 
	 * input:   $versions    Tableau de versions
	 *          $thematiques Tableau de thématiques
	 * 
	 * return:  les stats
	 * 
	 ****************************************************************************/
	public function getStats()
	{
		$nbProjets      = 0;
        $nouveau        = 0;
        $renouvellement = 0;
        $sansexperts    = 0;
        $nbDemHeures    = 0;
        $nbAttHeures    = 0;

		$experts_assoc  = [];
		$versions       = $this->versions;
        foreach( $versions as $version )
		{
            $etatVersion = $version->getEtat();
            
            // Pas de choix d'expert pour ces états de versions
            if( $etatVersion == Etat::EDITION_DEMANDE || $etatVersion == Etat::ANNULE ) continue; 

            $exp = $version->getExperts();
            if (count($exp)==0)
            {
				$sansexperts++;
			} 
			else 
			{
				foreach ($exp as $e)
				{
					if ($e==null) continue;
					if ( ! isset($experts_assoc[$e->getIdIndividu()]) )
					{
						$experts_assoc[$e->getIdIndividu()] = ['expert' => $e, 'projets' => 0 ];
					}
					$experts_assoc[$e->getIdIndividu()]['projets']++;
				}
	            /*if( !$version->isNouvelle() )
	            {
	                $renouvellement++;
				}
	            else
	            {
	                $nouveau++;
				}*/
			}
			
            $nbProjets++;

            $nbDemHeures    +=  $version->getDemHeures();
            /*if( $version->getExpertise() != null && $version->getExpertise()[0] != null )
			{
                $heures         =   $version->getExpertise()[0]->getNbHeuresAtt();
                $nbAttHeures    +=  $heures;
                $attHeures[$version->getId()]    =  $heures;
			}
            else
            {
                $attHeures[$version->getId()]    = 0;
			}*/
		}
		$stats = ["nbProjets"      => $nbProjets,
				  "nouveau"        => $nouveau,
				  "renouvellement" => $renouvellement,
				  "sansexperts"    => $sansexperts,
				  "nbDemHeures"    => $nbDemHeures,
				  "nbAttHeures"    => $nbAttHeures];
		return $stats;
	}

	/*************************************************************************
	 * getAttHeures
	 * Renvoie un tableau avec le nombre d'heures attribuées, pour affichage
	 * 
	 * return:  Un tableau indexé par l'id de la version
	 * 
	 ****************************************************************************/
	public function getAttHeures()
	{
		$versions = $this->versions;
        $attHeures = [];
        foreach( $versions as $version )
		{
            $etatVersion    =   $version->getEtat();
            if( $etatVersion == Etat::EDITION_DEMANDE || $etatVersion == Etat::ANNULE ) continue; 

            /*if( $version->getExpertise() != null && $version->getExpertise()[0] != null )
			{
                $attHeures[$version->getId()] = $version->getExpertise()[0]->getNbHeuresAtt();
			}
            else
            {
                $attHeures[$version->getId()]    = 0;
			}*/
		}
		return $attHeures;
	}

    ///////////////////////
    private static function cmpExperts($a,$b) 
	{
		return ($a["expert"]->getNom()<=$b["expert"]->getNom()) ? -1 : 1;
	}	
	private static function cmpExpertises($a,$b) { return $a->getId() > $b ->getId(); }


	public function getTableauExperts()
	{
		$versions      = $this->versions;
		$experts_assoc = [];
        foreach( $versions as $version )
		{
            // Pas de choix d'expert pour ces états de versions
            $etat_version = $version->getEtat();
            if( $etat_version == Etat::EDITION_DEMANDE || $etat_version == Etat::ANNULE ) continue; 
            
			$exp = $version->getExperts();
			foreach ($exp as $e)
			{
				if ($e==null) continue;
				if ( ! isset($experts_assoc[$e->getIdIndividu()]) )
				{
					$experts_assoc[$e->getIdIndividu()] = ['expert' => $e, 'projets' => 0 ];
				}
				$experts_assoc[$e->getIdIndividu()]['projets']++;
			}
		}

		// Mise en forme du tableau experts, pour avoir l'ordre alphabétique !
		$experts = [];
		foreach ($experts_assoc as $k => $e) 
		{ 
			if ( $e['projets'] > 0 )
			{
				$experts[] = $e;
			}
		}
	    usort($experts,"self::cmpExperts");

		return $experts;
	}

	/***
	 * Renvoie un formulaire avec une case à cocher, rien d'autre
	 *
	 *   params  $version (pour calculer le nom du formulaire)
	 *   return  une form
	 *
	 */
	private  function getSelForm($version)
	{
		$nom = 'selection_'.$version->getId();
		$formBuilder = $this->formFactory->createNamedBuilder($nom, FormType::class, null, ['csrf_protection' => false]);
		$formBuilder->add('sel',CheckboxType::class, [ 'required' =>  false, 'attr' => ['class' => "expsel"]  ]);
		return $formBuilder->getForm();
	}

	protected function getExpertForms($version)
	{
		$forms = [];
		$expertises = $version->getExpertise()->toArray();
		usort($expertises,['self','cmpExpertises']);

		// Liste d'exclusion = Les collaborateurs + les experts choisis par ailleurs
	    $exclus = AppBundle::getRepository(CollaborateurVersion::class)->getCollaborateurs($version->getProjet());
	    $experts= [];
	    foreach ($expertises as $expertise)
		{
			$expert = $expertise->getExpert();
			if ($expert != null) $exclus[$expert->getId()] = $expert;
		}

		$first = true;
		foreach ($expertises as $expertise)
		{
			// L'expert actuel (peut-être null)
			$expert = $expertise->getExpert();

			// La liste d'exclusion pour cette expertise
			$exclus_exp = $exclus;

			// On vire l'expert actuel de la liste d'exclusion
			if ($expert != null) unset($exclus_exp[$expert->getId()]);

			// Nom du formulaire
			$nom = 'expert'.$version->getProjet()->getIdProjet().'-'.$expertise->getId();

			//if ($version->getIdVersion()=="20A200044")	Functions::debugMessage("koukou $nom ".$expert->getId());
		    //Functions::debugMessage(__METHOD__ . "Experts exclus pour $version ".Functions::show( $exclus));

		    // Projets de type Projet::PROJET_FIL -> La première expertise est obligatoirement faite par un président !
		    if ($first && $version->getProjet()->getTypeProjet() == Projet::PROJET_FIL)
		    {
			    $choice = new ExpertChoiceLoader($exclus_exp,true);
			}
			else
			{
			    $choice = new ExpertChoiceLoader($exclus_exp);
			}

			$forms[] = $this->formFactory->createNamedBuilder($nom, FormType::class, null  ,  ['csrf_protection' => false ])
			                ->add('expert', ChoiceType::class,
			                    [
				                'multiple'  =>  false,
				                'required'  =>  false,
				                //'choices'       => $choices, // cela ne marche pas à cause d'un bogue de symfony
				                'choice_loader' => $choice, // nécessaire pour contourner le bogue de symfony
				                'data'          => $expert,
				                //'choice_value' => function (Individu $entity = null) { return $entity->getIdIndividu(); },
				                'choice_label'  => function ($individu) { return $individu->__toString(); },
			                    ])
		                    ->getForm();
		    // Ne pas proposer plusieurs fois le même expert !
			//$choice = null;
		    //if ($expert != null) $exclus[$expert->getId()] = $expert;
		    $first = false;
		}
		return $forms;
    }
		
	/******
	* Appelée quand on clique sur Notifier les experts
	* Envoie une notification aux experts passés en paramètre
	*
	* params $experts = liste d'experts (pas utilisé)
	*        $version = la version à expertiser
	*
	*****/
	protected function notifierExperts($experts, $version)
	{
		$expertises = $version->getExpertise();
		foreach ($expertises as $e)
		{
			$exp = $e->getExpert();
			if ($exp != null)
			{
				$dest = [ $exp->getMail() ];
				if ($dest!=null)
				{
					$params = [ 'object' => $e ];
					Functions::sendMessage ('notification/affectation_expert_version-sujet.html.twig',
											'notification/affectation_expert_version-contenu.html.twig',
											$params,
											$dest);
				}
			}
		}
	}

}
