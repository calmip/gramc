<?php

namespace AppBundle\Repository;
use AppBundle\Entity\Projet;
use AppBundle\Entity\Compta;
use AppBundle\AppBundle;

/**
 * ComptaRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ComptaRepository extends \Doctrine\ORM\EntityRepository
{

    public function conso(Projet $projet, $annee)
    {
        $debut = new \DateTime( $annee . '-01-01');
        $fin   = new \DateTime( $annee . '-12-31');
        
        $db_data = AppBundle::getManager()->createQuery(
            'SELECT c
            FROM AppBundle:Compta c
            WHERE c.loginname = :loginname
            AND c.date >= :debut
            AND c.date <= :fin
            ORDER BY c.date ASC'
        )
        ->setParameter('loginname', lcfirst($projet->getIdProjet() ) )
        ->setParameter('debut',$debut)
        ->setParameter('fin',$fin)
        ->getResult();            

        if( $db_data == null || empty( $db_data ) ) return null;
                        
        return $db_data;
    }
    
}
