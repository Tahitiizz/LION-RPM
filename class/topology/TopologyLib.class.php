<?
/**
 * 
 *  CB 5.3.1
 * 
 * 22/05/2013 : WebService Topology
 */
/*
*	@cb41000@
*
*	14/11/2007 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.00
*
*
*	01/12/2008 GHX
*		- Renomage de tous les fichiers csv créés en fichier topo (plus facile pour supprimer les fichiers à la fin de l'upload)
*		- ajout de la fonction createArrayNaLabel();
*
*	03/12/2008 GHX
*		- modification de la fonction sql()
*
*	14/10/2009 MPR 
*		- Ajout des deux paramètres globaux => activate_capacity_planing et activate_trx_charge_in_topo
 *
 *      04/01/2011 NSE bz 19674 : mauvais getNaMaxIntoFile s'il y a des zéros dans la colonne na_max_unique de sys_definition_network_agregation
 * 21/04/2011 NSE DE Non unique Labels : corrections de commentaires
 * 16/05/2011 NSE DE Topology characters replacement : le remplacement n'est plus fait directement dans le init()
 * 12/02/2015 JLG bz 45947/20604 : if one msc is associated to a network, 
 *	all other items with unique parent (msc, smscenter...) will be associated with this network
*/
?>
<?
/**
 *	Classe TopologyLib 	- Classe Mère du module de topologie.
 *					- On conserve les fonctions communes aux classes de Topology ainsi que les attributs en commun
 *
  * @version 4.1.0.00
 * @package Topology
 * @author MPR
 * @since CB4.1.0.0
 *
 *	maj MPR : Réécriture du fichier 
 */
class TopologyLib
{
	// -------------------------------------------- Attributs--------------------------------------------//

	/**
	 * Délimiteur présent dans le fichier chargé (; ou ,)
	 *
	 * @var char
	 */
	public static $delimiter;

	/**
	* Nombre d'éléments 3ème axe autorisé à être inséré 
	*/ 
	public static $nb_elems_axe3_limited;
	/**
	 * Chemin complet du fichier chargé
	 *
	 * @var string
	 */
	public static $file;

	/**
	 * Données du fichier chargé sans le header
	 *
	 * @var string
	 */
	public static $file_tmp;
	
	/**
	 * Paramètre global activate trx charge in topo permettant de savoir si on gère les deux params dans la topologie (eorp_trx et eorp_charge de edw_object_ref_parameters)
	 *
	 * @var string
	 */
	public static $activate_trx_charge_in_topo;
	
	/**
	 * Paramètre global activate capacity planing permettant de savoir si on gère les deux params dans la topologie (eorp_trx et eorp_charge de edw_object_ref_parameters)
	 *
	 * @var string
	 */
	public static $activate_capacity_planing;

	/**
	 * Fichier chargé converti en fonction des colonnes de la base (sai;cell_name;rnc_id;node_name;network_name => sai;sai_label;rnc;rnc_label;network;network_label
	 *
	 * @var string
	 */
	public static $file_tmp_db;

	/**
	 * Header du fichier converti (ex : sai;sai_label;rnc;rnc_label;network;network_label)
	 *
	 * @var string
	 */
	public static $header_db;

	/**
	 * Niveau minimum de la famille principale
	 *
	 * @var string
	 */
	public static $na_min;

	/**
	 * Répertoire racine de l'application
	 *
	 * @var string
	 */
	public static $rep_niveau0;

	/**
	 * Mode d'upload Topology (manuel ou auto)
	 *
	 * @var string
	 */
	public static $mode;

	/**
	 * Nom du module produit concerné
	 *
	 * @var string
	 */
	public static $product;
	
	/**
	 * Id du produit concerné
	 *
	 * @var integer
	 */
	public static $id_prod;

	/**
	 * Mode d'upload Topology (manuel ou auto)
	 *
	 * @var string
	 */
	public static $changes;

	/**
	 * Connexion à la base de données
	 *
	 * @var string
	 */
	public static $database_connection;

	/**
	 * Identifiant de l'utilisateur
	 *
	 * @var integer
	 */
	public static $id_user;

	/**
	 * Identifiant de l'utilisateur
	 *
	 * @var integer
	 */
	public static $debug;

	/**
	 * Valeur de l'axe réseau correspondant au fichier
	 *
	 * @version CB 4.1.0.00
	 * @var int
	 */
	protected static $axe;
	
