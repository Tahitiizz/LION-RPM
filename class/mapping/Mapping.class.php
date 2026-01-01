<?php
/*
	11/12/2008 GHX
		- création du fichier
	10/02/2009 GHX
		- implémentation de la fonction download
		- correction de petits bugs divers
	11/02/2009 GHX
		- modification de la fonction copy pour prendre en compte sur quel serveur se trouve le fichier à copier
		- suppression des fichiers une fois les traitements fini (uniquement si le mode débug est désactivé)
*/
?>
<?php
include_once dirname( __FILE__ ).'/MappingAbstract.class.php';
include_once dirname( __FILE__ ).'/MappingCheck.class.php';

/**
 * Cette classe permet de chargé un fichier de mapping en base
 *
 * @author : GHX
 * @version CB4.1.0.00
 * @since CB4.1.0.00
 */
class Mapping extends MappingAbstract
{
	/**
	 * nom du fichier CSV à charger
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var string
	 */
	private $_filename;
	
	/**
	 * nom du fichier CSV contenant la topo du master
	 *
	 *	Contient uniquement les colonnes : eor_id ; eor_obj_type (cf table edw_objec_ref)
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var string
	 */
	private $_fileMasterTopo;
	
	/**
	 * nom du fichier CSV à charger
	 *
	 *	Contient la table edw_objec_ref
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var string
	 */
	private $_fileMappedTopo;
	
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
	private $_masterProduct;
	
	/**
	 * Information sur le produit master topologie
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var array
	 */
	private $_masterTopology;
	
	/**
	 * Information sur le produit mappé
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var array
	 */
	private $_productMapped;
	
	/**
	 * Répertoire upload
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var string
	 */
	private $_dirUpload;
	
	/**
	 * Délimiteur de colonnes du fichier
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var string
	 */
	private $_delimiter;
	
	/**
	 * Tableau contenant les colonnes de l'entête, contient exactement les mêmes valeurs de que le tableau $_columnsName
	 * mais les index représentent le numéro de la colonne
	 *
	 *	NB : il n'y a donc pas d'index 0, il y a donc que les index 1, 2 et 3
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var array
	 */
	private $_header;
	
	/**
	 * Type de valeur que l'on veut dans la colonne na pour le donwload d'un fichier
	 *	- ast : valeur Astellia
	 *	- ta : valeur T&A
	 *
	 *	10/02/2009 GHX
	 *		- ajout
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var string (default : ta)
	 */
	private $_typeColumnNa = "ta";
	
	/**
	 * Constructeur
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param Ressource $db Ressource de connexion à la base de données
	 */
	public function __construct ( $db )
	{
		parent::__construct($db);
	} // End function __construct
	
	/**
	 * Destructeur (appelé automatique à la destruction de l'objet en général à la fin du script PHP en cours)
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 */
	public function __destruct ()
	{
		// Efface les 2 fichiers de topologies créés
		if ( $this->_debug == 0 )
		{
			@unlink($this->_dirUpload.$this->_fileMasterTopo);
			@unlink($this->_dirUpload.$this->_fileMappedTopo);
		}
	} // End function __destruct
	
	/**
	 * Retourne la valeur d'un paramètre
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @return mixed
	 */
	public function __get ( $key )
	{
		if ( isset($this->$key) )
		{
			return $this->$key;
		}
		
		$trace = debug_backtrace();
		trigger_error('Le paramètre '.$key.'n\'existe pas : '.$trace[0]['file'].' ['.$trace[0]['line'].']', E_USER_NOTICE);
		return null;
	} // End function __get
	
	/**
	 * Spécifie le répertoire upload dans lequel seront créés les fichiers temporaires
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param string $repUpload chemin du répertoire upload
	 */
	public function setDirectoryUpload ( $dirUpload )
	{
		if ( substr($dirUpload, -1) != '/' )
			$dirUpload .= '/';
		
		$this->_dirUpload = $dirUpload;
	} // End function setDirectoryUpload

	/**
	 * Spécifie le nom du fichier à upload
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param string $filename nom du fichier
	 */
	public function setFile ( $filename )
	{
		$this->_filename = $filename;
	} // End function setFile
	
