<?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*	
*	- maj 09/06/2008 - Correction du bug maxime : Si le fichier existe on le supprime avant  de le regénérer
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
*	@cb2000b_iu2000b@
*
*	20/07/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.0.0
*
*	Parser version iu_2.0.0.0
*/
?>
<?php
/*
	maj 08 04 2006 christophe : prise en compte du display des alarmes qui ont trop de résultats. ligne 156 / et fin du fichier (fonction WriteHTML) , ligne 207 (affichage en rouge)
		ligne 216, ligne 469, ligne 160
	maj 20/04/2006  maj des largeurs par défaut des colonnes avec l'axe 3

	- maj DELTA christophe 26 04 2006. tout le fichier a été changé (le fichier de la 1.1.9 écrase celui de la 1.2.0.0 car
    il y a eu beaucoup de modifications pour l'intégration de roaming dans la 1.1.9)
  - maj xavier 17/08/06 : ligne 305 résolution du bug du saut de page intempestif

	- maj 11/04/2007 Gwénaël
			suppression de la colonne "Critical Level" dans la fonction getColWidth suite à la suppression dans le fichier alarmCreateHTML.class.php
			modification pour qu'un saut de ligne \n soit prise en compte dans une cellule

	- maj 09/01/2008 Gwénaël
		Ajout de la fonction MultiCell2 pour pouvoir ajouter les liens dans une cellule

  02/08/2011 MMT Bz 23036 Calcul du nombre de ligne dans une cellule ne prends pas en compte
		        le nombre de '\n'  et est inexat, il faut utiliser la function NbLines
		       
  14/11/2011 ACS BZ 24518 error message in report preview with slave 5.0
  
*/
// Classe utilisée pour la génération des fichiers PDF.
include_once(REP_PHYSIQUE_NIVEAU_0 . 'pdf/fpdf153/fpdf.php');
include_once(REP_PHYSIQUE_NIVEAU_0 . 'class/acurioPDF.class.php');
require_once(REP_PHYSIQUE_NIVEAU_0 .'class/htmlparser.inc');

class PDF_HTML_Table extends acurioPDF{
	var $B; // bold (utilisé dans la fonction SetStyle)
	var $I; // italic (utilisé dans la fonction SetStyle)
	var $U; // underline (utilisé dans la fonction SetStyle)
	var $HREF;
	// modif 18/12/2007 Gwénaël
		// Ajout d'une variable pour pouvoir ajuster la lagueur des colonnes dans le PDF
		// en fonction du type de tableau à afficher
	var $typeTableau;

	function WriteHTML2($html)
	{
		//HTML parser
		$html = str_replace("\n",' ',$html);
		$a = 	preg_split('/<(.*)>/U',$html,-1,PREG_SPLIT_DELIM_CAPTURE);
//		__debug($html, '$html');
//		__debug($a, '$a');
		foreach($a as $i=>$e)
		{
			if($i%2==0)
			{
				//Text
				if($this->HREF)
					$this->PutLink($this->HREF,$e);
				else
					$this->Write(5,$e);
			}
			else
			{
				//Tag
				if($e{0}=='/')
					$this->CloseTag(strtoupper(substr($e,1)));
				else
				{
					//Extract attributes
					$a2=explode(' ',$e);
					$tag=strtoupper(array_shift($a2));
					$attr=array();
					foreach($a2 as $v)
						if(ereg('^([^=]*)=["\']?([^"\']*)["\']?$',$v,$a3))
							$attr[strtoupper($a3[1])]=$a3[2];
					$this->OpenTag($tag,$attr);
				}
			}
		}
	}
	
	function OpenTag($tag,$attr)
	{
		//Opening tag
		if($tag=='B' or $tag=='I' or $tag=='U')
			$this->SetStyle($tag,true);
		if($tag=='A')
			$this->HREF=$attr['HREF'];
		if($tag=='BR')
			$this->Ln(5);
		if($tag=='P')
			$this->Ln(10);
	}

	function CloseTag($tag)
	{
		//Closing tag
		if($tag=='B' or $tag=='I' or $tag=='U')
			$this->SetStyle($tag,false);
		if($tag=='A')
			$this->HREF='';
		if($tag=='P')
			$this->Ln(10);
	}

	function SetStyle($tag,$enable)
	{
		//Modify style and select corresponding font
		$this->$tag+=($enable ? 1 : -1);
		$style='';
		foreach(array('B','I','U') as $s)
			if($this->$s>0)
				$style.=$s;
		$this->SetFont('',$style);
	}

