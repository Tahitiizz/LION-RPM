-- SUPPRESSION DES FONCTIONS PRECEDENTES
DROP FUNCTION IF EXISTS erlangb(text, text, text, real, real, real, text, text, text);
DROP FUNCTION IF EXISTS erlangb(text, text, text, text, numeric, numeric, numeric, text, text, text);
DROP FUNCTION IF EXISTS erlangb(text, text, numeric, numeric, numeric, numeric, numeric, text, text, text);
DROP FUNCTION IF EXISTS erlangb(text, text, numeric, numeric, numeric, numeric, numeric, text);
DROP FUNCTION IF EXISTS erlangb(text, text, real, real, numeric, numeric, numeric, text);
-- SCT 16:39 15/01/2010 : modification de la fonction Erlangb
DROP FUNCTION IF EXISTS erlangb(text, text, text, real, real, numeric, numeric, numeric, text);
-- 11/03/2010 NSE bz 14713 
DROP FUNCTION IF EXISTS erlangb(text, text, text, real, anyelement, numeric, numeric, numeric, text);
DROP FUNCTION IF EXISTS erlangb(text, text, text, anyelement, anyelement, numeric, numeric, numeric, text);
-- 15/03/2010 NSE bz 14256 
DROP FUNCTION IF EXISTS erlangb(text, text, text, double precision, anyelement, numeric, numeric, numeric, text);
--
DROP FUNCTION IF EXISTS gos(real, integer);
DROP FUNCTION IF EXISTS gos(numeric, integer);
DROP FUNCTION IF EXISTS gos(numeric, integer, integer);
--
DROP FUNCTION IF EXISTS traffic(real, integer);
DROP FUNCTION IF EXISTS traffic(numeric, integer);
--
DROP FUNCTION IF EXISTS channels(real, real);
DROP FUNCTION IF EXISTS channels(numeric, numeric);

-- 11/03/2010 NSE bz 14713
-- le type de counter_tch est maintenant anyelement. Ce paramètre pourra recevoir :
--	* false : cas où TCH unknown ou TCH récupéré dans la topo
--  * une valeur de compteur null ou non null
-- si TCH=false, on sait grâce au mode si on doit le récupérer en topo ou si c'est l'inconnue
-- la fonction retourne null si le compteur vaut null (et non 0)
-- 15/03/2010 NSE bz 14256
-- modification du type pour counter_traffic : double precision au lieu de real pour recevoir les résultats de calculs
CREATE OR REPLACE FUNCTION erlangb(netval text, network text, na_encours_network text, counter_traffic double precision, counter_tch anyelement, p numeric, n numeric, a numeric, "mode" text)
  RETURNS numeric AS
$BODY$
DECLARE
	cel RECORD;
	cmpt numeric;
	cmpt_tch numeric;
	result numeric;
	tch numeric;
	hrp numeric;
	tchinter INT;
	myrec RECORD;
