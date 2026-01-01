<?php
/**
 * 
 *  CB 5.2
 * 
 * 23/02/2012 NSE DE Astellia Portal Lot2
 * 24/04/2012 NSE bz 26636 : utilisation de l'API Portail v 1.0.0.04 pour la gestion de Astellia Administrator
 * 12/09/2012 ACS DE Look & Feel update
 */
?><?php
/**
* @cb4100@
*
*	- Maj CCT1 13/11/08 : le chemin vers le fichier environnement_liens doit être en dur et on u'tilise toujours les constante pour REP_PHYSIQUE_NIVEAU_0
*	- maj 18/07/2008 slc -> suppression des cas $client_type=='acurio' - déprécié
*	- maj 18/07/2008 slc -> suppression de tous les droit_visible<=$client_id dans les requêtes SQL - déprécié
*	- maj 18/07/2008 slc -> suppression javascript GIS déprécié
*	- maj 22/07/2008 slc -> optimisation, refactoring majeure. La partie generation de la barre de menu va dans intranet_menu.phpn, le formulaire d'ajout de commentaires va dans intranet_top_graph_comment.php
*	- maj 27/11/2008 BBX : modification du header.  Celui-ci est déormais inclu et se trouve dans /php/header.php
*	- maj 13/01/2009 - MPR : Modification de la taille de la pop-up
*	24/04/2009 - SPS : ajout de la classe pngfix pour gerer la transparence des png sous ie < 7
*
*	30/06/2009 BBX : utilisation de la classe CSS productInHeader au lieu de product. BZ 10297
*
*	10/07/2009 GHX
*		- Correction du BZ10405 [REC][T&A Cb 5.0][Dashboard]: le titre de la page ne change pas lors du passage OT<-> ONE
*			-> modification de la fonction makeChemin()
*	23/02/2010 NSE
*		- remplacement de la fonction GetweekFromAcurioDay2 par leur équivalent de la classe Date
*
*	04/03/2010 BBX
*		- BZ 11686 : On ne donne plus la fonction "remove_loading" au onload du body. 
*		- BZ 11686 : Désormais, tout ce qui concerne le "loading page" est dans un script spécifique
*       03/09/2010 MPR
*               - BZ 17685 : Firefox n'interprète pas le curseur hand (remplacé par pointer)
 *      12/10/2010 NSE bz 18454, 18467 : gestion des cas d'affichage de l'icône du SA et du produit sélectionné
*/
?><?php
/**
* 
* @cb40012@
* 
* 	14/04/2008 - Copyright Astellia
* 
* 	Composant de base version cb_4.0.0.12
*
*	- maj 14/04/2008 benjamin : ajout de l'heure à côté de la date. BZ6220
 *
 */
?><?php
/**
* 
* @cb40000@
* 
* 	14/11/2007 - Copyright Acurio
* 
* 	Composant de base version cb_4.0.0.00
*
*	- maj 26/03/2008 christophe : modification de la fonction getUserDashboarList on liste tous les id_page sauf ceux des dashboards créés par d'autres utilisateurs.
*	- maj 20/02/2008 christophe : si le profil de l'utilisateur courant est user, on n'affiche pas les dahsboards des autres utilisateurs (seulement les dahsboard admin) > nb : la modif se trouve à 2 endroits.
*	- maj 20/02/2008 christophe : si le profil de l'utilisateur courant est user, on liste les id_page de ses dashboards.
*	- maj christophe 20/02/2008 : ajout de la fonction getUserDashboarList : liste les id_page de la table sys_pauto_page_name des dahsboards appartenant à l'utilisateur courant. 
*	- maj 17:19 28/01/2008 Gwénaël : modif sur la récupération du client_type
*	- maj 02/01/2008 Gwénaël : ajout d'une vérification pour savoir si l'utilisateur vient de T&A si non on le redirige sur la page de connexion
*	- maj 26/11/2007, benoit : intégration du bouton "GIS Supervision" dans le bandeau
 *
 */
