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

/**
 * Thematique
 *
 * @ORM\Table(name="thematique")
 * @ORM\Entity
 */
class Thematique
{
    /**
     * @var string
     *
     * @ORM\Column(name="libelle_thematique", type="string", length=200, nullable=false)
     */
    private $libelleThematique;

     /**
     * @var \AppBundle\Entity\MetaThematique
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\MetaThematique")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_meta_thematique", referencedColumnName="id_meta_thematique")
     * })
     */
    private $metaThematique;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_thematique", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idThematique;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Individu", inversedBy="idThematique")
     * @ORM\JoinTable(name="thematiqueExpert",
     *   joinColumns={
     *     @ORM\JoinColumn(name="id_thematique", referencedColumnName="id_thematique")
     *   },
     *   inverseJoinColumns={
     *     @ORM\JoinColumn(name="id_expert", referencedColumnName="id_individu")
     *   }
     * )
     */
    private $expert;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="\AppBundle\Entity\Version", mappedBy="thematique")
     */
    private $version;


    //////////////////////////////////////////////////////////

    public function getId(){ return $this->getIdThematique(); }
    public function __toString(){ return $this->getLibelleThematique(); }

    //////////////////////////////////////////////////////////

    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->expert = new \Doctrine\Common\Collections\ArrayCollection();
        $this->version = new \Doctrine\Common\Collections\ArrayCollection();
        //$this->projetTest = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set libelleThematique
     *
     * @param string $libelleThematique
     *
     * @return Thematique
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
     * Get idThematique
     *
     * @return integer
     */
    public function getIdThematique()
    {
        return $this->idThematique;
    }

    /**
     * Set metaThematique
     *
     * @param \AppBundle\Entity\MetaThematique $metaThematique
     *
     * @return Thematique
     */
    public function setMetaThematique(\AppBundle\Entity\MetaThematique $metaThematique = null)
    {
        $this->metaThematique = $metaThematique;

        return $this;
    }

    /**
     * Get metaThematique
     *
     * @return \AppBundle\Entity\MetaThematique
     */
    public function getMetaThematique()
    {
        return $this->metaThematique;
    }

    /**
     * Add expert
     *
     * @param \AppBundle\Entity\Individu $expert
     *
     * @return Thematique
     */
    public function addExpert(\AppBundle\Entity\Individu $expert)
    {
        if( ! $this->expert->contains($expert) )
            $this->expert[] = $expert;

        return $this;
    }

    /**
     * Remove expert
     *
     * @param \AppBundle\Entity\Individu $expert
     */
    public function removeExpert(\AppBundle\Entity\Individu $expert)
    {
        $this->expert->removeElement($expert);
    }

    /**
     * Get expert
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getExpert()
    {
        return $this->expert;
    }

    /**
     * Add version
     *
     * @param \AppBundle\Entity\Version $version
     *
     * @return Thematique
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

    

}
