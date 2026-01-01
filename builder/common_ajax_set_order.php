<?php
/**
*	@cb4100@
*	- Creation SLC	 29/10/2008
*
*	Cette page enregistre l'ordre des éléments du GTM ou du Dashboard
*
*	30/01/2009 GHX
*		- modification des requêtes SQL pour mettre id_page & id entre cote au niveau des inserts  [REFONTE CONTEXTE]
*
*/

$intranet_top_no_echo = true;
include_once('common.inc.php');

$trace = '';	// pour le debug

// on recupère les données
// 09:56 30/01/2009 GHX
// Suppression du formatage en INT
$id_page	= $_POST['id_page'];
$ordered	= $_POST['ordered'];
$type	= $_POST['type'];

// on va chercher le GTM / Dashboard pour vérifier qu'on a les droits d'écriture dessus
$query = " --- on va chercher le GTM / Dashboard / Report
	SELECT * FROM sys_pauto_page_name WHERE id_page= '$id_page'
";
$obj = $db->getrow($query);
if (!allow_write($obj)) {
	echo __T('G_GDR_BUILDER_ERROR_YOU_DONT_HAVE_THE_RIGHT_TO_CHANGE_THAT_OBJECT');
	exit;
}

// on inscrit le changement d'ordre dans la base
if ($ordered) {
	$morceaux = explode('&',$ordered);
	for ($i=0; $i<sizeof($morceaux); $i++) {
		list($devnul,$id) = explode('=',$morceaux[$i],2);
		if (($type == 'GTM') or ($type == 'report')) {
			$query = "
				--- set data order in GTM
				update sys_pauto_config set ligne=$i where id_page='$id_page' and id='$id'";
		} else {
			$query = "
				--- set data order in Dashboard
				update sys_pauto_config set ligne=$i where id_page='$id_page' and id_elem='$id'";
		}
		$trace .= "\n".$query;
		$db->execute($query);
	}
}

echo 'ok';
exit;

// debug
$fh = fopen('trace.txt','w');
fwrite($fh,$trace);
fclose($fh);
?>
