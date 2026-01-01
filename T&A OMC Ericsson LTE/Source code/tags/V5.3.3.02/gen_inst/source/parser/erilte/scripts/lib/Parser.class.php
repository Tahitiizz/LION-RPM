<?php
/**
 * Fichier qui contient des fonctions génériques utilisées dans les classes propres à chaque type de fichier à traiter
 * 
 * @package Parser library
 *
 */

abstract class Parser {
	
	public $parserFileName;
	/**
	 * @var type de ficher à traiter
	 */
	protected  $fileType;
	/**
	 * 
	 * Liste des paramètres statiques définis pour chaque famille
	 * @var ParametersList
	 */
	protected $params;
	/**
	 * 
	 * Valeur du paramètre global du contexte produit
	 * @var String
	 */
	protected $default_value_from_sfr;
	/**
	 * 
	 * Valeur du paramètre global du contexte produit définissant la valeur par défaut en cas de valeur non numérique d'un compteur
	 * @var String
	 */
	private $default_value_from_sfr_non_numeric_value;
	/**
	 * 
	 * Permet l'accès aux fonctions de requête vers la base de données
	 * @var DatabaseServices
	 */
	public $dbServices;
	
	/**
	 * 
	 * Structure de données comptenant les compteurs présents dans les fichiers sources parsés (id_group_table, nms_table, counters list)
	 * @var array
	 */
	private $listOfCountersInSourceFiles;
	
	/**
	 * 
	 * Stockage de la valeur de query_ri
	 * @var String
	 */
	public static $query_ri;
	/**
	 * 
	 * Stockage de la valeur de aggreg_net_ri
	 * @var String
	 */
	public static $aggreg_net_ri;
	/**
	 * 
	 * Stockage de la valeur de time_coef
	 * @var String
	 */
	public static $time_coef;
	/**
	 * 
	 * Stockage de la valeur de capture_duration_expected
	 * @var String
	 */
	public static $capture_duration_expected;
	
	/**
	 * 
	 * Mono ou multi processus
	 * @var boolean
	 */
	protected $retrieve_single_process;
		
	public $processManager;


	/**
	 * 
	 * Constructeur
	 * @param $dbServices
	 * @param $parameterList
	 * @param $parserFileName
	 * @param $fileType
	 * @param $single_process_mode
	 */
	public function Parser(DatabaseServices $dbServices, ParametersList $parameterList,$parserFileName,FileTypeCondition $fileType=NULL,$single_process_mode=TRUE)
	{
		$this->dbServices=$dbServices;
		$this->params=$parameterList;
		$this->parserFileName=$parserFileName;
		$this->fileType = $fileType;
		$this->initiateParam();
		$global_parameters = edw_LoadParams();
		
// 		//mono ou multi processus: si le parametre n'est pas défini (NULL) ou est à 1 retrieve_single_process=TRUE. Sinon False
// 		if(isset($global_parameters['retrieve_single_process']) )
// 			$this->retrieve_single_process=$global_parameters['retrieve_single_process']==1?TRUE:FALSE;
// 		else $this->retrieve_single_process=false;
		
		//mono ou multi processus: si le parametre n'est pas défini (NULL) ou est à 1 retrieve_single_process=TRUE. Sinon False
		$this->retrieve_single_process=$single_process_mode;
		
		Parser::$capture_duration_expected = $global_parameters["capture_duration"];
		// Si la valeur n'existe pas dans le contexte, la valeur par défaut pour ce paramètre est 1
		$this->default_value_from_sfr = ($global_parameters["default_value_from_sfr"] == null) ? 1 : $global_parameters["default_value_from_sfr"];
		//valeur à 0 par défaut (ex : si le paramètre n'existe pas), i.e. "NULL when value is non numeric"
		$this->default_value_from_sfr_non_numeric_value = 0;
		if(isset($global_parameters["default_value_from_sfr_non_numeric_value"])) {
			$default_value_from_sfr_non_numeric_value = $global_parameters["default_value_from_sfr_non_numeric_value"];
			if($default_value_from_sfr_non_numeric_value == 0 || $default_value_from_sfr_non_numeric_value == 1) {
				$this->default_value_from_sfr_non_numeric_value = $default_value_from_sfr_non_numeric_value;
			}
		}
		$this->listOfCountersInSourceFiles = array();
		$this->processManager=ProcessManager::getInstance();
	}
	

