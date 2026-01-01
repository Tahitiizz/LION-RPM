<?php
/*
	17/05/2010 NSE : Installation standard, utilisation d'une variable globale pour les chemins vers les exécutables psql et php (PSQL_DIR et PHP_DIR)
*/
/*
 * Script permettant de passer T&A en mode Corporate
 * ATTENTION : le passage en mode Corporate est irréversible
 * 
 * @author : GHX
 * @created : 25/03/2008
 */
?>
<?php

// >>>>>>>>>>
// DEBUG
$tables = array(
		'sys_definition_database_lib', 
		'sys_definition_flat_file_lib', 
		'sys_definition_connection', 
		'sys_definition_group_table_time', 
		'sys_global_parameters', 
		'sys_definition_master', 
		'sys_definition_master_ref', 
		'sys_definition_family', 
		'sys_definition_step'
	);
// Exécution des requêtes SQL
exec("rm ".REP_PHYSIQUE_NIVEAU_0."upload/backup_before_install_corporate.sql" );
foreach ( $tables as $table )
{
	exec("echo 'DROP TABLE $table;' >> ".REP_PHYSIQUE_NIVEAU_0."upload/backup_before_install_corporate.sql");
	exec("env PGPASSWORD=$APass ".PSQL_DIR."/pg_dump -U $AUser $DBName -d -t $table >> ".REP_PHYSIQUE_NIVEAU_0."upload/backup_before_install_corporate.sql");
}
exec("echo \"UPDATE sys_global_parameters SET value='0' WHERE parameters = 'corporate';\" >> ".REP_PHYSIQUE_NIVEAU_0."upload/backup_before_install_corporate.sql");
// <<<<<<<<<<


// Tableau contenant la liste des requêtes SQL pour passer en mode Serveur Corporate
$listQueries = array();

// Supprime les données on ne se sert pas de ces tables dans le cas d'un Corporate
$listQueries[] = 'TRUNCATE sys_definition_database_lib;';
$listQueries[] = 'TRUNCATE sys_definition_flat_file_lib;';

// Efface toutes les connections
$listQueries[] = 'TRUNCATE sys_definition_connection;';

// On met le on_off à 1 pour activer les niveaux BH
$listQueries[] = 'UPDATE sys_definition_group_table_time SET on_off = 1;';
$listQueries[] = "UPDATE sys_definition_group_table_time SET on_off = 0 WHERE time_agregation = 'hour';";

// Ajout du mot Corporate dans le nom de l'appli
$listQueries[] = "UPDATE sys_global_parameters SET value = value || ' Corporate' WHERE parameters = 'product_name';";

// Master
$listQueries[] = 'TRUNCATE sys_definition_master;';
$listQueries[] = 'TRUNCATE sys_definition_master_ref;';
$listQueries[] = "INSERT INTO sys_definition_master (master_id,master_name,utps,offset_time,on_off,visible,ordre) VALUES ('1','Master Corporate','840','360','1','1','1');";
$listQueries[] = 'INSERT INTO sys_definition_master_ref SELECT * FROM sys_definition_master;';

$listQueries[] = "UPDATE sys_global_parameters SET value = '1' WHERE parameters = 'offset_day';";
$listQueries[] = "UPDATE sys_global_parameters SET value = 'daily' WHERE parameters = 'compute_mode';";
$listQueries[] = "UPDATE sys_global_parameters SET value = 'day' WHERE parameters = 'compute_processing';";
$listQueries[] = "UPDATE sys_global_parameters SET value = null WHERE parameters = 'hour_to_compute';";
$listQueries[] = "UPDATE sys_global_parameters SET value = 'daily' WHERE parameters = 'compute_switch';";

// Family
$listQueries[] = "TRUNCATE sys_definition_family;";
$listQueries[] = "INSERT INTO sys_definition_family (family_id,family_name,family_type,master_id,ordre,id_group_table,on_off) VALUES ('0','Corporate - Start Info','internal','1','0','0','1');";
$listQueries[] = "INSERT INTO sys_definition_family (family_id,family_name,family_type,master_id,ordre,id_group_table,on_off) VALUES ('1','Corporate - Clean Structure','internal','1','1','0','1');";
$listQueries[] = "INSERT INTO sys_definition_family (family_id,family_name,family_type,master_id,ordre,id_group_table,on_off) VALUES ('2','Corporate - Master Corporate','internal','1','2','0','1');";
$listQueries[] = "INSERT INTO sys_definition_family (family_id,family_name,family_type,master_id,ordre,id_group_table,on_off) VALUES ('10','Corporate - Alarm Calculation','internal','1','10','0','1');";
$listQueries[] = "INSERT INTO sys_definition_family (family_id,family_name,family_type,master_id,ordre,id_group_table,on_off) VALUES ('11','Corporate - Alarm Sender','internal','1','11','0','1');";
$listQueries[] = "INSERT INTO sys_definition_family (family_id,family_name,family_type,master_id,ordre,id_group_table,on_off) VALUES ('12','Corporate - Others','internal','1','12','0','1');";
$listQueries[] = "INSERT INTO sys_definition_family (family_id,family_name,family_type,master_id,ordre,id_group_table,on_off) VALUES ('13','Corporate - Stop Info','internal','1','13','0','1');";

