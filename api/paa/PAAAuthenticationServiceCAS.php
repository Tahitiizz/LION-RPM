<?php
/**
 * @version 1.0 $Id: PAAAuthenticationServiceCAS.php 131468 2014-01-14 10:30:04Z n.stienne $
 *
 * 08/03/2012 NSE DE Logout
 * 12/11/2012 BBX/NSE bz 30094/29898 : lenteurs lors de la navigation -> mise en session des informations afin d'éviter des appels Portail
 * 07/05/2013 NSE bz 33388: add of doesPortalServerApiManage() method
 * 26/08/2013 MGO bz 34649 : cas cache erased on add or deleteProfile with new resetCasCacheUserRights function
 */

include_once 'PAAAuthenticationService.php';
include_once 'PAAAuthenticationServiceFileDegradedMode.php';
include_once 'lib/CAS.php';
ini_set('soap.wsdl_cache_enabled', SOAP_CACHE);

/**
 * Utilisation d'un singleton pour éviter la création multiplde d'objets, 
 * donc diminuer les appels au Portail
 * 
 * 19/10/2012 NSE bz 29953 création de la classe
 */
class UserSingleton{
    
    private static $_user = null;
    
    private function __construct($PAAAuthentication) {
        phpCAS :: trace(' ************************** '.date('Ymd H:i:s').' UserSingleton::__construct($PAAAuthentication) ' );
        // 05/11/2012 NSE bz 30219 : l'objet doit exister avant l'appel à getUser()
        global $PHPCAS_CLIENT;
        if (!is_object($PHPCAS_CLIENT)) {
            phpCAS::client(CAS_VERSION_2_0, CAS_SERVER, CAS_PORT, CAS_URI, false);
        }
        $login = phpCAS::getUser();
        $attributes = $PAAAuthentication->getUserAttributes($login);
        $mail = $attributes['mail'];
        $fullname = $attributes['fullname'];
        // NSE 04/01/2013 DE PhoneNumber API
        if(isset($attributes['phonenumber']))
            $phonenumber = $attributes['phonenumber'];
        self::$_user = new PAAUser($login, $login, $fullname ? $fullname : '', $mail ? $mail : '', isset($phonenumber) ? $phonenumber : '');
    }
    
    /**
     * Retourne l'instance d'objet (existante si possible, sinon en crée une)
     * @param type $PAAAuthentication
     * @return PAAUser 
     */
    public static function getInstance($PAAAuthentication) {
        phpCAS :: trace(' ************************** '.date('Ymd H:i:s').' UserSingleton::getInstance($PAAAuthentication)' );
        if(is_null(self::$_user)) {
            new UserSingleton($PAAAuthentication);  
        }
        return self::$_user;
    }
    
   /* public function __call($name,$args){
        $back = debug_backtrace();
        $var = print_r($back,true);

       // if(method_exists(self::$_user, $name))
       // {
			$ret = self::$_user->$name(implode(', ', $args));
            return $ret;
      //  }
        
    }
    public function __get($name) {
        return self::$_user->$name;
    }*/
}

class PAAAuthenticationServiceCAS extends PAAAuthenticationService {
  
  /**
   * the user currently login
   * @var $user
   */
  private $user;
  
  /**
   * Degraded Mode Service
   *
   * @var $serviceFileDegradedMode
   */
  private $serviceFileDegradedMode = null;
  
  protected $_casCacheSession = array();
  public static $soapObject = null;
  
  /**
   * constructor function   
   */
  public function __construct() {
	$this->serviceFileDegradedMode = new PAAAuthenticationServiceFileDegradedMode();
	if(!empty($_SESSION['cas_cache'])) $this->_casCacheSession = $_SESSION['cas_cache'];
  }
 
  public function __destruct() {
	$_SESSION['cas_cache'] = array_merge(array_key_exists('cas_cache', $_SESSION) ? (array)$_SESSION['cas_cache'] : array(), $this->_casCacheSession);
  }

  /**
   * Set enabled/disabled Degraded Mode
   */
  protected function setDegradedMode($value)
  {
    $_SESSION['CAS_DEGRADEDMODE_ENABLED'] = $value;
  }
  
