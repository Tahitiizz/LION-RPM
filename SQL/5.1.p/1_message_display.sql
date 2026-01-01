--
-- N 'OUBLIEZ PAS DE METTRE DES DELETE AVANT CHAQUE INSERT !!!!!!!!!!!!!!!!!!!!!!!!!
--
-- exemples
-- DELETE FROM sys_definition_messages_display WHERE id = 'TOTO';
-- INSERT INTO sys_definition_messages_display VALUES ('TOTO', 'toto');


-- Correction bz23299 (Ajout de tous les messages 5.0 pouvant poser problème lors d'un patch 5.1.x)
-- 13/12/2010 BBX BZ 18510
DELETE FROM sys_definition_messages_display WHERE id = 'A_DATABASE_PRODUCT_AUTO_DISABLED';
INSERT INTO sys_definition_messages_display VALUES ('A_DATABASE_PRODUCT_AUTO_DISABLED', 'Product $1 has been automatically disabled because it is not reachable. Contact administrators to get more information');

-- 13/12/2010 BBX BZ 18510
DELETE FROM sys_definition_messages_display WHERE id = 'A_DATABASE_PRODUCT_AUTO_DISABLED_INFO';
INSERT INTO sys_definition_messages_display VALUES ('A_DATABASE_PRODUCT_AUTO_DISABLED_INFO', 'This Product has been automatically disabled');

-- 14/12/2010 BBX BZ 18510
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCT_AUTOMATIC_ACTIVATION';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCT_AUTOMATIC_ACTIVATION', 'Automatic activation');

-- 14/12/2010 BBX BZ 18510
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCT_COMPONENT_STATUS';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCT_COMPONENT_STATUS', 'Component is currently');

-- 16/12/2010 BBX BZ 18510
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCT_HELP_AUTO_ACT';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCT_HELP_AUTO_ACT', 'Enabling this option will cause component to be automatically enabled when it is back. Uncheck this option if you do not want component to be automatically enabled (maintenance operations, network problems, ...)');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCT_HELP_AUTO_ACT_RO';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCT_HELP_AUTO_ACT_RO', 'Component has been manually disabled so automatic activation cannot take place. Component must be manually enabled in order to reactivate automatic activation');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCT_NOT_A_SLAVE';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCT_NOT_A_SLAVE', 'This product is no longer configured as Slave. If the former configuration cannot be restored you must delete this product.');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_FAMILY_LOCKED';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_FAMILY_LOCKED', 'Configuration is disabled as $1 is temporarily unavailable.');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCT_DISABLED_NOT_A_SLAVE';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCT_DISABLED_NOT_A_SLAVE', 'Product $1 has been automatically disabled because it is no longer configured as Slave');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_MAPPING_TOPO_INFO_FOR_CHOOSE_MASTER_TOPO';
INSERT INTO sys_definition_messages_display VALUES ('A_MAPPING_TOPO_INFO_FOR_CHOOSE_MASTER_TOPO', 'You can choose a master topology. Go to <a href="$1">Setup Product</a>');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_DATABASE_PRODUCT_AUTO_ENABLED_INFO';
INSERT INTO sys_definition_messages_display VALUES ('A_DATABASE_PRODUCT_AUTO_ENABLED_INFO', 'Product $1 has been automatically enabled because it is reachable again');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_DATABASE_PRODUCT_AUTO_ENABLED_FAILED';
INSERT INTO sys_definition_messages_display VALUES ('A_DATABASE_PRODUCT_AUTO_ENABLED_FAILED', 'Product $1 still not reachable. Could not enable it.');

-- 29/12/2010 BBX
-- BZ 19741
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROCESS_DUMP_PROCESSING_FILE';
INSERT INTO sys_definition_messages_display VALUES ('A_PROCESS_DUMP_PROCESSING_FILE', 'Processes will not start while maintenance file $1 is present');

--06/01/2011 NSE bz 19128
--01/03/2011 MMT bz 19128
DELETE FROM sys_definition_messages_display WHERE id = 'A_TASK_SCHEDULER_SCHEDULE_SETUP_FILE_FORMAT';
INSERT INTO sys_definition_messages_display VALUES ('A_TASK_SCHEDULER_SCHEDULE_SETUP_FILE_FORMAT', 'The version of the following T&A Product(s) will always generate alarm reports in PDF format: $1');

-- 04/01/2010 SCT BZ 19673
DELETE FROM sys_definition_messages_display WHERE id = 'G_E_SSH2_NOT_AVAILABLE_ON_REMOTE_SERVER';
INSERT INTO sys_definition_messages_display VALUES ('G_E_SSH2_NOT_AVAILABLE_ON_REMOTE_SERVER', 'Remote server $1 is not available.');
--

-- 14/01/2011 BZ 19783
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCT_PRODUCT_ENABLED';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCT_PRODUCT_ENABLED', '$1 has been activated. See $2 for details.');

-- 14/01/2011 NSE BZ 19672
DELETE FROM sys_definition_messages_display WHERE id = 'A_MAPPING_COUNTERS_ACTIVATION_COUNTER_DEACTIVATION_DISABLED_5';
INSERT INTO sys_definition_messages_display VALUES ('A_MAPPING_COUNTERS_ACTIVATION_COUNTER_DEACTIVATION_DISABLED_5', 'Counter "$1" ($2) is part of an alarm. It can not be deactivated.');
DELETE FROM sys_definition_messages_display WHERE id = 'A_MAPPING_COUNTERS_ACTIVATION_COUNTER_DEACTIVATION_DISABLED_6';
INSERT INTO sys_definition_messages_display VALUES ('A_MAPPING_COUNTERS_ACTIVATION_COUNTER_DEACTIVATION_DISABLED_6', 'Counter "$1" is used in an alarm. It can not be deactivated.');

