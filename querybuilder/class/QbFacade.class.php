<?php
/**
 * Query builder facade used by the Query builder GUI
 */

include_once REP_PHYSIQUE_NIVEAU_0.'php/edw_function.php';
 
class QbFacade {
	
	/* Error type constants */
	const ERROR_QB = 'qb';
	const ERROR_SYSTEM = 'system';
	const QUERIES_EXPORT_TMP_DIR = 'upload/querybuilder_queries_export'; // temporary queries export directory
	const QUERIES_IMPORT_TMP_DIR = 'upload/querybuilder_queries_import'; // temporary queries import directory
	
	/* Constructor */
	public function __construct() {		
		// PHP errors management function
		$this->setErrorHandler();			
	}
		
	/* Set custom error handler */
	private function setErrorHandler() {
		// PHP errors management function
		set_error_handler(array($this,"exception_error_handler"));
	}
	
	/* ---------------------------------------------- */
	/*      		  Private methods			  	  */
	/* ---------------------------------------------- */
		
	/** Throw a custom query builder exception
	 * @param number string error number
	 * @param string $message the error message	 
	 */
	private function throwQbException($number, $message) {		
		$qbException = new Exception($message);
		$qbException->type = self::ERROR_QB;
		$qbException->number = $number;
		throw($qbException);			  		
	}	
			
	/** Throw a user exception
	 * $message the message to display in the GUI
	 */
	private function throwUserException($message) {							
		$e = new Exception($message);
		$e->type = 'user';
		throw $e;
	}
	
	/** Format exception in a JSON string
	 * @param e exception - the current exception
	 * @return string JSON error string
	 */ 
	private function getJSONErrorMessage($e) {			
		// set default type to ERROR_SYSTEM		
		$type = isset($e->type)?$e->type:self::ERROR_SYSTEM;
		$message = addslashes($e->getMessage());								
		$number = isset($e->number)?$e->number:-1;
		return "{'error': {'type': '$type', 'number': '$number', 'message': '$message'}}"; 
	}
	
	/** Check if supplied parameters are set in the HTTP GET parameters
	 * @param $paramList array of string: list of parameter to check
	 * @throw qbException if a paremeter is not found
	 */	
	private function checkGetParameters($paramList) {		
		// for each parameter
		foreach ($paramList as $parameter) {				
			// check if parameter is defined in $_GET
	    	if(!isset($_GET[$parameter])) {
	    		$this->createParametersException("GET", $parameter);			    		
			}
		}
	}	
	
	/** Check if supplied parameters are set in the HTTP POST parameters
	 * @param $paramList array of string: list of parameter to check
	 * @throw qbException if a paremeter is not found
	 */	
	private function checkPostParameters($paramList) {		
		// for each parameter
		foreach ($paramList as $parameter) {				
			// check if parameter is defined in $_POST
	    	if(!isset($_POST[$parameter])) {
	    		$this->createParametersException("POST", $parameter);			    		
			}
		}
	}	
	
	/** Throw a QB exception if a mandatory parameter is missing
	 * @param $type string: 'GET' or 'POST'
	 * @param $parameter string: the missing parameter
	 */
	private function createParametersException($type, $parameter) {
		// throw QbException
	    $this->throwQbException('0', "QbFacade - '$parameter' ($type) parameter is mandatory.");
	}		
	
	/** Throw an exception if there is not database connection */
	private function createDatabaseConnectionException() {
		// throw QbException
		$qbException = new Exception('Database connection error.');
		$qbException->userErrNumber = '9';		
		throw($qbException);
	}
	
	/** Throw a QB exception if the user don't have right to perform an action */	 
	private function noRightException() {
		// throw QbException
	    $this->throwQbException('2', 'QbFacade - You are not authorized to perform this action');
	}			
	
	/* ---------------------------------------------- */
	/*    			    Public methods				  */
	/* ---------------------------------------------- */
	
	/** function to manage PHP errors
	 *  @param $errno string: error number
	 *  @param $errstr string: error message
	 *  @param $errfile string: file where occured the error
	 *  @param $errline integer: error line 
	 */
	public function exception_error_handler($errno, $errstr, $errfile, $errline ) {
 		$e = new Exception("$errstr - Error number : $errno - File : $errfile - Line : $errline");
		$e->number = $errno;
		throw($e);
	}	
	
	 /* Get network aggregation in common
	 * @param string $query (from $_POST)
	 * @return string JSON object containing the aggregations in common
	 */
    public function getQueryAgg() {    	
		try {
			// check mandatory parameters
			$this->checkPostParameters(array('query'));																			  			      		  				 			
						
			// database connection
		    $database = DataBase::getConnection();
			
			// get a query object from query json string
			$queryObject = json_decode(stripslashes($_POST['query']));
				
		    if($database->getCnx()) {	    
				// create querybuilder data model
				$qbMod = new QbModel($database);
			
				// Get network aggregation in common
				$elements = array_merge($queryObject->select->data, $queryObject->filters->data);
				$na = $qbMod->getNaInCommon($elements);	
				$ta = $qbMod->getTaInCommon($elements);
				
				$queryObject = new stdClass();
				$queryObject->aggregations = array();
				$queryObject->aggregations['network'] = $na;
				$queryObject->aggregations['time'] = $ta;
				
				// return the query (json string) 					
				echo(json_encode($queryObject));		
				
				// close db connection
				$database->close();
			} else {
				$this->createDatabaseConnectionException();
			}
			
			// delete database object	
			unset($database);			   	
					
		// Error management			
		} catch (Exception $e) {			
			echo $this->getJSONErrorMessage($e);			
		}
	}
	
	/* Get query from a CSV export
	 * @param string $exportId query's id (from $_GET)
	 * @return string JSON object containing the query data
	 */	
	public function getExportedQuery() {
		try {
			// check mandatory parameters
			$this->checkGetParameters(array('id'));																			  			      		  				 			
						
			// database connection
		    $database = DataBase::getConnection();
			
		    if($database->getCnx()) {	    
				// create querybuilder data model
				$qbMod = new QbModel($database);
				
				// Get csv export
				$export = $qbMod->getCsvExportById($_GET['id']);
				
				// if no export was found for this id -> throw exception
				if (!$export) {
					$this->throwQbException('5', "No query was found for this export id: '".$_GET['id']."'");
				}				
																								
				// get a query object from query json string
				$queryObject = json_decode($export['json']);
						
							
				$queryObject->system = array();				
				$queryObject->system['hasRight'] = true;				
				$queryObject->system['hasChanged'] = true;
				
				// Set id
				$queryObject->general = array();
				$queryObject->general['id'] = '';
				$queryObject->general['name'] = '';
				
				if (isset($queryObject->sql)) {
					$queryObject->general['type'] = 'sql';
				} else {
					$queryObject->general['type'] = 'wizard';
				}
				
				$na = false;
				$ta = false;
				
				if ($queryObject->general['type']!= 'sql') {
					$elements = array();
					if ($queryObject->select->data) {
						$elements = $queryObject->select->data; 
					}
				
					if ($queryObject->filters->data) {
						$elements = array_merge($elements, $queryObject->filters->data);
					}
					
					// Get network aggregation in common
					if ($elements && $elements[0]) {
						$na = $qbMod->getNaInCommon($elements);
						$ta = $qbMod->getTaInCommon($elements);					
					}				
					
					$queryObject->aggregations = array();
					$queryObject->aggregations['network'] = $na;
					$queryObject->aggregations['time'] = $ta;
					
					// Get ProductName and Name properties for each RAW/KPI
					$qbMod->fillupElementData($queryObject);
				}
					
				// return the query (json string) 					
				echo(json_encode($queryObject));			
					
				// close db connection
				$database->close();
			} else {
				$this->createDatabaseConnectionException();
			}
			
			// delete database object	
			unset($database);			   	
					
		// Error management			
		} catch (Exception $e) {			
			echo $this->getJSONErrorMessage($e);			
		}		
	}			
	
