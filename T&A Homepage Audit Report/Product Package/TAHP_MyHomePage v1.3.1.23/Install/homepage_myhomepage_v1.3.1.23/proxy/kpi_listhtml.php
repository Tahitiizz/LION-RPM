<?php
/*
 * 28/07/2011 SPD1: Querybuilder V2 - KPI list displayed in the left panel
 */

include_once('../../php/environnement_liens.php');
include_once('dao/querybuilderExtJS4/class/QbRawKpi.class.php');

// get search options JSON object
$searchOptions = '';
if (isset($_POST['filterOptions'])) {
	$searchOptions = json_decode(stripslashes($_POST['filterOptions']));
}

// Get KPIs
$qbRawKpi = new QbRawKpi();
$kpis = $qbRawKpi->getKPIs($searchOptions);
unset($qbRawKpi);

// If no result display an error message
if (!$kpis[0]) {
	echo '<div class="qbNoElementFound"><span class="icoCancel">No element found.</span><br><span>Please refine your search criteria.</span></div>';
	return true;
}

// Display KPI list
echo '<table class="qbElementList" width="100%"><tr><td><b>Label</b></td><td width="30%"><b>Product</b></td><td width="10"></td></tr></table>';
echo '<div class="qbElementContainer"><table class="qbElementList" width="100%"><tr><td></td><td width="30%"></td></tr>';
 
foreach ($kpis as $kpi){
	echo '<tr id="'.$kpi['id'].'" data-product="'.$kpi['sdp_id'].'" title="'.$kpi['object_libelle'].'"><td>'.$kpi['object_libelle'].'</td><td class="qbElementProduct">'.preg_replace('/[^a-zA-Z0-9 ]/', '', $kpi['sdp_label']).'</td><tr>';
}

echo '<tr><td>&nbsp;</td><td class="qbElementProduct">&nbsp;</td><tr>';
echo '</table></div>';

?>

