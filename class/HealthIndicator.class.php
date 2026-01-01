<?php
/**
 *  Classe gérant les indicateurs de santé de T&A.
 *  - Chaque indicateur est accessible via une méthode.
 *  - Tous les indicateurs sont disponibles ensemble via une seule méthode
 *      permettant de récupérer un fichier CSV complet.
 *
 * $Author: g.francois $
 * $Date: 2013-10-03 15:42:11 +0200 (jeu., 03 oct. 2013) $
 * $Revision: 123673 $
 *
 * 03/11/2011 ACS : bz24000 PG 9.1 Cast issue remaining
 * 28/11/2011 ACS : bz24775 error at content Health Indicators
 * 12/12/2011 OJT : Ajout de l'indicateur hi_perf_sa
 * 15/04/2013 NSE : Ajout du HI License Key
 */

require_once( 'Database.class.php' );
require_once( 'DataBaseConnection.class.php' );
require_once( dirname( __FILE__ ).'/../php/edw_function.php' );
require_once( dirname( __FILE__ ).'/../php/edw_function_family.php' );
include_once( dirname( __FILE__ ) . "/../class/topology/TopologyManagement.class.php");

class HealthIndicator
{
    const HI_DATE_FORMAT   = 'Ymd_His';
    const HI_CALL_MODE_API = 0;
    const HI_CALL_MODE_IHM = 1;

    /**
     * Liste de tous les indicateurs existants doivent finir par _HI_NAME pour
     * être automatiquement trouvé par getAllHiNames
     *
     * 14/02/2011 : OJT, bz26014 modification de l'indicateur LAST_COMPUTE_ALARM_DUR_HI_NAME
     */
    const LAST_COLLECT_DUR_HI_NAME           = "hi_perf_last_collect_duration";
    const LAST_RETRIEVE_DUR_HI_NAME          = "hi_perf_last_retrieve_duration";
    const LAST_COMPUTE_RAW_DUR_HI_NAME       = "hi_perf_last_compute_raw_duration";
    const LAST_COMPUTE_KPI_DUR_HI_NAME       = "hi_perf_last_compute_kpi_duration";
    const LAST_COMPUTE_DUR_HI_NAME           = "hi_perf_last_compute_all_duration";
    const NB_WAIT_FILE_HI_NAME               = "hi_perf_nb_wait_file";
    const FAMILY_HISTORY_HI_NAME             = "hi_perf_family_history";
    const NB_LAST_DAY_COLLECTED_FILE_HI_NAME = "hi_perf_nb_last_day_collected_files";
    const SA_HI_NAME                         = "hi_perf_sa";
    const NB_RAW_HI_NAME                     = "hi_perf_nb_raw";
    const NB_MAPPED_RAW_HI_NAME              = "hi_perf_nb_mapped_raw";
    const NB_CUSTOM_KPI_HI_NAME              = "hi_perf_nb_custom_kpi";
    const NB_CLIENT_KPI_HI_NAME              = "hi_perf_nb_client_kpi";
    const NB_STATIC_ALARMS_HI_NAME           = "hi_alarms_nb_static_alarms";
    const NB_DYN_ALARMS_HI_NAME              = "hi_alarms_nb_dyn_alarms";
    const NB_TW_ALARMS_HI_NAME               = "hi_alarms_nb_tw_alarms";
    const LAST_COMPUTE_ALARM_DUR_HI_NAME     = "hi_alarms_last_compute_alarms_duration";
    const NB_DE_HI_NAME                      = "hi_dataexports_nb_data_exports";
    const LAST_DE_DUR_HI_NAME                = "hi_dataexports_last_generation_duration";
    const NB_NE_HI_NAME                      = "hi_topo_nb_ne";
    const NB_NE_1_HI_NAME                    = "hi_topo_nb_ne_first_axis";
    const NB_NE_3_HI_NAME                    = "hi_topo_nb_ne_third_axis";
    const DISK_SPACE_HI_NAME                 = "hi_storage_disc_space";
    const NB_ACCOUNTS_HI_NAME                = "hi_others_nb_accounts";
    const NB_USERS_LAST_DAY_HI_NAME          = "hi_others_nb_connected_users_last_day";
    const NB_PAGES_LAST_DAY_HI_NAME          = "hi_others_nb_pages_last_day";
    const DATE_HI_NAME                       = "hi_date";
    const LICENSE_KEY                        = "hi_license_key";

    /**
     * @var Unsigned Integer Entier indiquant le mode des indicateurs
     * Si HI_CALL_MODE_API (valeur par défaut) les date seront retournées au format timestamp UNIX
     * Si HI_CALL_MODE_IHM, les dates seront retournées au format HI_DATE_FORMAT (meilleur lisibilité)
     */
    protected $mode = self::HI_CALL_MODE_API;

    /** @var DataBaseConnection object */
    protected $dbConnection;

    /** @var Integer Identifiant du produit */
    protected $idProduct;

    /**
     * Constructeur de la classe
     */
    public function __construct( $callMode = self::HI_CALL_MODE_API, $idProduct = NULL )
    {
        $this->idProduct = $idProduct;
        $this->mode      = $callMode;
        $this->getDbConnexion();          
    }

    /**
     * Tentative de connexion à la base de données
     * @return Boolean (TRUE si connexion établie, FALSE sinon)
     */
    public function getDbConnexion()
    {
        $retVal = FALSE;
        try
        {
            $this->dbConnection = Database::getConnection( $this->idProduct );
            $retVal = TRUE;
        }
        catch( Exception $e )
        {
            $this->dbConnection = NULL;
            // On ne fait rien de plus, les indicateurs de santé nécessitant
            // une connexion à la base seront nuls
        }
        return $retVal;
    }

    /**
     * Définit en fonction du mode le format d'affichage d'une date.
     * En timestamp pour le mode API, ou texte pour le mode IHM
     * 
     * @param String $str Chaîne de caractères à convertir
     * @return String
     */
    private function strToTimeHi( $str )
    {
        $retVal = NULL;
        if( $this->mode === self::HI_CALL_MODE_IHM )
        {
            $retVal = date( self::HI_DATE_FORMAT, strtotime( $str ) );
        }
        else
        {
            $retVal = strtotime( $str );
        }
        return $retVal;
    }

