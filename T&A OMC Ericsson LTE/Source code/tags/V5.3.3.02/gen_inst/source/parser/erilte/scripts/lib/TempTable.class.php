<?php
/**
 * 
 * Classe représentant les conditions d’éligibilité des tables temporaires présentes dans
 * la table sys_w_tables_list : en fonction des conditions qu'on lui attribue, un processus 
 * create_temp_table enfant va savoir s'il doit traiter telle ou telle table temporaire. 
 *
 */
class TempTableCondition {
	
	/**
	 * Heure intégrée, ex : 2012122500
	 */
	public $hour;
	
	/**
	 * Niveau réseau minimum, ex : cell
	 */
	public $networkMinLevel;
	
	/**
	 * Entier identifiant une famille, ex : 1 pour la famille BSS
	 */
	public $id_group_table;

	/**
	 * Constructor
	 */
	public function __construct($hour, $networkMinLevel, $id_group_table) {
		$this->hour=$hour;
		$this->networkMinLevel=$networkMinLevel;
		$this->id_group_table=$id_group_table;
	}
	
	/**
	 * Méthode destinée à restreindre les heures à traiter au sein de la 
	 * table sys_w_tables_list.
	 * Cette méthode est destinée à être utilisée dans une surcharge de la 
	 * méthode « CreateTempTable->get_hours » du CB.
	 */
	public function getDBHourCondition() {
		$expression="hour={$this->hour}";
		return $expression;
	}
	
	
	/**
	 * Méthode destinée à restreindre les familles à traiter au sein de la 
	 * table sys_w_tables_list.
	 * Cette méthode est destinée à être utilisée dans une surcharge de la 
	 * méthode « CreateTempTable->get_group_table_from_w_table_list » du CB.
	 */
	public function getDBGroupTableCondition() {
		$expression="group_table='{$this->id_group_table}'";
		return $expression;
	}
	
	
	/**
	 * Méthode destinée à restreindre les niveaux réseaux minimum à traiter au sein de la 
	 * table sys_w_tables_list.
	 * Cette méthode est destinée à être utilisée dans une surcharge de la 
	 * méthode « CreateTempTable->get_network_level_by_group_table_from_w_table_list » du CB.
	 */
	public function getDBNetworkMinLevelCondition() {
		$expression="network='{$this->networkMinLevel}'";
		return $expression;
	}
	
}
?>