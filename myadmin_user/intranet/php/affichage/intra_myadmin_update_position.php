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
		Gestion des enregistrements dans la table profile_menu_position
		(permet de stocker des positions de menu différentes en fonction
		des  profils)

		@author : christophe
	*/

	session_start();
	include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
	include_once($repertoire_physique_niveau0 . "php/database_connection.php");
	include_once($repertoire_physique_niveau0 . "php/edw_function.php");
	global $database_connection;

	// Récupération des données transmises par l'URL.
	$sens = $_GET["sens"]; // up ou down : si up la position prend -1 et down +1
	$id_profile = $_GET["id_profile"]; // identifiant du profile à modifier
	$id_menu = $_GET["id_menu"]; // identifiant du menu à modifier
	$position = $_GET["position"]; // position actuelles enregistrées
	$id_menu_parent = $_GET["id_menu_parent"]; // position actuelles enregistrées
	$profile_type = $_GET["profile_type"];

	// Debug
	//echo "Infos : sens ". $sens. ", id_profile ". $id_profile. ", id_menu ". $id_menu. ", position ". $position. ", id_menu_parent ". $id_menu_parent;


	// On met-à-jour toutes les positions des menus.
	if($sens == "up"){
		// On récupère la position de l'élément supérieur.
		$query = " select * from profile_menu_position where id_profile='$id_profile' and id_menu_parent='$id_menu_parent' and position < '$position' order by position desc ";
		$result = pg_query($database_connection,$query);
		$result_array = pg_fetch_array($result, 0);
		$nouvelle_position = $result_array["position"];
		$other_menu = $result_array["id_menu"];
		//echo "L'élément plus haut est : " . $result_array["id_menu"]. " (id_menu) ." ; exit;

		// MAJ de la position du menu supérieur.
		$query = " update profile_menu_position set position='$position' where id_menu_parent = '$id_menu_parent' and id_profile= '$id_profile' and id_menu = '$other_menu' ";
		//echo $query."<br>";
		pg_query($database_connection,$query);
		// MAJ de la position du menu sélectionné.
		$query = " update profile_menu_position set position='$nouvelle_position' where id_profile= '$id_profile' and id_menu='$id_menu' ";
		//echo $query."<br>";
		pg_query($database_connection,$query);
	} else { // down
		// On récupère la position de l'élément inf.
		$query = " select * from profile_menu_position where id_profile='$id_profile' and id_menu_parent='$id_menu_parent' and position > '$position' order by position asc ";
		$result = pg_query($database_connection,$query);
		$result_array = pg_fetch_array($result, 0);
		$nouvelle_position = $result_array["position"];
		$other_menu = $result_array["id_menu"];
		//echo "L'élément en dessous est : " . $result_array["id_menu"]. " (id_menu) ." ; exit;

		// MAJ de la position du menu supérieur.
		$query = " update profile_menu_position set position='$position' where id_menu_parent = '$id_menu_parent' and id_profile= '$id_profile' and id_menu = '$other_menu' ";
		//echo $query."<br>";
		pg_query($database_connection,$query);
		// MAJ de la position du menu sélectionné.
		$query = " update profile_menu_position set position='$nouvelle_position' where id_profile= '$id_profile' and id_menu='$id_menu' ";
		//echo $query."<br>";
		pg_query($database_connection,$query);
	}
	//exit;
	// On mets l'ensemble des id_menu dans $chaine pour l'affichage dans intra_myadmin.
	$query = " select profile_to_menu from profile where id_profile = '$id_profile' ";
	$result=pg_query($database_connection,$query);
	$result_array = pg_fetch_array($result, 0);
	$chaine = $result_array["profile_to_menu"];

	header("location:intra_myadmin_profile_management.php?chaine_menu_coche=$chaine&select=0&id_profile=$id_profile&profile_type=$profile_type");
	exit;
?>
