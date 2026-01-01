--
-- N 'OUBLIEZ PAS DE METTRE DES DELETE AVANT CHAQUE INSERT !!!!!!!!!!!!!!!!!!!!!!!!!�
--
-- SCT 14:24 15/05/2009 Mise � jour des s�quences pour �viter les probl�mes
SELECT setval('public.sys_definition_group_table_network_id_ligne_seq', (SELECT max(id_ligne)+1 from sys_definition_group_table_network), true);
SELECT setval('public.sys_definition_group_table_time_id_ligne_seq', (SELECT max(id_ligne)+1 from sys_definition_group_table_time), true);
SELECT setval('public.sys_definition_step_step_id_seq', (SELECT max(step_id)+1 from sys_definition_step), true);

-- D�sactivation de la step update_object_ref
UPDATE sys_definition_step SET on_off = 0 WHERE step_id = 118;

-- Ajout CCT1 04/11/08 table de param�trage des produits.

-- 05/11/2008 - Modif. benoit : ajout du champ 'sdp_db_port' indiquant le port de la base de donn�es du produit

-- 01/12/2008 - Modif. benoit : ajout du champ 'sdp_master_topo' pour indiquer quel est le produit master pour la topologie

CREATE TABLE sys_definition_product
(
  sdp_id serial NOT NULL,
  sdp_label text,
  sdp_ip_address text,
  sdp_directory text,
  sdp_db_name text,
  sdp_db_port smallint DEFAULT 5432,
  sdp_db_login text,
  sdp_db_password text,
  sdp_ssh_user text,
  sdp_ssh_password text,
  sdp_ssh_port smallint DEFAULT 22,
  sdp_on_off smallint DEFAULT 0,    
  sdp_master smallint DEFAULT 0,
  sdp_master_topo smallint DEFAULT 0
) 
WITH OIDS;

-- 14/11/2008 BBX : ajout d'un fonction pl_sql permettant de calculer le chemin d'agr�gation d'un NA
DROP FUNCTION IF EXISTS get_path(text,text,text);
CREATE OR REPLACE FUNCTION get_path(text,text,text) RETURNS SETOF TEXT AS $$
DECLARE
	myrow RECORD;
	agreg TEXT;
	net_agreg ALIAS FOR $1;
	min_net ALIAS FOR $2;
	net_family ALIAS FOR $3;
BEGIN
	agreg := net_agreg;
	RETURN NEXT agreg;
	WHILE agreg != min_net
	LOOP
		SELECT INTO myrow level_source FROM sys_definition_network_agregation WHERE family = net_family AND agregation = agreg LIMIT 1;
		IF agreg = myrow.level_source THEN
			RETURN;
		END IF;
		agreg := myrow.level_source;
		IF agreg IS NULL THEN
			RETURN;
		END IF;
		RETURN NEXT agreg;
	END LOOP;
	RETURN;
END;
$$ LANGUAGE PLPGSQL;

-- 26/11/2008 BBX : on renomme le param�tre de d�lai de l'autorefresh
UPDATE sys_global_parameters SET parameters = 'autorefresh_delay' WHERE parameters = 'gis_autorefresh_delay';
UPDATE sys_global_parameters SET label = 'Autorefresh delay' WHERE parameters = 'autorefresh_delay';

-- 26/11/2008 BBX : Cr�ation de la table qui va permettre d'enregistrer des s�lecteurs
CREATE TABLE sys_definition_selecteur 
(
	sds_id_selecteur serial NOT NULL,
	sds_id_page int NOT NULL,
	sds_mode character varying(20),
	sds_ta text,
	sds_period int,
	sds_na text,
	sds_na_list text,
	sds_na_axe3 text,
	sds_na_axe3_element text,
	sds_top int,
	sds_sort_by text,
	sds_order character varying(20),
	sds_filter_id text,
	sds_filter_operande character varying(20),
	sds_filter_value int,
	sds_report_id int
);

-- 27/11/2008 BBX : ajout de la colonne "homepage" dans la table users
ALTER TABLE users
ADD COLUMN homepage int;

---
-- 08/12/2008 BBX : cr�ation des 2 tables de jointure pour les DE sur les alarmes syst�mes
---
DROP TABLE IF EXISTS sys_definition_flat_file_per_connection;
CREATE TABLE sys_definition_flat_file_per_connection (
	sdffpc_id_connection	integer,
	sdffpc_id_flat_file		integer
);
--
DROP TABLE IF EXISTS sys_definition_users_per_connection;
CREATE TABLE sys_definition_users_per_connection (
	sdupc_id_connection	integer,
	sdupc_id_user		integer	
);
-- Par d�faut, tous les types de fichiers sont coch�s pour toutes les connections
-- Par d�faut les admin sont destinataires
CREATE OR REPLACE FUNCTION  fill_flat_file () RETURNS void AS $$
DECLARE
 myco RECORD;
 myfile RECORD;
 myuser RECORD;
BEGIN
 -- boucle sur les connexions
 FOR myco IN SELECT DISTINCT id_connection FROM sys_definition_connection
 LOOP
	-- boucle sur les fichiers
	FOR myfile IN SELECT DISTINCT id_flat_file FROM sys_definition_flat_file_lib	
	LOOP
		INSERT INTO sys_definition_flat_file_per_connection (sdffpc_id_connection,sdffpc_id_flat_file)
		VALUES (myco.id_connection,myfile.id_flat_file);
	END LOOP;
	-- boucle sur les users
	FOR myuser IN SELECT DISTINCT id_user FROM users WHERE user_profil IN (SELECT id_profile AS user_profil FROM profile WHERE profile_type = 'admin')
	LOOP
		INSERT INTO sys_definition_users_per_connection (sdupc_id_connection,sdupc_id_user)
		VALUES (myco.id_connection,myuser.id_user);
	END LOOP;
 END LOOP;
END;
$$ LANGUAGE plpgsql;

SELECT fill_flat_file();
DROP FUNCTION fill_flat_file();

-- 11:14 20/10/2008 GHX (DE)
-- Export Data : ajout d'une colone pour ordonner l'ordre des kpi et des raw dans le fichier
ALTER TABLE sys_export_raw_kpi_data ADD COLUMN ordre INTEGER;
-- Cr�ation d'une fonction psql pour initialiser les export data d�j� cr�es
CREATE OR REPLACE FUNCTION  addValueOrder () RETURNS void AS $$
DECLARE
 order INTEGER;
 typedata TEXT;
 myexport RECORD;
 myexportdata RECORD;
BEGIN
 typedata := '';
 FOR myexport IN SELECT * FROM sys_export_raw_kpi_config
 LOOP
  order := 1;
  FOR myexportdata IN SELECT * FROM sys_export_raw_kpi_data WHERE export_id::INTEGER = myexport.export_id::INTEGER
  LOOP
   IF typedata::TEXT <> myexportdata.raw_kpi_type::TEXT THEN
    order := 1;
    typedata := myexportdata.raw_kpi_type;
   END IF;
   UPDATE sys_export_raw_kpi_data SET ordre = order WHERE oid = myexportdata.oid;
   order := order + 1;
  END LOOP;
 END LOOP;
END;
$$ LANGUAGE plpgsql;

SELECT addValueOrder();
DROP FUNCTION addValueOrder();

update menu_deroulant_intranet	set	lien_menu='/builder/graph.php',		libelle_menu='Graph builder'		where id_menu='5246';
update menu_deroulant_intranet	set	lien_menu='/builder/graph.php',		libelle_menu='Graph builder'		where id_menu='29';
update menu_deroulant_intranet	set	lien_menu='/builder/dashboard.php',	libelle_menu='Dashboard builder'	where id_menu='5245';
update menu_deroulant_intranet	set	lien_menu='/builder/dashboard.php',	libelle_menu='Dashboard builder'	where id_menu='27';
update menu_deroulant_intranet	set	lien_menu='/builder/report.php',		libelle_menu='Report builder'		where id_menu='28';

-- 09:35 11/12/2008 GHX
-- Param�tre de debug du mapping
DELETE FROM sys_debug WHERE parameters = 'mapping';
INSERT INTO sys_debug VALUES ('mapping', '0', 'mode debug du mapping de la topologie : 0 = d�sactiv� / 1 = activ�');


-- 14:35 18/12/2008 GHX
-- Cr�ation des index sur les tables des fichiers archiv�s. Elles permettent de gagn�s du temps sur une requ�te principalement qui est ex�cute � la fin de la collecte des fichiers
CREATE INDEX index_sys_flat_file_uploaded_list_hour ON sys_flat_file_uploaded_list  USING btree (hour);
CREATE INDEX index_sys_flat_file_uploaded_list_flat_file_uniqid ON sys_flat_file_uploaded_list  USING btree (flat_file_uniqid);
CREATE INDEX index_sys_flat_file_uploaded_list_flat_file_template ON sys_flat_file_uploaded_list  USING btree (flat_file_template);
CREATE INDEX index_sys_flat_file_uploaded_list_archive_hour ON sys_flat_file_uploaded_list_archive  USING btree (hour);
CREATE INDEX index_sys_flat_file_uploaded_list_archive_flat_file_template ON sys_flat_file_uploaded_list_archive  USING btree (flat_file_template);
CREATE INDEX index_sys_flat_file_uploaded_list_archive_hour_flat_file_template ON sys_flat_file_uploaded_list_archive  USING btree (hour,flat_file_template);


-- 17:33 30/12/2008 SCT ajout des informations
-- Ajout des colonnes sp�cifiques au parser DTS Huawei (cela peut toujours servir)
ALTER TABLE sys_definition_categorie ADD COLUMN automatic_mapping integer DEFAULT 0;
ALTER TABLE sys_field_reference_all DROP COLUMN edw_agregation_function;
ALTER TABLE sys_field_reference_all DROP COLUMN edw_agregation_function_axe1;
ALTER TABLE sys_field_reference_all ADD COLUMN edw_agregation_function_axe1 text;
ALTER TABLE sys_field_reference_all DROP COLUMN edw_agregation_function_axe2;
ALTER TABLE sys_field_reference_all ADD COLUMN edw_agregation_function_axe2 text;
ALTER TABLE sys_field_reference_all DROP COLUMN edw_agregation_function_axe3;
ALTER TABLE sys_field_reference_all ADD COLUMN edw_agregation_function_axe3 text;
ALTER TABLE sys_field_reference_all DROP COLUMN default_value;
ALTER TABLE sys_field_reference_all ADD COLUMN default_value TEXT;
ALTER TABLE sys_field_reference_all ALTER COLUMN default_value SET DEFAULT 0;
ALTER TABLE sys_field_reference_all DROP COLUMN prefix_counter;
ALTER TABLE sys_field_reference_all ADD COLUMN prefix_counter TEXT;

-- Ajout d'une colonne dans sys_definition_flat_file_lib pour la gestion de l'automatic mapping
ALTER TABLE sys_definition_flat_file_lib DROP COLUMN prefix_counter;
ALTER TABLE sys_definition_flat_file_lib ADD COLUMN prefix_counter TEXT;

-- Ajout d'une colonne pour g�rer le s�parateur sp�cifique d'un couple de NA du 1er axe
ALTER TABLE sys_definition_categorie DROP COLUMN separator;
ALTER TABLE sys_definition_categorie ADD COLUMN separator TEXT;

-- 22/01/2009 GHX requetes d�plac� dans le fichier migrationGTMDashboardCB41.sql

