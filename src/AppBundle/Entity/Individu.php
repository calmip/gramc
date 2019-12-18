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

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;

use AppBundle\Utils\Functions;
use Symfony\Component\HttpFoundation\Request;

/**
 * Individu
 *
 * @ORM\Table(name="individu", uniqueConstraints={@ORM\UniqueConstraint(name="mail", columns={"mail"})}, indexes={@ORM\Index(name="id_labo", columns={"id_labo"}), @ORM\Index(name="id_statut", columns={"id_statut"}), @ORM\Index(name="id_etab", columns={"id_etab"})})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\IndividuRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Individu implements UserInterface, EquatableInterface
{

    const INCONNU       = 0;
    const POSTDOC       = 1;
    const ATER          = 2;
    const DOCTORANT     = 3;
    const ENSEIGNANT    = 11;
    const CHERCHEUR     = 12;
    const INGENIEUR     = 14;

/* LIBELLE DES STATUTS */
const LIBELLE_STATUT =
        [
        self::INCONNU     => 'INCONNU',
        self::POSTDOC     => 'Post-doctorant',
        self::ATER        => 'ATER',
        self::DOCTORANT   => 'Doctorant',
        self::ENSEIGNANT  => 'Enseignant',
        self::CHERCHEUR   => 'Chercheur',
        self::INGENIEUR   => 'Ingénieur'
        ];

