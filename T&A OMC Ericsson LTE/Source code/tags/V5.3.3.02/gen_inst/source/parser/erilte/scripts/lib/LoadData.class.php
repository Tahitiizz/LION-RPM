<?php

/**
 * 
 * Gère la séquence d'appel du process
 * @author g.francois
 *
 */

class LoadData {
	
	/**
	 * Cle associee aux parseurs de fichiers sources.
	 */
	const KEY_PARSER = "parser";
	
	/**
	 * Cle associee aux parseurs de fichiers de topologie.
	 */
	const KEY_TOPO_FILE_PARSER = "topo_parser";
	
	/**
	 * 
	 * Permet l'appel aux fonctions de gestion de la topologie
	 * @var LoadTopology
	 */
	protected $topology;

	/**
	 * 
	 * Liste des couples Extension / ParserName qui vont être traités
	 * @var array
	 */
	protected $fileExtensions;
	
	/**
	 * 
	 * Objet de gestion de la connexion à la base de données
	 * @var DataBaseConnection
	 */
	protected $dbConnection;
	
	/**
	 * 
	 * Requêtes SQL
	 */
	protected $dbServices;

	/**
	 * 
	 * Gestionnaire des processus
	 */
	protected $processManager;

	/**
	 * 
	 * Flag permettant de demander du mono processus 
	 * /débrayage du parallélisme)
	 */
	protected $singleProcess;
	
	/**
	 * 
	 * Parser à gérer
	 * Ex :  array(LoadData::KEY_PARSER => "ParserAsn1Bss");
	 */
	protected $parsersType;
	
	/**
	 * 
	 * Heure que les différents parsers doivent considérer lors de 
	 * l'upload de topo (mode croisière de la CTU).
	 * La valeur spéciale "ALL" signifie "toutes les heures" (mode full de la CTU). 
	 */
	protected $topologyHour;
	
	/**
	 * 
	 * Constructeur
	 * @param $parsersType array Liste des extensions / parser à traiter
	 * @param $execQueryCopyClassName string Nom de la classe ExecCopyQueryImpl à utiliser
	 */
	public function __construct($parsersType,$execQueryCopyClassName='ExecCopyQuery') {
		// recupération du parametre global qui definit la periodicite des mises à jour complete de la topologie
		// Le traitement de mise à jour de topo doit être effectué si le nombre d'heure totale collectée est inférieur ou égale à 20
		// et si l'heure courante est égale au paramètre global définissant l'heure de traitement de mise à jour de la topo
		$this->parsersType = $parsersType;
		
		$this->execQueryCopyClassName=$execQueryCopyClassName;
		
		$this->dbConnection = new DatabaseConnection();
		
		$this->processManager=ProcessManager::getInstance();
			
		// initialisation, confirmée ou pas dans la méthode "launch"
		$this->topologyHour="ALL";
		
		//objet DataBaseServices specifique ou générique selon getDatabaseServicesClassName()
		$this->dbServices = $this->getDatabaseServicesObject();
		
		//mode debug activé?
		Tools::$debug = get_sys_debug('retrieve_load_data');
	}
	
