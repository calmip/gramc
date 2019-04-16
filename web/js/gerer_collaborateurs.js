$(document).ready(
function()
{

//var ht = "<body><p>Paragraph Here</p></body>";
//alert( $('p', '<div>' + ht + '</div>').text() );

// collection =
$(".collection").each(function()
    {
    supprime_aff_collabs();
    // ajout d'un compteur des lignes
    if(  $(".collection-contents",this).data("count") == 0 )
        $(".collection-contents",this).data("count", $(".collection-tbody",this).find('tr').length);

    // ajout d'un paramètre id unique au bouton
    $(this).append('<button class="add" id="' + $(this).parent().parent().attr('id') +
                                '_add" type="submit">Ajouter une ligne dans le formulaire</button>');

    $(this).find(".collection-tbody-old").find("input[id$='_mail'][type='text']")
        .attr("class","mail ui-autocomplete-input").prop('disabled', true)
        .attr("title","Vous ne pouvez pas modifier vous-même l'adresse courriel de vos collaborateurs");

    // Le controleur envoie automatiquement une nouvelle ligne
    //nouvelle_ligne($(this));
    add_autocomplete( $(this) ); // ajout d'autocomplete sans nouvelle ligne
    });

$(".collection .add").click(function(event)
    {
    event.preventDefault();
    nouvelle_ligne( $(this) );
    });

$(".collection .resp").find("input[id$='_delete'][type='checkbox']").prop('disabled', true); // impossible de supprimer le responsable
$(".collection .resp").find("input[id$='_delete'][type='checkbox']").css("opacity",'0'); // impossible de supprimer le responsable

$(".collection .add").each( function() { opacity( $(this) ); }); // ajout du comportement du bouton delete à toutes les lignes
}); // $(document).ready()

function nouvelle_ligne(context)
    {
    var longueur = $(".collection-contents",context.parent()).data("count");
    var prototype = $(".collection-contents",context.parent()).data("prototype");
    prototype = prototype.replace(/__name__/g, longueur);
    $(".collection-contents",context.parent()).data("count", longueur + 1);
    $(".collection-tbody",context.parent()).append(prototype);

    $(".collection-tbody .collection-tbody-new",context.parent())
        .find("input[id$='_mail'][type='text']").attr("class","mail ui-autocomplete-input");
    opacity( context );// ajout du comportement du bouton delete à la nouvelle ligne
    //alert('before');
    mail_autocomplete(context); //autocomplete de l'adresse mail
    }

function add_autocomplete(context)
    {
    $(".collection-tbody .collection-tbody-new",context.parent())
        .find("input[id$='_mail'][type='text']").attr("class","mail ui-autocomplete-input");
    opacity( context );// ajout du comportement du bouton delete à la nouvelle ligne
    //alert('before');
    mail_autocomplete(context); //autocomplete de l'adresse mail
    }


///////////////////////

function opacity(context)
{

    $(".collection-contents .collection-tbody-new input[id$='_mail'][type='text']", context.parent()).each( function()
        { reactive_ligne( $(this) ); });

    $(".collection-contents .collection-tbody-new input[id$='_mail'][type='text']", context.parent()).unbind('on').on('propertychange input', function()
        { reactive_ligne( $(this) ); })

    $("input[id$='_mail'][type='text']", context.parent()).unbind('blur').on('blur', function()
        { complete_ligne( $(this).val(), $(this) ); })


    $("input[id$='_delete'][type='checkbox']", context.parent() .parent() ).unbind('change')

    // $(".collection-contents .collection-tbody input[id$='_delete'][type='checkbox']", context.parent()).unbind('change')
        .change(function()
        {
        //alert('here');
    if( $(this).prop('checked') )
        {
        $(this).parent().parent().find(":not([id$='_delete']):not([id$='_id'])").prop('disabled', true);
        $(this).parent().parent().find("[id$='_delete'],[id$='_id']").prop('disabled', false);
        prenom = $(this).parent().parent().find("input[name*='[prenom]']").val();
        nom    = $(this).parent().parent().find("input[name*='[nom]']").val();
        if( nom == '' && prenom == '' ) nom = "cet utilisateur";
        alert('ATTENTION ! Voulez-vous vraiment supprimer '+prenom+' '+nom+' de la liste des collaborateurs ?');
		}
    else
        {
        $(this).parent().parent().find(":not([id$='_mail'])").prop('disabled', false);
        $(this).parent().parent().find("[id$='_mail']").each( function() {complete_ligne( $(this).val(), $(this) );});
        }
    });
} // function opacity()

