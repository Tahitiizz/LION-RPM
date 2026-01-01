<?php
/*
	20/01/2009 GHX
		modification de la fonction getAllDashboard()
	25/03/2009 GHX
		modification de la fonction getAllDashboard() pour lui ajouter un paramètre
	19/05/2009 GHX
		- Ajout de la fonction allGtmAreSameFamily()
	25/05/2009 GHX
		- Ajout d'un paramètre à la fonction allGtmAreSameFamily()
	04/06/2009 GHX
		- Ajout de la fonction getNALevelsInCommon()*
	10/06/2009 GHX
		- Prise en compte d'un deuxieme paramètre dans le constructeur BZ9841
	26/06/2009 GHX
		- Modification d'une condition dans la fonction getNaPaths()
	23/07/2009 GHX
		- Ajout du ORDER BY dans le SQL qui récupère la liste des GTMs
	29/07/2009 GHX
		- Correction d'un bug dans la fonction getNaPaths() BZ 10439
	18/08/2009 GHX
		- Modification de la fonction getDashboardFromGTM()
		- Ajout de la fonction delete()
	28/10/2009 GHX
		- Ajout d'un paramètre au constructeur
		- Modification de la fonction delete() pour passer l'id produit à certaines fonctions
	09/11/2009 GHX
		- Ajout de divers fonctions
	17/11/2009 GHX
		- Modification d'une condition dans la fonction duplicate() et de 2 variables qui n'étaient pas bonnes (leur nom)
	09/12/2009 GHX
		- Correction du BZ 12638  [REC][T&A Cigale GSM][NAVIGATION]: pas de navigation possible MSC vers LAC
			-< Modification de la fonction getNaPaths()
	05/03/2010 BBX
		- Ajout de la méthode "manageConnections"
		- Utilisation de la méthode "manageConnections" au lieu de DatabaseConnection afin d'éviter les instances redondantes
	08/03/2010 BBX
		- Suppression de la méthode "manageConnections"
		- Utilisation de la méthode "Database::getConnection" à la place.
    20/01/2011 OJT : Ajout de la méthode getDashboardProducts
 * 06/06/2011 MMT DE 3rd axis : ajout de la gestion du 3eme axe dans plusieurs fonctions
 * 07/07/2011 NSE bz 22888 : dans getNaPaths(), on passe la liste des produits du Dash à getPathNetworkAggregation() pour ne récupérer que les arcs existants sur le produit
*/
?>
<?php
/**
*	Classe permettant de manipuler/récupérer les données d'un dashboard
*
*	@author	BBX - 18/11/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*
*
*/

/**
 *
	- maj 16/12/2008, benoit : ajout du paramètre 'gtm_id' à la méthode 'getGtms()'
 *
 */

class DashboardModel
{
	/**
	* Propriétés
	*/
	private $idDashboard = 0;
	private $idProduct = '';
	private $database = null;
	private $dashboardValues = Array();
	private $error = false;
	// Mémorise les instances de connexions ouvertes
	private static $connections = Array();

	/**
	 * Constructeur
	 *
	 *	09:14 28/10/2009 GHX
	 *		- Ajout du troisieme paramètre
	 *
	 * @param : int	id dashboard	<optional>
	 * @param string $mode mode du dashboard (default overtime)
	 * @param int $idProduit identifiant du produit sur lequel on doit se connecter (defaut le master produit)
	 */
	public function __construct($idDashboard, $mode = 'overtime', $idProduct = '')
	{
		// Sauvegarde de l'id dashbord
		$this->idDashboard = $idDashboard;
		$this->mode = $mode;
		// Connexion à la base de données
		$this->idProduct = $idProduct;
		$this->database = Database::getConnection($idProduct);
		// Récupération des valeurs par défaut du dashboard
		if($idDashboard != "") {

			// 28/01/2009 - Modif. benoit : ajout des informations "id_elem", "id_product" et "id_page" au résultat de la requete et au champ "sort_by" du tableau des valeurs du dashboard

			// 16:53 04/11/2009 GHX
			// Ajout de colonnes dans le select
			$sql = "
				SELECT
					sppn.page_name,
					sppn.droit,
					sppn.id_user,
					sppn.share_it,
					d.sdd_sort_by_id,
					d.sdd_sort_by_order,
					d.sdd_mode,
					d.sdd_selecteur_default_period,
					d.sdd_selecteur_default_top_overnetwork,
					d.sdd_selecteur_default_top_overtime,
					d.sdd_selecteur_default_na,
					d.sdd_selecteur_default_na_axe3,
					d.sdd_id_menu,
					c.class_object,
					c.id_elem,
					c.id_product,
					c.id_page
				FROM
					sys_pauto_page_name as sppn
					LEFT JOIN sys_definition_dashboard d ON (sppn.id_page = sdd_id_page)
					LEFT JOIN sys_pauto_config c ON d.sdd_sort_by_id = c.id
				WHERE
					sdd_id_page = '".$this->idDashboard."'
				";

			$row = $this->database->getRow($sql);

			// 11/02/2009 - Modif. benoit : remplacement du type "counter" du sort_by par "raw"

			$sort_by = (($row['class_object'] == "counter") ? "raw" : $row['class_object']);
			$this->dashboardValues = Array(
									"page_name"=>$row['page_name'],
									"droit"=>$row['droit'],
									"id_user"=>$row['id_user'],
									"share_it"=>$row['share_it'],
									"period"=>$row['sdd_selecteur_default_period'],
									"top"=>(($this->mode == 'overtime') ? $row['sdd_selecteur_default_top_overtime'] : $row['sdd_selecteur_default_top_overnetwork']),
									"na_level"=>$row['sdd_selecteur_default_na'],
									"axe3"=>$row['sdd_selecteur_default_na_axe3'],
									"order"=>$row['sdd_sort_by_order'],
									"sort_by"=>$sort_by.'@'.$row['id_elem'].'@'.$row['id_product'].'@'.$row['id_page'],
									"id_menu"=>$row['sdd_id_menu'],
									"mode"=>$row['sdd_mode']
			);
			// Si les infos dashboards ne sont pas récupérées, on renvoie une erreur
			if(count($row) == 0) $this->error = true;
		}
		else {
			// Si le format de l'id est incorrect, on renvoie une erreur
			$this->error = true;
		}
	}

	/************************************************************************
	* Méthode getValues : retourne un tableau associatif contenant les paramètres du dashboard
	* @return : array	Tableau associatif
	************************************************************************/
	public function getValues()
	{
		return $this->dashboardValues;
	}

	// 16/12/2008 - Modif. benoit : ajout du paramètre 'gtm_id' à la méthode ci-dessous

