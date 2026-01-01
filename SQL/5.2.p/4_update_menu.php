<?php
include_once dirname(__FILE__)."/../../php/environnement_liens.php";
include_once(REP_PHYSIQUE_NIVEAU_0."php/edw_function_menu.php");
include_once(REP_PHYSIQUE_NIVEAU_0."php/database_connection.php");

$database_connection = DataBase::getConnection();


//****************************************/
// MODIFICATIONS
//***************************************/

$logOutIdMenu = $database_connection->getOne("SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='Log Out'");
$database_connection->execute("UPDATE menu_deroulant_intranet set position = 4 WHERE id_menu='$logOutIdMenu'");
$database_connection->execute("UPDATE profile_menu_position set position = 4 WHERE id_menu='$logOutIdMenu'");

// 02/07/2013 MGO Bz 33439 Renommage de Graph Builder de l'administrateur
$graphAdminMenu = $database_connection->getOne("SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='Graph Builder' AND is_profile_ref_admin=1");
$database_connection->execute("UPDATE menu_deroulant_intranet set libelle_menu='GRAPH Builder' WHERE id_menu='$graphAdminMenu'");


//****************************************/
// SUPPRESSIONS
//***************************************/

// Suppression du menu  Setup Corporate
// 22/11/2012 Bz 30452 il faut supprimer l'ancienne version du menu Switch to another profile ou on a deux fois le menu apres migration GW
deleteMenu (" SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='Switch to another profile' AND id_menu='menu.01.0011' ", $database_connection);

//****************************************/
// AJOUTS
//***************************************/



?>
