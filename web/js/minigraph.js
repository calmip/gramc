$(document).ready(function() { 
    $(function() {
	$( "#graphique" ).dialog({
	    autoOpen: false,
	    minHeight: 500,
	    minWidth: 530
	});
	
	$( "#camembert" ).click(function() {
	    $( "#graphique" ).dialog( "open" );
	    return false;
	});
    });
    
    $(function() {
	$( "#graph0" ).dialog({
	    autoOpen: false,
	    minHeight: 450,
	    minWidth: 850
	});
	
	$( ".bouton_conso" ).click(function() {
	    $( "#graph0" ).dialog( "open" );
		return false;
	    });
    });
  
    $(function() {
	$( "div.graphique" ).dialog({
	    autoOpen: false,
	    minHeight: 450,
	    minWidth: 850
	});
	var graph = document.querySelectorAll("div.graphique");
	var tab_graph = [];
	for(var index=0; index<graph.length; index++) {
	var nom_graph = "graph"+index;
	
	graph[index].id = nom_graph;
	tab_graph.push(nom_graph);
	
	$( "#"+tab_button[index]).click(function(e) { //ouverture de la fenêtre pop up
	    var id_graph = e.target.id;
	    $( "#"+tab_graph[id_graph]).dialog( "open" );
	});
	}
    });

    var button = document.querySelectorAll("button.camembert");
    var tab_button = [];
  
    for(var index=0; index<button.length; index++) {
	if(!button) {
	    alert("Impossible de récupérer le button");
	    return;
	}  
	
	button[index].id = index;
	tab_button.push(index);
    }
  

    var boutons_conso = document.querySelectorAll(".bouton_conso");
    var boutons_conso_tab = [];
    for(var index=0; index<boutons_conso.length; index++) {
	var nom_boutons_conso = "boutons_conso"+index;
    
	boutons_conso[index].id = nom_boutons_conso;
	boutons_conso_tab.push(nom_boutons_conso);
    
	$( "#"+boutons_conso_tab[index]).click(function(e) { 
	    e.preventDefault();
	    var conso = $(this).next();
	    var donnees = conso.val();
	
	    console.log(donnees);
	    //~ donnees = JSON.parse(donnees);
	    console.log(donnees);
	
	    h =document.URL;
	    h += "&ajax=1"; 
	    $.ajax({
		type: "POST",
		data: '&donnees=' +donnees,
		url : h,
		success: function(data) {
		    console.log("OK");
		    $('#graph0').html(data);
		}
	    });
	});
    }
});
