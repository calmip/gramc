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
use AppBundle\Workflow\Session\SessionWorkflow;
use AppBundle\Utils\GramcDate;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;


/**
 * Workflow controller pour faire des tests.
 * @Security("has_role('ROLE_ADMIN')")
 * @Route("workflow")
 */
class WorkflowController extends Controller
{
    /**
     * entry.
     *
     * @Route("/", name="workflow_index")
     * Method({"GET", "POST"})
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $projets = [];
        foreach( $em->getRepository(Projet::class)->findAll() as $projet )
            if( $projet->getEtatProjet() != Etat::TERMINE )
                 $projets[] = $projet;

        $sessions = array_slice( $em->getRepository(Session::class)->findAll(), -4);
        $menu   = [];

        foreach( $sessions as $session )
            {
            $signal_forms[$session->getIdSession()] = AppBundle::getFormBuilder('signal' . $session->getIdSession())
            ->add('signal',   ChoiceType::class,
                    [
                    'multiple' => false,
                    'required'  => true,
                    'label'     => '',
                    'choices' =>
                                [
                                'DAT_DEB_DEM'   =>   Signal::DAT_DEB_DEM,
                                'DAT_FIN_DEM'   =>   Signal::DAT_FIN_DEM,
                                'CLK_ATTR_PRS'  =>   Signal::CLK_ATTR_PRS,
                                'CLK_SESS_DEB'       =>   Signal::CLK_SESS_DEB,
                                'CLK_SESS_FIN'       =>   Signal::CLK_SESS_FIN,
                                ],
                    ])
            ->add('submit', SubmitType::class, ['label' => 'Envoyer le signal à la session ' . $session->getIdSession()], ['required'  => false ])
            ->getForm();

            $signal_forms[$session->getIdSession()]->handleRequest($request);
            $signal_view_forms[$session->getIdSession()] = $signal_forms[$session->getIdSession()]->createView();

            if( $signal_forms[$session->getIdSession()]->isSubmitted() && $signal_forms[$session->getIdSession()]->isValid() )
                {
                $signal = $signal_forms[$session->getIdSession()]->getData()['signal'];

                $sessionWorkflow    =   new SessionWorkflow($session);
                $rtn = $sessionWorkflow->execute( $signal, $session );
                if ( $rtn == true )
                    Functions::debugMessage('WorkflowController : signal ' . Signal::getLibelle( $signal ). " a été appliqué avec succès sur " . $session);
                else
                    Functions::debugMessage('WorkflowController : signal ' . Signal::getLibelle( $signal ). " a été appliqué avec erreur sur " . $session);
                return $this->redirectToRoute('workflow_index');
                }


            ///////////////////////////////

            $etat_forms[$session->getIdSession()] = AppBundle::getFormBuilder('etat' . $session->getIdSession() )
            ->add('etat',   ChoiceType::class,
                    [
                    'multiple' => false,
                    'required'  => true,
                    'label'     => '',
                    'data'      => $session->getEtatSession(),
                    'choices' =>
                                [
                                'CREE_ATTENTE'                  =>   Etat::CREE_ATTENTE,
                                'EDITION_DEMANDE'               =>   Etat::EDITION_DEMANDE,
                                'EDITION_EXPERTISE'             =>   Etat::EDITION_EXPERTISE,
                                'EN_ATTENTE'                    =>   Etat::EN_ATTENTE,
                                'ACTIF'                         =>   Etat::ACTIF,
                                'TERMINE'                       =>   Etat::TERMINE,
                                ],
                    ])
            ->add('submit', SubmitType::class, ['label' => "Changer l'état de la session " . $session->getIdSession()], ['required'  => false ])
            ->getForm();

            $etat_forms[$session->getIdSession()]->handleRequest($request);
            $etat_view_forms[$session->getIdSession()] = $etat_forms[$session->getIdSession()]->createView();

            if( $etat_forms[$session->getIdSession()]->isSubmitted() && $etat_forms[$session->getIdSession()]->isValid() )
                {
                $session->setEtatSession($etat_forms[$session->getIdSession()]->getData()['etat']);
                Functions::sauvegarder( $session );
                return $this->redirectToRoute('workflow_index');
                }
            }

        return $this->render('workflow/index.html.twig',
            [
            'projets' => $projets,
            'sessions' => $sessions,
            'signal_view_forms'         => $signal_view_forms,
            'etat_view_forms'           => $etat_view_forms,
            'menu'  => $menu,
            ]);
    }

     /**
     *
     * @Route("/{id}/modify", name="worklow_modifier_session")
     * @Method({"GET", "POST"})
     */
    public function modifySessionAction(Request $request, Session $session)
    {
        $session_form = AppBundle::createFormBuilder()
            ->add('signal',   ChoiceType::class,
                    [
                    'multiple' => false,
                    'required'  => true,
                    'label'     => 'Signal',
                    'choices' =>
                                [
                                'DAT_DEB_DEM'   =>   Signal::DAT_DEB_DEM,
                                'DAT_FIN_DEM'   =>   Signal::DAT_FIN_DEM,
                                'CLK_ATTR_PRS'  =>   Signal::CLK_ATTR_PRS,
                                'CLK_SESS_DEB'       =>   Signal::CLK_SESS_DEB,
                                'CLK_SESS_FIN'       =>   Signal::CLK_SESS_FIN,
                                ],
                    ])
        ->add('submit', SubmitType::class, ['label' => 'Envoyer le signal'], ['required'  => false ])
        ->getForm();

        $session_form->handleRequest($request);

        if ( $session_form->isSubmitted() && $session_form->isValid() )
            {
            $signal = $session_form->getData()['signal'];

            $sessionWorkflow    =   new SessionWorkflow();
            $rtn = $sessionWorkflow->execute( $signal, $session );
            if ( $rtn == true )
                Functions::debugMessage('WorkflowController : signal ' . Signal::getLibelle( $signal ). " a été appliqué avec succès");
            else
                Functions::debugMessage('WorkflowController : signal ' . Signal::getLibelle( $signal ). " a été appliqué avec erreur");
            return $this->redirectToRoute('worklow_modifier_session', [ 'id' => $session->getIdSession() ]);
            }

        ////////////////////////////

        $etat_form = AppBundle::createFormBuilder()
            ->add('etat',   ChoiceType::class,
                    [
                    'multiple' => false,
                    'required'  =>  true,
                    'label'     => 'État',
                    'data'      => $session->getEtatSession(),
                    'choices' =>
                                [
                                'CREE_ATTENTE'                  =>   Etat::CREE_ATTENTE,
                                'EDITION_DEMANDE'               =>   Etat::EDITION_DEMANDE,
                                'EDITION_EXPERTISE'             =>   Etat::EDITION_EXPERTISE,
                                'EN_ATTENTE'                    =>   Etat::EN_ATTENTE,
                                'ACTIF'                         =>   Etat::ACTIF,
                                'TERMINE'                       =>   Etat::TERMINE,
                                ],
                    ])
        ->add('submit', SubmitType::class, ['label' => "Changer l'état"])
        ->getForm();

        $etat_form->handleRequest($request);

        if ( $etat_form->isSubmitted() &&  $etat_form->isValid())
            {
            $session->setEtatSession( $etat_form->getData()['etat'] );
            Functions::sauvegarder( $session );
            return $this->redirectToRoute('worklow_modifier_session', [ 'id' => $session->getIdSession() ]);
            }

        ////////////////////////////

        return $this->render('workflow/modify_session.html.twig',
            [
            'session' => $session,
            'session_form' => $session_form->createView(),
            'etat_form' => $etat_form->createView(),
            ]);
    }