	// Tables de référence

	/**
	 * Table de référence contenant tous les éléments réseau ou 3ème axe
	 *
	 * @var string
	 */
	public static $table_ref = 'edw_object_ref';

	/**
	 * Table de référence contenant toutes les relations entre les éléments réseau ou 3ème axe
	 *
	 * @var string
	 */
	public static $table_arc_ref = 'edw_object_arc_ref';

	/**
	 * Table de référence contenant tous les paramètres (coordonnées géographique+ delete_counters) des éléments réseau ou 3ème axe
	 *
	 * @var string
	 */
	public static $table_params_ref  = 'edw_object_ref_parameters';

	/**
	 * Type de mise à jour de l'upload topology ( 1 -> Upload Topology en auto ou manuel - 2 -> Appel parser )
	 *
	 * @var string
	 */
	public static $type_maj;

	/**
	 * Type de mise à jour de l'upload topology ( 1 -> Upload Topology en auto ou manuel - 2 -> Appel parser )
	 *
	 * @var string
	 */
	public static $topology;

	/**
	 * Type de mise à jour de l'upload topology ( 1 -> Upload Topology en auto ou manuel - 2 -> Appel parser )
	 *
	 * @var string
	 */
	public static $errors;
	
	/**
	 * Messages d'avertissement
	 *
	 * @var string
	 */
	public static $warnings;


	/**
	* Première ligne du fichier qui identifie les na ou paramètres à traiter ( sai, cell_name,rnc_id,node_name,network_name)
	*/
	public static $header;


	/**
	* Jour de traitement de la topologie
	*/
	public static $day;

	/**
	* Jour de traitement de la topologie
	*/
	public static $queries;

	/**
	* Liste des niveaux d'agrégation du fichier
	*/
	public static $na;

	/**
	 * Nom du niveau d'aggrégation réseau minimum dans le fichier
	 *
	 * @version CB4.1.0.00
	 * @var string
	 */
	protected static $naMinIntoFile;
	
	/**
	 * Tableau contenant les labels des niveaux d'aggrégation
	 *
	 * @since CB4.1.0.00
	 * @var array
	 */
	protected static $naLabel;
	
	/**
	 * famille concernée
	 *
	 * @since CB4.1.0.00
	 * @var array
	 */
	public static $_family="";
	
        //CB 5.3.1 WebService Topology
        public static $filenameInArchive="";
	// -------------------------------------------- Méthodes--------------------------------------------//

	/**
	 * Constructeur
	 */
	public function __construct()
	{
		self::$debug = (int) get_sys_debug('upload_topology');
		
		$main_family = get_main_family();
		// Niveau minimum de la famille principale
		self::$na_min = get_network_aggregation_min_from_family($main_family);
		
		// maj MPR 17:02 14/10/2009
		// On récupère les valeurs des paramètres activate_capacity_planing et activate_trx_charge_in_topo
		self::$activate_capacity_planing 	= get_sys_global_parameters('activate_capacity_planing');
		self::$activate_trx_charge_in_topo 	= get_sys_global_parameters('activate_trx_charge_in_topo');
		
	} // End function __construct

	/**
	 * Fonction qui permet d'ajouter les changements effectués
	 *
	 * @param array $chgt : changement effectués
	 */
	protected function set_changes($chgt)
	{
		self::$changes[] = $chgt;
	} // End function set_changes

    //CB 5.3.1 WebService Topology
    protected function setFilenameInArchive($name)
	{
		self::$filenameInArchive = $name;
	}
        
	/**
	 * Fonction qui récupère dans self::$header le header du fichier
	 */
	public function initHeader()
	{
		self::$header = array();
		$cmd = "awk 'NR == 1 {print $0}' ". self::$rep_niveau0 .'upload/'. self::$file;
		// $cmd = "awk 'NR == 1 {print $0}' /home/cb50000_alcatel30002_base_multiprod_liv/upload/". self::$file;
		$header = $this->cmd($cmd, true);
		$header =  $header[0];
		self::$header = explode(self::$delimiter, $header);

		// $this->demon(self::$header,"header");
	} // End function initHeader

