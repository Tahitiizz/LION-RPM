<?php
/**
*	Ce script est appelé via AJAX par dashboard_NA.js.
*	Ce script recherche les NA correspondant à une recherche dans le network element selecteur (nelsel)
*
*	@author	SLC - 26/09/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*
*/

	// connexion à la db
	include_once("../../../php/database_connection.php");
	global $database_connection;

	// on renvoie du XML
	header('Content-Type: text/xml;charset=utf-8');
	echo(utf8_encode("<?xml version='1.0' encoding='UTF-8' ?><options>"));
	
	// on compose la requête SQL
	if (isset($_GET['debut'])) {
	    $debut = utf8_decode($_GET['debut']);
	} else {
	    $debut = "";
	}
	
	$debut_q = '%'.strtolower($debut).'%';
	$na = $_GET['na'];

	$MAX_RETURN = 10;

	$q = "					
		SELECT DISTINCT eor_id,
			CASE WHEN eor_label IS NULL THEN '('||eor_id||')' ELSE eor_label END 
		FROM		edw_object_ref
		WHERE	eor_id IS NOT NULL
			AND	eor_obj_type = '$na'
			AND	((lower(eor_id) LIKE '$debut_q')	OR	(lower(eor_label) LIKE '$debut_q'))
		--	AND	eor_on_off=1
		ORDER BY	eor_label,eor_id
		LIMIT $MAX_RETURN
	";
	// echo(utf8_encode("<option>".$q.'|ss|'.$q."</option>"));
	
	// on effecture la requête
	$result = pg_query($database_connection, $q);
	$nombre_resultat = pg_num_rows($result);
	if ($nombre_resultat > 0)
	{
		for ($i = 0;$i < $nombre_resultat;$i++) {
			$row		= pg_fetch_array($result, $i);
			$value	= $row["eor_id"];
			$label	= $row["eor_label"];
			echo(utf8_encode("<option>".$label.'|ss|'.$value."</option>"));
		}
	}
	else
	{
		$html = __T('SELECTEUR_NO_RESULT');
		// $html = $q;
	}
	/*debug
	echo(utf8_encode("<option>".$debut.'|ss|'.$debut."</option>"));
	echo(utf8_encode("<option>".$na.'|ss|'.$na."</option>"));
	echo(utf8_encode("<option>".$na_label.'|ss|'.$na_label."</option>"));
	echo(utf8_encode("<option>".$q.'|ss|'.$q."</option>"));
	debug*/
	echo "</options>";

?>