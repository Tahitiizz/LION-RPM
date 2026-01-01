<?php
/*
 	23/11/2009 GHX
		- Ajout de la table menu_deroulant_intranet dans la liste des tables à garder
	26/11/2009 GHX
		- Correction du BZ 13081 [Mixed KPI] : activation mixed kpi ne plus prendre en compte template1 comme sur une installe de base
			-> Modification de la fonction createDatabase()
	30/11/2009 GHX
		- Modification de la fonction createDatabase() pour ajouter la table sur les process compatibles pour la //
	11/12/2009 GHX
		- Modification des paramètres dans sys_global_parameters, on remettant des valeurs par défaut
	11/12/2009 NSE
		- bz 13360 création du menu Mixed KPI à la création du produit de Mixed Kpi
	14/12/2009 NSE
		- bz 13146 création du menu Mixed KPI à la création du produit de Mixed Kpi
	23/03/2010 NSE
		- bz 14826 : on ne crée pas la table sys_definition_corporate
		- sélection pour exclusion des tables de données en corporate en se basant sur old_module
    06/05/2010 MPR  bz 15187 : Ajout de messages erreurs dans le démon (utilisation de displayInDemon)
    17/05/2010 NSE : Installation standard, utilisation d'une variable globale pour les chemins vers les exécutables psql et php (PSQL_DIR et PHP_DIR)
    26/07/2010 OJT : Correction bz16823
    27/07/2010 NSE - bz 17025 : Surcharge opérateur /0
    28/02/2011 NSE bz 17516 : ajout des données des tables du gis dans le produit Mixed Kpi
	09/12/2011 ACS Mantis 837 DE HTTPS support
*/
?>
<?php
/**
 * Cette classe permet de créer un produit Mixed KPI
 *
 * @author GHX
 * @created 09:18 06/10/2009
 * @version CB 5.0.1.00
 * @since CB 5.0.1.00
 */
class CreateMixedKpi
{
	/**
	 * Instance de connexion sur la base de données du master
	 * @var DatabaseConnection
	 */
	private $_dbMaster = null;

	/**
	 * Nom de la base de données du produit Mixed KPI
	 * @var string
	 */
	private $_databaseMK = null;

	/**
	 * Tableau contenant la configuration des process
	 * @var array
	 */
	private $_processMaster = array();

	/**
	 * Tableau d'information sur le produit Mixed KPI
	 * @var array
	 * - 11/12/2009 NSE bz 13360 ajout
	 */
	private $_infoMK;

	/**
	 * Constructeur
	 *
	 * @author GHX
	 */
	public function __construct ()
	{
		$this->_dbMaster = DataBase::getConnection();
	} // End fonction __construct


