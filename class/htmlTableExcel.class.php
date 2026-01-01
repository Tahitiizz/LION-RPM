<?php
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
	- maj 11/01/2008, maxime : on sauvegarde le fichier pour l'export des rapports

	- maj 15/01/2008, benoit : reprise de la sauvegarde
	- maj 15/01/2008, benoit : definition d'une variable indiquant le chemin vers le fichier où stocker les données dans le cas de la sauvegarde   dans un fichier

	- maj 16/01/2008, benoit : suppression des retours chariots des données du tableau et cast de celles comprenant le signe "=" en String (les   chaines comprenant le signe "=" sont considérées par la classe Excel comme des formules)
 *
 * 01/03/2011 MMT bz 19628 utilisation de REP_PHYSIQUE_NIVEAU_0
*/
?>
<?php
/**
 * Classe de création d'un fichier Excel à partir d'un tableau. Pour l'instant, utilisée uniquement pour les alarmes
 * 
 * @author BAC
 * @copyright Astellia
 * @version 1.0
 *
 */

// 20/05/2010 NSE : relocalisation du module excel dans le CB
// 27/07/2010 BBX
// Correction du require_once sur environnement_liens.php
// BZ 16960
require_once( dirname(__FILE__)."/../php/environnement_liens.php" );
require_once(REP_PHYSIQUE_NIVEAU_0."modules/excel/class.writeexcel_workbook.inc.php");
require_once(REP_PHYSIQUE_NIVEAU_0."modules/excel/class.writeexcel_worksheet.inc.php");

//01/03/2011 MMT bz 19628 utilisation de REP_PHYSIQUE_NIVEAU_0
require_once(REP_PHYSIQUE_NIVEAU_0.'class/htmlparser.inc');

class Excel_HTML_Table {

	private $_typeTableau;
	private $_sous_mode;
	private $_alarm_result_limit_nb;
	private $_debug;
	
	private $_filename;
	private $_workbook = null;
	private $_worksheet;
	private $_row_index;
	private $_save_file;
	private $_export_path;

