<?php
/**
 * 
 * Enter description here ...
 * @author mp.hubert
 *
 */
class LoadDataHuaBss extends LoadData{
	
	/**
	 * 
	 * Enter description here ...
	 * @var unknown_type
	 */
	
	public function LoadDataHuaBss($parsersType){
		parent::__construct($parsersType);
	}
	
	
	protected function getDatabaseServicesClassName(){
		return "DatabaseServicesHuaBss";
	}
	
	
}
?>