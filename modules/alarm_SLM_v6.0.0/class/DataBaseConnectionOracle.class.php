<?php


// cette fonction affiche la trace actuelle
function trace() {
	echo "<table style='color:#900;font:11px tahoma;'>
			<tr style='background:#BBB;'>
				<th colspan='4' align='left'>&nbsp;Backtrace :</th>
			</tr>
			<tr style='background:#BBB;'>
				<th>file</th>
				<th>line</th>
				<th>class</th>
				<th>function(<span style='color:#300;'>args</span>)</th>
			</tr>
			";
	$_ = debug_backtrace();
	while ( $d = array_pop($_) ) {
		foreach ($d['args'] as &$arg) {
				if (is_array($arg)) {
					$arg = '<pre>'.trim(var_export($arg,true)).'</pre>';
				} else {
					$arg = nl2br($arg);
				}
		}
		
		echo "
		<tr align='left' valign='top' style='background:#DDD;'>
			<td title='{$d['file']}'>".str_replace(REP_PHYSIQUE_NIVEAU_0,'/',$d['file'])."</td>
			<td align='right'>{$d['line']}</td>
			<td align='right'><strong>{$d['class']}</strong></td>
			<td>{$d['type']}<strong>{$d['function']}</strong>(<span style='color:#300;'>".implode('</span>,<span style="color:#300;">',$d['args'])."</span>)</td>
		</tr> ";
	}
	echo "</table>";
}



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
		
		// on verifie qu'on a bien une connexion
		if (!$this->_cnx) {
			$e = oci_error();
			$this->displayError("Oracle connexion error",$e);
		}

		// mysql_connect($this->_db_host.":".$this->_db_port, $this->_db_login, '');
		// mysql_select_db($this->_db_db);
	}
	
	/**
	*	Cette fonction affiche les messages d'erreur
	*
	*	@param string $title : titre de l'erreur
	*	@param object $e : objet de l'erreur
	*/
	public function displayError($title,$e) {
		echo "
		<div class='oracle_ko' style='margin:20px;border:20px solid #CCC;padding:4px;font-family:verdana;font-size:12px;'>
			<div style='margin:2px;padding:2px;background:#F66;'>$title</div>
			<table>
			";
		foreach ($e as $k => $v) {
			if ($k == 'sqltext') {
				echo "
					<tr>
						<td style='background:#CCC;font-size:11px;'>$k</td>
						<td style='font-size:11px;'><pre>".substr($v,0,$e['offset'])."<span style='background:#F99;'>".substr($v,$e['offset'])."</span></pre></td>
					</tr>";
			} else {
				echo "
					<tr>
						<td style='background:#CCC;font-size:11px;'>$k</td>
						<td style='font-size:11px;'>$v</td>
					</tr>";
			}
		}
		echo "</table></div>";
		trace();
		exit;
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
		// echo "<pre style='border:1px solid red; padding:2px;'>$sql</pre>";
		
		// we parse the query
		$this->_query = oci_parse ($this->_cnx, $sql);
		if (!$this->_query) {
			$e = oci_error($this->_cnx); // Connection resource passed
			$this->displayError("Oracle parse error",$e);
		}
		
		// we execute the query
		$rc = oci_execute ($this->_query);
		if (!$rc) {
			$e = oci_error($this->_query); // Statement resource passed
			$this->displayError("Oracle execution error",$e);
		}
		return $this->_query;
		// return mysql_query($sql);
	}
	
	/**
	* Renvoie les résultats d'une requete précedemment executée
	*
	* @param object $result reference au résultat de la requete executée
	* @param string $nb nombre de résultats à renvoyer ("one" : un seul, "all" : tous)
	* @return array les résultats de la requete
	*/
	
	function getQueryResults($query_statement,$nb = "all")
	{
		if ($nb == "all") {
			$rc = oci_fetch_all($query_statement,$results,0,-1,OCI_ASSOC+OCI_FETCHSTATEMENT_BY_ROW);
			if ($rc === false) {
				$e = oci_error($query_statement); // Statement resource passed
				$this->displayError("Oracle fetch all error",$e);
			}
			// on passe toutes les clés en lowercase
			$nb_rows = count($results);
			for ($i = 0; $i < $nb_rows; $i++) {
				$row = $results[$i];
				foreach ($row as $key => $val) {
					unset($results[$i][$key]);
					$results[$i][strtolower($key)] = $val;
				}
			}
			return $results;			
		} else {
			$rc = oci_fetch_array($query_statement, OCI_ASSOC);
			// echo '<hr/>';print_r($rc);echo "<br/>";echo "is array : ".is_array($rc);
			if (is_array($rc)) {
				// on passe les clés en lowercase
				$result = array();
				foreach ($rc as $key => $val) {
					$result[strtolower($key)] = $val;
				}
				return $result;
			} else {
				$e = oci_error($query_statement); // Statement resource passed
				if (is_array($e))
					$this->displayError("Oracle fetch error",$e);
			}
			return '';
		}
	}

}

?>