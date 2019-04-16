$(document).ready(function() {
	// autocomplete sur les champs de class refbib
	refbib_autocomplete();

	function refbib_autocomplete() {
		$('.refbib').unbind('autocomplete').autocomplete({
			delay: 500,
			minLength : 4,
			source : function(requete, reponse)
            {
				$.ajax({url: $("input[id$='_publication_refbib'][type='text']").data("autocomplete"),
						type: "POST",
						dataType: "json",
                        data: { 'autocomplete_form' : { 'refbib' :  requete.term } }, // structure compatible symfony
						context: $(this),
					   })
					.done(function(data){
						reponse(data); //alert( 'output = ' + data );
					})
					.fail(function(xhr, status, errorThrown) { alert (errorThrown); });
			},
            select :  function(event, ui ) { complete_publication( ui.item.value, $(this) );}
		});
	};
});

function complete_publication( valeur, context )
{
    $.ajax({
            url: $("input[id$='_publication_refbib'][type='text']").data("autocomplete"),
                   type: "POST",
                   dataType: "json",
                   data: { 'appbundle_publication' : { 'refbib' :  valeur } }, // structure compatible symfony
                   context: context,
                   converters: { 'text json': true},
                   })
            .done(function(data)
                {
                if( data != "\nnopubli" )
                    {
                    var input = '<div>' + data + '</div>';
                    $("select[id$='_publication_annee']", context.parent().parent().parent() ).val($("select[id$='_publication_annee']", input).val()  );
                    $("input[id$='_publication_openUrl']", context.parent().parent().parent() ).val($("input[id$='_publication_openUrl']", input).val()  );
                    $("input[id$='_publication_doi']", context.parent().parent().parent() ).val($("input[id$='_publication_doi']", input).val()  );
                    //$("input[id$='_publication_idPubli" , context.parent().parent().parent().parent() ).val($("input[id$='_publication_idPubli']", input).val()  );
                    $("input[id$='_publication_idPubli"  ).val($("input[id$='_publication_idPubli']", input).val()  );
                    }
                })
            .fail(function(xhr, status, errorThrown) { /* alert ('Erreur complete_publication ' + status + xhr); */ });    
}


