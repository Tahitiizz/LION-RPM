<?php
/**
 * 14/01/2011 DE Xpert MMT
 * Class that holds method for Links to Xpert from Dashboard graphs
 * This class aim to create a URL for each data of the GTM (like done for AA)
 * That URL will point to XpertLauncher which will do all the heavy treatment then for performances
 * point of view (this call methods are called for every data whereas the XpertLauncher is called once on Link click)
 * the XpertDashboardManager is linked to the XpertManager class
 * One instance of this class is to be created for each displayed dashboard
 *
 * @author m.monfort
 */
class XpertDashboardManager {

	//XpertManager obj, used for Xpert application access, database, log and session caching purposes
	private $manager;

	//array containing the dashboard selector values
	// from SelecteurDashboard class method getValues
	private $selecteur_values;
	private $viewMode; // overtime or overnetwork
	private $linkAllowed = false; // does Xpert Links are alowed in this dashboard?


	/**
	 * Constructor
	 * @param XpertManager $XpertMger XpertManager obj
	 * @param array $selecteurValues array containing the dashboard selector values from SelecteurDashboard class method getValues
	 * @param String $viewMode overtime or overnetwork
	 */
	public function __construct($XpertMger,$selecteurValues,$viewMode)
	{
		$this->manager = $XpertMger;
		$this->selecteur_values = $selecteurValues;
		$this->viewMode = $viewMode;
		// store the value for performances as isGTMLinkAllowed is called very often
		$this->linkAllowed = $this->queryIfGTMLinkAllowed();
	}


	/**
	 * check if GTM links to xpert are allowed in this dashboard
	 * for allowance, The following condition must be met:
	 *  - have at least one detected Xpert Application
	 *  - Those Xpert application must support Link (Version >= 1.2)
	 *  - the selector NA level must be supported (cell or SAI)
	 * @return boolean
	 */
	private function queryIfGTMLinkAllowed(){

		$na_level = $this->selecteur_values['na_level'];
		$this->log("isGTMLinkAllowed na_level:".$na_level,2);

		$hasXpertApps = $this->manager->hasXpertApplications();
		$doesVersionSupportGTMLinks = $this->manager->doesVersionSupportGTMLinks();
		$isNaTypeSupported = in_array($na_level, $this->manager->getSupportedNetworkElementTypes());

		$ret = ($hasXpertApps && $doesVersionSupportGTMLinks && $isNaTypeSupported);

		// get detailed log for the reason why links aren't displayed
		if($ret){
			$this->log("GTM Links to Xpert allowed on this dashboard");
		} else {
			if(!$hasXpertApps){
				$reasonLabel = "no xpert application detected";
			} else if(!$doesVersionSupportGTMLinks){
				$reasonLabel = "xpert version does not support this functionality";
			} else {
				$reasonLabel = "Network aggregation '$na_level' is not supported by Xpert";
			}
			$this->log("GTM Links to Xpert NOT allowed :".$reasonLabel);
		}

		return $ret;
	}

   /**
	 * True if the Dashboard with current selector setings allows Xpert links
	 * use a cached value initialized at construction by queryIfGTMLinkAllowed
	 * @return boolean
	 */
	public function isGTMLinkAllowed(){
		return $this->linkAllowed;
	}

	/**
	 * Return the GTM Link URL to be run in order to call the launchXpert.php with propoer parameter
	 * It is  launchXpert.php that will ultimately redirect the user to the Xpert view
	 * @param String $neId Network Element id of the selected Graph data
	 * @param int $productId Product Id of the Network Element
	 * @return string URL
	 */
	private function getXpertLauncherUrl($neId,$productId){

		$ta = $this->selecteur_values['ta_level'];
		if ($ta == "hour"){
			$ta_value = $this->selecteur_values['date']." ".$this->selecteur_values['hour'];
		} else {
			$ta_value = $this->selecteur_values['date'];
		}
		//change the selector date format to TA  YYYYMM ou YYYYWW ou YYYYMMDD...
		$formatedTaValue = getTaValueToDisplayReverse($ta, $ta_value, "/");
		if($this->viewMode == "overtime"){
			$period = $this->selecteur_values['period'];
		} else {
			$period = 1;
		}

		$this->log("getXpertLaunchUrl na_level:".$na_level." ta:".$ta." ta_value:".$ta_value." period:".$period,3);
		
		$ret = "php/launchXpert.php?ta=".$ta."&ta_value=".$formatedTaValue."&period=".$period."&ne=".$neId."&product=".$productId;
		
		return $ret;

	}

