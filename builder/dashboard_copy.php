<?php
/**
*	@cb4100@
*	- Creation SLC	 05/11/2008
*
*	Cette page effectue une copie du dashboard spécifié
*
*	30/01/09 GHX
*		- modification du nouveau format pour id_page de la table sys_pauto_page_name [REFONTE CONTEXTE]
*	02/02/2009 GHX
*		- Appel à la fonction qui génére un unique ID generateUniqId() [REFONTE CONTEXTE]
*		- Suppression de la colonne internal_id [REFONTE CONTEXTE]
*	24/06/2009 : MPR 
*		- Correction du bug 9796 : Ajout d'un id unique
*/

$intranet_top_no_echo = true;
include_once('common.inc.php');

// on recupère les données envoyées au script
// 09:38 30/01/2009 GHX
// Suprression du formatage id_page en INT
$id_page		= $_POST['id_page'];

// on va chercher le prochain id_page
// 09:39 30/01/2009 GHX
// Nouveau format de l'identifiant ce n'est plus un MAX+1
// 14:23 02/02/2009 GHX
// Appel à la fonction qui génére un unique ID
$next_id_page = generateUniqId('sys_pauto_page_name');

// maj 24/06/2009 : MPR - Correction du bug 9796 : Ajout d'un id unique
$next_id_menu = generateUniqId('menu_deroulant_intranet');

// on va chercher le Dashboard actuel
$query = " --- recherche du Dashboard $id_page
	select * from sys_pauto_page_name where id_page= '$id_page'";
$dash = $db->getrow($query);

// on insert le nouveau (la copie)

// on compose le nouveau nom
$i = 1;
$page_name = "copy of {$dash['page_name']}";
while ($db->getone("select id_page from sys_pauto_page_name where page_name='".str_replace("'","\'",$page_name)."'")) {
	$i++;
	$page_name = "copy $i of {$dash['page_name']}";
}

// on calcule le id_user
if (($client_type == 'client') && ($user_info['profile_type'] == 'user')) {
	$query = " --- copy Dashboard in  sppn
		insert into sys_pauto_page_name
			(id_page,page_name,droit,page_type,id_user,share_it)
		values ('$next_id_page','".str_replace("'","\'",$page_name)."','$client_type','page','{$user_info['id_user']}',0)";
} else {
	$query = " --- copy Dashboard in  sppn
		insert into sys_pauto_page_name
			(id_page,page_name,droit,page_type,share_it)
		values ('$next_id_page','".str_replace("'","\'",$page_name)."','$client_type','page',0)";
}
$db->execute($query);

// maintenant il faut copier les infos dans sys_definition_dashboard
$query = " --- we get the info for Dashboard $id_page
	select * from sys_definition_dashboard where sdd_id_page= '$id_page'
";
$dash_info = $db->getrow($query);
$dash_info['sdd_id_page'] = $next_id_page;
$dash_info['sdd_id_menu'] = $next_id_menu;
// 
$dash_info['sdd_is_online'] = 0;
$db->AutoExecute('sys_definition_dashboard',$dash_info,'INSERT');

// maintenant on va copier tous les graphs du dashboard (qui sont dans sys_pauto_config)
$query = " --- we fetch the graphs that are in the Dashboard $id_page
	select * from sys_pauto_config where id_page= '$id_page'";
$graphs = $db->getall($query);
if ($graphs) {
	foreach ($graphs as $graph) {
		
		// on change id et id_page du raw/kpi
		// maj 24/06/2009 : MPR - Correction du bug 9796 : Ajout d'un id unique
		$graph['id']			= generateUniqId('sys_pauto_config');
		$graph['id_page']		= $next_id_page;
		// on supprime l'id_product
		if (!$graph['id_product']) unset($graph['id_product']);
		// on insert la courbe de la copie dans sys_pauto_config
		$db->AutoExecute('sys_pauto_config',$graph,'INSERT');
	}
}

// on renvoie vers le dashboard builder
header("Location: {$niveau0}builder/dashboard.php?id_page=$next_id_page");
exit;

// debug
echo "<link rel='stylesheet' href='gtm.css' type='text/css'/>";
echo $db->displayQueries();
echo "<br/><br/><a href='{$niveau0}builder/dashboard.php?id_page=$next_id_page'>{$niveau0}builder/dashboard.php?id_page=$next_id_page</a>";

?>
