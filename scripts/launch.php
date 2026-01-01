<?php
/**
 * Execute a T&A process script
 * 05/02/2013 BBX
 * DE Optims T&A
 */
set_time_limit(45000);
include_once dirname(__FILE__) . '/../php/environnement_liens.php';
include_once REP_PHYSIQUE_NIVEAU_0."php/database_connection.php";

// Tests préliminaires
if(!isset($argv[1])) exit;
$script = $argv[1];
$module = get_sys_global_parameters('module','def');

// Inclusion du script à éxécuter
if(file_exists($script)) include($script);
else echo "Unable to launch $script";
?>