<?php
/**
 * Fichier de lancement de la creation de tables temporaires par famille de données
 * Inclus par le CB pour créer les tables
 * 
 * @package Parser Ericsson LTE 5.3
 * @author mdiagne 
 * @version 5.3
 *
 */

include_once(dirname(__FILE__)."/../../../php/environnement_liens.php");

// recherche du nom du parser
$module = strtolower(get_sys_global_parameters("module"));

// include des fichiers nécessaires
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/edw_function_family.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/database_connection.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/deploy_and_compute_functions.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/postgres_functions.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/create_temp_table_generic.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/lib/IncludeAll.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/Configuration.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/create_temp_table_omc.class.php");

//debug de ce script
Tools::$debug = get_sys_debug('retrieve_copy_from_temp_data');

//traitements
create_temp_table_omc::execute();


?>