	/**
	 * Création d'un menu Mixed KPI dans lequel seront mis les dashboards dupliqués
	 *
	 * - 11/12/2009 NSE bz 13360 ajout de la fonction pour création du menu à la création du produit de Mixed Kpi
	 * - 14/12/2009 NSE bz 13146 modification position menu Mixed Kpi
	 *
	 * @author NSE d'après SelectedDashboard::createMenuMixedKPI
	 * @version CB 5.0.2.00
	 * @since CB 5.0.2.00
	 */
	private function createMenuMixedKPI ()
	{
		$productMod = new ProductModel(ProductModel::getIdMixedKpi());
		$this->_infoMK = $productMod->getValues();
		unset($productMod);

		$libelleMenuProduct = $this->_infoMK['sdp_label'];
		$idMenuProduct = 'mdi.'.md5($this->_infoMK['sdp_db_name']);

		$query = "SELECT * FROM menu_deroulant_intranet WHERE id_menu = '{$idMenuProduct}'";
		$result = $this->_dbMaster->getAll($query);

		if ( count($result) == 0 ) // Si le menu Mixed KPI n'existe pas
		{
			// Instanciation d'un menu model
			$MenuModel = new MenuModel(0);
			// Ajout des données menu
			$MenuModel->setValue('niveau','1');
			$MenuModel->setValue('id_menu_parent', '0');
			$MenuModel->setValue('position',$MenuModel->getUserMenuLastPosition()+1);
			$MenuModel->setValue('libelle_menu',$libelleMenuProduct);
			$MenuModel->setValue('largeur',strlen($libelleMenuProduct)*10);
			$MenuModel->setValue('deploiement','0');
			$MenuModel->setValue('hauteur','20');
			$MenuModel->setValue('hauteur','20');
			$MenuModel->setValue('droit_affichage','customisateur');
			$MenuModel->setValue('droit_visible','0');
			$MenuModel->setValue('menu_client_default','0');
			$MenuModel->setValue('is_profile_ref_user','1');
			// Enregistrement du menu produit
			$MenuModel->addMenu($idMenuProduct);

			// NSE bz 13146 modification position menu Mixed Kpi
			// MaJ de la table profile_menu_position pour positionner le menu après les Dash clients
			// on boucle sur tous les profils
			foreach(ProfileModel::getProfiles() as $profil)
			{
				// uniquement pour les profils utilisateurs
				if ( $profil['profile_type'] == 'user' )
				{
					// Récupération des menus utilisateurs
					$userMenus = MenuModel::getRootUserMenus();
					// on récuprère
					$req = "SELECT MAX(position)
								FROM profile_menu_position
								WHERE id_profile='".$profil['id_profile']."'
								AND id_menu != '".$idMenuProduct."'
								AND id_menu IN ('".implode("','",$userMenus)."')";

					$newposition = $this->_dbMaster->getOne($req);

					// on instancie le profil
					$ProfileModel = new ProfileModel($profil['id_profile']);
					// on modifie la position du menu dans le profile
					$ProfileModel->setMenuPosition($newposition+1,$idMenuProduct);
				}
			}
			// NSE bz 13146 fin modif
		}

		$this->_idMenuMK = $idMenuProduct;
	} // End function createMenuMixedKPI

	/**
	 * Stop les process du master. On passe juste à OFF les process
	 *
	 * @author GHX
	 */
	public function stopProcessMaster ()
	{
		$this->_processMaster = $this->_dbMaster->getAll("SELECT * FROM sys_definition_master");
		$this->_dbMaster->execute('UPDATE sys_definition_master SET on_off = 0');
	} // End function stopProcessMaster

	/**
	 * Relance les process du master. On repasse juste à ON les process qui étaient à ON avant l'arrêt des process
	 *
	 * @author GHX
	 */
	public function restartProcessMaster ()
	{
		if ( count($this->_processMaster) > 0 )
		{
			foreach ( $this->_processMaster as $process )
			{
				$this->_dbMaster->execute("UPDATE sys_definition_master SET on_off = ".$process['on_off']." WHERE master_id = ".$process['master_id']);
			}
		}
	} // End function restartProcessMaster

