<?php
/**
 * 
 * 12/01/12 NSE bz 25402 : si le master est en RHEL 4.5, escapeshellarg() crée un disfonctionnement, on tente donc sans si le premier appel a échoué
 *  24/01/2012 BBX
 *  BZ 25585, 25727 : utilisation de la librairie PHP "PHP Secure Communications Library"
 *  On contrôle désormais le nombre de bits téléchargé et on reconnecte une fois la limite atteinte
 */
require_once REP_PHYSIQUE_NIVEAU_0 . 'class/ssh2/Net/SFTP.php';

/**
 * SSHConnection : classe permettant d'utiliser SSH. Elle se base sur la librairie libssh2 de Linux et utilise les fonctions ssh2_* de PHP.
 * La classe doit être utilisé dans un try/catch car s'il y a une erreur, une exception est levée.
 *
 *
 * @author GHX
 * @version CB4.1.0.0
 * @since CB4.1.0.0
 * @package SSHConnection
 */
class SSHConnection
{
    // Définition des constantes d'OS
    const WINDOWS_REMOTE_SYSTEM = 0;
    const UNIX_REMOTE_SYSTEM    = 1;
    const MAX_RECEIVED_BITS     = 715827882;  // Correspond au 2/3 d'1GB
    const MAX_FILE_SIZE         = 2000000000; // 2GB max par fichier

	/**
	 * Ressource sur une connexion SSH
	 * @version CB4.1.0.0
	 * @since CB4.1.0.0
	 * @var Ressource
	 */
	private $_connection = null;
	
	/**
	 * adresse du serveur distant
	 * @version CB4.1.0.0
	 * @since CB4.1.0.0
	 * @var string
	 */
	private $_sshHost;
	
	/**
	 * nom de l'utilisateur avec lequel on se connecte sur le serveur
	 * @version CB4.1.0.0
	 * @since CB4.1.0.0
	 * @var string
	 */
	private $_sshUser;
	
	/**
	 * mot de passe
	 * @version CB4.1.0.0
	 * @since CB4.1.0.0
	 * @var string
	 */
	private $_sshPassword;
	
	/**
	 * port sur lequel on effectue la connexion 
	 * @version CB4.1.0.0
	 * @since CB4.1.0.0
	 * @var string (defaut : 22)
	 */
	private $_sshPort = 22;
	
	/**
	 * Ressource SFTP bassé sur la connexion SSH
	 * @version CB4.1.0.0
	 * @since CB4.1.0.0
	 * @var Ressource
	 */
	private $_sftp = null;
	
    /** @var integer Type de l'OS distant */
    protected $_remoteSystem = NULL;

	/**
	 * Activation du mode débugage
	 * @version CB4.1.0.0
	 * @since CB4.1.0.0
	 * @var int (defaut : 0)
	 */
	private $_debug = 0;

	/**
     * 01/02/2012 BBX
     * BZ 25585, 25727 : comptabilise le nombre de bits reçus
     * @var integer
     */
    protected $_receivedBits = 0;

    /**
	 * Création d'une connexion ssh sur un serveur distant
	 *
	 * 	ATTENTION : Si la librairie PHP ssh2 n'est pas disponible, une exception est levée.
	 *
	 * @author GHX
	 * @version CB4.1.0.0
	 * @since CB4.1.0.0
	 * @param string $host adresse du serveur distant
	 * @param string $user nom de l'utilisateur avec lequel on se connecte sur le serveur
	 * @param string $password mot de passe
	 * @param int $port port sur lequel on effectue la connexion (defaut : 22)
	 * @param int $level niveau de debug (defaut : 0)
	 * @return SSHConnection
	 */
	public function __construct ( $host, $user, $password, $port = 22, $debug = 0 )
	{
		if ( !function_exists('ssh2_connect') )
		{
            throw new Exception('Cannot use SSH because PHP library is not installed.', 1 );
		}
		
		$this->_sshHost     = $host;
		$this->_sshUser     = $user;
		$this->_sshPassword = $password;
		$this->_sshPort     = $port;

        $this->_connection = $this->connectSO();
    } // End function __construct

