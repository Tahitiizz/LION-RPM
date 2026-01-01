<?php
/**
 * @version 1.0 $Id: PAAAuthenticationServiceFile.php 131652 2014-01-16 09:04:34Z j.ionoff $
 *
 * 07/05/2013 NSE bz 33388: add of doesPortalServerApiManage() method
 */
 
include_once 'PAAAuthenticationService.php';

class PAAAuthenticationServiceFile extends PAAAuthenticationService {

  /**
   * the user currently login
   * @var $user
   */
  private $user;
    
  /**
   * Return Database filename
   */
  protected function getDBFileName() 
  {
    return SQLITE_DIR.SQLITE_FILE;
  }
  
  /**
   * Return Database public key
   */
  protected function getDBPublicKey() 
  {
    return SQLITE_DIR.SQLITE_PUBLIC_KEY;
  }
  
  /**
   * Return Database private key
   */
  protected function getDBPrivateKey() 
  {
    return SQLITE_DIR.SQLITE_PRIVATE_KEY;
  }
  

  /**
   * use this function to check if the user is authenticate
   */
  public function validateAuthentication(){
	
	if (isset($_SESSION['login']) && isset($_SESSION['password']))
	{	
		// GET LOGIN / PASSWORD
		$login = $_SESSION['login'];
		$password = $_SESSION['password'];
		$result = null;
		   
		// vérification du login/pwd
		if ($db = $this->getSQLite()) 
		{
			//$enc = md5($password);
			$q = $db->query('SELECT * FROM users WHERE name = "'.$login.'" AND password = "'.$password.'";');
			$result = $q->fetch();
		}
		else
		{
		  return false;
		}
		
		// Récupération des informations
		if ($result!= NULL /*$q->ColumnCount() /*&& $q->columnType(0) != SQLITE3_NULL*/) 
		{
                  // NSE 04/01/2013 DE PhoneNumber API
		  $this->user = new PAAUser($result['id'],$result['name'],$result['fullname'],$result['email'],isset($result['phonenumber'])?$result['phonenumber']:'');
		  return true;
		} 
		else 
		{ 
		  return false;
		} 
	}
    
    return false;
  }
  /**
   * use this function to get a User
   * @see PAAUser
   */
  public function getUser(){
    return $this->user;
  }
  
  /**
   * @return the login url
   */
  public function getLoginUrl() {
  
    // YAX: gestion du port et https dans l'url
    // GFS 05/08/2013 - Bug 34772 - TA ne fonctionne pas en full https via le portail
    $scheme = $_SERVER["SERVER_PORT"] == 443 ? 'https' : 'http';
    $add = $scheme.'://'.$this->getServerUrl().urlencode($_SERVER["REQUEST_URI"]);

    return PAA_API_URL . "/login/form_login.php?url=".$add;
  }
  
  /**
   * use this function to logout an user
   */
  public function logout()
  {
  	unset($_SESSION['login']);
	unset($_SESSION['password']);	
	
	// suppression des paramètres
    $uri = substr($_SERVER["REQUEST_URI"], 0,strpos($_SERVER["REQUEST_URI"], '?'));
    
    // YAX: gestion du port et https dans l'url
	//header("Location: ". 'http://' . $_SERVER["SERVER_NAME"] . $uri);
    header("Location: ". 'http://' . $this->getServerUrl() . $uri);
	
    return true;
  }
  
  private function getSQLite() {
    $db = null;
    
    $filename = $this->getDBFileName();
    $data = file_get_contents($filename, FILE_USE_INCLUDE_PATH);
    
    $public_key = file_get_contents($this->getDBPublicKey(), FILE_USE_INCLUDE_PATH);
    $binary_signature = file_get_contents($filename.'.sign', FILE_USE_INCLUDE_PATH);
    
    $ok = openssl_verify($data, $binary_signature, $public_key, OPENSSL_ALGO_SHA1);

    if ($ok == 1) {
        $db = new PDO('sqlite:'.$filename);
    } elseif ($ok == -1) {
        echo "Bad (there's something wrong with sqlite:'$filename')\n";
    } else {
        echo "Error checking signature of '$filename' with '{$filename}.sign' and '{$this->getDBPublicKey()}'\n";
    }
    
    return $db;
  }
  
