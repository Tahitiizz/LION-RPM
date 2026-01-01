<?php
/**
*	Ce fichier permet à l'utilisateur de gérer les couleurs des éléments réseaux
*/
/**
* @cb4100@
*
*	12/10/2009 - Copyright Astellia
*
*/
?>
<?php
session_start();
include_once('../../../../php/environnement_liens.php');
include_once(REP_PHYSIQUE_NIVEAU_0 . 'php/edw_function_family.php');
include_once(REP_PHYSIQUE_NIVEAU_0 . 'class/select_family.class.php');


// Récupération de l'identifiant du produit
$id_prod = (isset($_GET['product'])) ? $_GET['product'] : '';

// Utilisation de la classe DatabaseConnection pour les requêtes SQL
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::get Connection()
$database = Database::getConnection($id_prod);


	include_once( REP_PHYSIQUE_NIVEAU_0 . "intranet_top.php");
    include_once( REP_PHYSIQUE_NIVEAU_0 . "php/menu_contextuel.php");
?>

<link rel="stylesheet" type="text/css" media="all" href="<?=NIVEAU_0?>css/global_interface.css" />

<style type="text/css">
	<!--
/* pour le color picker */
.colorPickerBtn {
	height:20px;
	width:20px;
	border:1px #696969 solid;
}
table.colorPicker {
	position: absolute;
	background-color: #FFFFFF;
	border: solid 1px #000000;
}
table.colorPicker td{
	width: 15px;
	height: 15px;
	border: solid 1px #000000;
	/* 08/04/2009 - modif SPS : ajout de la propriete min-width (pbl affichage ie8) */
	min-width:15px;
}
-->
</style>

<script src="<?=NIVEAU_0?>js/color_picker.js"></script>
<div id="color_picker_container" style="position:absolute;display:none;z-index:2000;"></div>

<?
	$axe3 = false;
	$label = 'Network Element Color Management';

	if(!isset($_GET['product']))
	{
		$select_family = new select_family( $_SERVER['PHP_SELF'], $_SERVER['argv'][0], $label, $axe3, '', 2);
		exit;
	}
?>

<div align="center">
	<img src="<?=$niveau0?>images/titres/ne_color_titre.gif"/>
	
	<div valign="middle"> 
	    <?php include("admintool_setup_color_detail.php"); ?>
	</div>
</div>

</body>
</html>