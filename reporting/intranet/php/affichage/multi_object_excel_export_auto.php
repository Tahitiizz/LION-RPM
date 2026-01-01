<?
set_time_limit(500);
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
*	Export des rapports sous forme de document excel
*
*
	- maj 18/06/2008, benoit : correction du bug 6928. Reprise des fonctions de recuperation des elements des graphes et de construction du      contenu du fichier Excel. On se base désormais, comme pour les exports Pdf et Word sur les tableaux de sessions '%_excel%' pour définir    le contenu des fichiers
*
*	14/12/2011 ACS BZ 25132 Correct "chmod +777" by "chmod 777" 
*
*/
?>
<?
session_start();

// 20/05/2010 NSE : relocalisation du module excel dans le CB
include_once(REP_PHYSIQUE_NIVEAU_0."modules/excel/class.writeexcel_workbook.inc.php");
include_once(REP_PHYSIQUE_NIVEAU_0."modules/excel/class.writeexcel_worksheet.inc.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/edw_function.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/edw_function_family.php");

class Object_Excel {

	var $object_name;
	var $object_type;
	var $file_name;
	var $ta;
	var $ta_value;
	var $na;
	var $na_value;
	var $path_file;
	
	function __construct( $object_type, $object_name, $file_name, $id_report, $na, $ta, $na_value, $ta_value, $family ){
		
		// 18/06/2008 - Modif. benoit : correction du bug 6928. Ajout de l'identifiant de l'utilisateur en tant que variable globale afin de pouvoir l'utiliser ultérieurement pour récuperer les données des graphes stockées en base

		global $id_user;

		$this->object_name = $object_name;
		$this->object_type = $object_type;
		$this->object_ref = get_object_ref_from_family($family);
		$this->path_file = REP_PHYSIQUE_NIVEAU_0.get_sys_global_parameters("report_files_dir");
		$this->file_name = $this->path_file.$file_name;
		$this->na_value = $na_value;
		$this->na = $na;
		$this->family = $family;
		$this->ta_value= $ta_value;
		$this->ta = $ta;
		
		$this->debug = get_sys_debug("report_send_mail");
				
		
		if($object_type == 'page'){

			// 18/06/2008 - Modif. benoit : correction du bug 6928. On utilise à présent les méthodes 'getElements_rewrite()' et 'write_content_rewrite()' pour construire les fichiers Excel
		
			/*$this->getTimes($this->ta, $this->ta_value); // On créé l'axe des abscisses
			// On récupère les données et on créé l'axe des abscisses
			$this->getElements( $id_report, $this->ta, $this->ta_value, $this->na, $this->na_value); 
						
			// Génération du fichier xls
			// Insertion des données
			$this->write_content();*/

			$this->getElements_rewrite();
			$this->write_content_rewrite();		
		}
	}
	
