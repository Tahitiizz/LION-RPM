<?php
/**
 * 27/08/2009 GHX
 *		- Modification de la fonction checkValues() pour ne faire la vérification du répertoire uniquement si on se trouve sur le bon serveur (impossible de faire le teste sur le serveur distant)
 *			= BZ 11306
 * 28/08/2009
 *		- Correction du bug 11271 : On ajoute les espaces et - dans les caractères autorisés et On remplace les espaces ou - dans le nom de l'export dans le fichier de topo généré
 * 22/09/2009 GHX
 *		- Modification concernant la connexion à la base, on mémorise aussi l'id du produit sur lequel la connexion a été faite
 *
 * 29/09/2009 BBX : updateAutomaticDataExport, updateDataList : modification de la récupération des raw/kpi
 *
 * 21/10/2009 GHX
 *		- Modifcation de la fonction updateAutomaticDataExport() pour prendre en compte le fichier de conf de Mixed KPI
 *		- Prise en compte de la colonne add_suffix
 *		- Prise en compte que la colonne add_prefix est maintenant au format text et non plus integer
 *
 * 10/12/2009 BBX
 *		- Si le fichier de conf est vide, on ne renvoie plus d'erreur, mais on ne fait que supprimer les Data Export auto existants. BZ 13408
 *
 * 27/07/2010 OJT : Correction bz16774
 */
?>
<?php
/**
*	Classe permettant de manipuler les Data Export
*	Travaille sur la table sys_export_raw_kpi_config, sys_export_raw_kpi_data, sys_field_reference, sys_definition_kpi, sys_definition_group_table
*
*	@author	BBX - 16/07/2009
*	@version	CB 5.0.0.00
*	@since	CB 5.0.0.00
*
*
*/
class DataExportModel
{
	/*
	*	Constantes
	*/
	// Table de config
	const CONFIG_TABLE = 'sys_export_raw_kpi_config';
	
	// Table de données
	const DATA_TABLE = 'sys_export_raw_kpi_data';
	
	// Table des compteurs
	const RAW_TABLE = 'sys_field_reference';
	
	// Table des KPIS
	const KPI_TABLE = 'sys_definition_kpi';
	
	// Table conf famille
	const GROUP_TABLE = 'sys_definition_group_table';
	
	// Valeurs par défaut d'un Data Export
	const DEFAULT_TARGET_DIR = REP_PHYSIQUE_NIVEAU_0;
	const DEFAULT_TARGET_FILE = 'export.csv';
	const DEFAULT_FIELD_SEPARATOR = ';';
	const DEFAULT_TIME_AGGREGATION = 'hour';
	const DEFAULT_NETWORK_AGGREGATION = '';
	const DEFAULT_HOME_NETWORK = '';
	const DEFAULT_WITH_HEADER = '1';
	const DEFAULT_EXPORT_NAME = '';
	const DEFAULT_ON_OFF = '1';
	const DEFAULT_FAMILY = '';
	const DEFAULT_GENERATE_HOUR_ON_DAY = '0';
	const DEFAULT_SELECT_PARENTS = '0';
	const DEFAULT_USE_CODE = '0';
	const DEFAULT_USE_CODE_NA = '0';
	const DEFAULT_NA_AXE3 = '';
	const DEFAULT_ADD_TOPO_FILE = '0';
	const DEFAULT_ADD_RAW_KPI_FILE = '0';
	const DEFAULT_VISIBLE = '1';
	const DEFAULT_ADD_PREFIX = '';
	const DEFAULT_ID_PRODUCT = '0';
	const DEFAULT_USE_CODEQ = '0';
	const DEFAULT_ADD_SUFFIX = '';
	/**
	 * Type de Data Export
	 *	0 = normal
	 *	1 = Data Export pour le Corporate
	 *	1 = Data Export pour le produit Mixed KPI
	 */
	const DEFAULT_EXPORT_TYPE = '0';
	

	/*
	*	Variables
	*/	
	// Id de l'export
	private $exportId = 0;
	
	// Id du produit
	private $productId = 0;
	
	// Valeurs de config du Data Export
	private $exportConfig = Array();

	// Mémorise l'erreur de récupération d'un data export
	private $error = false;

	// Instance de DatabaseConnection
	private static $database;
	private static $productIdDb;

