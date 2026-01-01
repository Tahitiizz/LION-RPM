<?php
/*
	09/04/2009 GHX
		- Ajout d'un ORDER BY ASC sur les éléments qui peuvent être sélectionné
		- Prise en compte des dépendances
	24/03/2010 NSE bz 14815 : on ne doit pas prendre en compte le produit mixed_kpi pour les contextes
*/
?>
<?php
/**
 * Classe représentant un élément qui peuvent être ajouté dans un contexte du style graphe, dashboard....
 *
 * @author GHX
 * @version CB4.1.0.00
 * @since CB4.1.0.00
 * @package Context
 */
class ContextElement
{
	/**
	 * Active ou non le mode débug
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var int (default 0)
	 */
	private static $_debug = 0;

	/**
	 * Configuration d'un élément (correspond à ce qu'il y a dans les tables contexte)
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var array
	 */
	private $_config;

	/**
	 *
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var array
	 */
	private $_firstTable;

	/**
	 * Tableau contenant la liste des tables utilisés pour l'élément
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var array
	 */
	private $_tables;

	/**
	 * Tableau contenant les colonnes des différentes tables utilisées
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var array
	 */
	private $_columns;

	/**
	 * Connexion à la base de données
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var Ressource
	 */
	private $_db;

	/**
	 * Tableaux de toutes les connexion à la base de données
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var Ressource
	 */
	private static $_AllDb = array();

	/**
	 * Tableaux la liste des éléments sélectionnées
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var Array
	 */
	private $_listSelected = array();

	/**
	 * Constructeur
	 *
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param int $idElement identifiant de l'élément
	 * @param int $idProduct identifiant du produit on doit se connecter par défaut le produit sur lequel on est (default '')
	 */
	public function __construct ( $idElement, $idProduct = '' )
	{
		$this->_db = $this->getConnection($idProduct);

		// Vérifie que l'identifiant existe bien sinon on lève une exception
                // 08/06/2011 BBX -PARTITIONING-
                // Correction des casts
		$result = $this->_db->getOne("SELECT count(sdc_id) FROM sys_definition_context WHERE sdc_id = '{$idElement}'");
		if ( $result == 0 )
			throw new Exception("This element does not exists ({$idElement})");

		$this->_config = array();
		$this->_tables = array();
		$this->_columns = array();

		$this->configuration($idElement);
	} // End function __construct

	/**
	 * Retourne la connexion à une base de données d'un produit
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param int $idProduct identifiant du produit on doit se connecter
	 * @return Ressource
	 */
	private function getConnection ( $idProduct )
	{
		$idProduct = intval($idProduct);
		if ( !array_key_exists($idProduct, self::$_AllDb) )
		{
                        // 31/01/2011 BBX
                        // On remplace new DatabaseConnection() par Database::getConnection()
                        // BZ 20450
			self::$_AllDb[$idProduct] = DataBase::getConnection($idProduct);
			if ( self::$_debug & 2 )
			{
				self::$_AllDb[$idProduct]->setDebug(1);
			}
		}

		return self::$_AllDb[$idProduct];
	} // End function getConnection

