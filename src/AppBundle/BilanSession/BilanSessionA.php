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

namespace AppBundle\BilanSession;


use AppBundle\Entity\Session;
use AppBundle\Entity\Version;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use AppBundle\Form\ChoiceList\ExpertChoiceLoader;

use AppBundle\AppBundle;
use AppBundle\Utils\Functions;

/****************************************
 * BilanSession: cette classe encapsule les algorithmes utilisés par les calculs de bilan de session
 **********************************************************/

class BilanSessionA extends BilanSession
{

	/*******
	 * Appelée par bilanAction dans le cas d'une session A
	 *
	 *********/
	protected function getEntetes()
	{
		$annee_prec      = $this->annee_prec;
		$annee_conso     = $annee_prec;		// Session 20A -> on regarde la conso de 2019 !
		$nom_ress        = $this->nom_ress;
		$full_annee_prec = $this->full_annee_prec;
		$id_session      = $this->id_session;
        $entetes = ['Projet',
                    'Thématique',
                    'Rattachement',
                    'Responsable scientifique',
                    'Laboratoire',
                    'Rapport',
                    'Expert',
                    'Demandes '     .$full_annee_prec,
                    'Dem rall '     .$full_annee_prec,
                    'Attr rall '    .$full_annee_prec,
                    'Pénalités '    .$full_annee_prec,
                    'Attributions ' .$full_annee_prec,
                    'Demandes '     .$id_session,
                    'Attributions ' .$id_session,
                    'Quota '        .$annee_conso,
                    'Consommation ' .$annee_conso,
                    'Conso gpu normalisee ',
                    "Consommation $annee_prec (%)",
                    "quota $nom_ress (To)",
                    "occup $nom_ress (%)"
                    ];

        // Les mois pour les consos
        array_push($entetes,'Janvier','Février','Mars','Avril',
            'Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre');

        return $entetes;
	}
	
	protected function initTotaux()
	{
        $totaux=
		[
            "dem_heures_prec"       =>  0,
            "attr_heures_prec"      =>  0,
            "dem_rall_heures_prec"  =>  0,
            "attr_rall_heures_prec" =>  0,
            "penal_heures_prec"     =>  0,
            "dem_heures_cour"       =>  0,
            "attr_heures_cour"      =>  0,
            "quota"                 =>  0,
            "conso_an"              =>  0,
            "conso_gpu"             =>  0,
            "recuperable"           =>  0,
            "conso_stock"           =>  0,
            "quota_stock"           =>  0,
		];
        $conso_flds = ['m00','m01','m02','m03','m04','m05','m06','m07','m08','m09','m10','m11'];
        foreach  ($conso_flds as $m)    $totaux[$m] =   0;
		return $totaux;
	}
	