  /**
   * Degraded Mode enabled ?
   */
  public function isDegradedMode()
  {
    if (isset($_SESSION['CAS_DEGRADEDMODE_ENABLED']))
        return $_SESSION['CAS_DEGRADEDMODE_ENABLED'] === 1;
    else
        return false;
  }

  /**
   * Vérifie si la connexion est enregistrée en local et si elle est valide
   * @param string $session_id Identifiant de la session (typiquement $_SESSION['session_id'])
   * @return int 0 : la session n'est pas connue localement
   *             1 : session connue localement et valide
   *            -1 : session connue localement et mais non valide
   * 
   * 08/03/2012 NSE DE Logout : Création
   */
  public function localValidation($session_id){
      $db = new PDO('sqlite:'.SQLITE_DIR.SQLITE_SESSION_FILE); 
      $q = $db->query("SELECT validity FROM local_session_validity WHERE session_id='".$session_id."'");
      
      $result = $q->fetch();
      if(empty ($result)){
        // aucun enregistrement pour cet identifiant de session
        return 0;
      }  
      else{ 
        if($result['validity']==1){
          return 1;
        }
        else{
          // la session a été enregistrée en local mais n'est plus valide
            return -1;
        }
      }
  }

  /**
   * Enregistre la session en local
   * @param string $session_id Identifiant de la session (typiquement $_SESSION['session_id'])
   * @return boolean true si l'enregistrement a réussit, false sinon.
   * 
   * 08/03/2012 NSE DE Logout : Création
   */
  public function localSessionSave($session_id){ 
      // 19/10/2012 NSE bz 29926: ajout de log
      $db = new PDO('sqlite:'.SQLITE_DIR.SQLITE_SESSION_FILE);
      // suppression des enregistrements de plus de 24 heures
      $db->exec("DELETE FROM local_session_validity WHERE create_date<(strftime('%s','now')-86400)");
      // 19/10/2012 NSE bz 29926: on n'enregistre la session que si l'id_session n'est pas vide.
      if(!empty($session_id)){
          // création de la nouvelle session
          $q = $db->exec("INSERT INTO local_session_validity (session_id, validity, last_real_check, nb_of_use, create_date) VALUES ('".$session_id."', 1,strftime('%s','now'),0,strftime('%s','now'))"); // , last_real_check, nb_of_use , NOW(), 0
          if($q==1){
              phpCAS :: trace(' '.date('Ymd H:i:s').' - session_id '.$session_id.' saved in local session table' );
              return true;
          }
          else{ 
            phpCAS :: trace(' '.date('Ymd H:i:s').' ERREUR - session_id '.$session_id.' not saved in local session table: '.$q );
            return false;
          }
      }
      else
          phpCAS :: trace(' '.date('Ymd H:i:s').' WARNING - session_id '.$session_id.' not saved in local session table: '.$q );

  }

