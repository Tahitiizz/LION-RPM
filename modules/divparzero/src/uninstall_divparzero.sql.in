-- ancien opérateur
DROP OPERATOR IF EXISTS // (float8,float8);

UPDATE pg_operator SET oprcode=(SELECT oid FROM pg_proc WHERE proname='float8div') WHERE oprname='/' AND oprleft=(SELECT oid FROM pg_type WHERE typname='float8') AND oprright=(SELECT oid FROM pg_type WHERE typname='float8');

UPDATE pg_operator SET oprcode=(SELECT oid FROM pg_proc WHERE proname='float4div') WHERE oprname='/' AND oprleft=(SELECT oid FROM pg_type WHERE typname='float4') AND oprright=(SELECT oid FROM pg_type WHERE typname='float4');

UPDATE pg_operator SET oprcode=(SELECT oid FROM pg_proc WHERE proname='float48div') WHERE oprname='/' AND oprleft=(SELECT oid FROM pg_type WHERE typname='float4') AND oprright=(SELECT oid FROM pg_type WHERE typname='float8');

UPDATE pg_operator SET oprcode=(SELECT oid FROM pg_proc WHERE proname='float84div') WHERE oprname='/' AND oprleft=(SELECT oid FROM pg_type WHERE typname='float8') AND oprright=(SELECT oid FROM pg_type WHERE typname='float4');


DROP FUNCTION IF EXISTS float8divzero (float8, float8);
DROP FUNCTION IF EXISTS float4divzero (float4, float4);
DROP FUNCTION IF EXISTS float48divzero (float4, float8);
DROP FUNCTION IF EXISTS float84divzero (float8, float4);


VACUUM FULL pg_operator;
REINDEX TABLE pg_operator;
