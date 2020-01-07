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

use AppBundle\Entity\Session;
use AppBundle\Entity\Version;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\AppBundle;
use AppBundle\Utils\Functions;
use AppBundle\Utils\Etat;
use AppBundle\Utils\Signal;
use AppBundle\Utils\Menu;

use AppBundle\Workflow\Session\SessionWorkflow;
use AppBundle\Utils\GramcDate;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
/**
 * Session controller.
 *
 * @Route("session")
 * @Security("has_role('ROLE_ADMIN')")
 */
class SessionController extends Controller
{

    /**
     * Lists all session entities.
     *
     * @security("has_role('ROLE_ADMIN')")
     * @Route("/", name="session_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $sessions = $em->getRepository('AppBundle:Session')->findAll();

        return $this->render('session/index.html.twig', array(
            'sessions' => $sessions,
        ));
    }

    /**
     * Lists all session entities.
     *
     * @Security("has_role('ROLE_ADMIN') or has_role('ROLE_PRESIDENT')")
     * @Route("/gerer", name="gerer_sessions")
     * @Method("GET")
     */
    public function gererAction()
    {
        $em = $this->getDoctrine()->getManager();

        $sessions = $em->getRepository(Session::class)->findBy([],['idSession' => 'DESC']);
        if ( count($sessions)==0 ) {
            $menu[] =   [
                        'ok' => true,
                        'name' => 'ajouter_session' ,
                        'lien' => 'Créer nouvelle session',
                        'commentaire'=> 'Créer la PREMIERE session'
                        ];
        }
        else
        {
            AppBundle::getSession()->remove('SessionCourante');

            $session_courante       =   Functions::getSessionCourante();
            $etat_session_courante  =   $session_courante->getEtatSession();
            $workflow   = new SessionWorkflow();

            //

            if(  $etat_session_courante == Etat::ACTIF )
                $menu[] =   [
                            'ok' => true,
                            'name' => 'ajouter_session' ,
                            'lien' => 'Créer nouvelle session',
                            'commentaire'=> 'Créer nouvelle session'
                            ];
            else
                $menu[] =   [
                            'ok' => false,
                            'name' => 'ajouter_session',
                            'lien' => 'Créer nouvelle session',
                            'commentaire'=> 'Pas possible de créer une nouvelle session',
                            'raison'    => "La session courante n'est pas encore active",
                            ];

            //

            if( $workflow->canExecute( Signal::DAT_DEB_DEM, $session_courante) )
                {
                $menu[] =
                            [
                            'ok' => true,
                            'name' => 'modifier_session',
                            'param' => $session_courante->getIdSession(),
                            'lien' => 'Modifier la session courante',
                            'commentaire'=> 'Modifier la session courante'
                            ];
                $menu[] =   [
                            'ok' => true,
                            'name' => 'demarrer_saisie',
                            'lien' => 'Démarrer la saisie',
                            'commentaire'=> "Démarrer la saisie",
                            ];
                }
            else
                {
                $menu[] =   [
                            'ok' => false,
                            'name' => 'modifier_session',
                            'param' => $session_courante->getIdSession(),
                            'lien' => 'Modifier la session courante',
                            'commentaire'=> 'Pas possible de modifier la session courante',
                            'raison'    => "La session courante a déjà commencé",
                             ];
                 $menu[] =  [
                             'ok' => false,
                             'name' => 'demarrer_saisie',
                             'lien' => 'Démarrer la saisie',
                             'commentaire'=> "Pas possible de démarrer la saisie des projets",
                             'raison' => "La saisie a déjà démarré pour cette session",
                             ];
                }

            //

            if( $workflow->canExecute( Signal::DAT_FIN_DEM, $session_courante)  )
                 $menu[] =  [
                            'ok' => true,
                            'name' => 'terminer_saisie',
                            'lien' => 'Terminer la saisie',
                            'commentaire'=> 'Terminer la saisie'
                            ];
            elseif( $workflow->canExecute( Signal::DAT_DEB_DEM, $session_courante) )
                $menu[] =  [
                            'ok' => false,
                            'name' => 'terminer_saisie',
                            'lien' => 'Terminer la saisie',
                            'commentaire'=> 'Pas possible de terminer la saisie des projets',
                            'raison' => "La saisie n'a pas encore démarré pour cette session",
                            ];
            else
                $menu[] =  [
                            'ok' => false,
                            'name' => 'terminer_saisie',
                            'lien' => 'Terminer la saisie',
                            'commentaire'=> 'Pas possible de terminer la saisie des projets',
                            'raison' => "La saisie est déjà terminée pour cette session",
                            ];
            //

             if( $workflow->canExecute( Signal::CLK_ATTR_PRS, $session_courante)  &&  $session_courante->getcommGlobal() != null )

                $menu[] =  [
                            'ok' => true,
                            'name' => 'envoyer_expertises',
                            'lien' => 'Envoyer les expertises',
                            'commentaire'=> 'Envoyer les expertises',
                            ];
            else
                {
                    $item   = [
                            'ok' => false,
                            'name' => 'envoyer_expertises',
                            'lien' => 'Envoyer les expertises',
                            'commentaire'=> "Impossible d'envoyer les expertises",
                            ];
                    if( $session_courante->getCommGlobal() == null )
                        $item['raison'] = "Il n'y a pas de commentaire du président";
                    else
                        $item['raison'] = "La session n'est pas dans un état qui permet les envois";
                   $menu[]  =   $item;
                }

            //

            $mois = GramcDate::get()->format('m');

            if( $mois != 1 && $mois != 6 && $mois != 7 && $mois != 12 )
                $menu[] =   [
                            'ok' => false,
                            'name' => 'activer_session',
                            'lien' => 'Activer la session',
                            'commentaire'=> "Pas possible d'activer la session",
                            'raison' => "Seulement en Décembre, Janvier, Juin ou Juillet !",
                            ];
            else
                {
                if( $etat_session_courante == Etat::EN_ATTENTE &&
                    ($workflow->canExecute( Signal::CLK_SESS_DEB, $session_courante) || $workflow->canExecute( Signal::DAT_JUI, $session_courante) )
                  )
                  $menu[] =     [
                                'ok' => true,
                                'name' => 'activer_session',
                                'lien' => 'Activer la session',
                                'commentaire'=> 'Activer la session',
                                ];
                else
                    $menu[] =   [
                                'ok' => false,
                                'name' => 'activer_session',
                                'lien' => 'Activer la session',
                                'commentaire'=> "Pas possible d'activer la session",
                                'raison' => "Le commentaire de session n'a pas été envoyé, ou la session est déjà active",
                                ];
                }
        }

        // un bogue complètement obscur, out of memory
        //for( $i = 0; $i < 15; $i++ )
        //    $new_sessions[] = $sessions[$i];

        //for( $i = 1; $i < count($sessions); $i++ )
        //    $new_sessions[] = $sessions[$i];
        //$new_sessions[] = $sessions[0];


        return $this->render('session/gerer.html.twig',
		[
            'menu'     => $menu,
            'sessions' => $sessions,
            //'sessions' => $new_sessions,
		]);
    }

