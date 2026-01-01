<?php	

	define("_CLASSPATH_","../../class/");

	require_once("../../php/environnement_liens.php");
	require_once("class/Log.class.php");
	//à décommenter pour le debug require_once("astellia/Log.php");
  	
	require_once("class/XpertApi.class.php");
	require_once("class/AstelliaSoapServer.class.php");
	
	
	Log::createInstance("logServer.txt", 0, "a+");

	//Log::getLog()->setConsoleLevel(Log::$DEBUG);
	Log::getLog()->setLevel(Log::$DEBUG);
	Log::getLog()->setInstanceName("API Xpert");
	Log::getLog()->begin("---API ACCESS BEGIN---");	
	
	// première étape : désactiver le cache lors de la phase de test
	ini_set("soap.wsdl_cache_enabled", "0");
  
	Log::getLog()->debug("post :".print_r($_POST,true));

	$server = new AstelliaSoapServer("XpertApi.wsdl", array('soap_version' => SOAP_1_2, 'encoding'=>'ISO-8859-1', 'uri' => __FILE__) );

	$server->setClass("XpertApi");

	$server->setPersistence(SOAP_PERSISTENCE_SESSION);
	$server->handle();

	Log::getLog()->end();
  
?>