	/**
	*	Constructeur
	*	@param string : id de l'export
	*	@param int : id du produit
	**/	
	public function __construct($exportId,$productId=0)
	{
		// Connection  la base de données du produit concerné par l'export
		if ( empty(self::$database) || self::$productIdDb != $productId )
		{
			self::$database = Database::getConnection( $productId );
			self::$productIdDb = $productId;
		}
		// Mémorisation de l'id de l'export
		$this->exportId = $exportId;
		// Récupération de l'export
		$query = "SELECT * FROM ".self::CONFIG_TABLE." WHERE export_id = '{$exportId}'";
		$array = self::$database->getRow($query);
		// Mémorisation des valeurs dans l'objet
		if(count($array) > 0) {
			$this->exportConfig = $array;
		}
		
		// Si la récupération des valeurs à échouée, on mémorise l'erreur
		else $this->error = true;
	}

	/**
	*	Retourne le statut de l'instanciation
	*	@return bool : statut (false = pas d'erreur)
	**/
	public function getError()
	{
		return $this->error;
	}

	/**
	*	Retourne une valeur ou le tableau de config de l'export
	*	@param string : champ à récupérer ou vide pour tout récupérer
	*	@return Array : tableau de config
	**/		
	public function getConfig($key='')
	{
		// Si pas de champ demandé, on renvoie tout le tableau de config
		if($key == '') {
			return $this->exportConfig;
		}
		// Si un champ particulié est demandé et qu'il existe, on renvoie sa valeur
		elseif(isset($this->exportConfig[$key])) {
			return $this->exportConfig[$key];
		}
		// Sinon on retourne false
		else {
			return false;
		}
	}

	/**
	*	Retourne le tableau des raws de l'export
	*	@return Array : retourne le tableau des raws
	**/		
	public function getRaws($getLabels=true)
	{
		// Déclaration du tableau de compteurs
		$rawsArray = Array();
		// Si l'export existe, on récupère les compteurs
		if(!$this->error)
		{
			if($getLabels) 
				// Récupération des labels (par défaut)
				$query = "SELECT d.raw_kpi_id AS id, r.edw_field_name_label AS label ";
			else 
				// Récupération des noms
				$query = "SELECT d.raw_kpi_id AS id, r.edw_field_name AS name ";
			$query .= "FROM ".self::DATA_TABLE." d, ".self::RAW_TABLE." r
			WHERE d.raw_kpi_id = r.id_ligne
			AND d.raw_kpi_type = 'raw'
			AND r.on_off = 1
			AND r.new_field = 0
			AND d.export_id = '{$this->exportId}'
			ORDER BY d.ordre";
			$result = self::$database->execute($query);
			while($values = self::$database->getQueryResults($result,1)) {
				if($getLabels) 
					// Récupération des labels (par défaut)
					$rawsArray[$values['id']] = $values['label'];
				else 
					// Récupération des noms
					$rawsArray[$values['id']] = $values['name'];
			}
		}
		// Retour du tableau
		return $rawsArray;
	}

	/**
	*	Retourne le tableau des kpis de l'export
	*	@return Array : retourne le tableau des kpis
	**/		
	public function getKpis($getLabels=true)
	{
		// Déclaration du tableau de kpis
		$kpisArray = Array();
		// Si l'export existe, on récupère les kpis
		if(!$this->error)
		{
			if($getLabels)
				// Récupération des labels (par défaut)
				$query = "SELECT d.raw_kpi_id AS id, k.kpi_label AS label ";
			else
				// Récupération des noms
				$query = "SELECT d.raw_kpi_id AS id, k.kpi_name AS name ";
			$query .= "FROM ".self::DATA_TABLE." d, ".self::KPI_TABLE." k
			WHERE d.raw_kpi_id = k.id_ligne
			AND d.raw_kpi_type = 'kpi'
			AND k.on_off = 1
			AND k.new_field = 0
			AND d.export_id = '{$this->exportId}'
			ORDER BY d.ordre";
			$result = self::$database->execute($query);
			while($values = self::$database->getQueryResults($result,1)) {
				if($getLabels)
					// Récupération des labels (par défaut)
					$kpisArray[$values['id']] = $values['label'];
				else
					// Récupération des noms
					$kpisArray[$values['id']] = $values['name'];
			}
		}
		// Retour du tableau
		return $kpisArray;
	}

