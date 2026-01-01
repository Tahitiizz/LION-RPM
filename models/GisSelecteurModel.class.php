<?php
/**
*	Classe permettant de récupérer les données pour investigation dashboard
*
* 02/08/2011 MMT Bz 22614: utilisation de edw_field_name au lieu de nms_field_name pour liste des raw GIS
*
*	@author	MPR
* 	@date 05/06/2009
*	@version	CB 5.0.0.0
*	@since	CB 5.0.0.0
*/

class GisModel
{
	/**
	 * id du produit
	 * @var int
	 **/
	private $product;
	
	/**
	 * id de la famille
	 * @var int
	 **/
	private $family;
	
	/**
	 * connexion a la base
	 * @var DatabaseConnection
	 **/ 
	private $database = null;


	/**
	 * contructeur
	 * @param int $product id du produit
	 * @param int $family id de la famille
	 **/
	public function __construct($product,$family) {
		$this->product = $product;
		$this->family = $family;
		
		// Connexion à la base de données du produit
                // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
		$this->database = Database::getConnection($this->product);
		
	}
	
	/**
	 * on recupere les raws
	 * @return mixed tableau contenant les raws
	 **/
	public function getRaws() {
		$array_return = Array();
		// 02/08/2011 MMT Bz 22614: utilisation de edw_field_name au lieu de nms_field_name pour liste des raw GIS
		$query = "	SELECT DISTINCT sfr.id_ligne, sfr.edw_field_name, sfr.edw_field_name_label
					FROM sys_field_reference sfr, sys_definition_group_table sdgt
					WHERE sfr.id_group_table = sdgt.id_ligne
					AND sdgt.family = '".$this->family."'
					AND sfr.on_off = 1
					AND sfr.new_field = 0
					AND sfr.visible = 1
					ORDER BY sfr.edw_field_name";
		$result = $this->database->execute($query);
		while($values = $this->database->getQueryResults($result,1)) {
			$array_return[$values['id_ligne']] = $values['edw_field_name']."||".$values['edw_field_name_label'];
		}
		//tableau du type t[id_raw] = [nom_raw]
		return $array_return;
	}
	
	
	/**
	 * on recupere les kpis
	 * @return mixed tableau contenant les kpis
	 **/
	public function getKpis() {
		$array_return = Array();
		$_module = get_sys_global_parameters('module','gsm', $this->product);
		
		$query = "	SELECT DISTINCT sdk.id_ligne, sdk.kpi_name, sdk.kpi_label
					FROM sys_definition_kpi sdk
					WHERE sdk.on_off = 1
					AND sdk.new_field = 0
					AND sdk.edw_group_table = 'edw_{$_module}_{$this->family}_axe1'
					AND sdk.visible = 1
					ORDER BY sdk.kpi_name";

		$result = $this->database->execute($query);
		while($values = $this->database->getQueryResults($result,1)) {
			$array_return[$values['id_ligne']] = $values['kpi_name']."||".$values['kpi_label'];
		}
		//tableau du type t[id_kpi] = [nom_kpi]
		return $array_return;
	}
	
	
	/**
	 * on fait un tableau contenant les raws et les kpis
	 * @return mixed tableau contenant les raws et les kpis
	 **/
	public function getRawKPIs() {
		$counters = array();
		// Parcours des kpis
		foreach($this->getKpis() as $id_kpi=>$kpi_name){				
			$counters["kpi@".$id_kpi] = "{$kpi_name}";
		}
		
		// Parcours des raws
		foreach($this->getRaws() as $id_raw=>$raw_name){				
			$counters["raw@".$id_raw] = "{$raw_name}";
		}
		return $counters;
	}

}
?>