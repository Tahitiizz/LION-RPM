<?php
/*
	14/04/2009 GHX
		- Suppression des doublons dans les compteurs après avoir monter un contexte
	16/04/2009 GHX
	 	- Ajout des fonctions deleteUsers() & restoreInfoUsersDeleted()
	17/04/2009 GHX
		- Avant de monter le contexte on supprime les menus de la table menu_deroulant_intranet qui ne sont pas de type client
		- Désactivation des vérifications sur les profils
	22/04/2009 GHX
		-  Modification de la requete de suppression des compteurs en doublons pour prendre en compte le nms_table, sinon tous les capture_duration étaient supprimés
	27/04/2009 GHX
		- Suppression de tous les menus associés au profil user
	24/06/2009 GHX
		- On met 1 pour id produit dans sys_pauto_config
		- Supprime la ligne du RI dans graph_data et sys_pauto_config
	30/09/2009 GHX
		- Correction du BZ 9855
			-> Ajout de l'option -i pour ignorer les différences de version de postgres dans le cas d'un multi-produit sur des serveurs distant donc les versions des postgres sont différentes
		- Correction du BZ 11785 [DEV][CB50][CONTEXTE] : duplication de KPi lors du montage du contexte pendant une migration
	21/12/2009 GHX
		- Modification de la fonction replaceNameById ()
	24/03/2010 NSE bz 14815 : on ne doit pas prendre en compte le produit mixed_kpi pour les contextes
	17/05/2010 NSE : Installation standard, utilisation d'une variable globale pour les chemins vers les exécutables psql et php (PSQL_DIR et PHP_DIR)
    29/06/2010 CCT1 :
               - Si il y a des doublons dans les compteurs (même id sur des famille différente), on affiche la liste des ces compteurs.
               - Requête spécifique pour les compteurs,  on ajoute une jointure sur la colonne family de sys_pauto_page_name (qui sera supprimée dans la méthode replaceNameById)
                 Cel permet de gérer le cas où l'on a des compteurs avec le même identifiant mais sur des familles différentes.
               - Modif de toutes les query pour gérer le cas où on a des graphe et dashboard avec le même nom qui se font référence
                 entre eux avec des droits différents (dash astellia vers un graph user par exemple)
   05/07/2010 MPR :
               -  Suppression des doublons sur le champ id_ligne de sys_field_reference
   19/2/2011 MMT bz 15606 ajout des profils clients avec reinitialization des menu sur profiles par default
  30/05/2011 MMT bz 15606 reopen, pour certain produits (HPG) le nom dans le contexte est UserProfile, pour d'autre (IU) UserProfile
 *
*/
?>
<?php
/**
 * Cette permet d'effectuer certains traitement avant et après le montage d'un contexte pour les versions qui sont migrées
 *
 * ATTENTION : le traitement doivent être effectué une seulement fois pour une base de données
 *
 * @author GHX
 * @version CB4.1.0.00
 * @since CB4.1.0.00
 * @package Context
 */
class ContextMigration
{
	/**
	 * Active ou non le mode débug
	 *	- 0 : désactivé
	 *	- 1 : activé
	 *	- 2 : activé et affiche les requetes SQL (uniquement les requetes exécutées via DataBaseConnection)
	 *	- 6 : activé, affiche les requetes SQL (uniquement les requetes exécutées via DataBaseConnection) et aucune compression
	 *
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var int
	 */
	private $_debug = 0;
	
	/**
	 * Répertoire dans lequel sera créé le contexte
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var string
	 */
	private $_directory = null;

	/**
	 * Liste des produits sur lesquels on doit faire une migration du contexte
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var array
	 */
	private $_listIdProduct = array();

	/**
	 * Tableaux de toutes les connexion à la base de données
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var array
	 */
	private $_listDb = array();

	/**
	 * Tableaux d'informations sur les 4 utilisateurs par défaut supprimés
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var array
	 */
	private $_infoUsers = array();
	
	/**
	 * Tableaux d'informations sur les menus clients
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var array
	 */
	private $_infoMenus = array();
	
	/**
	 * Tableaux d'informations sur les profils
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var array
	 */
	private $_infoProfiles = array();
	
	/**
	 * Constructeur
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 */
	public function __construct ()
	{
	} // End function __construct
	
	/**
	 * Spécifie le niveau de débug
	 *	- 0 : désactivé
	 *	- 1 : activé
	 *	- 2 : activé et affiche les requetes SQL (uniquement les requetes exécutées via DataBaseConnection)
	 *	- 6 : activé, affiche les requetes SQL (uniquement les requetes exécutées via DataBaseConnection) et aucune compression
	 *
	 * @author GHX
	 * @version CB 4.1.0.00
	 * @since CB 4.1.0.00
	 * @param int $level
	 */
	public function setDebug ( $level )
	{
		$this->_debug = intval($level);
	} // End function setDebug
	
	/**
	 * Ajout un identifiant de produit qui doit sur lequel une migration du contexte doit être faite
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param int $idProduct identifiant du produit
	 */
	public function addProduct ( $idProduct )
	{
		$this->_listIdProduct[] = $idProduct;
	} // End function $idProduct

	/**
	 * Spécifie le répertoire dans lequel se trouve le contexte où l'on peut décompresser le contexte
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param string $dir chemin absolute vers le répertoire
	 */
	public function setDirectory ( $dir )
	{
		if ( !is_dir($dir) )
			throw new Exception(__T('A_E_CONTEXT_DIRECTORY_NOT_EXISTS', $dir));

		if ( !is_writable($dir) )
			throw new Exception(__T('A_E_CONTEXT_DIRECTORY_NOT_WRITEABLE', $dir));

		if ( substr($dir, -1) != '/' )
		{
			$dir .= '/';
		}
		$this->_directory = $dir;
	} // End function setDirectory

