<?php

/**
 * 14/01/2011 DE Xpert MMT
 * Launch Xpert Viewer in the current window.
 * This is intended to  display Xpert view from link information from T&A Xpert dashboard
 * see XpertDashboardManager class for Xpert link details
 *
 * This class needs to find which Xpert contains the selected ID in order to do the correct redirection
 * It uses an XPert php api to do so, see XpertApplication class method isCellOrSAIAvailable
 * Xpert viewer needs to be provided with the following parameters:
 * - network element ID
 * - Id of the parent of this network element
 * - Date range
 *
 * @author m.monfort
 *
 * 
 *
 * 28/04/2011 MMT bz 21983 un seul import de fichier API Xpert
 * 28/04/2011 MMT bz 21988 utilise mapping pour parent de l'element selectionné
 */
class XpertLauncher {

	//XpertManager obj, used for Xpert application access, database, log and session caching purposes
	private $manager;
	private $neId; //T&A NE id from the GTM
	private $productId; // product Id of the NE
	private $ta; // selector Time aggregation
	private $ta_value; // selector time value in YYYYMM ou YYYYWW ou YYYYMMDD format
	private $period; // selector period

	private $neParentId; // NE parentId
	private $xpert; // Xpert applaication object related to the NE
	private $errorsMsg = Array(); // list of found error messages


	/**
	 * Constructor
	 * @param XpertManager $XpertMger
	 * @param String $ne T&A NE id from the GTM
	 * @param int $product product Id of the NE
	 * @param String $ta selector Time aggregation
	 * @param String $ta_value selector time value in YYYYMM ou YYYYWW ou YYYYMMDD format
	 * @param int $period selector period
	 */
	public function __construct($XpertMger,$ne,$product,$ta,$ta_value,$period)
	{
		$this->manager = $XpertMger;
		$this->log("XpertLauncher neId:".$ne." ta:".$ta." ta_value:".$ta_value." period:".$period);
		$this->neId = $ne;
		$this->productId = $product;
		$this->ta = $ta;
		$this->ta_value = $ta_value;
		$this->period = $period;

	}


   /**
	 * Check validity of paramter values, get the NE ParentId and the target Xpert application
	 * Store possible errors during process
	 */
	public function loadNetworkElementAndFindMatchingXpert(){

		$this->log("loadNetworkElementAndGetErrorMessage ");
		// addBlockingError throws exception to exit at first blocking error encounter
		try{
			$this->verifyParameters();

			// get the parent Id
			$this->neParentId = $this->getCellOrSAIParentId($this->neId, $this->productId);
			if(empty($this->neParentId)){
				$this->addBlockingError(__T('A_XPERT_NO_VALID_PARENT_FOUND',$this->neId));
			}else{
				$this->xpert = $this->getXpertForCellOrSAI($this->neId,$this->neParentId);
			}

			if(empty($this->xpert)){
				$this->addBlockingError(__T('A_XPERT_NO_VALID_APPLICATION_FOUND',$this->neId,$this->neParentId));
			} else {
				$this->log("Found Xpert '".$this->xpert->getLabel()."' for NE '".$this->neId."'");
			}

		} catch(Exception $e){
			$this->log("Exception in loadNetworkElementAndFindMatchingXpert :".$e);
		}
		
	}

	/**
	 * Verify if required parameters are provided
	 */
	private function verifyParameters(){

		if(empty($this->ta)){
			$this->addBlockingError("No time aggreagtion parameter found");
		}

		if(empty($this->ta_value)){
			$this->addBlockingError("No date parameter found");
		}

		if(empty($this->period)){
			$this->addBlockingError("No period parameter found");
		}

		if(empty($this->neId)){
			$this->addBlockingError("No Network element reference found");
		}

		if(empty($this->productId)){
			$this->addBlockingError("No Product reference found");
		}
	}

   /**
	 * True if found any errors during processing
	 * @return boolean
	 */
	public function hasEncounteredErrors(){
		return (count($this->errorsMsg) > 0);
	}

	/**
	 * True if found existing Xpert application matching the NE Id
	 * @return boolean
	 */
	public function hasFoundMatchingXpert(){
		return !empty($this->xpert);
	}

	/**
	 * Get the error message list
	 * @return array
	 */
	public function getErrorMessages(){
		return $this->errorsMsg;
	}


	/**
	 * Get the Xpert viewer URL to redirect the user to, using the found Xpert application
	 * Sample http://192.168.2.50/astellia_iu/xpert/index.php?action=statistic_rnc&tab=cause&cell=9954_301&parent=301&startDate=200905080100&endDate=200905092300
	 *
	 * @return string Xpert Viewer Statistic URL
	 */
	public function getXpertStatisticLinkUrl(){

		$ret = "";
		// check if processing was done
		if(!empty($this->xpert) && (!empty($this->neParentId))){
			$ret = $this->xpert->getStatisticLinkUrl()."&cell=".$this->neId."&parent=".$this->neParentId;

			// need to get a start and end date for Xpert in format YYYYMMDDHHmm using selector date, TA and period

			// startDate and endDate in format TA  YYYYMM ou YYYYWW ou YYYYMMDD...
			// start date is at 00:00 of the first day and end date 23:59 of the last day
			// the last day being included in the period so first day = last day - (period-1)
			// ex: 10/1/2010, TA = day, period =2  should result in start : 201001090000 end 201001102359
			$endDate = $this->ta_value;
			$startDate = getTAMinusPeriod($this->ta,$endDate,$this->period-1);

			$this->log("getLinkToXpertCellOrSaiStats " . $startDate ." to ".$endDate,2);

			// build dates case by case on the ta
			switch ( $this->ta ) {
			case 'hour':
				$startXpertDate = $startDate."00";
				$endXpertDate = $endDate."59";
				break;
			case 'day':
			case 'day_bh':
				$startXpertDate = $startDate."0000";
				$endXpertDate = $endDate."2359";
				break;
			case 'week':
			case 'week_bh':
				// manage week_starts_on_monday
				$startDateDay = Date::getFirstDayFromWeek($startDate,get_sys_global_parameters('week_starts_on_monday',1));
				$startXpertDate = $startDateDay."0000";
				$endDateDay = Date::getLastDayFromWeek($endDate,get_sys_global_parameters('week_starts_on_monday',1));
				$endXpertDate = $endDateDay."2359";
				break;
			case 'month':
			case 'month_bh':
				$startXpertDate = $startDate."010000";
				$endDateDay = $endDate.Date::getLastDayFromMonth($endDate);
				$endXpertDate = $endDateDay."2359";
				break;
			}

			$this->log("Date boundaries for xpert:" . $startXpertDate ." to ".$endXpertDate);
			$ret .= "&startDate=".$startXpertDate."&endDate=".$endXpertDate;
		}
		return $ret;
	}