	/**
	 * 
	 * Renvoie la liste des heures où des fichiers ont été collectés
	 */
	public function getHoursCollected() {
		$hours = $this->dbServices->getHoursCollected($this->fileType);
		return $hours;
	}
	

	
	/**
	 * 
	 * Récupère la valeur ou valeur par défaut du compteur $counterName
	 * @param Counter $counter Objet Counter qui définit le compteur
	 * @param array $data Liste des valeurs des compteurs
	 * @param bool $globalCounter permet de savoir si le compteur est un compteur explicit false par défaut
	 * @param bool $explicit permet de savoir si le compteur a des valeurs explicite false par défaut
	 * @return String $value la valeur du compteur $counterName
	 */
	protected function getCounterValue(Counter $counter, $data, $globalCounter = false,$explicit = false) {
		$val = null;
        // cas des compteurs déclinés 
        // ex : nms_field_name vaut NUMDEST_ANSWERED_CALLS@@DEST_DIR_ID=1&&DEST_TYPE_ID=10
        // dans ce cas on prend la valeur de NUMDEST_ANSWERED_CALLS si DEST_DIR_ID=1 ET DEST_TYPE_ID=10
        if (strpos($counter->nms_field_name[0],"@@")){
            $array_declined = explode("@@",strtolower($counter->nms_field_name[0]));
            if (isset($data[$array_declined[0]])) {
                $values_declined = explode("&&",$array_declined[1]);
                $stop=0;
                while($stop==0){
                    foreach ($values_declined as $elt){
                        $tab=explode("=",$elt);
                        if($data[$tab[0]]!=$tab[1])
                            $stop=1;
                    }
                    break;
                }
                if ($stop==0)
                    $val = $data[$array_declined[0]];
            }
        }
        // compteurs non déclinés
        foreach ($counter->nms_field_name as $nms_field_name) {     	
        	if(isset($data[$nms_field_name])){
        		$rawVal = $data[$nms_field_name];
        		//Cas d'un Multivalued counter
        		if($counter->flat_file_position !== null && $counter->flat_file_position != ''){
					if($counter->flat_file_position >=0){
						if($explicit == true){
							$position = intval($counter->flat_file_position);
							$arrayValue = explode(",", $rawVal);
							//on récupère la valeur du compteur à la bonne position dans le tableau
							$val = $arrayValue[$position];
						}else{
							$position = intval($counter->flat_file_position);
							$rawVal = substr($rawVal,2,strlen($rawVal));
							$arrayValue = explode(',', $rawVal);
							$valPreSum = array();
							$indexPreSum = array();
							foreach ($arrayValue as $key=>$value) {
									if($key % 2 == 1){
										array_push($valPreSum,$arrayValue[$key]);
									}else{
										array_push($indexPreSum,$arrayValue[$key]-1);
									}  
							}
							// comme les index trouvés dans les valeur condensées commence à 1 on utilisera position +1 
							if(in_array($position,$indexPreSum)){
								$key = array_search($position, $indexPreSum);
								$val = $valPreSum[$key];
							}else{
								$val = '0';
							}
						}
					}else{
						if($globalCounter === true){
							//cas d'un global counter condensé : on saute la 1ère valeur et on fait la somme d un nombre sur 2 
							//ex: <r>2,2,15,6,25</r> ici le total sera égale à 15+25 = 40
							if($counter->flat_file_position == -2){
			        			if($rawVal =="0" || $rawVal ==""){
			        				$val = '0';
			        			}else{
			        				$rawVal = substr($rawVal,2,strlen($rawVal));
				        			$arrayValue = explode(',', $rawVal);
				        			$valPreSum = array();
									foreach ($arrayValue as $key=>$value) {
										if($key % 2 == 1){
											array_push($valPreSum,$arrayValue[$key]);
										}   
									}
									$val = array_sum($valPreSum);
			        			}  	
				        	}
				        	//cas d'un global counter explicte on fait un summ de toute les valeurs
				        	else if($counter->flat_file_position == -1){
				        		$arrayValue = explode(",", $rawVal);
								$val = array_sum($arrayValue);
			        		}
						}
					}
				}else{
						$val = $data[$nms_field_name];
				}
				if(!(is_numeric($val))){
					if (preg_match('/^([0-9]+),(([0-9]+))$/', $val))
						$val = floatval(str_replace(',', '.', $val));
					else
						return ($this->default_value_from_sfr_non_numeric_value == 0 ? "" : $counter->default_value);
				}
				
        	}	
        }
       
        return ($val === null && $this->default_value_from_sfr == 1 ? $counter->default_value : $val);
        
    }


