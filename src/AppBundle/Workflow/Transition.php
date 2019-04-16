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

use AppBundle\Workflow\TransitionInterface;
use AppBundle\AppBundle;

use AppBundle\Utils\Functions;
use AppBundle\Utils\Etat;
use AppBundle\Utils\Signal;


class Transition implements TransitionInterface
{
    protected   $etat            = null;
    protected   $signal1         = null;
    protected   $signal2         = null;
    
    public function __construct( $etat, $signal1 = null, $signal2 = null )
    {
        $this->etat             =   (int)$etat;
        $this->signal1          =   $signal1;
        $this->signal2          =   $signal2;

    }

    public function __toString()
    {
        $output = 'Transition:';
        $output .= 'etat=' . ($this->etat === null?'null':$this->etat);
        $output .= ($this->signal1 === null?'':',signal 1 ='.$this->signal1);
        $output .= ($this->signal2 === null?'':',signal 2 ='.$this->signal2);
        return $output;
    }

    ////////////////////////////////////////////////////
    
    public function canExecute($object)
    {
       if ( method_exists( $object, 'setObjectState' ) )
            {
            $rtn = true;
           
            if( $this->signal1 != null  )
                {
                if( ! method_exists( $object,'getSubObjects') || ! method_exists( $object,'getSubWorkflow') )
                    return false;
                    
                    $subObjects = $object->getSubObjects();
                    $workflow = $object->getSubWorkflow();
                    foreach( $subObjects as $subObject )
                        {
                        $result = $workflow->canExecute( $this->signal1, $subObject );
                        if( $result == false )  static::log( 'canExecute', $workflow , $subObject, $this->signal1);
                        $rtn = $rtn && $result;
                        }
                }
                
            if( $this->signal2 != null  )
                {
                if( ! method_exists( $object,'getSubObjects2') || ! method_exists( $object,'getSubWorkflow2') )
                    return false;

                    $subObjects = $object->getSubObjects2();
                    $workflow = $object->getSubWorkflow2();
                    foreach( $subObjects as $subObject )
                        {
                        $result = $workflow->canExecute( $this->signal2, $subObject );
                        if( $result == false )  static::log( 'canExecute', $workflow , $subObject, $this->signal2);
                        $rtn = $rtn && $result;
                        }
                }            
            return $rtn;
            
            }
        else
            {
            Functions::errorMessage(__METHOD__ . ":" . __LINE__ . " problème avec " . get_class($object).
                                    " sans setObjectState()" );
            return false;
            }        
                
    }

    ///////////////////////////////////////////////////////
    
    public function execute($object)
    {
       if ( method_exists( $object, 'setObjectState' ) )
            {
            $rtn = true;
            $object->setObjectState($this->etat);
           
            if( $this->signal1 != null  )
                {
                if( ! method_exists( $object,'getSubObjects') || ! method_exists( $object,'getSubWorkflow') )
                    return false;
                    
                    $subObjects = $object->getSubObjects();
                    $workflow = $object->getSubWorkflow();
                    foreach( $subObjects as $subObject )
                        {
                        $result = $workflow->execute( $this->signal1, $subObject );
                        if( $result == false )  static::log( 'execute', $workflow , $subObject, $this->signal1);
                        $rtn = $rtn && $result;
                        }
                }
                
            if( $this->signal2 != null  )
                {
                if( ! method_exists( $object,'getSubObjects2') || ! method_exists( $object,'getSubWorkflow2') )
                    return false;

                    $subObjects = $object->getSubObjects2();
                    $workflow = $object->getSubWorkflow2();
                    foreach( $subObjects as $subObject )
                        {
                        $result = $workflow->execute( $this->signal2, $subObject );
                        if( $result == false )  static::log( 'execute', $workflow , $subObject, $this->signal2);
                        $rtn = $rtn && $result;
                        }
                }            
            return $rtn;
            }
        else
            return false;        
                
    }

    /////////////////////////////////////////////////////////////////////

    protected static function log( $action, $workflow, $subObject, $signal )
    {
    if( ! method_exists( $subObject, 'getObjectState' ) )
        {
            Functions::errorMessage(__METHOD__ . ":" . __LINE__ . " problème avec " . get_class($workflow).
            " sur l'objet sans getObjectState()" );
        }
    else
        {
        if( method_exists( $subObject, '__toString' ) )
            $nom    =   "(" . $subObject->__toString() . ")";
        else
            $nom    = "";

        $reflect    = new \ReflectionClass($workflow);
        $workflow   =  $reflect->getShortName();

        $reflect    = new \ReflectionClass($subObject);
        $objet      =  $reflect->getShortName(); 

        if( array_key_exists( $signal, Signal::LIBELLE_SIGNAL ) )
            $libelleSignal  =  "(". Signal::LIBELLE_SIGNAL[$signal] .")";
        else
            $libelleSignal  =  '';

        $etat   =  $subObject->getObjectState();

        if( array_key_exists( $etat, Etat::LIBELLE_ETAT ) )
            $libelleEtat  =  "(" . Etat::LIBELLE_ETAT[$etat] . ")";
        else
            $libelleEtat  =  '';
        
        Functions::debugMessage(__METHOD__ . ":" . __LINE__ . " problème de " . $action . " dans " . $workflow .
            ' pour le signal ' . $signal . $libelleSignal . " sur ". $objet . $nom . " état " . $etat . $libelleEtat );
        }
    }

}
