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

session_start();
include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
// include_once($repertoire_physique_niveau0 . "php/database_connection.php");

$product		= $_GET['product'];
$family		= $_GET['family'];
$id_object	= $_GET['object_id'];
$class_object	= $_GET['object_class'];

if ($class_object == "counter") $class_object = "raw";

// echo ">> product : $product >> famille : $family >> class object : $class_object >> id : $id_object ";
$url = "pageframe_range.php?product=$product&family=$family&id_element=$id_object&type=$class_object";
// echo "<a href='$url'>$url</a>";
header("location:$url");
exit;

?>
