<?php
/**
 * 
 * 09/10/2012 ACS DE GIS 3D ONLY
 *
 * @cb51
 * 
 * 22/11/2011 NSE bz 24774 : ajout du produit en paramètre lors de l'appel de la méthode GisModel::getGisDisplayMode()
 * 10/10/2012 MMT DE GIS 3D ONLY
 * 16/11/2012 MMT bz 30276 ajout gestion top-worst pour DE GIS 3D only 
 */
?>
<?php
/**
* Ce fichier contient la classe qui construit l'instance du GIS
*/
/**
 * 
 * @cb41000@
 * 
 * 	20/05/2009 - Copyright Astellia
 * 
 * 	Composant de base version cb_4.1.0.00
 *
 *	maj 20/05/2009 - MPR : Utilisation du nouveau module de Topologie - Modification des requetes SQL qui recupere les informations topologiques
 *	maj 20/05/2009 - MPR : Gestion multi produit - Ajout du parametre id_prod et utilisation de la classe DataBaseConnection
 *	maj 27/07/2009 - MPR : Correction du bug 7821 : On cree les repertoires qui stockent les images
 */
/**
 * 
 * @cb40000@
 * 
 * 	14/11/2007 - Copyright Acurio
 * 
 * 	Composant de base version cb_4.0.0.00
 *
	- maj 08/11/2007, benoit : ajout de 2 nouvelles variables representant les dimensions de la fenetre GIS (on n'utilise plus                  '$this->gis_side')
	- maj 13/11/2007, benoit : le vue du GIS n'etant plus necessairement un carre, on ajuste la viewbox en fonction de la taille de la vue
	- maj 16/11/2007, benoit : on limite la sous-requete a un seul resultat car il arrive que plusieurs polygones "intersectent" avec les       coordonnees de la souris
	- maj 19/11/2007, benoit : ajout de 2 variables supplementaires stockant les dimensions initiales de la vue
	- maj 12/12/2007, benoit : '$this->na_base' vaut toujours la valeur de la na minimum determinee par la requete

	- maj 28/12/2007, benoit : redefinition de la fonction 'selectNextNa()' qui retournait toujours la prochaine na de la famille principale    ce qui provoquait des erreurs quand cette na n'appartenait pas a la famille courante (ex. famille pagrac où quand l'on descend de         network on avait le niveau rnc qui n'appartient pas a cette famille)
	- maj 28/12/2007, benoit : modification des fonctions deprecated en php5
	- maj 28/12/2007, benoit : la definition du zoom se fait par rapport a la largeur ou la hauteur maximale de la viewbox origine
	- maj 28/12/2007, benoit : lors de la creation des data ranges automatiques, si la difference entre la valeur max et la valeur min est de   0, le pas vaut egalement 0 (calcul du pas : (max_value-min_value)/nb_ranges). Dans ce cas, on fixe la valeur max a celle de l'unique      valeur et la valeur min a 0
	
	- maj 03/01/2008, benoit : reformulation de la condition pour definir les dimensions de la vbox

	- maj 04/01/2008, benoit : remplacement du srid -1 par celui stockee en base
	
	- maj 28/01/2008, maxime : export vers Google Earth -> Creation du fichier KML
	- maj 18/02/2008, maxime : Gestion des elements reseau superposes

	- maj 25/03/2008, benoit : lors de la recherche des layers geo, on n'affiche que ceux qui ont des vecteurs definis
	- maj 25/03/2008, benoit : reprise de la requete de definition des polygones geo où on inclut le champ 'type' et d'où l'on supprime le      'Intersects()' (ne fonctionne pas sur les serveurs RedHat)
	- maj 25/03/2008, benoit : correction du contenu du path dans le tableau '$this->tab_polygones'

	- maj 26/03/2008, benoit : modification de l'ajout de styles geo dans '$this->tab_styles'
	- maj 26/03/2008, benoit : remise en forme des informations affichees lors du survol d'un vecteur geo
	
	- maj 15/04/2008, maxime : L'extension du fichier est kmz au lieu de kml
	- maj 22/04/2008, maxime : ajout d'un parametre pour supprimer les limites de la vb ( necessaire pour l'export vers Google Earth) pour les   dashboards

	- maj 23/05/2008, maxime : ajout d'un parametre pour supprimer les limites de la vb ( necessaire pour l'export vers Google Earth) pour      les alarmes
	- maj 23/05/2008, maxime : Changement de la requete qui recupere les informations. On se base toujours sur le niveau d'agregation reseau    minimum pour recuperer les informations de la na en cours
 	
	- maj 27/05/2008, benjamin : recuperation de la config gis en base de donnees. BZ6257
	- maj 27/05/2008, benjamin : Ajout de la fonction getGisConfig qui retourne les opacites definies dans sys_gis_config_global. BZ6257

	- maj 06/06/2008, maxime : Correction du B6637 - On affiche dans data information pour les na > a na_base l'id du na dans les ()
	- maj 10/06/2008, maxime : On recupere la valeur de l'element 3eme axe s'il existe
	- maj 12/06/2008, maxime : Correction du bug 6409 : Affichage incorrect  d'un na 3eme axe lorsqu'il n'a pas de resultat

	- maj 18/06/2008, benoit : correction du bug 6829	
	- maj 24/06/2008, maxime : correction du bug 6966 : La fonction SetReseauPolygones()  devient public
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

	- maj 18/07/2007, benoit : creation de nouvelles variables de classe relatives au 3eme axe et instaciation de   celles-ci dans le constructeur si les valeurs 3eme axe existent dans le tableau de donnees externes

	- maj 18/07/2007, benoit : creation d'une variable de classe indiquant si la famille est principale ou non

	- maj 20/07/2007, benoit : la variable 'na_base' devient 'na_min' et 'na_base' represente desormais le plus     niveau minimum de la famille principale. Cette definition est effectuee dans une nouvelle fonction            'setInfosFromOthersFamilies()' qui permet egalement de determiner si la famille est principale ou non et la   definition des valeurs reelles de la na dans le cas du 3eme axe

	- maj 24/07/2007, benoit : traitement du cas 3eme axe dans les requetes de selection des polygones,             d'affichage des informations et de recherche des valeurs des data_ranges

	- maj 29/08/2007, benoit : dans la recherche de data_ranges auto. de la fonction 'setGraphStyles()', on popule   le tableau de valeurs de data_ranges uniquement si la requete de recherche a renvoye des resultats

	- maj 17/09/2007, benoit : modification de la condition en rajoutant un test sur la presence du 3eme axe

	- maj 17/09/2007, benoit : ajout d'une limitation a un seul resultat a la sous-requete

*/
?>
<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01

	- maj 23/03/2007, benoit : modification de la requete de definition de la viewbox pour na_value = "ALL"      avec une verification sur la presence de la na selectionnee dans la table de definition des polygones
*/
?>
<?
/*
*	@cb21002@
*
*	23/02/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.0.02
*/
?>
<?
/*
*
*	- maj 26/02/2007, benoit : correction de la jointure de la requete de construction des cones dans la 		  fonction 'setAlarmPolygones()'. La jointure se fait sur la na_base et non sur cell.

	- maj 26/02/2007, benoit : la definition de la viewbox pour une na_value particuliere se fait a partir du    polygone correspondant et non par rapport aux coordonnees elargies de la na_value
*
*/
?>
<?php

set_time_limit(600);
session_start();

include_once( dirname(__FILE__) ."/../../php/environnement_liens.php");
include_once( REP_PHYSIQUE_NIVEAU_0."php/edw_function_family.php");

class gisExec
{
    protected static $listOfFilteredElements = array();
    
   /**
    * Constructeur
    * @param array $external_data : tableau des paramètre d'entrée
    */
	function __construct($external_data)
	{
		// maj 27/07/2009 - MPR : Correction du bug 7821 : On cree les repertoires qui stockent les images
		$dir = REP_PHYSIQUE_NIVEAU_0 ."gis/gis_temp/";
		if( !file_exists( $dir ) ){
		
			exec("mkdir $dir");
			exec("chmod -R 777 $dir");
			exec("chown astellia:astellia $dir");
		
		}
		
		$dir = REP_PHYSIQUE_NIVEAU_0 ."gis/gearth_legends/";
		if( !file_exists( $dir ) ){
		
			exec("mkdir $dir");
			exec("chmod -R 777 $dir");
			exec("chown astellia:astellia $dir");
		
		}
		
		// Variables de classe liees aux parametres d'appel (variables d'entree)
		$this->id_prod		= $external_data['id_prod'];
		
		if(!isset($this->database_connection))
			$this->database_connection = DataBase::getConnection( $this->id_prod );
		
		$this->na			= $external_data['na'];
		$this->na_value		= $external_data['na_value'];		
		$this->na_min		= $external_data['na_min'];
		$this->data_type	= $external_data['data_type'];

		$this->mode			= $external_data['mode'];
		if( isset($external_data['na_value_look_at']) )
			$this->na_value_look_at = $external_data['na_value_look_at'];
		// Temp

		//if ($this->mode == "top_worst") $this->mode = "top-worst";
		$this->family		= $external_data['family'];
		$this->ta			= $external_data['ta'];
		$this->ta_value		= $external_data['ta_value'];
		$this->id_data_type	= $external_data['id_data_type'];
		$this->module		= $external_data['module'];
		$this->alarm_color	= $external_data['alarm_color'];
		$this->sort_type	= $external_data['sort_type'];
		$this->sort_id		= $external_data['sort_id'];
		$this->sort_name	= $external_data['sort_name'];
		$this->sort_value	= $external_data['sort_value'];
		$this->table_name	= $external_data['table_name'];
		$this->no_limit_vb  = (isset($external_data['no_limit_vb'])) ? $external_data['no_limit_vb'] : false;

		// 08/11/2007 - Modif. benoit : ajout de 2 nouvelles variables representant les dimensions de la fenetre GIS (on n'utilise plus '$this->gis_side')

		$this->gis_width	= $external_data['gis_width'];
		$this->gis_height	= $external_data['gis_height'];

        // 10/10/2012 MMT DE GIS 3D ONLY - add limitNbElements and sortOrder recieved from the limit definition popup
		$this->limitNbElements = 0;
		if (!empty($external_data['limitation'])) {
			$this->limitNbElements = $external_data['limitation'];
		}
		
		$this->sortOrder = 'asc';
		if (!empty($external_data['order'])) {
			$this->sortOrder = $external_data['order'];
		}
        
		// 19/11/2007 - Modif. benoit : ajout de 2 variables supplementaires stockant les dimensions initiales de la vue

		$this->initial_gis_width	= $this->gis_width;
		$this->initial_gis_height	= $this->gis_height;

		// 18/07/2007 - Modif. benoit : instanciation des variables relatives au 3eme axe (si ces valeurs existent dans le tableau de donnees externes)

		if (isset($external_data['na_axe3']) && isset($external_data['na_value_axe3'])) {
			$this->axe3				= true;
			$this->na_axe3			= $external_data['na_axe3'];
			$this->na_value_axe3	= $external_data['na_value_axe3'];
		}
		else 
		{
			$this->axe3				= false;
			$this->na_axe3			= "";
			$this->na_value_axe3	= "";
		}

		// Variables de classe de sortie

		$this->view_box			= array();
		$this->view_box_origine	= array();
		$this->tab_zoom			= array();
		$this->current_zoom		= 0;
		$this->tab_styles		= array();
		$this->tab_layers		= array();
		$this->tab_polygones	= array();
		$this->raster			= false;

		// Variables locales
		$this->internal_data		= array();
		$this->debug				= false;
		$this->data_range_values	= array();
		$this->start_timestamp		= array();
		$this->layer_mouseover		= "";
		$this->alarm_rawkpi			= array();

		// 18/07/2007 - Modif. benoit : ajout des variables relatives aux familles differentes de la famille principale et de la variable du separateur 3eme axe

		$this->family_gis_based	= true;
		$this->sep_axe3			= "";

		// 20/07/2007 - Modif. benoit : la variable de classe 'na_base' n'est plus une variable d'entree mais une variable locale dont la valeur est definie dans 'setInfosFromOthersFamilies()'
		$this->na_base = "";
	
		$this->master_topo = getTopoMasterProduct();

		// Premier chargement

		$this->startActionTimeStamp("first_call");

		$this->setGlobalParameters();

		// 18/07/2007 - Modif. benoit : appel d'une fonction permettant de determiner si la famille est principale ou non et les valeurs reelles de la na dans le cas du 3eme axe

		$this->setInfosFromOthersFamilies();

		$this->setZooms();
		$this->setViewBox();

		// On verifie que la view_box a des valeurs sinon on n'effectue pas les autres etapes

		if (count($this->view_box) > 0) {
			if ($this->data_type == "alarm") $this->setAlarmSortName();
			$this->setReseauStyles();
			$this->setGeoLayersAndStyles();
			$this->setReseauLayers();
			$this->setReseauPolygones();
			$this->setGeoPolygones();
			$this->setLayerMouseOver($this->na);
		}
		
		
		$this->traceActions("1er appel : temps de generation", $this->stopActionTimeStamp("first_call"), "time");
		
		$msg[0] = (!$this->no_limit_vb) ? "Limites de la vb definies" : "Limites de la vb non definies - Cas Gearth depuis un graph ou res d'alarme";
		$this->traceActions("Limites de la viewbox","'".$msg[0]."'","var");
		
	} // End function __construct

    /**
     * Fonction de definition des parametres globaux de l'application servant a la classe
     */
	function setGlobalParameters()
	{
		$sql = "SELECT * FROM sys_gis_config_global";
		$req = $this->database_connection->getAll($sql);
		$row = $req[0];

		$this->internal_data['style_voronoi_defaut']		= $row['style_voronoi_defaut'];
		$this->internal_data['style_defaut_cone']			= $row['style_defaut_cone'];
		$this->internal_data['style_alarm_defaut']			= $row['style_alarm_defaut'];
		$this->internal_data['facteur_taille_cone_defaut']	= $row['facteur_taille_cone_defaut'];
		$this->internal_data['facteur_taille_voronoi']		= $row['facteur_taille_voronoi'];
		$this->internal_data['angle_de_precision_voronoi']	= $row['angle_de_precision_voronoi'];
		$this->internal_data['largeur_cone']				= $row['largeur_cone'];
		$this->internal_data['enlarge_viewbox_prct']		= $row['enlarge_viewbox_prct'];
		$this->internal_data['data_range_default']			= explode(';', $row['data_range_default']);

		$this->view_box_origine = array($row['mapxmin'], -$row['mapymax'], $row['mapxmax']-$row['mapxmin'], $row['mapymax']-$row['mapymin']);
		$this->gis_side = $row['gis_side'];
		$this->slide_duration = $row['slide_duration'];

		($row['srid'] != "") ? $this->srid	= $row['srid'] : $this->srid = -1;
		($row['debug'] != 1) ? $this->debug = false : $this->debug = true;
		
		// __debug($this->debug,"construct db debug");
		($row['raster'] != 1) ? $this->raster = false : $this->raster = true;
		
                // maj 14/06/2011 - MPR : Evolution GIS without polygons
                // Deux modes d'affichage :
                // Mode d'affichage = 1 : avec polygones sur tous les NA avec GIS
                // Mode d'affichage = 0 : sans polygones sur le NA min
                // Le mode d'affichage est défini par le paramètre global gis_display_mode
                // 22/11/2011 NSE bz 24774 : ajout du produit en paramètre
                $this->displayMode = GisModel::getGisDisplayMode($this->id_prod);
	} // End function setGlobalParameters()