    /**
     * use this function to check if the user is authenticate
     */
    public function validateAuthentication() 
    {
        global $PHPCAS_CLIENT;
        //$back = debug_backtrace();
        //$var = print_r($back,true);
        //phpCAS :: trace(' ******************************************************'.date('Ymd H:i:s').' : validateAuthentication ='.$back[0]['file'] );

        // ------------------------------------------------------------------------------
        // degraded mode
        if ($this->isDegradedMode()) {
            return $this->serviceFileDegradedMode->validateAuthentication();        
        }
        // degraded mode
        // ------------------------------------------------------------------------------
        // 08/03/2012 NSE DE Logout
        if(!isset($_SESSION)) session_start();
        $session_id = '';
        if(!empty($_SESSION['cas_session_id'])){
            $session_id = $_SESSION['cas_session_id'];		
        }
        elseif(!empty($_GET['ticket'])){
            $session_id = $_GET['ticket'];
            $_SESSION['cas_session_id'] = $session_id;
        }
        $ret = false;
        // On teste le chemin du log
        // La gestion du message d'erreur est faite dans /index.php
        if (CAS_DEBUG && is_writable(CAS_LOG_PATH)) phpCAS::setDebug(CAS_LOG_PATH.'/'.CAS_LOG_FILE);

        // 08/03/2012 NSE DE Logout
        // si des sessions sont enregistées par erreur sans id_session, on les supprime
        localSessionDestroy('');
        $localSession = $this->localValidation($session_id);

        // L'utilisateur n'est plus authentifié.
        // 19/11/2012 NSE bz 29952 : ajout du test sur $session_id pour logout VipCare
        if($localSession==-1 || empty($session_id)) {
            // 19/10/2012 NSE bz 29952: création de l'objet s'il n'existe pas et gestion du logout
            if (!is_object($PHPCAS_CLIENT)) {
                phpCAS::client(CAS_VERSION_2_0, CAS_SERVER, CAS_PORT, CAS_URI, false);
            }
            // est-on sur une requête de logout?
            phpCAS::setSingleSignoutCallback('localSessionDestroy');
            phpCAS::handleLogoutRequests(false);

            unset($_SESSION['cas_session_id']);
            return false;
        }

        if($localSession==1) {
            // on prépare l'utilisateur
            // 19/10/2012 NSE bz 29780: création de l'objet s'il n'existe pas
            if (!is_object($PHPCAS_CLIENT)) {
                phpCAS::client(CAS_VERSION_2_0, CAS_SERVER, CAS_PORT, CAS_URI, false);
            }
            phpCAS::isAuthenticated();

            // si l'appel courant provient d'une demande de déconnexion de la part du Portail
            phpCAS::setSingleSignoutCallback('localSessionDestroy');
            phpCAS::handleLogoutRequests(false);

            $login = phpCAS::getUser();
            // 19/10/2012 NSE bz 29953
            // on remplace le new PAAUser($login, $login, $fullname ? $fullname : '', $mail ? $mail : '');
            // par l'appel à getInstance de façon à éviter les appels portal trop nombreux via $this->getUserAttributes()
            $this->user = UserSingleton::getInstance($this);    
            return $localSession;
        }
        // si la session n'est pas connue en locale, on continue (on va vérifier et l'enregistrer si elle est valide)
        if (!$this->user)
        {  
            // YAX - BUG #23700
            // On teste la présence du server CAS
            $fp = @fsockopen(CAS_SERVER, CAS_PORT, $errno, $errstr);
            if (!$fp) 
            {
                // mode dégradé activé ?
                if (defined('CAS_DEGRADEDMODE_ENABLED'))
                  if (CAS_DEGRADEDMODE_ENABLED==1)
                    $this->setDegradedMode(CAS_DEGRADEDMODE_ENABLED);

                // message d'erreur si le mode dégradé n'est pas activé.
                if (!$this->isDegradedMode()) {  
                    echo "CAS server not enabled or bad configuration <br />$errstr ($errno)<br />\n";
                    exit;
                }
                else {           
                    return $this->serviceFileDegradedMode->validateAuthentication();
                }
            } 
            else {
                fclose($fp);
            }

            phpCAS::client(CAS_VERSION_2_0, CAS_SERVER, CAS_PORT, CAS_URI, false);
            phpCAS::setNoCasServerValidation();
            // 08/03/2012 NSE DE Logout
            phpCAS::setSingleSignoutCallback('localSessionDestroy');
            phpCAS::handleLogoutRequests(false);
            $ret = phpCAS::checkAuthentication();

            if ($ret) 
            {
                $login = phpCAS::getUser();
                $attributes = $this->getUserAttributes($login);
                $mail = $attributes['mail'];
                $fullname = $attributes['fullname'];

                // 19/10/2012 NSE bz 29953
                // on remplace le new PAAUser($login, $login, $fullname ? $fullname : '', $mail ? $mail : '');
                // par l'appel à getInstance de façon à éviter les appels portal trop nombreux via $this->getUserAttributes()
                $this->user = UserSingleton::getInstance($this);        
            }
        }
        else 
        {
            // 08/03/2012 NSE DE Logout
            phpCAS::setSingleSignoutCallback('localSessionDestroy');
            phpCAS::handleLogoutRequests(false);
            $ret = phpCAS::checkAuthentication();
        }

        // 08/03/2012 NSE DE Logout
        if($ret){
            // la session n'est pas connue en locale, elle est vérifiée.
            // on l'enregistre
            // 19/10/2012 NSE bz 29926: déplacement du log dans la méthode elle-même
            $this->localSessionSave($session_id);
        }

        return $ret;
    }
  
