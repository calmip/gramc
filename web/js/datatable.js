$(document).ready(function() { // table projets par année
	$('#table_projets_annee').DataTable( {
		"bPaginate": false,
		"bFilter":	 false,
		"info":    	 false,
		"aoColumnDefs": [{bSortable: false,aTargets: [ 1,3 ]}]
	});
});

$(document).ready(function() { // table projets par année
	$('#table_projets_data').DataTable( {
		"bPaginate": false,
		"bFilter":	 false,
		"info":    	 false,
		"aoColumnDefs": [{bSortable: false,aTargets: [ 2,3 ]}]
	});
});

$(document).ready(function() { // table projets par session
	$('#table_projets_session').DataTable( {
		"bPaginate": false,
		"bFilter":	 false,
		"info":    	 false,
		"aoColumnDefs": [{bSortable: false,aTargets: [ 1, 3, 5 ]}]
	});
});

$(document).ready(function() { // table tous les projets
	var dt = $('#projets_tous').DataTable( {
		"bPaginate": false,
		"bFilter":	 false,
		"info":    	 false,
		"aoColumnDefs": [{bSortable: false,aTargets: [ 2 ]}]
	});
	// (ne marche pas) dt.fnSort([1,'asc']);
});

$(document).ready(function() { // table utilisateurs
	$('#utilisateurs').DataTable( {
		"bPaginate": false,
		"bFilter":	 false,
		"info":    	 false,
		"aoColumnDefs": [{bSortable: false,aTargets: [0,1,2]}]
	});
});

$(document).ready(function() { // table projets par session
	$('#bilan_session').DataTable( {
		"bPaginate": false,
		"bFilter":	 false,
		"info":    	 false,
		"aoColumnDefs": [{bSortable: false,aTargets: [ 1 ]}]
	});
});

$(document).ready(function() { // table anciennes expertises
	$('#old_expertises').DataTable( {
		"bPaginate": false,
		"bFilter":	 false,
		"info":    	 false,
		"aoColumnDefs": [{bSortable: false,aTargets: [ 2,4 ]}]
	});
});

$(document).ready(function() { // table d'affectation des experts
	$('#affecte_experts').DataTable( {
		"bPaginate": false,
		"bFilter":	 false,
		"info":    	 false,
		"aoColumnDefs": [{bSortable: false,aTargets: [ 3,4]}]
	});
});

$(document).ready(function() { // table statistiques laboratoires
	$('#tab_statistiques_labo').DataTable( {
		"bPaginate": false,
		"bFilter":	 false,
		"info":    	 false,
		//~ "aoColumnDefs": [{bSortable: false,aTargets: [ 1, 3, 5 ]}]
	});
});

$(document).ready(function() { // table toutes les publis
	$('#table_publis').DataTable( {
		"bPaginate": false,
		"bFilter":	 false,
		"info":    	 false,
		"aoColumnDefs": [{bSortable: false,aTargets: [ 1,2 ]}]
	});
});

$(document).ready(function() { // table publis par projet
	$('#publis_projet').DataTable( {
		"bPaginate": false,
		"bFilter":	 false,
		"info":    	 false
	});
});

 $(document).ready(function() { // table general
	$('#general').DataTable( {
		"bPaginate": false,
		"bFilter":	 false,
		"info":    	 false,
        "aoColumnDefs": [{bSortable: false,aTargets: [ 0 ]}]
	});
});

$(document).ready(function() { // table laboratoires
	$('#laboratoires').DataTable( {
		"bPaginate": false,
		"bFilter":	 false,
		"info":    	 false,
        "aoColumnDefs": [{bSortable: false,aTargets: [ 0 ]}]
	});
});


