<?php

class ParserEricssonLTE extends Parser {
	
	const PARSER_FILE_NAME="XML";
	
	public function __construct(DataBaseConnection $dbConnection,FileTypeCondition $fileType = null,$single_process_mode=TRUE) {
		
		$conf = new Configuration();
		$this->params = $conf->getParametersList();
		$dBServices=new DatabaseServices($dbConnection);
		parent::__construct($dBServices,$this->params,self::PARSER_FILE_NAME,$fileType,$single_process_mode);
		// Cf. http://fr.php.net/xmlreader
		$this->excludedMo = array("DlBasebandCapacity","EUtranCellTDD");
		$this->parseDeprecatedMo = get_sys_global_parameters("eutran_cell_tdd_enabled");
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
		$pattern="/A([0-9]{4})([0-9]{2})([0-9]{2})\\.([0-9]{2})([0-9]{2})([+,-][0-9]{2})[0-9]{2}\\-([0-9]{2})([0-9]{2})([+,-][0-9]{2})[0-9]{2}_SubNetwork=(.*),SubNetwork=(.*),MeContext=(.*)_statsfile/i";
		//pour fichier Quadgen
		$pattern1="/A([0-9]{4})([0-9]{2})([0-9]{2})\\.([0-9]{2})([0-9]{2})\\-[0-9]{2}[0-9]{2}.*.xml/i";
		$this->min="00";
		if( preg_match($pattern, $flat_file->uploaded_flat_file_name, $regs)){
			$this->min=$regs[5];
			
			// cas d'un fichier nodeB
			//A<YYYYMMDD>.<hhmm+0000>-<hhmm+0000>_SubNetwork=<xxx>,SubNetwork=<RNC>,MeContext=<Node B>_statsfile.xml
			/*if($regs[11]!=$regs[12]){
				displayInDemon(" warning: {$flat_file->uploaded_flat_file_name} is a NodeB file and will not be parsed", "alert");
				return;
			}*/
		}elseif (preg_match($pattern1, $flat_file->uploaded_flat_file_name, $regs)){
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
		$listPositionMvcCounters = array();
		$mvcCounters = $this->getMvcCounters();
		// pour avoir un aperçu du n° de tour dans la boucle while
		$i=0;
		// déplace le curseur sur le prochain noeud du document XML
		while($this->xmlReader->read())
		{	
			if ($this->xmlReader->name == "mi")
			{
				if($this->xmlReader->nodeType == XMLReader::ELEMENT)
				{
					$counterList=array();
					$currentCptValueList=array();
					$firstMvBlock=true;
					$firstMoidBlock=true;
				}
				else if ($this->xmlReader->nodeType == XMLReader::END_ELEMENT)
				{
					
				}
			}
			if ($this->xmlReader->name == "measInfo")
			{
				if($this->xmlReader->nodeType == XMLReader::ELEMENT)
				{
					$counterList=array();
					$currentCptValueList=array();
					$firstMvBlock=true;
					$firstMoidBlock=true;
					$this->CounterValuesListPerBlock=array();
					$this->deprecatedMo = false;
				}
				else if ($this->xmlReader->nodeType == XMLReader::END_ELEMENT)
				{
					//displayInDemon(__METHOD__ . " TODO COUNT: ".count($counters_list_per_todo)."", "alert");
					if(count($counters_list_per_todo)!=0)
					foreach ($counters_list_per_todo as $todo => $counters_list) {
						
						$family="cellb";
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
								/**
								if($counter->flat_file_position == null && in_array(strtolower($counter->nms_field_name[0]),$mvcCounters[0])){
									$csv_sql .= ';' . $this->getCounterValue($counter,$values,true,true);
								}else if($counter->flat_file_position == null && in_array(strtolower($counter->nms_field_name[0]),$mvcCounters[1])){
									$csv_sql .= ';' . $this->getCounterValue($counter,$values,true,false);
								}
								else{
									$csv_sql .= ';' . $this->getCounterValue($counter,$values,false,false);
								}	
								**/
								//cas des valeurs explicite
								if(in_array(strtolower($counter->nms_field_name[0]),$mvcCounters[0])){
									//Cas d'un compteur global explicite
									if($counter->flat_file_position == -1){
										$csv_sql .= ';' . $this->getCounterValue($counter,$values,true,true);
									}
									//Sinon on a à faire à une déclinaison du compteur avec des valeurs explicites
									else if ($counter->flat_file_position >= 0){
										$csv_sql .= ';' . $this->getCounterValue($counter,$values,false,true);
									}
								}
								//cas des valeurs collapsed
								else if (in_array(strtolower($counter->nms_field_name[0]),$mvcCounters[1])){
									//Cas d'un compteur global collapsed
									if($counter->flat_file_position == -2){
										$csv_sql .= ';' . $this->getCounterValue($counter,$values,true,false);
									}
									//Sinon on a à faire à une déclinaison du compteur avec des valeurs condensées
									else if ($counter->flat_file_position >= 0){
										$csv_sql .= ';' . $this->getCounterValue($counter,$values,false,false);
									}
								}else{
									$csv_sql .= ';' . $this->getCounterValue($counter,$values,false,false);
								}
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
				}
			}
			elseif($this->xmlReader->name == "mt")
			{
				if($this->xmlReader->nodeType == XMLReader::ELEMENT)
				{
					// on lit le nom de compteur (prochain noeud) dans cette balise
					$this->xmlReader->read();
					$counterList[]=	$this->xmlReader->value;
					//On vérifie que le compteur récupéré dans le fichier source n'est pas un MultiValued counter
					$position = count($counterList);
					foreach ($mvcCounters[0] as $j => $mvcCounterExp){
						if(strtolower($this->xmlReader->value) == $mvcCounterExp){
							//On récupère la position du compteur
							if(!in_array($position, $listPositionMvcCounters)){
								array_push($listPositionMvcCounters,$position); 
							}
							
						}
					}
					foreach ($mvcCounters[1] as $i => $mvcCounterColl){
						if(strtolower($this->xmlReader->value) == $mvcCounterColl){
							//On récupère la position du compteur
							if(!in_array($position, $listPositionMvcCounters)){
								array_push($listPositionMvcCounters,$position); 
							}
							
						}
					}
					
				}
			}
			elseif($this->xmlReader->name == "measType")
			{
				if($this->xmlReader->nodeType == XMLReader::ELEMENT)
				{
					// on lit le nom de compteur (prochain noeud) dans cette balise
					$this->xmlReader->read();
					$counterList[]=	$this->xmlReader->value;
					//On vérifie que le compteur récupéré dans le fichier source n'est pas un MultiValued counter
					$position = count($counterList);
					foreach ($mvcCounters[0] as $j => $mvcCounterExp){
						if(strtolower($this->xmlReader->value) == $mvcCounterExp){
							//On récupère la position du compteur
							if(!in_array($position, $listPositionMvcCounters)){
								array_push($listPositionMvcCounters,$position); 
							}
							
						}
					}
					foreach ($mvcCounters[1] as $i => $mvcCounterColl){
						if(strtolower($this->xmlReader->value) == $mvcCounterColl){
							//On récupère la position du compteur
							if(!in_array($position, $listPositionMvcCounters)){
								array_push($listPositionMvcCounters,$position); 
							}
							
						}
					}
					
				}
			}
			else if($this->xmlReader->name == "neun")
			{
				if($this->xmlReader->nodeType == XMLReader::ELEMENT)
				{
					// on lit la valeur (prochain noeud) dans cette balise
					$this->xmlReader->read();
					$this->currentEnodeB=$this->xmlReader->value;
				}
			}
			else if($this->xmlReader->name == "managedElement")
			{
				if($this->xmlReader->nodeType == XMLReader::ELEMENT)
				{
					// on lit la valeur (prochain noeud) dans cette balise
					$this->currentEnodeB=$this->xmlReader->getAttribute("userLabel");
				}
			}
			elseif($this->xmlReader->name == "moid")
			{
				if($this->xmlReader->nodeType == XMLReader::ELEMENT)
				{
					// on lit la valeur (prochain noeud) dans cette balise
					$this->xmlReader->read();
					$moid = $this->xmlReader->value;
					$tabTopo=$this->get_ne_info($moid);
					$this->currentMo =  $this->xmlReader->value;
					
					if($this->parseDeprecatedMo == 0){
						$this->deprecatedMo = $this->checkDepreciatedMo($this->xmlReader->value);
					}
					

					if($tabTopo==null){
						//on saute jusqu'au prochain <md>
						while($this->xmlReader->read()){
							if (($this->xmlReader->name == "md")&&($this->xmlReader->nodeType == XMLReader::END_ELEMENT)) break;
						}
					}
					//si aucun compteur n'est connu on saute
					elseif(count($counters_list_per_todo)==0){
						//on saute jusqu'au prochain <md>
						while($this->xmlReader->read()){
							if (($this->xmlReader->name == "md")&&($this->xmlReader->nodeType == XMLReader::END_ELEMENT)) break;
						}
					}
					
					if(($firstMoidBlock)&&($tabTopo!=null)){
						//automatic mapping
						/*$family=$tabTopo["family"];
						$id_group_table=$this->getIdGroupTable($family);*/
						//$this->addToCountersList($counterList, "cellb_xml", $id_group_table);
						$this->addToCountersList($counterList, "cellb_xml", 1);
						$firstMoidBlock=false;
					}
					


				}
			}
						
			else if($this->xmlReader->name == "mv")
			{
				if($this->xmlReader->nodeType == XMLReader::ELEMENT)
				{
					if($firstMvBlock){
						$counters_list_per_todo = array();
						$counters_list_per_todo = $this->getCptsInFile($counterList);
						$firstMvBlock=false;
					}
					
				}
				elseif ($this->xmlReader->nodeType == XMLReader::END_ELEMENT)
				{
					$values=array();
					// On charge les données du fichiers sources dans le tableau $values

					for ($i = 0; $i <  count($counterList); $i++) {
						$values[strtolower($counterList[$i])] = $currentCptValueList[$i];
					}
					$this->CounterValuesListPerBlock[]=array($tabTopo,$values);
					
					//remis à zero
					$currentCptValueList=array();
				}
			}
			else if($this->xmlReader->name == "measValue")
			{
				if($this->xmlReader->nodeType == XMLReader::ELEMENT)
				{	
					
					$moid = $this->xmlReader->getAttribute("measObjLdn");
					$tabTopo=$this->get_ne_info($moid);
					//displayInDemon(__METHOD__ . " INFO moid : ".$moid."", "alert");
					$this->currentMo =  $this->xmlReader->value;
					if($firstMvBlock&&($tabTopo!=null)){
						
						$counters_list_per_todo = array();
						$counters_list_per_todo = $this->getCptsInFile($counterList);
						//displayInDemon(__METHOD__ . " INFO moid BLOCK : ".$counters_list_per_todo."", "alert");
						$this->addToCountersList($counterList, "cellb_xml", 1);
						$firstMvBlock=false;
					}
										
				}
				elseif ($this->xmlReader->nodeType == XMLReader::END_ELEMENT)
				{
					$values=array();
					// On charge les données du fichiers sources dans le tableau $values

					for ($i = 0; $i <  count($counterList); $i++) {
						$values[strtolower($counterList[$i])] = $currentCptValueList[$i];
					}
					$this->CounterValuesListPerBlock[]=array($tabTopo,$values);
					
					//remis à zero
					$currentCptValueList=array();
				}
			}

			elseif ($this->xmlReader->name == "md")
			{
				if($this->xmlReader->nodeType == XMLReader::ELEMENT)
				{

					$this->CounterValuesListPerBlock=array();
					$this->deprecatedMo = false;
					//construction de l'objet
					//$this->mdBlock = new MdBlock();
				}
				// on ecrit l'ancien objet dans le fichier de sortie puis on créé le nouveau
				else if ($this->xmlReader->nodeType == XMLReader::END_ELEMENT)
				{
					if(count($counters_list_per_todo)!=0)
					foreach ($counters_list_per_todo as $todo => $counters_list) {

						$family="cellb";
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
								/**
								if($counter->flat_file_position == null && in_array(strtolower($counter->nms_field_name[0]),$mvcCounters[0])){
									$csv_sql .= ';' . $this->getCounterValue($counter,$values,true,true);
								}else if($counter->flat_file_position == null && in_array(strtolower($counter->nms_field_name[0]),$mvcCounters[1])){
									$csv_sql .= ';' . $this->getCounterValue($counter,$values,true,false);
								}
								else{
									$csv_sql .= ';' . $this->getCounterValue($counter,$values,false,false);
								}	
								**/
								//cas des valeurs explicite
								if(in_array(strtolower($counter->nms_field_name[0]),$mvcCounters[0])){
									//Cas d'un compteur global explicite
									if($counter->flat_file_position == -1){
										$csv_sql .= ';' . $this->getCounterValue($counter,$values,true,true);
									}
									//Sinon on a à faire à une déclinaison du compteur avec des valeurs explicites
									else if ($counter->flat_file_position >= 0){
										$csv_sql .= ';' . $this->getCounterValue($counter,$values,false,true);
									}
								}
								//cas des valeurs collapsed
								else if (in_array(strtolower($counter->nms_field_name[0]),$mvcCounters[1])){
									//Cas d'un compteur global collapsed
									if($counter->flat_file_position == -2){
										$csv_sql .= ';' . $this->getCounterValue($counter,$values,true,false);
									}
									//Sinon on a à faire à une déclinaison du compteur avec des valeurs condensées
									else if ($counter->flat_file_position >= 0){
										$csv_sql .= ';' . $this->getCounterValue($counter,$values,false,false);
									}
								}else{
									$csv_sql .= ';' . $this->getCounterValue($counter,$values,false,false);
								}
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

					
				}
			}
			
			else if($this->xmlReader->name == "r")
			{
				if($this->xmlReader->nodeType == XMLReader::ELEMENT)
				{
					
					// on lit la valeur (prochain noeud) dans cette balise 
					$this->xmlReader->read();
					//On cherche a trouver la positon du Multivalued counter
					$positonValue = count($currentCptValueList)+1;
					// attention, certaines valeurs prennent la forme d'une liste de sous-valeurs
					// exemple : <r>1476,1400,1429</r>
					$virgulePosition = strpos($this->xmlReader->value, ",");
					
					// si absence de virgule (valeur)
					if ($virgulePosition === false) {
						if($this->deprecatedMo == true){
							$currentCptValue = null;
						}else{
							$currentCptValue = $this->xmlReader->value;
							$currentCptValue = str_replace(array("\r\n", "\n", "\r"), array("", "", ""), $currentCptValue);
						}
						
					}
					// sinon, il s'agit d'une liste de sous-valeurs qu'il faut sommer
					else {
						if(in_array(strval($positonValue), $listPositionMvcCounters)){
							if($this->deprecatedMo == true){
								$currentCptValue = null;
							}else{
								$currentCptValue = $this->xmlReader->value;
								$currentCptValue = str_replace(array("\r\n", "\n", "\r"), array("", "", ""), $currentCptValue);	
							}
						}else{
							if($this->deprecatedMo == true){
								$currentCptValue = null;
							}else{
								$subValuesArray = explode(",", $this->xmlReader->value);
								$currentCptValue = array_sum($subValuesArray);	
							}
						}
						// les balises <r/> ou <r></r> peuvent être interprétées comme un saut de ligne
					}
					$currentCptValueList[]=$currentCptValue;
				}
			}

			else if($this->xmlReader->name == "nedn")
			{
				if($this->xmlReader->nodeType == XMLReader::ELEMENT)
				{
					// on lit la valeur (prochain noeud) dans cette balise
					$this->xmlReader->read();
					$nedn=$this->xmlReader->value;
					
					
					/*//cas des fichiers W10
					if($this->currentRncCode=="")
					if(preg_match("/MeContext=([^,]*)/i", $nedn,$matches)){
						$this->currentRncCode=$matches[1];
					}*/

					
					//label rnc
		
				}
			}
			else
			{
				//echo "cas de balise inconnue : $this->xmlReader->name <br>";
			}
			$i++;
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
	
	
	public function get_ne_info($moid) {
		
		$cellbPattern="/ManagedElement=[a-zA-Z0-9_-]*,ENodeBFunction=[0-9]*,EUtranCellFDD=([a-zA-Z0-9_-]*)(,[^;]+)*/";
		$eNodeBPattern  = "/ManagedElement=[^;]*/";
				
		$topoTab=array();

		if(preg_match($cellbPattern, $moid, $matches)) {
			$family = "cellb";
			$cell = "$this->currentEnodeB"."_$matches[1]";
			//displayInDemon(__METHOD__ . " ICI : ".$cell, "alert");
			
			//topoInfo
			$topoInfo["Cell"]=$cell;
			$topoInfo["eNodeB"]=$this->currentEnodeB;
			//
			$topoTab["family"]="cellb";
			$topoTab["topoInfo"]=$topoInfo;
			$topoTab[$family]["base_ne"]=$cell;
		}
		//bypass le niveau cell
		else if(preg_match($eNodeBPattern, $moid, $matches)) {

			$family = "cellb";
			$cell = "virtual_".$this->currentEnodeB;
			
			//topoInfo
			$topoInfo["Cell"]=$cell;
			
			$topoInfo["eNodeB"]=$this->currentEnodeB;
			//
			$topoTab["family"]="cellb";
			$topoTab["topoInfo"]=$topoInfo;
			$topoTab[$family]["base_ne"]=$cell;
			//displayInDemon(__METHOD__ . " LA", "alert");
			//displayInDemon(__METHOD__ . " ".$cell."", "alert");
			//displayInDemon(__METHOD__ . " ".$topoInfo["eNodeB"]."", "alert");
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
	
	/**
	*
	* Récupère la liste des MultiValued Counter
	* @return tableau des nms_field_name lié à un MultiValued counter
	*/
	public function  getMvcCounters(){
		$counters = $this->dbServices->getAllCounters($this->params);
		$expliciteCounters= array();
		$collapasedCounters= array();
		foreach($counters as $counter) {
			// on récupère la liste des compteurs cumulés à partir de leur flat file position:
			//-2 compteur cumulé condensé
			//-1 compteur cumulé explicite
			// >=0 déclinaisont de compteur cumulé
			if( $counter->flat_file_position != null && $counter->flat_file_position < 0){
				if($counter->flat_file_position == -1){
					if(!in_array($counter->nms_field_name[0],$expliciteCounters)){
							array_push($expliciteCounters, strtolower($counter->nms_field_name[0]));	
					}
				}else if ($counter->flat_file_position == -2){
					if(!in_array($counter->nms_field_name[0],$collapasedCounters)){
							array_push($collapasedCounters, strtolower($counter->nms_field_name[0]));	
						}
					}	
			}	
		}
		return array($expliciteCounters,$collapasedCounters);
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

}
?>