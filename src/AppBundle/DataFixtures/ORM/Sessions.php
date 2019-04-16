<?php

    /* Fixture TEMPORAIRE - Utilisee pour l'instant (Janvier 2019) pour mettre le nombre d'heures correct
     *                      dans la table Sessions
     *****/
     
    namespace AppBundle\DataFixtures\ORM;

    use Doctrine\Common\DataFixtures\FixtureInterface;
    use Doctrine\Common\Persistence\ObjectManager;

    use AppBundle\Entity\Session;

   class Sessions implements FixtureInterface
   {
        public function load(ObjectManager $em)
        {
            $sessions = $em->getRepository(Session::class)->findAll();

            foreach( $sessions as $sess )
            {
                $annee = intval($sess->getAnneeSession());
                if ($annee <=18 && $annee >= 15) {
                    // Eos !!!!
                    $sess->setHParAnnee(86675000);
                }
                else if ( $annee>=19) {
                    // Olympe !!
                    $sess->setHParAnnee(105200000);
                };
                // Sinon je ne sais pas le compte (avant gramc, avant Eos)
            }
            $em->flush();  
        }
    }

       

