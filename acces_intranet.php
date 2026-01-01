<?php
/**
 * 
 *  CB 5.2
 * 
 * 23/02/2012 NSE DE Astellia Portal Lot2
 * 24/04/2012 NSE bz 26636 : utilisation de l'API Portail v 1.0.0.04 pour la gestion de Astellia Administrator
 */
?><?
/*
*	@cb4100@
*
*	11/12/2008 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.0
*
*	 maj 11/12/2008 - MPR : Appel à la classe Key pour y extraire ses données
*	 maj 11/12/2008 - MPR : Récupération de l'ensemble des produits
*	 maj 08/01/2009 - MPR : Si la clé est invalide, on redirige l'utilisateur sur la page de connexion

	- maj 02/02/2009, benoit : mise en commentaires de l'appel à la table 'sys_user_parameter' qui n'est plus utilisée

	19/05/2009 GHX
		- Définition de la homepage en user

	02/06/2009 BBX :
		- Si une homepage personnalisée est définie, on l'affiche
	27/07/2009 GHX
		- Correction du BZ 10427
	28/07/2009 GHX
		- Ajout du chargement des préférences utilisateurs
	05/08/2009 GHX
		- Correction du BZ 10892 [REC][T&A Cb 5.0][TP#1][TS#UC15-CB50][Homepage] : perte des heures du sélecteur d'un dashboard OT en TA Hour
			-> le problème venait du type de compute mode daily OK / hourly KO
	20/08/2009 GHX
		- Correction du BZ 11075 [REC][T&A CB 5.0][TC#37107][TP#1][DASHBOARD]: paramétrage du calendar en hourly incorrecte
	25/08/2009 MPR ;
		- Correction du bug 11214 : On vide le caddy uniquement sur la déconnexion du user
	27/08/2009 GHX
		- Utilisation de DatabaseConnection pour les liens externes
	07/10/2009 GHX
		- Modification pour ne pas avoir d'erreur sur la clé pour le produit Mixed KPI
	09/11/2009 GHX
		- Correction du BZ 12608 [SUP][T&A Cigale GSM 5.0][Only]:Date par défaut du selecteur erronée
	07/01/2010 NSE
		- Correction du bug 13671 : on force la ta au jour précédent, que les données soient horaires ou journalières
	16/02/2010 NSE
		- Correction du bug 14134 : on ne force pas la homepage si le user en a sélectionné une
	23/02/2010 NSE
		- bz 14134 modification pour gestion HP
    27/07/2010 OJT
        - bz 16951 Perte de info du selecteur au clic sur home
    06/06/2011 MMT
        - DE 3rd Axis remplace colonne sds_na_axe3_element par sds_na_axe3_list de la table sys_definition_selecteur
    03/08/2011 MMT
 		  - bz 22755 application au bug 22896 pour selection 3eme axe chargement homepage
   06/07/2011 MMT
 		- Bz 22891 unité de temps par defaut autre que day/hour n'affiche pas la correcte date
    24/08/2011 MMT
      - DE PAAL1 - Merge de la branche VLC 5_1_0_10_PAA_01_02
	09/12/2011 ACS Mantis 837 DE HTTPS support
*
*/
?>
<?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
* 	- 28/12/2007 Gwénaël : restructuration du fichier dans l'optique d'une meilleure lisibilité et maintenance
* 	  Et aussi pour prendre en compte les liens depuis une application externe (cf. GESTION DES LIENS EXTERNES)
*
* 	- 08/01/2007 Gwénaël : prise en compte des liens externes depuis l'envoie de mail (= type de lien interne)
*
*	- maj 10/01/2008, benoit : à la connexion, on supprime les enregistrements du panier de la session précédente
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
<?php 
session_start();
include dirname( __FILE__ ).'/php/environnement_liens.php';
include dirname( __FILE__ ).'/php/edw_function_menu.php';
include dirname( __FILE__ ).'/php/edw_function_session.php';
include dirname( __FILE__ ).'/php/edw_function_family.php';
include dirname( __FILE__ ).'/class/ExternalLink.class.php';

