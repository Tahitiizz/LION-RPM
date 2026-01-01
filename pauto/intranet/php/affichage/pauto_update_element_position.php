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
		Mise-à-jour de la position des éléments
		dans un page builder.

		@author : christophe
	*/

	session_start();
	include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
	include_once($repertoire_physique_niveau0 . "php/database_connection.php");
	include_once($repertoire_physique_niveau0 . "php/edw_function.php");
	global $database_connection;

	// Récupération des données transmises par l'URL.
	$ligne = $_GET["numero_ligne"];		// n uméro de la ligne à modifier.
	$id_page = $_GET["id_page"]; 		// identifiant de la page auto.
	$sens = $_GET["sens"]; 				// sens.
	$id_pauto = $_GET["id_pauto"];		// type de la page auto
	//$family = $_GET["family"];				// nom de la famille

	// Debug
	//echo "Infos : sens ". $sens. ", numero_ligne ". $numero_ligne. ", id_page ". $id_page. ", id_pauto ". $id_pauto. ", family ". $family; exit;
	/*echo "Infos : sens ". $sens. ", numero_ligne ". $numero_ligne. ", id_page ". $id_page. ", id_pauto ". $id_pauto;
	exit;*/

	// On met-à-jour toutes les positions.
	if($sens == "up"){
		$query = " update sys_pauto_config set ligne=ligne+1 where id_page = '$id_page' and ligne = '$ligne' ";
		pg_query($database_connection,$query);
		$frame_contenu_new = $ligne.":0";
		$frame_contenu = ($ligne+1).":0";
		$query = " update sys_pauto_config set ligne=ligne-1, frame_position='$frame_contenu_new' where id_page = '$id_page' and frame_position='$frame_contenu' ";
		pg_query($database_connection,$query);
		$query = " update sys_pauto_config set frame_position='$frame_contenu'  where id_page = '$id_page' and ligne = ".($ligne + 1);
		pg_query($database_connection,$query);
	} else { // down
		$query = " update sys_pauto_config set ligne=ligne-1 where id_page = '$id_page' and ligne = '$ligne' ";
		pg_query($database_connection,$query);
		$frame_contenu_new = $ligne.":0";
		$frame_contenu = ($ligne-1).":0";
		$query = " update sys_pauto_config set ligne=ligne+1, frame_position='$frame_contenu_new' where id_page = '$id_page' and frame_position='$frame_contenu' ";
		pg_query($database_connection,$query);
		$query = " update sys_pauto_config set frame_position='$frame_contenu'  where id_page = '$id_page' and ligne = ".($ligne - 1);
		pg_query($database_connection,$query);
	}


	//header("location:pageframe.php?action=display&id_page=".$id_page."&id_pauto=".$id_pauto."&family=".$family);
	header("location:pageframe.php?action=display&id_page=".$id_page."&id_pauto=".$id_pauto);
	exit;
?>
