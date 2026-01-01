<?php
/**
 * @cb50000@
 * 
 * 20/08/2009 - Copyright Astellia
 * 
 * Composant de base version cb_5.0.0.00	
 *
 *  20/08/2009 MPR : Correction du bug 11146 : Erreur Requête SQL Les cones n'étaient plus affichés
 *  16/02/2011 OJT : Ajout de la gestion des caractères spéciaux pour le X.M.L.
 *  01/07/2011 NSE bz 22699 : no style applied to cones
 *  01/07/2011 NSE 22865 : valeur du raw/kpi dans le cas des alarmes
 *	09/12/2011 ACS Mantis 837 DE HTTPS support
 *  10/10/2012 MMT DE GIS 3D only
*/
?>
<?php
/**
 * 
 * @cb40000@
 * 
 * 	14/11/2007 - Copyright Acurio
 * 
 * 	Composant de base version cb_4.0.0.00
 *
 *	Création de la classe KMLRender qui génère un fichier kml ou kmz ( fichier Google Earth )
 *	
 *	maj 24/06/2008 - maxime : Correction du bug BZ6965  - Création de la fonction get_alarm_description() qui affiche le détail du résultat de l'élément réseau pour l'alarme concernée
*	maj 17/07/2008 - maxime : Correction du bug B6799 : On ne fait plus appel à la fonction getNaLAbel()
*	maj 07/08/2008 - Maxime : Correction du bug B7150 - On affiche le label à la place de l'id sur le point d'info pour les na de niv > au niv minimum
 */
?>
<?php
	session_start();
	
	// include_once("REP_PHYSIQUE_NIVEAU_0/php/environnement_liens.php");
	// include_once(REP_PHYSIQUE_NIVEAU_0."php/database_connection.php");

	include_once(dirname(__FILE__)."/../../php/environnement_liens.php");
	include_once(REP_PHYSIQUE_NIVEAU_0."php/edw_function_family.php");

class KMLRender
{
	private $_gis_instance;
	private $_file_out;
	// private $_repertoire_physique_niveau0 = $_SESSION['repertoire_physique_niveau0'];
	private $_kml_file = "";
	
	private static $_END_OF_LINE = '';	// trouver le caractere fin de ligne adequat pour le fichier kml
	
	public function __construct($gis_instance){
		
		// On récupère l'instance du gis
		$this->setGisInstance($gis_instance);
		
		$this->setKMLFamily();
		
		// On définit le fichier qui va être créé
		$this->setFileOut();
		
		// On créé le contenu du fichier (style / légende / polygones)
		$this->setKmlFile();
		
		// On sauvegarde le fichier
		$this->saveKMLFile();
		
		$this->_gis_instance->traceActions("----------",$this->_file_out,"output");
		// On export le fichier
		$this->exportKMLFile($this->_file_out);
	}
	
	private function setGisInstance($gis_instance){
		// Prevoir ici les tests sur l'existence de la variable, que celle-ci est bien une instance de la classe gisExec, ...
		$this->_gis_instance = $gis_instance;
		
		if($this->_gis_instance->no_limit_vb == false){
		
			$this->_gis_instance->no_limit_vb = true;
		
			$this->_gis_instance->setReseauPolygones();
		
		}	
		
	}
	
	// On récupère la famille principale systématiquement pour obtenir les labels
	private function setKMLFamily(){
		
		$this->family = get_main_family();
	}
	
	
	private function setFileOut($raw_kpi=""){
		// Prevoir ici les tests sur l'existence du fichier, les permissions, ... (à gérer avec des exceptions) 
		
		if(!$this->_gis_instance->alarm_rawkpi){
						
			$raw_kpi = strtolower($this->_gis_instance->alarm_rawkpi[0])."_";
		}
		
		$_ta = $this->_gis_instance->ta_value;
		$_na = strtoupper($this->_gis_instance->na);
		
		$this->_file_out = REP_PHYSIQUE_NIVEAU_0."gis/gis_temp/gearth_".$_na."_".$raw_kpi.$_ta."_".date('His')."_".rand(1, 15).".kml";
		
	}
	
	public function getFileOut(){
	
		return $this->_file_out;
		
	}
	
	public function __destruct(){
		
	}
	
	private function get_sort_value($na_value){
		$id = array_keys($this->_gis_instance->tab_polygones['value'],$na_value);
		
		return $this->_gis_instance->polygons['sort_value'];
		
	}
	
	private function setKmlFile(){
		
		// Construction de l'entete
		$this->_kml_file  = '<?xml version="1.0" encoding="UTF-8"?>'.self::$_END_OF_LINE;
		$this->_kml_file .= '<kml xmlns="http://earth.google.com/kml/2.2">'.self::$_END_OF_LINE;
		$this->_kml_file .= '<Document>'.self::$_END_OF_LINE;
				
		// Construction des styles
		
		$this->_kml_file .= $this->setKmlStyle($this->_gis_instance->tab_styles);
		
		// On reformate le tableau des polygônes pour mieux exploiter ses données
        // 20/06/2011 NSE : merge Gis without polygons
		if($this->_gis_instance->displayMode == 1 )
            $this->init_tab_polygones($this->_gis_instance->tab_polygones[$this->_gis_instance->na]);
		
		// Construction des folders et de leurs contenus (placemarks)
		$this->_kml_file .= $this->setKmlFolder($this->_gis_instance->tab_layers, $this->_gis_instance->tab_polygones);
                
		
		// Construction de la légende
		$this->_kml_file .= $this->setkmlLegend();
		
		// Construction de la fin du fichier
		
		$this->_kml_file .= '</Document></kml>';
        
	}
	
