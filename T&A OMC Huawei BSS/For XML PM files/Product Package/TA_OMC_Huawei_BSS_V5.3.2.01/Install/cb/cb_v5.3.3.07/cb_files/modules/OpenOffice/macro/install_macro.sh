#!/bin/sh
# This script will setup astellia macros into openoffice 3.1 installation.
user=`whoami`
OOOINSTDIR=/opt/openoffice.org/basis3.1
MACRODIR=$OOOINSTDIR/share/basic
ASTELLIADIR=$MACRODIR/astellia
TAR=OOO_library_v1.0.tar.gz
# Root test
if [ $user = 'root' ]; then
	# Argument test
	if [ $# != "1" ]; then
		echo '###################################################################################################'
		echo '## '$0' : too many or too few arguments'
		echo '## Usage :'
		echo '## sh '$0' [source_path]'
		echo '###################################################################################################'
		exit 127
	fi
	# OOo Test
	if [ ! -d $OOOINSTDIR ] ; then
		echo "Error : OpenOffice is missing !"
		exit 127
	fi
	# First we check if the astellia library is already installed
	if [  -d $ASTELLIADIR ] ; then
		echo '=> Astellia library is already installed.'
		echo 'Installation will update current library.'
	else
		echo '=> Creating astellia library...'
		mkdir $ASTELLIADIR
	fi
	# Is Astellia already referenced ?
	REF=`cat $MACRODIR/script.xlc | grep "astellia" | wc -l`
	if [ $REF != "0" ] ; then
		echo '=> Astellia library is already referenced by OpenOffice config file.'
		echo 'No change will be made to this file.'
	else
		echo '=> Creating module reference...'
		LIBLINE=`echo '<library:library library:name="astellia" xlink:href="file://'$ASTELLIADIR'/script.xlb/" xlink:type="simple" library:link="true" library:readonly="false"/>'`
		cat $MACRODIR/script.xlc | sed -e "s/<\/library:libraries>//g" > $MACRODIR/temp.xlc
		cat $MACRODIR/temp.xlc > $MACRODIR/script.xlc
		rm -f $MACRODIR/temp.xlc
		echo $LIBLINE >> $MACRODIR/script.xlc
		echo "</library:libraries>" >> $MACRODIR/script.xlc
	fi
	# Installing library
	echo '=> Extracting module...'
	cd $ASTELLIADIR
	tar xzvf $1/$TAR
	# END
	echo 'All done.'
else
	echo 'You must be root to execute this script'
fi
