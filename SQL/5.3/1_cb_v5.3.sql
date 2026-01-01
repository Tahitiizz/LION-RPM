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


----------------------------------------------------------
------		DEBUT MISE A JOUR QUERY BUILDER V2		------
----------------------------------------------------------

-------------------------------------
-- CREATION DES GLOBAL PARAMETERS ---
-------------------------------------

-- 14/09/2012 ACS DE Make it possible to configure the maximum number of displayed elements
-- 18/09/2012 ACS BZ 29174 Global parameter "Maximum number of displayed elements" not visible
-- 23/01/2013 GFS BZ 31411 [QAL][T&A Gateway][Dashboard Overtime Limitation] : Maximum number of displayed element remain at default value (10)
UPDATE sys_global_parameters SET configure = 1, client_type = 'client', label = 'Maximum number of displayed elements', comment = 'The maximum number of network aggregation elements displayed in a dashboard in over time mode (default value is 10).', category = 4, order_parameter = 6, bp_visible = 1 WHERE parameters = 'max_topover_ot';

-- 14/09/2012 ACS DE Custom fields
SELECT create_column_with_check('sys_definition_flat_file_lib', 'custom_field_1', 'text', '');
SELECT create_column_with_check('sys_definition_flat_file_lib', 'custom_field_2', 'text', '');
SELECT create_column_with_check('sys_definition_flat_file_lib', 'custom_field_3', 'text', '');

-- 03/10/2012 ACS BZ 29105 display of warning during backup processus
SELECT insert_into_table_with_check('sys_global_parameters',
	'parameters', '''lock_dump_warning''',
	'''lock_dump_warning'',''0'',''0'',''Lock to prevent multiple warning display when starting process during dump procedure''',
	'parameters, value, configure, label');


-- 03/10/2012 ACS BZ 29105 display of warning during backup processus
SELECT insert_into_table_with_check('sys_global_parameters',
	'parameters', '''topology_delete_minimum_version''',
	'''topology_delete_minimum_version'',''5.3'',''0'',''minimum version required on the product for topology delete feature to be enabled''',
	'parameters, value, configure, label');

-- 05/10/12 ACS DE GIS 3D only
SELECT insert_into_table_with_check('sys_global_parameters',
	'parameters', '''gis_kmz_elements_limit''',
	'''gis_kmz_elements_limit'',''20000'',''1'',''customisateur'', ''Gis 3D maximum number of elements '', ''Maximum number of elements exported with Gis 3D module before displayed a warning'', 4, 24, 1',
	'parameters, value, configure, client_type, label, comment, category, order_parameter, bp_visible');
UPDATE sys_global_parameters SET comment = '0 = GIS module is deactivated ; 1 = 2D and 3D GIS are activated; 2 = 3D GIS only is activated (limited mode for multi SRID countries)' WHERE parameters = 'gis';
UPDATE sys_global_parameters SET comment = '0 : Disable Voronoi Polygons  / 1 : Enable Voronoi Polygons" (if "Gis mode" is set on "3D only", Voronoi Polygons are disabled)' WHERE parameters = 'gis_display_mode';