?><?
/*
*	@cb30015@
*
*	06/11/2007 - Copyright Acurio
*
*	Composant de base version cb_3.0.0.15
*/
?><?
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
*
* 	- 08/01/2008 Gwénaël : sauvegarde de la page chargée en session pour pouvoir utiliser la touche F5 du navigateur
*	- 29/08/07 christophe :  la variable _GET id_menu_en_cours est par fois id_menu_encours,
*	on tient compte de ces 2 variables.
*	- 23/08/2007 christophe : suppression de l'appel à popalt
*	- maj 25/06/2007, christophe : id permettant de se possitonner en haut via un lien href.
*	- maj 22/06/2007, christophe : ajout de prototype.js et prototype_window.js dans les includes.
*	- maj 20/08/2007, benoit : ajout du div "loader_background" et du style associé pour empecher le roll-over lors   du loading (provoquait un bug si l'on survolait les boutons du selecteur lors du chargement)
*	- maj 20/08/2007, benoit : dans la fonction 'remove_loading()', masquage du div "loader_background" à la fin du   chargement de la page
*
*/
?><?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*/
?><?
/*
*	@cb21002@
*
*	23/02/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.0.02
*/
?><?
/*
*	@cb2000b_iu2000b@
*
*	20/07/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.0.0
*
*	Parser version iu_2.0.0.0
*/
?><?
/*
*	16/06/2006 - Copyright Acurio
*
*	Composant de base version cb_1.2.0.2p
*
*	Parser version gb_1.0.0b
*/
?><?php
/*
* Affichage du menu de l'application.
* @author
* @version V1 2005-05-17
        - maj 03 02 2006 christophe : delta pour le passage à la version 1170
        - maj 01 03 2006 christophe : afficahge d'une image d'accueil par défaut venant du répertoire images/default
        - maj 03 03 2006 christophe : débug fonction js pour l'ajout des commentaires (caractères spéciaux...)
        - maj 03 03 2006 christophe : débug fonction js pour l'ajout des commentaires (caractères spéciaux...)
        - mal cl 03 mars 2006 ligne 19 : evite les install pirate
        - maj DELTA christophe 25 04 2006. cf MODIF DELTA NOUVEAU(ajout)   MODIF DELTA(mise en commentaires des modifications)
	- maj 15 05 2006 ligne 493 : le message 'limited to 255...' restait affiché lors de l'ajout d'un commentaire.
	- maj 19 05 2006 ligne 17 : affichage d'un message d'erreur et redirection vers l'accueil de l'appli si la session est terminée.
	- maj 29 09 2006 chrisotphe : caddy devient cart
        - maj 27/02/2007  Gwénaël : affichage des deux premiers chiffres du numéro de version du produit dans le bandeau
*/

// Maj CCT1 13/11/08 : le chemin vers le fichier environnement_liens doit être en dur et on u'tilise toujours les constante pour REP_PHYSIQUE_NIVEAU_0
include_once('php/environnement_liens.php');
include_once(REP_PHYSIQUE_NIVEAU_0."check_user_session.php");
include_once(REP_PHYSIQUE_NIVEAU_0."php/track_accessed_page.php");
// 09/02/2012 NSE DE Astellia Portal Lot2
include_once(REP_PHYSIQUE_NIVEAU_0 . '/api/paa/PAAAuthenticationService.php');

// >>>>>>>>>
// modif 08/01/2008 Gwénaël
	// Permet d'utiliser la touche F5 du navigateur
$_SESSION['file_a_charger'] = substr($_SERVER['REQUEST_URI'], strlen(NIVEAU_0));
// <<<<<<<<

// 15/12/2010 BBX
// On test l'intégrité des produits Slave
// BZ 18510
$masterModel = new ProductModel(ProductModel::getIdMaster());
if(!$masterModel->isMaintenance())
{
    foreach(ProductModel::getActiveProducts() as $p)
    {
        if((ProductModel::getIdMaster() != $p['sdp_id']) && !ProductModel::isSlave($p['sdp_id']))
        {
            ProductModel::fastDesactivation($p['sdp_id']);
            ProductModel::deployProducts();
            $message = __T('A_SETUP_PRODUCT_DISABLED_NOT_A_SLAVE',$p['sdp_label']);
            sys_log_ast('Critical', 'Trending&Aggregation', 'Database', $message, 'support_1', '');
        }
    }
}

// 22/12/2010 BBX
// Reactivation automatique des Slave
// BZ 18510
foreach(ProductModel::getProductsToReactivate() as $p => $pLabel)
{
    // Fetching Product Information
    $productModel   = new ProductModel($p);
    $productInfos   = $productModel->getValues();
    $conString      = "host={$productInfos['sdp_ip_address']} port={$productInfos['sdp_db_port']} dbname={$productInfos['sdp_db_name']} user={$productInfos['sdp_db_login']} password={$productInfos['sdp_db_password']}";

    if(DataBaseConnection::testConnection($conString))
    {
        ProductModel::fastActivation($p);
		// 09/07/2013 MGO BZ 34678 : copie de la table sys_definition_product du master sur les autres produits lors d'une réactivation
		ProductModel::deployProducts();
        sys_log_ast('Critical', 'Trending&Aggregation', 'Database', __T('A_DATABASE_PRODUCT_AUTO_ENABLED_INFO',$pLabel), 'support_1', '');
    }
    else
    {
        ProductModel::updateLastDesactivationValue($p, time());
        ProductModel::deployProducts();
        sys_log_ast('Critical', 'Trending&Aggregation', 'Database', __T('A_DATABASE_PRODUCT_AUTO_ENABLED_FAILED',$pLabel), 'support_1', '');
    }
}
// Fin BZ 18510

// MODIF DELTA
// include_once($repertoire_physique_niveau0 . "php/database_connection.php");
/*
	- 29/08/07 christophe :  la variable _GET id_menu_en_cours est parfois id_menu_encours,
	on tient compte de ces 2 variables.
*/

// 21/11/2011 BBX : Correction de messages "Notices" vu pendant les corrections
// 25/01/2012 OJT : bz25651, fil d'ariane KO
if( isset($_GET["id_menu_en_cours"]))
{
	$id_menu_courrant = $_GET["id_menu_en_cours"];
    $_SESSION["id_menu_courrant"] = $id_menu_courrant;
	
}
else if( isset($_GET["id_menu_encours"]))
{
    $id_menu_courrant = $_GET["id_menu_encours"];
    $_SESSION["id_menu_courrant"] = $id_menu_courrant;
}
else if( isset( $_SESSION["id_menu_courrant"] ) )
{
    $id_menu_courrant = $_SESSION["id_menu_courrant"];
}


// 21/11/2011 BBX
// Correction de messages "Notices" vu pendant les corrections
if(!isset($tester_log)) $tester_log = false;
if (!$tester_log) {
    $verification_user = "SELECT id_user FROM users WHERE id_user LIKE '$id_user'";
    $execution_verification = pg_query($database_connection, $verification_user);
    $verification = pg_num_rows($execution_verification);
	
	if ($verification == 0) {
		session_unset();
		echo "<script type='text/javascript'>location.href=\"".NIVEAU_0."\";</script>";
		exit;
	}
}

// modif 17:18 28/01/2008 Gwénaël	- modif sur la récupération du client_type
$client_type = getClientType($_SESSION['id_user']);
if ($client_type=='client') {$client_id=1;}
if ($client_type=='customisateur') {$client_id=1;}


// On vérifie quel type de profil est connecté, si c'est un admin, alors on n'afficha pas le caddy.
$user_info = getUserInfo($_SESSION['id_user']);
$profile_type = $user_info['profile_type'];
if($profile_type=="admin"){
	$premier_menu_bandeau = "bouton_about_pos1.gif";
	$second_menu_bandeau = "home.gif";
	$afficher_premier_menu_bandeau = false;
} 
else {
	$premier_menu_bandeau = "icone_caddy.gif";
	$second_menu_bandeau = "bouton_about_pos1.gif";
	$troisieme_menu_bandeau = "home.gif";
	$afficher_premier_menu_bandeau = true;
}

// 21/11/2011 BBX
// Correction de messages "Notices" vu pendant les corrections
// pour certaines pages (notamment les pages appelées via Ajax) on a pas besoin d'afficher le bandeau
// ni le menu :
if(!isset($intranet_top_no_echo)) $intranet_top_no_echo = false;
if ($intranet_top_no_echo) return;

//
// Gestion de l'arborescence
//

// Affichage du chemin courrant du menu.
function makeChemin($id_menu = '', $mode = '') {
	global $database_connection;

	$id_menu = trim($id_menu);
	if (!$id_menu) return "Home";
	
	// C'est la chaîne qu'on retournera au final :
	$arborescence = '';
	
	// La requête qu'on va utiliser tout le temps dans cette fonction
	$query_get_menu = "SELECT id_menu_parent,libelle_menu FROM menu_deroulant_intranet WHERE id_menu='%s'";

	// La requête qu'on va utiliser tout le temps dans cette fonction
	// 14:59 10/07/2009 GHX
	// Correction du BZ 10405
	if ( $mode == '' )
	{
		$query_get_menu = "SELECT id_menu_parent,libelle_menu FROM menu_deroulant_intranet WHERE id_menu='%s'";
	}
	else
	{
		$query_get_menu = "					
				SELECT
					CASE WHEN id_page IS NOT NULL AND libelle_menu != '%2\$s' THEN '%1\$s' ELSE id_menu_parent END AS id_menu_parent, 
					CASE WHEN id_page IS NOT NULL AND libelle_menu != '%2\$s' THEN '%2\$s' ELSE libelle_menu END AS libelle_menu
				FROM menu_deroulant_intranet 
				WHERE (id_menu_parent = (SELECT id_menu_parent FROM menu_deroulant_intranet WHERE id_menu='%1\$s')
					AND libelle_menu like '%2\$s')
					OR (id_menu = (SELECT id_menu FROM menu_deroulant_intranet WHERE id_menu='%1\$s') AND id_page IS NULL)
				";
	}
	
	// On va chercher le menu courrant
	$result = pg_query($database_connection, sprintf($query_get_menu,$id_menu,$mode));
	$nb_menu = pg_num_rows($result);
	
	// Tant qu'on va trouver des menus on les ajoute à l'arborescence
	$MAX_NB_WHILE = 10;
	$compteur=0;
	while ($nb_menu > 0 && $compteur < $MAX_NB_WHILE) {
		$menu = pg_fetch_array($result, 0);
		if ($arborescence != '') $arborescence = ' > '.$arborescence;
		$arborescence = $menu["libelle_menu"].$arborescence;
		$result = pg_query($database_connection, sprintf("SELECT id_menu_parent,libelle_menu FROM menu_deroulant_intranet WHERE id_menu='%s'",$menu['id_menu_parent']));
		$nb_menu = pg_num_rows($result);
	}
	
	// On ajoute 'Home'
	if ($arborescence != '') $arborescence = ' > '.$arborescence;
	$arborescence = 'Home'.$arborescence;
	
	return $arborescence;
}

// 21/11/2011 BBX
// Correction de messages "Notices" vu pendant les corrections
if(!isset($id_menu_courrant)) $id_menu_courrant = null;

// 14:59 10/07/2009 GHX
// Correction du BZ10405
if ( isset($_GET['mode']) )
{
	switch ($_GET['mode'])
	{
		case 'overtime' : 
				$arborescence = makeChemin($id_menu_courrant, 'Over Time'); 
				break;
			
		case 'overnetwork' : 
				$arborescence = makeChemin($id_menu_courrant, 'Over Network Elements'); 
				break;
	}
}
else
{
	$arborescence = makeChemin($id_menu_courrant);
}


// SLC -> propositions graphiques NG

// On vérifie la taille de la chaine arborescence pr ne pas faire planter l'affichage.
if(strlen($arborescence) >=70) $arborescence = substr($arborescence,0,67)."...";


// Répertoire contenant l'image du module en cours.
$rep_img_module = NIVEAU_0."parser/".get_sys_global_parameters('module')."/images/";

// BZ 11686
// 04/03/2010 BBX : On ne donne plus la fonction "remove_loading" au onload du body. 
$onload = '';

// modif 27/11/2008 BBX : modification du header. Désormais, celui-ci est écrit dans le fichier "php/header.php"
// afin de pouvoir inclure un header même si on a pas besoin d'intranet top.
// HEADER
include_once(REP_PHYSIQUE_NIVEAU_0.'php/header.php');

// 16/02/2009 - Modif. benoit : si l'on spécifie via l'url que le bandeau doit être masqué, on définit les elements bandeau (#header) et menu (#menu_container) comme non visibles
// 12/05/2010 BBX
// On déplace les test sur l'affichage du header T&A ici, à sa place, après le header HTML. BZ 15451
if (isset($_GET['hide_header']) && ($_GET['hide_header'] == "1"))
{
    echo '<style type="text/css">
                #header {display:none}
                #menu_container{display:none}
         </style>';
}

/* LOADER */
if(!isset($_GET['no_loading']))
{
	// BZ 11686
	// 04/03/2010 BBX : Désormais, tout ce qui concerne le "loading page" est dans un script spécifique
	include(REP_PHYSIQUE_NIVEAU_0.'php/loading_page.php');
} 
?>

<!-- Ajout d'un commentaire sur un graph -->
<?php include('intranet_top_graph_comment.php'); ?>


<?php /* HEADER */

// we compute $url (the link on the left logo)
// 21/11/2011 BBX
// Correction de messages "Notices" vu pendant les corrections
$params = '';
if(isset($_SERVER['argv'][0])){
	$params = "?";
	for($i=0;$i < count($_SERVER['argv']);$i++){
		if($_SERVER['argv'][$i] != "") $params .= $_SERVER['argv'][$i];
	}
}
$url = $_SERVER['PHP_SELF'].$params;
$url = urlencode($url);

// we get the middle image
if ($afficher_premier_menu_bandeau) {
	$img_milieu = "milieu_2.gif";
} else {
	$img_milieu = "milieu_2_2.gif";
}

// we search the $img_product
$rep_img_module = NIVEAU_0."parser/$module/images/";
$img = "titre_module.gif";
if(!file_exists($repertoire_physique_niveau0.$rep_img_module.$img)){
	$rep_img_module = NIVEAU_0."images/default/";
}
$img_product = NIVEAU_0.$rep_img_module.$img;

// we compute the $gis_url
// 26/11/2007 - Modif. benoit : intégration du bouton "GIS Supervision" dans le bandeau
$gis_onoff = get_sys_global_parameters('gis');
if ($gis_onoff != '' && $gis_onoff == 1) {
	// Definition des parametres à fournir à la fonction 'ouvrir_fenetre()'
	
	// Url + chaine de parametres à passer au script php
	$gis_parametre = '|@||@||@|supervision';

	// 30/11/2007 - Modif; benoit : on appele pas le GIS directement mais on passe par un choix de famille
	$gis_url = NIVEAU_0."gis/supervision.php?gis_data=".urlencode($gis_parametre);

	// Taille de la pop-up
	$sql1 = "SELECT gis_side FROM sys_gis_config_global";
	$req1 = pg_query($database_connection, $sql1);
	$row1 = pg_fetch_array($req1);

	($row1['gis_side'] != '') ? $side = $row1['gis_side'] : $side = 400;

	// Tooltip du bouton
	$tooltip_label = (strpos(__T('U_TOOLTIP_BANDEAU_GIS_SUPERVISION'), "Undefined") === false) ? __T('U_TOOLTIP_BANDEAU_GIS_SUPERVISION') : "GIS Supervision";
}

// echo '<pre>'; print_r($_SESSION); exit;

?>

<?php
	// Ajout YNE 28/04/10 : Ajout du module Source Availability
	include_once(REP_PHYSIQUE_NIVEAU_0."/reliability/class/SA_IHM.class.php");
	include_once(REP_PHYSIQUE_NIVEAU_0."/reliability/class/SA_Calculation.class.php");

	$information_product_selected = "";
	if($_SESSION['profile_type'] == 'user' && isset($_REQUEST['product'])){
		$information_product_selected = $_REQUEST['product'];
		$reliability = new SaIHM($information_product_selected);
	}
	else
	{
		$reliability = new SaIHM();
	}

        // 12/10/2010 NSE bz 18454, 18467 : gestion de l'affichage du SA
        // on affiche l'îcone dès lors qu'au moins un produit a le SA d'activé.
        // on affiche le SA
        //      pour le produit lié au Dash courant, sur lequel le SA est activé s'il y en a,
        //      sinon, pour l'un des produits liés au Dash courant si on en trouve,
        //      sinon sans produit spécifié.
		// 29/01/2013 GFS BZ#31476 - [SUP][T&A OMC Ericsson BSS][Zain Iraq][AVP#32904] Source Availability information display with client_admin
        if($reliability->isProductsExist()){
            $SA_activated = 1;            
            $productid = $reliability->productsToDisplaySa();

        }
        else
            $SA_activated = 0;
        
        
?>

<div id="header">
	<div class="left-side"></div>
	<div class="logo-opperator">
		<img src="<?=NIVEAU_0?>images/bandeau/logo_operateur.jpg"
			<?php
                        // maj 03/09/2010 MPR : Correction du bz 17685
                        //Le curseur hand n'est pas interprété par firefox remplacement de hand par pointer
                        if(($client_type=='customisateur') && (!$afficher_premier_menu_bandeau)) { ?>

                            onMouseOver="popalt('Click to change logo');style.cursor='pointer';"
                            onMouseOut="kill()"
                            onClick="ouvrir_fenetre('<?=NIVEAU_0?>logo_operateur_change.php?url=<?=$url?>','comment','yes','yes',500,350);return false;"
			<?php	} ?>
		>
	</div>
	<?php 
	/* 24/04/2009 - SPS : ajout de la classe pngfix pour gerer la transparence des png sous ie < 7*/
	// 30/06/2009 BBX : utilisation de la classe CSS productInHeader au lieu de product. BZ 10297
	// 12/09/2012 ACS DE Look & Feel update
	?>
	<div class="productInHeader">
		&nbsp;<br />
		<? if (CorporateModel::isCorporate()) { ?>
			<img src="<?=NIVEAU_0?>images/default/logo_corporate.png" class="pngfix"/><br />
		<? } else { ?>
			<img src="<?=NIVEAU_0?>images/default/logo.png" class="pngfix" style="margin-top:13px;" /><br />
		<? } ?>
	</div>

	<div class="right-side">
		<div class="logo-constructor">
			<!-- img align="right" src="<?=NIVEAU_0?>images/client/logo_constructeur.gif"/ -->
			<img align="right" src="<?=NIVEAU_0?>images/bandeau2008/logo_Astellia.png" class="pngfix" style="margin-top:17px;margin-right:5px;" />
		</div>
	</div>

	<div class="chemin">
		<?= $arborescence ?><br />
	</div>
		
	<div class="user">
		<img src="<?=NIVEAU_0?>images/bandeau2008/puce_bandeau.gif" /> <?= $_SESSION['nom_profile']." / ".$_SESSION['login'] ?><br />
	</div>

	<?php 
	// 23/09/2009 BBX : utilisation de div avec fond au lieu des images pour els icones du header. BZ 11666
	?><?php
        // 09/02/2012 NSE DE Astellia Portal Lot2 : ajout de l'icone de changement de profile
    function profileChange(){
        // supprimer le fichier de conf
        $PAAAuthentication = PAAAuthenticationService::getAuthenticationService(REP_PHYSIQUE_NIVEAU_0 . '/api/paa/conf/PAA.inc');

        // récupère les droits de l'utilisateur déclarés sur le Portail
        $rights = $PAAAuthentication->getUserRights($_SESSION['login'],APPLI_GUID_HEXA.'.'.APPLI_GUID_NAME,false);
        // 27/04/2012 NSE bz 27026 : traduction du Guid en Id
        $rights = array_map('ProfileModel::getIdFromPaaGuid',$rights);
        // 24/04/2012 NSE bz 26636 : utilisation de l'API Portail v 1.0.0.04
        // s'il a le droit "Astellia Admin"
        if($PAAAuthentication->isSupportUser($_SESSION['login'])){
            // on ajoute l'id_profile
            $rights[] = ProfileModel::getAstelliaAdminProfile();
        }
        //echo 'pour le login : '.$_SESSION['login'].'('.APPLI_GUID_HEXA.'.'.APPLI_GUID_NAME.')'.', droits : ';print_r( $rights);
        // si l'utilisateur a plus d'un profile
        if(count($rights)>1){
            // s'il en a deux, on passe sur l'autre
            if(count($rights) == 2){
                // 27/04/2012 NSE bz 27026 : correction déplacée dans ProfileModel::getIdFromPaaGuid
                if($rights[0]==$_SESSION['user_profil'] ){
                    $right = $rights[1];
                }
                else{
                    $right = $rights[0];
                }
                $onClick = "window.location='".NIVEAU_0."acces_intranet.php?fProfile=$right';";
            }
            // s'il en a plus, on lui donne le choix
            else {
                $onClick = "ouvrir_fenetre('".NIVEAU_0."/myadmin_user/intranet/php/affichage/profile_switch.php','profile_switch','no','yes',400,300);return false;";
            }
            ?>
        <div style="float:left;width:17px;height:16px;background-image:url(<?=NIVEAU_0?>images/bandeau2008/bouton_suivant.gif)"></div>
        <div 
                style="float:left;width:12px;height:16px;background-image:url(<?=NIVEAU_0?>images/bandeau2008/icone_switch_profile.png);cursor:pointer"
                onmouseover="popalt('Switch to <?=(count($rights) == 2?ProfileModel::getNameFromRightGuid($right):'another profile')?>')"
                onmouseout="kill()"
                onclick="<?=$onClick?>">
        </div>
            <?php
        }
    }
        ?>
	<div class="toolbar">	
		<div id="bandeau_toolbar_image_debut" style="float:left;width:20px;height:17px;background-image:url(<?=NIVEAU_0?>images/bandeau2008/toolbar_debut.png)"></div>	
			<?php
			if($afficher_premier_menu_bandeau) 
			{
			?>
				<div 
					style="float:left;width:20px;height:17px;background-image:url(<?=NIVEAU_0?>images/bandeau2008/<?=$premier_menu_bandeau?>);cursor:pointer"
					onmouseover="popalt('Click to open the Cart')"
					onmouseout="kill()"
					onclick="ouvrir_fenetre('<?=NIVEAU_0?>/reporting/intranet/php/affichage/multi_object_caddy.php?id_user=<?=$id_user?>','comment','yes','yes',700,300);return false;">
				</div>
				<?php 
				if ($gis_url) 
				{
				?>	
					<div style="float:left;width:17px;height:17px;background-image:url(<?=NIVEAU_0?>images/bandeau2008/bouton_suivant.gif)"></div>
					<div 
						style="float:left;width:18px;height:17px;background-image:url(<?=NIVEAU_0?>images/bandeau2008/icone_gis.gif);cursor:pointer"
						onmouseover="popalt('<?=$tooltip_label?>')"
						onmouseout="kill()"
						onclick="ouvrir_fenetre('<?=$gis_url?>','MapView','yes','yes',<?=($side*2)?>,<?=$side?>);return false;">
					</div>
				<?php				
				} 
				// maj 13/01/2009 - MPR : Modification de la taille de la pop-up
				?>
				<div style="float:left;width:17px;height:17px;background-image:url(<?=NIVEAU_0?>images/bandeau2008/bouton_suivant.gif)"></div>
				<div 
					style="float:left;width:20px;height:17px;background-image:url(<?=NIVEAU_0?>images/bandeau2008/<?=$second_menu_bandeau?>);cursor:pointer"
					onmouseover="popalt('About')"
					onmouseout="kill()"
					onclick="ouvrir_fenetre('<?=NIVEAU_0?>about.php?profile_type=<?=$profile_type?>','comment','yes','yes',550,345);return false;">
				</div>
				<?php
				if($SA_activated){
				?>
				<div style="float:left;width:17px;height:17px;background-image:url(<?=NIVEAU_0?>images/bandeau2008/bouton_suivant.gif)"></div>
				<div 
					style="float:left;width:20px;height:17px;background-image:url(<?=NIVEAU_0?>images/bandeau2008/icone_sa.png);cursor:pointer"
					onmouseover="popalt('Source Availability')"
					onmouseout="kill()"
					onclick="ouvrir_fenetre('<?=NIVEAU_0?>/reliability/index.php?first_load=1&productid=<?=$productid?>&product_selected=<?=$information_product_selected?>','source_availability','no','yes',600,400);return false;">
				</div>
				<?php
				}
				?>
				<div style="float:left;width:17px;height:17px;background-image:url(<?=NIVEAU_0?>images/bandeau2008/bouton_suivant.gif)"></div>
				<div
					style="float:left;width:18px;height:17px;background-image:url(<?=NIVEAU_0?>images/bandeau2008/<?=$troisieme_menu_bandeau?>);cursor:pointer"
					onmouseover="popalt('Home')"
					onmouseout="kill()"
					onclick="window.location='<?=NIVEAU_0?>acces_intranet.php?home=1'">
				</div>
                                <?=profileChange()?>
				<div style="float:left;width:18px;height:17px;background-image:url(<?=NIVEAU_0?>images/bandeau2008/bouton_fin.gif)"></div>
			<?php
			} 
			else 
			{			
			// maj 13/01/2009 - MPR : Modification de la taille de la pop-up
			?>				
				<div 
					style="float:left;width:20px;height:17px;background-image:url(<?=NIVEAU_0?>images/bandeau2008/<?=$premier_menu_bandeau?>);cursor:pointer"
					onmouseover="popalt('About')"
					onmouseout="kill()"
					onclick="ouvrir_fenetre('<?=NIVEAU_0?>about.php?profile_type=<?=$profile_type?>','comment','yes','yes',550,345);return false;">
				</div>				
				<?php
				if($SA_activated){
				?>
				<div style="float:left;width:17px;height:17px;background-image:url(<?=NIVEAU_0?>images/bandeau2008/bouton_suivant.gif)"></div>
				<div 
					style="float:left;width:20px;height:17px;background-image:url(<?=NIVEAU_0?>images/bandeau2008/icone_sa.png);cursor:pointer"
					onmouseover="popalt('Source Availability')"
					onmouseout="kill()"
					onclick="ouvrir_fenetre('<?=NIVEAU_0?>/reliability/index.php?first_load=1&productid=<?=$productid?>&product_selected=<?=$information_product_selected?>','source_availability','no','yes',600,400);return false;">
				</div>
				<?php
				}
				?>
				<div style="float:left;width:17px;height:17px;background-image:url(<?=NIVEAU_0?>images/bandeau2008/bouton_suivant.gif)"></div>
				<div
					style="float:left;width:18px;height:17px;background-image:url(<?=NIVEAU_0?>images/bandeau2008/<?=$second_menu_bandeau?>);cursor:pointer"
					onmouseover="popalt('Home')"
					onmouseout="kill()"
					onclick="window.location='<?=NIVEAU_0?>acces_intranet.php?home=1';">
				</div>
                                 <?=profileChange()?>
				<div style="float:left;width:18px;height:17px;background-image:url(<?=NIVEAU_0?>images/bandeau2008/bouton_fin.gif)"></div>				
			<?php
			} 
			?>
	</div>
	
	<div class="date"><?php // 23/02/2010 NSE remplacement GetweekFromAcurioDay2($day) par Date::convertAstelliaWeekToAstelliaWeekWithSeparator de Date::getWeekFromDay?>
		<?= date("d-m-Y H:i"); ?> / <?=Date::convertAstelliaWeekToAstelliaWeekWithSeparator(Date::getWeekFromDay(date("Ymd"),get_sys_global_parameters('week_starts_on_monday',1)),'W','-')?>
	</div>
</div>

<!-- MENU -->
<div id="menu_container">
	<?php include("intranet_menu.php"); ?>
</div>
<script type='text/javascript' src='<?=NIVEAU_0?>js/menu.js'></script>
<?php /* 24/04/2009 - SPS : ajout d'une fonction pour gerer la transparence des png pour ie < 7 */?>
<script type='text/javascript' src='<?=NIVEAU_0?>js/pngfix.js'></script>

<pre style="text-align: left">
<?php
//print_r($_SESSION);
?>
</pre>
