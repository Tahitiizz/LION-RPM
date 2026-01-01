-- Function: erlangb_traffic(ressource real, gos real)

-- DROP FUNCTION erlangb_traffic(real, real) ;
    
-- Main function
CREATE OR REPLACE FUNCTION erlangb_traffic(ressource real, gos real)
  RETURNS real AS
$BODY$
DECLARE
    ressource_rounded integer;
BEGIN
     
-- Gestion des valeurs particulieres
    -- RESSOURCE
    IF     ressource IS NULL    THEN RETURN NULL;
    ELSEIF ressource = 0        THEN RETURN 0;
    ELSEIF ressource > 10000000 THEN RETURN NULL; -- Max value for interger : 2147483647
    ELSEIF ressource < 0        THEN RETURN NULL;
    ELSE   ressource_rounded = round(ressource);
    END IF;
    -- GOS
    IF     gos IS NULL THEN RETURN NULL;
    ELSEIF gos = 0     THEN RETURN NULL;
    ELSEIF gos > 1     THEN RETURN NULL;
    ELSEIF gos < 0.000001 THEN RETURN NULL; -- si Gos < 0.0001 %, on considère que Gos = 0
    ELSEIF gos = 1     THEN RETURN NULL;
    END IF;

-- Si ressource < 100  , on utilise la methode de diessction pour le recherche du traffic
-- Si ressource >= 100 , on utilise la methode de falsi qui est plus rapide pour des valeurs elevees de ressources
-- Arrondi de la valeur de sortie au 1/100eme.
	IF ressource_rounded < 100 THEN RETURN round(erlangb_traffic_disection(ressource_rounded,gos)::numeric,2)::real;
	ELSE RETURN round(erlangb_traffic_falsi(ressource_rounded,gos)::numeric,2)::real;
	END IF;
END;
$BODY$
LANGUAGE 'plpgsql' IMMUTABLE;

------------------------------------------------------------------------implementation de l'algo de dissection (dichotomie)

CREATE OR REPLACE FUNCTION erlangb_traffic_disection(ressource real, gos real)
  RETURNS real AS
$BODY$
DECLARE
	l real; --left end point
	r real; --right end point
	middle real;
	gos_sup real;
	gos_mid real;
	prec real; -- présion du resultat de sortie
BEGIN
-- Initialisation des variables
	prec = 0.0001 ;
--starting interval
	l=0;
	r=ressource;
	gos_sup=erlangb_gos(r,ressource);
--evaluate the right end point
	WHILE gos_sup<gos LOOP
		l=r;
		r=r*2;
		gos_sup=erlangb_gos(r,ressource);
	END LOOP;
--  find resource using bisection method	
	WHILE (r-l)>prec LOOP
		middle=(l+r)/2;
		gos_mid=erlangb_gos(middle,ressource);
		IF gos_mid < gos THEN
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

------------------------------------------------------------------------implementation algo regula falsi
CREATE OR REPLACE FUNCTION erlangb_traffic_falsi(ressource real, gos real)
  RETURNS real AS
$BODY$
DECLARE
	l real;--min
	r real;--max
	middle real;
	gos_sup real;
	gos_mid real;
	prec real; -- présion du resultat de sortie
	a real;
	b real;
	c real;
	fa real;
	fb real;
	fc real;
	delta real;
BEGIN
-- Initialisation des variables
	prec = 0.0001 ;
--starting interval
	l=1;
	r=ressource;
	gos_sup=erlangb_gos(r,ressource);
	
--evaluate the right end point
	WHILE gos_sup<gos LOOP
		l=r;
		r=r*2;
		gos_sup=erlangb_gos(r,ressource);
	END LOOP;
	--  find traffic using regula_falsi method		
	--x(n+1)=b
	--x(n)=a
	a=l;
	b=r;
	c=b;
	fb=erlangb_gos(b,ressource)-gos;
	fa=erlangb_gos(a,ressource)-gos;
	delta=b-a;
	WHILE (abs(delta)>prec) LOOP
		c=b-(fb)*(b-a)/(fb-fa);
		fc=erlangb_gos(c,ressource)-gos;
		IF(fa*fc<0) THEN
			delta=b-c;
			b=c;
			fb=fc;
			
		ELSEIF(fa*fc>0) THEN
			delta=a-c;
			a=c;fa=fc;
		ELSE
			return c;
		END IF;
	END LOOP;
	RETURN c;
    EXCEPTION
        WHEN numeric_value_out_of_range THEN RETURN NULL;
        WHEN division_by_zero           THEN RETURN NULL;

END;
$BODY$
LANGUAGE 'plpgsql' IMMUTABLE;
