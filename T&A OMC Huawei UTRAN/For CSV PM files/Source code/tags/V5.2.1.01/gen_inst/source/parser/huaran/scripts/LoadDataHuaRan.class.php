<?php
/**
 * 
 * Enter description here ...
 * @author mp.hubert
 *
 */
class LoadDataHuaRan extends LoadData{
	
	/**
	 * 
	 * Enter description here ...
	 * @var unknown_type
	 */
	
	public function LoadDataHuaRan($parsersType){
		parent::__construct($parsersType);
	}
	
	
	protected function getDatabaseServicesClassName(){
		return "DatabaseServicesHuaRan";
	}
	
	
}
?>