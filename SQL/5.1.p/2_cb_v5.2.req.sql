---
-- Mise à jour SQL spécifiques à la version 5.2
-- Ajouté en 5.1.6 afin de permettre une mise en multiproduit 5.2/5.1.6
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

 SELECT insert_into_table_with_check('sys_global_parameters',
	'parameters', '''query_builder_minium_free_disk_space''',
	'''query_builder_minium_free_disk_space'',''20'',''1'',''customisateur'',''Minimum free disk space (in %) required for Query Builder'',4,19,''This parameter is used to set the minimum free diskspace (in percent) required to launch a CSV export in Query Builder.''',
	'parameters, value, configure, client_type, label, category, order_parameter, comment');

 SELECT insert_into_table_with_check('sys_global_parameters',
	'parameters', '''query_builder_export_expiry_date''',
	'''query_builder_export_expiry_date'',''1'',''1'',''customisateur'',''Query Builder CSV export expiry date'',4,19,''Removes Query Builder CSV export older than x days.''',
	'parameters, value, configure, client_type, label, category, order_parameter, comment');
	

-------------------------------------
-- CREATION DES OPTIONS DE DEBUG  ---
-------------------------------------

DELETE FROM sys_debug WHERE parameters = 'querybuilder';
INSERT INTO sys_debug (parameters, value, commentaire) VALUES ('querybuilder', 0, '0: mode buildé (javascript compressé), 1: mode debug plein de fichiers JS non compressés');

--------------------------
-- CREATION DES TABLES ---
--------------------------