	/**
	 *	Retourne le tableau des kpis qui ne sont pas dans de l'export (KPIs disponibles)
	 *	@return Array : retourne le tableau des kpis
	 */
	public function getAvailableKpis()
	{
		// Déclaration du tableau de kpis
		$kpisArray = Array();
		// Si l'export existe, on récupère les kpis
		if(!$this->error)
		{
			// Récupération de la famille
			$family = $this->getConfig('family');
			// Requête
                        // 27/07/2010 OJT : Correction bz16774
			$query = "SELECT id_ligne as id, kpi_label as label
			FROM ".self::KPI_TABLE."
			WHERE id_ligne NOT IN 
			(
				SELECT raw_kpi_id FROM ".self::DATA_TABLE."
				WHERE raw_kpi_type = 'kpi'
				AND export_id = '{$this->exportId}'
			)
			AND edw_group_table = 
			(
				SELECT edw_group_table 
				FROM ".self::GROUP_TABLE."
				WHERE family = '{$family}'
				LIMIT 1
			)
			AND on_off = 1
                        AND new_field = 0
			AND kpi_name != 'RI_CAPTURE_DURATION'
			ORDER BY kpi_label";
			$result = self::$database->execute($query);
			while($values = self::$database->getQueryResults($result,1)) {
				$kpisArray[$values['id']] = $values['label'];
			}
		}
		// Retour du tableau
		return $kpisArray;
	}
	
	/**
	*	Retourne le tableau des raws qui ne sont pas dans de l'export (Raws disponibles)
	*	@return Array : retourne le tableau des raws
	**/		
	public function getAvailableRaws()
	{
		// Déclaration du tableau de raws
		$rawsArray = Array();
		// Si l'export existe, on récupère les raws
		if(!$this->error)
		{
			// Récupération de la famille
			$family = $this->getConfig('family');
			// Requête
			// 17/09/2009 BBX : on affiche désormais les compteurs capture_duration
			$query = "SELECT id_ligne as id, edw_field_name_label as label
			FROM ".self::RAW_TABLE."
			WHERE id_ligne NOT IN 
			(
				SELECT raw_kpi_id FROM ".self::DATA_TABLE."
				WHERE raw_kpi_type = 'raw'
				AND export_id = '{$this->exportId}'
			)
			AND edw_group_table = 
			(
				SELECT edw_group_table 
				FROM ".self::GROUP_TABLE."
				WHERE family = '{$family}'
				LIMIT 1
			)
			AND on_off = 1
			AND visible = 1
			--AND edw_field_name NOT ILIKE 'capture_duration%'
			ORDER BY edw_field_name_label";
			$result = self::$database->execute($query);
			while($values = self::$database->getQueryResults($result,1)) {
				$rawsArray[$values['id']] = $values['label'];
			}
		}
		// Retour du tableau
		return $rawsArray;
	}
	
	/**
	*	Met à jour un paramètre de configuration
	*	@param string : nom du champ
	*	@param variable : valeur du champ
	**/
	public function setConfig($key,$value)
	{
		// Si l'export existe
		if(!$this->error)
		{
			// Si le champ à modifier existe, on le met à jour
			if(array_key_exists($key,$this->exportConfig))
			{
				// Récupération du type du champ
				$dataType = self::$database->getFieldType(self::CONFIG_TABLE,$key);
				// Tableau des différents types chaine de postgresql
				$PGStringTypes = Array('character varying','varchar','character','char','text');		
				// Mise à jour en base
				$query = "UPDATE ".self::CONFIG_TABLE."
				SET {$key} = ".((in_array($dataType,$PGStringTypes)) ? "'".$value."'" : $value)."
				WHERE export_id = '{$this->exportId}'";
				self::$database->execute($query);
				// Mise à jour dans l'objet
				$this->exportConfig[$key] = $value;
			}
		}
	}

	/**
	*	Met à jour la liste des Raw de l'export
	*	@param array : tableau des compteurs
	**/	
	public function setRawList($rawsArray)
	{
		// Si l'export existe
		if(!$this->error)
		{
			// On commence par supprimer la liste existante
			$query = "DELETE FROM ".self::DATA_TABLE."
			WHERE raw_kpi_type = 'raw'
			AND export_id = '{$this->exportId}'";
			self::$database->execute($query);
			// On peut ensuite insérer nos nouveaux compteurs
			$ordre = 0;
			foreach($rawsArray as $rawKpiId)
			{
				// Insertion du compteur
				$query = "INSERT INTO ".self::DATA_TABLE."
				(
					export_id,
					raw_kpi_id,
					raw_kpi_type,
					ordre			
				)
				VALUES
				(
					'{$this->exportId}',
					'{$rawKpiId}',
					'raw',
					{$ordre}				
				)";
				self::$database->execute($query);
				// Incrémentation de la variable d'ordre
				$ordre++;
			}
		}		
	}
	
