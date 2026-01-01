---
-- Mise a jour des messages specifiques a la version 5.0.1
---
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_CORPORATE_PAGE_TITLE';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_CORPORATE_PAGE_TITLE', 'Setup Corporate');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_CORPORATE_INFO_ACTIVATION';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_CORPORATE_INFO_ACTIVATION', 'This product is not a Corporate. Click on the button below to set it as Corporate.<br /><br /><b>Warning</b> : the current parser will be replaced with the one specific to Corporates.<br /><b><font color="red">This operation cannot be rolled back.</font></b>');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_CORPORATE_ACTIVATION_BUTTON';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_CORPORATE_ACTIVATION_BUTTON', 'Activate');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_CORPORATE_CONFIRM_ACTIVATION';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_CORPORATE_CONFIRM_ACTIVATION', 'Are you sure you want to activate the Corporate mode ?');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_CORPORATE_ACTIVATION_SUCCESS';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_CORPORATE_ACTIVATION_SUCCESS', 'The Corporate has been successfully activated');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_CORPORATE_ACTIVATION_ERROR';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_CORPORATE_ACTIVATION_ERROR', 'An error occurred during activation');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_CORPORATE_INFO_FAMILY_CONF';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_CORPORATE_INFO_FAMILY_CONF', 'Please, configure the Corporate families and press "Save" to deploy the configuration.<br /><u>Notice</u> : if you choose to export Kpis, they will be integrated as raw counters in the Corporate.<br /><hr />Once families are configured, <b><font color="red">do not forget to configure the connections to your affiliates</font></b> if not already done.<br />');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_CORPORATE_LABEL_FAMILY';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_CORPORATE_LABEL_FAMILY', 'Family');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_CORPORATE_LABEL_TA_MIN';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_CORPORATE_LABEL_TA_MIN', 'Minimal Time Aggregation for Corporate Application');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_CORPORATE_LABEL_NA_MIN';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_CORPORATE_LABEL_NA_MIN', 'Minimal Network Aggregation (1st axis)');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_CORPORATE_LABEL_NA_MIN_AXE3';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_CORPORATE_LABEL_NA_MIN_AXE3', 'Minimal Network Aggregation (3d axis)');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_CORPORATE_LABEL_SUPER_NET';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_CORPORATE_LABEL_SUPER_NET', 'Super Network');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_CORPORATE_LABEL_DATA_TYPE';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_CORPORATE_LABEL_DATA_TYPE', 'Data to export');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_CORPORATE_SAVE_SUCCESS';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_CORPORATE_SAVE_SUCCESS', 'The configuration has been successfully deployed');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_CORPORATE_SAVE_ERROR';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_CORPORATE_SAVE_ERROR', 'An error occurred during application of the configuration');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_CORPORATE_INFO_SETUP_CO_LINK';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_CORPORATE_INFO_SETUP_CO_LINK', 'Click here to acess the "Setup Connection" interface.');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_CORPORATE_CONFIRM_DEPLOY';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_CORPORATE_CONFIRM_DEPLOY', E'Are you sure you want to apply this configuration ?\\nAll data related to lower Network levels than the one you selected will no longer be accessible !');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_CORPORATE_CONFIRM_NEW_SN';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_CORPORATE_CONFIRM_NEW_SN', E'Are you sure you want to change Super Network ?\\nAll data based on the previous one will be lost !');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_CORPORATE_SN_CANNOT_BEGIN_NUM';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_CORPORATE_SN_CANNOT_BEGIN_NUM', 'Super Network name / label must not begin by a number.');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_CORPORATE_SN_INCORRECT';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_CORPORATE_SN_INCORRECT', 'Super Network label is incorrect');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_CORPORATE_ERROR_DATA_CHECK';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_CORPORATE_ERROR_DATA_CHECK', 'Please, select at least one Data type to export for family ');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_CORPORATE_ERROR_NO_CONNECTION';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_CORPORATE_ERROR_NO_CONNECTION', 'No connection defined');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_CORPORATE_ERROR_DATA_EXPORT';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_CORPORATE_ERROR_DATA_EXPORT', 'Error while sending the Data Export configuration to affiliate "$1".<br />Please check that the directory corresponds to /home/application_ta/upload/export_files_corporate and <u>is not write-protected</u>.');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_CONTEXT_MOUNT_UNABLE_MOUNT';
INSERT INTO sys_definition_messages_display VALUES ('A_E_CONTEXT_MOUNT_UNABLE_MOUNT', 'Unable to mount the context because no product corresponds to it');
-- 10:01 16/10/2009 SCT : ajout du message pour le fichier manquant en mode day (BZ 12055)
DELETE FROM sys_definition_messages_display WHERE id = 'A_PARSER_NO_TYPE_FILE_FOR_DAY';
INSERT INTO sys_definition_messages_display VALUES ('A_PARSER_NO_TYPE_FILE_FOR_DAY', 'No $1 file for day $2');

-- 12:07 22/10/2009 : Ajout des messages pour la gestion de trx et charge en topologie
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_UPLOAD_TOPO_NA_MIN_IN_FILE';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_UPLOAD_TOPO_NA_MIN_IN_FILE',E'You can\'t upload a file without the Network Aggregation $1 and with $2');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_UPLOAD_TOPO_NA_MIN_IN_FILE';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_UPLOAD_TOPO_NA_MIN_IN_FILE',E'You can\'t upload a file without the Network Aggregation $1 and with $2');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_UPLOAD_TOPO_ON_OFF_INVALID';
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_UPLOAD_TOPO_COLUMN_BOOLEAN_INVALID';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_UPLOAD_TOPO_COLUMN_BOOLEAN_INVALID','$1 is different from 0 or 1 [lines :  $2]');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_UPLOAD_TOPO_ON_OFF_IS_NULL';
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_UPLOAD_TOPO_COLUMN_BOOLEAN_IS_NULL';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_UPLOAD_TOPO_COLUMN_BOOLEAN_IS_NULL','$1 is null (0 or 1 required) [lines :  $2]');

DELETE FROM sys_definition_messages_display WHERE id = 'A_TOPO_LABEL_PARAMETER_CHARGE';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_TOPO_LABEL_PARAMETER_CHARGE','Charge');

DELETE FROM sys_definition_messages_display WHERE id = 'A_TOPO_LABEL_PARAMETER_ON_OFF';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_TOPO_LABEL_PARAMETER_ON_OFF','On/Off');

-- Message pour Setup Mixed Kpi
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_ONE_PRODUCT';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_SETUP_MIXED_ONE_PRODUCT','You cannot activate Mixed KPI product because there is standalone product.');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_BUTTON_ACTIVATION';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_SETUP_MIXED_KPI_BUTTON_ACTIVATION','Activation of Mixed KPI product ');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_INFO_ACTIVATION';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_SETUP_MIXED_KPI_INFO_ACTIVATION','The Mixed KPI product is not active. Click on the button below to activate the Mixed KPI product.<br /><br /><b>Warning</b> : It will be impossible to uninstall it.');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_BUTTON_CONFIRMATION_ACTIVATION';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_SETUP_MIXED_KPI_BUTTON_CONFIRMATION_ACTIVATION','Confirm the activation of Mixed KPI product');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_SETUP_MIXED_KPI_ERROR_TEMPLATE1';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_SETUP_MIXED_KPI_ERROR_TEMPLATE1','ERROR: source database "template1" is being accessed by $1 users. Creation of the database for Mixed KPI product unable.<br />You can verify if no body is connected on Postgres with PgAdmin or restart Postgres (Stop process before)');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_INFO_CONFIRMATON';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_SETUP_MIXED_KPI_INFO_CONFIRMATON','Installation of Mixed KPI product will restart all the processes. Check that all processes have been stopped correctly. <br />Be careful, this installation may increase processing time for all applications installed on this server.');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_TITLE_DISK_SPACE_BDD';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_SETUP_MIXED_KPI_TITLE_DISK_SPACE_BDD','Disk space for database :');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_TITLE_DISK_SPACE_FILE';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_SETUP_MIXED_KPI_TITLE_DISK_SPACE_FILE','Disk space for the application :');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_LABEL_FREE';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_SETUP_MIXED_KPI_LABEL_FREE','Free : $1');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_LABEL_RECOMMEND';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_SETUP_MIXED_KPI_LABEL_RECOMMEND','Recommend : $1');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_INFO_DISK_SPACE';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_SETUP_MIXED_KPI_INFO_DISK_SPACE','It must having enough space disk for all applications make correctly on this server. You can activate Mixed KPI product even if it is not enough space disk but it possible to have one saturation of space disk');

