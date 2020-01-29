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

namespace AppBundle\Utils;

use AppBundle\AppBundle;
use AppBundle\Entity\Journal;
use AppBundle\Entity\Individu;
use AppBundle\Entity\Templates;
use AppBundle\Entity\Session;
use AppBundle\Entity\Version;
use AppBundle\Entity\Projet;
use AppBundle\Entity\Rallonge;
use AppBundle\Entity\RapportActivite;

use AppBundle\Controller\VersionController;
use AppBundle\Controller\VersionModifController;

use AppBundle\Utils\GramcDate;
use AppBundle\Utils\ExactGramcDate;

use Symfony\Component\Security\Core\Exception\InsufficientAuthenticationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use Doctrine\ORM\ORMException;
use Doctrine\DBAL\DBALException;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use AppBundle\Workflow\Session\SessionWorkflow;



/***
 * class Menu = Permet d'afficher des liens html vers différents controleurs.
 *              Chaque fonction renvoit un tableau qui sera repris pour affichage par le twig
 *              Voir la macro menu dans macros.html.twig
 *
 * Clés du tableau menu:
 *      name             -> Nom du controleur symfony
 *      lien             -> Texte du lien html
 *      commentaire      -> Ce que fait le controleur en une phrase
 *      ok               -> Si true le lien est actif sinon le lien est inactif
 *      reason           -> Si le lien est inactif, explication du pourquoi. Pas utilisé si le lien est inactif
 *      todo (optionnel) -> Si le lien est actif, permet de visualiser le menu sous forme de todo liste - cf. consulter.html.twig, vers la ligne 20
 *
 *****************************************************************************************************************************************************/

class Menu
{

	/*******************
	 * Gestion de la session 
	 ***************************************************/

	// Nouvelle session
	public static function ajouterSession()
	{
        $session      = Functions::getSessionCourante();
        $etat_session = $session->getEtatSession();
        $workflow     = new SessionWorkflow();
        $menu['name']            = 'ajouter_session';
        $menu['lien']            = "Nouvelle session";
        if ($etat_session === Etat::ACTIF)
        {
			$menu['ok']          = true;
			$menu['commentaire'] = 'Nouvelle session';
			return $menu;
		}
		else
		{
			$menu['commentaire'] = "Pas possible de créer une nouvelle session";
			$menu['ok']          = false;
			$menu['raison']      = "La session courante n'est pas encore activée";
			return $menu;
		}
	}         

	// Modifier la session
	public static function modifierSession()
	{
        $session = Functions::getSessionCourante();
        $workflow   = new SessionWorkflow();
        $menu['name']            = 'modifier_session';
        $menu['params']          = [ 'id' => $session->getIdSession() ];
        $menu['lien']            = "Modifier la session";
        if( $workflow->canExecute( Signal::DAT_DEB_DEM, $session)  )
        {
			$menu['ok']          = true;
			$menu['commentaire'] = 'Modifier les paramètres de la session';
			return $menu;
		}
		else
		{
			$menu['commentaire'] = 'Pas possible de modifier la session.';
			$menu['ok']          = false;
			$menu['raison']      = 'La session a déjà démarré !';
			return $menu;
		}
	}         

	// Début de la saisie
	public static function demarrerSaisie()
	{
        $session = Functions::getSessionCourante();
        $workflow       = new SessionWorkflow();
        $menu['name']   = 'session_avant_changer_etat';
        $menu['lien']   = "Début de la saisie";
		$menu['params'] = [ 'ctrl' => 'demarrer_saisie'];

        if( $workflow->canExecute( Signal::DAT_DEB_DEM, $session)  )
        {
			$menu['ok']          = true;
			$menu['commentaire'] = 'Début de la saisie des demandeurs';
			return $menu;
		}
		else
		{
			$menu['commentaire'] = 'Pas possible de débuter la saisie des projets';
			$menu['ok']          = false;
			$menu['raison']      = 'La période est déjà passée !';
			return $menu;
		}
	}         
 		
	// Fin de la saisie
	public static function terminerSaisie()
	{
        $session = Functions::getSessionCourante();
        $workflow   = new SessionWorkflow();
        $menu['name']   = 'session_avant_changer_etat';
        $menu['lien']   = "Fin de la saisie";
		$menu['params'] = [ 'ctrl' => 'terminer_saisie'];

        if( $workflow->canExecute( Signal::DAT_FIN_DEM, $session)  )
        {
			$menu['ok']          = true;
			$menu['commentaire'] = 'Fin de la saisie des demandeurs';
			return $menu;
		}
		else
		{
			$menu['commentaire'] = 'Pas possible de terminer la saisie des projets';
			$menu['ok']          = false;
			$menu['raison']      = 'La session n\'est pas en période de saisie des projets';
			return $menu;
		}
	}

	// Envoyer les expertises
	public static function envoyerExpertises()
	{
        $session = Functions::getSessionCourante();
        $workflow   = new SessionWorkflow();
        $menu['name']            = 'session_avant_changer_etat';
        $menu['lien']            = 'Envoi des expertises';
        $menu['params'] = [ 'ctrl' => 'envoyer_expertises'];

		if( $workflow->canExecute( Signal::CLK_ATTR_PRS, $session)  &&  $session->getcommGlobal() != null )
		{
            $menu['ok']          = true;
            $menu['commentaire'] = "Envoyer les expertises";
            return $menu;
		}
		else
		{
			$menu['ok']          = false;
			$menu['commentaire'] = "Impossible d'envoyer les expertises";
			if( $session->getCommGlobal() == null )
			{
				$menu['raison']  = "Il n'y a pas de commentaire de session (menu Président)";
			}
            else
            {
				$menu['raison']  = "La session n'est pas en \"expertise\"";
			}
			return $menu;
		}
	}
	
	// Commentaire de session - accessible à partir de l'écran Président
    public static function commSess()
    {
        $session = Functions::getSessionCourante();
        $workflow   = new SessionWorkflow();

        $menu['name']            = 'session_commentaires';
        $menu['lien']            = "Commentaire de session ($session)";

        if( !AppBundle::isGranted('ROLE_PRESIDENT') )
        {
	        $menu['ok']          = false;
	        $menu['commentaire'] = "Vous ne pouvez pas ajouter le commentaire de session";
            $menu['raison']      = "Vous n'êtes pas président";
            return $menu;
        }
        if ( ! $workflow->canExecute( Signal::CLK_ATTR_PRS, $session) )
        {
	        $menu['ok']          = false;
	        $menu['commentaire'] = "Vous ne pouvez pas ajouter le commentaire de session";
            $menu['raison']      = "La session n'est pas en phase d'expertise";
	        return $menu;
        }
        else
        {
            $menu['ok']          = true;
            $menu['commentaire'] = "Commentaire de session et fin de la phase d'expertise";
	        return $menu;
        }
    }

	// Activer la session
    public static function activerSession()
    {
        $session = Functions::getSessionCourante();
        $workflow   = new SessionWorkflow();

        $menu['name']           = 'session_avant_changer_etat';
        $menu['lien']           = "Activer la session";
        $menu['params']         = [ 'ctrl' => 'activer_session'];

        if ( ! $workflow->canExecute( Signal::CLK_SESS_DEB, $session))
        {
	        $menu['ok']          = false;
			$menu['commentaire'] = "Vous ne pouvez pas activer la session";
            $menu['raison']      = "Le commentaire de session n'a pas été envoyé, ou la session est déjà active";
            return $menu;
        }
        else
        {
            $menu['ok']          = true;
            $menu['commentaire'] = "Activer la session $session";
            return $menu;
        }
    }


	/*******************
	 * Gestion des projets et des versions 
	 ***************************************************/

	public static Function nouveau_projet($type)
	{
        $prj_prefix = AppBundle::getParameter('prj_prefix');
        if (!isset($prj_prefix[$type] ))
        {
			return null;
		}

		switch ($type)
		{
			case Projet::PROJET_FIL:
				return self::nouveau_projet_fil();
				break;
			case Projet::PROJET_SESS:
				return self::nouveau_projet_sess();
				break;
			case Projet::PROJET_TEST:
				return self::nouveau_projet_test();
				break;
		}
	}

