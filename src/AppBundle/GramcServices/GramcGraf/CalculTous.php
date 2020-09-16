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

use AppBundle\Utils\Functions;


class CalculTous extends Calcul
{
    /* Génère les données "StructuredData" qui seront utilisées par dessineConsoHeures
	 * afin de faire le graphique de consommation des heures cpu+gpu pour TOUS les projets
	 * Cette fonction est différente de celle qui se trouve dans Calcul car la requête SQL est différente et donc
	 * $db_data est un tableau et tableaux et plus un tableau d'objets !
	 *
	 * $debut, $fin = dates de début et fin
	 * $db_data     = Le retour de la requête sql sur la table consommation
     *
	 * Retourne stuctured_data, c-a-d un array:
	 *     key = timestamp
	 *     val = [ 'cpu' => $conso_cpu, 'gpu' => $conso_gpu, 'quota' => $quota ]
	 * Les champs 'cpu','gpu',quota' sont TOUS optionnels
     */
	public function createStructuredData(\DateTime $date_debut, \DateTime $date_fin, $db_data)
	{
        $structured_data = [];
        $debut = $date_debut->getTimestamp();
        $fin   = $date_fin->getTimestamp();

        // Si pas de données (1er Janvier) on les crée artificiellement
        if (count($db_data) === 0)
        {
            $structured_data[$debut]['quota'] = 1;
            $structured_data[$fin]['quota'] = 1;
        }
        else
        {
            foreach( $db_data as $item )
            {
                $key = $item['date']->getTimestamp();
                if( $key < $debut || $key > $fin ) continue;

                if ( array_key_exists ( $key , $structured_data ) )
                {
                    $structured_data[$key][$item['ressource']] = $item['conso'];
                }
                else
                {
                    $data = [$item['ressource'] => $item['conso']];
                    $structured_data[$key] = $data;
                }
            }
        }

        // Remplissage des trous gpu et cpu et calcul des valeurs normalisees
        foreach( $structured_data as $key => $item )
        {
            if ( ! array_key_exists ( 'gpu' ,   $item ) ) $structured_data[$key]['gpu'] = 0;
            if ( ! array_key_exists ( 'cpu' ,   $item ) ) $structured_data[$key]['cpu'] = 0;
            if ( ! array_key_exists ( 'quota' , $item ) ) $structured_data[$key]['quota'] = 0;
            $structured_data[$key]['norm'] = $structured_data[$key]['cpu'] + $structured_data[$key]['gpu'];
        }

        return $structured_data;
	}
}
