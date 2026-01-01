<?php
/*
	15/07/2009 GHX
		- Ajout de la fonction closeFromReport()
	17/08/2009 GHX
		- Modification dans le fonction getSortByArray() pour changer la fonction appelée sur la classe GTMModel
	29/03/2010 NSE 14592 
		- pour être cohérent avec l'initialisation de mode dans box_dashboard_mode, inversion du then/else/
   06/06/2011 MMT DE 3rd Axis ajoute et merge getNa2Na axe3 pour gestion de la feètre de selection 3eme axe
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
class SelecteurEdit
{
	/**
	* Propriétés
	*/
	// Tableau contenu les propriétés du sélecteur
	private $selecteur_values = Array();
	// Contiendra un objet sélecteur
	private $selecteur = null;
	// Contiendra un objet DashboardModel
	private $dashboardModel = null;
	// Contiendra un objet SelecteurModel
	private $selecteurModel = null;
	// Contiendra une instance de connexion à la base de données
	private $database = null;
	// Id du dashboard
	private $idDashboard = 0;
	// Id du sélecteur
	private $idSelecteur = 0;
	// Mode
	private $mode = 'new';
	// Temps
	private $startTime = 0;
	// Gestion d'erreur
	private $error = false;
	// Elements à ne pas afficher dans la na box
	private $hide = '';
	
    // Flag indiquant l'état du mode FixedHour (par défaut à false, non affiché)
    protected $_fixedHourEnable = false;

	/************************************************************************
	* Constructeur
	* @param : int	id dashboard
	************************************************************************/
	public function __construct($idDashboard = 0, $idSelecteur = 0, $hide = '')
	{
		// Mémorisation du temps pour stats perf
		$this->startTime = $this->getMicrotime();
		// Sauvegarde de l'id dashbord & du sélecteur
		$this->idDashboard = $idDashboard;
		$this->idSelecteur = $idSelecteur;		
		// Connexion à la base de données
                // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
		$this->database = Database::getConnection();		
		// Instanciation du Modèle sélecteur
		$this->selecteurModel = new SelecteurModel($idSelecteur);			
		// Si le sélecteur existe, on récupère ses valeurs et on passe en mode "édition"
		if(!$this->selecteurModel->getError()) {
			$this->mode = 'edit';
			$this->selecteur_values = $this->selecteurModel->getValues();
			// Mise à jour de l'id du dashboard
			$this->idDashboard = $this->selecteur_values['id_page'];
			// Instanciation du modèle dashboard
			$this->dashboardModel = new DashboardModel($this->selecteur_values['id_page']);
		}
		// Si le sélecteur n'existe pas
		else {
			// Instanciation du modèle dashboard
			$this->dashboardModel = new dashboardModel($idDashboard);
			// Si l'instanciation du dashboard a échoué, on stoppe tout. Les paramètres sont incorrects ou la dashboard n'est pas configuré
			if($this->dashboardModel->getError() === true) {
				echo '<div class="errorMsg">'.__T('U_DASHBOARD_CANT_LOAD_DASHBOARD').'</div>';
				$this->error = true;
				return false;
			}
			else {
				// Si le dashboard existe, on récupère ses valeurs
				$this->selecteur_values = $this->dashboardModel->getValues();
			}
		}
		// Elément à masquer
		$this->hide = $hide;
	}
	
    /**
     * Seeter du paramètre fixedHourEnable
     *
     * @param  boolean $bool
     * @return boolean
     */
    public function setFixedHourEnable( $bool )
    {
        if( is_bool( $bool ) )
        {
            $this->_fixedHourEnable = $bool;
            return true;
        }
        else
        {
            return false;
        }
    }

	/************************************************************************
	* Méthode getMicrotime : récupère en ms, le temps au moment de son appel
	* @return float	temps en ms
	************************************************************************/
	public function getMicrotime()
	{	
	    list($usec, $sec) = explode(" ",microtime());
	    return ((float)$usec + (float)$sec);
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
	* Méthode getNaArray : génère un tableau contenant la liste des NA
	* @param : array	Tableau contenant les niveaux d'agrégation
	************************************************************************/
	public function getNaArray()
	{		
		// NA levels : la liste des NA levels
		$na_levels = $this->dashboardModel->getNALevels(1);
		
		// na2na : le tableau permettant de savoir : pour un na_level, quels accordéons doivent être affichés dans le network element selecteur
		$na2na = $this->dashboardModel->getNa2Na();
		//06/06/2011 MMT DE 3rd Axis ajoute et merge getNa2Na axe3 pour gestion de la feètre de selection 3eme axe
		$na2naAxe3 = $this->dashboardModel->getNa2Na(3);
		$na2na = array_merge($na2na, $na2naAxe3);

		// 02/02/2009 - Modif. benoit : remplacement de l'appel à la méthode 'getNALevelsThirdAxis()' de la classe 'dashboardModel' par 'getNALevels(3)'

		// axe3 options : liste du premier menu select axe 3
		$axe3_options = $this->dashboardModel->getNALevels(3);
		
		// defaults values for this box
		$defaults = array(
			'na_level'	=> $this->selecteur_values['na_level'],
			'tot'		=> $this->selecteur_values['top'],
		);
		return Array($na_levels,$na2na,$axe3_options,$defaults);
	}
	
	/************************************************************************
	* Méthode getTaArray : génère un tableau contenant la liste des TA
	* @param : array	Tableau contenant les niveaux d'agrégation temporels
	************************************************************************/
	public function getTaArray()
	{
		// Récupération des time agregation de tous les produits
		$ta_levels = $this->dashboardModel->getTaLevels();
		
		// Récupération des codes TA
		$ta_keys = array_keys($ta_levels);
		
		// Valeurs par défaut
		$defaults = array(
			'ta_level'	=> $ta_levels[$ta_keys[0]],
			'date'		=> date('d/m/Y'),
			'hour'		=> date('H:00'),
			'period'	=> $this->selecteur_values['period'],
		);		
		return Array($ta_levels,$defaults);
	}
	
	/************************************************************************
	* Méthode getTaArray : génère un tableau contenant la liste des counters / kpi Sort By
	* @param : array	Tableau contenant les compteurs / kpi Sort by
	************************************************************************/
	public function getSortByArray()
	{
		// Parcours des gtms du dashboard
		foreach($this->dashboardModel->getGtms() as $id_gtm=>$gtm_name)
		{
			// Instanciation d'un objet GTMModel
			$GTMModel = new GTMModel($id_gtm);			
			$options = Array();
			// Parcours des kpi
			// 16:34 17/08/2009 GHX
			// On appelle une autre fonction
			foreach($GTMModel->getGtmUniqKpis($id_gtm) as $id_kpi=>$kpi_name){				
				$options["kpi@".$id_kpi] = "{$kpi_name}";
			}
			// Parcours des raws
			// 16:34 17/08/2009 GHX
			// On appelle une autre fonction
			foreach($GTMModel->getGtmUniqRaws($id_gtm) as $id_raw=>$raw_name){				
				$options["counter@".$id_raw] = "{$raw_name}";
			}
			$sort_by_groups[$gtm_name] = $options;
		}
		return $sort_by_groups;
	}
	
	/************************************************************************
	* Méthode build : construit et affiche un sélecteur
	************************************************************************/
	public function build()
	{
		// Instanciation d'un sélecteur
		$this->selecteur = new selecteur();
		
		// On met les valeurs dans le sélecteur
		if(is_array($this->selecteur_values)) $this->selecteur->setValues($this->selecteur_values);
		
		// on ajoute la boite "mode"											
		$this->selecteur->addBox("Mode",'dashboard_mode',Array($this->selecteur_values['mode']));	
		
		// on ajoute la boite "time"
        // 14/02/2011 OJT : DE Selecteur/Historique, gestion de l'historique max pour chaque TA
        $dashProducts = DashboardModel::getDashboardProducts( $this->idDashboard );
        $this->selecteur->max_period = 0;
        $taList = $this->getTaArray();
        foreach( $taList[0] as $oneTA )
        {
        	if( stripos( $oneTA, 'bh' ) === false )
            {
            	$this->selecteur->max_periods[strtolower($oneTA)] = ProductModel::getMaxHistory( $oneTA, $dashProducts );
                if( $this->selecteur->max_period === 0 ){
                	$this->selecteur->max_period = $this->selecteur->max_periods[strtolower($oneTA)];
                }
            }
        }
        $this->selecteur->fixedHourMode = $this->_fixedHourEnable;
		$this->selecteur->addBox(__T('SELECTEUR_TIME'),'dashboard_time',$this->getTaArray(),array('hide' => 'autorefresh,date,hour'));
		
		// Pour l'autorefresh, on a besoin d'indiquer l'id_page à javascript
		echo '<script>';
		echo '_dashboard_id_page = "'.$this->idDashboard.'"';
		echo '</script>';

		// on ajoute la boite "network aggregation"
		$this->selecteur->max_top = 50;
		// 29/03/2010 NSE 14592 : pour être cohérent avec l'initialisation de mode dans box_dashboard_mode, inversion du then/else
		$top = ($this->selecteur_values['mode'] == 'one') ? 'TON' : 'TOT';
		$array_network_levels = $this->getNaArray();
			// On cache la box 3ème axe si il n'y a aucun élément à afficher
			if(count($array_network_levels[2]) == 0) $array_options = array('top' => $top, 'hide' => '3emeaxe,'.$this->hide);
			else $array_options = array('top' => $top, 'hide' => $this->hide);
		$this->selecteur->addBox(__T('SELECTEUR_NETWORK_AGGREGATION'),'dashboard_NA',$array_network_levels,$array_options);

		// on ajoute la boite "sort by"												
		$this->selecteur->addBox(__T('SELECTEUR_SORT_BY'),'dashboard_sort_by',$this->getSortByArray());	

		// Affichage du sélecteur (avec le bouton Save plutôt que Display)
        selecteur::$displayMode = 'save';
		$this->selecteur->display();
	}
	
	/************************************************************************
	* Méthode debug : affiche les valeurs du sélecteur
	************************************************************************/
	public function debug()
	{
		echo '<div style="margin-left:20px;border:1px solid #898989;background-color:#DDF4FF;font-family:Arial;width:500px;">';
		echo "<p><b><i>S&eacute;lecteur g&eacute;n&eacute;r&eacute; en ";
		printf("%01.2f",$this->getMicrotime()-$this->startTime);
		echo " secondes</b></i></p>";
		echo "<p>Mode : ".$this->mode."</p>";
		echo '<div><b>Valeurs du s&eacute;lecteur :</b></div>';
		echo '<pre>';
		print_r($this->selecteur_values);
		echo '</pre>';
		echo '<div><b>Na 1er axe en commun :</b></div>';
		echo '<pre>';
		print_r($this->dashboardModel->getNALevels(1));
		echo '</pre>';
		echo '<div><b>Na 3eme axe en commun :</b></div>';
		echo '<pre>';
		print_r($this->dashboardModel->getNALevels(3));
		echo '</pre>';	
		echo '<div><b>Na 2 NA :</b></div>';
		echo '<pre>';
		print_r($this->dashboardModel->getNa2Na());
		echo '</pre>';	
		echo '<div><b>Produits li&eacute;s au dashboard :</b></div>';
		echo '<pre>';
		print_r($this->dashboardModel->getInvolvedProducts());
		echo '</pre>';				
		echo '<div>';
	}
	
	/************************************************************************
	* Méthode getError : retourne le code d'erreur du dashbaord
	* @return : true = pas d'erreur, false = objet inutilisable
	************************************************************************/
	public function getError() 
	{
		return $this->error;
	}

	/************************************************************************
	* Méthode save : sauvegarde un sélecteur
	* @return : void()
	************************************************************************/	
	public function save()
	{
		// On passe toutes les valeurs du sélecteur au model sélecteur + l'id page
		$this->selecteurModel->setValue('id_page',$this->idDashboard);
		foreach($this->selecteur_values as $key=>$value) {
			$this->selecteurModel->setValue($key,$value);
		}
		// Nouveau sélecteur
		if($this->mode == 'new') {
			$this->selecteurModel->addSelecteur();
		}
		// Mise à jour d'un sélecteur existant
		else {
			$this->selecteurModel->updateSelecteur();
		}
	}

	/************************************************************************
	* Méthode setAsUserHomepage : définit ce sélecteur comme homepage d'un utilisateur
	* @param : int	id user
	* @return : void	
	************************************************************************/	
	public function setAsUserHomepage($idUser)
	{
		// On détruit un éventuel ancien sélecteur
		if($this->mode == 'new') {
			$oldHomepage = SelecteurModel::getUserHomepage($idUser);
			$SelecteurModel = new SelecteurModel($oldHomepage);
			if(!$SelecteurModel->getError()) {
				$SelecteurModel->deleteSelecteur();
			}
			unset($SelecteurModel);
		}
		// On enregistre le nouveau
		$this->selecteurModel->setAsUserHomepage($idUser);
	}
	
	/************************************************************************
	* Méthode setAsReportFilter : définit ce sélecteur pour un rapport
	* @param : int	id report
	* @return : void	
	************************************************************************/	
	public function setAsReportFilter($idReport)
	{
		// On enregistre l'id rapport
		$this->selecteurModel->setAsReportFilter($idReport);
	}
	
	/************************************************************************
	* Méthode close() : affiche le code JS pour fermer la fenêtre
	* @return : void()
	************************************************************************/
	public function close() 
	{
		$image =  NIVEAU_0.'images/icones/detail_vert.gif';
		echo <<<END
		<script>
			if(window.opener.document.getElementById('icon_setup_filter')) {
				window.opener.document.getElementById('icon_setup_filter').src = '{$image}';
			}
			window.close();
		</script>
END;
	}
	
	/**
	 * Méthode qui ferme la fenêtre d'éditition du sélecteur d'un dashboard dans un rapport 
	 * et qui appel une fonction JS
	 *
	 *	15/07/2009 GHX
	 *		- Ajout de la fonction pour le BZ10570
	 */
	public function closeFromReport ()
	{
		echo "
			<script>
				window.opener.closeEditSelecteur();
				window.close();
			</script>
		";
	}
}
?>