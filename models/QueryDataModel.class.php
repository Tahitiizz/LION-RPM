<?php

include_once dirname( __FILE__ ).'/../php/environnement_liens.php';
include_once REP_PHYSIQUE_NIVEAU_0.'class/QueryBean.class.php';
include_once REP_PHYSIQUE_NIVEAU_0.'php/edw_function_family.php';
include_once REP_PHYSIQUE_NIVEAU_0.'php/edw_function.php';

/**
 * Class to generate the SQL query from a query builder query
 */
class QueryDataModel { 

	/** Get a JSON response to a JSON request
	 * @param $data JSON string retreive from the API 
	 */
	public function getJsonResponse($data) {
		try {

			// Get the json object received by the API
			$data = json_decode($data);
			 			
			// Parse error
			if (!$data) {				
				return $this->getJsonError(4, 'Invalid Format. Could not request.');								
			}
			
			// If no method parameter
			if (!isset($data->method)) {
				return $this->getJsonError(5, 'Method parameter is mandatory.');
			}
			
			// If the method name does not exist
			if (!method_exists($this, 'api_'.$data->method)) {
				return $this->getJsonError(6, 'Could not find a suitable method with name: '.addslashes($data->method));
			}
					
			// If no parameters
			if (!isset($data->parameters) || !$data->parameters) {				
				$data->parameters = false;
			}						
			
			// call the method	
			$ret = call_user_func(array($this, 'api_'.$data->method), $data->parameters);
				
			// If the return is a string (html ...) return the string
			if (gettype($ret) == 'string') {
        		return $ret;        	
			} else {
				// Else this is JSON ...stringify the object
				return json_encode($ret);				
			} 
					
		} catch (Exception $e) {			
			// If this is a user exception, get the error number, else if this is a php error, number = 0					
			$number = isset($e->userErrNumber)?$e->userErrNumber:0;			
			return $this->getJsonError($number, $e->getMessage(), (isset($e->debug)?$e->debug:null));	
		}
	}
	
	//--------------------------------------------------------------//
	//				METHODS AVAILABLE THRUE JSON API				//
	//--------------------------------------------------------------//
	
	/** Retreive data from the database
	 * @param $query object query receive from the API
	 * @return object response
	 */		
	private function api_getData($query) {
		if (!$query) {
			return $this->getJsonError(7, 'No parameter found.', null);			
		}				
		return $this->getData($query);
	}
	
	/** Retreive HTML KPI list from the database
	 * @param $searchOptions query receive from the API
	 * @return object response
	 */		
	private function api_getKpiHtmlList($searchOptions) {
		if (!$searchOptions) {
			return $this->getJsonError(7, 'No parameter found.', null);
		}
		$qbMod = new QbModel();
		return $qbMod->getKpiHtmlList($searchOptions);
	}
				
	/** Retreive HTML RAW list from the database
	 * @param $searchOptions query receive from the API
	 * @return object response
	 */		
	private function api_getRawHtmlList($searchOptions) {
		if (!$searchOptions) {
			return $this->getJsonError(7, 'No parameter found.', null);
		}
		$qbMod = new QbModel();
		return $qbMod->getRawHtmlList($searchOptions);
	}
	
	/** Retreive KPI list from the database (JSON list)
	 * @param $searchOptions query receive from the API
	 * @return object response
	 */		
	private function api_getKpiList($searchOptions) {
		if (!$searchOptions) {
			return $this->getJsonError(7, 'No parameter found.', null);
		}
		$qbMod = new QbModel();
		return $qbMod->getKpiList($searchOptions);
	}
				
	/** Retreive RAW list from the database (JSON list)
	 * @param $searchOptions object receive from the API
	 * @return object response
	 */		
	private function api_getRawList($searchOptions) {
		if (!$searchOptions) {
			return $this->getJsonError(7, 'No parameter found.', null);
		}
		$qbMod = new QbModel();
		return $qbMod->getRawList($searchOptions);
	}
	
	/** Retreive NE list (limited to 5000 NE)
	 * @param $searchOptions query receive from the API
	 * @return object response
	 */		
	private function api_getNeHtmlList($searchOptions) {
		if (!$searchOptions) {
			return $this->getJsonError(7, 'No parameter found.', null);
		}
		
		$qbMod = new QbModel();
		$neList = $qbMod->getNe($searchOptions);
		
		// Build NE Html list
		$html = '<table>';
		foreach($neList as $ne) {
			$html.= '<tr><td id="'.$ne['eor_id'].'">'.$ne['eor_label'].'</td></tr>';
		}						
		return $html.'</table>';				
	}
	
	/** Retreive NE list (no limit)
	 * @param $searchOptions query receive from the API
	 * @return object response
	 */		
	private function api_getNeList($searchOptions) {
		if (!$searchOptions) {
			return $this->getJsonError(7, 'No parameter found.', null);
		}
		
		$json = '';
		$qbMod = new QbModel();
		$neList = $qbMod->getNe($searchOptions, true);				
		
		// Build NE JSON object		
		foreach($neList as $ne) {
			$json.='{"id": "'.$ne['eor_id'].'", "label": "'.$ne['eor_label'].'"},';
		}						
		return '['.rtrim($json, ',').']';
	}							
	
	/** Retreive TA and NA in common
	 * @param $elements RAW/KPI list (JSON)
	 * @return object response (JSON)
	 */		
	private function api_getAggInCommon($elements) {
		if (!$elements) {
			return $this->getJsonError(7, 'No parameter found.', null);
		}		
		$qbMod = new QbModel();
		$json = $qbMod->getAggInCommon($elements);
		return $json;
	}								
		
	/** Return product & families list
	 * @return object response (JSON)
	 */		
	private function api_getProductsFamilies() {
		$qbMod = new QbModel();
		$json = $qbMod->getProductsFamilies();
		return $json;
	}						
										
	//--------------------- END JSON API METHODS -----------------------------//
	
	
	/** Execute dbLink connects to access slaves products before executing a multi product query
	 * @param $database, the connection to the database
	 * @param $bean object the query object
	 * @return dblink connect list
	 */
	public function executeDbLinkConnect($database, $sql) {
	
		$lstConnects = $this->computeDbLinkConnects($sql);
		
		if ($lstConnects) {
			// For each dblink_connect
			foreach ($lstConnects as $connect) {
				__debug($connect);				
				$database->execute($connect);
			}
		}
						
		return $lstConnects;
	}
	
