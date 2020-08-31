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
use AppBundle\Entity\CommentaireExpert;
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
use AppBundle\AffectationExperts\AffectationExperts;
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

	/**
     * Affectation des experts
     *
     * @Route("/affectation_test", name="affectation_test")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_PRESIDENT')")
     */
    
    public function affectationTestAction(Request $request)
    {
	    $session  = Functions::getSessionCourante();
	    $annee    = $session->getAnneeSession();
	    $versions =  AppBundle::getRepository(Version::class)->findAnneeTestVersions($annee);
        $etatSession = $session->getEtatSession();

		$affectationExperts = new AffectationExperts($request, $versions, $this->get('form.factory'), $this->getDoctrine());
		
		//
		// 1ere etape = Traitement des formulaires qui viennent d'être soumis
		//              On boucle sur les versions:
		//                  - Une version non sélectionnée est ignorée
		//                  - Pour chaque version sélectionnée on fait une action qui dépend du bouton qui a été cliqué
		//              Puis on redirige sur la page
		//
		$form_buttons = $affectationExperts->getFormButtons();
		$form_buttons->handleRequest($request);
		if ($form_buttons->isSubmitted())
		{
			$affectationExperts->traitementFormulaires();
			// doctrine cache les expertises précédentes du coup si on ne redirige pas
			// l'affichage ne sera pas correctement actualisé !
			// Essentiellement avec sub3 (ajout d'expertise)
			return $this->redirectToRoute('affectation_test');
		}

		// 2nde étape = Création des formulaires pour affichage et génération des données de "stats"
		$forms       = $affectationExperts->getExpertsForms();
		$stats       = $affectationExperts->getStats();
		$stats['nouveau'] = null;
		$attHeures   = $affectationExperts->getAttHeures();
		
		$titre = "Affectation des experts aux projets tests de l'année 20$annee"; 
        return $this->render('expertise/affectation.html.twig', 
            [
            'titre'         => $titre,
            'versions'      => $versions,
            'forms'         => $forms,
            'sessionForm'   => null,
            'thematiques'   => null,
            'rattachements' => null,
            'experts'       => null,
            'stats'         => $stats,
            'attHeures'     => $attHeures,
            ]);
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
	    $sessionData = Functions::selectSession($request); // formulaire
	    $session     = $sessionData['session'];
	    $versions    = AppBundle::getRepository(Version::class)->findSessionVersions($session);
        $etatSession = $session->getEtatSession();

		$affectationExperts = new AffectationExperts($request, $versions, $this->get('form.factory'), $this->getDoctrine());
		
		//
		// 1ere etape = Traitement des formulaires qui viennent d'être soumis
		//              On boucle sur les versions:
		//                  - Une version non sélectionnée est ignorée
		//                  - Pour chaque version sélectionnée on fait une action qui dépend du bouton qui a été cliqué
		//              Puis on redirige sur la page
		//
		$form_buttons = $affectationExperts->getFormButtons();
		$form_buttons->handleRequest($request);
		if ($form_buttons->isSubmitted())
		{
			$affectationExperts->traitementFormulaires();
			// doctrine cache les expertises précédentes du coup si on ne redirige pas
			// l'affichage ne sera pas correctement actualisé !
			// Essentiellement avec sub3 (ajout d'expertise)
			return $this->redirectToRoute('affectation');
		}

		// 2nde étape = Création des formulaires pour affichage et génération des données de "stats"
	    $thematiques   = $affectationExperts->getTableauThematiques();
	    $rattachements = $affectationExperts->getTableauRattachements();
	    $experts       = $affectationExperts->getTableauExperts();
		$forms         = $affectationExperts->getExpertsForms();
		$stats         = $affectationExperts->getStats();
		$attHeures     = $affectationExperts->getAttHeures($versions);
		
		$sessionForm      = $sessionData['form']->createView();
		$titre            = "Affectation des experts aux projets de la session " . $session;
        return $this->render('expertise/affectation.html.twig',
            [
            'titre'         => $titre,
            'versions'      => $versions,
            'forms'         => $forms,
            'sessionForm'   => $sessionForm,
            'thematiques'   => $thematiques,
            'rattachements' => $rattachements,
            'experts'       => $experts,
            'stats'         => $stats,
            'attHeures'     => $attHeures,
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

	// Helper function used by listeAction
	private static function exptruefirst($a,$b) { 
		if ($a['expert']==true  && $b['expert']==false) return -1;
		if ($a['projetId'] < $b['projetId']) return -1;
		return 1;
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

        $mes_thematiques     =   $moi->getThematique();
        $expertiseRepository = AppBundle::getRepository(Expertise::class);
        $session             = Functions::getSessionCourante();

	// Les expertises affectées à cet expert
        $expertises  = $expertiseRepository->findExpertisesByExpert($moi, $session);
        $my_expertises  =   [];
        foreach( $expertises as $expertise )
        {
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
							    'thematique'    => $thematique,
							    'responsable'   => $version->getResponsable(),
							    'expert'        => true,
                                                         ];
        }

		//Functions::debugMessage(__METHOD__ . " my_expertises " . Functions::show($my_expertises));
		// Functions::debugMessage(__METHOD__ . " mes_thematiques " . Functions::show($mes_thematiques). " count=" . count($mes_thematiques->ToArray()));
	
        // Les projets associés à une de mes thématiques
        $expertises_by_thematique   =   [];
        foreach( $mes_thematiques as $thematique )
        {
            // $expertises_thematique =  $expertiseRepository->findExpertisesByThematique($thematique, $session);
            $expertises_thematique =  $expertiseRepository->findExpertisesByThematiqueForAllSessions($thematique);
            //Functions::debugMessage(__METHOD__ . " expertises pour thématique ".Functions::show($thematique). '-> '.Functions::show($expertises_thematique));
            $expertises =   [];
            foreach( $expertises_thematique as $expertise )
            {

                // On n'affiche pas les expertises définitives
                if ( $expertise->getDefinitif()) continue;

                $version    =  $expertise->getVersion();

                // On  n'affiche que les expertises des versions en édition expertise
                if ($version->getEtatVersion()!=Etat::EDITION_EXPERTISE && $version->getEtatVersion()!=Etat::EXPERTISE_TEST) continue;
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
				//Functions::debugMessage(__METHOD__ . " expertise ".$expertise->getId());

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
	                $expertises[$version->getIdVersion()] = $output;
				}
            }

            $expertises_by_thematique[] = [ 'expertises' => $expertises, 'thematique' => $thematique ];
        }

        ///////////////////
        // tri des tableaux expertises_by_thematique: d'abord les expertises pour lesquelles je dois intervenir
		foreach( $expertises_by_thematique as &$exp_thema )
		{
			uasort($exp_thema['expertises'],"self::exptruefirst");
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
        $rallonges     = [];
        $all_rallonges = AppBundle::getRepository(Rallonge::class)->findRallongesExpert($moi);
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

		// Commentaires
		// On propose aux experts du comité d'attribution (c-a-d ceux qui ont une thématique) d'entrer un commentaire sur l'année écoulée
		$mes_commentaires_flag = false;
		$mes_commentaires_maj        = null;
		if (AppBundle::hasParameter('commentaires_experts_d') && count($mes_thematiques)>0)
		{
			$mes_commentaires_flag = true;
			$mois = GramcDate::get()->format('m');
			$annee= GramcDate::get()->format('Y');
			if ($mois>=AppBundle::getParameter('commentaires_experts_d'))
			{
				$mes_commentaires_maj = $annee;
			}
			elseif ($mois<AppBundle::getParameter('commentaires_experts_f'))
			{
				$mes_commentaires_maj = $annee - 1;
			}
		}
		$mes_commentaires = $em->getRepository('AppBundle:CommentaireExpert')->findBy( ['expert' => $moi ] );

        ///////////////////////

        return $this->render('expertise/liste.html.twig',
            [
            'rallonges'                  => $rallonges,
            'expertises_by_thematique'   => $expertises_by_thematique,
            'expertises_hors_thematique' => $my_expertises,
            'old_expertises'             => $old_expertises,
            'mes_commentaires_flag'      => $mes_commentaires_flag,
            'mes_commentaires'           => $mes_commentaires,
            'mes_commentaires_maj'       => $mes_commentaires_maj,
			'session'                    => $session,
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


	// Helper function used by modifierAction
	private static function expprjfirst($a,$b) { 
		if ($a->getVersion()->getProjet()->getId() < $b->getVersion()->getId()) return -1;
		return 1;
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

		$em         = $this->getDoctrine()->getManager();
        $expertiseRepository = $em->getRepository(Expertise::class);
        $session    = Functions::getSessionCourante();
        $commGlobal = $session->getcommGlobal();
        $anneeCour  = 2000 +$session->getAnneeSession();
        $anneePrec  = $anneeCour - 1;

        // Le comportement diffère suivant le type de projet
        $version = $expertise->getVersion();
        if( $version == null )
            Functions::createException(__METHOD__ . ":" . __LINE__ . "  " . $expertise . " n'a pas de version !" );

		// $session_edition -> Si false, on autorise le bouton Envoyer
		//                  -> Si true, on n'autorise pas
		$msg_explain = '';
        $projet_type = $version->getProjet()->getTypeProjet();
        $etat_session= $session -> getEtatSession();

		// Projets au fil de l'eau avec plusieurs expertises:
		//    Si je suis président, on va chercher ces expertises pour affichage
		//    On vérifie leur état (définitive ou pas)
		$autres_expertises = [];
		$toutes_definitives= true;
		if ($projet_type == Projet::PROJET_FIL && AppBundle::isGranted('ROLE_PRESIDENT') )
		{
			$expertiseRepository = AppBundle::getRepository(Expertise::class);
			$autres_expertises   = $expertiseRepository -> findExpertisesForVersion($version,$moi);
			foreach ($autres_expertises as $e)
			{
				if (! $e->getDefinitif())
				{
					$toutes_definitives = false;
					break;
				}
			}
		}

        switch ($projet_type)
		{
			case Projet::PROJET_SESS:
		        // Si c'est un projet de type PROJET_SESS, le bouton ENVOYER n'est disponible QUE si la session est en états ATTENTE ou ACTIF
		        if ($session -> getEtatSession() == Etat::EN_ATTENTE || $session -> getEtatSession() == Etat::ACTIF)
		        {
					// bouton envoyer disponible
					$session_edition = false;
				}
				else
				{
					// bouton envoyer pas disponible
					$session_edition = true;
				}
				break;
			case Projet::PROJET_TEST:
				// Si c'est un projet de type PROJET_SESS, le bouton ENVOYER est toujours disponible
				$session_edition = false;
				break;
			case Projet::PROJET_FIL:
				// Si c'est un projet de type PROJET_FIL, le bouton ENVOYER est disponible (presque) tout le temps
				if ($etat_session == Etat::EDITION_DEMANDE)
				{
					$msg_explain = "Vous ne pouvez pas actuellement finaliser votre expertise, car la session est en phase de \"édition des demandes\"";
					$session_edition = true;
				}
				elseif ($etat_session == Etat::EDITION_EXPERTISE)
				{
					$msg_explain = "Vous ne pouvez pas actuellement finaliser votre expertise, car la session est en phase d'\"édition des expertises\" et le \"commentaire de session\" n'est pas entré";
					$session_edition = true;
				}
				elseif ($toutes_definitives == false)
				{
					$msg_explain = "Vous ne pouvez pas actuellement finaliser votre expertise, il vous faut attendre que les autres experts aient terminé leur expertise";
					$session_edition = true;
				}
				else
				{
					$session_edition = false;
				}
				break;
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

		// Les boutons d'enregistrement ou d'envoi
		$editForm = $editForm->add('enregistrer', SubmitType::class, ['label' =>  'Enregistrer' ]);
        if( $session_edition == false)
        {
            $editForm   =   $editForm->add('envoyer',   SubmitType::class, ['label' =>  'Envoyer' ]);
		}
		$editForm->add( 'annuler', SubmitType::Class, ['label' =>  'Annuler' ]);
        $editForm->add( 'fermer',  SubmitType::Class );


        $editForm = $editForm->getForm();
        
        $editForm->handleRequest($request);

        if( $editForm->isSubmitted() && ! $editForm->isValid() )
             Functions::warningMessage(__METHOD__ . " form error " .  Functions::show($editForm->getErrors() ) );

        // Bouton ANNULER
        if( $editForm->isSubmitted() && $editForm->get('annuler')->isClicked() )
        {
			return $this->redirectToRoute('expertise_liste');
		}

		// Boutons ENREGISTRER, FERMER ou ENVOYER
        $erreur  = 0;
        $erreurs = [];
        if ($editForm->isSubmitted() /* && $editForm->isValid()*/ )
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

			// Bouton FERMER
			if ($editForm->get('fermer')->isClicked())
			{
				return $this->redirectToRoute('expertise_liste');
			}
				
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

		$expertises = $expertiseRepository->findExpertisesByExpert($moi, $session);
		uasort($expertises,"self::expprjfirst");

		$k          = array_search($expertise,$expertises);
		if ($k==0) 
		{
			$prev = null;
		}
		else
		{
			$prev = $expertises[$k-1];
		}
		if ($k==count($expertises)-1)
		{
			$next = null;
		}
		else
		{
			$next = $expertises[$k+1];
		}
        return $this->render($twig,
            [
            'expertise'         => $expertise,
            'autres_expertises' => $autres_expertises,
            'msg_explain'       => $msg_explain,
            'version'           => $expertise->getVersion(),
            'edit_form'         => $editForm->createView(),
            'anneePrec'         => $anneePrec,
            'anneeCour'         => $anneeCour,
            'session'           => $session,
            'session_edition'   => $session_edition,
            'erreurs'           => $erreurs,
            'toomuch'           => $toomuch,
            'prev'              => $prev,
            'next'              => $next
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
