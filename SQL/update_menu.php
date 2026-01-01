<?php
include_once dirname(__FILE__)."/../php/environnement_liens.php";
include_once(REP_PHYSIQUE_NIVEAU_0."php/edw_function_menu.php");
include_once(REP_PHYSIQUE_NIVEAU_0."php/database_connection.php");

//****************************************/
// SUPPRESSIONS
//***************************************/
// Suppression du menu User Profile Management
deleteMenu (" (SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='User Profile Management') ", $database_connection);
// Suppression du menu Admin Profile Management
deleteMenu (" (SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='Admin Profile Management') ", $database_connection);
// Suppression du menu Menu Management
deleteMenu (" (SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='Menu management') ", $database_connection);
// Suppression du menu My Queries
deleteMenu (" (SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='My Queries') ", $database_connection);
// Suppression du menu Query Builder
deleteMenu (" (SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='Query Builder') ", $database_connection);
// Suppression du menu Query
deleteMenu (" (SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='Query') ", $database_connection);
// Suppression de Setup Family
deleteMenu (" (SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='Setup Family') ", $database_connection);
// Suppression de Setup Compute Config
deleteMenu (" (SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='Setup Compute Config') ", $database_connection);
// Suppression de Upload Topology 3rd axis
deleteMenu (" (SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='Upload Topology 3rd axis') ", $database_connection);
// Suppression de Setup Filter
deleteMenu (" (SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='Setup Filter') ", $database_connection);
// Suppression de Setup Filter Path
deleteMenu (" (SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='Setup Filter Path') ", $database_connection);
// Suppression de Setup Navigation
deleteMenu (" (SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='Setup Navigation') ", $database_connection);
// Suppression de System Info
deleteMenu (" (SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='System Info') ", $database_connection);

// // 16/03/2009 GHX
// Suppression des sous menus du menu ADMIN TOOL
deleteMenu (" (SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='Client Context') ", $database_connection);
deleteMenu (" (SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='Advanced Context') ", $database_connection);
deleteMenu (" (SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='Customisator Context') ", $database_connection);
deleteMenu (" (SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='Advanced Context Management') ", $database_connection);
deleteMenu (" (SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='Upload Context') ", $database_connection);

//****************************************/
// AJOUTS
//***************************************/
// Suppression du menu  Profile Management
deleteMenu (" (SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='Profile Management') ", $database_connection);
// Ajout du menu  Profile Management
$menu = array();
$menu["libelle_menu"] = "Profile Management";
$menu["droit_affichage"] = "astellia";
$menu["id_menu_parent"] = "(SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='USER')";
$menu["lien_menu"] = '/myadmin_user/intranet/php/affichage/profile_management.php';
$menu["is_profile_ref_admin"]	= 1;
$menu["position"]	= 3;
addMenu ($menu, $database_connection);
// Suppression du menu Menu management
deleteMenu (" (SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='Menu management') ", $database_connection);
// Recréation de Menu management
$menu = array();
$menu["libelle_menu"] = "Menu management";
$menu["droit_affichage"] = "astellia";
$menu["id_menu_parent"] = "(SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='USER')";
$menu["lien_menu"] = '/myadmin_user/intranet/php/affichage/menu_management.php';
$menu["is_profile_ref_admin"]	= 1;
$menu["position"]	= 4;
addMenu ($menu, $database_connection);
// Suppression du menu Query Builder
deleteMenu (" (SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='Query Builder') ", $database_connection);
// Recréation du Query Builder
$menu = array();
$menu["libelle_menu"] = "Query Builder";
$menu["droit_affichage"] = "astellia";
$menu["id_menu_parent"] = "(SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='INVESTIGATION')";
$menu["lien_menu"] = '/builder_report/intranet/php/affichage/builder_report_index.php';
$menu["is_profile_ref_user"] = 1;
$menu["is_menu_defaut"] = 1;
$menu["position"]	= 1;
addMenu ($menu, $database_connection);
// Suppression du menu Mapping
deleteMenu (" (SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='Mapping') ", $database_connection);
// Ajout du menu mapping
$menu = array();
$menu["libelle_menu"] = "Mapping";
$menu["droit_affichage"] = "astellia";
$menu["id_menu_parent"] = "(SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='TOPOLOGY')";
$menu["lien_menu"] = '/myadmin_tools/intranet/php/affichage/mapping.php';
$menu["is_profile_ref_admin"] = 1;
$menu["position"]	= 4;
addMenu ($menu, $database_connection);
// Suppression du menu Setup Products
deleteMenu (" (SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='Setup Products') ", $database_connection);
// Ajout du menu Setup Products
$menu = array();
$menu["libelle_menu"] = "Setup Products";
$menu["droit_affichage"] = "astellia";
$menu["id_menu_parent"] = "(SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='SETUP')";
$menu["lien_menu"] = '/myadmin_setup/intranet/php/affichage/setup_products.php';
$menu["is_profile_ref_admin"] = 1;
$menu["position"]	= 8;
addMenu ($menu, $database_connection);

// 16/03/2009 GHX
// Ajout du menu  Context Management
deleteMenu (" (SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='Context Management') ", $database_connection);
$menu = array();
$menu["libelle_menu"] = "Context Management";
$menu["droit_affichage"] = "astellia";
$menu["id_menu_parent"] = "(SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='ADMIN TOOL')";
$menu["lien_menu"] = '/context/index.php';
$menu["is_profile_ref_admin"]	= 1;
$menu["position"]	= 1;
addMenu ($menu, $database_connection);
?>