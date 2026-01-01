/**
*	Cette fonction initialise la fonction d'autrefresh
*
*	@author	BBX - 29/10/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*	@params	string	id de la checkbox
*/
function updateAutoRefresh(ar_id)
{
	// On stoppe le timer
	if (autorefreshTimer != null) autorefreshTimer.stop();
	if (updateSessionTimer != null) updateSessionTimer.stop();
	// Suivant l'etat de la case à cocher, on va recrée le timer ou le supprimer
	if($(ar_id).checked == true) {
		var delay = parseFloat($('selecteur_autorefresh_delay').value)*60;
		autorefreshTimer = new PeriodicalExecuter(launchAutoRefreshAction, delay);
		updateSessionTimer = new PeriodicalExecuter(updateSession, 60);
	}
	else {
		autorefreshTimer = null;
		updateSessionTimer = null;
	}
}

/**
*	Cette fonction effectue le requête Ajax de mise à jour de la ta
*
*	@author	BBX - 21/11/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*/
function launchAutoRefreshAction()
{
	var url = _selecteur_url_module+'php/selecteur.ajax.php';
	// Récupération de la ta
	var ta = $('selecteur_ta_level').value;
	// Récupération de la na
	var na = $('selecteur_na_level').value;
	// Récupération de la na 3ème axe
	var na_axe3 = $('selecteur_axe3') ? $('selecteur_axe3').value : '';
	// Test de l'id page
	var id_page = (_dashboard_id_page) ? '&id_page='+_dashboard_id_page : '';
	// Requête ajax
	new Ajax.Request(url, 
	{
		method: 'get',
		parameters: 'action=1'+id_page+'&na='+na+'&ta='+ta+'&na_axe3='+na_axe3,
		onComplete : submitSelecteur
	});	
}

/**
*	Cette fonction met à jour de la ta puis soumet le formulaire
*
*	@author	BBX - 21/11/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*/
function submitSelecteur(result)
{
	var ta_value = result.responseText;
	if(ta_value != '') {
		var array_ta_value = ta_value.split('|');
		if(array_ta_value.length > 1)
			$('selecteur_hour').value = array_ta_value[1];
		$('selecteur_date').value = array_ta_value[0];
	}
	$('selecteur').submit();
}

/**
*	Cette fonction met à jour la session
*
*	@author	BBX - 29/10/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*/
function updateSession() 
{
	var url = _selecteur_url_module+'php/selecteur.ajax.php';
	new Ajax.Request(url, 
	{
		method: 'get',
		parameters: 'action=2'
	});							
}

// variable globale de l'autorefresh
var autorefreshTimer = null;
var updateSessionTimer = null;

// Vérification du staut de l'autorefresh
updateAutoRefresh('selecteur_autorefresh_chk');