    /**
     * use this function to get a User
     * @see PAAUser
     */
    public function getUser() 
    {
        // ------------------------------------------------------------------------------
        // degraded mode
        if ($this->isDegradedMode()) 
          return $this->serviceFileDegradedMode->getUser();        
        // degraded mode
        // ------------------------------------------------------------------------------

        //19/10/2012 NSE bz 29953
        $this->user = UserSingleton::getInstance($this);
        return $this->user;
    }
  
    /**
     * @return the login url
     */
    public function getLoginUrl() 
    {
        // ------------------------------------------------------------------------------
        // degraded mode
        if ($this->isDegradedMode()) 
          return $this->serviceFileDegradedMode->getLoginUrl();        
        // degraded mode
        // ------------------------------------------------------------------------------

        // YAX: gestion du port et https dans l'url
        // GFS 05/08/2013 - Bug 34772 - TA ne fonctionne pas en full https via le portail
        $scheme = $_SERVER["SERVER_PORT"] == 443 ? 'https' : 'http';
        $add = $scheme.'://'.$this->getServerUrl().urlencode($_SERVER["REQUEST_URI"]);
        // Mantis 4270 gestion de deux réseaux
        // utilisation de l'adresse publique du Portail
        return 'https://'.CAS_SERVER_PUBLIC.":".CAS_PORT.CAS_URI."login"."?destination=".$add."&service=".$add."&url=".$add;
    }
  
    /**
     * use this function to logout an user
     */
    public function logout() 
    {
        // ------------------------------------------------------------------------------
        // degraded mode
        if ($this->isDegradedMode()) {
            $this->setDegradedMode(0);
            return $this->serviceFileDegradedMode->logout();        
        }
        // degraded mode
        // ------------------------------------------------------------------------------

        $pos = strpos($_SERVER["REQUEST_URI"], '?');
        $uri = $_SERVER["REQUEST_URI"];
        if ($pos) {
            $uri = substr($_SERVER["REQUEST_URI"], 0, $pos);
        }

        // Mantis 4270 gestion de deux réseaux
        // utilisation de l'adresse publique du Portail
        phpCAS::setServerLogoutURL("https://".CAS_SERVER_PUBLIC."/");
        phpCAS::logout();
    }
  
    /**
     * getSOAP
     * @return \SoapClient
     */
    private function getSOAP() 
    {
        $client = null;

        if(is_object(self::$soapObject)) {
            return self::$soapObject;
        }

        try {
            // Instanciation du client SOAP
            $client = new SoapClient(WEB_SERVICE_URL, array('trace'=> SOAP_TRACE,
                                                            'wsdl_cache' => SOAP_CACHE,
                                                            'soap_version'=> SOAP_1_1));
            self::$soapObject = $client;
        } catch (Exception $e) {
            echo 'Exception received : ',  $e->getMessage(), "\n";
        }

        return $client;
    }

    /**
     * use this function to list users with at least one of the rights in array provided
     */
    public function getUsersWithRights($guidArray, $appId, $flag) 
    {
        // ------------------------------------------------------------------------------
        // degraded mode
        if ($this->isDegradedMode()) 
            return $this->serviceFileDegradedMode->getUsersWithRights($guidArray, $appId, $flag);
        // degraded mode
        // ------------------------------------------------------------------------------
        phpCAS :: trace(' ------- '.date('Ymd H:i:s').' getUsersWithRights('.print_r($guidArray,true).') ' );
        // 07/11/2012 BBX
        // BZ 29898 : caching CAS calls
        $key = md5(print_r($guidArray,true)).$appId.$flag;
        if(isset($this->_casCacheSession[__METHOD__][$key])) {
            phpCAS :: trace(' ------- '.date('Ymd H:i:s').' return CacheSession ' );
            //phpCAS :: trace(' ------- '.date('Ymd H:i:s').' _casCacheSession: '.print_r($this->_casCacheSession[__METHOD__][$key],true) );
            return $this->_casCacheSession[__METHOD__][$key];
        }
        phpCAS :: trace(' ------- '.date('Ymd H:i:s').' appel Soap ' );

        $client = $this->getSOAP();
        try {
            $retour_ws =  $client->__call('getUsersWithRights',array($guidArray, $appId, $flag));
            $this->_casCacheSession[__METHOD__][$key] = $retour_ws;
        } catch (Exception $e) {
            echo 'Exception received : ',  $e->getMessage(), "\n";
        }
        //phpCAS :: trace(' ------- '.date('Ymd H:i:s').' soap: '.print_r($retour_ws,true) );
        return $retour_ws;
    }
  
