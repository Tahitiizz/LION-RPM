<?php
include_once REP_PHYSIQUE_NIVEAU_0.'querybuilder/class/QbRawKpi.class.php';

/**
 * Class to manage querybuilder queries and exports
 */
class QBModel {

	const CSV_EXPORT_TMP_DIR = 'upload/querybuilder_csv_export'; // temporary csv queries export directory
			
	protected $database;		// database connection object
	
	/** Constructor
	 * @param $database: the database connection object
	 */	
	public function __construct($database=null)	{
		$this->database = $database;
	}
											
	/** Get queries for a given id
	 * @param $id: the query id
	 * @return the query
	 */
	public function getQueryById($id) {		
		$sql = "SELECT * FROM qb_queries WHERE id =$id";
		return $this->database->getRow($sql);
	}
		
	/** Get queries for a given name and user
	 * @param $name string: the query name
	 * @param $user_id string: the user_id
	 * @return the query
	 */
	public function getQueryByName($name, $user_id) {			
		$sql = "SELECT * FROM qb_queries WHERE name ='".pg_escape_string($name)."' AND user_id='$user_id'";						
		return $this->database->getRow($sql);
	}
		
	/** Check if the user is the owner of the query
	 * @param $id: the query id
	 * @param $user_id string: the user_id
	 * @return boolean : true if the user_id is the owner of this query
	 */
	public function isOwner($id, $user_id) {			
		$sql = "SELECT user_id FROM qb_queries WHERE id=$id";						
		$owner = $this->database->getOne($sql);
		
		if ($owner == $user_id) {
			return true;
		} else {
			return false;
		}
	}
		
	/** Check if the user is the owner of the download
	 * @param $id: the download id
	 * @param $user_id string: the user_id
	 * @return boolean : true if the user_id is the owner of this download
	 */
	public function isDownloadOwner($id, $user_id) {			
		$sql = "SELECT user_id FROM qb_exports WHERE id=$id";
		$owner = $this->database->getOne($sql);
			
		if ($owner == $user_id) {			
			return true;
		} else {			
			return false;
		}
	}
				
	/** Get queries for a given user id
	 * @param $user_id: the user id
	 * @return array the queries for this user
	 */
	public function getUserQueries($user_id) {		
		// get user queries
		$sql = "SELECT * FROM qb_queries WHERE user_id ='$user_id' ORDER BY name";
		return $this->database->getAll($sql);
	}		
	
	/** Get shared queries
	 * @return array the shared queries
	 */
	public function getSharedQueries()	{		
		// get user queries		
		$sql = "SELECT * FROM qb_queries, users WHERE qb_queries.user_id = users.id_user AND shared=true ORDER BY qb_queries.name";
		return $this->database->getAll($sql);
	}
		
	/** Insert a new query
	 * @param name - string: the query name
	 * @param type - string: the query type
	 * @param user_id - string: the user id
	 * @param json - string: the query JSON format
	 * @return the query id
	 */
	public function insertQuery($name, $type, $user_id, $json)	{									
		//$json = str_replace("\\'", "''", addslashes($json)); 				
		$json = pg_escape_string($json);
		
		// insert query				
		$sql = "INSERT INTO qb_queries (name, type, user_id, json) VALUES ('".pg_escape_string($name)."', '$type', '$user_id', '".$json."') RETURNING id";			
		return $this->database->getOne($sql);
	}	
	
	/** Update an existing query
	 * @param id - string: the query id
	 * @param type - string: the query type 
	 * @param json - string: the query JSON format
	 */
	public function updateQuery($id, $type, $json)	{
		//$json = str_replace("\\'", "''", $json);			
		$json = pg_escape_string($json);	
		
		// update query					
		$sql = "UPDATE qb_queries SET type = '$type', json = '".$json."' WHERE id=$id";								
		return $this->database->execute($sql);
	}										

	/** Set shared attribute for a query
	 * @param id - string: the query id
	 * @param shared - string: true/false
	 */
	public function setSharedAttribute($id, $shared)	{		
		// update query				
		$sql = "UPDATE qb_queries SET shared = '$shared' WHERE id=$id";				
		return $this->database->execute($sql);
	}								
		
	/** Delete a query
	 * @param id - string: the query id
	 */
	public function deleteQuery($id)	{		
		// delete query
		$sql = "DELETE FROM qb_queries WHERE id=$id";				
		return $this->database->execute($sql);
	}											
	
	/** Delete an export
	 * @param id - string: the export id
	 */
	public function deleteExportRow($id)	{
		// get export file path	
		$sql = "SELECT file FROM qb_exports WHERE id=$id";
		$filePath = $this->database->getOne($sql);
		
		// if a csv file has been generated, delete it !
		if ($filePath) {
			$paths = explode('/', $filePath);
			$fileName = array_pop($paths);
			$dirName = array_pop($paths);
			
		    $command0 = "cd " . REP_PHYSIQUE_NIVEAU_0 . "upload/querybuilder_csv_export/;rm $dirName -rf;";
			exec($command0);
		}
			
		// delete query
		$sql = "DELETE FROM qb_exports WHERE id=$id";				
		return $this->database->execute($sql);
	}											
	
	/** Get favorites network elements lists for a user
	 * @param: $user_id	- the user id
	 * @return: NE elements lists for user_id
	 */
	public function getFavNeList($user_id) {	  	
		$sql = "SELECT id, name, (SELECT COUNT(*) FROM qb_favorite_object_ref WHERE qb_favorite_id=qb_favorite_ne.id) as nb FROM qb_favorite_ne WHERE user_id ='$user_id' ORDER BY name";		
		return $this->database->getAll($sql);
	}
		