-- maj 06/05/2010 MPR - Correction du BZ 15187 - Message d'erreur plus explicite
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_SETUP_MIXED_KPI_ERROR_DURING_ACTIVATION';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_SETUP_MIXED_KPI_ERROR_DURING_ACTIVATION','Error during the activation of Mixed KPI product.<br />$1');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_WARNING_ACTIVATION_TAKE_FEW_MINUTES';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_SETUP_MIXED_KPI_WARNING_ACTIVATION_TAKE_FEW_MINUTES','<span style="color:red"><b>Warning : </b> The activation will take a few minutes</span>');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_SETUP_MIXED_KPI_FAMILY_LABEL_ALREADY_EXISTS';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_SETUP_MIXED_KPI_FAMILY_LABEL_ALREADY_EXISTS','This label is already used by another family.');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_SETUP_MIXED_KPI_FAMILY_LABEL_EMPTY';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_SETUP_MIXED_KPI_FAMILY_LABEL_EMPTY','You must enter a family name.');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_SETUP_MIXED_KPI_NO_FAMILY_SELECTED';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_SETUP_MIXED_KPI_NO_FAMILY_SELECTED','You must select at least one family.');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_SETUP_MIXED_KPI_NO_NA_MIN_SELECTED';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_SETUP_MIXED_KPI_NO_NA_MIN_SELECTED','You must select one minimum level.');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_SETUP_MIXED_KPI_NO_NA_SOURCE_SELECTED';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_SETUP_MIXED_KPI_NO_NA_SOURCE_SELECTED','You must select the source agregation for $1.');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_SETUP_MIXED_KPI_PATH_NA_INVALID';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_SETUP_MIXED_KPI_PATH_NA_INVALID','Your aggregation paths are invalid.');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_SETUP_MIXED_KPI_NO_RAW_SELECTED';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_SETUP_MIXED_KPI_NO_RAW_SELECTED','You must select at least one counter.');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_SETUP_MIXED_ADD_AUTO_COUNTER';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_SETUP_MIXED_ADD_AUTO_COUNTER','One or several counters have been automaticaly added because they are used in formulas or default values.');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_UPDATE_TA';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_SETUP_MIXED_UPDATE_TA','The minimum Time Aggregation has been updated.');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_INFO_COMPTUTE_MODE';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_SETUP_MIXED_INFO_COMPTUTE_MODE','The product Mixed KPI is in compute mode "$1". ');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_SETUP_MIXED_ALL_PRODUCTS_NOT_SAME_COMPTUTE_MODE';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_SETUP_MIXED_ALL_PRODUCTS_NOT_SAME_COMPTUTE_MODE','Be careful : for all products which are not in same compute mode, data cannot be integrate :');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_FAMILY_CONF';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_FAMILY_CONF', 'You can configure the Mixed Kpi families below. To setup connections to your other products, follow the link hereafter :');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_LABEL_TA_MIN';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_LABEL_TA_MIN', 'Minimal Time Aggregation for this family');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_FAMILY_LABEL';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_FAMILY_LABEL', 'Family label');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_INFO_SETUP_CO_LINK';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_INFO_SETUP_CO_LINK', 'Mixed Kpi setup product');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_LABEL_LEVEL';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_LABEL_LEVEL', 'NA name');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_LABEL_LEVEL_MIN';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_LABEL_LEVEL_MIN', 'Use the level as min');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_LABEL_LEVEL_USED';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_LABEL_LEVEL_USED', 'Include the level');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_LABEL_AGGREGATION';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_LABEL_AGGREGATION', 'NA source for Aggregatio');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_CHOOSE_AGGREGATION';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_CHOOSE_AGGREGATION', 'Select the network aggregation');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_BACK_TO_MAIN';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_BACK_TO_MAIN', 'Back to main page');
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_SETUP_MIXED_KPI_FAMILY_UPDATED';
INSERT INTO sys_definition_messages_display VALUES ('A_E_SETUP_MIXED_KPI_FAMILY_UPDATED', 'Family updated');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_SETUP_MIXED_KPI_NO_FAMILY_CREATION_FAILLED';
INSERT INTO sys_definition_messages_display VALUES ('A_E_SETUP_MIXED_KPI_NO_FAMILY_CREATION_FAILLED', 'Family creation failed');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_FAMILY_NAME';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_FAMILY_NAME', 'Family name');
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_SETUP_MIXED_KPI_NA_MIN';
INSERT INTO sys_definition_messages_display VALUES ('A_E_SETUP_MIXED_KPI_NA_MIN', 'Na min');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_ACTIONS';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_ACTIONS', 'Actions');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_BT_ADD_FAMILY';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_BT_ADD_FAMILY', 'Add a family');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_BT_ACTION_EDIT_FAMILY';
-- 10:52 11/12/2009 GHX
-- Correction du BZ 13138 [REC][T&A CB 5.0]: mettre des majuscules au mot edit pour que la sï¿½paration entre les actions soit + visible
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_BT_ACTION_EDIT_FAMILY', 'Edit family');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_BT_ACTION_EDIT_NA';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_BT_ACTION_EDIT_NA', 'Edit NA');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_BT_ACTION_EDIT_RAW';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_BT_ACTION_EDIT_RAW', 'Edit counters');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_BT_ACTION_EDIT_KPI';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_BT_ACTION_EDIT_KPI', 'Edit Kpis');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_BT_ACTION_DELETE';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_BT_ACTION_DELETE', 'Delete');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_EDIT_FAMILY';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_EDIT_FAMILY', 'Edit family properties');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_SELECT_NA_FAMILIES';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_SELECT_NA_FAMILIES', 'Select the NA families over the different installed products.');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_FAMILY_LIST';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_FAMILY_LIST', 'Mixed KPI family list');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_NA_SELECTION';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_NA_SELECTION', 'NA selection');
-- 22/03/2010 BBX : correction du message
DELETE FROM sys_definition_messages_display WHERE id = 'A_H_SETUP_MIXED_KPI_ACTIVE_RAW_BY_SELECTING';
INSERT INTO sys_definition_messages_display VALUES ('A_H_SETUP_MIXED_KPI_ACTIVE_RAW_BY_SELECTING', 'To activate/desactivate a counter, select it in the list and use the right/left arrow.');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_COUNTERS_SELECTION';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_COUNTERS_SELECTION', 'Counters selection');
-- 22/03/2010 BBX : correction du message
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_ACTIVATE_RAWS';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_ACTIVATE_RAWS', 'Activate/desactivate counters.');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_KPIS_SELECTION';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_KPIS_SELECTION', 'KPI selection');
-- 22/03/2010 BBX : correction du message
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_ACTIVATE_KPIS';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_ACTIVATE_KPIS', 'To activate/desactivate a Kpi, select it in the list and use the right/left arrow.');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_BT_FAMILY_SAVE';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_BT_FAMILY_SAVE', 'save & configure NA');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_BT_SAVE_TA_MIN';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_BT_SAVE_TA_MIN', 'Save');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_BT_EDIT_CURRENT_FAMILY';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_BT_EDIT_CURRENT_FAMILY', 'Edit current family');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_BT_SAVE_EDIT_NA';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_BT_SAVE_EDIT_NA', 'Save');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_FAMILY_DELETED';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_FAMILY_DELETED', 'The family has been deleted');
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_SETUP_MIXED_KPI_FAMILY_NOT_DELETED';
INSERT INTO sys_definition_messages_display VALUES ('A_E_SETUP_MIXED_KPI_FAMILY_NOT_DELETED', 'Error while family deletion');


-- KPI BUILDER
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_KPI_BUILDER_UPLOAD_NO_COLUMN_IN_COMMON';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_KPI_BUILDER_UPLOAD_NO_COLUMN_IN_COMMON','No column in common between the file and the table');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_KPI_BUILDER_UPLOAD_NO_COLUMN_KPI_NAME';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_KPI_BUILDER_UPLOAD_NO_COLUMN_KPI_NAME','You must have the column "kpi_name" in file');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_KPI_BUILDER_UPLOAD_FORMULA_INVALID';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_KPI_BUILDER_UPLOAD_FORMULA_INVALID','KPIs following have been desactivated because the formulas are invalid : <br />');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_KPI_BUILDER_UPLOAD_EDW_GROUP_TABLE_INVALID';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_KPI_BUILDER_UPLOAD_EDW_GROUP_TABLE_INVALID','There are incorrect values in the column "edw_group_table" : <br />');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_KPI_BUILDER_UPLOAD_IGNORED_COLUMNS';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_KPI_BUILDER_UPLOAD_IGNORED_COLUMNS','The following columns are ignored : <br />');

DELETE FROM sys_definition_messages_display WHERE id = 'A_KPI_BUILDER_NB_KPI_UPDATED';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_KPI_BUILDER_NB_KPI_UPDATED','Number of updated KPIs : $1');