    /**
     * use this function to list users among array provided who have no rights to this application
     */
    public function getUsersWithNoRights($loginsArray, $appId) 
    {
        // ------------------------------------------------------------------------------
        // degraded mode
        if ($this->isDegradedMode()) 
            return $this->serviceFileDegradedMode->getUsersWithNoRights($loginsArray, $appId);
        // degraded mode
        // ------------------------------------------------------------------------------
        phpCAS :: trace(' ------- '.date('Ymd H:i:s').' getUsersWithNoRights() ' );
        // 07/11/2012 BBX
        // BZ 29898 : caching CAS calls
        $key = md5(print_r($loginsArray,true)).$appId;
        if(isset($this->_casCacheSession[__METHOD__][$key])) {
            phpCAS :: trace(' ------- '.date('Ymd H:i:s').' return CacheSession ' );
            return $this->_casCacheSession[__METHOD__][$key];
        }
        phpCAS :: trace(' ------- '.date('Ymd H:i:s').' appel Soap ' );
        $client = $this->getSOAP();
        try {
            $retour_ws =  $client->__call('getUsersWithNoRights',array($loginsArray, $appId));
            $this->_casCacheSession[__METHOD__][$key] = $retour_ws;
        } catch (Exception $e) {
            echo 'Exception received : ',  $e->getMessage(), "\n";
        }
        return $retour_ws;
    }
  
    /**
     * use this function to get a user's attributes
     */
    public function getUserAttributes($login) 
    {
        // ------------------------------------------------------------------------------
        // degraded mode
        if ($this->isDegradedMode()) 
            return $this->serviceFileDegradedMode->getUserAttributes($login);
        // degraded mode
        // ------------------------------------------------------------------------------
        phpCAS :: trace(' ------- '.date('Ymd H:i:s').' getUserAttributes() ' );
        // 07/11/2012 BBX
        // BZ 29898 : caching CAS calls

        if(isset($this->_casCacheSession[__METHOD__][$login])) {
            phpCAS :: trace(' ------- '.date('Ymd H:i:s').' return CacheSession ' );
            return $this->_casCacheSession[__METHOD__][$login];
        }
        phpCAS :: trace(' ------- '.date('Ymd H:i:s').' appel Soap ' );
        $client = $this->getSOAP();
        try {
            $retour_ws =  $client->__call('getUserAttributes',array($login));
            $this->_casCacheSession[__METHOD__][$login] = $retour_ws;
        } catch (Exception $e) {
            echo 'Exception received : ',  $e->getMessage(), "\n";
        }

        return $retour_ws;
    }
  
    /**
     * use this function to delete a right
     */
    public function deleteRight($rightId) 
    {  
        // ------------------------------------------------------------------------------
        // degraded mode
        if ($this->isDegradedMode()) 
            return $this->serviceFileDegradedMode->deleteRight($rightId);
        // degraded mode
        // ------------------------------------------------------------------------------

        $client = $this->getSOAP();
        try {
            $retour_ws =  $client->__call('deleteRight',array($rightId));
        } catch (Exception $e) {
            echo 'Exception received : ',  $e->getMessage(), "\n";
        }
        return $retour_ws;
    }
  
