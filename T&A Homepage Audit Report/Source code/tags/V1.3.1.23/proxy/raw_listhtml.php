<?php
/*
 * 28/07/2011 SPD1: Querybuilder V2 - RAW list displayed in the left panel
 */

include_once('../../php/environnement_liens.php');
include_once('dao/querybuilderExtJS4/class/QbRawKpi.class.php');

// get search options JSON object
$searchOptions = '';
if (isset($_POST['filterOptions'])) {
	$searchOptions = json_decode(stripslashes($_POST['filterOptions']));
}

// Get RAWs
$qbRawKpi = new QbRawKpi();
$raws = $qbRawKpi->getRAWs($searchOptions);
unset($qbRawKpi);

// If no result display an error message
if (!$raws[0]) {
	echo '<div class="qbNoElementFound"><span class="icoCancel">No element found.</span><br><span>Please refine your search criteria.</span></div>';
	return true;
}

// Display RAW list
echo '<table class="qbElementList" width="100%"><tr><td><b>Label</b></td><td width="30%"><b>Product</b></td><td width="10"></td></tr></table>';
echo '<div class="qbElementContainer"><table class="qbElementList" width="100%"><tr><td></td><td width="30%"></td></tr>';
 
foreach ($raws as $raw){
	echo '<tr id="'.$raw['id'].'" data-product="'.$raw['sdp_id'].'" title="'.$raw['object_libelle'].'"><td>'.$raw['object_libelle'].'</td><td class="qbElementProduct">'.preg_replace('/[^a-zA-Z0-9 ]/', '', $raw['sdp_label']).'</td><tr>';
}

echo '<tr><td>&nbsp;</td><td class="qbElementProduct">&nbsp;</td><tr>';
echo '</table></div>';

?>
