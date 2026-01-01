<?php 
$currYear = date('Y'); 
$currMonthName = date('M');
$currMonthNumber = date('m');

$yearfolder = '../archives/'.$currYear;
$monthfolder = '../archives/'.$currYear.'/'.$currMonthNumber.'-'.$currMonthName;

if (!is_dir($yearfolder)){

	mkdir($yearfolder, 0777);
	if (is_dir($yearfolder)) { 
		mkdir($monthfolder, 0777);
	}
}else{
	if (!is_dir($monthfolder)){
		mkdir($monthfolder, 0777);
			
	}
}



?>