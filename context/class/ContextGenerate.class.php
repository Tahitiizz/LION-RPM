<?php
/*
	06/04/2009 GHX
		- Correction du pattern de replacement de l'ID produit par le code produit (les codes produits se trouvent dans la table sys_aa_interface)
		- Suppression du répertoire dans lequel on crée les fichiers csv avec de générer le contexte (sinon erreur PHP sur mkdir)
		- Ajout d'une exception si le code produit n'est pas défini pour un type de produit (cad pas de ligne dans la table sys_aa_interface)
	07/04/2009 GHX
		- modification du nom d'une variable (deux variables avaient le même nom &  le problème ne se voyait pas sur un mono-produit)
	18/06/2009 GHX
		- Correction du bug BZ 9848 [REC][T&A CB 5.0][GESTION CONTEXTE]: Pb contenu export
	09/07/2009 GHX
		- Ajout du nombre de lignes copiées dans chaque fichier pour les logs
	10/07/2009 
		- Prise en compte d'un cas particulier avec la table sys_definition_selecteur pour le champ sds_sort_by qui contient aussi l'identifiant d'un produit dans la fonction loadDataInFile()
	22/09/2009 GHX
		- Correction du BZ 11533 
			-> Ajout des menus parents pour les menus dashbaords
	29/10/2009 GHX
		- Correction du BZ 11355 [REC][T&A CB 5.0][TP#1][TC#40520][Contexte]: en cas d'import multi-produit , l'ordre Master-Slave est primordial
	06/11/2009 GHX
		- Modification des requetes SQL entre les tables sys_aa_interface et sys_global_parameters car si on n'a pas le même case, impossible de générer le contexte
	24/03/2010 NSE bz 14815 : on ne doit pas prendre en compte le produit mixed_kpi pour les contextes
	17/05/2010 NSE : Installation standard, utilisation d'une variable globale pour les chemins vers les exécutables psql et php (PSQL_DIR et PHP_DIR)
   01/06/2011 MMT DE 3rd axis, getColumns: connection à la base slave au lieu du master au cas ou les colonnes sur le master n'existent pas sur le slave
*/
?>
<?php
/**
 * Classe qui génère un contexte
 *
 * @author GHX
 * @version CB4.1.0.00
 * @since CB4.1.0.00
 * @package Context
 */
class ContextGenerate
{
	/**
	 * Active ou non le mode débug
	 *	- 0 : désactivé
	 *	- 1 : activé
	 *	- 2 : activé et affiche les requetes SQL (uniquement les requetes exécutées via DataBaseConnection)
	 *	- 6 : activé, affiche les requetes SQL (uniquement les requetes exécutées via DataBaseConnection) et aucune compression
	 *
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var int
	 */
	private $_debug = 0;

	/**
	 * Répertoire dans lequel sera créé le contexte
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var string
	 */
	private $_directory = null;

	/**
	 * Identifiant du produit master
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var int
	 */
	private $_idMasterProduct = null;

	/**
	 * Booleen permettant de savoir si on doit ajouter les éléments supprimés dans le contexte
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var boolean
	 */
	private $_delete = false;

	/**
	 * Liste des éléments à ajouter dans le contexte
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var Ressource
	 */
	private $_listElements = array();

	/**
	 * Tableaux de toutes les connexion à la base de données
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var array
	 */
	private $_listDb = array();

	/**
	 * Liste des tables déjà utilisés dans le contexte du produit courant
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var array
	 */
	private $_listTables = array();

	/**
	 * 
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var string
	 */
	private $_patternReplaceIdProduct = null;
	
	/**
	 * Tableau contenant la liste des colonnes de chaque tables
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var array
	 */
	private $_tablesColumn = array();

	/**
	 * Connexion à la base de données courante
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var DataBaseConnection
	 */
	private $_db = null;

	/**
	 * Identifiant du produit courant
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var int
	 */
	private $_idProduct = null;

	/**
	 * Information sur le produit courant
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var int
	 */
	private $_infoProduct = null;

	/**
	 * Information sur le produit master
	 * @since CB5.3.2.01
	 * @var int
	 */
        // 2014/01/21 bz 39037 ajout du paramètre
	private $_infoMasterProduct = null;

	/**
	 * Nom du répertoire dans lequel se trouvera les fichiers csv du produit courant
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var int
	 */
	private $_directoryProduct = null;

	/**
	 * Boolean permettant de savoir si on génère un contexte d'activation.
	 * Permet d'inhiber certains traitement
	 * @version CB 5.0.0.09
	 * @since CB  5.0.0.09
	 * @var boolean
	 */
	private $_activation = false;
	
