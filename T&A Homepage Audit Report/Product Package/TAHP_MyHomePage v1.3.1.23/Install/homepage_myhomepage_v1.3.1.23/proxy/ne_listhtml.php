<?php

include_once('../../php/environnement_liens.php');
include_once('dao/models/NeModelBis.class.php');

// get search options JSON object
$searchOptions = '';
if (isset($_POST['filterOptions'])) {
	$searchOptions = json_decode(stripslashes($_POST['filterOptions']));
}
$roaming = false;
if(isset($_POST['roaming'])){
	$roaming = $_POST['roaming'];
	$childrenArray = array();
	$json = '{"data":[';
}

$neModelBis = new NeModelBis();
$neList = $neModelBis->getFilteredNe($searchOptions->products, $searchOptions->limit, $searchOptions->text);

if($roaming == false){
	if (!$neList[0]) {
		echo '<div class="qbNoElementFound"><span class="icoCancel">No element found.</span><br><span>Please refine your search criteria.</span></div>';
	}
	else {
		echo  '<table class="qbElementList" width="100%"><tr><td><b>Label</b></td><td><b>Level</b></td><td width="30%"><b>Product</b></td><td width="10"></td></tr></table>';
		echo  '<div class="qbElementContainer"><table class="qbElementList" width="100%"><tr><td></td><td width="30%"></td></tr>';
		foreach ($neList as $ne){
			echo  '<tr id="'.$ne['eor_id'].'" data-product="'.$ne['product_id'].'" agregation="'.$ne['agregation'].'" title="'.$ne['eor_label'].'"><td>'.$ne['eor_label'].'</td><td>'.$ne['agregation_label'].'</td><td class="qbElementProduct">'.preg_replace('/[^a-zA-Z0-9]/', '', $ne['sdp_label']).'</td><tr>';
		}
		echo '<tr><td>&nbsp;</td><td class="qbElementProduct">&nbsp;</td><tr>';
		echo '</table></div>';
	}
}else{
	foreach ($neList as $ne){
		$json .= '{"parent_id":"'.$ne['eor_id'].'","parent_label":"'.$ne['eor_label'].'"},';
	}
	$json = substr($json, 0, -1);
	echo $json.']}';

	
	
}

?>