DELETE FROM sys_definition_messages_display WHERE id = 'A_KPI_BUILDER_NB_KPI_NEW';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_KPI_BUILDER_NB_KPI_NEW','Number of new KPI : $1');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_KPI_BUILDER_UPLOAD_KPI_NAME_NOT_UNIQUE';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_KPI_BUILDER_UPLOAD_KPI_NAME_NOT_UNIQUE','KPI name not unique');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_KPI_BUILDER_UPLOAD_SPACE_IN_KPI_NAME';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_KPI_BUILDER_UPLOAD_SPACE_IN_KPI_NAME','You cannot have a space in KPI name :');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_KPI_BUILDER_UPLOAD_EMPTY_KPI_NAME';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_KPI_BUILDER_UPLOAD_EMPTY_KPI_NAME','You cannot have KPI name empty');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_KPI_BUILDER_UPLOAD_KPI_NAME_INVALID';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_KPI_BUILDER_UPLOAD_KPI_NAME_INVALID','The following KPI names are invalid :');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_KPI_BUILDER_UPLOAD_KPI_NAME_INVALID_CANNOT_START_NUMERIC';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_KPI_BUILDER_UPLOAD_KPI_NAME_INVALID_CANNOT_START_NUMERIC','KPI name cannot start with a number :');

-- maj 16:23 26/10/2009 - MPR : Ajout d'un message d'info dans le module  de topologie indiquant que les caractï¿½res spï¿½ciaux sont remplacï¿½s par des _
DELETE FROM sys_definition_messages_display WHERE id = 'A_UPLOAD_TOPO_MSG_INFO';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_UPLOAD_TOPO_MSG_INFO',E'Special chars \\'' &#34; \/ \\\\ # are replaced by _');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_LABEL_AGGREGATION';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_LABEL_AGGREGATION', 'NA source for Aggregation');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_CAN_NOT_DESELECT_MIN_LEVEL';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_CAN_NOT_DESELECT_MIN_LEVEL', 'You can not deselect the level you are using as min level. This level is obligatorily aggragated on itself.');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_CHANGE_MIN_LEVEL_LOOSE_DATA';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_CHANGE_MIN_LEVEL_LOOSE_DATA', 'Once defined, changing min level and selected level can lead to loose data.');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_DESELECT_FAMILY_WITH_RAW_KPI';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_DESELECT_FAMILY_WITH_RAW_KPI', 'You can not deselect a family if it owns an activated Raw or Kpi (the checkbox is disabled).');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_LABEL_TA_MIN';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_LABEL_TA_MIN', 'Minimal Time Aggregation of this product: ');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_TA_MIN_CONF';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_TA_MIN_CONF', 'You can modify the minimal Time Aggregation of the product. However this can lead to loose data.');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_SETUP_MIXED_KPI_NO_KPI_SELECTED';
INSERT INTO sys_definition_messages_display VALUES ('A_E_SETUP_MIXED_KPI_NO_KPI_SELECTED', 'You have to select at least one Kpi.');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_JS_CONFIRM_DELETE_FAMILY';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_JS_CONFIRM_DELETE_FAMILY', 'Are you sure to want to delete this family?');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_SETUP_MIXED_KPI_LOOSE_DATA';
INSERT INTO sys_definition_messages_display VALUES ('A_E_SETUP_MIXED_KPI_LOOSE_DATA', 'This can lead to loose data.');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_BT_SELECT_DASHBOARDS';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_BT_SELECT_DASHBOARDS', 'Select Dashboards');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_CAN_NOT_DESELECT_MIN_LEVEL';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_CAN_NOT_DESELECT_MIN_LEVEL', 'You can not deselect the level you are using as min level. This level is obligatorily aggregated on itself.');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_CHECKBOX_CHOOSE_FAMILY';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_CHECKBOX_CHOOSE_FAMILY', 'If checked, Network aggregation levels from this family will be available in your Mixed KPI family.');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_HELP_FAMILY_GENERAL_INFO';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_HELP_FAMILY_GENERAL_INFO', 'General information');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_FAMILY_CONF';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_FAMILY_CONF', 'You can configure the Mixed Kpi families below.');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_HELP_FAMILY_CHANGE_LABEL';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_HELP_FAMILY_CHANGE_LABEL', 'You can change the label of your family with the link "edit family".');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_HELP_FAMILY_SELECT_FAMILIES_FROM_PRODUCTS';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_HELP_FAMILY_SELECT_FAMILIES_FROM_PRODUCTS', 'You can select the families from each product with the link "edit family".');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_HELP_FAMILY_SELECT_NA';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_HELP_FAMILY_SELECT_NA', 'You can select the Network Aggregation levels to use with the link "edit NA".');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_HELP_FAMILY_NA_ERRORS_RETRIEVE';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_HELP_FAMILY_NA_ERRORS_RETRIEVE', 'If you do not select the Network Aggregation levels you will get errors while the retrieve process.');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_HELP_FAMILY_CANT_EDIT_RAW_NO_NA';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_HELP_FAMILY_CANT_EDIT_RAW_NO_NA', 'You can not edit counters or kpis if you have not selected its Network Aggregation.');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_HELP_SETUP_CONNECTION_TO_PRODUCTS';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_HELP_SETUP_CONNECTION_TO_PRODUCTS', 'To setup connections to your other products, follow the link hereafter:');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_INFO_SETUP_CO_LINK';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_INFO_SETUP_CO_LINK', 'Mixed Kpi setup connection');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_HELP_FAMILY_MIN_TA';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_HELP_FAMILY_MIN_TA', 'Minimal Time Aggregation');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_HELP_FAMILY_MODIFY_TA_MIN';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_HELP_FAMILY_MODIFY_TA_MIN', 'You can modify the minimal Time Aggregation of the product.');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_HELP_FAMILY_TA_MIN_LOOSE_DATA';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_HELP_FAMILY_TA_MIN_LOOSE_DATA', 'However this can lead to loose data.');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_HELP_FAMILY_TA_MIN_WHOLE_PRODUCT';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_HELP_FAMILY_TA_MIN_WHOLE_PRODUCT', 'The minimal Time Aggregation is defined for your whole Mixed KPI Product.');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_AVAILABLE_COUNTERS';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_AVAILABLE_COUNTERS', 'Available counters');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_SELECTED_COUNTERS';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_SELECTED_COUNTERS', 'Selected counters');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_AVAILABLE_KPIS';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_AVAILABLE_KPIS', 'Available KPIs');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_SELECTED_KPIS';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_SELECTED_KPIS', 'Selected KPIs');

DELETE FROM sys_definition_messages_display WHERE id = 'E_SETUP_MIXED_KPI_SELECT_NA_FOR_ALL_FAMILIES';
INSERT INTO sys_definition_messages_display VALUES ('E_SETUP_MIXED_KPI_SELECT_NA_FOR_ALL_FAMILIES', 'Your families are not correctly configured. Be sure to select Network Aggregation levels for all your families.');


DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_FIELDSET_SELECT_DASHBOARDS';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_FIELDSET_SELECT_DASHBOARDS', 'Selected Dashboards');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_FIELDSET_SELECT_DASHBOARDS_INFO_COLOR_CODE_RAW_KPI';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_FIELDSET_SELECT_DASHBOARDS_INFO_COLOR_CODE_RAW_KPI', 'Color code for Raw counters and KPIs');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_FIELDSET_SELECT_DASHBOARDS_INFO_COLOR_CODE_GPD';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_FIELDSET_SELECT_DASHBOARDS_INFO_COLOR_CODE_GPD', 'Color code for Graphs, Pies and Dashboards');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_FIELDSET_SELECT_DASHBOARDS_INFO_COLOR_CODE_RAW_KPI_KNOWN';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_FIELDSET_SELECT_DASHBOARDS_INFO_COLOR_CODE_RAW_KPI_KNOWN', 'All Raw counters and KPIs with the color <span class="complet">green</span> are present in Mixed KPI product.');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_FIELDSET_SELECT_DASHBOARDS_INFO_COLOR_CODE_RAW_KPI_UNKNOWN';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_FIELDSET_SELECT_DASHBOARDS_INFO_COLOR_CODE_RAW_KPI_UNKNOWN', 'All Raw counters and KPIs with the color <span class="unknown">red</span> are <b>not</b> present in Mixed KPI product.');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_FIELDSET_SELECT_DASHBOARDS_INFO_COLOR_CODE_GPD_COMPLET';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_FIELDSET_SELECT_DASHBOARDS_INFO_COLOR_CODE_GPD_COMPLET', 'Items in <span class="complet">green</span> are complete, no missing Raw counters or KPIs.');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_FIELDSET_SELECT_DASHBOARDS_INFO_COLOR_CODE_GPD_INCOMPLET';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_FIELDSET_SELECT_DASHBOARDS_INFO_COLOR_CODE_GPD_INCOMPLET', 'Items in <span class="incomplet">orange</span> have at least one missing Raw counters or KPIs.');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_FIELDSET_SELECT_DASHBOARDS_INFO_COLOR_CODE_GPD_UNKNOWN';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_FIELDSET_SELECT_DASHBOARDS_INFO_COLOR_CODE_GPD_UNKNOWN', 'Items in <span class="unknown">red</span> have no known Raw counters or KPIs');

-- 06/11/2009 BBX
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_CORPORATE_PLEASE_SAVE';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_CORPORATE_PLEASE_SAVE', 'Please, configure the Corporate families and press "Save" to deploy the configuration');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_UNABLE_CREATE_GRAPH';
INSERT INTO sys_definition_messages_display VALUES ('A_E_UNABLE_CREATE_GRAPH', 'Unable to create graph');