    /**
     * Fonction permettant de récupérer la liste des indicateurs existants
     * @return String Array
     */
    public function getAllHiNames()
    {
        // Utilisation de la class ReflectionClass afin de trouver automatiquement
        // tous les indicateurs existants. 
        $hiNames            = array();
        $reflector          = new ReflectionClass( __CLASS__ );
        $reflectorConstants = $reflector->getConstants();

        foreach( $reflectorConstants as $cstKey=>$cstValue )
        {
            if( substr( $cstKey, strlen( $cstKey ) - 8, 8 ) === "_HI_NAME" )
            {
                $hiNames[] = $cstValue;
            }
        }
        return $hiNames;
    }

    /**
     * Fonction permettant de récupérer l'indicateur de santé suivant : hi_perf_last_collect_duration
     * @return String
     */
    public function getLastCollectDuration()
    {
        /** @var String Variable de sortie */
        $retVal = NULL;

        /** @var Array Résultat de la requête SQL */
        $res = array();

        // Récupération d'un connexion
        if( $this->getDbConnexion() === TRUE )
        {
            $res = $this->dbConnection->getAll( 'SELECT message,message_date
                                                    FROM sys_log_ast
                                                    WHERE message
                                                    SIMILAR TO \'%(Start collecting files|End collecting files)%\'
                                                    ORDER BY message_date DESC, message LIMIT 2;'
                                                );
            if( ( count( $res ) === 2 ) && ( stristr( $res[0]['message'], 'end' ) != FALSE ) && ( stristr( $res[1]['message'], 'start' ) != FALSE ) )
            {
                $retVal = intval( strtotime( $res[0]['message_date'] ) - strtotime( $res[1]['message_date'] ) ).';'.$this->strToTimeHi( $res[0]['message_date'] );
            }
            else
            {
                // Résultat SQL non valide pour extraire des données
            }
        }
        else
        {
            // Impossible d'avoir une connexion à la base, on laisse la valeur
            // de retour par défaut et on quitte
        }
        return $retVal;
    }

    /**
     * Fonction permettant de récupérer l'indicateur de santé suivant : hi_perf_last_retrieve_duration
     * @return String
     */
    public function getLastRetrieveDuration()
    {
        /** @var String Variable de sortie */
        $retVal = NULL;

        /** @var Array Résultat de la requête SQL */
        $res = array();

        // Récupération d'un connexion
        if( $this->getDbConnexion() === TRUE )
        {
            $res = $this->dbConnection->getAll( 'SELECT message,message_date
                                                    FROM sys_log_ast
                                                    WHERE message
                                                    SIMILAR TO \'%(Start Retrieve|End Retrieve)\'
                                                    ORDER BY message_date DESC LIMIT 3;'
                                                );
            if( ( count( $res ) >= 2 ) && ( stristr( $res[0]['message'], 'end' ) != FALSE ) && ( stristr( $res[1]['message'], 'start' ) != FALSE ) )
            {
                $retVal = intval( strtotime( $res[0]['message_date'] ) - strtotime( $res[1]['message_date'] ) ).';'.$this->strToTimeHi( $res[0]['message_date'] );
            }
            else if( ( count( $res ) === 3 ) && ( stristr( $res[1]['message'], 'end' ) != FALSE ) && ( stristr( $res[2]['message'], 'start' ) != FALSE ) )
            {
                $retVal = intval( strtotime( $res[1]['message_date'] ) - strtotime( $res[2]['message_date'] ) ).';'.$this->strToTimeHi( $res[1]['message_date'] );
            }
            else
            {
                // Résultat SQL non valide pour extraire des données
            }
        }
        else
        {
            // Impossible d'avoir une connexion à la base, on laisse la valeur
            // de retour par défaut et on quitte
        }
        return $retVal;
    }

    /**
     * Fonction permettant de récupérer l'indicateur de santé suivant : hi_perf_last_compute_raw_duration
     * @return String
     */
    public function getLastComputeRawDuration()
    {
        /** @var String Variable de sortie */
        $retVal = NULL;

        /** @var Array Résultat de la requête SQL */
        $res = array();

        // Récupération d'un connexion
        if( $this->getDbConnexion() === TRUE )
        {
            $res = $this->dbConnection->getAll( 'SELECT message, message_date
                                                    FROM sys_log_ast
                                                    WHERE message
                                                    LIKE \'Compute Raw Duration%\'
                                                    ORDER by message_date DESC LIMIT 1;'
                                                );
            if( count( $res ) === 1 )
            {
                $resSplit = preg_split( '/[:|]+/', $res[0]['message'] );
                if( count( $resSplit ) === 3 )
                {
                    $retVal = intval( $resSplit[1] ).';'.$this->strToTimeHi( $res[0]['message_date'] ).'|'.intval( trim( str_replace( 'period(s)', '', $resSplit[2] ) ) );
                }
                else
                {
                    // Mauvais format du log
                }
            }
            else
            {
                // Résultat SQL non valide pour extraire des données
            }
        }
        else
        {
            // Impossible d'avoir une connexion à la base, on laisse la valeur
            // de retour par défaut et on quitte
        }
        return $retVal;
    }

    /**
     * Fonction permettant de récupérer l'indicateur de santé suivant : hi_perf_last_compute_kpi_duration
     * @return String
     */
    public function getLastComputeKpiDuration()
    {
        /** @var String Variable de sortie */
        $retVal = NULL;

        /** @var Array Résultat de la requête SQL */
        $res = array();

        // Récupération d'un connexion
        if( $this->getDbConnexion() === TRUE )
        {
            $res = $this->dbConnection->getAll( 'SELECT message, message_date
                                                    FROM sys_log_ast
                                                    WHERE message
                                                    LIKE \'Compute KPI Duration%\'
                                                    ORDER by message_date DESC LIMIT 1;'
                                                );
            if( count( $res ) === 1 )
            {
                $resSplit = preg_split( '/[:|]+/', $res[0]['message'] );
                if( count( $resSplit ) === 3 )
                {
                    $retVal = intval( $resSplit[1] ).';'.$this->strToTimeHi( $res[0]['message_date'] ).'|'.intval( trim( str_replace( 'period(s)', '', $resSplit[2] ) ) );
                }
                else
                {
                    // Mauvais format du log
                }
            }
            else
            {
                // Résultat SQL non valide pour extraire des données
            }
        }
        else
        {
            // Impossible d'avoir une connexion à la base, on laisse la valeur
            // de retour par défaut et on quitte
        }
        return $retVal;
    }

