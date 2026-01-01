<?php
/*
	10/07/2009 GHX
		- Modification de la fonction getList() pour vérifier aussi si on contexte a été installé sur un slave et on récupère aussi la dernière fois que le contexte a été installé
	03/08/2009 GHX
		- Correction du BZ 10919 [REC][T&A Cb 5.0][TP#3][TS#AA2-CB50][TC#36442][Context] : l'installation d'un contexte enlève les utilisateurs astellia
			-> Problème dans la fonction hasAlreadyMountContext() au niveau de la requête SQL
	04/09/2009 GHX
		- (Evo) pour chaque contexte on récupère la liste des types de produits sur lesquels il peut être monté
	17/09/2009 GHX
		- Correction du BZ 11533 [REC][contexte management] on peut réinitialiser le premier contexte
	22/09/2009 GHX
		- Correction du BZ 11533
			-> Le client ne peut pas supprimer les contextes ASTELLIA (ceux créés par les fichiers Excels)
	10/11/2009 GHX
		- Correction du BZ 9665 [REC][T&A CB 5.0][CONTEXT MGT]: création backup contexte incomplète
			-> Modication de la fonction getList() maintenant on fait la vérification sur le nom du fichier au de ID de sys_versioning
	16/11/2009 GHX
		- Modification des corrections du BZ 9665
	24/03/2010 NSE bz 14815 : on ne doit pas prendre en compte le produit mixed_kpi pour les contextes
	17/05/2010 NSE : Installation standard, utilisation d'une variable globale pour les chemins vers les exécutables psql et php (PSQL_DIR et PHP_DIR)
    04/10/2010 NSE bz 16534 : passage d'un contexte en mode migration sur un produit déjà migré
*/
?>
<?php
/**
 * 
 *
 * @author GHX
 * @version CB4.1.0.00
 * @since CB4.1.0.00
 * @package Context
 */
class Context
{
	/**
	 * Version du CB
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var string
	 */
	const VERSION_CB = '5';
	
	/**
	 * Connexion à la base de données
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var Ressource
	 */
	private $_db;

	/**
	 * Tableaux de toutes les connexion à la base de données
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var array
	 */
	private $_listDb = array();
	
	/**
	 * Constructeur
	 *
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 */
	public function __construct ()
	{
		// Par défaut on se connecte sur la base par défaut car ici on récupère juste le paramétrage contexte
		// et celui-ci est le même sur tous les produits (normalement)
        // 31/01/2011 BBX
        // On remplace new DatabaseConnection() par Database::getConnection()
        // BZ 20450
		$this->_db = DataBase::getConnection();
	} // End function __construct

	/**
	 * Retourne la liste de tous les éléments
	 *
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @return array
	 */
	public function getAllElements ()
	{
		$elements = array();
		$result = $this->_db->getAll('SELECT sdc_id FROM sys_definition_context ORDER BY sdc_order_display');
		foreach ( $result as $row )
		{
			try
			{
				$elements[$row['sdc_id']] = new ContextElement($row['sdc_id']);
			}
			catch ( Exception $e )
			{
				echo $e->getMessage();
				// maj 04/03/2010 - MPR : Correction du BZ  
			}
		}
		return $elements;
	} // End function getAllElements

