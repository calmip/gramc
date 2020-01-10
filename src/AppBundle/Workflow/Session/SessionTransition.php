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
    private $etat      = null;
    private $signal    = null;
    private $toProjets = false;

	/*******************************************************
	 * Constructeur
	 * 
	 * params:  int  $etat      = L'état de destination
	 *          int  $signal    = Le signal associé à la transition
	 *          bool $toProjets = Si true, le signal sera propagé aux projets de la session
	 ***************************************************/       
    public function __construct( $etat, $signal, $toProjets = false )
    {
        $this->etat      = (int)$etat;
        $this->signal    = $signal;
        $this->toProjets = $toProjets;
    }

    public function __toString()
    {
        $reflect = new \ReflectionClass($this);
        $output = $reflect->getShortName().':';
        $output .= 'etat=' . ($this->etat === null?'null':$this->etat) . ',';
        $output .= 'signal=' . ($this->signal === null?'null':$this->signal);
        $output .= 'toProjets=' . ($this->toProjets === null?'null':$this->toProjets);
        return $output;
    }

    public function canExecute($object)
    {
		if ( $object instanceof Session )
		{
			$rtn = true;

			// TODO - VIRER TOUT CE BORDEL PAS LA PEINE DE FAIRE DE LA PROPAGATION POUR canExecute !!!
            if( Transition::FAST == false)
            {
                // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
                // nous devons envoyer des signaux aux versions avant les projets
                // car les projets tests se synchornisent avec leur version
                // cet ordre n'est pas important pour les projets ordinaires
                $versions = AppBundle::getRepository(Version::class)->findBy( ['session' => $object] );
                $workflow = new VersionWorkflow();

				if( $versions == null && Transition::DEBUG)
				{
					Functions::debugMessage(__METHOD__ . ':' . __LINE__ . " aucune version pour la session " . $object );
				}

                foreach( $versions as $version )
                {
                    $output = $workflow->canExecute( $this->signal, $version );
                    $rtn = Functions::merge_return( $rtn, $output );
					if( $output != true && Transition::DEBUG)
					{
                        Functions::debugMessage(__METHOD__ . ':' . __LINE__ . " Version " . $version . "  ne passe pas en état "
                            . Etat::getLibelle( $version->getEtatVersion() ) . " signal = " . signal::getLibelle( $this->signal ));
					}
                }

                // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
                // les signaux envoyés par une session aux projets ne doivent pas être propagés aux versions
                // les projets test ne feront que synchroniser leur état avec leur propre version,
                // c'est pourquoi la version du projet test doit changer son état avant le projet test


                if( $this->toProjets == true )
				{
                    $projets = AppBundle::getRepository(Projet::class)->findAll();
                    $workflow = new ProjetWorkflow();
                    foreach( $projets as $projet )
					{
                        $output = $workflow->canExecute( $this->signal, $projet );
                        /* POUR DEBUG */
                        if( $output != true && Transition::DEBUG )
                        {
	                        Functions::warningMessage(__METHOD__ . ':' . __LINE__ . " Projet " . $projet . "  ne passe pas en état "
	                            . Etat::getLibelle( $projet->getEtatProjet() ) . " signal = " . signal::getLibelle( $this->signal ));
                        }
                        $rtn = $rtn && $output;
                        $rtn = Functions::merge_return( $rtn, $output );
					}
				}
            }
            return $rtn;
		}
        else
        {
			Functions::errorMessage(__METHOD__ . ':' . __LINE__ . " L'objet passé en paramètres n'est pas une instance de Session");
            return false;
		}

    }

    public function execute($object)
    {
        if ( $object instanceof Session )
		{
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

            if( $this->signal == null ) 
            {
                Functions::ErrorMessage( __FILE__ . ":" . __LINE__ . " signal null" );
			}
			else
			{
                $versions = AppBundle::getRepository(Version::class)->findBy( ['session' => $object] );
                if (Transition::DEBUG) Functions::debugMessage( __FILE__ . ":" . __LINE__ . " propagation du signal ".$this->signal." à ".count($versions)." versions");

                $workflow = new VersionWorkflow();

				// Propage le signal à toutes les versions qui dépendent de la session
                foreach( $versions as $version )
				{
                    $output = $workflow->execute( $this->signal, $version );
                    $rtn = Functions::merge_return( $rtn, $output );
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
			}

            if (Transition::DEBUG)
            {
				$old_etat = $object->getEtatSession();
	            $object->setEtatSession( $this->etat );
	            Functions::sauvegarder( $object );
				Functions::debugMessage( __FILE__ . ":" . __LINE__ . " La session " . $object->getIdSession() . " est passée de l'état " . $old_etat . " à " . $object->getEtatSession() . " suite au signal " . $this->signal);
			}
			else
			{
	            $object->setEtatSession( $this->etat );
	            Functions::sauvegarder( $object );
			}
            return $rtn;
		}
        else
        {
			Functions::errorMessage(__METHOD__ . ':' . __LINE__ . " L'objet passé en paramètres n'est pas une instance de Session");
            return false;
		}
    }
}
