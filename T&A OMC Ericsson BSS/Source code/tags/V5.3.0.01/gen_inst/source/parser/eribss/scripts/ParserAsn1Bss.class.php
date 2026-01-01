<?php
/**
 * 
 * Classe de parsing de l'ASN1 TDVD et TLVL
 * @author m.diagne
 *
 */

class ParserAsn1Bss extends Parser {
	/**
	 * utilisé qu'en mono process
	 */
	public static $s_equipmentList;
	
	//nom du parser
	const PARSER_FILE_NAME="ASN1";
	public function __construct(DataBaseConnection $dbConnection,FileTypeCondition $fileType = null,$single_process_mode=TRUE,$topoFileId = null) {
		// ---- standard
		$conf = new Configuration();
		$params = $conf->getParametersList();
		$dBServices=new DatabaseServicesEriBss($dbConnection);
		parent::__construct($dBServices,$params,self::PARSER_FILE_NAME,$fileType,$single_process_mode);
		
		// ----
		//specific
		//listes des type byPassé (toujours en dur)
		$this->byPassedTypes=array_flip(explode(";","ATERTRANS;BSC;BSCPOS;BSCQOS;BSCMSLOT;EMGPRS;LOAS;TRAPCOM;LOADREG;TRALOST;SUPERCH;ABISIP;TRH;PGWLDIST;PGW;ABISTG;BSCGEN;LOASMISC;GRPSWITCH;C7RTTOTAL;C7SCCPUSE;CP;M3PERF;SCTPAM;SCTPLM;TRASEVENT;SS7SCCPUSE;TRAPEVENT;BSCGPRS;BSCGPRS2;DELSTRTBF;GPHLOADREG"));
		$this->byPassed=false;
		$this->specif_enable_adj=get_sys_global_parameters("specif_enable_adjacencies");
		$this->topoFileId = $topoFileId;
			
		if($this->retrieve_single_process){
			$this->initialisationProcess=LoadDataEriBSS::$initialisationProcess;
		}else{
			$bool=$this->processManager->getVariable('initialisationProcess');
			$this->initialisationProcess=($bool=="TRUE"?true:false) ;
		}
		

	}
	

