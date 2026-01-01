/**
*	@cb4100@
*	- Creation SLC	 13/11/2008
*
*	Fonctions du Report builder
*
*	Fonctions qui pilotent le report builder au niveau du report (pas des éléments)
*
*	15/07/2009 GHX
*		- Ajout de fonctions pour corriger le BZ10570 [REC][T&A Cb 5.0][Report Builder]: pas de message si le rapport n'est pas configuré
*	05/08/2009 GHX
*		- Ajout d'une condition dans la fonction closeEditSelecteur()
*	07/08/2009 GHX
*		- Correction du BZ 10877[REC][T&A Cb 5.0][TP#1][TS#AC17-CB50][Report Builder] : les "/" dans le nom du rapport empêchent la preview des rapports
*	12/08/2009 GHX
*		- RE - Correction du BZ 10877[REC][T&A Cb 5.0][TP#1][TS#AC17-CB50][Report Builder] : les "/" dans le nom du rapport empêchent la preview des rapports
*			-> Modification de l'expression reguliere
*/


//	Affiche ou cache les propriétés du report
function get_properties() {
	if ($('gtm_properties').style.display=='block') {
		// on cache les properties
		$('gtm_properties').style.display='none';
		// on change l'image (off)
		myImg = $('gtm_list').getElementsByClassName('info')[0].getElementsByTagName('IMG')[0];
		myImg.src = myImg.src.replace(/.png/gi,'_off.png');
	} else {
		// on affiche le formulaire d'édition des properties
		$('gtm_properties').style.display='block';
		// on change l'icone (on)
		myImg = $('gtm_list').getElementsByClassName('info')[0].getElementsByTagName('IMG')[0];
		myImg.src = myImg.src.replace(/_off.png/gi,'.png');
	}
}


// fonction de confirmation de la suppression du report
function delete_report() {
	if (confirm("Are you sure you want to delete that report?")) {
		$('delete_gtm_form').submit();
	}
}


// validation du formulaire d'édition des properties du report
function check_reportForm() {
	// check page_name is filled
	if ($F('page_name') == '') {
		alert("You need to give a name to that report.");
		rouge('page_name','',true);
		return false;
	}
	// 14:16 07/08/2009 GHX
	// Correction du BZ 10877
	// 17:14 12/08/2009 GHX
	// RE-Correction du BZ 10877
	if ( /[^a-zA-Z0-9_ -]/.test( $F('page_name') ) ) {
		alert('Please enter a valid name');
		rouge('page_name','',true);
		return false;
	}
	return true;
}

// 11:55 15/07/2009 GHX
// Ajout de fonctions pour corriger le BZ10570
var _currentIdDashEditSelecteur = null;
function setIdDashEditSelecteur ( id )
{
	_currentIdDashEditSelecteur = id;
}

/**
 * Fonction appellé lorsque l'on clique sur display dans la fenêtre d'édition d'un sélecteur
 *
 */
function closeEditSelecteur ()
{
	if ( _currentIdDashEditSelecteur )
	{
		if ( $('gtm_element__'+_currentIdDashEditSelecteur) )
		{
			// 16:40 05/08/2009 GHX
			// Ajout de la condition sinon erreur JS si le sélecteur était déjà configuré
			if ( $('gtm_element__'+_currentIdDashEditSelecteur).getElementsBySelector('span[class="dash_not_configured"]')[0] )
			{
				$('gtm_element__'+_currentIdDashEditSelecteur).getElementsBySelector('span[class="dash_not_configured"]')[0].remove();
			}
		}
		_currentIdDashEditSelecteur = null;
	}
	displayReportNotConfigured();
}
/**
 * Fonction qui affiche/masque le message d'erreur comme quoi certains dashboards du rapport ne sont pas configurés
 */
function displayReportNotConfigured ()
{
	var dashNotConfigured = $$('span[class="dash_not_configured"]');
	if ( dashNotConfigured.length > 0 )
	{
		$('report_not_configured').show();
	}
	else
	{
		$('report_not_configured').hide();
	}
}