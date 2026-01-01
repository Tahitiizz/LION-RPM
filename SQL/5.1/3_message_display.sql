--
DELETE FROM sys_definition_messages_display WHERE id = 'U_TOOLTIP_OPEN_DATA_TABLE';
INSERT INTO sys_definition_messages_display VALUES ('U_TOOLTIP_OPEN_DATA_TABLE', 'Open data table');
--
DELETE FROM sys_definition_messages_display WHERE id = 'U_TOOLTIP_CLOSE_DATA_TABLE';
INSERT INTO sys_definition_messages_display VALUES ('U_TOOLTIP_CLOSE_DATA_TABLE', 'Close data table');
--
DELETE FROM sys_definition_messages_display WHERE id = 'U_E_GTM_DATA_TABLE_ERROR';
INSERT INTO sys_definition_messages_display VALUES ('U_E_GTM_DATA_TABLE_ERROR', 'Data could not be retrieved, please refresh the page');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_DOWNLOAD_LOG_TOPOLOGY_STATS_LABEL';
DELETE FROM sys_definition_messages_display WHERE id = 'A_DOWNLOAD_LOG_TOPOLOGY_STATS_COMMENT';
DELETE FROM sys_definition_messages_display WHERE id = 'A_DOWNLOAD_LOG_PARTITION_STATS_LABEL';
DELETE FROM sys_definition_messages_display WHERE id = 'A_DOWNLOAD_LOG_PARTITION_STATS_COMMENT';
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_DOWNLOAD_LOG_HEALTH_INDICATOR_LABEL';
INSERT INTO sys_definition_messages_display VALUES ('A_DOWNLOAD_LOG_HEALTH_INDICATOR_LABEL', 'Health Indicators');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_DOWNLOAD_LOG_HEALTH_INDICATOR_COMMENT';
INSERT INTO sys_definition_messages_display VALUES ('A_DOWNLOAD_LOG_HEALTH_INDICATOR_COMMENT', 'Adds health indicators file to the archive');
--
-- 25/05/2010 BBX
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_ALARM_DOES_NOT_EXIST';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_ALARM_DOES_NOT_EXIST', 'Alarm does not exist');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_ALARM_AUTOMATIC_UNSELECTION';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_ALARM_AUTOMATIC_UNSELECTION', '$1 $2 automatically unselected because some of $3 child elements have been unchecked');
--
DELETE FROM sys_definition_messages_display WHERE id = 'U_ALARM_NO_ELEMENT_SELECTED';
INSERT INTO sys_definition_messages_display VALUES ('U_ALARM_NO_ELEMENT_SELECTED', 'No element selected');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_ALARM_FILTERING_DISABLED';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_ALARM_FILTERING_DISABLED', 'Filtering disabled : topology is empty');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_ALARM_SELECT_NETWORK_LEVEL';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_ALARM_SELECT_NETWORK_LEVEL', 'Please select a network level');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_ALARM_SELECT_TIME_RES';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_ALARM_SELECT_TIME_RES', 'Please select a time resolution');

-- 29/06/2010 OJT : Correction bz11698
DELETE FROM sys_definition_messages_display WHERE id = 'G_PAUTO_GTM';
DELETE FROM sys_definition_messages_display WHERE id = 'G_PAUTO_GTM_PROPERTIES_LABEL';
DELETE FROM sys_definition_messages_display WHERE id = 'G_PAUTO_GTM_INFORMATION_LABEL';
DELETE FROM sys_definition_messages_display WHERE id = 'G_PAUTO_DASHBOARD_FORM_DASH_MODE_OPTIONS_CHOIX_ERROR';
DELETE FROM sys_definition_messages_display WHERE id = 'A_MAPPING_COUNTERS_COUNTER_USED_IN_GTM_1';
DELETE FROM sys_definition_messages_display WHERE id = 'A_MAPPING_COUNTERS_COUNTER_USED_IN_GTM_2';
DELETE FROM sys_definition_messages_display WHERE id = 'G_JS_DRAWLINE_UPDATING_GTM';
INSERT INTO sys_definition_messages_display VALUES ('G_PAUTO_GTM', 'Graphs' );
INSERT INTO sys_definition_messages_display VALUES ('G_PAUTO_GTM_PROPERTIES_LABEL', 'Graph properties' );
INSERT INTO sys_definition_messages_display VALUES ('G_PAUTO_GTM_INFORMATION_LABEL', 'Graph information' );
INSERT INTO sys_definition_messages_display VALUES ('G_PAUTO_DASHBOARD_FORM_DASH_MODE_OPTIONS_CHOIX_ERROR', 'No data found to configure Overtime mode sort. (No Graph inside your dashboard)' );
INSERT INTO sys_definition_messages_display VALUES ('A_MAPPING_COUNTERS_COUNTER_USED_IN_GTM_1', 'Counter "$1" ($2) is used in a Graph. It can not be deactivated' );
INSERT INTO sys_definition_messages_display VALUES ('A_MAPPING_COUNTERS_COUNTER_USED_IN_GTM_2', 'Counter "$1" is used in a Graph. It can not be deactivated' );
INSERT INTO sys_definition_messages_display VALUES ('G_JS_DRAWLINE_UPDATING_GTM', 'Updating Graph...' );

