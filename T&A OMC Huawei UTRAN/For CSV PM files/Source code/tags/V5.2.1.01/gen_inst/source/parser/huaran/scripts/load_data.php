<?php
/**
 * Fichier de lancement des parsers de chaque fichier
 * 
 * @package Parser Huawei Utran
 * @author Sébastien CAVALIER 
 * @version 5.0.0.00
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
include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/ParserHuaRan.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/DatabaseServicesHuaRan.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/LoadDataHuaRan.class.php");


$parsersType= array(LoadData::KEY_PARSER => "ParserHuaRan");
$load_data = new LoadDataHuaRan($parsersType);
$load_data->launch();

?>