include_once(REP_PHYSIQUE_NIVEAU_0 . '/api/paa/PAAAuthenticationService.php');

get_activated_faiture();

/**
 * Permet de faire une redirection en PHP via la fonction de header
 *
 * @param string $file_location : nom du fichier vers lequel la redirection doit être faite
 */
function redirection ( $file_location ) {

	// maj 15/04/2009 - MPR : Correction du BZ8679 : Accès en https impossible
	// 09/12/2011 ACS Mantis 837 DE HTTPS support
	$url_location = ProductModel::getCompleteUrlForMasterGui(dirname($_SERVER["PHP_SELF"]).'/');

	header('Location:'.$url_location.$file_location);
	exit();
} // End function redirection


// IMPORTANT : dans l'installation de PHP, il faut que 'register_globals' soit activé.
// Cela facilite le chargement des variables et la récupéartion dans une session.
// ATTENTION : dans PHP6, il ne sera plus possible d'activé 'register_globals' !!
$session_id = session_id();

$id_user  = $_SESSION['id_user'];
$login    = $_SESSION['login'];
$password = $_SESSION['password'];

/* on cherche si un enregistrement correspond à ce qui été saisi */
// 17:10 18/05/2009 GHX
// Ajout du champ "homepage" dans la requete (éviter de refaire une requete pour récupere la valeur)
// 07/06/2011 BBX -PARTITIONING-
// Application des casts nécessaires
// 24/08/2011 MMT DE PAAL1 - Merge de la branche VLC 5_1_0_10_PAA_01_02
// 23/02/2012 NSE DE Astellia Portal Lot2
$query = "
        SELECT t1.login, t1.id_user, t1.username,
                t1.user_agregation_value , network_element_preferences,
				t1.homepage
	        FROM users t1 
        WHERE (t1.login='$login')";

// 31/01/2011 BBX
// On remplace new DatabaseConnection() par Database::getConnection()
// BZ 20450
//$db_connect = new DatabaseConnection();
$db_connect = Database::getConnection();

$result = $db_connect->getAll($query);
$nb_result = count($result);

// >>>>>>>>>
// NORMALEMENT ON NE DEVRAIT JAMAIS RENTRER DANS LA CONDITION
// puisque le login/mdp ont été vérifié avant mais on n'est jamais assez prudent ;)
if ( count($result) == 0 )
	redirection('?error=login_passwd');
// <<<<<<<<<
$row = $result[0];
// 23/02/2012 NSE DE Astellia Portal Lot2
// Si on change de right / Profile
if(isset($fProfile) && !empty($fProfile)){
    $user_profile = $fProfile;
    $profil = new ProfileModel($user_profile);
    $profile_type = ($profil->getProfileType()=='customisateur'?'admin':$profil->getProfileType());
    unset($_SESSION[ $_SESSION['id_user'] ]['saved_menus']);
}
else if(!isset($user_profile) || !isset($profile_type)){
    
    // supprimer le fichier de conf
    $PAAAuthentication = PAAAuthenticationService::getAuthenticationService(REP_PHYSIQUE_NIVEAU_0 . '/api/paa/conf/PAA.inc');

    // récupère les droits de l'utilisateur déclarés sur le Portail
    $rights = $PAAAuthentication->getUserRights($_SESSION['login'],APPLI_GUID_HEXA.'.'.APPLI_GUID_NAME);
    // 27/04/2012 NSE bz 27026 : traduction du Guid en Id
    $rights = array_map('ProfileModel::getIdFromPaaGuid',$rights);
    // 24/04/2012 NSE bz 26636 : utilisation de l'API Portail v 1.0.0.04
    // s'il s'agit de "astellia_admin"
    if($PAAAuthentication->isSupportUser($_SESSION['login'])){
        // on le sélectionne
        $profile_type = 'admin';
        $user_profile = ProfileModel::getAstelliaAdminProfile();
    }
    else{
        //echo '$rights : ';print_r($rights);
        // on cherche lequel correspond à un profile admin
        $admProfiles = ProfileModel::getAdminProfiles(true,$rights);
        // 22/03/2012 NSE bz 26496 : Ajout de .'.'.APPLI_GUID_NAME pour identifier le droit
        if(!empty ($admProfiles)){
            $profile_type = 'admin';
            $user_profile = str_replace(APPLI_GUID_HEXA.'.'.APPLI_GUID_NAME.'.', '', $admProfiles[0]['id_profile']);
        }
        else{
            $profile_type = 'user';
            $user_profile = str_replace(APPLI_GUID_HEXA.'.'.APPLI_GUID_NAME.'.', '', $rights[0]);
        }
    }
}
elseif(is_array($user_profile)&&isset($user_profile['id_profile'])){
    $user_profile = str_replace(APPLI_GUID_HEXA.'.'.APPLI_GUID_NAME.'.', '', $user_profile['id_profile']);
}
//echo ' $user_profile ';print_r($user_profile);
// 17:11 18/05/2009 GHX
// Id de la homepage pour le user courant
$idHomepageUser = $row["homepage"];

