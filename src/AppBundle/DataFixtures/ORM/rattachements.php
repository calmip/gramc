<?php

namespace AppBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use AppBundle\Entity\Version;
use AppBundle\Entity\Rattachement;
use AppBundle\Entity\Thematique;
use AppBundle\Entity\MetaThematique;
use AppBundle\Entity\Individu;

/* ANITI et ONERA ne sont plus des thématiques, ce sont des "rattachements" */
class Rattachements  implements FixtureInterface
{
	public function load(ObjectManager $em)
	{
		// Remplir la table rattachements
		$ratt = new Rattachement();
		$ratt->setLibelleRattachement('ACADEMIQUE');
		$em->persist( $ratt );
		
		$ratt = new Rattachement();
		$ratt->setLibelleRattachement('ANITI');
		$em->persist( $ratt );

		$ratt = new Rattachement();
		$ratt->setLibelleRattachement('ONERA');
		$em->persist( $ratt );
		
		$em->flush();
		
		echo "Table Rattachement remplie\n";

		// Modifier la tables versions
		// Les versions de thématique ANITI et ONERA sont modifiées comme suit:
		// ANITI:
		//    - Thématique   => 7 (Informatique, automatique)
		//    - Rattachement => 1 (ANITI)
		// ONERA:
		//    - labo         => 68(ONERA)
		//    - Rattachement => 2 (ONERA)
		// AUTRES:
		//    - Rattachement => 0 (Académique)
		
		$themaAniti = $em->getRepository(Thematique::class)  ->findOneBy( ['idThematique' => 20] );
		$themaInfo  = $em->getRepository(Thematique::class)  ->findOneBy( ['idThematique' => 7] );

		$rattAniti  = $em->getRepository(rattachement::class)->findOneby( ['idRattachement' => 2] );
		$rattOnera  = $em->getRepository(rattachement::class)->findOneby( ['idRattachement' => 3] );
		$rattAcad   = $em->getRepository(rattachement::class)->findOneby( ['idRattachement' => 1] );
		
		$versions   = $em->getRepository(Version::class)     ->findAll();
		$nb_versions= 0;
		$nb_aniti   = 0;
		$nb_onera   = 0;
		$nb_acad    = 0;
		foreach( $versions as $version )
		{
			if ($version->getPrjThematique() == $themaAniti)
			{
				$version->setPrjThematique($themaInfo);
				$version->setPrjRattachement($rattAniti);
				$nb_aniti += 1;
			}
				
			elseif (strpos($version->getPrjLLabo(),'ONERA')!==false)
			{
				$version->setPrjRattachement($rattOnera);
				$nb_onera += 1;
			}
			
			else
			{
				$version->setPrjRattachement($rattAcad);	
				$nb_acad += 1;
			}
			$em->persist($version);
			
			$nb_versions++;
			if ($nb_versions % 100 == 0)
			{
				$em->flush();
			}
		}
		
		$em->flush();
		
		echo "Modification de la table Version effectuée";
		echo "BILAN:\n";
		echo "======\n\n";
		echo "Versions traitées              = $nb_versions\n";
		echo "Versions ANITI traitées        = $nb_aniti\n";
		echo "Versions ONERA traitées        = $nb_onera\n";
		echo "Versions ACADEMIQUES traitées  = $nb_acad\n";
		if ($nb_versions != $nb_aniti+$nb_onera+$nb_acad)
		{
			echo "+++++++++++++ ATTENTION ++++++++++++ PAS COHERENT\n";
		}
		
		// Modifier l'affectation de deux experts (hl + jle)	
		$hl = $em->getRepository(Individu::class) -> findOneBy( [ 'idIndividu' => 1317 ] );
		$jle= $em->getRepository(Individu::class) -> findOneBy( [ 'idIndividu' => 511 ] );

		$themaAniti->removeExpert($hl);
		$em->persist($themaAniti);
		$rattAniti->addExpert($hl);
		$em->persist($rattAniti);
		
		$themaOnera = $em->getRepository(Thematique::class)->findOneBy( ['idThematique' => 21] );
		$themaOnera->removeExpert($jle);
		$em->persist($themaOnera);
		$rattOnera->addExpert($jle);
		$em->persist($rattOnera);
		$em->flush();
		echo "Modification des affectations de HL et JLE OK\n";
		
		// Supprimer les thématiques ONERA et ANITI
		$em->remove($themaAniti);
		$em->remove($themaOnera);
		$em->flush();
		echo "Suppression de ANITI et ONERA de la table Thematiques OK\n";		

		// Supprimer les métathématiques ONERA et ANITI
		$metathemaAniti = $em->getRepository(MetaThematique::class)->findOneBy( ['idMetaThematique' => 10] );
		$em->remove($metathemaAniti);
		$metathemaOnera = $em->getRepository(MetaThematique::class)->findOneBy( ['idMetaThematique' => 11] );
		$em->remove($metathemaOnera);
		$em->flush();
		echo "Suppression de ANITI et ONERA de la table MetaThematiques OK\n";		
		
	}
}
