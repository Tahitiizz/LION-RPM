<?php
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
* 
* 	- 11/01/2008 Gwénaël : ajout des liens externes
*	- 23/05/2008 - maxime : On initialise l'object section
* 
*/
?>
<?
/**
 * Classe qui créer un document word à partir de la classe phprtf 
 *
 * @package export_word
 * @author MPR
 * @copyright Astellia
 * @version 1.0
 *
 */
// include_once ('../php/environnement_liens.php');
include_once(REP_PHYSIQUE_NIVEAU_0."word/rtf/Rtf.php");

class export_word{

	var $file_name; // Nom du fichier
	var $rtf; // Classe rtf
	var $format; // format de page
	var $nb_img = 1; // nombre d'images affichées
	var $nb_pages; // nombre de pages
	var $font_title; // style des titres
	var $font_footer; // style du pied de page
	var $font_global; // style de tous les textes
	var $font_link; // style des liens 
	var $font_error; // style des erreurs rencontrées
	var $date; // date du jour (ex: December 28 2007, 09:56 am)
	var $color_tab_header_text; // Couleur par défault du texte de l'entête d'un tableau
	var $color_tab_header_background; // Couleur du background par défault de l'entête d'un tableau  
	
	/**
	*  Constructeur de la classe
	* @access public
	* @param string $file_name nom du fichier généré
	*/
	function __construct($file_name){	
		$this->file_name = $file_name;
		$this->rtf = new Rtf();
		
		$this->nb_img = 0;
		$this->nb_tab = 0;
		$this->date = date('F d Y, g:i a');
		// Style global par défault
		$this->font_global = new Font(9, 'Verdana, Arial, sans-serif', '#fff');

		// Style de lien par défaut 
		$this->font_link = new Font(9, 'Verdana, Arial, sans-serif', '#0000ff');
		
		// Style des titres par défault
		$this->font_title = new Font(12, 'Verdana, Arial, sans-serif', '#fff');
		$this->font_title->setBold();
		
		// Style des tableaux par défault
		$this->color_tab_header_text = "#ffffff";
		$this->color_tab_header_background = "#5e5e5e";
		
		// Style des errors par défault
		$this->font_error = new Font(10,'Arial','#ff0000');
		$this->font_error->setBold();
		
		// Style du pied de page par défault
		$this->font_footer = new Font(10, 'Arial', '#000000','#eeeeee');
		$this->font_footer->setItalic();
		
		// Style de l'entête de page par défault
		$this->font_header = new Font(14, 'Arial', '#000000');
		// $this->font_header->setBold();
		
		// Style des commentaires par défault
		$this->font_comment = new Font(10, 'Arial', '#000000','#ffffff');
		$this->font_comment->setItalic();
		
		$this->border_comment = '#eeeeee';
		
	} // End function __construct
	
	/**
	* Fonction qui génère le fichier .doc
	*/
	function generate_word_file(){
	
		$this->rtf->sendRtf($this->file_name);
		
	} // End function generate_word_file
	
	/**
	* Fonction qui enregistre le fichier
	*@param string dir : chemin complet du fichier
	*/
	function save_file($dir){
		$path = $dir.'/'.$this->file_name.".doc";
		$this->rtf->save($path);
		
	} // End Fonction save_file
	
	/**
	* Fonction qui créé l'entête du fichier
	* @access public
	* @param string $img_ope path de l'image de l'opérateur 
	* @param string $img_clt path  de l'image du client 
	*/
	public function create_header($img_ope,$img_clt,$title){
		$this->title_page = $title;
		$null = null;
		$par_title = new ParFormat('center');
		$font = $this->get_font_header();
		
		$header = &$this->rtf->addHeader('all');
		$header->writeText("",$font,$null);
		
		$width_default = getimagesize($img_ope);
		
		

		$width_ope = $width_default[0] / 2;
		// echo "tot:".$this->width;
		// echo "ope:".$width;
		
		$header->addImage($img_ope, $null,$null);

		
		$aff_title = "<tab>$title<tab>";
		if($this->format=='landscape'){
			$aff_title = "$title";
		}
		$aff_title = $title;
		$nb_char = strlen($title);
		
		if($nb_char > 50)
			$aff_title = substr($title,0,57).'...';
		
		$nb_char = strlen($title);
	
		$nb_spaces = $this->get_nb_spaces_in_header($aff_title,$nb_char);
		// echo "/$nb_spaces\\";
		// $nb = $nb_spaces - 10;
		
		$tab = '<tab><tab><tab><tab>';
		if( $this->format == 'landscape' ){
			$tab.= $tab; 
			for($i = 0; $i <= 12 ; $i ++){
				$tab.= ' ';
			}
		}
		$header->writeText($tab, $null, $null);
		unset($tab);
		
		$width_default = getimagesize($img_clt);

		$width_clt = $width_default[0] / 4;
	
		// $nb = $nb_spaces - 10;
		$header->addImage($img_clt, $null ,$null );
		
		$header->writeText($aff_title, $font, $par_title);
		
		// $header->writeText('<br>', $font, new ParFormat());

	} // End function create_header
	
