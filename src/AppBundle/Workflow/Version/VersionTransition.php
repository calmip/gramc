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

namespace AppBundle\Workflow\Version;

use AppBundle\Workflow\Transition;
use AppBundle\AppBundle;
use AppBundle\Utils\Functions;
use AppBundle\Utils\Etat;
use AppBundle\Utils\Signal;
use AppBundle\Entity\Version;
use AppBundle\Workflow\Rallonge\RallongeWorkflow;
use AppBundle\Workflow\Projet\ProjetWorkflow;


class VersionTransition extends Transition
{
    private static $execute_en_cours     = false;
    
    ////////////////////////////////////////////////////
    public function canExecute($version)
    { 
		if ( !$version instanceof Version ) throw new \InvalidArgumentException;

		// Pour éviter une boucle infinie entre projet et version !
		if (self::$execute_en_cours) return true;
		else                         self::$execute_en_cours = true;

		$rtn = true;
		if (Transition::FAST == false && $this->getPropageSignal())
		{
			$rallonges = $version->getRallonge();
			if( $rallonges != null )
			{
				$workflow = new RallongeWorkflow();
				foreach( $rallonges as $rallonge )
				{
					$rtn = $rtn && $workflow->canExecute( $this->getSignal(), $rallonge );
				}
			}
		}
		
		self::$execute_en_cours = false;
		return $rtn;
    }

    ////////////////////////////////////////////////////
    public function execute($version)
    {
		if ( !$version instanceof Version ) throw new \InvalidArgumentException;
		if (Transition::DEBUG) Functions::debugMessage(">>> " .  __FILE__ . ":" . __LINE__ . " $this $version" );

		// Pour éviter une boucle infinie entre projet et version !
		if (self::$execute_en_cours) return true;
		self::$execute_en_cours = true;
		
		$rtn = true;

		// Propage le signal aux rallonges si demandé
		if ($this->getPropageSignal())
		{
			$rallonges = $version->getRallonge();

			if (count($rallonges) > 0)
			{
                $workflow = new RallongeWorkflow();

				// Propage le signal à toutes les rallonges qui dépendent de cette version
                foreach( $rallonges as $rallonge )
				{
                    $output = $workflow->execute( $this->getSignal(), $rallonge );
                    $rtn = Functions::merge_return( $rtn, $output );
				}
			}
		}

		// Propage le signal au projet si demandé
		if ($this->getPropageSignal())
		{
			$projet = $version->getProjet();
			$workflow = new ProjetWorkflow();
			$output   = $workflow->execute( $this->getSignal(), $projet);
			$rtn = Functions::merge_return( $rtn, $output);
		}
		
		// Change l'état de la version
		$this->changeEtat($version);

		// Envoi des notifications
		$this->sendNotif($version);

		self::$execute_en_cours = false;
		if (Transition::DEBUG) Functions::debugMessage( "<<< " . __FILE__ . ":" . __LINE__ . " rtn = " . Functions::show($rtn));

		return $rtn;
    }
}