	/**
	 * 
	 * Renvoie la liste des types des champs SQL des compteurs
	 * @param array $header Tableau contenant les éléments de l'en-tête du fichier source

	 */
    /**
     * 
     * Enter description here ...
     * @param $header tableau de la liste des compteurs à chercher
     * @param $id_group_table (optionnel) à renseigner si connu plour améliorer les performances
     * @return array Tableau des objets Counter présents, par todo, dans le fichier source
     */
	protected function getCptsInFile($header,$id_group_table=null) {
		$edw_ordered_list = array();
		foreach ($header as $value) {
			if (strncmp($value, "capture_duration", 16) === 0) { continue; }
			$counters = $this->params->getCounterFromFile($value,$id_group_table);	
			// On ne garde que les compteurs actifs		
			/*if (isset($counter) && $counter[1]->on_off == 1) {
				$edw_ordered_list[$counter[0]] = $counter[1];
			}*/
			if (isset($counters)) {
				foreach ($counters as $counter) {
					if (isset($counters)) {
						foreach ($counters as $counter) {
							if($counter->on_off == 1){
								$todo=Tools::getTodoString($counter->family, $counter->nms_table);
								$edw_ordered_list[$todo][] = $counter;
							}
							
						}
					}
					
				}
			}
		}
		return $edw_ordered_list;
	}
	
	protected function getCptsByNmsTable($nms_table){
		return $this->params->getCountersByNmsTable($nms_table);
	}

	
	/**
	 * 
	 * Purge de la table sys_flat_file_uploaded_list pour l'heure $hour
	 * @param String $hour Heure des fichiers collectés
	 */
	public function cleanFlatFileUploadedList() {
		$this->dbServices->clean_flat_file_uploaded_list($this->collectedFlatfiles);
	}

	/**
	 * 
	 * Ajouter les compteurs trouvés dans le fichiers sources dans une structure de données
	 * @param array $counters_in_source_file
	 * @param string $nms_table
	 * @param int $id_group_table
	 * @param string $prefix_counter
	 */
	public function addToCountersList($counters_in_source_file, $nms_table, $id_group_table, $prefix_counter = null){
		//on récupère le nms_table à partir du todo
		//nms_table insensible à la casse
		$nms_table=strtolower($nms_table);
		foreach ($counters_in_source_file as $counter){
			//construction de la structure de donnée
			if(! isset($this->listOfCountersInSourceFiles[$id_group_table])) $this->listOfCountersInSourceFiles[$id_group_table] = array();
			if(! isset($this->listOfCountersInSourceFiles[$id_group_table][$nms_table])) $this->listOfCountersInSourceFiles[$id_group_table][$nms_table] = array();
			$this->listOfCountersInSourceFiles[$id_group_table][$nms_table][$counter]=strtolower($prefix_counter);
		}
	}
	
	/**
	 * Méthode appellée à la fin du retreive 
	 * Regarde s'il y a des nouveaux compteurs et procède à l'insertion de ceux-ci dans sys_field_reference_all (en se basant sur $listOfCountersInSourceFiles)
	 * 
	 */
	public function addDynamicCountersToSysFieldReferenceAll(){
		$this->dbServices->update_dynamic_counter_list($this->listOfCountersInSourceFiles);
	}
	
