<?php

namespace AppBundle\Politique;

use AppBundle\Entity\Version;
use AppBundle\AppBundle;


class Data
{

    private $version;
    
    public function __construct( Version $version )
        {
        $this->version  =   $version;
        }

    public function getVersion(){ return $this->version; }
    
    public function setVersion(Version $version){ $this->version = $version; return $this;}

    public function getName()   {  return "data"; }
    
    
    
}
