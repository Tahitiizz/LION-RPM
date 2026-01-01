<?php
/*
 * 22/09/2011 SPD1: Query data T&A API  
 */

include dirname( __FILE__ ).'/../../php/environnement_liens.php';

// Header to fix IE bug
//header('Cache-Control: no-cache, must-revalidate');
//header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Set content type to serve JSON
//header('Content-type: application/json');

/** function to manage PHP errors
 *  @param $errno string: error number
 *  @param $errstr string: error message
 *  @param $errfile string: file where occured the error
 *  @param $errline integer: error line 
 */
function exception_error_handler($errno, $errstr, $errfile, $errline ) {
	$e = new Exception("$errstr - Error number : $errno - File : $errfile - Line : $errline");
	$e->number = $errno;
	throw($e);
}
	
// If no data POST parameter, nothing to do
if(isset($_POST['data'])) {
	
	// PHP errors management function
	set_error_handler("exception_error_handler");
		
	// Query data model																			
	$dataMod = new QueryDataModel();
									
	// If a request is done for a JSON response (default type)
	if(!isset($_GET['type']) || $_GET['type'] == 'json') {
		 	// Get query (JSON string format)
            $query = stripslashes($_POST['data']);
            echo $dataMod->getJsonResponse($query);
	}
}

?>