	private function setKmlStyle($tab_styles){
			
		$styles = "";

		$styles.= '<Style id="Hide">'.self::$_END_OF_LINE;
		
		$styles.= '<ListStyle>'.self::$_END_OF_LINE;
		$styles.= '	<listItemType>checkHideChildren</listItemType>'.self::$_END_OF_LINE;
		$styles.= '</ListStyle>'.self::$_END_OF_LINE;
		
		$styles.= '</Style>'.self::$_END_OF_LINE;
		
		foreach ($tab_styles as $key=>$value) {
						
			$style_def = explode(";", $value['style_def']);
			
			$style_values = array();
			
			for($i=0;$i<count($style_def);$i++){
				$style_tmp = explode(":", $style_def[$i]);
				$style_values[$style_tmp[0]] = $style_tmp[1];
			}
			
			$stroke = str_split(substr($style_values['stroke'], 1), 2);
			$stroke_color = $stroke[2].$stroke[1].$stroke[0];
			
			$fill = str_split(substr($style_values['fill'], 1), 2);
			$fill_color = $fill[2].$fill[1].$fill[0];
			
			// On définit le style de chaque polygone
			$styles .= '<Style id="'.$value['style_name'].'">'.self::$_END_OF_LINE;
			
			$styles .= '<LineStyle>'.self::$_END_OF_LINE;
			$styles .= '<width>1</width>'.self::$_END_OF_LINE;
			$styles .= '<color>ffffffff</color>'.self::$_END_OF_LINE;
			$styles .= '</LineStyle>'.self::$_END_OF_LINE;
			
			// 09/12/2011 ACS Mantis 837 DE HTTPS support
			if($this->_gis_instance->na !== $this->_gis_instance->na_base){
				$styles .= '<IconStyle>'.self::$_END_OF_LINE;
				$styles .= '<scale>1.0</scale>'.self::$_END_OF_LINE;
				$styles .= '<Icon>'.self::$_END_OF_LINE;
				$styles .= '<href>'.ProductModel::getCompleteUrlForMasterGui('gis/gis_icons/point_info.jpg').'</href>'.self::$_END_OF_LINE;
				$styles .= '</Icon><scale>0.4</scale>'.self::$_END_OF_LINE;
				$styles .= '</IconStyle>'.self::$_END_OF_LINE;
			}
			
			
			$styles .= '<PolyStyle>'.self::$_END_OF_LINE;
			$styles .= '<color>7f'.$fill_color.'</color>'.self::$_END_OF_LINE;
			$styles .= '</PolyStyle>'.self::$_END_OF_LINE;
			$styles .= '</Style>'.self::$_END_OF_LINE;
			
			
			// On définit le style des points placés au centre du polygone pour les niveaux d'agrégation réseau > au niveau minimum
			if($this->_gis_instance->na !== $this->_gis_instance->na_base ){
				$styles .= '<Style id="'.$value['style_name'].'_center">'.self::$_END_OF_LINE;
				
				$styles .= '<LineStyle>'.self::$_END_OF_LINE;
				$styles .= '<width>1</width>'.self::$_END_OF_LINE;
				$styles .= '<color>ffffffff</color>'.self::$_END_OF_LINE;
				$styles .= '</LineStyle>'.self::$_END_OF_LINE;

				$styles .= '<PolyStyle>'.self::$_END_OF_LINE;
				$styles .= '<color>7f'.$fill_color.'</color>'.self::$_END_OF_LINE;
				$styles .= '</PolyStyle>'.self::$_END_OF_LINE;
				
				$styles .= '</Style>'.self::$_END_OF_LINE;
			}
            // 20/06/2011 NSE : merge Gis without polygons
			// 09/12/2011 ACS Mantis 837 DE HTTPS support
            else
            {
                $styles .= '<Style id="style_cone_'.$value['style_name'].'">'.self::$_END_OF_LINE;

                $styles .= '<LineStyle>'.self::$_END_OF_LINE;
                $styles .= '<width>3</width>'.self::$_END_OF_LINE;
                $styles .= '<color>7f'.$fill_color.'</color>'.self::$_END_OF_LINE;
                $styles .= '</LineStyle>'.self::$_END_OF_LINE;

                $styles .= '<IconStyle>'.self::$_END_OF_LINE;
                $styles .= '<scale>1.0</scale>'.self::$_END_OF_LINE;
                $styles .= ' <Icon>'.self::$_END_OF_LINE;
                $styles .= '<href>'.ProductModel::getCompleteUrlForMasterGui('gis/gis_icons/pts_cone.png').'</href>'.self::$_END_OF_LINE;
                $styles .= '</Icon>'.self::$_END_OF_LINE;
                $styles .= '</IconStyle>'.self::$_END_OF_LINE;

                $styles .= '<PolyStyle>'.self::$_END_OF_LINE;
                $styles .= '<color>7f'.$fill_color.'</color>'.self::$_END_OF_LINE;
                $styles .= '</PolyStyle>'.self::$_END_OF_LINE;
                $styles .= '</Style>'.self::$_END_OF_LINE;

        	}
		
		}

		// On récupère l'icône pour les points d'information regroupant plusieurs élements réseau
		// 09/12/2011 ACS Mantis 837 DE HTTPS support
        $styles .= '<Style id="globeIcon">'.self::$_END_OF_LINE;

        $styles .= '<IconStyle>'.self::$_END_OF_LINE;
        $styles .= '<color>efffffff</color>'.self::$_END_OF_LINE;
        $styles .= '<Icon><href>'.ProductModel::getCompleteUrlForMasterGui('gis/gis_icons/point_info.jpg').'</href></Icon>'.self::$_END_OF_LINE;

        $styles .= '<scale>0.4</scale></IconStyle>'.self::$_END_OF_LINE;

        $styles .= '<LineStyle>'.self::$_END_OF_LINE;
        $styles .= '<width>1</width>'.self::$_END_OF_LINE;
        $styles .= '</LineStyle>'.self::$_END_OF_LINE;

        $styles .= '</Style>'.self::$_END_OF_LINE;
		
		return $styles;
	}
	
	private function setKmlFolder($tab_layers, $tab_polygones){
		
		$folders = "";		
		
		foreach ($tab_layers as $id=>$content) {
			
			$labels = getNaLabelList("na",$this->_gis_instance->family, $this->_gis_instance->id_prod );
			$labels = $labels[$this->_gis_instance->family];
			
			$this->infos_na_center = "";
            // maj 16/06/2011 - MPR GIS without Polygons : Gestion des deux modes d'
            $nbPolygones = ($this->_gis_instance->displayMode == 0 ) ? $tab_polygones['cone'] : count($tab_polygones[$id]);
            if ( $nbPolygones > 0  and $content['type'] != 'geo'){

                    if($id !== 'cone' && $this->_gis_instance->displayMode == 1){
					
					$_label = array_keys($labels, $id);
					$id_display = ($_label !== false) ? $labels[$id] : $id;
					$folders .= '<Folder>'.self::$_END_OF_LINE.'<name>'.$this->formatXML( $id_display ).'</name>'.self::$_END_OF_LINE;
					
					$folders .= '<Folder>'.self::$_END_OF_LINE.'<name>Voronoi Polygon</name>'.self::$_END_OF_LINE;
					if(!$this->_gis_instance->debug){
						$folders .= '<styleUrl>#Hide</styleUrl>'.self::$_END_OF_LINE;
					}
					$folders .= $this->setKmlPlacemark($tab_polygones[$id]);
					$folders .= '</Folder>'.self::$_END_OF_LINE;
					
					// Construction des points d'information
					if($this->_gis_instance->na_base == $this->_gis_instance->na){
						$folders .= $this->setKmlInfosPoint($this->_gis_instance->na);
					}
					else{
					//On ajoute une icone dans le centre du polygône
						$folders .= '<Folder>'.self::$_END_OF_LINE.'<name>Network Elements</name>'.self::$_END_OF_LINE;
						$folders .= '<styleUrl>#Hide</styleUrl>'.self::$_END_OF_LINE;
						$folders .= $this->infos_na_center;
						$folders .= '</Folder>'.self::$_END_OF_LINE;
					}
					$folders .= '</Folder>'.self::$_END_OF_LINE;
				}
                // 20/06/2011 NSE : merge Gis without polygons
                elseif( $this->_gis_instance->displayMode == 0 && $id == 'cone' )
                {
                    $folders .= $this->setKmlInfosPoint($this->_gis_instance->na);
                }
            }
        }
		
		return $folders;
	}
	
	private function init_tab_polygones($polygones){
	
	
		foreach($polygones as $poly){
			
			// maj 17/07/2008 - maxime : Correction du bug B6799 : On ne fait plus appel à la fonction getNaLAbel()
			$this->na_value_at_coordinate[$poly['to_kml']]['value'][] = $poly['value'];
			
			$this->na_value_at_coordinate[$poly['to_kml']]['sort_value'][] = $poly['sort_value'];
			// maj 10/06/2008 - maxime : On récupère la valeur de l'élément réseau 3ème axe
			$this->na_value_at_coordinate[$poly['to_kml']]['value_axe3'][] =  getNaLabel( $poly['value_axe3'],$this->_gis_instance->na_axe3,$this->family, $this->_gis_instance->id_prod );
		
		}
				
	}
	
