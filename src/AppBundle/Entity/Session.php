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

namespace AppBundle\Entity;

use AppBundle\AppBundle;
use Doctrine\ORM\Mapping as ORM;

use AppBundle\Utils\Etat;
use AppBundle\Utils\Functions;

/**
 * Session
 *
 * @ORM\Table(name="session", indexes={@ORM\Index(name="etat_session", columns={"etat_session"}), @ORM\Index(name="id_president", columns={"id_president"})})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\SessionRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Session 
{
        
    /**
     * @var boolean
     *
     * @ORM\Column(name="type_session", type="boolean", nullable=false)
     */
    private $typeSession;

    /**
     * @var integer
     *
     * @ORM\Column(name="hparannee", type="integer", nullable=false)
     */
    private $hparannee;

    /**
     * @var string
     *
     * @ORM\Column(name="comm_global", type="text", length=65535, nullable=true)
     */
    private $commGlobal;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_debut_session", type="date", nullable=false)
     */
    private $dateDebutSession;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_fin_session", type="date", nullable=true)
     */
    private $dateFinSession;

    /**
     * @var integer
     *
     * @ORM\Column(name="etat_session", type="integer", nullable=false)
     */
    private $etatSession;

    /**
     * @var string
     *
     * @ORM\Column(name="id_session", type="string", length=3)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $idSession;

    /**
     * @var \AppBundle\Entity\Individu
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Individu",cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_president", referencedColumnName="id_individu")
     * })
     */
    private $president;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="\AppBundle\Entity\Version", mappedBy="session")
     */
    private $version;

    
    ////////////////////////////////////////////////////////////////
    
    public function __toString(){ return $this->getIdSession(); }
    public function getId(){ return $this->getIdSession(); }

    ///////////////////////////////////////////////////////////////
    
    /**
    * @ORM\PostUpdate
    * @ORM\PostPersist
    */
    public function clearCacheSessionCourante()
    {
        if( AppBundle::getSession()->has('SessionCourante') )
                AppBundle::getSession()->remove('SessionCourante'); // clear cache
    }
    /////////////////////////
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->version = new \Doctrine\Common\Collections\ArrayCollection();
        $this->projetTest = new \Doctrine\Common\Collections\ArrayCollection();
        $this->etatSession = Etat::getEtat('CREE_ATTENTE');
    }

    /**
     * Set typeSession
     *
     * @param boolean $typeSession
     *
     * @return Session
     */
    public function setTypeSession($typeSession)
    {
        $this->typeSession = $typeSession;

        return $this;
    }

    /**
     * Get typeSession
     *
     * @return boolean
     */
    public function getTypeSession()
    {
        return $this->typeSession;
    }

    /**
     * Set hparannee
     *
     * @param integer $hparannee
     *
     * @return Session
     */
    public function setHParAnnee($hparannee)
    {
        $this->hparannee = $hparannee;

        return $this;
    }

    /**
     * Get hparannee
     *
     * @return integer
     */
    public function getHParAnnee()
    {
        return $this->hparannee;
    }

    /**
     * Set commGlobal
     *
     * @param string $commGlobal
     *
     * @return Session
     */
    public function setCommGlobal($commGlobal)
    {
        $this->commGlobal = $commGlobal;

        return $this;
    }

    /**
     * Get commGlobal
     *
     * @return string
     */
    public function getCommGlobal()
    {
        return $this->commGlobal;
    }

    /**
     * Set dateDebutSession
     *
     * @param \DateTime $dateDebutSession
     *
     * @return Session
     */
    public function setDateDebutSession($dateDebutSession)
    {
        $this->dateDebutSession = $dateDebutSession;

        return $this;
    }

    /**
     * Get dateDebutSession
     *
     * @return \DateTime
     */
    public function getDateDebutSession()
    {
        return $this->dateDebutSession;
    }

    /**
     * Set dateFinSession
     *
     * @param \DateTime $dateFinSession
     *
     * @return Session
     */
    public function setDateFinSession($dateFinSession)
    {
        $this->dateFinSession = $dateFinSession;

        return $this;
    }

    /**
     * Get dateFinSession
     *
     * @return \DateTime
     */
    public function getDateFinSession()
    {
        return $this->dateFinSession;
    }

    /**
     * Set etatSession
     *
     * @param integer $etatSession
     *
     * @return Session
     */
    public function setEtatSession($etatSession)
    {
        $this->etatSession = $etatSession;

        return $this;
    }

    /**
     * Get etatSession
     *
     * @return integer
     */
    public function getEtatSession()
    {
        return $this->etatSession;
    }

    /**
     * Set idSession
     *
     * @param string $idSession
     *
     * @return Session
     */
    public function setIdSession($idSession)
    {
        $this->idSession = $idSession;

        return $this;
    }

    /**
     * Get idSession
     *
     * @return string
     */
    public function getIdSession()
    {
        return $this->idSession;
    }

    /**
     * Set president
     *
     * @param \AppBundle\Entity\Individu $president
     *
     * @return Session
     */
    public function setPresident(\AppBundle\Entity\Individu $president = null)
    {
        $this->president = $president;

        return $this;
    }

    /**
     * Get president
     *
     * @return \AppBundle\Entity\Individu
     */
    public function getPresident()
    {
        return $this->president;
    }

    /**
     * Add version
     *
     * @param \AppBundle\Entity\Version $version
     *
     * @return Session
     */
    public function addVersion(\AppBundle\Entity\Version $version)
    {
        $this->version[] = $version;

        return $this;
    }

    /**
     * Remove version
     *
     * @param \AppBundle\Entity\Version $version
     */
    public function removeVersion(\AppBundle\Entity\Version $version)
    {
        $this->version->removeElement($version);
    }

    /**
     * Get version
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getVersion()
    {
        return $this->version;
    }

    

    //////////////////////////////////////////

    public function getLibelleEtat()
    {
        return Etat::getLibelle( $this->getEtatSession() );
    }

    // pour TWIG
    public function getHeuresAttribuees()
    {
        $heures = 0;
        foreach ( $this->getVersion() as $version )
            $heures += $version->getAttrHeures();
        return $heures;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////

    // pour le workflow symfony

    public function setLibelleEtat($libelle)
    {
        $etat = Etat::getEtat( $libelle );

        if( $etat != null )
            $this->setEtatSession( $etat );
        else
            {
            Functions::warningMessage("libelleEtat de la Session '" . $libelle . "' n'existe pas");
            $this->setEtatSession( null  );
            }
            
        return $this;
    }

    public function getObjectState()
    {
        return $this->getEtatSession();
    }
    
    public function setObjectState($state)
    {
        $this->setEtatSession($state);
        return $this;
    }

    public function getSubWorkflow()        { return new \AppBundle\Workflow\ProjetWorkflow(); }
    public function getSubObjects()         { return AppBundle::getRepository(Projet::class)->findNonTermines();  }
    
    
    ///////////////////////////////////////
    
    // type session A ou B
    public  function getLibelleTypeSession()
    {
        $lettre = substr($this->getIdSession(),-1);
        if( $this->getTypeSession() == false && $lettre == 'A' ) return 'A';
        elseif( $this->getTypeSession() == true && $lettre == 'B' ) return 'B';
        Functions::warningMessage("Session: incohérence entre IdSession et TypeSession pour la session ".$this->getIdSession());
        return $lettre;
    }

    // juste les deux derniers chiffres de l'année
    public  function getAnneeSession()
    {
        return intval(substr($this->getIdSession(),0,-1));
    }
    
    // id Session A
    public function getIdSessionPrincipale()
    {
        return $this->getAnneeSession() . 'A';
    }
    // id Session A Prochaine
    // fonctionne depuis 2010 jsuqu'au 2098 !
    public function getIdSessionPrincipaleProchaine()
    {
        return ($this->getAnneeSession() + 1) . 'A';
    }
}