	/**
	 * Création de la base de données
	 *
	 * @author GHX
	 * @return boolean
	 */
	public function createDatabase ()
	{
		include dirname(__FILE__).'/../../php/xbdd.inc';
		
		$databaseMK = 'mixed_kpi';


		// >>>>>>>>>>
		// 15:52 26/11/2009 GHX
		// Correction du BZ 13081
		// On utilise le même principe que pour installe de base pour l'activation du produit Mixed KPI
		exec('env PGPASSWORD='.$APass.' '.PSQL_DIR.'/psql -tc "select procpid from pg_stat_activity where datname=\'template1\'" -U '.$AUser.' | xargs -r kill');
		/*
		$nbOfUseTemplate1 = $this->_dbMaster->getOne("SELECT count(datname) FROM pg_stat_activity WHERE datname = 'template1'");
		// Si on a au moins un utilisateur qui utilise "template1" on ne pourra pas créer la base de données
		if ( $nbOfUseTemplate1 > 0 )
		{
			return false;
		}
		*/
		// <<<<<<<<<<

		/*
			1 : Création de la base de données
		*/
		$resultListMK = $this->_dbMaster->execute("SELECT datname FROM pg_database WHERE datname ILIKE 'mixed_kpi%'");
		if ( $this->_dbMaster->getNumRows() > 0 )
		{
			$listMK = array();
			while ( $dbname = $this->_dbMaster->getQueryResults($resultListMK, 1) )
			{
				$listMK[] = $dbname['datname'];
			}
			$compteur = 1;
			while ( in_array($databaseMK, $listMK) )
			{
				$databaseMK = sprintf('mixed_kpi_%d', $compteur++);
			}
		}

		$this->_dbMaster->execute("CREATE DATABASE {$databaseMK} WITH ENCODING='SQL_ASCII'");

		$lastError = $this->_dbMaster->getLastError();
		if ( !empty($lastError) )
		{
			return false;
		}

		/*
			2 : Création des tables de données
		*/
		// 23/03/2010 NSE sélection des tables de données en corporate
		$queryListTables = "
			SELECT
				tablename
			FROM
				pg_tables
			WHERE
				schemaname = 'public'
				AND tablename NOT LIKE 'pg_%'
				AND tablename LIKE (
							SELECT
								'edw_'||value||'_%'
							FROM
								sys_global_parameters
							WHERE parameters = 'module' AND value<>'def'
							OR parameters = 'old_module'
							LIMIT 1
					)
			ORDER BY tablename
		";

		$allTables = $this->_dbMaster->getAll($queryListTables);

		$modelMaster = new ProductModel(ProductModel::getIdMaster());
		$infoMaster = $modelMaster->getValues();

		$cmd = "env PGPASSWORD={$infoMaster['sdp_db_password']} ".PSQL_DIR."/pg_dump -U {$infoMaster['sdp_db_login']} {$infoMaster['sdp_db_name']} -s ";
		foreach ( $allTables as $table )
		{
			// Exclut les tables de données
			$cmd .= ' -T '.$table['tablename'];
		}
		// 23/03/2010 NSE bz 14826 : on ne crée pas la table sys_definition_corporate
		$cmd .= ' -T sys_definition_corporate';

		$cmd .= " | env PGPASSWORD={$infoMaster['sdp_db_password']} ".PSQL_DIR."/psql -U {$infoMaster['sdp_db_login']} {$databaseMK}";
		exec($cmd);

		// 27/07/2010 NSE bz 17025
		// Installation de l'opérateur / supportant la division par zéro
		$cmd = "env PGPASSWORD={$infoMaster['sdp_db_password']} ".PSQL_DIR."/psql -U {$infoMaster['sdp_db_login']} {$databaseMK} -f ".REP_PHYSIQUE_NIVEAU_0."modules/divparzero/lib/uninstall_divparzero.sql";
		exec($cmd);
		$cmd = "env PGPASSWORD={$infoMaster['sdp_db_password']} ".PSQL_DIR."/psql -U {$infoMaster['sdp_db_login']} {$databaseMK} -f ".REP_PHYSIQUE_NIVEAU_0."modules/divparzero/lib/divparzero.sql";
		exec($cmd);

		/*
			3 : Copie des données à recuperer
		*/
		// 14:54 23/11/2009 GHX
		// Ajout de la table menu_deroulant_intranet
                // 24/11/2010 BBX
                // Ajout de 'sys_pauto_family'
                // BZ 17911
                // 28/02/2011 NSE bz 17516 : ajout des tables du gis
		$tablesData = array(
				'sys_debug',
				'sys_definition_context',
				'sys_definition_context_dependency',
				'sys_definition_context_table',
				'sys_definition_context_table_key',
				'sys_definition_context_table_link',
				'sys_definition_master',
				'sys_definition_master_ref',
				'sys_definition_family',
				'sys_definition_step',
				'sys_definition_messages_display',
				'sys_global_parameters',
				'sys_global_parameters_categories',
				'sys_definition_time_agregation',
				'edw_object_ref_header',
				'sys_pauto_univers',
                                'sys_pauto_family',
				'sys_aa_interface',
				'menu_deroulant_intranet',
				'profile',
				'profile_menu_position',
				'sys_user_group',
				'users',
				'sys_definition_sa_config',
				'sys_definition_product',
				'sys_versioning',
				'sys_gis_config',
				'sys_gis_config_global',
				'sys_gis_config_palier',
				'sys_gis_config_vecteur',
				'sys_gis_data_polygon',
                                'spatial_ref_sys',
				'sys_definition_master_compatibility' // Ajout de la table concernant la parallélisation des process
			);

		$cmd = "env PGPASSWORD={$infoMaster['sdp_db_password']} ".PSQL_DIR."/pg_dump -U {$infoMaster['sdp_db_login']} {$infoMaster['sdp_db_name']} -a ";
		foreach ( $tablesData as $table )
		{
			$cmd .= ' -t '.$table;
		}
		$cmd .= " | env PGPASSWORD={$infoMaster['sdp_db_password']} ".PSQL_DIR."/psql -U {$infoMaster['sdp_db_login']} {$databaseMK}";
		exec($cmd);

		/*
			4 : Ajout du produit pour pouvoir récupérer facilement le nom de la base de données
		*/
		// 09/12/2011 ACS Mantis 837 DE HTTPS support
		$modelMaster = new ProductModel(ProductModel::getIdMaster());
		$infoMaster = $modelMaster->getValues();

		$productMK = new ProductModel('');

		$productMK->setValue('sdp_label', 'Mixed KPI');
		$productMK->setValue('sdp_ip_address', $infoMaster['sdp_ip_address']);
		$productMK->setValue('sdp_directory', $infoMaster['sdp_directory'].'/mixed_kpi_product');
		$productMK->setValue('sdp_db_name', $databaseMK);
		$productMK->setValue('sdp_db_port', $infoMaster['sdp_db_port']);
		$productMK->setValue('sdp_db_login', $infoMaster['sdp_db_login']);
		$productMK->setValue('sdp_db_password', $infoMaster['sdp_db_password']);
		$productMK->setValue('sdp_ssh_user', $infoMaster['sdp_ssh_user']);
		$productMK->setValue('sdp_ssh_password', $infoMaster['sdp_ssh_password']);
		$productMK->setValue('sdp_ssh_port', $infoMaster['sdp_ssh_port']);
		$productMK->setValue('sdp_on_off', 1);
		$productMK->setValue('sdp_master', 0);
		$productMK->setValue('sdp_master_topo', 0);
		$productMK->setValue('sdp_http', $infoMaster['sdp_http']);
		$productMK->setValue('sdp_https', $infoMaster['sdp_https']);
		$productMK->setValue('sdp_https_port', $infoMaster['sdp_https_port']);

		$productMK->addProduct();

		ProductModel::deployProducts();

		$this->_databaseMK = $databaseMK;

                /*
                 * 5: Mise à jour de sys_definiton_master* dans le cadre d'un
                 * MixedKPI créé depuis un Produit Blanc
                 */
                if ( ProductModel::isBlankProduct( ProductModel::getIdMaster() ) )
                {
                    $dbCon = Database::getConnection( ProductModel::getIdMixedKpi() );
                    $dbCon->execute( "DELETE FROM sys_definition_master WHERE master_name='Report Sender';" );
                    $dbCon->execute( "DELETE FROM sys_definition_master_ref WHERE master_name='Report Sender';" );
                }

		// 11/12/2009 NSE bz 13360 création du menu
		$this->createMenuMixedKPI();

		return true;
	} // End function createDatabase