$_SESSION['profile_type'] = $profile_type;
$_SESSION['user_profil'] = $user_profile;

if ( isset($_SESSION["killing_session"]) && $_SESSION["killing_session"] == true ) {
	update_user_id_session($id_user, $session_id);
	update_user_last_connection($id_user);
}

// >>>>>>>>>
// Verification la validité de la clé
// maj 11/12/2008 - MPR : Récupération de l'ensemble des produits
$products = getProductInformations();

if(count($products)>0)
{
	foreach($products as $prod)
        {
                // 18/04/2011 OJT : Exclusion de la gestion de clé pour certains produits
		if ( !Key::isProductManageKey( $prod['sdp_id'] ) ) continue;

		$key = get_sys_global_parameters('key',null,$prod['sdp_id']);
		// maj 11/12/2008 - MPR : Appel à la classe Key pour y extraire ses données
		$key_instance =	new Key();
		$decoded_key 			= 	$key_instance->Decrypt($key);
		$nb_elems_in_key 		= 	$key_instance->getNbElemsKey();
		$na_in_key 				= 	$key_instance->getNaKey();
		$date_expiration_in_key	= 	$key_instance->displayKeyEndDate();
		// maj 09/06/2009 - MPR : Correction bug 9593 - On vérifie que le niveau d'agrégation existe bien sur le produit
		$checkNa 				= 	$key_instance->checkNaExistInProduct($na_in_key, $prod['sdp_id']);

		// __debug("$nb_na,$na_type,$valid_date_instal,$valid_date_restitution,$eval");
		list($nb_na,$na_type,$valid_date_instal,$valid_date_restitution,$eval) = explode('@',$decoded_key);

		// maj 08/01/2009 - MPR : Si la clé est invalide, on redirige l'utilisateur sur la page de connexion
		if(!is_numeric($nb_elems_in_key) || empty($na_in_key) || (substr_count($date_expiration_in_key,'--') == 1))
			redirection('?error_'.$prod['sdp_id'].'=key_invalid_'.$prod['sdp_id']);

		if ( !(ereg('^20[0-9]{6}$',$valid_date_restitution) && ($valid_date_restitution > date('Ymd'))) )
			redirection('?error_'.$prod['sdp_id'].'=expired_'.$prod['sdp_id']);

		if ( !$checkNa )
			redirection('?error_'.$prod['sdp_id'].'=na_key_invalid_'.$prod['sdp_id']);

		// <<<<<<<<<

	}
}else{ // maj 11/12/2008 - MPR : cas où aucun produit n'est enregistré en base

	$key = get_sys_global_parameters('key');
	// maj 11/12/2008 - MPR : Appel à la classe Key pour y extraire ses données
	$key_instance =	new Key();
	$decoded_key 			= 	$key_instance->Decrypt($key);
	$nb_elems_in_key 		= 	$key_instance->getNbElemsKey();
	$na_in_key 				= 	$key_instance->getNaKey();
	$date_expiration_in_key	= 	$key_instance->displayKeyEndDate();

	list($nb_na,$na_type,$valid_date_instal,$valid_date_restitution,$eval) = explode('@',$decoded_key);
	// Si la clé est invalide, on redirige l'utilisateur sur la page de connexion
	if ( !(ereg('^20[0-9]{6}$',$valid_date_restitution) && ($valid_date_restitution > date('Ymd'))) )
		redirection('?error=expired');
}

