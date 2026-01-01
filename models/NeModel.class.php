<?php
/*
	13/05/2009 GHX
		- Création du fichier
	26/10/2009 GHX
		- Modif de la fonction getLabel
	03/12/2009 BBX
		- Ajout de la fonction getNumberOfNe(). BZ 13164
 * 31/01/2011 NSE bz 20160 : No RI value for mapped Network Element
 * 09/06/2011 MMT bz 22322 : ajout methode getUnMappedNE pour mapping inverse pour le NE dans les liens AA
 * 17/11/2011 ACS BZ 24535 Setup alarm: cannot save mapped Element in alarm
 * 25/11/2011 ACS BZ 24792 Virtual SAI of corporate are visibles in multiple GUI
 * 16/12/2011 ACS BZ 25168 pg_query error during schedule generation due to empty $arrayNe in getMapping method
*/
?>
<?php
/**
 * Cette classe permet de manipuler les éléments réseaux comme le mapping, les labels. Elle fait donc appel au table de topologie.
 *
 * @author GHX
 * @version CB4.1.0.00
 * @since CB4.1.0.00
 */
class NeModel
{
	/**
	 * Tableaux de toutes les connexion à la base de données
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var array
	 */
	private static $_listDb = array();

	/**
	 * Information sur le master produit
	 *
	 *	NOTE : ne pas confondre avec le master topologie qui peuvent être 2 produits instincts
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var array
	 */
	private static $_masterProduct = null;

	/**
	 * Information sur le produit master topologie
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var array
	 */
	private static $_masterTopology = null;

	/**
	 * Information sur les autres produits, contient aussi le master produit si c'est pas le master topologie
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var array
	 */
	private static $_othersProducts = array();
	
	
	/**
	 * Vérifie si un NE existe sur le produit donné
	 * Ajouté dans le cadre de la correction du bug 11482
	 *
	 * @author BBX
	 * @version CB5.0.2.1
	 * @since CB5.0.2.1
	 */	
	public static function exists($ne, $na, $idProduct = '')
	{
		self::init();
		
		if ( $idProduct == '' )
		{
			$idProduct = self::$_masterProduct['sdp_id'];
		}
		
		$db = self::getConnection($idProduct);
		
		$query = "SELECT eor_id FROM edw_object_ref
		WHERE eor_obj_type = '$na'
		AND eor_id = '$ne'";
		
		$db->execute($query);
		
		return ($db->getNumRows() > 0);
	}
	

	/**
	 * Initialise les attributs de classe
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 */
	private static function init ()
	{
		// Si aucune instance de DatabaseConnection n'est définie, on suppose que c'est la première fois qu'on fait appel à la classe
		// donc on peut initialiser les attributs de classe. Ca permet d'éviter de le faire plusieurs fois
        // 30/11/2010 BBX
        // Correction de l'utilisation de la variable $_listDb
		if ( count(self::$_listDb) == 0 )
		{
			$productsInformations = getProductInformations();
			foreach ( $productsInformations as $oneProduct )
			{
				if ( $oneProduct['sdp_master'] == 1 )
				{
					self::$_masterProduct = $oneProduct;
				}

				if ( $oneProduct['sdp_master_topo'] == 1 )
				{
					self::$_masterTopology = $oneProduct;
				}
				else
				{
					self::$_othersProducts[$oneProduct['sdp_id']] = $oneProduct;
				}
			}
                        self::$_listDb = $productsInformations;
		}
	} // End function __construct

