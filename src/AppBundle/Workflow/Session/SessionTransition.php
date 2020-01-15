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

namespace AppBundle\Workflow\Session;

use AppBundle\Workflow\Transition;
use AppBundle\AppBundle;
use AppBundle\Utils\Functions;

use AppBundle\Workflow\Projet\ProjetWorkflow;
use AppBundle\Workflow\Version\VersionWorkflow;

use AppBundle\Utils\Etat;
use AppBundle\Utils\Signal;
use AppBundle\Entity\Session;
use AppBundle\Entity\Projet;
use AppBundle\Entity\Version;


class SessionTransition extends Transition
{

    public function canExecute($session)
    {
		if ( !$session instanceof Session ) throw new \InvalidArgumentException;
		
		$rtn = true;
		if (Transition::FAST == false && $this->getPropageSignal())
		{
			// Propagation vers les versions
			$versions = AppBundle::getRepository(Version::class)->findBy( ['session' => $session] );
			$workflow = new VersionWorkflow();
			if( $versions == null && Transition::DEBUG)
			{
				Functions::debugMessage(__METHOD__ . ':' . __LINE__ . " aucune version pour la session " . $session );
			}

			foreach( $versions as $version )
			{
				$output = $workflow->canExecute( $this->getSignal(), $version );
				$rtn = Functions::merge_return( $rtn, $output );
				if( $output != true && Transition::DEBUG)
				{
					Functions::debugMessage(__METHOD__ . ':' . __LINE__ . " Version " . $version . "  ne passe pas en état "
						. Etat::getLibelle( $version->getEtatVersion() ) . " signal = " . signal::getLibelle( $this->getSignal() ));
				}
			}

			// PAS DE Propagation vers les projets
			/*
			$projets = AppBundle::getRepository(Projet::class)->findAll();
			$workflow = new ProjetWorkflow();
			foreach( $projets as $projet )
			{
				$output = $workflow->canExecute( $this->signal, $projet );
				// POUR 
				if( $output != true && Transition::DEBUG )
				{
					Functions::warningMessage(__METHOD__ . ':' . __LINE__ . " Projet " . $projet . "  ne passe pas en état "
						. Etat::getLibelle( $projet->getEtatProjet() ) . " signal = " . signal::getLibelle( $this->signal ));
				}
				$rtn = $rtn && $output;
				$rtn = Functions::merge_return( $rtn, $output );
			} */
		}
		return $rtn;
    }

    public function execute($session)
    {
		if ( !$session instanceof Session ) throw new \InvalidArgumentException;
		$rtn = true;

		// Si on ne peut pas remettre toutes les sessions php à zéro, renvoie false
		// La transition n'a pas eu lieu
		// Cela est une sécurité afin de s'assurer que personne ne reste connecté, ne sachant pas que la session
		// a changé d'état !
		if (Functions::clear_phpSessions()==false)
		{
			$rtn = false;
			Functions::errorMessage(__METHOD__ . ':' . __LINE__ . " clear_phpSessions renvoie false");
			return $rtn;
		}
		else
		{
			if (Transition::DEBUG) Functions::debugMessage( __FILE__ . ":" . __LINE__ . " nettoyage des sessions php" );
		}

		if( $this->getSignal() == null ) 
		{
			Functions::ErrorMessage( __FILE__ . ":" . __LINE__ . " signal null" );
			return false;
		}

		$versions = AppBundle::getRepository(Version::class)->findBy( ['session' => $session] );
		if ($this->getPropageSignal())
		{
			if (Transition::DEBUG) Functions::debugMessage( __FILE__ . ":" . __LINE__ . " propagation du signal ".$this->getSignal()." à ".count($versions)." versions");
	
			$workflow = new VersionWorkflow();
	
			// Propage le signal à toutes les versions qui dépendent de la session
			foreach( $versions as $version )
			{
				$output = $workflow->execute( $this->getSignal(), $version );
				$rtn = Functions::merge_return( $rtn, $output );
			}
		}
		
		// Si demandé, propage le signal à tous les projets qui dépendent de la session
	   /* if( $this->toProjets == true )
		{
			$projets = AppBundle::getRepository(Projet::class)->findAll();
			if (Transition::DEBUG) Functions::debugMessage( __FILE__ . ":" . __LINE__ . " propagation du signal ".$this->signal." à ".count($projets)." projets");
			$workflow = new ProjetWorkflow();
			foreach( $projets as $projet )
			{
				$output = $workflow->execute( $this->signal, $projet );
				$rtn = Functions::merge_return( $rtn, $output );
			}
		}
		else
		{
			if (Transition::DEBUG) Functions::debugMessage( __FILE__ . ":" . __LINE__ . " Pas de propagation du signal aux projets " );
		} */


		if (Transition::DEBUG)
		{
			$old_etat = $session->getEtatSession();
            $session->setEtatSession( $this->getEtat() );
            Functions::sauvegarder( $session );
			Functions::debugMessage( __FILE__ . ":" . __LINE__ . " La session " . $session->getIdSession() . " est passée de l'état " . $old_etat . " à " . $session->getEtatSession() . " suite au signal " . $this->getSignal());
		}
		else
		{
            $session->setEtatSession( $this->getEtat() );
            Functions::sauvegarder( $session );
		}
		return $rtn;
    }
}
