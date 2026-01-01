<?php
/**
 * 09/10/2012 ACS DE GIS 3D ONLY
 *
 *
 * @cb40000@
 *
 * 	14/11/2007 - Copyright Astellia
 *
 * 	Composant de base version cb_4.0.0.00
 *
 - maj 17/04/2008, benoit : correction du bug 6443
 - maj 26/05/2008 - maxime : Affichage d'un emssage d'erreur s'il n'y a aucune coordonnée géographique
 - maj 26/05/2008 - maxime : On ajoute l'export depuis les dash ou résultat des alarmes
 - maj 26/05/2008 - maxime : Affichage de No results  s'il n'y a aucune coordonnée géographique
 - maj 14:59 27/01/2010 - MPR : Correction du bug 13772 : On ne passe plus en paramètre le label du raw/kpi mais on le récupère
 * 16/11/2012 MMT bz 30276 ajout gestion top-worst pour DE GIS 3D only
 *
 */

// 17/04/2008 - Modif. benoit : correction du bug 6443. Modification du fichier pour permettre la génération du KML

session_start();

include_once (dirname(__FILE__) . '/../../php/environnement_liens.php');

include_once (REP_PHYSIQUE_NIVEAU_0 . 'gis/gis_class/gisExec.php');
include_once (REP_PHYSIQUE_NIVEAU_0 . 'php/edw_function_family.php');

/**
 * Retourne le chemin d'agrégation d'un niveau
 *
 * MPR 15/05/2009
 * @since cb4.1.0.0
 * @version cb4.1.0.0
 * @param string $family_min_net : niveau d'agregation minimum de la famille
 * @param string $family : famille
 * @param string $level : niveau dont on veut connaître le chemin jusqu'au
 * @return array : tableau contenant les niveaux d'agrégations depuis $level jusqu'au niveau minimum
 */
function getAgregPathGearth($id_prod, $family_min_net, $level, $family) {

	$database_connection = DataBase::getConnection($id_prod);

	// Récupération du chemin
	$query = "SELECT * FROM get_path('{$level}','{$family_min_net}','{$family}');";

	$array_result = Array();

	$result = $database_connection -> getAll($query);

	foreach ($result as $array) {

		$array_result[] = $array['get_path'];
	}

	// Sauvegarde du résultat dans l'objet pour éviter de rééxécuter les requêtes si on cherche de nouveau les mêmes informations
	$agregPathArray = array_reverse($array_result);

	return $agregPathArray;
}// End function getAgregPath

