<?php

/**
 *
 * 17/01/2011 MMT DE Xpert 606
 * API Managing dashboard selection from list of criteria
 * 3 main axes of research: by alarm, KPI or Counter additional Dashboard selector values can be provided
 * such as Network element, date time aggregation...
 *
 * Interface definition can be found in the following document
 * \\ast_sf\trending_aggregation$\CB5.0\Classeur\02.DonneesEntree\Demandes d'évolutions\Liens Xpert T&A et gestion Master-slave\TCB50_InterfaceTA_XPert_RE.doc
 *
 * several class in this file:
 * - QueryDashboardAPI : main API class, does the checling of parameter, querying of DB to build the dashboard list
 *   and set selector parameters to the sessions for the dashboard display
 * - KpiCounter : represent a KPI or Counter with name and family, used by the QueryDashboardAPI
 * - DashboardInfo : holds a matching dashboard info such as name and menu Id, used for display
 *
 * Merge to 5.1.4:
 * 11/04/2011 MMT bz 18176 reopened: mauvais format de config de $_SESSION["TA"]["selecteur"]["ne_axe1"] pour pour le dashboard
 * 06/06/2011 MMT DE 3rd Axis change le format de selection NE 3eme axe -> meme que le 1er
 * 27/07/2011 MMT Bz 22896 add 3rd axis preferences session variable
 *
 *
 *
 * @author m.monfort
 */
class QueryDashboardAPI {

	// search types
	const ST_ALARM = "alarm";
	const ST_KPI = "kpi";
	const ST_COUNTER = "counter";
	private $search_type_list = Array(self::ST_ALARM,self::ST_KPI,self::ST_COUNTER);

	// alarm types
	const AT_STATIC = "static";
	const AT_DYNAMIC = "dyn";
	const AT_DYN_ALARM = "dyn_alarm";
	const AT_TOP_WORST = "top-worst";
	private $alarm_type_list = Array(self::AT_STATIC,self::AT_DYNAMIC,self::AT_DYN_ALARM,self::AT_TOP_WORST);

	// param names of the interface
	const PN_SEARCH_TYPE = "search_type";
	const PN_ALARM_TYPE = "alarm_type";
	const PN_ID_ALARM = "id_alarm";
	const PN_NA = "na";
	const PN_NA_VALUE = "na_value";
	const PN_NA3 = "na3";
	const PN_NA3_VALUE = "na3_value";
	const PN_TA = "ta";
	const PN_TA_VALUE = "ta_value";
	const PN_PRODUCT = "product";
	const PN_PRODUCT_CODE = "product_code";
	const PN_KPI_CODE = "kpi_code";
	const PN_COUNTER_CODE = "counter_code";
	const PN_FAMILY_CODE = "family_code";
	const PN_PERIOD = "period";



	// param values
	private $search_type;
	private $alarm_type;
	private $id_alarm;
	private $na;
	private $na_value;
	private $na3;
	private $na3_value;
	private $ta;
	private $ta_value;
	private $productId;
	private $kpi_code;
	private $counter_code;
	private $family_code;
	private $period;

	// other values
	private $db;// connection to the master product
	private $targetProductDb; // connection to the target product

