<?php
if(isset($_FILES)){

	$folder = $_POST['folder'];
	$namefolder = $_POST['namefolder'];
	$newfolder = $folder.$namefolder;

	mkdir($newfolder, 0777);
		if (is_dir($newfolder)) { 
			echo '{success:true, folder:'.json_encode($namefolder).'}';
		}
}

?>

