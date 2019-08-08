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

use AppBundle\Entity\Projet;
use AppBundle\Entity\Version;
use AppBundle\Entity\Session;
use AppBundle\Entity\CollaborateurVersion;
use AppBundle\Entity\Thematique;
use AppBundle\Entity\Expertise;
use AppBundle\Entity\Individu;
use AppBundle\Entity\Sso;
use AppBundle\Entity\CompteActivation;
use AppBundle\Entity\Journal;
use AppBundle\Entity\Compta;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

use AppBundle\AppBundle;
use AppBundle\Utils\Functions;
use AppBundle\Utils\Menu;
use AppBundle\Utils\Etat;
use AppBundle\Utils\Signal;
use AppBundle\Workflow\Projet\ProjetWorkflow;
use AppBundle\Workflow\Version\VersionWorkflow;
use AppBundle\Utils\GramcDate;

use AppBundle\GramcGraf\Calcul;
use AppBundle\GramcGraf\CalculTous;
use AppBundle\GramcGraf\Stockage;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

// Pour le tri numérique sur les années, en commençant par la plus grande - cf. resumesAction
function cmpProj($a,$b) { return intval($a['annee']) < intval($b['annee']); }

/**
 * Projet controller.
 *
 * @Security("has_role('ROLE_OBS')")
 * @Route("projet")
 */
 // Tous ces controleurs sont exécutés au moins par OBS, certains par ADMIN seulement
 // et d'autres par DEMANDEUR

class ProjetController extends Controller
{

    private static $count;

    /**
     * Lists all projet entities.
     *
     * @Route("/", name="projet_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $projets = $em->getRepository('AppBundle:Projet')->findAll();

        return $this->render('projet/index.html.twig', array(
            'projets' => $projets,
        ));
    }

    /**
     * Delete old data.
     *
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/old", name="projet_nettoyer")
     * @Method({"GET","POST"})
     */
    public function oldAction(Request $request)
    {
    $list = [];
    $mauvais_projets = [];
    static::$count = [];
    $annees =   [];

    $all_projets = AppBundle::getRepository(Projet::class)->findAll();
    foreach( $all_projets as $projet )
        {
        $derniereVersion    =  $projet->derniereVersion();
        if(  $derniereVersion == null )
            {
            $mauvais_projets[$projet->getIdProjet()]    =   $projet;
            }
        else
            $annee = $projet->derniereVersion()->getAnneeSession();
        $list[$annee][] = $projet;
        }
    foreach( $list as $annee => $projets )
        {
        static::$count[$annee]  =   count($projets);
        $annees[]       =   $annee;
        }

    asort($annees );
    $form = AppBundle::createFormBuilder()
            ->add('annee',   ChoiceType::class,
                    [
                    'required'  =>  false,
                    'label'     => ' Année ',
                    'choices'   =>  $annees,
                    'choice_label' => function ($choiceValue, $key, $value )
                        {
                        return  $choiceValue . '  (' . ProjetController::$count[$choiceValue] . ' projets)';
                        }
                    ])
        ->add('supprimer projets', SubmitType::class, ['label' => ""])
        ->add('supprimer utilisateurs sans projet', SubmitType::class, ['label' => ""])
        ->add('nettoyer le journal', SubmitType::class, ['label' => ""])
        ->getForm();

    $date = GramcDate::get();
    $date->sub( \DateInterval::createFromDateString('1 year') );
    $individus = AppBundle::getRepository(Individu::class)->liste_avant( $date );
    $utilisateurs_a_effacer = Functions::utilisateurs_a_effacer($individus);
    $individus_effaces = [];
    $projets_effaces = [];
    $old_journal = null;
    $journal = false;

    $em =   AppBundle::getManager();

    $form->handleRequest($request);

    if( $form->isSubmitted() && $form->isValid() && $form->get('supprimer utilisateurs sans projet')->isClicked() )
        {
        $individus_effaces = Functions::effacer_utilisateurs($utilisateurs_a_effacer);
        $utilisateurs_a_effacer = [];
        //return new Response( Functions::show( $individus_effaces ) );
        }
    elseif( $form->isSubmitted() && $form->isValid() && $form->get('nettoyer le journal')->isClicked() )
        {
        if( AppBundle::hasParameter('old_journal') )
            {
            $old_journal = intval( AppBundle::getParameter('old_journal') );
            if( $old_journal > 0 )
	    {
                $date = GramcDate::get();
                $date->sub( \DateInterval::createFromDateString($old_journal . ' year') );
                // return new Response( Functions::show( [$old_journal,$date] ) );
                $journal = AppBundle::getRepository(Journal::class)->liste_avant( $date );
                foreach( $journal as $item) $em->remove( $item );
                $em->flush();
                $journal = true;
                }
            else
                Functions::errorMessage(__METHOD__ . ":" . __LINE__ . " La valeur du paramètre old_journal est " . $old_journal);
            }
        else
            Functions::errorMessage(__METHOD__ . ":" . __LINE__ . " Le paramètre old_journal manque");
        }
    elseif( $form->isSubmitted() && $form->isValid() && $form->get('supprimer projets')->isClicked() )
	{
        $annee = $form->getData()['annee']; // par exemple 2014

        $key = array_search($annee,$annees); // supprimer l'année de la liste du formulaire
        if( $key !== false)   unset($annees[$key]);

        $individus = [];
        foreach( $list[$annee] as $projet )
            foreach( $projet->getVersion() as $version )
                {
                foreach( $version->getCollaborateurs() as $collaborateur )
                    $individus[$collaborateur->getIdIndividu()]    =  $collaborateur;
                foreach( $version->getExpertise() as $expertise )
                    if( $expertise->getExpert() != null )
                        $individus[$expertise->getExpert()->getIdIndividu()]    =  $expertise->getExpert();
                foreach( $version->getRallonge() as $rallonge )
                    if( $rallonge->getExpert() != null )
                        $individus[$rallonge->getExpert()->getIdIndividu()]    =  $rallonge->getExpert();

                }

        // effacer des structures

        foreach( $list[$annee] as $projet )
		{
            $em->persist( $projet );
            $projet->setVersionDerniere( null );
            $projet->setVersionActive( null );
            $em->flush();

            // effacer des documents
            Functions::erase_parameter_directory( 'rapport_directory', $projet);
            Functions::erase_parameter_directory( 'signature_directory', $projet );
            Functions::erase_parameter_directory( 'fig_directory', $projet );

            //continue; //DEBUG

            foreach( $projet->getVersion() as $version )
                {
                $em->persist( $version );
                foreach( $version->getCollaborateurVersion() as $item )
                    {
                    $em->remove( $item );
                    //$em->flush();
                    }

                foreach( $version->getExpertise() as $item )
                    {
                    $em->remove( $item );
                    //$em->flush();
                    }
                /*
                $expertises = AppBundle::getRepository(Expertise::class)->findBy(['version' => $version]);
                foreach( $expertises as $item )
                    {
                    $em->remove( $item );
                    $em->flush();
                    }
                 */


                $em->remove( $version );
                }

            $versions = AppBundle::getRepository(Version::class)->findBy(['projet' => $projet]);
			foreach( $versions as $item )
			{
				$em->remove( $item );
			}

			$loginname = strtolower($projet->getIdProjet());
            foreach( AppBundle::getRepository(Compta::class)->findBy(['loginname' => $loginname]) as $item )
            {
				$em->remove( $item );
			}

            foreach( $projet->getRapportActivite() as $rapport )
			{
                $em->remove( $rapport );
			}

            /*
            if( $projet->derniereVersion() != null )
                {
                $version = $projet->getVersionDerniere();
                $em->persist( $version );
                $em->remove( $version );
                }
            */

            Functions::erase_parameter_directory( 'fig_directory', $projet->getIdProjet() );
            $projets_effaces[] = $projet;
            Functions::infoMessage('Le projet ' . $projet . ' a été effacé ');
            $em->remove( $projet );
		}

        //return new Response(Functions::show( $projets_effaces ) ); //DEBUG

        $em->flush();

        // effacer des anciens utilisateurs

        $individus_effaces = Functions::effacer_utilisateurs($individus);

        //return new Response( Functions::show( $individus_effaces ) );
	}
    //return new Response( Functions::show( $annees ) );

    $form = AppBundle::createFormBuilder()
            ->add('annee',   ChoiceType::class,
                    [
                    'required'  =>  false,
                    'label'     => ' Année ',
                    'choices'   =>  $annees,
                    'choice_label' => function ($choiceValue, $key, $value )
                        {
                        return  $choiceValue . '  (' . ProjetController::$count[$choiceValue] . ' projets)';
                        }
                    ])
        ->add('supprimer projets', SubmitType::class, ['label' => ""])
        ->add('supprimer utilisateurs sans projet', SubmitType::class, ['label' => ""])
        ->add('nettoyer le journal', SubmitType::class, ['label' => ""])
        ->getForm();

    return $this->render('projet/old.html.twig',
            [
            'annees' => $annees,
            'count' => static::$count,
            'projets'   =>  $list,
            'projets_effaces'   => $projets_effaces,
            'mauvais_projets'   =>  $mauvais_projets,
            'utilisateurs_a_effacer'    => $utilisateurs_a_effacer,
            'individus_effaces'    =>  $individus_effaces,
            'form'  =>  $form->createView(),
            'journal' => $journal,
            'old_journal' => $old_journal,
            ]);

    //return new Response( Functions::show( $mauvais_projets ) );
    //return new Response( Functions::show( $count ) );
    //return new Response( Functions::show( $list ) );
    }