BEGIN

	-- 16:35 15/01/2010 SCT : on intègre le test du NA à l'intérieur de la fonction
	IF network = na_encours_network THEN

		result:=0;
		hrp:=0.65;
		cmpt:=0;
		cmpt_tch:=0;
		tch:=0;

		-- 11/03/2010 NSE bz 14713 si on recherche le Gos ou le Traffic, on récupère le TCH passé en paramètre ou on le retrouve en la topo
		IF mode <> 'CHANNELS' THEN
		
			-- si on a passé une valeur numérique pour TCH, on l'utilise
			IF n > 0 THEN
				tch := n;
			ELSE
				-- 11/03/2010 NSE bz 14713 sinon, soit on doit retrouver le TCH à partir de la topo, soit à partir du compteur
				-- si on a false dans le compteur, c'est qu'il faut regarder en topo
				IF counter_tch = false
				THEN 
					-- Memorise les cellules
					SELECT * INTO cel
					FROM edw_object_ref_parameters, edw_object_ref
					WHERE eorp_id = eor_id
					AND eor_on_off = 1
					AND eor_obj_type = ''||network||''
					AND eor_id = ''||netval||'';
					
					-- Si pas de resultat, on sort 
					IF NOT FOUND THEN
						-- 11/03/2010 NSE on retroune null et non 0 si on n'a pas de résultat
						return null;
					END IF;
			
					SELECT CASE cel.eorp_trx INTO tch
						WHEN 1 THEN 6
						WHEN 2 THEN 12
						WHEN 3 THEN 19
						WHEN 4 THEN 26
						WHEN 5 THEN 32
						WHEN 6 THEN 39
						WHEN 7 THEN 46
						WHEN 8 THEN 53
						WHEN 9 THEN 60
						WHEN 10 THEN 67
						WHEN 11 THEN 74
						WHEN 12 THEN 81
						WHEN 13 THEN 88
						WHEN 14 THEN 95
						WHEN 15 THEN 102
						WHEN 16 THEN 109
						WHEN 17 THEN 116
						WHEN 18 THEN 123
						WHEN 19 THEN 130
						WHEN 20 THEN 137
						WHEN 21 THEN 144
						WHEN 22 THEN 151
						WHEN 23 THEN 158
						WHEN 24 THEN 165
						ELSE 0
					END;
					
					-- 11:30 22/12/2009 SCT : on spécifie la variable tch en integer
					IF cel.eorp_charge = 1 AND tch > 0 THEN
						tch := (tch*2/(2-hrp))::integer;
					END IF;
				
				ELSE
					-- 11/03/2010 NSE bz 14713 soit on a passé un compteur
					-- si le compteur n'est pas null, on retourne sa valeur
					IF counter_tch IS NOT NULL 
					THEN
						-- 16:26 15/01/2010 SCT : on spécifie la variable tch en integer => les fonctions GOS et TRAFFIC attendent ce paramètre en integer
						tch := counter_tch::integer;
					ELSE
						-- 11/03/2010 NSE bz 14713 sinon, on retour null
						return null;					
					END IF;
				END IF;
			END IF;
		END IF;
		
		-- 04/01/2010 BBX : la condition suivante ne doit etre appelée que si mode != traffic
		IF mode <> 'TRAFFIC' THEN
			IF a > 0 THEN
				cmpt := a;
			ELSE
				IF counter_traffic IS NOT NULL THEN		
					cmpt := counter_traffic;
				ELSE
					-- NSE bz 14713 retourne null et non 0 si le compteur vaut null
					RETURN null;
				END IF;
			END IF;
		END IF;

		IF mode = 'GOS' THEN
			tchinter := tch;
			result := GOS(cmpt::numeric,tchinter);
			-- 18/01/2010 MPR : On retourne 2 pour 2% et non 0.02
			result := result*100;
		END IF;
		
		IF mode = 'CHANNELS' THEN
			-- 06/01/2010 SCT : Limitation de la valeur de cmpt à 300 => au dessus de cette valeur (ou pour de grande valeur), le calcul de la fonction CHANNELS est trop long (vu avec PGR)
			IF cmpt >= 300 THEN
				result := 0;
			ELSE
				result := CHANNELS(cmpt::numeric,(p/100)::numeric);
			END IF;
		END IF;
		
		IF mode = 'TRAFFIC' THEN
			tchinter := tch;
			-- 06/01/2010 SCT : Limitation de la valeur de tchinter à 300 => au dessus de cette valeur (ou pour de grande valeur), le calcul de la fonction TRAFFIC est trop long (vu avec PGR)
			IF tchinter >= 300 THEN
				result := 0;
			ELSE
				result := TRAFFIC((p/100)::numeric,tchinter);
			END IF;
		END IF;
	ELSE
		result := null;
	END IF;
	RETURN result;
END;
$BODY$
  LANGUAGE 'plpgsql' VOLATILE;
ALTER FUNCTION erlangb(text, text, text, double precision, anyelement, numeric, numeric, numeric, text) OWNER TO postgres;

-- Function: gos(numeric, integer)
-- 03/03/2010 BBX : on passe la fonction en IMMUTABLE. BZ 14593
CREATE OR REPLACE FUNCTION gos(a numeric, n integer)
  RETURNS numeric AS
