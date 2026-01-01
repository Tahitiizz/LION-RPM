<?php

class ParserHuaBss extends Parser {
	// Définition du préfixe arbitrairement choisi pour les cellules virtuelles lors des ByPass
	const PREFIX_BYPASS_RNC = "virtual_";
	// Définition du type de fichier source à traiter
	const PARSER_FILE_NAME="CSV";
	
	//type de fichier à checker lorsque des données BSS sont collectée BZ31225
	const NMS_TABLE_TO_CHECK="E1T1_ES";
	
	private $file;
	private $typeParsing;
	
	//liste des rnc connus en topo BZ31225
	private $bscList;
	
	//liste des NE trouvés n'étant pas en topologie BZ31225
	private $unknownNE;
	
	public function __construct(DataBaseConnection $dbConnection,FileTypeCondition $fileType = null,$single_process_mode=TRUE) {
		$conf = new Configuration();
		$params = $conf->getParametersList();
		$dBServices=new DatabaseServicesHuaBss($dbConnection);
		parent::__construct($dBServices,$params,self::PARSER_FILE_NAME,$fileType,$single_process_mode);
		$this->bscList=array();
		$this->unknownNE=array();
		$this->specif_enable_trx = get_sys_global_parameters('specif_enable_trx');
		
		//$this->fileType = new FileTypeCondition("flat_file_naming_template", "~*", ".csv");
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
		$time_data = $hour.';'.$day.';'.$week.';'.$month.';'.$flat_file->capture_duration.';'.Parser::$capture_duration_expected.';'.$flat_file->capture_duration;
		
		if (file_exists($flat_file->flat_file_location)) {
			//modification du format du flat_file_name ex : BSS & BSSGPRS - toto - titi|tata
			//$flat_file_name_exploded=explode("|",$flat_file->flat_file_name);
			//$parameters=explode(" - ",$flat_file->flat_file_name);
			
			$pattern="/.*\s-\s(.*)$/";
			if( preg_match($pattern,$flat_file->flat_file_name,$matches) )
			{
				$nms_table 	= $matches[1];
				if($nms_table==self::NMS_TABLE_TO_CHECK){
					$this->bscList=$this->dbServices->getNeListByType("bsc");
				}	
				$this->fillCsvFiles($flat_file, $time_data,$nms_table,$hour);
			}
			else
			{
				$message = "Le flat_file_name  '{$flat_file->flat_file_name}' est invalide, on attend un format : family - label - entity (table : sys_definition_flat_file_lib)";
				displayInDemon("<span style='color:red;'>{$message}</span>\n");
				return;
			}
		}
		else displayInDemon(__METHOD__ . " ERROR : fichier {$flat_file->flat_file_location} non présent", "alert");

		if (Tools::$debug) {Tools::traceMemoryUsage();}
	}
	
	/**
	 * @param FaltFile $flat_file objet FlatFile
	 * @param string $timedata liste des données de temps séparées par des ";"
	 * @param string $nms_table entité considérée
	 * @param string $hour heure considérée
	 * @return string données à insérer dans le fichier sql
	 */

