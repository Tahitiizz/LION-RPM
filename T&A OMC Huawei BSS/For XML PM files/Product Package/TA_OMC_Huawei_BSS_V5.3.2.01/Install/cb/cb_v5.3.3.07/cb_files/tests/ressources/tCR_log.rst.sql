TRUNCATE TABLE sys_definition_topology_replacement_rules_default;
COPY sys_definition_topology_replacement_rules_default FROM '/tmp/sdtrrdBackup.sql';

TRUNCATE TABLE sys_definition_topology_replacement_rules;
COPY sys_definition_topology_replacement_rules FROM '/tmp/sdtrrBackup.sql';