    /**
     * Lists all projet entities.
     *
     * @Route("/{id}/session_csv", name="projet_session_csv")
     * @Method({"GET","POST"})
     */
    public function sessionCSVAction(Session $session)
    {
	    $sortie = 'Projets de la session ' . $session->getId() . "\n";

	    $ligne  =   [
	                'Nouveau',
	                'id_projet',
	                'état',
	                'titre',
	                'thématique',
	                'dari',
	                'courriel',
	                'prénom',
	                'nom',
	                'laboratoire',
	                'expert',
	                'heures demandées',
	                'heures attribuées',
	                ];
		if (AppBundle::getParameter('noconso')==false)
		{
			$ligne[] = 'heures consommées';
		}
	    $sortie     .=   join("\t",$ligne) . "\n";

	    $versions = AppBundle::getRepository(Version::class)->findSessionVersions($session);
	    foreach ( $versions as $version )
		{
			$responsable    =   $version->getResponsable();
			$ligne  =
					[
					( $version->isNouvelle() == true ) ? 'OUI' : '',
					$version->getProjet()->getIdProjet(),
					$version->getProjet()->getMetaEtat(),
					Functions::string_conversion($version->getPrjTitre() ),
					Functions::string_conversion($version->getPrjThematique() ),
					$version->getPrjGenciDari(),
					$responsable->getMail(),
					Functions::string_conversion($responsable->getPrenom() ),
					Functions::string_conversion($responsable->getNom() ),
					Functions::string_conversion($version->getPrjLLabo() ),
					( $version->getResponsable()->getExpert() ) ? '*******' : $version->getExpert(),
					$version->getDemHeures(),
					$version->getAttrHeures(),
					];
			if (AppBundle::getParameter('noconso')==false)
			{
				$ligne[] = $version->getConso();
			}

			$sortie     .=   join("\t",$ligne) . "\n";
		}
	    return Functions::csv($sortie,'projet_session.csv');
    }

    /**
     * Lists all projet entities.
     *
     * @Route("/tous_csv", name="projet_tous_csv")
     * @Method({"GET","POST"})
     */
    public function tousCSVAction()
    {
        $entetes =
                [
                "Numéro",
                "État",
                "Titre",
                "Thématique",
                "Courriel",
                "Prénom",
                "Nom",
                "Laboratoire",
                "Nb de versions",
                "Dernière session",
                "Heures demandées cumulées",
                "Heures attribuées cumulées",
                ];

    $sortie     =   "Projets enregistrés dans gramc à la date du " . GramcDate::get()->format('d-m-Y') . "\n" . join("\t",$entetes) . "\n";

    $projets = AppBundle::getRepository(Projet::class)->findBy( [],['idProjet' => 'DESC' ] );
    foreach ( $projets as $projet )
            {
            $version        =   $projet->getVersionDerniere();
            $responsable    =   $version->getResponsable();
            $info           =   AppBundle::getRepository(Version::class)->info($projet);

            $ligne  =
                [
                $projet->getIdProjet(),
                Etat::getLibelle( $projet->getEtatProjet() ),
                Functions::string_conversion($version->getPrjTitre() ),
                Functions::string_conversion($version->getPrjThematique() ),
                $responsable->getMail(),
                Functions::string_conversion($responsable->getPrenom() ),
                Functions::string_conversion($responsable->getNom() ),
                Functions::string_conversion($version->getPrjLLabo() ),
                $info[1],
                $version->getSession()->getIdSession(),
                $info[2],
                $info[3],
                ];
            $sortie     .=   join("\t",$ligne) . "\n";
            }

    return Functions::csv($sortie,'projet_gramc.csv');

    }

    /**
     * fermer un projet
     *
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/{id}/fermer", name="fermer_projet")
     * @Method({"GET","POST"})
     */
    public function fermerAction(Projet $projet, Request $request)
    {
        if( $request->isMethod('POST') )
            {
            $confirmation = $request->request->get('confirmation');

            if( $confirmation == 'OUI' )
                {
                $projetWorkflow = new ProjetWorkflow();
                if( $projetWorkflow->canExecute( Signal::CLK_FERM, $projet) )
                     $projetWorkflow->execute( Signal::CLK_FERM, $projet);
                }
            return $this->redirectToRoute('projet_tous'); // NON - on ne devrait jamais y arriver !
            }
        else
           return $this->render('projet/dialog_fermer.html.twig',
            [
            'projet' => $projet,
            ]);
    }

    /**
     * back une version
     *
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/{id}/back", name="back_version")
     * @Method({"GET","POST"})
     */
    public function backAction(Version $version, Request $request)
    {
        if( $request->isMethod('POST') )
            {
            $confirmation = $request->request->get('confirmation');

            if( $confirmation == 'OUI' )
                {
                //$versionWorkflow = new VersionWorkflow();
                //if( $versionWorkflow->canExecute( Signal::CLK_ARR, $version) )
                //     $versionWorkflow->execute( Signal::CLK_ARR, $version);
                $projetWorkflow = new ProjetWorkflow();
                if( $projetWorkflow->canExecute( Signal::CLK_ARR, $version->getProjet() ) )
                     $projetWorkflow->execute( Signal::CLK_ARR, $version->getProjet());
                }
            return $this->redirectToRoute('projet_session'); // NON - on ne devrait jamais y arriver !
            }
        else
           return $this->render('projet/dialog_back.html.twig',
            [
            'version' => $version,
            ]);
    }