	/* Get query by id
	 * @param string $id query's id (from $_GET)
	 * @return string JSON object containing the query data
	 */
    public function getQuery() {    	
		try {
			// check mandatory parameters
			$this->checkGetParameters(array('id'));																			  			      		  				 			
						
			// database connection
		    $database = DataBase::getConnection();
			
		    if($database->getCnx()) {	    
				// create querybuilder data model
				$qbMod = new QbModel($database);
				
				// get the query from its id
				$query = $qbMod->getQueryById($_GET['id']);
					
				// if no query was found for this id -> throw exception
				if (!$query) {
					$this->throwQbException('3', "No query was found for this id: '".$_GET['id']."'");
				}									
				
				// get a query object from query json string
				$queryObject = json_decode($query['json']);					
				$queryObject->system = array();
				
				// if user is not the owner of this query -> hasRight = false				
				if($_SESSION['id_user'] != $query['user_id']){										
					$queryObject->system['hasRight'] = false;					
				} else {
					$queryObject->system['hasRight'] = true;
				}

				$queryObject->system['hasChanged'] = false;
				
				// Set id
				$queryObject->general = array();
				$queryObject->general['id'] = $_GET['id'];
				$queryObject->general['name'] = $query['name'];
				$queryObject->general['type'] = $query['type'];
				
				$na = false;
				$ta = false;
				
				if ($query['type'] != 'sql') {
					$elements = array();
					if ($queryObject->select->data) {
						$elements = $queryObject->select->data; 
					}
				
					if ($queryObject->filters->data) {
						$elements = array_merge($elements, $queryObject->filters->data);
					}
					
					// Get network aggregation in common
					if ($elements && $elements[0]) {
						$na = $qbMod->getNaInCommon($elements);
								
						$ta = $qbMod->getTaInCommon($elements);					
					}				

					$queryObject->aggregations = array();
					$queryObject->aggregations['network'] = $na;
					$queryObject->aggregations['time'] = $ta;
					
					// Get ProductName and Name properties for each RAW/KPI
					$qbMod->fillupElementData($queryObject);
				}
									
				// return the query (json string) 					
				echo(json_encode($queryObject));			
					
				// close db connection
				$database->close();
			} else {
				$this->createDatabaseConnectionException();
			}
			
			// delete database object	
			unset($database);			   	
					
		// Error management			
		} catch (Exception $e) {			
			echo $this->getJSONErrorMessage($e);			
		}
    }	
	
	/* Get user or public queries
	 * Parameters
	 *  from : 'user' or 'public' (receive from $_GET) 
	 */
	public function getQueries() {
		try {
				
		// check mandatory parameters
		$this->checkGetParameters(array('from'));
																			
	    // database connection
	    $database = DataBase::getConnection();
		
	    if($database->getCnx()) {	    
			// Create querybuilder data model
			$qbMod = new QbModel($database);
			
			if ($_GET['from'] == 'user') {
				// Get user queries
				$queries = $qbMod->getUserQueries($_SESSION['id_user']);	
			} else {
				// Get public queries
				$queries = $qbMod->getSharedQueries($_SESSION['id_user']);				
			}
								    			
			// close db connection
			$database->close();
			
			// JSON text response
			$ret = '';
						
			// for each query
			foreach ($queries as $query) {
				$name = $query['name'];
				
				// Add "(SQL)" in the name for SQL type queries
				if ($query['type']=='sql') {
					$name.=' (SQL)';
				}
				
				// Shared value true/false
				$shared = ($query['shared']=='t')?'true':'false';
				
				// Set if user has right (if this query is owned by the current user)
				$hasRight = ($_SESSION['id_user'] == $query['user_id'])?"true":"false";
				
				if ($_GET['from'] == 'user') {
					$ret.= '{"checked": false,"queryId":"'.$query['id'].'","text": "'.$name.'","shared":"'.$shared.'", "leaf":true},';
				} else {
					$ret.= '{"checked": false,"queryId":"'.$query['id'].'","text": "'.$name.'","shared":"'.$shared.'", "hasRight":"'.$hasRight.'", "user":"'.$query['login'].'", "leaf":true},';
				}				
			}
			
			$ret = rtrim($ret, ',');
			$ret='['.$ret.']';
						
			echo $ret;
		} else {
			$this->createDatabaseConnectionException();
		}
		
		// delete database object	
		unset($database);
					
		// Error management			
		} catch (Exception $e) {			
			echo $this->getJSONErrorMessage($e);			
		}		
	}
	
	/* Save the query in the database)
	 * @param string $query - JSON object (from $_POST)
	 * @param string $overwrite - if true overwrite existing query(from $_POST)
	 * @param string $saveas - if true, this is a saveas action (from $_POST) 
	 * @return string JSON object containing the query data
	 */
    public function setQuery() {    	
		try {																  			      		  				 
			// check mandatory parameters
			$this->checkPostParameters(array('query'));
											
			// Create an object from the query parameter					 	
			$query = json_decode(stripslashes($_POST['query']));
			
			$id = $query->general->id;
			$name = $query->general->name;
			$type = $query->general->type;
			
			$query->general = null;
			unset($query->general);
			
			$ret = '';
													
			// database connection		
			$database = DataBase::getConnection();
		    if($database->getCnx()) {
		    	// Create querybuilder data model
				$qbMod = new QbModel($database);
				
				// if the query has already an id update the query and this is a save action
				if ($id && $_POST['saveas'] == 'false') {
					
					// Check if the user has right to update this query
					if (!$qbMod->isOwner($id, $_SESSION['id_user'])) {
						$this->noRightException();
					}
										
					$qbMod->updateQuery($id, $type, json_encode($query));
					
					// return the query id
					$ret = $id;
						
				// if the query has no id or this is as saveas action -> insert a new query	
				} else {															
					// check if a query already exist with this name for this user
					$existingQuery = $qbMod->getQueryByName($name, $_SESSION['id_user']);
															
					// if a query already exist and is not the current one
					if ($existingQuery['id'] != '') {						
									
						// get overwrite parameter
						$overwrite = (isset($_POST['overwrite']) && $_POST['overwrite'] == 'true')?true:false; 
						
						// if the already query is the current query -> overwrite it
						if ($existingQuery['id'] == $id) {
							$overwrite = true;
						}
						
						if ($overwrite) {
							// overwrite existing query
							// insert the new query and returns its id
							$qbMod->updateQuery($existingQuery['id'], $type, json_encode($query));
							$ret = $existingQuery['id'];
						} else {
							// if overwrite is not set to true -> throw exception, this will display a message to the user (this query already exist ...)
							$this->throwQbException('1', 'QbFacade - The query \''.$name.'\' already exist in the database');							
						}
					} else {
						// insert the new query and returns its id									
						$ret = $qbMod->insertQuery(addslashes($name), $type, $_SESSION['id_user'], json_encode($query));						
					}																												
				}
																
				// return the query id
				echo ('{"id":"'.$ret.'"}');
										
				// close db connection
				$database->close();
		    } else {
		    	$this->createDatabaseConnectionException();
			}
			
			unset($database);
			
		// Error management			
		} catch (Exception $e) {			
			echo $this->getJSONErrorMessage($e);			
		}
    }	
	
