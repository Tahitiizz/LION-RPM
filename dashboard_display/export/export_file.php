<?php
/*
*	Ce script récupère le nom du fichier a envoyer a l'utilisateur
*
*	@version CB 4.1.0.00
*	@author SPS
*	@date 18/03/2009
*
*	21/07/2009 BBX : ajout de la gestion du type mime. BZ 10393
*
*	22/07/2009 GHX
*		-  Correction d'un problème en effet la commande file -i pour les fichiers Execl
*
*  31/08/2010 MMT - DE firefox bz 17306
*    -  Utilisation de ce fichier pour export du fichier .aacontrol par LinkToAA.class
*    -  definition d'un MIME type pour les fichiers .aacontrol
*
*  06/09/2010 MMT - DE firefox bz 17695
*    - changement du content dispo de 'attachment' à 'inline' pour liens vers AA afin d'executer
*    AA sans que l'utilisateur ai besoin de confirmer (si configuré)
*/

include_once dirname(__FILE__)."/../../php/environnement_liens.php";

$file = base64_decode($_GET['file']);
//nom du fichier
$filename = basename($file);
//taille du fichier
$filesize = filesize($file);
//md5 du fichier
$filemd5 = md5_file($file);
// 21/07/2009 BBX : Type mime
$cmd = 'file -i '.$file.' | cut -d":" -f2 | cut -d";" -f1 | sed "s/ //g"';
exec($cmd,$result);
$mimeType = $result[0];
$contentDispo = 'attachment';
// 06/09/2010 MMT - DE firefox bz 17695 changement du content dispo de 'attachment' à 'inline' pour liens AA
// 17/03/2011 OJT : bz16524, gestion du 'Content-Disposition' pour Firefox
if ( stripos( $_SERVER['HTTP_USER_AGENT'], 'firefox' ) !== FALSE )
{
    $contentDispo = 'inline';
}

// 22/07/2009 GHX : Correction d'un problème en effet la commande file -i pour
// un fichier execl retourne le type mine d'un document word, donc on force le
// type mine pour les fichiers Excel

// 17/03/2011 OJT : Pour les fichiers PDF (créé par soffice) on chnage le type mime
$pathinfo = pathinfo($file);
if ( $pathinfo['extension'] == 'xls' && $mimeType == 'application/msword' )
{
	$mimeType = 'application/msexcel';
}
else if( $pathinfo['extension'] == 'pdf' )
{
    $mimeType = 'application/soffice';
}
else
{
    // Pour les autres type, on laisse tel quel
}

// 31/08/2010 MMT - DE firefox bz 17306 definition d'un MIME type non default
// pour les fichiers .aacontrol afin que le navigateur puisse associer le programme configuré
if ( $pathinfo['extension'] == 'aacontrol')
{
	$mimeType = 'application/aacontrol';
       // 06/09/2010 MMT - DE firefox bz 17695 changement du content dispo de 'attachment' à 'inline' pour liens AA
       $contentDispo = 'inline';
}
// Gestion du cache
header('Pragma: public');
header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
header('Cache-Control: must-revalidate, pre-check=0, post-check=0, max-age=0');

// Informations sur le contenu à envoyer
header('Content-Tranfer-Encoding: none');
header('Content-Length: '.$filesize);
header('Content-MD5: '.base64_encode($filemd5));
header('Content-Type: '.$mimeType.'; name="'.$filename.'"');
// 06/09/2010 MMT - DE firefox bz 17695 changement du content dispo de 'attachment' à 'inline' pour liens AA
// ce qui permet à FireFox d'executer le fichier directement (si configuré dans options)
header('Content-Disposition: '.$contentDispo.'; filename="'.$filename.'"');

// Informations sur la réponse HTTP elle-même
header('Date: '.gmdate('D, d M Y H:i:s', time()).' GMT');
header('Expires: '.gmdate('D, d M Y H:i:s', time()+1).' GMT');
header('Last-Modified: '.gmdate('D, d M Y H:i:s', time()).' GMT');

readfile($file);

//suppression du fichier
//unlink($file);
exit;

?>