-- 10/11/2009 MPR - Correction du bug 12621
DELETE FROM sys_definition_messages_display WHERE id = 'A_SCHEDULE_ERROR_RENAME_FILE';
INSERT INTO sys_definition_messages_display VALUES ('A_SCHEDULE_ERROR_RENAME_FILE', E'\n Unable to generate the document \n $1, \n please contact your administrator');

-- 16/11/2009 BBX : ajout des messages Download Log
DELETE FROM sys_definition_messages_display WHERE id = 'A_DOWNLOAD_LOG_APPLICATION_DAEMON_LABEL';
INSERT INTO sys_definition_messages_display (id, text)
VALUES ('A_DOWNLOAD_LOG_APPLICATION_DAEMON_LABEL','Application Daemon');

DELETE FROM sys_definition_messages_display WHERE id = 'A_DOWNLOAD_LOG_TRACELOG_LABEL';
INSERT INTO sys_definition_messages_display (id, text)
VALUES ('A_DOWNLOAD_LOG_TRACELOG_LABEL','Tracelog');

DELETE FROM sys_definition_messages_display WHERE id = 'A_DOWNLOAD_LOG_TOPOLOGY_DAEMON_LABEL';
INSERT INTO sys_definition_messages_display (id, text)
VALUES ('A_DOWNLOAD_LOG_TOPOLOGY_DAEMON_LABEL','Topology Daemon');

DELETE FROM sys_definition_messages_display WHERE id = 'A_DOWNLOAD_LOG_TOPOLOGY_STATS_LABEL';
INSERT INTO sys_definition_messages_display (id, text)
VALUES ('A_DOWNLOAD_LOG_TOPOLOGY_STATS_LABEL','Topology Statistics');

DELETE FROM sys_definition_messages_display WHERE id = 'A_DOWNLOAD_LOG_VERSION_HISTORY_LABEL';
INSERT INTO sys_definition_messages_display (id, text)
VALUES ('A_DOWNLOAD_LOG_VERSION_HISTORY_LABEL','Version History');

DELETE FROM sys_definition_messages_display WHERE id = 'A_DOWNLOAD_LOG_PARTITION_STATS_LABEL';
INSERT INTO sys_definition_messages_display (id, text)
VALUES ('A_DOWNLOAD_LOG_PARTITION_STATS_LABEL','Partition Statistics');

DELETE FROM sys_definition_messages_display WHERE id = 'A_DOWNLOAD_LOG_GLOBAL_PARAMETERS_LABEL';
INSERT INTO sys_definition_messages_display (id, text)
VALUES ('A_DOWNLOAD_LOG_GLOBAL_PARAMETERS_LABEL','Global Parameters');

DELETE FROM sys_definition_messages_display WHERE id = 'A_DOWNLOAD_LOG_FILE_PERMISSIONS_LABEL';
INSERT INTO sys_definition_messages_display (id, text)
VALUES ('A_DOWNLOAD_LOG_FILE_PERMISSIONS_LABEL','File permissions');

DELETE FROM sys_definition_messages_display WHERE id = 'A_DOWNLOAD_LOG_FRAME_LABEL';
INSERT INTO sys_definition_messages_display (id, text)
VALUES ('A_DOWNLOAD_LOG_FRAME_LABEL','Download Log');

DELETE FROM sys_definition_messages_display WHERE id = 'A_DOWNLOAD_LOG_DATE_PERIOD_LABEL';
INSERT INTO sys_definition_messages_display (id, text)
VALUES ('A_DOWNLOAD_LOG_DATE_PERIOD_LABEL','Date / Period');

DELETE FROM sys_definition_messages_display WHERE id = 'A_DOWNLOAD_LOG_CONTENT_LABEL';
INSERT INTO sys_definition_messages_display (id, text)
VALUES ('A_DOWNLOAD_LOG_CONTENT_LABEL','Content');

DELETE FROM sys_definition_messages_display WHERE id = 'A_DOWNLOAD_LOG_UNTIL_LABEL';
INSERT INTO sys_definition_messages_display (id, text)
VALUES ('A_DOWNLOAD_LOG_UNTIL_LABEL','Until');

DELETE FROM sys_definition_messages_display WHERE id = 'A_DOWNLOAD_LOG_DOWNLOAD_BUTTON';
INSERT INTO sys_definition_messages_display (id, text)
VALUES ('A_DOWNLOAD_LOG_DOWNLOAD_BUTTON','Download');

DELETE FROM sys_definition_messages_display WHERE id = 'A_DOWNLOAD_LOG_GENERATING_MESSAGE';
INSERT INTO sys_definition_messages_display (id, text)
VALUES ('A_DOWNLOAD_LOG_GENERATING_MESSAGE','Generating log...');

DELETE FROM sys_definition_messages_display WHERE id = 'A_DOWNLOAD_LOG_ALL_PRODUCTS';
INSERT INTO sys_definition_messages_display (id, text)
VALUES ('A_DOWNLOAD_LOG_ALL_PRODUCTS','All Products');

DELETE FROM sys_definition_messages_display WHERE id = 'A_DOWNLOAD_LOG_NOTHING_TO_EXPORT';
INSERT INTO sys_definition_messages_display (id, text)
VALUES ('A_DOWNLOAD_LOG_NOTHING_TO_EXPORT','Nothing to export');

DELETE FROM sys_definition_messages_display WHERE id = 'A_DOWNLOAD_LOG_ERROR_OCCURED';
INSERT INTO sys_definition_messages_display (id, text)
VALUES ('A_DOWNLOAD_LOG_ERROR_OCCURED','An error occurred during file generation. Try to lower the period.');

DELETE FROM sys_definition_messages_display WHERE id = 'A_DOWNLOAD_LOG_APPLICATION_DAEMON_COMMENT';
INSERT INTO sys_definition_messages_display (id, text)
VALUES ('A_DOWNLOAD_LOG_APPLICATION_DAEMON_COMMENT','Adds the split HTML application deamons for the selected period to the archive');

DELETE FROM sys_definition_messages_display WHERE id = 'A_DOWNLOAD_LOG_TRACELOG_COMMENT';
INSERT INTO sys_definition_messages_display (id, text)
VALUES ('A_DOWNLOAD_LOG_TRACELOG_COMMENT','Adds the tracelog for the selected period to the archive');

DELETE FROM sys_definition_messages_display WHERE id = 'A_DOWNLOAD_LOG_TOPOLOGY_DAEMON_COMMENT';
INSERT INTO sys_definition_messages_display (id, text)
VALUES ('A_DOWNLOAD_LOG_TOPOLOGY_DAEMON_COMMENT','Adds the HTML topology deamons for the selected period to the archive');

DELETE FROM sys_definition_messages_display WHERE id = 'A_DOWNLOAD_LOG_TOPOLOGY_STATS_COMMENT';
INSERT INTO sys_definition_messages_display (id, text)
VALUES ('A_DOWNLOAD_LOG_TOPOLOGY_STATS_COMMENT','Adds a network information file to the archive');

DELETE FROM sys_definition_messages_display WHERE id = 'A_DOWNLOAD_LOG_VERSION_HISTORY_COMMENT';
INSERT INTO sys_definition_messages_display (id, text)
VALUES ('A_DOWNLOAD_LOG_VERSION_HISTORY_COMMENT','Adds the version history file to the archive');

DELETE FROM sys_definition_messages_display WHERE id = 'A_DOWNLOAD_LOG_PARTITION_STATS_COMMENT';
INSERT INTO sys_definition_messages_display (id, text)
VALUES ('A_DOWNLOAD_LOG_PARTITION_STATS_COMMENT','Adds a disk usage information file to the archive');

DELETE FROM sys_definition_messages_display WHERE id = 'A_DOWNLOAD_LOG_GLOBAL_PARAMETERS_COMMENT';
INSERT INTO sys_definition_messages_display (id, text)
VALUES ('A_DOWNLOAD_LOG_GLOBAL_PARAMETERS_COMMENT','Adds global parameters file to the archive');

DELETE FROM sys_definition_messages_display WHERE id = 'A_DOWNLOAD_LOG_FILE_PERMISSIONS_COMMENT';
INSERT INTO sys_definition_messages_display (id, text)
VALUES ('A_DOWNLOAD_LOG_FILE_PERMISSIONS_COMMENT','Adds file permissions information file to the archive');

DELETE FROM sys_definition_messages_display WHERE id = 'A_DOWNLOAD_LOG_GLOBAL_COMMENT';
INSERT INTO sys_definition_messages_display (id, text)
VALUES ('A_DOWNLOAD_LOG_GLOBAL_COMMENT','Choose a date / period, check the data you need and click on Download to get them');

DELETE FROM sys_definition_messages_display WHERE id = 'A_DOWNLOAD_LOG_UNTIL_COMMENT';
INSERT INTO sys_definition_messages_display (id, text)
VALUES ('A_DOWNLOAD_LOG_UNTIL_COMMENT','Check to define a period');


