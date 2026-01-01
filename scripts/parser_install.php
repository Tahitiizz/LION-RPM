<?
/*
*	@cb50000@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_5.0.0.00
*
*	10:53 29/05/2009 SCT : 
*		- changement de nom du fichier
*	- CCT1 28/08/09 :  on ne fait plus de déploy dans l'install parser car cela est déjà fait dans le contexte.
*	09:38 24/09/2009 SCT : CE SCRIPT EST OBSELETE => 
*		+ LE DEPLOIEMENT S'EFFECTUE PAR LE MONTAGE DU CONTEXTE
+		+ LES REQUETES SQL RESTANT SERVENT A PREPARER LE DEPLOIEMENT DU PRODUIT (DEJA EFFECTUER PAR LE MONTAGE DU CONTEXTE)
*/
?>
<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*/
?>
<?php
set_time_limit(3600);
include_once(dirname(__FILE__)."/../php/environnement_liens.php");
include_once($repertoire_physique_niveau0 . "php/database_connection.php");
include_once($repertoire_physique_niveau0 . "php/edw_function.php");
include_once($repertoire_physique_niveau0."class/deploy.class.php");
include_once($repertoire_physique_niveau0 . "php/postgres_functions.php");
include_once($repertoire_physique_niveau0."php/edw_function_family.php");
include_once($repertoire_physique_niveau0."php/deploy_and_compute_functions.php");

// 09:40 24/09/2009 SCT => OBSELETE
//deploiement des tables du parsers
$query = "TRUNCATE sys_definition_group_table";
pg_query($database_connection, $query);
// 09:40 24/09/2009 SCT => OBSELETE
$query = "INSERT INTO sys_definition_group_table SELECT * FROM sys_definition_group_table_ref";
pg_query($database_connection, $query);

// 09:40 24/09/2009 SCT => OBSELETE
//activation du deploiment
$query = "UPDATE sys_definition_group_table SET raw_deploy_status = 1, kpi_deploy_status = 1";
pg_query($database_connection, $query);
// 09:40 24/09/2009 SCT => OBSELETE
$query = "UPDATE sys_definition_group_table_network SET deploy_status = 0";
pg_query($database_connection, $query);
// 09:40 24/09/2009 SCT => OBSELETE
$query = "UPDATE sys_definition_group_table_time SET deploy_status = 0";
pg_query($database_connection, $query);

// CCT1 28/08/09 :  on ne fait plus de déploy dans l'install parser car cela est déjà fait dans le contexte.
/*
$query = "SELECT DISTINCT id_ligne FROM sys_definition_group_table";
// //////echo $query."<br>";
$result = pg_query($database_connection, $query);
$nombre_resultat = pg_num_rows($result);
unset($ta_list);
if($nombre_resultat > 0)
{
	for($i = 0; $i < $nombre_resultat; $i++)
	{
		$row = pg_fetch_array($result, $i);
		$id_gt[]= $row["id_ligne"];
	}
}
for($k=0; $k<count($id_gt); $k++)
{
	$deploy = new deploy($database_connection, $id_gt[$k]);
	if(count($deploy->types) > 0)
	{
		$deploy->operate();
		$deploy->display(1);
	}
}

//deploie les colonnes
$query = "UPDATE sys_field_reference SET new_field = 1, on_off = 1";
pg_query($database_connection, $query);
exec("php -q ".$repertoire_physique_niveau0 . "scripts/clean_tables_structure.php");
*/
?>