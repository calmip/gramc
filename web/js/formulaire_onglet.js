


$(document).ready(function() {

    $('.invisible_if_no_js').show();

    $('#onglets').tabs( {
	classes: {
	    "ui-tabs": "highlight"
	}
    });

    // Lorsque je clique sur un lien #machin, activer l'onglet correspondant
    // Prérequis = Les div constituant les onglets ont comme id: #tab1, #tab2 etc.
    $('.gerer_onglets').click( function() {
	idcible = $(this).attr("href");
	tab = $(idcible).parents("div.onglet").attr("id");

	// tab3 ==> index 2 (zero-based !)
	tab_index = tab.slice(tab.length-1) - 1;
	$( '#onglets' ).tabs( "option", "active" , tab_index );
    });

    // Le dialog js utilisé suite à un appel ajax
    enregistrer_message = $( "#enregistrer_message" ).dialog({autoOpen: false,
            height: 300,
            width: 600,
            modal: true,
	    buttons: {
		       Ok: function() {
		         $( this ).dialog( "close" );
		       }
	              }
    });

    // Lorsqu'on clique sur le bouton Enregistrer, on déclenche une requête ajax
    $('#Enregistrer').on('click', function(e) {
		e.preventDefault();
		e.stopPropagation();

		// Récupère les données du formulaire, ajouter ENREGISTRER car ça ne se fait pas tout seul
		// (à cause de stopPropagation ou de preventDefault)
		form = $('#form_projet');
		form_data = form.serializeArray();
		//form_data.push({name:'ENREGISTRER',value:1});
		h = document.URL;
		//h += '&ajax=1';
		//console.log(h);
		//console.log(form.serialize());

		$.ajax(
        {
		    type: 'POST',
		    url: h,
		    data: form_data,
		    processData: true,
		    success: function( data )
            {
			    //console.log(data);
			    msg = $.parseJSON(data);
			    if ( msg.match(/ERREUR/) != null) {
			        msg = '<div class="message erreur"><h2>ATTENTION !</h2>'+msg+'</div>';
			    } else {
			        msg='<div class="message info"><h2>Projet enregistré</h2>'+msg+'</div>';
			    }
			    enregistrer_message.html(msg);
			    enregistrer_message.dialog("open");

			    // Supprime les lignes des collaborateurs supprimés
			    supprime_aff_collabs();
			    $('#liste_des_collaborateurs').find("input[id$='_mail'][type='text']" ).each(function() {
				    if ($(this).val() != "" ) {
						//alert($(this).val());
						$(this).prop("disabled",true).attr("title","Vous ne pouvez plus changer l'adresse de courriel !");
				    }
				});
	        },
		    error: function(response)
            {
				alert("ERREUR ! Pas possible d'enregistrer !");
		    }
	    });
    });

    // Lorsqu'on clique sur le bouton nogenci, on remplit quelques champs
    $('#nogenci').on('click', function(e) {
		e.preventDefault();
		e.stopPropagation();
		$('#form_prjGenciCentre').val('aucun');
		$('#form_prjGenciMachines').val('N/A');
		$('#form_prjGenciHeures').val('N/A');
		$('#form_prjGenciDari').val('N/A');
	});
} );


/*************

$(document_AJETER).ready(function() { // enregistrer parties des formulaires

    $('#Enregistrer').on('click', function(e) {
        e.preventDefault();
	e.stopPropagation();


 //       var renouv =  document.getElementById("prj_justif_renouv").innerHTML;
  //      var renouvellement_final = toMarkdown(renouv);

//		var data = $('#form_projet').serialize();// récupère données du formulaire
//		var titre = $('#titre').val();
//		var renouvellement = $("#prj_justif_renouv").text();
//		var id_version1= $('#id_version1').val();
		var fd = new FormData();

	//fd.append('Enregistrer',1);
	h = document.URL;
	h += '&ajax=1';
        $.ajax({
	    url: h,
            type: 'POST',
            data:
	    processData: false,
	    contentType: false,
	    success: function() {
		alert('Votre document est enregistré ');
	    },
            error: function() {
		alert('ERREUR ! Pas possible d\'enregistrer le document');
	    }
	})
    })
});


*/