// Export depuis le GIS
if (!isset($_GET['gis_data'])) {
	// On récupère les données du gis déjà enregistrées
	$gis_instance = unserialize($_SESSION['gis_exec']);

	// 09/10/2012 ACS DE GIS 3D ONLY
	if (!isset($_GET['limitation'])) {
		
                // 29/01/2013 BBX
                // DE Gis Filtering : on compte le nombre d'éléments filtrés en priorité
                $numberOfElements = count(gisExec::getNeListFromSelecteur($gis_instance->na_min, $gis_instance->id_prod));
                if(empty($numberOfElements)) {
                    // On récupère le nombre d'éléments à exporter
                    $numberOfElements = NeModel::getNumberOfNeForGisExport($gis_instance->na_min, $gis_instance->id_prod);
                }
                
		// On compare le nombre d'éléments exportés à la limite configurée dans les paramètres globaux
		$gisLimit = get_sys_global_parameters('gis_kmz_elements_limit', 1, $gis_instance->id_prod);
		if ($numberOfElements > $gisLimit) {
			$naLabel = NaModel::getNaLabelFromId($gis_instance->na, $gis_instance->id_prod);
			if ($gis_instance->sort_type == 'kpi') {
				$rawKpiModel = new KpiModel();
			}
			else {
				$rawKpiModel = new RawModel();
			}
            $rawKpiLabel = $rawKpiModel->getLabelFromId($gis_instance->sort_id	, Database::getConnection($gis_instance->id_prod));
			
            //10/10/2012 MMT DE GIS 3D only ajout du parametre 'type' = supervision
			echo "supervision|s|{$numberOfElements}|s|{$gisLimit}|s|{$naLabel}|s|{$rawKpiLabel}";
			exit;
		}
	}
	else {
		$gis_instance->limitNbElements = (int)$_GET['limitation'];
		$gis_instance->sortOrder = $_GET['order'];
	}

	$gis_instance -> updateDataBaseConnection($gis_instance -> id_prod);
	//$gis_instance->majByViewBoxUpdate($_GET['x'], $_GET['y'], $_GET['width'], $_GET['height']);

	$gis_instance -> traceActions("** Lien vers Google Earth depuis le Gis **", "**************", "**************");

	// Création du fichier kml
	// maj 26/05/2008 - maxime : Affichage de No results  s'il n'y a aucune coordonnée géographique
	// maj 17/06/2011 - MPR : Modification de la condition pour prendre en compte les deux modes
	$index = ($gis_instance -> displayMode == 1) ? $gis_instance -> na : 'cone';
	__debug($index, "INDEX");
	if (count($gis_instance -> tab_polygones[$index]) > 0)
		$file = $gis_instance -> create_kml_file($gis_instance);
	else
		echo 'no_result';

	// maj 26/05/2008 - maxime : On ajoute l'export depuis les dash ou résultat des alarmes
	// Export depuis le résultat des alarmes ou depuis un dashboard
} else {
        // 29/01/2013 BBX
        // DE Filtering GIS : permet de définir le mode du GIS (supervision ou depuis un graph)
        $_SESSION['gis_calling_method'] = 'dash';
    
	$external_data = explode('|@|', urldecode($_GET['gis_data']));

	// __debug($external_data,"ext data");

	$e_data = array();
	$e_data['id_prod'] = $external_data[0];
	$e_data['na'] = $external_data[1];
	$e_data['na_value'] = $external_data[2];
	// On récupère systématiquement tous les éléments réseau
        
        // 29/01/2013 BBX
        // DE Filtering GIS : permet de définir le mode du GIS (supervision, graph ou alarm)
        if(substr_count($external_data[16], 'sys_definition_alarm'))
            $_SESSION['gis_calling_method'] = 'alarm';
	
	// 09/10/2012 ACS DE GIS 3D ONLY
	if (GisModel::getGisMode($e_data['id_prod']) == 2) {
		$naMinimum = get_network_aggregation_min_from_family(get_main_family($e_data['id_prod']), $e_data['id_prod']);
		if ($naMinimum != $e_data['na']) {
			echo 'notNaMin';
			return;
		}
	}

	if (!isset($_GET['limitation'])) {

                // 29/01/2013 BBX
                // DE Gis Filtering : on compte le nombre d'éléments filtrés en priorité
				//Bug 34827 - [QAL][CB531] : "Gis 3D maximum number of elements" parameter has no effect
				$e_data['na_min'] = $external_data[3];
                $numberOfElements = count(gisExec::getNeListFromSelecteur($e_data['na_min'], $e_data['id_prod']));
                if(empty($numberOfElements)) {
                    // On récupère le nombre d'éléments à exporter
                    $numberOfElements = NeModel::getNumberOfNeForGisExport($e_data['na_min'], $e_data['id_prod']);
                }

		// On compare le nombre d'éléments exportés à la limite configurée dans les paramètres globaux
		$gisLimit = get_sys_global_parameters('gis_kmz_elements_limit', 1);
		if ($numberOfElements > $gisLimit) {
			$naLabel = NaModel::getNaLabelFromId($e_data['na'], $e_data['id_prod']);
			$type = $external_data[4];

			if ($type == "graph") {
				if ($external_data[12] == 'kpi') {
					$rawKpiModel = new KpiModel();
				}
				else {
					$rawKpiModel = new RawModel();
				}
	            $label = $rawKpiModel->getLabelFromId($external_data[13], Database::getConnection($e_data['id_prod']));
			}
			else {
                // 16/11/2012 MMT bz 30276 ajout gestion top-worst
                $alarm_type = $external_data[5];
                if($alarm_type == "top-worst"){
                    $type = "top-worst";
                }
				$alarm = new AlarmModel($external_data[9], $alarm_type, $e_data['id_prod']);
				$label = $alarm->getValue("alarm_name");
			}
			
			echo "{$type}|s|{$numberOfElements}|s|{$gisLimit}|s|{$naLabel}|s|{$label}";
			exit;
		}
	}
	else {
		$e_data['order'] = $_GET['order'];
		$e_data['limitation'] = (int)$_GET['limitation'];
	}

	$database_connection = DataBase::getConnection($e_data['id_prod']);

	if ($external_data[2] !== 'ALL') {
		// On vérifie qu'il existe des coordonnées géographiques pour l'élément réseau concerné
		// maj 20/05/2009 - MPR : Suppression de la condition sur axe3

		$na_min = get_network_aggregation_min_from_family(get_main_family($e_data['id_prod']), $e_data['id_prod']);

		if ($e_data['na'] == $na_min) {

			$_select = "SELECT eorp_longitude, eorp_latitude ";
			$_from = "FROM edw_object_ref_parameters ";
			$_where = "WHERE eorp_id = '{$e_data['na_value']}' ";
			$_where .= "AND eorp_longitude IS NOT NULL ";
			$_where .= "AND eorp_latitude IS NOT NULL ";
			$_where .= "AND eorp_azimuth IS NOT NULL";
		} else {

			// Jointure sur la table edw_object_ref_parameters
			/*
			 SELECT eorp_longitude,eorp_latitude FROM edw_object_ref_parameters ep,
			 (
			 SELECT DISTINCT e1.eoar_id
			 FROM edw_object_arc_ref e1, edw_object_arc_ref e2
			 WHERE split_part(e1.eoar_arc_type,'|s|',2) = split_part(e2.eoar_arc_type,'|s|',1)
			 AND e1.eoar_arc_type = 'sai|s|rnc'
			 AND e2.eoar_arc_type = 'rnc|s|network'
			 AND e2.eoar_id_parent = 'n1'
			 ) t
			 WHERE ep.eorp_id = t.eoar_id
			 AND ep.eorp_longitude IS NOT NULL
			 AND ep.eorp_latitude IS NOT NULL
			 LIMIT 1;
			 */

			// On récupère le chemin complet
			$tab = getAgregPathGearth($e_data['id_prod'], $na_min, $e_data['na'], $external_data[6]);

			$na_value = $e_data['na_value'];

			// Création de la sous-requête
			for ($i = 0; $i <= (count($tab) - 2); $i++) {

				if ($i == 0) {
					$sub_select .= "SELECT DISTINCT e{$i}.eoar_id ";
					$sub_from .= "FROM edw_object_arc_ref e{$i} ";
					$sub_where .= "WHERE e{$i}.eoar_arc_type='{$tab[$i]}|s|{$tab[$i+1]}' ";
				} else {
					$sub_from .= ", edw_object_arc_ref e{$i} ";
					$sub_where .= "AND e{$i}.eoar_arc_type='{$tab[$i]}|s|{$tab[$i+1]}' ";
				}
			}

			$sub_where .= " AND e" . ($i - 1) . ".eoar_id_parent = '{$na_value}'";

			$_select = "SELECT eorp_longitude,eorp_latitude ";
			$_from = "FROM edw_object_ref_parameters ep,(" . $sub_select . $sub_from . $sub_where . ") t ";
			$_where = "WHERE ep.eorp_id = t.eoar_id AND ep.eorp_longitude IS NOT NULL ";
			$_where .= "AND ep.eorp_latitude IS NOT NULL AND ep.eorp_azimuth IS NOT NULL";

		}
		$query = $_select . $_from . $_where . " LIMIT 1";
		// $query = "SELECT eorp_longitude, eorp_latitude FROM edw_object_ref_parameters WHERE $where AND longitude IS NOT NULL LIMIT 1";

		$res = $database_connection -> getAll($query);

		foreach ($res as $row) {
			$longitude = $row['longitude'];
			$latitude = $row['latitude'];
			$e_data['na_value'] = 'ALL';
			$e_data['na_value_look_at'] = $external_data[2];
		}

	}

	//  on remplace le nom du champ 'na_base' du tableau de données externes par 'na_min'

	$e_data['na_min'] = $external_data[3];
	$e_data['data_type'] = $external_data[4];
	$e_data['mode'] = $external_data[5];
	$e_data['family'] = $external_data[6];
	$e_data['ta'] = $external_data[7];
	$e_data['ta_value'] = $external_data[8];
	$e_data['id_data_type'] = $external_data[9];
	$e_data['module'] = $external_data[10];

	// maj 22/04/2008 - maxime : ajout d'un paramètre pour supprimer les limites de la vb ( nécessaire pour l'export vers Google Earth)
	$e_data['no_limit_vb'] = true;

	// Cas des alarmes
	$alarm_color = explode(';', $external_data[11]);

	if ($alarm_color != '') {
		for ($i = 0; $i < count($alarm_color); $i++) {
			$alarm_color_detail = explode(':', $alarm_color[$i]);
			$e_data['alarm_color'][$alarm_color_detail[0]] = $alarm_color_detail[1];
		}
	}

	$e_data['sort_type'] = $external_data[12];
	$e_data['sort_id'] = $external_data[13];

	// maj 14:59 27/01/2010 - MPR : Correction du bug 13772 : On ne passe plus en paramètre le label du raw/kpi mais on le récupère
	if ($e_data['sort_type'] == 'kpi') {
		$rawkpi = new KpiModel();
	} else {
		$rawkpi = new RawModel();
	}

	$_label = $rawkpi -> getLabelFromId($e_data['sort_id'], $database_connection);

	$e_data['sort_name'] = $_label;
	$e_data['sort_value'] = $external_data[15];
	$e_data['table_name'] = $external_data[16];

	// 18/07/2007 - Modif. benoit : ajout de l'element na 3eme axe et de sa valeur (si ces infos existent) dans le tableau de données externes

	if (isset($external_data[17]) && ($external_data[17] != "")) {
		$e_data['na_axe3'] = $external_data[17];
	}

	if (isset($external_data[18]) && ($external_data[18] != "")) {
		$e_data['na_value_axe3'] = $external_data[18];
	}

	//  ajout des parametres 'gis_width' et 'gis_height' dans le tableau de données externes par défaut à 50 ( lle gis n'est pas affiché)

	$e_data['gis_width'] = 50;
	$e_data['gis_height'] = 50;

	$gis_instance = new gisExec($e_data);
	$test = implode("|t|", $e_data);
	$gis_instance -> traceActions("** tests param **", $test, "tab");
	$index = ($gis_instance -> displayMode == 1) ? $gis_instance -> na : 'cone';

	unset($_SESSION['gis_exec']);
	$_SESSION['gis_exec'] = serialize($gis_instance);

	// maj  17/07/2008, maxime : correction fu bug 7122 : Export Gearth invalide pour une famille 3ème axe dont un na est sélectionné depusi un dash
	$_na = explode("_", $e_data['na']);

	// maj 26/05/2008 - maxime : Affichage de No results  s'il n'y a aucune coordonnée géographique
	if (count($gis_instance -> tab_polygones[$index]) > 0) {
		$file = $gis_instance -> create_kml_file($gis_instance);
	} else {
		echo 'no_result';
	}
}
?>