	/**
	*	Met à jour la liste des Kpis de l'export
	*	@param array : tableau des kpis
	**/	
	public function setKpiList($kpisArray)
	{
		// Si l'export existe
		if(!$this->error)
		{
			// On commence par supprimer la liste existante
			$query = "DELETE FROM ".self::DATA_TABLE."
			WHERE raw_kpi_type = 'kpi'
			AND export_id = '{$this->exportId}'";
			self::$database->execute($query);
			// On peut ensuite insérer nos nouveaux compteurs
			$ordre = 0;
			foreach($kpisArray as $rawKpiId)
			{
				// Insertion du compteur
				$query = "INSERT INTO ".self::DATA_TABLE."
				(
					export_id,
					raw_kpi_id,
					raw_kpi_type,
					ordre			
				)
				VALUES
				(
					'{$this->exportId}',
					'{$rawKpiId}',
					'kpi',
					{$ordre}				
				)";
				self::$database->execute($query);
				// Incrémentation de la variable d'ordre
				$ordre++;
			}
		}		
	}

	/**
	*	Supprime l'export
	**/	
	public function deleteExport()
	{
		// Si l'export existe
		if(!$this->error)
		{
			// Suppression dans la table de config
			$query = "DELETE FROM ".self::CONFIG_TABLE."
			WHERE export_id = '{$this->exportId}'";
			self::$database->execute($query);
			// Suppression dans la table de données
			$query = "DELETE FROM ".self::DATA_TABLE."
			WHERE export_id = '{$this->exportId}'";
			self::$database->execute($query);
			// l'export n'existe plus. On passe en mode error
			$this->error = true;
		}
	}
	
	/******************** STATIC FUNCTIONS *********************/
	
	/**
	*	Créé un nouveau Data Export
	*	@param string : famille
	*	@param int : id du produit
	*	@return string : retourne l'id du nouvel export
	**/		
	public static function create($family,$productId='')
	{
		// Instanciation de DatabaseConnection
		$database = Database::getConnection( $productId );
		// Génération de l'id Data Export
		$exportId = generateUniqId(self::CONFIG_TABLE);
		// On prépare la requête qui va créer un export avec des valeurs par défaut.
		$query = "INSERT INTO ".self::CONFIG_TABLE."
		(	
			export_id,
			target_dir,
			target_file,
			field_separator,
			time_aggregation,
			network_aggregation,
			home_network,
			with_header,
			export_name,
			on_off,
			family,
			generate_hour_on_day,
			select_parents,
			use_code,
			use_code_na,
			na_axe3,
			add_topo_file,
			add_raw_kpi_file,
			visible,
			add_prefix,
			id_product,
			use_codeq,
			add_suffix,
			export_type
		)
		VALUES 
		(
			'{$exportId}',
			".((self::DEFAULT_TARGET_DIR == '') ? "NULL" : "'".self::DEFAULT_TARGET_DIR."'").",
			".((self::DEFAULT_TARGET_FILE == '') ? "NULL" : "'".self::DEFAULT_TARGET_FILE."'").",
			".((self::DEFAULT_FIELD_SEPARATOR == '') ? "NULL" : "'".self::DEFAULT_FIELD_SEPARATOR."'").",
			".((self::DEFAULT_TIME_AGGREGATION == '') ? "NULL" : "'".self::DEFAULT_TIME_AGGREGATION."'").",
			".((self::DEFAULT_NETWORK_AGGREGATION == '') ? "NULL" : "'".self::DEFAULT_NETWORK_AGGREGATION."'").",
			".((self::DEFAULT_HOME_NETWORK == '') ? "NULL" : "'".self::DEFAULT_HOME_NETWORK."'").",
			".((self::DEFAULT_WITH_HEADER == '') ? "NULL" : self::DEFAULT_WITH_HEADER).",
			".((self::DEFAULT_EXPORT_NAME == '') ? "NULL" : "'".self::DEFAULT_EXPORT_NAME."'").",
			".((self::DEFAULT_ON_OFF == '') ? "NULL" : self::DEFAULT_ON_OFF).",
			'{$family}',
			".((self::DEFAULT_GENERATE_HOUR_ON_DAY == '') ? "NULL" : self::DEFAULT_GENERATE_HOUR_ON_DAY).",
			".((self::DEFAULT_SELECT_PARENTS == '') ? "NULL" : self::DEFAULT_SELECT_PARENTS).",
			".((self::DEFAULT_USE_CODE == '') ? "NULL" : self::DEFAULT_USE_CODE).",
			".((self::DEFAULT_USE_CODE_NA == '') ? "NULL" : self::DEFAULT_USE_CODE_NA).",
			".((self::DEFAULT_NA_AXE3 == '') ? "NULL" : "'".self::DEFAULT_NA_AXE3."'").",
			".((self::DEFAULT_ADD_TOPO_FILE == '') ? "NULL" : self::DEFAULT_ADD_TOPO_FILE).",
			".((self::DEFAULT_ADD_RAW_KPI_FILE == '') ? "NULL" : self::DEFAULT_ADD_RAW_KPI_FILE).",			
			".((self::DEFAULT_VISIBLE == '') ? "NULL" : self::DEFAULT_VISIBLE).",
			".((self::DEFAULT_ADD_PREFIX == '') ? "NULL" : "'".self::DEFAULT_ADD_PREFIX."'").",
			".((self::DEFAULT_ID_PRODUCT == '') ? "NULL" : self::DEFAULT_ID_PRODUCT).",
			".((self::DEFAULT_USE_CODEQ == '') ? "NULL" : self::DEFAULT_USE_CODEQ).",
			".((self::DEFAULT_ADD_SUFFIX == '') ? "NULL" : "'".self::DEFAULT_ADD_SUFFIX."'").",
			".((self::DEFAULT_EXPORT_TYPE == '') ? "0" : self::DEFAULT_EXPORT_TYPE)."
		)";
		// Insertion du nouvel export
		$database->execute($query);
		// Retour de l'id Data Export
		return $exportId;
	}
	
