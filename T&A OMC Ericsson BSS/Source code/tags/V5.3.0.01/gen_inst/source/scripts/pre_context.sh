#! /bin/bash

# Il est possible d'utiliser les variables du script quick_install :
# SQL WORKING_DIR type_install appname dbname psql_bin LOG DIALOG QUIET
#
# WORKING_DIR = chemin d'installation
# type_install = installation/migration/patch
# appname = nom de l'application (astellia_iu)
# dbname = nom de la base de donnees
# LOG = nom (+ chemin) du fichier de log
# DIALOG = dialog/gdialog
# QUIET = true/false, installation silencieuse
# $psql_bin = /usr/local/bin/psql, peut eventuellement changer
# SQL="$psql_bin -U postgres -L $LOG -d $dbname -tAc "
# SQLFILE="$psql_bin -U postgres -L $LOG -d $dbname -f "
#
# Exemple :
#$SQL "UPDATE sys_definition_network_agregation SET na_max_unique=0;" 2>&1
#$SQLFILE $WORKING_DIR/scripts/mon_fichier_.sql 2>&1


# sauvegarde du context avant installation
echo "*******************************************************************************************************************************"
echo "Start Context backup before installation " >> $LOG
if [ ! -n "${timestampIntallLog+x}" ]; then
    timestampIntallLog=$(date +%Y.%m.%d-%H.%M)
fi
backup_ctxt_dir="/home/"${appname}"/SQL/backup_context_preinstall_"${product_name}"_"${product_version}"_"$timestampIntallLog
mkdir -p $backup_ctxt_dir
cmd=$(dirname $psql_bin)"/pg_dump -U postgres -Fc "$appname
for table in $( $psql_bin -U postgres $appname -c "select sdct_table from sys_definition_context_table;" -t) sys_versioning sys_definition_context_element_to_delete; do
    cmd=$cmd" --table="$table" "
    $psql_bin -U postgres $appname -AF";" -c "select * from $table" > $backup_ctxt_dir/${table}.csv
    gzip -f $backup_ctxt_dir/${table}.csv
done
$cmd > $backup_ctxt_dir/backup_context.dump
echo "Context backup before installation available under $backup_ctxt_dir" >> $LOG
echo "*******************************************************************************************************************************"

#ajout des fonctions erlang améliorées
$SQLFILE $WORKING_DIR/scripts/erlangb_gos.sql 2>&1
$SQLFILE $WORKING_DIR/scripts/erlangb_ressource.sql 2>&1
$SQLFILE $WORKING_DIR/scripts/erlangb_traffic.sql 2>&1

