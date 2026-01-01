<?php
/**
 * 
 *  CB 5.2
 * 
 * 23/02/2012 NSE DE Astellia Portal Lot2
 * 13/03/2012 NSE bz 26260 : prise en compte de l'existence de droits utilisateur pour accéder à l'appli
 * 26/03/2012 NSE bz 26417 : reconnexion impossible après déconnexion en mode dégradé
 * 24/04/2012 NSE bz 26636 : utilisation de l'API Portail v 1.0.0.04 pour la gestion de Astellia Administrator
 */
?><?php
/*
*	@cb51000@
*
*	28-06-2010 - Copyright Astellia
*
*	Composant de base version cb_5.1.0.00
*
*	28/06/2010 NSE - Division par zéro : test du module
*   30/12/2010 17:19 SCT : Optimisation du code BZ 19673
*       - remplacement de "new Databaseconnection()" par "Database::getConnection()"
*   03/01/2011 16:43 SCT : Optimisation du code BZ 19673
*       - appel de la méthode "getAllSysGlobalParameters" afin de n'effectuer qu'un seul appel par boucle au lieu de 4 vers la table sys_global_parameters
*       - ajout d'un appel AJAX pour la vérification de l'installation du module SSH sur chaque serveur affiliate distant
*       - suppression du code non utilisé
*   21/01/2011 OJT : Mise à jour du texte d'attente Checking SSH après avoir exécuté
 *  21/01/2011 MMT DE Xpert 606 - utilisation de la class Xpert manager pour MAJ des menus XPert
 *  13/09/2011 MMT DE PAAL1 - gestion du portail et de la classe PAAAuthenticationService
 * 08/11/2011 NSE bz 24505 : gestion de la fermture ou du retour à l'identification suivant l'ouverture de l'appli
 * 30/11/2011 NSE bz 24845 : on propose Enter/Exit à l'utilisateur (au lieu de Enter/Back)
 * 09/12/2011 ACS Mantis 837 DE HTTPS support
 * 20/12/2011 ACS BZ 25206 Bad link to master on slave index page
*
*/
?>
<?php
/*
*	@cb4100@
*
*	11/12/2008 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.0
*
*	 maj 11/12/2008 - MPR : Appel à la classe Key pour y extraire ses données
*	 maj 07/01/2009 - MPR : On boucle sur tous les produits
*	 maj 08/01/2009 - MPR: Affichage de la clé expirée pour le produit concerné
*	 07/04/2009 - modif SPS : - ajout de la librairie Prototype dans les balises head de la page
* 							  - ajustement du style
*	15/05/2009 - SPS : ajout du cas ou la date de validite du login de l'utilisateur a expiree (correction bug 9591)
*
*
*	03/06/2009 BBX : gestion des user statistics. BZ 9751
*
*	22/06/2009 GHX
*		- Prise en compte du cas, où il n'est pas possible de ce connecter sur un produit => on le désactive
*	12/08/2009 GHX
*		- Correction du BZ 6652 : la session n'était pas bien détruite du coup car si on se déconnectait et se reconnectait avec un autre user on avait le même session_id
*
*	25/08/2009 - MPR : Correction du bug 11214 : On vide le caddy
*
*	27/08/2009 GHX
*		- Utilisation de DatabaseConnection pour les liens externes
*	02/09/2009 GHX
*		- Affichage de messages d'erreurs si OOo et/ou SSH sont mal installé
*	03/09/2009 GHX
*		- Correction du BZ 11366
*	24/09/2009 GHX
*		- Correction du BZ 11727
*	07/10/2009 GHX
*		- Modification pour ne pas avoir d'erreur sur la clé pour le produit Mixed KPI
*	03/12/2009 GHX
*		- Correction du BZ 12979 [CB 5.0][Test Open Office] problème de lenteur connexion à cause de OOo
*			-> Suppression du test sur OOo
*	16/12/2009 NSE
*		- Correction du BZ 13460 pas de message d'erreur lors déconnexion par même utilisateur
*	18/12/2009 NSE
*		- Correction bz13130 différenciation nom base, rep install et alias : la comparaison était faite avec l'alias (NIVEAU_0) au lieu
*			du répertoire physique pour déterminer si on est sur le master
*	24/03/2010 NSE bz 14815 :
*		- on ne désactive plus les produits pour lesquels on n'a pas de connexion à la BD (trop violent)
*		- on affiche un message sur la page d'accueil et dans le tracelog
*
*/

define( 'MAX_PRODUCTS_DISPLAY', 3 ); // 21/01/2011 : Nombre maximum de produit listés

session_start();
include dirname( __FILE__ ).'/php/environnement_liens.php';
include dirname( __FILE__ ).'/php/edw_function_menu.php';
include dirname( __FILE__ ).'/php/edw_function_session.php';
include dirname( __FILE__ ).'/php/edw_function_family.php';
include dirname( __FILE__ ).'/class/ExternalLink.class.php';

// 13/09/2011 MMT DE PAAL1 - Merge de la branche VLC 5_1_0_10_PAA_01_02
// Deplacement des variables d'erreur en tete de fichier
// ajout cas 'erreur critique' - pas de login ni d'entré possible
$msg_error = '';
$criticalError = false;

// 13/09/2011 MMT DE PAAL1 - ajout de $display_btn_enter
$display_login		= true; // affiche les champs login/mot de passe
$display_btn_enter= false;// affiche le bouton "Enter" si $display_login == false
$display_btn_back	= false;// affiche le lien " >> Back" si $display_login == false et $display_btn_enter == false

// 13/09/2011 MMT DE PAAL1 - Merge de la branche VLC 5_1_0_10_PAA_01_02
// la page de login se verifie sur controle_login.php
$url_form		= 'controle_login.php';
$error			= false;
// 13/09/2011 MMT DE PAAL1 - ajout variable d'erreur d'authentication
$errorBlockingForAuthentication = true;
$display_key		= false;
$key_exceeded		= false;
// 13/09/2011 MMT DE PAAL1 - ajout variable d'erreur d'authentication
$param_error	= '';


// 13/09/2011 MMT DE PAAL1 - Merge de la branche VLC 5_1_0_10_PAA_01_02
include_once dirname( __FILE__ ).'/api/paa/PAAAuthenticationService.php';
$paaConfigFile = dirname( __FILE__ ).'/api/paa/conf/PAA.inc';
// chargement de la config d'authentification
if(file_exists($paaConfigFile)){
	$PAAAuthentication = PAAAuthenticationService::getAuthenticationService($paaConfigFile);
} else {
	$criticalError = true;
	$msg_error = __T('U_PAA_CONFIG_NOT_FOUND',$paaConfigFile);
}


