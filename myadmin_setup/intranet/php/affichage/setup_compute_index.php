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
include_once($repertoire_physique_niveau0 . "php/environnement_donnees.php");
include_once($repertoire_physique_niveau0 . "php/environnement_nom_tables.php");
include_once($repertoire_physique_niveau0 . "php/menu_contextuel.php");
include_once($repertoire_physique_niveau0 . "intranet_top.php");
include_once($repertoire_physique_niveau0 . "php/edw_function.php");
?>

<html>
<head>
<title>Compute setup</title>
</head>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr valign="middle">
    <td class='head' align=center>
       <br><br><br><br><br>
    </td>
  </tr>

  <tr valign="middle">
    <td>
       <iframe name="update" width="100%" height=500
        frameborder="0" src="setup_compute.php" scrolling="auto"
        leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
       </iframe>
    </td>
  </tr>
</table>
</body>
</html>