	private function maj_footer($text,$font){
		$null = null;
		$this->footer->writeText($text, $font, $null);
	}
	/**
	* Fonction qui affiche le pied de page
	*/
	public function create_footer(){
		$null = null;
		$font = $this->get_font_footer();
		
		$this->footer = &$this->rtf->addFooter('all');
		$this->footer->writeText('Powered by Trending & Aggregation', $font, $null);
		$tabs = '<tab><tab><tab><tab>'.$this->date.'<tab><tab><tab><tab>'; 
		if($this->format == 'portrait')
			$tabs = '<tab><tab>'.$this->date.'<tab><tab>            '; // Espaces mis en dur ( une tab est trop grande)
		$this->footer->writeText($tabs.'Page '.$this->get_num_page_encours().' / '.$this->get_nb_pages(), $font, $null);
		
	} // End function create_footer
	
	/**
	*Fonction qui retourne le n° de la page en cours
	* @access private
	* @return string num_page n° de la page en cours 
	*/
	private function get_num_page_encours(){
		return '{\field{\*\fldinst Page}{\fldrslt 1}}';
	}
	
	/**
	*Fonction qui retourne le nombre de pages
	* @access private
	* @return string nb_pages nombre de pages 
	*/
	private function get_nb_pages(){
		return '{\field{\*\fldinst NUMPAGES}{\fldrslt 1}}';
	}
	/**
	* Fonction qui détermine le format de la page ( portrait/paysage)
	* @param string $format format de page
	*/
	public function set_format_page($format){
		$verif = array('portrait','landscape');
		if(!in_array($format,$verif)){
			echo "Le format de page $format n'existe pas";
			exit;
		}
		$this->height = 29.7;
		$this->width = 21;
		if($format == 'landscape'){
			// on fixe la taille de la page en paysage
			$this->height = 21;
			$this->width = 29.7;
			$this->rtf->setLandscape(true);
		}
		$this->format = $format;
		$this->rtf->setPaperHeight($this->height);
		$this->rtf->setPaperWidth($this->width);
		
	} // End function set_format_page
	
	/**
	* Fonction qui récupère le style des textes
	*  @return string 
	*/
	private function get_font_global(){
		return $this->font_global;
	}
	
	/**
	* Fonction qui récupère le style des liens
	*  @return string 
	*/
	private function get_font_link(){
		return $this->font_link;
	}
	
	/**
	* Fonction qui récupère le style des titres
	*  @return string 
	*/
	private function get_font_title(){
		return $this->font_title;
	}
	
	/**
	* Fonction qui récupère le style des commentaire
	*  @return string 
	*/
	private function get_font_comment(){
		return $this->font_comment;
	}
	
	/**
	* Fonction qui récupère le style de l'entête
	*  @return string 
	*/
	private function get_font_header(){
		return $this->font_header;
	}
	
	/**
	* Fonction qui récupère le style du pied de page
	*  @return string 
	*/
	private function get_font_footer(){
		return $this->font_footer;
	}
	
	/** 
	* Fonction qui détermine le style de chaque titre dans le fichier
	* @param string $police police du texte
	* @param int $size taille du texte
	* @param string $color couleur du texte
	*/
	public function set_font_title($police,$size,$color){
		$this->font_title = new Font($size, $police, $color); 
		$this->font_title->setBold();
	}
	
	/** 
	* Fonction qui détermine le style de chaque titre dans le fichier
	* @param string $police police du texte
	* @param int $size taille du texte
	* @param string $color couleur du texte
	*/
	public function set_font_header($police,$size,$color=""){
		if($color="")$color = NULL;
		$this->font_header = new Font($size, $police, $color); 
		$this->font_header->setBold();
	}
	
