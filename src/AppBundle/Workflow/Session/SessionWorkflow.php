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

namespace AppBundle\Workflow\Session;

use AppBundle\Workflow\Workflow;
use AppBundle\Workflow\NoTransition;
use AppBundle\Utils\Etat;
use AppBundle\Utils\Signal;
use AppBundle\Entity\Session;

/*********************
 * Changements d'états de la classe Session
 * 
 * cf. doc/uml/diagrammes_etat_transition/etats-d-une-session.odg
 ************************************************************************/
class SessionWorkflow extends Workflow
{
    public function __construct()
    {
        parent::__construct();

        $this
            ->addState( Etat::CREE_ATTENTE,
                [
                Signal::DAT_DEB_DEM => new SessionTransition(Etat::EDITION_DEMANDE, Signal::DAT_DEB_DEM),
                ])
            ->addState( Etat::EDITION_DEMANDE,
                [
                Signal::DAT_FIN_DEM => new SessionTransition(Etat::EDITION_EXPERTISE, Signal::DAT_FIN_DEM),
                ])
            ->addState( Etat::EDITION_EXPERTISE,
                [
                Signal::CLK_ATTR_PRS => new SessionTransition(Etat::EN_ATTENTE, Signal::CLK_ATTR_PRS),
                ])
            ->addState( Etat::EN_ATTENTE,
                [
                Signal::CLK_SESS_FIN => new NoTransition(0,0), 
                Signal::CLK_SESS_DEB => new SessionTransition(Etat::ACTIF, Signal::CLK_SESS_DEB, [], true),
                ])
            ->addState( Etat::ACTIF,
                [
                Signal::CLK_SESS_FIN => new SessionTransition(Etat::TERMINE, Signal::CLK_SESS_FIN, [], true),
              //  Signal::CLK_SESS_DEB => new NoTransition(0,0),
                ])
             ->addState( Etat::TERMINE,
                [
                Signal::CLK_SESS_DEB => new NoTransition(0,0),
                Signal::CLK_SESS_FIN => new NoTransition(0,0),
                ]);
    }

}
