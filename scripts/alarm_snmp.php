<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*/
?>
<?php
/*
*	@cb21000@
*
*	08/12/2006 - Copyright Acurio
*
*	Composant de base version cb_2.1.0.00
*/
?>
<?php
/*
		- 31-01-2007 MP : creation du fichier.
	*/
/*
		Permet de générer des alarmes statiques et dynamiques au format SNMP
	*/

include_once(dirname(__FILE__)."/../php/environnement_liens.php");
include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
include_once($repertoire_physique_niveau0 . "php/database_connection.php");
//include_once($repertoire_physique_niveau0 . "php/environnement_datawarehouse.php"); //necessaire car l'appel à get_time_to_calculate nécessite la variable edw_day contenue dans ce fichier
include_once($repertoire_physique_niveau0 . "php/edw_function.php");
include_once($repertoire_physique_niveau0 . "php/edw_function_family.php");
include_once($repertoire_physique_niveau0 . "class/alarmSNMP.class.php"); // Génère le tableau des alarmes et l'enregistre dans sys_contenu_buffer


?>
	<html>
	<head>
		<link rel="stylesheet" href="<?=$niveau0?>css/global_interface.css" type="text/css">
	</head>
	<body>
	<div class=texteGris>
<?php
$offset_day = get_sys_global_parameters("offset_day");
$snmp_activation = get_sys_global_parameters("snmp_activation");
if($snmp_activation)
{
	// $snmp_format = get_sys_global_parameters("snmp_format");
	// cree une instance d'un object d'envoi d'alarm par email afin d'exploiter la fonction qui retourne toutes les alarmes qui ont donné un résulat
	$alarm_snmp = new alarmSNMP($database_connection, $offset_day);
	// le tableau resultat est formatté ainsi : $tab_alarms[$row["ta"]][$row["family"]][$row["na"]][$alarm_type[$i]][] = $row["alarm_id"];
	$alarm_snmp->tab_snmp_alarms = $alarm_snmp->getAlarms();
	// teste s'il y a des Alarmes à envoyer sous forme de TRAP SNMP
	if ($alarm_snmp->tab_snmp_alarms != null) {
		// On récupère la liste des raw et des kpi
		$alarm_snmp->set_kpi_label();
		$alarm_snmp->set_raw_label();
		// récupère les éléments liés à la définition de l'alarme
		$alarm_snmp->get_alarm_definition();
		$alarm_snmp->get_alarm_results();

		if($alarm_snmp->tab_alarm_result!=NULL){
			$alarm_snmp->generate_SNMP_trap();
			$alarm_snmp->send_SNMP_TRAP();
		}
	}
}
else
	{
	echo "Le mode SNMP est désactivé<br>";
	}
	?>
	</div>
	</body>
	</html>