-- 30/06/2010 OJT : Correction bz13897
DELETE FROM sys_definition_messages_display WHERE id = 'A_JS_USER_EDIT_FIRSTNAME_NOT_VALID';
DELETE FROM sys_definition_messages_display WHERE id = 'A_JS_USER_EDIT_LASTNAME_NOT_VALID';
DELETE FROM sys_definition_messages_display WHERE id = 'A_JS_USER_EDIT_LOGIN_NOT_VALID';
INSERT INTO sys_definition_messages_display VALUES ('A_JS_USER_EDIT_FIRSTNAME_NOT_VALID', 'Please enter a valid firstname (only alphanumeric characters, " ", "." and "-" are allowed, minLength:2)' );
INSERT INTO sys_definition_messages_display VALUES ('A_JS_USER_EDIT_LASTNAME_NOT_VALID', 'Please enter a valid lastname (only alphanumeric characters, " ", "." and "-" are allowed, minLength:2)' );
INSERT INTO sys_definition_messages_display VALUES ('A_JS_USER_EDIT_LOGIN_NOT_VALID', 'Please enter a valid login (only alphanumeric characters, " ", "." and "-" are allowed, minLength:2)' );

-- 01/07/2010 OJT : Correction bz16310
DELETE FROM sys_definition_messages_display WHERE id = 'A_USER_USER_MANAGEMENT_LABEL_LOGIN';
INSERT INTO sys_definition_messages_display VALUES ('A_USER_USER_MANAGEMENT_LABEL_LOGIN', 'Login');

--
-- 09/06/2010 BBX
-- Correction messages alarmes : l'alarme n'est pas forcemment statique
DELETE FROM sys_definition_messages_display WHERE id = 'A_ALARM_CALCULATION_MSG_TRACELOG_ALARM_NOT_INSERTED';
INSERT INTO sys_definition_messages_display VALUES ('A_ALARM_CALCULATION_MSG_TRACELOG_ALARM_NOT_INSERTED', '>> $1 alarm $2 ($3) not inserted : $4 results for $5 level/$6=$7 <<');

-- 10/06/2010 NSE : merge Single KPI
DELETE FROM sys_definition_messages_display WHERE id = 'G_GDR_BUILDER_SINGLE_KPI';
INSERT INTO sys_definition_messages_display VALUES ('G_GDR_BUILDER_SINGLE_KPI', 'SingleKPI');

-- 05/07/2010 OJT : Correction bz13897
DELETE FROM sys_definition_messages_display WHERE id = 'A_JS_USER_EDIT_PASSWORD_NOT_VALID';
INSERT INTO sys_definition_messages_display VALUES ('A_JS_USER_EDIT_PASSWORD_NOT_VALID', E'Please enter a valid password (only alphanumeric characters and "\\$,@,],[,),(,/,#,|,!,%,-,_" are allowed, minLength:6, maxLength:64)' );

