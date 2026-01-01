<?php 
/*
* upload d'un contexte 
* @author SPS
* @date 31/03/2009
* @version CB 4.1.0.0
* @since CB 4.1.0.0
*/
include_once dirname(__FILE__)."/../../php/environnement_liens.php";

/**
* upload d'un fichier de contexte
* @return array message d'erreur (success, error)
*/
function uploadContext() {	
	//repertoire d'upload des contextes
	$content_dir = REP_PHYSIQUE_NIVEAU_0."upload/context/";
	
	//si le repertoire n'existe pas, on le cree
	if (!file_exists($content_dir)) {
		mkdir($content_dir,0777);
	}
	
	$file_name = $_FILES['fichier']['name'];
	$tmp_file = $_FILES['fichier']['tmp_name'];

	//on recupere l'extension du fichier
	$extension = substr($file_name, strrpos($file_name, '.') + 1);
	
	// Vérification sur le fichier uploadé par l'utilsateur
	$file_size  = $_FILES['fichier']['size'];
	$file_type  = $_FILES['fichier']['type'];
	$file_error = $_FILES['fichier']['error'];
	$msgError = null;
    
    /*
    * Le fichier est trop volumineux
    *   - 1 : excède le poids autorisé par la directive upload_max_filesize de php.ini 
    *   - 2 : excède le poids autorisé par le champ MAX_FILE_SIZE s'il a été donné 
    */

    if ( $file_error == 1 ||  $file_error == 2 )
    {	
		$msgError = __T('A_UPLOAD_TOPOLOGY_FILE_IS_TOO_BIG');
    }
    elseif ( $file_error == 3 ) // Le fichier n'a été uploadé que partiellement 
    {	
		$msgError = __T('A_E_UPLOAD_TOPOLOGY_FILE_PARTIAL');
    }
    elseif ( $file_error == 4 ) // Aucun fichier n'a été uploadé 
    {	
		$msgError = __T('A_E_UPLOAD_TOPOLOGY_FILE_MISSING');
    }
    elseif ( $file_size == 0 ) // Fichier vide
    {
		$msgError = __T('A_E_UPLOAD_TOPOLOGY_FILE_IS_EMPTY');
	}
    // 2013/12/06 NSE bz 27915 : valide également si type = force-download pour compatibilité Firefox
    elseif ( $file_type != "application/octet-stream" && $file_type != "application/force-download" ) // Fichier de type incorrect
    {
		$msgError = __T('A_E_UPLOAD_TOPOLOGY_FILE_TYPE').': '.$file_type;
    }
	elseif ( $extension != "bz2" ) //si l'extension n'est pas "bz2"
	{
		$msgError = __T('A_E_UPLOAD_FILE_EXTENSION_INCORRECT');
	}
	
	//si pas de message d'erreur
	if ( $msgError == null ) {
		// on copie le fichier dans le dossier de destination
		if( !move_uploaded_file($tmp_file, $content_dir.$file_name) )
		{	$msgError = __T('A_E_UPLOAD_FILE_NOT_COPIED');
		}
		else {
			$date=date("Y-m-d H:i:s");
			
                        // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::get Connection()
			$db = Database::getConnection();
			//on supprime les enregistrements avec le meme nom de fichier
			$query_delete = "DELETE FROM sys_definition_context_management WHERE sdcm_file_name = '$file_name'";
			$db->executeQuery($query_delete);
			
			//on enregistre ensuite le nom du fichier et la nouvelle date de l'upload du contexte
			$query_insert="INSERT INTO sys_definition_context_management (sdcm_file_name,sdcm_date) VALUES ('$file_name','$date')";
			$db->executeQuery($query_insert);
			
			$msgSuccess = __T('A_UPLOAD_SUCCESS',$file_name);
		}
	}
	$msg['error'] = $msgError;
	$msg['success'] = $msgSuccess;
	//on retourne le message (d'erreur ou de succès)
	return $msg;
	exit;
}
	
	
?>