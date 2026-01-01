#!/bin/bash

PG_USER=postgres
PG_DBNAME=$1
SQL="$psql_bin -U $PG_USER -d $PG_DBNAME -L $LOG"
SQLC="$psql_bin -U $PG_USER -d $PG_DBNAME -c"
BACKUP_FILE="backup_sys_data_range_style.txt"

echo "***************Restoring sys_data_range_style table*******************" >> $LOG
$SQLC "CREATE TABLE sdrs_temp (LIKE sys_data_range_style);"
cat $BACKUP_FILE | awk -F '\t' '{printf("INSERT INTO sdrs_temp (id_element, data_type, svg_style,range_sup,range_inf,filled_color,color,filled_transparence,range_order,family) VALUES(\047%s\047,\047%s\047,\047%s\047,\047%s\047,\047%s\047,\047%s\047,\047%s\047,\047%s\047,\047%s\047,\047%s\047);\n", $1,$2,$3,$4,$5,$6,$7,$8,$9,$10);}' | $SQL
NB_LINE=`$SQLC "SELECT count(*) FROM sdrs_temp WHERE NOT EXISTS(SELECT * FROM sys_data_range_style WHERE sdrs_temp.id_element=sys_data_range_style.id_element);" | sed '3!d'`
echo "There are $NB_LINE lines to restore." >> $LOG
#requete d'insertion dans sys_data_range_style
$SQLC "INSERT INTO sys_data_range_style (SELECT * FROM sdrs_temp WHERE NOT EXISTS(SELECT * FROM sys_data_range_style WHERE sdrs_temp.id_element=sys_data_range_style.id_element));"
rm -f $BACKUP_FILE
$SQLC "DROP TABLE sdrs_temp;"
echo "***************Restore done*******************" >> $LOG
