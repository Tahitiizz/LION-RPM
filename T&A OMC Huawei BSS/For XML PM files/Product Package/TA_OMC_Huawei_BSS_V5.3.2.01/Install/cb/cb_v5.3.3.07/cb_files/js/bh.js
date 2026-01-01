/*
 *
 *  12/08/2010 NSE DE firefox bz 16851 : remplacement options(0) par options[0]
 *  09/11/2011 ACS BZ 24526 Display a message when saving or deleting busy hour configuration
 *
*/
// // Fonction de recherche des valeurs du sélecteur de raw counters/kpi
// La variable fieldType correspond au type de données (raw/kpi)
// La cible correspond à la liste du formulaire qui recevra les données
// La famille est nécessaire pour les requêtes de récupération des raw counters et kpi
// Avant de lancer la requête, on désactive le champ 'cible' et on affiche le message 'loading...'

function getFieldValue(niveau0,fieldType,cible,family,product,selectedValue,max_size)
{
	$(cible).disabled = true;
	$(cible).options.length = 1;
	$(cible).options[0].text = "Loading ...";
	$(cible).options[0].value = "makeSelection";

	var url = niveau0+"php/ajax_select_raw_kpi.php?champ="+fieldType+"&cible="+cible+"&family="+family+"&max_size="+max_size+"&product="+product;
	new Ajax.Request(url,{
		onSuccess: function(res) {
			initOptionSelector(res.responseText,selectedValue,cible);
		}
	})

}

//voir format Xavier C. dans le fichier ajax_select_raw_kpi.php
//traite la reponse retourne par le script php/ajax_select_raw_kpi.php
function initOptionSelector(responseText,selectedValue,cible)
{
	var list_values = responseText.split('|column|');
	var list_to_update = $(cible);

	list_to_update.options.length = list_values.length-1;
	for (i=1; i< list_values.length - 1; i++) {

		infos = list_values[i].split('|field|');
		list_to_update.options[i].value = infos[0];//valeur
		list_to_update.options[i].text = infos[1];//label

		if(list_to_update.options[i].value==selectedValue)
			list_to_update.options.selectedIndex=i;
	}

	list_to_update.disabled = false;
        /* 12/08/2010 NSE DE firefox bz 16851 : remplacement options(0) par options[0]*/
	list_to_update.options[0].text='Make a Selection';
}

/*soumet le formulaire de la BH s'il est correctement renseigne*/
function save_bh_def(form,data_type,data_value,action) {
	hideMessages();
	
	if(data_type=='makeSelection' || data_value=='makeSelection')
		alert('Please, select a raw or a kpi');
	else{
		action.value="save";
		form.submit();
	}
}

/*soumet le formulaire de la BH s'il est correctement renseigne*/
function delete_bh_def(form,action,family) {
	hideMessages();
	
	if(confirm('Delete Busy Hour for '+family+'?')){
		action.value="delete";
		form.submit();
	}
}

function hideMessages() {
	if ($('msg_info')) {
		$('msg_info').style.display = 'none';
	}
	if ($('msg_error')) {
		$('msg_error').style.display = 'none';
	}
}
