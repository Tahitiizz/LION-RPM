<?php
/*
*	03/10/2011 - Copyright Astellia
*
*	24/10/2011 ACS BZ 24356 Product indicated is master even if reprocessing has been launched on slave
*	24/10/2011 ACS BZ 24348 Successful message even if no files have been deleted
*   14/10/2011 ACS BZ 24188 Impossible to filter on "Data reprocessing" module 
*	03/10/2011 ACS
*		- Mantis 615: DE Data reprocessing GUI
*/
?>
<?php

session_start();
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";
include_once(REP_PHYSIQUE_NIVEAU_0 . "/class/SSHConnection.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "/class/DataReprocessing.class.php");

// Connexion à la base du produit
$idProduct = $_POST['product'];
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
$database = Database::getConnection($_POST['product']);

$dataReprocessing = new DataReprocessing($database, $idProduct);

$mode = $_POST['mode'];

// action is confirmed?
$confirm = $_POST['confirm'] == "confirm";

	
$dataReprocessing->setMode($mode);

$dataReprocessing->setDates($_POST["dates"]);
$dataReprocessing->setConnections($_POST['connections']);


// check that inputs are correct
$errorCode = $dataReprocessing->checkData(); // 0 - success
if ($errorCode != 0) {
	$messageType = "error";
	
	if ($errorCode == 1) {
		$messageAlert = __T('A_PROCESS_DATA_REPROCESS_MODE');
	}
	else if ($errorCode == 2) {
		$messageAlert = __T('A_PROCESS_DATA_REPROCESS_DATE');
	}
	else if ($errorCode == 3) {
		$messageAlert = __T('A_PROCESS_DATA_REPROCESS_CONNECTION');
	}
}
else {
	if (!$confirm) { // case of reprocess
		$messageType = "warning";
		
		if ($mode == 0) {
			if ($dataReprocessing->countStoppedProcesses() > 0) {
				$messageAlert = "code2"; // reprocess files with stopped processes
			}
			else {
				$messageAlert = "code1"; // reprocess files
			}
		}
		else {
			$messageAlert = "code0"; // delete files
		}
	}
	// user has confirmed the process
	else {
		// 24/10/2011 ACS BZ 24348 Successful message even if no files have been deleted
		$nbImpactedFiles = $dataReprocessing->process();
		
		// 14/10/2011 ACS BZ 24190 List of available dates not updated after "Delete files" 
		$dateStr = ', "nbAvailableDates":'.json_encode(sizeof($dataReprocessing->getAvailableDates($idProduct))).', "availableDates":'.json_encode($dataReprocessing->getAvailableDates($idProduct));
		
		// 14/10/2011 ACS BZ 24188 Impossible to filter on "Data reprocessing" module 
		addLogs($dataReprocessing, $idProduct);
		
		if ($dataReprocessing->getMode() == 0) {
			$messageAlert =  __T('A_PROCESS_DATA_REPROCESS_REPROCESS_SUCCESS');
		}
		else {
			$messageAlert =  __T('A_PROCESS_DATA_REPROCESS_DELETE_SUCCESS', $nbImpactedFiles);
		}
	}
}

echo '{"message_alert":\''.$messageAlert.'\', "message_type":\''.$messageType.'\''.$dateStr.'}';

exit;

function addLogs($dataReprocessing, $idProduct) {
	$logModuleName = __T('A_TRACELOG_MODULE_LABEL_DATA_REPROCESS');
	$logSystem = get_sys_global_parameters('system_name');
	
	$logUserInfo = getUserInfo($_SESSION['id_user']);
	$logDate = getStrDates($dataReprocessing);
	
	// 24/10/2011 ACS BZ 24356 Product indicated is master even if reprocessing has been launched on slave
	$messageType = "info";
	if ($dataReprocessing->getMode() == 0) {
		$logText = __T('A_PROCESS_DATA_LOG_TEXT_REPROCESS', $logUserInfo['username'], $logDate);
		sys_log_ast('Info', $logSystem, $logModuleName, $logText, "support_1", "", $idProduct);
	}
	else {
		$logConnection = getStrConnections($dataReprocessing);
		$logText = __T('A_PROCESS_DATA_LOG_TEXT_DELETE', $logUserInfo['username'], $logDate, $logConnection);
		sys_log_ast('Info', $logSystem, $logModuleName, $logText, "support_1", "", $idProduct);
	}
}

function getStrDates($dataReprocessing) {
	$result = "";
	foreach ($dataReprocessing->getDates() as $date) {
		if ($result != "") {
			$result .= " & ";
		}
		$result .= substr($date, 6, 2)."/".substr($date, 4, 2)."/".substr($date, 0, 4);
	}
	return $result;
}

function getStrConnections($dataReprocessing) {
	$result = "";
	
	$activeConnections = $dataReprocessing->getActiveConnections();
	
	$connections = array_intersect($activeConnections, $dataReprocessing->getConnections());
	foreach ($connections as $connectionName => $connection) {
		if ($result != "") {
			$result .= " & ";
		}
		$result .= $connectionName;
	}
	return $result;
}

?>