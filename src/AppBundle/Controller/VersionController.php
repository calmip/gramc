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

use AppBundle\Entity\Version;
use AppBundle\Entity\Projet;
use AppBundle\Entity\Session;
use AppBundle\Entity\Individu;
use AppBundle\Entity\CollaborateurVersion;
use AppBundle\Entity\RapportActivite;

use AppBundle\Workflow\Projet\ProjetWorkflow;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\File;

use AppBundle\AppBundle;
use AppBundle\Utils\Functions;
use AppBundle\Utils\Etat;
use AppBundle\Utils\Signal;
use AppBundle\Utils\GramcDate;
use AppBundle\Utils\Menu;
use AppBundle\Utils\IndividuForm;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use AppBundle\Form\IndividuFormType;
use AppBundle\Validator\Constraints\PagesNumber;


/**
 * Version controller.
 *
 * @Security("has_role('ROLE_ADMIN')")
 * @Route("version")
 */
class VersionController extends Controller
{
    /**
     * Lists all version entities.
     *
     * @Route("/", name="version_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $versions = $em->getRepository('AppBundle:Version')->findAll();

        return $this->render('version/index.html.twig', array(
            'versions' => $versions,
        ));
    }

    /**
     * Creates a new version entity.
     *
     * @Route("/new", name="version_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $version = new Version();
        $form = $this->createForm('AppBundle\Form\VersionType', $version);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($version);
            $em->flush($version);

            return $this->redirectToRoute('version_show', array('id' => $version->getId()));
        }

        return $this->render('version/new.html.twig', array(
            'version' => $version,
            'form' => $form->createView(),
        ));
    }

    /**
     * Supprimer version
     *
     * @Route("/{id}/avant_supprimer/{rtn}", name="version_avant_supprimer", defaults= {"rtn" = "X" })
     * @Security("has_role('ROLE_DEMANDEUR')")
     * @Method("GET")
     *
     */
    public function avantSupprimerAction(Version $version, $rtn)
    {
    // ACL
    if( Menu::modifier_version($version)['ok'] == false )
        Functions::createException(__METHOD__ . ':' . __LINE__ . " impossible de supprimer la version " . $version->getIdVersion().
            " parce que : " . Menu::modifier_version($version)['raison'] );

    return $this->render('version/avant_supprimer.html.twig',
            [
            'version' => $version,
            'rtn'   => $rtn,
            ]
            );
    }


    /**
     * Supprimer version
     *
     * @Route("/{id}/supprimer/{rtn}", defaults= {"rtn" = "X" }, name="version_supprimer" )
     * @Security("has_role('ROLE_DEMANDEUR')")
     * @Method("GET")
     *
     */
    public function supprimerAction(Version $version, $rtn )
    {
	    // ACL
	    if( Menu::modifier_version($version)['ok'] == false )
	        Functions::createException(__METHOD__ . ':' . __LINE__ . " impossible de supprimer la version " . $version->getIdVersion().
	            " parce que : " . Menu::modifier_version($version)['raison'] );

	    $em =   AppBundle::getManager();
	    $etat = $version->getEtatVersion();
	    if( $version->getProjet() == null )
        {
	        $idProjet = null;
	        $idVersion == $version->getIdVersion();
        }
	    else
	    {
	        $idProjet   =  $version->getProjet()->getIdProjet();
		}


	    if( $etat == Etat::EDITION_DEMANDE || $etat == Etat::EDITION_TEST )
        {
			// Suppression des collaborateurs
	        foreach( $version->getCollaborateurVersion() as $collaborateurVersion )
	            $em->remove( $collaborateurVersion );

			// Suppression des expertises éventuelles
	        $expertises = $version->getExpertise();
	        foreach( $expertises as $expertise)
	            $em->remove( $expertise );

	        $em->remove( $version );
	        $em->flush();
        }

	    if( $idProjet == null )
	        Functions::warningMessage(__METHOD__ . ':' . __LINE__ . " version " . $idVersion . " sans projet supprimée");
	    else
        {
	        $projet =   AppBundle::getRepository(Projet::class)->findOneBy(['idProjet' => $idProjet]);
	        
	        // Si pas d'autre version, on supprime le projet
	        if( $projet != null && $projet->getVersion() != null && count( $projet->getVersion() ) == 0 )
            {
	            $em->remove( $projet );
	            $em->flush();
            }
	        elseif( $projet != null )
	        {
	            $projet->calculDerniereVersion();
			}
        }

	    //return $this->redirectToRoute( 'projet_accueil' );
	    // Il faudrait plutôt revenir là d'où on vient !
	    if( $rtn == "X" )
	        return $this->redirectToRoute( 'projet_accueil' );
	    else
	        return $this->redirectToRoute( $rtn );
    }

