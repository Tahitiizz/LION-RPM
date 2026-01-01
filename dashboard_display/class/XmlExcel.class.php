<?php
/*
* Creation d'un fichier Excel a partir d'un ou plusieurs XML
*
* 30/04/2009 - SPS : ajout de la ligne horizontale dans l'export
* 11/05/2009 - SPS : 
* 	- on protege les quotes presents dans les labels
* 	- on ajoute une ligne vide dans le tableau de donnees pour eviter que des donnees soient tronquees
*
*	22/07/2009 GHX
*		- Correction du BZ 10688 [REC][T&A CB 5.0][Export Excel]: Export Excel sans titre GTM
*	03/08/2009 GHX
*		- Décodage des éléments html des labels
*	20/08/2009 GHX 
*		- Correction du BZ 11166[REC][T&A Cb 5.0][TP#1][TS#TT1-CB50][TC#37233][Dashboard] : en BH, l'export Excel ne présente pas les mêmes informations pour les pie et les graph
*	21/08/2009 GHX
*		- Ajout des raw/kpi manquant pour les PIE
*	22/09/2009 GHX
*		- Correction du BZ 11272
*	18/01/2010 NSE bz 13789 : traduction du message de l'exception en anglais
*	11/06/2010 NSE merge Single KPI
*  21/09/2011 MMT bz 19740 - xls export non généré si graph vide
* 
* @author SPS
* @date 17/03/2009
* @version CB 4.1.0.0
* @since CB 4.1.0.0
*/

