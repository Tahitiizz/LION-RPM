<?php
/*
	07/07/2009 GHX
		- Correction du BZ10392 [REC][T&A Cb 5.0][Contexte]: l'installation d'un contexte contenant des kpi met tous les kpi en 'new_field' = 1
	20/08/2009 GHX
		- Ajout du session_start()
	25/08/2009 GHX
		- Appel de la fonction ContextMount::getMessage() quand on a monté un contexte avec succès
	29/09/2009 GHX
		- Correction du BZ 11733 [QUA][5.0.0.9] Réapplication de contexte fait disparaitre le menu Xpert
			-> include du fichier qui met à jour les menus Xpert
	08/10/2009 BBX
		- Gestion de l'activation d'un contexte sur un produit CorporateModel	
	21/12/2009 GHX
		- Correction du BZ 13527 [REC][T&A IU 5.0][Migration v4-v5] Mauvais positionnement des menus et absence des sous-menus du contexte
	19/03/2010 BBX : 
		- On ne change plus le module du produit. BZ 14392
		- On ne doit plus déployer le Corporate après montage. BZ 14392	
	24/03/2010 NSE bz 14815 : on ne doit pas prendre en compte le produit mixed_kpi pour les contextes
   21/01/2011 MMT DE Xpert 606 - utilisation de la class Xpert manager pour MAJ des menus et profiles XPert
   03/05/2011 MMT bz 22027 erreur lors du montage Contexte du a un include manquant
*/
?>
<?php 
/*
* installation d'un contexte 
*
* 15/04/2009 - modif SPS : si on a une erreur a l'installation, on restaure le contexte (si le fichier est present)
*
* @author SPS
* @date 31/03/2009
* @version CB 4.1.0.0
* @since CB 4.1.0.0
*/
// 11:00 20/08/2009 GHX
// Ajout du session_start
// 21/11/2011 BBX
// BZ 24764 : correction des messages "Notice" PHP
if(!isset ($_SESSION)) session_start();
include_once dirname(__FILE__)."/../../php/environnement_liens.php";
//classes necessaires pour l'installation du contexte
include_once REP_PHYSIQUE_NIVEAU_0.'context/class/Context.class.php';
include_once REP_PHYSIQUE_NIVEAU_0.'context/class/ContextElement.class.php';
include_once REP_PHYSIQUE_NIVEAU_0.'context/class/ContextMigration.class.php';
include_once REP_PHYSIQUE_NIVEAU_0.'context/class/ContextMount.class.php';
include_once REP_PHYSIQUE_NIVEAU_0.'context/class/ContextActivation.class.php';
include_once REP_PHYSIQUE_NIVEAU_0.'context/class/ContextGenerate.class.php';
include_once(REP_PHYSIQUE_NIVEAU_0."class/Xpert/XpertManager.class.php");
// 03/05/2011 MMT bz 22027 erreur lors du merge DE Xpert
include_once REP_PHYSIQUE_NIVEAU_0.'class/ArrayTools.class.php';

if(isset($_GET['filename'])) 
{	
	// 17/03/2010 BBX : on ne change plus le module du produit. BZ 14392
	// 17/03/2010 BBX : on ne doit plus déployer le Corporate après montage. BZ 14392	

	//repertoire d'upload des contextes
	$upload_dir = REP_PHYSIQUE_NIVEAU_0.'upload/context/';

	$filename = $_GET['filename'];
	
	ob_start();

	$context = new Context();

	$ctxMig = new ContextMigration();
	$ctxMig->setDirectory($upload_dir);
	$ctxMig->setDebug(1);

	$add_product = false;
	
	//on recupere la liste des produits
	$infoAllProducts = getProductInformations();
	
	// 24/03/2010 NSE bz 14815 : on ne doit pas prendre en compte le produit mixed_kpi pour les contextes
        // on supprime la Gateway du contexte
	// Boucle sur tous les produits
	foreach ( $infoAllProducts as $clef => $product )
	{
		if($product['sdp_id']==ProductModel::getIdMixedKpi()){
			// on supprime le produit du tableau 
			unset($infoAllProducts[$clef]);
		}
		elseif(ProductModel::isBlankProduct($product['sdp_id'])){
			// on supprime le produit du tableau 
			unset($productsInformations[$clef]);
		}
	}

	foreach($infoAllProducts as $p) {
		$hasMountedContext = $context->hasAlreadyMountContext($p['sdp_id']);
		$isMigratedVersion = $context->isMigratedVersion($p['sdp_id']);
		
		//si le contexte n'est pas monte et que la version a ete migree, on ajoute le produit
		if (!$hasMountedContext && $isMigratedVersion) {
			$add_product = true;
			$ctxMig->addProduct($p['sdp_id']);
		}
	}

	try {
		if ($add_product) {
			$ctxMig->beforeMount($filename);
		}

		//on monte le contexte
		$ctxMount = new ContextMount();
		$ctxMount->setDebug(1);
		$ctxMount->setDirectory($upload_dir);
		// 17:59 21/12/2009 GHX
		// Correction du BZ 13527 
		// Spécifie si c'est un contexte de migration
		if ($add_product) {
			$ctxMount->setMigration();
		}
		$ctxMount->extract($filename);
		
		$ctxMount->mount();
		
		if ($add_product) {
			$ctxMig->afterMount();
		}
		
		// 14:52 29/09/2009 GHX
		// Correction du BZ 11733 
		// 21/01/11 MMT DE Xpert 606 - utilisation de la class Xpert manager pour MAJ des menus et profiles XPert
		$Mger = new XpertManager();
		$Mger->checkAndUpdateMenus();
		$Mger->updateMenuProfiles();
	}
	catch ( Exception $e ) {
		$error = "\n\nERROR : ".$e->getMessage();
		
		/*
		* 15/04/2009 - modif SPS : si on a une erreur a l'installation, on restaure le contexte (si le fichier est present)
		*/
		//si le fichier de contexte existe, on peut le restorer
		if (file_exists($upload_dir.$filename)) {
			$context = new Context();
			$context->restore($upload_dir,$filename);
		}
		
		echo $error;
	}

	$str = ob_get_contents();
	ob_end_clean();

	//ecriture des logs
	$date=date("Ymd");
	$filename = basename($filename, '.tar.bz2');
	$log_name = $upload_dir.$date."_".$filename.".log";
	file_put_contents($log_name,$str,FILE_APPEND);
	
	// 14:01 07/07/2009 GHX
	// Correction du BZ10392 [REC][T&A Cb 5.0][Contexte]: l'installation d'un contexte contenant des kpi met tous les kpi en 'new_field' = 1
	// 14:41 25/08/2009 GHX
	// Ajout du message d'information sur le montage du contexte
	if (!$error) $msg = "SUCCESS".__T('A_CONTEXT_INSTALLED').$ctxMount->getMessage();
	if ($error) $msg = "ERROR".$error;

	echo $msg;
}

?>