	private function setKmlPlacemark($polygones,$cone=""){
		
		$placemark = "";
		$nb_polygones = count($polygones);

		for ($i=0;$i<$nb_polygones;$i++){
			
		
			if( $cone == "" ){
								
				$poly_kml = $this->txtToKml($polygones[$i]['to_kml'],$polygones[$i]['value']); // Récupère toutes les coordonées du polygone ou multipolygone
				$nb_poly = count($poly_kml);
				$_family = get_main_family();
				
				// On construit les placemarks avec toutes les coordonées à ajouter
				for ($j=0;$j<$nb_poly;$j++){
						// echo "yeeeeeeeeee";
					
					$lookat = "";
					
					$placemark .= '<Placemark>'.self::$_END_OF_LINE;
					$placemark .= '<styleUrl>'.$polygones[$i]['style'].'</styleUrl>'.self::$_END_OF_LINE;
					
					// maj 17/07/2008 - maxime : Correction du bug B6799 : On ne fait plus appel à la fonction getNaLAbel()
					
					$_label = getNaLabel( $polygones[$i]['value'],$this->_gis_instance->na,$this->family, $this->_gis_instance->id_prod );
					if( $_label !== '' and  $_label !== null ){
						$value =  getNaLabel( $polygones[$i]['value'],$this->_gis_instance->na,$this->family, $this->_gis_instance->id_prod );
					} else {
						$value = $polygones[$i]['value'];
					}
                    
					if( $this->_gis_instance->na_value_axe3 != "" ){
						$value.= "[".getNaLabel( $this->_gis_instance->na_value_axe3, $this->_gis_instance->na_axe3,$this->family, $this->_gis_instance->id_prod )."]";
					}
					$name = (count($this->na_value_at_coordinate[$poly_kml[$j]])>1) ? "" : $value;
					
					if($this->_gis_instance->alarm_rawkpi){
						$name = "";
					}
					$placemark .= '<name>'.$this->formatXML( $name ).'</name>'.self::$_END_OF_LINE;
					
					// maj 17/07/2008 - maxime : Correction du bug B6799 : On ne fait plus appel à la fonction getNaLAbel()
                                        $placemark .= '<description>'.
                                                          $this->polygoneDescription($polygones[$i]['value'],$polygones[$i]['sort_value'],$polygones[$i]['sort'],$polygones[$i]['to_kml'])
                                                     .'</description>'.self::$_END_OF_LINE;
					$placemark .= '<Snippet maxLines="0"></Snippet>'.self::$_END_OF_LINE; // On cache les infos dans la liste d'infos à gauche dans google earth				
					
					$placemark .= '<Polygon>'.self::$_END_OF_LINE;
					$placemark .= '<tessellate>1</tessellate>'.self::$_END_OF_LINE;
					$placemark .= '<extrude>1</extrude>'.self::$_END_OF_LINE;
					$placemark .= '<outerBoundaryIs><LinearRing><coordinates>'.$poly_kml[$j].'</coordinates></LinearRing></outerBoundaryIs>'.self::$_END_OF_LINE;
					$placemark .= '</Polygon>'.self::$_END_OF_LINE;
					

					// On récupère le centre pour les polygones de niveau > au niveau minimum
					$placemark .= '</Placemark>'.self::$_END_OF_LINE;
				
					
					// On récupère le centre pour les polygones de niveau > au niveau minimum
					$coordCenterPolygon = (!(strpos($polygones[$i]['to_kml'], "MULTIPOLYGON") === false) ) ?  $this->txtToKml($this->centerPolygon[$polygones[$i]['value']][$j],$polygones[$i]['value']) : $this->txtToKml($this->centerPolygon[$polygones[$i]['value']][0], $polygones[$i]['value'] );
					
					if( count($coordCenterPolygon)>0){
						$this->infos_na_center .= '<Placemark>'.self::$_END_OF_LINE;
						$this->infos_na_center .= '<styleUrl>#globeIcon</styleUrl>'.self::$_END_OF_LINE;
						
						// 07/08/2008 - Maxime : Correction du bug 7150 - On affiche le label à la place de l'id sur le point d'info pour les na de niv > au niv minimum
						$_label = getNaLabel( $polygones[$i]['value'],$this->_gis_instance->na,$this->family, $this->_gis_instance->id_prod );
						if( $_label !== '' and  $_label !== null ){
							$value =  getNaLabel( $polygones[$i]['value'],$this->_gis_instance->na,$this->family, $this->_gis_instance->id_prod );
						}else {
							$value = $polygones[$i]['value'];
						}
							
						$name = (count($this->na_value_at_coordinate[$poly_kml[$j]])>1) ? "" : $value;
						
						if($description !== ""){
							
							$desc = explode("</b>",$description);
							$this->_gis_instance->traceActions("***",$desc[1],"description");
							$this->_gis_instance->traceActions("***",$desc[0],"description2");
							
							$aff_graphe = ($this->_gis_instance->na !== $this->_gis_instance->na_base) ? $desc[0]."{$this->_gis_instance->na}" : $desc[0]."]]>";
                            //10/10/2012 MMT DE GIS 3D only correction bug sur NA > Na min => formatage XML erroné
							$aff = ( $this->_gis_instance->alarm_rawkpi ) ? "<![CDATA[".$this->formatXML($desc[1])."]]>" : $this->formatXML($aff_graphe);
			
						}else{
						
							$aff = 'No Results';
							
						}
                        //10/10/2012 MMT DE GIS 3D only correction bug sur NA > Na min => formatage XML erroné
						$this->infos_na_center .= '<description>'. $aff .'</description>'.self::$_END_OF_LINE;
						
						
						
						$this->infos_na_center .= '<name>'.$this->formatXML( $name ).'</name>'.self::$_END_OF_LINE;
						$this->infos_na_center .= $lookat;
						$this->infos_na_center .= '<Model id="model_'.$j.'"><orientation><heading>0</heading><tilt>90</tilt><roll>0</roll></orientation></Model>'.self::$_END_OF_LINE;
						$this->infos_na_center .= '<Point><coordinates>'.$coordCenterPolygon.'</coordinates></Point>'.self::$_END_OF_LINE;
						$this->infos_na_center .= '</Placemark>'.self::$_END_OF_LINE;			
					}
					
				}
			}			
		}
		
		return $placemark;
	}
	
