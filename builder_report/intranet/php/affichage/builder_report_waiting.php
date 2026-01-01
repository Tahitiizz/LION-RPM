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
include_once($repertoire_physique_niveau0."php/php2js.php");
include_once($repertoire_physique_niveau0."php/database_connection.php");
$lien_css=$path_skin."easyopt.css";
?>
<html>
<head>
<title>Waiting...</title>
<link rel="stylesheet" href="<?=$niveau0?>css/loader.css" type="text/css">
<script type='text/javascript' src='<?=$niveau0?>js/loader.js'></script>
</head>
<body>
<div id="loader_container">
	<div id="loader">
		<div align="center">Work in progress ...</div>

		<div id="loader_bg"><div id="progress"> </div></div>
	</div>
</div>
<div id='interface1' style="display:inline; visibility:visible;">
</body>
</html>