	function PutLink($URL,$txt)
	{
		//Put a hyperlink
		$this->SetTextColor(0,0,255);
		$this->SetStyle('U',true);
		$this->Write(5,$txt,$URL);
		$this->SetStyle('U',false);
		$this->SetTextColor(0);
	}

	/**
	 * Renvoie la largeur de la colonne définie par défaut
	 *
	 *	- maj 11/04/2007 Gwénaël :
	 *			suppression de la colonne "Critical level" (la largeur était de 22)
	 *			modification de la largeur de "Alarm name" de +11 (ancienne valeur 45)
	 *			modification de la largeur de "Trigger - Threshold" de +11 (ancienne valeur 55)
	 *
	 * @param int $colNumber : numéro de la colonne
	 * @return int
	 */
	function getColWidth($colNumber)
	{	
		
		if ( $this->typeTableau == 'iterative' ) {
			if($this->axe3){
				switch($colNumber){
					case 0 : 	$colWidth = 45;
								if ($this->sous_mode == 'condense') $colWidth = 56; //Alarm name (old value 45)
								if ($this->sous_mode == 'elem_reseau') $colWidth = 32;				//Network aggregation
								break;
					case 1 : 	$colWidth = 28;
								if ($this->sous_mode == 'condense') $colWidth = 28; //Source Time
								if ($this->sous_mode == 'elem_reseau') $colWidth = 56;				//Alarm name (old value 45)
								break;
					case 2 : 	$colWidth = 32;
								if ($this->sous_mode == 'condense') $colWidth = 32; //Network aggregation
								if ($this->sous_mode == 'elem_reseau') $colWidth = 28;				//Source Time
								break;
					case 3 : 	$colWidth = 45;
								break;
					case 4 : 	$colWidth = 66; // Trigger - Threshold (old value 55)
								break;
					case 5 : 	$colWidth = 18;
								break;
					case 6 : 	$colWidth = 15;
								break;
					case 7 : 	$colWidth = 26;
								break;
					case 8 : 	$colWidth = 35; //Calculation time
								break;
					case 9 : 	$colWidth = 35;
								break;
					default : 	$colWidth = 35;
				}
			}
			else {
				switch($colNumber){
					case 0 : 	$colWidth = 18; // Date
								break;
					case 1 : 	$colWidth = 20; // Critical level 
								break;
					case 2 : 	$colWidth = 60; // Threshold rawkpi OU trigger
								break;
					case 3 : 	$colWidth = 18; // Threshold OU Condition
								break;
					case 4 : 	$colWidth = 20; // Value
								break;
					case 5 : 	$colWidth = 60; // Trigger
								break;
					case 6 : 	$colWidth = 20; // Condition
								break;
					case 7 : 	$colWidth = 20; // value
								break;
					case 8 : 	$colWidth = 40; // Additional details
								break;
					default : 	$colWidth = 35;
				}
			}
		}
		elseif ( $this->typeTableau == 'dynamic' ) {
			switch($colNumber){
                            // Mantis 4178 : split additional details into two columns : Correction des valeurs
				case 0 : 	$colWidth = 45; // Alarm name 
							break;
				case 1 : 	$colWidth = 26; // Source Time
							break;
				case 2 : 	$colWidth = 50; // Network aggregation
							break;
				case 3 : 	$colWidth = 66; // Trigger
							break;
				case 4 : 	$colWidth = 17; // Condition
							break;
				case 5 : 	$colWidth = 15; // Value
							break;
                                // Mantis 4178 : split additional details into two columns 
				case 6 : 	$colWidth = 17; // Additional details : average
							break;
				case 7 : 	$colWidth = 19; // Additional details : overrun
							break;
				case 8 : 	$colWidth = 24; // Calculation time
							break;
				default : 	$colWidth = 35;
			}
		}
		elseif ( $this->typeTableau == 'additionnalFieldIterative' ) {
			switch($colNumber){
				case 0 : 	$colWidth = 18; // Date
							break;
				case 1 : 	$colWidth = 60; // Additional field
							break;
				case 2 : 	$colWidth = 20; // Value
							break;					
				default : 	$colWidth = 35;
			}
		}
		elseif ( $this->typeTableau == 'additionnalField' ) {
			switch($colNumber){
				case 0 : 	$colWidth = 60; // Additional field
							break;
				case 1 : 	$colWidth = 20; // Value
							break;					
				default : 	$colWidth = 35;
			}
		}
		elseif ( $this->typeTableau == 'topworst' ) {
			switch($colNumber){
				case 0 : 	$colWidth = 20; // Critical level 
							break;
				case 1 : 	$colWidth = 60; // Sort field
							break;
				case 2 : 	$colWidth = 18; // Sort by
							break;
				case 3 : 	$colWidth = 20; // Value
							break;
				case 4 : 	$colWidth = 60; // Trigger
							break;
				case 5 : 	$colWidth = 20; // Condition
							break;
				case 6 : 	$colWidth = 20; // value
							break;
				default : 	$colWidth = 35;
			}
		}
		else {
			if($this->axe3){
				switch($colNumber){
					case 0 : 	$colWidth = 45;
								if ($this->sous_mode == 'condense') $colWidth = 56; //Alarm name (old value 45)
								if ($this->sous_mode == 'elem_reseau') $colWidth = 32;				//Network aggregation
								break;
					case 1 : 	$colWidth = 28;
								if ($this->sous_mode == 'condense') $colWidth = 28; //Source Time
								if ($this->sous_mode == 'elem_reseau') $colWidth = 56;				//Alarm name (old value 45)
								break;
					case 2 : 	$colWidth = 32;
								if ($this->sous_mode == 'condense') $colWidth = 32; //Network aggregation
								if ($this->sous_mode == 'elem_reseau') $colWidth = 28;				//Source Time
								break;
					case 3 : 	$colWidth = 45;
								break;
					case 4 : 	$colWidth = 66; // Trigger - Threshold (old value 55)
								break;
					case 5 : 	$colWidth = 18;
								break;
					case 6 : 	$colWidth = 15;
								break;
					case 7 : 	$colWidth = 26;
								break;
					case 8 : 	$colWidth = 35; //Calculation time
								break;
					case 9 : 	$colWidth = 35;
								break;
					default : 	$colWidth = 35;
				}
			}
			else {
				switch($colNumber){
					case 0 : 	$colWidth = 45;
								if ($this->sous_mode == 'condense') $colWidth = 56; //Alarm name (old value 45)
								if ($this->sous_mode == 'elem_reseau') $colWidth = 32;                //Network aggregation
								if ($this->sous_mode == 'detail') $colWidth = 45;
								break;
					case 1 : 	$colWidth = 28;
								if ($this->sous_mode == 'condense') $colWidth = 28; //Source Time
								if ($this->sous_mode == 'elem_reseau') $colWidth = 56;                //Alarm name (old value 45)
								if ($this->sous_mode == 'detail') $colWidth = 45;
								break;
                                        // Mantis 4178 : split additional details into two columns
                                        // utilisation de la place libérée par la suppression de "additional details"
					case 2 : 	$colWidth = 50;
								if ($this->sous_mode == 'condense') $colWidth = 32;  //Network aggregation
								if ($this->sous_mode == 'elem_reseau') $colWidth = 28;                //Source Time
								if ($this->sous_mode == 'detail') $colWidth = 25;
								break;
					case 3 : 	$colWidth = 66; // Trigger - Threshold (old value 55)
								if ($this->sous_mode == 'detail') $colWidth = 25;
								break;
					case 4 : 	$colWidth = 18;
								if ($this->sous_mode == 'detail') $colWidth = 45;
								break;
					case 5 : 	$colWidth = 15;
								if ($this->sous_mode == 'detail') $colWidth = 25;
								break;
					case 6 : 	$colWidth = 26;
								if ($this->sous_mode == 'detail') $colWidth = 25;
								break;
					case 7 : 	$colWidth = 35; //Calculation time
								break;
					case 8 : 	$colWidth = 35;
								break;
					case 9 : 	$colWidth = 35;
								break;
					default : 	$colWidth = 35;
				}
			}
		}
		if($this->sous_mode == "autre"){
			switch($colNumber){
				case 0 : 	$colWidth = 35;
							break;
				default : 	$colWidth = 8;
			}
		}
		// Cas où il s'agit d'une alarme qui a trop de résultats. il n'y a alors que 2 colonnes affichées.
		//echo "$this->nb_total > $this->alarm_result_limit_nb<br>";

		if($this->display_error_style){
			switch($colNumber){
				case 0 : 	$colWidth = 20;
							break;
				case 1 : 	$colWidth = 100;
							break;
				default : 	$colWidth = 100;
			}
		}

		return $colWidth;
	}

