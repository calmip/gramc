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

use AppBundle\Entity\Individu;
use AppBundle\Entity\Thematique;
use AppBundle\AppBundle;
use AppBundle\Utils\Functions;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;

use AppBundle\Form\GererUtilisateurType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

// pour remplacer un utilisateur par un autre

use AppBundle\Entity\CollaborateurVersion;
use AppBundle\Entity\CompteActivation;
use AppBundle\Entity\Expertise;
use AppBundle\Entity\Journal;
use AppBundle\Entity\Rallonge;
use AppBundle\Entity\Session;
use AppBundle\Entity\Sso;
use AppBundle\Entity\Version;

/**
 * Individu controller.
 *
 * @Security("has_role('ROLE_ADMIN')")
 * @Route("individu")
 */
class IndividuController extends Controller
{


    /**
     * Supprimer utilisateur
     *
     * @Route("/{id}/supprimer", name="supprimer_utilisateur")
     * @Method("GET")
     */
    public function supprimerUtilisateurAction(Request $request, Individu $individu)
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($individu);
        $em->flush();
        return $this->redirectToRoute('individu_gerer');
    }

    /**
     * Supprimer utilisateur
     *
     * @Route("/{id}/remplacer", name="remplacer_utilisateur")
     * @Method({"GET", "POST"})
     */
    public function remplacerUtilisateurAction(Request $request, Individu $individu )
    {
        $form = $this
            ->get('form.factory')
            ->createNamedBuilder('autocomplete_form', FormType::class, [])
            ->add('mail', TextType::class,
                [
                'required' => false, 'csrf_protection' => false
                ])
            ->add('submit', SubmitType::class,
                 [
                 'label' => "Le nouvel utilisateur",
                 ])
            ->getForm();


        $CollaborateurVersion       =   AppBundle::getRepository(CollaborateurVersion::class)->findBy(['collaborateur' => $individu]);
        $CompteActivation           =   AppBundle::getRepository(CompteActivation ::class)->findBy(['individu' => $individu]);
        $Expertise                  =   AppBundle::getRepository(Expertise ::class)->findBy(['expert' => $individu]);
        $Journal                    =   AppBundle::getRepository(Journal::class)->findBy(['individu' => $individu]);
        $Rallonge                   =   AppBundle::getRepository(Rallonge::class)->findBy(['expert' => $individu]);
        $Session                    =   AppBundle::getRepository(Session::class)->findBy(['president' => $individu]);
        $Sso                        =   AppBundle::getRepository(Sso::class)->findBy(['individu' => $individu]);
        //$Version                    =   AppBundle::getRepository(Version::class)->findBy(['majInd' => $individu]);
        $Thematique                 =   $individu->getThematique();

        $erreurs  =   [];

        // utilisateur peu actif peut être effacé

        if(  $CollaborateurVersion == null && $Expertise == null
              && $Rallonge == null && $Session == null )
                {
                $em = AppBundle::getManager();

                foreach( $individu->getThematique() as $item )
                    {
                    $em->persist( $item );
                    $item->getExpert()->removeElement( $individu );
                    }

                foreach ( $CompteActivation  as $item )
                    $em->remove($item);


                foreach ( $Sso  as $item )
                    $em->remove($item);

                Functions::infoMessage('Utilisateur ' . $individu . ' (' .  $individu->getIdIndividu() . ') directement effacé ');

                $em->remove($individu);

                $em->flush();
                return $this->redirectToRoute('individu_gerer');
                }

        // utilisateur actif doit être remplacé

        $form->handleRequest($request);
        if( $form->isSubmitted() && $form->isValid() )
            {
            $mail  =   $form->getData()['mail'];
            $new_individu   =   AppBundle::getRepository(Individu::class)->findOneBy(['mail'=>$mail]);

            if( $new_individu != null )
                {
                $em = AppBundle::getManager();

                foreach( $individu->getThematique() as $item )
                    {
                    $em->persist( $item );
                    $item->getExpert()->removeElement( $individu );
                    }



                foreach ( $CollaborateurVersion  as $item )
                    {
                    if( ! $item->getVersion()->isCollaborateur( $new_individu ) )
                        $item->setCollaborateur( $new_individu );
                    else
                        $em->remove( $item );
                    }

                foreach ( $Expertise  as $item )
                    $item->setExpert( $new_individu );

                 foreach ( $Rallonge  as $item )
                    $item->setExpert( $new_individu );

                foreach ( $Journal  as $item )
                    $item->setIndividu( $new_individu );

                foreach ( $Session  as $item )
                    $item->setPresident( $new_individu );

                /*
                foreach ( $Version  as $item )
                    $item->setMajInd( $new_individu );
                */

                foreach ( $CompteActivation  as $item )
                    $em->remove($item);


                foreach ( $Sso  as $item )
                    $em->remove($item);

                Functions::infoMessage('Utilisateur ' . $individu . '(' .  $individu->getIdIndividu()
                    . ') remplacé par ' . $new_individu . ' (' .  $new_individu->getIdIndividu() . ')' );

                $em->remove($individu);

                $em->flush();
                return $this->redirectToRoute('individu_gerer');
                }
            else
                $erreurs[] = "Le mail du nouvel utilisateur \"" . $mail . "\" ne correspond à aucun utilisateur existant";
            }

        return $this->render('individu/remplacer.html.twig',
                [
                'form' => $form->createView(),
                'erreurs'                   => $erreurs,
                'CollaborateurVersion'   =>  $CollaborateurVersion,
                'CompteActivation'       =>  $CompteActivation,
                'Expertise'              =>  $Expertise ,
                'Journal '               =>  $Journal,
                'Rallonge'               =>  $Rallonge,
                'Session'                =>  $Session,
                'Sso'                    =>  $Sso,
                'individu'               =>  $individu,
                'Thematique'             =>  $Thematique->toArray(),
                ]
                );
    }

     /**
     * Deletes a individu entity.
     *
     * @Route("/{id}/delete", name="individu_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Individu $individu)
    {
        $form = $this->createDeleteForm($individu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($individu);
            $em->flush($individu);
        }

        return $this->redirectToRoute('individu_index');
    }


    /**
     * Lists all individu entities.
     *
     * @Route("/", name="individu_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $individus = $em->getRepository('AppBundle:Individu')->findAll();

        return $this->render('individu/index.html.twig', array(
            'individus' => $individus,
        ));
    }

     /**
     * Attribue le rôle PRESIDENT.
     *
     * CET ECRAN EST SUPPRIME - REMPLACE PAR LA COLONNE PRESIDENT DANS LE TABLEAU DES UTILISATEURS !
     *
     * @Route("/president", name="individu_president")
     * @Security("has_role('ROLE_ADMIN')")
     * @Method({"GET", "POST"})
     */
    public function presidentAction(Request $request)
    {
    $choices = AppBundle::getRepository(Individu::class)->getPresidentiables();
    $presidents = [];
    foreach( $choices as $choice )
        if( $choice->getPresident() ) $presidents[] = $choice;

    $form = AppBundle::createFormBuilder()
            ->add('president',   EntityType::class,
                    [
                    'class' => 'AppBundle:Individu',
                    'multiple' => true,
                    'required'  =>  false,
                    'expanded'  => true,
                    'choices' =>  $choices,
                    'data'  => $presidents,
                    'label' =>  ' ',
                    ])
            ->add('submit', SubmitType::class,
                 [
                 'label' => "Définir les présidents pour l'application",
                 ]
                 )
            ->getForm();

    $form->handleRequest($request);
    if( $form->isSubmitted() && $form->isValid() )
        {
        $em =  AppBundle::getManager();
        $presidents = $form->getData()['president'];

        //return new Response( Functions::show( $presidents->toArray() ) );
        foreach( $choices as $choice )          $choice->setPresident( false );
        foreach( $presidents as $president )    $president->setPresident( true );

        AppBundle::getManager()->flush();

        }

     return $this->render('individu/president.html.twig',
            [
            'form' => $form->createView(),
            ]
            );
    }

    /**
     * Creates a new individu entity.
     *
     * @Route("/new", name="individu_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $individu = new Individu();
        $form = $this->createForm('AppBundle\Form\IndividuType', $individu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($individu);
            $em->flush($individu);

            return $this->redirectToRoute('individu_show', array('id' => $individu->getId()));
        }

        return $this->render('individu/new.html.twig', array(
            'individu' => $individu,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a individu entity.
     *
     * @Route("/{id}/show", name="individu_show")
     * @Method("GET")
     */
    public function showAction(Individu $individu)
    {
        $deleteForm = $this->createDeleteForm($individu);

        return $this->render('individu/show.html.twig', array(
            'individu' => $individu,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing individu entity.
     *
     * @Route("/{id}/edit", name="individu_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Individu $individu)
    {
        $deleteForm = $this->createDeleteForm($individu);
        $editForm = $this->createForm('AppBundle\Form\IndividuType', $individu);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('individu_edit', array('id' => $individu->getId()));
        }

        return $this->render('individu/edit.html.twig', array(
            'individu' => $individu,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }



    /**
     * Creates a form to delete a individu entity.
     *
     * @param Individu $individu The individu entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Individu $individu)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('individu_delete', array('id' => $individu->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    ///////////////////////////////////////////////////////////////////////////////////////

     /**
     * Modifier profil
     *
     * @Route("/{id}/modifier_profil", name="modifier_profil")
     * @Method("GET")
     */
    public function modifierProfilAction(Request $request, Individu $individu)
    {
        $individu->setAdmin(true);
        $em = $this->getDoctrine()->getManager();
        $em->persist($individu);
        $em->flush($individu);

        return $this->render('individu/ligne.html.twig', [ 'individu' => $individu ] );
    }

    /**
     * Displays a form to edit an existing individu entity.
     *
     * @Route("/{id}/modify", name="individu_modify")
     * @Method({"GET", "POST"})
     */
    public function modifyAction(Request $request, Individu $individu)
    {
        $editForm = $this->createForm('AppBundle\Form\IndividuType', $individu);

        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() /*&& $editForm->isValid()*/)
            {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('individu_gerer');
            }

        return $this->render('individu/modif.html.twig',
            [
            'individu' => $individu,
            'form' => $editForm->createView(),
            ]);
    }

    /**
     * Displays a form to edit an existing individu entity.
     *
     * @Route("/ajouter", name="individu_ajouter")
     * @Method({"GET", "POST"})
     */
    public function ajouterAction(Request $request)
    {
        $individu = new Individu();
        $editForm = $this->createForm('AppBundle\Form\IndividuType', $individu);

        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() /*&& $editForm->isValid()*/)
            {
            $individu->setCreationStamp( new \DateTime() );
            $em = AppBundle::getManager();
            $em->persist($individu);
            $em->flush();

            return $this->redirectToRoute('individu_gerer');
            }

        return $this->render('individu/modif.html.twig',
            [
            'individu' => $individu,
            'form' => $editForm->createView(),
            ]);
    }
    /**
     * Devenir Admin
     *
     * @Route("/{id}/devenir_admin", name="devenir_admin")
     * @Method("GET")
     */
    public function devenirAdminAction(Request $request, Individu $individu)
    {
        $individu->setAdmin(true);
        $individu->setObs(false);    // Pas la peine d'être Observateur si on est admin !

        $em = $this->getDoctrine()->getManager();
        $em->persist($individu);
        $em->flush($individu);

        if ($request->isXmlHttpRequest())
            return $this->render('individu/ligne.html.twig', [ 'individu' => $individu ] );
        else
            return $this->redirectToRoute('individu_gerer');
    }

    /**
     * Cesser d'être Admin
     *
     * @Route("/{id}/plus_admin", name="plus_admin")
     * @Method("GET")
     */
    public function plusAdminAction(Request $request, Individu $individu)
    {
        $individu->setAdmin(false);
        $em = $this->getDoctrine()->getManager();
        $em->persist($individu);
        $em->flush($individu);

        if ($request->isXmlHttpRequest())
            return $this->render('individu/ligne.html.twig', [ 'individu' => $individu ] );
        else
           return $this->redirectToRoute('individu_gerer');
    }

     /**
     * Devenir Obs
     *
     * @Route("/{id}/devenir_obs", name="devenir_obs")
     * @Method("GET")
     */
    public function devenirObsAction(Request $request, Individu $individu)
    {
        $individu->setObs(true);
        $individu->setAdmin(false); // Si on devient Observateur on n'est plus admin !
        $em = $this->getDoctrine()->getManager();
        $em->persist($individu);
        $em->flush($individu);

        if ($request->isXmlHttpRequest())
            return $this->render('individu/ligne.html.twig', [ 'individu' => $individu ] );
        else
            return $this->redirectToRoute('individu_gerer');
    }

    /**
     * Cesser d'être Obs
     *
     * @Route("/{id}/plus_obs", name="plus_obs")
     * @Method("GET")
     */
    public function plusObsAction(Request $request, Individu $individu)
    {
        $individu->setObs(false);
        $em = $this->getDoctrine()->getManager();
        $em->persist($individu);
        $em->flush($individu);

        if ($request->isXmlHttpRequest())
            return $this->render('individu/ligne.html.twig', [ 'individu' => $individu ] );
        else
           return $this->redirectToRoute('individu_gerer');
    }

     /**
     * Devenir Sysadmin
     *
     * @Route("/{id}/devenir_sysadmin", name="devenir_sysadmin")
     * @Method("GET")
     */
    public function devenirSysadminAction(Request $request, Individu $individu)
    {
        $individu->setSysadmin(true);
        $em = $this->getDoctrine()->getManager();
        $em->persist($individu);
        $em->flush($individu);

        if ($request->isXmlHttpRequest())
            return $this->render('individu/ligne.html.twig', [ 'individu' => $individu ] );
        else
            return $this->redirectToRoute('individu_gerer');
    }

    /**
     * Cesser d'être Sysadmin
     *
     * @Route("/{id}/plus_sysadmin", name="plus_sysadmin")
     * @Method("GET")
     */
    public function plusSysadminAction(Request $request, Individu $individu)
    {
        $individu->setSysadmin(false);
        $em = $this->getDoctrine()->getManager();
        $em->persist($individu);
        $em->flush($individu);

        if ($request->isXmlHttpRequest())
            return $this->render('individu/ligne.html.twig', [ 'individu' => $individu ] );
        else
           return $this->redirectToRoute('individu_gerer');
    }

    /**
     * Devenir President
     *
     * @Route("/{id}/devenir_president", name="devenir_president")
     * @Method("GET")
     */
    public function devenirPresidentAction(Request $request, Individu $individu)
    {
        $individu->setPresident(true);
        $em = $this->getDoctrine()->getManager();
        $em->persist($individu);
        $em->flush($individu);

        if ($request->isXmlHttpRequest())
            return $this->render('individu/ligne.html.twig', [ 'individu' => $individu ] );
        else
            return $this->redirectToRoute('individu_gerer');
    }

    /**
     * Cesser d'être President
     *
     * @Route("/{id}/plus_president", name="plus_president")
     * @Method("GET")
     */
    public function plusPresidentAction(Request $request, Individu $individu)
    {
        $individu->setPresident(false);
        $em = $this->getDoctrine()->getManager();
        $em->persist($individu);
        $em->flush($individu);

        if ($request->isXmlHttpRequest())
            return $this->render('individu/ligne.html.twig', [ 'individu' => $individu ] );
        else
           return $this->redirectToRoute('individu_gerer');
    }

    /**
     * Devenir Expert
     *
     * @Route("/{id}/devenir_expert", name="devenir_expert")
     * @Method("GET")
     */
    public function devenirExpertAction(Request $request, Individu $individu)
    {
        $individu->setExpert(true);
        $em = $this->getDoctrine()->getManager();
        $em->persist($individu);
        $em->flush($individu);

        if ($request->isXmlHttpRequest())
            return $this->render('individu/ligne.html.twig', [ 'individu' => $individu ] );
        else
            return $this->redirectToRoute('individu_gerer');
    }

    /**
     * Cesser d'être Expert
     *
     * @Route("/{id}/plus_expert", name="plus_expert")
     * @Method("GET")
     */
    public function plusExpertAction(Request $request, Individu $individu)
    {
        $individu->setExpert(false);
        $em = $this->getDoctrine()->getManager();
        $em->persist($individu);

        Functions::noThematique($individu);

        $em->flush();

        return $this->render('individu/ligne.html.twig', [ 'individu' => $individu ] );
    }


     /**
     * Activer
     *
     * @Route("/{id}/activer", name="activer_utilisateur")
     * @Method("GET")
     */
    public function activerAction(Request $request, Individu $individu)
    {
        $individu->setDesactive(false);
        $em = $this->getDoctrine()->getManager();
        $em->persist($individu);
        $em->flush($individu);

        if ($request->isXmlHttpRequest())
            return $this->render('individu/ligne.html.twig', [ 'individu' => $individu ] );
        else
            return $this->redirectToRoute('individu_gerer');
    }

    /**
     * Desactiver utilisateur
     *
     * @Route("/{id}/desactiver", name="desactiver_utilisateur")
     * @Method("GET")
     */
    public function desactiverAction(Request $request, Individu $individu)
    {
        $em = $this->getDoctrine()->getManager();

        $individu->setDesactive(true);

        $ssos = $individu->getSso();
        foreach( $ssos as $sso ) $em->remove($sso);

        $em->persist($individu);
        $em->flush($individu);

        if ($request->isXmlHttpRequest())
            return $this->render('individu/ligne.html.twig', [ 'individu' => $individu ] );
        else
            return $this->redirectToRoute('individu_gerer');

    }

    /**
     * SUDO
     *
     * @Route("/{id}/sudo", name="sudo")
     * @Method("GET")
     */
    public function sudoAction(Request $request, Individu $individu)
    {
    if( ! AppBundle::isGranted('ROLE_PREVIOUS_ADMIN') )
            {
            Functions::infoMessage("Controller : connexion de l'utilisateur " . $individu . ' en SUDO ');
            return new RedirectResponse($this->generateUrl('accueil',[ '_switch_user' => $individu->getId() ]) );
            }
        else
            {
            Functions::warningMessage("Controller : connexion de l'utilisateur " . $individu . ' déjà en SUDO !');
            return $this->redirectToRoute('individu_gerer');
            }
    }

    /**
     * thematiques
     *
     * @Route("/{id}/thematique", name="choisir_thematique")
     * @Method({"GET", "POST"})
     */
    public function thematiqueAction(Request $request, Individu $individu)
    {
     $form = $this->createFormBuilder($individu)
            ->add('thematique', EntityType::class,
                [
                'multiple' => true,
                'expanded' => true,
                'class' => 'AppBundle:Thematique',
                ])
            ->add('submit',SubmitType::class, ['label' => 'modifier' ])
            ->add('reset',ResetType::class, ['label' => 'reset' ])
            ->getForm();

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid())
        {
            // thématiques && Doctrine ManyToMany
            $all_thematiques = AppBundle::getRepository(Thematique::class)->findAll();
            $my_thematiques = $individu->getThematique();

            foreach($all_thematiques as $thematique )
                {
                if( $my_thematiques->contains( $thematique ) )
                    $thematique->addExpert($individu);
                else
                    $thematique->removeExpert($individu);
                }
            AppBundle::getManager()->flush();

            return $this->redirectToRoute('individu_gerer');
        }

    return $this->render('individu/thematique.html.twig',
            [
            'individu' => $individu,
            'form' => $form->createView(),
            ]);
    }

    /**
     * Autocomplete mail
     *
     * @Route("/mail_autocomplete", name="mail_autocomplete")
     * @Security("has_role('ROLE_DEMANDEUR')")
     * @Method({"POST","GET"})
     */
    public function mailAutocompleteAction(Request $request)
    {
        $form = $this
            ->get('form.factory')
            ->createNamedBuilder('autocomplete_form', FormType::class, [])
            ->add('mail', TextType::class, [ 'required' => true, 'csrf_protection' => false] )
            ->getForm();

        $form->handleRequest($request);

        if ( $form->isSubmitted() ) // nous ne pouvons pas ajouter $form->isValid() et nous ne savons pas pourquoi
            {
             if ( array_key_exists('mail',$form->getData() ) )
                $data   =   AppBundle::getRepository(Individu::class)->liste_mail_like( $form->getData()['mail'] );
            else
                $data  =   [ ['mail' => 'Problème avec AJAX dans IndividuController:mailAutocompleteAction' ] ];

            $output = [];
            foreach( $data as $item )
                if( array_key_exists('mail', $item ))
                    $output[]   =   $item['mail'];

            $response = new Response(json_encode( $output ) );
            $response->headers->set('Content-Type', 'application/json');
            return $response;
            }

        // on complète le reste des informations

        $collaborateur    = new \AppBundle\Utils\IndividuForm();
        $form = $this->createForm('AppBundle\Form\IndividuFormType', $collaborateur, ['csrf_protection' => false]);
        $form->handleRequest($request);

        if (  $form->isSubmitted()  && $form->isValid() )
            {
            $individu = AppBundle::getRepository(Individu::class)->findOneBy(['mail' => $collaborateur->getMail() ]);
            //$individu = new Individu();
            if( $individu != null )
                {
                if($individu->getMail() != null     )   $collaborateur->setMail         (   $individu->getMail()  );
                if($individu->getPrenom() != null   )   $collaborateur->setPrenom       (   $individu->getPrenom()  );
                if($individu->getNom()    != null   )   $collaborateur->setNom          (   $individu->getNom()     );
                if($individu->getStatut() != null   )   $collaborateur->setStatut       (   $individu->getStatut()  );
                if($individu->getLabo()   != null   )   $collaborateur->setLaboratoire  (   $individu->getLabo()    );
                if($individu->getEtab()   != null   )   $collaborateur->setEtablissement(   $individu->getEtab()    );
                if($individu->getId()     != null   )   $collaborateur->setId           (   $individu->getId()    );
                $form = $this->createForm('AppBundle\Form\IndividuFormType', $collaborateur, ['csrf_protection' => false]);

                return $this->render('version/collaborateurs_ligne.html.twig', [ 'form' => $form->createView() ] );
                }
            else
                return  new Response('reallynouserrrrrrrr');
            }
        //return new Response( 'no form submitted' );
        return new Response( json_encode('no form submitted') );
    }


    /**
     * Lists all individu entities.
     *
     * @Route("/gerer", name="individu_gerer")
     * @Route("/liste")
     * @Method({"GET","POST"})
     */
    public function gererAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $form = AppBundle::getFormBuilder('tri', GererUtilisateurType::class, [] )->getForm();
        $form->handleRequest($request);

        if ( $form->isSubmitted() && $form->isValid() )
            {
            if( $form->getData()['all'] == true )
               $users = $em->getRepository(Individu::class)->findAll();
            else
               $users = $em->getRepository(Individu::class)->getActiveUsers();

            $pattern = '/' . $form->getData()['filtre'] . '/';

            $individus = [];
            foreach( $users as $individu )
                if( preg_match ( $pattern, $individu->getMail() ) )
                    $individus[] = $individu;
            }
        else
            $individus = $em->getRepository(Individu::class)->getActiveUsers();

        // statistiques
        $total = AppBundle::getRepository(Individu::class)->countAll();
        $actifs = 0;
        $idps = [];
        foreach( $individus as $individu )
		{
			$individu_ssos = $individu->getSso()->toArray();
			if( count( $individu_ssos ) > 0 && $individu->getDesactive() == false ) $actifs++;

			$idps = array_merge( $idps,
				array_map(
					function($value)
					{
						$str = $value->__toString();
						preg_match ( '/^(.+)(@.+)$/', $str, $matches );
						if( array_key_exists ( 2, $matches ) )
							return $matches[2];
						else
							return '@';
					},
					$individu_ssos ) );
		}
        $idps = array_count_values ( $idps );

        return $this->render('individu/liste.html.twig',
            [
            'idps'  => $idps,
            'total' => $total,
            'actifs' => $actifs,
            'form'  => $form->createView(),
            'individus' => $individus,
            ]);
    }

    private static function sso_to_string($sso,$key){ return $sso->__toString(); }
}
