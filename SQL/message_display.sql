--
-- N 'OUBLIEZ PAS DE METTRE DES DELETE AVANT CHAQUE INSERT !!!!!!!!!!!!!!!!!!!!!!!!!§
--
-- exemples
-- DELETE FROM sys_definition_messages_display WHERE id = 'TOTO';
-- INSERT INTO sys_definition_messages_display VALUES ('TOTO', 'toto');

-- maj 16:04 03/11/2008 - Maxime : Ajout de messages dans la homepage en admin
DELETE FROM sys_definition_messages_display WHERE id = 'A_HOMEPAGE_TOPOLOGY_TITLE';
INSERT INTO sys_definition_messages_display VALUES ('A_HOMEPAGE_TOPOLOGY_TITLE', 'Topology');

DELETE FROM sys_definition_messages_display WHERE id = 'A_HOMEPAGE_ADMIN_NETWORK_INFORMATIONS';
INSERT INTO sys_definition_messages_display VALUES ('A_HOMEPAGE_ADMIN_NETWORK_INFORMATIONS', '&nbsp;&nbsp;Network Information&nbsp;');
DELETE FROM sys_definition_messages_display WHERE id = 'A_HOMEPAGE_ADMIN_TOPOLOGY_NO_LABEL';
INSERT INTO sys_definition_messages_display VALUES ('A_HOMEPAGE_ADMIN_TOPOLOGY_NO_LABEL', 'No Label :');

DELETE FROM sys_definition_messages_display WHERE id = 'A_HOMEPAGE_ADMIN_TOPOLOGY_NO_COORDINATES';
INSERT INTO sys_definition_messages_display VALUES ('A_HOMEPAGE_ADMIN_TOPOLOGY_NO_COORDINATES', 'No Coordinates :');

DELETE FROM sys_definition_messages_display WHERE id = 'A_HOMEPAGE_ADMIN_TOPOLOGY_TITLE';
INSERT INTO sys_definition_messages_display VALUES ('A_HOMEPAGE_ADMIN_TOPOLOGY_TITLE', 'Topology');

DELETE FROM sys_definition_messages_display WHERE id = 'A_HOMEPAGE_ADMIN_DISK_SPACE';
INSERT INTO sys_definition_messages_display VALUES ('A_HOMEPAGE_ADMIN_DISK_SPACE', '&nbsp;&nbsp;Free Disk space&nbsp;');

DELETE FROM sys_definition_messages_display WHERE id = 'SELECTEUR_USER';
INSERT INTO sys_definition_messages_display VALUES ('SELECTEUR_USER', 'Users Selection');

DELETE FROM sys_definition_messages_display WHERE id = 'A_APPLICATION_STATS_LABEL_NB_CONNECTIONS';
INSERT INTO sys_definition_messages_display VALUES ('A_APPLICATION_STATS_LABEL_NB_CONNECTIONS', 'Number of connections');

DELETE FROM sys_definition_messages_display WHERE id = 'A_APPLICATION_STATS_LABEL_TRAFFIC';
INSERT INTO sys_definition_messages_display VALUES ('A_APPLICATION_STATS_LABEL_TRAFFIC', 'Pages viewed over the last 30 days');

DELETE FROM sys_definition_messages_display WHERE id = 'A_APPLICATION_STATS_LABEL_TRAFFIC_MONTH';
INSERT INTO sys_definition_messages_display VALUES ('A_APPLICATION_STATS_LABEL_TRAFFIC_MONTH', 'Pages viewed over the last 6 months');

DELETE FROM sys_definition_messages_display WHERE id = 'A_ADMIN_TITLE_TOPOLOGY_ERRORS';
INSERT INTO sys_definition_messages_display VALUES ('A_ADMIN_TITLE_TOPOLOGY_ERRORS', 'Topology Errors');

-- 12/11/2008 GHX 
UPDATE sys_definition_messages_display SET text = 'The minimum level must appear in the file header' WHERE id = 'A_UPLOAD_TOPO_NA_HEADER_INFO';

DELETE FROM sys_definition_messages_display WHERE id = 'G_CURRENT_PRODUCT';
INSERT INTO sys_definition_messages_display VALUES ('G_CURRENT_PRODUCT', 'Current product');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_UPLOAD_TOPOLOGY_FILE_IS_EMPTY';
INSERT INTO sys_definition_messages_display VALUES ('A_E_UPLOAD_TOPOLOGY_FILE_IS_EMPTY', 'Error empty file');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_UPLOAD_TOPOLOGY_FILE_MISSING';
INSERT INTO sys_definition_messages_display VALUES ('A_E_UPLOAD_TOPOLOGY_FILE_MISSING', 'Error missing file');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_UPLOAD_TOPOLOGY_FILE_TYPE';
INSERT INTO sys_definition_messages_display VALUES ('A_E_UPLOAD_TOPOLOGY_FILE_TYPE', 'Error file type');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_UPLOAD_TOPOLOGY_FILE_PARTIAL';
INSERT INTO sys_definition_messages_display VALUES ('A_E_UPLOAD_TOPOLOGY_FILE_PARTIAL', 'The uploaded file was only partially uploaded');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_UPLOAD_TOPOLOGY_ERROR_DURING_UPADTE';
INSERT INTO sys_definition_messages_display VALUES ('A_E_UPLOAD_TOPOLOGY_ERROR_DURING_UPADTE', 'An error occurred during the update of the data');

DELETE FROM sys_definition_messages_display WHERE id = 'A_UPLOAD_TOPOLOGY_TITLE_CHANGE_SUMMARY';
INSERT INTO sys_definition_messages_display VALUES ('A_UPLOAD_TOPOLOGY_TITLE_CHANGE_SUMMARY', 'Topology changes summary');

DELETE FROM sys_definition_messages_display WHERE id = 'A_UPLOAD_TOPOLOGY_TITLE_COL_NETWORK_LEVEL';
INSERT INTO sys_definition_messages_display VALUES ('A_UPLOAD_TOPOLOGY_TITLE_COL_NETWORK_LEVEL', 'Network level');

DELETE FROM sys_definition_messages_display WHERE id = 'A_UPLOAD_TOPOLOGY_TITLE_COL_NETWORK_VALUE';
INSERT INTO sys_definition_messages_display VALUES ('A_UPLOAD_TOPOLOGY_TITLE_COL_NETWORK_VALUE', 'Network value');

DELETE FROM sys_definition_messages_display WHERE id = 'A_UPLOAD_TOPOLOGY_TITLE_COL_CHANGE_INFO';
INSERT INTO sys_definition_messages_display VALUES ('A_UPLOAD_TOPOLOGY_TITLE_COL_CHANGE_INFO', 'Change info.');

DELETE FROM sys_definition_messages_display WHERE id = 'A_UPLOAD_TOPOLOGY_TITLE_COL_OLD_VALUE';
INSERT INTO sys_definition_messages_display VALUES ('A_UPLOAD_TOPOLOGY_TITLE_COL_OLD_VALUE', 'Old value');

DELETE FROM sys_definition_messages_display WHERE id = 'A_UPLOAD_TOPOLOGY_TITLE_COL_NEW_VALUE';
INSERT INTO sys_definition_messages_display VALUES ('A_UPLOAD_TOPOLOGY_TITLE_COL_NEW_VALUE', 'New value');

DELETE FROM sys_definition_messages_display WHERE id = 'A_UPLOAD_TOPOLOGY_LOADER_INFO';
INSERT INTO sys_definition_messages_display VALUES ('A_UPLOAD_TOPOLOGY_LOADER_INFO', 'Loading file ...');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_UPLOAD_TOPOLOGY_NB_COLUMNS_NOT_VALID';
INSERT INTO sys_definition_messages_display VALUES ('A_E_UPLOAD_TOPOLOGY_NB_COLUMNS_NOT_VALID', 'The number of columns is not valid on following lines : <br />$1');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_UPLOAD_TOPOLOGY_AXE1_AND_AXE3_NOT_POSSIBLE';
INSERT INTO sys_definition_messages_display VALUES ('A_E_UPLOAD_TOPOLOGY_AXE1_AND_AXE3_NOT_POSSIBLE', 'You can not mix the second axis and the third axis in the same file');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_UPLOAD_TOPOLOGY_HEADER_MUTLI_SAMES_COLUMNS';
INSERT INTO sys_definition_messages_display VALUES ('A_E_UPLOAD_TOPOLOGY_HEADER_MUTLI_SAMES_COLUMNS', 'The columns $1 reference the same level of aggregation network. You must use $2');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_UPLOAD_TOPOLOGY_HEADER_MUTLI_SAMES_COLUMNS_2';
INSERT INTO sys_definition_messages_display VALUES ('A_E_UPLOAD_TOPOLOGY_HEADER_MUTLI_SAMES_COLUMNS_2', 'You can not have more time column <b>$1</b> in the header');

-- 21/11/2008 BBX : message quand un dash plante
DELETE FROM sys_definition_messages_display WHERE id = 'U_DASHBOARD_CANT_LOAD_DASHBOARD';
INSERT INTO sys_definition_messages_display VALUES ('U_DASHBOARD_CANT_LOAD_DASHBOARD', 'Can''t load current dashboard. Please check that this dashboard and its call are correctly configured');

-- 21/11/2008 BBX : message quand trop d'élément dans la nebox
DELETE FROM sys_definition_messages_display WHERE id = 'U_SELECTEUR_NE_TOO_MANY_ELEMENTS';
INSERT INTO sys_definition_messages_display VALUES ('U_SELECTEUR_NE_TOO_MANY_ELEMENTS', 'Too many elements. Use search field to find any element');

-- 26/11/2008 BBX : message au passage de la souris sur le bouton pour afficher/masquer le sélecteur
DELETE FROM sys_definition_messages_display WHERE id = 'U_SELECTEUR_TIP_HIDE_SHOW_SELECTEUR';
INSERT INTO sys_definition_messages_display VALUES ('U_SELECTEUR_TIP_HIDE_SHOW_SELECTEUR', '$1 filter (hotkey : F2)');

-- GHX 15:03 25/11/2008
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_UPLOAD_TOPO_ON_OFF_INVALID';
INSERT INTO sys_definition_messages_display VALUES ('A_E_UPLOAD_TOPO_ON_OFF_INVALID','On/Off is different from 0 or 1 [lines :  $1]');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_UPLOAD_TOPO_ON_OFF_IS_NULL';
INSERT INTO sys_definition_messages_display VALUES ('A_E_UPLOAD_TOPO_ON_OFF_IS_NULL','On/Off is null (0 or 1 required) [lines :  $1]');

DELETE FROM sys_definition_messages_display WHERE id = 'A_UPLOAD_TOPOLOGY_SUCCES';
INSERT INTO sys_definition_messages_display VALUES ('A_UPLOAD_TOPOLOGY_SUCCES','The topology file has been successfully uploaded');
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_UPLOAD_TOPO_NA_MIN_NOT_UNIQUE';
INSERT INTO sys_definition_messages_display VALUES ('A_E_UPLOAD_TOPO_NA_MIN_NOT_UNIQUE','$1 ($2) not unique');

-- 28/11/2008 BBX : message pour els formulaire : remplir champs obligatoire
DELETE FROM sys_definition_messages_display WHERE id = 'A_JS_FORMS_FILL_IN_REQUIRED_FIELDS';
INSERT INTO sys_definition_messages_display VALUES ('A_JS_FORMS_FILL_IN_REQUIRED_FIELDS', 'Please, fill in all the required fields correctly');

-- 01/12/2008 BBX : Nouveaux messages pour la gestion des groupes
DELETE FROM sys_definition_messages_display WHERE id = 'A_USER_GROUP_MANAGEMENT_LABEL_INTERFACE';
INSERT INTO sys_definition_messages_display VALUES ('A_USER_GROUP_MANAGEMENT_LABEL_INTERFACE', 'Group Setup');

DELETE FROM sys_definition_messages_display WHERE id = 'A_USER_GROUP_MANAGEMENT_LABEL_INTERFACE_FULL';
INSERT INTO sys_definition_messages_display VALUES ('A_USER_GROUP_MANAGEMENT_LABEL_INTERFACE_FULL', 'Group Setup Interface');

DELETE FROM sys_definition_messages_display WHERE id = 'A_USER_GROUP_MANAGEMENT_CONFIRM_DELETION';
INSERT INTO sys_definition_messages_display VALUES ('A_USER_GROUP_MANAGEMENT_CONFIRM_DELETION', 'Are you sure you want to delete the group $1 ?');

DELETE FROM sys_definition_messages_display WHERE id = 'A_USER_GROUP_MANAGEMENT_ENTER_GROUP_NAME';
INSERT INTO sys_definition_messages_display VALUES ('A_USER_GROUP_MANAGEMENT_ENTER_GROUP_NAME', 'Please, enter a group name');

-- 01/12/2008 GHX message pour la topo
DELETE FROM sys_definition_messages_display WHERE id = 'A_UPLOAD_TOPO_NO_FILE_ARCHIVE';
INSERT INTO sys_definition_messages_display VALUES ('A_UPLOAD_TOPO_NO_FILE_ARCHIVE','No file is in the archive');

DELETE FROM sys_definition_messages_display WHERE id = 'A_UPLOAD_TOPO_CHANGE_LABEL';
INSERT INTO sys_definition_messages_display VALUES ('A_UPLOAD_TOPO_CHANGE_LABEL','Change label');

DELETE FROM sys_definition_messages_display WHERE id = 'A_UPLOAD_TOPO_CHANGE_ON_OFF';
INSERT INTO sys_definition_messages_display VALUES ('A_UPLOAD_TOPO_CHANGE_ON_OFF','Change On/Off');

DELETE FROM sys_definition_messages_display WHERE id = 'A_UPLOAD_TOPO_NEW_ELEMENT';
INSERT INTO sys_definition_messages_display VALUES ('A_UPLOAD_TOPO_NEW_ELEMENT','New'); -- Possibilité d'ajouté le niveau d'aggrégation avec le paramètre $1

-- 01/12/2008 BBX : Nouveaux messages pour la gestion des profiles
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROFILE_MANAGEMENT_LIST_TITLE';
INSERT INTO sys_definition_messages_display VALUES ('A_PROFILE_MANAGEMENT_LIST_TITLE','Select a Profile');

--  02/12/2008 GHX
DELETE FROM sys_definition_messages_display WHERE id = 'A_UPLOAD_TOPO_EMAIL_SUBJECT_ERROR';
INSERT INTO sys_definition_messages_display VALUES ('A_UPLOAD_TOPO_EMAIL_SUBJECT_ERROR','$1 - Topology update failed');

DELETE FROM sys_definition_messages_display WHERE id = 'A_UPLOAD_TOPO_EMAIL_SUBJECT_OK';
INSERT INTO sys_definition_messages_display VALUES ('A_UPLOAD_TOPO_EMAIL_SUBJECT_OK','$1 - Topology update');

DELETE FROM sys_definition_messages_display WHERE id = 'A_UPLOAD_TOPO_EMAIL_TITLE_CHANGE_SUMMARY';
INSERT INTO sys_definition_messages_display VALUES ('A_UPLOAD_TOPO_EMAIL_TITLE_CHANGE_SUMMARY','Topology changes summary');

DELETE FROM sys_definition_messages_display WHERE id = 'A_UPLOAD_TOPO_EMAIL_TITLE_ERROR_SUMMARY';
INSERT INTO sys_definition_messages_display VALUES ('A_UPLOAD_TOPO_EMAIL_TITLE_ERROR_SUMMARY','Topology errors summary');

