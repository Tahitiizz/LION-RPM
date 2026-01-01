<?php
/*
	19/10/2009 GHX
		- Ajout de fonctions
*/
?>
<?php
/**
*	Classe permettant de gérer les connexions
*	Travaille sur les tables sys_definition_connection
*
*	@author	BBX - 11/09/2009
*	@version	CB 5.0.1.00
*	@since	CB 5.0.1.00
*
*
*/
class ConnectionModel
{
	/*
	*	Constantes
	*/
	
	// Table de config des connexions
	const CONNECTION_TABLE = 'sys_definition_connection';
	
	/*
	*	Variables
	*/
	public static $database;
	public static $product;
	private $idConnection = 0;
	private $connectionValues = Array();

	/**
	*	Constructeur
	*	@param int : id connexion
	*	@param int : id produit
	**/		
	public function __construct($idConnection,$product='')
	{
		// Instanciation de DatabaseConnection
		if(empty(self::$database) || ($product != self::$product))
			self::$database = Database::getConnection($product);
		
		// On mémorise l'id produit
		self::$product = $product;
		
		// Mémorisation de l'id connexion
		$this->idConnection = $idConnection;
		
		// Récupération des valeurs
		$query = "SELECT * FROM ".self::CONNECTION_TABLE." WHERE id_connection = ".$idConnection;
		$this->connectionValues = self::$database->getRow($query);
	}
	
	/**
         * Drops the connection
         * 11/10/2011 BBX : added to fix BZ 20433
         */
        public function drop()
        {
            $query = "DELETE FROM ".self::CONNECTION_TABLE." WHERE id_connection = ".$this->idConnection;
            self::$database->execute($query);
        }
	
	/**
	*	Récupère une valeur
	*	@param string : champ recherché
	*	@return string : valeur du champ recherché
	**/
	public function getValue($key)
	{
		if(isset($this->connectionValues[$key])) return $this->connectionValues[$key];
	}

	/**
	 * Dé
	 *
	 * @author GHX
	 * @param string $key nom du paramètre
	 * @param mixed $value valeur du paramètre
	 */
	public function setValue($key, $value)
	{
		$this->connectionValues[$key] = $value;
	} // End functoin setValue
	
	/**
	 * Ajoute la nouvelle connexion et retourne son nouvel identifiant
	 *
	 * @author GHX
	 * @return string
	 */
	public function add ()
	{
		$queryFields = '';
		$queryValues = '';
		
		foreach ( $this->connectionValues as $field => $value )
		{
			if ( $field == 'id_connection' )
				continue;
			
			$queryFields .= $field.',';
			if ( $value === null || $value === '' )
			{
				$queryValues.= 'NULL,';
			}
			else
			{
				$dataType = self::$database->getFieldType(self::CONNECTION_TABLE, $field);
				// Tableau des différents types chaine de postgresql
				$PGStringTypes = Array('character varying','varchar','character','char','text');		
				if ( in_array($dataType,$PGStringTypes) )
				{
					$queryValues .= "'".$value."',";
				}
				else
				{
					$queryValues .= $value.',';
				}
			}
		}
		
		if ( !empty($queryFields) )
		{
			$queryFields = substr($queryFields, 0, -1);
			$queryValues = substr($queryValues, 0, -1);
			$queryInsert = "INSERT INTO ".self::CONNECTION_TABLE." ({$queryFields}) VALUES ({$queryValues}) RETURNING *";
			$result = self::$database->execute($queryInsert);
			$row = self::$database->getQueryResults($result, 1);
			return $row['id_connection'];
		}
		
		return false;
	} // End function add
	
	/**
	 * Met à jour la connexion
	 *
	 * @author GHX
	 */
	public function update()
	{
		$queryUpdate = "UPDATE ".self::CONNECTION_TABLE." SET ";
		foreach ( $this->connectionValues as $field => $value )
		{
			if ( $field == 'id_connection' )
				continue;
				
			$queryUpdate .= $field.'=';
			if ( $value === null || $value === '' )
			{
				$queryUpdate.= 'NULL,';
			}
			else
			{
				$dataType = self::$database->getFieldType(self::CONNECTION_TABLE, $field);
				// Tableau des différents types chaine de postgresql
				$PGStringTypes = Array('character varying','varchar','character','char','text');		
				if ( in_array($dataType,$PGStringTypes) )
				{
					$queryUpdate .= "'".$value."',";
				}
				else
				{
					$queryUpdate .= $value.',';
				}
			}
		}
		
		$queryUpdate = substr($queryUpdate, 0, -1);
		$queryUpdate .= ' WHERE id_connection = '.$this->connectionValues['id_connection'];
		
		self::$database->execute($queryUpdate);
	} // End function update
	
	/*
	*	Méthodes statiques
	*/
	
	/**
	*	Récupère toutes les connexions existantes dans un tableau
        *       maj 28/05/2010 - DE Source Availability Ajout du paramètre $_condition
	**/
	public static function getAllConnections($product='', $_condition='')
	{
		// Instanciation de DatabaseConnection
		if(empty(self::$database) || ($product != self::$product))
			self::$database = Database::getConnection($product);
		
		// On mémorise l'id produit
		self::$product = $product;
			
		// Tableau des connexions
		$allConnections = Array();
		$_where = ( $_condition != ""  ) ? " {$_condition}" : "";
		// Requete de recherche dse connexions
		$query = "SELECT id_connection FROM ".self::CONNECTION_TABLE.$_where;
		$result = self::$database->execute($query);
		while($row = self::$database->getQueryResults($result,1)) {
			$allConnections[] = $row['id_connection'];
		}
		
		// Retour des connexions
		return $allConnections;
	}

        /**
         * Fonction qui récupère les types de fichier sur lesquels seront calculer les données Source Availability
         * @param integer $id_connection : id de la connexion (par défaut null pour récupérer les infos sur toutes les connexion)
         * @param integer $product : id du produit (par défaut null = produit master)
         * @return array()
         *
         */
        public static function getFileTypesPerConnection($product = 1, $id_connection = '', $_condition = '' )
        {
            if(empty(self::$database) || ($product != self::$product))
                    // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::get Connection()
                    self::$database = Database::getConnection($product);

            // On mémorise l'id produit
            self::$product = $product;

            // Tableau des connexions
            $fileTypesPerConnection = Array();

            $_where = ($id_connection !== '') ? " AND c.id_connection = {$id_connection}" : "";
            $_where.= $_condition;

            // Requete de recherche dse connexions
            $query = "  SELECT sdsftpc_id_flat_file, c.id_connection
                        FROM ".self::CONNECTION_TABLE." c, sys_definition_sa_file_type_per_connection s
                        WHERE s.sdsftpc_id_connection = c.id_connection
                            AND c.on_off = 1
                            AND sdsftpc_id_flat_file IN (
                                SELECT DISTINCT id_flat_file FROM sys_definition_flat_file_lib WHERE on_off = 1
                        )
                        {$_where}";

            $result = self::$database->getAll($query);

            foreach($result as $row )
            {
                    $fileTypesPerConnection[ $row['id_connection'] ][] = $row['sdsftpc_id_flat_file'];
}

            // Retour des connexions
            return $fileTypesPerConnection;
        }
}
?>