	/**
	* Fonction qui récupère le niveau d'agrégation réseau ou 3ème maximum
	* @return string $na_max : niveau d'agrégation maximum
         *
         *  04/01/2011 NSE bz 19674 : retourne un mauvais NA_MAX_UNIQUE s'il y a des zéros dans la colonne na_max_unique de sys_definition_network_agregation

	*/
	public function getNaMaxIntoFile()
	{
            // 04/01/2011 NSE bz 19674 : IS NOT NULL ne prend pas en compte le cas où la valeur est 0. Ajout de la condition 'AND na_max_unique!=0'
		$query = "	SELECT DISTINCT agregation
					FROM sys_definition_network_agregation
					WHERE (na_max_unique IS NOT null AND na_max_unique!=0)
                                        AND agregation IN ('".implode("','",self::$na)."') LIMIT 1";

		$res = $this->sql($query);

		if (pg_num_rows($res) > 0)
		{
			while( $row = pg_fetch_array($res) )
			{
				$na_max = $row['agregation'];
			}

			return $na_max;
		}
		else
		{
			return false;
		}
	} // End function getNaMaxIntoFile

	/**
	* Fonction qui récupère le niveau d'agrégation réseau minimum  de la famille principale
	*
	* @return string $_na : niveau d'agrégation minimum de la famille principale
	*/
	public function getNaMinIntoFile()
	{
		return self::$naMinIntoFile;
	} // End function getNaMinIntoFile

	/**
	 * Spécifie le niveau minimum dans le fichier d'upload
	 * La valeur est déterminé lors du check sur le fichier
	 *
	 *	-27/11/2008 GHX 
	 *		ajout de la fonction
	 *
	 * @version CB 4.1.0.00
	 * @param string $na nom du niveau d'aggrégation
	 */
	protected function setNaMinIntoFile ( $na )
	{
		self::$naMinIntoFile = $na;
	} // End function setNaMinIntoFile
	
	/**
	 * Spécifie l'axe réseau sur lequel le fichier est chargé
	 * La valeur est déterminé lors du check sur le fichier
	 *
	 *	-27/11/2008 GHX 
	 *		ajout de la fonction
	 *
	 * @version CB 4.1.0.00
	 * @param int $axe axe sur lequel on charge le fichier valeur possible 1 ou 3
	 */
	protected function setAxe ( $axe )
	{
		self::$axe = $axe;
	} // End function setAxe
	
	/**
	 * Retourne l'axe réseau sur lequel le fichier est chargé
	 * La valeur est déterminé lors du check sur le fichier
	 *
	 *	-27/11/2008 GHX 
	 *		ajout de la fonction
	 *
	 * @version CB 4.1.0.00
	 * @return int
	 */
	protected function getAxe ()
	{
		return self::$axe;
	} // End function getAxe 
	
	/**
	 * Fonction qui retourne les changements effectués lors du chargement de la topologie
	 *
	 * @return array $changes : changement effectués
	 */
	public function getChanges()
	{
		return self::$changes;

	} // End function get_changes()
        
        //CB 5.3.1 WebService Topology
        public function getFilenameInArchive()
	{
		return self::$filenameInArchive;

	}
	/**
	 * Fonction qui permet d'ajouter les changements effectués
	 *
	 * @param array $chgt : changement effectués
	 */
	protected function setErrors($error)
	{
		self::$errors[] = $error;
	} // End function set_changes()

	/**
	 * Fonction qui initialise le type d'upload topology  ( 1 -> Upload Topology en auto ou manuel - 2 -> Appel parser )
	 *
	  * @param string $type_maj : type d'upload topology  ( 1 -> Upload Topology en auto ou manuel - 2 -> Appel parser )
	 */
	public function setTypeMaj($type_maj)
	{
		self::$type_maj = $type_maj;
	} // End function set_id_user

	/**
	 * Fonction qui initialise l'id du user
	 *
	  * @param string $id_user : id du user
	 */
	public function setIdUser($id_user)
	{
		self::$id_user = $id_user;
	} // End function set_id_user

	/**
	 * Fonction qui initialise le module du produit ('gsm', 'iu'...)
	 *
	  * @param string $prod : nom du module produit
	 */
	public function setProduct($prod)
	{
		self::$product = $prod;
	} // End function set_id_user

	/**
	 * Fonction qui initialise le chemin complet + nom du fichier chargé
	 *
	 * @param string $file : fichier chargé ors d'un upload topology
	 */
	public function setFile($file)
	{
		self::$file = $file;
        // 16/05/2011 NSE DE Topology characters replacement : le remplacement des caractères spéciaux n'est plus fait à cet endroit
	} // End function set_file

