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
use AppBundle\AppBundle;


class GramcDate extends GramcDateTime
{

    public function __construct ( $time = "now" , \DateTimeZone $timezone = null )
    {
        parent::__construct( $time,  $timezone  );
        $this->setTime(0,0,0);
    }

    static public function get()
    {
        return new GramcDate();
    }

    // Sommes-nous en periode de récupération des heures de printemps ?
    // param $annee Année considérée - Si non année courante, on renvoie false
    // return true/false
    static public function isRecupPrintemps($annee)
    {
        $d = new GramcDate();

        // Pas de paramètres: renvoie false
        if ( ! AppBundle::hasParameter('recup_printemps_d')) return false;
        if ( ! AppBundle::hasParameter('recup_printemps_f')) return false;
        if ($annee!=$d->showYear() && $annee-2000!=$d->showYear()) return false;

        $m = $d->showMonth();

        if ( $m >= AppBundle::getParameter('recup_printemps_d') && $m < AppBundle::getParameter('recup_printemps_f') )
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    // Sommes-nous en periode de récupération des heures d'automne ?
    // param $annee Année considérée - Si non année courante, on renvoie false
    // return true/false
    static public function isRecupAutomne($annee)
    {
        $d = new GramcDate();

        // Pas de paramètres: renvoie false
        if ( ! AppBundle::hasParameter('recup_automne_d')) return false;
        if ( ! AppBundle::hasParameter('recup_automne_f')) return false;
        if ($annee!=$d->showYear() && $annee-2000!=$d->showYear()) return false;

        $m = intval($d->showMonth());
        //Functions::debugMessage(__METHOD__.':'.__LINE__ ." m=$m recup_automne_d=" . intval(AppBundle::getParameter('recup_automne_d')));

        if ( $m >= intval(AppBundle::getParameter('recup_automne_d')) && $m < intval(AppBundle::getParameter('recup_automne_f')) )
        {
            return true;
        }
        else
        {
            return false;
        }
    }
}
