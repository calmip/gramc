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
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
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

        $sent   =   false;
        $responsables   =   static::getResponsablesFiche($session);

        $form   =  AppBundle::createFormBuilder()
                    ->add('submit', SubmitType::class, ['label' => "Envoyer le message aux responsables"])
                    ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $sent   =   true;
            foreach( $responsables as $item ) {
                $individus[ $item['responsable']->getIdIndividu() ] = $item['responsable'];

                Functions::sendMessage(
                    'notification/mail_to_responsables_fiche-sujet.html.twig',
                    'notification/mail_to_responsables_fiche-contenu.html.twig',
                    [ 'session' => $session, 'projets' => $item['projets'], 'responsable' => $item['responsable'] ],
                     [$item['responsable']]
                     );
            }
        }

        return $this->render('mail/mail_to_responsables_fiche.html.twig',
            [
            'sent'          =>  $sent,
            'responsables'  =>  $responsables,
            'session'       =>  $session,
            'form'          =>  $form->createView(),
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
    private static function getResponsablesFiche(Session $session)
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
                $responsables[$responsable->getIdIndividu()]['responsable']                         =   $responsable;
                $responsables[$responsable->getIdIndividu()]['projets'][$projet->getIdProjet()]     =   $projet;
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
    private static function getResponsables(Session $session)
    {
    $type_session = $session->getLibelleTypeSession();
    if( $type_session =='B' )
        $annee = $session->getAnneeSession();
    else
        $annee = $session->getAnneeSession() - 1;

    $responsables = [];
    $projets = [];
    $all_projets = AppBundle::getRepository(Projet::class)->findAll();
    //Functions::debugMessage( __METHOD__ . ' Projets -> ' . count($all_projets) );
    //Functions::debugMessage( __METHOD__ . ' session -> ' . $session );
    //Functions::debugMessage( __METHOD__ . ' annee -> ' . $annee );

    foreach( $all_projets as $projet )
        {
        if( $projet->isProjetTest() )continue;
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
                    $responsables[$responsable->getIdIndividu()]['responsable']                         =   $responsable;
                    $responsables[$responsable->getIdIndividu()]['projets'][$projet->getIdProjet()]     =   $projet;
                    }
                }
            # Session de type B = On ne s'intéresse qu'aux projets qui ont une forte consommation
            else
                {
                if( $derniereVersion->getSession()->getLibelleTypeSession() == 'B' ) continue;
                $consommation = $derniereVersion->getConsommation();
                if( $consommation != null )
                    $conso = $consommation->conso();
                else
                    $conso = 0;

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
                            $responsables[$responsable->getIdIndividu()]['responsable']                         =   $responsable;
                            $responsables[$responsable->getIdIndividu()]['projets'][$projet->getIdProjet()]     =   $projet;
                            }
                        }
                    }
                else
                    Functions::errorMessage( __METHOD__ . ':'. __LINE__ . " le paramètre conso_seuil_1 manque !");
                }
            }

        }

    return $responsables;
    }


}
