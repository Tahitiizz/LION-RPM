<?
/*
*	Fichier qui affiche tout l'historique des versions cb, parser, contexte...
*/
?>
<?
/*
*	@cb4100@
*
*	11/12/2008 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.0
*	-	maj 13/01/2009 - MPR : Si le produit est passé en paramètre on récupère les données sur celui-ci
*
*	06/07/2009 BBX : 
*		- modification des offset pour récupérer les infos contexte. Les ids ont du changer en 5.0. BZ 9716.
*	
*	25/08/2009 MPR :
*		- Correction du bug 9712 : Un des parsers n'est pas affiché lorsque l'on a plusieurs contexte (problème de doublons sur l'id)
*	  	- On trie le résultat sur l'oid et non plus sur l'id
*/
?>
<?php
/*
*
	- maj 13/05/2008, benoit : correction du bug 6317. Ajout du cas où la date est au format YYYY_MM_DD_HH et les minutes sont indiquées. Si   la date est au format YYYY_MM_DD_HH, les minutes indiquées seront "00"

	- maj 11/06/2008, benoit : correction du bug 6623
*
*/
?>
<?php

set_time_limit(36000);

include_once(dirname(__FILE__)."/../php/environnement_liens.php");

// maj 13/01/2009 - MPR : Si le produit est passé en paramètre on récupère les données sur celui-ci
$prod = (isset($_GET['product'])) ? $_GET['product'] : "";
new versionHistory($_GET['from'], $database_connection, $prod);

/**
 * Classe d'affichage et de sauvegarde au format csv du contenu de la table 'sys_versioning'
 * 
 * @author BAC
 * @copyright Astellia
 * @version 1.0
 * 	
 */

class versionHistory
{
	private $_from;
	private $_database_connection;
	private $_all_actions;
	private $_cb_versions;
	private $_parser_versions;
	private $_contexte_versions;

	public function __construct($from, $database_connection, $prod)
	{
		($from != "") ? $this->_from = $from : $this->_from = "demon";
		if($prod !== ""){
			// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
			$this->_database_connection = Database::getConnection($prod);
			$this->_product = $prod;
			
		}else{
		
			$this->_database_connection = $database_connection;
		}
		$this->getVersioningContent();
		$this->display();
	}

	/**
	 * Lit le contenu de la table 'sys_versioning' et sauvegarde ses informations
	 * 
	 */

