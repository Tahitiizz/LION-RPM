<?
/*
*	@cb50000@
*
*	16/07/2009 - Copyright Astellia
*
*	IHM de gestion des Data Export - Génération des exports
*
*	10/11/2009 GHX
*		- Prise en compte d'un mode débug (sys_debug : data_export)
*
*/
?>
<?php
@session_start();
// Librairies et classes requises
include_once dirname(__FILE__)."/../php/environnement_liens.php";
require_once(REP_PHYSIQUE_NIVEAU_0.'models/DataExportModel.class.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'class/DataExport.class.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'class/DirectoryManagement.class.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'class/topology/TopologyDownload.class.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'php/edw_function_family.php');

// Header HTML
if(isset($_SERVER["HTTP_USER_AGENT"])) {
	$arborescence = 'Export File';
	include_once(REP_PHYSIQUE_NIVEAU_0.'php/header.php');
}

// 24/11/2011 BBX
// BZ 24832 : pas de temps limite
set_time_limit(0);

// Titre
displayInDemon('Data Export','title');

// 11:22 10/11/2009 GHX
// Prise en compte d'un mode débug pour Data Export
$debugDataExport = (get_sys_debug('data_export') == 0 ? false : true);

// Récupération des infos compute
$compute_mode = get_sys_global_parameters('compute_mode');
$compute_process = get_sys_global_parameters('compute_processing');
$compute_switch = get_sys_global_parameters('compute_switch');
$offset_day = get_sys_global_parameters('offset_day');

//////////////////////////////////////////////
// 16/09/2009 BBX : Mise a jour automatique des donnees a exporter
DataExportModel::updateDataList();
// 15/09/2009 BBX : si on a un fichier update_data_export.cfg, on le parse
/*
* /!\ NE PAS MODIFIER LE NOM DU FICHIER update_data_export.cfg : pour le Corporate
*/
$configurationFile = REP_PHYSIQUE_NIVEAU_0.'upload/export_files_corporate/update_data_export.cfg';
if(file_exists($configurationFile)) 
{
	displayInDemon('Fichier update_data_export.cfg d&eacute;tect&eacute;. On le traite.','normal');
	if(!DataExportModel::updateAutomaticDataExport($configurationFile))
		displayInDemon('Une erreur est survenue pendant le traitement du fichier '.$configurationFile,'alert');
	else
		displayInDemon('Traitement du fichier update_data_export.cfg termin&eacute;','normal');
}
// 09:17 20/10/2009 GHX : si on a un fichier update_data_export.cfg, on le parse : pour le produit Mixed KPI
/*
* /!\ NE PAS MODIFIER LE NOM DU FICHIER update_data_export.cfg
*/
$configurationFile = REP_PHYSIQUE_NIVEAU_0.'upload/export_files_mixed_kpi/update_data_export.cfg';
if(file_exists($configurationFile)) 
{
	displayInDemon('Fichier update_data_export.cfg d&eacute;tect&eacute;. On le traite.','normal');
	if(!DataExportModel::updateAutomaticDataExport($configurationFile, true))
		displayInDemon('Une erreur est survenue pendant le traitement du fichier '.$configurationFile,'alert');
	else
		displayInDemon('Traitement du fichier update_data_export.cfg termin&eacute;','normal');
}
//////////////////////////////////////////////

