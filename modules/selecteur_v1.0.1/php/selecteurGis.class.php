<?php
/**
*	Classe permettant de manipuler un sélecteur pour les dashboard
*
*	@author	BBX - 30/09/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*
*	maj 20/08/2009 - MPR : Correction du bug 11077 : Gis avec Hour KO
*	maj 21/08/2009 MPR : Correction du bug 11078 - On récupère les infos du produit concerné
*	maj 27/08/2009 - MPR : Correction du bug 11248 et 11249 - Si aucun raw/kpi n'est déployé, on ne sélectionne pas le premier raw de la famille
*  maj 02/08/2011 MMT Bz 22614: utilisation de edw_field_name au lieu de nms_field_name pour liste des raw GIS
*
*/
class SelecteurGIS
{
	/**
	* Propriétés
	*/
	// Tableau contenu les propriétés du sélecteur
	private $selecteur_values = Array();
	// Contiendra un objet sélecteur
	private $selecteur = null;
	// Contiendra une instance de connexion à la base de données
	private $database_connection = null;
	// Contiendra la liste des na ainsi que leur label ainsi que la na par défault
	private $na;
	// Contiendra la liste des ta ainsi que leur label ainsi que la na par défault
	private $ta;
	
	/************************************************************************
	* Constructeur
	* @param : int	id produit	
	* @param : string	famille	
	************************************************************************/
	public function __construct($id_prod,$family)
	{	
                // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
		$this->database_connection = Database::getConnection( $id_prod );
		// Connexion à la base de données
		$this->id_prod = $id_prod;
		$this->family = $family;
		$_na = get_network_aggregation_max_from_family($family,0,$id_prod);
		$this->module = get_sys_global_parameters('module',0,$id_prod);
		
		$raw_default = $this->getFirstRawFromFamily();
		
		$this->selecteur_values = array('ta_level'	=> 'day',
										'date'	=> date('d/m/Y'),
										'na_level' => $_na,
										'gis_counters_selecteur' => $raw_default);		
	}
	
	
	public function setAutoRefreshMode( $autorefresh = 0 ){
		
		$this->selecteur_values['autorefresh'] = $autorefresh;
	}
	
	/************************************************************************
	* Méthode getSelecteurFromArray : paramètre un sélecteur depuis un tableau de valeurs
	* @param : array	Tableau contenant un paramétrage sélecteur
	************************************************************************/
	public function getSelecteurFromArray($array)
	{
		
		if(count($array) > 0) {
			foreach($array as $key=>$value) {
				$this->selecteur_values[$key] = $value;
			}
		}
	}


	/************************************************************************
	* Fonction qui retourne l'id du premier raw de la famille concernée
	* @param string $family 	: famille concerné	 
	* @param string $id_prod 	: produit concerné
	* @return string $id_raw 	: id du premier raw de la famille concerné
	************************************************************************/
	function getFirstRawFromFamily()
	{
		
		// maj 27/08/2009 - MPR : Correction du bug 11248 et 11249 - Si aucun raw/kpi n'est déployé, on ne sélectionne pas le premier raw de la famille
		// 02/08/2011 MMT Bz 22614: utilisation de edw_field_name au lieu de nms_field_name pour liste des raw GIS
		$query = "SELECT id_ligne, edw_field_name 
				  FROM sys_field_reference 
				  WHERE edw_group_table = 'edw_{$this->module}_{$this->family}_axe1' 
				  AND new_field = 0 AND on_off = 1 AND visible = 1
				  ORDER BY edw_field_name_label LIMIT 1";
		
		$res = $this->database_connection->getAll($query);		
		if( count($res) > 0 ){
			$raw = "raw@{$res[0]['id_ligne']}@{$res[0]['edw_field_name']}";
			return $raw;
		}
		else return false;
		
	}
	
	/************************************************************************
	* Méthode getTaArray : génère un tableau contenant la liste des TA
	* @param : array	Tableau contenant les niveaux d'agrégation temporels
	************************************************************************/
	public function setTaArray($ta_levels,$defaults)
	{
		// EN DUR POUR LE MOMENT
		$this->ta = array($ta_levels,$defaults);
			
	}

	
	/**
	 * Génère un tableau contenant la liste des ta
	 * @return array liste des ta
	 */
	public function getTaArray() {
		//on recupere la liste des labels de TA pour le produit
		$ta_levels = getTaLabelList($this->id_product);	
		//on recupere les parametres par defaut
		$defaults = $this->getDefaults();
		
		return Array($ta_levels,$defaults);
	}
	
	/**
	 * getter des parametres par defaut
	 * @return array tableau des parametres par defaut
	 **/ 
	public function getDefaults() {
		return $this->defaults;
	}
	
