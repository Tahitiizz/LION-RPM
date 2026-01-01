<?php

class DatabaseServicesHuaRan extends DatabaseServices{ 
	
	public function __construct($dbConnection) {
		parent::__construct($dbConnection);
	}
		
	/**
	 * 
	 * Retourne le tableau des types de fichiers activés pour lesquels 
	 * au moins 1 fichier vient d'être collecté.
	 * On trie par order décroissant sur le flat_file_name, pour ne pas traiter le fichier "Cell Based - E1T1 Port Bit Error Measurement 82833951 - E1T1_PORT" en début
	 * @param $fileType
	 */
	public function getFlatfilenamesForCollectedFiles(FileTypeCondition $fileType=NULL) {
		if($fileType!=NULL) $condition="AND {$fileType->getDBCondition()}";
		$query = "SELECT distinct flat_file_name
			FROM sys_flat_file_uploaded_list, sys_definition_flat_file_lib
			WHERE flat_file_template = flat_file_naming_template AND on_off = 1 $condition ORDER BY flat_file_name DESC;";				
		$result = $this->database->executeQuery($query);
		//en cas d'erreur
		$erreur = $this->database->getLastError();
		if($erreur != ''){
			displayInDemon("getFlatfilenamesForCollectedFiles:Error:$erreur",'alert');
			return array();
		}
		$tabFlatfileNames=array();
		while($row = $this->database->getQueryResults($result,1)) {
			$tabFlatfileNames[] = $row["flat_file_name"];
		}
		return $tabFlatfileNames;
	}
	
	
	
	/**
	 * Retourne la liste des NE actifs par type connus en topo
	 * @return array $neList liste des ne pourle type demandé
	 */
	public function getNeListByType($type){
		$query = "SELECT distinct eor_id
					FROM edw_object_ref
					WHERE eor_obj_type LIKE '$type' AND eor_on_off=1 ORDER BY eor_id;";				
		$result = $this->database->executeQuery($query);
		//en cas d'erreur
		$erreur = $this->database->getLastError();
		if($erreur != ''){
			displayInDemon("getNeList:Error:$erreur",'alert');
			return array();
		}
		$tabNe=array();
		while($row = $this->database->getQueryResults($result,1)) {
			$tabNe[] = $row["eor_id"];
		}
		return $tabNe;
	}
	
	/**
	 * Retourne la liste des NE actifs par type connus en topo
	 * @return array $neList liste des ne pourle type demandé
	 */
	public function getFamilyByEntity(){
		$query = "select nms_table, edw_group_table from sys_field_reference group by nms_table,edw_group_table;";				
		$result = $this->database->executeQuery($query);
		//en cas d'erreur
		$erreur = $this->database->getLastError();
		if($erreur != ''){
			displayInDemon("getFamilyByEntity:Error:$erreur",'alert');
			return array();
		}
		
		$tabEntity=array();
		$tabfamilly=array();
		while($row = $this->database->getQueryResults($result,1)) {
			$tabEntity[] = $row["nms_table"];
			preg_match("/^edw_.*_(.*)_axe1/", $row["edw_group_table"], $output);
			$tabfamilly[] = $output[1];
		}
		
		return array($tabEntity,$tabfamilly);
	}
	
}

?>