// L'API PAA loge automatiquement avec l'utilisateur associé à la session en cours, ce n'est pas quelque chose de controlable.
// si T&A est fermé mais la session persiste, en lancant T&A a partir du portail, T&A utilise alors
// l'utilisateur en session et non l'utilisateur du portail
// Pour eviter ca il faut faire un RAZ de la session lors d'un nouvel appel à T&A
if(PAA_SERVICE == PAAAuthenticationService::$TYPE_CAS && count($_GET) == 0 && count($_POST) == 0){
	session_unset(); // on efface toutes les variables de session
	session_destroy();
	//redirige avec parametre bidon pour evite redirection infiniue du a la redirection du cas dans checkAuthentication
	header("location:index.php?casSessionCleaned=yes");
	exit;
}

//21/01/2011 MMT DE Xpert 606 - utilisation de la class Xpert manager pour MAJ des menus XPert
include dirname( __FILE__ )."/class/Xpert/XpertManager.class.php";
$Mger = new XpertManager();
$Mger->checkAndUpdateMenus();

// ***************************************** //
// *** CONTROLE SUR LE TYPE DE PRODUIT ***//
// ***************************************** //

// 13/12/2010 BBX
// Tentative de connexion à tous les produits
// BZ 18510
foreach (ProductModel::getActiveProducts() as $product) {
    Database::getConnection($product['sdp_id']);
}

// 02/11/2010 BBX
// Mise à jour de la version produit Mixed KPI
// BZ 18928
MixedKpiModel::updateProductVersion();

// 13/12/2010 BBX : bz18510 Récupération de tous les produits sans les produits éventuellement désactivés
$products = ProductModel::getActiveProducts();

// 21/01/2011 OJT : Liste de TOUS les produits pour gérer le nombre max de produit affichés
$allProducts = ProductModel::getProducts();

// 15/02/2011 BBX
// On va afficher le menu des produits slave si un produit est désactivé
// BZ 20718
$affichageForceProduit = false;
if(count(ProductModel::getInactiveProducts()) > 0)
    $affichageForceProduit = true;

// on verifie qu'on a le droit de se connecter
// on ne peut PAS se connecter si un master a été definit ET qu'on est pas sur le master
$connectionGranted = true;
foreach ($products as $product)
{
    // Un master existe ?
    if ($product['sdp_master'] == 1)
    {
		// NSE 18/12/2009 bz13130 différenciation nom base, rep install et alias : la comparaison était faite avec l'alias (NIVEAU_0) au lieu du répertoire physique
		// 09/12/2011 ACS Mantis 837 DE HTTPS support
		// On est sur un autre produit que le master ?
        if (trim('home/'.$product['sdp_directory'],'/') != trim(REP_PHYSIQUE_NIVEAU_0,'/')) {
			$productModel = new ProductModel($product['sdp_id']);
			
			$connectionGranted = false;
			// 13/09/2011 MMT DE PAAL1 - Merge de la branche VLC 5_1_0_10_PAA_01_02
			$error = true;
			// maj 15/04/2009 - MPR : Correction du BZ8679 : Accès en https impossible
			// 20/12/2011 ACS BZ 25206 Bad link to master on slave index page
			$urlMaster = $productModel->getCompleteUrl('', false, true);
		}
    }
}

// ********************************************** //
// ***** DECONNEXION (NAVIGATION > LOG OUT) ***** //
// ********************************************** //

if ( isset($_GET['logout']) && $_GET['logout'] == 'ok' ) {
	$session = session_id();

	// ************************************************
	// 03/06/2009 BBX : gestion des user statistics. BZ 9751
    // 30/12/2010 17:19 SCT : Optimisation du code BZ 19673
    //	$database = new DatabaseConnection();
    $database = Database::getConnection();
	// Id user
	$id_user = $_SESSION["id_user"];

	// Dernière connexion
	$query = "SELECT last_connection FROM users WHERE id_user = '$id_user'";
	$result = $database->getRow($query);
	$lastConnection = $result['last_connection'];
	list($lastConnectionDay,$lastConnectionHour) = explode('@',$lastConnection);
	$lastConnectionDayArray = explode(',',$lastConnectionDay);
	$lastConnection = sprintf('%04d%02d%02d %s',$lastConnectionDayArray[2],$lastConnectionDayArray[1],$lastConnectionDayArray[0],$lastConnectionHour);
	$timeStampLastConnection = strtotime($lastConnection);

	// Id session
	$query = "SELECT id_session FROM users WHERE id_user = '$id_user'";
	$result = $database->getRow($query);
	$session_uniq_id = $result['id_session'];

	$start_connection = date("Y-m-d H:i:s",$timeStampLastConnection);
	$end_connection = date("Y-m-d H:i:s");
	$duration_session = strtotime($end_connection) - strtotime($start_connection);

	$query = "DELETE FROM track_users WHERE id_session='$session_uniq_id'";
	$database->execute($query);

	$query = "INSERT INTO track_users (id_user,start_connection,end_connection,duration_connection,id_session)
	VALUES ('$id_user','$start_connection','$end_connection','$duration_session','$session_uniq_id')";
	$database->execute($query);
	// ************************************************

	if ( session_register_check($session) )
		session_raz($session);
	session_unset(); // on efface toutes les variables de session
	session_destroy(); // on detruit la session en cours.

	// 15:41 12/08/2009 GHX
	// Correction du BZ 6652
	if (isset($_COOKIE[session_name()])) {
		setcookie(session_name(), '', time()-42000, '/');
	}
	if (isset($_COOKIE[session_id()])) {
		setcookie(session_id(), '', time()-42000, '/');
	}

	// modif 10/01/2008 Gwénaël
		// Supprime le cookie lié à la gestion des liens externes si l'utilisateur se déconnecte
	// 09:16 27/08/2009 GHX
	// On passe une instance de DatabaseConnection au lieu de passé l'ancienne variable $database_connection
    // 30/12/2010 17:19 SCT : Optimisation du code BZ 19673
    //	$link = new ExternalLink(new DatabaseConnection());
    $link = new ExternalLink(Database::getConnection());
	$link->deleteCookie();

	// 13/09/2011 MMT DE PAAL1 - Merge de la branche VLC 5_1_0_10_PAA_01_02
	// 11/01/11 VLC: PAA lot 1
	// en cas de logout, après avoir cloturé la session:
	//  - soit on a un portail
	//       -> si accès depuis portail, on ferme simplement la fenêtre
	//       -> si accès direct, on déloggue le user et on redirige
	//  - soit on n'a pas de portail, on poursuit la redirection normalement

	// redirection normale
	// maj 15/04/2009 - MPR : Correction du BZ8679 : Accès en https impossible
	// 09/12/2011 ACS Mantis 837 DE HTTPS support
	$url = ProductModel::getCompleteUrlForMasterGui();

	// 16/12/2009 NSE remplace erreur_sessiofooteersn par erreur_session
	if ( isset($_GET["erreur_session"]) )
		$url = '?erreur_session='.$_GET["erreur_session"];

	// 13/09/2011 MMT DE PAAL1 - Merge de la branche VLC 5_1_0_10_PAA_01_02
	if (PAA_SERVICE == PAAAuthenticationService::$TYPE_CAS && !isset($_GET["erreur_session"])) {
		// fermeture de la fenêtre
		// ATTENTION: ne fonctionne que si la fenêtre courante a été réellement ouverte par portail
		// sinon, renvoie page vide => attention au paramétrage!
		echo '
			<script type="text/javascript" >
				isOpened = window.opener;
				if (isOpened != null) {
					window.close();
				}
				else {
					parent.top.location.href = "index.php?unlog=true";
				}
			</script>
		';
		exit();
	} else {

		// On utilise une redirection via javascript et non avec header de php
		// car sinon elle faire une redirection uniquement dans l'iframe
		echo '<script>parent.top.location.href = "'.$url.'";</script>';
		exit();
	}
}