	/**
	 * Création du tableau dans le PDF
	 *
	 *	- maj 11/04/2007 Gwénaël
	 *			modification pour que la saut de ligne "\n" soit pris en compte et effectue bien le saut de ligne (la cellule doit être sur deux lignes sinon problème d'affichage)
	 *			il faut que le \n soit entre simple cote [ex: '\n' ] ou soit mettre deux anti-slash s'il se trouve entre double cote [ex : "\\n" ]
	 *
	 * @param Array $data : tableau contenant les données à afficher
	 * @param Array $w : tableau contenant la largeur des cellules
	 */
	function WriteTable($data, $w) {
//		__debug('<hr><div style="text-align:left">');
//		__debug($data, '$data');
//		__debug($this->typeTableau, '$$this->typeTableau');
				
		$this->SetLineWidth(.3);
		$cpt = 0;
		$current_style = "";
		$prec_style = "";
		$row_prec = "";
		
		for($k = 0; $k < count($data); $k++){
			$row = $data[$k];
			$this->SetDrawColor(0);
			if((strlen(str_replace(" ","",$row[0])) != 0) and ($k!=0)) $cpt++;
			if($cpt == 0){
				// style apppliqué aux en-têtes des tableaux.
				$this->SetFont('Arial','B',8);
				$this->SetFillColor(105,105,105);
				$this->SetTextColor(255,255,255);
				$current_style = "titre";
			}
			else if(($cpt % 2) == 0 ){
				// On affiche le même fond tant que l'on reste dans le même result.
				if(strlen(str_replace(" ","",$row[0])) != 0){
					// Fond différents une ligne sur 2
					$this->SetFont('Arial','',6);
					$this->SetFillColor(214,214,214);
					$this->SetTextColor(0);
					$current_style = "gris";
				}
			}
			else {
				if(strlen(str_replace(" ","",$row[0])) != 0){
					$this->SetFont('Arial','',6);
					$this->SetFillColor(255,255,255);
					$this->SetTextColor(0);
					$current_style = "blanc";
				}
			}	
			// modif du if pr prise en compte du nb limite d'alarmes calculées.
			if($this->nb_total == 0){
				$this->SetFont('Arial','B',8);
				$this->SetFillColor(255,255,255);
				$this->SetTextColor(255,0,0);
				$this->SetDrawColor(255,255,255);
				$current_style = "blanc";
				$this->display_error_style = true;
			}

			if((strlen(str_replace(" ","",$row[0])) != 0) && (strlen(str_replace(" ","",$row_prec)) == 0)){
				if($prec_style == $current_style){
					if($current_style == "blanc"){
						$this->SetFont('Arial','',6);
						$this->SetFillColor(214,214,214);
						$this->SetTextColor(0);
						$current_style = "gris";
					} else if($current_style == "gris") {
						$this->SetFont('Arial','',6);
						$this->SetFillColor(255,255,255);
						$this->SetTextColor(0);
						$current_style = "blanc";
					}
				}
			}
			
			/*
			if(($this->nb_total > 0) and ($row[0]!='')) {
				if($this->displayJavascript) {
					if($cpt > $this->nb_total) $cpt = $this->nb_total;
					
					?>
					<script>
						document.getElementById('texteLoader').innerHTML = "Building display <br>(<?=$cpt?> elements)";
					</script>
					<?
				}
			}
			*/

			$prec_style = $current_style;
			$row_prec = $row[0];
		    unset($nb_ligne_col);
		    $heightMax = 5;

			// On vérifier si une des colonnes de la ligne contient un texte qui dépasse la largeur définie.
			for($i=0;$i<count($row);$i++){
				$nb_ligne_col[$i] = 1;
				// modif 18/12/2007 Gwénaël
					// On arrondit la longueur de la chaine pour éviter d'avoir des cellules sur 2 lignes inutiles
				$chaine_to_display = trim($row[$i]);
				$split = preg_split('/<(.*)>/U', $chaine_to_display, -1, PREG_SPLIT_DELIM_CAPTURE);
				if ( count($split) == 5 ) {
					$chaine_to_display = $split[2];
				}

				//02/08/2011 MMT Bz 23036 Calcul du nomvre de ligne dans une cellule ne prends pas en compte
				// le nombre de '\n'  et est inexat, il faut utiliser la function NbLines
				$nb_ligne_col[$i] = $this->NbLines($this->getColWidth($i),$chaine_to_display);
				$height_col = $nb_ligne_col[$i] * 5;	// On calcule la hauteur de cellule
				if ($heightMax < $height_col) $heightMax = $height_col;
			}
			
			// on vérifie s'il ne faut pas sauter une page
			if ($heightMax)
				$this->CheckPageBreak($heightMax);
			else
				$this->CheckPageBreak($h);

			// On construit les colonnes.
			for($i=0;$i<count($row);$i++){
				
				$chaine_to_display = trim($row[$i]);
				$split = preg_split('/<(.*)>/U', $chaine_to_display, -1, PREG_SPLIT_DELIM_CAPTURE);
				if ( count($split) == 5 ) {
					$split2=explode(' ',$split[1]);
					$tag=strtoupper(array_shift($split2));
					$attr=array();
					foreach($split2 as $_v)
						if(ereg('^([^=]*)=["\']?([^"\']*)["\']?$',$_v,$split3))
							$attr[strtoupper($split3[1])]=$split3[2];
					$HREF = $attr['HREF'];
					$chaine_to_display = $split[2];
				}
				$chaine_to_display = str_replace('\n', "\n", $chaine_to_display);

				$x=$this->GetX();
				$y=$this->GetY();

				// $this->pageBreak permet de détecter si on vient de changer de page lorsque le tableau fait plusieurs page.
				// Si $cpt == 0 on stock le contenu de l'en-tête dans un tableau. pour l'afficher sur la page suivante
				if($cpt == 0){
					$tableHeaderBuffer[] = $chaine_to_display;
				}

				// Si heightMax est défini, c'est qu'une des colonnes a un texte trop grand pour n'être affiché que sur une seule ligne
				$h = isset($heightMax) ? $heightMax/$nb_ligne_col[$i] : 5;
				
				// Changement de page
				if($this->pageBreak) {
					$this->SetFont('Arial','B',8);
					$this->SetFillColor(105,105,105);
					$this->SetTextColor(255,255,255);

					// Si 0 result est affiché en début d'une nouvelle page.
					if($this->nb_total == 0){
						$this->SetFont('Arial','B',8);
						$this->SetFillColor(255,255,255);
						$this->SetTextColor(255,0,0);
						$this->SetDrawColor(255,255,255);
						$current_style = "blanc";
					}

					// Cas où l'on change de page et le tableau affiché sur la page précédente ce poursuit sur la nouvelle, donc
					// on réaffiche l'en-tête du tableau et on décrémente le compteur cpt (sinon on perds une donnée).
					// Et on repositionne le curseur sur le tableau (le curseur vient du foreach).
					if($cpt <= $this->nb_total){
						// Quand c'est l'en-tête du tableau la hauteur et 5 par défaut.
						$h = 5;
						$this->MultiCell($this->getColWidth($i),$h,trim($tableHeaderBuffer[$i]),1,'C',1);
					}

					// On remet le style courrant.
					if($current_style == "gris"){
						$this->SetFont('Arial','',6);
						$this->SetFillColor(214,214,214);
						$this->SetTextColor(0);
					}
					else if($current_style == "blanc"){
						$this->SetFont('Arial','',6);
						$this->SetFillColor(255,255,255);
						$this->SetTextColor(0);
					}

				}
				else {
					// cette colonne affiche le compteur d'alarme utilisé pour le changement de couleur
					if (($this->debug) and ($i==count($row)-1) and ($k!=0))
						$chaine_to_display = "alarme calculée $cpt";

					// >>>>>>>>>>>>>>>>>>>>
					// modif 09/01/2008 Gwénaël
						// ajout des liens externes
					if ( isset($HREF) ) {
						$this->SetTextColor(0,0,255);
						$this->MultiCell2($this->getColWidth($i),$h,$chaine_to_display,1,'C',1,$HREF);
						$this->SetTextColor(0);
						unset($HREF);
					}
					else {
						$this->MultiCell($this->getColWidth($i),$h,$chaine_to_display,1,'C',1);
					}
				}

				$this->SetXY($x+$this->getColWidth($i),$y);
			}
			if((strlen(str_replace(" ","",$row[0])) != 0) and ($this->pageBreak)) $cpt--;
			if($this->pageBreak) $k--;
			$this->pageBreak = false;
			$this->Ln($h);
		}
		$this->SetTextColor(0);
	}