if ( $profile_type == 'user' ) {
	// Vérification de l'intégrité du menu.
	check_integrite_menu($user_profile, $database_connection);
	/*
	 * - 28/06/2007 christophe : lorsque l'utilisateur se logue en profile user, on initialise la variable de session
	 * $_SESSION['network_element_repferences'] à ce qui est sauvegardé en base dans la table users.
	 */
	$_SESSION["network_element_preferences"] = $row["network_element_preferences"];

    // 03/08/2010 OJT : Correction bz16951
    if( !isset( $_SESSION['TA']['network_element_preferences'] ) ){
        $_SESSION['TA']['network_element_preferences'] = $row["network_element_preferences"];
    }
}


// Récupère la chaine des menus correspondant au profil qui seront affcihés
$query = "SELECT profile_name, profile_to_menu FROM profile WHERE (id_profile='$user_profile')";
$result = $db_connect->getAll( $query );

$row = $result[0];

$chaine_menu_profile = $row["profile_to_menu"];

$nom_profile         = $row["profile_name"];

//convertit la chaine en tableau
$menu_profile = explode("-", $chaine_menu_profile);

// 21/11/2011 BBX
// Correction de messages "Notices" vu pendant les corrections
if(!isset($user_zone)) $user_zone = null;

// défini les données de base (zone, day, week, etc...)
$year = date("Y");
// définit la wxek courante correspondant au format de la base de donnée AAAAWW
$numero_jour_annee = date("z");
$numero_week_courante = ceil($numero_jour_annee / 7);
$week   = $year . $numero_week_courante;
$day    = date("Ymd");
$today  = $day; //charge la date du jour au format AAAAMMJJ qui peut-être utilisé partout
$zone   = $user_zone;
$period = 20;
// charge les variables de session
session_register('id_user');
session_register('profile_type');
session_register('user_login');
session_register('username');
// 20/02/2012 NSE DE Astellia Portal Lot2 : suppression de uesr_prenom
session_register('user_agregation_network');
session_register('user_agregation_value');
session_register('user_profile');
session_register('nom_profile');
session_register('menu_profile');
session_register('year');
session_register('week');
session_register('day');
session_register('today');
session_register('period');
session_register('repertoire_physique_niveau0');
session_register('niveau0');

// 02/02/2009 - Modif. benoit : mise en commentaires de l'appel à la table 'sys_user_parameter' qui n'est plus utilisée

/*$query="DELETE FROM sys_user_parameter WHERE id_user='$id_user'";
$db_connect->execute($query);*/

// 10/01/2008 - Modif. benoit : on supprime les enregistrements du panier de la session précédente
//25/08/2009 - Modif MPR ; Correction du bug 11214 : On vide le caddy uniquement sur la déconnexion du user
// $sql = "DELETE FROM sys_panier_mgt WHERE id_user='$id_user'";
// $db_connect->execute($sql);

$homepage_content = 0;

// >>>>>>>>>>
// 17:07 18/05/2009 GHX
// Appel de la homepage dans le cas d'un profil user