	/** 
	* Fonction qui détermine le style du pied de page
	* @param string $police police du texte
	* @param int $size taille du texte
	* @param string $color couleur du texte
	*/
	public function set_font_footer($police,$size,$color,$backcolor=""){
		if($backcolor="")
			$this->font_footer = new Font($size, $police, $color);
		else
			$this->font_footer = new Font($size, $police, $color,$backcolor);
		$this->font_footer->setItalic();
	}
	
	/** 
	* Fonction qui détermine le style de chaque texte dans le fichier
	* @param string $police police du texte
	* @param int $size taille du texte
	* @param string $color couleur du texte
	*/
	public function set_font_global($police,$size,$color){
		$this->font_global = new Font($size, $police, $color); 
	}
	
	/** 
	* Fonction qui détermine le style de chaque lien dans le fichier
	* @param string $police police du texte
	* @param int $size taille du texte
	* @param string $color couleur du texte
	*/
	public function set_font_link($police,$size,$color){
		$this->font_link = new Font($size, $police, $color); 
	}
	
	/** 
	* Fonction qui détermine le style des commentaire
	* @param string $police police du texte
	* @param int $size taille du texte
	* @param string $color couleur du texte
	*/
	public function set_font_comment($police,$size,$color="",$border="",$style=""){
		$null = null;
		if($color=='')$color = NULL;
		if($this->border_comment=="")$this->border_comment = NULL;
		
		$this->font_comment = new Font($size, $police, $color, $null);
		
		if($style=='bold')
			$this->font_comment->setBold();
		if($style=='italic')
			$this->font_comment->setItalic();
	}
	
	/** 
	* Fonction qui détermine le nombre d'espace à ajouter dans le titre du header
	*	@param string title titre de l'entête
	*	@return integer nb_spaces
	*/
	private function get_nb_spaces_in_header($title,$nb_char){
		$size_ope = 3.5;
		$size_clt = 6;
		// echo $this->width;
		$layout_title = $this->width - ($size_ope + $size_clt);
		// echo "-$layout_title-";
		$titre = strlen($title) * 0.2;
		// echo "*$titre*";
		
		$nb_spaces = floor( ( $layout_title - $titre ) )*4;
		if( $nb_char > 40 ){
			$nb_spaces = $nb_spaces-10;
		}
		else if( $nb_char > 30 ){
			$nb_spaces = $nb_spaces;
		}else if ($nb_char > 20)
			$nb_spaces = $nb_spaces;
		else
			$nb_spaces = $nb_spaces + 10;
		// echo $nb_spaces;
		
		return $nb_spaces;
	}
	
	/**
	* Fonction qui permet d'ajouter une image dans le corps du texte
	* @param string $path path de l'image
	* @param string $title titre de l'image
	*/
	public function add_img($path,$title="",$comment=""){
		$this->nb_img++;
		$par_img = new ParFormat("center");
		$par_title = new ParFormat("left");
		$null = null;
		$arial14 = $this->get_font_title();
		
		
		// On fixe la taille de l'image en fonction du format de page
		$width = 14.8;
		if($this->format == 'landscape')
			$width = 23.5;
		
		// On affiche soit 2 images soit 1 seule par page en fonction du format de page
		if(!isset($this->sect)){
			$this->sect = $this->rtf->addSection();
			$this->sect->setNoBreak();
		}
		elseif($this->format=='landscape' or $this->format=='portrait' and !is_int($this->nb_img / 2) ){
			$this->sect->writeText('\\page', new Font(), $par_title);
			$this->nb_pages++;
		}
			
		// $this->sect->writeText('', new Font(), $null);
		
		// insertion du titre s'il existe
		if(!empty($title)){
			$this->sect->writeText("<br/><tab>", $arial14, $par_title);
			$this->sect->addImage(REP_PHYSIQUE_NIVEAU_0.'images/icones/pdf_alarm_titre_arrow.png', $null);
			$this->sect->writeText(" $title", $arial14, $null);
			$this->sect->writeText('<br/>', new Font(), $par_img);
		}
		
		if(!empty($comment)){
			$this->add_comment($comment);
			$this->sect->writeText('', new Font(),$par_img);
		}
		
		// insertion de l'image
		$this->sect->addImage($path, $par_img,$width);
		if($this->format == 'portrait')
			$this->sect->writeText('<br/><br/>', new Font(), $par_title);
	} // End function add_img
	
