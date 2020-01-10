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
use AppBundle\Utils\Etat;
use AppBundle\Utils\Signal;
use AppBundle\Entity\Version;
use AppBundle\Workflow\Rallonge\RallongeWorkflow;
use AppBundle\Workflow\Projet\ProjetWorkflow;


class VersionTransition implements TransitionInterface
{
    protected   $etat                    = null;
    protected   $signal_rallonge         = null;
    protected   $propage_signal          = false;
    protected   $mail                    = [];
    private static $execute_en_cours     = false;
    
    public function __construct( $etat, $signal, $mail=[], $propage_signal = false)
    {
        $this->etat            = (int)$etat;
        $this->signal          = $signal;
        $this->mail            = $mail;
        $this->propage_signal  = $propage_signal;
    }

    public function __toString()
    {
        $reflect    = new \ReflectionClass($this);
        $output = $reflect->getShortName().':etat='. Etat::getLibelle($this->etat);
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
            if (TransitionInterface::FAST == false && $propage_signal )
			{
				$rallonges = $object->getRallonge();
				if( $rallonges != null )
				{
					$workflow = new RallongeWorkflow();
					foreach( $rallonges as $rallonge )
					{
						$rtn = $rtn && $workflow->canExecute( $this->signal, $rallonge );
					}
                }
			}
            return $rtn;
		}
        else
        {
            return false;        
		}
    }

    public function execute($object)
    {
		Functions::debugMessage( __FILE__ . ":" . __LINE__ . " coucou version " . $object->getIdVersion() . " état " . $object->getEtatVersion() . " à " . $this->etat . " signal " . $this->signal);

        if ( $object instanceof Version )
        {
			// Pour éviter une boucle infinie entre projet et version !
			if (self::$execute_en_cours) return true;
			else                         self::$execute_en_cours = true;
			
            $rtn = true;

			// Propage le signal aux rallonges si demandé
			if ($this->propage_signal)
			{
				$rallonges = $object->getRallonge();
	
				if (count($rallonges) > 0)
				{
	                $workflow = new RallongeWorkflow();
	
					// Propage le signal à toutes les rallonges qui dépendent de cette version
	                foreach( $rallonges as $rallonge )
					{
	                    $output = $workflow->execute( $this->signal, $rallonge );
	                    $rtn = Functions::merge_return( $rtn, $output );
					}
				}
			}

			// Propage le signal au projet si demandé
			if ($this->propage_signal)
			{
				$projet = $object->getProjet();
				$workflow = new ProjetWorkflow();
				$output   = $workflow->execute( $this->signal, $projet);
				$rtn = Functions::merge_return( $rtn, $output);
			}
            if (TransitionInterface::DEBUG)
            {
				$old_etat = $object->getEtatVersion();
	            $object->setEtatVersion( $this->etat );
	            Functions::sauvegarder( $object );
				Functions::debugMessage( __FILE__ . ":" . __LINE__ . " La version " . $object->getIdVersion() . " est passée de l'état " . $old_etat . " à " . $object->getEtatVersion() . " suite au signal " . $this->signal);
			}
			else
			{
	            $object->setEtatVersion( $this->etat );
	            Functions::sauvegarder( $object );
			}
	
			// Envoi des notifications demandées
            foreach( $this->mail as $mail_role => $template )
            {
                $users = Functions::mailUsers([$mail_role], $object);
                //Functions::debugMessage(__METHOD__ .":" . __LINE__ . " mail_role " . $mail_role . " users : " . Functions::show($users) );
                $params['object'] = $object;
                $params['liste_mail_destinataires'] =   implode( ',' , Functions::usersToMail( $users ) );
                Functions::sendMessage( 'notification/'.$template.'-sujet.html.twig','notification/'.$template.'-contenu.html.twig',
                     $params , $users );
            }
			self::$execute_en_cours = false;
            return $rtn;
        }
        else
        {
            return false;
		}
    }
}
