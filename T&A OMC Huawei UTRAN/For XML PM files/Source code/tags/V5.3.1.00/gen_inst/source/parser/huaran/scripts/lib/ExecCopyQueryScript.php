
<?php
/**
 * Corps de chacun des processus lancés dans le cadre du parallélisme interne
 * au execCopyQuery. 
 * 
 */

try{	
	// includes et constantes du CB (dont REP_PHYSIQUE_NIVEAU_0)
	include_once(dirname(__FILE__)."/../../../../php/environnement_liens.php");
	
	// recherche du nom du parser
	$module = strtolower(get_sys_global_parameters("module"));
	
	// include des scripts CB 
	include_once(REP_PHYSIQUE_NIVEAU_0 . "php/edw_function_family.php");
	include_once(REP_PHYSIQUE_NIVEAU_0 . "php/deploy_and_compute_functions.php");
	include_once(REP_PHYSIQUE_NIVEAU_0 . "php/postgres_functions.php");
		
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
	
	displayInDemon("<span style='color:green;'><b>========= Step execcopyQuery: output for process $pid - BEGIN =========</b></span>");
	
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
		// fichiers temporaires à traiter (= à copier dans les tables temporaires)
		$tempFilesCondition=$var['condition'];
		
		// nom de la classe à considérer, à savoir "ExecCopyQuery" de la librairie parser,
		// mais potentiellement une classe fille spécifique si besoin
		$className=$var["class_name"];
		
		Tools::$debug=$var["debug"];
				
		// classe spécifique à considérer 
		$ExecCopyQueryClass = new ReflectionClass($className);
		
		// appel au constructeur de la classe spécifique à considérer
		$ExecCopyQueryImpl = $ExecCopyQueryClass->newInstance($tempFilesCondition);
		
		
		// entité à traiter
		$entity=$ExecCopyQueryImpl->tempFilesCondition->getFileEntityCondition();
		
		// heures à traiter
		$hours=$ExecCopyQueryImpl->tempFilesCondition->getFileHoursCondition();
		
		if (Tools::isPerfTraceEnabled()) {
			// démarrage du chrono
			Tools::debugTimeExcStart("execCopyQuery");
		}
		
		// pour chaque heure à "retriever"
		foreach($hours as $hour){
			// file_demon
			displayInDemon("execCopyQuery:Traitement de l'entité $entity et de l'heure $hour");
		
			// TODO MHT2 filtrer l'objet params pour ne laisser que les todo de l'entité concernée ??
			$param=$ExecCopyQueryImpl->params->getWithFamily(substr($entity,0,strpos($entity,"_")));
			
			// pour l'entité à traiter : création des tables temporaires et mémorisation du nom du fichier CSV correspondant
			$ExecCopyQueryImpl->dbServices->generic_create_table_w_by_entity($hour,$param,$entity);
			
			// requête COPY pour remplir les tables temporaires
			$ExecCopyQueryImpl->dbServices->copy_into_temp_tables($ExecCopyQueryImpl->params);
				
			// suppression des fichiers CSV (d'extension ".sql", Cf. Tools->getCopyFilePath)
			$ExecCopyQueryImpl->dbServices->clean_copy_files();
				
			// comme la création des tables se fait avant la vérification qu'il y a des données,
			// s'il n'y a pas de données, il faut supprimer les tables quand même
			$ExecCopyQueryImpl->dbServices->clean_copy_tables();
		}
		
		
		if (Tools::isPerfTraceEnabled()) {
		Tools::debugTimeExcEnd("execCopyQuery");
		}
		
	}catch (ReflectionException $ex) {
		$message="Error: instantiation of ExecCopyQuery object failed (" . $ex->getMessage().")";
		sys_log_ast("Critical", "Trending&Aggregation", __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
		displayInDemon($message,'alert');
		// arrête de bufferiser les logs et vide le buffer dans le file_demon
		ob_end_flush();
		exit(-1);
	}
	
	// fin du chronométrage
	$processDuration=Tools::debugTimeExcEnd("process $pid");
	displayInDemon("<span style='color:green;'><b>========= Step execcopyQuery: output for process $pid - END =========</b></span>");
		
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