	/************************************************************************
	* Méthode getGtms : récupère un tableau des id gtm du dashboard
	* @param int $gtm_id identifiant du gtm que l'on souhaite récupérer (facultatif)
	* @return : array	Tableau associatif id gtm => nom gtm
	************************************************************************/

	public function getGtms($gtm_id = '')
	{
		$array_return = Array();

		if($this->idDashboard != "")
		{
			$query =	 " SELECT s.id_elem, p.page_name"
						." FROM sys_pauto_config s, sys_pauto_page_name p"
						." WHERE s.id_elem = p.id_page AND s.id_page = '".$this->idDashboard."'"
						." AND s.class_object = 'graph'";

			if ($gtm_id != '') {
				$query .= " AND s.id_elem = '".$gtm_id."'";
			}

			// 11:48 23/07/2009 GHX
			// Ajout du ORDER BY
			$query .= " ORDER BY s.ligne ASC";

			$result = $this->database->execute($query);

			while($values = $this->database->getQueryResults($result,1)) {
			      $array_return[$values['id_elem']] = $values['page_name'];
			}
		}
		return $array_return;
	}

	/************************************************************************
	* Méthode getNALevels : récupère tous les NA commun 1er axe du dashboard
	* @return : array	tableau associatif "na_code => na_label"
	************************************************************************/
	public function getNALevels($axe = 1)
	{
		// maj 26/02/2010 - Correction du bug 13886 - Erreur lorsqu'un dash contient 3 graphes sur des familles 3 axe différentes
		// 					exemple : graphe 1 => traffic / graphe2 => qoshot / graphe 3 => qoshw
		$array_level = Array();
		$nb_gtm = 0;
		foreach($this->getGtms() as $id_gtm => $gtm_name)
		{
			$GTMModel = new GTMModel($id_gtm);
			// Récupération des na en commun par graphe
			$array_common_levels = $GTMModel->getNALevelsInCommon($axe);
			// Ce GTM n'a aucun élément commun 1er axe, on retourne un tableau vide
			if($array_common_levels === false)
			{
				return Array();
			}
			// Premier GTM, on valorise $array_level
			else
			{
				$array_level[$nb_gtm] = $array_common_levels;
			}
			// GTMs suivants, on fait l'intersection des éléments communs
			$nb_gtm++;
		}
		// Récupération des na en commun sur l'ensemble des graphes
		$array_common_levels = array();
		for($i=0;$i<$nb_gtm;$i++)
		{
			if( $i == 0 )
			{
				$array_common_levels = $array_level[$i];
			}
			else
			{
				$array_common_levels = array_intersect_assoc($array_level[$i],$array_common_levels);
			}
		}

		return $array_common_levels;
	}

	// 02/02/2009 - Modif. benoit : méthode ci-dessous dépréciée -> à supprimer

	/************************************************************************
	* Méthode getNALevelsThirdAxis : récupère tous les NA commun 3ème axe du dashboard
	* @return : array	tableau associatif "na_code => na_label"
	************************************************************************/
	/*public function getNALevelsThirdAxis()
	{
		$array_level = Array();
		foreach($this->getGtms() as $id_gtm => $gtm_name) {
			$GTMModel = new GTMModel($id_gtm);
			$array_common_levels = $GTMModel->getNALevelsInCommon(3);
			// Ce GTM n'a aucun élément commun 3ème axe, on retourne un tableau vide
			if($array_common_levels === false) {
				return Array();
			}
			// Premier GTM, on valorise $array_level
			elseif(count($array_level) == 0) {
				$array_level = $array_common_levels;
			}
			// GTMs suivants, on fait l'intersection des éléments communs
			else {
				$array_level = array_intersect_assoc($array_level,$array_common_levels);
			}
		}
		return $array_level;
	}*/

	/************************************************************************
	* Méthode getInvolvedProducts : récupère tous les produits nécessaires au dashboard
	* @return : array	tableau d'ids produits
	************************************************************************/
	public function getInvolvedProducts()
	{
		$array_retour = Array();
		foreach($this->getGtms() as $id_gtm => $gtm_name) {
			$GTMModel = new GTMModel($id_gtm);
			$array_retour = array_merge($array_retour,$GTMModel->getGTMProducts());
		}
		return array_unique($array_retour);
	}

	/************************************************************************
	* Méthode getNa2Na : récupère le tableau permettant de savoir : pour un na_level,
	* quels accordéons doivent être affichés dans le network element selecteur
	*
	* Hypothèse de départ : étant donné que l'on ne conserve que les éléments en commun
	* de tous les GTMs du dashboard, on a forcement quelquepart dans un de nos produits
	* une famille possédant tous les niveaux d'agrégation retournés par getNALevels
	*
	* On utilise dans cette méthode les fonctions "getPathNetworkAggregation" et
	* "getLevelsAgregOnLevel" de la librairie edw_function_family.
	*
	* @return : array	tableau des accordéons
	* 06/06/2011 MMT DE 3rd axis : ajout du pramaètre optionel axis, gestion du 3eme axe
	************************************************************************/
	public function getNa2Na($axe = 1)
	{
		// Récupération de nos niveaux disponibles
		//06/06/2011 MMT DE 3rd axis : gestion du 3eme axe
		$nalevels = $this->getNALevels($axe);
		// Préparation des variables id produit + connexion à la base
		$id_prod = '';
		$db_prod = null;
		// Sur quel produit allons-nous travailler ?
		foreach($this->getInvolvedProducts() as $one_prod) {
			$id_prod = $one_prod;
			$db_prod = Database::getConnection($id_prod);
			// Parcours des familles
			$query_families = "SELECT family FROM sys_definition_categorie";
			$result_families = $db_prod->execute($query_families);
			while($array_family = $db_prod->getQueryResults($result_families,1)) {
				// Sommes-nous sur la bonne famille ?
				$family = $array_family['family'];
				$query = "SELECT agregation_rank
				FROM sys_definition_network_agregation
				WHERE agregation IN ('".(implode("','",array_keys($nalevels)))."') AND family = '{$family}'";
				$result = $db_prod->getAll($query);
				// Si oui, on récupère le rank et on s'arrête là.
				if(count($result) == count(array_keys($nalevels))) {
					// On a  : le produit et la connection qu'il nous faut, on quitte :)
					break 2;
				}
			}
		}
		// Récupération des chemins agrégations
		//06/06/2011 MMT DE 3rd axis : gestion du 3eme axe
		$agreg_array = getPathNetworkAggregation($id_prod,'no',$axe,true);
                
                // 20/10/2011 BBX
                // BZ 24201 : $agreg_array doit toujours être un tableau
                if(!is_array($agreg_array))
                    $agreg_array = array();

		// On boucle maintenant sur tous les niveaux pour construire notre tableau $na2na
		$na2na = Array();
		foreach($nalevels as $level=>$label)
		{
			// Reset $temp_array
			$temp_array = Array();
			// Agrégation sur ce niveau
			$levels_agregated_on_current_level = getLevelsAgregOnLevel($level,$agreg_array);
			// Intersection entre les niveaux disponibles et les niveaux agrégés afin d'épurer le tableau des niveaux qui n'ont pas de lien avec le niveau courant
			$temp_array = array_intersect(array_keys($nalevels),$levels_agregated_on_current_level);
			// Tri sur les clés
			ksort($temp_array);
			// Sauvegarde du résultat
			$na2na[$level] = $temp_array;
		}
		// Retour du tableau
		return $na2na;
	}

