<?php
if(isset($_FILES)){
//var_dump($_FILES);
//and also consider to check if the file exists as with the other guy suggested.

$filename = $item = $_POST['folder'];

chdir('../archives'); // Comment this out if you are on the same folder
chmod($filename,0777);
	if (rmdir_recursive($filename)){
		sleep(1);
		echo '{success:true, file:'.json_encode($filename).'}';
	}
	else{
		echo '{success:false, file:'.json_encode($filename).'}';
	}

}

function rmdir_recursive($dir)
{
$clsfolder = $_POST['cls'];
	if($clsfolder == 'folder')
	{
		//Liste le contenu du répertoire dans un tableau
		$dir_content = scandir($dir);
		//Est-ce bien un répertoire?
		if($dir_content !== FALSE){
			//Pour chaque entrée du répertoire
			foreach ($dir_content as $entry)
			{
				//Raccourcis symboliques sous Unix, on passe
				if(!in_array($entry, array('.','..'))){
					//On retrouve le chemin par rapport au début
					$entry = $dir . '/' . $entry;
					//Cette entrée n'est pas un dossier: on l'efface
					if(!is_dir($entry)){
						unlink($entry);
					}
					//Cette entrée est un dossier, on recommence sur ce dossier
					else{
						rmdir_recursive($entry);
					}
				}
			}
			//On a bien effacé toutes les entrées du dossier, on peut à présent l'effacer
			rmdir($dir);
			return true;
		}
	}
	else{
		//on est dans le cas d'un fichier
		if(unlink($dir)){
			return true;
		}
		else{
			return false;
		}
		
	}
	

	
}

?>