-- 03/02/2011 BBX BZ 20369
DELETE FROM sys_definition_messages_display WHERE id = 'A_MAPPING_COUNTERS_ACTIVATION_COUNTER_DEACTIVATION_DISABLED_7';
INSERT INTO sys_definition_messages_display VALUES ('A_MAPPING_COUNTERS_ACTIVATION_COUNTER_DEACTIVATION_DISABLED_7', 'Counter "$1" is used in Mixed KPI. It cannot be deactivated.');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_COUNTERS_WILL_BE_REMOVED';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_COUNTERS_WILL_BE_REMOVED', 'Warning, The following counters will be removed from Mixed Kpi because the source counters has been disactivated :');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_COUNTERS_HAVE_BEEN_DELETED';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_COUNTERS_HAVE_BEEN_DELETED', 'The folowing counters have been deleted from Mixed KPI because source counters are disactivated : ');

-- 04/02/2011 NSE BZ 19666
DELETE FROM sys_definition_messages_display WHERE id = 'A_CLEAN_TABLES_STRUCTURE_DEMON_WARNING_KPI_DISABLE_ERROR';
INSERT INTO sys_definition_messages_display VALUES ('A_CLEAN_TABLES_STRUCTURE_DEMON_WARNING_KPI_DISABLE_ERROR', 'Returned error message: "$1"');

-- 11/02/2011 OJT DE Selecteur/Historique
DELETE FROM sys_definition_messages_display WHERE id = 'U_DASH_MAX_TOPOVER_OT';
INSERT INTO sys_definition_messages_display VALUES ('U_DASH_MAX_TOPOVER_OT', 'A maximum of $1 elements can be displayed when viewing statistics over time ');
DELETE FROM sys_definition_messages_display where id='SELECTEUR_PERIOD';
INSERT INTO sys_definition_messages_display (id,text) values ('SELECTEUR_PERIOD','Period');
DELETE FROM sys_definition_messages_display where id='SELECTEUR_PERIOD_WARNING';
INSERT INTO sys_definition_messages_display (id,text) values ('SELECTEUR_PERIOD_WARNING','Dashboards display with more than 200 periods can be very long');

-- 15/02/2011 OJT BZ 15445
DELETE FROM sys_definition_messages_display where id='A_JS_SETUP_CONNECTION_NAME_REGEXP';
INSERT INTO sys_definition_messages_display (id,text) values ('A_JS_SETUP_CONNECTION_NAME_REGEXP','Please enter a valid connection name (only alphanumeric characters, " ", ".", "_" and "-" are allowed, minLength : 2)');

-- 15/02/2011 BBX
-- BZ 20718
DELETE FROM sys_definition_messages_display WHERE id = 'A_U_LOGIN_SHOW_SLAVES';
INSERT INTO sys_definition_messages_display VALUES ('A_U_LOGIN_SHOW_SLAVES', 'Display slave products');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_U_LOGIN_HIDE_SLAVES';
INSERT INTO sys_definition_messages_display VALUES ('A_U_LOGIN_HIDE_SLAVES', 'Hide slave products');

-- 15/02/2011 NSE DE Query Builder
DELETE FROM sys_definition_messages_display WHERE id = 'U_QUERY_BUILDER_ONLY_1000_RESULTS';
INSERT INTO sys_definition_messages_display VALUES ('U_QUERY_BUILDER_ONLY_1000_RESULTS', 'Only the first $1 values will be displayed in the table result tab.<br>However, all results can be downloaded as a CVS export');

-- 24/02/2011 OJT BZ 20811
DELETE FROM sys_definition_messages_display WHERE id = 'A_CLEANING_COLUMNS_FAILED';
DELETE FROM sys_definition_messages_display WHERE id = 'A_CLEANING_COLUMNS_TABLE_INFO';
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_CONTEXT_MOUNT_LIMIT_NB_COUNTER_EXCEEDED_SIMPLE';
INSERT INTO sys_definition_messages_display VALUES ('A_CLEANING_COLUMNS_FAILED', 'Cleaning $1 failed. Please, check if Postgresql data partition has enough free space disc');
INSERT INTO sys_definition_messages_display VALUES ('A_CLEANING_COLUMNS_TABLE_INFO', 'Table $1 has been cleaned because the limit on maximum number of columns has been reached');
INSERT INTO sys_definition_messages_display VALUES ('A_E_CONTEXT_MOUNT_LIMIT_NB_COUNTER_EXCEEDED_SIMPLE', 'You have exceeded the limit of $1 for the family $2' );
-- Fin bz23299 (problème de merge 5.0)

-- 20/10/2010 BBX : Messages pour le DE sur les compteurs clients dans le contexte
DELETE FROM sys_definition_messages_display WHERE id = 'A_CONTEXT_COULD_NOT_UPDATE_COUNTERS';
INSERT INTO sys_definition_messages_display VALUES ('A_CONTEXT_COULD_NOT_UPDATE_COUNTERS', 'Could not update counter ids on the following product : "$1". Please, check the information filled in Setup/Setup Products');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_CONTEXT_LOG_UPDATED_COUNTERS';
INSERT INTO sys_definition_messages_display VALUES ('A_CONTEXT_LOG_UPDATED_COUNTERS', 'The following counters already exist in this application. They will be updated to comply with the Astellia context :');

-- 13/04/2011 BBX BZ 21810
DELETE FROM sys_definition_messages_display WHERE id = 'U_QUERY_BUILDER_GRAPH_NO_LEGEND';
INSERT INTO sys_definition_messages_display VALUES ('U_QUERY_BUILDER_GRAPH_NO_LEGEND', 'Legend has been removed from x-axis for legibility purposes');

-- 13/04/2011 BBX BZ 21790
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_CONNECTION_FORM_LABEL_SONDE_CODE';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_CONNECTION_FORM_LABEL_SONDE_CODE', 'Probe Code');

