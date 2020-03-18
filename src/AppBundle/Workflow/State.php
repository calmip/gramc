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
 * State - Une classe pour décrire l'ensemble des transitions possibles 
 *         à partir d'un état donné ($stateIdentifier)
 *         Les transitions acceptables sont dans le tableau $transitions, indexé par les signaux
 *         Les transitions sont des objets, leurs méthodes contiennent le code exécuté lors du changement d'état
 *             $transitions = [ signal1->transition1, signal2->transition2, ... ]
 * 
 *****************/
class State
{
    private $transitions      = [];
    private $stateIdentifier  = null;

	/***********
	 * Le constructeur
	 * 
	 * params:
	 * 		$stateIdentifier L'état de départ (un entier, cf. Utils/Etat.php)
	 *      $transitions     Un array de transitions (voir le format ci-dessus)
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
          
    //public function getTransitions()
    //{
    //    return $this->transitions;
    //}


    //public function getTransition($name)
    //{
      //  if( isset( $this->transitions[$name] ) )
      //      return $this->transitions[$name];
      //  else
      //      return null;
    //}

	/******************
	 * Existe-t-il une transition possible avec le signal $signal ?
	 * 
	 * params = $signal     Le signal
	 * return = true/false
	 **********************************/
    private function hasTransition($signal)
    {
        if( isset( $this->transitions[$signal] ) ) return true;
        else                                       return false;
    }

    /****************
     * La transition avec le signal peut-elle être exécutée sur l'objet $objet ?
     * 
     * params:
     *      $signal = Un identifiant de signal
     *      $object = Un objet 
     * 
     ********************************/
    public function canExecute($signal,$object)
    {
        if(  $this->hasTransition($signal))
        {
            return $this->transitions[$signal]->canExecute($object);
        }
        else
        {
            //echo ' State['.$this->stateIdentifier .'] signal ' . $signal . ' on ' . get_class ( $object ) . " n'existe pas "; 
            return false;
        }    
    }

    /****************
     * Exécute la transition $signal sur l'objet $objet
     * 
     * params:
     *      $signal = Un identifiant de signal
     *      $object = Un objet associé
     * 
     * return:
     * 		true  = transition ok
     * 		false = transition non effectuée, objet intact
     * 
     ********************************/
    public function execute($signal,$object)
    {
        if( $this->hasTransition($signal) )
        {
            //echo ' State['.$this->stateIdentifier .'] signal ' . $name . ' on ' . get_class ( $object ) . ' exécuté '; 
            //$msg = $this->transitions[$name] . ' -- on -- ' . $object;
            //AppBundle::getLogger()->info($msg);
            return $this->transitions[$signal]->execute($object);
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
