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

use AppBundle\Utils\Functions;
use AppBundle\Utils\Etat;

/***********************************************************************************************
 * Workflow - Implémente des changements d'état entre objets
 *            Workflow est une classe abstraite, seules ses classes dérivées sont utilisables
 *            Il y a une classe dérivée par type d'objet (Projet, Version, Session, Rallonge)
 *            Workflow contient un tableau d'objets de type State décrivant les états de l'objet
 * 
 **********************************************************************/
abstract class Workflow 
{
    protected $states             = [];	// Contient les objets State encapsulés par ce workflow
    protected $workflowIdentifier = null;

	/*************************
	 * Le constructeur = Sera surchargé par les classes dérivées afin 
	 * de mettre les transitions possibles
	 ******************************************/
    public function __construct()
    {
        $this->workflowIdentifier = get_class($this);
    }

    public function getIdentifier()
    {
        return $this->workflowIdentifier;
    }

    public  function getWorkflowIdentifier()
    {
        $reflect = new \ReflectionClass($this);
        return $reflect->getShortName();
    }

	/***********************************************
	 * Renvoie l'état de l'objet $object sous forme numérique (cf. Utils/Etat.php)
	 ************************************************************************/
    protected function getObjectState($object)
    {
        if( $object == null )
           AppBundle::errorMessage(__METHOD__  . ":" . __LINE__ . " getObjectState on object null"); 
        elseif( method_exists($object, 'getObjectState' )  )
            return $object->getObjectState( $this->workflowIdentifier );
        else
            AppBundle::errorMessage(__METHOD__ . ":" . __LINE__ . " getObjectState n'existe pas pour la class ". get_class($object) );
     }

    /************************************************* 
     * Ajoute un état avec ses transitions - Appelé par les constructeurs des classes filles
     * 
     * params: $stateConstant Un entier représentant l'état
     * params: $transition_array Tableau associatif représentant les transitions possibles
     *           key est un Entier représentant le signal (cf. Utils/Signal.php)
     *           val est un objet qui implémente TransitionInterface
     * 
     *******************************************************************/
    protected function addState($stateConstant,$transition_array)
    {
        $this->addStateObject( $stateConstant, new State( $stateConstant, $transition_array) );
        return $this;
    }

    /*************************************************
     * Crée un objet State avec ses transitions, et l'ajoute au workflow
     * Fonction privée appelée par addState
     * 
     * params: $stateConstant l'entier représentant l'état
     * params: $stateObject L'état (objet State)
     * 
     ***/
    private function addStateObject($stateConstant,State $stateObject)
    {
        $this->states[$stateConstant] = $stateObject;
    }

	/*************************************************
	 * Renvoie l'objet $state associé à $stateConstant
	 * Fonction privée appelée par execute
	 * 
	 * params: $stateConstant Nombre entier représentant un état (cf. Utils/Etat.php)
	 * 
	 * Return: le $state ou null s'il n'existe pas
	 *************************************************/
    public  function getState($stateConstant)
    {
        if( isset( $this->states[$stateConstant] ))
            return $this->states[$stateConstant];
        else
			return null;    
    }    

	/**************************************************
	 * Execute la transition $transition_code sur l'object $object
	 * 
     * params: $transition_code Un signal (entier), cf Utils/Signal.php
     *         $object Un objet sur lequel agit le workflow
     * 
     * return: true si l'état est dans le workflow et si la transition est possible
     *         false sinon
     **************************************************************/
    public function execute($transition_code, $object) 
    {
        if( $object == null )
		{
            Functions::warningMessage(__METHOD__ ." on a null object dans " . $workflowIdentifier );
            return  false;
		}
        $state = $this->getObjectState($object); 

        if ( $this->hasState( $state )) return $this->getState($state)->execute($transition_code,$object);
        else 
        {
            Functions::warningMessage(__METHOD__ .  ":" . __LINE__ . " état " . Etat::getLibelle( $state )
                    . "(" . $state . ") n'existe pas dans " . $this->getWorkflowIdentifier() );
            return false;
        }
    }

    /***************************************************
     * Renvoie true/false selon que la transition est possible ou pas
     * 
     * params: $transition_code Un signal (entier), cf Utils/Signal.php
     *         $object Un objet sur lequel agit le workflow
     * 
     * return: true si l'état est dans le workflow et si la transition est possible
     *         false sinon
     **************************************************************/
    public function canExecute($transition_code,$object)
    {
        $state = $this->getObjectState($object);
        if ($this->hasState($state)) return $this->states[$state]->canExecute($transition_code,$object);
        else                         return false;
    }

    public function hasState( $state )
    {
        return isset( $this->states[$state] );
    }

    public function __toString()
    {
        $output = "workflow(" . $this->getWorkflowIdentifier() . ":";
        foreach ( $this->states as $state ) $output .= $state->__toString().',';
        return $output . ")";

    }
}    
    
