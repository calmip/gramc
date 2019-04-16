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
    $( "input.cb_stat" ).change(change_cb_stat);

    function change_cb_stat() {
	$( "section div" ).show();
	$("input.cb_stat").each(function(){
	    var cl = '.' + $(this).attr("id");
	    if (!$(this).is(":checked")) {
		$(cl).parent().hide();
	    };
	});
	hide_or_show_info();
    };
    
    /* Si tous les tableaux d'incohérences sont vides, cacher tout le div */
    function hide_or_show_info() {
	var all_hidden=true;
	$("#tableau_affichage2 input.cb_stat").each(function(){
	    if ($(this).is(":checked")) {
		all_hidden=false;
	    };
	});
	if (all_hidden) {
	    $("#incoherences").hide();
	} else {
	    $("#incoherences").show();
	}
    }
});