	/** Get favorite by it id
	 * @param id the favorite id
	 */
	public function getFavoriteById($id) {		
		$sql = "SELECT 	qb_favorite_object_ref.eor_id
				FROM
					qb_favorite_ne,
					qb_favorite_object_ref
				WHERE
					qb_favorite_ne.id = qb_favorite_object_ref.qb_favorite_id
					AND qb_favorite_ne.id = $id ";				
		return $this->database->getAll($sql); 
	}
	
	/** Get NE labels
	 * @param $listId array list of eor_id
	 */
	public function getNeLabels($listId) {		
		$sql = "SELECT 	eor_id as code, COALESCE(eor_label, eor_id) as label FROM edw_object_ref WHERE eor_id IN('".implode("','", $listId)."')";		
		return $this->database->getAll($sql); 
	}
		
	/** Get NE from NE codes list
	 * @param String NE code list (NE1, NE2, NE3 ...) 
         * @param String NA code (Ajout BBX BZ 26949 24/04/2012)
	 * @return NE rows
	 */
	public function getNEListFromCodeList($neList, $na = '') {		
		$sql = "SELECT eor_id as code, 
					CASE WHEN edw_object_ref.eor_label IS NOT NULL THEN edw_object_ref.eor_label||' ('||edw_object_ref.eor_id||')' ELSE '('||edw_object_ref.eor_id||')' END AS label
				FROM					
					edw_object_ref
				WHERE
					edw_object_ref.eor_id IN ($neList)";
                
                if(!empty($na)) {
                    $sql .= " AND eor_obj_type = '$na'";
                }

		return $this->database->getAll($sql); 
	}
		
	/** Save NE list to favorite
	 * @param: $user_id the current user id
	 * @param: $favName the name for this favorite
	 * @param: $neCodeList the NE to save into this favorite
	 */
	public function saveToFavorites($user_id, $favName, $neCodeList) {
		$sql = "SELECT id from qb_favorite_ne WHERE user_id= '$user_id' AND name='".pg_escape_string($favName)."'";			
		$row = $this->database->getRow($sql);				  
	
		// Remove old favorites		
		if($row) {
			$favId = $row['id'];
			// Remove all NE from this favorite
			$sql = "DELETE FROM qb_favorite_object_ref WHERE qb_favorite_id = ".$favId;			
			$this->database->execute($sql);						
		} else {
			$sql = "INSERT INTO qb_favorite_ne (name, user_id) VALUES ('".pg_escape_string($favName)."', '".$user_id."') RETURNING id";				
			$favId = $this->database->getOne($sql); 
		}
	
		// Create one insert for each NE
		$sqlList = array();
		foreach ($neCodeList as $neCode) {			
			$sqlList[] = "INSERT INTO qb_favorite_object_ref (qb_favorite_id, eor_id) VALUES ('".$favId."', '$neCode')";								 	
		}
		
		// Execute inserts
		$sql = implode (';', $sqlList);		
		if ($sql) {
			$this->database->execute($sql);
		}
	}

	/** Delete a NE favorite
	 * @param $user_id the user id
	 * @param $favId the favorite id to delete
	 */
	public function deleteNeFavorite($user_id, $favId) {
		
		// Check if the favorite belongs to the current user
		$sql = "SELECT id from qb_favorite_ne WHERE user_id= '$user_id' AND id=$favId";			
		$row = $this->database->getRow($sql);				  
		
		// The favorite has been found for this user		
		if($row) {
			// Remove NE from this favorite
			$sql = "DELETE FROM qb_favorite_object_ref WHERE qb_favorite_id = ".$favId;			
			$this->database->execute($sql);			
			$sql = "DELETE FROM qb_favorite_ne WHERE id=$favId";
			$this->database->execute($sql);			
		}		
	}	
		
	/** Return NA in common
	 * @param $list array list of elements (RAW, KPI)
	 * @type $type string - 'na' or 'na_axe3'
	 * @return: array NA list or false if nothing in common
	 */
	private function getNaInCommonFromList($list, $type) {
		
		// get NA in common						   	 
		$na = getNALabelsInCommonFromList($list, $type);
		
		// Convert hash list to regular array
		if ($na) {
			$temp = array();
			foreach($na as $k => $v) {
				$temp[] = array("code" => $k, "label" => $v);				
			}
			$na = $temp;
		}
		
		return $na;
	}			
		
	/** Return NA and NA third axis in common
	 * @param $elements : array query list elements
	 * @return: array NA list
	 */
	public function getNaInCommon($elements) {
		
		$productsLabel = ProductModel::getProductsLabel(true);
		
		// Create a list with RAW and KPI elements only
		$list = array();
		foreach ($elements as $element) {						
			if ($element->type == 'RAW' || $element->type == 'KPI') {
				// Check if the productId is still valid (maybe it is a productId from a deleted slave product)
				if (isset($productsLabel[$element->productId])) {
					$item = array();
					$item['id_elem'] = $element->id;
					$item['class_object'] = $element->type == 'RAW'?'counter':'kpi';
					$item['id_product'] = $element->productId;			
					$list[] = $item;
				}
			}
		}
		
		$ret = array();
		
		// If there is a least one RAW/KPI
		if ($list) {
			// Get NA in common		
			$na = $this->getNaInCommonFromList($list, 'na');
			$na3 = $this->getNaInCommonFromList($list, 'na_axe3');		
			
			$ret['na'] = $na;			// NA
			$ret['na_axe3'] = $na3;		// NA third axis
		}
		
		return $ret;		
	}	
	
