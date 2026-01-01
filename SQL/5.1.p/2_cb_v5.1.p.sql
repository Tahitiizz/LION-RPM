---
-- Mise à jour SQL spécifiques à la version 5.1.1
---

---
--	21/01/2011 MPR
--	Réorganisation des requêtes afin pour pouvoir appliquer le patch n fois sans :
--		- écraser des modification de paramétrage
--		- systématiquement supprimer pour recréer
--		- récréer à chaque fois une nouvelle fonction
--		- compromettre ni une installe de base, ni un update
---

---
--	LIBRAIRIE LOCALE DE FONCTIONS USUELLES
---

-- 09/06/2011 BBX -PARTITIONING-
-- Correction des casts dans les requêtes

-- Crée une colonne dans une table si la colonne n'existe pas
CREATE OR REPLACE FUNCTION create_column_with_check(my_table text, my_column text, my_type text, my_default text) RETURNS VOID AS $$
DECLARE
	col_exists int;
    table_exists int;
	query_alter text;
BEGIN
	SELECT COUNT(*) INTO col_exists FROM pg_class c, pg_attribute a
	WHERE a.attrelid = c.oid
	AND a.attnum >= 0
	AND relname = my_table
	AND attname = my_column;
	IF col_exists = 0 THEN
        -- 21/04/2011 NSE DE Non unique Labels
        -- si la colonne n'existe pas, on vérifie que la table existe bien avant d'essayer de la créer
        SELECT COUNT(*) INTO table_exists
        FROM pg_class
        WHERE relname = my_table;
        IF table_exists=1 THEN
            query_alter := 'ALTER TABLE '||my_table||' ADD COLUMN '||my_column||' '||my_type;
            IF my_default <> '' THEN
                query_alter := query_alter || ' DEFAULT '||my_default;
            END IF;
            EXECUTE query_alter;
        END IF;
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

-- Correction du BZ 12509 [SUP][T&A Cigale GSM 5.0][Only]:Répertoire de sortie des Export data migrés erroné
CREATE OR REPLACE FUNCTION migration_data_export_target_dir() RETURNS VOID AS $$
DECLARE
	_directory text;
	_idProduct int;
	_dataExport RECORD;
BEGIN

	SELECT DISTINCT id_product INTO _idProduct FROM sys_export_raw_kpi_config WHERE id_product IS NOT NULL;
	IF _idProduct IS NULL THEN
		_idProduct = 1;
	END IF;
	SELECT '/home/'||sdp_directory||'/upload/export_files/' INTO _directory FROM sys_definition_product WHERE sdp_id = _idProduct;

	-- Boucle sur tous les data export visible
	FOR _dataExport IN SELECT * FROM sys_export_raw_kpi_config WHERE visible = 1 LOOP

		-- Si l id produit est null
		IF _dataExport.id_product IS NULL THEN
			RAISE NOTICE 'change product % (%)', _idProduct, _dataExport.export_id;
			_dataExport.id_product := _idProduct;
		END IF;

		-- Si le target_dir n est pas le bon ou qu il est null (cas peut probable)
		IF _dataExport.target_dir != _directory OR _dataExport.target_dir IS NULL THEN
			_dataExport.target_dir := _directory;
		END IF;

		--Met a jour le Data Export
		UPDATE
			sys_export_raw_kpi_config
		SET
			target_dir = _dataExport.target_dir,
			id_product = _dataExport.id_product
		WHERE
			export_id = _dataExport.export_id;
	END LOOP;
END;
$$ LANGUAGE PLPGSQL VOLATILE;

CREATE OR REPLACE FUNCTION dropfk(my_table text, fkName text) RETURNS integer AS $$
DECLARE	
	isDropped INTEGER;
	query_alter TEXT;
BEGIN
	IF EXISTS(SELECT 1 FROM pg_constraint WHERE conname = fkName) THEN
		query_alter := 'ALTER TABLE '||my_table||' DROP CONSTRAINT '||fkName||' CASCADE';
		EXECUTE query_alter;
		isDropped = 1;
	ELSE
		isDropped = 0;
	END IF;
	return isDropped;
END;
$$ LANGUAGE PLPGSQL VOLATILE;

-- 08/09/2011 BBX
-- Fonction qui répare les séquences
-- BZ 23652
DROP FUNCTION IF EXISTS repare_sequence();
CREATE OR REPLACE FUNCTION repare_sequence() RETURNS void LANGUAGE plpgsql AS
$$ 
DECLARE 
	s_lines record; 
	n_max integer;
