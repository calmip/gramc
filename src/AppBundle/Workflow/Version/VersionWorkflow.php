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

use AppBundle\Workflow\Workflow;
use AppBundle\Workflow\NoTransition;
use AppBundle\Utils\Etat;
use AppBundle\Utils\Signal;


class VersionWorkflow extends Workflow
{

    public function __construct()
    {
        $this->workflowIdentifier   = get_class($this);
        parent::__construct();

        $this
            ->addState( Etat::CREE_ATTENTE,
                [
                Signal::CLK_DEMANDE     => new VersionTransition(Etat::EDITION_DEMANDE, Signal::CLK_DEMANDE),
                Signal::CLK_TEST        => new VersionTransition(Etat::EDITION_TEST   , Signal::CLK_TEST),
                Signal::CLK_SESS_DEB    => new VersionTransition(Etat::TERMINE        , Signal::CLK_SESS_DEB),
                Signal::CLK_SESS_FIN    => new VersionTransition(Etat::TERMINE        , Signal::CLK_SESS_FIN),
                Signal::CLK_FERM        => new VersionTransition(Etat::TERMINE        , Signal::CLK_FERM),
                ])
            ->addState( Etat::EDITION_TEST, // projet test
                [
                Signal::CLK_VAL_DEM     => new VersionTransition(Etat::EXPERTISE_TEST , Signal::CLK_VAL_DEM, 
                                           [ 'R' => 'depot_projet_test_pour_demandeur',
                                             'A' => 'depot_projet_test_pour_admin',
                                             'P' => 'depot_projet_test_pour_president' ]),
                Signal::CLK_FERM        => new VersionTransition(Etat::TERMINE        , Signal::CLK_FERM),
                Signal::CLK_DEMANDE     => new VersionTransition(Etat::TERMINE        , Signal::CLK_DEMANDE),
                Signal::CLK_SESS_DEB    => new NoTransition(0,0),
                Signal::CLK_SESS_FIN    => new VersionTransition(Etat::TERMINE        , Signal::CLK_SESS_FIN, []),
                ])
            ->addState( Etat::ACTIF_TEST,
                [
                Signal::CLK_SESS_DEB    => new NoTransition(0,0),
                Signal::CLK_SESS_FIN    => new VersionTransition(Etat::TERMINE, Signal::CLK_SESS_FIN, [], true),
                Signal::CLK_VAL_EXP_OK  => new NoTransition(0,0),
                Signal::CLK_FERM        => new VersionTransition(Etat::TERMINE, Signal::CLK_FERM,     [], true),
                Signal::CLK_VAL_EXP_KO  => new NoTransition(0,0),
                Signal::CLK_VAL_EXP_CONT=> new NoTransition(0,0),
                Signal::CLK_VAL_DEM     => new NoTransition(0,0),
                Signal::CLK_ARR         => new NoTransition(0,0),
                Signal::CLK_DEMANDE     => new NoTransition(0,0),
                ])
            ->addState( Etat::EXPERTISE_TEST, 
                [
                Signal::CLK_VAL_EXP_OK  => new VersionTransition(Etat::ACTIF_TEST     , Signal::CLK_VAL_EXP_OK,
                                           [ 'R' => 'expertise', 
                                             'E' => 'expertise_pour_expert', 
                                             'A' => 'expertise_pour_admin' ]),
                Signal::CLK_VAL_EXP_KO  => new VersionTransition(Etat::TERMINE        , Signal::CLK_VAL_EXP_KO,
                                           [ 'E' => 'expertise_pour_expert', 
                                             'A' => 'expertise_pour_admin', 
                                             'P' => 'expertise_refusee' ],  Signal::CLK_VAL_EXP_KO ),
                Signal::CLK_FERM        => new VersionTransition(Etat::TERMINE        , Signal::CLK_FERM),
                Signal::CLK_ARR         => new VersionTransition(Etat::EDITION_TEST   , Signal::CLK_ARR),
                Signal::CLK_DEMANDE     => new VersionTransition(Etat::TERMINE        , Signal::CLK_DEMANDE),
                Signal::CLK_SESS_DEB    => new NoTransition(0,0),
                Signal::CLK_SESS_FIN    => new VersionTransition(Etat::TERMINE        , Signal::CLK_SESS_FIN),
                ])
            ->addState( Etat::EDITION_DEMANDE,
                [
                Signal::CLK_VAL_DEM     => new VersionTransition(Etat::EDITION_EXPERTISE, Signal::CLK_VAL_DEM,
                                           [ 'R' => 'depot_pour_demandeur', 
                                             'A' => 'depot_pour_experts',
                                             'ET' => 'depot_pour_experts']),
                Signal::CLK_SESS_DEB    => new VersionTransition(Etat::TERMINE, Signal::CLK_SESS_DEB),
                Signal::CLK_SESS_FIN    => new VersionTransition(Etat::TERMINE, Signal::CLK_SESS_FIN),
                Signal::CLK_FERM        => new VersionTransition(Etat::TERMINE, Signal::CLK_FERM),
                Signal::CLK_DEMANDE     => new VersionTransition(Etat::TERMINE, Signal::CLK_DEMANDE),
                ])
            ->addState( Etat::EDITION_EXPERTISE,
                [
                Signal::CLK_VAL_EXP_OK  => new VersionTransition(Etat::EN_ATTENTE, Signal::CLK_VAL_EXP_OK,
                                           [ 'R' => 'expertise',
                                             'E' => 'expertise_pour_expert',
                                             'A' => 'expertise_pour_admin' ]),
                Signal::CLK_VAL_EXP_KO  => new VersionTransition(Etat::TERMINE   , Signal::CLK_VAL_EXP_KO,   
                                           [ 'E' => 'expertise_pour_expert', 
                                             'A' => 'expertise_pour_admin', 
                                             'P' => 'expertise_refusee' ] ),
                Signal::CLK_VAL_EXP_CONT=> new VersionTransition(Etat::TERMINE   , Signal::CLK_VAL_EXP_CONT,
                                           [ 'R' => 'expertise',
                                             'E' => 'expertise_pour_expert', 
                                             'A' => 'expertise_pour_admin' ] ),
                Signal::CLK_SESS_DEB    => new NoTransition(0,0),
                Signal::CLK_SESS_FIN    => new VersionTransition(Etat::TERMINE   , Signal::CLK_SESS_FIN),
                Signal::CLK_FERM        => new VersionTransition(Etat::TERMINE   , Signal::CLK_FERM),
                Signal::CLK_ARR         => new VersionTransition(Etat::EDITION_DEMANDE, Signal::CLK_ARR),
                Signal::CLK_DEMANDE     => new VersionTransition(Etat::TERMINE   , Signal::CLK_DEMANDE),
                ])
            ->addState( Etat::EN_ATTENTE,
                [
                Signal::CLK_SESS_DEB    => new VersionTransition(Etat::ACTIF  , Signal::CLK_SESS_DEB),
                Signal::CLK_SESS_FIN    => new VersionTransition(Etat::TERMINE, Signal::CLK_SESS_FIN),
                Signal::CLK_FERM        => new VersionTransition(Etat::TERMINE, Signal::CLK_FERM),
                ])
            ->addState( Etat::ACTIF,
                [
                Signal::CLK_SESS_DEB    => new NoTransition(0,0),
                Signal::CLK_SESS_FIN    => new VersionTransition(Etat::TERMINE, Signal::CLK_SESS_FIN, [], true),
                Signal::CLK_VAL_EXP_OK  => new VersionTransition(Etat::NOUVELLE_VERSION_DEMANDEE, Signal::CLK_VAL_EXP_OK),
                Signal::CLK_FERM        => new VersionTransition(Etat::TERMINE, Signal::CLK_FERM,     [], true),
                Signal::CLK_VAL_EXP_KO  => new NoTransition(0,0),
                Signal::CLK_VAL_EXP_CONT=> new NoTransition(0,0),
                Signal::CLK_VAL_DEM     => new NoTransition(0,0),
                Signal::CLK_ARR         => new NoTransition(0,0),
                Signal::CLK_DEMANDE     => new NoTransition(0,0),
                ])
             ->addState( Etat::NOUVELLE_VERSION_DEMANDEE, // quand une autre version est EN_ATTENTE
                [
                // Si on reçoit SESS_DEB dans cet état, on FERME la version
                Signal::CLK_SESS_DEB    => new VersionTransition(Etat::TERMINE, Signal::CLK_SESS_FIN, [], true),
                Signal::CLK_SESS_FIN    => new VersionTransition(Etat::TERMINE, Signal::CLK_SESS_FIN, [], true),
                Signal::CLK_FERM        => new VersionTransition(Etat::TERMINE, Signal::CLK_FERM,     [], true),
                ])
             ->addState( Etat::TERMINE,
                [
                Signal::CLK_SESS_DEB    => new NoTransition(0,0),
                Signal::CLK_SESS_FIN    => new NoTransition(0,0),
                Signal::CLK_FERM        => new NoTransition(0,0),
                ])
            ->addState( Etat::ANNULE, // provisoire
                [
                Signal::CLK_SESS_DEB    => new VersionTransition(Etat::TERMINE, Signal::CLK_SESS_DEB),
                Signal::CLK_SESS_FIN    => new VersionTransition(Etat::TERMINE, Signal::CLK_SESS_FIN),
                Signal::CLK_FERM        => new VersionTransition(Etat::TERMINE, Signal::CLK_FERM),
                ]);
    }
}
