<?php
class XMLConditionProvider extends ConditionProvider {
	public function XMLConditionProvider($dbServices){
		parent::__construct($dbServices);
		
		$this->templateForNE="A[0-9]{4}[0-9]{2}[0-9]{2}\.[0-9]{2}[0-9]{2}[+,-][0-9]{4}\-[0-9]{2}[0-9]{2}[+,-][0-9]{4}_(.+)\.xml";
		

	}
}
?>