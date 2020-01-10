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

namespace AppBundle\Workflow\Projet;

use AppBundle\Workflow\Transition;
use AppBundle\AppBundle;
use AppBundle\Utils\Functions;

use AppBundle\Utils\Etat;
use AppBundle\Utils\Signal;

use AppBundle\Entity\Projet;
use AppBundle\Entity\Version;
use AppBundle\Workflow\Version\VersionWorkflow;



class ProjetTransition extends Transition
{
    protected   $etat                = null;
    protected   $signal              = null;
    private static $execute_en_cours = false;

    public function __construct( $etat, $signal, $mail=[], $propage_signal = false)
    {
        $this->etat                 =   (int)$etat;
        $this->signal               =   $signal;
        $this->propage_signal = $propage_signal;
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
        //if ( ! $object instanceof Projet ) throw new InvalidArgumentException;

		// Pour éviter une boucle infinie entre projet et version !
		if (self::$execute_en_cours) return true;
		else                         self::$execute_en_cours = true;
        $rtn    =   true;


        $versionWorkflow    =    new VersionWorkflow();
        foreach( $object->getVersion() as $version )
            if( $version->getEtatVersion() != Etat::TERMINE && $version->getEtatVersion() != Etat::ANNULE )
                {
                $output = $versionWorkflow->canExecute( $this->signal, $version );

                if( $output != true )
                            {
                            Functions::warningMessage(__METHOD__ . ':' . __LINE__ . " Version " . $version . "  ne passe pas en état "
                                . Etat::getLibelle( $version->getEtatVersion() ) . " signal = " . Signal::getLibelle( $this->signal ));
                            }

                $rtn = $rtn && $output;
                }

        return $rtn;
    }

    ///////////////////////////////////////////////////////
    // Transmet le signal aux versions du projet qui ne sont ni annulées ni terminées

    public function execute($object)
    {
		// Pour éviter une boucle infinie entre projet et version !
		if (self::$execute_en_cours) return true;
		else                         self::$execute_en_cours = true;
		
        if ( ! $object instanceof Projet ) return [[ 'signal' =>  $this->signal, 'object' => $object ]];

		//Functions::debugMessage(__METHOD__ .":" . __LINE__ . " Projet = " . $object->getIdProjet() . " transition de " . $object->getEtatProjet() . " vers " . $this->etat . " suite à signal " .$this->signal);
        $object->setEtatProjet( $this->etat );

        $rtn    =   true;
        if( $this->signal == null ) return $rtn;

		if ($this->propage_signal) 
		{
	        $versionWorkflow    =    new VersionWorkflow();
	        foreach( $object->getVersion() as $version )
	        {
	            if( $version->getEtatVersion() != Etat::TERMINE && $version->getEtatVersion() != Etat::ANNULE )
				{
	                $return = $versionWorkflow->execute( $this->signal, $version );
	
	                // ? if( $return == false )
	                // ?    $return = [[ 'signal' =>  $this->signal, 'object' => $version, 'user' => AppBundle::getUser() ]];
	
	                $rtn = Functions::merge_return( $rtn, $return );
				}
			}
		}
        Functions::sauvegarder( $object );

		self::$execute_en_cours = false;
        return $rtn;
    }

}
