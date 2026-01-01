<?php
/**
 * @version 5.1
 *
 * 03/08/2010 OJT : Correction bz16852 (on affiche pas les logs sans modules)
 * 13/07/2011 NSE bz 23040 : utilisation de  get_adr_server() à la place de localhost car la nouvelle politique de sécurité semble l'interdire
 * 09/12/2011 ACS Mantis 837 DE HTTPS support (create a new attribute for ProductModel instead of calling it several times)
 *
 */
/**
 *	Classe permettant de générer les Exports de log T&A
 *	Travaille sur la table sys_global_parameters, sys_definition_network_agregation, edw_object_ref
 *
 *	@author	BBX - 05/11/2009
 *	@version	CB 5.0.1.04
 *	@since	CB 5.0.1.04
 *
 *   23/04/2010 NSE bz 15180 : ajout de \ devant le & pour passer l'id product à versionHistory.php
 *   21/05/2010 OJT : Ajout de la gestion des indicateurs de santé
 *   25/06/2010 OJT : Correction bug suite merge
 *                  : Correction problème download sur slave distant
 *                  : Correction bz 15181
*    12/07/2010 - MPR : Correction du bz 6835 - On trie les éléments du tracelog par date puis par oid
 *   27/07/2010 OJT : Correction bz 16790
 */

require_once $repertoire_physique_niveau0 . "class/HealthIndicator.class.php";

class DownloadLog
{
	/*
	 *	Constantes
	 */
	const TMP_DIR = '/tmp/'; // Répertoire temporaire
	const TRACELOG_TABLE = 'sys_log_ast'; // Table du tracelog
	const GLOBAL_PARAM_TABLE = 'sys_global_parameters'; // Table des paramètres globaux
	const NA_TABLE = 'sys_definition_network_agregation'; // Table sys_definition_network_agregation
	const TOPO_TABLE = 'edw_object_ref'; // Table edw_object_ref

	/** @var Integer Identifiant du produit */
	protected $productId = 0;

	/** @var Integer Identifiant du produit */
	protected $productModel;

    /** @var DataBaseConnection Object de connction à la base de données */
	protected $_database;
    
    /** @var String Date de début de la période à gérer */
	protected $dateDebut;

    /** @var String Date de fin de la période à gérer */
	protected $dateFin;

    /** @var Array Listes des actions demandées par l'utilisateur */
	protected $listAction = Array();

    /**
     * Constructeur
     * @throws
     * @param String $dateDebut
     * @param String $dateFin
     * @param Integer $productId
     */
	public function __construct( $dateDebut, $dateFin, $productId = 0 )
	{
		$this->dateDebut = $dateDebut;
		$this->dateFin = $dateFin;
		$this->productId = $productId;
        $this->productModel = new ProductModel($productId);
        $this->_database = DataBase::getConnection( $productId );

		if( $this->dateDebut > $this->dateFin ){
            throw new InvalidArgumentException( 'End date must be greater than start date' );
        }
	}

	/**
	 *	Définit les actions à effectuer
	 *	@param array : actions
	 */
	public function setListAction($listAction = Array())
	{
		$this->listAction = $listAction;
	}
	
