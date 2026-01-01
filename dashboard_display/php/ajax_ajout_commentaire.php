<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*
*	- 18/04/2007 christophe : changement du format de la chaine de param d'un commentaire.
*	20/03/2009 - modif SPS : utilisation de la classe DataBaseConnection et modification des id recuperes (suppression de la famille)
*
*  2010/08/03 - MGD - BZ 11415 : Ajout du commentaire dans la session pour l'export PDF/Word
*/
?>
<?
	/*
		Insertion d'un commentaire via une requête xmlHttpRequest. (graph et alarme)
	*/

	session_start();
	include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
	//include_once($repertoire_physique_niveau0 . "php/database_connection.php");
	//global $database_connection;

	// On récupère les données venant de l'URL.
	$comment_trouble_ticket = 	$_GET["comment_trouble_ticket"];
	$comment_content = 			$_GET["comment_content"];
	$comment_action = 			$_GET["comment_action"];
	$comment_level = 			$_GET["comment_level"];


	$liste_param_commentaire = explode("@",$_GET["params_liste"]);

	// On récupère la liste des paramètres.
	$id_priority_type = 		$comment_level;
	$date_ajout = 				date("Ymd G:i");
	$date_selecteur = 			$liste_param_commentaire[5]; // 18/04/2007 christophe : changement du format de la chaine de param d'un commentaire.
	$trouble_ticket_number = 	$comment_trouble_ticket;
	$id_elem = 					$liste_param_commentaire[1];
	$type_elem = 				$liste_param_commentaire[0];
	$na = 						$liste_param_commentaire[3];
	$na_value = 				$liste_param_commentaire[4];
	$ta = 						$liste_param_commentaire[2];
	
	$lib_comment = 				$comment_content;
	
	//si on n'a saisi aucun commentaire, on n'enregistre rien en base
	if ($comment_action!="Action...") {
		$lib_action = $comment_action;
	}
	else {
		$lib_action = "";
	}

	$query_insert = "
		INSERT INTO edw_comment
		(
			id_user,
			id_priority_type,
			date_ajout,
			date_selecteur,
			trouble_ticket_number,
			id_elem,
			type_elem,
			na,
			na_value,
			ta,
			hn,
			hn_value,
			libelle_comment,
			libelle_action
		)
		VALUES
		(
			'$id_user',
			$id_priority_type,
			'$date_ajout',
			'$date_selecteur',
			'$trouble_ticket_number',
			'$id_elem',
			'$type_elem',
			'$na',
			'$na_value',
			'$ta',
			'$hn',
			'$hn_value',
			'$lib_comment',
			'$lib_action'
		)
	";

	// on ne retourne que les 200 premières lettres du commentaire.
	$resultats = "";
	$resultats .= strlen($comment_content) > 200 ? substr($comment_content, 0, 200)."..." : $comment_content;
	
        // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::get Connection()
	$db = Database::getConnection();
	$db->executeQuery($query_insert);

	/* 2010/08/03 - MGD - BZ 11415
	 * Afin que l'export (PDF/Word) affiche le dernier commentaire saisi par l'utilisateur,
	 * il faut ajouter l'information dans la session. On recherche les gtm qui ont le même
	 * nom que le gtm du commentaire qu'on ajoute.
	 */
	$gtm_label = $db->getOne("SELECT page_name FROM sys_pauto_page_name WHERE id_page='$id_elem';");
	foreach ($_SESSION['dashboard_export_buffer']['data'] as &$gtm) {
		if ($gtm['titre'] == $gtm_label) {
			$gtm['lastComment'] = $lib_comment;
		}
	}

	echo $comment_content;
?>
