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
 * Consommation
 *
 * @ORM\Table(name="consommation", uniqueConstraints={@ORM\UniqueConstraint(name="id_projet_3", columns={"id_projet", "annee"})}, indexes={@ORM\Index(name="id_projet", columns={"id_projet"}), @ORM\Index(name="annee", columns={"annee"}), @ORM\Index(name="annee_2", columns={"annee"}), @ORM\Index(name="id_projet_2", columns={"id_projet"})})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ConsommationRepository")
 */
class Consommation
{
    /**
     * @var string
     *
     * @ORM\Column(name="id_projet", type="string", length=6, nullable=false)
     */
    private $projet;

    /**
     * @var integer
     *
     * @ORM\Column(name="annee", type="integer", nullable=false)
     */
    private $annee;

    /**
     * @var integer
     *
     * @ORM\Column(name="limite", type="integer", nullable=false)
     */
    private $limite;

    /**
     * @var integer
     *
     * @ORM\Column(name="m01", type="integer", nullable=false)
     */
    private $m01;

    /**
     * @var integer
     *
     * @ORM\Column(name="m02", type="integer", nullable=false)
     */
    private $m02;

    /**
     * @var integer
     *
     * @ORM\Column(name="m03", type="integer", nullable=false)
     */
    private $m03;

    /**
     * @var integer
     *
     * @ORM\Column(name="m04", type="integer", nullable=false)
     */
    private $m04;

    /**
     * @var integer
     *
     * @ORM\Column(name="m05", type="integer", nullable=false)
     */
    private $m05;

    /**
     * @var integer
     *
     * @ORM\Column(name="m06", type="integer", nullable=false)
     */
    private $m06;

    /**
     * @var integer
     *
     * @ORM\Column(name="m07", type="integer", nullable=false)
     */
    private $m07;

    /**
     * @var integer
     *
     * @ORM\Column(name="m08", type="integer", nullable=false)
     */
    private $m08;

    /**
     * @var integer
     *
     * @ORM\Column(name="m09", type="integer", nullable=false)
     */
    private $m09;

    /**
     * @var integer
     *
     * @ORM\Column(name="m10", type="integer", nullable=false)
     */
    private $m10;

    /**
     * @var integer
     *
     * @ORM\Column(name="m11", type="integer", nullable=false)
     */
    private $m11;

    /**
     * @var integer
     *
     * @ORM\Column(name="m12", type="integer", nullable=false)
     */
    private $m12;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function __toString()
    {
        return $this->getProjet() . ':'. $this->getAnnee() . ':'. $this->conso();
    }

    /**
     * Set projet
     *
     * @param string $projet
     *
     * @return Consommation
     */
    public function setProjet($projet)
    {
        $this->projet = $projet;

        return $this;
    }

    /**
     * Get projet
     *
     * @return string
     */
    public function getProjet()
    {
        return $this->projet;
    }

    /**
     * Set annee
     *
     * @param integer $annee
     *
     * @return Consommation
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
     * Set limite
     *
     * @param integer $limite
     *
     * @return Consommation
     */
    public function setLimite($limite)
    {
        $this->limite = $limite;

        return $this;
    }

    /**
     * Get limite
     *
     * @return integer
     */
    public function getLimite()
    {
        return $this->limite;
    }

    /**
     * Set m01
     *
     * @param integer $m01
     *
     * @return Consommation
     */
    public function setM01($m01)
    {
        $this->m01 = $m01;

        return $this;
    }

    /**
     * Get m01
     *
     * @return integer
     */
    public function getM01()
    {
        return $this->m01;
    }

    /**
     * Set m02
     *
     * @param integer $m02
     *
     * @return Consommation
     */
    public function setM02($m02)
    {
        $this->m02 = $m02;

        return $this;
    }

    /**
     * Get m02
     *
     * @return integer
     */
    public function getM02()
    {
        return $this->m02;
    }

