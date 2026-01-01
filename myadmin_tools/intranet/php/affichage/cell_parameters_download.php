<?php

	session_start();

	include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
	include_once($repertoire_physique_niveau0 . "php/database_connection.php");
	include_once($repertoire_physique_niveau0 . "php/edw_function.php");
	include_once($repertoire_physique_niveau0 . "php/postgres_functions.php");

	// Creation du fichier d'export

	$filepath = $repertoire_physique_niveau0.'png_file/cell_parameters_'.date('Ymdhis').'.csv';

	// On copie les lignes de la table 'edw_object_capacity_ref' dans le fichier

	$sql = "COPY edw_object_capacity_ref TO '".$filepath."' DELIMITERS ';' CSV HEADER";
	$req = pg_query($sql);

	// Envoi du fichier vers la sortie standard

	header("Content-disposition: attachment; filename=".basename($filepath));
	header("Content-Type: application/force-download");
	header("Content-Transfer-Encoding: application/octet-stream\n");
	header("Content-Length: ".filesize($filepath));
	header("Pragma: no-cache");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0, public");
	header("Expires: 0");
	readfile($filepath);
	if (is_file($filepath)) unlink($filepath);