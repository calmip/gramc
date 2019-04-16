$(document).ready(function() {
	$('.invisible_if_no_js').show();
	/*
	disOrNot(['plus de 1 To','espace']);
	disOrNot(['autre','duree']);
	changeHandler1();
	
	// Premier handler
	$('select.espace').change(['plus de 1 To','espace'],changeHandler);
	$('select.duree').change(['autre','duree'],changeHandler);

	// Second handler	
	$('select.espace').change('',changeHandler1);
	$('select.duree').change('',changeHandler1);
	
	// Met un champ de type select sur disable.... ou pas suivant la valeur du input associé
	// elt est un array: 0 => valeur, 1 => selecteur
	// Le selecteur permet de trouver le select et le input (mêmes classes)
	// Si input est non vide on fixe la valeur du select et on le met sur enabled
	// Si input est vide on le met sur disabled
	function disOrNot(elt) {
		inp = $('input.'+elt[1]);
		sel = $('select.'+elt[1]);
		if (inp.val() != '') {
			sel.val(elt[0]);
			inp.prop("disabled",false);
		} else {
			inp.prop("disabled",true);
		}
	};


	// Handler appelé lorsque la valeur d'un select change
	// Active ou pas le champ input associé
	function changeHandler(ev) {
		champ = $('input.'+ev.data[1]);
		if ( ev.data[0] == this.value ) {
			champ.prop("disabled",false);
		} else {
			champ.prop("disabled",true);
		};
		//alert('bonjour ' + ev.data[0] + ' ' + ev.data[1]);
	}
	
	// Active ou pas le textarea pour la justification, suivant la valeur des deux select
	// Pas trop élégant, mais ça marche...
	function changeHandler1() {
		var flg_active = false;
		if ($('select.espace').val()=="plus de 1 To") flg_active = true;
		if ($('select.duree').val()=="< 3 ans") flg_active = true;
		if ($('select.duree').val()=="< 5 ans") flg_active = true;
		if ($('select.duree').val()=="autre") flg_active = true;
		if (flg_active)
		{
			$('#sond_justif_donn_perm').prop('disabled',false);
		}
		else
		{
			$('#sond_justif_donn_perm').prop('disabled',true);
		}
	}
	*/
	
})