	/**
	 *	Effectue les actions et crée l'archive finale
	 * 	@param String Chemin de l'archive finale
	 */
	public function createArchive( $archivePath )
	{
        /** @var String Chemin du repertoire */
        $filesPath = self::TMP_DIR.'ziplog'.uniqid().'/';

		/** @var String Array Fichiers à mettre dans l'archive */
		$filesToArchive = array();

        /** @var HealthIndicator object Instance de HealthIndicator pour les indicateurs de santé */
        $hiObject = NULL;

		exec( 'mkdir '.$filesPath ); // Création du répertoire où zipper
        
		// Application Daemon 
		if( ( isset( $this->listAction['application_daemon'] ) === TRUE ) && ( $this->listAction['application_daemon'] == 1 ) )
		{
			foreach( $this->getApplicationDaemon( $filesPath ) as $targetFile )
            {
				$filesToArchive[] = $targetFile;
			}
		}
        
		// Topology Daemon 
		if( ( isset( $this->listAction['topology_daemon'] ) === TRUE ) && ( $this->listAction['topology_daemon'] == 1) )
		{
			foreach($this->getTopologyDaemon($filesPath) as $targetFile)
            {
				$filesToArchive[] = $targetFile;
			}
		}

		// Version History
		if( ( isset( $this->listAction['version_history'] ) === TRUE ) && ( $this->listAction['version_history'] == 1 ) )
		{
			$targetFile = $filesPath.'version_history.csv';
			$this->getVersionHistory($targetFile);
			$filesToArchive[] = $targetFile;
		}

		// Tracelog
		if( ( isset( $this->listAction['tracelog'] ) === TRUE ) && ( $this->listAction['tracelog'] == 1 ) )
		{
			$targetFile = $filesPath.'tracelog_'.$this->dateDebut.'_'.$this->dateFin.'.csv ';
			$this->getTracelog($targetFile);
			$filesToArchive[] = $targetFile;
		}

		// Global Parameters 
		if( ( isset( $this->listAction['global_parameters'] ) === TRUE ) && ( $this->listAction['global_parameters'] == 1 ) )
		{
			$targetFile = $filesPath.'global_parameters.csv';
			$this->getGlobalParameters($targetFile);
			$filesToArchive[] = $targetFile;
        }
		// 29/03/2011 NSE Merge 5.0.5 -> 5.1.1
                // Partition Statistics
		if((isset($this->listAction['partition_statistics']) === TRUE) && ($this->listAction['partition_statistics'] == 1))
		{
			$targetFile = $filesPath.'disk_usage.csv';
			$this->getDiskUsage($targetFile);
			$filesToArchive[] = $targetFile;
		}
		// 29/03/2011 NSE Merge 5.0.5 -> 5.1.1
                // Topology Statistics
		if( (isset($this->listAction['topology_statistics']) === TRUE) && ($this->listAction['topology_statistics'] == 1))
		{
			$targetFile = $filesPath.'network_information.csv';
			$this->getTopology($targetFile);
			$filesToArchive[] = $targetFile;
		}


        // Health Indicators
        if( ( isset( $this->listAction['health_indicator'] ) === TRUE ) && ( $this->listAction['health_indicator'] == 1 ) )
		{
            // Définition du fichier de destination et création de l'objet
			$targetFile = $filesPath.'health_indicator_'.date( 'Ymd_His' ).'.csv';
            $hiObject   = new HealthIndicator( HealthIndicator::HI_CALL_MODE_IHM, $this->productId );

            // Génération du fichier (avec paramètre de date, DE SA in H.I.)
            $hiObject->generateOutputFile( $targetFile, $this->dateDebut );
			$filesToArchive[] = $targetFile;
            unset( $hiObject ); // Destruction de l'objet
		}
        else
        {
            // Ok, les indicateurs de santé ne sont pas demandés au téléchargement
        }

		// File Permissions
		if( ( isset( $this->listAction['file_permissions'] ) === TRUE ) && ( $this->listAction['file_permissions'] == 1 ) )
		{
			$targetFile = $filesPath.'file_permissions.txt';
			$this->getFilePerm($targetFile);
			$filesToArchive[] = $targetFile;
		}
        
		// Création de l'archive
		$cmd = 'cd '.$filesPath.' && zip -9 '.$archivePath.' *';
		exec( $cmd );
		$cmd = 'rm -Rf '.$filesPath;
		exec( $cmd );
	}