	private function getVersioningContent()
	{
		$versions_info = array();
		$this->_all_actions = array();
		
		// Sélection des informations stockées dans 'sys_versioning'
		// maj 25/08/2009 - Correction du bug 9712 : Un des parsers n'est pas affiché lorsque l'on a plusieurs contexte (problème de doublons sur l'id)
		//	  		   - On trie le résultat sur l'oid et non plus sur l'id
		$sql =	 " SELECT *,"
				." (CASE WHEN item LIKE '%cb%' THEN 'cb' ELSE (SELECT CASE WHEN item LIKE '%parser%' THEN 'parser' ELSE 'contexte' END) END)"
				." AS type"
				." FROM sys_versioning ORDER BY oid ASC";

		// maj 13/01/2009 - MPR : Récupération des données sur le produit concerné 
		if(isset($this->_product)){
		
			$req = $this->_database_connection->getAll($sql);
			
			if(count($req) > 0){
				foreach($req as $row){
					
					$version_date = $this->setCorrectDateValue($row['date']);
					
					$versions_info[] = array('id'=>$row['id'], 'item'=>$row['item'], 'item_value'=>$row['item_value'], 'item_mode'=>$row['item_mode'], 'type'=>$row['type'], 'date'=>$version_date);
					
				}
			}
			
		}else{
		
			$req = pg_query($sql);

			if (pg_num_rows($req) > 0) {			
				while ($row = pg_fetch_array($req)) {

					$version_date = $this->setCorrectDateValue($row['date']);
					
					$versions_info[] = array('id'=>$row['id'], 'item'=>$row['item'], 'item_value'=>$row['item_value'], 'item_mode'=>$row['item_mode'], 'type'=>$row['type'], 'date'=>$version_date);
				}
			}
		}
		
		// Remise en forme des informations au vu de leur affichage
					
					
		for ($i=0; $i < count($versions_info); $i++) {
			
			switch ($versions_info[$i]['item']) {
				
				// Composants de base

				case 'cb_name' :
				
					
					$cb_name = $versions_info[$i]['item_value'];
					
					if ($versions_info[$i+1]['item'] == "cb_version") {
						$cb_version	= $versions_info[$i+1]['item_value'];
						$cb_date	= $versions_info[$i]['date'];
						
						$this->_cb_versions[] = array('name'=>$cb_name, 'version'=>$cb_version, 'date'=>$cb_date);
						
						$this->_all_actions[] = array('name'=>$cb_name, 'version'=>$cb_version, 'date'=>$cb_date, 'type'=>$versions_info[$i]['type']);						
					}
				
				break;

				// Parsers

				case 'parser_name' :

					$parser_name = $versions_info[$i]['item_value'];

					if ($versions_info[$i+1]['item'] == "parser_version") {
						$parser_version	= $versions_info[$i+1]['item_value'];
						$parser_date	= $versions_info[$i]['date'];
						
						$this->_parser_versions[] = array('name'=>$parser_name, 'version'=>$parser_version, 'date'=>$parser_date);

						$this->_all_actions[] = array('name'=>$parser_name, 'version'=>$parser_version, 'date'=>$parser_date, 'type'=>$versions_info[$i]['type']);						
					}

				break;

				// Contextes

				case 'contexte' :

					// 06/07/2009 BBX : modification des offset pour récupérer les infos contexte. Les ids ont du changer en 5.0. BZ 9716.
					if (($versions_info[$i+1]['item'] == "context_name") && ($versions_info[$i+2]['item'] == "context_version")) {
						$contexte_name		= $versions_info[$i+1]['item_value'];
						$contexte_version	= $versions_info[$i+2]['item_value'];
						$contexte_date		= $versions_info[$i]['date'];
					}
					else 
					{
						$contexte_value = explode('_', basename($versions_info[$i]['item_value'], '.sql.bz2'));
						$contexte_name		= implode('_', array_slice($contexte_value, 0, count($contexte_value)-1));
						$contexte_version	= $contexte_value[count($contexte_value)-1];
						$contexte_date		= $versions_info[$i]['date'];	
					}

					$this->_contexte_versions[] = array('name'=>$contexte_name, 'version'=>$contexte_version, 'date'=>$contexte_date);

					$this->_all_actions[] = array('name'=>$contexte_name, 'version'=>$contexte_version, 'date'=>$contexte_date, 'type'=>$versions_info[$i]['type']);

				break;
			}
		}
	}

	/**
	 * Redirige vers la bonne methode d'affichage en fonction de l'origine du script
	 * 
	 */

	private function display()
	{	
	
		// __debug($this->_cb_versions);
		// __debug($this->_all_actions);
		switch ($this->_from) {
			
			case 'about' :

				// 11/06/2008 - Modif. benoit : correction du bug 6623. Suppression de l'ajout d'un numéro unique dans le nom du fichier

				//$filename = REP_PHYSIQUE_NIVEAU_0.get_sys_global_parameters("pdf_save_dir")."version_history_".md5(uniqid(rand(), true)).".csv";
				$filename = REP_PHYSIQUE_NIVEAU_0.get_sys_global_parameters("pdf_save_dir")."version_history.csv";	
				$this->displayAsCsv($filename, false);	
			break;
			
			case 'demon' :
				$this->displayAsText();
				$filename = REP_PHYSIQUE_NIVEAU_0."file_demon/version_history.csv";	
				$this->displayAsCsv($filename, true);		
			break;
		}
	}

	/**
	 * Affiche et sauvegarde le contenu de la table 'sys_versioning' au format Excel
	 * 
	 * @param string $filename chemin complet vers le fichier de sauvegarde
	 * @param boolean $save_in_file le fichier doit-il etre sauvegardé (true) ou affiché (false)
	 */

