<?php

class ParserHuaBssXml extends Parser {
	
	const PARSER_FILE_NAME="XML";
	
	public function __construct(DataBaseConnection $dbConnection,FileTypeCondition $fileType = null,$single_process_mode=TRUE) {
		
		$conf = new Configuration();
		$this->params = $conf->getParametersList();
		$dBServices=new DatabaseServicesHuaBss($dbConnection);
		parent::__construct($dBServices,$this->params,self::PARSER_FILE_NAME,$fileType,$single_process_mode);
		$this->xmlReader = new XMLReader();
		$this->bscList=array();
		$this->unknownNE=array();
		//la clé du tableau permet de retrouver l'id de la famille
		$this->listFamilies = array("bss","bssgprs","bsstrx");
		$this->specif_enable_trx = get_sys_global_parameters('specif_enable_trx');
		$this->entities = $dBServices->getFamilyByEntity();		
	}

	
	/**
	 * Fonction qui parse le fichier et qui va integrer dans un fichier au format csv les données issues du fichier source
	 *
	 * @param int $id_fichier numero du fichier à traiter
	 * @global text repertoire physique d'installation de l'application
	 */
	public function createCopyBody(FlatFile $flat_file, $topologyHour='ALL') {
		$hour=$flat_file->hour; 
		$this->topologyHour=$topologyHour;
		$this->currentHour=$hour;
		$day = substr($hour, 0, 8); 
		$week = Date::getWeek($day);
		$month = substr($hour, 0, 6);
		$this->time_data = $hour.';'.$day.';'.$week.';'.$month.';'.$flat_file->capture_duration.';'.Parser::$capture_duration_expected.';'.$flat_file->capture_duration;		
		//pour fichier Quadgen
		$pattern="/A([0-9]{4})([0-9]{2})([0-9]{2})\.([0-9]{2})([0-9]{2})[+-]{1}.*_(.*)\.xml/i";
		$this->min="00";
		if (preg_match($pattern, $flat_file->uploaded_flat_file_name, $regs)){
			$this->min=$regs[5];
		}
		
		if (file_exists($flat_file->flat_file_location)) {
			$this->parseFichierParameters($flat_file->flat_file_location,$hour);
		}
		else displayInDemon(__METHOD__ . " ERROR : fichier {$flat_file->flat_file_location} non présent", "alert");

		if (Tools::$debug) {Tools::traceMemoryUsage();}
	}
	
	
	//parsing fichier XML
	private function parseFichierParameters($fichierIn,$hour)
	{	
	
		$this->xmlReader->open($fichierIn,null, 1<<19);
		$process = 0;
        $counterPos=0;
		$measObjLdnList= array();
		
		// pour avoir un aperçu du n° de tour dans la boucle while
		$i=0;
		// déplace le curseur sur le prochain noeud du document XML
		while($this->xmlReader->read())
		{	
			
			if($this->xmlReader->name == "measInfo"){
				if($this->xmlReader->nodeType == XMLReader::ELEMENT)
				{
					
					$object_type="";
					$counterList = array();
					$this->CounterValuesListPerBlock=array();
					$process = 0;
					$counterPos=0;
					$nms_table=trim($this->xmlReader->getAttribute('measInfoId'));
					if($key = array_search($nms_table , $this->entities[0])){
						
						//on récupère l'id de la famille
						$family = $this->entities[1][$key];
						//$this->familyId = array_search($this->familyLabel,$this->listFamilies);
						
						if($family == "bsstrx" && $this->specif_enable_trx == "0"){
							//On connait pas l'entity
							while($this->xmlReader->read()){
								if (($this->xmlReader->name == "measInfo")&&($this->xmlReader->nodeType == XMLReader::END_ELEMENT)) break;
							}
						}

					}else{
						//On connait pas l'entity
						while($this->xmlReader->read()){
							if (($this->xmlReader->name == "measInfo")&&($this->xmlReader->nodeType == XMLReader::END_ELEMENT)) break;
						}
					}
				}
				else if ($this->xmlReader->nodeType == XMLReader::END_ELEMENT)
				{				
					if(count($counters_list_per_todo)!=0){				
					//Si certains compteurs ne sont pas connus on les enregistre dans l'automatic mapping, on ajoute un prefix car on ne peut pas créer de colonne qui commence par un chiffre dans postgres
						$this->addToCountersList($counterList, $nms_table,$id_group_table,"c_".$nms_table);
						foreach ($counters_list_per_todo as $todo => $counters_list) {
							//TODO récuéper dynamiquement la famille à partir du nms_table
							$family=substr($todo, 0,strpos($todo, "_")); 
							
							$param = $this->params->getParamWithTodo($todo);
							$id_group_table=$param->id_group_table;
							
							foreach ($this->CounterValuesListPerBlock as $valueTab) {
								//On connait pas l'entity
								
								$tabTopo=$valueTab[0];
								$values=$valueTab[1];

								$networkElement = $tabTopo[$family]["base_ne"];
								$topoInfo=$tabTopo["topoInfo"];
								
								if(($tabTopo["topoInfo"])!=null){
									if($this->topologyHour=='ALL' || $this->currentHour==$this->topologyHour)
									$param->addTopologyInfo($networkElement, $tabTopo["topoInfo"],$this->currentHour,"rawCase");
								}
								
								// ### Construction des lignes à ajouter dans le fichier SQL temporaire
								// Ajoute les premiers éléments de la ligne dans la chaine de caractères $csv_sql
								$csv_sql .= "{$this->min};{$networkElement};{$this->time_data}";
								
								//$param=$this->params->getParamWithTodo($parser_todo);
								$counters_list=$param->todo[$todo];
								foreach ($counters_list as $counter) {
									$csv_sql .= ';' . $this->getCounterValue($counter,$values,false,false);
								}
								// avec cette boucle, si on a plusieurs edw pour un nms (avec des aggregs diff par ex, pour erlang), ça fonctionne.
								$csv_sql .= "\n";
								// ### Fin de la construction de la ligne

							}
							$parser_fileSqlName = Tools::getCopyFilePath($param->network[0], $todo,$hour);
							//sauvegarde dans le fichier SQL
							$this->fileSauvSql($parser_fileSqlName, $csv_sql);
							unset($csv_sql);
						}
						$measObjLdnList = array();
					}else{
						
						//Si tous les compteurs ne sont pas connus on les enregistre dans l'automatic mapping
						$this->addToCountersList($counterList, $nms_table,$id_group_table,"c_".$nms_table);
						
						
					}
					
				}
			}
			elseif($this->xmlReader->name == "measTypes")
			{
				if($this->xmlReader->nodeType == XMLReader::ELEMENT)
				{
					// on lit la valeur (prochain noeud) dans cette balise
					$this->xmlReader->read();
					$counters = $this->xmlReader->value;
					$new_counters = rtrim($counters);
					$array_counters = explode(" ", $new_counters);
					foreach ($array_counters as $i => $counter) {
						$counterList[]=$counter;
					}  

				}
				
			}
			elseif((($this->xmlReader->name == "measResults")) && $this->xmlReader->nodeType == XMLReader::ELEMENT){
				
					// on lit la valeur (prochain noeud) dans cette balise 
					$this->xmlReader->read();
					$countersValue = rtrim($this->xmlReader->value);
					$currentCptValueList = explode(" ", $countersValue);
					
			}
			
			else if($this->xmlReader->name == "measValue")
			{
				if($this->xmlReader->nodeType == XMLReader::ELEMENT)
				{
				
					$measObjLdn=NULL;
					$measObjLdn=$this->xmlReader->getAttribute('measObjLdn');
					array_push($measObjLdnList,$measObjLdn);
					$tabTopo=$this->get_ne_info($measObjLdn,$family);
					
					if($tabTopo!=null){
						$family=$tabTopo["family"];
						$id_group_table=array_keys($this->listFamilies,$family);
						$id_group_table=$id_group_table[0]+1;
						$counters_list_per_todo = $this->getCptsByNmsTable($nms_table);
						
						if(count($counters_list_per_todo)!=0){
							$process=1;
						}
						
					}else{
						//On connait pas l'entity
						while($this->xmlReader->read()){
							if (($this->xmlReader->name == "measValue")&&($this->xmlReader->nodeType == XMLReader::END_ELEMENT)) break;
						}
					}

						
					// tableau pour stocker les valeurs du bloc <mv>...</mv> courant
					$countersValue = array();
					
				}
				elseif ($this->xmlReader->nodeType == XMLReader::END_ELEMENT)
				{
					if($process==1){					
						// tableau pour stocker les valeurs du bloc <mv>...</mv> courant
						$countersValue = array();
						$values=array();
						// On charge les données du fichiers sources dans le tableau $values
		
						for ($i = 0; $i <  count($counterList); $i++) {
							$currentValue = $currentCptValueList[$i];
							
							$values[strtolower($counterList[$i])] = $currentValue;
						}

						$this->CounterValuesListPerBlock[]=array($tabTopo,$values);
						//remis à zero
						$currentCptValueList=array();
					}
					
				}
			}
			
			else
			{
				//echo "cas de balise inconnue : $this->xmlReader->name <br>";
			}

		}				
		$this->xmlReader->close();
		return true;
	}

	

/**
	 * Fonction qui va sauvegarder les données $csv_sql dans un fichiers $filename
	 *
	 * @param string nom du fichier à utiliser pour la sauvegarde
	 * @param string texte à insérer dans le fichier $filename
	 * @param string $copy_header requete SQL qui sera executee, uniquement utile pour affichage debug
	 */
	private function fileSauvSql($filename, $csv_sql) {
		//on ouvre le fichier en append
		if (!$handle = fopen($filename, 'at')) {
			displayInDemon(__METHOD__ . " ERROR : impossible d'ouvrir le fichier (".$filename.")", "alert");
		}
		else {
			//on écrit les données
			flock($handle, LOCK_EX);
			if (fwrite($handle, $csv_sql) === FALSE)
				displayInDemon(__METHOD__ . " ERROR : impossible d'écrire dans le fichier (".$filename.")<br>\n", "alert");
			flock($handle, LOCK_UN);
			fclose($handle);
		}
	}
	
	
	public function get_ne_info($moid,$family) {
		//cell example: 		measObjLdn="MPU02BSC/GCELL:LABEL=5628_2GSB_BR_V_NAMINGE_P07_NAM_1, CellIndex=250, CGI=643042713DBD9" => cellid = CGI_CellIndex
		//$matches:
		//						0	=>	MPU02BSC/GCELL:LABEL=5628_2GSB_BR_V_NAMINGE_P07_NAM_1, CellIndex=250, CGI=643042713DBD9
		//						1	=>	MPU02BSC
		//						2	=>	5628_2GSB_BR_V_NAMINGE_P07_NAM_1
		//						3	=>	250
		//						4	=>	643042713DBD9
		
		//bypass cell exmaple: 	measObjLdn="MPU02BSC/BSC:MPU02BSC" => cell_id virtual_MPU02BSC
		//						measObjLdn="MPU02BSC/BSCRPT:MPU02BSC" => cell_id virtual_MPU02BSC
		//						0	=>	MPU02BSC/BSC:MPU02BSC
		//						1	=>	MPU02BSC
		//						2	=>	BSC
		//						3	=>	MPU02BSC
	
		// trx example: 		measObjLdn="MPU02BSC/Cell:LABEL=5628_2GSB_BR_V_NAMINGE_P07_NAM_1, CellIndex=250, CGI=643042713DBD9/TRX:TRX Index=2099, TRX Name=5628_2GSB_SI_V_NAMINGE_P07_NAM_02" => trxid = CGI_CellIndex_Index
		//						0	=>	MPU02BSC/Cell:LABEL=5628_2GSB_BR_V_NAMINGE_P07_NAM_1, CellIndex=250, CGI=643042713DBD9/TRX:TRX Index=2099, TRX Name=5628_2GSB_SI_V_NAMINGE_P07_NAM_02
		//						1	=>	MPU02BSC
		//						2	=>	5628_2GSB_BR_V_NAMINGE_P07_NAM_1
		//						3	=>	250
		//						4	=>	643042713DBD9
		//						5	=>	2099
		//						6	=>	5628_2GSB_SI_V_NAMINGE_P07_NAM_02
		
		$cellPattern="/(.*)\/GCELL:LABEL=(.*)\s*,\s*CellIndex=(.*)\s*,\s*CGI=(.*)/i";
		$bscPattern="/(.*)\/(BSC).*:(.*)/i";
		$trxPattern="/(.*)\/Cell:LABEL=(.*)\s*,\s*CellIndex=(.*)\s*,\s*CGI=(.*).*\/TRX.*Index=(.*)\s*,\s*TRX Name=(.*)/i";
		$topoTab=array();

		if(preg_match($cellPattern, $moid, $matches)) {
			$cell = trim($matches[4])."_".trim($matches[3]);
			$cell_label = trim($matches[2]);
			$bsc = trim($matches[1]);  
			//En fonction de la famille, le tableau de topo à construire n'est pas le même
			switch($family){
				case "bss" : 
					$topoInfo["Cell"]=$cell;
					$topoInfo["Cell label"]=$cell_label;
					$topoInfo["BSC"]=$bsc;
						
					break;
				case "bssgprs" :
					$topoInfo["Cell"]=$cell;
					$topoInfo["Cell label"]=$cell_label;
					$topoInfo["PCU"]=$bsc;
							
					break;
				case "bsstrx" :
					displayInDemon(__METHOD__ . " ERROR : measObjLdn Réservé pour famille bss et gprs<br>\n", "alert");	
					break;
			}	
		}
		//bypass niveau cell
		else if(preg_match($bscPattern, $moid, $matches)) {
			
			$bsc = trim($matches[3]);  
			$cell = "virtual_".$bsc;
						
			switch($family){
				case "bss" : 
					$topoInfo["Cell"]=$cell;
					$topoInfo["BSC"]=$bsc;
					break;
				case "bssgprs" :
					$topoInfo["Cell"]=$cell;
					$topoInfo["PCU"]=$bsc;
							
					break;
				case "bsstrx" :
					displayInDemon(__METHOD__ . " ERROR : measObjLdn Réservé pour famille bss et gprs<br>\n", "alert");	
					break;
			}
		}else if(preg_match($trxPattern, $moid, $matches)){

			$cell = trim($matches[4])."_".trim($matches[3]);
			$cell_label = trim($matches[2]);
			$trx = $cell."_".trim($matches[5]);
			$trx_label = trim($matches[6]);
			
			switch($family){
				case "bss" : 
					displayInDemon(__METHOD__ . " ERROR : measObjLdn Réservé pour famille trx <br>\n", "alert");	
					break;
				case "bssgprs" :
					displayInDemon(__METHOD__ . " ERROR : measObjLdn Réservé pour famille trx <br>\n", "alert");	
					break;
				case "bsstrx" :
					$topoInfo["Cell"]=$cell;
					$topoInfo["Cell label"]=$cell_label;
					$topoInfo["TRX"]=$trx;
					$topoInfo["TRX label"]=$trx_label;
					break;
			}
		}else{
			return null;
		}
		
		$topoTab["family"]=$family;
		$topoTab["topoInfo"]=$topoInfo;
		if($family == "bssgprs" || $family == "bss"){
			$topoTab[$family]["base_ne"]=$cell;
			
		}else{
			$topoTab[$family]["base_ne"]=$trx;
		}
		
		return $topoTab;
	}
	
	
	/**
	*
	* Enter description here ...
	*/
	public static function  getConditionProvider($dbServices){
		return new XMLConditionProvider($dbServices);
	}

}
?>