<?php

	session_start();

	$repertoire_physique_niveau0 = $_SESSION['repertoire_physique_niveau0'];

	echo "repertoire_physique_niveau0 : ".$repertoire_physique_niveau0."<br>";

	include_once($repertoire_physique_niveau0."php/database_connection.php");

	include $repertoire_physique_niveau0."gis/gis_class/gisExec.php";

	echo "gis_exec : ".$_SESSION['gis_exec']."<br>";

	$gis_instance = unserialize($_SESSION['gis_exec']);

	$gis_instance->updateDataBaseConnection($database_connection);
	$gis_instance->majByNaDesc();

	session_unregister('gis_exec');
	$_SESSION['gis_exec'] = serialize($gis_instance);

	$gis_display = new displayTotal($gis_instance->view_box, $gis_instance->view_box_origine, $gis_instance->gis_side, $gis_instance->slide_duration, $gis_instance->tab_zoom, $gis_instance->current_zoom, $gis_instance->tab_styles, $gis_instance->tab_layers, $gis_instance->tab_polygones, $gis_instance->raster);

?>