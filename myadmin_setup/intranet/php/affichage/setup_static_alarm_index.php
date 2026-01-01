<?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
*	maj 06/03/2008 - maxime : suppression des includes :
						- include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
						- include_once($repertoire_physique_niveau0 . "php/database_connection.php");
						- include_once($repertoire_physique_niveau0 . "php/environnement_donnees.php");
						- include_once($repertoire_physique_niveau0 . "php/environnement_nom_tables.php");
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
*	@cb2000b_iu2000b@
*
*	20/07/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.0.0
*
*	Parser version iu_2.0.0.0
*/
?>
<?
	session_start();

        // 03/12/2012 BBX
        // BZ 29463 : Using REP_PHYSIQUE_NIVEAU_0
	include_once(dirname(__FILE__).'/../../../../intranet_top.php');
	include_once(REP_PHYSIQUE_NIVEAU_0 . "/class/select_family.class.php");

	$family = $_GET["family"];

?>
<html>
</head>
<link rel="stylesheet" type="text/css" media="all" href="<?=$niveau0?>css/global_interface.css" />
<?
	if(!isset($_GET["family"])){

		$select_family = new select_family($_SERVER['PHP_SELF'], $_SERVER['argv'][0], 'Alarm');
		exit;
	}
?>
</head>
<html>
<body>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr align="center">
    <td>
		<?include("setup_static_alarm.php");?>
    </td>
  </tr>
</table>
</body>
</html>
