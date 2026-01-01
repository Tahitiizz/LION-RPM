<?php
/**
 *  Classe gérant l'API de T&A
 *
 * $Author: f.regnier $
 * $Date: 2013-06-26 14:49:45 +0200 (mer., 26 juin 2013) $
 * $Revision: 115656 $
 *
 * 01/09/2010 OJT : Ajout de l'encodage en base64 dans le RetrieveLog (bz17642)
 * 19/11/2010 OJT : Modification de la méthode de retour de RetrieveLog (bz19246)
 * 05/03/2012 SPD : Ajout des methodes pour l'API querybuilder
 * 15/04/2013 NSE : Ajout du HI License Key
 * 22/05/2013 : WebService Topology
 */
require_once( dirname( __FILE__).'/ApiConnection.class.php' );
require_once( dirname( __FILE__).'/../Database.class.php' );
require_once( dirname( __FILE__).'/../DataBaseConnection.class.php' );
require_once( dirname( __FILE__).'/../HealthIndicator.class.php' );
require_once( dirname( __FILE__).'/../log/TALogAstFile.class.php' );
require_once( dirname( __FILE__).'/../../models/QueryDataModel.class.php' );
require_once( dirname(__FILE__)."/../../php/environnement_liens.php");
require_once( dirname( __FILE__).'/UploadFileLib.php' );
require_once( dirname( __FILE__).'/../../php/edw_function.php' );

//CB 5.3.1 WebService Topology
interface uploadFileInterface
{
    const sRequestReceived = 'sRequestReceived';
    const sCanNotGettingFile = 'sCanNotGettingFile';
    const sSambaError = 'sSambaError';
    const sWaitingForIntegration = 'sWaitingForIntegration';
    const sIntegrationInProgress = 'sIntegrationInProgress';
    const sIntegrationError = 'sIntegrationError';
    const sIntegrationFinished = 'sIntegrationFinished';
    
    const eUnknownFile = 'eUnknownFile';
    
    const repTopologyAsm = 'topology/asm/';
    const sfuaIdUserAsm = 'asm';
}

class TrendingAggregationApi implements uploadFileInterface
{
    const eOk                    = 'eOk';
    const eNotConnected          = 'eNotConnected';
    const eDisconnected          = 'eDisconnected';
    const eNotAllowed            = 'eNotAllowed';
    const eAlreadyConnected      = 'eAlreadyConnected';
    const eUnknowHealthIndicator = 'eUnknowHealthIndicator';

    /** @var HealthIndicator Object */
    protected $_hiObject;

    /** @var ApiConnection Object  */
    protected $_apiConnection;

    /** @var TALogAstFile Object */
    protected $_astLogObject;

    /**
     * Connexion à l'API via login et mot de passe
     *
     * @param String $login Login utilisateur
     * @param String $password Mot de passe
     * @return String Code de retour
     */
    public function connection( $login, $password )
    {
        $retVal = self::eNotConnected;

        // Test si la connexion n'a pas encore été établit
        if( is_null( $this->_apiConnection ) || !$this->_apiConnection->getConnectionState() )
        {
            $this->_apiConnection = new ApiConnection( $login, $password );
            if( $this->_apiConnection->getConnectionState() )
            {
                $retVal = self::eOk;
            }
            else
            {
                $retVal = self::eNotConnected;
            }
        }
        else
        {
           $retVal = self::eAlreadyConnected;
        }
        return $retVal;
    }

    /**
     * Alias de connection
     * @param String $asmLogin
     * @param String $asmPassword
     * @return String
     */
    public function Login( $asmLogin, $asmPassword )
    {
        return $this->connection( $asmLogin, $asmPassword );
    }

    /**
     * Déconnexion de l'API
     */
    public function disconnection()
    {
        unset( $this->_apiConnection );
        unset( $this->_hiObject );
        $this->_apiConnection = NULL;
        $this->_hiObject = NULL;
        return self::eOk;
    }

