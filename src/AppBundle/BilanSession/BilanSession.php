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

abstract class BilanSession
{
	// Constructeur: Certains objets sont protégés dans les controleurs, 
	// donc on les passe séparément à affectationExperts
	// Arguments: $request   
	//			  $demandes	 La liste des demandes (array)
	//            $ff        Form factory
	//            $dct       getDoctrine()

	function __construct (Request $request, Session $session, $ff=null, $dct=null)
	{
		$this->formFactory = $ff;
		$this->doctrine    = $dct;
		$this->request     = $request;
		$this->session     = $session;
		
        $this->id_session  = $session->getIdSession();
        $this->annee_cour  = $session->getAnneeSession();
        $this->annee_prec  = $this->annee_cour - 1;
        $this->full_annee_cour      = 2000 + $this->annee_cour;
        $this->full_annee_prec      = 2000 + $this->annee_prec;
        $this->session_courante_A   = AppBundle::getRepository(Session::class)->findOneBy(['idSession' => $this->annee_cour .'A']);
        $this->session_courante_B   = AppBundle::getRepository(Session::class)->findOneBy(['idSession' => $this->annee_cour .'B']);
		$this->session_precedente_A = AppBundle::getRepository(Session::class)->findOneBy(['idSession' => $this->annee_prec .'A']);
        $this->session_precedente_B = AppBundle::getRepository(Session::class)->findOneBy(['idSession' => $this->annee_prec .'B']);
        
        // Année de prise en compte pour le calcul de la conso passée: 
        // 20A -> 2019, 20B -> 2020
		$type_session      = $session->getLibelleTypeSession(); // A ou B
		$this->annee_conso = ($type_session==='A') ? $this->annee_prec : $this->annee_cour;
        
		// Pour les ressources de stockage
		$ressources = AppBundle::getParameter('ressources_conso_group');
		foreach ($ressources as $k=>$r)
		{
			if ($r['type']==='stockage')
			{
				$this->ress     = $r['ress'];
				$this->nom_ress = $r['nom'];
			}
		}
		
		//		$t_fact = 1024*1024*1024;	// Conversion octets -> To
		$this->t_fact = 1024*1024*1024;
	}
	
	/*******
	 * Appelée par bilanAction dans le cas d'une session A
	 *
	 *********/
	public function getCsv()
	{
		$request              = $this->request;
		$session              = $this->session;
		$id_session           = $this->id_session;
		$annee_cour           = $this->annee_cour;
		$annee_prec           = $this->annee_prec;
		$session_courante_A   = $this->session_courante_A;
		$session_precedente_A = $this->session_precedente_A;
		$session_precedente_B = $this->session_precedente_B;
		$t_fact               = $this->t_fact;
        $versions             = AppBundle::getRepository(Version::class)->findBy( ['session' => $session ] );

		// Le tableau de totaux
		$totaux = $this->initTotaux();

		// première ligne = les entêtes
		$sortie = join($this->getEntetes(),"\t") . "\n";;

        // boucle principale
        foreach( $versions as $version )
		{
			$sortie .= join("\t",$this->getLigne($version,$totaux));
			$sortie.= "\t;";
            $sortie .= join("\t",$this->getLigneConso($version, $totaux));
            $sortie .= "\n";
		} 

		// Dernière ligne = les totaux
		$sortie .= join ("\t", $this->getTotaux($totaux));
		$sortie .= "\n";
		
		$file_name = 'bilan_session_'.$id_session.'.csv';

        return [$sortie, $file_name];
    }
	
	/*********************************************
	 * Calcule la fin de  ligne du csv
	 * Données de consommation par mois
	 * 
	 * Params = $version = La version
	 *          $totaux  = Le tableau des totaux
	 * 
	 * Renvoie    = Un tableau correspondant à la FIN de la ligne csv
	 * Voir aussi = getLigne
	 *************************************************/
	protected function getLigneConso(Version $version, &$totaux)
	{
		$annee_conso = $this->annee_conso;
		$full_annee_prec = $this->full_annee_prec;
		for ($m=0;$m<12;$m++)
		{
			$consmois= $version->getProjet()->getConsoMois($annee_conso,$m);
			$index   = 'm' . ($m<10?'0':'') . $m;

			$ligne[]         = $consmois;
			$totaux[$index] += $consmois;
		};
		return $ligne;
	}

}