	/**
	 * Constructeur
	 *
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 */
	public function __construct ()
	{
		$db = $this->getConnection("");
		// Récupère l'identifiant du produit master
		$nbProduct = $db->getOne("SELECT count(sdp_id) FROM sys_definition_product");
		if ( $nbProduct > 1 )
		{
			$this->_idMasterProduct = $db->getOne("SELECT sdp_id FROM sys_definition_product WHERE sdp_master = 1");
		}
		else
		{
			// S'il n'y a qu'un seul produit il est considéré comme le master
			$this->_idMasterProduct = $db->getOne("SELECT sdp_id FROM sys_definition_product");
		}
	} // End function __construct

	/**
	 * Spécifie le niveau de débug
	 *	- 0 : désactivé
	 *	- 1 : activé
	 *	- 2 : activé et affiche les requetes SQL (uniquement les requetes exécutées via DataBaseConnection)
	 *	- 6 : activé, affiche les requetes SQL (uniquement les requetes exécutées via DataBaseConnection) et aucune compression
	 *
	 * @author GHX
	 * @version CB 4.1.0.00
	 * @since CB 4.1.0.00
	 * @param int $level niveau de débuggage
	 */
	public function setDebug ( $level )
	{
		$this->_debug = intval($level);
	} // End function setDebug

	/**
	 * Spécifie le répertoire dans lequel sera créé le contexte
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param string $dir chemin absolute vers le répertoire
	 */
	public function setDirectory ( $dir )
	{
		if ( !is_dir($dir) )
			throw new Exception(__T('A_E_CONTEXT_DIRECTORY_NOT_EXISTS', $dir));

		if ( !is_writable($dir) )
			throw new Exception(__T('A_E_CONTEXT_DIRECTORY_NOT_WRITEABLE', $dir));

		if ( substr($dir, -1) != '/' )
		{
			$dir .= '/';
		}
		$this->_directory = $dir;
	} // End function setDirectory

	/**
	 * Spécifie que le contexte que l'on génère est un contexte d'activation
	 *
	 * @author GHX
	 * @version CB 5.0.0.09
	 * @since CB 5.0.0.09
	 * @param boolean $activation spécifie si on génère un contexte d'activation (default : true=
	 */
	public function setActivation ( $activation = true )
	{
		$this->_activation = $activation;
	} // End function setActivation
	
	/**
	 * Ajout un élément qui doit être dans le contexte
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param ContextElement $element élément à mettre dans le contexte
	 */
        // 2014/01/21 bz 39073 add of optional product Id parameter
	public function addElement ( $element, $idprod=null )
	{
		if ( !($element instanceof ContextElement) )
			throw new Exception(__T('A_E_CONTEXT_GENERATE_NOT_ELEMENT', $element));
                // 2014/01/21 bz 39073 Ajout de l'élément dans la liste du produit correspondant (si le produit est spécifié
		if($idprod)
                    $this->_listElements[$idprod][] = $element;
                else
		$this->_listElements[] = $element;
	} // End function addElement

	/**
	 * Spécifie si les éléments supprimés doivent être mis dans le contexte
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param boolean $add spécifié si on doit ajouter les éléments à supprimer dans le contexte (defaut : true)
	 */
	public function addDeleteElements ( $add = true )
	{
		$this->_delete = $add;
	} // End function addDeleteElements

