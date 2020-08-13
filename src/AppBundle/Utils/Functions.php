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
use AppBundle\Entity\Thematique;
use AppBundle\Entity\CollaborateurVersion;

use AppBundle\Entity\Expertise;
use AppBundle\Entity\Sso;
use AppBundle\Entity\CompteActivation;

use AppBundle\Controller\SessionController;

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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\Proxy;
use Doctrine\Common\Util\Debug;

use AppBundle\Form\ChoiceList\ExpertChoiceLoader;

class Functions
{
        const TOUS      =   0;  // tous les projets
        const ANCIENS   =   1;  // les projets renouvellés
        const NOUVEAUX  =   2;  // les nouveaux projets

    /*
     * calcul des années pour des formulaires
     *
     */
    public static function years( $begin = null, $end = null, $difference = 5 )
    {
        if( $begin == null )    $begin = new GramcDate();
        if( $end  == null )     $end = new GramcDate();

        // le nombre d'années est +- 5 par défaut, nous devons le changer
        $first_year = $begin->format('Y');      // la première année
        $last_year = $end->format('Y');         // la dernière année

        if( $first_year <= $last_year )
            $years = range( $first_year - $difference, $last_year + $difference);
        else
            $years = range( $last_year - $difference, $first_year + $difference);

        return $years;
    }

    public static function choicesYear( $begin = null, $end = null, $difference = 5 )
    {
        $choices = [];
        foreach( array_values( static::years( $begin, $end, $difference) ) as $choice )
            $choices[$choice]  =   $choice;
        return $choices;
    }

    /*
     * générer la bonne exception si on est authentifié ou pas
     *
    */

    public static function createException( $text = null )
    {
        if( $text != null ) static::warningMessage($text);

        if( AppBundle::isGranted( 'IS_AUTHENTICATED_FULLY' ) )
            throw new AccessDeniedHttpException();
        else
            throw new InsufficientAuthenticationException();
    }


    /*
     *
     * sauvegarder un objet avec un traitement des exceptions
     *
     */

    public static function sauvegarder( $object )
    {
        try {
            $em = AppBundle::getManager();
            if( $em->isOpen() )
                {
                $em->persist( $object );
                $em->flush( $object );
                return true;
                }
            else
                {
                AppBundle::getLogger()->error(__METHOD__ . ":" . __LINE__ . ' Entity manager closed');
                if( Request::createFromGlobals()->isXmlHttpRequest() )
                    return false;
                else
                    throw new ORMException();
                }
            }
        catch (ORMException $e)
            {
            AppBundle::getLogger()->error(__METHOD__ . ":" . __LINE__ . ' ORMException');
            return static::exception_treatment( $e );
            }
        catch ( \InvalidArgumentException $e)
            {
            AppBundle::getLogger()->error(__METHOD__ . ":" . __LINE__ . ' InvalidArgumentException');
            return static::exception_treatment( $e );
            }
        catch ( DBALException $e )
            {
            AppBundle::getLogger()->error(__METHOD__ . ":" . __LINE__ . ' DBALException');
            return static::exception_treatment( $e );
            }
    }

    private static function exception_treatment( $e  )
    {
        if( Request::createFromGlobals()->isXmlHttpRequest() )
                return false;
            else
                throw $e;
    }

    /**
     * Ecrire quelque chose dans le journal
     *
     * param $message
     * param $niveau Le niveau de log, Journal::WARNING, Journal::INFO etc.
     *               Voir Entity/Journal.php pour les différents niveaux possibles
     *
     * return l'objet inséré
     *
     ***/
    public static function journalMessage($message, $niveau)
    {
        $journal = new Journal();
        $journal->setStamp( new \DateTime() );

        if( AppBundle::getUser() instanceof Individu )
		{
            $journal->setIdIndividu( AppBundle::getUser()->getId() );
            $journal->setIndividu( AppBundle::getUser() );
		}
        else
		{
            $journal->setIdIndividu( null );
            $journal->setIndividu( null );
		}

        $journal->setGramcSessId( AppBundle::getSession()->getId() );
        $journal->setIp( AppBundle::getClientIp() );
        $journal->setMessage( $message );
        $journal->setNiveau( $niveau );
        $journal->setType( Journal::LIBELLE[$niveau] );

        $em = AppBundle::getManager();

        // nous testons des problèmes de Doctrine pour éviter une exception
        if( AppBundle::getEnvironment() != 'test' )
		{
            if( $em->isOpen() )
			{
                $em->persist( $journal );
                $em->flush();
			}
            else
            {
                AppBundle::getLogger()->error('Entity manager closed, message = ' . $message);
			}
		}

        return $journal;
    }

    public static function emergencyMessage( $message )
    {
            AppBundle::getLogger()->emergency($message);
            Functions::journalMessage($message, Journal::EMERGENCY);
    }

    public static function alertMessage( $message )
    {
            AppBundle::getLogger()->alert($message);
            Functions::journalMessage($message, Journal::ALERT);
    }

    public static function criticalMessage( $message )
    {
            AppBundle::getLogger()->critical($message);
            Functions::journalMessage($message, Journal::CRITICAL);
    }

    public static function errorMessage( $message )
    {
            AppBundle::getLogger()->error($message);
            Functions::journalMessage($message, Journal::ERROR);
    }

    public static function warningMessage( $message )
    {
            AppBundle::getLogger()->warning($message);
            Functions::journalMessage($message, Journal::WARNING);
    }

    public static function noticeMessage( $message )
    {
            AppBundle::getLogger()->notice($message);
            Functions::journalMessage($message, Journal::NOTICE);
    }

    public static function infoMessage( $message )
    {
            AppBundle::getLogger()->info($message);
            Functions::journalMessage($message, Journal::INFO);
    }

    public static function debugMessage( $message )
    {
            AppBundle::getLogger()->debug($message);
            Functions::journalMessage($message, Journal::DEBUG);
    }

/*********************************************************************************************/

    /*****
     * Envoi d'une notification
     *
     * param $twig_sujet, $twig_contenu Templates Twig des messages (ce sont des fichiers)
     * param $params                    La notification est un template twig, le contenu de $params est passé à la fonction de rendu
     * param $users                     Liste d'utilisateurs à qui envoyer ou des emails (cf mailUsers)
     *
     *********/
    static public function sendMessage( $twig_sujet, $twig_contenu, $params, $users = null )
    {
        // Twig avec des extensions
        // $twig = clone AppBundle::getTwig();
        //$twig->setLoader(new \Twig_Loader_String());

        // Twig sans extensions - meilleure sécurité
        /* $twig = new \Twig_Environment( new \Twig_Loader_String(),
                 [
                 'strict_variables' => false,
                 'autoescape' => false,
                 ]);
        */

        $twig       =   AppBundle::getTwig();
        $body       =   $twig->render( $twig_contenu, $params );
        $subject    =   $twig->render( $twig_sujet,   $params);
        static::sendRawMessage( $subject, $body, $users );
    }