	private function fillCsvFiles($flat_file, $timedata, $nms_table,$hour)
	{
		//$trx_flag = get_sys_global_parameters("specif_enable_trx");
		Tools::debugTimeExcStart(__METHOD__);
		
		$counters_list_per_todo=$this->getCptsByNmsTable($nms_table);
		if (count($counters_list_per_todo)==0){
			return;
		}
		
		$csv_sql = "";
		
		// On ouvre le fichier source et on récupère la 1ère ligne du fichie
		$items = $this->openSrcFile($flat_file->flat_file_location);
		// erreur de lecture :
		if ($items === null) {
			displayInDemon(__METHOD__ . "ERROR : erreur de lecture lors de fgetcsv() sur le fichier '$flat_file->flat_file_location'", "alert");
			fclose($this->file);
			return null;
		}
		// Définition des comtpeurs de l'en-tête du fichier source
		$headers = $this->parseHeader($items);
		$headers_lower = array_map("strtolower", $headers);
		$headersnb = count($headers);
		
		//pour chaque $todo
		foreach ($counters_list_per_todo as $todo => $counters_list){
			$param=$this->params->getParamWithTodo($todo);
			$id_group_table=$param->id_group_table;
			
			if (($param->family == Tools::$FAMILY_TRX) && ($this->specif_enable_trx == "0")) {
					$message = "The TRX stat option is disabled. The file name ".$flat_file->flat_file_location." for hour : " . $hour ." can't be integrated in TRX Family";
					displayInDemon(__METHOD__ . " WARNING : $message");
					continue;
			}
			
			// on parcourt chaque ligne du fichier
			while ($items !== false) {
				$items = fgetcsv($this->file, 65535, ',', '"');
				$itemsnb = count($items);
				// ligne vide :
				if (($itemsnb == 1) && ($items[0] === null)) { continue; }
				// lignes > 2 : les compteurs, que l'on va traiter :
				// Vérification qu'il existe autant de colonne de données sources que celles définient dans le header du fichier
				if ($headersnb != $itemsnb) {
					displayInDemon(__METHOD__ . " ERROR : ligne $lineno invalide dans le fichier '$filename', $itemsnb elements au lieu de $headersnb attendus", "alert");
					displayInDemon(__METHOD__ . " headers=".join(",", $headers));
					displayInDemon(__METHOD__ . " items=".join(",", $items));
					continue;
				}
				
				//BZ 29011 casse des id réseaux
				foreach ($headers_lower as $index => $counterName) {
					$values[$counterName]=strtoupper($items[$index]);
				}
				
				// Construit l'arbre de topologie et renvoie l'élément de topo définit pour cette ligne du fichier source.
				$networkElement = $this->fillTopology($values, $id_group_table,$hour,$nms_table==self::NMS_TABLE_TO_CHECK);
				if(empty($networkElement)) continue;
				
				// ### Construction des lignes à ajouter dans le fichier SQL temporaire
				// Ajoute les premiers éléments de la ligne dans la chaine de caractères $csv_sql
				$csv_sql .= "{$this->getMinute($values)};{$networkElement};{$timedata}";
				
				
				//$param=$this->params->getParamWithTodo($parser_todo);
				foreach ($counters_list as $counter) {
					$csv_sql .= ';' . $this->getCounterValue($counter, $values);
				}
				$csv_sql .= "\n";
			}

		
			$parser_fileSqlName = Tools::getCopyFilePath($param->network[0], $todo,$hour);

			//sauvegarde dans le fichier SQL
			$this->fileSauvSql($parser_fileSqlName, $csv_sql);
		
		
			// les 4 premiers items sont toujours "Result Time,Granularity Period,Object Name,Reliability"
			// donc on les vire, ce ne sont pas des compteurs
			$headersminus=array_slice($headers,4);
			//Automatic mapping
			//Sauvegarder les compteurs trouvés dans le fichiers sources
			$this->addToCountersList($headersminus, $nms_table, $id_group_table,$nms_table);
			
			if(!empty($this->unknownNE)){
				displayInDemon("Les elements reseaux suivants (".implode(", ",$this->unknownNE).") n'ont pas de correspondance en topologie, il ne seront pas integres\n");
			}
			
			unset($csv_sql);
			//on se replace en début de fichier
			rewind($this->file);
			//on saute la premiere ligne et éventuellement la 2 eme ligne qui correspond au header 
			$items = fgetcsv($this->file, 65535, ',', '"');
			$headers = $this->parseHeader($items);
		}
		fclose($this->file);
		Tools::debugTimeExcEnd(__METHOD__);
	}
	
	/**
	 *
	 * Déduis le nombre de minute à partir d'une date dans le fichier source.
	 * @param array $values
	 */
	private function getMinute($values) {
		//récupération de la notion de minute (2008-08-17 18:00)
		$min = 0;
		if ($this->typeParsing == 2 && array_key_exists("datetime", $values)) {
			//DateTime de la forme "0710180000"
			$min = (int)substr($values['datetime'], 8, 2);
		}
		elseif (($timestamp = strtotime($values['result time'])) !== false) {
			$min = (int)date("i", $timestamp);
		}
		else {
			displayInDemon("<span style='color:red;'>Format de la date incorrecte [{$values['result time']}] pour la gestion des fichiers inférieurs à 1h</span>");
		}
		return $min;
	}
	
	/**
	 *
	 * Ouvre le fichier source, détecte les erreurs liées à l'ouverture, à la récupération de la 1ère ligne et aux prérequis du traitement du fichier.
	 * @param string $filename
	 */
	private function openSrcFile($filename) {
		$items = array();
		// ouverture
		$this->file = fopen($filename, "rt");
		if (!$this->file) {
			displayInDemon(__METHOD__ . " ERROR : l'ouverture du fichier '$filename' en lecture a echoue", "alert");
			$items = null;
		}
		else {
			$items = fgetcsv($this->file, 65535, ',', '"');
		}
		return $items;
	}
	
