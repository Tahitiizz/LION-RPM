<?php
/*
	19/11/2009 GHX
		- Correction d'un problème sur le remplissage de la table sys_definition_group_table_time
	17/05/2010 NSE : Installation standard, utilisation d'une variable globale pour les chemins vers les exécutables psql et php (PSQL_DIR et PHP_DIR)
    28/07/2010 OJT : Suppression de la constante PHP_DIR (bz17016)
 *  18/10/2010 SCT : ajout de la méthode "getIdFamily" qui retourne l'id de la famille (string ou Id)
 *  18/03/2011 NSE bz 17516 : polygones non calculés sur les familles > niveau min et familles non proposées dans Gis supervision
 *      -> on initialise voronoi_polygon_calculation à 1 au lieu de null
 * 11/10/2011 NSE DE Bypass temporel : ajout de la méthode getFamilyFromEdwGroupTable
 */
?>
<?php
/**
*	Classe permettant de 
*	Travaille sur la table sys_definition_network_agregation, sys_definition_group_table_network
*
*	@author	 - //2009
*	@version	CB 
*	@since	CB 
*
*
*/
class FamilyModel
{
	/**
	 * Instance de connexion vers la base de données
	 * @var DatabaseConnection
	 */
	private static $_db = null;
	
	/**
	 * Identifiant du produit sur lequel on est connecté
	 * @var int
	 */
	private static $_idProduct = null;
	
	/**
	 * Identifiant de la famille
	 * @var string
	 */
	private $_idFamily = null;
	
	/**
	 * Configuration de la famille
	 * @var array
	 */
	private $_config = array();
	
	/**
	 * Liste des labels des familles
	 * @var array
	 */
	private static $_labelFamily = array();
	
	/**
	 * Constructeur
	 *
	 * @author GHX
	 * @param string $idFamily ID de la famille
	 * @param int $idProduct ID du produit sur lequel on doit se connecter (defaut le master produit)
	 */
	public function __construct( $idFamily, $idProduct = null )
	{
		$this->_idFamily = $idFamily;
		
		if ( is_null(self::$_db) || self::$_idProduct != $idProduct )
		{
			self::$_idProduct = $idProduct;
            // 31/01/2011 BBX bz 20450 : On remplace new DatabaseConnection() par Database::getConnection()
			self::$_db = Database::getConnection($idProduct);
		}
		
		if ( is_string($this->_idFamily) )
		{
			$this->_config = self::$_db->getRow("SELECT * FROM sys_definition_categorie WHERE family = '{$this->_idFamily}'");
		}
	} // End function __construct
	
	/**
	 * Retourne un tableau de la configuration de la famille
	 *
	 * @author GHX
	 * @return array
	 */
	public function getValues ()
	{
		return $this->_config;
	} // End function getValues
	
	
	/**
	 * Retourne la valeur d'un paramètre de la famille
	 *
	 * @author GHX
	 * @param string $key
	 * @return mixed
	 */
	public function getValue ( $key )
	{
		if ( array_key_exists($key, $this->_config) )
			return $this->_config[$key];
		
		return null;
	} // End function 
	
	/**
	 * Définie la valeur d'un paramètre de la famille
	 *
	 * @author GHX
	 * @param string $key nom du paramètre
	 * @param mixed $value valeur du paramètre
	 */
	public function setValue ( $key, $value )
	{
		$this->_config[$key] = $value;
	} // End function setValue
	
	/**
	 * Définie un nouveau label pour la famille
	 *
	 * @author GHX
	 * @param string $newLabel nouveau label de la famille
	 */
	public function updateLabel ( $newLabel )
	{
		self::$_db->execute("UPDATE sys_definition_categorie SET family_label = '{$newLabel}' WHERE family = '{$this->_idFamily}'");
	} // End function update
	
