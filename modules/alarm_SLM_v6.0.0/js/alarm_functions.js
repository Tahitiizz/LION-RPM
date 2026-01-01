/* Fonctions servant à la classe de listage des alarmes existantes */

var _winAlarmMail;

var _alarmIdForGroup;
var _alarmTypeForGroup;
var _alarmTAForGroup;


/**
 * Gère l'affichage de la fenetre de sélection des groupes et son contenu
 *
 * @param string window_title le titre de la fenetre des groupes
 * @param string window_content nom du calque contenant les informations à afficher
 * @param integer _alarm_id identifiant de l'alarme à laquelle on veut attacher des groupes de destinataires
 * @param string _alarm_ta valeur de l'agregation temporelle de l'alarme
 * @param string _alarm_type type de l'alarme
 * @return void
 */

function showGroups(window_title, window_content, _alarm_id, _alarm_ta, _alarm_type)
{
	_alarmIdForGroup	= _alarm_id;
	_alarmTypeForGroup	= _alarm_type;
	_alarmTAForGroup	= _alarm_ta;

	new Ajax.Request('php/alarm_actions_manager.php',
	{
		method:'get',
		parameters: {action: 'get_groups', alarm_id: _alarm_id, alarm_type: _alarm_type},
		onSuccess: function(data){
						if(data.responseText == "failed"){
							alert('An error occurs. No group found');
						}
						else
						{
							populateGroupSelect(eval('(' + data.responseText + ')'));					
							openAlarmMailWindow(window_title, window_content);
						}
						//opener.close();
					}
	    ,
		onFailure: function(){ alert('An error occurs. No group found'); }
	});
}

/**
 * Permet de remplir les listes contenant les groupes de l'application et ceux rattachés à l'alarme
 *
 * @param array groups liste des groupes
 * @return void
 */

function populateGroupSelect(groups){

	// On vide d'abord les 2 listes

	$('available_groups').options.length = 0;
	$('suscribed_groups').options.length = 0;

	// Population de la liste des groupes de l'application

	if (groups.available != "")
	{
		var available_list = (groups.available).split('|s|');

		$('available_groups').options.length = available_list.length;

		for (var i = 0;i<available_list.length;i++)
		{
			var available_group = available_list[i].split('|:|');

			$('available_groups').options[i].value	= available_group[0];
			$('available_groups').options[i].text	= available_group[1];
		}
	}

	// Population de la liste des groupes abonnés à l'alarme

	if (groups.suscribers != "")
	{
		var suscribers_list = (groups.suscribers).split('|s|');

		$('suscribed_groups').options.length = suscribers_list.length;

		for (var i = 0;i<suscribers_list.length;i++)
		{
			var suscribers_group = suscribers_list[i].split('|:|');

			$('suscribed_groups').options[i].value	= suscribers_group[0];
			$('suscribed_groups').options[i].text	= suscribers_group[1];
		}
	}
}

/**
 * Permet d'afficher la fenetre de sélection des groupes de destinataires d'une alarme
 *
 * @param string title le titre de la fenetre des groupes
 * @param string div_ocntent nom du calque contenant les informations à afficher
 * @return void
 */

function openAlarmMailWindow(title, div_content){
	if ( $(_winAlarmMail) == null)
	{
		_winAlarmMail = new Window({ 
		className:"alphacube",
		title: title,
		width:500, height:270,
		minWidth:500, minHeight:270,
		resizable:false,
		minimizable:false,
		recenterAuto: false,
		maximizable:false
		});

		_winAlarmMail.setZIndex(10000);
		_winAlarmMail.setContent(div_content);
		_winAlarmMail.showCenter(false,135);
		_winAlarmMail.updateHeight();
	}
	else
	{
		_winAlarmMail.showCenter(false,135);
		_winAlarmMail.updateHeight();
	}
}

/**
 * Permet de transférer les éléments sélectionnés d'une liste vers une autre
 *
 * @param string select_from identifiant de la liste d'origine
 * @param string select_to identifiant de la liste de destination
 * @return void
 */