-- 18/05/2011 BBX : Partitioning
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PARTITIONING_TA_APPLICATION_LABEL';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PARTITIONING_TA_APPLICATION_LABEL','T&amp;A Application');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PARTITIONING_POSTGRESQL_LABEL';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PARTITIONING_POSTGRESQL_LABEL','Postgresql');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PARTITIONING_PARTITIONING_LABEL';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PARTITIONING_PARTITIONING_LABEL','Partitioning');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PARTITIONING_INFORMATION_LABEL';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PARTITIONING_INFORMATION_LABEL','Information');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PARTITIONING_PARTITIONED_PRODUCT';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PARTITIONING_PARTITIONED_PRODUCT','Partitioned');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PARTITIONING_NOT_PARTITIONED_PRODUCT';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PARTITIONING_NOT_PARTITIONED_PRODUCT','Not Partitioned');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PARTITIONING_IP_ADDRESS';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PARTITIONING_IP_ADDRESS','IP Address');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PARTITIONING_PARTITIONING_AVAILABLE';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PARTITIONING_PARTITIONING_AVAILABLE','Partitioning available without restriction');
--
-- 30/06/2011 BBX
-- Correction du message. BZ 22749
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PARTITIONING_PARTITIONING_WARNING';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PARTITIONING_PARTITIONING_WARNING','Partitioning available but other partitioned T&A applications are installed on the server. This will not prevent applications from working but may decrease overall performance.');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PARTITIONING_AUTOVACCUM_DISABLED';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PARTITIONING_AUTOVACCUM_DISABLED','Autovacuum is disabled');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PARTITIONING_CONS_EXC_DISABLED';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PARTITIONING_CONS_EXC_DISABLED','constraint_exclusion is disabled');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PARTITIONING_MAX_LOCKS_INF';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PARTITIONING_MAX_LOCKS_INF','max_locks_per_transaction parameters is &lt; ');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PARTITIONING_PG_VERSION_INF';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PARTITIONING_PG_VERSION_INF','Postgresql version is &lt; 9.1');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PARTITIONING_CB_VERSION_INF';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PARTITIONING_CB_VERSION_INF','Base component version is &lt; 5.1.4');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PARTITIONING_CONFIG_LIMIT_REACHED';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PARTITIONING_CONFIG_LIMIT_REACHED','this application is not the only T&A application installed on the server and current configuration will not be able to handle it.');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PARTITIONING_PARTITIONING_NOT_AVAILABLE';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PARTITIONING_PARTITIONING_NOT_AVAILABLE','Partitioning not available because');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PARTITIONING_PARTITIONING_HELP';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PARTITIONING_PARTITIONING_HELP','Tick the checkboxes for the T&A applications you would like to partition.<br />It might not be possible to partition all T&A applications because of versionning or configuration issues.<br />Please have a look at the info popup for more information.');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PARTITIONING_CONFIRM_BOX_TITLE';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PARTITIONING_CONFIRM_BOX_TITLE','Warning about partitioning');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PARTITIONING_CONFIRM_BOX_MESSAGE';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PARTITIONING_CONFIRM_BOX_MESSAGE','Partitioning databases requires heavy database processing.<br />During partitioning activation, T&amp;A applications will become unavailable and data processing will be stopped.<br />Are you really sure you want to enable partitioning for the selected T&amp;A applications?<br /><span style="color:red;font-weight:bold">Warning: Partitioning cannot be rolled back.</span>');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PARTITIONING_CONFIRM_BOX_EMAIL';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PARTITIONING_CONFIRM_BOX_EMAIL','Send an email to the folowing address when partitioning is complete:');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PARTITIONING_PARTITION_BUTTON';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PARTITIONING_PARTITION_BUTTON','Partition');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PARTITIONING_CANCEL_BUTTON';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PARTITIONING_CANCEL_BUTTON','Cancel');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PARTITIONING_THANK_YOU';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PARTITIONING_THANK_YOU','Thank you for your understanding.');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PARTITIONING_NO_PARTITIONING_PROCESS';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PARTITIONING_NO_PARTITIONING_PROCESS','No partitioning process is currently running');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PARTITIONING_TIME_LEFT';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PARTITIONING_TIME_LEFT','T&amp;A should be back online in about');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PARTITIONING_SUCCESSFULLY_PARTITIONED';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PARTITIONING_SUCCESSFULLY_PARTITIONED','successfully partitioned');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PARTITIONING_PARTITIONING_FAILED';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PARTITIONING_PARTITIONING_FAILED','partitioning failed!');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PARTITIONING_CONTACT_SUPPORT';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PARTITIONING_CONTACT_SUPPORT','<br/ >Please, contact <a href="mailto:$2">Astellia support</a> as soon as possible.<br /><a href="$1">Click here to download partitioning log</a>');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PARTITIONING_PRODUCTS_BEING_PARTITIONED';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PARTITIONING_PRODUCTS_BEING_PARTITIONED','The following products are being partitioned:');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PARTITIONING_EMAIL_SUCCESS';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PARTITIONING_EMAIL_SUCCESS','<p>Partitioning activation on $1 successfully completed.</p><p>Users can now login to application and processes can be started.</p><p></p><p><a href="http://$2/$3/">http://$2/$3/</a></p>');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PARTITIONING_EMAIL_FAILED';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PARTITIONING_EMAIL_FAILED','<p>Partitioning activation on $1 failed.</p>Please, contact Astellia support as soon as possible.</p><p></p><p><a href="mailto:$4">Atellia support</a></p><p></p><p><a href="http://$2/$3/">http://$2/$3/</a></p><p></p><p>We are sorry for the inconvenience</p>');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PARTITIONING_REFRESH';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PARTITIONING_REFRESH','Click here to refresh');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PARTITIONING_CB_LABEL';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PARTITIONING_CB_LABEL','CB');

DELETE FROM sys_definition_messages_display WHERE id = 'A_XPERT_NO_VALID_PARENT_FOUND';
INSERT INTO sys_definition_messages_display VALUES ('A_XPERT_NO_VALID_PARENT_FOUND', 'Sorry, no supported BSC or RNC topology parent could be found for network element id ''$1'' ');