	/**
	 * Pour les produits avec fichier de topologie à parser.
	 * Enter description here ...
	 * @param TopoParser $objectFile Parser dédié au parsing d'un fichier de topologie
	 */
	private function processTopoFile(TopoParser $objectFile) {
		if (Tools::isPerfTraceEnabled()) {Tools::debugTimeExcStart("processTopoFile");}
		
		// de nombreux produits n'ont pas de fichier de topo à parser
		if (isset($objectFile)) {
			$flatFile=$objectFile->getTopoFile();
			// si le fichier de topo est disponible
			if($flatFile!=NULL){
				sys_log_ast("Info", "Trending&Aggregation", __T('A_TRACELOG_MODULE_LABEL_COLLECT'), "Parsing topology file", "support_1", "");
				// parsing et upload ?
				$topoFileId = $objectFile->parseTopoFile($flatFile);
				
				//nettoyage des fichiers topos
				$objectFile->clean_topo_files_uploaded();
				
				//suppression de fichier topo copié depuis la table sys_flat_file_uploaded_list_archive
				if(file_exists($flatFile->flat_file_location))	{
					unlink($flatFile->flat_file_location);
				}
				sys_log_ast("Info", "Trending&Aggregation", __T('A_TRACELOG_MODULE_LABEL_COLLECT'), "Topology file parsed", "support_1", "");
			}else{
				sys_log_ast("Critical", "Trending&Aggregation", __T('A_TRACELOG_MODULE_LABEL_COLLECT'), "No topo file collected", "support_1", "");
			}
		}
		if (Tools::isPerfTraceEnabled()) {Tools::debugTimeExcEnd("processTopoFile");}
		
		return $topoFileId;
	}

	/**
	 * 
	 * Lance les traitements du process de retrieve
	 * @param String $parserName
	 */
	private function processParser($parserName, $topoFileId = null, $single_process_mode=TRUE) {
		try {
				// script utilisé pour le parsing.
				// c'est le "corps" de chaque process lancé
				$cmd='php lib/ParserScript.php';
				
				// on récupère l'objet ConditionProvider à utiliser pour ce $parserName
				// => appel de la méthode static Parser::getConditionProvider()
				$parserClass = new ReflectionClass($parserName);
				$rMethod=$parserClass->getMethod("getConditionProvider");
				$conditionProvider=$rMethod->invoke(null,$this->dbServices);
				
				// si le mode mono-processus est explicitement demandé (parallélisation désactivée)
				if($single_process_mode){
					//pas la peine d'avoir plusieurs conditions; la condition du parser suffit (NULL ou pas)
					$arrayCondition=array($conditionProvider->getParserCondition());					
				}else{
					//liste des contitions à processer (1 condition => 1 processus à créer)
					$arrayCondition=$conditionProvider->getConditions();
					displayInDemon(count($arrayCondition)." processus vont être crees pour le parser $parserName .");
				}
				
				// pour chaque condition  (i.e. pour chaque "process à créer")
				foreach ($arrayCondition as $condition) {
					// si le mode mono-processus est explicitement demandé (parallélisation désactivée)
					if($single_process_mode){
						// on reste à l'intérieur du process courant (le process est unique)
						$newParserImpl = $parserClass->newInstance($this->dbConnection, $condition, $single_process_mode);
						$newParserImpl->process($this->topologyHour);
					}else{
						// mode multi process : on demande le lancement d'un nouveau process					
						$env = array('condition' => $condition,
							'parserName' => $parserName,
							'topologyHour'=> $this->topologyHour,
							'debug' => Tools::$debug,
							'single_process_mode'=>$single_process_mode,
							'topoFileId' => $topoFileId);
						$this->processManager->launchProcess($cmd, $env);
					}
				}				
		}catch(ReflectionException $ex) {
				displayInDemon("Erreur au lancement du traitement du parser : " . $ex->getMessage());
		}
	}
	
	
	/**
	*
	* Lance les traitements du process de l'exexCopyQuery
	* @param TempFilesCondition $condition une condition portant sur des fichiers temporaires
	* @param Array $hours liste des heures à traiter
	*/
	private function processTempFileCondition(TempFilesCondition $condition) {
		try {
				// nous allons créer un processus enfant par condition, c'est à dire
				// par entité 			
				$processManager=ProcessManager::getInstance();
				$cmd='php lib/ExecCopyQueryScript.php';
										
				// condition d'éligibilité des fichiers temporaires
				$env['condition']=$condition;
					
				//niveau de debug
				$env['debug']=Tools::$debug;
					
				// nom de la classe à considérer
				$env['class_name']=$this->execQueryCopyClassName;
					
				// lancment (ou mise en file d'attente) du nouveau processus
				$processManager->launchProcess($cmd, $env);
			}
			catch(Exception $ex) {
				displayInDemon("Erreur au lancement du traitement du execCopyQuery : " . $ex->getMessage());
			}
	}
	/**
	 * 
	 * /On set le fileType en fonction du type de fichier de Topology collecte
	 */		
	public function checkFileType(){
		$parsersType = $this->parsersType;
		$fileType = NULL;
		if(isset($parsersType['topo_parser'])){
			try{
				$parserTopo = $parsersType['topo_parser'];
				if($parserTopo == 'TopoParserCsv'){
					$fileType = new FileTypeCondition("flat_file_name", "!=", "TOPOLOGY - C_BTS");
				}
				else{
					$fileType = new FileTypeCondition("flat_file_name", "!=", "*YYYYMMDD*.xml");
				}	
			}
		 	catch (Exception $ex) {
				displayInDemon("Erreur lors du check du Type de parser : " . $ex->getMessage());
			}
		}
		return $fileType;
		
	}
	
