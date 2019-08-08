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

namespace AppBundle\Controller;

use AppBundle\Entity\Expertise;
use AppBundle\Entity\Individu;
use AppBundle\Entity\Thematique;
use AppBundle\Entity\Projet;
use AppBundle\Entity\Version;
use AppBundle\Entity\Rallonge;
use AppBundle\Entity\Session;
use AppBundle\Entity\CollaborateurVersion;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use AppBundle\AppBundle;
use AppBundle\Utils\Functions;
use AppBundle\Utils\Menu;
use AppBundle\Utils\Etat;
use AppBundle\Utils\Signal;
use AppBundle\Workflow\Projet\ProjetWorkflow;
use AppBundle\Workflow\Version\VersionWorkflow;
use AppBundle\Utils\GramcDate;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

use AppBundle\Form\ChoiceList\ExpertChoiceLoader;

/**
 * Expertise controller.
 *
 * @Route("expertise")
 * @Security("has_role('ROLE_PRESIDENT')")
 */
class ExpertiseController extends Controller
{

	/***
	 * Renvoie un tableau de formulaires pour choisir les experts d'une version
	 *
	 *   params  $version
	 *   return  un tableau de forms
	 *
	 */
	 private static function cmpExpertises($a,$b) { return $a->getId() > $b ->getId(); }
	 private function getExpertForms(Version $version)
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

			$forms[] = $this->get( 'form.factory')->createNamedBuilder($nom, FormType::class, null  ,  ['csrf_protection' => false ])
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

	/***
	 * Renvoie un formulaire avec une case à cocher, rien d'autre
	 *
	 *   params  $version
	 *   return  une form
	 *
	 */
	private function getSelForm(Version $version)
	{
		$nom = 'selection_'.$version->getIdVersion();
		return $this->get( 'form.factory')  -> createNamedBuilder($nom, FormType::class, null, ['csrf_protection' => false ])
										    -> add('sel',CheckboxType::class, [ 'required' =>  false ])
										    ->getForm();
	}


 /**
     * Affectation des experts
     *
     * @Route("/affectation_test", name="affectation_test")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_PRESIDENT')")
     */
    public function affectationTestAction(Request $request)
    {
	    $session = Functions::getSessionCourante();
	    $annee = $session->getAnneeSession();

	    $versions =  AppBundle::getRepository(Version::class)->findAnneeTestVersions($annee);
	    //return new Response( Functions::show( $versions ) );
	    return $this->affectationGenerique($request, $versions, true);
    }

    ///////////////////////

    /**
     * Affectation des experts
     *	  Affiche l'écran d'affectation des experts
     *
     * @Route("/affectation", name="affectation")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_PRESIDENT')")
     */
    public function affectationAction(Request $request)
    {
	    //Functions::debugMessage(__METHOD__   . " " .  print_r($_POST, true) );
	    $sessionData       =   Functions::selectSession($request); // formulaire
	    $session = $sessionData['session'];

	    $versions =  AppBundle::getRepository(Version::class)->findSessionVersions($session);
	    return $this->affectationGenerique($request, $versions, false );
    }

    ///////////////////////

