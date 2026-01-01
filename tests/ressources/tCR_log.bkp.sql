COPY sys_definition_topology_replacement_rules_default to '/tmp/sdtrrdBackup.sql';
COPY sys_definition_topology_replacement_rules to '/tmp/sdtrrBackup.sql';

SET statement_timeout = 0;
SET client_encoding = 'SQL_ASCII';
SET standard_conforming_strings = off;
SET check_function_bodies = false;
SET client_min_messages = warning;
SET escape_string_warning = off;
SET search_path = public, pg_catalog;

TRUNCATE TABLE sys_definition_topology_replacement_rules_default;
INSERT INTO sys_definition_topology_replacement_rules_default VALUES ('sdtrr.1', E'\'', '_', '_');
INSERT INTO sys_definition_topology_replacement_rules_default VALUES ('sdtrr.2', '"', '_', '_');
INSERT INTO sys_definition_topology_replacement_rules_default VALUES ('sdtrr.3', '#', '_', '_');
INSERT INTO sys_definition_topology_replacement_rules_default VALUES ('sdtrr.4', E'\\', '_', '_');

TRUNCATE TABLE sys_definition_topology_replacement_rules;
