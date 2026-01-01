<?php
/*
	16/07/2009 GHX
		- Modification des fonctions getRaws() & getKpis() pour prendre en compte les labels et prendre la bonne colonne pour le nom des compteurs
		- Modification de la fonction getRawKPIs() pour prendre en compte les modifs précédentes (fonction utilisée nulle part ??)
		- Modificaiton de la requete des sélections des KPI pour prendre en compte la famille
		
	05/03/2010 BBX
		- Ajout de la méthode "manageConnections"
		- Utilisation de la méthode "manageConnections" au lieu de DatabaseConnection afin d'éviter les instances redondantes
	08/03/2010 BBX
		- Suppression de la méthode "manageConnections"
		- Utilisation de la méthode "Database::getConnection" à la place.
*/
?>
<?php
/**
*	Classe permettant de récupérer les données pour investigation dashboard
*
*	@author	SPS 
* 	@date 28/05/2009
*	@version	CB 5.0.0.0
*	@since	CB 5.0.0.0
*/

class InvestigationModel
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
	
	// Mémorise les instances de connexions ouvertes
	private static $connections = Array();


	/**
	 * contructeur
	 * @param int $product id du produit
	 * @param int $family id de la famille
	 **/
	public function __construct($product,$family) {
		$this->product = $product;
		$this->family = $family;
		
		// Connexion à la base de données du produit
		$this->database = Database::getConnection($this->product);
		
	}
	
	/**
	 * on recupere les raws
	 * @return mixed tableau contenant les raws
	 **/
	public function getRaws() {
		$array_return = Array();
		// 11:37 16/07/2009 GHX
		// Modification de la requête pour prendre en compte les labels et modifier le nom de colonne 
		// récupéré pour le nom du compteur
		$query = "	SELECT DISTINCT sfr.id_ligne, sfr.edw_target_field_name, sfr.edw_field_name_label
					FROM sys_field_reference sfr, sys_definition_group_table sdgt
					WHERE sfr.id_group_table = sdgt.id_ligne
					AND sdgt.family = '".$this->family."'
					AND sfr.on_off = 1
					AND sfr.new_field = 0
					AND sfr.visible = 1
					ORDER BY sfr.edw_field_name_label";
		$result = $this->database->execute($query);
		while($values = $this->database->getQueryResults($result,1)) {
			$array_return[$values['id_ligne']] = array($values['edw_target_field_name'], $values['edw_field_name_label']);
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
		// 11:37 16/07/2009 GHX
		// Modification de la requete pour ajouter la colonne des labels
		// Ajout de la condition sur la famille
		$query = "	SELECT DISTINCT sdk.id_ligne, sdk.kpi_name, sdk.kpi_label
					FROM sys_definition_kpi sdk, sys_definition_group_table sdgt
					WHERE sdk.on_off = 1
					AND sdk.edw_group_table = sdgt.edw_group_table
					AND sdgt.family = '".$this->family."'
					AND sdk.new_field = 0
					AND sdk.visible = 1
					ORDER BY sdk.kpi_label";
		$result = $this->database->execute($query);
		while($values = $this->database->getQueryResults($result,1)) {
			$array_return[$values['id_ligne']] = array($values['kpi_name'], $values['kpi_label']);
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
			$counters["kpi@".$id_kpi.'@'.$kpi_name[0]] = $kpi_name[1];
		}
		
		// Parcours des raws
		foreach($this->getRaws() as $id_raw=>$raw_name){				
			$counters["raw@".$id_raw.'@'.$raw_name[0]] = $raw_name[1];
		}
		return $counters;
	}
}
