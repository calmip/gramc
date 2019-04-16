<?php

namespace AppBundle\Politique;

use AppBundle\Entity\Version;


class CpuData  extends Data
{

    public function getName()   {  return "cpu"; }

    public function getAttrHeures() { return $this->getVersion()->getAttrHeures(); }

    public function getAttrHeuresRallonge() { return $this->getVersion()->getAttrHeuresRallonge(); }

    public function getDemHeures() { return $this->getVersion()->getDemHeures(); }

    public function getDemHeuresRallonge() { return $this->getVersion()->getDemHeuresRallonge(); }

    public function getPenalHeures(){ return $this->getVersion()->getPenalHeures(); }

    public function getAttrHeuresEte() { return $this->getVersion()->getAttrHeuresEte(); }

    public function getConso() { return $this->getVersion()->getConso(); }

    public function getQuota() { return $this->getVersion()->getQuota(); }
}