    /**
     * fwd une version
     *
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/{id}/fwd", name="fwd_version")
     * @Method({"GET","POST"})
     */
    public function fwdAction(Version $version, Request $request)
    {
        if( $request->isMethod('POST') )
            {
            $confirmation = $request->request->get('confirmation');

            if( $confirmation == 'OUI' )
                {
                //$versionWorkflow = new VersionWorkflow();
                //if( $versionWorkflow->canExecute( Signal::CLK_VAL_DEM, $version) )
                //     $versionWorkflow->execute( Signal::CLK_VAL_DEM, $version);
                $projetWorkflow = new ProjetWorkflow();
                if( $projetWorkflow->canExecute( Signal::CLK_VAL_DEM, $version->getProjet() ) )
                     $projetWorkflow->execute( Signal::CLK_VAL_DEM, $version->getProjet());

                // TODO il faut ajouter des notifications !!!!
                }
            return $this->redirectToRoute('projet_session'); // NON - on ne devrait jamais y arriver !
            }
        else
           return $this->render('projet/dialog_fwd.html.twig',
            [
            'version' => $version,
            ]);
    }

    /**
     * Liste tous les projets qui ont une version lors de cette session
     *
     * @Route("/session", name="projet_session")
     * @Method({"GET","POST"})
     */
    public function sessionAction(Request $request)
    {
        $data       =   Functions::selectSession($request); // formulaire
        $versions   =   AppBundle::getRepository(Version::class)->findSessionVersions($data['session']);

        $demHeures      =   0;
        $attrHeures     =   0;
        $nombreProjets  =   count( $versions );
        $nombreNouveaux =   0;

        $nombreSignes   =   0;
        $nombreRapports =   0;
        $nombreExperts  =   0;
        $nombreAcceptes =   0;

        $nombreEditionTest      =   0;
        $nombreExpertiseTest    =   0;
        $nombreEditionFil       =   0;
        $nombreExpertiseFil     =   0;
        $nombreEdition          =   0;
        $nombreExpertise        =   0;
        $nombreAttente          =   0;
        $nombreActif            =   0;
        $nombreNouvelleDem      =   0;
        $nombreTermine          =   0;
        $nombreAnnule           =   0;


        $termine        =   Etat::getEtat('TERMINE');
        $nombreTermines =   0;

        $thematiques = AppBundle::getRepository(Thematique::class)->findAll();
        if( $thematiques == null ) new Response('Aucune thématique !');

        foreach( $thematiques as $thematique )
        {
            $statsThematique[$thematique->getLibelleThematique()]    =   0;
            $idThematiques[$thematique->getLibelleThematique()]      =   $thematique->getIdThematique();
        }

        $items  =   [];
        foreach( $versions as $version )
        {
            $demHeures  +=  $version->getDemHeures();
            $attrHeures +=  $version->getAttrHeures();
            if( $version->isNouvelle() == true )    $nombreNouveaux++;
            if( $version->getPrjThematique() != null )
                $statsThematique[$version->getPrjThematique()->getLibelleThematique()]++;
            if( $version->isSigne() )       $nombreSignes++;
            if( $version->hasRapport() )    $nombreRapports++;
            if( $version->hasExpert() )     $nombreExperts++;
            //if( $version->getAttrAccept() ) $nombreAcceptes++;
            if( $version->getProjet()->getMetaEtat() == 'ACCEPTE' )
                $nombreAcceptes++;
            if( $version->getProjet() != null && $version->getProjet()->getEtatProjet() == $termine ) $nombreTermines++;

            $etat = $version->getEtatVersion();
            $type = $version->getProjet()->getTypeProjet();


			// TODO Que c'est compliqué !
			//      Plusieurs workflows => pas d'états différents
			//      Utiliser un tableau
			if ($type == Projet::PROJET_TEST)
			{
				if ($etat == Etat::EDITION_TEST )
				{
					$nombreEditionTest++;
				}
				elseif ( $etat == Etat::EXPERTISE_TEST )
				{
					$nombreExpertiseTest++;
				}
			}
			elseif ($type == Projet::PROJET_FIL)
			{
				if ($etat == Etat::EDITION_TEST )
				{
					$nombreEditionFil++;
				}
				elseif ( $etat == Etat::EXPERTISE_TEST )
				{
					$nombreExpertiseFil++;
				}
			}
			elseif ($type == Projet::PROJET_SESS)
			{
				if ($etat == Etat::EDITION_DEMANDE )
				{
					$nombreEdition++;
				}
				elseif ( $etat == Etat::EDITION_EXPERTISE )
				{
					$nombreExpertise++;
				}
			};

			if ($etat == Etat::ACTIF )
			{
				$nombreActif++;
			}
			elseif ( $etat == Etat::NOUVELLE_VERSION_DEMANDEE )
			{
				$nombreNouvelleDem++;
			}
			elseif ( $etat == Etat::EN_ATTENTE )
			{
				$nombreAttente++;
			}
			elseif ( $etat == Etat::TERMINE )
			{
				$nombreTermine++;
			}
			elseif ( $etat == Etat::ANNULE )
			{
				$nombreAnnule++;
			};

            $items[]    =
                    [
                    'version'       =>  $version,
                    'sizeSigne'     =>  $version->getSizeSigne(),
                    //'sizeRapport'   =>  $version->getSizeRapport(),//
                    ];
        }

        foreach( $thematiques as $thematique )
        {
            if( $statsThematique[$thematique->getLibelleThematique()]    ==   0 )
            {
                unset( $statsThematique[$thematique->getLibelleThematique()] );
                unset( $idThematiques[$thematique->getLibelleThematique()] );
            }
        }

        return $this->render('projet/session.html.twig',
        [
            'nombreEditionTest'     =>  $nombreEditionTest,
            'nombreExpertiseTest'   =>  $nombreExpertiseTest,
            'nombreEdition'         =>  $nombreEdition,
            'nombreExpertise'       =>  $nombreExpertise,
            'nombreAttente'         =>  $nombreAttente,
            'nombreActif'           =>  $nombreActif,
            'nombreNouvelleDem'     =>  $nombreNouvelleDem,
            'nombreTermine'         =>  $nombreTermine,
            'nombreAnnule'          =>  $nombreAnnule,
            'nombreEditionFil'      =>  $nombreEditionFil,
            'nombreExpertiseFil'    =>  $nombreExpertiseFil,
            'form' => $data['form']->createView(), // formulaire
            'idSession' => $data['session']->getIdSession(), // formulaire
            'session'   => $data['session'],
            'versions'  => $versions,
            'nombreNouveaux'    =>  $nombreNouveaux,
            'demHeures'         =>  $demHeures,
            'attrHeures'        =>  $attrHeures,
            'nombreProjets'     =>  $nombreProjets,
            'nombreNouveaux'    =>  $nombreNouveaux,
            'thematiques'       =>  $statsThematique,
            'idThematiques'     =>  $idThematiques,
            'nombreSignes'      =>  $nombreSignes,
            'nombreRapports'    =>  $nombreRapports,
            'nombreExperts'     =>  $nombreExperts,
            'nombreAcceptes'    =>  $nombreAcceptes,
            'nombreTermines'    =>  $nombreTermines,
            'showRapport'       => (substr( $data['session']->getIdSession(), 2, 1 ) == 'A')? true : false,
        ]);
    }

