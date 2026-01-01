<?
/*
* Classe permettant d'extrapoler les données
*/
?>
<?
/*
*	-  maj 12:10 26/11/2009 MPR : Correction du bug 13052 - Suppression des index $id
*/
?>
<?
set_time_limit(3600);

class ExtrapolationData 
{
	// Attributs 
	private $nbTaExtrapolation;
	private $family;
	private $product;
	private $rawkpi = array();
	private $database_connection;
	private $na_level;
	private $data_extra = array();
	private $ta_level;
	private $debug = 0;
	public $activate;

	
	/**
	* Constructeur de la classe
	*/
	public function __construct()
	{
		$this->debug = get_sys_debug("extra_data");

	} // End Function __construct
		
	/**
	* Fonction qui définit le nombre de ta sur lequel nous devons extrapoler les données
	*/
	public function setParamsActivation()
	{
		$this->nbTaExtrapolation = get_sys_global_parameters("extrapolation_nb_ta","10",$this->product);
		$this->activate = get_sys_global_parameters("extrapolation_activate","0",$this->product);
	} // End Function setNbTA
	
	/**
	* Fonction qui définit le mode (Investigation Dashboard ou Dashboard OT)
	*/
	public function setMode($mode)
	{
		$this->mode = $mode;
	} // End Function setMode
	
	public function setFamilies($families)
	{
		$this->families = $families;
		$this->nb_families = count($families);
	} // End Function setFamily
	
	
	public function setGroupTable($group_table)
	{
		$this->group_table = $group_table;
		
	} // End Function setFamily
	
	/**
	* Connexion à la base de données
	* @param integer $product : produit concerné
	*/
	public function setDbConnection($product)
	{
		// On créé la connexion si celle-ci n'existe pas
                // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
		if( !isset( $this->database_connection[$product] ) )
			$this->database_connection[$product] = Database::getConnection ($product);
	
		return $this->database_connection[$product];
	} // End Function setConnection
	
	/**
	* Fonction qui initialise l'id du produit
	* @param array $datas : Données en entrée avant extrapolation 
	*/
	public function setProducts($products)
	{
		$this->products = $products;
		$this->nb_products = count($products);
	} // End Function setProduct
	
	/**
	* Fonction setDatas qui récupère les données extrapolees
	* @param array $datas : Données en entrée avant extrapolation 
	*/
	public function setDatas($datas)
	{
		$this->datas = $datas;
	} // End Function setDatas
		
	/** 
	* Fonction qui retourne si le mode debug est actif ou non
	* @return boolean $this->debug
	*/
	private function debug() {
		return $this->debug;
	} // End Function debug

	/**
	* Fonction qui permet d'afficher des messages dans l'IHM en fonction du mode debug "extra_data" (1 : actif / 0 : inactif)
	* @param string/array/integer $txt : message ou variable à afficher
	* @param string $title : titre du message (falcutatif)
	*/
	public function msg($txt,$title=""){
	
		if($this->debug){
			__debug($txt,utf8_encode($title),2);	
		}
	} // End Function msg
	
	
	public function completeDatas( ) {
	
		$this->setNbDatas($this->datas);

		$hole = array();
		$this->datas_extra = array();
		
		$this->msg($this->datas," BEFORE BLACK HOLE");
		__debug($this->group_table," GROUP TABLE");
		
		$i = 0;
		// Boucle sur tous les raw/kpi du dashboard
		foreach($this->rawkpi as $type=>$tab){
			__debug($tab['id_elem'],"TAB $type");
			// On boucle sur chacun des éléments
			foreach( $tab['id_elem'] as $id=>$val ){
				
				// Définition du produit concerné
				$product = $tab['id_product'][$id];
				__debug("$id - $product");
				
				// Définition du group table concerné
				$group_table = ( !isset( $this->group_table[ $product ][ $i ]) ) ? $this->group_table[ $product ][0] : $this->group_table[ $product ][ $i ];
		
				if(!isset($hole[$val]))
					$hole[$val] = array();
					
				// ETAPE 1 : IDENTIFICATION DES TROUS A COMBLER (on enregistre le nombre de TA concernées et la valeur qui sera dupliquée //
				// Boucle sur le nombre de données ( = nombre de TA)
				$hole[$val] = $this->identifyDataHoles($hole[$val], $type, $product, $val, $group_table );
				
				
				// ETAPE 2 :  DUPLICATION DES DONNEES  POUR LES TROUS IDENTIFIES				
				// Boucle sur tous les trous
				$this->duplicateData($hole[$val], $type, $product, $val);
				$i++;
			}
		}
		
		$this->msg($this->datas_extra,"DATA EXTRA");
		return $this->datas;	
	} // End function completeDatas()
	
