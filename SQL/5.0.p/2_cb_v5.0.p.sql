---
-- Mise à jour SQL spécifiques à la version 5.0.1
---

---
--	15/03/2010 BBX. BZ 14387
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

---
--	PATCH
---

-- Affectation du menu Setup Corporate uniquement au profil Astellia Administrator si pas déjà fait
SELECT insert_into_table_with_check('profile_menu_position',
	'id_menu',
	'(SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu = ''Setup Corporate'' LIMIT 1)',
	'(SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu = ''Setup Corporate'' LIMIT 1),(SELECT id_profile FROM profile WHERE profile_name = ''Astellia Administrator''),3,(SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu = ''SETUP'')','');

-- PARSER DEF : création de la table sys_link_filetype_grouptable
SELECT create_table_with_check('sys_link_filetype_grouptable');
SELECT create_column_with_check('sys_link_filetype_grouptable','id_group_table','integer','');
SELECT create_column_with_check('sys_link_filetype_grouptable','flat_file_id','integer','');

-- PARSER DEF : création de la colonne concat_code_connection
SELECT create_column_with_check('sys_definition_network_agregation','concat_code_connection','bool','');

-- 07/10/2009 BBX : Ajout du code produit "def"
SELECT insert_into_table_with_check('sys_aa_interface', 'saai_module', '''def''', '1,''def''','');

-- 11:55 15/10/2009 SCT : ajout de la colonne sur la table edw_object_ref
SELECT create_column_with_check('edw_object_ref','eor_color','text','');

-- 09:02 19/10/2009 SCT : ajout de la colonne sur la table sys_definition_network_agregation
SELECT create_column_with_check('sys_definition_network_agregation','allow_color','smallint','');

-- maj 06/10/2009 MPR : Ajout des paramètres trx et charge dans la topo pour le calcul Erlang B
-- 18/02/2010 NSE bz 13929 : changement de méthode pour la création de la colonne pour éviter message d'erreur sur add column si elle existe déjà
-- 05/04/2010 NSE bz 14636 : test existence sur eorp_trx au lieu de eorp_charge
SELECT create_column_with_check('edw_object_ref_parameters','eorp_trx','integer','');
SELECT create_column_with_check('edw_object_ref_parameters','eorp_charge','integer','1');

-- maj 06/10/2009 MPR : Ajout du paramètre
SELECT insert_into_table_with_check('sys_global_parameters',
	'parameters', '''activate_capacity_planing''',
	'''activate_capacity_planing'',''0'',''1'',''customisateur'',''Erlang B Calculation Activation'',2,19,''This parameter allows to enable or disable the Erlang B Calculation''',
	'parameters, value, configure, client_type, label, category, order_parameter, comment');

-- maj 06/10/2009 MPR : Ajout du paramètre
-- 15/03/2010 NSE bz 14732 : activate_trx_charge_in_topo à 0 par défaut
SELECT insert_into_table_with_check('sys_global_parameters',
	'parameters', '''activate_trx_charge_in_topo''',
	'''activate_trx_charge_in_topo'',''0'',''1'',''customisateur'',''Parameters Trx and Charge In Topology'',2,20,''Parameters trx and charge are used in topology''',
	'parameters, value, configure, client_type, label, category, order_parameter, comment');

-- 17:20 22/10/2009 GHX
SELECT create_column_with_check('sys_field_reference','sfr_sdp_id','int','');
SELECT create_column_with_check('sys_field_reference','sfr_product_family','text','');
SELECT create_column_with_check('sys_export_raw_kpi_config','add_suffix','text','NULL');

ALTER TABLE sys_export_raw_kpi_config ALTER COLUMN add_prefix DROP DEFAULT;
ALTER TABLE sys_export_raw_kpi_config ALTER COLUMN add_prefix TYPE text;
ALTER TABLE sys_export_raw_kpi_config ALTER COLUMN add_prefix SET DEFAULT NULL;

-- Ajout d'une colonne qui permet de savoir à quoi correspond le Data Export
SELECT create_column_with_check('sys_export_raw_kpi_config','export_type','smallint','0');
COMMENT ON COLUMN sys_export_raw_kpi_config.export_type IS 'Permet de savoir a quoi correspond le Data Export : 0 = export standard / 1 = export pour le Corporate / 2 = export pour le produit Mixed KPI';

-- 23/10/2009 BBX : Ajout de la colonne "specific" à la table sys_global_parameters. BZ
SELECT create_column_with_check('sys_global_parameters','specific','smallint','0');
COMMENT ON COLUMN sys_global_parameters.specific IS 'Permet d''informer que le paramètre est spécifique (au parser ou autre) et ne doit être effacé suite à des mises à jour de CB';

-- 11:33 02/11/2009 GHX
-- Création de 2 colonnes dans la sys_definition_kpi pour le produit Mixed KPI
SELECT create_column_with_check('sys_definition_kpi','sdk_sdp_id','int','');
SELECT create_column_with_check('sys_definition_kpi','sdk_product_family','text','');
SELECT create_column_with_check('sys_definition_kpi','old_id_ligne','text','');
SELECT create_column_with_check('sys_field_reference','old_id_ligne','text','');

--
--  10:10 10/11/2009 GHX
-- Correction du BZ 12509 [SUP][T&A Cigale GSM 5.0][Only]:Répertoire de sortie des Export data migrés erroné
--
SELECT migration_data_export_target_dir();

-- 11:20 10/11/2009 GHX
-- Ajout d'un mode débugage pour la génération des Data Exports
SELECT insert_into_table_with_check('sys_debug',
	'parameters', '''data_export''',
	'''data_export'', 0, ''Mode débug pour la génération des Data Exports (0 : désactivé / 1 : activé)''',
	'parameters, value, commentaire');

