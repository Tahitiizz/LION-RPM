<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*/
?>
<?php
include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
include_once($repertoire_physique_niveau0 . "php/database_connection.php");
require_once($repertoire_physique_niveau0 . "php/postgres_functions.php");
include_once($repertoire_physique_niveau0 . "php/edw_function.php");
include_once($repertoire_physique_niveau0 . "php/edw_function_family.php");
include_once($repertoire_physique_niveau0 . "php/deploy_and_compute_functions.php");
include_once($repertoire_physique_niveau0 . "class/deploy.class.php");

// 19/05/2011 BBX - PARTITIONING -
// On peut désormais passer une instance de connexion
$database = Database::getConnection();
$deploy = new deploy($database, $id_group_table);

$todo=array("1" => array("raw","kpi"));
$deploy->create_indexes("create",$todo);
?>
