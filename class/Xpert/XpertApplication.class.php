<?php

/**
 * 22/12/2010 DE Xpert MMT
 * Description of XpertApplication
 *
 * Represent one Xpert application associated with the current T&A
 * There is one xpert application for each symbolic link in /home/<t&A>/xpert folder
 * Associated with XpertManager.class
 *
 * @author m.monfort
 *
 *
 * 28/04/2011 MMT bz 21983 Lorsqu'il y a plusieurs Xpert viewer, si l'on importe plusieur fois le
 *  même fichier PHP avec des nom different on a une erreur: on appelle toujours le même fichier (celui de
 * la première application Xpert) -> Attention ne supporte plus des versions de Xpert viewer differentes installée
 *
 */



class XpertApplication {

	// Xpert Viewer statistic page URL, used for links to Xpert from dashboard
	const STATS_URL = '/index.php?action=statistic_rnc&tab=cause';
	// Xpert Viewer user profile menu link URL
	const USER_URL  = '/index.php';
	// Xpert Viewer admin profile menu link URL
	const ADMIN_URL  = '/index.php?action=configuration';
	// Xpert PHP API file path, used to communicate with Xpert on the existance of Network elements
	const XPERT_PHP_API_PATH = "/class/API/XpertAPI.php";
	
	private $label; // Menu and link label
	private $path; // relative path to the application link from the REP_PHYSIQUE_NIVEAU_0
	private $manager; // Parent XpertManager object

	//28/04/2011 MMT bz 21983 suppression du test de presence API file
	/**
	 * constructor
	 * @param XpertManager $XpertMger
	 * @param String $linkName File link name
	 */
	public function __construct($XpertMger,$linkName,$path){
		$this->manager = $XpertMger;
		$this->label = $linkName;
		$this->path = $path;

		//28/04/2011 MMT bz 21983 suppression du test de presence API file
	}


	/**
	 * get Xpert Viewer user profile menu link URL
	 * @return String
	 */
	public function getViewerUserMenuUrl(){
		return "/".$this->path.self::USER_URL;
	}

	/**
	 * get Xpert Viewer admin profile menu link URL
	 * @return String
	 */
	public function getViewerAdminMenuUrl(){
		return "/".$this->path.self::ADMIN_URL;
	}

	/**
	 * Xpert Viewer statistic page URL, used for links to Xpert from dashboard
	 * @return String
	 */
	public function getStatisticLinkUrl(){
		return NIVEAU_0.$this->path.self::STATS_URL;
	}

	/**
	 * get Xpert PHP API file path, used to communicate with Xpert on the existance of Network elements
	 * @return String
	 */
	public function getPhpApiFile(){
		return REP_PHYSIQUE_NIVEAU_0.$this->path.self::XPERT_PHP_API_PATH;
	}

	/**
	 * 28/04/2011 MMT bz 21983
	 * return the relative path to the application link from the REP_PHYSIQUE_NIVEAU_0
	 */
	public function getPath(){
		return $this->path;
	}

	/**
	 * get application label
	 * @return String
	 */
	public function getLabel(){
		return $this->label;
	}


	/**
	 * check if all required menus of the application exist in the DB by looking for links value
	 * @return boolean true if all presents
	 */
	public function hasExistingMenus()
	{
		$query = "select count(*) from menu_deroulant_intranet
					where lien_menu = '".$this->getViewerUserMenuUrl()."'
				   OR lien_menu = '".$this->getViewerAdminMenuUrl()."'";
		$nbMenus = $this->getDbConnection()->getOne($query);
		$ret = ($nbMenus == 2);
		$this->log($this->label." has correct menus?: '$ret'",2);
		return $ret;
	}

	/**
	 * get Manager DB connection
	 * @return DataBaseConnection
	 */
	private function getDbConnection(){
		return $this->manager->getDbConnection();
	}

   //28/04/2011 MMT bz 21983 suppression du test de presence API file

	/**
	 * Asks the Xpert Database if the given Network element exists
	 * Use the Xpert PHP API for that
	 *
	 * @param String $neId Id of the network element
	 * @param String $neParentId Id of the parent of the network element
	 * @return bool/string true or false and String if any error occured
	 */
	public function isCellOrSAIAvailable($neId,$neParentId){
		$this->log("IN isCellOrSAIAvailable neId: ".$neId." neParentId: ".$neParentId,3);
		$ret = "";
		//28/04/2011 MMT bz 21983 utilise un fichier API unique definit par le manager
		$apiFile = $this->manager->getSinglePhpApiFile();
		if($apiFile){
			// try to include an call the API
			try{
				// 28/04/2011 MMT bz 21983 get unique required file to include
				$this->log("Include API file : '". $apiFile . "' init param : '".REP_PHYSIQUE_NIVEAU_0.$this->getPath()."'",2);
				require_once($apiFile) ;

				// 28/04/2011 MMT bz 21983 new Xpert API constructor path parameter
				$api = new XpertAPI(REP_PHYSIQUE_NIVEAU_0.$this->getPath()) ;
				// ask for existence of ne
				if($api->isCellAvailable($neId, $neParentId)){
					$ret = true;
				} else {
					$ret = false;
				}
			}catch ( Exception $e ){
				$ret = $e->getMessage();
			}
		} else {
			$ret = "Could not find any Xpert API file for ".$this->getLabel();
		}
		$this->log("isCellOrSAIAvailable Xpert: ".$this->getLabel()." neId: ".$neId." neParentId: ".$neParentId." ==> ".$ret);
		return $ret;

	}

	
	/**
	 * Log message according to given level and current debug level
	 * @param String $string message to log
	 * @param int $level min debug level requiered to log
	 */
	private function log($string,$level=1){
		$this->manager->log($string,$level);
	}
}

?>