    /**
     * 23/01/2012 BBX
     * BZ 25585
     * Cette méthode permet d'établir une connexion SSH via le module PHP
     */
    protected function connectSO()
    {
        	$methods = array
        	(
          		'kex' => 'diffie-hellman-group1-sha1',
          		'client_to_server' => array('crypt' => 'arcfour,blowfish-cbc,aes128-cbc,aes192-cbc,aes256-cbc'),
       		   	'server_to_client' => array('crypt' => 'arcfour,blowfish-cbc,aes128-cbc,aes192-cbc,aes256-cbc')
        	);

        $connection = @ssh2_connect($this->_sshHost, $this->_sshPort, $methods );

        if ( !$connection )
            		throw new Exception(sprintf('Unable to establish connection to %s', $this->_sshHost), 1);
		
		if ( $this->_debug )
			printf('<br />SSH =>Establish connection to %s', $this->_sshHost);
		
        if ( !@ssh2_auth_password($connection, $this->_sshUser, $this->_sshPassword) )
            		throw new Exception( sprintf('Unable to authenticate with %s to %s', $this->_sshUser, $this->_sshHost), 2);

        if ( $this->_debug )
            printf('<br />SSH =>Authenticate with %s', $this->_sshUser);

        $this->_receivedBits = 0;

        if($this->_sftp !== null)
            $this->_sftp = $this->sftp($connection);

        return $connection;
		}
		
    /**
     * 23/01/2012 BBX
     * BZ 25585
     * Cette méthode permet d'établir une connexion SSH via la librairie PHP
     */
    public function connectPHP()
		{
        $sftp  = new Net_SFTP($this->_sshHost, $this->_sshPort);

        if($sftp->bitmap !== NET_SSH2_MASK_CONSTRUCTOR)
            throw new Exception(sprintf('Unable to establish connection to %s', $this->_sshHost), 1);

		if ( $this->_debug )
            printf('<br />SSH =>Establish connection to %s', $this->_sshHost);

        if (!$sftp->login($this->_sshUser, $this->_sshPassword))
            throw new Exception(sprintf('Unable to Authenticate to %s', $this->_sshHost), 1);

        if ( $this->_debug )
			printf('<br />SSH =>Authenticate with %s', $this->_sshUser);

        return $sftp;
		}
		
	/**
     * 01/02/2012 BBX
     * BZ 25585, 25727 : vérification de la quantité de bits reçus
     * Si cette quantité atteind le seuil, on repart sur une nouvelle connexion
     * @param integer
     */
    protected function checkAmountOfReceivedBits($additionalBits = 0)
    {
        if( ($this->_receivedBits + $additionalBits) >= self::MAX_RECEIVED_BITS)
        {
		if ( $this->_debug )
                echo "<br />{$this->_receivedBits} + {$additionalBits} >= ".self::MAX_RECEIVED_BITS;

            $this->_connection = $this->connectSO();
        }
		}

	/**
     * Test la connection à un serveur SSH distant (sans authentification)
     *
     * @since 5.0.4.15
     * @param string $host Adresse IP (ou nom) de l'hote distant
     * @param string $port Numéro du port à utiliser pour le test
     * @return boolean
     */
    public static function testConnection( $host, $port )
    {
        return ( @ssh2_connect( $host, $port ) !== false );
    }

    /**
	 * Définit le niveau de débug
	 *	0 : désactivé
	 *	1 : activé
	 *	2 : activé et affiche le résultat de chaque commande exécuté le serveur distant
	 *
	 * @author GHX
	 * @version CB4.1.0.0
	 * @since CB4.1.0.0
	 * @param int $level niveau de debug
	 */
	public function setDebug ( $level )
	{
		$this->_debug = (int)$level;
	} // End function setDebug
	
	/**
	 * Exécute une commande sur le serveur distant et retourne le résultat de la commande sous forme de tableau
	 *
	 * @author GHX
	 * @version CB4.1.0.0
	 * @since CB4.1.0.0
	 * @return array
	 */
	public function exec ( $cmd )
	{
		$stream = @ssh2_exec($this->_connection, $cmd);
		
		if ( !$stream )
		{
			throw new Exception(sprintf('Unable to execute commande <b>%s</b> on server %s', $cmd, $this->_sshHost));
		}
		
		stream_set_blocking( $stream, true );
		$lines = array();
		while ( $get = fgets($stream) )
		{
            // 14/02/2011 : OJT, utilisation de strlen (module mb_* non installé).
            // bz27348, supression du deuxième paramètres
			$lines[] = $get;
            $this->_receivedBits += strlen( $get );
		}
		
		if ( $this->_debug )
		{
			printf('<br />SSH =>Exec command "%s" on server %s', $cmd, $this->_sshHost);
			
			if ( $this->_debug & 2 )
			{
				echo '<br /><pre>'.print_r($lines, 1).'</pre>';
			}
		}
		return $lines;
	} // End function exec

