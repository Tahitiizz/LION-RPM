<?php

include_once "Bean.class.php";

class QueryBean extends Bean {    	
	protected $selectedElements;			// List of selected elements
	protected $filterElements;				// List of filter elements
	protected $allElements;					// All elements (RAW, KPI, TA, NA and NA axe3)
	protected $taElements;					// TA elements
	protected $naElements;					// NA elements
	protected $na3Elements;					// NA axe3 elements
	protected $rawkpis;						// RAW and KPI elements
	protected $hasAxe3ByFamily;				// Axe3 by family
	protected $isError;						// Boolean true: if there is an error
	protected $errorMessage;				// Error message
	protected $errorNumber;					// Error number
}

?>