	/** Compute dblink connects for all dblink statements founds in the SQL query
	 * @param $sql the sql query
	 * @return dblink connect list	 
	 */
	public function computeDbLinkConnects($sql) {
		// Extract dblink from the multiproduct SQL query
		$parts = explode('dblink(', $sql);				
				
		// No dblink found for this query
		if (!$parts || count($parts) < 2) {
			return false;
		}
		
		$connects = array();
			
		// For each dblink found in the query, extract dblink connection name
		$nb = count($parts);
						
		for ($i=1; $i < $nb; $i++) {
			$tmp = explode("'",$parts[$i]);			// extract connectionName from SQL dblink
			$connectionName = $tmp[1];				// sample $connectionName => "productId_2"
						
			$tmp = explode('_', $connectionName);	// extract productId from connectionName
			$productId = $tmp[1];					// sample $productId => 2
										
			// Get product info from connection product Id
			$product = ProductModel::getProductById($productId);
			
			// If product not found
			if (!$product) {
				$this->throwUserException(10, "You are using RAW or KPI from a product that does not exist (ProductId=$productId).", $sql);
			}
			
			// If product is disable display an error message
			if ($product['sdp_on_off'] == 0) {
				$this->throwUserException(11, "You are using RAW or KPI from a product that has been disabled (ProductId=$productId).", $sql);
			}

			// Get the dblink_connection string (sample : 'hostaddr=192.168.121.128 port=5432 dbname=gsm_querybuilder user=postgres password=')
			$dbLinkConnectString = $this->getDbLinkConnectString($product);			
			
			// Create a connection to the slave product
			$sql = "SELECT dblink_connect_u('$connectionName', '$dbLinkConnectString');";					

			// Save dblink connects
			$connects[$connectionName] = $sql;											
		}		

		return $connects;
	}
	
	/** Execute dbLink disconnect on connection list
	 * @param $database, the connection to the database
	 * @param $connectionList array list of connection	 
	 */
	public function executeDbLinkDisconnect($database, $connectionList) {				

		foreach ($connectionList as $connectionName => $connection) {
			// Create a connection to the slave product
			$sql = "SELECT dblink_disconnect('$connectionName');";
			$database->execute($sql);				
		}
								
	}
		
	/* Get the dblink_connection string from a product
	 * @param $product - product row from sys_definition_product
	 * @return string - sample : 'hostaddr=192.168.121.128 port=5432 dbname=gsm_querybuilder user=postgres password='
	 */
	private function getDbLinkConnectString($product) {
		$ret = '';
		
		if ($product) {
			$ret = 'hostaddr='.$product['sdp_ip_address'].' port='.$product['sdp_db_port'].' dbname='.$product['sdp_db_name'].' user=read_only_user password=read_only_user';	
		}
		
		return $ret;
	}
	
	/** Retreive data from the database
	 * @param $query object query receive from the API
	 * @return object response
	 */	
	public function getData($query) {
		try {
			$connectionList = null;
												
			// Get SQL query
			$bean = $this->createSqlQuery($query);
			
			// If there is an error
			if ($bean->getErrorMessage()) {									
				$this->throwUserException($bean->getErrorNumber(), $bean->getErrorMessage(), $bean->getSql());
			}
			
			// Get SQL query
			$sql = $bean->getSql();										
			
			// Get column label
			$columnLabel = $bean->getColumnHeader();
											
			// Connect to the server with readonly user
			$database = DataBase::getConnection($bean->getProductIdWhereQueryIsExecute(), true);
							
			if($database->getCnx()) {
				$i = 0;
				
				// If this is a multiproduct query, execute dblink_connect to connects to slaves products						
				$connectionList = $this->executeDbLinkConnect($database, $sql);

				// Get rows
				$rows = $database->getAll($sql);						
				
				// Create empty response object
				$response = new stdClass;
				
				// Count
				$response->count = count($rows);
							
				// Table header display
				if (!$rows || !$rows[0]) {
					// If this is a multiproduct query, execute dblink_disconnect to disconnects from slaves products
					if ($connectionList) {
						$this->executeDbLinkDisconnect($database, $connectionList);
					}
				
					// No result found message				
					$this->throwUserException(8, 'No result found. Please refine your query.', $sql);
				}						
				
				$response->label = array();						
				
				// Column label			
				foreach($rows[0] as $key => $value) {							
					$label = $columnLabel[$i]->label;					
					if ($columnLabel[$i]->visible) {
						$response->label[] = $label;
					}
					$i++;			
				}						
	
				// Data rows;			
				$response->data = array();						
				foreach($rows as $row) {							
					$i = 0;
					$colList = array();
					foreach($row as $key => $col) {					
						if (!isset($columnLabel) || ($columnLabel[$i]->visible)) {
							$colList[] = $col;
						}																	 								    
						$i++;					
					}				
					$response->data[] = $colList;				
				}						
				
				// If this is a multiproduct query, execute dblink_disconnect to disconnects from slaves products
				if ($connectionList) {
					$this->executeDbLinkDisconnect($database, $connectionList);
				}
				
				// Return response
				return $response;
							
			} else {
				// Error database connection
				$this->throwUserException(9, 'Database connection error.');
			}
		} catch (Exception $e) {			
			// If this is a multiproduct query, execute dblink_disconnect to disconnects from slaves products
			if ($connectionList) {
				$this->executeDbLinkDisconnect($database, $connectionList);
			}
				
			// If the query has been computed, add it to the exception
			if (isset($sql) && $sql) {
				$e->debug = $sql;
			}
			throw($e);
		}
	}
			
	/** Throw a custom exception
	 * @param number string error number
	 * @param string $message the error message	 
	 * @param string $debug the debug message
	 */
	private function throwUserException($number, $message, $debug=null) {				
		$qbException = new Exception($message);
		$qbException->userErrNumber = $number;
		$qbException->debug = $debug;
		throw($qbException);
	}	
		
	/** Create a JSON error message string
	 * @param $errorNumber integer, the error number
	 * @param $errorMessage string, the error message	 
	 * @param $debug string, debug message 
	 */	
	public function getJsonError($errorNumber, $errorMessage, $debug=null) {
		
		// If debug is enable
		$check = get_sys_debug('debug_global');
		
		if ($debug && $check) {
			$debug = str_replace(array("\r", "\r\n", "\n", "\t"), "", $debug);
			$debug= str_replace("\'", "'", addslashes($debug));						
			$debug = ",\"debug\":\"".$debug."\"";
			
			$errorMessage= str_replace(array("\r", "\r\n", "\n", "\t"), "", $errorMessage);
			$errorMessage= str_replace("\'", "'", addslashes($errorMessage));
		} else {
			$debug = '';
		}
		$errorMessage = str_replace("\'", "'", addslashes($errorMessage));		
		return "{\"error\": {\"number\":".$errorNumber.", \"message\":\"".$errorMessage."\"".$debug."}}";
	}
	
	/** Add a dblink to the current SQL query
	 * @param: $bean object - contain the sql query and column header label*
	 * @param: $productId the productId	 
	 * @param: $productName the product name 
	 */
	private function addDblink($bean, $productId, $productName) {
		$select = array(); $sqlColumnPrototype = array();
		
		// Get SQL query
		$sql = str_replace("'", "''", $bean->getSql());
				
		foreach ($bean->getColumnHeader() as $element) {
				$select[] = $element->sqlColumnName;
				$sqlColumnPrototype[] = $this->getSqlColumnPrototype($element);  
		}
		
		// Compute new query with dbLink
		$sql = "SELECT ".implode(', ', $select)." FROM dblink('ProductId_$productId', '$sql') as product$productId (".implode(', ', $sqlColumnPrototype).")";
		
		// Update the SQL query from the current query object
		$bean->setSql($sql);
	}
	