DELETE FROM sys_definition_messages_display WHERE id = 'A_UPLOAD_TOPO_EMAIL_CONTENT_NO_CHANGE';
INSERT INTO sys_definition_messages_display VALUES ('A_UPLOAD_TOPO_EMAIL_CONTENT_NO_CHANGE','< No changes made >');

-- 03/12/2008 BBX : Message d'aide à la gestion des menus profil
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROFILE_MANAGEMENT_HELP';
INSERT INTO sys_definition_messages_display VALUES ('A_PROFILE_MANAGEMENT_HELP','<li>Use Drag''n Drop to move menus. You can put a menu into another one (blue arrow) or under another one (blue line).</li><li>Click on a menu''s name to enable / disable it (menu in italic-grey are disabled).</li><li>Notice : Disactivating a menu will make its submenus unaccessible.</li>');

-- 03/12/2008 BBX : Message pour afficher / cacher l'aide
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROFILE_MANAGEMENT_HIDE_DISPLAY_HELP';
INSERT INTO sys_definition_messages_display VALUES ('A_PROFILE_MANAGEMENT_HIDE_DISPLAY_HELP','Click to hide/display help');

-- 03/12/2008 BBX : Message pour sélectionner un profil correct
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROFILE_MANAGEMENT_NO_PROFILE_SELECTED';
INSERT INTO sys_definition_messages_display VALUES ('A_PROFILE_MANAGEMENT_NO_PROFILE_SELECTED','Please, select a valid profile to delete');

-- 03/12/2008 BBX : Message de confirmation de suppression de profil
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROFILE_MANAGEMENT_CONFIRM_DELETION';
INSERT INTO sys_definition_messages_display VALUES ('A_PROFILE_MANAGEMENT_CONFIRM_DELETION','Are you sure you want to delete this profile ?');

-- 03/12/2008 BBX : Suppression impossible d'un profil car users
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROFILE_MANAGEMENT_DELETION_NOT_ALLOWED';
INSERT INTO sys_definition_messages_display VALUES ('A_PROFILE_MANAGEMENT_DELETION_NOT_ALLOWED','You cannot delete this profile because it is assigned to users $1');

-- 03/12/2008 BBX : Frmulaire nouveau profil : choisir un nom
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROFILE_MANAGEMENT_PROFILE_NAME';
INSERT INTO sys_definition_messages_display VALUES ('A_PROFILE_MANAGEMENT_PROFILE_NAME','Profile name');

-- 03/12/2008 BBX : Frmulaire nouveau profil : erreur, entrez un nom
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROFILE_MANAGEMENT_ENTER_A_PROFILE_NAME';
-- 13/05/2009 SPS : message d'erreur plus explicite (correction bug 9556)
-- 30/07/2009 MPR : Correction du bug 9818 : Message explicite
-- 16:17 25/08/2009 GHX
-- Re-correction du BZ 9818 car les caractères avec les accents ne sont pas bien affichés en restitution
INSERT INTO sys_definition_messages_display VALUES ('A_PROFILE_MANAGEMENT_ENTER_A_PROFILE_NAME',E'The profile name can\\''t contain specific caracters like #{[|`\^@]} and accented characters');

-- 03/12/2008 BBX : Nom du profile existe déjà
DELETE FROM sys_definition_messages_display WHERE id = 'A_PROFILE_MANAGEMENT_NAME_ALREADY_EXISTS';
INSERT INTO sys_definition_messages_display VALUES ('A_PROFILE_MANAGEMENT_NAME_ALREADY_EXISTS','This profile name aldready exists');

-- 04/12/2008 BBX : Menus : menu name
DELETE FROM sys_definition_messages_display WHERE id = 'A_MENU_MANAGEMENT_MENU_NAME';
INSERT INTO sys_definition_messages_display VALUES ('A_MENU_MANAGEMENT_MENU_NAME','Menu name');

-- 04/12/2008 BBX : Menus : menu help
DELETE FROM sys_definition_messages_display WHERE id = 'A_MENU_MANAGEMENT_HELP';
INSERT INTO sys_definition_messages_display VALUES ('A_MENU_MANAGEMENT_HELP','<li>Enter a new name to add a menu.</li><li>Click on a menu to edit it.</li><li>Use Drag''n Drop to change menus order.</li><li>Click on the red icon to delete a menu.</li>');

-- 04/12/2008 BBX : Menus : incorrect menu name
DELETE FROM sys_definition_messages_display WHERE id = 'A_MENU_MANAGEMENT_ENTER_NAME';
INSERT INTO sys_definition_messages_display VALUES ('A_MENU_MANAGEMENT_ENTER_NAME','Please, enter a valid menu name');

-- 04/12/2008 BBX : Menus : delete tooltip
DELETE FROM sys_definition_messages_display WHERE id = 'A_MENU_MANAGEMENT_DELETE_TIP';
INSERT INTO sys_definition_messages_display VALUES ('A_MENU_MANAGEMENT_DELETE_TIP','Delete this menu');

-- 04/12/2008 BBX : Menus : delete confirm
DELETE FROM sys_definition_messages_display WHERE id = 'A_MENU_MANAGEMENT_DELETE_CONFIRM';
INSERT INTO sys_definition_messages_display VALUES ('A_MENU_MANAGEMENT_DELETE_CONFIRM',E' *** Be carefull *** \\n This menu will be deleted in ALL profiles.\\n All sub-menus will be not accessible. \\n Do you want to delete ');

-- 04/12/2008 BBX : Nom du menu existe déjà
DELETE FROM sys_definition_messages_display WHERE id = 'A_MENU_MANAGEMENT_NAME_ALREADY_EXISTS';
INSERT INTO sys_definition_messages_display VALUES ('A_MENU_MANAGEMENT_NAME_ALREADY_EXISTS','This menu aldready exists');

-- 04/12/2008 BBX : erreur est survenur
DELETE FROM sys_definition_messages_display WHERE id = 'A_GENERAL_ERROR_OCCURED';
INSERT INTO sys_definition_messages_display VALUES ('A_GENERAL_ERROR_OCCURED','An error has occured');

-- 05/12/2008 BBX : Aucun élément
DELETE FROM sys_definition_messages_display WHERE id = 'U_SELECTEUR_NO_ELEMENT';
INSERT INTO sys_definition_messages_display VALUES ('U_SELECTEUR_NO_ELEMENT','No element');

-- 05/12/2008 BBX : Autorisation refusée
DELETE FROM sys_definition_messages_display WHERE id = 'A_USER_NOT_ALLOWED_TO_ACCESS';
INSERT INTO sys_definition_messages_display VALUES ('A_USER_NOT_ALLOWED_TO_ACCESS','Sorry, you are not allowed to access this page');

-- 21/10/2008 BBX : ajout du text bouton de test des connexions FTP dans Setup Connection
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_CONNECTION_FORM_BTN_FTP_CHECK';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_CONNECTION_FORM_BTN_FTP_CHECK', 'Check FTP connections');

-- 15:06 24/10/2008 GHX
-- Message pour le tracelog quand une connexion FTP échoue lors d'un retrieve
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_PARSER_CONNECTION_FTP_FAILED';
INSERT INTO sys_definition_messages_display VALUES ('A_E_PARSER_CONNECTION_FTP_FAILED', 'FTP Connection to $1 failed');
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_PARSER_CONNECTION_FTP_USER_FAILED';
INSERT INTO sys_definition_messages_display VALUES ('A_E_PARSER_CONNECTION_FTP_USER_FAILED', 'FTP identification to $1 failed');

-- 17.38 27/10/2008 SCT : ajout d'un message en cas de problème d'absence de fichier de référence dans une archive ZIP
DELETE FROM sys_definition_messages_display WHERE id = 'A_CB_RETRIEVE_MISSING_REFERENCE_IN_ZIP_FILE';
INSERT INTO sys_definition_messages_display VALUES ('A_CB_RETRIEVE_MISSING_REFERENCE_IN_ZIP_FILE', 'Missing reference file in ZIP tarball');

-- 04/11/2008 BBX : ajout du texte d'infos sur la désactivation des alarmes système
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_CONNECTION_SYSTEM_ALERTS_DISABLED';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_CONNECTION_SYSTEM_ALERTS_DISABLED', 'System alerts are disabled');

-- 08/12/2008 BBX : message pour changer de produit
DELETE FROM sys_definition_messages_display WHERE id = 'A_U_CHANGE_PRODUCT';
INSERT INTO sys_definition_messages_display VALUES ('A_U_CHANGE_PRODUCT', 'Change product');

-- 08/12/2008 BBX : message d'erreur setup family
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_FAMILY_NO_GROUP_TABLE';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_FAMILY_NO_GROUP_TABLE', 'Error [This family has no configured group table]');

-- 08/12/2008 BBX : Correction de A_SETUP_NETWORK_AGGREGATION_NAME_ALREADY_USED
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_NETWORK_AGGREGATION_NAME_ALREADY_USED';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_NETWORK_AGGREGATION_NAME_ALREADY_USED', '[Save aborted] Network Aggregation name must be unique.');

-- 08/12/2008 BBX : Activation d'un élément dans setup network agregation
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_NETWORK_AGGREGATION_ACTIVATE';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_NETWORK_AGGREGATION_ACTIVATE', 'Activate');

-- 09/12/2008 BBX : Supprimer un élément (tooltip)
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_NETWORK_AGGREGATION_DElETE';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_NETWORK_AGGREGATION_DElETE', 'Delete');

-- 09/12/2008 BBX : Network aggregation help box
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_NETWORK_AGGREGATION_HELP';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_NETWORK_AGGREGATION_HELP', '<li>Click on "New" to add a network aggregation</li><li>Click the "Activate" icon to activate undeployed network aggregations (displayed in red)</li><li>You can delete any deployed network aggregation. However, keep in mind that deleting a network aggregation will remove all related data</li><li>Notice : it is not possible to delete a built-in network aggregation.</li>');

