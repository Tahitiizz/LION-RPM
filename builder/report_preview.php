<?php
/**
*	@cb4100@
*	- Creation SLC	 12/11/2008
*
*	Page principale du Report builder
*
*	30/01/2009 GHX
*		- modification des requetes SQL pour mettre des valeurs entre cote [REFONTE CONTEXTE]
*	02/02/2009 GHX
*		- Suppression de la colonne internal_id [REFONTE CONTEXTE]
*	05/02/2009 GHX
*		- La requete qui récupère les labels n'est pas exécutée sur la base bonne (variable incorrecte)
 *      30/09/2010 NSE bz 18238 : génération d'un rapport avec dashboard hour sur données de la journée courante
 * 02/03/2011 MMT bz 19128
 *		- ajout d'un paramètre suplementaire pour le format du preview pour tester
*/

// INCLUDES
include_once(dirname(__FILE__)	. "/../php/environnement_liens.php");
require_once(REP_PHYSIQUE_NIVEAU_0.'php/edw_function_family.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'/class/PHPOdf.class.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'/class/DashboardExport.class.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'php/debug_tools.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'class/Date.class.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'class/Exporter.class.php');
require_once(MOD_SELECTEUR.'php/SelecteurDashboard.class.php');
include_once('../dashboard_display/class/DashboardData.class.php');
include_once('../dashboard_display/class/GtmXml.class.php');
include_once(MOD_CHARTFROMXML.'class/graph.php');
include_once(MOD_CHARTFROMXML.'class/SimpleXMLElement_Extended.php');
include_once(MOD_CHARTFROMXML.'class/chartFromXML.php');

ob_start();

// Inclusion du Header Astellia
$arborescence = 'Report Preview';
include_once(REP_PHYSIQUE_NIVEAU_0.'php/header.php');

// Messages
$A_GDR_BUILDER_PREVIEW_NO_DATA		= __T('A_GDR_BUILDER_PREVIEW_NO_DATA');
$A_GDR_BUILDER_PREVIEW_EMAIL		= __T('A_GDR_BUILDER_PREVIEW_EMAIL');

?>
<link rel="stylesheet" type="text/css" href="<?=NIVEAU_0?>css/graph_style.css" />
<div id="container" style="width:100%;text-align:left">
<?php

// Récupération de l'id rapport
$id_report = $_GET['id_report'];
// 02/03/2011 MMT bz 19128 ajout du parametre 'format' (pdf, xls ou doc) qui n'est pas utilisé dans l'appli mais peut
// etre utilisé en ligne de commande pour tester
$format_extention = $_GET['format'];
if(empty($format_extention)){
	$format_extention = 'pdf';
}

// Récupération de l'offset day
$offset_day = get_sys_global_parameters('offset_day');

// Instanciation de l'objet Exporter
// 30/08/2011 BBX
// BZ 10387 : ajout de la propriété preview pour connaitre le mode de génération
$myExporter = new Exporter($offset_day, true);
$myExporter->ext = $format_extention;
$myExporter->schedule_name = 'preview';

ob_end_flush();

// Si des données sont présentes
// 30/09/2010 NSE bz 18238 : comparaison exacte avec false
// si getFirstDayWithData = 0 on génère un export avec les données du jour (utile pour les rapports hour)
if ($myExporter->getFirstDayWithData($id_report) !== false)
{
	// On génère l'export
	$myExporter->exportReport($id_report);
	echo '<br />';
	echo '<img src="'.NIVEAU_0.'images/icones/information.png" border="0" />';
	echo '<div class="infoBox" style="text-align:left">';
	echo '<center><b>'.$A_GDR_BUILDER_PREVIEW_EMAIL.'</b></center>';
	echo '<pre>'.$myExporter->msg.'</pre></div>';
} 
else 
{
	// Sinon on indique qu'il n'y a aucune donnée
	echo '<div class="errorMsg">'.$A_GDR_BUILDER_PREVIEW_NO_DATA.'</div>';
}

?>

<!-- Bouton Close -->
<br/>
	<center>
		<input type="button" class="bouton" value="Close" onclick="window.close()" />
	</center>
<br />
<br />
</div>
</body>
</html>