	/**
	 *  Créer le contexte, il est possible de spécifié un identifiant de produit. Si aucun ID produit n'est passé, le contexte contiendra autant de sous-contexte qu'il y a de type
	 * de produit. L'ID produit est utilisé dans le cas de la création d'un contexte d'activation. C'est à dire que le contexte sera créé pour est remonté aussitot sur le master. Donc l'ID produit
	 * doit être obligatoirement un ID produit d'un produit slave;
	 *
	 * Retourne le nom du contexte créé.
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param string $cxtname nom du contexe
	 * @param string $version version du contexe
	 * @param int $idProduct identifiant d'un produit (default null)
	 * @return string
	 */
	public function generate ( $cxtname, $version, $idProduct = null, $prodList=array() )
	{
		$date = date('YmdHi');

		if ( $this->_debug )
		{
			echo "\nCréation du contexte : {$cxtname}_{$version} [{$this->_directory}]";
		}

		// 16:11 06/04/2009 GHX
		// Suppression du répertoire si celui-ci existe. Le répertoire existe dans le cas, où on a déjà généré un contexte avec le même nom+version et que la génération a planté
		if ( file_exists("{$this->_directory}{$cxtname}_{$version}") )
		{
			exec('rm -rf "'.$this->_directory.$cxtname."_".$version.'"');
		}
		
		// Création du répertoire dans lequel sera créer les fichiers csv du contexte
		mkdir("{$this->_directory}{$cxtname}_{$version}", 0777);
		
		// Récupère les informations sur tous les produits
		$productsInformations = getProductInformations();
                // 2014/01/21 bz 39037 récupération des infos du master
		$this->_infoMasterProduct = $productsInformations[ProductModel::getIdMaster()];
		// 24/03/2010 NSE bz 14815 : on ne doit pas prendre en compte le produit mixed_kpi pour les contextes
                // on supprime la Gateway du contexte si on n'est pas en contexte d'activation
		// Boucle sur tous les produits
		foreach ( $productsInformations as $clef => $product )
		{
			if($product['sdp_id']==ProductModel::getIdMixedKpi()){
				// on supprime le produit du tableau 
				unset($productsInformations[$clef]);
			}
                        elseif($idProduct === null && ProductModel::isBlankProduct($product['sdp_id'])){
				// on supprime le produit du tableau 
				unset($productsInformations[$clef]);
		}
                }
		$listProduct = array();
		$listProductPattern = array();
		
		// Boucle sur tous les produits
		// 13:40 29/10/2009 GHX
		// Correction du BZ 11355
		// Modification pour renseigner le tableau $listProductPattern
                if($idProduct === null && !empty($prodList)){

                    foreach ( $prodList as $product )
                    {
                            // Spécifie la base de données courante par rapport au produit
                            $db = $this->getConnection($product);
                            // Récupère le nom du module correspondant au produit et l'associe à son ID
                            $module = $this->getModuleFromProduct($product);			
                            if ( empty($module) )
                                    throw new Exception(__T('A_E_CONTEXT_GENERATE_CODE_PRODUCT_NO_EXISTS', $db->getOne("SELECT value FROM sys_global_parameters WHERE parameters = 'module'")));

                            if ( !in_array($module, $listProductPattern) )
                            {
                                    $listProductPattern[$product] = $module;
                            }
                            if ( !in_array($module, $listProduct) )
                            {
                                    $listProduct[$product] = $module;
                            }
                    }
                }
                else{
		foreach ( $productsInformations as $product )
		{
			// Spécifie la base de données courante par rapport au produit
			$db = $this->getConnection($product['sdp_id']);
			// Récupère le nom du module correspondant au produit et l'associe à son ID
			//$module = $db->getOne("SELECT saai_interface FROM sys_global_parameters LEFT JOIN sys_aa_interface ON (lower(value) = lower(saai_module)) WHERE parameters = 'module'");
			// 19/03/2010 BBX : utilisation de la valeur de "old_module" si présente. BZ 14392
			$module = $this->getModuleFromProduct($product['sdp_id']);			
			if ( empty($module) )
				throw new Exception(__T('A_E_CONTEXT_GENERATE_CODE_PRODUCT_NO_EXISTS', $db->getOne("SELECT value FROM sys_global_parameters WHERE parameters = 'module'")));
			
			if ( !in_array($module, $listProductPattern) )
			{
				$listProductPattern[$product['sdp_id']] = $module;
			}
			
			// 15:11 23/03/2009 GHX
			// Si c'est pas un contexte d'activation
			if ( $idProduct === null )
			{
				if ( $product['sdp_master'] == 1 )
				{
					$listProduct[$product['sdp_id']] = $module;
				}
				elseif ( !in_array($module, $listProduct) )
				{
					$listProduct[$product['sdp_id']] = $module;
				}
			}
			else // Si c'est un contexte d'activation
			{
				// Si c'est le produit qu'on veut
				if ( $product['sdp_id'] == $idProduct )
				{
					if ( !in_array($module, $listProduct) )
					{
						$listProduct[$product['sdp_id']] = $module;
					}
				}
			}
		}
                }
		
		/*
		 *Dans le cas de la génération d'un contexte d'activation le pattern (ou s'il n'y a qu'un seul produit)
		 * est de la forme suivante:
		 * 	CASE WHEN \1 = XXX THEN YYY ELSE \1 END
		 * 		->où \1 :  représentera le nom de la colonne comportant la chaine de caractère id_product
		 *		-> XXX représente l'identifiant d'un produit (en général dans ce cas ca sera 1)
		 *		-< YYY représente le code produit (par exemple pour IU ca sera 16)
		 */
		// 16:00 06/04/2009 GHX
		//  Correction du pattern
		// 14:41 27/10/2009 GHX
		// Correction du BZ 11355
		// Suppression du if/else
                // 09/06/2011 BBX -PARTITIONING-
                // Correction des casts
		$this->_patternReplaceIdProduct = "CASE ";
		foreach ( $listProductPattern as $id => $module )
		{
			$this->_patternReplaceIdProduct .= 'WHEN \1::text = '.$id.'::text THEN '.$module.' ';
		}
		$this->_patternReplaceIdProduct .= 'ELSE \1 ';
		$this->_patternReplaceIdProduct .= "END";
		
		// Boucle sur tous les produits devant se trouver dans le contexte
		foreach ( $listProduct as $id => $module )
		{
			// Vide la liste des tables utilisées par le produit d'avant
			$this->_listTables = array();
                        // 04/02/2014 NSE bz 39467 utilisation des mauvaises colonnes pour générer le contexte
			// Vide la liste des colonnes des tables utilisées par le produit d'avant
                        $this->_tablesColumn = array();
			// Spécifie le produit courant
			$this->_idProduct = $id;
			// Spécifie la base de données courante par rapport au produit courant
			$this->_db = $this->getConnection($id);
			// Spécifie les informations sur le produit courant
			$this->_infoProduct = $productsInformations[$id];
			// Création du nom du répertoire pour le produit courant
			$this->_directoryProduct = sprintf(
					"ctx_%s_%s",
					$date,
					$module
				);
			// Création du répertoire dans lequel se trouvera les fichiers csv du produit courant
			mkdir("{$this->_directory}{$cxtname}_{$version}/{$this->_directoryProduct}", 0777);

			if ( $this->_debug )
			{
				echo "\n\n ----------------";
				echo "\n - Type de produit : {$module}";
				echo "\n - Nom sous-contexte : {$this->_directoryProduct}";
				echo "\n - Master : ".($this->_infoProduct['sdp_master'] ? 'oui' : 'non');
			}

			// Création d'un fichier md5
			$this->generateMD5($cxtname.'_'.$version);

			// Boucle sur tous les éléments devant se trouver dans le contexte (graph, dash, alarmes...)
                        // 2014/01/21 bz 39073 
                        // si les éléments sont affectés à un produit on prend la liste pour ce produit
                        if(is_array($this->_listElements[$this->_infoProduct['sdp_id']]))
                            $listElements = $this->_listElements[$this->_infoProduct['sdp_id']];
                        // sinon, c'est que la liste est directement mémorisée
                        else
                            $listElements = $this->_listElements;
			foreach ( $listElements as $element )
			{
				// 15:06 23/03/2009 GHX
				// On fait la vérification si c'est le master uniquement dans le cas où c'est pas un contexte d'activation
                                // chaque produit porte maintenant ses éléments
				/*if ( $element->isOnlyMaster() && $idProduct === null )
				{
					// Si l'élément doit être présent uniquement sur dans le contexte du master et qu'on n'est pas
					// sur le produit master on passe à l'élément suivant
                                        // chaque produit porte maintenant ses éléments
					//if ( $id != $this->_idMasterProduct )
					//{
						if ( $this->_debug )
						{
							echo "\n\n    - On ne prend pas l'élément : {$element->getLabel()} (uniquement pour le master produit)";
						}
					//	continue;
					//}
				}*/

				if ( $this->_debug )
				{
					echo "\n\n    - Ajout de l'élément : {$element->getLabel()}";
				}

				// Récupère la liste des tables utilisées pour l'élément
				$tables = $element->getTables();
				// Création des fichiers csv à partir du nom des tables
				$this->createFilesCSV($tables, $cxtname.'_'.$version);

				// Récupère la liste des colonnes servant de clés primaires ou étrangères pour l'élément courant
				$columns = $element->getColumns();
				// Tableau contenant les conditions à appliquer pour utiliser les clés étrangères
				$keyLinks = array();

				// Récupère le nom de la première table sur laquel on doit se baser pour sélectionner les éléments dont on a besoin
				$firstTable = $element->getFirstTable();
				$infoFirstTable = $element->getInfoTable($firstTable);
				$firstTablePk = $element->getPrimaryKeys($firstTable);
				$firstTablePk = $firstTablePk[0];
				// Création de la condition WHERE pour la première table
				$where = "";
				// 10:31 18/06/2009 GHX
				// Ajout d'une condition pour la correction du BZ 9848
				if ( $infoFirstTable['sdctl_select_all'] != 1 && !empty($firstTable) )
				{
					$wheretmp = $element->getSQLWhereSelectDefault();
					if ( !empty($wheretmp) )
					{
						$where = $wheretmp;
					}
					// Si on a des éléments de sélectionnés
					if ( $element->hasSelected() )
					{
						if ( !empty($where) )
						{
							$where .= " AND ";
						}
						$list = implode("','", $element->getSelected());
						$where .= "{$columns[$firstTablePk]} IN ('{$list}')";
					}
					// Sauvegarde la condition s'il y en a une
					if ( !empty($where) )
					{
						$keyLinks = array(
								$firstTablePk => array(
										'table' => $firstTable,
										'column' => $columns[$firstTablePk],
										'where' => $where
									)
							);
					}
				}
				// On boucle sur toutes les tables de l'élément
				foreach ( $tables as $table )
				{
					if ( $this->_debug )
					{
						echo "\n\n        - On prend les données de la table : {$table}";
					}

					// On regarde si la table a déjà été traité ...
					if ( array_key_exists($table, $this->_listTables) )
					{
						// ... si oui on regarde si on pris tout son contenu si oui on passe à la table suivante
						if ( $this->_listTables[$table] == 1 )
						{
							if ( $this->_debug )
							{
								echo " (déjà fait)";
							}
							continue;
						}
					}

					// Récupère les informations de la table qui doit être traitée
					$infoTable = $element->getInfoTable($table);

					// Création de la condition qui permettra de récupérer uniquement les données nécessaires
					// On boucle sur toutes les clés étrangères de la table courante
					foreach ( $infoTable['fk'] as $fk => $linkKey )
					{
						// si la clé étrangère à un lien avec une autre clé de l'élément que l'on traite on récupère la condition
						// = si linkKey possède une condition
						if ( array_key_exists($linkKey, $keyLinks) )
						{
							if ( !empty($where) )
							{
								$where .= " AND ";
							}
							$where .= sprintf(
									"%s IN (SELECT %s FROM %s WHERE %s)",
									$columns[$fk],
									$keyLinks[$linkKey]['column'],
									$keyLinks[$linkKey]['table'],
									$keyLinks[$linkKey]['where']
								);
							
							$keyLinks[$fk] =  array(
										'table' => $table,
										'column' => $columns[$fk],
										'where' => $where
									);
						}
					}

					// Si la table courante à une condition, on la stocke
					// elle peut servir pour autre table (clé étrangère d'une autre table)
					if ( !empty($where) )
					{
						$keyLinks[$infoTable['pk'][0]] =  array(
										'table' => $table,
										'column' => $columns[$infoTable['pk'][0]],
										'where' => $where
									);
					}

					/* ***************************************************************************** */
					/* CAS PARTICULIER : pour les dashboards on ajoute aussi les menus parents des dashboards */
					/* ***************************************************************************** */
					// 09:17 22/09/2009 GHX
					// Correction du BZ 11533
					// Et si c'est pas un contexte d'activatoin
					if ( $table == 'menu_deroulant_intranet' && $element->getId() == 2 && !$this->_activation)
					{
						$where .= " OR id_menu IN (SELECT id_menu_parent FROM menu_deroulant_intranet WHERE {$where})";
					}
					
                                        // 15/11/2011 BBX
                                        // BZ 22972 : exclusion des data exports corporate et mixed kpi
                                        if($table == 'sys_export_raw_kpi_config'){
                                            $where .= (empty($where) ? " " : " AND")." export_type = 0";
                                        }
                                        if($table == 'sys_export_raw_kpi_data') {
                                            $where .= (empty($where) ? " " : " AND")." export_id NOT IN (SELECT export_id FROM sys_export_raw_kpi_config WHERE export_type > 0)";
                                        }
                                        // Fin BZ 22972
					
					// Copie toutes les données dans la table dans le fichier csv
                                        // 2014/01/21 bz 39037 ajout du paramètre $element pour savoir s'il faut se connecter au produit courant ou au master 
					$this->loadDataInFile($table, $cxtname.'_'.$version, $where, $element);

					// Ajout dans le tableau que la table a déjà été récupérée avec toutes ces données ou non
					if ( $element->isSelected() && $infoTable['sdctl_select_all'] == 0 )
					{
						$this->_listTables[$table] = 0; // une partie des données
					}
					else
					{
						$this->_listTables[$table] = 1; // toutes les données
					}

					$where = "";
				}
			}

			// Ajout les éléments à supprimer
			$this->deleteElements($cxtname.'_'.$version);
			
			$this->addVersioning($cxtname, $version);
			
			if ( $this->_debug )
			{
				echo "\n";
			}
			// Compression du sous-contexte
			$this->compress($this->_directory.$cxtname.'_'.$version, $this->_directoryProduct);
		}

		// Compression du contexte
		if ( $this->_debug )
		{
			echo "\n\nCompression du contexte";
		}
		$this->compress($this->_directory, $cxtname.'_'.$version);
		
		return $cxtname.'_'.$version.'.tar.bz2';
	} // End function generate