	/**
	 * Fonction qui parse le fichier et qui va integrer dans un fichier au format csv les données issues du fichier source
	 *
	 * @param int $id_fichier numero du fichier à traiter
	 * @global text repertoire physique d'installation de l'application
	 */
	public function createCopyBody(FlatFile $flat_file, $topologyHour='ALL') {
		$hour=$flat_file->hour;
		$day = substr($hour, 0, 8); 
		$week = Date::getWeek($day);
		$month = substr($hour, 0, 6);
		
		$this->time_data = $hour.';'.$day.';'.$week.';'.$month.';'.$flat_file->capture_duration.';'.Parser::$capture_duration_expected.';'.$flat_file->capture_duration;
		
		$this->currentHour=$hour;

		//recupération  de la minute et du nom du NE
		$regExp_ASN1="/[A-Z]([0-9]{4})([0-9]{2})([0-9]{2})[.]([0-9]{2})([0-9]{2})[-]([0-9]{4})([0-9]{2})([0-9]{2})[.]([0-9]{2})([0-9]{2})[_]([A-Z0-9]*)[:][A-Z]{0,1}[0-9]{4}$/";
		preg_match($regExp_ASN1,$flat_file->uploaded_flat_file_name,$matches);
		$this->min=$matches[5];
		$this->Equipment_Name=$matches[11];
		
		$this->hierarchy=array();
		
		$filename=$flat_file->flat_file_location;
		
		
        $f = fopen($filename,'rb');
        $size = filesize($filename);
        $content = fread($f, $size);
        $memoryPointer = fopen("php://memory", 'br+');
        $test=fwrite($memoryPointer,$content);
        rewind($memoryPointer);
		unset($content);
        $this->ASN1_parse($memoryPointer,-1);
		fclose($memoryPointer);
		fclose($f);
		if (Tools::$debug) {Tools::traceMemoryUsage();}
	}
	

	
	    /**
     * @param unknown_type $data handle du fichier ASN1
     * @param unknown_type $nbOfByteToRead nombre d'octets à lire avant de sortir de la fonction (-1 signifie tout lire)
     *
     */
    function ASN1_parse($data,$nbOfByteToRead)
    {
        $pointer_position=ftell($data);
        while ((!feof($data)) )
        {
            //$class  = ord($data[0]);
            //test si on on a dèjà lu les $nbOfByteToRead octets à lire
            $current_pointer_position=ftell($data);
            if(($nbOfByteToRead>0)&&(($current_pointer_position-$pointer_position)==$nbOfByteToRead)) return 0;
            $class  = ord(fread($data,1));
            array_push($this->hierarchy, dechex($class));
            $this->path=implode("-",$this->hierarchy);
            //print_r($shift);
            switch($class)
            {
                case 0x30 :
                case 0xa0 :
                case 0xa1 :
                case 0xa3 :
                case 0xa2 :
                    $shift=$this->get_len($data);
                    if (trim($this->path)=="30-a1-30-a1-30-a2")
                    {
                        $this->waiting_counters=array();
                        $this->lines=0;
                        $this->id_counter=0;
                    }elseif (trim($this->path)=="30-a1-30-a1-30"){
                    	//debut bloc compteurs et valeurs
                    	
                    	//ecriture du bloc précédent si non vide
                    	if(count($this->csvLinePerTodo)!=0){
                    		foreach ($this->csvLinePerTodo as $todo => $csvLine) {
                    			fclose($csvLine);
                    			/*$param=$this->currentParamPerTodo[$todo];
                    			$parser_fileSqlName = Tools::getCopyFilePath($param->network[0], $todo);
					
								//sauvegarde dans le fichier SQL
								$this->fileSauvSql($parser_fileSqlName, $csvLine);*/
                    		}
                    		$this->csvLinePerTodo=array();
                    	}
                    	//nouveau bloc
                    	$this->currentParamPerTodo=array();

                    }
                    if ($shift!=0)
                    {
                        foreach ($shift as $bytes => $len)
                            $this->ASN1_parse($data,$len);
                        array_pop($this->hierarchy);
                    }

                    break;

                case 0x19:
                case 0x13:
                    //equipement list of counters; counter by counter .....
                    $shift=$this->get_len($data);
                    foreach ($shift as $bytes => $len)
                    {
                        if ($this->path=="30-a1-30-a1-30-a2-13" || $this->path=="30-a1-30-a1-30-a2-19")
                        {
                            $string_data=fread($data,$len);
                            //echo "compteur = ".$string_data."\n";
                            $this->waiting_counters[$string_data]=$this->id_counter;
                            $this->id_counter++;
                        }
                    }
                    array_pop($this->hierarchy);
                    break;

                case 0x80 :
                    $shift=$this->get_len($data);
                    foreach ($shift as $bytes => $len)
                    {
                        if($len!=0)
                            $string_data = fread($data,$len);
                        if($this->path=="30-a1-30-a1-30-80")
                        {
                            $this->nbBlocsT3=0;
                            $this->SizeBlocT3=0;
                        }
                        //echo "End Period = ".$string_data."\n";
                        // process = 1 si le type courant est à integrer 
                        else if ($this->path=="30-a1-30-a1-30-a3-30-a1-80") 
                        {
                            if($this->process==1)
                            {
                                //displayInDemon("chaine brute ".bin2hex($string_data));
                            	if((hexdec(bin2hex($string_data))=="")&&($len!=0)){
                            		$this->counterValuesArray[]=0;
                            	}else{
                            		$this->counterValuesArray[]=hexdec(bin2hex($string_data));
                            		
                            	}
                                //displayInDemon("chaine hexdec bin2hex ".hexdec(bin2hex($string_data)));
                            }
                        }
                        //End of the list of counters
                        else if ($this->path=="30-a1-30-a1-30-a3-30-80")// && $this->process==1) 
                        {
                            $net_infos=explode(".",$string_data);
                            $this->type_netInfo=explode(".",$string_data);
                            $this->current_type=$net_infos[0];
                            $this->counterValuesArray=array();
                            
                            if($this->lines==0){
                            	displayInDemon("New Type : {$this->current_type},{$this->Equipment_Name}");
                            	$this->process=0;
                            	//add counters to the list of counters found in the file (automatic mapping)
								//$this->addCountersToTheList();
								
								if($this->initialisationProcess){
									$found=$this->fillEquipmentList();
									if($found){
										$this->hierarchy=array();
										return;
									}
									
									
								}//pour éviter les valeurs doublés
								elseif (isset ($this->AlreadyProcessedType[$this->currentHour][$this->Equipment_Name][$this->current_type][$this->min])){
									displayInDemon("{$this->current_type} type has already been processed in this hour for this NE.\n ","alert");
									
								}
								else{
									
									
									$counters_list_per_todo=$this->getCptsByNmsTable($this->current_type);
									if (count($counters_list_per_todo)!=0){
										foreach ($counters_list_per_todo as $todo => $counters_list){
	                            			$param=$this->params->getParamWithTodo($todo);
	                            			$id_group_table=$param->id_group_table;
	                            			
	                            			//activation adjacencies
	                            			if(($this->specif_enable_adj==0)&&($id_group_table==3)) continue;
	                            			$this->addToCountersList(array_keys($this->waiting_counters), $this->current_type, $id_group_table,$this->current_type);
	                            			
	                            			$this->process=1;
	                            			$this->currentParamPerTodo[$todo] = $param;
	                            				
	                            		}
	                            		//type de fichier Bypass ou pas
	                            		$this->byPassed=isset($this->byPassedTypes[$this->current_type]);
	                            		//marquage du type traité
	                            		$this->AlreadyProcessedType[$this->currentHour][$this->Equipment_Name][$this->current_type][$this->min]=1;
									}
									else{
										foreach($this->params as $param){
											$this->addToCountersList(array_keys($this->waiting_counters), $this->current_type,$param->id_group_table,$this->current_type);
										}
										
									}
								}
                            	
                            }
                                $this->lines++;

                        }

                    }
                    array_pop($this->hierarchy);
                    break;

                case 0x81:
                    //equipement
                    $shift=$this->get_len($data);
                    //echo "Data=".$data."<br>";
                    foreach ($shift as $bytes => $len)
                    {
                        if($len!=0)
                            $string_data = fread($data,$len);
                        if ($this->path=="30-a0-81")
                        {
                            $this->root_net=trim($string_data);
                            echo "Root Network Element = {$string_data}, Equipment name={$this->Equipment_Name}\n";

                        }
                    }
                    array_pop($this->hierarchy);
                    break;

                case 0x82:
                    // ??
                    //displayInDemon($this->path);
                    $shift=$this->get_len($data);
                    foreach ($shift as $bytes => $len)
                    {
                        if($len!=0)
                            $string_data = fread($data,$len);
                    }

                    if ($this->path=="30-a1-30-a1-30-a3-30-82")
                    {
                        //echo "end of values block. process={$this->process}\n";
                        $this->lines++;
                        if($this->process==1)
                        {
                        	$values=array();
                        	
                        	if($this->type_netInfo[0]=="TRAPEVENT") $values["codec"]=strtolower($this->type_netInfo[1]);
                        	foreach ($this->waiting_counters as $counterName => $index) {
                        		$values[strtolower($counterName)]=$this->counterValuesArray[$index];
                        	}
                        	
                        	foreach($this->currentParamPerTodo as $todo => $param){
                        		$tempCsvline="";
                        		$networkElement_array = $this->fillTopology( $param);
                        		//$networkElement_array est généralement un tableau d'un élément sauf pour les compteurs Adjacencies déclinés en BSS
                        		$values["incoming"]=0;//compteur outgoing
                        		
                        		foreach($networkElement_array as $networkElement){
	                        		$tempCsvline.="{$this->min};{$networkElement};{$this->time_data}";
	                        		$counters_list=$param->todo[$todo];
		                        	foreach ($counters_list as $counter) {
										$tempCsvline .= ';' . $this->getCounterValue($counter, $values);
									}
									$tempCsvline .= "\n";
									$values["incoming"]=1; //compteur incoming pour le second élément du tableau
                        		}
                        		if(!isset($this->csvLinePerTodo[$todo])){
                        			$this->csvLinePerTodo[$todo]=fopen(Tools::getCopyFilePath($param->network[0], $todo,$this->currentHour), 'at');
                        			/*//30 kO de buffering better perf???
                        			$status=stream_set_write_buffer ( $this->csvLinePerTodo[$todo] , 10240 );
                        			echo "STATUS BUFFER $status\n";*/
                        		}
                        		flock($this->csvLinePerTodo[$todo], LOCK_EX);
                        		fwrite($this->csvLinePerTodo[$todo], $tempCsvline);
                        		flock($this->csvLinePerTodo[$todo], LOCK_UN);
                        	}			
                        	
							//stocker le csv
                        }
                    }
                    elseif ($this->path=="30-a1-82"){
                    	//fin de tous les blocs , fin de fichier 
                    	
                    	//ecriture du dernier csvLine si pas vide
                         //ecriture du bloc précédent si non vide
                    	if(count($this->csvLinePerTodo)!=0){
                    		foreach ($this->csvLinePerTodo as $todo => $csvLine) {
                    			fclose($csvLine);
          
                    		}
                    		$this->csvLinePerTodo=array();
                    	}
                    }
                    //valeur de compteur NULL (type 82 au lieu de 80).
                    elseif ($this->path=="30-a1-30-a1-30-a3-30-a1-82")
                    {
                        $this->counterValuesArray[]="";
                    }
                    array_pop($this->hierarchy);
                    break;

                case 0x83:
                    //vendor
                    $shift=$this->get_len($data);
                    foreach ($shift as $bytes => $len)
                    {
                        if($this->path=="30-a0-83")
                        {
                            $string_data=fread($data,$len);
                            echo "Vendor = ".$string_data."\n";
                        }
                    }
                    array_pop($this->hierarchy);
                    break;

                case 0x84:
                    //date
                    $shift=$this->get_len($data);
                    foreach ($shift as $bytes => $len)
                    {
                        if($this->path=="30-a0-84")
                        {
                            $string_data=fread($data,$len);
                        }
                    }
                    array_pop($this->hierarchy);
                    break;

                default :

                    fread($data,1);
                    array_pop($this->hierarchy);
                    array_pop($this->hierarchy);
                    break;
            }
        }
    }
	
	
    function get_len($data)
    {
        $len  = ord(fread($data,1));

        //echo $len." ==== ".base_convert($len,10,2);
        $bytes  = 0;
        if($len==128)
        {	
            //  echo "Indefinite length\n";
            return 0;			
        }		
        elseif ($len & 128) 
        {
            $bytes  = $len  & 0x0f;
            $len  = 0;
            for ($i = 0;  $i  < $bytes; $i++) 
                $len  = ($len <<  8)  | ord(fread($data,1));
            //$len  = ($len <<  8)  | ord($data[$i  + 2]);
        }
        $infos[$bytes]=$len;
        return $infos;
    }
    