	/**
	 * Fonction qui initialise le chemin complet + nom du fichier chargé
	 *
	 * @param string $file : fichier chargé ors d'un upload topology
	 */
	public function setFileTmpDb()
	{
		$filename = date('Ymd_His');
		$file = pathinfo(self::$file);
		self::$file_tmp_db = self::$rep_niveau0 .'upload/'.$file['filename'].'_'.$filename.'_tmp_db.topo';
		$cmd = "cp -p ".self::$rep_niveau0 ."upload/".self::$file." ".self::$file_tmp_db;
		$this->cmd($cmd);
	} // End function setFileTmpDb

	/**
	 * Fonction qui initialise le chemin complet + nom du fichier chargé
	 *
	 * @param string $file : fichier chargé ors d'un upload topology
	 */
	public function setFileRef($file_ref)
	{
		self::$file_ref = $file_ref;
	} // End function set_file

	/**
	 * Fonction qui initialise le chemin complet + nom du fichier chargé
	 */
	public function setFileTmp()
	{
		$filename = date('Ymd_His');
		$file = pathinfo(self::$file);
		self::$file_tmp = self::$rep_niveau0 .'upload/'.$file['filename'].'_'.$filename.'_tmp.topo';

		$this->demon(self::$file_tmp,"file_tmp");

		$this->cmd("touch ".self::$file_tmp);
		$this->cmd("chmod 777 ".self::$file_tmp);
		// $this->cmd("chown astellia ".self::$file_tmp);

		$cmd = "awk 'NR > 1 {print $0\n}' ". self::$rep_niveau0 ."upload/". self::$file ." > ". self::$file_tmp;
		$this->cmd($cmd);

		$cmd = "dos2unix ".self::$file_tmp;
		$this->cmd($cmd);
	} // End function set_file_tmp

	/**
	 * Fonction qui initialise le mode d'upload topology (manuel ou auto)
	 *
	 * @param string $mode :  mode d'upload topology (manuel ou auto)
	 */
	public function setMode($mode)
	{
		self::$mode = $mode;
	} // End function set_mode

	public function unsetQueries(){
	
		self::$queries = array();
		self::$errors = array();
		self::$warnings = array();
	}
	/**
	 * Fonction qui initialise les requêtes SQL qui metteront à jour la topologie
	 *
	 * @acces public
	 * @param string $query :  requête SQL
	 */
	public function setQueries($query)
	{
		self::$queries[] = $query;
	} // End function setQueries

	public function limitMax3rdAxis(){
	
		$query = "SELECT agregation FROM sys_definition_network_agregation 
					WHERE agregation = '".self::$naMinIntoFile."' AND axe = 3 AND limit_3rd_axis = 1";
		
		$result = $this->sql($query);

		// Si on a un résultat c'est qu'il ne peut y avoir qu'une SEULE valeur pour ce niveau d'aggrégation
		if ( @pg_num_rows($result) > 0 )
		{
			return true;
		}else{
			return false;
		}
	}
	/**
	 * Fonction qui initialise le délimiteur présent dans le fichier chargé
	 *
	 * @param string $delimiter :  délimiteur présent dans le fichier chargé
	 */
	public function setDelimiter($delimiter)
	{
		self::$delimiter = $delimiter;
	} // End function set_delimiter

	/**
	 * Fonction qui retourne le délimiteur du fichier
	 */
	public function getDelimiter()
	{
		return self::delimiter;
	} // End function get_delimiter

	/**
	 * Fonction qui retourne les champs possibles à uploader selon le produit
	 */
	public function getTopologyProduct()
	{
		$query = "SELECT * FROM edw_object_ref_header
				  WHERE eorh_id_produit = '".self::$product."' ORDER BY eorh_id_column_file";

		$res = pg_query(self::$database_connection,$query);

		if( $res )
		{
			self::$topology = array();
			while($row = pg_fetch_array($res))
			{
				self::$topology[$row['eorh_id_column_file']][] = $row['eorh_id_column_db'];
			}
		}
		else
		{
		
			$this->setErrors("Product ".self::$product." does not exist");
		}
	} // End function getTopologyProduct
	
	public function setIdProduct($id_prod){
		
		self::$id_prod = $id_prod;
		
	}

