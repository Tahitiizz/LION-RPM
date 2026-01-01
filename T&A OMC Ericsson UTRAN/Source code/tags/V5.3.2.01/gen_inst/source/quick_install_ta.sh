#!/bin/bash

# ==== START AUTOMATIC MODIFICATION  ====

archive="@@ARCHIVE@@"
parser_name="@@PARSER_NAME@@"
default_appname="@@DEFAULT_APPNAME@@"
product_name="@@PRODUCT_NAME@@"
product_label="@@PRODUCT_LABEL@@"
product_version="@@PRODUCT_VERSION@@"
pkgmd5="@@PKGMD5@@"
type_install="@@TYPE_INSTALL@@"
install_order="@@INSTALL_ORDER@@"
gen_inst_version="@@GEN_INST_VERSION@@" # 11/02/2011 11:05 SCT : ajout de la version du gen_inst utilisé pour faire le package produit dans le fichier de log
min_previous_product_version="@@MIN_PREVIOUS_PRODUCT_VERSION@@" # version minimale requise pour installation, optionnel

# ==== END AUTOMATIC MODIFICATION ====

# ==== VARS DEFINE ====
# 14/04/2011 SCT : define postgresql default port
postgresqlDefaultPort="5432"
allowDifferentPostgresqlPort=0
# 30/06/2011 17:48 SCT => add datetime in logfilename to keep old logs
timestampIntall=$(date +%Y_%m_%d_%H_%M)
timestampIntallLog=$(date +%Y.%m.%d-%H.%M)
# 09:14 01/07/2011 SCT : gestion de la version de Red Hat
allowScriptToCtrlRedHatVersion=1
allowByPassRedHatNonAvailable=0
# 09:49 01/07/2011 SCT : mise en variable du nom du fichier DO NOT CUSTOMIZE
scriptName=$0
# 12:09 01/07/2011 SCT : Bypass sur la vérification du MD5
forceMD5ByPass=0
# 17:16 04/07/2011 SCT : Allow user to change selected network aggregation into homepage feature
allowUserCustomizeHomepage=0
# 16:10 02/09/2011 MMT : Allow user to select authentication mode via GUI
# 27/02/2012 NSE DE Astellia Portal Lot2
# 0 is not allowed with 5.2.0.x  allowUserSelectAuthentication=1

# root ?
if [ "$(id -u)" != "0" ]; then
	MSG="Usage: sh $0 [appname dbname key]

You must be root to run this script"
	echo "$MSG" "Error"
	exit
fi

# 16:46 05/07/2011 SCT : adding quick_install_tools extraction from archive
if [ -f $archive ] ; then
    tar xzf $archive quick_install_tools
else
	MSG="Unable to find $archive\n
If the sources are not copied on the T&A server:\n
  * Insert the installation CD-ROM in the CD drive\n
  * Launch an other session with root account in order to run the following command:\n
    $ mount /cdrom\n
  * Copy the followings directories in /home/ta_install\n
    $ cp -R /cdrom/sources/$archive /home/ta_install/"
    echo $MSG
    exit
fi
source quick_install_tools/quick_install_tools.sh

# quand on quitte le script, quelle que soit la raison (a part SIGKILL evidemment)
# on restaure l'etat de sysdefmaster, si toutefois l'etat a ete modifie
sdf_bkp_file=$(mktemp)
sfr_bkp_file=$(mktemp)
sdm_bkp_file=$(mktemp)
sdmr_bkp_file=$(mktemp)
trap "sysdefmaster restore ; sysfieldreference restore ; rm -f $sdf_bkp_file ; rm -f $sfr_bkp_file ;  rm -f $sdm_bkp_file ; rm -f $sdmr_bkp_file" EXIT

# Psql Bin
psql_bin="/usr/local/pgsql/bin/psql"
if [ ! -f "/usr/local/pgsql/bin/psql" ] ; then
	psql_bin="/usr/bin/psql"
fi

PSQL="$psql_bin -U postgres -tAc "
WORKING_DIR=$PWD

appname=""
dbname=""
key=""
# 14/04/2011 SCT : modification de la gestion du port postgresql
postgresqlPort=""
# 30/06/2011 17:48 SCT => add datetime in logfilename to keep old logs
# Initialisation du fichier de log
LOG=$WORKING_DIR/log_install_ta_${product_name}_${product_version}_${timestampIntallLog}.log
cat /dev/null > $LOG

# 11/02/2011 11:05 SCT : ajout de la version du gen_inst utilisé pour faire le package produit dans le fichier de log
add_log "GEN_INST version used for packaging product : $gen_inst_version" "big_title"

DIALOG=gdialog
[ -z $DISPLAY ] && DIALOG=dialog

# QUIET
if [ -n "$QUIET" ]; then
	QUIET="true"
else
	QUIET="false"
fi

