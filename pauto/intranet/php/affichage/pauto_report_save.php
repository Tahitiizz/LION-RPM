<?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
*	- maj 14/04/08 chrisotphe correction bug BZ6299, BZ6366, BZ6367 : appel à la fonction getClientType pour enregistrer le bon propriétaire du rapport.
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
*	@cb2000b_iu2000b@
*
*	20/07/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.0.0
*
*	Parser version iu_2.0.0.0
*/
?>
<?
	/*
		- maj 30 08 2006 christophe : on vérifie si il y a un sélecteur dans le rapport.
		- maj 18 09 2006 : il n'y a plus de sélecteur dans les rapports, on n'affiche donc plus de message d'erreur si il n'y en a pas.
		- maj 22 09 2006 xavier : champ page_mode de sys_pauto_page_name sert de on_off pour l'envoi des rapport (ligne 93)
		- maj 03 10 2006 xavier : dans un rapport, le choix des sélecteurs ne s'applique plus que pour les dashboard
		                          lorsque le rapport est vide, le on_off du rapport (champ page_mode de sys_pauto_page_name) passe à 0.
	*/
	// Enregistrement des page auto de type Report.
	session_start();
	include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
	include_once($repertoire_physique_niveau0 . "php/database_connection.php");
	include_once($repertoire_physique_niveau0 . "php/edw_function.php");
	include_once($repertoire_physique_niveau0 . "php/traitement_chaines_de_caracteres.php");
	global $database_connection;

	$liste_action="0-5084-0-5078-5086"; // Liste des id pour l'affichage du menu contextuel.

	// maj 14/04/08 chrisotphe correction bug BZ6299, BZ6366, BZ6367 : appel à la fonction getClientType pour enregistrer le bon propriétaire du rapport.
	$droit = getClientType($_SESSION['id_user']);

	// Récupération des données du formulaire.
	$id_page=	$_GET["id_page"];
	$name = 	renameString($_POST["page_name"]);
	$type = 	$_GET["id_pauto"];
	$action = 	$_GET["action"];	// new ou update
	$family = $_GET["family"];		// Nom de la famille de la page auto.

	$name =  preg_replace('@[^a-zA-Z0-9_]@', '', $name);
	$page_name = $name;


	// DEBUG
	//echo $id_page."- nom page : ".$name."- network aggr : ".$network_aggregation."- time aggr : ".$time_aggregation."- action : ".$action."- home net :".$home_network;
	//exit;

	// On vérifie que le champ saisit n'est pas vide.
	if (trim($name) == "" || $name=="enter a name"){

		if($action == "new"){
			header("location:pageframe.php?action=new&id_pauto=".$_GET["id_pauto"]."&family=".$family);
		} else {
			header("location:pageframe.php?action=display&id_page=".$id_page."&id_pauto=".$_GET["id_pauto"]."&family=".$family);
		}
		exit;
	}

	// Gestion des magicquotes.
	if (!get_magic_quotes_gpc()) {
		$name = addslashes($name);
	} else {
		$name = $name;
	}

	// Avant de faire un modification, on vérifie si le nom de la page
	// est déjà utilisé.
	$query_search=" select * from sys_pauto_page_name where page_name='$name' and page_type='$type'  ";;
	$result_search=pg_query($database_connection,$query_search);
	$result_nb = pg_num_rows($result_search);
	if($result_nb>0){
		$result_array = pg_fetch_array($result_search, 0);
		if($id_page != $result_array["id_page"]){
			if($action == "new"){
				header("location:pageframe.php?action=new&msg=exist"."&id_pauto=".$_GET["id_pauto"]."&family=".$family);
				exit;
			} else {
				header("location:pageframe.php?action=display&id_page=".$id_page."&id_pauto=".$_GET["id_pauto"]."&family=".$family);
				exit;
			}
		}
	}


	if($action == "new"){
		// Insertion dans la BD.
		$query = "
			INSERT INTO sys_pauto_page_name
			(id_page, page_name, page_mode,page_type,droit,time_aggregation,network_aggregation,axe3,family)
			VALUES
			('$id_page', '$name', 0, '$type','$droit','' ,'' ,'','$family')
		";
		pg_query($database_connection,$query);

	} else {

    // maj 03 10 2006 xavier
    $query_select = "
      SELECT DISTINCT class_object||'@'||id_elem FROM sys_pauto_config
      WHERE id_page='$id_page'
      AND class_object='page'
      AND class_object||'@'||id_elem NOT IN (SELECT id_elem FROM sys_user_parameter WHERE module_restitution='$id_page')
    ";
    $result_select = pg_query($query_select);
    $is_not_configured = pg_num_rows($result_select);

    $query_empty = "
      SELECT DISTINCT class_object||'@'||id_elem FROM sys_pauto_config
      WHERE id_page='$id_page'
    ";
    $result_empty = pg_query($query_empty);
    $is_empty = pg_num_rows($result_empty);

    if ($is_not_configured or !$is_empty) $selecteur_is_configured = '0';
    else $selecteur_is_configured = '1';

		// mise-à-jour.
		$query = "
			UPDATE sys_pauto_page_name SET
				page_name = '$page_name',
				page_mode = '$selecteur_is_configured'
				WHERE id_page = '$id_page'
		";
		pg_query($database_connection,$query);
	}

	/*
		On vérifie si il y a un sélecteur dans le rapport
	*/
	/*$query = "
		SELECT * FROM sys_pauto_config
			WHERE id_page = '$id_page'
			AND class_object = 'selecteur'
	";
	$result = pg_query($database_connection,$query);
	$nombre_resultat = pg_num_rows($result);
	if($nombre_resultat > 0){*/
		header("location:pageframe.php?action=display&id_page=".$id_page."&id_pauto=".$_GET["id_pauto"]."&family=".$family);
	/*} else {
		$msg_erreur = "Filter item is missing.";
		header("location:pageframe.php?msg_erreur=".$msg_erreur."&action=display&id_page=".$id_page."&id_pauto=".$_GET["id_pauto"]."&family=".$family);
	}*/

?>
