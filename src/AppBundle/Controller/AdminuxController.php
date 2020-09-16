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

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
//use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use AppBundle\AppBundle;
use AppBundle\Utils\Functions;
use AppBundle\Utils\GramcDate;
use AppBundle\Utils\Etat;

use AppBundle\Entity\Projet;
use AppBundle\Entity\Version;
use AppBundle\Entity\Session;
use AppBundle\Entity\Individu;
use AppBundle\Entity\CollaborateurVersion;
use AppBundle\Entity\Compta;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * AdminUx controller: Commandes curl envoyées par l'administrateur unix
 *
 * @Route("/adminux")
 * @Security("has_role('ROLE_ADMIN')")
 */
class AdminuxController extends Controller
{
    /**
     * Met à jour les données de comptabilité à partir d'un unique fichier csv
     *
     * format date, loginname, ressource, type, consommation, quota
     * ressource = cpu, gpu, home, etc.
     * type      = user ou group unix
     * @Route("/compta_update_batch", name="compta_update_batch")
     * @Method({"PUT"})
     */
     public function UpdateComptaBatchAction(Request $request)
     {
        if ( AppBundle::getParameter('noconso')==true )
        {
			throw new AccessDeniedException("Forbidden because of parameter noconso");
		}
        $conso_repository = AppBundle::getRepository(Compta::class);
        $em = AppBundle::getManager();

        $putdata = fopen("php://input", "r");
        //$input = [];

        while ( $ligne  =   fgetcsv($putdata) )
        {
            if( sizeof( $ligne ) < 5 ) continue; // pour une ligne erronée ou incomplète

            $date       =   $ligne[0]; // 2019-02-05
            $date       =   new \DateTime( $date . "T00:00:00");
            $loginname  =   $ligne[1]; // login
            $ressource  =   $ligne[2]; // cpu, gpu, ...
            $type   =   $ligne[3]; // user, group
            if ($type=="user") {
                $type_nb = Compta::USER;
            } else if ($type=="group") {
                $type_nb = Compta::GROUP;
            } else {
                return new Response('KO');
            }

            $compta =  $conso_repository->findOneBy( [ 'date' => $date, 'loginname' =>  $loginname,  'ressource' => $ressource, 'type' => $type_nb ] );
            if ( $compta == null ) // new item
            {
                $compta = new Compta();
                $compta->setDate( $date );
                $compta->setLoginname( $loginname );
                $compta->setRessource( $ressource );
                $compta->setType( $type_nb );
                $em->persist( $compta );
            }

            $conso  =   $ligne[4]; // consommation

            if( array_key_exists( 5, $ligne ) )
                $quota  =   $ligne[5]; // quota
            else
                $quota  =   -1;


            $compta->setConso( $conso );
            $compta->setQuota( $quota );

            //$input[]    =   $compta;
            //return new Response( Functions::show( $ligne ) );
        }

        try
        {
            $em->flush();
        }
        catch (\Exception $e)
        {
            return new Response('KO');
        }

        //return new Response( Functions::show( $conso_repository->findAll() ) );

        return $this->render('consommation/conso_update_batch.html.twig');
    }


    ///////////////////////////////////////////////////////////////////////////////

   /**
     * set loginname
     *
     * @Route("/users/setloginname/{idProjet}/projet/{idIndividu}/individu/{loginname}/loginname", name="set_loginname")
     * @Method({"POST"})
     */
	public function setloginnameAction(Request $request, $idProjet, $idIndividu, $loginname)
	{
		if ( AppBundle::getParameter('noconso')==true )
		{
			throw new AccessDeniedException("Accès interdit (paramètre noconso)");
		}
	    $error = [];
	    $projet      = AppBundle::getRepository(Projet::class)->find($idProjet);
	    if( $projet == null )
	       $error[]    =   'No Projet ' . $idProjet;

	    $individu       =   AppBundle::getRepository(Individu::class)->find($idIndividu);
	    if( $individu == null )
	        $error[]    =   'No Individu ' . $idIndividu;

	    if ( $error != [] )
	        return new Response( json_encode( ['KO' => $error ]) );

	    $versions = $projet->getVersion();
	    foreach( $versions as $version )
	        if( $version->getEtatVersion() == Etat::ACTIF)
			{
	            foreach( $version->getCollaborateurVersion() as $collaborateurVersion )
				{
	                $collaborateur  =  $collaborateurVersion->getCollaborateur() ;
	                if( $collaborateur != null && $collaborateur->isEqualTo( $individu ) )
					{
	                    $collaborateurVersion->setLoginname( $loginname );
	                    Functions::sauvegarder( $collaborateurVersion );
	                    return new Response(json_encode('OK'));
					}
				}
			}
			return new Response(json_encode( ['KO' => 'No user found' ]));
     }