// ************************************** //
// ***** GESTION DES LIENS EXTERNES ***** //
// ************************************** //

$external = false;
// 09:16 27/08/2009 GHX
// On passe une instance de DatabaseConnection au lieu de passé l'ancienne variable $database_connection
// 18/09/2009 BBX : on passe l'id du produit s'il est présent. BZ 11206
// 30/12/2010 17:19 SCT : Optimisation du code BZ 19673
//$link = new ExternalLink(new DatabaseConnection($_GET['product']));
$link = new ExternalLink(Database::getConnection(isset($_GET['product']) ? $_GET['product'] : ''));
if ( $link->isExternalLink($_GET) || $link->isInternalLink($_GET)) {
	$external = true;
	unset($_SESSION['file_a_charger']);
	if ( $link->checkParameters($_GET) ) {
		$link->saveParameters();
		if ( $link->checkCookie() ) {
			$_SESSION['goExternalLink'] = true;
			$link->redirect();
		}
	} else {
		$from = '';
		$f = $link->getFrom();
		if ( $f )
			$from = ' from '.$f;
		// 13/09/2011 MMT DE PAAL1 - renome $msg_error pour eviter conflits
		$msgerror = '<div style="text-align:left">';
		$msgerror .= __T('U_E_EXTERNAL_LINK_INVALID_LINK', $from);
		$msgerror .= '<br /><ul><li>'.implode('</il><li>', $link->getErrors()).'</li></ul>';
		$msgerror .= __T('U_E_EXTERNAL_LINK');
		$msgerror .= '</div>';
		if ( $link->checkCookie() ) {
			$display_login = false;
			// 13/09/2011 MMT DE PAAL1 - ajout $display_btn_enter
			$display_btn_enter = true;
			$display_btn_back = true;
		}
		else {
			$_SESSION['errorExternalLink'] = $msgerror;
		}
	}
}

$power_by		= get_sys_global_parameters('power_by');
$product_name		= get_sys_global_parameters('product_name');
$product_version	= reduce_num_version(get_sys_global_parameters('product_version'));


$title 		 = 'Welcome to '.$product_name.' '.$product_version;
$module   	 = get_sys_global_parameters('module');

// 13/09/2011 MMT DE PAAL1 - deplacement test division par zero pour erreur critique en CAS
// ************************************** //
// ***** GESTION PATCH DIV PAR ZERO ***** //
// ************************************** //

// 28/06/2010 NSE - Division par zéro : test du module
$kpi_formula = '256::real/0';
$divZeroErr = "";
foreach ($products as $product){
	$database = new DatabaseConnection($product['sdp_id']);
	$test_query = "SELECT $kpi_formula";
	if (! ($result = $database->getRow($test_query)) )
	{
		if(empty($divZeroErr))
			$divZeroErr = 'Database patch not installed on T&A:';
		$divZeroErr .= '<li>'.$product['sdp_label'].' on server '.$product['sdp_ip_address'].'</li>';
	}
}

// 13/09/2011 MMT DE PAAL1
// ************************************** //
// *****    EXTRACTION DONNEE CLE   ***** //
// ************************************** //

$footer_header = '';
if( count($products) == 0 ){

	// maj 11/12/2008 - MPR : Appel à la classe Key pour y extraire ses données
	$key = get_sys_global_parameters('key');
	$key_instance = new Key();
	$key_decript 		= $key_instance->Decrypt($key);
	$nb_elems_in_key 	= $key_instance->getNbElemsKey();
	$na_in_key 		= $key_instance->getNaKey();
	$date_expiration_in_key	= $key_instance->displayKeyEndDate();

	$footer_header =  $power_by.' '.date ('Y').' - '.$product_name.' '.$product_version.'<br /><div align="center"> '.		$date_expiration_in_key.'.</div>';

}else{
	$footer_header = $power_by.' '.date ('Y').' - List of Products : ';
}

