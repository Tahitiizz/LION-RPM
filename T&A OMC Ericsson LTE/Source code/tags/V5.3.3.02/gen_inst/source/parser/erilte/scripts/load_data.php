<?php

/**
 * Fichier de lancement des parsers de chaque fichier OMC
 *
 * @package Parser Ericsson LTE 5.3
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
include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/ParserEricssonLTE.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/XMLConditionProvider.class.php");


$parsersType= array(LoadData::KEY_PARSER => "ParserEricssonLTE");
$load_data = new LoadData($parsersType);
$load_data->launch();



?>