	/** Compute the group by for multiprod queries
	 * Adds a global query with nested single prod. queries. This global query merge RAW/KPI from all products using a group by clause
	 * @param: $bean the query object
	 * @param: $sql the SQL query 
	 * @return: the SQL query with group by 
	 */
	private function computeMultiProdGroupBy($bean, $sql) {
		$select = array(); $multiProdGroupBy = array(); $userGroupBySelect = array(); $distinct = '';
		
		// Compute GROUP BY to merge each product query (add SUM aggregate function for RAW and KPI)					
		foreach ($bean->getColumnHeader() as $element) {
			// if this is a RAW or KPI, add it to the select part with an aggregation function (SUM)
			if ($element->type == 'RAW' || $element->type == 'KPI') {
				// Add aggregate functions			
				$select[] = 'SUM('.$element->sqlColumnName.') AS '.$element->sqlColumnName;
			} else {
				// If this is a ta, na or na_axe3				
				// add it to the select part
				
				$select[] = $element->sqlColumnName;
								
				// add it to the group by
				$multiProdGroupBy[] = $element->sqlColumnName;
			}							 			 
		}
		
		// Add distinct ?		 
		$distinct = $bean->getIsDistinct()?'DISTINCT ':'';
		 				
		// Check if there is a user GROUP BY (if user check any group column and 'Disabled function' button is not enabled)
		$userGroupBy = $bean->getUserGroupBy();
					
		// Add user group by
		if ($userGroupBy) {
			// Add multiprod group by
			$sql = "SELECT ".implode(', ', $select)." FROM (\n$sql\n\n) as multiprod GROUP BY ".implode(', ', $multiProdGroupBy);
		
			foreach ($bean->getColumnHeader() as $element) {

				if (isset($element->function) && $element->function) {				
					$userGroupBySelect[] = $this->getFunction($element->function).'('.$element->sqlColumnName.') AS '.$element->sqlColumnName;
				} else {
					$userGroupBySelect[] = $element->sqlColumnName;
				}			 
			}						
			// Add user group by
			$sql = "SELECT ".$distinct.implode(', ', $userGroupBySelect)." FROM (\n\n$sql\n\n) as userGroupBy ".$userGroupBy;
			
		} else {
			// Add multiprod group by
			$sql = "SELECT ".$distinct.implode(', ', $select)." FROM (\n$sql\n\n) as multiprod GROUP BY ".implode(', ', $multiProdGroupBy);
		}
		
		$orderBy = $bean->getOrder();
		$order = '';
					
		// Add order by, Sort keys to keep user order
		if ($orderBy) {
			//ksort($orderBy);				
			$order = "\n ORDER BY \n\t".implode($orderBy, ",\n\t");
		}
						
		// Add 'order by' and 'limit' clauses
		$sql .= $order.$bean->getLimit();
				
		return $sql;				
	}

	/** Return the SQL column prototype (columnName columnType)
	 * @param $element object query element (Raw, Kpi, NA, TA ...)
	 * @return string the Sql column prototype
	 */
	private function getSqlColumnPrototype($element) {
		
		// Get SQL column type		
		switch($element->type) {
			case 'RAW':					
			case 'KPI':
				$type = 'real';
				break;
			case 'ta':
				$type = 'integer';					
				break;
			case 'na_axe3':																													
			case 'na':					
				$type = 'text';
				break;					
		}
		
		return $element->sqlColumnName.' '.$type;		
	}
	
	/** Create SQL query
	 * @param $query : the query object
	 * @param string $graphTaAxis if this is a query for graph contains the TA x-axis (Day, Week ...)
	 * @return object - contain the sql query and column header labels
	 */	
	public function createSqlQuery($query, $graphTaAxis = null) {								
		$orderBy = array();
		
		try {
			// Get all productIds from query
			$productIds = $this->getQueryProducts($query);			
			
			$nbProduct = count($productIds);
			
			// No product
			if ($nbProduct == 0) {
				// Empty query ...display an error message
				return $this->preParsingQuery($query);				
				 				
			// If this is a single product query
			}else if ($nbProduct < 2) {
				$isMultiProdQuery = false;
				
				// Compute SQL query for this single product
				$bean = $this->createSqlQueryForSingleProd($query, $graphTaAxis, $isMultiProdQuery, $productIds[0]);
					
				// Set the productId where the query will be execute, for mono product queries if all raw/kpi belongs to the same slave product, the query should be execute on this slave product
				$bean->setProductIdWhereQueryIsExecute($productIds[0]);								
			} else {
				$isMultiProdQuery = true;
				
				// Get master product id
				$masterProductId = ProductModel::getIdMaster();
												
				// Get product labels
				$productLabels = ProductModel::getProductsLabel();
					
				// This is a multi product query, for each product...										
				foreach($productIds as $productId) {
					// Compute the SQL query
					$bean = $this->createSqlQueryForSingleProd($query, $graphTaAxis, $isMultiProdQuery, $productId);
					
					// Get product name
					$productName = $productLabels[$productId];
						
					// If this product is not the master, add dblink for this query
					if ($productId != $masterProductId) {												
						
						// Add dblink to the Sql query
						$this->addDblink($bean, $productId, $productName);	
					}
					
					// Add a comment in the query
					$comment = "\n/* --- Query for product: $productName --- */\n\n";
					
					// Save the query for the current product
					$sql[] = $comment.'('.$bean->getSql().')';
						
				}
				
				// Make a SQL union with all queries
				$sql = implode(" \n\nUNION \n", $sql);
				
				// Add gobal query to hold each product queries
				$sql = $this->computeMultiProdGroupBy($bean, $sql);				
					
				// Save the Sql query in the bean object
				$bean->setSql($sql);
				
				// For multiproduct query, the query is execute on master 
				$bean->setProductIdWhereQueryIsExecute(ProductModel::getIdMaster());
			}
			
			return $bean;
			
		} catch(Exception $e) {
			// Catch undefined error and return a bean object with error message and error number = 0
			if (!isset($bean)) {
				// Create a new bean object if not already done
				$bean = new QueryBean();
			}

			// Error 0 : Undefined error
			$bean->setErrorNumber(0);
			$bean->setErrorMessage('Undefined error: '.$e->getMessage());

			return $bean;
		}								
	}
	
	/** Get NA3 SQL value
	 * @param $na3List - NA3 list
	 * @param $na - NA we want the value
	 * @param $firstTableName - name of the first data table
	 * @param $currentProductId - current product id
	 * @return SQL string to get the NA3 value
	 */	
	private function getNa3Value($na3List, $na, $firstTableName, $currentProductId) {
		$id = $na->id;
		
		// For filter with 'In' or 'Not in' operator the filter is apply on 'code'
		if (isset($na->isFilter) && ($na->operator == 'In' || $na->operator == 'Not in')) {
		 	$na->valueType = 'code';
		}
								
		if (in_array($id, $na3List)) {
								
			// If this is a NA3 min, the data id is available in the table
			if ($na->valueType == 'code') {			// Display code
				$value = $firstTableName.'.'.$id;
			
//			} elseif($na->valueType == 'label') {	// Display label
//				$value = '(SELECT eor_label FROM edw_object_ref WHERE eor_id = '.$firstTableName.'.'.$id.' LIMIT 1)';
				
			} else {	 // Display label or code if not label found
				$value = "COALESCE((SELECT eor_label FROM edw_object_ref WHERE eor_id=".$firstTableName.".".$id." AND eor_obj_type='".$id."' LIMIT 1),".$firstTableName.".".$id.")";
			}
		} else {
			// If this is a NA3 parent, id is not available in the table, get its label using the get_na_parent_label function
			$row = $this->getNa3minFromList($na3List, $currentProductId);
			$minLevel = $row['agregation'];
			$fam = $row['family'];
			
			if ($na->valueType == 'code') {
				$value = "(SELECT get_na_parent_id($firstTableName.$minLevel, '$minLevel', '$id', '$fam'))";						 															
			} else {
				$value = "COALESCE((SELECT get_na_parent_label($firstTableName.$minLevel, '$minLevel', '$id', '$fam')), (SELECT get_na_parent_id($firstTableName.$minLevel, '$minLevel', '$id', '$fam')))";
			}
		}	
		
		return $value;	
	}
	
