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
use Symfony\Component\Validator\Constraints as Assert;

use AppBundle\Form\ChoiceList\ExpertChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Rallonge
 *
 * @ORM\Table(name="rallonge", indexes={@ORM\Index(name="id_version", columns={"id_version"}), @ORM\Index(name="num_rallonge", columns={"id_rallonge"}), @ORM\Index(name="etat_rallonge", columns={"etat_rallonge"})})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\RallongeRepository")
 * @Assert\Expression("this.getNbHeuresAtt() > 0  or  this.getValidation() != 1",
 *  message="Si vous ne voulez pas attribuer des heures pour cette demande, choisissez ""Refuser""",groups={"expertise"})
 * @Assert\Expression("this.getNbHeuresAtt() == 0  or  this.getValidation() !=  0",
 *  message="Si vous voulez attribuer des heures pour cette demande, choisissez ""Accepter""",groups={"expertise"})
 * @ORM\HasLifecycleCallbacks()
 */
class Rallonge
{
    /**
     * @var integer
     *
     * @ORM\Column(name="etat_rallonge", type="integer", nullable=false)
     */
    private $etatRallonge;

    /**
     * @var integer
     *
     * @ORM\Column(name="dem_heures", type="integer", nullable=true)
     * @Assert\GreaterThan(0,message="Vous devez demander des heures.")
     * @Assert\GreaterThanOrEqual(0,message="Vous ne pouvez pas demander un nombre d'heures négatif.")
     */
    private $demHeures;

    /**
     * @var integer
     *
     * @ORM\Column(name="attr_heures", type="integer", nullable=true)
     */
    private $attrHeures;

    /**
     * @var string
     *
     * @ORM\Column(name="prj_justif_rallonge", type="text", length=65535, nullable=true)
     * @Assert\NotBlank(message="Vous n'avez pas rempli la justification scientifique")
     */
    private $prjJustifRallonge;