    /////////////////////////////////////////////////////////////////////

    /**
     * Creates a new session entity.
     *
     * @Route("/ajouter", name="ajouter_session")
     * @Method({"GET", "POST"})
     */
    public function ajouterAction(Request $request)
    {
        $info = static::prochain_session_info();
        $session = AppBundle::getRepository(Session::class)->find($info['id']);

        if( $session == null )
        {
            $hparannee = 0;
            $sess_act = Functions::getSessionCourante();
            if ($sess_act != null) {
                $hparannee=$sess_act->getHParAnnee();
                $president=$sess_act->getPresident();
            };
            $session = new Session();
            $session->setDateDebutSession( new GramcDate() )
                ->setDateFinSession( GramcDate::get()->add( \DateInterval::createFromDateString( '0 months' ) ) )
                ->setIdSession( $info['id'] )
                ->setTypeSession( $info['type'] )
                ->setHParAnnee($hparannee)
                ->setEtatSession( Etat::CREE_ATTENTE );
        }

        return $this->modifyAction( $request, $session );
    }

    /**
     *
     * @security("has_role('ROLE_ADMIN')")
     * @Route("/{id}/modify", name="modifier_session")
     * @Method({"GET", "POST"})
     */
    public function modifyAction(Request $request, Session $session)
    {
        AppBundle::getSession()->remove('SessionCourante');
        if( $session->getDateDebutSession() == null)
            $session->setDateDebutSession( new GramcDate() );
        if( $session->getDateFinSession() == null)
            $session->setDateFinSession( GramcDate::get()->add( \DateInterval::createFromDateString( '0 months' ) ) );

        $editForm = $this->createForm('AppBundle\Form\SessionType', $session,
            [ 'all' => false, 'buttons' => true ] );
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid())
            {
            AppBundle::getManager()->persist($session);
            AppBundle::getManager()->flush();

            return $this->redirectToRoute('gerer_sessions');
            }

