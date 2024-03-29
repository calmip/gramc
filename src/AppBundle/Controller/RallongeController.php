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

use AppBundle\Entity\Rallonge;
use AppBundle\Entity\Session;
use AppBundle\Entity\Version;
use AppBundle\Entity\Individu;
use AppBundle\Entity\Projet;
use AppBundle\Entity\Thematique;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use AppBundle\Utils\Etat;
use AppBundle\Utils\Signal;
use AppBundle\Utils\Functions;
use AppBundle\Workflow\Rallonge\RallongeWorkflow;
use AppBundle\AffectationExperts\AffectationExperts;
use AppBundle\AffectationExperts\AffectationExpertsRallonge;

use AppBundle\Utils\Menu;

use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use AppBundle\AppBundle;

/**
 * Rallonge controller.
 * @Security("has_role('ROLE_ADMIN')")
 * @Route("rallonge")
 */
class RallongeController extends Controller
{
    /**
     * Lists all rallonge entities.
     *
     * @Route("/", name="rallonge_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $rallonges = $em->getRepository('AppBundle:Rallonge')->findAll();

        return $this->render('rallonge/index.html.twig', array(
            'rallonges' => $rallonges,
        ));
    }

    /**
     * Creates a new rallonge entity.
     *
     * @Route("/new", name="rallonge_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $rallonge = new Rallonge();
        $form = $this->createForm('AppBundle\Form\RallongeType', $rallonge);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($rallonge);
            $em->flush($rallonge);

            return $this->redirectToRoute('rallonge_show', array('id' => $rallonge->getId()));
        }

        return $this->render('rallonge/new.html.twig', array(
            'rallonge' => $rallonge,
            'form' => $form->createView(),
        ));
    }

    /**
     * Creates a new rallonge entity.
     *
     * @Route("/{id}/creation", name="rallonge_creation")
     * @Method("GET")
     */
    public function creationAction(Request $request, Projet $projet)
    {
    // ACL
    if( Menu::rallonge_creation($projet)['ok'] == false )
        Functions::createException(__METHOD__ . ":" . __LINE__ . " impossible de créer une nouvelle rallonge pour le projet" . $projet .
            " parce que : " . Menu::rallonge_creation($projet)['raison'] );
    //return new Response( Functions::show( AppBundle::getRepository(Rallonge::class)->findRallongesOuvertes($projet)   ) );

    $version = $projet->versionActive();

    $rallonge   =   new Rallonge();
    $rallonge->setVersion( $version );
    $rallonge->setObjectState( Etat::CREE_ATTENTE);

    $session    =   Functions::getSessionCourante();

    $count  =   count ( $version->getRallonge() ) + 1;
    $rallonge->setIdRallonge( $version->getIdVersion() . 'R' . $count );

    $workflow = new RallongeWorkflow();
    $rtn = $workflow->execute( Signal::CLK_DEMANDE, $rallonge );
    if( $rtn == false )
        Functions::errorMessage(__METHOD__ . ":" . __LINE__ . " Impossible d'envoyer le signal CLK_DEMANDE à la rallonge " . $rallonge );

    Functions::sauvegarder( $rallonge );

    return $this->render('rallonge/creation.html.twig',
            [
            'projet'   => $projet,
            'rallonge' => $rallonge,
            ]
            );
    }

