<?php

namespace AppBundle\Politique;

use AppBundle\Entity\Version;

class Politique
{
    const POLITIQUE     =           0;  // politique nulle
    const CPU_POLITIQUE  =          1;  // politique par défaut
    const GPU_POLITIQUE  =          2;

    const DEFAULT_POLITIQUE =  self::CPU_POLITIQUE;  // la politique par défaut


    const   LIBELLE_POLITIQUE =
        [
            self::POLITIQUE                 =>  'POLITIQUE',            
            self::CPU_POLITIQUE             =>  'CPU_POLITIQUE',
            self::GPU_POLITIQUE             =>  'GPU_POLITIQUE',
        ];

    public static function getLibelle($politique)
    {
        if( $politique != null && array_key_exists( $politique , self::LIBELLE_POLITIQUE) )
            return self::LIBELLE_POLITIQUE[$politique];
        else
            return 'UNKNOWN';
    }
    
    public function getName()   {  return "politique"; }
    
    public function getData(Version $version)   {  return null;  }
    
}
