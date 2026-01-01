#!/bin/bash

# Authentication configuration script, allow to configure the T&A to use a CAS
# server for authentication with Astellia Portal
#
# This script generate the api/paa/conf/PAA.inc file from command-line arguments or
# will ask them to user in a shell dialog
# Can be ran manually or executed via the T&A quick_install

# 01/09/2011 MMT DE Portail Lot 1 - creation du script
# 20/02/2012 NSE DE Astellia Portal Lot2
# Mantis 2561: [DE MKT] Reverse Proxy - Prise en compte hostname dans les setups TA

# ------------- Usage and option management
# 15/10/2014 NSE bz 44552: add of protocol argument
function USAGE ()
{
    echo ""
    echo "USAGE: "
    echo "  configurePortal.sh [-l <logfile>] [-?] appli_ip [mode] [CAS IP] [-h <protocol>]"
    echo ""
    echo "OPTIONS:"
    echo " -l      log file"
    echo " -t      protocol used to launch T&A from Portal (http or https, http by default)"
    echo " -?      this usage information"
    echo ""
    echo "ARGUMENTS:"
    echo " appli_ip Application Ip"
    echo " [mode]   Authentication mode: "
    echo "            'File' for stand-alone use (local predefined accounts not updatable)"
    echo "            'CAS'  for use with Portal Authentication"
    echo "          If omitted, the script will prompt the user in dialog"
    echo " [CAS]    CAS server Ip address or servername (alias, hostname, fqdn...), only "
    echo "          relevant for CAS mode - required for CAS"
    echo ""
    echo "EXAMPLE:"
    echo "  configurePortal.sh -l /tmp/paa.log -t https 10.49.0.3 CAS 10.35.10.121"
    echo ""
    exit $E_OPTERROR    # Exit and explain usage, if no argument(s) given.
}

# manage command line options action and assignement
while getopts ":l:t:?" Option
do
    case $Option in
        l    ) LOGARG=$OPTARG;;
        t    ) PROTOARG=$OPTARG;;
        ?    ) USAGE
               exit 0;;
        *    ) echo ""
               echo "Unimplemented option chosen."
               USAGE   # DEFAULT
    esac
done
# shift the argument array to remove the options
shift $(($OPTIND - 1))