	private $errors = Array();
	private $debug; // level from sys_debug external_link

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->db = Database::getConnection();	
		$this->debug = get_sys_debug('external_link');
		$this->db->setDebug($this->debug);
		if ( $this->debug ){
			 $this->log(" <b> >>>>>>>>>> DEBUG MODE :  ".$this->debug."  <<<<<<<<<< </b>");
		}
	}

	// *************************************************************************************
	//				  ------------ parameters Validation and affectation ----------
	// *************************************************************************************


	/**
	 * validate all given parameters  and affect them to the object private member
	 * @param array<key->value> $paramList
	 */
	public function checkAndAffectParams($paramList){

		try{
			$this->log("Given Parameters:");
			$this->log($paramList);
			$this->search_type = $this->checkAndGetSearchTypeFromParams($paramList);

			$this->log("search type is :".$this->search_type);

			$this->checkMissingRequiredParams($paramList);

			$this->productId = $this->checkAndGetProductIdFromParams($paramList);
			$this->log("Product id is :".$this->productId);
			$this->targetProductDb = Database::getConnection($this->productId);
			$this->targetProductDb->setDebug($this->debug);

			if($this->getSearchType() == self::ST_ALARM){
				$this->checkAlarmTypeParam($paramList);
			}

			$this->alarm_type = $this->getParamValue(self::PN_ALARM_TYPE,$paramList);
			$this->id_alarm = $this->getParamValue(self::PN_ID_ALARM,$paramList);

			$this->na = $this->getParamValue(self::PN_NA,$paramList);
			$this->na_value = $this->getParamValue(self::PN_NA_VALUE,$paramList);
			$this->na3 = $this->getParamValue(self::PN_NA3,$paramList);
			$this->na3_value = $this->getParamValue(self::PN_NA3_VALUE,$paramList);

			$this->ta = $this->getParamValue(self::PN_TA,$paramList);
			$this->ta_value = $this->getParamValue(self::PN_TA_VALUE,$paramList);
			$this->period = $this->getParamValue(self::PN_PERIOD,$paramList);


			$this->kpi_code = $this->getParamValue(self::PN_KPI_CODE,$paramList);
			$this->counter_code = $this->getParamValue(self::PN_COUNTER_CODE,$paramList);
			$this->family_code = $this->getParamValue(self::PN_FAMILY_CODE,$paramList);

		}catch(Exception $e){
			$this->log("Exception in checkAndAffectParams :".$e);
		}
	}

	/**
	 * validate the given Search type parameter and return it
	 */
	private function checkAndGetSearchTypeFromParams($paramList){
		$ret = self::ST_ALARM;
		if(array_key_exists(self::PN_SEARCH_TYPE, $paramList)){
			$value = $paramList[self::PN_SEARCH_TYPE];
			if(in_array($value,$this->search_type_list)){
				$ret = $value;
			} else {
				$this->addParamValueError(self::PN_SEARCH_TYPE,$value);
			}
		}
		return $ret;
	}

	/**
	 * get a parameter value from an array, setting optional default value if not found or null
	 * @param String $paramName
	 * @param array<key->value> $paramList
	 * @param String $default
	 * @return String or null if not found with no default
	 */
	private function getParamValue($paramName,$paramList,$default=null){
		$ret = null;
		if(array_key_exists($paramName, $paramList)){
			$ret = $paramList[$paramName];
		}
		else if ($default != null){
			$ret = $default;
		}
		return $ret;
	}

	/**
	 * validate product parameters and get the product Id from them
	 * two ways of indicating the product
	 * 1 - product Id directly
	 * 2 - product code, in this case this function needs to look up the id from the DB
	 * @param array<key->value> $paramList
	 * @return int product Id
	 */
	private function checkAndGetProductIdFromParams($paramList){

		$this->log("checkAndGetProductIdFromParams IN");
		$ret = '';
		if(array_key_exists(self::PN_PRODUCT, $paramList)){
			$ret = $paramList[self::PN_PRODUCT];
		} else {
			$paramCode = $paramList[self::PN_PRODUCT_CODE];
			$this->log("Param Product Code : ".$paramCode);

			$productsInformations = getProductInformations();

			// need to query each product DB individually
			$query = "
						 SELECT saai_interface FROM sys_aa_interface sai, sys_global_parameters sgp
						 WHERE sgp.parameters = 'module'
						 AND sai.saai_module = sgp.value";
			$this->log("query to run on each product: ".$query);
			foreach ( $productsInformations as $clef => $product)
			{
				$prdId = $product['sdp_id'];
				$db = Database::getConnection($prdId);
				$code = $db->getOne($query);
				$db->close();

				$this->log("testing product $prdId with code: $code");
				if($code == $paramCode){
					$ret = $prdId;
					break;
				}
			}
			if($ret == ''){
				$this->addBlockingError("Could not find product with code '$paramCode'");
			}
		}
		return $ret;
	}



	/**
	 * test if any required parameters are missing from the given list
	 * @param array<key->value> $paramList
	 */
	private function checkMissingRequiredParams($paramList){
		
		$paramsToTest = Array();

		switch ( $this->getSearchType() ) {
			case self::ST_ALARM:
				$paramsToTest[] = self::PN_ALARM_TYPE;
				$paramsToTest[] = self::PN_ID_ALARM;
				break;
			case self::ST_KPI:
				$paramsToTest[] = self::PN_KPI_CODE;
				$paramsToTest[] = self::PN_FAMILY_CODE;
				break;
			case self::ST_COUNTER:
				$paramsToTest[] = self::PN_COUNTER_CODE;
				$paramsToTest[] = self::PN_FAMILY_CODE;
				break;
		}
		if(array_key_exists(self::PN_NA_VALUE, $paramList)){
			$paramsToTest[] = self::PN_NA;
		}

		if(array_key_exists(self::PN_TA_VALUE, $paramList)){
			$paramsToTest[] = self::PN_TA;
		}

		if(!array_key_exists(self::PN_PRODUCT, $paramList)){
			$paramsToTest[] = self::PN_PRODUCT_CODE;
		}

		foreach ($paramsToTest as $param){
			if(!array_key_exists($param, $paramList)){
				$this->addBlockingError("Missing required parameter '".$param."'");
			}
		}
	}

	/**
	 * validate param list from
	 * @param <type> $paramList
	 */
	private function checkAlarmTypeParam($paramList){
		$value = $paramList[self::PN_ALARM_TYPE];
		if(!in_array($value,$this->alarm_type_list)){
			$this->addParamValueError(self::PN_ALARM_TYPE,$value);
		}
	}

	/**
	 * generate a parameter value error resulting from failed validation of the value
	 * these generate a blocking error
	 * @param String $name
	 * @param String $value
	 */
	private function addParamValueError($name,$value){
		$this->addBlockingError("Invalid value for parameter '".$name."': '".$value."'");
	}



	// *************************************************************************************
	//						  ------------ Dashboard Queries ----------
	// *************************************************************************************

	/**
	 * Get the list of dashboards matching the given criteria
	 * 1 - get the list of KPI/counter
	 * 2 - get the list of Graphs with those KPI/counters
	 * 3 - get the list of Dashboard with those graphs and that the user is allowed to
	 * @return array of dashboard objects
	 */
	public function getMatchingDashboardList(){
		$this->log("getMatchingDashboardList IN",2);
		$ret = Array();
		try{

			//1 - get the list of KPI/counter
			$kpiCounters = array();
			// if alarm get the list of KPI/counters for that alarm
			if($this->getSearchType() == self::ST_ALARM){
				$kpiCounters = $this->getKpiCountersFromAlarm($this->id_alarm, $this->alarm_type);
				$this->log("getKpiCountersFromAlarm Found ".count($kpiCounters)." KPI/counters");
			}
			else if($this->getSearchType() == self::ST_KPI)
			{
				$kpiCounters[] = $this->getKpi($this->kpi_code,$this->family_code);
			}
			else if($this->getSearchType() == self::ST_COUNTER)
			{
				$kpiCounters[] = $this->getCounter($this->counter_code,$this->family_code);
			}
			//2 - get the list of Graphs with those KPI/counters
			$graphs = $this->getGraphsForKpiCounters($kpiCounters);
			$this->log("getGraphsForKpiCounters Found ".count($graphs)." graph(s)");
			$this->log($graphs);
			//3 - get the list of Dashboard with those graphs
			$ret = $this->getDashboardsForGraphs($graphs);
			$this->log("getDashboardsForGraphs Found ".count($ret)." dashboards");
			
		}
		catch(Exception $e){
			$this->log("Exception in getMatchingDashboardList :".$e);
		}
		return $ret;

	}


	/**
	 * Get the alarm table name from the type of alarm
	 * @param String $alarmType
	 * @return String
	 */
	private function getAlarmTableFromParamType($alarmType){
		$ret = "sys_definition_alarm_";

		// 23/05/2008 - Modif; benoit : correction du bug 6342. Remplacement de "dyn" par "dyn_alarm"
		switch($alarmType){
			case self::AT_TOP_WORST :
				$ret .= "top_worst";
				break;
			// 10:46 27/08/2009 GHX
			// Correction du BZ 11252
			// On remet le cas "dyn" car si on vient de alarm management on a une erreur comme quoi l'alarme n'existe pas
			case self::AT_DYNAMIC :
			case self::AT_DYN_ALARM :
				$ret .= "dynamic";
				break;
			case self::AT_STATIC :
				$ret .= "static";
				break;
		}
		return $ret;
	}


	/**
	 * get the list of Kpicounter Object from given alarm info
	 * @param String $alarmId
	 * @param String $alarmType
	 * @return array<KpiCounter> list of related KpiCounter object
	 */
	private function getKpiCountersFromAlarm($alarmId,$alarmType)
	{
		$this->log("getKpiCountersFromAlarm $alarmId , $alarmType");
		$ret = array();
		
		$table_name = $this->getAlarmTableFromParamType($alarmType);

		$query = "
				  SELECT *
				  FROM $table_name
				  WHERE alarm_id='$alarmId'
				  ORDER BY alarm_trigger_type DESC
			 ";
		$result = $this->targetProductDb->getall($query);


		if (count($result) > 0) {
			$tmp = array();
			foreach ($result as $row)
			{
				$tmp[] = new KpiCounter($this,$row['alarm_trigger_type'],$row['alarm_trigger_data_field'],$row['family']);
				$tmp[] = new KpiCounter($this,$row['additional_field_type'],$row['additional_field'],$row['family']);
				if($alarmType == self::AT_TOP_WORST){
					$tmp[] = new KpiCounter($this,$row['list_sort_field_type'],$row['list_sort_field'],$row['family']);
				}
				else if($alarmType == self::AT_DYNAMIC || $alarmType == self::AT_DYN_ALARM){
					$tmp[] = new KpiCounter($this,$row['alarm_field_type'],$row['alarm_field'],$row['family']);
				}
			}
			// only keep the valid objects
			foreach ($tmp as $kc){
				if($kc->isValid()){
					$ret[] = $kc;
				}
			}
		} else {
			$this->addBlockingError("Could not find '$table_name' alarm Id '$alarmId' on product id $this->productId");
		}
		return $ret;
	}

	

	/**
	 * get the related Kpicounter Object from given Kpi info
	 * @param String $kpiCode
	 * @param String $familyCode
	 * @return KpiCounter
	 */
	private function getKpi($kpiCode,$familyCode){

		$ret = null;
		$this->log("getKpi IN $kpiCode, $familyCode");
		$query="
		SELECT distinct kpi_name
		FROM sys_definition_kpi a,sys_definition_group_table b
		WHERE b.edw_group_table=a.edw_group_table
			AND a.visible = 1 AND a.on_off=1
			AND b.family='$familyCode'
			AND kpi_name='$kpiCode'";

		$result = $this->targetProductDb->getall($query);

		$this->log("getKpi found ".count($result)." matching KPI(s)");
		if(count($result) > 0){
			$ret = new KpiCounter($this,KpiCounter::TYPE_KPI,$kpiCode,$familyCode);
		} else {
			$this->addBlockingError("Could not find KPI '$kpiCode' of family '$familyCode' on product id $this->productId");
		}
		return $ret;

	}

	/**
	 * get the related Kpicounter Object from given Counter info
	 * @param String $kpiCode
	 * @param String $familyCode
	 * @return KpiCounter
	 */
	private function getCounter($counterCode,$familyCode){
		$ret = null;
		$this->log("getCounter IN $counterCode, $familyCode");
		$query="
		SELECT distinct edw_field_name
		FROM sys_field_reference a, sys_definition_group_table b
		WHERE b.edw_group_table=a.edw_group_table
			AND a.visible = 1 AND a.on_off=1
			AND b.family='$familyCode'
			AND edw_field_name='$counterCode'";

		$result = $this->targetProductDb->getall($query);

		$this->log("getCounter found ".count($result)." matching Counter(s)");
		if(count($result) > 0){
			$ret = new KpiCounter($this,KpiCounter::TYPE_COUNTER,$counterCode,$familyCode);
		} else {
			$this->addBlockingError("Could not find Raw Counter '$counterCode' of family '$familyCode' on product id $this->productId");
		}
		return $ret;
	}


	/**
	 * get the list of Graphs Ids from a list of KpiCounter object
	 * @param array<KpiCounter> $kpiCounterList
	 * @return array<String>
	 */
	private function getGraphsForKpiCounters($kpiCounterList)
	{
		$this->log("getGraphsForKpiCounters IN");
		$ret = array();
		$where_clauses = array();

		foreach ($kpiCounterList as $kc) {
			// on va chercher les id_ligne du raw/kpi
			if ($kc->isKpi()){
				$query = "select id_ligne from sys_definition_kpi where kpi_name='".$kc->getName()."'";
			}else{
				$query = "select id_ligne from sys_field_reference where edw_field_name='".$kc->getName()."'";
			}
			$id_elem = $this->targetProductDb->getone($query);


			// on ajoute la where clause
			// 18/09/2009 BBX : le type doit être testé car si le type est "raw" il faut mettre "counter" dans le requête. BZ 11629
			$where_clauses[] = "(id_elem='$id_elem' AND id_product='$this->productId' AND class_object='{$kc->getClassObject()}')";
		}
		if(count($where_clauses) > 0){
			$query = "
				SELECT DISTINCT id_page
					FROM sys_pauto_config
					WHERE ".implode(' OR ',$where_clauses)."
				";
			// 18/09/2009 BBX : correction du test de résultat et parcours des données renvoyées par la requête. BZ 11629
			$result = $this->db->getall($query);
			
			foreach ($result as $row){
				$ret[] = $row['id_page'];
			}
			
		}
		return $ret;
	}


	/**
	 * get the list of dashboards matching the list of graph Ids and available to the user's profile
	 * @param array<String> $graphList
	 * @return array<DashboardInfo>
	 */
	private function getDashboardsForGraphs($graphList){

		$this->log("getDashboardsForGraphs IN:");
		$ret = Array();

		if(count($graphList) > 0){
			$menuProfiles = $this->getMenuProfilesFromSession();
			$query = "
				SELECT spc.id_page,sppn.page_name,COUNT(spc.id_page) AS nb_graphs
				FROM sys_pauto_config AS spc, sys_pauto_page_name AS sppn, menu_deroulant_intranet AS mdi
				WHERE spc.id_elem IN ('".implode("','",$graphList)."')
					AND spc.id_page = sppn.id_page
					AND mdi.id_page = sppn.id_page
					AND mdi.id_menu IN ('".implode("','", $menuProfiles)."')
				GROUP BY spc.id_page,sppn.page_name
				ORDER BY nb_graphs DESC
			";
			$result = $this->db->getall($query);

			// 20/08/2009 BBX : correction de la récupération des dashboards. BZ 11118
			$dashboardsToDisplay = Array();
			if ($result) {
				foreach ($result as $row) {
					// on cherche le lien du dashboard

					// 20/08/2009 BBX : ajout de l'id_menu dans les valeurs à récupérer. BZ 11119
					// 11:32 27/08/2009 GHX
					// Ajout de la condition pour que le lien du menu ne soit pas vide sinon il y a des cas ou le dashboard n'est jamais affiché
					// Ajout du order by pour toujours avoir OVERTIME  (sauf si le dashboard est uniquement en mode ONE)
					$query="SELECT id_menu,lien_menu FROM menu_deroulant_intranet WHERE id_page='{$row['id_page']}' AND lien_menu IS NOT NULL ORDER BY id_menu ASC";

					$dashResult = $this->db->getRow($query);
					$lien_menu = $dashResult['lien_menu'];

					if ($lien_menu) {
						// 10/08/2007 - Modif. benoit : modification du masque de recherche de la fonction 'str_replace()' en changeant la valeur "defaut" de "selecteur_scenario" par "normal" (le scenario "defaut" n'existe plus)
						$link = str_replace('selecteur_scenario=normal','selecteur_scenario=byurl&na='.$this->na.'&na_value='.$this->na_value.'&affichage_header=0',$lien_menu);
						$ret[] = new DashboardInfo($row['page_name'],$dashResult['id_menu'],$link);
					}
				}
			}
		}
		return $ret;
	}

	/**
	 * get the list of Menu profiles stored in session
	 * if they can't be found, get them from the DB using the id_user from the session
	 * throws an error if session is not available
	 * @return array<String>  list of menu_profile
	 */
	private function getMenuProfilesFromSession(){

		if(array_key_exists('menu_profile',$_SESSION)){
			$this->log("Found Session menu_profile");
			$ret = $_SESSION['menu_profile'];
		} else {
			$this->log("Session menu_profile not found, getting profiles from user Id");
			$ret = array();
			if(array_key_exists('id_user',$_SESSION)){
				$userId = $_SESSION['id_user'];
			}else{
				$this->addBlockingError("Could not find session user information");
			}
			$this->log("Found Session userID '$userId'");
			$query = "
						 SELECT profile_to_menu
						 FROM profile, users
						 WHERE profile.id_profile = users.user_profil
						 AND users.id_user='$userId'";
			$profile_to_menu = $this->db->getone( $query );
			if(empty($profile_to_menu)){
				$this->addBlockingError("Unknown user Id '$userId'");
			} else {
				$ret = explode("-", $profile_to_menu);
			}
		}
		
		$this->log("getMenuProfilesStringFromSessionUser found ".count($ret)." MenuProfiles");
		return $ret;

	}



	/**
	 *  set the parameters values related to the dashboard selector in the session so they can be
	 *  affected to the dashboard selector on display
	 */
	public function affectSelectorValuesToSession(){

		$this->log("affectValuesToSession ",2);

		//3d axis management, get it from alarm details if search type is alarm
		if(empty($this->na3) && $this->getSearchType() == self::ST_ALARM){
			$na3dAxis = $this->getThirdAxisNaFromAlarm($this->id_alarm, $this->alarm_type);
		} else {
			$na3dAxis = $this->na3;
		}
		if(!empty($na3dAxis)){
			$_SESSION["TA"]["selecteur"]["na_axeN"] = $na3dAxis;
			if(!empty($this->na3_value)){
				// 06/06/2011 MMT DE 3rd Axis change le format de selection NE 3eme axe -> meme que le 1er
				$_SESSION['TA']['selecteur']['ne_axeN'] = $na3dAxis."||".$this->na3_value;
				// 27/07/2011 MMT Bz 22896 add 3rd axis preferences session variable
				$_SESSION['TA']['ne_axeN_preferences'] = $_SESSION['TA']['selecteur']['ne_axeN'];
			}
		}

		// time management
		if(!empty($this->ta)){
			$_SESSION["TA"]["selecteur"]["ta"] = $this->ta;
		}
		if(!empty($this->ta_value)){
			$_SESSION["TA"]["selecteur"]["ta_value"] = $this->ta_value;
		}
		if(!empty($this->period)){
			$_SESSION["TA"]["selecteur"]["period"] = $this->period;
		}
		// Network element and aggregation management
		$_SESSION["TA"]["selecteur"]["na_axe1"] = $this->na;
		//11/04/2011 MMT bz 18176 reopened: mauvais format pour le dashboard (MERGE 5.1.4)
		$_SESSION["TA"]["selecteur"]["ne_axe1"] = $this->na."||".$this->na_value;
		$_SESSION["TA"]['network_element_preferences'] = $this->na."||".$this->na_value;
		$this->log("affected Values To Session:");
		$this->log($_SESSION["TA"]);
	}

	/**
	 * get the third axis Network aggregation value from alamr details
	 * @param String $alarmId
	 * @param String $alarmType
	 * @return String the 3rd axis Network aggregation
	 */
	private function getThirdAxisNaFromAlarm($alarmId,$alarmType){

		$this->log("getThirdAxisNaFromAlarm $alarmId , $alarmType",2);
		$table_name = $this->getAlarmTableFromParamType($alarmType);

		// 23/09/2010 BBX
		// Gestion du 3ème axe
		// BZ 18036
		$query = "SELECT DISTINCT network FROM $table_name
		WHERE alarm_id = '$alarmId'";
		$alarmNetwork = $this->targetProductDb->getOne($query);
		list($na1stAxis,$na3dAxis) = explode('_',$alarmNetwork);

		if(empty($na3dAxis)){
			$this->log("No 3rd axis NA found for that alarm. network value: '$alarmNetwork'");
		} else {
			$this->log("getThirdAxisNaFromAlarm NA : $na3dAxis");
		}
		return $na3dAxis;
   }


	/**
	 * Log message according to given level and current debug level
	 * @param String/array $message to log if an array is provided the print_r method will be used
	 * @param int $level min debug level requiered to log
	 */
	public function log($string,$level=1){

		if($this->debug >= $level){
			echo "<pre>";
			//echo gettype($string);
			if(gettype($string) == "array"){
				print_r ( $string );
			} else {
				echo $string;
			}
			echo "</pre>\n";
		}
	}

	/**
	 * get the search type alarm, KPI or Counter
	 * @return String
	 */
	public function getSearchType(){
		return $this->search_type;
	}

	/**
	 * true if any errors were encountered since the instance was created
	 * @return boolean
	 */
	public function hasEncounteredErrors(){
		return (count($this->errors) > 0);
	}

	/**
	 * get the list of error messages
	 * @return array<String>
	 */
	public function getErrorMessages(){
		return $this->errors;
	}

	/**
	 * add a non-blocking error to the list
	 * @param String $msg
	 */
	private function addError($msg){
		$this->errors[] = $msg;
	}

	/**
	 * add a blocking error to the list
	 * it will throw an exception
	 * @param String $msg
	 */
	public function addBlockingError($msg){
		$this->addError($msg);
		throw new Exception($msg);
	}

	/**
	 * get a summary label of the query, for display purposes
	 * @return string
	 */
	public function getQuerySummary(){
		$ret = "List of dashboards from ";
		switch ( $this->getSearchType() ) {
			case self::ST_ALARM:
				$ret .= "selected alarm";
				break;
			case self::ST_KPI:
				$ret .= "KPI '".$this->kpi_code."'";
				break;
			case self::ST_COUNTER:
				$ret .= "counter '".$this->counter_code."'";
				break;
		}
		
		return $ret;
	}

}


