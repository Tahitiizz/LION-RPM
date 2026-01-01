<?php
/**
 * 
 * @cb5100@
 *
 *	17/05/2010 NSE : Installation standard, utilisation d'une variable globale pour les chemins vers les exécutables psql et php (PSQL_DIR et PHP_DIR)
 *  28/07/2010 OJT : Suppression de la constante PHP_DIR (bz17016)
 *  01/08/2011 NSE bz 22060 : initialisation de use_prefix à 1 en mode corporate
 * 11/10/2011 NSE DE Bypass temporel : ajout d'une ligne pour générer le Data Export de la Ta Bypassée
 * 18/12/2012 GFS - BZ#29752 - SQL errors when saving a slave
 */
?>
<?php
/**
*	Classe permettant de configurer un corporate
*	Travaille sur les tables sys_definition_corporate, sys_definition_network_agregation, sys_definition_time_agregation, sys_definition_categorie, sys_definition_group_table_time, sys_definition_gt_axe, sys_definition_master, sys_definition_master_ref, sys_definition_group_table, sys_definition_flat_file_lib, sys_link_filetype_grouptable
*
*	@author	BBX - 08/08/2009
*	@version	CB 5.0.1.00
*	@since	CB 5.0.1.00
*
*	27/10/2009 BBX :
*		- Correction de la fonction sendDataExport dans le cas d'un envoie FTP par SFTP.  BZ 12289
*
*	06/11/2009 BBX :
*		- Ajout de la fonction buildGroupTableTime. BZ 12578
*		- Désactivation de la TA hour si on passe en day. BZ 12578
*	18/12/2009 GHX :
*		- Quand on masque le compute laucnher hourly on le désactive (on_off = 0) pour ne pas avoir de problème
*		- Quand le corpo est basé sur DAY on passe le compute mode en daily et si HOUR en hourly
*	16/03/2010 BBX :
*		- Modification de l'incrémentation booléene de la variable $execCtrl dans la fonction activate().
*	23/03/2010 NSE bz 14810
*		- Désactivation du GIS à l'activation du corporate
 *      22/09/2010 NSE bz 18117 : initialisation du mode de connexion actif/passif
 * 04/05/2011 NSE bz 22040 : configuration uniq_label perdue lors de la mise à jour des NA : ajout de uniq_label dans la requête de mise à jour de la famille
*/
class CorporateModel
{
	/*
	*	Constantes
	*/
	
	// Table de config des data export
	const CONFIG_TABLE = 'sys_definition_corporate';

	// Table de config des éléments réseau
	const NETWORK_AGG_TABLE = 'sys_definition_network_agregation';

	// Table de config des éléments temporels
	const TIME_AGG_TABLE = 'sys_definition_time_agregation';
	
	// Table de config des éléments réseau (sauvegarde)
	const NETWORK_AGG_BCKP_TABLE = 'sys_definition_network_agregation_bckp';
	
	// Table de config des éléments temporels
	const TIME_AGG_BCKP_TABLE = 'sys_definition_time_agregation_bckp';
	
	// Table de configuration des familles
	const FAMILY_TABLE = 'sys_definition_categorie';
	
	// Table de configuration des niveaux temporels
	const TIME_AGG_CONFIG_TABLE = 'sys_definition_group_table_time';
	
	// Table de configuration des group table
	const GT_AXE_TABLE = 'sys_definition_gt_axe';
	
	// Table de configuration des tables de données
	const GT_TABLE = 'sys_definition_group_table';
	
	// Table des processus de référence
	const MASTER_REF_TABLE = 'sys_definition_master_ref';
	
	// Table des processus
	const MASTER_TABLE = 'sys_definition_master';
	
	// Table sys_definition_flat_file_lib
	const FLAT_FILE_TABLE = 'sys_definition_flat_file_lib';
	
	// Table sys_link_filetype_grouptable
	const FILETYPE_LINK_TABLE = 'sys_link_filetype_grouptable';
	
	const GLOBAL_PARAMETERS = 'sys_global_parameters';
	
	/*
	*	Variables
	*/
	public static $database;
	public static $product;
	public static $affiliateFailed = '';
        public static $errorMsg = '';
	

	/*
	*	Méthodes statiques
	*/
	
