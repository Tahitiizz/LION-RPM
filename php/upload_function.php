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
	// Fonctions permettant de gérer l'upload d'images.

	// fonction uploadImage ($rep, $tab, $type)
	//--------------------------------------------------------------
	// Fonction qui permet d'uploader toutes les images
	// répertoriées dans la variable $tab
	// vers le répertoire cible $rep.
	function uploadImage ($rep, $tab){
		// taille maximale des images uploadées.
		$taille_image = 100000; // = 100 ko

		// Propriétés de la nouvelle image crée.
		$largeur_vignette = 130;
		$hauteur_vignette = 55;
		$chemin_vignette = $rep;

		$a = 0;
		// On parcours toutes les images envoyées.
		$bool = false;
		while ($a < sizeof ($tab["file"]["name"])){
			// On vérifie si le fichier à uploader est :
			// - une image.
			// - est un type d'image autorisé.
			if($tab["file"]["tmp_name"][$a] != "none"){
				if (isImage ($tab["file"]["tmp_name"][$a], $taille_image)) {
					// On enlève les caractères invalides du nom du fichiers.
					$tab["file"]["name"][$a] = renameImage($tab["file"]["name"][$a]);

					//echo "nom fichier temp : ".$tab["file"]["tmp_name"][$a]."<br> nom fichier ".$tab["file"]["name"][$a]."<br>"; exit;
					if (!(move_uploaded_file ($tab["file"]["tmp_name"][$a], $rep . "logo_operateur.jpg"))){
						$_SESSION["msg_erreur"] = " Server copy error for  " . $rep . $tab["file"]["name"][$a];
					} else {
						$_SESSION["msg_erreur"] = "The picture has been updated.";
						//echo ($tab["file"]["name"][$a]);
						// On redimensionne l'image téléchargée
						creerVignette ("logo_operateur.jpg", $largeur_vignette, $hauteur_vignette, $rep);
						$bool = true;
					}
				}
			} else {
				$_SESSION["msg_erreur"] = "No file found.";
			}
			$a++;
		}
		return $bool;
	}
?>
