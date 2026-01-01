<?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
*	- maj 05/02/2008 christophe : utilisation de la fonction deleteMenu pour supprimer le menu d'un dashboard.
*
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
	- maj 22 05 2006 christophe : on empêche la suppression si c'est un gtm qui est contenu dans un dahsboard.
	- maj 21 09 2006 xavier : on supprime les enregistrements dans la table sys_user_parameter si le rapport est supprimé.
*/
// Supprime la page passée en paramètre. (id_page).
session_start();
include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/database_connection.php");
// maj 05/02/2008 christophe : utilisation de la fonction deleteMenu pour supprimer le menu d'un dashboard.
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/edw_function_menu.php");	

global $database_connection;

$id_page =	$_GET['id_page'];
$family = 	$_GET["family"];

// on récupère le type de la  page.
$query = 	" select page_type from sys_pauto_page_name where id_page='$id_page' ";
$result = 	pg_query($database_connection,$query);
$result_array = pg_fetch_array($result, 0);
$type_page = 	$result_array["page_type"];

/*
	Pour les types GTM on vérifie si le graphse trouve dans un dashboard.
	Si c'est le cas, on interdit la suppression.
*/
if($type_page == "gtm"){
	$query = "
		SELECT page_name,id_page FROM sys_pauto_page_name
			WHERE id_page IN
				(SELECT id_page FROM sys_pauto_config WHERE id_elem IN
					(SELECT id_graph FROM graph_information WHERE id_page=".$id_page.")
				)
	";

	$result = pg_query($database_connection,$query);
	$result_nb = pg_num_rows($result);
	if($result_nb > 0){
		$msg_erreur = "Deletion forbidden. Current graph used in dashboard.";
		header("location:pageframe.php?msg_erreur=$msg_erreur&action=display&id_page=".$id_page."&id_pauto=".$type_page."&family=".$family);
		exit;
	}
}

// Gestion des suppressions supplémentaires pour certains type de pages auto.
if($type_page == "page"){
	
	// Liste des id_menu du dashboard.
	$query= " SELECT id_menu FROM menu_deroulant_intranet WHERE id_page='$id_page' ";
	$result=pg_query($database_connection,$query);
	$result_nb = pg_num_rows($result);
	for ($k = 0;$k < $result_nb;$k++){ // liste des id_menu à supprimer.
		$result_array = pg_fetch_array($result, $k);
		// maj 05/02/2008 christophe : utilisation de la fonction deleteMenu pour supprimer le menu d'un dashboard.
		deleteMenu ($result_array["id_menu"], $database_connection);
	}

	// On supprime le options overtime.
	$query="delete FROM sys_definition_theme where id_theme=".$id_page;
	pg_query($database_connection,$query);
}
if($type_page == "gtm"){

	// On supprime les enregistrements de la table graph_information et graph_data.
	$query_delete = " delete from graph_data where id_data in (select id_data from sys_pauto_config where id_page='$id_page') ";
	pg_query($database_connection,$query_delete);
	// 2. On supprime le graph de la table graph_information.
	$query_delete="delete from graph_information where id_page=".$id_page;
	pg_query($database_connection,$query_delete);
}
// Pour les pages de type REPORT, on supprime aussi le selecteur dans sys_user_parameter.
if($type_page=="report"){
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
}


	// On supprime les enregistrements la table pauto_config et
	// pauto_page_name.
	// on supprime les graph contenu dans la page
	$query="delete  FROM sys_pauto_config where id_page=".$id_page;
	pg_query($database_connection,$query);

	// On supprime les enregistrements dans la table sys_internal_id.
	$query_delete = "
		DELETE FROM sys_internal_id WHERE internal_id IN (SELECT internal_id FROM sys_pauto_page_name where id_page='$id_page')
	";
	pg_query($database_connection,$query_delete);

	// on supprime la page
	$query=" DELETE  FROM sys_pauto_page_name where id_page=".$id_page;
	pg_query($database_connection,$query);



header("location:pageframe.php?action=new"."&id_pauto=".$_GET["id_pauto"]."&family=".$family);
?>
