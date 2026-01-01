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
deleteMenu (" (SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='Setup Partitioning') ", $database_connection);

// 29/06/2011 BBX
// Removing menu from astellia admin profile because deleteMenu does not
// BZ 22629
$query = "DELETE FROM profile_menu_position
    WHERE id_menu = 'menu.01.0002.11'";
pg_query($database_connection, $query);

// Ajout du menu  Setup Corporate
$menu = array();
$menu["libelle_menu"] = "Setup Partitioning";
$menu["id_menu"] = "menu.01.0002.11";
$menu["droit_affichage"] = "astellia";
$menu["id_menu_parent"] = "(SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='SETUP')";
$menu["lien_menu"] = '/partitioning/index.php';
$menu["is_profile_ref_admin"]	= 1;
$menu["position"]	= 3;
addMenu ($menu, $database_connection);

//****************************************/
// MODIFICATION
//***************************************/
// 29/06/2011 BBX
// Menu Setup Partitioning must be for astellia_admin only
// BZ 22629
$query = "DELETE FROM profile_menu_position
    WHERE id_profile NOT IN (SELECT id_profile FROM profile WHERE client_type = 'protected')
    AND id_menu = 'menu.01.0002.11'";
pg_query($database_connection, $query);
?>
