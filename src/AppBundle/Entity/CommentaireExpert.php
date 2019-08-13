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
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Expertise
 *
 * @ORM\Table(name="commentaireExpert", uniqueConstraints={@ORM\UniqueConstraint(columns={"annee", "id_expert"})}, indexes={@ORM\Index(columns={"id_expert"}), @ORM\Index(columns={"annee"})} )
 * @ORM\Entity(repositoryClass="AppBundle\Repository\CommentaireExpertRepository")
 *
 */
class CommentaireExpert
{
    /**
     * @var string
     *
     * Commentaire général sur les projets expertisés cette année
     *
     * @ORM\Column(name="commentaire", type="text", length=65535, nullable=false)
     */
    private $commentaire = "";

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="annee", type="integer", nullable=false)
     */
    private $annee;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="maj_stamp", type="datetime", nullable=false)
     */
    private $majStamp;

    /**
     * @var \AppBundle\Entity\Individu
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Individu")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_expert", referencedColumnName="id_individu")
     * })
     */
    private $expert;

    public function __toString()
    {
        return 'Commentaire '. $this->getId() . " par l'expert " . $this->getExpert();
    }

    /**
     * Set annee
     *
     * @param integer $annee
     *
     * @return Expertise
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
     * Set commentaire
     *
     * @param string $commentaire
     *
     * @return Expertise
     */
    public function setCommentaire($commentaire)
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    /**
     * Get commentaire
     *
     * @return string
     */
    public function getCommentaire()
    {
        return $this->commentaire;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set expert
     *
     * @param \AppBundle\Entity\Individu $idExpert
     *
     * @return Expertise
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

}