	/*
	 * Création d'un projet de type PROJET_SESS:
	 *     - Peut être créé seulement lors des sessions d'attribution
	 *     - Renouvelable à chaque session
	 *     - Créé seulement par un permanent, qui devient responsable du projet
	 *
	 */
    private static function  nouveau_projet_sess()
    {
        $menu   =   [];
        $menu['commentaire']    =   "Vous ne pouvez pas créer de nouveau projet actuellement";
        $menu['name']   =   'avant_nouveau_projet';
        $menu['params'] =   [ 'type' =>  Projet::PROJET_SESS ];
        $menu['lien']   =   'Nouveau projet';
        $menu['ok'] = false;

        $session =  Functions::getSessionCourante();
        if( $session == null )
		{
            $menu['raison'] = "Il n'y a pas de session courante";
            return $menu;
		}

        $etat_session   =   $session->getEtatSession();

        if(  ! self::peut_creer_projets() )
        {
            $menu['raison'] = "Seuls les personnels permanents des laboratoires enregistrés peuvent créer un projet";
		}
        elseif( $etat_session == Etat::EDITION_DEMANDE )
		{
            $menu['raison'] = '';
            $menu['commentaire'] = "Créez un nouveau projet, vous en serez le responsable";
            $menu['ok'] = true;
		}
        else
            $menu['raison'] = 'Nous ne sommes pas en période de demande, pas possible de créer un nouveau projet';

        return $menu;
    }

	/*
	 * Création d'un projet de type PROJET_TEST:
	 *     - Peut être créé seulement EN-DEHORS des sessions d'attribution
	 *     - Non renouvelable
	 *     - Créé par n'importe qui, qui devient responsable du projet
	 *
	 */
    public static function  nouveau_projet_test()
    {
        $menu   =   [];
        $menu['commentaire']    =   "Vous ne pouvez pas créer de nouveau projet test actuellement";
        $menu['name']   =   'nouveau_projet';
        $menu['params'] =   [ 'type' =>  Projet::PROJET_TEST ];
        $menu['lien']   =   'Nouveau projet test';
        $menu['ok'] = false;

        $session =  Functions::getSessionCourante();
        if( $session == null )
		{
            $menu['raison'] = "Il n'y a pas de session courante";
            return $menu;
		}

        $etat_session   =   $session->getEtatSession();
        //Functions:: debugMessage(__METHOD__ . ':' . __LINE__ . "countProjetsTestResponsable = " .
        //     AppBundle::getRepository(Projet::class)->countProjetsTestResponsable( AppBundle::getUser() ));

        if( ! AppBundle::getUser() instanceof Individu )
            $menu['raison'] = "Vous n'êtes pas connecté";
        elseif( AppBundle::getRepository(Projet::class)->countProjetsTestResponsable( AppBundle::getUser() ) > 0 )
            $menu['raison'] = "Vous êtes déjà responsable d'un projet test";
        // manu, 11 juin 2019: tout le monde peut créer un projet test. Vraiment ???
        //elseif( ! self::peut_creer_projets() )
        //    $menu['raison'] = "Vous n'avez pas le droit de créer un projet test, peut-être faut-il mettre à jour votre profil ?";
        elseif( $etat_session == Etat::EDITION_DEMANDE )
            $menu['raison'] = "Il n'est pas possible de créer un projet test en période d'attribution";
        else
            {
            $menu['commentaire'] = "Créer un projet test: 5000h max, uniquement pour faire des essais et avoir une idée du nombre d'heures dont vous avez besoin.";
            $menu['ok'] = true;
            }

        return $menu;
    }

	/*
	 * Création d'un projet de type PROJET_FIL:
	 *     - Peut être créé n'importe quand ("au fil de l'eau")
	 *     - Renouvelable seulement à chaque session
	 *     - Créé seulement par un permanent, qui devient responsable du projet
	 *
	 */
    private static function  nouveau_projet_fil()
    {
        $menu   =   [];

        $menu['commentaire']    =   "Vous ne pouvez pas créer de nouveau projet actuellement";
        $menu['name']   =   'avant_nouveau_projet';
        $menu['params'] =   [ 'type' =>  Projet::PROJET_FIL ];
        $menu['lien']   =   'Nouveau projet';
        $menu['ok']     = false;

        $session =  Functions::getSessionCourante();
        if( $session == null )
		{
            $menu['raison'] = "Il n'y a pas de session courante";
            return $menu;
		}

        $etat_session   =   $session->getEtatSession();
        if(  ! self::peut_creer_projets() )
        {
            $menu['raison'] = "Seuls les personnels permanents des laboratoires enregistrés peuvent créer un projet";
		}
        elseif( $etat_session != Etat::EDITION_EXPERTISE )
		{
            $menu['raison'] = '';
            $menu['commentaire'] = "Créez un nouveau projet, vous en serez le responsable";
            $menu['ok'] = true;
		}
        else
            $menu['raison'] = 'La période de saisie des projets est terminée, pas possible de créer un nouveau projet pour l\'instant';

        return $menu;
    }

	////////////////////////////////

    public static function peut_creer_projets($user = null)
    {
    if( $user == null ) $user   =   AppBundle::getUser();

    if( $user != null && $user->peut_creer_projets() )
        return true;
    else
        return false;
    }

    //////////////////////////////////////

    // Menu principal Admin

    //////////////////////////////////////

    public static function individu_gerer()
    {
    $menu['name']   =   'individu_gerer';
    $menu['commentaire']    =   "Gérer les utilisateurs de gramc";
    $menu['lien']           =   "Utilisateurs";

    if( AppBundle::isGranted('ROLE_ADMIN') )
        {
        $menu['ok'] = true;
        }
    else
        {
        $menu['ok'] = false;
        $menu['raison'] = "Vous n'êtes pas un administrateur";
        }

    return $menu;
    }

    //////////////////////////////////////

    public static function gerer_sessions()
    {
	    $menu['name']   =   'gerer_sessions';
	    $menu['commentaire']    =   "Gérer les sessions d'attribution";
	    $menu['lien']           =   "Sessions";
	
	
	    if( AppBundle::isGranted('ROLE_ADMIN') )
        {
	        $menu['ok'] = true;
        }
	    else
        {
	        $menu['ok'] = false;
	        $menu['raison'] = "Vous devez être un administrateur ou président pour accéder à cette page";
        }

	    return $menu;
    }

    //////////////////////////////////////

    public static function bilan_session()
    {
    $menu['name']   =   'bilan_session';
    $menu['commentaire']    =   "Générer et télécharger le bilan de session";
    $menu['lien']           =   "Bilan de session";


    if( AppBundle::isGranted('ROLE_OBS') || AppBundle::isGranted('ROLE_PRESIDENT') )
        {
        $menu['ok'] = true;
        }
    else
        {
        $menu['ok'] = false;
        $menu['raison'] = "Vous devez être un administrateur ou président pour accéder à cette page";
        }

    return $menu;
    }

    //////////////////////////////////////

    public static function bilan_annuel()
    {
        $menu['name']   =   'bilan_annuel';
        $menu['commentaire']    =   "Générer et télécharger le bilan annuel";
        $menu['lien']           =   "Bilan annuel";


        if( AppBundle::isGranted('ROLE_OBS') || AppBundle::isGranted('ROLE_PRESIDENT') )
        {
            $menu['ok'] = true;
        }
        else
        {
            $menu['ok'] = false;
            $menu['raison'] = "Vous devez être un administrateur ou président pour accéder à cette page";
        }

        return $menu;
    }

    //////////////////////////////////////

