<?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Astellia
*
*	Composant de base version cb_4.0.0.00
*
*
*
* 	- maj 11:10 21/01/2008 - maxime - Deux modes d'affichage ( portrait/ paysage ) 
*						   - On limite à 80 le nombre de caractère du titre dans l'entête
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
	/*
		Class acurioPDF
		Cette classe permet de rédénir les classes
		Header et footer de la classe FPDF.
		christophe
		last MAJ : 18 10 2005
	*/

	include_once(REP_PHYSIQUE_NIVEAU_0.'class/acurioPDF_bookmark.php');
	include_once(REP_PHYSIQUE_NIVEAU_0.'class/acurioPDF_index.php');

	class acurioPDF extends PDF_Index{

		// Définit le titre à afficher dans les en-têtes.
		function setHeaderTitle($label){
			$this->headerTitle = $label;
		}

		// Définit  la date à afficher dans le footer après published.
		function setFooterDate($label){
			$this->footerDate = $label;
		}
		
		// Définit le type de mise en page
		function setDisplayMode($display_mode){
			$this->display_mode = $display_mode;
		}
		
		// définie la'affichage de l'en-tête de chaque page du fichier PDF.
		function Header(){
			
			$resolution = 2.835; 	// Permet de convertir la taille pixel > cm de simages.
			$this->largeurDoc = ($this->display_mode == 'landscape') ? 297 : 210;		// largeur du document pdf.
			$this->margin = 10;				// Marges droites et gauche pour les éléments.
			
			$widthFirstLine = 15;	// longueur de la première ligne à gauche.
			$heightMax = 12;		// hauteur maximale des images.
			$decalage = 1;			// Décalage entre les traits et les images.
			$image_gauche = get_sys_global_parameters("pdf_logo_operateur");
			$image_droite = get_sys_global_parameters("pdf_logo_dev");
			$headerTitle = 	(isset($this->headerTitle)) ? $this->headerTitle : "";
			$dateHeader = date("F j Y, g:i a");


			// On récupère les tailles des images de l'en-tête.
			if(getimagesize(REP_PHYSIQUE_NIVEAU_0.$image_gauche)){
				list($width_image_gauche, $height_image_gauche,,) = getimagesize(REP_PHYSIQUE_NIVEAU_0.$image_gauche);
				list($width_image_droite, $height_image_droite,,) = getimagesize(REP_PHYSIQUE_NIVEAU_0.$image_droite);
			} else {
				echo "Error : the PDF file can't be built. [No image file detected for the PDF header]";
				exit;
			}

			// On ajuste les dimensions des images en fonction de la hauteur maximale maxHeight.
			$width_image_gauche = 	round($height_image_gauche / $resolution) > $heightMax ? round((round($width_image_gauche / $resolution)*$heightMax / round($height_image_gauche / $resolution))) : round($width_image_gauche / $resolution);
			$width_image_droite = 	round($height_image_droite / $resolution) > $heightMax ? round((round($width_image_droite / $resolution)*$heightMax / round($height_image_droite / $resolution))) : round($width_image_droite / $resolution);
			$height_image_gauche = 	round($height_image_gauche / $resolution) > $heightMax ? $heightMax : round($height_image_gauche / $resolution);
			$height_image_droite = 	round($height_image_droite / $resolution) > $heightMax ? $heightMax : round($height_image_droite / $resolution);

			$hauteurMax = ($height_image_droite > $height_image_gauche) ? $height_image_droite : $height_image_gauche;

			
			// Centre Y en fonction du milieu de la hauteur de la plus haute image.
			$lineYpos = ($heightMax / 2) + $this->margin;
			$tailleTitre = $this->GetStringWidth($headerTitle);

			// On positionne et on affiche les éléments.
			$this->SetFont('Arial','B',13);
			$this->SetTextColor(0);
			$posTitre = round(($this->largeurDoc/2) - round($this->GetStringWidth($headerTitle)/2));
			$espaceRestant = round($this->largeurDoc - $this->GetStringWidth($headerTitle));
			$this->setX($posTitre);
			
			$espaceRestantText = round($this->largeurDoc - ($decalage * 4 + $this->margin * 2 + 5 + widthFirstLine + $width_image_gauche + $width_image_droite) );

			$h = ( $this->GetStringWidth($headerTitle) > $espaceRestantText) ? 30 : 12;

			// if($h > 12){
				// $pos = strpos($headerTitle," ");
				
				// if( $pos !==false)
					// $headerTitle[$pos[1]] = '\n';
				
				// echo "pos : <b>";print_r($pos);echo "<br/>$headerTitle</b>";
			// }
			
			
			$this->Cell(round($this->GetStringWidth($headerTitle)), $h, $headerTitle,0,1,'C',0);		// on positionne le titre au centre.
			if( $h == 12 ){
				$this->line($this->margin, $lineYpos, $widthFirstLine+$this->margin, $lineYpos);	// ligne avant le logo opérateur
			}
			$this->SetFont('Arial','I',8);
			$this->Text($this->margin+$widthFirstLine+$decalage+$decalage+$width_image_gauche,$lineYpos-3,$dateHeader);
			$this->SetFont('Arial','B',13);
			if( $h == 12 ){
				$this->line($this->margin+$widthFirstLine+$decalage+$decalage+$width_image_gauche, $lineYpos, $posTitre-($decalage+5), $lineYpos);	// ligne entre le logo opérateur et le titre.
				$this->line($posTitre+$decalage+round($this->GetStringWidth($headerTitle))+5, $lineYpos, ($this->largeurDoc-($width_image_droite+$this->margin+$decalage)), $lineYpos);	// ligne entre le titre et le logo constructeur.
			}if (file_exists(REP_PHYSIQUE_NIVEAU_0.$image_gauche)) {
				$this->Image(REP_PHYSIQUE_NIVEAU_0.$image_gauche,  $widthFirstLine+$this->margin+$decalage,$this->margin,$width_image_gauche,$height_image_gauche);
				$this->Image(REP_PHYSIQUE_NIVEAU_0.$image_droite, ($this->largeurDoc-($width_image_droite+$this->margin)),$this->margin,$width_image_droite,$height_image_droite);
			}
			$this->Ln(8);

		}

		// Définit l'affichage du pied de page de chaque fichier PDF.
		function Footer(){
			$this->SetTextColor(0);
			$texte_footer = get_sys_global_parameters("pdf_footer_text");
			//Positionnement à 1,5 cm du bas
			$this->SetY(-15);
			// On dessine un rectangle gris.
			$this->SetFillColor(235,235,235);
			$this->SetDrawColor(220,220,220);
			$this->Rect($this->margin,$this->GetY()+2,$this->largeurDoc-($this->margin*2),5,'DF');
			$this->SetFont('Arial','I',8);
			$this->Cell(0,10,$texte_footer,0,0,'L');
			$this->SetX(190);
			$this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'R');
		}

	}
?>