-- Ajout du paramètre extrapolation_nb_ta
SELECT insert_into_table_with_check('sys_global_parameters',
	'parameters', '''extrapolation_nb_ta''',
	'''extrapolation_nb_ta'',''10'',''customisateur'',''Extrapolation - Number of time agregations'',''1'',''Number of time aggregations on which search extrapolated datas'',4',
	'parameters, value,client_type, label, configure, comment,category');

-- maj 06/10/2009 MPR
-- Ajout du paramètre extrapolation_activate
SELECT insert_into_table_with_check('sys_global_parameters',
	'parameters', '''extrapolation_activate''',
	'''extrapolation_activate'',''0'',''customisateur'',''Extrapolation - Activation'',''1'',''Activate Datas Extrapolation'',4',
	'parameters, value,client_type, label, configure, comment,category');

-- 20/11/2009 MPR
-- Ajout du mode debug de l'extrapolation des données
SELECT insert_into_table_with_check('sys_debug',
	'parameters', '''extra_data''',
	'''extra_data'',0,''Extrapolation des donnees''',
	'parameters,value,commentaire');

----------------------------------------------------------------
-- r.baril (c) Astellia 09/10/2009
-- Parallélisation de la collecte des fichiers pendant un compute
-----------------------------------------------------------------

-- Ajout du master dans la table sys_definition_master pour la collecte des fichiers

-- 01/02/2011 NSE bz 19738 : ne pas écraser la conf pour le process de Collect s'il existe déjà :
-- utilisation de la fonction insert_into_table_with_check
SELECT insert_into_table_with_check('sys_definition_master', 'master_name', '''Collect Files''',
                                    '(SELECT MAX(master_id) + 1 FROM sys_definition_master),''Collect Files'',      --master_id	--master_name																--master_name
                                    (SELECT utps FROM sys_definition_master WHERE master_id = 11),                  --utps
                                    (SELECT offset_time - 1 FROM sys_definition_master WHERE master_id = 11),       --offset_time
                                    (SELECT on_off FROM sys_definition_master WHERE master_id = 11),                --on_off
                                    FALSE,                                                                          --auto
                                    1,                                                                              --visible
                                    (SELECT MAX(ordre) + 1 FROM sys_definition_master),                             --ordre
                                    ''Dissociation de la collecte des fichiers''                                    --commentaire
                                    ',
                                    'master_id, master_name, utps, offset_time, on_off, auto, visible, ordre, commentaire');

SELECT insert_into_table_with_check('sys_definition_master_ref', 'master_name', '''Collect Files''',
                                    '(SELECT MAX(master_id) + 1 FROM sys_definition_master_ref),''Collect Files'',  --master_id	--master_name																--master_name
                                    (SELECT utps FROM sys_definition_master_ref WHERE master_id = 11),              --utps
                                    (SELECT offset_time - 1 FROM sys_definition_master_ref WHERE master_id = 11),   --offset_time
                                    (SELECT on_off FROM sys_definition_master_ref WHERE master_id = 11),            --on_off
                                    FALSE,                                                                          --auto
                                    1,                                                                              --visible
                                    (SELECT MAX(ordre) + 1 FROM sys_definition_master_ref),                         --ordre
                                    ''Dissociation de la collecte des fichiers''                                    --commentaire
                                    ',
                                    'master_id, master_name, utps, offset_time, on_off, auto, visible, ordre, commentaire');
-- fin 01/02/2011 NSE bz 19738

-- Création d'une nouvelle famille pour le master
DELETE FROM sys_definition_family WHERE family_name = 'Collect Files Process';
INSERT INTO sys_definition_family (	family_id,
	family_name,
	family_type,
	master_id,
	ordre,
	id_group_table,
	on_off) VALUES (
	(SELECT MAX(family_id) + 1 FROM sys_definition_family), 									--family_id
	'Collect Files Process', 																	--family_name
	'internal', 																				--family_type
	(SELECT master_id FROM sys_definition_master WHERE master_name = 'Collect Files'),			--master_id
	0, 																							--ordre
	0, 																							--id_group_table
	1);																							--on_off

-- Désactivation de la step de collecte des fichiers pour le retrieve
UPDATE sys_definition_step SET on_off = 0 WHERE script = '/scripts/edw_w_flat_file_upload.php';

-- Ajout d'une step pour la nouvelle famille
DELETE FROM sys_definition_step WHERE family_id = (SELECT family_id::text FROM sys_definition_family WHERE family_name = 'Collect Files Process');
INSERT INTO sys_definition_step (	step_id,
	step_name,
	script,
	step_type,
	on_off,
	family_id,
	ordre,
	visible) VALUES (
	(SELECT MAX(step_id) + 1 FROM sys_definition_step),											--step_id
	'Collect Files Process - upload flat files', 												--step_name
	'/scripts/edw_w_flat_file_upload.php', 														--script
	'internal', 																				--step_type
	1, 																							--on_off
	(SELECT family_id FROM sys_definition_family WHERE family_name = 'Collect Files Process'),	--family_id
	0, 																							--ordre
	1);																							--visible

-- New table for parallelisation
DROP TABLE IF EXISTS sys_definition_master_compatibility;
CREATE TABLE sys_definition_master_compatibility
(
  master_id smallint NOT NULL,
  master_compatible smallint NOT NULL
)
WITH (OIDS=FALSE);
ALTER TABLE sys_definition_master_compatibility OWNER TO postgres;

