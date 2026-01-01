---
-- Mise à jour SQL spécifiques à la version 5.2
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

-- 02/07/2012 NSE bz 27854 : API should no more uses users table -> use of global parameters
-- admin user
SELECT insert_into_table_with_check('sys_global_parameters',
	'parameters', '''api_login_admin''',
	'''api_login_admin'',''ast_supervision'',''1'',''customisateur'',''API login for admin access level''',
	'parameters, value, configure, client_type, label');
SELECT insert_into_table_with_check('sys_global_parameters',
	'parameters', '''api_password_admin''',
	'''api_password_admin'',''M29JVlZucEk1VWUyRlNs'',''1'',''customisateur'',''API password for admin access level''',
	'parameters, value, configure, client_type, label');
-- astellia admin user (pour Xpert)
SELECT insert_into_table_with_check('sys_global_parameters',
	'parameters', '''api_login_astellia_admin''',
	'''api_login_astellia_admin'',''astellia_admin'',''1'',''customisateur'',''API login for astellia admin access level''',
	'parameters, value, configure, client_type, label');
SELECT insert_into_table_with_check('sys_global_parameters',
	'parameters', '''api_password_astellia_admin''',
	'''api_password_astellia_admin'',''YXN0ZWxsaWFfYWRtaW4='',''1'',''customisateur'',''API password for astellia admin access level''',
	'parameters, value, configure, client_type, label');

-- 23/11/2012 BBX
-- BZ 30587 : Nouveau paramètre permettant de stocker le jour à computer
SELECT insert_into_table_with_check('sys_global_parameters','parameters', '''day_to_compute''',
	'''day_to_compute'','''',0,'''',''''',
	'parameters, value, configure, label, comment');

-- 04/12/2012 BBX
-- BZ 30852 : no '&' allowed in product labels
UPDATE sys_definition_product
SET sdp_label = replace(sdp_label, '&', '');

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
VACUUM ANALYZE sys_definition_product;