$(document).ready( function()
    {
    $("input[id$='_mail'][type='text']").prop('size','70');
    $("input[id$='_mail'][type='text']").unbind('autocomplete').autocomplete(
        {
        delay: 500,
        minLength : 4,
        source : function(requete, reponse)
            {
            $.ajax({
                   url: $(".mail").data("mail_autocomplete"),
                   type: "POST",
                   dataType: "json",
                   data: { 'autocomplete_form' : { 'mail' : requete.term } }, // structure compatible symfony
                   context: $(this)
                   })
            .done(function(data) { reponse(data); })
            .fail(function(xhr, status, errorThrown) { alert (errorThrown); });
            },
        select :  function(event, ui ) {$("input[id$='_mail'][type='text']").val(  ui.item.value);}
        });
    
    }
); // $(document).ready()