    /**
     * Résumés de tous les projets qui ont une version cette annee
     *
     * Param : $annee
     *
     * @Route("/{annee}/resumes", name="projet_resumes")
     * @Method({"GET","POST"})
     *
     */
    public function resumesAction($annee)
    {
        $paa   = Functions::projetsParAnnee($annee);
        $prjs  = $paa[0];
        $total = $paa[1];

        // construire une structure de données:
        //     - tableau associatif indexé par la métathématique
        //     - Pour chaque méta thématique liste des projets correspondants
        //       On utilise version B si elle existe, version A sinon
        //       On garde titre, les deux dernières publications, résumé
        $projets = [];
        foreach ($prjs as $p)
        {
            $v = empty($p['vb']) ? $p['va'] : $p['vb'];
            $metathema = $v->getPrjThematique()->getMetaThematique()->getLibelle();
            if (! isset($projets[$metathema])) {
                $projets[$metathema] = [];
            }
            $prjm = &$projets[$metathema];
            $prj  = [];
            $prj['id'] = $v->getProjet()->getIdProjet();
            $prj['titre'] = $v->getPrjTitre();
            $prj['resume']= $v->getPrjResume();
            $prj['laboratoire'] = $v->getLabo();
            $a = $v->getProjet()->getIdProjet();
            $a = substr($a,1,2);
            $a = 2000 + $a;
            $prj['annee'] = $a;
            $publis = array_slice($v->getProjet()->getPubli()->toArray(),-2,2);
            //$publis = array_slice($publis, -2, 2); // On garde seulement les deux dernières
            $prj['publis'] = $publis;
            $prj['porteur'] = $v->getResponsable()->getPrenom().' '.$v->getResponsable()->getNom();
            $prjm[] = $prj;
        };

        // Tris des tableaux par thématique du plus récent au plus ancien
        foreach ($projets as $metathema => &$prjm)
        {
            usort($prjm,"AppBundle\Controller\cmpProj");
        }

        return $this->render('projet/resumes.html.twig',
                [
                'annee'     => $annee,
                'projets'   => $projets,
                ]);
    }

    /**
     *
     * Liste tous les projets qui ont une version cette annee
     *
     * @Route("/annee", name="projet_annee")
     * @Method({"GET","POST"})
     */

    public function anneeAction(Request $request)
    {
        $data  =   Functions::selectAnnee($request); // formulaire
        $annee = $data['annee'];

        // $mois est utilisé pour calculer les éventuelles pénalités d'été
        // Si on n'est pas à l'année courante, on le met à 0 donc elles ne seront jamais calculées
        $annee_courante=GramcDate::get()->showYear();
        if ($annee == $annee_courante)
        {
            $mois  = GramcDate::get()->showMonth();
        } else {
            $mois = -1;
        }
        $isRecupPrintemps = GramcDate::isRecupPrintemps($annee);
        $isRecupAutomne   = GramcDate::isRecupAutomne($annee);

        $paa = Functions::projetsParAnnee($annee,$isRecupPrintemps, $isRecupAutomne);
        $projets = $paa[0];
        $total   = $paa[1];

        // Les sessions de l'année - On considère que le nombre d'heures par année est fixé par la session A de l'année
        // donc on ne peut pas changer de machine en cours d'année.
        // ça va peut-être changer un jour, ça n'est pas terrible !
        $sessions = Functions::sessionsParAnnee($annee);
        if (count($sessions)==0) {
            $hparannee=0;
        } else {
            $hparannee= $sessions[0]->getHParAnnee();
        }

        return $this->render('projet/annee.html.twig',
                [
                'form' => $data['form']->createView(), // formulaire
                'annee'     => $annee,
                //'mois'    => $mois,
                'projets'   => $projets,
                'total'     => $total,
                'showRapport' => false,
                'isRecupPrintemps' => $isRecupPrintemps,
                'isRecupAutomne'   => $isRecupAutomne,
                'heures_par_an'    => $hparannee
                ]);
    }

    /**
     * Projets de l'année en CSV
     *
     * @Route("/{annee}/annee_csv", name="projet_annee_csv")
     * @Method({"GET","POST"})
     */
    public function anneeCSVAction($annee)
    {
        $paa = Functions::projetsParAnnee($annee);
        $projets = $paa[0];
        $total   = $paa[1];

        $sortie = "Projets de l'année " . $annee . "\n";

        $header  = [
                    'projet',
                    'titre',
                    'thématique',
                    'courriel du resp',
                    'prénom',
                    'nom',
                    'laboratoire',
                    'heures demandées A',
                    'heures attribuées A',
                    'heures demandées B',
                    'heures attribuées B',
                    'rallonges',
                    'pénalités A',
                    'pénalités B',
                    'heures attribuées',
                    'quota machine',
                    'heures consommées'
                    ];

        $sortie     .=   join("\t",$header) . "\n";
        foreach ($projets as $prj_array) {
            $p = $prj_array['p'];
            $va= $prj_array['va'];
            $vb= $prj_array['vb'];
            $line = [];
            $line[] = $p->getIdProjet();
            $line[] = $p->getTitre();
            $line[] = $p->getThematique();
            $line[] = $p->getResponsable()->getMail();
            $line[] = $p->getResponsable()->getNom();
            $line[] = $p->getResponsable()->getPrenom();
            $line[] = $p->getLaboratoire();
            if (!empty($va)) {
                $line[] = $va->getDemHeures();
                $line[] = $va->getAttrHeures();
            } else {
                $line[] = '';
                $line[] = '';
            }
            if (!empty($vb)) {
                $line[] = $vb->getDemHeures();
                $line[] = $vb->getAttrHeures();
            } else {
                $line[] = '';
                $line[] = '';
            }
            $line[] = $prj_array['r'];
            $line[] = $prj_array['penal_a'];
            $line[] = $prj_array['penal_b'];
            $line[] = $prj_array['attrib'];
            $line[] = $prj_array['q'];
            $line[] = $prj_array['c'];

            $sortie     .=   join("\t",$line) . "\n";
        }
        return Functions::csv($sortie,'projets_'.$annee.'.csv');
    }

    /**
     * download rapport
     * @Security("has_role('ROLE_DEMANDEUR') or has_role('ROLE_OBS')")
     * @Route("/{id}/rapport/{annee}", defaults={"annee"=0}, name="rapport")
     * @Method("GET")
     */
    public function rapportAction(Version $version, Request $request, $annee )
    {
        if( ! Functions::projetACL( $version->getProjet() ) )
            Functions::createException(__METHOD__ . ':' . __LINE__ .' problème avec ACL');

        if ($annee == 0 )
            $filename = $version->getRapport();
        else
            $filename = $version->getRapport( $annee );

        //return new Response($filename);

        if(  file_exists( $filename ) )
            {
            return Functions::pdf( file_get_contents ($filename ) );
            }
        else
            {
            Functions::errorMessage(__METHOD__ . ":" . __LINE__ . " fichier du rapport d'activité \"" . $filename . "\" n'existe pas");
            return Functions::pdf( null );
            }
    }

    /**
     * download signature
     *
     * @Route("/{id}/signature", name="signature")
     * @Security("has_role('ROLE_OBS')")
     * @Method("GET")
     */
    public function signatureAction(Version $version, Request $request)
    {
    return Functions::pdf( $version->getSigne() );
    }