BEGIN   
	FOR s_lines IN
	SELECT s.sequence_name AS sequence, c.table_name AS table, c.column_name AS column, c.column_default
	FROM information_schema.sequences s 
	LEFT JOIN information_schema.columns c 
	ON (c.column_default ~ (E'\'(' || sequence_schema || E'.)?' || sequence_name || E'\'::regclass'))
	LOOP
		EXECUTE 'SELECT COALESCE(MAX(' || s_lines.column || ')+1,1) FROM ' || s_lines.table INTO n_max;
		EXECUTE 'SELECT setval(''' || s_lines.sequence || ''', ' || n_max || ')';
	END LOOP;
END;
$$;

-- 08/09/2011 BBX
-- BZ 23652 : Suppression des séquences obsolètes
DROP sequence IF EXISTS menu_deroulant_intranet_id_menu_seq CASCADE;
DROP sequence IF EXISTS sys_definition_kpi_id_ligne_seq CASCADE;
DROP sequence IF EXISTS sys_field_reference_id_ligne_seq CASCADE;
DROP sequence IF EXISTS sys_pauto_config_id_temp_seq CASCADE;
DROP sequence IF EXISTS sys_definition_alarm_dynamic_alarm_id_seq CASCADE;
DROP sequence IF EXISTS sys_definition_alarm_static_alarm_id_seq CASCADE;
DROP sequence IF EXISTS sys_definition_alarm_top_worst_alarm_id_seq CASCADE;

-- 08/09/2011 BBX
-- BZ 23652 : Réparation des séquence en début de traitement
SELECT repare_sequence();

-- maj 07/12/2010 - MPR : Correction du bz 19633
ALTER TABLE sys_aa_base ALTER COLUMN saab_tag TYPE text;

-- maj 06/01/2011 - MPR : Correction du bz 19972
SELECT create_column_with_check('sys_definition_categorie','network_axe_ri','smallint','1');

-- 12/04/2011 BBX BZ 20838
UPDATE sys_global_parameters
SET client_type = 'customisateur'
WHERE "parameters" = 'capture_duration'
AND client_type = 'client';

-- 13/04/2011 BBX BZ 21816
ALTER TABLE sys_definition_product
ALTER COLUMN sdp_db_port TYPE integer;
ALTER TABLE sys_definition_product
ALTER COLUMN sdp_ssh_port TYPE integer;

-- 11/01/2011 MMT DE Xpert 606  (MERGE 5.1.4)
-- Ajout d'un mode débugage pour Xpert
SELECT insert_into_table_with_check('sys_debug',
	'parameters', '''xpert_management''',
	'''xpert_management'', 0, ''Debug Mode for Xpert management inside T&A: menus, Graph links to Xpert and links from Xpert to T&A dashboards''',
	'parameters, value, commentaire');

-- 16/05/2011 BBX
-- Partitioning related queries
-- Adding 'Create Partitions' step
-- Compute Day
INSERT INTO sys_definition_step(
  step_name,
  script,
  step_type,
  on_off,
  family_id,
  ordre,
  visible
)
SELECT 'Create Partitions','/scripts/create_partitions.php',step_type,0,family_id,ordre-1::integer,1
FROM sys_definition_step
WHERE step_name ILIKE 'Compute raw'
AND (SELECT count(*) FROM sys_definition_step WHERE step_name = 'Create Partitions') = 0;
-- Compute Hour
INSERT INTO sys_definition_step(
  step_name,
  script,
  step_type,
  on_off,
  family_id,
  ordre,
  visible
)
SELECT 'Hourly - Create Partitions','/scripts/create_partitions.php',step_type,0,family_id,ordre-1::integer,1
FROM sys_definition_step
WHERE step_name ILIKE 'Hourly - compute raw'
AND (SELECT count(*) FROM sys_definition_step WHERE step_name = 'Hourly - Create Partitions') = 0;

-- 21/04/2011 NSE DE Non unique Labels
-- Ajout de la colonne 'uniq_label'
SELECT create_column_with_check('sys_definition_network_agregation','uniq_label','smallint','1 NOT NULL');
-- Ajout pour le Corporate si patché après déploiement
SELECT create_column_with_check('sys_definition_network_agregation_bckp','uniq_label','smallint','1 NOT NULL');

-- 16/05/2011 NSE DE Topology characters replacement

-- Création de la table des valeurs par défaut du CB
SELECT create_table_with_check('sys_definition_topology_replacement_rules_default');
SELECT create_column_with_check('sys_definition_topology_replacement_rules_default','sdtrr_id','text','NULL');
SELECT create_column_with_check('sys_definition_topology_replacement_rules_default','sdtrr_character','text','NULL');
SELECT create_column_with_check('sys_definition_topology_replacement_rules_default','sdtrr_code','text','NULL');
SELECT create_column_with_check('sys_definition_topology_replacement_rules_default','sdtrr_label','text','NULL');

SELECT insert_into_table_with_check('sys_definition_topology_replacement_rules_default',
	'sdtrr_id', '''sdtrr.1''',
	'''sdtrr.1'', '''''''', ''_'', ''_''',
	'sdtrr_id, sdtrr_character, sdtrr_code, sdtrr_label');
SELECT insert_into_table_with_check('sys_definition_topology_replacement_rules_default',
	'sdtrr_id', '''sdtrr.2''',
	'''sdtrr.2'', ''"'', ''_'', ''_''',
	'sdtrr_id, sdtrr_character, sdtrr_code, sdtrr_label');
