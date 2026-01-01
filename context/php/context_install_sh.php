<?php
/*
	24/06/2009 GHX
		- Ajout de retour à la ligne dans les échos
		- Ajout de tous les droits sur le fichier de log sinon warning si on monte un contexte via l'IHM
		- Ajout de tous les droits sur le contexte, sinon impossible de reuploader un contexte avec le même nom, il n'est pas écrasé
	12/08/09 CCT1 : traduction de tous les textes affichés en français en anglais. (correction BZ 10573)
	18/09/2009 GHX
		- Correction du BZ 11565 [T&A GSM 5.0][CB][installation contexte] : Le fichier de log doit être dans le répertoire SQL	
	21/12/2009 GHX
		- Correction du BZ 13527 [REC][T&A IU 5.0][Migration v4-v5] Mauvais positionnement des menus et absence des sous-menus du contexte
	24/03/2010 NSE bz 14815 : on ne doit pas prendre en compte le produit mixed_kpi pour les contextes
 *  03/02/2011 NSE bz 19738 : application des nouveaux paramètres pour le Task Scheduler
 *  17/01/2013 GFS - BZ#31387 : [QAL][T&A Gateway][Remove Product] : Product not accesible after being removed from gateway
*/
?>
<?php 
/*
* installation d'un contexte en ligne de commande
*
* Usage : context_install_sh.php contextName [archive]
*	contextName : chemin complet vers le contexte
*	archive : true ou false si doit archiver le contexte (default true)
*
* @author GHX
* @version CB 4.1.0.0
* @since CB 4.1.0.0
*/
include_once dirname(__FILE__)."/../../php/environnement_liens.php";
//classes necessaires pour l'installation du contexte
include_once REP_PHYSIQUE_NIVEAU_0.'context/class/Context.class.php';
include_once REP_PHYSIQUE_NIVEAU_0.'context/class/ContextElement.class.php';
include_once REP_PHYSIQUE_NIVEAU_0.'context/class/ContextMigration.class.php';
include_once REP_PHYSIQUE_NIVEAU_0.'context/class/ContextMount.class.php';
include_once REP_PHYSIQUE_NIVEAU_0.'context/class/ContextActivation.class.php';
include_once REP_PHYSIQUE_NIVEAU_0.'context/class/ContextGenerate.class.php';
// 20/10/2010 BBX : ajout de la classe ArrayTools pour le DE des compteurs clients
include_once REP_PHYSIQUE_NIVEAU_0.'class/ArrayTools.class.php';

if ( $argc == 0 )
{
	echo "\nERROR : No context file passed in argument! Unable mount a context";
	echo "\nUsage : ".$argv[0]."  contextName [archive]";
	echo "\n\t- contextName : path to file context";
	echo "\n\t- archive : true or false if the context should be archived";
	echo "\n";
	exit;
}

$filename = $argv[1];

$archive = true;
if ( $argc == 2 )
{
	$archive = ($argv[2] == 'false' ?  false : true);
}

