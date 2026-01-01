<?php
/*

	!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

   23/09/2011 MMT 23743 ajout cast explicit pour PG9.1

	05/06/2009 GHX
		La classe est en cours de compatibilité avec le CB5.0
		mais comme aucunes autres appli externes (AA, SLM, TT) n'utilisent les liens vers T&A.
		On arrete pour le moment son développement pour le CB5.0

	!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	
	27/08/2009 GHX
		- Correction du BZ 11206 [REC][T&A CB 5.0][TP#1][TC#14300][ALARM Dynamic]: pb sur les liens dans le pdf reçu
			-> Utilisation de la nouvelle classe DatabaseConnection()
			->Ajout de la fonction checkParam_product
			-> Si on se trouve sur le master, on fait une redirection vers le master
		!!! ATTENTION CETTE VERSION N'EST TOUJOURS PAS COMPATIBLE AVEC LES LIENS EXTERNES PROVENANT DE AA !!!!
*/
?>
<?php
/**
 * Cette classe permet de gérer les liens externes vers T&A.
 * La gestion des liens se fait en deux parties, la première dans index.php et la deuxième dans acces_intranet.php.
 * 
 * 	>> index.php
 * 		On vérifie si c'est un lien externe, ensuite vérification des paramètres puis sauvegarde des paramètres. Si c'est bon on vérifie le cookie et redirection vers controle_session.php
 * 		Si les paramètres de l'URL sont incorrect, un message d'erreur est affiché et si le cookie n'est pas bon, on arrive sur la page de connexion.
 * 
 * 	>> acces_intranet.php
 * 		On charge les paramètres, on récupère l'id du dashboard à afficher par rapport aux paramètres. Sauvegarde en session des paramètres du sélecteur en fonction des paramètres
 * 		Si aucun dashboard on arrive sur la homepage, les paramètres du sélecteurs sont chargés sauf si aucune homepage.
 * 
 * Liste des fichiers modifiés pour prendre en compte les liens externes :
 * 	- index.php
 * 	- acces_intranet.php
 * 	- controle_session.php (aucun appel à cette classe)
 * 	- reporting/.../dashboard_associe.php (pour les liens depuis l'envoi de mail des alarmes) = type de lien interne
 * 
 * 
 * NOTE :
 * 	Le tag since correspond à la version du CB la fonction ou le paramètre existe et le tag version correspond à la dernière version du CB dans lequel la fonction à été modifié
 * 	SI il y a des modifications de faites sur ce fichier, NE PAS OUBLIER de mettre à jour le tag version des fonctions ainsi que celui de la classe.
 * 	Celà permet de savoir rapidement dans quelle version on eu lieu les modifications.
 *
 *  
 * @author GHX
 * @since cb4.0.0.00
 * @version cb4.0.0.00
 *
	- maj 26/05/2008 Benjamin : Ajout d'un filtre sur agregation_level pour obtenir le bon résultat. BZ6698
	- maj 21/03/2008, benoit : correction du bug 4864
	- maj 18/04/2008, benoit : correction du bug 6388
	- maj 18/04/2008, benoit : correction du bug 6387
	- maj 23/05/2008, benoit : correction des bugs 6686/6688/6388/6759
	- maj 19/06/2008, benoit : correction du bug 6937
	- maj 19/06/2008, benoit : correction du bug 6940
	- maj 17/07/2008 BBX : Récupération de la famille concernée en fonction de l'id dashboard quand celà est possible BZ7124
	- maj 17/07/2008 BBX : affectation du na sélectionné BZ7124
	- maj 17/07/2008 BBX : on force la NA en minuscules BZ 7129
 *
 */
class ExternalLink {

	/**
	 * Mode débug, s'il est activé la redirection ne se fait automatiquement 
	 * un lien est affiché. Il suffit de cliquer dessus pour être redirigé. Car il est impossible
	 * de faire une redirection via header si des données ont été envoyées (= affichées)
	 * Et il ne sera pas possible d'aller jusqu'à la homepage car la redirection sera aussi bloqué sur acces_intranet.php
	 * 
	 * cf. get_sys_debug('external_link')
	 * 	0 : désactivé / 1 : activé
	 * 
	 * @since cb4.0.0.00
	 * @var int
	 */
	private $debug;
	
	/**
	 * Connexion sur la base de données T&A
	 * 
	 * @since cb4.0.0.00
	 * @var Ressource
	 */
	private $db_connec;
	
	/**
	 * Contenu de la variable $_GET
	 * 
	 * @since cb4.0.0.00
	 * @var array
	 */
	private $request;
	
	/**
	 * Tableau contenant la liste des paramètres
	 * 
	 * @since cb4.0.0.00
	 * @var array
	 */
	private $params;
	
	/**
	 * Tableau contenant la liste des erreurs
	 * 
	 * @since cb4.0.0.00
	 * @var array
	 */
	private $errors;
	
	/**
	 * Type du lien
	 * 
	 * @since cb4.0.0.00
	 * @var string
	 */
	private $type;
	
	/**
	 * Tableau contenant la liste des paramètres communs aux liens
	 * Tableau contenant la liste des paramètres uniquement pour les liens externes
	 * Tableau contenant la liste des paramètres uniquement pour les liens internes
	 * 
	 * @since cb4.0.0.00
	 * @var array
	 */
	private $commonParameters;
	private $externalParameters;
	private $internalParameters;
		
	/**
	 * Tableau contenant la liste des paramètres possibles dans l'URL
	 * 
	 * @since cb4.0.0.00
	 * @var array
	 */
	private $allParameters;
	
	/**
	 * Constructeur 
	 * 
	 * @since cb4.0.0.00
	 * @version cbR.0.0.06
	 * @param DatabaseConnection $database_connection instance de la classe DatabaseConnection
	 */
	public function __construct( $database_connection ) {
		$this->db_connec = $database_connection;
		$this->params    = new ArrayObject();
		$this->errors    = array();
		$this->debug     = get_sys_debug('external_link');
		
		// 10:37 27/08/2009 GHX
		// Ajout du paramètre product
		$this->commonParameters   = array('from', 'ta', 'na', 'na_value', 'na_axe3', 'na_axe3_value', 'product');
		$this->externalParameters = array('com');
		$this->internalParameters = array('alarm_type', 'alarm_id', 'ta_value');
		
		$this->allParameters = array_merge($this->commonParameters, $this->externalParameters, $this->internalParameters);
	} // End function __construct
	
