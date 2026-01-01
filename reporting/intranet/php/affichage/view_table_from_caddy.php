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
*	@cb1300b_iu2000b_070706@
*
*	12/07/2006 - Copyright Acurio
*
*	Composant de base version cb_1.3.0.0b
*
*	Parser version iu_2.0.0.0b
*/
?>
<?
	// permet d'afficher un tableau du caddy
	session_start();
	include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
	include_once($repertoire_physique_niveau0 . "php/database_connection.php");
	global $database_connection;

	/*
		- maj 21 09 2006 christophe : ajout de styles css pour un affichage + clair.
	*/
?>
<html>
<style>
#alarm{
	background-color : #fff;
	padding:5px;
	border : 2px #929292 solid;
}
#alarm tr{
	color: #000;
	font : 	normal 9pt Verdana, Arial, sans-serif;
	text-align: left;
}
.entete{
	color: #fff;
	background-color : #929292;
	font : 	normal 9pt Verdana, Arial, sans-serif;
	text-align: left;
}
</style>
<link rel="stylesheet" href="<?=$niveau0?>css/global_interface.css" type="text/css">
	<body>
	<div id="alarm">
	<?
		if(isset($_GET["type"])){
			$query = " select object_id from sys_panier_mgt where oid = ".$_GET["object_id"];
			$result=pg_query($database_connection,$query);
			$result_array = pg_fetch_array($result, 0);
			$val = $result_array["object_id"];
			//$val = urldecode($val);
			echo(urldecode($val));
		} else {
			echo $str[$_GET["object_id"]];
		}
	?>
	</div>
	</body>
</html>