	/**
	* Fonction qui retourne les dates affichés pour chaque graph du dashboard
	* @param integer ta : time aggregation
	* @param integer ta_value : time aggregation value
	*/
	function getTimes($ta, $ta_value){
	
		// En fonction de la ta on récupère les 30 dernières aggrégation temporelles
		$this->tableau_excel_export_abscisses = array();
		switch($ta){
			case ($ta == 'hour' || $ta == 'hour_bh'): 
				$date = mktime(substr($ta_value, 8, 2), 0, 0, substr($ta_value, 4, 2), substr($ta_value, 6, 2), substr($ta_value, 0, 4));
			   // $i = 1;
			   for($i = -1; $i < 29; $i ++){
					$day =  $date - $i * 3600; // TimeStamp
					$hour = date('YmdH', $unixdate - $i * 3600); // Heure que l'on récupère
					$tableau_excel_export_abscisses['label'][] = date('d-m-Y:H', $date - $i * 3600);
					$tableau_excel_export_abscisses['value'][] = date('YmdH', $date - $i * 3600);
				}
				
			break;
			
			case ($ta == 'day' || $ta == 'day_bh'): 
			// On récupère les dates 
				$date =  mktime(6, 0, 0, substr($ta_value, 4, 2), substr($ta_value, 6, 2), substr($ta_value, 0, 4));
				$date = $date -24 * 60 * 60;
				 // On recule d'un jour
				 for($i = -1; $i < 29; $i ++){
										
					$tableau_excel_export_abscisses['label'][] = date('d-m-Y', $date - $i * 24 * 3600);
					$tableau_excel_export_abscisses['value'][] = date('Ymd', $date - $i * 24 * 3600);
				}
			break;
			
			case ($ta == 'week' || $ta == 'week_bh'):
                                // 12/12/2012 BBX
                                // BZ 30489 : utilisation de la classe Date
				$last_day = Date::getLastDayFromWeek($ta_value);
				
				for($i = -1 ; $i < 29; $i ++){
					$date = mktime(6, 0, 0, substr($last_day, 4, 2), substr($last_day, 6, 2) - ($i * 7), substr($last_day, 0, 4));
					// 11/03/2013 GFS - BZ#31364 - [SUP][TA HPG][AVP 32586][Telus][Partionning]: All weekly history is delete on last week of the year compute
					$tableau_excel_export_abscisses['label'][] ="W".date('W-o', $date);
					$tableau_excel_export_abscisses['value'][] = date('oW', $date);
				}
			break;
			
			case ($ta == 'month' || $ta == 'month_bh'): 
				for ($i = -1; $i < 29; $i++) {
                        $date = mktime(6, 0, 0, substr($ta_value, 4, 2) - $i, 1, substr($ta_value, 0, 4));
                        $tableau_excel_export_abscisses['label'][] = date('m-Y', $date);
                        $tableau_excel_export_abscisses['value'][] = date('Ym', $date);
                }
			break;	
			
		}
		// On inverse les valeurs des ta
		$this->tableau_excel_export_abscisses['label'] = array_reverse($tableau_excel_export_abscisses['label']);
		$this->tableau_excel_export_abscisses['value'] = array_reverse($tableau_excel_export_abscisses['value']);

	}// End function getTimes()
	
	/**
	* Fonction qui complète le tableau de données inséré dans le fichier xls
	* On génère les cases manquantes (aucune données remontées)
	*/
	function init_data_array( $id_graph, $graph_element ){
		
		
		foreach($this->tableau_excel_export_abscisses['value'] as $id_date=>$date){
			foreach($this->tableau_excel_export_ordonnees[$id_graph] as $id_cpt=>$cpt){
				if($this->tableau_excel_export[$id_graph][$graph_element][$id_date][$id_cpt] == NULL)
					$this->tableau_excel_export[$id_graph][$graph_element][$id_date][$id_cpt] = "";
			}
		
		}
	
		
		
	}// End function complete_data_array()
	
