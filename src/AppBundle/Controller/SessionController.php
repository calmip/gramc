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
use AppBundle\Entity\Projet;
use AppBundle\Entity\Version;

use AppBundle\BilanSession\BilanSessionA;
use AppBundle\BilanSession\BilanSessionB;

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
	    if( Menu::gerer_sessions()['ok'] == false )
	        Functions::createException(__METHOD__ . ':' . __LINE__ . " Ecran interdit " . 
	            " parce que : " . Menu::gerer_sessions()['raison'] );

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
			// Refait le calcul de la session courante sans se fier au cache
            AppBundle::getSession()->remove('SessionCourante');

            $menu[] = Menu::ajouterSession();
			$menu[] = Menu::modifierSession();
			$menu[] = Menu::demarrerSaisie();
            $menu[] = Menu::terminerSaisie();
			$menu[] = Menu::envoyerExpertises();
	        $menu[] = Menu::activerSession();

        }

        return $this->render('session/gerer.html.twig',
		[
            'menu'     => $menu,
            'sessions' => $sessions,
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
     * Avant changement d'état de la version
     *
     * @Route("/avant_changer_etat/{rtn}/{ctrl}", name="session_avant_changer_etat", defaults= {"rtn" = "X" })
     * @Method("GET")
     *
     */
    public function avantActiverAction($rtn,$ctrl)
    {
		$session       =   Functions::getSessionCourante();
		$connexions = Functions::getConnexions();
	    return $this->render('session/avant_changer_etat.html.twig',
            [
            'session'    => $session,
            'ctrl'       => $ctrl,
            'connexions' => $connexions,
            'rtn'        => $rtn,
            ]
		);
    }

    /**
     *
     * @Route("/activer", name="activer_session")
     * @Method("GET")
     */
    public function activerAction(Request $request)
    {
		// Suppression du cache, du coup toutes les personnes connectées seront virées
        AppBundle::getSession()->remove('SessionCourante'); // remove cache

        $session_courante       =   Functions::getSessionCourante();
        $etat_session_courante  =   $session_courante->getEtatSession();

		$em       = $this->getDoctrine()->getManager();
        $sessions = $em->getRepository(Session::class)->findBy([],['idSession' => 'DESC']);

		$ok = false;
        $mois = GramcDate::get()->format('m');

        $workflow = new SessionWorkflow();
        
        // On active une session A
        if( $mois == 1 ||  $mois == 12 )
		{
            if( $workflow->canExecute( Signal::CLK_SESS_DEB, $session_courante) && $etat_session_courante == Etat::EN_ATTENTE )
			{
				// On termine les deux sessions A et B de l'année précédente
                foreach( $sessions as $session )
				{
					// On ne termine pas la session qui va démarrer !
                    if( $session->getIdSession() == $session_courante->getIdSession() ) continue;

                    $workflow   = new SessionWorkflow();
                    if( $workflow->canExecute( Signal::CLK_SESS_FIN, $session) )
                        $err = $workflow->execute( Signal::CLK_SESS_FIN, $session);
				}
                $ok = $workflow->execute( Signal::CLK_SESS_DEB, $session_courante );
                $em->flush();                
			}
		}
		
		// On active une session B
        elseif( $mois == 6 ||  $mois == 7 )
        {
            if( $workflow->canExecute(Signal::CLK_SESS_DEB , $session_courante)  && $etat_session_courante == Etat::EN_ATTENTE )
			{
                $ok = $workflow->execute(Signal::CLK_SESS_DEB , $session_courante );
                AppBundle::getManager()->flush();
			}
		}
		else
		{
			Functions::errorMessage(__METHOD__ . ':' . __LINE__ . " Une session ne peut être activée qu'en Décembre, en Janvier, en Juin ou en Juillet");
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

		$menu[] = Menu::envoyerExpertises();

        if ($editForm->isSubmitted() && $editForm->isValid())
        {
            $this->getDoctrine()->getManager()->flush();
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
			'Demande',
			'Attribution',
			'Consommation',
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
				$version->getDemHeuresTotal(),
				$version->getAttrHeuresTotal(),
				$version->getConsoCalcul(),
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
        $entetes = ['Laboratoire','Nombre de projets','Heures demandées','Heures attribuées','Heure consommées','projets'];
        $sortie  = join("\t",$entetes) . "\n";

        $stats   = Functions::projetsParCritere($annee, 'getAcroLaboratoire');
		$acros         = $stats[0];
		$num_projets   = $stats[1];
		$liste_projets = $stats[2];
		$dem_heures    = $stats[3];
		$attr_heures   = $stats[4];
		$conso         = $stats[5];

        // Calcul du csv
        foreach ($acros as $k)
        {
            $ligne   = [];
            $ligne[] = $k;
            $ligne[] = $num_projets[$k];
            $ligne[] = $dem_heures[$k];
            $ligne[] = $attr_heures[$k];
            $ligne[] = $conso[$k];
            $ligne[] = implode(',',$liste_projets[$k]);
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
			$bilan_session = new BilanSessionA($request, $session);
		}
		else
		{
			$bilan_session = new BilanSessionB($request, $session);
		}

		$csv = $bilan_session->getCsv();

		return Functions::csv($csv[0],$csv[1]);
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
    * TODO -> Mettre cette fonction dans Utils\Functions car elle est appelée depuis plusieurs objets
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
    * TODO -> Mettre cette fonction dans Utils\Functions car elle est appelée depuis plusieurs objets
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
