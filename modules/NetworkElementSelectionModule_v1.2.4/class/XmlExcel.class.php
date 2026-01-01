<?php
/*
* Creation d'un fichier Excel a partir d'un ou plusieurs XML
*
* 30/04/2009 - SPS : ajout de la ligne horizontale dans l'export
* 11/05/2009 - SPS : 
* 	- on protege les quotes presents dans les labels
* 	- on ajoute une ligne vide dans le tableau de donnees pour eviter que des donnees soient tronquees
* 21/09/2011 MMT bz 19740 - xls export non généré si graph vide
* 
* @author SPS
* @date 17/03/2009
* @version CB 4.1.0.0
* @since CB 4.1.0.0
*/

//librairies necessaires a la creation du fichier excel
// 20/05/2010 NSE : relocalisation du module excel dans le CB
require_once( dirname(__FILE__)."/../../php/environnement_liens.php" );
require_once(REP_PHYSIQUE_NIVEAU_0."modules/excel/class.writeexcel_workbook.inc.php");
require_once(REP_PHYSIQUE_NIVEAU_0."modules/excel/class.writeexcel_worksheet.inc.php");
/*
* classe qui lit le fichier xml contenant les donnees du graphe et cree un fichier Excel
*
* @author SPS
* @see exportExcel
* @version CB 4.1.0.0
* @since CB 4.1.0.0
*/
class XmlExcel {

	/**
	* message affiche par l'exception declenchee s'il n'y a pas de valeurs dans le xml
	*/
	const EXCEPTION_PAS_DE_DONNEES = "Impossible de g&eacute;n&eacute;rer le fichier demand&eacute; : aucune donn&eacute;e.";
		
	/*
	* nom du fichier xml
	* @var string
	*/
	private $xmlFile;
	
	/*
	* donnees
	* @var array
	*/
	private $data;

	/*
	* constructeur
	*/
	public function __construct() {
	}
	
	/**
	* setter du nom de fichier xml
	* @param string $xmlFile nom du xml
	*/
	public function setXmlFile($xmlFile) {
		$this->xmlFile = $xmlFile;
	}
	
	
	/**
	* extraction des donnees pour un fichier unique
	* @return array $data tableau de donnees
	*/
	public function getSingleData() {
		
		//le nom des colonnes correspond a la ligne 0
		$index_colonne = 0;
		
		$this->extractData($this->xmlFile,$index_colonne);
		
		//on retourne le tableau de donnees
		return $this->data;
	}
	
	/**
	* extraction des donnees pour une liste de fichiers
	* @param array $liste_xml liste de fichiers xml a parcourir
	* @return array $data tableau de donnees
	*/
	public function getMultipleData($liste_xml) {
		
		//le nom des colonnes correspond a la ligne 0
		$index_colonne = 0;
		
		//on parcourt la liste de fichiers xml
		foreach($liste_xml as $xml_file){
			// 21/09/2011 MMT bz 19740 - xls export non généré si graph vide
			if(!empty($xml_file)){
				$indice = $this->extractData($xml_file,$index_colonne);
				//le nouvel index des colonnes correspond au dernier indice d'ecriture des donnees + 1 ligne d'espacement
				$index_colonne = $indice + 2;
			}
			
		}		
		
		//on retourne le tableau de donnees
		return $this->data;
	}
	
	
	/**
	*	sauvegarde du fichier Excel
	*	@param string $filepath repertoire dans lequel on va sauvegarder le fichier XLS
	*	@return string chemin du fichier genere
	*/
	public function save($filepath = '') {
		//on genere le nom du fichier aleatoirement
		$filename = "export_excel_".rand().".xls";
		// nom et chemin du fichier excel.
		if (!$filepath)
			$filepath = REP_PHYSIQUE_NIVEAU_0.'png_file/';
		$filepath .= $filename;
		// ajout d'un  nouveau document excel.
		$workbook = &new writeexcel_workbook($filepath);				
		// ajout d'une nouvelle feuille
		$worksheet = &$workbook->addworksheet("Data Export");			
		$row=0;
		$col=0;
		for($j=0;$j<count($this->data);$j++){
			$col=0;
			for( $k=0;$k < count($this->data[$j]) ;$k++){
				$worksheet->write($row, $col, $this->data[$j][$k]);
				$col++;
			}
			$row++;
		}
		$workbook->close();
		
		//retourne le chemin du fichier genere
		return $filepath;
	}
	
