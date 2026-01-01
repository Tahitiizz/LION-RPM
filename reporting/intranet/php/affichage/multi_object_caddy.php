<?
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
*	
*	- maj 23/08/2007, Jérémy: Ajout de lien vers le CSS global_interface.css pour le tooltip
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
/*
*	@cb2001_iu2030_111006@
*
*	11/10/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.0.1
*
*	Parser version iu_2.0.3.0
*/
?>
<?
/*
* Affichage du caddy.
* @author christophe chaput
* @version V1 2005-05-16
*/
session_start();
include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
include_once($repertoire_physique_niveau0 . "php/database_connection.php");

//var_dump($str);
$tab = $str;
	/*
		- maj 11 10 2006 christophe : caddy > cart
	*/
?>
<html>
<head>
<link rel="stylesheet" type="text/css" href="<?=$niveau0?>css/caddy.css"/>
<link rel="stylesheet" type="text/css" href="<?=$niveau0?>css/prototype_window/default.css" />
<link rel="stylesheet" type="text/css" href="<?=$niveau0?>css/prototype_window/alphacube.css" />
<link rel="stylesheet" type="text/css" href="<?=$niveau0?>css/global_interface.css" >
<title>Cart content</title>
<script type="text/javascript" src="<?=$niveau0?>js/prototype/prototype.js"> </script>
<script type="text/javascript" src="<?=$niveau0?>js/prototype/window.js"> </script>
<script type="text/javascript" src="<?=$niveau0?>js/prototype/scriptaculous.js"> </script>
<script type="text/javascript" src="<?=$niveau0?>js/toggle_functions.js"></script>
<script type="text/javascript" src="<?=$niveau0?>js/caddy_management.js"></script>
<script type="text/javascript" src="<?=$niveau0?>js/fenetres_volantes.js"></script>
<script type="text/javascript" src="<?=$niveau0?>js/gestion_fenetre.js"></script>
</head>
<body>
<?
include_once($repertoire_physique_niveau0 . "class/multi_object_caddy.class.php");
$id_user = $_GET["id_user"];
$zoom_all = (isset($_GET["zoom_all"])) ? 1 : 0;
$caddy=new Multi_Object_Caddy($id_user,$tab,$zoom_all);
$caddy->caddy();
?>
</body>
</html>
