<?php

namespace AppBundle\GramcGraf;


abstract class GramcGraf
{
    /*
     * Le code utilisateur:
     *   - Définit les timestamps de début et de fin
     *   - Fait la requête dans la base de données, le résultat est dans $db_data
     *   - Appelle createStructuredData
     *   - Peut éventuellement travailler sur les structured_data (RAZ en début d'année pour gramc par exemple)
     *   - Appelle createImage
     *
     */
    abstract public function createStructuredData(\DateTime $debut,\Datetime $fin,$db_data);
    abstract public function createImage($structured_data);

}
