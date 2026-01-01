<?php
/**
 * @version 1.0 $Id: PAAAuthenticationServiceFileCustomLogin.php 37590 2011-12-20 13:38:09Z y.audoux $
 *
 */
 
include_once 'PAAAuthenticationServiceFile.php';

class PAAAuthenticationServiceFileCustomLogin extends PAAAuthenticationServiceFile {
 

  /**
   * @return the login url
   */
  public function getLoginUrl() {
  
	return PAA_LOGIN_PAGE;
  }  
}


?>
