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

namespace AppBundle\Utils;


class Etat
{
    // etats
    const CREE_ATTENTE              = 1;
    const EDITION_DEMANDE           = 2;
    const EDITION_EXPERTISE         = 3;
    const EN_ATTENTE                = 4;
    const ACTIF                     = 5;
    const NOUVELLE_VERSION_DEMANDEE = 6;
    const EN_STANDBY                = 7;
    const EN_SURSIS                 = 8;
    const TERMINE                   = 9;
    const ANNULE                    = 10;
    const FIN_ETATS                 = 11;

    const EDITION_TEST              = 21;
    const EXPERTISE_TEST            = 22;
    const ACTIF_TEST                = 23;

    const DESAFFECTE                = 31;

    const RENOUVELABLE              = 41;
    const NON_RENOUVELABLE          = 42;

    const   LIBELLE_ETAT=
        [
            self::CREE_ATTENTE              =>  'CREE_ATTENTE',
            self::EDITION_DEMANDE           =>  'EDITION_DEMANDE',
            self::EDITION_EXPERTISE         =>  'EDITION_EXPERTISE',
            self::EN_ATTENTE                =>  'EN_ATTENTE',
            self::ACTIF                     =>  'ACTIF',
            self::NOUVELLE_VERSION_DEMANDEE =>  'NOUVELLE_VERSION_DEMANDEE',
            self::EN_STANDBY                =>  'EN_STANDBY',
            self::EN_SURSIS                 =>  'EN_SURSIS',
            self::TERMINE                   =>  'TERMINE',
            self::ANNULE                    =>  'ANNULE',
            self::FIN_ETATS                 =>  'FIN_ETATS',
            self::EDITION_TEST              =>  'EDITION_TEST',
            self::EXPERTISE_TEST            =>  'EXPERTISE_TEST',
            self::ACTIF_TEST                =>  'ACTIF_TEST',
            self::DESAFFECTE                =>  'DESAFFECTE',
            self::RENOUVELABLE              =>  'RENOUVELABLE',
            self::NON_RENOUVELABLE          =>  'NON_RENOUVELABLE',
        ];

    public static function getLibelle($etat)
    {
        if( $etat != null && array_key_exists( $etat , Etat::LIBELLE_ETAT) )
            return self::LIBELLE_ETAT[$etat];
        else
            return 'UNKNOWN';
    }

    public static function getEtat($libelle)
    {
        $array_flip = array_flip( self::LIBELLE_ETAT  );

        if( $libelle != null && array_key_exists(  $libelle, $array_flip ) )
            return $array_flip[$libelle];
        else
            return null;
    }
}
