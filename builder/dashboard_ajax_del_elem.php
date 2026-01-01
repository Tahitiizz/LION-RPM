<?php
/**
*	@cb4100@
*	- Creation SLC	 29/10/2008
*
*	Supprime un element du dashboard
*
*	30/01/2009 GHX
*		- modification des requêtes SQL pour mettre id & id_page entre cote au niveau des inserts  [REFONTE CONTEXTE]
*/

$intranet_top_no_echo = true;
include_once('common.inc.php');


// on recupère les données
// 09:41 30/01/2009 GHX
// Suppression du formatage en INT
$id_page	= $_POST['id_page'];
$id_elem	= $_POST['id_elem'];


// on va chercher le dashboard pour vérifier qu'on a les droits d'écriture dessus
$query = " --- on va chercher le dashboard
	SELECT * FROM sys_pauto_page_name WHERE id_page = '$id_page'";
$dash = $db->getrow($query);
if (!allow_write($dash)) {
	echo __T('G_GDR_BUILDER_ERROR_YOU_DONT_HAVE_THE_RIGHT_TO_CHANGE_THAT_DASHBOARD');
	exit;
}


// supprime la ligne dans sys_pauto_config
$query = " --- delete element in sys_pauto_config
	delete from sys_pauto_config where id_page='$id_page' and id_elem='$id_elem'";
$db->execute($query);

echo 'ok';
exit;

?>
