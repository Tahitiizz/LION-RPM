
-- Exemple d'utilisation pour l'ajout/la modification d'un message display
-- DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_KPI_BUILDER_ERROR_BH_KPI_DEL';
-- INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_KPI_BUILDER_ERROR_BH_KPI_DEL', 'Kpi "$1" is set as Busy Hour. It cannot be deleted.');

-- 13/09/2012 ACS DE Improve configuration of Task Scheduler

DELETE FROM sys_definition_messages_display WHERE id = 'A_TASK_SCHEDULER_OFFSET_CHECK';
INSERT INTO sys_definition_messages_display VALUES ('A_TASK_SCHEDULER_OFFSET_CHECK', 'Offset value must be lower than Time period');

DELETE FROM sys_definition_messages_display WHERE id = 'A_TASK_SCHEDULER_EXECUTION_TIMES';
INSERT INTO sys_definition_messages_display VALUES ('A_TASK_SCHEDULER_EXECUTION_TIMES', 'Five first execution times:');

DELETE FROM sys_definition_messages_display WHERE id = 'A_SETUP_PROCESS_HELP';
INSERT INTO sys_definition_messages_display VALUES ('A_SETUP_PROCESS_HELP', '<li>Each box enables you to manage a product''s processes</li><li>The master product is displayed with "[master]"</li><li>The topology master product is displayed with "[topology master]"</li><li>The time period determines the frequency the processes will be launched (if on).<br />For example, 0h 25mn means the process will start every 25 minutes.</li><li>The offset indicates from what hour / minute a process should start.<br />For example, time period : 0h 25mn / offset 0h 12mn means the process will start every 25 minutes after the next 12th minute (08h12 - 08h37 - 09h12 - 09h37 - etc...).</li>');

-- 14/09/2012 ACS DE Make it possible to configure the maximum number of displayed elements

DELETE FROM sys_definition_messages_display WHERE id = 'A_GLOBAL_PARAMS_MAX_NUMBER_DISPLAYED_ELEMENT';
INSERT INTO sys_definition_messages_display VALUES ('A_GLOBAL_PARAMS_MAX_NUMBER_DISPLAYED_ELEMENT', 'Increasing the "Maximum number of displayed elements" value might severly damage the performances of all graph displays as well as report generations.');


-- 05/10/2012 MMT Merge DE 5.3 Delete Topology
-- 20/09/2012 MMT DE 5.3 Delete Topology
DELETE FROM sys_definition_messages_display WHERE id = 'A_UPLOAD_TOPO_UPLOAD_FRAME_TITLE';
INSERT INTO sys_definition_messages_display VALUES ('A_UPLOAD_TOPO_UPLOAD_FRAME_TITLE', 'Upload');

DELETE FROM sys_definition_messages_display WHERE id = 'A_UPLOAD_TOPO_DELETE_FRAME_TITLE';
INSERT INTO sys_definition_messages_display VALUES ('A_UPLOAD_TOPO_DELETE_FRAME_TITLE', 'Delete');

DELETE FROM sys_definition_messages_display WHERE id = 'A_UPLOAD_TOPO_DELETE_INFO_MESSAGE';
INSERT INTO sys_definition_messages_display VALUES ('A_UPLOAD_TOPO_DELETE_INFO_MESSAGE', 'Delete the topology for the selected product. All topology mapping will be deleted. You must upload new topology to access data and alarm history on all axes. Unreferenced data in the topology file for these axes is no longer available. Data for elements not referenced in the topology will be lost.');

DELETE FROM sys_definition_messages_display WHERE id = 'A_UPLOAD_TOPO_DELETE_SUBMIT';
INSERT INTO sys_definition_messages_display VALUES ('A_UPLOAD_TOPO_DELETE_SUBMIT', 'Delete topology');

DELETE FROM sys_definition_messages_display WHERE id = 'A_UPLOAD_TOPO_SUBMIT';
INSERT INTO sys_definition_messages_display VALUES ('A_UPLOAD_TOPO_SUBMIT', 'Upload topology file');