	/**
	 * Spécifie le délimiteur du fichier
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param string $delimiter valeur du délimiteur
	 */
	public function setDelimiter ( $delimiter )
	{
		$this->_delimiter = $delimiter;
	} // End function setDelimiter
	
	/**
	 * Spécifie un tableau d'information du master produit
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param array $masterProduct tableau d'information sur le master produit
	 */
	public function setMasterProduct ( $masterProduct )
	{
		$this->_masterProduct = $masterProduct;
	} // End function setMasterProduct
	
	/**
	 * Spécifie un tableau d'information du produit master topologie
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param array $masterTopology tableau d'information sur le master topologie
	 */
	public function setMasterTopology ( $masterTopology )
	{
		$this->_masterTopology = $masterTopology;
	} // End function setMasterTopology
	
	/**
	 * Spécifie un tableau d'information du produit mappé c'est à dire le produit sur lequel on charge le fichier de mapping
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param array $productMapped tableau d'information du produit mappé
	 */
	public function setProductMapped ( $productMapped )
	{
		$this->_productMapped = $productMapped;
	} // End function setProductMapped
	
	/**
	 * Spécifie l'entête du fichier
	 *
	 *	NOTE : renseigné pendant le check
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param array $header
	 */
	public function setHeader ( $header )
	{
		$this->_header = $header;
	} // End function setHeader
	
	/**
	 * Spécifie quel type de valeur on veut dans la colonne na : 
	 *	- ast : les noms Astellia
	 *	- ta : les noms présents dans T&A
	 * Cette fonction sert uniquement pour l'upload si aucune valeur spécifique c'est la valeur "ta" qui est précise par défaut
	 *
	 *	10/02/2009 GHX
	 *		- ajout de la fonction
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param string $typeColumnNa
	 */
	public function setTypeColumnNa ( $typeColumnNa )
	{
		$this->_typeColumnNa = $typeColumnNa;
	} // End function setTypeColumnNa
	
	/**
	 * Initilisation les fichiers nécessaires au mapping.
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 */
	public function prepareFiles ()
	{
		$this->_fileMasterTopo = uniqid('masterTopo', true).'.mapping';
		$this->_fileMappedTopo = uniqid('mappedTopo', true).'.mapping';
		
		// Création du fichier _fileMasterTopo
                // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
		$db_masterTopology = Database::getConnection($this->_masterTopology['sdp_id']);
		$queryMasterTopo = sprintf("
						COPY (SELECT eor_id, eor_obj_type FROM edw_object_ref) 
						TO '%s'
						WITH DELIMITER '%s'
						NULL ''
					",
					'/home/'.$this->_masterTopology['sdp_directory'].'/upload/'.$this->_fileMasterTopo,
					$this->_delimiter
				);
		
		$db_masterTopology->executeQuery($queryMasterTopo);
		
		// Copie le fichier créé dans le répertoire upload du produit master
		$this->copy($this->_fileMasterTopo, $this->_masterTopology, $this->_masterProduct);
		
		// Création du fichier _fileMappedTopo
                // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
		$db_mappedTopology = Database::getConnection($this->_productMapped['sdp_id']);
		$queryMappedTopo = sprintf("
						COPY (
							SELECT eor_id, eor_obj_type, eor_id_codeq,
								eor_date, eor_label, eor_on_off, eor_blacklisted
							FROM edw_object_ref
						)
						TO '%s'
						WITH DELIMITER '%s'
						NULL ''
					",
					'/home/'.$this->_productMapped['sdp_directory'].'/upload/'.$this->_fileMappedTopo,
					$this->_delimiter
				);
		
		$db_mappedTopology->executeQuery($queryMappedTopo);
		// Copie le fichier créé dans le répertoire upload du produit master
		$this->copy($this->_fileMappedTopo, $this->_productMapped, $this->_masterProduct);
		
	} // End function prepareFiles
	
	/**
	 * Lancer la vérification du fichier
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 */
	public function check ()
	{
		$mappingCheck = new MappingCheck($this->_db);
		$mappingCheck->setDebug($this->_debug);	
		$mappingCheck->setCheck($this);	
		$mappingCheck->process();
	} // End function check
	