	/**
	 * Récupère le paramètrage en base
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param int $idElement identifiant de l'élément pour lequel on récupère ca configuration
	 */
	private function configuration ( $idElement )
	{
		// Récupère la liste des éléments qui peuvent être dans le contexte
                // 08/06/2011 BBX -PARTITIONING-
                // Correction des casts
		$this->_config = $this->_db->getRow("SELECT * FROM sys_definition_context WHERE sdc_id = '{$idElement}'");

		// Récupère la liste des tables utilisés par l'élément
		$queryTables = "
				--- Récupère la liste des tables utilisées pour : {$this->_config['sdc_label']}

				SELECT
					sdct_id,
					sdct_table,
					sdctl_select_all
				FROM
					sys_definition_context_table_link
					LEFT JOIN sys_definition_context_table
					ON (sdctl_sdct_id = sdct_id)
				WHERE
					sdctl_sdc_id = {$idElement}
				ORDER BY
					sdctl_order
			";

		$this->_tables = $this->_db->getAll($queryTables);

		foreach ( $this->_tables as $index => $tables )
		{
                    // 08/06/2011 BBX -PARTITIONING-
                    // Si la table est vide, on passe pour ne pas provoquer
                    // d'erreur SQL
                    if(empty ($tables['sdct_table']))
                        continue;

			$queryKeys = "
					--- Récupère les clés primaires et étrangères pour la table : {$tables['sdct_table']}

					SELECT
						sdctk_id,
						sdctk_column,
						sdctk_sdctk_id
					FROM
						sys_definition_context_table
						LEFT JOIN sys_definition_context_table_key
						ON (sdct_id = sdctk_sdct_id)
					WHERE
						sdct_id = {$tables['sdct_id']}
				";

			$resultKeys = $this->_db->getAll($queryKeys);

			$this->_tables[$index]['pk'] = array();
			$this->_tables[$index]['fk'] = array();

			if ( count($resultKeys) > 0 )
			{
				foreach ( $resultKeys as $key )
				{
					$this->_columns[$key['sdctk_id']] = $key['sdctk_column'];

					if ( empty($key['sdctk_sdctk_id']) )
					{
						// Clé primaire
						$this->_tables[$index]['pk'][] = $key['sdctk_id'];
					}
					else
					{
						// Clé étrangère
						$this->_tables[$index]['fk'][$key['sdctk_id']] = $key['sdctk_sdctk_id'];
					}
				}
			}
		}

		// Recherche la premier table utilisée dans le cas où on peut sélectionné des éléments
		if ( $this->_config['sdc_selected'] == 1 )
		{
			// Tableau contenant la liste des tables où l'on prend toutes les données
			foreach ( $this->_tables as $table )
			{
				// Si on prend toutes les valeurs de la table c'est pas la première table
				if ( $table['sdctl_select_all'] == 1 )
					continue;

				// Si la table possède des clés étrangères c'est pas le première table
				if ( count($table['pk']) == 0 || count($table['fk']) > 0 )
					continue;

				if ( empty($this->_firstTable) )
				{
					$this->_firstTable = $table['sdct_table'];
				}
				else
				{
					// Si on a déjà une première table on ne peut pas savoir laquelle que l'on doit prendre
					throw new Exception('The configuration is wrong');
				}
			}
		}

		if ( self::$_debug )
		{
			echo '<p><b>Configuration de l\'élément : '.$this->_config['sdc_label'].'</b><br />';
			echo 'Premiere table : '.$this->_firstTable;
			echo '<pre>'.print_r($this->_config, 1).'</pre>';
			echo 'Liste des tables <pre>'.print_r($this->_tables, 1).'</pre>';
			echo 'Liste des clés primaires et étrangères <pre>'.print_r($this->_columns, 1).'</pre></p>';
		}
	} // End function configuration

	/**
	 * Détermine l'ordre des tables et retourne le résultat dans un tableau
	 *
	 * @author GHX
	 * @version CB 4.1.0.00
	 * @since CB 4.1.0.00
	 * @return array
	 */
	public function getOrderTables ()
	{
		$order = array();
		$select_all = array();
		// Boucle sur toutes les tables de l'élément
		foreach ( $this->_tables as $table )
		{
			$select_all[$table['sdct_table']] = $table['sdctl_select_all']; 
			// On ne prend pas en compte les tables où l'on prend toutes les données
			if ( $table['sdctl_select_all'] == 1 )
				continue;

			if ( count($table['fk']) == 0 )
				continue;
			
			// Boucle sur toutes les clés étrangères de la table
			foreach ( $table['fk'] as $key => $fk )
			{
				// On boucle une deuxieme fois sur toutes les tables pour trouver la clé primaire
				// de la clé étrangère
				foreach ( $this->_tables as $table2 )
				{
					// Si la clé étrangère correspond à la clé primaire d'une table
					if ( $table2['pk'][0] == $fk )
					{
						// ... on mémorise le résultat 
						// nom de la table de la clé primaire => nom de la table clé secondaire
						$order[$table2['sdct_table']][] = $table['sdct_table'];
						// On supprime les doublons
						$order[$table2['sdct_table']] = array_unique($order[$table2['sdct_table']]);
					}
				}
			}
		}

		print_r($order);
		// On détermine l'ordre via une podération
		// Plus une table à une podération faible plus il y a de chance qu'elle soit la première table
		// à traiter
		// Si plusieurs tables on la même pondération, c'est qu'il n'y a pas d'ordre de préférence pour les traiter
		$tmp = array();
		foreach ( $order as $tpk => $tfk)
		{
			$tmp[$tpk] -= count($order[$tpk]);
			foreach ( $tfk as $t )
			{
				$tmp[$t] -= abs($tmp[$tpk])+1;
				// if ( array_key_exists($t, $order2) )
				// {
					// $tmp[$tpk] += count($order2[$t]);
				// }
			}
			$tmp[$tpk] -= $select_all[$tpk]*2;
		}
		
		print_r($tmp);
		
		// On trie l'ordre des tables celon leur pondérations
		arsort($tmp);
		
		if ( self::$_debug )
		{
			echo "\nOrdre des table de l'élément {$this->getLabel()} :";
			print_r($tmp);
		}
		
		return array_keys($tmp);
	} // End fucntion orderTables

