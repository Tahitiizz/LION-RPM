<?
/**
 * @version cb5.0.4
 *
 *
 *  15/10/2009 MPR : Ajout des colonnes trx et charge pour Downnload Topology
 *  09/07/2009 MPR : Correction du bug 9601 : Erreur JS IE6
 *  13/08/2010 OJT : Optimisation générale du script pour la DE Firefox
 *                   Correction bz17026
 *  16/02/2011 MMT DE Query Builder : utilisation de export_display_download.php pour generation HTML
 */
	// INCLUDES.
	include_once("environnement_liens.php");
	include_once( REP_PHYSIQUE_NIVEAU_0 . "php/database_connection.php");
	include_once( REP_PHYSIQUE_NIVEAU_0 . "php/edw_function_family.php");
	include_once( REP_PHYSIQUE_NIVEAU_0 . "php/export_display_download.php");

	foreach($_GET['fields'] as $field)
    {
		$url_arg.= "&fields%5B%5D=".$field;
	}

	if(isset($_GET['coordinates']) )
    {
		foreach($_GET['coordinates'] as $field)
        {
			$url_arg.= "&coordinates%5B%5D=".$field;
		}
	}

	// 15/10/2009 - MPR : Ajout des paramètres trx et charge
	if( isset( $_GET['paramsErlang'] ) )
    {
		foreach($_GET['paramsErlang'] as $field)
        {
			$url_arg.= "&paramsErlang%5B%5D=".$field;
		}
	}

	//16/02/2011 MMT DE Query Builder : utilisation de export_display_download.php pour generation HTML
	// factorisation du code
	$url = "export_csv.php?product=".$_GET['product']."&family=".$_GET['family']."&type_header=".$_GET['type_header'];
	$url .= "&na_type=".$_GET['na_type'].$url_arg;

	displayFileGenerationAndDownload($url,"Download Topology","Building CSV File ...");
?>
