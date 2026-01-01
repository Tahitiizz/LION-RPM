<?php
/*
* Export Excel a partir du graphique
* 
* 02/04/2009 - modif SPS : ajout du try/catch pour gerer l'exception (qd pas de donnees)
*
* @author SPS
* @date 17/03/2009
* @version CB 4.1.0.0
* @since CB 4.1.0.0
*/

// Inclusion des librairies et variables d'environnement
include_once dirname(__FILE__)."/../../php/environnement_liens.php";
include_once dirname(__FILE__)."/../class/XmlExcel.class.php";

//recupere l'id du graph
$id_graph = $_GET['id_graph'];

//on recupere le nom du xml
$xml_file = REP_PHYSIQUE_NIVEAU_0.'png_file/'.$id_graph.'.xml';
	
	/* 02/04/2009 - modif SPS : ajout du try/catch pour gerer l'exception (qd pas de donnees) */
try
{
    $xmlExcel = new XmlExcel(); // Appel de la classe qui va generer le fichier Excel
    $xmlExcel->setXmlFile($xml_file); // On definit le nom du xml
    $xmlExcel->getSingleData(); // On recupere les donnees du xml
    $file = $xmlExcel->save(); // On genere le fichier excel

}
catch( Exception $e )
{
		//si on a aucune donnee
		echo "<div style=\"margin:10px;padding:5px;font-family:Arial;font-size:10pt;font-weight:bold;color:red;border:2px solid red;background-color:#F8DED1;\">";
		//on affiche pas le message de l'exception, ms un message enregistre en base
		echo __T('U_E_EXPORT_FILE_NOT_GENERATED');
		echo "</div>";
		exit;
}

// Si on passe le try, on a des données, on peut continuer
	
$filename     = basename( $file ); // nom du fichier
$filesize     = filesize( $file ); // taille du fichier
$filemd5      = md5_file( $file ); // md5 du fichier
	
// 15/02/2011 OJT : bz16524 (évolution du 17658), gestion du 'Content-Disposition' pour Firefox
if ( stripos( $_SERVER['HTTP_USER_AGENT'], 'firefox' ) !== FALSE )
{
    $contentDispo = 'inline';
}
	
// On envoie le fichier au navigateur

// Gestion du cache
header('Pragma: public');
header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
header('Cache-Control: must-revalidate, pre-check=0, post-check=0, max-age=0');

// Informations sur le contenu à envoyer
header('Content-Tranfer-Encoding: none');
header('Content-Length: '.$filesize);
header('Content-MD5: '.base64_encode($filemd5));
header('Content-Type: application/x-msexcel; name="'.$filename.'"');
// 14/09/2010 BBX bz 17658 : On passe en mode inline
header('Content-Disposition: inline; filename="'.$filename.'"');

// Informations sur la réponse HTTP elle-même
header('Date: '.gmdate('D, d M Y H:i:s', time()).' GMT');
header('Expires: '.gmdate('D, d M Y H:i:s', time()+1).' GMT');
header('Last-Modified: '.gmdate('D, d M Y H:i:s', time()).' GMT');

readfile($file);

//suppression du fichier genere
unlink($file);

?>