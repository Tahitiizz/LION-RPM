<?php
set_time_limit(600);

include_once dirname(__FILE__)."/../../php/environnement_liens.php";
//$transparence_color=true;

include_once(MOD_CHARTFROMXML . "class/graph.php");
include_once(MOD_CHARTFROMXML . "class/SimpleXMLElement_Extended.php");
include_once(MOD_CHARTFROMXML . "class/chartFromXML.php");


class GraphGenerator {
	/**
	 * Create a XML document with graph data
	 * Parameters:
	 *  $queryData - array : query data result
	 *  $parameters - object: parameters needed to customize graph
	 * 
	 * Return the document url on the server
	 * 
	 * 	$parameters sample:
	 *  {	
			name: 'query name',
			abscisse: 'data X axis',
			graphData: [
				type:		'type',
				color:		'color',
				position:	'position',
				legend:		'legend'
			]
		}
	 */
	public function displayGraph($queryData, $parameters) {
		 
		/*
			Création d'un fichier XML contenant les données affichées sur le graphe
			pour pouvoir faire l'export Excel depuis un caddy
		*/
		$dom = new DOMDocument();
		$dom->formatOutput = true;
		$dom_chart = $dom->createElement('chart');
		$dom->appendChild($dom_chart);
					
		/*
			1 : Propriétés du graphe dans le ficher XML
		*/
		$dom_pptes = $dom->createElement('properties');
		
		// > title
		$dom_tabtitle = $dom->createElement('tabtitle');
		
		// Compute the name: user name or "Graph result" by default
		$name = $parameters->name?$parameters->name:'Graph result';
		
		$dom_tabtitl_text = $dom->createElement('text', $name);
		$dom_tabtitle->appendChild($dom_tabtitl_text);
		$dom_pptes->appendChild($dom_tabtitle);
		
		// > Dimension  du graphe
		$dom_pptes->appendChild($dom->createElement('width', 880));
		$dom_pptes->appendChild($dom->createElement('height', 380));
		
		// > Position de la légende
		$dom_pptes->appendChild($dom->createElement('legend_position', 'top'));
		
		// Nom de l'ordonnée de gauche
		$dom_pptes->appendChild($dom->createElement('left_axis_label', $parameters->leftAxisLabel?$parameters->leftAxisLabel:''));
		
		// Nom de l'ordonnée de droite
		$dom_pptes->appendChild($dom->createElement('right_axis_label', $parameters->rightAxisLabel?$parameters->rightAxisLabel:''));
		
		// Type de graphe
		$dom_pptes->appendChild($dom->createElement('type', 'graph'));
		
		// Type slace
		$dom_pptes_scale = $dom->createElement('scale', 'textlin');
		$dom_pptes_scale->setAttribute("autoY", 1);
		$dom_pptes_scale->setAttribute("autoY2", 1);
		$dom_pptes->appendChild($dom_pptes_scale);
		
		$query_name = $parameters->name. date(" d-m-Y h:i:s");
		
		// > Graphe name
		$dom_graph_name = $dom->createElement('graph_name', $query_name.' (from query builder)');
		$dom_pptes->appendChild($dom_graph_name);
		
		$dom_chart->appendChild($dom_pptes);
				
		/*
			2 : L'abscisse du fichier XML
		*/
		$dom_xaxis = $dom->createElement('xaxis_labels');
		
		// Get X axis
		$abscisse = $parameters->abscisse?$parameters->abscisse:0;
		
        // 15/02/2011 NSE DE Query Builder : la légende n'est affichée que s'il y a moins de 70 résultats affichés
        // 13/04/2011 BBX Affichage d'un message lorsque la lagende n'est pas affichée BZ 21810
        $bottomMessage = '';		
        if(count($queryData[$abscisse])<70) {        	
            foreach ( $queryData[$abscisse] as $value ) {            
                    $dom_xaxis->appendChild($dom->createElement('label', $value));
            }
        }
        else
        {
            foreach ( $queryData[$abscisse] as $value ) {
                    $dom_xaxis->appendChild($dom->createElement('label', ''));
            }
            $bottomMessage = '<div class="infoBox">'.__T('U_QUERY_BUILDER_GRAPH_NO_LEGEND').'</div>';
        }
		$dom_chart->appendChild($dom_xaxis);
		
		/* There must be at least one graph parameter with position = left (graph component does'nt manage only one right axis*/
		$hasLeftAxis = false;
		
		/*
			3 : Les données du fichiers XML
		*/
		$dom_datas = $dom->createElement('datas');
		// Boucle sur les données affichées
		foreach ($parameters->graphData as $key => $data_displayed) {
			if ($data_displayed->type == 'no') {
				continue;				
			}
			
			/* There must be at least one graph parameter with position = left (graph component does'nt manage only one right axis*/
			if ($data_displayed->position == 'left') {
				$hasLeftAxis = true;
			}
			
			$dom_datas_data = $dom->createElement('data');
			$dom_datas_data->setAttribute("label", $data_displayed->legend);
			$dom_datas_data->setAttribute("line_design", 'none');
			$dom_datas_data->setAttribute("yaxis", $data_displayed->position);
			
			switch ($data_displayed->type) {
				case 'line': 
					$dom_datas_data->setAttribute("fill_color", $data_displayed->color.'@1');
					$dom_datas_data->setAttribute("stroke_color", $data_displayed->color);
					$dom_datas_data->setAttribute("type", 'line');
					break;
				case 'bar':
					// maj  02/12/2009 - MPR : Correction du bug 6068 - Ajout de transparence à 50% afin de pouvoir visualiser tous les raw/kpi à afficher 
					$dom_datas_data->setAttribute("fill_color", $data_displayed->color.'@0.5');
					$dom_datas_data->setAttribute("stroke_color", $data_displayed->color);
					$dom_datas_data->setAttribute("type", 'bar');
					break;
				case 'cumulated':
					// maj  02/12/2009 - MPR : Correction du bug 6068 - Ajout de transparence à 50% afin de pouvoir visualiser tous les raw/kpi à afficher 
					$dom_datas_data->setAttribute("fill_color", $data_displayed->color.'@0.5');
					$dom_datas_data->setAttribute("stroke_color", $data_displayed->color);
					$dom_datas_data->setAttribute("type", 'cumulatedbar');
					break;
			}
			
			// Boucles sur les valeurs de la donnée à afficher
		    // 15/02/2011 NSE DE Query Builder : on limite le nombre de résultats affichés
		    $cptvalue=0;			
			foreach ($queryData[$key] as $value ) {
		        // on n'affiche que 1000 valeurs
				if($cptvalue>get_sys_global_parameters('query_builder_nb_result_limit',1000)) {
					break;
				}				
				$dom_datas_data->appendChild($dom->createElement('value', $value));
		        $cptvalue++;
			}
			$dom_datas->appendChild($dom_datas_data);
		}
		$dom_chart->appendChild($dom_datas);
		
		/* There must be at least one graph parameter with position = left (graph component does'nt manage only one right axis*/
		if (!$hasLeftAxis) {
			throw new Exception('There must be at least one left axis.');
		}
		
		/*
			4 : Sauvegarde le fichier XML sur le serveur
		*/								
		$nom = uniqid('query_builder',true);		
		$xml_doc_url = REP_PHYSIQUE_NIVEAU_0.'png_file/'.$nom.'.xml';		
		$dom->save($xml_doc_url);
					
		// On crée l'objet en chargeant le fichier de données XML
		$my_gtm = new chartFromXML($xml_doc_url);
		
		// 15/02/2011 NSE DE Query Builder : pas de discrétisation, on veut afficher tous les éléments de l'axe X
		// initialisation à 1 de l'interval pour tous les afficher
		$my_gtm->setAbscisseInterval(1);
		
		// Modification des urls afin de stocker l'ensemble des fichiers (xml + png) dans le dossier "png_file" de l'application
		$my_gtm->setBaseUrl(NIVEAU_0.'/png_file/');
		$my_gtm->setBaseDir(REP_PHYSIQUE_NIVEAU_0.'png_file/');
		$my_gtm->setHTMLURL(NIVEAU_0);
		
		// on charge les valeurs par défaut (depuis un autre fichier XML)
		$my_gtm->loadDefaultXML(MOD_CHARTFROMXML . "class/chart_default.xml");
		
		echo $my_gtm->getHTML($nom);

		// 13/04/2011 BBX
		// Affichage d'un message lorsque la lagende n'est pas affichée
		// BZ 21810
		echo $bottomMessage;
/*		
		if (isset($nom)) {
			global $path_skin,$id_user;        
			?>
				<img src="<?=NIVEAU_0?>images/icones/caddy_icone_gf.gif" align="middle" border="0"  onMouseOver="popalt('Add to the  Cart');style.cursor='pointer';" onMouseOut="kill()" onClick="caddy_update('<?=NIVEAU_0?>','<?=$id_user?>','Builder report','graph','<?=$query_name?> (from builder report)','<?=$nom?>.png','Builder report graph','')">
			<?
		}
 */
	}							
		
}
	












?>
