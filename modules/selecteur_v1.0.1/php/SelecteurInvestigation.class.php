<?php
/**
*	Classe permettant de manipuler un sélecteur pour investigation dashboard
*
*	@author	SPS - 28/05/2009
*	@version	CB 5.0.0.0
*	@since	CB 5.0.0.0
*	@see InvestigationModel
*/
class SelecteurInvestigation
{
	/**
	* Propriétés
	*/
	private $id_product;
	private $id_family;
	
	// Tableau contenu les propriétés du sélecteur
	private $selecteur_values = Array();
	
	// Contiendra une instance de connexion à la base de données
	private $database = null;

	
	/**
	 * instance du modele 
	 * @var InvestigationModel
	 **/
	private $investigationModel;
	
	
	/**
	 * Constructeur de la classe
	 *
	 * @param int $product id du produit
	 * @param int $family id de la famille
	 */
	public function __construct($product, $family) {
		// Connexion à la base de données
                // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
		$this->database = Database::getConnection();
		
		$this->id_product = $product;
		$this->id_family = $family;
		
		$day = date ("d ")-1;
		$date = $day.date("/m/Y");
		
		$this->selecteur_values = array('ta_level'	=> 'day', 'date'=> $date, 'period' => 30);
		
		$this->investigationModel = new InvestigationModel($product, $family);;
	}

	
	/**
	 * Permet de charger des valeurs préexistantes pour le selecteur
	 *
	 * @param array $values valeurs préexistantes
	 */
	public function loadValues($values) {
		$this->selecteur_values = $values;
	}
	
		
	/**
	 * Permet de populer le selecteur depuis un tableau de valeurs
	 *
	 * @param array $_array liste des valeurs de paramétrage du selecteur
	 */
	public function getSelecteurFromArray($_array) {
		if(count($_array) > 0) {
			foreach($_array as $key=>$value) {
				$this->selecteur_values[$key] = $value;
			}
		}
	}
	
	/**
	 * Permet de charger des valeurs transmises depuis une source externe (via GET ou SESSION) dans le selecteur
	 * Note : cette méthode est temporaire, il faudra IMPERATIVEMENT unifier les valeurs du selecteur et celles transmises.
	 * L'unification devra se faire du côté du selecteur
	 *
	 * @param array $ext_values liste des valeurs transmises depuis la source externe
	 */
	public function loadExternalValues($ext_values) {
		
		// valeur transmise : ta
		if(isset($ext_values['ta'])){			
			$this->selecteur_values['ta_level'] = $ext_values['ta'];
		}
		
		// valeur transmise : ta_value
		
		if (isset($ext_values['ta_value'])) 
		{
			$ta_value = $ext_values['ta_value'];
						
			// Cas "hour" : on scinde la ta_value et l'on stocke les résultats dans 2 variables de '$this->selecteur_values'
			if ($this->selecteur_values['ta_level'] == "hour")
			{
				$this->selecteur_values['date'] = getTaValueToDisplay("day", substr($ta_value, 0, 8), "/");
				$this->selecteur_values['hour'] = substr($ta_value, 8, 2).":00";
			}
			if ($this->selecteur_values['ta_level'] == "week" || $this->selecteur_values['ta_level'] == "week_bh")
			{
				$this->selecteur_values['date'] = getTaValueToDisplay($this->selecteur_values['ta_level'], $ta_value);
			}
			else // Tous les autres cas
			{
				$this->selecteur_values['date'] = getTaValueToDisplay($this->selecteur_values['ta_level'], $ta_value, "/");
			}
		}
		
		// valeur transmise : period
		if (isset($ext_values['period'])) {
			$this->selecteur_values['period'] = $ext_values['period'];
		}
		
		if (isset($ext_values['axe3'])) {
			$this->selecteur_values['axe3'] = $ext_values['axe3'];
		}
		if (isset($ext_values['axe3_2'])) {
			$this->selecteur_values['axe3_2'] = $ext_values['axe3_2'];
		}
	}
	
