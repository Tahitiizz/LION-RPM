#: /bin/bash
PG_USER=postgres
PG_DBNAME=$1
SQL="$psql_bin -U $PG_USER -d $PG_DBNAME -c"
BACKUP_FILE="backup_sys_data_range_style.txt"

echo "***************Backing up sys_data_range_style table*******************" >> $LOG
NB_LINE=`$SQL "select count(*) from sys_data_range_style" | sed '3!d'`
echo "There are $NB_LINE lines to backup." >> $LOG
$SQL "COPY sys_data_range_style (id_element, data_type, svg_style,range_sup,range_inf,filled_color,color,filled_transparence,range_order,family) TO stdout WITH NULL AS ''"  > $BACKUP_FILE
echo "***************Backup sys_data_range_style done*******************" >> $LOG