	/**
	* Fonction qui retourne l'élément réseau dont le label est na_label
	* @ param string $na_label : label de l'élément réseau
	* @ return string
	*/
	function get_label_from_na_value($na,$na_label){
		global $database_connection;
		
		$query = "SELECT $na FROM ".$this->object_ref." WHERE ".$na."_label ilike '$na_label' limit 1";
		$res = pg_query($database_connection,$query);
		while( $row = pg_fetch_array($res) ){
			$na_value = $row[$na];
		}
		
		return $na_value;
	
	}
	/**
	* Fonction qui retourne les informations d'un graph
	* On construit le contenu du fichier à partir des éléments à récupéréer 
	* @param string ta : time aggregation
	* @param string ta_value : time aggregation value
	* @param string na : network aggregation
	* @param string na_value : network aggregation value
	* 
	*/
	function getElements($id_report,$ta, $ta_value, $na, $na_value){
	
		global $database_connection;

		// On récupère le produit (gsm / iu...) pour récupérer le nom de la table de données
		$product = get_sys_global_parameters('module');
		
                // 17/01/2011 BBX
                // Table sys_pdf_mgt obsolète
                // BZ 20200
                /*
		// On récupère pour chaque graph la liste de ses éléments
		$query = "
				SELECT object_titre, gt_categories, object_graph_number,graph_data_list FROM sys_pdf_mgt, graph_information WHERE object_graph_number IN (
					SELECT id_elem
					FROM sys_pauto_config 
					WHERE id_page IN (SELECT id_elem FROM sys_pauto_config WHERE id_page = $id_report AND class_object= 'page')
					      AND class_object = 'graph' ORDER BY id_elem
				)
				 AND id_graph = object_graph_number
				 AND gt_categories = '".$this->family."'
				";
		$res = pg_query($database_connection,$query);

		// Pour chaque élément on récupère les informations nécessaires pour récupérer les données
		while($row = pg_fetch_array($res)){

			$query = "SELECT distinct data_legend, data_value, data_type, gt_categories
						FROM graph_data gd, graph_information gi, sys_pdf_mgt s
						WHERE id_graph = ".$row['object_graph_number']." AND id_user='-1' AND s.object_type='graph_dashboard_solo'
							AND id_data IN (".$row["graph_data_list"].")
						order by data_legend";

			$res2 = pg_query($database_connection,$query);
			
			$n = 0;
			
			while( $row2 = pg_fetch_array($res2) ){
				$tab[] = $row2["object_titre"];
				// On récupère la na_value affiché pour chaque graph
				$cpt = strtolower( stristr($row2["object_titre"],"$na=") );
				$tmp = explode("=",$cpt);
				unset($cpt);
				if(!isset($this->onglet[$row['object_graph_number']]))
					$this->onglet[$row['object_graph_number']] = array();
				if(!isset($graph_infos[$row['object_graph_number']]['data_value'] ) )
					$graph_infos[$row['object_graph_number']]['data_value'] = array();
					
				if( !in_array( $row2['data_value'], $graph_infos[$row['object_graph_number']]['data_value'] )){
					$graph_infos[$row['object_graph_number']]['data_value'][] = $row2['data_value'];
					$graph_infos[$row['object_graph_number']]['data_type'][] = $row2['data_type'];
					$graph_infos[$row['object_graph_number']]['family'] = $row2['gt_categories'];		
					
					// Axe des ordonnées du fichier xls // On récupère les raw/kpi du graph
					$this->tableau_excel_export_ordonnees[$row['object_graph_number']][] = $row2['data_legend'];
				}
									
				$n++;				
			}
			//  Titre du tableau
			if( !in_array($row['object_titre'], $this->onglet[$row['object_graph_number']]) ){
				$this->onglet[$row['object_graph_number']][] = $row['object_titre'];
			}
	
		}*/
	
		// On récupère les données		
		$froms = array();
		$select = "";
		$where = "";
		$order = "";
		
		foreach($this->tableau_excel_export_ordonnees as $id_graph=>$infos){

			foreach($infos as $i=>$info){
				$table = "edw_".$product."_".$graph_infos[$id_graph]['family']."_axe1_".$graph_infos[$id_graph]['data_type'][$i]."_".$na."_".$ta;
				
				if(!in_array($table,$froms) ){
					$froms[] = $table;
					if( empty( $where ) ){
						$where = $table.".$ta <= $ta_value AND ".$table.".$ta >= ".$this->tableau_excel_export_abscisses['value'][0];
						
						// $where.= " AND ".$table.".".$condition_na_value;
						$select = $table.".".$ta.",";
						$order = "ORDER BY ".$table.".".$ta." DESC";
					}
				}
			}
			
			foreach($this->onglet[$id_graph] as $key=>$val){

				// On ajoute la condition sur le niveau d'aggrégation réseau
				if( strtolower( stristr($val,"$na=") ) != null or strtolower( stristr($val,"$na=") ) != ""){
					$condition = strtolower( stristr($val,"$na=") );
					
					$tmp = explode("=",$condition);
					unset($condition);
					$condition[] = $tmp[0];
					
					$na_value = $this->get_label_from_na_value($na,$tmp[1]);

					$condition[] = "'".$na_value."'";
					
					if($this->debug)
						echo "<br/>condition  r3 : ".$condition_na_value[$key]." <br/>";
					$condition_na_value[$key] = " AND ".$table.".".implode(" = ",$condition);
				}
				
			}

							
			foreach($this->onglet[$id_graph] as $key_graph=>$val){
				unset($where2);
				unset($select2);
				// Jointure entre les deux tables de données
				$where2 = $where.$condition_na_value[$key_graph];
				if( count($froms) > 1 ){
					$where2.= " AND ".implode(".$ta = ",$froms);
					$where2.= ".$ta";
					$where2.= " AND ".implode(".$na = ",$froms);
					$where2.= ".$na";
				}
				
				$from = implode(",",$froms);
		
				// On récupère tous les raw/kpi
				$select2 = $select.implode(",",$graph_infos[$id_graph]['data_value']);					
				
				// Construction de la requête
				$query = "SELECT $select2 FROM $from WHERE $where2";	
				if($this->debug)
					echo "<h4>$query</h4>";
			
				$result = pg_query($database_connection,$query);
				
				// On récupère les champs du tableau
				$keys = $graph_infos[$id_graph]['data_value'];
				// $keys[] = $ta;
				// On intègre les données
				while( $row_result = pg_fetch_array($result) ){

					$row_index = array_keys( $this->tableau_excel_export_abscisses['value'], $row_result[$ta]);

					foreach($keys as $k=>$key){
						$id = strtolower($key);
						if( $id != $ta ){
						
							$col_index = $k;
							$this->tableau_excel_export[$id_graph][ $key_graph ][ $row_index[0] ][ $col_index ] = ($row_result[$id] !== NULL) ? $row_result[$id] : "";
							
						}

					}
				}
				// On initialise le tableau de données
				$this->init_data_array( $id_graph, $key_graph);
			}
		}
	} // End function getElements()

