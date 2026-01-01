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
include_once($repertoire_physique_niveau0."php/environnement_liens.php");
include_once($repertoire_physique_niveau0."php/database_connection.php");

// gestion multi-produit - 21/11/2008 - SLC
include_once('../affichage/connect_to_product_database.php');

$query="DELETE FROM forum_queries where id_query='$id_query'";
$db_prod->execute($query);
// too bad this page does not exist anymore !!!
$file="../affichage/intra_forum_builder_report_equation_sql_define.php?product=".$product;
header("location:$file");
?>