	/**
	 * Création d'un fichier md5, il contient le md5 du nom du sous-contexte. Ca permettra de vérifier si le nom n'a pas changé.
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param string $cxtname nom du contexe
	 */
	private function generateMD5 ( $cxtname )
	{
		$md5 = md5($this->_directoryProduct);

		$file = "{$this->_directory}{$cxtname}/{$this->_directoryProduct}/md5";

		if ( $this->_debug )
		{
			echo "\n - Création du fichier md5 : {$md5}";
		}

		// Création du fichier
		touch($file);
		// On lui met tous les droits
		chmod($file, 0777);
		// Ajout du header
		file_put_contents($file, $md5, FILE_APPEND);
	} // End function generateMD5

	/**
	 * Création des fichiers csv dans lesquels les données seront insérées
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param array $list tableau contenant la liste des tables
	 * @param string $cxtname nom du contexe
	 */
	private function createFilesCSV ( $list, $cxtname )
	{
		foreach ( $list as $filename )
		{
			$file = "{$this->_directory}{$cxtname}/{$this->_directoryProduct}/{$filename}.csv";

			if ( !file_exists($file) )
			{
				if ( $this->_debug )
				{
					echo "\n        - Création du fichier csv : {$filename} [{$this->_directory}{$cxtname}/{$this->_directoryProduct}/]";
					echo "\n          header = ".implode(', ', $this->getColumns($filename));
				}
				// Création du fichier
				touch($file);
				// On lui met tous les droits
				chmod($file, 0777);
				// Ajout du header
				file_put_contents($file, implode("\t", $this->getColumns($filename))."\n", FILE_APPEND);
			}
			elseif ( $this->_debug )
			{
				echo "\n        - Création du fichier csv : {$filename} [{$this->_directory}{$cxtname}/{$this->_directoryProduct}/] (déjà présent)";
			}
		}
	} // End function createFilesCSV

