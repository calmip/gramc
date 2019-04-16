<?php

        namespace AppBundle\DataFixtures\ORM;

        use Doctrine\Common\DataFixtures\FixtureInterface;
        use Doctrine\Common\Persistence\ObjectManager;

        use AppBundle\Entity\Projet;
        use AppBundle\Entity\Version;
        use AppBundle\Entity\Expertise;
        use AppBundle\Entity\CollaborateurVersion;

        use AppBundle\Utils\Etat;

       class Projets  implements FixtureInterface
        {
                public function load(ObjectManager $em)
                {
                        $projets = $em->getRepository(Projet::class)->findAll();

                        foreach( $projets as $projet )
                            {
                            $etat = $projet->getEtatProjet();

                            if( $etat == Etat::RENOUVELABLE ||  $etat == Etat::NON_RENOUVELABLE || $etat == Etat::TERMINE )
                                continue;
                            elseif( $etat == Etat::ANNULE )
                                $projet->setEtatProjet( Etat::TERMINE );
                            elseif( $etat == Etat::EN_SURSIS )
                                $projet->setEtatProjet( Etat::NON_RENOUVELABLE );
                            elseif( $projet->isProjetTest() == true )
                                $projet->setEtatProjet( Etat::NON_RENOUVELABLE );
                            else
                                $projet->setEtatProjet( Etat::RENOUVELABLE );
                            }
                        $em->flush();

                }

        }