	/**
	 * Permet de sauvegarder les valeurs du selecteur dans le tableau de sessions
	 * Les valeurs transmises via la session sont la ta et sa valeur ainsi que les différentes valeurs de na (axe 1 et N)
	 * 
	 */
	public function saveToSession() {
		$session_values = array();
		
		//Sauvegarde de la ta, de sa valeur et de la période
		$session_values['ta']		= $this->selecteur_values['ta_level'];
		$session_values['ta_value']	= getTaValueToDisplayReverse($this->selecteur_values['ta_level'], $this->selecteur_values['date'], "/");
		$session_values['period']	= $this->selecteur_values['period'];
		
		//sauvegarde des selecteurs
		$session_values['investigation_nel_selecteur'] = $this->selecteur_values['investigation_nel_selecteur'];
		$session_values['investigation_counters_selecteur'] = $this->selecteur_values['investigation_counters_selecteur'];
		
		//Sauvegarde des valeurs dans le tableau de sessions
		$_SESSION['TA']['selecteur'] = $session_values;
	}
	
	
	/**
	 * Permet de charger les paramètres sauvegardés en session dans le selecteur courant
	 **/
	public function loadFromSession()
	{
		$session_values = $_SESSION['TA']['selecteur'];
		
		//Chargement de la ta et de sa valeur
		$this->selecteur_values['ta_level'] = $session_values['ta'];
		
		// Valeur de ta "hour" : on scinde la ta_value et l'on stocke les résultats dans 2 variables de '$this->selecteur_values'
		if ($session_values['ta'] == "hour")
		{
			$this->selecteur_values['date'] = getTaValueToDisplay("day", substr($session_values['ta_value'], 0, 8), "/");
			$this->selecteur_values['hour'] = substr($session_values['ta_value'], 8, 2).":00";
		}
		else // Tous les autres cas
		{
			$this->selecteur_values['date'] = getTaValueToDisplay($session_values['ta'], $session_values['ta_value'], "/");
		}
		
		//chargement des valeurs des selecteurs
		$this->selecteur_values['investigation_nel_selecteur'] = $session_values['investigation_nel_selecteur'];
		$this->selecteur_values['investigation_counters_selecteur'] = $session_values['investigation_counters_selecteur'];
		
	}
	
