<?php
/*
	17/09/2009 GHX
		- Prise en compte que le contexte peut être sur un autre produit ou un autre serveur
*/
?>
<?php
/*
*	Ce script envoye le fichier a l'utilisateur
*
*	@version CB 4.1.0.00
*	@author SPS
*	@date 31/03/2009
*
*/

include_once dirname(__FILE__)."/../../php/environnement_liens.php";

$file = $_GET['file'];
$idProduct = $_GET['product'];

if ( !empty($idProduct) )
{
	// Récupère les informations sur tous les produits
	$productsInformations = getProductInformations();
	$product = $productsInformations[$idProduct];
	
	$file = '/home/'.$productsInformations[$idProduct]['sdp_directory'].'/upload/context/'.basename($file);
	
	// Si le fichier est sur un serveur distant on le récupère
	if ( $product['sdp_ip_address'] != get_adr_server() )
	{
		include_once(REP_PHYSIQUE_NIVEAU_0."class/SSHConnection.class.php");
		try
		{
			$ssh = new SSHConnection($product['sdp_id_address'], $product['sdp_ssh_user'], $product['sdp_ssh_password'], $product['sdp_ssh_port']);
			$ssh->getFile($file, REP_PHYSIQUE_NIVEAU_0.'upload/'.basename($file));
			$file = REP_PHYSIQUE_NIVEAU_0.'upload/'.basename($file);
		}
		catch ( Exception $e )
		{
		}
	}
}

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
header('Content-Disposition: attachement; filename="'.$filename.'"');

// Informations sur la réponse HTTP elle-même
header('Date: '.gmdate('D, d M Y H:i:s', time()).' GMT');
header('Expires: '.gmdate('D, d M Y H:i:s', time()+1).' GMT');
header('Last-Modified: '.gmdate('D, d M Y H:i:s', time()).' GMT');

readfile($file);

//suppression du fichier
//unlink($file);
exit;

?>
