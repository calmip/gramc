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

/*
 *
 * une transition qui ajoute DAT_SESS_DEB si la session associée est déjà ACITF
 * utile quand un expert valide ou invalide une version trop tard
 * 
 */

namespace AppBundle\Workflow\Projet;

use AppBundle\Workflow\Transition;
use AppBundle\AppBundle;
use AppBundle\Utils\Functions;

//use AppBundle\Exception\WorkflowException;
//use AppBundle\Utils\Functions;

use AppBundle\Utils\Etat;
use AppBundle\Utils\Signal;
use AppBundle\Entity\Projet;
use AppBundle\Entity\Version;
use AppBundle\Workflow\Version\VersionWorkflow;

class DoubleProjetTransition extends ProjetTransition
{
    public function __construct( $etat, $signal = null )
    {
        parent::__construct( $etat, $signal );
        $this->etat                 =   (int)$etat;
        $this->signal               =   $signal;
    }

    public function __toString()
    {
        $reflect = new \ReflectionClass($this);
        return $reflect->getShortName().':etat=' . ($this->etat === null?'null':$this->etat)
                . ',signal_version=' . ($this->signal === null?'null':$this->signal);
    }

    ////////////////////////////////////////////////////
    
    public function canExecute($object)
    {
        if ( ! $object instanceof Projet ) return false;

        $rtn    =   parent::canExecute($object);
            
        $version = $object->getVersionDerniere();
        if( $version == null )
            {
            Functions::errorMessage("DoubleProjetTransition : version dernière null pour le projet " . $object);
            return false;
            }

        $session = $version->getSession();
        if( $session == null )
            {
            Functions::errorMessage("DoubleProjetTransition : session null pour la version " . $object);
            return false;
            }
            
        return $rtn;
    }

    ///////////////////////////////////////////////////////
    
    public function execute($object)
    {
        if ( ! $object instanceof Projet ) return false;
        
        $rtn = parent::execute( $object );
        
        $workflow = new VersionWorkflow();

        foreach( $object->getVersion() as $version )
            {
            $session = $version->getSession();
        
            if( $session == null )
                {
                Functions::errorMessage("DoubleProjetTransition : session null pour la version " . $object);
                return false;
                }
                
            if( $session->getEtatSession() == Etat::ACTIF )
                {
                $return = $workflow->execute( Signal::CLK_SESS_DEB, $version );
                if( $return == false )
                    $return = [[ 'signal' => Signal::CLK_SESS_DEB , 'object' => $version ]];
                $rtn = Functions::merge_return( $rtn, $return );
                Functions::sauvegarder( $version );
                }
            
            elseif( $session->getEtatSession() == Etat::ACTIF )
                Functions::errorMessage("DoubleProjetTransition : mauvais return de la fonction getLibelleTypeSession() pour la session "
                                        . $session  . "(" . Etat::getLibelle($session->getEtatSession() ) . ") type = '" . $session->getLibelleTypeSession() . "'");
            }

    return $rtn; 
    }

}
