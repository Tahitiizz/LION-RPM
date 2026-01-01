<?php
/**
 * Gere la creation des tables par famille à partir des tables contenant les données issues des fichiers source
 * 
 * @package Parser Huawei Utran
 * @author Matthieu HUBERT 
 * @version 5.2.0.00
 *
 */

class create_temp_table_omc extends CreateTempTable {
	/**
	 * constructeur qui fait appel aux fonctions génériques du Composant de Base
	 */
	function __construct($networkMinLevel,$single_process_mode) {
		$conf = new Configuration();
		$this->params = $conf->getParametersList();
		parent::__construct($networkMinLevel,$single_process_mode);
	}

	/**
	 * Fonction qui défini sous forme de tableau pour chaque group de table les jointures à affectuer entre les tables contenant les données des fichiers sources
	 *
	 * @param int $group_table_param identifiant du group table
	 */
	function get_join($group_table_param)
	{ 
		// pour les group tables générées à partir de plusieurs tables, on definit la jointure
		$param = $this->params->getWithGroupTable($group_table_param);
		$this->setJoinDynamic($param);
		if (Tools::$debug) {
			displayInDemon(__METHOD__ . " DEBUG : jointure[] puis specific_fields[]");
			var_dump($this->jointure);
			var_dump($this->specific_fields);
		}
	} 

	/**
	 * Fonction qui va mettre à jour les tables de topologie de référence en executant des requetes SQL à la fin du traitement du create_temp_table
	 * 
	 * @param int $group_table_param identifiant du group table
	 * @param text $table_object_ref nom de la table topologie de reference pour l'identifiant du group table
	 * @param text $table_object nom de la table TEMPORAIRE topologie de reference pour l'identifiant du group table
	 * @param int $day jour traité
	 * @global ressource identifiant de connection à la BDD
	 */
	function MAJ_objectref_specific($day="")
	{    
		//displayInDemon("<br>\n");
    	//MAJ VENDOR
		//récupération du nom du vendor renseigné dans Sys_global_parameters
    	//$vendor_name = get_sys_global_parameters('vendor_name');
    	//Insertion du vendor s'il n'existe pas dans la topo
    	//$id_vendor = $this->insertNaInTopoIsNotExist("vendor", $vendor_name, $vendor_name, $day);
		//$this->majArcNeMaxUnique("rnc", "vendor", $id_vendor);
		
		//MAJ NETWORK
		//$id_network = $this->insertNaInTopoIsNotExist("network", "MyNetwork", "MyNetwork", $day);
		//$this->majArcNeMaxUnique("rnc", "network", $id_network);
		//$this->majArcNeMaxUnique("rnc", "network");
		
		
		$vendorName = parent::MAJ_objectref_specific();
		
		$this->createUtranArc($vendorName);
		
		//désactivation des cellules (RNC) virtuel
		//$sql_virtuel = "UPDATE edw_object_ref SET eor_on_off = 0 WHERE eor_id LIKE '%_RNC' AND eor_label LIKE '%_RNC';";
		//$this->execRequeteAvecErreur($sql_virtuel);
		
    } 
 

    
    /**
    *
    * Méthode statique executée ...
    */
    public static function execute() {
    	parent::execute(get_class());
    }
    
} 

?>