	/**
	 * Retourne le(s) label(s) d'un (des) niveau(x) d'aggrégation(s). Si pas de label retourne FALSE
	 * ATTENTION : le mapping est pris en compte
	 *
     * 15/06/2011 OJT : Correction bz22547, on se connecte à la base local
     *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param $mixed $ne nom d'un élément réseau ou tableau d'éléments réseaux
	 * @param string $na niveau d'aggrégation sur lequel se trouve les éléments réseaux
	 * @param int $idProduct identifiant d'un produit (default master product)
	 * @return mixed
	 */
	public static function getLabel ( $ne, $na, $idProduct = '' )
	{
		self::init();
		
		// Si $ne  est une chaine de caractère
		if ( is_string($ne) )
		{
			// 17/11/2011 ACS BZ 24535 retrieve label when $ne contains the eor_id_codeq value
			$queryLabel = "
				SELECT
					eor_label,
					eor_id_codeq
				FROM
					edw_object_ref
				WHERE
					eor_id = '%s' OR eor_id_codeq = '%s'
					AND eor_obj_type = '%s'
				LIMIT 1
				";

			$db = self::getConnection($idProduct);
			$result = $db->getRow(sprintf($queryLabel, $ne, $ne, $na));

			// Si pas de résultat returne FALSE
			if ( $db->getNumRows() == 0 )
				return false;

			// On retourne le label si l'élément n'a pas de code équivalent (soit pas de mapping)
			// ou qu'il n'y pas de master topologie
			//  ou que le master est le même que le master topologie
			if ( empty($result['eor_id_codeq']) || self::$_masterTopology === null || $idProduct == self::$_masterTopology['sdp_id'] )
			{
				// Si pas de label retourne FALSE
				if ( empty($result['eor_label']) )
					return false;

				return $result['eor_label'];
			}

			// On regarde le label sur le master topologie
			$db = self::getConnection(self::$_masterTopology['sdp_id']);
			$result2 = $db->getRow(sprintf($queryLabel, $result['eor_id_codeq'], $result['eor_id_codeq'], $na));

			// Si pas de résultat returne FALSE
			if ( $db->getNumRows() == 0 )
				return false;

			// Si pas de label retourne FALSE
			// Si pas de label on retourne quand même le id codeq
			if ( empty($result2['eor_label']) )
				return $result['eor_id_codeq'];

			return $result2['eor_label'];
		}
		elseif ( is_array($ne) )
		{
            // 01/06/2011 OJT : Correction de la requête
            // 17/11/2011 ACS BZ 24535 retrieve label when $ne contains the eor_id_codeq value
			$queryLabel = "SELECT eor_id,eor_label,eor_id_codeq FROM edw_object_ref
                            WHERE (eor_id IN (%s) OR eor_id_codeq IN (%s)) AND eor_obj_type = '%s' ";

			$db = self::getConnection($idProduct);
			$neStr = implode(",", array_map(array(self, 'labelizeValue'), $ne));
			$result = $db->execute(sprintf($queryLabel, $neStr, $neStr, $na));

			// Si pas de résultat returne FALSE
			if ( $db->getNumRows() == 0 )
				return false;

			$resultLabels = array();
			$resultLabelsWidthCodeq = array();

			while ( $row = $db->getQueryResults($result, 1) )
			{
				// Si on n'a pas code équivalent ou pas de master topologie de
                // définie (normalement cas impossible) ou que le master produit
                // est lui même le master topologie
				if ( empty($row['eor_id_codeq']) || self::$_masterTopology === null || $idProduct == self::$_masterTopology['sdp_id'] )
				{
                    // 24/06/2011 OJT : Correction bz22709, gestion des NE sans label
                    // Les labels vides sont ajoutés au tableau de sortie
					$resultLabels[$row['eor_id']] = $row['eor_label'];
				}
				else
				{
					$resultLabelsWidthCodeq[$row['eor_id_codeq']] = $row['eor_id'];
				}
			}

			if ( count($resultLabelsWidthCodeq) > 0 )
			{
				$db = self::getConnection(self::$_masterTopology['sdp_id']);
				$neStr = implode(",", array_map(array(self, 'labelizeValue'), array_keys( $resultLabelsWidthCodeq ) ) );
				$result = $db->execute(sprintf($queryLabel, $neStr, $neStr, $na));

				if ( $db->getNumRows() > 0 )
				{
					while ( $row = $db->getQueryResults($result, 1) )
					{
						// Même si le label est vide, il est ajouté au tableau de sortie
						$resultLabels[$resultLabelsWidthCodeq[$row['eor_id']]] = $row['eor_label'];
					}
				}
			}

			if ( count($resultLabels) == 0 )
				return false;

			return $resultLabels;
		}
		
		return false;
	} // End function getLabel