/**
 * Class KpiCounter
 * represent a KPI or Counter with name and family, used by the QueryDashboardAPI
 */
class KpiCounter {

	// types
	const TYPE_KPI = 'kpi';
	const TYPE_COUNTER = 'counter';

	private $api; //QueryDashboardAPI object use it for logging and error reporting
	private $type; //kpi or counter
	private $name;
	private $family;// not used yet

	/**
	 * Constructor
	 * @param QueryDashboardAPI $api api object
	 * @param String $type kpi or counter
	 * @param String $name
	 * @param String $family 
	 */
	public function __construct($api,$type,$name,$family)
	{
		$this->api = $api;
		$this->name = $name;

		$this->affectType($type);
		$this->family = $family;
		if($this->isValid()){
			$this->log("new KpiCounter: type:".$this->getClassObject().", name:$name, family:$family");
		}
	}

	/**
	 * affect the given type, kpi or counter
	 * @param <type> $type
	 */
	private function affectType($type){
		$typeToAffect = $type;
		// in some cases (alarms), the counter type is given as "raw" from the db
		if($type == "raw"){
			$typeToAffect = self::TYPE_COUNTER;
		}
		$this->type = $typeToAffect;
	}

	/**
	 * return if the current object represent a valid KPI or Counter
	 * checks if the type is correct
	 * @return boolean
	 */
	public function isValid(){
		return ($this->type == self::TYPE_KPI || $this->type == self::TYPE_COUNTER);
	}