    private function affectationGenerique(Request $request, $versions, $projets_test = false)
    {
	    //Functions::debugMessage(__METHOD__   . " " .  print_r($_POST, true) );
	    // Formulaire de sélection des sessions
	    if( $projets_test == false )
        {
	        $sessionData       =   Functions::selectSession($request); // formulaire
	        $session = $sessionData['session'];
	        $etatSession    =   $session->getEtatSession();
        }
	    else
	        $session = Functions::getSessionCourante();


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

	    ///////////////////////

	    $experts = [];
	    foreach( AppBundle::getRepository(Individu::class)->findBy(['expert' => true]) as $expert )
	    {
	        $experts[ $expert->getIdIndividu() ] = ['expert' => $expert, 'projets' => 0 ];
		}

	    ////////////////////////

        $nbProjets      =   0;
        $nouveau        =   0;
        $renouvellement =   0;
        $nbDemHeures    =   0;
        $nbAttHeures    =   0;

        $forms      =   [];
        $expertId   =   [];
        $attHeures  =   [];

		$form_buttons = $this->get('form.factory')->createNamedBuilder('BOUTONS', FormType::class, null, ['csrf_protection' => false ])
		                     ->add( "sub1",SubmitType::Class, ['label' => 'Affecter les experts', 'attr' => ['title' => 'Les experts seront affectés incognito'] ] )
		                     ->add( "sub2",SubmitType::Class, ['label' => 'Affecter et notifier les experts', 'attr' => ['title' => 'Les experts affectés recevront une notification par courriel'] ] )
		                     ->add( "sub3",SubmitType::Class, ['label' => 'Ajouter une expertise', 'attr' => ['title' => 'Ajouter un expert si possible'] ] )
		                     ->add( "sub4",SubmitType::Class, ['label' => 'Supprimer une expertise', 'attr' => ['title' => 'ATTENTION - Risque de perte de données'] ] )
		                     ->getForm();

		$form_buttons->handleRequest($request);

		// 1ere etape = Traitement du formulaire qui vient d'être soumis
		//              On boucle sur les versions:
		//                  - Une version non sélectionnée est ignorée
		//                  - Pour chaque version sélectionnée on fait une action qui dépend du bouton qui a été cliqué
		//
		if ($form_buttons->isSubmitted())
		{
	        foreach( $versions as $version )
			{
	            $etatVersion    =   $version->getEtatVersion();
	            if( $etatVersion == Etat::EDITION_DEMANDE || $etatVersion == Etat::ANNULE ) continue; // on n'affiche pas de version en cet état

				// La version est-elle sélectionnée ? - Si non on ignore
				$selform = $this->getSelForm($version);
				$selform->handleRequest($request);
				if ($selform->getData()['sel']==false)
				{
		            //Functions::debugMessage( __METHOD__ . $version->getIdVersion().' selection NON');
		            continue;
				}
				//else
				//{
				//	Functions::debugMessage( __METHOD__ . $version->getIdVersion().' selection OUI');
				//}

	            //$expert = $version->getExpert();
	            //$projet = $version->getProjet();

				// traitement du formulaire d'affectation - On ignore les projets non sélectionnés
				$forms   = $this->getExpertForms($version);

				$experts = [];
				foreach ($forms as $f)
				{
					$f->handleRequest($request);
					$experts[] = $f->getData()['expert'];
				}

				// Traitements différentiés suivant le bouton sur lequel on a cliqué
				if ($form_buttons->get('sub1')->isClicked())
				{
					$this->affecterExpertsToVersion($experts,$version);
				}
				elseif ($form_buttons->get('sub2')->isClicked())
				{
					$this->affecterExpertsToVersion($experts,$version);
					$this->notifierExperts($experts,$version);
				}
				elseif ($form_buttons->get('sub3')->isClicked())
				{
					$this->addExpertiseToVersion($version);
				}
				elseif ($form_buttons->get('sub4')->isClicked())
				{
					$this->remExpertiseFromVersion($version);
				}
				else
				{
					continue;
				}
			}
			// doctrine cache les expertises précédentes du coup si on ne redirige pas
			// l'affichage ne sera pas correctement actualisé !
			// Essentiellement avec sub3 (ajout d'expertise)
			return $this->redirectToRoute('affectation');
		}

		// 2nde etape = Envoi des formulaires pour affichage
        foreach( $versions as $version )
		{
            $etatVersion    =   $version->getEtatVersion();
            if( $etatVersion == Etat::EDITION_DEMANDE || $etatVersion == Etat::ANNULE ) continue; // on n'affiche pas de version en cet état

            $experts = $version->getExperts();
            //$projet = $version->getProjet();

			// Formulaire pour la sélection
			$sform = $this->getSelForm($version)->createView();
			$forms['selection_'.$version->getIdVersion()] = $sform;

			// Génération du formulaire de choix de l'expert
			$eforms  = $this->getExpertForms($version);
			foreach ($eforms as &$f) $f=$f->createView();
			$forms[$version->getIdVersion()] = $eforms;

            //if( count($experts  === 0)
			//{
            //    $expertId[$version->getIdVersion()]    =   '';
			//}
            //else
			//{
            //    $expertId[$version->getIdVersion()]    =  $expert->getIdIndividu();
            //    if( isset( $experts[ $expert->getIdIndividu()] ) ) // on peut avoir des anciens experts
            //        $experts[ $expert->getIdIndividu()]['projets']++;
            //    else
            //        $experts[ $expert->getIdIndividu() ] = ['expert' => $expert, 'projets' => 1 ];
			//}

            if( $version->getPrjThematique() != null )
                 $thematiques[ $version->getPrjThematique()->getIdThematique() ]['projets']++;

            $nbProjets++;
            if( $version->getProjet()->countVersions() > 1 )
                $renouvellement++;
            else
                $nouveau++;

            $nbDemHeures    +=  $version->getDemHeures();
            if( $version->getExpertise() != null && $version->getExpertise()[0] != null )
			{
                $heures         =   $version->getExpertise()[0]->getNbHeuresAtt();
                $nbAttHeures    +=  $heures;
                $attHeures[$version->getIdVersion()]    =  $heures;
			}
            else
            {
                $attHeures[$version->getIdVersion()]    = 0;
			}
		}

		$forms['BOUTONS'] = $form_buttons->createView();

        /* if( $forms['17BP17003'] == $forms['17BP17002'] )
            Functions::debugMessage( __METHOD__ . ' forms same' . Functions::show( $forms ) );
        else
            Functions::debugMessage( __METHOD__ . ' forms not same' . Functions::show( $forms ) ); */

        ///////////////

        if( $projets_test == true )
		{
            $sessionForm    =   null;
            $session        =   null;
            $etatSession    =   null;
		}
        else
            $sessionForm    =   $sessionData['form']->createView();

        return $this->render('expertise/affectation.html.twig',
            [
            'versions'   =>  $versions,
            'forms'     =>  $forms,
            'sessionForm'   =>  $sessionForm,
            //'expertId'  =>  $expertId,
            'session'   => $session,
            'thematiques'   =>  $thematiques,
            //'experts'   =>  $experts,
            'experts' => [],
            'nbProjets' => $nbProjets,
            'renouvellement'    => $renouvellement,
            'nouveau'   => $nouveau,
            'nbDemHeures'   => $nbDemHeures,
            'nbAttHeures'   => $nbAttHeures,
            'attHeures'     => $attHeures,
            'libelleEtatSession'    => Etat::getLibelle( $etatSession ),
            'projets_test'      => $projets_test,
            'annee'         =>  Functions::getSessionCourante()->getAnneeSession() + 2000,
            ]);
    }

