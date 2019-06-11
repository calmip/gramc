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

use AppBundle\Workflow\TransitionInterface;
use AppBundle\AppBundle;
use AppBundle\Utils\Functions;

use AppBundle\Workflow\Projet\ProjetWorkflow;
use AppBundle\Workflow\Version\VersionWorkflow;

//use AppBundle\Exception\WorkflowException;

use AppBundle\Utils\Etat;
use AppBundle\Utils\Signal;
use AppBundle\Entity\Session;
use AppBundle\Entity\Projet;
use AppBundle\Entity\Version;


class SessionTransition implements TransitionInterface
{
    protected   $etat                   = null;
    protected   $signal_projet          = null;
    protected   $toProjets              = false;


    public function __construct( $etat, $signal_projet = null, $toProjets = false )
    {
        $this->etat                 =   (int)$etat;
        $this->signal_projet        =   $signal_projet;
        $this->toProjets            =   $toProjets;
    }

    public function __toString()
    {
        $reflect = new \ReflectionClass($this);
        $output = $reflect->getShortName().':';
        $output .= 'etat=' . ($this->etat === null?'null':$this->etat) . ',';
        $output .= 'signal_projet=' . ($this->signal_projet === null?'null':$this->signal_projet);
        $output .= 'toProjets=' . ($this->toProjets === null?'null':$this->toProjets);
        return $output;
    }

    ////////////////////////////////////////////////////

    public function canExecute($object)
    {
       if ( $object instanceof Session )
            {
            $rtn = true;

            if( $this->signal_projet != null )
            {
                // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
                // nous devons envoyer des signaux aux versions avant les projets
                // car les projets tests se synchornisent avec leur version
                // cet ordre n'est pas important pour les projets ordinaires

                $versions = AppBundle::getRepository(Version::class)->findBy( ['session' => $object] );
                $workflow = new VersionWorkflow();

                // POUR DEBUG
                        if( $versions == null )
                        {
                        Functions::debugMessage(__METHOD__ . ':' . __LINE__ . " aucune version" );
                        }


                foreach( $versions as $version )
                {
                    $output = $workflow->canExecute( $this->signal_projet, $version );
                    $rtn = Functions::merge_return( $rtn, $output );
                    // POUR DEBUG
                        if( $output != true )
                        {
                        Functions::debugMessage(__METHOD__ . ':' . __LINE__ . " Version " . $version . "  ne passe pas en état "
                            . Etat::getLibelle( $version->getEtatVersion() ) . " signal = " . Signal::getLibelle( $this->signal_projet ));
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
                        $output = $workflow->canExecute( $this->signal_projet, $projet );
                        /* POUR DEBUG
                        if( $output != true )
                        {
                        Functions::warningMessage(__METHOD__ . ':' . __LINE__ . " Projet " . $projet . "  ne passe pas en état "
                            . Etat::getLibelle( $projet->getEtatProjet() ) . " signal = " . Signal::getLibelle( $this->signal_projet ));
                        }
                        */
                        $rtn = $rtn && $output;
                        $rtn = Functions::merge_return( $rtn, $output );
                        }
                    }
            }

            return $rtn;

            }
        else
            return false;

    }

    ///////////////////////////////////////////////////////

    public function execute($object)
    {
        Functions::debugMessage( __FILE__ . ":" . __LINE__ . " entrer dans execute" );

        if ( $object instanceof Session )
		{
            $rtn = true;

			// Si on ne peut pas remettre toutes les session à zéro, renvoie false
			// La transition n'a pas eu lieu
			// Cela est une sécurité afin de s'assurer que personne ne reste connecté, ne sachant pas que la session
			// a changé d'état !
			if (Functions::clear_phpSessions()==false)
			{
				$rtn = false;
				return $rtn;
			}
			else
			{
				Functions::debugMessage( __FILE__ . ":" . __LINE__ . " nettoyage des sessions php" );
			}

            Functions::debugMessage( __FILE__ . ":" . __LINE__ . " execute" );
            if( $this->signal_projet == null )
                Functions::debugMessage( __FILE__ . ":" . __LINE__ . " signal projet null" );

            if( $this->signal_projet != null )
			{
                $versions = AppBundle::getRepository(Version::class)->findBy( ['session' => $object] );
                $workflow = new VersionWorkflow();

                Functions::debugMessage( __FILE__ . ":" . __LINE__ . " size versions = " . count($versions) );

                foreach( $versions as $version )
				{

                    if( $version->getIdVersion() == "19AP17002" )
					{
                        Functions::debugMessage( __FILE__ . ":" . __LINE__ . " Version " .  $version->getIdVersion());
                        Functions::debugMessage( __FILE__ . ":" . __LINE__ . " Etat Version " .  $version->getIdVersion()  . " = "
                            . Etat::getLibelle( $version->getEtatVersion() ));
					}

                    $output = $workflow->execute( $this->signal_projet, $version );

                    if( $version->getIdVersion() == "19AP17002" )
					{
                        Functions::debugMessage( __FILE__ . ":" . __LINE__ . " output = " .  Functions::show( $output) );
                        Functions::debugMessage( __FILE__ . ":" . __LINE__ . " Etat Version " .  $version->getIdVersion()
                            . " = " . Etat::getLibelle( $version->getEtatVersion() ));
					}

                    $rtn = Functions::merge_return( $rtn, $output );
				}



                if( $this->toProjets == true )
				{
                    Functions::debugMessage( __FILE__ . ":" . __LINE__ . " session courante " );
                    $projets = AppBundle::getRepository(Projet::class)->findAll();
                    $workflow = new ProjetWorkflow();
                    foreach( $projets as $projet )
					{
                        if( $version->getIdVersion() == "T19002" )
                            Functions::debugMessage( __FILE__ . ":" . __LINE__ . " Projet T19002" );

                        $output = $workflow->execute( $this->signal_projet, $projet );
                        $rtn = $rtn && $output;
                        $rtn = Functions::merge_return( $rtn, $output );
					}
				}
                else
                    Functions::debugMessage( __FILE__ . ":" . __LINE__ . " $this->toProjets == false " );
			}

            $object->setEtatSession( $this->etat );
            Functions::sauvegarder( $object );
            Functions::debugMessage("SessionTransition : état est passé à " . $object->getEtatSession());
            return $rtn;

            }
        else
            return false;
    }
}
