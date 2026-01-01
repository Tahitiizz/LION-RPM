<?php

include_once('../../php/environnement_liens.php');
include_once('dao/models/NeModelBis.class.php');

$searchOptions = '';
if (isset($_POST['nelist']) && isset($_POST['na']) && isset($_POST['product'])) {
	$searchOptions->nelist = $_POST['nelist'];
	$searchOptions->na = $_POST['na'];
	$searchOptions->product = $_POST['product'];
	if(isset($_POST['order'])){
		$searchOptions->order = $_POST['order'];
	}
}


$neModelBis = new NeModelBis();
$neList = $neModelBis->getNeLabels($searchOptions->nelist, $searchOptions->na, $searchOptions->product,$searchOptions->order);

echo json_encode($neList);

?>
