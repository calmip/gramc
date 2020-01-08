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
 *  authors : Miloslav Grundmann - C.N.R.S. - UMS 3667 - CALMIP
 *            Emmanuel Courcelle - C.N.R.S. - UMS 3667 - CALMIP
 *            Nicolas Renon - Université Paul Sabatier - CALMIP
 **/

namespace AppBundle\Workflow;

/************************
 * Transition - Implémente une transition d'états
 *              Classe abstraite triviale
 *              canExecute pemet de définir des ACL: suivant la personne connectée la transition peut être exécutée ou pas.
 * 					retourne true/false
 * 				execute essaie d'exécuter la transition:
 * 					retourne true  -> la transition est exécutée
 * 					retourne false -> il y a eu un pb (voir le journal) la transition ne s'est pas faite
 ************************/
interface TransitionInterface
{
	const DEBUG = true;					// Activer - ou pas - le debug dans les transitions
										// ATTENTION ! Mettre à false pour la prod, sinon perte de perfs !
	const FAST  = true;				    // TODO - VIRER TOUTE PROPAGATION DANS canExecute !!!
										// Si FAST est à false, on appelle canExecute pour TOUS les objets
										// Si canExecute sur Session on appelle canExecute pour tous les versions,
										// Les versions appellent canExecute pour tous les projets etc.
										// Cela est lent et pas très utile et pratique
    public function canExecute($object);
    public function execute($object);
    public function __toString();
}