	/**
	 * Génère un tableau contenant la liste des na
	 *
	 * @return array liste des na
	 */
	public function getNaArray() {
		
		//on recupere la liste des labels NA pour la famille et le produit
		$tab_na = getNaLabelList("na",$this->id_family,$this->id_product);

		//si on a du 3eme axe, on fait la meme chose
		if (get_axe3($this->id_family, $this->id_product)) {
			$tab_na_axe3 = getNaLabelList("na_axe3",$this->id_family,$this->id_product);
		}
		
		$axe3_options = $tab_na_axe3[$this->id_family];
		
		// NA levels : la liste des NA levels pour la famille
		$na_levels = $tab_na[$this->id_family];
						
		return Array( $na_levels, $axe3_options ); 
	
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
	 * setter des parametres par defaut
	 * @params array $defaults tableau de parametres par defaut
	 **/ 
	public function setDefaults($defaults) {
		$this->defaults = $defaults;
	}
	
	/**
	 * getter des parametres par defaut
	 * @return array tableau des parametres par defaut
	 **/ 
	public function getDefaults() {
		return $this->defaults;
	}
	
	/**
	 * Génère un tableau contenant la liste des raws / kpis de tri
	 *
	 * @return array liste des raws / kpis de tri
	 */
	public function getRawKPIs(){	
		return $this->investigationModel->getRawKPIs();	
	}
	
	/**
	 * Permet de construire et d'afficher le selecteur
	 *
	 */
	public function build()
	{
		// Instanciation d'un sélecteur
		$this->selecteur = new selecteur();
		
		// 26/01/2009 - Modif. benoit : avant de construire les boxes, on complète les valeurs manquantes de '$this->selecteur_values'

		$this->addMissingValue();

		// On met les valeurs dans le sélecteur
        // 14/02/2011 OJT : DE Selecteur/Historique, gestion de l'historique max pour chaque TA
		if(is_array($this->selecteur_values)) $this->selecteur->setValues($this->selecteur_values);
        $this->selecteur->max_period = ProductModel::getMaxHistory( $this->selecteur_values['ta_level'], array( $this->id_product ), $this->id_family );
        $taList = $this->getTaArray();
        foreach( $taList[0] as $oneTA )
        {
            if( stripos( $oneTA, 'bh' ) === false )
            {
                $this->selecteur->max_periods[strtolower($oneTA)] = ProductModel::getMaxHistory( $oneTA, array( $this->id_product ), $this->id_family );
            }
        }
		
		// on ajoute la boite "time"		
		$this->selecteur->addBox(
			__T('SELECTEUR_TIME'),
			'dashboard_time',
			$this->getTaArray(),
			array('hide' => 'autorefresh')
		);
		
		//contient la liste des na, et des 3emes axes possibles
		$na_data = $this->getNaArray();
		
		//on regarde si on peut avoir un 3eme axe
		if(!get_axe3($this->id_family,$this->id_product)){
			$hide.= '3emeaxe';
		}
		
		//on ajoute la boite Network Aggregation
		$this->selecteur->addBox(
			__T('SELECTEUR_NETWORK_AGGREGATION'),	// titre de la boite
			'investigation_NA',						// type de la boite (fichier box_investigation_NA.php)
			$na_data,							// informations à donner à la boite
			array('hide'=> $hide, 'product'=> $this->id_product)	// paramètres à donner à la boite ['hide'], ...
		);
		
		//on ajoute la boite Raw/KPI Selection
		$this->selecteur->addBox(
			__T('SELECTEUR_RAW_KPI_SELECTION'),
			'investigation_counters', //insertion du fichier (fichier box_investigation_counters.php)
			'', //pas besoin de donnees a envoyer
			array('product' =>$this->id_product, 'family' => $this->id_family) //parametres a donner a la boite
		);	
			
		$this->convertTaValue();
		
		// Affichage du sélecteur
		$this->selecteur->display();
		
		return $this->selecteur_values;
	}
	
	/**
	 * Permet d'ajouter certaines valeurs manquantes dans le tableau de valeurs du selecteur
	 *
	 **/
	private function addMissingValue() {
		// Ajout des informations sur la ta
		$ta = $this->getTaArray();
		
		if (!isset($this->selecteur_values['date'])) 
		{
			$this->selecteur_values['ta_level']	= $ta[1]['ta_level'];
			$this->selecteur_values['date']		= $ta[1]['date'];
			$this->selecteur_values['hour']		= $ta[1]['hour'];
		}

		
		if (!isset($this->selecteur_values['investigation_nel_selecteur'])) {
			$this->selecteur_values['investigation_nel_selecteur'] = "";
		}
		
		if (!isset($this->selecteur_values['investigation_counters_selecteur'])) {
			$this->selecteur_values['investigation_counters_selecteur'] = "";
		}

	}
	
	/**
	 * Permet d'afficher le mode debug qui liste les valeurs du selecteur
	 *
	 */
	public function debug() {
		echo '<div style="margin-left:20px;border:1px solid #898989;background-color:#DDF4FF;font-family:Arial;width:500px;">';
		echo '<div><b>Valeurs du s&eacute;lecteur :</b></div>';
		echo '<pre>';
		print_r($this->selecteur_values);
		echo '</pre>';
		echo '</div>';
	}


	/**
	 * Retourne l'état du chargement du dashboard
	 *
	 * @return : boolean état de chargement du dashboard (true : pas d'erreur, false : erreur lors du chargement)
	 */
	
	public function getError() {
		return $this->error;
	}
	
	/**
	 * Retourne les valeurs du selecteur
	 *
	 * @return array liste des valeurs du selecteur
	 **/
	public function getValues() {	
		return $this->selecteur_values;
	}


	
	/**
	 * Retourne la valeur du selecteur
	 *
	 * @param la clé de la valeur voulue
	 * @return la valeur du selecteur
	 */
	public function getValue($key) {
		return $this->selecteur_values[$key];
	}

	
	/**
	 * Set la valeur du selecteur
	 *
	 * @param la clé de la valeur à définir
	 * @param la valeur
	 * @return void la valeur du selecteur
	 */
	public function setValue($key,$val) {
		$this->selecteur_values[$key] = $val;
	}


	/** 
	 * Fonction convert_ta_value : reformate la ta_value en fonction de la ta
	 **/
	public function convertTaValue(){
		
		// Formatsà convertir : 
		// hour 	=>	12/11/2008 15:00
		// day 	=>	12/11/2008 
		// week 	=>	W46-2008
		// month 	=>	12/03/2008 
		
		$date = $this->selecteur_values['date'].' '.$this->selecteur_values['hour'];
		
		$this->selecteur_values['date'] = Date::getDateFromSelecteurFormat($this->selecteur_values['ta_level'],$date);

	}
	
}

?>
