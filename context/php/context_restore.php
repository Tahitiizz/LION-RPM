<?php
/*
*	Restauration d'un contexte
*
*	@version CB 4.1.0.00
*	@author SPS
*	@date 31/03/2009
*
*/ 
include_once dirname(__FILE__)."/../../php/environnement_liens.php";
include_once(REP_PHYSIQUE_NIVEAU_0."context/class/Context.class.php");

//repertoire ou sont stockes les contextes
$upload_dir = REP_PHYSIQUE_NIVEAU_0.'upload/context/';

if (isset($_GET['filename'])) {
	//nom du fichier
	$filename = $_GET['filename'];
	//chemin du fichier
	$filepath = $upload_dir.$filename;

	//si le fichier existe, on peut le restorer
	if (file_exists($filepath)) {
		$context = new Context();
		$context->restore($upload_dir,$filename);
		//envoi du message de reussite
		echo "SUCCESS".__T('A_CONTEXT_RESTORED');
	}

}
?>