	/******
	* Appelée par affectationGenerique quand on clique sur Notifier les experts
	* Envoie une notification aux experts passés en paramètre
	*
	* params $experts = liste d'experts (pas utilisé)
	*        $version = la version à expertiser
	*
	*****/
	private function notifierExperts($experts, $version)
	{
		$expertises = $version->getExpertise();
		foreach ($expertises as $e)
		{
			$dest = [ $e->getExpert()->getMail() ];

			$params = [ 'object' => $e ];
			Functions::sendMessage ('notification/affectation_expert_version-sujet.html.twig',
									'notification/affectation_expert_version-contenu.html.twig',
									$params,
									$dest);
		}
	}

	/**
	 * Appelée par affectationGenerique, sauvegarde les experts associés à la version
	 *
	 ***/
	private function affecterExpertsToVersion($experts,Version $version)
	{
		$em = $this->getDoctrine()->getManager();
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
		// Je n'utilise pas Functions::sauvegarder car je sauvegarde plusisuers objets à la fois !
		$em->flush();
	}

	/**
	* Appelée par affectationGenerique, ajoute une expertise à la version
	* Si on atteint le paramètre max_expertises_nb, ne fait rien
	* TODO - Si on atteint le paramètre max_expertises_nb, envoyer un message d'erreur !
	*
	* param = $version
	* Return= rien
	*
	****/
	private function addExpertiseToVersion(Version $version)
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
	* Appelée par affectationGenerique, retire la dernière expertise de la version
	* S'il n'en reste qu'une, ne fait rien
	* TODO - Plutôt uqe de ne rien faire, envoyer un message d'erreur !
	*
	* param = $version
	* Return= rien
	*
	****/
	private function remExpertiseFromVersion(Version $version)
	{
		$expertises = $version->getExpertise()->toArray();
		if (count($expertises) > 1)
		{
			usort($expertises,['self','cmpExpertises']);
			$expertise = end($expertises);
			$em = $this->getDoctrine()->getManager();
			$em->remove($expertise);
			$em->flush();
		}
	}

