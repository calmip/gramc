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
use AppBundle\AppBundle;
use AppBundle\Utils\Etat;
use AppBundle\Utils\Functions;

use AppBundle\Politique\Politique;

/**
 * Version
 *
 * @ORM\Table(name="version", indexes={@ORM\Index(name="etat_version", columns={"etat_version"}), @ORM\Index(name="id_session", columns={"id_session"}), @ORM\Index(name="id_projet", columns={"id_projet"}), @ORM\Index(name="prj_id_thematique", columns={"prj_id_thematique"})})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\VersionRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Version
{
    /**
     * @var integer
     *
     * @ORM\Column(name="etat_version", type="integer", nullable=false)
     */
    private $etatVersion = Etat::EDITION_DEMANDE;


    /**
     * @var string
     *
     * @ORM\Column(name="prj_l_labo", type="string", length=300, nullable=false)
     */
    private $prjLLabo = '';

    /**
     * @var string
     *
     * @ORM\Column(name="prj_titre", type="string", length=150, nullable=true)
     */
    private $prjTitre = '';

    /**
     * @var integer
     *
     * @ORM\Column(name="dem_heures", type="integer", nullable=true)
     */
    private $demHeures = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="attr_heures", type="integer", nullable=false)
     */
    private $attrHeures = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="cons_heures", type="integer", nullable=false)
     */
    private $consHeures = '0';


    /**
     * @var integer
     *
     * @ORM\Column(name="politique", type="integer", nullable=false)
     */
    private $politique = Politique::DEFAULT_POLITIQUE;


    /**
     * @var string
     *
     * @ORM\Column(name="prj_sous_thematique", type="string", length=100, nullable=true)
     */
    private $prjSousThematique;

    /**
     * @var string
     *
     * @ORM\Column(name="prj_financement", type="string", length=100, nullable=true)
     */
    private $prjFinancement = '';

    /**
     * @var string
     *
     * @ORM\Column(name="prj_genci_machines", type="string", length=60, nullable=false)
     */
    private $prjGenciMachines = '';

    /**
     * @var string
     *
     * @ORM\Column(name="prj_genci_centre", type="string", length=60, nullable=false)
     */
    private $prjGenciCentre = '';

    /**
     * @var string
     *
     * @ORM\Column(name="prj_genci_heures", type="string", length=30, nullable=false)
     */
    private $prjGenciHeures = '';

    /**
     * @var string
     *
     * @ORM\Column(name="prj_resume", type="text", nullable=false)
     */
    private $prjResume = '';

    /**
     * @var string
     *
     * @ORM\Column(name="prj_expose", type="text", nullable=false)
     */
    private $prjExpose = '';

    /**
     * @var string
     *
     * @ORM\Column(name="prj_justif_renouv", type="text", nullable=true)
     */
    private $prjJustifRenouv;

    /**
     * @var string
     *
     * @ORM\Column(name="prj_algorithme", type="text", length=65535, nullable=false)
     */
    private $prjAlgorithme = '';

    /**
     * @var boolean
     *
     * @ORM\Column(name="prj_conception", type="boolean", nullable=false)
     */
    private $prjConception = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="prj_developpement", type="boolean", nullable=false)
     */
    private $prjDeveloppement = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="prj_parallelisation", type="boolean", nullable=false)
     */
    private $prjParallelisation = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="prj_utilisation", type="boolean", nullable=false)
     */
    private $prjUtilisation = false;

    /**
     * @var string
     *
     * @ORM\Column(name="prj_fiche", type="blob", length=65535, nullable=false)
     */
    private $prjFiche = '';

    /**
     * @var boolean
     *
     * @ORM\Column(name="prj_fiche_val", type="boolean", nullable=false)
     */
    private $prjFicheVal = false;

    /**
     * @var string
     *
     * @ORM\Column(name="prj_genci_dari",  type="string", length=15, nullable=false)
     */
    private $prjGenciDari = '';

    /**
     * @var string
     *
     * @ORM\Column(name="code_nom", type="string", length=150, nullable=false)
     */
    private $codeNom = '';

    /**
     * @var string
     *
     * @ORM\Column(name="code_langage", type="string", length=30, nullable=true)
     */
    private $codeLangage = '';

    /**
     * @var boolean
     *
     * @ORM\Column(name="code_c", type="boolean", nullable=false)
     */
    private $codeC = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="code_cpp", type="boolean", nullable=false)
     */
    private $codeCpp = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="code_for", type="boolean", nullable=false)
     */
    private $codeFor = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="code_autre", type="boolean", nullable=false)
     */
    private $codeAutre = false;

    /**
     * @var string
     *
     * @ORM\Column(name="code_licence", type="text", length=65535, nullable=false)
     */
    private $codeLicence = '';

    /**
     * @var string
     *
     * @ORM\Column(name="code_util_sur_mach", type="text", length=65535, nullable=false)
     */
    private $codeUtilSurMach = '';

    /**
     * @var string
     *
     * @ORM\Column(name="code_heures_p_job", type="string", length=15, nullable=false)
     */
    private $codeHeuresPJob = '';

    /**
     * @var string
     *
     * @ORM\Column(name="code_ram_p_coeur", type="string", length=15, nullable=false)
     */
    private $codeRamPCoeur = '';

    /**
     * @var string
     *
     * @ORM\Column(name="gpu", type="string", length=15, nullable=false)
     */
    private $gpu = '';

    /**
     * @var string
     *
     * @ORM\Column(name="code_ram_part", type="string", length=15, nullable=false)
     */
    private $codeRamPart = '';

    /**
     * @var string
     *
     * @ORM\Column(name="code_eff_paral", type="string", length=15, nullable=false)
     */
    private $codeEffParal = '';

    /**
     * @var string
     *
     * @ORM\Column(name="code_vol_donn_tmp", type="string", length=15, nullable=false)
     */
    private $codeVolDonnTmp = '';

    /**
     * @var string
     *
     * @ORM\Column(name="dem_logiciels", type="text", length=65535, nullable=false)
     */
    private $demLogiciels ='';

    /**
     * @var string
     *
     * @ORM\Column(name="dem_bib", type="text", length=65535, nullable=false)
     */
    private $demBib ='';

    /**
     * @var string
     *
     * @ORM\Column(name="dem_post_trait", type="string", length=15, nullable=false)
     */
    private $demPostTrait = '';

    /**
     * @var string
     *
     * @ORM\Column(name="dem_form_maison", type="text", length=65535, nullable=false)
     */
    private $demFormMaison = '';

    /**
     * @var boolean
     *
     * @ORM\Column(name="dem_form_prise", type="boolean", nullable=false)
     */
    private $demFormPrise = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="dem_form_debogage", type="boolean", nullable=false)
     */
    private $demFormDebogage = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="dem_form_optimisation", type="boolean", nullable=false)
     */
    private $demFormOptimisation = false;

    /**
     * @var string
     *
     * @ORM\Column(name="dem_form_autres", type="text", length=65535, nullable=false)
     */
    private $demFormAutres = '';

    /**
     * @var boolean
     *
     * @ORM\Column(name="dem_form_fortran", type="boolean", nullable=false)
     */
    private $demFormFortran = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="dem_form_c", type="boolean", nullable=false)
     */
    private $demFormC = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="dem_form_cpp", type="boolean", nullable=false)
     */
    private $demFormCpp = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="dem_form_python", type="boolean", nullable=false)
     */
    private $demFormPython = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="dem_form_mpi", type="boolean", nullable=false)
     */
    private $demFormMPI = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="dem_form_openmp", type="boolean", nullable=false)
     */
    private $demFormOpenMP = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="dem_form_openacc", type="boolean", nullable=false)
     */
    private $demFormOpenACC = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="dem_form_paraview", type="boolean", nullable=false)
     */
    private $demFormParaview = false;

    /**
     * @var string
     *
     * @ORM\Column(name="libelle_thematique", type="string", length=200, nullable=false)
     */
    private $libelleThematique ='';

    /**
     * @var boolean
     *
     * @ORM\Column(name="attr_accept", type="boolean", nullable=false)
     */
    private $attrAccept = true;


    /**
     * @var integer
     *
     * @ORM\Column(name="rap_conf", type="integer", nullable=false)
     */
    private $rapConf = 0;

    /**
     * @var \AppBundle\Entity\Individu
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Individu")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="maj_ind", referencedColumnName="id_individu",onDelete="SET NULL")
     * })
     */
    private $majInd;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="maj_stamp", type="datetime", nullable=false)
     */
    private $majStamp;

    /**
     * @var string
     *
     * @ORM\Column(name="sond_vol_donn_perm", type="string", length=15, nullable=false)
     */
    private $sondVolDonnPerm = '';

    /**
     * @var string
     *
     * @ORM\Column(name="sond_duree_donn_perm", type="string", length=15, nullable=false)
     */
    private $sondDureeDonnPerm = '';

    /**
     * @var integer
     *
     * @ORM\Column(name="prj_fiche_len", type="integer", nullable=false)
     */
    private $prjFicheLen = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="penal_heures", type="integer", nullable=false)
     */
    private $penalHeures = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="attr_heures_ete", type="integer", nullable=false)
     */
    private $attrHeuresEte = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="sond_justif_donn_perm", type="text", nullable=false)
     */
    private $sondJustifDonnPerm = '';

    /**
     * @var string
     *
     * @ORM\Column(name="dem_form_autres_autres", type="text", length=65535, nullable=false)
     */
    private $demFormAutresAutres = '';

    /**
     * @var boolean
     *
     * @ORM\Column(name="cgu", type="boolean", nullable=false)
     */
    private $CGU = false;

    /**
     * @var string
     *
     * @ORM\Column(name="id_version", type="string", length=9)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $idVersion;

    /**
     * @var \AppBundle\Entity\Thematique
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Thematique")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="prj_id_thematique", referencedColumnName="id_thematique")
     * })
     */
    private $prjThematique;

    /**
     * @var \AppBundle\Entity\Session
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Session")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_session", referencedColumnName="id_session")
     * })
     */
    private $session;

    /**
     * @var \AppBundle\Entity\Projet
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Projet", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_projet", referencedColumnName="id_projet", nullable=false )
     * })
     */
    private $projet;

    /////////

    /**
    * @ORM\PrePersist
    */
    public function setInitialMajStamp()
    {
        $this->majStamp = new \DateTime();
        $this->majInd   = AppBundle::getUser();
    }

    /**
    * @ORM\PostPersist
    */
    public function setDerniereVersion()
    {
    if( $this->projet   !=  null )
        $this->projet->calculDerniereVersion();
    }

    /**
    * @ORM\PreUpdate
    */
    public function setUpdateMajStamp()
    {
            $this->majStamp = new \DateTime();
            $this->majInd   = AppBundle::getUser();
    }

    /**
    * @ORM\PostUpdate
    */
    public function setVersionActive()
    // on ne sait pas si cela marche parce que l'on ne s'en sert pas
    {
    if( $this->etatVersion == Etat::ACTIF && $this->projet   !=  null )
        {
            $this->projet->setVersionActive( $this);
            //AppBundle::getManager()->flush();
        }
    }

    /**
    * convertir la table codeLangage en checkbox
    * @ORM\PreUpdate
    */
    public function convertCodeLanguage()
    {
    $codeLangage = $this->getCodeLangage();
    if( $codeLangage !=  null )
        {
        if( preg_match('/Fortran/',$codeLangage) )    $this->setCodeFor( true );
        if( preg_match('/C,/',$codeLangage)  )        $this->setCodeC( true );
        if( preg_match('/C++/',$codeLangage)  )       $this->setCodeCpp( true );
        if( preg_match('/Autre/',$codeLangage) )      $this->setCodeAutre( true );
        // Supprimé par MANU car il peut y avoir là d'autres langages (python, R, etc)
        // Tant pis s'il y a redondance entre codeC etc. et codeLangage
        //$this->setCodeLangage(null);
        }
    }


    /**
    * convertir la table codeLangage en checkbox
    * @ORM\PostLoad
    */
    /* public function convert2CodeLanguage()
    {
    $codeLangage = $this->getCodeLangage();
    $modify = false;
    if( $codeLangage !=  null )
        {
        if( preg_match("/code_ccc/", $codeLangage ) == 1 )
                {
                $codeLangage = trim( preg_replace("/(code_ccc)/","",$codeLangage) );
                $this->setCodeC(true);
                $modify = true;
                }
        if( preg_match("/code_cpp/", $codeLangage ) == 1 )
                {
                $codeLangage = trim( preg_replace("/(code_cpp)/","",$codeLangage) );
                $this->setCodeCpp(true);
                $modify = true;
                }
        if( preg_match("/code_for/", $codeLangage ) == 1 )
                {
                $codeLangage = trim( preg_replace("/(code_for)/","",$codeLangage) );
                $this->setFor(true);
                $modify = true;
                }
        if( preg_match("/code_autre/", $codeLangage ) == 1 )
                {
                $codeLangage = trim( preg_replace("/(code_autre)/","",$codeLangage) );
                $this->setAutre(true);
                $modify = true;
                }
        if( $modify == true )
            $this->setCodeLangage( $codeLangage );
        }
    }*/

    ////////////////////////////////////////////////////////////////////////

   
    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="\AppBundle\Entity\CollaborateurVersion", mappedBy="version", cascade={"persist"})
     */
    private $collaborateurVersion;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="\AppBundle\Entity\Rallonge", mappedBy="version", cascade={"persist"})
     */
    private $rallonge;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="\AppBundle\Entity\Expertise", mappedBy="version", cascade={"persist"} )
     */
    private $expertise;


    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="\AppBundle\Entity\Projet", mappedBy="versionDerniere", cascade={"persist"} )
     */
    private $versionDerniere;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="\AppBundle\Entity\Projet", mappedBy="versionActive", cascade={"persist"} )
     */
    private $versionActive;

    ///////////////////////////////////////////////////////////

    public function __toString()    {   return (string)$this->getIdVersion();   }

    /////////////////////////////////////////////////////////////////


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->collaborateurVersion = new \Doctrine\Common\Collections\ArrayCollection();
        $this->rallonge             = new \Doctrine\Common\Collections\ArrayCollection();
        $this->expertise            = new \Doctrine\Common\Collections\ArrayCollection();
        $this->versionDerniere      = new \Doctrine\Common\Collections\ArrayCollection();
        $this->versionActive        = new \Doctrine\Common\Collections\ArrayCollection();
        $this->etatVersion          = Etat::EDITION_DEMANDE;
    }

    /**
     * Set etatVersion
     *
     * @param integer $etatVersion
     *
     * @return Version
     */
    public function setEtatVersion($etatVersion)
    {
        $this->etatVersion = $etatVersion;

        return $this;
    }

    /**
     * Get etatVersion
     *
     * @return integer
     */
    public function getEtatVersion()
    {
        return $this->etatVersion;
    }


    /**
     * Set prjLLabo
     *
     * @param string $prjLLabo
     *
     * @return Version
     */
    public function setPrjLLabo($prjLLabo)
    {
        $this->prjLLabo = $prjLLabo;

        return $this;
    }

    /**
     * Get prjLLabo
     *
     * @return string
     */
    public function getPrjLLabo()
    {
        return $this->prjLLabo;
    }

    /**
     * Set prjTitre
     *
     * @param string $prjTitre
     *
     * @return Version
     */
    public function setPrjTitre($prjTitre)
    {
        $this->prjTitre = $prjTitre;

        return $this;
    }

    /**
     * Get prjTitre
     *
     * @return string
     */
    public function getPrjTitre()
    {
        return $this->prjTitre;
    }

    /**
     * Set demHeures
     *
     * @param integer $demHeures
     *
     * @return Version
     */
    public function setDemHeures($demHeures)
    {
        $this->demHeures = $demHeures;

        return $this;
    }

    /**
     * Get demHeures
     *
     * @return integer
     */
    public function getDemHeures()
    {
        return $this->demHeures;
    }

    /**
     * Set attrHeures
     *
     * @param integer $attrHeures
     *
     * @return Version
     */
    public function setAttrHeures($attrHeures)
    {
        $this->attrHeures = $attrHeures;

        return $this;
    }

    /**
     * Get attrHeures
     *
     * @return integer
     */
    public function getAttrHeures()
    {
        return $this->attrHeures;
    }

    /**
     * Set consHeures
     *
     * @param integer $consHeures
     *
     * @return Version
     */
    public function setConsHeures($consHeures)
    {
        $this->consHeures = $consHeures;

        return $this;
    }

    /**
     * Get consHeures
     *
     * @return integer
     */
    public function getConsHeures()
    {
        return $this->consHeures;
    }

    /**
     * Set prjSousThematique
     *
     * @param string $prjSousThematique
     *
     * @return Version
     */
    public function setPrjSousThematique($prjSousThematique)
    {
        $this->prjSousThematique = $prjSousThematique;

        return $this;
    }

    /**
     * Get prjSousThematique
     *
     * @return string
     */
    public function getPrjSousThematique()
    {
        return $this->prjSousThematique;
    }

    /**
     * Set prjFinancement
     *
     * @param string $prjFinancement
     *
     * @return Version
     */
    public function setPrjFinancement($prjFinancement)
    {
        $this->prjFinancement = $prjFinancement;

        return $this;
    }

    /**
     * Get prjFinancement
     *
     * @return string
     */
    public function getPrjFinancement()
    {
        return $this->prjFinancement;
    }

    /**
     * Set prjGenciMachines
     *
     * @param string $prjGenciMachines
     *
     * @return Version
     */
    public function setPrjGenciMachines($prjGenciMachines)
    {
        $this->prjGenciMachines = $prjGenciMachines;

        return $this;
    }

    /**
     * Get prjGenciMachines
     *
     * @return string
     */
    public function getPrjGenciMachines()
    {
        return $this->prjGenciMachines;
    }

    /**
     * Set prjGenciCentre
     *
     * @param string $prjGenciCentre
     *
     * @return Version
     */
    public function setPrjGenciCentre($prjGenciCentre)
    {
        $this->prjGenciCentre = $prjGenciCentre;

        return $this;
    }

    /**
     * Get prjGenciCentre
     *
     * @return string
     */
    public function getPrjGenciCentre()
    {
        return $this->prjGenciCentre;
    }

    /**
     * Set prjGenciDari
     *
     * @param string $prjGenciDari
     *
     * @return Version
     */
    public function setPrjGenciDari($prjGenciDari)
    {
        $this->prjGenciDari = $prjGenciDari;

        return $this;
    }

    /**
     * Get prjGenciDari
     *
     * @return string
     */
    public function getPrjGenciDari()
    {
        return $this->prjGenciDari;
    }

    /**
     * Set prjGenciHeures
     *
     * @param string $prjGenciHeures
     *
     * @return Version
     */
    public function setPrjGenciHeures($prjGenciHeures)
    {
        $this->prjGenciHeures = $prjGenciHeures;

        return $this;
    }

    /**
     * Get prjGenciHeures
     *
     * @return string
     */
    public function getPrjGenciHeures()
    {
        return $this->prjGenciHeures;
    }

    /**
     * Set prjResume
     *
     * @param string $prjResume
     *
     * @return Version
     */
    public function setPrjResume($prjResume)
    {
        $this->prjResume = $prjResume;

        return $this;
    }

    /**
     * Get prjResume
     *
     * @return string
     */
    public function getPrjResume()
    {
        return $this->prjResume;
    }

    /**
     * Set prjExpose
     *
     * @param string $prjExpose
     *
     * @return Version
     */
    public function setPrjExpose($prjExpose)
    {
        $this->prjExpose = $prjExpose;

        return $this;
    }

    /**
     * Get prjExpose
     *
     * @return string
     */
    public function getPrjExpose()
    {
        return $this->prjExpose;
    }

    /**
     * Set prjJustifRenouv
     *
     * @param string $prjJustifRenouv
     *
     * @return Version
     */
    public function setPrjJustifRenouv($prjJustifRenouv)
    {
        $this->prjJustifRenouv = $prjJustifRenouv;

        return $this;
    }

    /**
     * Get prjJustifRenouv
     *
     * @return string
     */
    public function getPrjJustifRenouv()
    {
        return $this->prjJustifRenouv;
    }

    /**
     * Set prjAlgorithme
     *
     * @param string $prjAlgorithme
     *
     * @return Version
     */
    public function setPrjAlgorithme($prjAlgorithme)
    {
        $this->prjAlgorithme = $prjAlgorithme;

        return $this;
    }

    /**
     * Get prjAlgorithme
     *
     * @return string
     */
    public function getPrjAlgorithme()
    {
        return $this->prjAlgorithme;
    }

    /**
     * Set prjConception
     *
     * @param boolean $prjConception
     *
     * @return Version
     */
    public function setPrjConception($prjConception)
    {
        $this->prjConception = $prjConception;

        return $this;
    }

    /**
     * Get prjConception
     *
     * @return boolean
     */
    public function getPrjConception()
    {
        return $this->prjConception;
    }

    /**
     * Set prjDeveloppement
     *
     * @param boolean $prjDeveloppement
     *
     * @return Version
     */
    public function setPrjDeveloppement($prjDeveloppement)
    {
        $this->prjDeveloppement = $prjDeveloppement;

        return $this;
    }

    /**
     * Get prjDeveloppement
     *
     * @return boolean
     */
    public function getPrjDeveloppement()
    {
        return $this->prjDeveloppement;
    }

    /**
     * Set prjParallelisation
     *
     * @param boolean $prjParallelisation
     *
     * @return Version
     */
    public function setPrjParallelisation($prjParallelisation)
    {
        $this->prjParallelisation = $prjParallelisation;

        return $this;
    }

    /**
     * Get prjParallelisation
     *
     * @return boolean
     */
    public function getPrjParallelisation()
    {
        return $this->prjParallelisation;
    }

    /**
     * Set prjUtilisation
     *
     * @param boolean $prjUtilisation
     *
     * @return Version
     */
    public function setPrjUtilisation($prjUtilisation)
    {
        $this->prjUtilisation = $prjUtilisation;

        return $this;
    }

    /**
     * Get prjUtilisation
     *
     * @return boolean
     */
    public function getPrjUtilisation()
    {
        return $this->prjUtilisation;
    }

    /**
     * Set prjFiche
     *
     * @param string $prjFiche
     *
     * @return Version
     */
    public function setPrjFiche($prjFiche)
    {
        $this->prjFiche = $prjFiche;

        return $this;
    }

    /**
     * Get prjFiche
     *
     * @return string
     */
    public function getPrjFiche()
    {
        return $this->prjFiche;
    }

    /**
     * Set prjFicheVal
     *
     * @param boolean $prjFicheVal
     *
     * @return Version
     */
    public function setPrjFicheVal($prjFicheVal)
    {
        $this->prjFicheVal = $prjFicheVal;

        return $this;
    }

    /**
     * Get prjFicheVal
     *
     * @return boolean
     */
    public function getPrjFicheVal()
    {
        return $this->prjFicheVal;
    }

    /**
     * Set codeNom
     *
     * @param string $codeNom
     *
     * @return Version
     */
    public function setCodeNom($codeNom)
    {
        $this->codeNom = $codeNom;

        return $this;
    }

    /**
     * Get codeNom
     *
     * @return string
     */
    public function getCodeNom()
    {
        return $this->codeNom;
    }

    /**
     * Set codeLangage
     *
     * @param string $codeLangage
     *
     * @return Version
     */
    public function setCodeLangage($codeLangage)
    {
        $this->codeLangage = $codeLangage;

        return $this;
    }

    /**
     * Get codeLangage
     *
     * @return string
     */
    public function getCodeLangage()
    {
        return $this->codeLangage;
    }

    /**
     * Set codeC
     *
     * @param boolean $codeC
     *
     * @return Version
     */
    public function setCodeC($codeC)
    {
        $this->codeC = $codeC;

        return $this;
    }

    /**
     * Get codeC
     *
     * @return boolean
     */
    public function getCodeC()
    {
        return $this->codeC;
    }

    /**
     * Set codeCpp
     *
     * @param boolean $codeCpp
     *
     * @return Version
     */
    public function setCodeCpp($codeCpp)
    {
        $this->codeCpp = $codeCpp;

        return $this;
    }

    /**
     * Get codeCpp
     *
     * @return boolean
     */
    public function getCodeCpp()
    {
        return $this->codeCpp;
    }

    /**
     * Set codeFor
     *
     * @param boolean $codeFor
     *
     * @return Version
     */
    public function setFor($codeFor)
    {
        $this->codeFor = $codeFor;

        return $this;
    }

    /**
     * Get codeFor
     *
     * @return boolean
     */
    public function getCodeFor()
    {
        return $this->codeFor;
    }

    /**
     * Set codeAutre
     *
     * @param boolean $codeAutre
     *
     * @return Version
     */
    public function setAutre($codeAutre)
    {
        $this->codeAutre = $codeAutre;

        return $this;
    }

    /**
     * Get codeAutre
     *
     * @return boolean
     */
    public function getCodeAutre()
    {
        return $this->codeAutre;
    }

    /**
     * Set codeLicence
     *
     * @param string $codeLicence
     *
     * @return Version
     */
    public function setCodeLicence($codeLicence)
    {
        $this->codeLicence = $codeLicence;

        return $this;
    }

    /**
     * Get codeLicence
     *
     * @return string
     */
    public function getCodeLicence()
    {
        return $this->codeLicence;
    }

    /**
     * Set codeUtilSurMach
     *
     * @param string $codeUtilSurMach
     *
     * @return Version
     */
    public function setCodeUtilSurMach($codeUtilSurMach)
    {
        $this->codeUtilSurMach = $codeUtilSurMach;

        return $this;
    }

    /**
     * Get codeUtilSurMach
     *
     * @return string
     */
    public function getCodeUtilSurMach()
    {
        return $this->codeUtilSurMach;
    }

    /**
     * Set codeHeuresPJob
     *
     * @param string $codeHeuresPJob
     *
     * @return Version
     */
    public function setCodeHeuresPJob($codeHeuresPJob)
    {
        $this->codeHeuresPJob = $codeHeuresPJob;

        return $this;
    }

    /**
     * Get codeHeuresPJob
     *
     * @return string
     */
    public function getCodeHeuresPJob()
    {
        return $this->codeHeuresPJob;
    }

    /**
     * Set codeRamPCoeur
     *
     * @param string $codeRamPCoeur
     *
     * @return Version
     */
    public function setCodeRamPCoeur($codeRamPCoeur)
    {
        $this->codeRamPCoeur = $codeRamPCoeur;

        return $this;
    }

    /**
     * Get codeRamPCoeur
     *
     * @return string
     */
    public function getCodeRamPCoeur()
    {
        return $this->codeRamPCoeur;
    }

    /**
     * Set gpu
     *
     * @param string $gpu
     *
     * @return Version
     */
    public function setGpu($gpu)
    {
        $this->gpu = $gpu;

        return $this;
    }

    /**
     * Get gpu
     *
     * @return string
     */
    public function getGpu()
    {
        return $this->gpu;
    }

    /**
     * Set codeRamPart
     *
     * @param string $codeRamPart
     *
     * @return Version
     */
    public function setCodeRamPart($codeRamPart)
    {
        $this->codeRamPart = $codeRamPart;

        return $this;
    }

    /**
     * Get codeRamPart
     *
     * @return string
     */
    public function getCodeRamPart()
    {
        return $this->codeRamPart;
    }

    /**
     * Set codeEffParal
     *
     * @param string $codeEffParal
     *
     * @return Version
     */
    public function setCodeEffParal($codeEffParal)
    {
        $this->codeEffParal = $codeEffParal;

        return $this;
    }

    /**
     * Get codeEffParal
     *
     * @return string
     */
    public function getCodeEffParal()
    {
        return $this->codeEffParal;
    }

    /**
     * Set codeVolDonnTmp
     *
     * @param string $codeVolDonnTmp
     *
     * @return Version
     */
    public function setCodeVolDonnTmp($codeVolDonnTmp)
    {
        $this->codeVolDonnTmp = $codeVolDonnTmp;

        return $this;
    }

    /**
     * Get codeVolDonnTmp
     *
     * @return string
     */
    public function getCodeVolDonnTmp()
    {
        return $this->codeVolDonnTmp;
    }

    /**
     * Set demLogiciels
     *
     * @param string $demLogiciels
     *
     * @return Version
     */
    public function setDemLogiciels($demLogiciels)
    {
        $this->demLogiciels = $demLogiciels;

        return $this;
    }

    /**
     * Get demLogiciels
     *
     * @return string
     */
    public function getDemLogiciels()
    {
        return $this->demLogiciels;
    }

    /**
     * Set demBib
     *
     * @param string $demBib
     *
     * @return Version
     */
    public function setDemBib($demBib)
    {
        $this->demBib = $demBib;

        return $this;
    }

    /**
     * Get demBib
     *
     * @return string
     */
    public function getDemBib()
    {
        return $this->demBib;
    }

    /**
     * Set demPostTrait
     *
     * @param string $demPostTrait
     *
     * @return Version
     */
    public function setDemPostTrait($demPostTrait)
    {
        $this->demPostTrait = $demPostTrait;

        return $this;
    }

    /**
     * Get demPostTrait
     *
     * @return string
     */
    public function getDemPostTrait()
    {
        return $this->demPostTrait;
    }

    /**
     * Set demFormMaison
     *
     * @param string $demFormMaison
     *
     * @return Version
     */
    public function setDemFormMaison($demFormMaison)
    {
        $this->demFormMaison = $demFormMaison;

        return $this;
    }

    /**
     * Get demFormMaison
     *
     * @return string
     */
    public function getDemFormMaison()
    {
        return $this->demFormMaison;
    }

    /**
     * Set demFormAutres
     *
     * @param string $demFormAutres
     *
     * @return Version
     */
    public function setDemFormAutres($demFormAutres)
    {
        $this->demFormAutres = $demFormAutres;

        return $this;
    }

    /**
     * Set codeFor
     *
     * @param boolean $codeFor
     *
     * @return Version
     */
    public function setCodeFor($codeFor)
    {
        $this->codeFor = $codeFor;

        return $this;
    }

    /**
     * Set codeAutre
     *
     * @param boolean $codeAutre
     *
     * @return Version
     */
    public function setCodeAutre($codeAutre)
    {
        $this->codeAutre = $codeAutre;

        return $this;
    }

    /**
     * Set demFormPrise
     *
     * @param boolean $demFormPrise
     *
     * @return Version
     */
    public function setDemFormPrise($demFormPrise)
    {
        $this->demFormPrise = $demFormPrise;

        return $this;
    }

    /**
     * Get demFormPrise
     *
     * @return boolean
     */
    public function getDemFormPrise()
    {
        return $this->demFormPrise;
    }

    /**
     * Set demFormDebogage
     *
     * @param boolean $demFormDebogage
     *
     * @return Version
     */
    public function setDemFormDebogage($demFormDebogage)
    {
        $this->demFormDebogage = $demFormDebogage;

        return $this;
    }

    /**
     * Get demFormDebogage
     *
     * @return boolean
     */
    public function getDemFormDebogage()
    {
        return $this->demFormDebogage;
    }

    /**
     * Set demFormOptimisation
     *
     * @param boolean $demFormOptimisation
     *
     * @return Version
     */
    public function setDemFormOptimisation($demFormOptimisation)
    {
        $this->demFormOptimisation = $demFormOptimisation;

        return $this;
    }

    /**
     * Get demFormOptimisation
     *
     * @return boolean
     */
    public function getDemFormOptimisation()
    {
        return $this->demFormOptimisation;
    }

    /**
     * Set demFormFortran
     *
     * @param boolean $demFormFortran
     *
     * @return Version
     */
    public function setDemFormFortran($demFormFortran)
    {
        $this->demFormFortran = $demFormFortran;

        return $this;
    }

    /**
     * Get demFormFortran
     *
     * @return boolean
     */
    public function getDemFormFortran()
    {
        return $this->demFormFortran;
    }

    /**
     * Set demFormC
     *
     * @param boolean $demFormC
     *
     * @return Version
     */
    public function setDemFormC($demFormC)
    {
        $this->demFormC = $demFormC;

        return $this;
    }

    /**
     * Get demFormC
     *
     * @return boolean
     */
    public function getDemFormC()
    {
        return $this->demFormC;
    }

    /**
     * Set demFormCpp
     *
     * @param boolean $demFormCpp
     *
     * @return Version
     */
    public function setDemFormCpp($demFormCpp)
    {
        $this->demFormCpp = $demFormCpp;

        return $this;
    }

    /**
     * Get demFormCpp
     *
     * @return boolean
     */
    public function getDemFormCpp()
    {
        return $this->demFormCpp;
    }

    /**
     * Set demFormPython
     *
     * @param boolean $demFormPython
     *
     * @return Version
     */
    public function setDemFormPython($demFormPython)
    {
        $this->demFormPython = $demFormPython;

        return $this;
    }

    /**
     * Get demFormPython
     *
     * @return boolean
     */
    public function getDemFormPython()
    {
        return $this->demFormPython;
    }

    /**
     * Set demFormMPI
     *
     * @param boolean $demFormMPI
     *
     * @return Version
     */
    public function setDemFormMPI($demFormMPI)
    {
        $this->demFormMPI = $demFormMPI;

        return $this;
    }

    /**
     * Get demFormMPI
     *
     * @return boolean
     */
    public function getDemFormMPI()
    {
        return $this->demFormMPI;
    }

    /**
     * Set demFormOpenMP
     *
     * @param boolean $demFormOpenMP
     *
     * @return Version
     */
    public function setDemFormOpenMP($demFormOpenMP)
    {
        $this->demFormOpenMP = $demFormOpenMP;

        return $this;
    }

    /**
     * Get demFormOpenMP
     *
     * @return boolean
     */
    public function getDemFormOpenMP()
    {
        return $this->demFormOpenMP;
    }

    /**
     * Set demFormOpenACC
     *
     * @param boolean $demFormOpenACC
     *
     * @return Version
     */
    public function setDemFormOpenACC($demFormOpenACC)
    {
        $this->demFormOpenACC = $demFormOpenACC;

        return $this;
    }

    /**
     * Get demFormOpenACC
     *
     * @return boolean
     */
    public function getDemFormOpenACC()
    {
        return $this->demFormOpenACC;
    }

    /**
     * Set demFormParaview
     *
     * @param boolean $demFormParaview
     *
     * @return Version
     */
    public function setDemFormParaview($demFormParaview)
    {
        $this->demFormParaview = $demFormParaview;

        return $this;
    }

    /**
     * Get demFormParaview
     *
     * @return boolean
     */
    public function getDemFormParaview()
    {
        return $this->demFormParaview;
    }

    /**
     * Get demFormAutres
     *
     * @return string
     */
    public function getDemFormAutres()
    {
        return $this->demFormAutres;
    }

    /**
     * Set libelleThematique
     *
     * @param string $libelleThematique
     *
     * @return Version
     */
    public function setLibelleThematique($libelleThematique)
    {
        $this->libelleThematique = $libelleThematique;

        return $this;
    }

    /**
     * Get libelleThematique
     *
     * @return string
     */
    public function getLibelleThematique()
    {
        return $this->libelleThematique;
    }

    /**
     * Set attrAccept
     *
     * @param boolean $attrAccept
     *
     * @return Version
     */
    public function setAttrAccept($attrAccept)
    {
        $this->attrAccept = $attrAccept;

        return $this;
    }

    /**
     * Get attrAccept
     *
     * @return boolean
     */
    public function getAttrAccept()
    {
        return $this->attrAccept;
    }

    /**
     * Set rapConf
     *
     * @param integer $rapConf
     *
     * @return Version
     */
    public function setRapConf($rapConf)
    {
        $this->rapConf = $rapConf;

        return $this;
    }

    /**
     * Get rapConf
     *
     * @return integer
     */
    public function getRapConf()
    {
        return $this->rapConf;
    }

    /**
     * Set majInd
     *
     * @param AppBundle\Entity\Individu
     *
     * @return Version
     */
    public function setMajInd($majInd)
    {
        $this->majInd = $majInd;

        return $this;
    }

    /**
     * Get majInd
     *
     * @return AppBundle\Entity\Individu
     */
    public function getMajInd()
    {
        return $this->majInd;
    }

    /**
     * Set majStamp
     *
     * @param \DateTime $majStamp
     *
     * @return Version
     */
    public function setMajStamp($majStamp)
    {
        $this->majStamp = $majStamp;

        return $this;
    }

    /**
     * Get majStamp
     *
     * @return \DateTime
     */
    public function getMajStamp()
    {
        return $this->majStamp;
    }

    /**
     * Set sondVolDonnPerm
     *
     * @param string $sondVolDonnPerm
     *
     * @return Version
     */
    public function setSondVolDonnPerm($sondVolDonnPerm)
    {
        $this->sondVolDonnPerm = $sondVolDonnPerm;

        return $this;
    }

    /**
     * Get sondVolDonnPerm
     *
     * @return string
     */
    public function getSondVolDonnPerm()
    {
        return $this->sondVolDonnPerm;
    }

    /**
     * Set sondDureeDonnPerm
     *
     * @param string $sondDureeDonnPerm
     *
     * @return Version
     */
    public function setSondDureeDonnPerm($sondDureeDonnPerm)
    {
        $this->sondDureeDonnPerm = $sondDureeDonnPerm;

        return $this;
    }

    /**
     * Get sondDureeDonnPerm
     *
     * @return string
     */
    public function getSondDureeDonnPerm()
    {
        return $this->sondDureeDonnPerm;
    }

    /**
     * Set prjFicheLen
     *
     * @param integer $prjFicheLen
     *
     * @return Version
     */
    public function setPrjFicheLen($prjFicheLen)
    {
        $this->prjFicheLen = $prjFicheLen;

        return $this;
    }

    /**
     * Get prjFicheLen
     *
     * @return integer
     */
    public function getPrjFicheLen()
    {
        return $this->prjFicheLen;
    }

    /**
     * Set penalHeures
     *
     * @param integer $penalHeures
     *
     * @return Version
     */
    public function setPenalHeures($penalHeures)
    {
        $this->penalHeures = $penalHeures;

        return $this;
    }

    /**
     * Get penalHeures
     *
     * @return integer
     */
    public function getPenalHeures()
    {
        return $this->penalHeures;
    }

    /**
     * Set attrHeuresEte
     *
     * @param integer $attrHeuresEte
     *
     * @return Version
     */
    public function setAttrHeuresEte($attrHeuresEte)
    {
        $this->attrHeuresEte = $attrHeuresEte;

        return $this;
    }

    /**
     * Get attrHeuresEte
     *
     * @return integer
     */
    public function getAttrHeuresEte()
    {
        return $this->attrHeuresEte;
    }

    /**
     * Set sondJustifDonnPerm
     *
     * @param string $sondJustifDonnPerm
     *
     * @return Version
     */
    public function setSondJustifDonnPerm($sondJustifDonnPerm)
    {
        $this->sondJustifDonnPerm = $sondJustifDonnPerm;

        return $this;
    }

    /**
     * Get sondJustifDonnPerm
     *
     * @return string
     */
    public function getSondJustifDonnPerm()
    {
        return $this->sondJustifDonnPerm;
    }

    /**
     * Set demFormAutresAutres
     *
     * @param string $demFormAutresAutres
     *
     * @return Version
     */
    public function setDemFormAutresAutres($demFormAutresAutres)
    {
        $this->demFormAutresAutres = $demFormAutresAutres;

        return $this;
    }

    /**
     * Get demFormAutresAutres
     *
     * @return string
     */
    public function getDemFormAutresAutres()
    {
        return $this->demFormAutresAutres;
    }

    /**
     * Set idVersion
     *
     * @param string $idVersion
     *
     * @return Version
     */
    public function setIdVersion($idVersion)
    {
        $this->idVersion = $idVersion;

        return $this;
    }

    /**
     * Get idVersion
     *
     * @return string
     */
    public function getIdVersion()
    {
        return $this->idVersion;
    }

    /**
     * Set CGU
     *
     * @param boolean $CGU
     *
     * @return Version
     */
    public function setCGU($CGU)
    {
        $this->CGU = $CGU;

        return $this;
    }

    /**
     * Get CGU
     *
     * @return boolean
     */
    public function getCGU()
    {
        return $this->CGU;
    }

    /**
     * Get politique
     *
     * @return integer
     */
    public function getPolitique()
    {
        return $this->politique;
    }

    /**
     * Set politique
     *
     * @param integer $politique
     *
     * @return Version
     */
    public function setPolitique($politique)
    {
        $this->politique = $politique;

        return $this;
    }

    /**
     * Set prjThematique
     *
     * @param \AppBundle\Entity\Thematique $prjThematique
     *
     * @return Version
     */
    public function setPrjThematique(\AppBundle\Entity\Thematique $prjThematique = null)
    {
        $this->prjThematique = $prjThematique;

        return $this;
    }

    /**
     * Get prjThematique
     *
     * @return \AppBundle\Entity\Thematique
     */
    public function getPrjThematique()
    {
        return $this->prjThematique;
    }

    /**
     * Set session
     *
     * @param \AppBundle\Entity\Session $session
     *
     * @return Version
     */
    public function setSession(\AppBundle\Entity\Session $session = null)
    {
        $this->session = $session;

        return $this;
    }

    /**
     * Get session
     *
     * @return \AppBundle\Entity\Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Set projet
     *
     * @param \AppBundle\Entity\Projet $projet
     *
     * @return Version
     */
    public function setProjet(\AppBundle\Entity\Projet $projet = null)
    {
        $this->projet = $projet;

        return $this;
    }

    /**
     * Get projet
     *
     * @return \AppBundle\Entity\Projet
     */
    public function getProjet()
    {
        return $this->projet;
    }

   
    /**
     * Add collaborateurVersion
     *
     * @param \AppBundle\Entity\CollaborateurVersion $collaborateurVersion
     *
     * @return Version
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
     * Add rallonge
     *
     * @param \AppBundle\Entity\Rallonge $rallonge
     *
     * @return Version
     */
    public function addRallonge(\AppBundle\Entity\Rallonge $rallonge)
    {
        $this->rallonge[] = $rallonge;

        return $this;
    }

    /**
     * Remove rallonge
     *
     * @param \AppBundle\Entity\Rallonge $rallonge
     */
    public function removeRallonge(\AppBundle\Entity\Rallonge $rallonge)
    {
        $this->rallonge->removeElement($rallonge);
    }

    /**
     * Get rallonge
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRallonge()
    {
        return $this->rallonge;
    }

    // Expertise

    /**
     * Add expertise
     *
     * @param \AppBundle\Entity\Expertise $expertise
     *
     * @return Version
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

    /////////////////////////////////

    // pour workflow

    public function getObjectState()
    {
        return $this->getEtatVersion();
    }

    public function setObjectState($state)
    {
        $this->setEtatVersion($state);
        AppBundle::getManager()->flush();
        return $this;
    }

    public function getSubWorkflow()    { return new \AppBundle\Workflow\RallongeWorkflow(); }
    public function getSubObjects()     { return $this->getRallonge();   }

    ///////////////////////////////////////////////////////////////////////////////////

    /* pour bilan session depuis la table CollaborateurVersion
     *
     * getResponsable
     *
     * @return \AppBundle\Entity\Individu
     */
    public function getResponsable()
    {
        foreach( $this->getCollaborateurVersion() as $item )
                if( $item->getResponsable() == true )
                    return $item->getCollaborateur();
        return null;
    }

    public function getResponsables()
    {
        $responsables   = [];
        foreach( $this->getCollaborateurVersion() as $item )
                if( $item->getResponsable() == true )
                     $responsables[] = $item->getCollaborateur();
        return $responsables;
    }

    //
    // $moi_aussi == true : je peux être dans la liste éventuellement
    // $seulement_eligibles == true : Individu permanent et d'un labo régional à la fois
    //

    public function getCollaborateurs($moi_aussi = true, $seulement_eligibles = false )
    {
        $collaborateurs = [];

        foreach( $this->getCollaborateurVersion() as $item )
            {
            $collaborateur   =  $item->getCollaborateur();
            if( $collaborateur == null )
                {
                Functions::errorMessage("Version:getCollaborateur : collaborateur null pour CollaborateurVersion ". $item->getId() );
                continue;
                }
            if( $moi_aussi == false && $collaborateur->isEqualTo( AppBundle::getUser() ) )  continue;
            if( $seulement_eligibles == false || ( $collaborateur->isPermanent() && $collaborateur->isFromLaboRegional() ) )
                $collaborateurs[]   =  $collaborateur;
            }
        return $collaborateurs;
    }

    ////////////////////////////////////////////////

    public function changerResponsable(Individu $old, Individu $new)
    {
        $em =   AppBundle::getManager();
        foreach( $this->getCollaborateurVersion() as $item )
            {
            $collaborateur = $item->getCollaborateur();
            if( $collaborateur == null )
            {
                Functions::errorMessage(__METHOD__ .":". __LINE__ . " collaborateur null pour CollaborateurVersion ". $item->getId() );
                continue;
            }

            if( $collaborateur->isEqualTo( $new ) )
            {
                $item->setResponsable(true);
                $em->persist( $item );
                $labo = $item->getLabo();
                if( $labo != null )
                    $this->setPrjLLabo( Functions::string_conversion( $labo->getAcroLabo() ) );
                else
                    Functions::errorMessage(__METHOD__ . ':' . __LINE__ . " Le nouveau responsable " . $new . " ne fait partie d'aucun laboratoire");
                $this->setLaboResponsable($new );
            }
            elseif( $item->getResponsable() == true )
            {
                $item->setResponsable(false);
                $em->persist( $item );
            }

            /*

            if( $collaborateur->isEqualTo( $old ) )
                {
                $item->setResponsable(false);
                $em->persist( $item );
                }
            elseif( $collaborateur->isEqualTo( $new ) )
                {
                $item->setResponsable(true);
                $em->persist( $item );
                }
            */
            }
        $em->flush();
    }


    /*
     *
     * getLabo
     *
     * @return \AppBundle\Entity\Laboratoire
     */
    public function getLabo()
    {
        foreach( $this->getCollaborateurVersion() as $item )
                if( $item->getResponsable() == true )
                    return $item->getLabo();
        return null;
    }

    public function hasRapportActivite()
    {
        $rapportsActitive = $this->getProjet()->getRapportActivite();
        $annee = $this->getAnneeSession()-1; // rapport de l'année précédente
        foreach( $rapportsActitive as $rapport )
            if( $rapport->getAnnee() == $annee ) return true;
        return false;
    }

    public function getDernierRapportActitive()
    {
        $rapportsActitive = $this->getProjet()->getRapportActivite();
        $annee = $this->getAnneeSession()-1; // rapport de l'année précédente
        foreach( $rapportsActitive as $rapport )
            if( $rapport->getAnnee() == $annee ) return $rapport;
        return null;
    }

    public function getExpert()
    {
        $expertise =  $this->getOneExpertise();
        if( $expertise == null )
            return null;
        else
            return $expertise->getExpert();
    }

    // pour notifications
    public function getExperts()
    {
        $experts    =   [];
        foreach( $this->getExpertise() as $item )
            $experts[]  =  $item ->getExpert();
        return $experts;
    }

    public function hasExpert()
    {
        $expertise =  $this->getOneExpertise();
        if( $expertise == null ) return false;

        $expert = $expertise->getExpert();
        if( $expert != null )
            return true;
        else
            return false;
    }

    // pour notifications
    public function getExpertsThematique()
    {
    $thematique = $this->getPrjThematique();
    if( $thematique == null) return null;
    else return $thematique->getExpert();
    }

    public function getDemHeuresRallonge()
    {
        $demHeures  = 0;
        foreach( $this->getRallonge() as $rallonge ) $demHeures   +=  $rallonge->getDemHeures();
        return $demHeures;
    }

    public function getAttrHeuresRallonge()
    {
        $attrHeures  = 0;
        foreach( $this->getRallonge() as $rallonge ) $attrHeures   +=  $rallonge->getAttrHeures();
        return $attrHeures;
    }

    public function getAnneeSession()
    {
        return $this->getSession()->getAnneeSession() + 2000;
    }

    public function getConsommation()
    {
        return AppBundle::getRepository(Consommation::class)->findOneBy(
                                                        [
                                                        'annee'     => $this->getAnneeSession(),
                                                        'projet'    => $this->getProjet(),
                                                        ]);
    }
    public function getLibelleEtat()
    {
        return Etat::getLibelle( $this->getEtatVersion() );
    }
    public function getTitreCourt()
    {
        $titre = $this->getPrjTitre();

        if( strlen( $titre ) <= 20 )
            return $titre;
        else
            return substr( $titre, 0, 20 ) . "...";

    }

    public function getAcroLaboratoire()
    {
        return preg_replace('/^\s*([^\s]+)\s+(.*)$/','${1}',$this->getPrjLLabo() );
    }

    public function isNouvelle()
    {
        // Un projet test ne peut être renouvelé donc il est obligatoirement nouveau !
        if ($this->isProjetTest()) return true;

        $idVersion      = $this->getIdVersion();
        $anneeSession   = substr( $idVersion, 0, 2 );
        $typeSession    = substr( $idVersion, 2, 1 );
        $anneeProjet    = substr( $idVersion, 4, 2 );

        if ( $anneeProjet != $anneeSession )    return false;
        elseif( $typeSession == 'A' )           return true;

        if( 0 < AppBundle::getRepository( Version::class )->exists( $anneeSession . 'AP' . substr( $idVersion, 4) ) )
            return false; // elle existe
        else
            return true; // elle n'existe pas
    }

    public function isSigne()
    {
        $dir    =   AppBundle::getParameter('signature_directory');
        if( $dir == null )
            {
            Functions::errorMessage("Version:isSigne parameter signature_directory absent !" );
            return false;
            }
        $file   =  $dir . '/' . $this->getSession()->getIdSession() . '/' . $this->getIdVersion() . '.pdf';
        if( file_exists( $file ) && ! is_dir( $file ) )
            return true;
        else
            return false;
    }

    public function getSigne()
    {
        $dir    =   AppBundle::getParameter('signature_directory');
        if( $dir == null )
            {
            Functions::errorMessage("Version:isSigne parameter signature_directory absent !" );
            return null;
            }

        $file   =  $dir . '/' . $this->getSession()->getIdSession() . '/' . $this->getIdVersion() . '.pdf';

        if( file_exists( $file ) && ! is_dir( $file ) )
            return $file;
        else
            return null;
    }

    public function getSizeSigne()
    {
        $signe    =   $this->getSigne();
        if( $signe == null )
            return 0;
        else
            return intdiv( filesize( $signe ), 1024 );
    }

    public function hasRapport($annee = null)
    {
        //if( $this->isNouvelle() ) return true; // une nouvelle version compte comme une version avec un RA
        
        if( $annee == null )
            $annee = $this->getAnneeSession()-1;
        
        $rapportActivite    =   AppBundle::getRepository(RapportActivite::class)->findOneBy(
                                                [
                                                'projet'    => $this->getProjet(),
                                                'annee'     =>  $annee,
                                                ]);

        if( $rapportActivite == null ) return false;
        if( $this->getRapport($annee) == null )
            return false;
        else
            return true;

       
    }

    /* Renvoie le chemin vers le rapport d'activité 
     * 
     * Si $annee==null, calcule l'année précédente l'année de la session
     * (OK pour sessions de type A !)
     * 
     * */
    public function getRapport($annee = null)
    {
        $rapport_directory = AppBundle::getParameter('rapport_directory');
        
        if ( $annee == null )
            $annee  = $this->getAnneeSession()-1;
            
        $dir    =  $rapport_directory;
        if( $dir == null )
        {
            Functions::errorMessage(__METHOD__ . ":" . __LINE__ . " parameter rapport_directory absent !" );
            return null;
        }
            
        $file   =  $dir . '/' . $annee . '/' . $annee . $this->getProjet()->getIdProjet() . '.pdf';
        if( file_exists( $file ) && ! is_dir( $file ) )
            return $file;

        else
            Functions::warningMessage(__METHOD__ . ":" . __LINE__ . " fichier n'existe pas " . $file );
            
        return null;
    }

   

    public function getSizeRapport()
    {
        $rapportActivite    =   AppBundle::getRepository(RapportActivite::class)->findOneBy(
                                                [
                                                'projet'    => $this->getProjet(),
                                                'annee'     =>  $this->getAnneeSession()-1,
                                                ]);
        if( $rapportActivite    !=  null    )
            return  intdiv($rapportActivite->getTaille(), 1024 );
        else
            return  0;
        /*
        $rapportActivite    =   $this->getRapport();
        if( $rapportActivite == null )
            return 0;
        else
            return intdiv(filesize( $rapportActivite ), 1024 );
        */
    }


    // calcul de la consommation à partir de la table Consommation sur toute l'année

    public function getConso()
    {
        $consommation   =   $this->getConsommation();
        $conso          =   0;

        if( $consommation != null )
                 for ($i = 1; $i <= 12; $i++)
                 {
                    if( $i < 10 )
                        $methodName = 'getM0'.$i;
                    else
                        $methodName ='getM'.$i;
                    $c = $consommation->$methodName();
                    if( $c != null && $c > $conso ) $conso  =   $c;
                   }

        return $conso;
    }

    // calcul de la consommation à partir de la table Consommation juste pour une session

    public function getConsoSession()
    {
        $consommation   =   $this->getConsommation();
        $conso          =   0;

        if( $consommation == null )
            return 0;
        elseif( $this->typeSession() == "A" )
                 for ($i = 1; $i <= 6; $i++)
                 {
                 $methodName = 'getM0'.$i;
                 $c = $consommation->$methodName();
                 if( $c != null && $c > $conso ) $conso  =   $c;
                 }
        elseif( $this->typeSession() == "B" )
                {
                for ($i = 7; $i <= 12; $i++)
                 {
                    if( $i < 10 )
                        $methodName = 'getM0'.$i;
                    else
                        $methodName ='getM'.$i;

                    $c = $consommation->$methodName();
                    if( $c != null && $c > $conso ) $conso  =   $c;

                   }
                $c06    =  $consommation->getM06();
                if( $c06 != null )
                    $conso  =   $conso - $c06;
                }
        if( $conso < 0 )
            Functions::errorMessage(__METHOD__ . ':' . __LINE__ . " Consommation de la version " . $version . " est négative");

        return $conso;
    }

    ////////////////////////////////////////////////

    public function getQuota()
    {
        $consommation   =   $this->getConsommation();

        if( $consommation != null )
            return $consommation->getLimite();
        else
            return 0;
    }

    // MetaEtat d'une version (et du projet associé)
    // Ne sert que pour l'affichage des états de version
    public function getMetaEtat()
    {
        $projet =  $this->getProjet();
        if( $projet != null )
        {
            $etat_projet = $projet->getEtatProjet();

            if      (   $etat_projet    ==  Etat::EN_STANDBY    )   return 'STANDBY'; //En attente, renouvellement possible
            elseif  (   $etat_projet    ==  Etat::TERMINE       )   return 'TERMINE';
        }

        $etat_version   =   $this->getEtatVersion();

        if      (   $etat_version   ==  Etat::ANNULE                )   return 'ANNULE';
        elseif  (   $etat_version   ==  Etat::EDITION_DEMANDE       )   return 'EDITION';
        elseif  (   $etat_version   ==  Etat::EDITION_TEST          )   return 'EDITION';
        elseif  (   $etat_version   ==  Etat::EDITION_EXPERTISE     )   return 'EXPERTISE';
        elseif  (   $etat_version   ==  Etat::EXPERTISE_TEST        )   return 'EXPERTISE';
        elseif  (   $etat_version   ==  Etat::EN_STANDBY            )   return 'STANDBY';
        elseif  (   $etat_version   ==  Etat::TERMINE               )   return 'TERMINE';
        elseif  (   $this->getAttrAccept()   ==  true            )
        {
            $session = Functions::getSessionCourante();
            if( $session->getEtatSession() == Etat::EDITION_DEMANDE &&  $session->getLibelleTypeSession() === 'A' )
                return 'NONRENOUVELE'; // Non renouvelé
            else
                return 'ACCEPTE'; // Projet ou rallonge accepté par le comité d'attribution
        }
        else    return 'REFUSE';
    }

    //
    // supprimer collaborateur
    //

    public function supprimerCollaborateur(Individu $individu)
    {
        foreach( $this->getCollaborateurVersion() as $item )
            if($item->getCollaborateur() == null )
                Functions::errorMessage('Version:supprimerCollaborateur collaborateur null pour CollaborateurVersion ' . $item);
            elseif( $item->getCollaborateur()->isEqualTo($individu ) )
                {
                Functions::debugMessage('Version:supprimerCollaborateur CollaborateurVersion ' . $item . ' supprimé pour '. $individu);
                $em = AppBundle::getManager();
                $em->persist( $item );
                $em->remove( $item );
                $em->flush();
                }
    }

    // modifier login d'un collaborateur

    public function modifierLogin(Individu $individu, $login)
    {
        foreach( $this->getCollaborateurVersion() as $item )
            if($item->getCollaborateur() == null )
                Functions::errorMessage('Version:modifierLogin collaborateur null pour CollaborateurVersion ' . $item);
            elseif( $item->getCollaborateur()->isEqualTo($individu ) )
                {
                $item->setLogin( $login );
                $em = AppBundle::getManager();
                $em->persist( $item );
                $em->flush();
                }
    }

    public function isCollaborateur(Individu $individu = null)
    {
        if( $individu == null ) $individu = AppBundle::getUser();
        if( $individu == null ) return false;

        foreach( $this->getCollaborateurVersion() as $item )
            if($item->getCollaborateur() == null )
                Functions::errorMessage('Version:isCollaborateur collaborateur null pour CollaborateurVersion ' . $item);
            elseif( $item->getCollaborateur()->isEqualTo($individu ) )
                return true;

        return false;
    }

    public function isResponsable(Individu $individu = null)
    {
        if( $individu == null ) $individu = AppBundle::getUser();
        if( $individu == null ) return false;

        foreach( $this->getCollaborateurVersion() as $item )
            if($item->getCollaborateur() == null )
                Functions::errorMessage('Version:isCollaborateur collaborateur null pour CollaborateurVersion ' . $item);
            elseif( $item->getCollaborateur()->isEqualTo($individu ) && $item->getResponsable() == true )
                return true;

        return false;
    }

    public function isExpert(Individu $individu = null)
    {
        if( $individu == null ) $individu = AppBundle::getUser();
        if( $individu == null ) return false;

        foreach( $this->getExpertise() as $expertise )
                {
                $expert =  $expertise->getExpert();

                if( $expert == null )
                    Functions::errorMessage("Version:isExpert Expert null dans l'expertise " . $item);
                elseif( $expert->isEqualTo($individu) )
                    return true;
                }

        return false;
    }

    public function isExpertThematique(Individu $individu = null)
    {
        if( $individu == null ) $individu = AppBundle::getUser();
        if( $individu == null ) return false;

        //Functions::debugMessage(__METHOD__ . " thematique : " . Functions::show($thematique) );


            $thematique = $this->getPrjThematique();
            if( $thematique != null )
                foreach( $thematique->getExpert() as $expert )
                    if( $expert->isEqualTo($individu) )
                        return true;


        return false;
    }

    //////////////////////////////////

    public function typeSession()
    {
        return substr( $this->getIdVersion(), 2, 1 );
    }

    ////////////////////////////////////

    public function versionPrecedente()
    {
    // pas de version précédente
    $versions   =  $this->getProjet()->getVersion();
    if ( count( $versions ) <= 1 ) return null;

    $versions   =   $versions->toArray();
    usort($versions,
            function(Version $b, Version $a){ return strcmp( $a->getIdVersion(), $b->getIdVersion()); }
            );

    //Functions::debugMessage( __METHOD__ .':'. __LINE__ . " version ID = " . $versions[1] );
    return $versions[1];
    }

    //////////////////////////////////////////////

    public function anneeRapport()
    {
        $anneeRapport = 0;
        $myAnnee    =  substr( $this->getIdVersion(), 0, 2 );
        foreach( $this->getProjet()->getVersion() as $version )
        {
            $annee = substr( $version->getIdVersion(), 0, 2 );
            if( $annee < $myAnnee )
                $anneeRapport = max( $annee, $anneeRapport );
        }

        if( $anneeRapport < 10 && $anneeRapport > 0 )
            return '200' . $anneeRapport ;
        elseif( $anneeRapport >= 10 )
            return '20' . $anneeRapport ;
        else
            return '0';
    }


    ///////////////////////////////////////////////

    public function getOneExpertise()
    {
    $expertises =   $this->getExpertise()->toArray();
    if( $expertises !=  null )
        {
        $expertise  =   current( $expertises );

        //Functions::debugMessage(__METHOD__ . " expertise = " . Functions::show( $expertise )
        //    . " expertises = " . Functions::show( $expertises ));
        return $expertise;
        }
    else
        {
        Functions::noticeMessage(__METHOD__ . " version " . $this . " n'a pas d'expertise !");
        return null;
        }
    }

    //////////////////////////////////////////////////

    public function getFullAnnee()
    {
    return '20' . substr( $this->getIdVersion(), 0, 2 );
    }

    //////////////////////////////////////////////////

    public function isProjetTest()
    {
    $projet =   $this->getProjet();
    if( $projet == null )
        {
        Functions::errorMessage(__METHOD__ . ":" . __LINE__ . " version " . $this . " n'est pas associée à un projet !");
        return false;
        }
    else
        return $projet->isProjetTest();
    }

    ///////////////////////////////////////////////////

    public function isEdited()
    {
    $etat   =   $this->getEtatVersion();
    return $etat == Etat::EDITION_DEMANDE || $etat == Etat::EDITION_TEST;
    }

    ///////////////////////////////////////////////////

    public function getData()
    {

    //if( $this->getIdVersion()== '18BP18045' )
    //    Functions::debugMessage(__METHOD__ . ":" . __LINE__ . " La politique de la version " . $this->getIdVersion() . " est (" . $this->getPolitique() .")");
    //return AppBundle::getPolitique( $this->getPolitique() )->getData( $this );

    if( $this->getPolitique() == Politique::POLITIQUE || Politique::getLibelle( $this->getPolitique() ) == 'UNKNOWN' )
        $politique = Politique::DEFAULT_POLITIQUE;
    else
       $politique = $this->getPolitique();

    //if( $this->getPolitique() == 2 )
    //    Functions::debugMessage(__METHOD__ . ":" . __LINE__ . " La politique de la version " . $this->getIdVersion() . " est (" . $this->getPolitique() .")");

    //return AppBundle::getPolitique( $this->getPolitique() )->getData( $this );
    return AppBundle::getPolitique( $politique )->getData( $this );
    }

    ////////////////////////////////////////////

    public function getAcroEtablissement()
    {
    $responsable = $this->getResponsable();
    if( $responsable == null ) return "";

    $etablissement  =   $responsable->getEtab();
    if( $etablissement == null ) return "";

    return $etablissement->__toString();
    }

    ////////////////////////////////////////////

    public function getAcroThematique()
    {
    $thematique = $this->getPrjThematique();
    if( $thematique == null )
        return "sans thématique";
    else
        return $thematique->__toString();
    }
    ////////////////////////////////////////////

    public function getAcroMetaThematique()
    {
    $thematique = $this->getPrjThematique();
    if( $thematique == null ) return "sans thématique";

    $metathematique =   $thematique->getMetaThematique();
    if( $metathematique == null )
        return $thematique->__toString() . " sans métathématique";
    else
        return  $thematique->getMetaThematique()->__toString();
    }

    ////////////////////////////////////////////////////
    public function setLaboResponsable( $moi = null )
    {
    if( $moi == null || ! ( $moi instanceof Individu ) )
        $moi = AppBundle::getUser();

    if( $moi == null )
        {
        Functions::errorMessage(__METHOD__ . ':' . __LINE__ . " Il faut être connecté pour modifier une version");
        return;
        }

    $labo = $moi->getLabo();

    if( $labo != null )
        $this->setPrjLLabo( Functions::string_conversion( $labo ) );
    else
        Functions::errorMessage(__METHOD__ . ':' . __LINE__ . " Le nouveau responsable " . $moi . " ne fait partie d'aucun laboratoire");
    }
}