SELECT insert_into_table_with_check('sys_definition_topology_replacement_rules_default',
	'sdtrr_id', '''sdtrr.3''',
	'''sdtrr.3'', ''#'', ''_'', ''_''',
	'sdtrr_id, sdtrr_character, sdtrr_code, sdtrr_label');
SELECT insert_into_table_with_check('sys_definition_topology_replacement_rules_default',
	'sdtrr_id', '''sdtrr.4''',
	'''sdtrr.4'', ''\\\\'', ''_'', ''_''',
	'sdtrr_id, sdtrr_character, sdtrr_code, sdtrr_label');

-- Création de la table du contexte
SELECT create_table_with_check('sys_definition_topology_replacement_rules');
SELECT create_column_with_check('sys_definition_topology_replacement_rules','sdtrr_id','text','NULL');
SELECT create_column_with_check('sys_definition_topology_replacement_rules','sdtrr_character','text','NULL');
SELECT create_column_with_check('sys_definition_topology_replacement_rules','sdtrr_code','text','NULL');
SELECT create_column_with_check('sys_definition_topology_replacement_rules','sdtrr_label','text','NULL');

-- Déclaration de la table dans le contexte
SELECT insert_into_table_with_check('sys_definition_context',
	'sdc_id', '''24''',
	'24, ''Characters replacement rules'', '''', 0,0,0,1,0,'''',24',
	'sdc_id, sdc_label, sdc_column, sdc_default, sdc_visible, sdc_master, sdc_truncate, sdc_selected, sdc_sql_where_select_default, sdc_order_display');
SELECT insert_into_table_with_check('sys_definition_context_table',
	'sdct_id', '''48''',
	'48, ''sys_definition_topology_replacement_rules''',
	'sdct_id, sdct_table');
-- 10/01/2012 NSE bz 25408 : remplacement de id_sdtrr par sdtrr_id
-- 24/01/2012 NSE bz 25408 : reopen : on supprime le mauvais enregistrement
DELETE FROM sys_definition_context_table_key WHERE sdctk_id=62 AND sdctk_column='id_sdtrr';
SELECT insert_into_table_with_check('sys_definition_context_table_key',
	'sdctk_id', '''62''',
	'62, ''sdtrr_id'', 48, ''''',
	'sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id');
SELECT insert_into_table_with_check('sys_definition_context_table_link',
	'sdctl_sdc_id', '''24''',
	'24, 48, 1, 1',
	'sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order');

-- fin de la DE Topology characters replacement

-- 08/06/2011 NSE bz 20921 changement du type de la colonne saafk_tag
ALTER TABLE sys_aa_filter_kpi ALTER COLUMN saafk_tag TYPE text;
-- 14/06/2011 NSE bz 20921 : évolution du CB, suppression de la contrainte
-- 13/03/2011 SPD bz 23708 : supprime la contrainte que si elle existe (evite message d'erreur dans les log)
SELECT dropfk('sys_aa_filter_kpi', 'sys_aa_filter_kpi_tag');
--ALTER TABLE sys_aa_filter_kpi DROP CONSTRAINT sys_aa_filter_kpi_tag;

-- 10/06/2011 NSE merge : 5.0.5.09 -> 5.1.3
-- 04/05/2011 OJT : DE Conversion de données (05/05/2011 Correction d'une faute dans le label)
-- 04/08/2011 : bz23296, utilisation de insert_into_table_with_check
SELECT insert_into_table_with_check('sys_global_parameters','parameters', '''enable_size_unit_conversion''',
	'''enable_size_unit_conversion'',''0'',0,''Enable automatic size unit conversion on graph'',''Compatibility parameter for SizeUnitConversion activation''',
	'parameters, value, configure, label, comment');

-- 19/05/2011 OJT Ajout des colonnes dans sys_definition_selecteur pour la DE TopNALevelForBHGraph
SELECT create_column_with_check('sys_definition_selecteur','sds_fh_on_off','boolean','false' );
SELECT create_column_with_check('sys_definition_selecteur','sds_fh_na','text', '' );
SELECT create_column_with_check('sys_definition_selecteur','sds_fh_ne','text', '' );
SELECT create_column_with_check('sys_definition_selecteur','sds_fh_na_axe3','text', '' );
SELECT create_column_with_check('sys_definition_selecteur','sds_fh_ne_axe3','text', '' );
SELECT create_column_with_check('sys_definition_selecteur','sds_fh_product_bh','character varying(10)', '' );
SELECT create_column_with_check('sys_definition_selecteur','sds_fh_family_bh','text', '' );

-- 02/05/2011 MMT bz 21899 : ajout paramètre pour determiner la duree de vie des fichiers dans flat_file_upload_archive
-- 01/12/2011 ACS BZ 24404 Global parameters "flatFileArchiveDaysToLive" systematically reset after patch
SELECT insert_into_table_with_check('sys_global_parameters','parameters', '''flatFileArchiveDaysToLive''',
	'''flatFileArchiveDaysToLive'',''30'',1,''customisateur'',''Days to live for collected files'',''Number of days collected archived files in folder flat_file_upload_archive are stored before being erased'', 3, 15',
	'parameters, value, configure, client_type, label, comment, category, order_parameter');

