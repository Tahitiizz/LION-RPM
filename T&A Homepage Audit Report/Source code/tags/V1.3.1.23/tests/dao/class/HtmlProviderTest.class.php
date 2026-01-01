<?php

class HtmlProviderTest {



	public function getKpiList($parameters = array()) {
		$htmlresult = '';
		$searchOptions = '';
		if (isset($parameters['filterOptions'])) {
			$searchOptions = json_decode(stripslashes($parameters['filterOptions']));
		}
		$qbRawKpi = new QbRawKpi();
		$kpis = $qbRawKpi->getKPIs($searchOptions);
		unset($qbRawKpi);
		if (!$kpis[0]) {
			$htmlresult .= '<div class="qbNoElementFound"><span class="icoCancel">No element found.</span><br><span>Please refine your search criteria.</span></div>';
		}
		else {
			$htmlresult .=  '<table class="qbElementList" width="100%"><tr><td><b>Label</b></td><td width="30%"><b>Product</b></td><td width="10"></td></tr></table>';
			$htmlresult .=  '<div class="qbElementContainer"><table class="qbElementList" width="100%"><tr><td></td><td width="30%"></td></tr>';
			foreach ($kpis as $kpi){
				$htmlresult .=  '<tr id="'.$kpi['id'].'" data-product="'.$kpi['sdp_id'].'"><td>'.$kpi['object_libelle'].'</td><td class="qbElementProduct">'.preg_replace('/[^a-zA-Z0-9]/', '', $kpi['sdp_label']).'</td><tr>';
			}
			$htmlresult .=  '<tr><td>&nbsp;</td><td class="qbElementProduct">&nbsp;</td><tr>';
			$htmlresult .=  '</table></div>';
		}
		return $htmlresult;
	}
}
?>


