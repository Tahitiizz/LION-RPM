<?php
/*
 * 01/03/2011 MMT bz 19128 generate a export file for an alarm report
 * called by a master application on its slaves to get their alarm report export files
 * This file replaces the alarm_generate_pdf.php since this file supports all format
 *
 * ce fichier renvoit le nom du fichier précédé de "OK:" ou un message d'erreur si la génération à echouée
 *
 */

// $argv[0]  chemin et nom du script

// capture tout echo pour controller la sortie
ob_start();

for ($i = 1; $i <= count($argv); $i++) {
	list($key,$val) = explode('=',$argv[$i],2);
	if ($key){
		${$key} = $val;
	}
}

$alarm_name = urldecode($alarm_name);
$sql_selected_alarm = urldecode($sql_selected_alarm);

include_once(dirname(__FILE__)."/../php/environnement_liens.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/edw_function_family.php");

$errmsg = '';
$file_name = '';

// call the appropriate class depending on the format

if($format == 'pdf'){

	include_once(REP_PHYSIQUE_NIVEAU_0."class/alarmMailPdf.class.php");
	$alarmMail = new alarmMailPdf($offset_day);
	$alarmMail->setHeader("Alarm report (".$alarm_name.")");
}
elseif($format == 'xls')
{
	include_once(REP_PHYSIQUE_NIVEAU_0."class/alarmMailExcel.class.php");
	$alarmMail = new alarmMailExcel($offset_day);
}
else if($format == 'doc')
{
	include_once(REP_PHYSIQUE_NIVEAU_0."class/alarmMailWord.class.php");
	$alarmMail = new alarmMailWord($offset_day);
} else {
	$errmsg = "Unsupported export format : '$format'";
}
if(!empty($alarmMail)){

	// generate the file
	$file_name = $alarmMail->generateFile(
		$alarm_name,	// $title
		"export_{$alarm_type}_$id_alarm.$format",
		$na,
		$ta,
		$ta_value,
		$alarm_type,
		$sql_selected_alarm,
		1
	);
}

// end of output capture
$stdout = ob_get_contents();
ob_end_clean();


// if no $file_name we return the output as error message
if (empty($errmsg) && empty ($file_name)){
	$errmsg = "No file could be generated, sedde trace:<br>".$stdout;
}

// if no error display generated filename
if(!empty ($errmsg)){
	echo $errmsg;
}else{
	echo "OK:".$file_name;
}


?>
