<?php

/**
 * 
 * Enter description here ...
 * @author Matthieu HUBERT
 *
 */
class Configuration {
	/**
	 * 
	 * Enter description here ...
	 * @var unknown_type
	 */
	private $params;
	
	/**
	 * 
	 * Enter description here ...
	 */
	public function __construct() {
	
		/*************************************************************************************************************/
		//   LISTE DES ENTITES
		/*************************************************************************************************************/
		//CONFIGURATION
		$this->params = new ParametersList();
		
		//cellb
		$paramRan = new Parameters();
		$paramRan->family = Tools::$FAMILY_CELLB;
		$paramRan->field = "cell text,hour int8,day int8,week int4,month int4,capture_duration real,capture_duration_expected real,capture_duration_real real";
		$paramRan->id_group_table = "1";
		$paramRan->network = array("cell");
		$paramRan->specific_field = "cell,hour,day,week,month,capture_duration,capture_duration_expected,capture_duration_real";
		$paramRan->topoCellsArray = array();
		$paramRan->topoHeader = "RNC;Cell;Cell label\n";
		
		$this->params->add($paramRan);
	}
	
	/**
	 * 
	 * Enter description here ...
	 * @return $params Liste des paramètres (objets Parameters)
	 */
	public function getParametersList() {
		return $this->params;
	}
	
}

?>