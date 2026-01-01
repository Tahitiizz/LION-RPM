<?php
/**
*	@cb4100@
*	- Creation SLC	 29/10/2008
*
*	Cette page effectue une copie du graph spécifié
*
*	30/01/2009 GHX
*		- modification des requetes SQL pour mettre des valeurs entre cote [REFONTE CONTEXTE]
*		- nouveau format l'id_page [REFONTE CONTEXTE]
*	02/02/2009 GHX
*		- Appel à la fonction qui génére un unique ID generateUniqId() [REFONTE CONTEXTE]
*		- Suppression de la colonne internal_id [REFONTE CONTEXTE]
*	12/08/2009 GHX
*		- Correction d'un bug quand on a des cotes dans les champs definition et troubleshooting
*/

$intranet_top_no_echo = true;
include_once('common.inc.php');

// on recupère les données envoyées au script
// 10:20 30/01/2009 GHX
// Suppression du formatage en INT
$id_page		= $_POST['id_page'];

// on va chercher le prochain id_page
// 10:22 30/01/2009 GHX
// Nouveau formatage pour l'ID, ce n'est plus un MAX+1
// 14:23 02/02/2009 GHX
// Appel à la fonction qui génére un unique ID
$next_id_page = generateUniqId('sys_pauto_page_name');

// on va chercher le graph actuel
$query = " --- recherche du GTM $id_page
	select * from sys_pauto_page_name where id_page='$id_page'";
$graph = $db->getrow($query);

// on calcule le id_user
if (($client_type == 'client') && ($user_info['profile_type'] == 'user')) {
	$temp_id_user = "'".$user_info['id_user']."'";
} else {
	$temp_id_user = 'NULL';
}

// on insert le nouveau (la copie)

// on compose le nouveau nom
$i = 1;
$page_name = "copy of {$graph['page_name']}";
while ($db->getone("select id_page from sys_pauto_page_name where page_name='$page_name'")) {
	$i++;
	$page_name = "copy $i of {$graph['page_name']}";
}

$query = " --- copy GTM in  sppn
	insert into sys_pauto_page_name
		(id_page,page_name,droit,page_type,id_user,share_it)
	values ('$next_id_page','$page_name','$client_type','gtm',$temp_id_user,0)";
$db->execute($query);

// maintenant il faut copier les infos dans graph_information
$query = " --- we get the info for GTM $id_page
	select * from graph_information where id_page='$id_page'
";
$grinfo = $db->getrow($query);
$grinfo['id_page'] = $next_id_page;
// on fera l'insert après


// maintenant on va copier toutes les courbes du graph (qui sont dans sys_pauto_config)
$query = " --- we fetch the data that are in graph $id_page
	select * from sys_pauto_config where id_page='$id_page'";
$data = $db->getall($query);
if ($data) {
	foreach ($data as $d) {
		// on archive l'id du raw/kpi
		$old_id = $d['id'];
		// on va chercher le next id dans sys_pauto_config
		// 14:53 30/01/2009 GHX
		// Nouveau format de l'ID, ce n'est plus géré par un serial
		// 14:23 02/02/2009 GHX
		// Appel à la fonction qui génére un unique ID
		$next_id = generateUniqId('sys_pauto_config');
		// on change id et id_page du raw/kpi
		$d['id']		= $next_id;
		$d["id_page"]	= $next_id_page;
		// on insert la courbe de la copie dans sys_pauto_config
		$db->AutoExecute('sys_pauto_config',$d,'INSERT');
		// pour cette courbe, on va chercher les infos dans graph_data
		$query = " --- fetch data $old_id
			select * from graph_data where id_data='$old_id'";
		$graph_data = $db->getrow($query);
		// on change le id_data en mettant le next_id
		$graph_data['id_data']	= $next_id;
		// on copie les infos de la courbe dans graph_data
		$db->AutoExecute('graph_data',$graph_data,'INSERT');
		
		// on regarde si cette courbe servait dans le order by du pie
		if ($grinfo["pie_split_by"] == $old_id) $grinfo["pie_split_by"] = $next_id;
		// on regarde si cette courbe servait dans le GIS
		if ($grinfo["gis_based_on"] == $old_id) $grinfo["gis_based_on"] = $next_id;
		// on copie la valeur default_orderby si besoin
		if ($grinfo["default_orderby"] == $old_id) $grinfo["default_orderby"] = $next_id;
		// on copie la valeur default_asc_desc si besoin
		if ($grinfo["default_asc_desc"] == $old_id) $grinfo["default_asc_desc"] = $next_id;
	}
}


// on peut ajouter les infos du graph (maintenant qu'on a fait les modifs de liaison "pie_split_by" et "gis_based_on" en passant sur toutes les courbes
if (!$grinfo["pie_split_by"])		unset($grinfo["pie_split_by"]);
if (!$grinfo["gis_based_on"])		unset($grinfo["gis_based_on"]);
if (!$grinfo["default_orderby"])		unset($grinfo["default_orderby"]);
if (!$grinfo["default_asc_desc"])	unset($grinfo["default_asc_desc"]);

// 17:53 12/08/2009 GHX
// Correction d'un bug si on a des cotes dans les champs suivants
$grinfo['troubleshooting'] = str_replace("'", "\'", $grinfo['troubleshooting']);
$grinfo['definition'] = str_replace("'", "\'", $grinfo['definition']);

$db->AutoExecute('graph_information',$grinfo,'INSERT');

// on renvoie vers le graph builder
header("Location: {$niveau0}builder/graph.php?id_page=$next_id_page");
exit;

// debug
echo "<link rel='stylesheet' href='common.css' type='text/css'/>";
echo $db->displayQueries();
echo "<br/><br/><a href='{$niveau0}builder/graph.php?id_page=$next_id_page'>{$niveau0}builder/graph.php?id_page=$next_id_page</a>";

?>
