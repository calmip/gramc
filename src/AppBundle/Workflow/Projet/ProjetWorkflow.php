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

namespace AppBundle\Workflow\Projet;

use AppBundle\Workflow\Workflow;
use AppBundle\Utils\Etat;
use AppBundle\Utils\Signal;
use AppBundle\Workflow\NoTransition;


class ProjetWorkflow extends Workflow
{

    public function __construct()
    {
        $this->workflowIdentifier   = get_class($this);
        parent::__construct();
        
        $this
            ->addState( Etat::RENOUVELABLE,
                [
                // Utile seulement pour propagation aux versions
                Signal::CLK_DEMANDE         =>  new ProjetTransition(Etat::RENOUVELABLE, Signal::CLK_DEMANDE, [], true),
                Signal::CLK_VAL_DEM         =>  new ProjetTransition(Etat::RENOUVELABLE, Signal::CLK_VAL_DEM, [], true),
                Signal::CLK_ARR             =>  new ProjetTransition(Etat::RENOUVELABLE, Signal::CLK_ARR,     [], true),
                
                Signal::CLK_VAL_EXP_OK      =>  new DoubleProjetTransition(Etat::RENOUVELABLE, Signal::CLK_VAL_EXP_OK, [], true),
                Signal::CLK_VAL_EXP_CONT    =>  new ProjetTransition(Etat::RENOUVELABLE, Signal::CLK_VAL_EXP_CONT),
                
                Signal::CLK_SESS_FIN        =>  new NoTransition(0,0),
                Signal::CLK_SESS_DEB        =>  new ProjetTransition(Etat::RENOUVELABLE, Signal::CLK_SESS_DEB ),
                 
                Signal::CLK_VAL_EXP_KO      =>  new ProjetTransition(Etat::NON_RENOUVELABLE,Signal::CLK_VAL_EXP_KO,[],true),
                Signal::CLK_FERM            =>  new ProjetTransition(Etat::TERMINE,Signal::CLK_FERM),
                ])
             ->addState( Etat::NON_RENOUVELABLE,
                [
                Signal::CLK_DEMANDE         =>  new ProjetTransition(Etat::NON_RENOUVELABLE, Signal::CLK_DEMANDE, [], true),
                Signal::CLK_VAL_DEM         =>  new ProjetTransition(Etat::NON_RENOUVELABLE, Signal::CLK_VAL_DEM, [], true),
                Signal::CLK_ARR             =>  new ProjetTransition(Etat::NON_RENOUVELABLE, Signal::CLK_ARR,     [], true),
                Signal::CLK_VAL_EXP_OK      =>  new ProjetTransition(Etat::NON_RENOUVELABLE, Signal::CLK_VAL_EXP_OK, [], true),
    
                Signal::CLK_VAL_EXP_CONT    =>  new ProjetTransition(Etat::TERMINE, Signal::CLK_VAL_EXP_CONT),
                Signal::CLK_VAL_EXP_KO      =>  new ProjetTransition(Etat::TERMINE,Signal::CLK_VAL_EXP_KO, [], true),
                Signal::CLK_FERM            =>  new ProjetTransition(Etat::TERMINE,Signal::CLK_FERM, [], true),

                Signal::CLK_SESS_DEB        =>  new NoTransition(0,0),
                Signal::CLK_SESS_FIN        =>  new ProjetTransition(Etat::TERMINE, Signal::CLK_SESS_FIN),
                 ])
             
             ->addState( Etat::TERMINE,
                [
                Signal::CLK_SESS_FIN        =>  new NoTransition(0,0),
                Signal::CLK_SESS_DEB        =>  new NoTransition(0,0),
                Signal::CLK_FERM            =>  new NoTransition(0,0),
                ]);
            

    }

    
    
}