	/** Add na parent elements
	 * @param $query - the query object
	 * @return: $query - the query object updated with na parent elements
	 */	
	public function addNaParent($query) {			
				
		$list = array();
		$na = array();
		$hasGroupBy = false;
		
		// For each selected elements
		foreach ($query->select->data as $element) {			
			if ($element->type == 'RAW' || $element->type == 'KPI') {																						
				$item = array();
				$item['id_elem'] = $element->id;
				$item['class_object'] = $element->type == 'RAW'?'counter':'kpi';
				$item['id_product'] = $element->productId;
				$list[] = $item;				
			} elseif ($element->type == 'na'){
				$na[$element->id] = $element;				
			}
		}

		// If there is a least one RAW/KPI
		if ($list) {
			// Get NA in common
			$nasInCommon = $this->getNaInCommonFromList($list, 'na');		
			
			// For each NA in common	
			foreach($nasInCommon as $naInCommon) {
				// If the NA has not already bean added by the user, add it to the selected elements
				if (!isset($na[$naInCommon['code']])) {
					$newNa = new stdClass;
					$newNa->id = $naInCommon['code'];
					$newNa->name = $naInCommon['code'];
					$newNa->label = NaModel::getNaLabelFromId($naInCommon['code']);
					$newNa->type = 'na';
					$query->select->data[] = $newNa; 
				} else {
					break;
				}
			}
		}
		
		return $query;				
	}
	
	/** Return TA in common betwwen the selected element ($elementList)
	 * @param $elementList array
	 * @return array the TA in common
	 */
	public function getTaInCommon($elementList) {						
		// Get productId of elements
		$prodList = $this->array_pluck('productId', $elementList);				
		
		// Get productId used by elements
		$prodList = array_unique($prodList);				
		
		// Remove not active or deleted products
		$activeProdList = array();
		$productsLabel = ProductModel::getProductsLabel(true);
		foreach($prodList as $prod) {
			if (isset($productsLabel[$prod])) {
				$activeProdList[] = $prod;
			}
		}
		
		// Get TA in common
		$ta = getCommonTa($activeProdList);
		
		// Convert hash list to regular array
		if ($ta) {
			$temp = array();
			foreach($ta as $k => $v) {
				$temp[] = array("code" => $k, "label" => $v);				
			}
			$ta = $temp;
		}		
		
		return $ta;		
	}
	
	/* Plucks the value of a property from each item in the Array */
	private function array_pluck($key, $input) { 
	    if (is_array($key) || !is_array($input)) return array(); 
	    $array = array(); 
	    foreach($input as $v) { 
	        if($v->{$key}) {	        	
	        	$array[]=$v->{$key};
			} 
	    } 
	    return $array; 
	} 	
	
	/* Fill the productName and Name properties for all RAW and KPI
	 * Parameter:
	 *  - queryObject: the query 
	 */
	public function fillupElementData($queryObject) {
			
		// Get products label
		$productsLabel = ProductModel::getProductsLabel();
		
		// Select data
		foreach($queryObject->select->data as $element) {
			if ($element->type == 'RAW' || $element->type == 'KPI') {
				if (isset($productsLabel[$element->productId])) {
					$this->getElementData($element);										// fill element name
					$element->productName = $productsLabel[$element->productId];			// fill product label
				}
			}
		}
		
		// Filters data
		foreach($queryObject->filters->data as $element) {
			if ($element->type == 'RAW' || $element->type == 'KPI') {				
				if (isset($productsLabel[$element->productId])) {
					$this->getElementData($element);
					$element->productName = $productsLabel[$element->productId];			// fill product label
				}				
			}			
		}
	}
	
	/* Get missing data for an element (RAW/KPI)
	 * Parameter
	 *  - element: the element (RAW or KPI)
	 */
	public function getElementData($element) {
	    // database connection	
	    $database = DataBase::getConnection($element->productId);
		
	    if($database->getCnx()) {		    	    						
			if ($element->type == 'KPI') {
				// get the kpi
				$kpiMod = new KpiModel();
				$kpiData = $kpiMod->getById($element->id, $database);
				$element->name = addslashes($kpiData["kpi_name"]);													 				
			} else {
				// get the raw				
				$rawMod = new RawModel();
				$rawData = $rawMod->getById($element->id, $database);
				$element->name = addslashes($rawData["edw_field_name"]);												
			}	
		}
	}
	
	/* Set the id for a RAW or KPI
	 * Parameter
	 *  - element: the element (RAW or KPI)
	 * @return : true if Ok or false if the element id has not been found
	 */
	public function setElementId($element) {
	    // database connection
	    try {	
	    	$database = DataBase::getConnection($element->productId);
		} catch (Exception $e) {
			// Catch exception raised if the user import a multiproduct query on a single product query (the connection on the slave will raised an error)
			return false;
		}
		
	    if($database->getCnx()) {		    	    							
			if ($element->type == 'KPI') {
				// get the kpi
				$kpiMod = new KpiModel();				
				$kpiData = $kpiMod->getByName($element->name, $database);				
				if ($kpiData) {
					$element->id = addslashes($kpiData["id_ligne"]);
					return true;
				}													 				
			} else {
				// get the raw
				$rawMod = new RawModel();
				$rawData = $rawMod->getByName($element->name, $database);				
				if ($rawData) {					
					$element->id = addslashes($rawData["id_ligne"]);
					return true;
				}				
			}									
		}
		
		return false;
	}
				
	/** Insert a new export in the query export database table
	 * @param $query row - the query to export (from qbQueries table)
	 */
	public function createNewExport($query, $user_id) {
		$sql = "INSERT INTO qb_exports (name, json, user_id, state) VALUES ('".pg_escape_string($query['name'])."','".pg_escape_string($query['json'])."', '$user_id', 1)";						
		return $this->database->getOne($sql);	
	}
	