	/**
	 *	Gère léxécution locale ou distante d'une commande shell
	 *	@param string : commande shell
	 *	@return string : résultat de la commande
	 */
	public function execCommand($command)
	{
		// Variable de résultat
		$result = null;
		// Adresse IP du serveur
		$ipAdress = get_adr_server();
		// Récupération des infos produit
		$ProductValues = $this->productModel->getValues();
		// Si le produit est sur le même serveur, on effectue une éxécution locale
		if(($this->productId == '') || ($ipAdress == $ProductValues['sdp_ip_address']))
		{
			exec($command,$result);
		}
		// Sinon, on effectue une éxécution distante
		else
		{
			if(!isset($this->SSH))
				$this->SSH = new SSHConnection($ProductValues['sdp_ip_address'],$ProductValues['sdp_ssh_user'],$ProductValues['sdp_ssh_password'],$ProductValues['sdp_ssh_port']);
			$result = $this->SSH->exec($command);
		}
		// Retour du résultat
		return $result;
	}

    /**
     * Récupère le contenu du tracelog pour les dates paramétrées
     * @param String $targetFile
     * @return Boolean
     */
	public function getTracelog( $targetFile )
	{
        /**
         *  25/06/2010 OJT : Nouvelle méthode de récupération des logs dans syslogast.
         *  On ne redirige pas la requête dans un fichier dans /tmp (problème
         *  en cas de serveur distant).
         *
         *  03/08/2010 : Correction bz16852
         */
		$query = "SELECT message_date, severity, message, module FROM ".self::TRACELOG_TABLE." WHERE trim(module) != '' AND message_date BETWEEN '".date('Y/m/d 00:00:00',strtotime($this->dateDebut))."' AND '".date('Y/m/d 23:59:59',strtotime($this->dateFin))."' ORDER BY message_date DESC";
        $result = $this->_database->getAll( $query );
        if( ( $h = fopen( $targetFile, 'w' ) ) !== FALSE ){
            fwrite( $h, "Date;Severity;Message;Module\n" );
            foreach( $result as $line )
            {
                foreach( $line as $key => $value )
                {
                    fwrite( $h, $value.';' );
                }
                fwrite( $h, "\n" );
            }
            fclose( $h );
        }
		return (file_exists( $targetFile ) && ( filesize( $targetFile ) > 0 ) );
	}
	
	/**
	 *	Récupère le contenu de la table sys_global_parameters
	 *	@param String : fichier cible
	 *	@return bool
	 */
	public function getGlobalParameters( $targetFile )
	{
        /**
         *  25/06/2010 OJT : Nouvelle méthode de récupération de sgp.
         *  On ne redirige pas la requête dans un fichier dans /tmp (problème
         *  en cas de serveur distant). On utilise la fonction getTable
         */
        if( ( $h = fopen( $targetFile, 'w' ) ) !== FALSE ){
            fwrite( $h, "Parameter;Value;Configure;Client Type;Label;Comment;Category;Order;Specific\n" );
            foreach( $this->_database->getTable( self::GLOBAL_PARAM_TABLE ) as $line )
            {
                fwrite( $h, str_replace( "\N", '', str_replace( "\t", ';', $line ) ) );
            }
            fclose( $h );
        }
		return ( file_exists( $targetFile ) && ( filesize( $targetFile ) > 0 ) ); // Retour existence fichier
	}

   	/**
	 *	Récupère les droits sur les fichiers
	 * 	@param string : fichier cible
	 *	@return bool
	 */
	public function getFilePerm($targetFile)
	{
		// Récupération des infos produit
		if($this->productId != '')
		{
			$ProductValues = $this->productModel->getValues();
			$directory = '/home/'.$ProductValues['sdp_directory'];
		}
		else
		{
			$directory = REP_PHYSIQUE_NIVEAU_0;
		}
		// Commande qui récupère les permissions
		$cmd = 'ls -lisahR '.$directory;
		// Exécution de la commande
		$result = $this->execCommand($cmd);
		// Création du fichier
		file_put_contents($targetFile,implode("\n",$result));
		// Retour existence fichier
		return (file_exists($targetFile) && (filesize($targetFile) > 0));
	}