	/**
	 * Insertion des données d'une table dans un fichier csv
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param string $table nom de la table
	 * @param string $cxtname nom du contexe
	 * @param string $where condition spécifie à appliquer sur la sélection des données à mettre dans le fichier (defaut null)
	 */
        // 2014/01/21 bz 39037 ajout du paramètre $element
	private function loadDataInFile ( $table, $cxtname, $where = null, $element=null )
	{
		// Création de la requête SQL
		// On utilise un COPY vers l'écran ce qui permet de rediriger directement le résultat dans un fichier et aussi
		// de pouvoir exécuter la requete en ligne de commande sans ce soucis de l'IP du produit 
		$selectCopy = preg_replace('/(\w*id_product\w*)/', $this->_patternReplaceIdProduct, implode(',', $this->getColumns($table)));
		
		// ******************************** //
		/* 
			GESTION D'UN CAS PARTICULIER  
			
			Cas particulier avec la table sys_definition_selecteur et le champ sds_sort_by. En effet, ce champ
			contient l'identifiant du produit. L'expression régulière précédent n'est pas appliqué il faut donc faire le faire de façon
			brutale. De plus comme ce champ contient d'autres informations que l'id produit, il ne faut pas qu'elles soient perdues.
		*/
		// On regarde si on est bien sur la bonne table
		if ( $table == 'sys_definition_selecteur' )
		{
			// on regarde aussi si le champ est bien présent
			if ( in_array('sds_sort_by', $this->getColumns($table)) )
			{
				// On modifie le CASE WHEN
				$tmpPatternReplaceIdProduct = str_replace('\1', 'split_part(\1, \'@\', 3) ', $this->_patternReplaceIdProduct);
				// On force l'identifiant du produit en text car le champ sds_sort_by est en format text
				$tmpPatternReplaceIdProduct = preg_replace('/THEN ([0-9]+)/', 'THEN \1::text', $tmpPatternReplaceIdProduct);
				// On modifie le select
				$selectCopy = preg_replace('/(sds_sort_by)/', "split_part(sds_sort_by, '@', 1) || '@' || split_part(sds_sort_by, '@', 2) || '@' ||".$tmpPatternReplaceIdProduct, implode(',', $this->getColumns($table)));
			}
		}
		// ******************************** //
		
		$query = sprintf(
					"COPY (SELECT %s FROM %s %s) TO stdout NULL ''",
					$selectCopy,
					$table,
					( $where ? 'WHERE '.$where : '' )
				);

		// Fichier csv dans lequel le résultat du copy est redirégé
		$file = "{$this->_directory}{$cxtname}/{$this->_directoryProduct}/{$table}.csv";

		// Création de la commande linux
                // 2014/01/21 bz 39037 requétage sur le bon produit
                // Dans le cas d'un contexte d'activation, les Graphs, dash sont portés par les contextes des slaves, 
                // il faut donc les récupérer sur les slaves et les remonter sur le master ensuite.
                // on ne requête donc pas sur le master si on est en activation : ajout de && !$this->_activation
                if(isset($element) && $element->isOnlyMaster() && !$this->_activation){
                // Si l'élément est géré par le master, il faut requéter sur celui-ci.
		$cmd = sprintf(
				'env PGPASSWORD=%s '.PSQL_DIR.'/psql -U %s %s -h %s -c "%s" >> "%s"',
									$this->_infoMasterProduct['sdp_db_password'],
									$this->_infoMasterProduct['sdp_db_login'],
                                    $this->_infoMasterProduct['sdp_db_name'],
                                    $this->_infoMasterProduct['sdp_ip_address'],
                                    $query,
                                    $file
                            );
                }
                else{
                    // sinon, on utilise les informations du produit courant
                    $cmd = sprintf(
                                    'env PGPASSWORD=%s '.PSQL_DIR.'/psql -U %s %s -h %s -c "%s" >> "%s"',
                $this->_infoProduct['sdp_db_password'],
                $this->_infoProduct['sdp_db_login'],
				$this->_infoProduct['sdp_db_name'],
				$this->_infoProduct['sdp_ip_address'],
				$query,
				$file
			);
                }

		if ( $this->_debug )
		{
			echo "\n          avec la condition suivante : ".( $where ? $where : 'AUCUNE' );
			echo "\n          	>> commande executée : {$cmd}";
		}

		// Exécute la commande
		exec($cmd, $r, $error);

		// S'il y a une erreur, on lève une exception pour arreter la génération du contexte
		if ( $error )
		{
			// Afin de mieux visualiser l'erreur SQL, en mode débug, on l'exécute via la classe DataBaseConnection pour récupérer l'erreur
			if ( $this->_debug )
			{
				$db = $this->getConnection($this->_idProduct);
				$db->execute($query);
				echo "\n".$db->getLastError();
			}
			throw new Exception (__T('A_E_CONTEXT_GENERATE_UNABLE_COPY_DATA_IN_FILE_CSV', $this->_infoProduct['sdp_label']));
		}
		
		// 16:28 09/07/2009 GHX
		// Ajout du nombre de ligne insérées dans le fichier
		if ( $this->_debug )
		{
			$nbLines = count(file($file));
			echo ' => '.$nbLines.' ligne'.($nbLines > 1 ? 's' : '');
		}
	} // End function loadDataInFile