	// maj 14:26 24/06/2008 - maxime : Fonction qui affiche le détail du résultat de l'élément réseau pour l'alarme concernée
	private function get_alarm_description($lst_na_value,$na){
		
		$trigger = array_keys($this->_gis_instance->alarm_rawkpi);
				
		if($trigger[1]!==null and $trigger[1] !==""){
			$condition_trigger = " AND trigger = '".$trigger[1]."'";
			$trigger_label = $this->_gis_instance->alarm_rawkpi[$trigger[1]];
			
		}else{
			$condition_trigger = " AND trigger = '".$trigger[0]."'";
			$trigger_label = $this->_gis_instance->alarm_rawkpi[$trigger[0]];

		}

		if ($na != $this->_gis_instance->na) {
			$id_data_type = $this->_gis_instance->updateIdDataType($na);
		}
		else
		{
			$id_data_type = $this->_gis_instance->id_data_type;
		}

	// raitement du cas de l'axe3 dans la requete de jointure d'infos sur les alarmes
	$column_axe3 = "";
		if (!$this->_gis_instance->axe3) {
		
			$select_join			= "SELECT DISTINCT na_value,critical_level, value, trigger, field_type";
			$condition_join			= "";
			// $column_for_axe3		= "";
		}
		else 
		{
			
			$select_join = "SELECT DISTINCT na_value AS na_value,critical_level, value, trigger, field_type, a3_value AS na_value_axe3";
			
			if ($this->_gis_instance->na_value_axe3 != "ALL") {
				$condition_join .= " AND a3_value = '".$this->_gis_instance->na_value_axe3."' AND a3 = '{$this->_gis_instance->na_axe3}'";
			}
			else 
			{
				$condition_join = "";
			}
	
			$column_for_axe3 = ", na_value_axe3";
		}
		
		$condition_trigger = "";
		
		
		$sql =	 " SELECT distinct e_obj.eor_id AS na_value,e_obj.eor_label as label,t_data.critical_level, t_data.value AS sort_value, trigger, field_type"
				// ." , (SELECT ".$na."_label FROM edw_object_1_ref WHERE ".$na."=e_obj.".$na." LIMIT 1)"
				.$column_for_axe3
				." FROM sys_gis_topology_voronoi AS t_gis, edw_object_ref AS e_obj"
				." LEFT JOIN"
				." ("
				." ".$select_join
				." FROM edw_alarm, edw_alarm_detail"
				//.",edw_object_ref_parameters"
				." WHERE na='".$na."' ".$condition_join
				." AND ta='".$this->_gis_instance->ta."' AND ta_value=".$this->_gis_instance->ta_value
				//." AND na_value = eorp_id "
				." AND id_alarm='".$id_data_type."' AND alarm_type='".$this->_gis_instance->mode."'"
				.$condition_trigger
				.$condition_axe3
				// ." AND na = '$na'"
				." AND edw_alarm.id_result = edw_alarm_detail.id_result"
				." ) AS t_data"
				." ON e_obj.eor_id = t_data.na_value"
				." WHERE t_gis.na='".$na."' AND t_gis.na_value = e_obj.eor_id AND t_gis.p_voronoi IS NOT NULL"
				
				."	AND  (e_obj.eor_id IN ('".$lst_na_value."') OR e_obj.eor_label IN ('".$lst_na_value."') )"
				."  AND e_obj.eor_obj_type = '".$na."'";
		
		$this->_gis_instance->traceActions("infos on_mouseover alarm", $sql, "requete");
		
		@$req = $this->_gis_instance->database_connection->getAll($sql, 0);
		
		if( count($req) ){
			
			$content = array();
			$i = 0;
			
			foreach ( $req as $row ) {
				
				// Stockage du label et du nom de la NA dans le tableau '$title'

				if ($row['label'] != "") {
				
					$title[$i] = $row['label']." (".$row['na_value'].")";
				}
				else
				{
					$title[$i] = $row['na_value'];
				}

				// Stockage des informations d'alarme de la NA
				
				if($row['sort_value'] != "") {
					if ($row['trigger'] != ""){
												
						$sort_value_tab = explode('.', $row['sort_value']);
						if (count($sort_value_tab)>1) {
							$sort_value = $sort_value_tab[0].".".ceil(substr($sort_value_tab[1], 0, 4));
						}
						else
						{
							$sort_value = $row['sort_value'];
						}
						
						// $file = str_replace("__","_$sort_value_",$this->file_out);
						$this->setFileOut( strtolower($row['trigger']) );

						// 24/07/2007 - Modif. benoit : dans le cas de l'axe3, on precise la valeur de la na 3eme axe concernée par les informations disponibles au survol

						if (isset($row['na_value_axe3']) && $row['na_value_axe3'] != "") {
							$axe3_value = " [".$row['na_value_axe3']."]";
						}
						else 
						{
							$axe3_value = "";
						}
						
						// 18/06/2008 - Modif. benoit : correction du bug 6829. On affiche le label du trigger en fonction du tableau '$this->alarm_rawkpi' et l'on supprime le ";" après chaque résultat car celui-ci était considéré lors de l'affichage comme un séparateur de champs 
						
						//$rawkpi_content = $trigger_label.$axe3_value." [".$row['trigger']."] = ".$sort_value.";";

						$rawkpi_content = $trigger_label.$axe3_value." [".$row['trigger']."] = ".$sort_value;
						
						if (strpos($content, $rawkpi_content) === false) {
							$content[$i]= $rawkpi_content;
							$critical_level[$i] = $row['critical_level'];
						}
					}
				}
				else
				{
						$content[$i] = 'No Results';
				}
				$i++;				
			
			}
			
			$c = implode('||',$content);
			$this->_gis_instance->traceActions("<>",$c,"tpye");
			
			$tab = "";
			
			foreach($content as $id=> $description){
				$tab.= "<b>".$title[$id]."</b><br>";
				$tab.= $description."<br/><br/>";
			}
			
			return $tab;
		}
		
	}
	
	// Récupère et affiche le détail de chaque polygone
	private function polygoneDescription($na_value,$sort_value,$sort,$coordinates){
		// Définir ici les informations à afficher lorsque l'on fait "Ctrl+click" sur le polygone. Se baser sur le fichier 'gis/gis_scripts/
		
		// Cas des alarmes - Les polygones sont superposés
		if( $this->_gis_instance->alarm_rawkpi and count($this->na_value_at_coordinate[$coordinates]['value'])>0){
				$this->_gis_instance->traceActions("**Coord*",implode(' / ',$this->na_value_at_coordinate[$coordinates]['value']),"VaR");
				$lst_na_value = implode("','",$this->na_value_at_coordinate[$coordinates]['value']);
				$description = $this->get_alarm_description($lst_na_value, $this->_gis_instance->na);
				
				if( $description !== "" ){
						$this->_gis_instance->traceActions("*****",$alarm_result,"var");
						$infos .= "<![CDATA[ ".$this->formatXML( $description )." ]]>";
					
				}else{
					$infos .= ($sort_value !== "" and $sort_value !== null) ? "<![CDATA[ $sort = $sort_value ]]>" : "<![CDATA[ No results ]]>";
				}
		// Cas des dashboards ou du GIS - Les polygones sont superposés
		}elseif(count($this->na_value_at_coordinate[$coordinates]['value']) > 1){
		
				foreach( $this->na_value_at_coordinate[$coordinates]['value'] as $id=>$value_tmp ){
				
					$_label = getNaLabel( $value_tmp,$this->_gis_instance->na,$this->family, $this->_gis_instance->id_prod );
					// maj 07/08/2008 - Maxime : Correction du bug B7150 - On affiche le label à la place de l'id de l'élément réseau
					if( $_label !== '' and  $_label !== null ){
						$value =  getNaLabel( $value_tmp,$this->_gis_instance->na,$this->family, $this->_gis_instance->id_prod );
					}else {
						$value = $value_tmp;
					}
					$sort_value = $this->na_value_at_coordinate[$coordinates]['sort_value'][$id];
					$value_axe3 = $this->na_value_at_coordinate[$coordinates]['value_axe3'][$id];
					
					if($this->_gis_instance->axe3){
						$infos.= ( $sort_value !== "" and $sort_value !== null)  ? "<![CDATA[ <b>$value [ $value_axe3 ]</b><br/>$sort = ".$sort_value."<br/> ]]>" : "<![CDATA[ <b>$value [ $value_axe3 ]</b><br/>No Results<br/> ]]>";
					}else{
						$infos.= ( $sort_value !== "" and $sort_value !== null)  ? "<![CDATA[ <b>$value</b><br/>$sort = ".$sort_value."<br/> ]]>" : "<![CDATA[ <b>$value</b><br/>No Results<br/> ]]>";
					}
				}
		}else{ 
			$infos .= ($sort_value !== "" and $sort_value !== null) ? "<![CDATA[$sort = $sort_value]]>" : "<![CDATA[No results]]>";
		}
		
		return $infos;
		
	}

