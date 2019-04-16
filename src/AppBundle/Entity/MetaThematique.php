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
 * MetaThematique
 *
 * @ORM\Table(name="meta_thematique")
 * @ORM\Entity
 */
class MetaThematique
{
    /**
     * @var string
     *
     * @ORM\Column(name="libelle", type="string", length=200, nullable=false)
     */
    private $libelle;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_meta_thematique", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idMetaThematique;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="\AppBundle\Entity\Thematique", mappedBy="metaThematique")
     */
    private $thematique;

    public function __toString(){ return $this->getLibelle(); }    
    public function getId(){ return $this->getIdMetaThematique(); }
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->thematique = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set libelle
     *
     * @param string $libelle
     *
     * @return MetaThematique
     */
    public function setLibelle($libelle)
    {
        $this->libelle = $libelle;

        return $this;
    }

    /**
     * Get libelle
     *
     * @return string
     */
    public function getLibelle()
    {
        return $this->libelle;
    }

    /**
     * Get idMetaThematique
     *
     * @return integer
     */
    public function getIdMetaThematique()
    {
        return $this->idMetaThematique;
    }

    /**
     * Add thematique
     *
     * @param \AppBundle\Entity\Thematique $thematique
     *
     * @return MetaThematique
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
}