	/**
	 * Fonction qui initialise le répertoire racine de l'application
	 *
	 * @param string $repertoire_physique_niveau0 : répertoire racine de l'application
	 */
	public function setRepNiveau0($repertoire_physique_niveau0)
	{
		self::$rep_niveau0 = $repertoire_physique_niveau0;
	} // End function set_rep_niveau0

	/**
	 * Fonction qui initialise la connexion à la base de données
	 *
	 * @param string $database_connection : id connexion
	 */
	public function setDbConnection($database_connection)
	{
		self::$database_connection = $database_connection;
	} // End function set_db_connection

	/**
	 * DEPRECATED : Fonction qui affiche le message passé en parametre dans la barre de progression
	 *
	 * @param msg Message à afficher dans la barre de progression
	 */
	protected function displayLoadingInfo ( $msg )
	{
	} // End function displayLoadingInfo

	/**
	 * DEPRECATED : Fonction qui affiche le message passé en parametre dans la barre de progression en mode DEBUG
	 *
	 * @param msg Message à afficher dans la barre de progression
	 */
	protected function displayLoadingDebug ( $msg )
	{
	} // End function displayLoadingDebug

	/**
	 * Création du tableau naLabel qui contient tout les niveaux aggrégation avec leur label.
	 * Ce tableau ne contient pas de notion de famille ce qui est beaucoup plus simple pour la récupération des labels
	 *
	 * @author GHX
	 * @since CB4.1.0.00
	 */
	protected function createArrayNaLabel ()
	{
		//  01/12/2008 GHX
		// Création d'un tableau contenant les labels des niveaux d'aggrégations
		
		if ( $this->getAxe() == 1 )
		{
			$naLabelTmp = getNaLabelList('na');
		}
		else
		{
			$naLabelTmp = getNaLabelList('na_axe3');
		}
		
		
		$naLabel = array();
		foreach ( $naLabelTmp as $family )
		{
			$naLabel = array_merge($naLabel, $family);
		}
		
		self::$naLabel = $naLabel;
	}
	/**
	 * Exécute une requête SQL sur la base de donnée et renvoie le résultat
	 * Affichage des requêtes en mode débugge 2
	 *
	 * @param string $sql : requête sql
	 * @param boolean $transaction : vrai si la requête doit etre effectué dans une transaction (par défaut false)
	 * @return mixed
	 */
	protected function sql ( $query, $transaction = false )
	{
		if ( self::$debug & 2)
			self::demon( '<br /><pre style="color:#3399ff">'. $query .'</pre>');

		$deb = getmicrotime();

		if ( $transaction === true )
			$result_sql = @pg_query(self::$database_connection, 'BEGIN');

		$result_sql = @pg_query(self::$database_connection, $query);
		
		// 14:43 03/12/2008 GHX
		// ajout de self::$database_connection en paramètre de la function pg_last_error
                // 10/11/2011 BBX
                // BZ 23472 : correction du contrôle d'éxécution de la requête
		if (!$result_sql)
		{
			self::demon('<h3 style="color:#fff;background-color:#f00">ERREUR SQL</h3>');
			self::demon( '<pre style="color:#3399ff">'. $query .'</pre>'.pg_last_error(self::$database_connection));
			if ( $transaction === true )
				$result_sql = @pg_query(self::$database_connection, 'ROLLBACK');
			return false;
		}
		elseif ( $transaction === true )
			$result_sql = @pg_query(self::$database_connection, 'COMMIT');

		$fin = getmicrotime();
		if ( self::$debug & 2 )
			self::demon( '> Temps d\'exécution : '. round($fin - $deb, 3) .' secondes<br />');

		return $result_sql;
	} // End function sql

	/**
	 * Fonction de debugage du module.
	 * Ecrit dans le fichier de démon quand le mode débug est activé.
	 *
	 * @param string $text : texte à afficher
	 * @param string $title : titre
	 * @param string $color : couleur du titre
	 */
	protected function debug ( $text , $title = '', $color = 'black' )
	{
		if ( self::$debug & 2 )
		{
			$this->demon ($text , $title, $color);
		}
	} // End function debug