	/**
	* Fonction qui construit un tableau
	*@param array $header :  Entête du tableau
	*@param array(array) $data : Données du tableau
	*@param string $title : titre du tableau
	*@param array(float) $sizeCol Tableau contenant la taille de chaque colonne
	*@param string $color_header Couleur de l'entête du tableau
	*@param array(int,string,string) array($size,$police,$color) Style de l'entête du tableau
	*/

	public function add_tab($header,$data,$title="",$sizeCol=array(),$color_header = "", $size = 9, $police = 'Arial, sans-serif', $color = ""){
		
		$null = null;
		$font_title = $this->get_font_title();
		$par_title = new ParFormat('left');
		$font_global = $this->get_font_global();
		$font_link = $this->get_font_link();
		if(empty ($color) )
			$color = $this->color_tab_header_text;
			
		$font_header = new Font($size,$police,$color);
		$font_header->setBold();
		// On inègre le tableau dans une section
		if(!isset($this->sect))
			$this->sect = &$this->rtf->addSection();
		$this->sect->setMargins(1,3,0.5,1);
		$this->sect->setNoBreak();
		// insertion du titre
		$this->sect->writeText('<br/>',$null,$null);
		$num_page = $this->get_num_page_encours();
		if(!empty($title)){
			$this->nb_tab++;
			// $this->sect->writeText($num_page,$null,$null);
			if($this->nb_tab > 1)
				$this->sect->writeText('\\page <br/>',$null,$null);
			$this->sect->addImage(REP_PHYSIQUE_NIVEAU_0.'images/icones/pdf_alarm_titre_arrow.png', $null);
			$this->sect->writeText(' '.$title.'<br/>',$font_title,$null);
			$this->sect->writeText('<br/>',$font_title,$par_title);
		}else
			$this->sect->writeText('',$font_title,$par_title);
		
		
		if(count($data)>0 or $this->comment){
			// On définit le nombre et la taille des lignes et des colonnes
			$count= count($header);
			$countCols = $count;
			$countRows = count($data)+1;
		
			$table = &$this->sect->addTable('left'); // Création de l'object table
			$table->addRows(count($data)+1, 0.6); // On insère le nombre de lignes
			
			// On définit la taille des colonnes en fonction du paramétrage
			if( count($sizeCol)>0 ){
				foreach($sizeCol as $width){
					$table->addColumn($width);
				}
			}
			else{
				$colWidth = ($this->width - 2) / $count ;
				
				for ($i = 0; $i < count($header); $i ++) {	
					$table->addColumn($colWidth);
				}
			}

			// On fixe l'entête sur chaque page
			$table->setFirstRowAsHeader();
			
			// Construction du tableau
			$table->setDefaultAlignmentOfCells('center',1,1,$table->getRowsCount(),$table->getColumnsCount());
			$table->setVerticalAlignmentOfCells('center',1,1,$table->getRowsCount(),$table->getColumnsCount());
			if( empty($color_header) ){
				$color_header = $this->color_tab_header_background;
			}
			
			// On définit les bordures du tableau
			$table->setBordersOfCells(new BorderFormat(1, '#000000'), 1, 1, $table->getRowsCount(), $table->getColumnsCount());
			
			// On insère le header
			foreach($header as $k=>$val){
				$table->writeToCell(1, $k+1, $val, $font_header, $null);  	
				$table->setBackgroundOfCells($color_header, $num+1, $k+1);
			}
			
			// On insère les données
			// Old et new value vont permettre d'alterner de couleur entre les différents éléments ( ex : alarm management -> On change de couleur uniquement lorsque l'on affiche une nouvelle alarme) 
			$old_value = "";
			$old_color = "#dddddd";
			$k = 0;
			foreach ($data as $num_row=>$row) {
				$new_value = $row[1];
				
				if($this->title_page == "Alarm Management"){
					if($old_value != $new_value and !empty($new_value)){ // Si on change d'élément "identifiant", on change de couleur
						
						if($old_color == "#dddddd"){
							$new_color = "#fefefe";
							$old_color = "#fefefe";
						}else{
							$new_color = "#dddddd";
							$old_color = "#dddddd";
						}
						$old_value = $row[1];
					}
				}else
					$new_color = ( is_int($num_row/2) ) ? "#fefefe" : "#dddddd";
				foreach($header as $k=>$val){
					$num = $num_row + 1; 
					
					 // - modif 11/01/2008 Gwénaël
					 // 	Ajout des liens externes
					$split = preg_split("/<a[ ]*href=['\"]([^>'\"]*)['\"][ ]*>(.*?)<\/a[ ]*>/Ui", $row[$k], -1, PREG_SPLIT_DELIM_CAPTURE);
					if ( count($split) == 4 )
						$table->writeToCellHyperLink( $num+1, $k+1, $split[1], $split[2], $font_link, $null );
					else
						$table->writeToCell( $num+1, $k+1, $row[$k], $font_global, $null );
					
					$table->setBackgroundOfCells($new_color, $num+1, $k+1); 				
				}

			}
			
			
		}else{ // Si le tableau est vide, on affiche un message d'erreur 
			$this->sect->writeText("<br/>>><tab><tab><tab>No Result",$this->font_error,$null);
		}
	} // End Function add_tab()
	