-- Création de la table qb_queries
SELECT create_full_table_with_check('qb_queries', '
	CREATE TABLE qb_queries
	(
	  id serial NOT NULL PRIMARY KEY,
	  name text,
	  user_id text,
	  type text,
	  shared boolean DEFAULT false,
	  json text	  	 
	) 
	WITH (OIDS=FALSE);
');


-- création de la table qb_exports
SELECT create_full_table_with_check('qb_exports', '
	CREATE TABLE qb_exports
	(
	  id serial NOT NULL PRIMARY KEY,
	  name text,
	  user_id text,
	  file text,
	  state smallint,
	  process_pid smallint,
	  start_date timestamp,
	  end_date timestamp,
	  error_message text,
	  json text	  	  
	) 
	WITH (OIDS=FALSE);
');

-- Création de la table qb_favorite_ne
SELECT create_full_table_with_check('qb_favorite_ne', '
	CREATE TABLE qb_favorite_ne
	(
	  id serial NOT NULL PRIMARY KEY,
	  name text,
	  user_id text	  
	) 
	WITH (OIDS=FALSE);
');

-- Création de la table qb_favorite_object_ref
SELECT create_full_table_with_check('qb_favorite_object_ref', '
	CREATE TABLE qb_favorite_object_ref
	(
	  id serial NOT NULL PRIMARY KEY,
	  qb_favorite_id integer,
	  eor_id text	  
	) 
	WITH (OIDS=FALSE);
');

-- Création de la table queries_to_kill
SELECT create_full_table_with_check('queries_to_kill', '
	CREATE TABLE queries_to_kill
	(
	  id serial NOT NULL PRIMARY KEY,
	  search_string text
	) 
	WITH (OIDS=FALSE);
');



-- Function: get_na_lineage(text, text, text, integer);

CREATE OR REPLACE FUNCTION get_na_lineage(text, text, text, integer)
  RETURNS text[] AS
$BODY$
DECLARE

    ---------------------------------------------------
    -- @Created SPD1 on 08/12/2011 - Query builder V2 - 
    ---------------------------------------------------
    
    naMin ALIAS FOR $1;			/* The min network agregation level to start from */
    parentNa ALIAS FOR $2;		/* The max parent NA level where we should stop */
    familyId ALIAS FOR $3;		/* The family */
    nbLoop ALIAS FOR $4;		/* Set to 0 when you call this function (used for recursive loop) */
    agr RECORD;
    ret TEXT[];

BEGIN

-- Get path network agregation */
FOR agr in(SELECT family,
		level_source, 
		agregation, 
		COALESCE(axe, 1) AS axe,
		CASE WHEN level_source = naMin THEN 1 ELSE 0 END AS hasFound
	FROM sys_definition_network_agregation 
	WHERE agregation IN (
		SELECT distinct t2.agregation FROM (sys_definition_group_table t0 LEFT JOIN sys_definition_group_table_network t1 ON (t0.id_ligne = t1.id_group_table))
			LEFT JOIN sys_definition_network_agregation t2 ON (t0.family = t2.family)
		WHERE (t1.network_agregation LIKE  t2.agregation || '%' OR t1.network_agregation LIKE '%' || t2.agregation))		
	AND level_source != agregation
	AND family = familyId
	AND agregation = parentNa		
	ORDER BY hasFound DESC)
LOOP
	-- Too much loop (avoid circular reference infinite loop)
	IF nbLoop = 100 THEN
		RAISE EXCEPTION 'Too many loop';
	END IF;	

	-- If the parent na has been found, stop here
	IF agr.hasFound = 1 THEN
		RETURN ARRAY[(agr.level_source||'|s|'||agr.agregation)];
	ELSE
	-- If the parent na has not been found, search again
		RETURN get_na_lineage(naMin, agr.level_source, familyId, nbLoop+1) || (agr.level_source||'|s|'||agr.agregation);
	END IF;
END LOOP;

RETURN ARRAY[''];

    
END;
$BODY$
  LANGUAGE plpgsql IMMUTABLE;



-- Function: get_na_parent_id(text, text, text, text)

CREATE OR REPLACE FUNCTION get_na_parent_id(text, text, text, text)
  RETURNS text AS
$BODY$
DECLARE

    ---------------------------------------------------
    -- @Created SPD1 on 08/12/2011 - Query builder V2 - 
    -- Return parent network element id
    ---------------------------------------------------
    
    ne ALIAS FOR $1;		/* The network element 		*/
    naMin ALIAS FOR $2;		/* The network element NA level	*/
    parentNa ALIAS FOR $3;	/* The parent NA level 		*/
    familyId ALIAS FOR $4;    	/* The family 			*/
    lineage TEXT[];
    childId TEXT;
    naId TEXT;
    naLevel TEXT;

BEGIN

	-- Get lineage, array like: ('sai|s|rnc', 'rnc|s|network')
	SELECT INTO lineage get_na_lineage(naMin, parentNa, familyId, 0);

	childId:=ne;
	
	-- Find parent element id
	IF lineage IS NOT NULL THEN
		FOR naLevel in SELECT * FROM (SELECT lineage[i] FROM generate_series(array_lower(lineage,1), array_upper(lineage,1)) i) as getRecordFromArray LOOP		
			IF childId IS NOT NULL THEN				
				SELECT INTO childId eoar_id_parent FROM edw_object_arc_ref WHERE eoar_id=childId and eoar_arc_type=naLevel;				
			END IF;
		END LOOP;

		SELECT INTO naId eor_id FROM edw_object_ref WHERE eor_id = childId AND eor_obj_type = parentNa;
		RETURN naId;		
	END IF;
		
	RETURN '';
    
END;
$BODY$
  LANGUAGE plpgsql IMMUTABLE;


-- Function: get_na_parent_label(text, text, text, text)

CREATE OR REPLACE FUNCTION get_na_parent_label(text, text, text, text)
  RETURNS text AS
$BODY$
DECLARE

    ---------------------------------------------------
    -- @Created SPD1 on 08/12/2011 - Query builder V2 - 
    -- Return parent network element label
    ---------------------------------------------------
    
    ne ALIAS FOR $1;		/* The network element 		*/
    naMin ALIAS FOR $2;		/* The network element NA level	*/
    parentNa ALIAS FOR $3;	/* The parent NA level 		*/
    familyId ALIAS FOR $4;    	/* The family 			*/
    lineage TEXT[];
    childId TEXT;
    naLabel TEXT;
    naLevel TEXT;

BEGIN

	-- Get lineage, array like: ('sai|s|rnc', 'rnc|s|network')
	SELECT INTO lineage get_na_lineage(naMin, parentNa, familyId, 0);

	childId:=ne;
	
	-- Find parent element id
	IF lineage IS NOT NULL THEN
		FOR naLevel in SELECT * FROM (SELECT lineage[i] FROM generate_series(array_lower(lineage,1), array_upper(lineage,1)) i) as getRecordFromArray LOOP		
			IF childId IS NOT NULL THEN				
				SELECT INTO childId eoar_id_parent FROM edw_object_arc_ref WHERE eoar_id=childId and eoar_arc_type=naLevel;				
			END IF;
		END LOOP;

		SELECT INTO naLabel CASE WHEN eor_label IS NOT NULL THEN eor_label ELSE eor_id END FROM edw_object_ref WHERE eor_id = childId AND eor_obj_type = parentNa;
		RETURN naLabel;		
	END IF;
		
	RETURN '';
    
END;
$BODY$
  LANGUAGE plpgsql IMMUTABLE;


-- -----------------------
-- FIN QUERY BUILDER V2
-- -----------------------



---
--	SUPPRESSION DE LA LIBRAIRIE LOCALE DE FONCTIONS USUELLES
--      LAISSER A LA FIN DU FICHIER !
---
DROP FUNCTION IF EXISTS create_column_with_check(text, text, text, text);
DROP FUNCTION IF EXISTS create_table_with_check(text);
DROP FUNCTION IF EXISTS create_full_table_with_check(text, text);
DROP FUNCTION IF EXISTS insert_into_table_with_check(text, text, text, text, text);