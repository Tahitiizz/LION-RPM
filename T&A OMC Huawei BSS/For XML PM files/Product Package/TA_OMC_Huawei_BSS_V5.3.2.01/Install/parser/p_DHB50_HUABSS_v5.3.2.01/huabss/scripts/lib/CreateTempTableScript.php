
<?php
/**
 * Corps de chacun des processus lancés dans le cadre du parallélisme interne
 * au create_temp_table. 
 * 
 */

try{	
	// includes et constantes du CB (dont REP_PHYSIQUE_NIVEAU_0)
	include_once(dirname(__FILE__)."/../../../../php/environnement_liens.php");
	
	// recherche du nom du parser
	$module = strtolower(get_sys_global_parameters("module"));
	
	// include des fichiers nécessaires
	
	// include des scripts CB 
	include_once(REP_PHYSIQUE_NIVEAU_0 . "php/edw_function_family.php");
	include_once(REP_PHYSIQUE_NIVEAU_0 . "php/database_connection.php");
	include_once(REP_PHYSIQUE_NIVEAU_0 . "php/deploy_and_compute_functions.php");
	include_once(REP_PHYSIQUE_NIVEAU_0 . "php/postgres_functions.php");
	include_once(REP_PHYSIQUE_NIVEAU_0 . "class/create_temp_table_generic.class.php");
		
	// includes de la librairie parser
	include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/lib/IncludeAll.php");
	
	// includes du code spécifique
	include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/IncludeAllSpecific.php");
	
	// numéro du processus courant (pid = "Process ID")
	$pid=getmypid();
	if($pid==FALSE){
		$message="Error: impossible to get process ID (PID)";
		sys_log_ast("Critical", "Trending&Aggregation", __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
		displayInDemon($message,'alert');
		exit(-1);
	}
	
	// on commence à mesurer le temps d'execution de ce process
	Tools::debugTimeExcStart("process $pid");
	
	// redirige la sortie (= les logs destinés au file_demon) vers un buffer qui sera 
	// vidé ultérieurement.
	// L'objectif est de ne pas mélanger les logs des différents processus.
	$bufferingStarted = ob_start();
	if($bufferingStarted==FALSE) displayInDemon("warning: temporisation des logs non enclenchee (Cf. ob_start)");
	
	displayInDemon("<span style='color:green;'><b>========= Step create_temp_table: output for process $pid - BEGIN =========</b></span>");
	
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
		// niveau réseaux minimum de la famille (ou des familles) à traiter pour le nouveau processus
		$tempTableCondition=$var['condition'];
		
		// nom de la classe spécifique à considérer (classe fille de lib/CreateTempTable,
		// le plus souvent : create_temp_table_omc)
		$className=$var["class_name"];
		
		//mode single_process passé à false
		$single_process_mode=$var["single_process_mode"];
		
		// classe spécifique à considérer 
		$CreateTempTableClass = new ReflectionClass($className);
		
		// appel au constructeur de la classe spécifique à considérer
		$CreateTempTableImpl = $CreateTempTableClass->newInstance($tempTableCondition,$single_process_mode);
		
		// étapes 1 et 2
		//$CreateTempTableImpl->stepOneAndTwo();
		
	}catch (ReflectionException $ex) {
		$message="Error: instantiation of CreateTempTable object failed (" . $ex->getMessage().")";
		sys_log_ast("Critical", "Trending&Aggregation", __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
		displayInDemon($message,'alert');
		// arrête de bufferiser les logs et vide le buffer dans le file_demon
		ob_end_flush();
		exit(-1);
	}
	
	// fin du chronométrage
	$processDuration=Tools::debugTimeExcEnd("process $pid");
	displayInDemon("<span style='color:green;'><b>========= Step create_temp_table: output for process $pid - END =========</b></span>");
		
	// arrête de bufferiser les logs et vide le buffer dans le file_demon
	ob_end_flush();
	
	// 0 = tout s'est bien passé
	exit(0);


}//End of try
catch (Exception $ex) {
	$message="Error: captured error (" . $ex->getMessage().")";
	sys_log_ast("Critical", "Trending&Aggregation", __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
	displayInDemon($message,'alert');
	// arrête de bufferiser les logs et vide le buffer dans le file_demon
	ob_end_flush();
	exit(-1);
}

?>