	/**
	 * get versions non terminées
	 *
	 * @Route("/version/get", name="get_version")
	 *
	 * Exemples de données POST (fmt json):
	 * 			   ''
	 *             ou
	 *             '{ "projet" : null,     "session" : null }' -> Toutes les VERSIONS ACTIVES quelque soit la session
	 *
	 *             '{ "projet" : "P01234" }'
	 *             ou
	 *             '{ "projet" : "P01234", "session" : null }' -> LA VERSION ACTIVE du projet P01234
	 *
	 *             '{ "session" : "20A"}
	 *             ou
	 *             '{ "projet" : null,     "session" : "20A"}' -> Toutes les versions de la session 20A
	 *
	 *             '{ "projet" : "P01234", "session" : "20A"}' -> La version 20AP01234
	 *
	 * Donc on renvoie une ou plusieurs versions appartenant à différentes sessions, mais une ou zéro versions par projet
	 * Les versions renvoyées peuvent être en état: ACTIF, EN_ATTENTE, NOUVELLE_VERSION_DEMANDEE si "session" vaut null
	 * Les versions renvoyées peuvent être dans n'importe quel état (sauf ANNULE) si "session" est spécifiée
	 *
	 * Données renvoyées (fmt json):
	 * 			    idProjet	P01234
	 * 				idSession	20A
	 * 				idVersion	20AP01234
	 * 				mail		mail du responsable de la version
	 * 				attrHeures	Heures cpu attribuées
	 * 				quota		Quota sur la machine
	 * 				gpfs		sondVolDonnPerm stockage permanent demandé (pas d'attribution pour le stockage)
	 *
	 * @Method({"POST"})
	 *
	 */

	 public function versionGetAction(Request $request)
	 {
		$em = $this->getDoctrine()->getManager();
		$versions = [];

		$content  = json_decode($request->getContent(),true);
		if ($content == null)
		{
			$id_projet = null;
			$id_session= null;
		}
		else
		{
			$id_projet  = (isset($content['projet'])) ? $content['projet'] : null;
			$id_session = (isset($content['session']))? $content['session']: null;
		}

		$v_tmp = [];
		// Tous les projets actifs
		if ($id_projet == null && $id_session == null)
		{
			$sessions = $em->getRepository(Session::class)->get_sessions_non_terminees();
			foreach ($sessions as $sess)
			{
				//$versions = $em->getRepository(Version::class)->findSessionVersionsActives($sess);
				$v_tmp = array_merge($v_tmp,$em->getRepository(Version::class)->findSessionVersions($sess));
			}
		}

		// Tous les projets d'une session particulière  (on filtre les projets annulés)
		elseif ($id_projet == null)
		{
			$sess  = $em->getRepository(Session::class)->find($id_session);
			$v_tmp = $em->getRepository(Version::class)->findSessionVersions($sess);
		}

		// La version active d'un projet donné
		elseif ($id_session == null)
		{
			$projet = $em->getRepository(Projet::class)->find($id_projet);
			if ($projet != null) $v_tmp[]= $projet->getVersionActive();
		}

		// Une version particulière
		else
		{
			$projet = $em->getRepository(Projet::class)->find($id_projet);
			$sess  = $em->getRepository(Session::class)->find($id_session);
			$v_tmp[] = $em->getRepository(Version::class)->findOneVersion($sess,$projet);
		}

		// SEULEMENT si session n'est pas spécifié: On ne garde que les versions actives... ou presque actives
		if ( $id_session == null )
		{
			$etats = [Etat::ACTIF, Etat::EN_ATTENTE, Etat::NOUVELLE_VERSION_DEMANDEE, Etat::ACTIF_TEST];
			foreach ($v_tmp as $v)
			{
				if ($v == null) continue;
				if ($v->getSession()->getEtatSession() != Etat::TERMINE)
				{
					if (in_array($v->getEtatVersion(),$etats,true))
					//if ($v->getProjet()->getMetaEtat() === 'ACCEPTE' || $v->getProjet()->getMetaEtat() === 'NONRENOUVELE')
					{
						$versions[] = $v;
					}
				}
			}
		}

		// Si la session est spécifiée: On renvoie la version demandée, quelque soit son état
		// On renvoie aussi l'état de la version et l'état de la session
		else
		{
			$versions = $v_tmp;
		}

		$retour = [];
		foreach ($versions as $v)
		{
			if ($v==null) continue;
			$annee = 2000 + $v->getSession()->getAnneeSession();
			$attr  = $v->getAttrHeures() - $v->getPenalHeures();
			foreach ($v->getRallonge() as $r)
			{
				$attr += $r->getAttrHeures();
			}

			// Pour une session de type B = Aller chercher la version de type A correspondante et ajouter les attributions
			// TODO - Des fonctions de haut niveau (au niveau projet par exemple) ?
			if ($v->getSession()->getTypeSession())
			{
				$id_va = $v->getAutreIdVersion();
				$va = $em->getRepository(Version::class)->find($id_va);
				if ($va != null)
				{
					$attr += $va->getAttrHeures();
					$attr -= $va->getPenalHeures();
					foreach ($va->getRallonge() as $r)
					{
						$attr += $r->getAttrHeures();
					}
				}
			}
			$r = [];
			$r['idProjet']        = $v->getProjet()->getIdProjet();
			$r['idSession']       = $v->getSession()->getIdSession();
			$r['idVersion']       = $v->getIdVersion();
			$r['etatVersion']     = $v->getEtatVersion();
			$r['etatProjet']      = $v->getProjet()->getEtatProjet();
			$r['mail']            = $v->getResponsable()->getMail();
			$r['attrHeures']      = $attr;
			$r['sondVolDonnPerm'] = $v->getSondVolDonnPerm();
			$r['quota']			  = $v->getProjet()->getConsoRessource('cpu',$annee)[1];
			// Pour le déboguage
			// if ($r['quota'] != $r['attrHeures']) $r['attention']="INCOHERENCE";

			$retour[] = $r;
			//$retour[] = $v->getIdVersion();
		};

		// print_r est plus lisible pour le déboguage
		// return new Response(print_r($retour,true));
		return new Response(json_encode($retour));

	 }