    /**
     * Lists all projet entities.
     *
     * @Route("/tous", name="projet_tous")
     * @Method("GET")
     */
    public function tousAction()
    {
        $projets = AppBundle::getRepository(Projet::class)->findAll();

        $etat_projet['expertise']   =   0;
        $etat_projet['accepte']     =   0;
        $etat_projet['refuse']      =   0;
        $etat_projet['edition']     =   0;

        $data = [];

        $collaborateurVersionRepository     =  AppBundle::getRepository(CollaborateurVersion::class);
        $versionRepository                  =  AppBundle::getRepository(Version::class);
        $projetRepository                   =  AppBundle::getRepository(Projet::class);

        foreach ( $projets as $projet )
        {

            $info       =   $versionRepository->info($projet); // les stats du projet
            $version    =   $versionRepository->findVersionDerniere($projet);
            $etat       =   Etat::getLibelle( $projet->getEtatProjet() );

            $data[] = [
                    'projet'        => $projet,
                    'version'       =>  $version,
                    'etat'          =>  $etat,
                    'etat_version'  => ($version != null ) ? Etat::getLibelle( $version->getEtatVersion() ): 'SANS_VERSION',
                    'count'         =>  $info[1],
                    'dem'           =>  $info[2],
                    'attr'          =>  $info[3],
                    'responsable'   =>  $collaborateurVersionRepository->getResponsable($projet),
                     ];

        }

        $etat_projet['en_attente']                  =   $projetRepository->countEtat('EN_ATTENTE');
        $etat_projet['actif']                       =   $projetRepository->countEtat('ACTIF');
        $etat_projet['en_standby']                  =   $projetRepository->countEtat('EN_STANDBY');
        $etat_projet['en_sursis']                   =   $projetRepository->countEtat('EN_SURSIS');
        $etat_projet['nouvelle_version_demandee']   =   $projetRepository->countEtat('NOUVELLE_VERSION_DEMANDEE');
        $etat_projet['termine']                     =   $projetRepository->countEtat('TERMINE');
        $etat_projet['annule']                      =   $projetRepository->countEtat('ANNULE');
        $etat_projet['total']                       =   $projetRepository->countAll();

        $etat_projet['total_test']      =   $projetRepository->countAllTest();
        $etat_projet['actif_test']      =   $projetRepository->countEtatTest('ACTIF_TEST');
        $etat_projet['edition_test']    =   $projetRepository->countEtatTest('EDITION_TEST');
        $etat_projet['expertise_test']  =   $projetRepository->countEtatTest('EXPERTISE_TEST');
        $etat_projet['en_attente_test'] =   $projetRepository->countEtatTest('EN_ATTENTE');
        $etat_projet['termine_test']    =   $projetRepository->countEtatTest('TERMINE');
        $etat_projet['annule_test']     =   $projetRepository->countEtatTest('ANNULE');

        return $this->render('projet/projets_tous.html.twig',
        [
            'etat_projet'   =>  $etat_projet,
            'data' => $data,
        ]);
    }

    /**
     * Lists all projet entities.
     *
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/gerer", name="gerer_projets")
     * @Method("GET")
     */
    public function gererAction()
    {
        $projets = AppBundle::getRepository(Projet::class)->findAll();

        return $this->render('projet/gerer.html.twig', array(
            'projets' => $projets,
        ));
    }

    /**
     * Creates a new projet entity.
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/new", name="projet_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $projet = new Projet(Projet::PROJET_SESS);
        $form = $this->createForm('AppBundle\Form\ProjetType', $projet);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($projet);
            $em->flush($projet);

            return $this->redirectToRoute('projet_show', array('id' => $projet->getId()));
        }

        return $this->render('projet/new.html.twig', array(
            'projet' => $projet,
            'form' => $form->createView(),
        ));
    }

    /**
     * Creates a new projet entity.
     *
     * @Route("/avant_nouveau/{type}", name="avant_nouveau_projet")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_DEMANDEUR')")
     *
     */
    public function avantNouveauAction(Request $request,$type)
    {
        if( Menu::nouveau_projet($type)['ok'] == false )
            Functions::createException(__METHOD__ . ":" . __LINE__ . " impossible de créer un nouveau projet parce que " . Menu::nouveau_projet($type)['raison'] );

        $renouvelables = AppBundle::getRepository(Projet::class)->get_projets_renouvelables();

        if( $renouvelables == null )   return  $this->redirectToRoute('nouveau_projet', ['type' => $type]);

        return $this->render('projet/avant_nouveau_projet.html.twig',
            [
            'renouvelables' => $renouvelables,
            'type'          => $type
            ]
            );

    }

    /**
     * Creates a new projet entity.
     *
     * @Route("/nouveau/{type}", name="nouveau_projet")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_DEMANDEUR')")
     *
     */
    public function nouveauAction(Request $request, $type)
    {
		// Si changement d'état de la session alors que je suis connecté !
		// + contournement d'un problème lié à Doctrine
        AppBundle::getSession()->remove('SessionCourante'); // remove cache

        // NOTE - Pour ce controlleur, on identifie les types par un chiffre (voir Entity/Projet.php)
        $m = Menu::nouveau_projet("$type");
        if ($m == null || $m['ok']==false)
        {
			$raison = $m===null?"ERREUR AVEC LE TYPE $type - voir le paramètre prj_type":$m['raison'];
            Functions::createException(__METHOD__ . ":" . __LINE__ . " impossible de créer un nouveau projet parce que $raison");
         }

        $session    = Functions::getSessionCourante();

		$prefixes = AppBundle::getParameter('prj_prefix');
		if ( !isset ($prefixes[$type]) || $prefixes[$type]==="" )
	    {
			Functions::errorMessage(__METHOD__ . ':' . __LINE__ . " Pas de préfixe pour le type $type. Voir le paramètre prj_prefix");
			return $this->redirectToRoute('accueil');
		}

        $projet   = new Projet($type);
        switch ($type)
        {
			case Projet::PROJET_SESS:
			case Projet::PROJET_FIL:
	            $projet->setEtatProjet(Etat::RENOUVELABLE);
	            break;
	        case Projet::PROJET_TEST:
	            $projet->setEtatProjet(Etat::NON_RENOUVELABLE);
	            break;
	        default:
	           Functions::createException(__METHOD__ . ":" . __LINE__ . " mauvais type de projet " . Functions::show( $type) );
		}

        $version    =   new Version();
        //return new Response( Functions::show( $version ) );
        $version->setIdVersion( $session->getIdSession() . $projet->getIdProjet() );
        $version->setProjet( $projet );
        $version->setSession( $session );
        $version->setLaboResponsable();

        if( $type == Projet::PROJET_SESS )
            $version->setEtatVersion(Etat::EDITION_DEMANDE);
        else
            $version->setEtatVersion(Etat::EDITION_TEST);

        //return new Response( Functions::show( $version ) );
        $moi    =   AppBundle::getUser();
        $collaborateurVersion   =   new CollaborateurVersion( $moi );
        $collaborateurVersion->setVersion( $version );
        $collaborateurVersion->setResponsable( true );

        $em         = AppBundle::getManager();
        $em->persist( $projet );
        $em->persist( $version );
        $em->persist( $collaborateurVersion );
        $em->flush();

        if( $version instanceof Version )
            $projet->setVersionDerniere( $version );
        else
            return new Response( Functions::show( $version ) );

        return $this->redirectToRoute('modifier_version',[ 'id' => $version->getIdVersion() ] );

    }

    /**
     * Affichage graphique de la consommation d'un projet
     *
     * @Route("/{id}/conso/{annee}", name="projet_conso")
     * @Method("GET")
     * @Security("has_role('ROLE_DEMANDEUR')")
     */

