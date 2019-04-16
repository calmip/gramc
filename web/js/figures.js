$(document).ready(function() {

	// Add eventhandlers for dragover and prevent the default actions for this event
	$('.drop-zone').on('dragover', function(e) {
		//$(this).attr('class', 'dock_hover'); // If drag over the window, we change the class of the #dock div by "dock_hover"
		e.preventDefault();
		e.stopPropagation();
	});


	// Add eventhandlers for dragenter and prevent the default actions for this event
	$('.drop-zone').on('dragenter', function(e) {
		e.preventDefault();
		e.stopPropagation();
	});

	$('.drop-zone').on('dragleave', function(e) {
	//	$(this).attr('class', 'dock'); // If drag OUT the window, we change the class of the #dock div by "dock" (the original one)
	});

	// Ajouter eventhandlers pour permettre de supprimer une image téléversée
	// Sera appelée au retour d'ajax
	rem_handler();
	function rem_handler() {
		$('.icone').on('click', function(e) {
			e.preventDefault();
			e.stopPropagation();
			remove($(this).parent().attr('id'));
		});
	};

	// When drop the images
	$('.drop-zone').on('drop', function(e){ // drop-handler event
		if (e.originalEvent.dataTransfer) {
			//$('.progress-bar').attr('style', 'width: 0%').attr('aria-valuenow', '0').text('0%'); // Bootstrap progress bar at 0%
			if (e.originalEvent.dataTransfer.files.length) { // Check if we have files
				e.preventDefault();
				e.stopPropagation();
				// Launch the upload function
				upload(e.originalEvent.dataTransfer.files,$(this).attr('id')); // Access the dropped files with e.originalEvent.dataTransfer.files
			}
		}
	});

	// upload function
	function upload(files,drop_zone_id) {
	    // Create a FormData object to simulate a real form
	    var fd = new FormData();
	    fd.append('image_form[image]',files[0]);
	    fd.append('image_form[filename]',drop_zone_id);

	    // Retrieve url, remove #toto
	    h = document.URL;
	    var idieze = h.indexOf("#");
	    if (idieze>0) {
		    h = h.substring(0, idieze);
	    }
	    // JQuery Ajax
	    // On attend un tableau json avec:
	    //    -clés    = Id d'éléments (SANS le #)
	    //    -valeurs = html de ces éléments
	    $.ajax({
		    type: 'POST',
		    url: h, // URL to the PHP file which will insert new value in the database
		    data: fd, // We send the data string
		    processData: false,
		    contentType: false,
		    success: function(data) {
			json_data = $.parseJSON(data);
			$.each(json_data,function (k,v)
			{
			    $('#'+k).html(v);
			});
			// Remet en place le handler, la poubelle est peut-être neuve !
			rem_handler();
			//$('#dock').attr('class', 'dock'); // #dock div with the "dock" class
		    },
	    });
	};

	// remove function
	// param= id, le id du div dont on veut vider le contenu
	//        ce id est aussi le nom du fichier à supprimer
	function remove(id) {
	    // On fait une requête ajax pour supprimer le fichier
	    var fd = new FormData();
	    fd.append('remove_form[filename]',id);

	    // Retrieve url, remove #toto
	    h = document.URL;
	    var idieze = h.indexOf("#");
	    if (idieze>0) {
		    h = h.substring(0, idieze);
	    }
	    //h += '&ajax=1';

	    $.ajax({
		    type: 'POST',
		    url: h, // URL to the PHP file which will insert new value in the database
		    data: fd, // We send the data string
		    processData: false,
		    contentType: false,
		    success: function(data) {
			    json_data = $.parseJSON(data);
			    $.each(json_data,function (k,v) {
				    $('#'+k).html(v);
			    });

		    //$('#dock').attr('class', 'dock'); // #dock div with the "dock" class
		    },
	    });
	};

    /*
    Fonction retournant les erreurs dans un format "affichable".
    Ici j'ai volontairement choisi de n'afficher que la première erreur.
    On aurait pu les concaténer (cf commentaire plus bas)
    */

    function getFormErrors(data)
    {
	var errorMsg = '';
	if (data.error)
        {
        for (key in data.error)
            {
            // ou errorMsg = errorMsg + ' ' + ...
            if (data.error[key]['0'].length > 1)
                errorMsg = data.error[key]['0'];
            else
                errorMsg = data.error[key];
            }
        }
    else
        errorMsg = data;

    return errorMsg;
    };
});