if [ $# -lt 1 ]; then
		echo $0' : too many or too few arguments'
		USAGE
		exit 127
	fi

user=`whoami`
if [ $user != 'root' ]; then
	echo "Only root is allowed to run this script."
	exit 127
fi

# ------------- global environment definitions

# get the directory of the script (not where it gets called from)
# this should return /home/<ta>/tools/
currentScriptDir="$( cd -P "$( dirname "$0" )" && pwd )"

# get the current application name from the script directory
APPNAME="$(echo "$currentScriptDir" | cut -d "/" -f 3 )"

# ------------- global file settings
paa_conf_dir="/home/$APPNAME/api/paa/conf/"
paa_conf_model_file_name="PAAmodel.inc"
paa_conf_file_name="PAA.inc"

# define the log file, if not provided via -l option, none
if [ -z "$LOGARG" ]; then
	LOG="/dev/null"
else
	LOG=$LOGARG
fi

# if in SILENCE mode, no GUI nor prompt to the user, all parameters must be correctly
# provided in the command line
if [ ! -z "$3" ]; then
	SILENCE="true"
else
	SILENCE="false"
fi

# 15/10/2014 NSE bz 44552: add of protocol argument
ta_launch_protocol=""
if [ "$PROTOARG"=="http" -o "$PROTOARG"=="https" ]; then
    ta_launch_protocol="$PROTOARG"
fi
if [ "$SILENCE" == "true" ]; then
    if [ -z "$ta_launch_protocol" ]; then
        ta_launch_protocol="http"
    fi
fi

# dialog definition
DIALOG=gdialog
[ -z $DISPLAY ] && DIALOG=dialog

# ------------- functions definition

# log a message to the log file
# $1 : message to log
log_msg() {
	if [ $# -ne 1 ]
	then
		echo "Function 'log_message' requires 1 parameter, found: $#"
		exit 1
	fi
	echo "$1" >> $LOG 2>&1
}

# log a milestone message to the log file
# $1 : message to log
log_milestone() {
	log_msg "$1"
}

# log a lower importance message to the log file
# $1 : message to log
log_debug() {
	log_msg "    $1"
}

# Mantis 2561: [DE MKT] Reverse Proxy - Prise en compte hostname dans les setups TA
# plus de vérification du format de l'adresse

# Display a message in na infobox
# Display in stdout if in silence mode
# $1 : message
# $2 : box title (optional)
show_msg() {
	[ $# -lt 1 ] && return
	msg_title="Info"
	[ $# -eq 2 ] && msg_title=$2
	if [ "$SILENCE" = "true" ]; then
		echo $1
	else
		$DIALOG --clear --title "$msg_title" --aspect 50 --msgbox "$1" 0 0
	fi
}

# Display and log an error message and abort script
# $1 : error message
display_error_and_quit() {
	if [ $# -ne 1 ]
	then
		echo "Function 'display_error_and_quit' requires 1 parameter, found: $#"
		exit 1
	fi
	log_msg "!! Fatal Error in authentication configuration : $1"
	show_msg "ERROR: $1" "Error"
	exit 2
}

# ------------- Start of script

log_milestone "##############################################"
log_milestone "#             Portal configuration           #"
log_milestone "##############################################"

# log the current date
currentDate=$(date)
log_milestone "Date: $currentDate"

if [ -z "$APPNAME" ] || [ ! -d "/home/$APPNAME/" ]; then
	display_error_and_quit "Enable to get valid application name :'$APPNAME'"
fi

log_milestone "Detected TA application name: $APPNAME"

# set command line parameters if provided
# 23/02/2012 NSE DE Astellia Portal Lot2 : ajout de paramètres
appli_path=$1/$APPNAME
auth_mode="$2"
cas_ip_address="$3"

log_milestone "command line arguments: Appli_url: '$1', Appli_name: '$APPNAME', Protocol; '$ta_launch_protocol', Mode: '$auth_mode', IP: '$cas_ip_address', Log: '$LOGARG', SILENCE: '$SILENCE'"

# ------------- GUI dialog

if [ "$SILENCE" != "true" ]; then
    # ne sort pas tant que l'utilisateur n'a pas fait de choix du mode
    while [ -z "$auth_mode" ]; do
        exec 3>&1
        # choix du mode d'authentication TA ou CAS
        $DIALOG --clear --title "Central Authentication Service" \
                        --yesno "Do you want to register your T&A on a Portal using Central Authentication Service?" 6 61 2>&1 >&3
        res=$?
        exec 3>&-
        cas_ip_address=""
        case $res in
            0)
                auth_mode="CAS"
                # ne sort pas tant que l'utilisateur n'a pas saisi une IP valide ou annule
                while [ -z "$cas_ip_address" ] && [ ! -z "$auth_mode"  ]; do
                        exec 3>&1
                        # choix de l'IP du CAS
                        # Mantis 2561: [DE MKT] Reverse Proxy - Prise en compte hostname dans les setups TA
                        cas_ip_address=$($DIALOG --clear --title "Enter Portal address" \
                                --inputbox "Please enter the Ip address or servername (alias, hostname, fqdn...) for your Central Authentication Service." \
                                 20 61 2>&1 >&3)
                        abort="$?"
                        exec 3>&-
                        # si utilisateur selectionne annuler, on retourne au choix du mode
                        if [ "$abort" == "1" ]; then
                                auth_mode=""
                        else
                                # sinon on test l'IP
                                # Mantis 2561: [DE MKT] Reverse Proxy - Prise en compte hostname dans les setups TA
                                # plus de vérification du format de l'adresse
                                log_debug "User entered following CAS address: $cas_ip_address"
                        fi
                done

                # 15/10/2014 NSE bz 44552: add of protocol argument
                exec 3>&1
                if [ -z "$ta_launch_protocol" ]; then
                    ta_launch_protocol=$($DIALOG --clear \
                            --title "T&A launch protocol" \
                            --radiolist "Select protocol to use to launch T&A application from Portal. "  20 61 5 \
                            http "" on\
                            https "" off\
                            2>&1 >&3 )
                    exec 3>&-
                fi
                ;;
            1)
                auth_mode="File"
                ;;
            *)
                log_debug "Error encoutered with user choice on authentication mode, using STANDALONE mode as default"
                auth_mode="File"
                ;;
        esac
        log_milestone "Chosen authentication: mode: $auth_mode, CAS IP: $cas_ip_address"
    done
fi
# 15/10/2014 NSE bz 44552: add of protocol argument, dans tous les cas
appli_path=$ta_launch_protocol://$appli_path

# verifie les parametres saisis ou passés en ligne de commande
if [ ! -z "$auth_mode" ]; then

	if [ "$auth_mode" != "CAS" ] && [ "$auth_mode" != "File" ]; then
		display_error_and_quit "Invalid Authentication mode: $auth_mode"
	fi

        # Mantis 2561: [DE MKT] Reverse Proxy - Prise en compte hostname dans les setups TA
        # plus de vérification du format de l'adresse
fi

# ------------- conf file Generation

# bz25625, change staring to Starting
log_milestone "Starting PAA API conf file Generation"

if [ ! -d "$paa_conf_dir" ]; then
	display_error_and_quit "Could not find directory '$paa_conf_dir'"
fi

modelFile="$paa_conf_dir/$paa_conf_model_file_name"
confFile="$paa_conf_dir/$paa_conf_file_name"
if [ ! -f "$modelFile" ]; then
	display_error_and_quit "Could not find file '$modelFile'"
fi
# si en mode TA on affecte une addresse par default pour avoir un remplacement
if [ "$auth_mode" == "File" ]; then
	cas_ip_address="0.0.0.0"
fi
log_debug "generating file $confFile from $modelFile with mode: $auth_mode and CAS IP: $cas_ip_address "
if [ -f "$confFile" ]; then
    # 23/02/2012 NSE DE Astellia Portal Lot2
	# récupération du Guid s'il existe
        log_milestone "Saved Guids"
	guid_hexa=$(cat $confFile | grep APPLI_GUID_HEXA | cut -d"," -f2 | sed "s/[ ;')]//g")
	guid_appli=$(cat $confFile | grep APPLI_GUID_NAME | cut -d"," -f2 | sed "s/[ ;')]//g")
        guid_hexa=$(echo $guid_hexa | sed "s/\s//g")
        guid_appli=$(echo $guid_appli | sed "s/\s//g")
        log_milestone "guid_hexa: '$guid_hexa'"
        log_milestone "guid_appli: '$guid_appli'"
	log_debug "Deleting existing configuration file"
	rm -f "confFile"
fi

# 23/02/2012 NSE DE Astellia Portal Lot2
if [ -z "$guid_hexa" ]; then
  log_milestone "Generating new Guids"
  guid_hexa=$(php -r "echo substr(md5(uniqid(rand(), true)),0,12);")
  guid_appli=$(echo $APPNAME | sed 's/[_ &]//g')
  log_milestone "guid_hexa: $guid_hexa"
  log_milestone "guid_appli: $guid_appli"
fi

## lance le sed de generation du fichier cible
sed -e "s/<PAA_SERVICE_TO_REPLACE>/$auth_mode/g" \
	 -e "s/<CAS_SERVER_TO_REPLACE>/$cas_ip_address/g" \
	 -e "s/<TA_NAME_TO_REPLACE>/$APPNAME/g" \
	 -e "s/<APPLI_GUID_HEXA_TO_REPLACE>/$guid_hexa/g" \
	 -e "s/<APPLI_GUID_NAME_TO_REPLACE>/$guid_appli/g" \
	 "$modelFile" > "$confFile"

#  verification du success
if [ ! "$?" -eq 0 ]; then
	display_error_and_quit "error in configuration file generation"
fi
if [ ! -f "$confFile" ]; then
	display_error_and_quit "Could not generate file '$confFile'"
fi

# Modification des droits sur le fichier SQLite
sqlite_dir=$(cat $confFile | grep "'SQLITE_DIR'" | cut -d"," -f2 | sed "s/[ ;')$]//g")
sqlite_session_file=$(cat $confFile | grep SQLITE_SESSION_FILE | cut -d"," -f2 | sed "s/[ ;')$]//g")
sqlite_dir=$(echo $sqlite_dir | sed "s/\s//g")
sqlite_session_file=$(echo $sqlite_session_file | sed "s/\s//g")
if [ ! -z "$sqlite_session_file" ]; then
    chmod 0777 $sqlite_dir;
    chmod 0766 $sqlite_dir$sqlite_session_file;
    log_debug "Modification des droits sur $sqlite_dir et $sqlite_session_file"
else
    display_error_and_quit "Could not update rights on '$sqlite_dir$sqlite_session_file'"
fi

if [ "$auth_mode" == "CAS" ]; then
    # génération du fichier Xml
    log_milestone "Xml Generation and send..."
    ret=$(php -q /home/$APPNAME/scripts/generatePAAXml.php guid_hexa=$guid_hexa guid_appli=$guid_appli appli_path=$appli_path casIp=$cas_ip_address 2>&1)
    if [ "$ret" == "ok" ]; then
        log_debug "Application registered on CAS with return $ret"
        show_msg "Application registered on CAS."
    else
        log_debug "Application registration on CAS failed with return $ret"
        show_msg "WARNING: 
An error occured during application registration on CAS. 
Please ensure that your CAS is reachable on Ip address you entered ($cas_ip_address). 
You can launch again application registration by running script 'tools/configurePortal.sh'."
    fi
fi

log_milestone "------------ PAA configuration end -----------"
