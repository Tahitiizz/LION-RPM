<?php
/*
 * 01/03/2011 MMT Creation du fichier
 *    remplacement de alarmMailWithPDF dans le cadre du bug 19128 (ajout format xls et doc pour rapports)
 *
 *
 * voir alarmMail.class.php pour detail
 */

/**
 * Description of alarmMailExcel
 *
 * @author m.monfort
 */
include_once (REP_PHYSIQUE_NIVEAU_0."class/alarmMail.class.php");
include_once (REP_PHYSIQUE_NIVEAU_0."class/htmlTableExcel.class.php");

class alarmMailExcel extends alarmMail {
    //put your code here

   /**
	 * extends abstract method
	 */
	public function getExportFileFormat(){
		return 'excel';
	}

	/**
	 * extends abstract method
	 */
	public function getExportFileExtention(){
		return 'xls';
	}

	/**
	 * extends method
	 */
	protected function createFileFromBufferResults($file_name,$result_search){

		$excel_filename	= $file_name;

		$header_title	= 'Reporting : Alarm';
		$save_file = true;

		$excel_filepath	=  $this->createFilePath;

		$header_img		= array("operator" => REP_PHYSIQUE_NIVEAU_0.get_sys_global_parameters("pdf_logo_operateur"), "client" => REP_PHYSIQUE_NIVEAU_0.get_sys_global_parameters("pdf_logo_dev"));
		$sous_mode		= 'condense';

		$html_to_excel = new Excel_HTML_Table($excel_filepath, $excel_filename, '', '', $sous_mode, $save_file);

		$html = array();

		foreach ($result_search as $row_search) {
			$html[]=array($row_search['object_title'],$row_search['object_source'],$row_search['id_page']);
			print "<div class='texteGrisBoldPetit'>".$row_search['object_title']."</div>";
		}

		if (!empty($html)){
			$html_to_excel->writeContent ($html);
		}

		return $excel_filename;
	}


	/**
	 * extends method
	 *
	 */
	protected function getCreateHtmlQueries($na,$ta,$ta_value,$alarm_type,$sql_filter,$isReport){

		$allSevQueries = parent::getCreateHtmlQueries($na, $ta, $ta_value, $alarm_type, $sql_filter, $isReport);
		// this table is in the form : [0] => {['critical'] => {SQL queries}},
		//										 [1] => {['major'] => {SQL queries}},
		//										 [2] => {['minor'] => {SQL queries}}
		// this will generate 1 table per severity
		// for excel we need 1 table with all severities so we transform the returned arry to be in the form:
		//										 [0] => {['critical'] => {SQL queries},
		//											 		['major'] => {SQL queries},
		//											 		['minor'] => {SQL queries}
		//											 	  }

		$ret = array(array());// a sinle row
		foreach ($allSevQueries as $severitiesQueries) {
			foreach ($severitiesQueries as $severity => $queries) {
				$ret[0][$severity] = $queries;
			}
		}
		return $ret;
	}


}
?>
