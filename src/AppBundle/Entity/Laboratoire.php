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
 * Laboratoire
 *
 * @ORM\Table(name="laboratoire")
 * @ORM\Entity
 */
class Laboratoire
{
    /**
     * @var integer
     *
     * @ORM\Column(name="numero_labo", type="integer", nullable=false)
     */
    private $numeroLabo = '99999';

    /**
     * @var string
     *
     * @ORM\Column(name="acro_labo", type="string", length=15, nullable=false)
     */
    private $acroLabo = '';

    /**
     * @var string
     *
     * @ORM\Column(name="nom_labo", type="string", length=100, nullable=false)
     */
    private $nomLabo = '';

    /**
     * @var integer
     *
     * @ORM\Column(name="id_labo", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idLabo;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="\AppBundle\Entity\Individu", mappedBy="labo")
     */     
    private $individu;
    
    public function __toString()
    {
        if( $this->getAcroLabo() != null && $this->getNomLabo() != null)
            return $this->getAcroLabo() . ' - ' . $this->getNomLabo();
        elseif( $this->getAcroLabo() != null )
            return $this->getAcroLabo();
        elseif( $this->getNomLabo() != null )
            return $this->getNomLabo();
        else
            return $this->getIdLabo();
    }
    
    public function getId(){ return $this->getIdLabo(); }
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->individu = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set numeroLabo
     *
     * @param integer $numeroLabo
     *
     * @return Laboratoire
     */
    public function setNumeroLabo($numeroLabo)
    {
        $this->numeroLabo = $numeroLabo;

        return $this;
    }

    /**
     * Get numeroLabo
     *
     * @return integer
     */
    public function getNumeroLabo()
    {
        return $this->numeroLabo;
    }

    /**
     * Set acroLabo
     *
     * @param string $acroLabo
     *
     * @return Laboratoire
     */
    public function setAcroLabo($acroLabo)
    {
        $this->acroLabo = $acroLabo;

        return $this;
    }

    /**
     * Get acroLabo
     *
     * @return string
     */
    public function getAcroLabo()
    {
        return $this->acroLabo;
    }

    /**
     * Set nomLabo
     *
     * @param string $nomLabo
     *
     * @return Laboratoire
     */
    public function setNomLabo($nomLabo)
    {
        $this->nomLabo = $nomLabo;

        return $this;
    }

    /**
     * Get nomLabo
     *
     * @return string
     */
    public function getNomLabo()
    {
        return $this->nomLabo;
    }

    /**
     * Get idLabo
     *
     * @return integer
     */
    public function getIdLabo()
    {
        return $this->idLabo;
    }

    /**
     * Add individu
     *
     * @param \AppBundle\Entity\Individu $individu
     *
     * @return Laboratoire
     */
    public function addIndividu(\AppBundle\Entity\Individu $individu)
    {
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

    //////////////////////////////////////////////////////////////////////

    public function isLaboRegional()
    {
        return $this->idLabo > 1;
    }
}