	/**
	*	Active le mode corporate
	*	@return bool : vrai si activation réussie
	**/		
	public static function activate($product='')
	{
		// Instanciation de DatabaseConnection
		// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::get Connection()
                self::$database = Database::getConnection($product);
		
		// On mémorise l'id produit
		self::$product = $product;
			
		// Current module
		$currentModule = get_sys_global_parameters('module');

		// BEGIN
		self::$database->execute('BEGIN');
			
		// 1) Sauvegarde des tables sys_definition_network_agregation et sys_definition_time_agregation
		$query = "DROP TABLE IF EXISTS ".self::NETWORK_AGG_BCKP_TABLE;
		$execCtrl = (!self::$database->execute($query) ? false : true);		
		$query = "DROP TABLE IF EXISTS ".self::TIME_AGG_BCKP_TABLE;
		$execCtrl = $execCtrl && (!self::$database->execute($query) ? false : true);
		$query = "SELECT * INTO ".self::NETWORK_AGG_BCKP_TABLE." FROM ".self::NETWORK_AGG_TABLE;
		$execCtrl = $execCtrl && (!self::$database->execute($query) ? false : true);
		$query = "SELECT * INTO ".self::TIME_AGG_BCKP_TABLE." FROM ".self::TIME_AGG_TABLE;
		$execCtrl = $execCtrl && (!self::$database->execute($query) ? false : true);
		
		// 2) Création de la table sys_definition_corporate
		$query = "SELECT * FROM pg_tables 
		WHERE schemaname = 'public' 
		AND tablename = '".self::CONFIG_TABLE."'";
		self::$database->execute($query);
		if(self::$database->getNumRows() == 0)
		{
			$query = "CREATE TABLE ".self::CONFIG_TABLE." 
			(
				id_group_table int,
				na_min text,
				na_min_axe3 text,
				ta_min text,
				super_network text,
				export_raw smallint DEFAULT 1,
				export_kpi smallint DEFAULT 0
			)";
			$execCtrl = $execCtrl && (!self::$database->execute($query) ? false : true);
		}
		
		// 3) Remplissage de la table avec la configuration actuelle (contexte produit)
		$query = "TRUNCATE ".self::CONFIG_TABLE;
		$execCtrl = $execCtrl && (!self::$database->execute($query) ? false : true);
		$query = "SELECT 
			gt.id_group_table AS id_group_table, 
			c.network_aggregation_min::text AS na_min, 
			n.agregation::text AS na_axe3_min, 
			t.time_agregation::text AS ta_min,
			NULL::text AS super_network
		FROM 
			".self::FAMILY_TABLE." c, 
			".self::TIME_AGG_CONFIG_TABLE." t, 
			".self::GT_AXE_TABLE." gt
		LEFT JOIN ".self::NETWORK_AGG_TABLE." n 
			ON (n.family = gt.family AND axe = 3 AND agregation_level = 1)
		WHERE 
			c.family = gt.family
			AND t.id_source = -1
			AND t.data_type = 'raw'
			AND t.id_group_table = gt.id_group_table";
		$result = self::$database->execute($query);
		$execCtrl = $execCtrl && (!$result ? false : true);		
		while($array = self::$database->getQueryResults($result,1)) {
			$queryInsert = "INSERT INTO ".self::CONFIG_TABLE."
			(id_group_table,na_min,na_min_axe3,ta_min,super_network)
			VALUES (
				".$array['id_group_table'].",
				'".$array['na_min']."',
				".(empty($array['na_axe3_min']) ? "NULL" : "'".$array['na_axe3_min']."'").",
				'".$array['ta_min']."',
				".(empty($array['super_network']) ? "NULL" : "'".$array['super_network']."'").")";
			$execCtrl = $execCtrl && (!self::$database->execute($queryInsert) ? false : true);
		}
		
		// 4) On switch vers le parser DEF
		$query = "DELETE FROM sys_global_parameters
		WHERE parameters = 'old_module'";
		$execCtrl = $execCtrl && (!self::$database->execute($query) ? false : true);
		$query = "INSERT INTO sys_global_parameters
		(parameters,value,configure,client_type,label,comment,category,order_parameter)
		VALUES ('old_module',(SELECT value FROM sys_global_parameters WHERE parameters = 'module'),0,'customisateur',NULL,NULL,NULL,NULL)";
		$execCtrl = $execCtrl && (!self::$database->execute($query) ? false : true);
		$query = "UPDATE sys_global_parameters
		SET value = 'def' WHERE parameters = 'module'";
		$execCtrl = $execCtrl && (!self::$database->execute($query) ? false : true);
		
		// 5) Modification du répertoire de topologie auto
		// On commence par récupérer le répertoire de l'application
		$Product = new ProductModel(self::$product);
		$ProductConfig = $Product->getValues();
		$directory = REP_PHYSIQUE_NIVEAU_0;
		if($ProductConfig['sdp_master'] != '1')
			$directory = '/home/'.$ProductConfig['sdp_directory'].'/';
		// On met à jour la base		
		$query = "UPDATE sys_global_parameters
		SET value = '".$directory."topology/' WHERE parameters = 'topology_file_location'";
		$execCtrl = $execCtrl && (!self::$database->execute($query) ? false : true);
		
		// 6) activation de l'automatic mapping
		$query = "UPDATE ".self::FAMILY_TABLE." SET automatic_mapping = 1";
		$execCtrl = $execCtrl && (!self::$database->execute($query) ? false : true);
		
		// 7) activation de l'upload auto de la topo
		$query = "UPDATE sys_global_parameters
		SET value = '1' WHERE parameters = 'topology_auto'";
		$execCtrl = $execCtrl && (!self::$database->execute($query) ? false : true);

		// 8) Modification du product name
		$query = "UPDATE sys_global_parameters
		SET value = value || ' Corporate' WHERE parameters = 'product_name'";
		$execCtrl = $execCtrl && (!self::$database->execute($query) ? false : true);

		// 23/03/2010 NSE bz 14810
		// 9) Désactivation du GIS
		$query = "UPDATE sys_global_parameters
		SET value = 0 WHERE parameters = 'gis' OR parameters = 'gis_alarm'";
		$execCtrl = $execCtrl && (!self::$database->execute($query) ? false : true);
		
		// Clean Tables Structure
		$execCtrl = $execCtrl && (!self::cleanTablesStructure() ? false : true);		

		// Test de la variable de contrôle. Si une des requêtes précédente à échouée, on Rollback. Sinon on commit.
		$execCtrl ? self::$database->execute('COMMIT'): self::$database->execute('ROLLBACK');

		// Création des images
		self::buildProductImages($currentModule);

		// Retour du résultat de l'activation
		return $execCtrl;
	}

	/**
	*	Lance un clean tables structure
	**/		
	public function cleanTablesStructure()
	{
		// Variable de contrôle
		$execCtrl = true;
		// Récupération des infos du produit
		$Product = new ProductModel(self::$product);
		$ProductConfig = $Product->getValues();
                // 10/02/2011 BBX
                // On n'utilise SSH que dans le cas d'un serveur distant
                // BZ 20644
		if($ProductConfig['sdp_master'] != '1' && ($ProductConfig['sdp_ip_address'] != get_adr_server()))
		{			
			$SSH = new SSHConnection($ProductConfig['sdp_ip_address'], $ProductConfig['sdp_ssh_user'], $ProductConfig['sdp_ssh_password'],$ProductConfig['sdp_ssh_port']);
			try {
				$SSH->exec( 'php -q /home/'.$ProductConfig['sdp_directory'].'/scripts/clean_tables_structure.php');
			}
			catch (Exception $e) {
				$execCtrl = false;
			}			
		}
		else {
			exec( 'php -q '.REP_PHYSIQUE_NIVEAU_0.'/scripts/clean_tables_structure.php',$result,$error);
			$execCtrl = ($error == '0') ? true : false;
		}
		// Retourn	
		return $execCtrl;
	}

	/**
	*	Création des images du produit
	*	@param string : module produit
	**/		
	public function buildProductImages($currentModule)
	{
		// Traitement uniquement sur le master
		$Product = new ProductModel(self::$product);
		$ProductConfig = $Product->getValues();
		if($ProductConfig['sdp_master'] == '1')
		{
			/* Image 1 : titre.gif */
			// On récupère le calque
			$calque = imagecreatefromgif(REP_PHYSIQUE_NIVEAU_0.'parser/def/images/corporate_accueil.gif');
			$details_src = getimagesize(REP_PHYSIQUE_NIVEAU_0.'parser/def/images/corporate_accueil.gif');
			// On récupère l'image produit
			$image = imagecreatefromgif(REP_PHYSIQUE_NIVEAU_0.'parser/'.$currentModule.'/images/titre.gif');
			$x = imagesx($image); //on récupère la largeur
			$y = imagesy($image); //on récupère la hauteur
			// Fusionnnnnnnnnn !
			imagecopyresampled($image,$calque,50,($y-$details_src[1]-10),0,0,$details_src[0],$details_src[1],$details_src[0],$details_src[1]);
			imagegif($image,REP_PHYSIQUE_NIVEAU_0.'parser/def/images/titre.gif');
			imagedestroy($image);
			
			/* Image 2: logo_product.png */
			// On récupère le calque
			$calque = imagecreatefrompng(REP_PHYSIQUE_NIVEAU_0.'parser/def/images/corporate_bandeau.png');
			$details_src = getimagesize(REP_PHYSIQUE_NIVEAU_0.'parser/def/images/corporate_bandeau.png');
			// On récupère l'image produit
			$image = imagecreatefrompng(REP_PHYSIQUE_NIVEAU_0.'parser/'.$currentModule.'/images/logo_product.png');
			imagealphablending($image,false);
			$transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
			imagefill($image, 0, 0, $transparent);
			imagesavealpha($image,true);
			imagealphablending($image, true);
			$x = imagesx($image); //on récupère la largeur
			$y = imagesy($image); //on récupère la hauteur
			// Fusionnnnnnnnnn !
			imagecopyresampled($image,$calque,130,($y-$details_src[1]),0,0,$details_src[0],$details_src[1],$details_src[0],$details_src[1]);
			imagepng($image,REP_PHYSIQUE_NIVEAU_0.'parser/def/images/logo_product.png');
			imagedestroy($image);
		}
	}
	
