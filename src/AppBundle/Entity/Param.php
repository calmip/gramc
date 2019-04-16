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

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * Code
 *
 * @ORM\Table(name="param",uniqueConstraints={@ORM\UniqueConstraint(columns={"cle"})})
 * @ORM\Entity
 */
class Param
{
    /**
     * @var string
     *
     * @ORM\Column(name="cle", type="string", length=32, nullable=false)
     */
    private $cle;

    /**
     * @var string
     *
     * @ORM\Column(name="val", type="string", length=128)
     */
    private $val;

    /**
     * @var int
     *
     * @ORM\Column(name="id_param", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    public function __toString()
    {
        return $this->cle . '='. $this->val;
    }

    /**
     * Set cle
     *
     * @param string $cle
     * @return Param
     */
    public function setCle($cle)
    {
        $this->cle = $cle;

        return $this;
    }

    /**
     * Get cle
     *
     * @return string 
     */
    public function getCle()
    {
        return $this->cle;
    }
    /**
     * Set val
     *
     * @param string $val
     * @return Param
     */
    public function setVal($val)
    {
        $this->val = $val;

        return $this;
    }
    /**
     * Get val
     *
     * @return string 
     */
    public function getVal()
    {
        return $this->val;
    }
    public function getId()
    {
        return $this->id;
    }
}
