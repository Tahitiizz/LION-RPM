<?php

/**
 * Fichier de lancement des parsers de chaque fichier OMC
 *
 * @package Parser Ericsson BSS 5.1
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
include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/ParserAsn1Bss.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/DatabaseServicesEriBss.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/ASN1ConditionProvider.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/LoadDataEriBSS.class.php");

//fichier ASN1 
$parsersType= array(LoadData::KEY_PARSER => "ParserAsn1Bss");


$load_data = new LoadDataEriBSS($parsersType);
$load_data->launch();







?>