	private function displayAsXls($filename, $save_in_file = false)
	{
		// 20/05/2010 NSE : relocalisation du module excel dans le CB
		require_once(REP_PHYSIQUE_NIVEAU_0."modules/excel/class.writeexcel_workbook.inc.php");
		require_once(REP_PHYSIQUE_NIVEAU_0."modules/excel/class.writeexcel_worksheet.inc.php");
		
		$workbook = &new writeexcel_workbook($filename);
		$worksheet = &$workbook->addworksheet("Version History");

		$row_index = 0;

		// Ecriture du titre ("Version History on [date]")

		$bold_style =& $workbook->addformat(array(bold => 1));
		$worksheet->write($row_index, 0, "Version History on ".date('d-m-Y H:i:s'), $bold_style);
		$row_index += 2;

		// Recapitulatif des actions effectuées

		$worksheet->write($row_index, 0, "Name", $bold_style);
		$worksheet->write($row_index, 1, "Version", $bold_style);
		$worksheet->write($row_index, 2, "Date", $bold_style);
		$worksheet->write($row_index, 3, "Type", $bold_style);
		$row_index += 1;

		for ($i=0; $i < count($this->_all_actions); $i++) {
			$worksheet->write($row_index, 0, $this->_all_actions[$i]['name']);
			$worksheet->write($row_index, 1, "v".$this->_all_actions[$i]['version']);
			$worksheet->write($row_index, 2, $this->_all_actions[$i]['date']);
			$worksheet->write($row_index, 3, $this->_all_actions[$i]['type']);
			$row_index += 1;
		}

		$workbook->close();

		// Si l'on doit simplement afficher le fichier, on envoie son contenu au navigateur puis on le supprime

		if (!$save_in_file) {
			header("Content-Type: application/x-msexcel");
			$fh=fopen($filename, "rb");
			fpassthru($fh);
			if (is_file($filename)) unlink($filename);
		}
	}

	/**
	 * Affiche et sauvegarde le contenu de la table 'sys_versioning' au format CSV
	 * 
	 * @param string $filename chemin complet vers le fichier de sauvegarde
	 * @param boolean $save_in_file le fichier doit-il etre sauvegardé (true) ou affiché (false)
	 */

	private function displayAsCsv($filename, $save_in_file = false)
	{
		$file_content = "";
		
		// Ecriture du titre ("Version History on [date]")

		$file_content .= "Version History on ".date('d-m-Y H:i:s').";;;\n\n";

		// Recapitulatif des actions effectuées

		$file_content .= "Name;Version;Date;Type\n";

		for ($i=0; $i < count($this->_all_actions); $i++) {
			__debug("name:".$this->_all_actions[$i]['name']." / date:".$this->_all_actions[$i]['date']." / type:".$this->_all_actions[$i]['type']." / version:".$this->_all_actions[$i]['version'] ,"All actions");
			
			$file_content .= $this->_all_actions[$i]['name'].";";
			$file_content .= "v".$this->_all_actions[$i]['version'].";";
			$file_content .= $this->_all_actions[$i]['date'].";";
			$file_content .= $this->_all_actions[$i]['type']."\n";
		}

		$handle = fopen($filename, 'w+');
		fwrite($handle, $file_content);
		fclose($handle);
		
		// __debug(file($file_content),"FILE");

		// Si l'on doit simplement afficher le fichier, on envoie son contenu au navigateur puis on le supprime

		if (!$save_in_file) {
			header("Content-disposition: attachment; filename=".basename($filename));
			header("Content-Type: application/force-download");
			header("Content-Transfer-Encoding: application/octet-stream\n");
			header("Content-Length: ".filesize($filename));
			header("Pragma: no-cache");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0, public");
			header("Expires: 0");
			readfile($filename);
			if (is_file($filename)) unlink($filename);
		}		
	}

	/**
	 * Affiche le contenu de la table 'sys_versioning' au format HTML
	 *
	 */

