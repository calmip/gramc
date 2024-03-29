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

/*
 *
 * une transition qui ajoute DAT_SESS_DEB si la session associée est déjà en état ACTIF
 * Utile quand un expert valide ou invalide une version après le démarrage de la session
 * 
 */

namespace AppBundle\Workflow\Projet;

use AppBundle\Workflow\Transition;
use AppBundle\AppBundle;
use AppBundle\Utils\Functions;
use AppBundle\Utils\Etat;
use AppBundle\Utils\Signal;
use AppBundle\Entity\Projet;
use AppBundle\Entity\Version;
use AppBundle\Workflow\Version\VersionWorkflow;

class DoubleProjetTransition extends ProjetTransition
{
    ////////////////////////////////////////////////////
    
    public function canExecute($projet)
    {
        if ( ! $projet instanceof Projet ) throw new \InvalidArgumentException;

        $rtn    =   parent::canExecute($projet);
            
        $version = $projet->getVersionDerniere();
        if( $version == null )
		{
            Functions::errorMessage("DoubleProjetTransition : version dernière null pour le projet " . $projet);
            return false;
		}

        $session = $version->getSession();
        if( $session == null )
		{
            Functions::errorMessage("DoubleProjetTransition : session null pour la version " . $projet);
            return false;
		}
            
        return $rtn;
    }

    ///////////////////////////////////////////////////////
    
    public function execute($projet)
    {
        if ( ! $projet instanceof Projet ) return false;
		if (Transition::DEBUG) Functions::debugMessage( ">>> " . __FILE__ . ":" . __LINE__ . " $this $projet");

        // Envoie le signal en utilisant ProjetTransition
        $rtn = parent::execute( $projet );

		// NOTE - DoubleProjetTransition ne doit être employée QUE avec le signal CLK_VAL_EXP_OK
        // Si la session COURANTE est active, l'expert est en retard: il expertise le projet APRES l'activation de la session
        // Dans ce cas, après CLK_VAL_EXP_OK, on envoie aux versions le signal CLK_SESS_DEB        
		$session = Functions::getSessionCourante();
		if( $session == null )
		{
			Functions::errorMessage("DoubleProjetTransition : session courante nulle");
			return false;
		}
		if( $session->getEtatSession() != Etat::ACTIF )
		{
			if (Transition::DEBUG) Functions::debugMessage( "<<< " . __FILE__ . ":" . __LINE__ . " rtn = " . Functions::show($rtn));
			return $rtn;
		}
	                
        $workflow = new VersionWorkflow();
        foreach( $projet->getVersion() as $version )
		{
            if( $session->getEtatSession() == Etat::ACTIF )
			{
				// Renvoie le signal de début de session à la version
                $return = $workflow->execute( Signal::CLK_SESS_DEB, $version );
                $rtn = Functions::merge_return( $rtn, $return );
                Functions::sauvegarder( $version );
			}
		}

		if (Transition::DEBUG) Functions::debugMessage( "<<< " . __FILE__ . ":" . __LINE__ . " rtn = " . Functions::show($rtn));

	    return $rtn; 
    }
}