	/**
	 * Get the number of line that a string will take in a cell counting the number of \n and
	 * automatic break
	 *
	 * 02/08/2011 MMT Bz 23036 Calcul du nombre de ligne dans une cellule ne prends pas en compte
	 * le nombre de '\n'
	 *
	 * @param int $w  width of the cell
	 * @param String $txt  text of the cell
	 * @return int exact number of lines that the string will take in the cell width
	 */
	function NbLines($w,$txt)
	{
		// 02/08/2011 MMT Bz 23036 add \n management
		$lines = explode('\n', $txt);
		$ret = 0;
		foreach ($lines as $lineTxt){

			//Computes the number of lines a MultiCell of width w will take
			$cw=&$this->CurrentFont['cw'];
			if($w==0)
				$w=$this->w-$this->rMargin-$this->x;
			$wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
			$s=str_replace("\r",'',$lineTxt);
			$nb=strlen($s);
			if($nb>0 and $s[$nb-1]=="\n")
				$nb--;
			$sep=-1;
			$i=0;
			$j=0;
			$l=0;
			$nl=1;
			while($i<$nb)
			{
				$c=$s[$i];
				if($c=="\n")
				{
					$i++;
					$sep=-1;
					$j=$i;
					$l=0;
					$nl++;
					continue;
				}
				if($c==' ')
					$sep=$i;
				$l+=$cw[$c];
				if($l>$wmax)
				{
					if($sep==-1)
					{
						if($i==$j)
							$i++;
					}
					else
						$i=$sep+1;
					$sep=-1;
					$j=$i;
					$l=0;
					$nl++;
				}
				else
					$i++;
			}
			$ret = $ret + $nl;
		}
		return $ret;
	}


