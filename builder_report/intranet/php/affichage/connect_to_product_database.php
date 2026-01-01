<?
/*
*	@cb41000@
*
*	Composant de base version cb_4.1.0.00
* 
*	- 25/11/2008 - SLC - gestion multi-produit
* 
*/
?>
<?php
/*
*	@cb4100@
*
*	20/11/2008 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.0
*/

if (!isset($product)) {
    $product = $_POST['product'];
    if ($product == "") {
        $product = $_GET['product'];
    }
}

// base sur laquelle toutes les requêtes concernant le produit vont être effectuée - 20/11/2008 - SLC
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::get Connection()
$db_prod = Database::getConnection($product);

// echo "<div style='border:2px solid red;padding:3px;'>id_product : $product</div>";

?>
