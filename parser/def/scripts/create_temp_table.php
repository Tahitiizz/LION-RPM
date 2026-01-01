<?
/*
*	@gsm3.0.0.00@
*
*	10:04 04/08/2009 SCT
*		- mise à niveau sur CB 5.0
*		- la nouvelle classe d'appel aux données n'est pas utilisée : le fichier fait appel à une classe CB pas encore modifiée
*/
?>
<?php
/**
 * Fichier de lancement de la creation de tables temporaires par famille de données
 * 
 * @package Create_Tables_GSM
 * @author Guillaume Houssay 
 * @version 2.0.0.10
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
include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/create_temp_table_$module.class.php");

$creation_table_by_group_table = new create_temp_table_def();

?>
