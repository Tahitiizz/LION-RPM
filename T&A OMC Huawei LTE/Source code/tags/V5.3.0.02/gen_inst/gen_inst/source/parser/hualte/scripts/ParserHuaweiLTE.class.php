<?php

class ParserHuaweiLTE extends Parser {
	
	const PARSER_FILE_NAME="XML";
	
	public function __construct(DataBaseConnection $dbConnection,FileTypeCondition $fileType = null,$single_process_mode=TRUE) {
		
		$conf = new Configuration();
		$this->params = $conf->getParametersList();
		$this->dBServices=new DatabaseServicesHuaLte($dbConnection);
		parent::__construct($this->dBServices,$this->params,self::PARSER_FILE_NAME,$fileType,$single_process_mode);
		// Cf. http://fr.php.net/xmlreader
		$this->xmlReader = new XMLReader();
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
		$pattern="/A([0-9]{4})([0-9]{2})([0-9]{2})\.([0-9]{2})([0-9]{2})[+,-]([0-9]{4})\-([0-9]{2})([0-9]{2})[+,-]([0-9]{4})_([[:print:]]+)\.xml/i";
		$this->min="00";
		
		if( preg_match($pattern, $flat_file->uploaded_flat_file_name, $regs)){
			$this->min=$regs[5];
			$this->enodeb=$regs[10];
		}
		if (file_exists($flat_file->flat_file_location)) {
			$enodebId = $this->geteNodeBid($flat_file);
			$this->parseXml($flat_file,$hour,$enodebId);
		}
		else displayInDemon(__METHOD__ . " ERROR : fichier {$flat_file->flat_file_location} non présent", "alert");

		if (Tools::$debug) {Tools::traceMemoryUsage();}
	}
	
	
	//parsing fichier XML
	public function parseXml($flat_file,$hour,$enodebId){
		$filename=$flat_file->flat_file_location;
	    $xmlReader = new XMLReader();
        $xmlReader->open($filename);
        $process = 0;
        $counterPos=0;
		$measObjLdnList= array();
        $this->cellbFamilies = array("1"=>"cellba","2"=>"cellbb","3"=>"cellbc","4"=>"cellbd");      
        while ($xmlReader->read())
        {
       		if((($xmlReader->name == "measInfo")) && $xmlReader->nodeType == XMLReader::ELEMENT)
            {
                $object_type="";
                $counterList = array();
                $this->CounterValuesListPerBlock=array();
                $process = 0;
                $counterPos=0;
                $nms_table=$xmlReader->getAttribute('measInfoId');
				
            }

            elseif((($xmlReader->name == "measType")||($xmlReader->name == "measTypes")) && $xmlReader->nodeType == XMLReader::ELEMENT)
            {
				$xmlReader->read();
	            $new_counters = trim($xmlReader->value);
	            $array_counters = explode(" ", $new_counters);
	            foreach ($array_counters as $i => $counter) {
					$counterList[]=$counter;
				}     
            }
            elseif((($xmlReader->name == "measResults")) && $xmlReader->nodeType == XMLReader::ELEMENT)
            {
            	
                $xmlReader->read();
                $countersValue = trim($xmlReader->value);
                $currentCptValueList = explode(" ",$countersValue);
            }
            elseif((($xmlReader->name == "measValue")) && $xmlReader->nodeType == XMLReader::ELEMENT)
            {
            	////dans le cas du format XSD measObjLdn <=> balide <moid>
            	$measObjLdn=NULL;
            	$measObjLdn=$xmlReader->getAttribute('measObjLdn');
            	array_push($measObjLdnList,$measObjLdn);
            	$tabTopo=$this->get_ne_info($measObjLdn,$nms_table,$enodebId);
            	
            	if($tabTopo!=null){
            		
					$family=$tabTopo["family"];
            		$id_group_table=strval($this->getIdGroupTable($family));
            		$counters_list_per_todo = $this->getCptsByNmsTable($nms_table);
            		
            		if(count($counters_list_per_todo)!=0){
            			$process=1;
            		}
            		$count_moid++;
            	}

	            	
                // tableau pour stocker les valeurs du bloc <mv>...</mv> courant
                $countersValue = array();
            }
			
			
        	elseif(($xmlReader->name == "measValue") && $xmlReader->nodeType == XMLReader::END_ELEMENT)
            {
                if($process==1)
                {
                	 // tableau pour stocker les valeurs du bloc <mv>...</mv> courant
	                $countersValue = array();
	                $values=array();
					// On charge les données du fichiers sources dans le tableau $values
	
					for ($i = 0; $i <  count($counterList); $i++) {
						$currentValue = $currentCptValueList[$i];
						if($currentValue == "NIL"){
							$currentValue = null;
						}
						$values[strtolower($counterList[$i])] = $currentValue;
					}

					$this->CounterValuesListPerBlock[]=array($tabTopo,$values);
					//remis à zero
					$currentCptValueList=array();
                	
                	
                }              
            }
            
        	elseif((($xmlReader->name == "measInfo")) && $xmlReader->nodeType == XMLReader::END_ELEMENT)
            {
            	if(count($counters_list_per_todo)!=0){
            		$this->addToCountersList($counterList, $nms_table,$id_group_table);
            		
					foreach ($counters_list_per_todo as $todo => $counters_list) {
						//TODO récuéper dynamiquement la famille à partir du nms_table
						$family=substr($todo, 0,strpos($todo, "_")); 
						
						$param = $this->params->getParamWithTodo($todo);
						$id_group_table=$param->id_group_table;
						
						foreach ($this->CounterValuesListPerBlock as $valueTab) {
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
								//Si on tombe sur un compteurs issue de l'automatic mapping
								//on rajoute un c au nms_fiel_name dans le tableau des valeurs pour matcher avec le nms_field_name en base (compteurs issue de l'automatic mapping)
								if(preg_match("/[Cc]([0-9]{8,})/", $counter->nms_field_name[0], $output_array)){
									$newKey = "c".$output_array[1];
									$oldKey = $output_array[1];
									$values =  $this->replace_key_function($values,$oldKey,$newKey);
								}
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
					//on ajoute les bh pour les 3 autres familles cellb
					$this->manageBh($nms_table,$this->CounterValuesListPerBlock,$enodebId,$measObjLdnList,$family,$hour);
					//On vide le tableau de measObjLdn
					$measObjLdnList = array();
            	}else{
               	 	//automatic mapping
	            	if(in_array($family,$this->cellbFamilies)){
	            		foreach ($this->cellbFamilies as $id => $family){
	            			$this->addToCountersList($counterList, $nms_table,$id);
	            		}
	            	}else{
	            			$this->addToCountersList($counterList, $nms_table,$id_group_table);
	            	}
                	
                }  
            } 	        
		}
		
	}
	/**
	 * Fonction qui permet qui vérifier si le nms_table en cours de traitement est un nms_table qui contient les compteurs utilisés pour le calcul du bh.
	 * Si c'est le cas, on construit pour chaque famille restantes le  fichier csv qui va être utilisé pour remplir les tables temporaire (par nms_table)
	 * @param $nms_table string nms_table en cours de traitement
	 * @param $CounterValuesListPerBlock array tableau imbriqué contenant la topo = [0] et la les valeurs = [1]
	 * @param $enodebId int  id du enodeb qui va être utilisé pour construire la topo pour les compteurs liés au calcul du bh 
	 * @param $measObjLdnList array tableau contenant les measObjLdn pour le nms_table en cours de traitement
	 * @param mainBhfamily string famille pour lesquelles les compteurs bh existent vraiment. Celà nous permet de déterminer les familles restante à traiter
	 * @param hour string heure en cours de traitement
	**/
	public function manageBh ($nms_table,$CounterValuesListPerBlock,$enodebId,$measObjLdnList,$mainBhfamily,$hour){
		$arrayNmsTable = array();
		//liste des familles cellb
		$cellbFamilyList= array("cellba","cellbb","cellbc","cellbd");
		//on determine la liste des famille qu'il reste à traiter pour les bh
		unset($cellbFamilyList[array_search($mainBhfamily, $cellbFamilyList)]);
		//on créé chaque nom de nms_table équivalant pour chaque familles
		foreach($cellbFamilyList as $currentfamily ){
			$varNmsTable = $currentfamily."NmsTable";
			$$varNmsTable = $nms_table."_".$currentfamily."_bh";
			array_push($arrayNmsTable,$$varNmsTable);
		}
		//A partir des nom de nms_table construit dynamiquement, on regarde si ce dernier existe en base.
		$isBhNmsTable = ($this->getFamillyByNmsTable($arrayNmsTable[0])=="unknown")?false:true;
		
		if($isBhNmsTable){
			//Si le nms_table construit existe, cela veut dire que nous avons à faire au nms_table lié au bh
			$shortValue = array();
			foreach($arrayNmsTable as $index => $current_nms_table){
				$counters_list_per_todo = $this->getCptsByNmsTable($current_nms_table);	
				foreach ($measObjLdnList as $i => $measObjLdn){
					//Pour chaque moid que nous avons dans le tableau measObjLdnList on récupère les valeurs pour les compteurs utiisée dans le calcul du bh
					$shortValues = $this->getShortValue($CounterValuesListPerBlock,$counters_list_per_todo,$i);
					//On construit un nouveau tableau de topo à partir de ce moid
					$tabTopo=$this->get_ne_info($measObjLdn,$current_nms_table,$enodebId);
					preg_match("/\d*_(\D*)_bh/", $current_nms_table, $output_array);
					$family = $output_array[1];
					$networkElement = $tabTopo[$family]["base_ne"];
					//On commence à construire le fichier csv
					$csv_sql .= "{$this->min};{$networkElement};{$this->time_data}";
					//Pour chaque counter du nms_table, on attribue la bonne valeurs et on finit la construction du fichier csv
					foreach ($counters_list_per_todo as $todo => $counters_list) {
						$param = $this->params->getParamWithTodo($todo);
						$counters_list=$param->todo[$todo];
						
						foreach ($counters_list as $counter) {
							$csv_sql .= ';' . $this->getCounterValue($counter,$shortValues,false,false);
						}
						$csv_sql .= "\n";
					}
				}
				//On appelle le bon fichier temporaire pour rajouter le csv créé à la suite.
				$parser_fileSqlName = Tools::getCopyFilePath($param->network[0], $todo,$hour);	
				//sauvegarde dans le fichier SQL
				$this->fileSauvSql($parser_fileSqlName, $csv_sql);
				
				unset($csv_sql);
			}
		}
	}
	
	/**
	 * Fonction qui permet de récupérer les valeurs uniquement pour les compteurs qui permettent de calculer la bh
	 * @param $CounterValuesListPerBlock tableau imbriqué contenant la topo = [0] et la les valeurs = [1]
	 * @param $counters_list_per_todo string liste des compteurs utilisé dans le calcul des bh
	 * @param $pos int  position du measObjLdn que l'on est en train de lire 
	 * @return $shortValues array tableau contenant en index le compteur et en valeur, la valeur pour ce compteur
	 */
	public function getShortValue ($CounterValuesListPerBlock,$counters_list_per_todo,$pos) {
		$shortValues = array();
		$values=$CounterValuesListPerBlock[$pos][1];
		
		foreach ($counters_list_per_todo as $todo => $counters_list) {
			$param = $this->params->getParamWithTodo($todo);
			$id_group_table=$param->id_group_table;
			foreach ($counters_list as $counter) {
				$counterBh = $counter->nms_field_name[1];
				$shortValues[$counterBh] = $values[$counterBh]; 
			}
		}
		
		return $shortValues;
	}
	
	public function geteNodeBid($flat_file){

		$filename=$flat_file->flat_file_location;
	    $xmlReaderEnodb = new XMLReader();
        $xmlReaderEnodb->open($filename);
      
         while ($xmlReaderEnodb->read()){
       		if((($xmlReaderEnodb->name == "measValue")) && $xmlReaderEnodb->nodeType == XMLReader::ELEMENT){
            	////dans le cas du format XSD measObjLdn <=> balide <moid>
            	$measObjLdn=NULL;
            	$measObjLdn=$xmlReaderEnodb->getAttribute('measObjLdn');
            	$short_moid = substr($measObjLdn,0,60);
            	
            	
            	$cellb_indicator1= "/Cell:eNodeB";
				$cellb_indicator2= "/CN0perator:";
				$cellb_indicator3= "Sector"; 
				if(strpos($short_moid,$cellb_indicator1)|| strpos($short_moid,$cellb_indicator2) || strpos($short_moid,$cellb_indicator3)) {
					preg_match("/.*Local Cell ID=([0-9]{1,}).*eNodeB ID=([0-9]{1,}).*/", $measObjLdn, $output_array);
					if(isset($output_array[2])){
						$enodebId = $output_array[2];
					}else{
						$enodebId = null;
					}
					
					break;
					
	            }
        	}
       	 }
		
       	return $enodebId;
	}

/**
	 * Fonction qui va sauvegarder les données $csv_sql dans un fichiers $filename
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
	
	
	public static function  getConditionProvider($dbServices){
		return new XMLConditionProvider($dbServices);
	}

	
	public function get_ne_info($moid,$nms_table,$enodebId,$familyList=null) {
		
		$cellb_indicator1= "/Cell:eNodeB";
		$cellb_indicator2= "/CN0perator:";
		$cellb_indicator3= "Sector"; 
		$enodeb_indicator1 = "/eNodeBFunction:";
		$enodeb_indicator2 = ":Board ";
		$enodeb_indicator3 = ":Cabinet ";
		$enodeb_indicator4 = "/S1Interface:";
		$enodeb_indicator5 = "/X2Interface:";
		$adj_indicator1 = "/NCell:";
		

		$short_moid = substr($moid,0,60);
		
		
		$topoTab=array();
		
		if(strpos($short_moid,$cellb_indicator1)|| strpos($short_moid,$cellb_indicator2) || strpos($short_moid,$cellb_indicator3)) {
			$families = $this->getFamillyByNmsTable($nms_table);
			
			preg_match("/.*Local Cell ID=([0-9]{1,}).*Cell Name=(.*)\,.*eNodeB ID=([0-9]{1,}).*/", $moid, $output_array);
			$enodeb_id= $output_array[3];
			$localCellId=$output_array[1];
			$cell_label=$output_array[2];
			$cell = $enodeb_id."_".$localCellId;
			
			//topoInfo
			$topoInfo["Cell"]=$cell;
			$topoInfo["Cell Label"]=$cell_label;
			$topoInfo["eNodeB"]=$enodeb_id;
			$topoInfo["eNodeB Label"]=$this->enodeb;
			
			if (is_array($families)){
				
				$topoTab["family"]=$families[0];
				$topoTab["topoInfo"]=$topoInfo;
				$topoTab[$families[0]]["base_ne"]=$cell;	
			}else{
				if($families == "unknown"){
					$family= "cellbd";
					$topoTab["family"]=$family;
					$topoTab["topoInfo"]=$topoInfo;
					$topoTab[$family]["base_ne"]=$cell;
				}else{
					$topoTab["family"]=$families;
					$topoTab["topoInfo"]=$topoInfo;
					$topoTab[$families]["base_ne"]=$cell;
				}
			}
		}else if(strpos($short_moid,$adj_indicator1)){
			$family = "adj";
			preg_match("/.*NCell\:.*, Cell ID=([0-9]{1,}),.*Local Cell ID=([0-9]{1,}).*, eNodeB ID=([0-9]{1,}).*, Local eNodeB ID=([0-9]{1,})/", $moid, $output_array);
			$sourceCell = $output_array[4]."_".$output_array[2];
			$targetCell = $output_array[3]."_".$output_array[1];
			$sourceTargetCell = $sourceCell."_".$targetCell;
			
			
			//topoInfo
			$topoInfo["SourceCell"]=$sourceCell;
			$topoInfo["SourceTargetCell"]=$sourceTargetCell;
			$topoTab["family"]="adj";
			$topoTab["topoInfo"]=$topoInfo;
			$topoTab[$family]["base_ne"]="$sourceTargetCell;$sourceCell";

		}else if (strpos($short_moid,$enodeb_indicator1) || strpos($short_moid,$enodeb_indicator2) || strpos($short_moid,$enodeb_indicator3) || 
		strpos($short_moid,$enodeb_indicator4) || strpos($short_moid,$enodeb_indicator5)){	
			$family = "enodeb";
			$enodeb = $enodebId;
			$topoInfo["eNodeB"]=$enodeb;
			$topoInfo["eNodeB Label"]=$this->enodeb;
			$topoTab["family"]=$family;
			$topoTab[$family]["base_ne"]=$enodeb;
		}
		
		return $topoTab;
	}
	/**
	Fonction qui renvoie la famille associé au nms_table
	@param string nms_table id du nms_table 
	return $result array or string Retourne un tableau avec la famille du nms_table ou bien unknow si la requête n'a retourné aucun résultats
	**/
	public function  getFamillyByNmsTable($nms_table){
		$cellFamilly = $this->dbServices->getFamillyByNmsTable($nms_table);
			
		if(!empty($cellFamilly)){
			if(count($cellFamilly) > 1){
				$famTab=array();
				foreach($cellFamilly as $index => $currentfamily){
					preg_match("/.*_.*_(.*)_axe1$/", $currentfamily["edw_group_table"], $output_array);
					array_push($famTab,$output_array[1]);
				}
				$result = $famTab;
			}else{
				preg_match("/.*_.*_(.*)_axe1$/", $cellFamilly[0]["edw_group_table"], $output_array);
				$result = $output_array[1];
			}
			
		}else{
			$result = "unknown";
		}
		
		return $result;
	}
	
	
	/**
	*
	* Vérifie que le MO courant n'est pas un MO déprécié
	* @return un boolean 
	*/
	public function checkDepreciatedMo($moid){
		foreach($this->excludedMo as $mo){
			if(strpos($moid,$mo)==true){
				return true;
			}else{
				return false;
			}
		}
	}
	
	public function getIdGroupTable($family) {
		switch ($family) {
			case "cellba":
				$id_group_table=1;
			break;
			 case "cellbb":
				$id_group_table=2;
			break;
			 case "cellbc":
				$id_group_table=3;
			break;
			 case "cellbd":
				$id_group_table=4;
			break;
			 case "enodeb":
				$id_group_table=5;
			break;
			case "adj":
				$id_group_table=6;
			break;
			default:
			$id_group_table=1;
			break;
		}
		return $id_group_table;
	}
	
	public function replace_key_function($array, $key1, $key2)
	{
	    $keys = array_keys($array);
	    $index = array_search($key1, $keys);
	
	    if ($index !== false) {
	        $keys[$index] = $key2;
	        $array = array_combine($keys, $array);
	    }
	
	    return $array;
	}
	

}
?>