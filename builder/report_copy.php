<?php
/**
*	@cb4100@
*	- Creation SLC	 13/11/2008
*
*	Cette page effectue une copie du report spécifié
*
*	30/01/2009 GHX
*		- modification des requetes SQL pour mettre des valeurs entre cote [REFONTE CONTEXTE]
*		- nouveau format pour id de table sys_pauto_config et id_page de sys_pauto_page_name  [REFONTE CONTEXTE]
*	02/02/2009 GHX
*		- Appel à la fonction qui génére un unique ID generateUniqId() [REFONTE CONTEXTE]
*		- Suppression de la colonne internal_id [REFONTE CONTEXTE]
*/

$intranet_top_no_echo = true;
include_once('common.inc.php');

// on recupère les données envoyées au script
//10:41 30/01/2009 GHX
// Suppression du formatage en IT
$id_page		= $_POST['id_page'];

// on va chercher le prochain id_page
// 10:42 30/01/2009 GHX
// Nouveau format pour l'id_page, ce n'est plus un MAX+1
// 14:23 02/02/2009 GHX
// Appel à la fonction qui génére un unique ID
$next_id_page = generateUniqId('sys_pauto_page_name');

// on va chercher le rapport actuel
$query = " --- recherche du rapport $id_page
	select * from sys_pauto_page_name where id_page='$id_page'";
$report = $db->getrow($query);

// on calcule le id_user
if (($client_type == 'client') && ($user_info['profile_type'] == 'user')) {
	$temp_id_user = $user_info['id_user'];
} else {
	$temp_id_user = 'NULL';
}

// on insert le nouveau (la copie)
$query = " --- copy Report in  sppn
	insert into sys_pauto_page_name
		(id_page,page_name,droit,page_type,id_user,share_it)
	values ('$next_id_page','copy of {$report['page_name']}','$client_type','report','$temp_id_user',0)";
$db->execute($query);

// maintenant on va copier toutes les courbes du GTM (qui sont dans sys_pauto_config)
$query = " --- we fetch the data that are in report $id_page
	select * from sys_pauto_config where id_page='$id_page'";
$data = $db->getall($query);
if ($data) {
	foreach ($data as $d) {
		// on archive l'id du dash/alarm
		$old_id = $d['id'];
		// on va chercher le next id dans sys_pauto_config
		// 10:43 30/01/2009 GHX
		// Nouveau format pour l'id, ce n'est plus géré par un serial
		// 14:23 02/02/2009 GHX
		// Appel à la fonction qui génére un unique ID
		$next_id = generateUniqId('sys_pauto_config');
		// on change id et id_page du dash/alarm
		$d['id']		= $next_id;
		$d["id_page"]	= $next_id_page;
		// on insert la courbe de la copie dans sys_pauto_config
		$db->AutoExecute('sys_pauto_config',$d,'INSERT');
	}
}

// on renvoie vers le report builder
header("Location: {$niveau0}builder/report.php?id_page=$next_id_page");
exit;

// debug
echo "<link rel='stylesheet' href='common.css' type='text/css'/>";
echo $db->displayQueries();
echo "<br/><br/><a href='{$niveau0}builder/report.php?id_page=$next_id_page'>{$niveau0}builder/report.php?id_page=$next_id_page</a>";

?>