    /**
     * Fonction permettant de récupérer l'indicateur de santé suivant : hi_perf_last_compute_all_duration
     * @return String
     */
    public function getLastComputeAllDuration()
    {
        /** @var String Variable de sortie */
        $retVal = NULL;

        /** @var Array Résultat de la requête SQL */
        $res = array();

        // Récupération d'un connexion
        if( $this->getDbConnexion() === TRUE )
        {
            $res = $this->dbConnection->getAll( 'SELECT message, message_date
                                                    FROM sys_log_ast
                                                    WHERE message
                                                    SIMILAR TO \'Compute KPI Duration%|Compute Raw Duration%\'
                                                    ORDER by message_date DESC LIMIT 3;'
                                                );
            if( ( count( $res ) >= 2 ) && ( stristr( $res[0]['message'], 'KPI' ) != FALSE ) && ( stristr( $res[1]['message'], 'Raw' ) != FALSE ) )
            {
                $resSplitRaw = preg_split( '/[:|]+/', $res[1]['message'] );
                $resSplitKpi = preg_split( '/[:|]+/', $res[0]['message'] );
                if( ( count( $resSplitRaw ) === 3 ) && ( count( $resSplitKpi ) === 3 ) )
                {
                    $retVal = intval( $resSplitRaw[1] ) + intval( $resSplitKpi[1] ).';'.
                        $this->strToTimeHi( $res[1]['message_date'] ).'|'.
                        intval( trim( str_replace( 'period(s)', '', $resSplitRaw[2] ) ) );
                }
                else
                {
                    // Mauvais format du log
                }
            }
            else if( ( count( $res ) === 3 ) && ( stristr( $res[1]['message'], 'KPI' ) != FALSE ) && ( stristr( $res[2]['message'], 'Raw' ) != FALSE ) )
            {
                $resSplitRaw = preg_split( '/[:|]+/', $res[2]['message'] );
                $resSplitKpi = preg_split( '/[:|]+/', $res[1]['message'] );
                if( ( count( $resSplitRaw ) === 3 ) && ( count( $resSplitKpi ) === 3 ) )
                {
                    $retVal = intval( $resSplitRaw[1] ) + intval( $resSplitKpi[1] ).';'.
                        $this->strToTimeHi( $res[2]['message_date'] ).'|'.
                        intval( trim( str_replace( 'period(s)', '', $resSplitRaw[2] ) ) );
                }
                else
                {
                    // Mauvais format du log
                }
            }
            else
            {
                // Résultat SQL non valide pour extraire des données
            }
        }
        else
        {
            // Impossible d'avoir une connexion à la base, on laisse la valeur
            // de retour par défaut et on quitte
        }
        return $retVal;
    }

    /**
     * Fonction permettant de récupérer l'indicateur de santé suivant : hi_perf_nb_wait_file
     * @return Integer
     */
    public function getNbWaitFiles()
    {
        /** @var Variable de sortie */
        $retVal = NULL;
        
        // Récupération d'un connexion
        if( $this->getDbConnexion() === TRUE )
        {
            $res = $this->dbConnection->getRow( 'SELECT COUNT(hour) as nbfiles FROM sys_flat_file_uploaded_list;' );
            $retVal = intval( $res['nbfiles'] );
        }
        else
        {
            // Impossible d'avoir une connexion à la base, on laisse la valeur
            // de retour par défaut et on quitte
        }
        return $retVal;
    }

    /**
     * Fonction permettant de récupérer l'indicateur de santé suivant : hi_perf_family_history
     * @return String Array
     */
    public function getFamilyHistory()
    {
        /** @var Variable de sortie */
        $retVal = array();

        $defaultHourHistory = NULL;
        $defaultDayHistory = NULL;
        $defaultWeekHistory = NULL;
        $defaultMonthHistory = NULL;

        /** @var Array Résultat de la requête SQL */
        $res = array();

        // Récupération d'un connexion
        if( $this->getDbConnexion() === TRUE )
        {
            // Récupération des valeurs par défauts
            $res = $this->dbConnection->getAll( 'SELECT parameters,value
                                                    FROM sys_global_parameters
                                                    WHERE parameters=\'history_hour\'
                                                    OR parameters=\'history_day\'
                                                    OR parameters=\'history_week\'
                                                    OR parameters=\'history_month\'
                                                    ORDER BY parameters;'
                                                );
            if( count( $res ) === 4 )
            {
                $defaultDayHistory = $res[0]['value'];
                $defaultHourHistory = $res[1]['value'];
                $defaultMonthHistory = $res[2]['value'];
                $defaultWeekHistory = $res[3]['value'];
            }
            else
            {
                // Les valeurs par défaut n'ont pas été trouvées
            }

            $res = $this->dbConnection->getAll( 'SELECT sdc.family,sdh.ta,sdh.duration
                                                    FROM sys_definition_categorie AS sdc
                                                    LEFT OUTER JOIN sys_definition_history AS sdh
                                                    ON sdc.family=sdh.family
                                                    ORDER BY family,ta;'
                                                );
            $i = 0;
            while( $i < count( $res ) )
            {
                if( strlen( trim( $res[$i]['ta'] ) ) > 0 )
                {
                    array_push( $retVal, 'h:'.$res[$i+1]['duration'].'|d:'.$res[$i]['duration'].'|w:'.$res[$i+3]['duration'].'|m:'.$res[$i+2]['duration'].';'.trim( $res[$i]['family'] ) );
                    $i += 4;
                }
                else
                {
                    array_push( $retVal, 'h:'.$defaultHourHistory.'|d:'.$defaultDayHistory.'|w:'.$defaultWeekHistory.'|m:'.$defaultMonthHistory.';'.trim( $res[$i]['family'] ) );
                    $i++;
                }
                
            }
        }
        else
        {
            // Impossible d'avoir une connexion à la base, on laisse la valeur
            // de retour par défaut et on quitte
        }
        return $retVal;
    }

    /**
     * Fonction permettant de récupérer l'indicateur de santé suivant : hi_perf_nb_last_day_collected_files
     * @return Integer
     */
    public function getNbLastDayCollectedFiles()
    {
        /** @var Variable de sortie */
        $retVal = NULL;

        // Récupération d'un connexion
        if( $this->getDbConnexion() === TRUE )
        {
            $res = $this->dbConnection->getRow( 'SELECT COUNT(hour) as nbfiles
                                                    FROM sys_flat_file_uploaded_list_archive
                                                    WHERE uploaded_flat_file_time
                                                    LIKE \''.date( 'd F Y', time() - ( 24 * 60 * 60 ) ).'%\';'
                                                );
            $retVal = intval( $res['nbfiles'] );
        }
        else
        {
            // Impossible d'avoir une connexion à la base, on laisse la valeur
            // de retour par défaut et on quitte
        }
        return $retVal;
    }