	/**
	 * 
	 * gère les informations de topologie; retourne un tableau array(sourceCell,targetCell) ou un tableau d'une cellule array(Cell)
	 * @param unknown_type $values
	 * @param unknown_type $param
	 */
	private function fillTopology( $param) {
		$net_infos=$this->type_netInfo;
		$id_group_table=$param->id_group_table;
		$topoInfo=array();
		switch ($id_group_table) {
			case 1:
			case 2:
				$bsc=$this->Equipment_Name;

				if(!$this->byPassed){
					//fichier non bypassé
					
					$exploded_NEs=explode("-", $net_infos[1]);
					
					if(count($exploded_NEs)==2){
						//compteurs adjacencies décliné BSS
						$sourcecell=$exploded_NEs[0];
						$targetcell=$exploded_NEs[1];
						$topoInfo["Cell"]=$sourcecell;
						$topoInfo["BSC"]=$bsc;
						$topoInfo["PCU"]=$bsc;
						$param->addTopologyInfo($sourcecell,$topoInfo,$this->currentHour);
						
						return array($sourcecell,$targetcell);

						//retourne un tableau de 2 cellule: la premiere pour les compteurs outgoing , la seconde pour les incoming
						
					}else{
						//compteurs pur BSS
						$cell=$net_infos[1];
						$topoInfo["Cell"]=$cell;
						$topoInfo["BSC"]=$bsc;
						$topoInfo["PCU"]=$bsc;
						$param->addTopologyInfo($cell,$topoInfo,$this->currentHour);
						return array($cell);
					}

				}else{
					
					//compteur Bypassé
					$cell="virtual_{$this->Equipment_Name}";
					$topoInfo["Cell"]=$cell;
					$topoInfo["BSC"]=$bsc;
					$topoInfo["PCU"]=$bsc;
					$param->addTopologyInfo($cell,$topoInfo,$this->currentHour);
					return array($cell);
				}
				
				break ;
				
			case 3:
				//famille adjacencies
				$exploded_NEs=explode("-", $net_infos[1]);
				$sourcecell=$exploded_NEs[0];
				$targetcell=$exploded_NEs[1];
				return array("{$sourcecell}_{$targetcell};$sourcecell");
			break;	
		}

	}


	
	/**
	 * 
	 * Méthode utilisée lors de la phase d'initialisation pour remplir les listes des différents type de NE (MSC,BSC,HLR,STP,...)
	 */
	function fillEquipmentList(){

		if (($this->current_type=="BSC")||(preg_match('/BSC/i',$this->Equipment_Name)))
		{
			displayInDemon("This is a BSC file");
			$this->equipmentList["BSC"][$this->Equipment_Name]=$this->Equipment_Name;
			return true;
		}

		return false;
	}
	
	
	/**
	 * (non-PHPdoc)
	 * @see parser/eribss/scripts/lib/Parser::endOfParsingPerCondition()
	 */
	public function endOfParsingPerCondition(){
		// initialisation et multi-processus
		if(($this->initialisationProcess)&&(!$this->retrieve_single_process))
			$this->processManager->saveVariable('equipmentList',$this->equipmentList);
		elseif(($this->initialisationProcess)&&($this->retrieve_single_process))
			self::$s_equipmentList=$this->equipmentList;
	}

	

	/**
	 * 
	 * Enter description here ...
	 */
	public static function  getConditionProvider($dbServices){
		return new ASN1ConditionProvider($dbServices);
	}

}
?>