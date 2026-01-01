---
-- Mise à jour SQL spécifiques à DE CB 5.3.1 : Link to Nova Explorer
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


CREATE OR REPLACE FUNCTION create_sys_ne_filter_kpi() RETURNS VOID AS $$
DECLARE
	table_exists int;
	query_create text;
BEGIN
	SELECT COUNT(*) INTO table_exists
	FROM pg_tables
	WHERE schemaname = 'public'
	AND tablename = 'sys_ne_filter_kpi';
	IF table_exists = 0 THEN
                query_create := '
                CREATE TABLE sys_ne_filter_kpi (
                snefk_idfilter integer NOT NULL,
                snefk_idkpi text NOT NULL,
                snefk_type character varying(3) NOT NULL,
                snefk_family text NOT NULL,
                snefk_group_filter smallint,
                snefk_label_link text,
                snefk_interface text NOT NULL,
                snefk_xdrtype integer NOT NULL,
                CONSTRAINT sys_ne_filter_kpi_type CHECK ((((snefk_type)::text = ''kpi''::text) OR ((snefk_type)::text = ''raw''::text)))
                );
                ';
                EXECUTE query_create;

                query_create := 'ALTER TABLE public.sys_ne_filter_kpi OWNER TO postgres;';
                EXECUTE query_create;
                
                query_create := '    
                CREATE SEQUENCE sys_ne_filter_kpi_snefk_idfilter_seq
                    START WITH 1
                    INCREMENT BY 1
                    NO MAXVALUE
                    NO MINVALUE
                    CACHE 1;
                ';
                EXECUTE query_create;

                query_create := 'ALTER TABLE public.sys_ne_filter_kpi_snefk_idfilter_seq OWNER TO postgres;';
                EXECUTE query_create;

                query_create := 'ALTER SEQUENCE sys_ne_filter_kpi_snefk_idfilter_seq OWNED BY sys_ne_filter_kpi.snefk_idfilter;';
                EXECUTE query_create;

                query_create := 'SELECT pg_catalog.setval(''sys_ne_filter_kpi_snefk_idfilter_seq'', 1, false);';
                EXECUTE query_create;

                query_create := 'ALTER TABLE sys_ne_filter_kpi ALTER COLUMN snefk_idfilter SET DEFAULT nextval(''sys_ne_filter_kpi_snefk_idfilter_seq''::regclass);';
                EXECUTE query_create;

                query_create := 'ALTER TABLE ONLY sys_ne_filter_kpi ADD CONSTRAINT sys_ne_filter_kpi_pkey PRIMARY KEY (snefk_idfilter);';
                EXECUTE query_create;

                query_create := 'REVOKE ALL ON TABLE sys_ne_filter_kpi FROM PUBLIC;';
                EXECUTE query_create;

                query_create := 'REVOKE ALL ON TABLE sys_ne_filter_kpi FROM postgres;';
                EXECUTE query_create;

                query_create := 'GRANT ALL ON TABLE sys_ne_filter_kpi TO postgres;';
                EXECUTE query_create;

                query_create := 'GRANT SELECT ON TABLE sys_ne_filter_kpi TO read_only_user;';
                EXECUTE query_create;
	END IF;
END;
$$ LANGUAGE PLPGSQL VOLATILE;


CREATE OR REPLACE FUNCTION create_sys_ne_list_filter() RETURNS VOID AS $$
DECLARE
	table_exists int;
	query_create text;
BEGIN
	SELECT COUNT(*) INTO table_exists
	FROM pg_tables
	WHERE schemaname = 'public'
	AND tablename = 'sys_ne_list_filter';
	IF table_exists = 0 THEN
                query_create := '
                CREATE TABLE sys_ne_list_filter (
                    snelf_idfilter integer NOT NULL,
                    snelf_order smallint NOT NULL,
                    snelf_idcolumn integer NOT NULL,
                    snelf_idoperator integer NOT NULL,
                    snelf_value text NOT NULL
                );
                ';
                EXECUTE query_create;

                query_create := 'ALTER TABLE public.sys_ne_list_filter OWNER TO postgres;';
                EXECUTE query_create;

                query_create := 'ALTER TABLE ONLY sys_ne_list_filter ADD CONSTRAINT sys_ne_liste_filter_key PRIMARY KEY (snelf_idfilter, snelf_order);';
                EXECUTE query_create;

                query_create := 'REVOKE ALL ON TABLE sys_ne_list_filter FROM PUBLIC;';
                EXECUTE query_create;

                query_create := 'REVOKE ALL ON TABLE sys_ne_list_filter FROM postgres;';
                EXECUTE query_create;

                query_create := 'GRANT ALL ON TABLE sys_ne_list_filter TO postgres;';
                EXECUTE query_create;

                query_create := 'GRANT SELECT ON TABLE sys_ne_list_filter TO read_only_user;';
                EXECUTE query_create;
                
	END IF;
