<?php
class ASN1ConditionProvider extends ConditionProvider {
	public function ASN1ConditionProvider($dbServices){
		parent::__construct($dbServices);

		//$this->parserCondition=new FileTypeCondition("flat_file_name", "~*", "ASN1");
		//must contain one '(.+)'
		$this->templateForNE="C[0-9]{8}.[0-9]{4}[-][0-9]{8}.[0-9]{4}_(.+):";
		
		/*$this->maxNumberOfProcessesPerCore=4;
		$this->minNumberOfProcessesPerCore=2;
		$this->parserPoids=1;*/
	}
}
?>