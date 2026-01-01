<?
/*
*	@cb41000@
*
*	Composant de base version cb_4.1.0.00
* 
*	- 25/11/2008 - SLC - gestion multi-produit
* 
*/
?>
<?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
	- maj 10/01/2008, benoit : dans le cas du builder report, on bascule les infos du tableau de session Excel du builder report vers celui du    graph
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
<?
/*
* Gestion de l'ajout / suppression d'éléments dans le caddy
* @author christophe chaput
* @version V1.0 2005-05-16
*/

session_start();
include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
include_once($repertoire_physique_niveau0 . "php/database_connection.php");

global $test_temp;

// Action de la page (ajouter, supprimer, vider).
$action = $_GET["action"];

switch ($action) {
	case "ajouter" :
		if($_GET["object_type"] == "builder_report"){
			$query="select object_source from sys_contenu_buffer where id_contenu=".$_GET["object_id"];
			$result=pg_query($database_connection,$query);
			$result_array = pg_fetch_array($result, 0);
			$object_id=$result_array["object_source"];
			//$object_id=$_GET["object_id"];
		} else {
			$object_id=$_GET["object_id"];
		}
		// On vérifie si le graph est déjà dans le caddy.
		$query_search=" select * from sys_panier_mgt where object_id = '$object_id' ";
		/*if($_GET["object_type"]=="table"){
			$object_id= addslashes($str[$_GET["object_id"]]);
		} else {
			$object_id=$_GET["object_id"];
		}*/
		$result = pg_query($database_connection,$query_search);

		//echo pg_num_rows($result);
		if(pg_num_rows($result) <= 0){
			// Ajout d'un nouvel élément dans le caddy.
			$id_user=$_GET["id_user"];
			$object_page_from=$_GET["object_page_from"];
			$object_type=$_GET["object_type"];
			$object_title=$_GET["object_title"];
			$object_summary=$_GET["object_summary"];

			// 10/01/2008 - Modif. benoit : dans le cas du builder report, on bascule les infos du tableau de session Excel du builder report vers celui du graph
			
			if ($object_page_from == "Builder report") {
				
				// On extrait l'identifiant unique du graphe à partir de '$object_id' et on le stocke dans la variable '$object_summary' qui va servir de clé du tableau de session Excel
				
				$object_id_tab = explode('.', $object_id);
				$id_graph = $object_summary = $object_id_tab[0];
				
				// On transfère les informations entre les 2 tableaux de session
				
				$tableau_excel_export_ordonnee = array();
				
				for ($i=1;$i<count($_SESSION['tableau_legend_export_excel'][0]);$i++){
					$tableau_excel_export_ordonnee[] = $_SESSION['tableau_legend_export_excel'][0][$i];
				}
				$_SESSION['tableau_data_excel_ordonnee'][$id_graph] = $tableau_excel_export_ordonnee;
							
				$_SESSION['tableau_data_excel_abscisse'][$id_graph] = $_SESSION['tableau_abscisse_export_excel'][0];
			
				$tableau_excel_export = array();

				for ($i=1; $i < count($_SESSION['tableau_data_export_excel'][0]); $i++) {
					$tableau_excel_export[] = $_SESSION['tableau_data_export_excel'][0][$i];
				}
				$_SESSION['tableau_data_excel'][$id_graph] = $tableau_excel_export;		
			}

			$query="insert into sys_panier_mgt  (id_user,object_page_from,object_type,object_title,object_id,object_summary) values ('$id_user','$object_page_from','$object_type','$object_title','$object_id','$object_summary')";
			pg_query($database_connection,$query);
		}
		break;
	case "supprimer" :
		// Suppression d'un élément du caddy.
		$id_user=$_GET["id_user"];
		$object_id=$_GET["object_id"];

		$query="delete from sys_panier_mgt where id_user='$id_user' and object_id='$object_id' ";
		pg_query($database_connection,$query);
		break;
	case "vider" :
		// Permet de supprimer tous les élément sdu caddy.
		$id_user=$_GET["id_user"];

		$query="delete from sys_panier_mgt  where id_user='$id_user'";
		pg_query($database_connection,$query);
		break;
}
?>