	/**
	 * Retourne le mapping correspondant si pas de multi-produit retourne FALSE ou si l'identifiant du produit n'existe pas retourne FALSE dans le cas ou celui-ci est passé.
	 * Si un identifiant de produit est passé, le tableau retourné contient uniquement le mapping de ce produit (donc pas de notion de produit dans le tableau retounré)
	 *
	 * Le format du tableau $ne doit être le suivant : 
	 * Array (
	 *	[bsc] => Array (
	 *			[0] => bsc1,
	 *			[1] => bsc2,
	 *			...
	 *		),
	 *	[cell] => Array (
	 *			[0] => cell1,
	 *			...
	 *		),
	 *	...
	 * )
	 *
	 * Le tableau retourné est de la forme suivante : 
	 * Array (
	 *	[idProduct] => Array (
	 *			[bsc] => Array (
	 *					[eor_id_codeq] => [eor_id]
	 *					...
	 *				),
	 *			[cell] => Array (
	 *					...
	 *				)
	 *			...
	 *		),
	 *	...
	 * )
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param array $ne tableau d'élément réseau
	 * @param boolean $inverse TRUE si on inverse l'index et la valeur (c'est à dire que eor_id devient la clé et eor_id_codeq la valeur) (default FALSE)
	 * @param int $idProduct identifiant d'un produit (default null)
	 * @return array
	 */
	public static function getMapping ( $ne, $inverse = false, $idProduct = null )
	{
		self::init();
		
		if ( count(self::$_othersProducts) == 0 )
			return false;
	
		$resultMapping = array();
		
		$queryFieldIndex = 'eor_id_codeq';
		$queryFieldValue = 'eor_id';
		if ( $inverse )
		{
			$queryFieldIndex = 'eor_id';
			$queryFieldValue = 'eor_id_codeq';
		}
		
		// Boucle sur tous les produits qui ne sont pas le master topo
		foreach ( self::$_othersProducts as $oneProduct )
		{
			// Récupère une connexion sur le produit
			$db = self::getConnection($oneProduct['sdp_id']);
			// Boucle sur tous les types niveaux d'aggrégation
			foreach ( $ne as $na => $arrayNe )
			{
	            // 22/11/2011 BBX
	            // BZ 24764 : correction des notices php
	            // 16/12/2011 ACS BZ 25168 pg_query error during schedule generation due to empty $arrayNe in getMapping method
				if (count($arrayNe) > 0) {
					$query = "
						SELECT
							{$queryFieldIndex} AS index,
							{$queryFieldValue} AS value
						FROM
							edw_object_ref
						WHERE
							eor_obj_type = '{$na}'
							AND eor_id_codeq IN (".implode(",", array_map(array('self', 'labelizeValue'), $arrayNe)).")
						";
					
					$resultQuery = $db->execute($query);
					
					if ( $db->getNumRows() == 0 )
						continue;
					
					while ( $row = $db->getQueryResults($resultQuery, 1) )
					{
						$resultMapping[$oneProduct['sdp_id']][$na][$row['index']] = $row['value'];
					}
				}
			}
		}
		
		if ( $idProduct !== null )
		{
			//09/06/2011 MMT bz 22322 wrong test if productId provided
			if ( !array_key_exists($idProduct, self::$_othersProducts) ){
				return false;
			}
			
			if ( array_key_exists($idProduct, $resultMapping) )
			{
				$resultMapping = $resultMapping[$idProduct];
			}
		}
		
		return $resultMapping;
	} // End function getMapping
	