-- 01/10/2012 BBX BZ 29060
-- On remet la déclaration de la fonction à sa place
-- Trigger for data tables
CREATE OR REPLACE FUNCTION lock_data_tables()
RETURNS TRIGGER AS $$
BEGIN
    RAISE EXCEPTION 'Modification of this table not allowed';
END $$
LANGUAGE plpgsql;

-- 25/05/2011 MMT DE 3rd Axis ajoute une colonne sur sys_definition_selecteur pour elements multiples 3eme axe
SELECT create_column_with_check('sys_definition_selecteur','sds_na_axe3_list','text','');
COMMENT ON COLUMN sys_definition_selecteur.sds_na_axe3_list IS 'Store 3rd axis multiple value, replaces the sds_na_axe3_element using the switchToMultiple3rdAxisValues trigger';

-- trigger pour deplacer et traduire les valeures inséerés dans sds_na_axe3_element vers sds_na_axe3_list
CREATE OR REPLACE FUNCTION switchToMultiple3rdAxisValues() RETURNS trigger AS $switchToMultiple3rdAxisValues$
   BEGIN
	IF (NEW.sds_na_axe3_element IS NOT NULL) THEN
		IF (NEW.sds_na_axe3_element = 'ALL' OR NEW.sds_na_axe3_element = '') THEN
			NEW.sds_na_axe3_list := '';
		ELSE
			NEW.sds_na_axe3_list := NEW.sds_na_axe3 || '||' || NEW.sds_na_axe3_element;
		END IF;
	END IF;
	NEW.sds_na_axe3_element := NULL;

	RETURN NEW;
END;
$switchToMultiple3rdAxisValues$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS switchToMultiple3rdAxisValues ON sys_definition_selecteur;

CREATE TRIGGER switchToMultiple3rdAxisValues BEFORE INSERT OR UPDATE ON sys_definition_selecteur
FOR EACH ROW EXECUTE PROCEDURE switchToMultiple3rdAxisValues();

-- lance le trigger sur toutes les lignes pour conversion initiale
UPDATE sys_definition_selecteur SET sds_id_selecteur = sds_id_selecteur;

-- 10/06/2011 MPR DEV GIS without Polygons : Ajout du mode d'affichage du GIS
SELECT insert_into_table_with_check('sys_global_parameters',
	'parameters', '''gis_display_mode''',
	'''gis_display_mode'',''1'',''1'',''customisateur'',''Gis/Gis 3D display mode'',2,5,''0 : Disable Voronoi Polygons  / 1 : Enable Voronoi Polygons''',
	'parameters, value, configure, client_type, label, category, order_parameter, comment');

-- 29/06/2011 OJT : bz22718, modification
UPDATE sys_global_parameters
SET comment = 'Expected duration for probe capture (in seconds). It should be 3600 for hourly captures or 86400 for daily captures.'
WHERE "parameters" = 'capture_duration';

-- 29/06/2011 OJT : DE SFTP, correction du bug22679
DELETE FROM sys_global_parameters WHERE parameters = 'enable_sftp';
INSERT INTO sys_global_parameters (parameters,value,configure,label,comment) VALUES ( 'enable_sftp','1',0,'Enable SFTP for connection','Compatibility parameter for SFTP activation');
SELECT create_column_with_check('sys_definition_connection','connection_port','integer','');