	/**
	*	Retourne le tableau des raws actifs d'une famille d'un produit donné
	*	@param string : famille
	*	@param int : id du produit
    *   @param boolean : permet de définir si on récupère le compteur flag ou non
	*	@return Array : retourne le tableau des raws
	**/		
	public static function getAllRaws( $family, $productId='', $flag=false )
	{
		// Instanciation de DatabaseConnection
		if ( empty(self::$database) || self::$productIdDb != $productId )
		{
			self::$database = Database::getConnection( $productId );
			self::$productIdDb = $productId;
		}
		// Déclaration du tableau de Raws
		$rawsArray = Array();

        // 07/09/2010 BBX
        // DE Bypass : dans le cas d'un Corporate on doit récupérer les flags
        $visibleCondition = "AND visible = 1";
        if($flag) $visibleCondition = "AND (visible = 1 OR nms_field_name = 'flag')";

		// On récupère les raws
		// 17/09/2009 BBX : on affiche désormais les compteurs capture_duration
		$query = "SELECT id_ligne AS id, edw_field_name_label AS label
		FROM ".self::RAW_TABLE."
		WHERE edw_group_table = 
		(
			SELECT edw_group_table 
			FROM ".self::GROUP_TABLE."
			WHERE family = '{$family}'
			LIMIT 1
		)
		AND on_off = 1
		$visibleCondition
		AND new_field = 0
		--AND edw_field_name NOT ILIKE 'capture_duration%'
		ORDER BY edw_field_name_label";
		$result = self::$database->execute($query);
		while($values = self::$database->getQueryResults($result,1)) {
            // 07/09/2010 BBX
            // DE Bypass : dédoublonnage du tableau
            if(!in_array($values['label'],$rawsArray))
			    $rawsArray[$values['id']] = $values['label'];
		}
		// Retour du tableau
		return $rawsArray;
	}
	
	/**
	*	Retourne le tableau des kpis actifs d'une famille d'un produit donné
	*	@param string : famille
	*	@param int : id du produit
	*	@return Array : retourne le tableau des kpis
	**/		
	public static function getAllKpis($family,$productId='')
	{
		// Instanciation de DatabaseConnection
		if ( empty(self::$database) || self::$productIdDb != $productId )
		{
			self::$database = Database::getConnection( $productId );
			self::$productIdDb = $productId;
		}
		// Déclaration du tableau de kpis
		$kpisArray = Array();
		// On récupère les kpis
		$query = "SELECT id_ligne AS id, kpi_label AS label
		FROM ".self::KPI_TABLE."
		WHERE edw_group_table = 
		(
			SELECT edw_group_table 
			FROM ".self::GROUP_TABLE."
			WHERE family = '{$family}'
			LIMIT 1
		)
		AND on_off = 1
		AND new_field = 0
		AND kpi_name != 'RI_CAPTURE_DURATION'
		ORDER BY kpi_label";
		$result = self::$database->execute($query);
		while($values = self::$database->getQueryResults($result,1)) {
			$kpisArray[$values['id']] = $values['label'];
		}
		// Retour du tableau
		return $kpisArray;
	}

