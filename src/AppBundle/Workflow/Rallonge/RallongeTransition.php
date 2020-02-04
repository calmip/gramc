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

namespace AppBundle\Workflow\Rallonge;

use AppBundle\Workflow\Transition;
use AppBundle\AppBundle;
use AppBundle\Utils\Functions;
use AppBundle\Utils\Etat;
use AppBundle\Utils\Signal;
use AppBundle\Entity\Rallonge;
use AppBundle\Workflow\Rallonge\RallongeWorkflow;


class RallongeTransition extends Transition
{

    ////////////////////////////////////////////////////
    
    public function canExecute($rallonge)
    {
       if ( $rallonge instanceof Rallonge )
            return true;
        else
            return false;        
    }

    ///////////////////////////////////////////////////////
    
    public function execute($rallonge)
    {
		if ( !$rallonge instanceof Rallonge ) throw new \InvalidArgumentException;

 		// Change l'état de la rallonge
		$this->changeEtat($rallonge);

		// Envoyer les notifications
		$this->sendNotif($rallonge);

		return true;
    }

}
