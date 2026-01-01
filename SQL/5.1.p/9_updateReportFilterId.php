<?php
/*
 * 
 * Update the product id of the "sds_sort_by" and "sds_filter_id" field of selecteurs
 * 
 */

include_once(dirname(__FILE__)."/../../php/environnement_liens.php");

$database = Database::getConnection();

// Get list of "selecteurs" with "id", "sort by" and "filter" information
$query = "SELECT sds_id_selecteur, sds_sort_by, sds_filter_id FROM sys_definition_selecteur";
$result = $database->getAll($query);

foreach($result as $row) {
	$request = array();
	
	// check if a sort by is set
	if (isset($row[sds_sort_by]) && strlen($row[sds_sort_by]) > 0) {
		$sortBy = split("@", $row[sds_sort_by]);
		
		if (count($sortBy) == 3) { // there must be 3 informations (0 - type ; 1 - id ; 2 - idProduct)
			// retrieve correct product id from "sys_pauto_config" table
			$sortBy[2] = $database->getOne("SELECT id_product FROM sys_pauto_config WHERE class_object = '".$sortBy[0]."' AND id_elem = '".$sortBy[1]."'");

			$newSortBy = implode("@", $sortBy);
			if ($newSortBy != $row[sds_sort_by]) {
				$request[] = "sds_sort_by = '$newSortBy'";
			}
			
			// echo $row[sds_sort_by]." => ".$newSortBy."\n\r";
		}
	}
	
	// check if a filter is set
	if (isset($row[sds_filter_id]) && strlen($row[sds_filter_id]) > 0) {
		$filter = split("@", $row[sds_filter_id]);
		
		// retrieve correct product id from "sys_pauto_config" table
		$filter[2] = $database->getOne("SELECT id_product FROM sys_pauto_config WHERE class_object = '".$filter[0]."' AND id_elem = '".$filter[1]."'");
		
		$newFilter = implode("@", $filter);
		if ($newFilter != $row[sds_filter_id]) {
			$request[] = "sds_filter_id = '$newFilter'";
		}
		
		// echo $row[sds_filter_id]." => ".$newFilter."\n\r";
	}
	
	// update "sds_sort_by" and "sds_filter_id" fields with correct productId value
	if (count($request) > 0) {
		$fields = implode(",", $request);
		$queryUpdate = "UPDATE sys_definition_selecteur SET $fields WHERE sds_id_selecteur = ".$row[sds_id_selecteur];
		echo $queryUpdate."\n\r";
		$database->execute($queryUpdate);
	}
}

// close db connection
$database->close();

?>