	/**
	 * get the name of the kpi/counter
	 * @return String
	 */
	public function getName(){
		return $this->name;
	}

	/**
	 * true if the object is a KPI, false if is a counter
	 * @return boolean
	 */
	public function isKpi(){
		return ($this->type == self::TYPE_KPI);
	}

	/**
	 * get the type of the object KPI or counter
	 * @return String
	 */
	public function getClassObject(){
		return $this->type;
	}


	/**
	 * log message using the api
	 * @param String $string
	 * @param int $level
	 */
	private function log($string,$level=1){
			$this->api->log($string,$level);
	}

}

/**
 * Class DashboardInfo
 * Represent one matching dashboard from the API, it holds information required for the display and the link
 * to the actual dashboard page
 */
class DashboardInfo {

	private $label;
	private $id_menu;
	private $link;

	/**
	 * Constructor
	 * @param String $label
	 * @param String $id_menu
	 * @param String $link
	 */
	public function __construct($label,$id_menu,$link)
	{
		$this->label = $label;
		$this->id_menu = $id_menu;
		$this->link = $link;
	}

	/**
	 * get the dashboard label
	 * @return String
	 */
	public function getLabel(){
		return $this->label;
	}

	/**
	 * get the dashboard link
	 * @return String
	 */
	public function getLink(){
		return $this->link;
	}

	/**
	 * get the dashboard menu id
	 * @return String
	 */
	public function getIdMenu(){
		return $this->id_menu;
	}

}
?>