	/**
	 * Supprime la base Mixed KPI
	 *
	 * @author GHX
	 */
	public function dropDatabase ()
	{
		$this->_dbMaster->execute("CREATE DATABASE {$databaseMK}");
	} // End function dropDatabase

	/**
	 * Installation d'une partie via les crons afin d'avoir les droits astellia
	 *
	 * @author GHX
	 */
	public function installViaCron ()
	{
		$this->_dbMaster->execute("INSERT INTO sys_crontab (script,famille,master) VALUES ('mixed_kpi/php/install_via_cron.php', 26, 10)");

		$return = "true";
		$continue = true;
		$start = time()+5*60; // Temps maximum d'attente avant de considérer que l'installe a plantée
		do
		{
			sleep(1);
			if ( file_exists(REP_PHYSIQUE_NIVEAU_0.'upload/cronmixedkpi.txt') )
			{
				$r = file(REP_PHYSIQUE_NIVEAU_0.'upload/cronmixedkpi.txt');
				if ( count($r) > 0)
				{
					if ( $r[0] == 1 )
					{
						$continue = false;
						$return = "true";
					}
					elseif ( $r[0] == 2 )
					{
						$continue = false;
						$return = "Cannot install Mixed KPI product. Please contact you administrator";
					}
				}
			}
			if ( time() > $start )
			{
				$continue = false;
			}
		} while ( $continue );

		$unlink = @unlink(REP_PHYSIQUE_NIVEAU_0.'upload/cronmixedkpi.txt');
		if( !$unlink )
                {
                    $return = "Cannot find crontab. Please contact your administrator";
                    // Correction du bz - Installation d'un Mixed KPI en double
                    $this->_dbMaster->execute("DELETE FROM sys_crontab WHERE script = 'mixed_kpi/php/install_via_cron.php'");
                }
		return $return;
	} // End function installViaCron

