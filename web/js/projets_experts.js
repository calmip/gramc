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
 *
 * @endcond
 */

$( document ).ready(function() {

    // Sera connecté au click des liens de fermeture de projet
    // Déclenche une requête ajax, ouvre le dialog lorsqu'elle est finie
    function click_fermeture(event) {
        event.preventDefault();
        h = $(this).attr("href");
        h += "&ajax=1";

        $.ajax({url: h,
                type: "GET",
                context: $(this)})
         .done(function(data){
             formulaire_confirmation.dialog("open");
             // garde en memoire la ligne qu'il faudra modifier par la suite
             // gramc_ligne = $(this).parent().parent();
             gramc_cell = $(this).parent();
             formulaire_confirmation.html(data);
             // met à jour l'action du formulaire
             $( "#formulaire_confirmation form" ).attr({action: h});

             // connecte la fonction aux boutons submit nouvellement créés
             $( "#confirm_oui" ).click(submit_ferme);
             $( "#confirm_non" ).click(ferme_dialog);

         })
         .fail(function(xhr, status, errorThrown) { alert (errorThrown); });
    };

    // il ferme juste la fenetre
    function ferme_dialog( event )
    {
        event.preventDefault();
        formulaire_confirmation.dialog("close");
    }

    // Sera connecté au click des submits des dialogues de confirmation
    // Contruit et envoie la requête POST, affiche à nouveau la ligne du projet
	// @todo On fait une requete de trop ici !
	// @todo Pas besoin de faire une requete serveur pour savoir si on confirme ou pas
    function submit_ferme( event )
    {
        event.preventDefault();
		form = $( "#formulaire_confirmation form" );
        h =  form.attr('action');
		h += "&ajax=1";
		$.ajax({url: h,
	    type: "POST",
	    context: gramc_cell,
	    data: this.name+"="+this.value})
	    .done(function(data){
	    formulaire_confirmation.dialog("close");
	    gramc_cell.html('&nbsp;');
	    gramc_cell.siblings().filter( ".en_standby" ).html('CLOSED');
	    $( "a.fermeture" ).click(click_fermeture);
	    })
	    .fail(function(xhr, status, errorThrown) { alert (errorThrown); });
	};

	// Connecté aux evenements change des checkboxes
	function change_cb() {
		$( "#projets tr" ).show();
		 $("input.cb").each(function(){
			 cl = '.' + $(this).attr("id");
			 if (!$(this).is(":checked")) {
				 $(cl).parent().hide();
			 };
		 });
//		event.preventDefault();
	};

	// Connecté au checkbox inverser_selection
	// Inverse tous les checkboxes non cachés de la classe expsel
	// Puis appelle change_couleur dessus
	
	function invsel_all() {
		$("input.expsel").each(function(){
			if ($(this).is(':visible'))
			{
				$(this).prop('checked', !$(this).is(':checked'));
				change_couleur($(this));
			}
		});
	}

	// Connecté aux checkboxes de sélection
	// Appelle change_couleur
	function invsel_one() {
		change_couleur($(this));
	}

	// Calcul des statistiques sur les experts
	function calcul_stats() {
		//alert("HOHOHOHO");
		// Remettre à 0 l'attribut nbprj sur les cb de #experts
		//$( "#experts tr td .cb").attr('nbprj',0);

		// Recalculer la valeur de cet attribut
		$( "#experts tr td .cb").each(function(){
			cl = '.' + $(this).attr("id");
			v  = $(cl).length;
			$(this).parent().parent().children('td:first').html(v);
			//alert("coucou "+cl+' '+v)
		});
	}

	// Appelé lorsque le select change de valeur
	// TODO - Je ne crois pas que ça fonctionne !
	function change_select(event) {
		// changer la classe du parent du select
		$(this).parent().removeClass();
		cl = 'e' + $(this).val();
		$(this).parent().addClass(cl);

		// changer l'url de sudo
		a   = $(this).parent().children("a");
		url = a.attr("href");
		//alert (url.match(/=\d+$/,url));
		url=url.replace(/=\d+$/,"="+$(this).val());
		//url=url.replace(/=\d+$/,"="+"***");
		//alert(url);
		$(this).parent().children("a").attr("href",url);
		calcul_stats();
	}

	function change_cb_stat() {
		$( "section div" ).show();
		 $("input.cb_stat").each(function(){
			 cl = '.' + $(this).attr("id");
			 if (!$(this).is(":checked")) {
				 $(cl).parent().hide();
			 };
		 });
	};

	// Connecté aux cb de sélection
	// Change la couleur de la cellule du tableau
	// Incrémente ou décrémente le compteur cpt_sel
	// Affiche ou cache le div bouton_affecter général
	// Affiche ou cache le class bouton_affecter de la ligne

	cpt_sel = 0;
	function change_couleur(cb) {
		cell = cb.parent();
		line = cell.parent();
		// Si la cb est cochée la cellule se teinte en bleu
		if ( cb.is(":checked")) {
			cell.css("background-color","blue");
			line.find(".bouton_affecter").show();
			cpt_sel++;

		// Sinon elle prend la couleur de la cellule d'à côté
		} else {
			cell.css("background-color",cell.next().css("background-color"));
			line.find(".bouton_affecter").hide();
			cpt_sel--;
		}
		
		// Suivant la valeur du compteur, cache ou affiche le cadre des boutons
		if (cpt_sel==0)
		{
			$("#bouton_affecter").hide();
		}
		else
		{
			$("#bouton_affecter").show();
		}
	}

/* CE CODE EST EXECUTE AU CHARGEMENT DE LA PAGE */

    /* Fermer */
    // Le dialogue utilisé pour fermer un projet
    formulaire_confirmation = $( "#formulaire_confirmation" ).dialog({autoOpen: false,
            height: 500,
            width: 400,
            modal: true
	});

	// Tout cocher ou décocher: thematiques
	$( "#tX" ).click(function(event) {
		//alert("HOHO " + $(this).is(':checked') );
		if ( $(this).is(':checked') ) {
			$("#themas tr td input.cb").prop('checked','checked');
			$(this).parent().parent().children('th').html('Tout décocher');
		} else {
			$("#themas tr td .cb").attr('checked',false);
			$(this).parent().parent().children('th').html('Tout cocher');
		};
		change_cb();
	});

	// Tout cocher ou décocher: rattachements
	$( "#rX" ).click(function(event) {
		//alert("HOHO " + $(this).is(':checked') );
		if ( $(this).is(':checked') ) {
			$("#themas tr td input.cb").prop('checked','checked');
			$(this).parent().parent().children('th').html('Tout décocher');
		} else {
			$("#themas tr td .cb").attr('checked',false);
			$(this).parent().parent().children('th').html('Tout cocher');
		};
		change_cb();
	});

	// Tout cocher ou décocher: experts
	$( "#eX" ).click(function(event) {
		//alert("HOHO " + $(this).is(':checked') );
		if ( $(this).is(':checked') ) {
			$("#experts tr td input.cb").prop('checked','checked');
			$(this).parent().parent().children('th').html('Tout décocher');
		} else {
			$("#experts tr td .cb").attr('checked',false);
			$(this).parent().parent().children('th').html('Tout cocher');
		};
		change_cb();
	});

	// Connecter aux fonctions click lors de l'initialisation
	$( "a.fermeture" ).click(click_fermeture);

	// Connecter aux fonctions change des checkboxes
	$( "input.cb" ).change(change_cb);

	// Connecter aux fonctions change des select
	$( "#projets select").change(change_select);

	// Commencer par calculer les stats
	calcul_stats();

	// C'est quoi ça ?
	$( "input.cb_stat" ).change(change_cb_stat);

	// Connecter au bouton inverser la sélection
	$( "img#invsel").click(invsel_all);
	
	// Checkboxes de sélection 
	$( "input.expsel").change(invsel_one);
	
	// lors du chargement de la page, initialiser cpt_sel
	$("input.expsel").each(function() {
		cell = $(this).parent();
		line = cell.parent();
		if ( $(this).is(":checked") )
		{
			cell.css("background-color","blue");
			line.find(".bouton_affecter").show();
			cpt_sel++;
		}
		else
		{
			line.find(".bouton_affecter").hide();
		}
	});
	if (cpt_sel==0)
	{
		$("#bouton_affecter").hide();
	}
});