	// 18/07/2007 - Modif. benoit : ajout de la fonction ci-dessous
        /**
         * Fonction permettant de definir les informations liees aux familles differentes de la famille principale
         */
	function setInfosFromOthersFamilies()
	{
		// On determine si la famille est principale ainsi que le niveau minimum

		// 31/10/2007 - modif. benoit : correction de la requete ci-dessous où les quotes pour '$this->family' n'etaient jamais refermees

		$sql = "SELECT main_family, network_aggregation_min FROM sys_definition_categorie WHERE family = '".$this->family."'";

		$this->traceActions("determination de l'etat de la famille (principale ou non) ", $sql, "requete");
		
		$req = $this->database_connection->getAll($sql);
		$row = $req[0];

		if ($row['main_family'] != 1) {	// famille != famille principale	
			$this->family_gis_based	= false;

			$sql2 =	 " SELECT network_aggregation_min FROM sys_definition_categorie"
					." WHERE family=(SELECT family FROM sys_definition_categorie WHERE main_family = 1)";

			$req2 = $this->database_connection->getAll($sql2);
			$row2 = $req2[0];
			
			// 12/12/2007 - Modif. benoit : '$this->na_base' vaut toujours la valeur de la na minimum determinee par la requete

			//if ($row2['network_aggregation_min'] == $this->na_min) $this->na_base = $this->na_min;

			$this->na_base = $row2['network_aggregation_min'];
		}
		else 
		{
			$this->na_base = $this->na_min;
		}

		// Si le 3eme axe existe, on redefinit le nom et la valeur de la na
							
		if ($this->axe3) {

			// Definition du separateur axe3

			$sql2 = "SELECT value FROM sys_global_parameters WHERE parameters = 'sep_axe3'";
			$req2 = $this->database_connection->getAll($sql2);
			$row2 = $req2[0];
			
			$this->sep_axe3	= $row2['value'];

			// On redefinit la valeur de la na en explosant la na_value et en ne conservant que la premiere partie

			$na_value_tab	= explode($this->sep_axe3, $this->na_value);
			$this->na_value	= $na_value_tab[0];
			
			// On definit le nom de la na d'axe1 en soustrayant a la na le nom de la na d'axe3
			
			// Gestion Axe 3  - Inutile 
			//$this->na = str_replace('_'.$this->na_axe3, '', $this->na);
		}
	} // End function setInfosFromOthersFamilies()

