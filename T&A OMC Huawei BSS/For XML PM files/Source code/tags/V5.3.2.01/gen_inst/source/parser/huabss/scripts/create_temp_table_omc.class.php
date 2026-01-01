<?php
/**
 * Gere la creation des tables par famille à partir des tables contenant les données issues des fichiers source
 *
 * @package Parser Huawei BSS 5.2
 * @author Matthieu Hubert 
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
	 * 16:02 17/10/2008 SCT : ajout des 2 nouvelles familles (NSS LAC et NSS BSC)
	 *
	 * @param int $group_table_param identifiant du group table
	 */
	public function get_join($group_table_param) {
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
	 * Fonction qui va mettre à jour les tables de topologie de référence en executant des requetes SQL
	 * Appellée par ./class/create_temp_table_generic.class.php 
	 * @param int $day jour traité
	 */
	public function MAJ_objectref_specific($day="") {
//		// Connexion à la base de données locale
//		$database = new DatabaseConnection();
//		//Traitement qui permet la mise à jour des liens BSC vers Vendor et MSC vers Vendor (voir Univers des familles Bss GSM et Bss GPRS)
//		$vendor_name = get_sys_global_parameters('vendor_name');
//		//On teste si le vendor a été créé en topo
//		$query_vendor = "SELECT * FROM edw_object_ref WHERE eor_obj_type = 'vendor' AND eor_id = '$vendor_name'";
//		$resultv    = $database->executeQuery($query_vendor);
//		//Si le vendor n'existe pas on l'ajoute
//		if ($database->getNumRows() == 0) {
//			$query_vendor_insert  = "INSERT INTO edw_object_ref (eor_date, eor_blacklisted, eor_on_off, eor_obj_type, eor_id, eor_label, eor_id_codeq) VALUES (TO_CHAR(NOW(), 'YYYYMMDD'), 0, 1, 'vendor', '$vendor_name', '$vendor_name', NULL)";
//			$database->executeQuery($query_vendor_insert);
//			displayInDemon(__METHOD__." :: vendor '$vendor_name' ajouté à edw_object_ref");
//		}
//		else { displayInDemon(__METHOD__." :: vendor '$vendor_name' déjà présent dans edw_object_ref"); }

		$vendorName = parent::MAJ_objectref_specific();
		
		$this->createBssArc($vendorName);
		//AJOUT DES ARCS MANQUANTS
//		foreach (array('bsc','pcu') as $na) {
//			$query_arcs = "INSERT INTO edw_object_arc_ref
//				(select eor_id, '".$vendor_name."', '$na|s|vendor'
//				FROM edw_object_ref r
//				LEFT OUTER JOIN edw_object_arc_ref a ON r.eor_id = a.eoar_id AND a.eoar_arc_type = '$na|s|vendor'
//				WHERE r.eor_obj_type = '$na' AND eoar_id IS NULL)";
//			$database->executeQuery($query_arcs);
//		}

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