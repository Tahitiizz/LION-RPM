#! /bin/bash

PG_USER=postgres
PG_DBNAME=$1
SQL="$psql_bin -U $PG_USER -d $PG_DBNAME -L $LOG"

BACKUP_FILE="backup_parameters.txt"

echo "***************Restoring sys_global_parameters table*******************"  >> $LOG
cat $BACKUP_FILE | awk -F '\t' '{if($2=="NULL") printf("UPDATE sys_global_parameters SET value = %s WHERE parameters = \047%s\047;\n", $2, $1);else printf("UPDATE sys_global_parameters SET value = \047%s\047 WHERE parameters = \047%s\047;\n", $2, $1);}' | $SQL
rm -f $BACKUP_FILE
echo "***************Restore done*******************"  >> $LOG
