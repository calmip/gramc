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

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use AppBundle\AppBundle;
use AppBundle\Entity\Projet;
use AppBundle\Entity\Version;
use AppBundle\Entity\Consommation;
use AppBundle\Entity\Individu;

use AppBundle\Utils\Functions;
use AppBundle\Workflow\MessageTransition;

use libphonenumber\PhoneNumberUtil;
use libphonenumber\NumberParseException;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

use JpGraph\JpGraph;

class DefaultController extends Controller
{
    /**
     * @Route("/symfony", name="homepage")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function indexAction(Request $request)
    {
        
    return $this->render('default/base_test.html.twig');

        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
        ]);
    }

    /**
     * @Route("/test", name="test")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function testAction(Request $request)
    {
    $projet = AppBundle::getRepository(Projet::class)->findOneBy(['idProjet' => 'P1440']);
    //return new Response( count( $projet->getVersion() ) );
    //return new Response( gettype($projet->calculDerniereVersion()  ));
    return new Response( $projet->derniereVersion()->getSession() );
    return new Response( $projet->calculDerniereVersion()->getSession() );
    return new Response( $projet->getVersionDerniere()->getSession() );




    
    $query = AppBundle::getManager()->createQuery('SELECT partial u.{idIndividu,nom} AS individu, partial s.{eppn} AS sso, count(s) AS score FROM AppBundle\Entity\Individu u JOIN u.sso s GROUP BY u');
    $result = $query->getResult();
    //$version = AppBundle::getRepository(Version::class)->findDerniereVersion( $projet  );
    
    return new Response( get_class(  $result[0]['individu'] ));
    return new Response( gettype(  $result[0]['individu'] ));
    return new Response(implode(" ",array_keys( $result[0] ) ));
    return new Response($result[0]['score'] );
    
    if( gettype($result) == 'array' )
        return new Response( gettype( end($result) ) );
    else
        return new Response( gettype($result)  );
       
    return new Response( implode(" ", array_keys($result) ) );
    }

    /**
     * @Route("/twig", name="twig")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function twigAction(Request $request)
    {
    $users  =   [ 'a@x', 'b@x' ];
    $users  =   AppBundle::getRepository(Individu::class)->findBy( ['president' => true ] );
    $versions   =  AppBundle::getRepository(Version::class)->findAll();
    $users  =   Functions::mailUsers( [ 'E','R' ], $versions[301] );
    
    $output = Functions::sendMessage( 'projet/dialog_back.html.twig' ,'projet/dialog_back.html.twig' , [ 'projet' => [ 'idProjet' => 'ID' ] ], $users);

    //return new Response ( $users[0] );
    
    //return new Response ( Functions::getSessionCourante()->getPresident() );
    
    return new Response( $output['to'] );
    return new Response( $output['contenu'] );
    return new Response( $output['subject'] );
    }

    /**
     * @Route("/message", name="message")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function messageAction(Request $request)
    {
    $users  =   [ 'a@x', 'b@x' ];
    $users  =   AppBundle::getRepository(Individu::class)->findBy( ['president' => true ] );
    $versions   =  AppBundle::getRepository(Version::class)->findAll();
    $users  =   Functions::mailUsers( [ 'E','R' ], $versions[301] );
    $version    =  $versions[301];
    $transition =   new MessageTransition( $version->getEtatVersion(), null, 'expertise', [ 'E','R' ] );
    $string     =   $transition->__toString();
    $transition->execute( $version );
    return new Response( $string ); 
    
    $output = Functions::sendMessage( 'projet/dialog_back.html.twig' ,'projet/dialog_back.html.twig' , [ 'projet' => [ 'idProjet' => 'ID' ] ], $users);

    //return new Response ( $users[0] );
    
    //return new Response ( Functions::getSessionCourante()->getPresident() );
    
    return new Response( $output['to'] );
    return new Response( $output['contenu'] );
    return new Response( $output['subject'] );
    }
    
    /**
     * @Route("/message2", name="message2")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function message2Action(Request $request)
    {
    $version = AppBundle::getRepository(Version::class)->findOneBy(['idVersion' => '18BP18045' ] );
    if( $version != null )
        {
        //return new Response( Functions::show( $version->getPolitique() ));
        return new Response( Functions::show( $version->getData() ));
        }
    else
        return new Response( '$version == null' );
        
    return $this->render('notification/compte_ouvert-contenu.html.twig'); 
    }

  /**
     * @Route("/test_projet", name="test_projet")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function test_projetAction(Request $request)
    {
       $projets = AppBundle::getRepository(Projet::class)->get_projets_resp_ou_collab('31', false, true);
       return new Response( $projets[0]->getIdProjet() );
    }

    /**
     * @Route("/count", name="count")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function countAction(Request $request)
    {

        $annee = 2017;

        $projets = AppBundle::getRepository(Projet::class)->heuresProjetsAnnee($annee, Functions::TOUS);
       //return new Response( Functions::show( $projets )); 
 
        
       $projets = AppBundle::getRepository(Projet::class)->heuresProjetsAnnee($annee);
       //return new Response( Functions::show( $projets )); 

       $projets = AppBundle::getRepository(Projet::class)->findProjetsAnnee($annee, Functions::TOUS);

       $conso = 0;
       foreach( $projets as $projet )
            {
            $consoProjet    =  $projet->getConso($annee);
            if(  $consoProjet < 0 )
                Functions::errorMessage(__METHOD__ . ':' . __FILE__ . " consommation du projet " . $projet . " pour l'année " . $annee . " negative !");
            else
                $conso  +=  $consoProjet;
            }
            
       return new Response( "conso = " . $conso . "  ". Functions::show( $projets )); 

       $projets = AppBundle::getRepository(Projet::class)->countProjetsAnnee($annee);
       return new Response( Functions::show( $projets ));
    }
    
    /**
     * @Route("/test_params/{id1}/{id2}", name="test_params")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function test_paramsAction(Request $request)
    {
       return new Response( 'ok' );
    }
/**
     * @Route("/test_session", name="test_session")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function test_sessionAction(Request $request)
    {
       return new Response( var_dump( Functions::getSessionCourante() ) );
    }

   /**
     * @Route("/test_form", name="test_session")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function test_formAction(Request $request)
    {
        $form = $this
           ->get('form.factory')
           ->createNamedBuilder('image_form', FormType::class, [])
           ->add('image', TextType::class, [ 'required'       =>  false,] )
           ->add('number', TextType::class, ['required'       =>  false,] )
           ->getForm();

        $form->handleRequest($request);

        //if ($form->isSubmitted() )
        print_r($_POST, true);
        
        return $this->render('version/test_form.html.twig',
            [
            'form'       =>   $form->createView(),
            'print'     => print_r($_POST, true)
            ]);
    }

    /**
     * @Route("/graph1", name="graph1")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function graph1Action(Request $request)
    {
        \JpGraph\JpGraph::load();
        \JpGraph\JpGraph::module('line');
        
    $version = AppBundle::getRepository(Version::class)->findOneBy(
                        [
                        'idVersion' => '18AP1529',
                        ]
                    );
    
    $annee = '20' . substr( $version->getIdVersion(), 0, 2 );
    $consommation = AppBundle::getRepository(Consommation::class)->findOneBy(
                        [
                        'projet' => $version->getProjet()->getIdProjet(),
                        'annee' => $annee
                        ]
                    );
   
    $conso[]    =   0;
    $quota[] =  $consommation->getLimite();
    $affichage_max[]    =   $consommation->getLimite() * 1.1;
    if( $consommation != null )
            {
            for ($i = 1; $i <= 12; $i++)
                 {
                    if( $i < 10 )
                        ${"methodName"} = 'getM0'.$i;
                    else
                        ${"methodName"} = 'getM'.$i;
                $c = $consommation->${"methodName"}();
                if( $c > 0 )
                    {
                    $conso[] = $c;
                    }
                $quota[] =  $consommation->getLimite();
                $affichage_max[]    =   $consommation->getLimite() * 1.1;
                }
            }

    $legende = [ '','jan','fév','mar','avr','mai','juin','juil','août','sept','oct','nov','déc' ];

    $graph = new \Graph(800,400);
	$graph->SetScale('textlin');
	$graph->SetMargin(60,60,50,50);
	$graph->SetMarginColor("silver"); 
	$graph->SetFrame(true,'silver'); 
	$graph->title->Set('Consommation du projet ' . $version->getProjet() . " pour l'année " . $annee);
	$graph->xgrid->Show();
	$graph->xaxis->SetTickLabels($legende);
    
    $constante_limite = new \LinePlot($quota);
	$constante_limite->SetColor('#FF0000');
	$constante_limite->SetLegend('Quotas');
    $graph->Add($constante_limite);
    
    $courbe = new \LinePlot($conso);
    $courbe->SetLegend('Consommation');
    $courbe->SetColor('#2E64FE');
    $graph->Add($courbe);

    //aide a l'affichage du graphique : affiche 10% de la conso max en plus
	$aff_limite = new \LinePlot($affichage_max);
	$aff_limite->SetColor('#FFFFFF');
    $graph->Add($aff_limite);
    
    $graph->legend->SetFrameWeight(1);
	$graph->legend->SetLayout(1); // LEGEND_HOR
	$graph->legend->SetPos(0.5,0.98,'center','bottom');
    
    ob_start();
    $graph->Stroke();
    $image_data = ob_get_contents();
    ob_end_clean();

    $image = base64_encode($image_data);

    $twig = new \Twig_Environment( new \Twig_Loader_String(), array( 'strict_variables' => false ) );
    $body = $twig->render( '<img src="data:image/png;base64, {{ EncodedImage }}" />' ,  [ 'EncodedImage' => $image,      ] );

    return new Response($body);
    }

    
     
}
