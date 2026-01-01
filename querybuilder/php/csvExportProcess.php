<?php
/*
 * 19/10/2011 SPD1: Querybuilder V2 - CSV export process  
 */

include_once('../../php/environnement_liens.php');

// Get user_id
$user_id = $argv[1];

// database connection
$database = DataBase::getConnection();

if($database->getCnx()) {
	// Create Qb model
	$qbMod = new QbModel($database);
	
	// Check if there is dead csv exports (exports in process but with a dead PID), change its state to 'Error'
	$qbMod->cleanCsvExports();
	
	$continue = true;
	
	// Process export
	while($continue) {
		$continue = $qbMod->startCsvExport($user_id);
	}
	
	// Close db connection
	$database->close();
} else {
	// If no connection ...
	echo 'No database connection csvExportProcess.php\n';
}