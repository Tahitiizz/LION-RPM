<?php
/**
 * @file basicMasterToGatewayMasterMigration.php
 *
 * Script de migration effectuant la migration d'un multi-produit standard vers
 * un multi-produit avec T&A Gateway.
 * Le script est décomposé en 3 parties: la vérification des paramètres, le backup
 * des tables de données de l'ancien master et la migration en elle même.
 *
 * 14/11/2011 : bz24589, gestion des paramètres optionnels (port et user)
 * 09/12/2011 ACS Mantis 837 Use ProductModel to deploy the definition_setup_product on slaves
 * 
 * $Author: o.jousset $
 * $Date: 2012-01-25 15:15:14 +0100 (mer., 25 janv. 2012) $
 * $Revision: 59230 $
 */
    // Définition de la mise en forme (couleur, gras...)
	define ( 'ENDL'  , "\n" );
    define ( 'BOLD'  , "\033[1m" );
    define ( 'GREEN' , "\033[32m" );
    define ( 'RED'   , "\033[31m" );
    define ( 'ORANGE', "\033[33m" );
    define ( 'END_C' , "\033[0m" );

    define ( 'SCRIPT_USER'          , 'root' );
    define ( 'OUTPUT_FOLDER'        , './taGatewayMigration/'.date( 'Ymd-His' ).'/' );
    define ( 'OUTPUT_FOLDER_BKP'    , OUTPUT_FOLDER."backup/" );
    define ( 'OUTPUT_FILE'          , OUTPUT_FOLDER.'migration_'.date( 'Ymd-His' ).'.log' );
    define ( 'CRONTAB_ASTELLIA'     , '/var/spool/cron/astellia' );
    define ( 'MIN_MASTER_CB_VERSION', '5.1.6.00' ); // bz24564, version minimum mise en 5.1.6

    // Paramètres de connexion par défaut à PostgreSQL
    define ( 'DB_PORT'   , 5432 );
    define ( 'DB_HOST'   , 'localhost' );
    define ( 'DB_USER'   , 'postgres' );
    define ( 'DB_PASSWD' , '' );

    $dbPort = DB_PORT;
    $dbUser = DB_USER;
    $dbPasswd = DB_PASSWD; 
    $taBpDbName      = ''; // Nom de la base T&A Gateway (fourni en argument)
    $taMasterDbName  = ''; // Nom de la base de donnée Matser (fourni en argument)
    $logHandle       = null; // Handle vers le fichier de log
    $productsInfoRes = array(); // Tableau contenant toutes les infos des produits (ainsi qu'un ressources de connexion à la base de données)
    $backupTables    = array( 'sys_pauto_config', 'sys_pauto_page_name', 'users',
                        'menu_deroulant_intranet', 'profile', 'profile_menu_position',
                        'sys_user_group', 'graph_data', 'graph_information',
                        'sys_definition_product', 'sys_definition_selecteur',
                        'sys_report_schedule', 'sys_report_sendmail', 'sys_definition_dashboard' );

    // 08/11/2011 OJT : bz24569, définition d'un timezone (évite les erreurs PHP)
    date_default_timezone_set( exec( 'cat /etc/sysconfig/clock | grep "ZONE" | cut -d"=" -f2 | sed \'s/"//g\'' ));

    system( "clear" ); // Nettoie l'écran avant la première impression
    set_time_limit( 0 ); // Désactive le temps maximum d'exécution d'un script
    error_reporting( E_ALL ); // Initialise le reporting d'erreur PHP au maximum
    set_error_handler( "myErrorHandler" ); // Redirige les erreurs PHP dans le log

    echo BOLD;
    echo "##################################################".ENDL;
    echo "#                                                #".ENDL;
    echo "#       T&A GATEWAY product migration script     #".ENDL;
    echo "#                                                #".ENDL;
    echo "##################################################".ENDL;
    echo END_C;
    
    // Création du fichier de log
    @mkdir( OUTPUT_FOLDER, 0777, true );
    @mkdir( OUTPUT_FOLDER_BKP, 0777, true );
    @touch( OUTPUT_FILE );

    // Lancement des tests préliminaires
    printTitle( 'Checking script arguments and system configuration' );
    if( ( $ret = checkParamAndSystem( $argv, $taBpDbName, $taMasterDbName, $productsInfoRes, $dbPort, $dbUser, $dbPasswd ) ) !== 0 )
    {
        printTextColor( ENDL.ENDL."Errors detected during initialization, please fix problems and restart script".ENDL, RED );
        displayLog();
        die( $ret );
    }

    // Backup des tables concernées par la migration
    printTitle( 'Making database backup before migration' );
    if( ( $ret = backupDatatables( $backupTables, $taMasterDbName, $dbPort, $dbUser, $dbPasswd ) ) !== 0 )
    {
        printTextColor( ENDL.ENDL."Errors detected during backup database tables, please fix problems and restart script".ENDL, RED );
        displayLog();
        die( $ret );
    }

    // Lancement réél de la migration (après confirmation)
    printTitle( 'Starting Master product migration' );
    printTextColor( "please confirm migration start (y/N) ?" );
    if ( trim( fgets( STDIN ) ) === 'y' )
    {
        if( ( $ret = startMigration( $taBpDbName, $taMasterDbName, $productsInfoRes, $dbPort, $dbUser, $dbPasswd ) ) !== 0 )
        {
            printTextColor( ENDL.ENDL."Errors detected during migration, use backup SQL files to restore configuration".ENDL, RED );
            displayLog();
            die( $ret );
        }
    }

    // Demande d'affichage du log d'installation
    displayLog();
    die( 0 ); // On quitte le script sans code d'erreur
?>