	/**
	 * get The Xpert application from the NeId and its Parent Id
	 * @param String $neId
	 * @param String $parentId
	 * @return XpertApplication null if not found
	 */
	public function getXpertForCellOrSAI($neId,$parentId)
	{
		$this->log("getXpertForCellOrSAI neId:".$neId,3);
		$ret = null;

		foreach ($this->manager->getXpertApplications() as $xpert){
			$answer = $xpert->isCellOrSAIAvailable($neId,$parentId);
			//the return value is true/false or a String if error
			//28/04/2011 MMT bz 21983 test true/false avec ===
			if($answer === true){
				$ret = $xpert;
				break;
			} else if($answer !== false){
				$this->addError($answer);
			}
		}

		return $ret;
	}


	/**
	 * Get the Cell Id from the Ne ID and the product Id
	 * First check if a mapping is used, then get the parent from the DB
	 * @param String $neId
	 * @param int $productId
	 * @return String parent Id or '' if not found
	 */
	private function getCellOrSAIParentId($neId,$productId)
	{
		$this->log("IN getCellOrSAIParentId of $neId product: $productId ",3);
		
		$arcList = implode(",", $this->manager->getSupportedArcList());

		// DB connection on the NE product
		$productConn = Database::getConnection($productId);
		$productConn->setDebug($this->manager->getDebugLevel());

		//28/04/2011 MMT bz 21988 use getElementProductTopoId
		$neIdToLookFor = $this->getElementProductTopoId($productConn,$neId);

		// get the parent from the $neIdToLookFor
		$query = "
					 SELECT eoar_id_parent FROM edw_object_arc_ref WHERE eoar_id = '".$neIdToLookFor."'
					 AND eoar_arc_type IN (".$arcList.") LIMIT 1";

		$idParent = $productConn->getOne($query);

		//28/04/2011 MMT bz 21988 recupere l'id Mappe du parent si existe
		$idParent = $this->getElementMasterTopoId($productConn,$idParent);
		
		$this->log("ParentId of Cell or SAI '$neId' is '$idParent'");

		return $idParent;
	}

	/**
	 * 28/04/2011 MMT bz 21988
	 * get the product non-mapped network element Id from possibly mapped neId
	 * If not mapped return the
	 * @param DataBaseConnection $productConn DB connection to the neId owner's product
	 * @param string $neId Mapped or not mapped neId
	 * @return string non-mapped network element Id
	 */
	private function getElementProductTopoId($productConn,$neId)
	{
		$ret = $neId;
		// check if the NE ID is mapped to the Topo master
		$query = "SELECT eor_id FROM edw_object_ref WHERE eor_id_codeq = '".$neId."' LIMIT 1";

		$neIdMapped = $productConn->getOne($query);
		// if yes, take its real name in the current product
		if(!empty($neIdMapped)){
			$this->log("Found Mapping  for NE id $neId : $neIdMapped");
			$ret = $neIdMapped;
		}
		return $ret;
	}

	/**
	 * 28/04/2011 MMT bz 21988
	 * opposite function to getElementProductTopoId
	 * get the mapped network element Id from non mapped neId
	 * If not mapping return the given element Id
	 * @param DataBaseConnection $productConn DB connection to the neId owner's product
	 * @param string not mapped neId
	 * @return string mapped network element Id if mapping exist, else returns neId
	 */
	private function getElementMasterTopoId($productConn,$neId)
	{
		$ret = $neId;
		$query = "SELECT eor_id_codeq FROM edw_object_ref WHERE eor_id = '".$neId."' LIMIT 1";

		$neIdMapped = $productConn->getOne($query);
		if(!empty($neIdMapped)){
			$this->log("Found Mapped Id for NE id $neId : $neIdMapped");
			$ret = $neIdMapped;
		}
		return $ret;
	}

	/**
	 * Add a non-blocking error to the list
	 * @param String $msg  error message
	 */
	private function addError($msg){
		$this->errorsMsg[] = $msg;
	}

	/**
	 * Add a blocking error to the list, this will throw an exception
	 * @param String $msg  error message
	 */
	private function addBlockingError($msg){
		$this->addError($msg);
		throw new Exception($msg);
	}


	/**
	 * Log message, use the manager logging process
	 * @param String $string
	 * @param int $level
	 */
	private function log($string,$level=1){
		$this->manager->log($string,$level);
	}

}


?>
