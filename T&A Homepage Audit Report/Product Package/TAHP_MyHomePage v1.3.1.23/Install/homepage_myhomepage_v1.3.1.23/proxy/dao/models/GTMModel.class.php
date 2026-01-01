<?php
/**
*	Classe permettant de manipuler/récupérer les données d'un GTM
*
*	@author	BBX - 18/11/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*
*/

/**
 *
	- maj 02/12/2008, benoit : ajout de la méthode ci-dessous permettant de renvoyer des informations sur le raw / kpi de tri d'un GTM
	07/05/2009 GHX
		- On récupère le label dans graph_data et non dans sys_definition_kpi ou sys_field_reference pour le splitBy
	11/05/2009 GHX
		- Ajout de l'id_ligne du kpi/raw dans le tableau retourné par la fonction getGTMSplitBy()
	26/05/2009 GHX
		- Ajout de la fonction getInfoLinkToAA() qui permet d'avoir les raw/kpi ayant un lien vers Activity Analysis.
	29/07/2009 GHX
		- Ajout d'un ORDER BY dans les requetes SQL qui récupèrent les RAWs ou KPIs
		
	31/07/2009 BBX. BZ 10633
		- Ajout de la fonction getLastComment()
	03/08/2009 GHX
		- Correction du BZ 10863 [REC][T&A Cb 5.0][Gis] : on peut lancer le GIS avec la variable gis_mode = 0
	14/08/2009 GHX
		- (Evo) Ajout des fonctions suivantes :
			getGtmUniqKpis() & getGtmUniqRaws()
			getSameKpis() & getSameRaws()
	17/08/2009 GHX
		-Modif dans la fonction getGTMProperties()
	18/08/2009 GHX
		- Ajout de 2 fonctions getAll() & getAllContainsIdProduct()
		- Ajout de la fonction delete()
	25/08/2009 GHX
		- Correction du BZ 11198 [REC][T&A CB 5.0][TC#37203][TP#3][TS#TT1-CB540][DASHBOARD]: mauvais affichage label order by
			-> Modification de la fonction getGTMSortBy
	28/08/2009 GHX
		- Re-correction du BZ 11195
			-> Modification de la fonction getGTMSortBy() : ajout d'une condition dans une requete SQL
	19/10/2009 GHX
		- Ajout de la fonction getAllContainsIdFamily() qui permet de retourner la liste des GTMs dans lesquelles une famille est présentes
	27/10/2009 MPR
		- Correction du BZ 12258 : Ajout de l'id du produit afin de gérer le cas suivant => famille roaming présente sur produit slave et master avec des NA différentes
	28/10/2009 GHX
		- Modification d'un paramètre passé à DataBaseConnection qui était mauvais dans le fonction getAllContainsIdProduct()
		- Ajout d'un paramètre au constructeur
	23/11/2009 GHX
		- Correction du BZ 12977[MIXED KPI] : activation de Dashboard
			-> Modification de requetes SQL dans les fonctions setSplitFirstAxisByDefault ( ) & setGisByDefault (  )
	21/12/2009 GHX
		- Initialisation de quelques variables avec DataBaseConnection() dans plusieurs fonctions
	05/03/2010 BBX
		- Ajout de la méthode "manageConnections"
		- Utilisation de la méthode "manageConnections" au lieu de DatabaseConnection afin d'éviter les instances redondantes
	08/03/2010 BBX
		- Suppression de la méthode "manageConnections"
		- Utilisation de la méthode "Database::getConnection" à la place.
 */


class GTMModel
{
	/**
	* Propriétés
	*/
	private $idGTM = 0;	
	private $database = null;
	private $GTMValues = Array();
	
	// Mémorise les instances de connexions ouvertes
	private static $connections = Array();

	/**
	 * Constructeur
	 * 
	 * 	28/10/2009 GHX
	 *		- Ajout du deuxieme paramètre
	 * 
	 * @param : int	id dashboard	<optional>
	 * @param int $diProduct identifiant du produit sur lequel on doit se connecter (defaut le master product)
	 */
	public function __construct($idGTM, $idProduct = '')
	{
		// Sauvegarde de l'id GTM
		$this->idGTM = $idGTM;	
		$this->database = Database::getConnection($idProduct);
	}

	/************************************************************************
	* Méthode getGtmRaws : récupère un tableau des id raw d'un gtm
	* @return : array	Tableau associatif id raw => nom raw
	************************************************************************/
	public function getGtmRaws()
	{
		$array_return = Array();
		
		// 27/01/2009 - Modif. benoit : correction de la requete. L'id du raw retourné n'est pas celui le définissant dans 'sys_field_reference' mais son id dans 'sys_pauto_config'

		// 27/01/2009 - Modif. benoit : ajout de l'information 'id_product' dans la requete et dans la clé du tableau de résultats
		
		// 09:08 29/07/2009 GHX
		// Ajout du ORDER BY
		$query =	 " SELECT s.id_elem, g.data_legend, s.id_product"
					." FROM sys_pauto_config s, graph_data g"
					." WHERE s.id = g.id_data"
					." AND s.id_page = '".$this->idGTM."'"
					." AND s.class_object = 'counter'"
					." ORDER BY s.ligne ASC";

		$result = $this->database->execute($query);
		while($values = $this->database->getQueryResults($result,1)) {
			$array_return[$values['id_elem']."@".$values['id_product']] = $values['data_legend'];
		}
		return $array_return;
	}
	
	/**
	 * Similaire à la fonction getGtmRaws() mais prendre en compte le fait qu'il peut y avoir des RAW identiques code+label
	 *
	 *	14/08/2009 GHX
	 *		- Création de la fonction
	 *
	 * @return array
	 */
	public function getGtmUniqRaws()
	{
		$array_return = Array();

		$query =	 " SELECT s.id_elem, g.data_legend, s.id_product, s.ligne"
					." FROM sys_pauto_config s, graph_data g"
					." WHERE s.id = g.id_data"
					." AND s.id_page = '".$this->idGTM."'"
					." AND s.class_object = 'counter'"
					." ORDER BY s.id_product ASC";

		$result = $this->database->execute($query);
		
		$query_counter = "SELECT lower(edw_field_name) AS name FROM sys_field_reference WHERE id_ligne = '%s'";
		$counter = array();
		$id_product = null;
		$db_temp = Database::getConnection(0);
		while($values = $this->database->getQueryResults($result,1))
		{
			// Initilisation d'une connexion sur la bonne base de données
			if ( $id_product != $values['id_product'] )
			{
				$id_product = $values['id_product'];
				$db_temp = Database::getConnection($id_product);
			}
			// Récupère le nom de l'élément ...
			$name = $db_temp->getOne(sprintf($query_counter, $values['id_elem']));
			// ... l'ajout dans le tableau 
			$values['name'] = $name;
			// Ajout l'élément dans le tableau appropié en fonction de son type
			$counter[strtolower($name.$values['data_legend'])][] = $values;
		}
		
		if ( count($counter) > 0 )
		{
			$tmpTabSort = array();
			foreach ( $counter as $index => $elem )
			{
				$array_return[$elem[0]['id_elem']."@".$elem[0]['id_product']] = $elem[0]['data_legend'];
				$tmpTabSort[] = $elem[0]['ligne'];
			}
			array_multisort($tmpTabSort, SORT_ASC, $array_return);
		}
		
		
		return $array_return;
	} // End function getGtmUniqRaws
	
	
	/**
	 * Retourne la liste des RAW identiques (code+legend)
	 *
	 *	14/08/2009 GHX
	 *		- Création de la fonction
	 *
	 * @return array
	 */
	public function getSameRaws ( $name = "", $legend = "" )
	{
		$array_return = Array();

		$query =	 " SELECT s.id_elem, g.data_legend, s.id_product, s.ligne"
					." FROM sys_pauto_config s, graph_data g"
					." WHERE s.id = g.id_data"
					." AND s.id_page = '".$this->idGTM."'"
					." AND s.class_object = 'counter'"
					." ORDER BY s.id_product ASC";

		$result = $this->database->execute($query);
		
		$query_counter = "SELECT lower(edw_field_name) AS name FROM sys_field_reference WHERE id_ligne = '%s'";
		$counter = array();
		$id_product = null;
		$db_temp = Database::getConnection(0);
		while($values = $this->database->getQueryResults($result,1))
		{
			// Initilisation d'une connexion sur la bonne base de données
			if ( $id_product != $values['id_product'] )
			{
				$id_product = $values['id_product'];
				$db_temp = Database::getConnection($id_product);
			}
			// Récupère le nom de l'élément ...
			$n = $db_temp->getOne(sprintf($query_counter, $values['id_elem']));
			// ... l'ajout dans le tableau 
			$values['name'] = $n;
			// Ajout l'élément dans le tableau appropié en fonction de son type
			$counter[strtolower($n.$values['data_legend'])][] = $values;
		}
		
		if ( count($counter) > 0 )
		{
			if ( empty($name) && empty($legend) )
			{
				foreach ( $counter as $index => $elem )
				{
					if ( count($elem) > 1 )
					{
						foreach ( $elem as $el )
						{
							$array_return[$el['id_elem']."@".$el['id_product']] = $el['data_legend'];
						}
					}
				}
			}
			else
			{
				if ( array_key_exists(strtolower($name.$legend), $counter) )
				{
					$elem = $counter[strtolower($name.$legend)];
					if ( count($elem) > 1 )
					{
						foreach ( $elem as $el )
						{
							$array_return[$el['id_elem']."@".$el['id_product']] = $el['data_legend'];
						}
					}
				}
			}
		}
		
		
		return $array_return;
	} // End function getSameRaws
	
	/**
	 * Renvoie la liste des raws d'un GTM en les classant par produit
	 *
	 * @return array liste des raws du GTM ordonnés par produit
	 */