	/* Set shared value for a query
	 * @param string $id : query id (from $_GET)
	 * @param string $shared: new shared value for this query (from $_GET)
	 */			
	public function setSharedValue() {		
		try {
			// check mandatory parameters
			$this->checkGetParameters(array('id', 'shared'));
																																
			// database connection		
			$database = DataBase::getConnection();
		    if($database->getCnx()) {		    		
		    	
		     	// Create querybuilder data model
				$qbMod = new QbModel($database);
				
				// Check if the user has the right to update this query
				if (!$qbMod->isOwner($_GET['id'], $_SESSION['id_user'])) {
					$this->noRightException();
				}
									
				// update query
				$qbMod->setSharedAttribute($_GET['id'], $_GET['shared']);
					    										
				// close db connection
				$database->close();
		    } else {
		    	$this->createDatabaseConnectionException();
			}
			unset($database);				
		
		// Error management			
		} catch (Exception $e) {			
			echo $this->getJSONErrorMessage($e);			
		}	
	}
	
	/* Delete the query 
	 * @param string $id : query id (from $_GET)	 
	 */			
	public function deleteQuery() {		
		try {
			// check mandatory parameters
			$this->checkGetParameters(array('id'));
																		
			// database connection		
			$database = DataBase::getConnection();
		    if($database->getCnx()) {
		    	// Create querybuilder data model
				$qbMod = new QbModel($database);

				// Check if the user has right to delete this query
				if (!$qbMod->isOwner($_GET['id'], $_SESSION['id_user'])) {
					$this->noRightException();
				}
				
				// Delete the query				
		    	$qbMod->deleteQuery($_GET['id']);
										
				// close db connection
				$database->close();
		    } else {
		    	$this->createDatabaseConnectionException();
			}
			unset($database);				
		
		// Error management			
		} catch (Exception $e) {			
			echo $this->getJSONErrorMessage($e);			
		}	
	}
		
	/* Get element (RAW/KPI) by id
	 * @param string $id element id (from $_POST)
	 * @param string $type element type 'RAW' or 'KPI' (from $_POST)
	 * @param string $product the product id where to find the element (from $_POST)
	 * @return string JSON object containing the element
	 */	
	public function getElementById() {
		try {
			// check mandatory parameters
			$this->checkPostParameters(array('id', 'type', 'product'));														
					 					
			// get the queryId parameter		
			$id = $_POST['id'];
			$type = $_POST['type'];
			$productId = $_POST['product'];
			
		    // database connection
		    $database = DataBase::getConnection($productId);
		    if($database->getCnx()) {		    	    	
				
				$name = '';	$label = '';
				
				if ($type == 'KPI') {
					// get the kpi
					$kpiMod = new KpiModel();
					$kpiData = $kpiMod->getByIdFam($id, $database);
					$name = addslashes($kpiData["kpi_name"]);
					$label = addslashes($kpiData["kpi_label"]);
					$familyId = addslashes($kpiData['family']);					 					
				} else {
					// get the raw
					$rawMod = new RawModel();
					$rawData = $rawMod->getByIdFam($id, $database);
					$name = addslashes($rawData["edw_field_name"]);
					$label = addslashes($rawData["edw_field_name_label"]);
					$familyId = addslashes($rawData['family']);					
				}	
				
				// close db connection
				$database->close();
								
				// Create the JSON response	
				echo "{id: '$id', type: '$type', name: '$name', label: '$label', productId: '$productId', familyId: '$familyId'}";												
		    } else {
		    	$this->createDatabaseConnectionException();
			}

			unset($database);																		 						
					

		// Error management			
		} catch (Exception $e) {			
			echo $this->getJSONErrorMessage($e);			
		}	
	}

	/* Get element (RAW/KPI) by id and its family
	 * @param string $id element id (from $_POST)
	 * @param string $type element type 'RAW' or 'KPI' (from $_POST)
	 * @param string $product the product id where to find the element (from $_POST) 
	 * @return string JSON object containing the element
	 */	
	public function getElementByIdFam() {
		try {
			// check mandatory parameters
			$this->checkPostParameters(array('id', 'type', 'product'));																
					 					
			// get the queryId parameter		
			$id = $_POST['id'];
			$type = $_POST['type'];
			$productId = $_POST['product'];
			
		    // database connection
		    $database = DataBase::getConnection($productId);
		    if($database->getCnx()) {		    	    	
				
				$name = '';	$label = ''; $mod = null; $data = null;
				
				if ($type == 'KPI') {
					// get the kpi
					$mod = new KpiModel();
					$data = $mod->getByIdFam($id, $database);
					$name = addslashes($data['kpi_name']);
					$label = addslashes($data['kpi_label']);					
					$formula = addslashes($data['kpi_formula']);
					 						
				} else {
					// get the raw
					$mod = new RawModel();
					$data = $mod->getByIdFam($id, $database);
					$name = addslashes($data['edw_field_name']);
					$label = addslashes($data['edw_field_name_label']);					
					$formula = addslashes($data['edw_agregation_formula']);
				}	
				
				// comment
				$desc = addslashes($data['comment']);
				
				// family
				$familyId = $data['family'];
				$familyLabel = addslashes($data['family_label']);
				
				// close db connection
				$database->close();
				
				// Remove 'CASE WHEN' from formula if necessary
//				$formula = preg_replace('/CASE WHEN /', '', $formula, 1);
				
				// Create the JSON response	
				echo "{id: '$id', type: '$type', name: '$name', label: '$label', productId: '$productId', description: '$desc', formula: '$formula', familyId: '$familyId', familyLabel: '$familyLabel'}";												
		    } else {
		    	$this->createDatabaseConnectionException();
			}

			unset($database);																		 						
					

		// Error management			
		} catch (Exception $e) {			
			echo $this->getJSONErrorMessage($e);			
		}	
	}

	/* Get families for all products	 
	 * @return string JSON object products families
	 */	
	public function getProductsFamilies() {
		try {					
			// get all products
			$allProducts = ProductModel::getActiveProducts();
					
			$ret = '[';
			
			// for each product
			foreach ($allProducts as $prod) {
				$ret.= '{"qtip":"'.addslashes($prod['sdp_label']).'", "checked": true, "elementId":"'.$prod['sdp_id'].'", "text":"'.addslashes($prod['sdp_label']).'","children":[';
				// For each families in this product
				$fam = '';
				foreach( FamilyModel::getAllFamilies($prod['sdp_id']) as $family => $values ) {				
				    $fam .= '{"qtip":"'.addslashes($values['label']).'", "leaf": true, "checked": true, "elementId":"'.$values['code'].'","text":"'.addslashes($values['label']).'"},';
				}
				$fam = rtrim($fam, ',');				
				$ret.=$fam.']},';
			}
			$ret = rtrim($ret, ',');
			$ret.=']';
			
			echo $ret;
			
		// Error management			
		} catch (Exception $e) {			
			echo $this->getJSONErrorMessage($e);			
		}					
	}	
	
	/** Get SQL query from wizard parameters 
	 * @param $query (POST parameter) the query to display
	 */
	public function getComputedSqlQuery() {
		try {
			// check mandatory parameters
			$this->checkPostParameters(array('query'));
			
			// Get query object
			$queryObj = json_decode(stripslashes($_POST['query']));
			
			// Query data model																			
			$dataMod = new QueryDataModel();
												
			// Create SQL query
			$bean = $dataMod->createSqlQuery($queryObj);					
								
			// If there is an error, throw exception
			if ($bean->getErrorMessage()) {
				$e = new Exception($bean->getErrorMessage());		// Get error message
				if ($bean->getErrorNumber() != 0) {					// Error 0 : PHP error
					$e->type = 'user';								// Set error type to 'user' => if this is not a PHP error, the message could be displayed to the user
				}
				throw $e;
			}

			// Get Sql and return a json object
			$sql = $bean->getSql();

			// Remove forbidden keywords
			$sql = $this->removeSqlKeyWords($sql);
			
			// Create an empty object
			$json = new stdClass();
			
			// Add query
			$json->query = $sql;
			
			// Write response
			echo json_encode($json);			
			
		// Error management			
		} catch (Exception $e) {			
			echo $this->getJSONErrorMessage($e);					// By default Error type = system : don't show the error message to the user		
		}							
	}
	