	/**
	 * Spécifie le niveau de débug
	 *	- 0 : désactivé
	 *	- 1 : activé
	 *
	 * @author GHX
	 * @version CB 4.1.0.00
	 * @since CB 4.1.0.00
	 * @param int $level niveau de débuggage
	 */
	public static function setDebug ( $level )
	{
		self::$_debug = $level;
	} // End function setDebug

	/**
	 * Retourne TRUE si l'élément doit être affiché dans l'IHM
	 *
	 * @author GHX
	 * @version CB 4.1.0.00
	 * @since CB 4.1.0.00
	 * @return boolean
	 */
	public function isVisible ()
	{
		return $this->_config['sdc_visible'] == 1 ? true : false;
	} // End function isVisible

	/**
	 * Retourne TRUE si l'élément doit être sélectionné par défaut
	 *
	 * @author GHX
	 * @version CB 4.1.0.00
	 * @since CB 4.1.0.00
	 * @return boolean
	 */
	public function isDefault ()
	{
		return $this->_config['sdc_default'] == 1 ? true : false;
	} // End function isDefault

	/**
	 * Retourne TRUE si l'élément doit être uniquement dans le contexte du master
	 *
	 * @author GHX
	 * @version CB 4.1.0.00
	 * @since CB 4.1.0.00
	 * @return boolean
	 */
	public function isOnlyMaster ()
	{
		return $this->_config['sdc_master'] == 1 ? true : false;
	} // End function isOnlyMaster

	/**
	 * Retourne TRUE si les tables doit être vider avant de monter les monter éléments
	 *
	 * @author GHX
	 * @version CB 4.1.0.00
	 * @since CB 4.1.0.00
	 * @return boolean
	 */
	public function isTruncate ()
	{
		return $this->_config['sdc_truncate'] == 1 ? true : false;
	} // End function isTruncate

	/**
	 * Retourne TRUE si on peut sélectionner juste quelques éléments pour mettre dans le contexte ou
	 * si on doit tout prendre
	 *
	 * @author GHX
	 * @version CB 4.1.0.00
	 * @since CB 4.1.0.00
	 * @return boolean
	 */
	public function isSelected ()
	{
		return $this->_config['sdc_selected'] == 1 ? true : false;
	} // End function isTruncate

	/**
	 * Retourne l'identifiant de l'élément
	 *
	 * @author GHX
	 * @version CB 4.1.0.00
	 * @since CB 4.1.0.00
	 * @return int
	 */
	public function getId ()
	{
		return $this->_config['sdc_id'];
	} // End function getId
	
	/**
	 * Retourne le label de l'élément
	 *
	 * @author GHX
	 * @version CB 4.1.0.00
	 * @since CB 4.1.0.00
	 * @return string
	 */
	public function getLabel ()
	{
		return $this->_config['sdc_label'];
	} // End function getLabel

	/**
	 * Retourne toutes les clés (primaires et étrangères)
	 *
	 * @author GHX
	 * @version CB 4.1.0.00
	 * @since CB 4.1.0.00
	 * @return array
	 */
	public function getColumns ()
	{
		return $this->_columns;
	} // End function getKeys

	/**
	 * Retourne le nom de la table sur laquel on doit se base pour récupérer les labels des éléments s'il est possible de les sélectionner
	 * Nom de la table sur laquel la valeur du champ sdc_label existe.
	 *
	 * @author GHX
	 * @version CB 4.1.0.00
	 * @since CB 4.1.0.00
	 * @return string
	 */
	public function getFirstTable ()
	{
		return $this->_firstTable;
	} // End function getFirstTable

