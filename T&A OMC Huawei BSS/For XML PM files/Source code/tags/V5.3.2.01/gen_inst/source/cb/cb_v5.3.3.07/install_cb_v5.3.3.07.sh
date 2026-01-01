#!/bin/sh

# DOWNGRADE 
enable_downgrade=0

# VARIABLES TO BE DEFINED
version_cb=5.3.3.07
version_extractor=1.29

# INTERNAL VARIABLES DECLARATION (KEEP UNTOUCHED)
BASE=`pwd`
user=`whoami`
name_cb=cb
file_source_sql="cb_v"$version_cb".sql"
file_source="cb_v"$version_cb".tar.gz"
file_install_log="install_cb_"$version_cb".log"
cb_files='cb_files.tar.gz'
cb_bdd='cb_database.tar.gz'
cb_doc="cb_doc_v"$version_cb".tar.gz"
file_install_macro='install_macro.sh'
file_install_divparzero='install_divparzero.sh'
file_install_dblink='install_dblink.sh'
postgresDefaultPort='5432'
timezone_server=`cat /etc/sysconfig/clock | grep -E "^ZONE" | cut -d"=" -f2 | sed 's/"//g'`
date=`date +%Y_%m_%d_%H_%M`
cron_path="/usr/local/pgsql/bin:/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin"

# 23/09/2010 NSE : fusion de scripts d'install CB 5.0 et 5.1
# 05/07/2011 NSE bz 22257 : la v&#65533;rification de la version RHEL n'est plus effectu&#65533;e au niveau CB
# 05/10/2011 BBX BZ 22615 : optimisation de l'application des droits afin d'&#65533;viter les freeze sur les r&#65533;pertoires avec beaucoup de fichiers

# En principe :
#Un CB 5.0 s'installe sur une RHEL 4.5/4.8
#Un CB 5.1 s'installe sur une RHEL 4.5/4.8 ou sur une 5.5
is_cb50=`echo $version_cb | grep -E "^5.0." | wc -l`
cb_version_short=`echo $version_cb | sed -r "s/^([0-9]+\.[0-9]+)\..*/\1/g"`

# BINARY PATHS
psql_dir=/usr/bin
java_dir=/usr/java/jdk1.5.0_02/bin/

# GETTING PHP BINARY PATH AND CHECKING VERSION
if [ $is_cb50 = 1 ];then
	php_dir=/usr/local/bin/
else
	php_dir=''
fi
is_php5=`${php_dir}php -v | head -n1 | grep -E "^PHP 5.2." | wc -l`
if [ $is_php5 != "1" ] ; then
	echo "CANNOT FIND PHP 5.2 BINARY !";
	exit 127
fi


