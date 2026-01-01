<?php
include_once($repertoire_physique_niveau0."php/environnement_liens.php");
include_once($repertoire_physique_niveau0."php/database_connection.php");
include_once($repertoire_physique_niveau0."php/edw_function.php");
include_once($repertoire_physique_niveau0."php/environnement_datawarehouse.php");

// 16:59 07/07/2009 SCT : Bug 9735 => modification des messages
$system_name   = get_sys_global_parameters("system_name");
//$system_module = get_sys_global_parameters("module");
$system_module = __T('A_TRACELOG_MODULE_LABEL_COMPUTE');

$query = "SELECT master_name FROM sys_definition_master WHERE master_id = ( SELECT process FROM sys_process_encours WHERE encours=1 and done=0 order by oid DESC LIMIT 1 )";
$res = pg_query($database_connection, $query);
$row = pg_fetch_array($res, 0);
$message = __T('A_COMPUTE_CORPORATE_STOP_INFO');
sys_log_ast("Info", $system_name, $system_module, $message, "support_1", "");
?>
