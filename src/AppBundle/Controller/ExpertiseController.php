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

use AppBundle\Form\ChoiceList\ExpertChoiceLoader;

/**
 * Expertise controller.
 *
 * @Route("expertise")
 * @Security("has_role('ROLE_PRESIDENT')")
 */
class ExpertiseController extends Controller
{


    //////////////////////////////////////////////////////////////////////////
    //
    // préparation du formulaire du choix d'expert
    //

    private function getExpertForm(Projet $projet, Session $session)
    {

    $expert = $projet->getExpert($session);
    $collaborateurs = AppBundle::getRepository(CollaborateurVersion::class)->getCollaborateurs($projet);

    if( $expert ==  null )
        {
        $expert  =  $projet->proposeExpert( $collaborateurs );
        Functions::debugMessage(__METHOD__ ." nouvel expert proposé du projet " . $projet . " : " . Functions::show( $expert ) );
        }

    // Functions::debugMessage(__METHOD__ );
    // Functions::debugMessage(__METHOD__ ." expert proposé du projet " . $projet . " : " . Functions::show( $expert ) );
    //Functions::debugMessage(__METHOD__ ." collaborateurs du projet " . $projet . " : ". Functions::show( $collaborateurs ) );
    //Functions::debugMessage( __METHOD__ . " choices  du projet " . $projet . " : ". Functions::show( $choices ) );



    return $this->get( 'form.factory')->createNamedBuilder(   'expert'.$projet->getIdProjet() , FormType::class, null  ,  ['csrf_protection' => false ])
                ->add('expert', ChoiceType::class,
                    [
                'multiple'  =>  false,
                'required'  =>  false,
                //'choices'       => $choices, // cela ne marche pas à cause d'un bogue de symfony
                'choice_loader' => new ExpertChoiceLoader($collaborateurs), // nécessaire pour contourner le bogue de symfony
                'data'          => $expert,
                //'choice_value' => function (Individu $entity = null) { return $entity->getIdIndividu(); },
                'choice_label' => function ($individu)
                   { return $individu->__toString(); },
                    ])
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
	                Functions::warningMessage( __METHOD__ . ':' . __LINE__ . " Le mauvais expert " . $expert . " supprimé de la thématique " . $thematique);
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
        foreach( $versions as $version )
		{
            $etatVersion    =   $version->getEtatVersion();
            if( $etatVersion == Etat::EDITION_DEMANDE || $etatVersion == Etat::ANNULE ) continue; // on n'affiche pas de version en cet état


            $expert = $version->getExpert();
            $projet = $version->getProjet();
            //if( $expert ==  null )
                //$expert  =  $projet->proposeExpert( $projet->getCollaborateurs());
              //  $expert  =  $projet->proposeExpert();

            if(  $etatVersion == Etat::EDITION_EXPERTISE || $etatVersion == Etat::EXPERTISE_TEST)
                {
                //Functions::debugMessage( __METHOD__ . " form  du projet " . $projet );

                //$form  = $this->getExpertForm($version1->getProjet(),$session);
                $form  = $this->getExpertForm($version->getProjet(), $session);

                /*
                $form = AppBundle::getFormBuilder( 'expert_'.$projet->getIdProjet() , FormType::class,  ['csrf_protection' => false ])
                ->add('expert', ChoiceType::class,
                    [
                'multiple'  =>  false,
                'required'  =>  false,
                'choices'       => [],
                'data'          => null,
                'choice_label' => function ($individu)
                   { return $individu->__toString(); },
                    ])->getForm(); */


                // traitement du formulaire d'affectation

                $form->handleRequest($request);
                if( $form->isSubmitted()   && $form->isValid()  )
                    {
                    $expert     =   $form->getData()['expert'];
                    //Functions::debugMessage(__METHOD__ . " version " . $version . " a soumis l'expert " . $expert);
                    $expertise  =   $projet->getOneExpertise($session);
                    if( $expertise == null )
                        {
                        $expertise = new Expertise();
                        $expertise->setVersion( $version );
                        Functions::infoMessage(__METHOD__ . " version ". $version . " n'a pas d'expertise !");
                        }

                $expertise->setExpert( $expert );
                Functions::sauvegarder( $expertise );
                $form  = $this->getExpertForm($version->getProjet(),$session);

                    }
                $forms[$version->getIdVersion()] = $form->createView();
                }

            if( $expert  == null )
                $expertId[$version->getIdVersion()]    =   '';
            else
                {
                $expertId[$version->getIdVersion()]    =  $expert->getIdIndividu();
                if( isset( $experts[ $expert->getIdIndividu()] ) ) // on peut avoir des anciens experts
                    $experts[ $expert->getIdIndividu()]['projets']++;
                else
                    $experts[ $expert->getIdIndividu() ] = ['expert' => $expert, 'projets' => 1 ];
                }

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
                $attHeures[$version->getIdVersion()]    = 0;

            }
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
            'expertId'  =>  $expertId,
            'session'   => $session,
            'thematiques'   =>  $thematiques,
            'experts'   =>  $experts,
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
     * Lists all expertise entities.
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
            // On n'affiche pas les expertises définitives
            if ( $expertise->getDefinitif()) continue;

            $version    =   $expertise->getVersion();

            $projetId   =   $version->getProjet()->getIdProjet();
            $thematique =   $version->getPrjThematique();

            $my_expertises[ $expertise->getId() ]   =   [
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

        ////////////////

        $expertises_by_thematique   =   [];
        foreach( $mes_thematiques as $thematique )
        {
            $expertises_thematique =  $expertiseRepository->findExpertisesByThematique($thematique, $session);
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

                if( array_key_exists( $expertise->getId() , $my_expertises ) )
                {
                    unset( $my_expertises[ $expertise->getId() ]);
                    $output['expert']   =   true;
                }
                else
                    $output['expert']   =   false;

                $expertises[]   =   $output;
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
     * Displays a form to edit an existing expertise entity.
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

        // Session en mode Edition -> On n'a pas la possibilité d'envoyer son expertise
        $version = $expertise->getVersion();
        if( $version == null )
            Functions::createException(__METHOD__ . ":" . __LINE__ . "  " . $expertise . " n'a pas de version !" );
        if ( $session -> getEtatSession() == Etat::EDITION_EXPERTISE && ! $version->isProjetTest() )
            $session_edition = true;
        else
            $session_edition = false;


        $editForm = $this->createFormBuilder($expertise)
            ->add('commentaireInterne', TextAreaType::class, [ 'required' => false ] )
            ->add('commentaireExterne', TextAreaType::class, [ 'required' => false ] )
            ->add('validation', ChoiceType::class ,
                [
                //'label' => " ",
                //'required'  =>  false,
                //'expanded' => true,
                'multiple' => false,
                //'data'     => $expertise->getValidation(),
                'choices'   =>  [ 'Accepter' =>     1, 'Refuser pour cette session' => 2, 'Refuser définitivement et fermer le projet'    => 0, ],
                ]);


        if( $expertise->getNbHeuresAtt() == 0 )
            $editForm->add('nbHeuresAtt', IntegerType::class , ['required'  =>  false, 'data' => $version->getDemHeures(), ]);
        else
            $editForm->add('nbHeuresAtt', IntegerType::class , ['required'  =>  false, ]);

        if ($session->getTypeSession() && ! $expertise->getVersion()->isProjetTest() )
            $editForm -> add('nbHeuresAttEte');

        $definitif  =   $expertise->getDefinitif();
        if( $definitif == false )
            $editForm = $editForm->add('enregistrer',   SubmitType::class, ['label' =>  'Enregistrer' ]);

        if( $definitif == false && $session_edition == false)
            $editForm   =   $editForm->add('envoyer',   SubmitType::class, ['label' =>  'Envoyer' ]);

        $editForm->add( 'retour',   SubmitType::Class );

        $editForm = $editForm->getForm();
        $editForm->handleRequest($request);

        //if ($editForm->isSubmitted() /* && $editForm->isValid() */ && $definitif == false )
        if( $editForm->isSubmitted() && ! $editForm->isValid() )
             Functions::warningMessage(__METHOD__ . " form error " .  Functions::show($editForm->getErrors() ) );

        // Bouton RETOUR
        if( $editForm->isSubmitted() && $editForm->get('retour')->isClicked() )
             return $this->redirectToRoute('expertise_liste');

        $erreur = 0;
        $erreurs = [];
        if ($editForm->isSubmitted() /* && $editForm->isValid()  */ && $definitif == false )
        {
            $erreurs = Functions::dataError( $expertise);

            $validation = $expertise->getValidation();

            if( $validation != 1 )
                {
                $expertise->setNbHeuresAtt(0);
                $expertise->setNbHeuresAttEte(0);
                if ( $validation == 2 && $expertise->getVersion()->getProjet()->isProjetTest() ) {
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

        return $this->render('expertise/modifier.html.twig',
            [
            'expertise'  => $expertise,
            'version'    => $expertise->getVersion(),
            'edit_form'  => $editForm->createView(),
            'anneePrec'  => $anneePrec,
            'anneeCour'  => $anneeCour,
            'session'    => $session,
            'session_edition' => $session_edition,
            'erreurs'    => $erreurs,
            'toomuch'    => $toomuch,
            ]);
    }


    /**
     * Displays a form to edit an existing expertise entity.
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

    if( $editForm->isSubmitted()  )
        {
        if( $editForm->get('annuler')->isClicked() )
            return $this->redirectToRoute('expertise_modifier', [ 'id' => $expertise->getId() ] );

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

        $em = AppBundle::getManager();
        $em->persist( $expertise );
        $em->flush();
        return $this->redirectToRoute('expertise_liste');
        }

     return $this->render('expertise/valider.html.twig',
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