    /**
     * @var integer
     *
     * @ORM\Column(name="maj_ind", type="integer", nullable=false)
     */
    private $majInd = '0';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="maj_stamp", type="datetime", nullable=false)
     */
    private $majStamp;

    /**
     * @var boolean
     *
     * @ORM\Column(name="attr_accept", type="boolean", nullable=false)
     */
    private $attrAccept = '1';

    /**
     * @var string
     *
     * @ORM\Column(name="id_rallonge", type="string", length=15)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $idRallonge;

    /**
     * @var \AppBundle\Entity\Version
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Version")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_version", referencedColumnName="id_version")
     * })
     */
    private $version;

    ////////////////////////////////////////////////////////

    /**
     * @var integer
     *
     * @ORM\Column(name="nb_heures_att", type="integer", nullable=true)
     * @Assert\GreaterThanOrEqual(value = 0,message="Vous ne pouvez pas attribuer un nombre d'heures négatif.", groups={"expertise","president"})
     */
    private $nbHeuresAtt;

    /**
     * @var string
     *
     * @ORM\Column(name="commentaire_interne", type="text", length=65535, nullable=true)
     * @Assert\NotBlank(message="Vous n'avez pas rempli le commentaire interne", groups={"expertise","president"})
     */
    private $commentaireInterne;

    /**
     * @var string
     *
     * @ORM\Column(name="commentaire_externe", type="text", length=65535, nullable=true)
     * @Assert\NotBlank(message="Vous n'avez pas rempli le commentaire interne", groups={"president"})
     */
    private $commentaireExterne;

    /**
     * @var boolean
     *
     * @ORM\Column(name="validation", type="boolean", nullable=false)
     *
     */
    private $validation = true;

    /**
     * @var \AppBundle\Entity\Individu
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Individu")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_expert", referencedColumnName="id_individu")
     * })
     */
    private $expert;


    /////////

    /**
    * @ORM\PrePersist
    */
    public function setInitialMajStamp()
    {
    $this->majStamp = new \DateTime();
    }

    /**
    * @ORM\PreUpdate
    */
    public function setUpdateMajStamp()
    {
    $this->majStamp = new \DateTime();
    }

    /**
    * @ORM\PostLoad
    */
    public function convert()
    {
     if( $this->getEtatRallonge() == Etat::ACTIF && $this->getAttrHeures() == null )
        {
        $this->setAttrHeures( $this->getNbHeuresAtt() );
        Functions::infoMessage(__METHOD__ . ':' . __LINE__ . ' Fixture partielle de Rallonge ' . $this->getIdRallonge() );
        }
    }

    ////////////////////////////////////////////////////////

    /**
     * Constructor
     */
    public function __construct()
    {
        // $this->majStamp             =   new \DateTime("now");
    }



    /////////////////////////////////////////////////////////////////////////////


    public function getId(){ return $this->getIdRallonge(); }
    public function __toString(){ return $this->getIdRallonge(); }

    ////////////////////////////////////////////////////////////////////////////



    /**
     * Set etatRallonge
     *
     * @param integer $etatRallonge
     *
     * @return Rallonge
     */
    public function setEtatRallonge($etatRallonge)
    {
        $this->etatRallonge = $etatRallonge;

        return $this;
    }

    /**
     * Get etatRallonge
     *
     * @return integer
     */
    public function getEtatRallonge()
    {
        return $this->etatRallonge;
    }

    /**
     * Set demHeures
     *
     * @param integer $demHeures
     *
     * @return Rallonge
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
     * @return Rallonge
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
     * Set prjJustifRallonge
     *
     * @param string $prjJustifRallonge
     *
     * @return Rallonge
     */
    public function setPrjJustifRallonge($prjJustifRallonge)
    {
        $this->prjJustifRallonge = $prjJustifRallonge;

        return $this;
    }

    /**
     * Get prjJustifRallonge
     *
     * @return string
     */
    public function getPrjJustifRallonge()
    {
        return $this->prjJustifRallonge;
    }

    /**
     * Set majInd
     *
     * @param integer $majInd
     *
     * @return Rallonge
     */
    public function setMajInd($majInd)
    {
        $this->majInd = $majInd;

        return $this;
    }

    /**
     * Get majInd
     *
     * @return integer
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
     * @return Rallonge
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
     * Set attrAccept
     *
     * @param boolean $attrAccept
     *
     * @return Rallonge
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
     * Set idRallonge
     *
     * @param string $idRallonge
     *
     * @return Rallonge
     */
    public function setIdRallonge($idRallonge)
    {
        $this->idRallonge = $idRallonge;

        return $this;
    }

    /**
     * Get idRallonge
     *
     * @return string
     */
    public function getIdRallonge()
    {
        return $this->idRallonge;
    }

    /**
     * Set version
     *
     * @param \AppBundle\Entity\Version $version
     *
     * @return Rallonge
     */
    public function setVersion(\AppBundle\Entity\Version $version = null)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version
     *
     * @return \AppBundle\Entity\Version
     */
    public function getVersion()
    {
        return $this->version;
    }

    /////////////////////////////////////////////////////////////////////



    /**
     * Set nbHeuresAtt
     *
     * @param integer $nbHeuresAtt
     *
     * @return Rallonge
     */
    public function setNbHeuresAtt($nbHeuresAtt)
    {
        $this->nbHeuresAtt = $nbHeuresAtt;

        return $this;
    }

    /**
     * Get nbHeuresAtt
     *
     * @return integer
     */
    public function getNbHeuresAtt()
    {
        return $this->nbHeuresAtt;
    }

    /**
     * Set commentaireInterne
     *
     * @param string $commentaireInterne
     *
     * @return Rallonge
     */
    public function setCommentaireInterne($commentaireInterne)
    {
        $this->commentaireInterne = $commentaireInterne;

        return $this;
    }

    /**
     * Get commentaireInterne
     *
     * @return string
     */
    public function getCommentaireInterne()
    {
        return $this->commentaireInterne;
    }

    /**
     * Set commentaireExterne
     *
     * @param string $commentaireExterne
     *
     * @return Rallonge
     */
    public function setCommentaireExterne($commentaireExterne)
    {
        $this->commentaireExterne = $commentaireExterne;

        return $this;
    }

    /**
     * Get commentaireExterne
     *
     * @return string
     */
    public function getCommentaireExterne()
    {
        return $this->commentaireExterne;
    }

    /**
     * Set validation
     *
     * @param boolean $validation
     *
     * @return Rallonge
     */
    public function setValidation($validation)
    {
        $this->validation = $validation;

        return $this;
    }

    /**
     * Get validation
     *
     * @return boolean
     */
    public function getValidation()
    {
        return $this->validation;
    }

    /**
     * Set rallonge
     *
     * @param \AppBundle\Entity\Rallonge
     *
     * @return Rallonge
     */
    public function setRallonge($rallonge)
    {
        $this->rallonge = $rallonge;

        return $this;
    }

    /**
     * Get rallonge
     *
     * @return \AppBundle\Entity\Rallonge
     */
    public function getRallonge()
    {
        return $this->rallonge;
    }

    /**
     * Set expert
     *
     * @param \AppBundle\Entity\Individu $expert
     *
     * @return Rallonge
     */
    public function setExpert(\AppBundle\Entity\Individu $expert = null)
    {
        $this->expert = $expert;

        return $this;
    }

    /**
     * Get expert
     *
     * @return \AppBundle\Entity\Individu
     */
    public function getExpert()
    {
        return $this->expert;
    }

    /////////////////////////////////////////////////////////////////////////////

    // pour workflow
    public function getObjectState()
    {
        return $this->getEtatRallonge();
    }

    public function setObjectState($state)
    {
        $this->setEtatRallonge($state);
        AppBundle::getManager()->flush();
        return $this;
    }

    public function getResponsables()
    {
        $version = $this->getVersion();
        if( $version != null )
            return $version->getResponsables();
        else
            return [];
    }

    // pour notifications
    public function getOneExpert()
    {
        $expert = $this->getExpert();
        if( $expert == null )
            return null;
        else
            //return $expert[0];
            return $expert;
    }

    // pour notifications
    public function getExperts()
    {
        return [ $this->getExpert() ];
    }

    // pour notifications
    public function getExpertsThematique()
    {
    $version    =   $this->getVersion();
    if( $version    ==  null    ) return [];

    $thematique = $version->getThematique();
    if( $thematique == null) return [];
    else return $thematique->getExpert();
    }

    //////////////////////////////

    public function getMetaEtat()
    {
    $etat = $this->getEtatRallonge();
    if      (   $etat    ==  Etat::EDITION_DEMANDE    )     return  'EDITION';
    elseif  (   $etat    ==  Etat::EDITION_EXPERTISE  )     return  'EXPERTISE';
    elseif  (   $etat    ==  Etat::DESAFFECTE  )            return  'EXPERTISE';
    elseif  (   $etat    ==  Etat::EN_ATTENTE  )            return  'ATTENTE';
    elseif  (   $this->getAttrAccept() == true )            return  'ACCEPTE';
    elseif  (   $this->getAttrAccept() == false )           return  'REFUSE';
    else    return '';
    }

    //////////////

    public function getLibelleEtatRallonge()
    {
    return Etat::getLibelle( $this->getEtatRallonge() );
    }

    ///////////////////////////////////////////////////////////////////////////////
    //
    // préparation du formulaire du choix d'expert
    //

    public function getExpertForm()
    {

	    $expert = $this->getExpert();
	    $version    =   $this->getVersion();

	    if( $version != null )
	        $projet =   $version->getProjet();
	    else
	        $projet =   null;

	    $collaborateurs = AppBundle::getRepository(CollaborateurVersion::class)->getCollaborateurs($projet);

	    if( $expert ==  null && $projet != null)
        {
	        //$expert  =  $projet->proposeExpert( $collaborateurs );
	        // L'expert proposé est celui de la dernière expertise du projet, s'il y en a plusieurs
	        // NOTE - plantage si aucune expertise, mais cela ne devrait jamais arriver
	        $expertises = $version -> getExpertise()->toArray();
	        $expert     = end($expertises)->getExpert();
	        Functions::debugMessage(__METHOD__ . ":" . __LINE__ ." nouvel expert proposé à la rallonge " . $this . " : " . Functions::show( $expert ) );
        }


	    return AppBundle::getContainer()->get( 'form.factory')
	            ->createNamedBuilder(   'expert'.$this->getIdRallonge() , FormType::class, null  ,  ['csrf_protection' => false ])
	                ->add('expert', ChoiceType::class,
	                    [
	                'multiple'  =>  false,
	                'required'  =>  false,
	                'label'     => '',
	                //'choices'       => $choices, // cela ne marche pas à cause d'un bogue de symfony
	                'choice_loader' => new ExpertChoiceLoader($collaborateurs), // nécessaire pour contourner le bogue de symfony
	                'data'          => $expert,
	                //'choice_value' => function (Individu $entity = null) { return $entity->getIdIndividu(); },
	                'choice_label' => function ($individu)
	                   { return $individu->__toString(); },
	                    ])
	                    ->getForm();
    }

    ////////////////////////////////////////////////////////////////////////

    public function isExpert(Individu $individu = null)
    {
        if( $individu == null ) $individu = AppBundle::getUser();
        if( $individu == null ) return false;

        $expert = $this->getExpert();

        if( $expert == null )
            {
            Functions::warningMessage(__METHOD__ . ":" . __LINE__ . " rallonge " . $this->__toString() . " n'a pas d'expert ");
            return false;
            }
        elseif( $expert->isEqualTo($individu) )
            return true;
        else
            return false;
    }

    ////////////////////////////////////////////////////////////////////////

    public function isFinalisable()
    {
    if( $this->getEtatRallonge() == Etat::EN_ATTENTE )
        return true;
    else
        return false;
    }
}