	/**
	 * Retourne vrai si c'est un lien externe, le lien sera considéré comme un lien externe si une des valeurs contenu dans le tableau
	 * $this->allParameters est présent dans l'URL.
	 * 
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
	 * @param array $request : contenu de la variable $_GET
	 * @return boolean
	 */
	public function isExternalLink ( $request ) {		
		$keys = array_keys($request);
		
		$result1 = array_intersect($this->commonParameters, $keys);
		$result2 = array_intersect($this->externalParameters, $keys);
		if ( sizeof($result1) > 0 ||  sizeof($result2) > 0 )
			return true;
		
		return false;
	} // End function isExternalLink

	/**
	 * Retourne vrai si c'est un lien interne (c'est à dire depuis les mails) le lien sera considéré comme un lien externe si une des valeurs contenu dans le tableau
	 * $this->allParameters est présent dans l'URL.
	 * 
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
	 * @param array $request : contenu de la variable $_GET
	 * @return boolean
	 */
	public function isInternalLink ( $request ) {
		$keys = array_keys($request);
		
		$result1 = array_intersect($this->commonParameters, $keys);
		$result2 = array_intersect($this->internalParameters, $keys);
		if ( sizeof($result1) > 0 ||  sizeof($result2) > 0 )
			return true;
		
		return false;
	} // End function isInternalLink
	
	/**
	 * Vérifie la présence d'un cookie, si oui on regarde s'il est toujours valide dans le cas
	 * contraire la redirection ne sera pas possible. Il vérifie aussi le login/mdp contenu dans le cookie
	 * Renvoie true si le cookie est correct sinon false.
	 * 
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
	 * @return boolean
	 */
	public function checkCookie () {
		// Le cookie n'existe pas, l'utilisateur n'est pas identifié
		if ( !isset($_COOKIE['externalLinkToTA_login']) || !isset($_COOKIE['externalLinkToTA_mdp']))
			return false;
		
		$login_encode    = $_COOKIE['externalLinkToTA_login'];
		$password_encode = $_COOKIE['externalLinkToTA_mdp'];
		
		// On récupère le login de l'utilisateur.
		//23/09/2011 MMT 23743  ajout cast explicit pour PG9.1
		$query = "
			SELECT login 
			FROM users t1 
			WHERE (t1.password='".$password_encode."') 
				AND on_off=1 
				AND CURRENT_DATE::text <substring(date_valid::text from 1 for 4)||'-'||substring(date_valid::text from 5 for 2)||'-'||substring(date_valid::text from 7 for 2)
			";
		
		$login = $this->db_connec->getOne($query);

		// Le login n'est plus valable ou n'existe pas/plus
		if (  $this->db_connec->getNumRows() == 0 )
			return false;

		// Le login du cookie est incorrect
		if ( base64_encode($login) != $login_encode )
			return false;
		
		if ( $this->debug )
			echo 'Le cookie est OK';
		return true;
	} // End function checkCookie
	
	/**
	 * Créer un cookie ou le met à jour, on met dans le cookie uniquement le login/mdp encodé en base64.
	 * 
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
	 */
	public function saveCookie () {
		 if ( $this->debug )
			 echo '<span style="color:red">Le cookie ne peut pas être créé en mode débug</span><br />';
		
		$session_time = get_sys_global_parameters('session_time') * 60;
		setcookie('externalLinkToTA_login', base64_encode($_SESSION['login']), (time() + $session_time));
		setcookie('externalLinkToTA_mdp', $_SESSION['password'], (time() + $session_time));
	} // End function saveCookie
	
	/**
	 * Supprime le cookie
	 * 
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
	 */
	public function deleteCookie () {
		setcookie('externalLinkToTA_login', '', 0);
		setcookie('externalLinkToTA_mdp', '', 0);
	} // End function deleteCookie
	
	/**
	 * Vérifie les paramètres de l'URL s'ils ne sont pas correctes une exception est levé pour l'ensemble des erreurs
	 * Si certains paramètres ne sont pas nécessaires pour le lien, ils seront ignorés.
	 * Renvoie true si tous est correct sinon false.
	 * 
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
	 * @param array $request : contenu de la variable $_GET
	 * @return boolean
	 */
	public function checkParameters ( $request ) {
		$this->request = $request;
		
		$this->type = $this->getTypeLink();
		
		// 19/06/2008 - Modif. benoit : correction du bug 6937. On réalise le check sur les valeurs de l'alarme ou du com avant de vérifier les parametres from, ta, na et na_axe3 au lieu de l'inverse

		// 09:37 27/08/2009 GHX
		// Vérifie l'id du produit passé dans l'URL
		$this->checkParam_product();
		
		if ($this->type == 'internal')
		{
			$this->checkParam_alarm();
		}
		else 
		{
			$this->checkParam_com();
		}

		$this->checkParam_from();
		$this->checkParam_ta();
		$this->checkParam_na();
		$this->checkParam_na_axe3();
		
		if ( sizeof($this->errors) > 0 ) return false;
		
		return true;
	} // End function check
	
	/**
	 * Sauvegarde les paramètres de l'URL dans la session s'il n'y a pas eu d'erreur
	 * cf. $_SESSION['externalLink']
	 * 
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
	 */
	public function saveParameters () {
		if ( $this->debug )
			echo 'saveParameters() : <pre>'.print_r($this->params, 1).'</pre>';
		
		if ( sizeof($this->errors) == 0 )
			$_SESSION['externalLink'] = serialize($this->params);
	} // End function saveParameters
	
	/**
	 * Charge les paramètres qui sont en session et récupère les paramètres nécessaires
	 * à la création des variables de session pour le sélecteur
	 * 
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
	 * @return boolean
	 */
	public function loadParameters () {
		if ( !isset($_SESSION['externalLink']) )
			return false;
		
		 if ( $this->debug )
			echo 'loadParameters() : <pre>'.print_r($_SESSION['externalLink'], 1).'</pre>';
		
		$this->params = unserialize($_SESSION['externalLink']);
		$this->type = $this->getTypeLink();
		unset($_SESSION['externalLink']);
		return true;
	} // End function loadParameters
	
	/**
	 * Retourne un tableau d'erreurs
	 * 
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
	 * @return array
	 */
	public function getErrors () {
		return $this->errors;
	} // End function getErrors
	
