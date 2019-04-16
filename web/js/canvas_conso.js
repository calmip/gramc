$(document).ready(function() { 
	
    // Transformation des div en dialog
    $( "div.graphique" ).dialog({
      autoOpen: false,
      minHeight: 450,
      minWidth: 850
    });

    // Click sur le bouton conso -> Ouverture du dialog
    $(".bconso").click(function(e) {
	var id=e.target.id;
	id = id.replace("bconso_", "graph_"); 
	$('#'+id).dialog('open');
    });

    // Dessin des canvas représentant la conso
    var conso_seuil_1 = parseFloat($("#conso_seuil_1").val());
    var conso_seuil_2 = parseFloat($("#conso_seuil_2").val());

    $(".canvas_conso").each(function() {
	var ctx = this.getContext('2d');
	if(!ctx) {
	    alert("Impossible de récupérer le contexte du canvas");
	    return;
	}
	heures_conso = parseFloat($('#'+this.id.replace('canvas_','conso_')).val());
	if(conso_seuil_1 <= heures_conso < conso_seuil_2){
	    color= "#FFC000";
	}
	if(heures_conso >= conso_seuil_2) {
	    color= "#FF0000";
	}
	if(heures_conso < conso_seuil_1) {
	    color= "#00FF00";
	}	

	ctx.beginPath();
	ctx.strokeStyle = color;
	ctx.strokeRect(1,5,108,19);
	ctx.closePath();
	
	ctx.beginPath();
	ctx.fillStyle = color;
	ctx.fillRect(5,8,heures_conso,13);
	ctx.closePath();
    });	    

    // Click sur le canvas -> Ouverture du dialog
    $(".canvas_conso").click(function(e) {
	var id=e.target.id;
	id = id.replace("canvas_", "graph_"); 
	$('#'+id).dialog('open');
    });

});