	/**
	 * Lance l'insertion du mapping en base
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 */
	public function load ()
	{
		$fileResult = $this->generateFileResult();
		
		// Copie le fichier dans le répertoire upload du produit mappé
		$this->copy($fileResult,$this->_masterProduct, $this->_productMapped);
		
		// Charge le fichier
		$this->loadResult($fileResult);
		
		// 11/02/2009 GHX
		// Efface les fichiers uniquement si on n'est pas en mode débug
		if ( $this->_debug == 0 )
		{
			@unlink($this->_dirUpload.$this->_filename);
			@unlink($this->_dirUpload.$fileResult);
			if ( get_adr_server() != $this->_productMapped['sdp_ip_address'] )
			{
				try
				{
					// 31/01/2011 MMT bz 20347 : ajout ssh_port
					$SSHConnection = new SSHConnection($this->_productMapped['sdp_ip_address'], $this->_productMapped['sdp_ssh_user'], $this->_productMapped['sdp_ssh_password'], $this->_productMapped['sdp_ssh_port']);
					$SSHConnection->unlink('/home/'.$this->_productMapped['sdp_directory'].'/upload/'.$fileResult);
				}
				catch ( Exception $e )
				{
				}
			}			
		}
	} // End function load
	
	/**
	 * Vide le mapping d'un produit
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 */
	public function truncate ()
	{
                // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
		$db_mappedTopology = Database::getConnection($this->_productMapped['sdp_id']);
		
		$queryMappedTopo = "
						--- Suppression de la colonne
						ALTER TABLE edw_object_ref DROP COLUMN eor_id_codeq;
						
						--- Recréation la colonne
						ALTER TABLE edw_object_ref ADD COLUMN eor_id_codeq TEXT;
						
						--- Recréation de l index
						CREATE INDEX index_eor_id_codeq
							ON edw_object_ref
							USING btree (eor_id_codeq);
					";
		
		$db_mappedTopology->executeQuery('BEGIN');
		if ( $db_mappedTopology->executeQuery($queryMappedTopo) )
		{
			$db_mappedTopology->executeQuery('COMMIT');
		}
		else
		{
			$db_mappedTopology->executeQuery('ROLLBACK');
			throw new Exception(__T('A_E_UPLOAD_TOPOLOGY_ERROR_DURING_UPADTE'));
		}
	} // End function truncate
	
	/**
	 * Créer un fichier de mapping à partir du produit mappé sélectionné
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @return string le nom du fichier de mappé créé
	 */
	public function download ()
	{
		$fileMappedTopo = sprintf(
				'mappingTopology_%s_%s_%s.csv',
				str_replace(' ', '', $this->_masterTopology['sdp_label']),
				str_replace(' ', '', $this->_productMapped['sdp_label']),
				date('YmdHis')
			);
		// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
		$db_mappedTopology = Database::getConnection($this->_productMapped['sdp_id']);
		
		switch ( $this->_typeColumnNa )
		{
			case 'ta' : 
					$query = "
						COPY (
							SELECT 
								eor_id,
								eor_id_codeq,
								eor_obj_type
							FROM
								edw_object_ref
							WHERE
								eor_id_codeq IS NOT NULL
							ORDER BY
								eor_obj_type
						)
						TO '%s'
						WITH DELIMITER '%s'
						NULL ''
					";
					break;
			
			case 'ast' : 
					// Récupère le nom du module
					$module = $db_mappedTopology->getOne("SELECT value FROM sys_global_parameters WHERE parameters = 'module'");
					
					$query = "
						COPY (
							SELECT eor_id, eor_id_codeq, 
							CASE 
								WHEN (SELECT eorh_id_column_file FROM edw_object_ref_header WHERE eor_obj_type = eorh_id_column_db AND eorh_id_produit = '{$module}') IS NULL 
								THEN eor_obj_type 
								ELSE (SELECT eorh_id_column_file FROM edw_object_ref_header WHERE eor_obj_type = eorh_id_column_db AND eorh_id_produit = '{$module}')
							END AS na
							FROM 
								edw_object_ref
							WHERE 
								eor_id_codeq IS NOT NULL
							ORDER BY
								na
						)
						TO '%s'
						WITH DELIMITER '%s'
						NULL ''
					";
					break;
		}
		
		$queryCopyMappedTopo = sprintf (
					$query,
					'/home/'.$this->_productMapped['sdp_directory'].'/upload/'.$fileMappedTopo,
					$this->_delimiter
				);
		
		$db_mappedTopology->executeQuery($queryCopyMappedTopo);
		
		// Copie le fichier créé dans le répertoire upload du produit master
		$this->copy($fileMappedTopo, $this->_productMapped, $this->_masterProduct);
		
		if ( @filesize('/home/'.$this->_masterProduct['sdp_directory'].'/upload/'.$fileMappedTopo) == 0 || !file_exists('/home/'.$this->_masterProduct['sdp_directory'].'/upload/'.$fileMappedTopo) )
		{
			@unlink('rm -f /home/'.$this->_masterProduct['sdp_directory'].'/upload/'.$fileMappedTopo);
			throw new Exception(__T('A_MAPPING_EMPTY'));
		}
		
		// Ajout du header au début du fichier
		$cmdAddHeader = sprintf(
				"sed -i '1i %s;%s;%s' /home/%s/upload/%s",
				$this->_columnsName['A_MAPPING_TOPO_COLUMN_LABEL_NA_ID_MAPPED'],
				$this->_columnsName['A_MAPPING_TOPO_COLUMN_LABEL_NA_ID_CODEQ'],
				$this->_columnsName['A_MAPPING_TOPO_COLUMN_LABEL_NA_TYPE'],
				$this->_masterProduct['sdp_directory'],
				$fileMappedTopo
			);
		$this->exec($cmdAddHeader);
		
		return $fileMappedTopo;
	} // End function download
	
