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
 * @ORM\Table(name="sso", indexes={@ORM\Index(name="id_individu", columns={"id_individu"})})
 * @ORM\Entity
 */
class Sso
{
    /**
     * @var string
     *
     * @ORM\Column(name="eppn", type="string", length=200)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $eppn;

    /**
     * @var \AppBundle\Entity\Individu
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Individu")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_individu", referencedColumnName="id_individu")
     * })
     */
    private $individu;

    /**
     * Get eppn
     *
     * @return string
     */
    public function getEppn()
    {
        return $this->eppn;
    }
    public function getId() { return $this->getEppn(); }

    /**
     * Set eppn
     *
     * @param string
     * @return Sso
     */
    public function setEppn($eppn)
    {
        $this->eppn = $eppn;
        return $this;
    }

    /**
     * Set individu
     *
     * @param \AppBundle\Entity\Individu $idIndividu
     *
     * @return Sso
     */
    public function setIndividu(\AppBundle\Entity\Individu $idIndividu = null)
    {
        $this->individu = $idIndividu;

        return $this;
    }

    /**
     * Get individu
     *
     * @return \AppBundle\Entity\Individu
     */
    public function getIndividu()
    {
        return $this->individu;
    }

    public function __toString()
    {
        return $this->getEppn();
    }
}