	public function getGtmRawsByProduct()
	{
		$product_raws_tmp = Array();
		
		// 09:08 29/07/2009 GHX
		// Ajout du ORDER BY
		$sql =	 " SELECT s.id_elem, g.data_legend, s.id_product"
				." FROM sys_pauto_config s, graph_data g"
				." WHERE s.id = g.id_data AND s.id_page = '".$this->idGTM."' AND s.class_object = 'counter'"
				." ORDER BY s.ligne ASC";

		$raws = $this->database->getAll($sql);
		
		foreach($raws as $values){
			$product_raws_tmp[$values['id_product']][$values['id_elem']] = array('label' => $values['data_legend']);
		}

		// Ajout du nom, du group_table et de la famille des raws en fonction des produits auquels ils sont rattachés

		$product_raws = Array();

		foreach ($product_raws_tmp as $product=>$values)
		{
			// Création d'une connexion à la base de données du produit
			
			$db = Database::getConnection($product);

			// Récupération du nom, du group_table et de la famille des raws du produit

			// 02/02/2009 - Modif. benoit : on englobe les 'id_ligne' dans des cotes afin de correspondre à la nouvelle structure de tables
			
			$sql =	 " SELECT sfr.edw_field_name, sfr.id_ligne, sfr.edw_group_table, sdgtr.family"
					." FROM sys_field_reference sfr, sys_definition_group_table_ref sdgtr"
					." WHERE sfr.id_ligne IN (".implode(", ", array_map(array($this, 'labelizeValue'), array_keys($values))).")"
					." AND sfr.edw_group_table = sdgtr.edw_group_table";
			
			$row = $db->getAll($sql);

			for ($i=0; $i < count($row); $i++)
			{
				$raw_info = array('id' => $row[$i]['id_ligne'], 'label' => $values[$row[$i]['id_ligne']]['label']);		
				$product_raws[$product][$row[$i]['family']][$row[$i]['edw_field_name']] = $raw_info;
			}
		}
		
		return $product_raws;
	}

	/**
	 * Renvoie la liste des kpis d'un GTM en les classant par produit
	 *
	 * @return array liste des kpis du GTM ordonnés par produit
	 */
	public function getGtmKpisByProduct()
	{
		$product_kpis_tmp = Array();
		
		// 09:08 29/07/2009 GHX
		// Ajout du ORDER BY
		$sql =	 " SELECT s.id_elem, g.data_legend, s.id_product"
				." FROM sys_pauto_config s, graph_data g"
				." WHERE s.id = g.id_data AND s.id_page = '".$this->idGTM."' AND s.class_object = 'kpi'"
				." ORDER BY s.ligne ASC";

		$kpis = $this->database->getAll($sql);
		
		foreach($kpis as $values){
			$product_kpis_tmp[$values['id_product']][$values['id_elem']] = array('label' => $values['data_legend']);
		}

		// Ajout du nom, du group_table et de la famille des kpis en fonction des produits auquels ils sont rattachés

		$product_kpis = Array();

		foreach ($product_kpis_tmp as $product=>$values)
		{
			// Création d'une connexion à la base de données du produit
			
			$db = Database::getConnection($product);

			// Récupération du nom, du group_table et de la famille des kpis du produit
			
			$sql =	 " SELECT sdk.kpi_name, sdk.id_ligne, sdk.edw_group_table, sdgtr.family"
					." FROM sys_definition_kpi sdk, sys_definition_group_table_ref sdgtr"
					." WHERE sdk.id_ligne IN (".implode(", ", array_map(array($this, 'labelizeValue'), array_keys($values))).")"
					." AND sdk.edw_group_table = sdgtr.edw_group_table";
			
			$row = $db->getAll($sql);

			for ($i=0; $i < count($row); $i++)
			{
				$kpi_info = array('id' => $row[$i]['id_ligne'], 'label' => $values[$row[$i]['id_ligne']]['label']);				
				$product_kpis[$product][$row[$i]['family']][$row[$i]['kpi_name']] = $kpi_info;
			}
		}
		
		return $product_kpis;		
	}
	
	/************************************************************************
	* Méthode getGtmKpis : récupère un tableau des id kpi d'un gtm
	* @return : array	Tableau associatif id kpi => nom kpi
	************************************************************************/
	public function getGtmKpis()
	{
		$array_return = Array();
		
		// 27/01/2009 - Modif. benoit : correction de la requete. L'id du kpi retourné n'est pas celui le définissant dans 'sys_definition_kpi' mais son id dans 'sys_pauto_config'

		// 27/01/2009 - Modif. benoit : ajout de l'information 'id_product' dans la requete et dans la clé du tableau de résultats
		
		// 09:08 29/07/2009 GHX
		// Ajout du ORDER BY
		$query =	 " SELECT s.id_elem, g.data_legend, s.id_product"
					." FROM sys_pauto_config s, graph_data g"
					." WHERE s.id = g.id_data"
					." AND s.id_page = '".$this->idGTM."'"
					." AND s.class_object = 'kpi'"
					." ORDER BY s.ligne ASC";
		
		$result = $this->database->execute($query);
		while($values = $this->database->getQueryResults($result,1)) {
			$array_return[$values['id_elem']."@".$values['id_product']] = $values['data_legend'];
		}
		return $array_return;
	}

	/**
	 * Fonction similaire à la fonction getGtmKpis() mais prendre en compte le fait qu'il peut y avoir des KPI identiques code+label
	 *
	 *	14/08/2009 GHX
	 *		- Création de la fonction
	 *
	 * @return array
	 */
	public function getGtmUniqKpis()
	{
		$array_return = Array();

		$query =	 " SELECT s.id_elem, g.data_legend, s.id_product, s.ligne"
					." FROM sys_pauto_config s, graph_data g"
					." WHERE s.id = g.id_data"
					." AND s.id_page = '".$this->idGTM."'"
					." AND s.class_object = 'kpi'"
					." ORDER BY s.id_product ASC";
		
		$result = $this->database->execute($query);
		
		$query_kpi = "SELECT lower(kpi_name) AS name FROM sys_definition_kpi WHERE id_ligne = '%s'";
		$kpi = array();
		$id_product = null;
		$db_temp = Database::getConnection(0);
		while($values = $this->database->getQueryResults($result,1))
		{
			// Initilisation d'une connexion sur la bonne base de données
			if ( $id_product != $values['id_product'] )
			{
				$id_product = $values['id_product'];
				$db_temp = Database::getConnection($id_product);
			}
			// Récupère le nom de l'élément ...
			$name = $db_temp->getOne(sprintf($query_kpi, $values['id_elem']));
			// ... l'ajout dans le tableau 
			$values['name'] = $name;
			// Ajout l'élément dans le tableau appropié en fonction de son type
			$kpi[strtolower($name.$values['data_legend'])][] = $values;
		}
		
		if ( count($kpi) > 0 )
		{
			$tmpTabSort = array();
			foreach ( $kpi as $index => $elem )
			{
				$array_return[$elem[0]['id_elem']."@".$elem[0]['id_product']] = $elem[0]['data_legend'];
				$tmpTabSort[] = $elem[0]['ligne'];
			}
			array_multisort($tmpTabSort, SORT_ASC, $array_return);
		}
		
		return $array_return;
	} // End function getGtmUniqKpis
	
	/**
	 * Retourne la liste des KPI identiques (code+legend)
	 *
	 *	14/08/2009 GHX
	 *		- Création de la fonction
	 *
	 * @param $string $name
	 * @param $string $legend
	 * @return array
	 */
	public function getSameKpis ( $name = "", $legend = "")
	{
		$array_return = Array();

		$query =	 " SELECT s.id_elem, g.data_legend, s.id_product, s.ligne"
					." FROM sys_pauto_config s, graph_data g"
					." WHERE s.id = g.id_data"
					." AND s.id_page = '".$this->idGTM."'"
					." AND s.class_object = 'kpi'"
					." ORDER BY s.id_product ASC";
		
		$result = $this->database->execute($query);
		
		$query_kpi = "SELECT lower(kpi_name) AS name FROM sys_definition_kpi WHERE id_ligne = '%s'";
		$kpi = array();
		$id_product = null;
		$db_temp = Database::getConnection(0);
		while($values = $this->database->getQueryResults($result,1))
		{
			// Initilisation d'une connexion sur la bonne base de données
			if ( $id_product != $values['id_product'] )
			{
				$id_product = $values['id_product'];
				$db_temp = Database::getConnection($id_product);
			}
			// Récupère le nom de l'élément ...
			$n = $db_temp->getOne(sprintf($query_kpi, $values['id_elem']));
			// ... l'ajout dans le tableau 
			$values['name'] = $n;
			// Ajout l'élément dans le tableau appropié en fonction de son type
			$kpi[strtolower($n.$values['data_legend'])][] = $values;
		}
		
		if ( count($kpi) > 0 )
		{
			if ( empty($name) && empty($legend) )
			{
				foreach ( $kpi as $index => $elem )
				{
					if ( count($elem) > 1 )
					{
						foreach ( $elem as $el )
						{
							$array_return[$el['id_elem']."@".$el['id_product']] = $el['data_legend'];
						}
					}
				}
			}
			else
			{
				if ( array_key_exists(strtolower($name.$legend), $kpi) )
				{
					$elem = $kpi[strtolower($name.$legend)];
					if ( count($elem) > 1 )
					{
						foreach ( $elem as $el )
						{
							$array_return[$el['id_elem']."@".$el['id_product']] = $el['data_legend'];
						}
					}
				}
			}
		}
		
		return $array_return;
	} // End function getSameKpis
	
	/**
	 * Retourne un ensemble d'informations d'un kpi sur une famille donnée
	 *
	 * @param string $id_kpi identifiant du kpi
	 * @param int $id_product identifiant du produit
	 * @return array liste des informations du kpi
     *
     * 12/01/2011 OJT : bz19809 Ajout du KPI label dans le tableau retourné
	 */
	public function getKpiInformations($id_kpi, $id_product)
	{
		// Création d'une connexion à la base de données du produit
		$db = Database::getConnection($id_product);
		
		// Récupération du nom et de la famille du kpi du produit
		
		// 16/02/2009 - Modif. benoit : ajout des informations 'comment', 'formula' et 'link_to_aa'
		$sql =	 " SELECT sdk.kpi_name, sdk.kpi_label, sdgtr.family, sdk.comment,"
				." CASE WHEN substring(sdk.kpi_formula FROM 1 FOR 4) = 'CASE'"
				."		THEN substring(sdk.kpi_formula FROM '%ELSE #\"%#\" END' FOR '#')"
				."		ELSE kpi_formula"
				." END AS formula,"
				." CASE WHEN sdk.kpi_name IN (SELECT saafk_idkpi FROM sys_aa_filter_kpi) THEN true ELSE false END AS link_to_aa"
				." FROM sys_definition_kpi sdk, sys_definition_group_table_ref sdgtr"
				." WHERE sdk.id_ligne = '".$id_kpi."'"
				." AND sdk.edw_group_table = sdgtr.edw_group_table";
		$row_d = $db->getRow($sql);

		// Récupération du label de l'element sur la base de données locale
		$sql =	 " SELECT g.data_legend"
				." FROM sys_pauto_config s, graph_data g"
				." WHERE s.id = g.id_data"
				." AND s.id_page = '".$this->idGTM."' AND s.id_elem = '".$id_kpi."' AND s.class_object = 'kpi'";
		$row_l = $this->database->getRow($sql);

		return array(	'name'			=> $row_d['kpi_name'],
						'label'			=> $row_l['data_legend'],
                                                'src_label' 	=> $row_d['kpi_label'],
						'family'		=> $row_d['family'],
						'comment'		=> $row_d['comment'],
						'formula'		=> $row_d['formula'],
						'link_to_aa'	=> $row_d['link_to_aa']);
	}

