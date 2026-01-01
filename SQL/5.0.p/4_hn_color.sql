---
-- Mise à jour des messages spécifiques à la version 5.0.1 => ce script permet la migration des hn_color depuis la table edw_hn_color (CB 3.0) vers la table edw_object_ref (CB 5.0) [SCT]
-- 11:51 26/10/2009 SCT : BZ 12154 => Pas de gestion des HN color => pas de reprise des couleurs lors de la migration
-- 11:52 26/10/2009 SCT : BZ 12274 => suppression des tables edw_object_1_ref_axe1 et edw_object_1_ref_axe3 si elles existent
-- 11:56 26/10/2009 SCT : BZ 12275 => suppression de la table edw_hn_color
---


-- Transfert des couleurs depuis edw_hn_color vers edw_object_ref puis suppression de la table edw_hn_color
CREATE OR REPLACE FUNCTION checkTableExists() RETURNS VOID AS $$
DECLARE tableExists int;
BEGIN
	SELECT 
		COUNT(*) INTO tableExists
	FROM 
		pg_class
	WHERE 
		relname = 'edw_hn_color';
	IF tableExists = 1 THEN
		UPDATE 
			edw_object_ref 
		SET
			eor_color = (
				SELECT 
					color
				FROM 
					edw_hn_color
				WHERE
					edw_hn_color.hn = edw_object_ref.eor_id
			)
		WHERE 
			eor_obj_type = 'hn';
		DROP TABLE edw_hn_color;
	END IF;
END;
$$ LANGUAGE PLPGSQL;
SELECT checkTableExists();
DROP FUNCTION checkTableExists();

-- Suppression des tables edw_object_1_ref_axe1 et edw_object_1_ref_axe3
CREATE OR REPLACE FUNCTION DropTableObjectRefAxe(TEXT) RETURNS VOID AS $$
DECLARE
	laTableASupprimer TEXT;
	tableExists BOOLEAN;
BEGIN
	laTableASupprimer := $1;
	SELECT 
		COUNT(*) = 1 INTO tableExists
	FROM 
		pg_class
	WHERE 
		relname = laTableASupprimer;
	IF tableExists = TRUE THEN
		EXECUTE 'DROP TABLE "'|| laTableASupprimer ||'"';
	END IF;
END;
$$ LANGUAGE plpgsql;

SELECT DropTableObjectRefAxe('edw_object_1_ref_axe1');
SELECT DropTableObjectRefAxe('edw_object_1_ref_axe3');
DROP FUNCTION DropTableObjectRefAxe(TEXT);


