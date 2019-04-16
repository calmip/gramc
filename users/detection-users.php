<?php

/**
 *
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
 * 
 */


$params = init();
$pdo    = connection($params['parameters']['database_host'],
                     $params['parameters']['database_name'],
                     $params['parameters']['database_user'],
                     $params['parameters']['database_password']);
                
$users = get_users($pdo);

// Renome le users.csv existant
rename('users.csv','users-old.csv');

// Crée le users.csv nouveau et une version sérialisée
write_users($users,'users.csv');
$s_users = serial($users);
//sort($s_users);


// Lit users-old.csv et une version sérialisée
$users_old   = get_users_old('users-old.csv');
$s_users_old = serial($users_old);
//sort($s_users_old);

/*
foreach ($s_users_old as $u) {
	print_r($u);
}
exit(0);
*/

// Recherche les users arrivés et les users partis
$s_arrives = array_diff($s_users,$s_users_old);
$s_partis  = array_diff($s_users_old,$s_users);

$msg = "";
$msg .= logins("LOGINS A OUVRIR\n===============\n", unserial($s_arrives));
$msg .= logins("LOGINS A FERMER\n===============\n", unserial($s_partis));

// print($msg);

if ($msg != "") {
    $dest = $params['parameters']['unixadmins'];
    $to   = join(',',$dest);
    $from = $params['parameters']['mailfrom'];
    $subj = "A FAIRE= Gestion des utilisateurs";
    mail_utf8($to,$from,$from,$subj,$msg);
}


/*
print "LOGINS A OUVRIR:\n";
print_r(unserial($s_arrives));

print "LOGINS A FERMER:\n";
print_r(unserial($s_partis));
*/

exit(0);

/*******************************************************************
 *  logins = Ecrit en clair une liste de logins
 * 
 * params = $msg Message d'en-tête
 *          $users Liste d'utilisateurs
 * 
 * return = Un message à écrire ou à envoyer par mail
 * 
 *******************************************************************/
 function logins($titre,$users) {
     if (count($users) == 0) {
         return '';
     }
     
     $msg = $titre . "\n";
     foreach ($users as $u) {
         $msg .= "PRENOM= " . $u[3] . "\n";
         $msg .= "NOM   = " . $u[4] . "\n";
         $msg .= "PROJET= " . $u[1] . "\n";
         $msg .= "MAIL  = " . $u[2] . "\n";
         $msg .= "\n";
     }         
     
     return $msg;
 }
     


/**************************************************************************
 * Lit parameters.yml
 *
 * Return = un tableau de tableaux contenant les paramètres
 **************************************************************************/ 
function init() {
    $f_params = '../app/config/parameters.yml';
    $params= yaml_parse_file($f_params);
    return $params;
 }

/***************************************************************************
 * Se connecte à la base de données
 * 
 * Params = Les paramètres de connection
 * Return = le $pdo de connection
 * 
 ***************************************************************************/
function connection($host,$bd,$user,$passwd) {
    $dsn = 'mysql:dbname='.$bd.';host='.$host.';charset=utf8';
    $pdo = new PDO($dsn, $user, $passwd, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"]);
    return $pdo;;
}


/**********************************************
 * Renvoie un tableau de tableaux contenant les individus:
 *     - qui collaborent à une version de projet dans une session active (état 5)
 *     - qui ont le champ login à True
 * 
 * param  = $pdo
 * return = Le tableau
 * 
 */
function get_users($pdo) {
    //$requete = "SELECT i.id_individu,i.mail,i.prenom,i.nom FROM `individu` AS `i`";
    $requete = "SELECT i.id_individu,i.mail,i.prenom,i.nom,v.id_projet 
                FROM `collaborateurVersion` as `cv`,`individu` AS `i`,`version`AS `v`
                WHERE i.id_individu=cv.id_collaborateur 
                AND   cv.id_version=v.id_version
                AND   v.etat_version=5
                AND   cv.login=1
                ORDER BY i.id_individu ASC";
    $result = [];
    foreach ($pdo->query($requete) as $row) {
	    $result[] = [$row['id_individu']==null?"":$row['id_individu'],
		    	$row['id_projet']==null?"":$row['id_projet'],
			$row['mail']==null?""     :$row['mail'],
			$row['prenom']==null?""   :$row['prenom'],
			$row['nom']==null?""      :$row['nom']
		];
    }
    return $result;
}

/******************************
 * Ecrit en csv le tableau passé en paramètres
 * 
 * params = $users Le tableau
 *          $fname Le nom du fichier
 * 
 *************************************************/
function write_users($users,$fname) {
    $fp = fopen($fname, 'w');
    foreach ($users as $fields) {
        fputcsv($fp,$fields);
    }
    fclose($fp);
}

/*****************************
 * Lit le csv passé en paramètres et renvoie un tableau de tableaux
 * 
 * params = Le nom de fichier
 * return = La tableau de tableaux
 * 
 *****************************************************************/
function get_users_old($fname) {
    $users = [];
    $fp = fopen($fname,'r');
    if ($fp != FALSE) {
        while (($data = fgetcsv($fp)) !== FALSE) {
            $users[]=$data;
        }
    }
    return $users;
}

/***********************************
 * Entrée = tableau de tableaux
 * Sortie = tableau de strings, chaque tableau interne étant sérialisé
 ************************************************************************/
function serial($a) {
    $s=[];
    foreach ($a as $tab) {
        $s[] = serialize($tab);
    }
    return $s;
}

/***********************************
 * Entrée = tableau de strings, chaque string est un tableau sérialisé
 * Sortie = tableau de tableaux
 ************************************************************************/
function unserial($a) {
    $u = [];
    foreach ($a as $str) {
        $u[] = unserialize($str);
    }
    return $u;
}

/**
 *
 * envoi d'un mail utf8, intégralement pompée sur http://php.net/manual/en/function.mail.php#108669
 *
 * return La valeur de la fonction mail
 *
 */
function mail_utf8($to, $from_user, $from_email,$subject = '(No subject)', $message = '') {
    $from_user = "=?UTF-8?B?".base64_encode($from_user)."?=";
    $subject = "=?UTF-8?B?".base64_encode($subject)."?=";
    
    $headers = "From: $from_user <$from_email>\r\n".
        "MIME-Version: 1.0" . "\r\n" .
        "Content-type: text/plain; charset=UTF-8" . "\r\n";
    
    // Commenter la ligne suivante pour desactiver l'envoi des mails
    return mail($to, $subject, $message, $headers);

    echo $headers;
    echo $subject;
    echo "\n";
    echo $message;
    echo "\n";
    return true;
}
