#!/bin/sh

# ROOT ONLY CAN EXECUTE THIS SCRIPT
user=`whoami`
if [ $user != 'root' ]; then
	echo "You must be root to execute this script"
	exit 127
fi
        
# ARGUMENTS CHECK
if [ $# -lt 2 ]; then
	echo '#######################################################################################################'
	echo '## '$0' : too many or too few arguments'
	echo '## Usage :'
	echo '## sh '$0' [application_path] [database_name] [postgresql_port]'
	echo '########################################################################################################'
	exit 127
fi

# FETCHING ARGUMENTS
rep_install=$1		# install directory
database=$2			# DB name
# 15/04/2011 SCT : add postgresql port
postgresqlPort=$3
# if postgresql port is null, default value is defined
if [ -z "$postgresqlPort" ] ; then
    postgresqlPort=5432
fi

# TESTING INSTALL DIR
if [ ! -d $rep_install ] ; then
	echo "Error : T&A application path does not exists"
	exit 127
fi	

# POSTGRESQL BINARY
# 20/09/2010 NSE bz 17659 : vérification si exécutables = lien symbolique
if [ -f /usr/bin/psql ]; then
    psql_bin=/usr/bin/psql
else
    if [ -f /usr/local/pgsql/bin/psql ]; then
	psql_bin=/usr/local/pgsql/bin/psql
    else
	echo "Error : cannot find Postgresql exe"
	exit 127
    fi
fi

# s'il s'agit d'un lien symbolique, on le suit
# 28/10/2010 OJT : bz17659, modification de la méthode de recherche du nom réel
while [ -h $psql_bin ]; do
    DIR=$(dirname -- "$psql_bin")
    SYM=$(readlink $psql_bin)
    psql_bin=$(cd $DIR && cd $(dirname -- "$SYM") && pwd)/$(basename -- "$SYM")
done
echo $psql_bin

# CHECKING POSTGRESQL LIBRARY DIRECTORY
# 21/11/2012 GFS, BZ 30224 : compatibility with redhat 6.2
if [ -d /usr/local/pgsql/lib ] ; then
	repertoire_so=/usr/local/pgsql/lib/
elif [ -d /usr/lib64/pgsql/ ] ; then
	repertoire_so=/usr/lib64/pgsql/
elif [ -d /usr/pgsql-9.1/lib ] ; then
	repertoire_so=/usr/pgsql-9.1/lib/
	repertoire_sql=/usr/pgsql-9.1/share/contrib/		
else
		if [ -d /usr/pgsql-9.1/lib/ ] ; then
			repertoire_so=/usr/pgsql-9.1/lib/
		else
			echo "Error : cannot find Postgresql library directory"
			exit 127
		fi
fi

# GETTING POSTGRESQL BINARY ARCHITECTURE
archi=`file $psql_bin | sed -e "s/\(.*\)ELF \([0-9][0-9]\)-bit LSB executable\(.*\)/\2/"`

# TESTING ARCHITECTURE
if [ $archi != "32" ] ; then
	if [ $archi != "64" ] ; then
		if [ $archi != "128" ] ; then
			echo "Error : wrong architecture ($archi bits)"
			exit 127
		fi
	fi
fi

# GETTING POSTGRESQL VERSION
version=`psql --version | head -n1 | sed -r "s/(.*)[^0-9\.]([0-9]+\.[0-9]+).*/\2/"`

# COPYING FILES AND INSTALLING PATCH
if [ -d $rep_install'/modules/dblink/lib/' ] ; then

	# TESTING VERSION
	if [ ! -f $rep_install'/modules/dblink/lib/dblink.'$version'_'$archi'.so' ] ; then
		echo "Error : Postgresql version $version ($archi bits) is not currently supported"
		exit 127
	fi
	# 19/12/2013 GFS - Bug 38655 - [SUP][T&A Gateway][#38608][Tunisiana] : Wrong dblink installation
	if [ $version = "8.2" ] ; then
		cp -f $rep_install'/modules/dblink/lib/dblink.'$version'_'$archi'.so' $repertoire_so'dblink.so'
	fi
		
	# 19/12/2013 GFS - Bug 38655 - [SUP][T&A Gateway][#38608][Tunisiana] : Wrong dblink installation
	$psql_bin -U postgres $database -p $postgresqlPort -f $rep_install'/modules/dblink/lib/dblink.sql'
else
	echo "Error : cannot find /modules/dblink/lib/ directory"
	exit 127
fi
