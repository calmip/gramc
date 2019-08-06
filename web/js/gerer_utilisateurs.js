$( document ).ready(function() {

	/* Expert */
	// Sera connecté au click des liens Expert et Activation
	function click_expactiv(event) {
		event.preventDefault();
        h = $(this).attr("href");
        h += "&ajax=1";
		
        $.ajax({url: h,
                type: "GET",
                context: $(this)})
			.done(
				function(data){
					tr = $(this).parent().parent();
					tr.html(data);

					// pb de perf si on clique plusieurs fois (pour supprimer 4 à 5 experts par exemple)
					// --> je mets une classe provisoire au tr (clicked) et je ne sélectionne que les enfants de cette classe
					// --> on ne repasse ainsi que sur les éléments qu'on vient de cliquer !
					// --> A la fin je vire la classe
					tr.addClass("clicked");
					$( "tr.clicked a.modification" ).click(click_modification);
					$( "tr.clicked a.suppression" ).click(click_suppression);
					$( "tr.clicked a.activation" ).click(click_expactiv);
					$( "tr.clicked a.expert" ).click(click_expactiv);
					$( "tr.clicked a.admin" ).click(click_expactiv);
					$( "tr.clicked a.obs" ).click(click_expactiv);
                    $( "tr.clicked a.sysadmin" ).click(click_expactiv);
					tr.removeClass("clicked");
				})
			.fail(function(xhr, status, errorThrown) { alert (errorThrown); });
	};
	
    /* Suppression */
    // Sera connecté au click des liens de suppression
    function click_suppression (event ) {
        event.preventDefault();
        h = $(this).attr("href");
        h += "&ajax=1";

        $.ajax({url: h,
                type: "GET",
                context: $(this)})
         .done(function(data){$(this).parent().parent().remove();})
         .fail(function(xhr, status, errorThrown) { alert (errorThrown); });
    };

    /* Modification */
    // Le dialog js utilisé pour modifier un individu
    formulaire_profil = $( "#formulaire_profil" ).dialog({autoOpen: false,
            height: 500,
            width: 800,
            modal: true});

    // garde en mémoire la ligne modifiée
    gramc_line="";

    // Sera connecté au click des liens de modification
    // Déclenche une requête ajax, ouvre le dialog lorsqu'elle est finie
    function click_modification(event) {
        event.preventDefault();
        h = $(this).attr("href");
        h += "&ajax=1";

        $.ajax({url: h,
                type: "GET",
                context: $(this)})
         .done(function(data){
             formulaire_profil.dialog("open");
             // garde en memoire la ligne qu'il faudra modifier par la suite
             gramc_ligne = $(this).parent().parent();
             formulaire_profil.html(data);
             // met à jour l'action du formulaire
             $( "#formulaire_profil form" ).attr({action: h});
             // connecte la fonction au bouton submit nouvellement créé
             $( "#submit_individu" ).click(submit_individu);
         })
         .fail(function(xhr, status, errorThrown) { alert (errorThrown); });
    };
         
    // Sera connecté au click du lien d'ajout
    // Déclenche une requête ajax, ouvre le dialog lorsqu'elle est finie
    function click_ajout(event) {
        event.preventDefault();
        h = $(this).attr("href");
        h += "&ajax=1";

        $.ajax({url: h,
                type: "GET",
                context: $(this)})
         .done(function(data){
             formulaire_profil.dialog("open");
             // garde en memoire la ligne qu'il faudra modifier par la suite
             gramc_ligne = $( "table" );
             formulaire_profil.html(data);
             // met à jour l'action du formulaire
             $( "#formulaire_profil form" ).attr({action: h});
             // connecte la fonction au bouton submit nouvellement créé
             $( "#submit_individu" ).click(submit_individu);
         })
         .fail(function(xhr, status, errorThrown) { alert (errorThrown); });
    };

    // Sera connecté au click du submit de l'élément form du formulaire
    // Envoie la requête POST, analyse les data au retour de la requête:
    //        - Soit Affiche à nouveau le fomrulaire
    //        - Soit ferme le formulaire et met à jour la ligne du tableau
    //        - Soit ferme le formulaire et ajoute une ligne au tableau
    function submit_individu( event )  {
        event.preventDefault();
        h =  $( "#formulaire_profil form" ).attr("action");
        $.ajax({url: h,
                type: "POST",
                context: gramc_ligne,
                data: $( "#formulaire_profil form" ).serialize()})
         .done(function(data){
             // Si data contient le tag <!--F--> c'est un formulaire
             form_detect_regex = /<!--F-->/;
             ajout_detect_regex = /<!--A-->/;
             modif_detect_regex = /<!--M-->/;
             if (form_detect_regex.test(data)) {
                 formulaire_profil.html(data);
                 $( "#formulaire_profil form" ).attr({action: h});
                 $( "#submit_individu" ).click(submit_individu);
             } else if (modif_detect_regex.test(data)) {
                 formulaire_profil.dialog("close");
                 gramc_ligne.html(data);

                 // connecter à nouveau, à cause de la nouvelle ligne !
                 $( "a.activation" ).click(click_expactiv);
                 $( "a.suppression" ).click(click_suppression);
                 $( "a.modification" ).click(click_modification);
				 $( "a.expert" ).click(click_expactiv);
             } else  if (ajout_detect_regex.test(data)) {
                 formulaire_profil.dialog("close");
                 ligne = $( "#utilisateurs" ).children().first();
                 ligne.before(data);
				 //alert(data);

                 // connecter à nouveau, à cause de la nouvelle ligne !
                 $( "a.activation" ).click(click_expactiv);
                 $( "a.suppression" ).click(click_suppression);
                 $( "a.modification" ).click(click_modification);
				 $( "a.expert" ).click(click_expactiv);
             } else {
                 alert ('comprends rien '+data);
             }
         })
         .fail(function(xhr, status, errorThrown) { alert (errorThrown); });
    };

    // Connecter aux fonctions click lors de l'initialisation
    $( "a.modification" ).click(click_modification);
	// Pas d'ajax pour la suppression, car en cas d'erreur (si la personne a des projets)
	// la redirection ne se fait pas
	// Il faudra arranger ça, mais pour l'instant on vire l'ajax
	//    $( "a.suppression" ).click(click_suppression);
    $( "a.activation" ).click(click_expactiv);
    $( "a.expert" ).click(click_expactiv);
	$( "a.admin").click(click_expactiv);
    $( "a.obs" ).click(click_expactiv);
	$( "a.sysadmin").click(click_expactiv);
    $( "#ajout" ) .click(click_ajout);
});