	private function displayAsText()
	{		
		echo "<div style='padding-bottom:15px;font-weight:bold'>Version History on ".date('d-m-Y h:i:s')."</div>";

		// Ecriture des versions du cb

		echo '<span style="font-weight:bold;">Cb versions :</span>';	
		echo '<table border="1"><tr style="font-weight:bold;text-align:center"><td>Name</td><td>Version</td><td>Date</td>';

		for ($i=0; $i < count($this->_cb_versions); $i++) {
			echo "<tr>";
			echo "<td>".$this->_cb_versions[$i]['name']."</td>";
			echo "<td> v".$this->_cb_versions[$i]['version']."</td>";
			echo "<td>".$this->_cb_versions[$i]['date']."</td>";
			echo "</tr>";
		}

		echo "</table><br/>";

		// Ecriture des versions du parser

		echo '<span style="font-weight:bold;">Parser versions :</span>';	
		echo '<table border="1"><tr style="font-weight:bold;text-align:center"><td>Name</td><td>Version</td><td>Date</td>';

		for ($i=0; $i < count($this->_parser_versions); $i++) {
			echo "<tr>";
			echo "<td>".$this->_parser_versions[$i]['name']."</td>";
			echo "<td> v".$this->_parser_versions[$i]['version']."</td>";
			echo "<td>".$this->_parser_versions[$i]['date']."</td>";
			echo "</tr>";
		}

		echo "</table><br/>";

		// Ecriture des versions du contexte

		echo '<span style="font-weight:bold;">Context versions :</span>';	
		echo '<table border="1"><tr style="font-weight:bold;text-align:center"><td>Name</td><td>Version</td><td>Date</td>';

		for ($i=0; $i < count($this->_contexte_versions); $i++) {
			echo "<tr>";
			echo "<td>".$this->_contexte_versions[$i]['name']."</td>";
			echo "<td> v".$this->_contexte_versions[$i]['version']."</td>";
			echo "<td>".$this->_contexte_versions[$i]['date']."</td>";
			echo "</tr>";
		}

		echo "</table><br/>";

		// Recapitulatif des actions effectuées

		echo '<span style="font-weight:bold;">All versions :</span>';	
		echo '<table border="1"><tr style="font-weight:bold;text-align:center"><td>Name</td><td>Version</td><td>Date</td><td>Type</td>';

		for ($i=0; $i < count($this->_all_actions); $i++) {
			echo "<tr>";
			echo "<td>".$this->_all_actions[$i]['name']."</td>";
			echo "<td> v".$this->_all_actions[$i]['version']."</td>";
			echo "<td>".$this->_all_actions[$i]['date']."</td>";
			echo "<td>".$this->_all_actions[$i]['type']."</td>";
			echo "</tr>";
		}

		echo "</table><br/>";	
	}

	/**
	 * Renvoie une valeur de date au format attendu par la classe
	 * 
	 * @param string $date_value valeur de la date à remodeler
	 * @return string valeur de la date dans le format attendu
	 */

	private function setCorrectDateValue($date_value)
	{
		// 13/05/2008 - Modif. benoit : correction du bug 6317. Ajout du cas où la date est au format YYYY_MM_DD_HH et les minutes sont indiquées. Si la date est au format YYYY_MM_DD_HH, les minutes indiquées seront "00"

		if (count(explode('_', $date_value)) == 5) {	// Format YYYY_MM_DD_HH_ii (où ii représente les minutes)
			$date_value		= explode('_', $date_value);
			$correct_value	= $date_value[2]."-".$date_value[1]."-".$date_value[0]." ".$date_value[3].":".$date_value[4];
		}
		else if (count(explode('_', $date_value)) == 4) {	// Format YYYY_MM_DD_HH
			$date_value		= explode('_', $date_value);
			$correct_value	= $date_value[2]."-".$date_value[1]."-".$date_value[0]." ".$date_value[3].":00";
		}
		else if (!(($timestamp = strtotime($date_value)) === false)){	// Format RFC 2822 (cf. http://www.faqs.org/rfcs/rfc2822)
			$correct_value	= date('d-m-Y H:i:s', $timestamp);
		}
		else // Format inconnu -> on renvoie la valeur de la date dans son format initial
		{
			$correct_value = $date_value;
		}
		return $correct_value;
	}
}