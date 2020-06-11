$(document).ready(function() {

    /* Pénalités */
    // Sera connecté au click des liens de pénalités
    function click_penalite (event ) {
        event.preventDefault();
        h = $(this).attr("href");
        $.ajax({url: h,
                type: "GET",
		dataType: "json",
                context: $(this)})
         .done(function(data){
	    ligne = $(this).parent().parent();
	    ligne.children('td.penalite').html(data['penalite']);
	    ligne.children('td.recuperable').html(data['recuperable']);
	    ligne.find('.bouton_penalite').toggleClass('invisible');
	    stats_penal = parseInt($('#stats_penal').html().replace(/ /g,''));
	    stats_recuperables = parseInt($('#stats_recuperables').html().replace(/ /g,''));
	    stats_attribuees   = parseInt($('#stats_attribuees').html().replace(/ /g,''));
	    stats_attribuables = parseInt($('#stats_attribuables').html().replace(/ /g,''));
	    attr               = parseInt(ligne.children('td.attr').html().replace(/ /g,''));

		// Mise à jour des stats pénalités, attribution, et de la colonne attribution
		if (data['penalite']==0) {
			stats_penal        -= parseInt(data['recuperable']);
			stats_recuperables += parseInt(data['recuperable']);
			attr               += parseInt(data['recuperable']);
			stats_attribuees   += parseInt(data['recuperable']);
			stats_attribuables  -= parseInt(data['recuperable']);
		} else {
			stats_penal        += parseInt(data['penalite']);
			stats_recuperables -= parseInt(data['penalite']);
			attr               -= parseInt(data['penalite']);
			stats_attribuees   -= parseInt(data['penalite']);
			stats_attribuables  += parseInt(data['penalite']);
		}
		$('#stats_penal').html(stats_penal);
		$('#stats_recuperables').html(stats_recuperables);
		$('#stats_attribuees').html(stats_attribuees);
		$('#stats_attribuables').html(stats_attribuables);
		ligne.children('td.attr').html(attr);

		//alert('recuperable=' + data['recuperable'] + ' ** ' + 'penalite=' + data['penalite']);
	 })
         .fail(function(xhr, status, errorThrown) { alert (errorThrown); });
    };


    // Connecter aux fonctions click lors de l'initialisation
    $( "a.bouton_penalite" ).click(click_penalite);
});