DELETE FROM sys_definition_messages_display WHERE id = 'A_XPERT_NO_VALID_APPLICATION_FOUND';
INSERT INTO sys_definition_messages_display VALUES ('A_XPERT_NO_VALID_APPLICATION_FOUND', 'Could not find any Xpert application for element id ''$1'' with parent id ''$2''');

--20/04/2011 MMT fix bug 21125
DELETE FROM sys_definition_messages_display WHERE id = 'A_XPERT_NO_DASHBOARD_FOUND';
INSERT INTO sys_definition_messages_display VALUES ('A_XPERT_NO_DASHBOARD_FOUND', 'No matching dashboard.');

DELETE FROM sys_definition_messages_display WHERE id = 'A_XPERT_DASHBOARD_LIST_PRIVILEDGES_APPLY';
INSERT INTO sys_definition_messages_display VALUES ('A_XPERT_DASHBOARD_LIST_PRIVILEDGES_APPLY', 'Note: This list is restricted to your profile privileges');

-- 1/5/2011 MMT DE 3rdAxis
DELETE FROM sys_definition_messages_display WHERE id = 'U_INVESTIGATION_DASHBOARD_NOT_ENOUGH_ELEMENTS';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('U_INVESTIGATION_DASHBOARD_NOT_ENOUGH_ELEMENTS', 'Please select at least one Network Element for each axis and one Raw/Kpi !');

DELETE FROM sys_definition_messages_display WHERE id = 'A_INVESTIGATION_DASHBOARD_1ST_AXIS_SELECTOR_LABEL';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_INVESTIGATION_DASHBOARD_1ST_AXIS_SELECTOR_LABEL', 'Primary');

DELETE FROM sys_definition_messages_display WHERE id = 'A_INVESTIGATION_DASHBOARD_3RD_AXIS_SELECTOR_LABEL';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_INVESTIGATION_DASHBOARD_3RD_AXIS_SELECTOR_LABEL', 'Secondary');

--11/01/2011 MMT DE Xpert 606  (MERGE 5.1.4)
DELETE FROM sys_definition_messages_display WHERE id = 'A_GTM_XPERT_CONTEXTUAL_MENU_LABEL';
INSERT INTO sys_definition_messages_display VALUES ('A_GTM_XPERT_CONTEXTUAL_MENU_LABEL', 'Go to Xpert');

-- 02/08/2011 NSE bz 22264
DELETE FROM sys_definition_messages_display WHERE id = 'A_MAPPING_COUNTERS_ACTIVATED_COUNTER_DEMAPPING_DISABLED_1';
INSERT INTO sys_definition_messages_display VALUES ('A_MAPPING_COUNTERS_ACTIVATED_COUNTER_DEMAPPING_DISABLED_1', 
                                                    'Counter ''$1'' is activated. It can not be unmapped. Deactivate it first.');
DELETE FROM sys_definition_messages_display WHERE id = 'A_MAPPING_COUNTERS_ACTIVATED_COUNTER_DEMAPPING_DISABLED_2';
INSERT INTO sys_definition_messages_display VALUES ('A_MAPPING_COUNTERS_ACTIVATED_COUNTER_DEMAPPING_DISABLED_2', 
                                                    'Counter ''$1'' (''$1'') is activated. It can not be unmapped. Deactivate it first.');
-- 02/08/2011 NSE bz 23247
DELETE FROM sys_definition_messages_display WHERE id = 'A_MAPPING_COUNTERS_ACTIVATED_COUNTER_DEMAPPING_DISABLED_RETRIEVE_1';
INSERT INTO sys_definition_messages_display VALUES ('A_MAPPING_COUNTERS_ACTIVATED_COUNTER_DEMAPPING_DISABLED_RETRIEVE_1', 
                                                    'Counter ''$1'' is not fully deactivated. It can not be unmapped. Launch a retrieve and try again.');
DELETE FROM sys_definition_messages_display WHERE id = 'A_MAPPING_COUNTERS_ACTIVATED_COUNTER_DEMAPPING_DISABLED_RETRIEVE_2';
INSERT INTO sys_definition_messages_display VALUES ('A_MAPPING_COUNTERS_ACTIVATED_COUNTER_DEMAPPING_DISABLED_RETRIEVE_2', 
                                                    'Counter ''$1'' (''$1'') is not fully deactivated. It can not be unmapped. Launch a retrieve and try again.');

-- 24/02/2011 OJT DE SFTP
DELETE FROM sys_definition_messages_display WHERE id='A_SETUP_CONNECTION_FORM_BTN_FTP_CHECK';
DELETE FROM sys_definition_messages_display WHERE id='A_SETUP_CONNECTION_FORM_LABEL_PORT';
DELETE FROM sys_definition_messages_display WHERE id='A_JS_SETUP_CONNECTION_PORT_RANGE';
INSERT INTO sys_definition_messages_display (id,text) values ('A_SETUP_CONNECTION_FORM_LABEL_PORT','Port');
INSERT INTO sys_definition_messages_display (id,text) values ('A_JS_SETUP_CONNECTION_PORT_RANGE','Port number must be a positive integer (smaller than 65536)' );
INSERT INTO sys_definition_messages_display (id,text) values ('A_SETUP_CONNECTION_FORM_BTN_FTP_CHECK','Check remote connections' );