	/**
	 *	Récupère le version history
	 *	@param string : fichier cible
	 *	@return bool
	 */
	public function getVersionHistory($targetFile)
	{
		// On va demander à la classe version_history de bien vouloir nous générer un fichier :)
		// 23/04/2010 NSE bz 15180 : ajout de \ devant le & pour passer l'id product
        // 13/07/2011 NSE bz 23040 : utilisation de  get_adr_server() à la place de localhost car la nouvelle politique de sécurité semble l'interdire
		// 09/12/2011 ACS Mantis 837 DE HTTPS support (+ add --no-check-certificate parameter to allow HTTPS calls)
		$cmd = 'wget --no-check-certificate -O '.$targetFile.' '.$this->productModel->getCompleteUrl('class/versionHistory.php?from=about\&product='.$this->productId);
		exec($cmd);
		// Retour existence fichier
		return (file_exists($targetFile) && (filesize($targetFile) > 0));
	}
	
	/**
	 *	Récupère le(s) démons de l'application
	 *	@param string : répertoire cible
	 *	@return bool
	 */
	public function getTopologyDaemon($filesPath)
	{
		// Récupération des infos produit
		$ProductValues = $this->productModel->getValues();
		
		// Produit courant
		$APPLICATION_DIR = REP_PHYSIQUE_NIVEAU_0;
		// Le produit existe
		if($this->productId != '') $APPLICATION_DIR = '/home/'.$ProductValues['sdp_directory'].'/';

		// On récupère toutes les informations pour les dates comprises entre $dateDebut et $dateFin
		$timeStamp = strtotime($this->dateDebut);
		// Mémorise les archives créées
		$createdArchives = Array();
		
		// Adresse IP du serveur
		$ipAdress = get_adr_server();

		// Parcours des jours demandés
		while(date('Ymd',$timeStamp) <= date('Ymd',strtotime($this->dateFin))) 
		{
			// On va aller chercher le fichier démon correspondant à la date en cours
			$demonName = 'demon_topo_'.date('Ymd',$timeStamp).'.html';
			// Si le produit est sur le même serveur, on définit simplement son emplacement
			if(($this->productId == '') || ($ipAdress == $ProductValues['sdp_ip_address']))
			{
				// Chemin local du fichier
				$demonPath = $APPLICATION_DIR.'file_demon/'.$demonName;			
			}
			// Sinon, on effectue une copie distante
			else
			{
				// Connexion SSH au produit
				if( !isset( $SSH ) ){
					$SSH = new SSHConnection($ProductValues['sdp_ip_address'],$ProductValues['sdp_ssh_user'],$ProductValues['sdp_ssh_password'],$ProductValues['sdp_ssh_port']);
                                }
                
				/**
                                *  Récupération du fichier démon
                                *  microtime(true) permet de s'assurer de l'unicité du fichier
                                *  créer dans /tmp (pour éviter les pb vus en multiproduit)
                                */
                               $demonPath = '/tmp/'.microtime( true ).$demonName;
                               try{
                                   // 22/01/2013 BBX
                                   // BZ 31344 : utilisation de la variable déclarée ci-dessus
                                   $SSH->getFile($APPLICATION_DIR.'file_demon/'.$demonName,$demonPath);
                               }
                               catch( Exception $e ){
                                   // Aucun traitement
                               }
                                               // Chemin local du fichier		
                           }

			// Récupération des démons pour la date en cours
			if(file_exists($demonPath))
			{
				$archive = $filesPath.'demon_topo_'.date('Ymd',$timeStamp).'.html';
				// Copie du fichier dns le répertoire de log
				$cmd = 'cp '.$demonPath.' '.$archive;
				exec($cmd);
				$createdArchives[] = $archive;
			}			
			
			// On passe au jour suivant
			$timeStamp = strtotime(date('Ymd',$timeStamp).' + 1 day');
        }
		
		// On retourne les archives crées
		return $createdArchives;	
	}

