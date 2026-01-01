---------------------------------------------------------------------------------------------------------
-- Migration des graphes et dashboards pour le CB 4.1
-- 21/10/08 CCT1
---------------------------------------------------------------------------------------------------------

-- MIGRATION DES GRAPHES
---------------------------------------------------------------------------------------------------------
UPDATE graph_data 
	SET id_data=0
	WHERE data_value='RI_CAPTURE_DURATION';
UPDATE graph_information
	SET id_graph=0
	WHERE graph_name='ri';
-- Modifications sur la table GRAPH_DATA
-- On remplace id_data = id_data de la table sys_pauto_config par id_data = colonne id de la table sys_pauto_config, la colonne apsse de serial à integer.
-- création de la colonne temporaire pour stocker le nouvel id
ALTER TABLE graph_data ADD COLUMN id_data_temp integer;  
-- on met le contenu du champ id de la table sys_pauto_config dans la colonne id_data_temp.
UPDATE graph_data 
	SET id_data_temp=sys_pauto_config.id
	FROM sys_pauto_config
	WHERE sys_pauto_config.id_data = graph_data.id_data;
-- on supprime la colonne id_data					
ALTER TABLE graph_data DROP COLUMN id_data;
-- on renomme la colonne id_data_temp en id_data
ALTER TABLE graph_data RENAME COLUMN id_data_temp TO id_data;

-- Modifications de la table GRAPH_INFORMATION / SYS_PAUTO_PAGE_NAME
-- On copie le contenu du champ graph_title vers la table sys_pauto_page_name, champ page_name (car graph_title sera supprimé).
UPDATE sys_pauto_page_name
	SET page_name = graph_information.graph_title 
	FROM graph_information
	WHERE graph_information.id_page = sys_pauto_page_name.id_page;