	/**
	 * Restaure la base de données comme elle était avant qu'un contexte soit monté. Chaque fois qu'un contexte est monté une sauvegarde de la base est faite.
	 * La sauvegarde contient un dump de toutes les tables qui peuvent être modifées par un contexte.
	 *
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param string $directory répertoire dans lequel se trouve le contexe de sauvegarde
	 * @param string $ctxname nom du contexte
	 */
	public function restore ( $directory, $ctxname )
	{
		if ( substr($directory, -1) != '/' )
		{
			$directory .= '/';
		}

		$basename = basename($ctxname, '.tar.bz2');
		$backup = "{$directory}backup_before_mount_context_{$ctxname}";

		$dir = "{$directory}backup_before_mount_context_{$basename}/";
		
		// 02/03/2010 BBX : on vérifie l'existence du répertoire avant de le créer
		// si le répertoire existe déjà on le détruit. BZ 13354
		// 08/04/2010 : il ne faut pas supprimer le répertoire s'il existe ! BZ 14955
		if(!is_dir($dir))
		{
			mkdir($dir, 0777);
		}	

		$cmdUntar = sprintf(
				'tar xfj "%s" -C "%s"',
				$backup,
				$dir
			);
		exec($cmdUntar);

		// Récupère les informations sur tous les produits
		$infoAllProducts = getProductInformations();

		// 24/03/2010 NSE bz 14815 : on ne doit pas prendre en compte le produit mixed_kpi pour les contextes
		// Boucle sur tous les produits
		foreach ( $infoAllProducts as $clef => $product )
		{
			if($product['sdp_id']==ProductModel::getIdMixedKpi()){
				// on supprime le produit du tableau 
				unset($infoAllProducts[$clef]);
			}
		}

		// Parcourt le contenu du backup décompressé
		$listing = new DirectoryIterator($dir);

		foreach ( $listing as $filesql )
		{
			// on ignore "." et ".."
			if ( $filesql->isDot() )
				continue;

			// Récupère l'identifiant du produit
			if ( ereg ("^product_([0-9]*).sql$", $filesql->getFilename(), $regs) )
			{
				$idProduct = $regs[1];

				// Création de la commande pour restaurer la base
				$cmdRestore = sprintf(
						'cat "%s" | env PGPASSWORD=%s '.PSQL_DIR.'/psql -U %s %s -h %s',
						$filesql->getPathname(),
						$infoAllProducts[$idProduct]['sdp_db_password'],
						$infoAllProducts[$idProduct]['sdp_db_login'],
						$infoAllProducts[$idProduct]['sdp_db_name'],
						$infoAllProducts[$idProduct]['sdp_ip_address']
					);
				// Exécution de la commande
				exec($cmdRestore);
			}
		}
		
		$cmdRm = sprintf(
				'rm -rf "%s"',
				$dir
			);
		exec($cmdRm);
	} // End function restore

	/**
	 * Retourne TRUE si un contexte a déjà été monté sinon FALSE
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param int $idProduct identifiant du produit (default : le produit master)
	 * @return boolean
	 */
	public function hasAlreadyMountContext ( $idProduct = '' )
	{
		// 17:17 03/08/2009 GHX
		// Correction du BZ 10919
		// Problème de requetage SQL

                // 07/07/2010 BBX
                // Correction de la requête
                // - pour gérer des dates identiques sur plusieurs éléments
                // - permettre la compatibilité avec les version futures
                // BZ 16534

                // 28/07/2010 BBX
                // Ajout de l'exclusion des dates dans l'ancien format (format textuel)
                // BZ 16592
                $query = "SELECT
                        id
                FROM
                        sys_versioning
                WHERE
                        date ~ '^[0-9]{4}'
                        AND replace(date, '_', '')::bigint >=
                        (

                                SELECT
                                        replace(date, '_', '')::bigint AS install_date
                                FROM
                                        sys_versioning
                                WHERE
                                        item = 'cb_version'
                                        AND substr(item_value,1,position('.' in item_value)-1)::integer >= ".self::VERSION_CB."
                                GROUP BY install_date
                                ORDER BY install_date ASC
                                LIMIT 1
                        )
                        AND item = 'contexte'
                        AND replace(date, '_', '') ~ '^[0-9]*$'";
		
        // 31/01/2011 BBX bz 20450 : On remplace new DatabaseConnection() par Database::getConnection()
		$db = DataBase::getConnection($idProduct);
        
		$db->execute($query);
		
		if ( $db->getNumRows() > 0 )
			return true;
		
		return false;
	} // End function hasAlreadyMountContext
	
	/**
	 * Retourne TRUE si c'est une version migrée sinon FALSE
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param int $idProduct identifiant du produit (default : le produit master)
	 * @return boolean
	 */
	public function isMigratedVersion ( $idProduct = '' )
	{
		$query = sprintf(
				"
				SELECT 
					id 
				FROM
					sys_versioning
				WHERE 
					item = 'cb_version'
					AND item_value NOT LIKE '%s%%' 
				ORDER BY
					oid desc 
				LIMIT 1
				",
				self::VERSION_CB
			);
		
        // 31/01/2011 BBX bz 20450 : On remplace new DatabaseConnection() par Database::getConnection()
		$db = DataBase::getConnection($idProduct);
		
		$db->execute($query);
		
		if ( $db->getNumRows() > 0 )
			return true;
		
		return false;
	} // End function isMigratedVersion
	
