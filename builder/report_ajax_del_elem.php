<?php
/**
*	@cb4100@
*	- Creation SLC	 13/11/2008
*
*	Supprime un element du report
*
*	30/01/2009 GHX
*		- modification des requetes SQL pour mettre des valeurs entre cote [REFONTE CONTEXTE]
*/

$intranet_top_no_echo = true;
include_once('common.inc.php');

// on recupère les données
// 10:40 30/01/2009 GHX
// Suppression formatage en INT
$id	= $_POST['id'];

// on va chercher le rapport pour vérifier qu'on a les droits d'écriture dessus
$query = " --- on va chercher le rapport
	SELECT * FROM sys_pauto_page_name WHERE id_page IN 
	 ( SELECT id_page FROM sys_pauto_config WHERE id='$id' )
";
$report = $db->getrow($query);
if (!allow_write($report)) {
	echo __T('G_GDR_BUILDER_ERROR_YOU_DONT_HAVE_THE_RIGHT_TO_CHANGE_THAT_REPORT');
	exit;
}

// 03/07/2009 BBX : BZ 10453
// Récupération des infos de l'élément
$query = "SELECT id_elem,class_object FROM sys_pauto_config WHERE id = '$id'";
$elem = $db->getRow($query);

// Si cet élément est un dashboard, il faut supprimer sa configuration sélecteur
if($elem['class_object'] == 'page') {
	// On regarde si on a configuré un sélecteur sur ce dashboard
	$idSelecteur = SelecteurModel::getSelecteurId($report['id_page'],$elem['id_elem']);
	if($idSelecteur != 0) {
		// Suppression du sélecteur
		$selecteur = new SelecteurModel($idSelecteur);
		$selecteur->deleteSelecteur();
	}
}
// FIN BBX

// supprime la ligne dans sys_pauto_config
$query = " --- delete element in sys_pauto_config
	delete from sys_pauto_config where id='$id'";
$db->execute($query);
echo 'ok';
exit;

?>
