<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * Compta
 *
 * @ORM\Table(name="compta", uniqueConstraints={@ORM\UniqueConstraint(name="item", columns={"date", "loginname", "ressource", "type" })})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ComptaRepository")
 */
class Compta
{
    const USER = 1;
    const GROUP= 2;
    
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="date")
     */
    private $date;

    /**
     * @var string
     *
     * @ORM\Column(name="loginname", type="string", length=40)
     */
    private $loginname;

    /**
     * @var string
     *
     * @ORM\Column(name="ressource", type="string", length=40)
     */
    private $ressource;

    /**
     * @var int
     *
     * @ORM\Column(name="type", type="smallint")
     */
    private $type;

    /**
     * @var int
     *
     * @ORM\Column(name="conso", type="bigint")
     */
    private $conso;

    /**
     * @var int
     *
     * @ORM\Column(name="quota", type="bigint")
     */
    private $quota;


    

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
     * Set date
     *
     * @param \DateTime $date
     *
     * @return Compta
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set loginname
     *
     * @param string $loginname
     *
     * @return Compta
     */
    public function setLoginname($loginname)
    {
        $this->loginname = $loginname;

        return $this;
    }

    /**
     * Get loginname
     *
     * @return string
     */
    public function getLoginname()
    {
        return $this->loginname;
    }

    /**
     * Set ressource
     *
     * @param string $ressource
     *
     * @return Compta
     */
    public function setRessource($ressource)
    {
        $this->ressource = $ressource;

        return $this;
    }

    /**
     * Get ressource
     *
     * @return string
     */
    public function getRessource()
    {
        return $this->ressource;
    }

    /**
     * Set type
     *
     * @param integer $type
     *
     * @return Compta
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set conso
     *
     * @param integer $conso
     *
     * @return Compta
     */
    public function setConso($conso)
    {
        $this->conso = $conso;

        return $this;
    }

    /**
     * Get conso
     *
     * @return integer
     */
    public function getConso()
    {
        return $this->conso;
    }

    /**
     * Set quota
     *
     * @param integer $quota
     *
     * @return Compta
     */
    public function setQuota($quota)
    {
        $this->quota = $quota;

        return $this;
    }

    /**
     * Get quota
     *
     * @return integer
     */
    public function getQuota()
    {
        return $this->quota;
    }
}
