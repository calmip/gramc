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

namespace AppBundle\GramcServices\GramcGraf;


abstract class GramcGraf
{
	protected $ressources_conso_group;
	protected $ressources_conso_user;
	
	public function __construct($ressources_conso_group,$ressources_conso_user)
	{
		$this->ressources_conso_group = $ressources_conso_group;
		$this->ressources_conso_user  = $ressources_conso_user;
	}

    /*
     * Le code utilisateur:
     *   - Définit les timestamps de début et de fin
     *   - Fait la requête dans la base de données, le résultat est dans $db_data
     *   - Appelle createStructuredData
     *   - Peut éventuellement travailler sur les structured_data (RAZ en début d'année pour gramc par exemple)
     *   - Appelle createImage
     *
     */
    abstract public function createStructuredData(\DateTime $debut,\Datetime $fin,$db_data);
    abstract public function createImage($structured_data, $ressource);

}
