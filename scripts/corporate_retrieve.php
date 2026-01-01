<?php
/**
 * Gestion de toute la partie retrieve des données depuis l'OMC ou depuis des flat file
 * Integration des données dans la table daily
 */
set_time_limit(3600000);
include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
include_once($repertoire_physique_niveau0 . "php/database_connection.php");
include_once($repertoire_physique_niveau0 . "php/edw_function.php");
include_once($repertoire_physique_niveau0 . "php/edw_function_family.php");
include_once($repertoire_physique_niveau0 . "php/postgres_functions.php");
include_once($repertoire_physique_niveau0 . "php/environnement_datawarehouse.php");
include_once($repertoire_physique_niveau0 . "php/environnement_donnees.php");
include_once($repertoire_physique_niveau0 . "class/corporate_retrieve.class.php");
include_once($repertoire_physique_niveau0 . "php/deploy_and_compute_functions.php");

// ********** DEBUT DU SCRIPT *****************
printdate();
list($usec, $sec) = explode(" ", microtime());
$start = ((float)$usec + (float)$sec);

$query_select_gt_axe = "SELECT id_group_table FROM sys_definition_gt_axe ORDER BY id_group_table";
$result_select_gt_axe = pg_query($database_connection, $query_select_gt_axe);

while ( list($id_group_table_current) = pg_fetch_row($result_select_gt_axe) ) {
	
	displayInDemon('Groupe Table :'.$id_group_table_current,'title');
	displayInDemon(date('r'));
	
	$corporate = new CorporateRetrieve($database_connection, $repertoire_physique_niveau0, $id_group_table_current);
	$connections = $corporate->getAllConnections();

	foreach ( $connections as $connection_id => $connection_properties ) {
		print '<br><br>-> Connection to '.$connection_properties['name'].' ['.$connection_properties['ip_address'].']<br>';
		
		$datesComputes = $corporate->getComputedDates($connection_properties['ip_address'], $connection_properties['login'], $connection_properties['directory']);
		if ( $datesComputes === false ) {
			print 'Aucun compute n\'a été fait depuis la dernière fois<br>';
			continue;
		}
		
		$query_list = $corporate->getSourceTargetTable($connection_properties['id_region']);
		$query_list = $corporate->queryFilter($query_list, $datesComputes);
		
		print '***** Collecte et Integration des données à partir du serveur distant *****<br>';	
		foreach ( $query_list["source"] as $key => $query ) {
			$status_return = $corporate->copyServer2Server($key, $query, $connection_properties['ip_address'], $connection_properties['login'], $connection_properties['directory']);
			// la fonction precedente retourne 0 si la connection n'a pas été établie
			if ( $status_return )
				$corporate->loadDataFromFile($query_list["cible"][$key], $key);
		}
		
		print '***** Collecte et Integration de la table de reference distante *****<br>';
		$query_fields = "SELECT a.attname FROM pg_catalog.pg_attribute a WHERE a.attrelid IN (SELECT c.oid FROM pg_catalog.pg_class c LEFT JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace WHERE c.relname ~ '^edw_object_".$id_group_table_current."_ref$' AND pg_catalog.pg_table_is_visible(c.oid) ORDER BY 1) AND a.attnum > 0 AND NOT a.attisdropped AND a.attname <> 'the_geom' ORDER BY a.attnum";
		$key_fields   = "fields_edw_object_".$id_group_table_current."_ref".uniqid("");
		$status_return = $corporate->copyServer2Server($key_fields, $query_fields, $connection_properties['ip_address'], $connection_properties['login'], $connection_properties['directory']);
		// la fonction precedente retourne 0 si la connection n'a pas été établie           
		if ( $status_return ) {
			$query = "SELECT DISTINCT * FROM edw_object_".$id_group_table_current."_ref";
			$key   = "edw_object_".$id_group_table_current."_ref".uniqid("");
			$status_return = $corporate->copyServer2Server($key, $query, $connection_properties['ip_address'], $connection_properties['login'], $connection_properties['directory']);
			if ( $status_return )
				$corporate->loadAndIntegrateRemoteReferenceTable($key_fields, $key, $connection_properties['id_region']);
		}
		$corporate->delComputedDates($datesComputes, $connection_properties['ip_address'], $connection_properties['login'], $connection_properties['directory']);
	}

	$corporate->updateNACorporate();
}

sys_log("Group Network 1" , 'Retrieve Data', "$PHP_SELF", $start);
displayInDemon(date('r'));
?>