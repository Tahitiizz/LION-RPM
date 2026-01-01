<?
/*
*	@cb30000@
*
*	24/07/2007 - Copyright Acurio
*
*	Composant de base version cb_3.0.0.00
*
*	24/07/2007 - Jérémy - Recherche de l'id_user_max pour l'incrémenter et créer un nouvel id pour les nouveaux USER
*			La partie qui vérifie que l'ID est absent de la table avant de le créer a été conservée puisque sur la version courante, la plupart des USERS 
*			ont été créés avec le random, donc il pourrait y avoir des conflits. Cette partie de vérification pourra être supprimée plus tard
*/
?>
<?
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
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
	Dans cette page, on gère les modifications / création d'utilisateurs
	Les données reçues par cette page sont issue de la page
	user_edit.php
	- maj 05 05 2006 christophe : changement message d'erreur 'a user already has this login' par 'A user has already this login. Please enter a new login' correction bug flyspray n°276 ligne 93
	- maj 10 05 2006 christophe : on affiche un message d'erreur si le mot de passe est vide quand on ajoute un nouvel utilisateur ligne 97
  - maj 29 06 2006 xavier : encodage du mot de passe lignes 80 et 118
  - maj 06 07 2006 xavier : mise en commentaire de la récupération du mot de passe. ligne 50

*/


// initialisation
include("../../../../php/environnement_liens.php");
include($niveau4_vers_php."database_connection.php");
include($niveau4_vers_php."environnement_nom_tables.php");

	// On vérifie si l'utilisateur a sélectionné un profil.
	if(isset($_POST["user_profil"])){
		if($_POST["user_profil"][0] == 0){
			$msg_erreur = __T('A_E_USER_MANAGEMENT_NO_PROFILE_SELECTED');
			$user = isset($user_to_edit) ? "&user_to_edit=".$user_to_edit : "";
			header("location:user_index.php?user_to_edit=$user_to_edit&msg_erreur=$msg_erreur".$user);
			exit;
		}
	}
	// On vérifie si le champ nom est vide
	if(trim($_POST["username"]) == "" || trim($_POST["user_prenom"]) == "" || trim($_POST["user_login"]) == "" || trim($_POST["user_mail"]) == "" || trim($_POST["date_valid"]) == ""){
		$msg_erreur = __T('A_E_USER_MANAGEMENT_EMPTY_FIELD');
		$user = isset($user_to_edit) ? "&user_to_edit=".$user_to_edit : "";
		header("location:user_index.php?msg_erreur=$msg_erreur".$user);
		exit;
	}


if ($user_to_edit) {
	// mise a jour d'un utilisateur

	// on verifie que le login n'est pas déjà utilisé
/*
		// si le password n'a pas été envoyé, on va le chercher dans la base
		if (!$password) {
			$get_pwd = pg_query($database_connection,"select password from $nom_table_users where id_user=$user_to_edit");
			$row = pg_fetch_array($get_pwd,0);
			$password_to_check = $row['password'];
		} else {
			$password_to_check = $password;
		}
*/
		// on fait la verif
		$query = "select * from $nom_table_users
			WHERE login='$user_login'
				AND id_user!='$user_to_edit'";
		$check_login = pg_query($database_connection,$query);
		$nb_results = pg_num_rows($check_login);
		if ($nb_results) {
			echo '<script language="JavaScript">alert("'.__T('A_JS_USER_MANAGEMENT_LOGIN_ALREADY_USED',$user_login).'");history.go(-1);</script>';
			exit;
		}

	// debut de la query
	$query="
		UPDATE $nom_table_users
		SET user_prenom='$user_prenom',
			username='$username',
			user_mail='$user_mail',
			login='$user_login',";
	// gestion du mot de passe
	if ($password) {
		if ($password == $_password_confirm) {
			$query .= "
			password='".base64_encode($password)."',";
		}
	}
	// reste de la query
	$query .= "
			user_profil='$user_profil[0]',
			date_valid = '".intval(str_replace('-','',$date_valid))."',
			on_off = '".intval($on_off)."'
		WHERE id_user = ".intval($user_to_edit);
	pg_query($database_connection,$query);
} else {
	// creation d'un utilisateur
	// 24/07/2007 - JL - Recherche de l'id_user_max pour l'incrémenter et créer un nouvel id pour le nouveau USER
	$query = "SELECT max(id_user) FROM $nom_table_users";
	$result = pg_query($database_connection,$query);
	$row = pg_fetch_array($result);
	$max_id_user = $row['max'];
	$id_user_new = $max_id_user + 1;
	// on verifie que l'id est pas déjà prise
	$check_id = pg_query($database_connection,"select * from $nom_table_users where id_user=$id_user_new");
	while (pg_num_rows($check_id)) {
		$id_user_new++;
		$check_id = pg_query($database_connection,"select * from $nom_table_users where id_user=$id_user_new");
	}
	// on verifie que le login n'est pas déjà pris
	$query = "select * from $nom_table_users
		WHERE login='$user_login' ";
	$check_login = pg_query($database_connection,$query);
	if (pg_num_rows($check_login)) {
		echo '<script language="JavaScript">alert("'.__T('A_JS_USER_MANAGEMENT_LOGIN_ALREADY_USED',$user_login).'");history.go(-1);</script>';
		exit;
	}
	// Pas de password saisit.
	$temp = str_replace(" ", "",$password);
	if($temp == ""){
		echo '<script language="JavaScript">alert("'.__T('A_JS_USER_MANAGEMENT_PASSWORD_EMPTY_FIELD').'");history.go(-1);</script>';
		exit;
	}
	// apres ces quelques vérifications, on ajoute l'utilisateur
	$query="INSERT INTO $nom_table_users
			(id_user, username, user_prenom, user_mail, login, password, user_profil,date_creation, date_valid,on_off)
		VALUES
			('$id_user_new', '$username','$user_prenom','$user_mail','$user_login','".base64_encode($password)."','$user_profil[0]','".date('Ymd')."','".str_replace('-','',$date_valid)."','".intval($on_off)."')";
		//echo $query;
	pg_query($database_connection,$query);

	// mise à jour de la table qui contient les paramètres utilisateurs pour afficher les données du sélecteur
	// le but est d'initialiser une entrée dans cette table pour qu'il n'y ait pas d'erreur lors de la première connexion
	$week_en_cours=ceil(date("z")/7)-1;
	$day_courant=date("d");
	$year_courant=date("Y");
	$month_courant=date("m");
	$default_period=20;
	$query="INSERT INTO $nom_table_parametres_user
			(id_user, week, period, day, month, general_value, general_type, year, nombre_wcl)
		VALUES
			('$id_user_new','$week_en_cours','$default_period','$day_courant','$month_courant','bsc','BKB01','$year_courant','10')";
	pg_query($database_connection,$query);
	//exit;
}

// boxo(list_array($_POST));

?>

<script>
window.location = "user_index.php";
</script>