	/**
	 *	Récupère le(s) démons de l'application
	 *	@param string : répertoire cible
	 *	@return bool
	 */
	public function getApplicationDaemon($filesPath)
	{
		// Récupération des infos produit
		$ProductValues = $this->productModel->getValues();
		
		// Produit courant
		$APPLICATION_DIR = REP_PHYSIQUE_NIVEAU_0;
		// Le produit existe
		if($this->productId != '') $APPLICATION_DIR = '/home/'.$ProductValues['sdp_directory'].'/';

		// On récupère toutes les informations pour les dates comprises entre $dateDebut et $dateFin
		$timeStamp = strtotime($this->dateDebut);
		// Mémorise les archives créées
		$createdArchives = Array();
		
		// Adresse IP du serveur
		$ipAdress = get_adr_server();

		// Parcours des jours demandés
		while(date('Ymd',$timeStamp) <= date('Ymd',strtotime($this->dateFin))) 
		{
			// On va aller chercher le fichier démon correspondant à la date en cours
			$demonName = 'demon_'.date('Ymd',$timeStamp).'.html';
			// Si le produit est sur le même serveur, on définit simplement son emplacement
			if(($this->productId == '') || ($ipAdress == $ProductValues['sdp_ip_address']))
			{
				// Chemin local du fichier
				$demonPath = $APPLICATION_DIR.'file_demon/'.$demonName;			
			}
			// Sinon, on effectue une copie distante
			else
			{
				// Connexion SSH au produit
				if( !isset( $SSH ) ){
					$SSH = new SSHConnection($ProductValues['sdp_ip_address'],$ProductValues['sdp_ssh_user'],$ProductValues['sdp_ssh_password'],$ProductValues['sdp_ssh_port']);
                }
                
				/**
                 *  Récupération du fichier démon
                 *  microtime(true) permet de s'assurer de l'unicité du fichier
                 *  créer dans /tmp (pour éviter les pb vus en multiproduit)
                 */
				$demonPath = '/tmp/'.microtime( true ).$demonName;
				try{
					$SSH->getFile($APPLICATION_DIR.'file_demon/'.$demonName, $demonPath);
                }
                catch( Exception $e ){
                    // Aucun traitement
                }
			}
			
			// Récupération des démons pour la date en cours
			if(file_exists($demonPath))
			{
				$archive = $filesPath.'demon_'.date('Ymd',$timeStamp).'.zip';
				if($this->splitDeamon($demonPath,$archive)) {
					$createdArchives[] = $archive;
				}
			}			
			
			// On passe au jour suivant
			$timeStamp = strtotime(date('Ymd',$timeStamp).' + 1 day');
		}
		
		// On retourne les archives crées
		return $createdArchives;
	}