-- on met-à-jour le champ gis_based_on qui fait référence à la colonne id_data de la table graph_data (dans le CB 4.1 l'id_data de graph_data est égal à la colonne id de la table sys_pauto_config)
UPDATE graph_information 
	SET gis_based_on= sys_pauto_config.id 
	FROM sys_pauto_config 
	WHERE sys_pauto_config.id_data = graph_information.gis_based_on;
-- Idem avec le champ orderby
UPDATE graph_information 
	SET orderby = sys_pauto_config.id 
	FROM sys_pauto_config 
	WHERE sys_pauto_config.id_data = graph_information.orderby;

-- >>>>>>>>>>
-- 22/01/2009  GHX déplacement des requetes suivantes du cb4.1.00.sql dans ce fichier
-- 06/01/2009 - Modif. benoit : ajout d'une colonne 'pie_split_type' à la table 'graph_information'. Cette colonne permet de préciser le type de split d'un pie
ALTER TABLE graph_information ADD COLUMN pie_split_type text;
-- 15/01/2009 - maj SLC : migration données suite à l'ajout de la colonne pie_split_type de Benoit
UPDATE graph_information SET pie_split_type='first_axis' WHERE object_type='pie@one_ot';
UPDATE graph_information SET pie_split_type='third_axis' WHERE object_type='pie@axe3';
UPDATE graph_information SET object_type='pie3D' WHERE object_type LIKE 'pie%';
-- 15/01/2009 - maj SLC : supprime colonne asc_desc
ALTER TABLE graph_information DROP COLUMN asc_desc;
-- 15/01/2009 - maj SLC : renomme colonne order_by -> pie_split_by
ALTER TABLE graph_information ADD COLUMN pie_split_by integer default 0;
UPDATE graph_information set pie_split_by = orderby;
ALTER TABLE graph_information DROP COLUMN orderby;
-- 15/01/2009 - maj SLC : renomme les colonnes overnetwork_default_orderby et overnetwork_default_asc_desc
ALTER TABLE graph_information RENAME COLUMN overnetwork_default_orderby TO default_orderby;
ALTER TABLE graph_information RENAME COLUMN overnetwork_default_asc_desc TO default_asc_desc;
-- <<<<<<<<<<

-- FIN MIGRATION DES GRAPHES
---------------------------------------------------------------------------------------------------------



-- MIGRATION DES DASHBOARDS
---------------------------------------------------------------------------------------------------------
-- Mise-à-jour des graphes contenus dans des dashboard, dans le CB 4.1 dans la table sys_pauto_config pour les éléments class_object='gtm' on ne met plus dans la colonne id_elem
-- le contenu de la colonne id_graph de graph_information (colonne supprimée) mais le contenu d ela colonne id_page de la table sys_pauto_page_name.
UPDATE sys_pauto_config
	SET id_elem = graph_information.id_page 
	FROM graph_information
	WHERE sys_pauto_config.class_object='graph'
		AND graph_information.id_graph = sys_pauto_config.id_elem;
	
-- Création de la table sys_definition_dashboard
CREATE TABLE sys_definition_dashboard
(
  sdd_id_page integer,
  sdd_sort_by_id integer,
  sdd_sort_by_order text,
  sdd_mode text,
  sdd_is_online integer,
  sdd_id_menu integer,
  sdd_selecteur_default_period integer,
  sdd_selecteur_default_top_overnetwork integer,
  sdd_selecteur_default_top_overtime integer,
  sdd_selecteur_default_na text,
  sdd_selecteur_default_na_axe3 text
) 
WITH OIDS;
-- On remplit les champs de la nouvelle table sys_definition_dashboard
-- mise-à-jour de sdd_id_page : on prend tous les id_page des dashboards de la table sys_pauto_page_name
INSERT INTO sys_definition_dashboard (sdd_id_page)
	SELECT id_page FROM sys_pauto_page_name WHERE page_type='page';
-- on copie le contenu des anciennes colonnes de sys_pauto_page_name vers sys_definition_dashboard
UPDATE sys_definition_dashboard
	SET sdd_mode = sys_pauto_page_name.page_mode,
	sdd_is_online = sys_pauto_page_name.online,
	sdd_id_menu = sys_pauto_page_name.id_menu
	FROM sys_pauto_page_name
	WHERE sys_definition_dashboard.sdd_id_page = sys_pauto_page_name.id_page;
-- on copie le contenu des anciennes colonnes de sys_definition_theme vers sys_definition_dashboard
UPDATE sys_definition_dashboard
	SET sdd_sort_by_id = sys_definition_theme.id_sort_by,
	sdd_sort_by_order = sys_definition_theme.asc_desc
	FROM sys_definition_theme
	WHERE sys_definition_theme.id_theme = sys_definition_dashboard.sdd_id_page;
-- mise-à-jour de la période 
UPDATE sys_definition_dashboard
	SET sdd_selecteur_default_period = sys_selecteur_properties.default_value::integer
	FROM sys_selecteur_properties, sys_pauto_page_name, sys_object_selecteur
	WHERE sys_pauto_page_name.family = sys_object_selecteur.family
		AND sys_object_selecteur.object_id = sys_selecteur_properties.id_selecteur
		AND sys_pauto_page_name.id_page = sys_definition_dashboard.sdd_id_page
		AND sys_selecteur_properties.properties = 'period';
-- mise-à-jour du top ONE 
UPDATE sys_definition_dashboard
	SET sdd_selecteur_default_top_overnetwork = sys_selecteur_properties.default_value::integer
	FROM sys_selecteur_properties, sys_pauto_page_name, sys_object_selecteur
	WHERE sys_pauto_page_name.family = sys_object_selecteur.family
		AND sys_object_selecteur.object_id = sys_selecteur_properties.id_selecteur
		AND sys_pauto_page_name.id_page = sys_definition_dashboard.sdd_id_page
		AND sys_selecteur_properties.properties = 'top_one';
-- mise-à-jour du top OT
UPDATE sys_definition_dashboard
	SET sdd_selecteur_default_top_overtime = 
	CASE WHEN split_part(default_value,'@',3) = '' THEN '3' 
	ELSE  split_part(default_value,'@',3) END::integer
	FROM sys_selecteur_properties, sys_pauto_page_name, sys_object_selecteur
	WHERE sys_pauto_page_name.family = sys_object_selecteur.family
		AND sys_object_selecteur.object_id = sys_selecteur_properties.id_selecteur
		AND sys_pauto_page_name.id_page = sys_definition_dashboard.sdd_id_page
		AND sys_selecteur_properties.properties = 'network_agregation';
-- mise-à-jour de la NA par défaut
UPDATE sys_definition_dashboard
	SET sdd_selecteur_default_na = split_part(default_value,'@',1)
	FROM sys_selecteur_properties, sys_pauto_page_name, sys_object_selecteur
	WHERE sys_pauto_page_name.family = sys_object_selecteur.family
		AND sys_object_selecteur.object_id = sys_selecteur_properties.id_selecteur
		AND sys_pauto_page_name.id_page = sys_definition_dashboard.sdd_id_page
		AND sys_selecteur_properties.properties = 'network_agregation';
-- mise-à-jour de la NA 3ème axe par défaut
UPDATE sys_definition_dashboard
	SET sdd_selecteur_default_na_axe3 = split_part(default_value,'@',1)
	FROM sys_selecteur_properties, sys_pauto_page_name, sys_object_selecteur
	WHERE sys_pauto_page_name.family = sys_object_selecteur.family
		AND sys_object_selecteur.object_id = sys_selecteur_properties.id_selecteur
		AND sys_pauto_page_name.id_page = sys_definition_dashboard.sdd_id_page
		AND sys_selecteur_properties.properties = 'na_box';
		
-- Mise-à-jour du champ sdd_sort_by_id pour mettre le contenu du champ id de la table sys_pauto_config
UPDATE sys_definition_dashboard
	SET sdd_sort_by_id = SPC.id
	FROM sys_definition_theme SDT, sys_pauto_config SPC
	WHERE sys_definition_dashboard.sdd_id_page=SDT.id_theme
		AND SPC.id_page IN (SELECT id_page FROM sys_pauto_page_name WHERE page_type='gtm')
		AND SPC.id_elem=SDT.id_sort_by;


-- FIN MIGRATION DES DASHBOARDS
---------------------------------------------------------------------------------------------------------

-- Mise-à-jour de la table SYS_PAUTO_CONFIG
-- Ajout de la colonne internal_id
ALTER TABLE sys_pauto_config ADD COLUMN internal_id text ;
--  Mise-à-jour de la colonne internal_id pour les KPI.
UPDATE sys_pauto_config
	SET internal_id = sys_definition_kpi.internal_id
	FROM sys_definition_kpi
	WHERE sys_definition_kpi.id_ligne = sys_pauto_config.id_elem
	AND class_object='kpi';
-- Mise-à-jour de la colonne internal_id pour les RAW.
UPDATE sys_pauto_config
	SET internal_id = sys_field_reference.internal_id
	FROM sys_field_reference
	WHERE sys_field_reference.id_ligne = sys_pauto_config.id_elem
	AND class_object='counter';
-- On supprime tous les types selecteur qui ne sont plus utilisés.
DELETE FROM sys_pauto_config WHERE class_object='selecteur';
-- On change le type de la colonne id de sys_pauto_config en serial
ALTER TABLE sys_pauto_config ADD COLUMN id_temp serial;  
UPDATE sys_pauto_config SET id_temp=id;
ALTER TABLE sys_pauto_config DROP COLUMN id;
ALTER TABLE sys_pauto_config RENAME COLUMN id_temp TO id;
ALTER SEQUENCE sys_pauto_config_id_temp_seq RESTART WITH 2000;


-- Suppression des tables inutiles
DROP TABLE sys_definition_theme;
DROP TABLE sys_object_selecteur;
DROP TABLE sys_selecteur_properties;
DROP TABLE sys_object_graph; -- vieille table qui n'a jamais servie


-- Suppression des colonnes inutiles
ALTER TABLE graph_data DROP COLUMN data_name;
ALTER TABLE graph_data DROP COLUMN geographic_agregation;
ALTER TABLE graph_data DROP COLUMN time_agregation;
ALTER TABLE graph_data DROP COLUMN provider_agregation;
ALTER TABLE graph_data DROP COLUMN data_value;
ALTER TABLE graph_data DROP COLUMN data_table_name;
ALTER TABLE graph_data DROP COLUMN id_data_value;
ALTER TABLE graph_data DROP COLUMN data_type;
ALTER TABLE graph_data DROP COLUMN data_abscisse_field_name;
ALTER TABLE graph_data DROP COLUMN data_comparaison_field_name;
ALTER TABLE graph_data DROP COLUMN data_complement_sql_and;
ALTER TABLE graph_data DROP COLUMN data_complement_sql_join;
ALTER TABLE graph_data DROP COLUMN data_fixed_value;
ALTER TABLE graph_data DROP COLUMN edw_group_table;
ALTER TABLE graph_data DROP COLUMN busy_hour;
ALTER TABLE graph_data DROP COLUMN is_configure;
ALTER TABLE graph_data DROP COLUMN gt_categorie;
ALTER TABLE graph_data DROP COLUMN internal_id;

ALTER TABLE graph_information DROP COLUMN id_graph;
ALTER TABLE graph_information DROP COLUMN graph_name;
ALTER TABLE graph_information DROP COLUMN positiongraph;
ALTER TABLE graph_information DROP COLUMN graph_title;
ALTER TABLE graph_information DROP COLUMN graph_order;
ALTER TABLE graph_information DROP COLUMN graph_comment;
ALTER TABLE graph_information DROP COLUMN axe3;
ALTER TABLE graph_information DROP COLUMN axe3_sortby;
ALTER TABLE graph_information DROP COLUMN axe3_sortby_ascdesc;
ALTER TABLE graph_information DROP COLUMN axe3_data;
ALTER TABLE graph_information DROP COLUMN gt_categories;
ALTER TABLE graph_information DROP COLUMN gis_data_type;
ALTER TABLE graph_information DROP COLUMN graph_data_list;

ALTER TABLE sys_pauto_page_name DROP COLUMN page_mode;
ALTER TABLE sys_pauto_page_name DROP COLUMN time_aggregation;
ALTER TABLE sys_pauto_page_name DROP COLUMN network_aggregation;
ALTER TABLE sys_pauto_page_name DROP COLUMN axe3;
ALTER TABLE sys_pauto_page_name DROP COLUMN "online";
ALTER TABLE sys_pauto_page_name DROP COLUMN id_menu;
ALTER TABLE sys_pauto_page_name DROP COLUMN home_network;
ALTER TABLE sys_pauto_page_name DROP COLUMN id_graph;
-- CCT1 29/06/2010 on conserve cette colonne pour la gestion des doublons dans les compteurs, la colonne est supprimée dans le fichier ContextMigration.class.php maintenant
--ALTER TABLE sys_pauto_page_name DROP COLUMN family;
ALTER TABLE sys_pauto_page_name DROP COLUMN navigation_on_off;

ALTER TABLE sys_pauto_config DROP COLUMN frame_position;
ALTER TABLE sys_pauto_config DROP COLUMN colonne;
ALTER TABLE sys_pauto_config DROP COLUMN "comment";
ALTER TABLE sys_pauto_config DROP COLUMN temp;
ALTER TABLE sys_pauto_config DROP COLUMN id_data;


-- SLC 12/02/2009 - remplace la valeur cumulated -> cumulatedbar dans graph_data
update graph_data set display_type='cumulatedbar' where display_type='cumulated';

-- 10:55 21/09/2009 GHX
-- Correction BZ 11429 [REC][T&A Cb 5.0][Dashboard] : problème d'affichage pour un graph de type 'line' avec line_design='none'
-- On remplace tous les 'none' par 'square' pour les graphes de type lignes
UPDATE graph_data SET line_design = 'square' WHERE display_type = 'line' AND line_design = 'none';
UPDATE graph_data SET line_design = 'square' WHERE display_type = 'cumulatedline' AND line_design = 'none';

-- CCT1 correction bug 16425 dash client en doublon si on fait un save après migration
UPDATE sys_definition_dashboard
	SET sdd_id_menu = menu_deroulant_intranet.id_menu

	FROM menu_deroulant_intranet , sys_pauto_page_name

	WHERE menu_deroulant_intranet.id_page= sdd_id_page
	AND menu_deroulant_intranet.niveau=2
	AND menu_deroulant_intranet.id_page = sys_pauto_page_name.id_page
	AND sys_pauto_page_name.page_type='page' AND sys_pauto_page_name.droit='client';