	// 28/01/2009 - Modif. benoit : création de la méthode ci-dessous

	/**
	 * Retourne les chemins des na disponibles
	 *
	 *	26/06/2009 GHX
	 *		- Modif d'une condition pour éviter de prendre des mauvais chemin d'agrégatoin
	 *	29/07/2009 GHX
	 *		- Correction du BZ 10439 [REC][T&A Cb 5.0][Dashboard]: le choix d'un NA ne déselectionne pas les éléments réseaux d'un NA inférieur
         * 07/07/2011 NSE bz 22888 : on passe la liste des produits en paramètre
	 *
	 * @param int $axe valeur de l'axe des na dont on souhaite les chemins (par défaut, l'axe 1)
	 * @return array liste des chemins des na
	 */
	public function getNaPaths($axe = 1, $productTable = array())
	{
		// On récupère les na disponibles pour le dashboard
		$na_levels = array_keys($this->getNALevels($axe));

		// On récupère les chemins disponibles pour l'ensemble des na
                // 07/07/2011 NSE bz 22888 : on passe la liste des produits en paramètre
		$na_paths = getPathNetworkAggregation($productTable);
		// On définit les paths sous la forme d'un tableau : clé = na_parente -> valeur = na_descendante
		$paths = array();

		foreach ($na_paths as $family => $family_paths) {
			//06/06/2011 MMT DE 3rd axis : prise en compte du cas ou l'axe n'existe pas dans $family_paths
			if(array_key_exists($axe, $family_paths)){
				$paths_axe = $family_paths[$axe];
				foreach ($paths_axe as $na_master => $na_child) {
					// 11:27 26/06/2009 GHX
					// Ajout de la deuxieme partie de la condition
					if (in_array($na_master, $na_levels) && in_array($na_child[0], $na_levels))
					{
						// 09:07 30/07/2009 GHX
						// Correction du BZ 10439
						// Met les éléments fils dans un tableau dans le cas où on a 2 fils différents
						$paths[$na_master][]  = $na_child[0];
					}
				}
			}
		}

		// 08:41 09/12/2009 GHX
		// Correction du BZ 12638
		// Modification sur les chemins d'agrégation, on a parfois un chemin qui n'est pas possible par rapport à la config d'un dash
		//06/06/2011 MMT DE 3rd axis : gestion du 3eme axe
		$na2na = $this->getNa2Na($axe);

		// 17:06 29/07/2009 GHX
		// Correction d'un bug
		$patchsFinal = array();
		foreach ( $na_levels as $na )
		{
			if ( array_key_exists($na, $paths) )
			{
				// 29/07/2009 GHX
				// Ajout du unique
				foreach ( array_unique($paths[$na]) as $_ )
				{
					if ( in_array($na, $na2na[$_]) )
					{
						$patchsFinal[$na][] = $_;
					}
				}
			}
		}
		// <<<<<<<<<<

		return $patchsFinal;
	}

	/**
	 * Retourne les liens entre les niveaux d'agrégation
	 *
	 * @param int $axe valeur de l'axe des na dont on souhaite les chemins (par défaut, l'axe 1)
	 * @return array liste des chemins des na
	 */
	public function getNaParent($axe = 1)
	{
		// On récupère les na disponibles pour le dashboard
		$na_levels = array_keys($this->getNALevels($axe));

		// On récupère les chemins disponibles pour l'ensemble des na
		// récupère un tableau de la forme [family][num_axe][network_agregation][0][network_agregation_child]
		$na_paths = getPathNetworkAggregation();

		// On définit les paths sous la forme d'un tableau : clé = na_parente -> valeur = na_descendante
		$paths = array();

		// boucle sur toutes les familles du tableau
		foreach ($na_paths as $family => $family_paths) {

			// on ne récupère que les niveaux d'agrégation de l'axe demandé
			$paths_axe = $family_paths[$axe];

			foreach ($paths_axe as $na_master => $na_child) {
				// on vérifie que le na_master et que le na fils sont dans la liste des na disponibles pour le dashboard
				if (in_array($na_master, $na_levels) && in_array($na_child[0], $na_levels))
				{
					// Met les éléments fils dans un tableau dans le cas où on a 2 fils différents
					$paths[$na_master][]  = $na_child[0];
				}
			}
		}

		// Modification sur les chemins d'agrégation, on a parfois un chemin qui n'est pas possible par rapport à la config d'un dash
		$na2na = $this->getNa2Na();
		$patchsFinal = array();
		foreach ( $na_levels as $na ){
			if ( array_key_exists($na, $paths) ){
				foreach ( array_unique($paths[$na]) as $na_child ){
					if ( in_array($na, $na2na[$na_child]) )
						$patchsFinal[$na][] = $na_child;
				}
			}
		}

		$na_axe_paths = array();
		foreach ( $patchsFinal as $_na => $_value ){
			// Si le NA a plusieurs enfants on prend celui en commun par rapport au NA sélectionné dans le sélecteur
			if ( count($_value) != 1 )
			{
				$_valueTmp = array_intersect($_value, $na2na);

				if ( count($_valueTmp) > 0 )
					$_value = $_valueTmp;

				// Si après l'intersecte on a toujours plusieurs fils on prendra TOUJOURS le premier par ordre alphabétique (pour éviter une valeur aléatoire)
				sort($_value);
			}
			$na_axe_paths[$_na] = $_value[0];
		}

		return $na_axe_paths;
	}

	public function getNaAxeNByProduct()
	{
		$na_axeN = Array();

		$gtms = $this->getGtms();

		foreach($gtms as $id_gtm => $gtm_name)
		{
			$gtm_model = new GTMModel($id_gtm);

			$gtm_na_axeN = $gtm_model->getNAByProductsAndFamilies(3);

			foreach ($gtm_na_axeN as $id_product => $na_family)
			{
				foreach ($na_family as $family => $na)
				{
					$na_axeN[$id_product][$family] = $na;
				}
			}
		}
		return $na_axeN;
	}