    /**
     * Alias de disconnection
     */
    public function Logout()
    {
        return $this->disconnection();
    }
    
    /**
     * Récupération d'un indicateur de santé
     * @param String $indicatorName Nom de l'indicateur à récupérer
     * @return String Array
     *
     * 16/09/2010 OJT : Correction bz17767, ajout de l'indicateur hi_date
     */
    public function getHealthIndicator( $indicatorName )
    {
        $retVal = $this->testConnection( API_CONNECTION_ADMIN );
        if( $retVal === self::eOk )
        {
            // La connexion à l'API est corecct
            if( is_null( $this->_hiObject ) === TRUE )
            {
                $this->_hiObject = new HealthIndicator();
            }
            else
            {
                // Ok, l'objet à déjà été créé
            }
            
            switch( trim( $indicatorName ) )
            {
                case HealthIndicator::LAST_COLLECT_DUR_HI_NAME :
                    $retVal = array( $this->_hiObject->getLastCollectDuration() ); break;

                case HealthIndicator::LAST_RETRIEVE_DUR_HI_NAME :
                    $retVal = array( $this->_hiObject->getLastRetrieveDuration() ); break;

                case HealthIndicator::LAST_COMPUTE_RAW_DUR_HI_NAME :
                    $retVal = array( $this->_hiObject->getLastComputeRawDuration() ); break;

                case HealthIndicator::LAST_COMPUTE_KPI_DUR_HI_NAME :
                    $retVal = array( $this->_hiObject->getLastComputeKpiDuration() ); break;

                case HealthIndicator::LAST_COMPUTE_DUR_HI_NAME :
                    $retVal = array( $this->_hiObject->getLastComputeAllDuration() ); break;

                case HealthIndicator::NB_WAIT_FILE_HI_NAME :
                    $retVal = array( $this->_hiObject->getNbWaitFiles() ); break;

                case HealthIndicator::FAMILY_HISTORY_HI_NAME :
                    $retVal = $this->_hiObject->getFamilyHistory(); break;

                case HealthIndicator::NB_LAST_DAY_COLLECTED_FILE_HI_NAME :
                    $retVal = array( $this->_hiObject->getNbLastDayCollectedFiles() ); break;

                case HealthIndicator::NB_RAW_HI_NAME :
                    $retVal = $this->_hiObject->getNbRaw(); break;

                case HealthIndicator::NB_MAPPED_RAW_HI_NAME :
                    $retVal = $this->_hiObject->getNbMappedRaw(); break;

                case HealthIndicator::NB_CUSTOM_KPI_HI_NAME :
                    $retVal = $this->_hiObject->getNbCustomKpi(); break;

                case HealthIndicator::NB_CLIENT_KPI_HI_NAME :
                    $retVal = $this->_hiObject->getNbClientKpi(); break;

                case HealthIndicator::NB_STATIC_ALARMS_HI_NAME :
                    $retVal = array( $this->_hiObject->getNbStaticAlarms() ); break;

                case HealthIndicator::NB_DYN_ALARMS_HI_NAME :
                    $retVal = array( $this->_hiObject->getNbDynAlarms() ); break;

                case HealthIndicator::NB_TW_ALARMS_HI_NAME :
                    $retVal = array( $this->_hiObject->getNbTwAlarms() ); break;

                case HealthIndicator::LAST_COMPUTE_ALARM_DUR_HI_NAME :
                    $retVal = $this->_hiObject->getLastComputeAlarmsDuration(); break;

                case HealthIndicator::NB_DE_HI_NAME :
                    $retVal = array( $this->_hiObject->getNbDataExports() ); break;

                case HealthIndicator::LAST_DE_DUR_HI_NAME :
                    $retVal = $this->_hiObject->getLastDataExportsGenerationDuration(); break;

                case HealthIndicator::NB_NE_HI_NAME :
                    $retVal = array( $this->_hiObject->getNbNe() ); break;

                case HealthIndicator::NB_NE_1_HI_NAME :
                    $retVal = array( $this->_hiObject->getNbFirstAxisNe() ); break;

                case HealthIndicator::NB_NE_3_HI_NAME :
                    $retVal = array( $this->_hiObject->getNbThirdAxisNe() ); break;

                case HealthIndicator::LICENSE_KEY :
                    $retVal = array( $this->_hiObject->getLicenseKeyValues() ); break;
                
                case HealthIndicator::DISK_SPACE_HI_NAME :
                    $retVal = $this->_hiObject->getDiscSpace(); break;

                case HealthIndicator::NB_ACCOUNTS_HI_NAME :
                    $retVal = array( $this->_hiObject->getNbAccounts() ); break;

                case HealthIndicator::NB_USERS_LAST_DAY_HI_NAME :
                    $retVal = array( $this->_hiObject->getNbConnectedUsersLastDay() ); break;

                case HealthIndicator::NB_PAGES_LAST_DAY_HI_NAME :
                    $retVal = array( $this->_hiObject->getNbPagesLastDay() ); break;

                case HealthIndicator::DATE_HI_NAME :
                    $retVal = array( strtotime( 'now' ) ); break;

                default :
                    $retVal = array( self::eUnknowHealthIndicator );
                    break;
            }
        }
        else
        {
            $retVal = array( $retVal ); // Retour sous forme de tableau
        }
        return $retVal;
    }

