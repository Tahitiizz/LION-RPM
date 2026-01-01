#!/bin/sh
# Astellia 2011/11/03
# Matthieu STRULLU : Script designed to delete Network Element in T&A Topology which has no data since XX days.
#
#Astellia 2012/01/23
# Matthieu STRULLU : Don't take in account the virtual cell anymore.
#
#Astellia 2012/11/12
# Matthieu STRULLU : Suppress -d option on pg_dump command.
#
#Astellia 2013/01/26 - Release D
# Matthieu STRULLU : Add silent and verbose mode
#
#Astellia 2013/01/31 - Release E
# Matthieu STRULLU : Add print mode 


# FUNCTION
function usage() 
{

	echo "This is "$0", the script to delete inactive element in T&A topology"
	echo -e "\r"
    echo "Usage: "$0" [OPTIONS] [-d DATABASE] [-n NBDAYS]"	
	echo -e "\r"
	echo "Mandatory options:"
	echo " -d DATABASE	Name of T&A database"
	echo " -n NBDAYS	Number of days of inactivity of cells to delete"
	echo -e "\r"
	echo " Non Mandatory options:"
	echo " -s 		Silent (Non interactive) mode (For crontab use)"
	echo " -h		Show this help, then exit"
	echo " -v		Show debug information"
	echo " -p		Print inactive cell then exit"
	echo -e "\r"
	echo "For more information, refer to Astellia wiki or contact m.strullu@astellia.com"
	
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

function confirm () # Confirm or exit
{

if [ $SilentMode = "verbose" ] ; then

	NOCOL=$'\e[00m'
	RED=$'\e[1;31m'
	echo "$RED+++++++++++++++++++++++"
	echo "+  Continue ? y or n  +"
	echo "+++++++++++++++++++++++$NOCOL"
	read rep
	if [ $rep != "y" ] ; then
	  exit 1
	fi

fi
}

function echo_stamp()
{
  echo "`date +'[%d/%m/%y %H:%M:%S]'` $@"
}

function init_var() # Variable initialization
{
SilentMode="verbose"
debug="false"
PrintCell="false"

while getopts :svphd:n: opt ; do

	case $opt in
		s)
			SilentMode="silent"
			;;
		d)
			db="$OPTARG"
			;;
		n)
			InactiveDay="$OPTARG"
			;;
		h)
			usage
			exit 0 
			;;
		v)
			debug="true"
			;;
		p)
			PrintCell="print"
			;;
		\?)
			
			SilentMode="verbose"
			echo "Invalid option: -$OPTARG" >&2
			;;
	esac
done

CurrentTimeStamp=`date '+%Y%m%d%H%M%S'`
MainFamily=`sql "SELECT family from sys_definition_categorie where main_family='1' ;"`
NetworkLevel=`sql "SELECT network_aggregation_min from sys_definition_categorie where main_family='1' ;"`
NetworkLevelAxe3=`sql "SELECT agregation from sys_definition_network_agregation where family = '"$MainFamily"' and axe='3' and agregation_level='1' ;"`
NetworkLevelAxe3Length=`echo "$NetworkLevelAxe3" | wc -c`
DataTable=`sql "SELECT edw_group_table from sys_definition_group_table where family='"$MainFamily"' ;"`
DateToDelete=`date -d ""$InactiveDay" days ago" '+%Y%m%d'`
DateToDeleteDisplay=`date -d ""$InactiveDay" days ago" '+%Y/%m/%d'`
NbNetElts=`sql "SELECT COUNT(*) from edw_object_ref where eor_obj_type='"$NetworkLevel"' and eor_id not like '%virtual%'; "`
}

function debug() # Display variables content
{
	echo "Database involved: $db"
	echo "MainFamily: $MainFamily"
	echo "NetworkLevel: $NetworkLevel"
	echo "NetworkLevelAxe3: $NetworkLevelAxe3"
	echo "edw_group_tabe: $DataTable"
	echo "Sart of inactivity: $DateToDelete"
	echo "Length of NetworkLevelAxe3: $NetworkLevelAxe3Length"
}

function delete_net_elts() # Deletion of inactive element in topology tables
{
sqlv "DELETE FROM edw_object_ref where eor_obj_type='"$NetworkLevel"' and eor_id not in (SELECT distinct("$NetworkLevel") from $DataTable where day > '"$DateToDelete"') and eor_id not like '%virtual%' ;"
sqlv "DELETE FROM edw_object_arc_ref where eoar_arc_type like '"$NetworkLevel"|s|%' and eoar_id not in (SELECT distinct(eor_id) FROM edw_object_ref where eor_obj_type='"$NetworkLevel"')  ;"
sqlv "DELETE FROM edw_object_ref_parameters where eorp_id not in (SELECT distinct(eor_id) FROM edw_object_ref where eor_obj_type='"$NetworkLevel"')  ;"
}

function backup_topo() # Backup Topology tables
{
pg_dump "$db" -U postgres -t edw_object_ref >  edw_object_ref_$CurrentTimeStamp.sql
pg_dump "$db" -U postgres -t edw_object_arc_ref > edw_object_arc_ref_$CurrentTimeStamp.sql
pg_dump "$db" -U postgres -t edw_object_ref_parameters > edw_object_ref_parameters_$CurrentTimeStamp.sql
tar cfvz Backup_topo_before_purge_$CurrentTimeStamp.tar.gz edw_object_*$CurrentTimeStamp.sql
echo "+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
echo "Topology tables has been backup in the file Backup_topo_before_purge_$CurrentTimeStamp.tar.gz"
echo "+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
}

# Usage
if [ $# -lt 2 ] ; then
usage
exit 1
fi;

# Variable initialization
init_var $*

if [ $debug = "true" ] ; then
	debug
fi

# Test on 3rd Axis and definition of the based data table
if [ "$NetworkLevelAxe3Length" -gt 1 ]; then
	#echo "Axe 3 detected"
	DataTable=$DataTable"_raw_"$NetworkLevel"_"$NetworkLevelAxe3"_day"
else
	#echo "No Axe 3 detected"
	DataTable=$DataTable"_raw_"$NetworkLevel"_day"
fi


# Count the number of inactive element to delete
NbNetEltsToDel=`sql "SELECT COUNT(*) from edw_object_ref where eor_obj_type='"$NetworkLevel"' and eor_id not in (SELECT distinct("$NetworkLevel") from $DataTable where day > '"$DateToDelete"') and eor_id not like '%virtual%' ;"`



# Warning and confirmation of user
echo "+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
echo "+  There are $NbNetElts $NetworkLevel in $db"
echo "+  There are $NbNetEltsToDel $NetworkLevel inactive since $DateToDeleteDisplay in $db"
echo "+  This $NbNetEltsToDel $NetworkLevel will be delete of T&A topology"
echo "+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
if [ $PrintCell = "print" ] ; then
	echo "Cell;Cell Label" > InactiveCell_$DateToDelete.csv
	sql "SELECT eor_id, eor_label from edw_object_ref where eor_obj_type='"$NetworkLevel"' and eor_id not in (SELECT distinct("$NetworkLevel") from $DataTable where day > '"$DateToDelete"') and eor_id not like '%virtual%' ;" | sed 's/|/;/g' >> InactiveCell_$DateToDelete.csv
	echo "Inactive cells list export to InactiveCell_$DateToDelete.csv"
	exit 0
fi

confirm

# Backup Topology tables
backup_topo

confirm

# Deletion of inactive element in topology tables
delete_net_elts
