<?php
/*
	18/05/2009 GHX
		- Correction du Bug BZ 9673 [REC][T&A CB 5.0][DASHBOARD]: lors de la navigation inter-dash de la même famille, on perd les heures
		- Sauvegarde du sortBy et du filtre
	19/05/2009 GHX
		- Sauvegarde du top pour le dashboard courant
	05/06/2009 GHX
		- On n'affiche pas la période en mode ONE
	10/06/2009 GHX
		- Ajout d'un deuxieme paramètre au constructeur SelecteurDashboard pour la correction du bug BZ9841
	06/07/2009 GHX
		- Correction du BZ10423 [REC][T&A Cb 5.0][Dashboard]: le Top n'est sauvegardé lors du passage d'un dashboard à un autre
	14/08/2009 GXH
		- (Evo) Modificatoin de la fontion appelé sur GTMModel pour récupérer la liste des KPI/RAW afin de récupérer uniquement les uniques (prise en comptes des memes élements (code+legende)
	20/08/2009 GHX
		- Correction du BZ 11075 [REC][T&A CB 5.0][TC#37107][TP#1][DASHBOARD]: paramétrage du calendar en hourly incorrecte
	28/10/2009 BBX
		- création de la fonction "checkValues" qui vérifie si la NA du sélecteur existe. Dans le cas contraire, on prend la première NA existante. BZ 11806
	12/11/2009 BBX NSE
		- Ajout du contrôle de la TA dans la fonction checkValues. BZ 12683
		- lancement de la fonction checkValues à la fin de la fonction loadExternalValues. BZ 12683
	17/11/2009 GHX
		- Correction d'un problème sur la correction du  BZ 12683 (TA hour). BZ 12805
	18/11/2009 BBX :
		- On vérifie que le débug global est actif avant d'afficher le contenu de la fonction debug. BZ 12805
   06/06/2011 MMT :
 *    - DE 3rd AXis, ajout des fonctions getNeSelectionArrayFromStringValue et getSourcePathToNa
 *      gestion des elements 3eme axes multiples dans getNaAndNeAxeNPath

 * 07/07/2011 NSE bz 22888 : on passe la liste des produits du Dash à getNaPaths() pour ne récupérer que les arcs existants sur le produit
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
class SelecteurDashboard
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
	// Contiendra une instance de connexion à la base de données
	private $database = null;
	// Id du dashboard
	private $id_dashboard = 0;
	// Mode
	private $mode = 'overtime';
	// Temps
	private $startTime = 0;
	// Gestion d'erreur
	private $error = false;

	/**
	 * Constructeur de la classe
	 *
	 * @param int $id_dashboard identifiant du dashboard
	 * @param string $mode mode du dashboard
	 * @return boolean état de chargement du modèle du dashboard
	 */

	public function __construct($id_dashboard = 0, $mode = 'overtime')
	{
		// Mémorisation du temps pour stats perf
		$this->startTime = $this->getMicrotime();
		// Sauvegarde de l'id dashbord
		$this->id_dashboard = $id_dashboard;
		// Sauvegarde du mode
		$this->mode = $mode;
		// Connexion à la base de données
		$this->database = DataBase::getConnection();
		// Récupération des valeurs par défaut du dashboard
		// 18:09 10/06/2009 GHX
		// Ajout du deuxieme paramètre au contructeur BZ9841
		$this->dashboardModel = new DashboardModel($id_dashboard, $mode);
		// Si l'instanciation du dashboard a échoué, on stoppe tout. Les paramètres sont incorrects ou la dashboard n'est pas configuré
		if($this->dashboardModel->getError() === true) {
			echo '<div class="errorMsg">'.__T('U_DASHBOARD_CANT_LOAD_DASHBOARD').'</div>';
			$this->error = true;
			return false;
		}

		// Si tout est ok, on passe les valeurs du dashboard au sélecteur

		$this->selecteur_values = $this->dashboardModel->getValues();

		// 28/10/2009 BBX : fonction qui vérifie si la NA du sélecteur existe. BZ 11806
		$this->checkValues();
	}

	// 05/02/2009 - Modif. benoit : ajout de la méthode ci-dessous

	/**
	 * Permet de charger des valeurs préexistantes pour le selecteur
	 *
	 * @param array $values valeurs préexistantes
	 */

	public function loadValues($values)
	{
		$this->selecteur_values = $values;
	}


	/**
	*	28/10/2009 BBX : création de la fonction qui vérifie si la NA du sélecteur existe.
	*	Dans le cas contraire, on prend la première NA existante. BZ 11806
	 *
	 */
	public function checkValues()
	{
		// Vérification de l'existence du NA
		$existingNa = array_reverse(array_keys($this->getNALevels()));
		$existingNa3 = array_reverse(array_keys($this->getNALevels(3)));
		// Axe1
		if(!in_array($this->selecteur_values['na_level'],$existingNa)) {
			// Si le NA n'exite pas, on prend le premier existant.
			$this->selecteur_values['na_level'] = $existingNa[0];
		}
		// Axe3
		if($this->selecteur_values['axe3'] != '')
		{
			if(!in_array($this->selecteur_values['axe3'],$existingNa3)) {
				// Si le NA3 n'exite pas, on prend le premier existant.
                                // 22/11/2011 BBX
                                // BZ 24764 : correction des notices PHP
				$this->selecteur_values['axe3'] = isset($existingNa3[0]) ? $existingNa3[0] : null;			
			}
		}
		// 29/10/2009 BBX : Si on a du 3ème axe, mais qu'aucune valeur n'existe, on force à ALL. BZ 12372
		if($this->selecteur_values['axe3'] != '' && !isset($this->selecteur_values['axe3_2']))
			$this->selecteur_values['axe3_2'] = 'ALL';

		// Debut BZ 12683
		// 12/11/2009 : BBX / NSE
		// On effectue le même contrôle sur la TA. BZ 12683
		$existingTA = $this->getTaArray();
		$existingTAlevel = array_keys($existingTA[0]);
                // 22/11/2011 BBX
                // BZ 24764 : correction des notices PHP
                if(!isset($this->selecteur_values['ta_level']))
                    $this->selecteur_values['ta_level'] = array();
		if(!in_array($this->selecteur_values['ta_level'],$existingTAlevel)) {
			// Si la TA n'exite pas, on prend le premier existant.
			$this->selecteur_values['ta_level'] = $existingTAlevel[0];
			// 17/11/2009 GHX / BBX : correction de la valeur par défaut sur la TA. BZ 12805
			$this->selecteur_values['date'] = $existingTA[1]['date'];
			$this->selecteur_values['hour'] = $existingTA[1]['hour'];
		}
		// 18/11/2009 BBX : si la TA a planté, on la définie par défaut. BZ 12805
		if($this->selecteur_values['date'] == '//') {
			$this->selecteur_values['date'] = $existingTA[1]['date'];
			$this->selecteur_values['hour'] = $existingTA[1]['hour'];
		}
		// Fin BZ 12683
	}

	/**
	 * Récupère le temps en millisecondes
	 *
	 * @return float temps en millisecondes
	 */

	public function getMicrotime()
	{
	    list($usec, $sec) = explode(" ",microtime());
	    return ((float)$usec + (float)$sec);
	}

	/**
	 * Permet de populer le selecteur depuis un tableau de valeurs
	 *
	 * @param array $_array liste des valeurs de paramétrage du selecteur
	 */

	public function getSelecteurFromArray($_array)
	{
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

	public function loadExternalValues($ext_values)
	{
		//print_r($ext_values);

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
			else // Tous les autres cas
			{
				$this->selecteur_values['date'] = getTaValueToDisplay($this->selecteur_values['ta_level'], $ta_value, "/");
			}
		}

		// valeur transmise : period

		if (isset($ext_values['period'])) {
			$this->selecteur_values['period'] = $ext_values['period'];
		}

		// valeurs transmises : na_axe1 / ne_axe1

		$na_axe1_levels = array_keys($this->getNALevels(1));

		if(isset($ext_values['na_axe1']) && in_array($ext_values['na_axe1'], $na_axe1_levels))
		{
			$this->selecteur_values['na_level'] = $ext_values['na_axe1'];

			if(isset($ext_values['ne_axe1'])){
				$this->selecteur_values['nel_selecteur'] = $ext_values['ne_axe1'];
			}
		}

		// valeurs transmises : na_axeN / ne_axeN

		$na_axeN_levels = array_keys($this->getNALevels(3));

		if(isset($ext_values['na_axeN']) && in_array($ext_values['na_axeN'], $na_axeN_levels))
		{
			$this->selecteur_values['axe3'] = $ext_values['na_axeN'];

			if(isset($ext_values['ne_axeN'])){
				$this->selecteur_values['axe3_2'] = $ext_values['ne_axeN'];
			}
		}

		// valeur transmise : top

		if (isset($ext_values['top'])) {
			$this->selecteur_values['top'] = $ext_values['top'];
		}

		// valeur transmise : sort_by

		// 15:43 18/05/2009 GHX
		// Si on a sort by provenant d'un dashboard (uniquement dans le cas ou l'on charge des données de la sessions)
		if (isset($ext_values['sort_by_dash']))
		{
			if ( array_key_exists($this->id_dashboard, $ext_values['sort_by_dash']) )
			{
				$ext_values['sort_by'] = $ext_values['sort_by_dash'][$this->id_dashboard];
			}
		}


		if (isset($ext_values['sort_by']))
		{
			$sort_by	= explode("@", $ext_values['sort_by']);
			$order		= array_pop($sort_by);

			$this->selecteur_values['sort_by']	= implode("@", $sort_by);
			$this->selecteur_values['order']	= $order;
		}

		// valeur transmise : filter

		// 15:43 18/05/2009 GHX
		// Si on a filtre provenant d'un dashboard (uniquement dans le cas ou l'on charge des données de la sessions)
		if (isset($ext_values['filter_dash']))
		{
			if ( array_key_exists($this->id_dashboard, $ext_values['filter_dash']) )
			{
				$ext_values['filter'] = $ext_values['filter_dash'][$this->id_dashboard];
			}
		}

		if (isset($ext_values['filter']))
		{
			$filter			= explode("@", $ext_values['filter']);
			$filter_value	= array_pop($filter);
			$filter_operand	= array_pop($filter);

			$this->selecteur_values['filter_id']		= implode("@", $filter);
			$this->selecteur_values['filter_operande']	= $filter_operand;
			$this->selecteur_values['filter_value']		= $filter_value;
		}

		// Debut BZ 12683
		// 12/11/2009 BBX NSE :
		// On relance un check des valeurs afin de s'assurer que la TA / NA récupérées sont correctes. BZ 12683
		$this->checkValues();
		// Fin BZ 12683
	}

	/**
	 * Permet de sauvegarder les valeurs du selecteur dans le tableau de sessions
	 * Les valeurs transmises via la session sont la ta et sa valeur ainsi que les différentes valeurs de na (axe 1 et N)
	 *
	 */

	public function saveToSession()
	{
		$session_values = array();

		// 1 - Sauvegarde de la ta, de sa valeur et de la période

		$session_values['ta']		= $this->selecteur_values['ta_level'];
		// 11:04 18/05/2009 GHX
		// Correction du Bug BZ 9673 [REC][T&A CB 5.0][DASHBOARD]: lors de la navigation inter-dash de la même famille, on perd les heures
		if (  $this->selecteur_values['ta_level'] == 'hour' )
		{
			$session_values['ta_value']	= getTaValueToDisplayReverse($this->selecteur_values['ta_level'], $this->selecteur_values['date']." ".$this->selecteur_values['hour'], "/");
		}
		else
		{
			$session_values['ta_value']	= getTaValueToDisplayReverse($this->selecteur_values['ta_level'], $this->selecteur_values['date'], "/");
		}

		$session_values['period']	= $this->selecteur_values['period'];

		// 2 - Sauvegarde de la na axe1 et de sa / ses valeur(s)

		$session_values['na_axe1']	= $this->selecteur_values['na_level'];
		$session_values['ne_axe1']	= $this->selecteur_values['nel_selecteur'];

		// 3 - Sauvegarde de la na axeN et de sa valeur (si ces valeurs existent)

		if(isset($this->selecteur_values['axe3'])){
			$session_values['na_axeN']	= $this->selecteur_values['axe3'];
			$session_values['ne_axeN']	= $this->selecteur_values['axe3_2'];
		}

		// 09:57 19/05/2009 GHX
		// Sauvegarde du top pour le dashboard courant uniquement
		// 15:30 06/07/2009 GHX
		// Correction du BZ10423 [REC][T&A Cb 5.0][Dashboard]: le Top n'est sauvegardé lors du passage d'un dashboard à un autre
		// Maintenant on sauvegarde le top sans notion de ONE ou OT et de dashboard
		$session_values['top'] = $this->selecteur_values['top'];

		// 15:36 18/05/2009 GHX
		// Ajout de la sauvegarde du sort by et du filtre en fonction du dashboards
		if (isset($this->selecteur_values['sort_by']))
		{
			$session_values['sort_by_dash'][$this->id_dashboard] = $this->selecteur_values['sort_by'].'@'.$this->selecteur_values['order'];
		}

		if ( isset($this->selecteur_values['filter_id']) )
		{
			$session_values['filter_dash'][$this->id_dashboard] = $this->selecteur_values['filter_id'].'@'.$this->selecteur_values['filter_operande'].'@'.$this->selecteur_values['filter_value'];
		}

		// 4 - Sauvegarde des valeurs dans le tableau de sessions

		$_SESSION['TA']['selecteur'] = $session_values;
	}

	/**
	 * Génère un tableau contenant la liste des na
	 *
	 * @return array liste des na
	 */

	public function getNaArray()
	{
		// NA levels : la liste des NA levels
		$na_levels = $this->dashboardModel->getNALevels(1);

		// na2na : le tableau permettant de savoir : pour un na_level, quels accordéons doivent être affichés dans le network element selecteur
		$na2na = $this->dashboardModel->getNa2Na();
		//06/06/2011 MMT DE 3rd Axis concatene le 3eme axe aussi à la liste
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


	/**
	 * 06/06/2011 MMT DE 3rd Axis extract function from dashboard_display index.py file
	 * get the array of Ne selection per NA from the standard String format given value
	 * Check existance of each NA and only keep NAs and elements visible int the path resuling from de given  $na_level
	 * @param String $neStringValue  format  <NA1>||<NE1>|s|<NA1>||<NE2>|s|<NA2>||<NE3>...
	 * @param String $na_level Na level
	 * @param int $axe axis optional, 1(default) or 3
	 * @return array  format [NA1]{NE1, NE2} [NA2]{NE3}...
	 */
	public function getNeSelectionArrayFromStringValue($neStringValue,$na_level,$axe=1){

		$ne1_all = explode("|s|", $neStringValue);
		$ne1 = array();

		for ($i=0; $i < count($ne1_all); $i++) {
			$ne1_tmp = explode("||", $ne1_all[$i]);
			$ne1[$ne1_tmp[0]][] = $ne1_tmp[1];
		}

		// Si une seule na est défini dans le tableau de ne et qu'une seule ne existe pour cette na, on définit la na axe1 abcisse comme la na descente de la na sélectionnée

		// 15:05 21/07/2009 GHX
		// Correction d'un bug quand on fait reload
		if ( count($ne1) )
		{
			$na2na = $this->dashboardModel->getNa2Na($axe);
			// Récupère la liste de tous les éléments sélectionnées

			$currentSelection = array();

			foreach ( $ne1 as $_na1 => $_ne1 )
			{
				// Si le niveau d'agrégation fait parti de la liste des éléments réseaux du dashboard
				if ( array_key_exists($_na1, $na2na) )
				{
					if ( in_array($_na1, $na2na[$na_level]) )
					{
						// Garde uniquement les éléments dont le niveau d'agrégation est visible dans la sélection des éléments réseaux
						$currentSelection[$_na1] = $_ne1;
					}
				}
			}
			$ne1 = $currentSelection;
		}

		return $ne1;

	}



	/**
	 * 06/06/2011 MMT DE 3rd Axis extract function from dashboard_display index.py file
	 * return the path from the top of the topology to the given $na_level
	 * format is key(NA) => value(NA aggregation source) the order is top -> given $na_level
	 * @param String $na_level current NA level
	 * @param int $axe axis optional, 1(default) or 3
	 * @return array list of nas and its aggreagtion sources
	 */
	public function getSourcePathToNa($na_level,$axe=1){

		$ret = array();
		$tmp_paths = $this->getNaPaths($axe);

		$na2na = $this->dashboardModel->getNa2Na($axe);
		$na2na = $na2na[$na_level];

		foreach ( $tmp_paths as $_na => $_value )
		{
			// On regarde si le chemin est possible
			if ( in_array($_na, $na2na) || $_na == $na_level ){
				
				// Si le NA a plusieurs enfants on prend celui en commun par rapport au NA sélectionné dans le sélecteur
				if ( count($_value) != 1 )
				{
					$_valueTmp = array_intersect($_value, $na2na);

					// 16:32 28/08/2009 GHX
					if ( count($_valueTmp) > 0 )
						$_value = $_valueTmp;

					// Si après l'intersecte on a toujours plusieurs fils on prendra TOUJOURS le premier par ordre alphabétique (pour éviter une valeur aléatoire)
					sort($_value);

				}
				$ret[$_na] = $_value[0];
			}
		}
		return $ret;
	}

	/**
	 * Génère un tableau contenant la liste des ta
	 *
	 * @return array liste des ta
	 */

	public function getTaArray()
	{
		// Récupération des time agregation de tous les produits
		$ta_levels = $this->dashboardModel->getTaLevels();

		// Récupération des codes TA
		$ta_keys = array_keys($ta_levels);

		// Valeurs par défaut

		// 26/01/2009 - Modif. benoit : on place la date par défaut sur le day (si cette valeur existe)

		$ta_level = ((in_array("day", $ta_keys)) ? "day" : $ta_keys[0]);

		// 22/01/2009 - Modif. benoit : la valeur par défaut de la date est "J-1" et non le jour courant
		// 22/01/2009 - Modif. benoit : la valeur par défaut de l'heure est "H-1" et non l'heure courante

		// 11:12 20/08/2009 GHX
		// Correction du BZ 11075
		// Prise en compte du compute_mode pour savoir sur quel jour on doit se placer
		switch ( get_sys_global_parameters('compute_mode') )
		{
			case 'hourly' : $offsetDaySelecteur = 0; break;
			default : $offsetDaySelecteur = 1; break;
		}

        // 14/01/2011 OJT : Correction du bz20150. Optimisation via le Date::getHour
		$defaults = array(
			'ta_level'	=> $ta_level,
			'date'		=> getTaValueToDisplayV2("day", getDay($offsetDaySelecteur), "/"),
			'hour'		=> Date::getHour( 1, 'H:00' ),
			'period'	=> $this->selecteur_values['period'],
		);

		return Array($ta_levels,$defaults);
	}

	/**
	 * Génère un tableau contenant la liste des raws / kpis de tri
	 *
	 *	14/08/2009 GHX
	 *		- (Evo) modification de la fonction appelé sur la class GTMModel pour récupérer la liste des KPI/RAW
	 *
	 * @return array liste des raws / kpis de tri
	 */
	public function getSortByArray()
	{
		// Parcours des gtms du dashboard

		foreach($this->dashboardModel->getGtms() as $id_gtm=>$gtm_name)
		{
			// Instanciation d'un objet GTMModel

			$GTMModel = new GTMModel($id_gtm);
			$options = Array();

			// 27/01/2009 - Modif. benoit : ajout de l'information '$id_gtm' dans la clé du tableau d'options

			// Parcours des kpis

			// 16:06 14/08/2009 GHX
			// Modification de la fonction appelée
			foreach($GTMModel->getGtmUniqKpis() as $id_kpi=>$kpi_name){
				$options["kpi@".$id_kpi."@".$id_gtm] = "{$kpi_name}";
			}

			// Parcours des raws
			// 16:06 14/08/2009 GHX
			// Modification de la fonction appelée
			foreach($GTMModel->getGtmUniqRaws() as $id_raw=>$raw_name){
				$options["raw@".$id_raw."@".$id_gtm] = "{$raw_name}";
			}

			$sort_by_groups[$gtm_name] = $options;
		}
		return $sort_by_groups;
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
		if(is_array($this->selecteur_values)) $this->selecteur->setValues($this->selecteur_values);

            // On ajoute la boite "time"
            // 14/02/2011 OJT : DE Selecteur/Historique, gestion de l'historique max pour chaque TA
            $dashProducts = DashboardModel::getDashboardProducts( $this->id_dashboard );
            $this->selecteur->max_period = ProductModel::getMaxHistory( $this->selecteur_values['ta_level'], $dashProducts );
            $taList = $this->getTaArray();
            foreach( $taList[0] as $oneTA )
            {
                if( stripos( $oneTA, 'bh' ) === false )
                {
                    $this->selecteur->max_periods[strtolower($oneTA)] = ProductModel::getMaxHistory( $oneTA, $dashProducts );
                }
            }

		// 10:59 05/06/2009 GHX
		// On n'affiche pas le champ période en mode ONE
		$this->selecteur->addBox(__T('SELECTEUR_TIME'),'dashboard_time',$this->getTaArray(), (($this->mode == 'overtime') ? array('hide' => '') : array('hide' => 'period')));

		// Pour l'autorefresh, on a besoin d'indiquer l'id_page à javascript
		echo '<script>';
		echo '_dashboard_id_page = "'.$this->id_dashboard.'"';
		echo '</script>';

		// on ajoute la boite "network aggregation"
		$this->selecteur->max_top = 50;
		$top = ($this->mode == 'overtime') ? 'TOT' : 'TON';
		$array_network_levels = $this->getNaArray();

		// On cache la box 3ème axe si il n'y a aucun élément à afficher
            if( count($array_network_levels[2] ) == 0 )
		{
			$array_options = array('top' => $top, 'hide' => '3emeaxe');
		}
		else
		{
			$array_options = array('top' => $top);
		}
		$this->selecteur->addBox(__T('SELECTEUR_NETWORK_AGGREGATION'),'dashboard_NA',$array_network_levels,$array_options);
        $this->selecteur->addBox(__T('SELECTEUR_SORT_BY'),'dashboard_sort_by',$this->getSortByArray()); // on ajoute la boite "sort by"
        $this->selecteur->addFilter(); // Show - hide
        $this->selecteur->display(); // Affichage du sélecteur
	}

	/**
	 * Permet d'ajouter certaines valeurs manquantes dans le tableau de valeurs du selecteur
	 *
	 */

	private function addMissingValue()
	{
		// Ajout des informations sur la ta

		$ta = $this->getTaArray();

		if (!isset($this->selecteur_values['date']))
		{
			$this->selecteur_values['ta_level']	= $ta[1]['ta_level'];
			$this->selecteur_values['date']		= $ta[1]['date'];
			$this->selecteur_values['hour']		= $ta[1]['hour'];
		}

		// Ajout des informations sur la na (valeur(s) des ne préselectionnées)

		// Note : à modifier par la suite en fonction de l'avancement du dev. du dashboard builder (pour l'instant, impossible de sélectionner des ne)

		if (!isset($this->selecteur_values['nel_selecteur']))
		{
			$this->selecteur_values['nel_selecteur'] = "";
		}

		// Ajout des informations sur la na 3eme axe

		// axe3 - axe3_2

		$na			= $this->getNaArray();
		$na_axe3	= $na[2];

		if ((count($na_axe3) > 0) && ($this->selecteur_values['axe3'] == ""))
		{
			$axe3_keys = array_keys($na_axe3);
			$this->selecteur_values['axe3'] = $axe3_keys[0];

			// Définition de la ne axe3

			if (!(isset($this->selecteur_values['axe3_2']))) {
				$this->selecteur_values['axe3_2'] = "ALL";
			}
		}

		//print_r($na);
	}

	/**
	 * Permet d'afficher le mode debug qui liste les valeurs du selecteur
	 *
	 */

	public function debug()
	{
		// 18/11/2009 BBX : on vérifie que le débug global est actif avant d'afficher le contenu. BZ 12805
		if(get_sys_debug('debug_global'))
		{
			echo '<div style="margin-left:20px;border:1px solid #898989;background-color:#DDF4FF;font-family:Arial;width:500px;">';
			echo "<p><b><i>S&eacute;lecteur g&eacute;n&eacute;r&eacute; en ";
			printf("%01.2f",$this->getMicrotime()-$this->startTime);
			echo " secondes</b></i></p>";
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
			echo '</div>';
		}
	}

	// 28/01/2009 - Modif. benoit : création de la méthode ci-dessous

	/**
	 * Retourne les chemins des na disponibles
	 *
	 * @param int $axe valeur de l'axe des na dont on souhaite les chemins (par défaut, l'axe 1)
	 * @return array liste des chemins des na
         *
         * 07/07/2011 NSE bz 22888 : on passe la liste des produits en paramètre
         *
	 */
	public function getNaPaths($axe = 1, $productTable = array())
	{
		return $this->dashboardModel->getNaPaths($axe, $productTable);
	}

	/**
	 * Retourne les parents des na disponibles
	 *
	 * @param int $axe valeur de l'axe des na dont on souhaite les chemins (par défaut, l'axe 1)
	 * @return array liste des chemins des na
	 */

	public function getNaParent($axe = 1)
	{
		return $this->dashboardModel->getNaParent($axe);
	}

	// 04/02/2009 - Modif. benoit : création de la méthode ci-dessous

	/**
	 * Récupère toutes les NA communes d'un axe donné
	 *
	 * @param int $axe valeur de l'axe des na dont on souhaite les niveaux (par défaut, l'axe 1)
	 * @return array liste des niveaux communs
	 */

	public function getNALevels($axe = 1)
	{
		return $this->dashboardModel->getNALevels($axe);
	}

	// 02/02/2009 - Modif. benoit : création de la méthode ci-dessous

	/**
	 * Retourne les na axe N par famille et produit
	 *
	 */

	public function getNaAxeNByProduct()
	{
		return $this->dashboardModel->getNaAxeNByProduct();
	}

	// 29/01/2009 - Modif. benoit : création de la méthode ci-dessous

	/**
	 * Récupère le chemin d'accès à une na et une ne donnée (optionnel).
	 * Le chemin retourné pour la na sera de la forme : 'na_axeN_paths[id_product][family][0] = na'
	 * Le chemin retourné pour la ne sera de la forme : 'na_axeN_paths[id_product][family][na][0] = ne'
	 *
	 * @param string $na nom de la na dont on cherche le chemin
	 * @param string $ne valeur de la ne dont on souhaite le chemin
	 * @return array tableau contenant le chemin vers la na et vers la ne
	 */

	public function getNaAndNeAxeNPath($na = "", $ne = "ALL")
	{
		$na_axeN_paths	= array();
		$na_axeN_labels	= array();
		$ne_axeN_paths	= array();

		$na_axeN = $this->dashboardModel->getNaAxeNByProduct();

		if (count($na_axeN) > 0)
		{
			if ($na == "") // Aucune na d'axe3 spécifiée, on sélectionne alors toutes les na correspondants à la valeur défaut
			{
				foreach ($na_axeN as $product => $na_family) {
					foreach ($na_family as $family => $na_list) {

						for ($i=0; $i < count($na_list); $i++) {
							if ($na_list[$i]['third_axis_default_level'] == 1)
							{
								$na_tmp = $na_list[$i]['agregation'];

								$na_axeN_paths[$product][$family] = array($na_tmp);

								// On sauvegarde le label de la na 3ème axe

								if (!isset($na_axeN_labels[$na_tmp])) {
									$na_axeN_labels[$na_tmp] = $na_list[$i]['agregation_label'];
								}

								// On sélectionne la ne correspondante à la na sélectionnée

								$ne_axeN_paths[$product][$family][$na_list[$i]['agregation']] = array(getOneNeFromNa($na_tmp, $product));
							}
						}
					}
				}
			}
			else // Une na d'axe 3 est spécifiée, on la sélectionne
			{
				foreach ($na_axeN as $product => $na_family) {
					foreach ($na_family as $family => $na_list) {
						for ($i=0; $i < count($na_list); $i++) {
							if ($na == $na_list[$i]['agregation'])
							{
								// Note : pour l'instant, on gère un seul axe N (le 3) et le tableau de na axeN contient un seul élément

								$na_axeN_paths[$product][$family] = array($na);

								// On sauvegarde le label de la na 3ème axe

								if (!isset($na_axeN_labels[$na])) {
									$na_axeN_labels[$na] = $na_list[$i]['agregation_label'];
								}
								//06/06/2011 MMT DE 3rd Axis. L'element 3eme axe peut maintenant être multiple, il faut donc
								// sauvegarder un tableau, le $ne est une chaine de la forme <NA1>||<NE1>|s|<NA1>||<NE2>|s|<NA2>||<NE3>...
								// utilisation de la fonction getNeSelectionArrayFromStringValue
								$ne_axeN_paths[$product][$family] = $this->getNeSelectionArrayFromStringValue($ne,$na,3);
							}
						}
					}
				}
			}
		}


		return array('na_axeN' => $na_axeN_paths, 'na_axeN_label' => $na_axeN_labels, 'ne_axeN' => $ne_axeN_paths);
	}

	/**
	 * Retourne l'état du chargement du dashboard
	 *
	 * @return : boolean état de chargement du dashboard (true : pas d'erreur, false : erreur lors du chargement)
	 */

	public function getError()
	{
		return $this->error;
	}

	/**
	 * Retourne les valeurs du selecteur
	 *
	 * @return array liste des valeurs du selecteur
	 */

	public function getValues()
	{
		return $this->selecteur_values;
	}

	/**
	 * Retourne la valeur du selecteur
	 *
	 * @param la clé de la valeur voulue
	 * @return la valeur du selecteur
	 */

	public function getValue($key)
	{
		return $this->selecteur_values[$key];
	}


	/**
	 * Set la valeur du selecteur
	 *
	 * @param la clé de la valeur à définir
	 * @param la valeur
	 * @return void la valeur du selecteur
	 */

	public function setValue($key,$val)
	{
		$this->selecteur_values[$key] = $val;
	}

    /**
     * Retourne les produits concernés
     * 20/12/2010 BBX
     * BZ 18510
     * @return array
     */
    public function getInvolvedProducts()
    {
        return $this->dashboardModel->getInvolvedProducts();
	}
}
?>