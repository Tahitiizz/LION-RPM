<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*/
?>
<?
	// Permet de supprimer un range de la table sys_data_range_style.

	session_start();
	include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
	include_once($repertoire_physique_niveau0 . "php/edw_function.php");

	// analyse les variables envoyées
	$order		= $_GET['order'];
	$id_element	= $_GET['id_element'];
	$type		= $_GET['type'];
	$family		= $_GET['family'];
	$product		= $_GET['product'];
	
	// on se connecte a la base de données du produit en cours
        // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
	$db_prod		= Database::getConnection($product);

	// echo " Vous voulez supprimer : ". $id_element."  ".$type . " ordre ".$order;
	$query = "
		DELETE FROM  sys_data_range_style
		WHERE id_element = '$id_element'
			AND data_type = '$type'
			AND range_order = '$order' ";
	$db_prod->execute($query);

	header("location:pageframe_range.php?product=$product&family=$family&id_element=$id_element&type=$type");
	exit;

?>