	/** Get user CSV export
	 * @param $user_id string the user id
	 * @param $state optional the state of exports to retreive
	 * @return array user CSV export list
	 */
	public function getCsvExportList($user_id, $state=null) {
		$filter = '';
		if (!is_null($state)) {
			$filter = 'AND state='.$state;
		}
		
		// get exports
		$sql = "SELECT id, name, file, state, to_char(start_date, 'DD/MM/YYYY HH:MI:SS') as start_date, to_char(end_date, 'DD/MM/YYYY HH:MI:SS') as end_date, error_message FROM qb_exports WHERE user_id ='$user_id' $filter ORDER BY id DESC";
		return $this->database->getAll($sql);
	}		
	
	/** Get export by id
	 * @param $id String export id
	 * @return the export row
	 */
	public function getCsvExportById($id) {		
		// get exports
		$sql = "SELECT * FROM qb_exports WHERE id=$id";		
		return $this->database->getRow($sql);
	}		
		
	/** Set export in error
	 * @param: $id export id to set
	 * @param: $errorMessage the export error message to set
	 */
	public function setExportInError($id, $errorMessage) {
		$sql = "UPDATE qb_exports set state=7, error_message='".pg_escape_string($errorMessage)."', end_date=CURRENT_TIMESTAMP WHERE id=$id";
		return $this->database->execute($sql);
	}
		
	/** Get running export for current user or all users if not user_id
	 * @param: $userId (optionnal)- string the user id
	 * @return array user running exports
	 */
	private function getRunningCsvExports($user_id=null) {
		/* States list:	1 WAITING - 2 IN PROGRESS -	3 GENERATED - 4 EXECUTED - 5 AVAILABLE - 6 CANCELED - 7 ERROR */
		if ($user_id) {				
			$sql = "SELECT id, process_pid FROM qb_exports WHERE user_id ='$user_id' AND state IN(2,3,4) ORDER BY ID";			
		} else {
			$sql = "SELECT id, process_pid FROM qb_exports WHERE state IN(2,3,4)";
		}
		
		return $this->database->getAll($sql);
	}	
	
	/** Get first export with state = 1 (waiting) for a user
	 * @param: $userId string the user id
	 * @return array the export
	 */
	private function getFirstCsvExport($user_id) {					
		$sql = "SELECT * FROM qb_exports WHERE user_id ='$user_id' AND state=1";				
		return $this->database->getRow($sql);
	}	
		
	/** Start csv export */
	private function setCsvExportInProgress($id, $pid) {
		/* States list:	1 WAITING - 2 IN PROGRESS -	3 GENERATED - 4 EXECUTED - 5 AVAILABLE - 6 CANCELED - 7 ERROR */
		$sql = "UPDATE qb_exports SET state=2, process_pid=$pid, start_date=CURRENT_TIMESTAMP WHERE id=$id";					
		return $this->database->execute($sql);
	} 
	
	/** Start csv export */
	private function setCsvExportCancel($id) {
		/* States list:	1 WAITING - 2 IN PROGRESS -	3 GENERATED - 4 EXECUTED - 5 AVAILABLE - 6 CANCELED - 7 ERROR */ 
		$sql = "UPDATE qb_exports SET state=6 WHERE id=$id";							
		return $this->database->execute($sql);
	} 
		
	/** Set csv export state */
	private function setCsvExportCompleted($id) {
		/* States list:	1 WAITING - 2 IN PROGRESS -	3 GENERATED - 4 EXECUTED - 5 AVAILABLE - 6 CANCELED - 7 ERROR */
		$sql = "UPDATE qb_exports SET state=5, end_date=CURRENT_TIMESTAMP WHERE id=$id";				
		return $this->database->execute($sql);
	} 
			
	/* Check if there is dead csv exports */
	public function cleanCsvExports() {

		// Get running exports
		$exports = $this->getRunningCsvExports();

		if ($exports) {
			// For each export
			foreach($exports as $export) {			
				// Check if the process is still alive	
				$isAlive = $this->psExists($export['process_pid']);

				// If the process doesn't exist anymore
				if(!$isAlive) {									
					// Set this export in error					
					$this->setExportInError($export['id'], 'Abnormal termination');
				}
			}
		}		
	}
	
	/** Test if a process exist
	 * @param $pid the process pid
	 * @return boolean true if the process exist
	 */
	private function psExists($pid) { 
		exec("ps ax | grep $pid 2>&1", $output);
		 
		while(list(,$row) = each($output)) { 
        	$row_array = explode(" ", trim($row));			 
			$check_pid = $row_array[0];			 
			if($pid == $check_pid) { 
				return true; 
			} 
        } 
        return false; 
    } 
   