function transfertSelectedElement(select_from, select_to){

	var from	= $(select_from);
	var to		= $(select_to);

	var selected_count			= 0;
	var options_not_selected	= Array();

	// Transfert des options sélectionnées de la liste "from" vers la liste "to"

	for (var i=0;i < from.options.length;i++)
	{
		if (from.options[i].selected == true)
		{
			to.options.length = to.options.length+1;
			
			to.options[to.options.length-1].value	= from.options[i].value;
			to.options[to.options.length-1].text	= from.options[i].text;

			selected_count += 1;
		}
		else
		{
			options_not_selected[options_not_selected.length] = from.options[i];
		}
	}

	// Suppression de la liste "from" des éléments transferés

	from.options.length = from.options.length - selected_count;

	for (var i=0;i < options_not_selected.length;i++)
	{
			from.options[i].selected	= false;
			from.options[i].value		= options_not_selected[i].value;
			from.options[i].text		= options_not_selected[i].text;		
	}
}

/**
 * Permet de sauvegarder les membres d'un groupe définis dans une liste
 *
 * @param string select_elt identifiant de la liste contenant les membres du groupes
 * @return void
 */

function saveSelectedGroup(select_elt){

	var elts_selected = Array();

	for (var i=0;i < $(select_elt).options.length;i++)
	{	
		elts_selected[elts_selected.length]	= $(select_elt).options[i].value;
	}

	new Ajax.Request('php/alarm_actions_manager.php',
	{
		method:'get',
		parameters: {action: 'save_alarm_suscribers', suscribers_list: elts_selected.join('|s|'), alarm_id: _alarmIdForGroup, alarm_ta: _alarmTAForGroup, alarm_type: _alarmTypeForGroup},
		onSuccess: function(data){
						if(data.responseText == "failed"){
							alert('An error occurs');
						}
						else
						{
							var email_btn = $('mail_alarm_'+_alarmIdForGroup);
							
							if (elts_selected.length > 0)
							{
								email_btn.className = "email_enabled";
								email_btn.setAttribute('alt', "Desactivate email sending");
							}
							else
							{
								email_btn.className = "email_disabled";
								email_btn.setAttribute('alt', "Activate email sending");
							}
						}
						$(_winAlarmMail).close();			
					}
	    ,
		onFailure: function(){ alert('An error occurs'); }
	});
}

/**
 * Construit et affiche une fenêtre de confirmation de la suppression d'une alarme sélectionnée
 *
 * @param string title titre de la fenêtre de confirmation de suppression
 * @param string msg message à afficher pour confirmer la suppression
 * @param integer id identifiant de l'alarme à supprimer
 * @param string type type de l'alarme à supprimer
 * @param string family famille de l'alarme à supprimer
 * @param string ta valeur de l'agrégation temporelle de l'alarme à supprimer
 * @return void
 */

function openAlarmDropDialog(title, msg, id, type, family, ta){
	Dialog.confirm(msg, 
					{
						title:title,
						width:300, 
						okLabel: "OK",
						cancelLabel: "Cancel",
						className:"alphacube",
						id: "alarmDropDialog", 
						ok:function() {dropAlarm(id, type, family, ta, this);}
					}); 
}

/**
 * Lance l'appel Ajax du script PHP de suppression de l'alarme
 *
 * @param integer id identifiant de l'alarme à supprimer
 * @param string type type de l'alarme à supprimer
 * @param string family famille de l'alarme à supprimer
 * @param string ta valeur de l'agrégation temporelle de l'alarme à supprimer
 * @param string opener référence à la fenêtre de confirmation de suppression de l'alarme
 * @return void
 */

function dropAlarm(id, type, family, ta, opener){
	new Ajax.Request('php/alarm_actions_manager.php',
	{
		method:'get',
		parameters: {action: 'drop_alarm', alarm_id: id, alarm_type: type, alarm_family: family, alarm_ta: ta},
		onSuccess: function(data){
						if(data.responseText == "failed"){
							alert('An error occurs. The alarm was not deleted');
						}
						else
						{
							dropAlarmLine(id);
						}
						opener.close();			
					}
	    ,
		onFailure: function(){ alert('An error occurs. The alarm was not deleted'); }
	});	
}

/**
 * Supprime la ligne correspondant à l'alarme supprimée dans le tableau HTML des alarmes
 *
 * @param integer id identifiant de l'alarme à supprimer
 * @return void
 */

