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
*	@cb1300p_gb100b_060706@
*
*	06/07/2006 - Copyright Acurio
*
*	Composant de base version cb_1.3.0.0p
*
*	Parser version gb_1.0.0b
*/
?>
<?
/*
*	16/06/2006 - Copyright Acurio
*
*	Composant de base version cb_1.2.0.2p
*
*	Parser version gb_1.0.0b
*/
?>
<?
/*
	- maj 07 07 2006 christophe : on supprime les enregistrements dans la table sys_definition_theme si le dashboard est vidé.
	- maj 21 09 2006 xavier : on supprime les enregistrements dans la table sys_user_parameter si le rapport est vidé.
	- maj 03 10 2006 xavier : le bouton save devient rouge lors du reset d'un rapport.
*/

// Supprime tous les éléments de la page passée en paramètre.
session_start();
include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
include_once($repertoire_physique_niveau0 . "php/database_connection.php");
global $database_connection;

$id_page=$_GET['id_page'];
$family = $_GET["family"];


// Gestion de la suppression des graph pour le type id_pauto = gtm.
if($_GET["id_pauto"]=="gtm"){
	// On supprime les enregistrements de la table graph_information et graph_data.
	// 1 . on recherche les id_data et on les suppriment supprimer.
	/*$query = " select graph_data_list from graph_information where id_page = ".$id_page;
	$result = pg_query($database_connection,$query);
	$result_array = pg_fetch_array($result, 0);
	$liste_id_data = $result_array["graph_data_list"];
	$liste_id_data = explode(",",$liste_id_data);*/
	//for($i=0; $i < count($liste_id_data); $i++){
		// On supprime les éléments de la table graph_data.
		//$query_delete="delete from graph_data where id_data=".$liste_id_data[$i];
		$query_delete = " delete from graph_data where id_data in (select id_data from sys_pauto_config where id_page='$id_page') ";
		pg_query($database_connection,$query_delete);
	//}
	// 2. On supprime la liste des id_data de la table graph_information.
	$id_data = "";
	$query = " UPDATE graph_information set ";
	$query .= " graph_data_list = '$id_data'";
	$query .= " where id_page = '$id_page' ";
	pg_query($database_connection,$query);
} else if($_GET["id_pauto"]=='page'){

	// On met id_homepage à 0.
	$query="update sys_global_parameters set value='0' where parameters='id_homepage'";
	pg_query($database_connection,$query);
	// on MAJ la page.
	$query="update sys_pauto_page_name set online=0 where id_page='$id_page' ";
	pg_query($database_connection,$query);

	// On supprime les enregistrements dans sys_definition_theme
	$query_delete = " DELETE FROM sys_definition_theme WHERE id_theme=$id_page ";
	pg_query($database_connection,$query_delete);

	// On supprime l'id_menu correspondant dans menu_deroulant_intranet, profile et profile_menu_position
	// On supprime les enregistrements de profile et menu_deroulant_intranet.
	// 1 . on récupère la liste des id_menu à supprimer.
	$query= " select id_menu from menu_deroulant_intranet where id_page='$id_page' ";
	$result=pg_query($database_connection,$query);
	$result_nb = pg_num_rows($result);
	for ($k = 0;$k < $result_nb;$k++){
		$result_array = pg_fetch_array($result, $k);
		$tab_id_menu[] = $result_array["id_menu"];
	}
	// 2 . On supprime tous ces id_menu des profils.
	$query=" select * from profile ";
	$result=pg_query($database_connection,$query);
	$nb_result= pg_num_rows($result);
	if(isset($tab_id_menu)){
		for ($j = 0;$j < $nb_result;$j++) {
			$result_array = pg_fetch_array($result, $j);
			$profil = $result_array["profile_to_menu"];
			$id_profile = $result_array["id_profile"];
			$profil = explode("-",$profil);
			$new_profil = "";
			for($i=0;$i<count($profil);$i++){
					if(!in_array($profil[$i], $tab_id_menu)){
							$new_profil.=$profil[$i]."-";
					}
			}
			$new_profil = substr($new_profil,0,-1);
			// MAJ des id_menu dans la table profile.
			$query=" update profile set profile_to_menu='$new_profil' where id_profile='$id_profile' ";
			pg_query($database_connection,$query);
		} // boucle sur tous les profils
	}
	// On supprime les id_menu dans la table profile_menu_position.
	$query= " select id_menu from menu_deroulant_intranet where id_page='$id_page' ";
	$result=pg_query($database_connection,$query);
	$result_nb = pg_num_rows($result);
	for ($k = 0;$k < $result_nb;$k++){ // liste des id_menu à supprimer.
		$result_array = pg_fetch_array($result, $k);
		$query_delete = " delete from profile_menu_position where id_menu = ". $result_array["id_menu"];
		pg_query($database_connection,$query_delete);
	}

	// On supprime la(les) pages du menu.
	$query="delete  FROM menu_deroulant_intranet where id_page=".$id_page;
	pg_query($database_connection,$query);

}

// Pour les pages de type REPORT, on supprime aussi le selecteur dans sys_user_parameter.
if($_GET["id_pauto"]=="report"){
	$query_liste = " select class_object, id_elem from sys_pauto_config where id_page=".$id_page;
	$result=pg_query($database_connection,$query_liste);
	$result_nb = pg_num_rows($result);
	// MAJ de la table sys_user_parameter.
	for ($k = 0;$k < $result_nb;$k++){
		$result_array= pg_fetch_array($result, $k);
		$query_delete="DELETE FROM sys_user_parameter
                  WHERE id_elem='".$result_array["class_object"]."@".$result_array["id_elem"]."'
                  AND module_restitution=$id_page";
		pg_query($database_connection,$query_delete);
	}

  // maj 03 10 2006 xavier
	$query_update = "UPDATE sys_pauto_page_name SET page_mode=0 WHERE id_page=".$id_page;
	pg_query($database_connection,$query_update);
}

// On supprime les enregistrements la table pauto_config
$query="delete  FROM sys_pauto_config where id_page=".$id_page;
pg_query($database_connection,$query);

header("location:pageframe.php?action=display&id_page=".$id_page."&id_pauto=".$_GET["id_pauto"]."&family=".$family);
?>
