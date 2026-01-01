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
/*
*	@cb20100_iu2030@
*
*	24/10/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.1.00
*
*	Parser version iu_2.0.3.0
*/
?>
<?
session_start();
include_once($repertoire_physique_niveau0."php/environnement_liens.php");
include_once($repertoire_physique_niveau0."php/database_connection.php");
include_once($repertoire_physique_niveau0."php/php2js.php");
$lien_css=$path_skin."easyopt.css";
?>
<script>
function ontop()
	{
	self.focus();
	}
ontop();
</script>
<html>
<head>
<script>
function close_window()
         {
	 self.close();
         }
</script>
<title>Builder Report Error</title>
<link rel="stylesheet" href="<?=$lien_css?>" type="text/css">
<link rel="stylesheet" href="<?=$niveau0?>css/global_interface.css" type="text/css">
</head>
<body background="<?=$niveau0?>images/fond_grille.gif"    style="margin-left: 0px; margin-right: 0px" marginheight="0" marginwidth="0" >
<form height=100% name="formulaire" method="post" onsubmit="close_window()";>
	<table border="0"  height=100% width=100% cellspacing="0" cellpadding="1">
		<tr height=100%>
			<td width=100%>
				<table width=100% height=100% border="0" cellpadding="1" cellspacing="1" >
					<?
					$ERROR=explode("|",$ERROR);
					echo "<ul>";
					foreach($ERROR as $error)
					echo "<tr width=100%  height=100%><td width=100%><li> <font class='texteGris'>".ucfirst($error)."</li></td></tr>";
					echo "<tr><td><br></td></tr>";
 					echo "</ul>";
					?>
					<tr>
						<td  align="center"   width=100%>
							<input type="submit" class="bouton" value="Close window" align="center">
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</form>
</body>
</html>
