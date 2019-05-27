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

/* On recopie en 2018 la conso du 17 Décembre sur le 31 Décembre */
class Compta2018  implements FixtureInterface
{
    public function load(ObjectManager $em)
    {
        $projets = $em->getRepository(Projet::class)->findAll();

        $date17 = new \DateTime('2018-12-17');
        $date31 = new \DateTime('2018-12-31');
        foreach( $projets as $projet )
        {
            $compta31 = $em->getRepository(Compta::class)->consoDateProjet($projet,$date31);
            
            // Si déjà fait on passe
            if ($compta31 != null) continue;
            $compta17 = $em->getRepository(Compta::class)->consoDateProjet($projet,$date17);
            
            // Si pas de compta le 17 on passe
            if ($compta17 == null) continue;
            
            // sinon recopier la compta du 17 le 31 Décembre
            $compta31 = clone $compta17[0];
            $compta31->setDate($date31);

            $em->persist( $compta31 );
            $em->flush();
        }
    }
}