	/**
	 * Génère le fichier de topologie qui sera réinséré dans la table edw_object_ref et retourne le nom du fichier généré
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @return $string nom généré
	 */
	private function generateFileResult ()
	{
		$fileResult = uniqid('result', true).'.mapping';
		
		// Inverse les clés <=> valeurs
		$header = array_flip($this->_header);
		
		$indexNE = $header[$this->_columnsName['A_MAPPING_TOPO_COLUMN_LABEL_NA_TYPE']];
		$indexMapped = $header[$this->_columnsName['A_MAPPING_TOPO_COLUMN_LABEL_NA_ID_MAPPED']];
		$indexCodeq = $header[$this->_columnsName['A_MAPPING_TOPO_COLUMN_LABEL_NA_ID_CODEQ']];
		
                // 13/04/2012 BBX
                // BZ 22401 : on rend la mise à jour insensible à la casse
		$cmdGenerateFile = sprintf("
					awk '
					BEGIN {
						FS=\"%s\";
						OFS = FS;
						file1=\"\";
					}
					{
						# Si on est la première ligne
						if ( 1==FNR )
						{
							# si on est sur le premier fichier
							if ( file1==\"\" )
							{
								file1=FILENAME;	
								# Première ligne du fichier chargé, on saute l entête
								Next;								
							}
						}
						
						# Si on est sur le premier fichier
						if ( file1==FILENAME )
						{
							# On mémorie les éléments réseaux par type
							exists[tolower($%2\$s),$%3\$s] = 1;
							values[tolower($%2\$s),$%3\$s] = $%4\$s;						
						}
						else
						{
							# Associe le eor_codeq
							if ( exists[$2,$1] != \"\" )
							{
								$3 = tolower(values[$2,$1]);
							}
							print $0
						}
					}
					' %5\$s %6\$s > %7\$s", 
					$this->_delimiter,
					$indexNE,
					$indexMapped,
					$indexCodeq,
					$this->_dirUpload.$this->_filename,
					$this->_dirUpload.$this->_fileMappedTopo,
					$this->_dirUpload.$fileResult
				);

		$this->exec($cmdGenerateFile, true);
		
		return $fileResult;
	} // End function generateFileResult
	
	/**
	 * Génère le fichier de topologie qui sera réinséré dans la table edw_object_ref
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param string $file nom du fichier de résultat
	 */
	private function loadResult ( $file )
	{
                // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
		$db_mappedTopology = Database::getConnection($this->_productMapped['sdp_id']);
		
		$queryMappedTopo = sprintf(
					"
						COPY edw_object_ref (
							eor_id, eor_obj_type, eor_id_codeq,
							eor_date, eor_label, eor_on_off, eor_blacklisted
						)
						FROM '%s'
						WITH DELIMITER '%s'
						NULL ''
					",
					'/home/'.$this->_productMapped['sdp_directory'].'/upload/'.$file,
					$this->_delimiter
				);
		
		$db_mappedTopology->executeQuery('BEGIN');
		$db_mappedTopology->executeQuery('TRUNCATE edw_object_ref;');
		if ( $db_mappedTopology->executeQuery($queryMappedTopo) )
		{
			$db_mappedTopology->executeQuery('COMMIT');
		}
		else
		{
			$db_mappedTopology->executeQuery('ROLLBACK');
			throw new Exception(__T('A_E_UPLOAD_TOPOLOGY_ERROR_DURING_UPADTE'));
		}
	} // End function loadResult
	
	/**
	 * Génère le fichier de topologie qui sera réinséré dans la table edw_object_ref
	 * 
	 *	ATTENTION : le fichier d'origine est supprimé
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param string $file chemin du fichier source
	 * @param array $fromInfo tableau d'information sur la source
	 * @param array $toInfo tableau d'information sur la destination
	 */
	private function copy ( $file, $fromInfo, $toInfo )
	{
		$from = '/home/'.$fromInfo['sdp_directory'].'/upload/'.$file;		
		$to = '/home/'.$toInfo['sdp_directory'].'/upload/'.$file;
		
		// Copie le fichier en local si les IP sont identiques
		if ( $fromInfo['sdp_ip_address'] == $toInfo['sdp_ip_address'] )
		{
			if ( $this->_debug & 2 )
			{
				echo '<br />copie du fichier en local : '.$file;
				echo '<br /> de '.$fromInfo['sdp_directory'].' ['.$fromInfo['sdp_ip_address'].']';
				echo '<br /> vers '.$toInfo['sdp_directory'].' ['.$toInfo['sdp_ip_address'].']';
			}
			if ( $fromInfo['sdp_directory'] != $toInfo['sdp_directory'] )
			{
				copy($from, $to);
				@unlink($from);
			}
		}
		else // Si on utilise SSH pour récupérer le fichier
		{
			if ( $this->_debug & 2 )
			{
				echo '<br />copie du fichier via SSH : '.$file;
				echo '<br /> de '.$fromInfo['sdp_directory'].' ['.$fromInfo['sdp_ip_address'].']';
				echo '<br /> vers '.$toInfo['sdp_directory'].' ['.$toInfo['sdp_ip_address'].']';
			}
			try
			{
				// 11/02/2009 GHX
				// Si l'adresse IP sur lequel se trouve le fichier est à la même que celui du serveur
				// On se connecte sur le serveur distant pour envoyer le fichier
				if ( get_adr_server() == $fromInfo['sdp_ip_address'] )
				{
					// 31/01/2011 MMT bz 20347 : ajout ssh_port
					$SSHConnection = new SSHConnection($toInfo['sdp_ip_address'], $toInfo['sdp_ssh_user'], $toInfo['sdp_ssh_password'],$toInfo['sdp_ssh_port']);
					$SSHConnection->sendFile($from, $to);
				}
				else // Sinon on se connecte en local pour récupérer le fichier
				{
					// 31/01/2011 MMT bz 20347 : ajout ssh_port
					$SSHConnection = new SSHConnection($fromInfo['sdp_ip_address'], $fromInfo['sdp_ssh_user'], $fromInfo['sdp_ssh_password'],$fromInfo['sdp_ssh_port']);
					$SSHConnection->getFile($from, $to);
				}
				$SSHConnection->exec('rm -f '.$from);
			}
			catch ( Exception $e )
			{
				if ( $this->_debug )
				{
					echo "<br />Problème de copie via SSH ".$e->getMessage();
				}
				throw new Exception(__T('A_E_UPLOAD_TOPOLOGY_ERROR_DURING_UPADTE'));
			}
		}
	} // End function copy
	
} // End class Mapping
?>