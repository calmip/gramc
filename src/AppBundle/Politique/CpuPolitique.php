<?php

namespace AppBundle\Politique;

use AppBundle\Entity\Version;


class CpuPolitique  extends Politique
{

    public function getName()   {  return "cpu"; }
    
    public function getData(Version $version)   {  return new CpuData($version);  }
    
}
