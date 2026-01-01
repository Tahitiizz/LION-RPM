<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*/
?>
<?
/*
*	@cb2000b_iu2000b@
*
*	20/07/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.0.0
*
*	Parser version iu_2.0.0.0
*/
?>
<?php
  /**
   * @package deploy
   *
   * 02/11/2005 : ajout d'include pour utiliser dans la classe les fonctions du 3eme axe
   * 15 06 2006 : MD - modification du script pour lancer le deploiement de toutes les tables de sys_group_table
   */

include_once(dirname(__FILE__)."/../php/environnement_liens.php");
include_once($repertoire_physique_niveau0."php/database_connection.php");
include_once($repertoire_physique_niveau0."php/postgres_functions.php");
include_once($repertoire_physique_niveau0."php/edw_function.php");
include_once($repertoire_physique_niveau0."php/edw_function_family.php");
include_once($repertoire_physique_niveau0."php/deploy_and_compute_functions.php");
include_once($repertoire_physique_niveau0."class/deploy.class.php");

$database = Database::getConnection();

echo "Chargement de la base \"$DBName\" ($DBHost:$DBport)<br>";

$res = pg_query($database_connection,"select distinct id_ligne from sys_definition_group_table");
while ($row = pg_fetch_array($res)){
        // 19/05/2011 BBX - PARTITIONING -
        // On peut désormais passer une instance de connexion
	$deploy=new deploy($database,$row['id_ligne']);
	if(count($deploy->types)>0)
		$deploy->operate();

	$deploy->display(1);
}

//////////////////////////////////////////////
// pour manipuler indépendamment les index  //
//////////////////////////////////////////////

// $todo["group"]=array("data_type1","data_type2,...)
// exemple : $todo["edw_astellia_0"]=array("raw","kpi");

//$todo["edw_astellia_0"]=array("raw");

//$deploy->create_indexes("drop",$todo);

//////////////////////////////////////////////
//          pour vider les tables           //
//////////////////////////////////////////////

// $todelete["group"]=array("data_type1","data_type2,...)
// exemple : $todelete["edw_astellia_0"]=array("raw","kpi");

//$todelete["edw_astellia_0"]=array("raw","kpi");
//$deploy->delete_tables($todelete);

?>