	/**
	 * get users
	 *
	 * @Route("/users/get", name="get_users")
	 *
	 * Exemples de données POST (fmt json):
	 * 			   ''
	 *             ou
	 *             '{ "projet" : null,     "mail" : null }' -> Tous les collaborateurs avec login
	 *
	 *             '{ "projet" : "P01234" }'
	 *             ou
	 *             '{ "projet" : "P01234", "mail" : null }' -> Tous les collaborateurs avec login du projet P01234 (version ACTIVE)
	 *
	 *             '{ "mail" : "toto@exemple.fr"}
	 *             ou
	 *             '{ "projet" : null,     "mail" : "toto@exemple.fr"}' -> Tous les projets dans lesquels ce collaborateur a un login (version ACTIVE de chaque projet)
	 *
	 *             '{ "projet" : "P01234", "mail" : "toto@exemple.fr" }' -> rien ou toto si toto avait un login sur ce projet
	 *
	 * Par défaut on ne considère QUE les version actives de CHAQUE PROJET
	 * MAIS si on AJOUTE un PARAMETRE "session" : "20A" on travaille sur la session passée en paramètres (ici 20A)
	 *
	 * On renvoie pour chaque projet, ou pour un projet donné, la liste des collaborateurs qui doivent avoir un login
	 *
	 * Données renvoyées (fmt json):
	 * 				mail		toto@exemple.fr
	 * 				idIndividu	75
	 * 				nom			Toto
	 * 				prenom		Ernest
	 * 			    idProjet	P01234
	 * 				login		toto
	 * 			    idProjet	P56789
	 * 				login		titi
	 *
	 * @Method({"POST"})
	 *
	 */

	// curl --netrc -H "Content-Type: application/json" -X POST  -d '{ "projet" : "P0044", "mail" : null }' https://attribution-ressources-dev.calmip.univ-toulouse.fr/gramc2-manu/adminux/users/get