	/**
	* Fonction définit tous les points d'informations présentant les éléments réseau
	* @param string 
	*/
	private function setKmlInfosPoint($na){

		$description = "";
		// Affichage des points d'informpations sur les élements réseau de niveau minimum
		
			// Point d'informations sur chaque élément réseau
			// maj 20/05/2009 - MPR : Modification de la requête
			// $query = "SELECT distinct on (longitude,latitude,{$na},{$na}_label)  
						  // eor_id as {$na},eor_label as {$na}_label,eorp_longitude as longitude,eorp_x as x,
						  // eorp_y as y,eorp_latitude as latitude,eorp_azimuth as azimuth
				  // FROM edw_object_ref e, edw_object_ref_parameters p
				  // WHERE s.na='$na' AND longitude is not null AND s.na_value = e.$na 
				  // ORDER BY longitude,latitude,".$na."_label";
				 
			// 20/08/2009 MPR : Correction du bug 11146 : Erreur Requête SQL Les cones n'étaient plus affichés
			
            //10/10/2012 MMT DE GIS 3D only filtrage de la légende uniquement sur les éléments listé si restriction
            $na_values = $this->_gis_instance->getNeListRestriction();
            
            // 28/01/2013 BBX
            // DE GIS Filter : Récupération de la sélection
            // Gérer cas vide
            if(!empty($na_values)){
                $neFilterList = gisExec::getNeListFromSelecteur($this->_gis_instance->na_min, $this->_gis_instance->id_prod);
                if(!empty($neFilterList))
                    $na_values = array_intersect($neFilterList, $na_values);
            }
            else {
                $na_values = gisExec::getNeListFromSelecteur($this->_gis_instance->na_min, $this->_gis_instance->id_prod);
            }
            
            $neListRestrictionSQL = "";
            if(!empty($na_values)){
                $neListRestrictionSQL = "AND e.eor_id in ('".implode("','", $na_values)."')";
            }
            
			$query = "SELECT distinct  
					eor_label as {$na}_label, eor_id as {$na},eorp_longitude as longitude,eorp_x as x,
					eorp_y as y,eorp_latitude as latitude,eorp_azimuth as azimuth 
			  FROM edw_object_ref e, edw_object_ref_parameters p, sys_gis_topology_voronoi s
			  WHERE eorp_longitude is not null
				  AND eorp_latitude is not null 
				  AND s.na = '{$na}'
				  AND s.na_value = eorp_id
				  AND p_voronoi IS NOT NULL
				  AND eorp_azimuth is not null  
			          AND eorp_id = eor_id
				  AND eor_obj_type = '{$na}'
                  $neListRestrictionSQL
			  ORDER BY longitude,latitude,{$na}_label";

                        // 20/06/2011 NSE : merge Gis without polygons
			$res = $this->_gis_instance->database_connection->execute($query);
			$this->_gis_instance->TraceActions("setKmlInfosPoint",$query,"Qquery");
			
			$tab = array();
			
			$old_longitude = "";
			$old_latitude = "";
			$old_azimuth = "";
			
			$cpt = 0;
			
			$bts = get_sys_global_parameters('label_bts_gearth','',$this->_gis_instance->id_prod);
			
			$description = '<Folder>'.self::$_END_OF_LINE.'<name>'.$this->formatXML( $bts ).'</name>'.self::$_END_OF_LINE;
			$description .="<styleUrl>#Hide</styleUrl>".self::$_END_OF_LINE;
			
			// 20/06/2011 NSE : merge Gis without polygons
			while( $row = $this->_gis_instance->database_connection->getQueryResults($res,1) )
            {
				$new_longitude = $row['longitude'];
				$new_latitude = $row['latitude'];
				$new_azimuth = $row['azimuth'];
				
				// Si les coordonnées longitude et latitude sont les mêmes on insère l'élément réseau dans le même tableau
				if( !( $old_longitude == $new_longitude and $old_latitude == $new_latitude)){ 
					$cpt++;
			
				}
				
				$longitude[$cpt] = $new_longitude;
				$latitude[$cpt] = $new_latitude;
				$azimuth[$cpt][] = $row['azimuth'];
				// maj 20/05/2009 - MPR : On choisit soit le label soit l'id
				$tab[$cpt][] = ($row[$na."_label"] == "") ? $row[$na] : $row[$na."_label"]; 
				// 01/07/2011 NSE bz 22699 : no style applied to cones
                $tabId[$cpt][] = $row[$na];
				$old_longitude = $new_longitude;
				$old_latitude = $new_latitude;

			}
			
			$description_cone .= '<Folder>'.self::$_END_OF_LINE;
			$description_cone .= '<name>Network Elements</name>'.self::$_END_OF_LINE;
					
			// On intègre tous les éléments réseau
			foreach($tab as $n=>$elements ){
			
				$description .= '<Placemark>'.self::$_END_OF_LINE;
				$coordinates[$n] = $longitude[$n].",".$latitude[$n].",0";
				$name = ""; 
				
				$description .= "<name>".$this->formatXML( $name )."</name>".self::$_END_OF_LINE;
				$aff = ( !$this->_gis_instance->debug ) ? "" : $longitude[$n].'/ latitude : '.$latitude[$n].'/ azimuth:'.$azimuth[$n]."<br/>";
				$description .= '<description>'.self::$_END_OF_LINE;
				
				//On affiche la liste des éléments réseau présent sur le point
				foreach($elements as $element){
					$description .= $this->formatXML( $element )."<br/>".self::$_END_OF_LINE;
					
				}
				
				$description .="</description>".self::$_END_OF_LINE;
				
				// On change l'icône si plusieurs éléments réseau sont sur le même point
				$description .="<styleUrl>#globeIcon</styleUrl>".self::$_END_OF_LINE;
				
				// On intègre les coordonnées du point
				
				$description .="<Point>".self::$_END_OF_LINE;
				$description .="<coordinates>".$coordinates[$n]."</coordinates>".self::$_END_OF_LINE;
				$description .="</Point>".self::$_END_OF_LINE;
				$description .="</Placemark>".self::$_END_OF_LINE;			
				
				
				
				// On initialise les coordonnées des cônes
				
				foreach($elements as $k=>$element){
                    // $element = label de l'élément
                    // $id = code de l'élément
			
					// $label = getNaLabel($element,$this->_gis_instance->na,$this->_gis_instance->family); 
					// maj 17/07/2008 - maxime : Correction du bug B6799 : On ne fait plus appel à la fonction getNaLAbel()
					$label = $element;
                    // 20/06/2011 NSE : merge Gis without polygons
                    $id = $tabId[$n][$k];

					$coords = $this->setConePosition($element,$longitude[$n],$latitude[$n],$azimuth[$n][$k]);	
					// $this->_gis_instance->TraceActions("1 >>>>",$coords,"coord cone");
					
						
					$coords_tmp = str_replace(',64', '', $coords);					
					$coords_tmp = str_replace(',', '@', $coords_tmp);
					$coords_tmp = str_replace(' ', ',', $coords_tmp);
					$coords_tmp = str_replace('@', ' ', $coords_tmp);
					$coords_tmp = str_replace(',,', ',', $coords_tmp);
								
					
					$coords_tmp_center =  $this->setKmlCenterPolygon($coords_tmp);
					
					$_coordinates = $this->txtToKml($coords_tmp_center);
					
					// $this->_gis_instance->TraceActions("4 <<<<",$coords_tmp,"coord entrée cone");
                    // 20/06/2011 NSE : merge Gis without polygons
					$this->_gis_instance->TraceActions(" $element <=> $id",$this->_gis_instance->infosCone['style'][$id],"style cone");
					
					$description_cone .= '<Placemark>'.self::$_END_OF_LINE;
                    $description_cone .= '<styleUrl>style_cone_'.$this->_gis_instance->infosCone['style'][$id].'</styleUrl>'.self::$_END_OF_LINE;
					$description_cone .= '<name>'.$this->formatXML( $element ).'</name>'.self::$_END_OF_LINE;
			
                    $this->_gis_instance->TraceActions(" $element <=> $id",$this->_gis_instance->infosCone['value'][$id],"value cone");

                    // 20/06/2011 NSE GIS without Polygons : valeur du raw/kpi
                    // 01/07/2011 NSE 22865 : valeur du raw/kpi dans le cas des alarmes
                    if($this->_gis_instance->data_type == "alarm"){
                        $lst_na_value = $id;//implode("','",$this->na_value_at_coordinate[$coordinates]['value']);
                        // 22/01/2013 BZ 31487 problème avec caractère à dans le label de topo
                        $contentDescription = $this->formatXML($this->get_alarm_description($lst_na_value, $this->_gis_instance->na));
                    }
                    else
                        $contentDescription = ( $this->_gis_instance->infosCone['value'][$id] == '' ) ? "No Results" :  $this->_gis_instance->sort_value.' = '.$this->_gis_instance->infosCone['value'][$id];
                    $description_cone .= '<description>'.( empty($contentDescription) ? 'No Result' : $contentDescription).'</description>'.self::$_END_OF_LINE;
					$description_cone .= '<MultiGeometry><Polygon>'.self::$_END_OF_LINE;
					$description_cone .= '<tessellate>1</tessellate><extrude>1</extrude><altitudeMode>relativeToGround</altitudeMode>'.self::$_END_OF_LINE;
					$description_cone .= '<outerBoundaryIs><LinearRing><coordinates>'.$coords.'</coordinates></LinearRing></outerBoundaryIs>'.self::$_END_OF_LINE;
					$description_cone .= '</Polygon>'.self::$_END_OF_LINE;
					// $description_cone .= '<Point>'.self::$_END_OF_LINE;
					
					// $description_cone .= '</Placemark>'.self::$_END_OF_LINE;
					
					// $description_cone_label .= '<Placemark>'.self::$_END_OF_LINE;
					// $description_cone_label .= '<styleUrl>#label_cone</styleUrl>'.self::$_END_OF_LINE;
					// $description_cone_label .= '<name>'.$element.'</name>'.self::$_END_OF_LINE;
					// $description_cone_label .= '<description></description>'.self::$_END_OF_LINE;
					$description_cone .= '<Point><coordinates>'.$_coordinates.'</coordinates></Point>'.self::$_END_OF_LINE;
					$description_cone .= '</MultiGeometry>'.self::$_END_OF_LINE;
					$description_cone.= '</Placemark>'.self::$_END_OF_LINE;	
				}
				
			}
	
			if(!$this->_gis_instance->debug){
				$description_cone_label .= '<styleUrl>#Hide</styleUrl>'.self::$_END_OF_LINE;
				
			}
			
			$description .= '</Folder>'.self::$_END_OF_LINE;
			$description_cone .= '</Folder>'.self::$_END_OF_LINE;
			$description_cone_label .= '</Folder>'.self::$_END_OF_LINE;
			$description .= $description_cone;
			

		return $description;
	}
	
	
	private function get_angle_precision(){

		
		$query = "SELECT angle_de_precision_voronoi FROM sys_gis_config_global LIMIT 1";
		$res = $this->_gis_instance->database_connection->getAll($query);
		
		foreach($res as $row ){
			$angle_precision = $row['angle_de_precision_voronoi'];
		}
		
		if(empty($angle_precision ) ){
			$angle_precision = 15;
		}
		
		return $angle_precision;
		
	}
	