if ( isset($_GET ["error"]) ) {
	$error = true;
	// 13/09/2011 MMT DE PAAL1 - Merge de la branche VLC 5_1_0_10_PAA_01_02
	$param_error = '?error='.$_GET["error"];

	if ( isset($_GET["msg_erreur"]) ) {
		$msg_error = $_GET["msg_erreur"];
	} else {
		// 13/09/2011 MMT DE PAAL1 - Merge de la branche VLC 5_1_0_10_PAA_01_02
		// Si le portail n'est pas actif et qu'on a des erreurs, on vide le flag d'authentification
		// pour qu'en cas de fermeture/réouverture de la page, on ne garde pas ce statut
		if (PAA_SERVICE != PAAAuthenticationService::$TYPE_CAS) 
                    unset($_SESSION['islogged']);

		switch ( $_GET["error"] ) {
			// Erreur de login ou de mot de passe
			case 'login_passwd' :
				$msg_error = __T('G_E_LOGIN_INVALID');
				break;

			// L'utilisateur est déjà connecté sur une autre session
			case 'link':
				$display_login = false;
				// 13/09/2011 MMT DE PAAL1 - ajout $display_btn_enter
				$display_btn_enter = true;
				$msg_error = $_SESSION['errorExternalLink'];
				unset($_SESSION['errorExternalLink']);
				$url_form = 'acces_intranet.php';
				break;


			case 'expired':
				// Clé invalide/null/expirée
				$msg_error = __T('G_E_LOGIN_EXPIRED');
				// Saisi de la clé si c'est un profile de type ADMIN
				if ( $_SESSION['profile_type'] == 'admin' ) {
					$display_key = true;
					$key = get_sys_global_parameters('key');
				}
				break;

			case 'used':
                // 23/02/2012 NSE DE Astellia Portal Lot2
                $errorBlockingForAuthentication = true;
				$display_login = false;
				// 13/09/2011 MMT DE PAAL1 - ajout $display_btn_enter & $display_btn_back
				$display_btn_enter = true;
				$display_btn_back = true;
				$msg_error = __T('G_E_LOGIN_ALREADY_CONNECTED', $_SESSION['login']);
				$_SESSION['killing_session'] = true;

				ob_start();
                // 30/12/2010 17:19 SCT : Optimisation du code BZ 19673
                //$database = new DatabaseConnection();
                $database = Database::getConnection();
				ob_end_clean();
				// Id user
				$id_user = $_SESSION["id_user"];

				// maj 25/08/2009 - MPR : Correction du bug 11214 : On vide le caddy
				$sql = "DELETE FROM sys_panier_mgt WHERE id_user='$id_user'";
				$database->execute($sql);

				// 13/09/2011 MMT DE PAAL1 - Merge de la branche VLC 5_1_0_10_PAA_01_02
				unset($_SESSION['islogged']);
				$url_form = 'acces_intranet.php';
				break;

			/* 15/05/2009 - SPS : ajout du cas ou la date de validite du login de l'utilisateur a expiree (correction bug 9591)*/
			case 'login_date_valid_expired':
				$msg_error = __T('U_LOGIN_DATE_VALID_EXPIRED');
				break;
			// 13/09/2011 MMT DE PAAL1 - Merge de la branche VLC 5_1_0_10_PAA_01_02
			// 10/01/2011 VLC: PAA lot 1, ajout du cas user authentifié au portail mais inconnu dans l'appli
			case 'login_unknown':
				$msg_error = __T('U_LOGIN_UNKNOWN',$_SESSION['login']);
				break;
		}
	}
}
elseif ( isset($_GET ["erreur_session"]) ) {
	$error = true;
	// 13/09/2011 MMT DE PAAL1 - Merge de la branche VLC 5_1_0_10_PAA_01_02
	$errorBlockingForAuthentication = false; // les timeout de session ne sont pas bloquantes pour l'authentification CAS
	$param_error = '?error_msg='.$_GET ["erreur_session"];
	$msg_error = $_GET ["erreur_session"];
}

