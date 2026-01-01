<?php

$clsfolder = $_GET['cls'];
$filename = $_GET['folder'];
//$filename = '../archives/Aou-2010/PL_readme.docx';

	if($clsfolder != 'folder'){
		if (file_exists($filename)) {	
		header('Cache-Control: maxage=120'); 
		header('Expires: '.date(DATE_COOKIE,time()+120)); // Cache for 2 mins 
		header('Pragma: public'); 
		header("Content-type: application/force-download");  
		header("Content-Transfer-Encoding: Binary");  
		header('Content-Type: application/octet-stream');  
		header('Content-Disposition: attachment; filename='.$filename); 
		//echo file_get_contents ($filename);  
		
		ob_clean();
		flush();
		readfile($filename);
		
		//echo '{success:true, file:'.json_encode($filename).'}';
		}
	}
	

?>