	/**
	*	Retourne un tableau de tous les export de la famille avec en clé l'id de l'export
	*	@param string : famille
	*	@param int : id du produit
	*	@bool : vrai si on récupère même les exports cachés
	*	@return Array : retourne le tableau des export
	**/		
	public static function getExportList($family='',$productId='',$getAll=false)
	{
		// Instanciation de DatabaseConnection
		if ( empty(self::$database) || self::$productIdDb != $productId )
		{
			self::$database = Database::getConnection( $productId );
			self::$productIdDb = $productId;
		}
		// Déclaration du tableau des exports
		$exportArray = Array();
		// Requête
		$query = "SELECT * FROM ".self::CONFIG_TABLE."
		WHERE on_off = 1";
		$query .= ($getAll) ? "" : " AND visible = 1";
		if($family != '') $query .= " AND family = '{$family}'";
		$query .= " ORDER BY export_name, time_aggregation";
		foreach(self::$database->getAll($query) as $arrayExport) {
			$exportArray[$arrayExport['export_id']] = $arrayExport;
		}
		// Retour du tableau
		return $exportArray;
	}
	
	/**
	*	Vérifie que les valeurs d'un data export sont valides
	*	@param array : tableau des valeurs (ie : variable POST du formulaire)
	*	@return mixed : true si pas d'erreur, ou message d'erreur
	**/	
	public static function checkValues($valueArray)
	{
		// Contrôle du nom de l'export
		// maj 28/08/2009 - Correction du bug 11271 : On ajoute les espaces et - dans les caractères autorisés
		if(trim($valueArray['export_name']) == '' || ereg('[^a-zA-Z0-9_ -]',$valueArray['export_name'])) {
			return __T("A_DATA_EXPORT_FILL_EXPORT_NAME_FIELD");
		}
		// Contrôle du nom du fichier
		if(trim($valueArray['target_file']) == '' || !ereg('^[a-zA-Z0-9_]+\.csv$',$valueArray['target_file'])) {
			return __T("A_DATA_EXPORT_FILL_TARGET_FIELD");
		}		
		// Contrôle de saisie d'au moins un raw ou un kpi
		if(empty($valueArray['hidden_counters_selected']) && empty($valueArray['hidden_kpis_selected'])) {
			return __T('A_TASK_SCHEDULER_DATA_EXPORT_SELECT_RAW_KPIS');
		}

		// 14/12/2009 BBX
		// Contrôle de l'unicité du nom de l'export BZ 13261
		foreach(self::getExportList('',$valueArray['product'],true) as $export)
		{
			if(($valueArray['export_name'] == $export['export_name']) && ($valueArray['export_id'] != $export['export_id']))
			{
				return 'Data Export "'.$export['export_name'].'" already exists';
			}			
		}
		
		// 14/12/2009 BBX
		// Contrôle de l'unicité du fichier de destination. BZ 13261
		$targetFile = str_replace('//','/',$valueArray['target_dir'].'/'.$valueArray['target_file']);
		foreach(self::getExportList('',$valueArray['product'],true) as $export)
		{
			$targetFileTest = str_replace('//','/',$export['target_dir'].'/'.$export['target_file']);
			if(($targetFileTest == $targetFile) && ($valueArray['export_id'] != $export['export_id']))
			{
				return __T('A_TASK_SCHEDULER_DATA_EXPORT_TARGET_EXISTS',$targetFile,$export['export_name']);
			}			
		}
		// FIN BZ 13261
		
		// Contrôle du répertoire cible
		
		// 19:43 27/08/2009 GHX
		// La vérification du répertoire se fait uniquement si on est sur le bon serveur
		// 14/12/2009 BBX : Correction de la récupération des infos produits.
		$infosProduct = getProductInformations();
		$infosProd = $infosProduct[$valueArray['product']]; 
		if ( get_adr_server() == $infosProd['sdp_id_address'] )
		{
			$DirectoryManagement = new DirectoryManagement($valueArray['target_dir']);
			if(!$DirectoryManagement->autoFix(0777)) {
				return __T('A_TASK_SCHEDULER_DATA_EXPORT_CANNOT_CREATE_DIR');
			}	
		}

		return true;
	}
	