        return $this->render('session/modify.html.twig',
            [
            'session' => $session,
            'edit_form' => $editForm->createView(),
            'session'   => $session,
            ]);
    }

    /**
     *
     * @Route("/terminer_saisie", name="terminer_saisie")
     * @Method("GET")
     */
    public function terminerSaisieAction(Request $request)
    {
        AppBundle::getSession()->remove('SessionCourante'); // remove cache
        $session_courante       =   Functions::getSessionCourante();
        $workflow   = new SessionWorkflow();

        if( $workflow->canExecute( Signal::DAT_FIN_DEM, $session_courante) )
            {
            $workflow->execute( Signal::DAT_FIN_DEM, $session_courante);
            AppBundle::getManager()->flush();
            return $this->redirectToRoute('gerer_sessions');
            }
        else
            return $this->render('default/error.html.twig',
                [
                'message'   => 'Impossible terminer la saisie',
                'titre'     =>  'Erreur',
                ]);
    }

    /**
     *
     * @Route("/activer", name="activer_session")
     * @Method("GET")
     */
    public function activerAction(Request $request)
    {
        AppBundle::getSession()->remove('SessionCourante'); // remove cache
        $session_courante       =   Functions::getSessionCourante();
        $etat_session_courante  =   $session_courante->getEtatSession();
        $workflow   = new SessionWorkflow();

        $sessions = AppBundle::getRepository(Session::class)->findBy([],['idSession' => 'DESC']);

		$ok = false;
        $mois = GramcDate::get()->format('m');
        if( $mois == 1 ||  $mois == 12 )
		{
            if( $workflow->canExecute( Signal::CLK_SESS_DEB, $session_courante) && $etat_session_courante == Etat::EN_ATTENTE )
			{
                foreach( $sessions as $session )
				{
                    if( $session->getIdSession() == $session_courante->getIdSession() )
                        continue;

                    $workflow   = new SessionWorkflow();
                    if( $workflow->canExecute( Signal::CLK_SESS_FIN, $session) )
                        $err = $workflow->execute( Signal::CLK_SESS_FIN, $session);
				}

                $ok = $workflow->execute( Signal::CLK_SESS_DEB, $session_courante );
                AppBundle::getManager()->flush();
			}
		}
        elseif( $mois == 6 ||  $mois == 7 )
            if( $workflow->canExecute(Signal::CLK_SESS_DEB , $session_courante)  && $etat_session_courante == Etat::EN_ATTENTE )
			{
                //foreach( $sessions as $session )
                //    {
                //    $workflow   = new SessionWorkflow();
                //    if( $workflow->canExecute( Signal::CLK_SESS_DEB, $session) )
                //        $workflow->execute( Signal::CLK_SESS_DEB, $session);
                //    }
                $ok = $workflow->execute(Signal::CLK_SESS_DEB , $session_courante );
                AppBundle::getManager()->flush();
			}

		if ($ok==true)
		{
			return $this->redirectToRoute('gerer_sessions');
		}
		else
		{
	        return $this->render('default/error.html.twig',
			[
                'message'   => "Impossible d'activer la session, allez voir le journal !",
                'titre'     =>  'Erreur',
			]);
		}
    }

    /**
     *
     * @Security("has_role('ROLE_ADMIN') or has_role('ROLE_PRESIDENT')")
     * @Route("/envoyer_expertises", name="envoyer_expertises")
     * @Method("GET")
     */
    public function envoyerExpertisesAction(Request $request)
    {
        AppBundle::getSession()->remove('SessionCourante'); // remove cache
        $session_courante       =   Functions::getSessionCourante();
        $workflow   = new SessionWorkflow();

        if( $workflow->canExecute( Signal::CLK_ATTR_PRS, $session_courante) )
            {
            $workflow->execute( Signal::CLK_ATTR_PRS, $session_courante);
            AppBundle::getManager()->flush();
            return $this->redirectToRoute('gerer_sessions');
            }
        else
            return $this->render('default/error.html.twig',
                [
                'message'   => "Impossible d'envoyer les expertises",
                'titre'     =>  'Erreur',
                ]);
    }

    /**
     *
     *
     * @Route("/demarrer_saisie", name="demarrer_saisie")
     * @Method("GET")
     */
    public function demarrerSaisieAction(Request $request)
    {
        AppBundle::getSession()->remove('SessionCourante'); // remove cache
        $session_courante       =   Functions::getSessionCourante();
        //return new Response( $session_courante->getIdSession() );
        $workflow   = new SessionWorkflow();

        if( $workflow->canExecute( Signal::DAT_DEB_DEM, $session_courante) )
            {
            $workflow->execute( Signal::DAT_DEB_DEM, $session_courante);
            AppBundle::getManager()->flush();
            return $this->redirectToRoute('gerer_sessions');
            }
        else
            return $this->render('default/error.html.twig',
                [
                'message'   => "Impossible demarrer la saisie",
                'titre'     =>  'Erreur',
                ]);
    }

    /**
     * Creates a new session entity.
     *
     * @Route("/new", name="session_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $session = new Session();
        $form = $this->createForm('AppBundle\Form\SessionType', $session, ['all' => true ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($session);
            $em->flush($session);

            return $this->redirectToRoute('session_show', array('id' => $session->getId()));
        }

        return $this->render('session/new.html.twig', array(
            'session' => $session,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a session entity.
     *
     * @Route("/{id}/show", name="session_show")
     * @Method("GET")
     */
    public function showAction(Session $session)
    {
        $deleteForm = $this->createDeleteForm($session);

        return $this->render('session/show.html.twig', array(
            'session' => $session,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Meme chose que show, mais présenté "à la gramc"
     *
     * @Route("/{id}/consulter", name="consulter_session")
     * @Method("GET")
     */
    public function consulterAction(Session $session)
    {
        $menu = [ Menu::gerer_sessions() ];

        return $this->render('session/consulter.html.twig', array(
            'session' => $session,
            'menu' => $menu
        ));
    }

    /**
     * Displays a form to edit an existing session entity.
     *
     * @Route("/{id}/edit", name="session_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Session $session)
    {
        $deleteForm = $this->createDeleteForm($session);
        $editForm = $this->createForm('AppBundle\Form\SessionType', $session, [ 'all' => true ] );
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('session_edit', array('id' => $session->getId()));
        }

        return $this->render('session/edit.html.twig', array(
            'session' => $session,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing session entity.
     *
     * @Route("/commentaires", name="session_commentaires")
     * @Method({"GET", "POST"})
     */
    public function commentairesAction(Request $request)
    {

        AppBundle::getSession()->remove('SessionCourante'); // remove cache
        $session_courante       = Functions::getSessionCourante();
        $etat_session_courante  = $session_courante->getEtatSession();
        $workflow               = new SessionWorkflow();

        $editForm = $this->createForm('AppBundle\Form\SessionType', $session_courante, [ 'commentaire' => true ] );
        $editForm->handleRequest($request);

        if( $workflow->canExecute( Signal::CLK_ATTR_PRS, $session_courante)  &&  $session_courante->getcommGlobal() != null )
        {
            $menu[] =  [
                        'ok' => true,
                        'name' => 'envoyer_expertises',
                        'lien' => 'Envoyer les expertises',
                        'commentaire'=> 'Envoyer les expertises',
                        ];
        }
        else
        {
            $item   = [
                    'ok' => false,
                    'name' => 'envoyer_expertises',
                    'lien' => 'Envoyer les expertises',
                    'commentaire'=> "Impossible d'envoyer les expertises",
                    ];
            if( $session_courante->getCommGlobal() == null )
                $item['raison'] = "Il n'y a pas de commentaire de session";
            else
                $item['raison'] = "La session n'est pas en édition d'expertises";

            $menu[]  =   $item;
        }

        if ($editForm->isSubmitted() && $editForm->isValid())
        {
            $this->getDoctrine()->getManager()->flush();
            //return $this->redirectToRoute('president_accueil');
        }

        return $this->render('session/commentaires.html.twig',
            [
            'session'   => $session_courante,
            'edit_form' => $editForm->createView(),
            'menu'      => $menu
            ]);
    }

    /**
     * Deletes a session entity.
     *
     * @Route("/{id}", name="session_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Session $session)
    {
        $form = $this->createDeleteForm($session);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($session);
            $em->flush($session);
            if( AppBundle::getSession()->has('SessionCourante') )
                AppBundle::getSession()->remove('SessionCourante'); // clear cache
        }

        return $this->redirectToRoute('session_index');
    }

    /**
     * Creates a form to delete a session entity.
     *
     * @param Session $session The session entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Session $session)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('session_delete', array('id' => $session->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    private static function prochain_session_info()
    {
        $annee = GramcDate::get()->format('y');   // 15 pour 2015
        $mois  = GramcDate::get()->format('m');   // 5 pour mai

        if ($mois<7)
        {
            $id_session = $annee.'B';
            $type       = 1;
        }
        else
        {
            $id_session = $annee+1 .'A';
            $type = 0;
        }

        return [ 'id' => $id_session, 'type' => $type ];
    }

    ////////////////////////////////////////////////////////////////////

    /**
     *
     * @Route("/bilan", name="bilan_session")
     * @Method({"GET","POST"})
     */
    public function bilanAction(Request $request)
    {
        $data   =   Functions::selectSession($request);

        return $this->render('session/bilan.html.twig',
            [
            'form' => $data['form']->createView(),
            'idSession' => $data['session']->getIdSession(),
            'versions'  =>  AppBundle::getRepository(Version::class)->findBy( ['session' => $data['session'] ] )
            ]);
    }

    /**
     *
     * @Route("/bilan_annuel", name="bilan_annuel")
     * @Security("has_role('ROLE_OBS')")
     * @Method({"GET","POST"})
     */
    public function bilanAnnuelAction(Request $request)
    {
        $data   =   Functions::selectAnnee($request);
		$avec_commentaires = AppBundle::hasParameter('commentaires_experts_d');
        return $this->render('session/bilanannuel.html.twig',
            [
            'form' => $data['form']->createView(),
            'annee'=> $data['annee'],
            'avec_commentaires' => $avec_commentaires
            //'versions'  =>  AppBundle::getRepository(Version::class)->findBy( ['session' => $data['session'] ] )
            ]);
    }

    //////////////////////////////////////////////////////////////////////
    //
    //
    //
    //////////////////////////////////////////////////////////////////////

    /**
     *
     *
     * @Route("/{id}/questionnaire_csv", name="questionnaire_csv")
     * @Method("GET")
     */
    public function questionnaireCsvAction(Request $request,Session $session)
    {
	    $entetes =  [
			'Projet',
			'Titre',
			'Thématique',
			'Responsable scientifique',
			'Laboratoire',
			'Langages utilisés',
			'gpu',
			'Nom du code',
			'Licence',
			'Heures/job',
			'Ram/cœur',
			'Ram partagée',
			'Efficacité parallèle',
			'Stockage temporaire',
			'Post-traitement',
			'Meta données',
			'Nombre de datasets',
			'Taille des datasets'
		];
	    $sortie = join("\t",$entetes) . "\n";

	    $versions = AppBundle::getRepository(Version::class)->findBy( ['session' => $session ] );

	    foreach( $versions as $version )
		{
	        $langage = "";
	        if( $version->getCodeCpp()== true )     $langage .= " C++ ";
	        if( $version->getCodeC()== true )       $langage .= " C ";
	        if( $version->getCodeFor()== true )     $langage .= " Fortran ";
	        $langage .=  Functions::string_conversion($version->getCodeLangage());
	        $ligne = [
				($version->getIdVersion() != null) ? $version->getIdVersion() : 'null',
				Functions::string_conversion($version->getPrjTitre()),
				Functions::string_conversion($version->getPrjThematique()),
				$version->getResponsable(),
				$version->getLabo(),
				trim($langage),
				Functions::string_conversion($version->getGpu()),
				Functions::string_conversion($version->getCodeNom()),
				Functions::string_conversion($version->getCodeLicence()),
				Functions::string_conversion($version->getCodeHeuresPJob()),
				Functions::string_conversion($version->getCodeRamPCoeur()),
				Functions::string_conversion($version->getCodeRamPart()),
				Functions::string_conversion($version->getCodeEffParal()),
				Functions::string_conversion($version->getCodeVolDonnTmp()),
				Functions::string_conversion($version->getDemPostTrait()),
				Functions::string_conversion($version->getDataMetaDataFormat()),
				Functions::string_conversion($version->getDataNombreDatasets()),
				Functions::string_conversion($version->getDataTailleDatasets()),

			];
	        $sortie     .=   join("\t",$ligne) . "\n";
		}

	    return Functions::csv($sortie,'bilan_reponses__session_'.$session->getIdSession().'.csv');
    }

    //////////////////////////////////////////////////////////////////////
    //
    //
    //
    //////////////////////////////////////////////////////////////////////

    /**
     *
     * @Security("has_role('ROLE_OBS')")
     * @Route("/{annee}/bilan_annuel_csv", name="bilan_annuel_csv")
     * @Method("GET")
     *
     */
    public function bilanAnnuelCsvAction(Request $request, $annee)
    {
        $entetes = ['Projet','Thématique','Titre','Responsable','Quota'];

        // Les mois pour les consos
        array_push($entetes,'Janvier','Février','Mars','Avril', 'Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre');

        $entetes[] = "total";
        $entetes[] = "Total(%/quota)";

        $sortie     =   join("\t",$entetes) . "\n";

		// Sommes-nous dans l'année courante ?
		$annee_courante_flg = (GramcDate::get()->showYear()==$annee);

        //////////////////////////////

        $conso_flds = ['m01','m02','m03','m04','m05','m06','m07','m08','m09','m10','m11','m12'];

        // 2019 -> 19A et 19B
        $session_id_A = substr($annee, 2, 2) . 'A';
        $session_id_B = substr($annee, 2, 2) . 'B';
        $session_A = AppBundle::getRepository(Session::class)->findOneBy(['idSession' => $session_id_A ]);
        $session_B = AppBundle::getRepository(Session::class)->findOneBy(['idSession' => $session_id_B ]);

        $versions_A= AppBundle::getRepository(Version::class)->findBy( ['session' => $session_A ] );
        $versions_B= AppBundle::getRepository(Version::class)->findBy( ['session' => $session_B ] );

        // On stoque dans le tableau $id_projets une paire: [$projet, $version] où $version est la version A, ou la B si elle existe
        $id_projets= [];
        foreach ( array_merge($versions_A, $versions_B) as $v)
        {
            $projet = $v -> getProjet();
            $id_projet = $projet->getIdProjet();
            $id_projets[$id_projet] = [ $projet, $v ];
        }

        // Les totaux
        $tq  = 0;		// Le total des quotas
        $tm  = [0,0,0,0,0,0,0,0,0,0,0,0];		// La conso totale par mois
        $tttl= 0;		// Le total de la conso

        // Calcul du csv, ligne par ligne
        foreach ( $id_projets as $id_projet => $paire )
        {
            $line   = [];
            $line[] = $id_projet;
            $p      = $paire[0];
            $v      = $paire[1];
            $line[] = $p->getThematique();
            $line[] = $p->getTitre();
            $r      = $v->getResponsable();
            $line[] = $r->getPrenom() . ' ' . $r->getNom();
            $quota  = $v->getQuota();
            $line[] = $quota;
            for ($m=0;$m<12;$m++)
            {
				$c = $p->getConsoMois($annee,$m);
				$line[] = $c;
				$tm[$m] += $c;
			}

			// Si on est dans l'année courante on ne fait pas le total
            $ttl    = ($annee_courante_flg) ? 'N/A' : $p->getConsoCalcul($annee);
            if ($quota>0)
            {
	            $ttlp   = ($annee_courante_flg) ? 'N/A' : 100.0 * $ttl / $quota;
			}
			else
			{
				$ttlp = 0;
			}
            $line[] = $ttl;
            $line[] = ($ttlp=='N/A') ? $ttlp : intval($ttlp);

            $sortie .= join("\t",$line) . "\n";

            // Mise à jour des totaux
            $tq   += $quota;
            $tttl += $ttl;
        }

        // Dernière ligne
        $line   = [];
        $line[] = 'TOTAL';
        $line[] = '';
        $line[] = '';
        $line[] = '';
        $line[] = $tq;
        for ($m=0; $m<12; $m++)
        {
			$line[] = $tm[$m];
		}
        $line[] = $tttl;

        if ($tq > 0) {
            $line[] = intval(100.0 * $tttl / $tq);
        } else {
            $line[] = 'N/A';
        }
        $sortie .= join("\t",$line) . "\n";

        return Functions::csv($sortie,'bilan_annuel_'.$annee.'.csv');
    }

    /**
     *
     * @Security("has_role('ROLE_OBS')")
     * @Route("/{annee}/bilan_annuel_labo_csv", name="bilan_annuel_labo_csv")
     * @Method("GET")
     *
     */
    public function bilanLaboCsvAction(Request $request, $annee)
    {
        $entetes = ['Laboratoire','Nombre de projets','Heures attribuées','Heure consommées','projets'];

        $sortie     =   join("\t",$entetes) . "\n";

        //////////////////////////////

        // $annee = 2017, 2018, etc. (4 caractères)
        $session_id_A = substr($annee, 2, 2) . 'A';
        $session_id_B = substr($annee, 2, 2) . 'B';
        $session_A = AppBundle::getRepository(Session::class)->findOneBy(['idSession' => $session_id_A ]);
        $session_B = AppBundle::getRepository(Session::class)->findOneBy(['idSession' => $session_id_B ]);

        $versions_A= AppBundle::getRepository(Version::class)->findBy( ['session' => $session_A ] );
        $versions_B= AppBundle::getRepository(Version::class)->findBy( ['session' => $session_B ] );

        // Tableau $laboratoires = tableau associatif
        //     clé = Sigle du laboratoire tel qu'il est dans la base de données
        //     valeur = Un tableau associatifs:
        //              attrHeures -> Somme des heures attribuées à ce laboratoire
        //              projets    -> [] projets ayant une version cette année dans ce laboratoire
        $laboratoires=[];
        foreach ( array_merge($versions_A, $versions_B) as $v)
        {
            $projet  = $v -> getProjet();
            $acro    = $v -> getResponsable() -> getLabo() -> getAcroLabo();
            if ( ! array_key_exists($acro,$laboratoires) ) {
                $labo=[];
                $labo['attrHeures'] = 0;
                $labo['projets'] = [];
                $laboratoires[$acro] = $labo;
            }
            $labo = $laboratoires[$acro];
            $labo['attrHeures'] += $v->getAttrHeures();
            foreach ($v->getRallonge() as $r)
            {
                $labo['attrHeures'] += $r->getAttrHeures();
            }
            $labo['attrHeures'] -= $v->getPenalHeures();
            if ( ! in_array($projet,$labo['projets'])) {
                $labo['projets'][] = $projet;
            }
            $laboratoires[$acro] = $labo;
        }

        $keys = array_keys($laboratoires);
        sort($keys);

        // Calcul du csv, ligne par ligne
        foreach ($keys as $k)
        {
            $ligne   = [];
            $ligne[] = $k;
            $l       = $laboratoires[$k];

            $projets = $l['projets'];
            $ligne[] = count($projets);
            $ligne[] = $l['attrHeures'];

            // Calculer la consommation pour chaque laboratoire à partir de la liste des projets
            // NOTE - Ce calcul n'a pas trop de sens si $année est l'année courante !!!
            $c = 0;
            $id_projets = [];
            foreach ($projets as $p)
            {
                $c += $p -> getConsoCalcul($annee);
                $id_projets[] = $p -> getIdProjet();
            }

            $ligne[] = $c;
            $ligne[] = implode(',',$id_projets);

            $sortie .= join("\t",$ligne) . "\n";
        }

        return Functions::csv($sortie,'bilan_annuel_par_labo'.$annee.'.csv');
    }

    /**
     *
     * Génère le bilan de session au format CSV
     *
     * @Route("/{id}/bilan_csv", name="bilan_session_csv")
     * @Method("GET")
     */
    public function bilanCsvAction(Request $request,Session $session)
    {
        $type_session       = $session->getLibelleTypeSession(); // A ou B
        $id_session         = $session->getIdSession();
        $annee_cour         = $session->getAnneeSession();
        $session_courante_A = AppBundle::getRepository(Session::class)->findOneBy(['idSession' => $annee_cour .'A']);
        if( $session_courante_A == null ) return new Response('Session courante nulle !');

        if ($type_session == 'A')
        {
			return $this->bilanCsvAction_A($request,$session, $id_session, $annee_cour, $session_courante_A);
		}
		//else
		//{
		//	return $this->bilanCsvAction_B(Request $request,Session $session, $id_session, $annee_cour, $session_courante_A);
		//}

		// On laisse ce code jusqu'au printemps 2020 !
		// Au printemps 2020 on écrira la fonction bilanCsvAction_B
		//

        $annee_prec     =   $annee_cour - 1;
        $full_annee_prec= 2000 + $annee_prec;
        $full_annee_cour= 2000 + $annee_cour;

        $session_precedente_A   = AppBundle::getRepository(Session::class)->findOneBy(['idSession' => $annee_prec .'A']);
        $session_precedente_B   = AppBundle::getRepository(Session::class)->findOneBy(['idSession' => $annee_prec .'B']);


        if( $session_courante_A == null ) return new Response('Session courante nulle !');

        // Si type A on regarde la conso annee precedente, sinon annee courante !
        $annee_conso=($type_session=='A') ? $annee_prec : $annee_cour;

        $entetes = ['Projet',
                    'Thématique',
                    'Responsable scientifique',
                    'Laboratoire',
                    'Rapport',
                    'Expert',
                    'Demandes '     .$full_annee_prec,
                    'Dem rall '     .$full_annee_prec,
                    'Attr rall '    .$full_annee_prec,
                    'Pénalités '    .$full_annee_prec,
                    'Attributions ' .$full_annee_prec,
                    ];

        if ($type_session=='B')
            array_push($entetes,'Demandes '.$annee_cour.'A','Attributions '.$annee_cour.'A');

        array_push($entetes,'Demandes '.$id_session,'Attributions '.$id_session,"Quota $annee_prec",
                                "Consommation $annee_conso","Conso gpu normalisée","Consommation $annee_conso (%)");

         // Si type B on ajoute la colonne Recupérable
        if ($type_session=='B') array_push($entetes,'Récupérables');

        // Les mois pour les consos
        array_push($entetes,'Janvier','Février','Mars','Avril',
            'Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre');

        $sortie     =   join("\t",$entetes) . "\n";

        //////////////////////////////

        $totaux=
		[
            "dem_heures_prec"       =>  0,
            "attr_heures_prec"      =>  0,
            "dem_rall_heures_prec"  =>  0,
            "attr_rall_heures_prec" =>  0,
            "penal_heures_prec"     =>  0,
            "dem_heures_A"          =>  0, // session B
            "attr_heures_A"         =>  0, // session B
            "dem_heures_cour"       =>  0,
            "attr_heures_cour"      =>  0,
            "quota"                 =>  0,
            "conso_an"              =>  0,
            "conso_gpu"             =>  0,
            "recuperable"           =>  0,
		];
        $conso_flds = ['m00','m01','m02','m03','m04','m05','m06','m07','m08','m09','m10','m11'];
        foreach  ($conso_flds as $m)    $totaux[$m] =   0;

        //////////////////////////////
        //
        // boucle principale
        //
        //////////////////////////////

        $versions = AppBundle::getRepository(Version::class)->findBy( ['session' => $session ] );

        /*
         * Calcul de la date pour savoir s'il y a des heures à récupérer
         * Seulement utile pour la session B s'il y a une version du projet en session A
         */
        $date_recup = GramcDate::Get();
        $d30j       = new \DateTime($annee_cour.'-06-30'); // Le 30 Juin
      	// Si on est après le 30 juin on considère le 30 juin comme date de conso de référence
      	// Si on est avant, on considère la date du jour
      	// Evidemment elle ne devrait pas être trop éloignée du 30 juin sinon cela n'a pas trop de sens !
        if ($date_recup > $d30j)
        {
			$date_recup = $d30j;
		}

        foreach( $versions as $version )
		{
            if( $session_precedente_A != null )
                $version_precedente_A = AppBundle::getRepository(Version::class)
                            ->findOneVersion($session_precedente_A, $version->getProjet() );
            else $version_precedente_A = null;

            if( $session_precedente_B != null )
                $version_precedente_B = AppBundle::getRepository(Version::class)
                            ->findOneVersion($session_precedente_B, $version->getProjet() );
            else $version_precedente_B = null;

            if( $session_courante_A != null )
                $version_courante_A = AppBundle::getRepository(Version::class)
                            ->findOneVersion($session_courante_A, $version->getProjet() );
            else $version_courante_A = null;

            $dem_heures_prec    = 0;
            if( $version_precedente_A != null ) $dem_heures_prec += $version_precedente_A->getDemHeures();
            if( $version_precedente_B != null ) $dem_heures_prec += $version_precedente_B->getDemHeures();

            $attr_heures_prec   = 0;
            if( $version_precedente_A != null ) $attr_heures_prec += $version_precedente_A->getAttrHeures();
            if( $version_precedente_B != null ) $attr_heures_prec += $version_precedente_B->getAttrHeures();

            $penal_heures       = 0;
            if( $version_precedente_A != null ) $penal_heures   += $version_precedente_A->getPenalHeures();
            if( $version_precedente_B != null ) $penal_heures   += $version_precedente_B->getPenalHeures();

            $dem_heures_rallonge    =   0;
            if( $version_precedente_A != null ) $dem_heures_rallonge    += $version_precedente_A->getDemHeuresRallonge();
            if( $version_precedente_B != null ) $dem_heures_rallonge    += $version_precedente_B->getDemHeuresRallonge();

            $attr_heures_rallonge   =   0;
            if( $version_precedente_A != null ) $attr_heures_rallonge   += $version_precedente_A->getAttrHeuresRallonge();
            if( $version_precedente_B != null ) $attr_heures_rallonge   += $version_precedente_B->getAttrHeuresRallonge();

            $penal_heures   =   0;
            if( $version_precedente_A != null ) $penal_heures   += $version_precedente_A->getPenalHeures();
            if( $version_precedente_B != null ) $penal_heures   += $version_precedente_B->getPenalHeures();

            $dem_heures_A    = 0;
            if( $version_courante_A != null ) $dem_heures_A += $version_courante_A->getDemHeures();

            $attr_heures_A   = 0;
            if( $version_courante_A != null ) $attr_heures_A +=
                $version_courante_A->getAttrHeures() + $version_courante_A->getAttrHeuresRallonge() - $version_courante_A->getPenalHeures();

			$conso     = 0;
			$conso_gpu = 0;
			$quota     = 0;
			if ($type_session=='A')
			{
				if ($version_precedente_A != null) {
					$conso = $version_precedente_A->getConsoCalcul();
					//$quota = $version_precedente_A->getQuota();
					$quota = 1;
					$conso_gpu = $version->getProjet()->getConsoRessource('gpu',$full_annee_cour)[0];
				}
				elseif ( $version_precedente_B != null ) {
					$conso = $version_precedente_B->getConsoCalcul();
					//$quota = $version_precedente_A->getQuota();
					$quota = 1;
					$conso_gpu = $version->getProjet()->getConsoRessource('gpu',$full_annee_cour)[0];
				}
			}
			// type B
			else
			{
				$conso = $version->getConsoCalcul();
				$quota = $version->getQuota();
				$conso_gpu = $version->getProjet()->getConsoRessource('gpu',$full_annee_cour)[0];
			}

            $dem_heure_cour     =   $version->getDemHeures();
            $attr_heure_cour    =   $version->getAttrHeures();

			// Calcul des heures récupérables au printemps
            if( $version_courante_A != null )
            {
				// TODO - VERIFIER EN 2020 QUE CA MARCHE !
				$conso_juin = $version->getConsoCalcul($date_recup->format('Y-m-d'));
                $recuperable        =   static::calc_recup_heures_printemps( $conso_juin, $attr_heures_A);
			}
            else
            {
                $recuperable        =   0;
			}

            $ligne =
                    [
                    $version->getProjet(),
                    '"'. $version->getPrjThematique() .'"',
                    '"'.$version->getResponsable() .'"',
                    '"'.$version->getLabo().'"',
                    ( $version->hasRapportActivite() == true ) ? 'OUI' : 'NON',
                    ( $version->getResponsable()->getExpert() ) ? '*******' : $version->getExpert(),
                    $dem_heures_prec,
                    $dem_heures_rallonge,
                    $attr_heures_rallonge,
                    $penal_heures,
                    $attr_heures_prec+$attr_heures_rallonge-$penal_heures,
                    ];

            if ($type_session=='B')
                    $ligne = array_merge( $ligne, [ $dem_heures_A, $attr_heures_A ] );

             $ligne = array_merge( $ligne,
                    [
                    $dem_heure_cour,
                    $attr_heure_cour,
                    $quota,
                    //( $consommation != null ) ? $consommation->conso(): 0,
                    $conso,
                    $conso_gpu,
                    //( $quota != 0 ) ? intval(round( $consommation->conso() * 100 /$quota ) ): null,
                    $quota != 0  ? intval(round( $conso * 100 /$quota ) ): 0
                    ]);

	        if ($type_session=='B') $ligne[] =  $recuperable;

			for ($m=0;$m<12;$m++)
			{
				$consmois= $version->getProjet()->getConsoMois($annee_cour,$m);
				$index   = 'm' . ($m<10?'0':'') . $m;

				$ligne[] = $consmois;
				$totaux[$index] += $consmois;
			};

            $sortie     .=   join("\t",$ligne) . "\n";

            $totaux["dem_heures_prec"]          +=  $dem_heures_prec;
            $totaux["attr_heures_prec"]         +=  $attr_heures_prec;
            $totaux["dem_rall_heures_prec"]     +=  $dem_heures_rallonge;
            $totaux["attr_rall_heures_prec"]    +=  $attr_heures_rallonge;
            $totaux["penal_heures_prec"]        +=  $penal_heures;
            $totaux["dem_heures_cour"]          +=  $dem_heure_cour;
            $totaux["attr_heures_cour"]         +=  $attr_heure_cour;
            $totaux["dem_heures_A"]             +=  $dem_heures_A;
            $totaux["attr_heures_A"]            +=  $attr_heures_A;
            $totaux["quota"]                    +=  $quota;
            $totaux["conso_an"]                 +=  $version->getConsoCalcul(); //( $consommation != null ) ? $consommation->conso(): 0;
            $totaux["conso_gpu"]                +=  $conso_gpu;
            $totaux["recuperable"]              +=  $recuperable;

            } // fin de la boucle principale

        $ligne  =
                [
                'TOTAL','','','','','',
                $totaux["dem_heures_prec"],
                $totaux["dem_rall_heures_prec"],
                $totaux["attr_rall_heures_prec"],
                $totaux["penal_heures_prec"],
                $totaux["attr_heures_prec"]+$totaux["attr_rall_heures_prec"]-$totaux["penal_heures_prec"],
                ];

         if ($type_session=='B')
                $ligne  = array_merge( $ligne, [ $totaux["dem_heures_A"], $totaux["attr_heures_A"] ]);

          $ligne  =  array_merge( $ligne,
                [
                $totaux["dem_heures_cour"],
                $totaux["attr_heures_cour"],
                $totaux["quota"],
                $totaux["conso_an"],
                $totaux["conso_gpu"],
                '', // %
                ]);


          if ($type_session=='B') $ligne[] = $totaux["recuperable"]; // recupérable en session B

          $ligne  = array_merge( $ligne,
                [
                $totaux["m00"],$totaux["m01"],$totaux["m02"],$totaux["m03"],$totaux["m04"],$totaux["m05"],
                $totaux["m06"],$totaux["m07"],$totaux["m08"],$totaux["m09"],$totaux["m10"],$totaux["m11"],
                ]);

        $sortie     .=   join("\t",$ligne) . "\n";

        return Functions::csv($sortie,'bilan_session_'.$session->getIdSession().'.csv');
    }

	/*******
	 * Appelée par bilanAction dans le cas d'une session A
	 *
	 *********/
	private function bilanCsvAction_A(Request $request,Session $session, $id_session, $annee_cour, $session_courante_A)
	{
		// NOTE - Pour la session 20A, $full_annee_cour=2020, $full_anne_prec=2019 !
        $annee_prec      = $annee_cour - 1;
        $full_annee_prec = 2000 + $annee_prec;
        $full_annee_cour = 2000 + $annee_cour;

        $session_precedente_A = AppBundle::getRepository(Session::class)->findOneBy(['idSession' => $annee_prec .'A']);
        $session_precedente_B = AppBundle::getRepository(Session::class)->findOneBy(['idSession' => $annee_prec .'B']);

		// Pour les ressources de stockage
		$ressources = AppBundle::getParameter('ressources_conso_group');
		foreach ($ressources as $k=>$r)
		{
			if ($r['type']==='stockage')
			{
				$ress     = $r['ress'];
				$nom_ress = $r['nom'];
			}
		}
		$t_fact = 1024*1024*1024;	// Conversion octets -> To

        // type A: on regarde la conso annee precedente
        $annee_conso = $annee_prec;

        $entetes = ['Projet',
                    'Thématique',
                    'Responsable scientifique',
                    'Laboratoire',
                    'Rapport',
                    'Expert',
                    'Demandes '     .$full_annee_prec,
                    'Dem rall '     .$full_annee_prec,
                    'Attr rall '    .$full_annee_prec,
                    'Pénalités '    .$full_annee_prec,
                    'Attributions ' .$full_annee_prec,
                    ];

        array_push($entetes,'Demandes '.$id_session,'Attributions '.$id_session,"Quota $annee_prec",
                                "Consommation $annee_conso","Conso gpu normalisée","Consommation $annee_conso (%)");

        // Occupation du volume gpfs
        array_push($entetes,"quota $nom_ress (To)","occup $nom_ress (%)");

        // Les mois pour les consos
        array_push($entetes,'Janvier','Février','Mars','Avril',
            'Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre');

        $sortie = join("\t",$entetes) . "\n";

        //////////////////////////////

        $totaux=
		[
            "dem_heures_prec"       =>  0,
            "attr_heures_prec"      =>  0,
            "dem_rall_heures_prec"  =>  0,
            "attr_rall_heures_prec" =>  0,
            "penal_heures_prec"     =>  0,
            "dem_heures_cour"       =>  0,
            "attr_heures_cour"      =>  0,
            "quota"                 =>  0,
            "conso_an"              =>  0,
            "conso_gpu"             =>  0,
            "recuperable"           =>  0,
            "conso_stock"           =>  0,
            "quota_stock"           =>  0,
		];
        $conso_flds = ['m00','m01','m02','m03','m04','m05','m06','m07','m08','m09','m10','m11'];
        foreach  ($conso_flds as $m)    $totaux[$m] =   0;

        //////////////////////////////
        //
        // boucle principale
        //
        //////////////////////////////

        $versions = AppBundle::getRepository(Version::class)->findBy( ['session' => $session ] );

        foreach( $versions as $version )
		{
            if( $session_precedente_A != null )
                $version_precedente_A = AppBundle::getRepository(Version::class)
                            ->findOneVersion($session_precedente_A, $version->getProjet() );
            else $version_precedente_A = null;

            if( $session_precedente_B != null )
                $version_precedente_B = AppBundle::getRepository(Version::class)
                            ->findOneVersion($session_precedente_B, $version->getProjet() );
            else $version_precedente_B = null;

            if( $session_courante_A != null )
                $version_courante_A = AppBundle::getRepository(Version::class)
                            ->findOneVersion($session_courante_A, $version->getProjet() );
            else $version_courante_A = null;

			$projet = $version -> getProjet();

            $dem_heures_prec           = 0;
            if( $version_precedente_A != null ) $dem_heures_prec += $version_precedente_A->getDemHeures();
            if( $version_precedente_B != null ) $dem_heures_prec += $version_precedente_B->getDemHeures();

            $attr_heures_prec          = 0;
            if( $version_precedente_A != null ) $attr_heures_prec += $version_precedente_A->getAttrHeures();
            if( $version_precedente_B != null ) $attr_heures_prec += $version_precedente_B->getAttrHeures();

            $penal_heures              = 0;
            if( $version_precedente_A != null ) $penal_heures   += $version_precedente_A->getPenalHeures();
            if( $version_precedente_B != null ) $penal_heures   += $version_precedente_B->getPenalHeures();

            $dem_heures_rallonge       = 0;
            if( $version_precedente_A != null ) $dem_heures_rallonge    += $version_precedente_A->getDemHeuresRallonge();
            if( $version_precedente_B != null ) $dem_heures_rallonge    += $version_precedente_B->getDemHeuresRallonge();

            $attr_heures_rallonge      = 0;
            if( $version_precedente_A != null ) $attr_heures_rallonge   += $version_precedente_A->getAttrHeuresRallonge();
            if( $version_precedente_B != null ) $attr_heures_rallonge   += $version_precedente_B->getAttrHeuresRallonge();

            $penal_heures              = 0;
            if( $version_precedente_A != null ) $penal_heures   += $version_precedente_A->getPenalHeures();
            if( $version_precedente_B != null ) $penal_heures   += $version_precedente_B->getPenalHeures();

            $dem_heures_A              = 0;
            if( $version_courante_A != null ) $dem_heures_A += $version_courante_A->getDemHeures();

            $attr_heures_A             = 0;
            if( $version_courante_A != null ) $attr_heures_A +=
                $version_courante_A->getAttrHeures() + $version_courante_A->getAttrHeuresRallonge() - $version_courante_A->getPenalHeures();

			$consoRessource = $projet->getConsoRessource('cpu',$full_annee_prec);
			$conso          = $consoRessource[0];
			$quota          = $consoRessource[1];
			$conso_gpu      = $projet->getConsoRessource('gpu',$full_annee_prec)[0];
            $dem_heure_cour = $version->getDemHeures();
            $attr_heure_cour= $version->getAttrHeures();
			$recuperable    = 0;
			$stockRessource = $projet->getConsoRessource($ress,$full_annee_prec);
			$conso_stock    = $stockRessource[0];	// Occupation de l'espace-disque
			$quota_stock    = $stockRessource[1];	// Quota de disque
			if ($quota_stock != 0)
			{
				$totaux['conso_stock'] += $conso_stock;
				$totaux['quota_stock'] += $quota_stock;
				$conso_stock = intval(100 * $conso_stock/$quota_stock);
				$quota_stock = intval($quota_stock / $t_fact);
			}
			else
			{
				$conso_stock = 0;
			}

            $ligne =
				[
                    $projet,
                    '"'. $version->getPrjThematique() .'"',
                    '"'.$version->getResponsable() .'"',
                    '"'.$version->getLabo().'"',
                    ( $version->hasRapportActivite() == true ) ? 'OUI' : 'NON',
                    ( $version->getResponsable()->getExpert() ) ? '*******' : $version->getExpert(),
                    $dem_heures_prec,
                    $dem_heures_rallonge,
                    $attr_heures_rallonge,
                    $penal_heures,
                    $attr_heures_prec+$attr_heures_rallonge-$penal_heures,
				];

             $ligne = array_merge( $ligne,
				[
                    $dem_heure_cour,
                    $attr_heure_cour,
                    $quota,
                    $conso,
                    $conso_gpu,
                    $quota != 0  ? intval(round( $conso * 100 /$quota ) ): 0,
                    $quota_stock,
                    $conso_stock
				]);


			for ($m=0;$m<12;$m++)
			{
				$consmois= $version->getProjet()->getConsoMois($full_annee_prec,$m);
				$index   = 'm' . ($m<10?'0':'') . $m;

				$ligne[] = $consmois;
				$totaux[$index] += $consmois;
			};

            $sortie     .=   join("\t",$ligne) . "\n";

            $totaux["dem_heures_prec"]          +=  $dem_heures_prec;
            $totaux["attr_heures_prec"]         +=  $attr_heures_prec;
            $totaux["dem_rall_heures_prec"]     +=  $dem_heures_rallonge;
            $totaux["attr_rall_heures_prec"]    +=  $attr_heures_rallonge;
            $totaux["penal_heures_prec"]        +=  $penal_heures;
            $totaux["dem_heures_cour"]          +=  $dem_heure_cour;
            $totaux["attr_heures_cour"]         +=  $attr_heure_cour;
            $totaux["quota"]                    +=  $quota;
            $totaux["conso_an"]                 +=  $version->getConsoCalcul(); //( $consommation != null ) ? $consommation->conso(): 0;
            $totaux["conso_gpu"]                +=  $conso_gpu;
            $totaux["recuperable"]              +=  $recuperable;

		} // fin de la boucle principale

        $ligne  =
			[
			'TOTAL','','','','','',
			$totaux["dem_heures_prec"],
			$totaux["dem_rall_heures_prec"],
			$totaux["attr_rall_heures_prec"],
			$totaux["penal_heures_prec"],
			$totaux["attr_heures_prec"]+$totaux["attr_rall_heures_prec"]-$totaux["penal_heures_prec"],
			];

		$totaux_quota_stock = intval($totaux['quota_stock']/$t_fact);
		$totaux_conso_stock = intval($totaux['conso_stock']/$t_fact);
		$ligne  =  array_merge( $ligne,
			[
			$totaux["dem_heures_cour"],
			$totaux["attr_heures_cour"],
			$totaux["quota"],
			$totaux["conso_an"],
			$totaux["conso_gpu"],
			'', // %
			"$totaux_quota_stock (To)",
			"$totaux_conso_stock (To)",
			]);

		$ligne  = array_merge( $ligne,
			[
			$totaux["m00"],$totaux["m01"],$totaux["m02"],$totaux["m03"],$totaux["m04"],$totaux["m05"],
			$totaux["m06"],$totaux["m07"],$totaux["m08"],$totaux["m09"],$totaux["m10"],$totaux["m11"],
			]);

        $sortie .= join("\t",$ligne) . "\n";

        return Functions::csv($sortie,'bilan_session_'.$session->getIdSession().'.csv');
    }

    // type session A ou B
    public static function typeSession(Session $session)
    {
        return substr($session->getIdSession(),-1);
    }

    // années
    public static function codeSession(Session $session)
    {
        return intval(substr($session->getIdSession(),0,-1));
    }

    /********************
    * calc_recup_heures_printemps
    * Si le projet a eu beaucoup d'heures attribuées mais n'en a consommé que peu,
    * on récupère une partie de son attribution
    * cf. la règle 4
    *      param $conso  = Consommation
    *      param $attrib = Attribution
    *      return $recup = Heures pouvant être récupérées
    *
    *********************/
    public static function calc_recup_heures_printemps( $conso, $attrib)
    {
       $recup_heures = 0;
       if( $attrib <= 0 ) return 0;

       if(   ! AppBundle::hasParameter('recup_attrib_seuil')
          || ! AppBundle::hasParameter('recup_conso_seuil')
          || ! AppBundle::hasParameter('recup_attrib_quant' )
          )
          return 0;

        if ( $attrib >= AppBundle::getParameter('recup_attrib_seuil'))
        {
            $conso_rel = (100.0 * $conso) / $attrib;
            if ( $conso_rel <  AppBundle::getParameter('recup_conso_seuil') )
                $recup_heures = $attrib * AppBundle::getParameter('recup_attrib_quant' ) / 100;
        }
        return $recup_heures;
    }

	/********************************
	* calc_recup_heures_automne
    * Si le projet a consommé moins d'heures en été que demandé par le comité,
    * on récupère ce qui n'a pas été consommé
    *
    * param $conso_ete  = La consommation pour Juillet et Août
    * param $attrib_ete = L'attribution pour l'été
    * return $recup     = Heures pouvant être récupérées
    **********************************/
    public static function calc_recup_heures_automne( $conso_ete, $attrib_ete )
    {
       $recup_heures = 0;
       if( $attrib_ete <= 0 ) return 0;

       if ( $conso_ete < $attrib_ete ) {
          $recup_heures = $attrib_ete - $conso_ete;
          $recup_heures = 1000 * ( intval($recup_heures / 1000 ));
       }
       return $recup_heures;
    }

}
