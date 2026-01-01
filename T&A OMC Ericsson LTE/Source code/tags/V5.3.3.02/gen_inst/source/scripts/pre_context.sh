#! /bin/bash

# Il est possible d'utiliser les variables du script quick_install :
# SQL WORKING_DIR type_install appname dbname psql_bin LOG DIALOG QUIET postgresqlPort
#
# WORKING_DIR = chemin d'installation
# type_install = installation/migration/patch
# appname = nom de l'application (astellia_iu)
# dbname = nom de la base de donnees
# LOG = nom (+ chemin) du fichier de log
# DIALOG = dialog/gdialog
# QUIET = true/false, installation silencieuse
# $psql_bin = /usr/local/pgsql/bin/psql [PG 8.2] ou /usr/bin/psql [PG 9.1], peut eventuellement changer
# SQL="$psql_bin -U postgres -p $postgresqlPort -L $LOG -d $dbname -tAc "
# SQLFILE="$psql_bin -U postgres -p $postgresqlPort -L $LOG -d $dbname -f "
# postgresqlPort = port du server Postgresql
#
# Exemple :
#$SQL "UPDATE sys_definition_network_agregation SET na_max_unique=0;" 2>&1
#$SQLFILE $WORKING_DIR/scripts/mon_fichier_.sql 2>&1