	/**
	*	Récupère la description d'un compteur
	*	@param string : famille
	*	@param int : id produit
	*	@param string : id du compteur
	*	@return string : description
	**/	
	public static function getRawComment($family,$productId,$rawId)
	{
		// Instanciation de DatabaseConnection
		if ( empty(self::$database) || self::$productIdDb != $productId )
		{
			self::$database = Database::getConnection( $productId );
			self::$productIdDb = $productId;
		}
		// Requête SQL
		$query = "SELECT (CASE WHEN (edw_field_name_label = '' OR edw_field_name_label IS NULL) THEN edw_field_name ELSE edw_field_name_label END)||' : '||(CASE WHEN (comment = '' OR comment IS NULL) THEN 'No comment' ELSE comment END) as comment 
		FROM sys_field_reference
		WHERE id_ligne = '{$rawId}'";
		return self::$database->getOne($query);
	}
	
	/**
	*	Récupère la description d'un kpi
	*	@param string : famille
	*	@param int : id produit
	*	@param string : id du kpi
	*	@return string : description
	**/		
	public static function getKpiComment($family,$productId,$kpiId)
	{
		// Instanciation de DatabaseConnection
		if ( empty(self::$database) || self::$productIdDb != $productId )
		{
			self::$database = Database::getConnection( $productId );
			self::$productIdDb = $productId;
		}
		// Requête SQL
		$query = "SELECT (CASE WHEN (kpi_label = '' OR kpi_label IS NULL) THEN kpi_name ELSE kpi_label END)||' : '||(CASE WHEN (comment = '' OR comment IS NULL) THEN 'No comment' ELSE comment END) as comment 
		FROM sys_definition_kpi
		WHERE id_ligne = '{$kpiId}'";
		return self::$database->getOne($query);	
	}

	/**
	 * Met à jour la liste des data export automatiques
	 *
	 *	09:20 20/10/2009 GHX
	 *		- Modification pour prendre le compte le fichier de configuration pour le Mixed KPI
	 *			-> Paramétrage en plus
	 *
	 *	10/12/2009 BBX
	 *		- Si le fichier de conf est vide, on ne renvoie plus d'erreur, mais on ne fait que supprimer les Data Export auto existants. BZ 13408
	 *
	 *	14:27 07/01/2010 SCT
	 *		- BZ 13663 => modification de la variable $targetdir dans le cas du corporate afin de récupérer le chemin du fichier de configuration => éviter les problèmes de configuration avancées de serveur FTP (directement dans le répertoire export_files_corporate)
	 *
	 * @param string $configurationFile : fichier de configuration
	 * @param boolean $mixedKpi : TRUE si c'est un fichier de configuration pour Mixed KPI (default FALSE)
	 */
	public static function updateAutomaticDataExport ( $configurationFile, $mixedKpi = false )
	{
		// Si le fichier de conf existe
		if(file_exists($configurationFile))
		{
			// Suppression dse anciens exports
			foreach(self::getExportList('','',true) as $exportId => $values)
			{
				$export = new DataExportModel($exportId);
				if($export->getConfig('visible') == 0) {
					if ($export->getConfig('export_type') == 1 && $mixedKpi === false )
					{
						// Si on est dans le cas Corporate et que c'est un Data Export pour le Corporate on le supprime
						$export->deleteExport();
					}
					elseif ($export->getConfig('export_type') == 2 && $mixedKpi === true )
					{
						// Si on est dans le cas Mixed KPI et que c'est un Data Export pour le produit Mixed KPI on le supprime
						$export->deleteExport();
					}
				}
			}
			
			// 14:27 07/01/2010 SCT: BZ 13663 => modification de la variable $targetdir dans le cas du corporate afin de récupérer le chemin du fichier de configuration => éviter les problèmes de configuration avancées de serveur FTP (directement dans le répertoire export_files_corporate)
			$corporateConfigFileDir = dirname($configurationFile);
	
			// Si on arrive à lire le fichier de conf
			if($conf = file($configurationFile))
			{			
				// Création des nouveaux exports
				foreach($conf as $line)
				{
					$line = trim($line);
					if(trim($line) != '')
					{
						// Extraction des valeurs
						if ( $mixedKpi === false ) // Cas Corporate
						{
							list($family,$exportName,$targetDir,$timeAggregation,$networkAggregation,$networkAggregationThirdAxis,$visible,$rawExport,$kpiExport) = explode(';',$line);				
							// 14:27 07/01/2010 SCT: BZ 13663 => modification de la variable $targetdir dans le cas du corporate afin de récupérer le chemin du fichier de configuration => éviter les problèmes de configuration avancées de serveur FTP (directement dans le répertoire export_files_corporate)
							$targetDir = $corporateConfigFileDir;
						}
						else // Cas Mixed KPI
						{
							list($family,$exportName,$targetDir,$timeAggregation,$networkAggregation,$networkAggregationThirdAxis,$visible,$rawExport,$kpiExport,$idProduct,$suffix,$prefix) = explode(';',$line);				
						}
						// Création d'un nouvel export
						$exportId = self::create($family);
						// Instanciation du nouvel export
						$export = new DataExportModel($exportId);
						// Affectation de la config
						$export->setConfig('target_dir',$targetDir);
						$export->setConfig('target_file',$exportName.'.csv');
						$export->setConfig('time_aggregation',$timeAggregation);
						$export->setConfig('network_aggregation',$networkAggregation);
						$export->setConfig('export_name',$exportName);
						$export->setConfig('use_code','1');
						$export->setConfig('use_code_na','1');
						$export->setConfig('na_axe3',$networkAggregationThirdAxis);
						$export->setConfig('add_topo_file','1');
						$export->setConfig('visible','0');
						$export->setConfig('generate_hour_on_day','0');
						$export->setConfig('add_raw_kpi_file','0');
						
                        // 07/09/2010 BBX
                        // DE Bypass : gestion du flag
                        $flag = false;

						if ( $mixedKpi === false ) // Cas Corporate
						{
							$export->setConfig('use_codeq','0');
							$export->setConfig('id_product','0');
							$export->setConfig('export_type','1'); // Précise que c'est un Data Export pour le Corporate
                            $flag = true;
						}
						else // Cas Mixed KPI
						{
							$export->setConfig('id_product',$idProduct);
							$export->setConfig('use_codeq','1');
							$export->setConfig('add_suffix',$suffix);
							$export->setConfig('add_prefix',$prefix);
							$export->setConfig('export_type','2'); // Précise que c'est un Data Export pour le produit Mixed KPI
						}
						
						// Traitement des Raws
						if($rawExport == '1') {
							$export->setRawList(array_keys(self::getAllRaws($family,'',$flag)));
						}
						// Traitement des Kpis
						if($kpiExport == '1') {
							$export->setKpiList(array_keys(self::getAllKpis($family)));
						}
					}
				}
			}	
			// Suppression du fichier
			return @unlink($configurationFile);
		}
		// Return false
		return false;
	}