-- 09:48 17/11/2009 GHX
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_INFO_SETECTED_DASHBOARDS';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_SETUP_MIXED_KPI_INFO_SETECTED_DASHBOARDS','You can select to automatically deploy dashboards in Mixed KPI product.<br />If some elements are not present:<ul id="info_help_selectdash"><li>They will not be deployed.</li><li>If a graph, pie or dashboard are sorted by them, they are replaced by the first element found.</li></ul>You cannot copy dashboards in which all elements are missing.');
-- 12:21 19/11/2009 GHX

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_SETUP_CORPORATE_MIXED_KPI';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_SETUP_CORPORATE_MIXED_KPI','This product cannot be activate as Corporate');

DELETE FROM sys_definition_messages_display WHERE id = 'A_KPI_BUILDER_CANNOT_DELETE_USED_IN_KPI';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_KPI_BUILDER_CANNOT_DELETE_USED_IN_KPI','You cannot delete this counter because it''s used in KPI formula');

-- 26/11/2009 BBX. BZ 13026
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_BH_FEATURE_DISABLED';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_SETUP_BH_FEATURE_DISABLED','This feature is disabled on this product');

--30/11/2009 BBX BZ 13099
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_SETUP_CORPORATE_UPDATE_KEY';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_SETUP_CORPORATE_UPDATE_KEY','The network aggregation "$1" no longer exists. Please, update the licence key before logging out');

DELETE FROM sys_definition_messages_display WHERE id = 'A_ABOUT_NA_IN_KEY_NOT_VALID';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_ABOUT_NA_IN_KEY_NOT_VALID','Agregation level of the key does not exist for this product');

-- 30/11/2009 MPR BZ12780
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_UPLOAD_TOPO_COORDS_FORMAT_ISNT_VALID';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_UPLOAD_TOPO_COORDS_FORMAT_ISNT_VALID','Coordinates Format is not valid for the $1 $2 $3<br />( longitude (real) = $3 / latitude (real) = $4 / azimuth (integer) = $5 ) <br /><br />');

-- 17:17 30/11/2009 GHX
-- Message sur la // des process
DELETE FROM sys_definition_messages_display WHERE id = 'A_FLAT_FILE_UPLOAD_ALARM_MISSING_FILE_COLLECTED_NUMBER';
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROCESS_COLLECT_FILES_BEGIN';
INSERT INTO sys_definition_messages_display VALUES ('A_PROCESS_COLLECT_FILES_BEGIN', 'PROCESS : Start collecting files');
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROCESS_COLLECT_FILES_END';
INSERT INTO sys_definition_messages_display VALUES ('A_PROCESS_COLLECT_FILES_END', 'PROCESS : End collecting files ($1 files)');

-- 01/12/2009 BBX
-- Correction d'un message. BZ 13057
DELETE FROM sys_definition_messages_display WHERE id = 'U_GTM_NO_DATA_FOUND_PIE';
INSERT INTO sys_definition_messages_display VALUES ('U_GTM_NO_DATA_FOUND_PIE', '<h1>Information :</h1>No data found for <b>$1</b><br>$2 = <b>$3</b> - $4 $5 [ Top $6 Order By <b>$7</b> $8 split with <b>$9</b>] Split by $10');
--
DELETE FROM sys_definition_messages_display WHERE id = 'U_GTM_NO_DATA_FOUND_SPLIT_BY_OT';
INSERT INTO sys_definition_messages_display VALUES ('U_GTM_NO_DATA_FOUND_SPLIT_BY_OT', '<h1>Information :</h1>No data found for <b>$1</b><br>Split by (<b>$8</b>) is NULL or no data found<br>$2 = <b>$3</b> - $4 $5 [ Order By <b>$6</b> $7 split with <b>$9</b>] Split by $10');

-- 01/12/2009 - Correction bug topo - Doublons dans les labels
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_UPLOAD_TOPO_LABEL_ALREADY_EXISTS_IN_DB';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_UPLOAD_TOPO_LABEL_ALREADY_EXISTS_IN_DB','the $1 $2 for $3 in file  already exists in database for $4');

-- 02/12/2009 - Correction du BZ 13043
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_GLOBAL_PARAMS_COMPUTE_MODE_VALUE';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_SETUP_GLOBAL_PARAMS_COMPUTE_MODE_VALUE','The compute mode must be hourly or daily');

-- 02/12/2009 BBX. BZ 13169
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_FAMILY_LABEL_NOT_CORRECT';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_SETUP_MIXED_KPI_FAMILY_LABEL_NOT_CORRECT','Please enter a correct family label');

-- 10:48 03/12/2009 GHXBZ 12979
DELETE FROM sys_definition_messages_display WHERE id = 'A_OPEN_OFFICE_BTN_LABEL_TEST';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_OPEN_OFFICE_BTN_LABEL_TEST', 'OpenOffice test');
DELETE FROM sys_definition_messages_display WHERE id = 'A_OPEN_OFFICE_BTN_LABEL_TEST_TOOLTIP';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_OPEN_OFFICE_BTN_LABEL_TEST_TOOLTIP', 'Check OpenOffice if is installed correctly on all servers');
DELETE FROM sys_definition_messages_display WHERE id = 'A_OPEN_OFFICE_TEST_PROGESSING';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_OPEN_OFFICE_TEST_PROGESSING', 'OpenOffice test in progess ... ');
DELETE FROM sys_definition_messages_display WHERE id = 'A_OPEN_OFFICE_RESULT_LABEL_COL_INSTALLED';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_OPEN_OFFICE_RESULT_LABEL_COL_INSTALLED', 'Installed');
DELETE FROM sys_definition_messages_display WHERE id = 'A_OPEN_OFFICE_RESULT_LABEL_COL_IP';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_OPEN_OFFICE_RESULT_LABEL_COL_IP', 'Server');
DELETE FROM sys_definition_messages_display WHERE id = 'A_OPEN_OFFICE_RESULT_LABEL_COL_AVAILABLE';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_OPEN_OFFICE_RESULT_LABEL_COL_AVAILABLE', 'Available');

-- 15:08 03/12/2009 GHX BZ 8887
DELETE FROM sys_definition_messages_display WHERE id = 'U_E_DASHBOARD_NO_INTERACTIVITY';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('U_E_DASHBOARD_NO_INTERACTIVITY', 'Inside chart ''navigation & data display'' was turned off due to a large number of data.');
DELETE FROM sys_definition_messages_display WHERE id = 'U_E_DASHBOARD_STOP_DISPLAY_GRAPH';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('U_E_DASHBOARD_STOP_DISPLAY_GRAPH', '$1 charts requested, loading stopped at chart number $2');

-- 16:30 03/12/2009 GHX
-- Correction du BZ 11797
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_SETUP_PRODUCTS_ALREADY_MASTER';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_SETUP_PRODUCTS_ALREADY_MASTER', 'You cannot add this product, it''s already master');
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_SETUP_PRODUCTS_ALREADY_SLAVE';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_SETUP_PRODUCTS_ALREADY_SLAVE', 'You cannot add this product, it''s already slave');

-- 17:01 03/12/2009
-- kpi builder - Ajout de Erlangb


DELETE FROM sys_definition_messages_display WHERE id = 'A_KPI_BUILDER_ERLANG_TITLE_INFO';
INSERT INTO sys_definition_messages_display (id,text) VALUES ('A_KPI_BUILDER_ERLANG_TITLE_INFO','About ErlangB');

DELETE FROM sys_definition_messages_display WHERE id = 'A_KPI_BUILDER_ERLANG_DESCRIPTION';
INSERT INTO sys_definition_messages_display (id,text) VALUES ('A_KPI_BUILDER_ERLANG_DESCRIPTION','<br />In ErlangB formula the three variables involved are Busy Hour Traffic (BHT), Blocking Probability (Grade of service) and TCH (Number of Traffic Channel).<br /><br />If you know two of the figures, you can work out the third as follows: Click on the Unknown check box representing the unknown variable and then enter (or select) the 2 known figures into their edit boxes.<br /><br />');

DELETE FROM sys_definition_messages_display WHERE id = 'A_KPI_BUILDER_ERLANG_USE_FORMULA';
INSERT INTO sys_definition_messages_display (id,text) VALUES ('A_KPI_BUILDER_ERLANG_USE_FORMULA','Use ErlangB Formula');

DELETE FROM sys_definition_messages_display WHERE id = 'A_KPI_BUILDER_ERLANG_TRAFFIC';
INSERT INTO sys_definition_messages_display (id,text) VALUES ('A_KPI_BUILDER_ERLANG_TRAFFIC','Traffic (Erl.)');

DELETE FROM sys_definition_messages_display WHERE id = 'A_KPI_BUILDER_ERLANG_UNKOWN';
INSERT INTO sys_definition_messages_display (id,text) VALUES ('A_KPI_BUILDER_ERLANG_UNKOWN','Unknown<br />');

