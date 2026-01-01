<?php
/**
 * 
 *  CB 5.3.1
 * 
 * 22/05/2013 : WebService Topology
 */
/*
*	@cb41000@
*
*	01/12/2008 - Copyright Astellia
*
*	Composant de base cb41000
*
*	- maj 18/02/2009 - MPR : Purge des fichiers présents dans répertoire où sont archivés les export de données  depuis + de 20 jours 
*
*	05/05/2009 GHX
*		- Suppresion de "<?" en doublons ligne 1 et 2 : BZ9622 : [REC][T&A CB 5.0][COMPUTE DAY]: erreur du script clean_files.php
*	20/05/2009 SCT
*		- BZ 9654 : erreur gis_trace
*	08/06/2009 SPS 
*		- on laisse 30 jours de log maximum pour sys_log_ast (au lieu d'un an) : correction bug 6835
*	19/06/2009 GHX
*		- Suppression des logs contextes de plus de 60 jours
*	23/09/2009 GHX
*		- Ajout des tests sur la présence des répertoires avant de faire la suppression des fichiers de celui-ci
*	05/10/2009 BBX
*		- Purge du répertoire upload/export_files_corporate
*	19/11/2009 GHX
*		- Purge du répertoire upload/export_files_mixed_kpi
*   28/09/2010 MPR BZ 18102 : Ajout de l'option -maxdepth 1 afin de lister uniquement le contenu du répertoire upload/
*                                                           sans prendre en compte les sous-répertoires
 *
 *  02/05/2011 MMT
 *   - bz 21899 ajout global param pour nb days sur purge flat_file_upload_archive + creation function  purgeDirFromOldFiles
 * 
 *  12/01/2012 SPD1
 *   - purge des dossiers querybuilder_queries_export, querybuilder_queries_import, querybuilder_csv_export
 *  07/05/2015 JLG
 *   - Mantis 6451 : rendre configurable la profondeur des historiques (backups)
*/
?>
<?php
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
*	- 18/12/07 - maxime : Purge des rapports archivés en base ( par défault : /home/nom_version/report_files) correspond au paramètre de sys_global_parameters => report_files_dir
*	- 08/02/2008 - maxime : Purge des images dans /home/nom_version/gis/gearth_legends (légendes affichées dans Google Earth des fichiers générés)
* 
*/
?>
<?php
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
*
*	- 03/09/2007 christophe :  la purge des tables de AA se fait en fonction du offset day et de la valeur link_to_AA_history_db_servers de sys_global_parameters.
*
*/
?>
<?php
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*	maj 14/03/2007 - mp : On vérifie que le répertoire de sauvegardes de la bd existe bien
*			        - mp :  Le nettoyage des fichiers du répertoire de sauvegardes de la bd concerne uniquement les fichiers dump présents (de type bz2)
*/
?>
<?php
/*
*	@cb21001_gsm20010@
*
*	08/12/2006 - Copyright Acurio
*
*	Composant de base version cb_2.1.0.00
*
*	Parser version gsm_20010
*
*	maj 09/01/2007 - mp : On efface les fichiers dump présents dans le répertoire /home/backup datant depuis plus de 10 jours
*/
?>
<?php
/*
*	@cb1300_iu2000b_pour_cb2000b@
*
*	19/07/2006 - Copyright Acurio
*
*	Composant de base version cb_1.3.0.0
*
*	Parser version iu_2.0.0.0
*/
?>
<?php
set_time_limit(3600);
include_once(dirname(__FILE__)."/../php/environnement_liens.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/database_connection.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/edw_function.php");



/**
 * Remove files and folders from a directory
 *
 * @param String $dir root folder to start the delete from
 * @param int $nbDays number of days from today where files should be removed
 */