    public function consoAction(Projet $projet, $annee = null)
    {
		$projet_id = strtolower($projet->getIdProjet());

        // Seuls les collaborateurs du projet ont accès à la consommation
        if( ! Functions::projetACL( $projet ) )
                Functions::createException(__METHOD__ . ':' . __LINE__ .' problème avec ACL');

        // Si année non spécifiée on prend l'année la plus récente du projet
        if( $annee == null )
        {
            $version    =   $projet->derniereVersion();
            $annee = '20' . substr( $version->getIdVersion(), 0, 2 );
        }

		$conso_repo = AppBundle::getRepository(Compta::class);
        $debut = new \DateTime( $annee . '-01-01');
        $fin   = new \DateTime( $annee . '-12-31');

		// Génération du graphe de conso heures cpu et heures gpu
        $db_conso = $conso_repo->conso( $projet, $annee );
		//foreach ($db_conso as $item) {
		//	$msg = print_r($item,true);
		//	$this->get('logger')->warning($msg);
		//}

		$dessin_heures = new Calcul();
		$struct_data     = $dessin_heures->createStructuredData($debut,$fin,$db_conso);
		$dessin_heures->resetConso($struct_data);
        $image_conso     = $dessin_heures->createImage($struct_data)[0];

		$db_work    = $conso_repo->consoResPrj( $projet, 'work_space', $annee );
        $dessin_work = new Stockage();
        $struct_data = $dessin_work->createStructuredData($debut,$fin,$db_work);
        $image_work  = $dessin_work->createImage($struct_data)[0];

        $twig     = new \Twig_Environment( new \Twig_Loader_String(), array( 'strict_variables' => false ) );
        $twig_src = '<img src="data:image/png;base64, {{ image_conso }}" alt="Heures cpu/gpu" title="Heures cpu et gpu" /><hr /><img src="data:image/png;base64, {{ image_work }}" />';
        $html = $twig->render( $twig_src,  [ 'image_conso' => $image_conso,'image_work' => $image_work ] );

		return new Response($html);
    }


    /**
     * Affichage graphique de la consommation de TOUS les projets
     *
     * @Route("/{ressource/{ressource}/tousconso/{annee}", name="tous_projets_conso")
     * @Method("GET")
     * @Security("has_role('ROLE_ADMIN')")
     */

    public function consoTousAction($ressource,$annee)
    {

		if ( $ressource != 'cpu' && $ressource != 'gpu' )
		{
			return "";
		}

        $db_conso = AppBundle::getRepository(Compta::class)->consoTotale( $annee, $ressource );

		//foreach ($db_conso as $item) {
		//	$msg = print_r($item,true);
		//	$this->get('logger')->warning($msg);
		//}
		$debut = new \DateTime( $annee . '-01-01');
		$fin   = new \DateTime( $annee . '-12-31');

        $dessin_heures = new CalculTous();
        $struct_data = $dessin_heures->createStructuredData($debut,$fin,$db_conso);
        $image_conso     = $dessin_heures->createImage($struct_data)[0];

        $twig = new \Twig_Environment( new \Twig_Loader_String(), array( 'strict_variables' => false ) );
        $html = $twig->render( '<img src="data:image/png;base64, {{ ImageConso }}" alt="Heures cpu/gpu" title="Heures cpu et gpu" />' ,  [ 'ImageConso' => $image_conso ] );
		return new Response($html);
    }

    /**
     * Montre projets d'un utilisateur
     *
     * @Route("/accueil", name="projet_accueil")
     * @Route("/accueil/", name="projet_accueil1")
     * @Method("GET")
     * @Security("has_role('ROLE_DEMANDEUR')")
     */
    public function accueilAction()
    {
	    $individu       =   AppBundle::getUser();
	    $id_individu    =   $individu->getIdIndividu();

	    $projetRepository       =   AppBundle::getRepository(Projet::class);

	    $list_projets_collab =   $projetRepository-> get_projets_resp_ou_collab( $id_individu, false, true );
	    $list_projets_resp   =   $projetRepository-> get_projets_resp_ou_collab( $id_individu, true, false );

	    $projets_term       =   $projetRepository-> get_projets_etat( $id_individu, 'TERMINE' );
	    $projets_standby = []; // todo -> Virer définitivement ce code, les projets en standby sont maintenant affichés avec les projets actifs, pas avec les projets terminés

	    $session_actuelle       =   Functions::getSessionCourante();

	    // projets responsable
	    $projets_resp  = [];
	    foreach ( $list_projets_resp as $projet )
		{
	        $versionActive  =   $projet->versionActive();
	        if( $versionActive != null )
	            $rallonges  =  $versionActive ->getRallonge();
	        else
	            $rallonges  = null;
	        $projets_resp[]   =
	            [
	            'projet'    =>  $projet,
	            'conso'     =>  $projet->getConsoP(),
	            'rallonges' =>  $rallonges,
	            'cpt_rall'  =>  count($rallonges),
	            ];
		}

	    // projets collaborateur
	    $projets_collab  = [];
	    foreach ( $list_projets_collab as $projet )
		{
	        $versionActive  =   $projet->versionActive();
	        if( $versionActive != null )
	            $rallonges  =  $versionActive ->getRallonge();
	        else
	            $rallonges  = null;
	        $projets_collab[]   =
	            [
	            'projet'    =>  $projet,
	            'conso'     =>  $projet->getConsoP(),
	            'rallonges' =>  $rallonges,
	            'cpt_rall'  =>  count($rallonges),
	            ];
		}

		$prefixes = AppBundle::getParameter('prj_prefix');
		foreach (array_keys($prefixes) as $t)
		{
			$menu[] = Menu::nouveau_projet($t);
		}

	    return $this->render('projet/demandeur.html.twig',
	            [
	            'projets_collab'  => $projets_collab,
	            'projets_resp'    => $projets_resp,
	            'projets_term'    => $projets_term,
	            'projets_standby' => $projets_standby,
	            'menu'            =>  $menu,
	            ]
	            );
    }

    /**
     * envoyer à l'expert
     *
     * @Route("/{id}/avant_envoyer", name="avant_envoyer_expert")
     * @Method({"GET","POST"})
     * @Security("has_role('ROLE_DEMANDEUR')")
     */
    public function avantEnvoyerAction(Projet $projet,  Request $request)
    {
	    Functions::MenuACL( Menu::envoyer_expert($projet), "Impossible d'envoyer le projet " . $projet->getIdProjet() . " à l'expert", __METHOD__, __LINE__ );

	    $version    =    $projet->derniereVersion();
	    if( $version == null ) Functions::createException(__METHOD__ .":". __LINE__ ." Aucune version du projet " . $projet->getIdProjet());

	    $session = $version->getSession();

	    $form = AppBundle::createFormBuilder()
	            ->add('CGU',   CheckBoxType::class,
	                    [
	                    'required'  =>  false,
	                    'label'     => '',
	                    ])
	        ->add('envoyer', SubmitType::class, ['label' => "Envoyer à l'expert"])
	        ->add('annuler', SubmitType::class, ['label' => "Annuler"])
	        ->getForm();

	    $form->handleRequest($request);
	    if ( $form->isSubmitted() && $form->isValid() )
	    {
	        $CGU = $form->getData()['CGU'];
	        if( $form->get('annuler')->isClicked() )
	             return $this->redirectToRoute('consulter_projet',[ 'id' => $projet->getIdProjet() ] );

	        if( $CGU == false && $form->get('envoyer')->isClicked() )
	        {
	            //Functions::errorMessage(__METHOD__  .":". __LINE__ . " CGU pas acceptées ");
	            //return $this->redirectToRoute('consulter_projet',[ 'id' => $projet->getIdProjet() ] );
	            return $this->render('projet/avant_envoyer_expert.html.twig',
	                    [ 'projet' => $projet, 'form' => $form->createView(), 'session' => $session, 'cgu' => 'KO' ]
	                    );

	        }
	        elseif ( $CGU == true && $form->get('envoyer')->isClicked() )
	        {
	            $version->setCGU( true );
	            Functions::sauvegarder( $version );
	            return $this->redirectToRoute('envoyer_expert',[ 'id' => $projet->getIdProjet() ] );
	        }
	        else
	            Functions::createException(__METHOD__ .":". __LINE__ ." Problème avec le formulaire d'envoi à l'expert du projet " . $projet->getIdProjet());
	    }

	    return $this->render('projet/avant_envoyer_expert.html.twig',
	                        [ 'projet' => $projet, 'form' => $form->createView(), 'session' => $session, 'cgu' => 'OK' ]
	                        );

    }