-- 18/07/2011 OJT : DE SMS, ajout de la catégorie SMS-C dans Setup Global Parameter
DELETE FROM sys_global_parameters_categories WHERE id_category=5;
INSERT INTO sys_global_parameters_categories(id_category,label_category) VALUES (5, 'SMS-C Settings');

-- 18/07/2011 OJT : DE SMS, ajout des nouveaux paramètres globaux
-- 04/08/2011 OJT : bz23293, ecrasement systématique des paramètres globaux
SELECT insert_into_table_with_check('sys_global_parameters','parameters', '''smsc_host''',
	'''smsc_host'','''',1,0,5,''customisateur'',''SMS-C Host'',''Short Message Service Center host address''',
	'parameters, value, configure, order_parameter, category, client_type, label, comment');
SELECT insert_into_table_with_check('sys_global_parameters','parameters', '''smsc_port''',
	'''smsc_port'',''2775'',1,1,5,''customisateur'',''SMS-C Port'',''Short Message Service Center Port (default value is 2775)''',
	'parameters, value, configure, order_parameter, category, client_type, label, comment');
SELECT insert_into_table_with_check('sys_global_parameters','parameters', '''smsc_system_id''',
	'''smsc_system_id'','''',1,2,5,''customisateur'',''SMS-C System Id'',''System Identifier use for SMS-C identification at bind time''',
	'parameters, value, configure, order_parameter, category, client_type, label, comment');
SELECT insert_into_table_with_check('sys_global_parameters','parameters', '''smsc_system_type''',
	'''smsc_system_type'','''',1,3,5,''customisateur'',''SMS-C System Type'',''System Type used to categorize the type of ESME that is binding to the SMSC''',
	'parameters, value, configure, order_parameter, category, client_type, label, comment');
SELECT insert_into_table_with_check('sys_global_parameters','parameters', '''smsc_password''',
	'''smsc_password'','''',1,4,5,''customisateur'',''SMS-C Password'',''Password used by the SMSC to authenticate the identity of the binding ESME''',
	'parameters, value, configure, order_parameter, category, client_type, label, comment');
SELECT insert_into_table_with_check('sys_global_parameters','parameters', '''alarm_sms_sender''',
	'''alarm_sms_sender'','''',1,5,5,''customisateur'',''Alarm SMS Sender'',''SMS Sender phone number''',
	'parameters, value, configure, order_parameter, category, client_type, label, comment');
SELECT insert_into_table_with_check('sys_global_parameters','parameters', '''alarm_sms_format''',
	'''alarm_sms_format'',''[ALA_NAME] has been triggered [NB_ALA] times on [APP_NAME] ([IP])'',1,6,5,''customisateur'',''Alarm SMS Format'',''Alarm SMS Format''',
	'parameters, value, configure, order_parameter, category, client_type, label, comment');
SELECT insert_into_table_with_check('sys_global_parameters','parameters', '''alarm_sms_maximum_delay''',
	'''alarm_sms_maximum_delay'',''3'',1,7,5,''customisateur'',''Alarm SMS maximum delay'',''Maximum authorized delay for SMS sending (in number of time aggregation, default: 3)''',
	'parameters, value, configure, order_parameter, category, client_type, label, comment');
SELECT insert_into_table_with_check('sys_global_parameters','parameters', '''alarm_sms_minimum_severity''',
	'''alarm_sms_minimum_severity'',''major'',1,8,5,''customisateur'',''Alarm SMS minimum severity'',''Minimum severity for which SMS are sent (default: major)''',
	'parameters, value, configure, order_parameter, category, client_type, label, comment');

-- 20/07/2011 OJT : DE SMS, ajout de la nouvelle colonne dans la tables users
SELECT create_column_with_check('users','phone_number','text','' );

-- 20/07/2011 OJT : DE SMS, ajout du paramètre de compatibilité
-- 19/12/2011 ACS BZ 23707 patch reset enable_alarm_sms + "Envoi Alarm (SMS)"
SELECT insert_into_table_with_check('sys_global_parameters','parameters', '''enable_alarm_sms''',
	'''enable_alarm_sms'',''1'',0,''Enable SMS sending for alarms'',''Compatibility parameter for Alarm SMS activation''',
	'parameters, value, configure, label, comment');

-- 22/07/2011 OJT : DE SMS, création de la table sys_alarm_sms_sender
SELECT create_table_with_check('sys_alarm_sms_sender');
SELECT create_column_with_check('sys_alarm_sms_sender','id_alarm','text','');
SELECT create_column_with_check('sys_alarm_sms_sender','recipient_type','text','');
SELECT create_column_with_check('sys_alarm_sms_sender','recipient_id','text','');
SELECT create_column_with_check('sys_alarm_sms_sender','alarm_type','text','');

