<?php

        namespace AppBundle\DataFixtures\ORM;

        use Doctrine\Common\DataFixtures\FixtureInterface;
        use Doctrine\Common\Persistence\ObjectManager;

        use AppBundle\Entity\Projet;
        use AppBundle\Entity\Version;
        use AppBundle\Entity\Expertise;
        use AppBundle\Entity\CollaborateurVersion;

        use AppBundle\Utils\Etat;

	// Cette fixture ne fait rien
	// Elle ne sert qu'à éviter à bin/console doctrine:fixtures:load de renvoyer une erreur !
        class None  implements FixtureInterface
        {
            public function load(ObjectManager $em) {}
        }