    public static function projet_session()
    {
        $menu['name']   =   'projet_session';
        $menu['commentaire']    =   "Gérer les projets par session";
        $menu['lien']           =   "Projets ( par session )";

        if( AppBundle::isGranted('ROLE_OBS') || AppBundle::isGranted('ROLE_PRESIDENT') )
        {
            $menu['ok'] = true;
        }
        else
        {
            $menu['ok'] = false;
            $menu['raison'] = "Vous devez être un administrateur ou président pour accéder à cette page";
        }

        return $menu;
    }

    //////////////////////////////////////

    public static function projet_annee()
    {
        $menu['name']        =   'projet_annee';
        $menu['commentaire'] =   "Gérer les projets par année";
        $menu['lien']        =   "Projets ( par année )";

        if( AppBundle::isGranted('ROLE_OBS') || AppBundle::isGranted('ROLE_PRESIDENT') )
        {
            $menu['ok'] = true;
        }
        else
        {
            $menu['ok'] = false;
            $menu['raison'] = "Vous devez être un administrateur ou président pour accéder à cette page";
        }

        return $menu;
    }

    //////////////////////////////////////

    public static function projet_donnees()
    {
        $menu['name']        =   'projet_donnees';
        $menu['commentaire'] =   "Projets ayant des besoins en stockage ou partage de données";
        $menu['lien']        =   "Données (par année)";

        if( AppBundle::isGranted('ROLE_OBS') || AppBundle::isGranted('ROLE_PRESIDENT') )
        {
            $menu['ok'] = true;
        }
        else
        {
            $menu['ok'] = false;
            $menu['raison'] = "Vous devez être un administrateur ou président pour accéder à cette page";
        }

        return $menu;
    }
    //////////////////////////////////////

    public static function projet_tous()
    {
    $menu['name']   =   'projet_tous';
    $menu['commentaire']    =   "Liste complète des projets";
    $menu['lien']           =   "Tous les projets";


    if( AppBundle::isGranted('ROLE_OBS') || AppBundle::isGranted('ROLE_PRESIDENT') )
        {
        $menu['ok'] = true;
        }
    else
        {
        $menu['ok'] = false;
        $menu['raison'] = "Vous devez être un administrateur ou président pour accéder à cette page";
        }

    return $menu;
    }

    //////////////////////////////////////

    public static function journal()
    {
    $menu['name']   =   'journal_list';
    $menu['commentaire']    =   "Lire le journal des actions";
    $menu['lien']           =   "Lire le journal";

    if( AppBundle::isGranted('ROLE_ADMIN') )
        {
        $menu['ok'] = true;
        }
    else
        {
        $menu['ok'] = false;
        $menu['raison'] = "Vous devez être un administrateur pour accéder à cette page";
        }

    return $menu;
    }

    //////////////////////////////////////

    public static function laboratoires()
    {
	    $menu['name']   =   'gerer_laboratoires';
	    $menu['commentaire']    =   "Gérer la liste des laboratoires enregistrés";
	    $menu['lien']           =   "Laboratoires";

	    if( AppBundle::isGranted('ROLE_OBS') )
		{
	        $menu['ok'] = true;
		}
	    else
		{
	        $menu['ok'] = false;
	        $menu['raison'] = "Vous devez être au moins un observateur pour accéder à cette page";
		}

	    return $menu;
    }

    //////////////////////////////////////

    public static function thematiques()
    {
	    $menu['name']   =   'gerer_thematiques';
	    $menu['commentaire']    =   "Gérer la liste des thématiques";
	    $menu['lien']           =   "Thématiques";

	    if( AppBundle::isGranted('ROLE_OBS') )
		{
	        $menu['ok'] = true;
		}
	    else
		{
	        $menu['ok'] = false;
	        $menu['raison'] = "Vous devez être au moins un observateur pour accéder à cette page";
		}

	    return $menu;
    }

    //////////////////////////////////////

    public static function metathematiques()
    {
	    $menu['name']   =   'gerer_metaThematiques';
	    $menu['commentaire']    =   "Gérer la liste des méta-thématiques";
	    $menu['lien']           =   "Méta-Thématiques";

	    if( AppBundle::isGranted('ROLE_OBS') )
        {
	        $menu['ok'] = true;
        }
	    else
        {
	        $menu['ok'] = false;
	        $menu['raison'] = "Vous devez être au moins un observateur pour accéder à cette page";
        }

	    return $menu;
    }
    //////////////////////////////////////

    // Menu principal Projet

    //////////////////////////////////////

    public static function changer_responsable(Version $version)
    {
        $menu['name']   =   'changer_responsable';
        $menu['param']  =   $version->getIdVersion();
        $menu['lien']   =   "Nouveau responsable";

        if( AppBundle::isGranted('ROLE_ADMIN') )
        {
            $menu['commentaire'] = "Changer le responsable du projet en tant qu'administrateur";
            $menu['raison']      = "L'admininstrateur peut TOUJOURS modifier le responsable d'une version quelque soit son état !";
            $menu['ok']          = true;
            return $menu;
        }

        $menu['ok']          = false;
        $menu['commentaire'] =   "Vous ne pouvez pas changer le responsable de ce projet";

        $session        =   Functions::getSessionCourante();
        $etatVersion    =   $version->getEtatVersion();

        if( $version->getEtatVersion() != Etat::EDITION_DEMANDE )
            $menu['raison']         = "Commencez par demander le renouvellement du projet !";
        elseif( $session->getEtatSession() != Etat::EDITION_DEMANDE && ! $version->isProjetTest() )
            $menu['raison']         = "Nous ne sommes pas en période de demandes de ressources";
        elseif( $etatVersion == Etat::EDITION_EXPERTISE || $etatVersion == Etat::EXPERTISE_TEST )
            $menu['raison']         = "Le projet a déjà été envoyé à l'expert";
        elseif( $etatVersion != Etat::EDITION_DEMANDE && $etatVersion != Etat::EDITION_TEST )
            $menu['raison']         = "Cette version de projet n'est pas en mode édition";
        elseif( ! $version->isResponsable()  )
            $menu['raison']         = "Seul le responsable du projet peut passer la main. S'il n'est pas joignable, merci de nous envoyer un mail";
        else
        {
            $menu['ok']             = true;
            $menu['commentaire']    = "Quitter la responsabilité de ce projet";
        }

        return $menu;
    }


    ////////////////////////////////////

    public static function modifier_version( Version $version )
    {
        $menu['name']   = 'modifier_version';
        $menu['param']  = $version->getIdVersion();
        $menu['lien']   = "Modifier";
        $menu['commentaire']    =   "Vous ne pouvez pas modifier ce projet";
        $menu['ok']          = false;

        if( AppBundle::isGranted('ROLE_ADMIN') )
        {
            $menu['commentaire']    =   "Modifier le projet en tant qu'administrateur";
            $menu['raison']         =   "L'administrateur peut TOUJOURS modifier le projet quelque soit son état !";
            $menu['ok']             = true;
            return $menu;
        }

        $etatVersion    =   $version->getEtatVersion();
        $isProjetTest   =   $version->isProjetTest();

        if( $version == null)
        {
            $menu['raison'] = "Pas de projet à soumettre";
            Functions::errorMessage(__METHOD__ . ' le projet ' . Functions::show( $projet ) . " n'a pas de version !");
        }
        elseif( $version->getSession() == null )
        {
            $menu['raison'] = "Pas de session attachée à ce projet !";
            Functions::errorMessage(__METHOD__ . ' la version ' . Functions::show( $version ) . " n'a pas de session attachée !");
        }
        elseif( $etatVersion ==  Etat::EDITION_EXPERTISE  )
            $menu['raison'] = "Le projet a déjà été envoyé à l'expert !";
        elseif( $isProjetTest == true && $etatVersion ==  Etat::ANNULE )
            $menu['raison'] = "Le projet test a été annulé !";
        elseif( $isProjetTest == true && $etatVersion !=  Etat::EDITION_TEST )
            $menu['raison'] = "Le projet test a déjà été envoyé à l'expert !";
        elseif( $version->getSession()->getEtatSession() != Etat::EDITION_DEMANDE && $isProjetTest == false )
            $menu['raison'] = "Nous ne sommes pas en période de demandes de ressources";
        if( $version->isCollaborateur() == false )
            $menu['raison']         = "Seul un collaborateur du projet peut modifier ou supprimer le projet";
        elseif( $etatVersion !=  Etat::EDITION_DEMANDE && $etatVersion !=  Etat::EDITION_TEST )
            $menu['raison'] = "Le projet n'est pas en mode d'édition";
        else
        {
            $menu['ok']          = true;
            $menu['commentaire'] = "Modifier votre demande de ressources";
            $menu['todo']        = "<strong>Vérifier</strong> le projet et le <strong>compléter</strong> si nécessaire";
        }

        return $menu;
    }

