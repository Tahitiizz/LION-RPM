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
		
		//cellb
		$paramBss = new Parameters();
		$paramBss->family = Tools::$FAMILY_CELLB;
		$paramBss->field = "cell text,hour int8,day int8,week int4,month int4,capture_duration real,capture_duration_expected real,capture_duration_real real";
		$paramBss->id_group_table = "1";
		$paramBss->network = array("cell");
		$paramBss->specific_field = "cell,hour,day,week,month,capture_duration,capture_duration_expected,capture_duration_real";
		$paramBss->topoHeader = "RNC;RNC label;Cell\n";
		$paramBss->topoCellsArray = array();
		$this->params->add($paramBss);
		
		//adj
		$paramAdj = new Parameters();
		$paramAdj->family = Tools::$FAMILY_ADJ;
		$paramAdj->field = "stc text,sourcecell text,hour int8,day int8,week int4,month int4,capture_duration real,capture_duration_expected real,capture_duration_real real";
		$paramAdj->id_group_table = "4";
		$paramAdj->network = array("stc");
		$paramAdj->specific_field = "stc,sourcecell,hour,day,week,month,capture_duration,capture_duration_expected,capture_duration_real";
		$paramAdj->topoHeader = "Source Cell;Source Target Cell\n";
		$paramAdj->topoCellsArray = array();
		$this->params->add($paramAdj);
				
		//iub
		$paramIubl = new Parameters();
		$paramIubl->family = Tools::$FAMILY_IUB;
		$paramIubl->field = "iubl text,hour int8,day int8,week int4,month int4,capture_duration real,capture_duration_expected real,capture_duration_real real";
		$paramIubl->id_group_table = "2";
		$paramIubl->network = array("iubl");
		$paramIubl->specific_field = "iubl,hour,day,week,month,capture_duration,capture_duration_expected,capture_duration_real";
		$paramIubl->topoHeader = "IUB_LINK;RNC\n";
		$paramIubl->topoCellsArray = array();
		$this->params->add($paramIubl);
		
		//iur
		$paramIurl = new Parameters();
		$paramIurl->family = Tools::$FAMILY_IUR;
		$paramIurl->field = "iurl text,hour int8,day int8,week int4,month int4,capture_duration real,capture_duration_expected real,capture_duration_real real";
		$paramIurl->id_group_table = "3";
		$paramIurl->network = array("iurl");
		$paramIurl->specific_field = "iurl,hour,day,week,month,capture_duration,capture_duration_expected,capture_duration_real";
		$paramIurl->topoHeader = "IUR_LINK;RNC\n";
		$paramIurl->topoCellsArray = array();
		$this->params->add($paramIurl);

		//lac
		$paramLac = new Parameters();
		$paramLac->family = "lac";
		$paramLac->field = "lac text,hour int8,day int8,week int4,month int4,capture_duration real,capture_duration_expected real,capture_duration_real real";
		$paramLac->id_group_table = "5";
		$paramLac->network = array("lac");
		$paramLac->specific_field = "lac,hour,day,week,month,capture_duration,capture_duration_expected,capture_duration_real";
		$paramLac->topoHeader = "LAC;RNC\n";
		$paramLac->topoCellsArray = array();
		$this->params->add($paramLac);
		
		//rac
		$paramRac = new Parameters();
		$paramRac->family = "rac";
		$paramRac->field = "rac text,hour int8,day int8,week int4,month int4,capture_duration real,capture_duration_expected real,capture_duration_real real";
		$paramRac->id_group_table = "6";
		$paramRac->network = array("rac");
		$paramRac->specific_field = "rac,hour,day,week,month,capture_duration,capture_duration_expected,capture_duration_real";
		$paramRac->topoHeader = "RAC;RNC\n";
		$paramRac->topoCellsArray = array();
		$this->params->add($paramRac);
		
		//NodeB
		$paramNodeb = new Parameters();
		$paramNodeb->family = "nodeb";
		$paramNodeb->field = "sse text,hour int8,day int8,week int4,month int4,capture_duration real,capture_duration_expected real,capture_duration_real real";
		$paramNodeb->id_group_table = "7";
		$paramNodeb->network = array("sse");
		$paramNodeb->specific_field = "sse,hour,day,week,month,capture_duration,capture_duration_expected,capture_duration_real";
		$paramNodeb->topoHeader = "SSE;SE;NodeB;RNC\n";
		$paramNodeb->topoCellsArray = array();
		$this->params->add($paramNodeb);
		
        //RNC
		$paramRnc = new Parameters();
		$paramRnc->family = "rnc";
		$paramRnc->field = "rncsse text,hour int8,day int8,week int4,month int4,capture_duration real,capture_duration_expected real,capture_duration_real real";
		$paramRnc->id_group_table = "8";
		$paramRnc->network = array("rncsse");
		$paramRnc->specific_field = "rncsse,hour,day,week,month,capture_duration,capture_duration_expected,capture_duration_real";
		$paramRnc->topoHeader = "RNCSSE;RNCSE;RNC\n";
		$paramRnc->topoCellsArray = array();
		$this->params->add($paramRnc);
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
