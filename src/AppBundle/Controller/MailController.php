<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Storage\PhpBridgeSessionStorage;
use Symfony\Component\HttpFoundation\RedirectResponse;

use AppBundle\Form\IndividuType;
use AppBundle\Entity\Individu;
use AppBundle\Entity\Scalar;
use AppBundle\Entity\Sso;
use AppBundle\Entity\Compteactivation;
use AppBundle\Entity\Journal;
use AppBundle\Entity\Projet;

use AppBundle\Entity\Version;
use AppBundle\Entity\Session;

use AppBundle\AppBundle;
use AppBundle\Utils\Functions;
use AppBundle\Utils\Menu;
use AppBundle\Utils\Etat;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;



/////////////////////////////////////////////////////

/**
 * Mail controller.
 *
 * @Route("mail")
 * @Security("has_role('ROLE_ADMIN')")
 */
class MailController extends Controller
{

    /**
     * @Route("/{id}/mail_to_responsables_fiche",name="mail_to_responsables_fiche")
     * @Security("has_role('ROLE_ADMIN') or has_role('ROLE_PRESIDENT')")
     * @Method({"GET", "POST"})
    **/

    public function mailToResponsablesFicheAction(Request $request, Session $session)
    {

        $em = $this->getDoctrine()->getManager();

		$nb_msg = 0;
        $sujet   = \file_get_contents(__DIR__."/../../../app/Resources/views/notification/mail_to_responsables_fiche-sujet.html.twig");
        $body    = \file_get_contents(__DIR__."/../../../app/Resources/views/notification/mail_to_responsables_fiche-contenu.html.twig");
        $sent    =   false;
        $responsables   =   $this->getResponsablesFiche($session);

        $form   =  AppBundle::createFormBuilder()
                    ->add('texte', TextareaType::class, [
						'label' => " ",
						'data' => $body,
						'attr' => ['rows'=>10,'cols'=>150]])
                    ->add('submit', SubmitType::class, ['label' => "Envoyer le message"])
                    ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $sent   = true;
            $body   = $form->getData()['texte'];

            foreach( $responsables as $item ) {
                $individus[ $item['responsable']->getIdIndividu() ] = $item['responsable'];
                $selform = $this->getSelForm($item['responsable']);
				$selform->handleRequest($request);
				if ($selform->getData()['sel']==false)
				{
		            //Functions::debugMessage( __METHOD__ . $version->getIdVersion().' selection NON');
		            continue;
				}
                Functions::sendMessageFromString(
					$sujet,
					$body,
                    [ 'session' => $session, 'projets' => $item['projets'], 'responsable' => $item['responsable'] ],
                     [$item['responsable']]
                     );
                 $nb_msg++;
                 // DEBUG = Envoi d'un seul message
				 // break;
            }
        }