//librairies necessaires a la creation du fichier excel
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
	// 18/01/2010 NSE : passage du message en anglais
	const EXCEPTION_PAS_DE_DONNEES = "Unable to generate the document : no data found";
		
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
		$index = $index_colonne + 2;
		
                // 29/11/2011 BBX
                // BZ 24888 : pas de génération si le fichier n'existe pas (gtm sans données)
                if(!file_exists($xml_file)) return false;
                
		//on charge le fichier xml
		$xml = simplexml_load_file($xml_file);
	
		// 14:16 22/07/2009 GHX
		// Correction du BZ 10688
		// Ajout du titre du graphe dans le fichier XML
		$this->data[$index_colonne++][] = $xml->properties->graph_name;
		
		
		//on recupere les valeurs des balises tabtitle/text
		$tabtitle = $xml->properties->tabtitle->text;
		$title = "";
		// 16:55 22/09/2009 GHX
		// Correction du BZ 11272
		// Ajout de utf8_decode
		foreach($tabtitle as $value) {
			$title .= utf8_decode($value);
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
			// 14:19 03/08/2009 GHX
			// Décodage de élément html
			$tab_label[] = html_entity_decode($tlabel[$i]);
		}
		//on supprime les doublons du tableau de labels
		$tlabel = array_unique($tab_label);
		
		//on recupere les  balises data
		$tdata = $xml->datas->data;
		//on compte le nombre de balises data
		$nb_data = count($xml->datas->data);
		
		/* 30/04/2009 - SPS : on recupere la balise horizontal_line*/
		$line = $xml->properties->horizontal_line;
		
		//11/06/2010 FJT : bz 16014
		//comme on a ajouté singleKPI, il faut gérer autrement
		//on ne fait pas $type = $xml->properties->type car si null, il deviendra pie (on évite les effets de bord indésirable) 
		if ($xml->properties->type == 'graph'){
			$type='graph';
		}else if ($xml->properties->type == 'singleKPI'){
			$type='singleKPI';
		}else{
			$type='pie';
		}
		
		$busy_hour_column = array();
                // 16/09/2010 BBX BZ 17663
                $bhExists = false;

		//on parcourt les data
		for($j=0;$j<$nb_data;$j++) {
			
			/* 11/05/2009 - SPS : on protege les quotes presents dans les labels */
			//le label du data correspond au nom de la colonne
			$label = str_replace("'", "\'", $tdata[$j]['label']);
			
			$indexLabel = count($this->data[$index_colonne]);
			
			$this->data[$index_colonne][] = $label;
			
			//on recupere la valeur de chaque balise value
			$tvalues = $tdata[$j]->value;
			//on compte le nombre de balises value
			$nb_valeurs = count($tdata[$j]->value);
			
			if ( $type == 'graph' || $type == 'singleKPI' )
			{
				//on parcourt les balises value de chaque data
				for($k=0;$k<$nb_valeurs;$k++) {
					
					//on incremente le numero de colonne pour ne pas ecraser les labels
					$num_colonne = $j + 1;
					//l'indice correspond a la ligne ou commence l'ecriture des donnes + l'indice dans le tableau des valeurs
					$indice = $index + $k;
					//la 1ere colonne des donnees correspond au label
					$this->data[$indice][] = $tlabel[$k];
					//on enregistre la valeur de la balise
					// 17:52 20/08/2009 GHX
					// Correction du BZ 11166
					// Ajout de l'information de la BH
					// 2010/08/03 - MGD - BZ 15443 : Affichage de la BH sur une colonne séparée
					$this->data[$indice][$num_colonne] = str_replace('<br>', ' ', $tvalues[$k]);
					$busy_hour_column[$this->data[$indice][0]] = strip_tags($tvalues[$k]['bh_infos']);

                    // 16/09/2010 BBX
                    // On détermine l'existence de la BH pour les données courantes
                    // BZ 17663
                    if(strip_tags($tvalues[$k]['bh_infos']) != '')
                        $bhExists = true;
					
					/* 30/04/2009 - SPS : ajout de la valeur de la ligne horizontale dans l'export*/
					if (count($line) > 0 ) $this->data[$indice][$num_colonne+1] = $line[0];
				}
			}
			else
			{
				// 10:00 21/08/2009 GHX
				// Ajout des autres raw/kpi dans le fichier excel pour les PIE ainsi que le nom des raw/kpi
				//on parcourt les balises value de chaque data
				for($k=0;$k<$nb_valeurs;$k++) {
					
					//on incremente le numero de colonne pour ne pas ecraser les labels
					$num_colonne = $j + 1;
					//l'indice correspond a la ligne ou commence l'ecriture des donnes + l'indice dans le tableau des valeurs
					$indice = $index + $k;
					//la 1ere colonne des donnees correspond au label
					$this->data[$indice][] = $tlabel[$k];
					//on enregistre la valeur de la balise
					// 17:52 20/08/2009 GHX
					// Correction du BZ 11166
					// Ajout de l'information de la BH
					$this->data[$indice][$num_colonne] = str_replace('<br>', ' ', $tvalues[$k]);
					
					$this->data[$index_colonne][$indexLabel] = $tvalues[$k]['label'];
					$compteur = 0;
					while ( isset($tvalues[$k]['label_'.$compteur]) )
					{
						$this->data[$index_colonne][$indexLabel+$compteur+1] = str_replace('<br>', ' ', $tvalues[$k]['label_'.$compteur]);
						$this->data[$indice][$num_colonne+$compteur+1] = str_replace('<br>', ' ', $tvalues[$k]['value_'.$compteur]);
						$compteur++;
					}
				}
			}
			
			/* 11/05/2009 - SPS : on ajoute une ligne vide dans le tableau de donnees pour modifier la taille du tableau 
			 * car la ligne vide ajoutee plus haut (fonction getMultipleData) ne modifie pas la taille du tableau
			 * et on a des valeurs tronquees a la fin
			 * */
			$this->data[$indice+1][0] = '';
		}

		// 16/09/2010 BBX
        // On n'affiche la BH que si elle est disponible
        // BZ 17663
        if($bhExists)
        {
            // 2010/08/03 - MGD - BZ 15443 : Affichage de la BH sur une colonne séparée
            // 18/01/2012 BBX
            // BZ 18844 : pas de colonne BH pour les export SA
            if(basename($xml_file) != 'SA_data.xml')
            {
                $this->data[$index_colonne][] = "Busy Hour";
                $idx_bh = count($this->data[$index_colonne])-1;
                $te_count = count($tlabel); // nombre d'elements temporels
                for ($i=0; $i < $te_count; $i++)
                {
                    $this->data[$i+$index_colonne+1][$idx_bh] = $busy_hour_column[$this->data[$i+$index_colonne+1][0]];
                }
            }
        }

		/* 30/04/2009 - SPS : ajout de la legende de la ligne horizontale dans l'export*/
		if (count($line) > 0 ) $this->data[$index_colonne][$nb_data+1] = $line[0]['legend'];
		
		//retourne l'indice de la derniere ligne remplie
		return $indice;
	}
}
?>
