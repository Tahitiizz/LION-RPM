<?php
/**
*	@cb5001@
*	- Creation NSE	 12/10/2009 d'après dashboard_ajax_get_nalevels_in_common.php
*
*	Cette page renvoie la liste des NA levels en commun aux différents produits pour les familles sélectionnées
*
*/
session_start();
include_once dirname(__FILE__).'/../../php/environnement_liens.php';

$families = explode(';', $_GET['family']);
// Suppression de la dernière valeur qui est vide
array_pop($families);

$selectedFamily = array();
// Boucle sur toutes les familles sélectionnées
foreach ( $families as $family )
{
	$_ = explode('-', $family);
	$selectedFamily[$_[0]][] = $_[1];
}

$na_levels_in_common = FamilyModel::getCommonNaBetweenFamilyAndProducts($selectedFamily);

// si on a rien :
if ( !$na_levels_in_common)
{
	echo '<li style="border:0;background:red;color:#FFF;font-weight:bold;">'.__T('G_GDR_BUILDER_WARNING_NO_NA_LEVEL_IN_COMMON').'</li>';
}
else
{
	// on renvoie la liste
	foreach ($na_levels_in_common as $key => $level)
	{
		echo "<li>{$level}</li>";
	}
}
?>