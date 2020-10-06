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

class Calcul extends GramcGraf
{
    /* Génère les données "StructuredData" qui seront utilisées par dessineConsoHeures
	 * afin de faire le graphique de consommation des heures cpu+gpu pour un projet
	 *
	 * TODO - les noms de ressources 'cpu' et 'gpu' sont hardcodés
	 *        Il faudrait utiliser la valeur du champ 'ress' de la variable protégée
	 *        ressources_conso_group['1']['ress'] -> 'cpu,gpu'
	 *        (code à généraliser !)
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

        // Si pas de données (nouveau projet par ex) on les crée artificiellement
        if (count($db_data) === 0)
        {
            $structured_data[$debut]['quota'] = 1;
            $structured_data[$fin]['quota'] = 1;
        }
        else
        {
            foreach( $db_data as $item )
            {
                $key = $item->getDate()->getTimestamp();
                if( $key < $debut || $key > $fin ) continue;

                if ( array_key_exists ( $key , $structured_data ) )
                {
                    $structured_data[$key][$item->getRessource()] = $item->getConso();
					$quota1 = $structured_data[$key]['quota'];
					$quota2 = $item->getQuota();
					if( $quota1 != $quota2 )
					{
						Functions::errorMessage( __METHOD__ . ':' . __LINE__ . ' incohérence dans les quotas, date = ' .  $item->getDate()->format("d F Y") . "$quota1=$quota1 quota2=$quota2");
					}
                }
                else
                {
                    $data = [$item->getRessource() => $item->getConso(), 'quota' => $item->getQuota()];
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

   /* Affichage du graphique de la conso horaire d'un projet ou de la totale
    *
    * params = $structures_data (retour de createStructuredData)
    *          $ressource (inutilisé)
    *
    * return = Un tableau de deux éléments:
    *             - L'image en base64
    *             - La taille de l'image produite (en octets)
    */
    public function createImage($structured_data, $ressource=null)
    {
        // Test s'il y a cpu ou gpu
        $no_cpu   = true;
        $no_gpu   = true;
        $no_quota = true;

        if (array_sum(array_column($structured_data,'cpu'))  >0) $no_cpu   = false;
        if (array_sum(array_column($structured_data,'gpu'))  >0) $no_gpu   = false;
        if (array_sum(array_column($structured_data,'quota'))>0) $no_quota = false;

        // création des tables
        $cpu = [];
        $gpu = [];
        $xdata = [];
        $quota = [];
        $norm = [];

		$quotamax = 0;
        foreach( $structured_data as $key => $item )
        {
            $xdata[]    =   $key;
            $cpu[]      =   $structured_data[$key]['cpu'];
            $gpu[]      =   $structured_data[$key]['gpu'];
            $norm[]    =   $structured_data[$key]['norm'];
            $quota[]    =   $structured_data[$key]['quota'];
            $first = false;
            if ($structured_data[$key]['quota']>$quotamax) $quotamax = $structured_data[$key]['quota'];
        }

        \JpGraph\JpGraph::load();
        \JpGraph\JpGraph::module('line');
        \JpGraph\JpGraph::module('date');


        // Create the new graph
        $graph = new \Graph(640,400);

        //$graph = new \Graph(600,400);
        // Slightly larger than normal margins at the bottom to have room for
        // the x-axis labels and at left to have room for y-axis labels
        $graph->SetMargin(100,40,40,170);

        // Fix the Y-scale to go between [0,$quotamax] and use date for the x-axis
        $graph->SetScale('datlin',0,1.05*$quotamax);
        //$graph->SetScale('datlin');
        $graph->xaxis->scale->SetDateFormat("d-m-y");

        $graph->SetTickDensity( \TICKD_SPARSE, \TICKD_SPARSE );
        //$graph->xaxis->scale->AdjustForDST(false);
        $graph->xaxis->scale->SetDateAlign(\DAYADJ_1);
        //$graph->xaxis->scale->ticks->Set(8,2);

        // Set the angle for the labels to 90 degrees
        $graph->xaxis->SetLabelAngle(90);

		// The Y scale - between 0 and 1.1*$quotamax
//		$graph->SetYScale('1','lin','0','60600');


        if( $no_cpu == false )
        {
            $line = new \LinePlot($cpu,$xdata);
            $line->SetLegend('CPU');
            //$line->SetFillColor('lightblue@0.5');
            $line->SetColor("green");
            $line->SetWeight(2);
            $graph->Add($line);
        }

        if( $no_gpu == false )
        {
            $line = new \LinePlot($gpu,$xdata);
            $line->SetLegend('GPU');
            //$line->SetFillColor('lightblue@0.5');
            $line->SetColor("blue");
            $graph->Add($line);
        }

        if( $no_gpu == false && $no_cpu  == false )
        {
            $line = new \LinePlot($norm,$xdata);
            $line->SetLegend('GPU + CPU');
            //$line->SetFillColor('lightblue@0.5');
            $line->SetColor("black");
            $graph->Add($line);
            $line->SetWeight(3);

        }

        if( $no_quota == false )
        {
            $line = new \LinePlot($quota,$xdata);
            $line->SetLegend('Quota');
            //$line->SetFillColor('lightblue@0.5');
            $line->SetColor("red");
            $graph->Add($line);
        }

        $graph->legend->Pos( 0.05,0.05,"right" ,"center");
        $graph->legend->SetColumns(4);
		$graph->yaxis->title->Set('heures normalisées');
		$graph->yaxis->SetTitlemargin(65);
		$graph->xaxis->title->Set('date');
		$graph->xaxis->SetTitlemargin(60);

        ob_start();
        $graph->Stroke();
        $image_data = ob_get_contents();
        ob_end_clean();

        $size = getimagesizefromstring ( $image_data );
		return [ base64_encode($image_data), $size];
    }

	/* SEULEMENT POUR gramc */
	// recherche de la remise à zéro dans les 20 premiers jours
	// Normalement la conso en heures de calculs ne fait que grandir (sauf problème technique)
	// Sauf qu'on remet les compteurs à zéro en début d'année
	// Ici on détecte le jour de remise à zéro avant le 20 Janvier
	// et on met à zéro tout ce qui précède
	// Si vous remettez les compteurs à zéro après le 20 janvier, vous êtes mal

	// Modifier $structured_data
    public function resetConso(&$structured_data)
    {
        $remise_a_zero = null;
        $i = 20;
        $norm_precedente = 0;

        foreach( $structured_data as $key => $item )
        {
            if ( $i < 0 )   break;
            if ( $norm_precedente > $structured_data[$key]['norm'] )
            {
                $remise_a_zero = $key;
                break;
            }
            $norm_precedente = $structured_data[$key]['norm'];
            $i--;
        }

        // annulation avant la remise à zéro
        foreach( $structured_data as $key => $item )
        {
            if ( $remise_a_zero == null || $key >= $remise_a_zero )   break;
            $structured_data[$key]['gpu'] = $structured_data[$remise_a_zero]['gpu'];
            $structured_data[$key]['cpu'] = $structured_data[$remise_a_zero]['cpu'];
            $structured_data[$key]['quota'] = $structured_data[$remise_a_zero]['quota'];
            $structured_data[$key]['norm'] = $structured_data[$remise_a_zero]['norm'];
        }
	}
}

