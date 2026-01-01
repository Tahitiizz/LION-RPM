<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01

	- maj 23/03/2007, benoit : ajout de l'execution d'une fonction dans 'gis/gis.php' au chargement du SVG

*/
?>
<?php

class displayEntete
{

	// 08/11/2007 - Modif. benoit  remplacement du parametre '$gis_side' par '$gis_width' et '$gis_height'

	function displayEntete($view_box, $view_box_origine, $gis_width, $gis_height, $slide_duration, $tab_zooms, $current_zoom, $tab_styles)
	{
		$this->view_box			= $view_box;
		$this->view_box_origin	= $view_box_origine;
		
		//$this->gis_side			= $gis_side;
		$this->gis_width		= $gis_width;
		$this->gis_height		= $gis_height;

		$this->slide_duration	= $slide_duration;
		$this->tab_zoom			= $tab_zooms;
		$this->current_zoom		= $current_zoom;
		$this->tab_styles		= $tab_styles;

		$this->entete_svg  =  $this->constructStyles();

		// 23/03/2007 - Modif. benoit : ajout de l'execution d'une fonction dans 'gis/gis.php' au chargement du SVG

		$this->entete_svg .= '<svg id="root" x="0" y="0"  width="'.$this->gis_width.'" height="'.$this->gis_height.'" viewBox="'.implode(' ', $this->view_box).'" zoomAndPan="disable" onload="window.parent.doActionsOnLoad()">';

		$this->entete_svg .= '<animate  id="anim" attributeName="viewBox" begin="undefined" dur="'.$this->slide_duration.'s" values="" keyTimes="0; 1" keySplines="0 .75 .25 1" calcMode="spline" fill="freeze" />';

		$this->entete_svg .= '<g id="gisview" x="'.$this->view_box[0].'" y="'.$this->view_box[1].'" width="'.$this->view_box[2].'" height="'.$this->view_box[3].'">';

		$this->entete_svg .= '<rect id="fond_map" x="'.$this->view_box[0].'" y="'.$this->view_box[1].'" width="'.$this->view_box[2].'" height="'.$this->view_box[3].'" fill="white"/>';
	
	}

	// Fonction permettant de definir les styles css utilisés par les polygones svg dans une section du header SVG
        // 23/03/2012 BBX/MPR
        // BZ 26501 : optimisation de l'affichage du GIS
	function constructStyles()
	{
		$def_style	= '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 20001102//EN" "http://www.w3.org/TR/2000/CR-SVG-20001102/DTD/svg-20001102.dtd" [';
		$tab = array();
		$t = array_unique( $this->tab_styles );
		foreach ( $t as $key=>$value) 
		{
                        // Optimisation pour le chargement des fonds de carte
                        // Ajout d'un contrôle pour ne pas recréer deux fois le même style
			if( !in_array( $value['style_name'], $tab ) )
			{
				$def_style .= '<!ENTITY '.$value['style_name'].' "'.$value['style_def'].'">';
				$tab[] = $value['style_name'];
			}
		}

		$def_style	.= ']>';

		return $def_style;
	}
}

?>