if ( file_exists($filename) )
{
	$pathinfo = pathinfo($filename);
	
	$dirname = $pathinfo['dirname'];
	if ( substr($dirname, -1) != '/' )
	{
		$dirname .= '/';
	}
	$basename = $pathinfo['basename'];

	//repertoire d'upload des contextes
	$upload_dir = REP_PHYSIQUE_NIVEAU_0.'upload/context/';

	// Création du répertoire contexte s'il n'existe pas
	if ( !is_dir($upload_dir) )
	{
		echo "\nCreation of context directory in upload\n";
		mkdir($upload_dir, 0777);
	}
	// Par précaution met tous les droits sur le répertoire contexte
	chmod($upload_dir, 0777);
	
	if ( $upload_dir != $dirname )
	{
		echo "\nCopy context in directory {$upload_dir}\n";
		copy($filename, $upload_dir.$basename);
	}
	// 18:02 24/06/2009 GHX
	// Ajout de tous les droits sur le contexte, sinon impossible de reuploader un contexte avec le même nom, il n'est pas écrasé
	chmod($upload_dir.$basename, 0777);
	
	ob_start();

	$context = new Context();

	$ctxMig = new ContextMigration();
	$ctxMig->setDirectory($upload_dir);
	$ctxMig->setDebug(1);

	$add_product = false;
	
	//on recupere la liste des produits
	$infoAllProducts = getProductInformations();

	// 24/03/2010 NSE bz 14815 : on ne doit pas prendre en compte le produit mixed_kpi pour les contextes
	// Boucle sur tous les produits
	foreach ( $infoAllProducts as $clef => $product )
	{
		if($product['sdp_id']==ProductModel::getIdMixedKpi()){
			// on supprime le produit du tableau 
			unset($infoAllProducts[$clef]);
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
			$ctxMig->beforeMount($basename);
		}

		//on monte le contexte
		$ctxMount = new ContextMount();
		$ctxMount->setDebug(1);
		$ctxMount->setDirectory($upload_dir);
                // 12/03/2012 NSE bz 26292 : paramètre indiquant une installation par script
                $ctxMount->setContextMode(1);
		// 17:59 21/12/2009 GHX
		// Correction du BZ 13527 
		// Spécifie si c'est un contexte de migration
		if ($add_product) {
			$ctxMount->setMigration();
		}
		$ctxMount->extract($basename);
		
		$ctxMount->mount();
		
		if ($add_product) {
			$ctxMig->afterMount();
		}

                // 03/02/2011 NSE bz 19738 : application des nouveaux paramètres pour le Task Scheduler
                if (!$hasMountedContext && !$isMigratedVersion) {
                    // on est sur une installation de base
                    // on met à jour le Task Scheduler avec les nouvelles bonnes valeurs par défaut.
                    $db = Database::getConnection();
                    if(get_sys_global_parameters('compute_mode')=='daily'){
                        // compute mode = daily
                        echo "Mise à jour de sys_definition_master : Daily";
                        $db->executeQuery("UPDATE sys_definition_master SET utps=120, offset_time=17 where master_id='14'");// -- pour Collecte
                        $error = $db->getLastError();
                        $db->executeQuery("UPDATE sys_definition_master SET utps=60, offset_time=47 where master_id='11'");// -- pour Compute Launcher
                        $error .= $db->getLastError();
                        $db->executeQuery("UPDATE sys_definition_master SET utps=60, offset_time=19 where master_id='10'");// -- pour Retrieve";
                        $error .= $db->getLastError();
	}
                    else{
                        // compute mode = hourly
                        echo "Mise à jour de sys_definition_master : Hourly";
                        $db->executeQuery("UPDATE sys_definition_master SET utps=60, offset_time=25 where master_id='14'");// -- pour Collecte
                        $error = $db->getLastError();
                        $db->executeQuery("UPDATE sys_definition_master SET utps=5, offset_time=1 where master_id='12'");//-- pour Compute Launcher Hourly
                        $error .= $db->getLastError();
                        $db->executeQuery("UPDATE sys_definition_master SET utps=60, offset_time=47 where master_id='11'");// -- pour Compute Launcher
                        $error .= $db->getLastError();
                        $db->executeQuery("UPDATE sys_definition_master SET utps=5, offset_time=3 where master_id='10'");// -- pour Retrieve";
                        $error .= $db->getLastError();
                    }
                    if(!empty($error))
                        echo $error;
                    // Mets à jour la table sys_definition_master_ref à partir de la table sys_definition_master
                    $query_truncate = "TRUNCATE sys_definition_master_ref";
                    $db->executeQuery($query_truncate);
                    $query_insert = "INSERT INTO sys_definition_master_ref SELECT * FROM sys_definition_master";
                    $db->executeQuery($query_insert);
                }
        }
	catch ( Exception $e ) {
		$error = "\n\nERROR : ".$e->getMessage()."\n";
		
		/*
		* 15/04/2009 - modif SPS : si on a une erreur a l'installation, on restaure le contexte (si le fichier est present)
		*/
		//si le fichier de contexte existe, on peut le restorer
		if (file_exists($upload_dir.$basename)) {
			$context = new Context();
			$context->restore($upload_dir,$basename);
		}
		
		echo $error;
	}

	$str = ob_get_contents();
	ob_end_clean();

	//ecriture des logs
	$date=date("Ymd");
	$basename = basename($basename, '.tar.bz2');
	// 16:51 18/09/2009 GHX
	// Correction du BZ 11565
	// Le fichier de log doit être dans le répertoire SQL
	$log_name = REP_PHYSIQUE_NIVEAU_0.'SQL/'.$date."_".$basename.".log";
	// 18:02 24/06/2009 GHX
	// Ajout de tous les droits sur le fichier de log sinon warning si on monte un contexte via l'IHM
	file_put_contents($log_name,$str,FILE_APPEND);
	chmod($log_name, 0777);
	
	if (!$error) $msg = __T('A_CONTEXT_INSTALLED');
	// 08:23 24/06/2009 GHX
	// Suppression du doublons mot ERROR quand on affiche le message
	if ($error) $msg = $error;
	
	echo $msg."\n";
	
	// Archivage du contexte
	if ( $archive )
	{
		$date=date("Y-m-d H:i:s");
		
		// 03/02/2011 NSE : nouvelle gestion des connexions à la BD
        //$db = new DataBaseConnection();
        $db = DataBase::getConnection();
		//on supprime les enregistrements avec le meme nom de fichier
		$query_delete = "DELETE FROM sys_definition_context_management WHERE sdcm_file_name = '{$basename}.tar.bz2'";
		$db->executeQuery($query_delete);
		
		//on enregistre ensuite le nom du fichier et la nouvelle date de l'upload du contexte
		$query_insert="INSERT INTO sys_definition_context_management (sdcm_file_name,sdcm_date) VALUES ('{$basename}.tar.bz2','{$date}')";
		$db->executeQuery($query_insert);
	}
}
else
{
	// 17/01/2013 GFS - BZ#31387 : [QAL][T&A Gateway][Remove Product] : Product not accesible after being removed from gateway
	echo "\nERROR : The file context {$filename} doesn't exist\n";
}
?>