	// 18/06/2008 - Modif. benoit : correction du bug 6928. Définition d'une nouvelle méthode de récuperation des infos et des données des graphes des rapports

	function getElements_rewrite()
	{
		global $database_connection, $id_user;

                // 17/01/2011 BBX
                // Table sys_pdf_mgt obsolète
                // BZ 20200
                /*
		$sql = "SELECT * FROM sys_pdf_mgt WHERE id_user='$id_user' AND object_type='graph_dashboard_solo' ORDER BY oid ASC";
		$req = pg_query($database_connection, $sql);
			
		while ($row = pg_fetch_array($req)) {

			$id_graph = basename($row['object_content'], ".png");
			
			$this->title[$id_graph]								= $row['object_titre'];
			$this->onglet[$id_graph]							= $_SESSION['onglet_excel'][$id_graph];
			$this->tableau_excel_export_abscisses[$id_graph]	= $_SESSION['tableau_data_excel_ordonnee'][$id_graph];
			$this->tableau_excel_export_ordonnees[$id_graph]	= $_SESSION['tableau_data_excel_abscisse'][$id_graph];
			$this->tableau_excel_export[$id_graph]				= $_SESSION['tableau_data_excel'][$id_graph];
		}*/
	}
	
	/**
	* Fonction qui retourne le chemin complet du fichier généré
	*@return string 
	*/
	function parametre_sortie()
	{
		return $this->file_name;
	} // End function getFileName
	