--
-- 05/07/2010 BBX
-- Correction du message quand fichier de taille 0. BZ 15505
DELETE FROM sys_definition_messages_display WHERE id = 'A_FLAT_FILE_UPLOAD_ALARM_FILE_NOT_UPLOADED';
INSERT INTO sys_definition_messages_display VALUES ('A_FLAT_FILE_UPLOAD_ALARM_FILE_NOT_UPLOADED', 'Empty file : $1');

--
-- 05/07/2010 BBX
-- Ajout des messages pour interdire upload topo si retrieve. BZ 12974
DELETE FROM sys_definition_messages_display WHERE id = 'A_UPLOAD_TOPO_FORBIDDEN_RETRIEVE_COMPUTE';
INSERT INTO sys_definition_messages_display VALUES ('A_UPLOAD_TOPO_FORBIDDEN_RETRIEVE_COMPUTE', 'Topology should not be manually updated between "Retrieve" and "Compute" processes. Please wait for the end of the upcoming "Compute" and retry.');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_UPLOAD_TOPO_FORBIDDEN_PROCESS_RUNNING';
INSERT INTO sys_definition_messages_display VALUES ('A_UPLOAD_TOPO_FORBIDDEN_PROCESS_RUNNING', 'Topology should not be manually updated while processes are in progress.<br />Please unckeck all processes (Task Scheduler > Process), save, and wait for running processes to end before updating.');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_UPLOAD_TOPO_FORBIDDEN_CONTINUE_ANYWAY';
INSERT INTO sys_definition_messages_display VALUES ('A_UPLOAD_TOPO_FORBIDDEN_CONTINUE_ANYWAY', 'Continue anyway ? You might loose some data.');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_COMMOM_MESSAGE_YES';
INSERT INTO sys_definition_messages_display VALUES ('A_COMMOM_MESSAGE_YES', 'Yes');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_COMMOM_MESSAGE_NO';
INSERT INTO sys_definition_messages_display VALUES ('A_COMMOM_MESSAGE_NO', 'No');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_UPLOAD_TOPO_UPDATE_FORCED';
INSERT INTO sys_definition_messages_display VALUES ('A_UPLOAD_TOPO_UPDATE_FORCED', 'Topology manually updated ignoring displayed warnings on product "$1".');

-- 23/07/2010 OJT : Correction bz16636
DELETE FROM sys_definition_messages_display WHERE id = 'A_JS_CONTEXT_BUILD_SURE_TO_RESTORE';
INSERT INTO sys_definition_messages_display VALUES ('A_JS_CONTEXT_BUILD_SURE_TO_RESTORE', E'*** WARNING ***\\n\\nReinitialize option will restore T&A in the state it was before mounting the context [$1]\\n\\nBe careful : all modifications done in the context coverage (graphs,\\ndashboards, counters, kpis...) and after that [$2]\\n was mounted WILL BE LOST !\\nYou should reinitialize only if there are no other options.\\n\\nAre you sure you want to restore T&A as it was before mounting the file\\n [$3] ?');

-- 23/07/2010 OJT : Correction bz16812
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_UPLOAD_TOPOLOGY_HEADER_MUTLI_SAMES_COLUMNS_2';
INSERT INTO sys_definition_messages_display VALUES ('A_E_UPLOAD_TOPOLOGY_HEADER_MUTLI_SAMES_COLUMNS_2', 'Error, topology file contains duplicate columns for "$1"' );

-- 27/07/2010 OJT : Correction bz16933
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCTS_DIRECTORY_MUST_BE_TA';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCTS_DIRECTORY_MUST_BE_TA', 'The directory must be a T&A software' );

-- 27/07/2010 OJT : Correction bz16817
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_CONTEXT_MOUNT_UNKOWN_EXTENSION';
INSERT INTO sys_definition_messages_display VALUES ('A_E_CONTEXT_MOUNT_UNKOWN_EXTENSION', 'Unknown extension for file $1' );
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_CONTEXT_MOUNT_SUBCONTEXT_UNKOWN_EXTENSION';
INSERT INTO sys_definition_messages_display VALUES ('A_E_CONTEXT_MOUNT_SUBCONTEXT_UNKOWN_EXTENSION', 'Unknown extension for subcontext $1' );

