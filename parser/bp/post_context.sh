#! /bin/bash

# Il est possible d'utiliser les variables du script quick_install :
# SQL WORKING_DIR type_install appname dbname psql_bin LOG DIALOG QUIET
#
# WORKING_DIR  = chemin d'installation
# type_install = installation/migration/patch
# appname      = nom de l'application (astellia_iu)
# dbname       = nom de la base de donnees
# LOG          = nom (+ chemin) du fichier de log
# DIALOG       = dialog/gdialog
# QUIET        = true/false, installation silencieuse
# $psql_bin    = /usr/local/bin/psql, peut eventuellement changer
#
# SQL="$psql_bin -U postgres -L $LOG -d $dbname -tAc "
# SQLFILE="$psql_bin -U postgres -L $LOG -d $dbname -f "
#
# Exemple :
#$SQL "UPDATE sys_definition_network_agregation SET na_max_unique=0;" 2>&1
#$SQLFILE $WORKING_DIR/scripts/mon_fichier_.sql 2>&1

# 28/02/2013 GFS - Bug 32418 - [SUP][TA Roaming RAN][AVP 32959][Telus]:Weekly report preview doesn't work
$SQL "UPDATE sys_global_parameters SET value = '1' WHERE parameters = 'offset_day';" 2>&1

# On exécute les commandes uniquement pour une installation de base
if [ $type_install = "installation" ];then
    # Step 01 - Arret des cron
    /etc/init.d/crond stop

    # Step 02 - Mise en commentaire des 2 crontab inutiles sur le produit blanc
    sed -e "s/\(.*\/home\/$appname\/scripts\/[check|update].*\)/#\1/" /var/spool/cron/astellia > /var/spool/cron/astellia_tmp
    mv -f /var/spool/cron/astellia_tmp /var/spool/cron/astellia

    # Step 03 - Ajout de la nouvelle cron pour l'envoi des rapports
    newcron="0 \* \* \* \* php -q /home/$appname/scripts/report_sender_bp.php &> /dev/null"
    sed -e "/.*$appname\/scripts\/lancement_scripts.php.*/i\\$newcron" /var/spool/cron/astellia > /var/spool/cron/astellia_tmp
    mv -f /var/spool/cron/astellia_tmp /var/spool/cron/astellia
	
	# Step 04 - (13/12/2012 BBX BZ 30942) Ajout de la nouvelle cron pour nettoyer le produit
    newcron="@daily php -q /home/$appname/scripts/clean_files.php &> /dev/null"
    sed -i "/.*$appname\/scripts\/lancement_scripts.php.*/i\\$newcron" /var/spool/cron/astellia
	
    # Step 05 - Redemarrage des cron
    /etc/init.d/crond start

    # Step 06 - Ajout d'une entrée dans sys_definition_master (et ref)
    $SQL "INSERT INTO sys_definition_master (master_id,master_name,utps,offset_time,on_off,visible,ordre,commentaire) VALUES ((SELECT max(master_id)+1 FROM sys_definition_master),'Report Sender',0,0,0,1,(SELECT max(ordre)+1 FROM sys_definition_master),'Date use for manual report generation (with format yyyy/mm/dd)');" 2>&1
    $SQL "INSERT INTO sys_definition_master_ref (master_id,master_name,utps,offset_time,on_off,visible,ordre,commentaire) VALUES ((SELECT max(master_id)+1 FROM sys_definition_master),'Report Sender',0,0,0,1,(SELECT max(ordre)+1 FROM sys_definition_master),'Date use for manual report generation (with format yyyy/mm/dd)');" 2>&1

    # Step 07 - Ajout et mise à jour de paramètres globaux
    $SQL "INSERT INTO sys_global_parameters (parameters,value,configure,label,comment) VALUES ( 'bp_report_sender_hour','5',0,'Report sender hour for TA Gateway','');" 2>&1   
else
	# 13/12/2012 BBX BZ 30942
	# En cas d'update, il faut ajouter la cron clean_file si elle n'existe pas
	newcron="@daily php -q /home/$appname/scripts/clean_files.php &> /dev/null"
	/etc/init.d/crond stop
	cronexists=$(grep -c "$newcron" /var/spool/cron/astellia)
	if [ $cronexists -eq 0 ] ; then
		sed -i "/.*$appname\/scripts\/lancement_scripts.php.*/i\\$newcron" /var/spool/cron/astellia
	fi
	/etc/init.d/crond start
fi