	/**
	 * Recupere la liste des contextes et verifie s'ils sont installes et s'ils ont un backup
	 * 
	 * 	11:34 10/07/2009 GHX
	 * 		- On vérifie aussi si le contexte a été installé sur un slave
	 * 
	 * @author SPS
	 * @date 24/03/2009
	 * @return array 
	 */
	public function getList() {
		$upload_dir = REP_PHYSIQUE_NIVEAU_0."upload/context/";
		
		// Récupère les informations sur tous les produits
		$productsInformations = getProductInformations();

		// 24/03/2010 NSE bz 14815 : on ne doit pas prendre en compte le produit mixed_kpi pour les contextes
		// Boucle sur tous les produits
		foreach ( $productsInformations as $clef => $product )
		{
			if($product['sdp_id']==ProductModel::getIdMixedKpi()){
				// on supprime le produit du tableau 
				unset($productsInformations[$clef]);
			}
		}

		// 08:46 22/09/2009 GHX
		// Correction du BZ 11533
		// On récupère le type client admin qui est connecté
		$client_type = getClientType($_SESSION['id_user']);
		
		//on recupere le nom des fichiers de contexte uploades
		$query_sdcm = "
				SELECT 
					sdcm_file_name,
					sdcm_date
				FROM 
					sys_definition_context_management
				ORDER BY
					sdcm_date DESC
				";
		
		$results = $this->_db->getAll($query_sdcm);
		$tcontext = null;
		foreach($results as $context_file) {
			//on verifie que chacun des fichiers de contexte existe dans le repertoire
			if (file_exists($upload_dir.$context_file['sdcm_file_name'])) {
				$context['filename'] = $context_file['sdcm_file_name'];
				$context['date'] = $context_file['sdcm_date'];
				$context['installed'] = 0;
				$context['id_product'] = '';
				$context['installed_info'] = '';
				$context['backup'] = '';
				$context['customisateur'] = $this->isContextAstellia($upload_dir.$context_file['sdcm_file_name']);
				// 15:35 04/09/2009 GHX
				$context['info_products'] = $this->getTypeProductsInContext($upload_dir.$context_file['sdcm_file_name']);
				
				// 10:57 10/07/2009 GHX
				// Si le contexte n'est pas monté sur le master on vérifie s'il est monté sur un slave
				// Boucle sur tous les produits
				$backup = true;
				foreach ( $productsInformations as $product )
				{
					$db = $this->getConnection($product['sdp_id']);
					$query_sv = "
						SELECT 
							item_value, date, id
						FROM 
							sys_versioning
						WHERE
							item = 'contexte'
						AND 
							item_value = '".$context['filename']."'
						ORDER BY date DESC
					";
					$context_version = $db->getRow($query_sv);
					if( $context_version )
					{
						$context['installed'] = 1;
						
						if ( $context['installed_info'] != '' )
						{
							$context['installed_info'] .= "<br >";
						}
						// 11:20 10/07/2009 GHX
						// Récupère sur quel produit et à quelle date/heure le contexte à été installé pour la dernière fois
						$date = explode('_', $context_version['date']);
						$context['installed_info'] .= $product['sdp_label']. ' : '.$date[2].'/'.$date[1].'/'.$date[0].' '.$date[3].':'.$date[4];
						
						// 16:17 17/09/2009 GHX
						// Correction du BZ 11533 : on ne doit pas pouvoir réinitialiser le premier contexte d'installé
						// Cette requete permet de récupérer le premier contexte installé sur pour la dernier version du CB installé
						// On ne prend en compte que le premier digit pour le CB 
						// 17:25 10/11/2009 GHX
						// Correction du BZ 9665
						// Modification du champ du SELECT item_value au lieu de id
						$queryFistIdContext = "
							SELECT
								id, item_value
							FROM
								sys_versioning
							WHERE 
								replace(date, '_', '') > (
									SELECT 
										replace(date, '_', '')
									FROM
										sys_versioning
									WHERE 
										item = 'cb_version'
										AND item_value LIKE (
											SELECT substring(item_value from 1 for 1)||'%' 
											FROM sys_versioning 
											WHERE item='cb_version' 
											ORDER BY id DESC 
											LIMIT 1
										)
									ORDER BY
										date ASC 
									LIMIT 1
								) 
								AND item = 'contexte'
								AND replace(date, '_', '') ~ '^[0-9]*$'
							ORDER BY date ASC
							LIMIT 1
							";
						// 09:12 16/11/2009 GHX
						// Modification de la fonction appelé
						$fistContext = $db->getRow($queryFistIdContext);
						// 17:25 10/11/2009 GHX
						// Correction du BZ 9665
						// Maintenant on fait la vérification sur le nom du fichier et plus sur l'id de sys_versioning
						// 09:12 16/11/2009 GHX
						// Modification de la condition
						$backup &= ($fistContext['item_value'] != $context_version['item_value'] && $fistContext['id'] < $context_version['id'] ) ? true : false;
					}
					else
					{
						// $backup &= false;
					}
				}
				
				
				//on verifie s'il existe un backup du contexte uniquement sur le master
				if (file_exists($upload_dir."backup_before_mount_context_".$context_file['sdcm_file_name']) && $backup)
				{
					$context['backup'] = "backup_before_mount_context_".$context_file['sdcm_file_name'];
				}
				
				$tcontexte[] = $context;
			}
		}
		/*
		
		CETTE PARTIE PERMET DE LISTER LES CONTEXTES MONTER SUR LES PRODUITS SLAVES AVANT LEURS ACTIVATIONS
			FONCTIONNEL : 
				- listage des contextes (sur serveur local ou distant)
				- affichage 
				- download (sur serveur local ou distant)
			NON FONCTIONNEL
				- montage des contextes
				- suppressions des contextes
				- restores des contextes
				- détection si c'est un contexte astellia ou non
				
		foreach ( $productsInformations as $product )
		{
			// 16:42 17/09/2009 GHX
			// On regarde sur chaque slave s'il n'y a pas des contextes de montés
			if ( $product['sdp_master'] == 0 )
			{
				$db = $this->getConnection($product['sdp_id']);
				$slaveResults = $db->getAll($query_sdcm);

				// Si le slave est sur le même serveur que le master
				if ( $product['sdp_ip_address'] == get_adr_server() )
				{
					foreach ( $slaveResults as $slaveContextFile )
					{
						//on verifie que chacun des fichiers de contexte existe dans le repertoire
						if ( file_exists('/home/'.$product['sdp_directory'].'/upload/context/'.$slaveContextFile['sdcm_file_name']) )
						{
							$slaveContext = array();
							$slaveContext['filename'] = $slaveContextFile['sdcm_file_name'];
							$slaveContext['id_product'] = $product['sdp_id'];
							$slaveContext['date'] = $slaveContextFile['sdcm_date'];
							$slaveContext['installed'] = 0;
							$slaveContext['installed_info'] = '';
							$slaveContext['info_products'] = $this->getTypeProductsInContext('/home/'.$product['sdp_directory'].'/upload/context/'.$slaveContextFile['sdcm_file_name']);
							$query_sv = "
								SELECT 
									item_value, date, id
								FROM 
									sys_versioning
								WHERE
									item = 'contexte'
								AND 
									item_value = '".$slaveContextFile['sdcm_file_name']."'
								ORDER BY date DESC
							";
							$context_version = $db->getRow($query_sv);
							if( $context_version )
							{
								$slaveContext['installed'] = 1;
								$date = explode('_', $context_version['date']);
								$slaveContext['installed_info'] .= $product['sdp_label']. ' : '.$date[2].'/'.$date[1].'/'.$date[0].' '.$date[3].':'.$date[4];
							}
							
							$tcontexte[] = $slaveContext;
						}
					}
				}
				else // Le slave est sur un serveur distant
				{
					try
					{
				
						$ssh = new SSHConnection($product['sdp_ip_address'], $product['sdp_ssh_user'], $product['sdp_ssh_password'], $product['sdp_ssh_port']);
						foreach ( $slaveResults as $slaveContextFile )
						{
							//on verifie que chacun des fichiers de contexte existe dans le repertoire
							if ( $ssh->fileExists('/home/'.$product['sdp_directory'].'/upload/context/'.$slaveContextFile['sdcm_file_name']) )
							{
								$slaveContext = array();
								$slaveContext['filename'] = $slaveContextFile['sdcm_file_name'];
								$slaveContext['date'] = $slaveContextFile['sdcm_date'];
								$slaveContext['installed'] = 0;
								$slaveContext['id_product'] = $product['sdp_id'];
								$slaveContext['installed_info'] = '';
								
								$cmd = 'tar tfj "'.'/home/'.$product['sdp_directory'].'/upload/context/'.$slaveContextFile['sdcm_file_name'].'" | sed "s/^.*_\([0-9]*\).tar.bz2$/\1/g"';
								$result = $ssh->exec($cmd);
								array_map('intval', $result);
								
								$slaveContext['info_products'] = $this->getTypeProductsInContext('/home/'.$product['sdp_directory'].'/upload/context/'.$slaveContextFile['sdcm_file_name']);
								$query_sv = "
									SELECT 
										item_value, date, id
									FROM 
										sys_versioning
									WHERE
										item = 'contexte'
									AND 
										item_value = '".$slaveContextFile['sdcm_file_name']."'
									ORDER BY date DESC
								";
								$context_version = $db->getRow($query_sv);
								if( $context_version )
								{
									$slaveContext['installed'] = 1;
									$date = explode('_', $context_version['date']);
									$slaveContext['installed_info'] .= $product['sdp_label']. ' : '.$date[2].'/'.$date[1].'/'.$date[0].' '.$date[3].':'.$date[4];
								}
								
								$tcontexte[] = $slaveContext;
							}
						}
					}
					catch ( Exception $e )
					{
					}
				}
			}
		}
		*/		
		return $tcontexte;
	}
	