<?php
    // Définition des fonctions

    /**
     * Effectuer les tests préalables à une migration
     *
     * @param array $scriptArgv Arguments du script php
     * @param string $taBpDbName Nom de la base Gateway
     * @param string $taMasterDbName Nom de la base Master
     * @param array $productsInfoRes Tableau de ressources de connexion vers les bases (master et slaves)
     * @param string $optPort Numéro de port optionnel pour se conencter à la base (en local)
     * @param string $optUser Login optionnel pour se conencter à la base (en local)
     * @param string $optPasswd Password optionnel pour se conencter à la base (en local)
     * @return integer Zéro si aucun problème
     */
    function checkParamAndSystem( $scriptArgv, &$taBpDbName, &$taMasterDbName, &$productsInfoRes, &$optPort, &$optUser, &$optPasswd )
    {
        $retVal = array();
        
        // Vérification de l'utilisateur
        printSubTitle( 'script user' );
        $user = exec( 'whoami' );
        writeLog( "whoami return {$user}" );
        if( $user !== SCRIPT_USER )
        {
            printKo( "Script must be run by ".SCRIPT_USER." user" );
            $retVal []= 1;
        }
        else
        {
            printOk( "({$user} user)" );
        }

        // Vérification du nombre de paramètres
        printSubTitle( 'number of param' );
        $nbParam = count( $scriptArgv ) - 1;
        writeLog( "cheking number of param....({$nbParam} param found)" );
        
        if( $nbParam < 2 )
        {
            printKo( "Script usage: GatewayDatabaseName MasterDatabaseName [[portNumber] [user] [password]]" );
            $retVal []= 2;
        }
        else
        {
            // Lecture des options facultatives du scripts
            switch ( $nbParam )
            {
				case 5:
                    $optPasswd = $scriptArgv[5];
                    writeLog( "Optional argument found for PostgreSQL password : [{$optPasswd}]");
                    //Suppression volontaire du break;
                case 4:
                    $optUser = $scriptArgv[4];
                    writeLog( "Optional argument found for PostgreSQL user : [{$optUser}]");
                    //Suppression volontaire du break;
                case 3:
                    $optPort = $scriptArgv[3];
                    writeLog( "Optional argument found for PostgreSQL port : [{$optPort}]");
                    break;
                
            }

            printOk( "({$nbParam} params found)" );
        
            // Vérification du premier paramètre (nom de la base Gateway)
            printSubTitle( 'first param' );
            $taBpDbName = $scriptArgv[1];
            $connString = "host=".DB_HOST." port={$optPort} dbname={$taBpDbName} user={$optUser} password={$optPasswd}";
            writeLog( "checking first parameter [{$taBpDbName}]" );
            writeLog( "try to connect with connection string [{$connString}]" );
            if( ( $bdBpRes = @pg_connect( $connString ) ) !== false )
            {
                // Test si il s'agit bien d'un T&A Gateway
                $sql = "SELECT COUNT(parameters) AS nb FROM sys_global_parameters WHERE parameters='module' AND value='bp';";
                writeLog( "is well Gateway product with query: [{$sql}]" );
                if ( ( $res = @pg_query( $sql ) ) !== false )
                {
                    $nb = pg_fetch_array( $res, 0 );
                    writeLog( "nb = {$nb['nb']}" );
                    if( intval( $nb['nb'] ) === 1 )
                    {
                        printOk( "(T&A Gateway)");
                    }
                    else
                    {
                        printKo( "[{$taBpDbName}] database is not a T&A Gateway" );
                        $retVal []= 3;
                    }
                }
                else
                {
                    printSqlKo();
                    $retVal []= 99;
                }
            }
            else
            {
                printKo( "Unable to connect to database [{$taBpDbName}] on localhost" );       
                $retVal []= 3;
            }

            // Vérification du second paramètre
            printSubTitle( 'second param' );
            $taMasterDbName = $scriptArgv[2];
            $connString     = "host=".DB_HOST." port={$optPort} dbname={$taMasterDbName} user={$optUser} password={$optPasswd}";
            writeLog( "checking second parameter [{$taMasterDbName}]" );
            writeLog( "try to connect with connection string [{$connString}]" );
            if( ( $dbMasterRes = @pg_connect( $connString ) ) !== false )
            {
                // Test si il ne s'agit pas d'un T&A Gateway
                $sql = "SELECT COUNT(parameters) AS nb FROM sys_global_parameters WHERE parameters='module' AND value='bp';";
                writeLog( "is well NOT Gateway product with query: [{$sql}]" );
                if ( ( $res = @pg_query( $sql ) ) !== false )
                {
                    $nb = pg_fetch_array( $res, 0 );
                    writeLog( "nb = {$nb['nb']}" );
                    if( intval( $nb['nb'] ) === 0 )
                    {
                        // Test si il s'agit d'un master
                        $sql = "SELECT COUNT(sdp_id) AS nb FROM sys_definition_product WHERE sdp_db_name='{$taMasterDbName}' AND sdp_master=1 AND (SELECT COUNT(sdp_id) FROM sys_definition_product)>1;";
                        writeLog( "is well Master product with query: [{$sql}]" );
                        if ( ( $res = @pg_query( $sql ) ) !== false )
                        {
                            $nb = pg_fetch_array( $res, 0 );
                            writeLog( "nb = {$nb['nb']}" );
                            if( intval( $nb['nb'] ) === 1 )
                            {
                                printOk( "(multiproduct master)" );
                            }
                            else
                            {
                                printKo( "[{$taMasterDbName}] database must refer to a multi-product Master application" );
                                $retVal []= 4;
                            }
                        }
                        else
                        {
                            printSqlKo();
                            $retVal []= 99;
                        }
                    }
                    else
                    {
                        printKo( "[{$taMasterDbName}] database must not refer to a T&A Gateway" );
                        $retVal []= 4;
                    }
                }
                else
                {
                    printSqlKo();
                    $retVal []= 99;
                }
            }
            else
            {
                printKo( "unable to connect to database [{$taMasterDbName}] on localhost" );
                $retVal []= 4;
            }

            if( $bdBpRes )
            {
                // Vérification de l'état du T&A Gateway (doit être en standalone)
                printSubTitle( 'gateway state' );
                $sql = "SELECT * FROM sys_definition_product;";
                writeLog( "is well standalone TA Gateway with query: [{$sql}]" );
                if ( ( $res = @pg_query( $bdBpRes, $sql ) ) !== false )
                {
                    if( pg_num_rows( $res ) === 1 )
                    {                        
                        $productsInfoRes['0']        = pg_fetch_assoc( $res );
                        $productsInfoRes['0']['res'] = $bdBpRes;
                        printOk( "(standalone gateway)" );
                    }
                    else
                    {
                        printKo( "[{$taBpDbName}] is not a standalone T&A Gateway (already migrated ?)" ); 
                        $retVal []= 5;
                    }
                }
                else
                {
                    printSqlKo();
                    $retVal []= 99;
                }
            }

            if( $dbMasterRes )
            {
                // Vérification de l'accessibilité de tous les produits Master/Slaves
                printSubTitle( 'slaves connection' );
                if ( ( $res = @pg_query( $dbMasterRes, "SELECT * FROM sys_definition_product ORDER BY sdp_id ASC" ) ) !== false )
                {
                    $dbNameKo = array();
                    $nbSlaves = pg_num_rows( $res ) - 1;
                    while( $currentProduct = pg_fetch_assoc( $res ) )
                    {
                        $conStr = "host={$currentProduct['sdp_ip_address']} port={$currentProduct['sdp_db_port']} dbname={$currentProduct['sdp_db_name']} user={$currentProduct['sdp_db_login']} password={$currentProduct['sdp_db_password']}\n";
                        writeLog( "trying connection with [{$conStr}]" );
                        if( ( $slaveCon = @pg_connect( $conStr ) ) === false )
                        {
                            $dbNameKo []= $currentProduct['sdp_db_name'];
                        }
                        else
                        {
                            $productsInfoRes[$currentProduct['sdp_id']] = $currentProduct;
                            $productsInfoRes[$currentProduct['sdp_id']]['res'] = $slaveCon;
                        }
                    }

                    // Il y a t-il eu une erreur
                    if( count( $dbNameKo ) > 0 )
                    {
                        printKo( "all slaves must be reachable (problem with batabases ".implode( ',', $dbNameKo )." )" );
                        $retVal []= 8;
                    }
                    else
                    {
                        printOk( "({$nbSlaves} slaves reachable)");
                    }
                }
                else
                {
                    printSqlKo();
                    $retVal []= 99;
                }
                
                // Vérification de l'état des process master/slaves
                printSubTitle( 'processes state' );
                $sql = "SELECT COUNT(master_id) AS nb FROM sys_definition_master WHERE on_off!=0;";
                writeLog( "is all processes stopped with query: [{$sql}]" );

                $nb = 0;
                foreach( $productsInfoRes as $id=>$dbRes )
                {
                    if ( ( $res = @pg_query( $dbRes['res'], $sql ) ) !== false )
                    {
                        $tmpNb = pg_fetch_array( $res, 0 );
                        writeLog( "database [{$dbRes['sdp_db_name']}], nb = {$tmpNb['nb']}" );
                        $nb += intval( $tmpNb['nb'] );                       
                    }
                    else
                    {
                        printSqlKo();
                        $retVal []= 99;
                    }
                }
                if( intval( $nb ) === 0 )
                {
                    printOk( "(all processes off)" );
                }
                else
                {
                    printKo( 'processes must be stopped on all Master/Slaves products' );
                    $retVal []= 6;
                }

                // Vérification de l'état des slaves
                printSubTitle( 'slaves state' );
                $sql = "SELECT COUNT(sdp_id) AS nb FROM sys_definition_product WHERE sdp_on_off=0;";
                writeLog( "is all slaves activate with query: [{$sql}]" );
                if ( ( $res = @pg_query( $dbMasterRes, $sql  ) ) !== false )
                {
                    $nb = pg_fetch_array( $res, 0 );
                    writeLog( "nb = {$nb['nb']}" );
                    if( intval( $nb['nb'] ) === 0 )
                    {
                        printOk( "(all slaves enable)" );
                    }
                    else
                    {
                        printKo( 'all slaves must be activate on Master product' );
                        $retVal []= 7;
                    }
                }
                else
                {
                    printSqlKo();
                    $retVal []= 99;
                }

                // Vérification de l'égibilité de la version du Master
                printSubTitle( 'master cb version' );
                $sql = "SELECT item_value FROM sys_versioning WHERE item='cb_version' ORDER BY id DESC LIMIT 1;";
                writeLog( "is master version OK with query: [{$sql}]" );
                if ( ( $res = @pg_query( $dbMasterRes, $sql ) ) !== false )
                {
                    $version = pg_fetch_array( $res, 0 );
                    writeLog( "version = {$version['item_value']}" );
                    // La version du master doit être supérieur (ou égale) à celle du define
                    if( version_compare( $version['item_value'], MIN_MASTER_CB_VERSION ) >= 0 )
                    {
                        printOk( "({$version['item_value']})" );
                    }
                    else
                    {
                        printKo( 'master base component version must be at least '.MIN_MASTER_CB_VERSION);              
                        $retVal []= 9;
                    }
                }
                else
                {
                    printSqlKo();
                    $retVal []= 99;
                }
            }
        }
        
        // Si une erreur est présente, on retourne le premier code (spécifications)
        if( count( $retVal ) > 0 )
        {
            return $retVal[0];
        }
        return 0;
    }

    /**
     * Effectuer un backup de toutes les tables avant de commencer la migration
     *
     * @param array $listTables Liste des tables à traiter
     * @param string $dbName Nom de la base de données du Master
     * @param string $optPort Numéro de port optionnel pour se conencter à la base (en local)
     * @param string $optUser Login optionnel pour se conencter à la base (en local)
     * @param string $optPasswd Password optionnel pour se conencter à la base (en local)
     * @return integer Zéro si aucun problème
     */
    function backupDatatables( array $listTables, $dbName, $dbPort = DB_PORT, $dbUser = DB_USER, $dbPasswd = DB_PASSWD )
    {
        $retVal = 0;

        // Backup tables par tables
        // 08/11/2011 OJT : bz24567, utilisation de l'option 'inserts' compatible pg 9.1
        foreach( $listTables as $t )
        {
            printSubTitle( $t );

            $output    = array();
            $returnVar = 0;
            $cmd       = "env PGPASSWORD={$dbPasswd} pg_dump -h ".DB_HOST." -p {$dbPort} -U {$dbUser} -a --inserts -f ".OUTPUT_FOLDER_BKP."{$t}.sql -t {$t} {$dbName} &>/dev/null";
            writeLog( "exec command [{$cmd}]" );
            exec( $cmd, $output, $returnVar );
            if( $returnVar === 0 )
            {
                // On vérifie la taille du fichier
                $fS = filesize( OUTPUT_FOLDER_BKP."{$t}.sql" );
                writeLog( OUTPUT_FOLDER_BKP."{$t}.sql => {$fS} bytes" );
                if( $fS > 0 )
                {
                    printOk();
                }
                else
                {
                    printKo( "pg_dump command OK but filesize null [{$t}.sql]" );
                    $retVal = 1;
                }
            }
            else
            {
                printKo( 'pg_dump command error' );
                $retVal = 1;
            }
        }

        // Backup de la cron astellia
        printTextColor( ENDL." - ".str_pad( 'crontab', 25, ' ' )."\t" );
        if( file_exists( CRONTAB_ASTELLIA ) )
        {
            writeLog( "copy crontab to ".OUTPUT_FOLDER_BKP."crontab_astellia" );
            if( copy( CRONTAB_ASTELLIA, OUTPUT_FOLDER_BKP."crontab_astellia" ) )
            {
                printOk();
            }
            else
            {
                printKo( 'unable to copy astellia crontab' );
                $retVal = 1;
            }
        }
        else
        {
            printKo( 'astellia crontab not found' );
            $retVal = 1;
        }
        return $retVal;
    }

    /**
     * Effectue la migration d'un Master vers un Gateway
     * 
     * @param string $taBpDbName Nom de la base Gateway
     * @param array $productsInfoRes Tableau de ressources de connexion vers les base (master et slaves)
     * @param string $optPort Numéro de port optionnel pour se conencter à la base (en local)
     * @param string $optUser Login optionnel pour se conencter à la base (en local)
     * @param string $optPasswd Password optionnel pour se conencter à la base (en local)
     * @return integer Zéro si aucun problème
     */
    function startMigration( $taBpDbName, $taMasterDbName, $productsInfoRes, $dbPort = DB_PORT, $dbUser = DB_USER, $dbPasswd = DB_PASSWD )
    {
        $bdBpRes     = null;
        $retVal      = 0;
        $output      = array();
        $returnVar   = 0;
        $templateCmd = "env PGPASSWORD={$dbPasswd} psql -h ".DB_HOST." -p {$dbPort} -U {$dbUser} -d {$taBpDbName} -f ".OUTPUT_FOLDER_BKP."%s &>/dev/null";

        $connString = "host=".DB_HOST." port={$dbPort} dbname={$taBpDbName} user={$dbUser} password=".DB_PASSWD;
        if( ( $bdBpRes = @pg_connect( $connString ) ) === false )
        {
            printKo( "Unable to connect to database [{$taBpDbName}] on localhost" );
            return 99;
        }

		// 09/12/2011 ACS : Mantis 837 Use ProductModel to deploy the definition_setup_product on slaves
        // 25/01/2011 OJT : bz25361, users and profiles must be deployed on each product
    	$taBpDirectory = "/home/".$productsInfoRes[0]['sdp_directory'];
		include_once($taBpDirectory."/class/Database.class.php");
		include_once($taBpDirectory."/class/DataBaseConnection.class.php");
        include_once($taBpDirectory."/php/edw_function.php");
		include_once($taBpDirectory."/models/ProductModel.class.php");
        include_once($taBpDirectory."/models/UserModel.class.php");
        include_once($taBpDirectory."/models/ProfileModel.class.php");
		
        /**
         * 1- Première étape, migration des Dashboards, Graphs et Rapports
         * 
         * Recopie des tables sys_pauto_page_name, sys_pauto_config, graph_data,
         * graph_information, sys_definition_selecteur, sys_report_schedule,
         * sys_report_sendmail et  sys_definition_dashboard.
         */
        printSubTitle( 'dashs, graphs, reports' );
        $dashGraphTables = array( 'sys_pauto_page_name', 'sys_pauto_config', 'graph_data', 
                                    'graph_information', 'sys_definition_selecteur',
                                    'sys_report_schedule', 'sys_report_sendmail', 'sys_definition_dashboard' );

        // Truncate des tables modifiées (vide initialement sur un T&A Gateway)
        $sql = "TRUNCATE TABLE ".implode( ',', $dashGraphTables );
        if( !@pg_query( $sql ) )
        {
            printKo( 'error while cleaning tables' );
            writeLog( "TRUNCATE tables sys_pauto_page_name,sys_pauto_config,graph_data,graph_information failed" );
            return 99;
        }
  
        // Copie des tables
        foreach( $dashGraphTables as $oneDashGraphTable )
        {
            // La copie des tables s'effectue en utilisant les dumps de l'ancien master
            $cmd       = sprintf( $templateCmd, $oneDashGraphTable.'.sql' );
            $tmpRetCmd = 0;
            writeLog( "exec command [{$cmd}]" );
            exec( $cmd, $output, $tmpRetCmd );
            writeLog( "command return value:[{$tmpRetCmd}]" );
            $returnVar += intval( $tmpRetCmd );
        }

        if( $returnVar === 0 )
        {
            printOk();
        }
        else
        {
            printKo( 'psql error' );
            return 99;
        }

        /**
         * 2- Deuxième étape, migration des Groupes, Utilisateurs et Profiles
         */
        printSubTitle( 'groups, users, profiles' );
        $grpUsrTables = array( 'sys_user_group', 'users', 'profile', 'menu_deroulant_intranet', 'profile_menu_position' );
        $grpUsrProfState = true;
        
        $sql = "BEGIN;"; // Toutes les requêtes sont dans une transaction.
        foreach( $grpUsrTables as $oneGrpUsrTable )
        {
            // Pour toutes les tables, création de table temporaires (*_old) qui
            // vont permettre de n'ajouter que les lignes différentes entre le
            // master et le Gateway (au lieu de tout écraser).
            $sql .= "CREATE TEMP TABLE {$oneGrpUsrTable}_old (LIKE {$oneGrpUsrTable} INCLUDING CONSTRAINTS INCLUDING DEFAULTS);";
            $output    = array();
            $tmpRetCmd = 0;
            exec( "grep -E '^INSERT' ".OUTPUT_FOLDER_BKP."{$oneGrpUsrTable}.sql",$output, $tmpRetCmd );
            $insertSql = implode( ' ', $output );
            $sql .= str_replace( "INSERT INTO {$oneGrpUsrTable}", "INSERT INTO {$oneGrpUsrTable}_old", $insertSql );
        }

        // Requêtes permettant l'ajout des lignes différentes
        $sql .= "INSERT INTO users SELECT * FROM users_old WHERE username NOT IN (SELECT username FROM users);";
        $sql .= "INSERT INTO profile SELECT * FROM profile_old WHERE profile_name NOT IN (SELECT profile_name FROM profile);";
        $sql .= "INSERT INTO sys_user_group SELECT * FROM sys_user_group_old WHERE group_name NOT IN (SELECT group_name FROM sys_user_group);";
        $sql .= "INSERT INTO menu_deroulant_intranet SELECT * FROM menu_deroulant_intranet_old WHERE id_menu NOT IN (SELECT id_menu FROM menu_deroulant_intranet);";

        writeLog( "update users, profiles, groups and menus with:[{$sql}]" );
        if( pg_query( $sql ) !== false )
        {
            // Mise à jour des identifiants utilisteurs
            $sql = "SELECT u.id_user as new,uo.id_user as old FROM users u, users_old uo WHERE u.username=uo.username AND u.id_user != uo.id_user;";            
            if( ( $userRes = pg_query( $sql ) ) !== false )
            {
                $sql = "";
                while ( $row = pg_fetch_assoc( $userRes ) )
                {
                    $sql .= "UPDATE sys_report_sendmail SET mailto='{$row['new']}' WHERE mailto='{$row['old']}';";
                    $sql .= "UPDATE sys_user_group SET id_user='{$row['new']}' WHERE id_user='{$row['old']}';";
                }
                writeLog( "update user ids with:[{$sql}]" );
                $grpUsrProfState &= pg_query( $sql );
            }
            else
            {
                $grpUsrProfState = false;
            }

            // Mise à jour des identifiants profiles
            $sql = "SELECT p.id_profile as new,po.id_profile as old FROM profile p, profile_old po WHERE p.profile_name=po.profile_name AND p.id_profile != po.id_profile;";            
            if( ( $profRes = pg_query( $sql ) ) !== false )
            {
                $sql = "";
                while ( $row = pg_fetch_assoc( $profRes ) )
                {
                   $sql .= "UPDATE users SET user_profil='{$row['new']}' WHERE user_profil='{$row['old']}';";
                   $sql .= "UPDATE profile_menu_position_old SET id_profile='{$row['new']}' WHERE id_profile='{$row['old']}';";
                   $sql .= "INSERT INTO profile_menu_position SELECT * FROM profile_menu_position_old WHERE id_menu||id_profile NOT IN (SELECT id_menu||id_profile FROM profile_menu_position);";
                }
                writeLog( "update profile ids with:[{$sql}]" );
                $grpUsrProfState &= pg_query( $sql );
            }
            else
            {
                $grpUsrProfState = false;
            }

            // Reconstruction des 'profile_to_menu' pour chaque profile
            $sql = "SELECT DISTINCT id_profile FROM profile;";
            if( ( $profRes = pg_query( $sql ) ) !== false )
            {
                $sql = "";
                while ( $row = pg_fetch_assoc( $profRes ) )
                {
                    $sql .= "UPDATE profile SET profile_to_menu=(SELECT array_to_string(array(SELECT DISTINCT id_menu FROM profile_menu_position WHERE id_profile = '{$row['id_profile']}' ORDER BY id_menu), '-')) WHERE id_profile = '{$row['id_profile']}';";
                }
                writeLog( "update profile_to_menu with:[{$sql}]" );
                $grpUsrProfState &= pg_query( $sql );
            }
            else
            {
                $grpUsrProfState = false;
            }
        }
        else
        {
            $grpUsrProfState = false;
        }

        if( $grpUsrProfState !== true )
        {
            pg_query( 'COMMIT;' );
            printOk();
        }
        else
        {
            printSqlKo();
            pg_query( 'ROLLBACK;' );
            return 99;
        }

        /**
         * 3- Troisième étape, merge de sys_definition_product et mise à jour des
         * identifiants produits.
         *
         * Les nouveaux identifiants produits sont mis à jour de la facon
         * suivante: le T&A Gateway à l'identifiant 1 et les autres produits ont
         * leur identifiant incrémenté de 1.
         */
        printSubTitle( 'products identifier' );

        // Mise à jour du Master
        $output    = array();
        $tmpRetCmd = 0;
        exec( "grep -E '^INSERT' ".OUTPUT_FOLDER_BKP."sys_definition_product.sql",$output, $tmpRetCmd );

        // Création de toutes les requêtes à exécuter
        $sql = implode( ' ', $output );
        $sql .= "UPDATE sys_definition_product SET sdp_id=sdp_id+1 WHERE sdp_db_name <> '{$taBpDbName}';";
        $sql .= "UPDATE sys_definition_product SET sdp_master=0 WHERE sdp_id!=1;";
        $sql .= "UPDATE sys_definition_product SET sdp_master_topo=0 WHERE sdp_id=1;";

        // Requêtes pour le Mixed KPI (qu'il existe ou non)
        $sql .= "UPDATE sys_definition_product SET sdp_trigram=(CASE WHEN (SELECT COUNT(sdp_id) FROM sys_definition_product WHERE char_length(trim( both from sdp_trigram))>0)>0 THEN 'bpp' ELSE '' END) WHERE sdp_id=1;";
        $sql .= "UPDATE sys_definition_product SET sdp_directory=(SELECT sdp_directory FROM sys_definition_product WHERE sdp_id=1) || '/mixed_kpi_product' WHERE sdp_directory ilike '%/mixed_kpi_product';";
        
        // Requêtes sys_peuto_config
        $sql .= "UPDATE sys_pauto_config SET id_product=id_product+1 WHERE id_product IS NOT NULL;";
        
        writeLog( "update sys_definition_product and product_id on T&A Gateway with:[{$sql}]" );
        pg_query( "BEGIN;" );
        if( !@pg_query( $sql ) )
        {
            // Affichage de l'erreur et annulation de la transaction
            printKo( 'error while updating pruduct definition\'s table on T&A Gateway' );
            pg_query( 'ROLLBACK;' );
            return 99;
        }
        else
        {
            // Validation de la transaction
            pg_query( 'COMMIT;' );

            // Sauvegarde de la nouvelle table sys_definition_product;
			// 09/12/2011 ACS Mantis 837 Use ProductModel to deploy the definition_setup_product on slaves
			ProductModel::deployProducts();

            $sql = "UPDATE sys_export_raw_kpi_config SET id_product=id_product+1 WHERE id_product IS NOT NULL;";
            $sql .= "UPDATE sys_field_reference SET sfr_sdp_id=sfr_sdp_id+1 WHERE sfr_sdp_id IS NOT NULL;";
            $sql .= "UPDATE sys_definition_kpi SET sdk_sdp_id=sdk_sdp_id+1 WHERE sdk_sdp_id IS NOT NULL;";
            $sql .= "UPDATE sys_pauto_config SET id_product=id_product+1 WHERE id_product IS NOT NULL;";
            writeLog( "update product_id for each slave with [{$sql}]" );

            // Itération sur tous les slaves
            foreach( $productsInfoRes as $keyId => $oneSlaveRes )
            {
                // On exclut le 0 qui est le T&A Gateway
                if( $keyId != "0" )
                {
                    writeLog( "update slave [{$oneSlaveRes['sdp_db_name']}]" );
                    pg_query( $oneSlaveRes['res'], 'BEGIN' );
                    if( !@pg_query( $oneSlaveRes['res'], $sql ) )
                    {
                        printKo( "error while updating table for slave [{$oneSlaveRes['sdp_db_name']}]" );
                        writeLog( "error when updating slave [{$oneSlaveRes['sdp_db_name']}], rollback modifications for this slave and exit script" );
                        pg_query( $oneSlaveRes['res'], 'ROLLBACK' );
                        return 99;
                    }
                    else
                    {
                        writeLog( "slave [{$oneSlaveRes['sdp_db_name']}] successfully updated, commit modifications" );
                        pg_query( $oneSlaveRes['res'], 'COMMIT' );
                    }
                }
            }

            // 24/05/2012 NSE bz 27173 : il faut mettre à jour sys_definition_selecteur sur la Gateway
            writeLog( "update id_product in sys_definition_selecteur" );
            $sql = "";
            // Get list of "selecteurs" with "id", "sort by" and "filter" information
            $res = pg_query( $bdBpRes, "SELECT sds_id_selecteur, sds_sort_by, sds_filter_id FROM sys_definition_selecteur");
            while( $row = pg_fetch_assoc( $res ) ){
                $request = array();

                // check if a sort by is set
                if (isset($row['sds_sort_by']) && strlen($row['sds_sort_by']) > 0) {
                    $sortBy = split("@", $row['sds_sort_by']);
                    
                    // there must be 3 informations (0 - type ; 1 - id ; 2 - idProduct)
                    if (count($sortBy) == 3) { 
                        // retrieve correct product id from "sys_pauto_config" table
                        list($sortBy[2]) = pg_fetch_array( pg_query( $bdBpRes, "SELECT id_product FROM sys_pauto_config WHERE class_object = '".$sortBy[0]."' AND id_elem = '".$sortBy[1]."'"), 0);
                        
                        $newSortBy = implode("@", $sortBy);
                        if ($newSortBy != $row['sds_sort_by']) {
                            $request[] = "sds_sort_by = '$newSortBy'";
                        }

                        // echo $row[sds_sort_by]." => ".$newSortBy."\n\r";
                    }
                }

                // check if a filter is set
                if (isset($row['sds_filter_id']) && strlen($row['sds_filter_id']) > 0) {
                    $filter = split("@", $row['sds_filter_id']);

                    // retrieve correct product id from "sys_pauto_config" table
                    list($filter[2]) = pg_fetch_array( pg_query( $bdBpRes, "SELECT id_product FROM sys_pauto_config WHERE class_object = '".$filter[0]."' AND id_elem = '".$filter[1]."'"), 0);

                    $newFilter = implode("@", $filter);
                    if ($newFilter != $row['sds_filter_id']) {
                            $request[] = "sds_filter_id = '$newFilter'";
                    }
               }

                // update "sds_sort_by" and "sds_filter_id" fields with correct productId value
                if (count($request) > 0) {
                    $fields = implode(",", $request);
                    $sql .= "UPDATE sys_definition_selecteur SET $fields WHERE sds_id_selecteur = ".$row['sds_id_selecteur'].";";
                }
            }
            
            writeLog( " with:[{$sql}]" );
       
            pg_query( $bdBpRes, 'BEGIN' );
            // 09/10/2012 GFS : bz29675, Migration master to gateway failed when the sys_definition_selecteur table is empty
            if(!empty($sql) &&  !@pg_query( $bdBpRes, $sql ))
            {
                printKo( "error while updating product_id in sys_definition_selecteur table" );
                writeLog( "error when updating product_id in sys_definition_selecteur table, rollback modifications for sys_definition_selecteur table and exit script" );
                pg_query( $bdBpRes, 'ROLLBACK' );
                return 99;
            }
            else
            {
                writeLog( "sys_definition_selecteur table successfully updated, commit modifications" );
                pg_query( $bdBpRes, 'COMMIT' );
            }
                    
            // 25/01/2012 OJT : bz25361, users and profiles must be deployed on each product
            UserModel::deployUsers();
            ProfileModel::deployProfile();

            printOk();
        }

        /**
         * 4- Quatrième étape, gestion du default homepage
         *
         * Cette étape initialise la homepage par défaut dans sys_global parameters
         */
        printSubTitle( 'default homepage' );
        $defHPWarn = "";

        // Lecture de la homepage par defaut sur l'ancien master
        $sql = "SELECT value FROM sys_global_parameters WHERE parameters='id_homepage';";
        writeLog( "read default homepage on old master with [{$sql}]" );
        if( ( $hpRes = pg_query( $productsInfoRes['1']['res'], $sql ) ) !== false )
        {
            $idHp = pg_fetch_array( $hpRes, 0 );
            $sql = "UPDATE sys_global_parameters SET value='{$idHp['value']}' WHERE parameters='id_homepage';";
            writeLog( "update default homepage on T&A Gateway with [{$sql}]" );
            if( !pg_query( $productsInfoRes['0']['res'], $sql ) )
            {
                $defHPWarn = 'unable to set default homepage on T&A Gateway';
            }
        }
        else
        {
            $defHPWarn = 'unable to set default homepage on T&A Gateway';
        }    
        
        // Déplacement do logo opérateur
        $currentLogoPath    = "/home/{$productsInfoRes['1']['sdp_directory']}/images/bandeau/logo_operateur.jpg";
        $newLogoPath        = "/home/{$productsInfoRes['0']['sdp_directory']}/images/bandeau/logo_operateur.jpg";
        $newLogoPathDef     = "/home/{$productsInfoRes['0']['sdp_directory']}/images/bandeau/logo_operateur_default.jpg";
        rename( $newLogoPath, $newLogoPathDef );
        copy( $currentLogoPath, $newLogoPath );

        if( strlen( $defHPWarn ) > 0 )
        {
            printWarn( $defHPWarn );
        }
        else
        {
            printOk();
        }


        /**
         * 5- Cinqième étape, gestion du produit MixedKPI si il existe
         */
        printSubTitle( 'mixed KPI' );
        $warnMxKpi = ""; // Message pour un éventuel warning

        // Un MixedKPI est-il présent dans la liste des produits
        if( ( $idMxKpi = isMixedKpiExists( $productsInfoRes ) ) !== false )
        {                
            // Un produit Mixed KPI existe... déplacement de son répertoire
            $mixedKPIDir    = "/home/{$productsInfoRes['1']['sdp_directory']}/mixed_kpi_product";
            $newMixedKPIDir = "/home/{$productsInfoRes['0']['sdp_directory']}/mixed_kpi_product";
            writeLog( "MixedKPI found in directory: [{$mixedKPIDir}]" );
            writeLog( "Moving MixedKPI to : [{$newMixedKPIDir}]" );
            if( rename( $mixedKPIDir, $newMixedKPIDir ) )
            {
                $sedMixedKPIDir = str_replace( '/', '\\\/', $mixedKPIDir );
                $sedNewMixedKPIDir = str_replace( '/', '\\\/', $newMixedKPIDir );
                $sedCmd = "sed s/{$sedMixedKPIDir}/{$sedNewMixedKPIDir}/g ".OUTPUT_FOLDER_BKP."crontab_astellia > ".CRONTAB_ASTELLIA;
                writeLog( "move successfully done, change crontab definition with cmd:[$sedCmd]" );
                exec( $sedCmd, $output, $tmpRetCmd );
                writeLog( "command return value:[{$tmpRetCmd}]" );

                if( $tmpRetCmd === 0 )
                {
                    // Redémarrage des cron
                    writeLog( "OK, restart crond daemon with cmd:[/etc/init.d/crond restart]" );
                    exec( '/etc/init.d/crond restart' );

                    // Exécution des requêtes SQL propres au MixedKPI
                    $sql = "UPDATE sys_definition_mixedkpi SET sdm_sdp_id=sdm_sdp_id+1 WHERE sdm_sdp_id IS NOT NULL;";
                    writeLog( "update MixedKPI database with [{$sql}]" );
                    if( !@pg_query( $productsInfoRes[$idMxKpi]['res'], $sql ) )
                    {
                        printKo( 'error while updating MixedKPI table' );
                        return 99;
                    }

                    // Modification du xenv.inc
                    $xenvPath   = $newMixedKPIDir."/php/xenv.inc";
                    $niveau0Old = str_replace( '/', '\\\/', "/{$productsInfoRes['1']['sdp_directory']}/" );
                    $niveau0New = str_replace( '/', '\\\/', "/{$productsInfoRes['0']['sdp_directory']}/" );
                    $sedCmd     = "sed s/{$niveau0Old}/{$niveau0New}/g {$xenvPath}.old > {$xenvPath}";
                    
                    // Copie du xenv.inc dans un fichier de backup (utilisé aussi pour le sed)
                    copy( $xenvPath, $xenvPath.'.old' );

                    writeLog( "change xenv.inc file with cmd:[$sedCmd]" );
                    exec( $sedCmd, $output, $tmpRetCmd );
                    writeLog( "command return value:[{$tmpRetCmd}]" );
                    
                    if( $tmpRetCmd != 0 )
                    {
                        // Affichage d'un simple Warning, le lien symbolique
                        // prendra le relai
                        writeLog( "xenv.inc cannot be updated to set new MixedKPI folder, check if symbolic link well exists or modify xenv.inc" );
                        $warnMxKpi = "unable to update xenv.inc";
                    }
    
                    // Ajout d'un lien symbolique (par sécurité et pour toutes les
                    // applications tierces succeptibles d'avoir des liens vers l'
                    // ancien dossier du MixedKpi (e.g. vers des Data Exports).
                    $cmd = "ln -s '{$newMixedKPIDir}' '{$mixedKPIDir}'";
                    writeLog( "create new symlink for compatibility with cmd:[$cmd]" );
                    exec( $cmd, $output, $tmpRetCmd );
                    writeLog( "command return value:[{$tmpRetCmd}]" );
                    
                    if( $tmpRetCmd != 0 )
                    {
                        printWarn( 'unable to create symlink' );
                        writeLog( 'No symlink has been created, existing link on old MixedKpi folder can be broken (you can try to create it manually with ln -s command' );
                    }
                    else if( strlen( $warnMxKpi ) > 0 )
                    {
                        printWarn( $warnMxKpi );
                    }
                    else
                    {
                        printOk();
                    }
                }
                else
                {
                    printKo( 'error while updating crontab definition, restoring previous crontab' );

                    // Restauration de la crontab initiale
                    copy( OUTPUT_FOLDER_BKP."crontab_astellia", CRONTAB_ASTELLIA );
                    return 99;
                }
            }
            else
            {
                printKo( 'error while moving mixed kpi folder' );
                return 99;
            }
        }
        else
        {
            printOk( 'no MixedKPI found' );
        }

        /**
         * 6- Sixième (et dernière) étape, nettoyage de l'ancien master
         */
        printSubTitle( 'cleaning old master' );       
        // Nettoyage de sys_pauto_config, selecteur, schedules sur l'ancien master
        // L'id_product de l'ancien master est forcement 2
        $sql  = "TRUNCATE sys_definition_selecteur,sys_report_schedule,sys_report_sendmail;";
        $sql .= "DELETE FROM sys_pauto_page_name WHERE id_page IN (SELECT id_page FROM sys_pauto_config WHERE id_product != 2);";
        $sql .= "DELETE FROM sys_pauto_config WHERE id_product != 2;";
        writeLog( "clean old Master database with [{$sql}]" );
        pg_query( $productsInfoRes['1']['res'], 'BEGIN;' );
        if( !@pg_query( $productsInfoRes['1']['res'], $sql ) )
        {
            pg_query( $productsInfoRes['1']['res'], 'ROLLBACK;' );
            printWarn( 'unable to clean old master database' );
        }
        else
        {
            pg_query( $productsInfoRes['1']['res'], 'COMMIT;' );
            printOk();
        }
        return $retVal;
    }
    
    /**
     * Affiche un texte en spécifiant la couleur
     */
    function printTextColor( $text, $color = '' )
    {
        $end = '';
        if( $color !== '' )
        {
            $end = END_C;
        }
        writeLog( $text );
        echo $color.$text.$end;
    }
    function printOk( $msg = '' )
    {
        if( strlen( $msg ) > 0 )
        {
            $msg = " - {$msg}";
        }
        printTextColor( "OK{$msg}", GREEN );
    }

    function printKo( $msg = '' )
    {
        if( strlen( $msg ) > 0 )
        {
            $msg = " - {$msg}";
        }
        printTextColor( "KO{$msg}", RED );
    }
    function printWarn( $msg = '' )
    {
        if( strlen( $msg ) > 0 )
        {
            $msg = " - {$msg}";
        }
        printTextColor( "WARN{$msg}", ORANGE );
    }
    function printSqlKo() {printTextColor( "KO - Query internal error", RED );}

    /**
     * Ecrit un message dans le fichier de log.
     * Tous les messages sont préfixés par l'heure courante
     * 
     * @param string $text Le message à enregistrer
     */
    function writeLog( $text )
    {
        global $logHandle; // Utilisation de la variable globale

        if( $logHandle === null )
        {
            if( is_writable( dirname( OUTPUT_FILE ) ) )
            {
                if( ( $logHandle = fopen( OUTPUT_FILE, "a" ) ) === false )
                {
                    return;
                }
            }
        }
        else if( $logHandle !== false )
        {            
            fwrite( $logHandle, date( '[H:i:s] ').str_replace( "\n", "", $text ).ENDL );
        }
    }

    /**
     * Demande et affiche les logs générés
     */
    function displayLog()
    {
        global $logHandle; // Utilisation de la variable globale
        
        if( $logHandle !== null && $logHandle !== false )
        {
            fclose( $logHandle );
        }
        echo ENDL."Script ended, read the log file (y/N) ?";
        if ( trim( fgets( STDIN ) ) == 'y' ) {
            system( 'more '.OUTPUT_FILE );
        }
    }

    /**
     * Gestion des erreurs PHP rencontrées
     */
    function myErrorHandler($errno, $errstr, $errfile, $errline )
    {
        writeLog( "PHP Error (line {$errline}): [{$errstr}]" );
        return true;
    }

    /**
     * Fonction déterminant si un produit MixedKPI existe sur l'ancien master
     *
     * @param $productListInfos array Infos des produits
     */
    function isMixedKpiExists( $productListInfos )
    {
        $retVal = false;
        $nbProd = count( $productListInfos );
        $i      = 0;

        while( $i < $nbProd && $retVal === false )
        {
            $oneProduct = each( $productListInfos );
            if( substr( $oneProduct['value']['sdp_directory'], -18 ) === '/mixed_kpi_product' )
            {
                $retVal = $oneProduct['key'];
            }
            $i++;
        }
        return $retVal;
    }

    /**
     * Affiche un titre ou sous titre
     *
     * @staticvar int $titleInd Numéro d'indentation des titres
     * @param string $name Nom du titre
     * @param string $type  Type (0 pour titre, 1 pour sous titre)
     */
    function printTitle( $name, $type = 0 )
    {
        static $titleInd = 1;
        switch( $type )
        {
            // Titre
            case 0 :
                printTextColor( ENDL.ENDL.BOLD."{$titleInd} - {$name}...".END_C.ENDL );
                $titleInd++;
                break;

            // Sous titre
            case 1 :
                printTextColor( ENDL." - ".str_pad( $name.'...', 25, ' ' )."\t" );
                break;
        }
    }

    /**
     * Affiche un sous titre (redirection vers printTitle)
     *
     * @param string $name Nom du sous titre
     */
    function printSubTitle( $name )
    {
        printTitle( $name, 1 );
    }
?>