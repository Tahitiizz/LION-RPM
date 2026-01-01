<?php

class ParserHuaRan extends Parser {

	//nom du parser
	const PARSER_FILE_NAME="CSV";
	
	//type de fichier à checker lorsque des données BSS sont collectée BZ31225
	const NMS_TABLE_TO_CHECK="E1T1_PORT";
	
	//liste des rnc connus en topo BZ31225
	private $rncList;
	
	//liste des NE trouvés n'étant pas en topologie BZ31225
	private $unknownNE;
	
	public function __construct(DataBaseConnection $dbConnection,FileTypeCondition $fileType = null,$single_process_mode=TRUE) {
		$conf = new Configuration();
		$params = $conf->getParametersList();
		$dBServices=new DatabaseServicesHuaRan($dbConnection);
		parent::__construct($dBServices,$params,self::PARSER_FILE_NAME,$fileType,$single_process_mode);
		$this->rncList=array();
		$this->unknownNE=array();
		//$this->fileType = new FileTypeCondition("flat_file_name", "~*", "Cell Based");
		// ----
		//specific
		/*$conf = new Configuration();
		$this->params = $conf->getParametersList();
		parent::__construct($dbConnection, self::QUERY_RI, self::TIME_COEF, self::AGGREGATION_NET_RI);*/
	}
		
	/**
	 * Fonction qui parse le fichier et qui va integrer dans un fichier au format csv les données issues du fichier source
	 *
	 * @param int $id_fichier numero du fichier à traiter
	 * @global text repertoire physique d'installation de l'application
	 */
	function createCopyBody(FlatFile $flat_file, $topologyHour='ALL')
	{
		$hour=$flat_file->hour;
		$this->topologyHour=$topologyHour;
		$this->currentHour=$hour;
		$day = substr($hour, 0, 8); 
		$week = Date::getWeek($day);
		$month = substr($hour, 0, 6);
		$this->time_data = $hour.';'.$day.';'.$week.';'.$month.';'.$flat_file->capture_duration.';'.Parser::$capture_duration_expected.';'.$flat_file->capture_duration;
		
		
		if (file_exists($flat_file->flat_file_location)) {

			$parameters = explode(" - ", $flat_file->flat_file_name);
			if( count($parameters) == 3 )
			{
				$nms_table 	= $parameters[2];	//type de fichier de la forme : RBS_PS_LOAD_NBR
				if($nms_table==self::NMS_TABLE_TO_CHECK){
					$this->rncList=$this->dbServices->getNeListByType("rnc");
				}
				$this->fileType_generique($flat_file->flat_file_location,$nms_table,$hour);
			}
			else
			{
				$message = "Le flat_file_name  '{$flat_file->flat_file_name}' est invalide, on attend 3 paramètres (table : sys_definition_flat_file_lib)";
				displayInDemon("<span style='color:red;'>{$message}</span>\n");
				return;
			}

		}
		else displayInDemon(__METHOD__ . " ERROR : fichier {$flat_file->flat_file_location} non présent", "alert");

		if (Tools::$debug) {Tools::traceMemoryUsage();}
	}
	

	
	/**
	* @param FlatFile objet FlatFile lié au fichier
	* @parama string $time_data 
	* @return string données à insérer dans le fichier sql
	*/
	function fileType_generique($file_name,$nms_table,$hour)
	{
		$counters_list_per_todo=$this->getCptsByNmsTable($nms_table);
		if (count($counters_list_per_todo)==0){
			return;
		}
		
		
		$equipement 	= "";
		$csv_sql 		= "";
	
		//récupération de l'entête sour forme de tableau
		$headers = $this->openSrcFile($file_name);
		
		$headers_lower = array_map("strtolower", $headers);
		$headersnb = count($headers);
		
		//ignorer la ligne des unités
		fgetcsv($this->file, 65535, ',', '"');
		
		$csv_sql="";
		foreach ($counters_list_per_todo as $todo => $counters_list){
			$param=$this->params->getParamWithTodo($todo);
	        $id_group_table=$param->id_group_table;
			while(($line = fgetcsv($this->file, 65535, ',', '"'))!== FALSE){
				if(empty($line)) continue;
				
				//heure de la ligne
				preg_match("/([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2})/", $line[0],$matches);
				$year = $matches[1];
				$month = $matches[2];
				$day = $matches[3];
				$startHour = $matches[4];
				$lineHour="$year$month$day$startHour";
				$min=$matches[5];
				if($lineHour!=$this->currentHour) continue;
				
				//construction tableau associatif compteur valur
				foreach ($headers_lower as $index => $counterName) {
					$values[$counterName]=$line[$index];
				}
				$objectName=$values["object name"];
	            $equipement=$this->fillTopology($objectName,$param,$hour,$nms_table==self::NMS_TABLE_TO_CHECK);
	            if($equipement==false) continue;
	            $csv_sql .= "{$min};{$equipement};{$this->time_data}";
	            foreach ($counters_list as $counter) {
					$csv_sql .= ';' . $this->getCounterValue($counter, $values);
				}
				$csv_sql .= "\n";	
			}
			$parser_fileSqlName = Tools::getCopyFilePath($param->network[0], $todo,$hour);

			//sauvegarde dans le fichier SQL
			$this->fileSauvSql($parser_fileSqlName, $csv_sql);
		
			
			$counters_in_file=$headers;
			//Automatic mapping
			//Sauvegarder les compteurs trouvés dans le fichiers sources 
			$notCounter=explode(",", "Result Time,Granularity Period,Object Name,Reliability");
			$counters_in_file=array_diff($counters_in_file, $notCounter);
			$this->addToCountersList($counters_in_file, $nms_table, $id_group_table,$nms_table);
		
			if(!empty($this->unknownNE)){
				displayInDemon("Les elements reseaux suivants (".implode(", ",$this->unknownNE).") n'ont pas de correspondance en topologie, il ne seront pas integres\n");
			}
			
			unset($csv_sql);
		}
		fclose($this->file);
		
	}
	
