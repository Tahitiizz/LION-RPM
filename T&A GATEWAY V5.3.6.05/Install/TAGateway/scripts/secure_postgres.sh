#!/bin/bash

# ==== VARS DEFINE ====
postgresqlLogin="postgresta"
postgresqlEncryptedPassword="U2FsdGVkX19ye15MXOCN1e3TNtI27QkUwWjhP22En90="
postgresPasskey="56ngo8UYPDmjaEWewk2G7B"
pg_data_dir="/usr/local/pgsql/data/pgdata"
postgresqlDefaultPort="5432"
archive=secure_postgres.tar.gz
timestampLog=$(date +%Y.%m.%d-%H.%M)

# ==== FUNCTIONS ====

# check if postgresql connection is valid by using the OLD postgresql login
check_old_postgresql_connection() {
    resultTestConnectionPostgresql=$($psql_bin -U postgres -p $postgresqlPort -L $LOG -d template1 -tAc "SELECT pg_database_size('template1');" 2>&1) >> $LOG
    connectionState=0
    if [ "$(echo $resultTestConnectionPostgresql | grep "^[ [:digit:] ]*$")" ] ; then
        connectionState=1
    fi
    return 0
}

# check if postgresql connection is valid by using the NEW postgresql login
check_postgresql_connection() {
    resultTestConnectionPostgresql=$(env PGPASSWORD=$postgresqlPassword $psql_bin -U $postgresqlLogin -p $postgresqlPort -L $LOG -d template1 -tAc "SELECT pg_database_size('template1');" 2>&1) >> $LOG
    connectionState=0
    if [ "$(echo $resultTestConnectionPostgresql | grep "^[ [:digit:] ]*$")" ] ; then
        connectionState=1
    fi
    return 0
}

# exit the script
clean_exit() {
	txtRed='\e[0;31m'
	txtNormal='\033[0m'
	echo -e "Script log is available here :${txtRed} ${LOG} ${txtNormal}"
	exit $1
}

# log message in log file and on screen
# $1 : message
log() {
	#echo $1 | tee -a $LOG
	echo "$1" >> $LOG
}

# ==== SCRIPT ====
WORKING_DIR=$PWD
LOG=$WORKING_DIR/log_secure_postgres_${timestampLog}.log


usage="$(basename "$0") [-h] [-p n] [-q] [-u] -- script to set PosgreSQL security

where:
    -h  show this help text
    -p  set the PostgreSQL port (default: $postgresqlDefaultPort)
    -q  don't ask confirmation
    -u  remove PostgreSQL security"
while getopts ":p:hqu" opt; do
	case $opt in
	h)
		echo "$usage"
		exit
		;;
	p)
		postgresqlPort=$OPTARG
		;;
	q)
		quiet=true
		;;
	u)
		action="unsecure"
		;;
    \?)
		printf "illegal option: -%s\n" "$OPTARG" >&2
		echo "$usage" >&2
		exit 1
		;;
  esac
done

if [ "$(id -u)" != "0" ]; then
	log "Fatal error: You must be root to run this script"
	echo "Fatal error: You must be root to run this script"
	clean_exit 1
fi

# Psql Bin
psql_bin="/usr/local/pgsql/bin/psql"
if [ ! -f "/usr/local/pgsql/bin/psql" ] ; then
	psql_bin="/usr/bin/psql"
fi

if [ -z "$postgresqlPort" ] ; then
    postgresqlPort=$postgresqlDefaultPort
fi
log "Using PostgreSQL port $postgresqlPort"

# Load new account password
postgresqlPassword=$(echo $postgresqlEncryptedPassword | openssl aes-256-ecb -d -a -salt -k "$postgresPasskey")

#Check that the new PostgreSQL account exists and create it if it doesn't
check_postgresql_connection #try to connect using the new postgresql account
if [ "$connectionState" -eq 1 ] ; then
    log "Connection to database with the user $postgresqlLogin succeed" 
else
	log "Connection to database with the user $postgresqlLogin failed"
	check_old_postgresql_connection #try to connect using the old postgresql account
	if [ "$connectionState" -eq 1 ] ; then
		log "Connection with PostgreSQL default account succeed"
	
		# It isn't possible to log in using the new PostgreSQL account
		log "PostgreSQL default account is still being used" 
		log "Creating PostgreSQL account '$postgresqlLogin'"
		# Create the new PostgresSQL account (by using the default PostgreSQL account)
		tmp=$($psql_bin -U postgres -p $postgresqlPort -tAc "CREATE ROLE $postgresqlLogin WITH SUPERUSER CREATEDB CREATEROLE INHERIT LOGIN ENCRYPTED PASSWORD '$postgresqlPassword';")
		# Check that we can use the new PostgreSQL account
		check_postgresql_connection
		if [ "$connectionState" -eq 1 ] ; then
			log "Connection to database with the user $postgresqlLogin succeed"
		else
			log "Fatal error: Error while creating PostgreSQL account '$postgresqlLogin'"
			echo "Fatal error: Error while creating PostgreSQL account '$postgresqlLogin'"
			clean_exit 1
			
		fi
	else
		log "Fatal error: Connection with PostgreSQL default account failed"
		echo "Fatal error: Connection with PostgreSQL default account failed"
		clean_exit 1
	fi