    /*****
     * Envoi d'une notification
     *
     * param $twig_sujet, $twig_contenu Templates Twig des messages (ce sont des strings)
     * param $params                    La notification est un template twig, le contenu de $params est passé à la fonction de rendu
     * param $users                     Liste d'utilisateurs à qui envoyer ou des emails (cf mailUsers)
     *
     *********/
    static public function sendMessageFromString( $twig_sujet, $twig_contenu, $params, $users = null )
    {
        // Twig avec des extensions
        $twig = clone AppBundle::getTwig();
        $twig->setLoader(new \Twig_Loader_String());

        // Twig sans extensions - meilleure sécurité
        /* $twig = new \Twig_Environment( new \Twig_Loader_String(),
                 [
                 'strict_variables' => false,
                 'autoescape' => false,
                 ]);
        */

        $body       =   $twig->render( $twig_contenu, $params );
        $subject    =   $twig->render( $twig_sujet,   $params);
        static::sendRawMessage( $subject, $body, $users );
    }


    // Envoi du messages sans templates
    static public function sendRawMessage( $subject, $body, $users = null )
    {
        $message = \Swift_Message::newInstance()
                    ->setSubject( $subject )
                    ->setFrom(  AppBundle::getParameter('mailfrom') )
                    ->setBody($body ,'text/plain');

        if( $users != null )
            {
            $real_users =   [];
            $mails      =   [];

            foreach( $users as $user )
                {
                if( $user instanceof Individu )
                    $real_users[]   =   $user;  // class Individu
                elseif( is_string( $user ) )
                    $mails[]        =   $user;  // email string
                elseif( $users == null  )
                   static::warningMessage(__METHOD__ . ":" . __LINE__ . ' users contient un utilisateur null');
                else
                   static::errorMessage(__METHOD__ . ":" . __LINE__ . ' users contient un mauvais type de données: ' . Functions::show($user));
                }

            if( $mails == [] )
                $warning = true;
            else
                $warning = false;

            $mails  =   array_unique( array_merge( $mails, static::usersToMail( $real_users, $warning ) ) );
            foreach( $mails as $mail )   $message->addTo( $mail);

            // Ecrire une ligne dans le journal et dans les logs
            $to = '';
            if ( $message->getTo() != null )
                {
                $arrayTo = array_keys( $message->getTo() );
                foreach( $arrayTo as $item ) $to = $to . ' ' . $item;
                }

            // debug
            // return [ 'subject'  =>  $message->getSubject(), 'contenu' => $message->getBody(), 'to'  => $to  ]; // debug only

            static::infoMessage('email "' . $message->getSubject() . '" envoyé à ' . $to);

            // Envoi du message
            AppBundle::getMailer()->send($message);
            }
         else
            static::warningMessage(__METHOD__ . ":" . __LINE__ . 'email "' . $message->getSubject() . '" envoyé à une liste vide de destinataires');
    }

    ///////////

    // Renvoie les  utilisateurs associés à un rôle et un objet
    // Params: $mail_roles = liste de roles (A,d,P etc. cf ci-dessous)
    //         $objet      = version (pour E/R) ou thématique (pour ET) ou null (pour les autres roles)
    // Output: Liste d'individus (pour passer à sendMessage)
    //

    static public function mailUsers( $mail_roles = [], $objet = null )
    {
    $users  =   [];

    foreach ( $mail_roles as $mail_role )
        {
            switch( $mail_role )
            {
                case 'D': // demandeur
                    $user = AppBundle::getUser();
                    if( $user != null )
                        $users  =  array_merge( $users, [ $user ] );
                    else
                        Functions::errorMessage(__METHOD__ . ":" . __LINE__ ." Utilisateur n'est pas connecté !");
                    break;
                case 'A': // admin
                    $new_users  =  AppBundle::getRepository(Individu::class)->findBy(['admin'  =>  true ]);
                    if(  $new_users == null )
                        Functions::warningMessage(__METHOD__ . ":"  . __LINE__ .' Aucun admin !');
                    else
                        {
                        if( ! is_array( $new_users ) ) $new_users = $new_users->toArray();
                        $users  =  array_merge( $users, $new_users );
                        }
                    break;
                case 'S': // sysadmin
                    $new_users  =  AppBundle::getRepository(Individu::class)->findBy(['sysadmin'  =>  true ]);
                    if(  $new_users == null )
                        Functions::warningMessage(__METHOD__ . ":"  . __LINE__ .' Aucun sysadmin !');
                    else
                        {
                        if( ! is_array( $new_users ) ) $new_users = $new_users->toArray();
                        $users  =  array_merge( $users, $new_users );
                        }
                    break;
                 case 'P': //président
                    $new_users  =   AppBundle::getRepository(Individu::class)->findBy(['president'  =>  true ]);
                    if(  $new_users == null )
                        Functions::warningMessage(__METHOD__ . ":" .  __LINE__ .' Aucun président !');
                    else
                        {
                        if( ! is_array( $new_users ) ) $new_users = $new_users->toArray();
                        $users  =  array_merge( $users, $new_users );
                        }
                    break;

                case 'E': // expert
                    if( $objet == null )
                        {
                        Functions::warningMessage(__METHOD__ . ":" . __LINE__ .' Objet null pour expert');
                        break;
                        }
                    $new_users  = $objet->getExperts();
                    //Functions::debugMessage(__METHOD__ .":" . __LINE__ .  " experts : " . Functions::show($new_users) );
                    if(  $new_users == null )
                        Functions::warningMessage(__METHOD__ . ":" . __LINE__ ." Aucun expert pour l'objet " . $objet . ' !');
                    else
                        {
                        if( ! is_array( $new_users ) ) $new_users = $new_users->toArray();
                        //Functions::debugMessage(__METHOD__ .":" . __LINE__ .  " experts après toArray : " . Functions::show($new_users) );
                        $users  =  array_merge( $users, $new_users );
                        }
                    break;
                case 'R': // responsable
                    if( $objet == null )
                        {
                        Functions::warningMessage(__METHOD__ . ":" . __LINE__ .' Objet null pour responsable');
                        break;
                        }
                    $new_users  = $objet->getResponsables();
                    if(  $new_users == null )
                        Functions::warningMessage(__METHOD__ . ":" . __LINE__ ." Aucun responsable pour l'objet " . $objet . ' !');
                    else
                        {
                        if( ! is_array( $new_users ) ) $new_users = $new_users->toArray();
                        $users  =  array_merge( $users, $new_users );
                        }
                    break;
                case 'ET': // experts pour la thématique
                    if( $objet == null )
                        {
                        Functions::warningMessage(__METHOD__ . ":" .  __LINE__ .' Objet null pour experts de la thématique');
                        break;
                        }
                    $new_users  = $objet->getExpertsThematique();
                    if(  $new_users == null )
                        Functions::warningMessage(__METHOD__ . ":" . __LINE__ ." Aucun expert pour la thématique pour l'objet " . $objet . ' !');
                    else
                        {
                        if( ! is_array( $new_users ) ) $new_users = $new_users->toArray();
                        $users  =  array_merge( $users, $new_users );
                        }
                    break;
            }
        }
    return $users;
    }

