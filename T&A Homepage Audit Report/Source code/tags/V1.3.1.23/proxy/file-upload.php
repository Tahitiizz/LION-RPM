<?php

if(isset($_FILES)){
//var_dump($_FILES);
$folder = $item = $_POST['folder'];
  $temp_file_name = $_FILES['uploader']['tmp_name'];
  $original_file_name = $_FILES['uploader']['name'];

  // Find file extention
  $ext = explode ('.', $original_file_name);
  $ext = $ext [count ($ext) - 1];

  // Remove the extention from the original file name
  $file_name = str_replace ($ext, '', $original_file_name);

  $new_name = $folder .$file_name . $ext;

  if (move_uploaded_file ($temp_file_name, $new_name)) {
       sleep(1);
	   echo '{success:true, file:'.json_encode($_FILES['uploader']['name']).'}';
   } else {
      echo "error";
    }

}

   
?>