	/**
	 * Renvoi le nom de l'application qui a créé le lien si celui est précisé dans l'URL sinon renvoi null
	 * 
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
	 * @return string
	 */
	public function getFrom () {
		if ( isset($this->from) )
			return $this->from;
		return null;
	} // End function getFrom
	
	/**
	 * Renvoi le type de lien "internal" ou "external" si indéfini retourne null
	 * 
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
	 * @return string
	 */
	public function getTypeLink () {
		if ( sizeof($this->params) > 0 ) {
			foreach ( $this->internalParameters as $i ) { 
				if ( $this->params->offsetExists($i) )
					return 'internal';
			}
			foreach ( $this->externalParameters as $i ) { 
				if ( $this->params->offsetExists($i) )
					return 'external';
			}
		}
		elseif ( sizeof($this->request) > 0 ) {
			$keys = array_keys($this->request);
			$result = array_intersect($this->internalParameters, $keys);
			if ( sizeof($result) > 0 )
				return 'internal';
			$result = array_intersect($this->externalParameters, $keys);
			$result2 = array_intersect($this->commonParameters, $keys);
			if ( sizeof($result) > 0 || sizeof($result2) > 0 )
				return 'external';
		}

		// 22/05/2008 - Modif. benoit : correction des bugs 6686/6688/6388/6759. On ne doit jamais retouner le type null. Le lien ne peut être qu'interne ou externe

		//return null;
		return 'external';
	} // End function getTypeLink
	
	/**
	 * Redirection sur la homepage ou le dashboard
	 * 
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
	 */
	public function redirect () {
		$url_location = ProductModel::getCompleteUrlForMasterGui('controle_session.php');

		if ( $this->debug )
			echo '<center><p><b><a href="'.$url_location.'">GO TO HOMEPAGE </a></b></p></center>';
		else
			echo '<script>parent.top.location.href = "'.$url_location.'";</script>';
		
		exit();
	} // End function redirect
	
	/**
	 * Retourne les paramètres nécessaires pour le fichier dashboard_associe.php
	 * 
	 * @since cb4.0.0.00
	 * @version cb5.0.0.06
	 * @return string 
	 */
	public function getURLAssociatedDashboards () {
		$res = 'alarm_type='.$this->alarm_type;
		$res .= '&id_alarm='.$this->alarm_id;
		$res .= '&na='.$this->na. ( isset($this->na_axe3) ? '_'.$this->na_axe3 : '' );
		$res .= '&na_value='.$this->na_value. ( isset($this->na_axe3) ? get_sys_global_parameters('sep_axe3').$this->na_axe3_value : '' );
		$res .= '&ta='.$this->ta;
		$res .= '&ta_value='.$this->ta_value;
		// 10:30 27/08/2009 GHX
		// Ajout de l'id du product dans l'url
		$res .= '&product='.$this->product;
		$res .= '&externalLink=ok';
		
		return $res;
	} // End function getURLAssociatedDashboards
	
	/**
	 * Retourne l'id_page du dashoard à afficher
	 * 
	 * 	27/08/2009 GHX
	 *		- L'ID d'une alarme est maintenant une chaine de caractère => Modif SQL
	 *
	 * @since cb4.0.0.00
	 * @version cb5.0.0.06
	 * @return int 
	 */
	public function getIdDashboard () {
		if ( $this->type == 'internal' ) {
			if ( $this->alarm_type == 'static' )
				$table = 'sys_definition_alarm_static';
			elseif ( $this->alarm_type == 'dyn_alarm' )
				$table = 'sys_definition_alarm_dynamic';
			elseif ( $this->alarm_type == 'top-worst' )
				$table = 'sys_definition_alarm_top_worst';
			
			// 09:29 27/08/2009 GHX
			// On met l'ID de l'alarme entre cote
			$query = "
				SELECT t0.id_page
				FROM sys_pauto_page_name t0
				WHERE t0.family = (SELECT family FROM ".$table." WHERE alarm_id = '".$this->alarm_id."' LIMIT 1)
				LIMIT 1
				";
		}
		else {
			if ( $this->debug ) {
				echo '<br />$this->com : '.$this->com.'<br />';
			}
			
			if ( !isset($this->com) )
				return null;
			
			if ( is_numeric($this->com) ) {

				// 18/04/2008 - Modif. benoit : correction du bug 6388. Correction de la requete (suppression d'un alias inexistant)

				$query = "
					SELECT saacc_dashboard_id_page
					FROM sys_aa_column_code
					WHERE saacc_aacode = '".$this->com."'";
			}
			else {
				$query = "
					SELECT saacc_dashboard_id_page
					FROM sys_aa_column_code 
					WHERE saacc_fc_label = '".$this->com."'";
			}
		}
		
		return $this->db_connec->getOne($query);;
	} // End function getIdDashboard
	