	/** Get NA SQL value
	 * @param $naMin - the na min
	 * @param $na - the current na
	 * @param $bean - query object
	 * @return SQL string to get the NA3 value
	 */		
	private function getNaValue($naMin, $na, $bean) {		
		$id = $na->id;
		
		// For filter with 'In' or 'Not in' operator the filter is apply on 'code'
		if (isset($na->isFilter) && ($na->operator == 'In' || $na->operator == 'Not in')) {
		 	$na->valueType = 'code';
		}
					
		// If this is the NA min for this query
		if ($naMin == $id) {
			// The value is available in the edw_object_ref
			if ($na->valueType == 'code') {			// Display code
				$value = 'edw_object_ref.eor_id';
			
//			} elseif ($na->valueType == 'label') {	// Display label
//				$value = 'edw_object_ref.eor_label';
				
			} else {
				$value = 'COALESCE(edw_object_ref.eor_label, edw_object_ref.eor_id)';	// Display label or code (if no label found)
			}						
			
		// This is not the NA min
		} else {
			// Get its label using the get_na_parent_label SQL function			
			$fam = $bean->getRawkpis(); 	//$bean->getSelectedElements();
			$fam = $fam[0]->familyId;
			
			if ($na->valueType == 'code') {
				$value = "(SELECT get_na_parent_id(eor_id, '$naMin', '$id', '$fam'))";
			} else {
				$value = "COALESCE((SELECT get_na_parent_label(eor_id, '$naMin', '$id', '$fam')), (SELECT get_na_parent_id(eor_id, '$naMin', '$id', '$fam')))";
			}
		}
		return $value;
	}
	
	private function computeNaElements($bean, $query, &$colById, &$columnHeader, &$idLabel, &$select_list) {
		$orderBy = $bean->getOrderBy();
		$na3 = $bean->getNa3List();
		$groupBy = $bean->getGroupBy();
		
		foreach ($bean->getNaElements() as $na) {
			$id = $na->id;
			
			// Get the first join table name
			$firstRawKpis = $bean->getFirstRawKpiByProduct();
			$firstRawKpi = $firstRawKpis[$bean->getCurrentProductId()];												
			$firstTableName = $this->get_group_table($firstRawKpi->familyId, $firstRawKpi->productId);
			$firstTableName.='_'.strtolower($firstRawKpi->type);								
			$firstTableName.= '_'.$bean->getNaMin().($na3[$firstRawKpi->familyId]?'_'.$na3[$firstRawKpi->familyId]:'').'_'.$bean->getTaMin();
				
			
			// This is a na3				
			if ($na->type == 'na_axe3') {					
				// Get NA3 value
				$value = $this->getNa3Value($na3, $na, $firstTableName, $bean->getCurrentProductId());
				$isNaMin = false;
			} else {
				// Get the NA value
				$isNaMin = ($id == $bean->getNaMin());
				$value = $this->getNaValue($bean->getNaMin(), $na, $bean);					 					
			}
			
			// Save the column value for filters			
			$colById[$na->id] = $value;

			if (!isset($na->isFilter)) {			
				// Add aggregate function, if "Disable functions" button is not active 
				// and if this is not a multi product query, for multi product queries, group by is added later in the global query 
				if (isset($na->function) && $na->function && $query->select->disableFunctions !== true && !$bean->getIsMultiProduct()) {
					$value = $this->getFunction($na->function).'('.$value.')';
				}
														
				// Add a unique name for this column
				// 11/10/2012 GFS
				// BZ#29121 : Modification de la construction des alias
				$na->sqlColumnName = 'alias_'.$id.$idLabel++;
				
				// Add to group by
				if (isset($na->group) && $na->group) {$groupBy[$na->index] = $na->sqlColumnName;}																											

				// 11/10/2012 GFS
				// BZ#29121 : Modification de la construction des alias
				$value.= ' AS '.$na->sqlColumnName;
														
 				// If this is a query for table result: include NA in the select part (for graph display do not include all TA elements, only TA element for the X axis)
 				if ($bean->getGraphTaAxis() && $isNaMin) { 												
					$na->visible = true;					// For graph force the NA min visible
					 							 				
	 				// Add column label		
					$columnHeader[-1] = $na;							 					
	 				$select_list[-1] = $value;				// index = -1 for NA and -2 for TA, then for RAW and KPI index will be 1,2,3,4 ... after a ksort on the keys, elements will be in the right order TA, NA and RAWs/KPIs -> this is important for graph display (the two first data are not displayed)
	 				
				} else {												
					if ($bean->getGraphTaAxis()) {						// for graph hide NA other than na min
						$na->visible = false;
					}						
					
					// Add column label								
					$columnHeader[$na->index] = $na;													
	 				$select_list[$na->index] = $value;												
				}	
								
				// Add to order by
				if (isset($na->order) && $na->order) {$orderBy[$na->index] = $na->sqlColumnName.' '.$this->getOrder($na->order);}
				
				$bean->setGroupBy($groupBy);
				$bean->setOrderBy($orderBy);									
			}
		}
	}
	
	private function computeTaElements($bean, $query, &$colById, &$columnHeader, &$idLabel, &$select_list) {
		$orderBy = $bean->getOrderBy();
		$groupBy = $bean->getGroupBy();
		
		foreach ($bean->getTaElements() as $element) {						
			$ta_list[] = $element->id;
			$value = '{mainTable}.'.$element->id;			// maintable will be compute later
			
			// Save the column value for filters
			$colById[$element->id] = $value;
											
			// Include element in select only if this is not a filter and for graph if this is the selected x-axis
			$isXAxis = (strtolower($bean->getGraphTaAxis()) === $element->id);
						
			if (!isset($element->isFilter)) {											
				
				// Add aggregate function if "Disable functions" button is not active
				// and if this is not a multi product query, for multi product queries, group by is added later in the global query
				if (isset($element->function) && $element->function && $query->select->disableFunctions !== true && !$bean->getIsMultiProduct()) {$value = $this->getFunction($element->function).'('.$value.')';}
														
				// Add a unique name for this column	
				// 11/10/2012 GFS
				// BZ#29121 : Modification de la construction des alias
				$element->sqlColumnName = 'alias_'.$element->id.$idLabel++;
				
				// Add to group by
				if (isset($element->group) && $element->group) {$groupBy[$element->index] = $element->sqlColumnName;}
				
				// Add to order by
				if (isset($element->order) && $element->order) {$orderBy[$element->index] = $element->sqlColumnName.' '.$this->getOrder($element->order);}

				// 11/10/2012 GFS
				// BZ#29121 : Modification de la construction des alias
				//$value.= ' AS alias_'.$element->id.$idLabel++;					
				$value.= ' AS '.$element->sqlColumnName;
				
				// Add the column in the column list
						
				if ($bean->getGraphTaAxis() && $isXAxis) {
					// If this is the X Axis include the TA before raw and kpi
					// index = -1 for NA and -2 for TA, then for RAW and KPI index will be 1,2,3,4 ... after a ksort on the keys, elements will be in the right order TA, NA and RAWs/KPIs -> this is important for graph display (the two first data are not displayed)						
					$columnHeader[-2] = $element;								
					$select_list[-2] = $value;
					
					$element->visible = true;	// If this is the X axis force visible = true	
				} else {
					if ($bean->getGraphTaAxis()) {							// If this is a query for graph but this is not the X axis ...hide the element
						$element->visible = false;
					}
																	
					$columnHeader[$element->index] = $element;		
					$select_list[$element->index] = $value;												
				}	
									
			}
		}
		$bean->setGroupBy($groupBy);
		$bean->setOrderBy($orderBy);						
	}

