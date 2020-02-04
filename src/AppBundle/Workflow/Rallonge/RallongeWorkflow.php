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

use AppBundle\Workflow\Workflow;
use AppBundle\Workflow\Rallonge\RallongeTransition;
use AppBundle\Utils\Etat;
use AppBundle\Utils\Signal;
use AppBundle\Workflow\NoTransition;

class RallongeWorkflow extends Workflow
{
    protected   $states               = [];
    protected   $workflowIdentifier   = null;

    public function __construct()
    {
        if( $this->workflowIdentifier != null ) return;
        parent::__construct();

        $this
            ->addState( Etat::CREE_ATTENTE ,
                [
                Signal::CLK_DEMANDE      => new RallongeTransition(Etat::EDITION_DEMANDE, Signal::CLK_DEMANDE,
							                [ 'R' => 'creation_rallonge_pour_demandeur']),
                Signal::CLK_SESS_FIN     => new RallongeTransition(Etat::ANNULE, Signal::CLK_SESS_FIN),
                Signal::CLK_FERM         => new RallongeTransition(Etat::ANNULE, Signal::CLK_FERM),
                ])
            ->addState( Etat::EDITION_DEMANDE,
                [
                Signal::CLK_VAL_DEM      => new RallongeTransition(Etat::DESAFFECTE, Signal::CLK_VAL_DEM,
							                [ 'R' => 'depot_rallonge_pour_demandeur',
							                  'A' => 'depot_rallonge_pour_admin',
							                  'P' => 'depot_rallonge_pour_president']),
                Signal::CLK_SESS_FIN     => new RallongeTransition(Etat::ANNULE, Signal::CLK_SESS_FIN),
                Signal::CLK_FERM         => new RallongeTransition(Etat::ANNULE, Signal::CLK_FERM),
                ])
            ->addState( Etat::DESAFFECTE,
                [
                Signal::CLK_AFFECTER     => new RallongeTransition(Etat::EDITION_EXPERTISE, Signal::CLK_AFFECTER,
							                [ 'E' => 'affectation_expert_rallonge']),
                Signal::CLK_DESAFFECTER  => new NoTransition(0,0),
                Signal::CLK_SESS_FIN     => new RallongeTransition(Etat::ANNULE, Signal::CLK_SESS_FIN),
                Signal::CLK_FERM         => new RallongeTransition(Etat::ANNULE, Signal::CLK_FERM),
                ])
            ->addState( Etat::EDITION_EXPERTISE,
                [
                Signal::CLK_VAL_EXP_OK  =>  new RallongeTransition(Etat::EN_ATTENTE, Signal::CLK_VAL_EXP_OK,
							                [ 'A' => 'expertise_rallonge_pour_admin',
							                  'E' => 'expertise_rallonge_pour_expert',
							                  'P' => 'expertise_rallonge_pour_president']),
                Signal::CLK_VAL_EXP_KO  =>  new RallongeTransition(Etat::EN_ATTENTE, Signal::CLK_VAL_EXP_KO,
						                    [ 'A' => 'expertise_rallonge_pour_admin',
							                  'E' => 'expertise_rallonge_pour_expert',
							                  'P' => 'expertise_rallonge_pour_president']),
                Signal::CLK_DESAFFECTER =>  new RallongeTransition(Etat::DESAFFECTE, Signal::CLK_DESAFFECTER),
                Signal::CLK_AFFECTER    =>  new NoTransition(0,0),
                Signal::CLK_SESS_FIN     => new RallongeTransition(Etat::ANNULE, Signal::CLK_SESS_FIN),
                Signal::CLK_FERM         => new RallongeTransition(Etat::ANNULE, Signal::CLK_FERM),
                ])
            ->addState( Etat::EN_ATTENTE,
                [
                Signal::CLK_VAL_PRS     =>  new RallongeTransition(Etat::ACTIF, Signal::CLK_VAL_PRS,
							                [ 'R' => 'expertise_rallonge_finale_pour_demandeur',
							                  'A' => 'expertise_rallonge_finale_pour_admin',
							                  'E' => 'expertise_rallonge_finale_pour_expert',
							                  'P' => 'expertise_rallonge_finale_pour_president']),
                Signal::CLK_SESS_FIN     => new RallongeTransition(Etat::ANNULE, Signal::CLK_SESS_FIN),
                Signal::CLK_FERM         => new RallongeTransition(Etat::ANNULE, Signal::CLK_FERM),
                ])
            ->addState( Etat::ACTIF,
                [
                Signal::CLK_SESS_FIN     => new RallongeTransition(Etat::TERMINE, Signal::CLK_SESS_FIN),
                Signal::CLK_FERM         => new RallongeTransition(Etat::TERMINE, Signal::CLK_FERM),
                ])

            ->addState( Etat::TERMINE,
                [
                Signal::CLK_SESS_FIN     => new NoTransition(0,0),
                Signal::CLK_FERM         => new NoTransition(0,0),
                ])

            ->addState( Etat::ANNULE,
                [
                Signal::CLK_SESS_FIN     => new RallongeTransition(Etat::TERMINE, Signal::CLK_SESS_FIN),
                Signal::CLK_FERM         => new RallongeTransition(Etat::TERMINE, Signal::CLK_FERM),
                ]);
    }
}