	/** Get grid preview 
	 * @param $query (POST parameter) the query to display
	 */
	public function getGridPreview() {
		try {										
			// check mandatory parameters
			$this->checkPostParameters(array('query'));
			
			// Get query object			
			$queryObj = json_decode(stripslashes($_POST['query']));
			
			// Request Id, used to cancel (kill) the SQL request	
			$qbReqId = '/* qbPreview_'.$_POST['qbReqId'].' */';
			
			// Query data model
			$dataMod = new QueryDataModel();
			
			$bean = null;
					
			// Get the SQL query
			if (isset($queryObj->general->type) && $queryObj->general->type == 'sql') {
				// SQL type: get the user SQL query
				$query = $queryObj->sql->query;			
				
				// check mandatory parameters
				$this->checkPostParameters(array('server'));
					
				// set the server where the query should be execute
				$executeQueryOnProductId = $_POST['server'];		
				
			} else { 								
				// Wizard type: compute SQL query																													
				// Create SQL query
				$bean = $dataMod->createSqlQuery($queryObj);	
											
				// If there is an error, throw exception
				if ($bean->getErrorMessage()) {
					$e = new Exception($bean->getErrorMessage());		// Get error message
					if ($bean->getErrorNumber() != 0) {					// Error 0 : PHP error
						$e->type = 'user';								// Set error type to 'user' => if this is not a PHP error, the message could be displayed to the user
					}
					throw $e;
				}
								
				// Get Sql
				$query = $bean->getSql();
					
				// Get the product id where the query should be execute
				$executeQueryOnProductId = $bean->getProductIdWhereQueryIsExecute();
														
				// Get column label
				$columnLabel = $bean->getColumnHeader();
															
			}															
							
			// Remove forbidden keywords
			$query = $this->removeSqlKeyWords($query);
			
			// Tools for debug
			__debug("<span class='qbLink' onClick='Ext.get(\"qbTableDebug\").show()'>Debug</span><div id='qbTableDebug' style='display: none;'>\n$query<br><br></div>");														
						
			// database connection : if there is one productId connect to this server else connect to the master
		    //$database = DataBase::getConnection((isset($productIds) && count($productIds)==1)?$productIds[0]:null);		    		    
		    $database = DataBase::getConnection($executeQueryOnProductId, true);
					
			// If this is a multiproduct query, execute dblink_connect to connects to slaves products						
			$connectionList = $dataMod->executeDbLinkConnect($database, $query);
			
		    if($database->getCnx()) {
		    	$info = new stdClass;
				$rows = $database->getAll($qbReqId.$query, 1, $info);
				
				// If this is a multiproduct query, execute dblink_disconnect to disconnects from slaves products
				if ($connectionList) {
					$dataMod->executeDbLinkDisconnect($database, $connectionList);
				}
						
							
				// Display a warning if result list is truncated
				$nbLimit = get_sys_global_parameters('query_builder_nb_result_limit');
				if ($info->nbResults > $nbLimit){
					echo '<div class="qbPreviewLimit">Warning: number of results displayed for preview is limited to '.$nbLimit.'.</div>'; 
				}
			
				// Display query info
				echo '<div class="qbPreviewInfo">Number of result'.($info->nbResults>1?'s: <b>':': <b>').$info->nbResults.'</b> (query executed in '.$info->executionTime.($info->executionTime<2?' second).':'</b> seconds)').'</div>';
							
				// Table header display
				if ($rows && $rows[0]) {
					echo '<table class="qbPreviewTable">';
					echo '<thead><tr>';
					$i=0;
					
					foreach($rows[0] as $key => $value) {
						// For SQL type
						if (isset($queryObj->general->type) && $queryObj->general->type == 'sql') {
							echo '<th><b>&nbsp;'.ucwords($key).'&nbsp;</b></th>';
							
						// For wizard type
						} else {
							$label = $columnLabel[$i]->label;					
							if (!isset($columnLabel[$i]->visible) || $columnLabel[$i]->visible) {
								echo "<th><b>&nbsp;$label&nbsp;</b></th>";
							}
							$i++;
						}
					}
					echo '</tr></thead><tbody>';
				} else {
					// No result found message
					echo '<div class="qbPreviewQueryMessage"><span class="icoCancel qbErrorMessage">No result found, please refine your query.</span><br><br>';
					echo '<button type="button" class="qbButton" onClick="Ext.getCmp(\'mainTabPanel\').getLayout().setActiveItem(\'qbQueryTab\')">&nbsp;Back&nbsp;</button></div>';
					return;
				}
							
				$rowStrike = false;
				$numRow=1;
				
				// Row display			
				foreach($rows as $row) {
					$rowCls = $rowStrike?"row1":"row2";
					$rowStrike = !$rowStrike;
					echo "<tr class=\"$rowCls\">";
					$i = 0;
					foreach($row as $key => $col) {					
						if (!isset($columnLabel) || (!isset($columnLabel[$i]->visible) || $columnLabel[$i]->visible)) {
							echo "<td>&nbsp;$col&nbsp;</td>";
						}																	 								    
						$i++;					
					}
					echo '</tr>';
					
					// If the limit set in global parameters is reached -> stop
					if ($numRow == $nbLimit) {
						break;
					}
					$numRow++;				
				}
				echo '</tbody></table>';
				
			} else {
				$this->createDatabaseConnectionException();
			}
		// Error management			
		} catch (Exception $e) {			
			$type = isset($e->type)?$e->type:self::ERROR_SYSTEM;							
			$message = $e->getMessage();
			
			// Get error message
			$message = str_replace('href', 'noref', $message);		// disable php links in error message (replace href attribute by a fake attribute 'noref' <- does'nt mean nothing for the browser).		
			
			// If the query has been computed, add the query in the error message
//			if (isset($query) && $query) {
//				$message.='<br><br>Query:<br><p>'.$query.'</p>';
//			}
			
			// Display error message
			$this->displayExtendedErrorMessage($message, 'qbErrorGridPreview');
		}	
	}
	