	/**
	*	Détermine si l'application est un corporate ou non
	*	@return bool : vrai si corporate
	**/		
	public static function isCorporate($product='')
	{
		// Instanciation de DatabaseConnection
		// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::get Connection()
                self::$database = Database::getConnection($product);
		
		// On mémorise l'id produit
		self::$product = $product;
		
		// Requête qui regarde si la table corporate existe
		$query = "SELECT * FROM pg_tables
		WHERE schemaname = 'public'
		AND tablename = '".self::CONFIG_TABLE."'";
		self::$database->execute($query);
		// Retourne le résultat
		return self::$database->getNumRows();
	}

	/**
	*	Retourne la liste des Time Agregation disponible dans le contexte
	*	@return array : tableau des time agregation (sans BH)
	**/	
	public static function getCtxTimeAggregations($product='')
	{
		// Instanciation de DatabaseConnection
		// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::get Connection()
                self::$database = Database::getConnection($product);
		
		// On mémorise l'id produit
		self::$product = $product;
		
		// Requête qui récupère les TA
		$query = "SELECT agregation, agregation_label 
		FROM ".self::TIME_AGG_BCKP_TABLE."
		WHERE bh_type = 'normal'
		AND on_off = 1
		AND visible = 1
		AND agregation IN ('hour','day')
		ORDER BY agregation_rank";
		$result = self::$database->execute($query);
		// Tableau des résultats
		$returnArray = Array();
		while($array = self::$database->getQueryResults($result,1)) {
			$returnArray[$array['agregation']] = $array['agregation_label'];
		}
		// Retour des TA
		return $returnArray;
	}

	/**
	*	Retourne la liste des Network Agregation disponible dans le contexte
	*	@param string : family
	*	@param bool : axe3
	*	@return array : tableau des network agregation
	**/		
	public static function getCtxNetworkAggregations($family,$axe3=false,$product='')
	{
		// Instanciation de DatabaseConnection
		// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::get Connection()
                self::$database = Database::getConnection($product);
		
		// On mémorise l'id produit
		self::$product = $product;
		
		// Requête de récupération des NA ou NA axe 3
		$axe = ($axe3) ? '= 3' : 'IS NULL';
		$query = "SELECT agregation, agregation_label
		FROM ".self::NETWORK_AGG_BCKP_TABLE."  
		WHERE family = '".$family."'
		AND on_off = 1
		AND mandatory = 1
		AND axe ".$axe."
		ORDER BY agregation_rank";
		$result = self::$database->execute($query);

		// Tableau des résultats
		$returnArray = Array();
		while($array = self::$database->getQueryResults($result,1)) {
			$returnArray[$array['agregation']] = $array['agregation_label'];
		}
		// Retour des NA
		return $returnArray;
	}

	/**
	*	Retourne la ta_min configuré pour le corporate
	*	@return text : ta_min
	**/		
	public static function getTaMin($product='')
	{
		// Instanciation de DatabaseConnection
		// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::get Connection()
                self::$database = Database::getConnection($product);
		
		// On mémorise l'id produit
		self::$product = $product;
		// Requête qui récupère la TA min
		$query = "SELECT ta_min FROM ".self::CONFIG_TABLE." LIMIT 1";
		// Retour de la TA
		return self::$database->getOne($query);
	}
	
	/**
	*	Retourne une info corporate sur une famille donnée
	*	@param text : champ recherché
	*	@param text : famille
	*	@return mixed : valeur du champ demandé
	**/		
	public static function getFamilyInfo($key,$family,$product='')
	{
		// Instanciation de DatabaseConnection
		// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::get Connection()
                self::$database = Database::getConnection($product);
		
		// On mémorise l'id produit
		self::$product = $product;
		// Requête de récupération des infos de la famille
		$query = "SELECT 
			c.id_group_table, 
			c.na_min, 
			c.na_min_axe3, 
			c.ta_min, 
			n.agregation_label AS super_network, 
			c.export_raw, 
			c.export_kpi
		FROM 
			".self::CONFIG_TABLE." c LEFT JOIN ".self::NETWORK_AGG_TABLE." n
			ON (c.super_network = n.agregation_label AND n.family = '".$family."'),
			".self::GT_AXE_TABLE." gt
		WHERE c.id_group_table = gt.id_group_table
		AND gt.family = '".$family."'";		
		// Infos de la famille
		$familyInfos = self::$database->getRow($query);
		// Retour de la valeur cherchée
		if(isset($familyInfos[$key]))
			return $familyInfos[$key];
	}