	/**
	*
	* Ouvre le fichier source, détecte les erreurs liées à l'ouverture, à la récupération de la 1ère ligne et aux prérequis du traitement du fichier.
	* @param string $filename
	*/
	private function openSrcFile($filename) {
		$items = array();
		// ouverture
		$this->file = fopen($filename, "r");
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
	
	
	private function fillTopology($objectName,$param,$hour,$checkRNC=false){
			//*******************************************************************************//
		//  Test sur la présence des informations sur l'équipement
		//*******************************************************************************//

		//cellb de la forme "ZAIN_RNC/Cell:Label=3G_KABULONGA_GIRLS_3, CellID=9103"
		$expRegCellb 	= "/^([0-9a-zA-Z_-\s]*)\/([a-zA-Z0-9]*)Cell:Label=(.*), CellID=([0-9a-zA-Z_-]*)$/";
		$expRegCellbDest = "/^([0-9a-zA-Z_-\s]*)\/([a-zA-Z0-9]*)Cell:Label=(.*), CellID=([0-9a-zA-Z_-]*)\/RNC:([0-9a-zA-Z_-]*)\/DEST Cell ID:([0-9a-zA-Z_-]*)$/";
		//rnc de la forme "REKRNC/RncFunction:REKRNC" ou "KNHRNC08/BSC6900UMTSFunction:KNHRNC08"
		$expRegRnc 		= "/^([0-9a-zA-Z_-\s]*)\/.*:(.*)$/";

		//cas ou les données n'ont pas été trouvée
		if( empty($objectName) )
		{
			$message = "Les données n'ont pas été trouvées dans la ligne courante, le ligne ne peut être traité !!!";
			displayInDemon("<span style='color:red;'>{$message}</span>\n");
			return false;
		}
		//cas ou les fichiers générés sont au niveau cell base
		elseif( preg_match($expRegCellb, $objectName, $matches) )
		{
			$cell 		= $matches[4];
			$cell_label = $matches[3];
			$rnc		= $matches[1];
			$equipement = $rnc."_".$cell;
		}
		elseif( preg_match($expRegCellbDest, $objectName, $matches) )
		{
			$cell 		= $matches[4];
			$cell_label = $matches[3];
			$rnc		= $matches[1];
			// TODO A voir si on se sert un jour des parties après DEST Cell ID (voir object name)
			$equipement = $rnc."_".$cell;
		}
		//cas ou les fichiers générés sont au niveau RNC 6800 ou 6810
		elseif( preg_match($expRegRnc, $objectName, $matches) )
		{
			$rnc		= $matches[1];
			if($checkRNC){
				if(empty($this->rncList)){
					if(!in_array($rnc,$this->unknownNE))$this->unknownNE[]=$rnc;
					return false;
				}
				else{
					if(!in_array($rnc,$this->rncList)){
						if(!in_array($rnc,$this->unknownNE))$this->unknownNE[]=$rnc;
						return false;
					}
				}
			}
			$equipement = "virtual_".$rnc;
			$cell_label="";
		}
		//cas non reconnu
		else
		{
			$message = "This equipment is not valid [{$objectName}]";
			displayInDemon("<span style='color:red;'>Warning : {$message}</span>\n");
			return false;
		}
		
		$topo_array["RNC"]=$rnc;
		$topo_array["Cell"]=$equipement;
		$topo_array["Cell label"]=$cell_label;
		$param->addTopologyInfo($equipement,$topo_array,$hour);
		return $equipement;
	}
	
	
}
?>