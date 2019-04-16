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
 * Publication
 *
 * @ORM\Table(name="publication")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\PublicationRepository")
 */
class Publication
{
    /**
     * @var string
     *
     * @ORM\Column(name="refbib", type="text", length=65535, nullable=false)
     */
    private $refbib;

    /**
     * @var string
     *
     * @ORM\Column(name="doi", type="string", length=100, nullable=true)
     */
    private $doi;

    /**
     * @var string
     *
     * @ORM\Column(name="open_url", type="string", length=300, nullable=true)
     */
    private $openUrl;

    /**
     * @var integer
     *
     * @ORM\Column(name="annee", type="integer", nullable=false)
     */
    private $annee;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_publi", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idPubli;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Projet", mappedBy="publi")
     * 
     */
    private $projet;

    ////////////////////////////////////////////////////////////////////////

    public function __toString(){ return $this->getRefbib(); }
    
    /**
     * Get id
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getId()
    {
        return $this->getIdPubli();
    }

    ////////////////////////////////////////////////////////////////////////
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->projet = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set refbib
     *
     * @param string $refbib
     *
     * @return Publication
     */
    public function setRefbib($refbib)
    {
        $this->refbib = $refbib;

        return $this;
    }

    /**
     * Get refbib
     *
     * @return string
     */
    public function getRefbib()
    {
        return $this->refbib;
    }

    /**
     * Set doi
     *
     * @param string $doi
     *
     * @return Publication
     */
    public function setDoi($doi)
    {
        $this->doi = $doi;

        return $this;
    }

    /**
     * Get doi
     *
     * @return string
     */
    public function getDoi()
    {
        return $this->doi;
    }

    /**
     * Set openUrl
     *
     * @param string $openUrl
     *
     * @return Publication
     */
    public function setOpenUrl($openUrl)
    {
        $this->openUrl = $openUrl;

        return $this;
    }

    /**
     * Get openUrl
     *
     * @return string
     */
    public function getOpenUrl()
    {
        return $this->openUrl;
    }

    /**
     * Set annee
     *
     * @param integer $annee
     *
     * @return Publication
     */
    public function setAnnee($annee)
    {
        $this->annee = $annee;

        return $this;
    }

    /**
     * Get annee
     *
     * @return integer
     */
    public function getAnnee()
    {
        return $this->annee;
    }

    /**
     * Get idPubli
     *
     * @return integer
     */
    public function getIdPubli()
    {
        return $this->idPubli;
    }

    /**
     * Set idPubli
     * 
     * @param integer $id
     * @return Publication
     */
    public function setIdPubli($id)
    {
        $this->idPubli = $id;
        return $this;
    }

    /**
     * Add projet
     *
     * @param \AppBundle\Entity\Projet $projet
     *
     * @return Publication
     */
    public function addProjet(\AppBundle\Entity\Projet $projet)
    {   
        if( ! $this->projet->contains( $projet ) )
            $this->projet[] = $projet;

        return $this;
    }

    /**
     * Remove projet
     *
     * @param \AppBundle\Entity\Projet $projet
     */
    public function removeProjet(\AppBundle\Entity\Projet $projet)
    {
        $this->projet->removeElement($projet);
    }

    /**
     * Get projet
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProjet()
    {
        return $this->projet;
    }
}
