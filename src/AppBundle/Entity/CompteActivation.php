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
 * CompteActivation
 *
 * @ORM\Table(name="compteActivation", uniqueConstraints={@ORM\UniqueConstraint(name="key", columns={"gramc_key"})}, indexes={@ORM\Index(name="id_individu", columns={"id_individu"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 */
class CompteActivation
{
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="stamp", type="datetime", nullable=true)
     */
    //private $stamp = 'CURRENT_TIMESTAMP';
    private $stamp;

    /**
     * @var string
     *
     * @ORM\Column(name="gramc_key", type="string", length=35)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $key;

    /**
     * @var \AppBundle\Entity\Individu
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Individu")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_individu", referencedColumnName="id_individu")
     * })
     */
    private $individu;

    ////////////////////////////////////

    /**
    * @ORM\PrePersist
    */
    public function setInitialMajStamp()
    {
    $this->stamp = new \DateTime();
    }

    /////////////////////////////////////

    /**
     * Set stamp
     *
     * @param \DateTime $stamp
     *
     * @return CompteActivation
     */
    public function setStamp($stamp)
    {
        $this->stamp = $stamp;

        return $this;
    }

    /**
     * Get stamp
     *
     * @return \DateTime
     */
    public function getStamp()
    {
        return $this->stamp;
    }

    /**
     * Set key
     *
     * @param string $key
     *
     * @return CompteActivation
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Get key
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set individu
     *
     * @param \AppBundle\Entity\Individu $individu
     *
     * @return CompteActivation
     */
    public function setIndividu(\AppBundle\Entity\Individu $individu = null)
    {
        $this->individu = $individu;

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
}