DELETE FROM sys_definition_master_compatibility WHERE master_id = 11 AND master_compatible = 14 ;
INSERT INTO sys_definition_master_compatibility VALUES (11, 14);
DELETE FROM sys_definition_master_compatibility WHERE master_id = 12 AND master_compatible = 14;
INSERT INTO sys_definition_master_compatibility VALUES (12, 14);
DELETE FROM sys_definition_master_compatibility WHERE master_id = 4 AND master_compatible = 11;
INSERT INTO sys_definition_master_compatibility VALUES (4, 11);
DELETE FROM sys_definition_master_compatibility WHERE master_id = 4 AND master_compatible = 14;
INSERT INTO sys_definition_master_compatibility VALUES (4, 14);
DELETE FROM sys_definition_master_compatibility WHERE master_id = 13 AND master_compatible = 12;
INSERT INTO sys_definition_master_compatibility VALUES (13, 12);
DELETE FROM sys_definition_master_compatibility WHERE master_id = 13 AND master_compatible = 14;
INSERT INTO sys_definition_master_compatibility VALUES (13, 14);
DELETE FROM sys_definition_master_compatibility WHERE master_id = 14 AND master_compatible = 11;
INSERT INTO sys_definition_master_compatibility VALUES (14, 11);
DELETE FROM sys_definition_master_compatibility WHERE master_id = 14 AND master_compatible = 12;
INSERT INTO sys_definition_master_compatibility VALUES (14, 12);
DELETE FROM sys_definition_master_compatibility WHERE master_id = 14 AND master_compatible = 4;
INSERT INTO sys_definition_master_compatibility VALUES (14, 4);
DELETE FROM sys_definition_master_compatibility WHERE master_id = 14 AND master_compatible = 13;
INSERT INTO sys_definition_master_compatibility VALUES (14, 13);
--10:42 11/12/2009 GHX
-- Correction du BZ 13324 [REC][COLLECTE] : compute booster KO
DELETE FROM sys_definition_master_compatibility WHERE master_id = 4 AND master_compatible = 12;
INSERT INTO sys_definition_master_compatibility VALUES (4, 12);

-- 17:11 30/11/2009 GHX
-- Ajout d'une colonne pour faire un tri dessus au lieu de l'OID
SELECT create_column_with_check('sys_process_encours', 'timestamp', 'timestamp', 'CURRENT_TIMESTAMP');

-- 11/12/2009 BBX
-- Mise à jour de la requête qui récupère les KPI pour l'IHM Data Range
-- BZ 13303
-- 23/02/2010 NSE bz 13929 : ajout du ; à la fin
UPDATE sys_pauto_family
SET family_query =
(
	SELECT replace(family_query,'new_field=0','new_field<>2') AS family_query
	FROM sys_pauto_family
	WHERE id_univers = 32
	AND class_object = 'kpi'
)
WHERE id_univers = 32
AND class_object = 'kpi';

-- 12:00 15/12/2009 NSE
-- Optimisation de la collecte
-- abandon du tableau archived_files_info au profit d'une requête directe sur la table sys_flat_file_uploaded_list_archive indexée.
-- bz 12472
-- BZ 13303
-- 18/02/2010 NSE bz 13929 : ajout de if exists
DROP INDEX IF EXISTS ix__sys_flat_file_uploaded_list_archive__uniqid;

CREATE INDEX ix__sys_flat_file_uploaded_list_archive__uniqid
  ON sys_flat_file_uploaded_list_archive
  USING btree
  (flat_file_uniqid);

-- 04/01/2010 BBX : suppression du paramètre Corporate. BZ 13261
DELETE FROM sys_global_parameters WHERE parameters = 'corporate';

-- 07/01/2010 NSE : suppression du paramètre id_country. BZ 13658
DELETE FROM sys_global_parameters WHERE parameters = 'id_country';

-- 16:58 01/02/2010 MPR : Ajout du paramètre BZ14004
-- 18/02/2010 NSE bz 13929 : changement de méthode pour la création de la colonne pour éviter message d'erreur sur drop column si elle n'existe pas
SELECT create_column_with_check('sys_definition_connection','connection_mode','text','');

-- 24/02/2010 MPR : Correction du BZ 14368
DELETE FROM sys_definition_family WHERE family_id::text NOT IN (SELECT family_id FROM sys_definition_step);

-- 04/03/2010 MPR : Correction du BZ 14255
-- 15/03/2010 BBX : Correction de la correction
UPDATE sys_global_parameters
SET label = 'Number of Counters/KPIs',
	comment = 'Maximum number of active Counters/KPIs allowed'
WHERE parameters = 'maximum_mapped_counters';
--
UPDATE sys_global_parameters
SET value = '1570'
WHERE parameters = 'maximum_mapped_counters'
AND value::integer > 1570;

-- 05/03/2010 NSE bz 14366 on dédoublonne profile_menu_position
DROP TABLE IF EXISTS tmp_pmp_dedouble;
CREATE TABLE tmp_pmp_dedouble AS SELECT * FROM profile_menu_position GROUP BY id_menu, id_profile, id_menu_parent, position ORDER BY id_menu;
TRUNCATE profile_menu_position;
INSERT INTO profile_menu_position (SELECT * FROM tmp_pmp_dedouble);
-- 31/03/2010 NSE correction du dédoublonnage prenant en compte que la position peut être différente -> également supprimer même si position différente
DROP TABLE IF EXISTS tmp_pmp_dedouble;
CREATE TABLE tmp_pmp_dedouble AS
	SELECT pmp2.id_menu, pmp2.id_profile, pmp2.position, pmp2.id_menu_parent
	FROM profile_menu_position pmp2,
		(
			SELECT min(position) as position,id_menu, id_profile, id_menu_parent
			FROM profile_menu_position
			GROUP BY id_menu, id_profile, id_menu_parent order by id_menu
		) pmp
	WHERE pmp.id_menu=pmp2.id_menu
	AND pmp.id_profile=pmp2.id_profile
	AND pmp.position=pmp2.position;
