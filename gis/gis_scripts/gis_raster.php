<?php
/**
 * 
 * @cb40000@
 * 
 * 	14/11/2007 - Copyright Acurio
 * 
 * 	Composant de base version cb_4.0.0.00
 *
	- maj 04/01/2008, benoit : modification des fonctions deprecated en php5
	- maj 02/06/2009 - MPR : utilisation de la connexion à la db de l'instance du gis
	- maj 29/07/2009 - MPR : Correction du bug 10855 Ajout dans le caddy KO
 *
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


	include_once("../../php/environnement_liens.php");


	include '../gis_class/displayTotal.php';
	include '../gis_class/gisExec.php';

	
	// $gis_instance = unserialize($_SESSION['gis_exec']);
	// session_unregister('gis_exec');
	// $_SESSION['gis_exec'] = serialize($gis_instance);

	// 08/11/2007 - Modif. benoit : suppression du parametre 'gis_side' dans l'appel de la fonction 'displayTotal()' et remplacement de celui-ci par 'gis_width' et 'gis_height'

	// $gis_display = new displayTotal($gis_instance->view_box, $gis_instance->view_box_origine, $gis_instance->gis_width, $gis_instance->gis_height, $gis_instance->slide_duration, $gis_instance->tab_zoom, $gis_instance->current_zoom, $gis_instance->tab_styles, $gis_instance->tab_layers, $gis_instance->tab_polygones, $gis_instance->raster);

	// 11/10/2007 - Modif. benoit : a la fin de la maj de la viewbox, on ne met plus a jour la variable de session mais l'on renvoie le chemin vers le raster

	//$_SESSION['gis'] = $gis_display->output;

	// echo $gis_display->output;

	$rasterisation = "not_ok";

	// On genere le raster de la vue en cours du GIS

	$gis_instance = unserialize($_SESSION['gis_exec']);

	// 19/11/2007 - Modif. benoit : si la vue active du GIS a des dimensions supérieures à celles d'origines, celles-ci seront réadaptées afin que le raster généré soit de dimensions correctes dans le panier

	if (($gis_instance->gis_width > $gis_instance->initial_gis_width) || ($gis_instance->gis_height > $gis_instance->initial_gis_height))
	{
		$gis_instance->gis_width	= $gis_instance->initial_gis_width;
		$gis_instance->gis_height	= $gis_instance->initial_gis_height;
	}
	

	// 19/11/2007 - Modif. benoit : remplacement de l'argument 'gis_side' par les 2 arguments 'gis_width' et 'gis_height'

        // 02/08/2011 BBX
        // Ajout du dernier paramètre manquant
        // BZ 23029
	$gis_display = new displayTotal($gis_instance->view_box, $gis_instance->view_box_origine, $gis_instance->gis_width, $gis_instance->gis_height, $gis_instance->slide_duration, $gis_instance->tab_zoom, $gis_instance->current_zoom, $gis_instance->tab_styles, $gis_instance->tab_layers, $gis_instance->tab_polygones, $gis_instance->na, 0, 1);

	$raster = "../".$gis_display->output;

	// $raster = "../".$gis_instance->raster;
	

	// Si le raster existe, on le recrée dans le dossier "png_file/" et on le sauvegarde en base dans le panier

	if ( is_file($raster) ) {

		// Création de l'image de la vue du GIS (avec bordure noire)
		
		$raster_img_tmp	= imagecreatefrompng($raster);
		
		$raster_img	= imagecreatetruecolor(imagesx($raster_img_tmp)+2, imagesy($raster_img_tmp)+2);

		imagefilledrectangle($raster_img, 0, 0, imagesx($raster_img)-1, imagesy($raster_img)-1, imagecolorallocate($raster_img, 0, 0, 0));

		imagecopy($raster_img, $raster_img_tmp, 1, 1, 0, 0, imagesx($raster_img_tmp), imagesy($raster_img_tmp));

		// Creation de l'image de la légende
                // 02/08/2011 BBX
                // Ajout du dernier paramètre manquant
                // BZ 23029
		$legend_img = generateLegendImg(array(5, 0, 200, 250), $gis_instance->sort_name, '../fonts/arial.ttf', 9, $gis_instance->database_connection, $gis_instance->table_name, $gis_instance->id_data_type, $gis_instance->data_type, $gis_instance->data_range_values, $gis_instance->tab_styles, $database_connection);
		
		// Fusion des 2 images

		$final_width	= imagesx($raster_img)+imagesx($legend_img)+15;
		$final_height	= (imagesy($raster_img) > imagesy($legend_img)) ? imagesy($raster_img) : imagesy($legend_img);
		
		$final_height += 10;

		$final_img	= imagecreatetruecolor($final_width, $final_height);
		imagefilledrectangle($final_img, 0, 0, $final_width-1, $final_height-1, imagecolorallocate($final_img, 255, 255, 255));

		imagecopy($final_img, $raster_img, (imagesx($legend_img)+10), ($final_height-imagesy($raster_img))/2, 0, 0, imagesx($raster_img), imagesy($raster_img));

		imagecopy($final_img, $legend_img, 0, ($final_height-imagesy($legend_img))/2, 0, 0, imagesx($legend_img), imagesy($legend_img));

		$raster_name = ereg_replace('../gis_temp/', '', $raster);
		
		imagepng($final_img, REP_PHYSIQUE_NIVEAU_0."png_file/".$raster_name);
		// Destruction des images temporaires générées

		if(is_file($raster)) unlink($raster);
		
		imagedestroy($raster_img_tmp);
		imagedestroy($raster_img);
		imagedestroy($legend_img);
		
		$raster_name = ereg_replace('../gis_temp/', '', $raster);
		
		// $cmd = "cp -f {$raster} ".REP_PHYSIQUE_NIVEAU_0."png_file/".$raster_name;
		// exec($cmd);
	
	
		// if(is_file($raster)) unlink($raster);
		
		// Si l'image finale existe, on sauvegarde en base
		if ( is_file(REP_PHYSIQUE_NIVEAU_0."png_file/".$raster_name) ) {
			
			// Creation du titre
			// maj 02/06/2009 - MPR : utilisation de la connexion à la db de l'instance du gis
			$title = generateTitle($gis_instance->family, $gis_instance->na, $gis_instance->na_value, $gis_instance->ta, $gis_instance->ta_value,$gis_instance->database_connection);

			// Sauvegarde en base
			// maj 29/07/2009 - MPR : Correction du bug 10855 Ajout dans le caddy KO
			$sql = "INSERT INTO sys_panier_mgt(id_user, object_title, object_type, object_id) VALUES ('".$_SESSION['id_user']."', '".$title."', 'gis_raster', '".$raster_name."')";

			$gis_instance->traceActions("rasterisation", $sql, "query");
			$gis_instance->database_connection->execute($sql);

			$rasterisation = "ok";
		}
	}

	echo $rasterisation;

	// Fonction de generation du titre du raster tel qu'il apparaitra dans le panier

	function generateTitle($family, $na, $na_value, $ta, $ta_value, $database_connection)
	{
		// na_label

		$sql = "SELECT agregation_label FROM sys_definition_network_agregation WHERE agregation_name = '".$na."' AND family = '".$family."'";

		$req = $database_connection->getAll($sql);
		$row = $req[0];

		($row['agregation_label'] != '') ? $na_label = $row['agregation_label'] : $na_label = $na;

		// na_value_label

		if ($na_value != "ALL") {
			$sql = "SELECT ".$na."_label AS na_value_label FROM edw_object_1_ref WHERE ".$na." = '".$na_value."'";

			$req = $database_connection->getAll(sql);
			$row = $req[0];

			($row['na_value_label'] != '') ? $na_value_label = $row['na_value_label'] : $na_value_label = $na_value;
		}
		else 
		{
			$na_value_label = $na_value;	
		}

		// ta_label

		$sql = "SELECT agregation_label FROM sys_definition_time_agregation WHERE agregation = '".$ta."'";

		$req = $database_connection->getAll($sql);
		$row = $req[0];

		($row['agregation_label'] != '') ? $ta_label = $row['agregation_label'] : $ta_label = $ta;

		// ta_value_label

		$ta_value_label = getTaValueToDisplay($ta, $ta_value);

		// On retourne le titre correctement formaté

		return $na_label."=".$na_value_label." - ".$ta_label."=".$ta_value_label;		
	}

	// Fonction de generation de l'image de la légende
	// maj 02/06/2009 - MPR : utilisation de la connexion à la db de l'instance du gis
	function generateLegendImg($box, $sort_name, $font, $font_size, $database_connection, $table_name, $id_data_type, $data_type, $data_range, $tab_styles, $database_connection)
	{
		// Creation de l'image
		
		$im = imagecreatetruecolor($box[2], $box[3]);

		// Creation des couleurs utilisées dans l'image
		
		$white = imagecolorallocate($im, 255, 255, 255);
		$black = imagecolorallocate($im, 0, 0, 0);
		
		imagefilledrectangle($im, 0, 0, $box[2]-1, $box[3]-1, $white);	

		$start_x = $box[0];

		// Construction du titre

		if ($data_type == "alarm") {
			
			$sql = "SELECT alarm_name FROM ".$table_name." alarm WHERE alarm_id=".$id_data_type;
			$req = $database_connection->getAll($sql);
			$row = $req[0];

			$sort_name = $row['alarm_name'];
		}

		imagettftext($im, $font_size, 0, $start_x, 20, $black, $font, $sort_name);

		$bbox = imagettfbboxextended($font_size, 0, $font, $sort_name);
		
		$cumulated_y = $bbox['y'];

		// Construction du corps

		// Pour les graphes, on inclut le style defaut dans le tableau de data_ranges

		if ($data_type == "graph") {
			$range_default = array();

			$range_default[] = array('style_name'=>"style_voronoi_defaut", 'range_inf'=>"n/a", 'range_sup'=>"default style");
			$range_default[] = array('style_name'=>"style_defaut_cone", 'range_inf'=>"n/a", 'range_sup'=>"default style (direction)");

			$data_range = array_merge($data_range, $range_default);
		}

		for ($i=0; $i < count($tab_styles); $i++) {
			$style_def = array();
			$style_def_tmp1 = explode(';', $tab_styles[$i]['style_def']);
			for ($j=0; $j < count($style_def_tmp1); $j++) {
				$style_def_tmp2 = explode(':', $style_def_tmp1[$j]);
				$style_def[$style_def_tmp2[0]] = $style_def_tmp2[1];
			}
			$style_html[$tab_styles[$i]['style_name']] = $style_def;
		}

		for ($i=0; $i < count($data_range); $i++) {

			// Creation du rectangle représentant une couleur de la légende
			
			$style = $style_html[$data_range[$i]['style_name']];

			$opacity = $style['fill-opacity']*100;

			$stroke_color = str_replace('#', '', $style['stroke']);
			$stroke_color = imagecolorallocate($im, hexdec('0x' . $stroke_color{0} . $stroke_color{1}), hexdec('0x' . $stroke_color{2} . $stroke_color{3}), hexdec('0x' . $stroke_color{4} . $stroke_color{5}));

			$fill_color = str_replace('#', '', $style['fill']);
			$fill_color = imagecolorallocate($im, hexdec('0x' . $fill_color{0} . $fill_color{1}), hexdec('0x' . $fill_color{2} . $fill_color{3}), hexdec('0x' . $fill_color{4} . $fill_color{5}));

			$im_color = imagecreatetruecolor(22, 17);

			imagefilledrectangle($im_color, 0, 0, 22, 17, $stroke_color);
			imagefilledrectangle($im_color, 1, 1, 20, 15, $fill_color);

			imagecopymerge($im, $im_color, $start_x, 20+$cumulated_y, 0, 0, 22, 17, $opacity);
			imagedestroy($im_color);
			
			// Creation de la légende associé à la couleur

			$range_inf = $data_range[$i]['range_inf'];
			$range_sup = $data_range[$i]['range_sup'];

			if(is_numeric($range_inf)) $range_inf = round($range_inf, 2);
			if(is_numeric($range_sup)) $range_sup = round($range_sup, 2);

			if(($range_inf != "") && ($range_sup != "")) $sep_range = " - ";

			if($sep_range == "" && ($range_inf == 0 || $range_sup == 0)) $sep_range = " - ";

			$text = $range_inf."".$sep_range."".$range_sup;

			imagettftext($im, $font_size, 0, $start_x+30, 32.5+$cumulated_y, $black, $font, $text);

			$bbox = imagettfbboxextended($font_size, 0, $font, $text);

			$cumulated_y += 10+$bbox['y'];
		}

		return $im;
	}

	// Fonction provenant de php.net qui étend la fonction 'imagettfbbox'

	function imagettfbboxextended($size, $angle, $fontfile, $text) {
		
		/*this function extends imagettfbbox and includes within the returned array
		the actual text width and height as well as the x and y coordinates the
		text should be drawn from to render correctly.  This currently only works
		for an angle of zero and corrects the issue of hanging letters e.g. jpqg*/
		
		$bbox = imagettfbbox($size, $angle, $fontfile, $text);

		//calculate x baseline
		
		if($bbox[0] >= -1) 
		{
			$bbox['x'] = abs($bbox[0] + 1) * -1;
		} 
		else 
		{
			$bbox['x'] = abs($bbox[0] + 2);
		}

		//calculate actual text width
		
		$bbox['width'] = abs($bbox[2] - $bbox[0]);
		if($bbox[0] < -1) $bbox['width'] = abs($bbox[2]) + abs($bbox[0]) - 1;

		//calculate y baseline
		
		$bbox['y'] = abs($bbox[5] + 1);

		//calculate actual text height
		
		$bbox['height'] = abs($bbox[7]) - abs($bbox[1]);
		if($bbox[3] > 0) $bbox['height'] = abs($bbox[7] - $bbox[1]) - 1;

		return $bbox;
	}

?>