    /////////////////////////
    //
    // obtenir des adresses mail à partir des utilisateurs
    //

    static public function usersToMail( $users, $warning = false )
    {
    $mail   =   [];

    if( $users == null )
        {
        if( $warning == true )
            static::warningMessage(__METHOD__ . ":" . __LINE__ .' La liste des utilisateurs est vide');
        return $mail;
        }

    foreach( $users as $user )
        {
        if( $user != null && $user instanceof Individu )
            {
            $user_mail =  $user->getMail();
            if( $user_mail  !=  null    )
                $mail[] = $user_mail;
            else
                static::warningMessage(__METHOD__ . ":" . __LINE__ . ' Utilisateur '. $user . " n'a pas de mail");
            }
        elseif( $user == null )
            static::errorMessage(__METHOD__ . ":" . __LINE__ . ' Utilisater null dans la liste');
        elseif( ! $user instanceof Individu )
            static::errorMessage(__METHOD__ . ":".  __LINE__ . ' Un objet autre que Individu dans la liste des utilisateurs');
        }

    return array_unique( $mail );
    }

    /***********
    * Renvoie la session courante, c'est-à-dire la PLUS RECENTE session NON TERMINEE
    * 
    * NOTE -  A chaque instant il n'y a qu'UNE session active
    * 
    ************************************************************/
    static function getSessionCourante()
    {
        if( AppBundle::getSession()->has('SessionCourante') )
            return AppBundle::getSession()->get('SessionCourante'); // recall cache

        $sessions = AppBundle::getRepository(Session::class)->get_sessions_non_terminees();
        if( is_array( $sessions ) && count( $sessions ) > 0 )
        {
            reset( $sessions );
            $session = current($sessions);
            AppBundle::getSession()->set('SessionCourante', $session); // set cache
            return $session;
        }
        else
        {
            AppBundle::getSession()->set('SessionCourante', null); // set cache
            return null;
        }
    }

    //////////////////////////////////////////////////

	/***************
	 * Téléchargement d'un fichier csv
	 *****************/
    static public function csv($content, $filename = 'filename')
    {
        $response = new Response();
        $response->setContent($content);
        $response->headers->set('Content-Type','text/csv' );  // télécharger
        $response->headers->set('Cache-Control', 'no-cache, must-revalidate');
        $response->headers->set('Cache-Control','post-check=0,pre-check=0');
        $response->headers->set('Cache-Control','max-age=0');
        $response->headers->set('Pragma','no-cache');
        $response->headers->set('Expires','0');
        $response->headers->set('Content-Disposition','attachment; filename="'.$filename.'"');
        return $response;
    }

	/***************
	 * Téléchargement d'un fichier pdf
	 *****************/
    static public function pdf($filename)
    {
        $response = new Response();
        if( preg_match( '/^\%PDF/', $filename ) )
		{
            //$filename = preg_replace('~[\r\n]+~', '', $filename);
            $response->setContent($filename );
            $response->headers->set('Content-Disposition', 'inline; filename="document_gramc.pdf"' );
		}
        elseif( $filename != null && file_exists( $filename ) && ! is_dir( $filename ) )
		{
            $response->setContent(file_get_contents( $filename ) );
            $response->headers->set('Content-Disposition', 'inline; filename="' . basename($filename) .'"' );
		}
        else
            $response->setContent('');

        $response->headers->set(
           'Content-Type',
           'application/pdf'
        );

        return $response;
    }

    static public function string_conversion($string)
    {
        return str_replace(["\n", "\t", "\r"], '  ', trim($string));
    }

    ////////////////////////////////////////////////////

    // form pour choisir une session

    public static function selectSession(Request $request)
    {
	    $session = Functions::getSessionCourante();
	    $form = AppBundle::createFormBuilder( ['session' => $session ] )
	            ->add('session',   ChoiceType::class,
                    [
                    'multiple' => false,
                    'required'  =>  true,
                    'label'     => '',
                    'choices' =>  AppBundle::getRepository(Session::class)->findBy([],['idSession' => 'DESC']),
                    'choice_label' => function ($session) { return $session->__toString(); },
                    ])
		        ->add('submit', SubmitType::class, ['label' => 'Choisir'])
		        ->getForm();
        $form->handleRequest($request);

        if ( $form->isSubmitted() && $form->isValid() )
            $session = $form->getData()['session'];

        return ['form'  =>  $form, 'session'    => $session ];
    }

    ////////////////////////////////////////////////////

    // form pour choisir une année

    public static function selectAnnee(Request $request, $annee = null)
    {
        if( $annee == null )
            $annee=GramcDate::get()->showYear();

        $form = AppBundle::createFormBuilder( ['annee' => $annee ] )
                ->add('annee',   ChoiceType::class,
                        [
                        'multiple' => false,
                        'required'  =>  true,
                        'label'     => '',
                        'choices'         => array_reverse(Functions::choicesYear( new \DateTime('2000-01-01'), new GramcDate(), 0 ),true),
                        ])
            ->add('submit', SubmitType::class, ['label' => 'Choisir'])
            ->getForm();
        $form->handleRequest($request);

        if ( $form->isSubmitted() && $form->isValid() )
            $annee = $form->getData()['annee'];

        return ['form'  =>  $form, 'annee'    => $annee ];
    }

    ////////////////////////////////////////////////

    // ACL pour Projet

    public static function projetACL(\AppBundle\Entity\Projet $projet)
    {
        if (  AppBundle::isGranted('ROLE_OBS') ||  AppBundle::isGranted('ROLE_PRESIDENT'))
            return true;
        else
            return self::userProjetACL($projet);
    }

    // nous vérifions si un utilisateur a le droit d'accéder à une version d'un projet

    public static function userProjetACL(\AppBundle\Entity\Projet $projet)
    {
        $user = AppBundle::getUser();
        if( ! $user instanceof \AppBundle\Entity\Individu ) return false;

        foreach ( $projet->getVersion() as $version )
            if( static::userVersionACL( $version, $user ) == true ) return true;

        return false;
    }

    // nous vérifions si un utilisateur a le droit d'accès à une version