	/************************************************************************
	* Méthode getTaLevels : récupère le tableau des TA levels en commun sur tous les produits concernés
	* @return : array	tableau des des TA levels (ta_name=>ta_label)
	************************************************************************/
	public function getTaLevels()
	{
		// Récupération des time agregation de tous les produits
		$ta_levels = Array();
		foreach($this->getInvolvedProducts() as $p) {
			if(count($ta_levels) == 0)
				$ta_levels = getTaLabelList($p);
			else
				$ta_levels = array_intersect_key($ta_levels,getTaLabelList($p));
		}
		return $ta_levels;
	}

	// 19/01/2009 - Modif. benoit : création de la méthode ci-dessous

	/**
	 * Renvoie le nom du dashboard
	 *
	 * @return string le nom du dashboard
	 */

	public function getName()
	{
		// 16:54 04/11/2009 GHX
		// On déjà récupéré le nom du dashboard donc plus besoin de faire un requete SQL
		// $sql =	 " SELECT page_name FROM sys_pauto_page_name WHERE id_page = '".$this->idDashboard."'";
		// $row = $this->database->getRow($sql);
		return $this->dashboardValues['page_name'];
	}

	/************************************************************************
	* Méthode getError : retourne le code d'erreur du dashbaord
	* @return : true = pas d'erreur, false = objet inutilisable
	************************************************************************/
	public function getError()
	{
		return $this->error;
	}

	/**
	 * Retourne TRUE si tous les GTM du dashboard sont de la même famille, FALSE dans le cas contraire
	 * C'est à dire que tous les RAW & KPI de tous les graphes du dashboards appartiennent à la même famille
	 *
	 *	09:25 25/05/2009 GHX
	 *		- Ajout du paramètre
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param string $idGtm identifiant d'un gtm (default null soit tous les gtm du dashboard)
	 * @return boolean
	 */
	public function allGtmAreSameFamily ( $idGtm = "" )
	{
		$listElements = array();
		$queryListElem = "
			SELECT
				CASE WHEN class_object = 'counter' THEN 'sys_field_reference' ELSE 'sys_definition_kpi' END AS table,
				id_elem,
				id_product
			FROM sys_pauto_config
			WHERE id_page = '%s'
		";

		foreach ( $this->getGtms($idGtm) as $gtmId => $gtmName )
		{
			$resultListElem = $this->database->execute(sprintf($queryListElem, $gtmId));
			if (  $this->database->getNumRows() > 0 )
			{
				while ( $row = $this->database->getQueryResults($resultListElem, 1) )
				{
					$listElements[$row['id_product']][$row['table']][] = $row['id_elem'];
				}
			}
		}

		if ( count($listElements) > 0 )
		{
			$listFamily = array();
			$queryFamily = "
				SELECT DISTINCT edw_group_table
				FROM %s
				WHERE id_ligne IN ('%s')
			";
			foreach ( $listElements as $idProduct => $elements )
			{
				$db = Database::getConnection($idProduct);
				foreach ( $elements as $table => $list )
				{
					$resultFamily = $db->execute(sprintf($queryFamily, $table, implode("','", $list)));
					if ( $db->getNumRows() != 1 )
					{
						return false;
					}
					else
					{
						while ( $row = $this->database->getQueryResults($resultFamily, 1) )
						{
							$listFamily[$row['edw_group_table']] = 1;
						}
					}
				}
			}

			if ( count($listFamily) == 1 )
				return true;
		}

		return false;
	} // End function allGtmAreSameFamily

	/**
	 * Cette méthode retourne un tableau avec la liste des NA levels en commun sinon retourn FALSE si aucun na level en commen
	 *
	 *	Pour comprendre :
	 *		- un GTM contient des éléments
	 *		- chaque élément appartient à UNE famille
	 *		- chaque famille contient un ou plusieurs niveau d'aggrégation (NA level)
	 *
	 *	Cette fonction :
	 *		-1- va chercher tous les éléments pour chaque GTM
	 *		-2- va chercher les familles de ces éléments ( = requête dans la base du produit correspondant)
	 *		-3- fait le tableau de toutes les familles différentes
	 *		-4- va chercher la liste de tous les NA levels de chaque famillle
	 *		-5- cherche l'intersection de tous les NA levels communs à toutes les familles
	 *
	 *	17:21 04/06/2009 GHX
	 *		- Ajout de la fonction
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param int axe des niveau d'agrégation à récupérer 1 ou 3 (default 1)
	 * @return array
	 */
	public function getNALevelsInCommon ( $axe = 1 )
	{
		$na_levels_in_common = array();

		foreach ( $this->getGtms() as $idGtm => $nameGtm )
		{
			$gtm = new GTMModel($idGtm);
			if ( $na = $gtm->getNALevelsInCommon($axe) )
			{
				if ( count($na_levels_in_common) == 0 )
				{
					$na_levels_in_common = $na;
				}
				else
				{
					$tmp = array_intersect($na_levels_in_common, $na);
					if ( count($tmp) == 0 )
						return false;

					$na_levels_in_common = $tmp;
				}
			}
			else
			{
				return false;
			}
		}

		if ( count($na_levels_in_common) == 0 )
			return false;

		return $na_levels_in_common;
	} // End function getNALevelsInCommon

	/**
	 * Retourne le dernier commentaire
	 *
	 * @author BBX 31/07/2009
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @return string
	 */
	public function getLastComment()
	{
		$query = "SELECT libelle_comment
		FROM edw_comment
		WHERE id_elem = '{$this->idDashboard}'
		ORDER BY id_comment DESC
		LIMIT 1";
		return $this->database->getOne($query);
	}

	/************** STATIC FUNCTIONS **************/