if ( $profile_type  == 'user' )
{
	// 16/02/2010 + 23/02/2010 NSE bz 14134 : si l'utilisateur a sélectionné la homepage personnalisée
	if ( $idHomepageUser==-1 ){
		// 02/06/2009 BBX :
		// Si une homepage personnalisée est définie, on l'affiche
		if(file_exists(REP_PHYSIQUE_NIVEAU_0.'homepage/index.php')) {
			header("Location: ".NIVEAU_0."homepage/index.php");
			exit;
		}
		if(file_exists(REP_PHYSIQUE_NIVEAU_0.'homepage/index.html')) {
			header("Location: ".NIVEAU_0."homepage/index.html");
			exit;
		}
	}

	// Récupère l'id du dashboard qui sert de homepage par défault
	$idPage = get_sys_global_parameters('id_homepage', null);
	$mode = get_sys_global_parameters('mode_homepage');

	require_once(MOD_SELECTEUR."php/selecteur.class.php");
	require_once(MOD_SELECTEUR."php/SelecteurDashboard.class.php");

	// On regarde si le user a défini lui même une homepage par défaut
	// 23/02/2010 NSE bz 14134 ajout condition sur homapage (pour homepage installée)
	if ( !empty($idHomepageUser) && $idHomepageUser>-1 )
	{
		$queryHomepageUser = "SELECT * FROM sys_definition_selecteur WHERE sds_id_selecteur = {$idHomepageUser}";
		$resultHomepageUser = $db_connect->getRow($queryHomepageUser);

		$idPage = $resultHomepageUser['sds_id_page'];
		$mode = ($resultHomepageUser['sds_mode'] == 'ot' ? 'overtime' : 'overnetwork');

		$selecteur = new SelecteurDashboard($idPage,$mode);
		$ta_list = $selecteur->getTaArray();

		// 11:12 20/08/2009 GHX
		// Correction du BZ 11075
		// Prise en compte du compute_mode pour savoir sur quel jour on doit se placer
		// 11:43 09/11/2009 GHX
		// Correction du BZ 12608
		// 17:39 27/07/2009 GHX
		// Correction du BZ 10427

		// 06/07/2011 MMT Bz 22891 unité de temps par defaut autre que day/hour n'affiche pas la correcte date
		// selectionne l'unité de temps hour/day/week/month
		$strtimeUnit = str_replace("_bh", "", $resultHomepageUser['sds_ta']);
		// date est unité de temps précedente
		$defTime = strtotime( '-1 '.$strtimeUnit );
		$selecteurDay = date("Ymd",$defTime);//date in YYYYMMDD format

		if($strtimeUnit == 'week'){
			$selecteurDisplayDate = GetweekFromAcurioDay2($selecteurDay);
		} else {
			$selecteurDisplayDate = substr(getTaValueToDisplayV2($resultHomepageUser['sds_ta'], $selecteurDay, "/"), 0, 10);
		}

		$selecteurInfo = array(
				'ta_level' => $resultHomepageUser['sds_ta'],
				'na_level' => $resultHomepageUser['sds_na'],
				'nel_selecteur' => $_SESSION["network_element_preferences"],
				'period'   => $resultHomepageUser['sds_period'],
				'top'      => $resultHomepageUser['sds_top'],
				// 15:59 05/08/2009 GHX
				// Correction du BZ 10892
				'date'     => $selecteurDisplayDate
			);

		// Si on est sur la TA hour on ajout l'heure
		if ( $resultHomepageUser['sds_ta'] == 'hour' )
		{
			$selecteurInfo['hour'] = Date::getHour( 1, 'H:00' ); // Optimisation via le Date::getHour
		}
		elseif ( $resultHomepageUser['sds_ta'] == 'week' ) // Si on est sur la TA week on change le format de la date
		{
			$selecteurInfo['date'] = str_replace('/','-',$selecteurInfo['date']);
		}
		// Si on a un troisieme axe
		if ( !empty($resultHomepageUser['sds_na_axe3']) )
		{
			$selecteurInfo['axe3'] = $resultHomepageUser['sds_na_axe3'];

			// 06/06/2011 MMT DE 3rd Axis remplace colonne sds_na_axe3_element par sds_na_axe3_list
			if ( !empty($resultHomepageUser['sds_na_axe3_list']) )
			{
				$selecteurInfo['axe3_2'] = $resultHomepageUser['sds_na_axe3_list'];
				// 03/08/2011 MMT bz 22755 application au bug 22896 pour selection 3eme axe chargement homepage
				$_SESSION['TA']['ne_axeN_preferences'] = $selecteurInfo['axe3_2'];
			}
		}
		// Si on a un sort by
		if ( $resultHomepageUser['sds_sort_by'] != 'none' )
		{
			$tmpSortBy = explode('@', $resultHomepageUser['sds_sort_by']);
			$queryIdGTMSortBy = "
				SELECT
					b.id_page
				FROM
					sys_pauto_config AS a,
					sys_pauto_config AS b
				WHERE
					a.id_page = '{$idPage}'
					AND a.id_elem = b.id_page
					AND b.class_object = '{$tmpSortBy[0]}'
					AND b.id_elem = '{$tmpSortBy[1]}'
					AND b.id_product = '{$tmpSortBy[2]}'
				";

			$tmpSortBy[0] = ($tmpSortBy[0] == 'counter' ? 'raw' : 'kpi');
			$tmpSortBy[3] = $db_connect->getOne($queryIdGTMSortBy);

			$selecteurInfo['sort_by'] = implode('@', $tmpSortBy);
			$selecteurInfo['order'] = $resultHomepageUser['sds_order'];
		}
		else
		{
			// 17:21 28/07/2009 GHX
			$selecteurInfo['sort_by'] = 'none';
			$selecteurInfo['order'] = $resultHomepageUser['sds_order'];
		}
		// Si on a un filtre
		if ( !empty($resultHomepageUser['sds_filter_id']) )
		{
			$tmpFilter = explode('@', $resultHomepageUser['sds_filter_id']);
			$queryIdGTMFilter = "
				SELECT
					b.id_page
				FROM
					sys_pauto_config AS a,
					sys_pauto_config AS b
				WHERE
					a.id_page = '{$idPage}'
					AND a.id_elem = b.id_page
					AND b.class_object = '{$tmpFilter[0]}'
					AND b.id_elem = '{$tmpFilter[1]}'
					AND b.id_product = '{$tmpFilter[2]}'
				";

			$tmpFilter[0] = ($tmpFilter[0] == 'counter' ? 'raw' : 'kpi');
			$tmpFilter[3] = $db_connect->getOne($queryIdGTMFilter);

			$selecteurInfo['filter_id'] = implode('@', $tmpFilter);
			$selecteurInfo['filter_operande'] = $resultHomepageUser['sds_filter_operande'];
			$selecteurInfo['filter_value'] = $resultHomepageUser['sds_filter_value'];
		}

		// Ajout de la configuration du sélecteur définie par l'utilisateur concernant le dashboard qu'il a choisit
		$selecteur->getSelecteurFromArray($selecteurInfo);
		// Sauvegarde des paramètres en session
		$selecteur->saveToSession();
	}

	if ( $idPage != null )
	{
		$homepage_content = 1;

		$idMenu = $db_connect->getOne("SELECT id_menu FROM menu_deroulant_intranet WHERE lien_menu ilike '%dashboard_display/index.php%' AND lien_menu ilike '%id_dash={$idPage}%' AND lien_menu ilike '%mode={$mode}%'");

		// 17:39 27/07/2009 GHX
		// Correction du BZ 10427
		if ( empty($idHomepageUser) )
		{
			$selecteur = new SelecteurDashboard($idPage,$mode);
			$selecteur_values = $selecteur->getValues();

			$na_axe1_abcisse = $selecteur_values['na_level'];
			if ( empty($na_axe1_abcisse) )
			{
				$na_axe1_abcisse = key($selecteur->getNALevels(1));
			}

            // 27/07/2010 : Correction bz16951
            if( !isset( $_SESSION['TA']['selecteur'] ) )
            {
                // 07/01/2010 NSE bz 13671 : on force la ta au jour précédent, que les données soient horaires ou journalières
                // le jour précédent
                $offsetDayHomepage = 1;
                $selecteurInfo = array(
                        'na_level' => $na_axe1_abcisse,
                        'nel_selecteur' => $_SESSION["network_element_preferences"],
                        'ta_level' => 'day',
                        'date'     => substr(getTaValueToDisplayV2('day', getDay($offsetDayHomepage), "/"), 0, 10)
                    );

                // Ajout de la configuration du sélecteur définie par l'utilisateur concernant le dashboard qu'il a choisit
                $selecteur->getSelecteurFromArray($selecteurInfo);
                // Sauvegarde des paramètres en session
                $selecteur->saveToSession();
            }
		}
		$file_a_charger_homepage_non_vide = "dashboard_display/index.php?id_dash={$idPage}&mode={$mode}&id_menu_encours={$idMenu}";
	}
}
// <<<<<<<<<