	/**
	 * Ecrire dans le fichier de démon topologie
	 *
	 * @acces protected
	 * @param string $text : texte à afficher
	 * @param string $title : titre
	 * @param string $color : couleur du titre
	 */
	public function demon ( $text , $title = '', $color = 'black' )
	{
		ob_start();
		if ( !empty ( $title ) )
			echo '<span style="color: '. $color .'"><u><b>'. $title .' : </b></u></span><br/>';

		switch( gettype($text) )
		{
			case 'bool' :
			case 'boolean' :
				echo ( $text ? 'TRUE' : 'FALSE' ) .'<br />';
				break;

			case 'float' :
			case 'double' :
			case 'int' :
			case 'integer' :
			case 'string' :
				echo $text .'<br />';
				break;

			case 'NULL' :
				echo 'NULL<br />';
				break;

			default:
				echo "<pre>";
				print_r ( $text );
				echo "</pre>";
		}
		$str = ob_get_contents();

		ob_end_clean();

		$filename_demon = 'demon_topo_'. self::$day .'.html';
		$rep_demon = self::$rep_niveau0 .'file_demon';

		// Nom du fichier de démon avec la date du jour
		if ( is_writable($rep_demon) )
		{
			// modif 11:05 07/11/2007 Gwen
				// Met de résoudre le problème de fopen
			if ( !file_exists($rep_demon.'/'.$filename_demon) )
			{
				exec('touch '.$rep_demon.'/'.$filename_demon);
				exec('chmod 777 '.$rep_demon.'/'.$filename_demon);
			}
			// Si le fichier existe on écrit à la fin sinon on le crée
			if ( $handle = fopen($rep_demon.'/'.$filename_demon, 'a+') )
			{
				fwrite($handle, $str);
				fclose($handle);
			}
		}
		else
		{
			echo "accès refusé sur le fichier de démon";
		}
	} // End function demon

	/**
	 *
	 * @param $column
	 * @param $header
	 */
	public function getIdField($column,$header)
	{
		if ( @in_array($column,$header) )
		{
			$id = array_keys( $header, $column);
			$id = $id[0]+1;

			return $id;
		}
		
		return null;
	} // End function getIdField

	/**
	 * On récupère les temps de chargement de chaque étape de l'upload
	 *
	 *@param text contient le libellé de l'étape concernée ou bien les informations générales de l'upload (mode / type /famille)
	 *@param deb contient le premier timestamp
	 * @param fin contient le deuxième timestamp
	 */
	protected function traceLog ( $text, $deb = "", $fin = "" )
	{
		/* if ( self::$debug )
		{
			if ( $deb == "" && $fin == "" )
				$time = "**********";
			else
				$time = round($fin-$deb,3);
			$query = "INSERT INTO sys_topology_trace(date,action,time) VALUES($this->day,'$text','$time')";
			$this->sql($query);
		} */
	} // End function traceLog

	/**
	 * Exécute une commande Unix sur le serveur
	 * Affichage de la commande et résultat en mode debugge 2
	 *
	 * NOTE : s'il y a une erreur dans la commande, le résultat se vide
	 *
	 * @param string $cmd : commande Unix à exécuter
	 * @param boolean $return : si true retourne le résultat de la commande (defaut : false) Le résultat est sous la forme d'un tableau
	 * @param mixed
	 */
	function cmd ( $cmd, $return = false )
	{

		if ( self::$debug & 2)
			self::demon( '<br /><span style="color:yellow; background-color:black;">&nbsp;'. $cmd .'&nbsp;</span><br />');

		$deb = getmicrotime();
		if ( $return )
		{
			exec($cmd, $result, $error);
			if ( self::$debug & 2 )
				self::demon($result, 'Resultat');
		}
		else
			$o = exec($cmd, $result, $error);

		if ( $error )
		{
			if(substr($cmd,0,4) !== "diff"){
				self::demon( 'ERREUR');
				$this->demon($cmd,"erreur $error sur la commande:");
			}
		}
		$fin = getmicrotime();
		if ( self::$debug & 2 )
			self::demon( '> Temps d\'exécution : '. round($fin - $deb, 3) .' secondes<br />');
		if ( isset($result) )
			return $result;
	} // End function cmd
	
	/**
	 * Get children with unique parent
	 *
	 * @param string family
	 * @return array
	 */
	public function getChildrenWithUniqueParent($family) {
		$query = <<<EOF
		select distinct s1.agregation as agregation from sys_definition_network_agregation s1, (
			select agregation, agregation_level, family from sys_definition_network_agregation 
			where na_max_unique = 1
		) as s2
		where s1.agregation_level = s2.agregation_level-1 
			and s1.family = '$family'
			and s1.family = s2.family
EOF;
		$result = $this->sql($query);
		$data = array();
		while($item = pg_fetch_array($result))
		{
			$data[] = $item;
		}
		return $data;
	}
	
