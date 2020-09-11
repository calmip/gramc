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

namespace AppBundle\Controller;

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

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Component\HttpFoundation\Request;
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

class ProjetDfctController extends Controller
{
    /**
     * Appelé quand on clique sur le bouton € dans la page projets par année
     * Affiche les données de facturation actuelles
     *
     * @Route("/{id}/dfctaff/{annee}", name="dfctaff_projet")
     * @Method({"GET","POST"})
     */
	public function dfctAction(Projet $projet, $annee,  Request $request)
	{
		$versions = $projet -> getVersionsAnnee($annee);
		if (isset ($versions['A']))
		{
			$version = $versions['A'];
		}
		else
		{
			$version = $versions['B'];
		}

		return $this->render('projetfct/affiche.html.twig', ['projet' => $projet, 'version' => $version, 'annee' => $annee]);
	}

}