	/**
	 * Retourne les clés priamires d'une table
	 *
	 * @author GHX
	 * @version CB 4.1.0.00
	 * @since CB 4.1.0.00
	 * @param string $table nom de la table
	 * @return array
	 */
	public function getPrimaryKeys ( $table )
	{
		return $this->getKeys($table, 'pk');
	} // End function getFirstTable

	/**
	 *  Retourne les clés étrangères d'une table
	 *
	 * @author GHX
	 * @version CB 4.1.0.00
	 * @since CB 4.1.0.00
	 * @param string $table nom de la table
	 * @return array
	 */
	public function getForeignKeys ( $table )
	{
		return $this->getKeys($table, 'fk');
	} // End function getFirstTable

	/**
	 * Retourne les clés d'une table en fonction du type spécifié
	 *
	 * @author GHX
	 * @version CB 4.1.0.00
	 * @since CB 4.1.0.00
	 * @param string $table nom de la table
	 * @param string $typeKey type de clé (pk ou fk)
	 * @return array
	 */
	private function getKeys ( $table, $typeKey )
	{
		foreach ( $this->_tables as $t )
		{
			if ( $table == $t['sdct_table'] )
			{
				return $t[$typeKey];
			}
		}

		return null;
	} // End function getFirstTable

	/**
	 * Change de produit pour faire les requetes sur un autre produit
	 *
	 * @author GHX
	 * @version CB 4.1.0.00
	 * @since CB 4.1.0.00
	 * @param int $idProduct identifiant du produit
	 */
	public function changeProduct ( $idProduct )
	{
		$this->_db = $this->getConnection($idProduct);
	} // End function changeProduct