	/**
	 * Envoie un fichier sur le serveur distant
	 *
	 * @author GHX
	 * @version CB4.1.0.0
	 * @since CB4.1.0.0
	 * @param string $from  chemin vers le fichier local
	 * @param string $to chemin vers le fichier distant
	 * @param int $umask masque de création du fichier distant (defaut : utilise le umask par défaut du serveur distant)
	 * @return void
	 */
	public function sendFile ( $from, $to, $umask = null )
	{
		if ( $umask === null )
		{
			$send = @ssh2_scp_send($this->_connection, $from, $to);
		}
		else
		{
			$send = @ssh2_scp_send($this->_connection, $from, $to, $umask);
		}

		if ( !$send )
		{
			throw new Exception(sprintf('Unable to send file "%s" to "%s" on server %s', $from, $to, $this->_sshHost));
		}
		
		if ( $this->_debug )
		{
			printf('<br />SSH =>Send file "%s" to "%s" on server %s', $from, $to, $this->_sshHost);
		}
	} // End function sendFile

	/**
	 * Récupére un fichier du serveur distant
	 *
	 * @author GHX
	 * @version CB4.1.0.0
	 * @since CB4.1.0.0
	 * @param string $from chemin vers le fichier distant
	 * @param string $to  chemin vers le fichier local
	 * @return void
	 */
	public function getFile ( $from, $to )
	{
        // Récupération de la taille du fichier
        $filesize = $this->fileSize($from);

        // Test de la limite de taille du fichier
        if($filesize > self::MAX_FILE_SIZE) {
            throw new Exception(sprintf('File "%s" is too big on server %s', $from,$this->_sshHost));
        }

        // Si le fichier est plus gros que la limite, on le télécharge à l'aide
        // de la librairie PHP
        if($filesize >= self::MAX_RECEIVED_BITS)
            {
            if ( $this->_debug )
                echo "<br />This file is bigger than ".self::MAX_RECEIVED_BITS.", using PHP lib";

            $sftp = $this->connectPHP();
            $result = $sftp->get($from, $to);
            unset($sftp);
                }
        // Dans le cas contraire, on utilise le module PHP
        else {
            // On reconnecte si la connexion actuelle ne peut télécharger le fichier
            $this->checkAmountOfReceivedBits($filesize);
            // 18/10/2012 BBX
            // BZ 29859 : on supprime la fonction escapehellargs
            $result = @ssh2_scp_recv( $this->_connection, $from, $to );
            // On comptabilise notre fichier
            $this->_receivedBits += $filesize;
            }

        // Test du résultat du téléchargement
        if ( !$result ) {
            throw new Exception(sprintf('Unable to receive file "%s" to "%s" from server %s', $from, $to, $this->_sshHost));
        }

        // Debug
            if ( $this->_debug )
                    printf('<br />SSH =>Receive file "%s" to "%s" from server %s', $from, $to, $this->_sshHost);

	} // End function getFile

	/**
	 * Retourne une Ressource SFTP. La ressource retourné permet d'utiliser les fonctions ssh2_sftp_* de PHP. 
	 * Les fonctions rename, mkdir, rmdir, unlink, stat utilise déjà les fonctions ssh2_sftp_* de PHP.
	 *
	 * @author GHX
	 * @version CB4.1.0.0
	 * @since CB4.1.0.0
	 * @return sftp
	 */
    public function sftp ($connection)
	{
        return ssh2_sftp( $connection );
	} // End function sftp

