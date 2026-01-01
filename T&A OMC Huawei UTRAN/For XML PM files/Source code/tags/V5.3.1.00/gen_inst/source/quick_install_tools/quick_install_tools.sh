#!/bin/bash

# ==== Definition des fonctions ====

# Add message into logfile
# $1 : message
# $2 : severity (optional) info, title, big_title
add_log() {
	[ $# -lt 1 ] && return
	msg_severity="info"
	[ $# -eq 2 ] && msg_severity=$2
    
    message=$1
    if [ "$2" = "title" ] || [ "$2" = "big_title" ] ; then
        message="### "$message" ###"
    fi

    if [ "$2" = "big_title" ] ; then
        bigTitleMessage=""
        for i in $(seq 1 ${#message})
        do
            bigTitleMessage=$bigTitleMessage"#"
        done
        echo $bigTitleMessage >> $LOG 2>&1
    fi
    echo $message >> $LOG 2>&1
    [ "$2" = "big_title" ] && echo $bigTitleMessage >> $LOG 2>&1
}

# Display message in window
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

# Delete all deflated directory created during script execution
clean_dirs() {
	for dir in cb parser context doc homepage scripts quick_install_tools
	do
		[ -d $WORKING_DIR/$dir ] && rm -rf $WORKING_DIR/$dir
	done
}

# Delete all deflated directory created during script execution and abord execution of installation script
clean_exit() {
	clean_dirs
    # 10:38 06/09/2011 SCT : adding message when exiting script
    txtRed='\e[0;31m'
    txtNormal='\033[0m'
    echo -e "Script log is available here :${txtRed} ${LOG} ${txtNormal}"
	exit
}

# Verifie que la chaine passe en parametre est correct
check_appname() {
	str="$1"
	wrong="/ :~&#{}()[]|$%,;:!?"
	l=${#wrong}
	ll=${#str}
	for ((i = 0; i < $l; i++)); do
		for ((j = 0; j < $l; j++)); do
			if [ "${wrong:$i:1}" == "${str:$j:1}" ]
			then
                add_log "Incorrect name '$str', don't use '${wrong:$i:1}'."
				show_msg "Incorrect name '$str', don't use '${wrong:$i:1}'."
				clean_exit
			fi
		done
	done
	if [ -z "$str" ]
	then
		add_log "Incorrect name. You can relaunch this script now."
        show_msg "Incorrect name. You can relaunch this script now."
		clean_exit
	fi
	res=$(echo ${str} | awk '/^[0-9]/{print 1}')
	if [ "$res" == "1" ]
	then
        add_log "Incorrect name '$str', name can't start with a number."
        show_msg "Incorrect name '$str', name can't start with a number."
		clean_exit
	fi
	return 0
}

# 14/04/2011 SCT : check if postgresql connection is valid
check_postgresql_connection() {
    # 14/04/2011 SCT : let's check default postgresql port is OK
    resultTestConnectionPostgresql=$($psql_bin -U postgres -p $postgresqlPort -L $LOG -d template1 -tAc "SELECT pg_database_size('template1');" 2>&1) >> $LOG
    # Check resultat is integer (if not : postgresql port is asked to user)
    connectionState=0
    if [ "$(echo $resultTestConnectionPostgresql | grep "^[ [:digit:] ]*$")" ] ; then
        connectionState=1
    fi
    return 0
}
# FIN 14/04/2011 SCT

# appelle clean_exit si un process est en cours d'execution sur l'appli courante
exit_if_process_running() {
	[ -d "/home/$appname" ] || return # ce n'est pas un cas de migration ? alors rien a verifier
	process=$($psql_bin -U postgres -d $dbname -p $postgresqlPort -tAc "SELECT master_name FROM sys_process_encours, sys_definition_master WHERE encours = 1 AND process::text = master_id::text LIMIT 1" 2>>$LOG)
	if [ -n "$process" ]
	then
		cat <<EOF
A '$process' process is currently executing for $appname.
Please wait to allow it to finish, and relaunch this script.
EOF
        add_log "A '$process' process is currently executing for $appname. Please wait to allow it to finish, and relaunch this script."
		clean_exit
	fi
}

# backup et desactive les process actives qui pourraient etre lances en crontab
# ou restaure leur etat a celui d'avant l'appel du script.
# De la meme facon, on backup/restore l'eventuelle personnalisation (utps et offset_time)
# realisee par le client.
sysdefmaster() {
	[ -d "/home/$appname" ] || return # ce n'est pas un cas de migration ou de patch ? alors rien a verifier
	if [ "$1" = backup ] ; then
		# /usr/local/pgsql/bin/psql -U postgres -d $dbname -p $postgresqlPort -tAc "select master_id from sys_definition_master where on_off = 1 and visible = 1" > "$sdf_bkp_file" 2>>$LOG
		$psql_bin -U postgres -d $dbname -p $postgresqlPort -tAc "select master_id, utps, offset_time, on_off from sys_definition_master where on_off = 1 and visible = 1" > "$sdm_bkp_file" 2>>$LOG
		$psql_bin -U postgres -d $dbname -p $postgresqlPort -tAc "select master_id, utps, offset_time, on_off from sys_definition_master_ref where on_off = 1 and visible = 1" > "$sdmr_bkp_file" 2>>$LOG
		$psql_bin -U postgres -d $dbname -p $postgresqlPort -atAc "update sys_definition_master set on_off = 0 where on_off = 1 and visible = 1" >> $LOG 2>&1
	elif [ "$1" = restore ] ; then
		if test -r "$sdm_bkp_file" -a -s "$sdm_bkp_file"; then
			cat "$sdm_bkp_file" | awk -F '|' '{printf("UPDATE sys_definition_master SET utps = %s, offset_time = %s, on_off = %s WHERE master_id = \047%s\047;\n", $2, $3, $4, $1);}' | $psql_bin -U postgres -d $dbname >> $LOG 2>&1
		fi
		if test -r "$sdmr_bkp_file" -a -s "$sdmr_bkp_file"; then
			cat "$sdmr_bkp_file" | awk -F '|' '{printf("UPDATE sys_definition_master_ref SET utps = %s, offset_time = %s, on_off = %s WHERE master_id = \047%s\047;\n", $2, $3, $4, $1);}' | $psql_bin -U postgres -d $dbname >> $LOG 2>&1
		fi
		#list=$(tr "\n" , < "$sdf_bkp_file" | sed -re 's/,$//')
		#$psql_bin -U postgres -d $dbname -p $postgresqlPort -atAc "update sys_definition_master set on_off = 1 where master_id in ($list)" >> $LOG 2>&1
		rm -f "$sdm_bkp_file"
		rm -f "$sdmr_bkp_file"
	else
		add_log "sysdefmaster: parametre incorrect ($1)"
		clean_exit
	fi
}

# backup de la colonne on_off de sys_field_reference
sysfieldreference() {
	if [ "$1" = backup ] ; then
		$psql_bin -U postgres -d $dbname -p $postgresqlPort -tAc "SELECT id_ligne, on_off FROM sys_field_reference" > "$sfr_bkp_file" 2>>$LOG
	elif [ "$1" = restore ] ; then
		test -r "$sfr_bkp_file" || return
		test -s "$sfr_bkp_file" || return
		chmod 777 "$sfr_bkp_file"
		#/usr/local/pgsql/bin/psql -U postgres -d $dbname -p $postgresqlPort -atAc "COPY sys_field_reference (id_ligne,on_off) FROM '$sfr_bkp_file' WITH DELIMITER '|'" >> $LOG 2>&1
		cat "$sfr_bkp_file" | awk -F '|' '{if($2==1)printf("UPDATE sys_field_reference SET on_off = %s WHERE id_ligne = \047%s\047;\n", $2, $1);}' | /usr/local/pgsql/bin/psql -U postgres -d $dbname >> $LOG 2>&1
		rm -f "$sfr_bkp_file"
	else
		add_log "sysfieldreference: parametre incorrect ($1)"
		clean_exit $package_directory_temp
	fi
}

# Execute le script passe en parametre qui doit se trouver
# dans $WORKING_DIR/scripts/nom_du_script
launch_prepost_script() {
	if [ $# -ne 1 ]
	then
		add_log "Function 'launch_prepost_script' needs a parameter"
		return
	fi
	script="$WORKING_DIR/scripts/$1"
	if [ -f "$script" ]
	then
        add_log "Execution of '$script'" "title"
        # 15/04/2011 SCT : add dos2unix on script
        dos2unix $script >> $LOG 2>&1
		# On "source" plutot qu'execute ("sh"), cela permet de profiter
		# de toutes les variables (du script parent et du script enfant)
		source $script
        add_log "...done"
	fi
}

# Installe le CB de BASE
install_cb_base() {
	launch_prepost_script "pre_cb_base.sh"

	# Installation du CB initial
	add_log "Installation of Base Component" "big_title"
	
	cd $WORKING_DIR
	dos2unix -q cb/${cb_dir}/${cb_shell} >> $LOG 2>&1
	cd cb/$cb_dir
	# [application_path] [database_name] [alias] [source_path] [user] [add/reset crontab]
	# 15/11/2010 NSE bz 19142 : remplacement du . par le chemin absolu
	absolute_path=`pwd`
    # 23/03/2011 SCT BZ 20928 : ajout de l'IP en 7ème paramètre de script d'install CB
    # 07/04/2011 SCT : ajout d'un nouveau paramètre port postgresql en 7ème paramètre (décalage du 7ème paramètre existant)
    # 11/04/2011 SCT : décalage du 7ème paramètre vers la 8ème position => remise en place de la position de l'IP en 7ème position
	sh $cb_shell "/home/$appname" "$dbname" "$appname" "$absolute_path" "astellia" "add" "$server_ip_address" "$postgresqlPort" >> $LOG 2>&1  

	launch_prepost_script "post_cb_base.sh"
}

# Installe le parser (sans le contexte)
install_parser() {
	launch_prepost_script "pre_parser.sh"

	# Installation du parser
    add_log "Installation of Parser" "big_title"
	
	cd $WORKING_DIR
	dos2unix -q parser/${parser_dir}/${parser_shell} >> $LOG 2>&1
	cd parser/${parser_dir}
	# [application_path] [database_name] [source_path] [user] [context_path]
	# 15/11/2010 NSE : remplacement du . par le chemin absolu
	absolute_path=`pwd`
	sh $parser_shell "/home/$appname" "$dbname" "$absolute_path" "astellia" >> $LOG 2>&1 

	launch_prepost_script "post_parser.sh"
}

# Installe le contexte
install_context() {
	launch_prepost_script "pre_context.sh"

	# Installation du parser
	add_log "Installation of Context" "big_title"
	
	cd $WORKING_DIR
	dos2unix -q parser/${parser_dir}/${parser_shell} >> $LOG 2>&1
	cd parser/${parser_dir}
	# param [context_path] [archive:true/false]
	php /home/$appname/context/php/context_install_sh.php $ctx_dir/$ctx_archive true >> $LOG 2>&1

	launch_prepost_script "post_context.sh"
}

# Installe le patch du CB
install_cb_patch() {
	launch_prepost_script "pre_cb_patch.sh"

	# Installation eventuelle d'un patch CB
	cd $WORKING_DIR
	if [ -n "$cbp_ver" ]
	then
		add_log "Installation of Base Component Patch" "big_title"
		dos2unix -q cb/${cbp_dir}/${cbp_shell}  >> $LOG 2>&1
		cd cb/$cbp_dir
		sh $cbp_shell "/home/$appname" "$dbname" . "astellia" >> $LOG 2>&1  
	fi

	launch_prepost_script "post_cb_patch.sh"
}