function dropAlarmLine(id){

	// Suppression de la ligne

	$('alarm_line_'+id).remove();

	// Reformatage des couleurs de ligne

	var alarm_tr = $('alarm_table_list').getElementsByTagName('tr');
	var tr_list = $A(alarm_tr);

	var cpt = 0;

	tr_list.each(function(tr_list){
		if ((tr_list.id).match("^alarm_line_") && (tr_list.id != 'alarm_line_'+id))
		{
			(cpt%2 == 0) ? ($(tr_list.id).className = "zoneTexteBlanche") :  ($(tr_list.id).className = "zoneTexteStyleXPFondGris");
			cpt += 1;
		}
	});
}

/* Fonctions servant à la classe d'ajout / modification d'une alarme */

/**
 * Retourne la liste des valeurs des types d'alarme existants dans l'application
 *
 * @param string _type type de l'alarme
 * @param string _cible identifiant de la liste qui va contenir les valeurs du type sélectionné
 * @param string _family famille de l'alarme
 * @return void
 */

function getFieldValue(_type, _cible, _family){

	$(_cible).disabled = true;
	$(_cible).options.length = 1;
	$(_cible).options[0].text = "Loading ...";
	$(_cible).options[0].value = "makeSelection";
	
	new Ajax.Request('alarm_actions_manager.php',
	{
		method:'get',
		parameters: {action: 'get_type_values', type: _type, family: _family},
		onSuccess: function(data){
						if(data.responseText == "failed"){
							alert('An error occurs');
						}
						else
						{
							var tabFieldValue = (((decodeURI(data.responseText).replace(/\+/g,' ')).replace(/&amp;/g,'&'))).replace(/%2B/g,'+');
								
							
							tabFieldValue = tabFieldValue.split('|column|'); 

							$(_cible).options.length = tabFieldValue.length+1;

							$(_cible).options[0].value			= "makeSelection";
							$(_cible).options[0].text			= "Make a Selection";
							$(_cible).options[0].label_complete	= "Make a Selection";							

							for (var i =1;i<tabFieldValue.length;i++)
							{
								fieldValue = tabFieldValue[i-1].split('|field|');

								$(_cible).options[i].value			= fieldValue[0];
								$(_cible).options[i].text			= fieldValue[1];
								$(_cible).options[i].label_complete	= fieldValue[2];
							}
							$(_cible).disabled = false;
						}	
					}
	    ,
		onFailure: function(){ alert('An error occurs'); }
	});
}

/**
 * Affiche le label d'un trigger sélectionné pour tous les niveaux de criticité définis
 *
 * @param integer numero identifiant de la liste de sélection du trigger
 * @return void
 */

function changerLabel(numero) {

	var trigger_selected = $('trigger_field'+numero).options[$('trigger_field'+numero).selectedIndex];

	var trigger_value = trigger_selected.text;

	if ($('trigger_complete_label'+numero) != null) $('trigger_complete_label'+numero).remove();

	if(trigger_value != trigger_selected.label_complete)
	{
		var div_trigger_list = trigger_selected.parentNode.parentNode;
		
		var div_trigger_complete_label = new Element('div', {'id': "trigger_complete_label"+numero, 'class': "texteGris", 'style': "padding-left:105px;padding-top:5px;font-size:8pt"});
		div_trigger_complete_label.update(trigger_selected.label_complete);
		
		div_trigger_list.appendChild(div_trigger_complete_label);
	}

	if ((trigger_value == 'Make a Selection') || (trigger_value == '')) trigger_value = 'No trigger selected';
	
	if ($('trigger_label_critical'+numero) != null)	$('trigger_label_critical'+numero).value = trigger_value;
	if ($('trigger_label_major'+numero) != null)	$('trigger_label_major'+numero).value = trigger_value;
	if ($('trigger_label_minor'+numero) != null)	$('trigger_label_minor'+numero).value = trigger_value;
}

/**
 * Initie le vidage des champs des niveaux de criticité du trigger 'numero'
 *
 * @param integer numero identifiant des champs des niveaux de criticité à vider
 * @return void
 */

function remove_all_critical_level(numero) {

	var criticity = Array('critical', 'major', 'minor');

	for (var i = 0;i < criticity.length;i++)
	{
		remove_critical_level(criticity[i], numero);
	}
}

/**
 * Vide les champs du niveau de criticité 'critical' du trigger 'numero'
 *
 * @param string critical identifiant du niveau de criticité à vider
 * @param integer numero identifiant des champs du niveau de criticité à vider
 * @return void
 */

