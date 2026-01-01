<?php
/*
	11/12/2008 GHX
		- création du fichier
	10/02/2009 GHX
		- ajout du controle d'accès à la page
		- affichage d'un titre cf fonction displayTitle()
		-  appel de la fonction displayLinkFileForDownload() pour afficher le lien vers le fichier de mapping créé par le download
	19/11/2009 GHX
		- Prise en compte que les produits Mixed KPI ne peuvent pas mappés
*/
?>
<?php
/**
 * Script permet de gérer le mapping de la topologie.
 *
 * @author : GHX
 * @version CB4.1.0.00
 * @since CB4.1.0.00
 */
session_start();
include_once(dirname( __FILE__ ).'/../../../../php/environnement_liens.php');
include_once(dirname( __FILE__ ).'/mapping_functions.php');

if ( isset($_GET['action']) && $_GET['action'] == 'download' )
{
	displayLinkFileForDownload($_GET['file']);
}

include_once(REP_PHYSIQUE_NIVEAU_0.'intranet_top.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'class/SSHConnection.class.php');

// 10/02/2009 GHX
// Ajout du contrôle sur l'accès à la page
$userModel = new UserModel($_SESSION['id_user']);
$query = "SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu ILIKE 'Mapping'";
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
$database = Database::getConnection();
$result = $database->getRow($query);
$idMenu = ($result['id_menu'] == '') ? 0 : $result['id_menu'];
if ( !$userModel->userAuthorized($idMenu) )
{
	echo '<div class="errorMsg">'.__T('A_USER_NOT_ALLOWED_TO_ACCESS').'</div>';
	exit;
}

// Contient les informations sur le master produit
$productMasterProduct = null;
// Contient les informations sur le produit master topologie
$productMasterTopo = null;
// Tableau contenant tous les autres produits
$productOthers = array();
// Récupère les informations sur les produits
$productsInformations = getProductInformations();
foreach ( $productsInformations as $oneProduct )
{
	// >>>>>>>>>>
	// 10:09 19/11/2009 GHX
	// Un produit Mixed KPI ne doit pas faire partit de la liste des produits qui peuvent être mappé
	if ( MixedKpiModel::isMixedKpi($oneProduct['sdp_id']) )
		continue;
	// <<<<<<<<<<
	
	if ( $oneProduct['sdp_master_topo'] == 1 )
	{
		$productMasterTopo = $oneProduct;
	}
	else
	{
		$productOthers[$oneProduct['sdp_id']] = $oneProduct;
	}
	
	if ( $oneProduct['sdp_master'] == 1 )
	{
		$productMasterProduct = $oneProduct;
	}
}

displayTitle();
$error = false;
if ( count($productOthers) == 0 || (count($productOthers) == 1 &&  $productMasterTopo == null) )
{
	// Affiche d'un message comme quoi il n'y a qu'un seul produit donc pas de mapping
	displayNoOhtersProducts();
}
elseif ( $productMasterTopo == null )
{
	// Si aucun produit n'est le master topo, on affiche un message d'erreur
	displayErrorNoMasterTopo();
}
elseif ( !isset($_POST['product']) && count( $_POST ) > 0 )
{
    // maj 13/09/2010 - MPR : Si aucune topologie sur le produit Slave
    displayErrorNoTopologySlave();
    $error = true;
}

// Si la topologie du master est vide on affiche un message
if ( isEmptyTopology($productMasterTopo['sdp_id']) == true )
{
	displayMasterTopoIsEmpty($productMasterTopo['sdp_label']);
}

if ( isset($_POST['action']) )
{
    // maj 13/09/2010 - MPR : On vérifie qu'il n'y a pas de message d'erreur
	if( checkSelectProductNoTopoEmpty() && !$error )
	{
		actionMapping($_POST['action'], $productMasterProduct, $productMasterTopo, $productOthers[$_POST['product']]);
	}
}

// Affiche le formulaire pour upload le fichier de mapping
displayForm($productMasterTopo, $productOthers);
?>