	/**
	 * Méthode create : crée une famille
	 * 
	 * @author NSE
	 * @param string $id : identifiant de la famille
	 * @param string  $label : label de la famille
	 * @param int $main: 0 ou 1 s'il s'agit de la famille principale
	 * @param int $product : id produit sur lequel on doit créer la famille
	 * @param boolean $automaticMapping 1 si la famille a des compteurs dynamiques = famille visible dans l'IHM automatic Mapping (default false)
	 * @return boolean : true si la création a réussi
	 */		
	public static function create ( $id, $label, $main, $product, $rank='', $automaticMapping = false )
	{
		// Connexion à la base de données du produit
        // 31/01/2011 BBX bz 20450 : On remplace new DatabaseConnection() par Database::getConnection()
		$database = Database::getConnection($product);
		
		// Variable de contrôle
		$execCtrl = true;
		
		// Calcul des champs
		if(empty($rank)){
			$query_max = "SELECT CASE WHEN MAX(rank) IS NULL THEN 1 ELSE MAX(rank)+1 END as nb FROM sys_definition_categorie";
			$rank = $database->getOne($query_max);
		}
		
		$object_ref_table = 'edw_object_ref';
		
		// Requête de création 
		$query = "INSERT INTO sys_definition_categorie
		(
			family,
			family_label,
			object_ref_table,
			rank,
			on_off,
			visible,			
			main_family,
			automatic_mapping,
			separator,
			link_to_aa_3d_axis)
		VALUES 
		('$id','$label','$object_ref_table',$rank,1,1,$main,".($automaticMapping == false ? 'NULL' : 1 ).",null,FALSE)";
		$execCtrl = $execCtrl && (!$database->execute($query) ? false : true);
		
		// Requête de création 
		$query = "INSERT INTO sys_definition_gt_axe
		(
			axe_gt_id,
			axe_index_label,
			axe_label,
			axe_type,
			family,
			axe_order,
			id_group_table,
			axe_type_label
		)
		VALUES 
		('axe1','axe1','NA','NA','$id',1,$rank,NULL )";
		$execCtrl = $execCtrl && (!$database->execute($query) ? false : true);
		
		
		// Calcul des champs
		$edw_group_table = 'edw_'.get_sys_global_parameters('module', 'def', $product).'_'.$id.'_axe1'; 

		$query = "INSERT INTO sys_definition_group_table
		(
			edw_group_table,
			data_source,
			raw_on_off,
			kpi_on_off,
			adv_kpi_on_off,
			mixed_on_off,
			family,
			raw_deploy_status,
			kpi_deploy_status,
			adv_kpi_deploy_status,
			mixed_kpi_deploy_status,
			id_parser,
			visible,			
			id_ligne)
		VALUES 
		('$edw_group_table',0,1,1,1,0,'$id',0,0,0,0,1,1,$rank)";
		$execCtrl = $execCtrl && (!$database->execute($query) ? false : true);
		
		$query = "INSERT INTO sys_definition_group_table_ref
		(
			edw_group_table,
			data_source,
			raw_on_off,
			kpi_on_off,
			adv_kpi_on_off,
			mixed_on_off,
			family,
			raw_deploy_status,
			kpi_deploy_status,
			adv_kpi_deploy_status,
			mixed_kpi_deploy_status,
			id_parser,
			visible,			
			id_ligne)
		VALUES 
		('$edw_group_table',0,1,1,1,0,'$id',0,0,0,0,1,1,$rank)";
		$execCtrl = $execCtrl && (!$database->execute($query) ? false : true);
		
		return $execCtrl;
	}
	