	/**
	 * Retourne un ensemble d'informations d'un compteur sur une famille donnée
	 *
	 * @param string $id_kpi identifiant du compteur
	 * @param int $id_product identifiant du produit
	 * @return array liste des informations du compteur
     *
     * 12/01/2011 OJT : bz19809 Ajout du RAW label (edw_field_name_label) dans le tableau retourné
	 */
	public function getRawInformations($id_raw, $id_product)
	{
		// Création d'une connexion à la base de données du produit
		$db = Database::getConnection($id_product);
		
		// Récupération du nom et de la famille du raw du produit

		// 02/02/2009 - Modif. benoit : on englobe 'id_ligne' dans des cotes afin de correspondre à la nouvelle structure de tables
		$sql =	 " SELECT sfr.edw_field_name, sfr.edw_field_name_label, sdgtr.family, sfr.comment,"
				." CASE WHEN sfr.edw_field_name IN (SELECT saafk_idkpi FROM sys_aa_filter_kpi) THEN true ELSE false END AS link_to_aa"
				." FROM sys_field_reference sfr, sys_definition_group_table_ref sdgtr"
				." WHERE sfr.id_ligne = '".$id_raw."'"
				." AND sfr.edw_group_table = sdgtr.edw_group_table";
		$row_d = $db->getRow($sql);

		// Récupération du label de l'element sur la base de données locale
		$sql =	 " SELECT g.data_legend"
				." FROM sys_pauto_config s, graph_data g"
				." WHERE s.id = g.id_data"
				." AND s.id_page = '".$this->idGTM."' AND s.id_elem = '".$id_raw."' AND s.class_object = 'counter'";
		$row_l = $this->database->getRow($sql);

		return array(	'name'			=> $row_d['edw_field_name'],
						'label'			=> $row_l['data_legend'],
                                                'src_label'     => $row_d['edw_field_name_label'],
						'family'		=> $row_d['family'],
						'comment'		=> $row_d['comment'],
						'link_to_aa'	=> $row_d['link_to_aa']);
	}

	/************************************************************************
	*	Cette méthode retourne la liste des NA levels en commun pour un GTM donné (par son id_page)
	*
	*	Pour comprendre :
	*		- un GTM contient des éléments
	*		- chaque élément appartient à UNE famille
	*		- chaque famille contient un ou plusieurs niveau d'aggrégation (NA level)
	*
	*	Cette fonction :
	*		-1- va chercher tous les éléments du GTM
	*		-2- va chercher les familles de ces éléments ( = requête dans la base du produit correspondant)
	*		-3- fait le tableau de toutes les familles différentes
	*		-4- va chercher la liste de tous les NA levels de chaque famillle
	*		-5- cherche l'intersection de tous les NA levels communs à toutes les familles
	*
	*	@param int		axe des niveau d'agrégation à récupérer (1 ou 3)
	*	@return array	retourne false s'il n'y a aucun na level en commun, et retourne la liste des niveaux d'agrégation en commun sous forme d'un array sinon.
	************************************************************************/
	function getNALevelsInCommon($axe = 1)
	{
		// Test du paramètre
		if(($axe != 1) && ($axe != 3)) return false;
		$array_axis_parameters = Array(1 => 'na', 3 => 'na_axe3');		
		// -1- on va chercher la liste des data (raw/kpi) qui composent le GTM
		$query = "SELECT * FROM sys_pauto_config WHERE id_page = '".$this->idGTM."'";
		$elements = $this->database->getAll($query);		
		// -2- on boucle sur tous les éléments et on va chercher leur famille
		foreach ($elements as &$elem) {
			// make the query
			if ($elem['class_object']=='counter') {
				$query = "(SELECT family FROM sys_definition_group_table WHERE edw_group_table IN
						(SELECT edw_group_table FROM sys_field_reference WHERE id_ligne = '".$elem['id_elem']."'))";
			} else {
				$query = "(SELECT family FROM sys_definition_group_table WHERE edw_group_table IN
						(SELECT edw_group_table FROM sys_definition_kpi WHERE id_ligne = '".$elem['id_elem']."'))";
			}

