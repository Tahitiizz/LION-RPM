-- Function: erlangb_ressource(traffic real, gos real)

-- DROP FUNCTION erlangb_ressource(real, real);

CREATE OR REPLACE FUNCTION erlangb_ressource(traffic real, gos real)
 RETURNS real AS
$BODY$
DECLARE
    --ressource int;
	l int;--min
	r int;--max
	middle int;
	gos_inf real;
	gos_mid real;
BEGIN
-- Gestion des valeurs particulieres
    -- TRAFFIC
    IF     traffic IS NULL    THEN RETURN NULL;
    ELSEIF traffic = 0        THEN RETURN NULL;
    ELSEIF traffic > 10000000 THEN RETURN NULL;
    ELSEIF traffic < 0        THEN RETURN NULL;
    END IF;
    -- GOS
    IF     gos IS NULL THEN RETURN NULL;
    ELSEIF gos = 0     THEN RETURN NULL;
    ELSEIF gos > 1     THEN RETURN NULL;
    ELSEIF gos < 0.000001 THEN RETURN NULL; -- si Gos < 0.0001 %, on considere que Gos = 0
    ELSEIF gos = 1     THEN RETURN NULL;
    END IF;

-- Initialisation des variables

--starting interval
	l=0;
	r=ceil(traffic);
	
	gos_inf=erlangb_gos(traffic,r);
	
--evaluate the right end point
	WHILE gos_inf>gos LOOP
		l=r;
		r=r*2;
		gos_inf=erlangb_gos(traffic,r);
	END LOOP;
	
--  find resource using bisection method	
	WHILE (r-l)>1 LOOP
		middle=ceil((l+r)/2);
		gos_mid=erlangb_gos(traffic,middle);
		IF gos_mid > gos THEN
			l=middle;
		ELSE
			r=middle;
		END IF;
	END LOOP;
	
	RETURN r;

    EXCEPTION
        WHEN numeric_value_out_of_range THEN RETURN NULL;
        WHEN division_by_zero           THEN RETURN NULL;

END;
$BODY$
LANGUAGE 'plpgsql' IMMUTABLE;