	/**
	 * Get the GTM Xpert URL to be called for a specific data.
	 * Xpert Launcher needs two critical peice of info that is hold in the right-clicked data:
	 *  - the Network element (multiple NE in on GTM in overnetwork element mode)
	 *  - the product of that Network element, that depends on the KPI/counter selected.
	 * The product is needed in orderto get the parent Element of the Network element, required by Xpert
	 *
	 * As it is complicated to get the Ne Id from the GtmXml where this method is called, the easier and safest way
	 * is to get it from the dataLink URL already created (used on the left click)
	 *
	 * @param String $dataLink the datalink value of the GTM xml clicked data
	 * @param Int $productId  product ID of the NE clicked data
	 * @return String Xpert launcher URL   or null if no link is to be created
	 */
	public function getGTMlinkXpertValue($dataLink,$productId){
		$ret = null;
		// test if the product is supported it must contains certain arcs (at the moment
		// Xpert support cell and SAI with parent bsc or rnc)  if not, no link is to be created
		if(in_array($productId, $this->manager->getSupportedGTMLinkProducts()))
		{
			$neId = $this->getNeFromGTMDataLink($dataLink);
			if(!empty($neId)){
				$ret = $this->getXpertLauncherUrl($neId,$productId);
			} else {
				$this->log("Error: could not get the NE id from datalink '$dataLink'");
			}
		}
		return $ret;
	}


	
	/**
	 * Extract the NE from the GTM data_link value
	 * various format of $dataLink: it always contain a url that contains (once decoded) the NE in ne_axe1=<na>||<NE id>
	 * samples:
	 * javascript:open_window('gtm_navigation.php?id_dash=dshd.0008.01.001%26na_axe1%3Dpcu%26ne_axe1%3Dpcu%7C%7CBSCGE9901-1503&amp;ta=day&amp;ta_value=20090830&amp;mode=overtime&amp;top=3&amp;sort_by=kpi@kpis.0008.01.00001@1@gtms.0008.01.00001@desc&amp;id_menu_encours=menu.dshd.0008.01.001.01')
	 * index.php?id_dash=dshd.0004.01.001&amp;na_axe1=cell&amp;ne_axe1=bsc||3704&amp;ta=day&amp;ta_value=20071002&amp;mode=overnetwork&amp;top=3&amp;id_menu_encours=menu.dshd.0004.01.001.01&amp;sort_by=kpi@kpis.0004.01.00006@2@gtms.0004.01.00001@desc
	 *
	 * @param String $dataLink
	 * @return String Ne ID or '' if couldn't be found
	 */
	private function getNeFromGTMDataLink($dataLink){
		$ret = '';
		$urlParts = explode("ne_axe1=",urldecode($dataLink));
		if(count($urlParts) == 2){
			$neAxe1Parts = explode("&",$urlParts[1]);
			$neAxe1Val = $neAxe1Parts[0];
			$parts = explode("||",$neAxe1Val);
			if(count($urlParts) == 2){
				$ret = $parts[1];
			}
		}
		$this->log("getNeFromGTMDataLink :$ret",3);
		return $ret;

	}

	/**
	 * Log message, use the manager logging process
	 * @param String $string
	 * @param int $level 
	 */
	public function log($string,$level=1){
		$this->manager->log($string,$level);
	}
}


?>