-- 22/07/2011 OJT : DE SMS, gestion des nouveaux step
-- 19/12/2011 ACS BZ 23707 patch reset enable_alarm_sms + "Envoi Alarm (SMS)"
SELECT insert_into_table_with_check('sys_definition_step','step_name', '''Hourly - Envoi Alarm (SMS)''',
	'(SELECT MAX(step_id) + 1 FROM sys_definition_step), ''Hourly - Envoi Alarm (SMS)'', ''/scripts/alarm_sms.php'', ''internal'', 1, ''50'', (SELECT MAX(ordre)+1 FROM sys_definition_step WHERE family_id=''50''), 1',
	'step_id, step_name, script, step_type, on_off, family_id, ordre, visible');
SELECT insert_into_table_with_check('sys_definition_step','step_name', '''Envoi Alarm (SMS)''',
	'(SELECT MAX(step_id) + 1 FROM sys_definition_step), ''Envoi Alarm (SMS)'', ''/scripts/alarm_sms.php'', ''internal'', 1, ''40'', (SELECT MAX(ordre)+1 FROM sys_definition_step WHERE family_id=''40''), 1',
	'step_id, step_name, script, step_type, on_off, family_id, ordre, visible');

-- 07/10/2011 ACS Data reprocessing GUI
SELECT create_column_with_check('sys_flat_file_uploaded_list_archive', 'reprocess', 'smallint', '0');

	
-- 10/10/2011 NSE DE Bypass temporel
SELECT create_column_with_check('sys_definition_categorie','ta_bypass','text','');
SELECT create_column_with_check('sys_w_tables_list','ta','text','');

-- 13/10/2011 MMT DE Bypass temporel ajout de parametre global pour max Nb db partitionné
DELETE FROM sys_global_parameters WHERE parameters = 'partitioned_max_db_allowed';
INSERT INTO sys_global_parameters (parameters,value,configure,label,comment) VALUES ( 'partitioned_max_db_allowed','4',0,'Maximum number of partitioned DB on a server','Maximum number of partitioned DB on a server for partitioning activation');

-- 04/08/2011 OJT : correction bz23285
UPDATE sys_global_parameters SET label = 'Number of charts with interactive data' WHERE parameters = 'max_nb_charts_onmouseover';

-- 15/09/2011 BBX : Nouveaux éléments pour le BZ 23158
SELECT insert_into_table_with_check('sys_global_parameters','parameters', '''astellia_alert_recipient''',
	'''astellia_alert_recipient'',''support@astellia.com'',1,9,2,''customisateur'',''Alerts recipient'',''Recipient for application alerts''',
	'parameters, value, configure, order_parameter, category, client_type, label, comment');
--
SELECT insert_into_table_with_check('sys_global_parameters','parameters', '''application_custom_name''',
	'''application_custom_name'','''',1,10,2,''customisateur'',''Application custom name'',''Astellia support custom name of application''',
	'parameters, value, configure, order_parameter, category, client_type, label, comment');

-- 18/04/2011 OJT : Ajout de la colonne bp_visible dans la tables sys_global_parameter
SELECT create_column_with_check( 'sys_global_parameters','bp_visible','smallint','0' );

-- 18/04/2011 OJT : Mise à 1 des colonnes visibles pour le produit blanc dans sys_global_parameters
UPDATE sys_global_parameters SET bp_visible = '1' WHERE parameters = 'alarm_critical_color';
UPDATE sys_global_parameters SET bp_visible = '1' WHERE parameters = 'alarm_major_color';
UPDATE sys_global_parameters SET bp_visible = '1' WHERE parameters = 'alarm_management_autorefresh';
UPDATE sys_global_parameters SET bp_visible = '1' WHERE parameters = 'alarm_minor_color';
UPDATE sys_global_parameters SET bp_visible = '1' WHERE parameters = 'automatic_email_activation';
UPDATE sys_global_parameters SET bp_visible = '1' WHERE parameters = 'autorefresh_delay';
UPDATE sys_global_parameters SET bp_visible = '1' WHERE parameters = 'backup_database';
UPDATE sys_global_parameters SET bp_visible = '1' WHERE parameters = 'extrapolation_activate';
UPDATE sys_global_parameters SET bp_visible = '1' WHERE parameters = 'extrapolation_nb_ta';
UPDATE sys_global_parameters SET bp_visible = '1' WHERE parameters = 'gis';
UPDATE sys_global_parameters SET bp_visible = '1' WHERE parameters = 'investigation_dashboard_max_selection';
UPDATE sys_global_parameters SET bp_visible = '1' WHERE parameters = 'mail_reply';
UPDATE sys_global_parameters SET bp_visible = '1' WHERE parameters = 'max_nb_charts_onmouseover';
UPDATE sys_global_parameters SET bp_visible = '1' WHERE parameters = 'na_label_character_max';
UPDATE sys_global_parameters SET bp_visible = '1' WHERE parameters = 'product_name';
UPDATE sys_global_parameters SET bp_visible = '1' WHERE parameters = 'product_version';
UPDATE sys_global_parameters SET bp_visible = '1' WHERE parameters = 'report_files_history';
UPDATE sys_global_parameters SET bp_visible = '1' WHERE parameters = 'ri';
UPDATE sys_global_parameters SET bp_visible = '1' WHERE parameters = 'tabgraph_color';