    /**
     *
     * @param string $dateTimestamp
     * @param string $timeagg
     * @param integer $global
     * @return string
     */
    public function getSAValues( $dateTimestamp, $timeagg, $global )
    {
        // Initialisation des variables (gestion des valaurs par défaut
        if( $dateTimestamp === '' )
        {
            $dateTimestamp = date( 'Ymd' );
        }

        if( $timeagg === '' )
        {
            $timeagg = 'day';
        }

        if( $global === NULL )
        {
            $global = 1;
        }

        // Les valeurs du SA sont uniquement disponibles pour un administrateur
        $retVal = $this->testConnection( API_CONNECTION_ADMIN );
        if( $retVal === self::eOk )
        {
            // La connexion à l'API est corecct
            if( is_null( $this->_hiObject ) === TRUE )
            {
                $this->_hiObject = new HealthIndicator();
            }
            else
            {
                // Ok, l'objet à déjà été créé
            }
            
            if( ( $retVal = $this->_hiObject->getSAValues( $dateTimestamp, $timeagg, $global ) ) === false )
            {
                // Si la méthode renvoi false, un simple tableau vide est retourné
                $retVal = array();
            }
        }
        else
        {
            $retVal = array( $retVal ); // Retour sous forme de tableau
        }
        return $retVal;
    }

