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

use AppBundle\AppBundle;
use AppBundle\Entity\Version;
use AppBundle\Entity\Projet;
use AppBundle\Entity\CollaborateurVersion;


/***********************************************************************************************
 * PropositionExpertsType1 - Propose un expert, pour les projets de type 1 (liés à une session)
 *        - Si c'est un renouvellement on cherche dans les versions précédentes
 *        - S'il y a un rattachement administratif:
 *               - On prend les experts liés au rattachement
 *               - C'est administratif donc pas de conflit d'intérêt
 *        - Sinon
 *               - On prend les experts liés à la thématique
 *               - On gère les conflits d'intérêts
 * 
 **********************************************************************/
class PropositionExpertsType1 extends PropositionExperts
{
	public function getProposition(Version $version)
	{
		$rattachement = $version -> getPrjRattachement();
		
		if ($version->getPrjRattachement() == null)
		{
			// Les exclus sont les collaborateurs actuels du projet
		    $exclus = AppBundle::getRepository(CollaborateurVersion::class)->getCollaborateurs( $version );
		} else {
			// pas d'exclus
			$exclus = [];
		}

	    // Si possible on reprend l'expert de la version précédente
	    $expert = $this -> getExpertVersionPrecedente($version, $exclus);
	    if ($expert != null) return $expert;
	    
		// Expert pas trouvé dans les versions précédentes, on cherche dans les experts de rattachement ou de thématique
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
		    $thematique = $version->getPrjThematique();
		    if( $thematique == null )
	        {
		        Functions::errorMessage(__METHOD__ ." version " . $version->getIdVersion() . " n'a pas de thématique !" );
		        return null;
	        }
	
		    $experts = $thematique->getExpert();
		    if( $experts == null  )
	        {
		        Functions::warningMessage(__METHOD__  .  ":" . __LINE__ ." thematique " . $thematique . " n'a pas d'expert !" );
	        }
		}
		
		$expert = $this->getExpertDisponible($experts, $exclus);
		return $expert;		
	}
}