	/**
	* Méthode getNaArray : génère un tableau contenant la liste des NA
	* @param : array	$na_levels : Tableau contenant les niveaux d'agrégation
	* @param : array	$axe3_options : Tableau contenant les niveaux d'agrégation d'axe3
	* @param : array	$defaults : Tableau contenant les valeurs par défault
	*/
	public function setNaArray( $na_levels, $axe3_options, $defaults )
	{
		
		$this->na_level = $na_levels;
		$this->na_axe3 	= $axe3_options;
		
		$na2na = array();
		
		$this->na = Array($na_levels,$na2na,$axe3_options,$defaults);
	}
	
	/**
	* Méthode build : construit un sélecteur
	* @return array $this->selecteur_values : tableau contenant les paramètres du sélecteur
	*/
	public function build()
	{
		// Instanciation d'un sélecteur
		$this->selecteur = new selecteur();
		
		// __debug($this->selecteur_values,"before sel");
		
		// On met les valeurs dans le sélecteur
		if(is_array($this->selecteur_values)) $this->selecteur->setValues($this->selecteur_values);
		
		// on ajoute la boite "time"
		$this->selecteur->addBox(
			__T('SELECTEUR_TIME'),
			'dashboard_time',
			$this->getTaArray(),
			array('hide' => 'period')
		);
		// on ajoute la boite "network aggregation"
		if(count($this->na_axe3) == 0)
		{
			// maj 21/08/2009 MPR : Correction du bug 11078 - On récupère les infos du produit concerné
			$array_options = array('hide' => '3emeaxe, top','product' => $this->id_prod);
		}
		else 
		{
			$array_options = array('hide' => 'top', 'product' => $this->id_prod);	
		}
		
		$this->selecteur->addBox(__T('SELECTEUR_NETWORK_AGGREGATION'),'gis_NA',$this->na, $array_options, $this->na_value);

		// $this->selecteur->addBox(__T('SELECTEUR_NETWORK_AGGREGATION'),'dashboard_NA', $this->na , array('hide' => $hide,'product'=>"$this->id_prod"));
		
		
		$this->selecteur->addBox(
			__T('SELECTEUR_RAW_KPI_SELECTION'),
			'gis_sort_by', //insertion du fichier (fichier box_investigation_counters.php)
			'', //pas besoin de donnees a envoyer
			array('product' =>$this->id_prod, 'family' => $this->family),
			array('hide' => 'filter') //parametres a donner a la boite
		);	
		
		$this->selecteur->display();
		
		// On reformate les ta_value en fonction de la ta
		$this->convertTaValue();
		
		$this->setNaValue();
		
		
		return $this->selecteur_values;
		
		// on ajoute la boite "sort by"
	}
	
	/**
	 * Fonction qui récupère uniquement les id des éléments réseau et/ou 3ème axe
	*/
	public function setNaValue(){
	
		$na_value = explode("|s|",$this->selecteur_values['gis_nel_selecteur']);

		foreach($na_value as $key=>$val){
		
			$tab = explode("@",$val);
			
			$na_values[] = $tab[1];			
		
		}
		
		
		$this->selecteur_values['na_value'] = implode("||",$na_values);

		//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
		// Gestion du 3ème axe à inclure
		//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
		
	}

	
	/** Fonction convert_ta_value : reformate la ta_value en fonction de la ta
	* 
	*/
	public function convertTaValue(){
		
		// Formatsà convertir : 
		// hour 	=>	12/11/2008 15:00
		// day 	=>	12/11/2008 
		// week 	=>	W46-2008
		// month 	=>	12/03/2008 
		
		$date = $this->selecteur_values['date'];
			
		switch( $this->selecteur_values['ta_level'] ) {
					
			case 'hour' :	
				
				// maj 20/08/2009 - MPR : Correction du bug 11077 : Gis avec Hour KO
				$hour = substr($this->selecteur_values['hour'], 0, 2);				
				
				$day = substr($date, 0, 2);
				$month = substr($date, 3, 2);
				$year = substr($date, 6, 4);
				
				$ta_value = $year.$month.$day.$hour;
				
			break;
			
			case 'day':
			case 'day_bh':			
			
				$tab = explode("/",$date);
				$day = $tab[0];
				$month = $tab[1];
				$year = $tab[2];

				$ta_value = $year.$month.$day;
				
			break;
			
			// week 	=>	W46-2008
			case 'week' :
			case 'week_bh' :
				
				$week = substr($date, 1, 2);
				$year = substr($date, 4, 4);

				$ta_value = $year.$week;
				
			break;
			
			// month 	=>	12/03/2008 
			case 'month' :
			case 'month_bh' :
			
				$month = substr($date, 0, 2);
				$year  = substr($date, 3, 4);
				
				$ta_value = $year.$month;
		
			break;
			
		}

		$this->selecteur_values['date'] = $ta_value;

	}	
	
	
	/**
	* Méthode debug : affiche les valeurs du sélecteur
	*/
	public function debug()
	{
		echo '<div>Valeurs du s&eacute;lecteur :</div>';
		echo '<pre>';
		print_r($this->selecteur_values);
		echo '</pre>';
	}
}
?>