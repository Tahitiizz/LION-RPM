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

	if ($_GET['action'] != "show") return;

	session_start();

?>
<HTML>
<HEAD>
	<LINK REL="stylesheet" HREF="../css/gis_styles.css" TYPE="text/css">
	<SCRIPT LANGUAGE="javascript" SRC="gestion_fenetre.js" TYPE="text/javascript"></SCRIPT>
</HEAD>

<BODY topmargin="0" leftmargin="0">

<table border="0" width="100%">
<tr>
<td colspan="2">
	<IFRAME id="layers_content" NAME="layers_content" SRC="layers_content.php" WIDTH="265" HEIGHT="150" frameborder="0" scrolling="auto"></IFRAME>
</td>
</tr>
<?php

	/*include '../gis_class/gis_display_general.php';

	$gis = unserialize($_SESSION['gis_object']);

	if ($gis->data_type != "alarm") {*/

?>
		<tr>
		<td align="center"><input type="button" value="Add layers" class="bouton" onClick="ouvrir_fenetre('add_layers.php', 'add', 'auto', 'true', 300, 200)"></td>
		<td align="center"><input type="button" value="Remove layers" class="bouton" onClick="ouvrir_fenetre('remove_layers.php', 'remove', 'auto', 'true', 300, 200)"></td>
		</tr>
<?php

	//}

?>
</table>
</BODY>
</HTML>
