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

	- maj 05/07/2007, benoit : suppression du 'geomunion()' pour construire la miniature et remplacement de          celui-ci par le simple affichage de tous les polygones de niveau minimum

	- maj 06/07/2007, benoit : ajout de la condition "agregation = na" dans la requete de construction de la         miniature de manière à selectionner le niveau maximum présent dans 'sys_gis_topology_voronoi'

	- maj 20/07/2007, benoit : modification de la requete de selection des polygones de la miniature en remplacant   le nom de la famille par une sous-requete de recherche du nom de la famille principale afin de prendre en      compte les familles != famille principale

	- maj 20/07/2007, benoit : dans la requete de selection des polygones de la miniature, mise en commentaire de    la condition "agregation = na" qui faisait retourner tous les niveaux d'agregation et non un seul comme prévu   précedemment

	- maj 03/08/2007, benoit : dans la requete de selection des polygones de la miniature, suppression de la         condition exluant le niveau minimum de la sous-requete et rajout d'une condition sur la presence de            l'agregation dans les resultats de 'sys_gis_topology_voronoi'

*/
?>
<?php

session_start();

include_once("../../php/environnement_liens.php");

include '../gis_class/gisExec.php';

function generateOutput($view_box, $mini_side, $global_polygon, $style)
{
    $view_box_str = implode(' ', $view_box);

    $output	 = '<svg id="root" x="0" y="0" width="'.$mini_side.'" height="'.$mini_side.'" viewBox="'.$view_box_str.'" zoomAndPan="disable">';
    // 20/06/2011 NSE : merge Gis without polygons
    $output	.= '<rect x="'.$view_box[0].'" y="'.$view_box[1].'" width="'.$view_box[2].'" height="'.$view_box[3].'" fill="white"/>';
    foreach($global_polygon as $poly)
    {
        $output .='<path '.$poly.' style="'.$style.'"/>';
    }
    // 24/10/2007 - Temp.
    //$output	.= '<rect x="-18343.86" y="13799.745" width="30000" height="30000" fill="blue"/>';

    $output	.= '</svg>';

    return $output;
}

function generateRaster($output)
{
    $raster_name = "mini_".date('dmYHis')."_".rand(5, 15);

    $raster = "../gis_temp/".$raster_name;

    $handle = fopen($raster.'.svg', 'w+');
    fwrite($handle, $output);
    fclose($handle);

    // 20/05/2010 NSE : relocalisation du module batik dans le CB + modification chemin JDK
    // $raster_cmd = "/opt/jdk1.5.0_02/bin/java -Djava.awt.headless=true -Xmx512m -jar /home/batik/batik-rasterizer.jar -bg 255.255.255.255 ".$raster.".svg";
    $raster_cmd = "/usr/java/jdk1.5.0_02/bin/java -Djava.awt.headless=true -Xmx512m -jar ".REP_PHYSIQUE_NIVEAU_0."modules/batik/batik-rasterizer.jar -bg 255.255.255.255 ".$raster.".svg";

    exec($raster_cmd);
    // 20/06/2011 NSE : merge Gis without polygons
    //if(is_file($raster.".svg")) unlink($raster.".svg");

    return "gis_temp/".$raster_name.".png";
}

if (!((isset($_SESSION['miniature_path'])) && (is_file("../".$_SESSION['miniature_path'])))) {

    if (isset($_SESSION['gis_exec'])) {

        $mini_side = $_GET['side'];

        $gis_instance = unserialize($_SESSION['gis_exec']);


        $view_box_origine	= $gis_instance->view_box_origine;
        $view_box			= $gis_instance->view_box;

        $ratioVB_x = $view_box_origine[2]/$mini_side;
        $ratioVB_y = $view_box_origine[3]/$mini_side;

        $width	= 5*$ratioVB_x;
        $height	= 5*$ratioVB_y;

        $x = $view_box[0]+($view_box[2]-$width)/2;
        $y = $view_box[1]+($view_box[3]-$height)/2;

        // 05/07/2007 - Modif. benoit : suppression du 'geomunion()' pour construire la miniature et remplacement de celui-ci par le simple affichage de tous les polygones de niveau minimum

        // 06/07/2007 - Modif. benoit : ajout de la condition "agregation = na" dans la requete de manière à selectionner le niveau maximum présent dans 'sys_gis_topology_voronoi'

        // 20/07/2007 - Modif. benoit : modification de la requete en remplacant le nom de la famille par une sous-requete de recherche du nom de la famille principale afin de prendre en compte les familles != famille principale

        // 20/07/2007 - Modif. benoit : mise en commentaire de la condition "agregation = na" qui faisait retourner tous les niveaux d'agregation et non un seul comme prévu précedemment

        // 03/08/2007 - Modif. benoit : suppression de la condition exluant le niveau minimum de la sous-requete et rajout d'une condition sur la presence de l'agregation dans les resultats de 'sys_gis_topology_voronoi'
        // 20/06/2011 NSE : merge Gis without polygons
        if( $gis_instance->displayMode == 1 )
        {
			$sql =	 " SELECT AsSVG(p_voronoi) AS global_polygon FROM sys_gis_topology_voronoi"
					." WHERE na ="
					." ("
					."		SELECT agregation FROM sys_definition_network_agregation"
					."		WHERE family=(SELECT family FROM sys_definition_categorie WHERE main_family = 1)"
					."		AND voronoi_polygon_calculation=1"
					//."		AND agregation<>'".$gis_instance->na_base."'"
					."		AND on_off=1"
					."		AND agregation IS NOT NULL AND agregation<>''"
					."		AND agregation IN (SELECT DISTINCT na FROM sys_gis_topology_voronoi)"
					."		ORDER BY agregation_rank DESC LIMIT 1"
					." )";

			// $gis_instance->updateDataBaseConnection($gis_instance->id_prod);
			$gis_instance->traceActions("req. generation miniature", $sql, "requete");

            $req = $gis_instance->database_connection->execute($sql);

            $global_polygon = array();

            // maj 17/06/2011 - MPR :   Lorsque les polygones sont disponibles, on construit la miniature dessus
            //                          Sinon on construit la miniature à partir des cones

            while( $row = $gis_instance->database_connection->getQueryResults($req,1) )
            {
                    $polygons[] = $row['global_polygon'];
            }
            $global_polygon = array('d="'.implode(" ", $polygons).'"');
        }
        else
        {
            $global_polygon = array();

            foreach( $gis_instance->miniatureCones as $path_cone )
            {
                    $gis_instance->traceActions("Cones",$path_cone,"Cones");
                    $global_polygon[] = $path_cone;
        	}
    	}
        $_SESSION['miniature_path'] = generateRaster(generateOutput($view_box_origine, $mini_side,  $global_polygon, $gis_instance->internal_data['style_voronoi_defaut']));
    }
}

echo $_SESSION['miniature_path'];

?>