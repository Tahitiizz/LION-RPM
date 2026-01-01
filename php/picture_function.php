<?php
/*
	06/08/2009 GHX
		- Correction du BZ 3461 [QAL][V2.0] Logo client tout deforme
*/
?>
<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*/
?>
<?
	// Fonctions permettant de traiter les images.

	// Fonction creerVignette (image, largeur_vignette,
	//	hauteur_vignette, $chemin_vignette, chemin_biblio,
	//  prefixe_vignette)
	//--------------------------------------------------------------
	// Redimenssionne l'image dont le nom est passée en paramètre
	// en créant une copie de taille largeur_vignette*hauteur_vignette,
	// dans le répertoire chemin_vignette et de nom prefixe_vignette+image.
	// Copie également l'image $image dans le répertoire chemin_biblio.
	// Utilisé pour les images de la bibliothèque.
	//--------------------------------------------------------------
	function creerVignette ($image, $largeur_vignette, $hauteur_vignette, $rep_copy)
	{

		$cheminImageOriginale =  $rep_copy . $image;
		$cheminImageMini =  $rep_copy . $image;

		// getimagesize() retourne un tableau de 4 éléments :
		// ¤ index 0 contient la largeur.
		// ¤ index 1 contient la hauteur.
		// ¤ index 2 contient le type de l'image : 1=GIF, 2=JPG, 3=PNG, 6=BMP.
		// ¤ index 3 contient la chaîne à placer dans la balise HTML : "height=xxx width=xxx".
		$image = getimagesize ($cheminImageOriginale);

		// SPD1 09/03/2012 BZ 25636
		$imageWidth = $image[0];
		$imageHeight = $image[1];
		$imageType = $image[2];
		
		// Redimensionne l'image si nécessaire
		if($imageWidth > $largeur_vignette || $imageHeight > $hauteur_vignette){
						
			// 17:41 06/08/2009 GHX
			// Correction du BZ 3461
			if ( $imageWidth > $imageHeight )
			{
				// SPD1 09/03/2012 BZ 25636
				$dest_height = $imageHeight*$largeur_vignette/$imageWidth;				
				if ($dest_height > $hauteur_vignette) {
					$dest_width = $imageWidth*$hauteur_vignette/$imageHeight;
					$dest_height = $hauteur_vignette;
				} else {
					$dest_width = $largeur_vignette;
				}
			}
			else
			{
				$dest_width = $imageWidth*$hauteur_vignette/$imageHeight;				
				// SPD1 09/03/2012 BZ 25636
				if ($dest_width > $largeur_vignette) {
					$dest_width = $largeur_vignette;
					$dest_height = $imageHeight*$largeur_vignette/$imageWidth;
				} else {
					$dest_height = $hauteur_vignette;
				}
			}
			
			switch ($imageType) // On créé une copie de l'image source en fonction de son type.
			{
//				case 1 : $source_img = imagecreatefromgif ($cheminImageOriginale);
	//					 break;
				case 2 : $source_img = imagecreatefromjpeg ($cheminImageOriginale);
						 break;
				//case 3 : $source_img = imagecreatefrompng ($cheminImageOriginale);
					//	 break;
		//		case 6 : $source_img = imagecreatefromwbmp ($cheminImageOriginale);
			//			 break;
			}

			// Création d'un identifiant temporaire.
			$dest_img = imagecreatetruecolor($dest_width, $dest_height);

			// On redimensionne l'image
			imagecopyresampled ($dest_img, $source_img, 0, 0, 0, 0, $dest_width, $dest_height, $imageWidth, $imageHeight);
			// On l'enregistre au format dans le répertoire des vignettes.
			imagejpeg ($dest_img, $cheminImageMini, 60);
			// On supprime les identifiants temporaires.
			imagedestroy ($source_img);
			imagedestroy ($dest_img);
		}
	}

	// Fonction isImages (fichier)
	//------------------------------------------------------------------
	// Vérifie si le fichier passé en paramètre est une image
	// et si taille inférieure à $taille_image.
	// Retourne un booléen , vrai : le fichier est une image, faux sinon.
	//-------------------------------------------------------------------
	function isImage ($fichier, $taille_image){
		$image = getimagesize ($fichier); // Description de cette fonction dans la fonction resize()
		// Vérification du TYPE du fichier téléchargé.
		switch ($image[2]){
			//case 1 :		// Ici, on autorise les images du type GIF(1), JPG(2), PNG(3) et BMP(6).
			case 2 : return true; break;
			//case 3 : return true; break;
			//case 6 :		if (filesize($fichier) > $taille_image){
				//				$_SESSION["msg_erreur"] = "Max picture size is 100 ko.";
					//			return false;
						//	} else {
							//	return true;
						//	}
							//break;

			default :		$_SESSION["msg_erreur"] = "Not supported file.";
							return false;
		}

	}

	// fonction imageType (image)
	//------------------------------------------------------------------
	// Retourne l'extension du type de l'image passée en paramètre.
	//------------------------------------------------------------------
	function imageType ($fichier){
		$image = getimagesize ($fichier);
		switch ($image[2]){
			case 1 : $type = ".gif";
					 break;
			case 2 : $type = ".jpg";
					 break;
			case 3 : $type = ".png";
					 break;
			case 6 : $type = ".bmp";
					 break;
		}
		return ($type);
	}

	// fonction renameImage (image)
	//------------------------------------------------------------------
	// Permet de supprimer tous les caractères invalides du nom
	// d'un fichier image.
	//------------------------------------------------------------------
	function renameImage ($fichier){
		// Tableau contenant tous les caractères invalides.
		$tab_caracteres_invalide = array (" ","é","è","É","À","Á","Æ","Ç","à","'","&","\"","(","-","ç",")","=","~","#","{","[","|","`","\\","^","@","]","}","<",">",",",";",":","!","?","/","§","ù","*","%","µ","$","£","¤","€");
		// Tableau contenant les caractères qui remplacement des caractères invalides (même ordre).
		$tab_caracteres_de_remplacement = array ("","e","e","e","a","a","a","c","a","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","");
		$fichier_valide = str_replace ($tab_caracteres_invalide, $tab_caracteres_de_remplacement, $fichier);
		return ($fichier_valide);
	}

	// fonction cutExtension (image)
	//------------------------------------------------------------------
	// Permet de supprimer l'extension de l'image passée en paramètre
	// (utilisé dans la bibliothèque).
	//------------------------------------------------------------------
	function cutExtension ($fichier){
		//$extension = array (".gif",".bmp",".jpg",".png");
		$extension= array (".jpg");
		//$cut = array ("","","","");
		$cut = array ("","");
		return (str_replace ($extension, $cut, $fichier));
	}

?>