	/**
	 * Renome un fichier sur le serveur distant
	 *
	 * @author GHX
	 * @version CB4.1.0.0
	 * @since CB4.1.0.0
	 * @param string $from le fichier courant à renommer
	 * @param string $to le nouveau nom du fichier
	 * @return void
	 */
	public function rename ( $from, $to )
	{
		if ( $this->_sftp === null )
                $this->_sftp = $this->sftp( $this->_connection );

		if ( !@ssh2_sftp_rename($this->_sftp, $from, $to) )
		{
			throw new Exception(sprintf('Unable to rename "%s" into %s', $from, $to));
		}
		
		if ( $this->_debug )
		{
			printf('<br />SSH =>Rename file "%s" to "%s" on server %s', $from, $to, $this->_sshHost);
		}
	} // End function rename

	/**
	 * Supprime un dossier sur le serveur distant
	 *
	 * @author GHX
	 * @version CB4.1.0.0
	 * @since CB4.1.0.0
	 * @param string $dir le nom du dossier à supprimer
	 * @return void
	 */
	public function rmdir ( $dir )
	{
		if ( $this->_sftp === null )
                $this->_sftp = $this->sftp( $this->_connection );

		if ( !@ssh2_sftp_rmdir($this->_sftp, $dir) )
		{
			throw new Exception(sprintf('Unable to delete directory "%s" to %s', $dir, $this->_sshHost));
		}
		
		if ( $this->_debug )
		{
			printf('<br />SSH =>Delete directory "%s" on server %s', $dir, $this->_sshHost);
		}
	} // End function rmdir
	
	/**
	 * Créer un dossier sur le serveur distant
	 *
	 * @author GHX
	 * @version CB4.1.0.0
	 * @since CB4.1.0.0
	 * @param string $dir le nom du dossier à créer
	 * @return void
	 */
	public function mkdir ( $dir )
	{
		if ( $this->_sftp === null )
                $this->_sftp = $this->sftp( $this->_connection );

		if ( !@ssh2_sftp_mkdir($this->_sftp, $dir) )
		{
			throw new Exception(sprintf('Unable to create directory "%s" to %s', $dir, $this->_sshHost));
		}
		
		if ( $this->_debug )
		{
			printf('<br />SSH =>Create directory "%s" on server %s', $dir, $this->_sshHost);
		}
	} // End function mkdir

	/**
	 * Supprime un fichier sur le serveur distant
	 *
	 * @author GHX
	 * @version CB4.1.0.0
	 * @since CB4.1.0.0
	 * @param string $file le nom du fichier à supprimer
	 * @return void
	 */
	public function unlink ( $file )
	{
		if ( $this->_sftp === null )
                $this->_sftp = $this->sftp( $this->_connection );

		if ( !@ssh2_sftp_unlink($this->_sftp, $file) )
		{
			throw new Exception(sprintf('Unable to delete file "%s" to %s', $file, $this->_sshHost));
		}
		
		if ( $this->_debug )
		{
			printf('<br />SSH =>Delete file "%s" on server %s', $file, $this->_sshHost);
		}
	} // End function unlink

	/**
	 * Renvoie les informations à propos d'un fichier sous forme de tableau.
	 * Il est possible d'avoir qu'une seule valeur en spécifiant le nom de l'information que l'on veut :
	 * 	- size : taille en octets
	 *	- gid : groupid du propriétaire
	 *	- uid : userid du propriétaire
	 *	- atime : date de dernier accès (Unix timestamp)
	 *	- mtime : date de dernière modification (Unix timestamp)
	 *	- ctime :  date de dernier changement d'inode (Unix timestamp)
	 *	- mode : droit d'accès à l'inode
	 * Si aucune valeur n'est spécifiée, un tableau sera retourné
	 *
	 * Si l'information n'existe pas la fonction retourne null.
	 *
	 * Pour plus d'information sur les valeurs retournées voir la fonction stat (http://fr2.php.net/manual/fr/function.stat.php)
	 *
	 * @author GHX
	 * @version CB4.1.0.0
	 * @since CB4.1.0.0
	 * @param string $file le nom du fichier
	 * @param string $info nom de l'information à retourner (defaut: all)
	 * @return mixed
	 */
	public function stat ( $file, $info = null )
	{
                // 18/10/2012 BBX
                // BZ 29859 : on indique que les chemins avec espaces ne sont pas supportés
                if(substr_count($file, " ") > 0) {
                    sys_log_ast('Warning', 'Trending&Agregation', 'Data Collect', '"'.$file.'" contains spaces. Its collect will probably fail.');
                }
            
		if ( $this->_sftp === null )
                $this->_sftp = $this->sftp( $this->_connection );

		$statinfo = @ssh2_sftp_stat($this->_sftp, $file);

            // Si on n'arrive pas à obtenir les stats, on tente une reconnexion
            if ( !$statinfo ) {
                $this->_connection = $this->connectSO();
                $statinfo = @ssh2_sftp_stat($this->_sftp, $file);
            }

            // On utilise la méthode stat de la librairie PHP pour les gros fichiers
            // ou en cas d'échec
            if(!$statinfo || $statinfo['size'] < 0) {
                $sftp = $this->connectPHP();
                $statinfo = $sftp->stat($file);
            }

		if ( !$statinfo )
		{
			throw new Exception(sprintf('Unable to get stat for file "%s" from server %s', $file, $this->_sshHost));
		}

		if ( $this->_debug )
		{
			printf('<br />SSH =>Get stat for file "%s" from server %s', $file, $this->_sshHost);
		}
		
		if ( $info !== null && $info != 'all' )
		{
			if ( array_key_exists($info, $statinfo) )
			{
				return $statinfo[$info];
			}
			else
			{
				return null;
			}
		}

		return $statinfo;
	} // End function stat

