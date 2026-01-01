<?php

class ParserHuaRanXml extends Parser {
	
	const PARSER_FILE_NAME="XML";
	
	public function __construct(DataBaseConnection $dbConnection,FileTypeCondition $fileType = null,$single_process_mode=TRUE) {
		
		$conf = new Configuration();
		$this->params = $conf->getParametersList();
		$dBServices=new DatabaseServicesHuaRan($dbConnection);
		parent::__construct($dBServices,$this->params,self::PARSER_FILE_NAME,$fileType,$single_process_mode);
		$this->xmlReader = new XMLReader();
		$this->bscList=array();
		$this->unknownNE=array();
		//$this->entities = $dBServices->getFamilyByEntity();			
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
		//displayInDemon(__METHOD__ . " INFO : Starting parsing of file.<br>\n", "alert");
		$this->xmlReader->open($fichierIn,null, 1<<19);
		$process = 0;
        $counterPos=0;
		$measObjLdnList= array();
		$id_group_table=1;
		$family="cellb";
		$test = TRUE;
		$cc = 0;
		// pour avoir un aperçu du n° de tour dans la boucle while
		$i=0;
		$loop=0;
		// déplace le curseur sur le prochain noeud du document XML
		while($this->xmlReader->read())
		{	
			$loop = $loop+1;
			//displayInDemon(__METHOD__ . " INFO : loop ".$loop."<br>\n", "alert");
			if($this->xmlReader->name == "measInfo"){
				//displayInDemon(__METHOD__ . " INFO : Found measInfo", "alert");
				if($this->xmlReader->nodeType == XMLReader::ELEMENT)
				{
					//displayInDemon(__METHOD__ . " INFO : Found measInfo open", "alert");
					$object_type="";
					$counterList = array();
					$this->CounterValuesListPerBlock=array();
					$process = 0;
					$counterPos=0;
					
					//$nms_table=trim($this->xmlReader->getAttribute('measInfoId')); //on get le fileID
					
				}
				else if ($this->xmlReader->nodeType == XMLReader::END_ELEMENT)
				{	
					//displayInDemon(__METHOD__ . " INFO : Found measInfo end", "alert");
					if(count($counters_list_per_todo)!=0){				
					//Si certains compteurs ne sont pas connus on les enregistre dans l'automatic mapping, on ajoute un prefix car on ne peut pas créer de colonne qui commence par un chiffre dans postgres

						$this->addToCountersList($counterList, "XML",$id_group_table,"c_");
						foreach ($counters_list_per_todo as $todo => $counters_list) {
							//TODO récuéper dynamiquement la famille à partir du nms_table
							
							$param = $this->params->getParamWithTodo($todo);
														
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
									//displayInDemon(__METHOD__ . " INFO : HERE ", "alert");
									//displayInDemon(__METHOD__ . " INFO : Found counter : ".$counter." end of counter Values : ".$values." ", "alert");
									
									$csv_sql .= ';' . $this->getCounterValue($counter,$values,false,false);
								}
								//displayInDemon(__METHOD__ . " INFO : THERE ", "alert");
								// avec cette boucle, si on a plusieurs edw pour un nms (avec des aggregs diff par ex, pour erlang), ça fonctionne.
								$csv_sql .= "\n";
								//displayInDemon(__METHOD__ . " INFO : Write $csv_sql", "alert");
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
						$this->addToCountersList($counterList, "XML",$id_group_table,"c_");
						
						
					}
					
				}
			}
			elseif($this->xmlReader->name == "measTypes")
			{
				//displayInDemon(__METHOD__ . " INFO : Found measTypes", "alert");
				if($this->xmlReader->nodeType == XMLReader::ELEMENT)
				{
					//displayInDemon(__METHOD__ . " INFO : Found measTypes open", "alert");
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
					//displayInDemon(__METHOD__ . " INFO : Found measResults open", "alert");
					// on lit la valeur (prochain noeud) dans cette balise 
					$this->xmlReader->read();
					$countersValue = rtrim($this->xmlReader->value);
					$currentCptValueList = explode(" ", $countersValue);
					
					
			}
			
			else if($this->xmlReader->name == "measValue")
			{
				//displayInDemon(__METHOD__ . " INFO : Found measValue", "alert");
				if($this->xmlReader->nodeType == XMLReader::ELEMENT)
				{
					//displayInDemon(__METHOD__ . " INFO : Found measValue open", "alert");
					$measObjLdn=NULL;
					$measObjLdn=$this->xmlReader->getAttribute('measObjLdn');
															
					array_push($measObjLdnList,$measObjLdn);
					$tabTopo=$this->get_ne_info($measObjLdn,$family);
					
					if($tabTopo!=null){
						
						$counters_list_per_todo = $this->getCptsByNmsTable("XML");
						//$counters_list_per_todo = $this->getCptsInFile($counterList);
																		
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
					//displayInDemon(__METHOD__ . " INFO : Found measValue end", "alert");
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
			//displayInDemon(__METHOD__ . " ERROR : impossible d'ouvrir le fichier (".$filename.")", "alert");
		}
		else {
			//on écrit les données
			flock($handle, LOCK_EX);
			if (fwrite($handle, $csv_sql) === FALSE)
				//displayInDemon(__METHOD__ . " ERROR : impossible d'écrire dans le fichier (".$filename.")<br>\n", "alert");
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
	
		
		
		$cellPattern="/(.*)\/BSC.*UCell:Label=(.*)\s*,\s*CellID=(.*)/i";
		$rncPattern="/(.*)\/BSC.*UMTSFunction:(.*)/i";
		$topoTab=array();
		
		if(preg_match($cellPattern, $moid, $matches)) {
			$cell = trim($matches[1])."_".trim($matches[3]);
			$cell_label = trim($matches[2]);
			$rnc = trim($matches[1]);  
			//displayInDemon(__METHOD__ . " INFO : Found cell pattern.<br>\n", "alert");
			$topoInfo["Cell"]=$cell;
			$topoInfo["Cell label"]=$cell_label;
			$topoInfo["RNC"]=$rnc;
			$topoTab["family"]="cellb";
			$topoTab["cellb"]["base_ne"]=$cell;
			$topoTab["topoInfo"]=$topoInfo;
		}		
		else if(preg_match($rncPattern, $moid, $matches)) {
			$cell = "virtual_".trim($matches[1]);
			$cell_label = $cell;
			$rnc = trim($matches[1]); 
			//displayInDemon(__METHOD__ . " INFO : Found rnc pattern.<br>\n", "alert");
			$topoInfo["Cell"]=$cell;
			$topoInfo["Cell label"]=$cell_label;
			$topoInfo["RNC"]=$rnc;	
			$topoTab["family"]="cellb";
			$topoTab["cellb"]["base_ne"]=$cell;
			$topoTab["topoInfo"]=$topoInfo;			
		}
		
		if($cell == "MDC01RNC_12823"){
			//displayInDemon(__METHOD__ . " INFO : Found cell ".$cell." ", "alert");
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