	public function __construct($excel_filepath, $excel_filename, $header_img, $header_title, $sous_mode, $save_file = false){
		
		$this->_sous_mode = $sous_mode;

		// 15/01/2008 - Modif. benoit : definition d'une variable indiquant le chemin vers le fichier où stocker les données dans le cas de la sauvegarde dans un fichier

		$this->_export_path = $excel_filepath.$excel_filename;
		
		// maj 11/01/2008 - maxime : On sauvegarde le fichier pour l'export des rapports
		
		$this->_save_file = $save_file;
		$this->_alarm_result_limit_nb = get_sys_global_parameters("alarm_result_limit");
		$this->_debug = get_sys_debug('alarm_export_pdf');   // Affichage du mode debug
		
		$this->_row_index = 0;
				
		// Debut de la generation du document Excel
		
		$this->_filename	= tempnam("", "export_excel_".md5(uniqid(rand(), true)).".xls");	
		$this->_workbook	= &new writeexcel_workbook($this->_filename);
		$this->_worksheet	= &$this->_workbook->addworksheet("Alarm Export");
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
	
	/**
	 * Renvoie un tableau de largeurs de colonnes
	 * 
	 * @return array $col_array tableau de largeurs de colonnes
	 */

	private function getColWidth()
	{
		$col_array = array();
		
		if ( $this->_typeTableau == 'iterative' ) {
			if($this->axe3){
				if ($this->_sous_mode == 'condense')
				{
					$col_array = array(5.6, 2.8, 3.2, 4.5, 6.6, 1.8, 1.5, 2.6, 3.5, 3.5);
				}
				else if ($this->_sous_mode == 'elem_reseau')
				{
					$col_array = array(3.2, 5.6, 2.8, 4.5, 6.6, 1.8, 1.5, 2.6, 3.5, 3.5);
				}
				else
				{
					$col_array = array(4.5, 2.8, 3.2, 4.5, 6.6, 1.8, 1.5, 2.6, 3.5, 3.5);
				}
			}
			else 
			{
				$col_array = array(1.8, 2, 6, 1.8, 2, 6, 2, 2, 4);
			}
		}
		elseif ( $this->_typeTableau == 'dynamic' ) {
                        // Mantis 4178 : split additional details into two columns : nouvelles dimensions de colonnes
                        $col_array = array(5.6, 2.8, 3.2, 5.6, 1.8, 1.5, 1.8, 1.8, 3.5);
		}
		elseif ( $this->_typeTableau == 'additionnalFieldIterative' ) {
			$col_array = array(1.8, 6, 2);
		}
		elseif ( $this->_typeTableau == 'additionnalField' ) {
			$col_array = array(6, 2);
		}
		elseif ( $this->_typeTableau == 'topworst' ) {
			$col_array = array(2, 6, 1.8, 2, 6, 2, 2);
		}
		else 
		{
			if($this->axe3){
				if ($this->_sous_mode == 'condense')
				{
					$col_array = array(5.6, 2.5, 3.2, 4.5, 6.6, 1.8, 1.5, 2.6, 3.5, 3.5);
				}
				else if ($this->_sous_mode == 'elem_reseau')
				{
					$col_array = array(3.2, 5.6, 2.8, 4.5, 6.6, 1.8, 1.5, 2.6, 3.5, 3.5);
				}
				else
				{
					$col_array = array(4.5, 2.8, 3.2, 4.5, 6.6, 1.8, 1.5, 2.6, 3.5, 3.5);
				}
			}
			else 
			{
				if ($this->_sous_mode == 'condense')
				{
					$col_array = array(5.6, 2.8, 3.2, 6.6, 1.8, 1.5, 2.6, 3.5, 3.5);
				}
				else if ($this->_sous_mode == 'elem_reseau')
				{
					$col_array = array(3.2, 5.6, 2.8, 6.6, 1.8, 1.5, 2.6, 3.5, 3.5);
				}
				else if ($this->_sous_mode == 'detail')
				{
					$col_array = array(4.5, 4.5, 2.5, 2.5, 4.5, 2.5, 2.5, 3.5, 3.5);
				}
				else
				{
					$col_array = array(4.5, 2.8, 3.2, 6.6, 1.8, 1.5, 2.6, 3.5, 3.5);
				}
			}
		}
		if($this->_sous_mode == "autre"){
			$col_array = array(3.5);
		}
		
		// Cas où il s'agit d'une alarme qui a trop de résultats. il n'y a alors que 2 colonnes affichées.

		if($this->display_error_style){
			$col_array = array(2, 10);
		}
		
		return $col_array;
	}

	/**
	 * Nettoyage d'une chaine de ces caracteres html
	 *
	 * @param string $html
	 * @return string $html chaine purgée
	 */

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

	function parseTable($Table){
		$_var='';
		$htmlText = $Table;
		$parser = new HtmlParser ($htmlText);
		while ($parser->parse()) {
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
		}
		$elems = split(':-!',str_replace('/','',str_replace('::','',str_replace('!-:','',$_var)))); //opening row
		foreach($elems as $key=>$value)
		{
			if(trim($value)!='')
			{
				$elems2 = split('#,#',$value);
				array_pop($elems2);
				$data[] = $elems2;
			}
		}
		return $data;
	}

	function writeContent($tab_html){
		global $repertoire_physique_niveau0;

		$title_tmp = "";

		for ($num_page=0; $num_page<count($tab_html); $num_page++) {
			
			// Titre de l'alarme
			
			$title = $tab_html[$num_page][0];
			// Si le titre ne change pas entre 2 tableaux de resultats, on met le titre à vide pour rester sur la même page dans le excel (nouveau titre => nouvelle page)
			($title == $title_tmp) ? $title = "" : $title_tmp = $title;

			// Création des tableaux de résultats
			
			$html = "<br>".$tab_html[$num_page][1]."<br>";
			$this->display_error_style = false;
			$html = $this->ReplaceHTML($html);
			$this->axe3 = $flag_axe3;

			//Search for a table
			$start = strpos(strtolower($html),'<table');
			$end = strpos(strtolower($html),'</table');
			
			if($start!==false && $end!==false) {

				$tableVar = substr($html,$start,$end-$start);
				$tableData = $this->parseTable($tableVar);

				for($i=1;$i<=count($tableData[0]);$i++) {
					if($this->CurOrientation=='L')
						$w[] = abs(120/(count($tableData[0])-1))+24;
					else
						$w[] = abs(120/(count($tableData[0])-1))+5;
				}

				// En fonction des deux premiers noms des colonnes on détermine le type de tableau qui est affiché
				// ce qui permet de savoir la largeur de chaque colonne du tableau

				if ( trim($tableData[0][0]) == 'Date' && trim($tableData[0][1]) == 'Critical level' )
					$this->_typeTableau = 'iterative';
				elseif ( trim($tableData[0][0]) == 'Date' && trim($tableData[0][1]) == 'Additional field' )
					$this->_typeTableau = 'additionnalFieldIterative';
				elseif ( trim($tableData[0][0]) == 'Additional field' )
					$this->_typeTableau = 'additionnalField';
				elseif ( trim($tableData[0][0]) == 'Critical level' && trim($tableData[0][1]) == 'Threshold rawkpi' )
					$this->_typeTableau = 'dynamic';
				elseif ( trim($tableData[0][0]) == 'Critical level' && trim($tableData[0][1]) == 'Sort field' )
					$this->_typeTableau = 'topworst';
                                // Mantis 4178 : split additional details into two columns : détermination du type de tableau dynamic grâce aux nouvelles colonnes
                                elseif ( trim($tableData[0][6]) == 'Average' && trim($tableData[0][7]) == 'Overrun (%)' )
					$this->_typeTableau = 'dynamic';
				else
					$this->_typeTableau = 'default';
					
				// Création du header du tableau de résultats

				$header	= $tableData[0];
				array_walk(&$header, array($this, 'trimData'));	// On nettoie l'ensemble des valeurs du tableau '$header' des espaces en trop
				
				// Création du contenu du tableau de résultats
				
				$data = array();

				for ($j=1; $j < count($tableData); $j++) {
					$data_elt = $tableData[$j];
					array_walk(&$data_elt, array($this, 'trimData'));	// On nettoie l'ensemble des valeurs du tableau '$data' des espaces en trop
					$data[] = $data_elt;
				}
				
				// Définition du tableau contenant les largeurs des colonnes du tableau de résultats

				$col_size = $this->getColWidth();

				if (count($header) > count($col_size))		// nbre de colonnes d'entetes > nbre de largeurs : taille par défaut des colonnes
				{
					$col_size = array();
				}
				else if (count($header) < count($col_size))	// nbre de colonnes d'entetes < nbre de largeurs : suppression des valeurs en trop du tableau de taille
				{
					$col_size = array_slice($col_size, 0 , count($header));
				}
				
				// Ajout du tableau de résultats dans le fichier Excel
				
				// Titre de l'alarme (si celui-ci est non nul)
				
				if (trim($title) != "") {
					$title_style =& $this->_workbook->addformat(array(bold => 1));
					$this->_worksheet->write($this->_row_index, 0, $title, $title_style);
					$this->_row_index += 2;			
				}
				
				// Ecriture de l'entete du tableau
				
				for ($i=0;$i<count($header);$i++){
					$this->_worksheet->write($this->_row_index, $i, $header[$i]);				
				}
				$this->_row_index += 1;
				
				// Ecriture du contenu du tableau
				
				for ($i=0;$i<count($data);$i++){
					for ($j=0;$j<count($data[$i]);$j++){
						
						// 16/01/2008 - Modif. benoit : suppression des retours chariots des données du tableau et cast de celles comprenant le signe "=" en String (les chaines comprenant le signe "=" sont considérées par la classe Excel comme des formules)
						
						$data_to_write = str_replace('\n', " / ", $data[$i][$j]);
						if ((preg_match('/^=/', $data_to_write))) $data_to_write = " ".$data_to_write;

						$this->_worksheet->write($this->_row_index, $j, $data_to_write);	
					}
					$this->_row_index += 1;
				}
				$this->_row_index += 2;
			}
		}
		
		// Generation du fichier excel
				
		$this->_workbook->close();
		
		// maj 11/01/2008 - maxime : On sauvegarde le fichier pour l'export des rapports
		
		// 15/01/2008 - Modif. benoit : reprise du traitement de la sauvegarde
		
		if ($this->_save_file) {
			copy($this->_filename, $this->_export_path);
		}
		else
		{
			header("Content-Type: application/x-msexcel");
			$fh=fopen($this->_filename, "rb");
			fpassthru($fh);
		}
		if (is_file($this->_filename)) unlink($this->_filename);
	}
	
	/**
	 * Permet d'enlever les espaces en trop des elements d'un tableau
	 *
	 * @param string $elt element du tableau à nettoyer (passage par valeur)
	 */

	function trimData(&$elt)
	{
		$elt = trim($elt);
	}

}//Fin class
?>