	/**
	 *	Split un fichier démon
	 *	@param string : fichier démon
	 *	@return bool
	 */
	public function splitDeamon($filename,$targetFile)
	{
		// Récupère le fichier dans un tableau
		$cmd = "cat {$filename} | sed \"s/\(<br[ \/:]*>\)/\\1\\n/ig\" ";
		exec($cmd, $results, $erreur);

		// Récupère le nom du démon
		$extension = substr($filename, strrpos($filename,'.')+1);
		$name = basename($filename, '.'.$extension);

		// répertoire temporaire
		// Destruction par sécurité
		$cmd = 'rm -Rf '.self::TMP_DIR.$name;
		exec($cmd);
		// Crée le repértoire dans lequel seront créé tous les fichiers
		mkdir(self::TMP_DIR.$name);

		$rmLastSplit = false;
		$compteur = 0;
		
		$masterType = 'unknown';
		$masterTime = 'unknown';

		// Boucle sur toutes les lignes du tableau
		foreach ( $results as $result )
		{
			// Si la ligne contient "Lancement master" c'est qu'on est sur un nouveau process
			// dans se cas on incrément le compteur afin qu'il met les prochaines lignes du tableau dans un nouveau fichier
			if ( ereg('Lancement master', $result) )
			{	
				// Type de master
				if(ereg('Compute', $result))
				{
					if(ereg('Hourly', $result))
					{
						$masterType = 'compute_hourly';
					}
					else
					{
						$masterType = 'compute';
					}
				}
				else
				{
					$masterType = 'retrieve';
				}
				
				// Time
				if(ereg('Time stamp', $result))
				{
					$timestamp = trim(substr($result,strpos($result,':')+2,31));
					$masterTime = date('Hi',strtotime($timestamp));
				}

				// Regarde si on doit supprimer le dernier fichier créé
				if ( $rmLastSplit == true )
				{
					//unlink(self::TMP_DIR.$name.'/'.$name.'_part_'.sprintf('%02d', $compteur).'_.html');
					$cmd = 'rm -f '.self::TMP_DIR.$name.'/'.$name.'_part_'.sprintf('%02d', $compteur).'_*.html';
					exec($cmd);
					$rmLastSplit = false;
					$compteur--;
				}
				
				// Incrément la valeur pour créer un nouveau fichier
				$compteur++;
				
				// Si la ligne contient "Launcher" il sera supprimé dès qu'on passe sur un nouveau fichier
				if ( ereg('Launcher', $result) )
				{
					$rmLastSplit = true;
				}
			}
			
			// Ajout la ligne dans le fichier
			file_put_contents(self::TMP_DIR.$name.'/'.$name.'_part_'.sprintf('%02d', $compteur).'_'.$masterType.'_'.$masterTime.'.html', $result, FILE_APPEND);
		}

		// On zippe le répertoire		
		$cmd = 'cd '.self::TMP_DIR.$name;
		$cmd .= ';zip -9 '.$targetFile.' *';
		exec($cmd);

		// Puis on le détruit
		$cmd = 'rm -Rf '.self::TMP_DIR.$name;
		exec($cmd);
		// Retour existence fichier
		return (file_exists($targetFile) && (filesize($targetFile) > 0));
	}
	
	/************************ STATIC FUNCTIONS ***************************/
		
	/**
	 *	Supprime les anciens logs
	 */
	public static function deleteOldLogs()
	{
		$cmd = 'rm -f '.REP_PHYSIQUE_NIVEAU_0.'upload/log_*.zip';
		exec($cmd);
	}

	/**
	 *	Créé l'archive de toutes les archives produit
	 */
	public static function createSuperArchive($subarchives)
	{
		// Nom de l'archive finale
		$archiveName = 'log_allproducts_'.date('YmdHi').'.zip';
		// Création du répertoire temporaire de travail
		$tempDir = self::TMP_DIR.'zipallprod'.uniqid().'/';
		mkdir($tempDir);
		// On déplace toutes les sous archives vers ce répertoire
		foreach($subarchives as $archive)
		{
			$cmd = 'mv '.REP_PHYSIQUE_NIVEAU_0.'upload/'.$archive.' '.$tempDir;
			exec($cmd);
		}
		// On créé l'archive finale
		$cmd = 'cd '.$tempDir;
		$cmd .= ';zip -9 '.REP_PHYSIQUE_NIVEAU_0.'upload/'.$archiveName.' *';
		exec($cmd);
		// Suppression du répertoire temporaire
		$cmd = 'rm -Rf '.$tempDir;
		exec($cmd);
		// Retour de l'archive finale
		return $archiveName;
	}

    /**
     * Determine si le log (la case à cocher) est selectionner ou pas
     * Un test est fait dans les cookies
     * @param String $logName
     * @param Integer $defaultValue
     */
    public static function isLogSelected( $logName, $defaultValue )
    {
        $cookieValues = array();
        if( isset( $_COOKIE['downloadlog'] ) ){
            $cookieValues = unserialize( stripslashes( $_COOKIE['downloadlog'] ) );
            if( isset( $cookieValues[$logName] ) ){
                return intval( $cookieValues[$logName] );
            }
            return 0; // Le cookie existe mais pas la clé, on retourne 0
        }
        return $defaultValue; // Le cookie n'existe pas, on retourne la valeur par défaut
    }
}
?>