-- 09/12/2008 SLC : messages du Graph / Dash / Report builder
delete from sys_definition_messages_display where id='G_GDR_BUILDER_UNDEFINED_TYPE';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_UNDEFINED_TYPE','Undefined Type');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_CHOOSE_A_GTM_OR_CREATE_A_NEW_ONE';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_CHOOSE_A_GTM_OR_CREATE_A_NEW_ONE','Choose a graph or create a new one:');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_EXISTING_OPTGROUP';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_EXISTING_OPTGROUP','Existing $1');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_GRAPH_PROPERTIES';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_GRAPH_PROPERTIES','Graph properties');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_GTM_ELEMENT_BUILDER';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_GTM_ELEMENT_BUILDER','Graph element builder');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_THIS_ITEM_BELONGS_TO_THESE_DASHBOARDS';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_THIS_ITEM_BELONGS_TO_THESE_DASHBOARDS','This item belongs to these dashboards:');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_DELETE_THE_WHOLE_GRAPH';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_DELETE_THE_WHOLE_GRAPH','Delete the whole graph');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_EDIT_GRAPH_PROPERTIES';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_EDIT_GRAPH_PROPERTIES','edit graph properties');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_THE_DATA_INSIDE_YOUR_GTM';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_THE_DATA_INSIDE_YOUR_GTM','The data inside your graph:');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_NETWORK_AGGREGATION_LEVELS_IN_COMMON';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_NETWORK_AGGREGATION_LEVELS_IN_COMMON','Network aggregation levels in common:');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_NETWORK_AGGREGATION_LEVELS';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_NETWORK_AGGREGATION_LEVELS','Network aggregation levels');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_SHOW_NA_LEVELS';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_SHOW_NA_LEVELS','show network aggregation levels');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_ELEMENT_USED_FOR_ORDER_OPTION';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_ELEMENT_USED_FOR_ORDER_OPTION','Element used for Order option');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_ELEMENT_USED_FOR_GIS_OPTION';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_ELEMENT_USED_FOR_GIS_OPTION','Element used for GIS option');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_ELEMENT_USED_IN_THE_FOLLOWING_DASHBOARDS';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_ELEMENT_USED_IN_THE_FOLLOWING_DASHBOARDS','Element used in the following dashboards (overtime sort):');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_DELETE';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_DELETE','Delete');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_DETAILS';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_DETAILS','Details');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_NEW';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_NEW','New');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_SAVE';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_SAVE','Save');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_UNSHARE';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_UNSHARE','Unshare');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_SHARE';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_SHARE','Share');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_COPY';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_COPY','Copy');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_ERROR_YOU_DONT_HAVE_THE_RIGHT_TO_CHANGE_THAT_GTM';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_ERROR_YOU_DONT_HAVE_THE_RIGHT_TO_CHANGE_THAT_GTM','Error: you don''t have the right to change that graph');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_ERROR_THIS_IS_ALREADY_INSIDE_THAT_GTM';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_ERROR_THIS_IS_ALREADY_INSIDE_THAT_GTM','Error: this $1 is already inside that graph');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_WITH_RANGE';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_WITH_RANGE',' with range');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_CLIENT_';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_CLIENT_','client ');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_DATA_LEGEND';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_DATA_LEGEND','Data legend');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_DISPLAY_AS';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_DISPLAY_AS','Display as');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_LINE_DESIGN';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_LINE_DESIGN','Line design');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_NONE';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_NONE','None');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_SQUARE';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_SQUARE','Square');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_CIRCLE';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_CIRCLE','Circle');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_POSITION_ON_YAXIS';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_POSITION_ON_YAXIS','Position on Y-axis');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_LEFT_FRONT';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_LEFT_FRONT','Left (front)');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_RIGHT_BACK';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_RIGHT_BACK','Right (back)');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_STROKE_COLOR';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_STROKE_COLOR','Stroke color');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_FILL_COLOR';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_FILL_COLOR','Fill color');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_TRANSPARENCY';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_TRANSPARENCY','Transparency');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_CLOSE';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_CLOSE','Close');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_WARNING_NO_NA_LEVEL_IN_COMMON';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_WARNING_NO_NA_LEVEL_IN_COMMON','Warning: no network aggregation level in common.');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_ERROR_YOU_DONT_HAVE_THE_RIGHT_TO_DELETE_THAT_GTM';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_ERROR_YOU_DONT_HAVE_THE_RIGHT_TO_DELETE_THAT_GTM','Error: you don''t have the right to delete that graph');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_TITLE';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_TITLE','Title');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_DEFINITION';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_DEFINITION','Definition');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_TROUBLESHOOTING';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_TROUBLESHOOTING','Troubleshooting');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_GRAPH_TYPE';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_GRAPH_TYPE','Graph type');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_GRAPH';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_GRAPH','Graph');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_PIE';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_PIE','Pie');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_SPLIT_BY';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_SPLIT_BY','Split by:');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_OVER_NETWORK_ELEMENT';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_OVER_NETWORK_ELEMENT','Over Network Element');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_ORDER_BY';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_ORDER_BY','Order by:');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_THERE_IS_NO_DATA_IN_YOUR_GRAPH';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_THERE_IS_NO_DATA_IN_YOUR_GRAPH','there is no data in your graph');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_ASC';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_ASC','Asc');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_DESC';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_DESC','Desc');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_YAXIS_LABEL';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_YAXIS_LABEL','Y-axis label');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_LEFT';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_LEFT','Left');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_RIGHT';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_RIGHT','Right');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_GRAPH_SIZE';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_GRAPH_SIZE','Graph size');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_WIDTH';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_WIDTH','Width');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_HEIGHT';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_HEIGHT','Height');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_MINIMUM_SIZE_WIDTH_HEIGHT';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_MINIMUM_SIZE_WIDTH_HEIGHT','(minimum size: width = $1, height = $2)');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_TOP';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_TOP','Top');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_RIGHT';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_RIGHT','Right');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_GIS';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_GIS','GIS');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_OFF';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_OFF','Off');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_ON';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_ON','On');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_BASED_ON';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_BASED_ON','based on:');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_THERE_IS_NO_DATA_IN_YOUR_GRAPH';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_THERE_IS_NO_DATA_IN_YOUR_GRAPH','there is no data in your graph');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_CREATE_NEW_GTM';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_CREATE_NEW_GTM','Create new graph');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_CLOSE';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_CLOSE','Close');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_ERROR_YOU_DONT_HAVE_THE_RIGHT_TO_SHARE_UNSHARE_THAT_GTM';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_ERROR_YOU_DONT_HAVE_THE_RIGHT_TO_SHARE_UNSHARE_THAT_GTM','Error: you don''t have the right to share/unshare that graph');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_RAW_COUNTERS_AND_KPI_FILTER';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_RAW_COUNTERS_AND_KPI_FILTER','Raw counters and KPI FILTER');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_RAW_COUNTERS';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_RAW_COUNTERS','Raw counters');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_KPI';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_KPI','KPI');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_GTM_ELEMENTS';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_GTM_ELEMENTS','Graph elements');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_KPIS';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_KPIS','KPIs');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_LEGENDS_POSITION';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_LEGENDS_POSITION','Legend''s position');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_ERROR_YOU_DONT_HAVE_THE_RIGHT_TO_CHANGE_THAT_OBJECT';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_ERROR_YOU_DONT_HAVE_THE_RIGHT_TO_CHANGE_THAT_OBJECT','Error: you don''t have the right to change that object');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_ERROR_THIS_GTM_IS_ALREADY_INSIDE_THAT_DASHBOARD';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_ERROR_THIS_GTM_IS_ALREADY_INSIDE_THAT_DASHBOARD','Error: this graph is already inside that dashboard.');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_ERROR_YOU_DONT_HAVE_THE_RIGHT_TO_CHANGE_THAT_DASHBOARD';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_ERROR_YOU_DONT_HAVE_THE_RIGHT_TO_CHANGE_THAT_DASHBOARD','Error: you don''t have the right to change that dashboard.');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_ERROR_YOU_DONT_HAVE_THE_RIGHT_TO_SHARE_UNSHARE_THAT_OBJECT';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_ERROR_YOU_DONT_HAVE_THE_RIGHT_TO_SHARE_UNSHARE_THAT_OBJECT','Error: you don''t have the right to share / unshare that object.');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_DELETE_THE_WHOLE_DASHBOARD';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_DELETE_THE_WHOLE_DASHBOARD','Delete the whole dashboard');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_GTMS_IN_YOUR_DASHBOARD';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_GTMS_IN_YOUR_DASHBOARD','Graphs in your dashboard');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_THIS_GTMS_DATA_IS_USED_FOR_THE_ORDER_BY_OF_THIS_DASHBOARD';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_THIS_GTMS_DATA_IS_USED_FOR_THE_ORDER_BY_OF_THIS_DASHBOARD','This graph data is used for the order by of this dashboard.');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_YOU_NEED_TO_ADD_A_GTM_TO_YOUR_DASHBOARD';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_YOU_NEED_TO_ADD_A_GTM_TO_YOUR_DASHBOARD','You need to add a graph to your dashboard');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_DASHBOARD_TITLE';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_DASHBOARD_TITLE','Dashboard title');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_MODE';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_MODE','Mode');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_OVERTIME';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_OVERTIME','Overtime');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_OVER_NETWORK_ELEMENTS';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_OVER_NETWORK_ELEMENTS','Over Network Elements');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_OVERTIME_OVER_NETWORK_ELEMENTS';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_OVERTIME_OVER_NETWORK_ELEMENTS','Overtime & Over Network Elements');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_MENU_LINK';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_MENU_LINK','Menu link');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_ONLINE';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_ONLINE','Online');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_SET_AS_HOMEPAGE';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_SET_AS_HOMEPAGE','Set as Homepage');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_HOMEPAGE_DEFAULT_MODE';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_HOMEPAGE_DEFAULT_MODE','Homepage default mode:');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_SELECTOR';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_SELECTOR','Selector:');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_DEFAULT_ORDER_BY';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_DEFAULT_ORDER_BY','Default order by');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_SORTING_ORDER';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_SORTING_ORDER','Sorting order');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_PERIOD';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_PERIOD','Period');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_TOP_OVER_TIME';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_TOP_OVER_TIME','Top Over Time');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_TOP_OVER_NETWORK';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_TOP_OVER_NETWORK','Top Over Network');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_NETWORK_AGGREGATION';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_NETWORK_AGGREGATION','Network Aggregation');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_NETWORK_AGGREGATION_FOR_AXE_3';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_NETWORK_AGGREGATION_FOR_AXE_3','Network Aggregation for axe 3');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_CREATE_NEW_DASHBOARD';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_CREATE_NEW_DASHBOARD','Create new dashboard');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_YOU_CANNOT_DELETE_THAT_GTM_AS_IT_BELONGS_TO_SOME_DASHBOARDS';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_YOU_CANNOT_DELETE_THAT_GTM_AS_IT_BELONGS_TO_SOME_DASHBOARDS','You cannot delete that graph as it belongs to some dashboard(s).');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_CREATE_NEW_REPORT';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_CREATE_NEW_REPORT','Create new report');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_ERROR_YOU_DONT_HAVE_THE_RIGHT_TO_DELETE_THAT_REPORT';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_ERROR_YOU_DONT_HAVE_THE_RIGHT_TO_DELETE_THAT_REPORT','Error: you don''t have the right to delete that report');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_ERROR_YOU_DONT_HAVE_THE_RIGHT_TO_CHANGE_THAT_REPORT';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_ERROR_YOU_DONT_HAVE_THE_RIGHT_TO_CHANGE_THAT_REPORT','Error: you don''t have the right to change that report');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_ERROR_THIS_IS_ALREADY_INSIDE_THAT_REPORT';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_ERROR_THIS_IS_ALREADY_INSIDE_THAT_REPORT','Error: this element is already inside that report');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_THIS_ITEM_BELONGS_TO_THESE_REPORTS';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_THIS_ITEM_BELONGS_TO_THESE_REPORTS','This dashboard belongs to these reports:');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_YOU_CANNOT_DELETE_THAT_DASHBOARD_AS_IT_BELONGS_TO_SOME_REPORTS';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_YOU_CANNOT_DELETE_THAT_DASHBOARD_AS_IT_BELONGS_TO_SOME_REPORTS','You cannot delete that dashboard as it belongs to some report(s).');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_DELETE_THE_REPORT';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_DELETE_THE_REPORT','Delete the Report?');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_DASHBOARDS_AND_ALARMS_IN_THAT_REPORT';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_DASHBOARDS_AND_ALARMS_IN_THAT_REPORT','Dashboards and alarms in that report:');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_DASHBOARD';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_DASHBOARD','dashboard');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_ALARM_FROM';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_ALARM_FROM','alarm from $1');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_PREVIEW_IN_PDF';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_PREVIEW_IN_PDF','Preview in PDF');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_DASHBOARDS';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_DASHBOARDS','Dashboards:');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_ASTELLIA';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_ASTELLIA','Astellia');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_ADMIN';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_ADMIN','Admin');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_USERS';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_USERS','Users');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_STATIC';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_STATIC','Static');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_DYNAMIC';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_DYNAMIC','Dynamic');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_TOP_WORST';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_TOP_WORST','Top/Worst');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_DASHBOARDS_AND_ALARMS';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_DASHBOARDS_AND_ALARMS','Dashboards and Alarms');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_ASTELLIA_DASHBOARDS';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_ASTELLIA_DASHBOARDS','Astellia Dashboards');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_ADMINISTRATOR_DASHBOARDS';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_ADMINISTRATOR_DASHBOARDS','Administrator Dashboards');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_USERS_DASHBOARDS';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_USERS_DASHBOARDS','Users Dashboards');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_ALARM_STATIC';
-- 03/06/2009 SPS modification du texte categorie alarmes statiques builder(correction bug 9934)
--insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_ALARM_STATIC','Alarm Static');
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_ALARM_STATIC','Static Alarms');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_ALARM_DYNAMIC';
-- 03/06/2009 SPS modification du texte categorie alarmes dynamiques builder (correction bug 9934)
--insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_ALARM_DYNAMIC','Alarm Dynamic');
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_ALARM_DYNAMIC','Dynamic Alarms');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_ALARM_TOPWORST';
-- 03/06/2009 SPS modification du texte categorie alarm top/worst builder (correction bug 9934)
--insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_ALARM_TOPWORST','Alarm Top/Worst');
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_ALARM_TOPWORST','Top/Worst Alarms');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_HELP_DRAG_TO_RE_ORDER';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_HELP_DRAG_TO_RE_ORDER','You can re-order the elements of the following list by simply dragging them up or down.');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_USING_THE_GRAPH_BUILDER';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_USING_THE_GRAPH_BUILDER','Show builder informations');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_HELP_CLICK_TO_ADD_RAWKPI';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_HELP_CLICK_TO_ADD_RAWKPI','Click on one of the raw counter or kpi of the left column to add it to that graph.');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_HELP_CLICK_TO_ADD_GRAPH';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_HELP_CLICK_TO_ADD_GRAPH','Click on one of the graph of the left column to add it to that dashboard.');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_HELP_CLICK_TO_ADD_DASH_ALARM';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_HELP_CLICK_TO_ADD_DASH_ALARM','Click on one of the dashboard or alarm of the left column to add it to that report.');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_ELEMENT_USED_FOR_OVERNETWORK_DEFAULT_SORTBY';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_ELEMENT_USED_FOR_OVERNETWORK_DEFAULT_SORTBY','Element used for default sort by');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_DEFAULT_ORDER_BY';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_DEFAULT_ORDER_BY','Default order by');

-- 09/12/2008 BBX : Correction du message d'erreur de setup system alerts
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_SYSTEM_ALERTS_UPDATE_FAIL';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_SYSTEM_ALERTS_UPDATE_FAIL', 'Some error has occured during the update of data');

-- 10/12/2008 BBX : Messages setup processes
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PROCESS_PARAMETERS_SAVED';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PROCESS_PARAMETERS_SAVED', 'The Process parameters have been saved');

DELETE FROM sys_definition_messages_display WHERE id = 'A_PROCESS_TIME_PERIOD_CANNOT_BE_NULL';
INSERT INTO sys_definition_messages_display VALUES ('A_PROCESS_TIME_PERIOD_CANNOT_BE_NULL', 'The time period cannot be null');

DELETE FROM sys_definition_messages_display WHERE id = 'A_PROCESS_ERROR_FAILED';
INSERT INTO sys_definition_messages_display VALUES ('A_PROCESS_ERROR_FAILED', 'Error. The process failed to launch : ');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PROCESS_HELP';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PROCESS_HELP', '<li>Each box enables you to manage a product''s processes</li><li>The master product is displayed with "[master]"</li><li>The topology master product is displayed with "[topology master]"</li><li>The time period determines the frequency the processes will be launched (if on).<br />For example, 0h 25mn means the process will start every 25 minutes.</li><li>The offset indicates from what hour / minute a process should start.<br />For example, time period : 0h 25mn / offset 0h 12mn means the process will start every 25 minutes after the next 12th minute (08h12 - 08h37 - 09h12 - 09h37 - etc...).</li><li>You can set an automatic value for time period by selecting "Daily" or "Hourly" from the select box.<br />Selecting "Hourly" will set the time period to "1h 0mn" and "Daily" to "24h 0mn".</li>');

-- 15/12/2008 BBX : SETUP PRODUCTS
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCTS_PRODUCT_DISABLED';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCTS_PRODUCT_DISABLED', 'This product is disabled');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCTS_MASTER_PRODUCT';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCTS_MASTER_PRODUCT', 'This is the master product');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCTS_TOPO_MASTER_PRODUCT';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCTS_TOPO_MASTER_PRODUCT', 'This is the topology master product');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCTS_ALL_MASTER_PRODUCT';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCTS_ALL_MASTER_PRODUCT', 'This is the master and topology master product');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCTS_EDIT';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCTS_EDIT', 'Edit this product');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCTS_SAVE_SUCCESS';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCTS_SAVE_SUCCESS', 'Save successfully completed');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCTS_CANNOT_DISABLE_MASTER';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCTS_CANNOT_DISABLE_MASTER', 'A master product cannot be disabled');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCTS_FORM_LABEL';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCTS_FORM_LABEL', 'Please, enter a correct product label');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCTS_FORM_IP';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCTS_FORM_IP', 'Please, enter a correct ip address');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCTS_FORM_DIRECTORY';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCTS_FORM_DIRECTORY', 'Please, enter a correct directory (string without slashes)');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCTS_FORM_DB_NAME';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCTS_FORM_DB_NAME', 'Please, enter a correct database name');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCTS_FORM_DB_PORT';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCTS_FORM_DB_PORT', 'Please, enter a correct database port');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCTS_FORM_DB_LOGIN';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCTS_FORM_DB_LOGIN', 'Please, enter a correct database login');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCTS_CHECK_NEW';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCTS_CHECK_NEW', 'Please, save the new product before adding another one');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCTS_TEST_CONNECTION_OK';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCTS_TEST_CONNECTION_OK', 'Success !');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCTS_TEST_CONNECTION_FAILED';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCTS_TEST_CONNECTION_FAILED', 'Connection failed');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCTS_CANNOT_CONNECT';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCTS_CANNOT_CONNECT', 'You cannot connect to a slave product');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCTS_NO_PARSER_DEFINED';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCTS_NO_PARSER_DEFINED', 'no parser defined');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCTS_FORM_SSH_USER';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCTS_FORM_SSH_USER', 'Please, enter a correct SSH user');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCTS_FORM_SSH_PASSWORD';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCTS_FORM_SSH_PASSWORD', 'Please, enter a correct SSH password');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCTS_FORM_SSH_PORT';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCTS_FORM_SSH_PORT', 'Please, enter a correct SSH port number');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCTS_CONFIRM_MASTER';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCTS_CONFIRM_MASTER', 'Are you sure to define this product as master ? This operation is irreversible');


-- 10/12/2008 GHX
-- Message concernant le mapping de la topologie
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_MAPPING_TOPO_UNDEFINE_MASTER_TOPO';
INSERT INTO sys_definition_messages_display VALUES ('A_E_MAPPING_TOPO_UNDEFINE_MASTER_TOPO', 'No product defined as master topology');

DELETE FROM sys_definition_messages_display WHERE id = 'A_MAPPING_TOPO_INFO_FOR_CHOOSE_MASTER_TOPO';
INSERT INTO sys_definition_messages_display VALUES ('A_MAPPING_TOPO_INFO_FOR_CHOOSE_MASTER_TOPO', 'You must choose a master topology. Go to <a href="$1">Setup Product</a>');

DELETE FROM sys_definition_messages_display WHERE id = 'A_MAPPING_TOPO_ONE_PRODUCT';
INSERT INTO sys_definition_messages_display VALUES ('A_MAPPING_TOPO_ONE_PRODUCT', 'You cannot use the mapping topology because there is standalone product.');

DELETE FROM sys_definition_messages_display WHERE id = 'A_MAPPING_TOPO_FORM_LABEL_MASTER_TOPO_NAME';
INSERT INTO sys_definition_messages_display VALUES ('A_MAPPING_TOPO_FORM_LABEL_MASTER_TOPO_NAME', 'Product name of master topology');