	/*********************************************
	 * Calcule une ligne du csv
	 * 
	 * Params = $version = La version
	 *          $totaux  = Le tableau des totaux
	 * 
	 * Renvoie    = Un tableau correspondant au DEBUT de la ligne csv
	 * Voir aussi = getLigneConso
	 *************************************************/
	protected function getLigne(Version $version, &$totaux)
	{
		$session_precedente_A = $this->session_precedente_A;
		$session_precedente_B = $this->session_precedente_B;
		$session_courante_A   = $this->session_courante_A;
		$full_annee_prec      = $this->full_annee_prec;
		$annee_conso          = $full_annee_prec;	// Session 20A = on regarde la conso de 2019 !
		$ress                 = $this->ress;
		$t_fact               = $this->t_fact;

		$projet = $version -> getProjet();
		
        if( $session_precedente_A != null )
			$version_precedente_A = AppBundle::getRepository(Version::class)
                            ->findOneVersion($session_precedente_A, $version->getProjet() );
		else $version_precedente_A = null;

		if( $session_precedente_B != null )
			$version_precedente_B = AppBundle::getRepository(Version::class)
                            ->findOneVersion($session_precedente_B, $version->getProjet() );
		else $version_precedente_B = null;

		if( $session_courante_A != null )
			$version_courante_A = AppBundle::getRepository(Version::class)
                            ->findOneVersion($session_courante_A, $version->getProjet() );
		else $version_courante_A = null;

		$dem_heures_prec           = 0;
		if( $version_precedente_A != null ) $dem_heures_prec += $version_precedente_A->getDemHeures();
		if( $version_precedente_B != null ) $dem_heures_prec += $version_precedente_B->getDemHeures();

		$attr_heures_prec          = 0;
		if( $version_precedente_A != null ) $attr_heures_prec += $version_precedente_A->getAttrHeures();
		if( $version_precedente_B != null ) $attr_heures_prec += $version_precedente_B->getAttrHeures();

		$penal_heures              = 0;
		if( $version_precedente_A != null ) $penal_heures   += $version_precedente_A->getPenalHeures();
		if( $version_precedente_B != null ) $penal_heures   += $version_precedente_B->getPenalHeures();

		$dem_heures_rallonge       = 0;
		if( $version_precedente_A != null ) $dem_heures_rallonge    += $version_precedente_A->getDemHeuresRallonge();
		if( $version_precedente_B != null ) $dem_heures_rallonge    += $version_precedente_B->getDemHeuresRallonge();

		$attr_heures_rallonge      = 0;
		if( $version_precedente_A != null ) $attr_heures_rallonge   += $version_precedente_A->getAttrHeuresRallonge();
		if( $version_precedente_B != null ) $attr_heures_rallonge   += $version_precedente_B->getAttrHeuresRallonge();

		$dem_heures_A              = 0;
		if( $version_courante_A != null ) $dem_heures_A += $version_courante_A->getDemHeures();

		$attr_heures_A             = 0;
		if( $version_courante_A != null ) $attr_heures_A +=
			$version_courante_A->getAttrHeuresTotal();

		$consoRessource = $projet->getConsoRessource('cpu',$annee_conso);
		$conso          = $consoRessource[0];
		$quota          = $consoRessource[1];
		$conso_gpu      = $projet->getConsoRessource('gpu',$annee_conso)[0];
		$dem_heure_cour = $version->getDemHeures();
		$attr_heure_cour= $version->getAttrHeures();
		$recuperable    = 0;
		$stockRessource = $projet->getConsoRessource($ress,$full_annee_prec);
		$conso_stock    = $stockRessource[0];	// Occupation de l'espace-disque
		$quota_stock    = $stockRessource[1];	// Quota de disque
		if ($quota_stock != 0)
		{
			$totaux['conso_stock'] += $conso_stock;
			$totaux['quota_stock'] += $quota_stock;
			$conso_stock = intval(100 * $conso_stock/$quota_stock);
			$quota_stock = intval($quota_stock / $t_fact);
		}
		else
		{
			$conso_stock = 0;
		}

		$ligne =
			[
				$projet,
				'"'. $version->getPrjThematique() .'"',
				'"'. $version->getPrjRattachement() .'"',
				'"'.$version->getResponsable() .'"',
				'"'.$version->getLabo().'"',
				( $version->hasRapportActivite() == true ) ? 'OUI' : 'NON',
				( $version->getResponsable()->getExpert() ) ? '*******' : $version->getExpert(),
				$dem_heures_prec,
				$dem_heures_rallonge,
				$attr_heures_rallonge,
				$penal_heures,
				$attr_heures_prec+$attr_heures_rallonge-$penal_heures,
			];

		$ligne = array_merge( $ligne,
			[
				$dem_heure_cour,
				$attr_heure_cour,
				$quota,
				$conso,
				$conso_gpu,
				$quota != 0  ? intval(round( $conso * 100 /$quota ) ): 0,
				$quota_stock,
				$conso_stock
			]);
		
		$totaux["dem_heures_prec"]       += $dem_heures_prec;
		$totaux["attr_heures_prec"]      += $attr_heures_prec;
		$totaux["dem_rall_heures_prec"]  += $dem_heures_rallonge;
		$totaux["attr_rall_heures_prec"] += $attr_heures_rallonge;
		$totaux["penal_heures_prec"]     += $penal_heures;
		$totaux["dem_heures_cour"]       += $dem_heure_cour;
		$totaux["attr_heures_cour"]      += $attr_heure_cour;
		$totaux["quota"]                 += $quota;
		$totaux["conso_an"]              += $version->getConsoCalcul(); //( $consommation != null ) ? $consommation->conso(): 0;
		$totaux["conso_gpu"]             += $conso_gpu;
		$totaux["recuperable"]           += $recuperable;

		return $ligne;
	}
	
	/**************************************************
	 * Renvoie le tableau des totaux dans le bon ordre
	 **************************************************/
	 protected function getTotaux($totaux)
	 {
		$t_fact = $this->t_fact;
		 
		// dernière ligne = les totaux
        $ligne  =
			[
			'TOTAL','','','','','',
			$totaux["dem_heures_prec"],
			$totaux["dem_rall_heures_prec"],
			$totaux["attr_rall_heures_prec"],
			$totaux["penal_heures_prec"],
			$totaux["attr_heures_prec"]+$totaux["attr_rall_heures_prec"]-$totaux["penal_heures_prec"],
			];

		$totaux_quota_stock = intval($totaux['quota_stock']/$t_fact);
		$totaux_conso_stock = intval($totaux['conso_stock']/$t_fact);

		$ligne  =  array_merge( $ligne,
			[
			$totaux["dem_heures_cour"],
			$totaux["attr_heures_cour"],
			$totaux["quota"],
			$totaux["conso_an"],
			$totaux["conso_gpu"],
			'', // %
			"$totaux_quota_stock (To)",
			"$totaux_conso_stock (To)",
			]);

		$ligne  = array_merge( $ligne,
			[
			$totaux["m00"],$totaux["m01"],$totaux["m02"],$totaux["m03"],$totaux["m04"],$totaux["m05"],
			$totaux["m06"],$totaux["m07"],$totaux["m08"],$totaux["m09"],$totaux["m10"],$totaux["m11"],
			]);

		return $ligne;
	}
}