	// Function qui créer les cônes

	private function setConePosition($element,$longitude,$latitude,$azimuth){

		$angle_precision = $this->get_angle_precision();
		// On convertit l'azimuth en radian
		$angle_deg = $azimuth+$angle_precision; // Orientation du cône en degré
		
		// ----------------------------------------------------------------------------------------------------------
		// 					Orientation du cône 
		// ----------------------------------------------------------------------------------------------------------
		$angle = deg2rad($angle_deg); // Orientation du cône en radian
		
		$angle_2 = deg2rad( $angle_deg - 10); // Nécessaire pour récuperer le 2ème côté // On soustrait 10° pour l'angle du deuxième côté
		$angle_3 = deg2rad( $angle_deg + 10); // Nécessaire pour récuperer le 3ème côté // On ajoute 10° pour l'angle du deuxième côté
		
		// ----------------------------------------------------------------------------------------------------------
		// 					Base du cône 
		// ----------------------------------------------------------------------------------------------------------
		
		$x1 = $longitude;
		$y1 = $latitude;

		// On définit la longueur du cône
		$hyp = 0.002;
		
		$adj = $hyp*sin($angle); // côté adjacent - permet de calculer x
		$opp = $hyp*cos($angle); // côté opposé - Permet de calculer y	
		
		// ----------------------------------------------------------------------------------------------------------
		// 					Hauteur du cône 
		// ----------------------------------------------------------------------------------------------------------
		
		$x2 = $adj+ $x1; 
		$y2 = $opp+ $y1;
		
		$adj = $hyp * sin($angle_2); // côté adjacent - permet de calculer x
		$opp = $hyp * cos($angle_2); // côté opposé - Permet de calculer y
		
		// ----------------------------------------------------------------------------------------------------------
		// 					Deuxième côté du cône
		// ----------------------------------------------------------------------------------------------------------
		
		$x3 = $adj + $x1;
		$y3 = $opp + $y1;

		// ----------------------------------------------------------------------------------------------------------
		// 					Troisième côté
		// ----------------------------------------------------------------------------------------------------------
		
		$adj = $hyp * sin($angle_3); // côté adjacent - permet de calculer x
		$opp = $hyp * cos($angle_3); // côté opposé - Permet de calculer y		
		
		$x4 = $adj + $x1;
		$y4 = $opp + $y1;
		
		// ----------------------------------------------------------------------------------------------------------
		$altitude = 64;
		
		$coord[0] = $x1.",".$y1.",".$altitude; // Base du cône
		$coord[1] = $x3.",".$y3.",".$altitude; // Premier côté
		$coord[2] = $x2.",".$y2.",".$altitude; // Hauteur du cône
		$coord[3] = $x4.",".$y4.",".$altitude; // Deuxième côté
				

		// On construit le polygône
		$coordinates = $coord[0]." ".$coord[1]." ".$coord[2]."  ".$coord[3]." ".$coord[0];
		return $coordinates; 
	}
	