DELETE FROM sys_definition_messages_display WHERE id = 'A_MAPPING_TOPO_FORM_LABEL_LIST_OTHERS_PRODUCTS';
INSERT INTO sys_definition_messages_display VALUES ('A_MAPPING_TOPO_FORM_LABEL_LIST_OTHERS_PRODUCTS', 'List of products');

DELETE FROM sys_definition_messages_display WHERE id = 'A_MAPPING_TOPO_FORM_LABEL_LIST_OTHERS_PRODUCTS_INFO';
INSERT INTO sys_definition_messages_display VALUES ('A_MAPPING_TOPO_FORM_LABEL_LIST_OTHERS_PRODUCTS_INFO', 'to be mapped');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_MAPPING_TOPO_HEADER_COLUMN_NOT_VALID';
INSERT INTO sys_definition_messages_display VALUES ('A_E_MAPPING_TOPO_HEADER_COLUMN_NOT_VALID', 'Error in the file''s header - Columns are not valids : $1');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_MAPPING_TOPO_HEADER_COLUMN_MISSING';
INSERT INTO sys_definition_messages_display VALUES ('A_E_MAPPING_TOPO_HEADER_COLUMN_MISSING', 'Error in the file''s header - Missing the columns : $1');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_MAPPING_TOPO_COLUMN_MISSING';
INSERT INTO sys_definition_messages_display VALUES ('A_E_MAPPING_TOPO_COLUMN_MISSING', 'It miss the column "$1" lines : $2');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_MAPPING_TOPO_NA_NOT_EXISTS';
INSERT INTO sys_definition_messages_display VALUES ('A_E_MAPPING_TOPO_NA_NOT_EXISTS', '$1 $2 not exists on product $3');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_MAPPING_TOPO_NE_NOT_IN_TOPO';
INSERT INTO sys_definition_messages_display VALUES ('A_E_MAPPING_TOPO_NE_NOT_IN_TOPO', 'Network aggregation "$1" is not present on topology of product $2');

DELETE FROM sys_definition_messages_display WHERE id = 'A_MAPPING_MASTER_TOPO_IS_EMPTY';
INSERT INTO sys_definition_messages_display VALUES ('A_MAPPING_MASTER_TOPO_IS_EMPTY', 'Master topology $1 is empty. You cannot use the mapping tolopogy.');

DELETE FROM sys_definition_messages_display WHERE id = 'A_MAPPING_MASTER_TOPO_HEADER_COLUMN_EMPTY';
INSERT INTO sys_definition_messages_display VALUES ('A_MAPPING_MASTER_TOPO_HEADER_COLUMN_EMPTY', 'Error in the file''s header - The column $1 is empty');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_MAPPING_TOPO_NA_NOT_UNIQUE';
INSERT INTO sys_definition_messages_display VALUES ('A_E_MAPPING_TOPO_NA_NOT_UNIQUE', '$1 ($2) not unique [column: $3]');

DELETE FROM sys_definition_messages_display WHERE id = 'A_MAPPING_TOPO_INFO_TRUNCATE';
INSERT INTO sys_definition_messages_display VALUES ('A_MAPPING_TOPO_INFO_TRUNCATE', 'Select the product on which the topology mapping must be empty.');

DELETE FROM sys_definition_messages_display WHERE id = 'A_MAPPING_TOPO_UPLOAD_FILE_OK';
INSERT INTO sys_definition_messages_display VALUES ('A_MAPPING_TOPO_UPLOAD_FILE_OK', 'The mapping file has been successfully uploaded on product $1');

DELETE FROM sys_definition_messages_display WHERE id = 'A_MAPPING_TOPO_TRUNCATE_CONFIRM';
INSERT INTO sys_definition_messages_display VALUES ('A_MAPPING_TOPO_TRUNCATE_CONFIRM', 'Are you sure you empty the topology mapping');

DELETE FROM sys_definition_messages_display WHERE id = 'A_MAPPING_TOPO_TRUNCATE_OK';
INSERT INTO sys_definition_messages_display VALUES ('A_MAPPING_TOPO_TRUNCATE_OK', 'The mapping has been successfully empty on product $1');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_MAPPING_TOPO_SELECT_PRODUCT_TOPO_EMPTY';
INSERT INTO sys_definition_messages_display VALUES ('A_E_MAPPING_TOPO_SELECT_PRODUCT_TOPO_EMPTY', 'You cannot select product whose the topology is empty');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_MAPPING_TOPO_CODEQ_USED_INFO';
INSERT INTO sys_definition_messages_display VALUES ('A_E_MAPPING_TOPO_CODEQ_USED_INFO', 'Network elements are mapped one by one.In fact, one element can not reference two.');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_MAPPING_TOPO_CODEQ_USED';
INSERT INTO sys_definition_messages_display VALUES ('A_E_MAPPING_TOPO_CODEQ_USED', '($1) $2 is already mapped with $3, you cannot mapped it width $4.');

-- Les 3 messages suivants représentes le nom des colonnes de l'entête pour les fichiers de mapping
-- Ils ne doivent pas contenir de caractères spéciaux
DELETE FROM sys_definition_messages_display WHERE id = 'A_MAPPING_TOPO_COLUMN_LABEL_NA_ID_MAPPED';
INSERT INTO sys_definition_messages_display VALUES ('A_MAPPING_TOPO_COLUMN_LABEL_NA_ID_MAPPED', 'id');

DELETE FROM sys_definition_messages_display WHERE id = 'A_MAPPING_TOPO_COLUMN_LABEL_NA_ID_CODEQ';
INSERT INTO sys_definition_messages_display VALUES ('A_MAPPING_TOPO_COLUMN_LABEL_NA_ID_CODEQ', 'id_codeq');

DELETE FROM sys_definition_messages_display WHERE id = 'A_MAPPING_TOPO_COLUMN_LABEL_NA_TYPE';
INSERT INTO sys_definition_messages_display VALUES ('A_MAPPING_TOPO_COLUMN_LABEL_NA_TYPE', 'na');

-- change in Graph Builder (order_by -> split by)
DELETE FROM sys_definition_messages_display WHERE id = 'G_GDR_BUILDER_WITH';
INSERT INTO sys_definition_messages_display VALUES ('G_GDR_BUILDER_WITH', 'with');

DELETE FROM sys_definition_messages_display WHERE id = 'G_GDR_BUILDER_FIRST_AXIS';
INSERT INTO sys_definition_messages_display VALUES ('G_GDR_BUILDER_FIRST_AXIS', 'First Axis');

DELETE FROM sys_definition_messages_display WHERE id = 'G_GDR_BUILDER_THIRD_AXIS';
INSERT INTO sys_definition_messages_display VALUES ('G_GDR_BUILDER_THIRD_AXIS', 'Third Axis');

DELETE FROM sys_definition_messages_display WHERE id = 'G_GDR_BUILDER_THIRD_AXIS_IN_PURPLE';
INSERT INTO sys_definition_messages_display VALUES ('G_GDR_BUILDER_THIRD_AXIS_IN_PURPLE', 'Third axis in purple.');

-- maj 23/01/2009 - MPR : Ajout de messages dans l'interface de création des export de données (Export Data)
DELETE FROM sys_definition_messages_display WHERE id IN ('A_TASK_SCHEDULER_DATA_EXPORT_LABEL_SELECTION_PARENTS','A_TASK_SCHEDULER_DATA_EXPORT_LABEL_USE_CODE_NETWORKS','A_TASK_SCHEDULER_DATA_EXPORT_LABEL_GENERATE_HOURS_ON_DAY');
INSERT INTO sys_definition_messages_display(id, text) VALUES ('A_TASK_SCHEDULER_DATA_EXPORT_LABEL_SELECTION_PARENTS', 'Add Network Topology Reference In Data Export File');
INSERT INTO sys_definition_messages_display(id, text) VALUES ('A_TASK_SCHEDULER_DATA_EXPORT_LABEL_USE_CODE_NETWORKS', 'Use code network aggregation');
INSERT INTO sys_definition_messages_display(id, text) VALUES ('A_TASK_SCHEDULER_DATA_EXPORT_LABEL_GENERATE_HOURS_ON_DAY', 'Generate One file on the day');

-- 23/01/2009 GHX : ajout des messages d'erreur de Setup Product quand on ajout/modifie/teste une connexion sur un produit
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCTS_DATABASE_ALREADY_USES';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCTS_DATABASE_ALREADY_USES', 'The product "$1" already uses this database');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCTS_DIRECTORY_ALREADY_USES';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCTS_DIRECTORY_ALREADY_USES', 'The product "$1" already uses this directory');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCTS_PRODUCT_LABEL_MUST_BE_UNIQUE';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCTS_PRODUCT_LABEL_MUST_BE_UNIQUE', 'The product label must be unique');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCTS_CANNOT_CONNECT_DATABASE';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCTS_CANNOT_CONNECT_DATABASE', 'Can''t connect to the database');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCTS_HOST_NOT_REACHABLE';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCTS_HOST_NOT_REACHABLE', 'Host not reachable');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCTS_CB_INVALID';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCTS_CB_INVALID', 'Base component version is invalid');

-- 28/01/2009 - MPR : ajout des messages d'erreur js dans IHM Data Export
DELETE FROM sys_definition_messages_display WHERE id = 'A_JS_TASK_SCHEDULER_DATA_EXPORT_TARGET_DIR_NOT_VALID';
INSERT INTO sys_definition_messages_display(id, text) VALUES ('A_JS_TASK_SCHEDULER_DATA_EXPORT_TARGET_DIR_NOT_VALID',E'You\'re not allowed to access $1 or the directory doesn\'t exist on $2');

-- 10/02/2009 GHX
-- message pour le mapping topo
DELETE FROM sys_definition_messages_display WHERE id = 'A_MAPPING_TOPO_COLUMN_LABEL_NA_ID_MAPPED_INFO';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_MAPPING_TOPO_COLUMN_LABEL_NA_ID_MAPPED_INFO', 'Name of the column corresponding to the mapped product network identifiers');
DELETE FROM sys_definition_messages_display WHERE id = 'A_MAPPING_TOPO_COLUMN_LABEL_NA_ID_CODEQ_INFO';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_MAPPING_TOPO_COLUMN_LABEL_NA_ID_CODEQ_INFO', 'Name of the column corresponding to the topology product network identifiers');
DELETE FROM sys_definition_messages_display WHERE id = 'A_MAPPING_TOPO_COLUMN_LABEL_NA_TYPE_INFO';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_MAPPING_TOPO_COLUMN_LABEL_NA_TYPE_INFO', 'Name of the column corresponding to the network aggregation level');
DELETE FROM sys_definition_messages_display WHERE id = 'A_MAPPING_TITLE';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_MAPPING_TITLE', 'Mapping topology');
-- maj 09:44 22/09/2009 MPR : Correction du bug 11652 - Suppression du message d'info dans l'ihm du mapping de la topo
--DELETE FROM sys_definition_messages_display WHERE id = 'A_MAPPING_TOPO_FORM_LABEL_LIST_TYPE_COLUMN_NA';
--INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_MAPPING_TOPO_FORM_LABEL_LIST_TYPE_COLUMN_NA', 'Choose a type of value for name of the column corresponding to the network aggregation level :');
DELETE FROM sys_definition_messages_display WHERE id = 'A_MAPPING_EMPTY';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_MAPPING_EMPTY', 'Mapping Topology is empty');

-- 16/02/2009 - Modif. benoit : ajout d'un message pour la colonne "Product" dans la fenêtre d'informations des elements d'un GTM

DELETE FROM sys_definition_messages_display WHERE id = 'U_DATA_INFORMATION_PRODUCT';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('U_DATA_INFORMATION_PRODUCT', 'Product');

-- 19/02/2009 - Modif. benoit : ajout du message affiché lorsque le GTM ne contient pas de données

DELETE FROM sys_definition_messages_display WHERE id = 'U_GTM_NO_DATA_FOUND';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('U_GTM_NO_DATA_FOUND', '<h1>Information :</h1>No data found for <b>$1</b><br>$2 = <b>$3</b> - $4 $5 [ Top $6 Order By <b>$7</b> $8 ]');

-- 25/02/2009 - Modif. benoit : ajout du message affiché lorsque le GTM RI ne contient pas de données

DELETE FROM sys_definition_messages_display WHERE id = 'U_RI_NO_DATA_FOUND';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('U_RI_NO_DATA_FOUND', '<h1>Information :</h1>No data found for Reliability Indicator');

-- 04/03/2009 - Modif. benoit : ajout du message affiché lorsque l'on récupère les ne par ordre alphabétique dans le dashboard

DELETE FROM sys_definition_messages_display WHERE id = 'U_DASH_ORDERBY_ALPHA';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('U_DASH_ORDERBY_ALPHA', 'Over Time Sort : Indicator/Counter NULL or No Data Found -> Dashboard Element(s) Order by $1 DESC.');

