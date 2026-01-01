<?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
	- maj 26/03/2008, benoit : ajout d'un 'trim()' sur l'attribut de style car ceux définit pour les vecteurs géo comportent des espaces
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

class displayBody
{
	// Constructeur de la classe

	function __construct($tab_layers, $tab_polygones, $tab_styles)
	{
		$this->tab_layers		= $this->reorderLayers($tab_layers);
		
		$this->tab_polygones	= $tab_polygones;
		$this->tab_styles		= $this->changeTabStyle($tab_styles);

		$this->map_svg = $this->constructLayers();	// Contenu de la map au format SVG
		
	}

	function constructLayers()
	{

		$layer = "";

		foreach ($this->tab_layers as $id_layer=>$layer_content) {

			//if (count($this->tab_polygones[$id_layer]) > 0) {
				$layer.= '<g id="'.$id_layer.'"';			
				
				if ($layer_content['background'] == 0 && $layer_content['border'] == 0) {
					$layer.= ' display="none">';
				}
				else
				{
					$layer.= ' display="block">';
				}

				// Definition des polygones appartenant au layer

				$tab_polygones = $this->tab_polygones[$id_layer];

				for ($i=0; $i < count($tab_polygones); $i++) {

					$polygon = $tab_polygones[$i];

					// Definition du style
					$style = $style_ref = '&'.$polygon['style'].';';

					if (!($layer_content['background'] == 0 && $layer_content['border'] == 0)) {

						// exec("echo '{$polygon['style']} => {$this->tab_styles[$polygon['style']]}' >> /home/cb41000_iu40014_dev1/gis/gis_temp/go_styles.svg");
						// stroke:#00CCFF;stroke-opacity:.5;fill:#00CCFF;fill-opacity:.5;
						
						$polygon_style = explode(';', $this->tab_styles[$polygon['style']]);

						$current_style = array();

						if(count($polygon_style)>0){
							for ($j=0; $j < count($polygon_style); $j++) {
								$style_ppte = explode(':', $polygon_style[$j]);

								// 26/03/2008 - Modif. benoit : ajout d'un 'trim()' sur l'attribut de style car ceux définit pour les vecteurs géo comportent des espaces

								if (trim($style_ppte[0]) == "fill-opacity" && $layer_content['background'] == false){
									$style_ppte[1] = 0;
								}

								if (trim($style_ppte[0]) == "stroke-opacity" && $layer_content['border'] == false){
									$style_ppte[1] = 0;
								}

								$current_style[] = implode(':', $style_ppte);
							}
						}

						$style = implode(';', $current_style);
					}

					// Definition du path du polygone
					$poly_layer = '<path '.$polygon['path'].' style="'.$style.'" style_ref="'.$style_ref.'" ';
					
					$layer.= $poly_layer.'/>';
					
					// $test[] = $poly_layer." />\n";
					// $cmd = "echo '{$layer}' > /home/cb41000_iu40014_dev1/gis/gis_temp/poly_layer.svg";
					// exec($cmd);
				}
		
				// $test[] = '</g>';
				$layer.= '</g>';
				
		}
		
		// $cmd = "echo '{$layer}' > /home/cb41000_iu40014_dev1/gis/gis_temp/copy_layer.svg";
		// exec($cmd);
		// $this->test = $test;
		return $layer;
	}

	function reorderLayers($tab_layers)
	{
		$tab_reorder = array();

		foreach ($tab_layers as $id_layer=>$layer_content) {
			$tab_reorder[] = array($layer_content['order'], $id_layer, $layer_content);
		}
		sort($tab_reorder);

		$tab_layers = array();

		for ($i=0; $i < count($tab_reorder); $i++) {
			$tab_layers[$tab_reorder[$i][1]] = $tab_reorder[$i][2];
			
		}

		return $tab_layers;
	}

	function changeTabStyle($tab_styles)
	{
		$new_tab_styles = array();

		for ($i=0; $i < count($tab_styles); $i++) {
			$new_tab_styles[$tab_styles[$i]['style_name']] = $tab_styles[$i]['style_def'];
			exec("echo '{$tab_styles[$i]['style_name']} => {$tab_styles[$i]['style_def']}' >> /home/cb41000_iu40014_dev1/gis/gis_temp/new_styles.svg");

		}
		
		
		return $new_tab_styles;
	}
}

?>
