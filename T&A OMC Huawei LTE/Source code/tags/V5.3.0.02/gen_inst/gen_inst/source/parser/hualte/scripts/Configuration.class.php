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
		//cellba
		$paramCellba = new Parameters();
		$paramCellba->family = "cellba";
		$paramCellba->field = "cell text,hour int8,day int8,week int4,month int4,capture_duration real,capture_duration_expected real,capture_duration_real real";
		$paramCellba->id_group_table = "1";
		$paramCellba->network = array("cell");
		$paramCellba->specific_field = "cell,hour,day,week,month,capture_duration,capture_duration_expected,capture_duration_real";
		$paramCellba->topoHeader = "eNodeB;eNodeB Label;Cell;Cell Label\n";
		$paramCellba->topoCellsArray = array();
		$this->params->add($paramCellba);
		
		//cellbb
		$paramCellbb = new Parameters();
		$paramCellbb->family = "cellbb";
		$paramCellbb->field = "cell text,hour int8,day int8,week int4,month int4,capture_duration real,capture_duration_expected real,capture_duration_real real";
		$paramCellbb->id_group_table = "2";
		$paramCellbb->network = array("cell");
		$paramCellbb->specific_field = "cell,hour,day,week,month,capture_duration,capture_duration_expected,capture_duration_real";
		$paramCellbb->topoHeader = "eNodeB;eNodeB Label;Cell;Cell Label\n";
		$paramCellbb->topoCellsArray = array();
		$this->params->add($paramCellbb);
		
		//cellbc
		$paramCellbc = new Parameters();
		$paramCellbc->family = "cellbc";
		$paramCellbc->field = "cell text,hour int8,day int8,week int4,month int4,capture_duration real,capture_duration_expected real,capture_duration_real real";
		$paramCellbc->id_group_table = "3";
		$paramCellbc->network = array("cell");
		$paramCellbc->specific_field = "cell,hour,day,week,month,capture_duration,capture_duration_expected,capture_duration_real";
		$paramCellbc->topoHeader = "eNodeB;eNodeB Label;Cell;Cell Label\n";
		$paramCellbc->topoCellsArray = array();
		$this->params->add($paramCellbc);
		
		//cellbd
		$paramCellbd = new Parameters();
		$paramCellbd->family = "cellbd";
		$paramCellbd->field = "cell text,hour int8,day int8,week int4,month int4,capture_duration real,capture_duration_expected real,capture_duration_real real";
		$paramCellbd->id_group_table = "4";
		$paramCellbd->network = array("cell");
		$paramCellbd->specific_field = "cell,hour,day,week,month,capture_duration,capture_duration_expected,capture_duration_real";
		$paramCellbd->topoHeader = "eNodeB;eNodeB Label;Cell;Cell Label\n";
		$paramCellbd->topoCellsArray = array();
		$this->params->add($paramCellbd);
		
		//enodeb
		$paramEnodeb = new Parameters();
		$paramEnodeb->family = "enodeb";
		$paramEnodeb->field = "enodeb text,hour int8,day int8,week int4,month int4,capture_duration real,capture_duration_expected real,capture_duration_real real";
		$paramEnodeb->id_group_table = "5";
		$paramEnodeb->network = array("enodeb");
		$paramEnodeb->specific_field = "enodeb,hour,day,week,month,capture_duration,capture_duration_expected,capture_duration_real";
		$paramEnodeb->topoHeader = "eNodeB;eNodeB Label\n";
		$paramEnodeb->topoCellsArray = array();
		$this->params->add($paramEnodeb);
		
		//adjacencies
		$paramAdj = new Parameters();
		$paramAdj->family = Tools::$FAMILY_ADJ;
		$paramAdj->field = "stc text,sourcecell text,hour int8,day int8,week int4,month int4,capture_duration real,capture_duration_expected real,capture_duration_real real";
		$paramAdj->id_group_table = "6";
		$paramAdj->network = array("stc");
		$paramAdj->specific_field = "stc,sourcecell,hour,day,week,month,capture_duration,capture_duration_expected,capture_duration_real";
		$paramAdj->topoHeader = "SourceCell;SourceTargetCell\n";
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