	/**
	* Fonction qui génère le fichier xls
	* @param string dashname : nom du dashboard
	*/
	function write_content(){
		// 14/12/2011 ACS BZ 25132 Correct "chmod +777" by "chmod 777"
		$cmd = "touch ".$this->path_file.$this->file_name.";chmod 777 ".$this->path_file.$this->file_name;

		exec( $cmd );
		$workbook = &new writeexcel_workbook($this->file_name);
		$worksheet = &$workbook->addworksheet("Dashboard Export");
		
		$row_index = 0;
		
		// On construit le contenu du fichier Excel à partir des données du graphe	
		
		foreach($this->onglet as $id_graph=>$graph){
		
			foreach($graph as $id=>$title){

				// Titre du graphe

				$title_style =& $workbook->addformat(array(bold => 1));		
				$titre = explode(":",$title);
				
				$worksheet->write($row_index, 0, $title, $title_style);
				$row_index += 2;

				// Ecriture de la première cellule du tableau
				$worksheet->write($row_index, 0, $titre[1]);
				
				// Ecriture de l'abcisse
				
				$row_index_tmp = $row_index + 1;
				$nb_absc = count($this->tableau_excel_export_abscisses['label']);	
				
				for ( $i = 0; $i < $nb_absc; $i++ ){		

					$worksheet->write($row_index_tmp, 0, $this->tableau_excel_export_abscisses['label'][$i] );
					$row_index_tmp += 1;
				}
				
				// Ecriture de l'ordonnée
				
				$col_index = 1;
				$nb_ord = count($this->tableau_excel_export_ordonnees[$id_graph]);
	
				for ( $i = 0; $i < $nb_ord; $i++ ){

					$worksheet->write($row_index, $col_index, $this->tableau_excel_export_ordonnees[$id_graph][$i] );
					$col_index += 1;
				}
				
				// Ecriture des données
				$col_index = 0;			
				
				for ( $i=0; $i< $nb_ord; $i++ ){
					
					$row_index_tmp = $row_index + 1;
					$col_index += 1;
					
					for ( $j = 0; $j < $nb_absc; $j++ ){					
						
						$worksheet->write($row_index_tmp, $col_index, $this->tableau_excel_export[$id_graph][$id][$j][$i]);
						$row_index_tmp += 1;
					}
				}
								
				$row_index = $row_index_tmp + 2;
			}
		}
		$workbook->close();		
	}

	// 18/06/2008 - Modif. benoit : correction du bug 6928. Définition d'une nouvelle méthode d'écriture des fichiers Excel correspondants aux graphes.

	function write_content_rewrite()
	{
		$cmd = "touch ".$this->path_file.$this->file_name.";chmod +777 ".$this->path_file.$this->file_name;
		exec( $cmd );

		$workbook	= &new writeexcel_workbook($this->file_name);
		$worksheet	= &$workbook->addworksheet("Dashboard Export");

		$row_index = 0;
		
		foreach ($this->onglet as $id_graph=>$onglet_content)
		{
			// On construit le contenu du fichier Excel à partir des données du graphe
				
			// Titre du graphe
			
			$title_style =& $workbook->addformat(array(bold => 1));		
			
			$worksheet->write($row_index, 0, $this->title[$id_graph], $title_style);
			$row_index += 2;
			
			// Ecriture de la première cellule du tableau
			
			$worksheet->write($row_index, 0, $onglet_content);
			
			// Ecriture de l'abscisse

			$col_index = 1;

			for ($i=0;$i<count($this->tableau_excel_export_abscisses[$id_graph]);$i++){		
				$worksheet->write($row_index, $col_index, $this->tableau_excel_export_abscisses[$id_graph][$i]);
				$col_index += 1;
			}			

			// Ecriture de l'ordonnee

			$row_index_tmp = $row_index + 1;
			
			for ($i=0;$i<count($this->tableau_excel_export_ordonnees[$id_graph]);$i++){		
				$worksheet->write($row_index_tmp, 0, $this->tableau_excel_export_ordonnees[$id_graph][$i]);
				$row_index_tmp += 1;
			}

			// Ecriture des données

			$col_index = 0;
			
			for ($i=0;$i<count($this->tableau_excel_export[$id_graph]);$i++){
				
				$row_index_tmp = $row_index + 1;
				$col_index += 1;
				
				for ($j=0;$j<count($this->tableau_excel_export[$id_graph][$i]);$j++){					
					$worksheet->write($row_index_tmp, $col_index, $this->tableau_excel_export[$id_graph][$i][$j]);
					$row_index_tmp += 1;
				}
			}			
			$row_index = $row_index_tmp + 2;
		}		
		$workbook->close();
	}
}
?>
