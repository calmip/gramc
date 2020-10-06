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

class Stockage extends GramcGraf
{
	/* Génère les données "StructuredData" qui seront utilisées par dessineConsoStorage
	 * afin de faire le graphique de l'occupation des espaces disques pour un projet sur une année
	 * $debut, $fin = timestamps de début et fin
	 * $db_data     = Le retour de la requête sql
	 * $unite       = L'unité pour mise à l'échelle, Go ou To (cf. les paramètres ressources_conso_xxx dans parameters.yml)
	 * Retourne stuctured_data, c-a-d un array:
	 *     key = timestamp
	 *     val = [ $projet => $conso, 'quota' => $quota ]
	 */
	public function createStructuredData(\DateTime $date_debut, \DateTime $date_fin, $db_data, $unite='')
	{
		$diviseur = 1.0;
		if ( $unite==='Gio')
		{
			$diviseur *= 1024 * 1024;
		}
		elseif ( $unite === 'Tio')
		{
			$diviseur *= 1024 * 1024 * 1024;
		}
		elseif ( $unite === 'Go' )
		{
			$diviseur *= 1000 * 1000;
		}
		elseif ( $unite === 'To' )
		{
			$diviseur *= 1000 * 1000 * 1000;
		}

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
					// Ne devrait pas arriver pour l'instant
					continue;
                    //$structured_data[$key][$item->getRessource()] = $item->getConso();
					//$quota1 = $structured_data[$key]['quota'];
					//$quota2 = $item->getQuota();
					//if( $quota1 != $quota2 )
					//{
					//	Functions::errorMessage( __METHOD__ . ':' . __LINE__ . ' incohérence dans les quotas, date = ' .  $item->getDate()->format("d F Y") . ' projet = '. $projet );
					//}
                }
                else
                {
                    $data = [$item->getRessource() => $item->getConso()/$diviseur, 'quota' => $item->getQuota()/$diviseur];
                    $structured_data[$key] = $data;
                }
            }
        }
        return $structured_data;
	}

    /*
    * Affichage du graphique du stockage d'un projet
    *
    *      params $structured_data, retour de createStructuredData
    *             $ressource, la ressource utilisée (un array, cf. parameters.yml
    *
    *      return L'image calculée et codée en base64
    *
    */
    public function createImage($structured_data,$ressource)
    {
		// Compatibilité
		if ($ressource==null) $ressource = $this->ressources_conso_group['2']; // work_space

        // tester si les données existent
        $no_prj   = true;
        $no_quota = true;
        $res_nom  = $ressource['nom'];
        $ress     = $ressource['ress'];
        $res_unite= $ressource['unite'];
        foreach( $structured_data as $key => $item )
        {
            if( ! array_key_exists ( $ress , $item ) )
                $structured_data[$key][$ress] = 0;
            elseif ( $structured_data[$key][$ress]  > 0 )
                $no_prj = false;

		   if ( ! array_key_exists ( 'quota' , $item ) )
				$structured_data[$key]['quota'] = 0;
			elseif ( $structured_data[$key]['quota'] > 0 )
				$no_quota = false;
        }

        // création des tables
        $xdata = [];
        $prj   = [];
        $quota = [];
        $quota_max = 0;

        foreach( $structured_data as $key => $item )
        {
			$xdata[] = $key;
            $prj[]   = $structured_data[$key][$ress];
            $quota[] = $structured_data[$key]['quota'];
            if ($structured_data[$key]['quota']>$quota_max) $quota_max=$structured_data[$key]['quota'];
        }
		//Functions::errorMessage("coucou " );

        \JpGraph\JpGraph::load();
        \JpGraph\JpGraph::module('line');
        \JpGraph\JpGraph::module('date');


        // Create the new graph
        $graph = new \Graph(540,300);

        //$graph = new \Graph(600,400);
        // Slightly larger than normal margins at the bottom to have room for
        // the x-axis labels and at left to have room for y-axis labels
        $graph->SetMargin(80,40,40,170);

        // Fix the Y-scale to go between [0,100] and use date for the x-axis
        //$graph->SetScale('datlin',0,100);
        $graph->SetScale('datlin',0,$quota_max*1.1);
        $graph->xaxis->scale->SetDateFormat("d-m-y");

        $graph->SetTickDensity( \TICKD_SPARSE, \TICKD_SPARSE );
        //$graph->xaxis->scale->AdjustForDST(false);
        $graph->xaxis->scale->SetDateAlign(\DAYADJ_1);
        //$graph->xaxis->scale->ticks->Set(8,2);

        // Set the angle for the labels to 90 degrees
        $graph->xaxis->SetLabelAngle(90);

        if( $no_prj == false )
        {
            $line = new \LinePlot($prj,$xdata);
            $line->SetLegend($res_nom);
            //$line->SetFillColor('lightblue@0.5');
            $line->SetColor("green");
            $graph->Add($line);
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
		$graph->yaxis->title->Set($res_nom.' ('.$res_unite.')');
		$graph->yaxis->SetTitlemargin(40);
		$graph->xaxis->title->Set('date');
		$graph->xaxis->SetTitlemargin(50);

        ob_start();
        $graph->Stroke();
        $image_data = ob_get_contents();
        ob_end_clean();

		$size = getimagesizefromstring ( $image_data );
		return [base64_encode($image_data), $size];
        //$image = base64_encode($image_data);

        //$twig = new \Twig_Environment( new \Twig_Loader_String(), array( 'strict_variables' => false ) );
        //$body = $twig->render( '<img src="data:image/png;base64, {{ EncodedImage }}" alt="Heures cpu/gpu" title="Heures cpu et gpu" /><hr /><img src="data:image/png;base64, {{ EncodedImage }}" />' ,  [ 'EncodedImage' => $image,      ] );

        //return new Response($body);

    }
}


