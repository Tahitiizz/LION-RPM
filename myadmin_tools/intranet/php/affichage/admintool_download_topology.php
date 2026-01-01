<?php
/**
*	Ce fichier permet à l'utilisateur de faire un download topology ou un download Topology Third axis
*/
/**
* @cb4100@
*
*	21/11/2008 - Copyright Astellia
*
*	- maj 21/11/2008 - MPR : Modification des requetes de récupération des données liée au nouveau module de Topologie 
*		-> On ne prend plus en compte les familles
*		-> Récupération de l'identifiant du produit
*		-> Utilisation de la classe DatabaseConnection pour les requêtes SQL
*/
?>
<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*
*	- maj 13/04/2007 Gwénaël : modification pour la prise en compte du 3° axe
*	- 19/04/2007 christophe : si le paramètre axe3 est définit dans l'url, cela signifie que 
*	l'on doit seulement afficher les familles et na qui ont un 3ème axe.
*
*/
?>
<?
/*
*	@cb21000@
*
*	08/12/2006 - Copyright Acurio
*
*	Composant de base version cb_2.1.0.00
*/
?>
<?php
/*
-	MaJ	06/06/2006	sls : ajout du cas "no header" et suppression des ordonnées
-	MaJ	06/06/2006	sls : ajout du selecteur de famille
*/
session_start();
include_once("../../../../php/environnement_liens.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/edw_function_family.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/select_family.class.php");


// maj 21/11/2008 - MPR : Récupération de l'identifiant du produit
$id_prod = (isset($_GET['product']))  ? $_GET['product'] : "";
$family  = (isset($_GET['family']))  ? $_GET['family'] : "";

// maj 21/11/2008 - MPR : Utilisation de la classe DatabaseConnection pour les requêtes SQL
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::get Connection()
$database = Database::getConnection($id_prod);

if ($_GET['submit'] !== 'Export') {

	include_once( REP_PHYSIQUE_NIVEAU_0 . "intranet_top.php");
    include_once( REP_PHYSIQUE_NIVEAU_0 . "php/menu_contextuel.php");
    

?>

<link rel="stylesheet" type="text/css" media="all" href="<?=NIVEAU_0?>css/global_interface.css" />


<?
	$axe3 = ( isset($_GET['axe3']) ) ? true : false;
	$label = ( isset($_GET['axe3']) ) ? "Download Topology Third Axis": "Download Topology";

	
	if(!isset($_GET["family"])){
		$select_family = new select_family( $_SERVER['PHP_SELF'], $_SERVER['argv'][0], $label, $axe3,'',1);
		exit;
	}
?>

<div align="center">
	<img src="<?=$niveau0?>images/titres/download_topo_titre.gif"/>
	
	<div valign="middle"> 
	         <?php include("admintool_download_topology_detail.php"); ?>
	</div>
</div>

<?php } ?>