	/**
	 * Met à jour la table sys_definition_group_table_time d'une famille
	 *
	 * @author GHX
	 * @param string $idFamily identifiant de la famille
	 * @param string $idProduct identifiant du produit
	 */
	public function updateGroupTableTime ( $idFamily, $idProduct )
	{
		$execCtrl = true;
		
		// Connexion à la base de données du produit
        // 31/01/2011 BBX bz 20450 : On remplace new DatabaseConnection() par Database::getConnection()
		$database = Database::getConnection($idProduct);
		
		// Calcul des champs
		$queryRank = "SELECT rank FROM sys_definition_categorie WHERE family = '{$idFamily}'";
		$rank = $database->getOne($queryRank);
		
		$queryDelete = "DELETE FROM sys_definition_group_table_time WHERE id_group_table = '{$rank}'";
		$execCtrl = $execCtrl && (!$database->execute($queryDelete) ? false : true);
		
		// Calcul des champs
		$query_max = "SELECT CASE WHEN MAX(id_ligne) IS NULL THEN 1 ELSE MAX(id_ligne)+1 END as nb FROM sys_definition_group_table_time";
		$rank_sdgtt = $database->getOne($query_max);
		
		// 15:40 19/11/2009 GHX
		// Correction d'un problème sur l'agrégation temporelle, en effet le niveau month se calculait sur le niveau week au lieu de day
		$query = "
			SELECT agregation_rank,agregation, source_default 
			FROM sys_definition_time_agregation
			WHERE on_off = 1
			ORDER BY agregation_rank
		";		
		$result = $database->execute($query);
		// Mémorisation des niveaux temporels
		$taList = Array();
		while($array = $database->getQueryResults($result,1)) {
			$taList[$array['agregation']] = $array['source_default'];
		}
		
		$idLigne = $rank_sdgtt;
		foreach(array('raw','kpi') as $type)
		{
			$insertedTa = Array();
			foreach($taList as $ta => $source)
			{
				$idSource = ($ta == 'hour') ? '-1' : $insertedTa[$source];	
				
				$query = "INSERT INTO sys_definition_group_table_time
				(
					id_ligne,
					id_group_table,
					time_agregation,
					time_agregation_label,
					id_source,
					data_type,
					on_off,
					comment,
					deploy_status)
				VALUES 
				($idLigne,$rank,'$ta','$ta',$idSource,'$type',1,null,0)";

				$execCtrl = $execCtrl && (!$database->execute($query) ? false : true);
				$insertedTa[$ta] = $idLigne;
				$idLigne++;
			}
		}
		return $execCtrl;
	} // End function updateGroupTableTime
	
	/** 
	 * Retourne la valeur du edw_group_table de la famille
	 *
	 * @author GHX
	 * @return string
	 */
	public function getEdwGroupTable ()
	{
		return self::$_db->getOne("SELECT edw_group_table FROM sys_definition_group_table WHERE family = '{$this->_idFamily}'");
	} // End function getEdwGroupTable
	
	/**
	 * Retourne le label de la famille
	 *
	 * @author NSE
	 * @param string : identifiant de la famille
	 * @return int
	 */
	public static function getLabel ($id, $product)
	{
		// On mémorise les labels pour éviter des faire tous le temps une requete SQL
		if ( array_key_exists($product, self::$_labelFamily) )
		{
			if ( array_key_exists($id, self::$_labelFamily[$product]) )
			{
				return self::$_labelFamily[$product][$id];
			}
		}
		
        // 31/01/2011 BBX bz 20450 : On remplace new DatabaseConnection() par Database::getConnection()
		$database = Database::getConnection($product);
		$query = "SELECT family_label FROM sys_definition_categorie WHERE family = '$id'";
		self::$_labelFamily[$product][$id] = $database->getOne($query);
		return self::$_labelFamily[$product][$id];
	} // End function getLabel
	
	/**
	 * Met à jour le niveau d'agrégation minimum de la famille
	 *
	 * @author NSE
	 * @param string : identifiant de la famille
	 * @param int : identifiant du produit
	 * @param string : niveau d'agrégation minimum
	 * @return boolean
	 */
	public static function updateAggregationMin ($familyId,$product,$naMin)
	{
        // 31/01/2011 BBX bz 20450 : On remplace new DatabaseConnection() par Database::getConnection()
		$database = Database::getConnection($product);
		$query = "UPDATE sys_definition_categorie set network_aggregation_min='$naMin' WHERE family = '$familyId'";
		return (!$database->execute($query) ? false : true);
	} // End function updateAggregationMin
	
	
	
	// attribue à chaque Na un Level et un rank
	public function attribueNaRankLevel($source,$tableNa,$level,$rank,&$tableNaRankLevel){
		while($na = array_search($source,$tableNa)){
			$tableNaRankLevel[$na]['source']=$source;
			// 09/12/2009 BBX : correction de la récupération du level. BZ 13195
			$tableNaRankLevel[$na]['level']=$tableNaRankLevel[$source]['level']+1;
			$tableNaRankLevel[$na]['rank']=$rank;
			$tableNa[$na] = '';
			$rank++;
			if($na!=$source){
				//array_merge($tableNaRankLevel,self::attribueNaRankLevel($na,$tableNa,$level++,$rank,$tableNaRankLevel));
				self::attribueNaRankLevel($na,$tableNa,$level++,$rank,$tableNaRankLevel);
			}
		}
	} // End function attribueNaRankLevel
	
