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

namespace AppBundle\Workflow\Rallonge;

use AppBundle\Workflow\Transition;
use AppBundle\AppBundle;
use AppBundle\Utils\Functions;
use AppBundle\Utils\Etat;
use AppBundle\Utils\Signal;
use AppBundle\Entity\Rallonge;
use AppBundle\Workflow\Rallonge\RallongeWorkflow;


class RallongeTransition extends Transition
{
    protected   $etat                    = null;
    protected   $mail                    = [];
    
    public function __construct( $etat, $signal, $mail =[])
    {
        $this->etat   = (int)$etat;
        $this->signal = $signal;
        $this->mail   = $mail;
    }

    public function __toString()
    {
        $reflect = new \ReflectionClass($this);
        $output  = $reflect->getShortName().':etat='. Etat::getLibelle($this->etat);
        if( $this->mail != [] )
            $output .= ', mail=' .Functions::show($this->mail);
        return $output;
    }

    ////////////////////////////////////////////////////
    
    public function canExecute($object)
    {
       if ( $object instanceof Rallonge )
            return true;
        else
            return false;        
    }

    ///////////////////////////////////////////////////////
    
    public function execute($object)
    {
        if ( $object instanceof Rallonge )
		{
            if (Transition::DEBUG)
            {
				$old_etat = $object->getEtatRallonge();
	            $object->setEtatRallonge( $this->etat );
	            Functions::sauvegarder( $object );
				Functions::debugMessage( __FILE__ . ":" . __LINE__ . " La Rallonge " . $object->getIdRallonge() . " est passée de l'état " . $old_etat . " à " . $object->getEtatRallonge() . " suite au signal " . $this->signal);
			}
			else
			{
	            $object->setEtatRallonge( $this->etat );
	            Functions::sauvegarder( $object );
			}

            foreach( $this->mail as $mail_role => $template )
			{
                $users = Functions::mailUsers([$mail_role], $object);
                $params['object'] = $object;
                $params['liste_mail_destinataires'] =   implode( ',' , Functions::usersToMail( $users ) );
                Functions::sendMessage( 'notification/'.$template.'-sujet.html.twig','notification/'.$template.'-contenu.html.twig',
                     $params , $users );
			}  
            return true;
		}
        else
            return false;   
    }

}