        return $this->render('mail/mail_to_responsables_fiche.html.twig',
            [
            'sent'         => $sent,
            'nb_msg'       => $nb_msg,
            'responsables' => $responsables,
            'session'      => $session,
            'form'         => $form->createView(),
            ]
		);
    }

    ////////////////////////////////////////////////////////////////////////
    /***********************************************************
     *
     * Renvoie la liste des responsables de projet (et des projets) qui n'ont pas (encore)
     * téléversé leur fiche projet signée pour la session $session
     *
     ************************************************************/
    private function getResponsablesFiche(Session $session)
    {
        $responsables = [];
        $all_versions = AppBundle::getRepository(Version::class)->findBy(['session' => $session, 'prjFicheVal' => false] );

        foreach( $all_versions as $version )
        {
            $projet = $version->getProjet();
            if( $projet == null)
            {
                Functions::errorMessage( __METHOD__ . ':'. __LINE__ . " version " . $version . " n'a pas de projet !");
                continue;
            }

            if( $version->getEtatVersion() != Etat::ACTIF && $version->getEtatVersion() != Etat::EN_ATTENTE) continue;

            $responsable    =  $version->getResponsable();

            if( $responsable != null )
            {
				$responsables[$responsable->getIdIndividu()]['selform']                         = $this->getSelForm($responsable)->createView();
                $responsables[$responsable->getIdIndividu()]['responsable']                     = $responsable;
                $responsables[$responsable->getIdIndividu()]['projets'][$projet->getIdProjet()] = $projet;
            }
            else
                Functions::errorMessage( __METHOD__ . ':'. __LINE__ . " version " . $version . " n'a pas de responsable !");
        }

        return $responsables;
    }

    /**
     * @Route("/{id}/mail_to_responsables",name="mail_to_responsables")
     * @Security("has_role('ROLE_ADMIN') or has_role('ROLE_PRESIDENT')")
     * @Method({"GET", "POST"})
    **/
    public function mailToResponsablesAction(Request $request, Session $session)
    {

        $em = $this->getDoctrine()->getManager();

		$nb_msg = 0;
        $sujet   = \file_get_contents(__DIR__."/../../../app/Resources/views/notification/mail_to_responsables-sujet.html.twig");
        $body    = \file_get_contents(__DIR__."/../../../app/Resources/views/notification/mail_to_responsables-contenu.html.twig");
        $sent    =   false;
        $responsables   =   $this->getResponsables($session);

        $form   =  AppBundle::createFormBuilder()
                    ->add('texte', TextareaType::class, [
						'label' => " ",
						'data' => $body,
						'attr' => ['rows'=>10,'cols'=>150]])
                    ->add('submit', SubmitType::class, ['label' => "Envoyer le message"])
                    ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $sent   = true;
            $body   = $form->getData()['texte'];

            foreach( $responsables as $item ) {
                $individus[ $item['responsable']->getIdIndividu() ] = $item['responsable'];
                $selform = $this->getSelForm($item['responsable']);
				$selform->handleRequest($request);
				if ($selform->getData()['sel']==false)
				{
		            //Functions::debugMessage( __METHOD__ . $version->getIdVersion().' selection NON');
		            continue;
				}
                Functions::sendMessageFromString(
					$sujet,
					$body,
                    [ 'session' => $session, 'projets' => $item['projets'], 'responsable' => $item['responsable'] ],
                     [$item['responsable']]
                     );
                 $nb_msg++;
                 // DEBUG = Envoi d'un seul message
				 // break;
            }
        }

        return $this->render('mail/mail_to_responsables.html.twig',
            [
            'sent'         => $sent,
            'nb_msg'       => $nb_msg,
            'responsables' => $responsables,
            'session'      => $session,
            'form'         => $form->createView(),
            ]
		);
    }
    public function mailToResponsablesAction_SUPPR(Request $request, Session $session)
    {

        $sent           = false;
        $responsables   = static::getResponsables($session);
        //return new Response(Functions::show($responsables));

        $form   =  AppBundle::createFormBuilder()
                    ->add('submit', SubmitType::class, ['label' => "ENVOYER LE MESSAGE"])
                    ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $sent   =   true;

            // On envoie un mail à chaque responsable concerné
            $i=0;
            foreach( $responsables as $item )
            {
                //$individus[ $item['responsable']->getIdIndividu() ] = $item['responsable'];
                Functions::sendMessage(
                    'notification/mail_to_responsables-sujet.html.twig',
                    'notification/mail_to_responsables-contenu.html.twig',
                    [ 'session' => $session, 'projets' => $item['projets'], 'responsable' => $item['responsable'] ],
                     [$item['responsable']]
                     );

                $i++;
               // if ($i>2)
               //     break;
            }
        }

    return $this->render('mail/mail_to_responsables.html.twig',
        [
        'sent'          =>  $sent,
        'responsables'  =>  $responsables,
        'session'       =>  $session,
        'form'          =>  $form->createView(),
        ]
        );
    }


    /***********************************************************
     *
     * Renvoie la liste des responsables de projet (et des projets) qui n'ont pas (encore)
     * renouvelé leur projet pour la session $session
     *
     ************************************************************/
    private function getResponsables(Session $session)
    {
	    $type_session = $session->getLibelleTypeSession();
	    if( $type_session =='B' )
	        $annee = $session->getAnneeSession();
	    else
	        $annee = $session->getAnneeSession() - 1;
	
	    $responsables = [];
	    $projets = [];
	    $all_projets = AppBundle::getRepository(Projet::class)->findAll();

	    foreach( $all_projets as $projet )
        {
	        if( $projet->isProjetTest() ) continue;
	        if( $projet->getEtatProjet() == Etat::TERMINE ||  $projet->getEtatProjet() == Etat::ANNULE ) continue;
	        $derniereVersion    =  $projet->derniereVersion();
	        if( $derniereVersion != null
            && $derniereVersion->getSession() != null
            && $derniereVersion->getSession()->getAnneeSession() == $annee
            &&  ( $derniereVersion->getEtatVersion() == Etat::ACTIF || $derniereVersion->getEtatVersion() == Etat::TERMINE )
            )
            {
	            if(  $type_session == 'A' )
				{
	                $responsable    =  $derniereVersion->getResponsable();
	                if( $responsable != null )
					{
						$ind = $responsable->getIdIndividu();
						$responsables[$ind]['selform']                         = $this->getSelForm($responsable)->createView();
	                    $responsables[$ind]['responsable']                     = $responsable;
	                    $responsables[$ind]['projets'][$projet->getIdProjet()] = $projet;
	                    if ( !isset($responsables[$ind]['max_attr'])) $responsables[$ind]['max_attr'] = 0;
	                    $attr = $projet->getVersionActive()->getAttrHeures();
	                    if ($attr>$responsables[$ind]['max_attr']) $responsables[$ind]['max_attr']=$attr;
	                }
				}

	            # Session de type B = On ne s'intéresse qu'aux projets qui ont une forte consommation
	            else
                {
	                if( $derniereVersion->getSession()->getLibelleTypeSession() == 'B' ) continue;
	                $conso = $derniereVersion->getConsoCalcul();

	                if( $derniereVersion->getAttrHeures() > 0 )
	                    $rapport = $conso / $derniereVersion->getAttrHeures() * 100;
	                else
	                    $rapport = 0;

	                if( AppBundle::hasParameter('conso_seuil_1') )
                    {
	                    if( $rapport > AppBundle::getParameter('conso_seuil_1') )
                        {
	                        $responsable    =  $derniereVersion->getResponsable();
	                        if( $responsable != null )
                            {
								$ind = $responsable->getIdIndividu();
								$responsables[$ind]['selform']                         = $this->getSelForm($responsable)->createView();
			                    $responsables[$ind]['responsable']                     = $responsable;
			                    $responsables[$ind]['projets'][$projet->getIdProjet()] = $projet;
			                    if ( !isset($responsables[$ind]['max_attr'])) $responsables[$ind]['max_attr'] = 0;
			                    $attr = $projet->getVersionActive()->getAttrHeures();
			                    if ($attr>$responsables[$ind]['max_attr']) $responsables[$ind]['max_attr'] = $attr;
                            }
                        }
                    }
	                else
	                    Functions::errorMessage( __METHOD__ . ':'. __LINE__ . " le paramètre conso_seuil_1 manque !");
                }
            }
        }
        
        // On trie $responsables en commençant par les responsables qui on le plus d'heures attribuées !
        usort($responsables,"self::compAttr");
	    return $responsables;
    }
    
    // Pour le tri des responsables en commençant par celui qui a la plus grosse (attribution)
    private static function compAttr($a,$b)
    {
		if ($a['max_attr']==$b['max_attr']) return 0;
	    return ($a['max_attr'] > $b['max_attr']) ? -1 : 1;
	}

	/***
	 * Renvoie un formulaire avec une case à cocher, rien d'autre
	 *
	 *   params  $individu
	 *   return  une form
	 *
	 */
	private function getSelForm(Individu $individu)
	{
		$nom = 'selection_'.$individu->getId();
		return $this->get( 'form.factory')  -> createNamedBuilder($nom, FormType::class, null, ['csrf_protection' => false ])
										    -> add('sel',CheckboxType::class, [ 'required' =>  false, 'label' => " " ])
										    ->getForm();
	}
}