    /**
     *
     * @Route("/{id}/signal", name="workflow_signal_projet")
     * @Method({"GET", "POST"})
     */
    public function signalProjetAction(Request $request, Projet $projet)
    {
    //$projetWorkflow    =   new VersionWorkflow();
    //return new Response($projetWorkflow);

    $versions = $projet->getVersion();
    $sessions = [];
    $old_sessions = [];
    foreach( $versions as $version ) $old_sessions[] = $version->getSession();

    foreach ( array_slice( AppBundle::getRepository(Session::class)->findAll(), -4) as $session )
        if( ! in_array( $session,  $old_sessions ) ) $sessions[] = $session;

     $form = AppBundle::getFormBuilder('session')
            ->add('session',   EntityType::class,
                    [
                    'multiple' => false,
                    'class' => 'AppBundle:Session',
                    'required'  =>  true,
                    'label'     => 'Session',
                    'choices' =>  $sessions,
                    'choice_label' => function($session){ return $session->getIdSession(); }
                    ])
        ->add('submit', SubmitType::class, ['label' => 'Nouvelle version'])
        ->getForm();

    $form->handleRequest($request);
    if( $form->isSubmitted()&& $form->isValid() )
        {
        $session = $form->getData()['session'];
        if( $session != null )
            {
            $version = new Version();
            $version->setSession( $session );
            $version->setIdVersion( $session->getIdSession() . $projet->getIdProjet() );
            $version->setProjet( $projet );
            Functions::sauvegarder( $version);
            return $this->redirectToRoute('workflow_signal_projet', [ 'id' => $projet->getIdProjet() ]);
            }
        }

    //////////////////////

    $signal_form  = AppBundle::getFormBuilder('signal')
            ->add('signal',   ChoiceType::class,
                    [
                    'multiple' => false,
                    'required'  =>  true,
                    'label'     => 'Signal',
                    'choices' =>
                                [
                                'CLK_DEMANDE'   =>   Signal::CLK_DEMANDE,
                                'CLK_VAL_DEM'   =>   Signal::CLK_VAL_DEM,
                                'CLK_VAL_EXP_OK'  =>   Signal::CLK_VAL_EXP_OK,
                                'CLK_VAL_EXP_KO'  =>   Signal::CLK_VAL_EXP_KO,
                                'CLK_VAL_EXP_CONT'=>   Signal::CLK_VAL_EXP_CONT,
                                'CLK_ARR'       =>   Signal::CLK_ARR,
                                'CLK_SESS_DEB'       =>   Signal::CLK_SESS_DEB,
                                'CLK_SESS_FIN'       =>   Signal::CLK_SESS_FIN,
                                'CLK_FERM'      =>   Signal::CLK_FERM,
                                ],
                    ])
        ->add('submit', SubmitType::class, ['label' => 'Envoyer le signal au projet'])
        ->getForm();

    $signal_form->handleRequest($request);
    if( $signal_form->isSubmitted()&& $signal_form->isValid() )
        {
        $signal = $signal_form->getData()['signal'];


        $projetWorkflow    =   new ProjetWorkflow();
        $rtn = $projetWorkflow->execute( $signal, $projet );
        if ( $rtn == true )
            Functions::debugMessage('WorkflowController : signal ' . Signal::getLibelle( $signal ). " a été appliqué avec succès sur le projet " . $projet);
        elseif( $rtn == false )
            Functions::debugMessage('WorkflowController : signal ' .Signal::getLibelle( $signal ) . " a été appliqué avec erreur sur le projet " . $projet);
        elseif( is_array($rtn ) )
            {
            $message = 'WorkflowController : signal ' . $signal;
            foreach( $rtn as $return )
                $message .= "(" . $return['signal'] . ":" . $return['object'] . ")";
            Functions::debugMessage($message);
            }

       return $this->redirectToRoute('workflow_signal_projet', [ 'id' => $projet->getIdProjet() ]);
        }

    //////////////////////////////////
    $projet_form = AppBundle::getFormBuilder('projet' )
            ->add('etat',   ChoiceType::class,
                    [
                    'multiple' => false,
                    'required'  =>  true,
                    'label'     => 'État',
                    'data'      => $projet->getEtatProjet(),
                    'choices' =>
                                [
                                'RENOUVELABLE'                  =>   Etat::RENOUVELABLE,
                                'NON_RENOUVELABLE'              =>   Etat::NON_RENOUVELABLE,
                                'EDITION_DEMANDE'               =>   Etat::EDITION_DEMANDE,
                                'EDITION_TEST   '               =>   Etat::EDITION_TEST,
                                'EDITION_EXPERTISE'             =>   Etat::EDITION_EXPERTISE,
                                'EN_ATTENTE'                    =>   Etat::EN_ATTENTE,
                                'ACTIF'                         =>   Etat::ACTIF,
                                'TERMINE'                       =>   Etat::TERMINE,
                                'EN_STANDBY'                    =>   Etat::EN_STANDBY,
                                'EN_SURSIS'                     =>   Etat:: EN_SURSIS,
                                'ANNULE'                        =>   Etat::ANNULE,
                                ],
                    ])
        ->add('submit', SubmitType::class, ['label' => "Changer l'état du projet " . $projet->getIdProjet() ])
        ->getForm();

        $projet_form->handleRequest($request);

        if ( $projet_form->isSubmitted() &&  $projet_form->isValid())
            {
            $projet->setEtatProjet( $projet_form->getData()['etat'] );
            Functions::sauvegarder( $projet );
            return $this->redirectToRoute('workflow_signal_projet', [ 'id' => $projet->getIdProjet() ]);
            }

    //////////////////////////////////

    $versions = $projet->getVersion();

    $etat_view_forms = [];

    foreach( $versions as $version )
        {
        $etat_forms[$version->getIdVersion()] = AppBundle::getFormBuilder('version' . $version->getIdVersion() )
            ->add('etat',   ChoiceType::class,
                    [
                    'multiple' => false,
                    'required'  =>  true,
                    'label'     => 'État',
                    'data'      => $version->getEtatVersion(),
                    'choices' =>
                                [
                                'EDITION_DEMANDE'               =>   Etat::EDITION_DEMANDE,
                                'EDITION_TEST'                  =>   Etat::EDITION_TEST,
                                'EDITION_EXPERTISE'             =>   Etat::EDITION_EXPERTISE,
                                'EN_ATTENTE'                    =>   Etat::EN_ATTENTE,
                                'ACTIF'                         =>   Etat::ACTIF,
                                'TERMINE'                       =>   Etat::TERMINE,
                                'ANNULE'                        =>   Etat::ANNULE,
                                ],
                    ])
        ->add('submit', SubmitType::class, ['label' => "Changer l'état de la version " . $version->getIdVersion() ])
        ->getForm();

        $etat_forms[$version->getIdVersion()]->handleRequest($request);
        $etat_view_forms[$version->getIdVersion()] = $etat_forms[$version->getIdVersion()]->createView();

        if ( $etat_forms[$version->getIdVersion()]->isSubmitted() &&  $etat_forms[$version->getIdVersion()]->isValid())
            {
            $version->setEtatVersion( $etat_forms[$version->getIdVersion()]->getData()['etat'] );
            Functions::sauvegarder( $version );
            return $this->redirectToRoute('workflow_signal_projet', [ 'id' => $projet->getIdProjet() ]);
            }
        }

    return $this->render('workflow/add_version.html.twig',
            [
            'projet' => $projet,
            'versions' => $versions,
            'form' => $form->createView(),
            'signal_form'   => $signal_form->createView(),
            'etat_view_forms'   => $etat_view_forms,
            'projet_form'   => $projet_form->createView(),
            ]);

    }

     /**
     *
     * @Route("/{id}/reset", name="workflow_reset_version")
     * @Method({"GET", "POST"})
     */
    public function resetVersionAction(Request $request, Version $version)
    {
    $version->setEtatVersion( Etat::EDITION_DEMANDE );
    Functions::sauvegarder( $version );
    return $this->redirectToRoute('workflow_signal_projet', [ 'id' => $version->getProjet()->getIdProjet() ]);
    }


}