	/**
	 * Met à jour les niveaux d'agrégation de la famille
	 *
	 *	05/11/2009 GHX
	 *		- Ajout du tableau avec les labels
	 *
	 * @author NSE
	 * @param string : identifiant de la famille
	 * @param int : identifiant du produit
	 * @param array : tableau associatif na => source d'agrégation
	 * @param string : niveau d'agrégation minimum
	 * @param array 
	 * @return boolean $labels tableau des labels array(na => na_label, ...)
	 */
	public static function updateNA ($familyId,$product,$tableNa,$naMin, $labels)
	{
        // 31/01/2011 BBX bz 20450 : On remplace new DatabaseConnection() par Database::getConnection()
		$database = Database::getConnection($product);
		
		// Variable de contrôle
		$execCtrl = true;
		
		// Calcul des champs
		$table = 'sys_definition_network_agregation';
		// on supprimer les anciennes agrégations
		$query = "DELETE from $table where family='$familyId'";
		$execCtrl = $execCtrl && (!$database->execute($query) ? false : true);
		
		//$tableNaRankLevel = self::attribueNaRankLevel($naMin,$tableNa,1,1,array());
		$tableNaRankLevel = array();
		self::attribueNaRankLevel($naMin,$tableNa,1,1,$tableNaRankLevel);
                // 18/03/2011 NSE bz 17516 : on initialise voronoi_polygon_calculation à 1 au lieu de null
		foreach($tableNaRankLevel as $na => $naRankLevel){
			$sdna_id = generateUniqId($table);
			$query = "INSERT INTO $table 
			(
				agregation_rank,
				agregation,
				agregation_type,
				on_off,
				agregation_name,
				agregation_mixed,
				agregation_label,
				agregation_level,
				source_default ,
				level_operand ,
				level_source ,
				mandatory ,
				family,
				voronoi_polygon_calculation ,
				axe ,
				link_to_aa ,
				limit_3rd_axis ,
				na_max_unique ,
				na_parent_unique ,
				third_axis_default_level ,
				sdna_id,
				use_prefix)
			VALUES 
			(".$naRankLevel['rank'].",'$na','text',1,'$na',null,'".$labels[$na]."',".$naRankLevel['level'].",'".$naRankLevel['source']."','".($na==$naMin?'=':'>')."','".$naRankLevel['source']."',1,'$familyId',1,null,null,null,null,1,0,'$sdna_id',null)";
			$execCtrl = $execCtrl && (!$database->execute($query) ? false : true);
			//$rank++;
		}
		
		// Calcul des champs
		$table = 'sys_definition_group_table_network';
		$execCtrl = $execCtrl && (!NaModel::buildGroupTableNetwork($product) ? false : true);
		
		return $execCtrl;
	} // End function updateNA
	