-- 18/07/2011 OJT : DE SMS, ajout des messages de tooltip, avertissements et erreurs
-- 27/09/2011 OJT : bz23921, modification des messages SMS_SENDING_USER_ERROR et SMS_BINDING_ERROR
DELETE FROM sys_definition_messages_display WHERE id = 'SMS_TEST_BUTTON_TOOLTIP';
DELETE FROM sys_definition_messages_display WHERE id = 'SMS_TEST_SUCCESSFULLY_SENT';
DELETE FROM sys_definition_messages_display WHERE id = 'SMS_TEST_MESSAGE';
DELETE FROM sys_definition_messages_display WHERE id = 'SMS_SETUP_ALARM_BUTTON_TOOLTIP_OFF';
DELETE FROM sys_definition_messages_display WHERE id = 'SMS_SETUP_ALARM_BUTTON_TOOLTIP_ON';
DELETE FROM sys_definition_messages_display WHERE id = 'SMS_SETUP_ALARM_GROUP_WARN';
DELETE FROM sys_definition_messages_display WHERE id = 'SMS_SETUP_ALARM_USER_WARN';
DELETE FROM sys_definition_messages_display WHERE id = 'SMSC_PORT_ERROR';
DELETE FROM sys_definition_messages_display WHERE id = 'SMS_PHONE_NUMBER_ERROR';
DELETE FROM sys_definition_messages_display WHERE id = 'SMS_ALARM_SEVERITY_ERROR';
DELETE FROM sys_definition_messages_display WHERE id = 'SMS_BINDING_ERROR';
DELETE FROM sys_definition_messages_display WHERE id = 'SMS_SENDING_USER_ERROR';
DELETE FROM sys_definition_messages_display WHERE id = 'SMS_SENDING_MISSING_PHONE_NUMBER';
DELETE FROM sys_definition_messages_display WHERE id = 'SMS_SENDING_WRONG_PHONE_NUMBER';
INSERT INTO sys_definition_messages_display VALUES ('SMS_TEST_BUTTON_TOOLTIP','Send test SMS to this phone' );
INSERT INTO sys_definition_messages_display VALUES ('SMS_TEST_SUCCESSFULLY_SENT','SMS sent to $1. Please check it has been received' );
INSERT INTO sys_definition_messages_display VALUES ('SMS_TEST_MESSAGE','This is a test SMS sent from T&A application "$1" ($2)' );
INSERT INTO sys_definition_messages_display VALUES ('SMS_SETUP_ALARM_BUTTON_TOOLTIP_OFF','Configure or deactivate SMS sending' );
INSERT INTO sys_definition_messages_display VALUES ('SMS_SETUP_ALARM_BUTTON_TOOLTIP_ON','Activate SMS sending' );
INSERT INTO sys_definition_messages_display VALUES ('SMS_SETUP_ALARM_GROUP_WARN','Warning: missing phone number in group' );
INSERT INTO sys_definition_messages_display VALUES ('SMS_SETUP_ALARM_USER_WARN','Warning: no phone number defined for this user' );
INSERT INTO sys_definition_messages_display VALUES ('SMSC_PORT_ERROR','SMS-C Port number must be a positive integer (smaller than 65536)' );
INSERT INTO sys_definition_messages_display VALUES ('SMS_PHONE_NUMBER_ERROR','Phone number is not valid (only numeric characters, "+", "(", ")", "-" and "." are allowed)' );
INSERT INTO sys_definition_messages_display VALUES ('SMS_ALARM_SEVERITY_ERROR','Alarm SMS minimum severity is not valid ("minor", "major" or "critical")' );
INSERT INTO sys_definition_messages_display VALUES ('SMS_BINDING_ERROR','Error during SMS-C binding ($1), no SMS sent' );
INSERT INTO sys_definition_messages_display VALUES ('SMS_SENDING_USER_ERROR','Error during sending SMS for user $1 ($2)' );
INSERT INTO sys_definition_messages_display VALUES ('SMS_SENDING_MISSING_PHONE_NUMBER','Unable to send SMS to user $1, no phone number found' );
INSERT INTO sys_definition_messages_display VALUES ('SMS_SENDING_WRONG_PHONE_NUMBER','Unable to send SMS to user $1, wrong phone number ($1)' );

DELETE FROM sys_definition_messages_display WHERE id = 'G_PROFILE_FORM_LABEL_USER_PHONE_NUMBER';
INSERT INTO sys_definition_messages_display VALUES ('G_PROFILE_FORM_LABEL_USER_PHONE_NUMBER','Phone number (for SMS)' );

-- 10/08/2011 BBX BZ 23353
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PARTITIONING_PROCESS_RUNNING';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PARTITIONING_PROCESS_RUNNING','Processes are currently running on application $1. Please stop every process before partitioning');

