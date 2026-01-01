
-- Exemple d'utilisation pour l'ajout/la modification d'un message display
-- DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_KPI_BUILDER_ERROR_BH_KPI_DEL';
-- INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_KPI_BUILDER_ERROR_BH_KPI_DEL', 'Kpi "$1" is set as Busy Hour. It cannot be deleted.');

-- 23/02/2012 NSE DE Astellia Portal Lot2

DELETE FROM sys_definition_messages_display WHERE id = 'A_USER_USER_MANAGEMENT_LIST_CAS_WARNING';
INSERT INTO sys_definition_messages_display VALUES ('A_USER_USER_MANAGEMENT_LIST_CAS_WARNING', '"New" and "Delete" actions are not available in this GUI.<br>Users are managed by Astellia Portal.');

DELETE FROM sys_definition_messages_display WHERE id = 'A_USER_USER_MANAGEMENT_LABEL_USER_NAME';
INSERT INTO sys_definition_messages_display VALUES ('A_USER_USER_MANAGEMENT_LABEL_USER_NAME', 'Name');

DELETE FROM sys_definition_messages_display WHERE id = 'A_USER_USER_MANAGEMENT_LABEL_USER_PHONE_NUMBER';
INSERT INTO sys_definition_messages_display VALUES ('A_USER_USER_MANAGEMENT_LABEL_USER_PHONE_NUMBER', 'Phone number');

DELETE FROM sys_definition_messages_display WHERE id = 'A_USER_USER_EDIT_CAS_WARNING';
INSERT INTO sys_definition_messages_display VALUES ('A_USER_USER_EDIT_CAS_WARNING', 'Update of "User Login", "Name" and "Email" are managed on PAA.');

DELETE FROM sys_definition_messages_display WHERE id = 'A_USER_USER_MANAGEMENT_BTN_SYNCHRO_USER';
INSERT INTO sys_definition_messages_display VALUES ('A_USER_USER_MANAGEMENT_BTN_SYNCHRO_USER', 'Synchronize from PAA');

DELETE FROM sys_definition_messages_display WHERE id = 'A_USER_USER_MANAGEMENT_NO_USER_ON_PAA';
INSERT INTO sys_definition_messages_display VALUES ('A_USER_USER_MANAGEMENT_NO_USER_ON_PAA', 'Users list has not been updated from PAA.');


-- !!!! LAISSER A LA FIN DU FICHIER !!!!
VACUUM ANALYSE sys_definition_messages_display;