	/**
	 * Charge en session les paramètres du sélecteur
	 * 
	 * 	15:56 04/06/2009 GHX
	 *		- Modification pour prendre en compte le nouveau sélecteur 
	 *	 
	 * @since cb4.0.0.00
	 * @version cb4.1.0.00
	 * @param int $idDash : identifiant id_page du dashboard 
	 */
	public function loadSelecteurInSession ( $idDash ) {
		if ( $this->debug )
			echo '<br />loadSelecteurInSession ( '.$idDash.' )<br />';
			
		
		if ( $this->type == 'external' ) {
			// Récupère la ganularité temporelle la plus petite
			$ta_level = get_ta_min();
			if ( $ta_level == 'day' )
				$date = substr($this->ta, 0, 8);
			else 
			{
				$date =	$this->ta;
	
				if ( strlen($this->ta) == 8 ){

					// 18/04/2008 - Modif. benoit : correction du bug 6387. Si l'heure n'est pas présente dans la valeur de la ta on bascule en day au lieu de positionner arbitrairement la valeur de l'heure à 23

					//$ta_value .= '23';
					$ta_level = "day";
				}
			}
		}
		else {
			$ta_level = $this->ta;
			$date = $this->ta_value;
		}
		
		$queryConfigDash = "SELECT * FROM sys_definition_selecteur WHERE sds_id_page = '{$idDash}'";
		$resultConfigDash = $db_connec->getRow($queryConfigDash);
		
		$selecteurInfo = array(
				'ta_level' => $ta_level,
				'na_level' => $this->na,
				'period'   => $resultConfigDash['sds_period'],
				'top'      => $resultConfigDash['sds_top'],
				'date'     => getTaValueToDisplayV2($ta_level, $date, "/")
			);

		// Si on est sur la TA hour on ajout l'heure
		if ( $resultConfigDash['sds_ta'] == 'hour' )
		{
			$selecteurInfo['hour'] = Date::getHour( 1, 'H:00' ); // Optimisation via le Date::getHour
		}
		elseif ( $resultConfigDash['sds_ta'] == 'week' ) // Si on est sur la TA week on change le format de la date
		{
			$selecteurInfo['date'] = str_replace('/','-',$selecteurInfo['date']);
		}
		
		// Si on a un sort by
		if ( $resultConfigDash['sds_sort_by'] != 'none' )
		{
			$tmpSortBy = explode('@', $resultConfigDash['sds_sort_by']);
			$queryIdGTMSortBy = "
				SELECT
					b.id_page
				FROM
					sys_pauto_config AS a,
					sys_pauto_config AS b
				WHERE
					a.id_page = '{$idDash}'
					AND a.id_elem = b.id_page
					AND b.class_object = '{$tmpSortBy[0]}'
					AND b.id_elem = '{$tmpSortBy[1]}'
					AND b.id_product = '{$tmpSortBy[2]}'
				";
			
			$tmpSortBy[0] = ($tmpSortBy[0] == 'counter' ? 'raw' : 'kpi');
			$tmpSortBy[3] = $db_connec->getOne($queryIdGTMSortBy);
			
			$selecteurInfo['sort_by'] = implode('@', $tmpSortBy);
			$selecteurInfo['order'] = $resultConfigDash['sds_order'];
		}
		// Si on a un filtre
		if ( !empty($resultConfigDash['sds_filter_id']) )
		{
			$tmpFilter = explode('@', $resultConfigDash['sds_filter_id']);
			$queryIdGTMFilter = "
				SELECT
					b.id_page
				FROM
					sys_pauto_config AS a,
					sys_pauto_config AS b
				WHERE
					a.id_page = '{$idDash}'
					AND a.id_elem = b.id_page
					AND b.class_object = '{$tmpFilter[0]}'
					AND b.id_elem = '{$tmpFilter[1]}'
					AND b.id_product = '{$tmpFilter[2]}'
				";
			
			$tmpFilter[0] = ($tmpFilter[0] == 'counter' ? 'raw' : 'kpi');
			$tmpFilter[3] = $db_connec->getOne($queryIdGTMFilter);
			
			$selecteurInfo['filter_id'] = implode('@', $tmpFilter);
			$selecteurInfo['filter_operande'] = $resultConfigDash['sds_filter_operande'];
			$selecteurInfo['filter_value'] = $resultConfigDash['sds_filter_value'];		
		}
		
		$selecteur = new SelecteurDashboard($idDash, 'overtime');
		// Ajout de la configuration du sélecteur définie par l'utilisateur concernant le dashboard qu'il a choisit
		$selecteur->getSelecteurFromArray($selecteurInfo);
		// Sauvegarde des paramètres en session
		$selecteur->saveToSession();
		
		return;
			
		// Récupère la famille
		$query_family = "SELECT family FROM sys_pauto_page_name WHERE id_page = ".$idDash;
		$result_family = $this->sql($query_family);
		list($family) = pg_fetch_array($result_family);
		
		if ( $this->type == 'external' ) {
			// Récupère la ganularité temporelle la plus petite
			$ta = get_ta_min();
			if ( $ta == 'day' )
				$ta_value = substr($this->ta, 0, 8);
			else 
			{
				$ta_value =	$this->ta;
	
				if ( strlen($this->ta) == 8 ){

					// 18/04/2008 - Modif. benoit : correction du bug 6387. Si l'heure n'est pas présente dans la valeur de la ta on bascule en day au lieu de positionner arbitrairement la valeur de l'heure à 23

					//$ta_value .= '23';
					$ta = "day";
				}
			}
		}
		else {
			$ta = $this->ta;
			$ta_value = $this->ta_value;
		}
		
		// Récupère les propriétés du sélecteur
		$query = "
				SELECT properties, default_value, type, selection_sql, id_selecteur, visible, parameter_order, page_mode, libelle
				FROM sys_selecteur_properties, sys_object_selecteur
				WHERE id_selecteur = object_id
					AND family = '".$family."'
				ORDER BY parameter_order
				";

		$result = $this->sql($query);
		$row = pg_fetch_array($result);
		
		$_SESSION['sys_user_parameter_session']['commune']['commun'][0]['parameter_name']   = $ta;
		$_SESSION['sys_user_parameter_session']['commune']['commun'][0]['parameter_value']  = $ta_value;
		$_SESSION['sys_user_parameter_session']['commune']['commun'][0]['parameter_type']   = $row['type'];
		$_SESSION['sys_user_parameter_session']['commune']['commun'][0]['parameter_sql']    = $row['selection_sql'];
		$_SESSION['sys_user_parameter_session']['commune']['commun'][0]['parameter_parent'] = '';
		$_SESSION['sys_user_parameter_session']['commune']['commun'][0]['parameter_order']  = $row['parameter_order'];
		$_SESSION['sys_user_parameter_session']['commune']['commun'][0]['active']           = $row['visible'];
		$_SESSION['sys_user_parameter_session']['commune']['commun'][0]['libelle']          = $row['libelle'];

		// 21/03/2008 - Modif. benoit : correction du bug 4864. Ajout de la mise en session du champ "period" dans la section ['commune']['commun'] de '$sys_user_parameter_session'

		$row = pg_fetch_array($result);

		$_SESSION['sys_user_parameter_session']['commune']['commun'][1]['parameter_name']   = $row['properties'];
		$_SESSION['sys_user_parameter_session']['commune']['commun'][1]['parameter_value']  = $row['default_value'];
		$_SESSION['sys_user_parameter_session']['commune']['commun'][1]['parameter_type']   = $row['type'];
		$_SESSION['sys_user_parameter_session']['commune']['commun'][1]['parameter_sql']    = $row['selection_sql'];
		$_SESSION['sys_user_parameter_session']['commune']['commun'][1]['parameter_parent'] = '';
		$_SESSION['sys_user_parameter_session']['commune']['commun'][1]['parameter_order']  = $row['parameter_order'];
		$_SESSION['sys_user_parameter_session']['commune']['commun'][1]['active']           = $row['visible'];
		$_SESSION['sys_user_parameter_session']['commune']['commun'][1]['libelle']          = $row['libelle'];

		// 21/03/2008 - Modif. benoit : correction du bug 4864. Suite à la précédente correction, la boucle démarre à l'index 2 et non plus 1
	
		for ( $i = 1; $i < pg_numrows($result); $i++ ) {
			$row = pg_fetch_array($result,$i);
			$_SESSION['sys_user_parameter_session'][$family]['dashboard'][$i-1]['parameter_name']   = $row['properties'];
			$_SESSION['sys_user_parameter_session'][$family]['dashboard'][$i-1]['parameter_value']  = $row['default_value'];
			$_SESSION['sys_user_parameter_session'][$family]['dashboard'][$i-1]['parameter_type']   = $row['type'];
			$_SESSION['sys_user_parameter_session'][$family]['dashboard'][$i-1]['parameter_sql']    = $row['selection_sql'];
			$_SESSION['sys_user_parameter_session'][$family]['dashboard'][$i-1]['parameter_parent'] = '';
			$_SESSION['sys_user_parameter_session'][$family]['dashboard'][$i-1]['parameter_order']  = $row['parameter_order'];
			$_SESSION['sys_user_parameter_session'][$family]['dashboard'][$i-1]['active']           = $row['visible'];
			$_SESSION['sys_user_parameter_session'][$family]['dashboard'][$i-1]['libelle']          = $row['libelle'];		
		}

		// 21/03/2008 - Modif. benoit : correction du bug 4864. Suite à la précédente correction, incrementation de l'index pour récuperer les données de la na

		//$na_selecteur = $_SESSION['sys_user_parameter_session'][$family]['dashboard'][1]['parameter_value'];

		// 22/05/2008 - Modif. benoit : correction des bugs 6686/6688/6388/6759. Dans le cas d'un lien externe, l'element du tableau de session 'sys_user_parameter_session' n'a pas le même index que pour les liens internes

		if ($this->type == 'external') 
		{
			$na_selecteur = $_SESSION['sys_user_parameter_session'][$family]['dashboard'][0]['parameter_value'];
		}
		else 
		{
			$na_selecteur = $_SESSION['sys_user_parameter_session'][$family]['dashboard'][2]['parameter_value'];
		}
			
		$na_selecteur = explode('@', $na_selecteur);
		
		// 23/05/2008 - Modif. benoit : correction des bugs 6686/6688/6388/6759. Dans le cas des liens externes, on ne positionne pas obligatoirement la na au niveau minimum mais on laisse celle passée en parametre

		// 23/05/2008 - Modif. benoit : correction des bugs 6686/6688/6388/6759. On vérifie que la na appartient bien aux na de la famille avant de placer sa valeur dans le selecteur
		$na_lst = getNaLabelList( "na",$family );
		$na_in_family = in_array($this->na, array_keys($na_lst[$family]) );

		/*if ( $this->type == 'external' ){
			$na_selecteur[0] = get_network_aggregation_min_from_family($family); // On arrive toujours sur le niveau minimum
		}
		else 
		{*/
			if($na_in_family) $na_selecteur[0] = $this->na;
		//}

		// 22/05/2008 - Modif. benoit : correction des bugs 6686/6688/6388/6759. Dans le cas d'un lien externe, l'element du tableau de session 'sys_user_parameter_session' n'a pas le même index que pour les liens internes

		//$_SESSION['sys_user_parameter_session'][$family]['dashboard'][1]['parameter_value'] = implode('@', $na_selecteur);
		//$_SESSION['sys_user_parameter_session'][$family]['dashboard'][2]['parameter_value'] = implode('@', $na_selecteur);

		if ($this->type == 'external') 
		{
			$_SESSION['sys_user_parameter_session'][$family]['dashboard'][0]['parameter_value'] = implode('@', $na_selecteur);
			// maj 17/07/2008 BBX : affectation du na sélectionné BZ7124
			$_SESSION['sys_user_parameter_session'][$family]['dashboard'][1]['parameter_value'] = implode('@', $na_selecteur);
		}
		else 
		{
			$_SESSION['sys_user_parameter_session'][$family]['dashboard'][2]['parameter_value'] = implode('@', $na_selecteur);
		}
/*
		echo '<pre>';
		print_r($_SESSION['sys_user_parameter_session']);
		echo '</pre>';*/
		
		// Ajout en session l'élément réseau qui a été précisé dans le lien
		
		// 23/05/2008 - Modif. benoit : correction des bugs 6686/6688/6388/6759. On ne sauvegarde la valeur de la na dans les preferences que si la na appartient à la famille en cours

		if ($na_in_family)
		{
			$query_obj_ref = "SELECT object_ref_table FROM sys_definition_categorie WHERE family = '".$family."'";
			$result_obj_ref =  $this->sql($query_obj_ref);
			list($table_ref) = pg_fetch_row($result_obj_ref);
			
			if ( $this->type == 'external' ) {
				$query_na = "SELECT ".$this->na." FROM ".$table_ref." WHERE ".$this->na."_label = '".$this->na_value."'";
				$result_na =  $this->sql($query_na);
				list($na) = pg_fetch_row($result_na);
				$_SESSION['network_element_preferences'] = $this->na.'@'.$na.'@'.$this->na_value;
			}
			else {
				$query_na = "SELECT ".$this->na."_label FROM ".$table_ref." WHERE ".$this->na." = '".$this->na_value."'";
				$result_na =  $this->sql($query_na);
				list($na_value_label) = pg_fetch_row($result_na);
				$_SESSION['network_element_preferences'] = $this->na.'@'.$this->na_value.'@'.$na_value_label;
			}
		}
		
		// Si c'est une famille avec un troisième axe et que l'élément troisième a été précisé
		// on le met en session aussi
		if ( get_axe3($family) ) {

			// 23/05/2008 - Modif. benoit : correction des bugs 6686/6688/6388/6759. On vérifie que la na d'axe3 appartient bien à la famille avant de mettre à jour sa valeur dans le tableau de session de parametres utilisateur

			$list_na_axe3 = array();

			$sql = "SELECT DISTINCT agregation, agregation_rank FROM sys_definition_network_agregation WHERE family='".$family."' AND axe = 3 ORDER BY agregation_rank ASC";

			$req = pg_query($this->db_connec, $sql);

			while ($row = pg_fetch_array($req)) {		
				$list_na_axe3[] = $row['agregation'];
			}

			if ( isset($this->na_axe3) && (in_array($this->na_axe3, $list_na_axe3)))
			{
				$query_na_axe3 = "SELECT ".$this->na_axe3." FROM ".$table_ref." WHERE ".$this->na_axe3."_label = '".$this->na_axe3_value."'";
				$result_na_axe3 =  $this->sql($query_na_axe3);
				list($na_axe3) = pg_fetch_row($result_na_axe3);

				// 22/05/2008 - Modif. benoit : correction des bugs 6686/6688/6388/6759. Dans le cas d'un lien externe, l'element du tableau de session 'sys_user_parameter_session' n'a pas le même index que pour les liens internes

				//$_SESSION['sys_user_parameter_session'][$family]['dashboard'][3]['parameter_value'] = $this->na_axe3.'@'.$na_axe3;
				//$_SESSION['sys_user_parameter_session'][$family]['dashboard'][4]['parameter_value'] = $this->na_axe3.'@'.$na_axe3;
				
				if ($this->type == 'external') 
				{
					$_SESSION['sys_user_parameter_session'][$family]['dashboard'][3]['parameter_value'] = $this->na_axe3.'@'.$na_axe3;
				}
				else 
				{
					$_SESSION['sys_user_parameter_session'][$family]['dashboard'][4]['parameter_value'] = $this->na_axe3.'@'.$na_axe3;
				}
								
				if ( $this->debug ) {
					__debug($_SESSION['sys_user_parameter_session'], '$_SESSION[sys_user_parameter_session]', 2);
				}
			}
		}
	} // End function loadSelecteurInSession
	