-- 11:34 16/03/2009 GHX
-- Ajout des messages pour le montage d'un contexte et la génération d'un contexte
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_CONTEXT_MOUNT_SUBCONTEXT_INVALID_FORMAT';
INSERT INTO sys_definition_messages_display VALUES ('A_E_CONTEXT_MOUNT_SUBCONTEXT_INVALID_FORMAT', 'The format of subcontext $1 is incorrect');
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_CONTEXT_MOUNT_SUBCONTEXT_INVALID';
INSERT INTO sys_definition_messages_display VALUES ('A_E_CONTEXT_MOUNT_SUBCONTEXT_INVALID', 'The subcontext $1 is incorrect');
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_CONTEXT_MOUNT_SUBCONTEXT_UNABLE_EXTRACT';
INSERT INTO sys_definition_messages_display VALUES ('A_E_CONTEXT_MOUNT_SUBCONTEXT_UNABLE_EXTRACT', 'Unable to extract the subcontext $1');
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_CONTEXT_MOUNT_UNABLE_EXTRACT';
INSERT INTO sys_definition_messages_display VALUES ('A_E_CONTEXT_MOUNT_UNABLE_EXTRACT', 'Unable to extract the context');
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_CONTEXT_MOUNT_SUBCONTEXT_UNKOWN_EXTENSION';
INSERT INTO sys_definition_messages_display VALUES ('A_E_CONTEXT_MOUNT_SUBCONTEXT_UNKOWN_EXTENSION', 'Unkown the extension of the subcontext $1');
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_CONTEXT_MOUNT_UNKOWN_EXTENSION';
INSERT INTO sys_definition_messages_display VALUES ('A_E_CONTEXT_MOUNT_UNKOWN_EXTENSION', 'Unkown the extension of the file $1');
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_CONTEXT_MOUNT_UNABLE_LOAD_DATA_IN_TABLE_TEMP';
INSERT INTO sys_definition_messages_display VALUES ('A_E_CONTEXT_MOUNT_UNABLE_LOAD_DATA_IN_TABLE_TEMP', 'Error during the loading of data in the temporary table on product $1');
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_CONTEXT_MOUNT_UNABLE_CREATE_TABLE_TEMP';
INSERT INTO sys_definition_messages_display VALUES ('A_E_CONTEXT_MOUNT_UNABLE_CREATE_TABLE_TEMP', 'Error during the creation of the temporary table on product $1');
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_CONTEXT_MOUNT_UNDEFINED_PRIMARY_KEY';
INSERT INTO sys_definition_messages_display VALUES ('A_E_CONTEXT_MOUNT_UNDEFINED_PRIMARY_KEY', 'The table $1 has not defined primary key, unable to create the request for UPDATE and INSERT');
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_CONTEXT_MOUNT_NOT_FOUND_FIELD';
INSERT INTO sys_definition_messages_display VALUES ('A_E_CONTEXT_MOUNT_NOT_FOUND_FIELD', 'Unable to create the request for UPDATE and INSERT for the table $1 because not found field');
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_CONTEXT_MOUNT_UNABLED_SELECT_ELEMENT';
INSERT INTO sys_definition_messages_display VALUES ('A_E_CONTEXT_MOUNT_UNABLED_SELECT_ELEMENT', 'Unable to determine the elements present in subcontext $1');
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_CONTEXT_MOUNT_SUBCONTEXT_EMPTY';
INSERT INTO sys_definition_messages_display VALUES ('A_E_CONTEXT_MOUNT_SUBCONTEXT_EMPTY', 'The subcontext $1 is empty');
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_CONTEXT_MOUNT_EMPTY';
INSERT INTO sys_definition_messages_display VALUES ('A_E_CONTEXT_MOUNT_EMPTY', 'The context $1 is empty');
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_CONTEXT_MOUNT_NOT_EXTRACT';
INSERT INTO sys_definition_messages_display VALUES ('A_E_CONTEXT_MOUNT_NOT_EXTRACT', 'You can not mount the context because it''s not extract');
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_CONTEXT_MOUNT_UNABLE_MOUNT';
INSERT INTO sys_definition_messages_display VALUES ('A_E_CONTEXT_MOUNT_UNABLE_MOUNT', 'Unable to mount the context because it had not the same products in the context and the database');
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_CONTEXT_MOUNT_NOT_ARCHIVE';
INSERT INTO sys_definition_messages_display VALUES ('A_E_CONTEXT_MOUNT_NOT_ARCHIVE', 'The context $1 does not exists in directory $2');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_CONTEXT_DIRECTORY_NOT_WRITEABLE';
INSERT INTO sys_definition_messages_display VALUES ('A_E_CONTEXT_DIRECTORY_NOT_WRITEABLE', 'The directory $1 is not writeable');
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_CONTEXT_DIRECTORY_NOT_EXISTS';
INSERT INTO sys_definition_messages_display VALUES ('A_E_CONTEXT_DIRECTORY_NOT_EXISTS', 'The directory $1 does not exists');
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_CONTEXT_UNDEFINED_TABLE';
INSERT INTO sys_definition_messages_display VALUES ('A_E_CONTEXT_UNDEFINED_TABLE', 'Undefined table $1');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_CONTEXT_GENERATE_NOT_ELEMENT';
INSERT INTO sys_definition_messages_display VALUES ('A_E_CONTEXT_GENERATE_NOT_ELEMENT', '$1 is not instance of ContextElement');
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_CONTEXT_GENERATE_UNABLE_COPY_DATA_IN_FILE_CSV';
INSERT INTO sys_definition_messages_display VALUES ('A_E_CONTEXT_GENERATE_UNABLE_COPY_DATA_IN_FILE_CSV', 'Error during the execution of data copy in file csv with the product $1');
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_CONTEXT_GENERATE_UNABLE_COMPRESS';
INSERT INTO sys_definition_messages_display VALUES ('A_E_CONTEXT_GENERATE_UNABLE_COMPRESS', 'Error during the compression');

-- 19/03/2009 - ajout SPS : message d'erreur lors de l'export
DELETE FROM sys_definition_messages_display WHERE id = 'U_E_EXPORT_FILE_NOT_GENERATED';
INSERT INTO sys_definition_messages_display VALUES ('U_E_EXPORT_FILE_NOT_GENERATED','File not generated : no data.');

-- 20/03/2009 - ajout SPS : message du tooltip pour les commentaires
DELETE FROM sys_definition_messages_display WHERE id = 'U_TOOLTIP_SHOW_COMMENTS';
INSERT INTO sys_definition_messages_display VALUES ('U_TOOLTIP_SHOW_COMMENTS','Display all comments / View comments history');
DELETE FROM sys_definition_messages_display WHERE id = 'U_TOOLTIP_ADD_COMMENT';
INSERT INTO sys_definition_messages_display VALUES ('U_TOOLTIP_ADD_COMMENT','Add a comment');
DELETE FROM sys_definition_messages_display WHERE id = 'U_NO_COMMENT';
INSERT INTO sys_definition_messages_display VALUES ('U_NO_COMMENT','No comment...');

-- 23/03/2009 - ajout SPS : message d'erreur pour l'upload
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_UPLOAD_FILE_NOT_COPIED';
INSERT INTO sys_definition_messages_display VALUES ('A_E_UPLOAD_FILE_NOT_COPIED','Impossible to copy the file');
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_UPLOAD_FILE_EXTENSION_INCORRECT';
INSERT INTO sys_definition_messages_display VALUES ('A_E_UPLOAD_FILE_EXTENSION_INCORRECT','Incorrect file extension');
DELETE FROM sys_definition_messages_display WHERE id = 'A_UPLOAD_SUCCESS';
INSERT INTO sys_definition_messages_display VALUES ('A_UPLOAD_SUCCESS','File $1 uploaded');

-- 24/03/2009 - ajout SPS : messages pour la gestion des contextes
DELETE FROM sys_definition_messages_display WHERE id = 'A_CONTEXT_FILE_DELETED';
INSERT INTO sys_definition_messages_display VALUES ('A_CONTEXT_FILE_DELETED','Context file $1 deleted');
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_CONTEXT_NONE';
INSERT INTO sys_definition_messages_display VALUES ('A_E_CONTEXT_NONE','No context');
DELETE FROM sys_definition_messages_display WHERE id = 'A_CONTEXT_INSTALLED';
INSERT INTO sys_definition_messages_display VALUES ('A_CONTEXT_INSTALLED','Context installed');
DELETE FROM sys_definition_messages_display WHERE id = 'A_CONTEXT_RESTORED';
INSERT INTO sys_definition_messages_display VALUES ('A_CONTEXT_RESTORED','Context file $1 restored');

-- 25/03/2009 - ajout SPS : messages pour la gestion des contextes
DELETE FROM sys_definition_messages_display WHERE id = 'A_JS_CONTEXT_BUILD_SURE_TO_DELETE';
INSERT INTO sys_definition_messages_display VALUES ('A_JS_CONTEXT_BUILD_SURE_TO_DELETE','Are you sure to delete the context file [$1] ?');
DELETE FROM sys_definition_messages_display WHERE id = 'A_JS_CONTEXT_BUILD_PLEASE_WRITE_CONTEXT_NAME';
INSERT INTO sys_definition_messages_display VALUES ('A_JS_CONTEXT_BUILD_PLEASE_WRITE_CONTEXT_NAME','Please give a context name');
DELETE FROM sys_definition_messages_display WHERE id = 'A_JS_CONTEXT_BUILD_PLEASE_WRITE_CONTEXT_VERSION';
INSERT INTO sys_definition_messages_display VALUES ('A_JS_CONTEXT_BUILD_PLEASE_WRITE_CONTEXT_VERSION','Please give a context version');

-- 26/03/2009 - ajout SPS : messages pour la gestion des contextes
DELETE FROM sys_definition_messages_display WHERE id = 'A_JS_CONTEXT_NAME_NOT_VALID';
INSERT INTO sys_definition_messages_display VALUES ('A_JS_CONTEXT_NAME_NOT_VALID','The context name is not valid. (only alphanumeric characters, "." and "_" are allowed)');
DELETE FROM sys_definition_messages_display WHERE id = 'A_JS_CONTEXT_VERSION_NOT_VALID';
INSERT INTO sys_definition_messages_display VALUES ('A_JS_CONTEXT_VERSION_NOT_VALID','The context version is not valid. (only alphanumeric characters, "." and "_" are allowed)');
DELETE FROM sys_definition_messages_display WHERE id = 'A_JS_CONTEXT_BUILD_SURE_TO_INSTALL';
INSERT INTO sys_definition_messages_display VALUES ('A_JS_CONTEXT_BUILD_SURE_TO_INSTALL','Are you sure to install the context file [$1] ?');
DELETE FROM sys_definition_messages_display WHERE id = 'A_JS_CONTEXT_BUILD_SURE_TO_RESTORE';
INSERT INTO sys_definition_messages_display VALUES ('A_JS_CONTEXT_BUILD_SURE_TO_RESTORE','Are you sure to restore the context file [$1] ?');
DELETE FROM sys_definition_messages_display WHERE id = 'A_CONTEXT_MANAGEMENT_PAGE';
INSERT INTO sys_definition_messages_display VALUES ('A_CONTEXT_MANAGEMENT_PAGE','Context Management');
DELETE FROM sys_definition_messages_display WHERE id = 'A_CONTEXT_UPLOAD';
INSERT INTO sys_definition_messages_display VALUES ('A_CONTEXT_UPLOAD','Upload a context');
DELETE FROM sys_definition_messages_display WHERE id = 'A_CONTEXT_BUILD';
INSERT INTO sys_definition_messages_display VALUES ('A_CONTEXT_BUILD','Build a context');
DELETE FROM sys_definition_messages_display WHERE id = 'A_CONTEXT_MOUNT';
INSERT INTO sys_definition_messages_display VALUES ('A_CONTEXT_MOUNT','Mount a context');
DELETE FROM sys_definition_messages_display WHERE id = 'A_CONTEXT_RESTORE';
INSERT INTO sys_definition_messages_display VALUES ('A_CONTEXT_RESTORE','Reinitialize before the context');
DELETE FROM sys_definition_messages_display WHERE id = 'A_CONTEXT_DELETE';
INSERT INTO sys_definition_messages_display VALUES ('A_CONTEXT_DELETE','Delete a context file');
DELETE FROM sys_definition_messages_display WHERE id = 'A_CONTEXT_NAME';
INSERT INTO sys_definition_messages_display VALUES ('A_CONTEXT_NAME','Context name');
DELETE FROM sys_definition_messages_display WHERE id = 'A_CONTEXT_VERSION';
INSERT INTO sys_definition_messages_display VALUES ('A_CONTEXT_VERSION','Context version');
DELETE FROM sys_definition_messages_display WHERE id = 'A_DATE';
INSERT INTO sys_definition_messages_display VALUES ('A_DATE','Date');
DELETE FROM sys_definition_messages_display WHERE id = 'A_DONE';
INSERT INTO sys_definition_messages_display VALUES ('A_DONE','Done');
DELETE FROM sys_definition_messages_display WHERE id = 'A_MOUNT';
INSERT INTO sys_definition_messages_display VALUES ('A_MOUNT','Mount');
DELETE FROM sys_definition_messages_display WHERE id = 'A_RESTORE';
INSERT INTO sys_definition_messages_display VALUES ('A_RESTORE','Reinitialise');
DELETE FROM sys_definition_messages_display WHERE id = 'A_DELETE';
INSERT INTO sys_definition_messages_display VALUES ('A_DELETE','Delete the file');
DELETE FROM sys_definition_messages_display WHERE id = 'A_CONTEXT_CONTENT';
INSERT INTO sys_definition_messages_display VALUES ('A_CONTEXT_CONTENT','Context content');
DELETE FROM sys_definition_messages_display WHERE id = 'A_JS_CONTEXT_FILE_BE_CREATED';
INSERT INTO sys_definition_messages_display VALUES ('A_JS_CONTEXT_FILE_BE_CREATED','Context file $1_$2.tar.bz2 will be created');
DELETE FROM sys_definition_messages_display WHERE id = 'A_CONTEXT_PROPERTIES';
INSERT INTO sys_definition_messages_display VALUES ('A_CONTEXT_PROPERTIES','Context properties');

--27/03/2009 - ajout SPS : nom des boutons pour la gestion des contextes
DELETE FROM sys_definition_messages_display WHERE id = 'A_CONTEXT_UPLOAD_BUTTON';
INSERT INTO sys_definition_messages_display VALUES ('A_CONTEXT_UPLOAD_BUTTON','Upload');
DELETE FROM sys_definition_messages_display WHERE id = 'A_CONTEXT_BUILD_BUTTON';
INSERT INTO sys_definition_messages_display VALUES ('A_CONTEXT_BUILD_BUTTON','Build context');
DELETE FROM sys_definition_messages_display WHERE id = 'A_CONTEXT_FILE_NOT_FOUND';
INSERT INTO sys_definition_messages_display VALUES ('A_CONTEXT_FILE_NOT_FOUND','Context file $1 not found');
DELETE FROM sys_definition_messages_display WHERE id = 'A_CONTEXT_UPLOADED';
INSERT INTO sys_definition_messages_display VALUES ('A_CONTEXT_UPLOADED','Uploaded context');

--28/03/2009 - ajout SPS 
DELETE FROM sys_definition_messages_display WHERE id = 'A_CONTEXT_DOWNLOAD';
INSERT INTO sys_definition_messages_display VALUES ('A_CONTEXT_DOWNLOAD','Download the $1 context file');
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_CONTEXT_NO_ELEMENTS_SELECTED';
INSERT INTO sys_definition_messages_display VALUES ('A_E_CONTEXT_NO_ELEMENTS_SELECTED','No selected elements');

-- 31/03/2009 - BBX
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_NETWORK_AGGREGATION_ACTIVATION_PROCESS_IS_RUNNING';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_NETWORK_AGGREGATION_ACTIVATION_PROCESS_IS_RUNNING','A process is running. Please, wait the end of the process before activating a network aggregation');
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_NETWORK_AGGREGATION_DELETION_PROCESS_IS_RUNNING';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_NETWORK_AGGREGATION_DELETION_PROCESS_IS_RUNNING','A process is running. Please, wait the end of the process before deleting a network aggregation');

-- 16:18 06/04/2009 GHX
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_CONTEXT_GENERATE_CODE_PRODUCT_NO_EXISTS';
INSERT INTO sys_definition_messages_display VALUES ('A_E_CONTEXT_GENERATE_CODE_PRODUCT_NO_EXISTS', 'The product code doesn''t exist for the product $1');

-- 08/04/2009 BBX
DELETE FROM sys_definition_messages_display WHERE id = 'U_MYPROFILE_NASELECTION_NOTICE_COMMON_ONLY';
INSERT INTO sys_definition_messages_display VALUES ('U_MYPROFILE_NASELECTION_NOTICE_COMMON_ONLY', 'Notice : only common network elements will be displayed');

-- 15/04/2009 SLC messages du selecteur
delete from sys_definition_messages_display where id='SELECTEUR_NEL_SELECTION';
insert into sys_definition_messages_display (id,text) values ('SELECTEUR_NEL_SELECTION','Network element selection.');

delete from sys_definition_messages_display where id='SELECTEUR_TOP_OVER_NETWORK';
insert into sys_definition_messages_display (id,text) values ('SELECTEUR_TOP_OVER_NETWORK','Top Over Network');

delete from sys_definition_messages_display where id='SELECTEUR_TOP_OVER_TIME';
insert into sys_definition_messages_display (id,text) values ('SELECTEUR_TOP_OVER_TIME','Top Over Time');

delete from sys_definition_messages_display where id='SELECTEUR_NONE';
insert into sys_definition_messages_display (id,text) values ('SELECTEUR_NONE','None');

delete from sys_definition_messages_display where id='SELECTEUR_ASC';
insert into sys_definition_messages_display (id,text) values ('SELECTEUR_ASC','Asc');

delete from sys_definition_messages_display where id='SELECTEUR_DESC';
insert into sys_definition_messages_display (id,text) values ('SELECTEUR_DESC','Desc');