	// Vérifie si on doit effectuer un saut de page ou non.
	function CheckPageBreak($h){
		$this->PageBreakTrigger = 190;	// Seuil de saut de page automatique.
		$this->pageBreak = false;
		// Si il y a un dépassement alors on passe à la page suivante
		if($this->GetY()+$h > $this->PageBreakTrigger){
				$this->AddPage($this->CurOrientation);
				$this->pageBreak = true;
		}
	}

	function ReplaceHTML($html){
		$html = str_replace( '<li>', "\n<br> - " , $html );
		$html = str_replace( '<LI>', "\n - " , $html );
		$html = str_replace( '</ul>', "\n\n" , $html );
		$html = str_replace( '<strong>', "<b>" , $html );
		$html = str_replace( '</strong>', "</b>" , $html );
		$html = str_replace( '&#160;', "\n" , $html );
		$html = str_replace( '&nbsp;', " " , $html );
		$html = str_replace( '&quot;', "\"" , $html );
		$html = str_replace( '&#39;', "'" , $html );
		return $html;
	}

	function ParseTable($Table){
		$_var='';
		$htmlText = $Table;
		$parser = new HtmlParser ($htmlText);
		while ($parser->parse()) {
//			__debug($parser->iNodeName . ' - '.$parser->iNodeValue , "\n".'$parser->iNodeName  - $parser->iNodeValue');
			
			if(strtolower($parser->iNodeName)=='table')
			{
				if($parser->iNodeType == NODE_TYPE_ENDELEMENT)
					$_var .='/::';
				else
					$_var .='::';
			}

			if(strtolower($parser->iNodeName)=='tr')
			{
				if($parser->iNodeType == NODE_TYPE_ENDELEMENT)
					$_var .='!-:'; //opening row
				else
					$_var .=':-!'; //closing row
			}
			if(strtolower($parser->iNodeName)=='td' && $parser->iNodeType == NODE_TYPE_ENDELEMENT)
			{
				$_var .='#,#';
			}
			if ($parser->iNodeName=='Text' && isset($parser->iNodeValue))
			{
				$_var .= $parser->iNodeValue;
			}
			if ( $parser->iNodeName=='a' ) {
//				__debug($parser->iNodeType, "\n".'$parser->iNodeType');
//				__debug($parser->iNodeValue, "\n".'$parser->iNodeValue');
//				__debug($parser->iNodeAttributes, "\n".'$parser->iNodeAttributes');
				if ( $parser->iNodeType == NODE_TYPE_ENDELEMENT )
					$_var .= '<¤a>';
				else
					$_var .= '<a href="'.str_replace('::', '§§', str_replace('/', '¤', $parser->iNodeAttributes['href'])).'">';
			}
			
		}
//		__debug($_var, '$_var');
		$elems = split(':-!',str_replace('/','',str_replace('::','',str_replace('!-:','',$_var)))); //opening row
		foreach($elems as $key=>$value)
		{
			if(trim($value)!='')
			{
				$elems2 = split('#,#',$value);
				array_pop($elems2);
				$elems2 = str_replace('§§', '::', str_replace('¤', '/', $elems2));
				$data[] = $elems2;
			}
		}
		return $data;
	}


