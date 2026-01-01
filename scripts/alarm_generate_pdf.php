<?php

// $argv[0]  chemin et nom du script

// on recupère les variables passées au script
for ($i = 1; $i <= count($argv); $i++) {
	list($key,$val) = explode('=',$argv[$i],2);
	if ($key)
		${$key} = $val;
}

$alarm_name = urldecode($alarm_name);
$sql_selected_alarm = urldecode($sql_selected_alarm);

include_once(dirname(__FILE__)."/../php/environnement_liens.php");

// on se connecte à la db
$db = new DataBaseConnection($id_product);

include_once(REP_PHYSIQUE_NIVEAU_0 . "pdf/fpdf153/fpdf.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/alarm_calculation.class.php");	// Classe mère de calcul  des alarmes
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/alarm_static.class.php");		// Classe de calcul des alertes statiques
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/alarm_dynamic.class.php");	// Classe de calcul des alertes dynamiques
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/alarm_top_worst.class.php");	// Classe de calcul des listes
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/alarmMailWithPdf.class.php");

include_once(REP_PHYSIQUE_NIVEAU_0 . "php/deploy_and_compute_functions.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/edw_function_family.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/edw_alarm.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/postgres_functions.php");

$alarmMail = new alarmMailWithPdf($offset_day);
// $alarmMail->debug = true;
$file_name = $alarmMail->pdfBuilder(
	$alarm['alarm_name'],	// $title
	"export_{$alarm_type}_$id_alarm.pdf",
	REP_PHYSIQUE_NIVEAU_0.'report_files/',	// repertoire de sauvegarde du fichier
	$na,
	$ta,
	$ta_value,
	$alarm_type,
	$sql_selected_alarm,
	"Alarm report ($alarm_name)",		// $header,
	1
);

echo $file_name;

?>
