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
	- maj 21 09 2006 xavier : on supprime les enregistrements dans la table sys_user_parameter si la ligne supprimée provient d'un rapport.
	- maj 03 10 2006 xavier : le bouton save devient rouge lors de la suppression d'une ligne, forçant le clic sur save.
*/

session_start();
include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
include_once($repertoire_physique_niveau0 . "php/database_connection.php");
global $database_connection;
$ligne=$_GET['ligne'];
$colonne=$_GET['colonne'];
$idpage=$_GET['idpage'];

// on récupère le type de la  page.
$query= " select page_type from sys_pauto_page_name where id_page='$idpage' ";
$result=pg_query($database_connection,$query);
$result_array = pg_fetch_array($result, 0);
$type_page = $result_array["page_type"];

// Pour les pages de type GTM, on supprime aussi les id_data de la ligne.
if($type_page=="gtm"){
	$query_liste = " select * from sys_pauto_config where ligne='$ligne' and  id_page=".$idpage;
	$result=pg_query($database_connection,$query_liste);
	$result_nb = pg_num_rows($result);
	// MAJ de la table graph_data.
	for ($k = 0;$k < $result_nb;$k++){
		$result_array= pg_fetch_array($result, $k);
		$query_delete="delete from graph_data where id_data=".$result_array["id_data"];
		pg_query($database_connection,$query_delete);
	}
}

// Pour les pages de type REPORT, on supprime aussi le selecteur dans sys_user_parameter.
if($type_page=="report"){
	$query_liste = " select class_object, id_elem from sys_pauto_config where ligne='$ligne' and  id_page=".$idpage;
	$result=pg_query($database_connection,$query_liste);
	$result_nb = pg_num_rows($result);
	// MAJ de la table sys_user_parameter.
	for ($k = 0;$k < $result_nb;$k++){
		$result_array= pg_fetch_array($result, $k);
		$query_delete="DELETE FROM sys_user_parameter
                  WHERE id_elem='".$result_array["class_object"]."@".$result_array["id_elem"]."'
                  AND module_restitution=$idpage";
		pg_query($database_connection,$query_delete);
	}

  // maj 03 10 2006 xavier
  $query_update = "UPDATE sys_pauto_page_name SET page_mode=0 WHERE id_page=".$idpage;
	pg_query($database_connection,$query_update);
}


//echo "ligne $ligne/colonne $colonne<br>";

$query="delete  FROM sys_pauto_config  where ligne='$ligne' and  id_page=".$idpage;
pg_query($database_connection,$query);
//echo "$query<br>";
//$result=mysql_query($query);

if($type_page=="gtm"){
	// On met-à-jour la table graph_information.
	$query = "select id_data from sys_pauto_config where id_page=".$idpage;
	$result = pg_query($database_connection,$query);
	$result_nb = pg_num_rows($result);
	$id_data = "";
	if ($result_nb > 0){
		for ($k = 0;$k < $result_nb;$k++){
			$result_array= pg_fetch_array($result, $k);
			$id_data .= $result_array["id_data"].",";
		}
		$id_data = substr($id_data,0,-1);
	}
	$query = " UPDATE graph_information set ";
	$query .= " graph_data_list = '$id_data' ";
	$query .= " where id_page = '$idpage' ";
	pg_query($database_connection,$query);
}

//pour chaque ligne superieure je la descends d'un niveau
$query="select id,ligne,colonne FROM sys_pauto_config  where ligne>'$ligne' and  id_page=".$idpage;
$result=pg_query($database_connection,$query);
$result_nb = pg_num_rows($result);
//echo $query."<br>";
for ($k = 0;$k < $result_nb;$k++){
  $result_array= pg_fetch_array($result, $k);
  $ligne_sup=$result_array["ligne"];
  $colonne_sup=$result_array["colonne"];
  $id_ligne=$result_array["id"];

  $new_ligne_sup=$ligne_sup-1;
  $new_frame_position=$new_ligne_sup.':'.$colonne_sup;

  $query="update sys_pauto_config set ligne='$new_ligne_sup',frame_position='$new_frame_position' where id='$id_ligne'";
  pg_query($database_connection,$query);
  //mysql_db_query('pauto',$query);
  //echo $query."<br>";
 }
header("location:pageframe.php?action=display&id_page=" . $idpage."&id_pauto=".$_GET["id_pauto"]);
?>