    /**
     * envoyer à l'expert
     *
     * @Route("/{id}/envoyer", name="envoyer_expert")
     * @Method("GET")
     * @Security("has_role('ROLE_DEMANDEUR')")
     */
    public function envoyerAction(Projet $projet,  Request $request)
    {
	    //if( Menu::envoyer_expert($projet)['ok'] == false && (  AppBundle::hasParameter('kernel.debug') && AppBundle::getParameter('kernel.debug') == false ) )
	    //   Functions::createException(__METHOD__ ." Impossible d'envoyer le projet " . $projet->getIdProjet() . " à l'expert");

	    Functions::MenuACL( Menu::envoyer_expert($projet), " Impossible d'envoyer le projet " . $projet->getIdProjet() . " à l'expert", __METHOD__, __LINE__ );

	    $version    =    $projet->derniereVersion();
	    if( $version == null )
	        Functions::createException(__METHOD__ .":". __LINE__ ." Aucune version du projet " . $projet->getIdProjet());

	    if( Menu::envoyer_expert($projet)['incomplet'] == true )
	        Functions::createException(__METHOD__ .":". __LINE__ ." Projet " . $projet->getIdProjet() . " incomplet envoyé à l'expert !");

	    if( $version->getCGU() == false )
	        Functions::createException(__METHOD__ .":". __LINE__ ." Pas d'acceptation des CGU " . $projet->getIdProjet());

		// S'il y a déjà une expertise on ne fait rien
		// Sinon on la crée et on appelle le programme d'affectation automatique des experts
	    if( count( $version->getExpertise() ) > 0 )
        {
	        Functions::noticeMessage(__METHOD__ . ":" . __LINE__ . " Expertise de la version " . $version . " existe déjà");
        }
	    else
        {
	        $expertise  =   new Expertise();
	        $expertise->setVersion( $version );

			// Attention, l'algorithme de proposition des experts dépend du type de projet
            $expert = $projet->proposeExpert();
            if ($expert != null)
            {
				$expertise->setExpert( $expert );
			}
	        Functions::sauvegarder( $expertise );
        }

	    $projetWorkflow = new ProjetWorkflow();
	    $rtn = $projetWorkflow->execute( Signal::CLK_VAL_DEM, $projet );

	    //Functions::debugMessage(__METHOD__ .  ":" . __LINE__ . " Le projet " . $projet . " est dans l'état " . Etat::getLibelle( $projet->getObjectState() )
	    //    . "(" . $projet->getObjectState() . ")" );

	    if( $rtn == true )
	        return $this->render('projet/envoyer_expert.html.twig', [ 'projet' => $projet, 'session' => $version->getSession() ] );
	    else
        {
	        Functions::errorMessage(__METHOD__ .  ":" . __LINE__ . " Le projet " . $projet->getIdProjet() . " n'a pas pu etre envoyé à l'expert correctement");
	        return new Response("Le projet " . $projet->getIdProjet() . " n'a pas pu etre envoyé à l'expert correctement");
        }
    }

    /**
     * Affiche un projet avec un menu pour choisir la version
     *
     * @Route("/{id}/consulter", name="consulter_projet")
     * @Route("/{id}/consulter/{version}", name="consulter_version")
     * @Method({"GET","POST"})
     * @Security("has_role('ROLE_DEMANDEUR')")
     */
    public function consulterAction(Projet $projet, Version $version = null,  Request $request)
    {
        // choix de la version
        if( $version == null )
            $version =  $projet->derniereVersion();
        else
            $projet =   $version->getProjet(); // nous devons être sûrs que le projet corresponde à la version

         if( ! Functions::projetACL( $projet ) )
            Functions::createException(__METHOD__ . ':' . __LINE__ .' problème avec ACL');

       // AppBundle::getManager()->persist($version); // si on supprime cette ligne nous aurons un bogue dans le formulaire

		// LA SUITE DEPEND DU TYPE DE PROJET !
		$type = $projet->getTypeProjet();
		switch ($type)
		{
			case Projet::PROJET_SESS:
				return $this->consulterType1($projet, $version, $request);
			case Projet::PROJET_TEST:
				return $this->consulterType2($projet, $version, $request);
			case Projet::PROJET_FIL:
				return $this->consulterType3($projet, $version, $request);
			default:
				Functions::errorMessage(__METHOD__ . " Type de projet inconnu: $type");
		}
    }

	// Consulter les projets de type 1 (projets PROJET_SESS)
    private function consulterType1(Projet $projet, Version $version, Request $request)
    {
	    $session_form = AppBundle::createFormBuilder( ['version' => $version ] )
	        ->add('version',   EntityType::class,
	                [
	                'multiple' => false,
	                'class' => 'AppBundle:Version',
	                'required'  =>  true,
	                'label'     => '',
	                'choices' =>  $projet->getVersion(),
	                'choice_label' => function($version){ return $version->getSession(); }
	                ])
	    ->add('submit', SubmitType::class, ['label' => 'Changer'])
	    ->getForm();

	    $session_form->handleRequest($request);

	    if ( $session_form->isSubmitted() && $session_form->isValid() )
	    {
	        $version = $session_form->getData()['version'];
		}

	    if( $version != null )
	    {
	        $session = $version->getSession();
		}
	    else
	    {
	        Functions::createException(__METHOD__ . ':' . __LINE__ .' projet ' . $projet . ' sans version');
		}

		$menu = [];
	    if( AppBundle::isGranted('ROLE_ADMIN')  ) $menu[] = Menu::rallonge_creation( $projet );
	    $menu[] =   Menu::changer_responsable($version);
	    $menu[] =   Menu::renouveler_version($version);
	    $menu[] =   Menu::modifier_version( $version );
	    $menu[] =   Menu::envoyer_expert( $projet );
	    $menu[] =   Menu::modifier_collaborateurs( $version );
	    $menu[] =   Menu::telechargement_fiche( $version );
	    $menu[] =   Menu::televersement_fiche( $version );
	    $menu[] =   Menu::telecharger_modele_rapport_dactivite( $version );

	    $etat_version = $version->getEtatVersion();
	    if( ($etat_version == Etat::ACTIF || $etat_version == Etat::TERMINE ) && ! $version->hasRapport( $version->getAnneeSession() ) )
	        $menu[] =   Menu::televerser_rapport_annee( $version );

	    $menu[] =   Menu::gerer_publications( $projet );

	    $img_expose_1   =   Functions::image_parameters('img_expose_1', $version);
	    $img_expose_2   =   Functions::image_parameters('img_expose_2', $version);
	    $img_expose_3   =   Functions::image_parameters('img_expose_3', $version);

	    /*
	    if( $img_expose_1 == null )
	        Functions::debugMessage(__METHOD__.':'.__LINE__ ." img_expose1 null");
	    else
	        Functions::debugMessage(__METHOD__.':'.__LINE__ . " img_expose1 non null");
	    */

	    $img_justif_renou_1 =   Functions::image_parameters('img_justif_renou_1', $version);
	    $img_justif_renou_2 =   Functions::image_parameters('img_justif_renou_2', $version);
	    $img_justif_renou_3 =   Functions::image_parameters('img_justif_renou_3', $version);

	    $toomuch = false;
	    if ($session->getLibelleTypeSession()=='B' && ! $version->isNouvelle()) {
	        $version_prec = $version->versionPrecedente();
	        if ($version_prec->getAnneeSession() == $version_prec->getAnneeSession()) {
	            $toomuch = Functions::is_demande_toomuch($version_prec->getAttrHeures(),$version->getDemHeures());
	        }
	    }

	    return $this->render('projet/consulter_projet_sess.html.twig',
	            [
	            'projet' => $projet,
	            'version_form'   => $session_form->createView(),
	            'version'   =>  $version,
	            'session'   =>  $session,
	            'menu'      =>  $menu,
	            'img_expose_1'  =>  $img_expose_1,
	            'img_expose_2'  =>  $img_expose_2,
	            'img_expose_3'  =>  $img_expose_3,
	            'img_justif_renou_1'    =>  $img_justif_renou_1,
	            'img_justif_renou_2'    =>  $img_justif_renou_2,
	            'img_justif_renou_3'    =>  $img_justif_renou_3,
	            'toomuch'               => $toomuch
	            ]
	            );
	}