$product_error = '';
foreach($products as $index => $prod){

	// 11:33 22/06/2009 GHX
	//: Prise en compte du cas où il n'est pas possible de ce connecter sur un produit on le désactive
	// 24/03/2010 NSE bz 14815 : on ne désactive plus les produits pour lesquels on n'a pas de connexion à la BD (trop violent)
	ob_start();
 // 30/12/2010 17:19 SCT : Optimisation du code BZ 19673
 // $database = new DatabaseConnection($prod['sdp_id']);
 $database = Database::getConnection($prod['sdp_id']);
	ob_end_clean();
	// Si pas de connexion ...
	if ( !$database->getCnx() )
	{
		// ... On ajoute un message dans le trace_log ...
		$message = __T('G_E_PRODUCT_CONNECTION_PB', $prod['sdp_label']);
		sys_log_ast("Critical", get_sys_global_parameters("system_name"), __T('A_TRACELOG_MODULE_LABEL_CHECK_PROCESS_EXECUTION_TIME'), $message, "support_1", "");
		// ... On supprime le produit du tableau ...
		unset($products[$index]);
		// ... On passe au produit suivant
		$product_error .= $message.'<br>';
	  $affichageForceProduit = true;
		continue;
	}

	// ****************************** //
	// ***** MISE A JOUR DE CLÉ ***** //
	// ****************************** //

	if ( isset($_POST['key_'.$prod['sdp_id']]) && !empty($_POST['key_'.$prod['sdp_id']]) ) {

		$query = "UPDATE sys_global_parameters SET value = '".$_POST['key_'.$prod['sdp_id']]."' WHERE parameters = 'key'";
		$database->execute($query);

		$query = "UPDATE sys_global_parameters SET value=0 WHERE parameters='max_na_key_exceeded'";
		$database->execute($query);

		// On efface la session pour eviter d'avoir la page qui indique que l'utilisateur est déjà connecté
		// car l'utilisateur n'a pas pu se connecter car la clé est invalide
		$session = session_id();
		if ( session_register_check($session) )
			session_raz($session);
		session_unset(); // on efface toutes les variables de session
		session_destroy(); // on detruit la session en cours.

	}

	// ***************************************************** //
	// ***** RECUPERE LES DONNES POUR L'AFFICHAGE HTML ***** //
	// ***************************************************** //
	// 03/01/2011 16:39 SCT : appel du tableau de sys_global_parameters
	unset($tableauAllSGP);
	$tableauAllSGP 		= taCommonFunctions::getAllSysGlobalParameters($prod['sdp_id']);
	$key                    = $tableauAllSGP['key'] === false ? null : $tableauAllSGP['key'];
	$key_instance		= new Key();
	$key_decript 		= $key_instance->Decrypt($key);
	$nb_elems_in_key 	= $key_instance->getNbElemsKey();
	$na_in_key 		= $key_instance->getNaKey();
	$date_expiration_in_key	= $key_instance->displayKeyEndDate();

	// vérification de le surplus d'élément réseau sur la clé
	$tempNaKyExceed = $tableauAllSGP['max_na_key_exceeded'] === false ? 0 : $tableauAllSGP['max_na_key_exceeded'];
	if($tempNaKyExceed)
	{
	  $multiProduitCle[$prod['sdp_id']] = 'License to be upgraded. Maximum number of '.$na_in_key.' is limited to '.$nb_elems_in_key.'.';
	}

	$power_by                   = $tableauAllSGP['power_by'] === false ? 0 : $tableauAllSGP['power_by'];
	$product_name 		= $tableauAllSGP['product_name'] === false ? 0 : $tableauAllSGP['product_name'];
	$tempProductVersion         = $tableauAllSGP['product_version'] === false ? 0 : $tableauAllSGP['product_version'];
	$product_version		= reduce_num_version($tempProductVersion);
	$footers[ $prod['sdp_id'] ] = $product_name.' '.$product_version.' ['.$prod['sdp_label'].']';

	// 18/04/2011 OJT : Exclusion de la gestion de clé pour certains produits
	if ( !Key::isProductManageKey( $prod['sdp_id'] ) ) continue;

	$footers[ $prod['sdp_id'] ].= ($date_expiration_in_key !== "") ? "- $date_expiration_in_key.": ".";

	// ***************************************** //
	// ***** GESTION DE MESSAGES D'ERREURS ***** //
	// ***************************************** //

	// Erreur : login/session/clé
	if ( isset($_GET ["error_".$prod['sdp_id']]) ) {
		$error = true;
		// 13/09/2011 MMT DE PAAL1 - Merge de la branche VLC 5_1_0_10_PAA_01_02
		$param_error .= "&error_".$prod['sdp_id']."=".$_GET ["error_".$prod['sdp_id']];
		$param_error = substr_replace($param_error, '?', 0, 0);

		if ( isset($_GET["msg_erreur"]) ) {
			$msg_error.= $_GET["msg_erreur"];
		}
		elseif( $_GET["error_".$prod['sdp_id']] == 'expired_'.$prod['sdp_id']) {
			// Clé invalide/null/expirée
			$msg_error = __T('G_E_LOGIN_EXPIRED');
			// Saisi de la clé si c'est un profile de type ADMIN
			if ( $_SESSION['profile_type'] == 'admin' ) {
				$display_key = true;
				$key = get_sys_global_parameters('key',0,$prod['sdp_id']);
			}
			$affichageForceProduit = true;
		}elseif(  $_GET["error_".$prod['sdp_id']] == 'key_invalid_'.$prod['sdp_id'] ){

			// Clé invalide/null/expirée
			$msg_error = __T('A_ABOUT_KEY_NOT_VALID');
			// Saisi de la clé si c'est un profile de type ADMIN
			if ( $_SESSION['profile_type'] == 'admin' ) {
				$display_key = true;
				$key = get_sys_global_parameters('key',0,$prod['sdp_id']);
			}
			$affichageForceProduit = true;
		}elseif(  $_GET["error_".$prod['sdp_id']] == 'na_key_invalid_'.$prod['sdp_id'] ){

			// Clé invalide/null/expirée
			$msg_error = __T('A_ABOUT_NA_IN_KEY_NOT_VALID');
			// Saisi de la clé si c'est un profile de type ADMIN
			if ( $_SESSION['profile_type'] == 'admin' ) {
				$display_key = true;
				$key = get_sys_global_parameters('key',0,$prod['sdp_id']);
			}
			$affichageForceProduit = true;
		}
	}
	elseif ( isset($_GET ["erreur_session"]) ) {
		$error = true;
		// 13/09/2011 MMT DE PAAL1 - Merge de la branche VLC 5_1_0_10_PAA_01_02
		$errorBlockingForAuthentication = false; // les timeout de session ne sont pas bloquantes pour l'authentification CAS
		$msg_error = $_GET ["erreur_session"];
	}
}



// 13/09/2011 MMT DE PAAL1 - gestions specifiques mode CAS
// 23/02/2012 NSE DE Astellia Portal Lot2 : ajout du cas File
if (PAA_SERVICE == PAAAuthenticationService::$TYPE_CAS || PAA_SERVICE == PAAAuthenticationService::$TYPE_FILE) {
	// gestion erreur pour div par zero en mode cas: erreur critique
	if (!empty($divZeroErr)){
		$criticalError = true;
		$msg_error = $divZeroErr;
	}
	//en CAS, toutes les erreur bloquantes sont critiques: pas le login
	if($error == true && $errorBlockingForAuthentication == true){
		$criticalError = true;
	}
}

// 13/09/2011 MMT DE PAAL1 - gestion des erreur critiques : pas de login ni boutton d'entré
if ($criticalError){
	$display_login = false;
	$error = true;
	$errorBlockingForAuthentication = true;
}

// ********************************************* //
// *****    GESTION MODE AUTENTICATION     ***** //
// ********************************************* //

// 13/09/2011 MMT DE PAAL1 - Merge de la branche VLC 5_1_0_10_PAA_01_02
// On vérifie si le user est déjà authentifié
if($PAAAuthentication){
	$ret = $PAAAuthentication->validateAuthentication();
	//Si on vient d'une redirection suite à un logout, il faut faire ce logout
	if (isset($_GET["unlog"])&&$_GET["unlog"]) {
		$PAAAuthentication->logout();
		$ret = $PAAAuthentication->validateAuthentication();
                // 26/03/2012 NSE bz 26417 on ne poursuit pas le traitement dans le cas d'une déconnexion.
                exit;
	}
}
    
