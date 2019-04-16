$(document).ready(function() 
{
	url=$("#fileuploader").children("a").attr("href");
	$("#fileuploader").uploadFile(
	{
		url: url,
        fileName: "rapport[rapport]",
		//returnType: "json",
		dynamicFormData: function() 
		{
            var data = { "rapport" : {} };
			return data;
		},
		onSuccess:function(files,data,xhr,pd) 
		{
			//msg=JSON.stringify(data);
            msg = data;
			if (msg == 'OK') 
			{
				$('#uploadstatus').html("<p>Votre rapport d'activité est correctement enregistré.</p>").addClass("info").addClass("message");
				$('#fileuploader').remove();
				$('#uploadform').remove();
			} else 
			{
				//alert ( JSON.stringify(msg) );
				$('#uploadstatus').html('<p>'+msg+'</p>').addClass("erreur").addClass("message");
			}
		},
		dragDropStr: "<span><b>Faites glisser et déposez les fichiers</b></span>",
		uploadStr:"Téléversement du rapport"
	});
});