    ///////////////////////////////////////////////////////////

    public static function modifier_collaborateurs( Version $version )
    {
        $menu['name']   =   'modifier_collaborateurs';
        $menu['param']  =   $version->getIdVersion();
        $menu['lien']           =   "Collaborateurs";

        if( AppBundle::isGranted('ROLE_ADMIN') )
        {
            $menu['commentaire']    =   "Modifier les collaborateurs en tant qu'administrateur";
            $menu['ok']             = true;
        }
        elseif( ! $version->isResponsable() )
        {
        	$menu['ok']     = false;
        	$menu['commentaire']     = 'Bouton inactif';
            $menu['raison'] = "Seul le responsable du projet peut ajouter ou supprimer des collaborateurs";
		}
		else
		{
            $menu['ok']          = true;
            $menu['commentaire'] = "Modifier la liste des collaborateurs du projet";
		}
        return $menu;
    }

    //////////////////////////////////////////////////////////////////

    public static function televerser_rapport_annee( Version $version )
    {
    $menu['name']           =   'televerser_rapport_annee';
    $menu['ok']             =   false;

    if( $version != null )
        {
            $etat           = $version->getEtatVersion();
            $menu['param']  = $version->getIdVersion();
            $menu['lien']   = "Téléverser le rapport d'activité pour l'année " . $version->getAnneeSession();

            if( AppBundle::isGranted('ROLE_ADMIN') && ($etat == Etat::ACTIF || $etat == Etat::TERMINE))
            {
                $menu['commentaire'] = "Téléverser un rapport d'activité pour un projet en tant qu'administrateur";
                $menu['raison']      = "L'admininstrateur peut TOUJOURS téléverser un rapport d'activité pour un projet !";
                $menu['ok']          = true;
                return $menu;
            }

            $menu['ok']          = false;
            $menu['commentaire'] = "Vous ne pouvez pas téléverser un rapport d'activité pour ce projet";

            if( $version->getProjet() != null )
                $rapportActivite = AppBundle::getRepository(RapportActivite::class)->findOneBy(
                    [
                    'projet' => $version->getProjet(),
                    'annee' => $version->getAnneeSession(),
                    ]);
            else
                {
                $rapportActivite = null;
                Functions::errorMessage(__METHOD__ . ":" . __LINE__ . " version " . $version . " n'est pas associée à aucun projet !");
                }

           if( $etat != Etat::ACTIF && $etat != Etat::TERMINE)
                $menu['raison'] = "Vous devez soumettre le rapport annuel quand vous avez fini vos calculs de l'année en question";
           elseif( ! $version->isCollaborateur() )
                $menu['raison'] = "Seul un collaborateur du projet peut téléverser un rapport d'activité pour un projet";
           //elseif( $rapportActivite != null)
           //     $menu['raison'] = "Vous avez déjà téléversé un rapport d'activité pour ce projet pour l'année en question";
            else
            {
                $menu['ok']          = true;
                $menu['commentaire'] = "Téléverser votre rapport d'activité pour l'année " . $version->getAnneeSession() . "si vous avez déjà terminé vos calculs";
                $menu['todo']        = "Demander au responsable du projet de le <strong>relire</strong> et de <strong>l'envoyer pour expertise</strong>";
            }
        }
    else
        {
        $menu['param']          =   0;
        $menu['lien']           =   "Téléverser le rapport d'activité";
        $menu['commentaire']    =   "Vous ne pouvez pas téléverser un rapport d'activité pour ce projet";
        $menu['raison']         =   "Mauvaise version du projet !";
        Functions::errorMessage( __METHOD__ . ':' . __LINE__ . " Version null !");
        }

    return $menu;
    }

    ////////////////////////////////////////////////////////////

    public static function telecharger_modele_rapport_dactivite( Version $version )
    {
        $menu['name']           =   'telecharger_modele';
        $menu['lien']           =   "Télécharger un modèle de rapport d'activité";
        $menu['ok']             =   false;
        $menu['commentaire'] = "Vous ne pouvez pas télécharger un modèle de rapport d'activité pour ce projet";

    if( $version != null )
        {

            if( AppBundle::isGranted('ROLE_ADMIN') )
                {
                $menu['commentaire'] = "Télécharger un modèle de rapport d'activité en tant qu'administrateur";
                $menu['raison']      = "L'admininstrateur peut TOUJOURS télécharger un modèle de rapport d'activité !";
                $menu['ok']          = true;
                return $menu;
                }

            $etat    = $version->getEtatVersion();

            if( ! $version->isCollaborateur() )
                $menu['raison'] = "Seul un collaborateur du projet peut télécharger un modèle de rapport d'activité pour ce projet";
            //elseif( $etat != Etat::ACTIF && $etat != Etat::TERMINE)
            //    $menu['raison'] = "Vous devez soumettre le rapport annuel quand vous avez fini vos calculs de l'année en question";
            else
            {
                $menu['ok']          = true;
                $menu['commentaire'] = "Télécharger un modèle de rapport d'activité";
                $menu['todo']        = "Demander au responsable du projet de le <strong>relire</strong> et de <strong>l'envoyer pour expertise</strong>";
            }
        }
    else
        {
        $menu['raison']         = "Mauvaise version du projet !";
        Functions::errorMessage( __METHOD__ . ':' . __LINE__ . " Version null !");
        }

    return $menu;
    }

    ////////////////////////////////////////////////////////////

    public static function gerer_publications( Projet $projet )
    {
        $menu['name']   =   'gerer_publications';
        $menu['param']  =   $projet->getIdProjet();
        $menu['lien']           =   "Publications";

        if( AppBundle::isGranted('ROLE_ADMIN') )
        {
            $menu['commentaire']    =   "Modifier les publications en tant qu'administrateur";
            $menu['raison']         =   "L'admininstrateur peut TOUJOURS modifier les publications du projet  !";
            $menu['ok']             = true;
            return $menu;
        }

        $version = $projet->derniereVersion();
        $etat    = $version->getEtatVersion();

        $menu['ok']             = false;
        $menu['commentaire']    =   "Vous ne pouvez pas modifier les publications";

        if( ! $projet->isCollaborateur() )
            $menu['raison']     =  "Seul un collaborateur du projet peut gérer les publicatins associées à un projet";
        elseif( $version->isNouvelle() && ! ( $etat == Etat::ACTIF || $etat == Etat::TERMINE ) )
            $menu['raison']     =  "Vous ne pouvez ajouter que des publications que vous avez publiées grâce au calcul sur ".AppBundle::getParameter('mesoc');
        else
        {
            $menu['ok']             = true;
            $menu['commentaire']    = "Gérer les publicatins associées au projet " . $projet->getIdProjet();
            $menu['todo']           = '<strong>Signaler les dernières publications</strong> dans lesquelles '.AppBundle::getParameter('mesoc').' a été remercié pour ce projet';
        }

        return $menu;

    }

    /////////////////////////////////////////////////////////////////////