	/**
	 * Vérification de la présence du paramètre from
	 * 
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
	 */
	private function checkParam_from () {
		try {
			$this->from = $this->getParam('from', true);
		}
		catch ( Exception $e ) {}
	} // End function checkParam_from
	
	/**
	 * Vérification de la présence du paramètre ta et de sa valeur
	 * 
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
	 */
	private function checkParam_ta () {
		try {
			if ( $this->type == 'external' ) {// Si c'est un lien externe seul le paramètre ta est précisé
				$ta = $this->getParam('ta');
				if ( strlen($ta) < 8 ) // format inconnu
					throw new Exception(__T('U_E_EXTERNAL_LINK_PARAM_TA_INVALID', $ta));
				
				$y = substr($ta, 0, 4);
				$m = substr($ta, 4, 2);
				$d = substr($ta, 6, 2);
				
				if ( !checkdate($m, $d, $y) ) // format YYYYMMDD invalide
					throw new Exception(__T('U_E_EXTERNAL_LINK_PARAM_TA_INVALID', $ta));
					
				$h = 1;
				if( strlen($ta) > 8 )
					$h = substr($ta, 8, 2);
				
				if ( $h < 0 || $h > 23 ) // format YYYYMMDDHH invalide
					throw new Exception(__T('U_E_EXTERNAL_LINK_PARAM_TA_INVALID', $ta));
				
				$this->ta = substr($ta, 0, 10);
			}
			else 
			{
				$ta = $this->getParam('ta');
				if ( !in_array($ta, array('hour', 'day', 'day_bh', 'week', 'week_bh', 'month', 'month_bh')) )
					throw new Exception(__T('U_E_EXTERNAL_LINK_PARAM_TA_INVALID', $ta));
				
				$ta_value = $this->getParam('ta_value');
				
				// 19/06/2008 - Modif. benoit : correction du bug 6940. Depuis les liens internes, la valeur de la ta peut être de plusieurs types. On ne vérifie donc pas qu'elle est de format YYYYMMDD
				
				/*if ( strlen($ta_value) < 8 ) // format inconnu
					throw new Exception(__T('U_E_EXTERNAL_LINK_PARAM_TA_INVALID', $ta_value));
					
				$y = substr($ta_value, 0, 4);
				$m = substr($ta_value, 4, 2);
				$d = substr($ta_value, 6, 2);
				
				if ( !checkdate($m, $d, $y) ) // format YYYYMMDD invalide
					throw new Exception(__T('U_E_EXTERNAL_LINK_PARAM_TA_INVALID', $ta_value));
					
				$h = 1;
				if( strlen($ta_value) > 8 )
					$h = substr($ta, 8, 2);
				
				if ( $h < 0 || $h > 23 ) // format YYYYMMDDHH invalide
					throw new Exception(__T('U_E_EXTERNAL_LINK_PARAM_TA_INVALID', $ta_value));
					
				$this->ta = $ta;
				$this->ta_value = substr($ta_value, 0, 10);*/

				$this->ta = $ta;
				$this->ta_value = $ta_value;
			}
		}
		catch ( Exception $e ) {
			$this->addError($e->getMessage());
		}
	} // End function checkParam_ta
	
