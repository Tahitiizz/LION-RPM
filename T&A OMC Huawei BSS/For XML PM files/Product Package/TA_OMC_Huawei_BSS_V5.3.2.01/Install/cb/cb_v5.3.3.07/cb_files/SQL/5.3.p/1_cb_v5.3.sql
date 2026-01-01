---
-- Mise à jour SQL spécifiques à la version 5.3
---


---
--	LIBRAIRIE LOCALE DE FONCTIONS USUELLES
---

-- Crée une colonne dans une table si la colonne n'existe pas
CREATE OR REPLACE FUNCTION create_column_with_check(my_table text, my_column text, my_type text, my_default text) RETURNS VOID AS $$
DECLARE
	col_exists int;
	query_alter text;
BEGIN
	SELECT COUNT(*) INTO col_exists FROM pg_class c, pg_attribute a
	WHERE a.attrelid = c.oid
	AND a.attnum >= 0
	AND relname = my_table
	AND attname = my_column;
	IF col_exists = 0 THEN
		query_alter := 'ALTER TABLE '||my_table||' ADD COLUMN '||my_column||' '||my_type;
		IF my_default <> '' THEN
			query_alter := query_alter || ' DEFAULT '||my_default;
		END IF;
		EXECUTE query_alter;
	END IF;
END;
$$ LANGUAGE PLPGSQL VOLATILE;

-- Crée une table si elle n'existe pas
CREATE OR REPLACE FUNCTION create_table_with_check(my_table text) RETURNS VOID AS $$
DECLARE
	table_exists int;
	query_create text;
BEGIN
	SELECT COUNT(*) INTO table_exists
	FROM pg_tables
	WHERE schemaname = 'public'
	AND tablename = my_table;
	IF table_exists = 0 THEN
		query_create := 'CREATE TABLE '||my_table||' ()';
		EXECUTE query_create;
	END IF;
END;
$$ LANGUAGE PLPGSQL VOLATILE;

-- Crée une table avec ses colonnes si elle n'existe pas
CREATE OR REPLACE FUNCTION create_full_table_with_check(my_table text, query text) RETURNS VOID AS $$
DECLARE
	table_exists int;
	query_create text;
BEGIN
	SELECT COUNT(*) INTO table_exists
	FROM pg_tables
	WHERE schemaname = 'public'
	AND tablename = my_table;
	IF table_exists = 0 THEN		
		EXECUTE query;
	END IF;
END;
$$ LANGUAGE PLPGSQL VOLATILE;

-- Insertion conditionnelle dans une table
CREATE OR REPLACE FUNCTION insert_into_table_with_check(my_table text, my_column text, my_value text, values_to_insert text, my_fields text) RETURNS VOID AS $$
DECLARE
	value_exists int;
	query_test text;
	query_insert text;
BEGIN
	query_test := 'SELECT COUNT(*) FROM '||my_table||' WHERE '||my_column||' = '||my_value;
	EXECUTE query_test INTO value_exists;
	IF value_exists = 0 THEN
		query_insert := 'INSERT INTO '||my_table||' ';
		IF my_fields <> '' THEN
			query_insert := query_insert || ' ('||my_fields||')';
		END IF;
		query_insert := query_insert || 'VALUES ('||values_to_insert||')';
		EXECUTE query_insert;
	END IF;
END;
$$ LANGUAGE PLPGSQL VOLATILE;


---------------------------------------------------
-- CREATION / MISE A JOUR DES GLOBAL PARAMETERS ---
---------------------------------------------------

-- 12/12/2012 BBX
-- BZ 30841 : changement de cétégorie pour le paramètre week_starts_on_monday
UPDATE sys_global_parameters
SET category = 2
WHERE parameters = 'week_starts_on_monday'
AND category != 2;

-- 23/01/2013 GFS : Bug 31411 - [QAL][T&A Gateway][Dashboard Overtime Limitation] : Maximum number of displayed element remain at default value (10) 
UPDATE sys_global_parameters SET configure = 1, client_type = 'client', label = 'Maximum number of displayed elements', comment = 'The maximum number of network aggregation elements displayed in a dashboard in over time mode (default value is 10).', category = 4, order_parameter = 6, bp_visible = 1 WHERE parameters = 'max_topover_ot';

-- 23/01/2013 GFS : Bug 31417 - [QAL][T&A Gateway][Gis mode] : Gis mode parameter on Gateway side does not prevail on product 
UPDATE sys_global_parameters SET comment = '0 = GIS module is deactivated ; 1 = 2D and 3D GIS are activated; 2 = 3D GIS only is activated (limited mode for multi SRID countries)' WHERE parameters = 'gis';

-- 17/10/2013 GFS : Bug 35358 - [SUP][T&A GSM][ AVP 35543][Zain Bahrein] : SMS sending is too fast
SELECT insert_into_table_with_check(
	'sys_global_parameters', 'parameters',
	'''alarm_sms_delay''', '''alarm_sms_delay'', ''0'', 1, 9, 5, ''customisateur'', ''Temporization of SMS sending'', ''Allow to configure the SMS sending temporization (in seconds). Default value is "0".''',
	'parameters, value, configure, order_parameter, category, client_type, label, comment');
	
-- 18/03/2013 SNMP Trap community global parameter
SELECT insert_into_table_with_check('sys_global_parameters','parameters', '''snmp_community''',
	'''snmp_community'',''public'',1,20,1,''client'',''SNMP community'',''Allow to configure the SNMP community. Default value is "public".''',
	'parameters, value, configure, order_parameter, category, client_type, label, comment');