-- 09/09/2011 NSE DE Master 5.1 / Salve 5.0
SELECT insert_into_table_with_check('sys_global_parameters','parameters', '''min_slave_cb_version''',
	'''min_slave_cb_version'',''5.0.5.10'',0,''Minimal compatible CB version'',''Minimal CB version that can be added as slave of this product''',
	'parameters, value, configure, label, comment');

-- 15/11/2011 BBX
-- BZ 23222 : ajout d'une step pour loger la fin du retrieve
DELETE FROM sys_definition_step WHERE script = '/scripts/retrieve_stop.php';
INSERT INTO sys_definition_step (
    step_id,
    step_name,
    script,
    step_type,
    on_off,
    family_id,
    ordre,
    visible) 
VALUES (
    (SELECT MAX(step_id) + 1 FROM sys_definition_step),
    'Retrieve - stop',                                                                      --step_name
    '/scripts/retrieve_stop.php',                                                           --script
    'internal',                                                                             --step_type
    1,                                                                                      --on_ff
    (SELECT family_id FROM sys_definition_family WHERE family_name = 'Retrieve')::text,     --family_id
    (SELECT MAX(ordre)+1 FROM sys_definition_step WHERE family_id=(SELECT family_id FROM sys_definition_family WHERE family_name = 'Retrieve')::text),--ordre
    1);

-- 06/12/2011 ACS DE HTTPS support
SELECT create_column_with_check('sys_definition_product', 'sdp_http', 'smallint', '1');
SELECT create_column_with_check('sys_definition_product', 'sdp_https', 'smallint', '0');
SELECT create_column_with_check('sys_definition_product', 'sdp_https_port', 'text', '');

-- 21/11/2011 NSE bz 23942 : redéfinition de la fonction contextDeleteElementToDelete()
-- 21/11/2011 BBX bz 24786 : redéfinition de la fonction contextDeleteElementToDelete()
--
-- Création d'une fonction pg/psql qui est appelée lorsque l'on monte un contexte pour supprimer les éléments à supprimer
--
CREATE OR REPLACE FUNCTION contextDeleteElementToDelete () RETURNS void AS $$
DECLARE
	element RECORD;
BEGIN
        -- 21/11/2011 NSE bz 23942 : suppression de la condition "WHERE sdctk_sdctk_id IS NULL" pour nettoyer également les tables graph_information, graph_data
	FOR element IN SELECT sdctk_column, sdcetd_table, sdcetd_id FROM ctx_sys_definition_context_element_to_delete LEFT JOIN (sys_definition_context_table LEFT JOIN sys_definition_context_table_key ON (sdct_id = sdctk_sdct_id) ) ON (sdct_table = sdcetd_table) LOOP
		-- Requete qui supprime lélément
		EXECUTE 'DELETE FROM '||quote_ident(element.sdcetd_table)||' WHERE '||quote_ident(element.sdctk_column)||' = '||quote_literal(element.sdcetd_id);
		-- Requete qui supprime la ligne de la table sys_definition_context_element_to_delete
                -- 21/11/2011 NSE bz 23942 : insertion dans sys_definition_context_element_to_delete au lieu de suppression
		EXECUTE 'INSERT INTO sys_definition_context_element_to_delete (sdcetd_id, sdcetd_table) VALUES ( '||quote_literal(element.sdcetd_id)||','||quote_literal(element.sdcetd_table)||')' ;
	END LOOP;
END
$$ LANGUAGE plpgsql;


-- 08/03/2012 SPD BZ 26316 creation d'un index pour corriger pb de perf
-- 13/04/2012 BBX BZ 26316 on ne droppe pas l'index s'il existe
CREATE OR REPLACE FUNCTION index_sys_aa_base() RETURNS VOID AS $$
DECLARE 
	idx_exists INTEGER;
