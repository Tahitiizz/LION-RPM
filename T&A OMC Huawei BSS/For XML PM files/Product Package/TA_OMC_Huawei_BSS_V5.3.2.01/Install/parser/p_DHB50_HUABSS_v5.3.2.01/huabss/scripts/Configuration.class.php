<?php

/**
 * 
 * Enter description here ...
 * @author y.benghabrit
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
		$paramBss->topoHeader = "BSC;BSC label;Cell;Cell label\n";
		$paramBss->topoCellsArray = array();
		$this->params->add($paramBss);
		$paramGprs = new Parameters();
		$paramGprs->family = Tools::$FAMILY_GPRS;
		$paramGprs->field = "cell text,hour int8,day int8,week int4,month int4,capture_duration real,capture_duration_expected real,capture_duration_real real";
		$paramGprs->id_group_table = "2";
		$paramGprs->network = array("cell");
		$paramGprs->specific_field = "cell,hour,day,week,month,capture_duration,capture_duration_expected,capture_duration_real";
		$paramGprs->topoHeader = "PCU;Cell;Cell label\n";
		$paramGprs->topoCellsArray = array();
		$this->params->add($paramGprs);
		$paramTrx = new Parameters();
		$paramTrx->family = Tools::$FAMILY_TRX;
		$paramTrx->field = "trxid text,hour int8,day int8,week int4,month int4,capture_duration real,capture_duration_expected real,capture_duration_real real";
		$paramTrx->id_group_table = "3";
		$paramTrx->network = array("trxid");
		$paramTrx->specific_field = "trxid,hour,day,week,month,capture_duration,capture_duration_expected,capture_duration_real";
		$paramTrx->topoHeader = "Cell;Cell label;TRX;TRX label\n";
		$paramTrx->topoCellsArray = array();
		$this->params->add($paramTrx);
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