<?php
include_once(dirname(__FILE__)."/../../php/environnement_liens.php");
include_once REP_PHYSIQUE_NIVEAU_0.'homepage/proxy/dao/class/JsonProvider.class.php';
include_once REP_PHYSIQUE_NIVEAU_0.'homepage/proxy/dao/models/QueryDataModel.class.php';
include_once REP_PHYSIQUE_NIVEAU_0.'homepage/proxy/dao/models/NaModelBis.class.php';
include_once REP_PHYSIQUE_NIVEAU_0.'homepage/proxy/dao/querybuilderExtJS4/class/QbRawKpi.class.php';


session_start();
$jsonProvider = new JsonProvider();
if(isset($_GET['method'])) {
	$method = $_GET['method'];
	if (method_exists($jsonProvider, $method)) {
		$function = array($jsonProvider,$method);
		$functionParameters = array_merge($_GET, $_POST);
		//$functionParameters = $_GET;
		$jsonResponse = call_user_func($function, $functionParameters);
		echo $jsonResponse;
	}
	else {
		echo $jsonProvider->getJSONErrorFromMessage("Method \'$method\' is not valid. Please refine your query.");
	}
}
else {
	echo $jsonProvider->getJSONErrorFromMessage("No method found. Please refine your query.");
}
?>