    public static function renouveler_version( Version $version)
    {
        $menu['name']           =   'renouveler_version';
        $menu['param']          =   $version->getIdVersion();
        $menu['lien']           =   "Renouvellement";
        $menu['commentaire']    =   "Vous ne pouvez pas demander de renouvellement";
        $menu['ok']             =   false;

        $session = AppBundle::getRepository(Session::class)->findOneBy( [ 'etatSession' => Etat::EDITION_DEMANDE ] );

        if( $session == null )
        {
            $menu['raison']     =   "Nous ne sommes pas en période de demandes de ressources";
            return $menu;
        }

        $idVersion = $session->getIdSession() . $version->getProjet()->getIdProjet();

        if( AppBundle::getRepository( Version::class)->findOneBy( [ 'idVersion' =>  $idVersion] ) != null )
            $menu['raison']     =   "Version initiale ou renouvellement déjà demandé";
        elseif( $version->getProjet()->getEtatProjet() == Etat::TERMINE )
            $menu['raison']     =   "Votre projet est ou sera prochainement terminé";
        elseif( $version->isCollaborateur() )
            {
            $menu['commentaire']         =   "Demander de nouvelles ressources sur ce projet pour la session " . $session->getIdSession();
            $menu['ok']             =   true;
            }
        elseif( AppBundle::isGranted('ROLE_ADMIN') )
            {
            $menu['commentaire']         =   "Demander de nouvelles ressources sur ce projet pour la session "
                .   $session->getIdSession() . " en tant qu'administrateur";
            $menu['ok']             =   true;
        }
        else
            $menu['raison']         = "Vous n'avez pas le droit de renouveler ce projet, vous n'êtes pas un collaborateur";
        return $menu;

    }

    //////////////////////////////////////////////////////////////////

    public static function envoyer_expert( Projet $projet)
    {
        if( $projet == null ) return [];

        $menu['name']           =   'avant_envoyer_expert';
        $menu['param']          =   $projet->getIdProjet();
        $menu['lien']           =   "Envoyer à l'expert";
        $menu['commentaire']    =   "Vous ne pouvez pas envoyer ce projet à l'expert";
        $menu['ok']             =   false;
        $menu['raison']         =   "";
        $menu['incomplet']      =   false;

        $version      = $projet->derniereVersion();
        $etatVersion  = $version->getEtatVersion();
        // true si le projet est un projet test OU un projet fil de l'eau
        $type_projet  = $version->getProjet()->getTypeProjet();
        $isProjetTest = ($type_projet == Projet::PROJET_FIL || $type_projet == Projet::PROJET_TEST);

        if( $version->getSession() != null )
            $etatSession = $version->getSession()->getEtatSession();
        else
            $etatSession = null;

        if( $version == null)
        {
            $menu['raison'] = "Pas de projet à soumettre";
            Functions::errorMessage(__METHOD__ . ' le projet ' . Functions::show( $projet ) . " n'a pas de version !");
        }
        elseif( $version->getSession() == null )
        {
            $menu['raison'] = "Pas de session attachée à ce projet !";
            Functions::errorMessage(__METHOD__ . ' la version ' . Functions::show( $version ) . " n'a pas de session attachée !");
        }
        elseif( $version->isResponsable() == false )
            $menu['raison'] = "Seul le responsable du projet peut envoyer ce projet à l'expert";

        // manu - 11 juin 2019 - Tout le monde peut créer un projet test !
        elseif( $isProjetTest == false && $version->isResponsable() == true &&  ! static::peut_creer_projets() )
		{
            $menu['raison'] = "Le responsable du projet n'a pas le droit de créer des projets";
            Functions::warningMessage(__METHOD__ . ':' . __LINE__ ." Le responsable " . AppBundle::getUser()
                . " du projet " . $projet . " ne peut pas créer des projets");
		}
        elseif( $etatVersion ==  Etat::EDITION_EXPERTISE  )
            $menu['raison'] = "Le projet a déjà été envoyé à l'expert !";
        elseif( $isProjetTest == true && $etatVersion ==  Etat::ANNULE )
            $menu['raison'] = "Le projet test a été annulé !";
        elseif( $isProjetTest == true && $etatVersion !=  Etat::EDITION_TEST )
            $menu['raison'] = "Le projet test a déjà été envoyé à l'expert !";
        elseif( $etatVersion !=  Etat::EDITION_DEMANDE && $etatVersion !=  Etat::EDITION_TEST )
            $menu['raison'] = "Le responsable du projet n'a pas demandé de renouvellement";
        //elseif( $etatSession != Etat::EDITION_DEMANDE &&  $etatSession != Etat::EDITION_EXPERTISE  && $isProjetTest == false )
        elseif( $etatSession != Etat::EDITION_DEMANDE && $isProjetTest == false )
            $menu['raison'] = "Nous ne sommes pas en période de demandes de ressources";
        elseif( VersionModifController::versionValidate( $version ) != [] )
		{
			//Functions::debugMessage(__METHOD__ . ' '.$version->getIdVersion() . ' ' . print_r(VersionModifController::versionValidate( $version ), true));

            $menu['raison']         = "Votre demande est incomplète";
            $menu['name']           =   'version_avant_modifier';
            $menu['commentaire']    =   "Vous ne pouvez pas envoyer ce projet à l'expert parce que votre demande est incomplète";
            $menu['incomplet']      =   true;
            $menu['ok']             =   true;
            $menu['param']          =   $version->getIdVersion();
		}
        else
        {
            $menu['ok']          = true;
            $menu['commentaire'] = "Envoyer votre demande pour expertise. ATTENTION, vous ne pourrez plus la modifier par la suite";
            $menu['todo']        = "Envoyer le projet en <strong>expertise</strong>";
        }

        return $menu;
    }

    ////////////////////////////////////////////////////////////////////////////

    public static function affectation()
    {
        $session = Functions::getSessionCourante();

        $menu['name']        =   'affectation';
        $menu['lien']        =   "Affecter les experts ($session)";

        $menu['commentaire'] =   "Vous ne pouvez pas affecter les experts de la session " . $session;
        $menu['ok']          =   false;
        if( !AppBundle::isGranted('ROLE_PRESIDENT') )
        {
            $menu['raison']     =   "Vous n'avez pas le rôprésident";
        }
        else
        {
            $menu['ok']             =   true;
            $menu['commentaire']    =   "Espace d'affectation des experts";
        }

        return $menu;
    }

    ////////////////////////////////////////////////////////////////////////////

    public static function affectation_test()
    {
        //$session = Functions::getSessionCourante();

        $menu['name']        =   'affectation_test';
        $menu['lien']        =   "Projets test";

        $menu['commentaire'] =   "Vous ne pouvez pas affecter les experts aux projets test de cette année";
        $menu['ok']          =   false;
        if( !AppBundle::isGranted('ROLE_PRESIDENT') )
        {
            $menu['raison']     =   "Vous n'avez pas le rôprésident";
        }
        // Supprimé par manu - On peut affecter les experts en permanence, à cause des projets tests
        /*
        elseif ( $session->getEtatSession()!=Etat::EDITION_EXPERTISE && $session->getEtatSession()!=Etat::EN_ATTENTE)
        {
            $menu['raison']     =   "La session n'est pas en phase d'expertise";
        }
        */
        else
        {
            $menu['ok']             =   true;
            $menu['commentaire']    =   "Espace d'affectation des experts aux projets test";
        }

        return $menu;
    }

////////////////////////////////////////////////////////////////////////////

    public static function avancer()
    {
    //$session = Functions::getSessionCourante();
    $menu['name']           =   'param_avancer';
    $menu['lien']           =   "Avancer dans le temps";
    $menu['commentaire']    =   "Vous ne pouvez pas avancer dans le temps";
    $menu['ok']             =   false;
    $menu['raison']         =   "Vous n'êtes pas un administrateur";

    if( AppBundle::isGranted('ROLE_ADMIN') )
        {
        $menu['ok']             =   true;
        $menu['commentaire']    =   "Vous pouvez avancer dans le temps (pour déboguage)";
        }

    return $menu;
    }

////////////////////////////////////////////////////////////////////////////