	/**
	 * Retourne le nombre de niveaux d'agrégation de la famille
	 *
	 * @author NSE
	 * @param string : identifiant de la famille
	 * @param int : identifiant du produit
	 * @return int
	 */
	public static function nbNA ($familyId,$product)
	{
        // 31/01/2011 BBX bz 20450 : On remplace new DatabaseConnection() par Database::getConnection()
		$database = Database::getConnection($product);
		$table = 'sys_definition_network_agregation';
		$query = "SELECT COUNT(*) FROM $table WHERE family = '$familyId'";
		return $database->getOne($query);	
	} // End function nbNA
	
	
	/**
	 * Retourne le label de la famille
	 *
	 * @author NSE
	 * @param string : identifiant de la famille
	 * @param string : niveau d'agrégation minimum
	 * @return boolean
	 */
	public static function delete ($familyId,$product)
	{
		// Suppression des graphes/dash/rapport
		$gtms = GTMModel::getAllContainsIdFamily($familyId, $product);
		foreach ( $gtms as  $idGtm => $infoGtm )
		{
			// Récupère la liste des dashboards qui contient le graphe
			$dashboards = DashboardModel::getDashboardFromGTM($idGtm);
			// Si le graphe est présent dans au moins un dashboard
			if ( count($dashboards) > 0 )
			{
				foreach ($dashboards as $dashboard )
				{
					$idDashboard = $dashboard['id_page'];
					// Récupère la liste des rapports qui contient le dashoard
					$reports = ReportModel::getReportsIdFromDashboardId($idDashboard);
					// Si le rapport est présent dans au moins un rapport
					if ( count($reports) > 0 )
					{
						foreach ( $reports as $report )
						{
							$idReport = $report['id_page'];
							$reportModel = new ReportModel($idReport);
							$nameReport = $reportModel->getProperty('page_name');
							
							// Récupère la liste des schedules contenant le rapport
							$schedules = $reportModel->getSchedules();
							if ( count($schedules) > 0 )
							{
								foreach ( $schedules as $schedule )
								{
									$scheduleModel = new ScheduleModel($schedule['schedule_id']);
									// Supprime le rapport du schedule
									$scheduleModel->deleteReport($idReport);
									if ( count($scheduleModel->getProperty('report_id')) == 0 )
									{
										// Suplprime le schedule s'il est vide
										$scheduleModel->delete();
									}
								}
							}
							// Suppression du rapport
							$reportModel->delete();
						}
					}
					// Suppression du dashboard
					$dashModel = new DashboardModel($idDashboard);
					$dashName = $dashModel->getName();
					$dashModel->delete();
				}
			}
			// Suppression du graphe
			$gtmModel = new GTMModel($idGtm);
			$gtmModel->delete();
		}
		
		
		// Variable de contrôle
		$execCtrl = true;
		
        // 31/01/2011 BBX bz 20450 : On remplace new DatabaseConnection() par Database::getConnection()
		$database = Database::getConnection($product);

		// Suppression des RAW
		$query = "DELETE FROM sys_field_reference WHERE edw_group_table = (select edw_group_table from sys_definition_group_table where family = '$familyId')";
		$execCtrl = $execCtrl && (!$database->execute($query) ? false : true);
		// Suppression des KPI
		$query = "DELETE FROM sys_definition_kpi WHERE edw_group_table = (select edw_group_table from sys_definition_group_table where family = '$familyId')";
		$execCtrl = $execCtrl && (!$database->execute($query) ? false : true);
		
		//Suppression des tables de données 
		$query1 = "
			UPDATE sys_definition_group_table 
			SET
				raw_deploy_status = 2,
				kpi_deploy_status = 2
			WHERE family = '$familyId'
			";
		$database->execute($query1);
	
		$query2 = "
			UPDATE sys_definition_group_table_network
			SET
				deploy_status = 2
			WHERE id_group_table = (select id_ligne from sys_definition_group_table where family = '$familyId')
			";
		$database->execute($query2);
		
		$proModel = new ProductModel($product);
		$infoMK = $proModel->getValues();
		
		// Création de la commande pour lancer le déploiement en fonction du produit
		$cmd = 'php -q /home/'.$infoMK['sdp_directory'].'/scripts/deploy.php';
		exec($cmd, $r);
		
		// Suppression des dernières tables qui ne sont pas vidés par le deploie
		$query = "DELETE FROM sys_definition_categorie WHERE family = '$familyId'";
		$execCtrl = $execCtrl && (!$database->execute($query) ? false : true);
		
		$query = "DELETE FROM sys_definition_group_table_ref WHERE family = '$familyId'";
		$execCtrl = $execCtrl && (!$database->execute($query) ? false : true);
		
		$query = "DELETE FROM sys_definition_network_agregation WHERE family = '$familyId'";
		$execCtrl = $execCtrl && (!$database->execute($query) ? false : true);
		
		$query = "DELETE FROM sys_definition_gt_axe WHERE family = '$familyId'";
		$execCtrl = $execCtrl && (!$database->execute($query) ? false : true);

		return $execCtrl;
	} // End function delete
	
	/**
	  * Retourne la liste de toutes les familles activées et visibles d'un produit
	  *
	  * @author GHX
	  * @param int $idProduct identifiant du produit pour lequel on veut récupérer la liste des familles (defaut le master produit)
	  * @return array
	  */
	public static function getAllFamilies ( $idProduct = '' )
	{
        // 31/01/2011 BBX bz 20450 : On remplace new DatabaseConnection() par Database::getConnection()
		$db = Database::getConnection($idProduct);

		$sql = "
			SELECT 
				sdc.family,
				sdc.family_label,
				sdc.main_family,
				sdc.network_aggregation_min,
				sdc.rank,
				sdgt.edw_group_table
			FROM 
				sys_definition_categorie AS sdc 
				LEFT JOIN sys_definition_group_table AS sdgt ON (sdc.rank = sdgt.id_ligne)
			WHERE
				sdc.on_off = 1
				AND sdc.visible = 1
			ORDER BY
				sdc.rank
			";

		$results = $db->execute($sql);

		$families = array();
		if ( $db->getNumRows() > 0 )
		{
			while ( $family = $db->getQueryResults($results, 1) )
			{
				$families[$family['family']] = array(
								'id' => $family['rank'],
								'label' => $family['family_label'],
								'isMainFamily' => $family['main_family'],
								'naMin' => $family['network_aggregation_min'],
								'edwGroupTable' => $family['edw_group_table'],
								'code' => $family['family']
					);
			}
		}

		return $families;
	} // End function getAllFamilies
	
