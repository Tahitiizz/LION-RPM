<?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*/
?>
<?php
/*
*	@cb21002@
*
*	23/02/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.0.02
*
*	- modif 08:49 14/09/2007 Gwen : ajout des echo
*	- modif 11:42 22/08/2007 Gwénaël :
*			- nouveau fichier
*			- utilisation des classes de la Topology pour la mise à jour des tables edw_object_X_ref
*/
?>
<?php
include_once(dirname(__FILE__)."/../php/environnement_liens.php");
include_once($repertoire_physique_niveau0 . "php/database_connection.php");
include_once($repertoire_physique_niveau0 . "php/edw_function.php");
include_once($repertoire_physique_niveau0 . "php/edw_function_family.php");
include_once($repertoire_physique_niveau0 . "class/AbstractTopology.class.php");
include_once($repertoire_physique_niveau0 . "class/Topology.class.php");
include_once($repertoire_physique_niveau0 . "class/TopologyCheck.class.php");
include_once($repertoire_physique_niveau0 . "class/TopologyChanges.class.php");
include_once($repertoire_physique_niveau0 . "class/TopologyThirdAxis.class.php");
include_once($repertoire_physique_niveau0 . "class/TopologyReflectChanges.class.php");
include_once($repertoire_physique_niveau0 . "class/TopologyCorrect.class.php");

$nb_flat_file = get_sys_global_parameters("nb_flat_file_uploaded");

if ( $nb_flat_file != 0 ) {

	/*
	 * Sert uniquement à afficher le mode de l'upload dans le démon
	 * (et aussi en mode débugge pour remplir la table sys_topology_trace)
	 * le nom du mode peut-être changé
	 */
	$mode = 'retrieve';

	/*
	 * Type de mise à jour
	 * 16  = mise à jour des tables edw_object_X_ref
	 * Les autres types de mise à jour correspond aux uploads :  manuel/auto/maj familles secondaires
	 */
	$type_maj = 16;

	/*
	 * Nom de la famille principale
	 * Ici on met le nom de la famille principale pour pouvoir mettre à jour les familles secondaires
	 */
	$main_family = get_main_family();

	/*
	 * Nom de la table edw_object_X_ref de la famille principale
	 */
	$query = "
			SELECT object_ref_table
			FROM sys_definition_categorie
			WHERE main_family = 1
				AND family = '".$main_family."'
			LIMIT 1
		";
	$result = pg_query($database_connection, $query);
	list($table_ref) = pg_fetch_array($result, 0);
	unset($result);

	/*
	 * Les délimiteurs possibles sont , et ;
	 * (ici le choix a peu d'importance car on ne charge pas de fichier)
	 */
	$delimiter = ';';

	/** Création de l'objet **/
	$topo = new Topology();

	/** Initialisation de certains paramètres **/
	$topo->set_mode($mode);
	$topo->set_family($main_family);
	$topo->set_type_maj($type_maj);
	$topo->set_delimiter($delimiter);
	$topo->set_table_ref($table_ref);
	$topo->set_db_connection($database_connection);
	$topo->set_rep_niveau0($repertoire_physique_niveau0);

	/************************************************************/
	/** MISE A JOUR DE LA FAMILLE PRINCIPALE & SECONDAIRES **/
	/************************************************************/

	/** Effectuer la correction des tables edw_object_X_ref (complète les chemins et les labels) **/
	echo 'Mise à jour de topologie pour la famille principale et les familles secondaires ... ';
	$topo->loadParser();
	echo 'Fin<br>';

	/****************************************************/
	/** MISE A JOUR DES FAMILLES NON SECONDAIRES **/
	/****************************************************/

	// On récupère la liste des NA de la famille principale
	$na = getNaLabelList('na',$main_family);

	// Requête qui permet de récupérer les familles non secondaires
	// Une famille est dite secondaire si son niveau minimum est présent dans les niveaux d'aggrégation réseaux de la famille principale (utilisation de l'opérateur IN)
	// Il suffit donc de prendre l'inverse, les familles dont le niveau minimum n'est pas présent (utilisation de l'opérateur NOT IN)
	$query = "
			SELECT family, object_ref_table
			FROM sys_definition_categorie
			WHERE main_family = 0
				AND network_aggregation_min NOT IN ('". implode("','", array_keys($na[$main_family])) ."')
				AND on_off = 1
		";
	$result = pg_query($database_connection, $query);

	// Si on a un résultat
	if ( pg_num_rows($result) > 0 ) {
		// On parcourt chaque ligne ...
		while ( list($family, $object_ref_table) = pg_fetch_array($result) ) {
			// On spécifie la famille à mettre à jour
			$topo->set_family($family);
			// On spécifie le nom de la table edw_object_X_ref correspondant à la famille
			$topo->set_table_ref($object_ref_table);
			// ... et on effectue les corrections (complète les chemins et les labels)
			// Chaque appel de cette fonction sera considére comme un upload différent dans le démon
			echo 'Mise à jour de topologie pour la famille '.$family.' ... ';
			$topo->loadParser();
			echo 'Fin<br>';
		}
	}
} // Fin if $nb_flat_file
else {
    print "No update as no flat file uploaded<br>";
}
?>
