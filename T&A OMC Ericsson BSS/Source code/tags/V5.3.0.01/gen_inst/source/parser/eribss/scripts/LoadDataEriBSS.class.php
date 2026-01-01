<?php
/**
 * 
 * Enter description here ...
 * @author m.diagne
 *
 */
class LoadDataEriBSS extends LoadData{
	
	/**
	 * 
	 * Enter description here ...
	 * @var unknown_type
	 */
	public static $initialisationProcess;
	
	public function LoadDataEriBSS($parsersType){
		parent::__construct($parsersType);
	}
	
	
	
	
	protected function getDatabaseServicesClassName(){
		return "DatabaseServicesEriBss";
	}
	
	/**
	 * (non-PHPdoc)
	 * @see parser/eribss/scripts/lib/LoadData::onParsingStart()
	 */
	protected function onParsingStart(){
		
		$recentlyAddedNE = get_sys_global_parameters("specif_recentlyAddedNE");
		$Asn1HoursCollected=$this->dbServices->getHoursOfAsn1FilesCollected();
		if(($Asn1HoursCollected!=0)&&(get_sys_global_parameters("specif_eribss_expreg_bsc")=="")){
			
			if($this->retrieve_single_process){
				self::$initialisationProcess=true;
			}else{
				$this->processManager->saveVariable('initialisationProcess',"TRUE");
			}
			
			
			$message = "Start of Global Parameter List of BSC to collect Initialization";
		    sys_log_ast("Info", $system_name, "Data Collect", $message, "support_1", "");
		    $message ="No data (Counters & KPI) will be integrated from ASN1 files during this phase";
		    sys_log_ast("Info", $system_name, "Data Collect", $message, "support_1", "");

		}else{
			if($recentlyAddedNE!=""){
				$this->dbServices->keepFilesOftheLast2HourCollected($recentlyAddedNE);
				$this->dbServices->emptyRecentlyAddedNEParameter();
			}
			
			if($this->retrieve_single_process){
				self::$initialisationProcess=false;
			}else{
				$this->processManager->saveVariable('initialisationProcess',"FALSE");
			}
		}
	}
	
	protected function onParsingEnd(){
		
		if($this->retrieve_single_process){
			$initialisationProcess=self::$initialisationProcess;
		}else{
			$bool=$this->processManager->getVariable('initialisationProcess');
			$initialisationProcess=($bool=="TRUE"?true:false) ;
		}
			
		if($initialisationProcess){
			if($this->retrieve_single_process)
				$equipmentList=ParserAsn1Bss::$s_equipmentList;
			else
				$equipmentList=$this->processManager->getVariable('equipmentList');
			
			//générer les traces logs apres la phase d'initialisation
			$system_name = get_sys_global_parameters("system_name");
			foreach (array("BSC") as $neType) {
				
			    if(isset($equipmentList["$neType"])){
					//list of $neType
					$ne_array_list=array_keys($equipmentList["$neType"]);
					$nbOfNE=count($ne_array_list);
					$NE_list_text=implode(',',$ne_array_list);
					$NE_list_reg_exp=implode('|',$ne_array_list);
					
					if(preg_match("/^([[:alnum:]]|[|])+$/", $NE_list_reg_exp))
			        {
			        	$parameter="specif_eribss_expreg_bsc";
			            $this->dbServices->setGlobalParameterValue($parameter,$NE_list_reg_exp);
			            $message = "$nbOfNE $neType found: $NE_list_text.";
			            sys_log_ast("Info", $system_name, "Data Collect", $message, "support_1", "");
			            $message = "Global Parameter List of $neType to collect has been successfully initialized.";
			            sys_log_ast("Info", $system_name, "Data Collect", $message, "support_1", "");
			            $message = "Missing $neType? Log on Administration page and update Global Parameters List of $neType to collect from Process Settings tab (add |<missing $neType name> at the end of the current value)";
			            sys_log_ast("Info", $system_name, "Data Collect", $message, "support_1", "");
			            $message = "Counters & KPI based on ASN1 files will be available for those $neType from the next Collect";
			            sys_log_ast("Info", $system_name, "Data Collect", $message, "support_1", "");
			        }
			        else
			        {
			            $message = "Global Parameter List of $neType to collect Initialization failed";
			            sys_log_ast("Info", $system_name, "Data Collect", $message, "support_1", "");
			        }
			    }
			}
			
			//on vide le parametre $recentlyAddedNE
			$this->dbServices->emptyRecentlyAddedNEParameter();
		}
	}
}
?>