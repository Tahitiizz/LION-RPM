<?
/*
*	@cb31000@
*
*	09/10/2007 - Copyright Acurio
*
*	Composant de base version cb_3.1.0.00
*
	- maj 11/10/2007, benoit : on force désormait le display en mode raster

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

include 'displayEntete.php';
include 'displayBody.php';

class displayTotal
{
	// 08/11/2007 - Modif. benoit  remplacement du parametre '$gis_side' par '$gis_width' et '$gis_height'
	// 20/06/2011 NSE : merge Gis without polygons
	function displayTotal($view_box, $view_box_origine, $gis_width, $gis_height, $slide_duration, $tab_zooms, $current_zoom, $tab_styles, $tab_layers, $tab_polygones, $na, $raster, $displayMode)
	{
		$this->output_tmp = "test";
		// 20/06/2011 NSE : merge Gis without polygons
        $nbPolygons = ( $displayMode == 0 ) ? count($tab_polygones['cone']) : count( $tab_polygones[$na] ) ;
        if (count($view_box) > 0 and $nbPolygons > 0 ) {

			// 08/11/2007 - Modif. benoit  remplacement du parametre '$gis_side' par '$gis_width' et '$gis_height'

			$entete	= new displayEntete($view_box, $view_box_origine, $gis_width, $gis_height, $slide_duration, $tab_zooms, $current_zoom, $tab_styles);
			
			$body	= new displayBody($tab_layers, $tab_polygones, $tab_styles);

			// On récupère l'entête du fichier
			$this->output_tmp = $entete->entete_svg;
			
			// On récupère le contenu du fichier
			$this->output_tmp.= $body->map_svg;
						
			
			// On ajoute les balises qui finalisent le fichier
			$this->output_tmp.= "</g></svg>";
			 
			// exec("echo  '".$entete->entete_svg."' > /home/cb41000_iu40014_dev1/gis/gis_temp/entete.svg");
			// exec("echo  '".$body->map_svg."' > /home/cb41000_iu40014_dev1/gis/gis_temp/body.svg");
		
			// 10/10/2007 - Modif. benoit : on ne passe plus par la fonction 'generateRaster()' pour generer le raster (cette fonction formatait le raster au format d'un fichier SVG) mais directement par la fonction 'rasterize()'

			//if($raster) $this->output  = $this->generateRaster($entete, $view_box);

			$this->output  = $this->rasterize();

			// 26/10/2007 - Modif. benoit : definition d'une nouvelle variable indiquant le statut du display

			$this->status = "ok";
		}
		else
		{
			// 11/10/2007 - Modif. benoit : on ne passe plus par la fonction 'noResultDisplay()' pour generer le raster "no results" (cette fonction formatait le raster au format d'un fichier SVG) mais on indique directement le nom de l'image "no results"

			// 26/10/2007 - Modif. benoit : on n'indique plus directement le chemin vers l'image "no results" mais on en génère une dont les dimensions sont adaptées à la side du GIS
					
			$this->output = '';//'gis_icons/no_result_gis.png';

			// 26/10/2007 - Modif. benoit : definition d'une nouvelle variable indiquant le statut du display

			$this->status = "no_results";
		}
	}

	
	function getOutput(){
	
		return $this->output;
	}
	
	function getStatus(){
	
		return $this->status;
	}
	// Fonction permettant de vérifier qu'il existe bien des données pour les layers reseaux (au moins pour un)

	function isDataExists($tab_layers, $tab_polygones)
	{
		$data_exist = false;

		foreach ($tab_layers as $layer_id=>$layer_content) {
			if (($layer_content['type'] == "reseau") && (count($tab_polygones[$layer_id]) > 0)) {
				$data_exist = true;
			}
		}

		return $data_exist;
	}

	// Fonction de génération de l'image indiquant qu'il n'existe pas de resultats

	function generateNoResultsImg($gis_side)
	{
		$img_no		= imagecreatefrompng('../gis_icons/no_result_gis.png');
		$dest_img	= imagecreatetruecolor($gis_side, $gis_side);

		// On reajuste les dimensions de l'image "no results" en fonction de la side du GIS
		// Note : la side du GIS optimale pour l'affichage de l'image étant 400 pixels, 
		// on se base sur cette mesure pour ajuster les dimensions de l'image

		$img_no_width	= imagesx($img_no)*($gis_side/400);
		$img_no_height	= imagesy($img_no)*($gis_side/400);
		
		// On ajuste les coordonnées de l'image "no results" pour qu'elle apparaisse centrée dans la nouvelle image

		$img_no_x = ($gis_side-$img_no_width)/2;
		$img_no_y = ($gis_side-$img_no_height)/2;

		// On copie l'image "no results" dans l'image de destination

		imagecopy($dest_img, $img_no, $img_no_x, $img_no_y, 0, 0, $img_no_width, $img_no_height);

		// On genere l'image qui sera affichée dans le GIS

		$raster_name = "noresults_".date('dmYHis')."_".rand(5, 15).".png";

		imagepng($dest_img, "../gis_temp/".$raster_name);

		return "gis_temp/".$raster_name;
	}

	// 11/10/2007 - Modif. benoit : deprecated

	function generateRaster($entete, $view_box)
	{
		if ($view_box[2] > $view_box[3]) {
			$x		= $view_box[0];
			$y		= $view_box[1]-(($view_box[2]-$view_box[3])/2);
			$width	= $view_box[2];
			$height	= $view_box[2];
		}
		else
		{
			$x = $view_box[0]-(($view_box[3]-$view_box[2])/2);
			$y = $view_box[1];
			$width	= $view_box[3];
			$height	= $view_box[3];
		}

		$svg_raster  = $entete->entete_svg.'<image xlink:href="'.$this->rasterize().'" x="'.$x.'" y="'.$y.'" width="'.$width.'" height="'.$height.'"/></g></svg>';

		return $svg_raster;
	}

	function rasterize()
	{
		$raster_name = "raster_".date('dmYHis')."_".rand(5, 15);
		$raster = dirname(__FILE__)."/../gis_temp/".$raster_name;

		//exec("echo '{$this->output_tmp}' > {$raster}.svg");
		
		$handle = fopen($raster.'.svg', 'w+');
		// Correction du bug 13458 - Impossible de générer le png le fichier svg est incorrect
		$this->output_tmp = str_replace("--","-",$this->output_tmp);
		fwrite($handle, $this->output_tmp);
		fclose($handle);
		
		// 20/05/2010 NSE : relocalisation du module batik dans le CB + modification chemin JDK
		// $raster_cmd = "/opt/jdk1.5.0_02/bin/java -Djava.awt.headless=true -Xmx512m -jar /home/batik/batik-rasterizer.jar -bg 255.255.255.255 ".$raster.".svg";
		$raster_cmd = "/usr/java/jdk1.5.0_02/bin/java -Djava.awt.headless=true -Xmx512m -jar ".REP_PHYSIQUE_NIVEAU_0."modules/batik/batik-rasterizer.jar -bg 255.255.255.255 ".$raster.".svg";
		// 20/05/2010 NSE modif répertoire test
		//exec("echo '$raster_cmd' > ".REP_PHYSIQUE_NIVEAU_0."gis_temp/test_rasterize.svg");
		exec($raster_cmd);

		if(is_file($raster.".svg")) unlink($raster.".svg");

		return "gis_temp/".$raster_name.".png";
	}
}

?>