	/**
	 * Méthode getAllDashboard : retourne tous les dashboards de l'application
	 *
	 *	25/03/2009 GHX
	 *		- ajout du paramètre
	 *	02/11/2009 GHX
	 *		- Ajout des 2 paramètres $excludeIdProduct et $multiProducts
	 *
	 * @param int $idProduct : identifiant du produit sur lequel on doit se connecter (default : le master)
	 * @param mixed $excludeIdProduct : un ID ou une liste ID de produit à exclure (default NULL)
	 * @param boolean $multiProducts TRUE si les dashboards multiproduit des produits à exclure doivent être prise en compte si non FALSE (default TRUE)
	 * @return : array
	 */
	public static function getAllDashboard( $idProduct = "", $excludeIdProduct = null, $multiProducts = true )
	{
		$database = Database::getConnection($idProduct);
		// 20/01/2009 GHX : ajout de nouveaux champs dans le select + jointure sur la table menu_deroulant_intranet
		$query = "
			SELECT
				a.id_page,
				a.page_name,
				a.droit,
				d.sdd_sort_by_id,
				d.sdd_sort_by_order,
				d.sdd_mode,
				d.sdd_selecteur_default_period,
				d.sdd_selecteur_default_top_overnetwork,
				d.sdd_selecteur_default_top_overtime,
				d.sdd_selecteur_default_na,
				d.sdd_selecteur_default_na_axe3,
				d.sdd_id_menu
			FROM
				sys_pauto_page_name a,
				sys_definition_dashboard d
			WHERE
				a.id_page = d.sdd_id_page
				AND a.page_type = 'page'
				AND d.sdd_is_online = 1
			";

		// 17:31 02/11/2009 GHX
		// Si on a un ID de produit ou une liste de produit à exclure
		if ( $excludeIdProduct != null )
		{
			// Si c'est juste un ID on le met dans un tableau
			if ( !is_array($excludeIdProduct) )
				$excludeIdProduct = array($excludeIdProduct);

			$query .= "AND a.id_page IN (
				SELECT
					spc.id_page
				FROM
					sys_pauto_config AS spc
				WHERE
					class_object = 'graph'
					AND spc.id_elem IN (SELECT id_page FROM sys_pauto_config WHERE id_product NOT IN (".implode(',',$excludeIdProduct)."))
				GROUP BY spc.id_page
				".( $multiProducts ? '' : 'HAVING count(distinct id_product) = 1')."
				)
			";
		}

