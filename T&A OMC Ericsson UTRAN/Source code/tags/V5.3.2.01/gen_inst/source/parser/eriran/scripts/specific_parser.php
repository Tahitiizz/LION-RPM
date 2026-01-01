<?php


include_once(dirname(__FILE__)."/../../../php/environnement_liens.php");


// Connexion à la base de données locale
$database = new DatabaseConnection();
$system_name = get_sys_global_parameters("system_name");

$query="SELECT edw_field_name  FROM sys_field_reference WHERE on_off = 1 and  edw_field_name ilike '%_delta' ;";
$result = $database->executeQuery($query);
$values = $database->getQueryResults($result);
//echo "$query\n";
foreach($values as $tabSql){
	if (preg_match("/(.*)_DELTA$/i", $tabSql['edw_field_name'],$matches)){
		$query="SELECT edw_field_name  FROM sys_field_reference WHERE on_off = 1 and  edw_field_name = '{$matches[1]}' ;";
		//echo "$query\n";
		$result = $database->executeQuery($query);
		$values = $database->getQueryResults($result);
		if(count($values)==0){
		
			$query="UPDATE sys_field_reference SET on_off = 1, new_field = 1 where edw_field_name = '{$matches[1]}' ;";
			//echo "$query\n";
			$result = $database->executeQuery($query);
			$message = "The value for counter {$tabSql['edw_field_name']} has not been calculated in this Retreive. It will be calculated from the next Retreive.";
			sys_log_ast("Warning", $system_name, "Data Collect", $message, "support_1", "");
		}
	}
}

?>