	/**
	 * Update children with unique parent
	 *
	 * Example : if one msc is associated to a network, 
	 * all other items with unique parent (msc, smscenter...) will be associated with this network
     * JLG - BZ 45947/20604
	 */
	public function updateChildrenWithUniqueParent() {
		$nas = $this->getUniqueNas();
		foreach($nas as $na) {
			// get edw_obj_ref by eor_object_type
			$edwObjRefs = $this->getEorIdByEorObjectType($na['agregation']);
			if (sizeof($edwObjRefs) == 1) {
				$newParentEorId = $edwObjRefs[0]['eor_id'];
				$agregation = $na['agregation'];
				$levelSource = $na['level_source'];
				
				
				//1. update existing arcs
				//1.1 create change list
				$query = <<<EOF
select eoar_id, eoar_id_parent from edw_object_arc_ref 
	where eoar_arc_type = '$levelSource|s|$agregation'
	and eoar_id_parent <> '$newParentEorId'
EOF;
				$result = $this->sql($query);
				while($item = pg_fetch_array($result))
				{
					$this->set_changes(array(
						$levelSource, 
						$item['eoar_id'], 
						$levelSource . " <=> " . $agregation,
						$item['eoar_id_parent'],
						$newParentEorId)
					);
				}
				//1.2 do the changes
				$query = <<<EOF
update edw_object_arc_ref set eoar_id_parent = '$newParentEorId'
	where eoar_arc_type = '$levelSource|s|$agregation'
	and eoar_id_parent <> '$newParentEorId'
EOF;
				$result = $this->sql($query);
						
				//2. create new arcs
				//2.1 create change list
				$query = <<<EOF
select eor_id
from edw_object_ref 
where eor_obj_type = '$levelSource' and
	eor_id not in (select eoar_id from edw_object_arc_ref where eoar_arc_type = '$levelSource|s|$agregation')
EOF;
				$result = $this->sql($query);
				while($item = pg_fetch_array($result))
				{
					$this->set_changes(array(
						$levelSource, 
						$item['eor_id'], 
						$levelSource . " <=> " . $agregation,
						'null', 
						$newParentEorId)
					);
				}
				//2.2 do the changes
				$query = <<<EOF
insert into edw_object_arc_ref(eoar_id, eoar_id_parent, eoar_arc_type)
	select eor_id, '$newParentEorId', '$levelSource|s|$agregation'
	from edw_object_ref 
	where eor_obj_type = '$levelSource' and
	eor_id not in (select eoar_id from edw_object_arc_ref where eoar_arc_type = '$levelSource|s|$agregation')
EOF;
				$result = $this->sql($query);
			}
		}
	}
		
	/**
	 * Get network agregations with na_max_unique = 1 and children (level_source)
	 */
	public function getUniqueNas() {
		$query = <<<EOF
select distinct agregation, level_source 
from sys_definition_network_agregation
where na_max_unique = 1;
EOF;
		$result = $this->sql($query);
		$data = array();
		while($item = pg_fetch_array($result, null, PGSQL_ASSOC))
		{
			$data[] = $item;
		}
		return $data;
	}
	
	/**
	 * Get arc id (eo) by eor_object_type
	 *
	 * @param $value eor_object_type
	 * @return array
	 */
	public function getEorIdByEorObjectType($value) {
				$query = <<<EOF
select eor_id
from edw_object_ref
where eor_obj_type = '$value';
EOF;
		$result = $this->sql($query);
		$data = array();
		while($item = pg_fetch_array($result, null, PGSQL_ASSOC))
		{
			$data[] = $item;
		}
		return $data;
	}
	
	/**
	 * Get current time for logging
	 * @return string current date with log format ([])
	 */
	public function getLogDate() {
		return date('[d-M-Y H:i:s]');
	}
	
	/**
	 * Get link for file path on disk
	 * @param string file path
	 * @return a tag
	 */
	public function getLinkForFilePath($path) {
		return "<a href=\"" . str_replace("/home", "", $path) . "\">" . $path . "</a>";
	}
	

} // End Class TopologyLib
?>