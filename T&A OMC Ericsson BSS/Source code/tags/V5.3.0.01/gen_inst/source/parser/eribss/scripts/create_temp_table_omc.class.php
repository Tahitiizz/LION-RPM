<?php
/**
 * Gere la creation des tables par famille à partir des tables contenant les données issues des fichiers source
 *
 * @package Parser Huawei BSS 5.0
 * @author Stéphane Lesimple 
 * @version 5.00.00.04
 *
 *	11:20 15/06/2009 SCT
 *		- mise à niveau sur CB 5.0
 *		- la nouvelle classe d'appel aux données n'est pas utilisée : le fichier fait appel à une classe CB pas encore modifiée
 * 11-12-2006 GH : mise à jour automatique des 2 nouveaux niveaux d'agregation 'network' pour les familles Phone (group table 6) et SMS Center (group table 4)
 * 16:00 17/10/2008 SCT : ajout de 2 nouvelles familles (NSS LAC et NSS BSC) dans les fonctions get_join et MAJ_objectref_specific
 * 22/12/2008 - MPR : Modification des requêtes spécifiques qui mettent à jour les éléments réseau en fonction des combinaisons générées par les fichiers de données ( cb4.1 nouvelle structure de la topologie)
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
	 * Fonction qui va mettre à jour les tables de topologie de référence en executant des requetes SQL
	 * Appellée par ./class/create_temp_table_generic.class.php 
	 * @param int $day jour traité
	 */
	public function MAJ_objectref_specific($day="") {
		$vendorName = parent::MAJ_objectref_specific();
		$this->createBssArc($vendorName);
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