# ROOT ONLY CAN EXECUTE THIS SCRIPT
if [ $user = 'root' ]; then
    # 23/04/2014 NSE : suppression du port qui n'est pas utilis&#65533;
    # ARGUMENTS CHECK
    # 04/03/2011 SCT : Ajout d'un septieme parametre optionnel : l'IP du serveur
    # 12/04/2011 SCT : Passage &#65533; 6 param&#65533;tres obligatoires
	if [ $# -lt 6 ]; then
		echo '#######################################################################################################'
		echo '## '$0' : too many or too few arguments'
		echo '## Usage :'
		echo '## sh '$0' [application_path] [database_name] [alias] [source_path] [user] [add/reset crontab] [preset_ip : optional] [postgresql_port : optional, can be defined but not used yet]'
		echo '########################################################################################################'
		exit 127
	fi

	# FETCHING ARGUMENTS
	rep_install=$1		# install directory
	database=$2			# DB name
	alias=$3			# alias
	rep_source=$4		# source directory
	user=$5				# User
	owner=$user'.'$user	# Owner
	cron_mode=$6		# replace or add for crontab
        # 25/06/2014 NSE : on ne tient pas compte du port, m&#65533;me s'il existe.
        # Mantis 2561: [DE MKT] Reverse Proxy - Prise en compte hostname dans les setups TA 
        # on ne v&#65533;rifie plus la validit&#65533; de l'IP
        option1=$7
        option2=$8
        messageLog=""
        # si l'option 2 n'est pas vide on est s&#65533;r que l'option 1 est l'IP
        if [ ! -z "$option2" ]; then
            preset_ip=$option1
            messageLog=$messageLog"7th parameter transmitted, IP: "$option1"\n"
        # on n'a qu'une option
        elif [ ! -z "$option1" ]; then
            # si l'option1 est un entier, c'est le port et on n'a pas d'IP
            if [ "$(echo $option1 | grep "^[ [:digit:] ]*$")" ] && [ $option1 -lt 65535 ] ; then
                postgresPort=$option1
                messageLog=$messageLog"7th parameter is PostgresqlPort : "$option1"\n"
            # sinon, c'est l'IP
            else
                preset_ip=$option1
            fi
        fi
        # 11/05/2011 SCT : la s&#65533;lection d'un port postgresql doit &#65533;tre d&#65533;sactiv&#65533;e pour le moment => CB non compatible avec cette option
        postgresPort=$postgresDefaultPort
        if [ -z "$preset_ip" ] ; then
            messageLog=$messageLog"Server Ip is not in install_cb parameters\n"
        fi
        # FIN 12/04/2011 SCT

	# SCRIPT NAME
	file_install_script=`basename $0`

	# SOURCE DIR FULL PATH
	# 05/04/2011 NSE : on utilise le chemin absolu dans tous les cas
	cd $rep_source
	rep_source=`pwd`

	# REMOVING ENDING SLASH IF PRESENT
	rep_install=`echo $rep_install | sed "s/\/$//"`

	# COMPATIBILITY TRICK
	# SOME PARSERS NEED THE OLD PATH FOR BINARIES
	if [ ! -f "/usr/local/pgsql/bin/psql" ] ; then
		mkdir -p /usr/local/pgsql/bin/
		cd /usr/local/pgsql/bin/
		ln -s /usr/bin/psql
		ln -s /usr/bin/psql.bin
		ln -s /usr/bin/pg_dump
		ln -s /usr/bin/pg_ctl
		ln -s /usr/bin/createdb
		ln -s /usr/bin/postmaster
	fi

	# COMPATIBILITY HACK FOR RHEL 4.5/4.8 OS :
	# SYMLINKING PG BINARIES TO SAME PATH THAN RHEL 5.5
	if [ ! -f "$psql_dir/psql" ] ; then
		mkdir -p $psql_dir
		cd $psql_dir

		rm -f ./pg_dump
		rm -f ./pg_ctl
		rm -f ./createdb
		rm -f ./postmaster
		rm -f ./psql.bin

		# TESTING OLD BINARY
		if [ ! -f "/usr/local/pgsql/bin/psql" ] ; then
			echo "CANNOT FIND POSTGRESQL BINARY !";
			exit 127
		fi

		ln -s /usr/local/pgsql/bin/psql
		ln -s /usr/local/pgsql/bin/psql.bin
		ln -s /usr/local/pgsql/bin/pg_dump
		ln -s /usr/local/pgsql/bin/pg_ctl
		ln -s /usr/local/pgsql/bin/createdb
		ln -s /usr/local/pgsql/bin/postmaster
	fi

	# COMPATIBILITY HACK FOR RHEL 4.5/4.8 OS :
	# SYMLINKING JAVA BINARIES TO SAME PATH THAN RHEL 5.5
	if [ ! -f "$java_dir/java" ] ; then
		mkdir -p $java_dir
		cd $java_dir

		# TESTING OLD BINARY
		if [ ! -f "/opt/jdk1.5.0_02/bin/java" -a ! -h "/opt/jdk1.5.0_02/bin/java" ] ; then
			echo "CANNOT FIND JAVA BINARY !";
			exit 127
		fi

		ln -s /opt/jdk1.5.0_02/bin/java
	fi

	# READING IP ADDRESS
	# 23/09/2010 NSE report bz 16403 : nouvelle methode de recuperation de l'ip
	# 04/03/2011 SCT : Ajout d'un septieme parametre optionnel : l'IP du serveur
    # 10/02/2011 SCT : Utilisation de l'ip facultative preselectionnee si renseignee
    if [ ! -z $preset_ip ] ; then
		server_ip_address=$preset_ip
    else
        # Mantis 2561: [DE MKT] Reverse Proxy - Prise en compte hostname dans les setups TA 
        # on propose syst&#65533;matiquement de saisir un servername...
        for uneIp in $listEth
        do
            add_log "Found several Ip address on server : $uneIp"
                server_ip_address_list=$server_ip_address_list" "$uneIp" "$uneIp" "$onOffEth
                onOffEth="off"
        done
        exec 3>&1
        server_ip_address=$($DIALOG --clear \
                --title "Select Application Ip address or servername" \
                --radiolist "Select application Ip address or servername (alias, hostname, fqdn...) below \n(if you don't know which one to choose, keep first IP in list [private IP])."  20 61 5 \
                $server_ip_address_list\
                "Enter server Ip address or servername (alias, hostname, fqdn...)"  "" OFF \
                2>&1 >&3 )
        exec 3>&-

        if [ $nbrEth -eq 0 ] || [ -z "$server_ip_address" ] || [ "$server_ip_address" = "Enter server IP address or servername (alias, hostname, fqdn...)" ]; then
            exec 3>&1
            server_ip_address=$($DIALOG --clear --title "Enter Application Ip address or servername" \
                --inputbox "Please enter the Ip address or servername (alias, hostname, fqdn...) of the application (IpV4 or name)." \
                 16 61 2>&1 >&3)
            exec 3>&-
        fi

    fi
    # 12/04/2011 SCT : ajout de l'information d'IP dans le log
    if [ -z "$preset_ip" ] ; then
        messageLog=$messageLog"Server Ip is set to "$server_ip_address"\n"
    fi
    # FIN 12/04/2011 SCT

	# DOES DATABASE ALREADY EXIST ?
	base_exists=`$''$psql_dir$''/psql -U postgres template1 -c "\l" | grep $'\ '$database$'\ ' | wc -l`

	# TEST OF DATA SOURCE DIRECTORY
	if [ ! -d $rep_source ] ; then
		echo '###################################################################################################'
		echo '## Error : File source directory '$rep_source' does not exist									   ##'
		echo '###################################################################################################'
		exit 127
	fi

	# TEST OF SOURCE TARBALL
	if [ ! -f $rep_source'/'$file_source ] ; then
		echo '###################################################################################################'
		echo '## Error : File source '$file_source' does not exist											   ##'
		echo '###################################################################################################'
		exit 127
	fi

	# LAUNCHING INSTALLATION ####################################################################################

       # GETTING CURRENT VERSION
       #current_version=`$psql_dir/psql -U postgres $database -c "SELECT item_value FROM sys_versioning WHERE item = 'cb_version' ORDER BY date DESC LIMIT 1" | head -n3 | tail -n1 | sed 's/ //g' | grep -E "[0-9].[0-9]..*"`

	# PATHS
	chemin_crontab='/var/spool/cron/'$user
	chemin_crontab_root='/var/spool/cron/root'

	# CREATING INSTALL DIR IF NOT EXISTS (IF NEW INSTALL)
	if [ ! -d $rep_install ] ; then
		mkdir -p $rep_install
	fi

	# COPYING AND EXTRACTING TARBALL INTO APPLICATION DIRECTORY
	# 2 TARBALL EXTRACTED IF NEW INSTALL (DB + FILES)
	echo '-> Extracting base component files'
	cd $rep_source
	cp -f $file_source $rep_install
	cd $rep_install
	tar xzf $file_source

	# EXTRACTING FILES IF SUBTARBALL EXISTS (NEW INSTALL)
	if [ -f "$rep_install/$cb_files" ] ; then
		tar xzf $cb_files
	fi

	# CREATING INSTALLATION LOG FILE
	rm -f $rep_install'/SQL/'$file_install_log
	touch $rep_install'/SQL/'$file_install_log
	chmod 777 $rep_install'/SQL/'$file_install_log

	# LOG TITLE
	echo "##################################################" >> $rep_install'/SQL/'$file_install_log 2>&1
	echo "#          T&A Base Component $cb_version_short Setup          #" >> $rep_install'/SQL/'$file_install_log 2>&1
	echo "##################################################" >> $rep_install'/SQL/'$file_install_log 2>&1
    # 12/04/2011 SCT : log des nouvelles informations
    echo -e $messageLog >> $rep_install'/SQL/'$file_install_log 2>&1
    # FIN 12/04/2011 SCT

    # EXTRACTING DOCS FILES INTO CB DOC DIRECTORY
    if [ -f $rep_source'/'$cb_doc ]; then
        if [ ! -d $rep_install'/doc' ] ; then
            mkdir -p $rep_install'/doc/'
        fi
        cp $rep_source'/'$cb_doc $rep_install'/doc/'
        cd $rep_install'/doc/'
        echo '-> Extracting doc files'
        tar xzf $cb_doc
        rm -f $cb_doc
    fi

	#############
	# XENV FILE #
	#############
	cd $rep_install/php/

	# CREATING NEW FILE IF NOT EXISTS
	if [ ! -f "xenv.inc" ] ; then
		touch xenv.inc
		chown $owner xenv.inc
		chmod 755 xenv.inc
	fi

	# PHP TAG
	echo "<?php" > xenv.inc
	# ALIAS
	echo '$niveau0="/'$alias'/";' >> xenv.inc
	# INSTALL DIR
	echo '$repertoire_physique_niveau0="'$rep_install'/";' >> xenv.inc
	# PG DIR
	echo '$psqldir = "'$psql_dir'/";' >> xenv.inc
	# TIME ZONE
	echo '$timezonesvr = "'$timezone_server'";' >> xenv.inc
	# PHP TAG
	echo "?>" >> xenv.inc

	#############
	# XBDD FILE #
	#############
	if [ ! -f xbdd.inc ] ; then
		echo "Error : cannot find xbdd.inc"
		echo '--------------------------------------------------' >> $rep_install'/SQL/'$file_install_log 2>&1
		echo "Error : cannot find xbdd.inc" >> $rep_install'/SQL/'$file_install_log 2>&1
		echo '--------------------------------------------------' >> $rep_install'/SQL/'$file_install_log 2>&1
		exit 127
	fi
	sed -i "s/nom_database/$database/g" xbdd.inc
    # 15/04/2011 SCT : update postgresql port
    # 11/05/2011 SCT : desactivation de la fonctionnalite temporairement
    #sed -i "s/5432/$postgresPort/g" xbdd.inc
    # FIN 15/04/2011 SCT
	# COPYING LOGO IMAGE IF NOT EXISTS
	cd $rep_install
	if [ ! -f $rep_install'/images/bandeau/logo_operateur.jpg' ] ; then
		mv $rep_install/images/bandeau/logo_operateur_default.jpg $rep_install/images/bandeau/logo_operateur.jpg
	fi

	# SETTING UP FILES OWNER
	cd $rep_install
	chown $owner . flat_file flat_file_upload_archive upload png_file report_files >> $rep_install'/SQL/'$file_install_log 2>&1
	find . -type d \( ! -name "flat_file_upload_archive" -a ! -name "."  -a ! -name "upload" -a ! -name "flat_file" \) | while read mydir
	do 
		chown -R $owner $mydir >> $rep_install'/SQL/'$file_install_log 2>&1
	done
	find . -maxdepth 1 -type f | while read myfile
	do
		chown $owner $myfile >> $rep_install'/SQL/'$file_install_log 2>&1
	done

	###############
	# NEW INSTALL #
	###############
	if [ $base_exists != "1" ] ; then

		# STOPPING PROCESSES
		/etc/init.d/crond stop
		/etc/init.d/apachectl stop
		killall httpd
		sleep 5

		# KILLING PROCESSES USING "template1" THEN CREATING DATABASE
		$psql_dir/psql -tc "select procpid from pg_stat_activity where datname='template1'" -U postgres | xargs -r kill && $psql_dir/createdb -U postgres -E 'SQL_ASCII' $database -T template1 >> $rep_install'/SQL/'$file_install_log 2>&1

		# IF CREATION FAILED, THROWING ERROR THEN EXIT
		db_is_created=`$''$psql_dir$''/psql -U postgres template1 -c "\l" | grep $'\ '$database$'\ ' | wc -l`
		if [ $db_is_created != "1" ] ; then
			# REMOVING APPLICATION DIRECTORY
			cd  $rep_source
			rm -Rf $rep_install
			# ERROR
			echo '--------------------------------------------------' >> $rep_install'/SQL/'$file_install_log 2>&1
			echo 'Error : database cannot be created. "template1" may be locked. Please try again in a few minutes.'
			echo 'Error : database cannot be created. "template1" may be locked. Please try again in a few minutes.' >> $rep_install'/SQL/'$file_install_log 2>&1
			echo '--------------------------------------------------' >> $rep_install'/SQL/'$file_install_log 2>&1
			# EXIT
			exit 127
		fi

		# MOUNTING ROOT DUMP INTO DATABSE
		cd  $rep_install
		echo '-> Mounting database '$database' from '$cb_bdd
		echo '-> Mounting database '$database' from '$cb_bdd >> $rep_install'/SQL/'$file_install_log 2>&1
		echo '##################### SQL ########################' >> $rep_install'/SQL/'$file_install_log 2>&1
        # 08/04/2011 SCT : ajout d'une commande TAIL pour supprimer le nom du fichier qui est lu en ligne 1
		zcat $cb_bdd | tail -n+2 | $psql_dir/psql -q  -U postgres $database >> $rep_install'/SQL/'$file_install_log 2>&1

		# UPGRADING PRODUCT INFORMATION
		echo "TRUNCATE TABLE sys_definition_product" | $psql_dir/psql -U postgres $database >> $rep_install'/SQL/'$file_install_log 2>&1
		rep_install_sans_home=${rep_install//\/home\//}
        # 07/04/2011 SCT : remplacement du port par defaut postgresql 5432 pour la variable $postgresPort
		echo "INSERT INTO sys_definition_product (sdp_id,sdp_label,sdp_ip_address,sdp_directory,sdp_db_name,sdp_db_port,sdp_db_login,sdp_on_off,sdp_master,sdp_master_topo,sdp_ssh_user,sdp_ssh_password,sdp_ssh_port) VALUES (1,'$alias','$server_ip_address','$rep_install_sans_home','$database','$postgresPort','postgres',1,1,1,'astellia','astellia',22)" | $psql_dir/psql -U postgres $database >> $rep_install'/SQL/'$file_install_log 2>&1
		echo '##################################################' >> $rep_install'/SQL/'$file_install_log 2>&1
		echo '-> Mounting database Done.' >> $rep_install'/SQL/'$file_install_log 2>&1
		echo '--------------------------------------------------' >> $rep_install'/SQL/'$file_install_log 2>&1

		# CREATING HTTP ALIAS
		echo '-> Creating application alias...'
		echo '-> Creating application alias...' >> $rep_install'/SQL/'$file_install_log 2>&1
		echo '<Ifmodule mod_alias.c>' >> /home/httpd/conf/httpd.conf
		echo '        Alias /'$alias' "'$rep_install'/"'  >> /home/httpd/conf/httpd.conf
		echo '        <Directory "'$rep_install'">'  >> /home/httpd/conf/httpd.conf
		echo '                AllowOverride None'  >> /home/httpd/conf/httpd.conf
		echo '                Order allow,deny'  >> /home/httpd/conf/httpd.conf
		echo '                Allow from all'  >> /home/httpd/conf/httpd.conf
		echo '        </Directory>'  >> /home/httpd/conf/httpd.conf
		echo '</IfModule>' >> /home/httpd/conf/httpd.conf
		echo '' >> /home/httpd/conf/httpd.conf
		echo '-> Done.' >> $rep_install'/SQL/'$file_install_log 2>&1
		echo '--------------------------------------------------' >> $rep_install'/SQL/'$file_install_log 2>&1

		# CREATING CRONS
		echo '-> Installing process crontab.'
		echo '-> Installing process crontab.' >> $rep_install'/SQL/'$file_install_log 2>&1

		# ADD
		if [ $cron_mode == "add" ] ; then
			if [ ! -f $chemin_crontab ]; then
				touch $chemin_crontab
			fi
		fi

		# RESET
		if [ $cron_mode == "reset" ] ; then
			echo '' > $chemin_crontab
		fi

		# WRITING CRONS
		echo '* * * * * '$php_dir'php -q '$rep_install'/scripts/lancement_scripts.php &> /dev/null' >> $chemin_crontab
		echo '0 0-23/2 * * * '$php_dir'php -q '$rep_install'/scripts/check_process_execution.php &> /dev/null' >> $chemin_crontab
		echo '0,30 12-23 * * 0 '$php_dir'php -q '$rep_install'/scripts/save_database.php &> /dev/null' >> $chemin_crontab
		echo "@midnight ${php_dir}php -q $rep_install/scripts/update_offset_day.php &> /dev/null" >> $chemin_crontab
		echo '' >> $chemin_crontab
		
		# 03/12/2013 GFS Bug 27986 - [SUP][T&A Gateway][Telus][AVP NA][crontab]: blank line are added in crontab file 
		# Supprime les lignes vides consecutives (ne laisse qu'une seule ligne vide)
		sed -i '/./,/^$/!d' $chemin_crontab

		# AFFECTING RIGTHS
		chown $user.$user $chemin_crontab
		chmod 600 $chemin_crontab
		echo '-> Done.' >> $rep_install'/SQL/'$file_install_log 2>&1

		# ROOT CRON
		echo '-> Installing kill_qpid crontab.'
		echo '-> Installing kill_qpid crontab.' >> $rep_install'/SQL/'$file_install_log 2>&1

		if [ ! -f $chemin_crontab_root ]; then
			touch $chemin_crontab_root
		fi

		# WRITING CRON
		echo '* * * * * '$php_dir'php -q '$rep_install'/scripts/kill_qpid.php &> /dev/null' >> $chemin_crontab_root
		echo '' >> $chemin_crontab_root

		# AFFECTING RIGTHS
		chown root.root $chemin_crontab_root
		chmod 600 $chemin_crontab_root

		echo '-> Done.' >> $rep_install'/SQL/'$file_install_log 2>&1
		echo '--------------------------------------------------' >> $rep_install'/SQL/'$file_install_log 2>&1

		# UPDATING APACHE LimitRequestBody PARAMETER
		echo '-> Updating Apache configuration...'
		echo '-> Updating Apache configuration...' >> $rep_install'/SQL/'$file_install_log 2>&1
		mv /home/httpd/conf/httpd.conf /home/httpd/conf/httpd_old.conf
		cat /home/httpd/conf/httpd_old.conf | awk '{sub("LimitRequestBody 2048000","LimitRequestBody 8388668");print;}' > /home/httpd/conf/httpd.conf
		rm -f /home/httpd/conf/httpd_old.conf
		echo '-> Done.' >> $rep_install'/SQL/'$file_install_log 2>&1
		echo '--------------------------------------------------' >> $rep_install'/SQL/'$file_install_log 2>&1

		# UPDATING PHP post_max_size PARAMETER
		echo '-> Updating PHP configuration...'
		echo '-> Updating PHP configuration...' >> $rep_install'/SQL/'$file_install_log 2>&1
		if [ -d /usr/local/Zend ] ; then
			mv /usr/local/Zend/etc/php.ini /usr/local/Zend/etc/php_old.ini
			cat /usr/local/Zend/etc/php_old.ini | awk '{sub("post_max_size = 8M","post_max_size = 10M");print;}' > /usr/local/Zend/etc/php.ini
			rm -f /usr/local/Zend/etc/php_old.ini
		fi

		# RESTARTING PROCESSES
		/etc/init.d/crond start
		/etc/init.d/apachectl start

		echo '-> Done.' >> $rep_install'/SQL/'$file_install_log 2>&1
		echo '--------------------------------------------------' >> $rep_install'/SQL/'$file_install_log 2>&1

	#####################
	# MIGRATION / PATCH #
	#####################
	else
		# GETTING CURRENT VERSION
		current_version=`$psql_dir/psql -U postgres $database -c "SELECT item_value FROM sys_versioning WHERE item = 'cb_version' ORDER BY date DESC LIMIT 1" | head -n3 | tail -n1 | sed 's/ //g' | grep -E "[0-9].[0-9]..*"`
		current_version_short=`echo $current_version | sed -r "s/^([0-9]+\.[0-9]+)\..*/\1/g"`
		
		# TESTING VERSION
		if [ -z $current_version ] ; then
			echo "Error : cannot read current version"
			echo "Error : cannot read current version" >> $rep_install'/SQL/'$file_install_log 2>&1
			echo '--------------------------------------------------' >> $rep_install'/SQL/'$file_install_log 2>&1
			exit 127
		fi

		# WAITING FOR INSTRUCTIONS
		is_migration_40=`echo $current_version | grep -E "^4.0" | wc -l`
		
		#####################################
		# MIGRATION FROM CB 4.0.X TO CB 5.0 #
		#####################################
		if [ $is_migration_40 == "1" ] ; then
			# STOPPING PROCESSES
			/etc/init.d/crond stop

			echo '################## MIGRATION #####################' >> $rep_install'/SQL/'$file_install_log 2>&1
			echo '-> Upgrading current version from 4.0 to 5.0 (4 steps) :'
			echo '-> Upgrading current version from 4.0 to 5.0 (4 steps) :' >> $rep_install'/SQL/'$file_install_log 2>&1
			echo '##################################################' >> $rep_install'/SQL/'$file_install_log 2>&1

			# TOPOLOGY UPGRADE
			echo '-> Starting Topology upgrade (step 1/4), this may take a few minutes.'
			echo '-> Starting Topology upgrade (step 1/4), this may take a few minutes.' >> $rep_install'/SQL/'$file_install_log 2>&1
			${php_dir}php -q $rep_install/SQL/migration_topology.php
			echo '-> Done. Topology is up to date.'
			echo '-> Done. Topology is up to date.' >> $rep_install'/SQL/'$file_install_log 2>&1
			echo '--------------------------------------------------' >> $rep_install'/SQL/'$file_install_log 2>&1

			# DATABASE UPGRADE (KEEP EXECUTION ORDER)
			echo '-> Starting Database upgrade (step 2/4).'
			echo '-> Starting Database upgrade (step 2/4).' >> $rep_install'/SQL/'$file_install_log 2>&1
			$psql_dir/psql -U postgres $database -f $rep_install/SQL/migrationGTMDashboardCB41.sql >> $rep_install'/SQL/'$file_install_log 2>&1
			$psql_dir/psql -U postgres $database -f $rep_install/SQL/message_display.sql >> $rep_install'/SQL/'$file_install_log 2>&1
			$psql_dir/psql -U postgres $database -f $rep_install/SQL/cb_v5.0.0.00.sql >> $rep_install'/SQL/'$file_install_log 2>&1
			echo '-> Done. Database is up to date.'
			echo '-> Done. Database is up to date.' >> $rep_install'/SQL/'$file_install_log 2>&1
			echo '--------------------------------------------------' >> $rep_install'/SQL/'$file_install_log 2>&1

			# MENUS UPGRADE
			echo '-> upgrading Menu entries (step 3/4).'
			echo '-> upgrading Menu entries (step 3/4).' >> $rep_install'/SQL/'$file_install_log 2>&1
			${php_dir}php -q $rep_install/SQL/update_menu.php >> $rep_install'/SQL/'$file_install_log 2>&1
			echo '-> Done. Menus are up to date.'
			echo '-> Done. Menus are up to date.' >> $rep_install'/SQL/'$file_install_log 2>&1
			echo '--------------------------------------------------' >> $rep_install'/SQL/'$file_install_log 2>&1

			# CLEANING DEPRECATED FILES
			echo '-> Deleting deprecated files (step 4/4).'
			echo '-> Deleting deprecated files (step 4/4).' >> $rep_install'/SQL/'$file_install_log 2>&1
			cd $rep_install
			cat $rep_install/SQL/files_to_delete.txt | xargs rm -rf
			echo '-> Done. Application is clean.'
			echo '-> Done. Application is clean.' >> $rep_install'/SQL/'$file_install_log 2>&1
			echo '--------------------------------------------------' >> $rep_install'/SQL/'$file_install_log 2>&1

			# UPGRADING PRODUCT INFORMATION
			echo "TRUNCATE TABLE sys_definition_product" | $psql_dir/psql -U postgres $database >> $rep_install'/SQL/'$file_install_log 2>&1
			rep_install_sans_home=${rep_install//\/home\//}
            # 11/05/2011 SCT : remplacement du port 5432 par la variable $postgresPort
			echo "INSERT INTO sys_definition_product (sdp_id,sdp_label,sdp_ip_address,sdp_directory,sdp_db_name,sdp_db_port,sdp_db_login,sdp_on_off,sdp_master,sdp_master_topo,sdp_ssh_user,sdp_ssh_password,sdp_ssh_port) VALUES (1,'$alias','$server_ip_address','$rep_install_sans_home','$database','$postgresPort','postgres',1,1,1,'astellia','astellia',22)" | $psql_dir/psql -U postgres $database >> $rep_install'/SQL/'$file_install_log 2>&1

			# ADDING OFFSET DAY CRON
			echo '-> Installing Offset day crontab.'
			echo '-> Installing Offset day crontab.' >> $rep_install'/SQL/'$file_install_log 2>&1
			croncmd="@midnight ${php_dir}php -q $rep_install/scripts/update_offset_day.php &> /dev/null"
			cronexists=$(cat $chemin_crontab | grep "$croncmd" | wc -l)
			if [ $cronexists == "0" ] ; then
				echo $croncmd >> $chemin_crontab
			fi
			echo '-> Done. Crontab is up to date.'
			echo '-> Done. Crontab is up to date.' >> $rep_install'/SQL/'$file_install_log 2>&1
			echo '--------------------------------------------------' >> $rep_install'/SQL/'$file_install_log 2>&1

			# RESTARTING PROCESSES
			/etc/init.d/crond start

			# OK FOR MIGRATING FROM CB 5.0 TO 5.1
			start_install=1
		else
			start_install=0
		fi

		
		# si on n'installe pas un CB 5.0,
		# il faut installer dblink
		if [ $is_cb50 != "1" ]; then			
			echo '--------------------------------------------------' >> $rep_install'/SQL/'$file_install_log 2>&1			
			echo '-> Installing Postresql "dblink" patch...'
			echo '-> Installing Postresql "dblink" patch...' >> $rep_install'/SQL/'$file_install_log 2>&1
			if [ -f $rep_install/modules/dblink/$file_install_dblink ] ; then
				echo '##################### SQL ########################' >> $rep_install'/SQL/'$file_install_log 2>&1
				dos2unix $rep_install/modules/dblink/$file_install_dblink
				sh $rep_install/modules/dblink/$file_install_dblink $rep_install $database >> $rep_install'/SQL/'$file_install_log 2>&1
				echo '##################################################' >> $rep_install'/SQL/'$file_install_log 2>&1
				echo '-> Done. "dblink" patch installed.'
				echo '-> Done. "dblink" patch installed.' >> $rep_install'/SQL/'$file_install_log 2>&1
			else
				echo '-> Failed. Cannot find installation script'
				echo '-> Failed. Cannot find installation script' >> $rep_install'/SQL/'$file_install_log 2>&1
			fi
			echo '--------------------------------------------------' >> $rep_install'/SQL/'$file_install_log 2>&1		
		fi		

		######################################################
		# FUNCTION patchCB				   
		# Use to patch CB in version 5.X.X
		# Parameter : 
		#	$cb_version_short : CB version (5.1, 5.1.p, 5.2 ...)
		######################################################
		patchCB() {							
			cb_version_short=$1
				 	
			cd $rep_install/SQL/$cb_version_short

			echo '################## MIGRATION #####################' >> $rep_install'/SQL/'$file_install_log 2>&1
			echo "-> Patching version $cb_version_short :"
			echo "-> Patching version $cb_version_short :" >> $rep_install'/SQL/'$file_install_log 2>&1
			echo '##################################################' >> $rep_install'/SQL/'$file_install_log 2>&1

			find . -regex ./[0-9]+_.* -type f | sort -t"_" -k1,1 | while read script
			do
				scriptname=`basename $script`
				EXTENSION=`echo $script | awk -F "." '{print $NF}'`
				case $EXTENSION in
					# FICHIER SQL
					sql)
                                            echo " -> Executing SQL $scriptname ..."
                                            echo " -> Executing SQL $scriptname ..." >> $rep_install'/SQL/'$file_install_log 2>&1
						echo '##################### SQL ########################' >> $rep_install'/SQL/'$file_install_log 2>&1
						cat $script | $psql_dir/psql -q  -U postgres $database >> $rep_install'/SQL/'$file_install_log 2>&1
						echo '##################################################' >> $rep_install'/SQL/'$file_install_log 2>&1
                                            echo " -> SQL $scriptname execution Done." >> $rep_install'/SQL/'$file_install_log 2>&1
                                            echo '--------------------------------------------------' >> $rep_install'/SQL/'$file_install_log 2>&1
					;;
					# FICHIER PHP
					php)
                                            echo " -> Executing PHP $scriptname ..."
                                            echo " -> Executing PHP $scriptname ..." >> $rep_install'/SQL/'$file_install_log 2>&1
						${php_dir}php -q $script >> $rep_install'/SQL/'$file_install_log 2>&1
                                            echo " -> PHP $scriptname execution Done." >> $rep_install'/SQL/'$file_install_log 2>&1
                                            echo '--------------------------------------------------' >> $rep_install'/SQL/'$file_install_log 2>&1
					;;
				esac
			done
		}		

		##########################################
		# 	MIGRATE & PATCH CB USING SQL FOLDER	 #
		##########################################
		
		# Boucle sur les repertoires contenu dans le dossier SQL et installe ceux qui sont necessaires
		cd $rep_install/SQL
		ls -d */ | sed 's,/$,,g' | while read version
		do
	        if [ $start_install = 0 ] ; then
	                start_install=`echo $version | grep ^$current_version_short | wc -l`
	        fi
	        if [ $start_install = 1 ] ; then
	                if [ $version != $current_version_short ] ; then
	                		# Installe le contenu du repertoire $version (5.1, 5.1.p ...)
	                        patchCB $version
	                else 
			        	# Le repertoire $version contient des patch pour une version anterieure ...on passe au suivant
			        	echo "-> Skipping directory $version"
						echo "-> Skipping directory $version" >> $rep_install'/SQL/'$file_install_log 2>&1
			        fi
	        else 
	        	# Le repertoire $version contient des patch pour une version anterieure ...on passe au suivant
	        	echo "-> Skipping directory $version"
				echo "-> Skipping directory $version" >> $rep_install'/SQL/'$file_install_log 2>&1
	        fi
		done

	fi

	#####################################################
	# IN ALL CASES RIGHTS ARE UPDATED TO AVOID PROBLEMS #
	#####################################################

	# UPDATING OWNER FOR ALL FILES
	cd $rep_install
	chown $owner . flat_file flat_file_upload_archive upload png_file report_files >> $rep_install'/SQL/'$file_install_log 2>&1
	find . -type d \( ! -name "flat_file_upload_archive" -a ! -name "."  -a ! -name "upload" -a ! -name "flat_file" \) | while read mydir
	do 
		chown -R $owner $mydir >> $rep_install'/SQL/'$file_install_log 2>&1
	done
	find . -maxdepth 1 -type f | while read myfile
	do
		chown $owner $myfile >> $rep_install'/SQL/'$file_install_log 2>&1
	done

	# SETTING ALL RIGHTS TO SOME FILES/DIRS
	cd $rep_install
	# RECURSIVE RIGHT UPDATE WITH NO CHECK (NOT NEEDED)
	chmod -R 777 reporting gis images parser >> $rep_install'/SQL/'$file_install_log 2>&1
	chmod 777 flat_file flat_file_upload_archive upload flat_file_zip
	# RECURSIVE RIGHT UPDATE WITH CHECK (NEEDED IN CASE OF HUGE AMOUNT OF FILES)
	for mydir in topology report_files png_file upload/context upload/export_files upload/export_files_corporate file_demon file_archive
	do
		current_rights=`ls -lad $mydir | tr -s " " ";" | cut -d ";" -f1 | cut -c2-`
		if [ "$current_rights" != "rwxrwxrwx" ] ; then
			chmod -R 777 $mydir >> $rep_install'/SQL/'$file_install_log 2>&1
		fi
	done

	# SETTING ALL RIGHTS TO WSDL FILE IF EXISTS
	if [ -f api/ta.wsdl ]; then
		chmod 777 api/ta.wsdl >> $rep_install'/SQL/'$file_install_log 2>&1
	fi
	if [ -f api/xpert/XpertApi.wsdl ]; then
		chmod 777 api/xpert/XpertApi.wsdl >> $rep_install'/SQL/'$file_install_log 2>&1
	fi
	
	# UPDATING RIGHTS TO PARTITIONING FILE
	if [ -f modules/partitioning/migration.pl ] ; then
		chmod 755 modules/partitioning/migration.pl >> $rep_install'/SQL/'$file_install_log 2>&1
	fi

	# REMOVING INSTALL FILES
	cd $rep_install
	rm -f $cb_files
	rm -f $file_source
	rm -f $cb_bdd

	# UPGRADING VERSION NUMBER
	echo '-> Updating current version number.'
	echo '-> Updating current version number.' >> $rep_install'/SQL/'$file_install_log 2>&1
	echo '##################### SQL ########################' >> $rep_install'/SQL/'$file_install_log 2>&1
	version_exists=`$psql_dir/psql -U postgres $database -c "SELECT id FROM sys_versioning WHERE item = 'cb_version' AND item_value = '$version_cb' AND item_mode = 'version_de_base'" | head -n3 | tail -n1 | grep "|" | wc -l`
	if [ $version_exists == "0" ] ; then
		echo "INSERT INTO sys_versioning (item,item_value,item_mode,date) values ('cb_name','$name_cb','version_de_base','$date')" | $psql_dir/psql -U postgres $database >> $rep_install'/SQL/'$file_install_log 2>&1
		echo "INSERT INTO sys_versioning (item,item_value,item_mode,date) values ('cb_version','$version_cb','version_de_base','$date')" | $psql_dir/psql -U postgres $database >> $rep_install'/SQL/'$file_install_log 2>&1
	fi
	echo '##################################################' >> $rep_install'/SQL/'$file_install_log 2>&1
	echo '-> Done.' >> $rep_install'/SQL/'$file_install_log 2>&1
	echo '--------------------------------------------------' >> $rep_install'/SQL/'$file_install_log 2>&1

	# si on n'installe pas un CB 5.0,
	# il faut installer les macros OO et la division par 0
	if [ $is_cb50 != "1" ]; then
		# INSTALLING OPENOFFICE MACROS
		echo '-> Installing OpenOffice macros...'
		echo '-> Installing OpenOffice macros...' >> $rep_install'/SQL/'$file_install_log 2>&1
		if [ -f $rep_install/modules/OpenOffice/macro/$file_install_macro ] ; then
			echo '##################### SETUP #######################' >> $rep_install'/SQL/'$file_install_log 2>&1
			dos2unix $rep_install/modules/OpenOffice/macro/$file_install_macro
			sh $rep_install/modules/OpenOffice/macro/$file_install_macro $rep_install/modules/OpenOffice/macro/ >> $rep_install'/SQL/'$file_install_log 2>&1
			echo '##################################################' >> $rep_install'/SQL/'$file_install_log 2>&1
			echo '-> Done.'
			echo '-> Done.' >> $rep_install'/SQL/'$file_install_log 2>&1
		else
			echo '-> Failed. Cannot find installation script'
			echo '-> Failed. Cannot find installation script' >> $rep_install'/SQL/'$file_install_log 2>&1
		fi
		echo '--------------------------------------------------' >> $rep_install'/SQL/'$file_install_log 2>&1

		echo '-> Installing Postresql "division by zero" patch...'
		echo '-> Installing Postresql "division by zero" patch...' >> $rep_install'/SQL/'$file_install_log 2>&1
		if [ -f $rep_install/modules/divparzero/$file_install_divparzero ] ; then
			echo '##################### SQL ########################' >> $rep_install'/SQL/'$file_install_log 2>&1
			dos2unix $rep_install/modules/divparzero/$file_install_divparzero
			sh $rep_install/modules/divparzero/$file_install_divparzero $rep_install $database >> $rep_install'/SQL/'$file_install_log 2>&1
			echo '##################################################' >> $rep_install'/SQL/'$file_install_log 2>&1
			echo '-> Done. "division by zero" patch installed.'
			echo '-> Done. "division by zero" patch installed.' >> $rep_install'/SQL/'$file_install_log 2>&1
		else
			echo '-> Failed. Cannot find installation script'
			echo '-> Failed. Cannot find installation script' >> $rep_install'/SQL/'$file_install_log 2>&1
		fi
		echo '--------------------------------------------------' >> $rep_install'/SQL/'$file_install_log 2>&1
		
	fi

	# UPDATE SYS_DEBUG
	echo "UPDATE sys_debug SET value=0" | $psql_dir/psql -U postgres $database >> $rep_install'/SQL/'$file_install_log 2>&1

	
	# UPDATING CRON PATH
	echo '-> Updating cron path...'
	echo '-> Updating cron path...' >> $rep_install'/SQL/'$file_install_log 2>&1

	# STOPPING CRONS
	/etc/init.d/crond stop

	# PATH
	is_path_defined=`cat $chemin_crontab | grep -E "^PATH=" | wc -l`
	if [ $is_path_defined != "0" ] ; then
		sed -i "/^PATH=/d" $chemin_crontab
	fi
	echo "PATH=$cron_path" > /tmp/crontmp
	cat $chemin_crontab >> /tmp/crontmp
	mv /tmp/crontmp $chemin_crontab
	chown $user.$user $chemin_crontab
	chmod 600 $chemin_crontab

	# ROOT
	is_path_defined=`cat $chemin_crontab_root | grep -E "^PATH=" | wc -l`
	if [ $is_path_defined != "0" ] ; then
		sed -i "/^PATH=/d" $chemin_crontab_root
	fi
	echo "PATH=$cron_path" > /tmp/crontmp
	cat $chemin_crontab_root >> /tmp/crontmp
	mv /tmp/crontmp $chemin_crontab_root
	chown root.root $chemin_crontab_root
	chmod 600 $chemin_crontab_root

	# STARTING CRONS
	/etc/init.d/crond start

	echo '-> Done...'
	echo '-> Done...' >> $rep_install'/SQL/'$file_install_log 2>&1
	echo '--------------------------------------------------' >> $rep_install'/SQL/'$file_install_log 2>&1

	# ALL DONE
	echo '#############################################################################################################'
	echo '## Base Component installation done, installation log file :'$rep_install'/SQL/'$file_install_log
	echo '#############################################################################################################'

	#############
	# MIXED KPI #
	#############
	if [ $base_exists == "1" ] ; then
		# 01/2/2011 MMT bz 20452 patcher un Slave ne doit pas patcher egalement le Mixed KPI
		is_master=`$psql_dir/psql -U postgres $database -c "SELECT sdp_directory, sdp_db_name FROM sys_definition_product WHERE sdp_db_name = '$database' AND sdp_master = 1 " | head -n3 | tail -n1 | grep "|" | wc -l`
		is_mk=`$psql_dir/psql -U postgres $database -c "SELECT sdp_directory, sdp_db_name FROM sys_definition_product WHERE sdp_db_name LIKE 'mixed_kpi%'" | head -n3 | tail -n1 | grep "|" | wc -l`
		# IF MIXED KPI PRODUCT EXISTS
		if [[ $is_mk == "1" && $is_master == "1" ]] ; then
			# FETCHING DIR AND DB NAME
			mk_infos=`$psql_dir/psql -U postgres $database -c "SELECT sdp_directory, sdp_db_name FROM sys_definition_product WHERE sdp_db_name LIKE 'mixed_kpi%'" | head -n3 | tail -n1 | grep "|" | sed "s/ //g"`
			mk_dir=`echo $mk_infos | cut -d"|" -f1`
			mk_db=`echo $mk_infos | cut -d"|" -f2`
			# IF NOT ALREADY PATCHING THE MIXED KPI PRODUCT
			if [ $mk_db != $database ] ; then

				echo '################## MIXED KPI #####################' >> $rep_install'/SQL/'$file_install_log 2>&1
				echo '-> Patching Mixed KPI product...'
				echo '-> Patching Mixed KPI product...' >> $rep_install'/SQL/'$file_install_log 2>&1
				echo '##################################################' >> $rep_install'/SQL/'$file_install_log 2>&1

				# EXECUTING INSTALL SCRIPT FOR MIXED KPI PRODUCT
                # 11/04/2011 SCT : l'IP reprend la position de 7eme parametre
				sh $BASE/$file_install_script /home/$mk_dir/ $mk_db $mk_db $rep_source $user add $server_ip_address $postgresPort
				echo "-> Done. See /home/$mk_dir/SQL/$file_install_log to get installation details."
				echo "-> Done. See /home/$mk_dir/SQL/$file_install_log to get installation details." >> $rep_install'/SQL/'$file_install_log 2>&1
				echo '--------------------------------------------------' >> $rep_install'/SQL/'$file_install_log 2>&1
			fi
		fi
	fi

	# LOG FOOTER
	echo "##################################################" >> $rep_install'/SQL/'$file_install_log 2>&1
	echo "#      T&A Base Component $cb_version_short Installed          #" >> $rep_install'/SQL/'$file_install_log 2>&1
	echo "##################################################" >> $rep_install'/SQL/'$file_install_log 2>&1

else
	echo 'you must be root to run this script'
fi
