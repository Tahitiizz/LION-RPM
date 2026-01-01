<?php
/*
 * JGU2 : integre a la homepage du fait du bug bloquant des quotes resolu dans le QueryDataModel officiel.
 */

include dirname( __FILE__ ).'/../../../../../php/environnement_liens.php';
include_once dirname( __FILE__ ).'/../../../../../homepage/proxy/dao/models/QueryDataModel.class.php';

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
		echo $dataMod->getJsonResponse(stripslashes($_POST['data']));
	}
}

?>