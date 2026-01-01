#! /bin/bash

PG_USER=postgres
PG_DBNAME=$1
SQL="$psql_bin -U $PG_USER -d $PG_DBNAME -L $LOG"

BACKUP_FILE="backup_sys_definition_time_bh_formula.txt"

echo "***************Restoring sys_definition_time_bh_formula table*******************" >> $LOG
cat $BACKUP_FILE | awk -F '\t' '{printf("UPDATE sys_definition_time_bh_formula SET bh_indicator_name = \047%s\047 , bh_indicator_type = \047%s\047 , bh_parameter = \047%s\047 , bh_network_aggregation = \047%s\047 , comment = \047%s\047  WHERE family = \047%s\047;\n", $2, $3,$4, $5,$6,$1);}' | $SQL
rm -f $BACKUP_FILE
echo "***************Restore done*******************" >> $LOG
