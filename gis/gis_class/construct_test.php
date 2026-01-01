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

	class gisExec
	{
		function gisExec()
		{
			$this->view_box				= array("31655.392519531", "-259176.75703320498", "236906.57135547", "236906.57135547");
			$this->view_box_origine		= $this->view_box;
			$this->gis_side				= 400;
			$this->slide_duration		= 1.7;
			$this->tab_zooms			= array(1,2,4,8,16,32,64,128);
			$this->current_zoom			= 1;
			$this->tab_layers['msc']	= array('order'=>0, 'border'=>1, 'background'=>1, 'onmouseover'=>0);
			$this->tab_styles			= array('fill:#cccccc;stroke:#999999;fill-opacity:.3;stroke-opacity:0.5', 'stroke-opacity:1;stroke:#000000;fill-opacity:1;fill:#FFFF99', 'fill:#FF0000;stroke:#FF0000;fill-opacity:1;stroke-opacity:1', 'stroke-opacity:1;stroke:#00FF00;fill-opacity:1;fill:#00FF00');

			$this->squares_by_side = 5;

			$this->tab_polygones['msc'] = $this->drawSquare($this->squares_by_side);
		}

		function drawSquare($nb_squares_side)
		{
			$square_side = $this->view_box_origine[2]/$nb_squares_side;

			for ($i=0; $i < $nb_squares_side; $i++) {
				for ($j=0; $j < $nb_squares_side; $j++) {

					$x	= $this->view_box_origine[0]+$i*$square_side;
					$y	= $this->view_box_origine[1]+$j*$square_side;
					$x2	= $x+$square_side;
					$y2	= $y+$square_side;

					$style_info = ($i+$j)%2;

					$tab[] = array('path'=>'d="M '.$x.','.$y.' L '.$x2.','.$y.' L '.$x2.','.$y2.' L '.$x.','.$y2.' z"', 'style'=>$style_info, 'infos'=>$style_info);
				}
			}
			return $tab;
		}
	}

?>
