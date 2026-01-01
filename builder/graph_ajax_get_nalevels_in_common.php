<?php
/**
*	@cb4100@
*	- Creation SLC	 29/10/2008
*
*	Cette page renvoie la liste des NA levels en commun pour tous les elements (raw/kpi) du graph
*
*	30/01/09 GHX
*		- suppression du formatage en INT de la valeur id_page récupéré par POST [REFONTE CONTEXTE]
*/

$intranet_top_no_echo = true;
include_once('common.inc.php');

// on recupère les données envoyées au script
// 10:19 30/01/2009 GHX
// Suppression du formatage en  INT
$id_page		= $_GET['id_page'];


$na_levels_in_common		= getNALabelsInCommon($id_page,'na');
$na_axe3_levels_in_common	= getNALabelsInCommon($id_page,'na_axe3');

// si on a rien :
if ((!$na_levels_in_common) && (!$na_axe3_levels_in_common)) {
	echo "<li style='border:0;background:red;color:#FFF;font-weight:bold;'>".__T('G_GDR_BUILDER_WARNING_NO_NA_LEVEL_IN_COMMON')."</li>";

} else {
	// on renvoie la liste des na premier axe en commun
	if ($na_levels_in_common)
		foreach ($na_levels_in_common as $level)
			echo "<li>$level</li>";

	// on renvoie la liste des na 3eme axe en commun
	if ($na_axe3_levels_in_common)
		foreach ($na_axe3_levels_in_common as $level)
			echo "<li class='axe3'>$level</li>";
}

?>