/****
function commande(nom, argument) {
  if (typeof argument === 'undefined') {
    argument = '';
  }
  // Exécuter la commande
  if (nom == 'html') {
	   document.execCommand('formatBlock', false, argument);
  } else {
		document.execCommand(nom, false, argument);
	}
}

function resultat(statut){
	console.log(statut);
	if(statut == 'renouv') {
	var chaine =  document.getElementById("prj_justif_renouv").innerHTML;
	} else {
	var chaine =  document.getElementById("prj_expose").innerHTML;
	}
	var chaine_finale = resultat_markdown(chaine);
	return chaine_finale;
}

$(document).ready(function() { // enregistrer parties des formulaires

    $('#enregistrer1').on('click', function(e) {
        e.preventDefault();

        var renouv =  document.getElementById("prj_justif_renouv").innerHTML;
        var renouvellement_final = toMarkdown(renouv);

		var data = $('#form_projet').serialize();// récupère données du formulaire
		var titre = $('#titre').val();
		var renouvellement = $("#prj_justif_renouv").text();
		var id_version1= $('#id_version1').val();
		h = document.URL;
		h += '&ajax=1';
		if(titre == '') {
			alert('Veuillez renseigner le titre avant d\'enregistrer');
		} else {
        $.ajax({
			url: h,
            type: 'POST',
            data: data + '&renouvellement=' + renouvellement_final + '&id_version1=' + id_version1 ,
            success: function() {
				console.log('OK');
			},
            error: function() {
				alert('ERROR');
			}
        });
	}
    });

    $('#enregistrer2').on('click', function(e) {
	e.preventDefault();
	var expose = $('#prj_expose').innerHTML;
	var expose_final = toMarkdown(expose);
	var data = $('#form_projet').serialize();// récupère données du formulaire
	var id_version2= $('#id_version2').val();
	h = document.URL;
	h += '&ajax=1'; alert(h);

	$.ajax({
	    url: h,
	    type: 'POST',
	    data: data + '&expose=' + expose_final + '&id_version2=' + id_version2 ,
	    data: data,
	    success: function() {
		console.log('OK');
	    },
	    error: function() {
		alert('ERROR');
	    }
	});
    });

        $('#enregistrer3').on('click', function(e) {
        e.preventDefault();

		var data = $('#form_projet').serialize();// récupère données du formulaire
		var id_version3= $('#id_version3').val();
		h = document.URL;
		h += '&ajax=1';

        $.ajax({
			url: h,
            type: 'POST',
            data: data + '&id_version3=' + id_version3 ,
            success: function() {
				console.log('OK');
			},
            error: function() {
				alert('ERROR');
			}
        });
    });

        $('#enregistrer4').on('click', function(e) {
        e.preventDefault();

		var data = $('#form_projet').serialize();// récupère données du formulaire
		var id_version4= $('#id_version4').val();
		h = document.URL;
		h += '&ajax=1';

        $.ajax({
			url: h,
            type: 'POST',
            data: data + '&id_version4=' + id_version4 ,
            success: function() {
				console.log('OK');
			},
            error: function() {
				alert('ERROR');
			}
        });
    });
      $('#form_projet').on('submit', function() {
		var input_expose = document.createElement('input');
		var expose =  document.getElementById("prj_expose").innerHTML;
        var expose_final = toMarkdown(expose);
		input_expose.id    = 'expose';
		input_expose.name  = 'expose';
		input_expose.type  = 'hidden';
		input_expose.value = expose_final;
		document.getElementById('tab2').appendChild(input_expose);

		var input_renouv = document.createElement('input');
		var renouvellement =  document.getElementById("prj_justif_renouv").innerHTML;
        var renouvellement_final = toMarkdown(renouvellement);
		input_renouv.id    = 'justif_renouv';
		input_renouv.name  = 'justif_renouv';
		input_renouv.type  = 'hidden';
		input_renouv.value = renouvellement_final;
		document.getElementById('tab1').appendChild(input_renouv);
    });
});
*************/
