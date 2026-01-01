#!/bin/bash


db=$1;


function usage()
{
if [ $# -gt 2 -o  $# -lt 1  ] ; then
	echo "########################################################################";
	echo "# USAGE : "$0" database [type]";
	echo "#  - database : T&A database name"
	echo "########################################################################";
	exit;
fi;
}

function echo_stamp()
{
  echo "`date +'[%d/%m/%y %H:%M:%S]'` $@"
}

function sql() # Execute query
{
  psql -d "$db" -U postgres -tAc "$1"
  return $?
}

function sqlv() # Execute query: Verbose mode
{
	NOCOL=$'\e[00m'
	RED=$'\e[1;31m'
	echo "$NOCOL"
	echo "$1"
	echo "$RED+++++++++++++++++++++++"
	sql "$1"
	echo "+++++++++++++++++++++++$NOCOL"
	return $?
}

function confirm ()
{
read rep
if [ $rep = "n" ] ; then
  exit 1
fi
}

function appli_start_stop()
{

i=1
sqlv "truncate table sys_definition_master"
sqlv "insert into sys_definition_master select * from sys_definition_master_ref"
sqlv "update sys_definition_master set on_off=0"
nb_temp_table=$(sql "select count(tablename) FROM pg_tables WHERE schemaname = 'public' and tablename like 'w_%' ;")
old_IFS=$IFS
IFS=$'\n'

echo "There are $nb_temp_table temporary table to suppress"

	for table in $(sql "select tablename FROM pg_tables WHERE schemaname = 'public' and tablename like 'w_%' ;") ; do
		echo -en "\x0D"
		echo -n "$i"/"$nb_temp_table"
		sql "DROP TABLE $table ;" > /dev/null
		i=$(($i + 1))
	done

  for table in sys_w_tables_list sys_process_encours sys_requetes sys_step_track \
    sys_family_track sys_flat_file_uploaded_list sys_crontab sys_to_compute
  do
    sqlv "truncate table $table"
  done
}


usage $@

echo "++++++++++++++++++++++++++++++++"
echo "+  Launch of Stop Start        +"
echo "+  All temporary tables and    +"
echo "+  files will be deleted       +"
echo "+  Continue ? y or n           +"
echo "++++++++++++++++++++++++++++++++"
confirm
killall -9 php
appli_start_stop

for file in /home/$db/upload/*.txt ; do
	rm -f $file
done

rm -f /home/$db/png_files/dump_processing.txt

echo "++++++++++++++++++++++++++++++++"
echo "+  Launch of VACUUM FULL       +"
echo "+  In order to free disk space +"
echo "+  Continue ? y or n           +"
echo "++++++++++++++++++++++++++++++++"
confirm

echo_stamp "Launch of VACUUM FULL ANALYZE"
vacuumdb -U postgres -fz $db
echo_stamp "End of VACUUM FULL ANALYZE"
