<?php
class XMLConditionProvider extends ConditionProvider {
	public function XMLConditionProvider($dbServices){
		parent::__construct($dbServices);

		//must contain one '(.+)'
		//$pattern="/A([0-9]{4})([0-9]{2})([0-9]{2})\\.([0-9]{2})([0-9]{2})([+,-][0-9]{2})[0-9]{2}\\-([0-9]{2})([0-9]{2})([+,-][0-9]{2})[0-9]{2}_SubNetwork=(.*),SubNetwork=(.*),MeContext=.*/";
		$this->templateForNE="A[0-9]{4}[0-9]{2}[0-9]{2}.[0-9]{2}[0-9]{2}[+-]{1}.+_(.+).xml";
		
		/*$this->maxNumberOfProcessesPerCore=4;
		$this->minNumberOfProcessesPerCore=2;
		$this->parserPoids=1;*/
	}
}
?>