    /**
     * getUserRights method
     *
     * @param User $user User object
     * @param string $appId App ID
     */
    public function getUserRights ($user,$appId)
    {
        // ------------------------------------------------------------------------------
        // degraded mode
        if ($this->isDegradedMode()) 
          return $this->serviceFileDegradedMode->getUserRights($user,$appId);
        // degraded mode
        // ------------------------------------------------------------------------------
        phpCAS :: trace(' ------- '.date('Ymd H:i:s').' getUserRights() ' );
        // 07/11/2012 BBX
        // BZ 29898 : caching CAS calls
        if(isset($this->_casCacheSession[__METHOD__][$user.$appId])) {
            phpCAS :: trace(' ------- '.date('Ymd H:i:s').' return CacheSession ' );
            return $this->_casCacheSession[__METHOD__][$user.$appId];
        }
        phpCAS :: trace(' ------- '.date('Ymd H:i:s').' appel Soap ' );
        $client = $this->getSOAP();
        try {
            $retour_ws =  $client->__call('getUserRights',array($user,$appId));
            $this->_casCacheSession[__METHOD__][$user.$appId] = $retour_ws;
        } catch (Exception $e) {
            echo 'Exception received : ',  $e->getMessage(), "\n";
        }
        return $retour_ws;
    }
  
    /**
     * resetCasCacheUserRights method
     *
     * @param User $user User object
     * @param string $appId App ID
     */  
	
    public function resetCasCacheUserRights ($user,$appId)
    {
        // ------------------------------------------------------------------------------
        phpCAS :: trace(' ------- '.date('Ymd H:i:s').' resetCasCacheUserRights() ' );
        if(isset($this->_casCacheSession['PAAAuthenticationServiceCAS::getUserRights'][$user.$appId])) {
                unset($this->_casCacheSession['PAAAuthenticationServiceCAS::getUserRights'][$user.$appId]);
                unset($_SESSION['cas_cache']['PAAAuthenticationServiceCAS::getUserRights'][$user.$appId]);
        }
        return true;
    }
  
    /**
     * getUserRights method
     *
     * @param User $user User object
     */
    public function getUserRoles ($user)
    {
        // ------------------------------------------------------------------------------
        // degraded mode
        if ($this->isDegradedMode()) 
            return $this->serviceFileDegradedMode->getUserRoles($user);
        // degraded mode
        // ------------------------------------------------------------------------------
        phpCAS :: trace(' ------- '.date('Ymd H:i:s').' getUserRoles() ' );
        // 07/11/2012 BBX
        // BZ 29898 : caching CAS calls
        if(isset($this->_casCacheSession[__METHOD__][$user])) {
            phpCAS :: trace(' ------- '.date('Ymd H:i:s').' return CacheSession ' );
            return $this->_casCacheSession[__METHOD__][$user];
        }
        phpCAS :: trace(' ------- '.date('Ymd H:i:s').' appel Soap ' );
        $client = $this->getSOAP();
        try {
            $retour_ws =  $client->__call('getUserRoles',array($user));
            $this->_casCacheSession[__METHOD__][$user] = $retour_ws;
        } catch (Exception $e) {
            echo 'Exception received : ',  $e->getMessage(), "\n";
        }
        return $retour_ws;
    }
  
  /**
   * addApplication method
   *
   * @param string $application application Id
   * @param string $xml the xml describe API
   */
  public function setApplication ($xml){
  
    // ------------------------------------------------------------------------------
    // degraded mode
    if ($this->isDegradedMode()) 
      return $this->serviceFileDegradedMode->setApplication($xml);
    // degraded mode
    // ------------------------------------------------------------------------------
  
    $client = $this->getSOAP();
    try {
      $retour_ws =  $client->__call('setApplication',array($xml));
    } catch (Exception $e) {
  		echo 'Exception received : ',  $e->getMessage(), "\n";
  	}
  	return $retour_ws;
  }
  