	/**
	 * 09/06/2011 MMT bz 22322
	 * Get the unmapped NEid for the given NA, single NE and product
	 * Return false if no mapping could be found for those criteria
	 * @param String $na the NA of the given NE
	 * @param String $ne  the mapped Ne id
	 * @param Int $idProduct the product id of the NE (should not be master topo or it will always return false)
	 * @return String NE id unmapped  or false if does not exist
	 */
	public static function getUnMappedNE( $na, $ne, $idProduct)
	{
		$neMappingArray = array($na => array($ne));
		$resultMapping = self::getMapping($neMappingArray,false,$idProduct);
		$ret = false;

		if ( !empty($resultMapping) && array_key_exists($na, $resultMapping) && array_key_exists($ne, $resultMapping[$na]))
		{
			$ret = $resultMapping[$na][$ne];
		}
		return $ret;
	}

    /**
	 * Retourne la liste des éléments mappés
         *  ceux subissant un mapping (qui ont leur colonne eor_id_codeq renseignée)
         *  - si pas de multi-produit retourne FALSE
         *  - si l'identifiant du produit est passé et qu'il n'existe pas, retourne FALSE.
	 *  - Si un identifiant de produit est passé, le tableau retourné contient uniquement le mapping de ce produit (donc pas de notion de produit dans le tableau retounré)
	 *
	 *
	 * Le tableau retourné est de la forme suivante :
	 * Array (
	 *	[idProduct] => Array (
	 *			[bsc] => Array (
	 *					[eor_id_codeq] => [eor_id]
	 *					...
	 *				),
	 *			[cell] => Array (
	 *					...
	 *				)
	 *			...
	 *		),
	 *	...
	 * )
	 *
	 * @author NSE
	 * @since CB5.0.4.17
	 * @param array $tab_na tableau des NA
	 * @param boolean $inverse TRUE si on inverse l'index et la valeur (c'est à dire que eor_id devient la clé et eor_id_codeq la valeur) (default FALSE)
	 * @param int $idProduct identifiant d'un produit (default null)
	 * @return array
         *
         * 31/01/2011 NSE : créée dans le cadre de la correction du bz 20160
	 */
	public static function getMapped ( $tab_na, $inverse = false, $idProduct = null )
	{
		self::init();

		if ( count(self::$_othersProducts) == 0 )
			return false;

		$resultMapping = array();

		$queryFieldIndex = 'eor_id_codeq';
		$queryFieldValue = 'eor_id';

		if ( $inverse )
		{
			$queryFieldIndex = 'eor_id';
			$queryFieldValue = 'eor_id_codeq';
		}

		// Boucle sur tous les produits qui ne sont pas le master topo
		foreach ( self::$_othersProducts as $oneProduct )
		{
			// Récupère une connexion sur le produit
			$db = self::getConnection($oneProduct['sdp_id']);

                        // Boucle sur tous les types niveaux d'aggrégation
                        foreach ( $tab_na as $na )
                        {
                                $query = "
                                        SELECT
                                                {$queryFieldIndex} AS index,
                                                {$queryFieldValue} AS value
                                        FROM
                                                edw_object_ref
                                        WHERE
                                                eor_obj_type = '{$na}'
                                                AND eor_id_codeq IS NOT NULL
                                        ";

                                $resultQuery = $db->execute($query);

                                if ( $db->getNumRows() == 0 )
                                        continue;

                                while ( $row = $db->getQueryResults($resultQuery, 1) )
                                {
                                        $resultMapping[$oneProduct['sdp_id']][$na][$row['index']] = $row['value'];
                                }
                        }

		}

		if ( $idProduct !== null )
		{
            // 01/06/2011 OJT : Correction d'un bug sur le test de l'id product
			if ( !array_key_exists($idProduct, self::$_othersProducts) )
				return false;

			if ( array_key_exists($idProduct, $resultMapping) )
			{
				$resultMapping = $resultMapping[$idProduct];
			}
		}

		return $resultMapping;
	} // End function getMapped


	/**
	 * Permet de "labeliser" une valeur cad de l'entourer de quotes
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param string $value la valeur à labeliser
	 * @return string la valeur labelisée
	 */
	private static function labelizeValue ( $value )
	{
		return "'".$value."'";
	} // End function labelizeValue