			// choose db
			$db_temp = Database::getConnection($elem['id_product']);
			$elem['family'] = $db_temp->getone($query);
			unset($db_temp);
		}		
		// -3- maintenant qu'on a les elements et leurs familles, on compose le tableau de toutes les familles différentes des éléments
		if ($elements) {
			$families = array();
			// maj MPR : Correction du BZ 12258 : Ajout de l'id du produit afin de gérer le cas suivant => famille roaming présente sur produit slave et master avec des NA différentes
			foreach ($elements as $e)
				if (!in_array($e["family"],$families))
					$families[] = $e['id_product']."_".$e["family"];
		}		
		// -4- on va chercher toutes les listes de na_label sur tous les produits concernés
		// ex:		$na_labels['apn'] =  [ 'apnamegroup' => 'APName Group', 'apname' => 'APName' ]		
		$all_na_labels = Array();
		foreach ($this->getGTMProducts() as $p) {
			$na_labels = getNaLabelListForProduct( $array_axis_parameters[$axe], '', $p);
			foreach ($na_labels as $key => $val) {
				$all_na_labels[$p['sdp_id']."_".$key] = $val;
			}
		}		
		$na_levels = $all_na_labels;	
		// -5- on prend les na_levels de la première famille
		$na_levels_in_common = $na_levels[$families[0]];		
		if (!is_array($na_levels_in_common)	)	return false;		
		// on boucle sur toutes les familles pour trouver tous les NA levels communs à ces familles
		for ($i=1; $i<sizeof($families); $i++)
			$na_levels_in_common = @array_intersect_assoc( $na_levels_in_common, $na_levels[$families[$i]] );		
		// si on a rien :
		if (sizeof($na_levels_in_common) == 0) {
			return false;
		} else {
			// on renvoie la liste
			return $na_levels_in_common;
		}
	}

	function getNAByProductsAndFamilies($axe = 1)
	{		
		// Test du paramètre
		
		if(($axe != 1) && ($axe != 3)) return false;
		
		$array_axis_parameters = Array(1 => 'na', 3 => 'na_axe3');		
		
		// 1 - on va chercher la liste des data (raw/kpi) qui composent le GTM
		
		$sql = "SELECT * FROM sys_pauto_config WHERE id_page = '".$this->idGTM."'";
		$data = $this->database->getAll($sql);
		
		//echo $sql."<br>";
		
		// 2 - On réorganise les données suivant leurs produits et leurs types
		
		$gtm_data = array();
		
		for ($i=0;$i < count($data); $i++){
			$gtm_data[$data[$i]['id_product']][$data[$i]['class_object']][] = $data[$i]['id_elem'];
		}
				
		// 3 - On cherche les na des familles des éléments du GTM
		
		$na_family = array();
		
		foreach ($gtm_data as $product => $elements) 
		{			
			foreach ($elements as $type => $list_elts)
			{
				if ($type == "counter") 
				{	
					$sub_sql = "SELECT DISTINCT edw_group_table FROM sys_field_reference WHERE id_ligne IN (".implode(", ", array_map(array($this, 'labelizeValue'), $list_elts)).")";
				}
				else // "kpis"
				{
					$sub_sql = "SELECT DISTINCT edw_group_table FROM sys_definition_kpi WHERE id_ligne IN (".implode(", ", array_map(array($this, 'labelizeValue'), $list_elts)).")";
				}

				// 02/02/2009 - Modif. benoit : ajout de la colonne 'third_axis_default_level' et du tri par 'agregation_level'

				// 03/02/2009 - Modif. benoit : ajout de la colonne 'agregation_label'
				
				$sql =	 " SELECT DISTINCT sdna.family, sdna.agregation, sdna.agregation_label,"
						." sdna.third_axis_default_level, sdna.agregation_level"
						." FROM sys_definition_network_agregation sdna, sys_definition_group_table sdgt"
						." WHERE sdgt.edw_group_table IN (".$sub_sql.")"
						." AND sdgt.family = sdna.family";
				
				if ($axe == 1) $sql .= " AND sdna.axe IS NULL";
				if ($axe == 3) $sql .= " AND sdna.axe = 3";
				
				$sql .= " ORDER BY sdna.agregation_level DESC";
				
				$db_tmp = Database::getConnection($product);
				$row = $db_tmp->getAll($sql);
				
				for ($i=0;$i<count($row);$i++){
					if (@!in_array($row[$i]['agregation'], $na_family[$product][$row[$i]['family']])) 
					{
						$na_family[$product][$row[$i]['family']][] = array('agregation' => $row[$i]['agregation'], 'agregation_label' => $row[$i]['agregation_label'], 'third_axis_default_level' => $row[$i]['third_axis_default_level']);
					}			
				}
				unset($db_tmp);
			}
		}
		
		return $na_family;
	}
	
	/************************************************************************
	* Méthode getGTMProducts : récupère un tableau des id produits liés au GTM
	* @return : array	Tableau d'id produits
	************************************************************************/
	public function getGTMProducts()
	{
		$array_retour = Array();
		$query = "SELECT DISTINCT id_product FROM sys_pauto_config WHERE id_page = '".$this->idGTM."'";		
		$result = $this->database->execute($query);
		while($elem = $this->database->getQueryResults($result,1)) {
			$array_retour[] = $elem['id_product'];
		}
		return array_unique($array_retour);
	}
	
	// 20/02/2008 - Modif. benoit : ajout de la méthode ci-dessous
	
	/**
	 * Renvoie la liste des produits et des familles des éléments du GTM
	 *
	 * @return array liste des produits et des familles des éléments du GTM
	 */
		
	public function getGTMProductsAndFamilies()
	{
		$pdts_fams = array();
		
		// 1 - on va chercher la liste des data (raw/kpi) qui composent le GTM
		
		$sql = "SELECT id_product, id_elem, class_object FROM sys_pauto_config WHERE id_page = '".$this->idGTM."'";
		$data = $this->database->getAll($sql);
		
		// 2 - On réorganise les données suivant leurs produits et leurs types
		
		$gtm_data = array();
		
		for ($i=0;$i < count($data); $i++){
			$gtm_data[$data[$i]['id_product']][$data[$i]['class_object']][] = $data[$i]['id_elem'];
		}
		
		// 3 - On va chercher le group_table et la famille des données du GTM
				
		foreach ($gtm_data as $product => $elements) 
		{			
			$db = Database::getConnection($product);
			
			foreach ($elements as $type => $list_elts)
			{			
				// Selection de la famille et du group_table des données sur chaque produit
				
				$sql =	 " SELECT DISTINCT sdgtr.family, s.edw_group_table"
						." FROM ".(($type == "counter") ? "sys_field_reference" : "sys_definition_kpi")." s, sys_definition_group_table_ref sdgtr"
						." WHERE s.id_ligne IN (".implode(", ", array_map(array($this, 'labelizeValue'), $list_elts)).")"
						." AND s.edw_group_table = sdgtr.edw_group_table";

				$row = $db->getAll($sql);
				
				for ($i=0;$i < count($row); $i++){
					
					// On vérifie que la famille n'existe pas avant de l'ajouter au tableau de résultats
					
					$family_exist = false;
					
					for ($j=0;$j < count($pdts_fams[$product]); $j++){					
						if ($pdts_fams[$product][$j]['family'] == $row[$i]['family']) $family_exist = true;
					}
					
					// La famille n'existe pas : on l'ajoute ainsi que le group_table
					
					if (!$family_exist) {
						$pdts_fams[$product][] = array('family' => $row[$i]['family'], 'edw_group_table' => $row[$i]['edw_group_table']);
					}
				}		
			}
		}
		return $pdts_fams;
	}

	// 18/12/2008 - Modif. benoit : ajout de la méthode ci-dessous définissant le type des GTMs

	/**
	 * Renvoie le type du GTM
	 *
	 * @return string type du GTM
	 */
	
	public function getGTMType()
	{
		$sql = "SELECT object_type FROM graph_information WHERE id_page = '".$this->idGTM."' LIMIT 1";
		$row = $this->database->getRow($sql);

		return $row['object_type'];
	}
	
	// 21/01/2009 - Modif. benoit : ajout de la méthode ci-dessous permettant de définir les informations GIS du GTM
	
	/**
	 * Renvoie les informations du raw / kpi (s'il existe) du GTM sur lequel se base le GIS
	 *
	 * @return array liste des informations du raw / kpi du GIS
	 */
	
	public function getGTMGisInformations()
	{
		$gis_infos		= array();
		$gis_infos_tmp	= array();
		
		$sql =	 " SELECT spc.id_elem, spc.class_object, spc.id_product"
				." FROM graph_information gi, sys_pauto_config spc"
				." WHERE gi.id_page = '".$this->idGTM."' AND gi.gis = 1 AND gi.gis_based_on = spc.id";
		
		$gis_infos_tmp = $this->database->getRow($sql);
		
		// 03/08/2009 GHX : Correction du BZ 10863
        // 10/06/2011 MPR : DEV GIS without Polygons Utilisation du Model GIS
        // 23/09/2011 OJT : Précision du produit pour le getGisMode
		$gisActived = GisModel::getGisMode( $gis_infos_tmp['id_product'] );
		
		if (count($gis_infos_tmp) > 0 && $gisActived) {
			
			// 02/02/2009 - Modif. benoit : on englobe 'id_ligne' dans des cotes afin de correspondre à la nouvelle structure de tables

			if ($gis_infos_tmp['class_object'] == "counter") 
			{
				// maj 14:59 27/01/2010 - MPR : Correction du bug 13772 : On ne passe plus en paramètre le label du raw/kpi
				$sql =	 " SELECT sfr.edw_field_name AS name, sfr.id_ligne, sfr.edw_group_table, sdgtr.family"
						." FROM sys_field_reference sfr, sys_definition_group_table_ref sdgtr"
						." WHERE sfr.id_ligne = '".$gis_infos_tmp['id_elem']."'"
						." AND sfr.edw_group_table = sdgtr.edw_group_table";			
			}
			else // "kpi"
			{
				$sql =	 " SELECT sdk.kpi_name AS name, sdk.id_ligne, sdk.edw_group_table, sdgtr.family"
						." FROM sys_definition_kpi sdk, sys_definition_group_table_ref sdgtr"
						." WHERE sdk.id_ligne = '".$gis_infos_tmp['id_elem']."'"
						." AND sdk.edw_group_table = sdgtr.edw_group_table";			
			}
	
			$db = Database::getConnection($gis_infos_tmp['id_product']);
	
			$gis_infos = $db->getRow($sql);	
			
			// 11/02/2009 - Modif; benoit : si le raw / kpi du gis est de type "counter", on change celui-ci dans le tableau résultant en "raw"

			$gis_type = (($gis_infos_tmp['class_object'] == "counter") ? "raw" : $gis_infos_tmp['class_object']);
			
			return array('id' => $gis_infos['id_ligne'], 'name' => $gis_infos['name'], 'label' => '', 'type' => $gis_type, 'family' => $gis_infos['family'], 'product' => $gis_infos_tmp['id_product']);
		}
		else 
		{
			return array();
		}
	}

	// 02/12/2008 - Modif. benoit : ajout de la méthode ci-dessous permettant de renvoyer des informations sur le raw / kpi de tri d'un GTM

	/**
	 * Retourne les informations sur le raw / kpi de tri du GTM
	 *
	 * @return array liste des informations du raw / kpi du GTM
	 */

	public function getGTMSortBy()
	{
		$sort_by		= Array();
		$sort_by_tmp	= Array();
		
		// 16:44 25/08/2009 GHX
		// Correctoin du BZ 11198
		// Récupération du data_legend
		// 11:04 28/08/2009 GHX
		// Re-correction du BZ 11195
		// Ajout d'une condition
		$sql =	 " SELECT spc.id_elem, gi.default_orderby, gi.pie_split_by, gi.object_type,"
				." gi.default_asc_desc,"
				." spc.id_product, spc.class_object,gd.data_legend"
				." FROM sys_pauto_config spc, graph_information gi, graph_data gd"
				." WHERE spc.id_page = gi.id_page AND gd.id_data = gi.default_orderby AND spc.id = gi.default_orderby  AND gi.id_page = '".$this->idGTM."' LIMIT 1";

		$sort_by_tmp = $this->database->getRow($sql);
		
		// Ajout du nom, du group_table et de la famille du raw / kpi de tri du GTM en fonction du produit auquel il est rattaché

		// 02/02/2009 - Modif. benoit : on englobe 'id_ligne' dans des cotes afin de correspondre à la nouvelle structure de tables

		if ($sort_by_tmp['class_object'] == "counter") 
		{
			$sql =	 " SELECT sfr.edw_field_name AS name, sfr.edw_field_name_label AS label, sfr.id_ligne, sfr.edw_group_table, sdgtr.family"
					." FROM sys_field_reference sfr, sys_definition_group_table_ref sdgtr"
					." WHERE sfr.id_ligne = '".$sort_by_tmp['id_elem']."'"
					." AND sfr.edw_group_table = sdgtr.edw_group_table";			
		}
		else // "kpi"
		{
			$sql =	 " SELECT sdk.kpi_name AS name, sdk.kpi_label AS label, sdk.id_ligne, sdk.edw_group_table, sdgtr.family"
					." FROM sys_definition_kpi sdk, sys_definition_group_table_ref sdgtr"
					." WHERE sdk.id_ligne = '".$sort_by_tmp['id_elem']."'"
					." AND sdk.edw_group_table = sdgtr.edw_group_table";			
		}

		$db = Database::getConnection($sort_by_tmp['id_product']);

		$sort_by = $db->getRow($sql);

		// 11/02/2009 - Modif; benoit : si le raw / kpi de tri est de type "counter", on change celui-ci dans le tableau résultant en "raw"

		$sort_by_type = (($sort_by_tmp['class_object'] == "counter") ? "raw" : $sort_by_tmp['class_object']);

		// 16:44 25/08/2009 GHX
		// Correctoin du BZ 11198
		// Récupération du data_legend
		// 09:12 06/11/2009 GHX
		// Ajout de l'ID elem
		return array('id' => $sort_by_tmp['id_elem'], 'name' => $sort_by['name'], 'label' => $sort_by_tmp['data_legend'], 'type' => $sort_by_type, 'family' => $sort_by['family'], 'product' => $sort_by_tmp['id_product'], 'asc_desc' => (($sort_by_tmp['default_asc_desc'] == 1) ? "ASC" : "DESC"));
	}

	// 18/12/2008 - Modif. benoit : ajout de la méthode ci-dessous qui retourne le raw / kpi de split d'un pie

	/**
	 * Retourne les informations sur le raw / kpi de split du GTM
	 *
	 * @return array liste des informations du raw / kpi du GTM
	 */

	public function getGTMSplitBy()
	{
		$split_by		= Array();
		$split_by_tmp	= Array();

		$debug = false;

		$this->database->setDebug($debug);
		
		// 11/02/2009 - Modif. benoit : correction de la requete. Les champs 'overnetwork_default_orderby' et 'overnetwork_default_asc_desc' n'existent plus

		/*$sql =	 " SELECT spc.id_elem, gi.overnetwork_default_orderby, gi.orderby, gi.object_type,"
				." gi.asc_desc, gi.overnetwork_default_asc_desc,"
				." spc.id_product, spc.class_object"
				." FROM sys_pauto_config spc, graph_information gi"
				." WHERE spc.id_page = gi.id_page AND gi.id_page = '".$this->idGTM."' LIMIT 1";*/

		$sql =	 " SELECT spc.id_elem, gi.pie_split_by, gi.pie_split_type, spc.id_product, spc.class_object"
				." FROM sys_pauto_config spc, graph_information gi"
				." WHERE spc.id_page = gi.id_page AND gi.id_page = '".$this->idGTM."' LIMIT 1";

		$split_by_tmp = $this->database->getRow($sql);

		$split_type = $split_by_tmp['pie_split_type'];

		// 09:58 07/05/2009 GHX
		// On récupère le label dans graph_data et non dans sys_definition_kpi ou sys_field_reference
		
		$sql =	 " SELECT id_elem, id_product, class_object, data_legend AS label"
				." FROM sys_pauto_config s, graph_information g, graph_data gd"
				." WHERE s.id = '".$split_by_tmp['pie_split_by']."' AND s.id_page = g.id_page AND gd.id_data = s.id";

		$split_by_tmp = $this->database->getRow($sql);
		
		// Ajout du nom, du group_table et de la famille du raw / kpi de tri du GTM en fonction du produit auquel il est rattaché

		// 02/02/2009 - Modif. benoit : on englobe 'id_ligne' dans des cotes afin de correspondre à la nouvelle structure de tables

		if ($split_by_tmp['class_object'] == "counter") 
		{
			$sql =	 " SELECT sfr.edw_field_name AS name, sfr.edw_field_name_label AS label, sfr.id_ligne, sfr.edw_group_table, sdgtr.family"
					." FROM sys_field_reference sfr, sys_definition_group_table_ref sdgtr"
					." WHERE sfr.id_ligne = '".$split_by_tmp['id_elem']."'"
					." AND sfr.edw_group_table = sdgtr.edw_group_table";			
		}
		else // "kpi"
		{
			$sql =	 " SELECT sdk.kpi_name AS name, sdk.kpi_label AS label, sdk.id_ligne, sdk.edw_group_table, sdgtr.family"
					." FROM sys_definition_kpi sdk, sys_definition_group_table_ref sdgtr"
					." WHERE sdk.id_ligne = '".$split_by_tmp['id_elem']."'"
					." AND sdk.edw_group_table = sdgtr.edw_group_table";			
		}

		$db = Database::getConnection($split_by_tmp['id_product']);

		$db->setDebug($debug);

		$split_by = $db->getRow($sql);

		// 11/02/2009 - Modif; benoit : si le raw / kpi de split est de type "counter", on change celui-ci dans le tableau résultant en "raw"

		$split_by_type = (($split_by_tmp['class_object'] == "counter") ? "raw" : $split_by_tmp['class_object']);

		// 11/05/2009 GHX
			// Ajout de l'id_ligne du kpi/raw
		return array('id' => $split_by_tmp['id_elem'], 'name' => $split_by['name'], 'label' => $split_by_tmp['label'], 'type' => $split_by_type, 'family' => $split_by['family'], 'product' => $split_by_tmp['id_product'], 'split_type' => $split_type);
	}

	// 04/12/2008 - Modif. benoit : ajout de la méthode ci-dessous qui retourne l'ensemble des propriétés d'un GTM

	/**
	 * Retourne l'ensemble des propriétés du GTM courant
	 *
	 * @return array propriétés du GTM
	 */

	public function getGTMProperties()
	{
		$gtm_pptes = array();
				
		$sql =	 " SELECT spc.id_elem, spc.class_object, spc.id_page, spc.ligne, spc.id_product, sppn.page_name, page_type, gd.*, gi.*"
				." FROM sys_pauto_config spc, sys_pauto_page_name sppn, graph_data gd, graph_information gi"
				." WHERE spc.id = gd.id_data AND sppn.id_page = spc.id_page"
				." AND gi.id_page = spc.id_page AND spc.id_page = '".$this->idGTM."'"
				." ORDER BY spc.ligne ASC";

		$pptes = $this->database->getAll($sql);

		// Tableau contenant les propriétés intrasèques du GTM
		
		// 16/02/2009 - Modif. benoit : ajout des propriétés 'definition' et 'troubleshooting'

		$intra_pptes = array('page_name', 'graph_width', 'graph_height', 'position_legende', 'ordonnee_left_name', 'ordonnee_right_name', 'object_type', 'scale', 'definition', 'troubleshooting');

		// Tableau recensant les informations propres aux données du GTM (raws/kpis)

		$data_pptes = array('class_object', 'display_type', 'line_design', 'color', 'filled_color', 'position_ordonnee', 'id_product', 'id_elem');

		$tmData = array();
		foreach($pptes as $values)
		{
			// Récupération des propriétés intrasèques du GTM

			for ($i=0; $i < count($intra_pptes); $i++) {
				if (!isset($gtm_pptes[$intra_pptes[$i]]))
				{
					$gtm_pptes[$intra_pptes[$i]] = $values[$intra_pptes[$i]];
				}
			}
			
			// Récupération des données propres aux raws/kpis du GTM

			for ($i=0; $i < count($data_pptes); $i++) {

				// 11/02/2009 - Modif. benoit : si le type de l'element du GTM est "counter", on remplace celui-ci par "raw"
				
				if ($data_pptes[$i] == "class_object" && $values[$data_pptes[$i]] == "counter") {
					$values[$data_pptes[$i]] = "raw";
				}

				if(  !in_array(strtolower($values['data_legend']), $tmData) )
					$gtm_pptes['data'][$values['data_legend']][$data_pptes[$i]] = $values[$data_pptes[$i]];
				
				// 14:51 17/08/2009 GHX
				// Création d'un sous-data dans le même style que data mais pas avec les mêmes clés en index
				$gtm_pptes['data2'][$values['id_elem']][$data_pptes[$i]] = $values[$data_pptes[$i]];
				
			}
			// Ajout de la légende
			$gtm_pptes['data2'][$values['id_elem']]['data_legend'] = $values['data_legend'];
			
			// On mémorise la légende en minuscule
			$tmData[] = strtolower($values['data_legend']);
		}
		
		// 11:05 17/08/2009 GHX
		$gtm_pptes['same_kpis'] = $this->getSameKpis(); 
		$gtm_pptes['same_raws'] = $this->getSameRaws(); 
		
		return $gtm_pptes;
	}

	/**
	 * Retourne un tableau avec les raw/kpi ayant un lien vers AA. Si aucun élément le tableau retourné est vide
	 *
	 * Format du tableau vide retourné :
	 *	array(
	 *		'raw' => array(),
	 *		'kpi' => array()
	 *	)
	 *
	 * Format du tableau non videretourné :
	 *	array(
	 *		'raw' => array(
	 *				id_raw => array( 'idProduct' => id_product, 'family' => family)
	 *				...
	 *			),
	 *		'kpi' => array(
	 *				id_kpi =>array( 'idProduct' => id_product, 'family' => family),
	 *				...
	 *			)
	 *	)
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @return array
	 */
	public function getInfoLinkToAA ()
	{
		$linkToAA = array('raw' => array(), 'kpi' => array());
		
		// RAW
		$queryInfoLinkToAA = "
				SELECT 
					id_ligne,
					saafk_label_link,
					saafk_idkpi
				FROM 
					sys_aa_filter_kpi,
					sys_field_reference
				WHERE 
					saafk_family = '%s'
					AND saafk_type = '%s'
					AND lower(edw_field_name) = lower(saafk_idkpi) 
					AND lower(edw_field_name) IN (%s)
			";
			
		$rawsByProduct = $this->getGtmRawsByProduct();
		
		if ( count($rawsByProduct) > 0 )
		{
			foreach ( $rawsByProduct as $idProduct => $rawsByFamily )
			{
				$db = Database::getConnection($idProduct);
				foreach ( $rawsByFamily as $family => $raws )
				{
					$resultInfoLinkToAA = $db->execute(sprintf($queryInfoLinkToAA,
							$family,
							'raw',
							implode(", ", array_map(array($this, 'labelizeValue'), array_map('strtolower',array_keys($raws))))
						));
						
					if ( $db->getNumRows() > 0 )
					{
						while ( $row = $db->getQueryResults($resultInfoLinkToAA, 1) )
						{
							$linkToAA['raw'][$row['id_ligne']] = array('idProduct' => $idProduct, 'family' => $family, 'labelAA' => $row['saafk_label_link'], 'saafk_idkpi' => $row['saafk_idkpi']);
						}
					}
				}
			}
		}
		
		// KPI 
		$queryInfoLinkToAA = "
				SELECT 
					id_ligne,
					saafk_label_link,
					saafk_idkpi
				FROM 
					sys_aa_filter_kpi,
					sys_definition_kpi
				WHERE 
					saafk_family = '%s'
					AND saafk_type = '%s'
					AND lower(kpi_name) = lower(saafk_idkpi) 
					AND lower(kpi_name) IN (%s)
			";
		
		$kpisByProduct = $this->getGtmKpisByProduct();
		
		if ( count($kpisByProduct) > 0 )
		{
			foreach ( $kpisByProduct as $idProduct => $kpisByFamily )
			{
				$db = Database::getConnection($idProduct);
				foreach ( $kpisByFamily as $family => $kpis )
				{
					$resultInfoLinkToAA = $db->execute(sprintf($queryInfoLinkToAA,
							$family,
							'kpi',
							implode(", ", array_map(array($this, 'labelizeValue'), array_map('strtolower',array_keys($kpis))))
						));
						
					if ( $db->getNumRows() > 0 )
					{
						while ( $row = $db->getQueryResults($resultInfoLinkToAA, 1) )
						{
							$linkToAA['kpi'][$row['id_ligne']] = array('idProduct' => $idProduct, 'family' => $family, 'labelAA' => $row['saafk_label_link'], 'saafk_idkpi' => $row['saafk_idkpi']);
						}
					}
				}
			}
		}
		return $linkToAA;
	} // End function getInfoLinkToAA
	
	/**
	 * Permet de "labeliser" une valeur cad de l'entourer de quotes
	 *
	 * @param numeric $value la valeur à labeliser
	 * @return string la valeur labelisée
	 */

	private function labelizeValue($value)
	{
		return "'".$value."'";
	}
	
	/**
	 * Retourne le dernier commentaire sur le graphe
	 *
	 * @author BBX 31/07/2009
	 * @return string le commentaire le plus récent
	 */
	public function getLastComment()
	{
		// Query
		$query = "SELECT libelle_comment FROM edw_comment
		WHERE id_elem = '{$this->idGTM}'
		AND type_elem = 'graph'
		ORDER BY id_comment DESC
		LIMIT 1";
		// Retour du résultat
		return $this->database->getOne($query);
	}
	
	/**
	 * Retourne la liste de tous les graphes
	 *
	 * @param int $idProduct identifiant du produit (default master product)
	 * @return array
	 */
	public static function getAll ( $idProduct = '')
	{
		$db = Database::getConnection($idProduct);
		
		$query = "
			SELECT
				* 
			FROM 
				sys_pauto_page_name AS sppn,
				sys_pauto_config AS spc,
				graph_information AS gi,
				graph_data AS gd
			WHERE
				sppn.page_type = 'gtm'
				AND sppn.id_page = spc.id_page
				AND sppn.id_page = gi.id_page
				AND spc.id = gd.id_data
			ORDER BY
				sppn.page_name ASC,
				spc.ligne ASC
			";
		
		$allGtm = array();
		$result = $db->execute($query);
		
		$infoGraph =  array(
					// sys_pauto_page_name
					'page_name' => null,
					'droit' => null,
					'id_user' => null,
					'share_it' => null,
					// graph_information
					'ordonnee_left_name' => null,
					'ordonnee_right_name' => null,
					'graph_height' => null,
					'graph_width' => null,
					'position_legende' => null,
					'object_type' => null,
					'gis smallint' => null,
					'gis_based_on' => null,
					'is_configure' => null,
					'troubleshooting' => null,
					'definition' => null,
					'scale' => null,
					'pie_split_type' => null,
					'pie_split_by' => null,
					'default_orderby' => null,
					'default_asc_desc' => null
				);
		$infoElement = array (
					// sys_pauto_config
					'id_elem' => null,
					'class_object' => null,
					'ligne' => null,
					'id_product' => null,
					// graph_data
					'data_legend' => null,
					'position_ordonnee' => null,
					'display_type' => null,
					'line_design' => null,
					'color' => null,
					'filled_color' => null
				);
		if ( $db->getNumRows() > 0 )
		{
			while ( $row = $db->getQueryResults($result, 1) )
			{
				// Info sur le graphe
				if ( !array_key_exists($row['id_page'], $allGtm) )
					$allGtm[$row['id_page']] = array_intersect_key($row, $infoGraph);
				
				// Info sur la liste des éléments qui compose le graphe
				$allGtm[$row['id_page']]['elements'][$row['id']] = array_intersect_key($row, $infoElement);
			}
		}
		
		return $allGtm;
	} // End function getAll
	
	/**
	 * Retourne la liste de tous les graphes ayant un élément qui appartient à un produit spécifique
	 *
	 * @param int $containsIdProduct identifiant du produit donc les graphes doivent avoir un élément
	 * @param boolean $multiProducts TRUE si les graphes multiproduit doivent être prise en compte (default TRUE)
	 * @param int $idProductDb identifiant d'un produit (default master product)
	 * @param array
	 */
	public static function getAllContainsIdProduct ( $containsIdProduct, $multiProducts = true, $idProductDb = '' )
	{
		$query="
			SELECT
				* 
			FROM 
				sys_pauto_page_name AS sppn,
				sys_pauto_config AS spc,
				graph_information AS gi,
				graph_data AS gd
			WHERE
				page_type = 'gtm'
				AND sppn.id_page = spc.id_page
				AND sppn.id_page = gi.id_page
				AND spc.id = gd.id_data
				AND sppn.id_page IN (
					SELECT 
						spc.id_page
					FROM 
						sys_pauto_page_name AS sppn,
						sys_pauto_config AS spc
					WHERE
						sppn.id_page = spc.id_page
						AND sppn.page_type = 'gtm'
						AND spc.id_page IN (SELECT id_page FROM sys_pauto_config WHERE id_product = {$containsIdProduct})
					GROUP BY spc.id_page
					".( $multiProducts ? '' : 'HAVING count(distinct id_product) = 1')."
				)
			ORDER BY
				sppn.page_name ASC,
				spc.ligne ASC
			";
		
		$infoGraph =  array(
					// sys_pauto_page_name
					'page_name' => null,
					'droit' => null,
					'id_user' => null,
					'share_it' => null,
					// graph_information
					'ordonnee_left_name' => null,
					'ordonnee_right_name' => null,
					'graph_height' => null,
					'graph_width' => null,
					'position_legende' => null,
					'object_type' => null,
					'gis smallint' => null,
					'gis_based_on' => null,
					'is_configure' => null,
					'troubleshooting' => null,
					'definition' => null,
					'scale' => null,
					'pie_split_type' => null,
					'pie_split_by' => null,
					'default_orderby' => null,
					'default_asc_desc' => null
				);
		$infoElement = array (
					// sys_pauto_config
					'id_elem' => null,
					'class_object' => null,
					'ligne' => null,
					'id_product' => null,
					// graph_data
					'data_legend' => null,
					'position_ordonnee' => null,
					'display_type' => null,
					'line_design' => null,
					'color' => null,
					'filled_color' => null
				);
				
		$allGtm = array();
		// 09:25 28/10/2009 GHX
		// On passait le mauvais paramètre
                // 21/12/2010 BBX
                // Il faut requêter sur le master !!
                // BZ 18510
		$db = Database::getConnection(ProductModel::getIdMaster());
		$result = $db->execute($query);
		if ( $db->getNumRows() > 0 )
		{
			while ( $row = $db->getQueryResults($result, 1) )
			{
				// Info sur le graphe
				if ( !array_key_exists($row['id_page'], $allGtm) )
					$allGtm[$row['id_page']] = array_intersect_key($row, $infoGraph);
				// Info sur la liste des éléments qui compose le graphe
				$allGtm[$row['id_page']]['elements'][$row['id']] = array_intersect_key($row, $infoElement);
			}
		}
		
		return $allGtm;
	} // End function getAllContainsIdProduct
	
	
	/**
	 * Retourne la liste de tous les graphes ayant un élément qui appartient à un produit spécifique
	 *
	 * @param int $containsIdProduct identifiant du produit donc les graphes doivent avoir un élément
	 * @param boolean $multiProducts TRUE si les graphes multiproduit doivent être prise en compte (default TRUE)
	 * @param int $idProductDb identifiant d'un produit (default master product)
	 * @param array
	 */
	public static function getAllContainsIdFamily ( $containsIdFamily, $idProductFamily, $multiProducts = true, $idProductDb = '' )
	{
		// On récupère la liste de tous les ID des raws et kpis de la famille
		$db = Database::getConnection($idProductFamily);
		$querySelectRawKpi = "
			SELECT 'raw' AS type, id_ligne FROM sys_field_reference where edw_group_table = (SELECT edw_group_table FROM sys_definition_group_table WHERE family = '{$containsIdFamily}')
			UNION
			SELECT 'kpi' AS type, id_ligne FROM sys_definition_kpi WHERE edw_group_table = (SELECT edw_group_table FROM sys_definition_group_table WHERE family = '{$containsIdFamily}')
		";
		$resultSelectRawKpi = $db->execute($querySelectRawKpi);
		if ( $db->getNumRows() == 0 )
			return array();
			
		$list = array();
		while ( $row = $db->getQueryResults($resultSelectRawKpi, 1) )
		{
			$list[$row['type']][] = $row['id_ligne'];
		}
		$subQuery = '';
		if ( array_key_exists('raw', $list) )
		{
			$subQuery = " (class_object = 'counter' AND id_elem IN ('".implode("','",$list['raw'])."')) ";
		}
		if ( array_key_exists('kpi', $list) )
		{
			if ( array_key_exists('raw', $list) )
				$subQuery .= " OR ";
			$subQuery .= " (class_object = 'kpi' AND id_elem IN ('".implode("','",$list['kpi'])."')) ";
		}
		
		$query="
			SELECT
				* 
			FROM 
				sys_pauto_page_name AS sppn,
				sys_pauto_config AS spc,
				graph_information AS gi,
				graph_data AS gd
			WHERE
				page_type = 'gtm'
				AND sppn.id_page = spc.id_page
				AND sppn.id_page = gi.id_page
				AND spc.id = gd.id_data
				AND sppn.id_page IN (
					SELECT 
						spc.id_page
					FROM 
						sys_pauto_page_name AS sppn,
						sys_pauto_config AS spc
					WHERE
						sppn.id_page = spc.id_page
						AND sppn.page_type = 'gtm'
						AND spc.id_page IN (
								SELECT id_page 
								FROM sys_pauto_config 
								WHERE {$subQuery}
							)
					GROUP BY spc.id_page
					".( $multiProducts ? '' : 'HAVING count(distinct id_product) = 1')."
				)
			ORDER BY
				sppn.page_name ASC,
				spc.ligne ASC
			";
		
		$infoGraph =  array(
					// sys_pauto_page_name
					'page_name' => null,
					'droit' => null,
					'id_user' => null,
					'share_it' => null,
					// graph_information
					'ordonnee_left_name' => null,
					'ordonnee_right_name' => null,
					'graph_height' => null,
					'graph_width' => null,
					'position_legende' => null,
					'object_type' => null,
					'gis smallint' => null,
					'gis_based_on' => null,
					'is_configure' => null,
					'troubleshooting' => null,
					'definition' => null,
					'scale' => null,
					'pie_split_type' => null,
					'pie_split_by' => null,
					'default_orderby' => null,
					'default_asc_desc' => null
				);
		$infoElement = array (
					// sys_pauto_config
					'id_elem' => null,
					'class_object' => null,
					'ligne' => null,
					'id_product' => null,
					// graph_data
					'data_legend' => null,
					'position_ordonnee' => null,
					'display_type' => null,
					'line_design' => null,
					'color' => null,
					'filled_color' => null
				);
				
		$allGtm = array();
		$db = Database::getConnection($idProductDb);
		$result = $db->execute($query);
		if ( $db->getNumRows() > 0 )
		{
			while ( $row = $db->getQueryResults($result, 1) )
			{
				// Info sur le graphe
				if ( !array_key_exists($row['id_page'], $allGtm) )
					$allGtm[$row['id_page']] = array_intersect_key($row, $infoGraph);
				// Info sur la liste des éléments qui compose le graphe
				$allGtm[$row['id_page']]['elements'][$row['id']] = array_intersect_key($row, $infoElement);
			}
		}
		
		return $allGtm;
	} // End function getAllContainsIdFamily
	
	/**
	 * Supprime un graphe. Il n'est supprimé uniquement s'il n'est pas utilisé comme sort by dans un dashboard
	 * Retourne TRUE si la suppression a été faite sinon un message d'erreur
	 *
	 * @author GHX
	 * @version CB 5.0.0.06
	 * @since CB 5.0.0.06
	 * @return boolean
	 */
	public function delete ()
	{
		// vérife que le graph est supprimable (ie: il n'est pas utilisé par un dashboard)
		$query = "SELECT id_page FROM sys_pauto_config WHERE id_elem= '{$this->idGTM}' AND class_object='graph'";
		$dashboards = $this->database->getall($query);
		if ($dashboards)
			return __T('G_GDR_BUILDER_YOU_CANNOT_DELETE_THAT_GTM_AS_IT_BELONGS_TO_SOME_DASHBOARDS');

		// on supprime les data des courbes du graph
		$query = "DELETE FROM graph_data WHERE id_data IN (SELECT id FROM sys_pauto_config WHERE id_page='{$this->idGTM}')";
		$this->database->execute($query);

		// on supprime les courbes du graph
		$query = "DELETE FROM sys_pauto_config WHERE id_page='{$this->idGTM}'";
		$this->database->execute($query);

		// on supprime les infos de graph_information
		$query = "DELETE FROM graph_information WHERE id_page='{$this->idGTM}'";
		$this->database->execute($query);

		// on supprime enfin le graph dans sys_pauto_page_name
		$query = "DELETE FROM sys_pauto_page_name WHERE id_page='{$this->idGTM}'";
		$this->database->execute($query);

		return true;
	} // End function delete
	
	/**
	 * Duplique un graphe et retourne son nouvel identifiant
	 *
	 * @author GHX
	 * @throw Exception
	 * @param string $newId : identifiant du nouveau graphe ou génère un identifiant aléatoire si le paramètre vaut "auto" (default auto)
	 * @param string $newName : nom du nouveau graphe dupliqué, la chaine est évalué avec sprintf, %s répresente le nom du graphe à dupliquer (default copy of %s)
	 * @param string $newNameMulti : nom du nouveau graphe dupliqué dans le cas ou le premier (cf. $newName) est déjà utilisé, la chaine est évalué avec sprintf, %s répresente le nom du graphe à dupliquer et %d représente le nombre de fois que le nom est utilisé (default copy %d of %s)
	 * @param string $droit : droit du graphe 'client', 'customisateur' ou 'auto' si la valeur est auto le nouveau graphe car les mêmes droit (default customisateur)
	 * @param string $idUser : si le droit du nouveau graphe est 'client', il faut préciser l'id user (default null)
	 * @return string
	 */
	public function duplicate ( $newId = 'auto', $newName = 'copy of %s', $newNameMulti = 'copy %d of %s', $droit = 'customisateur', $idUser = null )
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
		
		// on compose le nouveau nom
		$properties = $this->getGTMProperties();
		$i = 1;
		$newName = str_replace(array("'", '"'),' ',sprintf($newName, $properties['page_name']));
		if ( empty($newNameMulti) )
		{
			$newNameMulti = 'copy %d of %s';
		}
		while ( $this->database->getOne("SELECT id_page FROM sys_pauto_page_name WHERE page_name='".$newName."'") )
		{
			$i++;
			$newName = str_replace(array("'", '"'),' ',sprintf($newNameMulti, $i, $properties['page_name']));
			if ( $i > 10 )
			{
				$newName = str_replace(array("'", '"'),' ',sprintf('copy %d of %s', $i, $properties['page_name']));
			}
			if ( $i >= 15 ) // Par mesure de précaution si on ne trouve pas de nom valide pour le nouveau graphe
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
		
		$querySPPN = "
			INSERT INTO sys_pauto_page_name (
				id_page,
				page_name,
				droit,
				page_type,
				id_user,
				share_it
			)
			VALUES (
				'$newId',
				'$newName',
				'$droit',
				'gtm',
				".(empty($idUser) ? "NULL" : "'".$idUser."'").",
				0
			)";
		$this->database->execute($querySPPN);
		
		// maintenant il faut copier les infos dans graph_information
		$queryGI = "SELECT * FROM graph_information WHERE id_page='".$this->idGTM."'";
		$grinfo = $this->database->getRow($queryGI);
		$grinfo['id_page'] = $newId;
		
		// maintenant on va copier toutes les courbes du graph (qui sont dans sys_pauto_config)
		$querySPC = "SELECT * FROM sys_pauto_config WHERE id_page='".$this->idGTM."'";
		$data = $this->database->getAll($querySPC);
		if ( count($data) > 0)
		{
			foreach ( $data as $d )
			{
				// on archive l'id du raw/kpi
				$old_id = $d['id'];
				$next_id = generateUniqId('sys_pauto_config');
				// on change id et id_page du raw/kpi
				$d['id']      = $next_id;
				$d["id_page"] = $newId;
				// on insert la courbe de la copie dans sys_pauto_config
				$this->database->AutoExecute('sys_pauto_config',$d,'INSERT');
				// pour cette courbe, on va chercher les infos dans graph_data
				$queryGD = "SELECT * FROM graph_data WHERE id_data= '".$old_id."'";
				$graph_data = $this->database->getRow($queryGD);
				// on change le id_data en mettant le next_id
				$graph_data['id_data'] = $next_id;
				// on copie les infos de la courbe dans graph_data
				$this->database->AutoExecute('graph_data',$graph_data,'INSERT');
				
				// on regarde si cette courbe servait dans le order by du pie
				if ($grinfo["pie_split_by"] == $old_id) $grinfo["pie_split_by"] = $next_id;
				// on regarde si cette courbe servait dans le GIS
				if ($grinfo["gis_based_on"] == $old_id) $grinfo["gis_based_on"] = $next_id;
				// on copie la valeur default_orderby si besoin
				if ($grinfo["default_orderby"] == $old_id) $grinfo["default_orderby"] = $next_id;
				// on copie la valeur default_asc_desc si besoin
				if ($grinfo["default_asc_desc"] == $old_id) $grinfo["default_asc_desc"] = $next_id;
			}
		}

		// on peut ajouter les infos du graph (maintenant qu'on a fait les modifs de liaison "pie_split_by" et "gis_based_on" en passant sur toutes les courbes
		if (!$grinfo["pie_split_by"]) unset($grinfo["pie_split_by"]);
		if (!$grinfo["gis_based_on"]) unset($grinfo["gis_based_on"]);
		if (!$grinfo["default_orderby"]) unset($grinfo["default_orderby"]);
		if (!$grinfo["default_asc_desc"]) unset($grinfo["default_asc_desc"]);

		$grinfo['troubleshooting'] = str_replace("'", "\'", $grinfo['troubleshooting']);
		$grinfo['definition'] = str_replace("'", "\'", $grinfo['definition']);

		$this->database->AutoExecute('graph_information',$grinfo,'INSERT');
		
		return $newId;
	} // End function duplicate
	
	/**
	 * Supprime un élément RAW ou KPI du graphe
	 *
	 * @author GHX
	 * @param string $idElem : identifiant de l'élément raw ou kpi
	 * @param string $type : type de l'élément raw ou kpi, il est possible d'utiliser counter à la place de raw
	 * @param string $id : identifiant de l'élément dans la table sys_pauto_config, si cette valeur est préciser les 2 premiers paramètres ne sont pas pris en compte (default null)
	 */
	public function removeElement ( $idElem, $type, $id = null )
	{
		if ( $id == null )
		{
			$type = strtolower($type);
			$type = ( $type == 'raw' ? 'counter' : $type);
			
			if ( $type != 'counter' && $type != 'kpi' )
				return false;
			
			// supprime la ligne dans graph_data
			$queryGD = "
				DELETE FROM graph_data 
				WHERE id_data = (
					SELECT id 
					FROM sys_pauto_config 
					WHERE 
						class_object = '".$type."' 
						AND id_elem = '".$idElem."'
						AND id_page = '".$this->idGTM."'
				)";

			// supprime la ligne dans sys_pauto_config
			$querySPC = "
				DELETE FROM sys_pauto_config 
				WHERE 
					class_object = '".$type."' 
					AND id_elem = '".$idElem."'
					AND id_page = '".$this->idGTM."'
				";
		}
		else
		{
			// supprime la ligne dans graph_data
			$queryGD = "DELETE FROM graph_data WHERE id_data = '".$id."'";

			// supprime la ligne dans sys_pauto_config
			$querySPC = "DELETE FROM sys_pauto_config WHERE id= '".$id."'";
		}
		
		$this->database->execute($queryGD);
		$this->database->execute($querySPC);
		
		$this->updateElementsDefault();
	} // End function removeElement
	
	/**
	 * Ajout un élément de type RAW ou KPI dans le graphe
	 *
	 * @author GHX
	 * @param string $idElem : identifiant de l'élément raw ou kpi
	 * @param string $type : type de l'élément raw ou kpi, il est possible d'utiliser counter à la place de raw
	 * @param int $idProduct : identifiant du produit sur lequel se trouve l'élément raw ou kpi
	 * @param string $dataLegend : légende de l'élément si celui vaut null, la légende correspondra au label de l'élément (default null)
	 * @param string $positionOrdonnee : position de l'élément sur le graphe left ou right (default left)
	 * @param string $displayType : type de réprésentation des éléments sur le graphe line, bac, cumulatedline, cumulatedbar (default line)
	 * @param string $lineDesign : type de réprésentation d'un plot pour une ligne square, circle ou none (default square)
	 * @param string $color : couleur de l'élément sur le graphe (default #FFFFFF)
	 * @param string $filledColor : couleur de remplissage de l'élément sur le graphe avec le pourcentage de transparance (default #1414E4@0.5)
	 */
	public function addElement ( $idElem, $type, $idProduct, $dataLegend = null, $positionOrdonnee = 'left', $displayType = 'line' , $lineDesign = 'square', $color = '#FFFFFF', $filledColor = '#1414E4@0.5')
	{
		$type = strtolower($type);
		$type = ( $type == 'raw' ? 'counter' : $type);
		
		if ( $type != 'counter' && $type != 'kpi' )
			return false;

		// on verifie que ce raw/kpi n'est pas déjà dans le graph
		$query = "
			SELECT
				id
			FROM sys_pauto_config
			WHERE
				id_elem = '".$idElem."'
				AND	id_product= ".$idProduct."
				AND	id_page= '".$this->idGTM."'
				AND	class_object= '".$type."' 
		";
		$check_data = $this->database->getone($query);
		if (!empty($check_data))
		{
			throw new Exception(__T('G_GDR_BUILDER_ERROR_THIS_IS_ALREADY_INSIDE_THAT_GTM',$type));
		}

		// on va chercher la valeur max de ligne pour les courbes actuellement dans le graph
		$queryNextLine = "SELECT ligne FROM sys_pauto_config WHERE id_page = '".$this->idGTM."' ORDER BY ligne DESC LIMIT 1";
		$ligne = intval($this->database->getone($queryNextLine));
		$ligne++;

		$next_id = generateUniqId('sys_pauto_config');

		// on insert le plot
		$querySPC = "
			INSERT INTO sys_pauto_config (
				id,
				id_elem,
				class_object,
				id_page,
				ligne,
				id_product
			)
			VALUES (
				'".$next_id."',
				'".$idElem."',
				'".$type."',
				'".$this->idGTM."',
				".$ligne.",
				'".$idProduct."'
			)";
		$this->database->execute($querySPC);

		// Vérifie que la valeur de position ordonnée est correcte left ou right
		$positionOrdonnee = trim(strtolower($positionOrdonnee));
		if ( !in_array($positionOrdonnee, array('left', 'right')) ) $positionOrdonnee = 'left';
		
		// Vérifie que le type de courbe est correcte
		$displayType = trim(strtolower(str_replace(' ', '', $displayType)));
		if ( !in_array($displayType, array('line', 'bar', 'cumulatedline', 'cumulatedbar')) ) $displayType = 'line';
		
		//  Vérifie que le type de plot est correct 
		$lineDesign = trim(strtolower($lineDesign));
		if ( !in_array($lineDesign, array('none', 'square', 'circle')) ) $lineDesign = 'line';
		
		if ( $dataLegend == null )
		{
			// on a besoin de la data_legend 
			if ( $type == 'counter' )
			{
				$query = "SELECT CASE WHEN edw_field_name_label IS NOT NULL THEN edw_field_name_label ELSE edw_field_name END FROM sys_field_reference WHERE id_ligne = '".$idElem."'";
			}
			else
			{
				$query  = "SELECT CASE WHEN kpi_label IS NOT NULL THEN kpi_label ELSE kpi_name END FROM sys_definition_kpi WHERE id_ligne = '".$idElem."'";
			}
			$db_temp = Database::getConnection($idProduct);
			$dataLegend = $db_temp->getOne($query);
		}
		
		// on insert les valeurs par défaut dans graph_data
		$queryGA = "
			INSERT INTO graph_data (
				id_data,
				data_legend,
				position_ordonnee,
				display_type,
				line_design,
				color,
				filled_color
			)
			VALUES (
				'".$next_id."',
				'".$dataLegend."',
				'".$positionOrdonnee."',
				'".$displayType."',
				'".$lineDesign."',
				'".$color."',
				'".$filledColor."'
			)";
		$this->database->execute($queryGA);
		
		$this->updateElementsDefault();
	} // End function addElement
	
	/**
	 * Vérifie que le sort by par défault, le split by par défaut (cas PIE) et que le GIS par défaut sont corrects si c'est pas le cas
	 * on considère que c'est le premier élément du graphe la bonne valeur
	 *
	 * @author GHX
	 */
	public function updateElementsDefault ()
	{
		// Récupère le premier élément du graphe
		$firstElementFound = $this->database->getOne("
			SELECT
				id
			FROM 
				sys_pauto_config
			WHERE
				id_page = '".$this->idGTM."'
			ORDER BY
				ligne ASC
			LIMIT 1
		");
		$firstElementFound = ($firstElementFound ? "'".$firstElementFound."'" : "NULL");
		
		$querySortByDefault = "
			SELECT
				COUNT(*)
			FROM
				graph_information AS ga,
				sys_pauto_config AS spc 
			WHERE
				ga.id_page = '".$this->idGTM."'
				AND ga.default_orderby = spc.id
		";
		// Si la requete retourne zéro c'est que le sort by n'est plus le bon
		if ( $this->database->getOne($querySortByDefault) == 0 )
		{
			// On définit donc le premier élément du graphe comme le sort by par défaut
			$this->database->execute("UPDATE graph_information SET default_orderby = ".$firstElementFound.", default_asc_desc = CASE WHEN default_asc_desc IS NULL THEN 0 ELSE default_asc_desc END WHERE id_page = '".$this->idGTM."'");
		}
		
		// Si le GIS est activé
		if ( $this->database->getOne("SELECT gis FROM graph_information WHERE id_page = '".$this->idGTM."'") == 1)
		{
			$queryGisDefault = "
				SELECT
					COUNT(*)
				FROM
					graph_information AS ga,
					sys_pauto_config AS spc 
				WHERE
					ga.id_page = '".$this->idGTM."'
					AND ga.gis_based_on = spc.id
			";
			// Si la requete retourne zéro c'est que le sort by n'est plus le bon
			if ( $this->database->getOne($queryGisDefault) == 0 )
			{
				// On définit donc le premier élément du graphe comme le sort by par défaut
				$this->database->execute("UPDATE graph_information SET gis_based_on = ".$firstElementFound." WHERE id_page = '".$this->idGTM."'");
			}
		}
		else
		{
			$this->database->execute("UPDATE graph_information SET gis_based_on = NULL WHERE id_page = '".$this->idGTM."'");
		}
		
		// On vérifie si le graph est de type PIE si oui ...
		if ( $this->getGTMType() == 'pie3D' )
		{
			// on vérifie que le split by définit par défaut est correct
			$querySplitBy = "
				SELECT
					COUNT(*)
				FROM
					graph_information AS ga,
					sys_pauto_config AS spc 
				WHERE
					ga.id_page = '".$this->idGTM."'
					AND ga.pie_split_by = spc.id
			";
			if ( $this->database->getOne($querySplitBy) == 0 )
			{
				// On définit donc le premier élément du graphe comme le splut by par défaut
				// si le type de split est vide on le redéfinit sur le split premier axe
				$this->database->execute("UPDATE graph_information SET pie_split_by = ".$firstElementFound.", CASE WHEN pie_split_type IS NULL THEN 'first_axis' ELSE pie_split_type END WHERE id_page = '".$this->idGTM."'");
			}
		}
	} // End function updateElementsDefault
	
	/**
	 * Définit le sort by par défaut du graphe, si celui-ci est incorrect le premier élément du graphe sera définit comme le sort by par défaut
	 *
	 *	ATTENTION : l'élément doit déjà être présent dans le graphe
	 *
	 * @author GHX
	 * @param string $idElem : identifiant du raw/kpi
	 * @param string $type : type de l'élément "raw" ou "kpi", il est possible d'utiliser "counter" au lieu de "raw"
	 * @param int $idProduct : identifiant du produit sur lequel se trouve l'élément
	 * @param string $orderBy : ordre de tri pour le sort by "asc" ou "desc" ou alors respectivement 1 et 0 (default desc)
	 */
	public function setSortByDefault ( $idElem, $type, $idProduct, $orderBy = 'desc' )
	{
		$type = trim(strtolower($type));
		$type = ( $type == 'raw' ? 'counter' : $type );
		
		// Si la type de sort by n'est pas bon on prend "desc" par défaut (évite des erreurs)
		if ( is_numeric($orderBy) )
		{
			// Si la valeur est incorrect, on définit le sort by DESC par défaut
			if ( $orderBy != 0 && $orderBy != 1 ) $orderBy = 0;
		}
		else
		{
			$orderBy = trim(strtolower($orderBy));
			// Si la valeur est incorrect, on définit le sort by DESC par défaut
			if ( !in_array($positionOrdonnee, array('desc', '0', 'asc', '1')) ) $orderBy = 'desc';
			// Convertir la valeur en numéric
			$orderBy = ( $orderBy == 'desc' || $orderBy == '0' ? 0 : 1 );
		}
		
		$queryGI = "
			UPDATE
				graph_information
			SET 
				default_orderby = spc.id,
				default_asc_desc = ".$orderBy."
			FROM
				sys_pauto_config AS spc
			WHERE
				spc.id_elem = '".$idElem."'
				AND spc.class_object = '".$type."'
				AND spc.id_product = ".$idProduct."
				AND spc.id_page = '".$this->idGTM."'
				AND graph_information.id_page = '".$this->idGTM."'
			";
		$this->database->execute($queryGI);
		
		// Dans le cas ou le nouveau sort n'a pas bien été définit on vérifie qu'on a bien un sort by pour éviter de faire planter la restitution
		if ( $this->database->getAffectedRows() == 0 )
		{
			$this->updateElementsDefault();
		}
	} // End function setSortByDefault
	
	/**
	 * Définit le split by par défaut du graphe pour le split sur le premier axe, si celui-ci est incorrect le premier élément du graphe sera définit comme le split by par défaut
	 *
	 *	ATTENTION : l'élément doit déjà être présent dans le graphe
	 *
	 * @author GHX
	 * @param string $idElem : identifiant du raw/kpi
	 * @param string $type : type de l'élément "raw" ou "kpi", il est possible d'utiliser "counter" au lieu de "raw"
	 * @param int $idProduct : identifiant du produit sur lequel se trouve l'élément
	 */
	public function setSplitFirstAxisByDefault ( $idElem, $type, $idProduct )
	{
		$type = trim(strtolower($type));
		$type = ( $type == 'raw' ? 'counter' : $type );
		
		// 11:55 23/11/2009 GHX
		// Correction du BZ 12977
		$queryGI = "
			UPDATE
				graph_information
			SET 
				pie_split_by = spc.id
			FROM
				sys_pauto_config AS spc
			WHERE
				spc.id_elem = '".$idElem."'
				AND spc.class_object = '".$type."'
				AND spc.id_product = ".$idProduct."
				AND spc.id_page = '".$this->idGTM."'
				AND graph_information.id_page = '".$this->idGTM."'
			";
		$this->database->execute($queryGI);
		
		// Dans le cas ou le nouveau split n'a pas bien été définit on vérifie qu'on a bien un split by pour éviter de faire planter la restitution
		if ( $this->database->getAffectedRows() == 0 )
		{
			$this->updateElementsDefault();
		}
	} // End function setSplitFirstAxisByDefault
	
	/**
	 * Définit l'élément GIS par défaut du graphe, si celui-ci est incorrect le premier élément du graphe sera définit comme le GIS par défaut
	 *
	 *	ATTENTION : l'élément doit déjà être présent dans le graphe
	 *
	 * @author GHX
	 * @param string $idElem : identifiant du raw/kpi
	 * @param string $type : type de l'élément "raw" ou "kpi", il est possible d'utiliser "counter" au lieu de "raw"
	 * @param int $idProduct : identifiant du produit sur lequel se trouve l'élément
	 */
	public function setGisByDefault ( $idElem, $type, $idProduct )
	{
		$type = trim(strtolower($type));
		$type = ( $type == 'raw' ? 'counter' : $type );
		
		// 11:55 23/11/2009 GHX
		// Correction du BZ 12977
		$queryGI = "
			UPDATE
				graph_information
			SET 
				gis_based_on = spc.id
			FROM
				sys_pauto_config AS spc
			WHERE
				spc.id_elem = '".$idElem."'
				AND spc.class_object = '".$type."'
				AND spc.id_product = ".$idProduct."
				AND spc.id_page = '".$this->idGTM."'
				AND graph_information.id_page = '".$this->idGTM."'
			";
		$this->database->execute($queryGI);
		
		// Dans le cas ou le nouveau split n'a pas bien été définit on vérifie qu'on a bien un split by pour éviter de faire planter la restitution
		if ( $this->database->getAffectedRows() == 0 )
		{
			$this->updateElementsDefault();
		}
	} // End function setSplitFirstAxisByDefault
}