TRUNCATE profile_menu_position;
INSERT INTO profile_menu_position (SELECT * FROM tmp_pmp_dedouble);
DROP TABLE tmp_pmp_dedouble;

-- 15/03/2010 BBX : Ajout de la colonne use_in_corporate. BZ 14392
SELECT create_column_with_check('sys_definition_context_table','use_in_corporate','smallint','1');
UPDATE sys_definition_context_table
SET use_in_corporate = 0
WHERE sdct_table IN ('sys_definition_flat_file_lib','sys_definition_group_table_network','sys_definition_group_table_time');

-- 25/03/2010 BBX : ajout de la colonne "sdp_trigram" dans "sys_definition_product"
SELECT create_column_with_check('sys_definition_product','sdp_trigram','char(3)','NULL');

-- 24/03/2010 NSE bz ajout du paramètre pour la limitation du nombre de kpi utilisant la fonction erlang
-- 02/04/2010 CCT1 modification du parametre order_parameter qui etait a 20 et devient 21.
SELECT insert_into_table_with_check('sys_global_parameters',
	'parameters', '''max_kpi_using_erlang''',
	'''max_kpi_using_erlang'',''20'',''1'',''customisateur'',''Maximum number of KPI using Erlangb'',2,21,''Maximum number of KPIs using Erlangb function in their formula.''',
	'parameters, value, configure, client_type, label, category, order_parameter, comment');

-- 05/05/2010 MPR : Suppression du paramètre alarm_missing_files_temporization dans sys_global_parameters (paramètre obselète) BZ 15072
DELETE FROM sys_global_parameters WHERE parameters = 'alarm_missing_files_temporization';

INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (4, 'id', 4, null);


-- 28/05/2010 MPR : DE Source Availability
-- maj 06/10/2009 MPR : Ajout du paramètre Source Availability Activation
SELECT insert_into_table_with_check('sys_global_parameters',
	'parameters', '''activation_source_availability''',
	'''activation_source_availability'',''0'',''1'',''customisateur'',''Source Availability Activation'',2,22,''1= Activate Source Availability / 0 = Deactivate Source Availability''',
	'parameters, value, configure, client_type, label, category, order_parameter, comment');

-- Création de la table sys_definition_sa_view
SELECT create_table_with_check('sys_definition_sa_view');
SELECT create_column_with_check('sys_definition_sa_view','sdsv_ta','text','');
SELECT create_column_with_check('sys_definition_sa_view','sdsv_id_connection','integer','');
SELECT create_column_with_check('sys_definition_sa_view','sdsv_ta_value','text','');
SELECT create_column_with_check('sys_definition_sa_view','sdsv_calcul_sa','double precision','');

-- Création des colonnes nb_chunks_in_file
SELECT create_column_with_check('sys_flat_file_uploaded_list_archive','nb_chunks_in_file','integer','1');
-- Création des colonnes nb_chunks_expected_in_file
SELECT create_column_with_check('sys_flat_file_uploaded_list_archive','nb_chunks_expected_in_file','integer','1');

-- Création des colonnes data_collection_frequency, data_chunks, granularity dans sys_definition_flat_file_lib
-- Fréquence de dépôt des fichiers sources dans le répertoire de collecte de chaque connexion
SELECT create_column_with_check('sys_definition_flat_file_lib','data_collection_frequency','real','');
-- Nombre de fichiers attendus par défaut
SELECT create_column_with_check('sys_definition_flat_file_lib','data_chunks','integer','');
-- TA sur lesquelles sont basées les données des fichiers source
SELECT create_column_with_check('sys_definition_flat_file_lib','granularity','text','');

-- Création de la table sys_definition_sa_config
SELECT create_table_with_check('sys_definition_sa_config');
SELECT create_column_with_check('sys_definition_sa_config','sdsc_color','text','');
SELECT create_column_with_check('sys_definition_sa_config','sdsc_seuil_min','integer','');
SELECT create_column_with_check('sys_definition_sa_config','sdsc_seuil_max','integer','');