	/**
	* Fonction qui extrapole les données dans Investigation Dashboard
	* @return array $datas : Tableau contenant les données réelles + données extrapolées ( $this->datas [ $na_value_label ] [ $rawkpi_label ] [ $ta_value ] = $val) 
	*/
	public function completeDatasInvestigation( ) {
	
		$nb_datas = $this->setNbDatas( $this->ta_values );
		$hole = array();
		$this->datas_extra = array();
		
		// On a un seul produit ainsi qu'une seule famille donc également un seul group_table
		$product = $this->products[0]; 
		$group_table = $this->group_table[$product][0];
		// Connexion à la base pour récupérer les id des raw/kpi
		$db = $this->setDbConnection($product);
		
		$this->msg($this->datas," BEFORE BLACK HOLE");
		
		// Le tableau de données est de la forme suivante $this->datas [ $na_value_label ] [ $rawkpi_label ] [ $ta_value ] = $val
		// On boucle sur tous les éléments réseau
		foreach($this->na_value as $na=>$na_value) {
		
			// Récupération du label de l'élément réseau (index du tableau de données)
			$na_value_label = $this->na_value_label[$na_value];
			
			// Boucle sur les compteurs
			$cpt=0;
			foreach( $this->rawkpi as $type=>$val ) {
				
				foreach($val as $v){
				
					// On récupère le label du raw/kpi (index du tableau de données)
					// maj 26/11/2009 MPR : Correction du bug 13078
					$raw_kpi_label = ($type == 'kpi' ) ? $this->kpiModel->getLabelFromId($v,$db) : $this->rawModel->getLabelFromId($v,$db);
										
					if( count($this->na_values_axe3) == 0 ) {
					
						if(!isset($hole[$na_value_label][$raw_kpi_label]))
							$hole[$na_value_label][$raw_kpi_label] = array();
											
						// ETAPE 1 : IDENTIFICATION DES TROUS A COMBLER (on enregistre le nombre de TA concernées et la valeur qui sera dupliquée //
						// Boucle sur le nombre de données ( = nombre de TA)
						$hole[$na_value_label][$raw_kpi_label] = $this->identifyDataHoles($hole[$na_value_label][$raw_kpi_label], $type, $product, $v, $group_table, $na_value_label, $raw_kpi_label );
						
						
						
						// ETAPE 2 :  DUPLICATION DES DONNEES  POUR LES TROUS IDENTIFIES				
						// Boucle sur tous les trous
						$this->duplicateData($hole[$na_value_label][$raw_kpi_label], $na_value_label, $raw_kpi_label);
					
					}else{
					
						foreach($this->na_values_axe3 as $k => $v_axe3){
						
							if($v_axe3 !== null){
								$raw_kpi_label_completed = $raw_kpi_label."@".$v_axe3;
								if(!isset($hole[$na_value_label][$raw_kpi_label_completed]))
									$hole[$na_value_label][$raw_kpi_label_completed] = array();
												
								// ETAPE 1 : IDENTIFICATION DES TROUS A COMBLER (on enregistre le nombre de TA concernées et la valeur qui sera dupliquée //
								// Boucle sur le nombre de données ( = nombre de TA)
								$hole[$na_value_label][$raw_kpi_label_completed] = $this->identifyDataHoles($hole[$na_value_label][$raw_kpi_label_completed], $type, $product, $v, $group_table, $na_value_label, $raw_kpi_label_completed );
								$this->msg($hole,"$v");
								// ETAPE 2 :  DUPLICATION DES DONNEES  POUR LES TROUS IDENTIFIES				
								// Boucle sur tous les trous
								$this->duplicateData($hole[$na_value_label][$raw_kpi_label_completed], $na_value_label, $raw_kpi_label_completed);
							}
						}
					
					}
					
				}
			}
		}
		
		$this->msg($this->datas_extra,"DATA EXTRA");
		$this->msg($this->datas,"DATA AFTER BLACK HOLE");
		return $this->datas;	
	} // End function completeDatasInvestigation()
	
