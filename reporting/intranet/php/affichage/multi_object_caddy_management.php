<?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
	- maj 10/01/2008, benoit : dans le cas des graphes "Investigation dashboard", on crée une entrée unique dans le tableau de session Excel      pour pouvoir disposer de plusieurs graphes de ce type dans le caddy
	- maj 10/01/2008, benoit : on recherche dans le panier s'il existe déja un graphe avec le même nom. Si c'est le cas, on ajout un increment    entre parenthèses
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
	Gestion de l'ajout / suppression d'éléments dans le caddy
	@author christophe chaput
	@version V1.1 2005-08-09

	- maj 19 05 2006 christophe : régression par rapport à la version 1200, on insère le denrier commentaire lorsque l'on ajoute un graph d'un dahsboard.
*/

session_start();

include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");

// 17/02/2009 - Modif. benoit : mise en commentaires des classes et des fonctions relatives à l'export PDF. Celles-ci seront à décommenter lors de la mise en oeuvre de l'export

/*include_once($repertoire_physique_niveau0 . "php/deploy_and_compute_functions.php");
include_once($repertoire_physique_niveau0 . "pdf/fpdf153/fpdf.php");
include_once($repertoire_physique_niveau0 . "class/acurioPDF.class.php");
include_once($repertoire_physique_niveau0 . "pdf/complement_pdf.php");

$fpdf_fontpath = $repertoire_physique_niveau0 . 'pdf/fpdf153/font/';
define('FPDF_FONTPATH', $fpdf_fontpath);*/

global $test_temp;

// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
$db = Database::getConnection();

switch($_GET["action"]){
	
	case "ajouter" :
		
		$object_id = $_GET["object_id"];
		
		// On vérifie si le graph est déjà dans le caddy.
		
		$sql = "SELECT * FROM sys_panier_mgt WHERE object_id = '$object_id'";
		$row = $db->getAll($sql);

		// Si c'est un tableau HTML venant du builder_report.
		
		if($_GET["object_type"]=="builder_report") $object_id=$test_temp;

		if(count($row) == 0) // Ajout d'un nouvel élément dans le caddy
		{		
			$id_user = 				$_GET["id_user"];
			$object_page_from = 	urldecode($_GET["object_page_from"]);
			$object_type = 			$_GET["object_type"];
			$object_title = 		urldecode($_GET["object_title"]);
			$object_summary = 		$_GET["object_summary"];
			$last_comment = 		isset($_GET["last_comment"]) ? $_GET["last_comment"] : "No comment...";

			// 10/01/2008 - Modif. benoit : dans le cas des graphes "Investigation dashboard", on crée une entrée unique dans le tableau de session Excel pour pouvoir disposer de plusieurs graphes de ce type dans le caddy

			if ($object_page_from == "Investigation dahsboard") {
				
				$old_id_graph	= $object_summary;
				$id_graph		= $object_summary = str_replace('.png', '', $object_id);

				$_SESSION['onglet_excel'][$id_graph]				= $_SESSION['onglet_excel'][$old_id_graph];
				$_SESSION['tableau_data_excel_ordonnee'][$id_graph]	= $_SESSION['tableau_data_excel_ordonnee'][$old_id_graph];
				$_SESSION['tableau_data_excel_abscisse'][$id_graph]	= $_SESSION['tableau_data_excel_abscisse'][$old_id_graph];
				$_SESSION['tableau_data_excel'][$id_graph]			= $_SESSION['tableau_data_excel'][$old_id_graph];
			}

			// 10/01/2008 - Modif. benoit : on recherche dans le panier s'il existe déja un graphe avec le même nom. Si c'est le cas, on ajout un increment entre parenthèses

			$sql = "SELECT object_title FROM sys_panier_mgt WHERE id_user='".$id_user."' AND object_title LIKE '%".$object_title."%' ORDER BY object_title DESC LIMIT 1";
			
			$row = $db->getAll($sql);

			for ($i=0; $i < count($row); $i++)
			{
				$last_title = $row[$i]['object_title'];

				if(preg_match('#\(?[0-9]\)#', $last_title, $index)){	// Un increment à déja été défini. On le récupère et l'augmente de 1
					$new_index = str_replace(array('(', ')'), '', $index[0]);
					$new_index = "(".($new_index+1).")";
					$object_title = str_replace($index[0], $new_index, $last_title);
				}
				else // Premier incrément
				{
					$object_title = $last_title." (1)";
				}
			}

			// Insertion dans le panier

			$sql =	 " INSERT INTO sys_panier_mgt"
					." (id_user,object_page_from,object_type,object_title,object_id,object_summary, last_comment)"
					." VALUES"
					." ('$id_user','$object_page_from','$object_type','$object_title','$object_id','$object_summary','$last_comment')";

			$db->executeQuery($sql);
		}
	
	break;
	
	case "ajouter_pdf" :
		// On récupère toutes les data.
		$id_user 			= $_GET["id_user"];
		$object_page_from 	= $_GET["object_page_from"];
		$object_type 		= $_GET["object_type"];

		$object_title 	= "";
		$object_summary = "";
		$object_id 		= "";
		$alarm_ids 		= "";

		$object_title = " PDF file from alarm ";
		$object_summary = $_GET["pdf"];
		$object_id = $_GET["pdf"];

		// Ajout d'un nouvel élément dans le caddy.
		$query="insert into sys_panier_mgt  (id_user,object_page_from,object_type,object_title,object_id,object_summary) values ('$id_user','$object_page_from','$object_type','$object_title','$object_id','$object_summary')";
		pg_query($database_connection,$query);

	break;
	
	case "supprimer" :
		// Suppression d'un élément du caddy.
		$id_user=$_GET["id_user"];
		$oid=$_GET["oid"];

		$query="delete from sys_panier_mgt where oid='$oid' ";
		pg_query($database_connection,$query);
		header("location:multi_object_caddy.php?id_user=$id_user");
		exit();
	
	break;
	
	case "vider" :
		// Permet de supprimer tous les élément sdu caddy.
		$id_user=$_GET["id_user"];

		$query="delete from sys_panier_mgt  where id_user='$id_user'";
		pg_query($database_connection,$query);
	
	break;
}
?>
