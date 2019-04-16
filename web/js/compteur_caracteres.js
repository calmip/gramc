/*********
 * Cette fonction affiche le nombre de caracteres entres par l'utilisateur dans un textarea
 * Code html: 
 *     - Le textarea doit appartenir à la classe "compteur"
 *     - Le textarea doit avoir un id (ici "un_id") 
 *     - L'élément mis à jour à chaque touche doit avoir l'id "un_id_cpt"
 * Et c'est tout 
 *************************/

/*** A VERIFIER JE NE SAIS PAS OU ON EN EST APRES LE HOTFIX D'AUTOMNE 2016 !!! ***/

$(document).ready(function(e)
{
    
    $('.compteur').each( function() { calcul_caracteres( $(this) ); } );
    $('.compteur').keyup(function() { calcul_caracteres( $(this) ); } );
    
});

function calcul_caracteres( context )
{
    var cpt_id = '#' + context.attr('id')+'_cpt';
	var nombreCaractere = context.val().length;
	//console.log(nombreCaractere);
	var msg = '(actuellement  ' + nombreCaractere + ' caractères)';
	$(cpt_id).text(msg);
}

