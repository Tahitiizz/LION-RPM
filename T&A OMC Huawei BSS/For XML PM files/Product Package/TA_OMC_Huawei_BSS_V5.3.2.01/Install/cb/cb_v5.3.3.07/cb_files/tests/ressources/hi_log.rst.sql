TRUNCATE TABLE sys_log_ast;
COPY sys_log_ast FROM '/tmp/slaBackup.sql';

TRUNCATE TABLE sys_definition_alarm_static;
COPY sys_definition_alarm_static FROM '/tmp/sdasBackup.sql';

TRUNCATE TABLE sys_definition_alarm_dynamic;
COPY sys_definition_alarm_dynamic FROM '/tmp/sdadBackup.sql';

TRUNCATE TABLE sys_definition_alarm_top_worst;
COPY sys_definition_alarm_top_worst FROM '/tmp/sdatwBackup.sql';