	/**
	 * Retourne la connexion à une base de données d'un produit
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param int $idProduct identifiant du produit on doit se connecter
	 * @return DatabaseConnection
	 */
	private static function getConnection ( $idProduct )
	{
            // 30/11/2010 BBX
            // Utilisation de la classe Database, gain de perf
            return Database::getConnection($idProduct);
            /*
		if ( !array_key_exists($idProduct, self::$_listDb) )
		{
			// On mémorise l'instance de l'objet pour éviter de refaire plusieurs fois une connexion sur la même base de données
			// car c'est relativement couteut
			self::$_listDb[$idProduct] = new DatabaseConnection($idProduct);			
		}

		return self::$_listDb[$idProduct];*/
	} // End function getConnection
	
	/**
	 * Retourne le nombre de NE présents en topologie
	 * Ajouté dans le cadre du bug BZ 13164
	 *
	 * @author BBX
	 * @version CB5.0.2.1
	 * @since CB5.0.2.1
	 * @param string : niveau d'agrégation
	 * @return int : nombre d'éléments présents en topologie
	 */	
	public static function getNumberOfNe($na,$idProduct='')
	{
		self::init();		
		if ( $idProduct == '' )
			$idProduct = self::$_masterProduct['sdp_id'];
		
		$db = self::getConnection($idProduct);	

		$query = "SELECT DISTINCT eor_id
		FROM edw_object_ref
		WHERE eor_obj_type = '{$na}'
		AND eor_on_off = 1";
		
		$db->execute($query);
		
		return $db->getNumRows();
	}
    
    /**
	 * 16/10/2012 MMT DE GIS 3D ONLY
     * Retourne le nombre de NE présents en topologie pour export GIS
	 *
	 * @author MMT
	 * @param string : niveau d'agrégation
     * @param int : id du produit
	 * @return int : nombre d'éléments présents en topologie
	 */	
	public static function getNumberOfNeForGisExport($na,$idProduct='')
	{
		self::init();		
		if ( $idProduct == '' )
			$idProduct = self::$_masterProduct['sdp_id'];
		
		$db = self::getConnection($idProduct);	

		$query = "SELECT count(*) 
		FROM sys_gis_topology_voronoi
		WHERE na = '{$na}'";
		
		$nb = $db->getOne($query);
		
		return $nb;
	}

    /**
     * Retourne la liste dédoublonnée de tous les NE existants dans les produits
     * donnés, sur le niveau d'agrégation donné. BZ 17929
     *
     * @author BBX
     * @param string $na : niveau d'agrégation
     * @param array $products : produits donnés
         * @param boolean $showLabel Optionnel, permet d'avoir le label du NE (en suffixe séparé par || )
     * @return array
     */
        public static function getNeFromProducts($na, array $products, $showLabel = false )
    {
        $defaultNel = array();
        foreach($products as $p)
        {
            $dbTemp = Database::getConnection($p);
            $query = "SELECT DISTINCT
                            eor_id,eor_label
                      FROM edw_object_ref
                      WHERE eor_obj_type = '$na'
                      AND eor_on_off = 1";
            $result = $dbTemp->execute($query);
                while( $row = $dbTemp->getQueryResults( $result, 1 ) )
                {
                    if( !$showLabel )
                    {
                $defaultNel[] = $row['eor_id'];
        }
                    else
                    {
                        $defaultNel[] = $row['eor_id'].'||'.$row['eor_label'];
                    }
                }
            }
        $defaultNel = array_unique($defaultNel);
        return array($na => $defaultNel);
    }

	/**
	 * Contruct the SQL clause to reject the virtual SAI for a request in network element table
	 * 
	 * @param string $tableName - optional: prefix used before attributeName
	 * @param string $attributeName - optional: default is 'eor_id' attribute 
	 * @param boolean $virtualNeRejected - optional: default is true (virtuals are rejected)
	 * 
	 * Ex: whereClauseWithoutVirtual('table') will returned: (table.eor_id NOT like ('virtual_%') AND table.eor_id NOT like ('%_virtual_%') )";
	 * 
	 * 25/11/2011 ACS BZ 24792 Virtual SAI of corporate are visibles in multiple GUI
	 */
	public static function whereClauseWithoutVirtual($tableName = '', $attributeName = 'eor_id', $virtualNeRejected = true) {
		$operande = "OR";
		$negStr = "";
		if ($virtualNeRejected) {
			$negStr = "NOT";
			$operande = "AND";
		}
		if ($tableName != '') {
			$tableName.= ".";
		}
		return "( ".$tableName.$attributeName." $negStr like ('virtual_%') $operande ".$tableName.$attributeName." $negStr like ('%_virtual_%') )";
	}

