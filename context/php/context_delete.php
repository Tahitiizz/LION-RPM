<?php 
/*
*	Suppression d'un contexte
*
*	@version CB 4.1.0.00
*	@author SPS
*	@date 31/03/2009
*
*/
include_once dirname(__FILE__)."/../../php/environnement_liens.php";
$upload_dir = REP_PHYSIQUE_NIVEAU_0.'upload/context/';

if (isset($_GET['filename'])) {
	//nom du fichier de contexte et du backup
	$filename = $_GET['filename'];
	$backupname = "backup_before_mount_context_".$filename;
	//chemins
	$filepath = $upload_dir.$filename;
	$backuppath = $upload_dir.$backupname;
	
	//si le fichier existe, on le supprime
	if (file_exists($filepath)) {
		//on supprime physiquement le fichier
		unlink($filepath);

		//on supprime le backup (s'il existe)
		if (file_exists($backuppath)) {
			unlink($backuppath);
		}
		
                // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::get Connection()
		$db = Database::getConnection();	
		
		//on supprime l'enregistrement lui correspondant dans la base
		$query_delete_sdcm = "
					DELETE 
					FROM
						sys_definition_context_management
					WHERE 
						sdcm_file_name = '$filename'
					";		
		$db->executeQuery($query_delete_sdcm);
		//succes
		echo "SUCCESS".__T('A_CONTEXT_FILE_DELETED',$filename);
	}
	else {
		//erreur
		echo "ERROR".__T('A_CONTEXT_FILE_NOT_FOUND',$filename);
	}
}
?>