	/**
	 * Retourne la connexion à une base de données d'un produit
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param int $idProduct identifiant du produit on doit se connecter
	 * @return DataBaseConnection
	 */
	private function getConnection ( $idProduct )
	{
		if ( !array_key_exists($idProduct, $this->_listDb) )
		{
            //31/01/2011 BBX bz 20450 : On remplace new DatabaseConnection() par Database::getConnection()
			$this->_listDb[$idProduct] = DataBase::getConnection($idProduct);
			if ( $this->_debug & 2 )
			{
				$this->_listDb[$idProduct]->setDebug(1);
			}
		}

		return $this->_listDb[$idProduct];
	} // End function getConnection
	
	/**
	 * Retourne la liste des types de produits contenu dans la contexte
	 *	Ex : 4 = GSM ...
	 *
	 * @author GHX
	 * @since CB 5.0.0.08
	 * @version CB 5.0.0.08
	 * @param string $context chemin complet vers le contexte
	 * @return array
	 */
	public function getTypeProductsInContext ( $context )
	{
		$cmd = 'tar tfj "'.$context.'" | sed "s/^.*_\([0-9]*\).tar.bz2$/\1/g"';
		exec($cmd, $result);
		return array_map('intval', $result);
	} // End function getTypeProductsInContext
	
	/**
	 * Retourne TRUE si c'est un contexte provenant d'ASTELLIA sinon false
	 *
	 *	22/09/2009 GHX
	 *		- Ajout de la fonction pour la correction du BZ 11533
	 *
	 * @author GHX
	 * @version CB 5.0.0.09
	 * @since CB 5.0.0.09
	 * @param string $context chemin complet vers le fichier de contexte
	 * @return boolean
	 */
	private function isContextAstellia ( $context )
	{
		$cmd = 'tar tfj "'.$context.'" | grep -E "^ctx_[0-9]{12}_[0-9]*.tar.bz2$" | wc -l';
		$result = exec($cmd);
		return ($result == 0 ? true : false);
	} // End function isContextAstellia
	
} // End class Context
?>