    /**
     * Retourne les logs T&A au format Astellia
     * @param Integer $startTimestamp Timestamp du début des logs
     * @param Integer $endTimestamp Timestamp de la fin des logs
     * @param Integer $maxNbBytes Nombre d'octets maxumim à retourner
     * @return String
	 *
	 * 19/11/2010 : La méthode ne retourne désormais qu'une chaine et plus un array
     * 11/01/2011 : Correction des valeurs par défaut mal gérées
     */
    public function RetrieveLog( $startTimestamp, $endTimestamp, $maxNbBytes )
	{
        // Initialisation des valeurs par défaut (non gérées dans la signature)
        if( $startTimestamp === NULL ) $startTimestamp = 0;       
        if( $endTimestamp === NULL )   $endTimestamp   = 0;
        if( $maxNbBytes === NULL )     $maxNbBytes     = 0;
        
        $retVal = $this->testConnection( API_CONNECTION_ADMIN );
        if( $retVal === self::eOk )
        {
            try{
                $this->_astLogObject = new TALogAstFile( Database::getConnection(), $startTimestamp, $endTimestamp, $maxNbBytes);
            }
            catch( InvalidArgumentException $e ){
                return '-2'; // Les arguments passés sont mauvais
            }
            catch( Exception $e ){
                return '-1'; // Autres erreurs (base de données)
            }

            // L'objet TALogAstFile s'est bien créé
            $listLog = $this->_astLogObject->getListLog();
			$listLogSize = count( $listLog );
            $listLogReturn = "";
            if( $listLogSize > 0 )
            {
				$it = 0;				
				$flagLimitSize = false;
				$base64Size = 0;
				/*
				 * On boucle jusqu'a la taille maximum. TALogAstFile retourne en théorie
				 * un nombre limité de logs mais l'encodage en base64 influt beaucoup
				 * sur la taille de la chaine retourné. On effectue donc un deuxième
				 * test ici
				 */
				while ( ( $it < $listLogSize ) && ( $flagLimitSize === false ) )
				{
					$stringToAdd = implode( TALogAstFile::SEPARATOR, preg_replace( "/\n|\t/", " ", $listLog[$it] ) )."\n";
					$stringToAddSize = strlen( base64_encode( $stringToAdd ) );
					if( ( $maxNbBytes == 0 ) || ( $stringToAddSize + $base64Size <= $maxNbBytes ) )
					{
						$listLogReturn .= $stringToAdd;
						$base64Size += strlen( base64_encode( $stringToAdd ) );
					}
					else
					{
						$flagLimitSize = true;
					}
					$it++;
				}
                return base64_encode( $listLogReturn );
            }
            else
			{
                return '1'; // On ne renvoi pas une chaine vide
            }
        }
        else
        {
            return $retVal; // Retour sous forme de string
        }
    }

