#: /bin/bash
PG_USER=postgres
PG_DBNAME=$1
SQL="$psql_bin -U $PG_USER -d $PG_DBNAME -c"
BACKUP_FILE="backup_sys_definition_time_bh_formula.txt"

echo "***************Backing up sys_definition_time_bh_formula table*******************" >> $LOG
NB_LINE=`$SQL "select count(*) from sys_definition_time_bh_formula" | sed '3!d'`
echo "There are $NB_LINE lines to backup." >> $LOG
$SQL "COPY sys_definition_time_bh_formula (family, bh_indicator_name,bh_indicator_type,bh_parameter,bh_network_aggregation,comment) TO stdout WITH NULL AS ''"  > $BACKUP_FILE
echo "***************Backup sys_definition_time_bh_formula done*******************" >> $LOG
