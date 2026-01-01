<?php
/**
 * 
 * This page is a proxy design to forward ajax request to a distant server (it is not possible to send an ajax request to a distant server).
 * 
 * 22/12/2011 ACS BZ 25285 send ajax request to "ProxyRequest" instead of a distant server
 */

session_start();
include_once dirname(__FILE__)."/environnement_liens.php";

$productId = $_GET["productId"];
$url = $_GET["url"];

// for security reasons, we check that the request match a list of allowed urls
$isAddressAllowed = (strpos($url, "myadmin_setup/intranet/php/affichage/setup_connection_index.php") == 0);

if ($isAddressAllowed) {
	$productModel = new ProductModel($productId);
	echo file_get_contents($productModel->getCompleteUrl($url));
}
?>
