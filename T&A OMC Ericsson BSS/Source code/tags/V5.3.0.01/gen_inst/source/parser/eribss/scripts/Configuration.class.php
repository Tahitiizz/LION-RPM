<?php

/**
 * 
 * Enter description here ...
 * @author g.francois
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
		$paramBss = new Parameters();
		$paramBss->family = Tools::$FAMILY_BSS;
		$paramBss->field = "cell text,hour int8,day int8,week int4,month int4,capture_duration real,capture_duration_expected real,capture_duration_real real";
		$paramBss->id_group_table = "1";
		$paramBss->network = array("cell");
		$paramBss->specific_field = "cell,hour,day,week,month,capture_duration,capture_duration_expected,capture_duration_real";
		$paramBss->topoHeader = "BSC;Cell\n";
		$paramBss->topoCellsArray = array();
		$this->params->add($paramBss);
		
		$paramGprs = new Parameters();
		$paramGprs->family = Tools::$FAMILY_GPRS;
		$paramGprs->field = "cell text,hour int8,day int8,week int4,month int4,capture_duration real,capture_duration_expected real,capture_duration_real real";
		$paramGprs->id_group_table = "2";
		$paramGprs->network = array("cell");
		$paramGprs->specific_field = "cell,hour,day,week,month,capture_duration,capture_duration_expected,capture_duration_real";
		$paramGprs->topoHeader = "PCU;Cell\n";
		$paramGprs->topoCellsArray = array();
		$this->params->add($paramGprs);
		
		
				//adj
		$paramAdj = new Parameters();
		$paramAdj->family = Tools::$FAMILY_ADJ;
		$paramAdj->field = "stc text,sourcecell text,hour int8,day int8,week int4,month int4,capture_duration real,capture_duration_expected real,capture_duration_real real";
		$paramAdj->id_group_table = "3";
		$paramAdj->network = array("stc");
		$paramAdj->specific_field = "stc,sourcecell,hour,day,week,month,capture_duration,capture_duration_expected,capture_duration_real";
		$paramAdj->topoHeader = "Source Cell;Source Target Cell\n";
		$paramAdj->topoCellsArray = array();
		$this->params->add($paramAdj);
		

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