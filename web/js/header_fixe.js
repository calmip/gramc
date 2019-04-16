$(window).scroll(function(){
	var style = $('#header').css( "left" ); // récupère la valeur du margin-left
	
	if(style.indexOf("px")>=0) // retire le "px" de la chaîne
	{
		style = style.replace("px",""); 
	}
	
	if (style<0) {   // fixe le menu lors d'un scroll
    $('#header').css({
        'left': $(this).scrollLeft() + 15
         
    });
	} else {	// ne bloque pas le menu une fois arrivé tout à droite
		$('#header').css({
        'left': $(this).scrollLeft() + 0
    });
	}
});