    /**
     * Lists all expertise entities.
     *
     * @Route("/", name="expertise_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $expertises = $em->getRepository(Expertise::class)->findAll();
        $projets =  AppBundle::getRepository(Projet::class)->findAll();


        return $this->render('expertise/index.html.twig',
            [
            'expertises' => $expertises,
            ]);
    }


	/**
	 * Liste les expertises attribuées à un expert
	 *       Aussi les anciennes expertises réalisées par cet expert
	 *
	 * @Route("/liste", name="expertise_liste")
	 * @Method("GET")
	 * @Security("has_role('ROLE_EXPERT')")
	 */
    public function listeAction()
    {
        $em = $this->getDoctrine()->getManager();

        $moi    =   AppBundle::getUser();
        if( is_string( $moi ) ) Functions::createException();

        $mes_thematiques    =   $moi->getThematique();
        //Functions::debugMessage(__METHOD__ . " mes thématiques " . Functions::show($mes_thematiques) );

        $expertiseRepository = AppBundle::getRepository(Expertise::class);
        $session             = Functions::getSessionCourante();

        $expertises  = $expertiseRepository->findExpertisesByExpert($moi, $session);

        $my_expertises  =   [];
        foreach( $expertises as $expertise )
        {
			//Functions::debugMessage(__METHOD__ . " koukou1 expertise " . $expertise->getId() . " exp " . $expertise->getExpert() . " vers " . $expertise->getVersion());

            // On n'affiche pas les expertises définitives
            if ( $expertise->getDefinitif()) continue;

            $version    =   $expertise->getVersion();

            $projetId   =   $version->getProjet()->getIdProjet();
            $thematique =   $version->getPrjThematique();

            $my_expertises[ $version->getIdVersion() ] = [
	                                                        'expertise' => $expertise,
	                                                        'demHeures' => $version->getDemHeures(),
	                                                        'versionId' => $version->getIdVersion(),
	                                                        'projetId'  => $projetId,
	                                                        'titre'     => $version->getPrjTitre(),
	                                                        'thematique' => $thematique,
	                                                        'responsable'   =>  $version->getResponsable(),
	                                                        'expert'        => true,
                                                         ];
        }

		Functions::debugMessage(__METHOD__ . " my_expertises " . Functions::show($my_expertises));
		Functions::debugMessage(__METHOD__ . " mes_thematiques " . Functions::show($mes_thematiques));

        ////////////////
        $expertises_by_thematique   =   [];
        foreach( $mes_thematiques as $thematique )
        {
            $expertises_thematique =  $expertiseRepository->findExpertisesByThematique($thematique, $session);
            Functions::debugMessage(__METHOD__ . " expertises pour thématique ".Functions::show($thematique). '-> '.Functions::show($expertises_thematique));
            //$expertises_thematique =  $expertiseRepository->findExpertisesByThematiqueForAllSessions($thematique);
            $expertises =   [];
            foreach( $expertises_thematique as $expertise )
            {

                // On n'affiche pas les expertises définitives
                if ( $expertise->getDefinitif()) continue;

                $version    =  $expertise->getVersion();
                $projetId   =  $version->getProjet()->getIdProjet();

                $output =               [
                                        'expertise'   => $expertise,
                                        'demHeures'   => $version->getDemHeures(),
                                        'versionId'   => $version->getIdVersion(),
                                        'projetId'    => $projetId,
                                        'titre'       => $version->getPrjTitre(),
                                        'thematique'  => $thematique,
                                        'responsable' =>  $version->getResponsable(),
                                        ];
				Functions::debugMessage(__METHOD__ . " expertise ".$expertise->getId());

				// On n'affiche pas deux expertises vers la même version
				if (!array_key_exists( $version->getIdVersion(), $expertises ))
				{
					// Si j'ai une expertise vers cette version, je remplace l'expertise trouvée par la mienne
	                if( array_key_exists( $version->getIdVersion(), $my_expertises ) )
	                {
						$output = $my_expertises[ $version->getIdVersion() ];
	                    unset( $my_expertises[ $version->getIdVersion() ]);
	                    $output['expert']   =   true;
	                }
	                else
	                {
	                    $output['expert']   =   false;
					}
	                $expertises[$version->getIdVersion()]   =   $output;
				}
            }

            $expertises_by_thematique[] = [ 'expertises' => $expertises, 'thematique' => $thematique ];
        }

        ///////////////////

        $old_expertises = [];
        $expertises  = $expertiseRepository->findExpertisesByExpertForAllSessions($moi);
        foreach( $expertises as $expertise )
        {
            // Les expertises non définitives ne sont pas "old"
            if ( ! $expertise->getDefinitif()) continue;

            $version    = $expertise->getVersion();
            $id_session = $version->getSession()->getIdSession();
            $output = [
                        'projetId'   => $version->getProjet()->getIdProjet(),
                        'sessionId'  => $id_session,
                        'thematique' => $version->getPrjThematique(),
                        'titre'      => $version->getPrjTitre(),
                        'demHeures'  => $version->getDemHeures(),
                        'attrHeures' => $version->getAttrHeures(),
                        'responsable' =>  $version->getResponsable(),
                        'versionId'   => $version->getIdVersion(),
                       ];
            $old_expertises[] = $output;
        };

        // rallonges

        $rallonges  =   [];
        $all_rallonges  =   AppBundle::getRepository(Rallonge::class)->findRallongesExpert($moi);
        foreach( $all_rallonges as $rallonge )
		{
            $version    =   $rallonge->getVersion();
            if( $version == null )
			{
                Functions::errorMessage(__METHOD__ . ':'. __FILE__ . " Rallonge " . $rallonge . " n'a pas de version !");
                continue;
			}
            $projet = $version->getProjet();
            if( $projet == null )
			{
                Functions::errorMessage(__METHOD__ . ':'. __FILE__ . " Version " . $version . " n'a pas de projet !");
                continue;
			}
            $rallonges[$projet->getIdProjet()]['projet']    =   $projet;
            $rallonges[$projet->getIdProjet()]['version']   =   $version;
            $rallonges[$projet->getIdProjet()]['rallonges'][$rallonge->getIdRallonge()] =   $rallonge;
		}

        ///////////////////////

        return $this->render('expertise/liste.html.twig',
            [
            'rallonges'                  => $rallonges,
            'expertises_by_thematique'   => $expertises_by_thematique,
            'expertises_hors_thematique' =>  $my_expertises,
            'old_expertises'             => $old_expertises
            ]);
    }

