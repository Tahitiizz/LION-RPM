<?php
/*
	07/08/2009 GHX
		- Modification dont on crée la date sinon on a un problème si on est sur un dès 9 premier jour de l'année
*/
?>
<?php
/**
*	Classe permettant de manipuler un sélecteur pour les dashboard
*
*	@author	BBX - 30/09/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*
*
*/
class SelecteurTopWorstList
{
	/**
	* Propriétés
	*/
	// Tableau contenu les propriétés du sélecteur
	private $selecteur_values = Array();
	// Contiendra un objet sélecteur
	private $selecteur = null;
	// Contiendra une instance de connexion à la base de données
	private $database = null;
	// Contiendra la liste des na ainsi que leur label ainsi que la na par défault
	private $na;
	// Contiendra la liste des ta ainsi que leur label ainsi que la na par défault
	private $ta;
	
	/************************************************************************
	* Constructeur
	* @param : int	id dashboard	<optional>
	************************************************************************/
	public function __construct($id_prod,$family)
	{
		// Connexion à la base de données
		$this->id_prod = $id_prod;
		$this->family = $family;
		
		// 10:44 07/08/2009 GHX
		// Modification dont on crée la date sinon on a un problème si on est sur un dès 9 premier jour de l'année
		$date = date("d/m/Y", mktime(0, 0, 0, date('m'), date('d')-1, date('Y')));
		$this->selecteur_values = array('ta_level'	=> 'day', 'date'=> $date);
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
	* Méthode getTaArray : génère un tableau contenant la liste des TA
	* @param : array	Tableau contenant les niveaux d'agrégation temporels
	************************************************************************/
	public function setTaArray($ta_levels,$defaults)
	{
		// EN DUR POUR LE MOMENT
		$this->ta = array($ta_levels,$defaults);
			
	}
	
	
	
	/**
	* Méthode getNaArray : génère un tableau contenant la liste des NA
	* @param : array	$na_levels : Tableau contenant les niveaux d'agrégation
	* @param : array	$axe3_options : Tableau contenant les niveaux d'agrégation d'axe3
	* @param : array	$defaults : Tableau contenant les valeurs par défault
	*/
	public function setNaArray( $na_levels, $axe3_options, $defaults )
	{
		$this->na = Array($na_levels,$axe3_options,$defaults);
	}
	
	/**
	* Méthode build : construit un sélecteur
	* @return array $this->selecteur_values : tableau contenant les paramètres du sélecteur
	*/
	public function build()
	{
		// Instanciation d'un sélecteur
		$this->selecteur = new selecteur();
		
		// On met les valeurs dans le sélecteur
		if(is_array($this->selecteur_values)) $this->selecteur->setValues($this->selecteur_values);
		
		// on ajoute la boite "time"
		$this->selecteur->addBox(__T('SELECTEUR_TIME'),'dashboard_time', $this->ta ,array('hide' => 'autorefresh,period'));

		// on ajoute la boite "network aggregation"
		$hide = 'top,na_level';
		if(!get_axe3($this->family,$this->id_prod)){
			$hide.= '3emeaxe';
		}
		$this->selecteur->addBox(__T('SELECTEUR_NETWORK_AGGREGATION'),'topworst_list_NA', $this->na , array('hide' => $hide,'product'=>"$this->id_prod"));
		
		$this->selecteur->display();
		
		// On reformate les ta_value en fonction de la ta
		$this->convertTaValue();
		
		// On récupère le na (s'il y en a plusieurs $this->selecteur_values['na'] = "")
		$this->setNaSelecteurValues();
		
		return $this->selecteur_values;
		
		// on ajoute la boite "sort by"
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
		//__debug($date,"date yep");
		//__debug($this->selecteur_values['ta_level'],"selecteur_values ta_level");
		
		switch( $this->selecteur_values['ta_level'] ) {
					
			case 'hour' :	
				
				$hour = substr($this->selecteur_values['hour'], 0, 2);
				
				$day = substr($date, 0, 2);
				$month = substr($date, 3, 2);
				$year = substr($date, 6, 4);
				
				$ta_value = $year.$month.$day.$hour;
				
			break;
			
			case 'day':
			case 'day_bh':			
			
				$day = substr($date, 0, 2);
				$month = substr($date, 3, 2);
				$year = substr($date, 6, 4);
				
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
	* fonction qui initialise la variable $this->selecteur_values['na']
	*/
	public function setNaSelecteurValues(){
	
		// On récupère tous les éléments réseaux sélectionnés
		$elements = explode("|s|", $this->selecteur_values['nel_selecteur']);

		// On compte le nombre de na différents
		$nb_na = array();
		if(count($elements) > 0){
			foreach($elements as $element){
				
				$tab_elements = explode("||",$element);
				
				if(!in_array($tab_elements[0], $nb_na)){
					$nb_na[] = $tab_elements[0];
				}
			}
			
			$this->selecteur_values['na'] = (count($nb_na) > 1) ? "": $nb_na[0]; 
		}else{
			$this->selecteur_values['na'] = (count($nb_na) > 1) ? "": $nb_na[0]; 
		}
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