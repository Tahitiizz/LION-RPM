<?php

/**
 * Fichier de lancement des parsers de chaque fichier OMC
 *
 * @package Parser NSN RAN 5.0
 * @author MDE
 *
 *
 */

include_once(dirname(__FILE__)."/../../../php/environnement_liens.php");

// recherche du nom du parser
$module = strtolower(get_sys_global_parameters("module"));

// include des fichiers nécessaires
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/edw_function_family.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/deploy_and_compute_functions.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/postgres_functions.php");

// includes des fichiers de Topology
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/topology/TopologyLib.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/topology/Topology.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/topology/TopologyCheck.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/topology/TopologyCorrect.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/topology/TopologyAddElements.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/topology/TopologyChanges.class.php");

include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/lib/IncludeAll.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/Configuration.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/ParserEricssonRan.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/XMLConditionProvider.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/DatabaseServicesEriRan.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/LoadDataEriRan.class.php");

$iurlinkParam = get_sys_global_parameters('specif_enable_iurlink');
$iublinkParam = get_sys_global_parameters('specif_enable_iublink');
$adjacenciesParam = get_sys_global_parameters('specif_enable_adjacencies');
$lacParam = get_sys_global_parameters('specif_enable_lac');
$racParam = get_sys_global_parameters('specif_enable_rac');
$nodebParam = get_sys_global_parameters('specif_enable_nodeb');	

if($adjacenciesParam == 0){
		$message = "The Adjacencies stat option is disabled. No data will be integrated for this family";
     	displayInDemon(__METHOD__ . " WARNING : $message");
}

if($iublinkParam == 0){	
	$message = "The IUB stat option is disabled. No data will be integrated for this family";
     displayInDemon(__METHOD__ . " WARNING : $message");
}

if($iurlinkParam == 0){
	$message = "The IUR stat option is disabled. No data will be integrated for this family";
     displayInDemon(__METHOD__ . " WARNING : $message");
}


if($racParam == 0){
	$message = "The RAC stat option is disabled. No data will be integrated for this family";
     displayInDemon(__METHOD__ . " WARNING : $message");
}

if($lacParam == 0){
	$message = "The LAC stat option is disabled. No data will be integrated for this family";
     displayInDemon(__METHOD__ . " WARNING : $message");
}
if($nodebParam == 0) {
	$message = "The Node-B stat option is disabled. No data will be integrated for this family";
     displayInDemon(__METHOD__ . " WARNING : $message");
}

$parsersType= array(LoadData::KEY_PARSER => "ParserEricssonRan");
$load_data = new LoadDataEriRan($parsersType);
$load_data->launch();

?>
