<?php
/**
 * Classe permettant de manipuler/récupérer les données d'un rapport
 *
 * @author GHX
 * @created 14:41 18/08/2009
 * @version	CB  5.0.0.06
 * @since CB 5.0.0.06
 *
 * 28/10/2009 GHX : Modification de la fonction delete() pour passer l'id produit à certaines fonctions
 * 20/01/2011 OJT : Ajout de la méthode getReportProducts
 */
class ReportModel
{
	/**
	 * Instance de connexion à la base de données
	 * @var DatabaseConnectoin
	 */
	static $database = null;
	/**
	 * Identifiant du produit sur lequel l'instance de la base de données est faite
	 * @var int
	 */
	static $databaseIdProduct = null;
	
	/**
	 * Identifiant du rapport
	 * @var string
	 */
	private $idReport;
	/**
	 * Tableau contenant les propriétés du schedule
	 * @var array
	 */
	private $properties;
	
	/**
	 * Constructeur
	 *
	 * @author GHX
	 * @version CB 5.0.0.06
	 * @since CB 5.0.0.06
	 * @param string $idReport identifiant d'un rapport (default null)
	 * @param int $idProduct identifiant du produit sur lequel on doit se connecter (default master product)
	 */
	public function __construct ( $idReport = null, $idProduct = "" )
	{
		self::initDatabaseConnection($idProduct);
		
		$this->idReport = $idReport;
		$this->properties = array();
		
		if ( !is_null($this->idReport) )
		{
			$sql = "
				SELECT 
					*
				FROM	
					sys_pauto_page_name
					LEFT JOIN sys_pauto_config USING(id_page)
				WHERE
					id_page = '{$this->idReport}'
				ORDER BY sys_pauto_config.ligne ASC
				";
			$results = self::$database->execute($sql);
			
			$infoReport = array(
						'page_name' => null,
						'droit' => null,
						'page_type' => null,
						'id_user' => null,
						'share_it' => null
				);
			$infoElements = array(
						'class_object' => null,
						'id_elem' => null,
						'page_type' => null,
						'ligne' => null,
						'id_product' => null
				);
		
			if ( self::$database->getNumRows() > 0 )
			{
				while ( $row = self::$database->getQueryResults($results, 1) )
				{
					if ( count($this->properties) == 0 )
					{
						$this->properties = array_intersect_key($row, $infoReport);
					}
					$this->properties['elements'][$row['id']] = array_intersect_key($row, $infoElements);
				}
			}
		}
	} // End function __construct
	
	
	/**
	 * Retourne la valeur d'une propiété du schédule. Si la propiété n'existe pas NULL est retourné
	 *
	 * @author GHX
	 * @version CB 5.0.0.06
	 * @since CB 5.0.0.06
	 * @param string $propertie
	 * @return mixed
	 */
	public function getProperty ( $property )
	{
		if ( array_key_exists($property, $this->properties) )
		{
			return $this->properties[$property];
		}
		return null;
	} // End function getProperty
	
	/**
	 * Retourne la liste de toutes les propiétés du schédule
	 *
	 * @author GHX
	 * @version CB 5.0.0.06
	 * @since CB 5.0.0.06
	 * @return array
	 */
	public function getProperties	()
	{
		return $this->properties;
	} // End function getPropertie
	