	/**
	 * Retourne les données a afficher dans l'IHM si les éléments peuvent être sélectionnés sinon retourne FALSE
	 *
	 *	09/04/2009 GHX
	 *		- Ajout d'un ORDER BY ASC
	 *
	 * @author GHX
	 * @version CB 4.1.0.00
	 * @since CB 4.1.0.00
	 * @return array
	 */
	public function getDataForDisplay ($idp=0)
	{
		// S'il n'est pas possible de sélectionné des éléments ou que la première table est vide
		// on ne peut donc pas récupèrer les données, on quitte donc la fonction
		if ( !$this->isSelected() || empty($this->_firstTable) )
			return false;

		$pk = '';
		foreach ( $this->_tables as $table )
		{
			if ( $this->_firstTable == $table['sdct_table'] )
			{
				if ( count($table['pk']) == 0 )
					return false;

				$pk = $this->_columns[$table['pk'][0]];
				continue;
			}
		}

		// Création de la requete SQL
		$query = "SELECT {$pk} AS index, {$this->_config['sdc_column']} AS value FROM {$this->_firstTable}";

		if ( !empty($this->_config['sdc_sql_where_select_default']) )
		{
			$query .= " WHERE {$this->_config['sdc_sql_where_select_default']}";
                    if($this->_firstTable=='sys_pauto_page_name'||$this->_firstTable=='sys_definition_alarm'){
                        $UserModel = new UserModel($_SESSION['id_user']);
                        $userProfileType = $UserModel->getUserProfileType();
                        if($userProfileType=='admin')
                            $query .= " AND droit='client'";
		}
                    elseif($this->_firstTable=='sys_definition_alarm_static'||$this->_firstTable=='sys_definition_alarm_dynamic'||$this->_firstTable=='sys_definition_alarm_top_worst'){
                        $UserModel = new UserModel($_SESSION['id_user']);
                        $userProfileType = $UserModel->getUserProfileType();
                        if($userProfileType=='admin')
                            $query .= " AND client_type='client'";
                    }
		}
                elseif($this->_firstTable=='sys_pauto_page_name'){
                    $UserModel = new UserModel($_SESSION['id_user']);
                    $userProfileType = $UserModel->getUserProfileType();
                    if($userProfileType=='admin')
                        $query .= " WHERE droit='client'";
                }
                elseif($this->_firstTable=='sys_definition_alarm_static'||$this->_firstTable=='sys_definition_alarm_dynamic'||$this->_firstTable=='sys_definition_alarm_top_worst'){
                    $UserModel = new UserModel($_SESSION['id_user']);
                    $userProfileType = $UserModel->getUserProfileType();
                    if($userProfileType=='admin')
                        $query .= " WHERE client_type='client'";
                }
		// 16:21 09/04/2009 GHX
		// Ajout du ORDER BY
		$query .= " ORDER BY {$this->_config['sdc_column']} ASC";
		
		$data = array();
		// 18:07 09/04/2009 GHX
		// Si c'est un élément qui  n'est pas seulement sur le master 
		// on va récupérer les données sur tous les produits
		if ( $this->isOnlyMaster() )
		{
			// Exécution
			$result = $this->_db->execute($query);
			// Récupération des données
//			if ( $this->_db->getNumRows() > 0 )
//			{
				while ( $elem = $this->_db->getQueryResults($result, "one") )
				{
                        // on  regarde l'id_produit de l'élément
                        $id_elem = $elem['index'];
                        $id_product = array();
                        // on récupère les id_produit de tous les éléments
                        $resultProd = $this->_db->execute("SELECT id_product, id_elem, id_page FROM sys_pauto_config WHERE id_elem='{$id_elem}' "); // and (id_product={$idp} OR id_product ISNULL)

                        while ($rowProd = $this->_db->getQueryResults($resultProd, "one")) {
                            $id_elem = $rowProd['id_elem'];
                            if (!empty($rowProd['id_product']) && $rowProd['id_product'] > 0) {
                                $id_product[] = $rowProd['id_product'];
                            } else {
                                // 
                                if (!empty($id_elem)) {
                                    // on recherche les produits sur lesquels sont enregistrés les éléments composant cet élément
                                    //  while(empty($id_product) && !empty($id_elem)){
                                    $resultProd2 = $this->_db->execute("SELECT * FROM sys_pauto_config WHERE id_page='{$id_elem}'"); // and id_product={$idp}
                                    while ($rowProd2 = $this->_db->getQueryResults($resultProd2, "one")) {
                                        if (!empty($rowProd2['id_product']) && $rowProd2['id_product'] > 0) {
                                            $id_product[] = $rowProd2['id_product'];
				}
			}
		}
                            }
                        }
                        // recherche pour les Dash
                        // on recherche maintenant les élément dont des composant sont dans sys_pauto_config
                        $resultProd = $this->_db->execute("SELECT id_product, id_elem, id_page FROM sys_pauto_config WHERE id_page='{$id_elem}'");
                        while ($rowProd = $this->_db->getQueryResults($resultProd, "one")) {
                            $id_elem = $rowProd['id_elem'];
                            if (!empty($rowProd['id_product']) && $rowProd['id_product'] > 0) {
                                $id_product[] = $rowProd['id_product'];
                            } else {
                                if (!empty($id_elem)) {
                                    // on recherche les produits sur lesquels sont enregistrés les éléments composant cet élément
                                    //  while(empty($id_product) && !empty($id_elem)){
                                    $resultProd2 = $this->_db->execute("SELECT * FROM sys_pauto_config WHERE id_page='{$id_elem}'");
                                    while ($rowProd2 = $this->_db->getQueryResults($resultProd2, "one")) {
                                        if (!empty($rowProd2['id_product']) && $rowProd2['id_product'] > 0) {
                                            $id_product[] = $rowProd2['id_product'];
                                        }
                                    }
                                }
                            }
                        }
			
                        if (!empty($id_product)) {
                            $idpCount = array_count_values($id_product);
                            // si on n'a qu'une seule valeur qui est le produit courant
                            if (count($idpCount) == 1 && isset($idpCount[$idp]) && $idpCount[$idp] > 0)
                                $data[$elem['index']] = $elem['value'];
				}
                        unset($id_product);
			}
		}
		else
			{
                    // On n'effectue la recherche que sur le produit passé en paramètre
                    $dbProduct = DataBase::getConnection($idp);
				// Exécution
				$result = $dbProduct->execute($query);
				// Récupération des données
				if ( $dbProduct->getNumRows() > 0 )
				{
					while ( $elem = $dbProduct->getQueryResults($result, "one") )
					{
                            // on n'indique plus entre parenthèses le produit
                            $data[$elem['index']] = $elem['value'];
					}
				}
			}
		
		return $data;
	} // End function getDataForDisplay

	/**
	 * Retourne la liste des tables utilisées par l'élément
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @return array
	 */
	public function getTables ()
	{
		$result = array();

		foreach ( $this->_tables as $table )
		{
			$result[] = $table['sdct_table'];
		}

		return $result;
	} // End function getTables

