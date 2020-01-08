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

namespace AppBundle\Workflow\Version;

use AppBundle\Workflow\TransitionInterface;
use AppBundle\AppBundle;
use AppBundle\Utils\Functions;

//use AppBundle\Exception\WorkflowException;
//use AppBundle\Utils\Functions;

use AppBundle\Utils\Etat;
use AppBundle\Utils\Signal;
use AppBundle\Entity\Version;
use AppBundle\Workflow\Rallonge\RallongeWorkflow;


class VersionTransition implements TransitionInterface
{
    protected   $etat                    = null;
    protected   $signal_rallonge         = null;
    protected   $mail                    = [];
    
    //
    // si $signal_rallonge est un array il est considéré comme $mail et $signal_rallonge = null
    //

    public function __construct( $etat, $signal_rallonge = null, $mail =[])
    {
        $this->etat                 =   (int)$etat;
        if( is_array( $signal_rallonge ) )
            {
            $this->signal_rallonge  =   null;
            $this->mail             =   $signal_rallonge;
            }
        else
            {
            $this->signal_rallonge  =   $signal_rallonge;
            $this->mail             =   $mail;
            }
    }

    public function __toString()
    {
        $reflect    = new \ReflectionClass($this);
        $output = $reflect->getShortName().':etat='. Etat::getLibelle($this->etat);
        if( $this->signal_rallonge != null )
            $output .= ',signal_ralonge=' . Signal::getLibelle($this->signal_rallonge);
        if( $this->mail != [] )
            $output .= ', mail=' .Functions::show($this->mail);
        return $output;
    }

    ////////////////////////////////////////////////////
    
    public function canExecute($object)
    {
       if ( $object instanceof Version )
            {
            $rtn = true;
           /*
            if( $this->signal_rallonge != null )
                {
                    $rallonges = $object->getRallonge();
                    if( $rallonges != null )
                        {
                        $workflow = new RallongeWorkflow();
                        
                        foreach( $rallonges as $rallonge )
                            $rtn = $rtn && $workflow->canExecute( $this->signal_rallonge, $rallonge );
                        }
                }*/
            return $rtn;
            
            }
        else
            return false;        
    }

    ///////////////////////////////////////////////////////
    
    public function execute($object)
    {
        if ( $object instanceof Version )
        {
			// Functions::debugMessage(__METHOD__ .":" . __LINE__ . " Version = " . $object->getIdVersion() . "transition de " . $object->getEtatVersion() . " vers " . $this->etat );
            $rtn = true;
            $object->setEtatVersion($this->etat);

            foreach( $this->mail as $mail_role => $template )
            {
                $users = Functions::mailUsers([$mail_role], $object);
                //Functions::debugMessage(__METHOD__ .":" . __LINE__ . " mail_role " . $mail_role . " users : " . Functions::show($users) );
                $params['object'] = $object;
                $params['liste_mail_destinataires'] =   implode( ',' , Functions::usersToMail( $users ) );

                //Functions::debugMessage(__METHOD__ ." : ". Functions::show($params['liste_mail_destinataires']) );
                
                Functions::sendMessage( 'notification/'.$template.'-sujet.html.twig','notification/'.$template.'-contenu.html.twig',
                     $params , $users );
            }
            Functions::sauvegarder( $object );   
            return $rtn;
        }
        else
            return false;   
    }

}