	/**
	* extraction des donnees pretes pour l'ecriture du fichier 
	* @param string $xml_file nom du fichier a analyse
	* @param int $index_colonne numero d'index de la colonne pour le fichier excel
	* @return int $indice indice de la derniere ligne remplie
	*/
	private function extractData($xml_file,$index_colonne) {
		//on commencera donc a ecrire les donnees a la ligne suivante
		$index = $index_colonne + 1;
		
		//on charge le fichier xml
		$xml = simplexml_load_file($xml_file);
		
		//on recupere les valeurs des balises tabtitle/text
		$tabtitle = $xml->properties->tabtitle->text;
		$title = "";
		foreach($tabtitle as $value) {
			$title .= $value;
		}
		$this->data[$index_colonne][] = $title;
		
		//on recupere les balises label
		$tlabel = $xml->xaxis_labels->label;
		
		//si on n'a pas de label, il n'y a pas de donnees
		if (count($tlabel) == 0) {
			// 21/09/2011 MMT bz 19740 - xls export non généré si graph vide
			// au lieu de faire une exception on affiche no data
			$this->data[$index_colonne][0] = "No data found";
			return $index;
		}		
		
		//on compte le nombre de balises label
		$nb_label = count($xml->xaxis_labels->label);
		
		//on enregistre tous les labels dans un tableau
		for($i=0;$i<$nb_label;$i++) {
			$tab_label[] = $tlabel[$i];
		}
		//on supprime les doublons du tableau de labels
		$tlabel = array_unique($tab_label);
		
		//on recupere les  balises data
		$tdata = $xml->datas->data;
		//on compte le nombre de balises data
		$nb_data = count($xml->datas->data);
		
		/* 30/04/2009 - SPS : on recupere la balise horizontal_line*/
		$line = $xml->properties->horizontal_line;
		
		//on parcourt les data
		for($j=0;$j<$nb_data;$j++) {
			
			/* 11/05/2009 - SPS : on protege les quotes presents dans les labels */
			//le label du data correspond au nom de la colonne
			$label = str_replace("'", "\'", $tdata[$j]['label']);
			$this->data[$index_colonne][] = $label;
			
			//on recupere la valeur de chaque balise value
			$tvalues = $tdata[$j]->value;
			//on compte le nombre de balises value
			$nb_valeurs = count($tdata[$j]->value);
			
			//on parcourt les balises value de chaque data
			for($k=0;$k<$nb_valeurs;$k++) {
				//on incremente le numero de colonne pour ne pas ecraser les labels
				$num_colonne = $j + 1;
				//l'indice correspond a la ligne ou commence l'ecriture des donnes + l'indice dans le tableau des valeurs
				$indice = $index + $k;
				//la 1ere colonne des donnees correspond au label
				$this->data[$indice][] = $tlabel[$k];
				//on enregistre la valeur de la balise
				$this->data[$indice][$num_colonne] = $tvalues[$k];
				
				/* 30/04/2009 - SPS : ajout de la valeur de la ligne horizontale dans l'export*/
				if (count($line) > 0 ) $this->data[$indice][$num_colonne+1] = $line[0];
			}
			
			/* 11/05/2009 - SPS : on ajoute une ligne vide dans le tableau de donnees pour modifier la taille du tableau 
			 * car la ligne vide ajoutee plus haut (fonction getMultipleData) ne modifie pas la taille du tableau
			 * et on a des valeurs tronquees a la fin
			 * */
			$this->data[$indice+1][0] = '';
		}
		/* 30/04/2009 - SPS : ajout de la legende de la ligne horizontale dans l'export*/
		if (count($line) > 0 ) $this->data[$index_colonne][$nb_data+1] = $line[0]['legend'];
		
		//retourne l'indice de la derniere ligne remplie
		return $indice;
	}
}
?>
