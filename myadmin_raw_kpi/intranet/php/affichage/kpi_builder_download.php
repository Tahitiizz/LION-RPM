<?php
session_start();
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";
include_once REP_PHYSIQUE_NIVEAU_0.'class/kpi/KpiDownload.class.php';


$file = REP_PHYSIQUE_NIVEAU_0.'upload/kpi_list_'.$_GET['family'].'.csv';
if ( KpiDownload::createFile($file, $_GET['idProduct'], $_GET['family']) )
{
	$filename = basename($file);
	//taille du fichier
	$filesize = filesize($file);
	//md5 du fichier
	$filemd5 = md5_file($file);

	// Gestion du cache
	header('Pragma: public');
	header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
	header('Cache-Control: must-revalidate, pre-check=0, post-check=0, max-age=0');

	// Informations sur le contenu à envoyer
	header('Content-Tranfer-Encoding: none');
	header('Content-Length: '.$filesize);
	header('Content-MD5: '.base64_encode($filemd5));
	header('Content-Type: application/force-download; name="'.$filename.'"');
	header('Content-Disposition: attachement; filename="'.$filename.'"');

	// Informations sur la réponse HTTP elle-même
	header('Date: '.gmdate('D, d M Y H:i:s', time()).' GMT');
	header('Expires: '.gmdate('D, d M Y H:i:s', time()+1).' GMT');
	header('Last-Modified: '.gmdate('D, d M Y H:i:s', time()).' GMT');

	readfile($file);

	//suppression du fichier
	unlink($file);
	exit;
}
else
{
	echo "Error during creating file";
}
?>