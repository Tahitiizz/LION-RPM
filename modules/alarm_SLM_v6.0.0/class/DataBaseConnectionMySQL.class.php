<?php

class DataBaseConnectionMySQL
{
	/**
	 * Nom de l'hôte de connexion à la BD
	 *
	 * @var string
	 */
	
	private $_db_host;
	
	/**
	 * Port de connexion à la BD
	 *
	 * @var integer
	 */
	
	private $_db_port;
	
	/**
	 * Login de connexion à la BD
	 *
	 * @var string
	 */
	
	private $_db_login;
	
	/**
	 * Nom de la base de donées
	 *
	 * @var string
	 */
	
	private $_db_db;
	
	/**
	 * Constructeur de la classe
	 *
	 * @param string $db_conf_url URL du fichier contenant les paramètres de connexion
	 */

	function __construct($db_conf_url)
	{
		if ($db_conf_url != '')
		{
			include_once $db_conf_url;

			// Utilisation des variables globales définis dans le script '$db_conf_url'
			
			$this->_db_host		= DB_HOST;
			$this->_db_port		= DB_PORT;
			$this->_db_login	= DB_LOGIN;
			$this->_db_db		= DB_DB;
		}

		mysql_connect($this->_db_host.":".$this->_db_port, $this->_db_login, '');
		mysql_select_db($this->_db_db);
	}
	
	/**
	 * Fonction "magique" de PHP appelé lors de la sérialisation de l'instance de cette classe depuis un autre script
	 *
	 * @return array tableau contenant les propriétés de l'instance
	 */

	public function __sleep()
    {
		return array_keys(get_object_vars($this));
    }
    
    /**
     * Fonction "magique" de PHP appelé lors de la déserialisation de l'instance de cette classe depuis un autre script
     *
     * @return object une instance de la classe
     */

    public function __wakeup()
    {
		return $this->__construct('');
    }
    
    /**
     * Permet d'executer une requete SQL
     *
     * @param string $sql la requete à executer
     * @return object reference au résultat de la requete executée 
     */

	function executeQuery($sql)
	{
		return mysql_query($sql);
	}
	
	/**
	 * Renvoie les résultats d'une requete précedemment executée
	 *
	 * @param object $result reference au résultat de la requete executée
	 * @param string $nb nombre de résultats à renvoyer ("one" : un seul, "all" : tous)
	 * @return array les résultats de la requete
	 */

	function getQueryResults($result, $nb = "all")
	{
		if ($nb == "all") {
			$results = array();

			while (@$row = mysql_fetch_array($result, MYSQL_ASSOC))
			{
				$results[] = $row;
			}
			return $results;			
		}
		else
		{
			return mysql_fetch_array($result, MYSQL_ASSOC);
		}
	}
}

?>