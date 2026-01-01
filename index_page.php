<?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
* 
* 	- 08/01/2008 Gwénaël : changement du nom de l'iframe
* 	- 28/12/2007 Gwénaël : restructuration du fichier dans l'optique d'une meilleure lisibilité et maintenance
* 	
*/
?>
<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*/
?>
<?
/*
*	@cb21002@
*
*	23/02/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.0.02
*/
?>
<?
/*
*	@cb2000b_iu2000b@
*
*	20/07/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.0.0
*
*	Parser version iu_2.0.0.0
*
* 01-09-2006 : HTTP request lors du unload qui correspond au logout ou a la fermeture de la fenetre
*/
?>
<?
/*
 * maj 26/02/2007 gwénaël : ajout du numéro de version dans le titre de la page
 */
?>
<?php
session_start();
include dirname( __FILE__ ).'/php/environnement_liens.php';

$product_name = get_sys_global_parameters('product_name');
$file_a_charger = $_SESSION['file_a_charger'];

// Titre de la page HTML
$product_name    = get_sys_global_parameters('product_name');
$product_version = reduce_num_version(get_sys_global_parameters('product_version'));
$title = $product_name.' '.$product_version; 
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="content-type" content="text/html;charset=iso-8859-1">
<meta http-equiv="Content-Script-Type" content="text/javascript">
<meta http-equiv="pragma" content="no-cache">
<meta http-equiv="cache-control" content="no-cache">
<title><?php echo $title; ?></title>
<script>
// Objet XMLHttprequest.
function getHTTPObject() {
	var xmlhttp;

	/*@cc_on
	@if (@_jscript_version >= 5)
	try {
		xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
	} 
	catch (e) {
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
		}
		catch (e) {
			xmlhttp = false;
		}
	}

	return xmlhttp;
}

var http = getHTTPObject();

// fonction à lancer
function executePage(url){
	http.open('GET', url, true);
	http.onreadystatechange = xmlHttpRequestDisplayReponse;  //inutile car lorsque cette action est realisé la page index_page.php est fermée
	http.send(null);
}

// Affiche la réponse renvoyée l'objet http courant : http
function xmlHttpRequestDisplayReponse(){
	// si tout c'est bien passé reayState est égal à 4
	if (http.readyState == 4){
		// http.responseText contient tous les 'echo' du fichier php exécuté via http.open (plus haut)
	}
}
</script>
</head>
<body style="margin:0;overflow:hidden;" onUnload="javascript: executePage('logout.php?session_uniq_id=<?=$_SESSION["session_uniq_id"]?>&id_user=<?=$_SESSION["id_user"]?>&start_session=<?=$_SESSION["start_user_session"]?>');">
<iframe id="general_iframe" name="general_iframe" src="<?php echo NIVEAU_0.$file_a_charger; ?>" frameborder="0" style="position:absolute;width:100%;height:100%;"></iframe>
</body>
</html>