$module = get_sys_global_parameters('module');
if ( $module ) {
	$_SESSION['module'] = $module;
	session_register('module');
}

$ta_min_restitution = get_sys_global_parameters('ta_min_restitution');
if ( $ta_min_restitution ) {
	$_SESSION['ta_min_restitution'] = $ta_min_restitution;
	session_register('ta_min_restitution');
}

// 23/02/2012 NSE DE Astellia Portal Lot2 : ajout de customisateur
if ( $profile_type  == 'admin' || $profile_type  == 'customisateur' ) {
	$file_a_charger = 'intranet_homepage_admin.php';
}
else {
	// ************************************** //
	// ***** GESTION DES LIENS EXTERNES ***** //
	// ************************************** //
	// 09:28 27/08/2009 GHX
	// On passe une instance de DatabaseConnection au lieu de passé l'ancienne variable $database_connection
        // 31/01/2011 BBX
        // On remplace new DatabaseConnection() par Database::getConnection()
        // BZ 20450
	$link = new ExternalLink(Database::getConnection());
	if ( $link->loadParameters() ) {
		$link->saveCookie();

		//$idDash = $link->getIdDashboard();
		if ( $link->getTypeLink() == 'internal' ) {
			$homepage_content = 1;
			$file_a_charger_homepage_non_vide = 'reporting/intranet/php/affichage/dashboard_associe.php?';
			$file_a_charger_homepage_non_vide .= $link->getURLAssociatedDashboards();
			$id_page = $idDash;
		}
		else {
			$idDash = $link->getIdDashboard();
			if ( $idDash ) {
				$homepage_content = 1;
				// Récupère le fichier qui doit être chargé
				$query = "SELECT lien_menu FROM menu_deroulant_intranet WHERE id_page='".$idDash."' AND lien_menu <> '' LIMIT 1";
				$result = $db_connect->getAll($query);

				// __debug($query,"query");
				// __debug($result,"query");
				$row = $result[0];
				$file_a_charger_homepage_non_vide = substr($row["lien_menu"],1);
				$file_a_charger_homepage_non_vide = str_replace('mode=overnetwork', 'mode=overtime', $file_a_charger_homepage_non_vide);
				$id_page = $idDash;
			}
		}
		// Charge les paramètres nécessaires au sélecteur en SESSION
		if ( $id_page )
			$link->loadSelecteurInSession($id_page);
	}

	if ( $homepage_content == 0 )
		$file_a_charger = 'reporting/intranet/php/affichage/multi_object_homepage_vide.php';
	else
		$file_a_charger = $file_a_charger_homepage_non_vide;
}


$_SESSION['file_a_charger'] = $file_a_charger;
$_SESSION['connected'] = 1;



redirection($file_a_charger);
?>