	/**
	 * 
	 * Parse le fichier source fourni en paramètre et crée un fichier CSV destiné à la 
	 * commande SQL "COPY" (création des tables temporaires)
	 * @param FlatFile $flat_file Fichier collecté
	 * @param booleen $topologyHour Heure sur laquelle doit se baser l'upload de 
	 * topo (mode croisière de la CTU). ALL = toutes les heures (mode full de la CTU).
	 */
	abstract protected function createCopyBody(FlatFile $flat_file, $topologyHour='ALL');
		

	
	
	/**
	 * 
	 * Retourne la propriété $this->params
	 * @return array Liste d'objets Parameters
	 */
	public function getParams() {
		return $this->params;
	}
	
	
	/**
	 * 
	 * Renvoie un tableau contenant, par familles, les info de topologie
	 */
	public function getTopoCellsArray() {
		$topoCellsArray = array();
		foreach ($this->params as $param) {
			if (isset($param->topoCellsArray) && is_array($param->topoCellsArray)) {
				$topoCellsArray[$param->family] = $param->topoCellsArray;
			}else if (isset($param->topo3rdAxisArray) && is_array($param->topo3rdAxisArray)){
				$topoCellsArray[$param->family] = $param->$topo3rdAxisArray;
			}
			else {
				$topoCellsArray[$param->family] = array();
			}
		}
		return $topoCellsArray;
	}
	
/**
	 * 
	 * Renvoie un tableau contenant, par familles, les info de topologie 3eme axe
	 */
	public function getTopo3rdAxisArray() {
		$topo3rdAxisArray = array();
		foreach ($this->params as $param) {
			if (isset($param->topo3rdAxisArray) && is_array($param->topo3rdAxisArray)){
				$topo3rdAxisArray[$param->family] = $param->topo3rdAxisArray;
			}
			else {
				$topo3rdAxisArray[$param->family] = array();
			}
		}
		return $topo3rdAxisArray;
	}
	