delete from sys_definition_messages_display where id='SELECTEUR_RAW_KPI_FILTER';
insert into sys_definition_messages_display (id,text) values ('SELECTEUR_RAW_KPI_FILTER','Raw/Kpi Filter');


delete from sys_definition_messages_display where id='SELECTEUR_CALENDAR';
insert into sys_definition_messages_display (id,text) values ('SELECTEUR_CALENDAR','Calendar');

delete from sys_definition_messages_display where id='SELECTEUR_HOUR_SELECTOR';
insert into sys_definition_messages_display (id,text) values ('SELECTEUR_HOUR_SELECTOR','Hour selector');

delete from sys_definition_messages_display where id='SELECTEUR_PERIOD';
insert into sys_definition_messages_display (id,text) values ('SELECTEUR_PERIOD','Period:');

delete from sys_definition_messages_display where id='SELECTEUR_NO_VALUE_FOUND';
insert into sys_definition_messages_display (id,text) values ('SELECTEUR_NO_VALUE_FOUND','No value found');

delete from sys_definition_messages_display where id='SELECTEUR_ON_N_ELEMENT';
insert into sys_definition_messages_display (id,text) values ('SELECTEUR_ON_N_ELEMENT','on $1 element');

delete from sys_definition_messages_display where id='SELECTEUR_ON_N_ELEMENTS';
insert into sys_definition_messages_display (id,text) values ('SELECTEUR_ON_N_ELEMENTS','on $1 elements');

delete from sys_definition_messages_display where id='SELECTEUR_NO_RESULT';
insert into sys_definition_messages_display (id,text) values ('SELECTEUR_NO_RESULT','No result');

delete from sys_definition_messages_display where id='SELECTEUR_DELETE_FROM_CURRENT_SELECTION';
insert into sys_definition_messages_display (id,text) values ('SELECTEUR_DELETE_FROM_CURRENT_SELECTION','Delete from current selection');

delete from sys_definition_messages_display where id='SELECTEUR_TIME';
insert into sys_definition_messages_display (id,text) values ('SELECTEUR_TIME','Time');

delete from sys_definition_messages_display where id='SELECTEUR_NETWORK_AGGREGATION';
insert into sys_definition_messages_display (id,text) values ('SELECTEUR_NETWORK_AGGREGATION','Network Aggregation');

delete from sys_definition_messages_display where id='SELECTEUR_SORT_BY';
insert into sys_definition_messages_display (id,text) values ('SELECTEUR_SORT_BY','Sort by');

delete from sys_definition_messages_display where id='SELECTEUR_NO_RESPONSE';
insert into sys_definition_messages_display (id,text) values ('SELECTEUR_NO_RESPONSE','No response text.');

delete from sys_definition_messages_display where id='SELECTEUR_APPLICATION_CANT_ACCESS_TO';
insert into sys_definition_messages_display (id,text) values ('SELECTEUR_APPLICATION_CANT_ACCESS_TO','Application can''t access to');

delete from sys_definition_messages_display where id='SELECTEUR_FILTER_NOT_SET';
insert into sys_definition_messages_display (id,text) values ('SELECTEUR_FILTER_NOT_SET','The filter operand is not set.');

delete from sys_definition_messages_display where id='SELECTEUR_FILTER_EMPTY';
insert into sys_definition_messages_display (id,text) values ('SELECTEUR_FILTER_EMPTY','The filter value is empty.');

delete from sys_definition_messages_display where id='SELECTEUR_FILTER_NOT_NUMERIC';
insert into sys_definition_messages_display (id,text) values ('SELECTEUR_FILTER_NOT_NUMERIC','The filter value is not numeric.');

delete from sys_definition_messages_display where id='SELECTEUR_AUTOREFRESH';
insert into sys_definition_messages_display (id,text) values ('SELECTEUR_AUTOREFRESH','autorefresh');

-- 10:41 20/04/2009 GHX
-- Ajout oublis
delete from sys_definition_messages_display where id='G_GDR_BUILDER_BY_FAMILY';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_BY_FAMILY','By family');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_NO_SORT';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_NO_SORT','No sort');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_TYPE_OF_DISPLAY';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_TYPE_OF_DISPLAY','Type of display');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_PRODUCT_LIST';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_PRODUCT_LIST','Product list');

delete from sys_definition_messages_display where id='G_GDR_BUILDER_TYPE_OF_ELEMENTS';
insert into sys_definition_messages_display (id,text) values ('G_GDR_BUILDER_TYPE_OF_ELEMENTS','Type of elements');

-- 04/05/2009 SPS
--ajout message d'erreur pour l'ajout d'une ligne
delete from sys_definition_messages_display where id='G_JS_DRAWLINE_VALUE_NOT_VALID';
insert into sys_definition_messages_display (id,text) values ('G_JS_DRAWLINE_VALUE_NOT_VALID','Please enter a valid number for this value !');

delete from sys_definition_messages_display where id='G_JS_DRAWLINE_NO_LEGEND';
insert into sys_definition_messages_display (id,text) values ('G_JS_DRAWLINE_NO_LEGEND','Please enter a legend !');

--06/05/2009 SPS
--labels pour l'ajout de ligne
delete from sys_definition_messages_display where id='G_DRAWLINE_ALIGN_ON';
insert into sys_definition_messages_display (id,text) values ('G_DRAWLINE_ALIGN_ON','Align on');

delete from sys_definition_messages_display where id='G_DRAWLINE_VALUE';
insert into sys_definition_messages_display (id,text) values ('G_DRAWLINE_VALUE','Value');

delete from sys_definition_messages_display where id='G_DRAWLINE_LEGEND';
insert into sys_definition_messages_display (id,text) values ('G_DRAWLINE_LEGEND','Legend');

delete from sys_definition_messages_display where id='G_DRAWLINE_COLOR';
insert into sys_definition_messages_display (id,text) values ('G_DRAWLINE_COLOR','Color');

delete from sys_definition_messages_display where id='G_DRAWLINE_UPDATE_ALL';
insert into sys_definition_messages_display (id,text) values ('G_DRAWLINE_UPDATE_ALL','Update all graphs');

delete from sys_definition_messages_display where id='G_DRAWLINE_REMOVE_LINES';
insert into sys_definition_messages_display (id,text) values ('G_DRAWLINE_REMOVE_LINES','Remove all lines');

delete from sys_definition_messages_display where id='G_DRAWLINE_BTN_UPDATE';
insert into sys_definition_messages_display (id,text) values ('G_DRAWLINE_BTN_UPDATE','Update');

delete from sys_definition_messages_display where id='G_DRAWLINE_BTN_CLOSE';
insert into sys_definition_messages_display (id,text) values ('G_DRAWLINE_BTN_CLOSE','Close');

--06/05/2009 SPS
--message pendant l'ajout de ligne
delete from sys_definition_messages_display where id='G_JS_DRAWLINE_UPDATING_GTM';
insert into sys_definition_messages_display (id,text) values ('G_JS_DRAWLINE_UPDATING_GTM','Updating GTM...');

-- 11/05/2009 GHX
-- Message si pas de donnée pour le split By d'un PIE
delete from sys_definition_messages_display where id = 'U_GTM_NO_DATA_FOUND_SPLIT_BY';
insert into sys_definition_messages_display (id, text) VALUES ('U_GTM_NO_DATA_FOUND_SPLIT_BY', '<h1>Information :</h1>No data found for <b>$1</b><br>Split by (<b>$9</b>) is NULL or no data found<br>$2 = <b>$3</b> - $4 $5 [ Top $6 Order By <b>$7</b> $8 ]');
-- Message si pas de donnée pour un PIE
DELETE FROM sys_definition_messages_display WHERE id = 'U_GTM_NO_DATA_FOUND_PIE';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('U_GTM_NO_DATA_FOUND_PIE', '<h1>Information :</h1>No data found for <b>$1</b><br>$2 = <b>$3</b> - $4 $5 [ Top $6 Order By <b>$7</b> $8 split width <b>$9</b>] Split by $10');

-- 11:26 14/05/2009 GHX
-- message dans le cas d'un pie en OT qui n'a pas de données sur le split by
-- attention le message U_GTM_NO_DATA_FOUND_SPLIT_BY n'est pas dans le même cas
DELETE FROM sys_definition_messages_display WHERE id = 'U_GTM_NO_DATA_FOUND_SPLIT_BY_OT';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('U_GTM_NO_DATA_FOUND_SPLIT_BY_OT', '<h1>Information :</h1>No data found for <b>$1</b><br>Split by (<b>$8</b>) is NULL or no data found<br>$2 = <b>$3</b> - $4 $5 [ Order By <b>$6</b> $7 split width <b>$9</b>] Split by $10');

-- 14/05/2009 SPS
-- message si lors d'un ajout de produit, son dossier n'existe pas
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCTS_DIRECTORY_MUST_EXIST';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_SETUP_PRODUCTS_DIRECTORY_MUST_EXIST', 'The product directory must exist');

-- message d'erreur qd on saisit un nom de menu avec des caracteres incorrects
DELETE FROM sys_definition_messages_display WHERE id = 'A_JS_MENU_MANAGEMENT_MENU_NAME_IS_NOT_VALID';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_JS_MENU_MANAGEMENT_MENU_NAME_IS_NOT_VALID', 'Please enter a valid menu name (only alphanumeric characters, " ", "." and "-" are allowed)');

-- 15/05/2009 SPS
-- messages d'erreur pour l'edition d'un user
DELETE FROM sys_definition_messages_display WHERE id = 'A_JS_USER_EDIT_LASTNAME_NOT_VALID';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_JS_USER_EDIT_LASTNAME_NOT_VALID', 'Please enter a valid lastname (only alphanumeric characters, " ", "." and "-" are allowed)');

DELETE FROM sys_definition_messages_display WHERE id = 'A_JS_USER_EDIT_FIRSTNAME_NOT_VALID';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_JS_USER_EDIT_FIRSTNAME_NOT_VALID', 'Please enter a valid firstname (only alphanumeric characters, " ", "." and "-" are allowed)');

DELETE FROM sys_definition_messages_display WHERE id = 'A_JS_USER_EDIT_LOGIN_NOT_VALID';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_JS_USER_EDIT_LOGIN_NOT_VALID', 'Please enter a valid login (only alphanumeric characters, " ", "." and "-" are allowed)');

DELETE FROM sys_definition_messages_display WHERE id = 'A_JS_USER_EDIT_PASSWORD_NOT_VALID';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_JS_USER_EDIT_PASSWORD_NOT_VALID', 'Please enter a valid password (only alphanumeric characters and "_" are allowed, minLength:6, maxLength:64)');

DELETE FROM sys_definition_messages_display WHERE id = 'A_JS_USER_EDIT_EMAIL_NOT_VALID';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_JS_USER_EDIT_EMAIL_NOT_VALID', 'Please enter a valid email');

-- message d'erreur si la date de validite du login n'est plus bonne
DELETE FROM sys_definition_messages_display WHERE id = 'U_LOGIN_DATE_VALID_EXPIRED';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('U_LOGIN_DATE_VALID_EXPIRED', 'The login has expired.');


-- Setup connection : browse remote directory
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_CONNECTION_BROWSE_REMOTE';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_SETUP_CONNECTION_BROWSE_REMOTE', 'Browse remote directory');

-- 10:54 25/05/2009 GHX
-- Message quand un filtre ne retourne pas de valeur
DELETE FROM sys_definition_messages_display WHERE id = 'U_DASH_FILTER_NO_DATA';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('U_DASH_FILTER_NO_DATA', 'Filter : Indicator/Counter NULL or No Data Found');

-- Message display : Product key successfully updated
DELETE FROM sys_definition_messages_display WHERE id = 'A_ABOUT_KEY_SUCCESSFULLY_UPDATED';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_ABOUT_KEY_SUCCESSFULLY_UPDATED', 'Product key successfully updated');
-- Message display : Product key is not valid
DELETE FROM sys_definition_messages_display WHERE id = 'A_ABOUT_KEY_NOT_VALID';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_ABOUT_KEY_NOT_VALID', 'Product key is not valid');

-- 25/05/2009 SPS 
-- investigation dashboard : il faut selectionner au moins un element reseau et un raw/kpi
DELETE FROM sys_definition_messages_display WHERE id = 'U_INVESTIGATION_DASHBOARD_NOT_ENOUGH_ELEMENTS';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('U_INVESTIGATION_DASHBOARD_NOT_ENOUGH_ELEMENTS', 'Please select at least one Network Element and one Raw/Kpi !');

-- 26/05/2009 SPS 
-- investigation dashboard : trop d'elements a afficher sur le graph => on limite
DELETE FROM sys_definition_messages_display WHERE id = 'U_INVESTIGATION_DASHBOARD_TOO_MANY_ELEMENTS';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('U_INVESTIGATION_DASHBOARD_TOO_MANY_ELEMENTS', 'Too many elements to display, limiting to $1');

-- 27/05/2009 SPS 
-- investigation dashboard : nom du selecteur raw/kpi
DELETE FROM sys_definition_messages_display WHERE id = 'SELECTEUR_RAW_KPI_SELECTION';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('SELECTEUR_RAW_KPI_SELECTION', 'Raw/KPI Selection');

-- 28/05/2009 SPS
-- investigation dashboard : message d'erreur sur le graph si pas de donnees
DELETE FROM sys_definition_messages_display WHERE id = 'U_INVESTIGATION_DASHBOARD_NO_DATA_FOUND';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('U_INVESTIGATION_DASHBOARD_NO_DATA_FOUND', '<h1>Information :</h1> No data found');

-- 02/06/2009 SPS
-- message d'erreur si on ajoute que des raw/kpi avec ordonnee a droite pour un graph
DELETE FROM sys_definition_messages_display WHERE id = 'G_GTM_BUILDER_NO_LEFT_Y_AXIS_POSITION';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('G_GTM_BUILDER_NO_LEFT_Y_AXIS_POSITION', 'Please select at least one raw/kpi on left y-axis position !');

-- 09/06/2009 MPR
-- message d'erreur indiquant que le niveau agrégation de la clé n'existe pas dans ce produit
-- Message display : Product key is not valid
DELETE FROM sys_definition_messages_display WHERE id = 'A_ABOUT_NA_IN_KEY_NOT_VALID';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_ABOUT_NA_IN_KEY_NOT_VALID', 'Network Agregation level of the key doesn''t not exist for this product');


-- 12:16 15/06/2009 GHX
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_CONTEXT_ACTIVATION_UNABLE_ONE_PRODUCT';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_CONTEXT_ACTIVATION_UNABLE_ONE_PRODUCT', 'Unable activation with one product');

DELETE FROM sys_definition_messages_display WHERE id = 'A_E_CONTEXT_ACTIVATION_UNABLE_ON_MASTER';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_CONTEXT_ACTIVATION_UNABLE_ON_MASTER', 'Unable activation on master product');

-- 14:39 16/06/2009 MPR
-- Tooltip sur la checkbox Generate One file on the day (Data Export)
DELETE FROM sys_definition_messages_display WHERE id = 'A_TASK_SCHEDULER_DATA_EXPORT_TOOLTIP_GENERATE_ONE_FILE_HOUR';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_TASK_SCHEDULER_DATA_EXPORT_TOOLTIP_GENERATE_ONE_FILE_HOUR', 'If checked, the export file will contain all hours of the calculated day. The export file will be generated during the Compute day.<br />Else, the export file will only contain the calculated hour and will be generated during the compute Hour.');

-- 18/06/2009 BBX : 
-- More than 1 $1 product cannot be activated
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCTS_PRODUCT_ALREADY_EXISTS';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_SETUP_PRODUCTS_PRODUCT_ALREADY_EXISTS', 'More than 1 $1 product cannot be activated');

