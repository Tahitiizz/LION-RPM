<?php

include_once('../../php/environnement_liens.php');
include_once('dao/models/NeModelBis.class.php');

// get search options JSON object
$searchOptions = '';
if (isset($_POST['filterOptions'])) {
	$searchOptions = json_decode(stripslashes($_POST['filterOptions']));
}
//var_dump($searchOptions);
$neModelBis = new NeModelBis();

$neList = $neModelBis->getFilteredNl($searchOptions->products, $searchOptions->limit, $searchOptions->text);

foreach($searchOptions->products as $product){
	$idProduct = $product->id;
	
}

if (!$neList[0]) {
	echo '<div class="qbNoElementFound"><span class="icoCancel">No element found.</span><br><span>Please refine your search criteria.</span></div>';
}
else {
	echo  '<table class="qbElementList" width="100%"><tr><td><b>Level</b></td><td width="10"></td></tr></table>';
	echo  '<div class="qbElementContainer"><table class="qbElementList" width="100%"><tr><td></td><td width="100%"></td></tr>';
	foreach ($neList as $ne){
		echo  '<tr id="'.$ne['agregation_name'].'" product_id="'.$idProduct.'" agregation_label="'.$ne['agregation_label'].'"data-product="'.$ne['product_id'].'"><td>'.$ne['family'].'</td><td class="qbElementProduct">'.preg_replace('/[^a-zA-Z0-9]/', '', $ne['agregation_label']).'</td><tr>';
	}
	echo '<tr><td>&nbsp;</td><td class="qbElementProduct">&nbsp;</td><tr>';
	echo '</table></div>';
}
?>
