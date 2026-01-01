<?php
/**
 * 
 * @cb40000@
 * 
 * 	14/11/2007 - Copyright Acurio
 * 
 * 	Composant de base version cb_4.0.0.00
 *
	- maj 08/11/2007, benoit : ajout des parametres 'gis_width' et 'gis_height' dans le tableau de données externes
	- maj 08/11/2007, benoit : suppression du parametre 'gis_side' dans l'appel de la fonction 'displayTotal()' et remplacement de celui-ci par   'gis_width' et 'gis_height'
	- maj 13/11/2007, benoit : maj de la viewbox en fonction des dimensions de la vue du GIS
	- maj 14/11/2007, benoit : on fait également une maj de la viewbox initiale en fonction des dimensions de la vue du GIS
	- maj 14/11/2007, benoit : on renvoit maintenant le nouvel output et la nouvelle viewbox (format JSON)
	- maj 15/11/2007, benoit : on met à jour le contenu du GIS en fonction de la nouvelle viewbox
	- maj 11/12/2007, benoit : passage des parametres au script JS appelant via le format JSON
	- maj 12/12/2007, benoit : maj de la valeur du parametre "network_agregation" dans le tableau de session "sys_user_parameter_session"
	-  maj 05/02/2008 - maxime : Export du gis vers Google Earth
	- maj 05/02/2008 - maxime : Export des graphes ou des résultats d'alarmes vers Google Earth
	- maj 19/02/2008 - maxime : gestion de plusieurs éléments réseau d'un même polygone dans data informations
	- maj 22/04/2008 - maxime : ajout d'un paramètre pour supprimer les limites de la vb ( nécessaire pour l'export vers Google Earth)
 *
 */