	/** Start export */
	public function startCsvExport($user_id) {
		try {
			// In case of error don't skip next export
			$continue = false;
							
			// get running exports for the user_id
			$exports = $this->getRunningCsvExports($user_id);
			
			// if there is already running export ...do nothing (only one export by user at the same time)
			if(count($exports) != 0) {						
				return false;
			}

			// get the first export with state = 1 (Waiting)
			$firstExport = $this->getFirstCsvExport($user_id);			
			
			// If nothing to export ...
			if (!$firstExport) {				
				return false;
			}
						
			$exportId = $firstExport['id'];
			$exportName = $firstExport['name'];
			$query = json_decode($firstExport['json']);
		
			// Save the pid for this export in database
			$this->setCsvExportInProgress($exportId, getmypid());
			
			// The export has been set "In progress" so now in case of error we can skip to next export.. this one will not be re-export again
			$continue = true;
			
			// if there is no export to run ...exit
			if (!$exportId) {			
				return false;
			}
				
			// Query data model																			
			$dataMod = new QueryDataModel();
								
			// Get the SQL query
			if (isset($query->sql)) {
				// SQL type: get the user SQL query
				$sql = $query->sql->query;
				
				// To export in CSV the query should not contain carriage return (export is managed using pgsql command line). 
				// So SQL comment '--' have to be removed from the query
				// No problem with /* and */ comments
				$sql = preg_replace('`(-){2,}.*`', '', $sql);		// remove sql comments --		
					 
				// Get the productId where the query should be execute
				$productId = $query->sql->executeOn;
				
			} else {
				// Wizard type : compute SQL query				 								
				
				// Apply CSV export options
				$query = $this->applyCsvExportOptions($query);
								
				// Compute the SQL query
				$bean = $dataMod->createSqlQuery($query);								
						
				// If there was an error during computing the SQL request
				if ($bean->getErrorMessage()) {
					// Set the export in error and save the error message													
					$this->setExportInError($exportId, 'Error '.$bean->getErrorNumber().' : '.$bean->getErrorMessage());				
					// Skip to next export
					return true;
				}
							
				// Manage visible/hidden columns
				$this->hideHiddenColumns($bean);
				
				// Get SQL query
				$sql = $bean->getSql();
				
				// Get the productId where the query should be execute
				$productId = $bean->getProductIdWhereQueryIsExecute();
																	
			}
			
			// Get dbConnect queries if the query contains dblink keyword
			$dbConnect = '';				
			$dbConnects = $dataMod->computeDbLinkConnects($sql);

			if ($dbConnects) {
				// dblink found, this is a multi product query
				$dbConnect = implode("", $dbConnects);
			}
			
			// Add an id into the query (used to kill the process if the user cancel the export during the query execution)
			$sql = '/* qbExport_'.$exportId.' */ '.$sql;
							
			// Create CSV file
			$this->createCsvExportFile($exportId, $exportName, $productId, $sql, $bean, $dbConnect);
			 						
			// Check if the export has been canceled during the query execution
			$export = $this->getCsvExportById($exportId);
					
			/* States list:	1 WAITING - 2 IN PROGRESS -	3 GENERATED - 4 EXECUTED - 5 AVAILABLE - 6 CANCELED - 7 ERROR */				
			if (!$export || $export['state'] == 6) {
				// if the export has been canceled, remove it from database
				$this->deleteExportRow($exportId); 
			} else {		
				// Set export to completed state
				$this->setCsvExportCompleted($exportId);
			}
			
			return true; 
			
		} catch (Exception $e) {
			$this->setExportInError($exportId, $e->getMessage());
			
			// Skip to the next export if $continue=true or exit
			return $continue;		
		}
	}
	
	/** Apply CSV export options to the query object
	 * @param $query - the query object
	 * @return $query - the updated query
	 */
	private function applyCsvExportOptions($query) {
		
		// Check if the query has a group by option
		foreach ($query->select->data as $element) {
			// If there is a group by don't apply CSV export options
			if (isset($element->group) && $element->group === true && !$query->select->disableFunctions === true) {
				return $query;					
			}
		}
		
		$selectList = array();
		
		// Add NA parents if it has been selected in the export options	
		if ($query->exportOptions->parentNe === true) {								
			$query = $this->addNaParent($query);
		}
		
		// Manage NA label (display code, label or both)
		foreach ($query->select->data as $element) {
			
			// For NA set if the code, label or both should be displayed
			if ($element->type == 'na' || $element->type == 'na_axe3') {
				if ($query->exportOptions->ne == 'both') {
					
					// Add label column
					$el = clone $element;					
					$el->valueType = 'label';											
					$selectList[] = $el;
					
					// Add code column
					$element->valueType = 'code';					
					$selectList[] = $element;
					
				} else {
					// Add code or label
					$element->valueType = $query->exportOptions->ne;
					$selectList[] = $element;
				}
			} else {
				$selectList[] = $element;
			}
		}
		
		$query->select->data = $selectList;
		
		return $query;
	}
	
	/** Export CSV : Manage 'visible' propertie of each column
	 * @param $bean object - query object
	 */
	public function hideHiddenColumns($bean) {
		// Manage visible columns
		$areAllColumnVisible = true;
		$selectList = array();
		foreach ($bean->getColumnHeader() as $element) {				
			if ($element->visible === false) {
				$areAllColumnVisible = false;					
			} else {					
				// If the element is visible add-it to the select list
				$selectList[] = $element->sqlColumnName; 
			}
		}
		
		// If not all column are visible									
		if (!$areAllColumnVisible) {
			$sql = $bean->getSql();
			$sql = 'SELECT '.implode(', ', $selectList).' FROM ('.$sql.') as visibleColumn';
			$bean->setSql($sql);
		}
	}
	
	/* Delete an export
	 * @param $id String export id */
	public function deleteExport($id) {

		// Get the export to delete
		$export = $this->getCsvExportById($id);

		if ($export) {
			
			// Get export state
			$state = $export['state'];
			
			// If the export is running
			/* States list:	1 WAITING - 2 IN PROGRESS -	3 GENERATED - 4 EXECUTED - 5 AVAILABLE - 6 CANCELED - 7 ERROR */ 
			if ($state==2 || $state==3 || $state==4) {
				
				// Check if the export process is still alive	
				if ($this->psExists($export['process_pid'])){
					
					// If the process is alive set the export to "canceled" state 
					$this->setCsvExportCancel($id);
					
					// Kill the SQL query (if it is running) for the current export
					$this->killQueryExport($id);
										
				}
			}	
										
			// Remove export from database
			$this->deleteExportRow($id);						
		}
	}	

