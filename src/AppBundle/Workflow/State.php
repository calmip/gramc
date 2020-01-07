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
use AppBundle\AppBundle;
use AppBundle\Utils\Signal;
use AppBundle\Utils\Etat;

/*****************
 * State - Une classe pour décrire des états d'objets, ainsi que les 
 *         transitions possibles à partir de ces objets
 *         Les transitions sont aussi des objets
 *         Les transitions acceptables à partir d'un objet donné se trouvent dans l'array protégé $transitions
 * 
 *****************/
class State
{
    protected $transitions      = [];
    protected $stateIdentifier  = null;

	/***********
	 * Le constructeur
	 * 
	 * params:
	 * 		$stateIdentifier L'état (un entier, cf. Utils/Etat.php)
	 *      $transitions     Un array de transitions
	 **********************/ 
    public function __construct($stateIdentifier, $transitions)
    {
        $this->stateIdentifier = $stateIdentifier;
        $this->transitions = $transitions;
    }

    public function __toString()
    {
        $output = " STATE{".Etat::getLibelle($this->stateIdentifier).' : ';
        foreach ( $this->transitions as $key => $value)
            $output .= ' ' . Signal::getLibelle($key) . ' => ('.$value.')';
        return $output . '}' ;   
    }

    //function addTransition($transitionConstant,$transitionObject)
    //{
        //$this->states[$transitionConstant] = $transitionObject;
    //}
          
    public function getTransitions()
    {
        return $this->transitions;
    }

    public function getTransition($name)
    {
        if( isset( $this->transitions[$name] ) )
            return $this->transitions[$name];
        else
            return null;
    }

    public function hasTransition($name)
    {
        if( isset( $this->transitions[$name] ) ) return true;
        else return false;
    }

    /****************
     * La transition $name peut-elle être exécutée sur l'objet $objet ?
     * 
     * params:
     *      $name   = Un nom (TODO - ??????????????) de transition
     *      $object = Un objet associé
     * 
     ********************************/
    public function canExecute($name,$object)
    {
        if(  $this->hasTransition($name))
        {
            //echo ' State['.$this->stateIdentifier .'] signal ' . $name . ' on ' . get_class ( $object ) . ' existe ';   
            return $this->transitions[$name]->canExecute($object);
        }
        else
        {
            //echo ' State['.$this->stateIdentifier .'] signal ' . $name . ' on ' . get_class ( $object ) . " n'existe pas "; 
            return false;
        }    
    }

    /****************
     * Exécute la transition $name sur l'objet $objet
     * 
     * params:
     *      $name   = Un nom (TODO - ??????????????) de transition
     *      $object = Un objet associé
     * 
     ********************************/
    public function execute($name,$object)
    {
        if( $this->hasTransition($name) )
        {
            //echo ' State['.$this->stateIdentifier .'] signal ' . $name . ' on ' . get_class ( $object ) . ' exécuté '; 
            //$msg = $this->transitions[$name] . ' -- on -- ' . $object;
            //AppBundle::getLogger()->info($msg);
            return $this->transitions[$name]->execute($object);
        }
        else
        {
            //echo ' State['.$this->stateIdentifier .'] signal ' . $name . ' on ' . get_class ( $object ) . ' ne peux pas être exécuté !!!!!!!!!!!! '; 
            return false;
        }    
    }
    
//    public function getStateIdentifier()
//    {
//        return $this->stateIdentifier();
//    }
}
