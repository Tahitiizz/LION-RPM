<?php

// Root only
#if(exec('whoami') != 'root') {
#    echo "Only root can run this script\n";
#    exit(69);
#}

// Database Connection
include_once(dirname(__FILE__) . "/../php/environnement_liens.php");
include REP_PHYSIQUE_NIVEAU_0  . "php/xbdd.inc";

if( isset( $_GET["database"] ) ) {
    $database = $_GET["database"];
}

$database   = (isset($database) ? $database : $argv[1]);
$database   = (isset($database) ? $database : $DBName);

$db = pg_connect("host=localhost port=5432 dbname=$database user=$AUser password=$APass");
if(!$db) {
    echo "Can't connect to database\n";
    exit(69);
}

// Cleaning dashs
echo "\n** Cleaning dashboards **\n";
$query = "DELETE FROM sys_pauto_config
WHERE id_page IN (
SELECT id_page 
FROM sys_pauto_page_name
WHERE page_type = 'page')
AND class_object != 'graph'";
$result = pg_query($db, $query);
$nbelem = pg_affected_rows($result);
$query = "DELETE FROM sys_pauto_config
WHERE id_page IN (
	SELECT id_page FROM sys_pauto_page_name
	WHERE page_type = 'page'
	AND id_page NOT IN (SELECT sdd_id_page FROM sys_definition_dashboard)
);";
$result = pg_query($db, $query);
$nbElem += pg_affected_rows($result);
$query = "DELETE FROM sys_pauto_page_name
WHERE page_type = 'page'
AND id_page NOT IN (SELECT sdd_id_page FROM sys_definition_dashboard);";
$result = pg_query($db, $query);
$nbElem += pg_affected_rows($result);
echo "Done. $nbelem elements cleaned\n";

// Cleaning graphs
echo "\n** Cleaning graphs **\n";
$query = "DELETE FROM sys_pauto_config
WHERE id_page IN (
SELECT id_page 
FROM sys_pauto_page_name
WHERE page_type = 'gtm')
AND class_object NOT IN ('kpi','counter')";
$result = pg_query($db, $query);
$nbelem = pg_affected_rows($result);
$query = "DELETE FROM sys_pauto_config
WHERE id_page IN (
	SELECT id_page FROM sys_pauto_page_name
	WHERE page_type = 'gtm'
	AND id_page NOT IN (SELECT id_page FROM graph_information)
);";
$result = pg_query($db, $query);
$nbElem += pg_affected_rows($result);
$query = "DELETE FROM sys_pauto_page_name
WHERE page_type = 'gtm'
AND id_page NOT IN (SELECT id_page FROM graph_information);";
$result = pg_query($db, $query);
$nbElem += pg_affected_rows($result);
echo "Done. $nbelem elements cleaned\n";

// Cleaning reports
echo "\n** Cleaning reports **\n";
$query = "DELETE FROM sys_pauto_config
WHERE id_page IN (
SELECT id_page 
FROM sys_pauto_page_name
WHERE page_type = 'report')
AND class_object != 'page'
AND class_object NOT LIKE 'alarm%'";
$result = pg_query($db, $query);
$nbelem = pg_affected_rows($result);
$query = "DELETE FROM sys_definition_selecteur
WHERE sds_report_id NOT IN (SELECT id_page FROM sys_pauto_page_name WHERE page_type = 'report');";
$result = pg_query($db, $query);
$nbElem += pg_affected_rows($result);
echo "Done. $nbelem elements cleaned\n";

// Raw prensent in data tables but not declared in sys_field_reference
$i=1;
echo "\n** Dropping extra columns in RAW data tables (not a raw, a NA or a TA) **\n";
$dataTables = array();
$query = "SELECT t.tablename, a.attname
FROM pg_attribute a, pg_class c, pg_tables t
WHERE a.attrelid = c.oid
AND t.tablename = c.relname
AND a.attnum > 0
AND a.attname NOT IN (
	SELECT s.edw_target_field_name FROM sys_field_reference s
	WHERE s.on_off = 1 
	AND s.new_field = 0
	AND t.tablename LIKE s.edw_group_table||'%'
	AND s.edw_target_field_name = a.attname
)
AND a.attname NOT IN (SELECT agregation FROM sys_definition_time_agregation)
AND a.attname NOT IN (SELECT agregation FROM sys_definition_network_agregation)
AND a.attname != 'bh'
AND a.attisdropped = false
AND t.schemaname = 'public'
AND t.tablename LIKE 'edw_%_axe1_raw_%'
AND t.tablename !~ '[0-9]+$'
ORDER BY t.tablename ASC, a.attname ASC;";
$result = pg_query($db, $query);
$total = pg_num_rows($result);
while($row = pg_fetch_assoc($result)) {
	$table = $row['tablename'];
	$field = $row['attname'];
	echo "Cleaning $table for deprecated columns (field $field) [field $i / $total]...\n";
    $queryD = "ALTER TABLE $table DROP COLUMN IF EXISTS $field CASCADE;";
	pg_query($db, $queryD);
	$i++;
}