DELETE FROM sys_definition_messages_display WHERE id = 'A_KPI_BUILDER_ERLANG_SELECTION_RAW_COUNTERS';
INSERT INTO sys_definition_messages_display (id,text) VALUES ('A_KPI_BUILDER_ERLANG_SELECTION_RAW_COUNTERS','Select from Raw Counters<br />');

DELETE FROM sys_definition_messages_display WHERE id = 'A_KPI_BUILDER_ERLANG_GOS_TITLE';
INSERT INTO sys_definition_messages_display (id,text) VALUES ('A_KPI_BUILDER_ERLANG_GOS_TITLE','GOS (grade of service %)');

DELETE FROM sys_definition_messages_display WHERE id = 'A_KPI_BUILDER_ERLANG_TCH_LABEL';
INSERT INTO sys_definition_messages_display (id,text) VALUES ('A_KPI_BUILDER_ERLANG_TCH_LABEL','TCH (Number of Traffic Channel)&nbsp;&nbsp;');

DELETE FROM sys_definition_messages_display WHERE id = 'A_KPI_BUILDER_ERLANG_CELL_PARAMETERS_LABEL';
INSERT INTO sys_definition_messages_display (id,text) VALUES ('A_KPI_BUILDER_ERLANG_CELL_PARAMETERS_LABEL','From Cell Parameters<br />');

-- 09/12/2009 BBX : Correction du message de suppression d'une famille mixed kpi. BZ 13179
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_JS_CONFIRM_DELETE_FAMILY';
INSERT INTO sys_definition_messages_display (id,text) VALUES ('A_SETUP_MIXED_KPI_JS_CONFIRM_DELETE_FAMILY',E'Are you sure to want to delete this family ?\\nWarning : all related data will be lost');

-- 09/12/2009 BBX : No family Defined. BZ 13182
DELETE FROM sys_definition_messages_display WHERE id = 'A_HOMEPAGE_NO_FAMILY_DEFINED';
INSERT INTO sys_definition_messages_display (id,text) VALUES ('A_HOMEPAGE_NO_FAMILY_DEFINED','No family defined');

-- 15:21 10/12/2009 SCT : ajout du message en cas de fichier de rï¿½fï¿½rence invalide (juste header du fichier, pas de donnï¿½es)
DELETE FROM sys_definition_messages_display WHERE id = 'A_FLAT_FILE_UPLOAD_ALARM_BAD_REFERENCE_FILE_STRUCTURE';
INSERT INTO sys_definition_messages_display (id,text) VALUES ('A_FLAT_FILE_UPLOAD_ALARM_BAD_REFERENCE_FILE_STRUCTURE','Reference file is not valid');

-- 15:29 10/12/2009 GHX
DELETE FROM sys_definition_messages_display where id ='A_SETUP_MIXED_INFO_COMPTUTE_MODE';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_SETUP_MIXED_INFO_COMPTUTE_MODE', 'The product Mixed KPI is based on the time agregation "$1".');

DELETE FROM sys_definition_messages_display where id ='A_E_SETUP_MIXED_ALL_PRODUCTS_NOT_SAME_COMPTUTE_MODE';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_SETUP_MIXED_ALL_PRODUCTS_NOT_SAME_COMPTUTE_MODE', 'Be careful : for all products which are not in compute mode "hourly", data cannot be integrate :');

-- 15:53 11/12/2009 GHX
-- Correction du BZ 13312
DELETE FROM sys_definition_messages_display where id ='A_SETUP_MIXED_KPI_JS_CONFIRM_CHANGE_TA_MIN';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_SETUP_MIXED_KPI_JS_CONFIRM_CHANGE_TA_MIN', 'Are you sure you want to modify the minimal TA ?');

-- 14/12/2009 BBX BZ 13261
DELETE FROM sys_definition_messages_display where id ='A_TASK_SCHEDULER_DATA_EXPORT_TARGET_EXISTS';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_TASK_SCHEDULER_DATA_EXPORT_TARGET_EXISTS', 'Target file "$1" already defined for Data Export "$2"');

DELETE FROM sys_definition_messages_display where id ='A_TASK_SCHEDULER_DATA_EXPORT_NAME_EXISTS';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_TASK_SCHEDULER_DATA_EXPORT_NAME_EXISTS', 'Data Export "$1" already exist');

-- 14:36 15/12/2009
DELETE FROM sys_definition_messages_display where id ='A_E_SETUP_MIXED_KPI_LIMIT_NB_COUNTER_EXCEEDED';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_SETUP_MIXED_KPI_LIMIT_NB_COUNTER_EXCEEDED', 'You can activate only $1 counters, $2 were not taken into account');

-- 17:31 16/12/2009GHX
-- BZ 13354
DELETE FROM sys_definition_messages_display where id ='A_E_CONTEXT_MOUNT_LIMIT_NB_COUNTER_EXCEEDED';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_CONTEXT_MOUNT_LIMIT_NB_COUNTER_EXCEEDED', 'You have exceeded the limit of $1 $2 for the family $3 (found $4) on the product $5');

-- 14:52 04/01/2010 GHX
-- BZ 13612
DELETE FROM sys_definition_messages_display where id ='A_E_SETUP_MIXED_KPI_SAVE_CONFIG_NA';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_SETUP_MIXED_KPI_SAVE_CONFIG_NA', 'You must save a valid configuration');

-- 18/01/2010 NSE
-- Correction d'un message. BZ 13789
DELETE FROM sys_definition_messages_display WHERE id = 'U_EXCEL_NO_DATA';
INSERT INTO sys_definition_messages_display VALUES ('U_EXCEL_NO_DATA', 'No data found.');

-- 22/01/2010 MPR - Messages erreur doivent ï¿½tre en anglais
DELETE FROM sys_definition_messages_display WHERE id = 'U_E_LINK_AA_SQL_INVALID_SERVER';
INSERT INTO sys_definition_messages_display VALUES ('U_E_LINK_AA_SQL_INVALID_SERVER', 'Unable to recover datas on database/server');

DELETE FROM sys_definition_messages_display WHERE id = 'U_E_LINK_AA_FORMAT_DATE_HOUR';
INSERT INTO sys_definition_messages_display VALUES ('U_E_LINK_AA_FORMAT_DATE_HOUR', 'Error in date hour format');

-- 18/02/2010 NSE bz 9050 : espace disque pour sauvegarde DB
DELETE FROM sys_definition_messages_display WHERE id = 'A_BACKUP_DATABASE_DISK_SPACE';
INSERT INTO sys_definition_messages_display VALUES ('A_BACKUP_DATABASE_DISK_SPACE', 'Not enough free disk space for database backup');
DELETE FROM sys_definition_messages_display where id ='A_BACKUP_DATABASE_DISK_SPACE_DETAILS_NEEDED';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_BACKUP_DATABASE_DISK_SPACE_DETAILS_NEEDED', 'Free disk space needed: $1 MB');
DELETE FROM sys_definition_messages_display where id ='A_BACKUP_DATABASE_DISK_SPACE_DETAILS_AVAILABLE';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_BACKUP_DATABASE_DISK_SPACE_DETAILS_AVAILABLE', 'Free disk space available: $1 MB');

-- maj 16:15 01/03/2010 MPR : Correction du bug 14255 : Prise en compte de la limite du nombre de KPI
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_KPI_BUILDER_UPLOAD_NB_ACTIVATED_KPI_EXCEEDED';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_KPI_BUILDER_UPLOAD_NB_ACTIVATED_KPI_EXCEEDED','The limit of $2 activated KPIs has been exceeded for family $1. $3 KPIs are currently activated. You are trying to activate $5 new KPIs on $4 remaining for this family.');

-- maj 16:15 01/03/2010 BBX : ajout du message sur la limite du nombre de compteurs
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_GLOBAL_PARAMS_MAX_COUNTERS';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_SETUP_GLOBAL_PARAMS_MAX_COUNTERS','The Number of counters you entered is too high. The limit is 1570.');

-- maj 09:58 03/03/2010 MPR : Mise ï¿½ jour du label Add Topology File dans le data export
DELETE FROM sys_definition_messages_display  WHERE id = 'A_TASK_SCHEDULER_DATA_EXPORT_ADD_TOPOLOGY_FILE';
INSERT INTO sys_definition_messages_display(id, text) VALUES ('A_TASK_SCHEDULER_DATA_EXPORT_ADD_TOPOLOGY_FILE','Add Topology file with coordinates ');

-- maj 14:47 04/03/2010 MPR : Message d'alert lors du montage du contexte si celui-ci contient le paramï¿½tre maximum_mapped_counters > 1570
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_CONTEXT_MOUNT_MSG_ALERT_UPDATE_LIMIT_NB_RAW_KPI_CTX';
INSERT INTO sys_definition_messages_display VALUES ('A_E_CONTEXT_MOUNT_MSG_ALERT_UPDATE_LIMIT_NB_RAW_KPI_CTX', 'Update of the number of active $1 allowed - The value defined in $2 ($3 active elements) is exceeding the allowed limit ( $4 active elements) on the product $5');

