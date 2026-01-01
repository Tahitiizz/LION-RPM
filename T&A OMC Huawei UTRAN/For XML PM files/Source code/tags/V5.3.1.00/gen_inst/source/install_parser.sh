#!/bin/bash
BASE=$PWD
# 09:55 01/07/2011 SCT : add minutes in parser date install
date=$(date +%Y_%m_%d_%H_%M)
date_context=$(date +%Y%m%d)

parser_version="@@PARSER_VERSION@@"
parser_name="@@PARSER_NAME@@"   ## = nom du module
product_name="@@PRODUCT_NAME@@" ## Peut etre different du module (cas gb<>gprs)
code_module="@@MODULE@@"

file_source=$product_name"_v"$parser_version".tar.gz"

# 09:45 07/09/2011 SCT : alternate psql_bin for Postgresql 9.1
psql_bin="/usr/local/pgsql/bin/psql"
if [ ! -f "/usr/local/pgsql/bin/psql" ] ; then
	psql_bin="/usr/bin/psql"
fi

# root ?
if [ "$(id -u)" != "0" ]
then
	echo "You must be root to run this script"
        exit
fi

# le 5eme parametre est optionnel
if [ $# -ne 4 ] && [ $# -ne 5 ] ; then
	echo '###################################################################################################'
	echo '## $0 : too many or too few arguments'
	echo '## Usage :'
	echo '## sh $0 [application_path] [database_name] [source_path] [user] [context_path]'
	echo '###################################################################################################'
	exit 127
fi

#######################
#
#	INITIALISATION
#
#######################

# Vérification du répertoire d'install
rep_install=$1
if [ ! -d "$rep_install" ]
then
	echo '###################################################################################################'
	echo '## !Application Directory '$rep_install' does not exist'
	echo '###################################################################################################'
	exit 127
fi

# Vérification de la présence de la base de données
database=$2
base_exists=$($psql_bin -U postgres template1 -c "\l" | grep $database | wc -l)
if [ "$base_exists" == "0" ]
then
	echo '###################################################################################################'
	echo '## !Database '$database' does not exist'
	echo '###################################################################################################'
	exit 127
fi

# Vérification du répertoire contenant l'install
rep_source=$3
if [ ! -d "$rep_source" ]
then
	echo '###################################################################################################'
	echo '## !file source directory '$rep_source' does not exist'
	echo '###################################################################################################'
	exit 127
fi

# Vérification de la présence du tar contenant les sources
if [ ! -f "$rep_source/$file_source" ]
then
	echo '###################################################################################################'
	echo '## !file source '$rep_source/$file_source' does not exist'
	echo '###################################################################################################'
	exit 127
fi

user=$4
owner="${user}.${user}"

if [ $# = 5 ]
then
	file_context=$5
	if [ ! -f "$file_context" ]
	then
		echo '###################################################################################################'
		echo '## !file context '$file_context' does not exist'
		echo '###################################################################################################'
		exit 127
	fi
fi

SQL="$psql_bin -U postgres -d $database -tAc "
LOG="$rep_install/SQL/log_$parser_name$parser_version.txt"
cat /dev/null > $LOG

#######################
#
#       VERIFICATION DES PARAMETRES PARSER AVANT INSTALLATION
#
#######################

# Verification que le parser est compatible
parser_install=$($SQL "SELECT item_value FROM sys_versioning where item='parser_name';" | wc -l)
if [ "$parser_install" == "0" ]
then
	type_installation="install"
	echo "-> Process mode : installation"

	# Vérification de présence du contexte
#	if [ ! -f "$file_context" ]
#	then
#		echo '###################################################################################################'
#		echo '## !file context '$file_context' does not exist'
#		echo '###################################################################################################'
#		exit 127
#	fi
else
	type_installation="update"
	echo "-> Process mode : update existing product"

	# Verification que le parser installe est bien du meme type que celui-ci
	parser_compatible=$($SQL "SELECT item_value FROM sys_versioning WHERE item='parser_name' AND item_value='$parser_name';" | wc -l)
	if [ "$parser_compatible" == "0" ]
	then
		echo '###################################################################################################'
		echo '## !Parser '$parser_name' not compatible'
		echo '###################################################################################################'
		exit 127
	fi

	# Verification que cette version de parser n'a pas deja ete installee
	parser_compatible=$($SQL "SELECT item_value FROM sys_versioning WHERE item='parser_version' AND item_value='$parser_version';" | wc -l)
	if [ "$parser_compatible" == "1" ]
	then
		echo '###################################################################################################'
		echo '## !Parser '$parser_name' ('$parser_version') already installed '
		echo '###################################################################################################'
		exit 127
	fi
fi

#######################
#
#	INSTALLATION (OU MISE A JOUR) DU PRODUIT
#
#######################

# Extraction du tar contenant les sources
echo "-> Extracting files"
# 17:45 17/05/2010 SCT : On va transformer les anciens fichiers SQL présents dans le répertoire parser afin qu'ils ne soient pas exécutés de nouveau
# On va dans le repertoire SQL du parser et on execute tous les fichiers ".sql"
if [ -d $rep_install/parser/$parser_name/SQL ]; then
	cd $rep_install/parser/$parser_name/SQL
elif [ -d $rep_install/parser/$parser_name/sql ]; then
	cd $rep_install/parser/$parser_name/sql
fi
if [ "$(ls *.sql 2>/dev/null| wc -l)" != "0" ]; then
	for sql_file in *.sql
	do
		mv $sql_file $sql_file".done"
	done
fi
# 17:45 17/05/2010 SCT : End modif
cd $rep_install/parser/
tar xzf $BASE/$file_source
	
#
#	SELECT DU MODE D'INSTALLATION
#
	
# Mise à jour de sys_global paramters dans le cas de l'installation (exécuté maintenant pour installation du contexte ensuite)
if [ "$type_installation" = "install" ]
then
	$SQL "UPDATE sys_global_parameters SET value='"$parser_name"' WHERE parameters='module';" >> $LOG

	# Dans le cas d'une installation, on efface toutes les references au parser qu'on va installer :
	# -> saai_interface et saai_module, car on peut avoir changer le nom ou le code du module
	# -> on insere le nouveau nom et code du parser
	$SQL "DELETE FROM sys_aa_interface WHERE saai_interface='$code_module' OR saai_module='$parser_name';" >> $LOG
	$SQL "INSERT INTO sys_aa_interface(saai_interface, saai_module) VALUES ("$code_module",'"$parser_name"');" >> $LOG
	
	# Ajout du code et nom de module dans la table sys_aa_interface
	module_existe=$($SQL "SELECT * FROM sys_aa_interface WHERE saai_interface='$code_module' AND saai_module='$parser_name';" | wc -l)
	if [ "$module_existe" = "0" ]
	then
		$SQL "INSERT INTO sys_aa_interface(saai_interface, saai_module) VALUES ("$code_module",'"$parser_name"');" >> $LOG
	fi
fi

# PARTIE LIBRE DES MODIFICATIONS DE NA AVANT PASSAGE DU CONTEXTE
#if [ $type_installation = "update" ]
#then
#fi
# FIN PARTIE LIBRE

# installation du contexte
if [ -f "$file_context" ]
then
	echo "-> Installing context"
	php $rep_install/context/php/context_install_sh.php $file_context true
	fichier_log=$(echo "$file_context" | sed "s/^.*\/\([^\/]*\)\.tar.bz2$/\1/")
	echo -e "\t+ Installing context log file : "$rep_install"/SQL/"$date_context"_"$fichier_log".log"
fi

# procédure UPDATE OU INSTALL
if [ "$type_installation" == "update" ] ; then
	#
	#	MODE UPDATE
	#

	# Fichier de log
	echo "-> Database update"
	echo -e "\t+ Update process log file : $LOG"

	# On va dans le repertoire SQL du parser et on execute tous les fichiers ".sql"
	if [ -d "$rep_install/parser/$parser_name/SQL" ]; then
		cd $rep_install/parser/$parser_name/SQL
	elif [ -d "$rep_install/parser/$parser_name/sql" ]; then
		cd $rep_install/parser/$parser_name/sql
	fi
	if [ "$(ls *.sql 2>/dev/null| wc -l)" != "0" ]; then
		for sql_file in *.sql
		do
			echo -e "\t+ Updating database, executing '$sql_file'"
			cat $sql_file | $psql_bin -U postgres $database -L $LOG 2>&1
			# 17:41 17/05/2010 SCT : on renomme le fichier SQL afin qu'il ne soit pas exécuté de nouveau lors d'un prochain patch
			mv $sql_file $sql_file".done"
		done
	fi
	
	# DEPLOY (pour le déploiement des nouveaux NA du contexte de migration
	echo -e "\t+ Deploying parser"
	cd $rep_install"/scripts"
	php deploy.php >> $LOG

	# PARTIE LIBRE : PERMET LE REGROUPEMENT DE COMMANDE A EXECUTER PENDANT L'UPDATE DU PARSER EXISTANT
	# SUPPRESSION DE L'ANCIENNE IMAGE PARSER

	# FIN PARTIE LIBRE

else
	#
	#	MODE INSTALL
	#

	echo '-> Installing parser'
	echo -e "\t* Installing process log file : $LOG"
	cd  $rep_install/scripts/

	echo -e "\t* Installing...(few minutes)"
	php parser_install.php >> $LOG

	# Recherche des NOTICES dans le fichier
	alerte_notice=$(grep "NOTICE:" $LOG | wc -l)
	if [ $alerte_notice != "0" ] ; then
		echo -e "\t\t+ NOTICE number : $alerte_notice"
	fi
	# Recherche des WARNING dans le fichier
	alerte_warning=$(grep "WARNING:" $LOG | wc -l)
	if [ $alerte_warning != "0" ] ; then
		echo -e "\t\t+ WARNING number : $alerte_warning"
	fi
	# Recherche des ERROR dans le fichier
	alerte_error=$(grep "ERROR:" $LOG | wc -l)
	if [ $alerte_error != "0" ] ; then
		echo -e "\t\t+ ERROR number : $alerte_error"
	fi

	# On va dans le repertoire SQL du parser et on execute tous les fichiers ".sql"
	if [ -d "$rep_install/parser/$parser_name/SQL" ]; then
		cd $rep_install/parser/$parser_name/SQL
	elif [ -d "$rep_install/parser/$parser_name/sql" ]; then
		cd $rep_install/parser/$parser_name/sql
	fi
	if [ "$(ls *.sql 2>/dev/null| wc -l)" != "0" ]; then
		for sql_file in *.sql
		do
			echo -e "\t+ Updating database, executing '$sql_file'"
			cat $sql_file | $psql_bin -U postgres $database >> $LOG 2>&1
		done
	fi
fi

#
#	AJOUT DANS SYS_VERSIONING
#
echo '-> Updating product version'
$SQL "INSERT INTO sys_versioning (item, item_value, item_mode, date) VALUES ('parser_name','$parser_name','version_de_base','$date');" >> $LOG
$SQL "INSERT INTO sys_versioning (item, item_value, item_mode, date) VALUES ('parser_version','$parser_version','version_de_base','$date');" >> $LOG

#
#	MESSAGE DE FIN
#
echo '##################################################################'
echo '## Parser installation done'
echo '##################################################################'