?>
<?
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14

	- maj 18/07/2007, benoit : dans le cas du premier appel du GIS, ajout de l'element na 3eme axe et de sa valeur   (si ces infos existent) dans le tableau de données externes transmis au GIS

	- maj 20/07/2007, benoit : dans le cas du premier appel du GIS, on remplace le nom du champ 'na_base' du         tableau de données externes par 'na_min'

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
<?php

	session_start();

	
	include_once(dirname(__FILE__)."/../../php/environnement_liens.php");
	// include_once(REP_PHYSIQUE_NIVEAU_0."php/database_connection.php");

	//global $database_connection;

	include_once '../gis_class/displayTotal.php';
	include_once '../gis_class/gisExec.php';
	
	// echo "*********".$_GET['action']." *********";
	switch ($_GET['action']) {
		case "first_load"	:

			$params = urldecode($_GET['external_data']);
			$external_data = explode('|@|', $params);

			$e_data = array();

			$e_data['id_prod']		= $external_data[0];
			$e_data['na']			= $external_data[1];
                        // maj 03/08/2010 - MPR : Correction du BZ 16967
                        // Gestion Sélection multiple des éléments réseau
			$e_data['na_value']		= implode("||", explode("|s|", $external_data[2]));

			// 20/07/2007 - Modif. benoit : on remplace le nom du champ 'na_base' du tableau de données externes par 'na_min'

			$e_data['na_min']		= $external_data[3];
			$e_data['data_type']            = $external_data[4];
			$e_data['mode']			= $external_data[5];
			$e_data['family']		= $external_data[6];
			$e_data['ta']			= $external_data[7];
			$e_data['ta_value']		= $external_data[8];
			$e_data['id_data_type']         = $external_data[9];
			$e_data['module']		= $external_data[10];

			$alarm_color = explode(';', $external_data[11]);

			if ($alarm_color != '') {
				for ($i=0; $i < count($alarm_color); $i++) {
					$alarm_color_detail = explode(':', $alarm_color[$i]);
					$e_data['alarm_color'][$alarm_color_detail[0]] = $alarm_color_detail[1];
				}
			}

			$e_data['sort_type']	= $external_data[12];
			$e_data['sort_id']		= $external_data[13];
			
			$database = DataBase::getConnection( $e_data['id_prod'] );
			// maj 14:59 27/01/2010 - MPR : Correction du bug 13772 : On ne passe plus en paramètre le label du raw/kpi mais on le récupère
			if( $e_data['sort_type'] == 'kpi' )
			{
				// include_once(REP_PHYSIQUE_NIVEAU_0."models/KpiModel.class.php");
				$rawkpi = new KpiModel();
			} 
			else 
			{
				// include_once(REP_PHYSIQUE_NIVEAU_0."models/RawModel.class.php");
				$rawkpi = new RawModel();
			}
			
			$_label = $rawkpi->getLabelFromId( $e_data['sort_id'], $database );
			
			$e_data['sort_name']	= $_label;
			$e_data['sort_value']	= $external_data[15];
			$e_data['table_name']	= $external_data[16];

			// 18/07/2007 - Modif. benoit : ajout de l'element na 3eme axe et de sa valeur (si ces infos existent) dans le tableau de données externes
			
			
			if (isset($external_data[17]) && ($external_data[17] != "")) {
				$e_data['na_axe3'] = $external_data[17];
			}

			if (isset($external_data[18]) && ($external_data[18] != "")) {
				$e_data['na_value_axe3'] = $external_data[18];
			}

			// 08/11/2007 - Modif. benoit : ajout des parametres 'gis_width' et 'gis_height' dans le tableau de données externes

			$e_data['gis_width']	= $_GET['gis_width'];
			$e_data['gis_height']	= $_GET['gis_height'];

			$gis_instance = new gisExec($e_data);
					
			foreach($e_data as $key=>$param){
				if($key == 'sort_name'){
					$gis_instance->traceActions("PARAMS $key récupéré dans GIS MANAGER depuis ".$e_data['sort_id'], $param,"O_o");
				} else {
					$gis_instance->traceActions("PARAMS $key", $param,"o_O");
				}
			}
	
			// 08/11/2007 - Modif. benoit : suppression du parametre 'gis_side' dans l'appel de la fonction 'displayTotal()' et remplacement de celui-ci par 'gis_width' et 'gis_height'
			$gis_instance->traceActions("TAb zoom", implode("/",$gis_instance->tab_zoom),"****");
			$gis_instance->traceActions("current_zoom", $gis_instance->current_zoom,"****");
			$gis_instance->traceActions("mode", $e_data['mode'],"****");
			$gis_instance->traceActions("alarm_color", $e_data['alarm_color'][$alarm_color_detail[0]],"****");
			$gis_instance->traceActions("view_box", implode(' ', $gis_instance->view_box),"****");
			$gis_instance->traceActions("view_origine", implode(',', $gis_instance->view_box_origine),"****");
			$gis_instance->traceActions("slide_duration", $gis_instance->slide_duration,"****");

			
			$gis_display = new displayTotal($gis_instance->view_box, $gis_instance->view_box_origine, $gis_instance->gis_width, $gis_instance->gis_height, $gis_instance->slide_duration, $gis_instance->tab_zoom, $gis_instance->current_zoom, $gis_instance->tab_styles, $gis_instance->tab_layers, $gis_instance->tab_polygones, $gis_instance->na, $gis_instance->raster, $gis_instance->displayMode);
					
			// 11/10/2007 - Modif. benoit : plus de stockage en session du display SVG du GIS

			// 11/10/2007 - Modif. benoit : le dernier parametre de la fonction JS 'InitCtrlParameters()' est '$gis_display->output' (chemin vers le raster) et non plus '$gis_instance->raster' (booleen indiquant si le mode raster est actif ou non)

			//echo "<script>window.parent.initCtrlParameters('".implode(',', $gis_instance->tab_zoom)."', '".$gis_instance->current_zoom."', '".implode(' ', $gis_instance->view_box)."', '".implode(',', $gis_instance->view_box_origine)."', '".$gis_display->output."', '".$gis_display->status."')</script>";
			
			// 31/10/2007 - Modif. benoit : passage des parametres au script JS appelant via le format JSON
			$gis_instance->traceActions("raster", $gis_display->output,"****");
			$gis_instance->traceActions("status", $gis_display->status,"****");
			
			$_SESSION['gis_exec'] = serialize($gis_instance);
			
			echo '{"tab_paliers":\''.implode(',', $gis_instance->tab_zoom).'\', "current_zoom":\''.$gis_instance->current_zoom.'\', "viewbox":\''.implode(' ', $gis_instance->view_box).'\', "view_origine":\''.implode(',', $gis_instance->view_box_origine).'\', "raster":\''.$gis_display->output.'\', "status":\''.$gis_display->status.'\'}';

		break;

		case "update_vb"	:

			$gis_instance = unserialize($_SESSION['gis_exec']);

			$gis_instance->updateDataBaseConnection($gis_instance->id_prod);
			$gis_instance->majByViewBoxUpdate($_GET['x'], $_GET['y'], $_GET['width'], $_GET['height']);

			session_unregister('gis_exec');
			$_SESSION['gis_exec'] = serialize($gis_instance);

			// 08/11/2007 - Modif. benoit : suppression du parametre 'gis_side' dans l'appel de la fonction 'displayTotal()' et remplacement de celui-ci par 'gis_width' et 'gis_height'
            // 20/06/2011 NSE : merge Gis without polygons
			$gis_display = new displayTotal($gis_instance->view_box, $gis_instance->view_box_origine, $gis_instance->gis_width, $gis_instance->gis_height, $gis_instance->slide_duration, $gis_instance->tab_zoom, $gis_instance->current_zoom, $gis_instance->tab_styles, $gis_instance->tab_layers, $gis_instance->tab_polygones, $gis_instance->na, $gis_instance->raster, $gis_instance->displayMode);

			// 11/10/2007 - Modif. benoit : a la fin de la maj de la viewbox, on ne met plus à jour la variable de session mais l'on renvoie le chemin vers le raster

			//$_SESSION['gis'] = $gis_display->output;

			echo $gis_display->output;

		break;
		
		// maj 19/02/2008 - maxime : gestion de plusieurs éléments réseau d'un même polygone dans data informations
		// Ajout du paramètre na
		case "show_na_information"	:

			$gis_instance = unserialize($_SESSION['gis_exec']);

			$gis_instance->updateDataBaseConnection($gis_instance->id_prod);

			echo $gis_instance->showNaInformation($_GET['x'], -1*$_GET['y'], $gis_instance->na);

		break;

		case "desc_in_na"	:

			$gis_instance = unserialize($_SESSION['gis_exec']);

			$gis_instance->updateDataBaseConnection($gis_instance->id_prod);
			$gis_instance->majByNaDesc();

			session_unregister('gis_exec');
			$_SESSION['gis_exec'] = serialize($gis_instance);

			// 08/11/2007 - Modif. benoit : suppression du parametre 'gis_side' dans l'appel de la fonction 'displayTotal()' et remplacement de celui-ci par 'gis_width' et 'gis_height'
// 20/06/2011 NSE : merge Gis without polygons
			$gis_display = new displayTotal($gis_instance->view_box, $gis_instance->view_box_origine, $gis_instance->gis_width, $gis_instance->gis_height, $gis_instance->slide_duration, $gis_instance->tab_zoom, $gis_instance->current_zoom, $gis_instance->tab_styles, $gis_instance->tab_layers, $gis_instance->tab_polygones, $gis_instance->na, $gis_instance->raster, $gis_instance->displayMode);

			// 12/12/2007 - Modif. benoit : maj de la valeur du parametre "network_agregation" dans le tableau de session "sys_user_parameter_session"

			if (isset($_SESSION['sys_user_parameter_session'][$gis_instance->family]['gis_supervision']))
			{	
				$sys_user_parameter_session_gis = $_SESSION['sys_user_parameter_session'][$gis_instance->family]['gis_supervision'];
				
				$ne_label	= getNELabel($gis_instance->na,$gis_instance->na_value, $gis_instance->id_prod );
				
				$sys_user_parameter_session_gis['gis_nel_selecteur'] 	 = "{$gis_instance->na}@{$ne_label}@({$gis_instance->na_value})";
				$sys_user_parameter_session_gis['na_level'] 	 = $gis_instance->na;
				
				
				
				$_SESSION['sys_user_parameter_session'][$gis_instance->family]['gis_supervision'] = $sys_user_parameter_session_gis;
				
			}
			

			// 12/10/2007 - Modif. benoit : une fois le nouveau gis défini, on ne met plus à jour la variable de session mais l'on renvoie le chemin vers le raster

			//$_SESSION['gis'] = $gis_display->output;
			
			//echo "fin";

			// 11/12/2007 - Modif. benoit : passage des parametres au script JS appelant via le format JSON

			//echo $gis_display->output;

			echo '{"url":\''.$gis_display->output.'\', "new_na":\''.$gis_instance->na.'\'}';

		break;

		case "add_layers"	:

			$gis_instance = unserialize($_SESSION['gis_exec']);

			$gis_instance->updateDataBaseConnection($gis_instance->id_prod);
			$gis_instance->majByLayersUpdate("add", explode(';', $_GET['layers_added']));

			session_unregister('gis_exec');
			$_SESSION['gis_exec'] = serialize($gis_instance);

			// 08/11/2007 - Modif. benoit : suppression du parametre 'gis_side' dans l'appel de la fonction 'displayTotal()' et remplacement de celui-ci par 'gis_width' et 'gis_height'
// 20/06/2011 NSE : merge Gis without polygons
			$gis_display = new displayTotal($gis_instance->view_box, $gis_instance->view_box_origine, $gis_instance->gis_width, $gis_instance->gis_height, $gis_instance->slide_duration, $gis_instance->tab_zoom, $gis_instance->current_zoom, $gis_instance->tab_styles, $gis_instance->tab_layers, $gis_instance->tab_polygones, $gis_instance->na, $gis_instance->raster, $gis_instance->displayMode);

			// 26/10/2007 - Modif. benoit : une fois le nouveau gis défini, on ne met plus à jour la variable de session mais l'on renvoie le chemin vers le raster

			//$_SESSION['gis'] = $gis_display->output;
			
			//echo "fin";

			echo $gis_display->output;

		break;

		case "remove_layers"	:

			$gis_instance = unserialize($_SESSION['gis_exec']);

			$gis_instance->updateDataBaseConnection($gis_instance->id_prod);
			$gis_instance->majByLayersUpdate("del", explode(';', $_GET['layers_removed']));

			session_unregister('gis_exec');
			$_SESSION['gis_exec'] = serialize($gis_instance);

			// 08/11/2007 - Modif. benoit : suppression du parametre 'gis_side' dans l'appel de la fonction 'displayTotal()' et remplacement de celui-ci par 'gis_width' et 'gis_height'
// 20/06/2011 NSE : merge Gis without polygons
			$gis_display = new displayTotal($gis_instance->view_box, $gis_instance->view_box_origine, $gis_instance->gis_width, $gis_instance->gis_height, $gis_instance->slide_duration, $gis_instance->tab_zoom, $gis_instance->current_zoom, $gis_instance->tab_styles, $gis_instance->tab_layers, $gis_instance->tab_polygones, $gis_instance->na,$gis_instance->raster, $gis_instance->displayMode);

			// 26/10/2007 - Modif. benoit : une fois le nouveau gis défini, on ne met plus à jour la variable de session mais l'on renvoie le chemin vers le raster

			//$_SESSION['gis'] = $gis_display->output;
			
			//echo "fin";

			echo $gis_display->output;

		break;

		case "change_layers_order"	:

			$gis_instance = unserialize($_SESSION['gis_exec']);

			$gis_instance->updateDataBaseConnection($gis_instance->id_prod);
			$gis_instance->majLayersOrder($_GET['layer_up'], $_GET['layer_down']);

			session_unregister('gis_exec');
			$_SESSION['gis_exec'] = serialize($gis_instance);

			// 08/11/2007 - Modif. benoit : suppression du parametre 'gis_side' dans l'appel de la fonction 'displayTotal()' et remplacement de celui-ci par 'gis_width' et 'gis_height'
// 20/06/2011 NSE : merge Gis without polygons
			$gis_display = new displayTotal($gis_instance->view_box, $gis_instance->view_box_origine, $gis_instance->gis_width, $gis_instance->gis_height, $gis_instance->slide_duration, $gis_instance->tab_zoom, $gis_instance->current_zoom, $gis_instance->tab_styles, $gis_instance->tab_layers, $gis_instance->tab_polygones,$gis_instance->na, $gis_instance->raster, $gis_instance->displayMode);

			// 26/10/2007 - Modif. benoit : une fois le nouveau gis défini, on ne met plus à jour la variable de session mais l'on renvoie le chemin vers le raster

			//$_SESSION['gis'] = $gis_display->output;
			
			//echo "fin";

			echo $gis_display->output;

		break;

		case "set_mouseover_layer"	:

			$gis_instance = unserialize($_SESSION['gis_exec']);

			$gis_instance->updateDataBaseConnection($gis_instance->id_prod);
			$gis_instance->setLayerMouseOver($_GET['layer_mouseover']);

			session_unregister('gis_exec');
			$_SESSION['gis_exec'] = serialize($gis_instance);

		break;

		case "change_layers_pptes"	:

			$gis_instance = unserialize($_SESSION['gis_exec']);

			$gis_instance->updateDataBaseConnection($gis_instance->id_prod);

			if($_GET['background'] == "true"){
				$background = true;
			}
			else
			{
				$background = false;
			}

			if($_GET['border'] == "true"){
				$border = true;
			}
			else
			{
				$border = false;
			}

			$gis_instance->majLayersPptes($_GET['layer'], $background, $border);

			session_unregister('gis_exec');
			$_SESSION['gis_exec'] = serialize($gis_instance);

			// 08/11/2007 - Modif. benoit : suppression du parametre 'gis_side' dans l'appel de la fonction 'displayTotal()' et remplacement de celui-ci par 'gis_width' et 'gis_height'
// 20/06/2011 NSE : merge Gis without polygons
			$gis_display = new displayTotal($gis_instance->view_box, $gis_instance->view_box_origine, $gis_instance->gis_width, $gis_instance->gis_height, $gis_instance->slide_duration, $gis_instance->tab_zoom, $gis_instance->current_zoom, $gis_instance->tab_styles, $gis_instance->tab_layers, $gis_instance->tab_polygones, $gis_instance->na, $gis_instance->raster, $gis_instance->displayMode);

			// 26/10/2007 - Modif. benoit : une fois le nouveau gis défini, on ne met plus à jour la variable de session mais l'on renvoie le chemin vers le raster

			//$_SESSION['gis'] = $gis_display->output;
			
			//echo "fin";

			echo $gis_display->output;

		break;

		// 31/10/2007 - Modif. benoit : nouveau cas -> redimensionnement de la map

		case "resize"	:

			$gis_instance = unserialize($_SESSION['gis_exec']);
			$gis_instance->updateDataBaseConnection($gis_instance->id_prod);
			
			
			//$gis_instance->gis_side = $_GET['side'];

			$old_dims = array($gis_instance->gis_width, $gis_instance->gis_height);

			$gis_instance->gis_width	= $_GET['width'];
			$gis_instance->gis_height	= $_GET['height'];

			// 13/11/2007 - Modif. benoit : maj de la viewbox en fonction des dimensions de la vue du GIS

			$gis_instance->view_box = $gis_instance->updateViewBoxFromView($gis_instance->view_box, $old_dims);

			// 14/11/2007 - Modif. benoit : on fait également une maj de la viewbox initiale en fonction des dimensions de la vue du GIS

			$gis_instance->view_box_origine = $gis_instance->updateViewBoxFromView($gis_instance->view_box_origine, $old_dims);

			// 15/11/2007 - Modif. benoit : on met à jour le contenu du GIS en fonction de la nouvelle viewbox

			$gis_instance->majByViewBoxUpdate($gis_instance->view_box[0], $gis_instance->view_box[1], $gis_instance->view_box[2], $gis_instance->view_box[3]);

			session_unregister('gis_exec');
			$_SESSION['gis_exec'] = serialize($gis_instance);

			// 08/11/2007 - Modif. benoit : suppression du parametre 'gis_side' dans l'appel de la fonction 'displayTotal()' et remplacement de celui-ci par 'gis_width' et 'gis_height'
// 20/06/2011 NSE : merge Gis without polygons
			$gis_display = new displayTotal($gis_instance->view_box, $gis_instance->view_box_origine, $gis_instance->gis_width, $gis_instance->gis_height, $gis_instance->slide_duration, $gis_instance->tab_zoom, $gis_instance->current_zoom, $gis_instance->tab_styles, $gis_instance->tab_layers, $gis_instance->tab_polygones, $gis_instance->na, $gis_instance->raster, $gis_instance->displayMode);

			// 14/11/2007 - Modif. benoit : on renvoit maintenant le nouvel output et la nouvelle viewbox (format JSON)

			echo '{"output":\''.$gis_display->output.'\', "viewbox":\''.implode(' ', $gis_instance->view_box).'\', "initial_viewbox":\''.implode(' ', $gis_instance->view_box_origine).'\'}';

		break;
		
		// maj 15:42 05/02/2008 - maxime : Export du gis vers Google Earth
		
		case "send_to_gearth_from_gis" :
			
			// On récupère les données du gis déjà enregistrées
			$gis_instance = unserialize($_SESSION['gis_exec']);
			
			$gis_instance->updateDataBaseConnection($gis_instance->id_prod);
			$gis_instance->majByViewBoxUpdate($_GET['x'], $_GET['y'], $_GET['width'], $_GET['height']);
			
			$gis_instance->traceActions("** Lien vers Google Earth depuis le Gis **", "**************", "**************");
			
			// include_once("export_gearth.php");
			session_unregister('gis_exec');
			$_SESSION['gis_exec'] = serialize($gis_instance);
			
			// Création du fichier kml
			$file = $gis_instance->create_kml_file($gis_instance);		
			// 20/06/2011 NSE : merge Gis without polygons
			$gis_display = new displayTotal($gis_instance->view_box, $gis_instance->view_box_origine, $gis_instance->gis_width, $gis_instance->gis_height, $gis_instance->slide_duration, $gis_instance->tab_zoom, $gis_instance->current_zoom, $gis_instance->tab_styles, $gis_instance->tab_layers, $gis_instance->tab_polygones, $gis_instance->na, $gis_instance->raster, $gis_instance->displayMode);

			echo $file;
			
		break;
		
		// maj 15:42 05/02/2008 - maxime : Export des graphes ou des résultats d'alarmes vers Google Earth
		
		case "send_to_gearth_from_graph_alarm_results":
					
			$external_data = explode('|@|', urldecode($_GET['gis_data']));

			$e_data = array();
			$e_data['id_prod']		= $external_data[0];
			$e_data['na']			= $external_data[1];
                        // 28/03/2011 NSE merge 5.0.5 -> 5.1.1
                        // modification imutée à 29/06/2010 OJT bz 15174 : Problem on GIS 2D on Over Network mode
            $naLocalValues = explode( '||', $external_data[2] );
            if( count( $naLocalValues ) > 1 ){
                $external_data[2] = 'ALL';
            }
            else{
                $external_data[2]	= $naLocalValues[0];
            }
			$e_data['na_value']		= $external_data[2];
			
			//  on remplace le nom du champ 'na_base' du tableau de données externes par 'na_min'
			$e_data['na_min']		= $external_data[3];
			$e_data['data_type']	= $external_data[4];
			$e_data['mode']			= $external_data[5];
			$e_data['family']		= $external_data[6];
			$e_data['ta']			= $external_data[7];
			$e_data['ta_value']		= $external_data[8];
			$e_data['id_data_type']	= $external_data[9];
			$e_data['module']		= $external_data[10];
			// On récupère systématiquement tous les éléments réseau
		
			if( $external_data[2] !== 'ALL' ){
				
				// On vérifie qu'il existe des coordonnées géographiques pour l'élément réseau concerné
                                // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
				$database_connection = Database::getConnection($e_data['id_prod']);
				
				if( $e_data['na'] == $e_data['na_min'] ){
			
					$_select = "SELECT eorp_longitude, eorp_latitude ";
					$_from 	 = "FROM edw_object_ref_parameters ";
					$_where  = "WHERE eorp_id = '{$e_data['na_value']}' ";
					$_where .= "AND eorp_longitude IS NOT NULL ";
					$_where .= "AND eorp_latitude IS NOT NULL ";
					$_where .= "AND eorp_azimuth IS NOT NULL";
					
				}else{
			
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
					$tab = getAgregPath( $e_data['id_prod'], $na_min, $e_data['na'], $external_data[6] );
					
					$na_value = $e_data['na_value'];
					
					// Création de la sous-requête
					for( $i = 0; $i <= (count($tab) - 2); $i++ ){
						
						if($i == 0)
						{
							$sub_select	.= "SELECT DISTINCT e{$i}.eoar_id ";
							$sub_from	.= "FROM edw_object_arc_ref e{$i} ";
							$sub_where 	.= "WHERE e{$i}.eoar_arc_type='{$tab[$i]}|s|{$tab[$i+1]}' ";
						}
						else
						{
							$sub_from	.= ", edw_object_arc_ref e{$i} ";
							$sub_where 	.= "AND e{$i}.eoar_arc_type='{$tab[$i]}|s|{$tab[$i+1]}' ";
						}
					}

					$sub_where .= " AND e".($i-1).".eoar_id_parent = '{$na_value}'"; 
				

					$_select = "SELECT eorp_longitude,eorp_latitude ";
					$_from 	 = "FROM edw_object_ref_parameters ep,(".$sub_select.$sub_from.$sub_where.") t ";
					$_where  = "WHERE ep.eorp_id = t.eoar_id AND ep.eorp_longitude IS NOT NULL ";
					$_where .= "AND ep.eorp_latitude IS NOT NULL AND ep.eorp_azimuth IS NOT NULL";
					
				}
				$query = $_select.$_from.$_where." LIMIT 1";
				$res = $database_connection->getAll($query);

				
				foreach( $res as $row ){
					$longitude = $row['longitude'];
					$latitude = $row['latitude'];
					$e_data['na_value']	    	= 'ALL';
					$e_data['na_value_look_at'] = $external_data[2];
				}
				
			}
			
			// maj 22/04/2008 - maxime : ajout d'un paramètre pour supprimer les limites de la vb ( nécessaire pour l'export vers Google Earth)
			$e_data['no_limit_vb'] = true;

			// Cas des alarmes 
			$alarm_color = explode(';', $external_data[11]);
		
			if ($alarm_color != '') {
				for ($i=0; $i < count($alarm_color); $i++) {
					$alarm_color_detail = explode(':', $alarm_color[$i]);
					$e_data['alarm_color'][$alarm_color_detail[0]] = $alarm_color_detail[1];
				}
			}

			$e_data['sort_type']	= $external_data[12];
			$e_data['sort_id']		= $external_data[13];
			$e_data['sort_name']	= $external_data[14];
			$e_data['sort_value']	= $external_data[15];
			$e_data['table_name']	= $external_data[16];

			// 18/07/2007 - Modif. benoit : ajout de l'element na 3eme axe et de sa valeur (si ces infos existent) dans le tableau de données externes

			if (isset($external_data[17]) && ($external_data[17] != "")) {
				$e_data['na_axe3'] = $external_data[17];
			}

			if (isset($external_data[18]) && ($external_data[18] != "")) {
				$e_data['na_value_axe3'] = $external_data[18];
			}

			//  ajout des parametres 'gis_width' et 'gis_height' dans le tableau de données externes par défaut à 50 ( lle gis n'est pas affiché)

			$e_data['gis_width']	= 50;
			$e_data['gis_height']	= 50;

			$gis_instance = new gisExec($e_data);
			
			unset($_SESSION['gis_exec']);
			$_SESSION['gis_exec'] = serialize($gis_instance);
			
			// Création du fichier kml
			$file = $gis_instance->create_kml_file($gis_instance);
			$gis_instance->traceActions("*******",$file,"file export");
			
			echo $file;
			
		break;
		
		case "destroy_all"	:

			session_unregister('gis_exec');
			session_unregister('gis');
			session_unregister('kml_file');
			//session_unregister('tab_layers');
			//session_unregister('list_na_html');

			//if (isset($_SESSION['miniature'])) session_unregister('miniature');

		break;
	}

	// Temporaire : tester le temps d'execution de la requete de recherche des valeurs en fonction de la position du curseur

	function microtime_float()
	{
	   list($usec, $sec) = explode(" ", microtime());
	   return ((float)$usec + (float)$sec);
	}

?>
