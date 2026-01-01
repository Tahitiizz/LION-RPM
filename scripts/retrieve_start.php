<?php
/*
 * 22/03/2013	FRR1	DE TA Optim - Retrieve a blanc
 *
 */

//Tracelog : PROCESS : Start Retrieve
sys_log_ast("Info", get_sys_global_parameters("system_name"), 	
					__T('A_TRACELOG_MODULE_LABEL_COLLECT'), 
					__T('A_FLAT_FILE_UPLOAD_ALARM_START_RETRIEVE_PROCESS'), "support_1", "");

//Get database connection
$database = Database::getConnection();

//Get number of files waiting for retrieve process
$query = 'SELECT * FROM sys_flat_file_uploaded_list';
$result = $database->execute($query);
$nbResult = $database->getNumRows($result);

//If no files to process, end of retrieve process
if($nbResult == 0){

	//Tracelog and demon : No files to be retrieved 
	displayInDemon( "No files to be retrieved" );
	sys_log_ast("Info", get_sys_global_parameters("system_name"), 	
					__T('A_TRACELOG_MODULE_LABEL_COLLECT'), 
					__T('A_RETRIEVE_START_NO_FILE_TO_PROCESS'), "support_1", "");

	//Select all retrieve steps except retrieve start and retrieve stop
	$query="SELECT * FROM sys_definition_step 
				WHERE family_id=(
					SELECT family_id::TEXT FROM sys_definition_family WHERE master_id=(
						SELECT master_id::TEXT FROM sys_definition_master WHERE master_name like 'Retrieve'
					)
				) 
				AND step_name != 'Retrieve - start' 
				AND step_name != 'Retrieve - stop' 
				AND on_off = '1' 
				ORDER BY ordre";

	$ret = $database->executeQuery($query);
	$result = $database->getQueryResults($ret);

	//Insert all retrieve steps with state done
	foreach($result as $row){
		$query="INSERT INTO sys_step_track( step_id, family_id, master_id, step_order, encours, done, date) 
					VALUES ('" . $row['step_id'] ."', 
					'" . $row['family_id'] ."', 
					null, 
					'" . $row['ordre'] ."', 
					'FALSE', 
					'TRUE', 
					'" . time() . "')";

		$ret = $database->executeQuery($query);
	}
}
?>