-- maj 16:53 05/03/2010 MPR : Message d'erreur lorsque la formule BH est = ï¿½ 0
DELETE FROM sys_definition_messages_display WHERE id = 'A_COMPUTE_MSG_ERROR_BH_FORMULA_IS_ZERO';
INSERT INTO sys_definition_messages_display VALUES ('A_COMPUTE_MSG_ERROR_BH_FORMULA_IS_ZERO', 'The BH Formula equals 0 - ');

-- maj 17:54 05/03/2010 NSE bz 2454 : Message d'erreur context lorsque tous le enregistrement du fichier csv n'ont pas ï¿½tï¿½ insï¿½rï¿½s dans la table correspondante
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_CONTEXT_MOUNT_DOES_NOT_INSERT_ALL_ELEMENTS_IN_TABLE';
INSERT INTO sys_definition_messages_display VALUES ('A_E_CONTEXT_MOUNT_DOES_NOT_INSERT_ALL_ELEMENTS_IN_TABLE', 'All records of the "$1.csv" file were not inserted in the "$1" table');

-- maj 12:05 08/03/2010 MPR bz 14317 : Message d'erreur lorsqu'on a dans le select une TA BH et aucune dans la ou les conditions du QUERY BUILDER
DELETE FROM sys_definition_messages_display WHERE id = 'U_QUERY_BUILDER_NO_CORRESPONDANCE_SELECT_CONDITION_ON_TA_BH';
INSERT INTO sys_definition_messages_display VALUES ('U_QUERY_BUILDER_NO_CORRESPONDANCE_SELECT_CONDITION_ON_TA_BH', 'If you want to display results with time aggregation BH, the time aggregation in query condition should be a time aggregation BH');

-- 11:16 24/03/2010 NSE bz 14796 : Message d'avertissement lorsqu'on a atteint le nombre max de Kpi utilisant Erlang
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_KPI_BUILDER_MAX_KPI_USING_ERLANG';
INSERT INTO sys_definition_messages_display VALUES ('A_E_KPI_BUILDER_MAX_KPI_USING_ERLANG', 'You reached the maximum number of Kpi using erlang function in their formula.');

-- 14:46 24/03/2010 NSE bz 14809 : Message d'avertissement lorsqu'on ne peut pas se connecter ï¿½ une bd slave
DELETE FROM sys_definition_messages_display WHERE id = 'G_E_PRODUCT_CONNECTION_PB';
INSERT INTO sys_definition_messages_display VALUES ('G_E_PRODUCT_CONNECTION_PB', 'Problem encountered with product "$1": unable to connect to the database.');

-- 25/03/2010 BBX : Message contraintes trigramme Setup Products
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCTS_TRIGRAM_3_CHARS';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCTS_TRIGRAM_3_CHARS', 'The product trigram must be 3 characters length');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCTS_TRIGRAM_SPEC_CHARS';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCTS_TRIGRAM_SPEC_CHARS', 'The product trigram shall be composed of letters only');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCTS_TRIGRAM_NOT_UNIQUE';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCTS_TRIGRAM_NOT_UNIQUE', 'The product $1 is already using the same trigram. Please, make sure that trigrams are unique before saving');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCTS_TRIGRAM_PROCESS';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCTS_TRIGRAM_PROCESS', 'The following process is running "$1". Please, stop all processes before updating a trigram');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCTS_TRIGRAM_FATAL';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCTS_TRIGRAM_FATAL', 'An error occurred during the update of Trigram. The Mixed KPI product might be corrupted.');
-- 16:51 02/04/2010 - MPR : DE Mixed KPI - Synchronisation des compteurs
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_EDIT_COUNTERS_SYNCHRO_IS_OK';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_EDIT_COUNTERS_SYNCHRO_IS_OK', 'Update Completed Successfully');
--
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_EDIT_COUNTERS_SYNCHRO_ERROR';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_EDIT_COUNTERS_SYNCHRO_ERROR', 'An error occurred during the Synchronization <br />$1');

-- 08/04/2010 BBX : Message contraintes trigramme Setup Products
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCTS_TRIGRAM_CHANGED';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCTS_TRIGRAM_CHANGED', 'The trigram "$1" was changed to "$2" for the product "$3"');
--

-- 26/04/2010 NSE bz 15188
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_MIXED_KPI_FAMILY_CONF_ADD';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_MIXED_KPI_FAMILY_CONF_ADD', 'Click the "Add a family" button to create a new family.');

-- 27/04/2010 NSE bz 15045
DELETE FROM sys_definition_messages_display WHERE id = 'A_KPI_BUILDER_KPI_CUSTO_NOT_UPDATED';
INSERT INTO sys_definition_messages_display VALUES ('A_KPI_BUILDER_KPI_CUSTO_NOT_UPDATED', 'KPIs owned by Astellia have not been updated');

-- 09/06/2010 MPR bz 15686 - On vérifie que le dashboard $id_page n'est pas configuré en homepage pour un ou plusieurs users
DELETE FROM sys_definition_messages_display WHERE id = 'G_GDR_BUILDER_THIS_ITEM_IS_DEFINED_AS_USER_HOMEPAGE';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('G_GDR_BUILDER_THIS_ITEM_IS_DEFINED_AS_USER_HOMEPAGE','This dashboard is defined as homepage for these users:');

-- 09/06/2010 MPR bz 15686 - On vérifie que le dashboard $id_page n'est pas configuré en la homepage user par défaut
DELETE FROM sys_definition_messages_display WHERE id = 'G_GDR_BUILDER_THIS_ITEM_IS_DEFINED_AS_USER_HOMEPAGE_DEFAULT';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('G_GDR_BUILDER_THIS_ITEM_IS_DEFINED_AS_USER_HOMEPAGE_DEFAULT','This dashboard is defined as the default users homepage');

-- 10/06/2010 NSE : merge Single KPI
DELETE FROM sys_definition_messages_display WHERE id = 'G_GDR_BUILDER_SINGLE_KPI';
INSERT INTO sys_definition_messages_display VALUES ('G_GDR_BUILDER_SINGLE_KPI', 'SingleKPI');

-- 10/06/2010 MPR : DE Source Availability IHM Setup Data Files
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_DATA_FILES_TITLE';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_DATA_FILES_TITLE', 'Setup Data Files');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_DATA_FILES_TABLE_TITLE_SA';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_DATA_FILES_TABLE_TITLE_SA', 'Source Availability');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_SYSTEM_ALERTS_ERROR_DATA_CHUNKS_NAN';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_SYSTEM_ALERTS_ERROR_DATA_CHUNKS_NAN', 'Data Chunks must be a integer greater than or equals to 1.');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_SYSTEM_ALERTS_ERROR_DATA_CHUNKS_VALUE_EXCEEDED';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_SYSTEM_ALERTS_ERROR_DATA_CHUNKS_VALUE_EXCEEDED', E'Maximum value of Data Chunks has exceeded. Maximum values expected are:<table border="0" ><tr><td align="left">- Data Granularity = Day / Data collection frequency = day / Data chunks = 1</td></tr><tr><td align="left">- Data Granularity = Hour / Data collection frequency =  day / Data chunks  = 24</td></tr><tr><td align="left">- Data collection frequency = hour / Data chunks = 24</td></tr><tr><td align="left">- Data collection frequency = 15mn / Data chunks = 96</td></tr></table>');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_SYSTEM_ALERTS_ACTIVATION_SYSTEM_ALERTS';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_SYSTEM_ALERTS_ACTIVATION_SYSTEM_ALERTS', 'Check to Activate System Alerts / Uncheck to Deactivate System Alerts');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_DATA_FILES_DATA_COLLECTION_FREQUENCY_VALUES';
INSERT INTO sys_definition_messages_display (text,id) VALUES ('0.25|15mn;1|Hour;24|Day','A_SETUP_DATA_FILES_DATA_COLLECTION_FREQUENCY_VALUES');

-- 21/06/2010 MPR : Correction du BZ 16126 - Labels ne correspondent pas à ceux attendus par les specs
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_SYSTEM_ALERTS_FORM_LABELS_WITH_SA';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_SYSTEM_ALERTS_FORM_LABELS_WITH_SA', 'Data file name;Data Granularity;Data Collection Frequency;Data Chunks;Period type;System Alarms Delay;Alarm Exclusions');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_SYSTEM_ALERTS_ACTIVATE_LABEL';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_SYSTEM_ALERTS_ACTIVATE_LABEL', 'Activate Alarms');

-- Modification du message de confirmation de suppression d'un produit + correction de l'id
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCTS_CONFIRM_DELETE_SALVE';
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCTS_CONFIRM_DELETE_SLAVE';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCTS_CONFIRM_DELETE_SLAVE', E'Are you sure to want to delete the product "$1" ?\\n\\nThis product will be uninstalled');

-- Correction du bz17154 - Ajout d'une icone d'information dans Setup Data Files
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_DATA_FILES_GRANULARITY_INFO';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_DATA_FILES_GRANULARITY_INFO', 'Data Granularity in Source Files is only used for Source Availability');