DELETE FROM sys_definition_messages_display WHERE id = 'A_UPLOAD_TOPO_DELETE_CONFIRM';
INSERT INTO sys_definition_messages_display VALUES ('A_UPLOAD_TOPO_DELETE_CONFIRM', 'Are you sure you want to delete all existing topology information for the selected product?');

DELETE FROM sys_definition_messages_display WHERE id = 'E_UPLOAD_TOPOLOGY_DELETE_PROCESS_RUNNING';
INSERT INTO sys_definition_messages_display VALUES ('E_UPLOAD_TOPOLOGY_DELETE_PROCESS_RUNNING', 'Processes are currently running. You may need to stop processing in the Process Setup interface (Task Scheduler Menu > Process) before deleting topology.');

DELETE FROM sys_definition_messages_display WHERE id = 'E_UPLOAD_TOPOLOGY_DELETE_FORBIDDEN_RETRIEVE_COMPUTE';
INSERT INTO sys_definition_messages_display VALUES ('E_UPLOAD_TOPOLOGY_DELETE_FORBIDDEN_RETRIEVE_COMPUTE', 'Cannot delete topology between "Retrieve" and "Compute" process. Please wait for the next upcoming "Compute". You can also prevent the next "Retrieve" from starting in the Process Setup interface (Task Scheduler Menu > Process)');

DELETE FROM sys_definition_messages_display WHERE id = 'A_UPLOAD_TOPOLOGY_DELETE_SUCCES';
INSERT INTO sys_definition_messages_display VALUES ('A_UPLOAD_TOPOLOGY_DELETE_SUCCES', 'Topology has been successfully deleted');

DELETE FROM sys_definition_messages_display WHERE id = 'A_UPLOAD_TOPOLOGY_DELETE_ERROR';
INSERT INTO sys_definition_messages_display VALUES ('A_UPLOAD_TOPOLOGY_DELETE_ERROR', 'Delete Topology Error');

DELETE FROM sys_definition_messages_display WHERE id = 'A_TRACELOG_TOPOLOGY_DELETED';
INSERT INTO sys_definition_messages_display VALUES ('A_TRACELOG_TOPOLOGY_DELETED', 'Topology deleted by user $1');

DELETE FROM sys_definition_messages_display WHERE id = 'A_TRACELOG_MODULE_LABEL_TOPOLOGY';
INSERT INTO sys_definition_messages_display VALUES ('A_TRACELOG_MODULE_LABEL_TOPOLOGY', 'Topology');

DELETE FROM sys_definition_messages_display WHERE id = 'A_UPLOAD_TOPOLOGY_DELETE_SLAVE_VERSION';
INSERT INTO sys_definition_messages_display VALUES ('A_UPLOAD_TOPOLOGY_DELETE_SLAVE_VERSION', 'This action is not available on the version of this product');

-- 09/10/2012 ACS DE GIS 3D ONLY
DELETE FROM sys_definition_messages_display WHERE id = 'A_GIS_NOT_NA_MIN';
INSERT INTO sys_definition_messages_display VALUES ('A_GIS_NOT_NA_MIN', 'In GIS 3D only Mode, Gis 3D is only available on the minimum network aggregation level.');

-- 18/01/2013 GFS - BZ#31387 : [QAL][T&A Gateway][Remove Product] : Product not accesible after being removed from gateway
DELETE FROM sys_definition_messages_display WHERE id = 'A_UPLOAD_CONTEXT_DELETE_PRODUCT';
INSERT INTO sys_definition_messages_display VALUES ('A_UPLOAD_CONTEXT_DELETE_PRODUCT', 'The slave product is well removed but the initial context upload failed.');

-- !!!! LAISSER A LA FIN DU FICHIER !!!!
VACUUM ANALYSE sys_definition_messages_display;
