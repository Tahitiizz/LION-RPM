#!/bin/bash

# ==== A MODIFIER ====

archive="homepage_myhomepage_v1.3.1.23.tar.gz"
product_version="1.3.1.23"
product_label="Homepage MyHomepage"
product_name="homepage_myhomepage"
dir_homepage_tmp="new_homepage"

# ==== FIN MODIFICATION ====


# Psql Bin
psql_bin="/usr/local/pgsql/bin/psql"
if [ ! -f "/usr/local/pgsql/bin/psql" ] ; then
	psql_bin="/usr/bin/psql"
fi

PSQL="$psql_bin -U postgres -tAc "
WORKING_DIR=$PWD

DIALOG=gdialog
[ -z $DISPLAY ] && DIALOG=dialog

# Initialisation du fichier de log
LOG=$WORKING_DIR/log_install_${product_name}_${product_version}.log
cat /dev/null > $LOG

# QUIET
if [ -n "$QUIET" ]; then
	QUIET="true"
else
	QUIET="false"
fi

# ==== Definition des fonctions ====

# Affiche un message
# $1 : message
# $2 : titre (facultatif)
show_msg() {
	[ $# -lt 1 ] && return
	msg_title="Info"
	[ $# -eq 2 ] && msg_title=$2
	if [ "$QUIET" = "true" ]; then
		echo $1
	else
		$DIALOG --clear --title "$msg_title" --aspect 50 --msgbox "$1" 0 0
	fi
}

# Verifie que la chaine passee en parametre est correcte
check_appname() {
	str="$1"
	wrong="/ :~&#{}()[]|$%,;:!?"
	l=${#wrong}
	ll=${#str}
	for ((i = 0; i < $l; i++)); do
		for ((j = 0; j < $l; j++)); do
			if [ "${wrong:$i:1}" == "${str:$j:1}" ]
			then
				echo "Incorrect name '$str', don't use '${wrong:$i:1}'."
				clean_exit
			fi
		done
	done
	if [ -z "$str" ]
	then
		echo "Incorrect name. You can relaunch this script now."
		clean_exit
	fi
	res=$(echo ${str} | awk '/^[0-9]/{print 1}')
	if [ "$res" == "1" ]
	then
		echo "Incorrect name '$str', name can't start with a number."
		clean_exit
	fi
	return 0
}

# Nettoie les fichiers qui ont ééressé quitte
clean_dirs() {
	for dir in cb parser context doc homepage scripts
	do
		[ -d $WORKING_DIR/$dir ] && rm -rf $WORKING_DIR/$dir
	done
}

# Nettoye les fichiers qui ont ééressé quitte
clean_exit() {
	clean_dirs
	exit
}

# ==== Tests preliminaires ====

# root ?
if [ "$(id -u)" != "0" ]; then
	MSG="Usage: sh $0 [appname dbname key]

You must be root to run this script"
	show_msg "$MSG" "Error"
	exit
fi

# Si des parametres sont passes
if [ $# -eq 3 ]; then
	appname="$1"
	dbname="$2"
	key="$3"
fi

# Si l'archive est absente, on quitte rapidement
if [ ! -f "$archive" ]; then
	MSG="Unable to find $archive\n
If the sources are not copied on the T&A server:\n
  * Insert the installation CD-ROM in the CD drive\n
  * Launch an other session with root account in order to run the following command:\n
    $ mount /cdrom\n
  * Copy the followings directories in /home/ta_install\n
    $ cp -R /cdrom/sources/$archive /home/ta_install/"

	show_msg "$MSG" "Error"
	clean_exit
fi

# on doit etre dans /home/ta_install
if [ "$PWD" != "/home/ta_install" ]; then
	cat <<EOF
This script must be launched from the /home/ta_install directory: cd /home/ta_install
Then, execute this script again.
Aborting installation.
EOF
	clean_exit
fi

# Nettoyage avant extraction (evite d'utiliser les fichers d'autres applications)
clean_dirs

TITLE="Installation of $product_label $product_version"
MSG="The final version of $product_label will be: $product_version\n
If the sources are not copied on the T&A server:\n
Insert the installation CD-ROM in the CD drive\n
Launch an other session with root account in order to run the following commands\n
Run : mount /cdrom\n
Copy the followings directories in /home/ta_install\n
If previous copies are made, answer y to the following question.\n"
show_msg "$MSG" "$TITLE"

# ==== Collecte des informations (appname, dbname, key) ====

# ==== APPNAME ====
# Si appname est vide on pose la question
if [ -z "$appname" ]
then
	exec 3>&1
	appname=$($DIALOG --clear --title "$TITLE" \
		--inputbox "Please enter the name of the application  (in lowercase)" \
		 16 61 $default_appname 2>&1 >&3)
	exec 3>&-
fi

check_appname $appname

if [ -z "$appname" ]; then
	show_msg "Incorrect name for the application. You can relaunch this script now." "Error"
	clean_exit
fi

# ==== DBNAME ====

# Verification de l'existence du repertoire donc du nom de l'application deja installee default_dbname=''
if [ -d "/home/$appname" ] ; then
	# verification de l'existence du fichier xbdd.inc
	if [ -f "/home/$appname/php/xbdd.inc" ] ; then
		# recuperation du nom de la base de donnees	
		# (on passe un coup de dos2unix, sinon on se recupere une mauvaise fin de ligne
		dos2unix -q /home/$appname/php/xbdd.inc
		default_dbname=$(grep "DBName" /home/$appname/php/xbdd.inc | sed -r 's/\$DBName = \"([^\"]*)\";/\1/g')
		if [ -n "$dbname" ] && [ "$dbname" != "$default_dbname" ] ; then
			MSG="Provided database name is different from installed database name."
			show_msg "$MSG" "Error"
			clean_exit
		fi
		# dans le cas ou le nom de la bdd n'a pas ete fourni et la bdd existe, on utilise celle existante
		dbname=$default_dbname
		# Si le repertoire existe deja on est alors en mode patch, sinon installation
		if [ -f "/home/$appname/homepage/index.php" ]; then
			type_install="patch"
		else
			type_install="installation"
		fi
	fi
fi

# verification de la composition du nom de la base de donnees check_appname $dbname

if [ -z "$dbname" ]; then
	MSG="Incorrect name for the database. You can relaunch this script now."
	show_msg "$MSG" "Error"
	clean_exit
fi

# bz 19811
show_msg "$MSG_DB_NAME $dbname" "$TITLE"
if [ "$appname" != "$dbname" ]; then
	MSG="Application name differs from database name."
	if [ $QUIET = "true" ]; then
		echo $MSG
	else
		MSG="$MSG\nContinue installation?"
		$DIALOG --title "Warning" --clear --yesno "$MSG" 0 0
		resDial=$?
		if [ $resDial -eq 1 ]; then
			clean_exit
		fi
	fi
fi

# Recherche des cas d'erreur : base existe/rep n'existe pas ou bien base n'exite pas/rep existe
base_exists=$($PSQL "SELECT count(*) FROM pg_database WHERE datname='$dbname';")
MSG_CHECK=''
if [ -d "/home/$appname" ] && [ "$base_exists" = "0" ]; then
	# cas ou le repertoire existe mais pas la base
	MSG_CHECK="Directory $appname already exists, but database $dbname doesn't!"	
fi

if [ ! -d "/home/$appname" ] && [ "$base_exists" = "1" ]; then
	# cas ou le repertoire n'existe pas mais la base est presente
	MSG_CHECK="Database $dbname already exists, but directory $appname doesn't!"	
fi

if [ -n "$MSG_CHECK" ]; then
	show_msg "$MSG_CHECK" "Error"
	clean_exit $WORKING_DIR
fi

# ==== Installation ====

if [ "$QUIET" = "false" ]; then
	$DIALOG --title "$TITLE" --msgbox "Press OK to proceed installation." 0 0
	resDial=$?
	if [ $resDial -eq 1 ]; then
		clean_exit
	fi
	$DIALOG --title "$TITLE" --infobox "Take few minutes..." 0 0
	[ $? -eq 255 ] && clean_exit
fi

#USER
owner='astellia.astellia'

check_appname $dir_homepage_tmp

if [ -z "$dir_homepage_tmp" ]; then
	MSG="Incorrect name for the temporary repository. Your script has failed."
	show_msg "$MSG" "Error"
	clean_exit
fi

SQL="$psql_bin -U postgres -L $LOG -d $dbname -tAc "

# === Copie du dossier dans un repertoire temporaire ===
if [ -d $dir_homepage_tmp ] ; then
	rm -rf $dir_homepage_tmp >> $LOG 2>&1
fi
mkdir -m 0777 $dir_homepage_tmp >> $LOG 2>&1
cp -f $archive $dir_homepage_tmp >> $LOG 2>&1
cd $dir_homepage_tmp >> $LOG 2>&1
echo 'Extract all directories and files ...' >> $LOG 2>&1
tar xzvf $archive >> $LOG 2>&1

if [ $type_install == "installation" ] ; then
	echo '
	=== Installation === 
' >> $LOG 2>&1
else
	echo '
	=== Patch === 
' >> $LOG 2>&1
fi

# === Assignation des droits maximums pour le repertoire 'Homepage' ===
chmod -R 0777 /home/$appname/homepage/ >> $LOG 2>&1

if [ $type_install == "installation" ] ; then
	# === Creation de la table sys_templates_list ===
	$SQL 'CREATE TABLE "sys_templates_list" (id_template text NOT NULL, label text, visible smallint DEFAULT 0) WITH OIDS; ALTER TABLE sys_templates_list OWNER TO postgres;' >> $LOG 2>&1
	$SQL "INSERT INTO sys_templates_list (id_template, label, visible) VALUES ('template1', '8 gauges', 1);" >> $LOG 2>&1
	$SQL "INSERT INTO sys_templates_list (id_template, label, visible) VALUES ('template2', '3 gauges', 1);" >> $LOG 2>&1
	$SQL "INSERT INTO sys_templates_list (id_template, label, visible) VALUES ('template3', '20 gauges', 0);" >> $LOG 2>&1
        $SQL "INSERT INTO sys_templates_list (id_template, label, visible) VALUES ('template4', 'URL', 0);" >> $LOG 2>&1
	$SQL "INSERT INTO sys_templates_list (id_template, label, visible) VALUES ('template5', 'Map', 0);" >> $LOG 2>&1
	$SQL "INSERT INTO sys_templates_list (id_template, label, visible) VALUES ('template6', 'Grid', 0);" >> $LOG 2>&1
	$SQL "INSERT INTO sys_templates_list (id_template, label, visible) VALUES ('template7', 'Cells Surveillance', 0);" >> $LOG 2>&1
        $SQL "INSERT INTO sys_templates_list (id_template, label, visible) VALUES ('template8', '6 gauges', 0);" >> $LOG 2>&1
		$SQL "INSERT INTO sys_templates_list (id_template, label, visible) VALUES ('template9', 'Audit Report', 0);" >> $LOG 2>&1
		$SQL "INSERT INTO sys_templates_list (id_template, label, visible) VALUES ('template10', 'Audit Report Evo', 0);" >> $LOG 2>&1
		
	
	# === Copie du repertoire homepage  ===
	cp -r . /home/$appname/homepage >> $LOG 2>&1

	# === Suppression des fichiers inutiles
	rm /home/$appname/homepage/$archive >> $LOG 2>&1
	
	# === Assignation des droits maximums pour les fichiers de configurations ===
	chmod -R 0777 /home/$appname/homepage/config/ >> $LOG 2>&1
	chmod -R 0777 /home/$appname/homepage/config/default/gauges >> $LOG 2>&1
	chmod -R 0777 /home/$appname/homepage/config/default/homepage.xml >> $LOG 2>&1

	 # === Assignation des droits mximums pour les fichiers exportes ===
	chmod -R 0777 /home/$appname/homepage/files/ >> $LOG 2>&1
	
	# === Assignation des droits mximums pour les fichiers uploades ===
	chmod -R 0777 /home/$appname/homepage/archives/ >> $LOG 2>&1
else
	#mode patch

	# Sauvegarde de l'ancienne config
	echo "Start Config backup before installation " >> $LOG 2>&1
	if [ ! -n "${timestampIntallLog+x}" ]; then
		timestampIntallLog=$(date +%Y.%m.%d-%H.%M)
	fi
	backup_config_dir="/home/"${appname}"/homepage/backup/backup_config_preinstall_"${product_name}"_"${product_version}"_"$timestampIntallLog
	backup_config_file="/home/"${appname}"/homepage/backup/backup_config_preinstall_"${product_name}"_"${product_version}"_"$timestampIntallLog".tar.gz"
	backup_dir="/home/"${appname}"/homepage/backup"
	if [ ! -d $backup_dir ] ; then
		mkdir -m 0777 $backup_dir >> $LOG 2>&1
	fi
	mkdir -m 0777 $backup_config_dir >> $LOG 2>&1
	cp -rf /home/$appname/homepage/config/* $backup_config_dir >> $LOG 2>&1
	tar -zcf $backup_config_file -C /home/$appname/homepage config >> $LOG 2>&1
	rm -Rf $backup_config_dir >> $LOG 2>&1
	echo "Config backup before installation available under $backup_config_file" >> $LOG 2>&1

	#si le repertoire archives n'existe pas, on le cree
	if [ ! -d /home/$appname/homepage/archives/ ] ; then
		mkdir -m 0777 /home/$appname/homepage/archives >> $LOG 2>&1
	fi
	
	#BZ 38466 set write rights to every user dir
	chmod 777 -R /home/$appname/homepage/config/*/

	# == Copie des repertoires generiques
	rm -Rf /home/$appname/homepage/css >> $LOG 2>&1
	cp -r css /home/$appname/homepage/css >> $LOG 2>&1
	
	rm -Rf /home/$appname/homepage/extjs >> $LOG 2>&1
	cp -r extjs /home/$appname/homepage/extjs >> $LOG 2>&1
	
	rm -Rf /home/$appname/homepage/fpdf >> $LOG 2>&1
	cp -r fpdf /home/$appname/homepage/fpdf >> $LOG 2>&1
	
	rm -Rf /home/$appname/homepage/images >> $LOG 2>&1
	cp -r images /home/$appname/homepage/images >> $LOG 2>&1
	
	rm -Rf /home/$appname/homepage/js >> $LOG 2>&1
	cp -r js /home/$appname/homepage/js >> $LOG 2>&1
	
	rm -Rf /home/$appname/homepage/proxy >> $LOG 2>&1
	cp -r proxy /home/$appname/homepage/proxy >> $LOG 2>&1
	
	# == Copie des fichiers generiques
	cp -f app-all.js /home/$appname/homepage/app-all.js >> $LOG 2>&1
	cp -f homepage.js /home/$appname/homepage/homepage.js >> $LOG 2>&1
	cp -f index.php /home/$appname/homepage/index.php >> $LOG 2>&1
        cp -f myhomepage.php /home/$appname/homepage/myhomepage.php >> $LOG 2>&1

    if [ ! -f /home/$appname/homepage/config/general.xml ] ; then
        cp -f config/general.xml /home/$appname/homepage/config/general.xml >> $LOG 2>&1
    fi
	
	#we don't need anymore map.xml, map conf is now located in general.xml
	if [ -f /home/$appname/homepage/config/map.xml ] ; then
        rm -f /home/$appname/homepage/config/map.xml >> $LOG 2>&1
    fi
	
	# BZ34198 copie des nouveaux templates en mode patch
	rm -f /home/$appname/homepage/config/templates/* >> $LOG 2>&1
	cp -rf config/templates/* /home/$appname/homepage/config/templates >> $LOG 2>&1
        
		# Maj des noms de template
        $SQL "UPDATE sys_templates_list SET label='8 gauges' WHERE id_template LIKE 'template1';" >> $LOG 2>&1
		$SQL "UPDATE sys_templates_list SET label='3 gauges' WHERE id_template LIKE 'template2';" >> $LOG 2>&1
		$SQL "UPDATE sys_templates_list SET label='20 gauges' WHERE id_template LIKE 'template3';" >> $LOG 2>&1
		$SQL "UPDATE sys_templates_list SET label='URL' WHERE id_template LIKE 'template4';" >> $LOG 2>&1
		$SQL "UPDATE sys_templates_list SET label='Map' WHERE id_template LIKE 'template5';" >> $LOG 2>&1
		$SQL "UPDATE sys_templates_list SET label='Grid' WHERE id_template LIKE 'template6';" >> $LOG 2>&1
		$SQL "UPDATE sys_templates_list SET label='Cells Surveillance' WHERE id_template LIKE 'template7';" >> $LOG 2>&1
		$SQL "UPDATE sys_templates_list SET label='6 gauges' WHERE id_template LIKE 'template8';" >> $LOG 2>&1
		$SQL "UPDATE sys_templates_list SET label='Audit Report' WHERE id_template LIKE 'template9';" >> $LOG 2>&1
		$SQL "UPDATE sys_templates_list SET label='Audit Report Evo' WHERE id_template LIKE 'template10';" >> $LOG 2>&1

        # Rajout des templates manquants
        $SQL "INSERT INTO sys_templates_list (id_template, label, visible) SELECT 'template1', '8 gauges', 1 WHERE NOT EXISTS (SELECT id_template FROM sys_templates_list WHERE id_template LIKE 'template1');" >> $LOG 2>&1
        $SQL "INSERT INTO sys_templates_list (id_template, label, visible) SELECT 'template2', '3 gauges', 1 WHERE NOT EXISTS (SELECT id_template FROM sys_templates_list WHERE id_template LIKE 'template2');" >> $LOG 2>&1
        $SQL "INSERT INTO sys_templates_list (id_template, label, visible) SELECT 'template3', '20 gauges', 0 WHERE NOT EXISTS (SELECT id_template FROM sys_templates_list WHERE id_template LIKE 'template3');" >> $LOG 2>&1
        $SQL "INSERT INTO sys_templates_list (id_template, label, visible) SELECT 'template4', 'URL', 0 WHERE NOT EXISTS (SELECT id_template FROM sys_templates_list WHERE id_template LIKE 'template4');" >> $LOG 2>&1
        $SQL "INSERT INTO sys_templates_list (id_template, label, visible) SELECT 'template5', 'Map', 0 WHERE NOT EXISTS (SELECT id_template FROM sys_templates_list WHERE id_template LIKE 'template5');" >> $LOG 2>&1
        $SQL "INSERT INTO sys_templates_list (id_template, label, visible) SELECT 'template6', 'Grid', 1 WHERE NOT EXISTS (SELECT id_template FROM sys_templates_list WHERE id_template LIKE 'template6');" >> $LOG 2>&1
        $SQL "INSERT INTO sys_templates_list (id_template, label, visible) SELECT 'template7', 'Cells Surveillance', 0 WHERE NOT EXISTS (SELECT id_template FROM sys_templates_list WHERE id_template LIKE 'template7');" >> $LOG 2>&1
        $SQL "INSERT INTO sys_templates_list (id_template, label, visible) SELECT 'template8', '6 gauges', 0 WHERE NOT EXISTS (SELECT id_template FROM sys_templates_list WHERE id_template LIKE 'template8');" >> $LOG 2>&1
		$SQL "INSERT INTO sys_templates_list (id_template, label, visible) SELECT 'template9', 'Audit Report', 0 WHERE NOT EXISTS (SELECT id_template FROM sys_templates_list WHERE id_template LIKE 'template9');" >> $LOG 2>&1
		$SQL "INSERT INTO sys_templates_list (id_template, label, visible) SELECT 'template10', 'Audit Report Evo', 0 WHERE NOT EXISTS (SELECT id_template FROM sys_templates_list WHERE id_template LIKE 'template10');" >> $LOG 2>&1
     
fi

# === Version de la Homepage ===
$SQL "DELETE FROM sys_global_parameters WHERE parameters = 'homepage_c-sight_version';" >> $LOG 2>&1
$SQL "INSERT INTO sys_global_parameters (parameters, value, configure, client_type, label, category, order_parameter, comment) VALUES ('homepage_c-sight_version', '$product_version', 1, 'client', 'Homepage version', 3, 20, 'Homepage version');" >> $LOG 2>&1

# === Suppression du repertoire temporaire
cd $WORKING_DIR
rm -rf $dir_homepage_tmp >> $LOG 2>&1

# === Restauration du proprietaire ===
chown -R $owner /home/$appname >> $LOG 2>&1

# ==== Weekly backup ===
sed -i "/backup_myhp_${appname}/d" /var/spool/cron/root
echo '0 0 * * 1' tar czf /home/backup/backup_myhp_${appname}_${product_version}_'$(date +\%Y\%m\%d)'.tgz /home/$appname/homepage/config '&&' find /home/backup -name 'backup_myhp*' -ctime +70 '|' xargs rm >> /var/spool/cron/root

# === Message de fin ===
MSG="Homepage installation done"
show_msg "$MSG"