    /**
     * Creates a new expertise entity.
     *
     * @Route("/new", name="expertise_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $expertise = new Expertise();
        $form = $this->createForm('AppBundle\Form\ExpertiseType', $expertise);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($expertise);
            $em->flush($expertise);

            return $this->redirectToRoute('expertise_show', array('id' => $expertise->getId()));
        }

        return $this->render('expertise/new.html.twig', array(
            'expertise' => $expertise,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a expertise entity.
     *
     * @Route("/{id}", name="expertise_show")
     * @Method("GET")
     */
    public function showAction(Expertise $expertise)
    {
        $deleteForm = $this->createDeleteForm($expertise);

        return $this->render('expertise/show.html.twig', array(
            'expertise' => $expertise,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing expertise entity.
     *
     * @Route("/{id}/edit", name="expertise_edit")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_PRESIDENT')")
     */
    public function editAction(Request $request, Expertise $expertise)
    {
        $deleteForm = $this->createDeleteForm($expertise);
        $editForm = $this->createForm('AppBundle\Form\ExpertiseType', $expertise);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('expertise_edit', array('id' => $expertise->getId()));
        }

        return $this->render('expertise/edit.html.twig', array(
            'expertise' => $expertise,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * L'expert vient de cliquer sur le bouton "Modifier expertise"
     * Il entre son expertise et éventuellement l'envoie
     *
     * @Route("/{id}/modifier", name="expertise_modifier")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_EXPERT')")
     */
    public function modifierAction(Request $request, Expertise $expertise)
    {
        // ACL
        $moi = AppBundle::getUser();
        if( is_string( $moi ) ) Functions::createException(__METHOD__ . ":" . __LINE__ . " personne connecté");
        elseif( $expertise->getExpert() == null ) Functions::createException(__METHOD__ . ":" . __LINE__ . " aucun expert pour l'expertise " . $expertise );
        elseif( ! $expertise->getExpert()->isEqualTo( $moi ) ) {
            Functions::createException(__METHOD__ . ":" . __LINE__ . "  " . $moi .
                " n'est pas un expert de l'expertise " . $expertise . ", c'est " . $expertise->getExpert() );
        }

        $session    =   Functions::getSessionCourante();
        $commGlobal =  $session->getcommGlobal();
        $anneeCour  = 2000 +$session->getAnneeSession();
        $anneePrec  = $anneeCour - 1;

        // Le comportement diffère suivant le type de projet
        $version = $expertise->getVersion();
        if( $version == null )
            Functions::createException(__METHOD__ . ":" . __LINE__ . "  " . $expertise . " n'a pas de version !" );
        $projet_type = $version->getProjet()->getTypeProjet();

        // Si c'est un projet de type PROJET_SESS, le bouton ENVOYER n'est disponible QUE si la session est en états ATTENTE ou ACTIF
        // Sinon le bouton est toujours disponible
		if ( $projet_type != Projet::PROJET_SESS )
		{
			$session_edition = false; // Autoriser le bouton Envoyer
		}
		else
		{
	        if ( $session -> getEtatSession() != Etat::EDITION_EXPERTISE && $session -> getEtatSession() != Etat::ACTIF )
	        {
	            $session_edition = true;
			}
			else
			{
	            $session_edition = false;
			}
		}

		// Création du formulaire
        $editForm = $this->createFormBuilder($expertise)
            ->add('commentaireInterne', TextAreaType::class, [ 'required' => false ] )
            ->add('validation', ChoiceType::class ,
                [
                'multiple' => false,
                'choices'   =>  [ 'Accepter' =>     1, 'Accepter, avec zéro heure' => 2, 'Refuser définitivement et fermer le projet'    => 0, ],
                ]);

		// Projet au fil de l'eau, le commentaire externe est réservé au président !
		// On utilise un champ caché, de cette manière le formulaire sera valide
		if ( $projet_type != Projet::PROJET_FIL || AppBundle::isGranted('ROLE_PRESIDENT'))
		{
            $editForm->add('commentaireExterne', TextAreaType::class, [ 'required' => false ] );
		}
		else
		{
			$editForm->add('commentaireExterne', HiddenType::class, [ 'data' => 'Commentaire externe réservé au Comité' ] );

		}

		// Par défaut on attribue les heures demandées !
        if( $expertise->getNbHeuresAtt() == 0 )
            $editForm->add('nbHeuresAtt', IntegerType::class , ['required'  =>  false, 'data' => $version->getDemHeures(), ]);
        else
            $editForm->add('nbHeuresAtt', IntegerType::class , ['required'  =>  false, ]);

		// En session B, on propose une attribution spéciale pour heures d'été
		// TODO A affiner car en septembre avec des projets PROJET_FIL en sera toujours en session  B et c'est un peu couillon de demander cela
		if ( $projet_type != Projet::PROJET_TEST )
	        if ($session->getTypeSession())
	            $editForm -> add('nbHeuresAttEte');

        //$definitif  =   $expertise->getDefinitif();
        //if( $definitif == false )
            //$editForm = $editForm->add('enregistrer',   SubmitType::class, ['label' =>  'Enregistrer' ]);
        //if( $definitif == false && $session_edition == false)
            //$editForm   =   $editForm->add('envoyer',   SubmitType::class, ['label' =>  'Envoyer' ]);
        //$editForm->add( 'retour',   SubmitType::Class );

		// Les boutons d'enregistrement ou d'envoi
		$editForm = $editForm->add('enregistrer', SubmitType::class, ['label' =>  'Enregistrer' ]);
        if( $session_edition == false)
            $editForm   =   $editForm->add('envoyer',   SubmitType::class, ['label' =>  'Envoyer' ]);
        $editForm->add( 'retour',   SubmitType::Class );


        $editForm = $editForm->getForm();
        $editForm->handleRequest($request);

        if( $editForm->isSubmitted() && ! $editForm->isValid() )
             Functions::warningMessage(__METHOD__ . " form error " .  Functions::show($editForm->getErrors() ) );

        // Bouton RETOUR
        if( $editForm->isSubmitted() && $editForm->get('retour')->isClicked() )
             return $this->redirectToRoute('expertise_liste');

        $erreur = 0;
        $erreurs = [];
        if ($editForm->isSubmitted() /* && $editForm->isValid()  */ )
        {
            $erreurs = Functions::dataError( $expertise);

            $validation = $expertise->getValidation();

            if( $validation != 1 )
			{
                $expertise->setNbHeuresAtt(0);
                $expertise->setNbHeuresAttEte(0);
                if ( $validation == 2 && $projet_type == Projet::PROJET_TEST )
                {
                    $erreurs[] = "Pas possible de refuser un projet test juste pour cette session";
                }
			}

            $em = AppBundle::getManager();
            $em->persist( $expertise );
            $em->flush();

            // Bouton ENVOYER --> Vérification des champs non renseignés puis demande de confirmation
            if( ! $session_edition && $editForm->get('envoyer')->isClicked() && $erreurs == null )
                    return $this->redirectToRoute('expertise_validation', [ 'id' => $expertise->getId() ]);
        }

        $toomuch = false;
        if ($session->getTypeSession() && ! $expertise->getVersion()->isProjetTest() )
        {
            $version_prec = $expertise->getVersion()->versionPrecedente();
            $attr_a       = ($version_prec==null) ? 0 : $version_prec->getAttrHeures();
            $dem_b        = $expertise->getVersion()->getDemHeures();
            $toomuch      = Functions::is_demande_toomuch($attr_a,$dem_b);
        }

		// Projets au fil de l'eau avec plusieurs exepertises:
		//    Si je suis président, on va chercher ces expertises pour affichage
		$autres_expertises = [];
		if ($projet_type == Projet::PROJET_FIL && AppBundle::isGranted('ROLE_PRESIDENT') )
		{
			$expertiseRepository = AppBundle::getRepository(Expertise::class);
			$autres_expertises   = $expertiseRepository -> findExpertisesForVersion($version,$moi);
		}

		// LA SUITE DEPEND DU TYPE DE PROJET !
		// Le workflow n'est pas le même suivant le type de projet, donc l'expertise non plus.
		switch ($projet_type)
		{
			case Projet::PROJET_SESS:
				$twig = 'expertise/modifier_projet_sess.html.twig';
				break;
			case Projet::PROJET_TEST:
				$twig = 'expertise/modifier_projet_test.html.twig';
				break;
			case Projet::PROJET_FIL:
				$twig = 'expertise/modifier_projet_fil.html.twig';
				break;
		}

        return $this->render($twig,
            [
            'expertise'         => $expertise,
            'autres_expertises' => $autres_expertises,
            'version'           => $expertise->getVersion(),
            'edit_form'         => $editForm->createView(),
            'anneePrec'         => $anneePrec,
            'anneeCour'         => $anneeCour,
            'session'           => $session,
            'session_edition'   => $session_edition,
            'erreurs'           => $erreurs,
            'toomuch'           => $toomuch,
            ]);
    }

    /**
     *
     * L'expert vient de cliquer sur le bouton "Envoyer expertise"
     * On lui envoie un écran de confirmation
     *
     * @Route("/{id}/valider", name="expertise_validation")
     * @Method({"GET","POST"})
     * @Security("has_role('ROLE_EXPERT')")
     */
    public function validationAction(Request $request, Expertise $expertise)
    {
	    // ACL
	    $moi = AppBundle::getUser();
	    if( is_string( $moi ) ) Functions::createException(__METHOD__ . ":" . __LINE__ . " personne connecté");
	    elseif( $expertise->getExpert() == null ) Functions::createException(__METHOD__ . ":" . __LINE__ . " aucun expert pour l'expertise " . $expertise );
	    elseif( ! $expertise->getExpert()->isEqualTo( $moi ) )
	        Functions::createException(__METHOD__ . ":" . __LINE__ . "  " . $moi .
	            " n'est pas un expert de l'expertise " . $expertise . ", c'est " . $expertise->getExpert());


	    $editForm = $this->createFormBuilder($expertise)
	                ->add('confirmer',   SubmitType::class, ['label' =>  'Confirmer' ])
	                ->add('annuler',   SubmitType::class, ['label' =>  'Annuler' ])
	                ->getForm();

	    $editForm->handleRequest($request);

		$em = AppBundle::getManager();
	    if( $editForm->isSubmitted()  )
        {
			// Bouton Annuler
	        if( $editForm->get('annuler')->isClicked() )
	            return $this->redirectToRoute('expertise_modifier', [ 'id' => $expertise->getId() ] );

			// Bouton Confirmer
			// Si projet au fil de l'eau mais qu'on n'est pas président, on n'envoit pas de signal
			// Dans tous les autres cas on envoie un signal CLK_VAL_EXP_XXX
			//
			$type_projet = $expertise->getVersion()->getProjet()->getTypeProjet();
			if ( $type_projet != Projet::PROJET_FIL || AppBundle::isGranted('ROLE_PRESIDENT') )
			{
		        $workflow   =   new ProjetWorkflow();

		        $expertise->getVersion()->setAttrHeures($expertise->getNbHeuresAtt() );
		        $expertise->getVersion()->setAttrHeuresEte($expertise->getNbHeuresAttEte() );
		        $expertise->getVersion()->setAttrAccept($expertise->getValidation()  );

		        $validation =  $expertise->getValidation();

		        $rtn    =   null;

		        if( $validation == 1 )      $signal = Signal::CLK_VAL_EXP_OK;
		        elseif( $validation == 2 )  $signal = Signal::CLK_VAL_EXP_CONT;
		        elseif( $validation == 0 )  $signal = Signal::CLK_VAL_EXP_KO;

		        $rtn    =   $workflow->execute( $signal, $expertise->getVersion()->getProjet() );
		        if( $rtn != true )
		            Functions::errorMessage(__METHOD__ . ":" . __LINE__ . " Transition avec " .  Signal::getLibelle( $signal )
		            . "(" . $signal . ") pour l'expertise " . $expertise . " avec rtn = " . Functions::show($rtn) );
		        else
		            $expertise->setDefinitif(true);

		        $em->persist( $expertise );
		        $em->flush();
			}
			else
			{
				$expertise->setDefinitif(true);
		        $em->persist( $expertise );
		        $em->flush();

		        // Envoi d'une notification aux présidents
		        $dest = Functions::mailUsers([ 'P' ]);
		        $params = [ 'object' => $expertise ];
		        Functions::sendMessage ('notification/expertise_projet_fil_pour_president-sujet.html.twig',
		        						'notification/expertise_projet_fil_pour_president-contenu.html.twig',
		        						$params,
		        						$dest);
			}

	        return $this->redirectToRoute('expertise_liste');
        }

		// LA SUITE DEPEND DU TYPE DE PROJET !
		// Le workflow n'est pas le même suivant le type de projet, donc l'expertise non plus.

		$version = $expertise->getVersion();
        $projet_type = $version->getProjet()->getTypeProjet();
		switch ($projet_type)
		{
			case Projet::PROJET_SESS:
				$twig = 'expertise/valider_projet_sess.html.twig';
				break;
			case Projet::PROJET_TEST:
				$twig = 'expertise/valider_projet_test.html.twig';
				break;
			case Projet::PROJET_FIL:
				$twig = 'expertise/valider_projet_fil.html.twig';
				break;
		}

        return $this->render($twig,
            [
            'expertise'  => $expertise,
            'version'    => $expertise->getVersion(),
            'edit_form'  => $editForm->createView(),
            ]);
    }
    /**
     * Deletes a expertise entity.
     *
     * @Route("/{id}", name="expertise_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Expertise $expertise)
    {
        $form = $this->createDeleteForm($expertise);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($expertise);
            $em->flush($expertise);
        }

        return $this->redirectToRoute('expertise_index');
    }

    /**
     * Creates a form to delete a expertise entity.
     *
     * @param Expertise $expertise The expertise entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Expertise $expertise)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('expertise_delete', array('id' => $expertise->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