// Récupération des exports du produit
foreach(DataExportModel::getExportList('','',true) as $exportId => $exportData)
{
	// Affichage de l'export en cours de construction
	displayInDemon('Export '.$exportData['export_name'],'list');

	// On va vérifier les conditions de génération des exports selon la TA par rapport au compute
	$doExport = false;
	$warning = '';
	switch($exportData['time_aggregation']) 
	{
		/* Export hour */
		case 'hour':		
			// Cas 1 : export par heure
			if($exportData['generate_hour_on_day'] == '0')
			{
				// Cas 1 : compute hour
				$condition_1 = (($compute_mode == 'hourly') && ($compute_process == 'hour'));
				// Cas 2 : compute switch
				$condition_2 = (($compute_mode == 'daily') && ($compute_switch == 'hourly'));
				// Si au moins une de ces 2 conditions est validée, la génération des export hour est autorisée
				if($condition_1 || $condition_2) $doExport = true;
				// Affichage d'un message en cas de non génération
				else {
					$computeToDisplay = ($compute_process == 'hour') ? $compute_mode : $compute_process;
					$warning = 'Export Hour non compatible avec le compute '.$computeToDisplay;
				}
			}
			// Cas 2 : export de toutes les heures d'une journée
			else
			{
				// Condition de génération
				if(($compute_mode == 'daily') || (($compute_mode == 'hourly') && ($compute_process == 'day'))) $doExport = true;
				// Affichage d'un message en cas de non génération
				else $warning = 'Export Day non compatible avec le compute '.$compute_mode;
			}
		break;
		
		/* Export day */
		case 'day':
		case 'day_bh':
			// Condition de génération
			if(($compute_mode == 'daily') || (($compute_mode == 'hourly') && ($compute_process == 'day'))) $doExport = true;
			// Affichage d'un message en cas de non génération
			else $warning = 'Export Day non compatible avec le compute '.$compute_mode;
		break;
		
		/* Export week */
		case 'week':
		case 'week_bh':
			// Cas 1 : changement de semaine
			$condition_1 = (Date::getWeekFromDatabaseParameters() != Date::getWeekFromDatabaseParameters($offset_day+1));
			// Cas 2 : reprise de données d'une semaine précédent la semaine courante
			$condition_2 = (Date::getWeekFromDatabaseParameters() != Date::getWeekFromDatabaseParameters(0));
			// Conditions de génération
			if(($compute_mode == 'daily') || (($compute_mode == 'hourly') && ($compute_process == 'day'))) {
				if($condition_1 || $condition_2) $doExport = true;
				// Affichage d'un message en cas de non génération
				else $warning = 'Pas de changement de semaine ou de reprise de données détectés pour la semaine '.Date::getWeekFromDatabaseParameters();
			}
			// Affichage d'un message en cas de non génération
			else $warning = 'Export Week non compatible avec le compute '.$compute_mode;
		break;
		
		/* Export month */
		case 'month':
		case 'month_bh':
			// Cas 1 : changement de mois
			$condition_1 = (Date::getMonthFromDatabaseParameters() != Date::getMonthFromDatabaseParameters($offset_day+1));
			// Cas 2 : reprise de données d'un mois précédent le mois courant
			$condition_2 = (Date::getMonthFromDatabaseParameters() != Date::getMonthFromDatabaseParameters(0));
			// Conditions de génération
			if(($compute_mode == 'daily') || (($compute_mode == 'hourly') && ($compute_process == 'day'))) {
				if($condition_1 || $condition_2) $doExport = true;
				// Affichage d'un message en cas de non génération
				else $warning = 'Pas de changement de mois ou de reprise de données détectés pour le mois '.Date::getMonthFromDatabaseParameters();
			}
			// Affichage d'un message en cas de non génération
			else $warning = 'Export Month non compatible avec le compute '.$compute_mode;			
		break;
	}
	
	// Si la génération de l'export est autorisée, on effectue cette génération
	if($doExport)
	{
		// Instanciation d'un objet DataExport
		$DataExport = new DataExport($exportId);
		// 11:22 10/11/2009 GHX
		// Prise en compte d'un mode débug pour Data Export
		$DataExport->setDebug($debugDataExport);
		$files = $DataExport->buildFiles();
		
		// Si pas de fichiers, on regarde s'il y a une erreur
		if(!$files && ($DataExport->getError() != '')) {
			displayInDemon($DataExport->getError(),'alert');
		}

		// Test des fichier générés
		if(isset($files['export'])) {
			foreach($files['export'] as $file) {
				if($DataExport->getUrl()) {
					displayInDemon('<a href="'.$DataExport->getUrl().'/'.$file.'">Click here to download '.$file.'</a>','normal');
				}
				else {
					displayInDemon('<a href="#">'.$file.' generated but not downloadable (the target directory is not reachable with a web browser)</a>','normal');
				}
			}
		}
		else {
			$message = 'No Data for ';
			$message .= ($exportData['time_aggregation'] == 'hour') ? 'hours of the day ' : 'the '.$exportData['time_aggregation'];
			$message .= ' '.$DataExport->getTaValue();
			displayInDemon($message,'normal');
		}
	}
	// Si la génération est refusée
	else
	{
		// Affichage du warning
		displayInDemon(htmlentities($warning),'normal');
	}
}

// Fin page
if(isset($_SERVER["HTTP_USER_AGENT"])) {
	echo '</body>';
	echo '</html>';
}