	/**
	* Fonction qui identifie les trous à combler
	* @param array $tab : tableau contenant l'ensemble des trous (nb + val de base)
	* @param string $type : raw ou kpi
	* @param integer $product
	* @param $id_rawkpi
	* @param $group_table
	* @param string $index1  (Pour investigation dashboard - val =  / Pour Navigation dashboard - val = "")
	* @param string $index2  (Pour investigation dashboard - val =  / Pour Navigation dashboard - val = "")
	* @return array $tab On retourne le tableau afin de combler les trous dans la fonction duplicateData
	*/
	private function identifyDataHoles( $tab, $type, $product, $id_rawkpi, $group_table,  $index1 ="", $index2 = "" ){
		
		$n=0;
		
		for($i=0; $i<= $this->nb_datas; $i++ ){
						
			if($index1 !== ""){

				// Par défault la données n'est pas extrapolée
				$this->datas_extra[$index1][$index2][$i] = false;
				// Check sur la valeur de la donnée
				$hole_search = $this->datas[$index1][$index2][ $this->ta_values[$i] ];
			}else{
				// Par défault la données n'est pas extrapolée
				$this->datas_extra[$id_rawkpi][$i] = false;
				
				// Check sur la valeur de la donnée
				$hole_search = $this->datas[$i][$type][ $product ][$id_rawkpi];
			}
					
			// On enregistre la TA de base et on compte le nombre de TA où il n'y a pas de données
			if( $hole_search == "" or $hole_search == null)
			{

				// On incrémente le nombre de ta à parcourir pour combler ou non le trou entier 
				$tab['nb'][$n]++;
				if($i>0){
					// Enregistrement de la TA sur lequel on va se basé pour combler le trou complet  
					if( !isset( $tab['value_base'][$n] ) ){
						if( $index1 !== "")
							$tab['value_base'][$n] = $this->datas[$index1][$index2][ $this->ta_values[$i-1] ];
						else
							$tab['value_base'][$n] = $this->datas[$i-1][$type][ $product ][$id_rawkpi];
					}
				} else {

					if($index1 !== ""){
					
						$axe3 = explode("@",$index2);
						
						if($axe3[0] == $index2 ){
						// On recherche en base de données s'il existe une valeur récente sur lequel on pourrait se baser
							$tab['value_base'][$n] = $this->searchValueInDb( $type, $product, $id_rawkpi, $i, $group_table);		
						$this->msg("MODE 1 $type, $product, $id_rawkpi, $i, $group_table");
						}else{
							$this->msg("MODE 2  $type, $product, $id_rawkpi, $i, $group_table");
							$tab['value_base'][$n] = $this->searchValueInDb( $type, $product, $id_rawkpi, $i, $group_table, $axe3[1]."@".$axe3[2]."@".$axe3[3]);		

						}
					}else{
						$this->msg("MODE 3  $type, $product, $id_rawkpi, $i, $group_table");
						$tab['value_base'][$n] = $this->searchValueInDb( $type, $product, $id_rawkpi, $i, $group_table);		
					}
				}
			} else {
				
				// Si la données est présente on passe à la suivante
				$n = $i + 1;
			}
		}
		
		$this->msg($tab,"IDENTIFY HOLES");
		
		return $tab;
	} // End function identifyDataHoles()
	
	
	/**
	* Fonction qui duplique les données pour combler les trous
	* @param array $hole : tableau contenant les trous à combler ( nb TA + val de base) 
	* @param string $index1 : Depuis Navigation dans les Dashboards val = / Depuis Investigation Dashboard val = na_value_label
	* @param string $index2 : Depuis Navigation dans les Dashboards val = / Depuis Investigation Dashboard val = raw_kpi_label
	* @param string $index3 : Depuis Navigation dans les Dashboards val = / Depuis Investigation Dashboard val = ""
	*/
	private function duplicateData($hole, $index1, $index2, $index3=""){
		// Boucle sur tous les trous
		
		$this->msg($hole,"HOLE DUPLICATE DATA");
		foreach( $hole['value_base'] as $id_hole=>$val_base ){
			
			// Si la taille du trou est inférieur au paramètre global extrapolation_nb_ta (par défault val = 10)					
			if( $hole['nb'][$id_hole] <= $this->nbTaExtrapolation ){
				
				// Boucle sur le nombre de TA 
				for($t=0; $t <= ($hole['nb'][$id_hole] - 1) ; $t++ ){
					// Si on a récupéré la valeur en base, celle-ci peut être nulle
					if( $val_base !== "" and $val_base !== null ){
						
						// On enregistre les TA concernées pour le tooltip
						if($index3 == ""){
							$this->datas_extra[ $index1 ][ $index2 ][ $this->ta_values[$id_hole + $t]  ] = true;
							$this->datas[ $index1 ][ $index2 ][ $this->ta_values[$id_hole + $t]  ] = $val_base;
						}else{
							$this->datas_extra[$index3][$id_hole + $t] = true;
							$this->datas[ $id_hole + $t ][ $index1 ][ $index2 ][ $index3 ] = $val_base;
						}
					}
				}
			}
		}
	} // End function duplicateData()
	