	 /**
	  * Retourne le nombre de familles activées et visibles d'un produit
	  *
	  * @author NSE
	  * @param int $idProduct identifiant du produit pour lequel on veut compter les familles (defaut le master produit)
	  * @return array
	  */
	 public static function getNbFamilies ( $idProduct = '' )
	 {
           // 31/01/2011 BBX bz 20450 : On remplace new DatabaseConnection() par Database::getConnection()
           $db = Database::getConnection($idProduct);

	   $sql = "
			SELECT 
				count(*)
			FROM 
				sys_definition_categorie AS sdc 
				LEFT JOIN sys_definition_group_table AS sdgt ON (sdc.rank = sdgt.id_ligne)
			WHERE
				sdc.on_off = 1
				AND sdc.visible = 1
			";

	   return $db->getOne($sql);
	} // End function getNbFamilies

        /**
	 * Retourne l'identifiant (string, code de la famille) de la famille en cours
	 *
	 * @author SCT
	 * @return string
	 */
	public function getIdFamily()
	{
            return $this->_idFamily;

	} // End function getIdFamily

	/**
	 * Retourne la liste des NA commun aux différentes familles des différents produits
	 *
	 *	10:24 15/10/2009 GHX
	 *		- Modification de la fonction ne gérè que les NA communs entre les produits et non entre familles et produits
	 *
	 * @author NSE
	 * @param array : liste associative des familles concernées par produits (prod1=>(fam1,fam2,fam3), prod2=>(fam2,fam3,fam4))
	 * @return array
	 */
	public static function getCommonNaBetweenFamilyAndProducts ($familyList)
	{
		$na_levels_family = array();
		$na_levels_in_common = array();
		$firstFamily = true;
		// liste des NA communs aux différents produits
		// pour chaque produit
		foreach ($familyList as $product => $families)
		{
			$na_levels_family = array();
			// pour chaque famille 	//get_network_aggregation_from_family(fam,prod);
			foreach ($families as $family)
			{
				// récupère la liste des na de la famille sur le produit
				$na_levels_family = array_merge($na_levels_family,getNaLabelListForProduct('na',$family,$product));
			}
			//regroupe les na sans distinction de famille
			foreach ( $na_levels_family as $nalf )
			{
				if ( $firstFamily )
				{
					$na_levels_in_common = $nalf;
					$firstFamily = false;
				}
				else
				{
					$na_levels_in_common = array_intersect_key($na_levels_in_common,$nalf);
				}
			}
		}
		
		if ( count($na_levels_in_common) > 0 )
		{
			return $na_levels_in_common;
		}
		return false;
	} // End function getCommonNaBetweenFamilyAndProducts
	
	/**
	 * Retourne TRUE si le label existe déjà sinon FALSE, insensible à la case
	 *
	 * @author GHX
	 * @param string $label label de la famille
	 * @param string $idProduct ID du produit sur lequel on doit vérifier le label (defaut le master produit)
	 * @param string $idFamily : ID de famille pour laquelle on ne doit pas vérifier (default null) ceci dans le cas où on c'est que le label est utilisé par cette famille
	 * @return boolean
	 */
	public static function labelExists ( $label, $idProduct = null, $idFamily = null )
	{
        // 31/01/2011 BBX bz 20450 : On remplace new DatabaseConnection() par Database::getConnection()
		$db = Database::getConnection($idProduct);
		$query = "SELECT * FROM sys_definition_categorie WHERE family_label ILIKE '{$label}'";
		if ( $idFamily != null )
		{
			$query .= " AND family != '{$idFamily}'";
		}
		$db->execute($query);
		
		if ( $db->getNumRows() == 0 )
			return false;
		
		return true;
	} // End function 