	// $header,$text,$title="",$sizeCol=array(),$color_header = "", $size = 9, $police = 'Arial, sans-serif', $color = ""
	public function add_comment($text){
		
		
		$null=NULL;
		$par_img = new ParFormat('center');
		// $this->sect->writeText('\\page', new Font(), $null);

		// $this->sect->writeText('\nobreak', new Font(),$null);
		$countCols = 1;
		$countRows = 1;
		
		
		// maj 23/05/2008 - maxime : On initialise l'object section
		if(!isset($this->sect))
			$this->sect = $this->rtf->addSection();
			

		$table = &$this->sect->addTable(); // Création de l'object table
		$table->addRows(1, 0.6); // On insère le nombre de lignes
		
		$colWidth = $this->width - 1;
		
		$table->addColumn($colWidth);
		
		// Construction du tableau
		$table->setDefaultAlignmentOfCells('left',1,1,$table->getRowsCount(),$table->getColumnsCount());
		$table->setVerticalAlignmentOfCells('center',1,1,$table->getRowsCount(),$table->getColumnsCount());
		
		// On définit les bordures du tableau
		$table->setBordersOfCells(new BorderFormat(1, $this->border_comment), 1, 1, $table->getRowsCount(), $table->getColumnsCount());
		
		// On insère le header
		$table->writeToCell(1, 1, " ".$text, $this->font_comment, $null);  	
		$table->writeToCell(1, 1, " ".$text, $this->font_comment, $null);  	

	
	}// End function add_comment
} // End Class export_word 

	// Function qui ajoute un commentaire dans le corps du texte


// Exemple d'utilisation de la classe
//-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
// $img_ope["path"] = "$repertoire_physique_niveau0/images/bandeau/logo_operateur.jpg";
// $img_clt["path"] = "$repertoire_physique_niveau0/images/client/logo_client_pdf.png";
// $word = new export_word('export_word');
// $word->set_format_page('paysage'); // Initialisation du nom du fichier créé
// $word->set_font_title('Arial',11,'#000066');
// $word->create_header($img_ope["path"],$img_clt["path"],"yataaaaaaayata"); // Création de l'entête
// $word->create_footer(); // On initialise le footer avant le corps de la page


//--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
// Tests export gtm
// for($i=0;$i<=10;$i++){
	// $word->add_img("$repertoire_physique_niveau0/png_file/gtmgraph_476fc273d31344.46541671.png",'GTM Call Drop %');
	// $word->add_img("$repertoire_physique_niveau0/png_file/gtmgraph_476fc27534d4a5.25792727.png",'GTM Call Setup %');
// }

//--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
// Test export alarmes 
// $title = 'Export des alarmes Trending & Aggregation to Ms Word';
// $header = array('Network aggregation','NA_value','Time aggregation','Ta_value','Alarm Name','Alarm Trigger','Threshold','Threshold value');
// $sizeCol = array(2,2.125,1,2,5,4,7,2.5);

// for($i = 0;$i<=10;$i++){
	// $data[] = array('Cell','cell1','day',20070602,'Alarme 1','KPI','Call Drop efficient %','123.5');
	// $data[] = array('','','week',200743,'Alarme 1','KPI','Call Drop efficient %','12213.5');
	// $data[] = array('','','month',200712,'Alarme 1','KPI','Call Drop efficient %','12543.5');
	// $data[] = array('Bsc','bsc2','day',20070603,'Alarme 2','RAW','Call Setup efficient %','1879.5');
	// $data[] = array('Cell','cell3','day',20070604,'Alarme 3','THRESHOLD','Calls number (%)','12.5');
// }
// $data2 = array();

// $word->add_tab($header,$data,$title,$sizeCol);
// $word->add_tab($header,$data2,$title,$sizeCol);
// $word->add_tab($header,$data,$title);

//--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
// $word->generate_word_file();

// génération du fichier .doc

?>