	private function computeRawKpi($bean, $query, &$columnHeader, &$idLabel, &$select_list, &$fromTable, &$where, &$mainTable) {		
		$na3 = $bean->getNa3List();
		$refTable = array();
		$previousTableName = '';
		$groupBy = $bean->getGroupBy();
		$orderBy = $bean->getOrderBy();
		
		foreach($bean->getRawkpis() as $element) {
			// Add the element to the select list									
			$value = $element->name;
			
			// If this is a multiproduct query, and this element not belongs to the current product id
			if ($bean->getIsMultiProduct() && $element->productId != $bean->getCurrentProductId()) {
														
				// If this is a filter for another product ...skip it !
				if (isset($element->isFilter)) {						
					continue;
				}
								
				// Add the element to the column Header	
				$columnHeader[$element->index] = $element;	
				
				// Add a unique name for this column
				// 11/10/2012 GFS
				// BZ#29121 : Modification de la construction des alias
				$element->sqlColumnName = 'alias_'.$element->name.$idLabel++;
				$value= 'CAST(null AS real) AS '.$element->sqlColumnName;					
									
				// Add the column in the SELECT column list
				$select_list[$element->index] = $value;		
				
				// Compute group by
				if (isset($element->group) && $element->group) {$groupBy[$element->index] = $element->sqlColumnName;}
				
				// Add to order by
				if (isset($element->order) && $element->order) {$orderBy[$element->index] = $element->sqlColumnName.' '.$this->getOrder($element->order);}
									
				// Skip to next element
				continue; 		
			}
					
			// Add one ref table join for each product
			if (!isset($refTable[$element->productId])) {
				$refTable[$element->productId] = get_object_ref_from_family($element->familyId, $element->productId);											
				$fromTable[] = $refTable[$element->productId];
				$where[] = ' ('.$refTable[$element->productId].'.eor_on_off != 0 AND '.$refTable[$element->productId].'.eor_obj_type=\''.$bean->getNaMin().'\')';
			}			
								
			// Add one group table join for each family
			if (!isset($fromTable[$element->familyId.'_'.$element->type])) {				
				$tableName = $this->get_group_table($element->familyId, $element->productId);
				$tableName.='_'.strtolower($element->type);								
				$tableName.='_'.$bean->getNaMin().($na3[$element->familyId]?'_'.$na3[$element->familyId]:'').'_'.$bean->getTaMin();
				$tableNameByFamily[$element->familyId.'_'.$element->type] = $tableName;
				
				if (!$previousTableName) {
					// This is the first table join: add join on NA Min
					$fromTable[$element->familyId.'_'.$element->type] = 'INNER JOIN '.$tableName.' ON ('.$refTable[$element->productId].'.eor_id = '.$tableName.'.'.$bean->getNaMin().')';
				} else {
					// This is not the first join: add join on NA Min, TA min and NA3 if needed (if a NA3 is displayed)						
					$na3Join = $na3[$element->familyId] && $bean->getNa3Elements()?' AND '.$previousTableName.'.'.$na3[$element->familyId].'='.$tableName.'.'.$na3[$element->familyId]:'';
					$fromTable[$element->familyId.'_'.$element->type] = 'FULL OUTER JOIN '.$tableName.' ON ('.
							$refTable[$element->productId].'.eor_id = '.$tableName.'.'.$bean->getNaMin().' AND '.
							$previousTableName.'.'.$bean->getTaMin().'='.$tableName.'.'.$bean->getTaMin().$na3Join.')';
				}
				
				$previousTableName = $tableName;
				
				// Save the first join table name
				if (!isset($mainTable)) {
					$mainTable = $tableNameByFamily[$element->familyId.'_'.$element->type];					
				}																			
			}	
			
			// If this element is not a filter
			if (!isset($element->isFilter)) {		
				$columnHeader[$element->index] = $element;			
					
				// Add table name
				$value = $tableNameByFamily[$element->familyId.'_'.$element->type].'.'.$value;	
											
				// Add aggregate function if "Disable functions" button is not active
				// and if this is not a multi product query, for multi product queries, group by is added later in the global query
				if (isset($element->function) && $element->function && $query->select->disableFunctions !== true && !$bean->getIsMultiProduct()) {$value = $this->getFunction($element->function).'('.$value.')';}																				
				
				// Add a unique name for this column
				// 11/10/2012 GFS
				// BZ#29121 : Modification de la construction des alias
				$element->sqlColumnName = 'alias_'.$element->name.$idLabel++;
				
				// Compute group by
				if (isset($element->group) && $element->group) {$groupBy[$element->index] = $element->sqlColumnName;}
				
				// Add to order by
				if (isset($element->order) && $element->order) {$orderBy[$element->index] = $element->sqlColumnName.' '.$this->getOrder($element->order);}

				// 11/10/2012 GFS
				// BZ#29121 : Modification de la construction des alias
				$value.= ' AS '.$element->sqlColumnName;					
				
				// Add the column in the column list
				$select_list[$element->index] = $value;				
			}

		}	
		$bean->setGroupBy($groupBy);
		$bean->setOrderBy($orderBy);	
	}
	
	private function computeFilters($bean, $query, &$colById, &$operators, &$limit) {
		$limit='';
		
		// For each filter element						
		if (isset($query->filters) && isset($query->filters->data)) {
			foreach($query->filters->data as $element) {									
					
				// Set a default value for operator (from the API this value is not forbidden)
				if (!isset($element->operator)) {
					$element->operator = '';
				}
				
				if (!isset($element->enable) || $element->enable) {
					if ($element->type == 'na' || $element->type == 'ta' || $element->type == 'na_axe3') {
						$name = $colById[$element->id];
					} else if($element->type == 'RAW' || $element->type == 'KPI'){
						// If this is a filter on a RAW/KPI for another product than the current one 
						if ($bean->getIsMultiProduct() && $element->productId != $bean->getCurrentProductId()) {
							$name = 'null';
						} else {								
							$name = $element->name;
						}
					}	
					
					// If this is not the max. nb. result filter							
					if (!($element->type == 'sys' && $element->id == 'maxfilter')) {																
						$operators[] = $this->getFilter($name, $element);
						$operators[] = ' '.((isset($element->connector) && $element->connector)?"\n\t".$element->connector:"\n\t AND").' ';
					} else {
						if (isset($element->value)) {
							$limit = "\n LIMIT ".$element->value;
							$bean->setLimit($limit);
						}
					}			
				}
			}
		}
	}
	
