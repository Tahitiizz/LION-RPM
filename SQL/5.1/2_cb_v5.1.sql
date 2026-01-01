---
-- Gestion de la documentation produit dans sys_global_parameters
---
DELETE FROM sys_global_parameters WHERE parameters = 'path_to_product_doc';
CREATE OR REPLACE FUNCTION add_new_product_doc_in_sgp() RETURNS VOID AS $$
	DECLARE
		order_value int;
	BEGIN
		SELECT order_parameter INTO order_value FROM sys_global_parameters WHERE parameters = 'path_to_user_doc' LIMIT 1;
		IF ( SELECT COUNT(*) FROM sys_global_parameters WHERE order_parameter = (order_value + 1) ) > 0 THEN
			UPDATE sys_global_parameters SET order_parameter=order_parameter+1 WHERE order_parameter > order_value;
		END IF;
		INSERT INTO sys_global_parameters VALUES ('path_to_product_doc', NULL, 1, 'customisateur', 'Path to product documentation',
		'Path to product documentation (path and file name), example : /doc/doc.pdf', 1,
		( order_value + 1 ), 0 );
	END;
$$ LANGUAGE PLPGSQL VOLATILE;
SELECT add_new_product_doc_in_sgp();

---
--	SUPPRESSION DE LA LIBRAIRIE LOCALE DE FONCTIONS USUELLES
---
DROP FUNCTION IF EXISTS add_new_product_doc_in_sgp();

-- 29/06/2010 OJT : Correction bz11698
UPDATE menu_deroulant_intranet SET libelle_menu = 'GRAPH Element Builder' WHERE libelle_menu = 'GTM Element Builder';

-- 30/06/2010 OJT : Correction bz15184
UPDATE sys_global_parameters SET label='ErlangB availability' WHERE parameters='activate_capacity_planing';
UPDATE sys_global_parameters SET comment='0 : ErlangB function not available in KPI builder, 1 : ErlangB function available in KPI builder' WHERE parameters='activate_capacity_planing';

-- 30/06/2010 OJT : Correction bz15442
UPDATE sys_global_parameters SET comment='Number of days of filed reports are kept in Trending&Aggregation (by default the value is set to 120)' WHERE parameters='report_files_history';
UPDATE sys_global_parameters SET comment='Number of days of hourly statistics are kept in Trending&Aggregation' WHERE parameters='history_hour';
UPDATE sys_global_parameters SET comment='Number of days of daily statistics are kept in Trending&Aggregation' WHERE parameters='history_day';
UPDATE sys_global_parameters SET comment='Number of weeks of weekly statistics are kept in Trending&Aggregation' WHERE parameters='history_week';
UPDATE sys_global_parameters SET comment='Number of months of monthly statistics are kept in Trending&Aggregation' WHERE parameters='history_month';

-- 01/07/2010 OJT : Correction bz16045
UPDATE sys_global_parameters SET comment='Delay in minute to refresh dashboards and GIS' WHERE parameters='autorefresh_delay';

-- 27/07/2010 BBX
-- Ajout d'un index sur sys_definition_alarm_network_elements
DROP INDEX IF EXISTS idx_sdane_alarm_id;
CREATE INDEX idx_sdane_alarm_id
ON sys_definition_alarm_network_elements
USING btree
(alarm_id);

-- 30/07/2010 NSE - bz 15423 : suppression de la doc admin de sys global parameter
DELETE FROM sys_global_parameters WHERE parameters = 'path_to_admin_doc';
DELETE FROM sys_global_parameters WHERE parameters = 'path_to_user_doc';

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