// Kpi prensent in data tables but not declared in sys_definition_kpi
$i=1;
echo "\n** Dropping extra columns in KPI data tables (not a kpi, a NA or a TA) **\n";
$dataTables = array();
$query = "SELECT t.tablename, a.attname
FROM pg_attribute a, pg_class c, pg_tables t
WHERE a.attrelid = c.oid
AND t.tablename = c.relname
AND a.attnum > 0
AND a.attname NOT IN (
	SELECT lower(k.kpi_name) FROM sys_definition_kpi k
	WHERE k.on_off = 1 
	AND k.new_field = 0
	AND t.tablename LIKE k.edw_group_table||'%'
	AND lower(k.kpi_name) = a.attname
)
AND a.attname NOT IN (SELECT agregation FROM sys_definition_time_agregation)
AND a.attname NOT IN (SELECT agregation FROM sys_definition_network_agregation)
AND a.attname != 'bh'
AND a.attisdropped = false
AND t.schemaname = 'public'
AND t.tablename LIKE 'edw_%_axe1_kpi_%'
AND t.tablename !~ '[0-9]+$'
ORDER BY t.tablename ASC, a.attname ASC;";
$result = pg_query($db, $query);
$total = pg_num_rows($result);
while($row = pg_fetch_assoc($result)) {
	$table = $row['tablename'];
	$field = $row['attname'];
	echo "Cleaning $table for deprecated columns (field $field) [field $i / $total]...\n";
    $queryD = "ALTER TABLE $table DROP COLUMN IF EXISTS $field CASCADE;";
	pg_query($db, $queryD);
	$i++;
}

// Kpi declared in sys_definition_kpi but not present in data tables
$i=1;
echo "\n** Adding active KPIs declared in 'sys_definition_kpi' but not present in data tables **\n";
$query = "SELECT t.tablename, lower(k.kpi_name) as kpiname
FROM sys_definition_kpi k, pg_class c, pg_tables t
WHERE lower(k.kpi_name) NOT IN (
	SELECT lower(a.attname) FROM pg_attribute a
	WHERE lower(a.attname) = lower(k.kpi_name) 
	AND a.attnum > 0 
	AND a.attisdropped = false
	AND a.attrelid = c.oid
)
AND t.tablename LIKE k.edw_group_table||'%'
AND c.relname = t.tablename
AND t.schemaname = 'public'
AND t.tablename LIKE 'edw_%_axe1_kpi_%'
AND t.tablename !~ '[0-9]+$'
AND k.on_off = 1
AND k.new_field = 0
ORDER BY t.tablename;";
$result = pg_query($db, $query);
$total = pg_num_rows($result);
while($row = pg_fetch_assoc($result)) {
	$table = $row['tablename'];
	$field = $row['kpiname'];
	echo "Adding KPI $field in table $table [field $i / $total]...\n";
    $queryA = "ALTER TABLE $table ADD COLUMN $field float4;";
	pg_query($db, $queryA);
	$i++;
}