-- 14/01/2009 - maj MPR : Ajout de la cat�gorie global parameters ( param�tre globaux du produit master => IHM) 
DELETE FROM sys_global_parameters_categories WHERE id_category = 4;
INSERT INTO sys_global_parameters_categories (id_category, label_category) VALUES(4, 'Global Parameters');

-- 14/01/2009 - maj MPR : D�finition des param�tres globaux ( param�tres utilis�s par le master pour l'IHM)
UPDATE sys_global_parameters SET category = 4 WHERE parameters IN (
'dashboard_alarm',
'gis_alarm',
'mode_homepage',
'alarm_critical_color',
'alarm_major_color',
'alarm_management_autorefresh',
'alarm_minor_color',
'automatic_email_activation',
'autorefresh_delay',
'id_homepage',
'investigation_dashboard_max_selection',
'mail_reply',
'max_nb_charts_onmouseover',
'max_nb_row_upload_topology',
'na_label_character_max	',
'pdf_footer_text',
'pdf_logo_dev',
'pdf_logo_operateur',
'pdf_save_dir',
'power_by',
'publisher',
'report_files_dir',
'report_files_history',
'session_time',
'tabgraph_color',
'timer_killq_br',
'user_statistics',
'week_starts_on_monday',
'filter_period_max',
'week_starts_on_monday'
);

-- 14/01/2009 - maj MPR : Suppression des param�tres obsel�tes
DELETE FROM sys_global_parameters WHERE parameters IN (
'na_min',
'id_menu_homepage',
'moteur_prod',
'transparency_default',
'trouble_ticket_alarme',
'trouble_ticket_location',
'true_color',
'duree_timer_liste_na',
'nb_page_auto',
'nb_process_relaunch',
'filter_date_ma',
'filter_na_max',
'filter_top_axe3_max',
'filter_top_one_max'
);

-- 19/01/2009 - MPR  : Ajout de 3 c olonnes pour la DE sur l'export data
ALTER TABLE sys_export_raw_kpi_config ADD COLUMN generate_hour_on_day smallint  default 0;
ALTER TABLE sys_export_raw_kpi_config ADD COLUMN select_parents smallint default 0;
ALTER TABLE sys_export_raw_kpi_config ADD COLUMN use_code smallint default 0;
-- 30/12/2008 GHX
-- Ajout d'une colonne pour avoir la possibilit� d'utiliser les identifiants des NA au lieu de leurs labels
ALTER TABLE sys_export_raw_kpi_config ADD COLUMN use_code_na smallint default 0;

-- 23/01/2009 MPR - Ajout d'une colonne na_axe3 pour s�parer le niveau d'agr�gation 3�me axe du niveau d'agr�gation r�seau
ALTER TABLE sys_export_raw_kpi_config ADD COLUMN na_axe3 TEXT default null;

-- 30/01/2009 - Modif. benoit : maj des liens des menus existants

UPDATE menu_deroulant_intranet 
SET lien_menu = replace
		(
			replace
			(
				replace
				(
					lien_menu, 'reporting/intranet/php/affichage/view_index.php', 'dashboard_display/index.php'
				), 'id_menu_encours='||id_menu||'&creator=pauto&page=', 'id_dash='
			), '&selecteur_scenario=normal', ''
		)
WHERE lien_menu LIKE '%view_index%';

-- 02/02/2009 - Modif. benoit : ajout de la colonne '3rd_axis_default_level' � la table 'sys_definition_network_agregation'
-- afin de g�rer le cas de la s�lection de plusieurs axes 3 diff�rents dans un m�me GTM

ALTER TABLE sys_definition_network_agregation ADD COLUMN third_axis_default_level SMALLINT DEFAULT 0;

DELETE FROM sys_debug WHERE parameters = 'database_connection';
INSERT INTO sys_debug (parameters, value, commentaire) VALUES ('database_connection', 0, 'Affiche ou non les requetes SQL qui plante');

-- 12/02/2009 - Modif. benoit : maj des valeurs de la colonne 'nom_icone' de la table 'menu_contextuel'.
-- On ne reference plus dans ce champ le chemin complet de l'image mais simplement le nom du style css � appliquer

UPDATE menu_contextuel SET nom_icone = 'blank' WHERE nom_icone IS NULL;
UPDATE menu_contextuel SET nom_icone = 'pdf' WHERE nom_icone = 'menu_contextuel/export_pdf.png';
UPDATE menu_contextuel SET nom_icone = 'xls' WHERE nom_icone = 'menu_contextuel/export_excel.png';
UPDATE menu_contextuel SET nom_icone = 'doc' WHERE nom_icone = 'menu_contextuel/export_word.png';
UPDATE menu_contextuel SET nom_icone = 'send' WHERE nom_icone = 'menu_contextuel/send_to.png';

-- 13/02/2009 - Modif. benoit : maj des actions des menus de 'menu_contextuel'

UPDATE menu_contextuel
SET url_action = 'javascript:open_window(''export/export_word_pdf.php?type=word'',''Word_file'',''yes'',''yes'',300,30)'
WHERE nom_action = 'Word';

UPDATE menu_contextuel
SET url_action = 'javascript:open_window(''export/export_word_pdf.php?type=pdf'',''Word_file'',''yes'',''yes'',300,30)'
WHERE nom_action = 'PDF';

-- 17/03/2009 - modif SPS : chgt lien export excel
UPDATE menu_contextuel
SET url_action = 'javascript:open_window(''export/export_excel_dashboard.php'',''Excel_file'',''yes'',''yes'',300,30)'
WHERE nom_action = 'Excel';

UPDATE menu_contextuel
SET url_action = 'javascript:navigate(''index.php?id_dash=$id_dashboard&id_menu_encours=$id_menu_encours&mode=overnetwork'')'
WHERE nom_action = 'Over Network Elements';

UPDATE menu_contextuel
SET url_action = 'javascript:navigate(''index.php?id_dash=$id_dashboard&id_menu_encours=$id_menu_encours&mode=overtime'')'
WHERE nom_action = 'Over Time';

-- 18/03/2009 - modif SPS : chgt lien envoi rapport
UPDATE menu_contextuel
SET url_action = 'javascript:open_window(''php/send_to_pdf.php'',''Send_to'',''yes'',''yes'',800,600)'
WHERE nom_action = 'Send to';

UPDATE menu_contextuel
SET url_action = 'javascript:open_window(''view_pdf.php?pdf=$pdf_menu_contextuel'',''nouvellepage'',''yes'',''yes'',500,600)'
WHERE nom_action = 'Alarm PDF';

UPDATE menu_contextuel
SET url_action = 'javascript:open_window(''view_pdf.php?pdf=$pdf_menu_contextuel'',''nouvellepage'',''yes'',''yes'',500,600)'
WHERE nom_action = 'Top/Worst List PDF';

UPDATE menu_contextuel
SET url_action = 'javascript:open_window(''view_xls.php?excel=$excel_menu_contextuel'',''Excel_file'',''yes'',''yes'',500,600)'
WHERE nom_action = 'Alarm Excel';

UPDATE menu_contextuel
SET url_action = 'javascript:open_window(''view_xls.php?excel=$excel_menu_contextuel'',''Excel_file'',''yes'',''yes'',500,600)'
WHERE nom_action = 'Top/Worst List Excel';

UPDATE menu_contextuel
SET url_action = 'javascript:navigate(''alarm_index.php?mode_alarme=management&sous_mode=elem_reseau&id_menu_encours=$page_encours'')'
WHERE nom_action = 'By Network Element';

UPDATE menu_contextuel
SET url_action = 'javascript:navigate(''alarm_index.php?mode_alarme=management&sous_mode=condense&id_menu_encours=$page_encours'')'
WHERE nom_action = 'By Alarm';

UPDATE menu_contextuel
SET url_action = 'javascript:open_window(''my_aggregation_creation_index.php?family=$family&product=$product'',''new_network_agregation'',''true'',''true'',800,510)'
WHERE nom_action = 'New Aggregation';

UPDATE menu_contextuel
SET url_action = 'javascript:open_window(''formula_builder_index.php?family=$family&product=$product'',''new_formula'',''true'',''true'',990,520)'
WHERE nom_action = 'New Formula';

-- 02/03/2009 - Modif. benoit : ajout du parametre 'dashboard_display_max_nb_queries_overtime' dans 'sys_global_parameters' permettant de d�finir le nombre maximal
-- de requetes execut�es afin de d�terminer les NEs affich�es dans les GTMs du dashboard

DELETE FROM sys_global_parameters WHERE parameters = 'dashboard_display_max_nb_queries_overtime';
INSERT INTO sys_global_parameters(parameters, "value", configure, "comment") 
VALUES ('dashboard_display_max_nb_queries_overtime', 40, 0, 'Maximum number of queries executed to find ne in Overtime');

-- 04/03/2009 - Modif. benoit : ajout du parametre 'dashboard_display' dans 'sys_debug'

DELETE FROM sys_debug WHERE parameters = 'dashboard_display';
INSERT INTO sys_debug(parameters, "value", commentaire) VALUES ('dashboard_display', 0, 'mode debug de l''affichage des dashboards');

-- 12/03/2009 - Modif. SLC - filter_period_max=200
UPDATE sys_global_parameters SET value='200' WHERE parameters='filter_period_max';

----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
-- 11:43 16/03/2009 GHX
-- SQL pour le nouveau contexte [REFONTE CONTEXTE]

--
-- Cr�ation du la table qui contiendra la liste des tables qui pourront se trouver dans un contexte
--
-- sdct_id      => correspondra � l'identifiant unique � supprimer
-- sdct_table => nom de la table dans laquel se trouve l'id
--
DROP TABLE IF EXISTS sys_definition_context_table;
CREATE TABLE sys_definition_context_table
(
	sdct_id integer,
	sdct_table text
)
WITH (OIDS=TRUE);
ALTER TABLE sys_definition_context_table OWNER TO postgres;

INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (1, 'sys_definition_kpi');
INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (2, 'sys_field_reference');
INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (3, 'sys_pauto_page_name');
INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (4, 'sys_pauto_config');
INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (5, 'graph_data');
INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (6, 'graph_information');
INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (7, 'menu_deroulant_intranet');
INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (8, 'sys_definition_dashboard');
INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (10, 'profile');
INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (11, 'users');
INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (12, 'sys_user_group');
INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (13, 'profile_menu_position');
INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (14, 'sys_definition_alarm_static');
INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (15, 'sys_definition_alarm_dynamic');
INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (16, 'sys_definition_alarm_top_worst');
INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (17, 'sys_aa_column');
INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (18, 'sys_aa_column_code');
INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (19, 'sys_aa_contextuel_filter');
INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (20, 'sys_aa_interface');
INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (21, 'sys_aa_list_filter');
INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (22, 'sys_aa_filter_kpi');
INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (23, 'sys_aa_operator');
INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (24, 'sys_aa_vue');
INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (25, 'sys_data_range_style');
INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (26, 'sys_definition_network_agregation');
INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (27, 'sys_field_reference_all');
INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (28, 'sys_definition_time_agregation');
INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (29, 'sys_gis_config');
INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (30, 'sys_gis_config_global');
INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (31, 'sys_gis_config_palier');
INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (32, 'sys_gis_config_vecteur');
INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (33, 'geometry_columns');
INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (34, 'sys_global_parameters');
INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (35, 'sys_export_raw_kpi_config');
INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (36, 'sys_export_raw_kpi_data');
INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (37, 'sys_definition_selecteur');

--
-- Cr�ation du la table qui contiendra les cl�s primaires et �trang�res des tables contenu dans la table sys_definition_context_tables
--
-- sdctk_id                => identifiant
-- sdctk_column       => nom de la colonne
-- sdctk_sdct_id      => identifiant [sys_definition_context_table.sdct_id] de la table  � laquelle fait r�f�rence la colonne
-- sdctk_sdctk_id    => si c'est une cl� �trang�re, identifiant [sdctk_id] de la colonne qui fait office de cl� primaire. Si c'est une cl� primaire, le champ est nul.
--
DROP TABLE IF EXISTS sys_definition_context_table_key;
CREATE TABLE sys_definition_context_table_key
(
	sdctk_id integer,
	sdctk_column text,
	sdctk_sdct_id integer,
	sdctk_sdctk_id text
)
WITH (OIDS=TRUE);
ALTER TABLE sys_definition_context_table_key OWNER TO postgres;

-- sys_definition_kpi
--	pk
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (1, 'id_ligne', 1, null);
-- sys_field_reference
--	pk
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (2, 'id_ligne', 2, null);
-- sys_pauto_page_name
--	pk
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (3, 'id_page', 3, null);
-- sys_pauto_config
--	pk
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (4, 'id', 4, null);
--	fk
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (5, 'id_page', 4, 3);
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (6, 'id_elem', 4, 1);
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (7, 'id_elem', 4, 2);
-- graph_data
--	fk
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (8, 'id_data', 5, 4);
-- graph_information
--	fk
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (9, 'id_page', 6, 3);
--INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (10, 'gis_based_on', 6, 4);
--INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (11, 'default_orderby', 6, 4);
--INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (12, 'pie_split_by', 6, 4);
-- menu_deroulant_intranet
--	pk
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (13, 'id_menu', 7, null);
--	fk
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (14, 'id_page', 7, 3);
-- sys_definition_dashboard
--	fk
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (15, 'sdd_id_page', 8, 3);
--INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (16, 'sdd_sort_by_id', 8, 4);
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (17, 'sdd_id_menu', 8, 13);
-- profile
--	pk
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (18, 'id_profile', 10, null);
-- users
--	pk
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (19, 'id_user', 11, null);
--	fk
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (20, 'user_profil', 11, 18);
-- sys_user_group
--	pk
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (21, 'id_group', 12, null);
--	fk
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (22, 'id_user', 12, 19);
-- profile_menu_position
--	fk
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (23, 'id_menu', 13, 13);
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (24, 'id_profile', 13, 18);
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (25, 'id_menu_parent', 13, 13);
-- sys_definition_alarm_static
--	pk
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (26, 'alarm_id', 14, null);
-- sys_definition_alarm_dynamic
--	pk
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (27, 'alarm_id', 15, null);
-- sys_definition_alarm_top_worst
--	pk
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (28, 'alarm_id', 16, null);
-- sys_aa_column
--	pf
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (29, 'saac_idcolumn', 17, null);
-- sys_aa_column_code
--	fk
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (30, 'saacc_idcolumn', 18, 29);
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (31, 'saacc_dashboard_id_page', 18, 3);
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (42, 'saacc_interface', 18, 33);
-- sys_aa_contextuel_filter
--	fk
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (32, 'saacf_idvue', 19, 37);
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (39, 'saacf_idcolumn', 19, 36);
-- sys_aa_interface
--	pk
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (33, 'saai_interface', 20, null);
-- sys_aa_list_filter
--	fk
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (34, 'saalf_idfilter', 21, 35);
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (40, 'saalf_idcolumn', 21, 29);
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (41, 'saalf_idoperator', 21, 36);
-- sys_aa_filter_kpi
--	pk
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (35, 'saafk_idfilter', 22, null);
--	fk
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (38, 'saafk_idvue', 22, 37);
-- sys_aa_operator
--	pk
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (36, 'saao_idoperator', 23, null);
-- sys_aa_vue
--	pk
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (37, 'saav_idvue', 24, null);
-- sys_data_range_style
--	fk
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (43, 'id_element', 25, 1);
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (44, 'id_element', 25, 2);
-- sys_definition_network_agregation
--	pk

-- sys_field_reference_all
--	pk
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (45, 'id_ligne', 27, null);
-- sys_gis_config
-- 	pk
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (46, 'id', 29, null);
--	fk
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (47, 'id_palier', 29, 49);
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (48, 'id_vecteur', 29, 50);
-- sys_gis_config_palier
--	pk
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (49, 'id', 31, null);
-- sys_gis_config_vecteur
--	pk
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (50, 'id', 32, null);
-- sys_gis_config_global
--	pk
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (51, 'id', 30, null);
-- sys_global_parameters
-- 	pk
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (52, 'parameters', 34, null);
-- sys_export_raw_kpi_config
-- 	pk
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (53, 'export_id', 35, null);
-- sys_export_raw_kpi_data
--	fk
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (54, 'export_id', 36, 53);
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (55, 'raw_kpi_id', 36, 1);
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (56, 'raw_kpi_id', 36, 2);
-- sys_definition_network_agregation
--	pk
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (57, 'sdna_id', 26, null);
-- sys_definition_selecteur
--	pk
-- 16:17 09/07/2009 GHX
-- Modification du nom de la colonne de la cl� �trang�re
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (58, 'sds_report_id', 37, 3);

--
-- Cr�ation du la table qui contiendra les �l�ments qui peuvent �tre dans un contexte (graphe, dashboard, alarmes...)
--
-- sdc_id                                         => identifiant
-- sdc_label                                     => label de l'�l�ment pour l'affichage
-- sdc_column                                 => nom de la colonne qui doit �tre affich�, pas besoin d'�tre configurer si la valeur sdc_selected est 0
--                                                           ATTENTION : la colonne est celle de la premi�re table !!!
-- sdc_default                                 => d�termine si les �l�ments doivent �tre s�lectionn�s par d�faut (1 : oui / 0 : non)
-- sdc_visible                                  => sp�cifie si les �l�ments sont visibles � l'affichage (1 : oui / 0 : non)
-- sdc_master                                  => sp�cifie si les �l�ments sont pr�sent uniquement dans le contexte du master ou il n'y a qu'un seul produit
-- sdc_truncate                               => sp�cifie si les �l�ments doivent supprimer avant d'ins�rer les nouveaux  (1 : oui / 0 : non) sert uniqment pour quand on monte un contexte
-- sdc_selected                               => sp�cifie si on peut s�lectionner un (des) �l�ment(s) ou alors on prend toutes la tables (1 : on peut s�lectionner / 0 : on prend tous)
-- sdc_sql_where_select_default => condition pour la requ�te SQL qui r�cup�re les �l�ments � afficher & condition pour la requ�te SQL pour savoir les �l�ments qui sont s�lectionn�s par d�faut
--                                                           ATTENTION : la requ�te est ex�ctut�e sur la premi�re table !!!
-- sdc_order_display                     => order d'affichage pour l'IHM
--
DROP TABLE IF EXISTS sys_definition_context;
CREATE TABLE sys_definition_context
(
	sdc_id text,
	sdc_label text,
	sdc_column text,
	sdc_default integer,
	sdc_visible integer,
	sdc_master integer,
	sdc_truncate integer,
	sdc_selected integer,
	sdc_sql_where_select_default text,
	sdc_order_display integer
)
WITH (OIDS=TRUE);
ALTER TABLE sys_definition_context OWNER TO postgres;

INSERT INTO sys_definition_context (sdc_id, sdc_label, sdc_column, sdc_default, sdc_visible, sdc_master, sdc_truncate, sdc_selected, sdc_sql_where_select_default, sdc_order_display) VALUES (1, 'Graphs', 'page_name', 1, 1, 1, 0, 1, 'page_type=''gtm''', 1);
INSERT INTO sys_definition_context (sdc_id, sdc_label, sdc_column, sdc_default, sdc_visible, sdc_master, sdc_truncate, sdc_selected, sdc_sql_where_select_default, sdc_order_display) VALUES (2, 'Dashboards', 'page_name', 1, 1, 1, 0, 1, 'page_type=''page''', 2);
INSERT INTO sys_definition_context (sdc_id, sdc_label, sdc_column, sdc_default, sdc_visible, sdc_master, sdc_truncate, sdc_selected, sdc_sql_where_select_default, sdc_order_display) VALUES (3, 'Reports', 'page_name', 1, 1, 1, 0, 1, 'page_type=''report''', 3);
INSERT INTO sys_definition_context (sdc_id, sdc_label, sdc_column, sdc_default, sdc_visible, sdc_master, sdc_truncate, sdc_selected, sdc_sql_where_select_default, sdc_order_display) VALUES (4, 'Users', null, 0, 1, 0, 0, 0, null, 4);
INSERT INTO sys_definition_context (sdc_id, sdc_label, sdc_column, sdc_default, sdc_visible, sdc_master, sdc_truncate, sdc_selected, sdc_sql_where_select_default, sdc_order_display) VALUES (5, 'Groups', null, 0, 1, 0, 0, 0, null, 5);
INSERT INTO sys_definition_context (sdc_id, sdc_label, sdc_column, sdc_default, sdc_visible, sdc_master, sdc_truncate, sdc_selected, sdc_sql_where_select_default, sdc_order_display) VALUES (6, 'Profiles', null, 0, 1, 0,  0, 0, null, 6);
INSERT INTO sys_definition_context (sdc_id, sdc_label, sdc_column, sdc_default, sdc_visible, sdc_master, sdc_truncate, sdc_selected, sdc_sql_where_select_default, sdc_order_display) VALUES (7, 'Static alarms', 'alarm_name', 1, 1, 0, 0, 1, null, 7);
INSERT INTO sys_definition_context (sdc_id, sdc_label, sdc_column, sdc_default, sdc_visible, sdc_master, sdc_truncate, sdc_selected, sdc_sql_where_select_default, sdc_order_display) VALUES (8, 'Dynamic alarms', 'alarm_name', 1, 1, 0, 0, 1, null, 8);
INSERT INTO sys_definition_context (sdc_id, sdc_label, sdc_column, sdc_default, sdc_visible, sdc_master, sdc_truncate, sdc_selected, sdc_sql_where_select_default, sdc_order_display) VALUES (9, 'Top/Worst lists', 'alarm_name', 1, 1, 0, 0, 1, null, 9);
INSERT INTO sys_definition_context (sdc_id, sdc_label, sdc_column, sdc_default, sdc_visible, sdc_master, sdc_truncate, sdc_selected, sdc_sql_where_select_default, sdc_order_display) VALUES (10, 'KPI', null, 1, 1, 0, 0, 0, null, 10);
INSERT INTO sys_definition_context (sdc_id, sdc_label, sdc_column, sdc_default, sdc_visible, sdc_master, sdc_truncate, sdc_selected, sdc_sql_where_select_default, sdc_order_display) VALUES (11, 'Counters', null, 1, 1, 0, 0, 0, null, 11);
INSERT INTO sys_definition_context (sdc_id, sdc_label, sdc_column, sdc_default, sdc_visible, sdc_master, sdc_truncate, sdc_selected, sdc_sql_where_select_default, sdc_order_display) VALUES (12, 'Link to Activity Analysis', null, 1, 1, 0, 1, 0, null, 12);
INSERT INTO sys_definition_context (sdc_id, sdc_label, sdc_column, sdc_default, sdc_visible, sdc_master, sdc_truncate, sdc_selected, sdc_sql_where_select_default, sdc_order_display) VALUES (13, 'Data Range', null, 1, 1, 0, 1, 0, null, 13);
INSERT INTO sys_definition_context (sdc_id, sdc_label, sdc_column, sdc_default, sdc_visible, sdc_master, sdc_truncate, sdc_selected, sdc_sql_where_select_default, sdc_order_display) VALUES (14, 'Dynamics counters', null, 1, 1, 0, 1, 0, null, 14);
INSERT INTO sys_definition_context (sdc_id, sdc_label, sdc_column, sdc_default, sdc_visible, sdc_master, sdc_truncate, sdc_selected, sdc_sql_where_select_default, sdc_order_display) VALUES (15, 'Network Aggregation', null, 0, 1, 0, 0, 0, 'mandatory = 1', 15);
INSERT INTO sys_definition_context (sdc_id, sdc_label, sdc_column, sdc_default, sdc_visible, sdc_master, sdc_truncate, sdc_selected, sdc_sql_where_select_default, sdc_order_display) VALUES (16, 'Time Aggregation', null, 0, 1, 0, 1, 0, null, 16);
INSERT INTO sys_definition_context (sdc_id, sdc_label, sdc_column, sdc_default, sdc_visible, sdc_master, sdc_truncate, sdc_selected, sdc_sql_where_select_default, sdc_order_display) VALUES (17, 'GIS', null, 0, 1, 0, 1, 0, null, 17);
INSERT INTO sys_definition_context (sdc_id, sdc_label, sdc_column, sdc_default, sdc_visible, sdc_master, sdc_truncate, sdc_selected, sdc_sql_where_select_default, sdc_order_display) VALUES (18, 'Global parameters', null, 0, 1, 0, 0, 0, null, 18);
INSERT INTO sys_definition_context (sdc_id, sdc_label, sdc_column, sdc_default, sdc_visible, sdc_master, sdc_truncate, sdc_selected, sdc_sql_where_select_default, sdc_order_display) VALUES (19, 'Export Data', null, 0, 1, 0, 0, 0, null, 19);
INSERT INTO sys_definition_context (sdc_id, sdc_label, sdc_column, sdc_default, sdc_visible, sdc_master, sdc_truncate, sdc_selected, sdc_sql_where_select_default, sdc_order_display) VALUES (20, 'Menu', null, 1, 1, 0, 0, 0, null, 20);

--
-- Cr�ation de la table qui contiendra la liste des tables utilis�s pour un �l�ment de style graphe, dashboard...
--
-- sdctl_sdc_id       => identifiant d'un �l�ment [sys_definition_context.sdc_id)
-- sdctl_sdct_id     => identifiant de la table utilis� pour un �l�ment [sys_definition_context_table.sdct_id]
-- sdctl_select_all => sp�cifie si toutes les donn�es de la table doivent �tre prises ou seulement les �l�ments concern�s (1 : on prend tous / 0 : uniquement les �l�ments concern�s),
--                                 Pour un �l�ment ayant qu'une seule table alors la valeur n'a pas d'importance, ca d�pend de la valeur de sys_definition_context.sdc_selected
-- sdctl_order        => ordre des tables
--
DROP TABLE IF EXISTS sys_definition_context_table_link;
CREATE TABLE sys_definition_context_table_link
(
	sdctl_sdc_id integer,
	sdctl_sdct_id integer,
	sdctl_select_all integer,
	sdctl_order integer
)
WITH (OIDS=TRUE);
ALTER TABLE sys_definition_context_table_link OWNER TO postgres;

-- graphe
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (1, 3, 0, 1);-- sys_pauto_page_name
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (1, 4, 0, 2);-- sys_pauto_config
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (1, 5, 0, 3);-- graph_data
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (1, 6, 0, 4);-- graph_information
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (1, 1, 1, 5);-- sys_definition_kpi
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (1, 2, 1, 6);-- sys_field_reference
-- dashboard
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (2, 3, 0, 1);-- sys_pauto_page_name
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (2, 4, 0, 2);-- sys_pauto_config
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (2, 8, 0, 3);-- sys_definition_dashboard
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (2, 7, 0, 4);-- menu_deroulant_intranet
-- rapports
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (3, 3, 0, 1);-- sys_pauto_page_name
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (3, 4, 0, 2);-- sys_pauto_config
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (3, 37, 0, 3);-- sys_definition_selecteur
-- Utilisateurs
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (4, 11, 0, 1);-- users
-- Groupes
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (5, 12, 0, 1);-- sys_user_group
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (5, 11, 1, 2);-- users
-- Profils
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (6, 10, 0, 1);-- profile
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (6, 13, 0, 2);-- profile_menu_position
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (6, 7, 0, 3);-- menu_deroulant_intranet
-- Alarmes statiques
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (7, 14, 0, 1);-- sys_definition_alarm_static
-- Alarmes dynamiques
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (8, 15, 0, 1);-- sys_definition_alarm_dynamic
-- Top/Worst list
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (9, 16, 0, 1);-- sys_definition_alarm_top_worst
-- KPI
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (10, 1, 0, 1);-- sys_definition_kpi
-- RAW
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (11, 2, 0, 1);-- sys_field_reference
-- menu
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (20, 7, 0, 1);-- menu_deroulant_intranet
-- Link to AA
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (12, 24, 0, 1);-- sys_aa_vue
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (12, 17, 0, 2);-- sys_aa_column
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (12, 18, 0, 3);-- sys_aa_column_code
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (12, 20, 0, 4);-- sys_aa_interface
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (12, 23, 0, 5);-- sys_aa_operator
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (12, 22, 0, 6);-- sys_aa_list_kpi
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (12, 19, 0, 7);-- sys_aa_contextuel_filter
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (12, 21, 0, 8);-- sys_aa_list_filter
-- Data Range
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (13, 25, 0, 1);-- sys_data_range_style
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (13, 1, 0, 2);-- sys_definition_kpi
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (13, 2, 0, 3);-- sys_field_reference
-- sys_field_reference_all
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (14, 27, 0, 1);-- sys_field_reference_all
-- Network Aggregation
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (15, 26, 0, 1);-- sys_definition_network_agregation
-- Time Aggregation
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (16, 28, 0, 1);-- sys_definition_time_agregation
-- GIS
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (17, 29, 0, 1);-- sys_gis_config
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (17, 30, 0, 2);-- sys_gis_config_global
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (17, 31, 0, 3);-- sys_gis_config_palier
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (17, 32, 0, 4);-- sys_gis_config_vecteur
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (17, 33, 0, 5);-- geometry_columns
-- Global parameters
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (18, 34, 0, 1);-- sys_global_parameters
-- Export Data
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (19, 35, 0, 1);-- sys_export_raw_kpi_config
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (19, 36, 0, 2);-- sys_export_raw_kpi_data
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (19, 1, 1, 3);-- sys_definition_kpi
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (19, 2, 1, 4);-- sys_field_reference

-----------------------------------------------------------------------------------
-----------------------------------------------------------------------------------

--
-- Cr�ation du la table qui contiendra les �l�ments d'un contexte � supprimer
--
-- id => correspondra � l'identifiant unique � supprimer
-- table => nom de la table dans laquel se trouve l'id
--
DROP TABLE IF EXISTS sys_definition_context_element_to_delete;
CREATE TABLE sys_definition_context_element_to_delete
(
	sdcetd_id text,
	sdcetd_table text
)
WITH (OIDS=TRUE);
ALTER TABLE sys_definition_context_element_to_delete OWNER TO postgres;

--
-- Cr�ation d'une fonction pg/psql qui est appel� par des triggers plac�s sur des deletes
--
-- text : identifiant unique de l'�l�ment qui doit �tre supprim�
-- text : nom de la table dans laquel se trouve l'id
--
CREATE OR REPLACE FUNCTION contextAddElementToDelete () RETURNS TRIGGER AS $$
DECLARE
	primaryKey TEXT;
	sql TEXT;
BEGIN
	SELECT sdctk_column INTO primaryKey FROM sys_definition_context_table LEFT JOIN sys_definition_context_table_key ON (sdct_id = sdctk_sdct_id) WHERE sdctk_sdctk_id IS NULL AND sdct_table = TG_RELNAME;
	sql := 'INSERT INTO sys_definition_context_element_to_delete (sdcetd_id, sdcetd_table) VALUES ((SELECT '||primaryKey||' FROM '||TG_RELNAME||' WHERE oid = '||OLD.oid||'), '||quote_literal(TG_RELNAME)||')';
	EXECUTE sql;
	RETURN OLD;
END;
$$ LANGUAGE plpgsql;

--
-- Cr�ation d'une fonction pg/psql qui est appel�e lorsque l'on monte un contexte pour supprimer les �l�ments � supprimer
--
CREATE OR REPLACE FUNCTION contextDeleteElementToDelete () RETURNS void AS $$
DECLARE
	element RECORD;
BEGIN
	FOR element IN SELECT sdctk_column, sdcetd_table, sdcetd_id FROM ctx_sys_definition_context_element_to_delete LEFT JOIN (sys_definition_context_table LEFT JOIN sys_definition_context_table_key ON (sdct_id = sdctk_sdct_id) ) ON (sdct_table = sdcetd_table) WHERE sdctk_sdctk_id IS NULL LOOP
		-- Requete qui supprime l�l�ment
		EXECUTE 'DELETE FROM '||quote_ident(element.sdcetd_table)||' WHERE '||quote_ident(element.sdctk_column)||' = '||quote_literal(element.sdcetd_id);
		-- Requete qui supprime la ligne de la table sys_definition_context_element_to_delete
		EXECUTE 'DELETE FROM sys_definition_context_element_to_delete WHERE sdcetd_table = '||quote_literal(element.sdcetd_table)||' AND sdcetd_id = '||quote_literal(element.sdcetd_id);
	END LOOP;
END
$$ LANGUAGE plpgsql;

--
-- Cr�ation d'une fonction pl/pgsql qui permet d'activer ou de d�sactiver un trigger sur une table
-- On utilise une fonction afin de ne pas g�n�rer d'erreur s'il n'y a pas de trigger sur la table
--
--
CREATE OR REPLACE FUNCTION contextEnableTrigger ( text, boolean ) RETURNS void AS $$
BEGIN
	IF $2 = TRUE THEN
		EXECUTE 'ALTER TABLE users ENABLE TRIGGER triggerContextAddElementToDelete_'||$1;
	ELSE
		EXECUTE 'ALTER TABLE users DISABLE TRIGGER triggerContextAddElementToDelete_'||$1;
	END IF;
EXCEPTION
	WHEN UNDEFINED_OBJECT THEN
		RETURN;
END
$$ LANGUAGE plpgsql;

--
-- Cr�ation des triggers
-- 
DROP TRIGGER triggerContextAddElementToDelete_sys_definition_kpi ON sys_definition_kpi;
CREATE TRIGGER triggerContextAddElementToDelete_sys_definition_kpi
BEFORE DELETE ON sys_definition_kpi
	FOR EACH ROW EXECUTE PROCEDURE contextAddElementToDelete();

DROP TRIGGER triggerContextAddElementToDelete_sys_field_reference ON sys_field_reference;
CREATE TRIGGER triggerContextAddElementToDelete_sys_field_reference
BEFORE DELETE ON sys_field_reference
	FOR EACH ROW EXECUTE PROCEDURE contextAddElementToDelete();

DROP TRIGGER triggerContextAddElementToDelete_sys_pauto_page_name ON sys_pauto_page_name;
CREATE TRIGGER triggerContextAddElementToDelete_sys_pauto_page_name
BEFORE DELETE ON sys_pauto_page_name
	FOR EACH ROW EXECUTE PROCEDURE contextAddElementToDelete();

DROP TRIGGER triggerContextAddElementToDelete_sys_pauto_config ON sys_pauto_config;
CREATE TRIGGER triggerContextAddElementToDelete_sys_pauto_config
BEFORE DELETE ON sys_pauto_config
	FOR EACH ROW EXECUTE PROCEDURE contextAddElementToDelete();

DROP TRIGGER triggerContextAddElementToDelete_menu_deroulant_intranet ON menu_deroulant_intranet;
CREATE TRIGGER triggerContextAddElementToDelete_menu_deroulant_intranet
BEFORE DELETE ON menu_deroulant_intranet
	FOR EACH ROW EXECUTE PROCEDURE contextAddElementToDelete();

DROP TRIGGER triggerContextAddElementToDelete_profile ON profile;
CREATE TRIGGER triggerContextAddElementToDelete_profile
BEFORE DELETE ON profile
	FOR EACH ROW EXECUTE PROCEDURE contextAddElementToDelete();

DROP TRIGGER triggerContextAddElementToDelete_users ON users;
CREATE TRIGGER triggerContextAddElementToDelete_users
BEFORE DELETE ON users
	FOR EACH ROW EXECUTE PROCEDURE contextAddElementToDelete();

DROP TRIGGER triggerContextAddElementToDelete_sys_user_group ON sys_user_group;
CREATE TRIGGER triggerContextAddElementToDelete_sys_user_group
BEFORE DELETE ON sys_user_group
	FOR EACH ROW EXECUTE PROCEDURE contextAddElementToDelete();

DROP TRIGGER triggerContextAddElementToDelete_sys_definition_alarm_static ON sys_definition_alarm_static;
CREATE TRIGGER triggerContextAddElementToDelete_sys_definition_alarm_static
BEFORE DELETE ON sys_definition_alarm_static
	FOR EACH ROW EXECUTE PROCEDURE contextAddElementToDelete();

DROP TRIGGER triggerContextAddElementToDelete_sys_definition_alarm_dynamic ON sys_definition_alarm_dynamic;
CREATE TRIGGER triggerContextAddElementToDelete_sys_definition_alarm_dynamic
BEFORE DELETE ON sys_definition_alarm_dynamic
	FOR EACH ROW EXECUTE PROCEDURE contextAddElementToDelete();

DROP TRIGGER triggerContextAddElementToDelete_sys_definition_alarm_top_worst ON sys_definition_alarm_top_worst;
CREATE TRIGGER triggerContextAddElementToDelete_sys_definition_alarm_top_worst
BEFORE DELETE ON sys_definition_alarm_top_worst
	FOR EACH ROW EXECUTE PROCEDURE contextAddElementToDelete();

DROP TRIGGER triggerContextAddElementToDelete_sys_definition_network_agregation ON sys_definition_network_agregation;
CREATE TRIGGER triggerContextAddElementToDelete_sys_definition_network_agregation
BEFORE DELETE ON sys_definition_network_agregation
	FOR EACH ROW EXECUTE PROCEDURE contextAddElementToDelete();

DROP TRIGGER triggerContextAddElementToDelete_sys_export_raw_kpi_config ON sys_export_raw_kpi_config;
CREATE TRIGGER triggerContextAddElementToDelete_sys_export_raw_kpi_config
BEFORE DELETE ON sys_export_raw_kpi_config
	FOR EACH ROW EXECUTE PROCEDURE contextAddElementToDelete();

-----------------------------------------------------------------------------------
-----------------------------------------------------------------------------------

--
-- Modification des colonnes des identifiants pour qu'ils puissent accept�s du texte dans le cas des nouvelles fa�ons de g�r�r les identifiants uniques ("internal_id")
--
ALTER TABLE users ALTER COLUMN id_user TYPE text;
ALTER TABLE users ALTER COLUMN user_profil TYPE text;
ALTER TABLE sys_user_parameter ALTER COLUMN id_user TYPE text;
ALTER TABLE sys_panier_mgt ALTER COLUMN id_user TYPE text;
ALTER TABLE sys_contenu_buffer ALTER COLUMN id_user TYPE text;
ALTER TABLE track_pages ALTER COLUMN id_user TYPE text;
ALTER TABLE sys_pauto_page_name ALTER COLUMN id_user TYPE text;
ALTER TABLE sys_pauto_page_name ALTER COLUMN id_page TYPE text;
ALTER TABLE sys_user_group ALTER COLUMN id_user TYPE text;
ALTER TABLE sys_user_group ALTER COLUMN id_group TYPE text;
ALTER TABLE sys_file_uploaded_archive  ALTER COLUMN id_user TYPE text;
ALTER TABLE sys_definition_users_per_connection  ALTER COLUMN sdupc_id_user TYPE text;
ALTER TABLE profile ALTER COLUMN id_profile TYPE text;
ALTER TABLE profile_menu_position ALTER COLUMN id_profile TYPE text;
ALTER TABLE profile_menu_position ALTER COLUMN id_menu TYPE text;
ALTER TABLE profile_menu_position ALTER COLUMN id_menu_parent TYPE text;
ALTER TABLE sys_pauto_config ALTER COLUMN id TYPE text;
ALTER TABLE sys_pauto_config ALTER COLUMN id_elem TYPE text;
ALTER TABLE sys_pauto_config ALTER COLUMN id_page TYPE text;
ALTER TABLE graph_data ALTER COLUMN id_data TYPE text;
ALTER TABLE graph_information ALTER COLUMN gis_based_on TYPE text;
ALTER TABLE graph_information ALTER COLUMN default_orderby TYPE text;
ALTER TABLE graph_information ALTER COLUMN id_page TYPE text;
ALTER TABLE graph_information ALTER COLUMN pie_split_by TYPE text;
ALTER TABLE sys_definition_dashboard ALTER COLUMN sdd_id_page TYPE text;
ALTER TABLE sys_definition_dashboard ALTER COLUMN sdd_id_menu TYPE text;
ALTER TABLE sys_definition_dashboard ALTER COLUMN sdd_sort_by_id TYPE text;
ALTER TABLE menu_deroulant_intranet ALTER COLUMN id_menu TYPE text;
ALTER TABLE menu_deroulant_intranet ALTER COLUMN id_menu_parent TYPE text;
ALTER TABLE menu_deroulant_intranet ALTER COLUMN id_page TYPE text;
ALTER TABLE sys_definition_kpi ALTER COLUMN id_ligne TYPE text;
ALTER TABLE sys_export_raw_kpi_data ALTER COLUMN export_id TYPE text;
ALTER TABLE sys_export_raw_kpi_data ALTER COLUMN raw_kpi_id TYPE text;
ALTER TABLE sys_field_reference ALTER COLUMN id_ligne TYPE text;
ALTER TABLE sys_data_range_style ALTER COLUMN id_element TYPE text;
ALTER TABLE sys_export_raw_kpi_config ALTER COLUMN export_id TYPE text;
ALTER TABLE sys_report_schedule ALTER COLUMN schedule_id TYPE text;
ALTER TABLE sys_report_sendmail ALTER COLUMN mailto TYPE text;
ALTER TABLE sys_report_sendmail ALTER COLUMN schedule_id TYPE text;
ALTER TABLE sys_definition_alarm_static ALTER COLUMN alarm_id TYPE text;
ALTER TABLE sys_definition_alarm_top_worst ALTER COLUMN alarm_id TYPE text;
ALTER TABLE sys_definition_alarm_dynamic ALTER COLUMN alarm_id TYPE text;
ALTER TABLE sys_definition_alarm_network_elements ALTER COLUMN id_alarm TYPE text;
ALTER TABLE sys_definition_alarm_exclusion ALTER COLUMN id_alarm TYPE text;
ALTER TABLE sys_alarm_email_sender ALTER COLUMN id_alarm TYPE text;
ALTER TABLE sys_alarm_email_sender ALTER COLUMN id_group TYPE text;
ALTER TABLE sys_alarm_snmp_sender ALTER COLUMN id_alarm TYPE text;
ALTER TABLE edw_alarm ALTER COLUMN id_alarm TYPE text;
ALTER TABLE sys_aa_column_code ALTER COLUMN saacc_dashboard_id_page TYPE text;
ALTER TABLE sys_definition_selecteur ALTER COLUMN sds_report_id TYPE text;
-- 12:00 18/03/2009 GHX
ALTER TABLE sys_definition_selecteur ALTER COLUMN sds_id_page TYPE text;
ALTER TABLE users ALTER COLUMN homepage TYPE text;
ALTER TABLE edw_alarm_log_error ALTER COLUMN id_alarm TYPE text;
ALTER TABLE edw_comment ALTER COLUMN id_user TYPE text;
-- 20/03/2009 SPS
ALTER TABLE edw_comment ALTER COLUMN id_elem TYPE text;

------------------------------------------------------------------UPDATE sys_definition_kpi SET id_ligne = internal_id;
------------------------------------------------------------------UPDATE sys_field_reference SET id_ligne = internal_id;

-- SUPPRESSION DES COLONNES internal_id
ALTER TABLE sys_pauto_config DROP COLUMN internal_id;
ALTER TABLE sys_definition_kpi DROP COLUMN internal_id;
ALTER TABLE sys_field_reference DROP COLUMN internal_id;
ALTER TABLE sys_pauto_page_name DROP COLUMN internal_id;


-- Cr�ation d'une colonne id pour les niveaux d'agr�gaton
ALTER TABLE sys_definition_network_agregation ADD COLUMN sdna_id text;

-- 18/03/2009 - Modif. SLC - ajout des lignes de separation dans menu_contextuel
-- on ajoute la premiere separation (entre reload et le telechargement des fichiers)
UPDATE menu_contextuel SET ordre_menu = ordre_menu + 1 WHERE type_pauto='dashboard';
INSERT INTO menu_contextuel (id,type_pauto,ordre_menu) VALUES (0,'dashboard',1);
-- on ajoute la seconde separation (entre le telech. des fichiers et le changement de mode)
UPDATE menu_contextuel SET ordre_menu = ordre_menu + 1 WHERE type_pauto='dashboard' AND ordre_menu>4;
INSERT INTO menu_contextuel (id,type_pauto,ordre_menu) VALUES (0,'dashboard',5);
-- on ajoute la troisieme separation (entre le changement de mode et le lien "send to")
UPDATE menu_contextuel SET ordre_menu = ordre_menu + 1 WHERE type_pauto='dashboard' AND ordre_menu>7;
INSERT INTO menu_contextuel (id,type_pauto,ordre_menu) VALUES (0,'dashboard',8);
-- on met � jour les menu deja existant
UPDATE menu_deroulant_intranet SET liste_action='0-5135-5136-5137-0-5132-5133-0-5134' WHERE liste_action='5135-5136-5137-5132-5133-5134';

-- 23/03/2009 - ajout SPS : creation de la table contenant le nom des fichiers de contexte ajoutes
DROP TABLE IF EXISTS sys_definition_context_management;
CREATE TABLE sys_definition_context_management
(
	sdcm_file_name text,
	sdcm_date text
)
WITH (OIDS=TRUE);
ALTER TABLE sys_definition_context_element_to_delete OWNER TO postgres;

-- 25/03/2009 - MPR : BZ 8006 : Script de calcul d'alarmes it�ratives est trop long
ALTER TABLE edw_alarm ALTER visible SET DEFAULT 1;

-- 12:14 27/10/2008 SCT : ajout des commandes SQL pour la gestion des fichiers ZIP
-- Ajout d'une colonne dans la table sys_definition_flat_file_lib pour g�rer les ordres de priorit� dans la collecte des fichiers et identification des fichiers de r�f�rence
	-- SYS_DEFINITION_FLAT_FILE_LIB
ALTER TABLE sys_definition_flat_file_lib ADD reference text;
ALTER TABLE sys_definition_flat_file_lib ADD ordre int default 0;
-- Ajout d'une colonne dans la table sys_definition_connection pour g�rer l'affichage de la connexion sur le r�pertoire 'flat_file_zip'
	-- SYS_DEFINITION_CONNECTION
ALTER TABLE sys_definition_connection ADD protected int default 0;
-- Ajout d'une ligne dans la table sys_definition_connection pour g�rer la connexion sur le r�pertoire 'flat_file_zip'
	-- SYS_DEFINITION_CONNECTION
DELETE FROM sys_definition_connection WHERE connection_name = 'flat_file_zip';

-- 08:53 07/04/2009 GHX
-- Ajout des codes produits pour le contexte
DELETE FROM sys_aa_interface WHERE saai_module = 'isup';
INSERT INTO sys_aa_interface (saai_interface, saai_module) VALUES ('64', 'isup');
DELETE FROM sys_aa_interface WHERE saai_module = 'gprs';
INSERT INTO sys_aa_interface (saai_interface, saai_module) VALUES ('128', 'gprs');
DELETE FROM sys_aa_interface WHERE saai_module = 'umts';
INSERT INTO sys_aa_interface (saai_interface, saai_module) VALUES ('256', 'umts');
DELETE FROM sys_aa_interface WHERE saai_module = 'core';
INSERT INTO sys_aa_interface (saai_interface, saai_module) VALUES ('1024', 'core');
DELETE FROM sys_aa_interface WHERE saai_module = 'abis';
INSERT INTO sys_aa_interface (saai_interface, saai_module) VALUES ('4096', 'abis');
DELETE FROM sys_aa_interface WHERE saai_module = 'hpg';
INSERT INTO sys_aa_interface (saai_interface, saai_module) VALUES ('5', 'hpg');
DELETE FROM sys_aa_interface WHERE saai_module = 'roaming';
INSERT INTO sys_aa_interface (saai_interface, saai_module) VALUES ('15', 'roaming');
DELETE FROM sys_aa_interface WHERE saai_module = 'alcatel';
DELETE FROM sys_aa_interface WHERE saai_module = 'omc';
INSERT INTO sys_aa_interface (saai_interface, saai_module) VALUES ('25', 'omc');
DELETE FROM sys_aa_interface WHERE saai_module = 'huawei';
INSERT INTO sys_aa_interface (saai_interface, saai_module) VALUES ('35', 'huawei');
DELETE FROM sys_aa_interface WHERE saai_module = 'ericsson';
INSERT INTO sys_aa_interface (saai_interface, saai_module) VALUES ('45', 'ericsson');

-- 07/04/2009 BBX
-- Data Export doit �tre �x�cut� �galement lors du compute Hour
-- Insertion dans sys_definition_family
INSERT INTO sys_definition_family (family_name,family_type,master_id,ordre,id_group_table,on_off)
VALUES ('Hourly - Data Export','internal',
(SELECT master_id FROM sys_definition_master WHERE master_name = 'Compute Hourly'),
(SELECT (ordre-1) FROM sys_definition_family WHERE family_name = 'Hourly - Compute Stop - Info'),0,1);
-- Insertion dans sys_definition_step
INSERT INTO sys_definition_step (step_name,script,step_type,on_off,family_id,ordre,visible)
VALUES ('Hourly - Data Export','/scripts/collect_export_data.php','internal',1,
(SELECT family_id FROM sys_definition_family WHERE family_name = 'Hourly - Data Export'),0,1);


-- 16:59 09/04/2009 GHX
--
-- Cette table permet par rapport un �l�ment d'un contexte de d�finir les d�pendances. (Exemple les dashboards doivent contenir les graphes)
-- 
-- sdcd_sdc_id                       => identifiant de l'�l�ment parent
-- sdcd_sdc_id_dependency => identifiant de l'�l�ment fils
-- sdcd_sdct_id                      => identifiant de la table qui sert de jointure
-- sdcd_column                       => nom de la colonne permettant de connaitre les �l�ments fils � s�lectionner par rapport au parent
-- sdcd_column_join              => nom de la colonne permettant de faire la jointure sur la table avec la liste des �l�ments parents
-- sdcd_where                         => condition suppl�mentaires sur la table utilis� avec AND
--
DROP TABLE IF EXISTS sys_definition_context_dependency;
CREATE TABLE sys_definition_context_dependency
(
	sdcd_sdc_id integer,
	sdcd_sdc_id_dependency integer,
	sdcd_sdct_id integer,
	sdcd_column text,
	sdcd_column_join text,
	sdcd_where text
)
WITH (OIDS=TRUE);
ALTER TABLE sys_definition_context_dependency OWNER TO postgres;
-- Dash -> graph
INSERT INTO sys_definition_context_dependency (sdcd_sdc_id, sdcd_sdc_id_dependency, sdcd_sdct_id, sdcd_column, sdcd_column_join, sdcd_where) VALUES (2, 1, 4 , 'id_elem', 'id_page', 'class_object=''graph''');
-- Rapport -> dash
INSERT INTO sys_definition_context_dependency (sdcd_sdc_id, sdcd_sdc_id_dependency, sdcd_sdct_id, sdcd_column, sdcd_column_join, sdcd_where) VALUES (3, 2, 4 , 'id_elem', 'id_page', 'class_object=''page''');
-- Rapport -> alarmes statiques
INSERT INTO sys_definition_context_dependency (sdcd_sdc_id, sdcd_sdc_id_dependency, sdcd_sdct_id, sdcd_column, sdcd_column_join, sdcd_where) VALUES (3, 7, 4 , 'id_elem', 'id_page', 'class_object=''alarm_static''');
-- Rapport -> alarmes dynamiques
INSERT INTO sys_definition_context_dependency (sdcd_sdc_id, sdcd_sdc_id_dependency, sdcd_sdct_id, sdcd_column, sdcd_column_join, sdcd_where) VALUES (3, 8, 4 , 'id_elem', 'id_page', 'class_object=''alarm_dynamic''');
-- Rapport -> alarmes top/worst
INSERT INTO sys_definition_context_dependency (sdcd_sdc_id, sdcd_sdc_id_dependency, sdcd_sdct_id, sdcd_column, sdcd_column_join, sdcd_where) VALUES (3, 9, 4 , 'id_elem', 'id_page', 'class_object=''alarm_top_worst''');


--16:54 10/04/2009 GHX
-- Suppression des tables inutiles
DROP TABLE sys_liste_file_dump;
DROP TABLE gis_trace;
DROP TABLE "key";
DROP TABLE menu_config;
DROP TABLE sys_config_adv_kpi;
DROP TABLE sys_config_client;
DROP TABLE sys_config_init;
DROP TABLE sys_configuration_infos;
DROP TABLE sys_definition_adv_kpi;
DROP TABLE sys_definition_database_lib;
DROP TABLE sys_definition_navigation_path;
DROP TABLE sys_definition_navigation_selecteur;
DROP TABLE sys_dump_action;
DROP TABLE sys_gis_config_miniature;
DROP TABLE sys_graph_online_mgt;
DROP TABLE sys_log;
DROP TABLE sys_page_encours;
DROP TABLE sys_parser_topology_rules;
DROP TABLE sys_ref_home_network_labels;
DROP TABLE sys_type_comment;
DROP TABLE sys_type_priority;
DROP TABLE sys_user;
DROP TABLE sys_user_parameter;
DROP TABLE sys_user_parameter_temp;
DROP TABLE topo_actualisee;
DROP TABLE user_parameters;


-- 17:11 14/04/2009 GHX
-- AJOUT DE LA COLONNE id_product
ALTER TABLE sys_pauto_config ADD COLUMN id_product int;

-- 15:31 20/04/2009 GHX
INSERT INTO sys_definition_context (sdc_id, sdc_label, sdc_column, sdc_default, sdc_visible, sdc_master, sdc_truncate, sdc_selected, sdc_sql_where_select_default, sdc_order_display) VALUES ('21', 'Parameters parser', NULL, 0, 0, 0, 1, 0, NULL, 21);

INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (38, 'sys_definition_flat_file_lib');
INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (39, 'sys_definition_group_table');
INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (40, 'sys_definition_group_table_network');
INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (41, 'sys_definition_group_table_time');
INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (42, 'sys_definition_gt_axe');
INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (43, 'sys_definition_categorie');

INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (21, 38, 0, 1);
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (21, 39, 0, 1);
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (21, 40, 0, 1);
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (21, 41, 0, 1);
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (21, 42, 0, 1);
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (21, 43, 0, 1);

--  20/04/2009 GHX
-- Ajout oubli !!!!
ALTER TABLE graph_information ADD COLUMN default_orderby text;
ALTER TABLE graph_information ADD COLUMN default_asc_desc integer;


-- 22/04/2009 - MPR : Modification de la fonction ri_calculation
CREATE OR REPLACE FUNCTION ri_calculation(text, text, text, text, text)
  RETURNS text AS
$BODY$
DECLARE
	min_network ALIAS FOR $1;
	tablename ALIAS FOR $2;
	net ALIAS FOR $3;
	net_value ALIAS FOR $4;
	arc_value ALIAS FOR $5;

	row RECORD;
	query text;
	condition_value text;
	condition_arc text;
	total integer;

BEGIN

	IF net!=min_network THEN
		condition_value:= ''''||net_value||'''';
		condition_arc:= ''''||arc_value||'''';
		IF condition_value!='' THEN 
			query := ' SELECT count(*) as number FROM edw_object_arc_ref WHERE eoar_id_parent='||condition_value||' AND  eoar_arc_type='||condition_arc;
			RAISE NOTICE 'query(%)', query;
			FOR row IN EXECUTE query LOOP 
				RETURN row.number;
			END LOOP;
		
		ELSE RETURN NULL;
		END IF;

	ELSE
	     RETURN 1;
	END IF;
END;
$BODY$
  LANGUAGE 'plpgsql' VOLATILE;
ALTER FUNCTION ri_calculation(text, text, text, text, text) OWNER TO postgres;


-- 22/04/2009 GHX
-- Ajout de la BH dans le param�trage contexte
INSERT INTO sys_definition_context (sdc_id, sdc_label, sdc_column, sdc_default, sdc_visible, sdc_master, sdc_truncate, sdc_selected, sdc_sql_where_select_default, sdc_order_display) VALUES ('22', 'Busy Hour', NULL, 0, 1, 0, 0, 0, NULL, 22);
INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (44, 'sys_definition_time_bh_formula');
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (22, 44, 0, 1);
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (59, 'family', 44, null);

-- 07/05/2009 SLC
-- passage de int en string de certaines colonnes de sys_contenu_buffer
ALTER TABLE sys_contenu_buffer ALTER COLUMN id_page TYPE text;

-- 11/05/2009 SPS
-- changement de l'url pour le menu Investigation Dashboard
UPDATE menu_deroulant_intranet SET lien_menu = '/dashboard_investigation/index.php' WHERE libelle_menu = 'Investigation Dashboard';


-- SCT 14:24 15/05/2009 Mise � jour des s�quences pour �viter les probl�mes en fin de fichier SQL
SELECT setval('public.sys_definition_group_table_network_id_ligne_seq', (SELECT max(id_ligne)+1 from sys_definition_group_table_network), true);
SELECT setval('public.sys_definition_group_table_time_id_ligne_seq', (SELECT max(id_ligne)+1 from sys_definition_group_table_time), true);
SELECT setval('public.sys_definition_step_step_id_seq', (SELECT max(step_id)+1 from sys_definition_step), true);

-- SLC 15/05/2009 Changement de nom du script qui envoie les rapports
update sys_definition_step set script='/scripts/report_sender.php' where script='/scripts/content_sender_v2.php';

-- SPS 29/05/2009 ajout d'un sys_debug pour dashboard investigation
INSERT INTO sys_debug (parameters,value,commentaire) VALUES('dashboard_investigation',1,'mode debug d''investigation dashboard');

-- 17:09 28/05/2009 GHX
-- Ajout de 2 colonnes dans sys_definition_categorie
ALTER TABLE sys_definition_categorie ADD COLUMN link_to_aa_3d_axis boolean DEFAULT false;
ALTER TABLE sys_definition_categorie ADD COLUMN separator TEXT;

-- 03/06/2009 BBX
-- Renommage de GTM Element Builder en GRAPH Element Builder
UPDATE menu_deroulant_intranet SET libelle_menu = 'GRAPH Element Builder'
WHERE libelle_menu = 'GTM Element Builder';

-- 03/06/2009 BBX
-- Modification du type de la colonne id_user dans la table track_users
ALTER TABLE track_users
ALTER COLUMN id_user TYPE text;

-- 10/06/2009 MPR - Gestion des �l�ments par d�fault du context ( B9889 )
UPDATE sys_definition_context SET sdc_default = 1 WHERE sdc_label in ('Users','Groups','Profiles');
UPDATE sys_definition_context SET sdc_default = 0 WHERE sdc_label = 'Link to Activity Analysis';

-- 15/06/2009 BBX id_user => int => text
ALTER TABLE report_builder_save
ALTER COLUMN id_user TYPE text;
--
ALTER TABLE forum_formula
ALTER COLUMN id_user TYPE text;
--
ALTER TABLE my_network_agregation
ALTER COLUMN id_user TYPE text;

-- 16/06/2009 - MPR : Mise � jour de la colonne with_header ( valeur par d�fault � 1)
-- On passe � 1 la colonne with_header --
UPDATE sys_export_raw_kpi_config SET with_header = 1;

-- Ajout de la valeur par d�fault --
ALTER TABLE sys_export_raw_kpi_config ALTER COLUMN with_header SET DEFAULT 1;

-- Ajout de la colonne add_topo_file dans sys_export_raw_kpi_config
ALTER TABLE sys_export_raw_kpi_config ADD COLUMN add_topo_file smallint DEFAULT 0;

-- Ajout de la colonne add_raw_kpi_file dans sys_export_raw_kpi_config
ALTER TABLE sys_export_raw_kpi_config ADD COLUMN add_raw_kpi_file smallint DEFAULT 0;

-- 08:53 18/06/2009 GHX
-- Modification dans le param�trage contexte pour corriger le BZ 9848
INSERT INTO sys_definition_context_dependency (sdcd_sdc_id,sdcd_sdc_id_dependency,sdcd_sdct_id,sdcd_column,sdcd_column_join,sdcd_where) VALUES (1,10,4,'id_elem','id_page','class_object=''kpi''');
INSERT INTO sys_definition_context_dependency (sdcd_sdc_id,sdcd_sdc_id_dependency,sdcd_sdct_id,sdcd_column,sdcd_column_join,sdcd_where) VALUES (1,11,4,'id_elem','id_page','class_object=''counter''');
DELETE FROM sys_definition_context_table_link WHERE sdctl_sdc_id = 1 AND sdctl_sdct_id = 1 AND sdctl_select_all = 1 AND  sdctl_order = 5;
DELETE FROM sys_definition_context_table_link WHERE sdctl_sdc_id = 1 AND sdctl_sdct_id = 2 AND sdctl_select_all = 1 AND  sdctl_order = 6;

-- 18/06/2009 BBX content_id => int => text
ALTER TABLE sys_content_buffer
ALTER COLUMN content_id TYPE text;

-- 18/06/09 CCT1 : ajout du drop des anciennes tables.
DROP TABLE IF EXISTS gis_trace;
DROP TABLE IF EXISTS 'key';
DROP TABLE IF EXISTS menu_config;
DROP TABLE IF EXISTS sys_config_adv_kpi;
DROP TABLE IF EXISTS sys_config_client;
DROP TABLE IF EXISTS sys_config_init;
DROP TABLE IF EXISTS sys_configuration_infos;
DROP TABLE IF EXISTS sys_context_advance;
DROP TABLE IF EXISTS sys_context_advance_management;
DROP TABLE IF EXISTS sys_definition_adv_kpi;
DROP TABLE IF EXISTS sys_definition_database_lib;
DROP TABLE IF EXISTS sys_definition_navigation_path;
DROP TABLE IF EXISTS sys_definition_navigation_selecteur;
DROP TABLE IF EXISTS sys_dump_action;
DROP TABLE IF EXISTS sys_extraction_cb;
DROP TABLE IF EXISTS sys_extraction_parser;
DROP TABLE IF EXISTS sys_gis_config_miniature;
DROP TABLE IF EXISTS sys_graph_online_mgt;
DROP TABLE IF EXISTS sys_list_file_dump;
DROP TABLE IF EXISTS sys_log;
DROP TABLE IF EXISTS sys_page_en_cours;
DROP TABLE IF EXISTS sys_parser_install;
DROP TABLE IF EXISTS sys_parser_topology_rules;
DROP TABLE IF EXISTS sys_pauto_interface_config;
DROP TABLE IF EXISTS sys_ref_home_network_labels;
DROP TABLE IF EXISTS sys_tables_to_dump;
DROP TABLE IF EXISTS sys_type_comment;
DROP TABLE IF EXISTS sys_type_priority;
DROP TABLE IF EXISTS sys_user;
DROP TABLE IF EXISTS sys_user_parameter;
DROP TABLE IF EXISTS sys_user_parameter_temp;
DROP TABLE IF EXISTS topo_actualisee;
DROP TABLE IF EXISTS user_parameters;

-- 19/06/2009 BBX :
-- Modification des liens menu contextuel des alarmes pour g�rer le produit
UPDATE menu_contextuel
SET url_action = 'javascript:navigate(''alarm_index.php?mode_alarme=management&sous_mode=elem_reseau&id_menu_encours=$page_encours&product=$product'')'
WHERE nom_action ILIKE '%By Network Element%';
---
UPDATE menu_contextuel
SET url_action = 'javascript:navigate(''alarm_index.php?mode_alarme=management&sous_mode=condense&id_menu_encours=$page_encours&product=$product'')'
WHERE nom_action ILIKE '%By Alarm%';
-- Modification des liens menu contextuel des alarmes
UPDATE menu_contextuel
SET url_action = 'javascript:open_window(''alarm_to_export.php?file_type=doc&mode=$mode_alarme&sous_mode=$sous_mode_alarme'',''nouvellepage'',''yes'',''yes'',300,30)'
WHERE id = 104;
--
UPDATE menu_contextuel
SET url_action = 'javascript:open_window(''alarm_to_export.php?file_type=pdf&mode=$mode_alarme&sous_mode=$sous_mode_alarme'',''nouvellepage'',''yes'',''yes'',300,30)'
WHERE id = 102;
--
UPDATE menu_contextuel
SET url_action = 'javascript:open_window(''alarm_to_export.php?file_type=xls&mode=$mode_alarme&sous_mode=$sous_mode_alarme'',''nouvellepage'',''yes'',''yes'',300,30)'
WHERE id = 103;


-- 09:54 22/06/2009 GHX
UPDATE sys_global_parameters SET value = '200' WHERE parameters = 'dashboard_display_max_nb_queries_overtime';

-- 15:19 22/06/2009 GHX
-- Ajout de la table sys_definition_group_table_ref dans le param�trage Parser du contexte
INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (45, 'sys_definition_group_table_ref');
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (21,45,0,1);

-- 17:22 08/07/2009 SCT
-- Modification de l'entr�e sys_aa_interface pour le montage du contexte GPRS
UPDATE sys_aa_interface SET saai_interface = 8, saai_module = 'gb' WHERE saai_interface = 128 AND saai_module = 'gprs';

-- 10:57 09/07/2009 GHX
-- Correction du BZ 10483 [REC][T&A CB 5.0][ACTIVATION] : Les "Report Schedule" des Slaves ne sont pas remont�s sur le master
-- Ajout la possibilit� d'ajouter les Reports Schedules dans les contextes
-- 11:33 10/07/2009 GHX
-- Modif du param�trage
INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (46, 'sys_report_schedule');
INSERT INTO sys_definition_context_table (sdct_id, sdct_table) VALUES (47, 'sys_report_sendmail');
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (60, 'schedule_id', 46, null);
INSERT INTO sys_definition_context_table_key (sdctk_id, sdctk_column, sdctk_sdct_id, sdctk_sdctk_id) VALUES (61, 'schedule_id', 47, 60);
INSERT INTO sys_definition_context (sdc_id, sdc_label, sdc_column, sdc_default, sdc_visible, sdc_master, sdc_truncate, sdc_selected, sdc_sql_where_select_default, sdc_order_display) VALUES (23, 'Report Schedule', null, 0, 0, 0, 0, 0, null, 23);
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (23, 46, 1, 1);
INSERT INTO sys_definition_context_table_link (sdctl_sdc_id, sdctl_sdct_id, sdctl_select_all, sdctl_order) VALUES (23, 47, 1, 2);


-- 11:27 09/07/2009 SCT : ajout d'un mode debug pour la collecte des fichiers pendant le retrieve
DELETE FROM sys_debug WHERE parameters = 'retrieve_collect_data';
INSERT INTO sys_debug (parameters, value, commentaire) VALUES ('retrieve_collect_data', 0, 'mode de debug de la collecte des fichiers pendant le retrieve [0 d�sactiv�, 1 activ�]');
DELETE FROM sys_debug WHERE parameters = 'retrieve_load_data';
INSERT INTO sys_debug (parameters, value, commentaire) VALUES ('retrieve_load_data', 0, 'mode de debug du chargement des fichiers pendant le retrieve [0 d�sactiv�, 1 activ�]');
DELETE FROM sys_debug WHERE parameters = 'retrieve_copy_from_temp_data';
INSERT INTO sys_debug (parameters, value, commentaire) VALUES ('retrieve_copy_from_temp_data', 0, 'mode de debug du chargement des tables temporaires pendant le retrieve [0 d�sactiv�, 1 activ�]');

-- 14:28 10/07/2009 MPR : Correction du bug 10556 
-- Ajout du menu contextuel dans Query Builder
-- Requ�te du Contexte 
UPDATE menu_contextuel SET type_pauto = 'query_builder' WHERE nom_action IN ('New Aggregation','New Formula');

-- 15:27 17/07/2009 
-- Ajout d'un mode d�bug pour le calcule des alarmes
DELETE FROM sys_debug WHERE parameters = 'alarm_calculation';
INSERT INTO sys_debug (parameters, value, commentaire) VALUES ('alarm_calculation', 0 , 'Mode de d�bug du calcule des alarmes (0 : d�sactiv� / 1 : activ�');

-- 15:35 04/08/2009 GHX
-- Correction du BZ 10640
UPDATE menu_contextuel SET url_action = 'javascript:navigate(''alarm_index.php?mode_alarme=management&sous_mode=elem_reseau&id_menu_encours=$page_encours&product=$product&nel_selecteur=$nel_selecteur&order_on=$order_on'')' WHERE id = 100;
UPDATE menu_contextuel SET url_action = 'javascript:navigate(''alarm_index.php?mode_alarme=management&sous_mode=condense&id_menu_encours=$page_encours&product=$product&nel_selecteur=$nel_selecteur&order_on=$order_on'')' WHERE id= 101;

---
-- DATA EXPORT
---
-- 09:23 17/07/2009 BBX : DATA EXPORT
-- Ajout des colonnes "visible", "add_prefix" et "id_product" � la table sys_export_raw_kpi_config 
ALTER TABLE sys_export_raw_kpi_config
ADD COLUMN visible int default 1;
ALTER TABLE sys_export_raw_kpi_config
ADD COLUMN add_prefix int default 0;
ALTER TABLE sys_export_raw_kpi_config
ADD COLUMN id_product int default 0;
ALTER TABLE sys_export_raw_kpi_config
ADD COLUMN use_codeq int default 0;
-- 12/08/2009 BBX
-- UPDATE DES STEPS
UPDATE sys_definition_step
SET script = '/scripts/data_export.php'
WHERE script = '/scripts/collect_export_data.php';
-- Update du lien menu
UPDATE menu_deroulant_intranet 
SET lien_menu = '/data_export/index.php'
WHERE libelle_menu = 'Data Export';

-- Midification des valeurs time period et offset par d�faut. BZ 11094
-- sys_definition_master
TRUNCATE sys_definition_master;
INSERT INTO sys_definition_master (master_id, master_name, utps, offset_time, on_off, auto, visible, ordre, commentaire) VALUES (13, 'Compute Hourly', 30, 15, 0, false, 0, 5, 'ne pas parametrer laisser on_off a 0, lanc� par id master 12');
INSERT INTO sys_definition_master (master_id, master_name, utps, offset_time, on_off, auto, visible, ordre, commentaire) VALUES (4, 'Compute', 1440, 780, 0, false, 0, 3, 'ne pas parametrer laisser on_off a 0, lanc� par id master 11');
INSERT INTO sys_definition_master (master_id, master_name, utps, offset_time, on_off, auto, visible, ordre, commentaire) VALUES (10, 'Retrieve', 12, 1, 0, false, 1, 1, NULL);
INSERT INTO sys_definition_master (master_id, master_name, utps, offset_time, on_off, auto, visible, ordre, commentaire) VALUES (11, 'Compute Launcher', 6, 3, 0, false, 1, 2, NULL);
INSERT INTO sys_definition_master (master_id, master_name, utps, offset_time, on_off, auto, visible, ordre, commentaire) VALUES (12, 'Compute Launcher Hourly', 3, 2, 0, false, 1, 4, NULL);
-- sys_definition_master_ref
TRUNCATE sys_definition_master_ref;
INSERT INTO sys_definition_master_ref (master_id, master_name, utps, offset_time, on_off, auto, visible, ordre, commentaire) VALUES (13, 'Compute Hourly', 30, 15, 0, false, 0, 5, 'ne pas parametrer laisser on_off a 0, lanc� par id master 12');
INSERT INTO sys_definition_master_ref (master_id, master_name, utps, offset_time, on_off, auto, visible, ordre, commentaire) VALUES (4, 'Compute', 1440, 780, 0, false, 0, 3, 'ne pas parametrer laisser on_off a 0, lanc� par id master 11');
INSERT INTO sys_definition_master_ref (master_id, master_name, utps, offset_time, on_off, auto, visible, ordre, commentaire) VALUES (10, 'Retrieve', 12, 1, 0, false, 1, 1, NULL);
INSERT INTO sys_definition_master_ref (master_id, master_name, utps, offset_time, on_off, auto, visible, ordre, commentaire) VALUES (11, 'Compute Launcher', 6, 3, 0, false, 1, 2, NULL);
INSERT INTO sys_definition_master_ref (master_id, master_name, utps, offset_time, on_off, auto, visible, ordre, commentaire) VALUES (12, 'Compute Launcher Hourly', 3, 2, 0, false, 1, 4, NULL);

-- ajout CCT1 le 20/08/09
-- correction du bug 10322 et accompagnement de la correction des bugs contextes BZ 11155, 11156, 11157
-- correction du probleme de la table geometry_columns lors de smigration la table n etait pas toujours presente d ou une erreur lors du montage contexte.
CREATE TABLE geometry_columns
(
  f_table_catalog character varying(256) NOT NULL,
  f_table_schema character varying(256) NOT NULL,
  f_table_name character varying(256) NOT NULL,
  f_geometry_column character varying(256) NOT NULL,
  coord_dimension integer NOT NULL,
  srid integer NOT NULL,
  "type" character varying(30) NOT NULL,
  CONSTRAINT geometry_columns_pk PRIMARY KEY (f_table_catalog, f_table_schema, f_table_name, f_geometry_column)
)
WITH (OIDS=TRUE);

-- 16:30 21/08/2009 GHX
-- On doit pouvoir uploader une topo avec les colonnes x et y
-- 14:52 01/09/2009 MPR 
-- On ne doit pas pouvoir uploader une topo avec les colonnes x et y
/*
DELETE FROM edw_object_ref_header WHERE eorh_id_column_db = 'x' AND eorh_id_column_file = 'longitude_or_x' AND eorh_id_produit = 'omc';
INSERT INTO edw_object_ref_header (eorh_id_column_db, eorh_id_column_file, eorh_id_produit) VALUES ('x', 'longitude_or_x', 'omc');
DELETE FROM edw_object_ref_header WHERE eorh_id_column_db = 'y' AND eorh_id_column_file = 'latitude_or_y' AND eorh_id_produit = 'omc';
INSERT INTO edw_object_ref_header (eorh_id_column_db, eorh_id_column_file, eorh_id_produit) VALUES ('y', 'latitude_or_y', 'omc');

DELETE FROM edw_object_ref_header WHERE eorh_id_column_db = 'x' AND eorh_id_column_file = 'longitude_or_x' AND eorh_id_produit = 'all';
INSERT INTO edw_object_ref_header (eorh_id_column_db, eorh_id_column_file, eorh_id_produit) VALUES ('x', 'longitude_or_x', 'all');
DELETE FROM edw_object_ref_header WHERE eorh_id_column_db = 'y' AND eorh_id_column_file = 'latitude_or_y' AND eorh_id_produit = 'all';
INSERT INTO edw_object_ref_header (eorh_id_column_db, eorh_id_column_file, eorh_id_produit) VALUES ('y', 'latitude_or_y', 'all');

DELETE FROM edw_object_ref_header WHERE eorh_id_column_db = 'x' AND eorh_id_column_file = 'longitude_or_x' AND eorh_id_produit = 'gb';
INSERT INTO edw_object_ref_header (eorh_id_column_db, eorh_id_column_file, eorh_id_produit) VALUES ('x', 'longitude_or_x', 'gb');
DELETE FROM edw_object_ref_header WHERE eorh_id_column_db = 'y' AND eorh_id_column_file = 'latitude_or_y' AND eorh_id_produit = 'gb';
INSERT INTO edw_object_ref_header (eorh_id_column_db, eorh_id_column_file, eorh_id_produit) VALUES ('y', 'latitude_or_y', 'gb');

DELETE FROM edw_object_ref_header WHERE eorh_id_column_db = 'x' AND eorh_id_column_file = 'longitude_or_x' AND eorh_id_produit = 'gsm';
INSERT INTO edw_object_ref_header (eorh_id_column_db, eorh_id_column_file, eorh_id_produit) VALUES ('x', 'longitude_or_x', 'gsm');
DELETE FROM edw_object_ref_header WHERE eorh_id_column_db = 'y' AND eorh_id_column_file = 'latitude_or_y' AND eorh_id_produit = 'gsm';
INSERT INTO edw_object_ref_header (eorh_id_column_db, eorh_id_column_file, eorh_id_produit) VALUES ('y', 'latitude_or_y', 'gsm');

DELETE FROM edw_object_ref_header WHERE eorh_id_column_db = 'x' AND eorh_id_column_file = 'longitude_or_x' AND eorh_id_produit = 'iu';
INSERT INTO edw_object_ref_header (eorh_id_column_db, eorh_id_column_file, eorh_id_produit) VALUES ('x', 'longitude_or_x', 'iu');
DELETE FROM edw_object_ref_header WHERE eorh_id_column_db = 'y' AND eorh_id_column_file = 'latitude_or_y' AND eorh_id_produit = 'iu';
INSERT INTO edw_object_ref_header (eorh_id_column_db, eorh_id_column_file, eorh_id_produit) VALUES ('y', 'latitude_or_y', 'iu');

DELETE FROM edw_object_ref_header WHERE eorh_id_column_db = 'x' AND eorh_id_column_file = 'longitude_or_x' AND eorh_id_produit = 'iub';
INSERT INTO edw_object_ref_header (eorh_id_column_db, eorh_id_column_file, eorh_id_produit) VALUES ('x', 'longitude_or_x', 'iub');
DELETE FROM edw_object_ref_header WHERE eorh_id_column_db = 'y' AND eorh_id_column_file = 'latitude_or_y' AND eorh_id_produit = 'iub';
INSERT INTO edw_object_ref_header (eorh_id_column_db, eorh_id_column_file, eorh_id_produit) VALUES ('y', 'latitude_or_y', 'iub');
*/

-- 09:24 31/08/2009 GHX
-- Correction du BZ 11270
-- On met certains �l�ments non visibles sur l'IHM
UPDATE sys_definition_context SET sdc_visible = 0, sdc_default = 0 WHERE sdc_id IN (
	'4', -- users
	'5', -- groups
	'6', -- profiles
	'12', -- Link to Activity Analysis
	'15', -- Network Aggregation
	'16', -- Time Aggregation
	'17', -- GIS 
	'18', -- Global parameters
	'20' -- Menu
);  
-- On change le label de Data Export (Export Data devient Data Export)
UPDATE sys_definition_context SET sdc_label = 'Data Export' WHERE sdc_id = 19;

-- 16:55 14/09/2009 GHX
-- Ajout d'une colonne pour l'utilisation du prefix dans le cas d'un corporate
ALTER TABLE sys_definition_network_agregation ADD COLUMN use_prefix smallint;

-- 18/09/2009 BBX : mise � jour des URLS de g�n�ration de doc des alarmes. BZ 11632
-- Export Word
UPDATE menu_contextuel 
SET url_action = 'javascript:open_window(''alarm_to_export.php?file_type=doc&mode=$mode_alarme&sous_mode=$sous_mode_alarme&product=$product'',''nouvellepage'',''yes'',''yes'',300,30)'
WHERE id = 104;
-- Export Excel
UPDATE menu_contextuel 
SET url_action = 'javascript:open_window(''alarm_to_export.php?file_type=xls&mode=$mode_alarme&sous_mode=$sous_mode_alarme&product=$product'',''nouvellepage'',''yes'',''yes'',300,30)'
WHERE id = 103;
-- Export PDF
UPDATE menu_contextuel 
SET url_action = 'javascript:open_window(''alarm_to_export.php?file_type=pdf&mode=$mode_alarme&sous_mode=$sous_mode_alarme&product=$product'',''nouvellepage'',''yes'',''yes'',300,30)'
WHERE id = 102;

-- 07/10/2009 BBX : Ajout du code produit "def"
DELETE FROM sys_aa_interface
WHERE saai_module = 'def';
--
INSERT INTO sys_aa_interface
(saai_interface,saai_module)
VALUES (1,'def');

