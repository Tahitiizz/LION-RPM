<?php
Class DatabaseServicesEriLte extends DatabaseServices{
	public function DatabaseServicesEriLte( $dbConnection){
		parent::__construct( $dbConnection);
	}


    public function activateGlobalCounters() {
        $this->database->execute("update sys_field_reference set on_off=1, new_field=1 where on_off=0 and edw_target_field_name in (select substring(edw_target_field_name,'([[:alnum:]]*)_[0-9]*$') from sys_field_reference where on_off=1)");
    }
	


}
?>