function purgeFileAndDirFromFiles($dir, $nbDays) {
Print "purgeFileAndDirFromFiles : $dir\n";
	
	if (is_dir($dir)) {					
		//$cmd = "find $dir -mtime +$nbDays -exec rm -rf {} \;";			
		$cmd = "find $dir -mtime +$nbDays";		
		exec($cmd,$filesToDelete);		
		Print count($filesToDelete)." element(s) found\n";
				
		// Delete directories
		foreach ($filesToDelete as $file){
			if (is_dir($file)) {
			 	$cmd = "rm -rf ".$file;
				exec($cmd);
			}
		}
	}
}
// 16:09 03/09/2009 MPR - Correction du bug 11378
// Purge du répertoire file_archives ( Fichiers chargés par la topo) 
exec("ls ".REP_PHYSIQUE_NIVEAU_0."file_archive/", $files);
//CB 5.3.1 WebService Topology
$days_to_live_for_file_archive_directory_backup = get_sys_global_parameters('days_to_live_for_file_archive_directory_backup');
// 04/09/2017 - cast de la valeur récupérée en entier
$days_to_live_for_file_archive_directory_backup = (int) $days_to_live_for_file_archive_directory_backup ; // Si days_to_live_for_file_archive_directory_backup était une string elle serait égale à 0 après le cast
if($days_to_live_for_file_archive_directory_backup == 0){ // Si == 0 => prendre valeur par défaut
	$days_to_live_for_file_archive_directory_backup_default = 30;
	print "Error: invalid value for parameter 'days_to_live_for_file_archive_directory_backup' : '$nbFFUAdaysToLive', using default value $days_to_live_for_file_archive_directory_backup_default <br>";
	$days_to_live_for_file_archive_directory_backup = $days_to_live_for_file_archive_directory_backup_default; // default
}
displayInDemon("Effacement fichiers dans file_archive ($days_to_live_for_file_archive_directory_backup derniers jours sont conservés)");
//Bug 34113 - [REC][CB 5.3.1.01][#TA-62541][WebService Topology]: Some files older than 30 days in “sys_file_uploaded_archive” table are not deleted
$query = "SELECT
			t0.file_name
		FROM
			sys_file_uploaded_archive t0 LEFT OUTER JOIN users t1 ON (t0.id_user = t1.id_user )
		WHERE 
			t0.file_name NOT ILIKE 'temp_topo_%' AND 
			t0.file_name NOT ILIKE 'temp_topo%' AND
			uploaded_time > TO_CHAR(CURRENT_TIMESTAMP-interval '$days_to_live_for_file_archive_directory_backup day', 'YYYY-MM-DD HH24:MI:SS')
		ORDER BY t0.uploaded_time DESC";
$res = pg_query($database_connection,$query);
	
$files_to_conserve = array();

while( $row = pg_fetch_array($res) )
{
	$files_to_conserve[] = $row['file_name'];
}
			
foreach( $files as $file )
{
	if( !in_array($file, $files_to_conserve) ){
		$cmd = "rm -f ".REP_PHYSIQUE_NIVEAU_0."file_archive/".$file;
		__debug($cmd,"CMD $file to delete");
		exec($cmd);
	}
}
// 03/02/2014 GFS - Bug 39423 - [SUP][T&A CB][#41583][ZainHQ] : sys_file_uploaded_archive table is not purged
pg_query($database_connection, "DELETE FROM sys_file_uploaded_archive WHERE file_name NOT ILIKE 'temp_topo_%' AND file_name NOT ILIKE 'temp_topo%' AND uploaded_time > TO_CHAR(CURRENT_TIMESTAMP-interval '$days_to_live_for_file_archive_directory_backup day', 'YYYY-MM-DD HH24:MI:SS')");

	
// SPD1 le 22/08/2011 - Querybuilder V2
// on supprimes le contenu (fichiers et répertoires) de plus de 24h présent dans /querybuilder_queries_export
purgeFileAndDirFromFiles(REP_PHYSIQUE_NIVEAU_0 . "upload/querybuilder_queries_export/", '1');
// on supprimes le contenu (fichiers et répertoires) de plus de 24h présent dans /querybuilder_queries_import
purgeFileAndDirFromFiles(REP_PHYSIQUE_NIVEAU_0 . "upload/querybuilder_queries_import/", '1');
// on supprimes le contenu (fichiers et répertoires) de plus de 24h présent dans /querybuilder_csv_export
purgeFileAndDirFromFiles(REP_PHYSIQUE_NIVEAU_0 . "upload/querybuilder_csv_export/", get_sys_global_parameters('query_builder_export_expiry_date','1'));



// 02/05/2011 MMT bz 21899 use purgeDirFromOldFiles function
// -------START 21899 ------
// on supprimes les fichiers présents dans le répertoire png_file depuis plus de 1 jours
$days_to_live_for_png_file_directory = get_sys_global_parameters('days_to_live_for_png_file_directory');
$days_to_live_for_png_file_directory = (int) $days_to_live_for_png_file_directory;
if($days_to_live_for_png_file_directory == 0){
	$days_to_live_for_png_file_directory_default = 1;
	print "Error: invalid value for parameter 'days_to_live_for_png_file_directory' : '$days_to_live_for_png_file_directory', using default value $days_to_live_for_png_file_directory_default <br>";
	$days_to_live_for_png_file_directory = $days_to_live_for_png_file_directory_default; // default
}
purgeDirFromOldFiles(REP_PHYSIQUE_NIVEAU_0."png_file/",$days_to_live_for_png_file_directory);
	
