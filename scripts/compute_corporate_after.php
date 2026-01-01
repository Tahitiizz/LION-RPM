<?php
include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
include_once($repertoire_physique_niveau0 . "php/database_connection.php");

/*
 * Après le compute Corporate, on remet le on_off à un
 */
 
$query = "
	UPDATE sys_definition_group_table_network 
	SET on_off = 1 
	WHERE id_group_table = ".$group_table_param." 
";

pg_query($database_connection, $query);
?>