	/**
	*	Met à jour la listes des raw / kpi à exporter dans les Data Export automatiques
	**/		
	public static function updateDataList()
	{
		// Instanciation de DatabaseConnection
		if (empty(self::$database))
			self::$database = Database::getConnection();
		// Parcours des Data Export existants
		foreach(self::getExportList('','',true) as $exportId => $values)
		{
			$export = new DataExportModel($exportId);
			// S'il s'agit d'un Data Export automatique
			if($export->getConfig('visible') == 0) 
			{
                // 07/09/2010 BBX
                // DE Bypass : récupération du type d'export
                // Et déduction du flag
                $exportType = $export->getConfig('export_type');
                $flag       = ($exportType == 1) ? true : false;

				// Types de données
				$rawExport = $kpiExport = false;
				// On récupère les types de données exportées
				$query = "SELECT DISTINCT raw_kpi_type 
				FROM ".self::DATA_TABLE."
				WHERE export_id = '".$exportId."'";
				$result = self::$database->execute($query);
				while($array = self::$database->getQueryResults($result,1))
				{
					if($array['raw_kpi_type'] == 'raw') $rawExport = true;
					if($array['raw_kpi_type'] == 'kpi') $kpiExport = true;
				}
				// Suppression des Data à exporter
				$query = "DELETE FROM ".self::DATA_TABLE."
				WHERE export_id = '".$exportId."'";
				self::$database->execute($query);
				// Traitement des Raws
				if($rawExport) {
                    // 07/09/2010 BBX
                    // DE Bypass : ajout du flag
                    $export->setRawList(array_keys(self::getAllRaws($export->getConfig('family'),'',$flag)));
				}
				// Traitement des Kpis
				if($kpiExport) {
					$export->setKpiList(array_keys(self::getAllKpis($export->getConfig('family'))));
				}
			}
		}	
	}
}
?>