        protected function cleanSFR( $_db )
        {
            // Récupération de l'id ligne le plus grand
            // Pour redéfinir les id_ligne de chaque doublons, on incrémente id_ligne max de 1
            $query = "SELECT max(id_ligne) as id_ligne FROM sys_field_reference";
            $id_ligne_max = $_db->getOne($query);

            // Création d'une table temporaire contenant les raws que l'on va modifiés
            $_db->execute("DROP TABLE IF EXISTS check_sys_field_ref");
            $query = "CREATE TABLE check_sys_field_ref(id_ligne text, old_id_ligne text, nms_field_name text, edw_group_table text);";
            $_db->execute($query);

            // Requête qui identifie les doublons
            $query = "
                        SELECT
                            SFR1.id_ligne as old_id_ligne,SFR1.nms_field_name, SFR1.edw_group_table
                        FROM
                            sys_field_reference SFR1, sys_field_reference SFR2
                        WHERE
                            SFR1.id_ligne=SFR2.id_ligne
                        GROUP BY
                            SFR1.id_ligne, SFR1.edw_field_name, SFR1.new_date, SFR1.nms_field_name,SFR1.edw_group_table
                        HAVING COUNT(SFR1.id_ligne) > 1
                        ORDER BY SFR1.id_ligne
                       ";
            $result = $_db->getAll($query);

            if ( $this->_debug )
                echo "\n\n<br /> Nettoyage de la table sys_field_reference (Suppression des doublons au niveau de id_ligne)\n<br />";

            $id = $id_ligne_max;
            $tab = array();
            $raws_fixed = array();


            // On récupère les doublons
            foreach($result as $row)
            {
                // Si l'id a déjà été identifié, on enregistre le compteur qui sera modifié
                if( isset($row['old_id_ligne'], $raws_fixed ) )
                {
                    $id++;

                    $old_id         = $row['old_id_ligne'];
                    $nms            = ($row['nms_field_name'] == "") ? '\\N' : $row['nms_field_name'];
                    $group_table    = ($row['edw_group_table'] == "") ? '\\N' : $row['edw_group_table'];
                    // Construction du tableau de données avec les nouveaux ID
                    $tab[] = "$id\t{$old_id}\t{$nms}\t{$group_table}";

                }
                else
                {
                // Si l'id n'est pas connu, on l'enregistre dans un tableau pour identifier ses futurs doublosn
                    $raws_fixed[] = $row['old_id_ligne'];
                }
            }
            if ( $this->_debug )
                echo "\n\n<br />Nombre de compteurs dont les id sont deja existant : ".count($tab);

            $_db->execute("TRUNCATE check_sys_field_ref");
            $_db->setTable("check_sys_field_ref", $tab);

            // Mise à jour des doublons dans la table sys_field_reference
            $_update = "UPDATE sys_field_reference t1
                        SET id_ligne = t0.new_id_ligne
                        FROM
                        (
                                SELECT id_ligne as new_id_ligne, nms_field_name ,edw_group_table
                                FROM check_sys_field_ref
                        ) t0
                        WHERE
                        t0.nms_field_name = t1.nms_field_name
                        AND t0.edw_group_table = t1.edw_group_table
            ";
            $_db->execute( $_update );

            if ( $this->_debug )
                echo "\n\n<br />Nombre de compteurs dont les id ont été mis a jour : ".$_db->getAffectedRows();

            $_db->execute("DROP TABLE IF EXISTS check_sys_field_ref");
        } // End function cleanSFR()

	/**
	 * Exécute certains traitement sur les produits venant d'un CB 4.0 et sur lesquels on monte le premier contexte
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param string $ctxname nom du contexte
	 */
	public function beforeMount ( $ctxname )
	{
		// Création d'un fichier de sauvegarde avant toutes les modifications
		$this->createBackup($ctxname);

		if ( $this->_debug )
		{
			echo "\n\n***** AVANT LE MONTAGE DU CONTEXTE *****";
		}
		// Parcourt tous les produits ayant une migration à faire
		foreach ( $this->_listIdProduct as $idProduct )
		{
			if ( $this->_debug )
			{
				echo "\n Migration du produit : ".$idProduct;
			}
			// Récupère la connexion sur la base de données
			$db = $this->getConnection($idProduct);

                        // maj 05/07/2010 - MPR : Suppression des doublons sur le champ id_ligne de sys_field_reference
                        $this->cleanSFR($db);

			// 07:51 24/06/2009 GHX
			// Fixe l'id produit dans sys_pauto_config
			// Vu qu'on a qu'un seul produit l'id produit vaut forcement 1
			$db->execute("UPDATE sys_pauto_config SET id_product = 1");
			
			// Exécution de différentes étapes à faire avant de monter le contexte
			$this->updateLabelAlarm($db);
			// 12:03 16/04/2009 GHX
			// Ajout de la suppression des 4 comptes par défaut
			$this->deleteUsers($db, $idProduct);
			$this->deleteProfiles($db, $idProduct);
			$this->replaceIdByName($db);
			$this->deleteNE($db);
			
			// Supprime les éléments Astellia
			$this->deleteElementsAstellia($db, $idProduct);
			
			// 15:06 17/04/2009 GHX
			//$this->checkProfiles($db, $idProduct);
		}
		
		if ( $this->_debug )
		{
			echo "\n***** FIN AVANT LE MONTAGE DU CONTEXTE *****\n\n";
		}
	} // End function beforeMount

	/**
	 * Exécute certains traitement sur les produits venant d'un CB 4.0 et sur lesquels on a monté un contexte
	 *
	 *	14/04/2009 GHX
	 *		- Ajout de l'appel à la fonction pour supprimer les doublons dans les compteurs
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 */
	public function afterMount ()
	{
		if ( $this->_debug )
		{
			echo "\n\n***** ARPES LE MONTAGE DU CONTEXTE *****";
		}
		
		// Parcourt tous les produits ayant une migration à faire
		foreach ( $this->_listIdProduct as $idProduct )
		{
			if ( $this->_debug )
			{
				echo "\n Migration du produit : ".$idProduct;
			}
			// Récupère la connexion sur la base de données
			$db = $this->getConnection($idProduct);
			
			// Exécution de différentes étapes à faire après avoir monté le contexte
			// 17:33 14/04/2009 GHX
			// Ajout de la suppression des compteurs en doublons
			$this->deleteCounters($db);
			
			// 10:52 17/04/2009 GHX
			// Gestion des menus clients
			$this->menuManagement($db, $idProduct);
			
			// 15:07 17/04/2009 GHX
			// Gestion des profils
			//$this->checkProfiles($db, $idProduct);
			$this->profilesManagement($db, $idProduct);
			
			$this->replaceNameById($db);
			
			// 12:03 16/04/2009 GHX
			// Restauration de certaines paramètres des 4 comptes supprimés
			$this->restoreInfoUsersDeleted($db, $idProduct);
		}
		
		if ( $this->_debug )
		{
			echo "\n***** FIN ARPES LE MONTAGE DU CONTEXTE *****\n\n";
		}
	} // End function afterMount