	/**
	 * Spécifie la liste des éléments sélectionnés. Uniquement dans le cas où on ne doit pas obligatoirement prendre
	 * toute les tables de données concernées par l'élément
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param array $list liste des éléments sélectionnés
	 */
	public function setSelected ( $list )
	{
		$this->_listSelected = $list;
	} // End function setSelected

	/**
	 * Retourne la liste des éléments sélectionnées. Uniquement dans le cas où on ne doit pas obligatoirement prendre
	 * toute les tables de données concernées par l'élémént
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @return array
	 */
	public function getSelected ()
	{
		return $this->_listSelected;
	} // End function getSelected

	/**
	 * Retourne TRUE si des éléments ont été sélectionnées
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @return boolean
	 */
	public function hasSelected ()
	{
		return count($this->_listSelected) > 0 ? true : false;
	} // End function selected

	/**
	 * Retourne la condition SQL  qui récupère les éléments à afficher & condition pour la requête SQL pour savoir les éléments qui sont sélectionnés par défaut
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @return string
	 */
	public function getSQLWhereSelectDefault ()
	{
		return $this->_config['sdc_sql_where_select_default'];
	} // End function getSQLWhereSelectDefault

	/**
	 * Retourne les informations d'une table
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param string $table nom de la table
	 * @return array
	 */
	public function getInfoTable ( $table )
	{
		foreach ( $this->_tables as $t )
		{
			if ( $t['sdct_table'] == $table )
			{
				return $t;
			}
		}

		return null;
	} // End function getInfoTable

	/**
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @return array
	 */
	public function getDependency ($idp=0)
	{
		$query = "
			SELECT
				sdcd_sdc_id_dependency,
				sdcd_column,
				sdct_table,
				sdcd_column_join,
				sdcd_where
			FROM
				sys_definition_context_dependency,
				sys_definition_context_table
			WHERE
				sdcd_sdct_id = sdct_id
				AND sdcd_sdc_id = ".$this->getId()."
			";
		
		$resultQuery = $this->_db->getAll($query);
		
		if ( count($resultQuery) == 0 )
			return false;
		
		// Création de la requete WHERE pour la sub-query
		$pk = $this->getPrimaryKeys($this->getFirstTable());
		$pk = $this->_columns[$pk[0]];
		$where = "";
		if ( !empty($this->_config['sdc_sql_where_select_default']) )
		{
			$where = " WHERE {$this->_config['sdc_sql_where_select_default']}";
		}
		if ( count($this->_listSelected) > 0 )
		{
			if ( empty($where) )
			{
				$where = " WHERE ";
			}
			else
			{
				$where .= " AND ";
			}
			$where .= " {$pk} IN ('".implode("','", $this->_listSelected)."')";
		}
		
		// On ne gère plus les éléments multi-produit.
		
		$result = array();
		foreach ( $resultQuery as $row )
		{
			// Création de la requete qui récupére les id des éléments que l'on doit récupérer
			$queryElementsDependency = "
				SELECT
					{$row['sdcd_column']}
				FROM
					{$row['sdct_table']}
				WHERE
					{$row['sdcd_column_join']} IN (
							SELECT
								{$pk} 
							FROM
								{$this->_firstTable}
							{$where}
							)
				";
			
			if ( !empty($row['sdcd_where']) )
			{
				$queryElementsDependency .= " AND {$row['sdcd_where']}";
			}
				
			$el = new ContextElement($row['sdcd_sdc_id_dependency']);
			
			// Si l'élément est présent uniquement sur le master
			if ( $this->isOnlyMaster() )
			{
				$resultElDep = $this->_db->execute($queryElementsDependency);
				if ( $this->_db->getNumRows() > 0 )
				{
					$result[$el->getId()] = array();
					while ( $rowElDep =  $this->_db->getQueryResults($resultElDep, 1) )
					{
						$result[$el->getId()][] = $rowElDep[$row['sdcd_column']];
					}
				}
			}
			else
			{
				// On ne gère plus les éléments multi-produit.
                                // On ne récupère donc plus que les informations du produit concerné par l'élément.
                                $dbProduct = DataBase::getConnection($idp);
					
					$resultElDep = $dbProduct->execute($queryElementsDependency);
					if ( $dbProduct->getNumRows() > 0 )
					{
						if ( !array_key_exists($el->getId(), $result) )
						{
							$result[$el->getId()] = array();
						}
						while ( $rowElDep =  $dbProduct->getQueryResults($resultElDep, 1) )
						{
							$result[$el->getId()][] = $rowElDep[$row['sdcd_column']];
						}
					}
				}

			$el->setSelected($result[$el->getId()]);
			// Si on a d'autres dépendances
			if ( $dependency = $el->getDependency($idp) )
			{
				// Fusion des résulstats
				foreach ( $dependency as $k => $v )
				{
					if ( array_key_exists($k, $result) )
					{
						$result[$k] = array_merge($result[$k], $v);
					}
					else
					{
						$result[$k] = $v;
					}
				}
			}
		}
		
		return $result;
	} // End function getDependency
	
