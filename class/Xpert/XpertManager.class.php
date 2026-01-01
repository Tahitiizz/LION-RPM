<?php
/**
 * 22/12/2010
 * Please refer to DE Xpert spec: \\ast_sf\trending_aggregation$\CB5.0\Classeur\02.DonneesEntree\Demandes d'évolutions\Liens Xpert T&A et gestion Master-slave\TCB50_InterfaceTA_XPert_RD.doc
 * Description of XpertManager
 * Class that manage instances of XpertApplication, is responsible for maintaining
 * the Xpert related menus and profiles up-to-date
 *
 * @author MMT
 *
 *
 * 28/04/2011 MMT bz 21983 Lorsqu'il y a plusieurs Xpert viewer, si l'on importe plusieur fois le
 *  même fichier PHP avec des nom different on a une erreur: on appelle toujours le même fichier (celui de
 * la première application Xpert) -> Attention ne supporte plus des versions de Xpert viewer differentes installée
 *
 */
include_once(REP_PHYSIQUE_NIVEAU_0."class/Xpert/XpertApplication.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0."php/edw_function.php");
include_once(REP_PHYSIQUE_NIVEAU_0."class/Date.class.php");

class XpertManager
{
	const FOLDER_NAME  = 'xpert'; // name of the folder containing all the Xpert links
	// it is also the name of the link used for a single expert application on version 1.0

	// admin menu constants
	const MENU_ADMIN_LABEL  = 'Setup Xpert';
	const MENU_ADMIN_PARENT_LABEL  = 'SETUP';
	const MENU_ADMIN_POSITION  = 10;

	// user menu constants
	const MENU_USER_LABEL  = 'Xpert';
	const MENU_USER_PARENT_LABEL  = 'INVESTIGATION';
	const MENU_USER_POSITION  = 5;

	// PHP HTTP Session key of the XpertManager serialized instance
	const SESSION_KEY  = 'Xpert_Manager_Object';

	// list of allowed parent types for a Network Element linked to Xpert
	private $SUPPORTED_XPERT_NE_PARENT_TYPES = Array("rnc","bsc");
	// list of allowed types for a Network Element linked to Xpert
	private $SUPPORTED_XPERT_NE_TYPES = Array("cell","sai");

	// list of current Xpert application objects
	private $xpertApps = Array();
	// db connection
	private $db;
	// list of product ids that support Xpert GTM link
	private $supportedGTMLinkProducts = Array();

	// debug level
   private $debug;

	// true if the Xpert application is < 1.2 (only one xpert application possible per T&A)
	private $isXpertV1 = false;


	//28/04/2011 MMT bz 21983 cache $singleApiFile for perf
	// always use the getter to access the value!
	private $singleApiFile = "UNSET";

	/**
	 * Constructor
	 * create all instances of Xpert application at construction level
	 * get all members value and store itself in session
	 * at the moment no other members should be modified after construction or
	 * changes would not be reflected in the session
	 *
	 */
	public function __construct()
	{
		// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
		$this->db = Database::getConnection();
		// get debug level from DB
		$this->debug = get_sys_debug('xpert_management');
		$this->db->setDebug(($this->debug > 1));

		if ( $this->debug ){
			 $this->log(" <b> >>>>>>>>>> DEBUG MODE : ".$this->debug."  <<<<<<<<<< </b>");
		}

		// create Xpert applications
		$this->createApplicationsFromLinks();

		// store list of GTM Links supported products
		if($this->hasXpertApplications() && $this->doesVersionSupportGTMLinks()){
			$this->supportedGTMLinkProducts = $this->querySupportedGTMLinkProducts();
		}
		// store the object in the session
		if(!empty ($_SESSION)){
			$_SESSION[self::SESSION_KEY] = serialize($this);
			$this->log("New Xpert Manager Instance Created and stored in Session!",2);
		} else {
			$this->log("Not session found, could not store Xpert Manager Instance");
		}

	}

	/**
	 * Get an instance of the XpertManager object from session if exists or create a new one if not
	 * Warinig : getting a XpertManager from the session will not check the Menu and profile to be up to date
	 * @return XpertManager
	 */
	public static function getInstance(){
		if(array_key_exists(self::SESSION_KEY, $_SESSION)){
			$ret = unserialize($_SESSION[self::SESSION_KEY]);
		} else {
			$ret = new XpertManager();
		}
		return $ret;
	}

	/**
	 * Create the $xpertApps array objects from the links in /home/<T&A>/xpert
	 * one application per link using the link name as label
	 */
	private function createApplicationsFromLinks(){

		$xpertLinksFolder = REP_PHYSIQUE_NIVEAU_0.self::FOLDER_NAME;
		if ( file_exists($xpertLinksFolder) ){
			
			// backward compatibility with Xpert 1.0
			if(is_link($xpertLinksFolder)){
				$this->log("Detected Xpert 1.0 link '$xpertLinksFolder'");
				// set the lone xpert link to the link path and the link 'xpert' name
				$this->isXpertV1 = true;
				$this->xpertApps[] = new XpertApplication($this,"Single Xpert 1.0",self::FOLDER_NAME);
			} else {
				// 1 - list all links
				$handle = opendir($xpertLinksFolder);
				$links = Array();
				while (false !== ($fileName = readdir($handle))) {
					 // test if the file is a link
					 if(is_link($xpertLinksFolder."/".$fileName)){
						 $links[] = $fileName;
					 }
				}
				closedir($handle);
				// 2 - sort the links in alphabetical order
				sort($links);
				// 3 - create apps
				foreach ($links as $linkName){
					$this->xpertApps[] = new XpertApplication($this,$linkName,self::FOLDER_NAME."/".$linkName);
					$this->log("Detected Xpert application '".$linkName."'");
				}
			}
			
		} else {
			$this->log("No ".$xpertLinksFolder." link or folder");
		}
		$this->log("Found ".count($this->xpertApps)." Xpert application(s)");

	}

	/**
	 * check if the current T&A menus matches the Xpert applications found from the files
	 * if not updates them (delete and recreate all)
	 * Also update profiles if needed
	 */
	public function checkAndUpdateMenus(){
		if($this->doMenusNeedUpdating()){
			$this->dropAllMenus();
			$this->createAllMenus();
		} 
	}

	/**
	 * Get the body (FROM + WHERE clause) of the SQL query that return all Xpert menus from menu_deroulant_intranet
	 * @return String
	 */
	private static function getAllXpertMenusQueryBody(){

		return "
					 FROM menu_deroulant_intranet t1, menu_deroulant_intranet t2
					 WHERE (t1.libelle_menu = '".self::MENU_ADMIN_LABEL."' OR
							t1.libelle_menu = '".self::MENU_USER_LABEL."')
					 AND (
						t1.id_menu = t2.id_menu
						OR
						t1.id_menu = t2.id_menu_parent
					 )";
	}


	/**
	 * Check if the menus are up to date
	 * 1 - check if the number of menu matches the number of apps
	 * 2 - if yes, test each apps for presence of menus
	 * @return boolean true if current menus do not match de current apps
	 */
	private function doMenusNeedUpdating(){

		$this->log("Check Xpert current menus");
		$query = "
					SELECT count(t2.libelle_menu) ".self::getAllXpertMenusQueryBody();
		$nbMenus = $this->getDbConnection()->getOne($query);

		$nbXpert = $this->getNbXpert();
		// one user and one admin for each apps
		// if only one apps, just the parent top menu
		$nbMenuTheoric = 2*$nbXpert;
		if($nbXpert  > 1 ){
			$nbMenuTheoric = 2*($nbXpert + 1);
		}
		$this->log("Nb xpert apps: ". $nbXpert.". found ".$nbMenus." menus in base, expected: ".$nbMenuTheoric);
		// compare the number of thoeric menus with the current one
		$ret = ($nbMenuTheoric != $nbMenus);
		if(!$ret){
			$this->log("Check validity of each existing menus");
			// test that each application can find its menus
			foreach ($this->xpertApps as $xpert){
				if(!$xpert->hasExistingMenus()){
					$this->log("Xpert ".$xpert->getLabel()." cannot find its menus");
					$ret = true;
					break;
				}
			}
		} else {
			$this->log("No Xpert menu update needed");
		}
		return $ret;
	}

	/**
	 * remove all Xpert menus from menu_deroulant_intranet
	 */
	private function dropAllMenus(){
		$this->log("Deleting all Xpert Menus and menu profiles");
		
		$menuListQuery = "
				    SELECT t2.id_menu as idmenu ".self::getAllXpertMenusQueryBody();

		$menuIds = $this->getDbConnection()->getColumnValues($menuListQuery,"idmenu");
		$menuIdsString = "'".implode("', '", $menuIds)."'";

		$deletMenuQuery = "DELETE FROM menu_deroulant_intranet WHERE id_menu IN ($menuIdsString)";
		$this->getDbConnection()->execute($deletMenuQuery);
		$this->log("Deleted ".$this->getDbConnection()->getAffectedRows()." menus");

		$deletMenuProfileQuery = "DELETE FROM profile_menu_position WHERE id_menu IN ($menuIdsString)";
		$this->getDbConnection()->execute($deletMenuProfileQuery);
		$this->log("Deleted ".$this->getDbConnection()->getAffectedRows()." menu profiles");

	}

	/**
	 * generate all menus for all current Xpert applications
	 */
	private function createAllMenus(){

		if($this->hasXpertApplications()){
			$userTopMenuUrl = '';
			$adminTopMenuUrl = '';
			// if only one Xpert we create only the top "Xpert" menu
			// only set Top menus URL links if one Xpert
			if($this->getNbXpert() == 1){
				$userTopMenuUrl = $this->xpertApps[0]->getViewerUserMenuUrl();
				$adminTopMenuUrl = $this->xpertApps[0]->getViewerAdminMenuUrl();
			}
			// create top menus for admin and user
			$this->createMenu(self::MENU_ADMIN_LABEL,$adminTopMenuUrl,self::MENU_ADMIN_PARENT_LABEL,2,self::MENU_ADMIN_POSITION);
			$this->createMenu(self::MENU_USER_LABEL,$userTopMenuUrl,self::MENU_USER_PARENT_LABEL,2,self::MENU_USER_POSITION,false);

			//create child menus if nb xpert > 1
			if ($this->getNbXpert() > 1){
				$posi = 0;
				foreach ($this->xpertApps as $xpert){
					$posi += 1;
					$this->createMenu($xpert->getLabel(),$xpert->getViewerAdminMenuUrl(),self::MENU_ADMIN_LABEL,3,$posi);
					$this->createMenu($xpert->getLabel(),$xpert->getViewerUserMenuUrl(),self::MENU_USER_LABEL,3,$posi,false);
				}
			}
		}
	}


	/**
	 * Create a Menu in menu_deroulant_intranet
	 * @param String $label
	 * @param String $url
	 * @param String $parentLabel
	 * @param int $level menu level hierarchy
	 * @param int $position position in the menu list for that level and parent
	 * @param boolean $isAdminProfile true if menu if for admin, false for user
	 */
	public function createMenu($label, $url, $parentLabel ,$level,$position,$isAdminProfile=true){

		$this->log("Creating menu ".$label." parent:".$parentLabel." url:".$url);

		$menu = array();
		$menu["libelle_menu"] = $label;	// Label du menu
		$menu["droit_affichage"] = "astellia";	// laisser astellia
		$menu["id_menu_parent"] = "(SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='".$parentLabel."')"; // identifiant du menu parent (normalement, ne pas modifier)
		$menu["lien_menu"] = $url; // mettre ici le chemin vers le fichier Xpert (vous pouvez mettre des param dans l'URL)
		if($isAdminProfile){
			$menu["is_profile_ref_admin"]	= 1;
		} else {
			$menu["is_profile_ref_user"]	= 1;
		}
		$menu["niveau"] = $level;
		$menu["position"]	= $position;
		$menu["droit_visible"]	= 0;
		$menu["menu_client_default"]	= 0;

		addMenu($menu, $this->getDbConnection()->getCnx());
	}

	/**
	 * associate each Xperts menus to each profile if needed
	 * Correction du BZ 11733 Réapplication de contexte fait disparaitre le menu Xpert
	 */
	public function updateMenuProfiles()
	{
		if($this->hasXpertApplications()){
			$this->log("Check profiles for Xpert menus");
			$query = "
						SELECT t2.id_menu, t2.is_profile_ref_admin, t2.is_profile_ref_user , t2.libelle_menu  ".self::getAllXpertMenusQueryBody();
			//list  menus
			$menuRes = $this->getDbConnection()->getall($query);

			$profileQuery = "select * from Profile";
			//list profiles
			$profileRes = $this->getDbConnection()->getall($profileQuery);

			$total = 0;
			foreach ( $profileRes as $profil )
			{
				$ProfileModel = new ProfileModel($profil['id_profile']);
				$this->log("Checking profile ".$profil['profile_name'],3);
				$count = 0;
				foreach ($menuRes as $menuRow) {
					// if the menu is missing in the profile and the profile type matches (user or admin)
					if ((!$ProfileModel->isMenuInProfile($menuRow['id_menu']))
						  &&(($profil['profile_type'] == "admin" && $menuRow['is_profile_ref_admin'] == 1)
							  || ($profil['profile_type'] == "user" && $menuRow['is_profile_ref_user'] == 1)))
						{
							// then add the profile to the menu
							$this->log("adding Menu ".$menuRow['libelle_menu']." to profile ".$profil['profile_name'],3);
							$ProfileModel->addMenuToProfile($menuRow['id_menu']);
							$ProfileModel->buildProfileToMenu();

							if($menuRow['is_profile_ref_admin']){
								$offset = self::MENU_ADMIN_POSITION;
							} else {
								$offset = self::MENU_USER_POSITION;
							}
							$count += 1;
						}
				}

				$this->log("added $count menu(s) for profile '".$profil['profile_name']."'",2);
				$total += $count;
			}
			$this->log("added $total profile menus ");
		}

	}

	/**
	 * get the list of XpertApplication objects
	 * @return array<XpertApplication>
	 */
	public function getXpertApplications(){
		return $this->xpertApps;
	}

	/**
	 * get the number of XpertApplication objects
	 * @return int
	 */
	public function getNbXpert(){
		return count($this->xpertApps);
	}

	public function hasXpertApplications(){
		return (count($this->xpertApps) > 0);
	}

	/**
	 * true if the current Xpert install can support links from dashboards (Version Xpert > 1.0)
	 * @return boolean
	 */
	public function doesVersionSupportGTMLinks(){
		return !$this->isXpertV1;
	}

	/**
	 * get supported Network Element Types for links to Xpert
	 * @return array<String>
	 */
	public function getSupportedNetworkElementTypes(){
		return $this->SUPPORTED_XPERT_NE_TYPES;
	}

	/**
	 * get supported Network parent Element Types for links to Xpert
	 * @return array<String>
	 */
	public function getSupportedNetworkElementParentTypes(){
		return $this->SUPPORTED_XPERT_NE_PARENT_TYPES;
	}

	/**
	 * get the list of Supported topology arcs, based on the list of allowed Network element types
	 * (today cell/sai ) and parent types (today bsc/rnc)
	 * @return array list of arc as strings
	 */
	public function getSupportedArcList(){

		$arcSep = get_sys_global_parameters('sep_axe3');
		$ret = array();
		foreach ($this->SUPPORTED_XPERT_NE_TYPES as $neType){
			foreach ($this->SUPPORTED_XPERT_NE_PARENT_TYPES as $neParentType){
				$ret[] = "'".$neType.$arcSep.$neParentType."'";
			}
		}
		return $ret;
	}

	/**
	 * query the list of supported Products from the DB, supported product contain at least
	 * one supported arc
	 *
	 * @return array of product Id
	 */
	private function querySupportedGTMLinkProducts(){
		$ret = array();
		$this->log("Check which product support Xpert Links");
		$query = "SELECT sdp_id FROM sys_definition_product";
		$prdRes = $this->getDbConnection()->getall($query);

		$arcList = implode(",", $this->getSupportedArcList());

		$query = "
					SELECT COUNT(*) FROM edw_object_arc_ref
					WHERE eoar_arc_type IN (".$arcList.")";
		$this->log("Query to run on each product: $query",2);

		foreach ( $prdRes as $row ){
			$prd = $row["sdp_id"];
			$dbPrd = Database::getConnection($prd);
			$query = "
						SELECT COUNT(*) FROM edw_object_arc_ref
						WHERE eoar_arc_type IN (".$arcList.")";
			$nbAcrs = $dbPrd->getOne($query);
			if($nbAcrs > 0){
				$ret[] = $prd;
			}
		}
		$this->log("found ".count($ret). " supported product(s) for GTM Links. id(s): ".implode(", ", $ret));
		return $ret;
	}

	public function getSupportedGTMLinkProducts(){
		return $this->supportedGTMLinkProducts;
	}


	/**
	 * 28/04/2011 MMT bz 21983 get a single PHP API File path
	 * It will always be the first Xpert available application file
	 * @return string the php file to include, regardless of the Xpert application, false if none could be found
	 */
	public function getSinglePhpApiFile(){
		if($this->singleApiFile == "UNSET"){
			$ret = false;

			if($this->hasXpertApplications()){
				foreach ($this->xpertApps as $xpert){
					$file = $xpert->getPhpApiFile();
					if(file_exists($file)){
						$ret = $file;
						break;
					}
				}
			}
			$this->singleApiFile = $ret;
			$this->log("Choosen API file '".$this->singleApiFile."'");
		} else {
			$ret = $this->singleApiFile;
		}
		return $ret;
	}

	/**
	 * get current database connection to share with other Xpert classes
	 * @return DataBaseConnection
	 */
	public function getDbConnection(){
		return $this->db;
	}

	/**
	 * get current debug level 0 = off, 1 = milestones 2 = querys
	 * @return int
	 */
	public function getDebugLevel(){
		return $this->debug;
	}

	/**
	 * true if the debug is not off
	 * @return boolean
	 */
	public function isDebugOn(){
		return ($this->debug > 0);
	}

	/**
	 * Log message according to given level and current debug level
	 * @param String/array $message to log if an array is provided the print_r method will be used
	 * @param int $level min debug level requiered to log
	 */
	public function log($string,$level=1){
		if($this->debug >= $level){
			$s = "<pre><b>Xpert($level):</b>"; 
			//echo gettype($string);
			if(gettype($string) == "array"){
				print_r ( $string );
			} else {
				$s .= $string;
			}
			$s .= "</pre>\n";
			echo $s;
			//exec("echo '$s' >> /tmp/xpertlog.log");
		}


	}


}
?>
