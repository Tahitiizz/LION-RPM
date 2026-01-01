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

if (isset($_SESSION['gis'])) {

	echo $_SESSION['gis'];

	session_unregister('gis');

}
else
{
	echo '<svg id="root" x="0" y="0" width="100%" height="100%">';
	echo '<image xlink:href="../gis_icons/loading_gis.png" x="25.625%" y="44.875%" width="48.75%" height="10.25%"/>';
	echo '</svg>';
}

?>
