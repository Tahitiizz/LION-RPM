<?
/*
*	@cb41000@
*
*	09/12/2008 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.00
*
*	09/12/2008 BBX : modification du script pour le CB 4.1
*	=> Utilisation des nouvelles variables globales
*	=> Utilisation de la classe DatabaseConnection
*	=> Gestion du produit
*/
?>
<?php
session_start();
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";

$activate_sa = false;

// Récupération des variables
$product = $_GET['product'];

// Connexion à la base du produit
$database = DataBase::getConnection($product);

$flat_file_content = $_GET;
$error = false;
if (count($flat_file_content) > 0) {
	
	$sql = "SELECT * FROM sys_definition_flat_file_lib WHERE on_off = 1 ORDER BY id_flat_file DESC";
	$req = $database->execute($sql);

	$activate_sa = ( get_sys_global_parameters("activation_source_availability", 0, $product) ) ? true : false;

	while ($row = $database->getQueryResults($req,1)) {
		
		$id = $row['id_flat_file'];

		$period			= $flat_file_content['period_'.$id];
		$temporization	= (integer)$flat_file_content['tempo_'.$id];
		$exclusion		= "";

		$_update_sa = array();
		if( $activate_sa )
		{
			$granularity = $flat_file_content['granularity_'.$id];
			$data_chunks = $flat_file_content['data_chunks_'.$id];
			$data_collection_freq = $flat_file_content['data_collection_freq_'.$id];
			$_update_sa[] = "data_collection_frequency = {$data_collection_freq}";
			$_update_sa[] = "data_chunks = {$data_chunks}";
			$_update_sa[] = "granularity = '{$granularity}' ";
		}

		// Pour le type de periode "hour", on verifie s'il existe des periodes d'exclusion

		if ($period == "hour")
                {
			$exclusion = str_replace(" ", "", $flat_file_content['exclusion_'.$id]);//decomposeExclusionValues(explode(";", $flat_file_content['exclusion_'.$id]));
		}
		else // Pour le type "day", on convertit la valeur de temporization en heures
		{
			$temporization *= 24;
		}

		// Pour le type de periode "day", on verifie s'il existe des periodes d'exclusion
		$_update_system_alerts = array();
                if ($period != "" && $temporization != "")
		{
                    $_update_system_alerts[] = "alarm_missing_file_temporization = ".$temporization;
                    $_update_system_alerts[] = "exclusion = '".$exclusion."'";
                    $_update_system_alerts[] = "period_type = '".$period."'";
                }

                // Construction de la requête SQL
                if( count($_update_sa) > 0 && count($_update_system_alerts) > 0)
                {
                    $_update = implode(", ",$_update_system_alerts).", ".implode(", ",$_update_sa);
                }
                elseif( count($_update_sa ) > 0)
                {
                    $_update = implode(", ",$_update_sa);
                }
                else
                {
                    $_update = implode(", ", $_update_system_alerts);
                }
							
			$sql2 =	 " UPDATE sys_definition_flat_file_lib"
                                ." SET ".$_update
					." WHERE id_flat_file=".$id;
			
			@$database->execute($sql2) or $error = true;

		}
	}

// Controle des données SA de chaque connexion en prenant en compte les modifications apportées
$connect_in_error_list = array();
if($activate_sa){
	// Recherche et boucle sur chacune des connexions définies dans setup connections
	$select_connect = " SELECT * FROM sys_definition_connection ORDER BY connection_name DESC; ";
	$arrayConnections = $database->getAll($select_connect);
	foreach($arrayConnections as $connection){
		// Pour chacune des connexions, on récupère les types de fichiers
		$query = "
			SELECT id_flat_file, flat_file_name,
			data_collection_frequency, data_chunks, granularity,
			0 AS file_expected
			FROM sys_definition_flat_file_lib
			WHERE on_off = 1
			ORDER BY flat_file_name";
		$files = $database->getAll($query);

		// renseigne la colonne file_expected
		$query = "
			SELECT sdsftpc_id_flat_file, sdsftpc_data_chunks
			FROM sys_definition_sa_file_type_per_connection
			WHERE sdsftpc_id_connection={$connection["id_connection"]}";
		$file_expected = $database->getAll($query);

		// On reprend les chunks en fonction de ceux attendus
		foreach ($files as &$f) {
			foreach ($file_expected as $fe) {
				if ($fe['sdsftpc_id_flat_file'] == $f['id_flat_file']) {
					$f['file_expected'] = true;
					$f['data_chunks'] = $fe['sdsftpc_data_chunks'];
					break;
}
			}
		}

		// On vérifie les données
		foreach ($files as &$f) {
			if (($f['granularity']=='day' && $f['data_collection_frequency'] == '24' && $f['data_chunks'] > 1) ||
				($f['granularity']=='day' && $f['data_collection_frequency'] == '1' && $f['data_chunks'] > 24) ||
				($f['granularity']=='hour' && $f['data_collection_frequency'] == '24' && $f['data_chunks'] > 24) ||
				($f['granularity']=='hour' && $f['data_collection_frequency'] == '1' && $f['data_chunks'] > 24) ||
				($f['granularity']=='hour' && $f['data_collection_frequency'] == '0.25' && $f['data_chunks'] > 96)
				) {
				if(!in_array($connection['connection_name'], $connect_in_error_list))
					$connect_in_error_list[] = $connection['connection_name'];
}
		}
	}
}

// Dans le cas où l'on a une erreur lors de la sauvegarde
if ($error)
{
	echo "failure";	
}
// Dans le cas où l'on a une erreur dans setup_connection due aux modifications
elseif(count($connect_in_error_list)){
	$err_message = "Maximum value of Data Chunks has exceeded. Maximum values expected are:\n";
	$err_message .= "\t- Data Granularity = Day / Data collection frequency =  day / Data chunks   = 1\n";
	$err_message .= "\t- Data Granularity = Hour / Data collection frequency =  day / Data chunks   = 24\n";
	$err_message .= "\t- Data collection frequency = hour / Data chunks   = 24\n";
	$err_message .= "\t- Data collection frequency = 15mn / Data chunks = 96";
	// 11/02/2011 BBX BZ 19187 : Modification du message afin qu'il soit plus informatif
	$err_message .= "\n\nPlease, modify the following connections to comply with your modifications :";
	foreach($connect_in_error_list as $connection_name)
		$err_message .= "\n\t- ".$connection_name;

	echo $err_message;
}
// Si l'on a pas d'erreur
else
{
	echo "success";
}

function decomposeExclusionValues($exclusion)
{
	$exclusion_values = array();
	
	for ($i=0; $i < count($exclusion); $i++) {
		if (strpos($exclusion[$i], "-") === false) {	// Valeur unique
			$exclusion_values[] = $exclusion[$i];
		}
		else // Intervalle
		{
			$exclusion_values = array_merge($exclusion_values, getIntervalValues(explode("-", $exclusion[$i])));
		}
	}
	
	// On supprime les doublons
	$exclusion_values = array_unique($exclusion_values);
	
	// On trie le tableau
	sort($exclusion_values);

	return $exclusion_values;
}

function getIntervalValues($interval)
{
	// Si les valeurs de départ et d'arrivée dans l'intervalle sont les mêmes, on retourne la première valeur

	if ((integer)$interval[0] == (integer)$interval[1]) return array((integer)$interval[0]);

	// On decompose l'intervalle en un tableau de valeurs
	
	$interval_values = array();

	for ($i=$interval[0]; $i <= $interval[1]; $i++) {
		$interval_values[] = (integer)$i;
	}
	return $interval_values;
}
?>