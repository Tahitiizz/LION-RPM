<?php
include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
include_once($repertoire_physique_niveau0 . "php/database_connection.php");

/*
 * Avant le compute Corporate, on met le on_off à zéro pour les niveaux d'aggrégation qui n'ont pas besoins d'être calculés.
 * On met on_off = 0 pour tous les niveaux d'aggrégation différents du niveau maximum
 */

$query_na_max = "
	SELECT network_agregation
	FROM sys_definition_group_table_network
	WHERE id_group_table = ".$group_table_param."
		AND data_type = 'raw'
	ORDER BY rank desc limit 1";
$result_na_max = pg_query($database_connection, $query_na_max);
list($na_max) = pg_fetch_row($result_na_max);

$query = "
	UPDATE sys_definition_group_table_network 
	SET on_off = 0 
	WHERE id_group_table = ".$group_table_param." 
		AND network_agregation <> '".$na_max."'
";

pg_query($database_connection, $query);
?>