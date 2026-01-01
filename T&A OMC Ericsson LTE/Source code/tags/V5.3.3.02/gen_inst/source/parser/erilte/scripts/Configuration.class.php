<?php

/**
 * 
 * Enter description here ...
 * @author mdiagne
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
		$paramCellb = new Parameters();
		$paramCellb->family = Tools::$FAMILY_CELLB;
		$paramCellb->field = "cell text,hour int8,day int8,week int4,month int4,capture_duration real,capture_duration_expected real,capture_duration_real real";
		$paramCellb->id_group_table = "1";
		$paramCellb->network = array("cell");
		$paramCellb->specific_field = "cell,hour,day,week,month,capture_duration,capture_duration_expected,capture_duration_real";
		$paramCellb->topoHeader = "eNodeB;Cell\n";
		$paramCellb->topoCellsArray = array();
		$this->params->add($paramCellb);
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