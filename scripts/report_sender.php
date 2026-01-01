<?php
/**
 * Script report sender. Ce script envoie les schedules. Il est normalement
 * appelé via cron mais peut l'être également manuellement (produit blanc)
 *
 * @author	SLC - 27/03/2009
 * @version CB 4.1.0.0
 * @since	CB 4.1.0.0
 *
 * 08/07/2009 BBX : Correction des includes
 * 19/04/2011 OJT : Ajout de la gestion du Produit Blanc
 */

// Starting Session
//@session_start();

// INCLUDES
include_once(dirname(__FILE__).'/../php/environnement_liens.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'php/edw_function_family.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'class/PHPOdf.class.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'class/DashboardExport.class.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'php/debug_tools.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'class/Date.class.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'class/Exporter.class.php');
require_once(MOD_SELECTEUR.'php/SelecteurDashboard.class.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'dashboard_display/class/DashboardData.class.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'dashboard_display/class/GtmXml.class.php');
include_once(MOD_CHARTFROMXML.'class/graph.php');
include_once(MOD_CHARTFROMXML.'class/SimpleXMLElement_Extended.php');
include_once(MOD_CHARTFROMXML.'class/chartFromXML.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'class/libMail.class.php');

// Initialisation de l'offset day (manuel, auto, auto pour ProduitBlanc)
if( isset( $_POST['date'] ) )
{    
    // On test le format de la date
    if( preg_match( "#^(19|20)[0-9]{2}/(0[1-9]|1[012])/(0[1-9]|[12][0-9]|3[01])$#", $_POST['date'] ) !== 1 )
    {
        displayInDemon( 'Error, invalid date format (please use \'yyyy/mm/dd\')' );
    }
    else
    {
        // On test ensuite la validité de la date (en plus du format)
        $dateSplit = explode( '/', $_POST['date'] );
        if( !checkdate( intval( $dateSplit[1] ), intval( $dateSplit[2] ), intval( $dateSplit[0] ) ) )
        {
            displayInDemon( 'Error, invalid date' );
        }
        else
        {
            // On test enfin la validité par rapport à l'historique mois configuré
            $monthHistory = ProductModel::getMaxHistory( 'month', array( ProductModel::getProductId() ) ) * 30;
            $nbDayDiff = Date::getDatesDiff( date( 'Ymd'), str_replace( '/', '', $_POST['date'] ) );
            if( $nbDayDiff > $monthHistory )
            {
                displayInDemon( 'Error, reports can not be launch on a date prior to your month history' );
            }
            else
            {
                // Aucune erreur détectée, on initialise l'offset day
                $offset_day = Date::getOffsetDayFromDay( $_POST['date'] );
            }
        }
    }
}
else if( ProductModel::isBlankProduct( ProductModel::getProductId() ) )
{
    // Dans le cas d'un lancement via les cron d'un ProduitBlanc
    displayInDemon( 'Automatic start of Report Sender script for BlankProduct' );
    $offset_day = 1; // Date de la veille
}
else
{
    // Dans le cas d'un lancement via les cron standards
    displayInDemon( 'Automatic start of Report Sender script for standard product' );
    $offset_day = get_sys_global_parameters( 'offset_day' );
}


// Si on est dans un contexte web (script non lancé par cron ou A.J.A.X)
if( $_SERVER["SERVER_NAME"] && !isset( $_POST['date'] ) )
{
	echo "
		<link rel='stylesheet' type='text/css' href='". NIVEAU_0 ."css/global_interface.css'/>
		<link rel='stylesheet' type='text/css' href='". NIVEAU_0 ."css/graph_style.css'/>
		<style type='text/css'>
		* {
			font-family: tahoma;
			font-size: 12px;
		}
		div.sql {
			border: 2px solid orange;
			background: #FE9;
			padding: 4px;
		}
		</style>
        <style type='text/css'> .sql_debug { display:none; } </style>
	";
}

// Test si l'offset_day a été défini (pas d'erreur)
if( isset( $offset_day ) )
{
    if ( get_sys_global_parameters( "automatic_email_activation", 0 ) == 1 )
    {
        // Dans le cas d'un lancement manuel (par A.J.A.X., on bufferise la sortie)
        if( isset( $_POST['date'] ) )
        {
            ob_start();
        }

        $id_user = -1;	// on considère tout le temps que l'utilisateur est -1 (même si /scripts/content_sender_v2.php est lancé par un utilisateur logué)
        $myExporter = new Exporter( $offset_day );
        $myExporter->exportSchedules();
        ob_end_clean();

        if( isset( $_POST['date'] ) )
        {
            ob_end_clean();
        }
    }
    else
    {
        echo "Error, sending e-mail is disabled";
    }
}