-- 26/03/2013 NSE : Alarm Email subject format
SELECT insert_into_table_with_check('sys_global_parameters','parameters', '''alarm_email_subject_format''',
	'''alarm_email_subject_format'',''[APP_NAME] Alarm ([NB_ALA] results) [DATE]'',1,2,3,''client'',''Alarm e-mail subject format'',''Allow to configure the subject of the email sent when an alarm is triggered''',
	'parameters, value, configure, order_parameter, category, client_type, label, comment');

-- 22/03/2013 FRR1 : DE TA Optim - Retrieve a blanc
SELECT insert_into_table_with_check('sys_definition_step',
	'step_name', '''Retrieve - start''',
	'''Retrieve - start'',''/scripts/retrieve_start.php'',''internal'',''1'',''26'',''-2'',''1''',
	'step_name, script, step_type, on_off, family_id, ordre, visible');

-- 07/05/2013 FRR1 : DE WebService topology
SELECT create_column_with_check('sys_file_uploaded_archive','initial_request_time','text','');
SELECT create_column_with_check('sys_file_uploaded_archive','file_name_request','text','');
SELECT create_column_with_check('sys_file_uploaded_archive','last_state','text','');
SELECT create_column_with_check('sys_file_uploaded_archive','is_cancelled','boolean','');

-- 22/07/2013 GFS : DE Counters management
SELECT create_column_with_check('sys_field_reference','owner','int','');

-- 30/01/2014 Mantis 4007 Gestion de la topologie (ajout du msc) : ajout du paramètre indiquant si on doit utiliser les infos sur les msc contenus dans les fichiers source
SELECT insert_into_table_with_check(
	'sys_global_parameters', 'parameters', '''use_topo_in_source_file''', 
        '''use_topo_in_source_file'', ''0'', 1,12,3, ''customisateur'', ''Extended topology information from source files'', ''1 to extract new topology information contained in source files (for example MSC in Gsm, PCU and SGSN in Gprs). Set to 0 to keep previous functioning and ignore them. Default value is "0". Should not be activated if all probes are not compliant.''',
	'parameters, value, configure, order_parameter, category, client_type, label, comment');

-- 07/07/2014 Mantis
-- Customisateur
SELECT insert_into_table_with_check(
	'sys_global_parameters', 'parameters', '''history_hour_max_customisateur''', 
        '''history_hour_max_customisateur'', ''365'', 0, ''Client maximum Hourly statistics history'', ''Maximum number of days of hourly statistics customisateur is allowed to configure to be kept in Trending&Aggregation.''',
	'parameters, value, configure, label, comment');
SELECT insert_into_table_with_check(
	'sys_global_parameters', 'parameters', '''history_day_max_customisateur''', 
        '''history_day_max_customisateur'', ''1000'', 0, ''Client maximum Hourly statistics history'', ''Maximum number of days of daily statistics customisateur is allowed to configure to be kept in Trending&Aggregation.''',
	'parameters, value, configure, label, comment');
SELECT insert_into_table_with_check(
	'sys_global_parameters', 'parameters', '''history_week_max_customisateur''', 
        '''history_week_max_customisateur'', ''260'', 0, ''Client maximum Hourly statistics history'', ''Maximum number of weeks of weekly statistics customisateur is allowed to configure to be kept in Trending&Aggregation.''',
	'parameters, value, configure, label, comment');
SELECT insert_into_table_with_check(
	'sys_global_parameters', 'parameters', '''history_month_max_customisateur''', 
        '''history_month_max_customisateur'', ''120'', 0, ''Client maximum Hourly statistics history'', ''Maximum number of months of monthly statistics customisateur is allowed to configure to be kept in Trending&Aggregation.''',
	'parameters, value, configure, label, comment');
-- Client
SELECT insert_into_table_with_check(
	'sys_global_parameters', 'parameters', '''history_hour_max_client''', 
        '''history_hour_max_client'', ''31'', 0, ''Client maximum Hourly statistics history'', ''Maximum number of days of hourly statistics client administrator is allowed to configure to be kept in Trending&Aggregation.''',
	'parameters, value, configure, label, comment');
SELECT insert_into_table_with_check(
	'sys_global_parameters', 'parameters', '''history_day_max_client''', 
        '''history_day_max_client'', ''200'', 0, ''Client maximum Hourly statistics history'', ''Maximum number of days of daily statistics client administrator is allowed to configure to be kept in Trending&Aggregation.''',
	'parameters, value, configure, label, comment');
SELECT insert_into_table_with_check(
	'sys_global_parameters', 'parameters', '''history_week_max_client''', 
        '''history_week_max_client'', ''104'', 0, ''Client maximum Hourly statistics history'', ''Maximum number of weeks of weekly statistics client administrator is allowed to configure to be kept in Trending&Aggregation.''',
	'parameters, value, configure, label, comment');
SELECT insert_into_table_with_check(
	'sys_global_parameters', 'parameters', '''history_month_max_client''', 
        '''history_month_max_client'', ''60'', 0, ''Client maximum Hourly statistics history'', ''Maximum number of months of monthly statistics client administrator is allowed to configure to be kept in Trending&Aggregation.''',
	'parameters, value, configure, label, comment');
	
-- 04/06/2014 GFS : 
UPDATE sys_definition_context_table_key SET sdctk_sdctk_id = null WHERE sdctk_id = 23;
	