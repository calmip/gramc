<?php

namespace AppBundle\GramcGraf;

use AppBundle\Utils\Functions;

class Stockage extends GramcGraf
{
	/* Génère les données "StructuredData" qui seront utilisées par dessineConsoStorage
	 * afin de faire le graphique de l'occupation des espaces disques pour un projet sur une année
	 * $debut, $fin = timestamps de début et fin
	 * $db_data     = Le retour de la requête sql
	 * Retourne stuctured_data, c-a-d un array:
	 *     key = timestamp
	 *     val = [ $projet => $conso, 'quota' => $quota ]
	 */
	public function createStructuredData(\DateTime $date_debut, \DateTime $date_fin, $db_data)
	{
		$diviseur = 1.00*1024*1024*1024;
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
    *             $ressource, le nom de la ressource utilisée
    *
    *      return L'image calculée et codée en base64
    *
    */
    public function createImage($structured_data,$ressource=null)
    {
		// Compatibilité
		if ($ressource==null) $ressource = 'work_space';

		//$html = print_r($structured_data,true);
		//return $html;

        // tester si les données existent
        $no_prj   = true;
        $no_quota = true;
        foreach( $structured_data as $key => $item )
        {
            if( ! array_key_exists ( $ressource , $item ) )
                $structured_data[$key][$ressource] = 0;
            elseif ( $structured_data[$key][$ressource]  > 0 )
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
            $prj[]   = $structured_data[$key][$ressource];
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
            $line->SetLegend($ressource);
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
		$graph->yaxis->title->Set($ressource);
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