	function generatePDF($sous_mode,$header_title) {
		global $id_user;

		$this->sous_mode = $sous_mode;

		// Configuration du fichier PDF.
		$dir_pdf = get_sys_global_parameters("pdf_save_dir");
		$this->dir_saving_pdf_file = REP_PHYSIQUE_NIVEAU_0.$dir_pdf;

		$this->alarm_result_limit_nb = get_sys_global_parameters("alarm_result_limit");
		$this->displayJavascript = ($id_user != -1) ? true : false;
		$this->debug = get_sys_debug('alarm_export_pdf');   // Affichage du mode debug

		// Ecriture de l'entête et du bas de page du fichier PDF.
		$this->setDisplayMode('landscape');
		$this->orientation_pdf = 'L';
		$this->setHeaderTitle($header_title);
		$this->setFooterDate("date footer");
		$this->SetAutoPageBreak(true);
		$this->AliasNbPages();

		$fpdf_fontpath = REP_PHYSIQUE_NIVEAU_0 . 'pdf/fpdf153/font/';
		define('FPDF_FONTPATH', $fpdf_fontpath);
	}


	function set_PDF_file_name ($PDF_name) {
		$this->nom_pdf = $PDF_name;
	}


	function get_PDF_file_name () {
		return $this->nom_pdf;
	}