// on supprime les fichiers présents dans le répertoire upload depuis plus de 5 jours
// maj 28/09/2010 - MPR : BZ 18102 Ajout de l'option -maxdepth 1 afin de lister uniquement le contenu du répertoire upload/ sans prendre en compte les sous-répertoires
purgeDirFromOldFiles(REP_PHYSIQUE_NIVEAU_0."upload/",5);
	
// 17/02/2011 BBX BZ 20499 On supprime les fichiers .txt présents dans le répertoire upload depuis plus de 2 jours
purgeDirFromOldFiles(REP_PHYSIQUE_NIVEAU_0."upload/",2, "/\.txt$/");

// maj 18/02/2009 - MPR : Purge des fichiers présents dans répertoire où sont archivés les export de données  depuis + de 20 jours 
$days_to_live_for_export_files_directory = get_sys_global_parameters('days_to_live_for_export_files_directory');
$days_to_live_for_export_files_directory = (int) $days_to_live_for_export_files_directory;
if($days_to_live_for_export_files_directory == 0){
	$days_to_live_for_export_files_directory_default = 20;
	print "Error: invalid value for parameter 'days_to_live_for_export_files_directory' : '$days_to_live_for_export_files_directory', using default value $days_to_live_for_export_files_directory_default <br>";
	$days_to_live_for_export_files_directory = $days_to_live_for_export_files_directory_default; // default
}
purgeDirFromOldFiles(REP_PHYSIQUE_NIVEAU_0."upload/export_files",$days_to_live_for_export_files_directory);
	
$days_to_live_for_export_files_corporate_directory = get_sys_global_parameters('days_to_live_for_export_files_corporate_directory');
$days_to_live_for_export_files_corporate_directory = (int) $days_to_live_for_export_files_corporate_directory;
if($days_to_live_for_export_files_corporate_directory == 0){
	$days_to_live_for_export_files_corporate_directory_default = 20;
	print "Error: invalid value for parameter 'days_to_live_for_export_files_corporate_directory' : '$days_to_live_for_export_files_corporate_directory', using default value $days_to_live_for_export_files_corporate_directory_default <br>";
	$days_to_live_for_export_files_corporate_directory = $days_to_live_for_export_files_corporate_directory_default; // default
}
// maj 05/10/2009 - BBX : Purge des fichiers présents dans répertoire où sont archivés les export de données  depuis + de 20 jours 
purgeDirFromOldFiles(REP_PHYSIQUE_NIVEAU_0."upload/export_files_corporate",$days_to_live_for_export_files_corporate_directory);

$days_to_live_for_export_files_mixed_kpi_directory = get_sys_global_parameters('days_to_live_for_export_files_mixed_kpi_directory');
$days_to_live_for_export_files_mixed_kpi_directory = (int) $days_to_live_for_export_files_mixed_kpi_directory;
if($days_to_live_for_export_files_mixed_kpi_directory == 0){
	$days_to_live_for_export_files_mixed_kpi_directory_default = 20;
	print "Error: invalid value for parameter 'days_to_live_for_export_files_corporate_directory' : '$days_to_live_for_export_files_mixed_kpi_directory', using default value $days_to_live_for_export_files_mixed_kpi_directory_default <br>";
	$days_to_live_for_export_files_mixed_kpi_directory = $days_to_live_for_export_files_mixed_kpi_directory_default; // default
}
// 17:06 19/11/2009 GFX Suppression des Data Exports pour mixed KPI qui ont plus de 20 jours
purgeDirFromOldFiles(REP_PHYSIQUE_NIVEAU_0."upload/export_files_mixed_kpi",$days_to_live_for_export_files_mixed_kpi_directory);
	
// on supprime les fichiers présents dans le répertoire astellia_flat_file_upload_archive depuis plus de 30 jours
// 02/05/2011 MMT bz 21899 recupere le nombre de jours pour flat_file_upload_archive dans sys_global_parameters
$nbFFUAdaysToLive = get_sys_global_parameters('flatFileArchiveDaysToLive');
$nbFFUAdaysToLive = (int) $nbFFUAdaysToLive;
if($nbFFUAdaysToLive == 0){
	$nbFFUAdaysToLiveDefault = 30;
	print "Error: invalid value for parameter 'flatFileArchiveDaysToLive' : '$nbFFUAdaysToLive', using default value $nbFFUAdaysToLiveDefault <br>";
	$nbFFUAdaysToLive = $nbFFUAdaysToLiveDefault; // default
}
purgeDirFromOldFiles(REP_PHYSIQUE_NIVEAU_0."flat_file_upload_archive/",$nbFFUAdaysToLive);
	