	/**
	 * Vérification sur la présence des paramètres na et na_value et de leur valeur
	 * 
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
	 */
	private function checkParam_na () {
		try {
			// Verification du paramètre : na
			// maj 17/07/2008 BBX : on force la NA en minuscules BZ 7129
			$na = strtolower($this->getParam('na'));

			// 19/06/2008 - Modif. benoit : correction du bug 6937. Suivant le type du lien, on choisie la table de référence soit par rapport à l'id de l'alarme (lien interne) ou via la na (lien externe)

			if ($this->type == "internal") 
			{		
				$query = " 
						SELECT 
							*
						FROM
							sys_definition_network_agregation AS sdna,
							{$this->alarm_table} AS sda
						WHERE 
							sda.alarm_id = '{$this->alarm_id}'
							AND sdna.family = sda.family
							AND sdna.agregation = '{$na}'
					";
				
				$this->db_connec->execute($query);
			
				if ( $this->db_connec->getNumRows() == 0 )
					throw new Exception(__T('U_E_EXTERNAL_LINK_PARAM_NA_INVALID', $na));
			}
			else 
			{
				$id_dash = $this->getIdDashboard();
				if(!empty($id_dash))
				{
					$dashModel = new DashboardModel($id_dash);
					$naLevelsInCommon = $dashModel->getNALevelsInCommon(1);
					
					if ( !in_array($na, $naLevelsInCommon) )
					{
						throw new Exception(__T('U_E_EXTERNAL_LINK_PARAM_NA_INVALID', $na));
					}
				}
				else
				{
					$query = "
						SELECT
							*
						FROM 
							sys_definition_network_agregation 
						WHERE 
							agregation = '{$na}' 
							AND agregation_level = 1
					";
					
					$this->db_connec->execute($query);
			
					if ( $this->db_connec->getNumRows() == 0 )
						throw new Exception(__T('U_E_EXTERNAL_LINK_PARAM_NA_INVALID', $na));
				}
			}
			
			// Verification du paramètre : na_value
			$na_value = $this->getParam('na_value');
			
			/*
			$isOk = false;

			while ( $o = pg_fetch_object($result) ) {
				$query = "SELECT eor_id, eor_label FROM edw_object_ref WHERE eor_obj_type = '{$na}' AND eor_id = '{$na_value}'";
				$result =  $this->sql($query);
				if ( pg_num_rows($result) > 0 ) {
					$isOk = true;
					break;
				}
			}
			
			if ( $isOk === false )
				throw new Exception(__T('U_E_EXTERNAL_LINK_PARAM_NA_VALUE_INVALID', $na_value));
			*/
			
			$this->na = $na;
			$this->na_value = $na_value;
		}
		catch ( Exception $e ) {
			$this->addError($e->getMessage());
		}
	} // End function checkParam_na
	
