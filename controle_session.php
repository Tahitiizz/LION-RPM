<?php
/**
 * 
 *  CB 5.2
 * 
 * 23/02/2012 NSE DE Astellia Portal Lot2
 */
?><?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
* 	- 28/12/2007 Gwénaël : restructuration du fichier dans l'optique d'une meilleure lisibilité et maintenance
* 	- 07/01/2008 Gwénaël : evolution liens externes
*	15/05/2009 - SPS : on teste la date de validite de l'utilisateur (correction bug 9591)
*  24/08/2011 MMT DE PAAL1 - Merge de la branche VLC 5_1_0_10_PAA_01_02
 * 08/11/2011 NSE bz 24513 : gestion des liens externes
 * 09/12/2011 ACS Mantis 837 DE HTTPS support
 * 21/12/2011 ACS BZ 25227 authentication asked to access to related dashboard
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
*	@cb21002@
*
*	23/02/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.0.02
*/
?>
<?
/*
*	@cb1300_iu2000b_pour_cb2000b@
*
*	19/07/2006 - Copyright Acurio
*
*	Composant de base version cb_1.3.0.0
*
*	Parser version iu_2.0.0.0
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

/*
    - maj 29 06 2006 xavier : encodage du mot de passe pour identification ligne 27
    - maj 06 07 2006 xavier : encodage du mot de passe si en clair dans la base de données ligne 51
	 01-09-2006 : initialisation de variables utilisées lors du logout pour la session utilisateur
     - maj 02/03/2007 gwénaël : réduction du nombre de chiffre du numéro de produit à 2 chiffres
*/

?>
<?php
session_start();
include dirname( __FILE__ ).'/php/environnement_liens.php';
include dirname( __FILE__ ).'/php/edw_function_session.php';
// 24/08/2011 MMT DE PAAL1 - Merge de la branche VLC 5_1_0_10_PAA_01_02
include_once dirname( __FILE__ ).'/api/paa/PAAAuthenticationService.php';
$PAAAuthentication = PAAAuthenticationService::getAuthenticationService();

$check_time_session = true;

$session_id      = session_id();
// 24/08/2011 MMT DE PAAL1 - Merge de la branche VLC 5_1_0_10_PAA_01_02
// 12/01/11 VLC: PAA lot 1: obsolète, réalisé par controle_login.php en standalone, et par l'API PAA autrement

// On récupère l'identifiant de l'utilisateur.
// 24/08/2011 MMT DE PAAL1 - Merge de la branche VLC 5_1_0_10_PAA_01_02
// On tente d'authentifier le user
$ret = $PAAAuthentication->validateAuthentication();

// Si l'authentification échoue, on sort avec message d'erreur
// normalement, ne devrait pas arriver à ce stade
if (!$ret) {
	// 09/12/2011 ACS Mantis 837 DE HTTPS support
	$url_location = ProductModel::getCompleteUrlForMasterGui('?error=login_passwd');
	header('Location:'.$url_location);
	exit();
}

// on recupere le user
$user = $PAAAuthentication->getUser();
$login = $user->getLogin();

//Si l'authentification a réussi
// NSE DE Astellia Portal Lot2
// PAA Lot 2 : on met à jour les infos locales de l'utilisateur à partir du portail
UserModel::updateLocalUsersAttributes($login);

// 07/06/2011 BBX -PARTITIONING-
// Application des casts nécessaires
// 24/08/2011 MMT DE PAAL1 - pas de test sur le mot de passe
// 20/02/2012 NSE DE Astellia Portal Lot2 : suppression de date_valid et on_off
$query = "SELECT id_user FROM users WHERE login='$login' ";
$result = pg_query($database_connection, $query);

//Le user est authentifié ET connu de l'application

$row = pg_fetch_array($result, 0);
$id_user = $row["id_user"];

$_SESSION['id_user']  = $id_user;

// 12/01/11 VLC: PAA lot 1: obsolète, réalisé par controle_login.php en standalone, et par l'API PAA autrement
//$_SESSION['login']    = $login;
//$_SESSION['password'] = $password_encode;
// 08/11/2011 NSE bz 24513 : Utile pour le cas des liens externes !
if(!isset($_SESSION['islogged']) && $_SESSION['islogged'] !== true && isset($_COOKIE['externalLinkToTA_login'])){
    $check_time_session = false;
    $_SESSION['login']     = base64_decode($_COOKIE['externalLinkToTA_login']);
    $_SESSION['password']  = $_COOKIE['externalLinkToTA_mdp'];
    $login = base64_decode($_COOKIE['externalLinkToTA_login']);
    $password_encode = $_COOKIE['externalLinkToTA_mdp'];
}
else{
    $login = $_SESSION['login'];
    $password_encode = $_SESSION['password'];
}
//stocke le timestamp de connection de l'utilisateur
$_SESSION["start_user_session"] = date("Y-m-d H:i:s");
//initialise une variable qui n'est conservée que lorsque l'utilisateur est connecté à l'appli. On ne peut pas utiliser l'id_session généré par le serveur car même si l'utilisateur se delogue, tant que IE n'est pas fermé cet id reste toujours
$_SESSION["session_uniq_id"] = uniqid("");

// On vérifie si un autre utilisateur est déjà connecté.
// Si c'est la première fois qu'un utilisateur se connecte, on enregistre les champs id_session et last_connection.
// 08/11/2011 NSE bz 24513 : ajout de la condition si on est dans le cas des liens externes
if ( get_user_session_id($id_user) == "" && get_user_last_connection($id_user) == "" || ( !isset($_SESSION['islogged']) && $_SESSION['islogged'] !== true && isset($_COOKIE['externalLinkToTA_login']) ) ) {
	update_user_id_session($id_user, $session_id);
	update_user_last_connection($id_user);
	$file_location = 'acces_intranet.php';
}
else {
	// 21/11/2011 BBX
	// Correction de messages "Notices" vu pendant les corrections
	// 21/12/2011 ACS BZ 25227 authentication asked to access to related dashboard
	if ( $session_id != get_user_session_id($id_user) && $check_time_session === true ) {
		// on vérifie si le temps d'inactivité est dépasssé.
		// 24/08/2011 MMT DE PAAL1 - utilisation de session_has_timedout
		if ( !session_has_timedout($id_user)) { 
			$file_location = '?error=used';
		} else {
			// Le temps d'inactivité est dépassé, on remplace donc l'id session.
			update_user_id_session($id_user, $session_id);
			update_user_last_connection($id_user);
			$file_location = 'acces_intranet.php';
		}
	}
	else {
		if ( $check_time_session === false ) {
			update_user_id_session($id_user, $session_id);
			update_user_last_connection($id_user);
		}
		// C'est bien l'utilisateur courrant qui est connecté.
		update_user_last_connection($id_user);
		$file_location = 'acces_intranet.php';
	}
}

if ( isset($_SESSION['errorExternalLink']) && $file_location == 'acces_intranet.php' ) {
	$file_location = '?error=link';
}

// maj 15/04/2009 - MPR : Correction du BZ8679 : Accès en https impossible
// 24/08/2011 MMT DE PAAL1 - Merge de la branche VLC 5_1_0_10_PAA_01_02
// 09/12/2011 ACS Mantis 837 DE HTTPS support
$url_location = ProductModel::getCompleteUrlForMasterGui($file_location);

header('Location:'.$url_location);
?>