    public static function mailToResponsables()
    {
    $session = Functions::getSessionCourante();
    if( $session != null )
        {
        $etatSession    =   $session->getEtatSession();
        $idSession      =   $session->getIdSession();
        }
    else
        {
        Functions::errorMessage(__METHOD__ . ':' . __LINE__ . " La session courante est nulle !");
        $etatSession    =   null;
        $idSession      =   'X';
        }

    $menu['name']           =   'mail_to_responsables';
    $menu['param']          =   $idSession;
    $menu['lien']           =   "Mail - projets non renouvelés";
    $menu['commentaire']    =   "Vous ne pouvez pas envoyer un mail aux responsables des projets qui ne l'ont pas renouvelé";
    $menu['ok']             =   false;
    $menu['raison']         =   "Vous n'êtes pas un administrateur ou président";

    $session    =   Functions::getSessionCourante();
    if( $session != null )
        $etatSession    =   $session->getEtatSession();
    else
        {
        Functions::errorMessage(__METHOD__ . ':' . __LINE__ . " La session courante est nulle !");
        $etatSession    =   null;
        }

    if( $etatSession    !=  Etat::EDITION_DEMANDE )
        $menu['raison']         =   "La session n'est pas en mode d'édition";
    elseif( AppBundle::isGranted('ROLE_ADMIN') || AppBundle::isGranted('ROLE_PRESIDENT') )
        {
        $menu['ok']             =   true;
        $menu['commentaire']    =   "Envoyer un rappel aux responsables des projets qui n'ont pas renouvelé !";
        }

    return $menu;
    }

  ////////////////////////////////////////////////////////////////////////////

    public static function mailToResponsablesFiche()
    {
    $session = Functions::getSessionCourante();
    if( $session != null )
        {
        $etatSession    =   $session->getEtatSession();
        $idSession      =   $session->getIdSession();
        }
    else
        {
        Functions::errorMessage(__METHOD__ . ':' . __LINE__ . " La session courante est nulle !");
        $etatSession    =   null;
        $idSession      =   'X';
        }

    $menu['name']           =   'mail_to_responsables_fiche';
    $menu['param']          =   $idSession;
    $menu['lien']           =   "Mail - projets sans fiche";
    $menu['commentaire']    =   "Vous ne pouvez pas envoyer un mail aux responsables des projets qui n'ont pas téléversé leur fiche projet";
    $menu['ok']             =   false;
    $menu['raison']         =   "Vous n'êtes pas un administrateur ou président";

    $session    =   Functions::getSessionCourante();
    if( $session != null )
        $etatSession    =   $session->getEtatSession();
    else
        {
        Functions::errorMessage(__METHOD__ . ':' . __LINE__ . " La session courante est nulle !");
        $etatSession    =   null;
        }

    if( $etatSession    !=  Etat::ACTIF && $etatSession    !=  Etat::EN_ATTENTE )
        $menu['raison']         =   "La session n'est pas en mode actif ou en attente";
    elseif( AppBundle::isGranted('ROLE_ADMIN') || AppBundle::isGranted('ROLE_PRESIDENT') )
        {
        $menu['ok']             =   true;
        $menu['commentaire']    =   "Envoyer un rappel aux responsables des projets qui n'ont pas téléversé leur fiche projet !";
        }

    return $menu;
    }

////////////////////////////////////////////////////////////////////////////

    public static function nettoyer()
    {
    $menu['name']           =   'projet_nettoyer';
    $menu['lien']           =   "Nettoyage pour conformité au RGPD";
    $menu['commentaire']    =   "Vous ne pouvez pas supprimer les projets ou les utilisateurs anciens";
    $menu['ok']             =   false;
    $menu['raison']         =   "Vous n'êtes pas un administrateur";

    if( AppBundle::isGranted('ROLE_ADMIN') )
        {
        $menu['ok']             =   true;
        $menu['commentaire']    =   "Suppresion des anciens projets et des utilisateurs orphelins";
        }

    return $menu;
    }

/////////////////////////////////////////////////////////////////////////////////

    public static function connexions()
    {
    $menu['name']           =   'connexions';
    $menu['lien']           =   "Personnes connectées";
    $menu['commentaire']    =   "Vous ne pouvez pas voir les personnes connectées";
    $menu['ok']             =   false;
    $menu['raison']         =   "Vous n'êtes pas un administrateur";

    if( AppBundle::isGranted('ROLE_ADMIN') )
        {
        $menu['ok']             =   true;
        $menu['commentaire']    =   "Vous pouvez pouvez voir les personnes connectées";
        }

    return $menu;
    }

 ////////////////////////////////////////////////////////////////////////////

    public static function presidents()
    {

    $menu['name']           =   'individu_president';
    $menu['lien']           =   "Attribuer le rôle de président";
    $menu['commentaire']    =   "Vous ne pouvez pas attribuer le rôle de président";
    $menu['ok']             =   false;
    $menu['raison']         =   "Vous n'êtes pas un administrateur";

    if( AppBundle::isGranted('ROLE_ADMIN') )
        {
        $menu['ok']             =   true;
        $menu['commentaire']    =   "Vous pouvez attribuer la fonction du président à un utilisateur admin ou expert";
        }

    return $menu;
    }

    ////////////////////////////////////////////////////////////////////////////

    public static function rallonge_creation(Projet $projet)
    {
    $menu['name']           =   'rallonge_creation';
    $menu['param']          =   $projet->getIdProjet();
    $menu['lien']           =   "Rallonge";
    $menu['commentaire']    =   "Vous ne pouvez pas créer une nouvelle rallonge";
    $menu['ok']             =   false;
    $menu['raison']         =   "Vous n'êtes pas un administrateur";


    $version = $projet->versionActive();

    if( $version == null )
        $menu['raison']         =   "Le projet " . $projet . " n'est pas actif !";
    elseif( AppBundle::getRepository(Rallonge::class)->findRallongesOuvertes($projet) != null )
        $menu['raison']         =   "Une autre rallonge du projet " . $projet . " est déjà traitée !";
    //elseif( $version->getEtatVersion()  == Etat::NOUVELLE_VERSION_DEMANDEE )
    //    $menu['raison']         =   "Un renouvellement du projet " . $projet . " est déjà accepté !";
    elseif( AppBundle::isGranted('ROLE_ADMIN')  )
        {
        $menu['ok']             =   true;
        $menu['commentaire']    =   "Vous pouvez créer une nouvelle rallonge !";
        }

    return $menu;

    }

    ////////////////////////////////////////////////////////////////////////////

    public static function rallonge_modifier(Rallonge $rallonge)
    {
    $menu['name']           =   'rallonge_modifier';
    $menu['param']          =   $rallonge->getIdRallonge();
    $menu['lien']           =   "Modifier la demande";
    $menu['commentaire']    =   "Vous ne pouvez pas modifier la demande";
    $menu['ok']             =   false;
    $menu['raison']         =   "raison inconnue";


    $version = $rallonge->getVersion();
    if( $version != null )
        $projet = $version->getProjet();
    else
        $projet = null;

    if( $version == null )
        $menu['raison']         =   "Cette rallonge n'est associée à aucun projet !";
    //elseif( $version->getEtatVersion()  == Etat::NOUVELLE_VERSION_DEMANDEE )
    //    $menu['raison']         =   "Un renouvellement du projet " . $projet . " est déjà accepté !";
    elseif(  $version->getProjet() == null )
        $menu['raison']     =   "Cette version du projet n'est associée à aucun projet";
    elseif( $version->getProjet()->getEtatProjet() == Etat::TERMINE )
        $menu['raison']     =   "Votre projet est ou sera prochainement terminé";
    elseif( $version->getEtatVersion() == Etat::ANNULE )
        $menu['raison']     =   "Votre projet est annulé";
    elseif( $version->getEtatVersion() == Etat::TERMINE )
        $menu['raison']     =   "Votre projet de cette session est déjà terminé";
    elseif( $rallonge->getEtatRallonge() == Etat::ANNULE )
        $menu['raison']     =   "Cette rallonge a été annulée";
    elseif( $rallonge->getEtatRallonge() != Etat::EDITION_DEMANDE )
        $menu['raison']     =   "Cette rallonge a déjà été envoyée à l'expert";
    elseif( AppBundle::isGranted('ROLE_ADMIN')  )
        {
        $menu['ok']             =   true;
        $menu['commentaire']    =   "Vous pouvez modifier la demande en tant qu'administrateur !";
        }
    elseif( $version->isCollaborateur() )
        {
        $menu['commentaire']         =   "Vous pouvez modifier votre demande " ;
        $menu['ok']             =   true;
        }
    else
        $menu['raison']         = "Vous n'avez pas le droit de modifier cette demande, vous n'êtes pas un collaborateur";

    return $menu;
    }


////////////////////////////////////////////////////////////////////////////