    /**
      * Is Support User ?
      *
      * @param string $username username
      * @return boolean Is Support User ?
      */
    public function isSupportUser($username) 
    {
        // ------------------------------------------------------------------------------
        // degraded mode
        if ($this->isDegradedMode()) 
            return $this->serviceFileDegradedMode->isSupportUser($username);
        // degraded mode
        // ------------------------------------------------------------------------------
        phpCAS :: trace(' ------- '.date('Ymd H:i:s').' isSupportUser() '.__METHOD__.', user'.$username );
		//phpCAS :: trace('_casCacheSession: '.print_r($this->_casCacheSession,true));
        // 07/11/2012 BBX
        // BZ 29898 : caching CAS calls
        if(isset($this->_casCacheSession[__METHOD__][$username])) {
            phpCAS :: trace(' ------- '.date('Ymd H:i:s').' return CacheSession ' );
            return $this->_casCacheSession[__METHOD__][$username];
        }
        phpCAS :: trace(' ------- '.date('Ymd H:i:s').' appel Soap ' );
        $client = $this->getSOAP();
        try {
	            $retour_ws =  $client->__call('isSupportUser',array($username));
	            $this->_casCacheSession[__METHOD__][$username] = $retour_ws;
        } catch (Exception $e) {
            if (!stripos($e->getMessage(), "is not a valid method for this service"))
            echo 'Exception received : ',  $e->getMessage(), "\n";
            $retour_ws = false;
        }
		//phpCAS :: trace('_casCacheSession2: '.print_r($this->_casCacheSession,true));
        return $retour_ws;
    }
    
    /**
     * Is Valid User ?
     *
     * @param string $username username
     * @return boolean Is Valid User ?
     */
    public function isValidUser($username)
    {
    	phpCAS :: trace(' ------- '.date('Ymd H:i:s').' isValidUser() '.__METHOD__.', user'.$username );
    	if(isset($this->_casCacheSession[__METHOD__][$username])) {
    		phpCAS :: trace(' ------- '.date('Ymd H:i:s').' return CacheSession ' );
    		return $this->_casCacheSession[__METHOD__][$username];
    	}
    	phpCAS :: trace(' ------- '.date('Ymd H:i:s').' appel Soap ' );
    	$client = $this->getSOAP();
    	try {
    		$retour_ws =  $client->__call('isValidUser',array($username));
    		$this->_casCacheSession[__METHOD__][$username] = $retour_ws;
    	} catch (Exception $e) {
    		if (!stripos($e->getMessage(), "is not a valid method for this service"))
    			echo 'Exception received : ',  $e->getMessage(), "\n";
    		$retour_ws = false;
    	}
    	return $retour_ws;
    }
  
    /**
     * Returns true if the module is managed by Portal Api, else false
     *
     * @param string $module
     * @return boolean
     */
    public function doesPortalServerApiManage($module)
    {
        // ------------------------------------------------------------------------------
        // degraded mode
        if ($this->isDegradedMode()) 
            return $this->serviceFileDegradedMode->doesPortalServerApiManage($module);
        // degraded mode
        // ------------------------------------------------------------------------------

        // caching CAS calls
        if(isset($this->_casCacheSession[__METHOD__][$module])) {
            return $this->_casCacheSession[__METHOD__][$module];
        }

        $client = $this->getSOAP();
        try {
            $retour_ws =  $client->__call('doesPortalServerApiManage',array($module));
            $this->_casCacheSession[__METHOD__][$module] = $retour_ws;
        } catch (Exception $e) {
            if($e->getMessage()=='Function ("doesPortalServerApiManage") is not a valid method for this service')
                return false;
            echo 'Exception received ', "(", $e->getCode(),"): ", $e->getMessage(), "\n";
        }
        return $retour_ws;
    }
    
}

/**
* Supprime la session en local
* @param string $session_id Identifiant de la session (typiquement le ticket)
* @return boolean true si l'enregistrement a été supprimé, false sinon.
 * 
 * 08/03/2012 NSE DE Logout : création
*/
function localSessionDestroy($session_id) {
  $db = new PDO('sqlite:'.SQLITE_DIR.SQLITE_SESSION_FILE);
  $q = $db->exec("UPDATE local_session_validity SET validity=0 WHERE session_id='".$session_id."'"); // , last_real_check, nb_of_use , NOW(), 0
  if($q==1)
      return true;
  else
      return false;
}
?>