	/**
	* Fonction qui retourne un tableau indiquant si les données sont extrapolées ou non
	* @return array(boolean)  $datas_extra: Tableau indiquant si les données sont extrapolées ou non (true si la données est extrapolée / false si elle ne l'est pas)
	*/
	public function getDatasExtra()
	{
		return $this->datas_extra;
	} // End function getDatasExtra()
	
	
	/**
	* Fonction qui retourne un tableau indiquant si les données sont extrapolées ou non
	* @return array(boolean)  $datas_extra: Tableau indiquant si les données sont extrapolées ou non (true si la données est extrapolée / false si elle ne l'est pas)
	*/
	public function getNeCodeFromLabel($_label, $_na, $product)
	{
		$query = "SELECT eor_id FROM edw_object_ref 
				WHERE eor_obj_type ='$_na' AND (
						'(' || eor_id || ')' = '$_label' OR eor_label = '$label')";
		$db = $this->setDbConnection($product);
		$res = $db->getOne($query);
		
		return $res;
	} // End function getDatasExtra()
	
	/**
	* Fonction qui définit le nombre de données
	* @param array $tab : tableau contenant soit tous les données soit toutes les ta 
	*/
	private function setNbDatas($tab){
		
		$this->nb_datas = count($tab) - 1;
	} // End function  setNbDatas
	