	/**
	 * Vérification sur la présence des paramètres na_axe3 et na_axe3_value et de leur valeur
	 * si le paramètre na_axe3 n'est pas présent, le paramètre na_axe3_value sera ignoré (car ces paramètres sont optionnels)
	 * 
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
	 */
	private function checkParam_na_axe3 () {
		try {
			$na_axe3 = $this->getParam('na_axe3');
		}
		catch ( Exception $e ) {
			return;
		}

		try 
		{
			// Vérifie si le niveau d'aggrégation troisième axe existe
			
			// 19/06/2008 - Modif. benoit : correction du bug 6937. Suivant le type du lien, on choisie la table de référence soit par rapport à l'id de l'alarme (lien interne) ou via la na (lien externe)

			if ($this->type == "internal") 
			{		
				$query = " 
						SELECT 
							*
						FROM
							sys_definition_network_agregation AS sdna,
							{$this->alarm_table} AS sda
						WHERE 
							sda.alarm_id = '{$this->alarm_id}'
							AND sdna.family = sda.family
							AND sdna.agregation = '{$na_axe3}'
					";
			}
			else 
			{
				$query = "
						SELECT
							*
						FROM 
							sys_definition_network_agregation 
						WHERE 
							agregation = '{$na_axe3}'
							AND axe = 3
					";
			}
			
			$this->db_connec->execute($query);
			
			if ( $this->db_connec->getNumRows() == 0 )
				throw new Exception(__T('U_E_EXTERNAL_LINK_PARAM_NA_AXE3_VALUE_INVALID', $na_axe3));
			
			// Vérification de la valeur du troisième axe
			$na_axe3_value = $this->getParam('na_axe3_value');
			
			/*
			$isOk = false;
			$l = '';
			if ( $this->type == 'external' )
				$l = '_label';
			
			while ( $o = pg_fetch_object($result) ) {
				$query = "SELECT ".$this->na_axe3." FROM ".$o->object_ref_table." WHERE ".$this->na_axe3.$l." = '".$na_axe3_value."'";
				$result = $this->sql($query);
				if ( pg_num_rows($result) > 0 ) {
					$isOk = true;
					break;
				}
			}
			if ( $isOk === false ) {
				unset($this->na_axe3);
				throw new Exception(__T('U_E_EXTERNAL_LINK_PARAM_NA_AXE3_VALUE_INVALID', $na_axe3_value));
			}*/
			
			$this->na_axe3 = $na_axe3;
			$this->na_axe3_value = $na_axe3_value;
		}
		catch ( Exception $e ) {
			$this->addError($e->getMessage());
		}
	} // End function checkParam_na_axe3
	
	/**
	 * Vérification sur la présence du paramètre com et sa valeur
	 * Paramètre optionnel, ne retourne pas d'erreur s'il n'est pas présent
	 * 
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
	 */
	private function checkParam_com () {
		try {
			$this->com = $this->getParam('com', true);
		}
		catch ( Exception $e ) {
			return;
		}
		
		try {
			// Vérification que le type de com existe
			if ( is_numeric($this->com) ) {
				$query = "
					SELECT t1.id_page, t1.family, t1.page_name 
					FROM sys_aa_column_code t0, sys_pauto_page_name t1
					WHERE t0.saacc_dashboard_id_page = t1.id_page
						AND t0.saacc_aacode = '".$this->com."'";
			}
			else {
				$query = "
					SELECT t1.id_page, t1.family, t1.page_name 
					FROM sys_aa_column_code t0, sys_pauto_page_name t1
					WHERE t0.saacc_dashboard_id_page = t1.id_page
						AND t0.saacc_fc_label = '".$this->com."'";
			}
			
			$this->db_connec->execute($query);
			if ( $this->db_connec->getNumRows() == 0 ){

				// 18/04/2008 - Modif. benoit : correction du bug 6388. Si le com est invalide, on genere une nouvelle exception

				$com_in_msg = $this->com;
								
				unset($this->com);

				throw new Exception(__T('U_E_EXTERNAL_LINK_INVALID_COM', $com_in_msg));
			}
			
			if ( $this->debug )
				echo '<br />$this->com : '.$this->com.'<br />';
		}
		catch ( Exception $e ) {
			$this->addError($e->getMessage());
		}
	} // End function checkParam_com
	
	/**
	 * Vérification sur la présence du paramètre alarm_type, alarm_id et leurs valeurs
	 * 
	 * 	27/08/2009 GHX
	 *		- L'ID d'une alarme est maintenant une chaine de caractère => Modif SQL
	 * 
	 * @since cb4.0.0.00
	 * @version cb5.0.0.06
	 */
	private function checkParam_alarm () {
		try {
			// Vérification du type de l'alarme
			$alarm_type = strtolower($this->getParam('alarm_type'));
			
			if ( $alarm_type == 'static' )
				$table = 'sys_definition_alarm_static';
			elseif ( $alarm_type == 'dyn_alarm' )
				$table = 'sys_definition_alarm_dynamic';
			elseif ( $alarm_type == 'top-worst' )
				$table = 'sys_definition_alarm_top_worst';
			else
				throw new Exception(__T('U_E_EXTERNAL_LINK_PARAM_ALARM_TYPE_INVALID', $alarm_type));

			// 19/06/2008 - Modif. benoit : correction du bug 6937. Ajout de la variable de classe '$this->alarm_table' réutilisée lors du check de la valeur de la na

			$this->alarm_table = $table;
			
			// Vérification que l'identifiant est bon
			$alarm_id = $this->getParam('alarm_id');
			
			// 09:19 27/08/2009 GHX
			// Modification SQL pour mettre l'ID de l'alarme entre cote
			$query = "SELECT * FROM ".$this->alarm_table." WHERE alarm_id = '".$alarm_id."'";
			$this->db_connec->execute($query);
			if ( $this->db_connec->getNumRows() == 0 )
				throw new Exception(__T('U_E_EXTERNAL_LINK_PARAM_ALARM_ID_NOT_EXIST', $alarm_id));
			
			$this->alarm_type  = $alarm_type;
			$this->alarm_id = $alarm_id;
		}
		catch ( Exception $e ) {
			$this->addError($e->getMessage());
		}
	} // End function checkParam_alarm
	