	public function computeSql($bean, $query, $columnHeader, $operators, $limit, $select_list, $fromTable, $where, $mainTable) {
		$groupBy = $bean->getGroupBy();
		$orderBy = $bean->getOrderBy();
		
		// remove empty TA rows
		$where[] = $mainTable.'.'.$bean->getTaMin().' IS NOT NULL';
		// 30/09/2013 GFS - Bug 36748 - [SUP][T&A CB][#38959][MCI] : inadequate Virtual_Cell label
		$where[] = "{$mainTable}.{$bean->getNaMin()} NOT ILIKE 'virtual_%'";
		
		// Sort selected elements 		 				
		ksort($select_list);
		$bean->setIsDistinct(isset($query->select->distinct) && $query->select->distinct);
		
		$sql = ($bean->getIsDistinct())?"SELECT DISTINCT \n\t":"SELECT \n\t";
		$sql.= implode(",\n\t", $select_list);
		$sql.= "\n FROM \n\t";
		$sql.= implode(" \n\t", $fromTable);
		$sql.= " \nWHERE \n\t".implode("\n\t AND ", $where);
		
		// Add user filters
		if ($operators) {
			// Remove the last "AND" or "OR"
			array_pop($operators);
			$sql.= "\n\t AND (".implode('', $operators).')';
		}
		
		// Add group by, if the button "Disable functions" is not active
		if ($groupBy && $query->select->disableFunctions !== true) {
			// Sort keys to keep user order
			ksort($groupBy);
			
			// Save group by (in case of multiprod query)
			$bean->setUserGroupBy("\n GROUP BY \n\t".implode($groupBy, ",\n\t"));
			
			// Add group by to the SQL query if this is not a multi product query, for multi product group by is added later in the gloabl query	
			if (!$bean->getIsMultiProduct()) {
				$sql.= $bean->getUserGroupBy();
			}
		} else {				
			// No user group by
			$bean->setUserGroupBy(false);
		}
		
		
		// Compute order by	
		if ($orderBy) {	
			ksort($orderBy);
			$order = "\n ORDER BY \n\t".implode($orderBy, ",\n\t");		// Sort keys to keep user order
			$bean->setOrder($orderBy);
		} else {
			$order = '';				
		}
	
		// Add 'order by' and 'limit'
		if (!$bean->getIsMultiProduct()) {						 								
			$sql .= $order.$limit;
		}				
		
		// Set the main table
		$sql = str_replace('{mainTable}', $mainTable, $sql);						
		
		// Save column label
		ksort($columnHeader);
		$bean->setColumnHeader($columnHeader);
		
		// Save the query in the bean object
		$bean->setSql($sql);
		$bean->setGroupBy($groupBy);
		$bean->setOrderBy($orderBy);
	}
	
	/** Create SQL query for a single product
	 * @param $query : the query object
	 * @param string $graphTaAxis if this is a query for graph contains the TA x-axis (Day, Week ...)
	 * @param string $productId if this is multi product query (the current productId beeing computed) or null for a single product query
	 * @return object - contain the sql query and column header label
	 */	
	public function createSqlQueryForSingleProd($query, $graphTaAxis = null, $isMultiProduct, $currentProductId) {				
		$select_list = array(); $fromTable = array(); $where = array(); $columnHeader = array(); $operators = array(); $colById = array(); $idLabel = 0;			
		
		try {
						
			// Set default value if not set
			if (!isset($query->select->disableFunctions)) {				
				$query->select->disableFunctions = false;
			}
							
			// Pre-parsing query -> init the query bean object		
			$bean = $this->preParsingQuery($query);
								
			// There is an error ?
			if ($bean->getErrorMessage()) {
				return $bean;
			}						

			$bean->setCurrentProductId($currentProductId);
			$bean->setIsMultiProduct($isMultiProduct);
			
			// Compute NA axe 3
			$bean->setNa3List($this->computeNa3($bean, $currentProductId));
			
			// Compute the TA min
			$ta_listId = $bean->getTaElementsId();
			$elements = $bean->getTaElements();
			$element = ($elements)?$elements[0]:null;													
			$bean->setTaMin($this->detect_min_time($ta_listId, isset($element->productId)?$element->productId:null));
			
			// Set Graph X axis
			if ($graphTaAxis === 'tamin') {
				$graphTaAxis = $bean->getTaMin();
			}			
			$bean->setGraphTaAxis($graphTaAxis);
			
			
			// Five Steps to get a SQL query for the current product
			
			// 1 - Compute NA elements
			$this->computeNaElements($bean, $query, &$colById, &$columnHeader, &$idLabel, &$select_list);
											
			// 2 - Compute TA elements
			$this->computeTaElements($bean, $query, &$colById, &$columnHeader, &$idLabel, &$select_list);														
															
			// 3 - Compute RAW and KPI
			$this->computeRawKpi($bean, $query, &$columnHeader, &$idLabel, &$select_list, &$fromTable, &$where, &$mainTable);													
					
			// 4 - Compute filters
			$this->computeFilters($bean, $query, &$colById, &$operators, &$limit);
			
			// 5 - Compute SQL
			$this->computeSql($bean, $query, $columnHeader, $operators, $limit, $select_list, $fromTable, $where, $mainTable);
						
			return $bean;
			
		
		// Error management	
		} catch(Exception $e) {
			// Catch undefined error and return a bean object with error message and error number = 0
			if (!isset($bean)) {
				// Create a new bean object if not already done
				$bean = new QueryBean();
			}

			// Error 0 : Undefined error (should not be raised)
			$bean->setErrorNumber(0);
			$bean->setErrorMessage('Undefined error: '.$e->getMessage());

			return $bean;
		}								
	}	

	/** Compute NA axe3
	 * @param: $bean, the bean object
	 * @param: $currentProductId the current productId
	 * @return: array the NA axe3 for each family
	 */
	private function computeNa3($bean, $currentProductId) {		
		// Get NA axe3
		$na3 = $bean->getHasAxe3ByFamily();		
 
		// For each family
		foreach ($na3 as $family => $hasAxe3 ) {
			// If this family has an na3
			if ($hasAxe3) {				
				// Get the NA3 selected in the query
				$na3_list = $bean->getNa3Elements();
						
				// If there is selected NA3 in the query
				if ($na3_list && count($na3_list)>0) {
					// Set the NA to the smaller NA used in the query
					$row = $this->getNa3minFromList($na3_list, $currentProductId);
					$na3[$family] = $row['agregation']; 																			
				} else {
					// If no na3 in the query: Get the na3 min (the one with third_axis_default_level = 1)
					$na3minForFamily = getNAAxe3MinFromFamily($currentProductId, $family);
					if ($na3minForFamily) {
						$na3[$family] = $na3minForFamily[0];
					} 
					//$na3[$family] = get_network_aggregation_min_axe3_from_family($family, $currentProductId);
				}					 	
			}
		}		
		return $na3;		
	}
				
	/** Get group table by family
	 *  @param
	 *   $family - String, the family
	 * 	 $product - String, product id
	 *  @Return
	 *   String - the group table for family/product
	 */
	private function get_group_table($family, $product = '') {
		$db = Database::getConnection($product);		
		$query = "SELECT edw_group_table FROM sys_definition_group_table WHERE family='$family'";
		$row = $db->getrow($query);
		return $row['edw_group_table'];
	}
	
