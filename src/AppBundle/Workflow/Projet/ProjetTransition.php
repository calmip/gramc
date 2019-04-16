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

use AppBundle\Workflow\TransitionInterface;
use AppBundle\AppBundle;
use AppBundle\Utils\Functions;

//use AppBundle\Exception\WorkflowException;
//use AppBundle\Utils\Functions;

use AppBundle\Utils\Etat;
use AppBundle\Utils\Signal;

use AppBundle\Entity\Projet;
use AppBundle\Entity\Version;
use AppBundle\Workflow\Version\VersionWorkflow;



class ProjetTransition implements TransitionInterface
{
    protected   $etat                               = null;
    protected   $signal                             = null;
    
    public function __construct( $etat, $signal = null )
    {
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

        $rtn    =   true;
        if( $this->signal == null ) return $rtn;
        
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
    
    public function execute($object)
    {
        if ( ! $object instanceof Projet ) return [[ 'signal' =>  $this->signal, 'object' => $object ]];
        
        $object->setEtatProjet( $this->etat );
        
        $rtn    =   true;
        if( $this->signal == null ) return $rtn;

        $versionWorkflow    =    new VersionWorkflow();
        
        foreach( $object->getVersion() as $version )
            if( $version->getEtatVersion() != Etat::TERMINE && $version->getEtatVersion() != Etat::ANNULE )
                {
                $return = $versionWorkflow->execute( $this->signal, $version );

                if( $return == false )
                    $return = [[ 'signal' =>  $this->signal, 'object' => $version, 'user' => AppBundle::getUser() ]];

                $rtn = Functions::merge_return( $rtn, $return ); 
                }
        //AppBundle::getManager()->persist( $object );
        //AppBundle::getManager()->flush();
        Functions::sauvegarder( $object );

        return $rtn; 
    }

}
