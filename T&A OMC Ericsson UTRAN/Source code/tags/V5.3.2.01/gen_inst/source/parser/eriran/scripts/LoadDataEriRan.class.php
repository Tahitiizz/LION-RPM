<?php
/**
 * 
 * Enter description here ...
 * @author y.beghabrit
 *
 */
class LoadDataEriRan extends LoadData{
	
	/**
	 * 
	 * Enter description here ...
	 * @var unknown_type
	 */
	public static $initialisationProcess;
	
	public function LoadDataEriRan($parsersType){
		parent::__construct($parsersType,"ExecCopyQueryEriRan");
	}
	
	
	
	
	protected function getDatabaseServicesClassName(){
		return "DatabaseServicesEriRan";
	}
	


}
?>