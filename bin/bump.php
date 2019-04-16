<?php
/**
 * @cond
 * --GPLBEGIN LICENSE 
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
 *  --GPLEND LICENSE
 *
 *  --AUTHORBEGIN
 *  author : Erwan Mouillard (stage L3 2012, IUT - Université Paul Sabatier - University of Toulouse)
 *           Enzo Alunni (stage L3 2015, Université Paul Sabatier - University of Toulouse)
 *  supervision: Nicolas Renon - Université Paul Sabatier - University of Toulouse)
 *               Emmanuel Courcelle - C.N.R.S. - UMS 3667 - CALMIP
 *  --AUTHOREND
 * @endcond
 */

if ( $argc != 3) usage();
$config        = $argv[1];
$gramc_bump    = $argv[2];   

$conf          = lit_config($config);
$gramc_version = lit_version($conf);

if ($gramc_version >= $gramc_bump) {
    echo "ATTENTION ! - La nouvelle version doit être > $gramc_version\n";
//    exit(1);
}

$conf = remplace_version($conf,$gramc_bump);
ecrit_config($config,$conf);

echo "C'est fait !\n";
exit(0);

function remplace_version($conf,$gramc_bump) {
    return preg_replace('/define\(\'GRAMC_VERSION\',\'(.+)\'\)/',"define('GRAMC_VERSION','$gramc_bump')",$conf);
}

function ecrit_config($config,$conf) {
    $fh = fopen($config,'w');
    if ( $fh === False ) {
        echo "Fichier $config pas trouvé ou protégé\n";
        usage();
    }
    $conf = fwrite($fh,$conf);
    fclose ($fh);
}

function lit_config($config) {
    $fh = fopen($config,'r');
    if ( $fh === False ) {
        echo "Fichier $config pas trouvé\n";
        usage();
    }
    $conf = fread($fh,filesize($config));
    fclose ($fh);
    return $conf;
}

function lit_version($conf) {
    $matches = array();
    $trouve = preg_match('/define\(\'GRAMC_VERSION\',\'(.+)\'\)/',$conf,$matches);
    return $matches[1];
}

function usage() {
    global $argv;
    echo "Usage: $argv[0] config.php version\n";
    exit(1);
}

?>
