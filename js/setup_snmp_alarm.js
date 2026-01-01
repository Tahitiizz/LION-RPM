/*
	création christophe le 06 02 2007
	Permet d'ajouter / supprimer une alarme de
	la trape SNMP.
	- 27 02 2007 christophe ; correction bug FS 512, inversement des tooltiptext
*/
// Objet XMLHttprequest.
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

var http_snmp = getHTTPObject();
var current_image = '';
var current_td = '';

/*
	Appel le fichier setup_snmp_alarm.php
	Params :
	- product : identifiant du produit
	- alarm_id : identifiant de l'alarme.
	- alarm_type : type de l'alarme.
	- action : add ou delete.
	- image : id de l'icône cliquée.
	- id_td : id de la balise td sur laquelle se trouve l'appel à la fonction sur l'évènement onclick.
*/
function updateSNMPTrap(product,alarm_id, alarm_type, action, image){
	var message_confirm = '';
	current_image = image;
	
	/*
		Si l'utilisateur vient d'activer une alarme et qu'il reclique dessus l'alarme ne doit pas être ajoutée 2 fois.
	*/
	if ( document.getElementById(current_image).src.indexOf('send_vert_snmp.jpg') != -1 && action == 'add')
		action = 'delete';
	else if ( document.getElementById(current_image).src.indexOf('send_rouge_snmp.jpg') != -1 && action == 'delete')
		action = 'add';
	
	if ( action == 'delete' )
		message_confirm = "Remove this alarm from a SNMP trap ?";
	else
		message_confirm = "Send this alarm in a SNMP trap ?";
	
	if ( confirm(message_confirm) )
	{
		url = "setup_snmp_alarm.php";
			url += "?product="+product;
			url += "&alarm_id="+alarm_id;
			url += "&alarm_type="+alarm_type;
			url += "&action="+action;
		http_snmp.open("GET", url, true);
		http_snmp.onreadystatechange = updateSNMPTrap_message;	
		http_snmp.send(null);
	}
}
function updateSNMPTrap_message(){
	if (http_snmp.readyState == 4){
		//alert(http_snmp.responseText);
		//change_send(current_image,current_td);
		window.location.reload(); 
	}
}

/*
	Permet de changer l'icône send et le message alt.
	id_img = id de l'image.
	id_td = id de la balise td.
*/
function change_send(id_img, id_td){
	src = document.getElementById(id_img).src;
	
	if(src.indexOf('send_rouge_snmp.jpg') != -1){
		src_final = src.replace(/send_rouge_snmp.jpg/, 'send_vert_snmp.jpg');
		alt_text = "Deactivate SNMP Trap";
	} else {
		src_final = src.replace(/send_vert_snmp.jpg/, 'send_rouge_snmp.jpg');
		alt_text = "Activate SNMP Trap";
	}
	document.getElementById(id_img).src = src_final;
	document.getElementById(id_img).alt = alt_text;
}