	/**
	 * Insertion des éléments à supprimer, La liste des éléments à supprimer se trouvent dans la table sys_definition_context_element_to_delete
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param string $cxtname nom du contexe
	 */
	private function deleteElements ( $cxtname )
	{
		// Si on ne doit pas ajouter les éléments supprimer on quitte la fonction
		if ( !$this->_delete )
			return;
		
		if ( $this->_debug )
		{
			echo "\n\n Insertion des éléments à supprimer";
		}
		
		$tablename = 'sys_definition_context_element_to_delete';
		$this->createFilesCSV(array($tablename), $cxtname);
		$this->loadDataInFile($tablename, $cxtname, "sdcetd_table IN ('".implode("','", array_keys($this->_listTables))."')");
	} // End function deleteElements

	/**
	 * Compresse un répertoire : création d'un tar.bz2
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param string $patch chemin dans lequel ce trouve le dossier
	 * @param string $directory nom du dossier
	 */
	private function compress ( $patch, $directory )
	{
		if ( $this->_debug & 4 )
		{
			echo "\n>>> Compression inhibée ";
			return;
		}

		if ( substr($patch, -1) != '/' )
		{
			$patch .= '/';
		}

		// Commande pour aller dans le répertoire $directory
		$cmdCd  = "cd \"{$patch}{$directory}\"";
		// Commande pour créer l'archive $directory
		$cmdTar = "tar cfj \"../{$directory}.tar.bz2\" *";
		// Commande pour supprimer le répertoire $directory
		$cmdRm  = "rm -rf \"{$patch}{$directory}\"";

		if ( $this->_debug )
		{
			echo "\n>>> Compression du répertoire : {$directory} ";
			echo "\n     - {$cmdCd} ";
			echo "\n     - {$cmdTar} ";
			echo "\n     - {$cmdRm} ";
		}

		exec("{$cmdCd};{$cmdTar};{$cmdRm}", $r, $error);

		if ( $error )
			throw new Exception (__T('A_E_CONTEXT_GENERATE_UNABLE_COMPRESS'));
	} // End function compress

