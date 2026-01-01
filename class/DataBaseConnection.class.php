<?php
/**
* DataBaseConnection : la classe d'accès à la base de données.
*
* Cette classe permet d'accéder à la base de données.
*
* Les fonctions getall(), getrow() et getone() miment les mêmes fonctions existant dans la classe ADOdb (http://adodb.sourceforge.net/)
*
* @author BAC, CCT, SLC
* @version CB4.1.0.0
*
*
* maj 20/11/2008, benoit : ajout du setter du débugage
* maj 20/11/2008, benoit : ajout d'une methode de renvoi du timestamp en secondes et utilisation de celle-ci pour l'affichage du temps d'exection des requetes en mode debug
* MaJ 21/11/2008 - SLC - ajout de la méthode getPgLastOid()
* maj 27/11/2008, benoit : désactivation du debug dans la méthode 'getQueryResults()'
* maj 12/02/2009 GHX : prise en compte du parametre débug database_connection qui affiche ou non les requetes SQL qui plante
* maj 19/02/2009 GHX : ajout du nom de la base et de l'ip dans le message d'erreur
* maj 28/04/2009 GHX : ajout du nom de la base dans le debug & affichage de la classe qui appel la fonction execute pour savoir où on fait exécute la requete
* maj 20/05/2009 MPR : ajout du paramètre display_erros qui permet de masquer l'affichage des erreurs lors de l'exécution d'une requête
* 13/07/2009 GHX : ajout de la fonction getIndexes($table)
* 13:28 05/10/2009 GHX : ajout de la fonction getSize()
* 11:41 12/10/2009 GHX ; ajout de la fonction getColumns($table)
* 09:17 13/10/2009 GHX : ajout de la fonction getPGStringTypes()
*
*	05/03/2010 BBX :
*		- Modification de la classe pour ajouter les fonctionnalités de connexions persistantes ( != pg_pconnect !!! )
*		- Pour celà, les méthodes suivantes ont été ajoutées (voir commentaire de chaque fonction) :
*			=> connectionExists()
*			=> dropConnection()
*			=> localConnection()
*			=> saveConnection()
*			=> useConnection()
*		- Les propriétés suivantes ont été ajoutées (voir commentaire des propriétés)
*			=> _idConnection
*			=> savedConnections
*		- Les méthodes suivantes ont été modifiées :
*			=> __construct()
*			=> close()
*
* 2010/03/08 MGD - BZ 17167 - Changement du nom de la variable $DBUser -> $AUser
 * 05/10/2010 NSE bz 18300 pas d'affichage lorsqu'il n'y a pas de résultat + optimisation méthode getRow
 * 21/01/2011 MMT DE Xpert ajout de la methode getColumnValues
 * 21/04/2011 NSE DE Non unique Labels : création de la méthode doesColumnExist($table,$column)
 * 13/05/2011 NSE DE Topology characters replacement : Ajout de la méthode doesTableExist
 * 01/08/2011 NSE bz 22558 : ajout des méthodes CopyPrepare(), CopyLine() et CopyEnd()
 * 13/12/2011 SPD1 ajout d'un parametre $info facultatif dans les methodes d'execution de requete (permet d'obtenir le nombre de resultat et le temps d'execution) 
 * 20/12/2011 ACS BZ 25191 PSQL error in setup product with disabled product
 * 21/12/2011 ACS BZ 25191 Warnings displayed in partitionning menu
 * 05/04/2012 MMT : Bz 26995 utilisation de 'lower' au lieu de ilike pour eviter la prise en compte
 * 22/05/2013 : T&A Optimizations
*/

