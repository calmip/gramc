<?php

namespace AppBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use AppBundle\Entity\Projet;
use AppBundle\Entity\Version;
use AppBundle\Entity\Rallonge;

use AppBundle\Utils\Etat;

/* Suppression du stamp de mise à jour dans les versions lorsque majInd correspond à un individu qui n'est pas un collaborateur de la version courante ! */
class Versions  implements FixtureInterface
{
	public function load(ObjectManager $em)
	{
		$nb_versions         = 0;
		$nb_versions_modifs  = 0;
		$nb_versions_noindex = 0;
		
		$versions = $em->getRepository(Version::class)->findAll();

		foreach( $versions as $version )
		{
			$nb_versions++;
			if ($version->getMajInd() != null)
			{
				$maj_ind = $version->getMajInd();
				if ( ! $version->isResponsable($maj_ind) )
				{
					$version->setMajInd(null);
					$version->setMajStamp(null);
		            $em->persist( $version );
		            $nb_versions_modifs++;
				}
			}
			else
			{
				$nb_versions_noindex++;
			}
		}
		
		$em->flush();
		
		echo "BILAN:\n";
		echo "======\n\n";
		echo "Versions                       = $nb_versions\n";
		echo "Versions sans maj_ind          = $nb_versions_noindex\n";
		echo "Versions avec maj_ind supprimé = $nb_versions_modifs\n";
		echo "Versions avec maj_ind inchangé = ";
		echo $nb_versions - $nb_versions_noindex - $nb_versions_modifs . "\n";


	}

}