// 13/03/2012 NSE bz 26260 : prise en compte de l'existence de droits utilisateur pour accéder à l'appli
// utilisation d'une variable concernant l'absence de droits
$withoutRights = false;
if ($ret){
    // si authentifié, on récupère l'utilisateur
    $user = $PAAAuthentication->getUser();
    $login = $user->getLogin();
    $_SESSION['login']    = $login; 
    // on vérifie que l'utilisateur a des droits sur l'application
    // 24/04/2012 NSE bz 26636 : utilisation de l'API Portail v 1.0.0.04
    // si ce n'est pas "Astellia Admin"
    // 15/03/2013 GFS BZ#30459 : [REC][TA-62164]Portal :can still login with deleted user 
    // 16/02/2015 FGD BZ#42411 : [REC][IU 5.3.2.02][TC#TASW-1819][Login and entrance refusal] Successfully login to T&A directly with user which doesn't have any role in T&A
    if(!($PAAAuthentication->isValidUser($_SESSION['login']))){
    	//login isn't valid (blocked?)
    	$withoutRights = true;	
    }elseif(!$PAAAuthentication->isSupportUser($_SESSION['login'])){
        // on récupère la liste des droits de l'utilisateur déclarés sur le Portails
        $rights = $PAAAuthentication->getUserRights($login,APPLI_GUID_HEXA.'.'.APPLI_GUID_NAME);
        // 27/04/2012 NSE bz 27026 : traduction du Guid en Id
        $rights = array_map('ProfileModel::getIdFromPaaGuid',$rights);
        if(empty($rights)){
            $withoutRights = true;
            $param_error = '?error_msg='.htmlentities('No rights for this application');
        }
    }
}

