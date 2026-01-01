<?php
/**
 * Classe permettant de manipuler/récupérer un schedule
 *
 * @author GHX
 * @created 14:41 18/08/2009
 * @version	CB  5.0.0.06
 * @since CB 5.0.0.06
 */
class ScheduleModel
{
	/**
	 * Valeur du séparateur des identifiants de rapports pour le champ report_id;
	 */
	const SEPARATOR_ID_REPORT = ',';
	
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
	 * Identifiant du schedule
	 * @var string
	 */
	private $idSchedule;
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
	 * @param string $idSchedule identifiant du schedule (default null)
	 * @param int $idProduct identifiant du produit sur lequel on doit se connecter (default master product)
	 */
	public function __construct ( $idSchedule = null, $idProduct = "" )
	{
		self::initDatabaseConnection($idProduct);
		
		$this->idSchedule = $idSchedule;
		$this->properties = array();
		
		if ( !is_null($this->idSchedule) )
		{
			$sql = "
				SELECT
					*
				FROM 
					sys_report_schedule 
					LEFT JOIN sys_report_sendmail USING(schedule_id)
				WHERE schedule_id = '{$this->idSchedule}'
				ORDER BY schedule_name ASC
				";
			$results = self::$database->execute($sql);
			
			$infoSchedule = array(
						'schedule_id' => null,
						'mailto' => null,
						'mailto_type' => null,
						'on_off' => null,
						'report_id' => null
				);
			$infoMail = array(
						'mailto' => null,
						'mailto_type' => null,
						'on_off' => null
				);
			
			if ( self::$database->getNumRows() > 0 )
			{
				while ( $row = self::$database->getQueryResults($results, 1) )
				{
					if ( count($this->properties) == 0 )
					{
						$this->properties = array_diff_key($row, $infoSchedule);
						$this->properties['report_id'] = explode(self::SEPARATOR_ID_REPORT, $row['report_id']);
					}
					$this->properties['mail'][] = array_intersect_key($row, $infoMail);
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
	 * Supprime un rapport du schedule
	 *
	 * @author GHX
	 * @version CB 5.0.0.06
	 * @since CB 5.0.0.06
	 * @param string $idReport identifiant du rapport à supprimer
	 * @return boolean
	 */
	public function deleteReport ( $idReport )
	{
		// Supprime le rapport de la liste
		$index = array_search($idReport, $this->properties['report_id']);
		if ( $index !== false )
		{
			unset($this->properties['report_id'][$index]);
		}
		else
		{
			return "The report is not present in the scheduler";
		}
		
		$listIdReport = implode(self::SEPARATOR_ID_REPORT, $this->properties['report_id']);
		
		$query = "UPDATE sys_report_schedule SET report_id = ".($listIdReport = '' ? "NULL" : "'{$listIdReport}'")." WHERE schedule_id = '{$this->idSchedule}'";
		self::$database->execute($query);
		
		return true;
	} // End function delete

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
		// suppression du schedule
		$query = "DELETE FROM sys_report_schedule WHERE schedule_id = '{$this->idSchedule}'";
		self::$database->execute($query);

		// suppression du envoi de mail du schedule
		$query = "DELETE FROM sys_report_sendmail WHERE schedule_id = '{$this->idSchedule}'";
		self::$database->execute($query);

		return true;
	} // End function delete

	/**
	 * Retourne la liste des schedules
	 *
	 * @author GHX
	 * @version CB 5.0.0.06
	 * @since CB 5.0.0.06
	 * @param int  $idProduct identifiant du produit sur lequel on doit se connecter (default master product)
	 * @return array
	 */
	public static function getAll ( $idProduct = '' )
	{
		self::initDatabaseConnection($idProduct);
		$sql = "
			SELECT
				*
			FROM 
				sys_report_schedule 
				LEFT JOIN sys_report_sendmail USING(schedule_id)
			ORDER BY schedule_name ASC
			";
		$results = self::$database->execute($sql);
		
		$infoSchedule = array(
					'schedule_id' => null,
					'mailto' => null,
					'mailto_type' => null,
					'on_off' => null
			);
		$infoMail = array(
					'mailto' => null,
					'mailto_type' => null,
					'on_off' => null
			);
			
		$schedules = array();
		if ( self::$database->getNumRows() > 0 )
		{
			while ( $row = self::$database->getQueryResults($results, 1) )
			{
				if ( !array_key_exists($row['schedule_id'], $schedules) )
				{
					$schedules[$row['schedule_id']] = array_diff_key($row, $infoSchedule);
					$schedules[$row['schedule_id']]['report_id'] = explode(self::SEPARATOR_ID_REPORT, $schedules[$row['schedule_id']]['report_id']);
				}
				$schedules[$row['schedule_id']]['mail'][] = array_intersect_key($row, $infoMail);
			}
		}
		
		return $schedules;
	} // End function getSchedules
	
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
	
} // End class ScheduleModel
?>