# ==== Tests preliminaires ====
add_log "Preliminary tests" "title"
# Si des parametres sont passes
# 14/04/2011 SCT : modification de la gestion du port postgresql
if [ $# -eq 4 ] || [ $# -eq 3 ] ; then
	appname="$1"
	dbname="$2"
	key="$3"
    # 14/04/2011 SCT : Postgresql as script parameter
	postgresqlPort="$4"
fi
# 14/04/2011 SCT : in case of postgresqlPort not defined, take the default value
if [ -z "$postgresqlPort" ] ; then
    postgresqlPort=$postgresqlDefaultPort
fi
# 14/04/2011 SCT : redefine $PSQL
PSQL="$psql_bin -U postgres -p $postgresqlPort -tAc "

# on doit etre dans /home/ta_install
if [ "$PWD" != "/home/ta_install" ]; then
	MSG="This script must be launched from the /home/ta_install directory: cd /home/ta_install\n
Then, execute this script again.\n
Aborting installation.\n"
    # 10:30 12/09/2011 SCT : Message con't be read when installation package is not in "/home/ta_install" directory
    show_msg "$MSG"
    add_log "Script must be launched from directory '/home/ta_install'"
	clean_exit
fi

# verification de la signature MD5
# 12:09 01/07/2011 SCT : Bypass sur la vérification du MD5
forceMD5ByPass=0
if [ $forceMD5ByPass = 0 ] ; then
    computedmd5=$(md5sum $archive | awk '{print $1}')
    if [ "$computedmd5" != "$pkgmd5" ]; then
        MSG="The MD5 signature of the package ($computedmd5) doesn't match the expected value ($pkgmd5).\n
This means that the archive ($archive) is CORRUPTED.\n
Please re-transfer the archive ($archive).\n
Aborting installation."
        show_msg "$MSG" "Error"
        add_log "$MSG"
        clean_exit
    fi
else
    add_log "MD5 check feature disabled by user"
fi
add_log "...done"

# verification de l'integrite de l'archive (en plus d'md5...)
add_log "Unzip archive ($archive)" "title"
if ! tar tzf $archive >> $LOG 2>&1; then
	MSG="The archive ($archive) doesn't pass the tar extraction test, which means it is CORRUPTED.\n
Please re-transfer the archive ($archive).\n
Aborting installation.\n"
    show_msg "$MSG"
    add_log "$MSG"
	clean_exit
fi
add_log "...done"

# 09:18 01/07/2011 SCT : check Red Hat Release version before installation
add_log "Server tests" "title"

# check version of packaged CB
cbUnderVersion=1
for ligne in $(tar -tzf $archive)
do
    if [ "${ligne:0:3}" = "cb/" ] && [ "${ligne: -3}" = ".sh" ] && [ $cbUnderVersion = 1 ] ; then
        ligne=$(echo $ligne | awk -F '/' '{print $2}')
        cbVersion=${ligne#*_v}
        cbUnderVersion=$(echo $cbVersion | awk -F'.' '{if (($1 == "5" && (($2 == "0" && ($3 >= "7" || ($3 == "6" && $4 >= "04"))) || ($2 == "1" && $3 >= "4") || ($2 >= "2"))) || ($1 >= "6")) {print "0"} else {print "1"}}')
    fi
done

if [ $allowScriptToCtrlRedHatVersion -eq 1 ]
then
    # Mantis 4700 : [Hardware-HP] - Support HP Gen8 V2
    is_rhel=`grep -Ec "4|5.5|6.2|6.5" /etc/redhat-release`
    is_rhel55=`grep -Ec "5.5" /etc/redhat-release`
    is_rhel62=`grep -Ec "6.2" /etc/redhat-release`
    is_rhel65=`grep -Ec "6.5" /etc/redhat-release`
    rhVersion=`awk -F'release ' '{print $2}' /etc/redhat-release`
    add_log "Check Red Hat version" "title"
	cbMajorVersion=${cbVersion:0:3}
	
	if [ -z "$rhVersion" ] ; then
		rhVersion="undetermined"
	fi

	#
	# Any CB but not a compatible RedHat
	#	
	if [ "$is_rhel" != "1" ]
	then	
        add_log "Red Hat version is not one of the expected"
        # Quiet Mode AND CB is not higher enough
        if [ $QUIET = "true" ] && [ $cbUnderVersion -eq 1 ]; then
            MSG="Rehat version is RH $rhVersion, this version is not in recommended version.(RH 4.8, RH 5.X)."
            MSG="$MSG\nWe strongly advise you to upgrade your version of Red Hat before installation T&A."
            cat $MSG
            add_log "$MSG"
            clean_exit
        # Quiet Mode BUT CB is higher enough
        elif [ $QUIET = "true" ] && [ $cbUnderVersion -eq 0 ]; then
            MSG="Rehat version is RH $rhVersion, this version is not in recommended version.(RH 4.8, RH 5.X).."
            MSG="$MSG\nWe strongly advise you to upgrade your red Hat version."
            MSG="$MSG\nCB version is higher enougth to be installed on this server."
            cat $MSG
            add_log "$MSG"
        # VERBOSE Mode
        else
            MSG="Rehat version is RH $rhVersion, this version is not in recommended version.(RH 4.8, RH 5.X)."
            MSG="$MSG\nWe strongly advise you to upgrade your Red Hat version."
            # Quick Install is set to NOT override control AND CB is not higher enough
            if [ $allowByPassRedHatNonAvailable -eq 0 ] && [ $cbUnderVersion -eq 1 ]; then
                add_log "Quick Install is set to quit product installation. Please upgrade Red Hat version."
                show_msg "$MSG" "Warning"
                clean_exit
            # Quick Install is set to NOT override control BUT CB is higher enough
            elif [ $allowByPassRedHatNonAvailable -eq 0 ] && [ $cbUnderVersion -eq 0 ]; then
                add_log "Server has not right version but CB can be installed on it."
                MSG="$MSG\nCB version is higher enougth to be installed on this server."
                show_msg "$MSG" "Warning"
            # Quick Install is set to override control : user can choose to install anyway
            else
                MSG="$MSG\nDo you want to abort T&A installation ?"
                $DIALOG --title "Warning" --clear --yesno "$MSG" 0 0
                resDial=$?
                if [ $resDial -eq 0 ]; then
                    add_log "User chooses to stop installation as version of Red Hat is not expected one"
                    clean_exit
                fi
                add_log "User chooses to continue installation without the expected version of Red Hat"
            fi
		fi
	#
	# CB version is 5.2 but RedHat version is not compatible
	#
	elif [ "$cbMajorVersion" == "5.2" ] && [ "$is_rhel55" != "1" ]
	then
		MSG="This Operating system ($rhVersion) is not compatible with base component $cbVersion."
		MSG="$MSG\nCB $cbVersion should only be installed on a RedHat 5.5 64bits server."
		# Installation stopped
		if [ $allowByPassRedHatNonAvailable -eq 0 ]
		then
			add_log "Quick Install is set to quit product installation. Please upgrade Red Hat version."
			if [ "$QUIET" != "true" ] ; then
				show_msg "$MSG" "Warning"
			fi
			clean_exit
		# User is asked
		elif [ "$QUIET" != "true" ]
		then
			MSG="$MSG\nDo you want to abort T&A installation ?"
			$DIALOG --title "Warning" --clear --yesno "$MSG" 0 0
			resDial=$?
			if [ $resDial -eq 0 ]; then
				add_log "User chooses to stop installation as version of Red Hat is not expected one"
				clean_exit
			fi
			add_log "User chooses to continue installation without the expected version of Red Hat"
		# Quiet mode
		else
			add_log "Operating system not compatible ($rhVersion) however Quick Install is set to continue installation."
		fi
	#
	# CB version is 5.3 but RedHat version is not compatible
	# Mantis 4700 : [Hardware-HP] - Support HP Gen8 V2
	elif  [ "$cbMajorVersion" == "5.3" ] && [ "$is_rhel62" != "1" ] && [ "$is_rhel65" != "1" ] && [ "$is_rhel55" != "1" ]
	then
		MSG="This Operating system ($rhVersion) is not compatible with base component $cbVersion."
		MSG="$MSG\nCB $cbVersion should only be installed on a RedHat 5.5/6.2 64bits server."
		# Installation stopped
		if [ $allowByPassRedHatNonAvailable -eq 0 ]
		then
			add_log "Quick Install is set to quit product installation. Please upgrade Red Hat version."
			if [ "$QUIET" != "true" ] ; then
				show_msg "$MSG" "Warning"
			fi
			clean_exit
		# User is asked
		elif [ "$QUIET" != "true" ]
		then
			MSG="$MSG\nDo you want to abort T&A installation ?"
			$DIALOG --title "Warning" --clear --yesno "$MSG" 0 0
			resDial=$?
			if [ $resDial -eq 0 ]; then
				add_log "User chooses to stop installation as version of Red Hat is not expected one"
				clean_exit
			fi
			add_log "User chooses to continue installation without the expected version of Red Hat"
		# Quiet mode
		else
			add_log "Operating system not compatible ($rhVersion) however Quick Install is set to continue installation."
		fi
	#
	# RedHat version is compatible
	#
    else
        add_log "Red Hat version is one of the expected"
    fi

else
    add_log "Red Hat version control is diasabled by quick install configuration"
fi

# 10/02/2011 10:01 SCT : Ajout d'un warning dans le cas d'une installation sur une version PHP inférieure à 5.2.13
#  - récupération de la version PHP
#  - comparaison de la version avec 5.2.13 : si version inférieure, affichage d'un message d'alerte
# 30/06/2011 17:46 SCT : Add infos about php 5.2.13 for GIS (need bigger serialized objects)
version_php_number=$(php -v | awk 'NR==1 {print $0}' | awk -F ' ' '{print $2}')
version_php=$(php -v | awk 'NR==1 {print $0}' | awk -F ' ' '{print $2}' | awk -F'.' '{if ($1 > "5" || ($1 == "5" && ($2 >= "3" || ($2 == "2" && $3 >= "13")))) {print "OK"}}')
add_log "Server PHP version: $version_php_number ($version_php)"
if [ "$version_php" != "OK" ] ; then
    add_log "PHP version is not one of the expected"
    if [ $QUIET = "true" ]; then
        MSG="This version of T&A requires PHP 5.2.13 or higher."
        MSG="$MSG\nSome features (GIS) will not be available when using earlier versions."
        MSG="$MSG\nWe strongly advise you to upgrade your version of PHP before proceeding."
        cat $MSG
        add_log "$MSG"
        clean_exit
    else
        MSG="This version of T&A requires PHP 5.2.13 or higher."
        # PHP 5.2.13 obligatoire avec CB 5.2 ou supérieur
        if [ "$cbVersion52" != 1 ]; then
            MSG="$MSG\nSome features (GIS) will not be available when using earlier versions."
            MSG="$MSG\nWe strongly advise you to upgrade your version of PHP before proceeding."
            MSG="$MSG\nQuit installer and upgrade PHP ?"
            $DIALOG --title "Warning" --clear --yesno "$MSG" 0 0
            resDial=$?
            if [ $resDial -eq 0 ]; then
                add_log "User chooses to stop installation as version of PHP is not expected one"
                clean_exit
            fi
            add_log "User chooses to continue installation without expected version of PHP"
         else
            add_log "Quick Install is set to quit product installation. Please upgrade PHP version."
            show_msg "$MSG" "Warning"
            clean_exit
         fi
    fi
fi
# FIN 10/02/2011 10:01 SCT

# 14/04/2011 SCT : let's check default postgresql port is OK
add_log "Check postgresql access"
check_postgresql_connection
# Check resultat is integer (if not : postgresql port is asked to user)
if [ "$connectionState" -eq 1 ] ; then
    add_log "Check postgresql port defaut ($postgresqlPort) : OK"
else
    add_log "Default postgresql port is not valid, ask user for the real one."
    postgresqlPort=""
fi

# Nettoyage avant extraction (evite d'utiliser les fichers d'autres applications)
clean_dirs

# Extraction de l'archive
add_log "Archive extract" "title"
tar xzf $archive >> $LOG 2>&1
add_log "...done"

# ==== Recherche des numeros de version ====
add_log "Looking for component versions" "title"
# ==== CB ====
if [ -d $WORKING_DIR/cb ] ; then
	# CB initial
	tmp=$(ls -1 cb/|head -1)
	cb_dir=${tmp##*/}
	cb_ver=${tmp#cb_v}
	tmp="$(ls -1 cb/$cb_dir/*.sh)"
	cb_shell="${tmp##*/}"
	cb_final_ver=$cb_ver
	
    add_log "CB version : $cb_ver"
	# patch CB
	tmp=$(ls -1 cb/|tail -1)
	cbp_dir=${tmp##*/}
	if [ $cbp_dir != $cb_dir ]
	then
		# dans ce cas on a effectivement un patch CB
		cbp_ver=${tmp#cb_v}
		tmp="$(ls -1 cb/$cbp_dir/*.sh)"
		cbp_shell="${tmp##*/}"
		cb_final_ver=$cbp_ver
        add_log "CB patch : $cbp_ver"
	fi
	# En cas de patch on a un seul CB mais c'est un patch
	if [ "$type_install" = "patch" ] ; then
		cbp_ver=$cb_ver
		cbp_shell=$cb_shell
	fi
fi

# ==== PARSER ====
if [ -d $WORKING_DIR/parser ] ; then
	tmp=$(ls -1 parser)
	parser_dir=${tmp##*/}
	parser_ver=${tmp#*_v}
	tmp="$(ls -1 parser/$parser_dir/*.sh)"
	parser_shell="${tmp##*/}"
    add_log "Parser : $parser_ver"
fi

# ==== CONTEXT ====
if [ -d $WORKING_DIR/context ] ; then
	ctx_dir=$PWD/context
	ctx_archive=$(ls -1 context)
	tmp=${ctx_archive##*_}
	ctx_ver=${tmp%.tar.bz2}
    add_log "Context : $ctx_ver"
fi

# ==== HOMEPAGE ====
if [ -d $WORKING_DIR/homepage ] ; then
    homepage_shell="$(cd homepage; ls -1 *.sh| head -1)"
    homepage_version=${homepage_shell##*_v}
    homepage_version=${homepage_version%.sh}
    add_log "Homepage : $homepage_version"
fi


# ==== Collecte des informations (appname, dbname, key) ====
add_log "Installation process" "big_title"
TITLE="Installation of $product_label $product_version"

# Affichage de bienvenue
MSG=''
[ -d $WORKING_DIR/cb ] && MSG="The final version of Base Component will be: $cb_final_ver"
[ -d $WORKING_DIR/parser ] && MSG="$MSG\nThe final version of Parser will be: $parser_ver"
[ -d $WORKING_DIR/context ] && MSG="$MSG\nThe final version of Context will be: $ctx_ver"
show_msg "$MSG" "$TITLE"


# 14/04/2011 SCT : if postgresql port is empty (not default postgresql port OR the one given in silent mode)
# ==== POSTGRESQL PORT ====
add_log "Postgresql port selection" "title"
if [ -z "$postgresqlPort" ] ; then
    # dans le cas d'une désactivation de l'option de modification du port postgresql
    if [ "$allowDifferentPostgresqlPort" == 0 ] ; then
        add_log "Postgresql server port is not default $postgresqlDefaultPort and feature for different postgresql port is not allow."
        if [ $QUIET = "true" ] ; then
            MSG="Postgresql Server port is not available for connection."
            MSG="$MSG\nWe advise you to contact T&A support team as postgresql is not $postgresqlDefaultPort."
            cat $MSG
            add_log "$MSG"
            clean_exit
        else
            MSG="Postgresql server port is not setup on $postgresqlDefaultPort. Change postgresql server port or contact T&A support team."
            show_msg "$MSG" "Error"
            add_log "$MSG"
            clean_exit
        fi
    else
        if [ $QUIET = "true" ] ; then
            MSG="Postgresql Server port is not available for connection."
            MSG="$MSG\nWe advise you to check Postgresql Port and give it to command line."
            cat $MSG
            add_log "$MSG"
            clean_exit
        fi
        conditionPostgresqlPortValid=0
        messsagePostgresqlPort=""
        while [ $conditionPostgresqlPortValid -eq 0 ]
        do
            exec 3>&1
            postgresqlPort=$($DIALOG --clear --title "Select Postgresql port" \
                --colors --inputbox "Please enter the port for postgresql server (in numeric format) $messagePostgresqlPort" \
                16 61 $postgresqlDefaultPort 2>&1 >&3)
            buttonAction=$?
            exec 3>&-
            # user gives up
            if [ $buttonAction -eq 1 ] ; then
                conditionPostgresqlPortValid=1
                postgresqlPort=""
                add_log "User choose to give up on Postgresl Port selection"
            # in case of given port is null
            elif [ -z "$postgresqlPort" ]; then
                messagePostgresqlPort="\n\Z1Postgresql port can't be null. Please, try again.\Zb"
                add_log "User choose postgresql server port : $postgresqlPort"
            # in case of given postgresql port is not null, let's check connection
            else
                check_postgresql_connection
                # Check resultat is integer (if not : postgresql port is asked to user)
                if [ "$connectionState" -eq 1 ] ; then
                    conditionPostgresqlPortValid=1
                    add_log "User choose postgresql server port : $postgresqlPort (connection to database is successful)"
                else
                    messagePostgresqlPort="\n\Z1This Postgresql port is not valid. Please, try again.\Zb"
                    add_log "User choose postgresql server port : $postgresqlPort (not valid)"
                fi
            fi
        done
        if [ -z "$postgresqlPort" ]; then
            show_msg "You must choose the postgresql server port. You can relaunch this script now." "Error"
            add_log "You must choose the postgresql server port. You can relaunch this script now."
            clean_exit
        fi
    fi
    add_log "User choose postgresql server port : $postgresqlPort"
    PSQL="$psql_bin -U postgres -p $postgresqlPort -tAc "
fi
add_log "...done"
# FIN 14/04/2011 SCT

# ==== APPNAME ====
# Si appname est vide on pose la question
# 09:00 04/07/2011 SCT : list applications and not databases
add_log "Application name selection" "title"
if [ -z "$appname" ]
then
    tempTimeStamp=$(date +%Y_%m_%d_%H_%M_%S)
    stockageDatName=/tmp/${tempTimeStamp}
    compteur=0
    percentage=0
    nbrDirectory=$(ls -d /home/*/ | wc -l)
    listDirectory=(`ls -d /home/*/`)
    (
    while test $compteur != $nbrDirectory
    do
        percentage=$(($compteur*100/$nbrDirectory))

        echo $percentage
        echo "XXX"				
        echo "Please wait while searching existing products ..."
        echo "XXX"

        # Actions to execute
        #   - Search if BDD file exists
        #   - Search BDD name
        #   - Search Module and Version
        #   - Add elements to $stockageDatName
        if [ -f "${listDirectory[$compteur]}php/xbdd.inc" ] ; then
            datname=$(cat ${listDirectory[$compteur]}php/xbdd.inc | grep "DBName" | awk -F '"' '{print $2}')
            tableExists=$($psql_bin -U postgres -d $datname -p $postgresqlPort -tAc "SELECT COUNT(*) FROM pg_tables WHERE schemaname = 'public' AND tablename IN ('sys_global_parameters');")
            if [ "$tableExists" = 1 ]; then
                valeurModule=$($psql_bin -U postgres -d $datname -p $postgresqlPort -tAc "SELECT value FROM sys_global_parameters WHERE parameters='module';")
                productVersion=$($psql_bin -U postgres -d $datname -p $postgresqlPort -tAc "SELECT value FROM sys_global_parameters WHERE parameters='product_version';")
                actualProduct=${listDirectory[$compteur]}
                realAppName=${actualProduct:6:$((${#actualProduct}-7))}
                if [ "$valeurModule" = "$parser_name" ]; then
                    add_log "Found existing $parser_name application on server : $datname ($productVersion)"
                    echo " "$realAppName" ("$productVersion") off" >> $stockageDatName
                elif [ "$valeurModule" = "def" ] ; then
                    valeurOldModule=$($psql_bin -U postgres -d $datname -p $postgresqlPort -tAc "SELECT value FROM sys_global_parameters WHERE parameters='old_module';")
                    if [ "$valeurOldModule" = "$parser_name" ]; then
                        add_log "Found existing $parser_name application on server : $datname"
                        echo " "$realAppName" ("$productVersion") off" >> $stockageDatName
                    fi
                fi
            fi
        fi
        # recherche la correspondance avec le produit
        compteur=$(($compteur+1))
    done
    ) | $DIALOG --clear --title "Searching existing products" --gauge "Please wait..." 10 70 0
    stockageDatname=$(cat $stockageDatName)
    rm -f $stockageDatName

	if [ ! -z "$stockageDatname" ]; then
		exec 3>&1
		appname=$($DIALOG --clear \
            --title "Existing products can be upgraded" \
	        --radiolist "Install a new product or upgrade an existing product below"  20 61 5 \
       			"Install a new product"  "" ON \
		        $stockageDatname \
	            2>&1 >&3 )
		exec 3>&-
		if [ -z "$appname" ]; then
			show_msg "You have cancelled T&A installation. You can relaunch this script now." "Error"
            add_log "You have cancelled T&A installation. You can relaunch this script now."
			clean_exit
		fi

		if [ "$appname" = "Install a new product" ]; then
			appname=""
		fi
	fi

	if [ -z "$appname" ]; then
        choiceType='manual appname'
		exec 3>&1
		appname=$($DIALOG --clear --title "$TITLE" \
			--inputbox "Please enter the name of the application  (in lowercase)" \
			 16 61 $default_appname 2>&1 >&3)
		exec 3>&-
	fi
    # FIN 23/03/2011 SCT : amélioration du script d'installation => on propose plusieurs bases dans le cas de la migration
fi

add_log "User choose application : $appname"
add_log "Check application name function."
check_appname $appname

if [ -z "$appname" ]; then
	show_msg "Incorrect name for the application. You can relaunch this script now." "Error"
    add_log "Incorrect name for the application. You can relaunch this script now."
	clean_exit
fi

# 10:29 06/09/2011 SCT : modification du nom du log
newLOG=$WORKING_DIR/log_install_ta_${product_name}_${product_version}_${timestampIntallLog}_${appname}.log
mv $LOG $newLOG
LOG=$newLOG
add_log "Application name has been selected. Changing log file name to '$LOG'."
add_log "...done"

# En cas en patch :
# Si appname est vide ou si le répertoire n'existe pas on quitte
if [ "$type_install" = "patch" ] && [ ! -d "/home/$appname/" ]
then
    MSG="$appname is not an installed T&A product"
	show_msg "$MSG" "Error"
    add_log "Patching existing application : $appname doesn't exist. Application can't be migrated."
    clean_exit
fi

# ==== DBNAME ====
add_log "Database name selection" "title"
# Vérification de l'existance du répertoire donc du nom de l'application déjà installée
default_dbname=''
if [ -d "/home/$appname" ] ; then
	# vérification de l'existance du fichier xbdd.inc
	if [ -f "/home/$appname/php/xbdd.inc" ] ; then
		# récupération du nom de la base de données
		# (on passe un coup de dos2unix, sinon on se recupere une mauvaise fin de ligne
		dos2unix -q /home/$appname/php/xbdd.inc
		default_dbname=$(grep "DBName" /home/$appname/php/xbdd.inc | sed -r 's/\$DBName = \"([^\"]*)\";/\1/g')
		if [ -n "$dbname" ] && [ "$dbname" != "$default_dbname" ] ; then
			MSG="Provided database name is different from installed database name."
			show_msg "$MSG" "Error"
            add_log "$MSG"
			clean_exit
		fi
		# dans le cas où le nom de la bdd n'a pas été fournie et la bdd existe, on utilise celle existante
		dbname=$default_dbname
		# Si la base existe on est soit en "migration" soit en "patch"
		# on verifie si on est deja en v5
		if [ "$($PSQL "SELECT substr(item_value, 1, 1) from sys_versioning WHERE item='cb_version' ORDER BY date DESC LIMIT 1" -d $dbname)" = "5" ]; then
			type_install="patch"
		else
			type_install="migration"
		fi
    
        # 10:48 26/07/2011 SCT : in case of user choose the same appname as an existing one with "new install product" feature
        if [ "$choiceType" = "manual appname" ] ; then
            MSG="You choose to install a new product whose name already exists."
            MSG="$MSG\nContinue installation with updating existing product?"
            $DIALOG --title "Warning" --clear --yesno "$MSG" 10 61
            resDial=$?
            if [ $resDial -eq 1 ]; then
                add_log "Application name already exists for new application. User stops installation."
                clean_exit
            fi

        fi
	fi
fi

# 11:35 01/07/2011 SCT : Add installation mode in log
add_log "Quick install mode : $type_install"

# 14:34 12/09/2011 SCT : check if this quick install can be apply on existing application
if [ "$type_install" != "installation" ] && [ "$choiceType" = "manual appname" ] ; then
    valeurModuleOnSelectApp=$($psql_bin -U postgres -d $dbname -p $postgresqlPort -tAc "SELECT value FROM sys_global_parameters WHERE parameters='module';")
    if [ "$valeurModuleOnSelectApp" = "def" ] ; then
        valeurModuleOnSelectApp=$($psql_bin -U postgres -d $dbname -p $postgresqlPort -tAc "SELECT value FROM sys_global_parameters WHERE parameters='old_module';")
    fi
    if [ "$valeurModuleOnSelectApp" != "$parser_name" ]; then
        MSG="User tried to apply quick_install on non compatible existing application."
        MSG="$MSG\nCheck compatible application to patch or select compatible quick_install application."
        if [ $QUIET = "true" ] ; then
            cat $MSG
        else
            show_msg "$MSG" "Error"
        fi
        add_log "$MSG"
        clean_exit
    fi
fi

# bz 19811 : modification du message si l'application existe déjà
if [ "$type_install" != "installation" ]
then
	MSG_APP_NAME="The application is named $appname"
	MSG_DB_NAME="The database is named"
else
	MSG_APP_NAME="The application will be named $appname"
	MSG_DB_NAME="The database will be named " 
fi	
show_msg "$MSG_APP_NAME" "$TITLE"

# Si dbname est vide et qu'on est en mode 'installation' (pas de migration),
# dbname doit etre égale par defaut à appname
if [ "$type_install" = "installation" ] && [ -z "$dbname" ]; then
	# poser la question
	exec 3>&1
	dbname=$($DIALOG --clear --title "$TITLE" \
		--inputbox "Please enter the name of the database (in lowercase)" \
		 16 61 $appname 2>&1 >&3)
	exec 3>&-
fi

#[MDE]verification si l'appication patchée est à la version min_previous_product_version au minimum
if [ "$type_install" != "installation" ] && [ -n "$min_previous_product_version" ]
then
	patchedAppVersion=$($psql_bin -U postgres -d $dbname -p $postgresqlPort -tAc "SELECT value FROM sys_global_parameters WHERE parameters='product_version';")
	#Resultat des sed : Ex: "5.0.8.01" => 01*1+8*100+0*10000+5*1000000=5000801
	patchedAppVersion_nb=$(echo $patchedAppVersion | sed 's/\([0-9]*\)\.\([0-9]*\)\.\([0-9]*\).\([0-9]*\)/\4*1+\3*100+\2*10000+\1*1000000/' | bc)
	min_previous_product_version_nb=$(echo $min_previous_product_version | sed 's/\([0-9]*\)\.\([0-9]*\)\.\([0-9]*\).\([0-9]*\)/\4*1+\3*100+\2*10000+\1*1000000/' | bc)
	if [ $patchedAppVersion_nb -lt $min_previous_product_version_nb ]; then 
		MSG="Upgrade from $patchedAppVersion to $product_version is not supported.\nUpgrade to $product_version requires $product_label $min_previous_product_version or higher.\nSo, please upgrade your application first."
		show_msg "$MSG" "Error"
		add_log "$MSG"
		clean_exit
	fi
fi



# vérification de la composition du nom de la base de données
add_log "User choose database : $dbname"
add_log "Check database name function."
check_appname $dbname

if [ -z "$dbname" ]; then
    MSG="Incorrect name for the database. You can relaunch this script now."
	show_msg "$MSG" "Error"
    add_log "$MSG"
	clean_exit
fi

# bz 19811
show_msg "$MSG_DB_NAME $dbname" "$TITLE"
if [ "$appname" != "$dbname" ]; then
	MSG="Application name differs from database name."
    add_log "$MSG"
	if [ $QUIET = "true" ]; then
		echo $MSG
	else
		MSG="$MSG\nContinue installation?"
		$DIALOG --title "Warning" --clear --yesno "$MSG" 0 0
		resDial=$?
		if [ $resDial -eq 1 ]; then
            add_log "Application name differs from database name. User stops installation."
			clean_exit
		fi
	fi
fi
add_log "Database name : $dbname"
add_log "...done"

# 23/03/2011 SCT : amélioration du script d'installation => on vérifie s'il existe plusieurs IP sur la machine : on les propose. S'il n'y a pas d'IP trouvée, l'utilisateur peut la saisir manuellement.
# Vérification de l'IP
add_log "Server IP selection" "title"
server_ip_address=''
if [ "$type_install" = "migration" ] || [ "$type_install" = "installation" ]; then
	nbrEth=$(/sbin/ifconfig | grep "dr" | grep "Bcast" | grep -v "127.0.0.1" | grep -Eo 'dr:([0-9]{1,3}\.){3}[0-9]{1,3}' | cut -d: -f2 | wc -l)
	listEth=$(/sbin/ifconfig | grep "dr" | grep "Bcast" | grep -v "127.0.0.1" | grep -Eo 'dr:([0-9]{1,3}\.){3}[0-9]{1,3}' | cut -d: -f2)
	list1stEth=$(/sbin/ifconfig | grep "dr" | grep "Bcast" | grep -v "127.0.0.1" | grep -Eo 'dr:([0-9]{1,3}\.){3}[0-9]{1,3}' | cut -d: -f2 | head -1)
	server_ip_address_list=""
	onOffEth="on"
        # Mantis 2561: [DE MKT] Reverse Proxy - Prise en compte hostname dans les setups TA 
        # on propose systématiquement de saisir un servername...
	if [ $QUIET = "true" ]; then
		server_ip_address=$list1stEth
	else 
		for uneIp in $listEth
		do
                        add_log "Found several Ip addresses on server : $uneIp"
			server_ip_address_list=$server_ip_address_list" "$uneIp" "." "$onOffEth
			onOffEth="off"
		done
		exec 3>&1
		server_ip_address=$($DIALOG --clear \
			--title "Select Application Ip address or servername" \
			--radiolist "Select application Ip address or servername (alias, hostname, fqdn...) below \n(if you don't know which one to choose, keep first IP in list [private IP]). "  20 61 5 \
                        $server_ip_address_list\
                        "Enter server Ip address or servername" "" OFF \
			2>&1 >&3 )
		exec 3>&-
	fi
    # Dans le cas d'IP inexistante ou vide
    if [ $nbrEth -eq 0 ] || [ -z "$server_ip_address" ] || [ "$server_ip_address" = "Enter server Ip address or servername" ]; then
        exec 3>&1
        server_ip_address=$($DIALOG --clear --title "Enter Application Ip address or servername" \
            --inputbox "Please enter the Ip address or servername (alias, hostname, fqdn...) of the application (IpV4 or name)." \
             16 61 2>&1 >&3)
        exec 3>&-
    fi
    # plus de vérification du format de l'adresse

    # FIN 24/03/2011 SCT
    # en cas d'IP toujours vide, on retourne une erreur
    if [ -z "$server_ip_address" ]; then
        show_msg "You have to choose an IP for T&A. You can relaunch this script now." "Error"
        add_log "You have to choose an IP for T&A. You can relaunch this script now."
        clean_exit
    fi
    add_log "IP address selected : $server_ip_address"
else
    server_ip_address=$($PSQL "SELECT sdp_ip_address FROM sys_definition_product WHERE sdp_db_name='$dbname' AND sdp_directory='$appname'"  -d $dbname)
    add_log "IP address found: $server_ip_address SELECT sdp_ip_address FROM sys_definition_product WHERE sdp_db_name='$dbname' AND sdp_directory='$appname'" 
fi
add_log "...done"
# FIN 23/03/2011 SCT : amélioration du script d'installation => on vérifie s'il existe plusieurs IP sur la machine : on les propose. S'il n'y a pas d'IP trouvée, l'utilisateur peut la saisir manuellement.

# Recherche des cas d'erreur : base existe/rep n'existe pas ou bien base n'exite pas/rep existe
base_exists=$($PSQL "SELECT count(*) FROM pg_database WHERE datname='$dbname';")
MSG_CHECK=''
if [ -d "/home/$appname" ] && [ "$base_exists" = "0" ]; then
	# cas ou le repertoire existe mais pas la base
	MSG_CHECK="Directory $appname already exists, but database $dbname doesn't!"	
fi

if [ ! -d "/home/$appname" ] && [ "$base_exists" = "1" ]; then
	# cas ou le repertoire n'existe pas mais la base est présente
	MSG_CHECK="Database $dbname already exists, but directory $appname doesn't!"	
fi

if [ -n "$MSG_CHECK" ]; then
	show_msg "$MSG_CHECK" "Error"
    add_log "$MSG_CHECK"
	clean_exit
fi

# ==== KEY ====

# Dans le cas d'une migration, on récupère l'ancienne clé
if [ "$type_install" = "patch" ] || [ "$type_install" = "migration" ]; then
	key=$($PSQL "SELECT value FROM sys_global_parameters WHERE parameters='key'" -d $dbname)
    add_log "Case of updating or migrating existing product. Backuping old key : $key"
fi

# si key est vide on pose la question
# 07/04/2011 SCT : on échappe le renseignement de la clé si le module est BP
# 01/09/2011 SCT : modification du nom du produit blanc sur demande OJT
if [ -z "$key" ] && [ ! "$product_name" = "gateway" ] ; then
# FIN 07/04/2011 SCT
    add_log "Key selection" "title"
    # 23/03/2011 SCT : amélioration du script d'installation => on propose la gestion des clés lorsque les fichiers sont présents
    FICHIER_CLE="/home/ta_install/cles_produit/key_$product_name.txt"
    if [ -f $FICHIER_CLE ]
    then
        CONTENU_CLE=""
        for line in $(cat $FICHIER_CLE)
        do
            CONTENU_CLE=$CONTENU_CLE' '$line
        done

        exec 3>&1

        key=$($DIALOG --clear \
                --title "Product activation" \
                --radiolist "Enter a new key or select from existing keys below"  20 81 5 \
                "Enter a new Key"  "" ON \
                $CONTENU_CLE \
                    2>&1 >&3 )
        exec 3>&-
        if [ "$key" = "Enter a new Key" ]; then
            key=""
        fi
    fi

    if [ -z "$key" ]; then
        exec 3>&1
        key=$($DIALOG --clear --title "$TITLE" \
            --inputbox "Please enter the product key provided by Astellia" \
             16 61 2>&1 >&3)
        exec 3>&-
    fi
    # FIN 23/03/2011 SCT : amélioration du script d'installation => on propose la gestion des clés lorsque les fichiers sont présents
	# si elle est toujours vide, on quitte
	if [ -z "$key" ]; then
		show_msg "Please provide a key to install this product" "Error"
		clean_exit
	fi
    add_log "Key selected for product : $key"
fi


# ==== In case of migration or patch, check "date" from sys_versioning tables ====
if [ "$type_install" = "patch" ] || [ "$type_install" = "migration" ]; then
    add_log "Type_install : ${type_install} => last checks" "title"
	# 03/11/2011 - MPR : bz24480 : on postgreSQL 9.1.1, the query is invalid
	queryCheck="SELECT COUNT(*) FROM sys_versioning WHERE REPLACE(date, '_', '#') !~ E'^[0-9]{4}#[0-9]{2}#[0-9]{2}#[0-9]{2}.*?';"
	NbError=$($PSQL "$queryCheck" -d $dbname)
    if [ $NbError -gt 0 ] ; then
        # backup of sys_versioning table
        $PSQL "CREATE TABLE sys_versioning_bck AS SELECT * FROM sys_versioning;" -d $dbname > /dev/null
        add_log "Error found into sys_versioning, trying to format date"
        # 03/11/2011 - MPR : bz24480 : on postgreSQL 9.1.1, the query is invalid
		# 14/11/2011 - MPR : Management All date formats
		$PSQL "UPDATE sys_versioning SET date = to_char(date::timestamp, 'YYYY_MM_DD_HH_ii') WHERE date !~ E'^[0-9]{4}_[0-9]{2}_[0-9]{2}_[0-9]{2}_[0-9]{2}$' AND date !~ E'^[0-9]{4}_[0-9]{2}_[0-9]{2}_[0-9]{2}$';" -d $dbname > /dev/null 2>&1
        # check once again sys_versioning
		# 03/11/2011 - MPR : bz24480 : on postgreSQL 9.1.1, the query is invalid
        NbError=$($PSQL "$queryCheck" -d $dbname)
        if [ $NbError -gt 0 ] ; then
            # restore backuped table
            $PSQL "DROP TABLE sys_versioning; ALTER TABLE sys_versioning_bck RENAME TO sys_versioning;" -d $dbname > /dev/null
            if [ "$QUIET" = "false" ]; then
                show_msg "There is a problem with 'date' field from sys_versioning table.\nPlease check this field and change date format if necessary.\nExpected date format : YYYY_MM_DD_HH_II[_SS]" "Error"
            fi
            add_log "There is a problem with 'date' field from sys_versioning table. Please check this field and change date format if necessary."
            clean_exit
        else
            # restore backuped table
            $PSQL "DROP TABLE sys_versioning_bck;" -d $dbname > /dev/null
            add_log "dates have been cleaned."
            add_log "... done"
        fi
    else
        add_log "... sys_versioning table's date are well formated"
        add_log "... done"
    fi
fi

# ==== Installation ====
add_log "Installation launched" "big_title"
# Les variables sont initialisées, les questions sont posées
# on peut commencer le traitement

# Juste avant de commencer a faire des modifications un peu partout,
# on regarde s'il n'y a aucun process en cours.
# Pourquoi on ne regarde pas avant, avec les autres verifs ?
# Parce que la reponse aurait largement le temps de changer entre temps
# http://fr.wikipedia.org/wiki/Situation_de_comp%C3%A9tition
exit_if_process_running

# 05/06/2014 - GFS Bug 41791 - [REC][IU 5.3.2.01][TC #TA-62666][Migrate from 5.3.1 to 5.3.2] There are errors in demon file
# Juste avant de commencer a faire des modifications un peu partout,
# on regarde s'il n'y a aucun counter tout juste activé mais non deployé.
exit_if_active_counters_not_deployed

# On met a off les process qui pourraient se lancer automatiquement
# et on se souvient de leur etat pour restauration ulterieure
sysdefmaster backup

# Pourquoi encore exit_if_process_running ? Parce qu'un process a pu se lancer entre le
# exit_if_process_running ci-dessus, qui verifie s'il y a un process en cours
# et le sysdefmaster backup, qui desactive les futurs lancement de process
# Race condition les enfants, race condition.
# Je remets le lien wikipedia ? Non ? D'accord.
exit_if_process_running

if [ "$QUIET" = "false" ]; then
	$DIALOG --title "$TITLE" --msgbox "Press OK to proceed installation." 0 0
	resDial=$?
	if [ $resDial -eq 1 ]; then
        add_log "User aborts installation"
		clean_exit
	fi
	$DIALOG --title "$TITLE" --infobox "Take few minutes..." 0 0
	[ $? -eq 255 ] && clean_exit
fi

# Creation of database backup directory (/home/backup)
if [ -d /home/backup/ ]; then
	add_log "Backup directory /home/backup already created"
else
	add_log "Creation of backup directory /home/backup"
	mkdir /home/backup/
	chmod 744 /home/backup/
	chown astellia:astellia /home/backup/
fi

SQL="$psql_bin -U postgres -p $postgresqlPort -L $LOG -d $dbname -tAc "
SQLFILE="$psql_bin -U postgres -p $postgresqlPort -L $LOG -d $dbname -f "

# 11:46 01/07/2011 SCT : Save content of table sys_versioning into log in case of update existing application
if [ "$type_install" = "migration" ] || [ "$type_install" = "patch" ]; then
    add_log "Updating existing application, application history :"
    poubelle=$($psql_bin -U postgres -p $postgresqlPort -L $LOG -d $dbname -c "SELECT * FROM sys_versioning;" 2>&1) >> $LOG
fi


# ===== Installation des elements =====

# export des variables utiles pour les scripts pre/post
export SQL WORKING_DIR type_install appname dbname LOG DIALOG QUIET psql_bin postgresqlPort

# Script facultatif precedant l'installation des elements
launch_prepost_script "pre_installation.sh"

# On boucle sur le contenu de la variable $install_order
# et on lance la fonction d'installation associee a chaque element

# ajout du ; final a $install_order, si manquant
# 15:13 11/07/2011 SCT : adding progres bar during installation
[ "${install_order:$((${#install_order}-1)):1}" = ";" ] && install_order=${install_order:0:$((${#install_order} - 1))}
# set string as tab
OIFS=$IFS
IFS=";"
tabInstallOrder=( $install_order )
IFS=$OIFS
# number of component
nbrTabInstallOrder=${#tabInstallOrder[*]}
if [ "$nbrTabInstallOrder" -gt 0 ] ; then
    compteur=0
    (
    while test $compteur != $nbrTabInstallOrder
    do
        percentage=$(($compteur*100/$nbrTabInstallOrder))

        # component
        to_inst=${tabInstallOrder[$compteur]}
        componentProgress=""
        case "$to_inst" in
            "cb_base")
                componentProgress="Base Component"
                ;;
            "cb_patch")
                componentProgress="Patch CB"
                ;;
            "parser")
                componentProgress="Parser"
                ;;
            "context")
                componentProgress="Context"
                ;;
        esac

        echo $percentage
        echo "XXX"				
        echo "Please wait while installing product component ..."
        echo "..."$componentProgress
        echo "XXX"

        case "$to_inst" in
            "cb_base")
                # Dans le cas d'un patch on force install_cb_patch au lieu de install_cb_base
                # Sauf dans le cas d'un CB superieur a 5.0.3.X (install_cb fait les 2)
                cb_final_ver_testable=`echo "$cb_final_ver" | sed "s/\.//g"`
                if [ "$type_install" != "patch" ] || [ "${cb_final_ver_testable:0:3}" -ge 503 ]; then
                    [ -n "$cb_ver" ] && install_cb_base
                else
                    [ -n "$cb_final_ver" ] && install_cb_patch
                fi
                ;;
            "cb_patch")
                [ -n "$cb_final_ver" ] && install_cb_patch
                ;;
            "parser")
                [ -n "$parser_ver" ] && install_parser
                ;;
            "context")
                [ -n "$ctx_ver" ] && install_context
                ;;
        esac
        compteur=$(($compteur+1))
    done
    ) | $DIALOG --clear --title "Installing product" --gauge "Please wait..." 10 70 0
fi

# 15:51 05/07/2011 SCT : T&A connection is set before post_installation script launch
# default connection
if [ "$type_install" = "installation" ]; then
	# Creation seulement si aucune autre connexion identique n'existe et qu'on est en mode installation
	nb_cx=$($psql_bin -U postgres -p $postgresqlPort -tAc "SELECT count(connection_name) FROM sys_definition_connection WHERE connection_directory='/home/$appname/flat_file'" $dbname |head -1|sed 's/\s*//')
	[ "$nb_cx" = "0" ] && $SQL "INSERT INTO sys_definition_connection (connection_name,connection_login,connection_password,on_off,connection_ip_address,connection_type,id_region,connection_directory,connection_code_sonde,protected) VALUES ('local','','',1,'','local','','/home/$appname/flat_file','',0);" >> $LOG 2>&1
fi
# Si on est en mode patch, on essai de remettre la sequence 'sys_definition_connection_id_connection_seq' en etat
if [ "$type_install" = "patch" ]; then
	$SQL "SELECT nextval('sys_definition_connection_id_connection_seq') WHERE (SELECT last_value FROM sys_definition_connection_id_connection_seq) = 1;" >> $LOG 2>&1
fi

# Script facultatif suivant l'installation des elements
launch_prepost_script "post_installation.sh"

# ===== Fin de l'installation des elements =====

add_log "Apache restart" "title"
/etc/init.d/apachectl restart >> $LOG 2>&1
add_log "...done"

# Copie des docs eventuelles
cd $WORKING_DIR
if [ -d doc ]; then
    add_log "Copying T&A's documentation" "title"
	cp -a doc/* /home/$appname/doc/ >> $LOG 2>&1
    add_log "...done"
fi

# 09:44 01/07/2011 SCT : Add "Product version" and "T&A Package Generator version" in sys_versioning
add_log "Adding 'Product version' and 'T&A Package Generator version' in sys_versioning" "title"
$SQL "INSERT INTO sys_versioning (item, item_value, item_mode, date) VALUES ('QuickInstall','$scriptName','$type_install','$timestampIntall');" >> $LOG
$SQL "INSERT INTO sys_versioning (item, item_value, item_mode, date) VALUES ('T&A Package Generator','$gen_inst_version',null,'$timestampIntall');" >> $LOG

# maj de la clé key
add_log "Updating application key" "title"
$SQL "UPDATE sys_global_parameters SET value='$key' WHERE parameters='key';" >> $LOG 2>&1
# maj du product_version
add_log "Updating application version" "title"
$SQL "UPDATE sys_global_parameters SET value='$product_version' WHERE parameters='product_version';" >> $LOG 2>&1

# mise a jour du parametre topology_file_location
# 17:28 06/09/2011 SCT : BZ 23507 => [SUP][TA NSN Parameters 5.0.2.01][AVP1755][Videotron]:Parameter 'Topology File Location' is replace by default value after patch installation
if [ "$type_install" != "patch" ]; then
    add_log "Updating topology file location parameter" "title"
    topo=$($psql_bin -U postgres -p $postgresqlPort -tAc "SELECT value FROM sys_global_parameters WHERE parameters='topology_file_location';" $dbname|sed 's/ //'|head -1)
    topo="/home/$appname/${topo#/home/*/}"
    $SQL "UPDATE sys_global_parameters SET value='$topo' WHERE parameters='topology_file_location';" >> $LOG 2>&1
fi

# maj de la conf d'apache (alias)
# lors de la migration v4 vers v5 il peut rester un "/" final dans l'alias
add_log "Cleaning Apache alias" "title"
sed -i "s@Alias /$appname/@Alias /$appname@" /home/httpd/conf/httpd.conf
add_log "...done"

# mise a jour du label du produit
# => si le label du produit est egale au nom du repertoire alors on utilise 
add_log "Updating product label" "title"
plabel=$($psql_bin -U postgres -p $postgresqlPort -tAc "SELECT sdp_label FROM sys_definition_product WHERE sdp_directory='$appname';" $dbname|sed 's/ //'|head -1)
if [ "$plabel" = "$appname" ]; then
	$SQL "UPDATE sys_definition_product SET sdp_label=(SELECT value FROM sys_global_parameters WHERE parameters='product_name') WHERE sdp_directory='$appname';" >> $LOG 2>&1
fi

# 02/09/2011 MMT - DE portal Lot 1
# Portal & authentication configuration
# lance la configuration de l'authentication si le script de config existe
# 24/02/2012 NSE DE Astellia Portal Lot2
# Ajout de l'IP + modification règles lancement configuration
portalConfigurator="/home/$appname/tools/configurePortal.sh"
if [ -f "$portalConfigurator" ]; then
	paaConfFile="/home/$appname/api/paa/conf/PAA.inc"
        
    # on ne lance pas la commande avec les mêmes paramètres en 5.1 et 5.2.
    cbPAAL2=$(echo $cbVersion | awk -F'.' '{if (($1 == "5" && $2 >= "2") || ($1 > "5")) {print "1"} else {print "0"}}')

	# détermine si on doit lancer la configuration 
	if [ ! -f "$paaConfFile" ]; then
            # si le fichier n'existe pas
            configLaunch=1
        else
            # si le fichier existe
            # si le CB est >= 5.2 alors il faut mettre à jour le fichier de conf pour le PAA Lot2 même s'il existe déjà pour le PAA Lot1
            if [ "$cbPAAL2" = 1 ]; then
                guid_hexa=$(cat $paaConfFile | grep APPLI_GUID_HEXA | cut -d"," -f2 | sed "s/[ ;')]//g")
                # mais que la variable spécifique PAAL2 n'existe pas
                if [ -z "$guid_hexa" ]; then
                    configLaunch=1
                else
                    configLaunch=0
                fi
            else
                configLaunch=0
            fi
        fi
        if [ "$configLaunch" = 1 ]; then
		dos2unix -q "$portalConfigurator"
		if [ $QUIET = "false" ]; then
                        if [ "$cbPAAL2" = 1 ]; then
                            echo "Launching authentication configuration script $portalConfigurator -l $LOG $server_ip_address" >> $LOG 2>&1
                            sh "$portalConfigurator" -l "$LOG" $server_ip_address
                        else
                            echo "Launching authentication configuration script $portalConfigurator -l $LOG " >> $LOG 2>&1
                            sh "$portalConfigurator" -l "$LOG"
                        fi
		else 
                        if [ "$cbPAAL2" = 1 ]; then
                            echo "Authentication config GUI off, using default authentication mode : File, with $server_ip_address" >> $LOG 2>&1
                            sh "$portalConfigurator" -l "$LOG" $server_ip_address File
                        else
                            echo "Authentication config GUI off, using default authentication mode : File" >> $LOG 2>&1
                            sh "$portalConfigurator" -l "$LOG" TA
                        fi			
		fi
	else
            echo "Skipping portal configuration because it is already configured: $paaConfFile" >> $LOG 2>&1
            # Modification des droits sur le fichier SQLite
            sqlite_dir=$(cat $paaConfFile | grep "'SQLITE_DIR'" | cut -d"," -f2 | sed "s/[ ;')$]//g")
            sqlite_session_file=$(cat $paaConfFile | grep SQLITE_SESSION_FILE | cut -d"," -f2 | sed "s/[ ;')$]//g")
            sqlite_dir=$(echo $sqlite_dir | sed "s/\s//g")
            sqlite_session_file=$(echo $sqlite_session_file | sed "s/\s//g")
            if [ ! -z "$sqlite_session_file" ]; then
                chmod 0777 $sqlite_dir;
                chmod 0766 $sqlite_dir$sqlite_session_file;
                log_debug "Updating Modification des droits sur $sqlite_dir et $sqlite_session_file"
            else
                display_error_and_quit "Could not update rights on '$sqlite_dir$sqlite_session_file'"
            fi
	fi
	# 04/09/2013 GFS - Bug 35923 - [SUP][5.3.1][#NA][Telus] : Data mixed KPI are not calculated for Network and Vendor level
	if [ -d "/home/$appname/mixed_kpi_product/api/paa/conf" ]; then
		cp $paaConfFile /home/$appname/mixed_kpi_product/api/paa/conf
	fi
else 
	echo "Skipping portal configuration because script does not exist: $portalConfigurator" >> $LOG 2>&1
fi

# Homepage
# Installation de la homepage si le repertoire est present
if [ -d $WORKING_DIR/homepage ]; then
    add_log "Homepage installation" "big_title"
	dos2unix -q $WORKING_DIR/homepage/$homepage_shell
	if [ $QUIET = "false" ]; then
		exec 3>&1
		$DIALOG --clear --title "$TITLE" \
				--yesno "Do you want homepage feature to be installed ? " 16 61 2>&1 >&3
		res=$?
		exec 3>&-
		case $res in
			0)
				# 22/02/2011 SCT : désactivation de l'écrasement du fichier config.xml
				add_log "User choosed to install the homepage feature."
				# Variable d'état de conservation de l'ancienne config
				keep_old_config=0
				# Confirmation de conservation de l'ancienne configuration si elle existe
				if [ -d /home/$appname/homepage ] ; then
					# sauvegarde du fichier de configuration
					if [ -f /home/$appname/homepage/config.xml ] ; then
                        add_log "Backuping old homepage config.xml"
						rm -f /home/$appname/upload/config.xml.bck
						cp /home/$appname/homepage/config.xml /home/$appname/upload/config.xml.bck
					fi
					#MSG="Homepage already installed."
					#MSG="$MSG\nKeep old homepage setup ?"
					#$DIALOG --title "Homepage setup" --clear --yesno "$MSG" 0 0
					#resDial=$?
					# Dans le cas où on veut conserver l'ancienne config
					#if [ $resDial -eq 0 ] ; then
						keep_old_config=1
					#fi
				fi
				(cd $WORKING_DIR/homepage; sh $homepage_shell /home/$appname $dbname $PWD astellia) >> $LOG 2>&1
				# Restauration de l'ancienne configuration de page d'accueil
				if [ $keep_old_config -eq 1 ] ; then
					if [ -f /home/$appname/upload/config.xml.bck ] ; then
						add_log "Old homepage config file is going to be kept on new version."
						add_log "New homepage config file is backuped as '/home/$appname/homepage/config.xml.$product_version'."
						mv /home/$appname/homepage/config.xml /home/$appname/upload/config.xml.$product_version
						mv /home/$appname/upload/config.xml.bck /home/$appname/homepage/config.xml
					fi
				fi
				# FIN 22/02/2011 SCT : désactivation de l'écrasement du fichier config.xml
                # 10:09 01/07/2011 SCT : change owner for file config.xml
                chown astellia.astellia /home/$appname/homepage/config.xml
                chmod 777 /home/$appname/homepage/config.xml
                # 17:16 04/07/2011 SCT : allow user to change network aggregation into homepage
                if [ "$allowUserCustomizeHomepage" = 1 ] ; then
                    add_log "homepage default network aggregation level selection feature : enabled"
                    stockageListNa=""
                    # search for family
                    homepageFamily=$(grep "<family>" /home/$appname/homepage/config.xml | awk -F 'family>' '{print $2}')
                    homepageFamily=${homepageFamily/<\//};
                    # search selected NA
                    selectedNa=$(grep "Nelement" /home/$appname/homepage/config.xml | awk -F '"' '{print $2}')
                    selectedNa=${selectedNa/ /}
                    # search for available NA in this family
                    listNa=$($psql_bin -U postgres -d $dbname -p $postgresqlPort -tAc "SELECT agregation FROM sys_definition_network_agregation WHERE family = '$homepageFamily' ORDER BY agregation_level;")
                    for oneNa in $listNa
                    do
                        if [ "$oneNa" = "$selectedNa" ]; then
                            stockageListNa=$stockageListNa" "$oneNa" "$oneNa" on"
                        else
                            stockageListNa=$stockageListNa" "$oneNa" "$oneNa" off"
                        fi
                    done
                    # select NA from NA list
                    exec 3>&1
                    newSelectedNa=$($DIALOG --clear \
                        --title "Change homepage default NA" \
                        --radiolist "Select a default network aggregation level for homepage"  20 61 5 \
                            $stockageListNa \
                            2>&1 >&3 )
                    exec 3>&-
                    # save NA level choice
                    if [ -z "$newSelectedNa" ]; then
                        add_log "User keep default NA level for homepage : $selectedNa."
                        show_msg "You have cancelled to choose a new default network aggregation level for homepage. Old default one will be used as default." "Info"
                    else
                        add_log "User decides to select a new NA level for homepage : $newSelectedNa."
                        sed -i "s/<Nelement element=\"${selectedNa}\">/<Nelement element=\"${newSelectedNa}\">/g" /home/$appname/homepage/config.xml >> $LOG 2>&1
                    fi
                else
                    add_log "homepage default network aggregation level selection feature : disabled"
                fi
				;;
			1)
				add_log "User choosed not to install the homepage feature."
				;;
			*)
				add_log "Error encoutered with user choice whether to install the homepage"
				;;
		esac
	fi
    add_log "...done"
fi

# on ne va plus faire de modif, je le mets explicitement ici (meme si c'est appele de toutes facons a la fin du script)
# car a la ligne suivante on appelle $DIALOG, et si l'operateur n'est pas devant l'ecran, il peut bloquer l'application
# pour rien jusqu'a ce qu'il appuie sur entree (alors que le script d'install a fini les modifs sur l'application)
sysdefmaster restore

if [ "$type_install" != "patch" ]
then
	MSG="
	Be sure that you have the client topology.\n
	Refer to CD/Documents/InstallationManual/\n
	Enter the login and password client_admin, and then click on Enter.\n
	You can update the topology by following this steps:\n
	- Go to the menu TOPOLOGY, Upload Topology\n
	- Choose a family\n
	- By clicking on Browse button, select the topology of your choice.\n
	- Click on Submit \n
	The uploaded topology must appear in the window with a success message."
	show_msg "$MSG" "Update of main topology"
	# 11/02/2014 GFS - Bug 38513 - [REC][T&A Iu 5.3.1.02][TC#TA-57155][Installation]:Information on 'FTP connection' popup is not correct
	MSG="
	For the collect of data files its necessary to create a ftp connection for every PSU\n
	Refer to :\nAdministrator manual : Part 5.2 SETUP CONNECTION (Manuals are available in the About)\n
	You also need to install FTP server FileZilla on every PSU. Follow :\nCD/Documents/InstallationManual/TA_FilezillaFtpServer_IM_RA.pdf\nUsing file CD/Tools/FileZilla_Server-0_9_25.exe"
	show_msg "$MSG" "FTP connection"
	
	MSG="Connect to TA with astellia_admin account \nClick on the logo to change it."
	show_msg "$MSG" "Logo's update"
fi

# 17:35 30/06/2011 SCT : BZ 22165 => fixed misspell error
MSG="Setup of $product_label $product_version finished \nA log is available here: \n$LOG"
show_msg "$MSG" "$TITLE"

# Tout s'est bien passé, on peut inscrire dans le log général que l'application est installée
# Les champs sont les suivants : 
#  * date;
#  * nom du produit;
#  * nom de l'application;
#  * migration/installation/patch;
#  * version cb;
#  * version parser;
#  * version contexte
#echo "$(date +"%Y-%m-%d %H:%M");$product_name;$appname;$type_install;$cb_final_ver;$parser_ver;$ctx_ver" >> /home/ta_installed_product

add_log "End installation" "big_title"
add_log "cleaning installation directory"
clean_dirs
add_log "installation done."
