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
    $('.figure').on('click',function(e) {
	var img_src = $(this).attr('src');
	var img_alt = $(this).attr('alt');
	var width   = $(this).data("width");        // pas utilisé
	var height  = $(this).data("height");       // pas utilisé
	$('#dialog').html('<img src="'+img_src+'" alt="'+img_alt+'" title="'+img_alt+'" />');
	$('#dialog').dialog({autoOpen: false, modal: false });
	$('#dialog').dialog( "option", "title", img_alt );
	$('#dialog').dialog( "option", "height", 'auto' );
	$('#dialog').dialog( "option", "width", 'auto' );
	$('#dialog').dialog('open');
    });

    $('.conso').on('click',function(e) {
	var href = $(this).data('href');
	var img_alt = $(this).attr('alt');
	var width   = $(this).data("width");        // pas utilisé
	var height  = $(this).data("height");       // pas utilisé
	//$('#dialog').html('<img src="'+img_src+'" alt="'+img_alt+'" title="'+img_alt+'" />');
	$.ajax({
		url: href,
		type: "GET",
		context: $(this)
		})
	.done(function(data)
	    {
	    $('#dialog-compta').html(data);
	    $('#dialog-compta').dialog({autoOpen: false, modal: false });
	    $('#dialog-compta').dialog( "option", "title", img_alt );
	    $('#dialog-compta').dialog( "option", "height", 'auto' );
	    $('#dialog-compta').dialog( "option", "width", 'auto' );
	    $('#dialog-compta').dialog('open');
	    })
	});
    
});