	 public function usersGetAction(Request $request)
	 {
		$em = $this->getDoctrine()->getManager();
		$content  = json_decode($request->getContent(),true);
		if ($content == null)
		{
			$id_projet = null;
			$mail      = null;
		}
		else
		{
			$id_projet  = (isset($content['projet'])) ? $content['projet'] : null;
			$mail       = (isset($content['mail']))? $content['mail']: null;
			$id_session = (isset($content['session']))? $content['session']: null;
		}

//		$sessions  = $em->getRepository(Session::class)->get_sessions_non_terminees();
		$users = [];
		$projets   = [];

		// Tous les collaborateurs de tous les projets non terminés
		if ($id_projet == null && $mail == null)
		{
			$projets = $em->getRepository(Projet::class)->findNonTermines();
		}

		// Tous les projets dans lesquels une personne donnée a un login
		elseif ($id_projet == null)
		{
			$projets = $em->getRepository(Projet::class)->findNonTermines();
		}

		// Tous les collaborateurs d'un projet
		elseif ($mail == null)
		{
			$p = $em->getRepository(Projet::class)->find($id_projet);
			if ($p != null)
			{
				$projets[] = $p;
			}
		}

		// Un collaborateur particulier d'un projet particulier
		else
		{
			$p = $em->getRepository(Projet::class)->find($id_projet);
			if ($p->getEtatProjet() != Etat::TERMINE)
			{
				$projets[] = $p;
			}
		}

		//
		// Construire le tableau $users:
		//      toto@exemple.com => [ 'idIndividu' => 34, 'nom' => 'Toto', 'prenom' => 'Ernest', 'projets' => [ 'p0123' => 'toto', 'p456' => 'toto1' ] ]
		//
		foreach ($projets as $p)
		{
			// Si session non spécifiée, on prend la version active de chaque projet !
			if ($id_session==null)
			{
				$v = $p->getVersionActive();
			}

			// Sinon, on prend la version de cette session... si elle existe
			else
			{
				$id_version = $id_session . $p->getIdProjet();
				$v          = $em->getRepository(Version::class)->find($id_version);
			}

			if ($v != null)
			{
				$collaborateurs = $v->getCollaborateurVersion();
				foreach ($collaborateurs as $c)
				{
					if ($c->getLogin())
					{
						$m = $c -> getCollaborateur() -> getMail();
						if ($mail != null && $mail != $m)
						{
							continue;
						}

						if (!isset($users[$m]))
						{
							$users[$m] = [];
							$users[$m]['nom']        = $c -> getCollaborateur() -> getNom();
							$users[$m]['prenom']     = $c -> getCollaborateur() -> getPrenom();
							$users[$m]['idIndividu'] = $c -> getCollaborateur() -> getIdIndividu();
							$users[$m]['projets']    = [];
						}
						$users[$m]['projets'][$p->getIdProjet()] = $c->getLoginname();
					}
				}
			}
		}

		// print_r est plus lisible pour le déboguage
		//return new Response(print_r($users,true));
		return new Response(json_encode($users));
	 }

    /**
     * set loginname
     *
     * @Route("/getloginnames/{idProjet}/projet", name="get_loginnames")
     * @Method({"GET"})
     */
	public function getloginnamesAction($idProjet)
	{
		if ( AppBundle::getParameter('noconso')==true )
		{
			throw new AccessDeniedException("Accès interdit (paramètre noconso)");
		}
		$projet      = AppBundle::getRepository(Projet::class)->find($idProjet);
	    if( $projet == null )
	    {
			return new Response( json_encode( ['KO' => 'No Projet ' . $idProjet ]) );
	    }

		$versions    = $projet->getVersion();
		$output      =   [];
		$idProjet    =   $projet->getIdProjet();

		foreach( $versions as $version )
		{
	        if( $version->getEtatVersion() == Etat::ACTIF)
	        {
				foreach( $version->getCollaborateurVersion() as $collaborateurVersion )
				{
	                if( $collaborateurVersion->getLogin() == false ) continue;

	                $collaborateur  =  $collaborateurVersion->getCollaborateur() ;
	                if( $collaborateur != null )
                    {
	                    $loginname  =   $collaborateurVersion->getLoginname();
	                    $prenom     =   $collaborateur->getPrenom();
	                    $nom        =   $collaborateur->getNom();
	                    $idIndividu =   $collaborateur->getIdIndividu();
	                    $mail       =   $collaborateur->getMail();
	                    $login      =   $collaborateurVersion->getLogin();
	                    $output[] =   [
	                            'idIndividu' => $idIndividu,
	                            'idProjet' =>$idProjet,
	                            'mail' => $mail,
	                            'prenom' => $prenom,
	                            'nom' => $nom,
	                            'login' => $login,
	                            'loginname' => $loginname,
	                            ];
                    }
                }
			}
		}
	    return new Response( json_encode( $output) );
	}


    /**
     * Vérifie la base de données, et envoie un mail si l'attribution d'un projet est différente du quota
     *
     * @Route("/quota_check", name="quota_check")
     * @Method({"GET"})
     */
     public function quotaCheckAction(Request $request)
     {
		if ( AppBundle::getParameter('noconso')==true )
		{
			throw new AccessDeniedException("Accès interdit (paramètre noconso)");
		}

        $annee_courante=GramcDate::get()->showYear();
        $projets = Functions::projetsParAnnee($annee_courante)[0];

        // projets à problème
        $msg = "";
        foreach ($projets as $p)
        {
            if ($p['attrib'] != $p['q'])
            {
                $msg .= $p['p']->getIdProjet() . "\t" . $p['attrib'] . "\t\t" . $p["q"] . "\n";
            }
        }

        if ($msg != "")
        {
            $dest = Functions::mailUsers( [ 'S' ], null);
            Functions::sendMessage('notification/quota_check-sujet.html.twig','notification/quota_check-contenu.html.twig',[ 'MSG' => $msg ],$dest);
        }

        return $this->render('consommation/conso_update_batch.html.twig');
    }
}