	/**
	* Fonction définit tous les points d'informations présentant les éléments réseau
	* @param string 
	*/
	private function setKmlCenterPolygon($coordinates=""){

		
		if($coordinates !== ""){
			// Affichage des points d'informations sur les élements réseau > au niveau minimum
			$query = "SELECT AsText(centroid(GeometryFromText( 'POLYGON((".$coordinates."))'))) as coordinates";
			
			// echo $query;
			// $this->_gis_instance->TraceActions("2 ***",$query,"query");
			@$res = $this->_gis_instance->database_connection->getAll($query, 0);
			if( count($res) ){
				foreach( $res as $row ){
					$replace = array("POINT(",")");
					
					$control = explode(" ",$row['coordinates']);
					
					$coordinates = str_replace($replace,"",$control);

					if(is_numeric($coordinates[0]) and is_numeric($coordinates[1])){
						 // echo $query."<hr>";
						return $row['coordinates'];

					}
					
				}
			}
		}

	}
	
	private function generateLegendImg($box, $sort_name, $font, $font_size, $database_connection, $table_name, $id_data_type, $data_type, $data_range, $tab_styles)
	{
		// Creation de l'image
		
		$im = imagecreatetruecolor($box[2], $box[3]);
		
		$this->_gis_instance->traceActions("*****",$im,"img");

		// Creation des couleurs utilisées dans l'image
		
		$white = imagecolorallocate($im, 255, 255, 255);
		$black = imagecolorallocate($im, 0, 0, 0);
		
		imagefilledrectangle($im, 0, 0, $box[2]-1, $box[3]-1, $white);	

		$start_x = $box[0];

		// Construction du titre

		if ($data_type == "alarm") {
			
			$sql = "SELECT alarm_name FROM ".$table_name." alarm WHERE alarm_id='".$id_data_type."'";
			$req = $this->_gis_instance->database_connection->getAll($sql);
			$this->_gis_instance->traceActions("**************", $sql, "query");
			$row = $req[0];
			$sort_name = $row['alarm_name'];
		}

		// titre
		imagettftext($im, $font_size, 0, $start_x, 20, $black, $font, $sort_name);

		$bbox = $this->imagettfbboxextended($font_size, 0, $font, $sort_name);
		
		$cumulated_y = $bbox['y'];

		// Construction du corps

		// Pour les graphes, on inclut le style defaut dans le tableau de data_ranges

		if ($data_type == "graph") {
			$range_default = array();

			$range_default[] = array('style_name'=>"style_voronoi_defaut", 'range_inf'=>"n/a", 'range_sup'=>"default style");
			// $range_default[] = array('style_name'=>"style_defaut_cone", 'range_inf'=>"n/a", 'range_sup'=>"default style (direction)");

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

			$opacity = 50;//$style['fill-opacity']*100;
			$this->_gis_instance->TraceActions("Transparence dans la légende",$opacity,"var");

			$stroke_color = str_replace('#', '', $style['stroke']);
			$stroke_color = imagecolorallocate($im, hexdec('0x' . 'ffff'), hexdec('0x' . 'ffff'), hexdec('0x' . 'ffff'));

			$fill_color = str_replace('#', '', $style['fill']);
			$fill_color = imagecolorallocate($im, hexdec('0x' . $fill_color{0} . $fill_color{1}), hexdec('0x' . $fill_color{2} . $fill_color{3}), hexdec('0x' . $fill_color{4} . $fill_color{5}));

			$im_color = imagecreatetruecolor(22, 17);

			imagefilledrectangle($im_color, 0, 0, 22, 17, $stroke_color);
			imagefilledrectangle($im_color, 1, 1, 20, 15, $fill_color);

			imagecopymerge($im, $im_color, $start_x, 20+$cumulated_y, 0, 0, 22, 17, $opacity);
			imagedestroy($im_color);
			
			$range_inf = $data_range[$i]['range_inf'];
			$range_sup = $data_range[$i]['range_sup'];

			if(is_numeric($range_inf)) {$range_inf = round($range_inf, 2);}
			if(is_numeric($range_sup)) {$range_sup = round($range_sup, 2);}

			if(($range_inf != "") && ($range_sup != "")) {$sep_range = " - ";}

			if($sep_range == "" && ($range_inf == 0 || $range_sup == 0)){ $sep_range = " - ";}
			
			
			$text = $range_inf."".$sep_range."".$range_sup;
			
			imagettftext($im, $font_size, 0, $start_x+30, 32.5+$cumulated_y, $black, $font, $text);

			$bbox = $this->imagettfbboxextended($font_size, 0, $font, $text);

			$cumulated_y += 10+$bbox['y'];
			
		}
		
		// On genere l'image qui sera affichée dans le GIS

		$file_name = "legend_".date('dmYHis').rand(5, 15).".png";		
		
		
		imagepng($im, REP_PHYSIQUE_NIVEAU_0."gis/gearth_legends/".$file_name);
		
		// 09/12/2011 ACS Mantis 837 DE HTTPS support
		$path_img = ProductModel::getCompleteUrlForMasterGui("gis/gearth_legends/".$file_name);
		
		return $path_img;
	}
	
	// On ajoute la légende dans le fichier
	private function setkmlLegend(){
		
		// Génération de la légende sous forme d'image
		// Correction du bug 
		// On fixe la hateur de l'img par rapport au nombre de data ranges
                // 75 correspond aux data range par défaut, nécessaire s'il n'y a pas de données
		$height = count( $this->_gis_instance->data_range_values ) * 20 + 75;
		
		$img = $this->generateLegendImg( array(5, 0, 200, $height), $this->_gis_instance->sort_name, '../fonts/arial.ttf', 9, $this->_gis_instance->database_connection, $this->_gis_instance->table_name, $this->_gis_instance->id_data_type, $this->_gis_instance->data_type, $this->_gis_instance->data_range_values, $this->_gis_instance->tab_styles);
		
	    $legend = '<Folder>'.self::$_END_OF_LINE;
		$legend .= '<styleUrl>#Hide</styleUrl>'.self::$_END_OF_LINE;
		$legend.= '<name>Legend</name>'.self::$_END_OF_LINE;
		$legend .= '<ScreenOverlay>'.self::$_END_OF_LINE;
		$legend .= '<Icon>'.$img.'</Icon>'.self::$_END_OF_LINE;
		$legend .= '<overlayXY x="0" y="1" xunits="fraction" yunits="fraction"/>'.self::$_END_OF_LINE;
		$legend .= '<screenXY x="0" y="1" xunits="fraction" yunits="fraction"/>'.self::$_END_OF_LINE;
		$legend .= '<rotationXY x="0" y="0" xunits="fraction" yunits="fraction"/>'.self::$_END_OF_LINE;
		$legend .= '<size x="0" y="0" xunits="fraction" yunits="fraction"/>'.self::$_END_OF_LINE;
		$legend .= '</ScreenOverlay>'.self::$_END_OF_LINE;
		$legend .= '</Folder>'.self::$_END_OF_LINE;
		
		return $legend;
	}
	
