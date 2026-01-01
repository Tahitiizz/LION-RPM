<?php
/**
 * @version 1.0 $Id: PAAAuthenticationServiceFileDegradedMode.php 112894 2013-05-21 16:14:44Z n.stienne $
 *
 * 07/05/2013 NSE bz 33388: add of doesPortalServerApiManage() method
 */
 
include_once 'PAAAuthenticationServiceFile.php';

class PAAAuthenticationServiceFileDegradedMode extends PAAAuthenticationServiceFile {
 

  /**
   * @return the login url
   */
  public function getLoginUrl() {
  
    $uri = 'http://'.$this->getServerUrl().$_SERVER["REQUEST_URI"];
    
    return PAA_API_URL . "/login/form_login.php?degraded&url=".urlencode($uri);
  }  
  
 /**
   * Return Database filename
   */
  protected function getDBFileName() 
  {
    return CAS_DEGRADEDMODE_SQLITE_DIR.CAS_DEGRADEDMODE_SQLITE_FILE;
  }
  
  /**
   * Return Database public key
   */
  protected function getDBPublicKey() 
  {
    return CAS_DEGRADEDMODE_SQLITE_DIR.CAS_DEGRADEDMODE_SQLITE_PUBLIC_KEY;
  }
  
  /**
   * Return Database private key
   */
  protected function getDBPrivateKey() 
  {
    return CAS_DEGRADEDMODE_SQLITE_DIR.CAS_DEGRADEDMODE_SQLITE_PRIVATE_KEY;
  }
  
   /**
     * Returns true if the module is managed by Portal Api, else false
     *
     * @param string $module
     * @return boolean
     */
  public function doesPortalServerApiManage($module){
    $modules = array();
    return in_array($module,$modules);
  }
   
}
?>
