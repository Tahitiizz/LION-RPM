<?php
/**
*	Script permettant de générer les Exports de log T&A
*
*	@author	BBX - 13/11/2009
*	@version	CB 5.0.1.04
*	@since	CB 5.0.1.04
*
*
*/
// High time limit
set_time_limit(3600);

// I ncludes
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";
include_once REP_PHYSIQUE_NIVEAU_0.'class/SSHConnection.class.php';
include_once REP_PHYSIQUE_NIVEAU_0.'class/DownloadLog.class.php';

// Test URL uniquement
if(isset($_POST['url']))
{
	if(ereg('SSH', $_POST['url'])){
		echo "SSH_error";
		exit;
	}
	if(ereg('Warning', $_POST['url']))
		exit;
	$file = @basename($_POST['url']);
	if(@file_exists(REP_PHYSIQUE_NIVEAU_0.'upload/'.$file))
		echo 'OK';
	else
		echo 'KO';

	exit;
}

// Mise à jour du Cookie
setcookie('downloadlog', serialize($_POST));

// Traitement des dates
if(!isset($_POST['date_fin'])) $_POST['date_fin'] = $_POST['date_debut'];
$dateDebut = Date::getDateFromSelecteurFormat('day',$_POST['date_debut']);
$dateFin = Date::getDateFromSelecteurFormat('day',$_POST['date_fin']);

// test des dates
if($dateDebut > $dateFin)
	exit;

// Produit
$idProduit = isset($_POST['product']) ? $_POST['product'] : '';

// Suppression des anciens logs
DownloadLog::deleteOldLogs();

// Fonction qui créé une archive pour un produit donné
function createArchive($dateDebut,$dateFin,$idProduit)
{
	// Gestion de l'id produit
	$idProduit = ($idProduit == '') ? 1 : $idProduit;

	// Instanciation DownloadLog
	try
    {
        $DownloadLog = new DownloadLog( $dateDebut, $dateFin, $idProduit );
		$ProductModel = new ProductModel($idProduit); // Infos produit
		$ProductInfos = $ProductModel->getValues();
		$DownloadLog->setListAction($_POST);
		// Création de l'archive
		$archive = 'log_'.strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $ProductInfos['sdp_label'])).'_'.date('YmdHi').'.zip';
		$DownloadLog->createArchive(REP_PHYSIQUE_NIVEAU_0.'upload/'.$archive);
		return $archive;
	}
    catch( Exception $e )
    {
        // Aucun traitement
    }
}

// Si on doit générer sur tous les produits
if($idProduit == 'all')
{
	// Génération pour tous les produits
	$createdArchive = Array();
	foreach(ProductModel::getActiveProducts() as $product)
	{
		$idProd = $product['sdp_id'];
		// Création de l'archive pour le produit donné
		$createdArchive[] = createArchive($dateDebut,$dateFin,$idProd);
	}
	// Création de l'archive multiproduit
	$finalArchive = DownloadLog::createSuperArchive($createdArchive);
	if(file_exists(REP_PHYSIQUE_NIVEAU_0.'upload/'.$finalArchive))
		echo NIVEAU_0.'upload/'.$finalArchive;
}
else
{
	// Création de l'archive pour le produit donné
	$finalArchive = createArchive($dateDebut,$dateFin,$idProduit);
	if(file_exists(REP_PHYSIQUE_NIVEAU_0.'upload/'.$finalArchive))
		echo NIVEAU_0.'upload/'.$finalArchive;
}

?>