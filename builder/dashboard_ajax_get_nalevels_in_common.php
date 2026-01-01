<?php
/**
*	@cb4100@
*	- Creation SLC	 05/11/2008
*
*	Cette page renvoie la liste des NA levels en commun pour tous les elements (graphs) du dashboard
*
*	30/01/2009 GHX
*		- modification des requêtes SQL pour mettre id_page entre cote au niveau des inserts  [REFONTE CONTEXTE]
*	15/07/2009 GHX
*		- Correction du BZ 10604 [REC][CB 5.0][DASHBOARD BUILDER] le niveau d'agrégation du sélection n'est pas enregistrer en base.
*/

$intranet_top_no_echo = true;
include_once('common.inc.php');

// on recupère les données envoyées au script
// 09:41 30/01/2009 GHX
// Suppression du formatage en INT 
$id_page		= $_GET['id_page'];

$current_selecteur_na		= $_GET['current_selecteur_na'];

// on va chercher tous les graphs du dashboard
$query = " --- on va chercher les GTM du dashbord $id_page
	select id_elem from sys_pauto_config where id_page='$id_page'
";
$graphs = $db->getall($query);
if ($graphs) {
	// on prend les na levels du premier graph
	$na_levels_in_common = getNALabelsInCommon($graphs[0]['id_elem'],'na');
	$na_axe3_levels_in_common = getNALabelsInCommon($graphs[0]['id_elem'],'na_axe3');

	if ($na_levels_in_common) {
		// on boucle sur tous les autres graphs
		for ($i = 1; $i < sizeof($graphs); $i++) {
			$na_levels_of_the_graph = getNALabelsInCommon($graphs[$i]['id_elem']);
			if ($na_levels_of_the_graph) {
				// on fait l'intersection des na levels
				$na_levels_in_common = array_intersect_assoc( $na_levels_in_common, $na_levels_of_the_graph);
				unset($na_levels_of_the_graph);
			} else {
				// l'un des graphs n'a pas de na levels en commun, donc c'est fichu
				$na_levels_in_common = false;
				$i = 1000;		// on sort du for i
			}
		}
	}

	if ($na_axe3_levels_in_common) {
		// on boucle sur tous les autres graphs
		for ($i = 1; $i < sizeof($graphs); $i++) {
			$na_axe3_levels_of_the_graph = getNALabelsInCommon($graphs[$i]['id_elem'],'na_axe3');
			if ($na_axe3_levels_of_the_graph) {
				// on fait l'intersection des na levels
				$na_axe3_levels_in_common = array_intersect_assoc( $na_axe3_levels_in_common, $na_axe3_levels_of_the_graph);
				unset($na_axe3_levels_of_the_graph);
			} else {
				// l'un des graphs n'a pas de na levels en commun, donc c'est fichu
				$na_axe3_levels_in_common = false;
				$i = 1000;		// on sort du for i
			}
		}
	}
}


// si on a rien :
if ((!$na_levels_in_common) && (!$na_axe3_levels_in_common)) {
	echo "<li style='border:0;background:red;color:#FFF;font-weight:bold;'>".__T('G_GDR_BUILDER_WARNING_NO_NA_LEVEL_IN_COMMON')."</li>";

	// 16:35 15/07/2009 GHX
	// Si aucun élément réseau en commun on vide les 2 champs dans la table sys_definition_dashboard
	$queryUpdateNA = "UPDATE sys_definition_dashboard SET sdd_selecteur_default_na = NULL, sdd_selecteur_default_na_axe3 = NULL WHERE sdd_id_page = '{$id_page}'";
	$db->execute($queryUpdateNA);
} else {
	// on renvoie la liste
	foreach ($na_levels_in_common as $key => $level) {
		echo "<li class='virtual_$key'>$level</li>";
		// la classe 'virtual_$key' est utilisée pour cacher la valeur de la $key dans
		// le <li> en vue de la recopier dans les <option value='$key'> du menu
		// "default na level" du selecteur
	}

	// on renvoie la liste des na 3eme axe en commun
	if ($na_axe3_levels_in_common)
	{
		foreach ($na_axe3_levels_in_common as $key => $level)
			echo "<li class='axe3 virtual_$key'>$level</li>";
	}
	else
	{
		$queryUpdateNA = "UPDATE sys_definition_dashboard SET sdd_selecteur_default_na_axe3 = NULL WHERE sdd_id_page = '{$id_page}'";
		$db->execute($queryUpdateNA);
	}	
	
	// 16:18 15/07/2009 GHX
	// Correction du BZ 10604
	if ( empty($current_selecteur_na) || !array_key_exists($current_selecteur_na,$na_levels_in_common) )
	{
		reset($na_levels_in_common);
		$na = key($na_levels_in_common);
		$queryUpdateNA = "UPDATE sys_definition_dashboard SET sdd_selecteur_default_na = '{$na}' WHERE sdd_id_page = '{$id_page}'";
		$db->execute($queryUpdateNA);
		
		if ($na_axe3_levels_in_common)
		{
			reset($na_axe3_levels_in_common);
			$na_axe3 = key($na_axe3_levels_in_common);
			$queryUpdateNA = "UPDATE sys_definition_dashboard SET sdd_selecteur_default_na_axe3 = '{$na_axe3}' WHERE sdd_id_page = '{$id_page}'";
			$db->execute($queryUpdateNA);
			
		}
	}
	
}

?>