function remove_critical_level(critical, numero) {
	
	if ($('trigger_operand_' + critical + numero) != null)
	{
		$('trigger_operand_' + critical + numero).selectedIndex = 0;
		$('trigger_value_' + critical + numero).value = '';
	}
}

/**
 * Suppression des valeurs de la liste 'cible'
 *
 * @param string cible identifiant de la liste à vider
 * @return void
 */

function remove_choice(cible) {
	if (cible.options[0].value == 'makeSelection') {
		cible.options.remove(0);
	}
}

/**
 * Suppression d'un champ additionnel
 *
 * @param integer numero identifiant du champ additionnel à supprimer
 * @return void
 */

function deleteCompleteAdditionalFieldLabel(numero){
	if ($('additionnal_field_complete_label'+numero) != null) $('additionnal_field_complete_label'+numero).remove();
}

/**
 * Vide les champs d'un champ additionnel
 *
 * @param string champ identifiant du champ additionnel à vider
 * @param string champ_type identifiant du type du champ additionnel à vider
 * @return void
 */

function vider_additional_field(champ,champ_type) {
	$(champ).options[0].value='makeSelection';
	$(champ).options[0].text='Make a selection';
	$(champ).length=1;
	$(champ).selectedIndex=0;
	$(champ_type).selectedIndex=0;
}

/**
 * Initie la suppression complete d'un trigger (cad le vidage de tous ses champs)
 *
 * @param integer numero identifiant du trigger à supprimer
 * @return void
 */

function remove_trigger(numero) {

	divlist = $('trigger_list'+numero);
	divcritical = $('trigger_critical'+numero);
	divmajor = $('trigger_major'+numero);
	divminor = $('trigger_minor'+numero);

	// Réinitialisation du type et des triggers disponibles

	if($('trigger_type'+numero).nodeName == "INPUT")	// Dans le cas où il n'existe qu'un seul type, on ne vide pas la liste des triggers
	{
		if ($('trigger_field'+numero).options[0].value != "makeSelection")
		{
			new Insertion.Before($('trigger_field'+numero).options[0], '<option value="makeSelection" selected>Make a Selection</option>');
		}
					
		$('trigger_field'+numero).selectedIndex=0;
	}
	else	// Sinon, on réinitialise tous les champs
	{
		$('trigger_field'+numero).options[0].value='makeSelection';
		$('trigger_field'+numero).options[0].text='Make a selection';
		$('trigger_field'+numero).length=1;
		$('trigger_field'+numero).selectedIndex=0;
		$('trigger_type'+numero).selectedIndex=0;
	}

	remove_all_critical_level(numero);

	// Suppression de la ligne correspondant au trigger

	// Note 21/07/2008 : dder à CCT1 la commande pour lister / compter le nombre d'elements ayant un certain label / type avec Prototype

	var someTriggers = false;

	// Note : mettre le nombre de triggers max en variable JS globale

	for (var i=1;i<=5;i++)
	{
		if (($('trigger_list_'+i) != null) && (i != numero) && (!someTriggers))
		{
			someTriggers = true;
		}
	}

	if (someTriggers)	// On supprime la ligne seulement s'il en existe d'autres. Sinon, on la vide simplement
	{
		$('trigger_list_'+numero).remove();
		
		var criticity = Array('critical', 'major', 'minor');

		for (var i = 0;i < criticity.length;i++)
		{
			$('trigger_'+criticity[i]+numero).remove();
		}
	}
	else
	{		
		changerLabel(numero);
	}

	if ($('trigger_complete_label'+numero) != null) $('trigger_complete_label'+numero).remove();
}

/**
*  affiche tous les champs concernant le trigger suivant de la liste 'ordreAffichage'.
*  on ne peut afficher plus de 'nombre_max' triggers.
*/

/**
 * Permet d'ajouter un trigger
 *
 * @return void
 */

function add_trigger() {
	nombre = $('nb_trigger');
	if (nombre.value < nombre_max){
		nombre.value++;
	}
	else
	{
		alert("Static Alarm is limited to "+nombre_max+" triggers")
		for (i=0; i < nombre_max; i++) {
			if ($('trigger_list'+ordreAffichage[i]).style.display=='none') {
				$('trigger_list'+ordreAffichage[i]).style.display='block';
				$('trigger_critical'+ordreAffichage[i]).style.display='block';
				$('trigger_major'+ordreAffichage[i]).style.display='block';
				$('trigger_minor'+ordreAffichage[i]).style.display='block';
				break;
			}
		}
	}
}

