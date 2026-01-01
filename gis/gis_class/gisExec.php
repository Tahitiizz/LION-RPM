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

	// Temp : à remplacer par une req. vers 'sys_gis_config_global' pour determiner le mode à employer

	$mode = "db";

	switch ($mode) {
		case 'test'		:	include 'construct_test.php';
		break;
		case 'db'		:	include 'construct_db.php';
		break;
	}

?>
