<?
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
*
*	- maj 19/06/2007 Gwénaël :  suppression de la homepage si la valeur est NONE (uniquement dans le cas d'un USER
*	- 28/06/2007 christophe : gestion de la sauvegarde de network_element_preferences.
*	- 14/08/2007 christophe : pour sauvegarder les éléments réseaux de l'utilisateur, on se base sur la variable $_SESSION["selecteur_general_values"]["list_of_na"]
*	et non sur $_SESSION["network_element_preferences"].
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
*	16/06/2006 - Copyright Acurio
*
*	Composant de base version cb_1.2.0.2p
*
*	Parser version gb_1.0.0b
*/
?>
<?

/*
	Dans cette page, on gère les modifications / création de son propre compte utilisateur
	Les données reçues par cette page sont issue de la page
	user_edit_me.php

	- maj 10 05 2006 christophe : on affiche un message d'erreur
	- maj 29 06 2006 xavier : encodage du mot de passe ligne 69
	- maj 06 07 2006 xavier : la vérification se fait sur le seul login plutôt que sur le couple login/password. ligne 36
*/


// initialisation
session_start();
include("../../../../php/environnement_liens.php");
include($niveau4_vers_php."database_connection.php");
include($niveau4_vers_php."environnement_nom_tables.php");

// include($niveau4_vers_php."outils_stephane.php");


// l'id de l'utilisateur
$id_user = $_SESSION['id_user'];


// on verifie que le login n'est pas déjà utilisé
/*
	// si le password n'a pas été envoyé, on va le chercher dans la base
	if (!$_POST['password']) {
		$get_pwd = pg_query($database_connection,"select password from $nom_table_users where id_user=$id_user");
		$row = pg_fetch_array($get_pwd,0);
		$password_to_check = $row['password'];
	} else {
		$password_to_check = $_POST['password'];
	}
*/
	// on fait la verif
	$query = "select * from $nom_table_users
		WHERE login='".$_POST['user_login']."'
			AND id_user!='$id_user'";
	$check_login = pg_query($database_connection,$query);
	$nb_results = pg_num_rows($check_login);
	if ($nb_results) {
		echo '<script language="JavaScript">alert("This login is already used. Please enter a new login.");history.go(-1);</script>';
		exit;
	}

// debut de la query
$query="
	UPDATE $nom_table_users
	SET user_prenom='".$_POST['user_prenom']."',
		username='".$_POST['username']."',
		user_mail='".$_POST['user_mail']."',
		login='".$_POST['user_login']."'";
// gestion du mot de passe
/*
	Si il n'y a pas de mot de passe, on affiche un message d'erreur
*/
if ($_POST['password']) {
	if ($_POST['password'] == $_POST['_password_confirm']) {
		$query .= ",
		password='".base64_encode($_POST['password'])."'";
	}
}

/*
	- 28/06/2007 christophe : gestion de la sauvegarde de network_element_preferences.
	- 14/08/2007 christophe : pour sauvegarder les éléments réseaux de l'utilisateur, on se base sur la variable $_SESSION["selecteur_general_values"]["list_of_na"]
	et non sur $_SESSION["network_element_preferences"].
*/
$network_element_preferences = '';
if ( isset ( $_SESSION["selecteur_general_values"]["list_of_na"] ) )
{
	$network_element_preferences = $_SESSION["selecteur_general_values"]["list_of_na"];
}
$query .= ",network_element_preferences='$network_element_preferences'";


// reste de la query
$query .= "
	WHERE id_user = ".intval($id_user);

pg_query($database_connection,$query);


// modif 19/06/2007 Gwénaël
	// suppression de la homepage si NONE
// Si la valeur de l'homepage est none, on supprime les valeurs dans la table sys_selecteurs_properties
if ( isset($_POST['homepage']) ) {
	if ( $_POST['homepage'] == 'none' ) {
		$query_delete = "DELETE FROM sys_selecteur_properties WHERE id_user = ".$_SESSION['id_user']."";
		$result = pg_query($query_delete);
	}
	unset($_SESSION['id_selecteur_user_homepage']);
}
?>
<script>
 window.location = 'user_edit_me.php?nocache=<?=date('U')?>';
</script>