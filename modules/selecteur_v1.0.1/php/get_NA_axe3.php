<?php
/**
*	Ce fichier est appelé via ajax pour donner la liste des options du second menu 3eme axe
*
*	@author	SLC - 26/09/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*/

	// header("Cache-Control: no-cache, must-revalidate");

	include_once("../../../php/database_connection.php");

	// On récupère le contenu de la sélection courante et le séparateur.
	$axe3	= $_GET['axe3'];
	$axe3_2	= $_GET['axe3_2'];
	
	// On émule un pseudo menu, ne sachant pas la requête SQL exacte permettant de générer le sous menu du selecteur 3eme axe
	$html = '';
	for ($i=0; $i<3; $i++) {
		/*
		$html .= "<option value='$axe3-$i'";
		if ("$axe3-$i" == $axe3_2) $html .= " selected='selected'";
		$html .= ">$axe3-$i</option>";	*/
		
		$html .= "|s|$axe3-$i|$axe3-$i";
	}
	
	// on enlève le premier separateur
	$html = substr($html,3);
	
	// on envoie le tout
	echo $html;
	exit;


/*	contenu a modifier lorsque l'on connaîtra la bonne requête SQL


	$query = "SELECT value,label FROM  ??? WHERE ???_id='$axe3'";
	$result = pg_query($database_connection,$query);
	$nb_result = pg_num_rows($result);

	if ($nb_result == 0) {
		echo "<option value=''>".__T('SELECTEUR_NO_VALUE_FOUND')."</option>";
		exit;
	}	

	$html = '';
	for ($i = 0; $i < $nb_result; $i++) {
		$row = pg_fetch_array($result, $i);
		$html .= "<option value='{$row['value']}'";
		if ($row['value'] == $axe3_2) $html .= " selected='selected'";
		$html .= ">{$row['label']}</option>";
	}

	echo $html;
	*/
	
?>