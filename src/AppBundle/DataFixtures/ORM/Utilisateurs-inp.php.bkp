<?php

        namespace AppBundle\DataFixtures\ORM;

        use Doctrine\Common\DataFixtures\FixtureInterface;
        use Doctrine\Common\Persistence\ObjectManager;

        use AppBundle\Entity\Sso;
        use AppBundle\Entity\Version;
        use AppBundle\Entity\Expertise;
        use AppBundle\Entity\CollaborateurVersion;

        use AppBundle\Utils\Etat;

		/*
		 * Cette fixture change les EPPN des utilisateurs @inp-toulouse.fr en @toulouse-inp.fr
		 * afin de suivre le changement de configuration à l'inp de Toulouse intervenu en Juin 2019
		 *
		 */
       class EPPN  implements FixtureInterface
        {
                public function load(ObjectManager $em)
                {
					// Suppression des eppn en toulouse-inp.fr (il y en a quelques-uns) car sinon on aura des erreurs sql
					$ssos = $em->getRepository(Sso::class)->findAll();
					foreach ( $ssos as $sso )
					{
						$eppn = $sso->getEppn();
						$a_eppn = explode('@',$eppn);
						if ($a_eppn[1] == 'toulouse-inp.fr')
						{
							$em->remove($sso);
							echo "Suppression de $eppn\n";
						}
					}
					$em->flush();

					// remplacement de inp-toulouse.fr par toulouse-inp.fr
					$ssos = $em->getRepository(Sso::class)->findAll();
					foreach( $ssos as $sso )
					{
						$eppn = $sso->getEppn();
						$a_eppn = explode('@',$eppn);
						if ($a_eppn[1] == 'inp-toulouse.fr')
						{
							$sso->setEppn($a_eppn[0].'@toulouse-inp.fr');
							$em->persist($sso);
							echo "mise à jour de $eppn\n";
						}
					}
					$em->flush();
				}
		}