	/** Get all unique productIds used by this query
	 * @param $query, the query object
	 * @return return an array of id (with only one element for mono product queries)
	 */
	private function getQueryProducts($query) {

		$lstProd = array(); $productIds = array();
			
		// Get all query elements
		if (isset($query->filters) && isset($query->filters->data)) { 		 		
			$allElements = array_merge($query->select->data, $query->filters->data);					
		} else {
			$allElements = $query->select->data;			
		}
		 		 		
		// For each elements
		foreach ($allElements as $element) {
			// Check product id
			if (isset($element->productId) && $element->productId) {
				$lstProd[$element->productId] = true;				
			}
		}
				
		// Get products id list		
		foreach ($lstProd as $productId => $value) {
			$productIds[] = $productId;
		}

		return $productIds;
	}
	
	
	/** Pre parse the query 
	 * @param $query: the query object
	 */
	private function preParsingQuery($query) {
		
		// If there is some filters
		if (isset($query->filters) && isset($query->filters->data)) { 		 		
			$allElements = array_merge($query->select->data, $query->filters->data);
			
			// For each filter element
			foreach($query->filters->data as $element) {
				$element->isFilter = true;			
			}
			
			$filters = $query->filters;		
		} else {
			$allElements = $query->select->data;
			$filters = null;
		}
		
		$firstRawKpiByProduct = array(); $ta_listId = array(); $ta_list = array(); $ta_Idlist = array(); $na_list = array(); $na2_list = array(); $na_Idlist = array(); $na3_list = array(); $rawkpi_list = array(); $hasAxe3ByFam = array(); $labelById = array();
				
		// Create a new bean object
		$bean = new QueryBean();				
		
		// Save export options
		if (isset($query->exportOptions)) {
			$bean->setExportOptions($query->exportOptions);
		}		
							
		// For each elements	
		foreach ($allElements as $index => $element) {
			
			$element->index = $index;
			
			// Set a default value (visible is optional if the query comes from the API)
			if (!isset($element->visible)) {				
				$element->visible = true;
			}
																	
			// Set label for each element id
			if (!isset($element->label)) {
				$element->label = isset($element->name)?$element->name:$element->id;
			}
			
			$labelById[$element->id] = $element->label; 
												
			switch($element->type) {
				case 'RAW':					
				case 'KPI':
					
					// Save the first RAW or KPI for each product
					if (!isset($firstRawKpiByProduct[$element->productId])) {
						$firstRawKpiByProduct[$element->productId] = $element;
					}
					
					// Fill element name
					$this->getElementDataFam($element);
					$rawkpi_list[] = $element;					
					// is there an axe3 for this family
					if (!isset($hasAxe3ByFam[$element->familyId])) {						
						$hasAxe3ByFam[$element->familyId] = GetAxe3($element->familyId, $element->productId);						
					}																				
					break;
				case 'ta':
					$ta_list[] = $element;						// TA list
					$ta_listId[] = $element->id;															
					break;
				case 'na_axe3':												
					if (!isset($element->valueType)) {
						$element->valueType = '';
					}					
					$na3_list[] = $element->id;					// NA axe3					
					$na_list[] = $element;						// NA list
					break;														
				case 'na':					
					if (!isset($element->valueType)) {
						$element->valueType = '';											
					}
					$na2_list[] = $element;
					$na_list[] = $element;						// NA list
					$na_Idlist[] = $element->id;					
					break;											
			}
		}				
				
		// If no NA or TA ...exit
		if (!$ta_list || !$na2_list || !$rawkpi_list) {
			
			// No NA ...
			if (!$na2_list) {
				$bean->setErrorMessage('The query must include at least one network aggregation.');
				$bean->setErrorNumber(1);
			}
			
			// No TA ...
			if (!$ta_list) {
				$bean->setErrorMessage('The query must include at least one time aggregation.');
				$bean->setErrorNumber(2);
			}			
						
			// No RAW/KPI ...
			if (!$rawkpi_list) {
				$bean->setErrorMessage('The query must include at least one RAW counter or at least one KPI.');
				$bean->setErrorNumber(3);
			}		
					
			return $bean;
		}		
		
		// Compute NA min
		$naMin = $this->detect_min_network($na_Idlist, $rawkpi_list[0]->familyId, $rawkpi_list[0]->productId);
		
		// Fill the bean with the computed data
		$bean->setNaMin($naMin);								// The Na min
		$bean->setFirstRawKpiByProduct($firstRawKpiByProduct);	// Save the first RAW or KPI for each product
		$bean->setSelectedElements($query->select->data);		// Selected elements
		$bean->setFilterElements($filters?$filters->data:null);	// Filter elements
		$bean->setAllElements($allElements);					// All elements (RAW, KPI, TA, NA, NA axe3)
		$bean->setTaElements($ta_list);							// TA elements
		$bean->setTaElementsId($ta_listId);						// TA elements Id
		$bean->setNaElements($na_list);							// NA elements
		$bean->setNaIds($na_Idlist);							// TA elements
		$bean->setNa3Elements($na3_list);						// NA axe3 elements
		$bean->setRawkpis($rawkpi_list);						// RAW and KPI elements		
		$bean->setHasAxe3ByFamily($hasAxe3ByFam);				// Axe3 by family
		$bean->setGroupBy(array());
		$bean->setOrderBy(array());				
		return $bean;
	}

    /*
	* fonction qui determine à partir d'un table d'élément time quel est le plus petit
	* pour cela utilise les colonnes agregation_level et agregation_rank de la table sys_definition_network_agregation
	* @param : array contenant une liste d'élement time
	* @return :nom de l'élément le plus petit
	*/
    private function detect_min_time($array_time, $product) {
    	$db = Database::getConnection($product);
        $query = "SELECT agregation FROM sys_definition_time_agregation ORDER BY agregation_level ASC, agregation_rank ASC";
        $res = $db->getall($query);
        foreach ($res as $row) {
            $agregation = $row['agregation'];
            if (in_array($agregation, $array_time)) {
                return $agregation;
            }
        }
    }
	

	/** fonction qui determine à partir d'un table d'élément réseau quel est le plus petit
	* pour cela utilise les colonnes agregation_level et agregation_rank dela table sys_definition_network_agregation
	* @param : array contenant une liste d'élement réseau
	* @product: string family
	* @return :nom de l'élément le plus petit
	*/
    private function detect_min_network($array_network, $family, $product) {
        $query = "SELECT agregation FROM sys_definition_network_agregation WHERE family LIKE '$family' ORDER BY agregation_level ASC, agregation_rank ASC";
    	$db = Database::getConnection($product);		
        $res = $db->getall($query);
        $array_net = (is_array($array_network)?$array_network:array($array_network));
		foreach ($res as $row) {
            $agregation = $row['agregation'];
            if (in_array($agregation, $array_net))
                return $agregation;
        }
    }
	
	/** Get SQL function name
	 * @param $functionName String, function name
	 * @return SQL function name	 
	 */
	private function getFunction($functionName) {
		switch(strtolower($functionName)) {
			case 'sum':
				return 'sum';				
			case 'average':
				return 'avg';
			case 'maximum':
				return 'max';
			case 'minimum':
				return 'min';
			case 'count':
				return 'count';			
		}
		return '';
	}