		$query .= "ORDER BY a.page_name";
		return $database->getAll($query);
	}

	/************************************************************************
	* Méthode getDashboardFromGTM : retourne l'id du dashboard contenant le GTM passé en paramètre
	*
	*	18/08/2009 GHX
	*		- Modification de la fonction car un graphe peut être présent dans plusieurs dashboards
	*
	* @author BBX 31/07/2009
	* @param int $idGTM : identifiant du GTM
	* @param int $idProduct : identifiant du produit sur lequel on doit se connecter (default : le master)
	* @return : string : id du dashboard
	************************************************************************/
	public static function getDashboardFromGTM($idGTM,$idProduct='')
	{
		$database = Database::getConnection($idProduct);
		// Requête
		$query = "SELECT id_page FROM sys_pauto_config
		WHERE id_elem = '{$idGTM}'
		AND class_object = 'graph'";
		return $database->getAll($query);
	}

	/**
     * Retourne la liste des produits associés à un Dashboard
     *
     * @since 5.0.4.14
     * @param string $idDash    Identifiant du Dashboard
     * @param int    $idProduct Identifiant du produit sur lequel requêter
     * @return array Liste d'entier représentant les produits associés au Dashboard
     */
    public static function getDashboardProducts( $idDash, $idProduct = 0 )
    {
        $retVal = array();
		$database = Database::getConnection( $idProduct );
		$query = "SELECT DISTINCT id_product
                  FROM sys_pauto_config WHERE id_page IN
                  (
                    SELECT id_elem FROM sys_pauto_config WHERE id_page = '{$idDash}'
                  );";
        foreach ( $database->getAll( $query ) as $product )
        {
            $retVal []= $product['id_product'];
        }
        return $retVal;
    }

	/**
	 * Supprime un dashboard. Retourne TRUE si la suppression a été faite sinon un message d'erreur
	 *
	 * @author GHX
	 * @version CB 5.0.0.06
	 * @since CB 5.0.0.06
	 * @return boolean
	 */
	public function delete ()
	{
		// vérife que le dashboard est supprimable (ie: il n'est pas utilisé par un report)
		$query = "SELECT id_page FROM sys_pauto_config WHERE id_elem = '{$this->idDashboard}' and class_object='report'";
		$reports = $this->database->getall($query);
		if ($reports)
			return __T('G_GDR_BUILDER_YOU_CANNOT_DELETE_THAT_DASHBOARD_AS_IT_BELONGS_TO_SOME_REPORTS');
		// on supprime le menu du dashboard s'il existait
		$myMenu_id = $this->database->getone("SELECT sdd_id_menu FROM sys_definition_dashboard WHERE sdd_id_page= '{$this->idDashboard}'");
		if ($myMenu_id)
		{
			// on supprime le menu
			// 16:12 28/10/2009 GHX
			// On passe l'id du produit
			$myMenu = new MenuModel($myMenu_id, 0, $this->idProduct);
			$myMenu->deleteMenu();
		}

		// on supprime le lien d'appartenance Graph -> Dash
		$query = "DELETE FROM sys_pauto_config WHERE id_page= '{$this->idDashboard}'";
		$this->database->execute($query);

		// Récupère l'id du menu
		$query = "SELECT sdd_id_menu FROM sys_definition_dashboard WHERE sdd_id_page = '{$this->idDashboard}'";
		$idMenu = $this->database->getOne($query);
		// Supprime le menu
		// 16:12 28/10/2009 GHX
		// On passe l'id du produit
		$menu = new MenuModel($idMenu, 0, $this->idProduct);
		$menu->deleteMenu();

		// on supprime le dashboard dans sdd
		$query = "DELETE FROM sys_definition_dashboard WHERE sdd_id_page = '{$this->idDashboard}'";
		$this->database->execute($query);

		// on supprime le dashboard
		$query = "DELETE FROM sys_pauto_page_name WHERE id_page = '{$this->idDashboard}'";
		$this->database->execute($query);

		return true;
	} // End function delete

	/**
	 * Duplique un dashboard et retourne son nouvel identifiant
	 *
	 * @author GHX
	 * @throw Exception
	 * @param string $newId : identifiant du nouveau dashboard dupliqué ou génère un identifiant aléatoire si le paramètre vaut "auto" (default auto)
	 * @param string $newIdMenu : identifiant du nouveau menu du dashboard dupliqué ou génère un identifiant aléatoire si le paramètre vaut "auto" (default auto)
	 * @param string $newName : nom du nouveau dashboard dupliqué, la chaine est évalué avec sprintf, %s répresente le nom du dashboard à dupliquer (default copy of %s)
	 * @param string $newNameMulti : nom du nouveau dashboard dupliqué dans le cas ou le premier (cf. $newName) est déjà utilisé, la chaine est évalué avec sprintf, %s répresente le nom du dashboard à dupliquer et %d représente le nombre de fois que le nom est utilisé (default copy %d of %s)
	 * @param string $droit : droit du dashboard 'client', 'customisateur' ou 'auto' si la valeur est auto le nouveau dashboard car les mêmes droit (default customisateur)
	 * @param string $idUser : si le droit du nouveau dashboard est 'client', il faut préciser l'id user (default null)
	 * @param int $online : 1 si le dashboard doit être présent en restitution sinon 0, si le nouveau dashboard est online il sera automatiquement mis dans le même menu que le dashboard à dupliquer (default 0)
	 * @param string $idMenuParent : identifiant du menu parent dans lequel on met le nouveau dashboard, par défaut il sera dans le même menu que le menu parent du dashboard à dupliquer (default null)
	 * @return string
	 */
	public function duplicate ( $newId = 'auto', $newIdMenu = 'auto', $newName = 'copy of %s', $newNameMulti = 'copy %d of %s', $droit = 'customisateur', $idUser = null, $online = 0, $idMenuParent = null )
	{
		// Si on généré un nouvel identifiant aléatoire
		if ( strtolower($newId) == 'auto' )
		{
			$newId = generateUniqId('sys_pauto_page_name');
		}
		else // Vérifie que le nouvel id passé en paramètre n'est pas déjà utilisé
		{
			// On vérifie si le nouvel ID demandé n'existe pas
			if ( $this->database->getOne("SELECT COUNT(id_page) FROM sys_pauto_page_name WHERE id_page = '".$newId."'") > 0 )
			{
				// Si oui on ne va pas plus loin
				throw new Exception("New id_page ".$newId." already exists.");
			}
		}

		// Si on généré un nouvel identifiant aléatoire
		if ( strtolower($newIdMenu) == 'auto' )
		{
			$newIdMenu = generateUniqId('menu_deroulant_intranet');
		}
		else // Vérifie que le nouvel id passé en paramètre n'est pas déjà utilisé
		{
			// On vérifie si le nouvel ID menu demandé n'existe pas
			if ( $this->database->getOne("SELECT COUNT(id_menu) FROM menu_deroulant_intranet WHERE id_menu = '".$newIdMenu."'") > 0 )
			{
				// Si oui on ne va pas plus loin
				throw new Exception("New id_menu ".$newIdMenu." already exists.");
			}
		}

		// on compose le nouveau nom
		$i = 1;
		$newName = str_replace(array("'", '"'),' ',sprintf($newName, $this->getName()));
		if ( empty($newNameMulti) )
		{
			$newNameMulti = 'copy %d of %s';
		}
		while ( $this->database->getOne("SELECT id_page FROM sys_pauto_page_name WHERE page_name='".$newName."'") )
		{
			$i++;
			$newName = str_replace(array("'", '"'),' ',sprintf($newNameMulti, $i, $this->getName()));
			if ( $i > 10 )
			{
				$newName = str_replace(array("'", '"'),' ',sprintf('copy %d of %s', $i, $this->getName()));
			}
			if ( $i >= 15 ) // Par mesure de précaution si on ne trouve pas de nom valide pour le nouveau dashboard
			{
				throw new Exception("Unable to find a new name.");
			}
		}

		if ( $droit == 'client' && ($idUser == null || empty($idUser)) )
		{
			// TODO : erreur
		}
		elseif ( strtolower($droit) == 'auto' || empty($droit) )
		{
			// si on est en mode auto, on reprend les droits du dashboard à dupliquer
			$droit = $this->dashboardValues['droit'];
		}

		/*
			Table : sys_pauto_page_name
		*/
		$querySPPN = "
			INSERT INTO sys_pauto_page_name (
				id_page,
				page_name,
				droit,
				page_type,
				id_user,
				share_it
			)
			values (
				'".$newId."',
				'".$newName."',
				'".$droit."',
				'page',
				".(empty($idUser) ? "NULL" : "'".$idUser."'").",
				0
			)";
		$this->database->execute($querySPPN);

		/*
			Table : sys_definition_dashboard
		*/
		$querySDD = "SELECT * FROM sys_definition_dashboard WHERE sdd_id_page= '".$this->idDashboard."'";
		$dash_info = $this->database->getRow($querySDD);
		$dash_info['sdd_id_page'] = $newId;
		$dash_info['sdd_id_menu'] = $newIdMenu;
		$dash_info['sdd_is_online'] = ($online == 1 ? 1 : 0);
		$this->database->AutoExecute('sys_definition_dashboard',$dash_info,'INSERT');

		/*
			Table : sys_pauto_config
		*/
		// Récupère la liste des graphes du dashboards
		$querySPC = "SELECT * FROM sys_pauto_config WHERE id_page= '".$this->idDashboard."'";
		$graphs = $this->database->getAll($querySPC);
		if ( count($graphs) > 0 )
		{
			foreach ( $graphs as $graph )
			{
				// on change id et id_page du raw/kpi
				$graph['id'] = generateUniqId('sys_pauto_config');
				$graph['id_page'] = $newId;
				// on supprime l'id_product
				if (!$graph['id_product']) unset($graph['id_product']);
				$this->database->AutoExecute('sys_pauto_config',$graph,'INSERT');
			}
		}

		// Si le dashbord doit être online
		if ( $online == 1 )
		{
			// 15:07 17/11/2009 GHX
			// Modification de la condition == au lieu de !=
			if ( $idMenuParent == null )
			{
				$idMenuParent = $this->database->getOne("SELECT id_menu_parent FROM menu_deroulant_intranet WHERE id_menu = '".$this->dashboardValues['id_menu']."'");
			}
			else
			{
				// Si l'id menu parent n'existe pas on prend celui du dashboard à dupliquer par défaut
				if ( $this->database->getOne("SELECT COUNT(*) FROM menu_deroulant_intranet WHERE id_menu = '".$idMenuParent."'") == 0 )
				{
					$idMenuParent = $this->database->getOne("SELECT id_menu_parent FROM menu_deroulant_intranet WHERE id_menu = '".$this->dashboardValues['id_menu']."'");
				}
			}

			// Si pas d'id menu parent on prend celui du menu CLIENT DASHBOARD
			if ( !$idMenuParent )
			{
				$idMenuParent = $this->database->getOne("SELECT id_menu_parent FROM menu_deroulant_intranet WHERE libelle_menu = 'CLIENT DASHBOARD'");
			}

			// on crée le menu
			$myMenu = new MenuModel(0);
			$myMenu->setValue('libelle_menu',$newName);
			$myMenu->setValue('lien_menu','');
			// on va chercher les actions
			$getActions = $this->database->getAll("SELECT id FROM menu_contextuel WHERE type_pauto='dashboard' ORDER BY ordre_menu");
			$actions = array();
			if ( count($getActions) > 0)
			{
				foreach ( $getActions as $act )
					$actions[] = $act['id'];
				$myMenu->setValue('liste_action',implode('-',$actions));
			}
			$myMenu->setValue('largeur',150);
			$myMenu->setValue('hauteur',20);
			$myMenu->setValue('id_page',$newId);
			// on specifie le droit_affichage
			if ( $droit == 'customisateur' )
			{
				$myMenu->setValue('droit_affichage','customisateur');
			}
			else
			{
				$myMenu->setValue('droit_affichage','astellia');
			}
			$myMenu->setValue('droit_visible',1);
			$myMenu->setValue('menu_client_default',0);
			$myMenu->setValue('is_profile_ref_user',1);
			$myMenu->setValue('id_menu_parent',$idMenuParent);

			$idMenu = $myMenu->addMenu(null, true);

			// on copie l'id du menu dans sys_definition_dashboard
			$this->database->execute("UPDATE sys_definition_dashboard SET sdd_id_menu='$idMenu' WHERE sdd_id_page='$newId'");

			// si le dashboard n'est pas bimode, on specifie son lien_menu
			if ( $this->dashboardValues['mode'] == 'overtime')
			{
				$myMenu->setValue('lien_menu',"/dashboard_display/index.php?id_dash=$newId&mode=overtime&id_menu_encours=$idMenu");
				$myMenu->updateMenu();
			}
			if ( $this->dashboardValues['mode'] == 'overnetwork')
			{
				$myMenu->setValue('lien_menu',"/dashboard_display/index.php?id_dash=$newId&mode=overnetwork&id_menu_encours=$idMenu");
				$myMenu->updateMenu();
			}

			// en bimode, il faut ajouter les sous-menu "Over Network Elements" / "Over Time"
			if ( $this->dashboardValues['mode'] == 'bimode')
			{
				$myMenu->setValue('id_menu_parent',$idMenu);

				// on ajoute le sous-menu 'Over Time'
				$myMenu->setValue('libelle_menu','Over Time');
				$id_menu = $myMenu->addMenu($myMenu->getValue('id_menu_parent').'.01');
				// 15:08 17/11/2009 GHX
				// $id_menu au lieu de $idMenu
				$myMenu->setValue('lien_menu',"/dashboard_display/index.php?id_dash=$newId&mode=overtime&id_menu_encours=$id_menu");
				$myMenu->updateMenu();

				// on ajoute le sous-menu 'Over Network Elements'
				$myMenu->setValue('libelle_menu','Over Network Elements');
				$id_menu = $myMenu->addMenu($myMenu->getValue('id_menu_parent').'.02');
				// 15:08 17/11/2009 GHX
				// $id_menu au lieu de $idMenu
				$myMenu->setValue('lien_menu',"/dashboard_display/index.php?id_dash=$newId&mode=overnetwork&id_menu_encours=$id_menu");
				$myMenu->updateMenu();
			}
		}

		return $newId;
	} // End function duplicate

	/**
	 * Supprime un graphe/pie du dashboard
	 *
	 * @author GHX
	 * @param string $idGTM : identifiant du graphe/pie à supprimer
	 */
	public function removeGTM ( $idGTM )
	{
		// supprime la ligne dans sys_pauto_config
		$query = "DELETE FROM sys_pauto_config WHERE id_page='".$this->idDashboard."' AND id_elem='".$idGTM."'";
		$this->database->execute($query);

		$this->updateNADefault();
		$this->updateSortByDefault();
	} // End function removeGTM

	/**
	 * Ajoute un graphe/pie au dashboard
	 *
	 * @author GHX
	 * @param string $idGTM : identifiant du graphe/pie à ajouter
	 */
	public function addGTM ( $idGTM )
	{
		// on va chercher la valeur max de ligne pour ce dashboard
		$query = "SELECT ligne FROM sys_pauto_config WHERE id_page= '".$this->idDashboard."' ORDER BY ligne DESC LIMIT 1";
		$ligne = $this->database->getOne($query);
		$ligne++;
		if ( empty($ligne) ) $ligne = 1;

		$next_id = generateUniqId('sys_pauto_config');

		// on insert le plot
		$query = "
			INSERT INTO sys_pauto_config (
				id,
				id_elem,
				class_object,
				id_page,
				ligne
			)
			VALUES (
				'".$next_id."',
				'".$idGTM."',
				'graph',
				'".$this->idDashboard."',
				".$ligne."
			)";
		$this->database->execute($query);

		$this->updateNADefault();
		$this->updateSortByDefault();
	} // End function addGTM

	/**
	 * Met à jour la NA axe1 et NA axe3 par defaut du dashboard par rapport à la liste des graphes du dashboard s'ils ne sont pas corrects ou non définit.
	 * Et retourne la liste des NA en communs pour chaque axes
	 * Format du tableau retourné
	 *	array(
	 *		'axe1' => liste des NA en communs pour l'axe 1,
	 *		'axe3' => liste des NA en communs pour l'axe 3
	 *	)
	 *
	 * @author GHX
	 * @return array
	 */
	public function updateNADefault ()
	{
		// on va chercher tous les graphs du dashboard
		$query = "SELECT id_elem FROM sys_pauto_config WHERE id_page='".$this->idDashboard."'";
		$graphs = $this->database->getAll($query);

		$na_levels_in_common = false;
		$na_axe3_levels_in_common = false;

		if ( count($graphs) > 0 )
		{
			$na_levels_in_common = $this->getNALevelsInCommon(1);
			$na_axe3_levels_in_common = $this->getNALevelsInCommon(3);
			// on prend les na levels du premier graph

			/*
			$na_levels_in_common = getNALabelsInCommon($graphs[0]['id_elem'],'na');
			$na_axe3_levels_in_common = getNALabelsInCommon($graphs[0]['id_elem'],'na_axe3');

			// Si on a des NA sur l'axe1 pour le premier graphe si c'est pas le cas pas la peine d'aller plus loin
			if ($na_levels_in_common)
			{
				// on boucle sur tous les autres graphs
				for ($i = 1; $i < sizeof($graphs); $i++)
				{
					$na_levels_of_the_graph = getNALabelsInCommon($graphs[$i]['id_elem']);
					if ($na_levels_of_the_graph)
					{
						// on fait l'intersection des na levels
						$na_levels_in_common = array_intersect_assoc( $na_levels_in_common, $na_levels_of_the_graph);
						unset($na_levels_of_the_graph);
					}
					else
					{
						// l'un des graphs n'a pas de na levels en commun, donc c'est fichu
						$na_levels_in_common = false;
						$i = 1000; // on sort du for i
					}
				}
			}
			// Si on a des NA sur l'axe3 pour le premier graphe si c'est pas le cas pas la peine d'aller plus loin
			if ( $na_axe3_levels_in_common )
			{
				// on boucle sur tous les autres graphs
				for ($i = 1; $i < sizeof($graphs); $i++)
				{
					$na_axe3_levels_of_the_graph = getNALabelsInCommon($graphs[$i]['id_elem'],'na_axe3');
					if ($na_axe3_levels_of_the_graph)
					{
						// on fait l'intersection des na levels
						$na_axe3_levels_in_common = array_intersect_assoc( $na_axe3_levels_in_common, $na_axe3_levels_of_the_graph);
						unset($na_axe3_levels_of_the_graph);
					}
					else
					{
						// l'un des graphs n'a pas de na levels en commun, donc c'est fichu
						$na_axe3_levels_in_common = false;
						$i = 1000; // on sort du for i
					}
				}
			} */
		}

		// si on a rien :
		if ( (!$na_levels_in_common) && (!$na_axe3_levels_in_common) )
		{
			// Si aucun élément réseau en commun on vide les 2 champs dans la table sys_definition_dashboard
			$queryUpdateNA = "UPDATE sys_definition_dashboard SET sdd_selecteur_default_na = NULL, sdd_selecteur_default_na_axe3 = NULL WHERE sdd_id_page = '".$this->idDashboard."'";
			$this->database->execute($queryUpdateNA);
		}
		else
		{
			// Si on n'a pas de NA pour l'axe 3 on le définit a NULL dans la table sys_definition_dashboard
			if ( !$na_axe3_levels_in_common )
			{
				$queryUpdateNA = "UPDATE sys_definition_dashboard SET sdd_selecteur_default_na_axe3 = NULL WHERE sdd_id_page = '".$this->idDashboard."'";
				$this->database->execute($queryUpdateNA);
			}

			// Si le NA par défault du premier axe n'est pas bon, on le redéfinit
			if ( empty($this->dashboardValues['na_level']) || !array_key_exists($this->dashboardValues['na_level'], $na_levels_in_common) )
			{
				reset($na_levels_in_common);
				$na = key($na_levels_in_common);
				$queryUpdateNA = "UPDATE sys_definition_dashboard SET sdd_selecteur_default_na = '".$na."' WHERE sdd_id_page = '".$this->idDashboard."'";
				$this->database->execute($queryUpdateNA);

				// Si on n'a pas de NA pour l'axe 3 on le définit a NULL
				if ( $na_axe3_levels_in_common )
				{
					reset($na_axe3_levels_in_common);
					$na_axe3 = key($na_axe3_levels_in_common);
					$queryUpdateNA = "UPDATE sys_definition_dashboard SET sdd_selecteur_default_na_axe3 = '".$na_axe3."' WHERE sdd_id_page = '".$this->idDashboard."'";
					$this->database->execute($queryUpdateNA);
				}
			}
		}

		return array('axe1'=> $na_levels_in_common, 'axe3' => $na_axe3_levels_in_common);
	} // End function updateNADefault

	/**
	 * Met à jour le sort by par défault si celui n'est plus valide ou est non définit
	 *
	 * @author GHX
	 */
	public function updateSortByDefault()
	{
		$sortBy = explode('@', $this->dashboardValues['sort_by']);

		// Si on a un sort by de définit on regarde s'il est bon
		if ( !empty($sortBy[1]) )
		{
			$nb = $this->database->getOne("
				SELECT
					COUNT(gtm.id)
				FROM
					sys_pauto_config AS dash
					left join sys_pauto_config AS gtm ON (dash.id_elem = gtm.id_page)
				WHERE
					dash.id_page =  '".$this->idDashboard."'
					AND gtm.id_elem = '".$sortBy[1]."'
			");
			// Si le sort by est bon on peut quitter la fonction
			if ( $nb == 1 )
				return;
		}

		// Le sort est non définit ou est invalide dans ce cas on prend le premier élément du premier graphe/pie définit dans le dashboard
		$sortDefault = $this->database->getOne("
			SELECT
				gtm.id
			FROM
				sys_pauto_config AS dash
				left join sys_pauto_config AS gtm ON (dash.id_elem = gtm.id_page)
			WHERE
				dash.id_page = '".$this->idDashboard."'
			ORDER BY
				dash.ligne ASC,
				gtm.ligne ASC
			LIMIT 1
		");

		$this->database->execute("UPDATE sys_definition_dashboard SET sdd_sort_by_id = '".$sortDefault."' WHERE sdd_id_page = '".$this->idDashboard."'");
	} // End function updateSortByDefault

	/**
	 * Définit le sort by par défaut du dashboard, si celui-ci est incorrect le premier élément du premier graphe sera définit comme le sort by par défaut
	 *
	 * @author GHX
	 * @param string $idElem : identifiant du raw/kpi
	 * @param string $type : type de l'élément "raw" ou "kpi", il est possible d'utiliser "counter" au lieu de "raw"
	 * @param string $idGTM : identifiant du graphe/pie dans lequel se trouve l'élément raw/kpi
	 * @param int $idProduct : identifiant du produit sur lequel se trouve l'élément
	 * @param string $orderBy : ordre de tri pour le sort by "asc" ou "desc" (default desc)
	 */
	public function setSortByDefault ( $idElem, $type, $idGTM, $idProduct, $orderBy = 'desc' )
	{
		$type = trim(strtolower($type));
		$type = ( $type == 'raw' ? 'counter' : $type);

		// Si la type de sort by n'est pas bon on prend "desc" par défaut (évite des erreurs)
		$orderBy = trim(strtolower($orderBy));
		if ( !in_array($positionOrdonnee, array('desc', 'asc')) ) $orderBy = 'desc';

		$querySDD = "
			UPDATE
				sys_definition_dashboard
			SET
				sdd_sort_by_id = spc.id,
				sdd_sort_by_order = '".$orderBy."'
			FROM
				sys_pauto_config AS spc
			WHERE
				spc.id_elem = '".$idElem."'
				AND spc.class_object = '".$type."'
				AND spc.id_page = '".$idGTM."'
				AND spc.id_product = ".$idProduct."
				AND sys_definition_dashboard.sdd_id_page = '".$this->idDashboard."'
			RETURNING *
			";

		$result = $this->database->execute($querySDD);

		// Dans le cas ou le nouveau sort n'a pas bien été définit on vérifie qu'on a bien un sort by pour éviter de faire planter la restitution
		if ( $this->database->getAffectedRows() == 0 )
		{
			$this->updateSortByDefault();
		}
		else // Si le sort by est correcte on remplace la valeur du dashboard courante
		{
			$row = $this->database->getQueryResults($result, 1);
			$sortBy = explode('@', $this->dashboardValues['sort_by']);
			$sortBy[1] = $row['id_elem'];
			$this->dashboardValues['sort_by'] = implode('@', $sortBy);
		}
	} // End function setSortByDefault
}