	/* Display graph 
	 * @param $query (POST parameter) the query to display as graph
	 * @param $graphParameters (POST parameter) the graph parameters
	 */
	public function displayGraph() {		
		try {

			// check mandatory parameters
			$this->checkPostParameters(array('query'));					// The query
			$this->checkPostParameters(array('graphParameters'));		// The graph parameters			
			
			$parameters = json_decode(stripslashes($_POST['graphParameters']));
			$queryObj = json_decode(stripslashes($_POST['query']));
				
			// Request Id, used to cancel (kill) the SQL request
			$qbReqId = '/* qbPreview_'.$_POST['qbReqId'].' */';
			
			$isDataOnLeftAxis = false;
			$nbVisible = 0;
			
			// Test if there is at least one data on left axis. If no, JPGraph module raise an error.
			foreach ($parameters->graphData as $param) {				
				if (isset($param->position) && $param->position == 'left') {
					$isDataOnLeftAxis = true;
//					break;
				}
				// Count number of visible elements
				if (isset($param->type) && $param->type != 'no') {
					$nbVisible++;
				}
			}
									
			// If no data on left axis, display an error
			if (!$isDataOnLeftAxis) {
				$this->throwUserException('You must set at least one data on left position.');				
			}

			// If there is more than 30 visible elements ...			
			if ($nbVisible > 30) {
				$this->throwUserException('You can not display more than 30 elements at once.');
			}

			if ($nbVisible == 0) {
				$this->throwUserException('Select at least one element to display.'); 	
			}
												
			// Query data model																			
			$dataMod = new QueryDataModel();
						
			// Create SQL query
			$bean = $dataMod->createSqlQuery($queryObj, 'tamin');		
												
			// If there is an error, throw exception
			if ($bean->getErrorMessage()) {
				$e = new Exception($bean->getErrorMessage());		// Get error message
				if ($bean->getErrorNumber() != 0) {					// Error 0 : PHP error
					$e->type = 'user';								// Set error type to 'user' => if this is not a PHP error, the message could be displayed to the user
				}
				throw $e;
			}						

			$parameters->abscisse = 0;
			
			// Remove hidden columns
			$qbMod = new QbModel(null);
			$qbMod->hideHiddenColumns($bean);
			
			// Get Sql
			$query = $bean->getSql();																		
				
			// Remove forbidden keywords
			$query = $this->removeSqlKeyWords($query);
			
			// Tools for debug
			__debug("<span class='qbLink' onClick='Ext.get(\"qbGraphDebug\").show()'>Debug</span><div id='qbGraphDebug' style='display: none;'><br>Param : ".stripslashes($_POST['graphParameters'])."<br>\n$query<br></div>");										
									
		    // database connection : if there is one productId connect to this server else connect to the master
		    //$database = DataBase::getConnection((isset($productIds) && count($productIds)==1)?$productIds[0]:null);
			$database = DataBase::getConnection($bean->getProductIdWhereQueryIsExecute(), true);
			
		    if($database->getCnx()) {
		    	
				// If this is a multiproduct query, execute dblink_connect to connects to slaves products						
				$connectionList = $dataMod->executeDbLinkConnect($database, $query);
										
				$rows = $database->getAll($qbReqId.$query);
				if (!$rows || !$rows[0]) {
					// No result found message
					echo '<div class="qbPreviewQueryMessage"><span class="icoCancel qbErrorMessage">No result found, please refine your query.</span><br><br>';
					echo '<button type="button" class="qbButton" onClick="Ext.getCmp(\'mainTabPanel\').getLayout().setActiveItem(\'qbQueryTab\')">&nbsp;Back&nbsp;</button></div>';
					return;
				}
				
															
				$tableau_data = array();
				$nombre_resultat_affiche = min(100, count($rows));				
				$first_row = $rows[0];
				
				$i = 0;
				foreach ($first_row as $field_name => $some_value) {
	                // 15/02/2011 NSE DE Query Builder : on limite le nombre de résultats affichés
					for ($j = 0;$j < $nombre_resultat_affiche;$j++) {
						$tableau_data[$i][$j] = $rows[$j][$field_name];
					}
					$i++;
				}
												
				// restore default error handler (too many error in chart classes)
				restore_error_handler();
				
				// If this is a multiproduct query, execute dblink_disconnect to disconnects from slaves products
				if ($connectionList) {
					$dataMod->executeDbLinkDisconnect($database, $connectionList);
				}
															
				// Display graph
				$graph = new GraphGenerator();
				$graph->displayGraph($tableau_data, $parameters);
				
				// Store the current user id in the currentQuery -> used by the caddy_update method
				echo "<script>Ext.getCmp('qbGraphPanel').app.currentQuery.system.userId='".$_SESSION['id_user']."'</script>";
				
				// set our custom error handler
				$this->setErrorHandler();
			} else {
				$this->createDatabaseConnectionException();
			}
		// Error management			
		} catch (Exception $e) {			
			$type = isset($e->type)?$e->type:self::ERROR_SYSTEM;							
			$message = $e->getMessage();
			
			// Get error message
			$message = str_replace('href', 'noref', $message);		// disable php links in error message (replace href attribute by a fake attribute 'noref' <- does'nt mean nothing for the browser).		
			
			// If the query has been computed, add the query in the error message
			if (isset($query) && $query) {
				$message.='<br><br>Query:<br><p>'.$query.'</p>';
			}
			
			// Display error message
			$this->displayExtendedErrorMessage($message, 'qbErrorGraphPreview'); 						
		}						
	}

	/** Export queries in an archive
	 * @param string $queriesId (from $_POST) 
	 */
	public function exportQueries() {
		try {
						
			// check mandatory parameters
			$this->checkPostParameters(array('queriesId'));
									
			// Get the queries id to export
			$queriesId = explode(',', $_POST['queriesId']);
						
			// Keep unique id only
			$queriesId = array_unique($queriesId);
		
			// database connection
		    $database = DataBase::getConnection();
	
			// create querybuilder data model
			$qbMod = new QbModel($database);
							
		    if($database->getCnx()) {
		    	
				// Temporary directory for queries exports
				$tempPath = REP_PHYSIQUE_NIVEAU_0.self::QUERIES_EXPORT_TMP_DIR;
				if (!is_dir($tempPath)) {
					// Create qb_queries_import directory				
					mkdir($tempPath, 0777);
				}
																					
				// Create a new directory in the tempPath for this export
				$exportPath = $tempPath.'/'.getmicrotime();
				mkdir($exportPath, 0777);
				$gzip = false;
							
				// For each query
				foreach ($queriesId as $key => $queryId) {
										
					// Create a file to save the current query
			        if(($handle = fopen($exportPath."/".$key, 'w' ))){
			        	
						// Get the query as a JSON string
						$query = $qbMod->getQueryById($queryId);
						
						if ($query) {														
							// Parse query (remove queryId, parse RAW and KPI ...)
							$query = $this->parseQueryForExport($database, $query);
												
			            	fwrite($handle, $query);
							$gzip = true;
						}
            			fclose($handle);
        			}																						
				}
				
				// close db connection
				$database->close();
			} else {
				$this->createDatabaseConnectionException();
			}
			
			// delete database object	
			unset($database);									
			
			// If at least one export file has been created => create the gzip archive
			if ($gzip) {
				// 23/01/2014 GFS - Bug 39244 - [SUP][TA Gateway][Query Builder v2][AVP 40714][Zain Kuweit]: Export are not compressed
				$cmd = 'cd '.$exportPath.' && tar -zcf qb_queries_export.tar.gz *';
				exec($cmd);
				
				// Return the path to download the archive
				$link = str_replace(REP_PHYSIQUE_NIVEAU_0, NIVEAU_0, $exportPath); 
				echo '{"filePath": "'.$link.'/qb_queries_export.tar.gz"}';				
			}
				 		
		
		// Error management			
		} catch (Exception $e) {			
			echo $this->getJSONErrorMessage($e);			
		}
	}

	/** Get NE for a NA (with filter)
	 * @param $param string JSON object receive from $_POST
	 * 		  $param format : {"na": "cell", "text":"CELL_151"}
	 * 		  	na -> eor_obj_type
	 *          labelFilter (optional) -> string to search in the NE label
	 * 
	 * @return NE HTML list 
	 */
	public function getNe() {
		try {									
			// check mandatory parameters
			$this->checkPostParameters(array('param'));
		
			// Get parameters object			
			$param = json_decode(stripslashes($_POST['param']));
			
			// Get NE list
			$qbMod = new QbModel();
			$neList = $qbMod->getNe($param);
			
			// Build NE Html list
			echo '<table>';
			$lines = array();
			foreach($neList as $ne) {
				$lines[] = '<tr><td id="'.$ne['eor_id'].'">'.$ne['eor_label'].'</td></tr>';
			}
			$lines = array_unique($lines);
			foreach($lines as $line) {
				echo $line;
			}
			echo '</table>';						
			
		// Error management			
		} catch (Exception $e) {			
			echo $this->getJSONErrorMessage($e);			
		}
	}
			