/**
 * Initie l'appel Ajax du script PHP de définition des valeurs d'une na
 *
 * @param string _na nom de la na dont on recherche les valeurs
 * @param string _cible identifiant du champ qui va contenir les valeurs de na
 * @param string _status_btn identifiant du bouton indiquant que des valeurs de na sont sélectionnées pour l'alarme
 * @param string _family nom de la famille de l'alarme
 * @return void
 */

function getNEList(_na, _cible, _status_btn, _family) {

	new Ajax.Request('alarm_actions_manager.php',
	{
		method:'get',
		parameters: {action: 'get_ne', na: _na, family: _family},
		onSuccess: function(data){
						if(data.responseText == "failed"){
							alert('An error occurs');
						}
						else
						{
							//alert('resp= '+data.responseText);
							
							$(_cible).value = data.responseText;
							$(_status_btn).className = "bt_on";

							var elements = $$('div[class="accordion_title"]');

							elements.each(function(item) {
								if (item.id == (_na+"_title")){
									Element.show(item);
								}
								else
								{
									Element.hide(item);
								}
							});
						}	
					}
	    ,
		onFailure: function(){ alert('An error occurs'); }
	});
}

/**
 * Reaffecte le champ contenant la liste des ne au formulaire HTML qui sera posté
 *
 * @param string ne_list_id identifiant du champ contenant la liste des ne
 * @param string new_parent_id identifiant du formulaire auquel on réattribue la liste
 * @return void
 */

function initNEList(ne_list_id, new_parent_id){

	// On change le parent du champ 'ne_list' afin de l'inclure dans le formulaire

	var ne_list_tmp = $(ne_list_id);
		
	$(ne_list_id).parentNode.removeChild(ne_list_tmp);
	$(new_parent_id).appendChild(ne_list_tmp);

	// On attribue les valeurs de ne sélectionnées au champ 'ne_list'

	//$(ne_list_id).value = list_values;
}

function showHideNE(tab_ne_div){
	
	for (var i=0;i<tab_ne_div.length;i++)
	{
		Element.toggle(tab_ne_div[i]+"_title");
	}

	if ($('net_to_sel').options[$('net_to_sel').selectedIndex].value != "makeSelection")
	{
		Element.show($('net_to_sel').options[$('net_to_sel').selectedIndex].value+"_title");
	}
}

/**
 * Vérifie lors du postage du formulaire que tous les champs obligatoires de définition de l'alarme ont été correctement remplis
 *
 * @return void
 */