	function set_PDF_directory ($PDF_directory) {
		$this->dir_saving_pdf_file = $PDF_directory;
	}


	function savePDF () {
		if ($this->get_PDF_file_name() != '') {
			// sauvegarde du fichier PDF
			
			$file = $this->dir_saving_pdf_file.$this->nom_pdf;
			
			// maj 09/06/2008 - maxime : Si le fichier existe on le supprime avant  de le regénérer
			// 14/11/2011 ACS BZ 24518 error message in report preview with slave 5.0
			if (is_file($file)) {
				unlink($file);
			}
			$this->Output($file ,'F');	// paramètre F pour forcer l'enregistrement sur le serveur.
		}
	}


	function WriteHTML($tab_html){
//		__debug('<hr><b>DEBUT '. __FILE__ .' : '. __LINE__ .'</b><hr><pre style="text-align:left">');
//		__debug(  $tab_html  , '<br>$tab_html [<span style="color:red">' . __FILE__ .' ligne  ' . __LINE__ .'</span>]');

		for ($num_page=0; $num_page<count($tab_html); $num_page++) {

			if ($tab_html[$num_page][0] != '') {

				// écriture du fichier PDF
				$this->AddPage('L');

				// écriture du titre du tableau
				$this->SetFont('Arial','',6);
				$this->setX(10);
				$this->SetFont('Arial','B',10);
				$this->Image(REP_PHYSIQUE_NIVEAU_0.'images/icones/pdf_alarm_titre_arrow.png', $this->GetX(), $this->GetY());
				$this->setX(15);
				$this->SetTextColor(0);
				$this->Cell(0,8,$tab_html[$num_page][0],0,0,'L');

				// ajout du tableau dans la liste des bookmark
				// le trim(,chr(160)) est là parce que le caractère euro apparaissant dans les noms des signets dans Acrobat Reader.
				$this->Bookmark(trim($tab_html[$num_page][0],chr(160)));
			}

			$html = "<br>".$tab_html[$num_page][1]."<br>";			// ???
			$this->display_error_style = false;
			$html = $this->ReplaceHTML($html);
			$this->nb_total = $tab_html[$num_page][2];
			//echo "<br>>> $this->nb_total<br>";
			$this->pageBreak = false;
			$this->axe3 = $flag_axe3;

			//Search for a table
			$start = strpos(strtolower($html),'<table');
			$end = strpos(strtolower($html),'</table');
			if($start!==false && $end!==false) {
				$this->WriteHTML2(substr($html,0,$start).'<BR>');

				$tableVar = substr($html,$start,$end-$start);
//				__debug($tableVar, '<pre style="text-align:left">$tableVar -------------------------------------');
				$tableData = $this->ParseTable($tableVar);
				// __debug($tableData, '<pre style="text-align:left">------------------------------------- $tableData -------------------------------------');

				for($i=1;$i<=count($tableData[0]);$i++) {
					if($this->CurOrientation=='L')
						$w[] = abs(120/(count($tableData[0])-1))+24;
					else
						$w[] = abs(120/(count($tableData[0])-1))+5;
				}
				
				// En fonction des deux premiers noms des colonnes on détermine le type de tableau qui est affiché
				// ce qui permet de savoir la largeur de chaque colonne du tableau
				if ( trim($tableData[0][0]) == 'Date' && trim($tableData[0][1]) == 'Critical level' )
					$this->typeTableau = 'iterative';
				elseif ( trim($tableData[0][0]) == 'Date' && trim($tableData[0][1]) == 'Additional field' )
					$this->typeTableau = 'additionnalFieldIterative';
				elseif ( trim($tableData[0][0]) == 'Additional field' )
					$this->typeTableau = 'additionnalField';
				elseif ( trim($tableData[0][0]) == 'Critical level' && trim($tableData[0][1]) == 'Threshold rawkpi' )
					$this->typeTableau = 'dynamic';
				elseif ( trim($tableData[0][0]) == 'Critical level' && trim($tableData[0][1]) == 'Sort field' )
					$this->typeTableau = 'topworst';
                                // Mantis 4178 : split additional details into two columns : détermination du type de tableau dynamic grâce aux nouvelles colonnes
                                elseif ( trim($tableData[0][6]) == 'Average' && trim($tableData[0][7]) == 'Overrun (%)' )
					$this->typeTableau = 'dynamic';
				else
					$this->typeTableau = 'default';
				
				$this->WriteTable($tableData,$w);

				$this->WriteHTML2(substr($html,$end+8,strlen($html)-1).'<BR>');
			}
			else {
				$this->WriteHTML2($html);
			}
		}
	}
	