	/** Get NE favourites lists 
	 * @return json string - favorites list 
	 */
	public function getFavNeList() {
		try {
		    // database connection
		    $database = DataBase::getConnection();
			
		    if($database->getCnx()) {	    
				// Create querybuilder data model
				$qbMod = new QbModel($database);
				
				$favorites = $qbMod->getFavNeList($_SESSION['id_user']);	
									    			
				// close db connection
				$database->close();
				
				// return json list									
				echo json_encode($favorites);
				
			} else {
				$this->createDatabaseConnectionException();
			}
			
			// delete database object	
			unset($database);
						
		// Error management			
		} catch (Exception $e) {			
			echo $this->getJSONErrorMessage($e);			
		}		
	}
	
	/* Save NE list into favourites */
	public function saveToFavorites() {
		try {
			// check mandatory parameters
			$this->checkPostParameters(array('items'));		// NE codes list
			$this->checkPostParameters(array('name'));      // Favorite name
			
		    // database connection
		    $database = DataBase::getConnection();
		
		    if($database->getCnx()) {	    
				// Create querybuilder data model
				$qbMod = new QbModel($database);
								
				// Save favourites
				$favorites = $qbMod->saveToFavorites($_SESSION['id_user'], $_POST['name'], explode(',', $_POST['items']));	
									    			
				// close db connection
				$database->close();
											
			} else {
				$this->createDatabaseConnectionException();
			}
			
			// delete database object	
			unset($database);
						
		// Error management			
		} catch (Exception $e) {			
			echo $this->getJSONErrorMessage($e);			
		}				
	}
	
	/* Delete a favorite */
	public function deleteFavorite() {
		try {
			// check mandatory parameters
			$this->checkGetParameters(array('id'));		// Favorite id			
			
		    // database connection
		    $database = DataBase::getConnection();
		
		    if($database->getCnx()) {	    
				// Create querybuilder data model
				$qbMod = new QbModel($database);
								
				// Save favorites
				$favorites = $qbMod->deleteNeFavorite($_SESSION['id_user'], $_GET['id']);	
									    			
				// close db connection
				$database->close();
											
			} else {
				$this->createDatabaseConnectionException();
			}
			
			// delete database object	
			unset($database);
						
		// Error management			
		} catch (Exception $e) {			
			echo $this->getJSONErrorMessage($e);			
		}						
	}
		
	/* Get a favorite from its id */
	public function getFavoriteById() {
		try {
			// check mandatory parameters
			$this->checkGetParameters(array('id'));		// Favorite id			
			
			// Get all products
			$allProducts = ProductModel::getActiveProducts();
			
			// Get id of each favorite elements
			$database = DataBase::getConnection();			
			$qbMod = new QbModel($database);				
			$elements = $qbMod->getFavoriteById($_GET['id']);
			
			// If no id exit ...
			if (!$elements) {
				echo '[]';
				return;
			}
			
			// Get eor_id list
			$ids = array();
			foreach ($elements as $element) {
				$ids[] = $element['eor_id'];
			}
								
			$favoritesList = array();
			
			// For each product...
			foreach ($allProducts as $product) {
			    // database connection
			    $database = DataBase::getConnection($product['sdp_id']);
			
			    if($database->getCnx()) {	    
					// Create querybuilder data model
					$qbMod = new QbModel($database);
									
					// Get element for this favorite
					$labels= $qbMod->getNeLabels($ids);	
													    			
					// close db connection
					$database->close();
												
				} else {
					$this->createDatabaseConnectionException();
				}
				
				//echo "\nProduct : ".$product['sdp_id']."\n".json_encode($favorites);
				$favoritesList = array_merge($favoritesList, $labels);
				
				// delete database object	
				unset($database);
			}
						
			echo json_encode($favoritesList);
			
		// Error management			
		} catch (Exception $e) {			
			echo $this->getJSONErrorMessage($e);			
		}						
	}	
	/** Parse query before exporting to file
	 * Remove queryId, for each RAW/KPI, add the element code and remove the element id
	 * @param string $database: the database connection
	 * @param string $query: the query to parse
	 * @return string the query to export	 
	 */
	private function parseQueryForExport($database, $query) {
		$json = json_decode($query['json']);
		
		$json->general = array();
		$json->general['type'] = $query['type'];
		$json->general['name'] = $query['name'];
		 		
		if ($query['type'] != 'sql') {		
						
			$qbMod = new QbModel($database);
					  
			// For each selected element
			foreach ($json->select->data as $element) {						
				// Fill the element name
				if ($element->type == 'RAW' || $element->type == 'KPI') {
					$qbMod->getElementData($element);								
					// Remove element id
					$element->id = null;
				}			
			}		
							
			// For each selected element
			foreach ($json->filters->data as $element) {						
				// Fill the element name
				if ($element->type == 'RAW' || $element->type == 'KPI') {
					$qbMod->getElementData($element);								
					// Remove element id
					$element->id = null;
				}			
			}		
			
			// For each selected element
			foreach ($json->graphParameters->gridParameters as $element) {						
				// Fill the element name
				if ($element->type == 'RAW' || $element->type == 'KPI') {
					$qbMod->getElementData($element);								
					// Remove element id
					$element->id = null;
				}			
			}						
		}								
		return json_encode($json);
	}
	
	/** Import queries in an archive
	 * @param string $file(from $_FILE)
	 * @return string JSON object	 
	 */
	public function importQueries() {
		try {
			// check mandatory parameters
			if(!isset($_FILES['importFile'])) {
	    		$this->createParametersException("FILE", 'importFile');			    		
			}
										
			// database connection
		    $database = DataBase::getConnection();
			 		    	
		    if($database->getCnx()) {
		    
				// Query builder model object
				$qbMod = new QbModel($database);
					
				// Temprary directory for queries import
				$tempPath = REP_PHYSIQUE_NIVEAU_0.self::QUERIES_IMPORT_TMP_DIR;
				if (!is_dir($tempPath)) {
					// Create qb_queries_export directory				
					mkdir($tempPath, 0777);
				}
																					
				// Create a new directory in the tempPath for this export
				$importPath = $tempPath.'/'.getmicrotime();
				mkdir($importPath, 0777);
				
				// Get file infos
				$file_name = $_FILES['importFile']['name'];
				$tmp_file = $_FILES['importFile']['tmp_name'];											
				$extension = substr($file_name, strrpos($file_name, '.') + 1);					
				$file_size  = $_FILES['importFile']['size'];
				$file_type  = $_FILES['importFile']['type'];
				$file_error = $_FILES['importFile']['error'];
				$msgError = null;
				
				// Error management
			    if ( $file_error == 1 ||  $file_error == 2 ) {	
					$msgError = __T('A_UPLOAD_TOPOLOGY_FILE_IS_TOO_BIG');
			    } elseif ( $file_error == 3 ) {	
					$msgError = __T('A_E_UPLOAD_TOPOLOGY_FILE_PARTIAL');
			    } elseif ( $file_error == 4 ) {	
					$msgError = __T('A_E_UPLOAD_TOPOLOGY_FILE_MISSING');
			    } elseif ( $file_size == 0 ) {
					$msgError = __T('A_E_UPLOAD_TOPOLOGY_FILE_IS_EMPTY');
			    } elseif ($extension != "gz" ) {
					$msgError = __T('A_E_UPLOAD_FILE_EXTENSION_INCORRECT');
				}

				// if no error
				if ($msgError == null) {					
					// move the file in the upload path
					$file_name = 'archive.tar.gz';			// fixe the name of the uploaded file on the server, to avoid name with spaces ...
					
					if( !move_uploaded_file($tmp_file, $importPath.'/'.$file_name) ){
						$msgError = __T('A_E_UPLOAD_FILE_NOT_COPIED');
					}
					else {
						// extract archive
						$cmd = "cd $importPath && tar -xf $file_name";
						exec($cmd);
						
						// remove archive
						$cmd = "cd $importPath && rm -f $file_name";
						exec($cmd);
						
						// Import queries one by one
						$msgError = $this->importQueriesFromFiles($importPath, $qbMod);
					}
				}
								
				// Write the JSON response
				if ($msgError != null) {
					echo '{"success": false, "msg":"'.addslashes($msgError).'"}';
				}

				// close db connection
				$database->close();
			} else {
				$this->createDatabaseConnectionException();
			}
			
			// delete database object	
			unset($database);																 		
		
		// Error management			
		} catch (Exception $e) {			
			echo $this->getJSONErrorMessage($e);			
		}
	}