    public static function userVersionACL(\AppBundle\Entity\Version $version, \AppBundle\Entity\Individu $user)
    {

        // nous vérifions si $user est un collaborateur de cette version

        if( $version->isCollaborateur() ) return true;

        // nous vérifions si $user est un expert de cette version

        if( $version->isExpert() ) return true;

        foreach ( $version->getRallonge() as $rallonge )
        {
            $e = $rallonge->getExpert();
            if ($e != null && $user->isEqualTo($rallonge->getExpert())) return true;
        }

        // nous vérifions si $user est un expert de al thématique

        if( $version->isExpertThematique() ) return true;

        return false;
    }

    // Renvoie une représentation en "string" de la variable passée en input
    // Utilisé pour déboguer
    public static function show( $input )
    {
	    if( $input instanceof \DateTime )
	        return $input->format("d F Y H:i:s");
	    elseif( is_object( $input ) )
	        {
	        $reflect    = new \ReflectionClass($input);
	        if(  method_exists( $input, '__toString') )
	            return '{'.$reflect->getShortName() .':'.$input->__toString().'}';
	        elseif( method_exists( $input, 'toArray') )
	            return '{'.$reflect->getShortName() .':' . static::show( $input->toArray()) .'}';
	        else
	            {
	            ob_start();
	            Debug::dump( $input, 1);
	            //return '{'.$reflect->getShortName().'}';
	            return ob_get_clean();
	            }
	        }
	    elseif( is_string( $input ) )
	        return "'" . $input . "'";
	    elseif( $input === [] )
	        return '[]';
	    elseif( is_array( $input ) )
	        {
	        $output = '[ ';
	        foreach( $input as $key => $value ) $output .= static::show( $key ) . '=>' . static::show( $value ) . ' ';
	        return $output .= ']';
	        }
	    elseif( $input === NULL )
	        return 'null';
	    elseif( is_bool( $input ) )
	        {
	        if( $input == true ) return 'true';
	        else return 'false';
	        }
	    else
	        return $input;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public static function formError( $data, $constraintes )
    {
	    $violations = [];
	    $violations = AppBundle::getContainer()->get('validator')->validate( $data, $constraintes );
	
	    if (0 !== count($violations) )
        {
	        $errors = "<strong>Erreur : </strong>";
	        foreach ($violations as $violation)
	            $errors .= $violation->getMessage() .' ';
	        return $errors;
        }
	    else
	    {
	        return "Erreur indeterminée concernant des données soumises, les limites du système ont été probablement dépassées";
		}
    }

    //////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function dataError( $data, $groups = ['Default'] )
    {
	    $validator = AppBundle::getContainer()->get('validator');
	    if( is_string( $groups ) ) $groups = [$groups];
	    $violations = $validator->validate($data, null, $groups);

	    $erreurs = [];
	    foreach( $violations as $violation )
	        $erreurs[]  =   $violation->getMessage();
	    return $erreurs;
    }
    ///////////////////////////////////////////////////////////////////////////////////////////////////

    // $old et $new sont soit true, soit arrays

    public static function merge_return( $old, $new )
    {
    if( is_array( $old) && is_array( $new ) )
        return array_merge( $old, $new );
    elseif( is_bool( $new ) && is_array( $old) )
        return $old;
    elseif( is_bool( $old )  && is_array( $new ) )
        return $new;
    elseif(  is_bool( $old )  && is_bool( $new ) )
        return $new && $old;
    else
        static::errorMessage(__METHOD__ . " arguments error" . static::show( $new ) . " " . static::show( $old ) );
    return false;
    }

    /////////////////////////////////////////////////////////////////////////////////////
    //
    // informations à propos d'une image liée à une version
    //
    /////////////////////////////////////////////////////////////////////////////////////

    public static function image_parameters( $filename, Version $version)
    {
    $full_filename = static::image_filename( $filename, $version);
    if( file_exists( $full_filename ) && is_file( $full_filename ) )
        {
        $imageinfo  =   [];
        $my_image_info = getimagesize ($full_filename, $imageinfo  );
        return [
            'contents'  =>  base64_encode( file_get_contents( $full_filename ) ),
            'width'     =>  $my_image_info[0],
            'height'    =>  $my_image_info[1],
            'balise'    =>  $my_image_info[2],
            'mime'      =>  $my_image_info['mime'],
            ];
        }
    else
        return [];
    }

    /////////////////////////////////////////////////////////////////////

    static public function image_filename( $filename, Version $version)
    {
    $full_filename  =   static::image_directory( $version ) .'/'.  $filename;

    if( file_exists( $full_filename . ".png" ) && is_file( $full_filename . ".png") )
        $full_filename  =  $full_filename. ".png";
    elseif( file_exists( $full_filename . ".jpeg" ) && is_file( $full_filename . ".jpeg") )
        $full_filename  =  $full_filename. ".jpeg";

    return $full_filename;
    }

    /////////////////////////////////////////////////////////////////////////////////////

    static public function image_directory(Version $version )
    {
    if( ! AppBundle::hasParameter('fig_directory') ) Functions::errorMessage("parameter fig_directory n'existe pas !");

    $dir  =    AppBundle::getParameter('fig_directory');

    if( ! is_dir ( $dir ) )
            {
            if( file_exists( $dir ) && is_file( $dir ) ) unlink( $dir );
            mkdir( $dir );
            Functions::warningMessage("fig_directory " . $dir . " créé !");
            }

    $dir  .= '/'. $version->getProjet()->getIdProjet();
    //Functions::debugMessage($dir);

    if( ! is_dir ( $dir ) )
            {

            if( file_exists( $dir ) && is_file( $dir ) ) unlink( $dir );
            mkdir( $dir );
            }

    $dir  .= '/'. $version->getIdVersion();
    //Functions::debugMessage($dir);

    if( ! is_dir ( $dir ) )
            {
            if( file_exists( $dir ) && is_file( $dir ) ) unlink( $dir );
            mkdir( $dir );
            }

    return $dir;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////

    static public function MenuACL( $menu, $message = "", $method = "", $line = "")
    {
    if( $menu['ok'] == false )
        {
        if( $method != "" && $line != "" )
            $output = $method . ":" . $line;
        elseif( $method != "" )
            $output = $method;
        else
            $output = __METHOD__ . ":" . __LINE__;

        $output .= ' ' . $message . " parce que " . $menu['raison'];
        static::createException( $output );
        }
    }

    public static function noThematique(Individu $individu )
    {
    // thématiques && Doctrine ManyToMany
    foreach($individu->getThematique() as $thematique ) $individu->removeThematique( $thematique );
    $all_thematiques = AppBundle::getRepository(Thematique::class)->findAll();
    foreach($all_thematiques as $thematique ) $thematique->removeExpert($individu);

    AppBundle::getManager()->flush();
    }

   /*********
     * Utilisé seulement en session B
     * renvoie true si l'attribution en A est supérieure à ATTRIB_SEUIL_A et la demande en B supérieure à attr_heures_a / 2
     *
     * param  id_version, $attr_heures_a, $attr_heures_b
     * return true/false
     *
     **************************/
    public static function is_demande_toomuch($attr_heures_a, $dem_heures_b) {

        // Si demande en A = 0, no pb (il s'agit d'un nouveau projet apparu en B)
        if ($attr_heures_a==0) return false;

        // Si demande en B supérieure à attribution en A, pb
        if ($dem_heures_b > $attr_heures_a) return true;

        // Si attribution inférieure au seuil, la somme ne doit pas dépasser 1,5 * seuil
        if ($attr_heures_a < intval(AppBundle::getParameter('attrib_seuil_a'))) {
            if ($dem_heures_b + $attr_heures_a > intval(AppBundle::getParameter('attrib_seuil_a')) * 1.5)
            {
                return true;
            } else {
                return false;
            }
        }
        else
        {
            if ( intval($dem_heures_b) > (intval($attr_heures_a)/2) ) {
                return true;
            } else {
                return false;
            }
        }
    }

    ///////////////////////////////////////////////////////////////////////////////

    public static function createDirectories( $annee = null, $session = null )
    {
    $rapport_directory = AppBundle::getParameter('rapport_directory');
    if( $rapport_directory != null )
        static::createDirectory( $rapport_directory );
    else
        static::createException(__METHOD__ . ":" . __FILE__ . " rapport_directory est null !");

    $fiche_projet_directory = AppBundle::getParameter('signature_directory');
    if( $fiche_projet_directory != null )
        static::createDirectory( $fiche_projet_directory );
    else
        static::createException(__METHOD__ . ":" . __FILE__ . " signature_directory est null !");

    $fig_directory = AppBundle::getParameter('fig_directory');
    if( $fig_directory != null )
        static::createDirectory( $fig_directory );
    else
        static::createException(__METHOD__ . ":" . __FILE__ . " fig_directory est null !");

    if( $session == null )  $session    =   static::getSessionCourante();
    if( $annee == null )    $annee      =   $session->getAnneSession() + 2000;

    static::createDirectory($rapport_directory . '/' . $annee );
    static::createDirectory($fiche_projet_directory . '/' . $session->getIdSession() );
    }

    //////////////////////////////////////////////////////////////////////////////////

    public static function createDirectory( $dir )
    {
    if( $dir != null &&  ! file_exists( $dir ) )
            mkdir( $dir );
    elseif( $dir != null && ! is_dir(  $dir ) )
            {
            static::errorMessage(__METHOD__ . ":" . __FILE__ . " " . $dir . " n'est pas un répertoire ! ");
            unlink( $dir );
            mkdir( $dir );
            }
    }

    /**
     * Retourne toutes les sessions d'une année particulière
     *
     * Param: $annee (2018, 2019, etc)
     * Return: [ $sessionA,$sessionB ] ou [ $sessionA] ou []
     *
     **/
     public function sessionsParAnnee($annee)
     {
         $annee -= 2000;
         $sessions = [];
         $s = AppBundle::getRepository(Session::class)->findOneBy(['idSession' => $annee.'A']);
         if ($s!=null) $sessions[]=$s;
         $s = AppBundle::getRepository(Session::class)->findOneBy(['idSession' => $annee.'B']);
         if ($s!=null) $sessions[]=$s;
         return $sessions;
     }

    /**
     * Filtre la version passee en paramètres, suivant qu'on a demandé des trucs sur les données ou pas
     *        Utilise par donneesParProjet
     *        Modifie le paramètre $p
     *        Renvoie true/false suivant qu'on veut garder la version ou pas
     *
     * Param : $v La version
     *         $p [inout] Tableau représentant le projet
     *
     * Ajoute des champs à $p (voir le code), ainsi que deux flags:
     *         - 'stk' projet ayant demandé du stockage
     *         - 'ptg' projet ayant demandé du partage
     *
     * Return: true/false le 'ou' de ces deux flags
     *
     */

    private function donneesParProjetFiltre($v,&$p)
    {
		$keep_it = false;
		$p               = [];
		$p['p']          = $v->getProjet();
		$p['stk']                = false;
		$p['ptg']				 = false;
		$p['sondVolDonnPerm']    = $v->getSondVolDonnPerm();
		$p['sondVolDonnPermTo']  = preg_replace( '/^(\d+) .+/', '${1}', $p['sondVolDonnPerm']);
		$p['sondJustifDonnPerm'] = $v->getSondJustifDonnPerm();
		$p['dataMetaDataFormat'] = $v->getDataMetaDataFormat();
		$p['dataNombreDatasets'] = $v->getDataNombreDatasets();
		$p['dataTailleDatasets'] = $v->getDataTailleDatasets();
		if ($p['sondVolDonnPerm']   != null
			&& $p['sondVolDonnPerm'] != '< 1To'
			&& $p['sondVolDonnPerm'] != '1 To'
			&& strpos($p['sondVolDonnPerm'],'je ne sais') === false
			) $keep_it = $p['stk'] = true;
		if ($p['dataMetaDataFormat'] != null && strstr($p['dataMetaDataFormat'],'intéressé') == false) $keep_it = $p['ptg'] = true;
		if ($p['dataNombreDatasets'] != null && strstr($p['dataNombreDatasets'],'intéressé') == false) $keep_it = $p['ptg'] = true;
		if ($p['dataTailleDatasets'] != null && strstr($p['dataTailleDatasets'],'intéressé') == false) $keep_it = $p['ptg'] = true;
		return $keep_it;
	}

	/*
    *  Ajoute le champ 'c,q,f' au tableau $p:
    *         c => conso
    *         q => quota en octets
    * 		  q => quota en To (nombre entier)
    *
    */
    private function addConsoStockage(&$p,$annee,$ress) {
		if ($ress === "")
		{
			$p['q']  = 0;
			$p['qt'] = 0;
			$p['c']  = 0;
			$p['ct'] = 0;
			$p['cp'] = 0;
		}
		else
		{
	        $conso = $p['p']->getConsoRessource($ress,$annee);
	        $p['q']  = $conso[1];
	        $p['qt'] = intval($p['q']/(1024*1024*1024));
	        $p['c']  = $conso[0];
	        $p['ct'] = intval($p['c']/(1024*1024*1024));
	        $p['cp'] = ($p['q'] != 0) ? 100*$p['c']/$p['q'] : 0;
		}
    }

    /**
     * Liste tous les projets pour lesquels on a demandé des données en stockage ou en partage
     *       Utilise par ProjetController
     *
     * Param : $annee
     * Return: [ $projets, $total ] Un tableau de tableaux pour les projets, et les données consolidées
     *
     */
	public function donneesParProjet($annee)
	{
		$total   = [];
		$projets = [];

		$total['prj']     = 0;	// Nombre de projets
		$total['sprj']    = 0;	// Nombre de projets ayant demandé du stockage
		$total['pprj']    = 0;	// Nombre de projet ayant demandé du partage
		$total['autostk'] = 0;	// Nombre de To attribués automatiquement (ie 1 To / projet)
		$total['demstk']  = 0;	// Nombre de To demandés (> 1 To / projet)
		$total['attrstk']  = 0; // Nombre de To alloués suite à une demande

        // $annee = 2017, 2018, etc. (4 caractères)
        $session_id_A = substr($annee, 2, 2) . 'A';
        $session_id_B = substr($annee, 2, 2) . 'B';
        $session_A = AppBundle::getRepository(Session::class)->findOneBy(['idSession' => $session_id_A ]);
        $session_B = AppBundle::getRepository(Session::class)->findOneBy(['idSession' => $session_id_B ]);

        $versions_A= AppBundle::getRepository(Version::class)->findBy( ['session' => $session_A ] );
        $versions_B= AppBundle::getRepository(Version::class)->findBy( ['session' => $session_B ] );

        /* Ressource utilisée pour déterminer l'occupation et le quota:
         *
         * Regarde le paramètre ressources_conso_group et prend la première de type 'stockage'
         *         S'il y en a plusieurs... problème !
         *         S'il n'y en a aucune... on ne fait rien
         */
		$ress = "";
		if ( AppBundle::hasParameter('ressources_conso_group'))
		{
			$ressources = AppBundle::getParameter('ressources_conso_group');
			foreach ($ressources as $k=>$r)
			{
				if ($r['type']==='stockage')
				{
					$ress = $r['ress'];
				}
			}
		}

		// Boucle sur les versions de la session B
		$projets_b = [];
        foreach ( $versions_B as $v)
        {
			$total['prj'] += 1;
			$p = [];
			$p_id = $v->getProjet()->getIdProjet();
			$keep_it = Functions::donneesParProjetFiltre($v,$p);
			//if ($keep_it === true)
			//{
				Functions::addConsoStockage($p,$annee,$ress);
				if ($p['stk'])
				{
					$total['sprj']    += 1;
					$total['demstk']  += $p['sondVolDonnPermTo'];
					$total['attrstk'] += $p['qt'];
				}
				else
				{
					$total['autostk'] += 1;
				}
				if ($p['ptg'])
				{
					$total['pprj'] += 1;
				}
	            $projets[$p_id] = $p;
			//}
			//else
			if ($keep_it === false)
			{
				$total['autostk'] += 1;
			}
			$projets_b[] = $p_id;
        }

		// Boucle sur les versions de la session A
        foreach ( $versions_A as $v)
        {
			$p_id = $v->getProjet()->getIdProjet();
			if (!in_array($p_id,$projets_b))
            {
				$p = [];
				$total['prj'] += 1;

				$keep_it = Functions::donneesParProjetFiltre($v,$p);

				//if ($keep_it === true) {
					Functions::addConsoStockage($p,$annee,$ress);
		            $projets[$p_id] = $p;
					if ($p['stk'])
					{
						$total['sprj']    += 1;
						$total['demstk']  += $p['sondVolDonnPermTo'];
						$total['attrstk'] += $p['qt'];
					}
					else
					{
						$total['autostk'] += 1;
					}
					if ($p['ptg'])
					{
						$total['pprj'] += 1;
					}
				//}
				//else
				if ($keep_it === false)
				{
					$total['autostk'] += 1;
				}
			}
		}

        return [$projets,$total];
	}

    /**
     * Liste tous les projets qui ont une version cette annee
     *       Utilise par ProjetController et AdminuxController, et aussi par StatistiquesController
     *
     * Param : $annee
     *         $isRecupPrintemps (true/false, def=false) -> Calcule les heures récupérables au printemps
     *         $isRecupAutomne (true/false, def=false)   -> Calcule les heures récupérables à l'Automne
     * 
     * Return: [ $projets, $total ] Un tableau de tableaux pour les projets, et les données consolidées
     *
     * NOTE - Si un projet a DEUX VERSIONS et change de responsable, donc de laboratoire, au cours de l'année, 
     *        on affiche les données de la VERSION A (donc celles du début d'année)
     * 		  Cela peut conduire à une erreur à la marge dans les statistiques
     * 
     */

     // Ajoute les champs 'c','g','q', 'cp' au tableau $p
    private function ppa_conso(&$p,&$annee) {
        $conso_cpu = $p['p']->getConsoRessource('cpu',$annee);
        $conso_gpu = $p['p']->getConsoRessource('gpu',$annee);
        $p['c'] = $conso_cpu[0] + $conso_gpu[0];
        $p['q'] = $conso_cpu[1];
        $p['g'] = $conso_gpu[0];
        $p['cp']= ($p['q']>0) ? (100.0 * $p['c']) / $p['q'] : 0;
    }

    public function projetsParAnnee($annee,$isRecupPrintemps=false,$isRecupAutomne=false)
    {
        // Données consolidées
        $total = [];
        $total['prj']         = 0;  // Nombre de projets (A ou B)
        $total['demHeuresA']  = 0;  // Heures demandées en A
        $total['attrHeuresA'] = 0;  // Heures attribuées en A
        $total['demHeuresB']  = 0;  // Heures demandées en B
        $total['attrHeuresB'] = 0;  // Heures attribuées en B

        $total['rall']        = 0;  // Nombre de rallonges
        $total['demHeuresR']  = 0;  // Heures demandées dans des rallonges
        $total['attrHeuresR'] = 0;  // Heures attribuées dans des rallonges

        $total['prjTest']     = 0;  // Nombre deprojets tests
        $total['demHeuresT']  = 0;  // Heures demandées dans des projets tests
        $total['attrHeuresT'] = 0;  // Heures attribuées dans des projets tests

        $total['demHeuresP']  = 0;  // Nombre d'heures demandées: A+B+Rallonges
        $total['attrHeuresP'] = 0;  // Heures attribuées aux Projets: A+B+Tests+Rallonges-Pénalité
        $total['consoHeuresP']= 0;  // Heures consommées
        $total['recupHeuresP']= 0;  // Heures récupérables


        $total['penalitesA']  = 0;  // Pénalités de printemps (sous-consommation entre Janvier et Juin)
        $total['penalitesB']  = 0;  // Pénalités d'Automne (sous-consommation l'été)

        // $annee = 2017, 2018, etc. (4 caractères)
        $session_id_A = substr($annee, 2, 2) . 'A';
        $session_id_B = substr($annee, 2, 2) . 'B';
        $session_A = AppBundle::getRepository(Session::class)->findOneBy(['idSession' => $session_id_A ]);
        $session_B = AppBundle::getRepository(Session::class)->findOneBy(['idSession' => $session_id_B ]);

        $versions_A= AppBundle::getRepository(Version::class)->findBy( ['session' => $session_A ] );
        $versions_B= AppBundle::getRepository(Version::class)->findBy( ['session' => $session_B ] );

        // $mois est utilisé pour calculer les éventuelles pénalités d'été
        // Si on n'est pas à l'année courante, on le met à 0 donc elles ne seront jamais calculées
        $annee_courante=GramcDate::get()->showYear();
        if ($annee == $annee_courante)
        {
            $mois  = GramcDate::get()->showMonth();
        }
        else
        {
            $mois = -1;
        }

        $projets= [];

        // Boucle sur les versions de la session A
        foreach ( $versions_A as $v)
        {
            $p_id = $v->getProjet()->getIdProjet();
            $p = [];
            $p['p']      = $v->getProjet();
            $p['va']     = $v;
            $p['penal_a']= $v->getPenalHeures();
            $p['labo']   = $v->getLabo();
            $p['resp']   = $v->getResponsable();

            // Ces champs seront renseignés en session B
            $p['vb']      = null;
            $p['penal_b'] = 0;
            $p['attrete'] = 0;
            $p['consoete']= 0;

            $rallonges = $v->getRallonge();
            $p['r'] = 0;
            $p['attrib']     = $v->getAttrHeures();
            $p['attrib']    -= $v->getPenalHeures();
            foreach ($rallonges as $r)
            {
                if ($r->getEtatRallonge() != Etat::EDITION_DEMANDE && $r->getEtatRallonge() != Etat::EDITION_EXPERTISE && $r->getEtatRallonge() != Etat::EN_ATTENTE && $r->getEtatRallonge() != Etat::ANNULE) {
                    $total['rall']        += 1;
                    $total['demHeuresR']  += $r->getDemHeures();
                    $total['demHeuresP']  += $r->getDemHeures();
                    $total['attrHeuresR'] += $r->getAttrHeures();
                    $total['attrHeuresP'] += $r->getAttrHeures();
                    $p['r']               += $r->getAttrHeures();
                    $p['attrib']          += $r->getAttrHeures();
                }
            }

            if ($v->getEtatVersion() != Etat::EDITION_DEMANDE ) {
                $total['prj'] += 1;
                $total['demHeuresP']  += $v->getDemHeures();
                $total['attrHeuresP'] += $v->getAttrHeures();
                $total['demHeuresA']  += $v->getDemHeures();
                $total['attrHeuresA'] += $v->getAttrHeures();
                $total['penalitesA']  += $v->getPenalHeures();
                $total['attrHeuresP'] -= $v->getPenalHeures();
            }
            if ( $v->getProjet()->isProjetTest() )
            {
                if ($v->getEtatVersion() != Etat::EDITION_DEMANDE ) {
                    $total['prjTest']     += 1;
                    $total['demHeuresT']  += $v->getDemHeures();
                    $total['attrHeuresT'] += $v->getAttrHeures();
                }
            }

            // La conso
            Functions::ppa_conso($p,$annee);
            $total['consoHeuresP'] += $p['c'];

            // Récup de Printemps
            if ($isRecupPrintemps==true) {
                $p['recuperable'] = SessionController::calc_recup_heures_printemps($p['c'],intval($p['attrib'])+intval($p['r']));
                $total['recupHeuresP'] += ($v->getPenalHeures()==0)?$p['recuperable']:0;
            } else {
                $p['recuperable'] = 0;
            }

            $projets[$p_id] = $p;

        }

        // Boucle sur les versions de la session B
        foreach ( $versions_B as $v)
		{
            $p_id = $v->getProjet()->getIdProjet();
            if (isset($projets[$p_id])) {
                $p = $projets[$p_id];
            } else {
                $total['prj']         += 1;
                $p = [];
                $p['p'] = $v->getProjet();
                $p['va'] = null;
                $p['penal_a']     = 0;
                $p['recuperable'] = 0;
                $p['r']  = 0;
                $p['attrib'] = 0;
                $p['labo']   = $v->getLabo();         // Si version A et B on choisit le labo
				$p['resp']   = $v->getResponsable();  // et le responsable de la version B (pas obligatoirement le même)


            }
            $p['vb']      = $v;
            $rallonges    = $v->getRallonge();
            foreach ($rallonges as $r)
            {
                if ($r->getEtatRallonge() != Etat::EDITION_DEMANDE && $r->getEtatRallonge() != Etat::EDITION_EXPERTISE && $r->getEtatRallonge() != Etat::EN_ATTENTE && $r->getEtatRallonge() != Etat::ANNULE) {
                    $total['rall']        += 1;
                    $total['demHeuresR']  += $r->getDemHeures();
                    $total['demHeuresP']  += $r->getDemHeures();
                    $total['attrHeuresP'] += $r->getAttrHeures();
                    $total['attrHeuresR'] += $r->getAttrHeures();
                    $p['r']               += $r->getAttrHeures();
                    $p['attrib']          += $r->getAttrHeures();
                }
            }

            // S'il y a eu une attrib en session A, on verifie que la demande B ne soit pas toomuch
            if ( !empty($p['va'])) {
                $p['toomuch'] = Functions::is_demande_toomuch($p['va']->getAttrHeures(),$p['vb']->getDemHeures());
            } else {
                $p['toomuch'] = false;
            }

            $p['attrib'] += $v->getAttrHeures();

            // Pénalités déja appliquée en session B
            $p['penal_b'] = $v->getPenalHeures();
            $p['attrib'] -= $p['penal_b'];

            $total['demHeuresP']  += $v->getDemHeures();
            $total['attrHeuresP'] += $v->getAttrHeures();
            $total['demHeuresB']  += $v->getDemHeures();
            $total['attrHeuresB'] += $v->getAttrHeures();
            $total['penalitesB']  += $v->getPenalHeures();
            $total['attrHeuresP'] -= $v->getPenalHeures();

            // La conso (attention à ne pas compter deux fois la conso pour les projets déjà entamés !)
			Functions::ppa_conso($p,$annee);
            if ($v->isNouvelle())
            {
                $total['consoHeuresP'] += $p['c'];
            }

            // Pour le calcul des pénalités d'Automne
            $p['attrete'] = $v->getAttrHeuresEte();

            // Penalites d'automne. Elles dépendent de la consommation des mois de Juillet et d'Août
            if ($isRecupAutomne==true) {
				$d = $annee_courante.'-07-01';
				$f = $annee_courante.'-09-01';
                $p['consoete']    = $v->getProjet()->getConsoIntervalle(['cpu','gpu'],[$d,$f]);
                $p['recuperable'] = SessionController::calc_recup_heures_automne($p['consoete'],$p['attrete']);
                $total['recupHeuresP'] += ($v->getPenalHeures()==0)?$p['recuperable']:0;
            
            // Si recuPrintemps est à true, 'recuperable' est déjà calculé, ne pas y toucher
            // NB - Oui il y a des gens qui ne consomment pas en A et qui demandent des heures en B !
            } elseif ($isRecupPrintemps==false) {
                $p['recuperable'] = 0;
            }

			$projets[$p_id] = $p;
		}

		return [$projets,$total];
    }

    // supprimer les répertoires

    public static function erase_parameter_directory( $parameter, $projet = 'none')
        {
        if( AppBundle::hasParameter($parameter) )
            {
            $dir = AppBundle::getParameter($parameter);
            static::erase_directory( $dir, $projet );
            }
        }

    public static function erase_directory( $dir, $projet = 'none' )
        {
        if( file_exists($dir) && is_dir( $dir ) )
                {
                $files = glob($dir . '*', GLOB_MARK);

                foreach ($files as $file)
                    if (  is_dir($file) )
                        {
                        if( preg_match ( '/' .  $projet . '/' , $file ) )
                            static::erase_directory( $file, '' );
                        else
                            static::erase_directory( $file, $projet );
                        }
                    elseif( preg_match ( '/' .  $projet . '/' , $file ) )
                        {
                        //static::debugMessage(__FILE__ . ":" . __LINE__ . " fichier " . $file . " est effacé ");
                        unlink($file);
                        }
                if( count( scandir( $dir ) ) == 2 )
                    rmdir($dir);
                }
            else
                static::warningMessage(__FILE__ . ":" . __LINE__ . " répértoire " . $dir . " n'existe pas ou ce n'est pas un répértoire ");
        }


    //////////////////////////////////////////////////////////////////////////////////////////
    //
    // effacer utilisateurs
    //

    public static function effacer_utilisateurs( $individus = null )
        {
        $individus_effaces = [];
        $em = AppBundle::getManager();

        foreach( $individus as $individu )
            if(
                AppBundle::getRepository(CollaborateurVersion::class)->findOneBy( [ 'collaborateur' => $individu ] ) == null
                &&
                AppBundle::getRepository(Expertise::class)->findOneBy( [ 'expert' => $individu ] ) == null
                &&
                AppBundle::getRepository(Session::class)->findOneBy( [ 'president' => $individu ] ) == null
                &&
                $individu->getAdmin() == false && $individu->getExpert() == false && $individu->getPresident() == false
                )
                {
                $individus_effaces[] = clone $individu;

                foreach( AppBundle::getRepository(Sso::class)->findBy(['individu' => $individu]) as $sso )
                    $em->remove($sso);

                foreach( AppBundle::getRepository(CompteActivation::class)->findBy(['individu' => $individu]) as $sso )
                    $em->remove($sso);

                Functions::infoMessage("L'utilisateur " . $individu . ' a été effacé ');
                $em->remove($individu);
                }

         $em->flush();
         return $individus_effaces;
        }

    ///////////////////////////////////////////////////////////////////////////////////////////////
    //
    // utilisateurs à effacer
    //

    public static function utilisateurs_a_effacer($individus = [])
	{
        $individus_effaces = [];

        foreach( $individus as $individu )
            if(
                AppBundle::getRepository(CollaborateurVersion::class)->findOneBy( [ 'collaborateur' => $individu ] ) == null
                &&
                AppBundle::getRepository(Expertise::class)->findOneBy( [ 'expert' => $individu ] ) == null
                &&
                AppBundle::getRepository(Session::class)->findOneBy( [ 'president' => $individu ] ) == null
                &&
                $individu->getAdmin() == false && $individu->getExpert() == false && $individu->getPresident() == false
                )
                $individus_effaces[] = $individu;

         return $individus_effaces;
	}

	////////////////////////
	// Supprime TOUS les fichiers du répertoire de sessions
	// Tant pis s'il y avait des fichiers autres que des fichiers de session symfony
	// (ils n'ont rien à faire ici de toute manière)
	//
	public static function clear_phpsessions()
	{
		$dir = session_save_path();
	    $scan = scandir( $dir );
	    $result = true;
	    foreach ( $scan as $filename )
	    {
			if( $filename != '.' && $filename != '..' )
			{
				$path = $dir . '/' . $filename;
				if (@unlink($path)==false)
				{
					Functions::errorMessage(__METHOD__ . ':' . __LINE__ . " Le fichier $path n'a pas pu être supprimé !" );
					$result = false;
				}
			}
		}
		return $result;
	}
	
	/**********
	 * Renvoie un tableau avec la liste des connexions actives
	 **********************************************************/
	 public static function getConnexions()
	 {
	    $connexions = [];
 	    $dir = session_save_path();
	    $scan = scandir( $dir );
	
	    $save = $_SESSION;
	    $time = time();
	    foreach ( $scan as $filename )
	    {
	        if( $filename != '.' && $filename != '..' )
            {
	            $atime = fileatime( $dir . '/' . $filename );
	            $mtime = filemtime( $dir . '/' . $filename );
	            $ctime = filectime( $dir . '/' . $filename );
	            //$atime = max ( [ $atime, $mtime, $ctime ] );
	
	            $diff  = intval( ($time - $mtime) / 60 );
	            $min   = $diff % 60;
	            $heures= intVal($diff/60);
	            $contents = file_get_contents( $dir . '/' . $filename );
	            session_decode($contents );
	
	            if(  ! array_key_exists('_sf2_attributes', $_SESSION ) )
	                Functions::errorMessage(__METHOD__ . ':' . __LINE__ . " Une session autre que symfony !" );
	            elseif( array_key_exists('real_user', $_SESSION['_sf2_attributes'] ) )
	                {
	                $user = $_SESSION['_sf2_attributes']['real_user'];
	                $individu = AppBundle::getRepository(Individu::class)->find( $user->getIdIndividu() );
	                if( $individu == null )
	                    Functions::errorMessage(__METHOD__ . ':' . __LINE__ . " Problème d'individu " . $user );
	                else
	                    $connexions[] = [ 'user' => $individu, 'minutes' => $min,'heures' => $heures ];
	                }
	            elseif( ! array_key_exists( '_security_consoupload', $_SESSION['_sf2_attributes'] ) )
	                Functions::errorMessage(__METHOD__ . ':' . __LINE__ . " Problème avec le fichier session " . $filename );
	
            }
		}
	    $_SESSION = $save;
	    return $connexions;
	}
}