	/**
	 * Ajout le fichier de versioning dans le sous-contexte
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param string $cxtname nom du contexte
	 * @param string $version version du contexte
	 */
	private function addVersioning ( $cxtname, $version )
	{
		$file = "{$this->_directory}{$cxtname}_{$version}/{$this->_directoryProduct}/sys_versioning.csv";
		
		if ( $this->_debug )
		{
			echo "\n        - Ajout du fichier de versioning : sys_versioning [{$this->_directory}{$cxtname}_{$version}/{$this->_directoryProduct}/]";
			echo "\n            context_name   = {$cxtname}";
			echo "\n            context_version = {$version}";
		}
		
		// Création du fichier
		touch($file);
		// On lui met tous les droits
		chmod($file, 0777);
		// Ajout du header
		file_put_contents($file, "id\titem\titem_value"."\n", FILE_APPEND);
		// Ajout du contenu
		file_put_contents($file, "1\tcontext_name\t{$cxtname}"."\n", FILE_APPEND);
		file_put_contents($file, "2\tcontext_version\t{$version}"."\n", FILE_APPEND);
	} // End function addVersioning
	
	/**
	 * Retourne la liste des champs d'une table
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param string $table nom de la table
	 * @return array
	 */
	private function getColumns ( $table )
	{
		if ( !array_key_exists($table, $this->_tablesColumn) )
		{
			$querySelectColumn = "
					--- Récupère la liste des champs de la table {$table}

					SELECT
						a.attname as field,
						a.attnum-1 as order
					FROM
						pg_catalog.pg_attribute a
					WHERE
						a.attrelid IN (
									SELECT
										c.oid
									FROM
										pg_catalog.pg_class c
										LEFT JOIN pg_catalog.pg_namespace n
										ON n.oid = c.relnamespace
									WHERE
										c.relname ~ '^{$table}$'
										AND pg_catalog.pg_table_is_visible(c.oid)
								)
						AND a.attnum > 0
						AND NOT a.attisdropped
						AND a.attname <> 'the_geom'
					ORDER BY
						a.attnum
				";

			// 1/6/11 MMT DE 3rd axis, connection à la base slave au cas ou les colonnes sur le master n'existent pas sur le slave
			$db = $this->getConnection($this->_infoProduct['sdp_id']);

			$resultSelectColumn = $db->execute($querySelectColumn);

			while ( $row = $db->getQueryResults($resultSelectColumn, "one") )
			{
				$this->_tablesColumn[$table][$row['order']] = $row['field'];
			}

			if ( !array_key_exists($table, $this->_tablesColumn) )
				throw new Exception(__T('A_E_CONTEXT_UNDEFINED_TABLE', $table));
		}

		return $this->_tablesColumn[$table];
	} // End function getColumns

