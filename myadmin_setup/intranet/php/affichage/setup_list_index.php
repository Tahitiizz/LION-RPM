<?
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
*
*	- 11/07/2007 christophe : suppression de menu_contextuel
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
<?php
	session_start();

	include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
	include_once($repertoire_physique_niveau0 . "php/database_connection.php");
	include_once($repertoire_physique_niveau0 . "intranet_top.php");
	include_once($repertoire_physique_niveau0 . "php/deploy_and_compute_functions.php");
	include_once($repertoire_physique_niveau0 . "class/select_family.class.php");

	$family = $_GET["family"];

?>
<html>
<head>
<title>List Setup</title>
</head>
<link rel="stylesheet" type="text/css" media="all" href="<?=$niveau0?>css/global_interface.css" />
<?
	if(!isset($_GET["family"])){
		$select_family = new select_family($_SERVER['PHP_SELF'], $_SERVER['argv'][0], 'List');
		exit;
	}
?>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr valign="middle">
    <td>
		<?include("setup_list.php");?>
    </td>
  </tr>
</table>
</body>
</html>