    /**
     * Finds and displays a rallonge entity.
     *
     * @Route("/{id}/show", name="rallonge_show")
     * @Method("GET")
     */
    public function showAction(Rallonge $rallonge)
    {
        $deleteForm = $this->createDeleteForm($rallonge);

        return $this->render('rallonge/show.html.twig', array(
            'rallonge' => $rallonge,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing rallonge entity.
     *
     * @Route("/{id}/edit", name="rallonge_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Rallonge $rallonge)
    {
        $deleteForm = $this->createDeleteForm($rallonge);
        $editForm = $this->createForm('AppBundle\Form\RallongeType', $rallonge);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('rallonge_edit', array('id' => $rallonge->getId()));
        }

        return $this->render('rallonge/edit.html.twig', array(
            'rallonge' => $rallonge,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing rallonge entity.
     *
     * @Route("/{id}/consulter", name="rallonge_consulter")
     * @Security("has_role('ROLE_DEMANDEUR')")
     * @Method("GET")
     */
    public function consulterAction(Request $request, Rallonge $rallonge)
    {
	    $version = $rallonge->getVersion();
	    if( $version != null )
        {
	        $projet  = $version->getProjet();
	        $session = $version->getSession();
        }
	    else
	        Functions::createException(__METHOD__ . ":" . __LINE__ . " rallonge " . $rallonge . " n'est pas associée à une version !");

	    // ACL
	    if( ! Functions::projetACL( $projet ) || $projet == null )
            Functions::createException(__METHOD__ . ':' . __LINE__ .' problème avec ACL');

	    $menu[]   = Menu::rallonge_modifier( $rallonge );
	    $menu[]   = Menu::rallonge_envoyer( $rallonge );

	    return $this->render('rallonge/consulter.html.twig',
            [
            'rallonge'  => $rallonge,
            'session'   => $session,
            'projet'    => $projet,
            'version'   => $version,
            'menu'      => $menu
            ]);
    }

     /**
     * Displays a form to edit an existing rallonge entity.
     *
     * @Route("/{id}/modifier", name="rallonge_modifier")
     * @Security("has_role('ROLE_DEMANDEUR')")
     * @Method({"GET", "POST"})
     */
    public function modifierAction(Request $request, Rallonge $rallonge)
    {
        // ACL
        if( Menu::rallonge_modifier($rallonge)['ok'] == false )
        {
            Functions::createException(__METHOD__ . " impossible de modifier la rallonge " . $rallonge->getIdRallonge().
                " parce que : " . Menu::rallonge_modifier($rallonge)['raison'] );
		}

        $editForm = $this->createFormBuilder($rallonge)
            ->add('demHeures', IntegerType::class, [ 'required'       =>  false ] )
            ->add('prjJustifRallonge', TextAreaType::class, [ 'required'       =>  false ] )
            ->add('enregistrer',SubmitType::class, ['label' => 'Enregistrer' ])
            ->add('fermer',SubmitType::class, ['label' => 'Fermer' ])
            ->getForm();

        $version = $rallonge->getVersion();
        if( $version != null )
		{
            $projet = $version->getProjet();
            $session = $version->getSession();
		}
        else
        {
            Functions::createException(__METHOD__ . ":" . __LINE__ . " rallonge " . $rallonge . " n'est pas associée à une version !");
		}
		
        $erreurs = [];
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted()  )
		{
            $erreurs = Functions::dataError( $rallonge);
            AppBundle::getManager()->flush();

            if( $editForm->get('fermer')->isClicked() )
            {
			    $menu[]   = Menu::rallonge_modifier( $rallonge );
			    $menu[]   = Menu::rallonge_envoyer( $rallonge );
                return $this->render('rallonge/fermer.html.twig',
				[
                    'rallonge'  => $rallonge,
                    'projet'    => $projet,
                    'session'   => $session,
                    'erreurs'   => $erreurs,
                    'menu'      => $menu,
				 ]);
			}
		}
        return $this->render('rallonge/modifier.html.twig',
		[
            'rallonge'  => $rallonge,
            'projet'    => $projet,
            'session'   => $session,
            'edit_form' => $editForm->createView(),
            'erreurs'   => $erreurs,
		]);
    }

     /**
     * Displays a form to edit an existing rallonge entity.
     *
     * @Route("/{id}/expertiser", name="rallonge_expertiser")
     * @Security("has_role('ROLE_EXPERT')")
     * @Method({"GET", "POST"})
     */
    public function expertiserAction(Request $request, Rallonge $rallonge)
    {

	    // ACL
	    if( Menu::rallonge_expertiser($rallonge)['ok'] == false )
	        Functions::createException(__METHOD__ . " impossible d'expertiser la rallonge " . $rallonge->getIdRallonge().
	            " parce que : " . Menu::rallonge_expertiser($rallonge)['raison'] );
	
	    $editForm = $this->createFormBuilder($rallonge)
	            ->add('nbHeuresAtt', IntegerType::class, [ 'required'       =>  false, 'data' => $rallonge->getDemHeures() ] )
	            ->add('commentaireInterne', TextAreaType::class, [ 'required'       =>  false ] )
	            ->add('validation', ChoiceType::class, ['expanded' => true, 'multiple' => false, 'choices' => [ 'Accepter' => true, 'Refuser' => false ]])
	            ->add('enregistrer',SubmitType::class, ['label' => 'Enregistrer' ])
	            ->add('annuler',SubmitType::class, ['label' => 'Annuler' ])
	            ->add('fermer',SubmitType::class, ['label' => 'Fermer' ])
	            ->add('envoyer',SubmitType::class, ['label' => 'Envoyer' ])
	            ->getForm();
	
	
	    $erreurs = [];
	    $editForm->handleRequest($request);
	
	    $version = $rallonge->getVersion();
	    if( $version != null )
        {
	        $projet = $version->getProjet();
	        $session = $version->getSession();
        }
	    else
	        Functions::createException(__METHOD__ . ":" . __LINE__ . " rallonge " . $rallonge . " n'est pas associée à une version !");
		
        // Bouton ANNULER
        if( $editForm->isSubmitted() && $editForm->get('annuler')->isClicked() )
        {
			return $this->redirectToRoute('expertise_liste');
		}

		// Boutons ENREGISTRER, FERMER ou ENVOYER
	    if ($editForm->isSubmitted()  )
		{
            $erreurs = Functions::dataError( $rallonge, ['expertise'] );
            AppBundle::getManager()->flush();

			// Bouton FERMER
			if ($editForm->get('fermer')->isClicked())
			{
				return $this->redirectToRoute('expertise_liste');
			}

			// bouton ENVOYER
            if( $editForm->get('envoyer')->isClicked() )
                return $this->redirectToRoute('avant_rallonge_envoyer_president', [ 'id' => $rallonge->getId() ]);
		}
	
	    $session    =   Functions::getSessionCourante();
	    $anneeCour  = 2000 +$session->getAnneeSession();
	    $anneePrec  = $anneeCour - 1;
	
	    return $this->render('rallonge/expertiser.html.twig',
	            [
	            'rallonge'  => $rallonge,
	            'edit_form' => $editForm->createView(),
	            'erreurs'   => $erreurs,
	            'anneePrec' => $anneePrec,
	            'anneeCour' => $anneeCour
	            ]);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////

    private function getFinaliserForm( Rallonge $rallonge )
    {
		$nbHeuresAttrib = [ 'required' => false ];
		if ($rallonge->getValidation() === false)
		{
			$nbHeuresAttrib['disabled'] = 'disabled';
		}
	    return $this->createFormBuilder($rallonge)
            ->add('nbHeuresAtt', IntegerType::class, $nbHeuresAttrib )
            ->add('commentaireInterne', TextAreaType::class, [ 'required' => false ] )
            ->add('commentaireExterne', TextAreaType::class, [ 'required' => false ] )
            ->add('validation', ChoiceType::class,
                    [
                    'expanded' => true,
                    'multiple' => false,
                    'choices' => [ 'Accepter' => true, 'Refuser' => false ],
                    'choice_attr' => function($key, $val, $index)
                            { return ['disabled' => 'disabled']; },
                    ])
            ->add('enregistrer',SubmitType::class, ['label' => 'Enregistrer' ])
            ->add('envoyer',SubmitType::class, ['label' => 'Envoyer' ])
            ->getForm();
    }

    /**
     * Displays a form to edit an existing rallonge entity.
     *
     * @Route("/{id}/avant_finaliser", name="avant_rallonge_finaliser")
     * @Security("has_role('ROLE_PRESIDENT')")
     * @Method({"GET", "POST"})
     */
    public function avantFinaliserAction(Request $request, Rallonge $rallonge)
    {
	    $erreurs = [];
	    $validation =   $rallonge->getValidation(); //  tout cela juste à cause de validation disabled
	
	    $editForm = $this->getFinaliserForm($rallonge);
	
	    $editForm->handleRequest($request);
	
	    $version = $rallonge->getVersion();
        if( $version != null )
		{
            $projet = $version->getProjet();
            $session = $version->getSession();
		}
        else
            Functions::createException(__METHOD__ . ":" . __LINE__ . " rallonge " . $rallonge . " n'est pas associée à une version !");

	    //if( ! $rallonge->isFinalisable() )
	    //    Functions::createException(__METHOD__ . ":" . __LINE__ . " rallonge " . $rallonge . " n'est pas en attente !");

	    if ($editForm->isSubmitted()  )
		{
            $rallonge->setValidation( $validation ); //  tout cela juste à cause de validation disabled
            $erreurs = Functions::dataError( $rallonge, ['president'] );

            Functions::sauvegarder( $rallonge );
            $workflow = new RallongeWorkflow();

            if( ! $workflow->canExecute( Signal::CLK_VAL_PRS, $rallonge ) )
			{
                $erreur = "La finalisation de la rallonge " . $rallonge .
                    " refusée par le workflow, la rallonge est dans l'état " . Etat::getLibelle( $rallonge->getEtatRallonge() );
                Functions::errorMessage(__METHOD__ . ":" . __LINE__ . ' ' . $erreur );
                $erreurs[] = $erreur;
			}
            elseif( $editForm->get('envoyer')->isClicked() && $erreurs == null  )
			{
                //$workflow->execute( Signal::CLK_VAL_PRS, $rallonge );
                return $this->render('rallonge/finaliser.html.twig',
                    [
                    'erreurs'   => $erreurs,
                    'projet'    => $projet,
                    'session'   => $session,
                    'rallonge'  => $rallonge,
                    ]);
			}
            //else
            //    return $this->redirectToRoute('avant_rallonge_finaliser', [ 'id' => $rallonge->getId() ] );
		}
	
	    $editForm = $this->getFinaliserForm($rallonge);  //  tout cela juste à cause de validation disabled
	
	    $session    =   Functions::getSessionCourante();
	    $anneeCour  = 2000 +$session->getAnneeSession();
	    $anneePrec  = $anneeCour - 1;
	
	    return $this->render('rallonge/avant_finaliser.html.twig',
		[
            'erreurs'   => $erreurs,
            'rallonge'  => $rallonge,
            'edit_form' => $editForm->createView(),
            'anneePrec' => $anneePrec,
            'anneeCour' => $anneeCour
		]);
    }

    /**
     * Displays a form to edit an existing rallonge entity.
     *
     * @Route("/{id}/avant_envoyer_president", name="avant_rallonge_envoyer_president")
     * @Security("has_role('ROLE_EXPERT')")
     * @Method("GET")
     */
    public function avantEnvoyerPresidentAction(Request $request, Rallonge $rallonge)
    {
        // ACL
        if( Menu::rallonge_expertiser($rallonge)['ok'] == false )
            Functions::createException(__METHOD__ . " impossible d'envoyer la demande " . $rallonge->getIdRallonge().
                " au président parce que : " . Menu::rallonge_expertiser($rallonge)['raison'] );

        $version = $rallonge->getVersion();
        if( $version != null )
		{
            $projet = $version->getProjet();
            $session = $version->getSession();
		}
        else
            Functions::createException(__METHOD__ . ":" . __LINE__ . " rallonge " . $rallonge . " n'est pas associée à une version !");

        $erreurs = Functions::dataError( $rallonge, ['expertise'] );

        return $this->render('rallonge/avant_envoyer_president.html.twig',
            [
            'rallonge'  => $rallonge,
            'projet'    => $projet,
            'session'   => $session,
            'erreurs'   => $erreurs,
            ]);
    }



    /**
     * Displays a form to edit an existing rallonge entity.
     *
     * @Route("/{id}/avant_envoyer", name="avant_rallonge_envoyer")
     * @Security("has_role('ROLE_DEMANDEUR')")
     * @Method("GET")
     */
    public function avantEnvoyerAction(Request $request, Rallonge $rallonge)
    {
        // ACL
        if( Menu::rallonge_envoyer($rallonge)['ok'] == false )
            Functions::createException(__METHOD__ . " impossible d'envoyer la rallonge " . $rallonge->getIdRallonge().
                " à l'expert parce que : " . Menu::rallonge_envoyer($rallonge)['raison'] );
        $version = $rallonge->getVersion();
        if( $version != null )
            {
            $projet = $version->getProjet();
            $session = $version->getSession();
            }
        else
            Functions::createException(__METHOD__ . ":" . __LINE__ . " rallonge " . $rallonge . " n'est pas associée à une version !");

        $erreurs = Functions::dataError( $rallonge);
        return $this->render('rallonge/avant_envoyer.html.twig',
            [
            'rallonge'  => $rallonge,
            'projet'    => $projet,
            'session'   => $session,
            'erreurs'   => $erreurs,
            ]);
    }

    /**
     * Displays a form to edit an existing rallonge entity.
     *
     * @Route("/{id}/envoyer", name="rallonge_envoyer")
     * @Security("has_role('ROLE_DEMANDEUR')")
     * @Method("GET")
     */
    public function envoyerAction(Request $request, Rallonge $rallonge)
    {
        // ACL
        if( Menu::rallonge_envoyer($rallonge)['ok'] == false )
            Functions::createException(__METHOD__ . " impossible de modifier la rallonge " . $rallonge->getIdRallonge().
                " parce que : " . Menu::rallonge_envoyer($rallonge)['raison'] );

        $erreurs = Functions::dataError( $rallonge);
        $workflow = new RallongeWorkflow();

        if( $erreurs != null )
		{
            Functions::warningMessage(__METHOD__ . ":" . __LINE__ ." L'envoi à l'expert de la rallonge " . $rallonge . " refusé à cause des erreurs !");
            return $this->redirectToRoute('avant_rallonge_envoyer', [ 'id' => $rallonge->getId() ]);
		}
        elseif( ! $workflow->canExecute( Signal::CLK_VAL_DEM, $rallonge ) )
		{
            Functions::warningMessage(__METHOD__ . ":" . __LINE__ ." L'envoi à l'expert de la rallonge " . $rallonge .
                " refusé par le workflow, la rallonge est dans l'état " . Etat::getLibelle( $rallonge->getEtatRallonge() ) );
            return $this->redirectToRoute('avant_rallonge_envoyer', [ 'id' => $rallonge->getId() ]);
		}

        $workflow->execute( Signal::CLK_VAL_DEM, $rallonge );

        $version = $rallonge->getVersion();
        if( $version != null )
		{
            $projet = $version->getProjet();
            $session = $version->getSession();
		}
        else
            Functions::createException(__METHOD__ . ":" . __LINE__ . " rallonge " . $rallonge . " n'est pas associée à une version !");


        return $this->render('rallonge/envoyer.html.twig',
		[
            'rallonge'  => $rallonge,
            'projet'    => $projet,
            'session'   => $session,
		]);
    }


    /**
     * Displays a form to edit an existing rallonge entity.
     *
     * @Route("/{id}/finaliser", name="rallonge_finaliser")
     * @Security("has_role('ROLE_PRESIDENT')")
     * @Method("GET")
     */
    public function finaliserAction(Request $request, Rallonge $rallonge)
    {
        $erreurs = Functions::dataError( $rallonge);
        $workflow = new RallongeWorkflow();

        if( $erreurs != null )
		{
            Functions::warningMessage(__METHOD__ . ":" . __LINE__ ." La finalisation de la rallonge " . $rallonge . " refusée à cause des erreurs !");
            return $this->redirectToRoute('avant_rallonge_finaliser', [ 'id' => $rallonge->getId() ]);
		}
        elseif( ! $workflow->canExecute( Signal::CLK_VAL_PRS, $rallonge ) )
		{
            Functions::warningMessage(__METHOD__ . ":" . __LINE__ ." La finalisation de la rallonge " . $rallonge .
                " refusée par le workflow, la rallonge est dans l'état " . Etat::getLibelle( $rallonge->getEtatRallonge() ) );
            return $this->redirectToRoute('avant_rallonge_finaliser', [ 'id' => $rallonge->getId() ]);
		}

        if( $rallonge->getValidation() == true )
            $workflow->execute( Signal::CLK_VAL_PRS, $rallonge );
        else
            $workflow->execute( Signal::CLK_FERM, $rallonge );

        $version = $rallonge->getVersion();
        if( $version != null )
		{
            $rallonge->setAttrHeures( $rallonge->getNbHeuresAtt() );
            $projet = $version->getProjet();
            $session = $version->getSession();
		}
        else
            Functions::createException(__METHOD__ . ":" . __LINE__ . " rallonge " . $rallonge . " n'est pas associée à une version !");


        return $this->render('rallonge/rallonge_finalisee.html.twig',
		[
            'erreurs'   => $erreurs,
            'rallonge'  => $rallonge,
            'projet'    => $projet,
            'session'   => $session,
		]);
    }


 /**
     * Displays a form to edit an existing rallonge entity.
     *
     * @Route("/{id}/envoyer_president", name="rallonge_envoyer_president")
     * @Security("has_role('ROLE_EXPERT')")
     * @Method("GET")
     */
    public function envoyerPresidentAction(Request $request, Rallonge $rallonge)
    {
       // ACL
        if( Menu::rallonge_expertiser($rallonge)['ok'] == false )
            Functions::createException(__METHOD__ . " impossible d'envoyer la demande " . $rallonge->getIdRallonge().
                " au président parce que : " . Menu::rallonge_expertiser($rallonge)['raison'] );

        $erreurs = Functions::dataError( $rallonge, ['expertise'] );
        $workflow = new RallongeWorkflow();

        if( $erreurs != null )
		{
            Functions::warningMessage(__METHOD__ . ":" . __LINE__ ." L'envoi au président de la rallonge " . $rallonge . " refusé à cause des erreurs !");
            return $this->redirectToRoute('avant_rallonge_envoyer_president', [ 'id' => $rallonge->getId() ]);
		}
        elseif( ! $workflow->canExecute( Signal::CLK_VAL_EXP_OK, $rallonge ) )
		{
            Functions::warningMessage(__METHOD__ . ":" . __LINE__ ." L'envoi au président de la rallonge " . $rallonge .
                " refusé par le workflow, la rallonge est dans l'état " . Etat::getLibelle( $rallonge->getEtatRallonge() ) );
            return $this->redirectToRoute('avant_rallonge_envoyer_presdient', [ 'id' => $rallonge->getId() ]);
		}

        if( $rallonge->getValidation() == true )
            $workflow->execute( Signal::CLK_VAL_EXP_OK, $rallonge );
        elseif( $rallonge->getValidation() == false )
            $workflow->execute( Signal::CLK_VAL_EXP_KO, $rallonge );
        else
            Functions::createException(__METHOD__ . ":" . __LINE__ . " rallonge " . $rallonge . " contient une validation erronée !");


        $version = $rallonge->getVersion();
        if( $version != null )
		{
            $projet = $version->getProjet();
			$session = $version->getSession();
		}
        else
            Functions::createException(__METHOD__ . ":" . __LINE__ . " rallonge " . $rallonge . " n'est pas associée à une version !");

        return $this->render('rallonge/envoyer_president.html.twig',
            [
            'rallonge'  => $rallonge,
            'projet'    => $projet,
            'session'   => $session,
            ]);
    }



    /**
     * Affectation des experts
     *
     * @Route("/affectation", name="rallonge_affectation")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_PRESIDENT')")
     */
    public function affectationAction(Request $request)
    {
		$em = $this->getDoctrine()->getManager();
	    $sessions = AppBundle::getRepository(Session::class) ->findBy( ['etatSession' => Etat::ACTIF ] );
	    if ( isset( $sessions[0] ) )
	        $session1 = $sessions[0];
	    else
	        $session1 = null;
	    $session = $session1;

	    if ( isset( $sessions[1] ) )
        {
	        $session2 = $sessions[1];
	        $session  = $session2;
        }
	    else
	        $session2 = null;

        $annee = $session->getAnneeSession();

	    $all_rallonges = $em -> getRepository(Rallonge::class)->findSessionRallonges($sessions);
	    
		$affectationExperts = new AffectationExpertsRallonge($request, $all_rallonges, $this->get('form.factory'), $this->getDoctrine());
		
		//
		// 1ere etape = Traitement des formulaires qui viennent d'être soumis
		//              Puis on redirige sur la page
		//
		$form_buttons = $affectationExperts->getFormButtons();
		$form_buttons->handleRequest($request);
		if ($form_buttons->isSubmitted())
		{
			$affectationExperts->traitementFormulaires();
			return $this->redirectToRoute('rallonge_affectation');
		}

		// 2nde étape = Création des formulaires pour affichage et génération des données de "stats"
		//              On utilise $proj, un tableau associatif indexé par id_projet
	    $proj = [];
		foreach ($all_rallonges as $rallonge)
		{
			$version   = $rallonge->getVersion();
			$projet    = $version->getProjet();
			$id_projet = $projet->getIdProjet();
			if ( ! isset($proj[$id_projet] ))
			{
				$p = [];
				$proj[$id_projet] = $p;
	            $proj[$id_projet ]['projet']      = $projet;
	            $proj[$id_projet ]['version']     = $version;
	            $proj[ $id_projet ]['rallonges']  = [];
	            $proj[ $id_projet ]['etat']       = $projet->getMetaEtat();
	            $proj[ $id_projet ]['etatProjet']         = $projet->getEtatProjet();
	            $proj[ $id_projet ]['libelleEtatProjet']  = Etat::getLibelle( $projet->getEtatProjet() );
	            $proj[ $id_projet ]['etatVersion']        = $version->getEtatVersion();
	            $proj[ $id_projet ]['libelleEtatVersion'] = Etat::getLibelle( $version->getEtatVersion() );
	            $proj[ $id_projet ]['conso']      = $projet->getConsoCalcul(  $version->getAnneeSession() );
				$expert = $rallonge->getExpert();
                if( $rallonge->getExpert() != null )
                {
					$proj[$id_projet]['affecte'] = true;
				}
                else
                {
					$proj[$id_projet]['affecte'] = false;
				}

	            if( $version->isNouvelle() )
	                $proj[ $id_projet ]['NR'] = 'N';
	            else
	                $proj[ $id_projet ]['NR'] = '';
			}
			$proj[ $id_projet ]['rallonges'][] = $rallonge;
		}

		// 3 ème étape = Mise en forme pour l'affichage
		// 				 Dans rowspan = le nombre de rallonges + 1
		// 				 Dans rstate  = l'état de la dernière rallonge
		//
		// On recopie $proj dans $projets, qui pourra être trié
		$projets = [];
	    foreach( $proj as $key => $projet )
        {
			$nr = count( $projet['rallonges'] );
	        $projet['rowspan'] = $nr + 1;
	        $projet['rstate']  = intval($projet['rallonges'][$nr-1]->getEtatRallonge());
	        $projets[] = $projet;
        }
  
		// On trie les projets en fonction de l'état de la dernière rallonge
		usort($projets, "self::cmpProjetsByRallonges");
		
		
		$forms = $affectationExperts->getExpertsForms();
		$stats = $affectationExperts->getStats();
		$titre = "Affectation des experts aux rallonges de l'année 20$annee"; 

        return $this->render('rallonge/affectation.html.twig',
		[
            'projets'  => $projets,
            'forms'    => $forms,
            'session1' => $session1,
            'session2' => $session2,
            'stats'    => $stats,
		]);
    }

	// Cette fonction est utilisée par affectationAction, 
	// elle permet d'écrire les rallonges de manière ordonnée
	// D'abord les projets qui ont une rallonge en état "non actif"
	//
	private static function cmpProjetsByRallonges($a,$b)
	{
		if ($a['rstate'] == $b['rstate'])
		{
	        return 0;
	    }
	    return ($a['rstate'] < $b['rstate']) ? -1 : 1;
	}



    /**
     * Deletes a rallonge entity.
     *
     * @Route("/{id}", name="rallonge_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Rallonge $rallonge)
    {
        $form = $this->createDeleteForm($rallonge);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($rallonge);
            $em->flush($rallonge);
        }

        return $this->redirectToRoute('rallonge_index');
    }

    /**
     * Creates a form to delete a rallonge entity.
     *
     * @param Rallonge $rallonge The rallonge entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Rallonge $rallonge)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('rallonge_delete', array('id' => $rallonge->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
