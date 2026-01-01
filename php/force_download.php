<?php
/*
 Ce script permet de forcer le telechargement d'un fichier

 maj 15/04/2009 - MPR : Correction du bug  BZ 8679 [SUP][v4.0][7840][SFR IdF]:  Impossible d'accéder au serveur web T&A en https
					  On avait un problème en https pour l'envoi de fichier PDF

	24/03/2010 NSE bz 14457, 14458 : enregistrer un data export distant (filsize inopérant)
 * 21/02/2011 NSE DE Query Builder :
 *      suppression du fichier après téléchargement
 *      ou après fermeture de la fenêtre sans téléchargement : mode onlyDelete
*/

$filepath = $_GET['filepath'];

// 21/02/2011 NSE DE Query Builder : suppression du fichier si on ferme la fenêtre sans l'avoir téléchargé
// si on ne veut pas uniquement supprimer le fichier (cas de la fermeture sans télécahrgement)
if(!isset($_GET['onlyDelete']) || $_GET['onlyDelete']!=1){

  switch(strrchr(basename($filepath), ".")) {
	case ".gz": $type = "application/x-gzip"; break;
	case ".tgz": $type = "application/x-gzip"; break;
	case ".zip": $type = "application/zip"; break;
	case ".pdf": $type = "application/pdf"; break;
	case ".png": $type = "image/png"; break;
	case ".gif": $type = "image/gif"; break;
	case ".jpg": $type = "image/jpeg"; break;
	case ".kmz": $type = "application/vnd.google-earth.kmz";	break; // maj  26/05/2008 - maxime : On ajoute l'export des fichiers kmz
	case ".kml": $type = "application/vnd.google-earth.kml+xml";	break; // maj  26/05/2008 - maxime : On ajoute l'export des fichiers kml
	//application/vnd.google-earth.kml+xml .kml 
	// .kmz
	case ".txt": $type = "text/plain"; break;
	case ".htm": $type = "text/html"; break;
	case ".html": $type = "text/html"; break;
	default: $type = "application/octet-stream"; break;
  }

  // Correction du bug  BZ 8679 [SUP][v4.0][7840][SFR IdF]:  Impossible d'accéder au serveur web T&A en https
  // On avait un problème en https pour l'envoi de fichier PDF
  // Modification des headers envoyés au navigateur pour bien lui préciser que c'est un fichier Excel
  $filename = basename($filepath);
  // 24/03/2010 NSE bz 14457 14458 : erreur si fichier distant (http)
  $filesize = @filesize($filepath);
  $filemd5 = md5_file($filepath);
  //
  // Gestion du cache
  //
  header('Pragma: public');
  header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
  header('Cache-Control: must-revalidate, pre-check=0, post-check=0, max-age=0');
  //
  // Informations sur le contenu à envoyer
  //
  header('Content-Tranfer-Encoding: '.$type."\n");
  // 24/03/2010 NSE bz 14457 14458 : erreur si fichier distant (http)
  if(isset($filesize)&&$filesize)
	header('Content-Length: '.$filesize);
  header('Content-MD5: '.base64_encode($filemd5));

  header('Content-Type: application/force-download; name="'.$filename.'"');
  header('Content-Type: application/download; name="'.$filename.'"');
  header('Content-Disposition: attachement; filename="'.$filename.'"');
  //
  // Informations sur la réponse HTTP elle-même
  //
  header('Date: '.gmdate('D, d M Y H:i:s', time()).' GMT');
  header('Expires: '.gmdate('D, d M Y H:i:s', time()+1).' GMT');
  header('Last-Modified: '.gmdate('D, d M Y H:i:s', time()).' GMT');

  // header("Content-disposition: attachment; filename=".basename($filepath));
  // header("Content-Type: application/force-download");
  // header("Content-Transfer-Encoding: $type\n"); // Surtout ne pas enlever le \n
  // header("Content-Length: ".filesize($filepath));
  // header("Pragma: no-cache");
  // header("Cache-Control: must-revalidate, post-check=0, pre-check=0, public");
  // header("Expires: 0");
  readfile($filepath);

}// fin du if "on n'est pas en mode onlyDelete"

// 21/02/2011 NSE DE Query Builder : suppression du fichier après téléchargement
if(isset($_GET['delete']) && $_GET['delete']==1 && is_file($filepath))
    unlink($filepath);
?>