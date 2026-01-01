<?php

class DataBaseConnectionOracle
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
	 * Mot de passe de connexion à la BD
	 *
	 * @var string
	 */
	
	private $_db_pwd;
	
	/**
	 * Nom de la base de donées
	 *
	 * @var string
	 */
	
	private $_db_db;
	
	/**
	 * Connexion à la base de donées
	 *
	 * @var object
	 */
	
	private $_cnx;
	
	/**
	 * Last parsed query (retournée par oci_parse())
	 *
	 * @var object
	 */
	
	private $_query;
	
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
			
			$this->_db_host	= ALK_DB_SLM_HOST;
			$this->_db_port	= ALK_DB_SLM_PORT;
			$this->_db_login	= ALK_DB_SLM_LOGIN;
			$this->_db_db		= ALK_DB_SLM_NAME;
			$this->_db_pwd	= 'astellia';	// pourquoi le pwd n'est pas dans le fichier de conf ?
		}

		if (!$this->_db_host)		$this->_db_host = 'localhost';
		if ($this->_db_port)		$this->_db_port = ':'.$this->_db_port;
		$this->_cnx = oci_connect($this->_db_login, $this->_db_pwd, "$this->_db_host$this->_db_port/$this->_db_db");
		$this->_db_port = substr($this->_db_port,1);	// on enlève les : du debut

		// mysql_connect($this->_db_host.":".$this->_db_port, $this->_db_login, '');
		// mysql_select_db($this->_db_db);
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
		$this->_query = oci_parse ($this->_cnx, $sql);
		oci_execute ($this->_query);
		return $this->_query;
		// return mysql_query($sql);
	}
	
	/**
	* Renvoie les résultats d'une requete précedemment executée
	*
	* //@param object $result reference au résultat de la requete executée
	* @param string $nb nombre de résultats à renvoyer ("one" : un seul, "all" : tous)
	* @return array les résultats de la requete
	*/
	
	function getQueryResults($query_statement,$nb = "all")
	{
		if ($nb == "all") {
			oci_fetch_all($query_statement,$results,0,-1,OCI_ASSOC);
			return $results;			
		} else {
			return oci_fetch_array($query_statement, OCI_ASSOC);
		}
	}

}

?>