// Steps
$listQueries[] = "TRUNCATE sys_definition_step;";
$listQueries[] = "INSERT INTO sys_definition_step (step_name,script,step_type,on_off,family_id,ordre,visible) VALUES ('Corporate - Start Info','/scripts/corporate_start_info.php','internal','1','0','1','1');";
$listQueries[] = "INSERT INTO sys_definition_step (step_name,script,step_type,on_off,family_id,ordre,visible) VALUES ('Corporate - Version history','/class/versionHistory.php','internal','1','0','2','1');";

$listQueries[] = "INSERT INTO sys_definition_step (step_name,script,step_type,on_off,family_id,ordre,visible) VALUES ('Corporate - Clean Structure','/scripts/clean_tables_structure.php','internal','1','1','2','1');";

$listQueries[] = "INSERT INTO sys_definition_step (step_name,script,step_type,on_off,family_id,ordre,visible) VALUES ('Corporate - Retrieve','/scripts/corporate_retrieve.php','internal','1','2','1','1');";
$listQueries[] = "INSERT INTO sys_definition_step (step_name,script,step_type,on_off,family_id,ordre,visible) VALUES ('Corporate - Clean History','/scripts/clean_history.php','internal','1','2','2','1');";
$listQueries[] = "INSERT INTO sys_definition_step (step_name,script,step_type,on_off,family_id,ordre,visible) VALUES ('Corporate - Compute Before','/scripts/compute_corporate_before.php','internal','1','2','3','1');";
$listQueries[] = "INSERT INTO sys_definition_step (step_name,script,step_type,on_off,family_id,ordre,visible) VALUES ('Corporate - Compute Raw - NA Max','/scripts/compute_corporate_raw.php','internal','1','2','4','1');";
$listQueries[] = "INSERT INTO sys_definition_step (step_name,script,step_type,on_off,family_id,ordre,visible) VALUES ('Corporate - Compute Kpi - NA Max','/scripts/compute_corporate_kpi.php','internal','1','2','5','1');";
$listQueries[] = "INSERT INTO sys_definition_step (step_name,script,step_type,on_off,family_id,ordre,visible) VALUES ('Corporate - Compute After','/scripts/compute_corporate_after.php','internal','1','2','6','1');";

$listQueries[] = "INSERT INTO sys_definition_step (step_name,script,step_type,on_off,family_id,ordre,visible) VALUES ('Corporate - Alarm Calculation','/scripts/alarm_calculation.php','internal','1','10','2','1');";

$listQueries[] = "INSERT INTO sys_definition_step (step_name,script,step_type,on_off,family_id,ordre,visible) VALUES ('Corporate - Alarm Sent By Mail','/scripts/alarm_mail_pdf.php','internal','1','11','1','1');";
$listQueries[] = "INSERT INTO sys_definition_step (step_name,script,step_type,on_off,family_id,ordre,visible) VALUES ('Corporate - Alarm Sent By SNMP','/scripts/alarm_snmp.php','internal','1','11','2','1');";

$listQueries[] = "INSERT INTO sys_definition_step (step_name,script,step_type,on_off,family_id,ordre,visible) VALUES ('Corporate - Envoi de Rapports','/scripts/content_sender_v2.php','internal','1','12','1','1');";
$listQueries[] = "INSERT INTO sys_definition_step (step_name,script,step_type,on_off,family_id,ordre,visible) VALUES ('Corporate - Voronoi','/scripts/voronoi.php','internal','1','12','2','1');";
$listQueries[] = "INSERT INTO sys_definition_step (step_name,script,step_type,on_off,family_id,ordre,visible) VALUES ('Corporate - Vacuum','/scripts/vacuum.php','internal','1','12','3','1');";
$listQueries[] = "INSERT INTO sys_definition_step (step_name,script,step_type,on_off,family_id,ordre,visible) VALUES ('Corporate - Clean Files','/scripts/clean_files.php','internal','1','12','4','1');";

$listQueries[] = "INSERT INTO sys_definition_step (step_name,script,step_type,on_off,family_id,ordre,visible) VALUES ('Corporate - Stop Info','/scripts/corporate_stop_info.php','internal','1','13','1','1');";

// Ajout christophe le 01/04/2008.
$listQueries[] = "ALTER TABLE sys_definition_network_agregation DROP COLUMN corporate;";
$listQueries[] = "ALTER TABLE sys_definition_network_agregation ADD COLUMN corporate INT;";

// Exécution des requêtes SQL
foreach ( $listQueries as $query )
{
	pg_query($database_connection, $query);
}
?>