$days_to_live_for_file_demon_directory = get_sys_global_parameters('days_to_live_for_file_demon_directory');
$days_to_live_for_file_demon_directory = (int) $days_to_live_for_file_demon_directory;
if($days_to_live_for_file_demon_directory == 0){
	$days_to_live_for_file_demon_directory_default = 30;
	print "Error: invalid value for parameter 'days_to_live_for_file_demon_directory' : '$days_to_live_for_file_demon_directory', using default value $days_to_live_for_file_demon_directory_default <br>";
	$days_to_live_for_file_demon_directory = $days_to_live_for_file_demon_directory_default; // default
}
// on supprime les fichiers présents dans le répertoire file_demon depuis plus de 30 jours
purgeDirFromOldFiles(REP_PHYSIQUE_NIVEAU_0."file_demon/",$days_to_live_for_file_demon_directory);

$days_to_live_for_log_directory = get_sys_global_parameters('days_to_live_for_log_directory');
$days_to_live_for_log_directory = (int) $days_to_live_for_log_directory;
if($days_to_live_for_log_directory == 0){
	$days_to_live_for_log_directory_default = 30;
	print "Error: invalid value for parameter 'days_to_live_for_log_directory' : '$days_to_live_for_log_directory', using default value $days_to_live_for_log_directory_default <br>";
	$days_to_live_for_log_directory = $days_to_live_for_log_directory_default; // default
}

// 16/06/2010 : OJT on supprime les fichiers présents dans le répertoire log depuis plus de 30 jours
// 10/06/2011 NSE Merge : utilisation de la fonction purgeDirFromOldFiles() sur la correction d'Olivier
purgeDirFromOldFiles(REP_PHYSIQUE_NIVEAU_0."log/",$days_to_live_for_log_directory);

purgeDirFromOldFiles(REP_PHYSIQUE_NIVEAU_0."gis/gis_temp/",1);
	
//08/02/2008 - maxime : Purge des images dans /home/nom_version/gis/gearth_legends (légendes affichées dans Google Earth des fichiers générés)
purgeDirFromOldFiles(REP_PHYSIQUE_NIVEAU_0."gis/gearth_legends/",30);

// maj 18/12/2007 maxime : On purge les rapports dans /home/nom_version/report_files  archivés depuis plus de n jours ( n correspond au param  dans sys_global_paremeters
$report_files_history = get_sys_global_parameters('report_files_history');
$n = !empty($report_files_history) ? $report_files_history : 21;
$report_files_dir = get_sys_global_parameters('report_files_dir');
// 18/10/2012 BBX
// BZ 29905 : purge des rapports
purgeDirFromOldFiles(REP_PHYSIQUE_NIVEAU_0.$report_files_dir."/",$n);

purgeDirFromOldFiles(REP_PHYSIQUE_NIVEAU_0."topology/delete_backup/",100);

// 10:48 19/06/2009 GHX Suppression des logs contextes de plus de 60 jours
purgeDirFromOldFiles(REP_PHYSIQUE_NIVEAU_0.'/upload/context/',60,"/\.log$/");

$days_to_live_for_database_backup = get_sys_global_parameters('days_to_live_for_database_backup');
$days_to_live_for_database_backup = (int) $days_to_live_for_database_backup;
if($days_to_live_for_database_backup == 0){
	$days_to_live_for_database_backup = 10;
	print "Error: invalid value for parameter 'days_to_live_for_database_backup' : '$days_to_live_for_database_backup', using default value $days_to_live_for_database_backup_default <br>";
	$days_to_live_for_database_backup = $days_to_live_for_database_backup; // default
}
//maj 09/01/2007 - mp : On efface les fichiers dump présents dans le répertoire /home/backup datant depuis plus de 10 jours
$repertoire_backup_database = get_sys_global_parameters("backup_database");
if(isset($repertoire_backup_database)){
	// On limite le nettoyage aux fichiers dump de type bz2
    // 01/08/2011 OJT : bz23218, modification de l'extension en 'gz'
	purgeDirFromOldFiles($repertoire_backup_database,$days_to_live_for_database_backup,"/\.gz$/");
}

// -------END 21899 ------

$off_day = (int) get_sys_global_parameters("offset_day") + 5;

$day = getDay($off_day);

