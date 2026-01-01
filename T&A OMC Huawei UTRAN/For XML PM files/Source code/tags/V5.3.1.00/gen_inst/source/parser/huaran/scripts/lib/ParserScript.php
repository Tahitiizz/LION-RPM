<?php
/**
 * Corps de chacun des processus lancés dans le cadre du parallélisme interne
 * au load data. 
 * 
 */

try{
	// includes et constantes du CB (dont REP_PHYSIQUE_NIVEAU_0)
	include_once(dirname(__FILE__)."/../../../../php/environnement_liens.php");
	
	// recherche du nom du parser 
	// = nom du sous-dossier de ".../parser/", nécessaire à certains includes
	$module = strtolower(get_sys_global_parameters("module"));
	
	// include des scripts CB 
	include_once(REP_PHYSIQUE_NIVEAU_0 . "php/edw_function_family.php");
	include_once(REP_PHYSIQUE_NIVEAU_0 . "php/deploy_and_compute_functions.php");
	include_once(REP_PHYSIQUE_NIVEAU_0 . "php/postgres_functions.php");
	
	// includes des classes Topology du CB
	include_once(REP_PHYSIQUE_NIVEAU_0 . "class/topology/TopologyLib.class.php");
	include_once(REP_PHYSIQUE_NIVEAU_0 . "class/topology/Topology.class.php");
	include_once(REP_PHYSIQUE_NIVEAU_0 . "class/topology/TopologyCheck.class.php");
	include_once(REP_PHYSIQUE_NIVEAU_0 . "class/topology/TopologyCorrect.class.php");
	include_once(REP_PHYSIQUE_NIVEAU_0 . "class/topology/TopologyAddElements.class.php");
	include_once(REP_PHYSIQUE_NIVEAU_0 . "class/topology/TopologyChanges.class.php");
	
	// includes de la librairie parser
	include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/lib/IncludeAll.php");
	
	// includes du code spécifique
	include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/IncludeAllSpecific.php");
	
	// numéro du processus courant (pid = "Process ID")
	$pid=getmypid();
	if($pid==FALSE) {
		$message="Error: impossible to get process ID (PID)";
		sys_log_ast("Critical", "Trending&Aggregation", __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
		displayInDemon($message,'alert');
	}
	
	// on commence à mesurer le temps d'execution de ce process
	Tools::debugTimeExcStart("process $pid");
	
	// redirige la sortie (= les logs destinés au file_demon) vers un buffer qui sera 
	// vidé ultérieurement.
	// L'objectif est de ne pas mélanger les logs des différents processus.
	$bufferingStarted = ob_start();
	if($bufferingStarted==FALSE) displayInDemon("warning: temporisation des logs non enclenchee (Cf. ob_start)");
	
	displayInDemon("<span style='color:green;'><b>========= Step load_data: output for process $pid - BEGIN =========</b></span>");
	
	// stocke le flux d'entrée dans une chaîne
	$serializedVars= stream_get_contents(STDIN);
	
	// convertit la chaîne en variable PHP
	$var=unserialize($serializedVars);
	if($var==FALSE){
		$message="Error: impossible to unserialize variable 'serializedVars' ";
		sys_log_ast("Critical", "Trending&Aggregation", __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
		displayInDemon("$message => $serializedVars",'alert');
		// arrête de bufferiser les logs et vide le buffer dans le file_demon
		ob_end_flush();
		exit(-1);
	}
	try {
		// nom de la classe fille de Parser
		$parserClassName=$var["parserName"];
		
		$single_process_mode=$var["single_process_mode"];
		
		$topoFileId = $var["topoFileId"];
		
		// classe Parser
		$parserClass = new ReflectionClass($parserClassName);
	
		// connection BDD pour ce process
		$dbConnection=new DataBaseConnection();
		
		// condition composite déterminant les fichiers à traiter
		$condition=$var["condition"];
		displayInDemon("Condition traitee: ".$condition->getDBCondition());
		
		//mode debug activé ou pas?
		Tools::$debug=$var['debug'];
		
		// appel au constructeur de la classe Parser
		$newParserImpl = $parserClass->newInstance($dbConnection, $condition, $single_process_mode, $topoFileId);
		
		// heure sur laquelle doit se baser l'upload de topo (Cf. CTU)
		$hourToUpload=$var['topologyHour'];
		
		// parsing des fichiers sources
		$newParserImpl->process($hourToUpload);
		
	}catch (ReflectionException $ex) {
		$message="Error: instantiation of Parser object failed (" . $ex->getMessage().")";
		sys_log_ast("Critical", "Trending&Aggregation", __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
		displayInDemon($message,'alert');
		// libère la connection BDD
		$dbConnection->close();
		displayInDemon("<span style='color:green;'><b>========= Step load_data: output for process $pid - END =========</b></span>");
		// arrête de bufferiser les logs et vide le buffer dans le file_demon
		ob_end_flush();
		exit(-1);
	}
	
	// libère la connection BDD
	$dbConnection->close();	
	
	// fin du chronométrage
	$processDuration=Tools::debugTimeExcEnd("process $pid");
	
	displayInDemon("<span style='color:green;'><b>========= Step load_data: output for process $pid - END =========</b></span>");
	
	// arrête de bufferiser les logs et vide le buffer dans le file_demon
	ob_end_flush();
	
	// logs de niveau debug : statistiques sur les durées des processus
	if(Tools::$debug){
		// gestionnaire unique des différents processus
		$processManager=ProcessManager::getInstance();
		
		// stockage du résultat du chronométrage, ainsi mis à disposition du processManager
		$processManager->saveVariable('processesDuration',array("PID $pid" => $processDuration));
		
		// stockage du résultat du chronométrage, ainsi mis à disposition du processManager
		// Cette condition n'est-elle pas déjà connue par le process manager juste
		// avant la création du processus ? Le processManager est le plus generique possible. Les conditions ne sont pas "connues" du ProcessManager.
		
		$processManager->saveVariable('processesCondition',array("PID $pid" => $condition->getDBCondition()));
	}
	
	// 0 = tout s'est bien passé
	exit(0);


}//End of try
catch (Exception $ex) {
	$message="Error: captured error (" . $ex->getMessage().")";
	sys_log_ast("Critical", "Trending&Aggregation", __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
	displayInDemon($message,'alert');
	// libère la connection BDD
	$dbConnection->close();
	displayInDemon("<span style='color:green;'><b>========= Step load_data: output for process $pid - END =========</b></span>");
	// arrête de bufferiser les logs et vide le buffer dans le file_demon
	ob_end_flush();
	exit(-1);
}

?>