	/**
	 * Import queries from files
	 * @param $path path where the files to import can be found
	 * @param $qbMod the query builder model object
	 * @return message to the user (Ok or success)
	 */
	private function importQueriesFromFiles($path, $qbMod) {
				 			
		$dir = opendir($path);
		$file = readdir($dir);
		$message = '';
		$ret = true;
		
		// Loop on all queries to import
		while($file!== false) {			
			if($file != '.' && $file != '..') {
				// Read the query file  
				$query = file_get_contents($path.'/'.$file);													
																							
				// Get the query JSON object
				$query = json_decode($query);
								
				// If file corrupted
				if (!$query) {					
					break;										
				}
												
				$queryName = $query->general->name;
				$queryType = $query->general->type;
				$query->general = null;
				
				$message.='<br><b> Query: '.$queryName.'</b><br>';
												
				// a query already exist with this name ?
				if ($qbMod->getQueryByName($queryName, $_SESSION['id_user'])) {
					$queryName.='_1';
					if ($qbMod->getQueryByName($queryName, $_SESSION['id_user'])) {
						$message.=' - A query already exist with this name.<br>';
						break;
					}
				}
								
				// Check each RAW/KPI, if someone is not found on the server return false 
				if ($queryType != 'sql') {
					$ret = $this->checkQuerieElements($query, $qbMod);
				}										
								
				if ($ret !== true) {
					// Get the error message
					$message.=$ret;		
					$ret = true;			
				} else {
					// Save the query in the database
					
					// insert the new query and returns its id
					$qbMod->insertQuery(addslashes($queryName), $queryType, $_SESSION['id_user'], json_encode($query));
					
					$name = ($queryName!=$queryName)?' ('.$queryName.')':'';
					$message.=' - imported successfully'.$name.'.<br>';
				}
			} 			
			$file = readdir($dir);
		} 
		
		closedir($dir);
		
		if (!$message) {
			$message.='<br>File corrupted !<br>Import aborted.';
		}
		  
		return $message;		
	}

	/**
	 * Check elements of the query
	 * @param $query the query to check
	 * @param $qbMod the query model object
	 * @return true if Ok else false
	 */
	private function checkQuerieElements($query, $qbMod) {
				
		// For each data elements
		foreach ($query->select->data as $element) {						
			$ret = $this->checkElement($element, $qbMod);
			if ($ret !== true) {
				return $ret;				
			}
		}
		
		// For each filters elements
		foreach ($query->filters->data as $element) {						
			$ret = $this->checkElement($element, $qbMod);
			if ($ret !== true) {
				return $ret;				
			}
		}
		
		// For each graph parameter
		foreach ($query->graphParameters->gridParameters as $element) {						
			$ret = $this->checkElement($element, $qbMod);
			if ($ret !== true) {
				return $ret;				
			}
		}
				
		return true;
	}			
		
	/**
	 * Check element (RAW, KPI ...)
	 * @param $element the element to check
	 * @return true if Ok else error message
	 */	
	private function checkElement($element, $qbMod) {
		
		// switch on element type
		switch ($element->type) {
			case 'RAW':
			case 'KPI':				
				// For RAW and KPI, find the element in the database and fill its id in the query
				if (!$qbMod->setElementId($element)) {
					return ' - Element '.$element->name.' ('.$element->type.') not found in the database.<br> - Import aborted.<br>';					
				}
			break;
		}		
		
		return true;
	}
		
	/** Remove forbidden keywords (grant,update,insert,delete,drop,create)
	 * @param $sql string the sql query
	 * @return the sql query without forbidden keyword
	 */
	private function removeSqlKeyWords($sql) {
		// Forbidden keywords list
//		$keywords = array('grant ', "grant\n", 'update ', "update\n", 'insert ', "insert\n", 'delete ', "delete\n", 'drop ', "drop\n", 'create ', "create\n");
	
//		return str_ireplace($keywords, '', $sql);
return $sql; 
	}
	
	/** Display an error message with a 'details' link to show extended message
	 * @param $message error message
	 * @param $spanId id of the span containing the error message (should be unique)
	*/	
	private function displayExtendedErrorMessage($message, $spanId) {
		echo '<div class="qbPreviewQueryMessage">';
		echo '<span class="icoCancel qbErrorMessage">An error occured during the query execution.<br>Please refine your query.</span> <span class="qbLink" onClick="Ext.get(\''.$spanId.'\').show(true)">Details</span><br><br>';
		echo '<button type="button" class="qbButton" onClick="Ext.getCmp(\'mainTabPanel\').getLayout().setActiveItem(\'qbQueryTab\')">&nbsp;Back&nbsp;</button>';				
		echo '<br><br><span class="qbPreviewDetailError" id="'.$spanId.'">'.$message.'</span>';
		echo '</div>';		
	}
	
	/** Export queries 
	 * @param $param string JSON object receive from $_POST
	 * 		  $param format : {"queriesId": [1,2,3,4...]}
	 */
	public function csvExportQueries() {
		try {
			// check mandatory parameters
			$this->checkPostParameters(array('param'));
				
			// database connection
		    $database = DataBase::getConnection();
		    
			if($database->getCnx()) {

				// Create Qb model
				$qbMod = new QbModel($database);
				
				// Check if there at least 20% of free disk space
				if (!$qbMod->checkDiskSpaceForCsvExport()) {
					$this->throwUserException('There is not enough free disk space on the server to launch the export process.'); 				
				}				

				// Get parameters object			
				$param = json_decode(stripslashes($_POST['param']));
			
				// Keep unique id only
				$queriesId = array_unique($param->queriesId);
				
				// Export queries
				foreach($queriesId as $id) {
			
					// get the query from its id
					$query = $qbMod->getQueryById($id);
													
					if ($query) {												
						// Insert a new export in the qbExports database table
						$qbMod->createNewExport($query, $_SESSION['id_user']);
					}
				}
				
				// Close db connection
				$database->close();
								
				// Run export process for current user
				exec("php ".REP_PHYSIQUE_NIVEAU_0."querybuilder/php/csvExportProcess.php ".$_SESSION['id_user']." > /dev/null &", $output);
				
				// Response
				echo '{}';
				
			} else {
				$this->createDatabaseConnectionException();
			}						
			
		// Error management			
		} catch (Exception $e) {			
			echo $this->getJSONErrorMessage($e);			
		}
	}
	
