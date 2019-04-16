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
use AppBundle\Test;


// GramcDate avec l'heure exacte pour Journal

/*
 * examples parametres :
 *
 * DateString: -2 years + 3 days
 * DateShift: P3Y30D
 * NewDate: 20130101
 * OldDate: 20130101
 *
 */


class GramcDateTime extends \DateTime
{

public function __construct ( $time = "now" , \DateTimeZone $timezone = null )

    {
    // uniquement pour des tests unitaires
    if( ( AppBundle::getEnvironment() == 'test' ) )
        {
            if( $time == "now")
                {
                if( Test::$nowGramcDateTime   instanceof  \DateTime )
                    parent::__construct( Test::$nowGramcDateTime->format('Y-m-dTH:i:s'),  $timezone  );
                else
                    parent::__construct("2017-02-28T11:12:13",  $timezone  );
                }
            else
                parent::__construct( $time,  $timezone  );
            return;
        }

    if( AppBundle::hasParameter('NewDate') && ! AppBundle::hasParameter('OldDate')  )
        {
            parent::__construct( AppBundle::getParameter('NewDate'),  $timezone  );
            return;
        }

    // variable now pour des tests unitaires
    if( AppBundle::hasParameter('now') && $time == "now" )
        parent::__construct( AppBundle::getParameter('now'),  $timezone  );
    else
        parent::__construct( $time,  $timezone  );

    if( AppBundle::hasParameter('DateString') )
        {
            $dateInterval =  \DateInterval::createFromDateString( AppBundle::getParameter('DateString') );
            $this->add($dateInterval);
            return;
        }
    elseif (   AppBundle::hasParameter('DateShift')  )
        {
            $dateInterval = new \DateInterval( AppBundle::getParameter('DateShift') );

            if( AppBundle::hasParameter('future') && AppBundle::getParameter('future') == false )
                $this->sub( $dateInterval );
            else
                $this->add( $dateInterval );
            return;
        }
    elseif ( AppBundle::hasParameter('NewDate') &&  AppBundle::hasParameter('OldDate')  )
        {
            $oldDate = new \DateTime( AppBundle::getParameter('OldDate') );
            $newDate = new \DateTime( AppBundle::getParameter('NewDate') );
            $dateInterval =  date_diff( $oldDate, $newDate );

            $this->add($dateInterval);
            return;
        }

    }


public function showDate( $format = "d F Y" )
    {
        return $this->format($format);
    }

public function showDateTime( $format = "d F Y H:i:s" )
    {
        return $this->format($format);
    }

public function showYear( $format = "Y" )
    {
        return $this->format($format);
    }

public function showMonth( $format = "m" )
    {
        return $this->format($format);
    }

static public function get()
    {
        return new GramcDateTime();
    }
}
