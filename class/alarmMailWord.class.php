<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of alarmMailPdf
 *
 * @author m.monfort
 */
include_once (REP_PHYSIQUE_NIVEAU_0."class/alarmMail.class.php");
include_once (REP_PHYSIQUE_NIVEAU_0."class/htmlTableWord.class.php");

class alarmMailWord extends alarmMail {
    //put your code here

	/**
	 * extends abstract method
	 */
	public function getExportFileFormat(){
		return 'word';
	}

	/**
	 * extends abstract method
	 */
	public function getExportFileExtention(){
		return 'doc';
	}

	/**
	 * extends method
	 */
	protected function createFileFromBufferResults($file_name,$result_search){

		// the Word_HTML_Table->writeContent  always appands the ".doc" extention (whereas Word_HTML_Table does not)
		// so we strip the extention before save
		$word_filename	= substr($file_name, 0,-4);

		$header_title	= 'Reporting : Alarm';
		$save_file = true;

		$word_filepath	=  $this->createFilePath;

		$header_img		= array("operator" => REP_PHYSIQUE_NIVEAU_0.get_sys_global_parameters("pdf_logo_operateur"), "client" => REP_PHYSIQUE_NIVEAU_0.get_sys_global_parameters("pdf_logo_dev"));
		$sous_mode		= 'condense';

		$html_to_word = new Word_HTML_Table($word_filepath, $word_filename, $header_img, $header_title, $sous_mode, $save_file);

		$html = array();
		foreach ($result_search as $row_search) {
			$html[]=array($row_search['object_title'],$row_search['object_source'],$row_search['id_page']);
			print "<div class='texteGrisBoldPetit'>".$row_search['object_title']."</div>";
		}

		if (!empty($html)){
			$html_to_word->writeContent ($html);
		}

		return $file_name;
	}

}
?>