END;
$$ LANGUAGE PLPGSQL VOLATILE;


CREATE OR REPLACE FUNCTION create_sys_ne_operator() RETURNS VOID AS $$
DECLARE
	table_exists int;
	query_create text;
BEGIN
	SELECT COUNT(*) INTO table_exists
	FROM pg_tables
	WHERE schemaname = 'public'
	AND tablename = 'sys_ne_operator';
	IF table_exists = 0 THEN
                query_create := '
                CREATE TABLE sys_ne_operator (
                    sneo_idoperator integer NOT NULL,
                    sneo_name text NOT NULL
                );
                ';
                EXECUTE query_create;

                query_create := 'ALTER TABLE public.sys_ne_operator OWNER TO postgres;';
                EXECUTE query_create;

                query_create := 'ALTER TABLE ONLY sys_ne_operator ADD CONSTRAINT sys_ne_operator_pkey PRIMARY KEY (sneo_idoperator);';
                EXECUTE query_create;

                query_create := 'REVOKE ALL ON TABLE sys_ne_operator FROM PUBLIC;';
                EXECUTE query_create;

                query_create := 'REVOKE ALL ON TABLE sys_ne_operator FROM postgres;';
                EXECUTE query_create;
                
                query_create := 'GRANT ALL ON TABLE sys_ne_operator TO postgres;';
                EXECUTE query_create;

                query_create := 'GRANT SELECT ON TABLE sys_ne_operator TO read_only_user;';
                EXECUTE query_create;
	END IF;
END;
$$ LANGUAGE PLPGSQL VOLATILE;


CREATE OR REPLACE FUNCTION create_sys_ne_column() RETURNS VOID AS $$
DECLARE
	table_exists int;
	query_create text;
BEGIN
	SELECT COUNT(*) INTO table_exists
	FROM pg_tables
	WHERE schemaname = 'public'
	AND tablename = 'sys_ne_column';
	IF table_exists = 0 THEN
                query_create := '
                CREATE TABLE sys_ne_column (
                    snec_idcolumn integer NOT NULL,
                    snec_name text NOT NULL,
                    snec_withcode boolean DEFAULT false
                );
                ';
                EXECUTE query_create;

                query_create := 'ALTER TABLE public.sys_ne_column OWNER TO postgres;';
                EXECUTE query_create;
                
                query_create := 'ALTER TABLE ONLY sys_ne_column ADD CONSTRAINT sys_ne_column_pkey PRIMARY KEY (snec_idcolumn);';
                EXECUTE query_create;
               
                query_create := 'REVOKE ALL ON TABLE sys_ne_column FROM PUBLIC;';
                EXECUTE query_create;

                query_create := 'REVOKE ALL ON TABLE sys_ne_column FROM postgres;';
                EXECUTE query_create;

                query_create := 'GRANT ALL ON TABLE sys_ne_column TO postgres;';
                EXECUTE query_create;

                query_create := 'GRANT SELECT ON TABLE sys_ne_column TO read_only_user;';
                EXECUTE query_create;
	END IF;
END;
$$ LANGUAGE PLPGSQL VOLATILE;


--CREATE OR REPLACE FUNCTION create_sys_ne_column_code() RETURNS VOID AS $$
--DECLARE
--	table_exists int;
--	query_create text;
--BEGIN
--	SELECT COUNT(*) INTO table_exists
--	FROM pg_tables
--	WHERE schemaname = 'public'
--	AND tablename = 'sys_ne_column_code';
--	IF table_exists = 0 THEN
--                query_create := '
--                CREATE TABLE sys_ne_column_code (
--                    snecc_necode integer NOT NULL,
--                    snecc_idcolumn integer NOT NULL,
--                    snecc_fc_label text NOT NULL
--                );
--                ';
--                EXECUTE query_create;
--
--                query_create := 'ALTER TABLE public.sys_ne_column_code OWNER TO postgres;';
--                EXECUTE query_create;
--
--                query_create := 'REVOKE ALL ON TABLE sys_ne_column_code FROM PUBLIC;';
--                EXECUTE query_create;
--
--                query_create := 'REVOKE ALL ON TABLE sys_ne_column_code FROM postgres;';
--                EXECUTE query_create;
--
--                query_create := 'GRANT ALL ON TABLE sys_ne_column_code TO postgres;';
--                EXECUTE query_create;
--
--                query_create := 'GRANT SELECT ON TABLE sys_ne_column_code TO read_only_user;';
--                EXECUTE query_create;                
--	END IF;
--END;
--$$ LANGUAGE PLPGSQL VOLATILE;

