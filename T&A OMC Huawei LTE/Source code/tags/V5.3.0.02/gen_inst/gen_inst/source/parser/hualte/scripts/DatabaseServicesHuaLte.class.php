<?php
Class DatabaseServicesHuaLte extends DatabaseServices{
	public function DatabaseServicesHuaLte( $dbConnection){
		parent::__construct( $dbConnection);
	}

    public function getFamillyByNmsTable($nms_table){		
		$query="select distinct edw_group_table from sys_field_reference where nms_table = '$nms_table'";
    	$res = $this->database->executeQuery($query);
		$values=$this->database->getQueryResults($res);

		return $values;
		
    }
	
}
?>
