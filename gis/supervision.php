<?php
/**
 * 
 * @cb51
 * 
 * 27/07/2010 BBX : Ajout des scripts prototype. BZ 16675
 * 22/11/2011 NSE bz 24774 : ajout du produit en paramètre lors de l'appel de la méthode GisModel::getGisDisplayMode()
 * 
 */
?><?php
/*
 *  @cb5.0.4.00
 *
 *  17/08/2010 NSE DE Firefox bz 16932 : page blanche au lancement de Gis Supervision
 */
?><?php
/**
 *
 * @cb40000@
 *
 * 	14/11/2007 - Copyright Acurio
 *
 * 	Composant de base version cb_4.0.0.00
 *
 *
 */
?>
<?php

session_start();

include_once(dirname(__FILE__)."/../php/environnement_liens.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/select_family.class.php");

// 17/08/2010 NSE DE Firefox bz 16932 on utilise header('Location, il faut donc supprimer les affichage faits avants (on les rentres dans le if)
if ( !isset($_GET["family"]) ) {
    // 17/08/2010 NSE DE Firefox bz 16932 : il faut inclide prototype pour fenetres_volantes.
    ?>
<html>
<head>
	<title>GIS Supervision</title>
    <script type='text/javascript' src='<?=NIVEAU_0?>js/prototype/prototype.js'> </script>
	<script type='text/javascript' src='<?=NIVEAU_0?>js/prototype/window.js'> </script>
	<script type='text/javascript' src='<?=NIVEAU_0?>js/prototype/scriptaculous.js'> </script>
	<script src="<?=NIVEAU_0?>js/gestion_fenetre.js" ></script>
	<script src="<?=NIVEAU_0?>js/fenetres_volantes.js" ></script>
	<script src="<?=NIVEAU_0?>js/fonctions_dreamweaver.js"></script>
	<link rel="stylesheet" href="<?=NIVEAU_0?>css/pauto.css" type="text/css"/>
	<link rel="stylesheet" href="<?=NIVEAU_0?>css/global_interface.css" type="text/css"/>
</head>
<body>
<?php

    // maj 10/06/2011 - MPR : DEV GIS without Polygons : Prise en compte de gis_display_mode
    $condition = "AND family IN (
                    SELECT DISTINCT family
                    FROM sys_definition_network_agregation
                    WHERE agregation";
    // 22/11/2011 NSE bz 24774 : ajout du produit en paramètre
    if( GisModel::getGisDisplayMode($_GET['product']) == 1 )
    {
        $condition.= "    IN (
                            SELECT agregation
                            FROM sys_definition_network_agregation
                            WHERE voronoi_polygon_calculation = 1
                           )";
    }
    else
    {
        $condition.= "    = (
                            SELECT network_aggregation_min
                            FROM sys_definition_categorie WHERE main_family = 1
                            )";
    }
    $condition.= ")";

    session_unregister('external_data');
    session_unregister('miniature_path');
    session_unregister('sys_user_parameter_session');
    session_unregister('gis_miniature');

    // if( isset($_GET['action']) and isset( $_GET['family']) ){
            // $url = "supervision.php";
    // }

    // maj 27/08/2009 - Correction du bug 11246 : Ajout d'une valeur par défault pour GIS Supervision
    $url_params = ( isset($_GET['product']) ) ? "action=choose_family&autorefresh=true&product=".$_GET['product'] : "action=choose_family";
    $select_family = new select_family("supervision.php", $url_params ,"GIS Supervision", false, $condition);

    exit;
}

$family = $_GET['family'];
$product = $_GET['product'];
$action = "choose_family";

// 09:46 03/09/2009 MPR - Correction du bug 11367
$url_params = "action=choose_family&autorefresh=true&family=".$_GET['family']."&product=".$_GET['product'];
// 17/08/2010 NSE DE Firefox bz 16932 : on remplace <script>window.location par la version php
header('Location: '.NIVEAU_0."gis/index.php?".$url_params);
?>
</body>
</html>