    /**
     * Retourne le nombre max de colonnes supprimées pour la famille
     *
     * @since 5.0.5.00
     * @param  string $dataType
     * @return integer
     */
    public function getMaxNumberOfDroppedColumns( $dataType = '' )
    {
        // Table condition
        $table = $this->getEdwGroupTable();
        if( !empty( $dataType ) ){
            $table .= "_{$dataType}_%";
        }
        else{
            $table .= "_%";
        }

        // Query
        $query = "SELECT MAX(t0.nbcols)
        FROM (SELECT t.tablename, count(a.attname) as nbcols
            FROM pg_class c, pg_attribute a, pg_tables t
            WHERE a.attrelid = c.oid
            AND c.relname = t.tablename
            AND t.schemaname = 'public'
            AND a.attnum >= 0
            AND a.attisdropped = true
            AND c.relname LIKE '$table'
            GROUP BY t.tablename) t0";

        // Return value
        return (int)self::$_db->getOne($query);
    }

    /**
     * Retourne le nombre maximal de colonnes comptées pour la famille
     *
     * @since 5.0.5.00
     * @param  string $dataType
     * @return integer
     */
    public function getMaxNumberOfColumns($dataType = '')
    {
        // Table condition
        $table = $this->getEdwGroupTable();
        if( !empty( $dataType ) )
        {
            $table .= "_{$dataType}_%";
        }
        else
        {
            $table .= "_%";
        }


        // Query
        $query = "SELECT MAX(t0.nbcols)
        FROM (SELECT t.tablename, count(a.attname) as nbcols
            FROM pg_class c, pg_attribute a, pg_tables t
            WHERE a.attrelid = c.oid
            AND c.relname = t.tablename
            AND t.schemaname = 'public'
            AND a.attnum >= 0
            AND c.relname LIKE '$table'
            GROUP BY t.tablename) t0";

        // Return value
        return (int)self::$_db->getOne($query);
    }

    /**
     * Récupère les tables de données liées à la famille
     *
     * @since 5.0.5.00
     * @param  string $dataType
     * @param bool excludePartitions
     * @return array
     */
    public function getRelatedTables($dataType = '', $excludePartitions = false)
    {
        // Table list
        $tableList = array();

        // Table condition
        $table = $this->getEdwGroupTable();
        if( !empty( $dataType ) )
        {
            $table .= "_{$dataType}_%";
        }
        else
        {
            $table .= "_%";
        }

        // Query
        $query = "SELECT tablename
            FROM pg_tables
            WHERE schemaname = 'public'
            AND tablename LIKE '$table'
            ORDER BY tablename ASC";

        // 27/06/2011 BBX
        // Adding possibility to exclude partitions
        // BZ 22721
        if($excludePartitions) {
            $query = "SELECT t.tablename
            FROM pg_tables t, pg_class c
            WHERE t.tablename = c.relname
            AND t.schemaname = 'public'
            AND t.tablename LIKE '$table'
            AND c.oid NOT IN (SELECT inhrelid FROM pg_inherits)
            ORDER BY t.tablename ASC";
        }

        $result = self::$_db->execute($query);
        while($row = self::$_db->getQueryResults($result,1)) {
            $tableList[] = $row['tablename'];
        }

        // Return value
        return $tableList;
    }

    /**
     * Retourne les infos de définition de la Busy Hour pour la famille
     *
     * @since 5.0.5.06
     * @return array Tableau associatif contenant toutes les informations
     */
    public function getBHInfos()
    {
        $query    = " SELECT * FROM sys_definition_time_bh_formula WHERE family='{$this->_idFamily}'";
        $retArray = array();
        $result   = self::$_db->execute( $query );

        if( self::$_db->getNumRows() > 0 )
        {
            $retArray = self::$_db->getQueryResults( $result,1 );
        }
        return $retArray;
    }