-- 10/10/2011 ACS DE Data reprocessing GUI
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROCESS_DATA_REPROCESS_MODE';
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROCESS_DATA_REPROCESS_DATE';
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROCESS_DATA_REPROCESS_CONNECTION';
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROCESS_DATA_REPROCESS_WARNING_REPROCESS';
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROCESS_DATA_REPROCESS_PROCESS_OFF';
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROCESS_DATA_REPROCESS_WARNING_DELETE';
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROCESS_DATA_REPROCESS_REPROCESS_SUCCESS';
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROCESS_DATA_REPROCESS_DELETE_SUCCESS';
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROCESS_DATA_REPROCESS_PROCESS_TOOLTIP';
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROCESS_DATA_REPROCESS_DELETE_TOOLTIP';
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROCESS_DATA_REPROCESS_DATE_TOOLTIP';
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROCESS_DATA_DE_MISSING';
DELETE FROM sys_definition_messages_display WHERE id = 'A_TRACELOG_MODULE_LABEL_DATA_REPROCESS';
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROCESS_DATA_LOG_TEXT_REPROCESS';
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROCESS_DATA_LOG_TEXT_DELETE';
INSERT INTO sys_definition_messages_display VALUES ('A_PROCESS_DATA_REPROCESS_MODE','Please select a correct mode');
INSERT INTO sys_definition_messages_display VALUES ('A_PROCESS_DATA_REPROCESS_DATE','Please select a date');
INSERT INTO sys_definition_messages_display VALUES ('A_PROCESS_DATA_REPROCESS_CONNECTION','Please select a connection');
INSERT INTO sys_definition_messages_display VALUES ('A_PROCESS_DATA_REPROCESS_WARNING_REPROCESS','Do you really want to reprocess data files for date(s) "$1"?<br /><br />Reprocessing task:<br />  - Will process most recent files first<br /> - Can take several hours and will be launched when running task, if any, are completed.');
INSERT INTO sys_definition_messages_display VALUES ('A_PROCESS_DATA_REPROCESS_PROCESS_OFF','Warning : Collect, retrieve & compute processes are stopped. Reprocessing will not take place unless "on" checkboxes above are checked.');
INSERT INTO sys_definition_messages_display VALUES ('A_PROCESS_DATA_REPROCESS_WARNING_DELETE','Do you really want to delete data files for the following date(s) "$1" and connection(s) "$2"?<br /><br />New data files, if available, will be collected and processed.');
INSERT INTO sys_definition_messages_display VALUES ('A_PROCESS_DATA_REPROCESS_REPROCESS_SUCCESS','Reprocess files request has been sent.');
INSERT INTO sys_definition_messages_display VALUES ('A_PROCESS_DATA_REPROCESS_DELETE_SUCCESS','Data files have been removed ($1 files impacted).');
INSERT INTO sys_definition_messages_display VALUES ('A_PROCESS_DATA_REPROCESS_PROCESS_TOOLTIP','Use existing files for reprocessing.');
INSERT INTO sys_definition_messages_display VALUES ('A_PROCESS_DATA_REPROCESS_DELETE_TOOLTIP','Delete existing files and reprocess new files, if any.');
INSERT INTO sys_definition_messages_display VALUES ('A_PROCESS_DATA_REPROCESS_DATE_TOOLTIP','Only available dates are displayed.');
INSERT INTO sys_definition_messages_display VALUES ('A_PROCESS_DATA_DE_MISSING','This product needs to be updated to allow Data reprocessing.');
INSERT INTO sys_definition_messages_display VALUES ('A_TRACELOG_MODULE_LABEL_DATA_REPROCESS','Data reprocessing');
INSERT INTO sys_definition_messages_display VALUES ('A_PROCESS_DATA_LOG_TEXT_REPROCESS','"$1" force to reprocess files for "$2"');
INSERT INTO sys_definition_messages_display VALUES ('A_PROCESS_DATA_LOG_TEXT_DELETE','"$1" delete files for "$2" on "$3"');

-- 10/10/2011 NSE DE Bypass temporel
DELETE FROM sys_definition_messages_display WHERE id = 'A_CONTEXT_TA_BYPASS_UPDATE_CORPORATE';
INSERT INTO sys_definition_messages_display VALUES ('A_CONTEXT_TA_BYPASS_UPDATE_CORPORATE', 'Updates have been done by context mount. Go to GUI SETUP / Setup Corporate and click on "Save" button to validate Family configuration (your setup has not been modified).');
DELETE FROM sys_definition_messages_display WHERE id = 'A_CONTEXT_TA_BYPASS_UPDATE_MIXEDKPI';
INSERT INTO sys_definition_messages_display VALUES ('A_CONTEXT_TA_BYPASS_UPDATE_MIXEDKPI', 'Updates have been done by context mount. Go to GUI SETUP / Setup Mixed Kpi, click on "Edit Na" then on "Save" button for one every family (none of your setup has been modified).');

-- 14/09/2011 BBX BZ 20799
DELETE FROM sys_definition_messages_display WHERE id = 'G_GDR_BUILDER_HELP_DRAG_TO_RE_ORDER';
INSERT INTO sys_definition_messages_display VALUES ('G_GDR_BUILDER_HELP_DRAG_TO_RE_ORDER','You can re-order the elements of the following list by simply dragging them up or down. The element on top will be in the background of the graph. For cumulated elements, the lowest element will be used to define order.' );

-- 15/09/2011 BBX BZ 23158
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROCESS_KILLED_MAIL_ALERT';
INSERT INTO sys_definition_messages_display VALUES ('A_PROCESS_KILLED_MAIL_ALERT','<p>A process of the application "$1" ran for too long and had to be killed.</p><p>You should check whether this application needs maintenance.</p>');

-- 29/09/2011 BBX
DELETE FROM sys_definition_messages_display WHERE id = 'A_MAPPING_COUNTERS_ACTIVATION_COUNTER_DEACTIVATION_DISABLED_8';
INSERT INTO sys_definition_messages_display VALUES ('A_MAPPING_COUNTERS_ACTIVATION_COUNTER_DEACTIVATION_DISABLED_8','Counter "$1" is used in the query "$2" of user "$3"');

-- 12/10/2011 BBX
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_CORPORATE_SUPER_NETWORK_CONFLICT';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_CORPORATE_SUPER_NETWORK_CONFLICT','Super Network "$1" could not be created as network aggregation "$1" already exists');

-- 14/10/2011 BBX
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_BH_FORMULA_IS_INCORRECT';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_BH_FORMULA_IS_INCORRECT','The formula of the selected $1 is incorrect');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_BH_FORMULA_IS_NULL';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_BH_FORMULA_IS_NULL','The formula of the selected $1 is null');

-- 19/10/2011 BBX
DELETE FROM sys_definition_messages_display WHERE id = 'A_USER_MANAGEMENT_SMSC_SELECTION';
INSERT INTO sys_definition_messages_display VALUES ('A_USER_MANAGEMENT_SMSC_SELECTION','Please, select a configuration to use');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_USER_MANAGEMENT_SMSC_NO_SMSC';
INSERT INTO sys_definition_messages_display VALUES ('A_USER_MANAGEMENT_SMSC_NO_SMSC','No SMSC configured');

