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


    // Affichage de la consommation
    // Transformation des div en dialog
    $( "div.graphique" ).dialog({
      autoOpen: false,
      minHeight: 450,
      minWidth: 850
    });

    // Click sur le bouton conso -> Appel ajax et affichage du résultat dans le dialog
    $(".bconso").click(function(e) {
	var href = $(this).data('href');
	var img_alt = $(this).attr('alt');
	var width   = $(this).data("width");        // pas utilisé
	var height  = $(this).data("height");       // pas utilisé
	$.ajax(
	{
	    url: href,
	    type: "GET",
	    context: $(this)
	})
	.done(function(data)
	{
	    //alert(data);
	    var id=e.target.id;
	    id = '#' + id.replace("bconso_", "graph_");
	    $(id).dialog('open');
	    $(id).html(data);
	    $(id).dialog( "option", "title", img_alt );
	})
    });

});