	/**
	*	Retourne le code du super Network de la famille
	*	@param text : famille
	*	@return string : code du super network
	**/		
	public static function getSuperNetworkCode($family,$product='')
	{
		$superNetLabel = self::getFamilyInfo('super_network',$family,$product);
		return strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $superNetLabel));
	}

	/**
	*	Met à jour la conf des familles
	*	@param array : tableau de conf
	**/		
	public static function updateConf($config,$product='')
	{
		// Instanciation de DatabaseConnection
		if(empty(self::$database) || ($product != self::$product))
			self::$database = Database::getConnection($product);
		
		// On mémorise l'id produit
		self::$product = $product;
		
		// Variable de controle
		$execCtrl = true;
		
		// Gestion du super Network
		$execCtrl &= self::manageSuperNetwork($config,$product);
	
		// BEGIN
		self::$database->execute('BEGIN');

		// Pour toutes les familles
		foreach(getFamilyList($product) as $family => $familyLabel)
		{
			// Tableau des donnees famille
			$data = $config[$family];
		
			// Mise a jour de la table de configuration du corporate
			$query = "UPDATE ".self::CONFIG_TABLE."
			SET na_min = '".$data['na_min']."',
			na_min_axe3 = ".(empty($data['na_min_axe3']) ? 'NULL' : "'".$data['na_min_axe3']."'").",
			ta_min = '".$config['ta_min']."',
			super_network = ".(empty($data['super_network']) ? 'NULL' : "'".$data['super_network']."'").",
			export_raw = ".(empty($data['export_raw']) ? '0' : $data['export_raw']).",
			export_kpi = ".(empty($data['export_kpi']) ? '0' : $data['export_kpi'])."
			WHERE id_group_table = (SELECT id_group_table FROM ".self::GT_AXE_TABLE." WHERE family = '".$family."')";
			$execCtrl = $execCtrl && (!self::$database->execute($query) ? false : true);

			// Suppression des NA 1er axe de la famille
			$query = "DELETE FROM ".self::NETWORK_AGG_TABLE."
			WHERE family = '".$family."'
			AND axe IS NULL
			AND mandatory = 1";
			$execCtrl = $execCtrl && (!self::$database->execute($query) ? false : true);
			
			// Récupération des NA 1er axe à conserver
			$naPathArray = self::getPathNetworkAggregation($family,false,$product);
			$naToKeep = getLevelsAgregOnLevel($data['na_min'], $naPathArray);
			
			// Insertion de la nouvelle configuration NA 1er axe de la famille
            // 04/05/2011 NSE bz 22040 : configuration uniq_label perdue lors de la mise à jour des NA : ajout de uniq_label dans la requête
            // 01/08/2011 NSE bz 22060 : initialisation de use_prefix à 1 en mode corporate
			$query = "INSERT INTO ".self::NETWORK_AGG_TABLE." (";
			$query .= "SELECT 
				CASE WHEN agregation = '".$data['na_min']."' THEN 1 ELSE agregation_rank END AS agregation_rank,
				agregation,
				agregation_type,
				on_off,
				agregation_name,
				agregation_mixed,
				agregation_label,
				CASE WHEN agregation = '".$data['na_min']."' THEN 1 ELSE agregation_level END AS agregation_level,
				CASE WHEN agregation = '".$data['na_min']."' THEN '".$data['na_min']."' ELSE source_default END AS source_default,
				CASE WHEN agregation = '".$data['na_min']."' THEN '=' ELSE level_operand END AS level_operand,
				CASE WHEN agregation = '".$data['na_min']."' THEN '".$data['na_min']."' ELSE level_source END AS level_source,
				mandatory,
				family,
				voronoi_polygon_calculation,
				axe,
				link_to_aa,
				limit_3rd_axis,
				na_max_unique,
				na_parent_unique,
				third_axis_default_level,
				sdna_id,
				1, -- use_prefix initialisé à 1 en mode Corporate
                concat_code_connection,
                allow_color,
                uniq_label
			FROM ".self::NETWORK_AGG_BCKP_TABLE."
			WHERE family = '".$family."'
			AND agregation IN ('".implode("','",$naToKeep)."')
			AND axe IS NULL
			AND mandatory = 1
			ORDER BY agregation_rank)";
			$execCtrl = $execCtrl && (!self::$database->execute($query) ? false : true);

			// Si on a un 3eme axe
			if(!empty($data['na_min_axe3']))
			{
				// Suppression des NA 3eme axe de la famille
				$query = "DELETE FROM ".self::NETWORK_AGG_TABLE."
				WHERE family = '".$family."'
				AND axe = 3
				AND mandatory = 1";
				$execCtrl = $execCtrl && (!self::$database->execute($query) ? false : true);
				
				// Récupération des NA 3ème axe à conserver
				$naPathArray = self::getPathNetworkAggregation($family,1,$product);
				$naToKeep = getLevelsAgregOnLevel($data['na_min_axe3'], $naPathArray);

				// Insertion de la nouvelle configuration NA 3eme axe de la famille
                // 04/05/2011 NSE bz 22040 : configuration uniq_label perdue lors de la mise à jour des NA : ajout de uniq_label dans la requête
                // 01/08/2011 NSE bz 22060 : initialisation de use_prefix à 1 en mode corporate
				$query = "INSERT INTO ".self::NETWORK_AGG_TABLE." (";
				$query .= "SELECT 
					CASE WHEN agregation = '".$data['na_min_axe3']."' THEN 1 ELSE agregation_rank END AS agregation_rank,
					agregation,
					agregation_type,
					on_off,
					agregation_name,
					agregation_mixed,
					agregation_label,
					CASE WHEN agregation = '".$data['na_min_axe3']."' THEN 1 ELSE agregation_level END AS agregation_level,
					CASE WHEN agregation = '".$data['na_min_axe3']."' THEN '".$data['na_min_axe3']."' ELSE source_default END AS source_default,
					CASE WHEN agregation = '".$data['na_min_axe3']."' THEN '=' ELSE level_operand END AS level_operand,
					CASE WHEN agregation = '".$data['na_min_axe3']."' THEN '".$data['na_min_axe3']."' ELSE level_source END AS level_source,
					mandatory,
					family,
					voronoi_polygon_calculation,
					axe,
					link_to_aa,
					limit_3rd_axis,
					na_max_unique,
					na_parent_unique,
					third_axis_default_level,
					sdna_id,
					1, -- use_prefix initialisé à 1 lors de l'activation Corporate
                    concat_code_connection,
                    allow_color,
                    uniq_label
				FROM ".self::NETWORK_AGG_BCKP_TABLE."
				WHERE family = '".$family."'
				AND agregation IN ('".implode("','",$naToKeep)."')
				AND axe = 3
				AND mandatory = 1
				ORDER BY agregation_rank)";
				$execCtrl = $execCtrl && (!self::$database->execute($query) ? false : true);
			}
			
			// Modification du na_min dans la table sys_definition_categorie
			$query = "UPDATE ".self::FAMILY_TABLE." 
						SET network_aggregation_min = '".$data['na_min']."' 
						WHERE family = '".$family."'";
			$execCtrl = $execCtrl && (!self::$database->execute($query) ? false : true);
			
			// Mise à jour du statut de la famille
			$query = "UPDATE ".self::GT_TABLE." 
			SET raw_deploy_status = 3,
			kpi_deploy_status = 3,
			adv_kpi_deploy_status = 3
			WHERE family = '{$family}'";
			$execCtrl = $execCtrl && (!self::$database->execute($query) ? false : true);
		}
		
		// Pour toutes les familles, il faut enlever la condition d'unicité sur le niveau max (hors super networks)
		$query = "UPDATE ".self::NETWORK_AGG_TABLE." SET na_max_unique = NULL WHERE mandatory = 1";
		$execCtrl = $execCtrl && (!self::$database->execute($query) ? false : true);
		
		// On désactive le compute master hourly si on configure notre Corporate en DAY
		$query = "UPDATE ".self::MASTER_REF_TABLE." SET visible = 1 WHERE master_id = 12";
		if($config['ta_min'] == 'day')
			// 08:23 18/12/2009 GHX
			// Quand on masque le compute hourly on le désactive en même temps sinon ca posse problème
			$query = "UPDATE ".self::MASTER_REF_TABLE." SET visible = 0, on_off = 0 WHERE master_id = 12";
		$execCtrl = $execCtrl && (!self::$database->execute($query) ? false : true);
		$query = "DROP TABLE IF EXISTS ".self::MASTER_TABLE;
		$execCtrl = $execCtrl && (!self::$database->execute($query) ? false : true);
		$query = "SELECT * INTO ".self::MASTER_TABLE." FROM ".self::MASTER_REF_TABLE."";
		$execCtrl = $execCtrl && (!self::$database->execute($query) ? false : true);
		
		// 06/11/2009 BBX. BZ 12578
		// On passe l'application en DAY si on a sélectionné DAY
		if($config['ta_min'] == 'day')
		{
			// Désactivation de la TA hour
			$query = "UPDATE ".self::TIME_AGG_TABLE." SET on_off = 1, visible = 0 WHERE agregation = 'hour'";
			$execCtrl = $execCtrl && (!self::$database->execute($query) ? false : true);
			// Désactivation de la BH
			$query = "UPDATE ".self::TIME_AGG_TABLE." SET visible = 0 WHERE agregation ILIKE '%_bh'";
			$execCtrl = $execCtrl && (!self::$database->execute($query) ? false : true);
			
			// 08:26 18/12/2009 GHX
			$query = "UPDATE ".self::GLOBAL_PARAMETERS." SET value = 'daily' WHERE parameters = 'compute_mode'";
			$execCtrl = $execCtrl && (!self::$database->execute($query) ? false : true);
		}
		else
		{
			// Activation de la TA hour
			$query = "UPDATE ".self::TIME_AGG_TABLE." SET on_off = 1, visible = 1 WHERE agregation = 'hour'";
			$execCtrl = $execCtrl && (!self::$database->execute($query) ? false : true);	
			// Résactivation de la BH
			$query = "UPDATE ".self::TIME_AGG_TABLE." SET visible = 1 WHERE agregation ILIKE '%_bh'";
			$execCtrl = $execCtrl && (!self::$database->execute($query) ? false : true);
			
			// 08:26 18/12/2009 GHX
			$query = "UPDATE ".self::GLOBAL_PARAMETERS." SET value = 'hourly' WHERE parameters = 'compute_mode'";
			$execCtrl = $execCtrl && (!self::$database->execute($query) ? false : true);
		}
	
		// 08:26 18/12/2009 GHX
		$query = "UPDATE ".self::GLOBAL_PARAMETERS." SET value = NULL WHERE parameters = 'compute_switch'";
		$execCtrl = $execCtrl && (!self::$database->execute($query) ? false : true);
			
		// Test de la variable de controle. Si une des requetes precedentes a echoue, on Rollback. Sinon on commit.
		$execCtrl ? self::$database->execute('COMMIT') : self::$database->execute('ROLLBACK');

                // 12/10/2011 BBX
                // BZ 22256 : Mise à jour des super network
                if($execCtrl)
                    $execCtrl &= self::checkSuperNetwork($config, $product);

		// Rebuild de la table sys_definition_group_table_network
		if($execCtrl)
			$execCtrl &= NaModel::buildGroupTableNetwork($product);
		
		// Rebuild de la table sys_definition_group_table_time
		if($execCtrl)
			$execCtrl &= self::buildGroupTableTime($config['ta_min'],$product);
		
		// Si tout est OK jusque là
		if($execCtrl)
		{
			// Déploiement de la configuration
			foreach(getFamilyList($product) as $family => $familyLabel)
			{
				// Infos sur la famille
				$familyInfos = GetGTInfoFromFamily($family,$product);
				// Déploiement de la configuration				
                                // 19/05/2011 BBX - PARTITIONING -
                                // On peut désormais passer une instance de connexion
				// 18/12/2012 GFS - BZ#29752 - SQL errors when saving a slave
				$deploy = new deploy(self::$database, $familyInfos['id_ligne'], $product);
				if(count($deploy->types) > 0) $deploy->operate();
				$deploy->display(0);
			}
		}
		
		// Génération de la conf des fichiers
		$execCtrl &= self::configureFlatFileLib($product);

		// Retour de la valeur de la variable de contrôle
		return $execCtrl;
	}

	/**
	*	06/11/2009 BBX. BZ 12578
	*	Reconstruit sys_definition_group_table_time
	*	@param string : ta min
	*	@param int : id produit
	*	@return boolean
	**/		
	public static function buildGroupTableTime($taMin,$product='')
	{
		// Instanciation de DatabaseConnection
		// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::get Connection()
                self::$database = Database::getConnection($product);
		
		// On mémorise l'id produit
		self::$product = $product;
		
		// Variable de contrôle
		$execCtrl = true;
		
		// Truncate de la table
		$query = "TRUNCATE ".self::TIME_AGG_CONFIG_TABLE;
		$execCtrl = $execCtrl && (!self::$database->execute($query) ? false : true);
		
		// Récupération des niveaux temporels
		if($taMin == 'hour')
		{
			$query = "SELECT agregation_rank,agregation, source_default 
			FROM ".self::TIME_AGG_TABLE."
			WHERE on_off = 1
			ORDER BY agregation_rank";
		}
		else
		{
			$query = "SELECT agregation_rank,agregation, source_default 
			FROM ".self::TIME_AGG_TABLE."
			WHERE bh_type = 'normal'
			AND on_off = 1
			ORDER BY agregation_rank";		
		}
		$result = self::$database->execute($query);
		
		// Mémorisation des niveaux temporels
		$taLevels = Array();
		while($array = self::$database->getQueryResults($result,1)) {
			$taLevels[$array['agregation']] = $array['source_default'];
		}

		// Pour toutes les familles
		$idLigne = 1;
		foreach(getFamilyList($product) as $family => $familyLabel)
		{
			// Pour les raw et les kpis
			foreach(Array('raw','kpi') as $type)
			{
				$insertedTa = Array();
				foreach($taLevels as $agregation => $source)
				{
					// Détermination de l'id source
					$idSource = ($agregation == 'hour') ? '-1' : $insertedTa[$source];				
					// Insertion en base
					$queryInsert = "INSERT INTO ".self::TIME_AGG_CONFIG_TABLE."
						(id_ligne,
						id_group_table,
						time_agregation,
						time_agregation_label,
						id_source,
						data_type,
						on_off,
						comment,
						deploy_status)
					VALUES 
						({$idLigne},
						(SELECT rank FROM ".self::FAMILY_TABLE." WHERE family = '{$family}'),
						'{$agregation}',
						'".ucfirst($agregation)."',
						{$idSource},
						'{$type}',
						1,
						NULL,
						1)";
					$execCtrl = $execCtrl && (!self::$database->execute($queryInsert) ? false : true);
					// Mémorisation de l'id affecté à la ligne
					$insertedTa[$agregation] = $idLigne;
					// Incrémentation de l'id ligne
					$idLigne++;
				}
			}
		}
		// Retour de la variable de contrôle
		return $execCtrl;
	}

	/**
         * Met à jour les niveaux source des super network
         * 12/10/2011 pour le BZ 22256
         * @param type $config
         * @param type $product
         * @return type 
         */
        public static function checkSuperNetwork($config,$product='')
        {
            // Instanciation de DatabaseConnection
		if(empty(self::$database) || ($product != self::$product))
			self::$database = Database::getConnection($product);
                
            // On mémorise l'id produit
            self::$product = $product;
            
            // Variable de contrôle
            $execCtrl = true;

            // Parcours des familles
            foreach(getFamilyList($product) as $family => $familyLabel)
            {
                // Super network
                $SuperNetwork = self::getFamilyInfo('super_network',$family,$product);

                // Si le super network existe, on met à jour sa source au cas oula conf max est changée
                // Cas GPRS sur RAI par exemple
                if($SuperNetwork != '') {
                    // Source
                    $query = "SELECT agregation FROM ".self::NETWORK_AGG_TABLE." 
                        WHERE family='{$family}'
                        AND agregation_label NOT ILIKE '$SuperNetwork'
                        AND axe IS NULL ORDER BY agregation_level DESC LIMIT 1;";
                    $naSource = self::$database->getOne($query);
                    // Update
                    $queryUpdate = "UPDATE ".self::NETWORK_AGG_TABLE."
                        SET level_source = '$naSource'
                        WHERE agregation_label ILIKE '$SuperNetwork'
                        AND family = '$family'";
                    $execCtrl = $execCtrl && (!self::$database->execute($queryUpdate) ? false : true);
                }
            }
            
            return $execCtrl;
        }

	/**
	*	Gestion du super network
	*	@param array : tableau de config qui contient la valeur du super network
	*	@return boolean
	**/		
	public static function manageSuperNetwork($config,$product='')
	{
		// Instanciation de DatabaseConnection
		if(empty(self::$database) || ($product != self::$product))
			self::$database = Database::getConnection($product);
		
		// On mémorise l'id produit
		self::$product = $product;
			
		// Variable de contrôle
		$execCtrl = true;
		
		// Parcours des familles
		foreach(getFamilyList($product) as $family => $familyLabel)
		{
			$oldSuperNetwork = self::getFamilyInfo('super_network',$family,$product);
			$newSuperNetwork = $config[$family]['super_network'];
			$createSN = false;

			// Un super Network existe déjà
			if(!empty($oldSuperNetwork))
			{
				// Le nouveau Super Network est différent
				if($oldSuperNetwork != $newSuperNetwork)
				{
					// On doit supprimer l'ancien super network
					$execCtrl &= NaModel::deleteNA(self::getSuperNetworkCode($family,$product),$family,$product);					
					
					// On en recréé un si un nouveau est spécifié
					if(!empty($newSuperNetwork)) $createSN = true;
				}
			}
			// Aucun Super Network n'existe
			else
			{
				// On doit créer un super network
				if(!empty($newSuperNetwork)) $createSN = true;
			}
			
			// Création du super network
			if($createSN)
			{
                                // 12/10/2011 BBX
                                // BZ 22256 : Vérification conflit avec un autre NA
                                if(NaModel::exists($newSuperNetwork,$family,$product))
                                {
                                    self::$errorMsg = __T('A_SETUP_CORPORATE_SUPER_NETWORK_CONFLICT',$newSuperNetwork);
                                    $execCtrl = false;
                                }
                                else
                                {
				// Récupération du niveau MAX de la famille
                                // 04/03/2011 OJT : bz21093, modification de la requête pour exclure le 3ème axe
                                $query = "SELECT agregation FROM ".self::NETWORK_AGG_TABLE." WHERE family='{$family}' AND axe IS NULL ORDER BY agregation_level DESC LIMIT 1;";
				$naSource = self::$database->getOne($query);
				$execCtrl &= NaModel::createNA($newSuperNetwork,$naSource,$family,$product,'1');
			}
		}
		}
		// Retour booléen
		return $execCtrl;
	}
	
	/**
         * Envoie des fichiers CFG aux affiliates
         * 25/02/2011 OJT : DE SFTP, gestion du protocole SFTP + bz 20998
         *
         * @param string $product Identifiant du produit
         * @return boolean
         */
	public static function sendDataExport( $product='' )
	{
            $execCtrlGlobal = true; // Variable de test
		
            // Si pas de connexions, on retourne false
            if(count(ConnectionModel::getAllConnections($product)) == 0)
                    return false;
			
            // Récupération des infos produit
            $pModel = new ProductModel($product);
            $productInfos = $pModel->getValues();
		
            // Flag permttant de savoir si le Corporate est en local
            $corpIsLocal = ( $productInfos['sdp_ip_address'] == get_adr_server() );

            // On parcours les affiliates
            foreach(ConnectionModel::getAllConnections($product) as $idConnection)
            {
                // Chemin du fichier
                /*
                * /!\ NE PAS MODIFIER LE NOM DU FICHIER
                */
                $fileName = 'update_data_export.cfg';
                $filePath = REP_PHYSIQUE_NIVEAU_0.'upload/'.$fileName;
                $execCtrl = true; // Variable de test interne
			
                // Création du fichier
                self::buildCFG($idConnection,$filePath,$product);

                // Instanciation d'un objet connexion
                $connection = new ConnectionModel($idConnection,$product);
			
                // Selon le mode de transfert
                switch( $connection->getValue('connection_type') )
                {
                    // Cas d'une connection locale
                    case 'local' :
                        $newfile = $connection->getValue('connection_directory').'/'.$fileName; // Copie locale
				
				// Le corporate a la même IP que le master
                        if( $corpIsLocal ){
                            
                            // 11/10/2011 BBX
                            // BZ 24037 : Suppression du fichier s'il existe déjà pour éviter les conflits de droits
                            if(file_exists($newfile)) @unlink($newfile);                            
                                $execCtrl &= @copy($filePath, $newfile);
                        }
                        // Le corporate est une application slave distante
                        else 
                        {
                            try 
                            {
                                $SSH = new SSHConnection($productInfos['sdp_ip_address'], $productInfos['sdp_ssh_user'], $productInfos['sdp_ssh_password'],$productInfos['sdp_ssh_port']);

                                // 11/10/2011 BBX
                                // BZ 24037 : Suppression du fichier s'il existe déjà pour éviter les conflits de droits
                                if($SSH->fileExists($newfile)) @$SSH->unlink($newfile);

                                $SSH->sendFile($filePath, $newfile);
                            }
                            catch (Exception $e)
                            {
                                $execCtrl &= false;
                            }
                        }
                    break;
				
                    // Cas d'une connection FTP
                    case 'remote' :
                        // Le corporate a la même IP que le master
                        if( $corpIsLocal )
                        {
                            $conn_id = @ftp_connect($connection->getValue('connection_ip_address'));
                            $execCtrl &= @ftp_login($conn_id,$connection->getValue('connection_login'),$connection->getValue('connection_password'));
                            // 22/09/2010 NSE bz 18117 : initialisation du mode de connexion
                            if($connection->getValue('connection_mode') == 0)
                                @ftp_pasv($conn_id, true);
                            else
                                @ftp_pasv($conn_id, false);

                            // 11/10/2011 BBX
                            // BZ 24037 : Suppression du fichier s'il existe déjà pour éviter les conflits de droits
                            @ftp_delete($conn_id,$connection->getValue('connection_directory').'/'.$fileName);

                            $execCtrl &= @ftp_put($conn_id,$connection->getValue('connection_directory').'/'.$fileName,$filePath,FTP_ASCII);
                            @ftp_close($conn_id);
                        }
                        // Le corporate est une application slave distante
                        else
                        {
                            try 
                            {
                              $SSH = new SSHConnection($productInfos['sdp_ip_address'], $productInfos['sdp_ssh_user'], $productInfos['sdp_ssh_password'],$productInfos['sdp_ssh_port']);
                              $SSH->sendFile($filePath, '/home/'.$productInfos['sdp_directory'].'/upload/'.$fileName);
                              // 27/10/2009 BBX : modification de la fonction ftp_put. Le chemin du fichier à envoyer était incorrect. BZ 12289
                              // 22/09/2010 NSE bz 18117 : initialisation du mode de connexion

                            // 11/10/2011 BBX
                            // BZ 24037 : Suppression du fichier s'il existe déjà pour éviter les conflits de droits
                              $ftpMode = $connection->getValue('connection_mode');
                              if( $ftpMode == '' ) $ftpMode = 1;
                              $file = '<?php
                                \$conn_id = @ftp_connect(\''.$connection->getValue('connection_ip_address').'\',21,30);
                                if( !\$conn_id ) exit( \'NOK\' );
                                \$login_result = @ftp_login (\$conn_id, \''.$connection->getValue('connection_login').'\', \''.$connection->getValue('connection_password').'\');
                                if(!\$login_result) exit( \'NOK\');
                                if('.$ftpMode.' == 0) @ftp_pasv(\$conn_id, true);
                                else @ftp_pasv(\$conn_id, false);
                                if(@ftp_chdir(\$conn_id, \''.$connection->getValue('connection_directory').'\')){
                                    @ftp_delete(\$conn_id,\''.$connection->getValue('connection_directory').'/'.$fileName.'\');
                                  if(@ftp_put(\$conn_id,\''.$connection->getValue('connection_directory').'/'.$fileName.'\',\''.'/home/'.$productInfos['sdp_directory'].'/upload/'.$fileName.'\',FTP_ASCII)) {
                                    exit( \'OK\' );
                                  }
                                }
                                exit( \'NOK\' );
                             ?>';

                              $file_test = '/home/'.$productInfos['sdp_directory'].'/upload/test_ftp_connexion.php';
                              $SSH->exec('echo "'.$file.'" > '.$file_test);
                              $test_ftp = $SSH->exec('php -q '.$file_test);
                              $SSH->exec('rm -f '.$file_test);
                              // 27/10/2009 BBX : ajout de la suppression du fichier update_data_export.cfg après transfert de celui-ci. BZ 12289
                              $SSH->exec('rm -f /home/'.$productInfos['sdp_directory'].'/upload/'.$fileName);
                              // 27/10/2009 BBX : le résultat est un tableau. Ajout de la lecture sur l'index "0". BZ 12289
                              $execCtrl &= ($test_ftp[0] == 'OK') ? true : false;
                            }
                            catch (Exception $e) {
                                    $execCtrl &= false;
                            }				
                        }
                    break;

                    // Cas d'une connection SFTP
                    case 'remote_ssh' :
                        // Le corporate a la même IP que le master
                        if( $corpIsLocal )
                        {
                            try
                            {
                                $res = new SSHConnection( $connection->getValue('connection_ip_address'), $connection->getValue('connection_login'), $connection->getValue('connection_password'), $connection->getValue('connection_port') );
                                
                                // 11/10/2011 BBX
                                // BZ 24037 : Suppression du fichier s'il existe déjà pour éviter les conflits de droits
                                if($res->fileExists($connection->getValue('connection_directory').'/'.$fileName)) 
                                        @$res->unlink($connection->getValue('connection_directory').'/'.$fileName);
                                
                                $res->sendFile( $filePath, $connection->getValue('connection_directory').'/'.$fileName );
                            }
                            catch( Exception $ex )
                            {
                                $execCtrl &= false;
                            }
                        }
                        else
                        {
                            try
                            {
                                // 08/03/2011 OJT : Correction bz21156 pour DE SFTP (mauvais numéro de port)
                                // 11/10/2011 BBX
                                // BZ 24037 : Suppression du fichier s'il existe déjà pour éviter les conflits de droits
                                $SSH = new SSHConnection($productInfos['sdp_ip_address'], $productInfos['sdp_ssh_user'], $productInfos['sdp_ssh_password'],$productInfos['sdp_ssh_port']);
                                $SSH->sendFile($filePath, '/home/'.$productInfos['sdp_directory'].'/upload/'.$fileName);
                                $file = '<?php
                                            \$res = @ssh2_connect( \''.$connection->getValue('connection_ip_address').'\', '.$connection->getValue('connection_port').' );
                                            if( !\$res ) exit( \'NOK\' );
                                            if( !@ssh2_auth_password( \$res, \''.$connection->getValue('connection_login').'\', \''.$connection->getValue('connection_password').'\' ) ) exit( \'NOK\' );
                                            @ssh2_sftp_unlink ( \$res, \'/home/'.$productInfos['sdp_directory'].'/upload/'.$fileName.'\' );
                                            if( !@ssh2_scp_send( \$res, \'/home/'.$productInfos['sdp_directory'].'/upload/'.$fileName.'\', \''.$connection->getValue('connection_directory').'/'.$fileName.'\') ) exit( \'NOK\' );
                                            exit( \'OK\' );
                                        ?>';
                                $file_test = '/home/'.$productInfos['sdp_directory'].'/upload/sftpCFGSend.tmp.php';
                                $SSH->exec('echo "'.$file.'" > '.$file_test);
                                $sendCFG = $SSH->exec('php -q '.$file_test);
                                $execCtrl &= ($sendCFG[0] == 'OK') ? true : false;
                                $SSH->exec('rm -f '.$file_test); // Suppression du fichier PHP dynamique
                                $SSH->exec('rm -f /home/'.$productInfos['sdp_directory'].'/upload/'.$fileName); // Suppression du CFG
                            }
                            catch( Exception $ex )
                            {
                                $execCtrl &= false;
                            }
                        }
                        break;
                }

                // 24/09/2010 BBX : BZ 17891 On ne retourne plus false ici car
                // il faut tout de même envoyer le fichier pour les connexions valides
                if(!$execCtrl) {
                        // On mémorise l'affiliate en question
                        self::$affiliateFailed = $connection->getValue('connection_name');
                   $execCtrlGlobal = false;
                }
                else {
                        // Suppression du fichier
                        unlink($filePath);
                }
            }
            return $execCtrlGlobal; // Retour booléen
	}

	/**
	*	Génération du fichier CFG
	*	@param string : id de la connexion
	*	@param string : chemin vers le fichier
         * 
         * 11/10/2011 NSE DE Bypass temporel : ajout d'une ligne pour générer le Data Export de la Ta Bypassée
	**/		
	public static function buildCFG($idConnection,$filePath,$product='')
	{
		// Instanciation d'un objet connexion
		$connection = new ConnectionModel($idConnection,$product);

		// Ouverture du fichier
		$cfgFile = fopen($filePath,'w+');
		
		// Calcul du nom de l'affiliate
		$affiliateName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $connection->getValue('connection_name')));
		
		// Répertoire cible
		$targetDir = $connection->getValue('connection_directory');
		
		foreach(getFamilyList($product) as $family => $familyLabel)
		{
                    // Récupération des valeurs de l'export
                    $exportName = "auto_".$affiliateName."_".$family;			
                    $timeAggregation = self::getFamilyInfo('ta_min',$family,$product);
                    $networkAggregation = self::getFamilyInfo('na_min',$family,$product);
                    $networkAggregationThirdAxis = self::getFamilyInfo('na_min_axe3',$family,$product);
                    $rawExport = self::getFamilyInfo('export_raw',$family,$product);
                    $kpiExport = self::getFamilyInfo('export_kpi',$family,$product);

                    // Affectation des valeurs à insérer
                    $valuesToInsert = Array();
                    $valuesToInsert[] = $family;
                    $valuesToInsert[] = $exportName;
                    $valuesToInsert[] = $targetDir;
                    $valuesToInsert[] = $timeAggregation;
                    $valuesToInsert[] = $networkAggregation;
                    $valuesToInsert[] = $networkAggregationThirdAxis;
                    $valuesToInsert[] = '0';
                    $valuesToInsert[] = $rawExport;
                    $valuesToInsert[] = $kpiExport;

                    // Ligne à insérer dans le fichier
                    $lineToInsert = implode(';',$valuesToInsert);
                    fwrite($cfgFile,$lineToInsert."\n");

                    // 11/10/2011 NSE DE Bypass temporel : ajout d'une ligne pour générer le Data Export de la Ta Bypassée
                    foreach(TaModel::getAllTaForFamily($family) as $ta){
                        // si la Ta est supérieure à la Ta Min du Corporate et différente de la Ta de l’enregistrement courant
                        if(TaModel::isTa1Greater($ta,$timeAggregation,$product)>0){
                            if(TaModel::IsTABypassedForFamily($ta, $family, $product)==1){
                                // on modifie le nom de l'export
                                $valuesToInsert[1] = "auto_".$affiliateName."_".$family.'_bypass_'.$ta;
                                // On modifie la Ta
                                $valuesToInsert[3] = $ta;
                                // on insère cette ligne supplémentaire dans le fichier
                                $lineToInsert = implode(';',$valuesToInsert);
                                fwrite($cfgFile,$lineToInsert."\n");
                            }
                        }
                    }

		}
		
		// Fermeture du fichier
		fclose($cfgFile);
	}

	/**
	*	Récupère les chemins d'agrégation d'une famille
	*	@param string : famille
	*	@param bool : axe3 = vrai
	*	@return array : tableau des chemins
	**/		
	public static function getPathNetworkAggregation($family = '', $axe3 = false, $product='')
	{	
		// Instanciation de DatabaseConnection
		// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::get Connection()
                self::$database = Database::getConnection($product);
		
		// On mémorise l'id produit
		self::$product = $product;

		// Récupération des NA de la famille
		$axe = $axe3 ? ' = 3' : ' IS NULL';
		$query = "SELECT agregation_rank,agregation 
		FROM ".self::NETWORK_AGG_BCKP_TABLE."
		WHERE mandatory = 1
		AND axe ".$axe."
		AND family = '".$family."'
		ORDER BY agregation_rank";		
		$result = self::$database->execute($query);

		// Récupération des NA
		$pathNA = Array();
		while($array = self::$database->getQueryResults($result,1)) 
		{
			// Récupération des enfants
			$query = "SELECT DISTINCT agregation_rank,agregation
			FROM ".self::NETWORK_AGG_BCKP_TABLE."
			WHERE mandatory = 1
			AND axe ".$axe."
			AND family = '".$family."'
			AND level_source = '".$array['agregation']."'
			AND agregation != '".$array['agregation']."'
			ORDER BY agregation_rank";
			$result2 = self::$database->execute($query);
			while($array2 = self::$database->getQueryResults($result2,1))
			{
				$pathNA[$array['agregation']][] = $array2['agregation'];
			}
		}
		// Retour du tableau
		return $pathNA;
	}
      
	/**
	*	Configure le parser pour les fichiers sources à intégrer
	*	@return boolean
         * 
         * 13/10/2011 NSE DE Bypass temporel : ajout d'une ligne pour le Data Export de la Ta Bypassée
	**/			
	public static function configureFlatFileLib($product='')
	{
            // Instanciation de DatabaseConnection (via singleton)
            self::$database = Database::getConnection( $product );
		
            // On mémorise l'id produit
            self::$product = $product;

            // Labels des familles
            $familyLabels = getFamilyList($product);

            // On vide les tables sys_link_filetype_grouptable et sys_definition_flat_file_lib
            $query = "TRUNCATE TABLE ".self::FILETYPE_LINK_TABLE;
            self::$database->execute($query);
            $query = "TRUNCATE TABLE ".self::FLAT_FILE_TABLE;
            self::$database->execute($query);

            // Construction de sys_definition_flat_file_lib et sys_link_filetype_grouptable
            $querymax = "SELECT MAX(id_group_table) 
            FROM ".self::CONFIG_TABLE." c, ".self::GT_TABLE;
            $maxIdGroupTable = self::$database->getOne($querymax);  
            
            $query = "SELECT * 
            FROM ".self::CONFIG_TABLE." c, ".self::GT_TABLE." gt
            WHERE c.id_group_table = gt.id_ligne
            ORDER BY id_group_table";
            $result = self::$database->execute($query);
            while($array = self::$database->getQueryResults($result,1))
            {
                // 11/03/2011 OJT : bz20984, ajout des paramètre manquants
                $dcf = 1; // Data collection frequency temp variable
                $dc  = 24; // Data chunks temp variable
                if( strtolower( trim( $array['ta_min'] ) ) == 'day' ){
                    $dcf = 24;
                    $dc  = 1;
                }

                // Construction de sys_definition_flat_file_lib
                $insert = "INSERT INTO ".self::FLAT_FILE_TABLE."
                (id_flat_file,
                  flat_file_name,
                  flat_file_naming_template,
                  on_off,
                  alarm_missing_file_temporization,
                  period_type,
                  exclusion,
                  prefix_counter,
                  reference,
                    ordre,
                    data_collection_frequency,
                    granularity,
                    data_chunks
                )
                VALUES
                (
                    {$array['id_group_table']},
                    'Corporate {$familyLabels[$array['family']]}',
                    'auto_*_{$array['family']}_[0-9_H]+.csv',
                  1,
                  6,
                    '{$array['ta_min']}',
                  NULL,
                  NULL,
                  NULL,
                    {$array['id_group_table']},
                    {$dcf},
                    '{$array['ta_min']}',
                    {$dc}
                )";
                self::$database->execute($insert);

                // Construction de sys_link_filetype_grouptable
                $insert = "INSERT INTO ".self::FILETYPE_LINK_TABLE."
                (id_group_table,flat_file_id)
                VALUES (".$array['id_group_table'].",".$array['id_group_table'].")";
                self::$database->execute($insert);
                
                // 11/10/2011 NSE DE Bypass temporel : ajout d'une ligne pour générer le Data Export de la Ta Bypassée
                foreach(TaModel::getAllTaForFamily($array['family']) as $ta){
                    
                    $dcf = 1; // Data collection frequency temp variable
                    $dc  = 24; // Data chunks temp variable
                    if( strtolower( trim( $ta ) ) == 'day' ){
                        $dcf = 24;
                        $dc  = 1;
                    }
                    
                    // si la Ta est supérieure à la Ta Min du Corporate et différente de la Ta de l’enregistrement courant
                    if(TaModel::isTa1Greater($ta,$array['ta_min'],$product)>0){
                        if(TaModel::IsTABypassedForFamily($ta, $array['family'], $product)==1){ 
                            $maxIdGroupTable++;
                            // On ajoute un enregistrement avec la Ta trouvée
                            // Construction de sys_definition_flat_file_lib
                            $insert = "INSERT INTO ".self::FLAT_FILE_TABLE."
                            (id_flat_file,
                              flat_file_name,
                              flat_file_naming_template,
                              on_off,
                              alarm_missing_file_temporization,
                              period_type,
                              exclusion,
                              prefix_counter,
                              reference,
                                ordre,
                                data_collection_frequency,
                                granularity,
                                data_chunks
                            )
                            VALUES
                            (
                                {$maxIdGroupTable},
                                'Corporate {$familyLabels[$array['family']]} Bypass',
                                'auto_*_{$array['family']}_bypass_{$ta}_[0-9]+.csv',
                              1,
                              6,
                                '$ta',
                              NULL,
                              NULL,
                              NULL,
                                {$maxIdGroupTable},
                                {$dcf},
                                '$ta',
                                {$dc}
                            ) ";
                            self::$database->execute($insert);
                            
                            // Construction de sys_link_filetype_grouptable
                            $insert = "INSERT INTO ".self::FILETYPE_LINK_TABLE."
                            (id_group_table,flat_file_id)
                            VALUES (".$array['id_group_table'].",".$maxIdGroupTable.")";
                            self::$database->execute($insert);
                        }
                    }
                }
                
            }		
            return true;
        }
}