	/** Get SQL order by
	 * @param $order String, order
	 * @return SQL order	 
	 */
	private function getOrder($order) {
		switch(strtolower($order)) {
			case 'ascending':
				return 'asc';				
			case 'descending':
				return 'desc';					
		}		
		return '';
	}
		
	/** Get SQL operator
	 * @param $elementName String, element to filter on 
	 * @param $operator String, operator
	 * @param $value, String - Filter value 
	 * @return SQL operator	 
	 */	
	private function getOperator($elementName, $operator, $value) {				
		switch($operator) {
			case '':			
			case 'equals to':				
				return "($elementName = $value)";			
			case 'not equals to':
				return "($elementName != $value)";				
			case 'less than':
				return "($elementName < $value)";
			case 'less than or equal':
				return "($elementName <= $value)";
			case 'greater than':
				return "($elementName > $value)";	
			case 'greater than or equal':
				return "($elementName >= $value)";
			case 'between':
				$el = explode(',', $value);
				return '('.$elementName.' BETWEEN '.$el[0].' AND '.$el[1].')';															
			case 'not between':
				$el = explode(',', $value);
				return '('.$elementName.' NOT BETWEEN '.$el[0].' AND '.$el[1].')';
			case 'is null':
				return "($elementName IS NULL)";
			case 'is not null':
				return "($elementName IS NOT NULL)";
			case 'is true':
				return "($elementName IS TRUE)";
			case 'is false':
				return "($elementName IS FALSE)";
			case 'in':				
				//$elementName = str_replace('get_na_parent_label', 'get_na_parent_id', $elementName);  	// For 'in' and 'not in' operator the search is done on element id
				return (count(explode(',',$value))>1)?"$elementName IN($value)":"$elementName = $value";							
			case 'not in':
				//$elementName = str_replace('get_na_parent_label', 'get_na_parent_id', $elementName);  	// For 'in' and 'not in' operator the search is done on element id
				return (count(explode(',',$value))>1)?"$elementName NOT IN($value)":"$elementName <> $value";							
			case 'starts with':
				$value = str_replace(array('\\','%','_'), array('\\\\','\%','\_'), $value);
				return "($elementName ILIKE '$value%')";
			case 'not starts with':
				$value = str_replace(array('\\','%','_'), array('\\\\','\%','\_'), $value);
				return "($elementName NOT ILIKE '$value%')";				
			case 'ends with':
				$value = str_replace(array('\\','%','_'), array('\\\\','\%','\_'), $value);
				return "($elementName ILIKE '%$value')";
			case 'not ends with':
				$value = str_replace(array('\\','%','_'), array('\\\\','\%','\_'), $value);
				return "($elementName NOT ILIKE '%$value')";				
			case 'contains':
				$value = str_replace(array('\\','%','_'), array('\\\\','\%','\_'), $value);
				return "($elementName ILIKE '%$value%')";
			case 'not contains':
				$value = str_replace(array('\\','%','_'), array('\\\\','\%','\_'), $value);
				return "($elementName NOT ILIKE '%$value%')";				
		}		
		return '';				
	}
	
	/** Compute SQL filter
	 * @param $elementName string the element name
	 * @param $element object the element to filter on
	 * @return string SQL filter
	 */
	private function getFilter($elementName, $element) {
		$value = $element->value;	
		// 10/04/2013 GFS - Bug 27396 - [REC][T&A CB 52018][TC#TA-57389][Query Builder]: The returned result is incorrect when filter with kpi/raw which its value is float	
		if (is_numeric($value) && fmod((float) $value, 1) !== 0) {
			$elementName .= "::numeric";
		}
		
		$operator = strtolower($element->operator);
		
		// $elementName = something like: table.day or table.day_bh or table.week ...
		$time = explode('.', $elementName);				
		
		// If this is a filter on a TA
		if ($element->type == 'ta') {
			
			// switch on TA type (day, hour ...)
			switch($time[1]) {
				case 'day':
	 			case 'day_bh':
					// Manage offset (floating date)
					$value = explode('today', $element->value);
					if (isset($value[1])) {
						$offset = $value[1]*-1;
						$value = "to_char(now() - '$offset days'::interval,'YYYYMMDD')::INT";
					} else {					
						// $element->value = something like: 2011-09-12T00:00:00			
						$value = explode('T', $element->value);
						$value = $value[0];
						$value = str_replace('-', '', $value);
					}
					break;
					
				// Hour format hh:mm
				case 'hour':													
					$value = substr($value,0,2);
					$elementName = "substring($elementName::TEXT from '..$')::INT"; 					
					break;
				
				// Week filter format yyyyww or ww
				case 'week':													
				case 'week_bh':
					// Manage ww format
					if (strlen($value) == 2) {											
						$elementName = "substring($elementName::TEXT from '..$')::INT";
						$value =  "'".$value."'";
					} 		
				// Month filter format yyyymm or mm
				case 'month':
				case 'month_bh':
					// Manage mm format
					if (strlen($value) == 2) {											
						$elementName = "substring($elementName::TEXT from '..$')::INT";
						$value =  "'".$value."'";
					} 															
					break;
			}
		
		// If this is a filter on NA
		} else if ($element->type == 'na' || $element->type == 'na_axe3') {
			//$value = str_replace("'", "''", $value);
			$value = pg_escape_string($value);
			
			// For 'in' and 'not in' operator parse value			
			if ($operator == 'in' || $operator == 'not in') {
				//$elementName = 'edw_object_ref.eor_id';					// For 'in' and 'not in' the filter is done on the NE code because the selection is done by the 'Network elements selection window'				
				if ($value) {								
					$value = "'".str_replace(",","','", $value)."'";													
				} 
			}						
		}
				
		// Get SQL filter string
		return $this->getOperator($elementName, $operator, $value);						
	}

	/* Get family for an element (RAW/KPI)
	 * @param element: the element (RAW or KPI)
	 */
	public function getElementDataFam($element) {
	    // database connection	
	    $database = DataBase::getConnection($element->productId);
		
	    if($database->getCnx()) {
			if ($element->type == 'KPI') {
				// get the kpi
				$kpiMod = new KpiModel();
				$kpiData = $kpiMod->getByIdFam($element->id, $database);
				$element->name = addslashes($kpiData["kpi_name"]);
				$element->familyId = addslashes($kpiData['family']);													 				
			} else {
				// get the raw
				$rawMod = new RawModel();
				$rawData = $rawMod->getByIdFam($element->id, $database);
				$element->name = addslashes($rawData["edw_field_name"]);
				$element->familyId = addslashes($rawData['family']);								
			}	
		}
	}

	/* Get the NA3 min from a list of NA3
	 * @param $na_list the na3 list
	 * @param $product the current product id
	*/
	function getNa3minFromList($na3_list,$product = '')
	{
		$naList = "'".implode("','", $na3_list)."'";
		
		$db = Database::getConnection( $product );
		$query = "SELECT agregation, family FROM sys_definition_network_agregation 
				  WHERE 
					axe = 3
					AND agregation IN($naList)
				  ORDER BY agregation_level ASC, agregation_rank ASC LIMIT 1";	
		
		$row = $db->getrow($query);
		if ($row) {
			return $row;
		} else {
			return false;
		}
	} 				
}

?>