	/** Get NE list with various parameters (NE without label are in the end of the list)
	 * @param $na string aggregation level
	 * @param $products array product list, if null -> master will be used
	 * @param $limit integer the number of max result, if null -> no no no limit !
	 * @param $labelFilter string filter on NE label
	 */
	public static function getFilteredNeFromProducts($na, $products=null, $limit, $labelFilter=null) {
		
		$result = array();
		
		// Compute query parameters
		$products = $products?$products:array(null);								// If no product, master will be used
        $limit = $limit?" LIMIT $limit":'';           								// Compute limit
        
		// Escape special char		
		$labelFilter = addcslashes($labelFilter, '%\\_');
		$labelFilter = pg_escape_string($labelFilter);
		
        // Compute label filter
        $labelFilter = $labelFilter?" AND (eor_label ILIKE '%".$labelFilter."%' OR eor_id ILIKE '%".$labelFilter."%') ":'';
		    
		// For each product
		foreach($products as $p) {
			// Database connection

			$p = is_array($p)?$p['sdp_id']:$p;											    	
            $db = Database::getConnection($p);
            
			// Query for $p product                
            $query = "SELECT DISTINCT 
						eor_id,							
						CASE WHEN eor_label IS NOT NULL THEN 0 ELSE 1 END as order_col,
						CASE WHEN eor_label IS NOT NULL THEN eor_label||' ('||eor_id||')' ELSE '('||eor_id||')' END AS eor_label
					FROM edw_object_ref
					WHERE eor_id IS NOT NULL
						AND	eor_obj_type = '$na'
						AND	eor_on_off=1
						AND ".self::whereClauseWithoutVirtual()."	
						$labelFilter	
					ORDER BY order_col, eor_label
					$limit";
							
			// Execute query											
            $result = array_merge($result, $db->getAll($query));
        }
		// 19/03/2014 GFS - Bug 40125 - [SUP][T&A GW][#42712][ZainKW][Query Builder]:presence of duplicated networks elements in case of products with identics network elements.
		// 12/06/2014 GFS - Bug 42059 - [REC][CB 5.3.1.15][Multi Product][Query Builder] List of elements is NOT show all elements
        // Roll back
        return $result;            
	}
        
    /**
     * 29/01/2013 BBX
     * DE Ne Filter + Gis Filter
     * @param type $na
     * @param array $neParents
     * @param type $product
     * @return type
     */
    public static function getChildrenFromParents($na, array $neParents, $product=null)
    {
        $children = array();
        $db = Database::getConnection($product);
        $query = "SELECT eor_obj_type, eor_id
        FROM edw_object_arc_ref e1, edw_object_ref e0
        WHERE e1.eoar_id = e0.eor_id
        AND eor_on_off = 1
        AND eoar_arc_type LIKE '%|s|{$na}'
        AND eoar_id_parent IN ('".implode("','",$neParents)."')";  
        $result = $db->execute($query);
        // 06/06/2013 NSE bz 34040 : boucle infinie sur l'affichage de la liste des éléments enfants filtrés
        // s'il y a des résutlats
        if($db->getNumRows()>0){
            // on mémorise les enfants
            while($row = $db->getQueryResults($result, 1)) {
                $children[$row['eor_obj_type']][] = $row['eor_id'];
            }
            $childrentmp = array();
            foreach($children as $child => $neList) {
                // on recherche les enfants des enfants
                $childrentmp = array_merge($childrentmp, self::getChildrenFromParents($child, $neList, $product));
            }
            $children = array_merge($children, $childrentmp);
        }
        return $children;
    }
} // End class NeModel
?>