-- Correction du bz16538 - Crash de l'application si deux kpi name possède plus de 63 caractères
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_KPI_BUILDER_UPLOAD_KPI_NAME_TOO_LONG';
INSERT INTO sys_definition_messages_display VALUES ('A_E_KPI_BUILDER_UPLOAD_KPI_NAME_TOO_LONG', 'KPI name too long (63 caracters maximum)');

-- Correction du bz16538 - Crash de l'application si deux kpi name possède plus de 63 caractères
DELETE FROM sys_definition_messages_display WHERE id = 'A_KPI_BUILDER_UPLOAD_KPI_NAME_INFOS';
INSERT INTO sys_definition_messages_display VALUES ('A_KPI_BUILDER_UPLOAD_KPI_NAME_INFOS', 'The name must be an alphanumeric string (underscores allowed) containing less than 64 characters');

-- 2010/08/11 - MGD - BZ 16787 : Additionnal -> Additional
DELETE FROM sys_definition_messages_display WHERE id = 'A_TASK_SCHEDULER_DATA_EXPORT_TITLE_ADDITIONNAL_FILES';
INSERT INTO sys_definition_messages_display VALUES ('A_TASK_SCHEDULER_DATA_EXPORT_TITLE_ADDITIONNAL_FILES', 'Additional files');

-- 2010/09/13 - MPR - Correction d'un bug dans le mapping de la topologie
DELETE FROM sys_definition_messages_display WHERE id = 'A_MAPPING_SLAVE_TOPO_IS_EMPTY';
INSERT INTO sys_definition_messages_display VALUES ('A_MAPPING_SLAVE_TOPO_IS_EMPTY', 'Topology is empty on the product slave. You cannot use the mapping tolopogy.');

-- maj 31/08/2010 MMT : DE firefox bz 17306 - warning si utilisation de navigateur autre que IE . TOTO mettre a jour version de AA une fois connue
DELETE FROM sys_definition_messages_display WHERE id = 'U_E_LINK_AA_WARNING_NO_ACTIVEX';
-- maj 10/09/2010 MMT : DE liens horraire pour AA, ajout de la version min de AA en paramètre
INSERT INTO sys_definition_messages_display (id, text) VALUES ('U_E_LINK_AA_WARNING_NO_ACTIVEX','Your Browser does not support ActiveX. To launch Activity Analysis please execute the file with AAcontrol.exe $1 or greater.');

-- 03/09/2010 MMT : De FireFox bz 17006 encadre les messages help avec Liste (<li>) dans <ul></ul>
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROFILE_MANAGEMENT_HELP';
INSERT INTO sys_definition_messages_display VALUES ('A_PROFILE_MANAGEMENT_HELP','<ul class="infoBoxList"><li>Use Drag''n Drop to move menus. You can put a menu into another one (blue arrow) or under another one (blue line).</li><li>Click on a menu''s name to enable / disable it (menu in italic-grey are disabled).</li><li>Notice : Disactivating a menu will make its submenus unaccessible.</li></ul>');

DELETE FROM sys_definition_messages_display WHERE id = 'A_MENU_MANAGEMENT_HELP';
INSERT INTO sys_definition_messages_display VALUES ('A_MENU_MANAGEMENT_HELP','<ul class="infoBoxList"><li>Enter a new name to add a menu.</li><li>Click on a menu to edit it.</li><li>Use Drag''n Drop to change menus order.</li><li>Click on the red icon to delete a menu.</li></ul>');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_NETWORK_AGGREGATION_HELP';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_NETWORK_AGGREGATION_HELP', '<ul class="infoBoxList"><li>Click on "New" to add a network aggregation</li><li>Click the "Activate" icon to activate undeployed network aggregations (displayed in red)</li><li>You can delete any deployed network aggregation. However, keep in mind that deleting a network aggregation will remove all related data</li><li>Notice : it is not possible to delete a built-in network aggregation.</li></ul>');

-- 2010/09/27 - MPR - Correction du bz 18035 / Ajout de messages dans le démon et dans le tracelog
DELETE FROM sys_definition_messages_display WHERE id = 'A_CLEAN_TABLES_STRUCTURE_DEMON_WARNING_KPI_DISABLE';
INSERT INTO sys_definition_messages_display VALUES ('A_CLEAN_TABLES_STRUCTURE_DEMON_WARNING_KPI_DISABLE', 'Le KPI $1 a été désactivé - Erreur dans la formule du KPI<br />');

DELETE FROM sys_definition_messages_display WHERE id = 'A_CLEAN_TABLES_STRUCTURE_DEMON_WARNING_KPI_DISABLE_DETAILS';
INSERT INTO sys_definition_messages_display VALUES ('A_CLEAN_TABLES_STRUCTURE_DEMON_WARNING_KPI_DISABLE_DETAILS', 'Contrôle SQL effectuée : $1 <br />');

DELETE FROM sys_definition_messages_display WHERE id = 'A_TRACELOG_WARNING_KPI_DISABLE';
INSERT INTO sys_definition_messages_display VALUES ('A_TRACELOG_WARNING_KPI_DISABLE', 'KPI $1 has been disabled : Invalid formula');

DELETE FROM sys_definition_messages_display WHERE id = 'A_CLEAN_TABLES_STRUCTURE_DEMON_WARNING_KPI_DISABLE_SHOW_RAW_DISABLE';
INSERT INTO sys_definition_messages_display VALUES ('A_CLEAN_TABLES_STRUCTURE_DEMON_WARNING_KPI_DISABLE_SHOW_RAW_DISABLE', 'Le raw $1 est déployé mais désactivé<br />');

DELETE FROM sys_definition_messages_display WHERE id = 'A_CLEAN_TABLES_STRUCTURE_ERROR_KPI_DISABLE_IMPOSSIBLE';
INSERT INTO sys_definition_messages_display VALUES ('A_CLEAN_TABLES_STRUCTURE_ERROR_KPI_DISABLE_IMPOSSIBLE', 'Erreur : impossible de le desactiver : $1<br />');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PROCESS_HELP';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PROCESS_HELP', '<ul class="infoBoxList"><li>Each box enables you to manage a product''s processes</li><li>The master product is displayed with "[master]"</li><li>The topology master product is displayed with "[topology master]"</li><li>The time period determines the frequency the processes will be launched (if on).<br />For example, 0h 25mn means the process will start every 25 minutes.</li><li>The offset indicates from what hour / minute a process should start.<br />For example, time period : 0h 25mn / offset 0h 12mn means the process will start every 25 minutes after the next 12th minute (08h12 - 08h37 - 09h12 - 09h37 - etc...).</li><li>You can set an automatic value for time period by selecting "Daily" or "Hourly" from the select box.<br />Selecting "Hourly" will set the time period to "1h 0mn" and "Daily" to "24h 0mn".</li></ul>');

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

-- 24/02/2011 OJT DE SFTP
DELETE FROM sys_definition_messages_display where id='A_SETUP_CONNECTION_FORM_BTN_FTP_CHECK';
DELETE FROM sys_definition_messages_display where id='A_SETUP_CONNECTION_FORM_LABEL_PORT';
DELETE FROM sys_definition_messages_display where id='A_JS_SETUP_CONNECTION_PORT_RANGE';
INSERT INTO sys_definition_messages_display (id,text) values ('A_SETUP_CONNECTION_FORM_LABEL_PORT','Port');
INSERT INTO sys_definition_messages_display (id,text) values ('A_JS_SETUP_CONNECTION_PORT_RANGE','Port number must be a positive integer (smaller than 65536)' );
INSERT INTO sys_definition_messages_display (id,text) values ('A_SETUP_CONNECTION_FORM_BTN_FTP_CHECK','Check remote connections' );

-- 24/02/2011 OJT BZ 20811
DELETE FROM sys_definition_messages_display WHERE id = 'A_CLEANING_COLUMNS_FAILED';
DELETE FROM sys_definition_messages_display WHERE id = 'A_CLEANING_COLUMNS_TABLE_INFO';
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_CONTEXT_MOUNT_LIMIT_NB_COUNTER_EXCEEDED_SIMPLE';
INSERT INTO sys_definition_messages_display VALUES ('A_CLEANING_COLUMNS_FAILED', 'Cleaning $1 failed. Please, check if Postgresql data partition has enough free space disc');
INSERT INTO sys_definition_messages_display VALUES ('A_CLEANING_COLUMNS_TABLE_INFO', 'Table $1 has been cleaned because the limit on maximum number of columns has been reached');
INSERT INTO sys_definition_messages_display VALUES ('A_E_CONTEXT_MOUNT_LIMIT_NB_COUNTER_EXCEEDED_SIMPLE', 'You have exceeded the limit of $1 for the family $2' );

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


-- 14/09/2011 BBX
-- LAISSER EN BAS
VACUUM FULL ANALYZE sys_definition_messages_display;
REINDEX TABLE sys_definition_messages_display;