	/**
	* Function qui créer les cônes dans le fichier généré 
	* @param array
	* @return string
	*/
	private function setkmlCones( $tab_polygones ){
		
		foreach($tab_polygones as $key=>$cones){
					
			// Image du cône
			// 09/12/2011 ACS Mantis 837 DE HTTPS support
			$path_img = ProductModel::getCompleteUrlForMasterGui("gis/gis_icones/img_cone_1.png");
			
			$cones.= '<Placemark>'.self::$_END_OF_LINE;
			$cones.= '<name>'.$this->formatXML( "Cone $key" ).'</name>'.self::$_END_OF_LINE;
			$cones.= "<description><![CDATA[<b>List of referenced SAI</b><br /><br />".$this->formatXML( $this->coneDescription($cones['to_kml']) )."]]></description>".self::$_END_OF_LINE;
			$cones.= '<Style>'.self::$_END_OF_LINE;
			$cones.= '<IconStyle>'.self::$_END_OF_LINE;
			$cones.= '<scale>1</scale>'.self::$_END_OF_LINE;
			
			// Intégration de l'image
			$cones.= '<Icon>'.self::$_END_OF_LINE;
			$cones.= '<href>'.$path_img.'</href>'.self::$_END_OF_LINE;
			$cones.= '</Icon>'.self::$_END_OF_LINE;
			$cones.= '</IconStyle>'.self::$_END_OF_LINE;
			$cones.= '<LabelStyle>'.self::$_END_OF_LINE;
			$cones.= '<scale>1.06</scale>'.self::$_END_OF_LINE;
			$cones.= '</LabelStyle>'.self::$_END_OF_LINE;
			$cones.= '</Style>'.self::$_END_OF_LINE;
			$cones.= '<Point>'.self::$_END_OF_LINE;
			$cones.= '<coordinates>'.$this->txtToKml($cones['to_kml']).'</coordinates>'.self::$_END_OF_LINE;
			$cones.= '</Point>'.self::$_END_OF_LINE;
			$cones.= '</Placemark>'.self::$_END_OF_LINE;
		}
	}
	
	// On éclate les multipolygones
	private function txtToKml($txt,$na_value="", $to_kml= ""){

		$kml = array();
		
		$altitude = '100 '; 		// On ajoute 100 pour gérer la transparence des polygones
		
		if (!(strpos($txt, "MULTIPOLYGON") === false)) { // On définit les coordonnées d'un multi-polygone
						
			$multi_polygon	= str_replace(array('MULTIPOLYGON(((', ')))'), '', $txt);
			$polygons		= explode(')),((', $multi_polygon);
			
			if($na_value !== ""){
					$this->centerPolygon[$na_value] = array();
			
				// On récupère chaque polygones
				for ($i=0; $i < count($polygons); $i++) {
					
					$coords = $this->setKmlCenterPolygon($polygons[$i]); // On récupère le centre des polygônes
					if($coords != null and $coords != ""){
						$this->centerPolygon[$na_value][$i] = $coords;
					}
					$kml_tmp = str_replace(' ', '@', $polygons[$i]);
					$kml_tmp = str_replace(',', " ", $kml_tmp);
					$kml_tmp = str_replace('@', ',', $kml_tmp);
					$kml_tmp.= " ";

					$kml[] = $kml_tmp;
		
				}
			}
		}		
		elseif (!(strpos($txt, "POLYGON") === false)) { // On définit les coordonnées d'un polygone
			
			$kml_tmp = str_replace(array('POLYGON((', '))'), '', $txt);
			$kml_tmp = str_replace(' ', '@', $kml_tmp);
			$kml_tmp = str_replace(','," " , $kml_tmp);
			$kml_tmp = str_replace('@', ',', $kml_tmp);
			$tab = explode(",",$kml_tmp);
					
			$kml_tmp.= " ";

			$kml[] = $kml_tmp;
			$this->_gis_instance->traceActions("coordinates $na_value",$kml_tmp,"var");
			$this->na_value_at_coordinate[$kml_tmp][] = $na_value;
			
		}
		elseif(!(strpos($txt, "CONE") === false)){ // On définit les coordonnées d'un cône
			$kml_tmp = str_replace(array('CONE(', ')'), '', $txt);
			$tab = explode("@",$kml_tmp);
			$kml_tmp = $tab[1];
			$kml_tmp = str_replace(' ', '||', $kml_tmp);
			$kml[] = $kml_tmp;
			
		}
		elseif(!(strpos($txt, "POINT") === false)){ // On définit les coordonnées de l'image au centre du polygone
			
			$icone_width = 0.005; 	
			$kml_tmp = str_replace(array('POINT(', ')'), '', $txt);
			$kml_tmp = str_replace(' ',"," , $kml_tmp);

			$kml = $kml_tmp;

		}

		return $kml;		
	}
	
	
	private function saveKMLFile(){
	
		$handle = fopen($this->_file_out, 'w+');
		fwrite($handle, $this->_kml_file);
		fclose($handle);
		
        
		$file = str_replace(REP_PHYSIQUE_NIVEAU_0."gis/gis_temp/","", str_replace(".kml","",$this->_file_out));

		$cmd = "cd ".REP_PHYSIQUE_NIVEAU_0."gis/gis_temp/ ; zip $file.zip $file.kml;mv -f $file.zip $file.kmz; rm -f $file.kml";
		
		// Conversion du fichier kml en kmz // On zip le fichier puis on renomme l'extension en kmz
		if(!$this->_gis_instance->debug){
			
			$this->_gis_instance->TraceActions("conversion kml to kmz",$cmd ,"commande unix");
			exec($cmd);
			$this->_kml_file =  REP_PHYSIQUE_NIVEAU_0."gis/gis_temp/".$file.".kmz";
			$this->_file_out =  REP_PHYSIQUE_NIVEAU_0."gis/gis_temp/".$file.".kmz";
		}		
	}
	
	/**
	* Function qui export le fichier kml vers google earth
	* @access private
	* @param string $file : fichier kml exporté
	*/
	
	private function exportKMLFile($file){

		
		// On ouvre le fichier s'il contient des polygones
		if( count($this->_gis_instance->tab_polygones) >0){
		
			echo $file;
			
		} else {
				$na = ( isset($this->_gis_instance->na_value_look_at) ) ? $this->_gis_instance->na_value_look_at : 'ALL';
				$msg_erreur = __T('U_EXPORT_GEARTH_NOT_COORDINATES',$na);
				?>	<html>
						<head>
							<TITLE>Google Earth</TITLE>
							<LINK REL='stylesheet' HREF='<?=REP_PHYSIQUE_NIVEAU_0?>css/global_css.css' TYPE='text/css'>
						</head>
						<body>
							<div class='texteRouge'><?=$msg_erreur?></div>

					</body>
					</html>
				<?
		}
	}
	
	private function imagettfbboxextended($size, $angle, $fontfile, $text) {
		
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
		if($bbox[0] < -1){ $bbox['width'] = abs($bbox[2]) + abs($bbox[0]) - 1;}

		//calculate y baseline
		
		$bbox['y'] = abs($bbox[5] + 1);

		//calculate actual text height
		
		$bbox['height'] = abs($bbox[7]) - abs($bbox[1]);
		if($bbox[3] > 0) {$bbox['height'] = abs($bbox[7] - $bbox[1]) - 1;}

		return $bbox;
	}

    /**
     * Mise en forme du texte d'une balise XML (description ou name).
     * Certains caractères doivent être échapés pour rendre le XML compatible 
     * 
     * @since 5.0.4.17
     * @param string $str Chaîne de caractère à formater
     * @return string
     */
    private function formatXML( $str )
    {
        // 22/01/2013 BZ 31487 problème avec caractère à dans le label de topo
        return str_replace( "<", "&lt;", str_replace( "&", "&amp;", utf8_encode($str) ) );
    }
}