    public static function rallonge_envoyer(Rallonge $rallonge)
    {
    $menu['name']           =   'avant_rallonge_envoyer';
    $menu['param']          =   $rallonge->getIdRallonge();
    $menu['lien']           =   "Envoyer la demande à l'expert";
    $menu['commentaire']    =   "Vous ne pouvez pas envoyer cette demande à l'expert";
    $menu['ok']             =   false;
    $menu['raison']         =   "raison inconnue";


    $version = $rallonge->getVersion();
    if( $version != null )
        $projet = $version->getProjet();
    else
        $projet = null;

    if( $version == null )
        $menu['raison']         =   "Cette rallonge n'est associée à aucun projet !";
    //elseif( $version->getEtatVersion()  == Etat::NOUVELLE_VERSION_DEMANDEE )
    //    $menu['raison']         =   "Un renouvellement du projet " . $projet . " est déjà accepté !";
    elseif(  $version->getProjet() == null )
        $menu['raison']     =   "Cette version du projet n'est associée à aucun projet";
    elseif( $version->getProjet()->getEtatProjet() == Etat::TERMINE )
        $menu['raison']     =   "Votre projet est ou sera prochainement terminé";
    elseif( $version->getEtatVersion() == Etat::ANNULE )
        $menu['raison']     =   "Votre projet est annulé";
    elseif( $version->getEtatVersion() == Etat::TERMINE )
        $menu['raison']     =   "Votre projet de cette session est déjà terminé";
    elseif( $rallonge->getEtatRallonge() == Etat::ANNULE )
        $menu['raison']     =   "Cette rallonge a été annulée";
    elseif( $rallonge->getEtatRallonge() != Etat::EDITION_DEMANDE )
        $menu['raison']     =   "Cette rallonge a déjà été envoyée à l'expert";
    elseif( AppBundle::isGranted('ROLE_ADMIN')  )
        {
        $menu['ok']             =   true;
        $menu['commentaire']    =   "Vous pouvez envoyer cette demande à l'expert en tant qu'administrateur !";
        }
    elseif( $version->isResponsable() )
        {
        $menu['commentaire']         =   "Vous pouvez envoyer votre demande à l'expert" ;
        $menu['ok']             =   true;
        }
    else
        $menu['raison']         = "Vous n'avez pas le droit d'envoyer cette demande à l'expert, vous n'êtes pas le responsable du projet";

    return $menu;
    }

////////////////////////////////////////////////////////////////////////////

    public static function affectation_rallonges()
    {
    //$session = Functions::getSessionCourante();
    $menu['name']           =   'rallonge_affectation';
    $menu['lien']           =   "Rallonge de ressources";
    $menu['commentaire']    =   "Affecter les experts pour les rallonges";
    $menu['ok']             =   false;
    $menu['raison']         =   "Vous n'êtes pas président";

    if( AppBundle::isGranted('ROLE_ADMIN') ||  AppBundle::isGranted('ROLE_PRESIDENT') )
        {
        $menu['ok']             =   true;
        $menu['commentaire']    =   "Affecter les experts pour les rallonges";
        }

    return $menu;
    }

    ////////////////////////////////////////////////////////////////////////////

    public static function televersement_generique()
    {
    $menu['name']           =   'televersement_generique';
    $menu['lien']           =   "Téléversements génériques";
    $menu['commentaire']    =   "Téléverser des fiches projet ou des rapports d'activité";
    $menu['ok']             =   false;
    $menu['raison']         =   "Vous n'êtes pas un administrateur";

    if( AppBundle::isGranted('ROLE_ADMIN') )
        {
        $menu['ok']             =   true;
        $menu['commentaire']    =   "Téléverser des fiches projet ou des rapports d'activité";
        }

    return $menu;
    }

    //////////////////////////////////////////////////////////////////////////////


    public static function telechargement_fiche(Version $version)
    {
    $menu['name']           =   'version_fiche_pdf';
    $menu['param']          =   $version->getIdVersion();
    $menu['lien']           =   "Téléchargement de la fiche projet";
    $menu['commentaire']    =   "Vous ne pouvez pas télécharger la fiche de ce projet";
    $menu['ok']             =   false;
    $menu['raison']         =   "Vous n'êtes pas un collaborateur du projet";

    if( AppBundle::isGranted('ROLE_ADMIN') )
        {
        $menu['ok']             =   true;
        $menu['commentaire']    =   "Télécharger la fiche projet en tant qu'administrateur";
        }
    elseif( ! $version->isCollaborateur() )
        $menu['raison']         =   "Vous n'êtes pas un collaborateur du projet";
    elseif( $version->getPrjFicheVal() == true )
        $menu['raison']         =   "La fiche projet signée a déjà été téléversée";
    else
        {
        $menu['ok']             =   true;
        $menu['commentaire']    =   "Télécharger la fiche projet";
        }

    return $menu;
    }


 //////////////////////////////////////////////////////////////////////////////


    public static function televersement_fiche(Version $version)
    {
    $menu['name']           =   'version_televersement_fiche';
    $menu['param']          =   $version->getIdVersion();
    $menu['lien']           =   "Téléversement de la fiche projet";
    $menu['commentaire']    =   "Vous ne pouvez pas téléverser la fiche de ce projet";
    $menu['ok']             =   false;
    $menu['raison']         =   "Vous n'êtes pas un collaborateur du projet";

    if( AppBundle::isGranted('ROLE_ADMIN') )
        {
        $menu['ok']             =   true;
        $menu['commentaire']    =   "Téléverser la fiche projet en tant qu'administrateur";
        }
    elseif( ! $version->isCollaborateur() )
        $menu['raison']         =   "Vous n'êtes pas un collaborateur du projet";
    elseif( $version->getPrjFicheVal() == true )
        $menu['raison']         =   "La fiche projet signée a déjà été téléversée";
    else
        {
        $menu['ok']             =   true;
        $menu['commentaire']    =   "Téléverser la fiche projet";
        }

    return $menu;
    }

    //////////////////////////////////////////////////////////////////////////

    public static function rallonge_expertiser(Rallonge $rallonge)
    {
    $menu['name']           =   'rallonge_expertiser';
    $menu['param']          =   $rallonge->getIdRallonge();
    $menu['lien']           =   "Expertiser la rallonge";
    $menu['commentaire']    =   "Vous ne pouvez pas expertiser cette demande";
    $menu['ok']             =   false;
    $menu['raison']         =   "raison inconnue";


    $version = $rallonge->getVersion();
    if( $version != null )
        $projet = $version->getProjet();
    else
        $projet = null;

    $etatRallonge   =  $rallonge->getEtatRallonge();

    if( $version == null )
        $menu['raison']         =   "Cette rallonge n'est associée à aucun projet !";
    //elseif( $version->getEtatVersion()  == Etat::NOUVELLE_VERSION_DEMANDEE )
    //    $menu['raison']         =   "Un renouvellement du projet " . $projet . " est déjà accepté !";
    elseif(  $version->getProjet() == null )
        $menu['raison']     =   "Cette version du projet n'est associée à aucun projet";
    elseif( $version->getProjet()->getEtatProjet() == Etat::TERMINE )
        $menu['raison']     =   "Votre projet est ou sera prochainement terminé";
    elseif( $version->getEtatVersion() == Etat::ANNULE )
        $menu['raison']     =   "Votre projet est annulé";
    elseif( $version->getEtatVersion() == Etat::TERMINE )
        $menu['raison']     =   "Votre projet de cette session est déjà terminé";
    elseif(  $etatRallonge == Etat::EDITION_DEMANDE )
        $menu['raison']     =   "Cette demande n'a pas encore été envoyée à l'expert";
    elseif(  $etatRallonge== Etat::ANNULE )
        $menu['raison']     =   "Cette demande a été annulée";
    elseif(  $etatRallonge != Etat::EDITION_EXPERTISE )
        $menu['raison']     =   "Cette demande a déjà été envoyée au président";
    elseif( AppBundle::isGranted('ROLE_ADMIN')  )
        {
        $menu['ok']             =   true;
        $menu['commentaire']    =   "Vous pouvez expertiser cette demande et l'envoyer au président en tant qu'administrateur !";
        }
    elseif( $rallonge->isExpert() )
        {
        $menu['commentaire']         =   "Vous pouvez expertiser cette demande et l'envoyer au président" ;
        $menu['ok']             =   true;
        }
    else
        $menu['raison']         = "Vous n'avez pas le droit d'expertiser cette demande, vous n'êtes pas l'expert designé";

    return $menu;
    }


