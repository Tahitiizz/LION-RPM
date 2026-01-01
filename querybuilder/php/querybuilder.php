<?php
/*
 * 28/07/2011 SPD1: Querybuilder facade  
 */

session_start();
include dirname( __FILE__ ).'/../../php/environnement_liens.php';
session_commit();

include_once dirname(__FILE__).'/../class/QbFacade.class.php';
include_once dirname(__FILE__).'/../class/GraphGenerator.class.php';

 
// If a method argument is passed -> call the method facade
if(isset($_GET['method'])) {		
	
	// Create the facade object
	$facade = new QbFacade();		
	
	// Call the method passed in the GET parameter
	if (method_exists($facade, $f=$_GET['method'])) {
								
		// call the method		
        call_user_func(array($facade,$f));
		return; 
    } 						
}

// if no argument passed -> display the GUI
//include_once dirname(__FILE__).'/querybuilderGUI.php';
	
?>