BEGIN
	SELECT INTO idx_exists COUNT(indexname) FROM pg_indexes
		WHERE schemaname = 'public'
		AND tablename = 'sys_aa_base'
		AND indexname = 'ix_sys_aa_base';

	IF idx_exists = 0 THEN
            CREATE INDEX ix_sys_aa_base
            ON sys_aa_base
            USING btree
            (saab_ta , saab_hourstart );
	END IF;
END;
$$ LANGUAGE PLPGSQL VOLATILE;
SELECT index_sys_aa_base();
DROP FUNCTION index_sys_aa_base();
  
-- 09/12/2011 NSE DE new parameters in AA links contextual filters : ajout des colonnes
SELECT create_column_with_check('sys_aa_contextuel_filter','saacf_use_code','smallint','0');
SELECT create_column_with_check('sys_aa_contextuel_filter','saacf_use_case','smallint','0');


-- 06/12/2011 BBX
-- BZ 20827 : Ajout du paramètre global "vacuum_full" activé par défaut
SELECT insert_into_table_with_check('sys_global_parameters','parameters', '''vacuum_full''',
	'''vacuum_full'',''1'',0,''customisateur'',''Enable Vacuum Full'',''Launches Vacuum Full every Sunday''',
	'parameters, value, configure, client_type, label, comment');

-- 23/11/2012 BBX
-- BZ 30587 : Nouveau paramètre permettant de stocker le jour à computer
SELECT insert_into_table_with_check('sys_global_parameters','parameters', '''day_to_compute''',
	'''day_to_compute'','''',0,'''',''''',
	'parameters, value, configure, label, comment');

-- 12/12/2012 BBX
-- BZ 30841 : changement de cétégorie pour le paramètre week_starts_on_monday
UPDATE sys_global_parameters
SET category = 2
WHERE parameters = 'week_starts_on_monday'
AND category != 2;

-- 18/02/2013 GFS - BZ#32049 : [REC][v5.1.6.34][Multi-produit] Context not well mounted when product is added
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) 
SELECT (SELECT max(sdctk_id)+1 FROM sys_definition_context_table_key), 'id_page', sdct_id, null
FROM sys_definition_context_table
WHERE sdct_table = 'graph_information'
AND NOT EXISTS (
	SELECT * FROM sys_definition_context_table_key k, sys_definition_context_table t
	WHERE k.sdctk_sdctk_id IS NULL
	AND t.sdct_id = k.sdctk_sdct_id
	AND t.sdct_table = 'graph_information'
);

INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) 
SELECT (SELECT max(sdctk_id)+1 FROM sys_definition_context_table_key), 'sds_id_selecteur', sdct_id, null
FROM sys_definition_context_table
WHERE sdct_table = 'sys_definition_selecteur'
AND NOT EXISTS (
	SELECT * FROM sys_definition_context_table_key k, sys_definition_context_table t
	WHERE k.sdctk_sdctk_id IS NULL
	AND t.sdct_id = k.sdctk_sdct_id
	AND t.sdct_table = 'sys_definition_selecteur'
);

INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) 
SELECT (SELECT max(sdctk_id)+1 FROM sys_definition_context_table_key), 'sdd_id_page', sdct_id, null
FROM sys_definition_context_table
WHERE sdct_table = 'sys_definition_dashboard'
AND NOT EXISTS (
	SELECT * FROM sys_definition_context_table_key k, sys_definition_context_table t
	WHERE k.sdctk_sdctk_id IS NULL
	AND t.sdct_id = k.sdctk_sdct_id
	AND t.sdct_table = 'sys_definition_dashboard'
);

--      LAISSER A LA FIN DU FICHIER !
-- 08/09/2011 BBX
-- BZ 23652 : Réparation des séquence en fin de traitement
SELECT repare_sequence();

---
--	SUPPRESSION DE LA LIBRAIRIE LOCALE DE FONCTIONS USUELLES
---
DROP FUNCTION IF EXISTS create_column_with_check(text, text, text, text);
DROP FUNCTION IF EXISTS create_table_with_check(text);
DROP FUNCTION IF EXISTS insert_into_table_with_check(text, text, text, text, text);
DROP FUNCTION IF EXISTS migration_data_export_target_dir();
DROP FUNCTION IF EXISTS repare_sequence();
DROP FUNCTION IF EXISTS dropfk(text, text);
VACUUM ANALYZE sys_definition_step;
VACUUM ANALYZE sys_global_parameters;
