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
use AppBundle\Workflow\Transition;

class MessageTransition extends Transition
{

    protected   $mail_roles;
    protected   $twig;
    
    public function __construct( $etat, $signal, $twig = null, $mail_roles = [] )
    {
        parent::__construct( $etat, $signal, null );
        $this->mail_roles   =   $mail_roles;
        $this->twig         =   $twig; 
    }

    public function __toString()
    {
        $output = 'MessageTransition:';
        $output .= 'etat=' . ($this->etat === null?'null':$this->etat);
        $output .= ($this->signal1 === null?'':',signal='.$this->signal1);
        $output .= ($this->twig == null?'':',twig='.$this->twig);
        $output .= ',mail_roles =['. implode(',', $this->mail_roles) . ']'; 
        return $output;
    }

    ///////////////////////////////////////////////////////
    
    public function execute($object)
    {
        $rtn =   parent::execute( $object );
        
        if( $this->twig == null || $this->mail_roles == [] ) return $rtn; 

        // uniquement envoi des notifications
                                                
        $param['object']                    =   $object;
        
        $param['destinataires']             =   Functions::mailUsers( $this->mail_roles, $object); // array class Individu
        $param['mail_destinataires' ]       =   Functions::usersToMail( $param['destinataires'] ); // array string email
        
        $param['liste_destinataires']       =   implode( ',', $param['destinataires'] );       // string noms & prenoms
        $param['liste_mail_destinataires' ] =   implode( ',', $param['mail_destinataires'] );  // string emails
    
        Functions::sendMessage( 'notification/' . $this->twig . '-sujet.html.twig',
                                'notification/' . $this->twig . '-contenu.html.twig',
                                $param,  $param['mail_destinataires' ] );
        return $rtn;
                
    }


}
