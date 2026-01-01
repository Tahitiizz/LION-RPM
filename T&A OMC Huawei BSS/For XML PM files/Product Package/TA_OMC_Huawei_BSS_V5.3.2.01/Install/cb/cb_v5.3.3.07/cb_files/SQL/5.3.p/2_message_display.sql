
-- Exemple d'utilisation pour l'ajout/la modification d'un message display
-- DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_KPI_BUILDER_ERROR_BH_KPI_DEL';
-- INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_KPI_BUILDER_ERROR_BH_KPI_DEL', 'Kpi "$1" is set as Busy Hour. It cannot be deleted.');

-- 11/04/2013 NSE SNMP Trap community
DELETE FROM sys_definition_messages_display WHERE id = 'A_SNMP_SEND';
INSERT INTO sys_definition_messages_display VALUES ('A_SNMP_SEND', '$1 SNMP traps send.');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SNMP_SEND_ERROR';
INSERT INTO sys_definition_messages_display VALUES ('A_SNMP_SEND_ERROR', '$1 errors occurred while sending $2 SNMP traps.');

-- 15/04/2013 NSE Phone number managed by Portal
DELETE FROM sys_definition_messages_display WHERE id = 'A_USER_USER_MANAGEMENT_FULLY_LIST_CAS_WARNING';
INSERT INTO sys_definition_messages_display VALUES ('A_USER_USER_MANAGEMENT_FULLY_LIST_CAS_WARNING', 'Use Astellia Portal to create, modify or delete users.');
DELETE FROM sys_definition_messages_display WHERE id = 'A_USER_USER_MANAGEMENT_LIST_CAS_WARNING';
INSERT INTO sys_definition_messages_display VALUES ('A_USER_USER_MANAGEMENT_LIST_CAS_WARNING', 'Use Astellia Portal to create or delete users.');

-- 18/07/2013 MGO bz 27170 - Message on disabled PAA Synchronization button
DELETE FROM sys_definition_messages_display WHERE id = 'A_USER_USER_MANAGEMENT_BTN_SYNCHRO_USER_DISABLED';
INSERT INTO sys_definition_messages_display VALUES ('A_USER_USER_MANAGEMENT_BTN_SYNCHRO_USER_DISABLED', 'Synchronization disabled in standalone mode.');

--CB 5.3.1 DE TA Optim - Retrieve a blanc
DELETE FROM sys_definition_messages_display WHERE id = 'A_RETRIEVE_START_NO_FILE_TO_PROCESS';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_RETRIEVE_START_NO_FILE_TO_PROCESS','No files to be retrieved');

--CB 5.3.1 WebService Topology
DELETE FROM sys_definition_messages_display WHERE id = 'A_TRACELOG_MODULE_LABEL_TOPO_WEBSERVICE';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_TRACELOG_MODULE_LABEL_TOPO_WEBSERVICE','WebService Upload Topology');

DELETE FROM sys_definition_messages_display  WHERE id = 'A_UPLOAD_TOPO_INFO_LAST_FILES_UPLOADED';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_UPLOAD_TOPO_INFO_LAST_FILES_UPLOADED', '(Only files uploaded during the last 30 days are displayed)');

-- 28/05/2013 NSE bz 34018 : correction des bullets en dehors du cadre sous FF
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PROCESS_HELP';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PROCESS_HELP', '<ul class="infoBoxList"><li>Each box enables you to manage a product''s processes</li><li>The master product is displayed with "[master]"</li><li>The topology master product is displayed with "[topology master]"</li><li>The time period determines the frequency the processes will be launched (if on).<br />For example, 0h 25mn means the process will start every 25 minutes.</li><li>The offset indicates from what hour / minute a process should start.<br />For example, time period : 0h 25mn / offset 0h 12mn means the process will start every 25 minutes after the next 12th minute (08h12 - 08h37 - 09h12 - 09h37 - etc...).</li></ul>');

-- 30/06/2014 NSE Mantis 5450: Reprocess des données
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROCESS_DATA_REPROCESS_LIMIT';
INSERT INTO sys_definition_messages_display VALUES ('A_PROCESS_DATA_REPROCESS_LIMIT', 'Reprocess 5 days maximum.');
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROCESS_DATA_REPROCESS_WARNING';
INSERT INTO sys_definition_messages_display VALUES ('A_PROCESS_DATA_REPROCESS_WARNING', 'It is strongly recommended to avoid reprocessing more than 2 days. Continue anyway?');
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROCESS_DATA_DELETE_WARNING';
INSERT INTO sys_definition_messages_display VALUES ('A_PROCESS_DATA_DELETE_WARNING', 'You are about to delete statistics for $1 days which might impact server performances. Continue anyway?');

-- 11/07/2014 NSE Mantis 5449: Amélioration de l'historique des données 
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_DATA_HISTORY_POSITIVE_INTEGER';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_DATA_HISTORY_POSITIVE_INTEGER', 'Only positive integer values are allowed.');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_DATA_HISTORY_LIMITS_VALUES';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_DATA_HISTORY_LIMITS_VALUES', 'At least one value is incorrect.Limits are: $1 days for Hour, $2 days for Day, $3 weeks for Week, $4 months for Month');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_DATA_HISTORY_LIMITS_RECOMMENDED_CONFIRM';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_DATA_HISTORY_LIMITS_RECOMMENDED_CONFIRM', 'Values are above recommended limits. Performance and disk usage might be impacted. Apply settings anyway?');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_DATA_HISTORY_PROTECTED';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_DATA_HISTORY_PROTECTED', 'This value can only be modified by Astellia administrator.');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_DATA_HISTORY_LIMITS_RECOMMENDED';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_DATA_HISTORY_LIMITS_RECOMMENDED', '* Recommended maximum value');

-- 23/09/2014 NSE bz 42921 : le mail était réaffiché à la place du numéro de téléphone
DELETE FROM sys_definition_messages_display WHERE id = 'SMS_SENDING_WRONG_PHONE_NUMBER';
INSERT INTO sys_definition_messages_display VALUES ('SMS_SENDING_WRONG_PHONE_NUMBER','Unable to send SMS to user $1, wrong phone number ($2)' );


-- !!!! LAISSER A LA FIN DU FICHIER !!!!
VACUUM ANALYSE sys_definition_messages_display;