	/**
	* Fonction searchExtrapolatedValueInDb qui recherche une valeur sur la période  $this->nbTaExtrapolation (10 par défault) en commençant par la ta -1
	* @param string $type : type du compteur (raw / kpi)
	* @param string $product : id du produit du compteur
	* @param string $id_rawkpi : id du compteur
	* @param string $id : premier index du tableau de données (correspond à la ta)
	* @return string $value : valeur extrapolée
	*
	*/
	public function searchValueInDb( $type, $product, $id_rawkpi, $id, $group_table, $axe3="") {
	
		$value = "";
		// On se connecte à la base de données correspondante
		$db = $this->setDbConnection($product);
		
		// Récupération de l'id du raw ou kpi
		$raw_kpi_name = ( $type == 'kpi' ) ? $this->kpiModel->getNameFromId($id_rawkpi,$db) : $this->rawModel->getNameFromId($id_rawkpi,$db);
		
		// 
		__debug($group_table,"GROUP TABLE");
		
		$family = getFamilyFromGroupTable($group_table, $product);
		__debug($family,"GROUP TABLE");
		// Gestion du 3ème axe
		$_na_axe3 = "";
		if( isset( $this->na_axe3 ) ){
			
			$_na 				 = "{$this->na_level}_{$this->na_axe3}";
			$_na_condition 		 = "{$this->na_level} = '{$this->na_value}' AND {$this->na_axe3} = '{$this->na_axe3_value}'";

		}else{
			
			if( $axe3 !== ""  ){
				$t_axe3 = explode("@",$axe3);
				$_na = $this->na_level[$id]."_".$t_axe3[0];
				$_na_condition  = "{$this->na_level[$id]} = '{$this->na_value[$id]}' AND ";
				
				if($t_axe3[1] !== 'ALL'){
					$_na_condition .= "{$t_axe3[0]} = '{$t_axe3[1]}'";
				}else{
				
					$_na_condition .= "{$t_axe3[0]} = '".$this->getNeCodeFromLabel($t_axe3[2],$t_axe3[0], $product)."'";
				}
				
			}else{
				// maj 12/01/2010 - MPR Correction du bug 13690 : erreur SQL si mélange raw/kpi de famille avec et sans 3ieme axe
				// Lorsqu'on combine famille 3ème axe et 1er axe les paramètres d'entrée sont différents, on ne possède pas de 3ème axe
				// il faut donc extraire le 3ème lorsque le sort by est sur le raw/kpi de la famille 3ème axe
				
				// Récupération du niveau d'agrégation par défault pour générer le nom de la table de données 
				if( get_axe3( $family ,$product) ){
					$_na_axe3 = getNAAxe3MinFromFamily($product, $family );
					$_na_axe3 = "_".$_na_axe3[0];
				}
				
				// maj 12:10 26/11/2009 MPR : Correction du bug 13052 - Suppression des index $id
				$this->msg("MODE => $this->mode");
				$_na = ($this->mode == 'dash') ? $this->na_level : $this->na_level[$id];
				
				if( get_axe3( $family ,$product)){
					$na_value = $this->na_value;
				} else {
					$na_value = explode("|s|", $this->na_value);
					$this->na_value = $na_value[0];
				}
				$_na_condition  = ($this->mode == 'dash') ? "{$_na } = '{$this->na_value}'" : "{$_na } = '{$this->na_value[$id]}'";
			}
		}
		
		// Récupération du nom de la table correspondante aux données
		$table = "{$group_table}_{$type}_{$_na}{$_na_axe3}_{$this->ta_level}";

		$ta_value = $this->ta_values[$id];		

		// Construction de la requête qui récupère les données
		$query  = "SELECT {$this->ta_level} as ta,{$raw_kpi_name} as data
				  FROM {$table}
				  WHERE {$this->ta_level} < {$ta_value} AND {$raw_kpi_name} IS NOT NULL
				  AND {$_na_condition}
				  ORDER BY {$this->ta_level} DESC LIMIT 1 
				  ";
		
		$this->msg($query,"Recuperation de la valeur la plus proche en base");
		$result = $db->getRow($query);
		
		if($result['ta'] !== "" and $result['ta'] !== null){
			
			$date = new Date();

			// En fonction de la ta on définit le jour recherché et le jour sur lequel on extrapole les données
			switch( $this->ta_level ){
				
				case "month":
				case "month_bh":
					$day_1 = $ta_value."01";
					$day_2 = $result['ta']."01";
				break;
				case "week":
				case "week_bh":
					$day_1 = $date->getFirstDayFromWeek($ta_value);
					$day_2 = $date->getFirstDayFromWeek($result['ta']);
				break;
				case "day" :
				case "day_bh" :
					$day_1 = $ta_value;
					$day_2 = $result['ta'];
				break;
				case "hour" :
					$day_1 = substr($ta_value,0,count($ta_value)-2);
					$day_2 = substr($result['ta'],0,count($result['ta'])-2);
				break;
			}

			// On récupère le nombre de jours entre la valeur trouvée en base et le jour sur lequel on veut extrapoler les données
			$nb_ta = $date->getDatesDiff($day_1,$day_2);
			

			// Si l'intervalle de recherche n'est pas dépassé, on extrapole les données
			if( $nb_ta  <= $this->nbTaExtrapolation )
			{
				return $result['data'];
			} else {
				return null;
			}
		}
		return null;
	} // End function searchValueInDb
	
