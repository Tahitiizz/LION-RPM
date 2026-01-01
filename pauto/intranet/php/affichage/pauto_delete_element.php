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
	// Permet de supprimer un élément du tableau à partir de sa position
	// et de l'id de la page courrante.
	// $frame_position a pour format ligne:colonne.
	// $id_page : identifiant de la page où se trouve l'élément.
	session_start();
	include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
	include_once($repertoire_physique_niveau0 . "php/database_connection.php");
	global $database_connection;
	$id_page = $_GET['id_page'];
	$frame_position = $_GET['frame_position'];

	// On récupère la type de la page auto.
	$query=" select page_type from sys_pauto_page_name where id_page = '$id_page' ";
	$result = pg_query($database_connection,$query);
	$result_array= pg_fetch_array($result, 0);
	$id_pauto = $result_array["page_type"];

	//echo "Type page :".$id_pauto."<br>";

	if($id_pauto=="gtm"){
		$query_liste = " select * from sys_pauto_config where frame_position='$frame_position' and id_page=".$id_page;
		$result=pg_query($database_connection,$query_liste);
		$result_nb = pg_num_rows($result);
		// MAJ de la table graph_data.
		for ($k = 0;$k < $result_nb;$k++){
			$result_array= pg_fetch_array($result, $k);
			$query_delete="delete from graph_data where id_data=".$result_array["id_data"];
			pg_query($database_connection,$query_delete);
		}
		$query="delete  FROM sys_pauto_config  where frame_position='$frame_position' and id_page='$id_page'";
		pg_query($database_connection,$query);
	} else {
		$query="delete  FROM sys_pauto_config  where frame_position='$frame_position' and id_page='$id_page'";
		pg_query($database_connection,$query);
	}

	if($id_pauto=="gtm"){
		// On met-à-jour la table graph_information.
		$query = "select id_data from sys_pauto_config where id_page=".$id_page;
		$result = pg_query($database_connection,$query);
		$result_nb = pg_num_rows($result);
		$id_data = "";
		if ($result_nb > 0){
			for ($k = 0;$k < $result_nb;$k++){
				$result_array= pg_fetch_array($result, $k);
				$id_data .= $result_array["id_data"].",";
				//echo "id_data à réinsérer :".$result_array["id_data"]."<br>";
			}
			$id_data = substr($id_data,0,-1);
		}
		$query = " UPDATE graph_information set ";
		$query .= " graph_data_list = '$id_data' ";
		$query .= " where id_page = '$id_page' ";
		pg_query($database_connection,$query);
	}

	if(!isset($msg_erreur)) $msg_erreur = "";

	header("location:pageframe.php?action=display&id_page=" . $id_page."&id_pauto=".$id_pauto.$msg_erreur);
?>