	/**
	 * Redéfinition de la fonction de la classe FPDF
	 *
	 */
	function MultiCell2($w,$h,$txt,$border=0,$align='J',$fill=0, $link='') {
		 //Output text with automatic or explicit line breaks
		$cw = &$this->CurrentFont ['cw'];
		if ($w == 0)
			$w = $this->w - $this->rMargin - $this->x;
		$wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
		$s = str_replace ( "\r", '', $txt );
		$nb = strlen ( $s );
		if ($nb > 0 and $s [$nb - 1] == "\n")
			$nb --;
		$b = 0;
		if ($border) {
			if ($border == 1) {
				$border = 'LTRB';
				$b = 'LRT';
				$b2 = 'LR';
			} else {
				$b2 = '';
				if (is_int ( strpos ( $border, 'L' ) ))
					$b2 .= 'L';
				if (is_int ( strpos ( $border, 'R' ) ))
					$b2 .= 'R';
				$b = is_int ( strpos ( $border, 'T' ) ) ? $b2 . 'T' : $b2;
			}
		}
		$sep = - 1;
		$i = 0;
		$j = 0;
		$l = 0;
		$ns = 0;
		$nl = 1;
		while ( $i < $nb ) {
			//Get next character
			$c = $s [$i];
			if ($c == "\n") {
				//Explicit line break
				if ($this->ws > 0) {
					$this->ws = 0;
					$this->_out ( '0 Tw' );
				}
				$this->Cell ( $w, $h, substr ( $s, $j, $i - $j ), $b, 2, $align, $fill, $link );
				$i ++;
				$sep = - 1;
				$j = $i;
				$l = 0;
				$ns = 0;
				$nl ++;
				if ($border and $nl == 2)
					$b = $b2;
				continue;
			}
			if ($c == ' ') {
				$sep = $i;
				$ls = $l;
				$ns ++;
			}
			$l += $cw [$c];
			if ($l > $wmax) {
				//Automatic line break
				if ($sep == - 1) {
					if ($i == $j)
						$i ++;
					if ($this->ws > 0) {
						$this->ws = 0;
						$this->_out ( '0 Tw' );
					}
					$this->Cell ( $w, $h, substr ( $s, $j, $i - $j ), $b, 2, $align, $fill, $link );
				} else {
					if ($align == 'J') {
						$this->ws = ($ns > 1) ? ($wmax - $ls) / 1000 * $this->FontSize / ($ns - 1) : 0;
						$this->_out ( sprintf ( '%.3f Tw', $this->ws * $this->k ) );
					}
					$this->Cell ( $w, $h, substr ( $s, $j, $sep - $j ), $b, 2, $align, $fill, $link );
					$i = $sep + 1;
				}
				$sep = - 1;
				$j = $i;
				$l = 0;
				$ns = 0;
				$nl ++;
				if ($border and $nl == 2)
					$b = $b2;
			} else
				$i ++;
		}
		//Last chunk
		if ($this->ws > 0) {
			$this->ws = 0;
			$this->_out ( '0 Tw' );
		}
		if ($border and is_int ( strpos ( $border, 'B' ) ))
			$b .= 'B';
		$this->Cell ( $w, $h, substr ( $s, $j, $i ), $b, 2, $align, $fill, $link );
		$this->x = $this->lMargin;
	}
}//Fin class
?>