	/* Kill the SQL query for an export ID 
	 * @param id the export id
	 */
	public function killQueryExport($id) {
		// To kill a process we need to be logged has root so we delegate this task to the 'SQL/kill_qpid.php'.
		// This script is execute by a crontab and checks he queries_to_kill table.
		//
		// To get the query PID, this script checks the queries beeing execute on the server and try to find the one
		// containing the export id (all exports queries contains a comment with the export id)
		// Sample export query : 	/* qbExport_1241 */ SELECT ...
		// To find the query PID, this query is used : SELECT procpid FROM pg_stat_activity WHERE current_query LIKE '%qbExport_1421%'
		// This query will returns 2 results:
		//		this query itself because it contains the string 'qbExport_1421'
		//		the export query because it contains the string 'qbExport_1421' in comment (the one we want to kill)
		// 
		// To avoid getting two results we used this query instead:
		// 		SELECT procpid FROM pg_stat_activity WHERE current_query LIKE '%qb'||'Export_1421%'
		// This is the same query except this one doesn't contains que string 'qbExport_1421' thus it will returns only one result, the query PID to kill ! 
		
		$sql = "INSERT INTO queries_to_kill (search_string) VALUES('qbEx\'\|\|\'port_$id')";				
		return $this->database->execute($sql);
	}
	
	/* Kill a SQL query used by preview tab (table or graph SQL preview) 
	 * @param id the export id
	 */
	public function killPreviewQuery($id) {
		// See killQueryExport function for details	
		$sql = "INSERT INTO queries_to_kill (search_string) VALUES('qbPre\'\|\|\'view_$id')";				
		return $this->database->execute($sql);
	}	
			
	/** Create CSV export file
	 * @param string $exportId - the export id
	 * @param string $exportName - the query name
	 * @param string $productId - the product id where the query should be executed
	 * @param string $sql - the SQL query
	 * @param string $bean - the query bean object or null for a SQL type query
	 * @param string $dbConnect SQL dbConnect queries
	 */
	private function createCsvExportFile($exportId, $exportName, $productId, $sql, $bean, $dbConnect) {
	
		// Temporary directory for queries exports
		$tempPath = REP_PHYSIQUE_NIVEAU_0.self::CSV_EXPORT_TMP_DIR;
		if (!is_dir($tempPath)) {
			// If directory does'nt exist ...create it !				
			mkdir($tempPath, 0777);
			// Change permission
			exec('chmod 777 '.$tempPath);
		}
																			
		// Create a new directory in the tempPath for this export
		$exportPath = $tempPath.'/'.getmicrotime();
		mkdir($exportPath, 0777);			
		
		// Change file permission
		exec('chmod 777 '.$exportPath);
				
		// Remove special char from file name
		$exportFileName = getCommandSafeFileName($exportName);			 			 		 		
		$exportFileName = str_replace('/', '_', $exportFileName);
		
		// Get the product
		$product = ProductModel::getProductById($productId);
		
		// Compute full csv export file path
		$fullPath = $exportPath.'/'.$exportFileName.'.csv';
		
		// Remove special char from query (get a one line query to use it as command line parameter)
		$sql = str_replace("\n"," ", $sql);
		$sql = str_replace("\t"," ", $sql);
														
		// Create export file
		$handle = fopen($fullPath, "w");
		fclose($handle);				
		
		// Change file permission
		exec('chmod 777 '.$fullPath);
		
		// For SQL type queries export HEADER from the query, for wizard don't export header, column title will be computed later using the export options
		$header = $bean?'':' HEADER';			

		$sql = str_replace('"', '\"', $sql);		

		// Export data from postgres (use STDOUT because read only user has no right to write file directly on the server)
		$command = '"'.$dbConnect.' COPY ('.$sql.') TO STDOUT DELIMITER AS \';\' CSV '.$header.'" >'.$fullPath;
				
		$cmd = sprintf(
			'env PGPASSWORD=read_only_user '.PSQL_DIR.'/psql -U read_only_user %s -h %s -c %s',
			$product['sdp_db_name'],
			$product['sdp_ip_address'],
			$command			
		);	
		
		// Update export CSV filepath in the qb_exports table (this is done before the file creation start because if the user wants to abort export before the end of the process, we can get the file path here)
		$link = str_replace(REP_PHYSIQUE_NIVEAU_0, NIVEAU_0, $exportPath);
		$this->updateCsvExportFilePath($exportId, $link.'/'.$exportFileName.'.tar.gz');
		
		// echo "\n".$cmd."\n";
		
		// Execute psql export command line (create CSV file)		
		exec($cmd);
																																					
		// If an export file has been created => create the gzip archive
		if( file_exists($fullPath)) {
			
			// Empty file
			if (filesize($fullPath) === 0) {
				throw new Exception('No data to export.');
			}
				
			// Create column titles (for wizard mode only)		
			if ($bean) {
				$columnTitles = $this->getCsvExportColumnTitles($bean);				
				// Insert column titles						 																			
				exec("sed -i '1i$columnTitles' $fullPath");
			}
			// 23/01/2014 GFS - Bug 39244 - [SUP][TA Gateway][Query Builder v2][AVP 40714][Zain Kuweit]: Export are not compressed							
			$cmd = 'cd '.$exportPath.' && tar -zcf '.$exportFileName.'.tar.gz *';
			exec($cmd);
			
		} else {
			throw new Exception('Error during creating export file.');
		} 							
				
	}	

	/** Compute column titles for CSV export 
	 * @param $bean - query object
	 * @return string the export title CSV string 
	 */
	private function getCsvExportColumnTitles($bean) {
		$titles = array();
		
		// Get export options
		$exportOptions = $bean->getExportOptions();
		
		// For each column
		foreach ($bean->getColumnHeader() as $element) {
			// Check if column is visible
			if ($element->visible) {
																				
				// Get user preferences from export options: check if we should export code or label for RAW/KPI ?
				if ($exportOptions->el == 'name' && ($element->type == 'RAW' || $element->type == 'KPI')) {
					// Export code (only for RAW/KPI)
					$titles[] = str_replace(';','',$element->name);
				} else {
					// Export label
					$titles[] = str_replace(';','',$element->label);
				}
			}
		}
		
		// Get the titles with CSV format
		$csvTitles = implode(';', $titles);
				
		// Remove special chars
		$csvTitles = str_replace("'", "", $csvTitles);
		
		return $csvTitles;
						 		
	}
	
