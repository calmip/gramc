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
 * Sso
 *
 * @ORM\Table(name="statut", indexes={@ORM\Index(name="id_statut", columns={"id_statut"})})
 * @ORM\Entity
 */
class Statut
{
    /**
     * @var string
     *
     * @ORM\Column(name="id_statut", type="smallint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $idStatut;

    /**
     * @var string
     *
     * @ORM\Column(name="libelle_statut", type="string", length=50, nullable=false)
     */
    private $libelleStatut;

     /**
     * @var boolean
     *
     * @ORM\Column(name="permanent", type="boolean", nullable=false)
     */
    private $permanent = false;
    
    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="\AppBundle\Entity\Individu", mappedBy="statut")
     */
    private $individu;

    //////////////////////////////////////////////////////

    public function __toString()    {   return $this->getLibelleStatut();   }
    public function getId()         {   return $this->getIdStatut();        }

    /**
     * Set idStatut
     *
     * @param integer $idStatut
     *
     * @return Statut
     */
    public function setIdStatut($idStatut)
    {
        $this->idStatut = $idStatut;

        return $this;
    }

    //////////////////////////////////////////////////
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->individu = new \Doctrine\Common\Collections\ArrayCollection();
    }
    /**
     * Get idStatut
     *
     * @return integer
     */
    public function getIdStatut()
    {
        return $this->idStatut;
    }
    
    /**
     * Set libelleStatut
     *
     * @param string $libelleStatut
     *
     * @return Statut
     */
    public function setLibelleStatut($libelleStatut)
    {
        $this->libelleStatut = $libelleStatut;

        return $this;
    }

    /**
     * Get libelleStatut
     *
     * @return string
     */
    public function getLibelleStatut()
    {
        return $this->libelleStatut;
    }

    /**
     * Set permanent
     *
     * @param boolean $permanent
     *
     * @return Statut
     */
    public function setPermanent($permanent)
    {
        $this->permanent = $permanent;

        return $this;
    }

    /**
     * Get permanent
     *
     * @return boolean
     */
    public function getPermanent()
    {
        return $this->permanent;
    }

    /**
     * Is permanent
     *
     * @return boolean
     */
    public function isPermanent()
    {
        return $this->permanent;
    }

    /**
     * Add individu
     *
     * @param \AppBundle\Entity\Individu $individu
     *
     * @return Statut
     */
    public function addIndividu(\AppBundle\Entity\Individu $individu)
    {
        if (!$this->individu->contains($individu))
            $this->individu[] = $individu;

        return $this;
    }

    /**
     * Remove individu
     *
     * @param \AppBundle\Entity\Individu $individu
     */
    public function removeIndividu(\AppBundle\Entity\Individu $individu)
    {
        $this->individu->removeElement($individu);
    }

    /**
     * Get individu
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getIndividu()
    {
        return $this->individu;
    }

}
