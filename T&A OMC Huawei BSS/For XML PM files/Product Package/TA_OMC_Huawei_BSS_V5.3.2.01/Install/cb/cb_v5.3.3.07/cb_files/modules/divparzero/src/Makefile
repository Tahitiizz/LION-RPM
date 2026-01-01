MODULES = divparzero
DATA_built = divparzero.sql uninstall_divparzero.sql
ifdef USE_PGXS
PG_CONFIG = pg_config
PGXS := $(shell $(PG_CONFIG) --pgxs)
include $(PGXS)
endif