	/**
	 * Configure certaines tables et fichiers PHP du produit Mixed KPI
	 *
	 * @author GHX
	 */
	public function configure ()
	{
		/*
			Modification de la table sys_global_parameters
		*/
		$dbMK = DataBase::getConnection(ProductModel::getIdMixedKpi());
		$dbMK->execute("UPDATE sys_global_parameters SET value = 'Trending&Aggregation Mixed KPI' WHERE parameters = 'product_name' ");
		$dbMK->execute("UPDATE sys_global_parameters SET value = 'def' WHERE parameters = 'module' ");
		// 25/03/2010 BBX : suppression de "old_module" si existant
		$dbMK->execute("DELETE FROM sys_global_parameters WHERE parameters = 'old_module'");
		$dbMK->execute("UPDATE sys_global_parameters SET value = NULL WHERE parameters = 'key' ");
		// 11:21 11/12/2009 GHX
		// On redéfinit les valeurs par défaut pour les paramètre suivants
		$dbMK->execute("UPDATE sys_global_parameters SET value = 1 WHERE parameters = 'retrieve_delete_file' ");
		$dbMK->execute("UPDATE sys_global_parameters SET value = 0 WHERE parameters = 'retrieve_search_directory' ");

		/*
			Désactivation de la step key_management.php
		*/
		$dbMK->execute("UPDATE sys_definition_step SET on_off = 0 WHERE script = '/scripts/key_management.php'");

		/*
			Vide sys_versioning pour ne garder que la partie CB
		*/
		$dbMK->execute("DELETE FROM sys_versioning WHERE item NOT IN ('cb_name', 'cb_version')");

		/*
			Création de la table sys_definition_mixedkpi
		*/
		$dbMK->execute("
				CREATE TABLE sys_definition_mixedkpi
				(
				  sdm_id text,
				  sdm_sdp_id integer,
				  sdm_family text
				)
				WITH (
				  OIDS=TRUE
				);
			");

                /*
                        23/09/2010 BBX
                        On passe la mise à jour de la topologie en auto
                        BZ 17517
                 */
                $dbMK->execute("UPDATE sys_global_parameters
                    SET value = '1' WHERE parameters = 'topology_auto'");
                
                // 20/08/2012 BBX
                // BZ 28452 : correction du dossier de topology auto sur le MK
                $dbMK->execute("UPDATE sys_global_parameters
                    SET value = '".REP_PHYSIQUE_NIVEAU_0."mixed_kpi_product/topology/' WHERE parameters = 'topology_file_location'");
	} // End function configure

	/**
	 * Création du répertoire mixed_kpi_product dans le répertoire de l'application du master
	 * ATTENTION : Cette fonction est lancé via une cron
	 *
	 * @author GHX
	 */
	public function createDirectory ()
	{
                /*
                * 1 : Création du répertoire
                */
                // Création du répertoire mixed_kpi_product
                $error = 0;
                exec('mkdir '.REP_PHYSIQUE_NIVEAU_0.'mixed_kpi_product',$r,$error);
                if($error){
                    displayInDemon("Impossible de créer le répertoire ".REP_PHYSIQUE_NIVEAU_0."mixed_kpi_product","alert");
                }

		// Copie des ficheirs du master.
		// Si la commande rsync existe on l'utilise car plus rapide sinon on fait un simple cp car on peut exclure le contenu de certains répertoires
		$rsync = exec('which rsync');
		if ( !empty($rsync) )
		{
			// -r		visite récursive des répertoires
			// -p		préserve les permissions
			// -o		préserve le propriétaire
			// -g		préserve le groupe
                        // 21/08/2012 BBX
                        // BZ 26480 : compatibility with redhat 6.2
			$cmdRsync = "$rsync -a --exclude='/topology/*' --exclude='/report_files/*' --exclude='/flat_file/*' --exclude='/file_archive/*' --exclude='/png_file/*' --exclude='/flat_file_upload_archive/*' --exclude='/file_demon/*' --include='/upload/*/' --exclude='/upload/*/*' --exclude='/upload/*' --exclude='/mixed_kpi_product' ".REP_PHYSIQUE_NIVEAU_0."* ".REP_PHYSIQUE_NIVEAU_0."mixed_kpi_product/";
			$error = 0;
                        exec($cmdRsync,$r,$error);
                        if($error == 1)
                        {
                             displayInDemon("Impossible de copier les sources","alert");
		        }
		}
		else
		{
			// Copie des fichiers du master dans le répertoire du produit Mixed KPI
			$cmdCPFiles = 'cp -Rp '.REP_PHYSIQUE_NIVEAU_0.'* '.REP_PHYSIQUE_NIVEAU_0.'mixed_kpi_product';
			exec($cmdCPFiles);

			// Liste des répertoires dont on supprime le contenu
			$listRepDel = array (
				'png_file',
				'upload',
				'file_demon',
				'file_archive',
				'flat_file',
				'flat_file_upload_archive',
				'report_files',
				'topology'
			);

			foreach ( $listRepDel as $del )
			{
				$cmdDel = 'find '.REP_PHYSIQUE_NIVEAU_0.'mixed_kpi_product/'.$del.'/ -type f -exec rm -f {} \;';
				exec($cmdDel);
			}
		}

		/*
         * 2 : Modification du fichier xenv.inc
         * 26/07/2010 OJT : Correction BZ 16823
		 */
		$error = 0;
        $n0 = str_replace('/', '\/', NIVEAU_0);
		$cmdXenv = 'sed -i "s/'.$n0.'/'.$n0.'mixed_kpi_product\//" '.REP_PHYSIQUE_NIVEAU_0.'mixed_kpi_product/php/xenv.inc';
		exec( $cmdXenv, $r, $error );
        if($error == 1){
            displayInDemon("Modification du fichier xenv.inc sur $n0 impossible","alert");
        }

		/*
         * 3 : Modification du fichier xbdd.inc
		 */
        $error = 0;
		$modelMaster = new ProductModel(ProductModel::getIdMaster());
		$infoMaster = $modelMaster->getValues();
		$DBName = $infoMaster['sdp_db_name'];
		$modelMK = new ProductModel(ProductModel::getIdMixedKpi());
		$infoMK = $modelMK->getValues();
		$NewDBName = $infoMK['sdp_db_name'];
		$cmdXbdd = 'sed -i "s/'.$DBName.'/'.$NewDBName.'/" '.REP_PHYSIQUE_NIVEAU_0.'mixed_kpi_product/php/xbdd.inc';
		exec($cmdXbdd,$r,$error);
        if($error == 1){
            displayInDemon("Modification du fichier xbdd.inc impossible","alert");
        }
	} // End function createDirectory

	/**
	 * Création des crons ASTELLIA pour le produit Mixed KPI
	 * ATTENTION : Cette fonction est lancé via une cron
	 *
	 * @author GHX
	 */
	public static function createCron ()
	{
            // Copie les crons dans un fichier temporaire
            $cmdCopyCrontab = 'crontab -l >> '.REP_PHYSIQUE_NIVEAU_0.'upload/tmpCrontab && echo "" >> '.REP_PHYSIQUE_NIVEAU_0.'upload/tmpCrontab';
            exec($cmdCopyCrontab);

            // Ajout à ce fichier les crons pour le produit Mixed KPI
            $rep = str_replace('/', '\/', REP_PHYSIQUE_NIVEAU_0);
            $repMK = str_replace('/', '\/', REP_PHYSIQUE_NIVEAU_0.'mixed_kpi_product/');

            // On supprime l'éventuel report_sender_bp.php provenant d'un Produit Blanc
            // 16/10/2013 GFS - Bug 37173 - [SUP][5.3.0.12][SMART] [Mixed-KPI] Raw and KPI values are multiplied in mixed-KPI application
            // 13/12/2012 BBX BZ 30942 : On supprime l'éventuel clean_files.php provenant d'un Produit Blanc
            $cmdCreateCronMK = 'crontab -l | grep "'.REP_PHYSIQUE_NIVEAU_0.'" | grep -v "report_sender_bp.php" | grep -v "clean_files.php" | sed "s/'.$rep.'/'.$repMK.'/g" >> '.REP_PHYSIQUE_NIVEAU_0.'upload/tmpCrontab && echo "" >> '.REP_PHYSIQUE_NIVEAU_0.'upload/tmpCrontab';
            exec($cmdCreateCronMK);
            
            // 13/12/2012 BBX
            // 16/10/2013 GFS - Bug 37173 - [SUP][5.3.0.12][SMART] [Mixed-KPI] Raw and KPI values are multiplied in mixed-KPI application
            // BZ 30942 : On supprime l'éventuel clean_files.php provenant d'un Produit Blanc
            //$cmdCreateCronMK = 'crontab -l | grep "'.REP_PHYSIQUE_NIVEAU_0.'" | grep -v "clean_files.php" | sed "s/'.$rep.'/'.$repMK.'/g" >> '.REP_PHYSIQUE_NIVEAU_0.'upload/tmpCrontab && echo "" >> '.REP_PHYSIQUE_NIVEAU_0.'upload/tmpCrontab';
            //exec($cmdCreateCronMK);

            // Dans le cas d'un Master Produit Blanc, les crons ne sont pas
            // bonnes pour un produit intégrant des données, on les modifie
            exec( 'sed -i -re "s@#*(.*'.REP_PHYSIQUE_NIVEAU_0.'mixed_kpi_product/.*)@\1@" '.REP_PHYSIQUE_NIVEAU_0."upload/tmpCrontab" );

            // Recopie le fichier temporaire dans le fichier de cron
            $error = 0;
            $cmdCrontab = 'crontab < '.REP_PHYSIQUE_NIVEAU_0.'upload/tmpCrontab';
            exec($cmdCrontab,$r,$error);
            if($error == 1)
                displayInDemon("Création des cron Mixed KPI impossible","alert");

            // Suppression du fichier temporaire (via unlink)
            unlink( REP_PHYSIQUE_NIVEAU_0.'upload/tmpCrontab' );
	} // End function createCron

	/**
	 * Activation du produit Mixed KPI via la cron root
	 *ATTENTION : Cette fonction est lancé via une cron
	 *
	 * @author GHX
	 */
	public function installViaCronRoot ()
	{
		copy(REP_PHYSIQUE_NIVEAU_0.'scripts/kill_qpid.php', REP_PHYSIQUE_NIVEAU_0.'scripts/kill_qpid.php.save');
		copy(dirname(__FILE__).'/../php/install_restart_cron.php', REP_PHYSIQUE_NIVEAU_0.'scripts/kill_qpid.php');

		$continue = true;
		$start = time()+4.5*60; // Temps maximum d'attente avant de considérer que l'installe a plantée
		do
		{
			sleep(1);
			if ( file_exists(REP_PHYSIQUE_NIVEAU_0.'upload/cronmixedkpi.txt') )
			{
				$r = file(REP_PHYSIQUE_NIVEAU_0.'upload/cronmixedkpi.txt');
				if ( count($r) > 0)
				{
					if ( $r[0] != 0 )
					{
						$continue = false;
					}
				}
			}
			if ( time() > $start )
			{
				$continue = false;
			}
		} while ( $continue );
	} // End function installViaCronRoot
} // End class CreateMixedKpi
?>