	/**
	 * Vérifie si un fichier ou un dossier existe
	 *
	 * @author GHX
	 * @version CB4.1.0.0
	 * @since CB4.1.0.0
	 * @param string $filename chemin vers le fichier ou le dossier. 
	 * @return boolean
	 */
	public function fileExists ( $filename )
	{
        // 28/02/2011 OJT : DE SFTP, utilisation de méthode multi plateforme
        	try
        	{
            		if( $this->stat( $filename ) !== false ){
				return true;
			}
        	}
        	catch( Exception $ex )
        	{
			return false;
        	}
    	}
	
	/**
	 * Lit la taille d'un fichier ou dossier
	 *
	 * @author GHX
	 * @version CB4.1.0.0
	 * @since CB4.1.0.0
	 * @param string $filename chemin vers le fichier ou le dossier. 
	 * @return int
	 */
	public function fileSize ( $filename )
	{
        // 28/02/2011 OJT : DE SFTP, utilisation de méthode multi plateforme
        $size = $this->stat( $filename, 'size' );
        if($size < 0) {
            // On utilise la méthode size de la librairie PHP pour les gros fichiers
            $sftp = $this->connectPHP();
            $size = $sftp->size($filename);
    }
        return $size;
    }
		
    /**
     * Liste les fichiers d'un répertoire.
     *
     * @param string $path
     * @return array
     */
    public function listDir( $path )
		{
        $fileList = array();
        switch( $this->getRemoteSystem() )
        {
            case self::WINDOWS_REMOTE_SYSTEM :
                try{ $fileList = $this->exec( "dir -l ".escapeshellarg( $path ) ); }catch( Exception $ex ){}
                break;

            default:
            case self::UNIX_REMOTE_SYSTEM :
                try{ $fileList = $this->exec( "ls -l ".escapeshellarg( $path ) ); }catch( Exception $ex ){}
                break;
		}
        return $fileList;
    }
		
    /**
     * Get remote operating system for current connection
     *
     * @return string (WINDOWS_REMOTE_SYSTEM or UNIX_REMOTE_SYSTEM)
		 */
    private function getRemoteSystem()
    {
        if( $this->_remoteSystem === null )
        {
			// 27/06/2012 ACS BZ 27794 Collect fails when using SFTP
			try {
				$testUnix = $this->exec("which ls");
				if (isset($testUnix) && isset($testUnix[0]) && $testUnix[0] != '') {
					$this->_remoteSystem = self::UNIX_REMOTE_SYSTEM;
				}
				else {
					$this->_remoteSystem = self::WINDOWS_REMOTE_SYSTEM;
				}
			}
			catch( Exception $e ) {
				$this->_remoteSystem = self::WINDOWS_REMOTE_SYSTEM;
			}

			if ($this->_debug) {
				printf('<br />System detected: '.$this->_remoteSystem, $file, $this->_sshHost);
			}
        }
        return $this->_remoteSystem;
    }
	
} // End class SSHConnection
?>