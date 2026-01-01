/**
	* Fonction qui permet de changer la date dans le champ caché ta_value
	* et envoie le formulaire
	*/
function changedate(newdate){
	document.getElementById("ta_value").getAttributeNode("value").value = newdate;
	
	document.changePreferences.submit();
}

/**
	* Fonction qui permet de changer la granularité (ta)
	* et envoie le formulaire
	*/
function changeGranul(ta){
	document.getElementById("ta_mode").getAttributeNode("value").value = ta;
	
	document.changePreferences.submit();
}

/**
	* Fonction qui permet de switcher du mode day au mode hour, sur une journée bien précise
	*/
function switchDate(ta, ta_value){
	document.getElementById("ta_mode").getAttributeNode("value").value = ta;
	document.getElementById("ta_value").getAttributeNode("value").value = ta_value;

	document.changePreferences.submit();
}

/**
	* Fonction qui permet de vérifier le mode d'affichage avant d'envoyer le formulaire
	*/
function checkMode(id){
	if(id == 'show_errors_first'){
		if( document.getElementById('show_errors_first').checked ){
			document.getElementById('show_errors_first').checked = true;
			document.getElementById('show_errors_only').checked = false;
		}
		else{
			document.getElementById('show_errors_first').checked = false;
		}
	}
	else if( id == 'show_errors_only' ){
		if( document.getElementById('show_errors_only').checked ){
			document.getElementById('show_errors_only').checked = true;
			document.getElementById('show_errors_first').checked = false;
		}
		else{
			document.getElementById('show_errors_only').checked = false;
		}
	}

	document.changePreferences.submit();
}

/**
	* Fonction d'export excel
	*/
function export_as_excel(product_id, ta_value, ta_mode, show_errors, ajax_file){
	str_params = "productid="+product_id;
	str_params+= "&ta_value="+ta_value;
	str_params+= "&ta_mode="+ta_mode;
	str_params+= "&show_errors="+show_errors;
	
	new Ajax.Request(ajax_file,
			{
				method: 'get',
				parameters: str_params,
				onSuccess: function(link){
											if(link.responseText != ""){
												window.open("openExportFile.php?type=Excel&url="+link.responseText, 
																		'', 
																		'resizable=yes,scrollbars=yes,status=no,width=500,height=100');
											}
											else if(link.responseText == "error"){
												alert('An error is occured, sorry it\'s impossible to generate the excel file');
											}
											else
												alert('No data available on this page');
										}
			});	
}