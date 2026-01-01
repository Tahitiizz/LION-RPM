#! /bin/bash

NbLinesInFile=`cat $WORKING_DIR/scripts/clean_sys_flat_file_uploaded_list_archive.csv | wc -l`  >> /dev/null  2>&1
NbLinesInBase=`$psql_bin -U postgres -d $dbname -tAc "SELECT count(flat_file_template) FROM sys_flat_file_uploaded_list_archive;"`

echo "*******************************************************************************************************************************"
echo "WARNING : "$NbLinesInFile" SQL requests will be executed on a table containing "$NbLinesInBase" lines. This may take some time."
echo "This operation may take between 5 and 60 minutes depending on your server and database."

echo "1" > /tmp/progress_bar
sh $WORKING_DIR/scripts/progress_bar.sh &
cat $WORKING_DIR/scripts/clean_sys_flat_file_uploaded_list_archive.csv | awk -F ';' '{if (NR>1) {printf("UPDATE sys_flat_file_uploaded_list_archive SET flat_file_template=\047%s\047 WHERE flat_file_template=\047%s\047;\n", $2, $1);}}' | $psql_bin -U postgres -d $dbname >> $LOG 2>&1
cat $WORKING_DIR/scripts/clean_sys_flat_file_uploaded_list_archive.csv | awk -F ';' '{if (NR>1) {printf("UPDATE sys_flat_file_uploaded_list SET flat_file_template=\047%s\047 WHERE flat_file_template=\047%s\047;\n", $2, $1);}}' | $psql_bin -U postgres -d $dbname >> $LOG 2>&1

rm /tmp/progress_bar
echo ""
echo "SQL operation finished."
echo "*******************************************************************************************************************************"