fi
log "Login $postgresqlLogin is OK"

if [ "$action" == "unsecure" ] ; then
	log "Removing PostgreSQL security"
	cat > $pg_data_dir/pg_hba.conf <<EOF
# PostgreSQL Client Authentication Configuration File
# ===================================================
#
# Refer to the "Client Authentication" section in the PostgreSQL
# documentation for a complete description of this file.  A short
# synopsis follows.
#
# This file controls: which hosts are allowed to connect, how clients
# are authenticated, which PostgreSQL user names they can use, which
# databases they can access.  Records take one of these forms:
#
# local      DATABASE  USER  METHOD  [OPTIONS]
# host       DATABASE  USER  ADDRESS  METHOD  [OPTIONS]
# hostssl    DATABASE  USER  ADDRESS  METHOD  [OPTIONS]
# hostnossl  DATABASE  USER  ADDRESS  METHOD  [OPTIONS]
#
# (The uppercase items must be replaced by actual values.)
#
# The first field is the connection type: "local" is a Unix-domain
# socket, "host" is either a plain or SSL-encrypted TCP/IP socket,
# "hostssl" is an SSL-encrypted TCP/IP socket, and "hostnossl" is a
# plain TCP/IP socket.
#
# DATABASE can be "all", "sameuser", "samerole", "replication", a
# database name, or a comma-separated list thereof.
#
# USER can be "all", a user name, a group name prefixed with "+", or a
# comma-separated list thereof.  In both the DATABASE and USER fields
# you can also write a file name prefixed with "@" to include names
# from a separate file.
#
# ADDRESS specifies the set of hosts the record matches.  It can be a
# host name, or it is made up of an IP address and a CIDR mask that is
# an integer (between 0 and 32 (IPv4) or 128 (IPv6) inclusive) that
# specifies the number of significant bits in the mask.  A host name
# that starts with a dot (.) matches a suffix of the actual host name.
# Alternatively, you can write an IP address and netmask in separate
# columns to specify the set of hosts.  Instead of a CIDR-address, you
# can write "samehost" to match any of the server's own IP addresses,
# or "samenet" to match any address in any subnet that the server is
# directly connected to.
#
# METHOD can be "trust", "reject", "md5", "password", "gss", "sspi",
# "krb5", "ident", "peer", "pam", "ldap", "radius" or "cert".  Note that
# "password" sends passwords in clear text; "md5" is preferred since
# it sends encrypted passwords.
#
# OPTIONS are a set of options for the authentication in the format
# NAME=VALUE.  The available options depend on the different
# authentication methods -- refer to the "Client Authentication"
# section in the documentation for a list of which options are
# available for which authentication methods.
#
# Database and user names containing spaces, commas, quotes and other
# special characters must be quoted.  Quoting one of the keywords
# "all", "sameuser", "samerole" or "replication" makes the name lose
# its special character, and just match a database or username with
# that name.
#
# This file is read on server startup and when the postmaster receives
# a SIGHUP signal.  If you edit the file on a running system, you have
# to SIGHUP the postmaster for the changes to take effect.  You can
# use "pg_ctl reload" to do that.

# Put your actual configuration here
# ----------------------------------
#
# If you want to allow non-local connections, you need to add more
# "host" records.  In that case you will also need to make PostgreSQL
# listen on a non-local interface via the listen_addresses
# configuration parameter, or via the -i or -h command line switches.

# CAUTION: Configuring the system for local "trust" authentication
# allows any local user to connect as any PostgreSQL user, including
# the database superuser.  If you do not trust all your local users,
# use another authentication method.


# TYPE  DATABASE        USER            ADDRESS                 METHOD

# "local" is for Unix domain socket connections only
local   all             postgres                                     trust
local   all             read_only_user                                     trust
local   all             all                                     md5
# IPv4 local connections:
host    all             postgres             127.0.0.1/32            trust
host    all             read_only_user             127.0.0.1/32            trust
host    all             all             127.0.0.1/32            md5
# IPv6 local connections:
host    all             postgres             ::1/128                 trust
host    all             read_only_user             ::1/128                 trust
host    all             all             ::1/128                 md5
# IPv4 external connections:
host    all             postgres             0.0.0.0/0	            trust
host    all             read_only_user             0.0.0.0/0	            trust
host    all             all             0.0.0.0/0	            md5
EOF
	chown postgres.postgres $pg_data_dir/pg_hba.conf >> $LOG 2>&1
	tmp=$(env PGPASSWORD=$postgresqlPassword $psql_bin -U $postgresqlLogin -p $postgresqlPort -L $LOG -d template1 -tAc "SELECT pg_reload_conf();" 2>&1) >> $LOG
	log "PostgreSQL security removed"
	echo "PostgreSQL security removed"