    /**
     * Retourne la version du produit (ex : Trending&Aggregation Cigale Iu 5.0.2.3 )
     * @return String
     */
    public function GetVersion()
    {
        $retVal = $this->testConnection( API_CONNECTION_ADMIN );
        if( $retVal === self::eOk )
        {
            try{
               $db = Database::getConnection();
            }
            catch( Exception $e ){
                return '-1';
            }
            return $db->getOne( 'SELECT array_to_string
                                    (
                                        array
                                        (
                                            SELECT value
                                            FROM sys_global_parameters
                                            WHERE parameters=\'product_name\'
                                            OR parameters=\'product_version\'
                                            ORDER BY parameters
                                        ), \' \'
                                    );');
        }
        else
        {
            return $retVal; // Retour sous forme de tableau
        }

    }
    
    
    /**
     * CB 5.3.1 WebService Topology
     * Request to upload file in topology
     * @return String
     */
    public function UploadFileRequest($netbios, $userSamba, $pdwSamba, $repository, $file)
    {      
        displayInDemon(date("Y-m-d H:i:s") . " [WebService Topo] : " . __FUNCTION__ . " (" . $netbios . ", "  . $userSamba . ", "  . $pdwSamba . ", "  . $repository . ", "  . utf8_decode($file) . ")", "title", true);

        $retVal = $this->testConnection( API_CONNECTION_ADMIN );
        if( $retVal === self::eOk )
        {
            //Missing input parameter
            if( $netbios=="" || $userSamba=="" || $pdwSamba=="" || $repository=="" || $file=="" ){
                displayInDemon(date("Y-m-d H:i:s") . " [WebService Topo] : Missing input parameter", "alert", true);
                return false;
            }
            else{
                //Check if a request for this file has been already done and if this file isn't again cancelled
                //Bug 34410 - [REC][CB 5.3.1.03][Webservice]UploadFileRequest failed with specials characters
                //$filename = basename($file);
                $filename = getCommandSafeFileName(basename($file));
                $row = selectFile($filename);

                //Yes => previous file has to be cancelled before treat the new file
                if($row != ""){
                    if($row['last_state'] == uploadFileInterface::sRequestReceived || 
                            $row['last_state'] == uploadFileInterface::sSambaError){
                        unlink($rep_tmp . $filename);
                    }
                    else if($row['last_state'] == uploadFileInterface::sWaitingForIntegration){
                        unlink($rep_final . $filename);           
                    }
                    cancelFile($row['id_file']);
                }

                //Insert new file whith state sRequestReceived
                $id_file = insertFile($filename);
                
                //Bug 34115 - [REC][CB 5.3.1.01][Webservice]UploadFileRequest with right parameters is error for the special character in filename
                if(strpos($file, '<') === false && strpos($file, '>') === false && strpos($file, '"') === false){
                    //Get the file in parallel
                    exec("php -q ".dirname( __FILE__)."/UploadFileRequest.php \"$netbios\" \"$userSamba\" \"$pdwSamba\" \"$repository\" \"$file\" \"$id_file\" >> /dev/null &");                
                }
                else{
                    updateState($id_file, uploadFileInterface::sCanNotGettingFile);
                    displayInDemon(date("Y-m-d H:i:s") . " [WebService Topo] : " . uploadFileInterface::sCanNotGettingFile, "alert", true); 
                }
                return true;
            }
        }
        else
        {
            displayInDemon(date("Y-m-d H:i:s") . " [WebService Topo] : " . $retVal, "alert", true);
            return $retVal;
        }      
    }
    
    
    /**
     * CB 5.3.1 WebService Topology
     * Returns the file's state
     * @return String
     */
    public function UploadFileState($file)
    {
        displayInDemon(date("Y-m-d H:i:s") . " [WebService Topo] : " . __FUNCTION__ . " (" . $file . ")", "title", true);

        $retVal = $this->testConnection( API_CONNECTION_ADMIN );
        if( $retVal === self::eOk )
        {
            if( $file=="" ){
                displayInDemon(date("Y-m-d H:i:s") . " [WebService Topo] : Missing input parameter", "alert", true);
                return self::eUnknownFile;
            }
            else{
                $row = selectFile($file);
            
                if($row == "")
                    return self::eUnknownFile;
                else
                    return $row['last_state'];
            }
        }
        else
        {
            displayInDemon(date("Y-m-d H:i:s") . " [WebService Topo] : " . $retVal, "alert", true);
            return $retVal;
        }
    }
    
    
    /**
     * CB 5.3.1 WebService Topology
     * Request to cancel to upload file in topology
     * @return String
     */
    public function UploadFileCancel($file)
    {
        displayInDemon(date("Y-m-d H:i:s") . " [WebService Topo] : " . __FUNCTION__ . " (" . $file . ")", "title", true);

        $retVal = $this->testConnection( API_CONNECTION_ADMIN );
        if( $retVal === self::eOk )
        {   
            if( $file=="" ){
                displayInDemon(date("Y-m-d H:i:s") . " [WebService Topo] : Missing input parameter", "alert", true);
                return self::eUnknownFile;
            }
            else{
                $row = selectFile($file);

                if($row == ""){
                    return self::eUnknownFile;
                }
                else{
                    if($row['last_state']==self::sRequestReceived ||
                        $row['last_state']==self::sSambaError){

                            exec("rm -f " . REP_PHYSIQUE_NIVEAU_0 . self::repTopologyAsm . 'tmp/' . $file);
                    }
                    else if($row['last_state']==self::sWaitingForIntegration){
                            exec("rm -f " . REP_PHYSIQUE_NIVEAU_0 . self::repTopologyAsm . $file);
                    }
                    cancelFile($row['id_file']);
                    return true;
                }
            }
        }
        else
        {
            displayInDemon(date("Y-m-d H:i:s") . " [WebService Topo] : " . $retVal, "alert", true);
            return $retVal;
        }
    }
    
    
    /**
     * CB 5.3.1 WebService Topology
     * Get a list of files not yet acquitted
     * @return String
     */
    public function UploadFileList()
    {
        displayInDemon(date("Y-m-d H:i:s") . " [WebService Topo] : " . __FUNCTION__ . " (" . $file . ")", "title", true);

        $retVal = $this->testConnection( API_CONNECTION_ADMIN );
        if( $retVal === self::eOk )
        {   
            $row = selectAllFiles();
            return $row;
        }
        else
        {
            displayInDemon(date("Y-m-d H:i:s") . " [WebService Topo] : " . $retVal, "alert", true);
            //Bug 34051 - [REC][CB_5.3.1.01][#TA-62527][Webservice]: UploadFileList's return is wrong when not connected
            //return $retVal;
            return array($retVal);
        }
    }
 

    /**
     * Retourne les information systèmes (version OS et nom de machine)
     * @return Array
     */
    public function GetOSInfos()
    {
        $retVal = $this->testConnection( API_CONNECTION_ADMIN );
        if( $retVal === self::eOk )
        {
            return array( exec( 'cat /etc/redhat-release 2>/dev/null' ), php_uname( 'n' ) );
        }
        else
        {
            return array( $retVal ); // Retour sous forme de tableau
        }
    }

    /**
     * Retourne les information d'utilisation disque
     * @return Array
     */
    public function GetDiskUse()
    {
        // On appelle la méthode des indicateurs de santé
        return $this->getHealthIndicator( 'hi_storage_disc_space' );
    }

    /**
     * Test si la connection à l'API est active et vérifie que le niveau
     * demandé est suffisant
     * @param Integer $levelRequired (API_CONNECTION_USER, ... )
     * @return eError
     */
    private function testConnection( $levelRequired )
    {
        $retVal = self::eNotConnected;
        if( ( is_null( $this->_apiConnection ) === FALSE ) && ( $this->_apiConnection->getConnectionState() === TRUE  ) )
        {
            if( $this->_apiConnection->getConnectionLevel() >= $levelRequired  )
            {
                // Vérification du timeout
                if( $this->_apiConnection->isConnectionActiv() === TRUE )
                {
                    $retVal = self::eOk;
                }
                else
                {
                    $retVal = self::eDisconnected;
                }
            }
            else
            {
                $retVal = self::eNotAllowed;
            }
        }
        else
        {
            // Erreur
        }
        return $retVal;
    }

	/** Retreive data from the database
	* @param $queryJsonString query receive from the API (JSON string)
	* @return string response (JSON string)
	*/	
    public function getData($queryJsonString) {    	
		return $this->callJsonAPI('getData', $queryJsonString);
	}
	
	/** Get KPI list from the database
	* @param $getKpiListJsonString JSON string received from the API
	* @return string response JSON list
	*/	
    public function getKpiList($getKpiListJsonString) {    	
		return $this->callJsonAPI('getKpiList', $getKpiListJsonString);
	}
	
	/** Get RAW list from the database
	* @param $getRawListJsonString query receive from the API
	* @return string response JSON list
	*/	
    public function getRawList($getRawListJsonString) {    	
		return $this->callJsonAPI('getRawList', $getRawListJsonString);
	}

	/** Get NE list
	* @param $jsonRequest query receive from the API
	* @return string response JSON list
	*/	
    public function getNeList($getNeListJsonString) {    	
		return $this->callJsonAPI('getNeList', $getNeListJsonString);
	}

	/** Get aggregation (TA & NA) in common
	* @param $jsonRequest query receive from the API
	* @return string response JSON list
	*/	
    public function getAggInCommon($getAggInCommonJsonString) {    	
		return $this->callJsonAPI('getAggInCommon', $getAggInCommonJsonString);
	}

	/** Get products/families 
	* @param $jsonRequest query receive from the API
	* @return string response JSON list
	*/	
    public function getProductsFamilies($getProductsFamiliesJsonString) {    	
		return $this->callJsonAPI('getProductsFamilies', '{}');
	}
	
	/** JSON API call
	* @param $methodName string method name
	* @param $parameter string JSON string
	* @return JSON string response
	*/	
	private function callJsonAPI($methodName, $parameter) {
		$dataMod = new QueryDataModel();				
		return $dataMod->getJsonResponse('{"method":"'.$methodName.'", "parameters":'.$parameter.'}');	
	}    
}