// si authentifié et pas d'erreur bloquante pour l'authentication, on saute la page de login
if ($ret && ($error == false || $errorBlockingForAuthentication == false) && !$withoutRights) {
	// Si on a rencontré une erreur précédemment (timeout session), on inscrit le message dans la session
	// pour pouvoir afficher une alerte à l'utilisateur
	 if ( $error === true ) {
		$_SESSION['session_reloaded_msg'] = $msg_error;
	}
	// redirection vers controle_session pour entrer dans T&A
	header('Location:controle_session.php');

} else {
    
	// si pas authentifié, affichage du login du Portail si pas d'erreur à afficher
	if (PAA_SERVICE == PAAAuthenticationService::$TYPE_CAS && ($error == false || $errorBlockingForAuthentication == false) && !$withoutRights) {
		$login_url = $PAAAuthentication->getLoginUrl() . $param_error;
		// redirection vers portail avec parametre d'erreurs si existe
		header('Location:'.$login_url);
		exit;
	}
        // 23/02/2012 NSE DE Astellia Portal Lot2
        // on redirige vers la page de login local si on est en mode FILE
        elseif (PAA_SERVICE == PAAAuthenticationService::$TYPE_FILE && $error == false){
            header('Location:'.'api/paa/login/form_login.php?url='.NIVEAU_0.'index.php');
            exit;
        }
        elseif($withoutRights){
            // 13/03/2012 NSE bz 26260 : prise en compte de l'existence de droits utilisateur pour accéder à l'appli
            // si l'utilisateur n'a pas de droits sur l'appli
            // logout de l'appli
            $PAAAuthentication->logout();
            // retour authentifié dans PAA
            $ret = $PAAAuthentication->validateAuthentication(); 
        }
	//sinon affichage login T&A
// ********************************************* //
// ***** AFFICHAGE DE LA PAGE DE CONNEXION TA ** //
// ********************************************* //
?>
<html>
<head>
	<title><?php echo $title; ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<link rel="stylesheet" href="css/global_interface.css" type="text/css">
	<?php /* 07/04/2009 - modif SPS : ajout de la librairie prototype */ ?>
	<script type='text/javascript' src='js/prototype/prototype.js'> </script>
	<script type='text/javascript' src="js/gestion_fenetre.js"></script>
    <script type='text/javascript' src="js/gestionRequeteAjax.js"></script>
</head>
<body bgcolor="#ffffff" onLoad="checkSSHConfig('ssh_configuration_msg_error'); if(document.getElementById('login')) document.getElementById('login').focus();">
<script type="text/javascript">
var requeteAjax;

// méthode pour la vérification des services SSH
function checkSSHConfig()
{
    var accessGranted = <?=$connectionGranted ? 1 : 0?>;
    if(accessGranted == '1')
    {
        requeteAjax = new Ajax.Request('index_ajax.php',{
            method:'post',
            asynchronous:'true',
            parameters:'accessGranted='+accessGranted,
            onSuccess: function(res) {
                // 18/03/2011 BBX
                // Nettoyage code obsolète
                // BZ 20718
                if(res.responseText != '')
                {
                    if($('ssh_configuration'))
                    {
                        $('ssh_configuration').src='images/icones/exclamation.png';
                    }
                    // on découpe le message en host
                    var textReponse = res.responseText;
                    // dans le cas d'un problème sur le module ssh2_connect du master
                    if(textReponse == 'master')
                    {
                        handles = $$('.masterSshAjax');
                        for (i=0; i<handles.length; i++) {
                            handles[i].setStyle({backgroundColor: '#F8DED1'});
                        }
                        $('masterSshAjaxMessage').setStyle({display: 'block'});
                    }
                    // dans le cas d'un slave
                    else
                    {
                        var tableauHost = textReponse.split('|s|');
                        for(compteur=0; compteur < tableauHost.length; compteur++)
                        {
                            // on recherche les éléments dont la classe correspond
                            handles = $$('.'+tableauHost[compteur]);
                            for (i=0; i<handles.length; i++) {
                                handles[i].setStyle({backgroundColor: '#F8DED1'});
                            }
                            // on recherche les éléments dont la classe correspond
                            handles2 = $$('.slave_'+tableauHost[compteur]);
                            for (j=0; j<handles2.length; j++) {
                                handles2[j].setStyle({display: 'block'});
                            }
                        }
                        // affichage de la liste des produits
                        // 15/02/2011 BBX
                        // Correction de la fonction
                        // BZ 20718
                        if($('listeProduits').style.display == 'none') {
                            basculeAffichage($('show_hidden_remote_product'));
                        }
                    }
                }
            }
        });
    }
}
// méthode pour la destruction du flux Ajax
function destructionRequeteAjax()
{
    requeteAjax.abort();
}

// méthode pour la bascule de l'affichage de la liste des produits
// 15/02/2011 BBX
// Correction de la fonction
// BZ 20718
function basculeAffichage(me)
{
    if($('listeProduits').style.display == 'block') {
        me.innerHTML = '<?php echo __T('A_U_LOGIN_SHOW_SLAVES'); ?>';
        $('listeProduits').style.display = 'none';
    }
    else {
        me.innerHTML = '<?php echo __T('A_U_LOGIN_HIDE_SLAVES'); ?>';
        $('listeProduits').style.display = 'block';
    }
}
</script>
<div align="center" style="padding-top:20px;height:70%">
	<? 
	// 20/09/2012 ACS BZ 29191 Wrong image when loging failed
	if (CorporateModel::isCorporate()) {
	?>
		<img src="<?=NIVEAU_0?>images/default/titre_corporate.png" style="margin: 30px 0 50px 0;" /><br />
	<? } else { ?>
		<img src="<?=NIVEAU_0?>images/default/titre.png" style="margin: 30px 0 50px 0;" /><br />
	<? } ?>

	<!-- Affichage du message d'erreur -->
	<? if ( $error === true && $display_login === true ) { ?>
		<div class="texteRouge" align="center"><b><?php echo $msg_error; ?></b></div>
	<? } ?>

	<form name="formulaire" method="post" action="<?php echo $url_form; ?>">
		<table cellpadding="0" cellspacing="0" border="0">
			<tr>
				<td><img src="images/accueil/chg.gif" width="6px" height="5px"></td>
				<td style="background-image: url('images/accueil/fdh.gif'); background-repeat: repeat-x;"></td>
				<td><img src="images/accueil/chd.gif" width="6px" height="5px"></td>
			</tr>
			<tr>
				<!-- BEGIN : Affichage des champs pour la connexion ou affichage d'une erreur -->
				<?php
				// 13/09/2011 MMT DE PAAL1 - si la connection est refusée (connection au slave il faut afficher le bon message)
				if ( !$connectionGranted || $display_login === true ) { ?>
					<td width="5px" style="background-image: url('images/accueil/fdcg.gif'); background-repeat: repeat-y;"></td>
					<td class="fondPrincipal">
						<table cellpadding="4" cellspacing="4" border="0">
							<tr>
								<td><img src="images/icones/icone_connect.gif"></td>
								<td align="center">
									<?php
										// maj 15/12/2008 - BBX
										// 3) Si la connexion ne doit pas avoir lieu, on la bloque
										if (!$connectionGranted) {
											$display_login = false;
											echo '<div class="errorMsg">'.__T('A_SETUP_PRODUCTS_CANNOT_CONNECT').'</div>';
											echo '<div><a href="'.$urlMaster.'">'.$urlMaster.'</a></div>';
										} else {
											?>
											<table border="0" cellspacing="3" cellpadding="2">
												<tr>
													<td class="texteGrisBold">Login :</td>
													<td><font> <input type="text" id="login" class="zoneTexteStyleXP" name="login" size="18" maxlength="30"></font></td>
												</tr>
												<tr>
													<td width="100" class="texteGrisBold">Password :</td>
													<td><font> <input type="password" class="zoneTexteStyleXP" name="password" size="18" maxlength="30"> </font></td>
												</tr>
											</table>
										<?php
										}
									?>
								</td>
							</tr>
						</table>
					</td>
				<?php } else { ?>
					<td width="5px"style="background-image : url('images/accueil/fdcg.gif'); background-repeat:repeat-y;"></td>
					<td class="fondPrincipal">
						<?php /* 07/04/2009 : modif SPS : ajustement du style */ ?>
						<table cellpadding="5" cellspacing="4"  align="center">
							<tr>
								<td class="texteGrisBold" align="center"><?php echo $msg_error; ?></td>
							</tr>
							<tr>
						<?php
							// 13/09/2011 MMT DE PAAL1 - gestion $display_btn_enter
							if (!$display_btn_enter){ ?>
								<td align="center" class="texteGrisBold">
										<a href="index.php?logout=ok">Logout </a>
								</td>
						<?php } else { ?>
								<td align="center" class="texteGrisBold">
									<input type="submit" class="bouton" value="Enter" id="login">
								<!-- BEGIN : affichage du bouton "Back" -->
							<?php
									// 13/09/2011 MMT DE PAAL1 - gestion $display_btn_back
									// en Mode CAS on affiche 'exit' et on ferme la fenetre
								  if ( $display_btn_back ) {
									  if (PAA_SERVICE == PAAAuthenticationService::$TYPE_CAS){ 
                                                                              // 08/11/2011 NSE bz 24505 : 
                                                                              // fermeture de la fenêtre uniquement si on est passé par le portail pour ouvrir le T&A
                                                                              // si on a utilisé directement l'url, on revient sur la page de login du CAS.
                                                                              // 17/11/2011 NSE bz 24505 reopen : l'appel à fermeture() n'était pas fait dans le lien!
                                                                              ?>
<script type="text/javascript" >
    function fermeture(){
        isOpened = window.opener;
        if (isOpened != null) {
                window.close();
        }
        else {
                parent.top.location.href = "index.php?unlog=true";
        }
    }
</script>
										  &nbsp;&nbsp;&nbsp;<a href="javascript:fermeture()">>> Exit</a>
									 <?php } else { 
                                                                             // 30/11/2011 NSE bz 24845 : on propose Enter/Exit à l'utilisateur (au lieu de Enter/Back) 
                                                                             // (on ne sait pas s'il vient de la page de login ou d'un Dashboard)
                                                                             ?>
										  &nbsp;&nbsp;&nbsp;<a href="index.php?logout=ok">>> Exit</a>
									 <?php } ?>
							<?php } ?>
									<!-- END : affichage du bouton "Back" -->
								</td>
							<?php } ?>
							</tr>
						</table>
					</td>
				<?php } ?>
				<!-- END : Affichage des champs pour la connexion ou affichage d'une erreur -->

					<td width="5px" style="background-image: url('images/accueil/fdcd.gif'); background-repeat: repeat-y;"></td>
				</tr>
				<tr height="5px">
					<td><img src="images/accueil/cbg.gif" width="6px" height="5px"></td>
					<td style="background-image: url('images/accueil/fdb.gif'); background-repeat: repeat-x;"></td>
					<td><img src="images/accueil/cbd.gif" width="6px" height="5px"></td>
				</tr>

			<!-- Affichage du bouton display uniquement s'il y a les champs input pour se connecter -->
			<?php if ( $display_login === true ) { ?>
				<tr>
					<td align="center" valign="top" colspan="3"><input type="image" src="images/boutons/bt_enter.gif" name="Submit" value="" border="0" onClick="destructionRequeteAjax();"></td>
				</tr>
			<?php } ?>

		</table>
	</form>


	<!-- liste des produits -->
	<div align="center">
		<table cellpadding="0" cellspacing="0" border="0" class="texteAccueil">
			<tr><th><?=$footer_header?></th></tr>
			<tr><td>

			<?php
            $baliseDebut = 0;
			// maj MPR 08/01/2009 : Affichage de tous les produits

                // dans le cas où une clé est périmée, on affiche directement la liste
                $clePerimee = 0;
                if(isset($multiProduitCle))
                {
                    $clePerimee = 1;
                }

                foreach($products as $id_prod=>$prod)
                {
					$id_prod = $prod['sdp_id'];

                    if($prod['sdp_master'] != 1 && $baliseDebut == 0 && count( $allProducts ) > MAX_PRODUCTS_DISPLAY )
                    {
                        // 15/02/2011 BBX
                        // Modification du comportement de l'affichage
                        // Si produit désactivé ou problème SSH
                        // on affiche la liste des produits
                        // sinon on la cache par défaut
                        // BZ 20718
                        $htmlListeProduitStyle = ' style="display: none;"';
                        $htmlTextShowHiddeRemoteProduct = __T('A_U_LOGIN_SHOW_SLAVES');
                        $baliseDebut = 1;
                        if($clePerimee == 1 || $affichageForceProduit)
                        {
                             $htmlListeProduitStyle = ' style="display: block;"';
                             $htmlTextShowHiddeRemoteProduct = __T('A_U_LOGIN_HIDE_SLAVES');
                        }
                        echo '<li class="texteAccueil" id="accessListeProduit">
                                <span id="show_hidden_remote_product" style="text-decoration: underline; cursor: pointer;" onClick="basculeAffichage(this);">'.
                                    $htmlTextShowHiddeRemoteProduct.'
                                </span>&nbsp;('.( count( $allProducts ) - 1 ).')
                            </li>';
                        echo '<fieldset id="listeProduits"'.$htmlListeProduitStyle.'><ul style="margin:2px;">';
                    }
                    $messageAdditionnel = '';
                    $messageProbleme    = '';
                    $messageClassMaster = '';
                    $liStyle            = 'margin-top:2px;';

                    // on indique qui est le master
                    if ($prod['sdp_master'] == 1)
                    {
                        $messageAdditionnel .= ' - <strong style="color:#900">Master</strong>';
					}
                    // on indique qui est le master topo
                    if ($prod['sdp_master_topo'] == 1)
                        $messageAdditionnel .= ' - <strong style="color:#009">Topology master</strong>';

                    // dans le cas d'un multi de moins de 3 produits
                    // 18/03/2011 : on n'affiche plus le message checking SSH
                    // BZ 20718

                    // dans le cas d'un problème avec une clé produit
                    if(isset($multiProduitCle[$prod['sdp_id']]))
                        $messageProbleme   .= '<br /><strong style="color:#900000;">'.$multiProduitCle[$prod['sdp_id']].'<br /></strong>';
                    // on prépare un message de retour pour le contrôle Ajax sur la présence du module SSH2_CONNECT du master
                    if ($prod['sdp_master'] == 1)
                    {
                        $messageAdditionnel .= '<strong id="masterSshAjaxMessage" style="color:#900; display: none;">'.__T('G_E_SSH2_NOT_INSTALLED').'</strong>';
                        $messageClassMaster .= ' masterSshAjax';
                    }
                    // réécriture des adresses IP
                    $stockageIpAdress[$id_prod] = ' '.str_replace('.', '', $prod['sdp_ip_address']);
                    // dans le cas d'un slave avec un problème d'accès SSH
                    if ($prod['sdp_master'] != 1)
                    {
                        $messageAdditionnel .= '<strong class="slave_'.trim($stockageIpAdress[$id_prod]).'" style="color:#900; display: none;">'.__T('G_E_SSH2_NOT_AVAILABLE_ON_REMOTE_SERVER',$prod['sdp_ip_address']).'</strong>';
                    }

                    if( strlen( trim( $messageProbleme ) ) > 0 ){
                        $liStyle .= 'background-color:#F8DED1;';
                    }
                    echo '<li style="'.$liStyle.'" class="texteAccueil'.$stockageIpAdress[$id_prod].$messageClassMaster.'">'.$footers[$id_prod].$messageProbleme.$messageAdditionnel.'</li>';

					if ( isset($_GET["error_$id_prod"]) && $_GET["error_$id_prod"] !== "" && $_SESSION['profile_type'] == 'admin') {
						// MODIF DELTA NOUVEAU (jusqu'à la fin du fichier)
						?>
						<form name="key_form" method="post" action="index.php">
							<span class="texteGrisPetit">
								Product key :&nbsp;
								<input type="text" id="key_<?=$id_prod?>" class="zoneTexteStyleXP" name="key_<?=$id_prod?>" size="40"
									value="<?=get_sys_global_parameters('key',null,$id_prod)?>" />
								&nbsp;
								<input type="submit" class="boutonPlat" value="Update"/>
								<input type="button" class="boutonPlat" value="About" onClick="ouvrir_fenetre('<?=NIVEAU_0?>about.php?profile_type=user','comment','yes','yes',550,330);return false;"/>
							</span>
						</form>
						<?
					}

				}
				// 24/03/2010 NSE bz 14815 : on affiche le message d'erreur pour les produits pour lesquels on n'a pas de connexion à la BD
				if(isset($product_error)&&!empty($product_error)){
					echo '<div class="errorMsg" style="width:70%">'.$product_error.'</div>';
				}

                // 13/12/2010 BBX
                // Les produits désactivés apparaissent en rouge avec type de désactivation
                // BZ 18510
                foreach(ProductModel::getInactiveProducts() as $p) {
                    $type = $p['sdp_last_desactivation'] == 0 ? 'manual' : 'automatic';
                    echo '<li class="texteAccueil" style="color:red">'.$p['sdp_label'].' is disabled ('.$type.')</li>';
				}
                if($baliseDebut == 1) echo "</ul></fieldset>";
					?>
		</td>
		</tr>
		</table>
	</div>

	<br />
    <div class="errorMsg" id="ssh_configuration_msg_error" style="width:30%; display: none;"></div>
    <?php
	 // 13/09/2011 MMT DE PAAL1 - deplacement tests division par zero
	 if(PAA_SERVICE != PAAAuthenticationService::$TYPE_CAS && !empty($divZeroErr)){
		 echo "<div class='errorMsg' style='width:70%'>$divZeroErr</div>";
	 }

// 04/01/2010 SCT : BZ 19673 => déplacement de la vérification des services SSH locaux et distants via AJAX (sur le onload de la page)
}
?>
</div>
</body>
</html>