	/* Update export CSV file path 
	 * @param id the export id
	 * @param filePath the CSV export file path*/
	public function updateCsvExportFilePath($id, $filePath) {
		$sql = "UPDATE qb_exports SET file = '$filePath' WHERE id=$id";				
		return $this->database->execute($sql);
	}
	
	/* Check if there at least 20% of free disk space (customizable in global parameters)
	 * @return true/false
	 */
	public function checkDiskSpaceForCsvExport() {
		
		// Compute free disk space in percent
		$freeSpace = disk_free_space('/home');
		$totalSpace = disk_total_space('/home');
		$percent = $freeSpace ? round($freeSpace / $totalSpace, 2) * 100 : 0;
				
		return ($percent < get_sys_global_parameters('query_builder_minium_free_disk_space', 20))?false:true;
	}
	
	/* Parse query object, update product id
	* @param $type wizard or sql  
	* @param $query query object
	* @param $newProductId the new product id 
	*/
	static public function parseQueryForMaster($type, $query, $newProductId) {
		
		if ($type == 'wizard') {
		// For wizard query
		
			// For each selected element
			foreach($query->select->data as $item) {							
				if ($item->productId != null) {
					$item->productId = $newProductId;
				}
			}
			
			// For each filter element
			foreach($query->filters->data as $item) {				
				if ($item->productId != null) {
					$item->productId = $newProductId;
				}
			}
			
			// For each grid parameter
			foreach($query->graphParameters->gridParameters as $item) {
					if ($item->productId != null) {
					$item->productId = $newProductId;
				}
			}
			
		} else {
		// For SQL query
			$query->sql->executeOn = $newProductId;
		}
		
		return $query;
	}
	
	/* Copy queries from slave to master product */
	static public function deployQueries() 
        {

		$database = Database::getConnection(0);
		$masterId = ProductModel::getIdMaster();

		// Get all products
		$allProducts = ProductModel::getProducts();

		// For each product (except master product)
                foreach($allProducts as $product) 
                {
        	
                    // Skip master product
                    if($product['sdp_id'] == $masterId) { continue; }

			// Connect to the (slave) product
                        $slaveDb = Database::getConnection($product['sdp_id']);
					
			// Check if this product has a qb_queries table (CB5.2 or greater)
                        // 23/04/2012 BBX
                        // On utilise la méthode de DatabaseConnection pour cela !!
			//$sql = "SELECT tablename FROM pg_tables where schemaname = 'public' and tablename='qb_queries'";
			//if (!$slaveDb->getOne($sql)) {
                        if(!$slaveDb->doesTableExist('qb_queries')) {
				// If there is no qb_queries table, nothing to do
				continue;
			}
			
			// Get all queries
			$sql = "SELECT * FROM qb_queries";
			$queries = $slaveDb->getAll($sql);	
			
			// For each queries
                        foreach($queries as $query) {
				// Get query object
				$json = json_decode($query['json']);
				
				// Update productId of each raw/kpi with the new slave product number
				$json = self::parseQueryForMaster($query['type'], $json, $product['sdp_id']);
				
				// Get a JSON string
				$json_string = json_encode($json);					
				
				// Check if a query already exists with this name
				$name = $query['name'];
				
				$sql = "SELECT * FROM qb_queries WHERE name ='".pg_escape_string($name)."' AND user_id='".$query['user_id']."'";
				$row = $database->getRow($sql);						
				if ($row) {										
					// If this is the same query ...skip to the next
					if ($row['json'] == $json_string) {
						continue;
					} else {						
						// if a different query already exists with this name, add a time stamp to the query name						
						$name = $name.getmicrotime();
					}
				}
					
  				// Add the query to the master
				$sql = "INSERT INTO qb_queries (name, user_id, type, shared, json) VALUES ('".pg_escape_string($name)."', '".$query['user_id']."', '".$query['type']."', '".$query['shared']."', '".pg_escape_string($json_string)."')";
				$database->execute($sql);
			}
		}
	}
	
	/* Get KPI HTML List
	* @see getRawKpiHtmlList method for usage
	*/
	public function getKpiHtmlList($searchOptions='') {			
		return $this->getRawKpiHtmlList('kpi', $searchOptions);
	}

	/* Get RAW HTML List
	* @see getRawKpiHtmlList method for usage
	*/	
	public function getRawHtmlList($searchOptions='') {			
		return $this->getRawKpiHtmlList('raw', $searchOptions);
	}
		
	/* Get KPI JSON List
	* @see getRawKpiList method for usage
	*/
	public function getKpiList($searchOptions='') {			
		return $this->getRawKpiList('kpi', $searchOptions);
	}

	/* Get RAW JSON List
	* @see getRawKpiList method for usage
	*/	
	public function getRawList($searchOptions='') {			
		return $this->getRawKpiList('raw', $searchOptions);
	}
			