/////////////////////////////////////////////////////////////////////////////////

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="creation_stamp", type="datetime", nullable=false)
     */
    private $creationStamp;

    /**
     * @var string
     *
     * @ORM\Column(name="nom", type="string", length=50, nullable=true)
     */
    private $nom;

    /**
     * @var string
     *
     * @ORM\Column(name="prenom", type="string", length=50, nullable=true)
     */
    private $prenom;

    /**
     * @var string
     *
     * @ORM\Column(name="mail", type="string", length=200, nullable=false)
     */
    private $mail;

    /**
     * @var boolean
     *
     * @ORM\Column(name="admin", type="boolean", nullable=false)
     */
    private $admin = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="sysadmin", type="boolean", nullable=false)
     */
    private $sysadmin = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="obs", type="boolean", nullable=false)
     */
    private $obs = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="expert", type="boolean", nullable=false)
     */
    private $expert = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="responsable", type="boolean", nullable=false)
     */
    private $responsable = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="collaborateur", type="boolean", nullable=false)
     */
    private $collaborateur = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="president", type="boolean", nullable=false)
     */
    private $president = false;

    /**
     * @var \AppBundle\Entity\Projet
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Statut")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_statut", referencedColumnName="id_statut")
     * })
     */
    private $statut;

    /**
     * @var boolean
     *
     * @ORM\Column(name="desactive", type="boolean", nullable=false)
     */
    private $desactive = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_individu", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idIndividu;

    /**
     * @var \AppBundle\Entity\Laboratoire
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Laboratoire",cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_labo", referencedColumnName="id_labo")
     * })
     */
    private $labo;

    /**
     * @var \AppBundle\Entity\Etablissement
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Etablissement")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_etab", referencedColumnName="id_etab")
     * })
     */
    private $etab;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Thematique", mappedBy="expert")
     */
    private $thematique;

    ///////////////////////////////////////

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="\AppBundle\Entity\Session", mappedBy="president")
     */
    private $session;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="\AppBundle\Entity\Sso", mappedBy="individu")
     */
    private $sso;



    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="\AppBundle\Entity\CollaborateurVersion", mappedBy="collaborateur")
     */
    private $collaborateurVersion;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="\AppBundle\Entity\CompteActivation", mappedBy="individu")
     */
    private $compteActivation;


    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="\AppBundle\Entity\Expertise", mappedBy="expert")
     */
    private $expertise;


    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Journal", mappedBy="individu")
     */
    private $journal;

    ///////////////////////////////////////////

    /**
    * @ORM\PrePersist
    */
    public function setInitialMajStamp()
    {
    $this->creationStamp = new \DateTime();
    }

    //////////////////////////////////////////

    public function __toString()
        {
        if(  $this->getPrenom() != null ||  $this->getNom() != null)
            return $this->getPrenom() . ' ' . $this->getNom();
        elseif( $this->getMail() != null )
            return $this->getMail();
        else
            return 'sans prénom, nom et mail';
        }

    ////////////////////////////////////////////////////////////////////////////

    /* Pour verifier que deux classes sont égales, utiliser cet interface et pas == ! */
    public function isEqualTo(UserInterface $user)
    {

        if ( $user == null || !$user instanceof Individu) return false;

        if ($this->idIndividu !== $user->getId())
            return false;
        else
            return true;

    }

    public function getId()         {   return $this->idIndividu;}
    public function getUsername()   {   return $this->idIndividu;}

    public function getSalt()       {   return null;}
    public function getPassword()   {   return null;}
    public function eraseCredentials(){}

    /* LES ROLES DEFINIS DANS L'APPLICATION
     *     - ROLE_DEMANDEUR = Peut demander des ressoureces - Le minimum
     *     - ROLE_ADMIN     = Peut paramétrer l'application et intervenir dans les projets ou le workflow
     *     - ROLE_OBS       = Peut tout observer, mais ne peut agir
     *     - ROLE_EXPERT    = Peut être affecté à un projet pour expertise
     *     - ROLE_PRESIDENT = Peut affecter les experts à des projets
     *     - ROLE_SYSADMIN  = Administrateur système, est observateur et reçoit certains mails
     *     - ROLE_ALLOWED_TO_SWITCH = Peut changer d'identité (kifkif admin - A supprimer ?)
     */
    public function getRoles()
    {

        $roles[] = 'ROLE_DEMANDEUR';

        if( $this->getAdmin() == true )
        {
            $roles[] = 'ROLE_ADMIN';
            $roles[] = 'ROLE_OBS';
            $roles[] = 'ROLE_ALLOWED_TO_SWITCH';
        }

        if( $this->getPresident() == true )
        {
            $roles[] = 'ROLE_PRESIDENT';
            $roles[] = 'ROLE_EXPERT';
        }
        elseif( $this->getExpert() == true )
            $roles[] = 'ROLE_EXPERT';

        if ( $this->getObs() == true )
        {
            $roles[] = 'ROLE_OBS';
        }

        if ( $this->getSysadmin() == true )
        {
            $roles[] = 'ROLE_SYSADMIN';
            $roles[] = 'ROLE_OBS';
        }

        return $roles;
    }

 //////////////////////////////////////////////////////////////////////////////////////////////////////


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->thematique = new \Doctrine\Common\Collections\ArrayCollection();
        $this->session = new \Doctrine\Common\Collections\ArrayCollection();
        $this->sso = new \Doctrine\Common\Collections\ArrayCollection();
        $this->collaborateurVersion = new \Doctrine\Common\Collections\ArrayCollection();
        $this->compteActivation = new \Doctrine\Common\Collections\ArrayCollection();
        $this->expertise = new \Doctrine\Common\Collections\ArrayCollection();
        $this->journal = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set creationStamp
     *
     * @param \DateTime $creationStamp
     *
     * @return Individu
     */
    public function setCreationStamp($creationStamp)
    {
        $this->creationStamp = $creationStamp;

        return $this;
    }

    /**
     * Get creationStamp
     *
     * @return \DateTime
     */
    public function getCreationStamp()
    {
        return $this->creationStamp;
    }

    /**
     * Set nom
     *
     * @param string $nom
     *
     * @return Individu
     */
    public function setNom($nom)
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Get nom
     *
     * @return string
     */
    public function getNom()
    {
        return $this->nom;
    }

    /**
     * Set prenom
     *
     * @param string $prenom
     *
     * @return Individu
     */
    public function setPrenom($prenom)
    {
        $this->prenom = $prenom;

        return $this;
    }

    /**
     * Get prenom
     *
     * @return string
     */
    public function getPrenom()
    {
        return $this->prenom;
    }

    /**
     * Set mail
     *
     * @param string $mail
     *
     * @return Individu
     */
    public function setMail($mail)
    {
        $this->mail = $mail;

        return $this;
    }

    /**
     * Get mail
     *
     * @return string
     */
    public function getMail()
    {
        return $this->mail;
    }

    /**
     * Set admin
     *
     * @param boolean $admin
     *
     * @return Individu
     */
    public function setAdmin($admin)
    {
        $this->admin = $admin;

        return $this;
    }

    /**
     * Set sysadmin
     *
     * @param boolean $sysadmin
     *
     * @return Individu
     */
    public function setSysadmin($sysadmin)
    {
        $this->sysadmin = $sysadmin;

        return $this;
    }

    /**
     * Set obs
     *
     * @param boolean $obs
     *
     * @return Individu
     */
    public function setObs($obs)
    {
        $this->obs = $obs;

        return $this;
    }

    /**
     * Get admin
     *
     * @return boolean
     */
    public function getAdmin()
    {
        return $this->admin;
    }

    /**
     * Get sysadmin
     *
     * @return boolean
     */
    public function getSysadmin()
    {
        return $this->sysadmin;
    }

    /**
     * Get obs
     *
     * @return boolean
     */
    public function getObs()
    {
        return $this->obs;
    }

    /**
     * Set expert
     *
     * @param boolean $expert
     *
     * @return Individu
     */
    public function setExpert($expert)
    {
        $this->expert = $expert;

        return $this;
    }

    /**
     * Get expert
     *
     * @return boolean
     */
    public function getExpert()
    {
        return $this->expert;
    }

    /**
     * Set responsable
     *
     * @param boolean $responsable
     *
     * @return Individu
     */
    public function setResponsable($responsable)
    {
		throw new Exception("setResponsable: METHODE ET CHAMP EN VOIE DE DISPARITION");

        $this->responsable = $responsable;

        return $this;
    }

    /**
     * Get responsable
     *
     * @return boolean
     */
    public function getResponsable()
    {
		throw new Exception("getResponsable: METHODE ET CHAMP EN VOIE DE DISPARITION");
        return $this->responsable;
    }

    /**
     * Set collaborateur
     *
     * @param boolean $collaborateur
     *
     * @return Individu
     */
    public function setCollaborateur($collaborateur)
    {
		throw new Exception("setCollaborateur: METHODE ET CHAMP EN VOIE DE DISPARITION");
        $this->collaborateur = $collaborateur;
        return $this;
    }

    /**
     * Get collaborateur
     *
     * @return boolean
     */
    public function getCollaborateur()
    {
		throw new Exception("getCollaborateur: METHODE ET CHAMP EN VOIE DE DISPARITION");
        return $this->collaborateur;
    }

    /**
     * Set president
     *
     * @param boolean $president
     *
     * @return Individu
     */
    public function setPresident($president)
    {
        $this->president = $president;
        return $this;
    }

    /**
     * Get president
     *
     * @return boolean
     */
    public function getPresident()
    {
        return $this->president;
    }

    /**
     * Set desactive
     *
     * @param boolean $desactive
     *
     * @return Individu
     */
    public function setDesactive($desactive)
    {
        $this->desactive = $desactive;

        return $this;
    }

    /**
     * Get desactive
     *
     * @return boolean
     */
    public function getDesactive()
    {
        return $this->desactive;
    }

    /**
     * Get idIndividu
     *
     * @return integer
     */
    public function getIdIndividu()
    {
        return $this->idIndividu;
    }

    /**
     * Set statut
     *
     * @param \AppBundle\Entity\Statut $statut
     *
     * @return Individu
     */
    public function setStatut(\AppBundle\Entity\Statut $statut = null)
    {
        $this->statut = $statut;

        return $this;
    }

    /**
     * Get statut
     *
     * @return \AppBundle\Entity\Statut
     */
    public function getStatut()
    {
        return $this->statut;
    }

    /**
     * Set labo
     *
     * @param \AppBundle\Entity\Laboratoire $labo
     *
     * @return Individu
     */
    public function setLabo(\AppBundle\Entity\Laboratoire $labo = null)
    {
        $this->labo = $labo;

        return $this;
    }

    /**
     * Get labo
     *
     * @return \AppBundle\Entity\Laboratoire
     */
    public function getLabo()
    {
        return $this->labo;
    }

    /**
     * Set etab
     *
     * @param \AppBundle\Entity\Etablissement $etab
     *
     * @return Individu
     */
    public function setEtab(\AppBundle\Entity\Etablissement $etab = null)
    {
        $this->etab = $etab;

        return $this;
    }

    /**
     * Get etab
     *
     * @return \AppBundle\Entity\Etablissement
     */
    public function getEtab()
    {
        return $this->etab;
    }

    /**
     * Add thematique
     *
     * @param \AppBundle\Entity\Thematique $thematique
     *
     * @return Individu
     */
    public function addThematique(\AppBundle\Entity\Thematique $thematique)
    {
        $this->thematique[] = $thematique;

        return $this;
    }

    /**
     * Remove thematique
     *
     * @param \AppBundle\Entity\Thematique $thematique
     */
    public function removeThematique(\AppBundle\Entity\Thematique $thematique)
    {
        $this->thematique->removeElement($thematique);
    }

    /**
     * Get thematique
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getThematique()
    {
        return $this->thematique;
    }

    /**
     * Add session
     *
     * @param \AppBundle\Entity\Session $session
     *
     * @return Individu
     */
    public function addSession(\AppBundle\Entity\Session $session)
    {
        $this->session[] = $session;

        return $this;
    }

    /**
     * Remove session
     *
     * @param \AppBundle\Entity\Session $session
     */
    public function removeSession(\AppBundle\Entity\Session $session)
    {
        $this->session->removeElement($session);
    }

    /**
     * Get session
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Add sso
     *
     * @param \AppBundle\Entity\Sso $sso
     *
     * @return Individu
     */
    public function addSso(\AppBundle\Entity\Sso $sso)
    {
        $this->sso[] = $sso;

        return $this;
    }

    /**
     * Remove sso
     *
     * @param \AppBundle\Entity\Sso $sso
     */
    public function removeSso(\AppBundle\Entity\Sso $sso)
    {
        $this->sso->removeElement($sso);
    }

    /**
     * Get sso
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSso()
    {
        return $this->sso;
    }



    /**
     * Add collaborateurVersion
     *
     * @param \AppBundle\Entity\CollaborateurVersion $collaborateurVersion
     *
     * @return Individu
     */
    public function addCollaborateurVersion(\AppBundle\Entity\CollaborateurVersion $collaborateurVersion)
    {
        $this->collaborateurVersion[] = $collaborateurVersion;

        return $this;
    }

    /**
     * Remove collaborateurVersion
     *
     * @param \AppBundle\Entity\CollaborateurVersion $collaborateurVersion
     */
    public function removeCollaborateurVersion(\AppBundle\Entity\CollaborateurVersion $collaborateurVersion)
    {
        $this->collaborateurVersion->removeElement($collaborateurVersion);
    }

    /**
     * Get collaborateurVersion
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCollaborateurVersion()
    {
        return $this->collaborateurVersion;
    }

    /**
     * Add compteActivation
     *
     * @param \AppBundle\Entity\CompteActivation $compteActivation
     *
     * @return Individu
     */
    public function addCompteActivation(\AppBundle\Entity\CompteActivation $compteActivation)
    {
        $this->compteActivation[] = $compteActivation;

        return $this;
    }

    /**
     * Remove compteActivation
     *
     * @param \AppBundle\Entity\CompteActivation $compteActivation
     */
    public function removeCompteActivation(\AppBundle\Entity\CompteActivation $compteActivation)
    {
        $this->compteActivation->removeElement($compteActivation);
    }

    /**
     * Get compteActivation
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCompteActivation()
    {
        return $this->compteActivation;
    }

    /**
     * Add expertise
     *
     * @param \AppBundle\Entity\Expertise $expertise
     *
     * @return Individu
     */
    public function addExpertise(\AppBundle\Entity\Expertise $expertise)
    {
        $this->expertise[] = $expertise;

        return $this;
    }

    /**
     * Remove expertise
     *
     * @param \AppBundle\Entity\Expertise $expertise
     */
    public function removeExpertise(\AppBundle\Entity\Expertise $expertise)
    {
        $this->expertise->removeElement($expertise);
    }

    /**
     * Get expertise
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getExpertise()
    {
        return $this->expertise;
    }

    /**
     * Add journal
     *
     * @param \AppBundle\Entity\Journal $journal
     * @return Individu
     */
    public function addJournal(\AppBundle\Entity\Journal $journal)
    {
        if (!$this->journal->contains($journal))
           $this->journal[] = $journal;

        return $this;
    }

    /**
     * Remove journal
     *
     * @param \AppBundle\Entity\Journal $journal
     */
    public function removeJournal(\AppBundle\Entity\Journal $journal)
    {
        $this->journal->removeElement($journal);
    }

    /**
      * Get journal
      *
      * @return \Doctrine\Common\Collections\Collection
      */
    public function getJournal()
    {
        return $this->journal;
    }

    ///////////////////////////////////////////////////////////////////////////

    public function getIDP()
    {
        return implode(',',$this->getSso()->toArray() );
    }

    public function getEtablissement()
    {
        $server =  Request::createFromGlobals()->server;
        if(  $server->has('REMOTE_USER') || $server->has('REDIRECT_REMOTE_USER') )
            {
            if( $server->has('REMOTE_USER') )           $eppn =  $server->get('REMOTE_USER');
            if( $server->has('REDIRECT_REMOTE_USER') )  $eppn =  $server->get('REDIRECT_REMOTE_USER');
            preg_match( '/^.+@(.+)$/', $$eppn, $matches );
            if( $matches[0] != null )
                return $matches[0];
            else
                Functions::warningMessage('Individu::getEtablissements user '. $this .' a un EPPN bizarre');
            }
        return 'aucun établissement connu';
    }

    public function isExpert()
    {
    return $this->expert;
    }

    ////

    public function isPermanent()
    {
    $statut = $this->getStatut();
    if( $statut != null && $statut->isPermanent() )
        return true;
    else
        return false;
    }

    public function isFromLaboRegional()
    {
    $labo = $this->getLabo();
    if( $labo != null && $labo->isLaboRegional() )
        return true;
    else
        return false;
    }

    ///

    public function getEppn()
    {
    $ssos = $this->getSso();
    $eppn = [];
    foreach( $ssos as $sso )
        $eppn[] =   $sso->getEppn();
    return $eppn;
    }

    ///

    public function peut_creer_projets()
    {
    if( $this->isPermanent() && $this->isFromLaboRegional() )
        return true;
    else
        return false;
    }
}