	/**
	 * Création d'une archive qui sauvegarde les tables du contexte avant toutes les modifications
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param string $ctxname nom du contexte
	 */
	private function createBackup ( $ctxname )
	{
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

		$db = $this->getConnection('');

		// Création du répertoire de backup
		$basename = basename($ctxname, '.tar.bz2');
		$dir = "{$this->_directory}backup_before_mount_context_{$basename}/";
		mkdir($dir, 0777);

		if ( $this->_debug )
		{
			echo "\nCréation d'un backup : {$this->_directory}backup_before_mount_context_{$basename}.tar.bz2";
		}
		
		foreach ( $infoAllProducts as $product )
		{
                    // Connexion au produit courant
                    $dbProd = Database::getConnection($product['sdp_id']);            
                    
                    // Création du fichier de backup
                    $filebackup = "{$dir}product_{$product['sdp_id']}.sql";
                    $comment = "
                            --
                            -- Sauvegarde des tables du contexte avant de monter le contexte '".$ctxname."'
                            -- Genere le : ".date('Y-m-d H:i:s')."
                            --
                            -- Produit : ".$product['sdp_label']."
                            -- Identifiant du produit : ".$product['sdp_id']."
                            -- Repertoire : ".$product['sdp_directory']."
                            -- Base de donnees : ".$product['sdp_db_name']."
                            --
                            ";
                    file_put_contents($filebackup, str_replace("\t", "", $comment), FILE_APPEND);

                    // Récupération des tables contexte + sys_versioning
                    // 18/11/2013 GFS - Bug 37891 - [SUP][T&A Gateway][Econet Zimbabwe][AVP 40118][Context] : Backup before mount context is corrupted 
                    $result = $db->execute("SELECT sdct_table FROM sys_definition_context_table WHERE sdct_table != 'sys_definition_topology_replacement_rules'
                        UNION ALL
                        SELECT 'sys_versioning' as sdct_table");                    
                    while ( $row = $db->getQueryResults($result, 1) )
                    {
                        $table = $row['sdct_table'];
                        $str =  "\nTRUNCATE TABLE {$table};\n";
                        file_put_contents($filebackup, $str, FILE_APPEND);

                        // 12/12/2011 BBX
                        // BZ 24572 : utilisation de la méthode dumpTable pour dumper
                        // Afin d'éviter les problème de version de pg_dump
                        file_put_contents($filebackup, $dbProd->dumpTable($table, false, true), FILE_APPEND);
                    }
		}

		// Commande pour aller dans le répertoire $dir
		$cmdCd  = "cd \"{$dir}\"";
		// Commande pour créer l'archive $dir
		$cmdTar = "tar cfj \"../backup_before_mount_context_{$basename}.tar.bz2\" *";
		// Commande pour supprimer le répertoire $dir
		$cmdRm  = "rm -rf \"{$dir}\"";
		// Exécution des commandes pour créer l'archive
		exec("{$cmdCd};{$cmdTar};{$cmdRm}");
		
	} // End function createBackup

	/**
	 * Met "OLD" devant tous les noms des alarmes
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param DataBaseConnection $db connexion à une base de données
	 */
	private function updateLabelAlarm ( $db )
	{
		if ( $this->_debug )
		{
			echo "\n\t- Met 'OLD' devant tous les noms des alarmes statiques, dynamiques et Top/Worst List.";
		}
		$db->execute("UPDATE sys_definition_alarm_static SET alarm_name = 'OLD '|| alarm_name");
		$db->execute("UPDATE sys_definition_alarm_dynamic SET alarm_name = 'OLD '|| alarm_name");
		$db->execute("UPDATE sys_definition_alarm_top_worst SET alarm_name = 'OLD '|| alarm_name");
	} // End function updateLabelAlarm

	/**
	 * Supprime les utilisateurs par défaut :
	 *	- astellia_admin
	 *	- astellia_user
	 *	- client_admin
	 *	- client_user
	 *
	 *	ATTENTION : pour ces 4 comptes, les mots de passes seront réinitialisés.
	 *
	 *	16/04/2009 GHX
	 *		- ajout de la fonction
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param DataBaseConnection $db connexion à une base de données
	 * @param int $idProduct identifiant d'un produit
	 */
	private function deleteUsers ( $db, $idProduct )
	{
		if ( $this->_debug )
		{
			echo "\n\t- Suppression des utilisateurs :";
		}

		// Liste des utilisateurs à supprimer
		$logins =  array('astellia_admin', 'astellia_user', 'client_admin', 'client_user');
		
		foreach ( $logins as $login )
		{
			if ( $this->_debug )
			{
				echo "\n\t\t- login : {$login}";
			}
			
			// Mémorise les informations sur l'utilisateur que l'on supprime
			$this->_infoUsers[$idProduct][$login] = $db->getRow("SELECT * FROM users WHERE login = '{$login}'");
			
			$db->execute("DELETE FROM users WHERE login = '{$login}'");
		}
	} // End function deleteUsers
	
	/**
	 * Supprime les profils
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param DataBaseConnection $db connexion à une base de données
	 * @param int $idProduct identifiant d'un produit
	 */
	private function deleteProfiles ( $db, $idProduct )
	{
		if ( $this->_debug )
		{
			echo "\n\t- Suppression des profils";
		}

		// Récupère les profils
		$this->_infoProfiles[$idProduct] = $db->getAll("SELECT * FROM profile");
		
		// Vide les tables pour la configuration des profils
		$db->execute("TRUNCATE profile");
		$db->execute("TRUNCATE profile_menu_position");
	} // End function deleteProfiles
	
	/**
	 * Restauration de certains paramètres pour les 4 comptes suivants
	 *	- astellia_admin
	 *	- astellia_user
	 *	- client_admin
	 *	- client_user
	 *
	 * On restaure les param-tres suivants ;
	 *	- homepage
	 *	- network_element_preferences
	 *	- on_off
	 *	- user_mail
	 *
	 *	16/04/2009 GHX
	 *		- ajout de la fonction
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param DataBaseConnection $db connexion à une base de données
	 * @param int $idProduct identifiant d'un produit
	 */
	private function restoreInfoUsersDeleted ( $db, $idProduct )
	{
		if ( $this->_debug )
		{
			echo "\n\t- Restauration des informations (homepage/on_off/email/networt preferences) pour les utilisateurs :";
		}
		foreach ( $this->_infoUsers[$idProduct] as $login => $infoUser )
		{
			if ( $this->_debug )
			{
				echo "\n\t\t- login : {$login}";
			}
			
			$oldIdUser = $infoUser['id_user'];
			
			// Récupère le nouvel ID
			$newIdUser = $db->getOne("SELECT id_user FROM users WHERE login = '{$login}'");
			
			// 11:23 18/06/2009 GHX
			// Ajout d'un message de débug
			if ( $this->_debug )
			{
				echo " (old ID : {$oldIdUser} / new ID : {$newIdUser})";
			}
			
			// Restauration des certains paramètres de l'utilisateur
			// On utilise un CASE WHEN pour éviter d'avoir '' comme valeur
			$db->execute("
					UPDATE
						users 
					SET
						homepage = CASE WHEN '{$infoUser['homepage']}' = '' THEN NULL ELSE '{$infoUser['homepage']}' END,
						network_element_preferences = CASE WHEN '{$infoUser['network_element_preferences']}' = '' THEN NULL ELSE '{$infoUser['network_element_preferences']}' END,
						on_off = '{$infoUser['on_off']}',
						user_mail = '{$infoUser['user_mail']}'
					WHERE
						id_user = '{$newIdUser}'
				");
			
			// 11:23 18/06/2009 GHX
			// Ajout d'un message de débug
			if ( $this->_debug )
			{
				echo ($db->getNumRows() > 0 ? ' OK' : ' NOK');
			}
			
			// Modification de l'ancien ID par le nouveau
			// 	-> dans les groupes
			$db->execute("UPDATE sys_user_group SET id_user = '{$newIdUser}' WHERE id_user = '{$oldIdUser}'");
			//	-> dans les schedules
			$db->execute("UPDATE sys_report_sendmail SET mailto = '{$newIdUser}' WHERE mailto = '{$oldIdUser}' AND mailto_type = 'user'");
			//	-> pour l'appartenance des Graph/Dashboard/Rapport
			$db->execute("UPDATE sys_pauto_page_name SET id_user = '{$newIdUser}' WHERE id_user = '{$oldIdUser}'");
			//	-> pour l'envoi des alarmes systemes des connexions
			$db->execute("UPDATE sys_definition_users_per_connection SET sdupc_id_user = '{$newIdUser}' WHERE sdupc_id_user = '{$oldIdUser}'");
		}
	} // End function restoreInfoUsersDeleted
	
	/**
	 * Remplace les identifiants par leur nom
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param DataBaseConnection $db connexion à une base de données
	 */
	private function replaceIdByName ( $db )
	{
		if ( $this->_debug )
		{
			echo "\n\t- Remplace les ID par les noms.";
		}
		
		// Remplace les id des KPI / RAW / GRAPH dans la table sys_pauto_config
		if ( $this->_debug )
		{
			echo "\n\t\t- sys_pauto_config [kpi/raw/graphe/dashboard].";
		}
		/*
			CTT1 25/06/2010
			Correction du BZ 16293
			Si n compteurs répartis dans des familles différentes ont le même nom la migration des id des compteurs pour les graphes n'est pas effectuées car la requête
			exécuté dans la méthode replaceNamebyId retourne n lignes à la place de 1 seule.
			Afin de corriger ce problème pour les compteurs on concatène au nom du compteur le champ edw_group_table.
			Ainsi on se retrouve avec un 'identifiant' temporaire unique.
			J'effectue la même modification sur les KPI par sécurité.
			Cette modification est effectuée dans les méthodes replaceIdByName et replaceNameById
		*/
		/*$db->execute("
			UPDATE
				sys_pauto_config
			SET
				id_elem = CASE
							WHEN class_object = 'counter' THEN
								(SELECT lower(edw_field_name)||'_'||edw_group_table FROM sys_field_reference WHERE id_ligne = id_elem)
							WHEN class_object = 'kpi' THEN
								(SELECT lower(kpi_name)||'_'||edw_group_table FROM sys_definition_kpi WHERE id_ligne = id_elem)
							WHEN class_object = 'graph' THEN
								(SELECT lower(page_name) FROM sys_pauto_page_name WHERE id_page = id_elem)
							WHEN class_object = 'page' THEN
								(SELECT lower(page_name) FROM sys_pauto_page_name WHERE id_page = id_elem)
							ELSE
								id_elem
						END
			");//*/
			/*
				CCT1 29/06/2010
				Si il y a des doublons dans les compteurs (même id sur des famille différente), on affiche la liste des ces compteurs.
			*/
			$result = $db->getAll("
				SELECT SFR1.id_ligne, SFR1.edw_field_name,SFR1.nms_field_name,SFR1.new_date,
					CASE WHEN (SELECT count(*) FROM sys_field_reference_all SFRA WHERE SFRA.nms_field_name=SFR1.nms_field_name) > 0 THEN 'Mapped' ELSE 'Not mapped' END as statut
					FROM sys_field_reference SFR1, sys_field_reference SFR2
					WHERE SFR1.id_ligne=SFR2.id_ligne
					GROUP BY SFR1.id_ligne, SFR1.edw_field_name,SFR1.new_date,SFR1.nms_field_name
					HAVING COUNT(SFR1.id_ligne)>1
					ORDER BY SFR1.id_ligne
			");
			// Si on a des doublons, on affiche la liste
			if ( count($result) > 0 )
			{
				echo "\n\t\t- Duplicates were detected in sys_field_reference (counters), here is the list : ";
				foreach ( $result as $row )
				{
					echo "\n\t\t\t- id_ligne=".$row['id_ligne'].
						"  edw_field_name=".$row['edw_field_name'].
						"  nms_field_name=".$row['nms_field_name'].
						"  activation_date=".$row['new_date'].
						"  has been mapped= ".$row['statut'];
				}
			}

			/*
				CCT1 29/06/2010
				Requête spécifique pour les compteurs,  on ajoute une jointure sur la colonne family de sys_pauto_page_name (qui sera supprimée dans la méthode replaceNameById)
				Cel permet de gérer le cas où l'on a des compteurs avec le même identifiant mais sur des familles différentes.
			*/
			if ( $this->_debug )
			{
				echo "\n\t\t- sys_pauto_config [Replace raw id by raw name and family].";
			}
			$db->execute("
				UPDATE sys_pauto_config
				SET id_elem = (SELECT lower(SFR.edw_field_name)||'_'||SFR.edw_group_table
					FROM sys_field_reference SFR, sys_definition_group_table SDGT, sys_pauto_page_name SPPN
					WHERE SFR.id_ligne = sys_pauto_config.id_elem
						AND SFR.edw_group_table=SDGT.edw_group_table
						AND SPPN.family=SDGT.family
						AND SPPN.id_page=sys_pauto_config.id_page)
				WHERE class_object = 'counter'
			");

			// Requête de replace pour les KPI/graph/dashboards
			if ( $this->_debug )
			{
				echo "\n\t\t- sys_pauto_config [kpi/graphe/dashboard].";
			}
			/*
				CCT1 modif de toutes les query pour gérer le cas où on a des graphe et dashboard avec le même nom qui se font référence
				entre eux avec des droits différents (dash astellia vers un graph user par exemple)
			*/
			$db->execute("
			UPDATE
				sys_pauto_config
			SET
				id_elem = CASE
							WHEN class_object = 'kpi' THEN
								(SELECT lower(kpi_name)||'_'||edw_group_table FROM sys_definition_kpi WHERE id_ligne = id_elem)
							WHEN class_object = 'graph' THEN
								(SELECT lower(SPPN.page_name)||'_graph_'||SPPN.droit
									FROM sys_pauto_page_name SPPN
									WHERE SPPN.id_page=sys_pauto_config.id_elem
									AND SPPN.page_type='gtm')
							WHEN class_object = 'page' THEN
								(SELECT lower(SPPN.page_name)||'_dash_'||SPPN.droit
									FROM sys_pauto_page_name SPPN
									WHERE SPPN.id_page=sys_pauto_config.id_elem
									AND SPPN.page_type='page')
							ELSE
								id_elem
						END
			");

		// Remplace les id des KPI / RAW  dans la table sys_export_raw_kpi_data
		if ( $this->_debug )
		{
			echo "\n\t\t- sys_export_raw_kpi_data [kpi/raw].";
		}
		$db->execute("
			UPDATE
				sys_export_raw_kpi_data
			SET
				raw_kpi_id = CASE
								WHEN raw_kpi_type = 'raw' THEN
									(SELECT lower(edw_field_name) FROM sys_field_reference WHERE id_ligne = raw_kpi_id)
								WHEN raw_kpi_type = 'kpi' THEN
									(SELECT lower(kpi_name) FROM sys_definition_kpi WHERE id_ligne = raw_kpi_id)
							END
			");

		// Remplace les ID des rapports dans la table sys_report_schedule
		if ( $this->_debug )
		{
			echo "\n\t\t- sys_report_schedule [rapport].";
		}
		$result = $db->execute("SELECT schedule_id, report_id FROM sys_report_schedule");
		if ( $db->getNumRows() > 0 )
		{
			while ( $row = $db->getQueryResults($result, 1) )
			{
				$listRapport = array();
				foreach ( explode(',', $row['report_id']) as $id_page )
				{
					// On replace les virgules par des underscore pour ne pas avoir de soucis
					$page_name = $db->getOne("SELECT replace(page_name, ',', '_') FROM sys_pauto_page_name WHERE id_page = '{$id_page}'");
					$listRapport[] = $page_name;
					//$page_name = preg_match('/([^a-zA-Z0-9_])/g', '_', $page_name);
				}
				$db->execute("UPDATE sys_report_schedule SET report_id = '".implode("','",$listRapport)."' WHERE schedule_id = '".$row['schedule_id']."'");
			}
		}
		
		// Homepage
		if ( $this->_debug )
		{
			echo "\n\t\t- sys_global_parameters pour la homepage (parametre : id_homepage).";
		}
		$db->execute("
			UPDATE
				sys_global_parameters
			SET 
				value = (SELECT lower(page_name) FROM sys_pauto_page_name WHERE id_page = value)
			WHERE
				parameters = 'id_homepage'
			");
	} // End function replaceIdByName

	/**
	 * Supprime les niveaux d'aggrégation qui n'ont pas été créé par le client
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param DataBaseConnection $db connexion à une base de données
	 */
	private function deleteNE ( $db )
	{
		if ( $this->_debug )
		{
			echo "\n\t- Supprime les niveaux d'aggrégation de la table sys_definition_network_agregation ou le champ mandatory = 1.";
		}
		$db->execute("DELETE FROM sys_definition_network_agregation WHERE mandatory = 1");
		// Création d'un unique ID pour les niveaux d'aggrégation créés par le client
		$db->execute("UPDATE sys_definition_network_agregation SET sdna_id = 'sdna.'||md5(agregation||family||agregation_level||agregation_rank)");
	} // End function deleteNE

	/**
	 * Vérifie les menus de chaque profile pour vérifier s'il n'y a pas de soucis
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param DataBaseConnection $db connexion à une base de données
	 * @param int $idProduct identifiant d'un produit
	 */
	private function checkProfiles ( $db, $idProduct )
	{
		if ( $this->_debug )
		{
			echo "\n\t- Vérifie chaque profil. Si un ID menu n'existe plus dans la table menu_deroulant_intranet, on le supprime du profil. Si un ID parent n'existe plus dans la table menu deroulant intranet, supprime tous les menus appartenant à l'ID parent.";
		}
		$result = $db->execute("SELECT id_profile FROM profile");
		
		if ( $db->getNumRows() == 0 )
			return;
		
		while ( $row = $db->getQueryResults($result, 1) )
		{
			$p = new ProfileModel($row['id_profile'], $idProduct);
			$p->checkIntegrityMenus();
		}
	} // End function checkProfiles
	
	/**
	 * Supprime les éléments Astellia
	 *	
	 *	- KPI [sys_definition_kpi] qui sont de type customisateur
	 *	- GRAPHES [sys_pauto_page_name, sys_pauto_config, graph_data, graph_information] qui sont de type customisateur
	 *	- DASHBOARDS [sys_pauto_page_name, sys_pauto_config, sys_definition_dashboard] qui sont de type customisateur
	 *	- RAPPORTS [sys_pauto_page_name, sys_pauto_config, sys_definition_selecteur] qui sont de type customisateur
	 *	- MENUS [menu_deroulant_intranet] qui sont de type customisateur
	 *
	 *	17/04/2009 GHX
	 *		- Ajout de la suppression des menus
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param DataBaseConnection $db connexion à une base de données
	 * @param int $idProduct identifiant du produit
	 */
	private function deleteElementsAstellia ( $db, $idProduct )
	{
		if ( $this->_debug )
		{
			echo "\n\t- Supprime les éléments ASTELLIA.";
		}
		
		/*
			1. Supprime les KPI
		*/
		if ( $this->_debug )
		{
			echo "\n\t\t- KPI (sys_definition_kpi).";
		}
		// 11:36 30/09/2009 GHX
		// Correction du BZ 11785
		// Modification de la requete SQL ; ajout des 2 dernières conditions
		$db->execute("DELETE FROM sys_definition_kpi WHERE value_type = 'customisateur' OR value_type = '' OR value_type IS NULL");
		
		/*
			2. Supprime les graphes
		*/
		if ( $this->_debug )
		{
			echo "\n\t\t- Graphe (graph_data, graph_information, sys_pauto_config, sys_pauto_page_name).";
		}
		// Suppression dans la table graph_data
		$db->execute("
				DELETE FROM 
					graph_data 
				WHERE id_data IN (
							SELECT 
								id
							FROM
								sys_pauto_config
							WHERE id_page IN (
										SELECT
											id_page
										FROM
											sys_pauto_page_name 
										WHERE
											droit = 'customisateur'
											AND page_type= 'gtm'
									)
						)
			");
		// Suppression dans la table sys_pauto_config
		$db->execute("
				DELETE FROM
					sys_pauto_config
				WHERE id_page IN (
							SELECT
								id_page
							FROM
								sys_pauto_page_name 
							WHERE
								droit = 'customisateur'
								AND page_type= 'gtm'
						)
			");
		// Suppression dans la table graph_information
		$db->execute("
				DELETE FROM
					graph_information
				WHERE id_page IN (
							SELECT
								id_page
							FROM
								sys_pauto_page_name 
							WHERE
								droit = 'customisateur'
								AND page_type= 'gtm'
						)
			");
		// Suppression dans la table sys_pauto_page_name
		$db->execute("
				DELETE FROM
					sys_pauto_page_name 
				WHERE
					droit = 'customisateur'
					AND page_type= 'gtm'
			");
			
		/*
			3. Supprime les dashboards
		*/
		if ( $this->_debug )
		{
			echo "\n\t\t- Dashboard (sys_pauto_config, sys_definition_dashboard, menu_deroulant_intranet, sys_pauto_page_name).";
		}
		// Suppression dans la table sys_pauto_config
		$db->execute("
				DELETE FROM
					sys_pauto_config
				WHERE id_page IN (
							SELECT
								id_page
							FROM
								sys_pauto_page_name 
							WHERE
								droit = 'customisateur'
								AND page_type= 'page'
						)
			");
		// Suppression dans la table sys_definition_dashboard
		$db->execute("
				DELETE FROM
					sys_definition_dashboard
				WHERE sdd_id_page IN (
							SELECT
								id_page
							FROM
								sys_pauto_page_name 
							WHERE
								droit = 'customisateur'
								AND page_type= 'page'
						)
			");
		// Suppression dans la table sys_definition_dashboard
		$db->execute("
				DELETE FROM
					menu_deroulant_intranet
				WHERE id_page IN (
							SELECT
								id_page
							FROM
								sys_pauto_page_name 
							WHERE
								droit = 'customisateur'
								AND page_type= 'page'
						)
			");
		// Suppression dans la table sys_pauto_page_name
		$db->execute("
				DELETE FROM
					sys_pauto_page_name 
				WHERE
					droit = 'customisateur'
					AND page_type= 'page'
			");
		
		/*
			3. Supprime les rapports
		*/
		if ( $this->_debug )
		{
			echo "\n\t\t- Rapport (sys_pauto_config, sys_definition_selecteur, sys_pauto_page_name).";
		}
		// Suppression dans la table sys_pauto_config
		$db->execute("
				DELETE FROM
					sys_pauto_config
				WHERE id_page IN (
							SELECT
								id_page
							FROM
								sys_pauto_page_name 
							WHERE
								droit = 'customisateur'
								AND page_type= 'report'
						)
			");
		// Suppression dans la table sys_definition_selecteur
		$db->execute("
				DELETE FROM
					sys_definition_selecteur
				WHERE sds_report_id IN (
							SELECT
								id_page
							FROM
								sys_pauto_page_name 
							WHERE
								droit = 'customisateur'
								AND page_type= 'report'
						)
			");
		// Suppression dans la table sys_pauto_page_name
		$db->execute("
				DELETE FROM
					sys_pauto_page_name 
				WHERE
					droit = 'customisateur'
					AND page_type= 'report'
			");
		
		// 10:04 17/04/2009 GHX
		// On supprime tous les menus car on fixe tous les ID en dur
		/*
			4. Supprime les menus
		*/
		if ( $this->_debug )
		{
			echo "\n\t\t- MENUS (menu_deroulant_intranet).";
		}

		// Récupère la liste des menus des clients et les mémorises
                // 07/06/2011 BBX -PARTITIONING-
                // Correction des casts
		$this->_infoMenus[$idProduct] = $db->getAll("
				SELECT DISTINCT 
					id_menu,
					libelle_menu
				FROM
					menu_deroulant_intranet 
				WHERE
					(
						is_profile_ref_user = 1
						AND id_menu_parent = '0'
						AND droit_affichage != 'astellia'
					)
					OR (libelle_menu = 'CLIENT DASHBOARD')
			");
		
		// Supprime tous les menus sauf les menus créés par l'utilisateur et des menus dashboards clients
		$db->execute("
				DELETE FROM
					menu_deroulant_intranet
				WHERE 
					id_page NOT IN (
								SELECT
									id_page
								FROM
									sys_pauto_page_name
								WHERE
									droit = 'client'
									AND page_type = 'page'
							)
					OR id_page IS NULL
					AND id_menu NOT IN (
								SELECT DISTINCT 
									id_menu
								FROM
									menu_deroulant_intranet 
								WHERE
									is_profile_ref_user = 1
									AND id_menu_parent = '0'
									AND droit_affichage != 'astellia'
							)
					AND libelle_menu != 'CLIENT DASHBOARD'
			");
			
		// 07:53 24/06/2009 GHX
		/*
			5. Suppression du RI
		*/
		if ( $this->_debug )
		{
			echo "\n\t\t- RI (graph_data, graph_information).";
		}
		$db->execute("
				DELETE FROM
					graph_data
				WHERE
					data_legend = 'Reliability Indicator'
			");
		$db->execute("
				DELETE FROM
					graph_information
				WHERE
					id_page = '-1'
			");
	} // End function deleteElementsAstellia
	
	/**
	 * Supprime les compteurs de la table sys_field_reference qui sont en doublons dont leur id_ligne est un entier
	 *
	 *	14/04/2009 GHX
	 *		- Ajout de la fonction
	 *	22/04/2009 GHX
	 *		- Modification de la sous-requete pour prendre en compte le nms_table, sinon tous les capture_duration étaient supprimés
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param DataBaseConnection $db connexion à une base de données
	 */
	private function deleteCounters ( $db )
	{
		if ( $this->_debug )
		{
			echo "\n\t- Supprime les compteurs en doublons et dont id_ligne est un entier";
		}
		
		//  22/04/2009 GHX
		// Modification de la sous-requete pour prendre en compte le nms_table, sinon tous les capture_duration étaient supprimés
		$db->execute("
				DELETE FROM 
					sys_field_reference
				WHERE
					id_ligne IN (
						SELECT
							id_ligne
						FROM
							sys_field_reference
						WHERE
							ROW(LOWER(edw_field_name), nms_table) IN (
								SELECT
									LOWER(edw_field_name), nms_table
								FROM
									sys_field_reference
								GROUP BY 
									LOWER(edw_field_name), nms_table
								HAVING 
									COUNT(LOWER(edw_field_name)) > 1
							)
							AND id_ligne ~ '^[0-9]*$' 
					)
			");
	} // End function deleteCounters
	
	/**
	 * Remplace les noms par leur nouveau identifiant
	 *
	 * @author GHX
	 * @version CB5.0.2.2
	 * @since CB4.1.0.00
	 * @param DataBaseConnection $db connexion à une base de données
	 */
	private function replaceNameById ( $db )
	{
		if ( $this->_debug )
		{
			echo "\n\t- Remplace les noms par les ID.";
		}
		
		// Remplace les noms des KPI / RAW / GRAPH dans la table sys_pauto_config
		if ( $this->_debug )
		{
			echo "\n\t\t- sys_pauto_config [kpi/raw/graphe/dashboard].";
		}
		// 17:55 21/12/2009 GHX
		// Ajout de condition sans les sous-requetes
		/*
			CTT1 25/06/2010
			Correction du BZ 16293
			Si n compteurs répartis dans des familles différentes ont le même nom la migration des id des compteurs pour les graphes n'est pas effectuées car la requête
			exécuté dans la méthode replaceNamebyId retourne n lignes à la place de 1 seule.
			Afin de corriger ce problème pour les compteurs on concatène au nom du compteur le champ edw_group_table.
			Ainsi on se retrouve avec un 'identifiant' temporaire unique.
			J'effectue la même modification sur les KPI par sécurité.
			Cette modification est effectuée dans les méthodes replaceIdByName et replaceNameById
		*/
		$db->execute("
			UPDATE
				sys_pauto_config
			SET
				id_elem = CASE
							WHEN class_object = 'counter' THEN
								(SELECT id_ligne FROM sys_field_reference WHERE lower(edw_field_name)||'_'||edw_group_table = id_elem)
							WHEN class_object = 'kpi' THEN
								(SELECT id_ligne FROM sys_definition_kpi WHERE lower(kpi_name)||'_'||edw_group_table = id_elem)
							WHEN class_object = 'graph' THEN
								(SELECT id_page FROM sys_pauto_page_name SPPN
									WHERE lower(SPPN.page_name)||'_graph_'||SPPN.droit = id_elem
									AND page_type='gtm')
							WHEN class_object = 'page' THEN
								(SELECT id_page FROM sys_pauto_page_name SPPN
									WHERE lower(SPPN.page_name)||'_dash_'||SPPN.droit = id_elem
									AND page_type='page')
							ELSE
								id_elem
						END
			WHERE 
				(class_object = 'counter' AND (SELECT count(id_ligne) FROM sys_field_reference WHERE lower(edw_field_name)||'_'||edw_group_table = id_elem) = 1)
				OR (class_object = 'kpi' AND (SELECT count(id_ligne) FROM sys_definition_kpi WHERE lower(kpi_name)||'_'||edw_group_table = id_elem) = 1)
				OR (class_object = 'graph' AND (SELECT count(id_page) FROM sys_pauto_page_name SPPN WHERE lower(SPPN.page_name)||'_graph_'||SPPN.droit = id_elem AND page_type='gtm') = 1)
				OR (class_object = 'page' AND (SELECT count(id_page) FROM sys_pauto_page_name SPPN WHERE lower(SPPN.page_name)||'_dash_'||SPPN.droit = id_elem AND page_type='page') = 1)
			");
		// CCT1 29/06/2010 on supprime la colonne family de la table sys_pauto_config
		$db->execute("
			ALTER TABLE sys_pauto_page_name DROP COLUMN family
		");

		// Remplace les noms des KPI / RAW  dans la table sys_export_raw_kpi_data
		if ( $this->_debug )
		{
			echo "\n\t\t- sys_export_raw_kpi_data [kpi/raw].";
		}
		$db->execute("
			UPDATE
				sys_export_raw_kpi_data
			SET
				raw_kpi_id = CASE
								WHEN raw_kpi_type = 'raw' THEN
									(SELECT id_ligne FROM sys_field_reference WHERE lower(edw_field_name) = raw_kpi_id)
								WHEN raw_kpi_type = 'kpi' THEN
									(SELECT id_ligne FROM sys_definition_kpi WHERE lower(kpi_name) = raw_kpi_id)
							END
			");

		// Remplace les noms des rapports dans la table sys_report_schedule
		if ( $this->_debug )
		{
			echo "\n\t\t- sys_report_schedule [rapport].";
		}
		$result = $db->execute("SELECT schedule_id, report_id FROM sys_report_schedule");
		if ( $db->getNumRows() > 0 )
		{
			while ( $row = $db->getQueryResults($result, 1) )
			{
				$listRapport = array();
				foreach ( explode(',', $row['report_id']) as $page_name )
				{
					$id_page = $db->getOne("SELECT id_page FROM sys_pauto_page_name WHERE replace(page_name, ',', '_') = '{$page_name}'");
					$listRapport[] = $id_page;
					//$page_name = preg_match('/([^a-zA-Z0-9_])/g', '_', $page_name);
				}
				$db->execute("UPDATE sys_report_schedule SET report_id = '".implode("','",$listRapport)."' WHERE schedule_id = '".$row['schedule_id']."'");
			}
		}
		
		// Homepage
		if ( $this->_debug )
		{
			echo "\n\t\t- sys_global_parameters pour la homepage (parametre : id_homepage).";
		}
		$db->execute("
			UPDATE
				sys_global_parameters
			SET 
				value = (SELECT id_page FROM sys_pauto_page_name WHERE lower(page_name) = value)
			WHERE
				parameters = 'id_homepage'
			");
	} // End function replaceNameById
	
	/**
	 * On regarde si le menu client a été ajouté via le contexte
	 * Si c'est le cas, on remplace les id_parent de l'ancien menu par le nouveau et on supprime l'ancien menu
	 *			
	 * Exemple : 
	 *	On a un menu client (avant le montage du contexte) qui s'appelle "DASHBOARDS" et dans le contexte que l'on doit monter
	 *	on a aussi un menu qui s'appelle "DASHBOARDS". Dans ce cas, on récupère l'ID du menu du contexte. Et tous les menus qui appartient au menu déjà
	 *	existant on les met dans le nouveau menu. Ensuite, on supprime l'ancien menu. Ceci dans le but d'éviter d'avoir 2 fois les mêmes menus.
	 *
	 *	17/04/2009 GHX
	 *		- Ajout de la fonction
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param DataBaseConnection $db connexion à une base de données
	 * @param int $idProduct identifiant d'un produit
	 */
	private function menuManagement ( $db, $idProduct )
	{
		foreach ( $this->_infoMenus[$idProduct] as $menu )
		{
			$libelleMenu = $menu['libelle_menu'];
			$oldIdMenu = $menu['id_menu'];
			$newIdMenu = $db->getOne("SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu = '{$libelleMenu}' AND is_profile_ref_user = 1 AND id_menu <> '{$oldIdMenu}'");
				
			if ( $db->getNumRows() > 0 )
			{
				// On met à jour les menus enfants avec le nouvel ID du nouveau menu
				$db->execute("UPDATE menu_deroulant_intranet SET id_menu_parent = '{$newIdMenu}' WHERE id_menu_parent = '{$oldIdMenu}'");
				// Supprime l'ancien menu
				$db->execute("DELETE FROM menu_deroulant_intranet WHERE id_menu = '{$oldIdMenu}'");
			}
		}
	} // End function menuManagement 
	
	/**
	 * Pour le profil de type user, on lui met tous les menus accessibles par défaut. Car le contexte ne lui associe aucun menu. Et tous les utilisateurs de type "user" ce voient affecter ce profil.
	 * Et pour les utilisateurs "admin", on leurs affecte le profil de type admin ajouté par le contexte.
	 * 
	 *	17/04/2009 GHX
	 *		- Ajout de la fonction
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param DataBaseConnection $db connexion à une base de données
	 * @param int $idProduct identifiant d'un produit
	 */
	private function profilesManagement ( $db, $idProduct )
	{
		if ( $this->_debug )
		{
			echo "\n\t- Restauration des profils par défaut";
		}
		
		// Récupère le profil User par défaut
		$profileUser = $db->getRow("SELECT * FROM profile WHERE profile_type = 'user'");
		// Récupère l'ID du profil Admin par défaut
		$newIdProfileAdmin = $db->getOne("SELECT id_profile FROM profile WHERE profile_type = 'admin' AND client_type IS NULL");
		
		// 27/04/2009 GHX
		// Suppression de tous les menus associés au profil
		$db->execute("DELETE FROM profile_menu_position WHERE id_profile = '{$profileUser['id_profile']}'");
		
		// Création d'un profil user par défaut
		$ProfileModel = new ProfileModel($profileUser['id_profile']);
		$ProfileModel->addMenuListToProfile(MenuModel::getUserMenus());
		$ProfileModel->checkMandatoryMenus();
		$ProfileModel->buildProfileToMenu();
		
		// Boucle sur les anciens profils
		// 19/2/2011 MMT bz 15606 ajout des profils clients avec reinitialization des menu sur profiles par default
		foreach ( $this->_infoProfiles[$idProduct] as $oldProfile ) {
			// si profile user astellia default
			// 30/05/2011 MMT bz 15606 reopen, pour certain produits (HPG) le nom dans le contexte est UserProfile, pour d'autre (IU) UserProfile
			if($oldProfile['profile_name'] == 'UserProfile1' || $oldProfile['profile_name'] == 'UserProfile') {
				// Remplace l'ancien par ID par le nouvel ID sur la table user
				$newUserPrflId = $profileUser['id_profile'];
			}// si profile admin astellia default
			else if($oldProfile['profile_name'] == 'Astellia Administrator' || $oldProfile['profile_name'] == 'AdminProfile')// Si c'est un profil admin
			{
				$newUserPrflId = $newIdProfileAdmin;
			}// si profile client
			else {
				//genere nouvel ID au lien d'un numerique
				$newCustomPrflId = generateUniqId('profile');
				$newUserPrflId = $newCustomPrflId;

				// selectionne le profile 'model' admin ou user
				if ( $oldProfile['profile_type'] == 'user' )
				{
						$profileIdSrc = $profileUser['id_profile'];
					} else {
						$profileIdSrc = $newIdProfileAdmin;
				}
				// ajout dans la table profile
				$query = "insert into profile(id_profile,profile_name,profile_to_menu,profile_type,client_type)
							select '$newCustomPrflId','{$oldProfile['profile_name']}',profile_to_menu,profile_type,client_type from profile
							where id_profile = '{$profileIdSrc}'";
				$db->execute($query);
				if ( $this->_debug )
				{
					echo "restore Client profile {$oldProfile['profile_name']} in profile table :\n $query \n  affected rows : {$db->getAffectedRows()} \n";
				}
	
				// ajout dans la table profile_menu_position
				$query = "insert into profile_menu_position(id_profile,id_menu,position,id_menu_parent)
							select '$newCustomPrflId',id_menu,position,id_menu_parent from profile_menu_position
							where id_profile = '{$profileIdSrc}'";
				$db->execute($query);
				if ( $this->_debug )
				{
					echo "restore Client profile {$oldProfile['profile_name']} in profile_menu_position table :\n $query \n  affected rows : {$db->getAffectedRows()} \n";
				}
			}
			//mise a jour de la table user
			$db->execute("UPDATE users SET user_profil = '{$newUserPrflId}' WHERE user_profil = '{$oldProfile['id_profile']}'");
			// Fin de modif :19/2/2011 MMT bz 15606
		}
	} // End function profilesManagement
	
	/**
	 * Retourne la connexion à une base de données d'un produit
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param int $idProduct identifiant du produit sur lequel on doit se connecter
	 * @return DataBaseConnection
	 */
	private function getConnection ( $idProduct )
	{
		if ( !array_key_exists($idProduct, $this->_listDb) )
		{
                        // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::get Connection()
			$this->_listDb[$idProduct] = Database::getConnection($idProduct);
			if ( $this->_debug & 2 )
			{
				$this->_listDb[$idProduct]->setDebug(1);
			}
		}

		return $this->_listDb[$idProduct];
	} // End function getConnection

} // End class ContextMigration
?>