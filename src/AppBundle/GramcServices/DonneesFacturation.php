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
//use AppBundle\Entity\Compta;

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
	
	public function __construct($dfct_directory='popo')
	{
		$this->dfct_directory = $dfct_directory;
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
		 
}
