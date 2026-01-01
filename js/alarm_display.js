/*
	Fonctions javascript utilisées dans l'affichage des alarmes.;
*/

function getHTTPObject() {
	var xmlhttp;
	
	/*@cc_on
	@if (@_jscript_version >= 5)
	try {
	  xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
	  } catch (e) {
	  try {
		xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		} catch (E) {
		xmlhttp = false;2
		}
	  }
	@else
	xmlhttp = false;
	@end @*/
		
	if (!xmlhttp && typeof XMLHttpRequest != 'undefined') {
		try {
			xmlhttp = new XMLHttpRequest();
		} catch (e) {
			xmlhttp = false;
		}
	}
		
	return xmlhttp;
}

var http = getHTTPObject();

/*
	Permet d'acquitter une alarme.
	
	tab : tableau dans lequel se trouve l'élément.
	obj : ligne concernée (cette ligne peut avoir des enfants).
*/
// validateAlarm(4,'tableau_critical','TAG10_53570_TAG10_312_20080729030917_2008061515', 0,'NA','hour','2008061515','sai','TAG10_53570_TAG10_312',0,'','2008-07-29 03:09:17' )
function validateAlarm(product,tab,obj,oid,mode,ta,ta_value,na,na_value,id_alarm,alarm_type,calculation_time){
	if (confirm('Alarm acknowledgement ?')) {

		var url = validateAlarmWithoutConfirmation(product,tab,obj,oid,mode,ta,ta_value,na,na_value,id_alarm,alarm_type,calculation_time);
		// Envoi de la requête xmlhttp.
		http.open("GET", url, true);
		http.onreadystatechange = validateAlarm_reponse;	
		http.send(null);
		
	}
}

/**
 * Validate alarm without displaying confirmation prompt
 * @param product
 * @param tab
 * @param obj
 * @param oid
 * @param mode
 * @param ta
 * @param ta_value
 * @param na
 * @param na_value
 * @param id_alarm
 * @param alarm_type
 * @param calculation_time
 * @returns {string}
 */
function validateAlarmWithoutConfirmation(product,tab,obj,oid,mode,ta,ta_value,na,na_value,id_alarm,alarm_type,calculation_time) {
	var url = 'alarm_acknowledgment_ajax.php?';
	url += 'product='+product+'&';
	url += 'oid='+oid+'&';
	url += 'mode='+mode+'&';
	url += 'ta='+ta+'&';
	url += 'ta_value='+ta_value+'&';
	url += 'na='+na+'&';
	url += 'na_value='+na_value+'&';
	url += 'id_alarm='+id_alarm+'&';
	url += 'calculation_time='+calculation_time+'&';
	url += 'alarm_type='+alarm_type;

	var str = obj;
	var tableau = document.getElementById(tab);

	// $('myurl').innerHTML = url;
	// alert(url);

	if(oid == 0){
		/*
		 On supprime la ligne parente ainsi que toutes les lignes
		 enfants, c'est-à-dire celles qui sont dans la balise TBODY.
		 */
		tableau.deleteRow(document.getElementById(obj).rowIndex);
		var child_id = obj+'_child';
		var noeud = document.getElementById(child_id);
		tableau.removeChild(noeud);
	} else {
		// On supprime seulement la ligne.
		var id_parent = document.getElementById(obj).parentNode.id;
		var first_child = document.getElementById(id_parent).firstChild;
		var last_child = document.getElementById(id_parent).lastChild;

		tableau.deleteRow(document.getElementById(obj).rowIndex);
		if(first_child == last_child){
			var id_to_delete = id_parent.replace(/_child/,"");
			tableau.deleteRow(document.getElementById(id_to_delete).rowIndex);
		}
	}
	return url;
}

function validateAlarm_reponse(){
	if (http.readyState == 4) {
		//alert('OK - requête exécuté');
	}
}

/**
 * Get alarm oids from dom
 */
function getAlarmOids() {
	var items = [];
	$('top').getElementsBySelector( '#critical tr', '#major tr', '#minor tr').each(
		function(item) {
			var id = item.identify();
			var index = id.indexOf("child");
			if (index != -1) {
				items.push(id.substr(index+6));
			}
		}
	);
	console.log("Found " + items.length + " alarm(s) to acknowledge");
	return items;
}

/**
 * Get alarm oids from dom
 */
function getAlarmTrs() {
	var items = [];
	$('top').getElementsBySelector( '#tableau_critical tr', '#tableau_major tr', '#tableau_minor tr').each(
		function(item) {
			var id = item.identify();
			if (id != "" && id.indexOf('anonymous') == -1) {
				items.push(item);
			}
		}
	);
	console.log("Found " + items.length + " alarm(s) tr nodes");
	return items;
}

/**
 * Validate all alarms from the current page
 */
function validateAllAlarms() {
	if (confirm('Do you want to acknowledge all alarms displayed on this page?')) {
		var oids = getAlarmOids();
		if (oids.length > 0) {
			new Ajax.Request(
				'alarm_acknowledgment_by_oids_ajax.php',
				{
					method: 'get',
					parameters: {"oids[]": oids, "product": $$('input[name="product"]')[0].value},
					onSuccess: function () {
						console.info('All alarms are successfully validated.');
						var trs = getAlarmTrs();
						for (var i = 0; i < trs.length; i++) {
							trs[i].remove();
						}
					},
					onFailure: function () {
						console.error('Error while validate all alarms.')
					}
				}
			);
		}
	}

}

/*
	Change l'image plus / moins
*/
function change_img(obj){
	src = document.getElementById(obj).src;
	if(src.indexOf('plus_alarme.gif') != -1){
		src_final = src.replace(/plus_alarme.gif/, 'moins_alarme.gif');
	} else {
		src_final = src.replace(/moins_alarme.gif/, 'plus_alarme.gif');
	}
	document.getElementById(obj).src = src_final;
}




var _oldTrColor = "";

/*
	Permet de surligner la ligne d'un tableau au passage de la souris.
*/
function surligner(obj){
	elem = document.getElementById(obj);
	if(elem.marquer == 'false'){
		if(elem.style.backgroundColor != "#ffdab9"){
			_oldTrColor = elem.style.backgroundColor;
		}
		if(elem.style.backgroundColor == "#ffdab9"){
			elem.style.backgroundColor = _oldTrColor;
		} else {
			elem.style.backgroundColor = '#ffdab9';
		}
	}
}


/*
	Permet de marquer une ligne.
*/
function marquer(id){
	elem = document.getElementById(id);
	elem.style.backgroundColor = "#dad8d8";
	elem.marquer = 'true';
}