class DataBaseConnection
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
	 * Nom du la connexion
	 *
	 * @var string
	 */

	private $_cnx;

	/**
	 * Variable de debug
	 *
	 * @var string
	 */
	public $debug = false;

	/**
	 * Active ou non les requêtes SQL qui plante (définit dans la table sys_debug par le paramètre database_connection)
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var int (default 0)
	 */
	private static $_debugQuery;

	/**
	 * Liste des query executées
	 *
	 * @var array
	 */
	private $_query_list = array();

	/**
	 * Compteur des queries affichées via $this->displayQueries()
	 *
	 * @var array
	 */
	private $_nb_query_displayed = 0;

	/**
	 * Résultat de l'éxécution d'une requête
	 *
	 * @var ressource
	 */
	private $_lastResult = null;

	/**
	 * Etat de la connexion
	 *
	 * @var array
	 */

	private $_connection_state = false;

	/**
	 * Statut du débugage
	 *
	 * @var boolean
	 */

	private $_debug = false;


	/**
	 * Mémorise l'id de connexion en cours
	 *
	 * @var mixed
	 */
	private $_idConnection;


	/**
	 * Mémorise les connexions ouvertes
	 *
	 * @var array
	 */
	private static $savedConnections = Array();

	// 05/11/2008 - Modif. benoit : reformatage du constructeur pour tenir compte des différentes valeurs pouvant prendre l'argument '$db_conf_url' (renommé en '$connection')
	// 07/11/2008 - Modif. Stephane : ajout paramètre PGSQL_CONNECT_FORCE_NEW sur tous les pg_connect pour eviter que deux instances de la classes qui auraient la même chaîne de connexion ne se partage la même connexion
	// 15/12/2011 - Modif. SPD1 : ajout d'un paramètre $readOnly pour se connecter à l'aide de l'utilisateur postgres 'read_only_user' (droits en lecture seule sur la base) pour Query Builder V2
	/**
	 * Constructeur de la classe
	 *
	 * @param mixed $connection paramètre de connexion à la base de donnée ciblée. Ce paramètre peut prendre 3 états. S'il est vide, les paramètre de connexion à la base seront ceux présents dans le fichier 'php/xbdd.inc'. Si c'est une chaine de caractères alors on considère que c'est une chaine de connexion de la forme "host=A port=B dbname=C user=D password=E". Enfin, si c'est un nombre alors on considère qu'il s'agit d'une connexion référencée dans la table 'sys_definition_product' de la base "maitre"
	 * @param boolean $readOnly : true pour se conncter avec le user qui a les droits read-only sur la base
	 */
	public function __construct($connection = '', $readOnly = false)
	{						
		// Postgres user name for read only connection
		$readOnlyUser = 'read_only_user';
		
		// Construction de l'id de la connexion
		$idConnection = empty($connection) ? 0 : $connection;

		if ($readOnly) {
			$idConnection.='_readonly';
		}
		
		// Si la connexion existe déjà
		if(self::connectionExists($idConnection))
		{
			// On réutilise la connexion ouverte
			$this->useConnection($idConnection);
		}
		// Si la connexion n'existe pas
		else
		{
			try
			{
				//
				// Cas 1 : si le paramètre "connection" est vide
				//
				if(empty($connection))
				{
					// On effectue une connexion locale
					$this->localConnection();
				}

				//
				// Cas 2 : le parametre "connection" est une chaine de caractères (non vide)
				//
				elseif (!is_numeric($connection) && !empty($connection))
				{
					// On tente la connexion avec cette chaine
					$this->stringConnection($connection);
				}

				//
				// Cas 3 : le parametre "connection" est un entier correspondant à l'id de 'sys_definition_product' à utiliser
				//
				elseif (is_numeric($connection))
				{
					// On effectue une connexion à la base locale
					if(self::connectionExists(0)) $this->useConnection(0);
					else $this->localConnection();

					// Puis on récupère le produit qui correspond à l'id "connection"
					$sql = "SELECT * FROM sys_definition_product WHERE sdp_id = ".$connection;
					$row = $this->getRow($sql);

					// 20/12/2011 ACS BZ 25191 useConnection and localConnection put the connection_state to true but we are not connected to the correct product yet
					$this->_connection_state = false;

					// Si le produit existe
					if (count($row) > 0)
					{
						// Une fois les paramètres de connexion à la base distante récupérés, on établit la connexion à cette base
						$this->_db_host = $row['sdp_ip_address'];
						$this->_db_db = $row['sdp_db_name'];
						
						// If read only connection
						if ($readOnly) {
							$this->_db_login = $readOnlyUser;
							$this->_db_pwd = $readOnlyUser;
                                                        // 01/02/2013 BBX
                                                        // Using persistant connections
							$this->_cnx = $this->connect("host=".$row['sdp_ip_address']." port=".$row['sdp_db_port']." dbname=".$row['sdp_db_name']." user=".$readOnlyUser." password=".$readOnlyUser);							
						} else {													
							$this->_db_login = $row['sdp_db_login'];	// On sauvegarde également le login à la base de données (YNE 19/07/10)
							$this->_db_pwd = $row['sdp_db_password'];
                                                        // 01/02/2013 BBX
                                                        // Using persistant connections
							$this->_cnx = $this->connect("host=".$row['sdp_ip_address']." port=".$row['sdp_db_port']." dbname=".$row['sdp_db_name']." user=".$row['sdp_db_login']." password=".$row['sdp_db_password']);
						}
						                		               			

						if (!$this->_cnx) {
							throw new Exception('Connection failed ['.$this->_db_host.' : '.$this->_db_db.']');
						}
						else {
							// On mémorise la connexion à ce produit
							self::saveConnection($connection.($readOnly?'_readonly':''),$this->_cnx,$this->_db_host,$this->_db_db,$this->_db_login,$this->_db_pwd);
						}
					}
					else
					{
						// Sinon on déclenche une exception
						throw new Exception('The product '.$connection." is not defined in 'sys_definition_product'<br/>");
					}
				}

				// Si aucune erreur n'est survenue, on positionne l'état de la connexion à "true" (= établit)
				$this->_connection_state = true;

				if ( self::$_debugQuery === null )
				{
					 self::$_debugQuery = $this->getOne("SELECT value FROM sys_debug WHERE parameters = 'database_connection'");
				}
			}
			catch (Exception $e) {
        		// 13/12/2010 BBX
        		// Erreur affichée désormais dans le tracelog
        		// BZ 18510
        		//echo $e->getMessage();
        		sys_log_ast('Critical', 'Trending&Aggregation', 'Database', $e->getMessage(), 'support_1', '');
			}
		}
	}

	/**
         * Connects to a database.
         * In case of failure, tries to reconnect several times.
         * If connection cannot be established the target product is disabled
         * and an Exception is sent.
         * Added for BZ 18510
         * @param string $conString
         * @param connection option $opt
         */
        public function connect($conString, $opt = null)
        {
            // First connection attemp
            if(!empty($opt)) $connection = @pg_connect($conString,$opt);
            else             $connection = @pg_connect($conString);

            // If connection OK, let's stop here and return instance
            if($connection) return $connection;

            // IN MOST CASES FUNCTION WILL STOP HERE //

            // Fetching target product ID
            $productId = ProductModel::getProductFromDatabase($this->_db_db, $this->_db_host);

            // Test if product already disabled
            // In this case we just return false
            foreach(ProductModel::getInactiveProducts() as $p) {
                if($p['sdp_id'] == $productId) {
                    return false;
                }
            }

            // Counts number of attemps
            $numberOfAttemps = 1;

            // Max number of attemps
            $maxNumberOfAttemps = (int)get_sys_global_parameters('number_of_connection_tries');

            // While connection failed
            while(!$connection && ($numberOfAttemps < $maxNumberOfAttemps))
            {
                // Sleeping for 0.5s
                usleep(500000);

                // Another connection attemp
                if(!empty($opt)) $connection = @pg_connect($conString,$opt);
                else             $connection = @pg_connect($conString);

                // Counting
                $numberOfAttemps++;
            }

            // If Connection finnaly Ok, let's stop here and return instance
            if($connection) return $connection;

            // Fetching Master ID
            $masterId = ProductModel::getIdMaster();

            // If product is Master or undetermined let's just return false
            if(($productId == 0) || ($productId == $masterId))
                return false;

            // Connection still failed. I have no choice but to :
            // - disable target product
            ProductModel::fastDesactivation($productId);
            ProductModel::deployProducts();
            // - trace message
            $productLabel = '';
            foreach(ProductModel::getInactiveProducts() as $p) {
                if($p['sdp_id'] == $productId) {
                    $productLabel = $p['sdp_label'];
                    break;
                }
            }
            $message = __T('A_DATABASE_PRODUCT_AUTO_DISABLED',$productLabel);
            // - send exception
            throw new Exception($message);
            // Sorry buddy :(
        }

	// 20/12/2011 ACS BZ 25191 create getter to retrieve information of "connection_state"
	/**
	 * @return true if connection is ok
	 */
	public function isConnectionOk() {
		return $this->_connection_state;
	}

	/**
	 * 	Vérifie l'existance et le fonctionnement d'une connexion
	 *	@param int : id de la connexion
	 *	@return bool
	 */
	public static function connectionExists($idConnection)
	{
		$status = false;
		if(isset(self::$savedConnections[$idConnection])) {
			$status = true && @pg_last_error(self::$savedConnections[$idConnection]['_cnx']);
		}
		return $status;
	}

	/**
	 * 	Sauvegarde une connexion
	 *	@param int : id de la connexion
	 *	@param ressource : instance de connexion
	 *	@param string : hote
	 *	@param string : nom de la base
	 **	@param string : login de la base
	 **	@param string : mot de passe de la base
	 */
	public static function saveConnection($idConnection,$cnx,$host='',$name='',$login='',$pwd='')
	{
		if($cnx) {
			self::$savedConnections[$idConnection]['_cnx'] = $cnx;
			self::$savedConnections[$idConnection]['_db_host'] = $host;
			self::$savedConnections[$idConnection]['_db_db'] = $name;
			self::$savedConnections[$idConnection]['_db_login'] = $login;
			self::$savedConnections[$idConnection]['_db_pwd'] = $pwd;
		}
	}

	/**
	 * 	Supprime une connexion existante
	 *	@param int : id de la connexion
	 */
	public static function dropConnection($idConnection)
	{
		if(isset(self::$savedConnections[$idConnection]))
			unset(self::$savedConnections[$idConnection]);
	}

	/**
	 * 	Récupère une connexion existante
	 *	@param int : id de la connexion
	 */
	public function useConnection($idConnection)
	{
		$this->_idConnection = $idConnection;
		$this->_cnx = self::$savedConnections[$idConnection]['_cnx'];
		if($this->_cnx) {
			$this->_db_host = self::$savedConnections[$idConnection]['_db_host'];
			$this->_db_db = self::$savedConnections[$idConnection]['_db_db'];
			$this->_db_login = self::$savedConnections[$idConnection]['_db_login'];
			$this->_db_pwd = self::$savedConnections[$idConnection]['_db_pwd'];
			$this->_connection_state = true;
		}
	}

	/**
	 * 	Effectue une connexion locale
	 */
	public function localConnection()
	{
		include dirname(__FILE__).'/../php/xbdd.inc';

		$this->_db_host = $AHost;
		$this->_db_db = $DBName;
		$this->_db_login = $AUser;
		$this->_db_pwd = $APass;

                // 13/12/2010 BBX
                // Using connect() instead of pg_connect
                // BZ 18510
		//$this->_cnx = @pg_connect("host=$AHost port=$Aport dbname=$DBName user=$AUser password=$APass",PGSQL_CONNECT_FORCE_NEW);
                // 01/02/2013 BBX                                                        // Using persistant connections
                $this->_cnx = $this->connect("host=$AHost port=$Aport dbname=$DBName user=$AUser password=$APass");

		if (!$this->_cnx) {
			throw new Exception('Connection failed ['.$this->_db_host.' : '.$this->_db_db.']<br/>');
		}
		else {
			self::saveConnection(0,$this->_cnx,$AHost,$DBName,$AUser, $APass); // 2010/08/03 MGD, correction BZ 17167
			$this->_connection_state = true;
			$this->_idConnection = 0;
		}
	}

	/**
	 * 	Effectue une connexion depuis une chaine de connexion
	 *	@param string : chaine de connexion
	 */
	public function stringConnection($conString)
	{
            // 13/12/2010 BBX
            // Using connect() instead of pg_connect
            // BZ 18510
            //$this->_cnx = @pg_connect($conString,PGSQL_CONNECT_FORCE_NEW);
            // 01/02/2013 BBX                                                        
            // Using persistant connections
            $this->_cnx = $this->connect($conString);

		if (!$this->_cnx) {
			throw new Exception("Connection failed. The connection string '".$conString."' is incorrect<br/>");
		}
		else {
			self::saveConnection($conString,$this->_cnx);
			$this->_connection_state = true;
			$this->_idConnection = $conString;
		}
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
     * Recording a query
     * @param string $sql
     * @param float $chrono_start
     * @return type 
     */
    protected function recordQuery($sql, $chrono_start)
    {
        $chrono = round(($this->microtime_float()-$chrono_start), 4);
        if (sizeof($this->_query_list) < 200) {
                $this->_query_list[] = array(
                        'query'	=> $sql,
                        'time'	=> $chrono,
                        'error'	=> is_resource($this->_cnx) ? pg_last_error($this->_cnx) : '',
                );
        }
        return $chrono;
    }

	// maj 20/05/2009 MPR : ajout du paramètre display_erros qui permet de masquer l'affichage des erreurs lors de l'exécution d'une requête
	// maj 13/12/2011 SPD1 : ajout du parametre $info, permet d'obtenir des info sur la requere (temps d'execution, nombre de resultats)
	/**
	* Permet d'executer une requete SQL
	*
	*	12/02/2009 GHX
	*		- affiche la requete qui plante uniquement si la variable database_connection de la table sys_debug est à 1
	*
	* @param string $sql la requete à executer
	* @param boolean $display_errors affichage des erreurs rencontrées
	* @param objet $info facultatif, permet d'obtenir des info sur la requete (temps d'execution, nombre de resultats) 
	* @return object reference au résultat de la requete executée
	*/
	public function executeQuery($sql,$display_errors=1, &$info=null)
	{
            // Testing connection state
            if (!$this->_connection_state) {
                echo "Connection does not exist.<br/>";
                return;
            }

            // If debug enabled, tracing execution time            
			$chrono_start = $this->microtime_float();
            
            // Executing query
            $result = pg_query($this->_cnx,$sql);

            // If debug enabled, tracing execution time
            if($this->_debug) {
                $chrono = $this->recordQuery($sql, $chrono_start);
            }

            // If query failed
            if ( !$result)
            {
                // 01/02/2013 BBX
                // Test de la connexion
                if(pg_connection_status($this->_cnx) !== PGSQL_CONNECTION_OK) {
                    // Si la connexion a laché on reconnecte
                    $this->resetConnection();
                    // Et on retente l'éxécution de la requête
                    $result = pg_query($this->_cnx,$sql);
                }
            }
            
            // If query still failed
            if ( !$result)
            {
                if($display_errors)
                {
					if ($info) {
						// Save the execution time in the info object
						$info->executionTime = round(($this->microtime_float()-$chrono_start), 4);
					}
				
                    // 14:16 12/02/2009 GHX
                    // affiche la requete qui plante uniquement si la variable database_connection de la table sys_debug est à 1
                    if (  self::$_debugQuery )
                    {
                        // echo '<div class="errorMsg"><u>ERREUR SQL ['.$this->_db_host.' : '.$this->_db_db.']:</u><br /><pre>'.$sql.'</pre><br />'.$this->getLastError().'</div>';

                        // debug SLC : dans ma version, on affiche de backtrace dès qu'on a une erreur SQL
                        echo "<table style='color:#900;font:11px tahoma;'>
                                        <tr style='background:#666;color:white;'>
                                                <th colspan='4' align='left'>&nbsp;ERREUR SQL [$this->_db_host:$this->_db_db]</th>
                                        </tr>
                                        <tr style='background:#CCC;color:black;'>
                                                <td colspan='4' align='left'>".$this->pretty($sql)."</td>
                                        </tr>
                                        <tr style='background:#666;color:white;'>
                                                <td colspan='4'>&nbsp;".$this->getLastError()."</td>
                                        </tr>


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
                                // $d['args'] contient tous les arguments passés à la fonction $d['function']
                                // SI JAMAIS un de ces arguments est un objet qui ne peut pas se transformer en string, on va avoir une belle erreur
                                // donc on boucle sur les arguments et on transforme les objets en string
                                if ($d['args'])
                                        foreach ($d['args'] as &$arg)
                                                if (is_object($arg))
                                                        $arg = "Objet de type ".get_class($arg);

                                echo "
                                <tr valign='top' style='background:#DDD;'>
                                        <td title='{$d['file']}'>".str_replace(REP_PHYSIQUE_NIVEAU_0,'/',$d['file'])."</td>
                                        <td align='right'>{$d['line']}</td>
                                        <td align='right'><strong>{$d['class']}</strong></td>
                                        <td>{$d['type']}<strong>{$d['function']}</strong>(<span style='color:#300;'>".nl2br(implode('</span>,<span style="color:#300;">',$d['args']))."</span>)</td>
                                </tr> ";
                        }
                        echo "</table>";
                    }
                }
                return;
            }

            //19/11/2008 BBX : sauvegarde du dernier résultat dans l'objet
            $this->_lastResult = $result;
			if ($info) {
				// Save the execution time in the info object
				$info->executionTime = round(($this->microtime_float()-$chrono_start), 4);
			}
            // debug
            if ($this->debug) 
            {
                $_ = debug_backtrace();
                $f = null;
                while ( $d = array_pop($_) ) {
                        // 28/04/2009 GHX
                        // modification de la condition
                        if (strtolower($d['class']) == strtolower(__CLASS__) )
                        {
                                $f['line'] = $d['line'];
                                break;
                        }
                        $f = $d;
                }
                echo "<div class='sql_debug'>";
                // 28/04/2009 GHX
                // Ajout du nom de la base dans le debug
                echo "<u>$sql</u> : [function : <code>{$f['class']}::{$f['function']}()</code> - line <code>{$f['line']}</code> - database <code>".pg_dbname($this->_cnx)."</code>]
                        <pre>".str_replace(array("<", ">"), array('&lt;','&gt;'), $sql)."</pre>";

                if ( !$result )
                {
                        echo '<span style="color:red">'.pg_last_error().'</span><br />';
                }
                else
                {
                         echo '<u>num_rows :</u> <code>'.(pg_num_rows($result)+pg_affected_rows($result)).'</code><br />';
                         // 20/11/2008 - Modif. benoit : ajout de l'affichage du temps d'execution de la requete dans le debug
                         echo '<u>execution time (in seconds) : '.$chrono.' second(s)</u><br/>';
                }

                echo "</div>";
            }

            // Returning query result
            return $result;
	}

	/**
	 * Renvoie les résultats d'une requete précedemment executée
	 *
	 * @param object $result reference au résultat de la requete executée
	 * @param string $nb nombre de résultats à renvoyer ("one" : un seul, "all" : tous)
	 * @param objet $info facultatif, permet d'obtenir des info sur la requete (temps d'execution, nombre de resultats)
	 * @return array les résultats de la requete
	 */

	public function getQueryResults($result, $nb = "all", &$info = null)
	{
		if (!$this->_connection_state) {
			echo "Connection does not exist.<br/>";
			return;
		}

		if ($info) {
			// Save the number of results in the info object
			$info->nbResults = $result?pg_num_rows($result):0;
		}
		
		// 2010/08/03 - MGD - BZ 16858 : On test la presence de resultats avant de les récuperer.
		if (!$result) {
			// 05/10/2010 NSE bz 18300 pas d'afficahge lorsqu'il n'y a pas de résultat
                        //echo "No result to fetch.<br/>";
			return;
		}

		// 2010/08/03 - MGD - On déclare le tableau en dehors du if, ca évite un bug dans le else...
		$results = array();
		if ($nb == "all") {
			while ($row = pg_fetch_assoc($result)) {
				$results[] = $row;
		        }
		}
		else {
			$results = pg_fetch_assoc($result);
		}

		// 27/11/2008 - Modif. benoit : désactivation du debug pour cette méthode. En effet, celle-ci étant la plupart du temps utilisée pour boucler sur les résultats on a, quand le debug est activé, une ligne de debug par résultat ce qui peut être embetant lorsqu'il y a beaucoup de résultats

		/*// debug
		if ($this->debug) {
			$_ = debug_backtrace();
			$f = null;
			while ( $d = array_pop($_) ) {
				if (strtolower($d['function']) == 'sql') break;
				$f = $d;
			}
			echo "<div class='sql_debug'>";
			echo "[function : <code>{$f['class']}::{$f['function']}()</code> - line <code>{$f['line']}</code>]";
			if ( !$results ) echo '<span style="color:red">'.pg_last_error().'</span><br />';
			echo "</div>";
		}*/
		return $results;
	}

	// maj 20/05/2009 MPR : ajout du paramètre display_erros qui permet de masquer l'affichage des erreurs lors de l'exécution d'une requête
	// maj 13/12/2011 SPD1 : ajout du parametre $info, pour obtenir des info sur la requete (nombre de resultats, temps d'execution)
	/**
	* Execution d'une requête et retour du tableau de données obtenu.
	*
	* @param string $sql la requete à executer
	* @param boolean $display_errors Affichage des erreurs SQL rencontrées
	* @param objet $info facultatif, permet d'obtenir des info sur la requete (temps d'execution, nombre de resultats)
	* @return array tableau à deux dimentions contenant tous les enregistrements retournés
	*/
	public function getAll($query, $display_errors=1, &$info = null)
	{
		// Set default values
		if ($info) {
			$info->nbResults = 0;
			$info->executionTime = 0;
		}
		
		$result = $this->executeQuery($query, $display_errors, $info);
		return $this->getQueryResults($result, "all", $info);
	}


	/**
	 * 21/01/2011 MMT DE Xpert
	 * Execute une requete et renvoie la liste de valeure d'une seule colonne passée en parametre
	 * @param string $query la requete à executer
	 * @param string $column le nom de la colonne a recuperer les valeurs
	 * @param boolean $display_errors Affichage des erreurs SQL rencontrées
	 * @return array tableau a une dimention contenant les valeurs de la colonne dans l'ordre de retour le la requette
	 *               si la colonne n'existe le tableau retourné est vide
	 */
	public function getColumnValues($query, $column,$display_errors=1){
		$ret = array();

		$result = $this->getAll($query,$display_errors);
		if(count($result) > 0){
			// test la presence de la colonne sur la premiere valeure
			if(array_key_exists($column, $result[0])){
				foreach ($result as $row) {
					$ret[] = $row[$column];
				}
			}
		}
		return $ret;
	}


	/**
	* Execution d'une requête et retour de la première ligne des données obtenues
	*
	* @param string $sql la requete à executer
	* @return array tableau associatif à une dimention contenant la première ligne retournée
	*/
	public function getRow($query)
	{
		$result = $this->executeQuery($query);
		$data = $this->getQueryResults($result);
                
        // 21/11/2011 BBX : bz24764, correction des messages "Notice" PHP
        // 22/12/2011 OJT : retour de null au lieu de false
		return isset($data[0]) ? $data[0] : null;
	}

	/**
	* Execution d'une requête et retour du premier champ de la première ligne des données obtenues
	*
	* @param string $sql la requete à executer
	* @return string première valeur de la première ligne retournée
	*/
	public function getOne($query)
	{
		$result = $this->executeQuery($query);
		$data = $this->getQueryResults($result,0);
		if (!$data) return false;
		$row = $data[0];
		foreach ($row as $field)
			return $field;
	}

	/**
	* Execute simplement une query. Cette fonction est un alias de executeQuery() et n'est là que pour rendre le code PHP
	* entièrement compatible avec adodb.
	*
	* @param string $sql la requete à executer
	* @return object reference au résultat de la requete executée
	*/
	public function execute($query) {
		return $this->executeQuery($query);
	}

	/**
	* Pale copie de la fonction AutoExecute d'adodb : http://phplens.com/adodb/reference.functions.getupdatesql.html#autoexecute
	* Cette fonction compose elle même la requête SQL pour faire un INSERT ou un UPDATE
	*
	* @param string $table est le nom de la table dans laquelle on fait l'insert ou l'update
	* @param array $arrFields est le tableau associatif des valeurs à ajouter / modifier. Les valeurs des clés sont égales aux noms des colonnes de la table.
	* @param string $mode est égal à "UPDATE" ou "INSERT" selon l'action à effectuer
	**/
	public function AutoExecute($table, $arrFields, $mode, $where=false)
	{
		if ($mode == 'INSERT') {
			$fields	= '';
			$values	= '';
			foreach ($arrFields as $key => $val) {
				$fields	.= ", $key";
				if (($val === 'null') or ($val === null) or ($val === ''))		$values	.= ", null";
				else											$values	.= ", '$val'";
			}
			$fields	= ltrim($fields,',');
			$values	= ltrim($values,',');

			$query = " --- AutoExecute $mode $table
				insert into $table ($fields) values ($values)";
			$this->execute($query);
		}

		if ($mode == 'UPDATE') {
			// TODO
		}
	}

	/**
	* Retourne l'instance de connexion à la base
	*
	* @return obj : instance de connexion
	*/
	public function getCnx()
	{
		return $this->_cnx;
	}

    /**
    * Retourne le nom de la base de données
    * @return String
    */
    public function getDbName()
    {
        return $this->_db_db;
    }

    /**
    * Retourne le login de la base de données
    * @return String
    */
    public function getDbLogin()
    {
        return $this->_db_login;
    }
    
    /**
    * Retourne le login de la base de données
    * @return String
    */
    public function getDbPassword()
    {
        return $this->_db_pwd;
    }

	/**
	* Transforme la query pour qu'elle soit plus jolie
	*
	* @param string $query a rendre "jolie"
	* @return string la requête rendue plus jolie
	*/
	public function pretty($query) {
		$lines = explode("\n",$query);
		foreach ($lines as &$line) {
			if (strpos($line,'---')) {
					$line = str_replace('---','-<span style="color:#900;">-<strong>-',$line).'</strong></span>';
			} else {
				$line = str_replace('--', '-<span style="color:#666">-',$line).'</span>';
			}
		}
		$query = implode("<br/>",str_replace("\t",'&nbsp;&nbsp;&nbsp;&nbsp;',$lines));
		return $query;
	}


	/**
	 * Affiche sous forme d'un tableau toutes les query executées jusqu'à maintenant
	 *
	 * @param void
	 * @return string le tableau HTML présentant les queries
	 */
	public function displayQueries()
	{
                if(!$this->_debug) {
                    echo "<div>Debug must be enabled to trace queries</div>";
                    return;
                }
                
		$html = "<table cellspacing='1' cellpadding='2' border='0' class='query_list'>
		 	<tr><th>#</th><th>Query</th><th>Rows</th><th>Time <small>(ms)</small></th></tr>";

		// $i = 0;
		$total_chrono = 0;
		while ($q = array_shift($this->_query_list)) {
			$this->_nb_query_displayed++;
			if ($q['error']) {
				$html .= "
					<tr>
					<td colspan='4' style='background:#333;color:white;'>{$q['error']}</td>
					</tr>
				";
			}
			$html .= "
				<tr align='right'>
					<td>$this->_nb_query_displayed</td>
					<td align='left'>".$this->pretty($q['query'])."</td>
					<td>{$q['nb_rows']}</td>
					<td>{$q['time']}</td>
				</tr>";
			$total_chrono += $q['time'];
		}

		$html .= "<tr class='last' align='right'><td>&nbsp;</td><td>total nb of queries: $this->_nb_query_displayed</td><td></td><td>$total_chrono</td></tr>";

		$html .= '</table>';
		return $html;
	}

	// 20/11/2008 - Modif. benoit : ajout du setter permettant l'activation / désactivation du débugage

	/**
	 * Activation / Désactivation du débugage
	 *
	 * @param $debug boolean activer / désactiver le débugage
	 * @return void
	 */

	public function setDebug($debug)
	{
		$this->debug = $debug;
	}

	// 20/11/2008 - Modif. benoit : ajout de la méthode ci-dessous permettant de renvoyer le timestamp courant en secondes

	/**
	 * Renvoie la valeur du timestamp exprimée en secondes
	 *
	 * @param void
	 * @return float valeur du timestamp en secondes
	 */

	private function microtime_float()
	{
	   list($usec, $sec) = explode(" ", microtime());
	   return ((float)$usec + (float)$sec);
	}

	/**
	 *  Lit le dernier message d'erreur sur la connexion
	 *
	 * @param void
	 * @ Return : Une chaîne de caractères contenant le dernier message d'erreur sur la connexion connection  ou FALSE en cas d'erreur.
	 */
	public function getLastError()
	{
		return pg_last_error($this->_cnx);
	}

	/**
	 *  Retourne le nombre de lignes affectées par la dernière requête éxécutée
	 *
	 * @param void
	 * @ Return int : nombre de lignes affectées
	 */
	public function getAffectedRows()
	{
		return pg_affected_rows($this->_lastResult);
	}

	/**
	 *  Retourne le pg_last_oid()
	 *
	 * @param void
	 * @ Return int : nombre de lignes affectées
	 */
	public function getPgLastOid()
	{
		return pg_last_oid($this->_lastResult);
	}

	/**
	 * Ferme la connexion
	 *
	 * @param void
	 */
	public function close()
	{
		// On supprime la référence à la connexion
		self::dropConnection($this->_idConnection);
		// On ferme la connexion
		pg_close($this->_cnx);
                // 31/01/2011 BBX
                // On détruit $this->_cnx
                // BZ 20450
                $this->_cnx = null;
		// On définit la connexion comme off
		$this->_connection_state = false;
	}

	/**
	 *  Retourne le pg_num_rows()
	 *
	 * @param void
	 * @ Return int : nombre de lignes de résultat
	 */
	public function getNumRows()
	{
		return pg_num_rows($this->_lastResult);
	}

	/**
	 *  Récupère le contenu d'une table
	 *
	 * @param string Nom de la tables
     * @param array Liste des colonnes à exclure
	 * @return array Tableau représentant la table dumpée
	 */
	public function getTable($table, $excludedCols = array() )
	{
        // 17/02/2011 BBX : BZ19714 On utilise plus pg_copy_to qui plante.
        // On utilise désormais un simple SELECT *
        $data  = array();
        $query  = "SELECT * FROM $table";
        $result = $this->execute( $query );
        while( $row = $this->getQueryResults( $result, 1 ) )
        {
            $line = array();
            foreach( $row as $key=>$value )
            {
                if( !in_array( $key, $excludedCols ) )
                {
                    if( trim( $value ) == '' )  $line[] = "\\N";
                    else $line[] = $value;
            }
        }
            $data[] = implode( "\t", $line )."\n";
        }
        return $data;
	}

	/**
	 *  Rempli une table à l'aide d'un tableau
	 *
	 * @param string	table name
	 * @param array	rows to insert
         * @param string colonnes
	 * @ Return bool
	 */
	public function setTable($table,$rows,$columns = '')
	{
        // 17/02/2011 BBX
        // On utilise plus pg_copy_from qui plante.
        // On utilise un COPY normal
        // BZ 19714
        $execCtl = true;
        // Préparation du COPY
        $this->execute("BEGIN");
        $query = "COPY $table ";
        if(!empty($columns)) $query .= "($columns) ";
        $query .= "FROM STDIN;";
        $execCtl = $execCtl && (!$this->execute($query) ? false : true);
        // COPY des lignes
        foreach($rows as $line) {
            // 23/03/2011 BBX
            // Il arrive que le retour chariot na passe pas
            // ou soit absent. Dans ce cas on l'ajoute automatiquement.
            // BZ 21537
            if(substr($line,-1) != "\n") $line .= "\n";
            $execCtl = $execCtl && (!pg_put_line($this->_cnx, $line) ? false : true);
        }
        // Fin de COPY
        $execCtl = $execCtl && (!pg_put_line($this->_cnx, "\\.\n") ? false : true);
        $execCtl = $execCtl && (!pg_end_copy($this->_cnx) ? false : true);
        $this->execute($execCtl ? 'COMMIT' : 'ROLLBACK');
        // Retour booléen
		return $execCtl;
	}

    // 01/08/2011 NSE bz 22558 : ajout des méthodes CopyPrepare(), CopyLine() et CopyEnd()
	/**
     * 25/05/2011 - MPR : Ajout de la méthode
     * Fonction qui initialise un Copy ligne par ligne
     * @param string $table : Nom de la table cible
     * @param array $cols : Tableau contenant les colonnes à copier
     */
    public function copyPrepare($table, $cols = array())
    {
        $query = "COPY $table ";
        if(!empty($cols)) $query .= "(".implode(",",$cols).")";
        $query .= " FROM STDIN WITH DELIMITER '\t' NULL ''";
        $result = pg_query($this->_cnx, $query );

        if( !$result )
        {
            echo pg_last_error($this->_cnx);
        }

    } // End function copyPrepare()

    /**
     * 25/05/2011 - MPR : Ajout de la méthode
     * Fonction qui copie une ligne dans une table
     * Doit être appeler après un copyPrepare()
     * @param string $row
     */
    public function copyLine( $row )
    {

        $return = pg_put_line($this->_cnx, $row."\n");
        if( !$return )
        {
            echo pg_last_error($this->_cnx);
        }
    } // End function copyLine()

    /**
     * 25/05/2011 - MPR : Ajout de la méthode
     * Fonction qui finalise le copy ligne par ligne
     */
    public function copyEnd()
    {
        $this->copyLine("\\.");
        pg_end_copy($this->_cnx);
    } // End function copyEnd()

	/**
	*  Liste les tables
	*
	* @return array		la liste des tables
	*/
	public function listTables()
	{
		return pg_list_tables();
	}

	/**
	 * Retourne les index d'une table sous forme de tableau. Si auncun index un tableau vide est retourné
	 *
	 * Format du tableau retourné :
	 *	array(
	 *		nom_index => requete_sql_pour_créer_index,
	 *		...
	 *	)
	 *
	 * @param string $table nom de la table
	 * @return array
	 */
	public function getIndexes( $table )
	{
		$sql = "SELECT indexname, indexdef from pg_indexes where tablename='$table'";

		$result = $this->execute($sql);

		$indexes = array();
		if ( $this->getNumRows() > 0)
		{
			while( $row = $this->getQueryResults($result, 1) )
			{
				$indexes[$row['indexname']] = $row['indexdef'];
			}
		}

		return $indexes;
	} // End function getIndexes

	/**
	 * Retourne le type d'un champ d'une table
	 *
	 * @param string $table nom de la table
	 * @param string $field nom du champ
	 * @return string
	 */
	public function getFieldType($table,$field)
	{
		$query = "SELECT {$field} FROM {$table} LIMIT 1";
		$result = $this->execute($query);
		return pg_field_type($result, 0);
	}

	/**
	 * Retourne l'espace disque utilisé par la base de données
	 *
	 * @author GHX
	 * @since CB 5.0.1.00
	 * @since CB 5.0.1.00
	 * @param boolean $pretty affichage humain du résultat (default false)
	 * @return mixed si $pretty vaut FALSE la taille retournvé est en octets (bigint) si vaut TRUE la taille est retourné sous forme de chaine de caractère
	 */
	public function getSize ( $pretty = false )
	{
		if ( $pretty )
		{
			return $this->getOne("SELECT pg_size_pretty(pg_database_size('".pg_dbname($this->_cnx)."'))");
		}
		else
		{
			return $this->getOne("SELECT pg_database_size('".pg_dbname($this->_cnx)."')");
		}
	} // End function getSize

	/**
	 * Retourne la liste des champs d'une table dans l'ordre
	 *
	 * @author GHX
	 * @param string $table nom du table
	 * @return array
	 */
	public function getColumns ( $table )
	{
		$querySelectColumn = "
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

		$resultSelectColumn = $this->execute($querySelectColumn);

		$result = array();
		if ( $this->getNumRows() > 0 )
		{
			while ( $row = $this->getQueryResults($resultSelectColumn, 1) )
			{
				$result[$row['order']] = $row['field'];
			}
		}

		return $result;
	} // End function getColumns
    
    /**
         * Determine si une colonne existe dans une table
         * 29/09/2011 BBX : utilisation de la méthode CB 5.1.4.X car la
         * méthode précédent était différente et buggée. BZ 23976
         *
         * @since 5.0.5.06
         * @param string $table Nom de la table
         * @param string $col Nom de la colonne
         * @return boolean
         */
        public function columnExists( $table, $col )
        {
            // 29/09/2011 BBX : il faut rendre la requête insensible à la casse
				// 05/04/2012 MMT : Bz 26995 utilisation de 'lower' au lieu de ilike pour eviter la prise en compte
				// des charactère de patern (% ou _)
            // BZ 23976
            $query = "SELECT count(attname)
            FROM pg_attribute a, pg_class c
            WHERE a.attrelid = c.oid
            AND c.relname = '$table'
            AND a.attisdropped = false
            AND attnum >= 0
            AND lower(attname) = lower('$col')";
            return (int)$this->getOne($query);
        }

    /**
     * Vérifie si la table existe dans la base courante
     *
     * @param String table table dont vérifier l'existence
     * @return Boolean True si la table existe dans la base, false sinon
     *
     * 13/05/2011 NSE DE Topology Characters replacement : création de la méthode
     * 13/10/2011 BBX BZ 20636 : optim de la méthode
     */
    public function doesTableExist($table){
        $query = "SELECT COUNT(oid) FROM pg_class WHERE relname = '$table'";
        $res = (int)$this->getOne($query);
        return ($res > 0);
    }

	/**
	 * Retourne un tableau avec toutes les types de champs qui peuvent contenir une chaine de caractères
	 *
	 * @author GHX
	 * @return array
	 */
	public static function getPGStringTypes ()
	{
		return  array('character varying','varchar','character','char','text');
	} // End function getPGStringTypes

        /**
         * Test connection to a product
         * Returns false if connection fails
         * Added for BZ 18510
         * @param integer $productId
         * @return boolean
         */
        public static function testConnection($conString)
        {
            // Testing connexion status
            if(!@pg_connect($conString,PGSQL_CONNECT_FORCE_NEW)) {
                return false;
	    }
            return true;
        }

    /**
     * Reconstruit une table (suppression réelle des colonnes supprimées)
     *
     * @param  string $table
     * @return boolean
     */
    public function rebuildTable($table)
    {
        // Récupération des index
        $indexes = $this->getIndexes( $table );
        // Début de la transaction
        $execCtrl = true;
        $this->execute("BEGIN");
        $execCtrl = $execCtrl && (!$this->execute("CREATE TABLE {$table}_tmp (LIKE $table INCLUDING CONSTRAINTS INCLUDING DEFAULTS)") ? false : true);
        $execCtrl = $execCtrl && (!$this->execute("INSERT INTO {$table}_tmp SELECT * FROM $table") ? false : true);
        $execCtrl = $execCtrl && (!$this->execute("DROP TABLE $table") ? false : true);
        $execCtrl = $execCtrl && (!$this->execute("ALTER TABLE {$table}_tmp rename to {$table}") ? false : true);

        // Recréation des indexes
        foreach($indexes as $indexName => $indexRef) {
            $execCtrl = $execCtrl && (!$this->execute($indexRef) ? false : true);
        }

        // Fin de transaction
        if($execCtrl) $this->execute("COMMIT");
        else $this->execute("ROLLBACK");
        return $execCtrl;
    }

    /**
     * Permet de récréer une table de données avec ses partitions
     * Créé dans le cadre de la correction du bug 22721
     * 27/06/2011 BBX
     * @param string $table
     * @return boolean 
     */
    public function rebuildTableWithPartitions($table)
    {
        // Début de la transaction
        $execCtrl = true;

        // Etape 1 : Récupération de la time aggregation
        $tableInfos = explode('_', $table);
        $ta = isset($tableInfos[7]) ? $tableInfos[7] : '';
        if( isset( $tableInfos[8] ) && ( $tableInfos[8] == 'bh' ) )
            $ta .= '_bh';
        $timeAgregations = array("hour","day","day_bh","week","week_bh","month","month_bh");
        if( in_array( $tableInfos[6],$timeAgregations ) ) {
            $ta = $tableInfos[6];
            if( isset( $tableInfos[7] ) && ( $tableInfos[7] == 'bh') )
            $ta .= '_bh';
        }

        // Etape 2 : Récupération des index
        $indexes = $this->getIndexes( $table );

        // Etape 3 : copie des données dans une table temporaire
        $this->execute("BEGIN");
        $execCtrl = $execCtrl && (!$this->execute("CREATE UNLOGGED TABLE {$table}_tmp (LIKE $table INCLUDING CONSTRAINTS INCLUDING DEFAULTS)") ? false : true);
        $execCtrl = $execCtrl && (!$this->execute("INSERT INTO {$table}_tmp SELECT * FROM $table") ? false : true);
        
        // Etape 4 : suppression de la table de données et de ses partitions
        $execCtrl = $execCtrl && (!$this->execute("DROP TABLE $table CASCADE") ? false : true);

        // Etape 5 : recréation d'une table mère saine
        $execCtrl = $execCtrl && (!$this->execute("CREATE TABLE {$table} (LIKE {$table}_tmp INCLUDING CONSTRAINTS INCLUDING DEFAULTS)") ? false : true);
        $execCtrl = $execCtrl && (!$this->execute("CREATE TRIGGER {$table}_trig_lock BEFORE INSERT OR UPDATE OR DELETE ON {$table} FOR EACH ROW EXECUTE PROCEDURE lock_data_tables();") ? false : true);
        // Recréation des indexes
        foreach($indexes as $indexName => $indexRef) {
            $execCtrl = $execCtrl && (!$this->execute($indexRef) ? false : true);
        }

        // Fin de transaction
        if($execCtrl) $this->execute("COMMIT");
        else {
            $this->execute("ROLLBACK");
            return false;
        }

        // Etape 6 : pour chaque donnée à réinsérer, on créé la partition correspondante et on réinsère
        $query = "SELECT DISTINCT {$ta} AS time_agg FROM {$table}_tmp";
        $result = $this->execute($query);
        while($row = $this->getQueryResults($result,1))
        {
            // Création de la partition
            $partition = new Partition($table, $row['time_agg'], $this);
            $execCtrl = $execCtrl && $partition->create();
            
            // Remplissage de la partition
            $this->execute("INSERT INTO ".$partition->getName()." SELECT * FROM {$table}_tmp WHERE {$ta} = ".$row['time_agg']);

            // Mise à jour des stats
            $this->execute("ANALYZE ".$partition->getName());
        }

        // Etape 7 : destruction de la table temporaire
        $this->execute("DROP TABLE {$table}_tmp");

        // Retour booléen
        return $execCtrl;
    }

    /**
    * Returns Postgresql server version
    * @return string
    */
    public function getVersion()
    {
        $version = pg_version($this->_cnx);
        return (float)$version['server'];
    }

    /**
     * Returns true if database is partitioned
     * @return boolean
     */
    public function isPartitioned()
    {
        // Number of lines
        $nbLines = 0;

        // Checking heritage
        $queryHerit = "SELECT inhrelid FROM pg_inherits LIMIT 1";
        $this->execute($queryHerit);
        $nbLines += $this->getNumRows();

        // Checking trigger
        $queryTrig = "SELECT tgrelid FROM pg_trigger
        WHERE tgname LIKE '%_trig_lock' LIMIT 1";
        $this->execute($queryTrig);
        $nbLines += $this->getNumRows();

        // Returning result
        return ($nbLines >= 1);
	 }

    /**
     * Returns some important PG parameters
     * @return array
     */
	// 21/12/2011 ACS BZ 25191 Warnings displayed in partitionning menu
    public function getConfigInformation()
    {
        $config = array();
        $parameters = array('shared_buffers',
            'work_mem',
            'maintenance_work_mem',
//            'effective_io_concurrency',
            'checkpoint_segments',
//            'checkpoint_completion_target',
            'random_page_cost',
            'effective_cache_size',
            'default_statistics_target',
            'constraint_exclusion',
            'max_locks_per_transaction',
            'default_with_oids',
            'autovacuum');
        foreach($parameters as $param) {
            $config[$param] = $this->getOne("SHOW $param");
        }
        return $config;
    }

    /**
     * Returns all existing databases
     * @return array
     */
    public function getDatabases()
    {
        $allDb = array();
        $query = "SELECT datname FROM pg_database WHERE datname NOT IN ('template1','template0','postgres')";
        $result = $this->execute($query);
        while($row = $this->getQueryResults($result,1)) {
            $allDb[] = $row['datname'];
        }
        return $allDb;
    }

    /**
     * Connects to another specified database
     * @param string $database
     */
    public function changeDatabase($database)
    {
        if(in_array($database,$this->getDatabases()))
        {
            $host = pg_host($this->_cnx);
            $port = pg_port($this->_cnx);
            $this->_cnx = pg_connect("host=$host port=$port dbname=$database user=postgres");
		}
    }

    /**
     * Resets curent connection
     */
    public function resetConnection()
    {
         return pg_connection_reset($this->getCnx());
    }
	
    /**
     * Construct the string to connect on a database
     */
    public static function getConnectionString($ipAddress, $dbName, $dbLogin, $dbPassword, $dbPort) {
            return "host={$ipAddress} port={$dbPort} dbname={$dbName} user={$dbLogin} password={$dbPassword}";
    }
        
    /**
     * 12/12/2011 BBX
     * BZ 24572
     * Dump une table
     * Attention : ne gère que la table et ses colonnes.
     * Pour un dump plus complet (avec contraintes, index...) utiliser pg_dump
     * @param type $table table name
     * @param type $nodata true not to export data
     * @param type $nostructure true not to export struture
     * @return string : code SQL de dump de la table
     */
    public function dumpTable($table, $nodata = false, $nostructure = false)
    {
        // Structure Begin
        $structure = "CREATE TABLE users\n";
        $structure .= "(";
        
        // Table columns full information
        $query = "SELECT att.*, def.*, pg_catalog.pg_get_expr(def.adbin, def.adrelid) AS defval, CASE WHEN att.attndims > 0 THEN 1 ELSE 0 END AS isarray, format_type(ty.oid,NULL) AS typname, format_type(ty.oid,att.atttypmod) AS displaytypname, tn.nspname as typnspname, et.typname as elemtypname,
          ty.typstorage AS defaultstorage, cl.relname, na.nspname, att.attstattarget, description, cs.relname AS sername, ns.nspname AS serschema,
          (SELECT count(1) FROM pg_type t2 WHERE t2.typname=ty.typname) > 1 AS isdup, indkey,
          EXISTS(SELECT 1 FROM  pg_constraint WHERE conrelid=att.attrelid AND contype='f' AND att.attnum=ANY(conkey)) As isfk
          FROM pg_attribute att
          JOIN pg_type ty ON ty.oid=atttypid
          JOIN pg_namespace tn ON tn.oid=ty.typnamespace
          JOIN pg_class cl ON cl.oid=att.attrelid
          JOIN pg_namespace na ON na.oid=cl.relnamespace
          LEFT OUTER JOIN pg_type et ON et.oid=ty.typelem
          LEFT OUTER JOIN pg_attrdef def ON adrelid=att.attrelid AND adnum=att.attnum
          LEFT OUTER JOIN pg_description des ON des.objoid=att.attrelid AND des.objsubid=att.attnum
          LEFT OUTER JOIN (pg_depend JOIN pg_class cs ON objid=cs.oid AND cs.relkind='S') ON refobjid=att.attrelid AND refobjsubid=att.attnum
          LEFT OUTER JOIN pg_namespace ns ON ns.oid=cs.relnamespace
          LEFT OUTER JOIN pg_index pi ON pi.indrelid=att.attrelid AND indisprimary
         WHERE att.attrelid = cl.oid
	   AND cl.relname = '$table'
           AND att.attnum > 0
           AND att.attisdropped IS FALSE
         ORDER BY att.attnum";
        $result = $this->execute($query);
        $numCol = 0;
        $listFields = array();
        while($row = $this->getQueryResults($result,1)) 
        {
            // Separator
            $separator = ($numCol == 0) ? "" : ",";
            // Field name
            $field = $row['attname'];
            $listFields[] = $field;
            // Field type
            $type = $row['typname'];
            // Default value
            $defaultValue = "";
            if(!empty($row['defval'])) {
                $defaultValue = " DEFAULT ".$row['defval'];
            }            
            
            // Writing column code
            $structure .= "{$separator}\n {$field} {$type}{$defaultValue}";
            $numCol++;
        }
        
        // End of table
        $structure .= "\n);\n";
        
        // Fetching Data
        $tableContents = "COPY $table (". implode(',',$listFields) .") FROM STDIN;\n";       
        $tableContents .= implode('',$this->getTable($table));
        $tableContents .= "\.\n";
        
        // Dump
        $dump = "";
        if(!$nostructure) $dump .= $structure;        
        if(!$nodata) $dump .= $tableContents;
        return $dump;
    }
}
?>