	/**
	 * Vérifie l'ID du produit passé dans l'URL, s'il n'existe pas on considère qu'on est sur le produit courant donc par défaut le master.
	 * Si c'est pas le cas et qu'on se retrouve sur un slave, on redirige l'utilisateur sur le master en ajoutant l'ID du slave dans l'URL.
	 *
	 * Dans tous les cas si l'ID n'existe pas on affiche une erreur (si on est sur le slave, on redirige d'abord vers le master)
	 *
	 * @version CB 5.0.0.06
	 * @since CB 5.0.0.06
	 */
	private function checkParam_product ()
	{
		// On vérifie qu'on est sur le master
		$products = getProductInformations();
		$infoMaster = null;
		$infoProductCurrent = null;
		$isMaster = true;
		foreach ($products as $product)
		{
			if ($product['sdp_master'] == 1)
			{
				// Récupère les infos du master
				$infoMaster = $product;
				if ($product['sdp_directory'] != trim(NIVEAU_0,'/'))
				{	// on est sur un autre produit que le master ?
					$isMaster = false;
				}
			}
			elseif ($product['sdp_directory'] == trim(NIVEAU_0,'/'))
			{
				$infoProductCurrent = $product;
			}
		}
		
		// Si on est sur le master on peut vérifier l'ID du produit sinon on redirige vers le master
		if ( $isMaster )
		{
			$this->product = $infoMaster['sdp_id'];
			
			try
			{
				$product = $this->getParam('product');
			}
			catch ( Exception $e )
			{
				return;
			}
			
			if ( !empty($product) )
			{
				$this->db_connec->execute("SELECT * FROM sys_definition_product WHERE sdp_id = {$product}");
				
				if ( $this->db_connec->getNumRows() == 0 )
				{
					$this->addError(__T('U_E_EXTERNAL_LINK_PRODUCT_NOT_EXISTS'));
					return;
				}
				$this->product = $product;
			}
		}
		else
		{
			// Récupère l'ID du produit à mettre dans l'URL
			try
			{
				$_GET['product'] = $this->getParam('product');
			}
			catch ( Exception $e )
			{
				$_GET['product'] =  $infoProductCurrent['sdp_id'];
			}
			
			// Construction de l'url de redirection
			$url = 'http'.( isset($_SERVER["HTTPS"]) ? 's' : '').'://'.$infoMaster["sdp_ip_address"].'/'.$infoMaster["sdp_directory"].'/?';
			$url .= http_build_query($_GET);
			
			// Redirection vers le master
			if ( headers_sent() )
			{
				header('Location:'.$url);
			}
			else
			{
				echo '<script>parent.top.location.href = "'.$url.'";</script>';
			}
			exit;
		}
	} // End function checkParam_product
	
	/**
	 * Ajouter une erreur
	 * 
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
	 * @param string $txt : message d'erreur
	 */
	private function addError ( $txt ) {
		array_push($this->errors, $txt);
	} // End function addError
	
	/**
	 * Renvoie la valeur du paramètre si aucun paramètre n'est trouvé une exception est levée
	 * 
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
	 * @param string $name : nom du paramètre
	 * @param boolean [optionnal] $empty : vrai si le paramètre peut-être vide [false par défaut]
	 * @return string 
	 */
	private function getParam ( $name, $empty = false ) {
		if ( !isset($this->request[$name] ) )
			throw new Exception(__T('U_E_EXTERNAL_LINK_PARAM_NOT_EXIST', $name));
		if ( $empty === false && empty($this->request[$name]) )
			throw new Exception(__T('U_E_EXTERNAL_LINK_PARAM_EMPTY', $name));
		return $this->request[$name];
	} // End function getParam
	
	/**
	 * Exécute une requête et retourne le résultat
	 * 
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
	 * @param string $query : requète à exécuter
	 * @return string  
	 */
	private function sql ( $query ) {
		$result = @pg_query($this->db_connec, $query);
		if ( $this->debug ) {
			$_ = debug_backtrace();
			$f = null;
			while ( $d = array_pop($_) ) {
				if ( (strtolower($d['function']) == 'sql') ) break;
				$f = $d;
			}
			echo '<br /><u><b>function : '.$f['function']. ' [line '.$d['line'].']</b></u><br /><u>$query :</u><pre>'.str_replace(array("\t", "<", ">"), array('', '&lt;','&gt;'), $query).'</pre>';
			if ( !$result ) echo '<span style="color:red">'.pg_last_error().'</span><br />';
			else echo '<u>num_rows :</u> <code>'.pg_num_rows($result).'</code><br />';
		}
		
		return $result;
	} // End function sql
	
	/**
	 * 
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
	 * @param string $k
	 * @param mixed $v
	 */
	private function __set ( $k, $v ) {
		if ( in_array($k, $this->allParameters) )
			$this->params->offsetSet($k, $v);
		else
			$this->$k = $v;
	} // End function __set
	
	/**
	 * 
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
	 * @param string $k
	 * @return mixed $v
	 */
	private function __get ( $k ) {
		if ( in_array($k, $this->allParameters) )
			return $this->params->offsetGet($k);
		else
			return $this->$k;
	} // End function __get
	
	/**
	 * 
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
	 * @param string $k
	 * @return boolean
	 */
	private function __isset ( $k ) {
		if ( in_array($k, $this->allParameters) )
			return $this->params->offsetExists($k);
		else
			return isset($this->$k);
	} // End function __get
	
	/**
	 * 
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
	 * @param string $k
	 */
	private function __unset ( $k ) {
		if ( in_array($k, $this->allParameters) )
			$this->params->offsetUnset($k);
		else
			unset($this->$k);
	} // End function __get
} // End class ExternalLink
?>