    /**
     * Retourne la Busy Hour pour une jour donné (et un couple NA/NE).
     *
     * @since 5.0.6.00
     *
     * @param  string $day Jour à analyse (yyyymmdd)
     * @param  string $na Nom du Netwotk Aggregation 3ème axe
     * @param  string $ne Identifiant du Netwotk Element 3ème axe
     * @param  string $na_axe3 Nom du Netwotk Aggregation 3ème axe
     * @param  string $ne_axe3 Identifiant du Netwotk Element 3ème axe
     * @return string Hour de BH (yyyymmddhh) ou false
     */
    public function getBHValueFromDay( $date, $na, $ne, $na_axe3 = NULL, $ne_axe3 = NULL )
    {
        $module       = get_sys_global_parameters( 'module', 'def', self::$_idProduct );
        $bhInfos      = $this->getBHInfos();
        $bhRawKpiType = strtolower( $bhInfos['bh_indicator_type'] );

        // Si le module trouvé est 'def', on cherche le 'old_module'
        if( $module == 'def' )
        {
            // Si old_module n'existe pas, 'def' sera pris par défaut
            $module = get_sys_global_parameters( 'old_module', 'def', self::$_idProduct );
        }

        // Test du format de la date fourni
        if ( strlen( $date ) !== 8 || !checkdate( intval( substr( $date, 4, 2 ) ), intval( substr( $date, 6, 2 ) ), intval( substr( $date, 0, 4 ) ) ) )
        {
            // Si la date est mal formé ou invalide, on quitte la méthode
            return false;
        }

        // Test de l'existance du Network Element sur le produit
        if ( !NeModel::exists( $ne, $na, self::$_idProduct ) )
        {
            // Le NE n'existe pas sur le produit, il est peut mappé
            $mapped = NeModel::getMapped( array( $na ), false, self::$_idProduct );
            if ( count( $mapped ) > 0 && in_array( $ne, array_keys( $mapped[$na] ) ) )
            {
                // Si le NE est effectivement mappé, on utilise son id d'origine
                $ne = $mapped[$na][$ne];
            }
            else
            {
                // Si le NE n'existe pas sur le produit ET qu'il n'est pas mappé
                // on retourne false (rien ne sert d'exécuter la requête qui
                // echouera obligatoirement.
                return false;
            }
        }

        // Création du nom de la table source et de la requête
        // (en fonction de la présence d'éléments troisième axe ou non)
        if( $na_axe3 == NULL && $ne_axe3 == NULL )
        {
            $tableName = "edw_{$module}_{$this->_idFamily}_axe1_{$bhRawKpiType}_{$na}_day_bh";
            $query = "SELECT bh FROM {$tableName} WHERE {$na}='{$ne}' AND day_bh=".intval( $date );
        }
        else
        {
            $tableName = "edw_{$module}_{$this->_idFamily}_axe1_{$bhRawKpiType}_{$na}_{$na_axe3}_day_bh";
            $query = "SELECT bh FROM {$tableName} WHERE {$na}='{$ne}' AND {$na_axe3}='{$ne_axe3}' AND day_bh=".intval( $date );
        }

        // Création de la requête, si une erreur est présente dans la requête,
        // du à de mauvais paramètres, getOne retournera false.
        return self::$_db->getOne( $query );
    }

    /**
     * Retourne la liste des familles ayant le ou les NA fournis en paramètre
     *
     * @since 5.0.6.00
     * @param array $naCode Liste de codes de Network Agregation
     * @return array Nom des familles
     */
    public static function getFamiliesFromNa( array $naCode, $idProduct = '' )
    {
        $retArray = null;
        $db       = Database::getConnection( $idProduct );

        foreach( $naCode as $na )
        {
            $tmpArray = array();
            $query = "SELECT family FROM sys_definition_network_agregation WHERE agregation='{$na}'";
            $res = $db->executeQuery( $query );
            while( $row = $db->getQueryResults( $res, 1 ) )
            {
                $tmpArray []= $row['family'];
            }
            if( $retArray === null )
            {
                $retArray = $tmpArray;
            }
            else
            {
                $retArray = array_intersect( $retArray, $tmpArray );
            }
        }
        return $retArray;
    }
    
    /** 
     * Retourne le code famille en fontion de l'id group table
     * @param type $idg
     * @param type $idProduct
     * @return type 
     */
    public static function getFamilyFromIdGroupTable($idg, $idProduct = '')
    {
        $db = Database::getConnection($idProduct);
        $query = "SELECT family FROM sys_definition_group_table WHERE id_ligne = $idg";
        return $db->getOne($query);
    }
}