	/**
	 * 
	 * Lance le traitement pour les fichiers collectés
	 */
	public function launch() {		
		//On vérifie si le fichier collecté est un fichier de topologiy 
		$fileType = $this->checkFileType();
		//=================== CTU (mode "full" ou "croisère")
		// on recherche la liste ordonnée des heures collectées dans la table "sys_flat_file_uploaded_list" avec le fileType en condition.
		// Dans le cas ou on collecte un fichier de topology il prend la valeur TOPOLOGY - C_BTS ou *YYYYMMDD*.xml. Le getHoursCollected 
		// retourne ainsi les heures collectées uniquement pour les fichiers source  et permet d'éviter la reprise de données pour l'heure 00h00		
		//cf BZ34233
		$collectedHoursLog=$this->dbServices->getHoursCollected($fileType);
		$collectedHours=array_keys($collectedHoursLog);
		// si aucune heure à parser
		if(count($collectedHours)==0) return;
		
		//affichage dans trace logs
		sys_log_ast("Info", "Trending&Aggregation", __T('A_TRACELOG_MODULE_LABEL_COLLECT'), array_sum($collectedHoursLog) . " files to parse over " . count($collectedHours) . " Hour(s)(". implode(',', $collectedHours) .")", "support_1", "");
		
		//TODO MHT2 supprimer la CTU, faire un update de topo full à chaque retrieve 
		
		// paramètres globaux liés à la CTU (= upload de topology pour toutes les heures 
		// ou pour l'heure la plus récente)
		//$topology_max_hour_retrieved = get_sys_global_parameters("topology_max_hour_retrieved",20); //renvoie 20 si parametre non défini en base
		//$topology_last_update_date = get_sys_global_parameters("topology_last_update_date",date("Ymd")); //renvoie la date d'aujourd'hui si parametre non défini en base
		
		// on prendre en compte toutes les heures collectées (= mode "full")
		// si le nb d'heures collectées est < au paramètre "$topology_max_hour_retrieved" (ex : 20h)
		// et si ce mode "full" n'a pas déjà été fait ce jour		
		//$topologyFromAllHours=((count($collectedHours) <= $topology_max_hour_retrieved) && $topology_last_update_date != date("Ymd"));
		
		// si mode "full"
		//if($topologyFromAllHours)
			$this->topologyHour='ALL';
		// sinon (mode "croisière")
		/*
		else{
			//upload topo only for this hour (à traiter coter spécifique)
			$this->topologyHour=$collectedHours[count($collectedHours)-1];
		}
		*/
		//===================
		
		// choix du mode : single ou multi processus
		//si le parametre n'est pas défini get_sys_global_parameters renvoie 1
		$single_process_mode=get_sys_global_parameters('retrieve_single_process',0)==0?FALSE:TRUE;
		
		
		//générer un warning si paramètre automapping_last_update_date manquant
		if(get_sys_global_parameters('automapping_last_update_date')==0){
			displayInDemon("Missing parameter automapping_last_update_date",'alert');
		}
		
		// méthode permettant un traitement spécifique à un produit
		$this->onParsingStart();
		
		// on commence par parser la topologie
		$topoFileId = null;
		foreach ($this->parsersType as  $type => $parsersName) {
			try {
				if($type==self::KEY_TOPO_FILE_PARSER){
					// objet de parsing du fichier de topologie  (exple nsnran)
					$topoParser = new ReflectionClass($parsersName);
					$topoParserImpl = $topoParser->newInstance($this->dbConnection);
					$topoFileId = $this->processTopoFile($topoParserImpl);
				}
			}
			catch (ReflectionException $ex) {
				displayInDemon("Erreur au lancement du traitement du parser de topologie : " . $ex->getMessage());
			}
		}
		//attendre la fin des process
		$this->processManager->waitEndOfAllProcess(TRUE,'TopoFile');
		
		// on parse les autres parsers (ex : ASN1 et XML, BR9 et BR10)
		foreach ($this->parsersType as  $type => $parsersName) {
			try {
				// cas d'un parser de "fichier source standard"
				if(!($type==self::KEY_TOPO_FILE_PARSER)){
					$parsersName_tab=explode(",", $parsersName);
					foreach ($parsersName_tab as $parser) {	
						$this->processParser($parser, $topoFileId, $single_process_mode);				
					}
				}
			}
			catch (ReflectionException $ex) {
				displayInDemon("Erreur au lancement du traitement du parser : " . $ex->getMessage());
			}
		}
		//attendre la fin des process
		$this->processManager->waitEndOfAllProcess(TRUE,'PmFile');
		
		//maj de la date d'automatic mapping
		$this->updateLastAutomappingDate();
		
		// méthode permettant un traitement spécifique à un produit
		$this->onParsingEnd();
		
		
		//===================Processes logs and stats
		
		// logs de niveau debug : statistiques sur les durées des processus
		if(Tools::$debug && !$single_process_mode){
			$processesDuration=$this->processManager->getVariable('processesDuration');
			$processesCondition=$this->processManager->getVariable('processesCondition');
			$recap=array_merge_recursive($processesDuration,$processesCondition);
			//stats
			$stats["nb of process"]=count($processesDuration);
			$stats["min process duration"]=min($processesDuration);
			$stats["max process duration"]=max($processesDuration);
			$stats["average process duration"]=array_sum($processesDuration)/count($processesDuration);
			$stats["standard deviation"]=Tools::standard_deviation($processesDuration);
			//affichage des logs
			echo Tools::display_tab($recap, array("process PID","duration","condition"));
			echo Tools::display_array($stats);
		}

		//===================
				
		
		//============================ Upload de topo & ExecCopyQuery
		// recupération de l'objet $param à partir du fichier "paramsSerialized.ser",
		// (= paramétrage des familles)
		$filename=REP_PHYSIQUE_NIVEAU_0 . "parser/paramsSerialized.ser";
		if(file_exists($filename)){
			$paramsSerialized=file_get_contents($filename);
			$this->params=unserialize($paramsSerialized);
			//unlink($filename);
			
			//uploadTopo pour les familles
			$this->uploadTopology();
			
			//mise à jour du parametre topology_last_update_date
			// TODO JGU2 : à faire seulement si $this->topologyHour=='ALL' => sinon lorsque le nombre d'heure collectée 
			// dépasse le paramètre global "topology_max_hour_retrieved", on va à tort mettre à 
			// jour le paramètre global "topology_last_update_date".  			
			/*if($this->topologyHour=='ALL')*/
			//$this->updateLastTopologyDate();
			
			// si le mode mono-processus est explicitement demandé (parallélisation désactivée)
			if($single_process_mode){
				// pour chaque heure collectée
				foreach ($collectedHours as $hour) {
					// execCopyQuery (= chargement des fichiers csv dans les tables temporaires w_astellia)
					$this->execCopyQuerySingleProcessMode($hour);
				}
			}
			// sinon (mode avec parallélisation des tâches)
			else{
				// TODO : temporaire
				displayInDemon("Before ExecCopyQuery", "alert"); printdate();
				
				// récupération des conditions d'éligibilité des fichiers temporaires :
				// pour l'instant, nous générons une condition par entité utile (= avec compteurs activés)
				// sans inclure de contrainte concernant l'heure de capture (sinon cela amenerait à
				// de trop nombreux processus). 
				$tempFilesConditions=ExecCopyQuery::getConditions($this->params,$collectedHours);

				// pour chaque entité, lancer un processus execCopyQuery qui traitera l'entité concernée
				foreach ($tempFilesConditions as $condition) {
					$this->processTempFileCondition($condition);
				}
				
				// attendre la fin des process
				$this->processManager->waitEndOfAllProcess(TRUE,'execCopyQuery');
				
				// TODO : temporaire
				displayInDemon("After ExecCopyQuery", "alert"); printdate();
			}
			
			unlink($filename);
			
		}else{
			$message="Error: file $filename not found";
			sys_log_ast("Critical", "Trending&Aggregation", __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
			displayInDemon($message,'alert');
		}
		//================================
		
		// suppression des variables sérialisés (= de leur fichier de stockage)
		$this->processManager->removeSavedVariables();
			
	}
	
	/**
	 * Fonction qui exécute les requetes de chargement des fichiers csv dans les tables 
	 * temporaires w_astellia (une par todo).
	 */
	protected function execCopyQuerySingleProcessMode($hour)	{ 
		if (Tools::isPerfTraceEnabled()) {Tools::debugTimeExcStart("execCopyQuery");}
		displayInDemon("execCopyQuery:Traitement de l'heure $hour");
		
		// création des tables temporaires
		$this->dbServices->generic_create_table_w($hour,$this->params);
		
		// requête COPY pour remplir les tables temporaires 
		$this->dbServices->copy_into_temp_tables($this->params);

		// suppression des fichiers CSV 
		$this->dbServices->clean_copy_files();
		
		// comme la création des tables se fait avant la vérification qu'il y a des données,
		// s'il n'y a pas de données, il faut supprimer les tables quand même
		$this->dbServices->clean_copy_tables();
		
		if (Tools::isPerfTraceEnabled()) {Tools::debugTimeExcEnd("execCopyQuery");}
	}
	
	/**
	 * 
	 * Met à jour la date de la dernière MAJ de topologie
	 */
	private function updateLastTopologyDate() {
		return $this->dbServices->updateLastTopologyDate();
	}
	
	/**
	*
	* Met à jour la date de la dernière automatic mapping
	*/
	private function updateLastAutomappingDate() {
		return $this->dbServices->updateLastautomappingDate();
	}
	
	/**
	 * 
	 * Méthode qui récupère les infos de topo des diffèrents processus et upload la topologie 
	 * pour chaque famille
	 */
	protected function uploadTopology(){
		// TOPOLOGY 1er axe
		$filename=REP_PHYSIQUE_NIVEAU_0 . "parser/topoSerialized.ser";
		
		if(file_exists($filename)){
			// affichage de logs dans le demon
	        displayInDemon('********** Traitement de la topologie ************ <br>'."\n");
	        
	        // DEBUT - Merge des topologies des différents processus
			$topoArray=array();
			$topoArrayAllHours=array();
			$sLines = file($filename);
			$serializedArray="";
			$nb=count($sLines);
			foreach($sLines as $line) {
				// si on arrive à la fin du tableau sérialisé
				if($line=="END_ARRAY\n") {
					$tempTopoArray=unserialize($serializedArray);
					if($tempTopoArray!=false) {
						//$topoArray=array_merge_recursive($topoArray,$tempTopoArray);
						// pour chaque famille
						foreach ($tempTopoArray as $family => $familyTabHours) {
							
							foreach ($familyTabHours as $hour => $familyTab) {
								// pour chaque élément réseau de base
								foreach ($familyTab as $NEid => $topoInfo) {
									$topoArray[$hour][$family][$NEid]=$topoInfo;
									$topoArrayAllHours[$family][$NEid]=$topoInfo;
								}
							}
						}


					}else{
						$message="Error: unable to deserialise topology variable saved";
						sys_log_ast("Critical", "Trending&Aggregation", __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
						displayInDemon($message,'alert');
					}
					
					$serializedArray="";
				}
				// sinon, on continue à lire la forme sérialisée du tableau
				else $serializedArray.=$line;
			}
			//FIN  - Merge des topologies des différents processus			
			
			// objet de chargement de la topo
			$this->topology = new LoadTopology(strtoupper(get_sys_global_parameters("module")), $this->dbConnection, $this->params);
			
			// ouverture des handles vers les fichiers CSV temporaires de topo			
			$this->topology->openCsvHandles();
			
			// ecrit les données dans les fichiers temporaires de topologie (CTU)
			//if($this->topologyHour=='ALL')
				$this->topology->createFileTopo($topoArrayAllHours);
			//else
			//	$this->topology->createFileTopo($topoArray["{$this->topologyHour}"]);
			// charge les fichiers temporaires de topologie 
			// en utilisant les classes de Topology du CB
			$this->topology->load_files_topo();
			
			// affichage dans le demon
			displayInDemon('********** Fin traitement de la topologie ************ <br>'."\n");
			
			// mise à jour de la topologie terminée.	
			//if($this->topologyHour=='ALL'){
				$hoursList=array_keys($topoArray);
				$hoursListText=implode(',', $hoursList);
				if($hoursListText!='')
					sys_log_ast("Info", "Trending&Aggregation", __T("A_TRACELOG_MODULE_LABEL_COLLECT"), "Topology updated based on all collected hours ($hoursListText)", "support_1", "");
			//}else
			//	sys_log_ast("Info", "Trending&Aggregation", __T("A_TRACELOG_MODULE_LABEL_COLLECT"), "Topology updated based on {$this->topologyHour}", "support_1", "");
			// supression du fichier topoSerialized.ser
			unlink($filename);
		}else{
			$message="Error: unable to find topology file saved ($filename)";
			sys_log_ast("Critical", "Trending&Aggregation", __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
			displayInDemon($message,'alert');
		}
		// on repasse par le même processus pour la topologie 3eme axe
		$filename3rdAxis=REP_PHYSIQUE_NIVEAU_0 . "parser/topo3rdAxisSerialized.ser";
		if(file_exists($filename3rdAxis)){
			// affichage de logs dans le demon
	        displayInDemon('********** Traitement de la topologie 3eme axe************ <br>'."\n");
	        
	        // DEBUT - Merge des topologies des différents processus
			$thirdAxis = true; 
	        $topoArray3rdAxis=array();
			$topoArrayAllHours3rdAxis=array();
			$sLines = file($filename3rdAxis);
			$paramsSerialized=file_get_contents($filename3rdAxis);
			$serializedArray="";
			$nb=count($sLines);
			
			foreach($sLines as $line) {
				// si on arrive à la fin du tableau sérialisé
				if($line=="END_ARRAY\n") {
					$tempTopoArray=unserialize($serializedArray);
					
					if($tempTopoArray!=false) {
						//$topoArray=array_merge_recursive($topoArray,$tempTopoArray);
						// pour chaque famille
						
						foreach ($tempTopoArray as $family => $familyTabHours) {
							foreach ($familyTabHours as $hour => $familyTab) {
								// pour chaque élément réseau de base
								foreach ($familyTab as $NEid => $topoInfo) {
									$topoArray3rdAxis[$hour][$family][$NEid]=$topoInfo;
									$topoArrayAllHours3rdAxis[$family][$NEid]=$topoInfo;
								}
							}
						}


					}else{
						$message="Error: unable to deserialise topology 3rd axis variable saved";
						sys_log_ast("Critical", "Trending&Aggregation", __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
						displayInDemon($message,'alert');
					}
					
					$serializedArray="";
				}
				// sinon, on continue à lire la forme sérialisée du tableau
				else $serializedArray.=$line;
			}
			//FIN  - Merge des topologies des différents processus			
			
			// objet de chargement de la topo
			$this->topology = new LoadTopology(strtoupper(get_sys_global_parameters("module")), $this->dbConnection, $this->params);
			
			// ouverture des handles vers les fichiers CSV temporaires de topo			
			$this->topology->openCsvHandles($thirdAxis);
			
			// ecrit les données dans les fichiers temporaires de topologie (CTU)
			//if($this->topologyHour=='ALL')
				$this->topology->createFileTopo($topoArrayAllHours3rdAxis,$thirdAxis);
			//else
			//	$this->topology->createFileTopo($topoArray["{$this->topologyHour}"]);
			// charge les fichiers temporaires de topologie 
			// en utilisant les classes de Topology du CB
			$this->topology->load_files_topo();
			
			// affichage dans le demon
			displayInDemon('********** Fin traitement de la topologie 3eme axe************ <br>'."\n");
			
			// mise à jour de la topologie terminée.	
			//if($this->topologyHour=='ALL'){
				$hoursList=array_keys($topoArray3rdAxis);
				$hoursListText=implode(',', $hoursList);
				if($hoursListText!='')
					sys_log_ast("Info", "Trending&Aggregation", __T("A_TRACELOG_MODULE_LABEL_COLLECT"), "Topology updated based on all collected hours ($hoursListText)", "support_1", "");
			//}else
			//	sys_log_ast("Info", "Trending&Aggregation", __T("A_TRACELOG_MODULE_LABEL_COLLECT"), "Topology updated based on {$this->topologyHour}", "support_1", "");
			// supression du fichier topoSerialized.ser
					
				copy($filename3rdAxis, REP_PHYSIQUE_NIVEAU_0 . "parser/def/serial.txt");
				unlink($filename3rdAxis);
		}else{
			$message="Error: unable to find 3rd axis topology file saved ($filename)";
			sys_log_ast("Critical", "Trending&Aggregation", __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
			displayInDemon($message,'alert');
		}
		
		
	}
	
	/**
	 * 
	 * Retourne un objet DatabaseServices, éventuellement à partir d'une classe
	 * fille définie côté spécifique (Cf. getDatabaseServicesClassName ci-après)
	 */
	private function getDatabaseServicesObject(){
		$databaseServicesClassName=$this->getDatabaseServicesClassName();
		try {
			$dbServicesClass = new ReflectionClass($databaseServicesClassName);
			$databaseServicesObject = $dbServicesClass->newInstance($this->dbConnection);
			return $databaseServicesObject;
		} catch (ReflectionException $ex) {
			displayInDemon("Erreur au lancement du traitement du parser : " . $ex->getMessage());
			return NULL;
		}
	}
	
	
	/**
	 * 
	 * Côté spécifique : à redénir si besoin.
	 */
	protected function getDatabaseServicesClassName(){
		return "DatabaseServices";
	}
	
	/**
	 * 
	 * Méthode permettant, en début de parsing, un traitement spécifique à un produit
	 */
	protected function onParsingStart(){
		
	}
	
	/**
	 * 
	 * Méthode permettant, en fin de parsing, un traitement spécifique à un produit
	 */
	protected function onParsingEnd(){
		
	}
	
	/**
	 * 
	 * Destructeur
	 */
	function __destruct(){
		// supprimme toutes les variables et les fichiers associés
		$this->processManager->removeSavedVariables();
		
		$filename=REP_PHYSIQUE_NIVEAU_0 . "parser/topoSerialized.ser";
		if(file_exists($filename)) unlink($filename);
		
		$filename=REP_PHYSIQUE_NIVEAU_0 . "parser/paramsSerialized.ser";
		if(file_exists($filename)) unlink($filename);
	}
}

?>