<?php

require('../fpdf/fpdf.php');

if(!isset($_SESSION)) session_start();

include_once($repertoire_physique_niveau0.'php/environnement_liens.php');
include_once('dao/models/AlarmModelHomepage.class.php');
// Get the user name
$database_connection = new DataBaseConnection();
$query = "SELECT login
			FROM users 
			WHERE id_user='{$_SESSION['id_user']}'
			LIMIT 1;";
$userName = $database_connection->getOne($query);

// If the user is client_user or astellia_user, he has an admin access to the homepage
$isAdmin = ($userName == 'astellia_admin' || $userName == 'astellia_user') ? true : false;

$task = '';
if (isset($_POST['task'])){
	// Get task from Ext JS
	$task = $_POST['task'];
}

switch($task){
	// Initialize the user configuration files
	case 'EXPORT':
		export();
		break;

	// Get the widgets to export
	case 'GET_WIDGETS':
		getWidgets();
		break;

	default:
		echo 'failure';
		break;
}

function export() {
	// Class managing a PDF document
	class PDF extends FPDF
	{
		// Header
		function Header() {	
			$this->SetY(5);	
			$this->Image('../images/LogoAstellia.png', null, null, 23, 8);
						
			// Date
			$this->SetXY(70, 5);	
			$this->SetFont('Arial', 'I', 10);
			$this->Cell(0, 7, date('F j Y, h:i a'), 0, 1);
			
			// Title
			$this->SetFont('Arial', 'B', 12);
			$w = $this->GetStringWidth($_POST['tab']) + 6;
			$this->Line(10, 15.5, (297 - $w) / 2 - 5, 15.5);
			$this->SetX((297 - $w) / 2);
			$this->Cell($w, 7, $_POST['tab'], 0, 0, 'C', 0);
			$this->Line((297 + $w) / 2 + 5, 15.5, 250, 15.5);			
			
			// Logo
			$this->SetX(255);
			$this->Image('../../images/bandeau/logo_operateur.jpg');
			
			$this->Ln(4);
		}

		// Footer
		function Footer()
		{
			// 10 mm to the bottom
			$this->SetY(-10);
			$this->SetFont('Arial', 'I', 10);

			// Page number			
			$this->SetFillColor(255, 255, 204);
			$this->Cell(0, 7, 'Powered by Trending & Aggregation', 0, 0, 'L', 1);
			$this->Cell(0, 7, 'Page '.$this->PageNo().'/{nb}', 0, 0, 'R');
		}
	}

	if (isset($_POST['widgets'])) {
		$widgets = json_decode(stripslashes($_POST['widgets']));
		$a = session_id();
		if(empty($a)) session_start();

		// Directory where the PNGs are generated
		$dir = '../files/'.date('YmdHis').session_id();
		if(!is_dir($dir)) {
			mkdir($dir, 0777, true);
		}

		if (($_POST['templateId'] != 'template5') &&
			($_POST['templateId'] != 'template6') && 
			($_POST['templateId'] != 'template7')&& 
			($_POST['templateId'] != 'template9'&&
			$_POST['templateId'] != 'template10')) {
			// Create the PDF
			$pdf = new PDF('L', 'mm', 'A4');
			$pdf->AliasNbPages();
			$pdf->AddPage();
	
			// Add the chart
			$pdf->SetFont('Arial', '', 8);
			if ($_POST['templateId'] == 'template1') {
				// Template 1 : 2 x 4 gauges and 1 period
				for ($i = 0; $i < 8; $i++) {				
					// Display the gauges				
					if ($i < 4) $pdf->SetY(27); else $pdf->SetY(67);
					$pdf->SetX(10 + ($i % 4) * 69);
					
					$label1 = isset($widgets->widget[$i]->label1) ? $widgets->widget[$i]->label1 : '';
					$label2 = isset($widgets->widget[$i]->label2) ? $widgets->widget[$i]->label2 : '';
					$label3 = isset($widgets->widget[$i]->label3) ? $widgets->widget[$i]->label3 : '';
					
					$pdf->Cell(0, 3, $widgets->widget[$i]->title.': '.$widgets->widget[$i]->value, 0, 2);				
					$pdf->Cell(0, 3, $label1, 0, 2);
					$pdf->Cell(0, 3, $label2, 0, 2);
					$pdf->Cell(0, 3, $label3, 0, 2);
						
					$data = $widgets->widget[$i]->dataurl;
					if (isset($data)) {
						$encodedData = substr($data, strpos($data, ',') + 1);
						$encodedData = str_replace(' ', '+', $encodedData);
						$decocedData = base64_decode($encodedData);
							
						$imgFile = $dir.'/'.$widgets->widget[$i]->id.'.png';
						file_put_contents($imgFile, $decocedData);
						$pdf->Image($imgFile, null, null, 50, 25);
					}			
				}
				
				// Display the period
				$pdf->Ln(5);
				$pdf->Cell(0, 10, $widgets->widget[$i]->title, 0, 1);	
				$data = $widgets->widget[$i]->dataurl;
				$encodedData = substr($data, strpos($data, ',') + 1);
				$encodedData = str_replace(' ', '+', $encodedData);
				$decocedData = base64_decode($encodedData);
					
				$imgFile = $dir.'/'.$widgets->widget[$i]->id.'.png';
				file_put_contents($imgFile, $decocedData);
				$pdf->Image($imgFile, null, null, 272, 70);				
			} else if ($_POST['templateId'] == 'template2') {
				// Template 2 : 3 x (1 gauge + 1 period)
				for ($i = 0; $i < 3; $i++) {
					$pdf->SetY(27 + $i * 53);
					
					// Gauge
					$label1 = isset($widgets->widget[$i * 2]->label1) ? $widgets->widget[$i * 2]->label1 : '';
					$label2 = isset($widgets->widget[$i * 2]->label2) ? $widgets->widget[$i * 2]->label2 : '';
					$label3 = isset($widgets->widget[$i * 2]->label3) ? $widgets->widget[$i * 2]->label3 : '';
					
					$pdf->Cell(0, 3, $widgets->widget[$i * 2]->title.': '.$widgets->widget[$i * 2]->value, 0, 2);				
					$pdf->Cell(0, 3, $label1, 0, 1);
					$pdf->Cell(0, 3, $label2, 0, 1);
					$pdf->Cell(0, 3, $label3, 0, 1);
					
					$data = $widgets->widget[$i * 2]->dataurl;
					if (isset($data)) {
						$encodedData = substr($data, strpos($data, ',') + 1);
						$encodedData = str_replace(' ', '+', $encodedData);
						$decocedData = base64_decode($encodedData);
							 
						$imgFile = $dir.'/'.$widgets->widget[$i * 2]->id.'.png';
						file_put_contents($imgFile, $decocedData);
						$pdf->Image($imgFile, 10, 40 + $i * 53, 80, 40);
					}
					
					// Period
					$pdf->SetY(27 + $i * 53);
					$pdf->SetX(85);
					$pdf->Cell(0, 3, $widgets->widget[$i * 2 + 1]->title.': '.$widgets->widget[$i * 2 + 1]->value, 0, 2);
					
					$data = $widgets->widget[$i * 2 + 1]->dataurl;
					if (isset($data)) {
						$encodedData = substr($data, strpos($data, ',') + 1);
						$encodedData = str_replace(' ', '+', $encodedData);
						$decocedData = base64_decode($encodedData);
							
						$imgFile = $dir.'/'.$widgets->widget[$i * 2 + 1]->id.'.png';
						file_put_contents($imgFile, $decocedData);
						$pdf->Image($imgFile, 85, 32 + $i * 53, 192, 48);
					}
				}
			} else if ($_POST['templateId'] == 'template3') {
				// Template 3 : 4 x 5 gauges
				for ($i = 0; $i < 20; $i++) {
					$pdf->SetY(27 + floor($i / 5) * 40);
					$pdf->SetX(10 + ($i % 5) * 55);
					
					// Gauge
					$label1 = isset($widgets->widget[$i]->label1) ? $widgets->widget[$i]->label1 : '';
					$label2 = isset($widgets->widget[$i]->label2) ? $widgets->widget[$i]->label2 : '';
					$label3 = isset($widgets->widget[$i]->label3) ? $widgets->widget[$i]->label3 : '';
					
					$pdf->Cell(0, 3, $widgets->widget[$i]->title.': '.$widgets->widget[$i]->value, 0, 2);				
					$pdf->Cell(0, 3, $label1, 0, 2);
					$pdf->Cell(0, 3, $label2, 0, 2);
					$pdf->Cell(0, 3, $label3, 0, 2);
					
					$data = $widgets->widget[$i]->dataurl;
					if (isset($data)) {
						$encodedData = substr($data, strpos($data, ',') + 1);
						$encodedData = str_replace(' ', '+', $encodedData);
						$decocedData = base64_decode($encodedData);
							 
						$imgFile = $dir.'/'.$widgets->widget[$i]->id.'.png';
						file_put_contents($imgFile, $decocedData);
						$pdf->Image($imgFile, null, null, 50, 25);
					}				
				}
			} //else if ($_POST['templateId'] == 'template5') {
// 				// Template 5 : 1 map, 1 donut, 1 period
// 				$mapData 	= $widgets->widget[0]->mapdataurl;
// 				$donutData 	= $widgets->widget[0]->donutdataurl;
// 				$periodData = $widgets->widget[0]->perioddataurl;
				
// 				if (isset($mapData)) {
// 					$pdf->SetY(27);
// 					$pdf->SetX(10);
					
// 					$encodedData = substr($mapData, strpos($mapData, ',') + 1);
// 					$encodedData = str_replace(' ', '+', $encodedData);
// 					$decocedData = base64_decode($encodedData);
							 
// 					$imgFile = $dir.'/'.$widgets->widget[0]->id.'_map.png';
// 					file_put_contents($imgFile, $decocedData);
// 					$pdf->Image($imgFile, null, null, 130, 160);				
// 				}
				
// 				if (isset($donutData)) {
// 					$pdf->SetY(27);
// 					$pdf->SetX(165);
				
// 					$encodedData = substr($donutData, strpos($donutData, ',') + 1);
// 					$encodedData = str_replace(' ', '+', $encodedData);
// 					$decocedData = base64_decode($encodedData);
							 
// 					$imgFile = $dir.'/'.$widgets->widget[0]->id.'_donut.png';
// 					file_put_contents($imgFile, $decocedData);
// 					$pdf->Image($imgFile, null, null, 115, 75);				
// 				}
				
// 				if (isset($periodData)) {
// 					$pdf->SetY(110);
// 					$pdf->SetX(165);
				
// 					$encodedData = substr($periodData, strpos($periodData, ',') + 1);
// 					$encodedData = str_replace(' ', '+', $encodedData);
// 					$decocedData = base64_decode($encodedData);
							 
// 					$imgFile = $dir.'/'.$widgets->widget[0]->id.'_period.png';
// 					file_put_contents($imgFile, $decocedData);
// 					$pdf->Image($imgFile, null, null, 115, 75);				
// 				}
// 			}
			else if ($_POST['templateId'] == 'template8') {
				// Template 8 : 3 x 2 gauge + 1 period
				for ($i = 0; $i < 6; $i++) {
					// Display the gauges
					if ($i < 3) $pdf->SetY(27); else $pdf->SetY(67);
					$pdf->SetX(10 + ($i % 3) * 69);
						
					$label1 = isset($widgets->widget[$i]->label1) ? $widgets->widget[$i]->label1 : '';
					$label2 = isset($widgets->widget[$i]->label2) ? $widgets->widget[$i]->label2 : '';
					$label3 = isset($widgets->widget[$i]->label3) ? $widgets->widget[$i]->label3 : '';
						
					$pdf->Cell(0, 3, $widgets->widget[$i]->title.': '.$widgets->widget[$i]->value, 0, 2);
					$pdf->Cell(0, 3, $label1, 0, 2);
					$pdf->Cell(0, 3, $label2, 0, 2);
					$pdf->Cell(0, 3, $label3, 0, 2);
			
					$data = $widgets->widget[$i]->dataurl;
					if (isset($data)) {
						$encodedData = substr($data, strpos($data, ',') + 1);
						$encodedData = str_replace(' ', '+', $encodedData);
						$decocedData = base64_decode($encodedData);
							
						$imgFile = $dir.'/'.$widgets->widget[$i]->id.'.png';
						file_put_contents($imgFile, $decocedData);
						$pdf->Image($imgFile, null, null, 50, 25);
					}
				}
			
				// Display the period
				$pdf->Ln(5);
				$pdf->Cell(0, 10, $widgets->widget[$i]->title, 0, 1);
				$data = $widgets->widget[$i]->dataurl;
				$encodedData = substr($data, strpos($data, ',') + 1);
				$encodedData = str_replace(' ', '+', $encodedData);
				$decocedData = base64_decode($encodedData);
					
				$imgFile = $dir.'/'.$widgets->widget[$i]->id.'.png';
				file_put_contents($imgFile, $decocedData);
				$pdf->Image($imgFile, null, null, 272, 70);
			} 
			
			$outFile = $dir.'/export.pdf';
			$pdf->Output($outFile);
		} else if ($_POST['templateId'] == 'template5') {
			// Template 5 : Map new version
		   $zip = new ZipArchive();

		   $fullscreen=true;
		   if(isset($_POST['roaming'])){
		   		$roaming = json_decode(stripslashes($_POST['roaming']));
		   }
		   if(isset($widgets->widget[0]->donuttitle)){
 		   		$fullscreen=false;
 		   }
		   
		   //get map time from maptitle
		   $maptime=substr($widgets->widget[0]->maptitle,strrpos($widgets->widget[0]->maptitle,"_")+1);

		   if(!$fullscreen){
		   		$outFile = $dir.'/Map_Trend_Donut_mode_export_'.$maptime.'.zip';
		   }
		   else{
		   		if($roaming == true){
		   			$outFile = $dir.'/Map_Roaming_mode_export_'.$maptime.'.zip';
		   		}else{
		   			$outFile = $dir.'/Map_Fullscreen_mode_export_'.$maptime.'.zip';
		   		}
		   }
		   
		   if($zip->open($outFile, ZipArchive::CREATE) == TRUE){
			   if($fullscreen){
			   		//mode fullscreen
				   	$mapdataurl = $widgets->widget[0]->mapdataurl;
				   	$maptitle=$widgets->widget[0]->maptitle;
				   	
	   		      	$mapencodedData = substr($mapdataurl, strpos($mapdataurl, ',') + 1);
	   				$mapencodedData = str_replace(' ', '+', $mapencodedData);
	   				$mapdecocedData = base64_decode($mapencodedData);
	   				
	   				$imgFile = $dir.'/'.$maptitle.'.png';
	   				file_put_contents($imgFile, $mapdecocedData);
	   				$name = $maptitle.'.png';
	   				$zip->addFile($imgFile,$name);

	   				$csv = '';
	   				foreach ($widgets->widget[0]->csvdata[0] as $line=>$data) { 
	   					// no headers
	   					foreach ($data as $values) {
	   						$csv .= $values.';';
	   					}
	   					$csv .= "\n";
	   				}
	   				
	   				if($roaming == true){
	   					$csv2 = '';
		   				foreach ($widgets->widget[0]->csvdata2[0] as $line=>$data) { 
		   					// no headers
		   					foreach ($data as $values) {
		   						$csv2 .= $values.';';
		   					}
		   					$csv2 .= "\n";
		   				}	
	   				}
	   				
	   				if($roaming == true){
	   					$csvChild=$widgets->widget[0]->csvChild;
	   					$csvParent=$widgets->widget[0]->csvParent;
	   					
	   					$zip->addFromString($csvChild.'.csv', $csv);
	   					$zip->addFromString($csvParent.'.csv', $csv2);
	   				}else{
	   					$zip->addFromString($maptitle.'.csv', $csv);
	   				}
	   				
	   				 
	   				$zip->close();
	   				unlink($imgFile);
				}
				else{
				   	//mode trend_donut
				   	
					//map
					$mapdataurl = $widgets->widget[0]->mapdataurl;
					$maptitle=$widgets->widget[0]->maptitle;
					
					$mapencodedData = substr($mapdataurl, strpos($mapdataurl, ',') + 1);
					$mapencodedData = str_replace(' ', '+', $mapencodedData);
					$mapdecocedData = base64_decode($mapencodedData);
					
					$imgFile = $dir.'/'.$maptitle.'.png';
					file_put_contents($imgFile, $mapdecocedData);
					$name = $maptitle.'.png';
					$zip->addFile($imgFile,$name);
					
					//donut
					$donutdataurl = $widgets->widget[0]->donutdataurl;
					$donuttitle=$widgets->widget[0]->donuttitle;
						
					$donutencodedData = substr($donutdataurl, strpos($donutdataurl, ',') + 1);
					$donutencodedData = str_replace(' ', '+', $donutencodedData);
					$donutdecocedData = base64_decode($donutencodedData);
						
					$imgFile = $dir.'/'.$donuttitle.'.png';
					file_put_contents($imgFile, $donutdecocedData);
					$name = $donuttitle.'.png';
					$zip->addFile($imgFile,$name);
					
					//trend
					$trenddataurl = $widgets->widget[0]->trenddataurl;
					$trendtitle=$widgets->widget[0]->trendtitle;
					
					$trendencodedData = substr($trenddataurl, strpos($trenddataurl, ',') + 1);
					$trendencodedData = str_replace(' ', '+', $trendencodedData);
					$trenddecocedData = base64_decode($trendencodedData);
					
					$imgFile = $dir.'/'.$trendtitle.'.png';
					file_put_contents($imgFile, $trenddecocedData);
					$name = $trendtitle.'.png';
					$zip->addFile($imgFile,$name);

					$csv = '';
					foreach ($widgets->widget[0]->csvdata[0] as $line=>$data) {
						// no headers
						foreach ($data as $values) {
							$csv .= $values.';';
						}
						$csv .= "\n";
					}
					
					$zip->addFromString($trendtitle.'.csv', $csv);
						
					$zip->close();
					unlink($imgFile);
				}
		   	}
		} else if ($_POST['templateId'] == 'template6') {
			// Template 6 : 1 grid
			$csv = '';

			// Add the headers
			foreach ($widgets->widget[0]->data[0] as $header) {
				$csv .= $header.';';
			}
			$csv .= "\n";
			
			// Add the datas
			for ($r = 1; $r < count($widgets->widget[0]->data); $r++) {
				foreach ($widgets->widget[0]->data[$r] as $key => $value) {
					$csv .= $value.';';
				}
				$csv .= "\n";
			}
									
			$outFile = $dir.'/'.$widgets->widget[0]->title.'.xls';
			file_put_contents($outFile, $csv);
		} else if ($_POST['templateId'] == 'template7') {
			// Template 7 : Several grids
			$zip = new ZipArchive();
			
			$outFile = $dir.'/Export.zip';
			if($zip->open($outFile, ZipArchive::CREATE) == TRUE)
      		{
      			foreach ($widgets->widget[0]->data as $data) {
      				$csv = '';
      				
					// Add the headers
					foreach ($data[1] as $header) {
						$csv .= $header.';';
					}
					$csv .= "\n";
					
					// Add the datas
					for ($r = 2; $r < count($data); $r++) {
						foreach ($data[$r] as $key => $value) {
							$csv .= $value.';';
						}
						$csv .= "\n";
					}

					$zip->addFromString($data[0].'.csv', $csv);
      			} 
      			
        		$zip->close();
      		} 
		}else if ($_POST['templateId'] == 'template9' || $_POST['templateId'] == 'template10') {
			// Template 9 : Audit report
		   $zip = new ZipArchive();
		   $outFile = $dir.'/Export.zip';
		   $indexRig = 1;
		   $indexSg = 1;
		   $indexAg = 1;
		   $indexDg = 1;
		   //var_dump($widgets);
		   if($zip->open($outFile, ZipArchive::CREATE) == TRUE)
      		{
      			for ($r = 0; $r < count($widgets->widget[0]->dataurl); $r++) {
      				$data = $widgets->widget[0]->dataurl[$r];
      				$name = $widgets->widget[0]->title[$r];
	      			
      				if (strpos($name,'reliability_indicator_trend')) {
					    $name = 'reliability_indicator_graph_'.$indexRig;
					    $indexRig++;
					}
					
					else if(strpos($name,'summary_graph_evo_trend')){
							$name = 'summary_graph_evo'.$indexSg;
						$indexSg++;
					}else if(strpos($name,'summary_graph_trend')){
							$name = 'summary_graph_'.$indexSg;
						$indexSg++;
					}
      				else if(!strpos($name,'chartsPanel')){						
      					if(!strpos($name,'donut')){
      						$name = 'alarms_graph_'.$indexAg;
      						$indexAg++;
      					}
      					else{
      						$name = 'alarms_piechart_'.$indexDg;
      						$indexDg++;
      					}
      					
					}
					
      				if (isset($data) && isset($name)) {
      					
	      				$encodedData = substr($data, strpos($data, ',') + 1);
						$encodedData = str_replace(' ', '+', $encodedData);
						$decocedData = base64_decode($encodedData);	
						
						$imgFile = $dir.'/'.$widgets->widget[0]->id.'_'.$r.'.png';
						file_put_contents($imgFile, $decocedData);
						//$pdf->Image($imgFile, 85, 32 + $i * 53, 192, 48);
						$name = $name.'.png';
						$zip->addFile($imgFile,$name);
					}
      			 }
				if ($_POST['templateId'] == 'template10'){
					//var_dump($widgets->widget[0]);
					//$test = getInfoForIntersection($_POST['tabId']);
					$config = json_decode(stripslashes($_POST['config']));
					
					$alarm_ids = json_decode(stripslashes($_POST['alarm_ids']));
					$sdp_id = $_POST['sdp_id'];
					$current_date = $_POST['time'];
					$selectedmode = $config->{'@attributes'}->selectedmode;
					$ratio = $config->{'@attributes'}->ratio;
					$nbdays = $config->{'@attributes'}->nbdays;


					foreach ($widgets->widget[0]->summary_graph_evo as $summary_graph) {
					$csv = '';
					// Add the headers
						foreach ($summary_graph[0] as $header) {
							if($header != "Ref month"){
							
								$csv .= $header.';';
							}
						}
					}
					$csv .= "\n";

					// Add the datas
					for ($r = 1; $r < count($summary_graph); $r++) {
						foreach ($summary_graph[$r] as $key => $value) {
							if($key<3){
								$csv .= $value.';';
							}
						}
						$csv .= "\n";
					}
					$zip->addFromString('summary_graph.csv', $csv);

					//Create the intersection warning file (from month-1 inter month-2)
					$AlarmModelHomepage = new AlarmModelHomepage();
					$values=$AlarmModelHomepage->calculateIntersectionWarningCells($sdp_id,$alarm_ids,$current_date,$selectedmode,$ratio,$nbdays,true,true,true);
					
					$csv = '';
					$headers=array("cell_id","cell_label","days_in_fault_month-1","days_in_fault_month-2");
					foreach ($headers as $header) {
							$csv .= $header.';';
					}
					$csv .= "\n";
					foreach ($values["csv"]["warning"] as $array_values) {
						foreach ($array_values as $value){
							$csv .= $value.';';
						}
						$csv .= "\n";
					}
					//var_dump($csv);
					$zip->addFromString('shingis_list.csv', $csv);
					
				}else{
					foreach ($widgets->widget[0]->summary_graph as $summary_graph) {
						$csv = '';
						// Add the headers
						foreach ($summary_graph[0] as $header) {
						
							$csv .= $header.';';
							
						}
						$csv .= "\n";
	
						// Add the datas
						for ($r = 1; $r < count($summary_graph); $r++) {
							foreach ($summary_graph[$r] as $key => $value) {
								
								$csv .= $value.';';
								
							}
							$csv .= "\n";
						}
	
						$zip->addFromString('summary_graph.csv', $csv);
						}
				}
      			
      			for ($x = 1; $x <= $widgets->widget[0]->alarms_graph_number[0]; $x++) {
	      			$current_alarms_graph_grid = 'alarms_graph_grid_'.$x;
      				foreach ($widgets->widget[0]->$current_alarms_graph_grid as $alarms_graph_grid) {
	      				$csv = '';
						// Add the headers
						foreach ($alarms_graph_grid[0] as $header) {
							
							$csv .= $header.';';
						}
						$csv .= "\n";
	
						// Add the datas
						for ($r = 1; $r < count($alarms_graph_grid); $r++) {
							foreach ($alarms_graph_grid[$r] as $key => $value) {
								$csv .= $value.';';
							}
							$csv .= "\n";
						}
	
						$zip->addFromString($current_alarms_graph_grid.'.csv', $csv);
	      			}
      			}
      			if ($_POST['templateId'] == 'template10'){
					foreach ($widgets->widget[0]->warning_evo as $warning) {
						$csv = '';
						// Add the headers
						foreach ($warning[0] as $header) {
							$csv .= $header.';';
							
						}
						$csv .= "\n";

						// Add the datas
						for ($r = 1; $r < count($warning); $r++) {
							foreach ($warning[$r] as $key => $value) {
								$csv .= $value.';';
							}
							$csv .= "\n";
						}

						$zip->addFromString('warning.csv', $csv);
					}
					
					foreach ($widgets->widget[0]->penalty_evo as $penalty) {
						$csv = '';
						
						// Add the headers
						foreach ($penalty[0] as $header) {
							$csv .= $header.';';
						}
						$csv .= "\n";
						
						// Add the datas
						for ($r = 1; $r < count($penalty); $r++) {
							foreach ($penalty[$r] as $key => $value) {
								$csv .= $value.';';
							}
							$csv .= "\n";
						}
						$zip->addFromString('penalty.csv', $csv);
					}		
				}else{
					foreach ($widgets->widget[0]->warning as $warning) {
						$csv = '';
						// Add the headers
						foreach ($warning[0] as $header) {
							$csv .= $header.';';
							
						}
						$csv .= "\n";

						// Add the datas
						for ($r = 1; $r < count($warning); $r++) {
							foreach ($warning[$r] as $key => $value) {
								$csv .= $value.';';
							}
							$csv .= "\n";
						}

						$zip->addFromString('warning.csv', $csv);
					}
					
					foreach ($widgets->widget[0]->penalty as $penalty) {
						$csv = '';
						
						// Add the headers
						foreach ($penalty[0] as $header) {
							$csv .= $header.';';
						}
						$csv .= "\n";
						
						// Add the datas
						for ($r = 1; $r < count($penalty); $r++) {
							foreach ($penalty[$r] as $key => $value) {
								$csv .= $value.';';
							}
							$csv .= "\n";
						}
						$zip->addFromString('penalty.csv', $csv);
					}	
				}
      			//Detailed report
      			 foreach ($widgets->widget[0]->detailed_report as $detailed_report) {
	      			
      			 	$csv = '';
						// Add the headers
						foreach ($detailed_report[0] as $header) {
							$csv .= $header.';';
						}
						$csv .= "\n";
	
						// Add the datas
						for ($r = 1; $r < count($detailed_report); $r++) {
							foreach ($detailed_report[$r] as $key => $value) {
								$csv .= $value.';';
							}
							$csv .= "\n";
						}
	
						$zip->addFromString('detailed_report.csv', $csv);
      			 }				
      		} 
      			
        	$zip->close();		
        	unlink($imgFile);
		}
		
		if ($_SERVER['HTTPS'] == 'on') {
			$protocol = 'https';
		} else {
			$protocol = 'http';
		}

		$url = $_SERVER['PHP_SELF'];
		$pos = strrpos($url, '/');
		$url = substr($url, 0, $pos);
		$pos = strrpos($url, '/');
		$url = substr($url, 0, $pos);
		
		//echo $protocol.'://'.$_SERVER['SERVER_ADDR'].$url.substr($outFile, 2);
		//Correction Bug 40169 li� au reverse proxy
		echo $url.substr($outFile, 2);
	}
}

function getWidgets() {
	if (isset($_POST['tab'])) {
		$tab = $_POST['tab'];
		
		global $isAdmin;
		if ($isAdmin) {
			$file = '../config/default/homepage.xml';
		} else {
			$userId = $_SESSION['id_user'];
			$file = '../config/'.$userId.'/homepage.xml';
		}

		// Load the configuration file
		$dom = new DOMDocument();
		$dom->load($file);

		// Get the template file
		$xpath = new DOMXpath($dom);
		$templateNodeList = $xpath->query('/homepage/tab[@id="'.$tab.'"]/template');
		$templateFile = '../config/templates/'.$templateNodeList->item(0)->nodeValue.'.xml';
		
		$dom->load($templateFile);
		$xpath = new DOMXpath($dom);

		// Get the widgets
		$json = '{"widget":[';
		$widgetList = $xpath->query('/template/row/widget');
		for ($i = 0; $i < $widgetList->length; $i++) {
			$json .= '{"id":"'.$widgetList->item($i)->getAttribute('id').'","type":"'.$widgetList->item($i)->getAttribute('type').'"},';
		}

		// Remove the last comma
		if ($widgetList->length > 0) {
			$json = substr($json, 0, -1);
		}

		echo $json.']}';
	}
}





?>
