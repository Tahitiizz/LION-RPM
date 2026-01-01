<?php
/* 
 * 01/03/2011 MMT Creation du fichier
 *    remplacement de alarmMailWithPDF dans le cadre du bug 19128 (ajour format xls et doc pour rapports)
 *
 * voir alarmMail.class.php pour detail
 */

/**
 * Description of alarmMailPdf
 *
 * @author m.monfort
 */
include_once (REP_PHYSIQUE_NIVEAU_0."class/alarmMail.class.php");
include_once (REP_PHYSIQUE_NIVEAU_0."class/htmlTablePDF.class.php");

class alarmMailPdf extends alarmMail {
    //put your code here


	var $header = '';

	/**
	 * specify the PDF header
	 * @param String $header
	 */
	public function setHeader($header){
		$this->header= $header;
	}

	/**
	 * extends abstract method
	 */
	public function getExportFileFormat(){
		return 'pdf';
	}

	/**
	 * extends abstract method
	 */
	public function getExportFileExtention(){
		return 'pdf';
	}

	/**
	 * extends method
	 */
	protected function createFileFromBufferResults($file_name,$result_search){

		$html_to_pdf = new PDF_HTML_Table();
		$html_to_pdf->generatePDF('history',$this->header);
		$html_to_pdf->set_PDF_directory($this->createFilePath);
		$html_to_pdf->set_PDF_file_name($file_name);

		$html = array();
		foreach ($result_search as $row_search) {
			$html[]=array($row_search['object_title'],$row_search['object_source'],$row_search['id_page']);
			print "<div class='texteGrisBoldPetit'>".$row_search['object_title']."</div>";
		}
		if (!empty($html)){
			$html_to_pdf->WriteHTML ($html);
		}

		$pdf_file_name = $html_to_pdf->get_PDF_file_name();

		$html_to_pdf->savePDF();

		return $pdf_file_name;
	}


	/**
	 * extends method
	 * set header with group name on id alarm generation
	 */
	protected function getListeIdAlarmes($group_info, $tab, $alarm_type){
		$this->setHeader("Alarm report (".$group_info['group_name'].")");
		return parent::getListeIdAlarmes($group_info, $tab, $alarm_type);
	}


}
?>
