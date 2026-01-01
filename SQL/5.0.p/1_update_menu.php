<?php
include_once dirname(__FILE__)."/../../php/environnement_liens.php";
include_once(REP_PHYSIQUE_NIVEAU_0."php/edw_function_menu.php");
include_once(REP_PHYSIQUE_NIVEAU_0."php/database_connection.php");

//****************************************/
// SUPPRESSIONS
//***************************************/

//****************************************/
// AJOUTS
//***************************************/
// Suppression du menu  Setup Corporate
deleteMenu (" (SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='Setup Corporate') ", $database_connection);
// Ajout du menu  Setup Corporate
$menu = array();
$menu["libelle_menu"] = "Setup Corporate";
// 05/03/2010 NSE bz 14366 : on lui attribue l'id_menu défini
$menu["id_menu"] = "menu.01.0002.09";
$menu["droit_affichage"] = "astellia";
$menu["id_menu_parent"] = "(SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='SETUP')";
$menu["lien_menu"] = '/corporate/index.php';
$menu["is_profile_ref_admin"]	= 1;
$menu["position"]	= 3;
addMenu ($menu, $database_connection);

//****************************************/
// AJOUTS : BZ 12148 => menu non présent si patch seul
//***************************************/
// Suppression du menu  NE Color
deleteMenu (" (SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='Setup NE Color') ", $database_connection);
// Ajout du menu  Setup NE Color
$menu = array();
$menu["libelle_menu"] = "Setup NE Color";
// 05/03/2010 NSE bz 14366 : on lui attribue l'id_menu défini
$menu["id_menu"] = "menu.01.0008.05";
$menu["droit_affichage"] = "astellia";
$menu["id_menu_parent"] = "(SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='TOPOLOGY')";
$menu["lien_menu"] = '/myadmin_tools/intranet/php/affichage/admintool_setup_color.php';
$menu["is_profile_ref_admin"]	= 1;
$menu["position"]	= 4;
addMenu ($menu, $database_connection);

// 11:01 17/11/2009 GHX
// Ajout du menu Setup Mixed KPI
// Suppression du menu  Setup Mixed KPI
deleteMenu (" (SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='Setup Mixed KPI') ", $database_connection);
// Ajout du menu  Setup Mixed KPI
$menu = array();
$menu["libelle_menu"] = "Setup Mixed KPI";
// 05/03/2010 NSE bz 14366 : on lui attribue l'id_menu défini
$menu["id_menu"] = "menu.01.0002.10";
$menu["droit_affichage"] = "astellia";
$menu["id_menu_parent"] = "(SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='SETUP')";
$menu["lien_menu"] = '/mixed_kpi/index.php';
$menu["is_profile_ref_admin"]	= 1;
addMenu ($menu, $database_connection);

// 30/11/2009 BBX
// BZ 13116
// Menu Download Topo Third Axis dans tous les cas. 
$query = "SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='Download Topology 3rd axis'";
$result = pg_query($database_connection,$query);
// Si le menu n'existe pas
if(pg_num_rows($result) == 0)
{
	// Ajout du menu  Download Topology 3rd axis
	$menu = array();
	$menu["libelle_menu"] = "Download Topology 3rd axis";
	// 05/03/2010 NSE bz 14366 : on lui attribue l'id_menu défini
	$menu["id_menu"] = "menu.01.0008.03";
	$menu["droit_affichage"] = "astellia";
	$menu["id_menu_parent"] = "(SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='TOPOLOGY')";
	$menu["lien_menu"] = '/myadmin_tools/intranet/php/affichage/admintool_download_topology.php?axe3=true';
	$menu["is_profile_ref_admin"]	= 1;
	$menu["position"]	= 4;
	$menu["niveau"]	= 2;
	addMenu ($menu, $database_connection);
}
// Menu Setup Busy Hour dans tous les cas. 
$query = "SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='Setup Busy Hour'";
$result = pg_query($database_connection,$query);
// Si le menu n'existe pas
if(pg_num_rows($result) == 0)
{
	// Ajout du menu Setup Busy Hour
	$menu = array();
	$menu["libelle_menu"] = "Setup Busy Hour";
	// 05/03/2010 NSE bz 14366 : on lui attribue l'id_menu défini
	$menu["id_menu"] = "menu.01.0002.06";
	$menu["droit_affichage"] = "astellia";
	$menu["id_menu_parent"] = "(SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='SETUP')";
	$menu["lien_menu"] = '/myadmin_setup/intranet/php/affichage/setup_bh_index.php';
	$menu["is_profile_ref_admin"]	= 1;
	$menu["position"]	= 6;
	$menu["niveau"]	= 2;
	addMenu ($menu, $database_connection);
}
// Menu Setup NE Color dans tous les cas. 
$query = "SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='Setup NE Color'";
$result = pg_query($database_connection,$query);
// Si le menu n'existe pas
if(pg_num_rows($result) == 0)
{
	// Ajout du menu Setup Busy Hour
	$menu = array();
	$menu["libelle_menu"] = "Setup NE Color";
	// 05/03/2010 NSE bz 14366 : on lui attribue l'id_menu défini
	$menu["id_menu"] = "menu.01.0008.05";
	$menu["droit_affichage"] = "astellia";
	$menu["id_menu_parent"] = "(SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='TOPOLOGY')";
	$menu["lien_menu"] = '/myadmin_tools/intranet/php/affichage/admintool_setup_color.php';
	$menu["is_profile_ref_admin"]	= 1;
	$menu["position"]	= 4;
	$menu["niveau"]	= 2;
	addMenu ($menu, $database_connection);
}
// FIN BZ 13116

// 11/06/2010 - MPR : DE Source Availability - Mise à jour des menus Setup Connections et Setup Data Files
$query = "UPDATE profile_menu_position SET position = 1
            WHERE id_menu = (
                    SELECT id_menu
                    FROM menu_deroulant_intranet
                    WHERE libelle_menu = 'Setup System Alerts'
         )";
pg_query($database_connection,$query);

$query = "UPDATE menu_deroulant_intranet SET libelle_menu = 'Setup Data Files', position = 1 WHERE libelle_menu = 'Setup System Alerts'";
pg_query($database_connection,$query);

$query = "UPDATE menu_deroulant_intranet SET libelle_menu = 'Setup Connections' WHERE libelle_menu = 'Setup Connection'";
pg_query($database_connection,$query);

// 11/02/2011 - SPD : correction BZ 14637 - "Menu management" => "Menu Management"
$query = "UPDATE menu_deroulant_intranet SET libelle_menu='Menu Management' WHERE libelle_menu='Menu management'";
pg_query($database_connection,$query);
$query = "UPDATE menu_deroulant_intranet SET libelle_menu='Group Management' WHERE libelle_menu='Group management'";
pg_query($database_connection,$query);

?>