$BODY$
DECLARE
	s numeric(12,6);
	v numeric;
	i int;
BEGIN
	IF a = 0 THEN RETURN 0.0000;
	END IF;

	s = 0.0000;
	
	FOR i IN 1..n
	LOOP
		s = (1.0000 + s)*(i/a);
	END LOOP;

	RETURN round ((1.0000/(1.0000 + s)), 4);

	EXCEPTION
		WHEN numeric_value_out_of_range
	THEN RETURN 0.0000;
		WHEN division_by_zero
	THEN RETURN 0.0000;

END;
$BODY$
  LANGUAGE 'plpgsql' IMMUTABLE;
ALTER FUNCTION gos(numeric, integer) OWNER TO postgres;

-- FONCTION GOS AVEC PRECISION PARAMETREABLE
-- 03/03/2010 BBX : on passe la fonction en IMMUTABLE. BZ 14593
CREATE OR REPLACE FUNCTION gos(a numeric, n integer, prec integer) 
  RETURNS numeric AS
$BODY$
DECLARE
	s numeric(12,6);
	i int;
BEGIN
	IF a = 0 THEN RETURN 0.0000;
	END IF;

	s = 0.0000;
	FOR i IN 1..n
	LOOP
		s = (1.0000 + s)*(i/a);
	END LOOP;

	RETURN round ((1.0000/(1.0000 + s)), prec);

	EXCEPTION
		WHEN numeric_value_out_of_range
	THEN RETURN 0.0000;
		WHEN division_by_zero
	THEN RETURN 0.0000;
END;
$BODY$
  LANGUAGE 'plpgsql' IMMUTABLE;
ALTER FUNCTION gos(numeric, integer, integer) OWNER TO postgres;

-- Function: traffic(numeric, integer)
-- 03/03/2010 BBX : on passe la fonction en IMMUTABLE. BZ 14593
CREATE OR REPLACE FUNCTION traffic(p numeric, n integer)
  RETURNS numeric AS
$BODY$
DECLARE
	a numeric;
	val numeric;
	prec smallint;
	startLoop smallint;
	NBZ smallint;
	inc numeric;
	cp numeric;
BEGIN
	a := 0;
	val := 0;
	prec := 4;
	
	IF p >= 1 THEN
		RETURN round(n * p * 100,prec);
	END IF;
	
	IF n <= 0 THEN
		startLoop = 0;
	ELSE
		startLoop = floor(log(n)) * (-1);
	END IF;
	
	FOR NBZ IN startLoop..(prec+1) BY 1 
	LOOP
		inc := 1/power(10,(NBZ));
		a := val;
		cp := GOS(a,n,prec+3);
		
		WHILE cp <= p LOOP
			val := a;			
			a := a + inc;			
			cp := GOS(a,n,prec+3);
		END LOOP;

	END LOOP;

	RETURN round(a,prec);
END;
$BODY$
  LANGUAGE 'plpgsql' IMMUTABLE;
ALTER FUNCTION traffic(numeric, integer) OWNER TO postgres;

-- Function: channels(numeric, numeric)
-- 03/03/2010 BBX : on passe la fonction en IMMUTABLE. BZ 14593
CREATE OR REPLACE FUNCTION channels(a numeric, p numeric)
  RETURNS integer AS
$BODY$
DECLARE
	n int;
	cp numeric;
	startLoop smallint;
	inc numeric;
BEGIN
	n = ceil(a/2);	
	startLoop = 0;
	IF a > 10 THEN
		startLoop = floor(log(a)) * (-1);
	END IF;
	
	FOR nbz IN startLoop..0 BY 1
	LOOP
		inc = 1/power(10,(nbz));
		cp = GOS(a,n);	
		WHILE cp > p
		LOOP					
			n = n + inc;
			cp = GOS(a,n);
		END LOOP;
		n = n - inc;
	END LOOP;

	RETURN round(n+inc);
END;
$BODY$
  LANGUAGE 'plpgsql' IMMUTABLE;
ALTER FUNCTION channels(numeric, numeric) OWNER TO postgres;
