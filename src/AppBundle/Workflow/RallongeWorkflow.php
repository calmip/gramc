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

namespace AppBundle\Workflow;

use AppBundle\Workflow\Workflow;
//use AppBundle\Exception\WorkflowException;
//use AppBundle\Utils\Functions;
use AppBundle\Utils\Etat;
use AppBundle\Utils\Signal;


class RallongeWorkflow extends Workflow
{
    protected static    $states               = [];
    protected static    $workflowIdentifier   = null;
    
    public function __construct()
    {
        if( static::$workflowIdentifier != null ) return;
        parent::__construct();
        
        static::addState( Etat::EDITION_DEMANDE,
                [
                Signal::CLK_VAL_DEM     =>  new Transition(Etat::EDITION_EXPERTISE),
                Signal::FERMER_RALLONGE =>  new Transition(Etat::ANNULE),
                ]);
    
        static::addState( Etat::EDITION_EXPERTISE,
                [
                Signal::CLK_VAL_EXP_OK  =>  new Transition(Etat::EN_ATTENTE),
                Signal::CLK_VAL_EXP_KO  =>  new Transition(Etat::EN_ATTENTE),
                Signal::FERMER_RALLONGE =>  new Transition(Etat::ANNULE),
                ]);

        static::addState( Etat::EN_ATTENTE,
                [
                Signal::CLK_VAL_PRS     =>  new Transition(Etat::ACTIF),
                Signal::FERMER_RALLONGE =>  new Transition(Etat::ANNULE),
                ]);

        static::addState( Etat::ACTIF,
                [
                Signal::FERMER_RALLONGE          =>  new Transition(Etat::TERMINE),
                ]);

        static::addState( Etat::TERMINE,
                [
                Signal::FERMER_RALLONGE          =>  new Transition(Etat::TERMINE),
                ]);
                
        static::addState( Etat::ANNULE,
                [
                Signal::FERMER_RALLONGE          =>  new Transition(Etat::ANNULE),
                ]);
        
    }

    
    
}
