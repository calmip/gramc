<?php

/**
 * This file is part of GRAMC (Computing Ressource Granting Software)
 * GRAMC stands for : Gestion des Ressources et de leurs Attributions pour Mésocentre de Calcul
 *
 * GRAMC is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 *  GRAMC is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with GRAMC.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  authors : Emmanuel Courcelle - C.N.R.S. - UMS 3667 - CALMIP
 *            Nicolas Renon - Université Paul Sabatier - CALMIP
 **/

namespace AppBundle\GramcServices;

use AppBundle\Entity\Projet;
//use AppBundle\Entity\Version;
//use AppBundle\Entity\Session;
//use AppBundle\Entity\CollaborateurVersion;
//use AppBundle\Entity\Thematique;
//use AppBundle\Entity\Rattachement;
////use AppBundle\Entity\Expertise;
//use AppBundle\Entity\Individu;
//use AppBundle\Entity\Sso;
//use AppBundle\Entity\CompteActivation;
//use AppBundle\Entity\Journal;
use AppBundle\Entity\Compta;

//use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManager;

//use Symfony\Bundle\FrameworkBundle\Controller\Controller;
//use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
//use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
//use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

//use Symfony\Component\HttpFoundation\Request;
//use Symfony\Component\HttpFoundation\Response;
//use Symfony\Component\HttpFoundation\RedirectResponse;

//use AppBundle\AppBundle;
//use AppBundle\Utils\Functions;
//use AppBundle\Utils\Menu;
//use AppBundle\Utils\Etat;
//use AppBundle\Utils\Signal;
//use AppBundle\Workflow\Projet\ProjetWorkflow;
//use AppBundle\Workflow\Version\VersionWorkflow;
//use AppBundle\Utils\GramcDate;

//use AppBundle\GramcGraf\Calcul;
//use AppBundle\GramcGraf\CalculTous;
//use AppBundle\GramcGraf\Stockage;

//use Symfony\Bridge\Doctrine\Form\Type\EntityType;
//use Symfony\Component\Form\Extension\Core\Type\SubmitType;
//use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
//use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * ProjetFctController rassemble les controleurs dédiés au bouton "Euro" (données de facturation)
 *
 * @Security("has_role('ROLE_OBS')")
 * @Route("projet")
 */
 // Tous ces controleurs sont exécutés au moins par OBS, certains par ADMIN seulement
 // et d'autres par DEMANDEUR

class DonneesFacturation 
{
	private $dfct_directory;
	private $em;
	
	public function __construct($dfct_directory='popo',EntityManager $em)
	{
		$this->dfct_directory = $dfct_directory;
		$this->em             = $em;
	}

	/*
	 * Construit le nom de répertoire à partir du projet, et de l'année 
	 */
	private function getDirName(Projet $projet, $annee)
	{
		return $this->dfct_directory . '/' . $annee . '/' . $projet;
	}
	
	/*
	 * Renvoie un tableau trié de chemins correspondant aux pdf de facturation
	 */
	private function getPathes(Projet $projet, $annee)
	{
		$dfct_dir   = $this->getDirName($projet, $annee);
		$dfct_files = [];
		if (is_dir($dfct_dir))
		{
			$dir = dir($dfct_dir);
			if (false !== $dir)
			{
				while (false !== ($entry = $dir->read())) 
				{
					if (is_dir($entry)) continue;
					// On ne garde que les fichiers dfctN.pdb avec N=1..9
					if (preg_match('/^dfct[123456789]\.pdb$/',$entry)===1)
					{
						$dfct_files[] = $entry;
					}
					sort($dfct_files);
				}
			}
		}
		return $dfct_files;
	}
	
	/*
	 * Renvoie un array de numéros qui permettra de reconstruire la liste des données de facturation
	 */ 
	 
	public function getNbEmises(Projet $projet, $annee)
	{
		
		$dfct_files = $this->getPathes($projet, $annee);
		$numeros    = [];
		foreach ($dfct_files as $f)
		{
			$numeros[] = substr($f,4,1);	// dfct1.pdb -> "1"
		}
		return $numeros;
	}
	
	/*
	 * Renvoie le chemin du fichier numéro $nb, ou '' s'il n'existe pas
	 */
	 public function getPath(Projet $projet, $annee, $nb)
	 {
		 $f = $this->getDirName($projet, $annee) . '/dfct'.$nb.'.pdb';
		 if (is_file($f))
		 {
			 return $f;
		 }
		 else
		 {
			 return '';
		 }
	 }
	 
	 /*
	  * Renvoie la consomation du projet entre deux dates
	  * Renvoie -1 s'il y a une incohérence de dates
	  */
	 public function getConsoPeriode(Projet $projet, \Datetime $debut_periode, \DateTime $fin_periode)
	 {
 		// conso en début de période, ie date de début à 0h00
		// Avant le 20 Janvier on considère que la conso vaut 0
		// (la RAZ a eu lieu début Janvier)
		// Conséquences:
		//    - Pas possible de faire des facturations avant le 20 Janvier
		//    - On espère que le compteur a été remis à zéro avant le 20 Janvier...
		//
		
		$annee = $debut_periode->format('Y');
		$repos = $this->em->getRepository(Compta::class);

		if ($debut_periode < new \DateTime($annee . '-01-20'))
		{
			$debut_conso = 0;
		}
		else
		{
			$debut_conso = $repos->consoDateInt($projet,$debut_periode);
		}
		
		// conso en fin de projet, ie date de fin à 23h59
		// On ajoute un jour à la date de fin pour savoir la conso
		$fin_periode_conso = clone $fin_periode;
		$fin_periode_conso->add(new \DateInterval('P1D'));
		$fin_conso = $repos->consoDateInt($projet,$fin_periode_conso);
		//echo ($fin_periode_conso->format('Y-m-d')."###".$fin_conso);
		if ($debut_conso >= $fin_conso)
		{
			$conso_periode = -1;
		}
		else
		{
			$conso_periode = $fin_conso - $debut_conso;
		}
		return $conso_periode;
	 }
	 
	 /*
	  * Renvoie la version qui sera utilisée pour stocker les données de consommation
	  */
	 public function getVersion(Projet $projet, $annee)
	 {
 		//
 		// s'il y a une version A on utilise le champ fctstamp de version A
 		// s'il n'y a qu'une version B on utilise le champ de la version B
 		//

		$versions = $projet -> getVersionsAnnee($annee);
		if (isset ($versions['A']))
		{
			$version = $versions['A'];
		}
		else
		{
			$version = $versions['B'];
		}
		return $version;
	}		 
}
