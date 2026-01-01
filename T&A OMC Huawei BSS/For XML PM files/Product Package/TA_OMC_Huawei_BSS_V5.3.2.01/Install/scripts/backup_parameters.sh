#!/bin/bash
PG_USER=postgres
PG_DBNAME=$1
SQL="$psql_bin -U $PG_USER -d $PG_DBNAME -c"
BACKUP_FILE="backup_parameters.txt"

echo "***************Backing up sys_global_parameters table*******************"  >> $LOG
NB_PARAM=`$SQL "select count(*) from sys_global_parameters" | sed '3!d'`
echo "There are $NB_PARAM parameters to backup." >> $LOG
$SQL "COPY sys_global_parameters (parameters, value) TO stdout WITH NULL AS 'NULL'"  > $BACKUP_FILE

echo "***************Backup done*******************"  >> $LOG

