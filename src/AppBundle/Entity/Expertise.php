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
 * @ORM\Table(name="expertise", uniqueConstraints={@ORM\UniqueConstraint(name="id_version_2", columns={"id_version", "id_expert"})}, indexes={@ORM\Index(name="version_expertise_fk", columns={"id_version"}), @ORM\Index(name="expert_expertise_fk", columns={"id_expert"}), @ORM\Index(name="id_version", columns={"id_version"}), @ORM\Index(name="id_expert", columns={"id_expert"})})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ExpertiseRepository")
 * @Assert\Expression("this.getNbHeuresAttEte() <= this.getNbHeuresAtt()",
 *      message="Vous ne pouvez pas attribuer plus d'heures pour l'été que pour la session.")
 *
 * @Assert\Expression("this.getNbHeuresAtt() == 0  or  this.getValidation() == 1",
 *      message="Vous ne pouvez pas attribuer des heures et les refuser à la fois")
 *
 * @Assert\Expression("this.getNbHeuresAtt() > 0  or  this.getValidation() != 1",
 *      message="Si vous ne voulez pas attribuer des heures pour cette session, choisissez ""Refuser pour cette session""")
 *
 */
class Expertise
{
    /**
     * @var boolean
     *
     * true = L'expert a répondu positivement et a attribué des heures (éventuellement 0 heure si le projet est validé mais la machine surchargée)
     * false= L'expert a répondu négativement (et l'attribution est obligatoirement 0)
     *
     * @ORM\Column(name="validation", type="integer", nullable=false)
     */
    private $validation=1;

    /**
     * @var integer
     *
     * @ORM\Column(name="nb_heures_att", type="integer", nullable=false)
     * @Assert\GreaterThanOrEqual(0,message="Vous ne pouvez pas attribuer un nombre d'heures négatif.")
     */
    private $nbHeuresAtt = 0;

    /**
     * @var string
     *
     * Expertise qui sera connue du comité d'attribution uniquement
     *
     * @ORM\Column(name="commentaire_interne", type="text", length=65535, nullable=false)
     * @Assert\NotBlank(message="Vous n'avez pas rempli le commentaire pour le comité")
     */
    private $commentaireInterne = "";

    /**
     * @var string
     *
     * Expertise qui sera connue du porteur de projet
     *
     * @ORM\Column(name="commentaire_externe", type="text", length=65535, nullable=false)
     * @Assert\NotBlank(message="Vous n'avez pas rempli le commentaire pour le responsable")
     */
    private $commentaireExterne = "";

    /**
     * @var boolean
     *
     * false = Nous sommes en phase d'édition, l'expertise n'a pas encore été envoyée
     * true  = Expertise envoyée, pas de modification possible
     *
     * @ORM\Column(name="definitif", type="boolean", nullable=false)
     */
    private $definitif = false;

    /**
     * @var integer
     *
     * Seulement lors de la session de type B: ces heures doivent être consommées en Juillet-Août, sinon on pourra appliquer des pénalités
     *
     * @ORM\Column(name="nb_heures_att_ete", type="integer", nullable=false)
     * @Assert\GreaterThanOrEqual(0,message="Vous ne pouvez pas attribuer un nombre d'heures d'été négatif.")
     */
    private $nbHeuresAttEte = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \AppBundle\Entity\Version
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Version")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_version", referencedColumnName="id_version")
     * })
     */
    private $version;

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
        return 'Expertise '. $this->getId() . " par l'expert " . $this->getExpert();
    }


    /**
     * Set validation
     *
     * @param integer $validation
     *
     * @return Expertise
     */
    public function setValidation($validation)
    {
        $this->validation = $validation;

        return $this;
    }

    /**
     * Get validation
     *
     * @return integer
     */
    public function getValidation()
    {
        return $this->validation;
    }

    /**
     * Set nbHeuresAtt
     *
     * @param integer $nbHeuresAtt
     *
     * @return Expertise
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
     * @return Expertise
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
     * @return Expertise
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
     * Set definitif
     *
     * @param boolean $definitif
     *
     * @return Expertise
     */
    public function setDefinitif($definitif)
    {
        $this->definitif = $definitif;

        return $this;
    }

    /**
     * Get definitif
     *
     * @return boolean
     */
    public function getDefinitif()
    {
        return $this->definitif;
    }

    /**
     * Set nbHeuresAttEte
     *
     * @param integer $nbHeuresAttEte
     *
     * @return Expertise
     */
    public function setNbHeuresAttEte($nbHeuresAttEte)
    {
        $this->nbHeuresAttEte = $nbHeuresAttEte;

        return $this;
    }

    /**
     * Get nbHeuresAttEte
     *
     * @return integer
     */
    public function getNbHeuresAttEte()
    {
        return $this->nbHeuresAttEte;
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
     * Set version
     *
     * @param \AppBundle\Entity\Version $idVersion
     *
     * @return Expertise
     */
    public function setVersion(\AppBundle\Entity\Version $idVersion = null)
    {
        $this->version = $idVersion;

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
}
