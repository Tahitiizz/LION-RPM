<?php
/**
*	@cb4100@
*	- Creation SLC	 29/10/2008
*
*	Cette page supprime le graph
*
*	30/01/2009 GHX
*		- modification des requetes SQL pour mettre des valeurs entre cote [REFONTE CONTEXTE]
*/

$intranet_top_no_echo = true;
include_once('common.inc.php');

// vérif que le graph est supprimable (ie: il n'est pas utilisé par un dashboard)
$query = " --- check if that graph belongs to a dashboard
	select id_page from sys_pauto_config where id_elem='$id_page' and class_object='graph'
";
$dashboards = $db->getall($query);
if ($dashboards) {
	echo __T('G_GDR_BUILDER_YOU_CANNOT_DELETE_THAT_GTM_AS_IT_BELONGS_TO_SOME_DASHBOARDS');
	exit;
}


// on recupère les données envoyées au script
// 10:20 30/01/2009 GHX
// Suppression du formatage en INT
$id_page		= $_POST['id_page'];

// on va chercher le graph pour vérifier qu'on a les droits d'écriture dessus
$query = " --- on va chercher le graph
	SELECT * FROM sys_pauto_page_name WHERE id_page='$id_page'
";
$graph = $db->getrow($query);
if (!allow_write($graph)) {
	echo __T('G_GDR_BUILDER_ERROR_YOU_DONT_HAVE_THE_RIGHT_TO_DELETE_THAT_GTM');
	exit;
}

// on supprime les data des courbes du graph
$query = " --- we delete the data in graph_data
	delete from graph_data where id_data IN
		(select id from sys_pauto_config where id_page='$id_page')
	";
$db->execute($query);

// on supprime les courbes du graph
$query = " --- we delete the data in sys_pauto_config
	delete from sys_pauto_config where id_page='$id_page'";
$db->execute($query);

// on supprime les infos de graph_information
$query = " --- we delete the graph info
	delete from graph_information where id_page='$id_page'";
$db->execute($query);

// on supprime enfin le graph dans sys_pauto_page_name
$query = " --- we delete the graph in sppn
	delete from sys_pauto_page_name where id_page='$id_page'";
$db->execute($query);


// on renvoie vers le graph builder
header("Location: {$niveau0}builder/graph.php");
exit;


// debug
echo "<link rel='stylesheet' href='common.css' type='text/css'/>";
echo $db->displayQueries();
echo "<br/><br/><a href='{$niveau0}builder/graph.php'>{$niveau0}builder/graph.php</a>";
?>
