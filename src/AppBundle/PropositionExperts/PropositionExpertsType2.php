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

namespace AppBundle\PropositionExperts;

use AppBundle\Entity\Version;
use AppBundle\Entity\Projet;
use AppBundle\Entity\Individu;
use AppBundle\Entity\CollaborateurVersion;


/***********************************************************************************************
 * PropositionExpertsType2 et 3 - Propose un expert, pour les projets de type 2/3 (fil de l'eau):
 *        - S'il y a un rattachement administratif:
 *               - On prend les experts liés au rattachement
 *               - C'est administratif donc pas de conflit d'intérêt
 *        - Sinon
 *               - On prend les présidents
 *               - On gère les conflits d'intérêts
 * 
 **********************************************************************/
class PropositionExpertsType2 extends PropositionExperts
{
	public function getProposition(Version $version)
	{
		$rattachement = $version -> getPrjRattachement();
		
		if ($rattachement == null)
		{
			// Les exclus sont les collaborateurs actuels du projet
		    $exclus = $this->em->getRepository(CollaborateurVersion::class)->getCollaborateurs( $version );
		} else {
			// pas d'exclus
			$exclus = [];
		}

		if ($rattachement != null) 
		{
		    $experts = $rattachement->getExpert();
		    if( $experts == null  )
	        {
		        Functions::warningMessage(__METHOD__  .  ":" . __LINE__ ." rattachement " . $rattachement . " n'a pas d'expert !" );
		        return null;
	        }
		}
		else
		{
	        $experts = $this->em->getRepository(Individu::class)->findBy(['president'=>true]);
		}

		$expert = $this->getExpertDisponible($experts, $exclus);
		return $expert;
	}
}
