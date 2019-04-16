<?php

/*
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

namespace AppBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use AppBundle\Entity\Param;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;
use Twig\Extensions\TextExtension;

use AppBundle\Politique\Politique;
use AppBundle\Politique\CpuPolitique;
use AppBundle\Politique\GpuPolitique;

use AppBundle\Utils\Functions;
use AppBundle\Utils\AppBundleInitialize;

/*****************************
 * Cette classe permet d'utiliser les services Symfony à partir d'un environnement qui ne soit pas un controleur
 * 
 * Exemple: Pour faire des appels à la base de données:
 * 
 *          $em = AppBundle::getManager();
 * 
 ******************/ 
class AppBundle extends Bundle
{

    protected static $static_container      =   null;
    protected static $static_manager        =   null;
    protected static $static_manager_copy   =   null;
    protected static $politiques            =   [];
    
    public function boot()
    { 
        parent::boot();
        self::$static_container     =   $this->container;
        self::$static_manager       =   $this->container->get('doctrine')->getManager();
        self::$static_manager_copy  =   null;
        
	$this->container->get('twig')->addExtension(new TextExtension());

        // nous définissons des politiques
        self::$politiques[ Politique::POLITIQUE ]       = new Politique();
        self::$politiques[ Politique::CPU_POLITIQUE ]   = new CpuPolitique();
        self::$politiques[ Politique::GPU_POLITIQUE ]   = new GpuPolitique();
    }

    public static function getPolitique($politique)
    {
            /*
            if( static::$static_container == null )
            {
            Functions::warningMessage(__METHOD__ . ":" . __LINE__ . " static container null ");
            $init = new AppBundleInitialize();
            }
            */
            
            //if( $politique != 1 )
            //    Functions::debugMessage(__METHOD__ . ":" . __LINE__ . " la politique est (" . $politique . ")");
                
            if( array_key_exists( $politique, self::$politiques ) )
                return self::$politiques[$politique];
            else
                {
                Functions::errorMessage(__METHOD__ . ":" . __LINE__ . " la politique " . $politique . " n'existe pas ");
                return self::$politiques[   Politique::DEFAULT_POLITIQUE    ]; // nous mettons la politique par défaut
                }
    }

    public static function setStaticContainer( $container )
    {
        if( $container != null ) self::$static_container = $container;
    }


    // pour mocking Entity Manager

    public static function setEntityManager( $manager )
    {
        if( $manager != null )
            {
            if( self::$static_manager_copy == null)
                self::$static_manager_copy  =  self::$static_manager;
                 
            self::$static_manager = $manager;
            }
    }

    public static function resetEntityManager()
    {
        if( self::$static_manager_copy != null )
            {
                self::$static_manager       =  self::$static_manager_copy;
                self::$static_manager_copy  =   null; 
            }
    }

    // client pour tests fonctionnels
    public static function createClient( array $server = [] )
    {
        $client = self::getContainer()->get('test.client');
        
        if( $server == null )
            $client->setServerParameters(
                [
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW'   => 'test',
                ]);
        else
            $client->setServerParameters($server);
            
        return $client;
    }
    
    public static function getEnvironment() { return self::getKernel()->getEnvironment(); }
    public static function getContainer()   { return self::$static_container; }
    public static function getKernel()      { return self::$static_container->get('kernel'); }
    
    public static function getDoctrine()    { return self::$static_container->get('doctrine'); }
    public static function getManager()
        {
             if( self::$static_manager != null )
                return self::$static_manager;
             else
                return self::$static_container->get('doctrine')->getManager();
        }
    public static function getRepository($class)
        {
             if( self::$static_manager != null )
                return self::$static_manager->getRepository($class);
             else
                return self::$static_container->get('doctrine')->getManager()->getRepository($class);
        }

    public static function getLogger()      { return self::$static_container->get('logger'); }
    public static function getSession()     { return self::$static_container->get('session'); }

    public static function getAuth()        { return self::$static_container->get('security.authorization_checker'); }
    
    public static function isGranted($role)
        {
            // un bogue obscure de symfony
            try
                {
                if( self::$static_container->has('security.authorization_checker') )
                    return self::$static_container->get('security.authorization_checker')->isGranted( $role );
                else
                    return false;
                }
            catch (AuthenticationCredentialsNotFoundException $e)
                {
                return false; 
                }
            catch ( \Exception $e)
                {
                return false; 
                }
        }
    
    public static function getToken()      { return self::$static_container->get('security.token_storage')->getToken(); }
    public static function getCsrfToken($param = null)
        { return self::$static_container->get('security.csrf.token_manager')->getToken($param); }
    
    public static function getUser()
    {
        $token = self::$static_container->get('security.token_storage')->getToken();
        if( $token != null )
            return $token->getUser();
        else
            return null;
    }
    
    public static function getRoles()
    {
        $token = self::$static_container->get('security.token_storage')->getToken();
        if( $token != null )
            return $token->getRoles();
        else
            return [];
    }
    
    public static function getMailer()      { return self::$static_container->get('mailer'); }
    public static function getTwig()        { return self::$static_container->get('twig'); }

    public static function getAssets()      { return self::$static_container->get('templating.helper.assets'); }
    
    public static function getRouter()      { return self::$static_container->get('router'); }

    public static function getClientIp()
    {
    if(    static::$static_container->get('request_stack') instanceof  RequestStack
        && static::$static_container->get('request_stack')->getMasterRequest() instanceof  Request 
        && static::$static_container->get('request_stack')->getMasterRequest()->getClientIp() != null
      )
        return self::$static_container->get('request_stack')->getMasterRequest()->getClientIp();
    else
        return '127.127.127.127'; // pour le debug seulement
    }
    
    public static function getFormBuilder( $nom = 'form', $class = FormType::class, $options = [] )
    { 
        return  self::$static_container->get( 'form.factory')->createNamedBuilder($nom, $class, null, $options );
    }

    public static function createFormBuilder( $data = null, $options = [] )
    {
        return self::$static_container->get('form.factory')->createBuilder(FormType::class, $data, $options);
    }

    public static function getPDF($html)
    {
        return self::$static_container->get('knp_snappy.pdf')->getOutputFromHtml($html);
    }    
     
    public static function getParameter($parameter)
    {            
        // pour contourner un problème obscur de la console quand la table ou la classe Param n'existe pas
        try {
            if( self::getRepository(Param::class) != null )
                $parameter_structure = self::getRepository(Param::class)->findOneBy( [ 'cle' => $parameter ] );
            else
                $parameter_structure = null;
        }
        catch (Exception $e) { 
            return self::$static_container->getParameter($parameter);
        }
            
        if( $parameter_structure != null )
            return $parameter_structure->getVal();
        else
            return self::$static_container->getParameter($parameter);
    }
        
    public static function hasParameter($parameter)
    {
        if ( self::$static_container == null ) return false;
        
        // pour contourner un problème  obscure de la console  quand la table ou la classe Param n'existe pas
        try { 
            if( self::getRepository(Param::class) != null )
                $parameter_structure = self::getRepository(Param::class)->findOneBy( [ 'cle' => $parameter ] );
            else
                $parameter_structure = null;
        }
        catch (Exception $e) {
            return self::$static_container->hasParameter($parameter); 
        }
            
        if( $parameter_structure != null )
            return true;
        else
            return self::$static_container->hasParameter($parameter);
    }
        
}
