<?php

namespace AppBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use AppBundle\Entity\Projet;
use AppBundle\Entity\Version;
use AppBundle\Entity\Expertise;
use AppBundle\Entity\CollaborateurVersion;
use AppBundle\Entity\Compta;

use AppBundle\Utils\Etat;

/* On introduit la compta gpfs du 1er Janvier au 20 mai 2019 */
class Comptagpfs2019  implements FixtureInterface
{
    public function load(ObjectManager $em)
    {
        if (($handle = @fopen("comptagpfs2019.csv", "r")) === FALSE) 
        {
            echo "Fichier csv absent\n";
            return;
        }

        $conso_repository = $em->getRepository(Compta::class);
        $chk_data = true;
        $row = 0;
        while ( $ligne = fgetcsv($handle) )
        {
            if (count( $ligne ) < 5) continue; // pour une ligne erronée ou incomplète
            if ($ligne[0]=='Date') continue;   // Sauter les headers
            $date       =   $ligne[0]; // 2019-02-05
            $date       =   new \DateTime( $date . "T00:00:00");
            $loginname  =   $ligne[1]; // login ou nom de groupe
            $ressource  =   $ligne[2]; // work_space, work_inode
            $type   =   $ligne[3]; // user, group
            if ($type=="user") {
                $type_nb = Compta::USER;
            } else if ($type=="group") {
                $type_nb = Compta::GROUP;
            } else {
                echo "type de ressource inconu\n";
                return;
            }
            $conso = $ligne[4]; // consommation
            if( array_key_exists( 5, $ligne ) )
                $quota  =   $ligne[5]; // quota
            else
                $quota  =   -1;
            
            // $chk_data: On recherche la donnée actuelle et si on la trouve laisse tomber
            if ($chk_data)
            {
                $compta =  $conso_repository->findOneBy( [ 'date' => $date, 'loginname' =>  $loginname,  'ressource' => $ressource, 'type' => $type_nb ] );
                if ($compta != null)
                {
                    echo "comptagpfs2019 = Les DONNEES SONT DEJA DANS LA BASE !!!\n";
                    return;
                }
                $chk_data = false;
            }
            
            // Entrée des données
            $compta = new Compta();
            $compta->setDate( $date );
            $compta->setLoginname( $loginname );
            $compta->setRessource( $ressource );
            $compta->setType( $type_nb );
            $compta->setConso( $conso );
            $compta->setQuota( $quota );
            $em->persist( $compta );
            $row++;
            if ( $row % 1000 === 0)
            {
                echo "Ligne numéro: $row\n";
                try 
                {
                    $em->flush();
                    $em->clear(Compta::class);
                }   
                catch (\Exception $e)
                {
                    echo "Erreur en écrivant les données\n";
                }
            }
        }
                    
        try 
        {
            $em->flush();
        }   
        catch (\Exception $e)
        {
            echo "Erreur en écrivant les données\n";
        }
    }
}
