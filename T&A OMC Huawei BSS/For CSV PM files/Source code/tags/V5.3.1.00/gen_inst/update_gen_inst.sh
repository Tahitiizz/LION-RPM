#! /bin/bash

# Declaretion des variables
svn_url='http://192.168.1.248/svn/TA/TOOLS/gen_inst/tags/'
maxSvnVersion=0
WORKING_DIR=$PWD

# root ?
if [ "$(id -u)" != "0" ]; then
	MSG="Usage: sh $0 [appname dbname key]

You must be root to run this script"
	echo "$MSG" "Error"
	exit
fi

DIALOG=gdialog
[ -z $DISPLAY ] && DIALOG=dialog

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

# # information
show_msg "This script will update T&A Package Generator with the last SVN official version." "Info"

# username
exec 3>&1
username=$($DIALOG --clear --title "Connection to SVN server" \
    --inputbox "Please enter your login for connection to SVN server" \
     10 70 "" 2>&1 >&3)
exec 3>&-
if [ -z "$username" ]; then
    show_msg "You have cancelled T&A installation. You can relaunch this script now." "Error"
    exit
fi

# password
exec 3>&1
password=$($DIALOG --clear --title "Connection to SVN server" \
    --passwordbox "Please enter your password for connection to SVN server\nDon't worry : your password will be hidden while you're typing. " \
     10 70 "" 2>&1 >&3)
exec 3>&-
if [ -z "$password" ]; then
    show_msg "You have cancelled T&A installation. You can relaunch this script now." "Error"
    exit
fi