    //////////////////////////////////////////////////////////////////////////

    /**
     * Finds and displays a version entity.
     *
     * @Route("/{id}/show", name="version_show")
     * @Method("GET")
     */
    public function showAction(Version $version)
    {
        $deleteForm = $this->createDeleteForm($version);

        return $this->render('version/show.html.twig', array(
            'version' => $version,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Affiche au format pdf
     *
     * @Route("/{id}/pdf", name="version_pdf")
     * @Security("has_role('ROLE_DEMANDEUR')")
     * @Method("GET")
     */
    public function pdfAction(Version $version, Request $request)
    {
	    $projet =  $version->getProjet();
	    if( ! Functions::projetACL( $projet ) )
	            Functions::createException(__METHOD__ . ':' . __LINE__ .' problème avec ACL');

	    $session = $version->getSession();

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


	    $html4pdf =  $this->render('version/pdf.html.twig',
	            [
	            'toomuch' => Functions::is_demande_toomuch($version->getAttrHeures(),$version->getDemHeures()),
	            'projet' => $projet,
	            'version'   =>  $version,
	            'session'   =>  $session,
	            'img_expose_1'  =>  $img_expose_1,
	            'img_expose_2'  =>  $img_expose_2,
	            'img_expose_3'  =>  $img_expose_3,
	            'img_justif_renou_1'    =>  $img_justif_renou_1,
	            'img_justif_renou_2'    =>  $img_justif_renou_2,
	            'img_justif_renou_3'    =>  $img_justif_renou_3,
	            ]
	            );
	    //return $html4pdf;
	    //$html4pdf->prepare($request);
	    //$pdf = AppBundle::getPDF($html4pdf);
	    $pdf = AppBundle::getPDF($html4pdf->getContent());

	    return Functions::pdf( $pdf );
    }

    /**
     * Finds and displays a version entity.
     *
     * @Route("/{id}/fiche_pdf", name="version_fiche_pdf")
     * @Security("has_role('ROLE_DEMANDEUR')")
     * @Method("GET")
     */
    public function fichePdfAction(Version $version, Request $request)
    {
    $projet =  $version->getProjet();

    // ACL
    if( Menu::telechargement_fiche($version)['ok'] == false )
        Functions::createException(__METHOD__ . ':' . __LINE__ . " impossible de télécharger la fiche du projet " . $projet .
            " parce que : " . Menu::telechargement_fiche($version)['raison'] );

    $session = $version->getSession();

    $html4pdf =  $this->render('version/fiche_pdf.html.twig',
            [
            'projet' => $projet,
            'version'   =>  $version,
            'session'   =>  $session,
            ]
            );
    // return $html4pdf;
    //$html4pdf->prepare($request);
    //$pdf = AppBundle::getPDF($html4pdf);
    $pdf = AppBundle::getPDF($html4pdf->getContent());

    return Functions::pdf( $pdf );

    }


    ///////////////////////////////////////////////////////////////

    /**
     * Téléverser le rapport d'actitivé de l'année précedente
     *
     * @Route("/{id}/televersement_fiche", name="version_televersement_fiche")
     * @Method({"POST","GET"})
     * @Security("has_role('ROLE_DEMANDEUR')")
     */
    public function televersementFicheAction(Request $request, Version $version)
    {
    // ACL
    if( Menu::televersement_fiche($version)['ok'] == false )
        Functions::createException(__METHOD__ . ':' . __LINE__ . " impossible de téléverser la fiche du projet " . $projet .
            " parce que : " . Menu::telechargement_fiche($version)['raison'] );

    $format_fichier = new \Symfony\Component\Validator\Constraints\File(
                [
                'mimeTypes'=> [ 'application/pdf' ],
                'mimeTypesMessage'=>' Le fichier doit être un fichier pdf. ',
                'maxSize' => "2024k",
                'uploadIniSizeErrorMessage' => ' Le fichier doit avoir moins de {{ limit }} {{ suffix }}. ',
                'maxSizeMessage' => ' Le fichier est trop grand ({{ size }} {{ suffix }}), il doit avoir moins de {{ limit }} {{ suffix }}. ',
                ]);

     $form = $this
           ->get('form.factory')
           ->createNamedBuilder( 'upload', FormType::class, [], ['csrf_protection' => false ] )
           ->add('file', FileType::class,
                [
                'required'          =>  true,
                'label'             => "",
                'constraints'       => [$format_fichier , new PagesNumber() ]
                ])
           ->getForm();

    $erreurs = [];
    $resultat   =   [];

    $form->handleRequest( $request );
    if( $form->isSubmitted() )
        {
        $data   =   $form->getData();

        if( isset( $data['file'] ) && $data['file'] != null )
            {
            $tempFilename = $data['file'];
            if( ! empty( $tempFilename  ) && $tempFilename != "" )
                {
                $validator = AppBundle::getContainer()->get('validator');
                $violations = $validator->validate( $tempFilename, [ $format_fichier, new PagesNumber() ] );
                foreach( $violations as $violation )    $erreurs[]  =   $violation->getMessage();
                }
            }
        else
            $tempFilename = null;


        if( is_file( $tempFilename ) && ! is_dir( $tempFilename ) )
                $file = new File( $tempFilename );
            elseif( is_dir( $tempFilename ) )
                {
                Functions::errorMessage(__METHOD__ .":" . __LINE__ . " Le nom  " . $tempFilename . " correspond à un répertoire");
                $erreurs[]  =  " Le nom  " . $tempFilename . " correspond à un répertoire";
                }
            else
                {
                Functions::errorMessage(__METHOD__ .":" . __LINE__ . " Le fichier " . $tempFilename . " n'existe pas" );
                $erreurs[]  =  " Le fichier " . $tempFilename . " n'existe pas";
                }

        if( $form->isValid() && $erreurs == [] )
            {
            $session = $version->getSession();
            $projet = $version->getProjet();
            if( $projet != null && $session != null )
                {
                $filename = AppBundle::getParameter('signature_directory') .'/'.$session->getIdSession() .
                                "/" . $session->getIdSession() . $projet->getIdProjet() . ".pdf";
                $file->move( AppBundle::getParameter('signature_directory') .'/'.$session->getIdSession(),
                                 $session->getIdSession() . $projet->getIdProjet() . ".pdf" );

                // on marque le téléversement de la fiche projet
                $version->setPrjFicheVal(true);
                AppBundle::getManager()->flush();
                $resultat[] =   " La fiche du projet " . $projet . " pour la session " . $session . " téléversé ";
                }
            else
                {
                $resultat[] =   " La fiche du projet n'a pas été téléversé";
                if( $projet == null )
                    Functions::errorMessage( __METHOD__ . ':'. __LINE__ . " version " . $version . " n'a pas de projet");
                if( $session == null )
                    Functions::errorMessage( __METHOD__ . ':' . __LINE__ . " version " . $version . " n'a pas de session");
                }
            }

        }

    return $this->render('version/televersement_fiche.html.twig',
            [
            'version'       =>  $version,
            'form'          =>  $form->createView(),
            'erreurs'       =>  $erreurs,
            'resultat'      =>  $resultat,
            ]);

    }



    /**
     * Displays a form to edit an existing version entity.
     *
     * @Route("/{id}/edit", name="version_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Version $version)
    {
        $deleteForm = $this->createDeleteForm($version);
        $editForm = $this->createForm('AppBundle\Form\VersionType', $version);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('version_edit', array('id' => $version->getId()));
        }

        return $this->render('version/edit.html.twig', array(
            'version' => $version,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a version entity.
     *
     * @Route("/{id}", name="version_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Version $version)
    {
        $form = $this->createDeleteForm($version);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($version);
            $em->flush($version);
        }

        return $this->redirectToRoute('version_index');
    }

    /**
     * Creates a form to delete a version entity.
     *
     * @param Version $version The version entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Version $version)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('version_delete', array('id' => $version->getIdVersion())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }


    /**
     * Changer le responsable d'une version.
     *
     * @Route("/{id}/responsable", name="changer_responsable")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_DEMANDEUR')")
     */
    public function changerResponsableAction(Version $version, Request $request)
    {

	// Si changement d'état de la session alors que je suis connecté !
	AppBundle::getSession()->remove('SessionCourante'); // remove cache

    // ACL
    $moi = AppBundle::getUser();

    if( $version == null )
        Functions::createException(__METHOD__ .":". __LINE__ .' version null');

     if( Menu::changer_responsable($version)['ok'] == false )
            Functions::createException(__METHOD__ . ":" . __LINE__ .
                " impossible de changer de responsable parce que " . Menu::changer_responsable($version)['raison'] );

    // préparation de la liste des responsables potentiels
    $collaborateurs = $version->getCollaborateurs( false, true ); // pas moi, seulement éligibles

     $change_form = AppBundle::createFormBuilder()
            ->add('responsable',   EntityType::class,
                    [
                    'multiple' => false,
                    'class' => 'AppBundle:Individu',
                    'required'  =>  true,
                    'label'     => '',
                    'choices' =>  $collaborateurs,
                    ])
        ->add('submit', SubmitType::class, ['label' => 'Nouveau responsable'])
        ->getForm();

        $change_form->handleRequest($request);

        $projet =  $version->getProjet();

        if( $projet != null )
            $idProjet   =   $projet->getIdProjet();
        else
            {
            Functions::errorMessage(__METHOD__ .":". __LINE__ . " projet null pour version " . $version->getIdVersion());
            $idProjet   =   null;
            }

        if ( $change_form->isSubmitted() && $change_form->isValid() )
            {
            $moi = AppBundle::getUser();
            //if( $moi == null || $version == null || ! in_array( $moi, $version->getResponsables() ) )
            //  Functions::createException(__METHOD__ .":". __LINE__ . 'VersionController:changerResponsable : Seul le responsable du projet peut passer la main');

            $nouveau_responsable = $change_form->getData()['responsable'];
            $version->changerResponsable( $moi, $nouveau_responsable );
            return $this->redirectToRoute('consulter_version',
                [
                'version' => $version->getIdVersion(),
                'id'    =>  $idProjet,
                ]
                );
            }

     return $this->render('version/responsable.html.twig',
            [
            'projet' => $idProjet,
            'change_form'   => $change_form->createView(),
            'version'   =>  $version,
            'session'   =>  $version->getSession(),
            ]
            );

    }


    /**
     * Mettre une pénalité sur une version (en GET par aajx)
     *
     * @Route("/{id}/version/{penal}/penalite", name="penal_version")
     * @Method({"GET"})
     */
    public function penalAction(Version $idversion, $penal)
    {
        $data = [];
        $em = $this->getDoctrine()->getManager();
        $version = $em->getRepository('AppBundle:Version')->findOneBy( [ 'idVersion' =>  $idversion] );
        if ($version != null) {
            if ($penal >= 0)
            {
                $data['recuperable'] = 0;
                $data['penalite' ] = $penal;
                $version ->setPenalHeures($penal);
            }
            else
            {
                $data['penalite'] = 0;
                $data['recuperable' ] = -$penal;
                $version ->setPenalHeures(0);
            }
            $em->persist($version);
            $em->flush($version);
        }
        return new Response(json_encode( $data ));
    }

    /**
     * Ecran affiché dans le cas où la demande est incomplète
     *
     * @Route("/{id}/avant_modifier", name="version_avant_modifier")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_DEMANDEUR')")
     */
    public function avant_modifierAction(Request $request, Version $version )
    {
	    // ACL
	    if( Menu::modifier_version($version)['ok'] == false )
	        Functions::createException(__METHOD__ . ":" . __LINE__ . " impossible de modifier la version " . $version->getIdVersion().
	            " parce que : " . Menu::modifier_version($version)['raison'] );
	
	    return $this->render('version/avant_modifier.html.twig',
		[
			'version'   => $version
		]);
    }


	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Téléversements génériques de rapport d'activité ou de fiche projet
     *
     * @Route("/televersement", name="televersement_generique")
     * @Method({"POST","GET"})
     * @Security("has_role('ROLE_ADMIN')")
     */

    public function televersementGeneriqueAction(Request $request)
    {
	    $format_fichier = new \Symfony\Component\Validator\Constraints\File(
		[
			'mimeTypes'=> [ 'application/pdf' ],
			'mimeTypesMessage'=>' Le fichier doit être un fichier pdf. ',
			'maxSize' => "2024k",
			'uploadIniSizeErrorMessage' => ' Le fichier doit avoir moins de {{ limit }} {{ suffix }}. ',
			'maxSizeMessage' => ' Le fichier est trop grand ({{ size }} {{ suffix }}), il doit avoir moins de {{ limit }} {{ suffix }}. ',
		]);

		$def_annee = GramcDate::get()->format('Y');
		$def_sess  = Functions::getSessionCourante()->getIdSession();

        $form = $this
		   ->get('form.factory')
		   ->createNamedBuilder( 'upload', FormType::class, [], ['csrf_protection' => false ] )
		   ->add('projet',  TextType::class, [ 'label'=> "", 'required' => false, 'attr' => ['placeholder' => 'P12345']] )
		   ->add('session', TextType::class, [ 'label'=> "", 'required' => false, 'attr' => ['placeholder' => $def_sess]] )
		   ->add('annee',   TextType::class, [ 'label'=> "", 'required' => false, 'attr' => ['placeholder' => $def_annee]] )
		   ->add('type',  ChoiceType::class,
			[
				'required' => true,
				'choices'  => [
								"Rapport d'activité" => "r",
								"Fiche projet"       => "f",
							  ],
				'label'    => "",
			])
	       ->add('file', FileType::class,
			[
				'required'    =>  true,
				'label'       => "",
				'constraints' => [$format_fichier , new PagesNumber() ]
			])
		   ->getForm();

		$erreurs  = [];
	    $resultat = [];
	    
  	    $form->handleRequest( $request );
	    if( $form->isSubmitted() )
        {
	        $data   =   $form->getData();
	
	        if( isset( $data['projet'] ) && $data['projet'] != null )
            {
	            $projet = AppBundle::getRepository(Projet::class)->find( $data['projet'] );
	            if( $projet == null ) $erreurs[]  =   "Le projet " . $data['projet'] . " n'existe pas";
            }
	        else
	        {
	            $projet = null;
			}

	        if( isset( $data['session'] ) && $data['session'] != null )
            {
	            $session = AppBundle::getRepository(Session::class)->find(  $data['session'] );
	            if( $session == null )
	                $erreurs[] = "La session " . $data['session'] . " n'existe pas";
            }
	        else
	            $session = Functions::getSessionCourante();

	        if( isset( $data['annee'] ) && $data['annee'] != null )
	            $annee = $data['annee'];
	        else
	            $annee = $session->getAnneeSession() + 2000;

	        if( isset( $data['file'] ) && $data['file'] != null )
            {
	            $tempFilename = $data['file'];
	            if( ! empty( $tempFilename  ) && $tempFilename != "" )
                {
	                $validator = AppBundle::getContainer()->get('validator');
	                $violations = $validator->validate( $tempFilename, [ $format_fichier, new PagesNumber() ] );
	                foreach( $violations as $violation )    $erreurs[]  =   $violation->getMessage();
                }
            }
	        else
	            $tempFilename = null;

	        $type = $data['type'];
	
	        if( $annee == null && $type != "f" )    $erreurs[] =  "L'année doit être donnée pour un rapport d'activité";
	        if( $projet == null )                   $erreurs[] =  "Le projet doit être donné";
	        if( $session == null && $type == "f" )  $erreurs[] =  "La session doit être donnée pour une fiche projet";
	
	        Functions::createDirectories( $annee, $session );

	        if( is_file( $tempFilename ) && ! is_dir( $tempFilename ) )
                $file = new File( $tempFilename );
            elseif( is_dir( $tempFilename ) )
			{
                Functions::errorMessage(__METHOD__ .":" . __LINE__ . " Le nom  " . $tempFilename . " correspond à un répertoire");
                $erreurs[]  =  " Le nom  " . $tempFilename . " correspond à un répertoire";
			}
            else
			{
                Functions::errorMessage(__METHOD__ .":" . __LINE__ . " Le fichier " . $tempFilename . " n'existe pas" );
                $erreurs[]  =  " Le fichier " . $tempFilename . " n'existe pas";
			}

			if( $form->isValid() && $erreurs == [] )
            {
	            if( $type == "f" )
				{
	                $filename = AppBundle::getParameter('signature_directory') .'/'.$session->getIdSession() .
	                                "/" . $session->getIdSession() . $projet->getIdProjet() . ".pdf";
	                $file->move( AppBundle::getParameter('signature_directory') .'/'.$session->getIdSession(),
	                                 $session->getIdSession() . $projet->getIdProjet() . ".pdf" );
	
	                // on marque le téléversement de la fiche projet
	                $version = AppBundle::getRepository(Version::class)->findOneBy( ['projet' => $projet, 'session' => $session ] );
	                if( $version != null )
					{
	                    $version->setPrjFicheVal(true);
	                    AppBundle::getManager()->flush();
					}
	                else
	                    Functions::errorMessage(__METHOD__ . ':' . __LINE__ . " Il n'y a pas de version du projet " . $projet . " pour la session " . $session );
	
	                $resultat[] =   " Fichier " . $filename . " téléversé";
				}
	            elseif( $type = "r" )
				{
	                $filename = AppBundle::getParameter('rapport_directory') .'/'.$annee .
	                                "/" . $annee . $projet->getIdProjet() . ".pdf";
	                $file->move( AppBundle::getParameter('rapport_directory') .'/'.$annee,
	                                 $annee . $projet->getIdProjet() . ".pdf" );
	                $resultat[] =   " Fichier " . $filename . " téléversé";
	                static::modifyRapport( $projet, $annee, $filename, false );
				}
			}
		}

	    $form1 = $this
			->get('form.factory')
			->createBuilder()
			->add('version', TextType::class, [
					'label' => "Numéro de version",'required' => true, 'attr' => ['placeholder' => $def_sess.'P12345']])
			->add('attrHeures', IntegerType::class, [
					'label' => 'Attribution', 'required' => true, 'attr' => ['placeholder' => '100000']])
			->add('attrHeuresEte', IntegerType::class, [
					'label' => 'Attribution', 'required' => false, 'attr' => ['placeholder' => '10000']])
			->getForm();

		$erreurs1 = [];
  	    $form1->handleRequest( $request );
	    if( $form1->isSubmitted() )
        {
			$data    = $form1->getData();
	        if( isset( $data['version'] ) && $data['version'] != null )
            {
	            $version = AppBundle::getRepository(Version::class)->find( $data['version'] );
	            if( $version == null ) $erreurs1[]  =   "La version " . $data['version'] . " n'existe pas";
            }
	        else
	        {
	            $version     = null;
	            $erreurs1[]  = "Pas de version spécifiée";
			}
			if ($version != null)
			{
				$etat = $version -> getEtatVersion();
				if ($etat != Etat::ACTIF && $etat != Etat::EN_ATTENTE)
				{
					$libelle = Etat::LIBELLE_ETAT[$etat];
					$erreurs1[] = "La version ".$version->getIdVersion()." est en état $libelle, pas possible de changer son attribution !";
				}
			}
			
			$attrHeures = $data['attrHeures'];
			if ($attrHeures<0)
			{
				$erreurs1[] = "$attrHeures ne peut être une attribution";
			}
			if (isset($data['attrHeuresEte']) && $data['attrHeuresEte'] != null)
			{
				$attrHeuresEte = $data["attrHeuresEte"];
				if ($attrHeuresEte<0)
				{
					$erreurs1[] = "$attrHeuresEte ne peut être une attribution, même pour un été torride";
				}
			}
			else
			{
				$attrHeuresEte = -1;
			}
			
			if (count($erreurs1) == 0)
			{
				$version->setAttrHeures($attrHeures);
				if ($attrHeuresEte>=0)
				{
					$version->setAttrHeuresEte($attrHeuresEte);
				}
		        $em = $this->getDoctrine()->getManager();
		        $em->persist($version);
	            $em->flush();
			}
		}

	    return $this->render('version/televersement_generique.html.twig',
		[
			'form'     => $form->createView(),
			'erreurs'  => $erreurs,
			'form1'    => $form1->createView(),
			'erreurs1' => $erreurs1,
			'def_annee' => $def_annee,
			'def_sess'  => $def_sess,
			'resultat' => $resultat,
		]);

	}

    ///////////////////////////////////////////////////////////////


    /**
     * Téléverser le rapport d'actitivé de l'année précedente
     *
     * @Route("/{id}/rapport", name="televerser_rapport")
     * @Method("POST")
     * @Security("has_role('ROLE_DEMANDEUR')")
     */
    public function televerserRapportPrecedentAction(Version $version, Request $request)
    {
    // ACL
    //Functions::debugMessage(__METHOD__ . ':' . __LINE__ . " form data = " . Functions::show( $request->request->get('rapport') ) );
    if( Menu::televerser_rapport_annee($version->versionPrecedente())['ok'] == false )
        {
        Functions::warningMessage(__METHOD__ . ":" . __LINE__ .
                " impossible de téléverser le rapport parce que " . Menu::televerser_rapport_annee($version->versionPrecedente())['raison'] );
        return new Response( Menu::televerser_rapport_annee($version->versionPrecedente() )['raison'] );
        }
    //Functions::debugMessage('VersionController:televerserRapportAction');
    return new Response( $this->handleRapport( $request, $version->versionPrecedente(), $version->anneeRapport() ) );
    }

    /**
     * Téléverser le rapport d'actitivé de l'année
     *
     * @Route("/{id}/rapport_annee/{annee}", defaults={"annee"=0}, name="televerser_rapport_annee")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_DEMANDEUR')")
     */
    public function televerserRapportAction(Version $version, Request $request, $annee )
    {
    // ACL
    if( Menu::televerser_rapport_annee($version)['ok'] == false )
            Functions::createException(__METHOD__ . ":" . __LINE__ .
                " impossible de téléverser le rapport parce que " . Menu::televerser_rapport_annee($version)['raison'] );
    //Functions::debugMessage('VersionController:televerserRapportActionAnnee');

    if( $annee == 0 )
        $annee  =   $version->getAnneeSession();

    $rtn = $this->handleRapport( $request, $version, $annee );

    if( $rtn == 'OK' )
        return $this->render('version/confirmation_rapport.html.twig',
            [
            'projet'    =>  $version->getProjet()->getIdProjet(),
            'version'   =>  $version->getIdVersion(),
            ]);
    elseif( is_object( $rtn ) )
        return $this->render('version/televerser_rapport.html.twig',
            [
            'projet'    =>  $version->getProjet()->getIdProjet(),
            'version'   =>  $version->getIdVersion(),
            'annee'     =>  $version->getAnneeSession(),
            'form'      =>  $rtn->createView(),
            ]);
    else
        return $this->render('version/erreur_rapport.html.twig',
            [
            'projet'    =>  $version->getProjet()->getIdProjet(),
            'version'   =>  $version->getIdVersion(),
            'annee'     =>  $version->getAnneeSession(),
            'erreur'    =>  $rtn,
            ]);

    }

    ////////////////////////////////////////////////////////////////////

    private function handleRapport(Request $request, Version $version, $annee = null )
    {
		$format_fichier = new \Symfony\Component\Validator\Constraints\File(
			[
			'mimeTypes'=> [ 'application/pdf' ],
			'mimeTypesMessage'=>' Le fichier doit être un fichier pdf. ',
			'maxSize' => "2048k",
			'uploadIniSizeErrorMessage' => ' Le fichier doit avoir moins de {{ limit }} {{ suffix }}. ',
			'maxSizeMessage' => ' Le fichier est trop grand ({{ size }} {{ suffix }}), il doit avoir moins de {{ limit }} {{ suffix }}. ',
			]);

		$form = $this
			->get('form.factory')
			->createNamedBuilder( 'rapport', FormType::class, [], ['csrf_protection' => false ] )
			->add('rapport', FileType::class,
				[
					'required'          =>  true,
					'label'             => "Rapport d'activité",
					'constraints'       => [$format_fichier , new PagesNumber() ]
                ])
			->getForm();
		//Functions::debugMessage(__METHOD__ . ':' . __LINE__ . " form data = " . Functions::show( $request->request->get('rapport') ) );

		$form->handleRequest( $request );

		if( $form->isSubmitted() && $form->isValid() )
        {
	        $tempFilename = $form->getData()['rapport'];
	        if( $annee == null)
	            $annee = $version->anneeRapport();
	
	        if( is_file( $tempFilename ) && ! is_dir( $tempFilename ) )
	            $file = new File( $tempFilename );
	        elseif( is_dir( $tempFilename ) )
	            return "Erreur interne : Le nom  " . $tempFilename . " correspond à un répertoire" ;
	        else
	            return "Erreur interne : Le fichier " . $tempFilename . " n'existe pas" ;
	
	        $dir = AppBundle::getParameter('rapport_directory') . '/' . $annee;

	        if(  ! file_exists( $dir ) )
	        {
	            mkdir( $dir );
			}
	        elseif( ! is_dir(  $dir ) )
            {
	            unlink( $dir );
	            mkdir( $dir );
            }

	        //$file->move( $dir, $version->getIdVersion() . ".pdf" );
	        //$filename = $dir . "/" . $version->getIdVersion() . ".pdf";
	        $filename = $annee . $version->getProjet()->getIdProjet() . ".pdf";
	        $file->move( $dir, $filename );
	        $filename = $dir . "/" . $filename;

	        Functions::debugMessage(__METHOD__ . ':' . __LINE__ . " Rapport d'activité de l'année " . $annee . " téléversé dans le fichier " . $filename );

	        //Functions::debugMessage(__METHOD__ . ':' . __LINE__ . " filename = " . $filename );
	        // création de la table RapportActivite
	        $rapportActivite = AppBundle::getRepository(RapportActivite::class)->findOneBy(
                [
                'projet' => $version->getProjet(),
                'annee' => $annee,
                ]);
	        if( $rapportActivite == null )
	            $rapportActivite    = new RapportActivite( $version->getProjet(), $annee);

	        $rapportActivite->setTaille( filesize( $filename ) );
	        $rapportActivite->setNomFichier($filename);
	        $rapportActivite->setFiledata("");

	        $em =   AppBundle::getManager();
	        $em->persist( $rapportActivite  );
	        $em->flush();
	
	        return 'OK';
        }
		elseif( $form->isSubmitted() && ! $form->isValid() )
        {
	        if( isset( $form->getData()['rapport'] ) )
	            return  Functions::formError( $form->getData()['rapport'], [$format_fichier , new PagesNumber() ]) ;
	        else
	            return "Le fichier n'a pas été soumis correctement";
		}
		elseif( $request->isXMLHttpRequest() )
		{
			return "Le formulaire n'a pas été soumis";
		}
	    else
	    {
	        return $form;
		}
    }

    private static function modifyRapport(Projet $projet, $annee, $filename )
    {
    // création de la table RapportActivite
        $rapportActivite = AppBundle::getRepository(RapportActivite::class)->findOneBy(
                [
                'projet' => $projet,
                'annee' => $annee,
                ]);
        if( $rapportActivite == null )
            $rapportActivite    = new RapportActivite( $projet, $annee);


        $rapportActivite->setTaille( filesize( $filename ) );
        $rapportActivite->setNomFichier($filename);
        $rapportActivite->setFiledata("");

        $em =   AppBundle::getManager();
        $em->persist( $rapportActivite  );
        $em->flush();
    }


}