    /**
     * Fonction permettant de récupérer l'indicateur de santé suivant : hi_perf_sa
     *
     * @param string  $date Date au format YYYYMMDD[hh]
     * @param string  $ta Granularité à utiliser ('day' ou 'hour')
     * @param integer $global Valeur globale (1) ou individuelle par connexion (0)
     */
    public function getSAValues( $date = 0, $ta = 'day', $global = 1 )
    {
        $retArray = array();
        $ta = strtolower( $ta );

        // Le Source Availability est-il actif (bz25624, sur le produit en cours)
        if( get_sys_global_parameters( 'activation_source_availability', 0, $this->idProduct ) != 1 )
        {
            // Fin de la méthode, retour du mot clé SA_OFF
            return array( ';SA_OFF' );
        }

        // Si aucune date n'est renseignée, utilisation de la date de la veille
        if( $date === 0 )
        {
            $date = date( 'Ymd' );
        }

        // Vérification des paramètres d'entrées
        if( ( $global !== 0 && $global !== 1  ) || 
            ( $ta !== 'day' && $ta !== 'hour' ) ||
            ( !checkdate( substr( $date, 4, 2 ), substr( $date, 6, 2 ), substr( $date, 0, 4 ) ) ) ||
            ( strlen( $date ) === 10 && !( intval( substr( $date, 8, 2 ) ) < 24 && intval( substr( $date, 8, 2 ) ) >= 0 ) )
        )
        {
            // Erreur dans les paramètres, la fonction retourne false
            return false;
        }

        // Récupération d'un connexion
        if( $this->getDbConnexion() === true )
        {
            // Récupération des connexions existantes (utilisé ensuite pour combler les valeurs manquantes)
            $allCnxIds = ConnectionModel::getAllConnections( $this->idProduct );

            // Lecture en base des valeurs.
            if( $global === 0 )
            {
                $sql = "SELECT cnx.connection_name as con,sa.sdsv_ta_value as ta, sa.sdsv_calcul_sa as value
                        FROM sys_definition_sa_view AS sa
                        LEFT OUTER JOIN sys_definition_connection AS cnx ON id_connection=sdsv_id_connection
                        WHERE sa.sdsv_ta_value LIKE '{$date}%' AND sa.sdsv_ta='{$ta}' 
                        ORDER BY sa.sdsv_ta_value ASC,cnx.connection_name ASC;";
            }
            else
            {
                $sql = "SELECT sa.sdsv_ta_value as ta, avg(sa.sdsv_calcul_sa) as value
                        FROM sys_definition_sa_view AS sa
                        WHERE sa.sdsv_ta_value LIKE '{$date}%' AND sa.sdsv_ta='{$ta}'
                        GROUP BY sdsv_ta_value 
                        ORDER BY sa.sdsv_ta_value;";
            }

            $res = $this->dbConnection->getAll( $sql );

            // Gestion d'un retour sans données (gestion du bz25207)
            if( count( $res ) === 0 )
            {
                // Si il n'y a aucune données sur une ta hour pour une date au format YYYYMMDD
                if( ( $ta === 'hour' ) && ( strlen( $date ) === 8 ) )
                {
                    // Initialisation par défaut avec l'heure 0
                    $date = $date.'00';
                }

                // Dans le cas d'un retour non global, on retourne une ligne par connexion
                if( $global === 0 )
                {
                    foreach( $allCnxIds as $oneCnxId )
                    {
                        $cnxModel = new ConnectionModel( $oneCnxId, $this->idProduct );
                        $retArray []= ";{$cnxModel->getValue( 'connection_name' )}|{$date}";
                    }
                }

                // Dans le cas d'un overview, on retourne juste une ligne
                else
                {
                    $retArray []= ";overview|{$date}";
                }
            }
            else
            {
                // Tableau mémorisant les connexions traitées (utilisé ensuite pour combler les valeurs manquantes)
                $treatedCnx = array();

                foreach( $res as $saValue )
                {
                    if( $global === 0 )
                    {
                        $retArray   []= "{$saValue['value']};{$saValue['con']}|{$saValue['ta']}";
                        $treatedCnx []= $saValue['con'];
                    }
                    else
                    {
                        $retArray []= "{$saValue['value']};overview|{$saValue['ta']}";
                    }
                }

                // Ajout des connexions sans valeur (gestion du bz25207)
                if( $global === 0 )
                {
                    // Si il n'y a aucune données sur une ta hour pour une date au format YYYYMMDD
                    if( ( $ta === 'hour' ) && ( strlen( $date ) === 8 ) )
                    {
                        // Initialisation par défaut avec l'heure 0
                        $date = $date.'00';
                    }
                    
                    foreach( $allCnxIds as $oneCnxId )
                    {
                        $cnxModel   = new ConnectionModel( $oneCnxId, $this->idProduct );
                        $tmpCnxName = $cnxModel->getValue( 'connection_name' );
                        if( !in_array( $tmpCnxName, $treatedCnx ) )
                        {
                            $retArray []= ";{$tmpCnxName}|{$date}";
                        }
                    }
                }
            }
        }
        else
        {
            // Impossible d'avoir une connexion à la base, on laisse la valeur
            // de retour par défaut et on quitte
        }
        return $retArray;
    }

    /**
     * Fonction permettant de récupérer l'indicateur de santé suivant : hi_perf_nb_raw
     * @return String Array
     */
    public function getNbRaw()
    {
        /** @var String Variable de sortie */
        $retVal = array();

        /** @var Array Résultat de la requête SQL */
        $res = array();

        // Récupération d'un connexion
        if( $this->getDbConnexion() === TRUE )
        {
            $res = $this->dbConnection->getAll( 'SELECT sdc.family AS family, COUNT(id_ligne) AS nbraw FROM sys_field_reference AS sfr LEFT OUTER JOIN sys_definition_categorie AS sdc ON sdc.rank=sfr.id_group_table WHERE sfr.on_off=1 GROUP BY sfr.id_group_table,sdc.family;' );
            foreach( $res as $oneRes )
            {
                array_push( $retVal, $oneRes['nbraw'].';'.$oneRes['family'] );
            }
        }
        else
        {
            // Impossible d'avoir une connexion à la base, on laisse la valeur
            // de retour par défaut et on quitte
        }
        return $retVal;
    }

