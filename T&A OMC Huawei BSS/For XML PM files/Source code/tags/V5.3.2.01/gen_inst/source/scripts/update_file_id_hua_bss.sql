-- Bug 32071 - [REC][T&A OMC Huawei BSS v5.2] [Context] The flat file IDs have been modified
--suite au passage à huawei bss 5.3, 2 file_id ont changé entre le contexte 5.1.0.06 et 5.3.0.00, on met donc à jour les tables du SA et d'alerte système


CREATE OR REPLACE FUNCTION updatefileid() RETURNS text AS
$BODY$
DECLARE
    mviews RECORD;
BEGIN
    FOR mviews IN SELECT * FROM sys_definition_flat_file_per_connection ORDER BY sdffpc_id_connection LOOP
	--l'id de falt_file 169 devient 168
        EXECUTE 'UPDATE sys_definition_flat_file_per_connection SET sdffpc_id_flat_file=168 WHERE sdffpc_id_connection='||mviews.sdffpc_id_connection||' AND sdffpc_id_flat_file=169 AND NOT EXISTS(SELECT * FROM sys_definition_flat_file_per_connection WHERE sdffpc_id_connection='||mviews.sdffpc_id_connection||' AND sdffpc_id_flat_file=168)';
	EXECUTE 'DELETE FROM sys_definition_flat_file_per_connection WHERE sdffpc_id_connection='||mviews.sdffpc_id_connection||' AND sdffpc_id_flat_file=169';
	--l'id de falt_file 176 devient 175
	EXECUTE 'UPDATE sys_definition_flat_file_per_connection SET sdffpc_id_flat_file=175 WHERE sdffpc_id_connection='||mviews.sdffpc_id_connection||' AND sdffpc_id_flat_file=176 AND NOT EXISTS(SELECT * FROM sys_definition_flat_file_per_connection WHERE sdffpc_id_connection='||mviews.sdffpc_id_connection||' AND sdffpc_id_flat_file=175)';
	EXECUTE 'DELETE FROM sys_definition_flat_file_per_connection WHERE sdffpc_id_connection='||mviews.sdffpc_id_connection||' AND sdffpc_id_flat_file=176';

    END LOOP;
    FOR mviews IN SELECT * FROM sys_definition_sa_file_type_per_connection ORDER BY sdsftpc_id_connection LOOP
	--l'id de falt_file 169 devient 168
	EXECUTE 'UPDATE sys_definition_sa_file_type_per_connection SET sdsftpc_id_flat_file=168 WHERE sdsftpc_id_connection='||mviews.sdsftpc_id_connection||' AND sdsftpc_id_flat_file=169 AND NOT EXISTS(SELECT * FROM sys_definition_sa_file_type_per_connection WHERE sdsftpc_id_connection='||mviews.sdsftpc_id_connection||' AND sdsftpc_id_flat_file=168)';
	EXECUTE 'DELETE FROM sys_definition_sa_file_type_per_connection WHERE sdsftpc_id_connection='||mviews.sdsftpc_id_connection||' AND sdsftpc_id_flat_file=169';
	--l'id de falt_file 176 devient 175
	EXECUTE 'UPDATE sys_definition_sa_file_type_per_connection SET sdsftpc_id_flat_file=175 WHERE sdsftpc_id_connection='||mviews.sdsftpc_id_connection||' AND sdsftpc_id_flat_file=176 AND NOT EXISTS(SELECT * FROM sys_definition_sa_file_type_per_connection WHERE sdsftpc_id_connection='||mviews.sdsftpc_id_connection||' AND sdsftpc_id_flat_file=175)';
	EXECUTE 'DELETE FROM sys_definition_sa_file_type_per_connection WHERE sdsftpc_id_connection='||mviews.sdsftpc_id_connection||' AND sdsftpc_id_flat_file=176';
    END LOOP;
    return 'DONE : sys_definition_flat_file_per_connection et sys_definition_sa_file_type_per_connection updated';
END;
$BODY$ LANGUAGE plpgsql VOLATILE;

ALTER FUNCTION updatefileid() OWNER TO postgres;

select updatefileid();
DROP FUNCTION updatefileid();