-- 19/04/2011 OJT : Produit Blanc
DELETE FROM sys_definition_messages_display WHERE id = 'U_MYPROFILE_NASELECTION_NOTICE_NO_NA';
INSERT INTO sys_definition_messages_display VALUES ('U_MYPROFILE_NASELECTION_NOTICE_NO_NA', 'Notice : unable to set ''Network Element Preferences'', no common element found');
DELETE FROM sys_definition_messages_display WHERE id = 'U_PROFILE_HOMEPAGE_NO_DASH';
INSERT INTO sys_definition_messages_display VALUES ('U_PROFILE_HOMEPAGE_NO_DASH', 'Notice : unable to configure ''My Homepage'', no dashboard found');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PARTITIONING_PARTITIONING_NOT_AVAILABLE_GATEWAY';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PARTITIONING_PARTITIONING_NOT_AVAILABLE_GATEWAY','Partitioning not available on T&A Gateway product');
--
-- 12/09/2011 NSE DE Master 5.1 / slave 5.0 compatibility
-- 09/11/2011 ACS BZ 23873 Change \' by '' in messages (for PG 9.1)
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCTS_CB_INVALID';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCTS_CB_INVALID', 'This product cannot be set as slave of product "$1".<br>Please ensure that its Base Component version ($4) is:<br> &nbsp; - higher than $2,<br> &nbsp; - lower than "$1" Base Component version ($3).');
DELETE FROM sys_definition_messages_display WHERE id = 'A_ALARM_WARNING_NO_PARENT_NETWORK_LEVEL';
INSERT INTO sys_definition_messages_display VALUES ('A_ALARM_WARNING_NO_PARENT_NETWORK_LEVEL', 'It is not possible to select a parent network element for this product<br>because its Base Component version ($1) is not compatible with this feature.');



-- 24/08/2011 MMT DE PAAL1 - Merge de la branche VLC 5_1_0_10_PAA_01_02
-- message d'erreur si le user est connu du portail mais pas de l'application
DELETE FROM sys_definition_messages_display WHERE id = 'U_LOGIN_UNKNOWN';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('U_LOGIN_UNKNOWN', 'User ''$1'' does not exist on this application. Please contact your product administrator.');

DELETE FROM sys_definition_messages_display WHERE id = 'U_PAA_CONFIG_NOT_FOUND';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('U_PAA_CONFIG_NOT_FOUND', 'Authentication configuration file ''$1'' not found');

DELETE FROM sys_definition_messages_display WHERE id = 'A_USER_USER_MANAGEMENT_LIST_CAS_WARNING';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_USER_USER_MANAGEMENT_LIST_CAS_WARNING', 'All user modification (new/deleted/modified) must be manually applied on the Application Portal.');

-- 24/11/2011 NSE bz 24829 : words "to Login, Password or Email" deleted
DELETE FROM sys_definition_messages_display WHERE id = 'A_USER_USER_MANAGEMENT_EDIT_CAS_WARNING';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_USER_USER_MANAGEMENT_EDIT_CAS_WARNING', 'All modification must be manually applied on the Application Portal.');

DELETE FROM sys_definition_messages_display WHERE id = 'A_TOOLS_USER_EXPORT_ERROR';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_TOOLS_USER_EXPORT_ERROR', 'Error occured during the export of users: $1<br><br>Please contact Astellia support for assistance.');

DELETE FROM sys_definition_messages_display WHERE id = 'A_TOOLS_USER_EXPORT_SUCCESS';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_TOOLS_USER_EXPORT_SUCCESS', 'T&A Users export operation successful <br><br>You may download the CSV file and import it to Astellia Portal<br>Note: Passwords will be ignored for existing users when imported on Astellia Portal');
-- 24/08/2011 MMT DE PAAL1 - FIN

-- 09/11/2011 ACS BZ 24526
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_BH_SAVE_OK';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_BH_SAVE_OK','Busy hour configuration has been saved');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_BH_RESET_OK';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_BH_RESET_OK','Busy hour configuration has been deleted');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_DATA_HISTORY_SAVE_OK';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_DATA_HISTORY_SAVE_OK','Data history configuration has been saved');

-- 28/11/2011 OJT : bz24855 Add family information in message
DELETE FROM sys_definition_messages_display WHERE id = 'A_TRACELOG_WARNING_KPI_DISABLE';
INSERT INTO sys_definition_messages_display VALUES ('A_TRACELOG_WARNING_KPI_DISABLE', 'KPI $1 ($2) has been disabled : Invalid formula');

-- 25/11/2011 NSE bz 24824
-- 16/12/2011 ACS BZ 25166
DELETE FROM sys_definition_messages_display WHERE id = 'U_PDF_FILE_DOWNLOAD_ALL_EXPORT';
INSERT INTO sys_definition_messages_display VALUES ('U_PDF_FILE_DOWNLOAD_ALL_EXPORT','Click here to download the file containing all Dashboards');

-- 06/12/2011 ACS DE HTTPS support
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCT_NO_PROTOCOL';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCT_NO_PROTOCOL','Product must authorize one protocol at least. Please allow one of HTTP or HTTPS protocol.');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCT_HTTPS_NOT_SUPPORTED';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCT_HTTPS_NOT_SUPPORTED','Slave product doesn''t support HTTPS protocol.<br />Please choose HTTP protocol or update the slave product.');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCT_PROTOCOL_TITLE';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCT_PROTOCOL_TITLE','Protocol');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCT_USE_HTTP';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCT_USE_HTTP','Use HTTP in external links');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCT_USE_HTTPS';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCT_USE_HTTPS','Use HTTPS in external links');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCT_ALLOW_HTTP';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCT_ALLOW_HTTP','Reachable with HTTP protocol');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCT_ALLOW_HTTPS';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCT_ALLOW_HTTPS','Reachable with HTTPS protocol');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCT_HTTPS_PORT';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCT_HTTPS_PORT','HTTPS port');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCT_PROTOCOL_TEST';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCT_PROTOCOL_TEST','Test protocol connection');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCT_PROTOCOL_CONN_FAILED';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCT_PROTOCOL_CONN_FAILED','Connection unavailable with $1 protocol');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCT_PROTOCOL_CONN_FAILED_PORT';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCT_PROTOCOL_CONN_FAILED_PORT','Connection unavailable with $1 protocol on port $2');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCTS_FORM_PROTOCOL_PORT';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCTS_FORM_PROTOCOL_PORT','Please, enter a correct protocol port number');

