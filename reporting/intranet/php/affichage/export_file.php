<?php
/*
*	Ce script envoie le fichier a l'utilisateur (utilise pour l'export du caddy)
*
*	@version CB 4.1.0.00
*	@author SPS
*	@date 07/05/2009
*
*/

include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";

$file = base64_decode($_GET['file']);
//nom du fichier
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
// 14/09/2010 BBX
// On passe en mode inline
// BZ 17658
header('Content-Disposition: inline; filename="'.$filename.'"');

// Informations sur la réponse HTTP elle-même
header('Date: '.gmdate('D, d M Y H:i:s', time()).' GMT');
header('Expires: '.gmdate('D, d M Y H:i:s', time()+1).' GMT');
header('Last-Modified: '.gmdate('D, d M Y H:i:s', time()).' GMT');

readfile($file);

//suppression du fichier
//unlink($file);
exit;

?>
