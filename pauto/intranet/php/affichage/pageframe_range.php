<?
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
*
*	maj 13/08/2007 - Jérémy : Ajout de l'inclusion du fichier "global_interface.css" pour les tooltips
*	16/02/2010 NSE bz 14235 : ajout de l'appel à prototype.js pour utilisation de Event dans popalt()
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
include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
// include_once($repertoire_physique_niveau0 . "php/database_connection.php");
include_once($repertoire_physique_niveau0 . "php/edw_function.php");
global $niveau0;

?>
<html>
<link rel="stylesheet" href="<?=$niveau0?>css/stylesheet.css" />
<link rel="stylesheet" href="<?=$niveau0?>css/pauto.css" />
<link rel="stylesheet" href="<?=$niveau0?>css/global_interface.css" />
<script type='text/javascript' src='<?=$niveau0?>js/prototype/prototype.js'></script>
<script type='text/javascript' src='<?=$niveau0?>js/gestion_fenetre.js'></script>
<script src="<?=$niveau0?>js/fenetres_volantes.js" ></script>

<!-- javascript local utilisé pour cette page -->
<!-- 22/09/2010 OJT : Correction bz14248, ajout du charset -->
<script language="JavaScript" src='<?=$niveau0?>js/data_range_builder.js' charset="iso-8859-1"></script>

<style type='text/css'>
	.hexfield {
		border:1px groove black;
		font-family: arial;
		font-size:4pt;
		width:32px;
		height:18px;
	}
</style>

<body>

<?php

	// analyse les variables envoyées
	$id_element = (isset($_GET["id_element"])) ? $_GET["id_element"] : 0;
	$type	= $_GET['type'];
	$family	= $_GET['family'];
	$product	= $_GET['product'];
	
	include("pauto_frame_range.class.php");
	$page = new pageframe($id_element,$type);

?>

</body>
</html>