	// Consulter les projets de type 2 (projets test)
	private function consulterType2 (Projet $projet, Version $version, Request $request)
	{
        if( AppBundle::isGranted('ROLE_ADMIN')  ) $menu[] = Menu::rallonge_creation( $projet );
        $menu[] =   Menu::modifier_version( $version );
        $menu[] =   Menu::envoyer_expert( $projet );
        $menu[] =   Menu::modifier_collaborateurs( $version );

        return $this->render('projet/consulter_projet_test.html.twig',
            [
            'projet' => $projet,
            'version'   =>  $version,
            'menu'      =>  $menu,
            ]
            );
	}

	// Consulter les projets de type 3 (projets PROJET_FIL)
    private function consulterType3(Projet $projet, Version $version, Request $request)
    {
	    $session_form = AppBundle::createFormBuilder( ['version' => $version ] )
	        ->add('version',   EntityType::class,
	                [
	                'multiple' => false,
	                'class' => 'AppBundle:Version',
	                'required'  =>  true,
	                'label'     => '',
	                'choices' =>  $projet->getVersion(),
	                'choice_label' => function($version){ return $version->getSession(); }
	                ])
	    ->add('submit', SubmitType::class, ['label' => 'Changer'])
	    ->getForm();

	    $session_form->handleRequest($request);

	    if ( $session_form->isSubmitted() && $session_form->isValid() )
	        $version = $session_form->getData()['version'];

	    if( $version != null )
	        $session = $version->getSession();
	    else
	        Functions::createException(__METHOD__ . ':' . __LINE__ .' projet ' . $projet . ' sans version');

	    if( AppBundle::isGranted('ROLE_ADMIN')  ) $menu[] = Menu::rallonge_creation( $projet );
	    $menu[] =   Menu::changer_responsable($version);
	    $menu[] =   Menu::renouveler_version($version);
	    $menu[] =   Menu::modifier_version( $version );
	    $menu[] =   Menu::envoyer_expert( $projet );
	    $menu[] =   Menu::modifier_collaborateurs( $version );
	    $menu[] =   Menu::telechargement_fiche( $version );
	    $menu[] =   Menu::televersement_fiche( $version );
	    $menu[] =   Menu::telecharger_modele_rapport_dactivite( $version );

	    $etat_version = $version->getEtatVersion();
	    if( ($etat_version == Etat::ACTIF || $etat_version == Etat::TERMINE ) && ! $version->hasRapport( $version->getAnneeSession() ) )
	        $menu[] =   Menu::televerser_rapport_annee( $version );

	    $menu[] =   Menu::gerer_publications( $projet );

	    $img_expose_1   =   Functions::image_parameters('img_expose_1', $version);
	    $img_expose_2   =   Functions::image_parameters('img_expose_2', $version);
	    $img_expose_3   =   Functions::image_parameters('img_expose_3', $version);

	    /*
	    if( $img_expose_1 == null )
	        Functions::debugMessage(__METHOD__.':'.__LINE__ ." img_expose1 null");
	    else
	        Functions::debugMessage(__METHOD__.':'.__LINE__ . " img_expose1 non null");
	    */

	    $img_justif_renou_1 =   Functions::image_parameters('img_justif_renou_1', $version);
	    $img_justif_renou_2 =   Functions::image_parameters('img_justif_renou_2', $version);
	    $img_justif_renou_3 =   Functions::image_parameters('img_justif_renou_3', $version);

	    $toomuch = false;
	    if ($session->getLibelleTypeSession()=='B' && ! $version->isNouvelle()) {
	        $version_prec = $version->versionPrecedente();
	        if ($version_prec->getAnneeSession() == $version_prec->getAnneeSession()) {
	            $toomuch = Functions::is_demande_toomuch($version_prec->getAttrHeures(),$version->getDemHeures());
	        }
	    }

	    return $this->render('projet/consulter_projet_fil.html.twig',
	            [
	            'projet' => $projet,
	            'version_form'   => $session_form->createView(),
	            'version'   =>  $version,
	            'session'   =>  $session,
	            'menu'      =>  $menu,
	            'img_expose_1'  =>  $img_expose_1,
	            'img_expose_2'  =>  $img_expose_2,
	            'img_expose_3'  =>  $img_expose_3,
	            'img_justif_renou_1'    =>  $img_justif_renou_1,
	            'img_justif_renou_2'    =>  $img_justif_renou_2,
	            'img_justif_renou_3'    =>  $img_justif_renou_3,
	            'toomuch'               => $toomuch
	            ]
	            );
	}

    /**
     * Finds and displays a projet entity.
     *
     * @Route("/modele", name="telecharger_modele")
     * @Method("GET")
     * @Security("has_role('ROLE_DEMANDEUR')")
     */
    public function telechargerModeleAction()
    {
    return $this->render('projet/telecharger_modele.html.twig');
    }

    /**
     * Finds and displays a projet entity.
     *
     * @Route("/{id}", name="projet_show")
     * @Route("/{id}/show", name="consulter_show_projet")
     * @Method("GET")
     */
    public function showAction(Projet $projet)
    {
        $deleteForm = $this->createDeleteForm($projet);

        return $this->render('projet/show.html.twig', array(
            'projet' => $projet,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing projet entity.
     *
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/{id}/edit", name="projet_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Projet $projet)
    {
        $deleteForm = $this->createDeleteForm($projet);
        $editForm = $this->createForm('AppBundle\Form\ProjetType', $projet);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('projet_edit', array('id' => $projet->getId()));
        }

        return $this->render('projet/edit.html.twig', array(
            'projet' => $projet,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a projet entity.
     *
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/{id}", name="projet_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Projet $projet)
    {
        $form = $this->createDeleteForm($projet);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($projet);
            $em->flush($projet);
        }

        return $this->redirectToRoute('projet_index');
    }

    /**
     * Creates a form to delete a projet entity.
     *
     * @param Projet $projet The projet entity
     * @Security("has_role('ROLE_ADMIN')")
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Projet $projet)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('projet_delete', array('id' => $projet->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
