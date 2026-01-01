-- Function: erlangb_gos(traffic real, ressource real)

-- DROP FUNCTION erlangb_gos(real, real) ;

CREATE OR REPLACE FUNCTION erlangb_gos(traffic real, ressource real)
  RETURNS real AS
$BODY$
DECLARE
    gos double precision;
    inc int;  -- entier pour incrementation jusqu'àb ressource dans la boucle
    ressource_rounded integer;
    loop_step integer ;
    loop_start integer ;
BEGIN

-- Gestion des valeurs particulieres
    -- TRAFFIC
    IF     traffic IS NULL    THEN RETURN NULL;
    ELSEIF traffic = 0        THEN RETURN NULL;
    ELSEIF traffic > 10000000 THEN RETURN NULL;
    ELSEIF traffic < 0        THEN RETURN NULL;
    END IF;
    -- RESSOURCE
    IF     ressource IS NULL    THEN RETURN NULL;
    ELSEIF ressource = 0        THEN RETURN NULL;
    ELSEIF ressource > 10000000 THEN RETURN NULL; -- Max value for interger : 2147483647
    ELSEIF ressource < 0        THEN RETURN NULL;
    ELSE   ressource_rounded = round(ressource);
    END IF;

    -- Initialisation de la variable avant le debut de la boucle
    gos = 0 ;
    loop_step = 200 ; -- step = 200 car c'est le maximum moyen de ressource vu sur le BSS 2G
    loop_start = 1 ;

    -- Test de la valeur de GOS tous les steps
    -- pour eviter de boucler qu'au bout alors que le resultats est deja 0

    WHILE loop_start+loop_step < ressource_rounded
    LOOP
        FOR inc in loop_start..loop_start+loop_step
        LOOP
            gos = (1+gos)/traffic*inc;
        END LOOP ; -- FOR

        -- Test Precision 0.0001 pour numeric(4,4), 0.001 pour numeric(3,3), etc...
        IF round((1/(1+gos))::numeric,10) = 0 THEN RETURN 0 ;
        END IF ;
        
        loop_start = loop_start+loop_step+1;

    END LOOP ; -- WHILE

        FOR inc IN loop_start..ressource_rounded
        LOOP
            gos = (1+gos)/traffic*inc;
        END LOOP ; -- FOR

    -- Test Precision 0.0001 pour numeric(4,4), 0.001 pour numeric(3,3), etc...
    IF round((1/(1+gos))::numeric,10) = 0 THEN
        RETURN 0 ;
    ELSE
        RETURN round((1/(1+gos))::numeric,10)::real ;
    END IF ;

    EXCEPTION
        WHEN numeric_value_out_of_range THEN RETURN 0;
        WHEN division_by_zero           THEN RETURN NULL;

END;
$BODY$
LANGUAGE 'plpgsql' IMMUTABLE;