function checkAlarmFields(){

	var check_ok = true;
	var errors_list = Array();

	$('errors_summary').innerHTML = "";

	// Vérification de la saisie du nom de l'alarme

	if ($('alarm_name').value == "")
	{
		check_ok = false;
		errors_list.push("'Alarm Name' field is empty");
	}
	else	// Test des caractères spéciaux
	{
		var expr_special_chars = new RegExp("[^a-zA-Z0-9+-/_* ]","gi");
		var name_no_special_chars = ($('alarm_name').value).replace(expr_special_chars, '');

		if ($('alarm_name').value != name_no_special_chars)
		{
			check_ok = false;
			errors_list.push("'Alarm Name' contains special characters");
		}
	}

	// Vérification de la saisie d'une na et d'au moins une valeur de ne

	if (($('net_to_sel').options[$('net_to_sel').selectedIndex].value == "makeSelection") || ($('ne_list').value == ""))
	{
		check_ok = false;
		errors_list.push("No network element was selected");	
	}

	// Vérification de la saisie d'une ta

	if ($('time_to_sel').options[$('time_to_sel').selectedIndex].value == "makeSelection")
	{
		check_ok = false;
		errors_list.push("'Time resolution' field is empty");	
	}

	// Vérification de la saisie d'un trigger

	var criticity = Array('critical', 'major', 'minor');

	var trigger_list = $$('div[id^="trigger_list_"]');
    
	trigger_list.each(function(item) {
		
		trigger_id = (item.id).replace(/trigger_list_/,"");

		// Vérification du type du trigger

		trigger_type = $('trigger_type'+trigger_id);

		type_ok = true;

		if (trigger_type.nodeName != "INPUT")
		{
			if (trigger_type.options[trigger_type.selectedIndex].value == "makeSelection")
			{
				check_ok = false;
				errors_list.push("No trigger was selected");
				type_ok = false;
			}
		}
		else // Pour le cas d'un type unique, on ne fait pas de vérification mais l'on change la valeur affichée par la valeur réelle du type
		{
			trigger_type.value = trigger_type.real_value;
		}

		// Vérification de la selection d'une valeur de trigger

		trigger_field = $('trigger_field'+trigger_id);

		if ((type_ok == true) && (trigger_field.options[trigger_field.selectedIndex].value == "makeSelection"))
		{
			check_ok = false;
			errors_list.push("No trigger was selected");	
		}

		// Vérification de la saisie des operandes et des valeurs pour les triggers sélectionnés et les niveaux de criticité existants

		for (var i=0;i<criticity.length;i++)
		{
			// Vérification de l'opérande du trigger
			
			trigger_operand = $('trigger_operand_'+criticity[i]+trigger_id);
			
			if (trigger_operand != null)
			{
				if (trigger_operand.options[trigger_operand.selectedIndex].value == "none")
				{
					check_ok = false;
					errors_list.push("No operand configured for the trigger");
				}
			}

			// Vérification de la valeur du trigger
			
			trigger_value = $('trigger_value_'+criticity[i]+trigger_id);
			
			if (trigger_value != null)
			{
				if (trigger_value.value == "")
				{
					check_ok = false;
					errors_list.push("No value configured for the trigger");
				}
				else if (isNaN(trigger_value.value))
				{
					check_ok = false;
					errors_list.push("The trigger value must be numeric");				
				}
			}
		}
	});

	if (!check_ok)	// S'il existe des erreurs, on les affiche dans une fenêtre
	{
		// Création du contenu de la fenêtre

		var errors_html = new Element('div', {'id': "errors_report", 'align': "left", 'class': "errors_info", 'style': "display:block"});
		
		var errors_content = "&nbsp;<span style=\"font-weight:bold;text-decoration:underline;\">Some errors occurs :</span><ul style=\"margin-top:10px\">";
		
		for (var i=0;i<errors_list.length;i++)
		{
			errors_content += "<li>"+errors_list[i]+"</li>";
		}

		errors_content += "<ul></div>";

		errors_html.innerHTML = errors_content;

		$('errors_summary').appendChild(errors_html);
		$('errors_summary').style.display = "block";
	}
	else // Sinon, on poste le formulaire
	{
		postSetupForm();//$('alarmSetupForm').submit();
	}
}

/**
 * Initie l'appel Ajax du script de postage du formulaire
 *
 * @return void
 */

function postSetupForm(){
	
	// document.location = 'alarm_actions_manager.php?action=save_alarm&'+Form.serialize('alarmSetupForm');
	// return;
	
	new Ajax.Request('alarm_actions_manager.php',
	{
		method:'get',
		parameters: "action=save_alarm&"+Form.serialize('alarmSetupForm'),
		onSuccess: function(data){
						if(data.responseText == "failed"){
							alert('An error occurs');
						}
						else
						{
							//window.location.replace('example.php');
							window.history.back();
						}	
					},
		onFailure: function(){ alert('An error occurs'); }
	});

}

/**
 * Coche ou décoche en fonction de règles préetablies des valeurs de ne dans la liste des valeurs disponibles
 *
 * @param string checked_box identifiant de la case à cochée représentant une ne sélectionnée
 * @return void
 */

function setBoxChecked(checked_box){

	if ((checked_box.value).match("^all_")) // valeur "All" sélectionnée
	{
		if (checked_box.checked)
		{
			var elements = $$('input[type="checkbox"]');

			elements.each(function(item) {       
				if((item.parent_id == checked_box.parent_id) && (item.id != checked_box.id)){
					item.checked = false;
					//saveInNeSelection(item.id);
				}
			});
		}
		resetNeSelection();
		checked_box.checked = true;
	}
	else // valeurs différentes de "All"
	{
		var elements = $$('input[type="checkbox"][id^="all_"]');

		elements.each(function(item) {
			if(item.parent_id == checked_box.parent_id && (item.checked == true)){
				item.checked = false;
				saveInNeSelection(item.id);
			}
		});
	}
	saveInNeSelection(checked_box.id);
}