    /**
     * Fonction permettant de récupérer l'indicateur de santé suivant : hi_perf_nb_mapped_raw
     * @return String Array
     */
    public function getNbMappedRaw()
    {
        /** @var String Variable de sortie */
        $retVal = array();

        /** @var Array Résultat de la requête SQL */
        $res = array();

        // Récupération d'un connexion
        if( $this->getDbConnexion() === TRUE )
        {
            // 07/06/2011 BBX -PARTITIONING-
            // 03/11/2011 ACS BZ 24000 PG 9.1 Cast issue remaining
            // 28/11/2011 ACS BZ 24775 cast issue
            // Correction des casts
            $res = $this->dbConnection->getAll( 'SELECT COUNT(sfra.id_ligne) AS nbmappedraw, sdc.family AS family
                                                FROM sys_field_reference AS sfr,sys_field_reference_all AS sfra
                                                LEFT OUTER JOIN sys_definition_categorie AS sdc ON sdc.rank::text=sfra.id_group_table
                                                WHERE sfr.nms_field_name=sfra.nms_field_name::text
                                                AND sfr.id_group_table::text=sfra.id_group_table AND sfra.blacklisted=0 AND sfr.on_off=1
                                                GROUP BY sdc.family;' );
            foreach( $res as $oneRes )
            {
                array_push( $retVal, $oneRes['nbmappedraw'].';'.$oneRes['family'] );
            }
        }
        else
        {
            // Impossible d'avoir une connexion à la base, on laisse la valeur
            // de retour par défaut et on quitte
        }
        return $retVal;
    }

    /**
     * Fonction permettant de récupérer l'indicateur de santé suivant : hi_perf_nb_custom_kpi
     * @return String Array
     */
    public function getNbCustomKpi()
    {
        /** @var String Variable de sortie */
        $retVal = array();

        /** @var Array Résultat de la requête SQL */
        $res = array();

        // Récupération d'un connexion
        if( $this->getDbConnexion() === TRUE )
        {
            $res = $this->dbConnection->getAll( 'SELECT COUNT(sdk.id_ligne) AS nbcustomkpi, sdgt.family AS family
                                                FROM sys_definition_kpi AS sdk
                                                LEFT OUTER JOIN sys_definition_group_table AS sdgt
                                                ON sdk.edw_group_table=sdgt.edw_group_table
                                                WHERE value_type=\'customisateur\' AND on_off=1
                                                GROUP BY sdgt.family;
                                                ' );
            foreach( $res as $oneRes )
            {
                array_push( $retVal, $oneRes['nbcustomkpi'].';'.$oneRes['family'] );
            }
        }
        else
        {
            // Impossible d'avoir une connexion à la base, on laisse la valeur
            // de retour par défaut et on quitte
        }
        return $retVal;
    }

    /**
     * Fonction permettant de récupérer l'indicateur de santé suivant : hi_perf_nb_client_kpi
     * @return String Array
     */
    public function getNbClientKpi()
    {
        /** @var String Variable de sortie */
        $retVal = array();

        /** @var Array Résultat de la requête SQL */
        $res = array();

        // Récupération d'un connexion
        if( $this->getDbConnexion() === TRUE )
        {
            $res = $this->dbConnection->getAll( 'SELECT COUNT(sdk.id_ligne) AS nbclientkpi, sdgt.family AS family
                                                FROM sys_definition_kpi AS sdk
                                                LEFT OUTER JOIN sys_definition_group_table AS sdgt
                                                ON sdk.edw_group_table=sdgt.edw_group_table
                                                WHERE value_type=\'client\' AND on_off=1
                                                GROUP BY sdgt.family;
                                                ' );
            foreach( $res as $oneRes )
            {
                array_push( $retVal, $oneRes['nbclientkpi'].';'.$oneRes['family'] );
            }
        }
        else
        {
            // Impossible d'avoir une connexion à la base, on laisse la valeur
            // de retour par défaut et on quitte
        }
        return $retVal;
    }

    /**
     * Fonction permettant de récupérer l'indicateur de santé suivant : hi_alarms_nb_static_alarms
     * @return Integer
     */
    public function getNbStaticAlarms()
    {
        /** @var Variable de sortie */
        $retVal = NULL;

        /** @var Array Résultat de la requête SQL */
        $res = array();

        // Récupération d'un connexion
        if( $this->getDbConnexion() === TRUE )
        {
            $res = $this->dbConnection->getRow( 'SELECT COUNT(alarm_id) AS nbalarms
                                                    FROM sys_definition_alarm_static
                                                    WHERE on_off=1;'
                                                );
            $retVal = intval( $res['nbalarms'] );
        }
        else
        {
            // Impossible d'avoir une connexion à la base, on laisse la valeur
            // de retour par défaut et on quitte
        }
        return $retVal;
    }

    /**
     * Fonction permettant de récupérer l'indicateur de santé suivant : hi_alarms_nb_dyn_alarms
     * @return Integer
     */
    public function getNbDynAlarms()
    {
        /** @var Variable de sortie */
        $retVal = NULL;

        /** @var Array Résultat de la requête SQL */
        $res = array();
        
        // Récupération d'un connexion
        if( $this->getDbConnexion() === TRUE )
        {
            $res = $this->dbConnection->getRow( 'SELECT COUNT(alarm_id) AS nbalarms
                                                    FROM sys_definition_alarm_dynamic
                                                    WHERE on_off=1;'
                                                );
            $retVal = intval( $res['nbalarms'] );
        }
        else
        {
            // Impossible d'avoir une connexion à la base, on laisse la valeur
            // de retour par défaut et on quitte
        }
        return $retVal;
    }

    /**
     * Fonction permettant de récupérer l'indicateur de santé suivant : hi_alarms_nb_tw_alarms
     * @return Integer
     */
    public function getNbTwAlarms()
    {
        /** @var Variable de sortie */
        $retVal = NULL;

        /** @var Array Résultat de la requête SQL */
        $res = array();

        // Récupération d'un connexion
        if( $this->getDbConnexion() === TRUE )
        {
            $res = $this->dbConnection->getRow( 'SELECT COUNT(alarm_id) AS nbalarms
                                                    FROM sys_definition_alarm_top_worst
                                                    WHERE on_off=1;'
                                                );
            $retVal = intval( $res['nbalarms'] );
        }
        else
        {
            // Impossible d'avoir une connexion à la base, on laisse la valeur
            // de retour par défaut et on quitte
        }
        return $retVal;
    }

    /**
     * Fonction permettant de récupérer l'indicateur de santé suivant : hi_alarms_last_compute_alarms_duration
     * @return String Array
     */
    public function getLastComputeAlarmsDuration()
    {
        /** @var String Variable de sortie */
        $retVal = array();

        /** @var Array Résultat de la requête SQL */
        $res = array();

        /** @var Array Alarme déjà affichées */
        $alaramsUsed = array();

        // Récupération d'un connexion
        if( $this->getDbConnexion() === TRUE )
        {
            $res = $this->dbConnection->getAll( 'SELECT message
                                                    FROM sys_log_ast
                                                    WHERE message LIKE \'Alarm%\'
                                                    ORDER BY message_date DESC;'
                                                );
            foreach( $res as $oneRes )
            {
                $resSplit = preg_split( '/[:,\(\)]+/', $oneRes['message'] );
                if( ( count( $resSplit ) > 5 ) && ( in_array( $resSplit[1], $alaramsUsed ) === FALSE ) )
                {
                    array_push( $alaramsUsed, $resSplit[1] );
                    array_push( $retVal, trim( str_replace( 's', '', $resSplit[3] ) ).';'.$resSplit[1].'|'.trim( preg_replace( '/(^Alarm)(.+)/', '$2', $resSplit[0] ) ).'|'.trim( preg_replace( '/^(.+) (.+)$/', '$1', $resSplit[4] ) ) );
                }
                else
                {
                    // Mauvais format du log
                }
            }
        }
        else
        {
            // Impossible d'avoir une connexion à la base, on laisse la valeur
            // de retour par défaut et on quitte
        }
        return $retVal;
    }

    /**
     * Fonction permettant de récupérer l'indicateur de santé suivant : hi_dataexports_nb_data_exports
     * @return Integer
     */
    public function getNbDataExports()
    {
        /** @var Variable de sortie */
        $retVal = NULL;

        /** @var Array Résultat de la requête SQL */
        $res = array();

        // Récupération d'un connexion
        if( $this->getDbConnexion() === TRUE )
        {
            $res = $this->dbConnection->getRow( 'SELECT COUNT(export_id) AS nbde FROM sys_export_raw_kpi_config;' );
            $retVal = intval( $res['nbde'] );
        }
        else
        {
            // Impossible d'avoir une connexion à la base, on laisse la valeur
            // de retour par défaut et on quitte
        }
        return $retVal;
    }

    /**
     * Fonction permettant de récupérer l'indicateur de santé suivant : hi_dataexports_last_generation_duration
     * @return String Array
     */
    public function getLastDataExportsGenerationDuration()
    {
        /** @var String Variable de sortie */
        $retVal = array();

        /** @var Array Résultat de la requête SQL */
        $res = array();

        /** @var Array Data Export déjà affichés */
        $dataExportUsed = array();

        // Récupération d'un connexion
        if( $this->getDbConnexion() === TRUE )
        {
            $res = $this->dbConnection->getAll( 'SELECT message
                                                    FROM sys_log_ast
                                                    WHERE message LIKE \'Data Export%\'
                                                    ORDER BY message_date DESC;'
                                                );
            foreach( $res as $oneRes )
            {
                $resSplit = preg_split( '/[:\(\)]+/', $oneRes['message'] );
                if( ( count( $resSplit ) === 4 ) && ( in_array( $resSplit[1], $dataExportUsed) === FALSE ) )
                {
                    array_push( $dataExportUsed, $resSplit[1] );
                    array_push( $retVal, trim( $resSplit[3] ).';'.$resSplit[1] );
                }
                else
                {
                    // Mauvais format du log
                }
            }
        }
        else
        {
            // Impossible d'avoir une connexion à la base, on laisse la valeur
            // de retour par défaut et on quitte
        }
        return $retVal;
    }

    /**
     * Fonction permettant de récupérer l'indicateur de santé suivant : hi_topo_nb_ne
     * @return String
     */
    public function getNbNe()
    {
        /** @var Variable de sortie */
        $retVal = NULL;

        /** @var Array Résultat de la requête SQL */
        $res = array();

        // Récupération d'un connexion
        if( $this->getDbConnexion() === TRUE )
        {
            $res = $this->dbConnection->getRow( 'SELECT COUNT(eor_id) AS nbne
                                                     FROM edw_object_ref
                                                     WHERE eor_on_off=1;'
                                                );
            $retVal = intval( $res['nbne'] );
        }
        else
        {
            // Impossible d'avoir une connexion à la base, on laisse la valeur
            // de retour par défaut et on quitte
        }
        return $retVal;
    }

    /**
     * Fonction permettant de récupérer l'indicateur de santé suivant : hi_topo_nb_ne_first_axis
     * @return String
     */
    public function getNbFirstAxisNe()
    {
        /** @var Variable de sortie */
        $retVal = NULL;

        /** @var Array Résultat de la requête SQL */
        $res = array();

        // Récupération d'un connexion
        if( $this->getDbConnexion() === TRUE )
        {
            $res = $this->dbConnection->getAll( 'SELECT DISTINCT eor_id,eor_label,axe
                                                    FROM edw_object_ref AS eor
                                                    LEFT OUTER JOIN sys_definition_network_agregation AS sdna
                                                    ON (eor.eor_obj_type=sdna.agregation)
                                                    WHERE eor_on_off=1 AND axe IS NULL;'
                                                );
            $retVal = count( $res );
        }
        else
        {
            // Impossible d'avoir une connexion à la base, on laisse la valeur
            // de retour par défaut et on quitte
        }
        return $retVal;
    }

    /**
     * Fonction permettant de récupérer l'indicateur de santé suivant : hi_topo_nb_ne_third_axis
     * @return String
     */
    public function getNbThirdAxisNe()
    {
        /** @var Variable de sortie */
        $retVal = NULL;

        /** @var Array Résultat de la requête SQL */
        $res = array();

        // Récupération d'un connexion
        if( $this->getDbConnexion() === TRUE )
        {
            $res = $this->dbConnection->getAll( 'SELECT DISTINCT eor_id,eor_label,axe
                                                    FROM edw_object_ref AS eor
                                                    LEFT OUTER JOIN sys_definition_network_agregation AS sdna
                                                    ON (eor.eor_obj_type=sdna.agregation)
                                                    WHERE eor_on_off=1 AND axe=3;'
                                                );
            $retVal = count( $res );
        }
        else
        {
            // Impossible d'avoir une connexion à la base, on laisse la valeur
            // de retour par défaut et on quitte
        }
        return $retVal;
    }

    /**
     * Fonction permettant de récupérer l'indicateur de santé suivant : hi_storage_disc_space
     * @return String Array
     */
    public function getDiscSpace()
    {
        /** @var Variable de sortie */
        $retVal = array();

        /** @var Array Résultat de la commande exec */
        $execOutput = array();

        $execRetVal = 1;

        exec( 'df | tr -s \' \' | grep -E \'^/\'', $execOutput, $execRetVal );
        if( $execRetVal === 0 )
        {
            foreach( $execOutput as $oneOutput )
            {
                $splitOutput = explode( ' ', $oneOutput );
                if( count( $splitOutput ) === 6 )
                {
                    array_push( $retVal, $splitOutput[1].'|'.$splitOutput[3].';'.$splitOutput[5] );
                }
                else
                {
                    // Mauvais format
                }
            }
        }
        else
        {
            // La commande à echouée, on quitte
        }
        return $retVal;
    }

    /**
     * Fonction permettant de récupérer l'indicateur de santé suivant : hi_others_nb_accounts
     * @return Integer
     */
    public function getNbAccounts()
    {
        /** @var Variable de sortie */
        $retVal = NULL;

        /** @var Array Résultat de la requête SQL */
        $res = array();

        // Récupération d'un connexion
        if( $this->getDbConnexion() === TRUE )
        {
            $res = $this->dbConnection->getRow( 'SELECT COUNT(id_user) as nbusers FROM users;' );
            $retVal = intval( $res['nbusers'] );
        }
        else
        {
            // Impossible d'avoir une connexion à la base, on laisse la valeur
            // de retour par défaut et on quitte
        }
        return $retVal;
    }

    /**
     * Fonction permettant de récupérer l'indicateur de santé suivant : hi_others_nb_connected_users_last_day
     * @return Integer
     */
    public function getNbConnectedUsersLastDay()
    {
        /** @var Variable de sortie */
        $retVal = NULL;

        /** @var Array Résultat de la requête SQL */
        $res = array();

        // Récupération d'un connexion
        if( $this->getDbConnexion() === TRUE )
        {
            // 07/06/2011 BBX -PARTITIONING-
            // Correction des casts
            $res = $this->dbConnection->getRow( 'SELECT COUNT( DISTINCT(id_user) ) as nbusers
                                                    FROM track_users
                                                    WHERE start_connection::text
                                                    LIKE \''.date( 'Y-m-d', time() - ( 24 * 60 * 60 ) ).'%\';'
                                                );
            $retVal = intval( $res['nbusers'] );
        }
        else
        {
            // Impossible d'avoir une connexion à la base, on laisse la valeur
            // de retour par défaut et on quitte
        }
        return $retVal;
    }

    /**
     * Fonction permettant de récupérer l'indicateur de santé suivant : hi_others_nb_pages_last_day
     * @return String
     */
    public function getNbPagesLastDay()
    {
        /** @var Variable de sortie */
        $retVal = NULL;

        /** @var Array Résultat de la requête SQL */
        $res = array();

        // Récupération d'un connexion
        if( $this->getDbConnexion() === TRUE )
        {
            $res = $this->dbConnection->getRow( 'SELECT COUNT(page) as nbpages
                                                    FROM track_pages
                                                    WHERE access_day=\''.date( 'Ymd', time() - ( 24 * 60 * 60 ) ).'\';'
                                                );
            $retVal = intval( $res['nbpages'] );
        }
        else
        {
            // Impossible d'avoir une connexion à la base, on laisse la valeur
            // de retour par défaut et on quitte
        }
        return $retVal;
    }
    
    /**
     * Récupère le nombre max de NE autorisés, le niveau d’agrégation concerné, 
     * le nombre courant d’élément sur le niveau en question et la date de validité de la clef.
     * Calcule le pourcentage de NE utilisé par rapport au nombre autorisé.
     * @return null si erreur sur la clef / string  [%tage];[NE max number]|[NA]|[current NE number]|[expiration date]
     */
    function getLicenseKeyValues(){
        
        /** @var String Variable de sortie */
        $retVal = NULL;
        
        // Initialisation du produit (par défault id_prod = '' pour pointer vers la base local
        $id_prod = '';

        // On récupère la famille principale du produit
        $main_family = get_main_family($id_prod);

        $network_agregation     = getNaLabelList("na",$main_family, $id_prod);
        $lst_network_agregation = $network_agregation[$main_family];

        // Récupération des informations de la clé
        $key = get_sys_global_parameters("key");
        $key_instance = new Key();
        // Conversion de la clé
        $key_decript = $key_instance->Decrypt($key);
        // Récupération des données de la clé décryptée
        $tab_key['na']              = $key_instance->getNaKey();
        $tab_key['nb_elements']     = $key_instance->getNbElemsKey();

        // 03/10/2013 GFS - Bug 36876 - [SUP][CB5.3.1][#NA][Telus] : Download Log does not work
        if ( !empty($tab_key['na']) && !empty($lst_network_agregation) && in_array( $tab_key['na'], array_keys( $lst_network_agregation ) ) )
        {
            // Récupération du nombre d'éléments présents en base du même niveau d'agrégation que celui de la clé
            $topoManagement = TopologyManagement::getInstance( $id_prod );
            $topoManagement->setModeLog("off");
            $nb_elements_in_db = $topoManagement->getNbElements( $tab_key['na'] );
            $pourcent = round($nb_elements_in_db*100/$tab_key['nb_elements']*100)/100;
            $retVal = "$pourcent;{$tab_key['nb_elements']}|{$tab_key['na']}|{$nb_elements_in_db}|";

            // S'il y a un edate de validité, on l'ajoute en dernière info complémentaire
            $dateInfo = $key_instance->getKeyEndDateInfo();
            if($dateInfo)
                $retVal .= $dateInfo[0];
        }
        else{
            // Le niveau d'aggregation {$tab_key['na']} present dans la cle n'existe pas dans la table reference de la topology ou n'appartient pas à la famille principale        
            $retVal = null;
        }

        return $retVal;
    }

    /**
     * Fonction permettant de générér le fihcier de sortie CSV
     *
     * @param string $path Chemin du fichier de sortie
     * @param integer $date Date utile à certains indicateurs (timestamp)
     * @return Boolean
     *
     * 16/09/2010 OJT : Correction bz17758, hi_perf_nb_wait_file, hi_perf_nb_last_day_collected_files et hi_date
     * 12/12/2011 OJT : Ajout du paramètre date
     */
    public function generateOutputFile( $path, $date = 0 )
    {
        /** @var Variable de sortie */
        $retVal = FALSE;

        /** @var File pointer Pointeur sur le fichier CSV */
        $handle = NULL;

        /** @var String Chaîne de sortie */
        $output = '';

        if( ( is_dir( dirname( $path ) ) === TRUE ) && ( is_writable( dirname( $path ) ) === TRUE ) )
        {
            $handle = fopen( $path, 'w');
            if( $handle != FALSE ) // Si la création du fichier s'est bien passée
            {
                // Lecture des valeurs Source Availability
                $saValues = $this->formatHiToCsv( self::SA_HI_NAME, $this->getSAValues( $date ) );
                if( strpos( $saValues, 'SA_OFF' ) === FALSE )
                {
                    $saValues .= $this->formatHiToCsv( self::SA_HI_NAME, $this->getSAValues( $date, 'day', 0 ) );
                }

                // On écrit un à un tous les indicateurs
                $output .= 'indicator_names;indicator_values;indicator_informations'."\n";
                $output .= $this->formatHiToCsv( self::LAST_COLLECT_DUR_HI_NAME, $this->getLastCollectDuration() );
                $output .= $this->formatHiToCsv( self::LAST_RETRIEVE_DUR_HI_NAME, $this->getLastRetrieveDuration() );
                $output .= $this->formatHiToCsv( self::LAST_COMPUTE_RAW_DUR_HI_NAME, $this->getLastComputeRawDuration() );
                $output .= $this->formatHiToCsv( self::LAST_COMPUTE_KPI_DUR_HI_NAME, $this->getLastComputeKpiDuration() );
                $output .= $this->formatHiToCsv( self::LAST_COMPUTE_DUR_HI_NAME, $this->getLastComputeAllDuration() );
                $output .= $this->formatHiToCsv( self::NB_WAIT_FILE_HI_NAME, $this->getNbWaitFiles() );
                $output .= $this->formatHiToCsv( self::FAMILY_HISTORY_HI_NAME, $this->getFamilyHistory() );
                $output .= $this->formatHiToCsv( self::NB_LAST_DAY_COLLECTED_FILE_HI_NAME, $this->getNbLastDayCollectedFiles() );
                $output .= $saValues;
                $output .= $this->formatHiToCsv( self::NB_RAW_HI_NAME, $this->getNbRaw() );
                $output .= $this->formatHiToCsv( self::NB_MAPPED_RAW_HI_NAME, $this->getNbMappedRaw() );
                $output .= $this->formatHiToCsv( self::NB_CUSTOM_KPI_HI_NAME, $this->getNbCustomKpi() );
                $output .= $this->formatHiToCsv( self::NB_CLIENT_KPI_HI_NAME, $this->getNbClientKpi() );
                $output .= $this->formatHiToCsv( self::NB_STATIC_ALARMS_HI_NAME, $this->getNbStaticAlarms() );
                $output .= $this->formatHiToCsv( self::NB_DYN_ALARMS_HI_NAME, $this->getNbDynAlarms() );
                $output .= $this->formatHiToCsv( self::NB_TW_ALARMS_HI_NAME, $this->getNbTwAlarms());
                $output .= $this->formatHiToCsv( self::LAST_COMPUTE_ALARM_DUR_HI_NAME, $this->getLastComputeAlarmsDuration() );
                $output .= $this->formatHiToCsv( self::NB_DE_HI_NAME, $this->getNbDataExports() );
                $output .= $this->formatHiToCsv( self::LAST_DE_DUR_HI_NAME, $this->getLastDataExportsGenerationDuration() );
                $output .= $this->formatHiToCsv( self::NB_NE_HI_NAME, $this->getNbNe() );
                $output .= $this->formatHiToCsv( self::NB_NE_1_HI_NAME, $this->getNbFirstAxisNe() );
                $output .= $this->formatHiToCsv( self::NB_NE_3_HI_NAME, $this->getNbThirdAxisNe() );
                $output .= $this->formatHiToCsv( self::LICENSE_KEY, $this->getLicenseKeyValues() );
                $output .= $this->formatHiToCsv( self::DISK_SPACE_HI_NAME, $this->getDiscSpace() );
                $output .= $this->formatHiToCsv( self::NB_ACCOUNTS_HI_NAME, $this->getNbAccounts() );
                $output .= $this->formatHiToCsv( self::NB_USERS_LAST_DAY_HI_NAME, $this->getNbConnectedUsersLastDay() );
                $output .= $this->formatHiToCsv( self::NB_PAGES_LAST_DAY_HI_NAME, $this->getNbPagesLastDay() );
                $output .= $this->formatHiToCsv( self::DATE_HI_NAME, $this->strToTimeHi( 'now' ) );
                fwrite( $handle, $output );
                fclose( $handle );
                $retVal = TRUE;
            }
            else
            {
                // On retournera FALSE
            }
        }
        else
        {
            // On retournera FALSE
        }
        return $retVal;
    }

    /**
     * Formatte un indicateur de santé pour être écrit dans le fichier CSV
     * @param String $hiName Nom de l'indicateur de santé
     * @param Variant $hiValues Les données de l'indicateur à formatter
     * @return String Chaîne de caractères à insérer dans le fichier (retour à la ligne inclut)
     */
    private function formatHiToCsv( $hiName, $hiValues )
    {
        /** @var String Valeur de retour */
        $retVal = NULL;

        // Test si l'indicateur est NULL
        if( is_null( $hiValues ) === FALSE )
        {
            if( is_array( $hiValues ) === TRUE )
            {
                if( count( $hiValues ) > 0 )
                {
                    // Si l'indicateur est sous forme de tableau, on retournera une à une toutes les lignes
                    $retVal = ''; // Pour une meilleur concaténation
                    foreach( $hiValues as $entry )
                    {
                        $retVal .= $hiName.';'.$entry."\n"; // Les doubles cotes sont ici normales
                    }
                }
                else
                {
                    $retVal = $hiName.';'."\n"; // Les doubles cotes sont ici normales
                }
            }
            else
            {
                // Si l'indicateur est déja une chaîne de caractères, on la retourne avec le nom de l'indicateur
                $retVal = $hiName.';'.$hiValues."\n"; // Les doubles cotes sont ici normales
            }
        }
        else
        {
            $retVal = $hiName.';'."\n"; // Les doubles cotes sont ici normales
        }
        return $retVal;
    }
}
