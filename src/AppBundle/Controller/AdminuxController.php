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

use AppBundle\Entity\Consommation;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

use AppBundle\AppBundle;
use AppBundle\Utils\Functions;
use AppBundle\Utils\GramcDate;
use AppBundle\Utils\Etat;

use AppBundle\Entity\Projet;
use AppBundle\Entity\Version;
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

        try {
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
     * Met à jour la consommation de tous les projets à partir d'un unique fichier csv
     *
     * @Route("/conso_update_batch", name="consommation_update_batch")
     * @Method({"PUT"})
     */
     public function updateConsoBatchAction(Request $request)
     {
        $em = $this->getDoctrine()->getManager();
        $conso_repository = $em->getRepository('AppBundle:Consommation');
        $conso_lignes = 0;

        $putdata = fopen("php://input", "r");
        while ($ligne=fgetcsv($putdata)) {
            // Affreuse bidouille on met la date sur la première ligne à la place d'un header dont on ne se sert pas !
            if ($ligne[0] == 'Date')
            {
                $dat = $ligne[1];
                $annee = substr($dat,0,4);
                $mois = substr($dat,4,2);
                $day = substr($dat,6,2);
            }

            // Il doit y avoir 7 colonnes dans le fichier csv sinon on saute la ligne
            // Ligne mal formattée, ou fin de fichier
            if (count($ligne)!=7)
            {
                continue;
            }
            else
            {
                $id_projet = strtoupper($ligne[0]);
                $id_projet = preg_replace('/T20([01])([0-9])([0-9][0-9])/','T${1}${2}0${3}',$id_projet); // T201604 ==> T16004
                $conso     = $ligne[1];
                $limite    = $ligne[2];

                // Contrainte d'unicité: on fait soit un update soit un insert !
                $consommation = $conso_repository->findOneBy( [ 'projet' => $id_projet, 'annee' => $annee ] );
                if ($consommation == null)
                {
                    $consommation = new Consommation();
                    $consommation->setAnnee($annee);
                    $consommation->setProjet($id_projet);
                    $consommation->setM01(0);
                    $consommation->setM02(0);
                    $consommation->setM03(0);
                    $consommation->setM04(0);
                    $consommation->setM05(0);
                    $consommation->setM06(0);
                    $consommation->setM07(0);
                    $consommation->setM08(0);
                    $consommation->setM09(0);
                    $consommation->setM10(0);
                    $consommation->setM11(0);
                    $consommation->setM12(0);
                }
                $consommation->setLimite($limite);
                $m = intval($mois);
                switch ($m) {
                case 1:
                    $consommation->setM01(intval($conso));
                    break;
                case 2:
                    $consommation->setM02(intval($conso));
                    break;
                case 3:
                    $consommation->setM03(intval($conso));
                    break;
                case 4:
                    $consommation->setM04(intval($conso));
                    break;
                case 5:
                    $consommation->setM05(intval($conso));
                    break;
                case 6:
                    $consommation->setM06(intval($conso));
                    break;
                case 7:
                    $consommation->setM07(intval($conso));
                    break;
                case 8:
                    $consommation->setM08(intval($conso));
                    break;
                case 9:
                    $consommation->setM09(intval($conso));
                    break;
                case 10:
                    $consommation->setM10(intval($conso));
                    break;
                case 11:
                    $consommation->setM11(intval($conso));
                    break;
                case 12:
                    $consommation->setM12(intval($conso));
                    break;
                }

                $em->persist($consommation);
                $em->flush();

                $conso_lignes += 1;
            }
        }
        if ($conso_lignes>0)
        {
            Functions::infoMessage("Fichier de consommation téléversé - $conso_lignes lignes ajoutées");
        }

        return $this->render('consommation/conso_update_batch.html.twig');
    }

   /**
     * set loginname
     *
     * @Route("/setloginname/{idProjet}/projet/{idIndividu}/individu/{loginname}/loginname", name="set_loginname")
     * @Method({"GET"})
     */
    public function setloginnameAction(Request $request, $idProjet, $idIndividu, $loginname)
     {
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
      return new Response(json_encode( ['KO' => 'No user found' ]));
     }

    /**
     * set loginname
     *
     * @Route("/getloginnames/{idProjet}/projet", name="get_loginnames")
     * @Method({"GET"})
     */
   public function getloginnamesAction($idProjet)
   {
   $projet      = AppBundle::getRepository(Projet::class)->find($idProjet);
   if( $projet == null )
        return new Response( json_encode( ['KO' => 'No Projet ' . $idProjet ]) );


   $versions    = $projet->getVersion();
   $output      =   [];
   $idProjet    =   $projet->getIdProjet();

   foreach( $versions as $version )
        if( $version->getEtatVersion() == Etat::ACTIF)
             foreach( $version->getCollaborateurVersion() as $collaborateurVersion )
                {
                if( $collaborateurVersion->getLogin() == false )
                    continue;

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
        $annee_courante=GramcDate::get()->showYear();
        $projets = Functions::projetsParAnnee($annee_courante)[0];

        // projets à problème
        $msg = "";
        foreach ($projets as $p)
        {
            if ($p['attrib'] != $p['q']) {
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