# current Package Generator version
currentVersion=$(grep "VERSION=" $WORKING_DIR/* | head -1 | awk -F '"' '{print $2}')
if [ -z "$currentVersion" ] ; then
    show_msg "Can't find current version of T&A Package Generator in this directory." "Error"
    exit
fi

# Determination de la derniere version du tag de gen_inst
resultSvn=$(svn list --no-auth-cache --non-interactive --username $username --password $password $svn_url 2>&1)
if [ $(echo $resultSvn | grep 'authorization failed' | wc -l) -gt 0 ] ; then
    show_msg "Connection to SVN server failed" "Error"
    exit
fi

for i in $resultSvn
do
    # 11:43 26/07/2011 SCT : check first caracter of $i in order to verify Quick Install full version and not patch
    if [ "$( echo ${i:0:1} | grep '[0-9]' > /dev/null && echo '1' || echo '0' )" -eq 1 ] ; then
        # Suppression du dernier caractere du tag svn de gen_inst (e.g. 2.10/)
        i=`echo $i | awk '{ print substr($1,0,length($1)-1)}'`
        iRealName=$i
        # in case of $i length is only 3, add a "0" between "." and last digit (i.e "2.8")
        if [ ${#i} -eq 3 ] ; then
            i=${i:0:2}"0"${i:2:1}
        fi
        # Stockage de la derniere version du tag
        if [ "$( echo "$maxSvnVersion < $i" | bc )" -eq 1 ] ; then
            maxSvnVersion=$i
            maxSvnVersionRealName=$iRealName
        fi
    fi
done

# in case of $currentVersion length is only 3, add a "0" between "." and last digit (i.e "2.8")
currentVersionRealName=$currentVersion
if [ ${#currentVersion} -eq 3 ] ; then
    currentVersion=${currentVersion:0:2}"0"${currentVersion:2:1}
fi

# check current version Vs. latest official version
if [ $( echo "$currentVersion < $maxSvnVersion" | bc ) -eq 1 ] ; then
    $DIALOG --clear --title "Confirm T&A Package Generator update" \
        --yesno "Your current version : ${currentVersionRealName}\nLast official version : ${maxSvnVersionRealName}\nDo you want to upgrade your version to the last official one ?" \
         7 70
    confirmDownload=$?
    if [ "$confirmDownload" -eq 0 ] ; then
        # create temp directory
        tempDirectory=/tmp/TnAPackageGeneratorUpdate
        svn export --no-auth-cache --username $username --password $password $svn_url$maxSvnVersionRealName $tempDirectory > /dev/null

        # add only new files in source directory
        cp -R -i --reply=no $tempDirectory/source .
        # remplace SH files
        for file in ${tempDirectory}/*
        do
            if [ -f "$file" ]; then
                # in case of "conf" file
                if [ ${file: -8} = "gen.conf" ] ; then
                    # gen.conf backup already exists
                    if [ -f gen.conf.bck ] ; then
                        for tempFile in $(seq 0 10)
                        do 	
                            if [ ! -f gen.conf.bck.$tempFile ] ; then
                                mv gen.conf gen.conf.bck.$tempFile
                                genConfFileBackup="gen.conf.bck.${tempFile}"
                                break
                            fi
                        done
                    else
                        mv gen.conf gen.conf.bck
                        genConfFileBackup="gen.conf.bck"
                    fi
                fi
                cp -i --reply=yes $file .
            fi    
        done
        # 11:25 26/07/2011 SCT : specify directory to update
        cp -i --reply=yes ${tempDirectory}/source/*.sh source/
        cp -i --reply=yes ${tempDirectory}/source/quick_install_tools/* source/quick_install_tools/

        # delete temp directory
        rm -rf ${tempDirectory}

        MSG="Your T&A Package Generator version have been updated."
        if [ ! -z $genConfFileBackup ] ; then
            MSG=$MSG"\nOld 'gen.conf' file is backuped as '$genConfFileBackup'."
            # 12:10 06/09/2011 SCT : replace values in new gen.conf file
            source $genConfFileBackup
            produit=${produit/&/\\&}
            produit_label=${produit_label/&/\\&}
            default_appname=${default_appname/&/\\&}
            produit_code=${produit_code/&/\\&}
            ver=${ver/&/\\&}
            verp=${verp/&/\\&}
            install_order=${install_order/&/\\&}
            code_module=${code_module/&/\\&}
            prop_produit_nom_long=${prop_produit_nom_long/&/\\&}
            prop_produit_nom_court=${prop_produit_nom_court/&/\\&}
            prop_user_manual=${prop_user_manual/&/\\&}
            prop_admin_manual=${prop_admin_manual/&/\\&}
            sed -i -e "s/@@PRODUIT@@/${produit}/" \
                -e "s/@@PRDT_LABEL@@/${produit_label}/" \
                -e "s/@@DEFAULT_APPNAME@@/${default_appname}/" \
                -e "s/@@PRDT_CODE@@/${produit_code}/" \
                -e "s/@@VER_PRDT@@/${ver}/" \
                -e "s/@@VER_PARSER@@/${verp}/" \
                -e "s/@@INSTALL_ORDER@@/$install_order/" \
                -e "s/@@CODE_MODULE@@/${code_module}/" \
                -e "s/@@PROP_PRDT_NOM_LONG@@/${prop_produit_nom_long}/" \
                -e "s/@@PROP_PRDT_NOM_COURT@@/${prop_produit_nom_court}/" \
                -e "s/@@PROP_USER_MANUAL@@/${prop_user_manual}/" \
                -e "s/@@PROP_ADMIN_MANUAL@@/${prop_admin_manual}/" \
				-e "s/@@MIN_PREVIOUS_PRODUCT_VERSION@@/${min_previous_product_version}/" \
                gen.conf
            MSG=$MSG"\nOld 'gen.conf' file has been inserted into new backuped 'gen.conf' file. Just check file content."
        fi
        show_msg "$MSG" "Info"
        exit
    else
        show_msg "You have chosen to abort updating your T&A Package Generator current version. You can relaunch this script now." "Error"
        exit
    fi
else
    show_msg "Your T&A Package Generator current version is already up to date." "Info"
    exit
fi