-- 13/12/2011 ACS BZ 24853 Impossible to compute hours retreived after having activated a counter
DELETE FROM sys_definition_messages_display WHERE id = 'A_COUNTER_ACTIVATION_FORBIDDEN_RETRIEVE_COMPUTE';
INSERT INTO sys_definition_messages_display VALUES ('A_COUNTER_ACTIVATION_FORBIDDEN_RETRIEVE_COMPUTE', 'Warning: counters can''t be selected between "Retrieve" and "Compute" processes. Please wait for the end of the upcoming "Compute" and retry.');

-- 05/12/2011 BBX
-- BZ 24843
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROCESS_BACKUP_DB_FAILED';
INSERT INTO sys_definition_messages_display VALUES ('A_PROCESS_BACKUP_DB_FAILED','Backup failed !');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROCESS_BACKUP_DB_DB_SIZE';
INSERT INTO sys_definition_messages_display VALUES ('A_PROCESS_BACKUP_DB_DB_SIZE','Database size : $1 MB');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROCESS_BACKUP_DB_WRONG_DIR';
INSERT INTO sys_definition_messages_display VALUES ('A_PROCESS_BACKUP_DB_WRONG_DIR','$1 is not a valid directory');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROCESS_BACKUP_DB_ENOUGH_SPACE';
INSERT INTO sys_definition_messages_display VALUES ('A_PROCESS_BACKUP_DB_ENOUGH_SPACE','$1 MB space left on device. Backuping...');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROCESS_BACKUP_DB_PROCESS_RUNNING';
INSERT INTO sys_definition_messages_display VALUES ('A_PROCESS_BACKUP_DB_PROCESS_RUNNING','Backup cannot proceed because a process is currently running');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROCESS_BACKUP_DB_CANNOT_WRITE';
INSERT INTO sys_definition_messages_display VALUES ('A_PROCESS_BACKUP_DB_CANNOT_WRITE','Not allowed to create dump in $1');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROCESS_BACKUP_DB_PROCESS_FILE_KO';
INSERT INTO sys_definition_messages_display VALUES ('A_PROCESS_BACKUP_DB_PROCESS_FILE_KO','Could not generate process file $1');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROCESS_BACKUP_DB_PROCESS_FILE_OK';
INSERT INTO sys_definition_messages_display VALUES ('A_PROCESS_BACKUP_DB_PROCESS_FILE_OK','Writing process file $1');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROCESS_BACKUP_DB_SUCCESS';
INSERT INTO sys_definition_messages_display VALUES ('A_PROCESS_BACKUP_DB_SUCCESS','Backup successfully created');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROCESS_BACKUP_DB_EXISTS';
INSERT INTO sys_definition_messages_display VALUES ('A_PROCESS_BACKUP_DB_EXISTS','Backup already done today $1');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROCESS_BACKUP_DB_PROCESS_FILE_DELETED';
INSERT INTO sys_definition_messages_display VALUES ('A_PROCESS_BACKUP_DB_PROCESS_FILE_DELETED','Process file $1 has been successfully deleted');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROCESS_BACKUP_DB_PROCESS_FILE_NOT_DELETED';
INSERT INTO sys_definition_messages_display VALUES ('A_PROCESS_BACKUP_DB_PROCESS_FILE_NOT_DELETED','Process file $1 has NOT been deleted');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROCESS_BACKUP_DB_PROCESS_FILE_NOT_DELETED';
INSERT INTO sys_definition_messages_display VALUES ('A_PROCESS_BACKUP_DB_PROCESS_FILE_NOT_DELETED','Process file $1 has NOT been deleted');

-- 23/03/2012 BBX BZ 26044
DELETE FROM sys_definition_messages_display WHERE id = 'A_MAPPING_COUNTERS_COUNTER_USED_IN_GRAPH';
INSERT INTO sys_definition_messages_display VALUES ('A_MAPPING_COUNTERS_COUNTER_USED_IN_GRAPH','Counter $1 cannot be deactivated because it is used in the following graphs :');

-- 30/03/2012 BBX BZ 26521
DELETE FROM sys_definition_messages_display WHERE id = 'A_SYSTEM_GET_ADR_SERVER_FAILSAFE';
INSERT INTO sys_definition_messages_display VALUES ('A_SYSTEM_GET_ADR_SERVER_FAILSAFE','Function get_adr_server() : could not fetch ip address from database. $1 used.');

-- 13/04/2012 BBX BZ 20585
DELETE FROM sys_definition_messages_display WHERE id = 'A_AUTOMATIC_MAPPING_MK_NOT_AVAILABLE';
INSERT INTO sys_definition_messages_display VALUES ('A_AUTOMATIC_MAPPING_MK_NOT_AVAILABLE','Automatic Mapping not available for Mixed KPI.<br />Counter ank Kpi management are available in menu <a href="../../../../mixed_kpi/">Setup / Setup Mixed KPI</a>.');

-- 19/04/2012 BBX BZ 25888
DELETE FROM sys_definition_messages_display WHERE id = 'SMS_SENDER_NUMBER_PROBLEM';
INSERT INTO sys_definition_messages_display VALUES ('SMS_SENDER_NUMBER_PROBLEM','Cannot use the sender number defined in <i>Setup Global Parameters</i> :<br />');

-- 07/09/2012 NSE BZ 28787
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROCESS_BACKUP_DB_RUNNING';
INSERT INTO sys_definition_messages_display VALUES ('A_PROCESS_BACKUP_DB_RUNNING','Backup on progress (size = $1)');

-- LAISSER A LA FIN !!
VACUUM ANALYSE sys_definition_messages_display;