    //////////////////////////////////////////////////////////////////////////

    public static function statistiques_etablissement($annee = null)
    {
    if( $annee == null )   $annee=GramcDate::get()->showYear();

    $menu['name']           =   'statistiques_etablissement';
    $menu['params']          =   ['annee' => $annee];
    $menu['lien']           =   "Statistiques par établissement";

    if( AppBundle::isGranted('ROLE_OBS') || AppBundle::isGranted('ROLE_PRESIDENT') )
        {
        $menu['ok']             =   true;
        $menu['commentaire']    =   "Vous pouvez accéder aux statistiques par établissement !";
        }
    else
        {
        $menu['ok']             =   false;
        $menu['commentaire']    =   "Vous ne pouvez pas accéder aux statistiques par établissement !";
        $menu['raison']         =   "Vous devez être président ou administrateur pour y accéder";
        }

    return $menu;
    }

 //////////////////////////////////////////////////////////////////////////

    public static function statistiques_laboratoire($annee = null)
    {
    if( $annee == null )   $annee=GramcDate::get()->showYear();

    $menu['name']           =   'statistiques_laboratoire';
    $menu['params']          =   ['annee' => $annee];
    $menu['lien']           =   "Statistiques par laboratoire";

    if( AppBundle::isGranted('ROLE_OBS') || AppBundle::isGranted('ROLE_PRESIDENT') )
        {
        $menu['ok']             =   true;
        $menu['commentaire']    =   "Vous pouvez accéder aux statistiques par laboratoire !";
        }
    else
        {
        $menu['ok']             =   false;
        $menu['commentaire']    =   "Vous ne pouvez pas accéder aux statistiques par laboratoire !";
        $menu['raison']         =   "Vous devez être président ou administrateur pour y accéder";
        }

    return $menu;
    }

    //////////////////////////////////////////////////////////////////////////

    public static function statistiques_thematique($annee = null)
    {
    if( $annee == null )   $annee=GramcDate::get()->showYear();

    $menu['name']           =   'statistiques_thematique';
    $menu['params']          =   ['annee' => $annee];
    $menu['lien']           =   "Statistiques par thématique";

    if( AppBundle::isGranted('ROLE_OBS') || AppBundle::isGranted('ROLE_PRESIDENT') )
        {
        $menu['ok']             =   true;
        $menu['commentaire']    =   "Vous pouvez accéder aux statistiques par thématique !";
        }
    else
        {
        $menu['ok']             =   false;
        $menu['commentaire']    =   "Vous ne pouvez pas accéder aux statistiques par thématique !";
        $menu['raison']         =   "Vous devez être président ou administrateur pour y accéder";
        }

    return $menu;
    }

    //////////////////////////////////////////////////////////////////////////

    public static function statistiques_metathematique($annee = null)
    {
    if( $annee == null )   $annee=GramcDate::get()->showYear();

    $menu['name']           =   'statistiques_metathematique';
    $menu['params']          =   ['annee' => $annee];
    $menu['lien']           =   "Statistiques par metathématique";

    if( AppBundle::isGranted('ROLE_OBS') || AppBundle::isGranted('ROLE_PRESIDENT') )
        {
        $menu['ok']             =   true;
        $menu['commentaire']    =   "Vous pouvez accéder aux statistiques par metathématique !";
        }
    else
        {
        $menu['ok']             =   false;
        $menu['commentaire']    =   "Vous ne pouvez pas accéder aux statistiques par metathématique !";
        $menu['raison']         =   "Vous devez être président ou administrateur pour y accéder";
        }

    return $menu;
    }

    //////////////////////////////////////////////////////////////////////////

    public static function statistiques()
    {
    $menu['name']           =   'statistiques';
    $menu['lien']           =   "Statistiques";

    if( AppBundle::isGranted('ROLE_OBS') || AppBundle::isGranted('ROLE_PRESIDENT') )
        {
        $menu['ok']             =   true;
        $menu['commentaire']    =   "Vous pouvez accéder aux statistiques  !";
        }
    else
        {
        $menu['ok']             =   false;
        $menu['commentaire']    =   "Vous ne pouvez pas accéder aux statistiques  !";
        $menu['raison']         =   "Vous devez être président ou administrateur pour y accéder";
        }

    return $menu;
    }

    //////////////////////////////////////////////////////////////////////////

    public static function statistiques_collaborateur($annee = null)
    {
    if( $annee == null )   $annee=GramcDate::get()->showYear();

    $menu['name']           =   'statistiques_collaborateur';
    $menu['params']          =   ['annee' => $annee];
    $menu['lien']           =   "Statistiques concernant les collaborateurs";

    if( AppBundle::isGranted('ROLE_OBS') || AppBundle::isGranted('ROLE_PRESIDENT') )
        {
        $menu['ok']             =   true;
        $menu['commentaire']    =   "Vous pouvez accéder aux statistiques concernant les collaborateurs !";
        }
    else
        {
        $menu['ok']             =   false;
        $menu['commentaire']    =   "Vous ne pouvez pas accéder aux  statistiques concenrant les collaborateurs!";
        $menu['raison']         =   "Vous devez être président ou administrateur pour y accéder";
        }

    return $menu;
    }

    //////////////////////////////////////////////////////////////////////////

    public static function statistiques_repartition($annee = null)
    {
    if( $annee == null )   $annee=GramcDate::get()->showYear();

    $menu['name']           =   'statistiques_repartition';
    $menu['params']          =   ['annee' => $annee];
    $menu['lien']           =   "Statistiques concernant la répartition des projets";

    if( AppBundle::isGranted('ROLE_OBS') || AppBundle::isGranted('ROLE_PRESIDENT') )
        {
        $menu['ok']             =   true;
        $menu['commentaire']    =   "Vous pouvez accéder aux statistiques concernant la répartition des projets !";
        }
    else
        {
        $menu['ok']             =   false;
        $menu['commentaire']    =   "Vous ne pouvez pas accéder aux  statistiques concenrant la répartition des projets !";
        $menu['raison']         =   "Vous devez être président ou administrateur pour y accéder";
        }

    return $menu;
    }

	/*
	 * Demandes concernant stockage et partage des données
	 */
    public static function donnees( Version $version )
    {
        $menu['name']  = 'donnees';
        $menu['param'] = $version->getIdVersion();
        $menu['lien']  = "Vos données";

        if( AppBundle::isGranted('ROLE_ADMIN') )
        {
            $menu['commentaire']    =   "Gestion et valorisation des données en tant qu'admin";
            $menu['ok']             = true;
        }
		elseif( ! $version->isResponsable() )
		{
			$menu['ok']          = false;
	        $menu['commentaire'] = "Bouton inactif";
            $menu['raison']      = "Vous n'êtes pas responsable du projet";
		}
		else
		{
            $menu['ok']          = true;
            $menu['commentaire'] = "Gestion et valorisation des données";
        }

        return $menu;
    }
}