-- Insertion des seuils dans sys_definition_sa_config
SELECT insert_into_table_with_check('sys_definition_sa_config', 'sdsc_color','''#00C000''','''#00C000'',100,100','sdsc_color, sdsc_seuil_min, sdsc_seuil_max');
SELECT insert_into_table_with_check('sys_definition_sa_config', 'sdsc_color','''#80FF00''','''#80FF00'',61,99','sdsc_color, sdsc_seuil_min, sdsc_seuil_max');
SELECT insert_into_table_with_check('sys_definition_sa_config', 'sdsc_color','''yellow''','''yellow'',41,60','sdsc_color, sdsc_seuil_min, sdsc_seuil_max');
SELECT insert_into_table_with_check('sys_definition_sa_config', 'sdsc_color','''#FF8000''','''#FF8000'',21,40','sdsc_color, sdsc_seuil_min, sdsc_seuil_max');
SELECT insert_into_table_with_check('sys_definition_sa_config', 'sdsc_color','''red''','''red'',0,20','sdsc_color, sdsc_seuil_min, sdsc_seuil_max');
SELECT insert_into_table_with_check('sys_definition_sa_config', 'sdsc_color','''#C0C0C0''','''#C0C0C0'',null,null','sdsc_color, sdsc_seuil_min, sdsc_seuil_max');


-- Création de la table sys_definition_sa_view
SELECT create_table_with_check('sys_definition_sa_view');
SELECT create_column_with_check('sys_definition_sa_view','sdsv_ta','text','');
SELECT create_column_with_check('sys_definition_sa_view','sdsv_id_connection','integer','');
SELECT create_column_with_check('sys_definition_sa_view','sdsv_ta_value','text','');
SELECT create_column_with_check('sys_definition_sa_view','sdsv_calcul_sa','double precision','');


-- Table contenant les types de fichiers utilisés pour le calcul de SA pour chaque connexion
SELECT create_table_with_check('sys_definition_sa_file_type_per_connection');
SELECT create_column_with_check('sys_definition_sa_file_type_per_connection','sdsftpc_id_connection','integer','');
SELECT create_column_with_check('sys_definition_sa_file_type_per_connection','sdsftpc_id_flat_file','integer','');
SELECT create_column_with_check('sys_definition_sa_file_type_per_connection','sdsftpc_data_chunks','integer','');

-- Création des index sur la table sys_definition_sa_view
DROP INDEX IF EXISTS sys_definition_sa_view_connection;
CREATE INDEX sys_definition_sa_view_connection
  ON sys_definition_sa_view
  USING btree
  (sdsv_id_connection);


DROP INDEX IF EXISTS sys_definition_sa_view_ta;
CREATE INDEX sys_definition_sa_view_ta
  ON sys_definition_sa_view
  USING btree
  (sdsv_ta);

DROP INDEX IF EXISTS sys_definition_sa_view_ta_value;
CREATE INDEX sys_definition_sa_view_ta_value
  ON sys_definition_sa_view
  USING btree
  (sdsv_ta_value);


DROP INDEX IF EXISTS sys_definition_sa_view_ta_id_connection_ta_value;
CREATE INDEX sys_definition_sa_view_ta_id_connection_ta_value
  ON sys_definition_sa_view
  USING btree
  (sdsv_ta, sdsv_ta_value, sdsv_id_connection);


SELECT insert_into_table_with_check('sys_debug',
	'parameters', '''src_availability''',
	'''src_availability'', 0, ''Mode debug pour Source Availability''',
	'parameters, value, commentaire');


-- Correction du bz 16421 - Valeur par défaut de paramètres globaux modifiés
UPDATE sys_global_parameters SET value = '0' WHERE parameters = 'activate_trx_charge_in_topo';
UPDATE sys_global_parameters SET value = '12' WHERE parameters = 'max_3rd_axis';
-- 23/03/2011 BBX : suppression de la mise à jour de la valeur déjà effectuée précédemment et proprement. BZ 21541
-- UPDATE sys_global_parameters SET value = '1570' WHERE parameters = 'maximum_mapped_counters';
UPDATE sys_global_parameters SET value = '120' WHERE parameters = 'report_files_history';

-- Correction bz 16758 pour DE Firefox (remplacement des navigate par des window.location)
UPDATE menu_contextuel SET url_action=E'javascript:window.location=\'alarm_index.php?mode_alarme=management&sous_mode=condense&id_menu_encours=$page_encours&product=$product&nel_selecteur=$nel_selecteur&order_on=$order_on\'' WHERE nom_action='By Alarm';
UPDATE menu_contextuel SET url_action=E'javascript:window.location=\'alarm_index.php?mode_alarme=management&sous_mode=elem_reseau&id_menu_encours=$page_encours&product=$product&nel_selecteur=$nel_selecteur&order_on=$order_on\'' WHERE nom_action='By Network Element';
UPDATE menu_contextuel SET url_action=E'javascript:window.location=\'index.php?id_dash=$id_dashboard&id_menu_encours=$id_menu_encours&mode=overnetwork\'' WHERE nom_action='Over Network Elements';
UPDATE menu_contextuel SET url_action=E'javascript:window.location=\'index.php?id_dash=$id_dashboard&id_menu_encours=$id_menu_encours&mode=overtime\'' WHERE nom_action='Over Time';

-- 20/08/2010 MMT : ajout separateur pour fichier AAcontrol  - DE firefox bz 17306
DELETE FROM sys_global_parameters WHERE parameters = 'sep_AAcontrol_filter';
INSERT INTO sys_global_parameters (parameters,value,configure,client_type,label,comment) VALUES ('sep_AAcontrol_filter','&&',0,'customisateur','.aacontrol file filter separator','.aacontrol file filter separator');

-- 2010/08/17 - MGD - BZ 17368 : modification de l'ordre d'affichage des process
-- le nouvel ordre est le suivant :
-- Collect Files (14)
-- Retrieve (10)
-- Compute Launcher Hourly (12)
-- Compute Hourly (13)
-- Compute Launcher (11)
-- Compute (4)
UPDATE sys_definition_master SET ordre=1 WHERE master_id=14;
UPDATE sys_definition_master SET ordre=2 WHERE master_id=10;
UPDATE sys_definition_master SET ordre=3 WHERE master_id=12;
UPDATE sys_definition_master SET ordre=4 WHERE master_id=13;
UPDATE sys_definition_master SET ordre=5 WHERE master_id=11;
UPDATE sys_definition_master SET ordre=6 WHERE master_id=4;

-- maj 07/09/2010 : MPR - BZ 17368 : Il faut également mettre à jour la table sys_definition_master_ref
UPDATE sys_definition_master_ref SET ordre=1 WHERE master_id=14;
UPDATE sys_definition_master_ref SET ordre=2 WHERE master_id=10;
UPDATE sys_definition_master_ref SET ordre=3 WHERE master_id=12;
UPDATE sys_definition_master_ref SET ordre=4 WHERE master_id=13;
UPDATE sys_definition_master_ref SET ordre=5 WHERE master_id=11;
UPDATE sys_definition_master_ref SET ordre=6 WHERE master_id=4;

-- 08/09/2010 MMT : DE liens Horraire + bz 17732
-- prefixe pour la date fixe predefinit entre AA et TA voir DE firefox + DE horraire
DELETE FROM sys_global_parameters WHERE parameters = 'prefix_AAcontrol_fixedDateFormat';
INSERT INTO sys_global_parameters (parameters,value,configure,client_type,label,comment) VALUES ('prefix_AAcontrol_fixedDateFormat','@DATE@',0,'customisateur','AAcontrol fixed date format prefix','AAcontrol fixed date format prefix, predifined between AA and TA in order for T&A to call AA in a non Internet Explorer browser');

-- 10/09/2010 MMT : DE liens Horraire
-- version de AAcontrole contenant la DE liens horraire et DE Firefox
DELETE FROM sys_global_parameters WHERE parameters = 'version_AAcontrol_firefoxAndhashing';
INSERT INTO sys_global_parameters (parameters,value,configure,client_type,label,comment) VALUES ('version_AAcontrol_firefoxAndhashing','3.12.5',0,'customisateur','AAcontrol version of introduction of the hashing option and firefox compatibility','Version of AAcontrol with the introduction of firefox compatibility support for T&A and the "hashing" option that should not be used with previous versions');
-- 22/09/2010 NSE bz 18077 : le gis n'est plus géré par le contexte
delete from sys_definition_context_table where sdct_table='sys_gis_config';
delete from sys_definition_context_table where sdct_table='sys_gis_config_global';
delete from sys_definition_context_table where sdct_table='sys_gis_config_palier';
delete from sys_definition_context_table where sdct_table='sys_gis_config_vecteur';
delete from sys_definition_context_table where sdct_table='geometry_columns';

-- 28/09/2010 NSE bz 14937 et support VideoTron
update sys_pauto_config set id_product=NULL where id_product=0;

---
--  13/12/2010 BBX
--  Ajout de 2 colonnes dans sys_definition_product
--  BZ 18510
---
SELECT create_column_with_check('sys_definition_product','sdp_automatic_activation','smallint','1');
SELECT create_column_with_check('sys_definition_product','sdp_last_desactivation','integer','0');

---
--  13/12/2010 BBX
--  Ajout des paramètres number_of_connection_tries et automatic_product_activation_delay
---
SELECT insert_into_table_with_check('sys_global_parameters',
	'parameters', '''number_of_connection_tries''',
	'''number_of_connection_tries'',''3'',''0'',''customisateur'',''Number of tries before disactivating a Slave'',2,20,''Number of tries before disactivating a Slave''',
	'parameters, value, configure, client_type, label, category, order_parameter, comment');
SELECT insert_into_table_with_check('sys_global_parameters',
	'parameters', '''automatic_product_activation_delay''',
	'''automatic_product_activation_delay'',''1800'',''0'',''customisateur'',''Delay before automatic activation attemp for a slave'',2,21,''Delay before automatic activation attemp for a slave''',
	'parameters, value, configure, client_type, label, category, order_parameter, comment');

---
-- 17/01/2010 BBX
-- Suppression des tables sys_pdf_mgt et user_images
-- BZ 20200
---
DROP TABLE IF EXISTS sys_pdf_mgt;
DROP TABLE IF EXISTS user_images;

-- 20/01/2011 MPR : Correction du bz 20246 - Ajout de la colonne network_axe_ri
SELECT create_column_with_check('sys_definition_categorie','network_axe_ri','smallint','1');

-- 11/02/2011 OJT : DE Selecteur/Historique
DELETE FROM sys_global_parameters WHERE parameters = 'filter_period_max';
DELETE FROM sys_global_parameters WHERE parameters = 'max_topover_ot';
INSERT INTO sys_global_parameters (parameters,value,configure,label,comment) VALUES ( 'max_topover_ot','10',0,'Maximum TopOver on overtime','Maximum TopOver value for Dashboard in overtime mode');

-- 15/02/2011 NSE DE Query Builder : Ajout du nombre maximum de résultats affichés dans Query Builder
SELECT insert_into_table_with_check('sys_global_parameters',
	'parameters', '''query_builder_nb_result_limit''',
	'''query_builder_nb_result_limit'',''1000'',''1'',''customisateur'',''Maximum results number displayed in Query Builder'',4,19,''This parameter allows to define the maximum number of results displayed in "Table Result" and "Graph Result" tabs of Query Builder''',
	'parameters, value, configure, client_type, label, category, order_parameter, comment');

-- 01/03/2011 NSE bz 17516 : Si les tables du Gis ne sont pas renseignée pour le produit mixed kpi, on les remplies à partir de dumps.

-- Insertion conditionnelle dans une table à partir d'un fichier
CREATE OR REPLACE FUNCTION insert_into_table_with_check_from_file(my_table text, my_file text, my_fields text) RETURNS VOID AS $$
DECLARE
    value_exists int;
    query_test text;
    query_insert text;
BEGIN
    query_test := 'SELECT COUNT(*) FROM '||my_table;
    EXECUTE query_test INTO value_exists;
    IF value_exists = 0 THEN
        query_insert := 'COPY ' || my_table;
        IF my_fields <> '' THEN
            query_insert := query_insert || ' ('||my_fields||')';
        END IF;
        query_insert := query_insert || ' FROM ''' || my_file || ''' WITH DELIMITER '';''';
        EXECUTE query_insert;
    END IF;
END;
$$ LANGUAGE PLPGSQL VOLATILE;

-- si on est sur un produit Mixed Kpi
CREATE OR REPLACE FUNCTION tmp_insert_into_mixed_kpi() RETURNS VOID AS $$
DECLARE
    query_test text;
    is_mixed_kpi int;
    _directory text;
BEGIN
    query_test := 'SELECT count(*) FROM pg_tables WHERE schemaname = ''public'' AND tablename = ''sys_definition_mixedkpi''';
    EXECUTE query_test INTO is_mixed_kpi;
    IF is_mixed_kpi > 0 THEN
        SELECT '/home/'||sdp_directory||'/SQL/5.0.p/' INTO _directory FROM sys_definition_product WHERE sdp_db_name like 'mixed_kpi%';
        PERFORM insert_into_table_with_check_from_file('sys_gis_config', _directory||'sys_gis_config.dump', 'id, id_palier, id_vecteur, on_off');
        PERFORM insert_into_table_with_check_from_file('sys_gis_config_global', _directory||'sys_gis_config_global.dump', '');
        PERFORM insert_into_table_with_check_from_file('sys_gis_config_vecteur', _directory||'sys_gis_config_vecteur.dump', 'id, nom, layer_order, table_data, filled_color, stroke_color, on_off, stroke_length');
        PERFORM insert_into_table_with_check_from_file('sys_gis_config_palier', _directory||'sys_gis_config_palier.dump', 'id, nom, niveau, id_parent, scale, mainscale, activation_right, on_off');
        PERFORM insert_into_table_with_check_from_file('sys_gis_data_polygon', _directory||'sys_gis_data_polygon.dump', 'id, nom, "type", layer_ordre, _geometry');
        PERFORM insert_into_table_with_check_from_file('spatial_ref_sys', _directory||'spatial_ref_sys.dump', 'srid, auth_name, auth_srid, srtext, proj4text');
        -- 18/03/2011 NSE bz 17516 (REOPEN) : mise à jour de la table sys_definition_network_agregation pour mettre à 1
        UPDATE sys_definition_network_agregation set voronoi_polygon_calculation=1 WHERE voronoi_polygon_calculation IS NULL;
    END IF;
END;
$$ LANGUAGE PLPGSQL VOLATILE;

SELECT tmp_insert_into_mixed_kpi();

DROP FUNCTION IF EXISTS tmp_insert_into_mixed_kpi();

-- 02/03/2011 MMT: bz 19128 ajout paramt pour detection version d'export alarme dans les rapports
DELETE FROM sys_global_parameters WHERE parameters = 'alarm_reporting_nonPdfFormat_enabled';
INSERT INTO sys_global_parameters (parameters,value,configure,client_type,label,comment) VALUES ( 'alarm_reporting_nonPdfFormat_enabled','1',0,'customisateur','Support of xls and doc format for alarm reporting','Support of xls and doc format for alarm reporting, 1 = yes 0 = no, if no alarm reports will always be generated in PDF format');

-- 24/02/2011 OJT : DE SFTP
DELETE FROM sys_global_parameters WHERE parameters = 'enable_sftp';
INSERT INTO sys_global_parameters (parameters,value,configure,label,comment) VALUES ( 'enable_sftp','1',0,'Enable SFTP for connection','Compatibility parameter for SFTP activation');
SELECT create_column_with_check('sys_definition_connection','connection_port','integer','');

-- 09/03/2011 OJT : Correction bz20811. Ajout des nouveaux step
CREATE OR REPLACE FUNCTION update_ordre_from_step() RETURNS VOID AS $$
DECLARE
    query_count text;
    flag_update int;
BEGIN
    query_count := 'SELECT COUNT(step_id) FROM sys_definition_step WHERE family_id=(SELECT family_id::text FROM sys_definition_family WHERE family_name=''Vacuum + Clean Files'') AND ordre=0;';
    EXECUTE query_count INTO flag_update;
    IF flag_update > 0 THEN
        UPDATE sys_definition_step SET ordre=(ordre+1) WHERE family_id=(SELECT family_id FROM sys_definition_family WHERE family_name='Vacuum + Clean Files');
    END IF;
END;
$$ LANGUAGE PLPGSQL VOLATILE;
DELETE FROM sys_definition_step WHERE step_name='Clean Columns';
SELECT update_ordre_from_step();
INSERT INTO sys_definition_step(step_id,step_name,script,step_type,on_off,family_id,ordre,visible) VALUES
((SELECT MAX(step_id) + 1 FROM sys_definition_step),'Clean Columns','/scripts/clean_columns.php','internal',1,(SELECT family_id FROM sys_definition_family WHERE family_name='Vacuum + Clean Files'),0,1);

-- 18/03/2011 OJT : Correction bz20984, remise en état de la table sys_definition_flat_file_lib
-- Mise à jour uniquement si TOUS les champs sont nuls
UPDATE sys_definition_flat_file_lib SET data_collection_frequency=24, data_chunks=1, granularity='day'
WHERE data_collection_frequency IS NULL AND data_chunks IS NULL AND granularity IS NULL AND period_type ILIKE 'day';
UPDATE sys_definition_flat_file_lib SET data_collection_frequency=1, data_chunks=24, granularity='hour'
WHERE data_collection_frequency IS NULL AND data_chunks IS NULL AND granularity IS NULL AND period_type ILIKE 'hour';

-- 04/05/2011 OJT : DE Conversion de données (05/05/2011 Correction d'une faute dans le label)
-- 04/08/2011 : bz23296, utilisation de insert_into_table_with_check
SELECT insert_into_table_with_check('sys_global_parameters','parameters', '''enable_size_unit_conversion''',
	'''enable_size_unit_conversion'',''0'',0,''Enable automatic size unit conversion on graph'',''Compatibility parameter for SizeUnitConversion activation''',
	'parameters, value, configure, label, comment');

-- 02/05/2011 MMT bz 21899 : ajout paramètre pour determiner la duree de vie des fichiers dans flat_file_upload_archive
-- 02/11/2010 ACS BZ 24404 Global parameters "flatFileArchiveDaysToLive" systematically reset after patch
SELECT insert_into_table_with_check('sys_global_parameters','parameters', '''flatFileArchiveDaysToLive''',
	'''flatFileArchiveDaysToLive'',''30'',1,''customisateur'',''Days to live for collected files'',''Number of days collected archived files in folder flat_file_upload_archive are stored before being erased'', 3, 15',
	'parameters, value, configure, client_type, label, comment, category, order_parameter');

-- 19/05/2011 OJT Ajout des colonnes dans sys_definition_selecteur pour la DE TopNALevelForBHGraph
SELECT create_column_with_check('sys_definition_selecteur','sds_fh_on_off','boolean','false' );
SELECT create_column_with_check('sys_definition_selecteur','sds_fh_na','text', '' );
SELECT create_column_with_check('sys_definition_selecteur','sds_fh_ne','text', '' );
SELECT create_column_with_check('sys_definition_selecteur','sds_fh_na_axe3','text', '' );
SELECT create_column_with_check('sys_definition_selecteur','sds_fh_ne_axe3','text', '' );
SELECT create_column_with_check('sys_definition_selecteur','sds_fh_product_bh','character varying(10)', '' );
SELECT create_column_with_check('sys_definition_selecteur','sds_fh_family_bh','text', '' );

-- 18/07/2011 OJT : DE SMS, ajout de la catégorie SMS-C dans Setup Global Parameter
DELETE FROM sys_global_parameters_categories WHERE id_category=5;
INSERT INTO sys_global_parameters_categories(id_category,label_category) VALUES (5, 'SMS-C Settings');

-- 18/07/2011 OJT : DE SMS, ajout des nouveaux paramètres globaux
-- 04/08/2011 OJT : bz23293, ecrésement systématique des paramètres globaux
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
DELETE FROM sys_global_parameters WHERE parameters = 'enable_alarm_sms';
INSERT INTO sys_global_parameters (parameters,value,configure,label,comment) VALUES ( 'enable_alarm_sms','1',0,'Enable SMS sending for alarms','Compatibility parameter for Alarm SMS activation');

-- 22/07/2011 OJT : DE SMS, création de la table sys_alarm_sms_sender
SELECT create_table_with_check('sys_alarm_sms_sender');
SELECT create_column_with_check('sys_alarm_sms_sender','id_alarm','text','');
SELECT create_column_with_check('sys_alarm_sms_sender','recipient_type','text','');
SELECT create_column_with_check('sys_alarm_sms_sender','recipient_id','text','');
SELECT create_column_with_check('sys_alarm_sms_sender','alarm_type','text','');

-- 22/07/2011 OJT : DE SMS, gestion des nouveaux step
DELETE FROM sys_definition_step WHERE step_name like '%Envoi Alarm (SMS)';
INSERT INTO sys_definition_step (
    step_id,
	step_name,
	script,
	step_type,
	on_off,
	family_id,
	ordre,
	visible) VALUES (
	(SELECT MAX(step_id) + 1 FROM sys_definition_step),					 --step_id
	'Hourly - Envoi Alarm (SMS)',                                        --step_name
	'/scripts/alarm_sms.php',           								 --script
	'internal', 														 --step_type
	1, 																	 --on_ff
	'50',                                                                  --family_id
	(SELECT MAX(ordre)+1 FROM sys_definition_step WHERE family_id='50'), --ordre
	1);
INSERT INTO sys_definition_step (
    step_id,
	step_name,
	script,
	step_type,
	on_off,
	family_id,
	ordre,
	visible) VALUES (
	(SELECT MAX(step_id) + 1 FROM sys_definition_step),					 --step_id
	'Envoi Alarm (SMS)',                                                 --step_name
	'/scripts/alarm_sms.php',           								 --script
	'internal', 														 --step_type
	1, 																	 --on_off
	'40',                                                                  --family_id
    (SELECT MAX(ordre)+1 FROM sys_definition_step WHERE family_id='40'), --ordre
	1);

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

-- 06/12/2011 BBX
-- BZ 20827 : Ajout du paramètre global "vacuum_full" activé par défaut
SELECT insert_into_table_with_check('sys_global_parameters','parameters', '''vacuum_full''',
	'''vacuum_full'',''1'',0,''customisateur'',''Enable Vacuum Full'',''Launches Vacuum Full every Sunday''',
	'parameters, value, configure, client_type, label, comment');

---
--	SUPPRESSION DE LA LIBRAIRIE LOCALE DE FONCTIONS USUELLES
--      LAISSER A LA FIN DU FICHIER !
---
DROP FUNCTION IF EXISTS create_column_with_check(text, text, text, text);
DROP FUNCTION IF EXISTS create_table_with_check(text);
DROP FUNCTION IF EXISTS insert_into_table_with_check(text, text, text, text, text);
DROP FUNCTION IF EXISTS insert_into_table_with_check_from_file(text, text, text);
DROP FUNCTION IF EXISTS migration_data_export_target_dir();
DROP FUNCTION IF EXISTS update_ordre_from_step();
VACUUM ANALYZE sys_definition_step;