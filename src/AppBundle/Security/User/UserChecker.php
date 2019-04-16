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

namespace AppBundle\Security\User;

use AppBundle\Exception\AccountDeletedException;
use AppBundle\Entity\Individu as AppUser;

use Symfony\Component\Security\Core\Exception\AccountExpiredException;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\Exception\LockedException;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use AppBundle\Exception\UserException;
use Symfony\Component\HttpFoundation\Request;


use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use AppBundle\AppBundle; 
use AppBundle\Utils\Functions;

/*
include_once dirname(__FILE__).'/../../../config.php';
include_once dirname(__FILE__).'/../../../init.php';
include_once dirname(__FILE__).'/../../../lib/pdo3.php';
include_once dirname(__FILE__).'/../../../modeles/infos_projet.php';
include_once dirname(__FILE__).'/../../../lib/controleur.php';
include_once dirname(__FILE__).'/../../../modeles/auth_fesr.php';
*/

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user)
        {
        
        if (!$user instanceof AppUser) return;
        
        if( AppBundle::isGranted('ROLE_PREVIOUS_ADMIN') )
            Functions::debugMessage('UserChecker : checkPreAuth : User ' . $user->getPrenom() . ' ' . $user->getNom() .
                " ROLE_PREVIOUS_ADMIN granted");           

        if( $user->getDesactive() )
            {
            Functions::warningMessage('UserChecker : checkPreAuth : User ' . $user->getPrenom() . ' ' . $user->getNom() .
                " est désactivé");
            throw new UserException($user);
            }
        else
            {
            //  Functions::debugMessage('UserChecker : checkPreAuth : User ' . $user->getPrenom() . ' ' . $user->getNom() .
            //   " peut se connecter");  
            return true;
            }
    
    }

    public function checkPostAuth(UserInterface $user)
    {
        
        if (!$user instanceof AppUser) return;

        // on peut faire sudo même si l'utilisateur n'a pas le droit de se connecter
        if(  $user->getDesactive()   && ! AppBundle::isGranted('ROLE_ALLOWED_TO_SWITCH' ))
            {
              Functions::warningMessage('UserChecker : checkPostAuth : User ' . $user->getPrenom() . ' ' . $user->getNom() .
                " est désactivé");
              throw new UserException($user);
            }
            
        // on stocke l'information sur SUDO dans la variable de la session 'real_user'
        // s'il n'y a pas de SUDO 'real_user' = $user
        // en cas de SUDO 'real_user' est l'utilisateur qui a fait SUDO

        if( ! AppBundle::isGranted('ROLE_PREVIOUS_ADMIN') )
            {
                if( AppBundle::isGranted('ROLE_ALLOWED_TO_SWITCH' ) && AppBundle::getSession()->has('real_user') )
                {
                    Functions::debugMessage('UserChecker : checkPostAuth : User ' . $user->getPrenom() . ' ' . $user->getNom() . " est connecté en SUDO par " .
                        AppBundle::getUser()->getPrenom() . ' ' . AppBundle::getUser() ->getNom());
                    AppBundle::getSession()->set('real_user', AppBundle::getUser() );
                    AppBundle::getSession()->set('sudo_url', Request::createFromGlobals()->headers->get('referer') );
                    //Functions::debugMessage(__METHOD__ . " sudo_url set to : " . AppBundle::getSession()->get('sudo_url') );
                }
                else
                {
                AppBundle::getSession()->set('real_user', $user);
                AppBundle::getSession()->remove('sudo_url');
                //Functions::debugMessage(__METHOD__ . " sudo_url removed" );
                //Functions::debugMessage('UserChecker : checkPostAuth : User ' . $user->getPrenom() . ' ' . $user->getNom() . " est connecté");
                }
            }
            else
            {
            //Functions::debugMessage('UserChecker : checkPostAuth : User '. AppBundle::getUser() . " redevient ".  $user );
            AppBundle::getSession()->remove('sudo_url');
            //Functions::debugMessage(__METHOD__ . " sudo_url removed" );
            }
            

        /*        
    $_SESSION['id_gramcuser'] = $user->getIdIndividu();
    $_SESSION['president'] =  $user->getPresident(); 
    $_SESSION['expert'] =  $user->getExpert();
    $_SESSION['admin'] = $user->getAdmin();
    $_SESSION['responsable']= (int)$user->getResponsable();
    $_SESSION['collaborateur'] = (int)$user->getCollaborateur() ;
    $_SESSION['mail'] = $user->getMail();
    $_SESSION['nom'] = $user->getNom();
    $_SESSION['prenom'] = $user->getPrenom();

    if( $user->getLabo() != null )
        {
        $_SESSION['id_labo'] = $user->getLabo()->getIdLabo();
        $_SESSION['libelle_labo'] = $user->getLabo()->getAcroLabo() . ' - ' . $user->getLabo()->getNomLabo();
        }
    else
        {
        $_SESSION['id_labo'] = 'none';
        $_SESSION['libelle_labo'] = 'none';
        }

    if( $user->getStatut() != null )
        {
        $_SESSION['id_statut'] = $user->getStatut()->getIdStatut();
        $_SESSION['libelle_statut'] = $user->getStatut()->getLibelleStatut();
        }
    else
        {
        $_SESSION['id_statut'] = 'none';
        $_SESSION['libelle_statut'] = 'none';
        }

    if( $user->getEtab() != null )
        {
        $_SESSION['id_etab'] = $user->getEtab()->getIdEtab();
        $_SESSION['libelle_etab'] = $user->getEtab()->getLibelleEtab();
        }
    else
        {
        $_SESSION['id_etab'] = 'none';
        $_SESSION['libelle_etab'] = 'none';
        }

    $_SESSION['projets_collab'] = get_projets_collab($_SESSION['id_gramcuser'],false);
    $_SESSION['projets_resp'] = get_projets_resp($_SESSION['id_gramcuser']);
    $_SESSION['projets_term'] = get_projets_termines($_SESSION['id_gramcuser']);
    $_SESSION['projets_standby'] = get_projets_en_standby($_SESSION['id_gramcuser']);


    // création d'un répertoire temporaire
    $dir = CHEMIN_TMP.session_id();
    if( ! is_dir( $dir ) )
        {
            if( file_exists( $dir  ) ) unlink ( $dir );
            mkdir( $dir );
        }        
           
    if( ! isset( $_SESSION['sessions_non_terminees'] ) )
                {

                $init = AppBundle::getContainer()->get('app.init_controller');
                $sessions = $init->sessions_non_terminees();
                  
                foreach($sessions as $session)
                    {
                   $s['id_session']    = $session->getIdSession();
                    
                    if( $session->getTypeSession() == false )
                        $s['type_session']  = 0;
                    else
                        $s['type_session']  = 1;
                    
                    $s['etat_session']  = $session->getEtatSession();
                    $s['libelle_etat']  = $session->getLibelleEtat();
                    $s['id_president']  = $session->getPresident()->getId();
                    
                    $_SESSION['sessions_non_terminees'][] = $s;
                    } // foreach($sessions as $session)
                    
                } */            
      return true;
    }
}
