<?php
	/*
	 * Gere l'authentication de l'utilisateur envoyé par index.php en mode TA (normal)
	 *
	 * 13/09/2011 MMT DE PAAL1 - Merge de la branche VLC 5_1_0_10_PAA_01_02
	 * Fusion de code originels dans control_session et index.php
	 * 09/12/2011 ACS Mantis 837 DE HTTPS support
	 *
	*/
	session_start();
	include dirname( __FILE__ ).'/php/environnement_liens.php';
	include_once dirname( __FILE__ ).'/php/edw_function_session.php';

  	if ( isset($_POST["login"]) ) {
		$login           = $_POST["login"];
		$password        = $_POST["password"];
		$password_encode = base64_encode($password);	
	}
	else {
		// modif 07/01/2008 Gwénaël
			// si on vient depuis un lien externe
		$check_time_session = false;
		$login     = base64_decode($_COOKIE['externalLinkToTA_login']);
		$password  = base64_decode($_COOKIE['externalLinkToTA_mdp']);
		$password_encode = $_COOKIE['externalLinkToTA_mdp'];
	}
	
  	  	
   	// On récupère l'identifiant de l'utilisateur.
	$query = "SELECT id_user FROM users t1 WHERE (t1.login='$login' AND t1.password='$password_encode') and on_off=1";
	$result = pg_query($database_connection, $query);
	$nb_result = pg_num_rows($result);
	
	
	// le couple login<->mot de passe n'existe pas
	// soit le mot n'est pas encodé en base dans ce cas on l'encode
	// soit l'utilisateur c'est trompé lors de la saisi, on le redirige sur la page de connexion avec un message d'erreur
	if ( $nb_result == 0 ) {
		$query_mdp = "SELECT id_user FROM users t1 WHERE (t1.login='$login' AND t1.password='$password') and on_off=1";
		$result_mdp = pg_query($database_connection, $query_mdp);
		$nb_result_mdp = pg_num_rows($result_mdp);
		
		if ( $nb_result_mdp != 0 ) {
			$query_update = "UPDATE users SET password = '$password_encode' WHERE (login='$login' AND password='$password')";
			pg_query($database_connection, $query_update);
			
			$query = "SELECT id_user FROM users t1 WHERE (t1.login='$login' AND t1.password='$password_encode') and on_off=1";
			$result = pg_query($database_connection, $query);
			$nb_result = pg_num_rows($result);
		}
		else {
			// 09/12/2011 ACS Mantis 837 DE HTTPS support
			$url_location = ProductModel::getCompleteUrlForMasterGui('?error=login_passwd');
			header('Location:'.$url_location);
			exit();
		}
	}
	
	$row = pg_fetch_array($result, 0);
	$id_user = $row["id_user"];
	
	$_SESSION['id_user']  = $id_user;
	$_SESSION['login']    = $login;
	$_SESSION['password'] = $password_encode;
	$_SESSION['islogged'] = true;

	// une fois authentifié, on redirige vers l'index
	
	// maj 15/04/2009 - MPR : Correction du BZ8679 : Accès en https impossible
	// 09/12/2011 ACS Mantis 837 DE HTTPS support
	$url_location = ProductModel::getCompleteUrlForMasterGui();
	
	header('Location:'.$url_location);

?>