	/**
	* Fonction setDatas qui récupère les données extrapolees
	* @param array $ta_values : Tableau contenant l'ensemble des ta
	* @param boolean $valid_format : true depuis Investigation Dashboard
	*/
	public function setTaValues($ta_values,$valid_format=false)
	{
		if(!$valid_format){
		include_once(REP_PHYSIQUE_NIVEAU_0."class/Date.class.php");
		
		$date = new Date();
		
		// On reformate les dates au format T&A
		foreach($ta_values as $val){
			$this->ta_values[] = $date->getDateFromSelecteurFormat( $this->ta_level, $val );
		}
		}else{
			$this->ta_values = $ta_values;
		}
		
		// RECUPERATION DES DONNEES EXTRAPOLEES 
	} // End Function setDatas
	
	/**
	* Fonction qui détermine la liste des raws/kpis du graphe
	*/
	public function setRawKpi($rawkpi)
	{
		$this->rawkpi = $rawkpi;	
		
		$tab = array_keys($this->rawkpi);

		foreach($tab as $elem){
			
			switch($elem){
				case "kpi": 
					if( !isset($this->kpiModel) )
						$this->kpiModel = new KpiModel();
						
				break;
				case "raw":
					if( !isset($this->rawModel) )
						$this->rawModel = new RawModel();
				break;
			}
		}	
		
	} // End Function setDatas
	
	/**
	* Fonction qui détermine la liste des raws/kpis du graphe
	*/
	public function setNbRawKpi($nb_rawkpi)
	{
			$this->nb_rawkpi = $nb_rawkpi;						
	} // End Function setDatas
	
	/**
	* Fonction qui initialise la na concernée
	* @param string $na : Niveau d'agrégation réseau concerné 
	*/
	public function setNa($na)
	{
		$this->na_level = $na;
	} // End Function setNa
	
	/**
	* Fonction qui initialise la na concernée
	* @param string $na : Niveau d'agrégation réseau concerné 
	*/
	public function setNaAxe3($na_axe3)
	{
		$this->na_axe3 = $na_axe3;
		
	} // End Function setNa
		

	/**
	* Fonction qui initialise la na concernée
	* @param string $na : Niveau d'agrégation réseau concerné 
	*/
	public function setNaValueAxe3($na_values_axe3)
	{
		$this->na_values_axe3 = $na_values_axe3;
		
	} // End Function setNa
	
	/**
	* Fonction qui initialise la ta concernée
	* @param string $ta : Niveau d'agrégation temporel concerné 
	*/
	public function setTa($ta)
	{
		$this->ta_level = $ta;
	} // End Function setTa

	/**
	* Fonction qui initialise les éléments réseau
	* @param array/string $na_values : Element réseau concernés
	*/
	public function setNaValues($na_values, $na_values_label="")
	{

		if( isset($this->na_axe3) ){
			$_element = explode("|s|", $na_values );
			
			$this->na_value = $_element[0];		
			$this->na_axe3_value = $_element[1];
			if( $na_values_label !== "" ){
				$_label = explode("|s|", $na_values_label );
				$this->na_value_label = $_label[0];
				$this->na_axe3_value_label = $_label[1];
			}
			
		} else {
			$this->na_value = $na_values;		
			if( $na_values_label !== "" ){
				$this->na_value_label = $na_values_label;
			}
		}
		
	} // End Function setNaValues
} // End Class
?>