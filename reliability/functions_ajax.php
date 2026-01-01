<?php
if(!isset($_SESSION)) session_start();

include_once("../php/environnement_liens.php");
include_once("../php/edw_function.php");
include_once("../class/DashboardExport.class.php");

include_once("./class/SA_IHM.class.php");
include_once("./class/SA_Calculation.class.php");

$productid = (isset($_REQUEST["productid"]))? $_REQUEST["productid"] : 1;
$ta_value = (isset($_REQUEST["ta_value"]))? $_REQUEST["ta_value"] : date('Ymd', mktime(0,0,0, date('m'), date('d')-1, date('Y')));
// Par défaut, on affiche les données day
$ta_mode = (isset($_REQUEST["ta_mode"]))? $_REQUEST["ta_mode"] : "day";
// Mode d'affichage des errors only or all
$errors = (isset($_REQUEST["show_errors"]))? $_REQUEST["show_errors"] : 1;

$source_avail = new SaIHM($productid);

$source_avail->setTa($ta_mode);
$source_avail->setTaValue($ta_value);
$source_avail->setConnexions($productid);
$source_avail->sortByMode($errors);

$xml = $source_avail->exportExcel();

// chargement des paramètres
$astelliaLogo	= get_sys_global_parameters('pdf_logo_dev');
$clientLogo	= get_sys_global_parameters('pdf_logo_operateur');
$path = "../report_files/SA_data.xml"; // Nom du fichier d'export de données SA au format excel

// création du fichier XML
$fp = fopen($path, "w+");
fwrite($fp, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n");
fwrite($fp, $xml);
fclose($fp);

// appel de la classe pour exporter les données vers Excel
$dashboard_export = array('titre' => "Test_titre",
													'data' => array(array('titre' => 'data_titre',
																					'image' => 'image.png',
																					'xml' => "$path"
																					))
													);

$DashboardExport = new DashboardExport(
	$dashboard_export,					// tableau des Dashs
	'landscape',				// format du fichier = 'landscape', 'portrait', ...
	REP_PHYSIQUE_NIVEAU_0.'report_files',	// dir de sauvegarde
	'export_',							// prefix du fichier
	REP_PHYSIQUE_NIVEAU_0.$astelliaLogo,
	REP_PHYSIQUE_NIVEAU_0.$clientLogo,
	REP_PHYSIQUE_NIVEAU_0.'/images/icones/pdf_alarm_titre_arrow.png'
);

$filePath = $DashboardExport->excelExport();

// traitement du lien
// 09/12/2011 ACS Mantis 837 DE HTTPS support
$filePath = str_replace($repertoire_physique_niveau0, ProductModel::getCompleteUrlForMasterGui(), $filePath);
echo $filePath;
?>