else
	if [ "$quiet" != true ] ; then
		#ask if the user wants to continue
		echo "Before enabling the new PostgreSQL security, you must check that every T&A products installed on the server only use the new PostgresSQL account:"
		echo "* For each T&A product installed on the server, check that the product is configured to use the new PostgreSQL account."
		echo "* For each T&A gateway installed on the server, check that every products registered on the gateway are configured to use the new PostgreSQL account."
		read -r -p "Install the new PostgreSQL security? [y/N] " response
		case $response in
			[yY][eE][sS]|[yY]) 
				log "User confirmed the installation of PostgreSQL security"
				;;
			*)
				log "Fatal error: User cancelled the installation of PostgreSQL security"
				echo "Fatal error: User cancelled the installation of PostgreSQL security"
				clean_exit 1
				;;
		esac
	fi

	log "Setting PostgreSQL security"
	cat > $pg_data_dir/pg_hba.conf <<EOF
# PostgreSQL Client Authentication Configuration File
# ===================================================
#
# Refer to the "Client Authentication" section in the PostgreSQL
# documentation for a complete description of this file.  A short
# synopsis follows.
#
# This file controls: which hosts are allowed to connect, how clients
# are authenticated, which PostgreSQL user names they can use, which
# databases they can access.  Records take one of these forms:
#
# local      DATABASE  USER  METHOD  [OPTIONS]
# host       DATABASE  USER  ADDRESS  METHOD  [OPTIONS]
# hostssl    DATABASE  USER  ADDRESS  METHOD  [OPTIONS]
# hostnossl  DATABASE  USER  ADDRESS  METHOD  [OPTIONS]
#
# (The uppercase items must be replaced by actual values.)
#
# The first field is the connection type: "local" is a Unix-domain
# socket, "host" is either a plain or SSL-encrypted TCP/IP socket,
# "hostssl" is an SSL-encrypted TCP/IP socket, and "hostnossl" is a
# plain TCP/IP socket.
#
# DATABASE can be "all", "sameuser", "samerole", "replication", a
# database name, or a comma-separated list thereof.
#
# USER can be "all", a user name, a group name prefixed with "+", or a
# comma-separated list thereof.  In both the DATABASE and USER fields
# you can also write a file name prefixed with "@" to include names
# from a separate file.
#
# ADDRESS specifies the set of hosts the record matches.  It can be a
# host name, or it is made up of an IP address and a CIDR mask that is
# an integer (between 0 and 32 (IPv4) or 128 (IPv6) inclusive) that
# specifies the number of significant bits in the mask.  A host name
# that starts with a dot (.) matches a suffix of the actual host name.
# Alternatively, you can write an IP address and netmask in separate
# columns to specify the set of hosts.  Instead of a CIDR-address, you
# can write "samehost" to match any of the server's own IP addresses,
# or "samenet" to match any address in any subnet that the server is
# directly connected to.
#
# METHOD can be "trust", "reject", "md5", "password", "gss", "sspi",
# "krb5", "ident", "peer", "pam", "ldap", "radius" or "cert".  Note that
# "password" sends passwords in clear text; "md5" is preferred since
# it sends encrypted passwords.
#
# OPTIONS are a set of options for the authentication in the format
# NAME=VALUE.  The available options depend on the different
# authentication methods -- refer to the "Client Authentication"
# section in the documentation for a list of which options are
# available for which authentication methods.
#
# Database and user names containing spaces, commas, quotes and other
# special characters must be quoted.  Quoting one of the keywords
# "all", "sameuser", "samerole" or "replication" makes the name lose
# its special character, and just match a database or username with
# that name.
#
# This file is read on server startup and when the postmaster receives
# a SIGHUP signal.  If you edit the file on a running system, you have
# to SIGHUP the postmaster for the changes to take effect.  You can
# use "pg_ctl reload" to do that.

# Put your actual configuration here
# ----------------------------------
#
# If you want to allow non-local connections, you need to add more
# "host" records.  In that case you will also need to make PostgreSQL
# listen on a non-local interface via the listen_addresses
# configuration parameter, or via the -i or -h command line switches.

# CAUTION: Configuring the system for local "trust" authentication
# allows any local user to connect as any PostgreSQL user, including
# the database superuser.  If you do not trust all your local users,
# use another authentication method.


# TYPE  DATABASE        USER            ADDRESS                 METHOD

# "local" is for Unix domain socket connections only
local   all             postgres                                     reject
local   all             all                                     md5
# IPv4 local connections:
host    all             postgres             127.0.0.1/32            reject
host    all             all             127.0.0.1/32            md5
# IPv6 local connections:
host    all             postgres             ::1/128                 reject
host    all             all             ::1/128                 md5
# IPv4 external connections:
host    all             postgres             0.0.0.0/0	            reject
host    all             all             0.0.0.0/0	            md5
EOF
	chown postgres.postgres $pg_data_dir/pg_hba.conf >> $LOG 2>&1
	tmp=$(env PGPASSWORD=$postgresqlPassword $psql_bin -U $postgresqlLogin -p $postgresqlPort -L $LOG -d template1 -tAc "SELECT pg_reload_conf();" 2>&1) >> $LOG
	log "PostgreSQL security set"
	echo "PostgreSQL security set"
fi

clean_exit 0