	/**
	 * Retourne la connexion à une base de données d'un produit
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param int $idProduct identifiant du produit on doit se connecter
	 * @return DataBaseConnection
	 */
	private function getConnection ( $idProduct )
	{
		if ( !array_key_exists($idProduct, $this->_listDb) )
		{
                        // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::get Connection()
			$this->_listDb[$idProduct] = Database::getConnection($idProduct);
			if ( $this->_debug & 2 )
			{
				$this->_listDb[$idProduct]->setDebug(1);
			}
		}

		return $this->_listDb[$idProduct];
	} // End function getConnection
	
	/**
	 * Retourne le code produit d'un produit donné.
	 * Cette méthode gère le cas Corporate il faut se baser sur "old_module"
	 * Méthode créer dans le cadre de la correction du bug BZ 14392
	 *
	 * @author BBX
	 * @version CB 5.0.2.12
	 * @since CB 5.0.2.12
	 * @param int $idProd id du produit
	 */		
	public function getModuleFromProduct($idProd = '')
	{
		$db = $this->getConnection($idProd);
		$queryModule = "SELECT saai_interface
		FROM 
		(
			SELECT parameters, value
			FROM sys_global_parameters
			WHERE parameters IN ('old_module','module')
			ORDER BY parameters DESC
			LIMIT 1
		) sgp 
		LEFT JOIN sys_aa_interface
		ON (lower(sgp.value) = lower(saai_module))";
		return $db->getOne($queryModule);	
	}

} // End class ContextGenerate
?>