	/* Get RAW/KPI HML list
	* @param $type string : 'raw' or 'kpi'
	* @param $searchOptions (optional) object filter options (JSON)
	* 		 sample : {"text":"call","products":[{"id":"1",{"id":"2"}]}								=> search KPI for product id 1 and 2 with the work 'call' in the label, code or description
	*				  {"text":"","products":[{"id":"1","families":["10","11","12"]},{"id":"2"}]} 	=> search all KPI for product 2 and KPI for product 1 only belongs to family 10 or 11 or 12.
	* @return HTML list
	*/	
	public function getRawKpiHtmlList($type, $searchOptions='') {				
		
		// Get RAWs
		$qbRawKpi = new QbRawKpi();
		if ($type == 'raw') {
			$list = $qbRawKpi->getRAWs($searchOptions);
		} else {
			$list = $qbRawKpi->getKPIs($searchOptions);
		}
		
		// If no result display an error message
		if (!isset($list[0])) {
			return '<div class="qbNoElementFound"><span class="icoCancel qbErrorMessage">No element found.</span><br><span>Please refine your search criteria.</span></div>';
		}
		
		// Display list
		//$html = '<table class="qbElementList" width="100%"><tr><td><b>Label</b></td><td width="30%"><b>Product</b></td><td width="10"></td></tr></table>';         		
		$html= '<div class="qbElementContainer"><table class="qbElementList" width="100%"><tr><td></td><td width="40%"></td></tr>';
			      
		foreach ($list as $element){	
			$html.= '<tr id="'.$element['id'].'" data-product="'.$element['sdp_id'].'"><td>'.$element['object_libelle'].'</td><td class="qbElementProduct">'.preg_replace('/[^a-zA-Z0-9]/', '', $element['sdp_label']).'</td><tr>';		
		}
		
//		$html.= '<tr><td>&nbsp;</td><td class="qbElementProduct">&nbsp;</td><tr>';
		$html.= '</table></div>';
		
		return $html;
	}
	
	/* Get RAW/KPI JSON list
	* @param $type string : 'raw' or 'kpi'
	* @param $searchOptions (optional) object filter options (JSON)
	* 		 sample : {"text":"call","products":[{"id":"1",{"id":"2"}]}								=> search KPI for product id 1 and 2 with the work 'call' in the label, code or description
	*				  {"text":"","products":[{"id":"1","families":["10","11","12"]},{"id":"2"}]} 	=> search all KPI for product 2 and KPI for product 1 only belongs to family 10 or 11 or 12.
	* @return JSON list
	*/	
	public function getRawKpiList($type, $searchOptions='') {
		$json = '';
		
		// Get RAWs
		$qbRawKpi = new QbRawKpi();
		if ($type == 'raw') {
			$list = $qbRawKpi->getRAWs($searchOptions);
		} else {
			$list = $qbRawKpi->getKPIs($searchOptions);
		}
				
		// If no result display an error message
		if (!isset($list[0])) {
			return '[]';
		}
					      
		foreach ($list as $element){	
			$json.= '{"id": "'.$element['id'].'", "productId": "'.$element['sdp_id'].'", "family": "'.$element['family'].'", "label": "'.$element['object_libelle'].'", "productLabel": "'.preg_replace('/[^a-zA-Z0-9]/', '', $element['sdp_label']).'"},';		
		}
		
		$json = '['.rtrim($json, ",").']';
						
		return $json;
	}
	
	/** Get NE for a NA (with filter)
	* @param $param string JSON 
	* 		  format : {"na": "cell", "text":"CELL_151"}
	* 		  	na : eor_obj_type
	*          labelFilter (optional) : string to search in the NE label
	* @param $noLimit : if true no limit, by default null -> limited to 5000 NE
	* @return Array NE list 
	*/
	public function getNe($param, $noLimit = null) {		
		
		// Get label filter if needed
		$labelFilter = isset($param->text)&&$param->text?$param->text: null;
		
		// Get all products
		$allProducts = ProductModel::getActiveProducts();
		
		if (isset($noLimit) && $noLimit) {
			$limit = 5000;
		} else {
			$limit = null;
		}
		
		// Get NE list (limit to 5000 result max.)
		return NeModel::getFilteredNeFromProducts($param->na, $allProducts, $limit, $labelFilter); 
	}
	
	/* Get NA and TA in common for a RAW/KPI list
	* @param $elements raw/kpi element list (json), sample : [{"id": "raws.0016.10.64.99.00002", "type":"RAW", "productId":"1"},{"id": "raws.0016.10.64.99.00008", "type":"RAW", "productId":"1"}] 
	* @return json object (ta & na in common)
	*/
	public function getAggInCommon($elements) {																	  			      		  				 			
		// database connection
	    $database = DataBase::getConnection();
						
	    if($database->getCnx()) {	    
			// create querybuilder data model
			$qbMod = new QbModel($database);
		
			// Get network aggregation in common
			$na = $qbMod->getNaInCommon($elements);	
			$ta = $qbMod->getTaInCommon($elements);
			
			$json = new stdClass();
			$json->aggregations = array();
			$json->aggregations['network'] = $na;
			$json->aggregations['time'] = $ta;
										
			// close db connection
			$database->close();
		} else {
			$this->createDatabaseConnectionException();
		}
		
		// delete database object	
		unset($database);			   	
				
		// return the query (json string)
		return json_encode($json);
	}
	
	/* Get product and families	 
	 * @return string JSON object products families
	 */	
	public function getProductsFamilies() {					
		// get all products
		$allProducts = ProductModel::getActiveProducts();
				
		$ret = '[';
		
		// for each product
		foreach ($allProducts as $prod) {
			$ret.= '{"id":"'.$prod['sdp_id'].'", "label":"'.addslashes($prod['sdp_label']).'","families":[';
			// For each families in this product
			$fam = '';
			foreach( FamilyModel::getAllFamilies($prod['sdp_id']) as $family => $values ) {				
			    $fam .= '{"id":"'.$values['code'].'","label":"'.addslashes($values['label']).'"},';
			}
			$fam = rtrim($fam, ',');				
			$ret.=$fam.']},';
		}
		$ret = rtrim($ret, ',');
		$ret.=']';
		
		return $ret;
	}		
}
?>