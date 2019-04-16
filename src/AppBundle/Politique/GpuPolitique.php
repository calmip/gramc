<?php

namespace AppBundle\Politique;

use AppBundle\Entity\Version;


class GpuPolitique  extends Politique
{

    public function getName()   {  return "gpu"; }
    
    public function getData(Version $version)   {  return new GpuData($version);  }
    
}