  /**
   * use this function to list users with at least one of the rights in array provided
   */
  public function getUsersWithRights($guidArray, $appId, $flag) {
  	$userArray = array();
    $db = $this->getSQLite();
    
    // 01/08/2011 VLC PAA V2 - Unicité des ID de profils dans le portail: on tronque le GUID application de ce qui est envoyé
    // car en stand-alone, on ne fait pas cet ajout
    //$guidArray = str_replace($appId.".", "", $guidArray);
    // 25/10/2012 BBX
    // BZ 30044 : on ne conserve le n° du profil
    $guidArray = preg_replace('/([a-zA-Z0-9]+\.)/', '', $guidArray);
    
    $q = $db->query("SELECT DISTINCT u.name
	FROM users u
	INNER JOIN astelliaapp_user_has_role ur ON u.id = ur.users_uid
	INNER JOIN astelliaapp_role ro ON ro.roleId = ur.astelliaapp_role_id
	INNER JOIN astelliaapp_role_has_right rr ON ro.roleId = rr.astelliaapp_role_roleId
	INNER JOIN astelliaapp_right ri ON ri.rightId = rr.astelliaapp_right_rightId
	WHERE ri.guid IN ('".implode("','",$guidArray)."')");
    // AND ri.astelliaapp_application_appId = (SELECT a.appId FROM astelliaapp_application a WHERE a.guid = '".$appId."')   
    
    
    while ($result = $q->fetch()){
      $userArray[] = $result['name'];
    }
    return $userArray;
  }
  
  /**
   * use this function to list users among array provided who have no rights to this application
   */
  public function getUsersWithNoRights($loginArray, $appId) {
  	$userArray = array();
    $db = $this->getSQLite();
    
    $q = $db->query("SELECT u.name
	FROM users u
	INNER JOIN astelliaapp_user_has_role ur ON u.id = ur.users_uid
	INNER JOIN astelliaapp_role ro ON ro.roleId = ur.astelliaapp_role_id
	INNER JOIN astelliaapp_role_has_right rr ON ro.roleId = rr.astelliaapp_role_roleId
	INNER JOIN astelliaapp_right ri ON ri.rightId = rr.astelliaapp_right_rightId
	WHERE u.name IN ('".implode("','",$loginArray)."')");
    // 	AND ri.astelliaapp_application_appId = (SELECT a.appId FROM astelliaapp_application a WHERE a.guid = '".$appId."') 
    
    while ($result = $q->fetch()){
      $usersWithRights[] = $result['name'];
    }
    
	$usersWithNoRights = array_diff($loginArray, $usersWithRights);
    return $usersWithNoRights;
  }

  /**
   * use this function to get a user's attributes
   */
  public function getUserAttributes($login) {
  	$userAttributes = array();
  	$db = $this->getSQLite();
  	$q = $db->query("SELECT * FROM users WHERE name='".$login."'");
    $result = $q->fetch();
    $userAttributes['fullname'] = $result['fullname'];
    $userAttributes['mail'] = $result['email'];
  	
  	return $userAttributes;
  }
    
  /**
   * use this function to delete a right
   */
  public function deleteRight($rightId) {
  	//VLC 22/07/2011 PAA lot 2 - en mode standalone, pas d'update de la base locale
    //   cependant, le code au-delà du "return true" est fonctionnel, si on souhaite ajouter la fonctionnalité
    //   il suffit de retirer ce return et d'ajouter la gestion de l'update des clés
    
    return true;
  	
  	$db = $this->getSQLite();
  	
  	$q = $db->query("SELECT rightId FROM astelliaapp_right WHERE guid='".$rightId."'");
    while ($result = $q->fetch()){
      $rightIds[] = $result['rightId'];
    }
  	
    foreach ($rightIds as $rid) {
        $q = $db->query("DELETE FROM astelliaapp_role_has_right WHERE astelliaapp_right_rightid='".$rid."'");
  	  	$q = $db->query("DELETE FROM astelliaapp_right WHERE rightId = '".$rid."';");
    }
  	
  	return $q;
  }
  
  /**
   * getUserRights method
   *
   * @param User $user User object
   * @param string $appId App ID
   */
  public function getUserRights ($user,$appId){
    $guidArray = array();
    $db = $this->getSQLite();
    $today = date("Y-m-d");
    $q = $db->query("SELECT DISTINCT r.guid FROM astelliaapp_right r, astelliaapp_role_has_right rr, astelliaapp_role ro, astelliaapp_user_has_role ur, users u, astelliaapp_application a 
    				WHERE rr.astelliaapp_right_rightId = r.rightId 
                    AND rr.astelliaapp_role_roleId = ur.astelliaapp_role_id
                    AND u.id = ur.users_uid
    				AND r.astelliaapp_application_appId = a.appId
    				AND ur.expirationdate > $today 
    				AND u.name = '$user';");

    while ($result = $q->fetch()){
      $guidArray[] = $result['guid'];
    }
	
    return $guidArray;
  }
  
  /**
   * getUserRights method
   *
   * @param User $user User object
   */
  public function getUserRoles ($user){
    $db = $this->getSQLite();
    
    $q = $db->query(" SELECT DISTINCT ro.guid FROM astelliaapp_role ro, astelliaapp_user_has_role ur, users u
                    WHERE ro.roleId = ur.astelliaapp_role_id
                    AND u.id = ur.users_uid
    				AND ur.expirationdate > date()
    				AND u.name = '$user';");
    $guidArray = array();
    while ($result = $q->fetch()){
      $guidArray[] = $result['guid'];
    }
    
    return $guidArray;
  }

  private function libxml_display_error($error)
  {
      $return = "<br/>\n";
      switch ($error->level) {
          case LIBXML_ERR_WARNING:
              $return .= "<b>Warning $error->code</b>: ";
              break;
          case LIBXML_ERR_ERROR:
              $return .= "<b>Error $error->code</b>: ";
              break;
          case LIBXML_ERR_FATAL:
              $return .= "<b>Fatal Error $error->code</b>: ";
              break;
      }
      $return .= trim($error->message);
      if ($error->file) {
          $return .=    " in <b>$error->file</b>";
      }
      $return .= " on line <b>$error->line</b>\n";
  
      return $return;
  }
  
  private function libxml_display_errors() {
      $errors = libxml_get_errors();
      $errorsStr = '';
      foreach ($errors as $error) {
          $errorsStr .= $this->libxml_display_error($error);
      }
      libxml_clear_errors();
      return $errorsStr;
  }
  
  /**
   * addApplication method
   *
   * @param string $xml the xml describe API
   */
  public function setApplication ($xmlSrc){
    $appId = '';
    //VLC 22/07/2011 PAA lot 2 - en mode standalone, pas d'update de la base locale
    //   cependant, le code au-delà du return 0 est fonctionnel, si on souhaite ajouter la fonctionnalité
    //   il suffit de retirer ce return et d'ajouter la gestion de l'update des clés
    
    return "0";
    
    // TODO: Vérifié les regles de gestion en cas d'erreur
    /*if (!file_exists($this->getDBPrivateKey())) {
      return false;
    }*/
    
    $db = $this->getSQLite();
    $dir = dirname(__FILE__);
    
    // Get XML and validate
    $xml = new DOMDocument();
    $xml->preserveWhiteSpace=false; 
    $xml->formatOutput = true; 
    $xml->loadXML($xmlSrc);
    libxml_use_internal_errors(true);
    
    /*try {
      $valide = $xml->schemaValidate('./conf/paa_import_application.xsd');
    }catch (Exception $e){
      return $e->getMessage();
    }*/
    //if ($valide){
      // get All application definition
      $xmlApplication = $xml->getElementsByTagName('application')->item(0);
      $guid = $xmlApplication->getElementsByTagName('guid')->item(0)->nodeValue;
      $name = $xmlApplication->getElementsByTagName('name')->item(0)->nodeValue;
      $path = $xmlApplication->getElementsByTagName('path')->item(0)->nodeValue;
      $description = $xmlApplication->getElementsByTagName('description')->item(0)->nodeValue;
      $category = $xmlApplication->getElementsByTagName('category')->item(0)->nodeValue;
      $type = $xmlApplication->getElementsByTagName('type')->item(0)->nodeValue;
      
      // set Value to Object
      $q = $db->query("SELECT * FROM 'astelliaapp_application' WHERE guid = '$guid';");
      $result = $q->fetch();
      
      /*if ($result) {
        $q = $db->query("UPDATE 'astelliaapp_application' 
        					SET 'name' = '$name','path' = '$path','description' = '$description',
        					'type' = '$type[0]','category' = '$category[0]' 
        					WHERE 'guid' = '$guid';");
        
        $q = $db->query("DELETE FROM 'astelliaapp_ip' WHERE astelliaapp_application_appId = '".$result['appId']."';");
        $appId = $result['appId'];
      }else{
        $q = $db->query("INSERT INTO 'astelliaapp_application' ('guid','name','path','description','type','category') 
        					VALUES ('$guid','$name','$path','$description','$type[0]','$category[0]');");
        $appId = $db->lastInsertId();     
      } */
      
      //Create IP list and add to application
      /*$ips = $xmlApplication->getElementsByTagName('ips')->item(0);
      $allIp = $ips->getElementsByTagName('IP');
      
      foreach($allIp as $ipObj){
        if ($ipObj->getElementsByTagName('port')->item(0)){
          $newIP = $ipObj->getElementsByTagName('IP')->item(0)->nodeValue;
          $newPort = $ipObj->getElementsByTagName('port')->item(0)->nodeValue;
          
          $q = $db->query("INSERT INTO 'astelliaapp_ip' ('IP','port','astelliaapp_application_appId') 
      					VALUES ('$newIP','$newPort','$appId');");
        }
      }*/
      
      // Create Rights list and add to application
      $rights = $xmlApplication->getElementsByTagName('rights')->item(0);
      $allRights = $rights->getElementsByTagName('Right');
      
      foreach($allRights as $rightObj){
        $guidRight = $rightObj->getElementsByTagName('guid')->item(0)->nodeValue;
        $guidName = $rightObj->getElementsByTagName('name')->item(0)->nodeValue;
        $guidType = $rightObj->getElementsByTagName('type')->item(0)->nodeValue;
        
        $q = $db->query("SELECT * FROM 'astelliaapp_right' WHERE guid = '$guidRight';");
        $result = $q->fetch();
        
        if ($result){
         $q = $db->query("UPDATE 'astelliaapp_right' SET 'name'='$guidName','type'='$guidType','astelliaapp_application_appId'='$appId'
         					WHERE guid = '$guidRight';");
        }else{
          $q = $db->query("INSERT INTO 'astelliaapp_right' ('guid','name','type','astelliaapp_application_appId') 
        					VALUES ('$guidRight','$guidName','$guidType','$appId');");
        }
      }
                  
      // Create Role inside XML
      $roles = $xmlApplication->getElementsByTagName('roles')->item(0);
      $allRoles = $roles->getElementsByTagName('Role');
      
      $newRoleArray = array();
      
      foreach($allRoles as $roleObj){
        $guidRole = $roleObj->getElementsByTagName('guid')->item(0)->nodeValue;
        $nameRole = $roleObj->getElementsByTagName('name')->item(0)->nodeValue;
        $typeRole = $roleObj->getElementsByTagName('type')->item(0)->nodeValue;
        $descriptionRole = $roleObj->getElementsByTagName('description')->item(0)->nodeValue;
        
        $rights = $roleObj->getElementsByTagName('rights_guid')->item(0);
        $allRights = $rights->getElementsByTagName('rights');
        
        $q = $db->query("SELECT * FROM 'astelliaapp_role' WHERE guid = '$guidRole';");
        $result = $q->fetch();
        $roleId = null;
        
        if ($result){
          $q = $db->query("UPDATE 'astelliaapp_role' SET 'name' = '$nameRole','type' = '$typeRole','description' = '$descriptionRole'
        					WHERE guid = '$guidRole';");
          $q = $db->query("DELETE FROM 'astelliaapp_role_has_right' WHERE astelliaapp_role_roleId = '".$result['roleId']."';");
          $roleId = $result['roleId'];
        }else{
          $q = $db->query("INSERT INTO 'astelliaapp_role' ('guid','name','type','description') 
        					VALUES ('$guidRole','$nameRole','$typeRole','$descriptionRole');");
          $roleId = $db->lastInsertId();  
        }
                
        // Search all Right with guid
        foreach($allRights as $rightObjRole){
          $guid = $rightObjRole->nodeValue;
          
          $q = $db->query("SELECT * FROM astelliaapp_right WHERE guid='$guid';");
          $result = $q->fetch();
          
          $q = $db->query("INSERT INTO 'astelliaapp_role_has_right' ('astelliaapp_role_roleId','astelliaapp_right_rightId') 
      					VALUES ('$roleId','".$result['rightId']."');");
        }      
      }
    /*}else{    
      $strError = $this->libxml_display_errors();
      return "XML application cannot be validate with schema paa_import_application. Please check your XML.".$strError;
    }*/
    
    /*if (file_exists($this->getDBPrivateKey())) {
      $filename = getDBFileName();
      $data = file_get_contents($filename, FILE_USE_INCLUDE_PATH);
      $private_key = file_get_contents($this->getDBPrivateKey(), FILE_USE_INCLUDE_PATH);
      $binary_signature = "";
      openssl_sign($data, $binary_signature, $private_key, OPENSSL_ALGO_SHA1);
      file_put_contents($filename.'.sign',$binary_signature);
    }*/
    
    return $appId;
  }
  
 /**
   * Is Support User ?
   *
   * @param string $username username
   * @return boolean Is Support User ?
   */
  public function isSupportUser($username) {
  // 30/08/2013 MGO bz 34678 : test sur astellia_admin au lieu de astellia_support
    return ($username==="astellia_admin" || $username==="astellia_support");
  }

  public function isValidUser($username) {
  	// TODO
  	return true;
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