	/**
	 * 
	 * Cette méthode est exécutée à la fin du parsing des fichiers vérifiant la condition.
	 * Elle est est disponible pour développer si besoin des actions côté spécifique.
	 */
	protected function endOfParsingPerCondition(){
		//empty
	}

	
	/**
	 * 
	 * Lance les traitements principaux du process Retrieve
	 * $topologyHour : heure pour laquelle l'upload de topo doit avoir lieu (ou "ALL" 
	 * si la CTU est en mode "full").
	 */
	public function process($topologyHour) {

		//existe-t-il des fichiers de ce type de parser et la condition "$this->fileType"
		$this->collectedFlatfiles = $this->dbServices->getFiles($this->fileType);
		
		if(!$this->collectedFlatfiles->valid()) {echo "No file for condition {$this->fileType->getDBCondition()}" ;return;}			

		// début du traitement des fichiers collectés sélectionnés via la condition "$this->fileType"
		foreach ($this->collectedFlatfiles as $flatFile) {
			// flat_file_name : nom du type de fichier (ex : ASN1 pour Ericsson BSS)
			// uploaded_flat_file_name : nom du fichier source précédé d'un ID de la connexion 
			// (ex : local_home_mde_eribss_ref_flat_file_20111024.1800_C20111024.1800-20111024.1900_CUDRBS1:1000)
			displayInDemon('********** groupe de fichiers '.$flatFile->flat_file_name.' : '.$flatFile->uploaded_flat_file_name.' ************');
			
			// parsing du fichier source et création d'un fichier CSV destiné à la 
	 		// commande SQL "COPY" (création des tables temporaires)
			if (Tools::isPerfTraceEnabled()) {Tools::debugTimeExcStart("createCopyBody");}
			// méthode spécifique à chaque parser
			$this->createCopyBody($flatFile, $topologyHour);
			if (Tools::isPerfTraceEnabled()) {Tools::debugTimeExcEnd("createCopyBody");}
		}
		
		//fin de parsing des fichiers vérifiants la condition
		$this->endOfParsingPerCondition();

		//supprime les fichiers de l'heure courante.
		$this->cleanFlatFileUploadedList();

		// date du dernier automatic mapping 
		$automapping_last_update_date = get_sys_global_parameters("automapping_last_update_date");
		
		//automatic mapping only once a day
		if($automapping_last_update_date != date("Ymd") || $automapping_last_update_date==0){
			// MAJ du paramètre global 'automapping_last_update_date' : la méthode updateLastAutomappingDate() 
			// est appelée dans le LoadData à la fin du parsing.
			$this->addDynamicCountersToSysFieldReferenceAll();
		}
		
		//serialisation de la topologie
		$topologyArray=$this->getTopoCellsArray();
		$topology3rdAxis=$this->getTopo3rdAxisArray();
		$serialisedTopo=serialize($topologyArray);		
		$filename=REP_PHYSIQUE_NIVEAU_0 . "parser/topoSerialized.ser";
		// option "a+" : Open for reading and writing; place the file pointer at the end of the file. If the file does not exist, attempt to create it.
		$fileHandle=fopen($filename,'a+');
		if($fileHandle!=false){
			
			// Verrouillage du fichier par le processus, bloque l'écriture jusqu'à libération 
			// du fichier par un autre processus.
			// => doit fonctionner puisque nos processus enfants sont des processus "lourds", et donc
			// vus comme des processus différents par le système.
			// LOCK_EX : met en place un verrou exclusif (écriture) => the CALL WILL BLOCK UNTIL ALL OTHER LOCKS have been released.
			flock($fileHandle, LOCK_EX);
			fwrite($fileHandle, $serialisedTopo);
			fwrite($fileHandle, "\nEND_ARRAY\n"); // END_ARRAY : marqueur de fin d'objet serialisé
			// LOCK_UN : libérer le verrou (qu'il soit partagé ou exclusif)
			flock($fileHandle, LOCK_UN);
			fclose($fileHandle);		
		}else{
			$message="ERROR: topologie not saved for this process. file ($filename)";
			sys_log_ast("Critical", "Trending&Aggregation", __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
			displayInDemon($message,'alert');
		}
		
		if(!empty($topology3rdAxis)){
			$serialisedTopo3rdAxis=serialize($topology3rdAxis);		
			$filename=REP_PHYSIQUE_NIVEAU_0 . "parser/topo3rdAxisSerialized.ser";
			// option "a+" : Open for reading and writing; place the file pointer at the end of the file. If the file does not exist, attempt to create it.
			$fileHandle3rdAxis=fopen($filename,'a+');
			$paramsSerialized=file_get_contents($filename);
			
			if($fileHandle3rdAxis!=false){
				
				// Verrouillage du fichier par le processus, bloque l'écriture jusqu'à libération 
				// du fichier par un autre processus.
				// => doit fonctionner puisque nos processus enfants sont des processus "lourds", et donc
				// vus comme des processus différents par le système.
				// LOCK_EX : met en place un verrou exclusif (écriture) => the CALL WILL BLOCK UNTIL ALL OTHER LOCKS have been released.
				flock($fileHandle3rdAxis, LOCK_EX);
				fwrite($fileHandle3rdAxis, $serialisedTopo3rdAxis);
				fwrite($fileHandle3rdAxis, "\nEND_ARRAY\n"); // END_ARRAY : marqueur de fin d'objet serialisé
				// LOCK_UN : libérer le verrou (qu'il soit partagé ou exclusif)
				flock($fileHandle3rdAxis, LOCK_UN);
				fclose($fileHandle3rdAxis);		
			}else{
				$message="ERROR: topologie for 3rd axis not saved for this process. file ($filename)";
				sys_log_ast("Critical", "Trending&Aggregation", __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
				displayInDemon($message,'alert');
			}
		}
		
	}
	
	/**
	 * 
	 * Initialise des propriétés d'objet ParameterList
	 * @param array $family2Param Tableau associatif pour les paramètres field, specific_field, group_table et network
	 * 
	 */
	protected function initiateParam() {

		$filename=REP_PHYSIQUE_NIVEAU_0 . "parser/paramsSerialized.ser";
		// si aucun processus n'a eu le temps de finir de créer ce fichier
		// (plusieurs processus peuvent rentrer dans ce "if" : les "file_put_contents"
		// ci-dessous vont alors s'écraser les uns les autres)
		if(!file_exists($filename)){

			//ON PREPARE LA LISTE DES FICHIERS A TRAITER
			// on liste les compteurs *** activés ***
			$counters = $this->dbServices->getAllCounters($this->params);
			
			// on liste les todo à traiter
			$tabTodo = array();
			foreach($counters as $counter) {
				$tabTodo[] = Tools::getTodoString($counter->family, $counter->nms_table);
			}
			// on dédoublonne $tabTodo
			$tabTodo = array_unique($tabTodo);
			
			// On initialise la liste des todo de la structure $this->params
			// Chaque $this->params->todo contient la liste de tous les objets Counter actifs définis dans la table sys_field_reference
			foreach($tabTodo as $todo) {
				if (preg_match("/^([[:alnum:]]+)_/", $todo, $regs) ) {
					$family = $regs[1];
					$nmsTable = substr($todo, strpos($todo, '_') + 1);
					$todoCounters = $counters->getWithTodo($todo);
					$param = $this->params->getWithFamily($family);
					$param->todo[$todo] = $todoCounters;
				}
				else {
					displayInDemon(__METHOD__ . " ERROR, $todo n'est pas de la forme attendue", "alert");
				}
			}
			// renseigne les listes d'éléments réseaux desactivé
			// (les $param->deactivated_NE)
			$this->dbServices->deactivatedNEPerFamily($this->params);
			
			//serialisation et sauvegarde
			$paramsSerialized=serialize($this->params);
			
			// stockage du paramétrage dans un fichier avec verrou Exclusif.
			// 		file_put_contents : If filename does not exist, the file is created. Otherwise, the existing file is overwritten, unless the FILE_APPEND flag is set.
			//      LOCK_EX means (EXCLUSIVE LOCK) : only a single process may possess an exclusive lock to a given file at a time. 
			$paramsAreStored=file_put_contents($filename,$paramsSerialized,LOCK_EX);
			if($paramsAreStored==false) {
				$message="Error: Unable to create parameter file ($filename)";
				sys_log_ast("Critical", "Trending&Aggregation", __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
				displayInDemon($message,'alert');
			}
			
		}
		// Sinon, le fichier existe et on l'utilise (gain en perf!).
		// Il est complet (= non corrompu) car l'opération
		// de création de ce fichier est atomique (voir "file_put_contents" ci-dessus). 
		else{
			$paramsSerialized="";
			$handle=fopen($filename,'rt');
			if ($handle) {
				//verrou en lecture partagée (bloque jusqu'à libération du verrou exlusif s'il existe)
				flock($handle, LOCK_SH);
			    while (!feof($handle)) {
			        $paramsSerialized .= fgets($handle);
			    }
			    flock($handle, LOCK_UN);//dévérouillage
			    fclose($handle);
			}else{
				$message="Error: Unable to open parameter file ($filename)";
				sys_log_ast("Critical", "Trending&Aggregation", __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
				displayInDemon($message,'alert');
			}			
			$this->params=unserialize($paramsSerialized);
			if(($this->params==false)||($this->params=='')){
				$message="Error: Unable to unserialize parameter file ($filename)";
				sys_log_ast("Critical", "Trending&Aggregation", __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
				displayInDemon($message,'alert');
			}
		}

	}
	
	

	/**
	 * 
	 * Retourne le ConditionProvider à utiliser pour ce parser.
	 * A redéfinir côté spécifique si besoin.
	 * @param unknown_type $dbServices
	 */
	public static function getConditionProvider($dbServices){
		return new ConditionProvider($dbServices);
	}
	


}
?>