-- 18/06/2009 BBX : 
-- Please, define a master product
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCTS_DEFINE_MASTER';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_SETUP_PRODUCTS_DEFINE_MASTER', 'Please, define a master product');

-- 18/06/2009 BBX : 
-- Building file
DELETE FROM sys_definition_messages_display WHERE id = 'A_EXPORTS_BUILDING_FILE';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_EXPORTS_BUILDING_FILE', 'Building file');

-- 11:47 22/06/2009 GHX
DELETE FROM sys_definition_messages_display WHERE id = 'G_E_AUTO_DISABLE_PRODUCT';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('G_E_AUTO_DISABLE_PRODUCT', 'Automatic disactivation of product $1 : unable to connect to the database');

-- 11:14 01/07/2009 MPR
-- 05/03/2008 maxime : Ajout de messages pour l'évolution du TraceLog
DELETE FROM sys_definition_messages_display WHERE id = 'A_TRACELOG_DETAILS_MODULE_COMPUTE';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_TRACELOG_DETAILS_MODULE_COMPUTE','Compute');

DELETE FROM sys_definition_messages_display WHERE id = 'A_TRACELOG_DETAILS_NO_RESULTS';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_TRACELOG_DETAILS_NO_RESULTS','No Results');


-- 21/03/2008 - Maxime - Modification des labels des modules dans le tracelog 
DELETE FROM sys_definition_messages_display WHERE id = 'A_TRACELOG_MODULE_LABEL_COLLECT';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_TRACELOG_MODULE_LABEL_COLLECT','Data Collect');

DELETE FROM sys_definition_messages_display WHERE id = 'A_TRACELOG_MODULE_LABEL_ALARM';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_TRACELOG_MODULE_LABEL_ALARM','Alarm Calculation');

DELETE FROM sys_definition_messages_display WHERE id = 'A_TRACELOG_MODULE_LABEL_CHECK_PROCESS_EXECUTION_TIME';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_TRACELOG_MODULE_LABEL_CHECK_PROCESS_EXECUTION_TIME','Process check');

DELETE FROM sys_definition_messages_display WHERE id = 'A_TRACELOG_MODULE_LABEL_COMPUTE';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_TRACELOG_MODULE_LABEL_COMPUTE','Data Compute');

DELETE FROM sys_definition_messages_display WHERE id = 'A_TRACELOG_MODULE_LABEL_DUMP_DATABASE';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_TRACELOG_MODULE_LABEL_DUMP_DATABASE','Backup Database');

DELETE FROM sys_definition_messages_display WHERE id = 'A_TRACELOG_MODULE_LABEL_LICENCE_KEY';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_TRACELOG_MODULE_LABEL_LICENCE_KEY','License Management');

DELETE FROM sys_definition_messages_display WHERE id = 'A_TRACELOG_MODULE_LABEL_VACCUM';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_TRACELOG_MODULE_LABEL_VACCUM','Vacuum database');

DELETE FROM sys_definition_messages_display WHERE id = 'A_TRACELOG_INTERFACE_INFO_DATE';
INSERT INTO sys_definition_messages_display(id, text) VALUES ('A_TRACELOG_INTERFACE_INFO_DATE', 'Follow the exemples below to use a filter on date :<ul><li><b>2009/06*</b> : means that all messages of June will be only shown</li><li><b>2009/06* *15:*</b> : means that all messages of June at 3:00pm will be only shown</li></ul>');

DELETE FROM sys_definition_messages_display WHERE id = 'A_TRACELOG_INTERFACE_INFO_MESSAGE';
INSERT INTO sys_definition_messages_display(id, text) VALUES ('A_TRACELOG_INTERFACE_INFO_MESSAGE', 'Follow the examples below to use a filter on a message :<ul><li><b>*Word*</b> : means that all messages with <b>Word</b> wil be only shown</li><li><b>Process*</b> : means that all messages of retrieve and compute process wil be only shown</li></ul>');

-- 03/07/2009 BBX : preview report builder
DELETE FROM sys_definition_messages_display WHERE id = 'A_GDR_BUILDER_PREVIEW_NO_DATA';
INSERT INTO sys_definition_messages_display(id, text) VALUES ('A_GDR_BUILDER_PREVIEW_NO_DATA', 'No dashboard of that report contains any data');
---
DELETE FROM sys_definition_messages_display WHERE id = 'A_GDR_BUILDER_PREVIEW_EMAIL';
INSERT INTO sys_definition_messages_display(id, text) VALUES ('A_GDR_BUILDER_PREVIEW_EMAIL', 'Preview of the message that will be sent via email');

-- 12:05 07/07/2009 GHX
-- Modification du message pour qu'il soit plus explicite
-- 12:17 09/07/2009 GHX
-- Modif du message
DELETE FROM sys_definition_messages_display WHERE id='A_JS_CONTEXT_BUILD_SURE_TO_RESTORE';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_JS_CONTEXT_BUILD_SURE_TO_RESTORE', E'*** WARNING ***\\n\\nReinitialize option permits to go back to the previous context(s) in chronological order.\\n\\nBe careful : all context(s) parameters from the selected context in the list will be deleted from application.\\nYou should reinitialize only if there are no other options.\\n\\nAre you sure to reinitialize before the context file [$1] ?');
-- Correction du BZ10392
DELETE FROM sys_definition_messages_display WHERE id='A_CONTEXT_INSTALLED_INFO';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_CONTEXT_INSTALLED_INFO', 'KPI and RAW will be activated after retrieve process');

-- 07/07/2009 SPS
-- modification des messages dans dashboard/gtm builder (correction bug 10406)
DELETE FROM sys_definition_messages_display WHERE id='G_GDR_BUILDER_USING_THE_GRAPH_BUILDER';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('G_GDR_BUILDER_USING_THE_GRAPH_BUILDER', 'Show/hide builder informations');
--
DELETE FROM sys_definition_messages_display WHERE id='G_GDR_BUILDER_SHOW_NA_LEVELS';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('G_GDR_BUILDER_SHOW_NA_LEVELS', 'Show/hide network aggregation levels');

-- 08/07/2009 MPR --
-- Ajout d'un message d'erreur lors d'un upload topology lorsque l'identifiant de l'élément réseau de niveau d'agregation minimum du fichier est null - 
DELETE FROM sys_definition_messages_display  WHERE id = 'A_E_UPLOAD_TOPO_NA_MIN_IS_NULL';
INSERT INTO sys_definition_messages_display(id, text) VALUES ('A_E_UPLOAD_TOPO_NA_MIN_IS_NULL','The level $1 has got a null identifier on line $2');

-- 08/07/2009 SPS
-- ajout du titre Alarms pour la zone de filtre du report builder
DELETE FROM sys_definition_messages_display  WHERE id = 'G_GDR_BUILDER_ALARMS';
INSERT INTO sys_definition_messages_display(id, text) VALUES ('G_GDR_BUILDER_ALARMS','Alarms:');

-- 11:28 10/07/2009 GHX
DELETE FROM sys_definition_messages_display  WHERE id = 'A_CONTEXT_LAST_INSTALLATION';
INSERT INTO sys_definition_messages_display(id, text) VALUES ('A_CONTEXT_LAST_INSTALLATION','Last installation on:');

-- 11:23 15/07/2009 GHX
-- Ajout des 2 messages pour le BZ1057
DELETE FROM sys_definition_messages_display  WHERE id = 'G_GDR_BUILDER_DASHBOARD_NOT_CONFIGURED';
INSERT INTO sys_definition_messages_display(id, text) VALUES ('G_GDR_BUILDER_DASHBOARD_NOT_CONFIGURED','not configured');
DELETE FROM sys_definition_messages_display  WHERE id = 'G_GDR_BUILDER_REPORT_NOT_CONFIGURED';
INSERT INTO sys_definition_messages_display(id, text) VALUES ('G_GDR_BUILDER_REPORT_NOT_CONFIGURED','Some dashboards of the report are not configured');

-- 11:23 15/07/2009 BBX
-- Titre de l'IHM Data Export
DELETE FROM sys_definition_messages_display  WHERE id = 'A_TASK_SCHEDULER_DATA_EXPORT_TITLE_INDEX';
INSERT INTO sys_definition_messages_display(id, text) VALUES ('A_TASK_SCHEDULER_DATA_EXPORT_TITLE_INDEX','Export List');
-- Network Aggregation
DELETE FROM sys_definition_messages_display  WHERE id = 'A_TASK_SCHEDULER_DATA_EXPORT_LABEL_NA';
INSERT INTO sys_definition_messages_display(id, text) VALUES ('A_TASK_SCHEDULER_DATA_EXPORT_LABEL_NA','Network Aggregation');
-- Target File
DELETE FROM sys_definition_messages_display  WHERE id = 'A_TASK_SCHEDULER_DATA_EXPORT_TARGET_FILE';
INSERT INTO sys_definition_messages_display(id, text) VALUES ('A_TASK_SCHEDULER_DATA_EXPORT_TARGET_FILE','Target File');
-- Add Topology file
DELETE FROM sys_definition_messages_display  WHERE id = 'A_TASK_SCHEDULER_DATA_EXPORT_ADD_TOPOLOGY_FILE';
INSERT INTO sys_definition_messages_display(id, text) VALUES ('A_TASK_SCHEDULER_DATA_EXPORT_ADD_TOPOLOGY_FILE','Add Topology file');
-- Generate one file which content the complete Topology
DELETE FROM sys_definition_messages_display  WHERE id = 'A_TASK_SCHEDULER_DATA_EXPORT_TOPOLOGY_FILE_HELP';
INSERT INTO sys_definition_messages_display(id, text) VALUES ('A_TASK_SCHEDULER_DATA_EXPORT_TOPOLOGY_FILE_HELP','Generate one file that contains the complete Topology');
-- Add KPI/Counters Informations file
DELETE FROM sys_definition_messages_display  WHERE id = 'A_TASK_SCHEDULER_DATA_EXPORT_ADD_RAW_KPI_FILE';
INSERT INTO sys_definition_messages_display(id, text) VALUES ('A_TASK_SCHEDULER_DATA_EXPORT_ADD_RAW_KPI_FILE','Add KPI/Counters Information file');
-- Generate two files which content KPI and Counters Informations
DELETE FROM sys_definition_messages_display  WHERE id = 'A_TASK_SCHEDULER_DATA_EXPORT_ADD_RAW_KPI_HELP';
INSERT INTO sys_definition_messages_display(id, text) VALUES ('A_TASK_SCHEDULER_DATA_EXPORT_ADD_RAW_KPI_HELP','Generate two files that contain KPI and Counters Information');
-- Use Counters and Kpis Codes in the export
DELETE FROM sys_definition_messages_display  WHERE id = 'A_TASK_SCHEDULER_DATA_EXPORT_USE_CODE';
INSERT INTO sys_definition_messages_display(id, text) VALUES ('A_TASK_SCHEDULER_DATA_EXPORT_USE_CODE','Use Counters and Kpis Codes in the export');

-- 24/07/2009 - Maxime - Ajout du module Automatic Topology 
DELETE FROM sys_definition_messages_display WHERE id = 'A_TRACELOG_MODULE_LABEL_TOPO_AUTO';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_TRACELOG_MODULE_LABEL_TOPO_AUTO','Automatic Upload Topology');


-- 16:44 27/07/2009 GHX
DELETE FROM sys_definition_messages_display WHERE id = 'U_SELECTEUR_NUMBER_NE_SELECTED';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('U_SELECTEUR_NUMBER_NE_SELECTED', '<em>($1 elements selected)</em>');

-- 15:09 28/07/2009 GHX
DELETE FROM sys_definition_messages_display WHERE id = 'U_SELECTEUR_LABEL_LOAD_FAVORITES';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('U_SELECTEUR_LABEL_LOAD_FAVORITES', 'Load your favorites network elements');

-- 29/07/2009 BBX : modification de textes Data Export
DELETE FROM sys_definition_messages_display WHERE id = 'A_DATA_EXPORT_FILL_EXPORT_NAME_FIELD';
INSERT INTO sys_definition_messages_display (id,text) 
VALUES ('A_DATA_EXPORT_FILL_EXPORT_NAME_FIELD','Please, fill in the Export name field correctly');
---
DELETE FROM sys_definition_messages_display WHERE id = 'A_DATA_EXPORT_FILL_TARGET_FIELD';
INSERT INTO sys_definition_messages_display (id,text) 
VALUES ('A_DATA_EXPORT_FILL_TARGET_FIELD','Please, fill in the Target file field correctly');
---
DELETE FROM sys_definition_messages_display WHERE id = 'A_DATA_EXPORT_ELEMENT_INFO';
INSERT INTO sys_definition_messages_display (id,text) 
VALUES ('A_DATA_EXPORT_ELEMENT_INFO','Double-click on item to get info.');
---
DELETE FROM sys_definition_messages_display WHERE id = 'A_DATA_EXPORT_SAVE_OK';
INSERT INTO sys_definition_messages_display (id,text) 
VALUES ('A_DATA_EXPORT_SAVE_OK','The export has been successfully saved');
---
DELETE FROM sys_definition_messages_display WHERE id = 'A_DATA_EXPORT_SAVE_NOK';
INSERT INTO sys_definition_messages_display (id,text) 
VALUES ('A_DATA_EXPORT_SAVE_NOK','The export save has failed');

-- 15:04 29/07/2009 GHX
DELETE FROM sys_definition_messages_display WHERE id = 'U_QUERY_BUILDER_NO_NA_SELECTED';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('U_QUERY_BUILDER_NO_NA_SELECTED', 'At least one network information must be selected');

DELETE FROM sys_definition_messages_display WHERE id = 'U_QUERY_BUILDER_NO_TA_SELECTED';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('U_QUERY_BUILDER_NO_TA_SELECTED', 'At least one time information must be selected');

-- 10:54 31/07/2009 MPR  - Ajout d'un message d'erreur lorsque la conversion des coordonnées chargées en topo est impossible
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_UPLOAD_TOPOLOGY_COORDINATES_CONVERSION_NOK';
INSERT INTO sys_definition_messages_display VALUES ('A_E_UPLOAD_TOPOLOGY_COORDINATES_CONVERSION_NOK', 'Problem of Geographical Coordinates Conversion');


-- 08:48 04/08/2009 GHX
-- Ajout d'un message pour la confirmation de suppession d'un produit
DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PRODUCTS_CONFIRM_DELETE_SALVE';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PRODUCTS_CONFIRM_DELETE_SALVE', E'Are you sure to delete the product "$1" ?\\n\\nThis product will be not uninstall');

