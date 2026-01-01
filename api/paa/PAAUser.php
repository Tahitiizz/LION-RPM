<?php
/**
 * 
 * NSE 04/01/2013 DE PhoneNumber API : ajout de la variable phonenumber, de la méthode getPhoneNumber() et modification du constructeur pour intégrer ce paramètre
 * GFS 16/02/2013 Correction syntaxe __construct()
 *
 */
 
class PAAUser {

	/**
	 * user ID
	 * @var $id
	 */
	public $id;

	/**
	 * user fullname
	 * @var $fullname
	 */
	public $fullname;
        
	/**
	 * user phonenumber
	 * @var $phonenumber
	 */
	public $phonenumber;
	
	/**
	 * user login
	 * @var $login
	 */
	public $login;

	/**
	 * user mail
	 * @var $mail
	 */
	public $mail;
	
	function __construct(/*Integer*/ $id,/*String*/ $login, /*String*/ $fullname,/*String*/ $mail, /*String*/ $phonenumber='') {
	  $this->id = $id;
	  $this->login = $login;
	  $this->fullname = $fullname;
	  $this->mail = $mail;
          $this->phonenumber = $phonenumber;
	}
	
	/**
	 * Use to get user id
	 * @return id
	 */
	public function getId() {
	  return $this->id;
	}
	
	/**
     * Use to get login
     * @return login
     */
    public function getLogin() {
	  return $this->login;
	}
	
    /**
     * Use to get fullname
     * @return fullname
     */
    public function getFullname() {
	  return $this->fullname;
    }
    
    /**
     * Use to get user mail
     * @return mail
     */
    public function getMail() {
	  return $this->mail;
    }
	
    /**
     * Use to get phone number
     * @return phonenumber
     */
    public function getPhoneNumber() {
	  return $this->phonenumber;
    }
        
}

?>
