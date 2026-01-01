<?php


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

		$vendorName = parent::MAJ_objectref_specific();
		
		$this->createUtranArc($vendorName);

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