    /**
     * Set m03
     *
     * @param integer $m03
     *
     * @return Consommation
     */
    public function setM03($m03)
    {
        $this->m03 = $m03;

        return $this;
    }

    /**
     * Get m03
     *
     * @return integer
     */
    public function getM03()
    {
        return $this->m03;
    }

    /**
     * Set m04
     *
     * @param integer $m04
     *
     * @return Consommation
     */
    public function setM04($m04)
    {
        $this->m04 = $m04;

        return $this;
    }

    /**
     * Get m04
     *
     * @return integer
     */
    public function getM04()
    {
        return $this->m04;
    }

    /**
     * Set m05
     *
     * @param integer $m05
     *
     * @return Consommation
     */
    public function setM05($m05)
    {
        $this->m05 = $m05;

        return $this;
    }

    /**
     * Get m05
     *
     * @return integer
     */
    public function getM05()
    {
        return $this->m05;
    }

    /**
     * Set m06
     *
     * @param integer $m06
     *
     * @return Consommation
     */
    public function setM06($m06)
    {
        $this->m06 = $m06;

        return $this;
    }

    /**
     * Get m06
     *
     * @return integer
     */
    public function getM06()
    {
        return $this->m06;
    }

    /**
     * Set m07
     *
     * @param integer $m07
     *
     * @return Consommation
     */
    public function setM07($m07)
    {
        $this->m07 = $m07;

        return $this;
    }

    /**
     * Get m07
     *
     * @return integer
     */
    public function getM07()
    {
        return $this->m07;
    }

    /**
     * Set m08
     *
     * @param integer $m08
     *
     * @return Consommation
     */
    public function setM08($m08)
    {
        $this->m08 = $m08;

        return $this;
    }

    /**
     * Get m08
     *
     * @return integer
     */
    public function getM08()
    {
        return $this->m08;
    }

    /**
     * Set m09
     *
     * @param integer $m09
     *
     * @return Consommation
     */
    public function setM09($m09)
    {
        $this->m09 = $m09;

        return $this;
    }

    /**
     * Get m09
     *
     * @return integer
     */
    public function getM09()
    {
        return $this->m09;
    }

    /**
     * Set m10
     *
     * @param integer $m10
     *
     * @return Consommation
     */
    public function setM10($m10)
    {
        $this->m10 = $m10;

        return $this;
    }

    /**
     * Get m10
     *
     * @return integer
     */
    public function getM10()
    {
        return $this->m10;
    }

    /**
     * Set m11
     *
     * @param integer $m11
     *
     * @return Consommation
     */
    public function setM11($m11)
    {
        $this->m11 = $m11;

        return $this;
    }

    /**
     * Get m11
     *
     * @return integer
     */
    public function getM11()
    {
        return $this->m11;
    }

    /**
     * Set m12
     *
     * @param integer $m12
     *
     * @return Consommation
     */
    public function setM12($m12)
    {
        $this->m12 = $m12;

        return $this;
    }

    /**
     * Get m12
     *
     * @return integer
     */
    public function getM12()
    {
        return $this->m12;
    }

    /**
     * conso = retourne le premier champ non vide en partant de M12
     ****************************************/
    public function conso()
    {
        if ($this->m12 > 0) return $this->m12;
        if ($this->m11 > 0) return $this->m11;
        if ($this->m10 > 0) return $this->m10;
        if ($this->m09 > 0) return $this->m09;
        if ($this->m08 > 0) return $this->m08;
        if ($this->m07 > 0) return $this->m07;
        if ($this->m06 > 0) return $this->m06;
        if ($this->m05 > 0) return $this->m05;
        if ($this->m04 > 0) return $this->m04;
        if ($this->m03 > 0) return $this->m03;
        if ($this->m02 > 0) return $this->m02;
        if ($this->m01 > 0) return $this->m01;
    }

    /**
     *
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
}