////////////////////////////////////////

function mail_autocomplete(context)
    {
    //alert('autocomplete');
    $('.mail',context.parent() ).unbind('autocomplete').autocomplete(
        {
        delay: 500,
        minLength : 4,
        source : function(requete, reponse)
            {
            $.ajax({
                   url: $(".collection-contents").data("mail_autocomplete"),
                   type: "POST",
                   dataType: "json",
                   data: { 'autocomplete_form' : { 'mail' : requete.term } }, // structure compatible symfony
                   context: $(this)
                   })
            .done(function(data) { reponse(data); })
            .fail(function(xhr, status, errorThrown) { alert (errorThrown); });
            },
        select :  function(event, ui ) {complete_ligne( ui.item.value, $(this) );}
        });
    } // function mail_autocomplete()

// ajax pour compléter la ligne

function complete_ligne( mail, context )
{
 $.ajax({
            url: $(".collection-contents").data("mail_autocomplete"),
                   type: "POST",
                   dataType: "json",
                   data: { 'Individu' : { 'mail' :  mail } }, // structure compatible symfony
                   context: context,
                   converters: { 'text json': true},
                   })
            .done(function(data)
                {
                //alert( data );
                var regex = /reallynouserrrrrrrr/;
                if( ! regex.test( data ) )
                //if( data != "nouser" )
                    {
                    var input = '<div>' + data + '</div>';
                    $("input[id$='_prenom']", context.parent().parent() ).val( $('#Individu_prenom', input).val() ).prop('disabled', false) ;
                    $("input[id$='_nom']", context.parent().parent() ).val( $('#Individu_nom', input).val() ).prop('disabled', false) ;
                    $("select[id$='_statut']", context.parent().parent() ).val( $('#Individu_statut', input).val() ).prop('disabled', false) ;
                    $("select[id$='_laboratoire']", context.parent().parent() ).val( $('#Individu_laboratoire', input).val() ).prop('disabled', false) ;
                    $("select[id$='_etablissement']", context.parent().parent() ).val( $('#Individu_etablissement', input).val() ) .prop('disabled', false);
                    $("input[id$='_id']", context.parent().parent() ).val( $('#Individu_id', input).val() ).prop('disabled', false) ;
                    $("input[id$='_mail'][type='text']", context.parent().parent() ).prop("disabled",true)
                    .attr("title","Vous ne pouvez plus changer l'adresse de courriel !");
                    }
                else
                    $("input[id$='_delete'][type='checkbox']", context.parent().parent() )
                        .not(":checked").parent().parent().find("input[id$='_mail'][type='text']").prop("disabled",false)
                        .attr("title","Vous pouvez encore changer l'adresse de courriel !");;
                })
            .fail(function(xhr, status, errorThrown) { /* alert ('Erreur complete_ligne ' + status + xhr); */ });
}

//////////////////////////////////////////////

function reactive_ligne(context)
{
if( context.val() == '' )
        {
        // le champ mail est vide
        context.parent().parent().find(":not(input[id$='_mail'][type='text'])").prop("disabled",true)
            .attr("title","Commencez par l'adresse de courriel !");
        context.parent().parent().find(":not([disabled='true'])").attr("title","Commencez par l'adresse de courriel !");
        }
    else
        {
        // le champs mail contient quelque chose mais ce n'est pas à cause d'autocomplete

        context.parent().parent().find(":not(input[id$='_mail'][type='text'])").prop("disabled",false).attr("title","Vous pouvez remplir ce champ !");
        //context.parent().parent().find("input[id$='_prenom'][type='text']").prop("disabled",false).attr("title","CHAMP OBLIGATOIRE");
        //context.parent().parent().find("input[id$='_nom'][type='text']").prop("disabled",false).attr("title","CHAMP OBLIGATOIRE");
        context.parent().parent().find("input[id$='_prenom']").prop("disabled",false).attr("title","CHAMP OBLIGATOIRE !");
        context.parent().parent().find("input[id$='_nom']").prop("disabled",false).attr("title","CHAMP OBLIGATOIRE !");
        context.parent().parent().find("select").prop("disabled",false).attr("title","Vous pouvez remplir ce champ !");
        context.parent().parent().find("select").find("option").prop("disabled",false);
        }

}

function supprime_aff_collabs()
{
    $(".collection").find("input[id$='_delete'][type='checkbox']").each(function ()
        {
            if ($(this).is(':checked'))
                {
                        //alert('dégage');
                        $(this).parents('tr').remove();
                }
        });
}