	/**
	 * Supprime le rapport. Retourne TRUE si la supression a été faite sinon un message d'erreur
	 *
	 * @author GHX
	 * @version CB 5.0.0.06
	 * @since CB 5.0.0.06
	 * @return boolean
	 */
	public function delete ()
	{
		// on verifie que le report n'appartient pas à un schedule
		$schedules = $this->getSchedules();
		
		if ($schedules)
			return "You cannot delete this report, because it belongs to these schedules : ";
		
		// Suppression du paramétrage des sélecteurs des dashboards
		$query = "SELECT id_elem, class_object FROM sys_pauto_config WHERE id_page = '{$this->idReport}'";
		$results = self::$database->execute($query);
		if ( self::$database->getNumRows() > 0 )
		{
			while ( $elem = self::$database->getQueryResults($results, 1) )
			{
				// Si cet élément est un dashboard, il faut supprimer sa configuration sélecteur
				if($elem['class_object'] == 'page')
				{
					// On regarde si on a configuré un sélecteur sur ce dashboard
					$idSelecteur = SelecteurModel::getSelecteurId($id_page,$elem['id_elem']);
					if($idSelecteur != 0)
					{
						// Suppression du sélecteur
						// 16:13 28/10/2009 GHX
						// On passe l'id du produit
						$selecteur = new SelecteurModel($idSelecteur, self::$databaseIdProduct);
						$selecteur->deleteSelecteur();
					}
				}
			}
		}
		
		// on supprime les dashboards et alarmes du report
		$query = "DELETE FROM sys_pauto_config WHERE id_page = '{$this->idReport}'";
		self::$database->execute($query);

		// on supprime le report
		$query = "DELETE FROM sys_pauto_page_name WHERE id_page = '{$this->idReport}'";
		self::$database->execute($query);

		return true;
	} // End function delete

	/**
	 * Retourne la liste des schedules qui contient le rapport
	 *
	 * @author GHX
	 * @version CB 5.0.0.06
	 * @since CB 5.0.0.06
	 * @return array
	 */
	public function getSchedules ()
	{
		$sql = "SELECT * FROM sys_report_schedule WHERE report_id LIKE '%{$this->idReport}%'";
		return self::$database->getall($sql);
	} // End function getSchedules
	
	/**
	 * Retourne la liste des identiants des rapports dans lesquels se trouvent le dashboard
	 *
	 * @author GHX
	 * @version CB 5.0.0.06
	 * @since CB 5.0.0.06
	 * @param string $idDash identifiant du dashboard
	 * @param int $idProduct : identifiant du produit sur lequel on doit se connecter (default master product )
	 * @return array
	 */	
	public static function getReportsIdFromDashboardId ( $idDash, $idProduct = '' )
	{
		self::initDatabaseConnection($idProduct);
		
		$query = "
			SELECT
				id_page
			FROM 
				sys_pauto_config
			WHERE id_elem = '{$idDash}'
			AND class_object = 'page'
			";
		
		return self::$database->getAll($query);
	} // End function getReportsIdFromDashboardId
	
	/**
     * Retourne la liste des identifiants de produit associés à un rapports
     *
     * @since  5.0.4.14
     * @param  string $idReport  Identifiant du rapport
     * @param  int    $idProduct Identifiant du produit sur lequel requêter
     * @return array Liste d'entier représentant les produits associés au rapport
     */
    public static function getReportProducts( $idReport, $idProduct = 0 )
    {
        $retVal = array();
		$database = Database::getConnection( $idProduct );
		$query = "SELECT DISTINCT id_product FROM sys_pauto_config WHERE id_page IN
                 (
                    SELECT id_elem FROM sys_pauto_config WHERE id_page IN
                    (
                        SELECT id_elem FROM sys_pauto_config WHERE id_page = '{$idReport}'
                    )
                 )";
        foreach ( $database->getAll( $query ) as $product )
        {
            $retVal []= $product['id_product'];
        }
        return $retVal;
    }

	/**
	 * Instancie une connexion à la base de données
	 *
	 * @author GHX
	 * @version CB 5.0.0.06
	 * @since CB 5.0.0.06
	 * @param int $idProduct identifiant du produit sur lequel on doit se connecter (default master product)
	 */
	private static function initDatabaseConnection ( $idProduct = "" )
	{
		// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
                self::$database = Database::getConnection();
                self::$databaseIdProduct = $idProduct;
	} // End function initDatabaseConnection
	
} // End class ReportModel
?>