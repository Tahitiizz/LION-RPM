
-- Exemple d'utilisation pour l'ajout/la modification d'un message display
-- DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_KPI_BUILDER_ERROR_BH_KPI_DEL';
-- INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_KPI_BUILDER_ERROR_BH_KPI_DEL', 'Kpi "$1" is set as Busy Hour. It cannot be deleted.');

-- 18/01/2013 GFS - BZ#31387 : [QAL][T&A Gateway][Remove Product] : Product not accesible after being removed from gateway
DELETE FROM sys_definition_messages_display WHERE id = 'A_UPLOAD_CONTEXT_DELETE_PRODUCT';
INSERT INTO sys_definition_messages_display VALUES ('A_UPLOAD_CONTEXT_DELETE_PRODUCT', 'The slave product is well removed but the initial context upload failed.');

-- !!!! LAISSER A LA FIN DU FICHIER !!!!
VACUUM ANALYSE sys_definition_messages_display;
