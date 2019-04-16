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


namespace AppBundle\Controller;

use AppBundle\Entity\Individu;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManager;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use AppBundle\Utils\Etat;

use AppBundle\Utils\GramcDate;

//include_once(dirname(__FILE__).'/../../lib/etat_transition.php');

class InitController
{

    private $em;
    private $kernel = false;
    protected $sessions_non_terminees;
    protected $session_courante = null;
    protected $etat_session_courante;
    protected $libelle_etat_session_courante;
    protected $id_session_courante;

    public function __construct(EntityManager $em, $kernel)
    {
        $this->kernel = $kernel;
        $this->em = $em;
        // un bogue obscur de symfony (lié à la console)
        try
        {
            $this->sessions_non_terminees =
                $em->getRepository('AppBundle:Session')->get_sessions_non_terminees();

            if( isset( $this->sessions_non_terminees[0] ) )
                $this->session_courante = $this->sessions_non_terminees[0];

            if( $this->session_courante != null )
                {
                $this->etat_session_courante  =  $this->session_courante->getEtatSession();
                if( array_key_exists( $this->etat_session_courante, Etat::LIBELLE_ETAT ) )
                    $this->libelle_etat_session_courante = Etat::LIBELLE_ETAT[$this->etat_session_courante];
                else
                    $this->libelle_etat_session_courante = "UNKNOWN";
                $this->id_session_courante = $this->session_courante->getIdSession();
                }
        }
        catch ( \Exception $e)
        { };


    }

    function getLibelleEtatSessionCourante()
    {
        return $this->libelle_etat_session_courante;
    }

    function getSessionCourante()
        {
            return $this->session_courante;
        }


    function getEtatSessionCourante()
        {
            return $this->etat_session_courante;
        }

    public function sessions_non_terminees()
    {
        return $this->sessions_non_terminees;
    }

    public function mail_replace($mail)
    {
          return str_replace('@',' at ',$mail);
    }

   // just testing

    public function show()
    {
        $this->kernel->getContainer()->get('logger');
        //if ($this->kernel->getEnvironment() === 'dev' ) return var_dump($this->kernel) ;
        if( $this->sessions_non_terminees === null ) return 'NULL';
        return $this->libelle_etat_session_courante;
        return $this->sessions_non_terminees[0]->getIdSession();
    }


function gramc_date($format) {
    $date   = new GramcDate();

    if ($this->kernel->getEnvironment() === 'dev')
        { // symfony dbg
        if (isset($_SESSION['parametres']))
            {
                $decalage = $_SESSION['parametres']['decalage_date'];
            }
        elseif( $this->kernel->getContainer()->hasParameter('decalage_date') ) // symfony
            {
                $decalage = $this->kernel->getContainer()->getParameter('decalage_date');
            }
         else
            {
                $decalage = 0;
            }
        $datint = new \DateInterval('P'.abs($decalage).'D');

        if ($decalage > 0)
            {
                $date->add($datint);
            }
        else
            {
                $date->sub($datint);
            }
    } // if symfony dbg

    if ( $format == 'raw' )
        {
            return $date;
        }
    else
        {
            return $date->format($format);
        }
    } // function gramc_date

    function prochaine_session_saison()
    {
        $annee        = 2000 + intval(substr($this->id_session_courante,0,2));
        $type         = substr($this->id_session_courante,2,1);
        $mois_courant = intval($this->gramc_date('m'));
        $result['annee']=$annee;
        if ($type == 'A')
            {
            $result['type']='P';
            } else
            {
            $result['type']='A';
            }
        return $result;

    } //  function prochaine_session_saison()

    function strftime_fr($format,$date)
        {
            setlocale(LC_TIME,'fr_FR.UTF-8');
            return strftime($format,$date->getTimestamp());
        } // function strftime_fr

    function   tronquer_chaine($s,$l)
        {
        if (grapheme_strlen($s)>=intval($l))
            {
            return grapheme_substr($s,0,intval($l)).'...';
            }
        else
            {
            return $s;
            }
        }


    function cette_session()
        {
            $aujourdhui    = $this->gramc_date('raw');
            $fin_sess_date = $this->session_courante->getDateFinSession();
            $interval   = date_diff($aujourdhui,$fin_sess_date);
            $duree      = $interval->format('%R%a-%H');
            $jours      = intval($duree);
            return array( 'jours' => $jours, 'fin_sess' => $fin_sess_date->format("d/m/Y") );
        } // function cette_session()

    function prochaine_session()
        {
            return $this->session_courante->getDateDebutSession()->format("d/m/Y");
        } // function prochaine_session

} // class