	/** Get user csv exports
	 * @return json object : user exports list
	 */
	public function getDownloads() {
		try {				
			// database connection
		    $database = DataBase::getConnection();
		    
			if($database->getCnx()) {

				// Create Qb model
				$qbMod = new QbModel($database);
				
				// Clean Csv export list	
				$qbMod->cleanCsvExports();
						
				// Get export list
				$exports = $qbMod->getCsvExportList($_SESSION['id_user']);
								
				$list = array();
				$nbWaiting = 0;
				$nbLoading = 0;
				
				// Create JSON list
				foreach ($exports as $export) {
					// For completed exports (state=5) : Check if export is still present on the server (CSV exports older than 24 hours are automaticaly removed)
					$path = str_replace(NIVEAU_0, '', REP_PHYSIQUE_NIVEAU_0).$export['file'];
															
					if ($export['state'] == '5' && !file_exists($path)) {
						// if export file has been removed, change export state						
						$qbMod->setExportInError($export['id'], 'Export has been removed from the server.');
					}
					
					$jsonExport = $this->getJsonDownload($export);					
					$list[] = $jsonExport;
					
					if ($jsonExport->state == 'Scheduled') {
						$nbWaiting++;
					} else if ($jsonExport->state == 'In progress') {
						$nbLoading++;
					}
															
				}
				
				// Create JSON response
				$json = new stdClass;
				$json->children = $list;
				$json->nbWaiting = $nbWaiting;
				$json->nbLoading = $nbLoading;
								
				echo json_encode($json);
															
				// close db connection
				$database->close();
			} else {
				$this->createDatabaseConnectionException();
			}
			
			
		// Error management			
		} catch (Exception $e) {			
			echo $this->getJSONErrorMessage($e);			
		}
	}
	
	/* Get download sate string by state code
	 * @param $stateCode (0,1,2,3 ...)
	 * @Return string the download state */
	private function getJsonDownload($export) {		
		switch((string)$export['state']) {
			case '1':
				$state = 'Scheduled';
				$iconCls = 'icoTime';
				break;	
			case '2':
			case '3':
			case '4':						
				$state = 'In progress';
				$iconCls = 'icoLoading';
				break;
			case '5':
				$state = 'Completed';
				$iconCls = 'icoAccept';
				break;
			case '6':
			case '7':
				$state = 'Error';
				$iconCls = 'icoStop';
				break;
		}
		
		// Create download object
		$json = new stdClass;
		$json->id = (string)$export['id'];
		$json->start_date = (string)$export['start_date'];
		$json->end_date = (string)$export['end_date'];
		$json->iconCls= $iconCls;
		$json->state = $state;
		$json->name = $export['name'];
		$json->filePath = $export['file'];
		$json->leaf = 'true';
		$json->error_message = $export['error_message'];
		
		return $json;
		
	}
	
	/* Delete a download 
	 * @param string $id : download id (from $_GET)	 
	 */			
	public function deleteDownload() {		
		try {				
			// check mandatory parameters
			$this->checkGetParameters(array('id'));
																		
			// database connection		
			$database = DataBase::getConnection();
		    if($database->getCnx()) {
		    	// Create querybuilder data model
				$qbMod = new QbModel($database);

				// Check if the user has right to delete this export
				if (!$qbMod->isDownloadOwner($_GET['id'], $_SESSION['id_user'])) {
					$this->noRightException();
				}
				
				// Delete the download				
		    	$qbMod->deleteExport($_GET['id']);
										
				// close db connection
				$database->close();
		    } else {
		    	$this->createDatabaseConnectionException();
			}
			unset($database);				
		
		// Error management			
		} catch (Exception $e) {			
			echo $this->getJSONErrorMessage($e);			
		}	
	}
	
	/* Delete all download (download = exports) 
	 * 	 
	 */			
	public function deleteAllDownload() {		
		try {																						
			// database connection		
			$database = DataBase::getConnection();
		    if($database->getCnx()) {
		    	// Create querybuilder data model
				$qbMod = new QbModel($database);

				// Get export list
				$exports = $qbMod->getCsvExportList($_SESSION['id_user']);
												
				// For each export
				foreach ($exports as $export) {
					// Check if the user has right to delete this query
					if (!$qbMod->isDownloadOwner($export['id'], $_SESSION['id_user'])) {
						$this->noRightException();
					}
					
					// Delete the download				
			    	$qbMod->deleteExport($export['id']);
				}
										
				// close db connection
				$database->close();
		    } else {
		    	$this->createDatabaseConnectionException();
			}
			unset($database);				
		
		// Error management			
		} catch (Exception $e) {			
			echo $this->getJSONErrorMessage($e);			
		}	
	}	
	
	
	/* getNELabels from NE code list 
	 * @param string $id : download id (from $_GET)	 
	 */			
	public function getNELabels() {		
		try {
			// check mandatory parameters
			$this->checkPostParameters(array('neList'));
                        $this->checkPostParameters(array('na'));
			
			// Surround neCode by quotes			
			$neIdList = "'".str_replace(",", "','", $_POST['neList'])."'";
                        $na       = !empty($_POST['na']) ? $_POST['na'] : '';
						
			$allProducts = ProductModel::getActiveProducts();
			$list = array();
											
			// for each product
			foreach ($allProducts as $prod) {
				// database connection		
				$database = DataBase::getConnection($prod['sdp_id']);
				
			    if($database->getCnx()) {		    	
			    	// Create querybuilder data model
					$qbMod = new QbModel($database);
					
					// Get NE	
                                        // 24/04/2012 BBX
                                        // BZ 26949 : Ajout du NA
					$neList = $qbMod->getNEListFromCodeList($neIdList, $na);
												
					$list = array_merge($list, $neList);
					
					// close db connection
					$database->close();
			    } else {
			    	$this->createDatabaseConnectionException();
				}
				unset($database);
			}
			
			//create a list without duplicated elements
			$unique_list = array();
			foreach($list as $tmpList) {
				$found = false;
				foreach($unique_list as $tmpUniqueList){
					$found = $found || ($tmpList["code"]==$tmpUniqueList["code"] && $tmpList["label"]==$tmpUniqueList["label"]);
				}
				if(!$found){
					$unique_list[] = $tmpList;
				}
			}
			
			// Display result (NE codes + NE labels)
			echo json_encode($unique_list);
			
		// Error management			
		} catch (Exception $e) {			
			echo $this->getJSONErrorMessage($e);			
		}	
	}	
	
	/* Cancel an SQL request 
	* @param id ($_POST) : the query id to kill
	*/
	public function cancelSqlRequest() {
		try {
						
			// check mandatory parameters
			$this->checkGetParameters(array('id'));
																		
			// database connection		
			$database = DataBase::getConnection();
		    if($database->getCnx()) {
		    	// Create querybuilder data model
				$qbMod = new QbModel($database);
				
				// Kill SQL query (if it is running)
				$neList = $qbMod->killPreviewQuery($_GET['id']);				
				
				// close db connection
				$database->close();
		    } else {
		    	$this->createDatabaseConnectionException();
			}
			unset($database);

			echo '{}';
						
		// Error management			
		} catch (Exception $e) {			
			echo $this->getJSONErrorMessage($e);			
		}	
	}
	
	/** Get KPI html list
	* @param filterOptions ($_POST parameter) see getKpiHtmlList method for usage
	* @return HTML KPI list
	*/
	public function getKpiHtmlList() {
		try {								
			// get search options JSON object
			$searchOptions = '';			
			if (isset($_POST['filterOptions'])) {
				$searchOptions = json_decode(stripslashes($_POST['filterOptions']));	
			}
			
		    // Get KPI list
			$qbMod = new QbModel();
			echo $qbMod->getKpiHtmlList($searchOptions);				
						
		// Error management			
		} catch (Exception $e) {			
			echo $this->getJSONErrorMessage($e);			
		}
	}
	
	/** Get RAW html list
	* @param filterOptions ($_POST parameter) see getKpiHtmlList method for usage
	* @return HTML RAW list
	*/	
	public function getRawHtmlList() {
		try {								
			// get search options JSON object
			$searchOptions = '';			
			if (isset($_POST['filterOptions'])) {
				$searchOptions = json_decode(stripslashes($_POST['filterOptions']));	
			}
			
		    // Get KPI list
			$qbMod = new QbModel();
			echo $qbMod->getRawHtmlList($searchOptions);				
						
		// Error management			
		} catch (Exception $e) {			
			echo $this->getJSONErrorMessage($e);			
		}
	}	
}	

?>