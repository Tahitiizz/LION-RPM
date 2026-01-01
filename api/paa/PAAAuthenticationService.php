<?php
/**
 * @version 1.0 $Id: PAAAuthenticationService.php 131069 2014-01-09 08:35:46Z n.stienne $
 *
 * 07/05/2013 NSE bz 33388: add of doesPortalServerApiManage() method
 */

include_once 'PAAUser.php';

abstract class PAAAuthenticationService {

    /**
     * define getAuthenticationService must instanciate a cas type
     * @var $TYPE_CAS
     */
    public static $TYPE_CAS = "CAS";

    /**
     * define getAuthenticationService must instanciate a file type
     * @var $TYPE_FILE
     */
    public static $TYPE_FILE = "File";

    /**
     * define getAuthenticationService must instanciate a file type
     * @var $TYPE_FILE_CUSTOM
     */
    public static $TYPE_FILE_CUSTOM = "FileCustomLogin";
    
    /**
     * define getAuthenticationService must instanciate a file type
     * @var $user
     */
    private $user;
    
    /**
     *    Use to instanciate $type
     *
     *    @param $config 
     *        define the path to the configuration file
     *    @return the instanciate class or null
     */
    //public static function getAuthenticationService(/*String*/ $type,/*String*/ $config) {
    public static function getAuthenticationService(/*String*/ $config = null) {
    
        if (!isset($config))
        {
          $config = $_SESSION['PAA_CONFIG_FILE'];
        }
        
        // Include config 
        if (!include_once($config)){
          throw new Exception ($config.'please check configuration file path');
        }
        
        // Mantis 4270 gestion de deux réseaux
        // Gestion de compatibilité avec ancien fichier de conf (au cas où)
        if(!defined('CAS_SERVER_PUBLIC'))
            define('CAS_SERVER_PUBLIC',CAS_SERVER);
        
        $type = PAA_SERVICE;
        $_SESSION['PAA_CONFIG_FILE'] = realpath($config);
        
        // include class an make a new
        if (include_once 'PAAAuthenticationService' . $type . '.php') {
          $className = 'PAAAuthenticationService' . $type;
          return new $className();
        } else {
          throw new Exception ('Driver not found');
        }
        
        return null;
    }
    
/**
     * Abstract Returns true if the module is managed by Portal Api, else false
     * @param string $module
     * @return boolean
     */
    abstract public function doesPortalServerApiManage($module);

    /**
     * Abstract use this function to check if the user is authenticate
     */
    abstract public function validateAuthentication();

    /**
     * Abstract use this function to get a User
     * @see PAAUser
     */
    abstract public function getUser();

    /**
     * Abstract Use this function to logout an user
     */
    abstract public function logout();
    
    /**
     * @return the login url
     */
    abstract public function getLoginUrl();
    
    /**
     * getUserRights method
     *
     * @param User $user User object
     * @param string $appId App ID
     */
    abstract public function getUserRights ($user,$appId);
    
    /**
     * getUsersWithRights method
     *
     * @param array $guidArray array of rights guid
     * @param string $appId App ID
     * @param boolean $flag flag: if true, gets super admin/users as well
     */
    abstract public function getUsersWithRights($guidArray, $appId, $flag);
    
    /**
     * getUsersWithNoRights method
     *
     * @param array $loginsArray array of logins to check
     * @param string $appId App ID
     */
    abstract public function getUsersWithNoRights($loginsArray, $appId);

    /**
     * getUserAttributes method
     *
     * @param string $login login for which to retrieve attributes
     */
    abstract public function getUserAttributes($login);
    
    /**
     * deleteRight method
     *
     * @param string $rightId GUID of right to delete 
     */
    abstract public function deleteRight($rightId);
    
    /**
     * getUserRights method
     *
     * @param User $user User object
     */
    abstract public function getUserRoles ($user);

    /**
     * addApplication method
     *
     * @param string $xml the xml describe API
     */
    abstract public function setApplication ($xml);
    
   /**
     * Is Support User ?
     *
     * @param string $username username
     * @return boolean Is Support User ?
     */
     abstract public function isSupportUser($username);
    
     /**
      * Is Valid User ?
      *
      * @param string $username username
      * @return boolean Is Valid User ?
      */
     abstract public function isValidUser($username);
    
    /**
     * This method checks to see if the request is secured via HTTPS
     * source: phpCAS library
     *
     * @return true if https, false otherwise
     */
    private function isHttps() {
        if ( isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Try to figure out the server URL with possible Proxys / Ports etc.
     * source: phpCAS library
     *
     * @return Server URL with domain:port
     */
    public function getServerUrl()
    {
    $server_url = '';
    if(!empty($_SERVER['HTTP_X_FORWARDED_HOST'])){
        $server_url = $_SERVER['HTTP_X_FORWARDED_HOST'];
    }else if(!empty($_SERVER['HTTP_X_FORWARDED_SERVER'])){
        $server_url = $_SERVER['HTTP_X_FORWARDED_SERVER'];
    }else{
        if (empty($_SERVER['SERVER_NAME'])) {
            $server_url = $_SERVER['HTTP_HOST'];
        } else {
            $server_url = $_SERVER['SERVER_NAME'];
        }
    }
    if (!strpos($server_url, ':')) {
        if ( ($this->isHttps() && $_SERVER['SERVER_PORT']!=443)
        || (!$this->isHttps() && $_SERVER['SERVER_PORT']!=80) ) {
            $server_url .= ':';
            $server_url .= $_SERVER['SERVER_PORT'];
        }
    }
    return $server_url;
}
}


?>