	/**
	 *
	 * Construit l'arbre de la topologie du réseau. Renvoie l'élement enfant.
	 * L'arbre de topologie est stocké dans la variable privée $this->topoCellsArray pour chaque famille
	 * @param array $values
	 * @param int $id_group_table
	 * @param string $hour heure considérée
	 * @param boolean $checkRNC check si l'élément réseau est un BSC connu
	 * @return string $childId
	 */
	private function fillTopology($values, $id_group_table,$hour,$checkBSC=false) {
		// Analyse du contenu de la ligne ($values) du fichier source
		// Détermination de l'élément réseau fils et père
		// initialisation des elts reseaux (la cellule et son parent, BSC ou PCU)
		$childId = NULL;
		$parentNeId = NULL;
		// Gestion du ByPass BSC/PCU
		// On compare les OBJECT NAME aux expressions régulières pour déterminer si le fichier est construit pour un
		// NE cell, BSC ou PCU
		//ELEMENT,DateTime,GCI,Cell
		if ($this->typeParsing == 2 && array_key_exists("element", $values) && array_key_exists("cell", $values)) {
			$parentNeId = $values['element'];
			$childId 	= $values['cell'];
		}
		//TRX index
		elseif (array_key_exists('object name', $values)) {
			if (preg_match("@^([^/\s]+)/Cell:LABEL=([a-z0-9 _/.-]+).*TRX Index=([0-9]+)@i", $values['object name'], $regs) == 1) {
				//gestion du cas ou on veut integer un fichier TRX comme du BSS (bsc6000_mr_cv_interference)
				if ($id_group_table == 1) {
					$parentNeId = $regs[1];
					$childId = $regs[2];
				}
				else {
					$parentNeId = $regs[2];
					$childId = $regs[3];
					$childId=$regs[2].'_'.$childId;
				}
			}
			// Correction du bug 17631 ci-après.
			// bss     : sed 's@/[^:]*:Label=\\([^,]*\\),[^;]*@;\\1@i'"
			// BSC1/Cell:Label=S092-3, MODUNO=5, CELLLNUM=20
			// BSC1/Cell:Label=S092-3, MODUNO=5, CELLLNUM=20/Trx:TRX-8
			// BSC6900SC/GCELL:LABEL=Azurduy-1, CellIndex=2045, CGI=73602293E1D82
			// Cell -> BSC -> Network
			// Contenu de l'expression régulière :
			//		([^/\s]+) : tout sauf espace, tabulation, saut de ligne, ou tout autre caractère non imprimable
			elseif (preg_match("@^([^/\s]+)/G?Cell:Label *[:=] *([a-z0-9 _/.-]+)@i", $values['object name'], $regs) == 1) {
				$parentNeId = $regs[1];
				$childId = $regs[2];
			}
			//BZ30843
			elseif (preg_match("@^([^/\s]+)/(?:Gcell_)?G?Cell:Label *[:=] *([a-z0-9 _/.-]+),\s+CellIndex@i", $values['object name'], $regs) == 1) {
				$parentNeId = $regs[1];
				$childId = $regs[2];
			}
			elseif (preg_match("@^([^/\s]+)/BSC *[:=] *([a-z0-9 _/.-]+)@i", $values['object name'], $regs) == 1) {
				$parentNeId = $regs[1];
				$childId = self::PREFIX_BYPASS_RNC . $regs[1];
			}
			//BZ 29775
			//elseif (preg_match("@^([^/\s]+)/BSC Report *[:=] *([a-z0-9 _/.-]+)@i", $values['object name'], $regs) == 1) {
			elseif (preg_match("@^([^/\s]+)/(?:BSC Report|BSCRPT) *[:=] *([a-z0-9 _/.-]+)@i", $values['object name'], $regs) == 1) {	
			$parentNeId = $regs[1];
				$childId = self::PREFIX_BYPASS_RNC . $regs[1];
			}
			elseif (preg_match("@^([^/\s]+)/BSCNE *[:=] *([a-z0-9 _/.-]+)@i", $values['object name'], $regs) == 1) {
				$parentNeId = $regs[1];
				$childId = self::PREFIX_BYPASS_RNC . $regs[1];
			}
			// bssgprs : sed 's@/[^:]*:@;@i'
			// PCU1/PCUCell:s145-2
			// Cell -> PCU -> Network
			elseif (preg_match("@^([^/\s]+)/PCUCell *[:=] *([a-z0-9 _/.-]+)@i", $values['object name'], $regs) == 1) {
				$parentNeId = $regs[1];
				$childId = $regs[2];
			}
			elseif (preg_match("@^([^/\s]+)/PCU:LABEL *[:=] *([a-z0-9 _/.-]+)@i", $values['object name'], $regs) == 1) {
				$parentNeId = $regs[1];
				$childId = self::PREFIX_BYPASS_RNC . $regs[1];
			}
			// fallback...
			elseif (preg_match("@^([^/\s]+)/([a-z0-9:= _/.-]+)@i", $values['object name'], $regs) == 1) {
				$parentNeId = $regs[1];
				$childId = $regs[2];
			}
		}
		elseif ($this->typeParsing == 2) {
			displayInDemon(__METHOD__ . " ERROR : TypeParsing : 2 pour '".print_r($values, true)."'", "alert");
			print_r($items);
			$childId = null;
		}
		else {
			displayInDemon(__METHOD__ . " ERROR : preg_match fail pour '".$values['object name']."'", "alert");
			print_r($items);
			$childId = null;
		}
		// Ajout des relations père/fils des éléments réseaux dans le tableau de gestion de la topologie
		// Le tableau $this->topoCellsArray doit être renseigné ($child, $elt) pour la famille traité (bss, gprs, trx)
		// Ex : $this->topoCellsArray["bss"][$childId] = $parentNeId; pour la famille BSS
		
		$param = $this->params->getWithGroupTable($id_group_table);
		if (isset($param) && isset($childId)) {
			//$param->topoCellArray[$childId] = $parentNeId;
			//utilisation de la méthode AddTopologyInfo, parser lib 1.5
			switch($id_group_table){
				case "1" : 
					if($checkBSC){		  
						if(empty($this->bscList)){
							if(!in_array($parentNeId,$this->unknownNE))$this->unknownNE[]=$parentNeId;
								return false;
							}
						else{
							if(!in_array($parentNeId,$this->bscList)){
								if(!in_array($parentNeId,$this->unknownNE))$this->unknownNE[]=$parentNeId;
									return false;
								}
							}
						}					
						$topo_array["BSC"]=$parentNeId;
						$topo_array["Cell"]=$childId;
						break;
				case "2" : $topo_array["PCU"]=$parentNeId;
							$topo_array["Cell"]=$childId;
							break;
				case "3" : $topo_array["Cell"]=$parentNeId;
							$topo_array["TRX"]=$childId;
							break;
			}
			$param->addTopologyInfo($childId,$topo_array,$hour);
		}
		return $childId;
	}
	
/**
	 *
	 * Analyse l'en-tête du fichier source et construit le fichier d'automatique mapping.
	 * @param array $items
	 */
	private function parseHeader($items) {
		$headers = array();
		// BSC6900 et BSC6000 : si le nms_field_name commence par un "ID:", on ne garde que ça
		// (la meme chose est faite dans get_cpts_from_sfr)
		foreach ($items as $token) {
			if (preg_match('/^([a-z0-9]+)\s*:/i', $token, $regs) == 1) {
				$headers[] = strtolower($regs[1]);
			}
			else {
				$headers[] = strtolower($token);
			}
		}
		// premiere ligne : en-tête
		// on sauvegarde ça dans un tableau, en mettant tout en minuscules (suivant les clients, la casse des nms n'est pas la même...)
		//Si type des fichiers BSC32 dont l'entête est de la forme : "ELEMENT,DateTime,GCI,Cell,7061,7062,..."
		if( count($items) > 3 && $items[0] == "ELEMENT" && $items[1] == "DateTime" ) {
			$this->typeParsing = 2;
		}
		else {
			//On définit des types T1 (type courante) et T2 (type des fichiers BSC32 dont l'entête est de la forme : "ELEMENT,DateTime,GCI,Cell,7061,7062,..." )
			$this->typeParsing = 1; //par défaut le type est T1
		}
		// 	deuxieme ligne : unites, ignorees dans le cas du type 1
		if ($this->typeParsing == 1) {
			fgetcsv($this->file, 65535, ',', '"');
		}
		return $headers;
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
	
	/**
	 * 
	 * Cette fonction cherche la famille à laquelle appartient le fichier source analysé.
	 * Spécifique à Huawei BSS car le fichier ne contient qu'un seul ensemble de compteur (nms_table unique).
	 * @param array $values Tableau de valeurs de l'en-tête du fichier lu
	 */
	private function getGroupeTable($header) {
		foreach ($header as $value) {
			if (strncmp($value, "capture_duration", 16) === 0) { continue; }
			$counter = $this->params->getCounter($value);
			if (isset($counter)) {
				return $counter->id_group_table;
			}
		}
		return null;
	}
}
?>