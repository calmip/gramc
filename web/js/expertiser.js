/*********
 * Remise à 0 et désactivation du champ #nb_heures_att
 * lorsque l'utilisateur presse le bouton-radio #refus
 *
 * Activation du champ #nb_heures_att lorsque l'utilisateur
 * presse le bouton-radio #valide
 *
 *************************/

$(document).ready(function(e) {
    $('#refus').click(function() {
		$('#nb_heures_att').prop('disabled',true);
		$('#nb_heures_att').val(0);
	});
    $('#valide').click(function() {
		$('#nb_heures_att').prop('disabled',false);
	});
});
