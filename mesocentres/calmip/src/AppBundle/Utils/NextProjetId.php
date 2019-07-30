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

namespace AppBundle\Utils;
use AppBundle\AppBundle;
use AppBundle\Entity\Projet;
use AppBundle\Repository\ProjetRepository;


class NextProjetId
{
	/*
	 * Calcule le prochain id de projet, à partir des projets existants
     *
     * Params: $anne   L'année considérée
     *         $type   Le type de projet
     *
     *
     * Return: Le nouvel id, ou null en cas d'erreur
     *
     */
	public static function NextProjetId($annee, $type)
	{
		$numero = AppBundle::getRepository(Projet::class)->getLastNumProjet( $annee, $type );
		if ($numero === null )
		{
			return null;
		}
		else
		{
			// NB - Si la fonction précédente n'a pas renvoyé null, $type est correct pas la peine de retester
			$prefix = AppBundle::getParameter('prj_prefix')[$type];
			return $prefix . $annee . sprintf("%'.03d", $numero+1);
		}
	}
}