-- 04/08/2009 BBX : modification de textes Data Export
DELETE FROM sys_definition_messages_display WHERE id = 'A_TASK_SCHEDULER_DATA_EXPORT_USE_CODEQ';
INSERT INTO sys_definition_messages_display (id,text) 
VALUES ('A_TASK_SCHEDULER_DATA_EXPORT_USE_CODEQ','Use mapped codes for network elements');
---
DELETE FROM sys_definition_messages_display WHERE id = 'A_TASK_SCHEDULER_DATA_EXPORT_USE_CODEQ_HELP';
INSERT INTO sys_definition_messages_display (id,text) 
VALUES ('A_TASK_SCHEDULER_DATA_EXPORT_USE_CODEQ_HELP','Use Topology Master network element codes instead of product network element codes');
---
DELETE FROM sys_definition_messages_display WHERE id = 'A_TASK_SCHEDULER_DATA_EXPORT_SELECT_RAW_KPIS';
INSERT INTO sys_definition_messages_display (id,text) 
VALUES ('A_TASK_SCHEDULER_DATA_EXPORT_SELECT_RAW_KPIS','You need to choose at least one raw counter or one kpi.');
---
DELETE FROM sys_definition_messages_display WHERE id = 'A_TASK_SCHEDULER_DATA_EXPORT_DL_EXPORT';
INSERT INTO sys_definition_messages_display (id,text) 
VALUES ('A_TASK_SCHEDULER_DATA_EXPORT_DL_EXPORT','Click here to download the Data Export file');
---
DELETE FROM sys_definition_messages_display WHERE id = 'A_TASK_SCHEDULER_DATA_EXPORT_DL_COUNTERS';
INSERT INTO sys_definition_messages_display (id,text) 
VALUES ('A_TASK_SCHEDULER_DATA_EXPORT_DL_COUNTERS','Click here to download the Counters file');
---
DELETE FROM sys_definition_messages_display WHERE id = 'A_TASK_SCHEDULER_DATA_EXPORT_DL_KPIS';
INSERT INTO sys_definition_messages_display (id,text) 
VALUES ('A_TASK_SCHEDULER_DATA_EXPORT_DL_KPIS','Click here to download the KPIs file');
---
DELETE FROM sys_definition_messages_display WHERE id = 'A_TASK_SCHEDULER_DATA_EXPORT_DL_TOPO1';
INSERT INTO sys_definition_messages_display (id,text) 
VALUES ('A_TASK_SCHEDULER_DATA_EXPORT_DL_TOPO1','Click here to download the Topology First Axis file');
---
DELETE FROM sys_definition_messages_display WHERE id = 'A_TASK_SCHEDULER_DATA_EXPORT_DL_TOPO3';
INSERT INTO sys_definition_messages_display (id,text) 
VALUES ('A_TASK_SCHEDULER_DATA_EXPORT_DL_TOPO3','Click here to download the Topology Third Axis file');

-- 17:17 04/08/2009 GHX
-- Correction du BZ 6038
DELETE FROM sys_definition_messages_display WHERE id = 'G_GTM_BUILDER_INFO_CUMULATEDBAR';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('G_GTM_BUILDER_INFO_CUMULATEDBAR' , 'Some cumulated bar data are not on the same Y-Axis. You need at least two data per Y-axis in cumulated bar type for a better display.');

-- 11:21 07/08/2009 GHX
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_CONNECTION_NO_SSH_USER_DEFINED';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_CONNECTION_NO_SSH_USER_DEFINED' , 'No SSH user defined for the product $1. Unable to check FTP connection.');

-- 09:06 12/08/2009 GHX
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_SETUP_PRODUCTS_SSH_MUST_EXIST';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_SETUP_PRODUCTS_SSH_MUST_EXIST' , 'You must enter a SSH user and SSH password');

DELETE FROM sys_definition_messages_display WHERE id = 'U_INVESTIGATION_DASHBOARD_MAXIMUM_VALUE_EXCEEDED';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('U_INVESTIGATION_DASHBOARD_MAXIMUM_VALUE_EXCEEDED' , 'Maximum numbers values for display exceeded.');

-- 13/08/2009 BBX
DELETE FROM sys_definition_messages_display WHERE id = 'A_TASK_SCHEDULER_DATA_EXPORT_TITLE_FILE_CONTENT';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_TASK_SCHEDULER_DATA_EXPORT_TITLE_FILE_CONTENT' , 'File content options');
---
DELETE FROM sys_definition_messages_display WHERE id = 'A_TASK_SCHEDULER_DATA_EXPORT_TITLE_ADDITIONNAL_FILES';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_TASK_SCHEDULER_DATA_EXPORT_TITLE_ADDITIONNAL_FILES' , 'Additionnal files');
---
DELETE FROM sys_definition_messages_display WHERE id = 'A_TASK_SCHEDULER_DATA_EXPORT_LABEL_USE_CODE_NETWORKS';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_TASK_SCHEDULER_DATA_EXPORT_LABEL_USE_CODE_NETWORKS' , 'Use code network elements');
---
DELETE FROM sys_definition_messages_display WHERE id = 'A_TASK_SCHEDULER_DATA_EXPORT_LABEL_RAW_KPI_CODES';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_TASK_SCHEDULER_DATA_EXPORT_LABEL_RAW_KPI_CODES' , 'If checked, counter and KPI codes will be displayed in the export file header instead of counter and KPI labels');
---
DELETE FROM sys_definition_messages_display WHERE id = 'A_TASK_SCHEDULER_DATA_EXPORT_LABEL_ADD_PARENTS';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_TASK_SCHEDULER_DATA_EXPORT_LABEL_ADD_PARENTS' , 'If checked, all topology since the selected Network aggregation will be added to the export file');
---
DELETE FROM sys_definition_messages_display WHERE id = 'A_TASK_SCHEDULER_DATA_EXPORT_LABEL_USE_CODE_NA';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_TASK_SCHEDULER_DATA_EXPORT_LABEL_USE_CODE_NA' , 'If checked, the export file will contain network element codes instead of network element labels (header included)');


-- 13/08/2009 BBX
DELETE FROM sys_definition_messages_display WHERE id = 'A_TASK_SCHEDULER_DATA_EXPORT_CANNOT_CREATE_DIR';
INSERT INTO sys_definition_messages_display (id,text) 
VALUES ('A_TASK_SCHEDULER_DATA_EXPORT_CANNOT_CREATE_DIR','The target directory you specified cannot be created');
---
DELETE FROM sys_definition_messages_display WHERE id = 'A_MAPPING_COUNTERS_ACTIVATION_COUNTER_DEACTIVATION_DISABLED_3';
INSERT INTO sys_definition_messages_display (id,text) 
VALUES ('A_MAPPING_COUNTERS_ACTIVATION_COUNTER_DEACTIVATION_DISABLED_3','Counter "$1" is part of a Data Export. It cannot be desactivated');
---
DELETE FROM sys_definition_messages_display WHERE id = 'A_MAPPING_COUNTERS_ACTIVATION_COUNTER_DEACTIVATION_DISABLED_4';
INSERT INTO sys_definition_messages_display (id,text) 
VALUES ('A_MAPPING_COUNTERS_ACTIVATION_COUNTER_DEACTIVATION_DISABLED_4','Counter "$1" is part of an automatic Data Export (not visible). It cannot be desactivated');

-- 08:48 18/08/2009 GHX
-- Message pour Graph Builder pour quand on a plusieurs fois le meme kpi/raw (code+legende)
DELETE FROM sys_definition_messages_display  WHERE id = 'G_GTM_BUILDER_INFO_SAME_KPI_RAW';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('G_GTM_BUILDER_INFO_SAME_KPI_RAW', '<b>Several KPI or RAW have the same code, they will be considered as a single KPI or RAW in the dashboard.</b><br />- If you change the style of an element it will change the style of other elements which have the same code and same legend<br />- If you change the data legend of one of the elements, they will no longer be considered a single KPI or RAW, but as two distincts elements.');

-- 16:58 19/08/2009 GHX
DELETE FROM sys_definition_messages_display  WHERE id = 'A_SETUP_PRODUCT_DELETE_PRODUCT_OK';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_SETUP_PRODUCT_DELETE_PRODUCT_OK', 'The product $1 has been successfully deleted');

DELETE FROM sys_definition_messages_display  WHERE id = 'A_E_SETUP_PRODUCT_CANNOT_DELETE_MASTER';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_SETUP_PRODUCT_CANNOT_DELETE_MASTER', 'You cannot delete the product master');

-- 15:21 21/08/2009 GHX
-- Ajout de message pour la topo
DELETE FROM sys_definition_messages_display  WHERE id = 'A_E_UPLOAD_TOPOLOGY_COORDINATES_CONVERSION_CANNOT_FIND_SRID';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_UPLOAD_TOPOLOGY_COORDINATES_CONVERSION_CANNOT_FIND_SRID', 'The SRID is not valid');

DELETE FROM sys_definition_messages_display  WHERE id = 'A_E_UPLOAD_TOPOLOGY_COORDINATES_CONVERSION_LONGITUDE_LATITUDE_INCORRECT';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_UPLOAD_TOPOLOGY_COORDINATES_CONVERSION_LONGITUDE_LATITUDE_INCORRECT', 'The geographical coordinates are not valid');


-- 14:46 25/08/2009 GHX
-- 17:21 02/09/2009 GHX
-- Modification du message
DELETE FROM sys_definition_messages_display  WHERE id = 'A_CONTEXT_INSTALLED_DASH_NO_MENU';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_CONTEXT_INSTALLED_DASH_NO_MENU', 'The following dashboards are not associated to the existing menus : ');
-- 17:22 02/09/2009 GHX
-- Modification du message
DELETE FROM sys_definition_messages_display  WHERE id = 'A_CONTEXT_INSTALLED_INFO_DASH_NO_MENU';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_CONTEXT_INSTALLED_INFO_DASH_NO_MENU', 'You must go in menu "OBJECT BUILDER > DASHBOARD & VIEW Builder" to associate a menu to each previous dashboards to see them in user.');

-- 17:56 25/08/2009 GHX
-- BZ 11188

DELETE FROM sys_definition_messages_display  WHERE id = 'U_CART_UPDATED';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('U_CART_UPDATED', 'Cart updated');

-- 11:46 27/08/2009 GHX

DELETE FROM sys_definition_messages_display  WHERE id = 'U_E_EXTERNAL_LINK_PRODUCT_NOT_EXISTS';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('U_E_EXTERNAL_LINK_PRODUCT_NOT_EXISTS', 'This product does not exists');
-- 15:10 27/08/2009 GHX
-- BZ 10970
DELETE FROM sys_definition_messages_display  WHERE id = 'A_UPLOAD_TOPO_INFO_LAST_FILES_UPLOADED';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_UPLOAD_TOPO_INFO_LAST_FILES_UPLOADED', '(Only the 20 last files uploaded are displayed)');

-- 09:52 31/08/2009 GHX.
DELETE FROM sys_definition_messages_display  WHERE id = 'A_CONTEXT_INSTALLED_INFO_BH_DEPLOYED';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_CONTEXT_INSTALLED_INFO_BH_DEPLOYED', 'The Busy Hour has been actived for the product $1');



DELETE FROM sys_definition_messages_display  WHERE id = 'G_E_SSH2_NOT_INSTALLED';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('G_E_SSH2_NOT_INSTALLED', 'The library PHP SSH2 isn''t installed');
DELETE FROM sys_definition_messages_display  WHERE id = 'G_E_SSH2_NOT_INSTALLED_ON_REMOTE_SERVER';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('G_E_SSH2_NOT_INSTALLED_ON_REMOTE_SERVER', 'The library PHP ssh2 isn''t installed on server $1');

-- 11:30 02/09/2009 GHX
DELETE FROM sys_definition_messages_display  WHERE id = 'G_E_OPEN_OFFICE_NOT_INSTALLED';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('G_E_OPEN_OFFICE_NOT_INSTALLED', 'Open Office isn''t installed');
DELETE FROM sys_definition_messages_display  WHERE id = 'G_E_OPEN_OFFICE_NOT_INSTALLED_ON_REMOTE_SERVER';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('G_E_OPEN_OFFICE_NOT_INSTALLED_ON_REMOTE_SERVER', 'Open Office isn''t installed on server $1');
DELETE FROM sys_definition_messages_display  WHERE id = 'G_E_OPEN_OFFICE_UNABLE_CREATE_FILE_ODT';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('G_E_OPEN_OFFICE_UNABLE_CREATE_FILE_ODT', 'Unable to create a file at format odt (Open Office)');
DELETE FROM sys_definition_messages_display  WHERE id = 'G_E_OPEN_OFFICE_UNABLE_CREATE_FILE_ODT_ON_REMOTE_SERVER';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('G_E_OPEN_OFFICE_UNABLE_CREATE_FILE_ODT_ON_REMOTE_SERVER', 'Unable to create a file at format odt (Open Office) on server $1');
DELETE FROM sys_definition_messages_display  WHERE id = 'G_E_OPEN_OFFICE_UNABLE_CREATE_FILE_WORD_AND_PDF';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('G_E_OPEN_OFFICE_UNABLE_CREATE_FILE_WORD_AND_PDF', 'Open Office is probably incorrectly installed');
DELETE FROM sys_definition_messages_display  WHERE id = 'G_E_OPEN_OFFICE_UNABLE_CREATE_FILE_WORD_AND_PDF_ON_REMOTE_SERVER';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('G_E_OPEN_OFFICE_UNABLE_CREATE_FILE_WORD_AND_PDF_ON_REMOTE_SERVER', 'Open Office is probably incorrectly installed on server $1');
DELETE FROM sys_definition_messages_display  WHERE id = 'G_E_OPEN_OFFICE_ERRORS_INFO';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('G_E_OPEN_OFFICE_ERRORS_INFO', 'Be careful , if Open Office isn''t installed , Word,Excel and PDF files will not be generated by application (used in Data Export, Reports and Dashboards)');

-- 11:52 04/09/2009 GHX
DELETE FROM sys_definition_messages_display  WHERE id = 'A_CONTEXT_MOUNT_DATA_EXPORT_1';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_CONTEXT_MOUNT_DATA_EXPORT_1', 'Be careful, the directory "$1" does not exist for the following Data Exports :');
DELETE FROM sys_definition_messages_display  WHERE id = 'A_CONTEXT_MOUNT_DATA_EXPORT_2';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_CONTEXT_MOUNT_DATA_EXPORT_2', 'Would you replace it by "$1" ?');
-- 15:30 04/09/2009 GHX
DELETE FROM sys_definition_messages_display  WHERE id = 'A_CONTEXT_LIST_PRODUCT';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_CONTEXT_LIST_PRODUCT', 'Indicates on which product will be mounted context : ');

DELETE FROM sys_definition_messages_display  WHERE id = 'A_E_UPLOAD_TOPO_AUTO_CORPORATE_DIRECTORY_NO_RIGHT_TO_WRITE';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_UPLOAD_TOPO_AUTO_CORPORATE_DIRECTORY_NO_RIGHT_TO_WRITE', 'No right to write : $1');

DELETE FROM sys_definition_messages_display  WHERE id = 'A_E_UPLOAD_TOPO_AUTO_CORPORATE_DIRECTORY_NOT_EXISTS';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_UPLOAD_TOPO_AUTO_CORPORATE_DIRECTORY_NOT_EXISTS', 'The directory $1 doesn''t exists');

-- maj 09:44 22/09/2009 MPR : Correction du bug 11652 : Ajout d'un message d'information dans l'IHM du mapping de la topologie
DELETE FROM sys_definition_messages_display WHERE id = 'A_MAPPING_TOPO_INFO_DOWNLOAD';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_MAPPING_TOPO_INFO_DOWNLOAD','Click below to download the topology mapping for the selected product');

-- maj 16:15 01/03/2010 MPR : Correction du bug 14255 : Prise en compte de la limite du nombre de KPI
DELETE FROM sys_definition_messages_display WHERE id = 'A_E_KPI_BUILDER_UPLOAD_NB_ACTIVATED_KPI_EXCEEDED';
INSERT INTO sys_definition_messages_display (id, text) VALUES ('A_E_KPI_BUILDER_UPLOAD_NB_ACTIVATED_KPI_EXCEEDED','The limit of $2 activated KPIs has been exceeded for family $1. $3 KPIs are currently activated. You are trying to activate $5 new KPIs on $4 remaining for this family.');