// 09/06/2011 BBX -PARTITIONING-
// Correction des casts
Print "Effacement de sys_process_encours<br>";
$query = "DELETE FROM sys_process_encours WHERE substring(date::text from 1 for 8)<='$day'";
$res = pg_query($database_connection, $query);
print pg_affected_rows($res) . "=" . $query . "<br>";
Print "Effacement de sys_process_log<br>";
$query = "DELETE FROM sys_process_log WHERE substring(date::text from 1 for 8)<='$day'";
//pg_query($database_connection, $query);
$res = pg_query($database_connection, $query);
print pg_affected_rows($res) . "=" . $query . "<br>";
// 20/05/2009 SCT BZ 9654 : erreur gis_trace$query = "TRUNCATE gis_trace";
//Print "Effacement de gis_trace<br>";
//print $query . "<br>";
//pg_query($database_connection, $query);

$day = getDay((int) get_sys_global_parameters("offset_day") + $nbFFUAdaysToLive);
$query = "DELETE FROM sys_flat_file_uploaded_list_archive where day<$day";
//pg_query($database_connection, $query);
$res = pg_query($database_connection, $query);
displayInDemon(pg_affected_rows($res) . "=" . $query . "<br>","alert");

//CB 5.3.1 WebService Topology
Print "Effacement de sys_file_uploaded_archive<br>";
//Bug 34113 - [REC][CB 5.3.1.01][#TA-62541][WebService Topology]: Some files older than 30 days in ?sys_file_uploaded_archive? table are not deleted
//$query = "DELETE FROM sys_file_uploaded_archive WHERE SPLIT_PART(uploaded_time, ' ', 1) < TO_CHAR(CURRENT_TIMESTAMP-interval '30 day', 'YYYY-MM-DD')";
// 03/02/2014 GFS - Bug 39423 - [SUP][T&A CB][#41583][ZainHQ] : sys_file_uploaded_archive table is not purged
$query = "DELETE FROM sys_file_uploaded_archive WHERE file_name NOT ILIKE 'temp_topo_%' AND file_name NOT ILIKE 'temp_topo%' AND uploaded_time < TO_CHAR(CURRENT_TIMESTAMP-interval '$days_to_live_for_file_archive_directory_backup day', 'YYYY-MM-DD HH24:MI:SS')";
$res = pg_query($database_connection, $query);
print pg_affected_rows($res) . "=" . $query . "<br>";

Print "Effacement de sys_log_ast<br>";
/* 08/06/2009 SPS on laisse 30 jours de log maximum (au lieu d'un an) correction bug 6835*/
//on recupere la date d'il y a 30 jours
$day = getDay($days_to_live_for_log_directory);
//$day = getDay(get_sys_global_parameters("offset_day") + 15);
$query = "DELETE FROM sys_log_ast where (substring(message_date::text from 1 for 4)||substring(message_date::text from 6 for 2)||substring(message_date::text from 9 for 2))::int4 < $day";
$res = pg_query($database_connection, $query);
print pg_affected_rows($res) . "=" . $query . "<br>";

Print "Effacement de track_pages<br>";
$day = getDay((int) get_sys_global_parameters("offset_day") + 183); //on conserve 6 mois de données
$query = "DELETE FROM track_pages WHERE access_day<'$day'";
$res = pg_query($database_connection, $query);
print pg_affected_rows($res) . "=" . $query . "<br>";
Print "Effacement de track_users<br>";
$day = getDay((int) get_sys_global_parameters("offset_day") + 365); //on conserve 1 an de données
$format_day_timestamp=substr($day,0,4)."-".substr($day,4,2)."-".substr($day,6,2)." 00:00:00";
$query = "DELETE FROM track_users WHERE start_connection<'$format_day_timestamp'";
$res = pg_query($database_connection, $query);
print pg_affected_rows($res) . "=" . $query . "<br>";
// modif 14:54 26/07/2007 Gwénaël
	// suppression des données sur les bases et serveurs AA
print "Effacement de base Activity Analysis<br>";
// 03/09/2007 christophe :  la purge des tables de AA se fait en fonction du offset day et de la valeur link_to_AA_history_db_servers de sys_global_parameters.
$day = getDay($off_day + get_sys_global_parameters("link_to_AA_history_db_servers"));

$query = "DELETE FROM sys_aa_base WHERE saab_ta<'$day'";
$res = pg_query($database_connection, $query);
print pg_affected_rows($res) . "=" . $query . "<br>";
Print "Effacement des serveurs Activity Analysis n'ayant plus de bases associées<br>";
$query = "DELETE FROM sys_aa_server WHERE saas_idserver NOT IN (SELECT DISTINCT saab_idserver FROM sys_aa_base)";
$res = pg_query($database_connection, $query);
print pg_affected_rows($res) . "=" . $query . "<br>";
?>
