$(document).ready(function() {
	$("#ajout_collab").click(ajout_collab);
	desactive_tout();
	$(".mail").blur(remplit_reactive_ligne);
	mail_autocomplete();
	$(".alerte").click(alerte_suppression);

	// appelée quand on clique sur le bouton Ajouter une ligne
	function ajout_collab(event) {
        event.preventDefault();

		var collab_wrapper = $("#profil_horiz");           // La table des collaborateurs
		var rowzero        = $("#profil_horiz tr:odd");    // La ligne "zero" réservée au code js

		// On copie le template (rowzero), on remplace %N% par le compteur, et on incrémente le compteur
		nouv_ligne = '<tr>'+rowzero.html()+'</tr>';
		nb_row= parseInt(rowzero.attr('nombre'),10);
		nb_row_str = nb_row.toString();
		nouv_ligne = nouv_ligne.replace(/%N%/g,nb_row.toString());
		collab_wrapper.last().append(nouv_ligne);
		nb_row += 1;
		rowzero.attr('nombre',nb_row);
		desactive_tout();
		$(".mail").change(remplit_reactive_ligne);
		mail_autocomplete();
	};

	// appelée au chargement de la page, ou quand on clique sur le bouton Ajouter une ligne
	// autocomplete sur les champs de class mail
	function mail_autocomplete() {
		$('.mail').autocomplete({
			delay: 500,
			minLength : 4,
			source : function(requete, reponse) {
				h = document.URL;
				h = h.replace(/#.*$/,'');
				h += '&ajax=1';
				h += '&term='+requete.term;
				//alert("coucou " + h);
				$.ajax({url: h,
						type: "GET",
						dataType: "json",
						context: $(this)
					   })
					.done(function(data){
						reponse(data);
					})
					.fail(function(xhr, status, errorThrown) { alert (errorThrown); });
			}
		});
	};

	// appelée quand on supprime un collaborateur !
	function alerte_suppression() {
		if ($(this).is(':checked')) {
			prenom = $(this).parents('tr').find("input[name*='[prenom]']").val();
			nom    = $(this).parents('tr').find("input[name*='[nom]']").val();
			alert('ATTENTION ! Voulez-vous vraiment supprimer '+prenom+' '+nom+' de la liste des collaborateurs ?');
		}
		
	};

	// appelée au chargement de la page, ou quand on clique sur le bouton Ajouter une ligne
	// Désactive tous les éléments de la classe .inactif et qui sont sur la même ligne qu'un champ de classe .mail laissé vide !
	// Cela force l'utilisateur à renseigner le mail en premier
	function desactive_tout() {
		$("#profil_horiz").find("tr").each(function(){
			tr=$(this);
			tr.find(".mail").each(function(){
				// si pas de mail spécifié, désactiver tous les champs de la ligne et de la classe inactif !
				if ($(this).val()=="") {
					$(this).parents("tr").find(".inactif").prop("disabled",true).attr("title","Commencez par l'adresse de courriel !");
				}

			});
		});
	};

	// appelée quand on quitte le champ mail après avoir changé qq chose dedans
	// remplit les champs de la ligne et les réactive
	function remplit_reactive_ligne(event) {
		if ($(this).val()!="") {
			h  = document.URL;
			h += '&ajax=1';
			h += '&mail='+$(this).val();
			//alert("coucou " + h);
			$.ajax({url: h,
					type: "GET",
					dataType: "json",
					context: $(this)})
				.done(function(data){

					// Si on a trouvé un individu avec la bonne adresse mail, on complète les autres champs !
					if (data.toString() != "") {
						$(this).parents('tr').find("input[name*='[nom]']").val(data['nom']);
						$(this).parents('tr').find("input[name*='[prenom]']").val(data['prenom']);
						$(this).parents('tr').find("select[name*='[statut]']").val(data['id_statut']);
						$(this).parents('tr').find("select[name*='[labo]']").val(data['id_labo']);
						$(this).parents('tr').find("select[name*='[etab]']").val(data['id_etab']);
					};
				})
				.fail(function(xhr, status, errorThrown) { alert (errorThrown); });
			$(this).parents("tr").find(".inactif").prop("disabled",false);
		};
	}
});

// appelée au retour d'ajax/enregistrer = Supprime la ligne correspondant aux collaborateurs effectivement supprimés
function supprime_aff_collabs() {
	$('#profil_horiz input.inactif').each(function () {
		if ($(this).is(':checked'))
		{
			//alert('dégage');
			$(this).parents('tr').remove();
		}
	});
}	




/*

	var max_fields      = 10; //maximum input rows allowed
    var wrapper         = $("#table_form_col"); //Fields wrapper
    var add_button      = $(".add_collabo_button"); //Add button ID
    var statuts		= $(".statorig option");
    var labos		= $(".laborig option");
    var etabs		= $(".etaborig option");
    var x 		= $("#nb_collabo").text();//initial row count
	
    $(add_button).click(function(event){ //on add input button click
        event.preventDefault();
		return;
	 if(x < max_fields){ //max row allowed
            x++; //row increment
            $(wrapper).last().append('<tr><td><input type="checkbox" id="collabo['+x+'][login]" name="collabo['+x+'][login]" value=1></td>                                      <td><input type="text" class="petite_col" id="collabo['+x+'][nom]" name="collabo['+x+'][nom]" ></td>                                      <td><input type="text" class="petite_col" id="collabo['+x+'][prenom]" name="collabo['+x+'][prenom]" ></td>                                <td><input type="text" class="petite_col autocomp" id="collabo['+x+'][mail]" name="collabo['+x+'][mail]"></td>						              <td><select id="statselec'+x+'" name="collabo['+x+'][statut]"></select></td>			   				 <td><select id="labselec'+x+'" name="collabo['+x+'][labo]"></select></td><td><select id="etabselec'+x+'" name="collabo['+x+'][etab]"></select></td></tr>'); //add input row
	
		$(statuts).each(function(){
			var v = $(this).val();
			var t = $(this).text();
			$('#statselec'+x).append('<option value="'+v+'">'+t+'</option>');
		});
		
		 $(labos).each(function(){
                        var v = $(this).val();
                        var t = $(this).text();
                        $('#labselec'+x).append('<option value="'+v+'">'+t+'</option>');
   		});

		$(etabs).each(function(){
                        var v = $(this).val();
                        var t = $(this).text();
                        $('#etabselec'+x).append('<option value="'+v+'">'+t+'</option>');
                });
		
		$('.autocomp').autocomplete({
                	source : 'liste_mail.php',
                	minLength : 3
        	});	
		
		$('input').keypress(function(event) { return event.keyCode != 13; });	
	}});

	$('.autocomp').autocomplete({
		source : 'liste_mail.php',
		minLength : 3
	});
	
	$('input').keypress(function(event) { return event.keyCode != 13; });
   
});

*/
	