        /**
         * Fonction permettant de definir la viewbox correspondant a la selection de na
         * @return type 
         */
	function setViewBox()
	{
		// 23/03/2007 - Modif. benoit : maj de la requete de definition de la viewbox pour na_value = "ALL" avec une verification sur la presence de la na selectionnee dans la table de definition des polygones

		if ($this->na_value == "ALL") {
			$sql = "SELECT eorp_x as x, eorp_y as y FROM edw_object_ref_parameters WHERE eorp_x IS NOT NULL AND eorp_y IS NOT NULL AND (SELECT COUNT(*) FROM sys_gis_topology_voronoi WHERE na = '".$this->na."') > 0";

			$this->traceActions("def de la viewbox correspondant aux na sel", $sql, "requete");

			$req = $this->database_connection->getAll($sql);

			if (count($req) > 0) {
				// Definition des valeurs x_min, x_max, y_min et y_max

				$voro_x = $voro_y = array();

				foreach ($req as $row) {
					$voro_x[] = $row['x'];
					$voro_y[] = $row['y'];
				}

				$xmin = min($voro_x);
				$xmax = max($voro_x);
				$ymin = min($voro_y);
				$ymax = max($voro_y);

				// Elargissement de la view_box suivant le pourcentage d'elargissement defini en base

				$enlarge_prct = $this->internal_data['enlarge_viewbox_prct'];

				$xmin -= $xmin*($enlarge_prct/pow(10, (strlen((int)$xmin)-2)));
				$ymin -= $ymin*($enlarge_prct/pow(10, (strlen((int)$ymin)-2)));
				$xmax += $xmax*($enlarge_prct/pow(10, (strlen((int)$xmax)-2)));
				$ymax += $ymax*($enlarge_prct/pow(10, (strlen((int)$ymax)-2)));
			}
		}
		else
		{
			// 26/02/2007 - Modif. benoit : la definition de la viewbox pour une na_value particuliere se fait a partir du polygone correspondant et non par rapport aux coordonnees elargies de la na_value

			// maj 20/08/2009 - MPR :  Modification de la condition sur la na_value
			$sql = "SELECT xmin(extent(p_voronoi)) as xmin, ymin(extent(p_voronoi)) as ymin,
						   xmax(extent(p_voronoi)) as xmax, ymax(extent(p_voronoi)) as ymax 
					FROM sys_gis_topology_voronoi 
					WHERE na = '{$this->na}' AND na_value IN (
						
						SELECT DISTINCT eor_id FROM edw_object_ref 
						WHERE eor_obj_type = '{$this->na}' AND ";
						
			$n_value = explode("||", $this->na_value);
			
			foreach($n_value as $na_value){			
					$sql_tmp[] = " (eor_id = '{$na_value}' OR eor_label = '{$na_value}' OR eor_id_codeq = '{$na_value}' )";
			}
			$sql.= implode(" OR ", $sql_tmp).")";
			
			$this->traceActions("def de la viewbox correspondant aux na sel", $sql, "requete");

			$req = $this->database_connection->getAll($sql);

			if (count($req) > 0) {
				$row = $req[0];

				// Elargissement de la view_box suivant le pourcentage d'elargissement defini en base

				$enlarge_prct = $this->internal_data['enlarge_viewbox_prct'];

				$xmin = $row['xmin']-$row['xmin']*($enlarge_prct/pow(10, (strlen((int)$row['xmin'])-2)));
				$ymin = $row['ymin']-$row['ymin']*($enlarge_prct/pow(10, (strlen((int)$row['ymin'])-2)));
				$xmax = $row['xmax']+$row['xmax']*($enlarge_prct/pow(10, (strlen((int)$row['xmax'])-2)));
				$ymax = $row['ymax']+$row['ymax']*($enlarge_prct/pow(10, (strlen((int)$row['ymax'])-2)));
			}
		}

		// Si la valeur '$min' n'est pas defini, on sort de la fonction

		if ((!isset($xmin)) || (($xmax-$xmin) <= 0) || (($ymax-$ymin) <= 0)) {
			$this->view_box = array();
			return;
		}

		$this->view_box = array($xmin, -$ymax, $xmax-$xmin, $ymax-$ymin);

		// On adapte la taille de la view_box en fonction du facteur de zoom courant

		// 28/12/2007 - Modif. benoit : la definition du zoom se fait par rapport a la largeur ou la hauteur maximale de la viewbox origine

		if ($this->view_box_origine[2] > $this->view_box_origine[3]) {
			$zoom_box = $this->view_box_origine[2]/$this->view_box[2];
		}
		else 
		{
			$zoom_box = $this->view_box_origine[3]/$this->view_box[3];
		}

		$this->traceActions("valeur du zoom", $zoom_box, "valeur variable");

		$this->current_zoom	= $this->getCurrentZoom($zoom_box);

		if ($this->current_zoom != $zoom_box) {

			$old_vb_width	= $this->view_box[2];
			$old_vb_height	= $this->view_box[3];

			if ($this->current_zoom > $zoom_box) {
				$this->view_box[2] *= $this->current_zoom/$zoom_box;
				$this->view_box[3] *= $this->current_zoom/$zoom_box;
			}
			else
			{
				$this->view_box[2] *= $zoom_box/$this->current_zoom;
				$this->view_box[3] *= $zoom_box/$this->current_zoom;
			}
			$this->view_box[0] += ($old_vb_width-$this->view_box[2])/2;
			$this->view_box[1] += ($old_vb_height-$this->view_box[3])/2;
		}

		// 13/11/2007 - Modif. benoit : le vue du GIS n'etant plus necessairement un carre, on ajuste la viewbox en fonction de la taille de la vue
		
		$vb_width	= $this->view_box[2];
		$vb_height	= $this->view_box[3];		

		// 03/01/2008 - Modif. benoit : reformulation de la condition pour definir les dimensions de la vbox

		($vb_width >= $vb_height) ? $vbin = 1 : $vbin = 0;
		($this->gis_width >= $this->gis_height) ? $tbin = 1 : $tbin = 0;

		if ((bindec($vbin.$tbin) % 2) == 1) {
			$vb_width	= ($vb_height/$this->gis_height)*$this->gis_width;

			$xsvg	= $this->view_box[0]-($vb_width-$this->view_box[2])/2;
			$ysvg	= $this->view_box[1];			
		}
		else 
		{
			$vb_height	= ($vb_width/$this->gis_width)*$this->gis_height;

			$xsvg	= $this->view_box[0];
			$ysvg	= $this->view_box[1]-($vb_height-$this->view_box[3])/2;			
		}

		$this->view_box = array($xsvg, $ysvg, $vb_width, $vb_height);
	} // End function setViewBox()

        /**
         * Fonction permettant de definir les paliers de zoom disponibles dans le GIS
         */
	function setZooms()
	{
		$sql =	 " SELECT id, scale FROM sys_gis_config_palier WHERE on_off=1"
				." UNION SELECT id, mainscale FROM sys_gis_config_palier WHERE on_off=1 ORDER BY id";

		$this->traceActions("sel des paliers de zoom", $sql, "requete");

		$req = $this->database_connection->getAll($sql);

		foreach ($req as $row) {
			$this->tab_zoom[] = $row['scale'];
		}
	} // End function setZooms()

        /**
         * Fonction permettant de determiner le palier de zoom courant
         * @param integer $zoom_actuel zoom actuel
         * @return integer 
         */
	function getCurrentZoom($zoom_actuel)
	{
		if (in_array($zoom_actuel, $this->tab_zoom)){
			return $zoom_actuel;
		}
		else
		{
			$current_zoom = 0;

			if ($zoom_actuel >= $this->tab_zoom[count($this->tab_zoom)-1]) {	// zoom > limite max
				$current_zoom	= $this->tab_zoom[count($this->tab_zoom)-1];
			}
			else if ($zoom_actuel <= $this->tab_zoom[0]) {	// zoom < limite min
				$current_zoom	= $this->tab_zoom[0];
			}
			else	// Zoom compris entre 2 valeurs du tableau
			{
				for ($i=0; $i < count($this->tab_zoom)-1; $i++) {
					if (($zoom_actuel >= $this->tab_zoom[$i]) && ($zoom_actuel <= $this->tab_zoom[$i+1])) {

						$actual_zoom_min = $zoom_actuel-$this->tab_zoom[$i];
						$actual_zoom_max = $this->tab_zoom[$i+1]-$zoom_actuel;

						if ($actual_zoom_min <= $actual_zoom_max) {
							$current_zoom = $this->tab_zoom[$i];
						}
						else
						{
							$current_zoom = $this->tab_zoom[$i+1];
						}
					}
				}
			}
			return $current_zoom;
		}
	} // End function getCurrentZoom()

        /**
         * Fonction de definition du tableau de styles
         */
	function setReseauStyles()
	{
		switch ($this->data_type) {
			case 'graph'	:	$this->setGraphStyles();
			break;
			case 'alarm'	:	$this->setAlarmStyles();
			break;
		}
	} // End function setReseauStyles()

        /**
         * Fonction de definition des styles du graphe
         */
	function setGraphStyles()
	{
		// maj 27/05/2008 Benjamin : recuperation de la config gis en base de donnees. BZ6257
		$gis_config = $this->getGisConfig();
	
		$this->tab_styles			= array();
		$this->data_range_values	= array();

		// Definition des styles par defaut
		
		$this->tab_styles[] = array('style_name'=>"style_voronoi_defaut", 'style_def'=>$this->internal_data['style_voronoi_defaut']);

		$this->tab_styles[] = array('style_name'=>"style_defaut_cone", 'style_def'=>$this->internal_data['style_defaut_cone']);
	
		// Recherche des data_ranges definis en base

		$sql = "SELECT * FROM sys_data_range_style WHERE id_element='".$this->sort_id."'";

		$this->traceActions("recherche des data_ranges definis en base", $sql, "requete");
        // 20/06/2011 NSE : merge Gis without polygons
		$req = $this->database_connection->execute($sql);

		if ($this->database_connection->getNumRows() > 0) {	// Des data_ranges existent
			while ( $row = $this->database_connection->getQueryResults($req,1) ) {
				$style_name = "style_data_range_".$row['range_order'];
				$this->tab_styles[] = array('style_name'=>$style_name, 'style_def'=>$row['svg_style']);
				$this->data_range_values[] = array('style_name'=>$style_name, 'range_inf'=>$row['range_inf'], 'range_sup'=>$row['range_sup']);
			}
		}
		else	// Pas de data_ranges definis, creation de data_ranges automatiques
		{
            // 20/06/2011 NSE : merge Gis without polygons
                    $coordsVb = $this->getViewBox();
                    $x_min = $coordsVb[0];
                    $y_max = $coordsVb[1];
                    $x_max = $coordsVb[2];
                    $y_min = $coordsVb[3];

			$data_range_default = $this->internal_data['data_range_default'];

			// 24/07/2007 - Modif. benoit : pour les familles 3eme axe, ajout d'une condition sur la valeur de la na 3eme axe dans la recherche des valeurs pour les data_ranges

			$condition_3eme_axe = "";

			// 17/09/2007 - Modif. benoit : modification de la condition en rajoutant un test sur la presence du 3eme axe

			if ($this->axe3 && $this->na_value_axe3 != "ALL") {
				$condition_3eme_axe = " AND {$this->na_axe3} = '{$this->na_value_axe3}'";
			}

            // 21/11/2011 OJT : bz24445, exclusion des cellules virtuelles
			$sql =	 " SELECT DISTINCT ".$this->sort_value." AS sort_value"
					." FROM ".$this->table_name
					." WHERE ".$this->ta." = ".$this->ta_value
					." AND ".$this->sort_value." IS NOT NULL".$condition_3eme_axe
                    ." AND $this->na NOT LIKE 'virtual_%'"
					." GROUP BY ".$this->sort_value
					." ORDER BY ".$this->sort_value;

			$this->traceActions("recherche des valeurs pour les data_ranges auto.", $sql, "requete");
                    $req = $this->database_connection->execute($sql);

			// 29/08/2007 - Modif. benoit : on popule le tableau de valeurs de data_ranges uniquement si la requete de recherche de data_ranges automatique a renvoye des resultats

                    if ( $this->database_connection->getNumRows() > 0 ) {

				$tab_row = array();

                        while( $row = $this->database_connection->getQueryResults($req,1))
                        {
					$tab_row[] = $row['sort_value'];
				}

				$min_value	= $tab_row[0];
				$max_value	= $tab_row[count($tab_row)-1];

				// 28/12/2007 - Modif. benoit : si la difference entre la valeur max et la valeur min est de 0, le pas vaut egalament 0 (calcul du pas : (max_value-min_value)/nb_ranges). Dans ce cas, on fixe la valeur max a celle de l'unique valeur et la valeur min a 0

				if ($max_value - $min_value == 0) {
					$max_value = $min_value;
					$min_value = 0;
				}

				
				$step_value	= ($max_value-$min_value)/count($data_range_default);

				for ($i=0; $i < count($data_range_default); $i++) {
					$range_inf	= $min_value+($i*$step_value);
					$range_sup	= $min_value+(($i+1)*$step_value);
					$style_name	= "style_data_range_".($i+1);
					
					$this->tab_styles[] = array('style_name'=>$style_name, 'style_def'=>'stroke:'.$data_range_default[$i].';stroke-opacity:'.$gis_config["stroke-opacity"].';fill:'.$data_range_default[$i].';fill-opacity:'.$gis_config["stroke-opacity"].';');

					$this->data_range_values[] = array('style_name'=>$style_name, 'range_inf'=>$range_inf, 'range_sup'=>$range_sup);
				}
			}
		}
		
		foreach($this->tab_styles as $key=>$styles){
			foreach($styles as $k=>$st)
			$this->traceActions("***$k***",$st,"$key");
		}
	}

	// Fonction de definition des styles de l'alarme

	function setAlarmStyles()
	{
		// maj 27/05/2008 Benjamin : recuperation de la config gis en base de donnees. BZ6257
		$gis_config = $this->getGisConfig();
		
		$this->tab_styles			= array();
		$this->data_range_values	= array();

		$this->tab_styles[] = array('style_name'=>"style_alarm_defaut", 'style_def'=>$this->internal_data['style_alarm_defaut']);
		$this->data_range_values[] = array('style_name'=>"style_alarm_defaut", 'range_inf'=>"alarm", 'range_sup'=>"down");

		if ($this->mode == "top-worst") {
			$this->tab_styles[] = array('style_name'=>"style_alarm_up", 'style_def'=>'stroke:'.$this->alarm_color['critical'].';stroke-opacity:'.$gis_config["stroke-opacity"].';fill:'.$this->alarm_color['critical'].';fill-opacity:'.$gis_config["stroke-opacity"].';');
			$this->data_range_values[] = array('style_name'=>"style_alarm_up", 'range_inf'=>"alarm", 'range_sup'=>"up");
		}
		else
		{
			$this->tab_styles[] = array('style_name'=>"style_alarm_critical", 'style_def'=>'stroke:'.$this->alarm_color['critical'].';stroke-opacity:'.$gis_config["stroke-opacity"].';fill:'.$this->alarm_color['critical'].';fill-opacity:'.$gis_config["stroke-opacity"].';');
			$this->data_range_values[] = array('style_name'=>"style_alarm_critical", 'range_inf'=>"alarm", 'range_sup'=>"up (critical)");

			$this->tab_styles[] = array('style_name'=>"style_alarm_major", 'style_def'=>'stroke:'.$this->alarm_color['major'].';stroke-opacity:'.$gis_config["stroke-opacity"].';fill:'.$this->alarm_color['major'].';fill-opacity:'.$gis_config["stroke-opacity"].';');
			$this->data_range_values[] = array('style_name'=>"style_alarm_major", 'range_inf'=>"alarm", 'range_sup'=>"up (major)");

			$this->tab_styles[] = array('style_name'=>"style_alarm_minor", 'style_def'=>'stroke:'.$this->alarm_color['minor'].';stroke-opacity:'.$gis_config["stroke-opacity"].';fill:'.$this->alarm_color['minor'].';fill-opacity:'.$gis_config["stroke-opacity"].';');
			$this->data_range_values[] = array('style_name'=>"style_alarm_minor", 'range_inf'=>"alarm", 'range_sup'=>"up (minor)");
		}
	} // End function setAlarmStyles()

        /**
         * Fonction de definition des layers et des styles geographiques
         */
	function setGeoLayersAndStyles()
	{
		// 25/03/2008 - Modif. benoit : on n'affiche que les layers geo qui ont des vecteurs definis
		
		/*$sql =	 " SELECT layer_order, gis_vecteur.id AS geo_id, gis_vecteur.nom AS geo_nom,"							." 'fill:'||gis_vecteur.filled_color||';"
				." fill-opacity:1;stroke:'||gis_vecteur.stroke_color||';stroke-opacity:1;"
				." stroke-width:'||gis_vecteur.stroke_length AS style"
				." FROM sys_gis_config_vecteur AS gis_vecteur, sys_gis_config AS gis_conf"
				." WHERE gis_conf.id_palier = ("
				." SELECT id FROM sys_gis_config_palier WHERE scale=".$this->current_zoom.""
				." OR mainscale=".$this->current_zoom.")"
				." AND gis_conf.on_off=1 AND gis_conf.id_vecteur = gis_vecteur.id AND gis_vecteur.on_off = 1"
				." ORDER BY layer_order ASC";*/
		
		$sql =	 " SELECT layer_order, gis_vecteur.id AS geo_id, gis_vecteur.nom AS geo_nom,"
				." 'fill:'||gis_vecteur.filled_color||';"
				." fill-opacity:1;stroke:'||gis_vecteur.stroke_color||';stroke-opacity:1;"
				." stroke-width:'||gis_vecteur.stroke_length::text AS style"
				." FROM sys_gis_config_vecteur AS gis_vecteur, sys_gis_config AS gis_conf, sys_gis_data_polygon AS gis_data"
				." WHERE gis_conf.id_palier = ("
				." SELECT id FROM sys_gis_config_palier WHERE scale=".$this->current_zoom.""
				." OR mainscale=".$this->current_zoom.")"
				." AND gis_conf.on_off=1 AND gis_conf.id_vecteur = gis_vecteur.id AND gis_vecteur.on_off = 1"
				." AND gis_data.type = gis_vecteur.nom"
				." ORDER BY layer_order ASC";

		$this->traceActions("recherche des styles geo en fonction du zoom courant", $sql, "requete");

		$req = $this->database_connection->execute($sql);

		$tab_order = array();
		
		foreach ($this->tab_layers as $id_layer=>$layer_content) {
			$tab_order[] = $layer_content['order'];
		}

		// 26/03/2008 - Modif. benoit : definition d'un tableau contenant l'ensemble des identifiants des styles dans '$this->tab_styles'

		$style_name_list = array();

		for ($i=0; $i < count($this->tab_styles); $i++) {
			$style_name_list[] = $this->tab_styles[$i]['style_name'];
		}

		while ( $row = $this->database_connection->getQueryResults($req, 1) )
                {
			if (!isset($this->tab_layers[$row['geo_nom']])) {

				$new_order = $row['layer_order'];

				if (in_array($new_order, $tab_order)) {
					while (in_array($new_order, $tab_order)) {
						$new_order += 1;
					}
				}

				$tab_order[] = $new_order;

				$this->tab_layers[$row['geo_nom']] = array('order'=>$new_order, 'border'=>1, 'background'=>1, 'type'=>"geo");
				//$this->tab_styles[] = array('style_name'=>'style_'.$row['geo_nom'], 'style_def'=>$row['style']);
			}

			// 26/03/2008 - Modif. benoit : si le style geo n'existe pas dans '$this->tab_styles', on le rajoute

			if (!in_array('style_'.$row['geo_nom'], $style_name_list)) {
				$this->tab_styles[] = array('style_name'=>'style_'.$row['geo_nom'], 'style_def'=>$row['style']);
			}
		}
	} // End function setGeoLayersAndStyles()

        /**
         * Fonction de definition des layers reseau
         */
	function setReseauLayers()
	{
		// On determine le prochain index du tableau de layers (si il existe)

		if(count($this->tab_layers) > 0){
			$tab_order = array();
			foreach ($this->tab_layers as $id_layer=>$layer_content) {
				$tab_order[] = $layer_content['order'];
			}
			$next_order = max($tab_order)+1;
		}
		else
		{
			$next_order = 0;
		}

		// On ajoute le layer du na actif (si ce layer n'existe pas)

		if(!isset($this->tab_layers[$this->na])) $this->tab_layers[$this->na] = array('order'=>$next_order, 'border'=>1, 'background'=>1, 'type'=>"reseau");

		// Si la na est la na_base, on ajoute un layer de type cône

		if (($this->na == $this->na_base) && (!isset($this->tab_layers['cone']))) {
			$next_order += 1;
			$this->tab_layers['cone'] = array('order'=>$next_order, 'border'=>1, 'background'=>1, 'type'=>"reseau");
		}

		// On indique que les infos au survol auront pour source le layer

		//$this->layer_mouseover = $this->na;
	} // End function setReseauLayers()

	/**
         * Fonction de definition des polygônes reseau
     * 
     * 24/06/2008, maxime : correction du bug 6966 : La fonction SetReseauPolygones()  devient public
         */
	public function setReseauPolygones()
	{
		// Selection des layers de type "reseau" dans le tableau de layers

		$layers_reseau = array();

		foreach ($this->tab_layers as $layer_id=>$layer_content)
                {
                    if (($layer_content['type'] == "reseau") && ($layer_id != "cone"))
                    { 
				$layers_reseau[] = $layer_id;
			}
		}

		// Cas particulier du layer na_base, on peut avoir les cônes mais les voronoi masques. Dans ce cas, on inclut le layer na_base

		if ((!in_array($this->na_base, $layers_reseau)) && (isset($this->tab_layers['cone']) /*&& ($this->tab_layers['cone']['border'] == 1) || ($this->tab_layers['cone']['background'] == 1)*/)) {
			$layers_reseau[] = $this->na_base;
		}

		// Selection des polygones

		switch ($this->data_type) {
			case 'graph'	:
				for ($i=0; $i < count($layers_reseau); $i++) {
					$this->setGraphPolygones($layers_reseau[$i]);
				}
			break;
			case 'alarm'	:
				for ($i=0; $i < count($layers_reseau); $i++) {
					$this->setAlarmPolygones($layers_reseau[$i]);
				}
			break;
		}
	} // End function setReseauPolygones()

    /**
     * Fonction qui recherche la table de données en fonction du NA
     * @param string $na
     * @return string
     */
    protected function getTableName( $na )
    {
        // On definit la table de donnees a utiliser
        if ($na == $this->na) {
            $table_name = $this->table_name;
        }
        else
        {
            $table_name = $this->updateTableName($this->na, $na);
        }
        return $table_name;
    } // End function getTableName()

    /**
     * Fonction qui récupère la sous-requête en fonction du NA et de la table de données
     * @param type $na NA
     * @param type $table_name table de données
     * @return string query
     */
    protected function getSubQuery( $na, $table_name="" )
    {
        if( $this->data_type == 'graph' )
        {
    // On definit la sous-requete de selection des valeurs des na et des sort_values
            if ( !$this->axe3 )
            {
                $_select_data = "{$na}, {$this->sort_value} AS sort_value";
                $_from_data =  "{$table_name}";
                $_where_data = "{$this->ta}={$this->ta_value}";

                $sub_query = " SELECT ".$_select_data." FROM ".$_from_data." WHERE ".$_where_data;
            }
            else
            {
                // 10/06/2008 - Modif.maxime : On recupere la valeur de l'element 3eme axe s'il existe
                $_select_data = "{$na}, {$this->na_axe3} AS value_axe3 , {$this->sort_value} AS sort_value";
                $_from_data =  "{$table_name}";
                $_where_data = "{$this->ta}={$this->ta_value}";

                if ($this->na_value_axe3 != "ALL") {
                    $_where_data .= " AND {$this->na_axe3} = '".$this->na_value_axe3."'";
                }
                $sub_query = " SELECT ".$_select_data." FROM ".$_from_data." WHERE ".$_where_data;
            }
        }
        else // gestion des Alarmes
        {
            if ($na == $this->na)
            {
                $id_data_type = $this->id_data_type;
            }
            else
            {
                $id_data_type = $this->updateIdDataType($na);
            }

            // On definit la sous-requete de selection des valeurs des na et des sort_values

            // 24/07/2007 - Modif. benoit : dans le cas du 3eme axe, on redefini la sous-requete de la selection des polygones de l'alarme

            //16/11/2012 MMT bz 30276 ajout gestion top-worst pour DE GIS 3D only
            // dans le cas top worst il faut joindre sur edw_alarm_detail pour avoir la valeure et pouvoir ordonner dessus
            $qFrom = "FROM edw_alarm";
            if ($this->mode == "top-worst")
            {
                $qFrom = ",edw_alarm_detail.value as topworst_value FROM edw_alarm, edw_alarm_detail ";
                $qAnd = " AND edw_alarm.id_result = edw_alarm_detail.id_result ";
            }
            
            if (!$this->axe3)
            {
                $sub_query = " SELECT na_value, critical_level $qFrom"
                                        ." WHERE na='".$na."' AND ta='".$this->ta."' AND ta_value=".$this->ta_value
                                        ." AND id_alarm='".$id_data_type."' AND alarm_type='".$this->mode."'".$qAnd;
            }
            else
            {
                // Maj 02/06/2009 - Gestion 3eme different dans la table edw_alarm
                $sub_query = " SELECT DISTINCT na_value AS na_value, critical_level"
                                        ." $qFrom WHERE na='".$na."'"
                                        ." AND ta='".$this->ta."' AND ta_value=".$this->ta_value
                                        ." AND id_alarm='".$id_data_type."' AND alarm_type='".$this->mode."'".$qAnd;

                if ( $this->na_value_axe3 != "ALL" )
                {
                    $sub_query .= " AND a3_value = '".$this->na_value_axe3."'";
                }
            }
        }
        return $sub_query;
    } // End function getSubQuery()

    /**
     * Fonction qui récupère les coordonnées de la VB
     * @return array(xmin,xmax,ymin,ymax)
     */
    protected function getViewBox()
    {
        // On definit la requete complete de selection des polygones
        $x_min = $this->view_box[0];
        $y_max = -1*$this->view_box[1];
        $x_max = $x_min+$this->view_box[2];
        $y_min = $y_max-$this->view_box[3];

        // On elargit les coordonnees de la viewbox de la selection pour englober plus de polygones
        $enlarge = 30;

        $x_min -= $x_min*($enlarge/pow(10, (strlen((int)$x_min)-2)));
        $y_min -= $y_min*($enlarge/pow(10, (strlen((int)$y_min)-2)));
        $x_max += $x_max*($enlarge/pow(10, (strlen((int)$x_max)-2)));
        $y_max += $y_max*($enlarge/pow(10, (strlen((int)$y_max)-2)));

        return array( $x_min, $x_max, $y_min, $y_max);
    } // End function getViewBox()

    /**
     * Fonction qui génère la requête récupérant les polygones sur le NA min
     * @param type $limits_vb limites de la VB
     * @param type $select_axe3 Sélection 3ème axe
     * @param type $sub_query sous-requête
     * @return string query
     */
    protected function generateQueryGraphPolygonsOnNaMin( $limits_vb, $select_axe3, $sub_query )
    {

        // 10/10/2012 MMT DE GIS 3D ONLY - utilisation de getNeRestrictionQuerySelect et getNeRestrictionQueryClause
        // pour limiter le nombre d'elements à afficher
        
        // 28/01/2013 BBX
        // DE GIS Filter : Récupération de la sélection
        $neList = self::getNeListFromSelecteur($this->na_min, $this->id_prod);

        $sql =	 " SELECT AsSVG(t_gis.p_voronoi) AS svg_na, t_data.sort_value".$this->getNeRestrictionQuerySelect()
        ." , t_gis.na_value AS value $select_axe3 AsText(transform(t_gis.p_voronoi, 4326)) AS to_kml"
        ." FROM sys_gis_topology_voronoi AS t_gis"
        ." LEFT JOIN ($sub_query) AS t_data"
        ." ON t_gis.na_value=t_data.".$this->na_base
        ." RIGHT JOIN"
        ." ("
        ."		SELECT DISTINCT eorp_id as {$this->na_base} FROM edw_object_ref_parameters"
        ."		WHERE eorp_x IS NOT NULL AND eorp_y IS NOT NULL";
        
        // 28/01/2013 BBX
        // DE GIS Filter : Récupération de la sélection
        if(!empty($neList)) {
            $sql .= " AND eorp_id IN ('".implode("','",$neList)."')";
        }
        
        $sql .= $limits_vb
        ." ) AS t_object"
        ." ON t_gis.na_value=t_object.".$this->na_base
        ." WHERE t_gis.na='".$this->na_base."' AND t_gis.p_voronoi IS NOT NULL";
	$sql .= $this->getNeRestrictionQueryClause();   
        return $sql;
    } // End function generateQueryGraphPolygonsOnNaMin()

    /**
     * Fonction qui génère la requête récupérant les polygones sur les NA > Na min
     * @param type $na NA
     * @param type $sub_query sous-requête
     * @param type $select_axe3 sélection 3ème axe
     * @param type $limits_vb Limites de la VB
     * @return string query
     */
    protected function generateQueryGraphPolygonsOnOtherNa( $na, $sub_query, $select_axe3, $limits_vb )
    {
        /*
            SELECT DISTINCT network, tos , PE_ControlCommand_Nb_Critical_Err_Core
            FROM edw_iu_traffic_axe1_raw_network_tos_day, edw_object_arc_ref e0, edw_object_ref_parameters, edw_object_arc_ref e1
            WHERE day=20090518
                AND e1.eoar_arc_type = 'sai|s|rnc'
                AND e0.eoar_arc_type = 'rnc|s|network'
                AND SPLIT_PART(e0.eoar_arc_type,'|s|',1) = SPLIT_PART(e1.eoar_arc_type,'|s|',2)
                AND e1.eoar_id_parent = e0.eoar_id
                AND e1.eoar_id = eorp_id
                AND eorp_x IS NOT NULL AND eorp_y IS NOT NULL
                AND eorp_x >= 110331.064355 AND eorp_x <= 529092.938625 AND eorp_y >= 1807928.45121 AND eorp_y <= 2014741.98383
                AND network = e0.eoar_id_parent
        */
        
        // 28/01/2013 BBX
        // DE GIS Filter : Récupération de la sélection
        $neList = self::getNeListFromSelecteur($this->na_min, $this->id_prod);

        $this->traceActions("****","SetGraphPolygon on na > na_base****","****");

        $tab = $this->getAgregPathGIS($this->na_base, $na, $this->family);
        $tab = array_reverse($tab);

        $this->traceActions("****",implode("/", $tab),"array");

        $_select = "SELECT DISTINCT e0.eoar_id_parent as {$na} ";

        $_from_topo = "FROM edw_object_ref_parameters ";

        $i = count($tab)-2;

        // Boucle pour jointure sur les elements reseau
        // On remonte du niveau agregation minimum  jusqu'au niveau d'agregation recherche (ex : network -> rnc -> sai)
        $_where_topo = "";
        foreach( $tab as $k=>$_na ){
            $i = $k + 1;
            if( $k <= (count($tab)-2) ){
                $_from_topo .= ",edw_object_arc_ref e{$k} ";
                $_where_topo.= ($_where_topo == "") ? " WHERE ": " AND ";
                // $_where_topo.= " e{$k}.eoar_arc_type='{$tab[$i]}|s|{$_na}'";
                // $_where_topo.= " AND e{$k}.eoar_arc_type='{$tab[$i]}|s|{$_na}'";

                // $i = $k+1;
                $this->traceActions("<<<<<<<<<<<<<$tab[$i]<<<<<<<<<<<<<<<",$this->na_min,"ok");

                if($tab[$i] !== $this->na_min){
                    $this->traceActions("<<<<<<<<<<<<$tab[$i]<<<<<<<$i<<<<<<<<<",$this->na_min,"erf");

                    $_where_topo.= ( $tab[$i] !== "" ) ? " e{$k}.eoar_id = e{$i}.eoar_id_parent" : "";
                    $_where_topo.= " AND SPLIT_PART(e{$k}.eoar_arc_type,'|s|',1)"
                                ." = SPLIT_PART(e{$i}.eoar_arc_type,'|s|',2)";
                }else{
                    $_where_topo.= " eorp_id = e{$k}.eoar_id";
                }
            }
        }

        // 28/01/2013 BBX
        // DE GIS Filter : Récupération de la sélection
        if(!empty($neList)) {
            $_where_topo .= " AND eorp_id IN ('".implode("','",$neList)."') ";
        }       

        $_where_topo.= " AND eorp_x IS NOT NULL AND eorp_y IS NOT NULL {$limits_vb} ";

        $n = count($tab)-2;

        $_where.= $_where_topo." AND eorp_id = e{$n}.eoar_id ";
        // Construction de la requete
        $sub_query_topo = $_select.$_from_topo.$_where;

		// 10/10/2012 MMT DE GIS 3D ONLY - utilisation de getNeRestrictionQuerySelect et getNeRestrictionQueryClause
        // pour limiter le nombre d'elements à afficher
        $sql =	 " SELECT AsSVG(t_gis.p_voronoi) AS svg_na, t_data.sort_value".$this->getNeRestrictionQuerySelect()
            ." , t_gis.na_value AS value $select_axe3 AsText(transform(t_gis.p_voronoi, 4326)) AS to_kml"
            ." FROM sys_gis_topology_voronoi AS t_gis"
            ." LEFT JOIN ($sub_query) AS t_data"
            ." ON t_gis.na_value=t_data.".$na
            ." RIGHT JOIN"
            ." ("
            .$sub_query_topo
            ." ) AS t_object"
            ." ON t_gis.na_value=t_object.".$na
            ." WHERE t_gis.na='".$na."' AND t_gis.p_voronoi IS NOT NULL";
            $sql .= $this->getNeRestrictionQueryClause();
            return $sql;
	} // End function generateQueryGraphPolygonsOnOtherNa()

    /**
     * Fonction qui génère les polygones sur le NA min
     * @param type $limits_vb Limites de la VB
     * @param type $sub_query sous requête
     */
    protected function generateConeOnNaMin( $limits_vb, $sub_query )
    {
        $this->tab_polygones['cone'] = array();
        
        // 28/01/2013 BBX
        // DE GIS Filter : Récupération de la sélection
        $neList = self::getNeListFromSelecteur($this->na_min, $this->id_prod);

        // 02/08/2007 - Modif. benoit : refonte de la requete de selection des polygones des cônes en ne selectionnant plus les polygones des cônes par leur intersection avec la viewbox (ne fonctionne pas sur des serveurs RedHat) mais par l'intersection des coordonnees x, y des polygones des na des cônes avec la viewbox
        // maj 20/05/2009 - MPR : Utilisation du nouveau module de Topologie - Modification des requetes SQL qui recupere les informations topologiques
        
        // 10/10/2012 MMT DE GIS 3D ONLY - utilisation de getNeRestrictionQuerySelect et getNeRestrictionQueryClause
        // pour limiter le nombre d'elements à afficher
        $sql =	 " SELECT eorp_id as ".$this->na_base.", t_data.sort_value, area2d(g1.p_voronoi) AS area_2d".$this->getNeRestrictionQuerySelect()
                ." ,e1.eorp_x as x, e1.eorp_y as y, e1.eorp_azimuth as azimuth,e1.eorp_latitude as latitude,e1.eorp_longitude as longitude "
                ." FROM edw_object_ref_parameters e1, sys_gis_topology_voronoi g1"
                ." LEFT JOIN ($sub_query) AS t_data ON g1.na_value=t_data.".$this->na_base
                ." WHERE g1.na = '".$this->na_base."' AND g1.p_voronoi IS NOT NULL"
                        ." AND eorp_x IS NOT NULL AND eorp_y IS NOT NULL";
        // On fait sauter les limites de la VB pour générer la miniature sur les cones
        if( $this->displayMode == 1 )
        {
            $sql.= " $limits_vb ";
        }
        $sql.=" AND eorp_id = g1.na_value";
        
        
        // 28/01/2013 BBX
        // DE GIS Filter : Récupération de la sélection
        if(!empty($neList)) {
            $sql .= " AND eorp_id IN ('".implode("','",$neList)."') ";
        }    

        $sql.= $this->getNeRestrictionQueryClause();

        $this->traceActions("definition des polygones reseau - graph / cones", $sql, "requete");

        $req = $this->database_connection->execute($sql);

        if ( $this->database_connection->getNumRows() > 0)
        {
            while($row = $this->database_connection->getQueryResults($req,1) )
            {
                // Definition du style
                $style = "style_defaut_cone";

                // Si une valeur du sort existe, on recherche a quel data_range elle appartient et on lui attribue le style associe
                if ( $row['sort_value'] != "" )
                {
                    for ($i=0; $i < count($this->data_range_values); $i++)
                    {
                        $data_range_value = $this->data_range_values[$i];
                        if ($row['sort_value'] >= $data_range_value['range_inf'] && $row['sort_value'] <= $data_range_value['range_sup'])
                        {
                            $style = $data_range_value['style_name'];
                        }
                    }
                }

                // Definition du polygone du cône
                $area_2d            = sqrt($row['area_2d']);
                $a			= ceil($area_2d*$this->internal_data['facteur_taille_voronoi']);
                $b			= ceil($a*abs(tan(deg2rad($this->internal_data['largeur_cone']))));
                $angle		= $row['azimuth']+$this->internal_data['angle_de_precision_voronoi'];

                //$path_cone	= 'd="M 0,10 L-'.$b.' -'.$a.' L'.$b.' -'.$a.'z" transform="translate('.$row['x'].', -'.$row['y'].') rotate('.$angle.')"';
                $path_cone	= 'd="M 0,0 L-'.$b.' -'.$a.' L'.$b.' -'.$a.'z" transform="translate('.$row['x'].', -'.$row['y'].') rotate('.$angle.')"';

                $tokml = "CONE(".$row['x']."||".$row['y']."@".$row['longitude']."||".$row['latitude']."||".$row['azimuth'].")";

                // 10/10/2012 MMT DE GIS 3D ONLY - ajout de la valeur 'value' dans tab_polygones['cone'] pour stoquer le code des elements
                $this->tab_polygones['cone'][] = array('path'=>$path_cone, 'style'=>$style, 'to_kml'=>$tokml, 'value'=>$row[$this->na_base] );
                $this->miniatureCones[] = $path_cone;
                $this->infosCone['style'][ $row[$this->na_base] ] = $style;

                $this->traceActions($row[$this->na_base],$this->infosCone['style'][ $row[$this->na_base] ],$style);
                $this->infosCone['value'][ $row[$this->na_base] ] = $row['sort_value'];
                $this->traceActions($row[$this->na_base],$this->infosCone['value'][ $row[$this->na_base] ],$row['sort_value']);

            }
        }
    } // End function generateConeOnNaMin()

    /**
     * Fonction qui génère les polygones issus d'un graphe
     * @param type $na
     * @param type $sql
     */
    protected function generateGraphPolygones( $na, $sql )
    {
        $this->traceActions("definition des polygones reseau - graph hors cones", $sql, "requete");
        $req = $this->database_connection->execute($sql);

        $nb_results =  $this->database_connection->getNumRows();
        if ( $nb_results > 0)
{
            $this->tab_polygones[$na] = array();
            while( $row = $this->database_connection->getQueryResults($req, 1) )
            {

                // Definition du style
                $style = "style_voronoi_defaut";

                // Si une valeur du sort existe, on recherche a quel data_range elle appartient et on lui attribue le style associe
                if ($row['sort_value'] != "")
    {
                    for ($i=0; $i < count($this->data_range_values); $i++)
                    {
                        $data_range_value = $this->data_range_values[$i];
                        if ($row['sort_value'] >= $data_range_value['range_inf'] && $row['sort_value'] <= $data_range_value['range_sup'])
                        {
                            $style = $data_range_value['style_name'];
    }
    }
        }
            // 27/12/2007 - Modif. benoit : ajout des informations 'na_value' et 'to_kml' dans les valeurs du tableau de polygones
            // 10/06/2008 - Modif.maxime : On recupere la valeur de l'element 3eme axe s'il existe
            $value_axe3 = ( isset($row['value_axe3'] ) ) ? $row['value_axe3'] : "" ;
            $this->tab_polygones[$na][] = array('path'=>'d="'.$row['svg_na'].'"', 'style'=>$style, 'sort' => $this->sort_value, 'sort_value' => $row['sort_value'], 'value'=>$row['value'], 'to_kml'=>$row['to_kml'], 'value_axe3'=>$value_axe3);
    }
        }

    } // End function generateGraphPolygones()

    /**
     * Fonction de definition des polygônes reseau du graph
     * @param type $na NA
     */
	function setGraphPolygones($na)
	{
            // On definit la table de donnees a utiliser
            $table_name = $this->getTableName( $na );

            // On definit la sous-requete de selection des valeurs des na et des sort_values
            $sub_query = $this->getSubQuery( $na, $table_name );

            // On défini les limites de la VB
            $cadre = $this->getViewBox();

            $x_min = $cadre[0];
            $x_max = $cadre[1];
            $y_min = $cadre[2];
            $y_max = $cadre[3];

            // 02/08/2007 - Modif. benoit : refonte de la requete de selection des polygones en ne selectionnant plus les polygones par leur intersection avec la viewbox (ne fonctionne pas sur des serveurs RedHat) mais par l'intersection des coordonnees x, y des polygones des na avec la viewbox
            // 27/12/2007 - Modif. benoit : ajout des informations 'value' et 'to_kml' dans la requete
            // 22/04/2008 - Modif. maxime : On supprime les limites de la vb pour l'export Google Earth
            // maj 20/05/2009 - MPR : Utilisation du nouveau module de Topologie - Modification des requetes SQL qui recupere les informations topologiques
            $limits_vb = (!$this->no_limit_vb) ? "		AND eorp_x >= $x_min AND eorp_x <= $x_max AND eorp_y >= $y_min AND eorp_y <= $y_max" : "";
		
            // 10/06/2008 - Modif.maxime : On recupere la valeur de l'element 3eme axe s'il existe
            $select_axe3 = ($this->axe3) ? ",t_data.value_axe3, ": ",";

		
            // maj 15/05/2009 - MPR : Gestion du nouveau module de topo
            if ( $na == $this->na_base ) 
            {
                if( $this->displayMode == 1 )
                {
                    $sql = $this->generateQueryGraphPolygonsOnNaMin($limits_vb, $select_axe3, $sub_query);
                    $this->generateGraphPolygones($na, $sql);
                }
                $this->generateConeOnNaMin($limits_vb, $sub_query );		
            }
            else 
            {
                $sql = $this->generateQueryGraphPolygonsOnOtherNa( $na, $sub_query, $select_axe3, $limits_vb );	
                $this->generateGraphPolygones($na, $sql);
            }
	} // End function setGraphPolygones()
		
    /**
     * Fonction qui génère la requête récupérant les polygones réseau sur le NA min à partir d'un lien depuis les alarmes
     * @param string $limits_vb Limites de la VB
     * @param type $sub_query Sous-requête
     */
    protected function generateQueryAlarmPolygonsOnNaMin($sql_case, $limits_vb, $sub_query)
    {
        $sql_head  = "SELECT AsSVG(sgtv.p_voronoi) AS svg_na, sgtv.na_value AS value, ";

        // 27/12/2007 - Modif. benoit : ajout des informations 'value' et 'to_kml' dans la requete
        $sql_head .= "AsText(transform(sgtv.p_voronoi, 4326)) AS to_kml,";

		// 09/10/2012 ACS DE GIS 3D ONLY
    $sql_body =	 " FROM sys_gis_topology_voronoi AS sgtv"
                ." LEFT JOIN ($sub_query) AS ea"
                ." ON sgtv.na_value = ea.na_value"
                ." RIGHT JOIN"
                ." ("
                            ."		SELECT DISTINCT eorp_id as ".$this->na_base." FROM edw_object_ref_parameters"
                ."		WHERE eorp_x IS NOT NULL AND eorp_y IS NOT NULL"
                .$limits_vb
                ." ) AS t_object"
                            ." ON sgtv.na_value = t_object.".$this->na_base
                            ." WHERE sgtv.na='".$this->na_base."' AND sgtv.p_voronoi IS NOT NULL";

        return "{$sql_head}{$sql_case}{$sql_body}";

    } // End function generateQueryAlarmPolygonsOnNaMin()

    /**
     * Fonction qui génère la requête récupérant les polygones réseau des NA > NA min depuis les alarmes
     * @param string $na NA
     * @param string $limits_vb limites de la VB
     * @param string $sub_query sous-requête
    */
    protected function generateQueryAlarmPolygonsOnOtherNa($na, $limits_vb, $sub_query )
    {
        $this->traceActions("****","SetAlarmPolygon on na > na_base****","****");

        $tab = $this->getAgregPathGIS($this->na_base, $na, $this->family);

        $this->traceActions("****",implode("/", $tab),"array");

        $id = count($tab)-2;
        // Construction de la premiere partie de la requete
        $sql  =  " SELECT AsSVG(sgtv.p_voronoi) AS svg_na,sgtv.na_value AS value, "
                ."		AsText(transform(sgtv.p_voronoi, 4326)) AS to_kml, "
                ."		CASE WHEN ea.critical_level != '' THEN 'style_alarm_'||ea.critical_level"
                ."		ELSE 'style_alarm_defaut' END AS style "
                ." FROM sys_gis_topology_voronoi AS sgtv LEFT JOIN ({$sub_query}) AS ea ON sgtv.na_value = ea.na_value "
                ." RIGHT JOIN"
                ." ("
                ." 		SELECT DISTINCT ea{$id}.eoar_id_parent as {$na}"
                ." 		FROM edw_object_ref_parameters ep";


        // Boucle pour jointure sur les elements reseau
        // On remonte du niveau agregation minimum  jusqu'au niveau d'agregation recherche (ex : sai -> rnc -> network)
        foreach( $tab as $k=>$_na )
        {
            if($k == 0 )
            {
                $_where_topo.= " AND ea{$k}.eoar_id = eorp_id ";
            }

            if( $k <= (count($tab)-2) )
            {
                $i = $k+1;
                $_na_sup = $tab[$i];
                $_from_topo .= ", edw_object_arc_ref ea{$k}";
                $_where_topo.= " AND ea{$k}.eoar_arc_type = '{$_na}|s|{$_na_sup}' ";

                if($k > 0 and $i <= count($tab)-2)
                    $_where_topo.= " AND ea{$i}.eoar_id =  ea{$k}.eoar_id_parent ";
            }
        }

		// 09/10/2012 ACS DE GIS 3D ONLY
        $sql.=  $_from_topo
                ." 		WHERE eorp_x IS NOT NULL AND eorp_y IS NOT NULL {$limits_vb} {$_where_topo} "
                ." ) AS t_object"
                ." ON sgtv.na_value = t_object.".$na
                ." WHERE sgtv.na='".$na."' AND sgtv.p_voronoi IS NOT NULL";

        return $sql;
    } // End function generateQueryAlarmPolygonsOnOtherNa()

    /*
     * 10/10/2012 MMT DE GIS 3D ONLY - ajout des fonctions
     * Si une restriction sur le nombre d'élément existe
     * ajoute la colonne isSortValueNull qui contient les valeures 1 ou 0 suivant si la valeur t_data.sort_value est null
     * on utilise cette colonne pour pouvoir faire un "order by t_data.sort_value nulls last" 
     */
    private function getNeRestrictionQuerySelect()
    {
        $sql = "";
        if ($this->limitNbElements > 0) {
            $sql =  " ,case when t_data.sort_value IS NULL THEN 1 ELSE 0 END AS isSortValueNull";
        }
        return $sql;
    }
    
    /*
     * 10/10/2012 MMT DE GIS 3D ONLY - ajout des fonctions
     * Si une restriction sur le nombre d'élément existe
     * ajoute la clause ORDER BY et LIMIT pour prendre en compte la limitation du nombre d'éléments
     */
    private function getNeRestrictionQueryClause()
    {
        $orderByValueColName = "t_data.sort_value";
        $sql = "";
        if ($this->limitNbElements > 0) {
			$sql .= " ORDER BY isSortValueNull ASC, $orderByValueColName ". $this->sortOrder; 
            $sql .= " LIMIT ".$this->limitNbElements;
		}
        return $sql;
    }
    
    /*
     * 10/10/2012 MMT DE GIS 3D ONLY - ajout des fonctions
     * retourne la liste des éléments réseaux selectionnés si une limite existe 
     * retourne false si aucune limite active
     */
    public function getNeListRestriction()
    {
        $ret = false;
        if ($this->getNeRestrictionQueryClause() != "") {
            $ret = array();
            if($this->tab_polygones){
            	// 22/07/2013 GFS - Bug 34887 - [QAL][CB531] : Voronoy Polygon is shift from its cell (GIS3D)
            	// les polygones n'était pas sur les cellules en cas de filtre sur le nombre d'éléments à afficher
                foreach ($this->tab_polygones['cell'] as $poly) {
                    if(array_key_exists( 'value',$poly)){
                        $ret[] = $poly['value'];
                    }
                }
            }
        }
        return $ret;
    }
    
    /**
     * 28/01/2013 BBX
     * DE GIS filtering
     * @param type $na
     * @param integer $idProd
     * @return type
     */
    public static function getNeListFromSelecteur($na, $idProd = null)
    {
        if(!empty(self::$listOfFilteredElements)) return self::$listOfFilteredElements;

        $neList = array();
        $parentList = array();
        
        // Mode dash uniquement
        if($_SESSION['gis_calling_method'] != 'dash') return $neList;
        
        if(!empty($_SESSION['TA']['selecteur']['ne_axe1'])) {
            // Parcours de la sélection
            foreach(explode('|s|',$_SESSION['TA']['selecteur']['ne_axe1']) as $nelsel) {
                list($nelselNA,$nelselNE) = explode('||', $nelsel);
                // Le NE se trouve le sur le NA affiché
                if($na == $nelselNA) $neList[] = $nelselNE;
                // Le NE appartient à un NA parent
                else $parentList[$nelselNA][] = $nelselNE;
            }
            // Récupération des NE issus des parents
            foreach($parentList as $naParent => $neParentList) {
                foreach(NeModel::getChildrenFromParents($naParent, $neParentList, $idProd) as $naChild => $child) {
                    if($na == $naChild) $neList = array_merge($neList,$child);
                }
            }
        }
        self::$listOfFilteredElements = array_unique($neList);
        return self::$listOfFilteredElements;
    }
    
    
    /**
     * Fonction de definition des polygônes reseau de l'alarme
     * @param type $na NA
     */
	function setAlarmPolygones($na)
	{
        // On construit la sous-requête
        $sub_query = $this->getSubQuery( $na );
                
		// On definit la requete complete de selection des polygones
		$cadre = $this->getViewBox();

        $x_min = $cadre[0];
        $x_max = $cadre[1];
        $y_min = $cadre[2];
        $y_max = $cadre[3];
		
		$limits_vb = (!$this->no_limit_vb) ? "		AND eorp_x >= $x_min AND eorp_x <= $x_max AND eorp_y >= $y_min AND eorp_y <= $y_max" : "";
                
        switch ( $this->mode )
        {
                case 'top-worst' :
                        $sql_case = " CASE WHEN ea.na_value != '' THEN 'style_alarm_up' ELSE 'style_alarm_defaut' END AS style";
                break;
                default :
                        $sql_case = " CASE WHEN ea.critical_level != '' THEN 'style_alarm_'||ea.critical_level ELSE 'style_alarm_defaut' END AS style";
		}
		
        //16/11/2012 MMT bz 30276 ajout gestion top-worst pour DE GIS 3D only
        $limitElementSQL = "";
        if ($this->limitNbElements > 0) {
            if ($this->mode == "top-worst"){
                // on recupère la définition de l'alarm top-worst
                $alarm = new AlarmModel($this->id_data_type, $this->mode,$this->id_prod);
                $sortField = $alarm->getSortField();
                $sortOrder = $sortField["list_sort_asc_desc"];

                $limitElementSQL = " ORDER BY style DESC, ea.topworst_value $sortOrder";
            } else {
                $limitElementSQL = " ORDER BY ea.critical_level ASC";
            }
            $limitElementSQL .= " LIMIT ".$this->limitNbElements;
        }
        
        if( $this->displayMode == 1 )
        {
            if( $this->na_base == $na  )
            {
                // 20/06/2011 NSE GIS without Polygons : ajout du paramètre $sql_case
                $sql = $this->generateQueryAlarmPolygonsOnNaMin( $sql_case, $limits_vb, $sub_query );
            }
            else
            {
                $sql = $this->generateQueryAlarmPolygonsOnOtherNa( $na, $limits_vb, $sub_query );
            }

            //10/10/2012 MMT DE GIS 3D ONLY limitation sur la criticité pour les alarmes
            //16/11/2012 MMT bz 30276 ajout gestion top-worst pour DE GIS 3D only
            $sql .= $limitElementSQL;
            
            $this->traceActions("definition des polygones reseau - alarme hors cones", $sql, "requete");
            $req = $this->database_connection->execute($sql);

            if ($this->database_connection->getNumRows() > 0)
            {
                $this->tab_polygones[$na] = array();
                // 20/06/2011 NSE GIS without Polygons : remplacement du foreach par un while
                while( $row = $this->database_connection->getQueryResults($req,1) )
                {
                    // 27/12/2007 - Modif. benoit : ajout des informations 'value' et 'to_kml' dans les valeurs du tableau de polygones
                    $this->tab_polygones[$na][] = array('path'=>'d="'.$row['svg_na'].'"', 'style'=>$row['style'], 'value'=>$row['value'], 'to_kml'=>$row['to_kml']);
                }
            }
        }


		// Si la na en cours est la na_base, on definit egalement les polygones des cônes
		if ( $na == $this->na_base ) 
                {
			$this->tab_polygones['cone'] = array();

			$sql_head =	 "SELECT sgtv.na_value, area2d(sgtv.p_voronoi) AS area_2d, eorp_x as x, eorp_y as y, eorp_azimuth as azimuth, eorp_latitude as latitude, eorp_longitude as longitude, ";

			// 26/02/2007 - Modif. benoit : correction de la requete. La jointure se fait sur na_base et non sur cell

			// 02/08/2007 - Modif. benoit : refonte de la requete de selection des polygones des cônes en ne selectionnant plus les polygones des cônes par leur intersection avec la viewbox (ne fonctionne pas sur des serveurs RedHat) mais par l'intersection des coordonnees x, y des polygones des na des cônes avec la viewbox

			/*$sql_body =  " FROM edw_object_1_ref e1, sys_gis_topology_voronoi AS sgtv"
						." LEFT JOIN ($sub_query) AS ea ON sgtv.na_value = ea.na_value"
						." WHERE sgtv.na='".$na."' AND sgtv.p_voronoi IS NOT NULL"
						." AND Intersects(sgtv.p_voronoi, GeometryFromText"
						." ('POLYGON(($x_min $y_min, $x_max $y_min, $x_max $y_max, $x_min $y_max, $x_min $y_min))',"
						." ".$this->srid.")) AND e1.".$this->na_base."=sgtv.na_value";*/
			
			// maj 23/05/2008 - maxime : ajout d'un parametre pour supprimer les limites de la vb ( necessaire pour l'export vers Google Earth)
			$limits_vb = (!$this->no_limit_vb) ? "		AND eorp_x >= $x_min AND eorp_x <= $x_max AND eorp_y >= $y_min AND eorp_y <= $y_max" : "";
		
			$sql_body =  " FROM edw_object_ref_parameters, sys_gis_topology_voronoi AS sgtv"
						." LEFT JOIN ($sub_query) AS ea ON sgtv.na_value = ea.na_value"
						." WHERE sgtv.na='".$na."' AND sgtv.p_voronoi IS NOT NULL"
						." AND eorp_x IS NOT NULL AND eorp_y IS NOT NULL"; 
            
            // Les limites de la VB sont supprimées pour gérer la miniature sur les cones
            if( $this->displayMode == 1 )
                $sql_body.=$limits_vb;

            $sql_body.=" AND eorp_id=sgtv.na_value";

			$sql = $sql_head.$sql_case.$sql_body;

            
            //10/10/2012 MMT DE GIS 3D ONLY limitation sur la criticité pour les alarmes
            //16/11/2012 MMT bz 30276 ajout gestion top-worst pour DE GIS 3D only
            $sql .= $limitElementSQL;
            
			$this->traceActions("definition des polygones reseau - alarme / cones", $sql, "requete");

			$req = $this->database_connection->execute($sql);

			if ($this->database_connection->getNumRows() > 0)
                        {
				while ( $row = $this->database_connection->getQueryResults($req,1) )
                                {

					$area_2d	= sqrt($row['area_2d']);
					$a			= ceil($area_2d*$this->internal_data['facteur_taille_voronoi']);
					$b			= ceil($a*abs(tan(deg2rad($this->internal_data['largeur_cone']))));
					$angle		= $row['azimuth']+$this->internal_data['angle_de_precision_voronoi'];

					//$path_cone	= 'd="M 0,10 L-'.$b.' -'.$a.' L'.$b.' -'.$a.'z" transform="translate('.$row['x'].', -'.$row['y'].') rotate('.$angle.')"';

					$path_cone	= 'd="M 0,0 L-'.$b.' -'.$a.' L'.$b.' -'.$a.'z" transform="translate('.$row['x'].', -'.$row['y'].') rotate('.$angle.')"';

					// On recupere les coordonnees des cônes pour les afficher
					$tokml = "CONE(".$row['x']."||".$row['y']."@".$row['longitude']."||".$row['latitude']."||".$row['azimuth'].")";
                    // 10/10/2012 MMT DE GIS 3D ONLY - ajout de la valeur 'value' dans tab_polygones['cone'] pour stoquer le code des elements
					$this->tab_polygones['cone'][] = array('path'=>$path_cone, 'style'=>$row['style'], 'to_kml'=>$tokml, 'value'=>$row['na_value']);
				
                    $this->miniatureCones[] = $path_cone;
                    $this->styleCone[ $row['na_value'] ] = $row['style'];
                    $this->infosCone['style'][ $row['na_value'] ] = $row['style'];
                    $this->infosCone['value'][ $row['na_value'] ] = ($row['style'] == "up") ? "up" : "down";
				}
			// __debug($sql,"sql");
			}
		}
	} // End function setAlarmPolygones()
    
	/**
     * Fonction de definition des polygônes geographiques
     */
	function setGeoPolygones()
	{
		$x_min = $this->view_box[0];
		$y_max = -1*$this->view_box[1];
		$x_max = $x_min+$this->view_box[2];
		$y_min = $y_max-$this->view_box[3];

		// 27/12/2007 - Modif. benoit : ajout des informations 'value' et 'to_kml' dans la requete
		// 25/03/2008 - Modif. benoit : ajout du champ 'type' dans la requete
		// 25/03/2008 - Modif. benoit : suppression de la condition sur l'Intersects (ne fonctionne pas sur les serveurs RedHat)

		$sql =	 " SELECT AsSVG(_geometry) AS svg_geo, nom AS value, AsText(transform(_geometry, 4326)) AS to_kml, type"
				." FROM sys_gis_data_polygon"
				." WHERE type IN"
				." ("
				." SELECT gis_vecteur.nom"
				." FROM sys_gis_config_vecteur AS gis_vecteur, sys_gis_config AS gis_conf"
				." WHERE gis_conf.id_palier = ("
				."	SELECT id FROM sys_gis_config_palier WHERE 	scale=".$this->current_zoom
				."	OR mainscale=".$this->current_zoom.")"
				." AND gis_conf.on_off=1 AND gis_conf.id_vecteur = gis_vecteur.id"
				." AND gis_vecteur.on_off = 1 ORDER BY layer_order ASC"
				." )"
				." AND ((xmin(_geometry) >= $x_min AND xmin(_geometry) <= $x_max)" 
				."		OR (xmax(_geometry) >= $x_min AND xmax(_geometry) <= $x_max))"
				." AND ((ymin(_geometry) >= $y_min AND ymin(_geometry) <= $y_max)"
				."		OR (ymax(_geometry) >= $y_min AND ymax(_geometry) <= $y_max))";
		
		
		$this->traceActions("definition des polygones geo", $sql, "requete");

		$req = $this->database_connection->execute($sql);

		if ($this->database_connection->getNumRows() > 0)
                {
                    while ( $row = $this->database_connection->getQueryResults($req,1) )
                    {
				// 27/12/2007 - Modif. benoit : ajout des informations 'na_value' et 'to_kml' dans les valeurs du tableau de polygones
				// 28/12/2007 - Modif. benoit : correction de la requete -> ajout d'une dimension supplementaire au tableau sinon, chaque type ne contient qu'un seul polygone
				//$this->tab_polygones[$row['type']] = array('path'=>$row['svg_geo'], 'style'=>"style_".$row['type'], 'value'=>$row['value'], 'to_kml'=>$row['to_kml']);
				// 25/03/2008 - Modif. benoit : correction du contenu du path dans le tableau de polygones
				
				$this->tab_polygones[$row['type']][] = array('path'=>'d="'.$row['svg_geo'].'"', 'style'=>"style_".$row['type'], 'value'=>$row['value'], 'to_kml'=>$row['to_kml']);
				
				$geo_layers[] = $row['type'];
			}
		}
	} // End function setGeoPolygones()

	// Fonction permettant de definir les raws/kpis de l'alarme et leurs labels associes
	function setAlarmSortName()
	{
		$this->alarm_rawkpi = array();

		// Definition des colonnes du raw/kpi principal en fonction du mode

		switch ($this->mode) {
			case 'static' :
				$primary_trigger	= 'alarm_trigger_data_field';
				$primary_type		= 'alarm_trigger_type';
			break;
			case 'dyn_alarm' :
				$primary_trigger	= 'alarm_field';
				$primary_type		= 'alarm_field_type';
			break;
			case 'top-worst' :
				$primary_trigger	= 'list_sort_field';
				$primary_type		= 'list_sort_field_type';
			break;
		}

		// Definition de la requete de selection des raws/kpis et de leurs labels

		$sql =	 " SELECT DISTINCT ".$primary_trigger." AS primary_trigger,"
				." additional_field AS secondary_trigger,";

		if ($this->mode == "top-worst") {
			$sql .=	 " alarm_trigger_data_field, "
					." (CASE alarm_trigger_type"
					."	WHEN 'raw' THEN (SELECT comment FROM sys_field_reference"
					."	WHERE edw_field_name = alarm_trigger_data_field)"
					."	WHEN 'kpi' THEN (SELECT kpi_label FROM sys_definition_kpi"
					."	WHERE kpi_name = alarm_trigger_data_field)"
					." END) AS trigger_legend,";
		}

		$sql .=	 " (CASE ".$primary_type
				." 	WHEN 'raw' THEN (SELECT comment FROM sys_field_reference"
				."  WHERE edw_field_name = ".$primary_trigger.")"
				." 	WHEN 'kpi' THEN (SELECT kpi_label FROM sys_definition_kpi"
				."  WHERE kpi_name = ".$primary_trigger.")"
				." END) AS primary_legend,"
				." (CASE additional_field_type"
				." WHEN 'raw' THEN (SELECT comment FROM sys_field_reference"
				."  WHERE edw_field_name = additional_field)"
				."  WHEN 'kpi' THEN (SELECT kpi_label FROM sys_definition_kpi"
				."  WHERE kpi_name = additional_field)"
				." END) AS secondary_legend"
				." FROM ".$this->table_name." alarm WHERE alarm_id='".$this->id_data_type."'";

		$this->traceActions("definition des raws/kpis et des labels de l'alarme", $sql, "requete");

		$req = $this->database_connection->execute($sql);

		if ($this->database_connection->getNumRows() > 0) {
			while ( $row = $this->database_connection->getQueryResults($req,1) )
            {
				if ((trim($row['primary_trigger']) != '') && (trim($row['primary_legend']) != '')) {
					$this->alarm_rawkpi[trim($row['primary_trigger'])] = trim($row['primary_legend']);
				}

				if ((trim($row['secondary_trigger']) != '') && (trim($row['secondary_legend']) != ''))
				{
					$this->alarm_rawkpi[trim($row['secondary_trigger'])] = trim($row['secondary_legend']);
				}

				if ((trim($row['alarm_trigger_data_field']) != '') && (trim($row['trigger_legend']) != ''))
				{
					$this->alarm_rawkpi[trim($row['alarm_trigger_data_field'])] = trim($row['trigger_legend']);
				}
			}
		}
	} // End function setAlarmSortName()

    /**
     * Fonction de mise a jour du GIS lors d'un zoom ou deplacement
     * @param type $new_x Nouveau X de la VB
     * @param type $new_y Nouveau Y de la VB
     * @param type $new_width Nouvelle taille de la VB
     * @param type $new_height Nouvelle hauteur de la VB
     */
	function majByViewBoxUpdate($new_x, $new_y, $new_width, $new_height)
	{
		$this->traceActions("**MAJ : ZOOM, DEPLACEMENT**", "**************", "**************");
		$this->startActionTimeStamp("viewbox_update");

		// maj de la view_box

		$this->view_box = array($new_x, $new_y, $new_width, $new_height);

		// maj du zoom courant

		if ($this->view_box_origine[2] > $this->view_box_origine[3]) {
			$zoom_box = $this->view_box_origine[2]/$this->view_box[2];
		}
		else 
		{
			$zoom_box = $this->view_box_origine[3]/$this->view_box[3];
		}

		$this->current_zoom	= $this->getCurrentZoom($zoom_box);

		$this->traceActions("current_zoom", $this->current_zoom, "**TEST**");

		// maj des layers et des styles geo (lies au zoom courant)

		$this->setGeoLayersAndStyles();

		// maj des polygones reseau et geo

		$this->setReseauPolygones();
		
		$this->setGeoPolygones();

		$this->stopActionTimeStamp("viewbox_update");
	} // End function majByViewBoxUpdate()

    /**
     * Fonction de mise a jour des dimensions d'une viewbox (courante ou initiale) par rapport a la taille de la fenetre de la vue du GIS
     * @param type $vb_to_update VB à mettre à jour
     * @param type $old_dims Ancienne coordonnées de la VB
     * @return type nouvelles coordonnées de la VB
     */
	function updateViewBoxFromView($vb_to_update, $old_dims)
	{
		$new_width	= $this->gis_width * ($vb_to_update[2]/$old_dims[0]);
		$new_height	= $this->gis_height * ($vb_to_update[3]/$old_dims[1]);
		
		$new_x		= $vb_to_update[0]-(($new_width-$vb_to_update[2])/2);
		$new_y		= $vb_to_update[1]-(($new_height-$vb_to_update[3])/2);

		return array($new_x, $new_y, $new_width, $new_height);
	} // End function updateViewBoxFromView()

    /**
     * Fonction permettant d'afficher des informations du GIS en fonction des coordonnees de la souris
     * @param double precision $mouse_x Position x de la souris
     * @param double precision $mouse_y Position y de la souris
     * @return string information sur le NE ou le layer geo de la position donnée
     */
	function showNaInformation($mouse_x, $mouse_y)
	{
		$info = "";

		switch ($this->layer_mouseover['type']) {
			case 'reseau'	:	$info = $this->showInfoReseau($mouse_x, $mouse_y);
			break;
			case 'geo'		:	$info = $this->showInfoGeo($mouse_x, $mouse_y);
			break;
		}
		return $info;
	} // End function showNaInformation()

	
	// Fonction qui gere le mapping de la topo
	/**
	* @param string $na_value 		: id du NE
	* @param string $na_value_label 	: label du NE 
	*/
	function getMappingLabel( $na_value, $na_value_label )
	{
		$query = " SELECT DISTINCT eor_id_codeq FROM edw_object_ref " 
				." WHERE eor_obj_type = '{$this->na}' AND eor_id = '{$na_value}'"
				." LIMIT 1";

		
		$mapping_id = $this->database_connection->getOne($query);
		
		if( $mapping_id !== "" and $mapping_id !== null )
		{
			$mapping_label = getNELabel($this->na, $mapping_id, $this->master_topo['sdp_id']);
			$this->traceActions("*************************",$mapping_label,"var");
			$mapping = array( 'id'=> $mapping_id, 'label' => $mapping_label);
			
			return $mapping;			
		}else
		{
			return array( 'id'=> $na_value, 'label' => $na_value_label);
		}
		
	}
	
    /**
     * Fonction d'affichage des infos au survol pour les elements reseaux
     * @param double precision $mouse_x position x de la souris
     * @param double precision $mouse_y position y de la souris
     * @return string informations sur le ou les NE sur la position de la souris
     */
	function showInfoReseau($mouse_x, $mouse_y)
	{
		if ($this->layer_mouseover["id"] == "cone") {
			$na = $this->na_base;
		}
		else
		{
			$na = $this->layer_mouseover["id"];
		}

		switch ($this->data_type) {
			case 'graph'	:

				if ($na != $this->na) {
					$table_name = $this->updateTableName($this->na, $na);
					
				}
				else
				{
					$table_name = $this->table_name;
				}

				// On definit la sous-requete de selection des valeurs des na et des sort_values

				// 18/07/2007 - Modif. benoit : modification de la sous-requete dans le cas d'une famille 3eme axe

				if (!$this->axe3) {
					$sub_query = " SELECT ".$na.", ".$this->sort_value." FROM ".$table_name." WHERE ".$this->ta."=".$this->ta_value;
					
					$column_for_axe3 = "";
					$_na_axe3 = "";
				}
				else 
				{

					$sub_query = " SELECT {$na}, {$this->na_axe3}, ".$this->sort_value."  FROM ".$table_name." WHERE ".$this->ta."=".$this->ta_value;

					if ($this->na_value_axe3 != "ALL") {
						$sub_query .= " AND {$this->na_axe3} = '{$this->na_value_axe3}'";
					}

					$column_for_axe3 = ", ".$this->na_axe3." AS label_axe3";
					// $_na_axe3 = ", CASE WHEN e_obj.".$this->na_axe3."_label IS NOT NULL THEN e_obj.".$this->na_axe3."_label ELSE e_obj.".$this->na_axe3." END AS label_axe3";
				
				}

				// 02/08/2007 - Modif. benoit : 

				// 17/09/2007 - Modif. benoit : ajout d'une limitation a un seul resultat a la sous-requete

				// 04/01/2008 - Modif. benoit : remplacement du srid -1 par celui stockee en base
				if($this->na_base == $na){
				
					$sql =	 " SELECT DISTINCT eor_id AS na_value FROM edw_object_ref"
							." WHERE eor_id = ("
							."	SELECT na_value FROM sys_gis_topology_voronoi"
							."	WHERE na='".$this->na_base."'"
							."	AND INTERSECTS(p_voronoi, GeometryFromText('POINT($mouse_x $mouse_y)', ".$this->srid."))"
							."	AND p_voronoi IS NOT NULL"
							."	LIMIT 1"
							." )";

					$row = $this->database_connection->getOne( $sql );
					$this->traceActions("requete d'infos sur les cones",$sql,"array");
				
				
				// maj 23/05/2008 -  maxime : Changement de la requete qui recupere les informations
				// 					 On se base toujours sur le niveau d'agregation reseau minimum pour recuperer les informations de la na en cours
				
				// maj 06/06/2008 - maxime : Correction du B6637 - On affiche dans data information pour les na > a na_base l'id du na dans les ()
				
					
					$_select = "SELECT e_obj.eor_label as label, e_obj.eor_id as n_value,";
					$_select.= "t_gis.na_value AS na_value, t_data.".$this->sort_value." AS sort_value";
					$_select.= $column_for_axe3;
					
					$_from = " FROM sys_gis_topology_voronoi AS t_gis, edw_object_ref AS e_obj ";
					$_from.= "LEFT JOIN ($sub_query) AS t_data ON e_obj.eor_id=t_data.{$na}";
					
					$_where = " WHERE t_gis.na='{$na}' AND t_gis.na_value = e_obj.eor_id AND e_obj.eor_obj_type = '{$na}' ";
					$_where.= "AND INTERSECTS(p_voronoi, GeometryFromText('POINT($mouse_x $mouse_y)', ".$this->srid."))";
					
					$_order = " ORDER BY t_data.".$this->sort_value;
					
				} else {
				
					// On recupere le chemin complet entre na et na_base
					$tab = $this->getAgregPathGIS($this->na_base, $na, $this->family);
					
					$_select = "";
					$_from = "";
					$_where = "";
					
					// --------------------------------------------------------------SELECT---------------------------------------------------------------------------//
					$_select = "SELECT DISTINCT eor_label as label, eor_id as na_value, eor_id as n_value, t_data.{$this->sort_value} AS sort_value $column_for_axe3";

					// --------------------------------------------------------------FROM-----------------------------------------------------------------------------//
					for($j=0;$j<=(count($tab)-2); $j++){
						$_from.= ( $_from == "" ) ? " FROM edw_object_arc_ref e{$j}" : ", edw_object_arc_ref e{$j} ";
					}

					$_from.= ", edw_object_ref LEFT JOIN ( {$sub_query} ) ";
					$_from.= " AS t_data ON eor_id=t_data.{$na}";

						// --------------------------------------------------------------WHERE---------------------------------------------------------------------------//
					for( $i=0; $i<=(count($tab)-2); $i++ ){

						$_where.= ($_where == "") ? " WHERE " : " AND ";
						$_where.= "e{$i}.eoar_arc_type = '{$tab[ $i ]}|s|{$tab[ $i+1 ]}'";
						
						// maj 20/08/2009 MPR : Correction du bug 11106 : Affichage des éléments rattachés aux cellules ayant des coordonnées
						if( $i < (count($tab)-2) )
							$_where.= " AND e{$i}.eoar_id_parent = e".($i +1).".eoar_id ";
					}

					$n = count($tab)-2;
					// Jointure avec la table edw_object_ref
					$_where.= " AND eor_obj_type ='{$na}' AND e{$n}.eoar_id_parent = eor_id ";
					$_where.= " AND e0.eoar_id IN ( SELECT na_value FROM sys_gis_topology_voronoi";
					$_where.= " WHERE na = '{$tab[0]}' ";
					$_where.= " AND INTERSECTS(p_voronoi, GeometryFromText('POINT($mouse_x  $mouse_y)', {$this->srid})) )"; 

					
					$_order = " ORDER BY t_data.".$this->sort_value;
					
					// $sql = $_select.$_from.$_where.$_order;
					
				}
				
				/*
				$sql =	 " SELECT ".$_na.$column_for_axe3.",t_gis.na_value AS na_value, t_data.".$this->sort_value." AS sort_value"
						//." , (SELECT ".$na."_label FROM edw_object_1_ref WHERE ".$na."=t_gis.na_value LIMIT 1)"
						//." AS label".$column_for_axe3
						." FROM sys_gis_topology_voronoi AS t_gis,  edw_object_1_ref AS e_obj"
						." LEFT JOIN ($sub_query) AS t_data ON e_obj.eor_id=t_data.".$na
						." WHERE t_gis.na='".$this->na_base."' AND t_gis.na_value = e_obj.eor_id".$this->na_base
						." AND INTERSECTS(p_voronoi, GeometryFromText('POINT($mouse_x $mouse_y)', ".$this->srid."))"
						// ." AND t_gis.na_value = '".$row['na_value']."'"
						." ORDER BY t_data.".$this->sort_value;
				*/
				$sql = $_select.$_from.$_where.$_order;
				
				$this->traceActions("infos on_mouseover graph", $sql, "requete");
				$req = $this->database_connection->execute($sql);
					
				if (!$this->database_connection->getLastError()) {
					$find		= false;
					$title		= array();
					
					$content = array();					
					
					$nb_results	= $this->database_connection->getNumRows();

					$this->traceActions("nb results ",$nb_results,"integer");
					if ($nb_results > 0) {
						$i = 0;
						while( $row = $this->database_connection->getQueryResults($req,1) )
                        {
							if($row['na_value'] != ""){
								
								// Ajout COntrole //
								if( $this->id_prod !== $this->master_topo['sdp_id'] ){
								
									$na_value = ($na == $this->na_base) ? $row['na_value'] : $row['n_value'];
									$mapping = $this->getMappingLabel( $row['na_value'], $row['label'] );
								
									$this->traceActions("----------------------", implode( "|||", $mapping ),"var");
									$row['label'] = $mapping['label'];									
									if($na == $this->na_base)
										$row['na_value'] = $mapping['id'];	
									else
										$row['n_value'] = $mapping['id'];									
								}
								
								if ($row['label'] != "") {
								
									$title[$i] = $row['label'];
									// maj 06/06/2008 - maxime : Correction du B6637 - On affiche dans data information pour les na > a na_base l'id du na dans les ()
									$title[$i].= ($na == $this->na_base) ? " (".$row['na_value'].")" : " (".$row['n_value'].")";
								}
								else
								{
									// maj 06/06/2008 - maxime : Correction du B6637 - On affiche dans data information pour les na > a na_base l'id du na dans les ()
									$title[$i] = ($na == $this->na_base) ? $row['na_value'] : $row['n_value'];
								}
								
								// 24/07/2007 - Modif. benoit : on redefini le contenu de la legende dans le cas de l'axe3 (on stocke en plus le label du 3eme axe de la na selectionnee)
								
								// 12/08/2009  - maj MPR : Correction du bug 6573
								$label_axe3 = getNELabel($this->na_axe3, $row['label_axe3'],$this->id_prod );
								$content[$i] = ($this->axe3) ? array($label_axe3, $row['sort_value']) : $content[$i] = $row['sort_value'];	
							
							}
							$i++;
						}
					}
					
					$sep = '|s|';
					if(count($content) > 0){
			
						// maj 11:13 18/02/2008 maxime : Gestion des elements reseau superposes
						
						for ($j=0; $j < count($content); $j++) {
							// __debug($this->data_range_values,"data_range");
							$i = 0;
							$find = false;
							$value = ($this->axe3) ? $content[$j][1] : $content[$j];
							while($i <= count($this->data_range_values) and !$find ) {
								$data_range_value = $this->data_range_values[$i];
								  
								if (($value >= $data_range_value['range_inf'] && $value <= $data_range_value['range_sup'])) {
									
									$style = $this->tab_styles[$i+2]['style_def'];
									
									$style_def = explode(";", $style);
									// __debug($style_def,"style_def");
									$this->traceActions($title[$j],$style,"style of element");
									$this->traceActions($data_range_value['range_inf'],$data_range_value['range_sup'],"conditions colors");
									foreach($style_def as $element){
										$style_tmp = explode(":", $element);
										$style_values[$style_tmp[0]] = $style_tmp[1];
									}
									$find = true;
								}
								$i++;
							}
							// __debug($style_values,$content[$j]);

							$rect_color = $style_values['stroke'].'-'.$style_values['fill'].'-'.($style_values['fill-opacity']*100);
							$content_str .= $title[$j].$sep;
							
							if($value != "" ){

								$content_str .=  (!$this->axe3) ? $rect_color.$sep.$this->sort_name." = ".$value : $rect_color.$sep.$this->sort_name." [".$content[$j][0]."] = ".$value;
								
							}else{
								if(!$this->axe3){
									$content_str .= "No Results";
								}else{
									$content_str .= ($content[$j][0] != "") ? "[".$content[$j][0]."] No Results" : "No Results";
								}
								// $content_str .= (!$this->axe3) ? "No Results" : ($content[$j][0] != "") ? "[".$content[$j][0]."] No Results" : "No Results";
							}
							if( $j < (count($content)-1) or $j == 1 )
								$content_str .=	$sep;
						}
						
					}
					else
					{
						$content_str .= "No Data Informations";
					}
					$content = $content_str;
				}

			break;
			case 'alarm'	:

				$trigger = array_keys($this->alarm_rawkpi);
				
				if($trigger[1]!==null and $trigger[1] !== ""){
					$condition_trigger = " AND trigger = '".$trigger[1]."'";
					$trigger_label = $this->alarm_rawkpi[$trigger[1]];
				}else{
					$condition_trigger = " AND trigger = '".$trigger[0]."'";
					$trigger_label = $this->alarm_rawkpi[$trigger[0]];
					
				}	

				if ($na != $this->na) {
					$id_data_type = $this->updateIdDataType($na);
				}
				else
				{
					$id_data_type = $this->id_data_type;
				}

				// 24/07/2007 - Modif. benoit : traitement du cas de l'axe3 dans la requete de jointure d'infos sur les alarmes
			$column_axe3 = "";
				if (!$this->axe3) {
					$na_axe3 = $na;

					$select_join			= "SELECT DISTINCT na_value,critical_level, value, trigger, field_type";
					$condition_join			= "";
					// $column_for_axe3		= "";
				}
				else 
				{
					$na_axe3 = $na;
					
					$select_join = "SELECT DISTINCT na_value AS na_value,critical_level, value, trigger, field_type, a3_value AS na_value_axe3";
					
					if ($this->na_value_axe3 != "ALL") {
						$condition_join .= " AND a3_value = '".$this->na_value_axe3."' AND a3 = '{$this->na_axe3}'";
					}
					else 
					{
						$condition_join = "";
					}
			
					$column_for_axe3 = ", na_value_axe3";
				}
				
				$this->traceActions(">>>",$trigger[0],"trigger");
				$this->traceActions(">>>",$trigger[1],"trigger");
				
				$condition_trigger = "";
				
				$condition_axe3 = " AND eorp_id=na_value";	
				
				if( $na == $this->na_base){
					$sql =	 " SELECT distinct e_obj.eor_id AS na_value,e_obj.eor_label as label,t_data.critical_level, t_data.value AS sort_value, trigger, field_type"
							// ." , (SELECT ".$na."_label FROM edw_object_1_ref WHERE ".$na."=e_obj.".$na." LIMIT 1)"
							.$column_for_axe3
							." FROM sys_gis_topology_voronoi AS t_gis, edw_object_ref AS e_obj"
							." LEFT JOIN"
							." ("
							." ".$select_join.", eorp_id as {$this->na_base}"
							." FROM edw_alarm, edw_alarm_detail,edw_object_ref_parameters"
							." WHERE na='".$na_axe3."'".$condition_join
							." AND ta='".$this->ta."' AND ta_value=".$this->ta_value
							." AND id_alarm='".$id_data_type."' AND alarm_type='".$this->mode."'"
							.$condition_trigger
							.$condition_axe3
							// ." AND na = '$na'"
							." AND edw_alarm.id_result = edw_alarm_detail.id_result"
							." ) AS t_data"
							." ON e_obj.eor_id = t_data.".$this->na_base
							." WHERE t_gis.na_value = e_obj.eor_id AND e_obj.eor_obj_type='".$this->na_base."'" 
							." AND t_gis.p_voronoi IS NOT NULL AND t_gis.na='".$this->na_base."'"
							."	AND INTERSECTS(p_voronoi, GeometryFromText('POINT($mouse_x $mouse_y)', ".$this->srid."))";
							// ." ".$this->alarm_rawkpi;
						
						// $this->traceActions("****",implode("/",array_keys($this->alarm_rawkpi)),"tab");
						// ." AND t_gis.na_value = '".$row['na_value']."'";
				}
				$this->traceActions("infos on_mouseover alarm", $sql, "requete");

				$req = $this->database_connection->execute($sql);
				if (!$this->database_connection->getLastError() ) {
					
					$content = array();
					$i = 0;
					
					while ( $row = $this->database_connection->getQueryResults($req,1) )
                    {
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

								// 24/07/2007 - Modif. benoit : dans le cas de l'axe3, on precise la valeur de la na 3eme axe concernee par les informations disponibles au survol

								if (isset($row['na_value_axe3']) && $row['na_value_axe3'] != "") {
									$label_axe3 = getNELabel($this->na_axe3, $row['na_value_axe3'],$this->id_prod );
									$axe3_value = " [".$label_axe3."]";
								}
								else 
								{
									$axe3_value = "";
								}
								
								// 18/06/2008 - Modif. benoit : correction du bug 6829. On affiche le label du trigger en fonction du tableau '$this->alarm_rawkpi' et l'on supprime le ";" apres chaque resultat car celui-ci etait considere lors de l'affichage comme un separateur de champs 
								
								//$rawkpi_content = $trigger_label.$axe3_value." [".$row['trigger']."] = ".$sort_value.";";

								$rawkpi_content = $this->alarm_rawkpi[$row['trigger']].$axe3_value." [".$row['trigger']."] = ".$sort_value;
								
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
					
					$this->traceActions("***",implode("/",$content),"tab");
					
					$sep = '|s|';
					
					if(count($content) > 0){
			
						// maj 11:13 18/02/2008 maxime : Gestion des elements reseau superposes
						
						for ($j=0; $j < count($content); $j++) {
						
							$i = 0;
							$find = false;
							$value = $content[$j];
							
							while($i <= count($this->data_range_values) and !$find ) {
								
								$this->traceActions("----> $i",$this->tab_styles[$i]['style_def'],"Yeeeeee style");
								
								$data_range_value = $this->data_range_values[$i];
								$this->traceActions($data_range_value['range_inf'],"range inf",$value);
								$this->traceActions($data_range_value['range_sup'],"range_sup",$value);
								// $this->traceActions(implode('||',implode('@',$data_range_value)),"data ranges $i","array");
															
								
								if ( "up (".$critical_level[$j].")" == $data_range_value['range_sup']   or  ($critical_level[$j] == "" and $data_range_value['range_sup'] == "down" and $value == "No Results") or $critical_level[$j] == "" and $data_range_value['range_sup'] == "up"){
									$style = $this->tab_styles[$i]['style_def'];
									
									$style_def = explode(";", $style);
									
									$this->traceActions($title[$j],$style,"styleS of element");
									$this->traceActions($data_range_value['range_inf'],$data_range_value['range_sup'],"conditions colors");
									foreach($style_def as $element){
										$style_tmp = explode(":", $element);
										$style_values[$style_tmp[0]] = $style_tmp[1];
									}
									$find = true;
								}
								/*
									$style = $this->tab_styles[$i]['style_def'];
									
									$style_def = explode(";", $style);
									
									$this->traceActions($title[$j],$style,"style of element");
									$this->traceActions($data_range_value['range_inf'],$data_range_value['range_sup'],"conditions colors");
									foreach($style_def as $element){
										$style_tmp = explode(":", $element);
										$style_values[$style_tmp[0]] = $style_tmp[1];
									}
									$find = true;
								}*/
							
								$i++;
							}
							
							
						// maj 12/06/2008 maxime : correction du bug 6409 : Affichage incorrect  d'un na 3eme axe lorsqu'il n'a pas de resultat
							$rect_color = ($style_values['stroke'] == "" ) ? "" : $style_values['stroke'].'-'.$style_values['fill'].'-'.($style_values['fill-opacity']*100);
							$content_str .= $title[$j].$sep;
							
							if($value != "" ){
								$rect = ($rect_color == "") ? "" : $rect_color.$sep;
								// $content_str .=  (!$this->axe3) ? $rect.$value : $rect.$this->sort_name." [".$content[$j][0]."] = ".$value;
								$content_str .=  $rect.$value;
								
							}else
								$content_str .= (!$this->axe3) ? "No Results" : ($content[$j][0] != "") ? "[".$content[$j][0]."] No Results" : "No Results";
							
							if( $j < (count($content)-1) or $j == 1 )
								$content_str .=	$sep;
						}
			
					}
					$this->traceActions("---",$content_str,"var");
					$content = $content_str;
					
				}
			break;
		}
		$this->traceActions("*-*-*-*",rawurlencode(implode(";",$title)."||".$content.";".$this->na),"title");
		
		return rawurlencode(implode(";",$title)."||".$content.";".$this->na);
	} // End function showInfoReseau()

	// maj 15/05/2009 - MPR : Ajout fonction qui recupere le niveau d'agregation voulu en fonction d'un sai donne  
	/** 
	* Fonction qui recupere le niveau d'agregation voulu en fonction d'un sai donne  
	* @param array $tab : tableau contenant le chemin complet de na_base au na recherche
	* @param string $na_value : valeur de la na_base
	* @return string $n_value
	*/
	function getNaValueFromNaBase( $tab, $na_value ){
	
		$_select = "";
		$_from = "";
		$_where = "";

		// --------------------------------------------------------------SELECT---------------------------------------------------------------------------//
		foreach($tab as $k=>$t) {
			if($k == 0){
				$_select = "SELECT e{$k}.eoar_id as $t, ";	
			} else {
				$i = $k-1;
				$_select_tmp[] = "e{$i}.eoar_id_parent as {$t}";
			}
		}
		$_select.= implode(", ",$_select_tmp);

		// --------------------------------------------------------------FROM-----------------------------------------------------------------------------//
		for($j=0;$j<=(count($tab)-2); $j++){
			$_from.= ( $_from == "" ) ? " FROM edw_object_arc_ref e{$j}" : ", edw_object_arc_ref e{$j} ";
		}

		// --------------------------------------------------------------WHERE---------------------------------------------------------------------------//
		for( $i=0; $i<=(count($tab)-2); $i++ ){

			$_where.= ($_where == "") ? " WHERE " : " AND ";
			$_where.= "e{$i}.eoar_arc_type = '{$tab[ $i ]}|s|{$tab[ $i+1 ]}'";
			
		}
		
		$n = count($tab)-2;
		$_where.= " AND e0.eoar_id = '{$na_value}'";

		$sql = $_select.$_from.$_where;
		$res = $this->database_connection->getAll($sql);
		
		$_na = $tab[ count($tab)- 1];
		$this->traceActions("Recuperation de l'element reseau de niveau $_na",$sql,"query");
		
		$n_value = (count($res)>0) ? $res[0][$na] : "";
	
		return $n_value;
	}
	
	/**
	 * Retourne le chemin d'agregation d'un niveau
	*
	* MPR 15/05/2009
	* @since cb4.1.0.0
	* @version cb4.1.0.0
	* @param string $family_min_net : niveau d'agregation minimum de la famille
	* @param string $family : famille
	* @param string $level : niveau dont on veut connaître le chemin jusqu'au
	* @return array : tableau contenant les niveaux d'agregations depuis $level jusqu'au niveau minimum
	*/
	function getAgregPathGIS($family_min_net, $level,$family) 
	{

		// Recuperation du chemin			
		$query = "SELECT * FROM get_path('{$level}','{$family_min_net}','{$family}');";
		$array_result = Array();
		
		$result = $this->database_connection->execute($query);
		
		while( $array = $this->database_connection->getQueryResults($result,1) ) {

			$array_result[] = $array['get_path'];
		}
		
		// Sauvegarde du resultat dans l'objet pour eviter de reexecuter les requetes si on cherche de nouveau les memes informations
		$agregPathArray = array_reverse($array_result);
		
		return $agregPathArray;
				
	} // End function getAgregPath
	
    /**
     * Fonction d'affichage des infos au survol pour les elements geo
     * @param type $mouse_x position x de la souris
     * @param type $mouse_y position y de la souris
     * @return string informations sur le layer geo à la position donnée
     */
	function showInfoGeo($mouse_x, $mouse_y)
	{
		$sql =	 " SELECT DISTINCT nom FROM sys_gis_data_polygon"
				." WHERE type='".$this->layer_mouseover["id"]."'"
				." AND Intersects(_geometry, GeometryFromText('POINT(".$mouse_x." ".$mouse_y.")',"
				." ".$this->srid."))";
	
		$row = $this->database_connection->getOne($sql);

		// 26/03/2008 - Modif. benoit : remise en forme des informations affichees lors du survol d'un vecteur geo

		($row['nom'] != "") ? $content = $row['nom'] : $content = "No results";

		return rawurlencode(ucfirst($this->layer_mouseover["id"])."||<b>".ucfirst($this->layer_mouseover["id"])."</b>|s|".$content);
	} // end function showInfoGeo()

    /**
     * Fonction de mise a jour du GIS par descente au prochain niveau de na (clic droit sur la map)
     * @return type
     */
	function majByNaDesc()
	{
		// 20/07/2007 - Modif. benoit : pour stopper la descente, on se base sur la na minimum et non sur la na base

		if($this->na == $this->na_min) return;

		$new_na = $this->selectNextNa();

		if($new_na == $this->na) return;

		$this->removeLayers(array($this->na));

		switch ($this->data_type) {
			case 'graph'	:	$this->table_name = $this->updateTableName($this->na, $new_na);
			break;
			case 'alarm'	:	$this->id_data_type = $this->updateIdDataType($new_na);
			break;
		}
		
		// $_SESSION['external_data'][1] = $new_na;
		// $_SESSION['sys_user_parameter_session'][$this->family]['gis_supervision']['na_level'] = $new_na;
		// $_SESSION['sys_user_parameter_session'][$this->family]['gis_supervision']['gis_nel_selecteur'] = "";

		// $this->traceActions("><<<<<<<<<<<<<<<<<",$_SESSION['external_data'][1],"var");
		// $this->traceActions("><<<<<<<<<<<<<<<<<",$_SESSION['sys_user_parameter_session'][$this->family]['gis_supervision']['gis_nel_selecteur'],"var");
		// $this->traceActions("><<<<<<<<<<<<<<<<<",$_SESSION['sys_user_parameter_session'][$this->family]['gis_supervision']['na_level'],"var");
		
		$old_na		= $this->na;
		$this->na	= $new_na;

		$this->setReseauStyles();
		$this->setReseauLayers();
		$this->setReseauPolygones();

		if ($this->layer_mouseover["id"] == $old_na) $this->setLayerMouseOver($this->na);

		// 26/03/2008 - Modif. benoit : on redefinit le style et les polygones geo lors de la descente dans les na

		$this->setGeoLayersAndStyles();
		$this->setGeoPolygones();
	} // End function majByNaDesc()

	// 28/12/2007 - Modif. benoit : redefinition de cette fonction qui retournait toujours la prochaine na de la famille principale ce qui provoquait des erreurs quand cette na n'appartenait pas a la famille courante (ex. famille pagrac où quand l'on descend de network on avait le niveau rnc qui n'appartient pas a cette famille)
    /**
     * Fonction permettant de definir le prochain niveau d'une na dans le cas d'une maj
     * @return type
     */
	function selectNextNa()
	{
		// Selection des na et de leur niveau source dans la famille principale
		
		$sql =	 " SELECT DISTINCT agregation, level_source, agregation_level FROM sys_definition_network_agregation"
				." WHERE family = (SELECT family FROM sys_definition_categorie WHERE main_family=1) ORDER BY agregation_level DESC";

		$req = $this->database_connection->getAll($sql);

		$na_in_main_family = array();

		if (count($req) > 0) {
			foreach ($req as $row) {
				$na_in_main_family[$row['agregation']] = $row['level_source'];
			}
		}

		// Selection des na et de leur niveau source dans la famille courante

		$sql =	 " SELECT DISTINCT agregation, level_source, agregation_level FROM sys_definition_network_agregation"
				." WHERE family = '".$this->family."' AND axe IS NULL ORDER BY agregation_level DESC";

		$req = $this->database_connection->getAll($sql);

		$na_in_family = array();

		if (count($req) > 0) {
			foreach ($req as $row) {
				$na_in_family[$row['agregation']] = $row['level_source'];
			}
		}

		// Definition de la prochaine na

		$next_na = $this->na;

		// Si la prochaine na de la famille est differente de la prochaine na dans la famille principale, on regarde si la prochaine na de la famille appartient aux na de la famille principale. Si c'est le cas, on definit celle-ci comme prochaine na. Dans tous les autres cas, on definit la prochaine na comme la na courante

		if ($na_in_family[$next_na] != $na_in_main_family[$next_na]) {
			if (in_array($na_in_family[$next_na], array_keys($na_in_main_family))) {
				$next_na = $na_in_family[$next_na];
			}
		}
		else 
		{
			$next_na = $na_in_family[$next_na];
		}
		
		return $next_na;
	} // End function selectNextNa()

    // 14/06/2011 - MPR : Suppression de la méthode checkCoordGeo() qui est obselete
    
	/**
     * Fonction de mise a jour du GIS par ajout/suppression de layers
     * @param string $action add ou del pour ajouter ou suppresser un layer
     * @param type $layers : Tableau du layers à ajouter ou supprimer
	*/
	function majByLayersUpdate($action, $layers)
	{
		switch ($action) {
			case 'add'	:	$this->addLayers($layers);
							$this->setReseauPolygones();
			break;
			case 'del'	:	$this->removeLayers($layers);
			break;
		}
	} // End function majByLayersUpdate()

	/**
     * Fonction de definition du nom d'une table de donnees pour une na a partir du nom de la table correspondant a une autre na (graph)
     * @param string $old_na ancien NA
     * @param string $new_na nouveau NA
     * @return string nouvelle table de données
     */
	function updateTableName($old_na, $new_na)
	{
		$table_name = explode('_', $this->table_name);

		for ($i=0; $i < count($table_name); $i++) {
			if ($table_name[$i] == $old_na) $table_name[$i] = $new_na;
		}

		return implode('_', $table_name);
	} // End function updateTableName()

	// Fonction de definition d'un id_data_type pour une na a partir de l'id_data_type correspondant a une autre na (alarm)
	function updateIdDataType($new_na)
	{
		$sql =	 " SELECT DISTINCT cible.alarm_id"
				." FROM ".$this->table_name." source, ".$this->table_name." cible"
				." WHERE source.alarm_id = ".$this->id_data_type
				." AND source.alarm_trigger_data_field = cible.alarm_trigger_data_field"
				." AND cible.alarm_id != source.alarm_id"
				." AND cible.network = '".$new_na."'";

		$this->traceActions("definition du nouvel id_data_type", $sql, "requete");

		$req = $this->database_connection->getAll($sql);

		if(count($req) > 0){
			$row = $req[0];
			$id_data_type = $row['alarm_id'];
		}
		else
		{
			$id_data_type = $this->id_data_type;
		}

		return $id_data_type;
	} // End function updateTableName()

    /**
     * Fonction d'ajout de layers
     * @param array $layers_added Layers à ajouter
     */
	function addLayers($layers_added)
	{
		// On determine le prochain index du tableau de layers (si il existe)

		if(count($this->tab_layers) > 0){
			$tab_order = array();
			foreach ($this->tab_layers as $id_layer=>$layer_content) {
				$tab_order[] = $layer_content['order'];
			}
			$next_order = max($tab_order)+1;
		}
		else
		{
			$next_order = 0;
		}

		// Ajout des layers dans le tableau de layers (Note : pour l'instant, on ne peut ajouter que des layers reseaux)

		for ($i=0; $i < count($layers_added); $i++) {
			if(!isset($this->tab_layers[$layers_added[$i]])){
				$this->tab_layers[$layers_added[$i]] = array('order'=>$next_order, 'border'=>1, 'background'=>1, 'type'=>"reseau");
				$next_order += 1;
				if ($layers_added[$i] == $this->na_base) {
					$this->tab_layers['cone'] = array('order'=>$next_order, 'border'=>1, 'background'=>1, 'type'=>"reseau");
					$next_order += 1;
				}
			}
		}
	} // End function addLayers()

    /**
     * Fonction de suppression de layers
     * @param array $layers_removed Layers à supprimer
     */
	function removeLayers($layers_removed)
	{
		//if (in_array($this->na_base, $layers_removed)) $layers_removed[] = "cone";

		for ($i=0; $i < count($layers_removed); $i++) {

			// Si le layer existe, on le supprime du tableau de layers

			if (isset($this->tab_layers[$layers_removed[$i]])){
				array_splice($this->tab_layers, array_search($layers_removed[$i], array_keys($this->tab_layers)), 1);
			}

			// On supprime egalement les polygones de ce layer

			if (isset($this->tab_polygones[$layers_removed[$i]])) {
				array_splice($this->tab_polygones, array_search($layers_removed[$i], array_keys($this->tab_polygones)), 1);
			}

			// Si le layer a supprimer possedait les infos "on mouseover", on les transmets au layer du na actif

			if ($layers_removed[$i] == $this->layer_mouseover['id']) $this->setLayerMouseOver($this->na);
		}
	} // End function removeLayers()

    /**
     * Fonction de mise a jour de l'ordre des layers
     * @param string $layer_up Layer en premier plan
     * @param string $layer_down en arrière plan
     */
	function majLayersOrder($layer_up, $layer_down)
	{
		$this->traceActions("MAJ ORDER", $layer_up." ".$layer_down, "**TEST**");

		$layer_up_order		= $this->tab_layers[$layer_up]['order'];
		$layer_down_order	= $this->tab_layers[$layer_down]['order'];

		$this->tab_layers[$layer_up]['order']	= $layer_down_order;
		$this->tab_layers[$layer_down]['order']	= $layer_up_order;

		//$this->setReseauPolygones();
	} // End function majLayersOrder() 

    /**
     * Fonction permettant de definir le layer qui possede les infos "on mouse over"
     * @param type $layer
     */
	function setLayerMouseOver($layer)
	{
		$this->layer_mouseover = array("id"=>$layer, "type"=>$this->tab_layers[$layer]['type']);
	} // End function setLayerMouseOver()

    /**
     * Fonction permettant de mettre a jour les proprietes ("background", "border") des layers
     * @param string $layer
     * @param string $background
     * @param string $border
     */
	function majLayersPptes($layer, $background, $border)
	{
		$this->tab_layers[$layer]['background']	= $background;
		$this->tab_layers[$layer]['border']		= $border;
	} // End function majLayersPptes()

    /**
     * Fonction appelee lors des mises a jour pour remettre a jour la variable de connexion a la base de donnees
     * @param type $id_prod
     */
	function updateDataBaseConnection($id_prod)
	{
		$this->database_connection = DataBase::getConnection( $id_prod );
	} // End function updateDataBaseConnection()

    /**
     * Fonction de tracage des actions de construction
     * @param string $action
     * @param string $comment
     * @param string $type
     */
	function traceActions($action, $comment, $type)
	{
		if ($this->debug == true) {
			$sql = "INSERT INTO sys_gis_trace(action, comment, type) VALUES('".addslashes($action)."', '".addslashes($comment)."', '".addslashes($type)."')";
			$req = $this->database_connection->execute($sql);
		}
	} // End function traceActions()

    /**
     * Fonction appelee au demarrage du tracage d'une action
     * @param string $action
     */
	function startActionTimeStamp($action)
	{
		$this->start_timestamp[$action] = $this->microtime_float();
	} // End function startActionTimeStamp()

	// Fonction appelee a la fin du tracage d'une action. Va retourner le temps d'execution de celle-ci

    /**
     * Fonction appelee a la fin du tracage d'une action. Va retourner le temps d'execution de celle-ci
     * @param string $action
     * @return real temps d'exécution total
     */
	function stopActionTimeStamp($action)
	{
		return ($this->microtime_float()-$this->start_timestamp[$action]);
	}

        /**
         * Fonction de traitement du temps
         * @return float 
         */
	function microtime_float()
	{
	   list($usec, $sec) = explode(" ", microtime());
	   return ((float)$usec + (float)$sec);
	}

	
	/**
     * Fonction qui génère un fichier kmz/kml (GIS 3D)
     * @param gisExec $gis_instance
     * @return string path du fichier généré
     * 
     * 28/01/2008 maxime - Export vers Google Earth -> Creation du fichier KML
         */
	function create_kml_file($gis_instance){
	
		
		$this->traceActions("repertoire physique niveau 0",REP_PHYSIQUE_NIVEAU_0,"var");
		
		
		// On cree un fichier d'extension kml // Son extension deviendra kmz a la suite d'un zip du fichier kml pendant la generation du fichier
		$file = REP_PHYSIQUE_NIVEAU_0."gis/gis_temp/export_gearth_".date('dmYHis')."_".rand(5, 15).".kml";
	
		$this->traceActions("Creation du fichier kml",$file,"path");
		$this->traceActions("appel de la classe kmlRender",REP_PHYSIQUE_NIVEAU_0."gis/gis_class/KMLRender.php","path");
		
		include_once(REP_PHYSIQUE_NIVEAU_0."gis/gis_class/KMLRender.php");
		
		$kml = new KMLRender($this);
		
		// $file = $kml->getFileOut(); // maj 15/04/2008, maxime : L'extension du fichier est kmz au lieu de kml
				
		return $kml;
	}
	
	// maj 27/05/2008 Benjamin : Ajout de la fonction getGisConfig qui retourne les opacites definies dans sys_gis_config_global. BZ6257
	/**
     * Retourne un tableau associatif dy type : Array("fill-color"=>"#21345456","stroke-color"=>"#456123","fill-opacity"=>"0.3","stroke-opacity"=>"0.6")
     * @return array
     */
	function getGisConfig()
	{
		$query = "SELECT style_voronoi_defaut FROM sys_gis_config_global LIMIT 1";
		$result = $this->database_connection->getOne($query);
		$array = $result;
		
		list($fill_color_complete,$stroke_color_complete,$fill_opacity_complete,$stroke_opacity_complete) = explode(";",$result);
		list($label,$fill_color) = explode(":",$fill_color_complete);
		list($label,$stroke_color) = explode(":",$stroke_color_complete);
		list($label,$fill_opacity) = explode(":",$fill_opacity_complete);
		list($label,$stroke_opacity) = explode(":",$stroke_opacity_complete);
		return Array("fill-color"=>$fill_color,"stroke-color"=>$stroke_color,"fill-opacity"=>$fill_opacity,"stroke-opacity"=>$stroke_opacity);
	}
}

	// Test
/*
	$database_connection = pg_connect("host=localhost port=5432 dbname=mpr_iu_gis user=postgres password=''");

	$external_data = array();

	$external_data['na'] = 'bsc';
	$external_data['na_value'] = 'ALL';//'14727_12707';
	$external_data['na_base'] = 'cell';
	$external_data['data_type'] = 'graph';//'alarm';
	$external_data['mode'] = 'static';
	$external_data['family'] = 'efferl';
	$external_data['ta'] = 'day';
	$external_data['ta_value'] = 20061008;//20060403;
	$external_data['id_data_type'] = 62;//69;//323;//
	$external_data['module'] = 'gsm';
	$external_data['alarm_color'] = array('critical'=>"#FF0000", 'major'=>"#fab308", 'minor'=>"#f7fa08");
	$external_data['sort_type'] = 'kpi';
	$external_data['sort_id'] = 123456789;//739;	
	$external_data['sort_name'] = '% failure HO outgoing';//'% Call drops NSS BSS';
	$external_data['sort_value'] = 'kpi_ho_inter_out_fail_rate';
	$external_data['table_name'] = 'edw_gsm_efferl_axe1_kpi_bsc_day';

	$gis_exec = new gisExec($external_data);

	//$gis_exec->majByLayersUpdate("add", array('cell'));

	$gis_exec->majLayersPptes('bsc', false, true);

	include 'displayTotal.php';

	$gis_display = new displayTotal($gis_exec->view_box, $gis_exec->view_box_origine, $gis_exec->gis_side, $gis_exec->slide_duration, $gis_exec->tab_zoom, $gis_exec->current_zoom, $gis_exec->tab_styles, $gis_exec->tab_layers, $gis_exec->tab_polygones, $gis_exec->raster);

	$handle = fopen('../gis_temp/test.svg', 'w+');
	fwrite($handle, $gis_display->output);
	fclose($handle);
*/
/* 
gis_data=1|@|sai|@|7336_502|@|sai|@|graph|@||@|ept|@|day|@|20080612|@|cs_calls_drop_cn|@|iu|@||@|kpi|@|sdk.bfb90b2009fc25.29150079|@|CS%20Calls%20Drop%20Core%20Network%20(%)|@|69|@|edw_iu_ept_axe1_kpi_sai_day|@|
*/
?>