-- 21/03/2014 GFS - Bug 40080 - [REC][Core CS 5.3.1.01][TC #TA-62630][Link to NEx]: There is a warning message in the console panel when link to NEx from "IN" dashboard
CREATE OR REPLACE FUNCTION create_sys_ne_contextuel_filter() RETURNS VOID AS $$
DECLARE
	table_exists int;
	query_create text;
BEGIN
	SELECT COUNT(*) INTO table_exists
	FROM pg_tables
	WHERE schemaname = 'public'
	AND tablename = 'sys_ne_contextuel_filter';
	IF table_exists = 0 THEN
                query_create := '
                CREATE TABLE sys_ne_contextuel_filter (
                    snecf_type text NOT NULL,
                    snecf_idcolumn integer NOT NULL,
                    snecf_group_filter smallint,
                    snecf_before_value text,
                    snecf_after_value text,
                    snecf_use_code smallint DEFAULT 0,
  					snecf_use_case smallint DEFAULT 0
                );
                ';
                EXECUTE query_create;

                query_create := 'ALTER TABLE public.sys_ne_contextuel_filter OWNER TO postgres;';
                EXECUTE query_create;

                query_create := 'REVOKE ALL ON TABLE sys_ne_contextuel_filter FROM PUBLIC;';
                EXECUTE query_create;

                query_create := 'REVOKE ALL ON TABLE sys_ne_contextuel_filter FROM postgres;';
                EXECUTE query_create;

                query_create := 'GRANT ALL ON TABLE sys_ne_contextuel_filter TO postgres;';
                EXECUTE query_create;

                query_create := 'GRANT SELECT ON TABLE sys_ne_contextuel_filter TO read_only_user;';
                EXECUTE query_create;
	END IF;
END;
$$ LANGUAGE PLPGSQL VOLATILE;

CREATE OR REPLACE FUNCTION create_column_with_table_check(table_to_check text, my_column text, my_type text, my_default text) RETURNS VOID AS
$$
BEGIN
	IF EXISTS (SELECT relname FROM pg_class WHERE relname='||table_to_check||') 
	THEN
		SELECT create_column_with_check('||table_to_check||','||my_column||','||my_type||','||my_default||');
	END IF;
END;
$$ LANGUAGE PLPGSQL VOLATILE;

---------------------------- Main ----------------------------
SELECT insert_into_table_with_check('sys_debug',
	'parameters', '''launch_NE''',
	'''launch_NE'',''0'',''mode debug pour le lien vers NE, 0 désactivé / 1 activé''',
	'parameters, value, commentaire');

-- Bug 34151 - [REC][CB 5.3.1.01][TC #TA-62510]Multi product] "Nova Explorer url" parameter should be displayed in global parameter of Master product
--SELECT insert_into_table_with_check('sys_global_parameters',
--	'parameters', '''url_NE''',
--	'''url_NE'','''',''1'',''customisateur'',''Nova Explorer url'',''Url of related Nova Explorer application to link to'',''4'',''0'',''0''',
--	'parameters, value, configure, client_type, label, comment, category, specific, bp_visible');
SELECT insert_into_table_with_check('sys_global_parameters',
	'parameters', '''url_NE''',
	'''url_NE'','''',''1'',''customisateur'',''Nova Explorer url'',''Url of related Nova Explorer application to link to'',''4'',''0'',''1''',
	'parameters, value, configure, client_type, label, comment, category, specific, bp_visible');

SELECT insert_into_table_with_check('sys_global_parameters',
	'parameters', '''size_max_url''',
	'''size_max_url'',''2048'',''0'','''','''','''',''0'',''0''',
	'parameters, value, configure, client_type, label, comment, specific, bp_visible');

SELECT create_column_with_check('sys_definition_network_agregation','link_to_ne','smallint','');
SELECT create_column_with_table_check('sys_definition_network_agregation_bckp', 'link_to_ne', 'smallint', '');

SELECT create_sys_ne_filter_kpi();

SELECT create_sys_ne_list_filter();

SELECT create_sys_ne_operator();

SELECT create_sys_ne_column();

--SELECT create_sys_ne_column_code();

SELECT create_sys_ne_contextuel_filter();

--Bug 34304 - [INT][5.3.1.02] Context tables data needed for Link to Nova Explorer
SELECT insert_into_table_with_check('sys_definition_context',
	'sdc_label', '''Link to Nova Explorer''',
	'''25'',''Link to Nova Explorer'','''',''0'',''0'',''0'',''1'',''0'','''',''25''',
	'sdc_id, sdc_label, sdc_column, sdc_default, sdc_visible, sdc_master, sdc_truncate, sdc_selected, sdc_sql_where_select_default, sdc_order_display');

SELECT insert_into_table_with_check('sys_definition_context_table_link',
	'sdctl_sdct_id', '''49''',
	'''25'',''49'',''0'',''1''',
	'sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order');

SELECT insert_into_table_with_check('sys_definition_context_table_link',
	'sdctl_sdct_id', '''50''',
	'''25'',''50'',''0'',''2''',
	'sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order');

SELECT insert_into_table_with_check('sys_definition_context_table_link',
	'sdctl_sdct_id', '''51''',
	'''25'',''51'',''0'',''3''',
	'sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order');

SELECT insert_into_table_with_check('sys_definition_context_table_link',
	'sdctl_sdct_id', '''52''',
	'''25'',''52'',''0'',''4''',
	'sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order');

SELECT insert_into_table_with_check('sys_definition_context_table_link',
	'sdctl_sdct_id', '''53''',
	'''25'',''53'',''0'',''5''',
	'sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order');

SELECT insert_into_table_with_check('sys_definition_context_table',
	'sdct_id', '''49''',
	'''49'',''sys_ne_column'',''1''',
	'sdct_id, sdct_table, use_in_corporate');

SELECT insert_into_table_with_check('sys_definition_context_table',
	'sdct_id', '''50''',
	'''50'',''sys_ne_contextuel_filter'',''1''',
	'sdct_id, sdct_table, use_in_corporate');

SELECT insert_into_table_with_check('sys_definition_context_table',
	'sdct_id', '''51''',
	'''51'',''sys_ne_list_filter'',''1''',
	'sdct_id, sdct_table, use_in_corporate');

SELECT insert_into_table_with_check('sys_definition_context_table',
	'sdct_id', '''52''',
	'''52'',''sys_ne_filter_kpi'',''1''',
	'sdct_id, sdct_table, use_in_corporate');

SELECT insert_into_table_with_check('sys_definition_context_table',
	'sdct_id', '''53''',
	'''53'',''sys_ne_operator'',''1''',
	'sdct_id, sdct_table, use_in_corporate');

SELECT insert_into_table_with_check('sys_definition_context_table_key',
	'sdctk_id', '''63''',
	'''63'',''snec_idcolumn'',''49'',''''',
	'sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id');

SELECT insert_into_table_with_check('sys_definition_context_table_key',
	'sdctk_id', '''64''',
	'''64'',''snecf_idcolumn'',''50'',''63''',
	'sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id');

SELECT insert_into_table_with_check('sys_definition_context_table_key',
	'sdctk_id', '''65''',
	'''65'',''snelf_idfilter'',''51'',''68''',
	'sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id');

SELECT insert_into_table_with_check('sys_definition_context_table_key',
	'sdctk_id', '''66''',
	'''66'',''snelf_idcolumn'',''51'',''63''',
	'sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id');

SELECT insert_into_table_with_check('sys_definition_context_table_key',
	'sdctk_id', '''67''',
	'''67'',''snelf_idoperator'',''51'',''69''',
	'sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id');

SELECT insert_into_table_with_check('sys_definition_context_table_key',
	'sdctk_id', '''68''',
	'''68'',''snefk_idfilter'',''52'',''''',
	'sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id');

SELECT insert_into_table_with_check('sys_definition_context_table_key',
	'sdctk_id', '''69''',
	'''69'',''sneo_idoperator'',''53'',''''',
	'sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id');

--Bug 34437 - [INT][5.3.1.03]: snelf_value column of sys_ne_list_filter table may be null
ALTER TABLE sys_ne_list_filter ALTER COLUMN snelf_value DROP NOT NULL;

-- 21/03/2014 GFS - Bug 40080 - [REC][Core CS 5.3.1.01][TC #TA-62630][Link to NEx]: There is a warning message in the console panel when link to NEx from "IN" dashboard
SELECT create_column_with_check('sys_ne_contextuel_filter','snecf_use_code','smallint','0');
SELECT create_column_with_check('sys_ne_contextuel_filter','snecf_use_case','smallint','0');

  					