	/**
	 * Permet de vérifier la configuration de l'élément pour voir si tout est correct que les tables, colonnes existent... et retourne TRUE si tout est bon
	 * sinon FALSE
	 *
	 * 	NOTE : à utiliser uniquement pour les développements !!!!
	 *	ATTENTION : la fonction utilise la fonction echo
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @return boolean
	 */
	public function checkConfiguration ()
	{
		echo '<br /><b>Vérification de la configuration</b><br />';

		$configOK = true;

		// Vérifie si les tables existent et vérifié si les clés pk et fk sont correctes
		foreach ( $this->_tables as $table )
		{
			echo "<br /> table : {$table['sdct_table']}";
			$queryTestTableExists = "SELECT * FROM {$table['sdct_table']} LIMIT 1";
			$resultTestTableExists = $this->_db->getAll($queryTestTableExists);
			if ( !is_array($resultTestTableExists) )
			{
				echo "<br /><span style=\"color:red\">La table '{$table['sdct_table']}' n'existe pas</span>";
				$configOK = false;
			}

			// Vérifie si les clés primaires existent
			if ( count($table['pk']) )
			{
				foreach ( $table['pk'] as $pk )
				{
					$column = $this->_columns[$pk];
					$queryTestColumnExists = "SELECT {$column} FROM {$table['sdct_table']} LIMIT 1";
					$resultTestColumnExists = $this->_db->getAll($queryTestColumnExists);
					if ( !is_array($resultTestColumnExists) )
					{
						echo "<br /><span style=\"color:red\">La colonne (clé primaire) '{$column}' n'existe pas dans la table '{$table['sdct_table']}'</span>";
						$configOK = false;
					}
				}
			}
			else
			{
				echo "<br /><span style=\"color:orange\">La table '{$table['sdct_table']}' n'a pas de clé primaire</span>";
			}

			// Vérifie si les clés étrangères existent
			if ( count($table['fk']) )
			{
				foreach ( $table['fk'] as $fk => $pk )
				{
					$column = $this->_columns[$fk];
					$queryTestColumnExists = "SELECT {$column} FROM {$table['sdct_table']} LIMIT 1";
					$resultTestColumnExists = $this->_db->getAll($queryTestColumnExists);
					if ( !is_array($resultTestColumnExists) )
					{
						echo "<br /><span style=\"color:red\">La colonne (clé étrangère) '{$column}' n'existe pas dans la table '{$table['sdct_table']}'</span>";
						$configOK = false;
					}
					else
					{
						// Vérifie si la clé primaire est défini
						if ( !array_key_exists($pk, $this->_columns) )
						{
							echo "<br /><span style=\"color:red\">La clé étrangère '{$column}' de la table '{$table['sdct_table']}' n'a pas ca clé primaire de défini</span>";
							$configOK = false;
						}
					}
				}
			}
		}

		if ( $this->_config['sdc_selected'] == 1 )
		{
			$queryTestColumnLabel = "SELECT {$this->_config['sdc_column']} FROM {$this->_firstTable} LIMIT 1";

			$resultTestColumnLabel = $this->_db->getAll($queryTestColumnLabel);
			if ( !is_array($resultTestColumnExists) )
			{
				echo "<br /><span style=\"color:red\">La colonne '{$this->_config['sdc_column']}' n'existe pas dans la table '{$this->_firstTable}'</span>";
				$configOK = false;
			}
		}

		return $configOK;
	} // End function checkConfiguration

} // End class ContextElement
?>