-- 29/07/2010 OJT : Correction bz15263
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCT_DELETE_PRODUCT_OK';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCT_DELETE_PRODUCT_OK', 'The product $1 has been successfully deleted.<br />Please note that user accounts from $2 product have not been removed from master product.' );

--
-- 30/07/2010 BBX
-- Ajout du texte d'aide. BZ 17023
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_ALARM_HELP_BOX';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_ALARM_HELP_BOX', 'Alarm will be triggered for $1. Click the filter button to modify the $2 list');

-- 20/09/2010 NSE bz 17820
DELETE FROM sys_definition_messages_display WHERE id = 'G_GDR_BUILDER_EDIT_REPORT_PROPERTIES';
INSERT INTO sys_definition_messages_display VALUES ('G_GDR_BUILDER_EDIT_REPORT_PROPERTIES', 'Edit report properties');

-- 22/09/2010 OJT bz 16835
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_SETUP_PRODUCTS_BOTH_SSH_PARAM_MUST_EXIST';
INSERT INTO sys_definition_messages_display VALUES ('A_E_SETUP_PRODUCTS_BOTH_SSH_PARAM_MUST_EXIST', 'Both SSH parameters must be defined');

-- 22/09/2010 BBX BZ 14516
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_UPLOAD_TOPO_NA_MIN_IS_NULL';
INSERT INTO sys_definition_messages_display VALUES ('A_E_UPLOAD_TOPO_NA_MIN_IS_NULL', 'NULL identifier found for level $1 on line $2');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_UPLOAD_TOPO_LABEL_NOT_ONE_NA';
INSERT INTO sys_definition_messages_display VALUES ('A_E_UPLOAD_TOPO_LABEL_NOT_ONE_NA', 'Same label "$1" found for $2 $3(s) [$4]');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_UPLOAD_TOPO_NA_NULL_LABEL_NOT_NULL';
INSERT INTO sys_definition_messages_display VALUES ('A_E_UPLOAD_TOPO_NA_NULL_LABEL_NOT_NULL', 'NULL identifier found for level $1 with label </i>"$2"</i>');
-- Fin BZ 14516

-- 13/10/2010 OJT : Correction bz18461
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_DATA_FILES_UPDATE_SUCCESS';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_DATA_FILES_UPDATE_SUCCESS', 'Only new connections will take into account the new settings.However, existing connections will not be modified. In order to modify existing connections, please use Setup Connections menu item.');

-- 16:36 13/10/2010 SCT : BZ 18427 => Désactivation de compteur utilisé pour la BH possible
DELETE FROM sys_definition_messages_display WHERE id = 'A_MAPPING_COUNTERS_ACTIVATION_COUNTER_DEACTIVATION_DISABLED_5';
INSERT INTO sys_definition_messages_display VALUES ('A_MAPPING_COUNTERS_ACTIVATION_COUNTER_DEACTIVATION_DISABLED_5', 'Counter "$1" is set as Busy Hour counter. It cannot be desactivated');
DELETE FROM sys_definition_messages_display WHERE id = 'A_COMPUTE_MSG_ERROR_BH_FORMULA_RAW_KPI_OFF';
INSERT INTO sys_definition_messages_display VALUES ('A_COMPUTE_MSG_ERROR_BH_FORMULA_RAW_KPI_OFF', '$2 "$3" is set as Busy Hour $2 for family $1 but has been desactivated.');
DELETE FROM sys_definition_messages_display WHERE id = 'A_COMPUTE_MSG_ERROR_BH_FORMULA_RAW_KPI_DEL';
INSERT INTO sys_definition_messages_display VALUES ('A_COMPUTE_MSG_ERROR_BH_FORMULA_RAW_KPI_DEL', '$2 "$3" is set as Busy Hour $2 for family $1 but has been deleted.');

-- 13:40 18/10/2010 SCT : BZ 18518 => Suppression impossible de Kpi => Busy Hour
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_KPI_BUILDER_ERROR_BH_KPI_DEL';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_KPI_BUILDER_ERROR_BH_KPI_DEL', 'Kpi "$1" is set as Busy Hour. It cannot be deleted.');

-- !!!! LAISSER A LA FIN DU FICHIER !!!!
VACUUM ANALYSE sys_definition_messages_display;
