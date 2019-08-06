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
							$id_projet = $projet->getIdProjet();
							$type = substr( $id_projet , 0, 1 );
							if ($type=='T')
							{
								$projet->setTypeProjet(Projet::PROJET_TEST);
							}
							else
							{
								$projet->setTypeProjet(Projet::PROJET_SESS);
							}
						}
                        $em->flush();

                }

        }

