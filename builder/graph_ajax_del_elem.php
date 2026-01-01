<?php
/**
*	@cb4100@
*	- Creation SLC	 29/10/2008
*
*	Supprime un element du GTM
*
*	30/01/2009 GHX
*		- modification des requetes SQL pour mettre des valeurs entre cote [REFONTE CONTEXTE]
*/


$intranet_top_no_echo = true;
include_once('common.inc.php');


// on recupère les données
// 10:16 30/01/2009 GHX
// Suppression du formatage en INT
$id	= $_POST['id'];


// on va chercher le GTM pour vérifier qu'on a les droits d'écriture dessus
$query = " --- on va chercher le GTM
	SELECT * FROM sys_pauto_page_name WHERE id_page IN 
	 ( SELECT id_page FROM sys_pauto_config WHERE id= '$id' )
";
$gtm = $db->getrow($query);
if (!allow_write($gtm)) {
	echo __T('G_GDR_BUILDER_ERROR_YOU_DONT_HAVE_THE_RIGHT_TO_CHANGE_THAT_GTM');
	exit;
}



// supprime la ligne dans graph_data
$query = " --- delete element in graph_data
	delete from graph_data where id_data= '$id'";
$db->execute($query);

// supprime la ligne dans sys_pauto_config
$query = " --- delete element in sys_pauto_config
	delete from sys_pauto_config where id= '$id'";
$db->execute($query);


echo 'ok';
exit;

?>
