<?php
/**
*	@cb4100@
*	- Creation SLC	 29/10/2008
*
*	Cette page share / unshare le graph / dashboard / report spécifié
*
*	30/01/2009 GHX
*		- modification des requêtes SQL pour mettre id_page entre cote au niveau des inserts  [REFONTE CONTEXTE]
*/

$intranet_top_no_echo = true;
include_once('common.inc.php');

// on recupère les données envoyées au script
$type		= $_POST['type'];	//	= 'graph' or 'dashboard' or 'report'
// 09:44 30/01/2009 GHX
// Suppression du formatage en INT
$id_page		= $_POST['id_page'];

// on va chercher le GTM / Dash pour vérifier qu'on a les droits d'écriture dessus
$query = " --- on va chercher le GTM / Dash $id_page
	SELECT * FROM sys_pauto_page_name WHERE id_page='$id_page'
";
$obj = $db->getrow($query);
if (!allow_write($obj)) {
	echo __T('G_GDR_BUILDER_ERROR_YOU_DONT_HAVE_THE_RIGHT_TO_SHARE_UNSHARE_THAT_OBJECT');
	exit;
}

if ($_POST['unshare'] == 1) 	$share = 0;
else 						$share = 1;

// on ecrit dans la base
$query = " --- we write the sharing in the database
	update sys_pauto_page_name set share_it=$share where id_page='$id_page'
	";
$db->execute($query);

// on renvoie vers le GTM ou dashboard builder
header("Location: {$niveau0}builder/$type.php?id_page=$id_page");
exit;


// debug
echo "<link rel='stylesheet' href='gtm.css' type='text/css'/>";
echo $db->displayQueries();
echo "<br/><br/><a href='{$niveau0}builder/$type.php'>{$niveau0}builder/$type.php</a>";
?>
