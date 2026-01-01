<?php
/*
	04/12/2009 GHX
		- Correction du BZ 12160 [REC][T&A Cb 5.0][Context Management] : si on monte un contexte contenant trop de data export, leur chemin n'est pas mis à jour
			-> Utilisation de $_POST au lieu de $_GET
	24/03/2010 NSE bz 14815 : on ne doit pas prendre en compte le produit mixed_kpi pour les contextes
*/
?>
<?php
/**
 * Ce script liste tous les Data Exports dont le champ target_dir n'est pas celui qui pointe vers le répertoire upload/export_files de l'appli
 *
 *	04/09/2009 GHX
 *		- Création du fichier 
 *
 * @author GHX
 * @version CB 5.0.0.08
 * @since CB 5.0.0.08
 */
session_start();

include_once dirname(__FILE__)."/../../php/environnement_liens.php";

if ( isset($_POST['id']) ) // Si on doit moddifier le champ target_dir
{
	$list = unserialize(urldecode($_POST['id']));
	foreach ( $list as $export )
	{
		$de = new DataExportModel( $export['id'], $export['product'] );
		echo "\n".$export['id'] . ' -- '.'/home/'.$export['target_dir'].'/upload/export_files/';
		print_r($export);
		$de->setConfig('target_dir', '/home/'.$export['target_dir'].'/upload/export_files/');
	}
}
else // Liste des Data Exports incorrects
{
	$tab = array();
	$result = array();
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

	foreach ( $infoAllProducts as $product )
	{
		// Récupère la liste des Data Exports du produits
		$dataExports = DataExportModel::getExportList('', $product['sdp_id'] );
		if ( count($dataExports) )
		{
			foreach ( $dataExports as $export )
			{
				if ( !eregi('/home/'.$product['sdp_directory'].'/upload/export_files', $export['target_dir']) )
				{
					$tab[$export['target_dir']]['data'][] = array ( 
						'id' => $export['export_id'],
						'product' => $product['sdp_id'],
						'target_dir' => $product['sdp_directory']
					);
					$tab[$export['target_dir']]['name'][] = $export['export_name'];
				}
			}
		}
		
		if ( count($tab) > 0 )
		{	
			foreach ( $tab as $target_dir => $r )
			{
				$msg= __T('A_CONTEXT_MOUNT_DATA_EXPORT_1', $target_dir);
				$msg2= "\n\n".__T('A_CONTEXT_MOUNT_DATA_EXPORT_2',"/home/".$product['sdp_directory']."/upload/export_files/");
				$result[] = $msg."\n   - ".implode("\n   - ",$r['name']).$msg2.'@@@'.urlencode(serialize($r['data']));
			}
		}
	}
	
	if ( count($result) > 0 )
		echo implode('|||', $result);
}
?>