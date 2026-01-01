<?php
/**
*	@cb4100@
*	- Creation SLC	 05/11/2008
*
*	Cette page supprime le dashboard
*
*	30/01/2009 GHX
*		-  modification des requêtes SQL pour mettre id_page entre cote  [REFONTE CONTEXTE]
*/

$intranet_top_no_echo = true;
include_once('common.inc.php');

// on recupère les données envoyées au script
// 09:58 30/01/2009 GHX
// Suppression du formatage en INT
$id_page		= $_POST['id_page'];


// vérif que le dashboard est supprimable (ie: il n'est pas utilisé par un report)
$query = " --- check if that dashboard belongs to a report
	select id_page from sys_pauto_config where id_elem='$id_page' and class_object='report'
";
$reports = $db->getall($query);
if ($reports) {
	echo __T('G_GDR_BUILDER_YOU_CANNOT_DELETE_THAT_DASHBOARD_AS_IT_BELONGS_TO_SOME_REPORTS');
	exit;
}



// on va chercher le Dashboard pour vérifier qu'on a les droits d'écriture dessus
$query = " --- on va chercher le dashboard $id_page
	SELECT * FROM sys_pauto_page_name WHERE id_page= '$id_page'
";
$dash = $db->getrow($query);
if (!allow_write($dash)) {
	echo __T('G_GDR_BUILDER_ERROR_YOU_DONT_HAVE_THE_RIGHT_TO_DELETE_THAT_DASHBOARD');
	exit;
}

// on supprime le lien d'appartenance Graph -> Dash
$query = " --- supprime l'appartenance des graphs au dashboard $id_page
	delete from sys_pauto_config where id_page= '$id_page' ";
$db->execute($query);

// on supprime le menu du dashboard s'il existait
$myMenu_id = $db->getone("SELECT sdd_id_menu FROM sys_definition_dashboard WHERE sdd_id_page= '$id_page'");
if ($myMenu_id) {
	// on supprime le menu
	$myMenu = new MenuModel($myMenu_id);
	$myMenu->deleteMenu();
}

// on supprime le dashboard dans sdd
$query = " --- supprime les informations du dashboard $id_page
	delete from sys_definition_dashboard where sdd_id_page = '$id_page'";
$db->execute($query);

// on supprime le dashboard
$query = " --- supprime le dashboard $id_page
	delete from sys_pauto_page_name where id_page = '$id_page'";
$db->execute($query);


// on renvoie vers le dashboard builder
header("Location: {$niveau0}builder/dashboard.php");
exit;


// debug
echo "<link rel='stylesheet' href='gtm.css' type='text/css'/>";
echo $db->displayQueries();
echo "<br/><br/><a href='{$niveau0}builder/dashboard.php'>{$niveau0}builder/dashboard.php</a>";

?>