// Raw declared in sys_field_reference but not present in data tables
$i=1;
echo "\n** Adding active RAWs declared in 'sys_field_reference' but not present in data tables **\n";
$query = "SELECT t.tablename, lower(s.edw_target_field_name) as rawname
FROM sys_field_reference s, pg_class c, pg_tables t
WHERE lower(s.edw_target_field_name) NOT IN (
	SELECT lower(a.attname) FROM pg_attribute a
	WHERE lower(a.attname) = lower(s.edw_target_field_name) 
	AND a.attnum > 0 
	AND a.attisdropped = false
	AND a.attrelid = c.oid
)
AND t.tablename LIKE s.edw_group_table||'%'
AND c.relname = t.tablename
AND t.schemaname = 'public'
AND t.tablename LIKE 'edw_%_axe1_raw_%'
AND t.tablename !~ '[0-9]+$'
AND s.on_off = 1
AND s.new_field = 0
ORDER BY t.tablename;";
$result = pg_query($db, $query);
$total = pg_num_rows($result);
while($row = pg_fetch_assoc($result)) {
	$table = $row['tablename'];
	$field = $row['rawname'];
	echo "Adding KPI $field in table $table [field $i / $total]...\n";
    $queryA = "ALTER TABLE $table ADD COLUMN $field float4;";
	pg_query($db, $queryA);
	$i++;
}

// Cleaning deprecated references
echo "\n** Cleaning all elements declared on an unexisting product **\n";
// Dashs
$query = "DELETE FROM sys_export_raw_kpi_data
WHERE export_id IN (SELECT export_id
	FROM sys_export_raw_kpi_config
	WHERE id_product NOT IN (SELECT sdp_id FROM sys_definition_product)
	AND id_product IS NOT NULL);";
$result = pg_query($db, $query);
$nbElem = pg_affected_rows($result);
$query = "DELETE FROM sys_export_raw_kpi_config
WHERE id_product NOT IN (SELECT sdp_id FROM sys_definition_product)
AND id_product IS NOT NULL;";
pg_query($db, $query);
// Data Exports
$query = "DELETE FROM sys_pauto_page_name
WHERE id_page IN (
	SELECT id_page
	FROM sys_pauto_config
	WHERE id_product NOT IN (SELECT sdp_id FROM sys_definition_product)
	AND id_product IS NOT NULL);";
$result = pg_query($db, $query);
$nbElem += pg_affected_rows($result);
$query = "DELETE FROM sys_pauto_config
WHERE id_product NOT IN (SELECT sdp_id FROM sys_definition_product)
AND id_product IS NOT NULL;";
pg_query($db, $query);
// Menus
$query = "DELETE FROM menu_deroulant_intranet
WHERE id_page NOT IN (SELECT id_page FROM sys_pauto_page_name WHERE page_type = 'page')
AND id_page IS NOT NULL;";
$result = pg_query($db, $query);
$nbElem += pg_affected_rows($result);
echo "Done. $nbElem cleaned.\n";

// Cleaning menus : reactiving all menus for all users
echo "\n** Reactivating all User menus for all Users **\n";
// Cleaning current entries
$queryClean = "DELETE FROM profile_menu_position
WHERE id_profile IN (SELECT id_profile FROM profile
	WHERE profile_type = 'user');";
pg_query($db, $queryClean);
// Cleaning current entries
$queryClean = "UPDATE profile
SET profile_to_menu = NULL
WHERE profile_type = 'user';";
pg_query($db, $queryClean);
// Activating all menus
$queryAdd = "INSERT INTO profile_menu_position (id_menu, position, id_menu_parent, id_profile)
SELECT m.id_menu, m.position, m.id_menu_parent, p.id_profile
FROM menu_deroulant_intranet m,
	(SELECT id_profile FROM profile
	WHERE profile_type = 'user') p
WHERE is_profile_ref_user = 1;";
$result = pg_query($db, $queryAdd);
$nbElem = pg_affected_rows($result);
// Activating all menus
$queryP = "SELECT DISTINCT p.id_profile, m.ptm
FROM profile p, 
	(SELECT id_profile, array_to_string(array_agg(DISTINCT(id_menu)), '-') AS ptm 
	FROM profile_menu_position
	GROUP BY id_profile) m
WHERE p.id_profile = m.id_profile;";
$result = pg_query($db, $queryP);
while($row = pg_fetch_assoc($result)) {
	$ptm = $row['ptm'];
	$idProfile = $row['id_profile'];
	$queryU = "UPDATE profile SET profile_to_menu = '$ptm'
	WHERE id_profile = '$idProfile';";
	pg_query($db, $queryU);
}
$queryVac = "VACUUM ANALYZE profile_menu_position;";
$result = pg_query($db, $queryVac);
echo "Done. $nbElem activated.\n";

echo "\nAll done Captain !\n";
echo "Notice : it is strongly advised to logout and clean the browser's cache before reconnecting to T&A\n";
?>
