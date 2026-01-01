<?php
/*
	27/08/2009 GHX
		- Modification pour que si on fait un Export sur un produit slave qui se trouve sur un serveur marche (modificatoin des requetes SQL COPY)
	03/09/2009 GHX
		- Remplace les espaces et - du nom du Data Export par un _ (cf BZ 11271)
		- Correction du BZ 11366
			-> Si on n'a pas de "/" à la fin du target dir on le rajoute
	14/09/2009 GHX
		- Correction du BZ 11559 [REC][5.0.0.8] Contenu des exports automatique vide
	15/09/2009 GHX
		- Ajout d'un commentaire pour dire que l'on doit modifier le nom des fichiers de topo générés
	23/09/2009 - MPR : Correction du bug 11573
		-  On supprime le fichier généré s'il existe avant de le recréer afin de s'assurer de l'intégrité des données
		- Ajout de l'option -f pour toutes les commandes mv (erreur engendré lorsque l'on alterne generate file via IHM et generate file via retrieve/compute
	21/10/2009 GHX
		- Prise en compte de la colonne add_suffix
	10/11/2009 GHX
		- Correction du BZ 12639 [REC][ROAMING][DATAEXPORT] : La première colonne de données est vide
			-> Modification dans la function addTopologyToExport()
	26/11/2009 BBX. BZ 12657
		- Génération d'un fichier de topologie épuré pour un data export en corpo ou mixed kpi si le niveau min n'est pas utilisé
			=> Modification des fonctions buildFirstAxisTopoFile() et buildThirdAxisTopoFile()
			=> Création de la fonction getTopoFields()
	21/12/2009 GHX
		- Correction du BZ 13180 [REC][T&A CB 5.0.2]: les compteurs capture_duration sont visibles
			-> Modification de la fonction getExportHeader() pour ne pas mettre le suffix aux compteurs capture_duration et capture_duration_expected
	07/04/2010 BBX
		- Modification de la jointure pour générer le DataExport au profit d'un "FULL JOIN". BZ 14978
	17/05/2010 NSE : Installation standard, utilisation d'une variable globale pour les chemins vers les exécutables psql et php (PSQL_DIR et PHP_DIR)
    21/05/2010 OJT : Ajout de l'écriture d'un log dans sys_log_ast pour les indicateurs de santé
	28/07/2011 MMT bz 22987
 			- si les valeurs de NA contiennent le caractere de separation csv, les commandes echouent
 			  On code ces caractères apres la génération SQL et decode une fois les commandes effectuées
         - pour les label KPI/RAW on ajoute des caractères d'enclosure si ils contiennent
 			  le caractere de separation csv
	25/01/2013 GFS - BZ#31293 - [SUP][TA OMC UTRAN][AVP NA][ZAIN Corporate] : Button generate file doesn't work on Data export monthly
*/
?>
<?php
/**
*	Classe permettant de générer les Data Export
*	Travaille sur la table sys_definition_network_agregation, edw_object_ref, sys_definition_kpi, sys_field_reference, sys_definition_group_table
*
*	@author	BBX - 04/08/2009
*	@version	CB 5.0.0.00
*	@since	CB 5.0.0.00
*
*
*/
class DataExport
{
	/*
	*	Constantes
	*/
	// Table de config des niveaux d'agrégation
	const NETWORK_AGR_TABLE = 'sys_definition_network_agregation';
	// Table object ref
	const OBJECT_REF_TABLE = 'edw_object_ref';
	// Table des kpis
	const KPI_TABLE = 'sys_definition_kpi';
	// Table des compteurs
	const RAW_TABLE = 'sys_field_reference';
	// Table conf famille
	const GROUP_TABLE = 'sys_definition_group_table';

	/*
	*	Variables
	*/	
	// Id de l'export
	private $exportId = 0;	
	// Id du produit
	private $productId = 0;	
	// Tableau d'information sur le produit
	private $infosProduct = array();	
	// Modeèle DataExport
	private $DataExportModel = '';
	// Instance de DatabaseConnection
	private static $database;	
	// Mémorise les fichiers générés
	private $generatedFiles = Array();	
	// Débug
	private $debug = false;
	// Fichier de topologie 1er axe pour jointure (affichage des parents)
	private $topologyFilePath = '';	
	// Fichier de topologie 3ème axe pour jointure (affichage des parents)
	private $topologyThirdAxisFilePath = '';	
	// Mémorise le header pour ne pas avoir à le générer à chaque fois
	private $exportHeader = '';	
	// Mémorise les champs à conserver
	private $wantedFields = Array();	
	// OffsetDay
	private $offsetDay = 0;	
	// Erreurs
	private $error = '';	
	// Répertoire de création de l'export
	private $targetDir = '';
        // Utilisation hour to compute
        // Added 15/09/2011 BBX BZ 22802
        protected $_hourToCompute = true;

	/**
	*	Constructeur
	*	@param string : id de l'export
	*	@param int : id du produit
	**/	
	public function __construct($exportId,$productId=0)
	{
		// Connection  la base de données du produit concerné par l'export
		if (empty(self::$database))
			self::$database = Database::getConnection($productId);
		// Mémorisation du produit
		$this->productId = $productId;
		// 16:59 27/08/2009 GHX
		// Récupère les infos sur le produit
		$infosProduct = getProductInformations();	
		$this->infosProduct = $infosProduct[$this->productId];
	
		// 15:07 14/09/2009 GHX
		// Correction du BZ 11559
		// Dans le cas on l'ID du produit est zéro on récupère les infos du fichier xbdd.inc pour ce connecter sur la base courante
		// sinon les commandes psql ne fonctionne pas car le nom de la base et l'IP sont vide 
		if ( $this->productId == 0 )
		{
			include dirname(__FILE__).'/../php/xbdd.inc';
			$this->infosProduct['sdp_db_password'] = $APass;
			$this->infosProduct['sdp_db_login'] = $AUser;
			$this->infosProduct['sdp_db_name'] = $DBName;
			$this->infosProduct['sdp_ip_address'] = $AHost;
		}
	
		// Mémorisation de l'id de l'export
		$this->exportId = $exportId;
		// Instanciation d'un model DataExport
		$this->DataExportModel = new DataExportModel($exportId,$productId);
		// Récupération de l'offset day
		$this->offsetDay = get_sys_global_parameters('offset_day',0,$productId);
		// Mémorisation du répertoire de création définit dans l'export
		$this->targetDir = $this->DataExportModel->getConfig('target_dir');
		
		// 11:20 03/09/2009 GHX
		// Correction du BZ 11366
		// Si on n'a pas de / à la fin du répertoire on le rajoute
		if ( substr($this->targetDir, -1) != '/' )
		{
			$this->targetDir .= '/';
		}
	}
	
	/**
         * Affecte un offset day personnalisé
         * Added 15/09/2011 BBX BZ 22802
         * @param type $offsetDay 
         */
        public function setOffsetDay($offsetDay)
        {
            $this->offsetDay = (int)abs($offsetDay);
        }
        
        /**
         * Indique à la classe de ne pas utiliser hour_to_compute
         * pour récupérer les heures
         * Added 15/09/2011 BBX BZ 22802
         */
        public function disableHourToCompute()
        {
            $this->_hourToCompute = false;
        }
	
	/**
	*	Affecte un nouveau répertoire de création de l'export
	*	@param string : chemin
	**/	
	public function setTargetDir($targetDir)
	{
		// 11:20 03/09/2009 GHX
		// Correction du BZ 11366
		// Si on n'a pas de / à la fin du répertoire on le rajoute
		if ( substr($targetDir, -1) != '/' )
		{
			$targetDir .= '/';
		}
		$this->targetDir = $targetDir;
	}

	/**
	*	Récupère l'url de localisation de l'export
	*	@return string / bool : url ou  false si pas dans le rep de l'appli
	**/		
	public function getUrl()
	{
		if(substr_count($this->targetDir,NIVEAU_0) > 0) {
			return substr($this->targetDir,strpos($this->targetDir,NIVEAU_0));
		}
		return false;
	}

	/**
	*	Active ou désactive le débug
	*	@param bool : debug ou pas
	**/		
	public function setDebug($debug=false)
	{
		$this->debug = $debug;
	}
	
	/**
	*	Retourne une éventuelle erreur
	*	@return string : erreur
	**/		
	public function getError()
	{
		return $this->error;
	}

	/**
	*	Récupère la valeur de la TA en fonction de la TA et de l'offsetDay
	*	@return string : valeur de la TA
	**/		
	public function getTaValue()
	{
		switch($this->DataExportModel->getConfig('time_aggregation'))
		{
			// Retour du dernier jour intégré
			case 'hour':
			case 'day':
			case 'day_bh':
				return Date::getDayFromDatabaseParameters($this->offsetDay);
			break;
			// Retour de la dernière semaine intégrée
			case 'week':
			case 'week_bh':
				return Date::getWeekFromDatabaseParameters($this->offsetDay);
			break;
			// Retour du dernier mois intégré
			case 'month':
			case 'month_bh':
				// 25/01/2013 GFS - BZ#31293 - [SUP][TA OMC UTRAN][AVP NA][ZAIN Corporate] : Button generate file doesn't work on Data export monthly
				$result = Date::getMonthFromDatabaseParameters($this->offsetDay);
				if ($result == date('Ym')) {
					$result = date('Ym', strtotime($result."01 -1 month"));
				}
				return $result;
				
			break;
		}
	}

	/**
	*	Génère le header de l'export
	**/		
	public function getExportHeader()
	{
		if($this->exportHeader == '')
		{
			// La TA
			$header = $this->DataExportModel->getConfig('time_aggregation');
			// A-t-on demandé les labels des NA ?
			if($this->DataExportModel->getConfig('use_code_na') == '0')
				$header = ucfirst($this->DataExportModel->getConfig('time_aggregation'));
			
			// Les champs
			$wantedFields = $this->getWantedFields();
			
			// Si on a demandé les labels, le code est en trop
			if($this->DataExportModel->getConfig('use_code_na') == '0') {
				unset($wantedFields[1][0]);
				if($this->DataExportModel->getConfig('na_axe3') != '')
					unset($wantedFields[3][0]);
			}
			
			// Les NA 1er axe
			foreach($wantedFields[1] as $level)
			{			
				// A-t-on demandé les labels des NA ?
				if($this->DataExportModel->getConfig('use_code_na') == '0') {
					// Si le champ est un label, on récupère son code
					$level = str_replace('_label','',$level);
					$header .= $this->DataExportModel->getConfig('field_separator').getNetworkLabel($level,$this->DataExportModel->getConfig('family'),$this->productId);
				}
				else {
					$header .= $this->DataExportModel->getConfig('field_separator').$level;
				}
			}
			
			// Les NA 3ème axe
			if($this->DataExportModel->getConfig('na_axe3') != '')
			{
				foreach($wantedFields[3] as $level)
				{			
					// A-t-on demandé les labels des NA ?
					if($this->DataExportModel->getConfig('use_code_na') == '0') {
						// Si le champ est un label, on récupère son code
						$level = str_replace('_label','',$level);
						$header .= $this->DataExportModel->getConfig('field_separator').getNetworkLabel($level,$this->DataExportModel->getConfig('family'),$this->productId);
					}
					else {
						$header .= $this->DataExportModel->getConfig('field_separator').$level;
					}
				}		
			}
			
			// Doit-on préfixer les compteurs / kpis ?
			$prefixe = '';
			$tmpPrefix = $this->DataExportModel->getConfig('add_prefix');
			if ( !empty($tmpPrefix) )
			{
				if ( $tmpPrefix != "''" )
				{
					$prefixe = $tmpPrefix.'_';
				}
			}
			// 10:28 20/10/2009 GHX
			$suffix = '';
			$tmpSuffix = $this->DataExportModel->getConfig('add_suffix');
			if ( !empty($tmpSuffix) )
			{
				if ( $tmpSuffix != "''" )
				{
					$suffix = '_'.$tmpSuffix;
				}
			}
			
			// A-t-on demandé les labels des Raw / Kpi ?
			$getLabels = false;
			if($this->DataExportModel->getConfig('use_code') == '0')
				$getLabels = true;

			// 09:36 21/12/2009 GHX
			// Récupère le type de l'export
			// 0 = export standard / 1 = export pour le Corporate / 2 = export pour le produit Mixed KPI
			$exportType = $this->DataExportModel->getConfig('export_type');
			
			// Busy hour
			if(substr_count($this->DataExportModel->getConfig('time_aggregation'),'_bh') > 0) {
				$bhHeader = $getLabels ? 'Busy Hour' : 'bh';
				$header .= $this->DataExportModel->getConfig('field_separator').$prefixe.$bhHeader.$suffix;
			}
			
			// Les Raws
			// 10:36 20/10/2009 GHX
			// Ajout du suffix
			foreach($this->DataExportModel->getRaws($getLabels) as $rawId => $rawNameLabel) {
				// 09:41 21/12/2009 GHX
				// Pas de suffix préffix pour les 2 compteurs capture_duration_expected et capture_duration pour les Data Export de Mixed KPI
				//28/07/2011 MMT bz 22987 on ajoute des caractères d'enclosure si les labels contiennent le caractere de separation csv
				if ( $exportType == 2 && ($rawNameLabel == 'capture_duration_expected' || $rawNameLabel == 'capture_duration') ){
					$value = $rawNameLabel;
				} else {
					$value = $prefixe.$rawNameLabel.$suffix;
				}
				$value = TopologyDownload::getSafeCsvValue($value, $this->DataExportModel->getConfig('field_separator'));
				$header .= $this->DataExportModel->getConfig('field_separator').$value;
				// fin 22987
			}
			
			// Les Kpis
			// 10:36 20/10/2009 GHX
			// Ajout du suffix
			foreach($this->DataExportModel->getKpis($getLabels) as $kpiId => $kpiNameLabel) {
				//28/07/2011 MMT bz 22987 on ajoute des caractères d'enclosure si les labels contiennent le caractere de separation csv
				$value = TopologyDownload::getSafeCsvValue($prefixe.$kpiNameLabel.$suffix, $this->DataExportModel->getConfig('field_separator'));
				$header .= $this->DataExportModel->getConfig('field_separator').$value;
			}
			
			// Mémorisation du header
			$this->exportHeader = $header;
		}

		// Retour du header
		return $this->exportHeader;
	}

	/**
	*	Récupère les parents d'un niveaux
	*	@param string : niveau
	*	@param array : parents du niveau
	**/	
	public function getTopologyParents($level)
	{
		// Tableau qui va mémoriser les parents
		$parents = Array();
		
		// Requête de recherche des parents
		$query = "SELECT DISTINCT agregation_rank, agregation 
		FROM ".self::NETWORK_AGR_TABLE." 
		WHERE level_source = '".$level."'
		AND family = '".$this->DataExportModel->getConfig('family')."'
		AND level_operand != '='
		ORDER BY agregation_rank";

		// Récupération récursif des parents
		$result = self::$database->execute($query);	
		while($values = self::$database->getQueryResults($result,1))
		{
			$parents[] = $values['agregation'];
			$parents = array_merge($parents,$this->getTopologyParents($values['agregation']));
		}

		// On a désormais tous nos parents "empilés". On va les retrier pour conserver un ordre logique (par rank).
		$query = "SELECT agregation 
		FROM ".self::NETWORK_AGR_TABLE." 
		WHERE family ='".$this->DataExportModel->getConfig('family')."'
		AND agregation IN ('".implode("','",$parents)."')
		ORDER BY agregation_rank";
		$result = self::$database->execute($query);	
		$parents = Array();
		while($values = self::$database->getQueryResults($result,1))
			$parents[] = $values['agregation'];
		
		// Retour des parents
		return $parents;
	}

	/**
	*	26/11/2009 BBX : BZ 12657
	*	Récupère les champs à exporter en topologie à partir du niveau défini dans le data export
	*	@param return array : tableau des champs
	**/	
	public function getTopoFields()
	{
		// Tableau des champs
		$wantedFields = Array();
		
		// Parents 1er axe
		$fields = Array();
		$naMin = $this->DataExportModel->getConfig('network_aggregation');
		$fields[] = $naMin;
		$fields = array_merge($fields,$this->getTopologyParents($naMin));
		$wantedFields[1] = $fields;
		// Parents 3ème axe
		if($this->DataExportModel->getConfig('na_axe3') != '')
		{
			$fields = Array();
			$naMin = $this->DataExportModel->getConfig('na_axe3');
			$fields[] = $naMin;
			$fields = array_merge($fields,$this->getTopologyParents($naMin));
			$wantedFields[3] = $fields;	
		}
		
		// Ajout des labels
		// 1er axe
		$fields = Array();
		foreach($wantedFields[1] as $field) {
			$fields[] = $field;
			$fields[] = $field.'_label';
		}
		$wantedFields[1] = $fields;
		// 3ème axe
		if($this->DataExportModel->getConfig('na_axe3') != '')
		{
			$fields = Array();
			foreach($wantedFields[3] as $field) {
				$fields[] = $field;
				$fields[] = $field.'_label';
			}
			$wantedFields[3] = $fields;
		}		
		
		return $wantedFields;
	}

	/**
	*	Récupère les champs à conserver pour l'export
	*	@return array : NA de l'export
	**/		
	public function getWantedFields()
	{
		if(count($this->wantedFields) == 0) 
		{
			// Avec parents
			if($this->DataExportModel->getConfig('select_parents') == '1')
			{
				// Parents 1er axe
				$fields = Array();
				$naMin = $this->DataExportModel->getConfig('network_aggregation');
				$fields[] = $naMin;
				$fields = array_merge($fields,$this->getTopologyParents($naMin));
				$this->wantedFields[1] = $fields;
				// Parents 3ème axe
				if($this->DataExportModel->getConfig('na_axe3') != '')
				{
					$fields = Array();
					$naMin = $this->DataExportModel->getConfig('na_axe3');
					$fields[] = $naMin;
					$fields = array_merge($fields,$this->getTopologyParents($naMin));
					$this->wantedFields[3] = $fields;	
				}
			}
			// Sans parent (Rémi)
			else
			{
				$this->wantedFields[1] = Array($this->DataExportModel->getConfig('network_aggregation'));
				$this->wantedFields[3] = Array($this->DataExportModel->getConfig('na_axe3'));
			}
		
			// Si l'on doit afficher les labels dans l'export, on va donc récupérer les labels au lieu des codes (sauf pour le niveau min cause jointure)
			if($this->DataExportModel->getConfig('use_code_na') == '0')
			{
				// 1er axe
				$fields = Array();
				foreach($this->wantedFields[1] as $field) {
					if($field == $this->DataExportModel->getConfig('network_aggregation')) {
						$fields[] = $field;
					}
					$fields[] = $field.'_label';
				}
				$this->wantedFields[1] = $fields;
				// 3ème axe
				if($this->DataExportModel->getConfig('na_axe3') != '')
				{
					$fields = Array();
					foreach($this->wantedFields[3] as $field) {
						if($field == $this->DataExportModel->getConfig('na_axe3')) {
							$fields[] = $field;
						}
						$fields[] = $field.'_label';
					}
					$this->wantedFields[3] = $fields;
				}
			}
		}
		return $this->wantedFields;
	}

	/**
	 * Ajoute la topologie au fichier d'export
	 *
	 *	10/11/2009 GHX
	 *		- Correction du BZ 12639 sur la partie troisieme axe
	 *
	 * @param string : chemin du fichier d'export
	 */		
	public function addTopologyToExport($exportFile)
	{
		// Déclaration de variables
		$nbParents = 0;
		$nbParentsThirdAxis = 0;	
	
		// Fichier temporaire de tri
		$sortFile = $this->targetDir.'sort_file.csv';		
		// Fichier temporaire de jointure
		$joinFile = $this->targetDir.'join_file.csv';		
		// Colonnes désirées
		$wantedFields = $this->getWantedFields();		
		// Champs premier axe
		$fieldsFirstAxis = $wantedFields[1];

		// Génération du fichier de topologie 1er axe
		if($this->topologyFilePath == '') 
		{
			// Fichier de topologie temporaire
			$fileName = 'topology_to_join_to_export.csv';
			// Instanciation d'un objet TopologyDownload
			$TopologyDownload = new TopologyDownload($this->DataExportModel->getConfig('family'),$this->productId);
			$TopologyDownload->setFields($fieldsFirstAxis);
			$TopologyDownload->setSeparator($this->DataExportModel->getConfig('field_separator'));
			$TopologyDownload->setLabelFillingMethod(1);

                        // 07/09/2010 BBX
                        // DE Bypass : ajout des cellules virtuelles en mode Coporate
                        if($this->DataExportModel->getConfig('export_type') == 1)
                            $TopologyDownload->setExportVirtualElement(1);

			// Doit-on utiliser une topologie mappée ?
			if(getTopologyMappingInfo() && ($this->DataExportModel->getConfig('use_codeq') == '1'))
				$TopologyDownload->setMapping(1);
			$filePath = $TopologyDownload->exportTopology($fileName,false);

			// 28/07/2011 MMT bz 22987  need to escape the separator char in the csv values encapsulated by '"' by SQL csv
			// or some commands will fail (join, awk..)
			$this->codeDelimiterInFileBeforeCmd($filePath);
			
			// On déplace ce fichier dans le répertoire des exports, il se sentira mieux avec ses copains
			// $cmd = 'rm -f '.$this->targetDir.$fileName;
			// exec($cmd);
			
			$cmd = 'mv -f '.$filePath.' '.$this->targetDir.$fileName;
			exec($cmd);
			
			$this->topologyFilePath = $this->targetDir.$fileName;
			// On trie le fichier de topologie sur la première colonne et on enlève le header
			$cmd = 'awk \'BEGIN{FS="'.$this->DataExportModel->getConfig('field_separator').'";OFS=FS}NR>1{print $0}\' '.$this->topologyFilePath;
			$cmd .= ' | sort -t"'.$this->DataExportModel->getConfig('field_separator').'" -k1,1 > '.$sortFile;
			$cmd .= ' && mv -f '.$sortFile.' '.$this->topologyFilePath;
			exec($cmd);
		}
		
		// On compte le nombre de parents 1er axe
		$nbParents = count($fieldsFirstAxis);

		// Génération du fichier de topologie 3ème axe
		if($this->DataExportModel->getConfig('na_axe3') != '')
		{
			// Champs 3ème axe
			$fieldsThirdAxis = $wantedFields[3];
			// Si le fichier n'a pas encore été généré
			if($this->topologyThirdAxisFilePath == '') 
			{
				// Fichier de topologie temporaire
				$fileName = 'topology3_to_join_to_export.csv';
				// Instanciation d'un objet TopologyDownload
				$TopologyDownload = new TopologyDownload($this->DataExportModel->getConfig('family'),$this->productId);
				$TopologyDownload->setFields($fieldsThirdAxis);
				$TopologyDownload->setSeparator($this->DataExportModel->getConfig('field_separator'));
				$TopologyDownload->setLabelFillingMethod(1);

                                // 07/09/2010 BBX
                                // DE Bypass : ajout des cellules virtuelles en mode Coporate
                                if($this->DataExportModel->getConfig('export_type') == 1)
                                    $TopologyDownload->setExportVirtualElement(1);

				// Doit-on utiliser une topologie mappée ?
				if(getTopologyMappingInfo() && ($this->DataExportModel->getConfig('use_codeq') == '1'))
					$TopologyDownload->setMapping(1);
				$filePath = $TopologyDownload->exportTopology($fileName,true);
				// 28/07/2011 MMT bz 22987  need to escape the separator char in the csv values encapsulated by '"' by SQL csv
				// or some commands will fail (join, awk..)
				$this->codeDelimiterInFileBeforeCmd($filePath);

				// On déplace ce fichier dans le répertoire des exports, il se sentira mieux avec ses copains
				$cmd = 'mv -f '.$filePath.' '.$this->targetDir.$fileName;
				exec($cmd);
				$this->topologyThirdAxisFilePath = $this->targetDir.$fileName;
				// On trie le fichier de topologie sur la première colonne et on enlève le header
				$cmd = 'awk \'BEGIN{FS="'.$this->DataExportModel->getConfig('field_separator').'";OFS=FS}NR>1{print $0}\' '.$this->topologyThirdAxisFilePath;
				$cmd .= ' | sort -t"'.$this->DataExportModel->getConfig('field_separator').'" -k1,1 > '.$sortFile;
				$cmd .= ' && mv -f '.$sortFile.' '.$this->topologyThirdAxisFilePath;
				exec($cmd);
			}
			// On compte le nombre de parents 3ème axe
			$nbParentsThirdAxis = count($fieldsThirdAxis);
		}
		
		// On compte le nombre de colonnes du fichier d'export
		$cmd = 'awk -F"'.$this->DataExportModel->getConfig('field_separator').'" \'NR==1{print NF}\' '.$exportFile;
		exec($cmd,$result);
		$nbColsInExportFile = $result[0];
		
		// Calcul du nombre de colonnes réservées aux NA + date
		$nbNaCols = ($this->DataExportModel->getConfig('na_axe3') != '') ? 3 : 2;
		
		// Préparation du fichier d'export à la jointure 1er axe : on trie le fichier sur la seconde colonne
		$cmd = 'sort -t"'.$this->DataExportModel->getConfig('field_separator').'" -k2,2 '.$exportFile.' > '.$sortFile;
		$cmd .= ' && mv -f '.$sortFile.' '.$exportFile;
		exec($cmd);
		if( $this->debug ){
			echo "<br><b><u>Préparation du fichier d'export à la jointure 1er axe : on trie le fichier sur la seconde colonne</u></b><br>";
			echo '<pre>'.$cmd.'</pre>';
		}
		// Si on a demandé les labels, on affichera pas le code. Inversement, si code demandé, on affichera pas les labels
		if($this->DataExportModel->getConfig('use_code_na') == '0') {
			// Colonnes Date uniquement. Le label 1er axe fait partie du fichier de topo
			$colOrder = '1.1';	
		}
		else {
			// Colonnes Date, code NA 1er axe
			$colOrder = '1.1 0';
		}
		
		// Colonnes des parents 1er axe
		for($c = 2; $c <= $nbParents; $c++)
			$colOrder .= ' 2.'.$c;	
		// Si 3ème axe
		if($this->DataExportModel->getConfig('na_axe3') != '')
			$colOrder .= ' 1.3';
		// Colonnes des données
		for($c = ($nbNaCols+1); $c <= $nbColsInExportFile; $c++)
			$colOrder .= ' 1.'.$c;	

		// On va maintenant joindre le fichier d'export au fichier de topologie 1er axe
		$cmd = 'join -t "'.$this->DataExportModel->getConfig('field_separator').'" -a1 -1 2 -2 1 -o "'.$colOrder.'" ';
		$cmd .= $exportFile.' '.$this->topologyFilePath.' > '.$joinFile;

		$cmd.= ' && mv -f '.$joinFile.' '.$exportFile;
		exec($cmd);
		if( $this->debug ){
			echo "<br><b><u>ADD TOPOLOGY FILE TO EXPORT FILE : first axis</u></b><br>";
			echo '<pre>'.$cmd.'</pre>';
		}

		// Si on a un troisième axe
		if($this->DataExportModel->getConfig('na_axe3') != '')
		{
			// 16:53 10/11/2009 GHX
			// >>>>>>>>>> Début correction du BZ 12639
			// Ajout et Modification de condition principale
			// Sinon modification sur la variable $nbParents
			if($this->DataExportModel->getConfig('use_code_na') == '1') {
				$nbParents++;
			}
			// Préparation du fichier d'export à la jointure 3ème axe : on trie le fichier sur la troisième colonne
			$cmd = 'sort -t"'.$this->DataExportModel->getConfig('field_separator').'" -k'.($nbParents+1).','.($nbParents+1).' '.$exportFile.' > '.$sortFile;
			$cmd .= ' && mv -f '.$sortFile.' '.$exportFile;
			exec($cmd);	
			if( $this->debug ){
				echo "<br><b><u>Préparation du fichier d'export à la jointure 3ème axe : on trie le fichier sur la troisième colonne</u></b><br>";
				echo '<pre>'.$cmd.'</pre>';
			}
			// On définit l'ordre d'affichage des colonnes
			// Colonnes Date + NA 1er axe
			$colOrder = '1.1 1.2';
			// Colonnes parents 1er axe
			for($c = 3; $c <= $nbParents; $c++)
				$colOrder .= ' 1.'.$c;
			// Si on a demandé les labels, on affichera pas le code. Inversement, si code demandé, on affichera pas les labels
			if($this->DataExportModel->getConfig('use_code_na') == '0') {
				// Colonnes des parents 3ème axe
				for($c = 2; $c <= $nbParentsThirdAxis; $c++)
					$colOrder .= ' 2.'.$c;	
				// Colonnes des données
				for($c = ($nbNaCols+$nbParents-1); $c < $nbColsInExportFile+($nbParents-1); $c++)
					$colOrder .= ' 1.'.$c;
			}
			else {
				// Colonnes des parents 3ème axe
				for($c = 1; $c <= $nbParentsThirdAxis; $c++)
					$colOrder .= ' 2.'.$c;
				// Colonnes des données
				for($c = ($nbNaCols+$nbParents-1); $c < $nbColsInExportFile+($nbParents-1); $c++)
					$colOrder .= ' 1.'.$c;
			}
			// Colonne de jointure
			$joinCol = $nbNaCols+$nbParents-2;
			// <<<<<<<<<< Fin correction du BZ 12639

			// On va maintenant joindre le fichier d'export au fichier de topologie 3ème axe
			$cmd = 'join -t "'.$this->DataExportModel->getConfig('field_separator').'" -a1 -1 '.$joinCol.' -2 1 -o "'.$colOrder.'" ';
			$cmd .= $exportFile.' '.$this->topologyThirdAxisFilePath.' > '.$joinFile;
			$cmd .= ' && mv -f '.$joinFile.' '.$exportFile;
			exec($cmd);
			if( $this->debug ){
				echo "<br><b><u>ADD TOPOLOGY FILE TO EXPORT FILE : third axis</u></b><br>";
				echo '<pre>'.$cmd.'</pre>';
			}
		}

		// Finalement, on va trier les données sur la TA et la NA min
		$sortCmd = ($this->DataExportModel->getConfig('na_axe3') != '') ? '1,3' : '1,2';
		$cmd = 'sort -t"'.$this->DataExportModel->getConfig('field_separator').'" -k'.$sortCmd.' '.$exportFile.' > '.$sortFile;
		$cmd .= ' && mv -f '.$sortFile.' '.$exportFile;
		exec($cmd);
		if( $this->debug ){
			echo "<br><b><u>Finalement, on va trier les données sur la TA et la NA min</u></b><br>";
			echo '<pre>'.$cmd.'</pre>';
		}
		// Suppression des fichiers temporaires
		exec('rm -f '.$sortFile);
		exec('rm -f '.$joinFile);
		// die;

		// 28/07/2011 MMT bz 22987  need to decode the separator done by codeDelimiterInFileBeforeCmd
		$this->decodeDelimiterInFileAfterCmd($exportFile);
	}

	/**
	*	Génère le fichier d'export
	**/	
	public function buildExportFile()
	{
		// Récupération de la TA
		$taValue = $this->getTaValue();
		
		// Par défaut, une seule TA à traiter
		$taValuesToTreat = Array($taValue);
		
		// Si le fichier est horaire et qu'il faux générer un fichier par heure, on boucle sur toutes les heures de la journée
		if(($this->DataExportModel->getConfig('time_aggregation') == 'hour') && ($this->DataExportModel->getConfig('generate_hour_on_day') == '0'))
		{
			// 14/12/2009 BBX
			// Récupération des heures computées. BZ 13441
			$computedHours = get_sys_global_parameters('hour_to_compute','',$this->productId);
			if(!empty($computedHours) && $this->_hourToCompute) 
			{
				$taValuesToTreat = is_array(explode('|s|',$computedHours)) ? explode('|s|',$computedHours) : $computedHours;
			}
			else
			// Sinon on traite toutes les heures de la journée computée
			{
				// Récupération des heures de la journée
				$taValuesToTreat = Array();
				for($h = 0; $h <= 23; $h++) {
					$taValuesToTreat[] = $taValue.sprintf('%02d',$h);
				}			
			}
		}

		// Pour toutes les TA à traiter
		foreach($taValuesToTreat as $taCurrentValue)
		{
			// Chemin des fichiers
			$exportFileFinalName = str_replace('.csv','_'.$taCurrentValue.'.csv',$this->DataExportModel->getConfig('target_file'));
			// S'il s'agit d'un fichier journalier qui contient des heures, on ajoute 'H' dans le nom
			if (($this->DataExportModel->getConfig('time_aggregation') == 'hour') && ($this->DataExportModel->getConfig('generate_hour_on_day') == '1')) {
				$exportFileFinalName = str_replace('.csv','_'.$taCurrentValue.'_H.csv',$this->DataExportModel->getConfig('target_file'));
			}
			$targetFile = $this->targetDir.$exportFileFinalName;
			$tempFile = $this->targetDir.'/temp_export_'.uniqid().'.csv';		
                        // 25/09/2012 ACS/BBX
                        // BZ 28875 : On ne fourni le data export que lorsqu'il est complet
			$tempCompleteFile = $this->targetDir.'/temp_export_complete_'.uniqid().'.csv';		
			$tempHeader = $this->targetDir.'/temp_export_header.csv';
			
			// Group table infos
			$groupTableInfos = GetGTInfoFromFamily($this->DataExportModel->getConfig('family'),$this->productId);

			// Data Table des Raw
			$rawDataTable = $groupTableInfos['edw_group_table'].'_raw_';
			$rawDataTable .= $this->DataExportModel->getConfig('network_aggregation').'_';
			if($this->DataExportModel->getConfig('na_axe3') != '') 
				$rawDataTable .= $this->DataExportModel->getConfig('na_axe3').'_';
			$rawDataTable .= $this->DataExportModel->getConfig('time_aggregation');
			
			// Data Table des Kpi
			$kpiDataTable = $groupTableInfos['edw_group_table'].'_kpi_';
			$kpiDataTable .= $this->DataExportModel->getConfig('network_aggregation').'_';
			if($this->DataExportModel->getConfig('na_axe3') != '') 
				$kpiDataTable .= $this->DataExportModel->getConfig('na_axe3').'_';
			$kpiDataTable .= $this->DataExportModel->getConfig('time_aggregation');
			
			// RAWS
			$rawList = '';
			foreach($this->DataExportModel->getRaws(false) as $rawId => $rawName) {
				$rawList .= ",r.".$rawName;
			}
			
			// KPIS
			$kpiList = '';			
			foreach($this->DataExportModel->getKpis(false) as $kpiId => $kpiName) {
				$kpiList .= ",k.".$kpiName;
			}
			
			// FROM
			if($rawList == '') {
				$fromPart = " FROM $kpiDataTable k";
				$tableRef = "k";
			}
			elseif($kpiList == '') {
				$fromPart = " FROM $rawDataTable r";
				$tableRef = "r";		
			}
			else {
				// 07/04/2010 BBX : modification de la jointure vers un full join. BZ 14978
				// $fromPart = " FROM $rawDataTable r, $kpiDataTable k";
				$fromPart = " FROM $rawDataTable r FULL JOIN $kpiDataTable k";
				$tableRef = "r";
			}
			
			// JOINTURE si raws + kpis
			if(!empty($rawList) && !empty($kpiList))
			{
				// BZ 14978
				// 07/04/2010 BBX : modification de la jointure au profit d'un "FULL JOIN". 
				$fromPart .= " ON (r.".$this->DataExportModel->getConfig('network_aggregation')." = k.".$this->DataExportModel->getConfig('network_aggregation');
				if($this->DataExportModel->getConfig('na_axe3') != '') 
					$fromPart .= " AND r.".$this->DataExportModel->getConfig('na_axe3')." = k.".$this->DataExportModel->getConfig('na_axe3');
				$fromPart .= " AND r.".$this->DataExportModel->getConfig('time_aggregation')." = k.".$this->DataExportModel->getConfig('time_aggregation').")";
				// FIN BZ 14978
			}
			
			// Busy Hour
			if(substr_count($this->DataExportModel->getConfig('time_aggregation'),'_bh') > 0) {
				$bh = ",".$tableRef.".bh";
			}
			
			// TOPO
			$fromPart .= ", ".self::OBJECT_REF_TABLE." ref1";
			if($this->DataExportModel->getConfig('na_axe3') != '')
				$fromPart .= ", ".self::OBJECT_REF_TABLE." ref2";
			
			/*
			* Construction de la requête
			*/
			// SELECT
			$query = "COPY (SELECT ";
			
			// La TA
			$query .= $tableRef.".".$this->DataExportModel->getConfig('time_aggregation').",";
			
			// La NA 1er axe			
			$NaQuery = 'ref1.eor_id';
			// Gestion du mapping
			if(getTopologyMappingInfo() && ($this->DataExportModel->getConfig('use_codeq') == '1')) {
				$NaQuery = 'CASE WHEN (ref1.eor_id_codeq IS NOT NULL) THEN ref1.eor_id_codeq ELSE ref1.eor_id END';
			}			
			$query .= $NaQuery." AS ".$this->DataExportModel->getConfig('network_aggregation');				
			// Eventuellement la NA axe 3
			if($this->DataExportModel->getConfig('na_axe3') != '') 
			{
				$NaQuery = 'ref2.eor_id';
				// Gestion du mapping
				if(getTopologyMappingInfo() && ($this->DataExportModel->getConfig('use_codeq') == '1')) {
					$NaQuery = 'CASE WHEN (ref2.eor_id_codeq IS NOT NULL) THEN ref2.eor_id_codeq ELSE ref2.eor_id END';
				}
				$query .= ",".$NaQuery." AS ".$this->DataExportModel->getConfig('na_axe3');
			}			

			// Raw & Kpis
			$query .= $bh.$rawList.$kpiList;
			// From
			$query .= $fromPart;

			// Condition sur la TA
			$taCondition = $this->DataExportModel->getConfig('time_aggregation');
			// Si le fichier est horaire mais doit contenir toutes les heures de la journée, alors la TA de la condition sera le jour
			if (($this->DataExportModel->getConfig('time_aggregation') == 'hour') && ($this->DataExportModel->getConfig('generate_hour_on_day') == '1'))
				$taCondition = 'day';
			$query .= " WHERE ";
			$query .= $tableRef.".".$taCondition." = ".$taCurrentValue;
			
			// On vérifie que la NA n'est pas nulle
			$query .= " AND ".$tableRef.".".$this->DataExportModel->getConfig('network_aggregation')." IS NOT NULL";
			if($this->DataExportModel->getConfig('na_axe3') != '') 
				$query .= " AND ".$tableRef.".".$this->DataExportModel->getConfig('na_axe3')." IS NOT NULL";
				
			// JOINTURE TOPO
			$query.= " AND ref1.eor_id = ".$tableRef.".".$this->DataExportModel->getConfig('network_aggregation');
			$query .= " AND ref1.eor_obj_type = '".$this->DataExportModel->getConfig('network_aggregation')."'";
			$query .= " AND ref1.eor_on_off = 1";
			if($this->DataExportModel->getConfig('na_axe3') != '') {
				$query.= " AND ref2.eor_id = ".$tableRef.".".$this->DataExportModel->getConfig('na_axe3');
				$query .= " AND ref2.eor_obj_type = '".$this->DataExportModel->getConfig('na_axe3')."'";
				$query .= " AND ref2.eor_on_off = 1";
			}
			
                        // 22/11/2011 BBX
                        // BZ 17786 : exclusion des cellules virtuelles pour les exports non auto
                        if($this->DataExportModel->getConfig('export_type') == 0) {
                            $query .= " AND ref1.eor_id NOT LIKE 'virtual_%'";
                        }
			
			// ORDER BY pour ranger tout ça
			$query .= " ORDER BY ".$tableRef.".".$this->DataExportModel->getConfig('time_aggregation');
			$query .= ",ref1.eor_id";
			if($this->DataExportModel->getConfig('na_axe3') != '') 
				$query .= ",ref2.eor_id";
				
			// END COPY
			$query .= ")
			TO stdout
			WITH CSV DELIMITER AS '".$this->DataExportModel->getConfig('field_separator')."' NULL AS ''";

			/* DEBUG */
			// Debug valeurs de l'export
			if($this->debug) {
				__debug($this->DataExportModel->getConfig(),"Config Export");
				__debug($this->DataExportModel->getRaws(),"Raws Export");
				__debug($this->DataExportModel->getKpis(),"Kpis Export");
			}
			
			// Debug requête
			if($this->debug) {
				__debug($query,"Query COPY");
			}
			/* FIN DEBUG */

			// 16:59 27/08/2009 GHX
			$cmd = sprintf(
				'env PGPASSWORD=%s '.PSQL_DIR.'/psql -U %s %s -h %s -c "%s" >> "%s"',
				$this->infosProduct['sdp_db_password'],
				$this->infosProduct['sdp_db_login'],
				$this->infosProduct['sdp_db_name'],
				$this->infosProduct['sdp_ip_address'],
				$query,
				$tempFile
			);
			
			exec($cmd);
			
			// maj 11:41 23/09/2009 - MPR : Correction du bug 11573 : On supprime le fichier généré s'il existe avant de le recréer afin de s'assurer de l'intégrité des données
			if (file_exists($targetFile)) {
				@unlink($targetFile);
			}
                        // 25/09/2012 ACS/BBX
                        // BZ 28875 : On ne fourni le data export que lorsqu'il est complet
			if (file_exists($tempCompleteFile)) {
				@unlink($tempCompleteFile);
			}
			
			// Si le fichier n'est pas vide, on rajoute le header et on créé le fichier final
			if(file_exists($tempFile) && (filesize($tempFile) > 0)) 
			{
				// Sans Header
                                // 25/09/2012 ACS/BBX
                                // BZ 28875 : On ne fourni le data export que lorsqu'il est complet
				$cmd = 'cat '.$tempFile.' > '.$tempCompleteFile;
				exec($cmd);
			
				// Gestion de la topologie à l'intérieur du fichier d'export
                                // 25/09/2012 ACS/BBX
                                // BZ 28875 : On ne fourni le data export que lorsqu'il est complet
				$this->addTopologyToExport($tempCompleteFile);

				// Doit-on ajouter le header ?
				if($this->DataExportModel->getConfig('with_header') == '1') 
				{
					// 28/07/2011 MMT bz 22987 le header peut contenir des " pour enclosure csv
					// il faut les escaper pour le awk
					$header = $this->getExportHeader();
					$header = str_replace('"', '\"', $header);
					// 25/09/2012 ACS/BBX
                                        // BZ 28875 : On ne fourni le data export que lorsqu'il est complet
					$cmd = 'awk \'BEGIN{FS="'.$this->DataExportModel->getConfig('field_separator').'";OFS=FS;';
					$cmd .= 'print "'.$header.'"}{print $0}\' ';
					$cmd .= $tempCompleteFile.' > '.$tempHeader;
					$cmd .= ' && mv -f '.$tempHeader.' '.$tempCompleteFile;
					exec($cmd);
				}
                                // 25/09/2012 ACS/BBX
                                // BZ 28875 : On ne fourni le data export que lorsqu'il est complet
				$cmd = 'mv -f '.$tempCompleteFile.' '.$targetFile;
				exec($cmd);

				// On ajoute le fichier à la liste des fichiers générés
				$this->generatedFiles['export'][$taCurrentValue] = $exportFileFinalName;
			}
			
			// Suppression fichiers temporaires
			exec('rm -f '.$tempFile);
		}
		// Suppression du fichier temporaire de topologie si existant
		if($this->topologyFilePath != '')
			exec('rm -f '.$this->topologyFilePath);
		if($this->topologyThirdAxisFilePath != '')
			exec('rm -f '.$this->topologyThirdAxisFilePath);
	}

	/**
	*	Génère le fichier des compteurs
	**/		
	public function buildRawFile()
	{
		// Nom du fichier
		$fileName = str_replace('.csv','_counter_list.csv',$this->DataExportModel->getConfig('target_file'));
		// Chemin du fichier
		$targetFile = $this->targetDir.'/'.$fileName;
		// Récupération du id_group_table de la famille de l'export
		$familyInfo = get_family_information_from_family($this->DataExportModel->getConfig('family'),$this->productId);
		$idGroupTable = $familyInfo['rank'];
		// Requête de récupération des compteurs
		$query = "COPY (SELECT edw_field_name AS Code, edw_field_name_label AS Label, comment AS Description
		FROM ".self::RAW_TABLE." 
		WHERE on_off = 1
		AND new_field = 0
		AND visible = 1
		AND id_group_table = (
			SELECT id_ligne FROM ".self::GROUP_TABLE."
			WHERE family = '{$this->DataExportModel->getConfig('family')}'
			LIMIT 1)
		ORDER BY edw_field_name)
		TO stdout
		WITH CSV HEADER DELIMITER AS ';' NULL AS ''";
		
		// suppression du fichier avant sa réécriture
		exec("rm -f {$targetFile}", $error_erase);

		// 16:59 27/08/2009 GHX
		$cmd = sprintf(
			'env PGPASSWORD=%s '.PSQL_DIR.'/psql -U %s %s -h %s -c "%s" >> "%s"',
			$this->infosProduct['sdp_db_password'],
			$this->infosProduct['sdp_db_login'],			
			$this->infosProduct['sdp_db_name'],
			$this->infosProduct['sdp_ip_address'],
			$query,
			$targetFile
		);
		exec($cmd, $r, $error);
		
		if( !$error )
			$this->generatedFiles['raws'] = $fileName;
		}

	/**
	*	Génère le fichier des kpis
	**/		
	public function buildKpiFile()
	{
		// Nom du fichier
		$fileName = str_replace('.csv','_kpi_list.csv',$this->DataExportModel->getConfig('target_file'));
		// Chemin du fichier
		$targetFile = $this->targetDir.'/'.$fileName;
		// Requête de récupération des compteurs
		$query = "COPY (SELECT kpi_name AS Code, kpi_label AS Label, comment AS Description
		FROM ".self::KPI_TABLE." 
		WHERE on_off = 1
		AND new_field = 0
		AND visible = 1
		AND edw_group_table = (
			SELECT edw_group_table FROM ".self::GROUP_TABLE."
			WHERE family = '{$this->DataExportModel->getConfig('family')}'
			LIMIT 1)
		ORDER BY kpi_name)
		TO stdout
		WITH CSV HEADER DELIMITER AS ';' NULL AS ''";
		
		// suppression du fichier avant sa réécriture
		exec("rm -f {$targetFile}", $error_erase);

		// 16:59 27/08/2009 GHX
		$cmd = sprintf(
			'env PGPASSWORD=%s '.PSQL_DIR.'/psql -U %s %s -h %s -c "%s" >> "%s"',
			$this->infosProduct['sdp_db_password'],
			$this->infosProduct['sdp_db_login'],
			$this->infosProduct['sdp_db_name'],
			$this->infosProduct['sdp_ip_address'],
			$query,
			$targetFile
		);
		exec($cmd, $r, $error);
		
		if(!$error)
			$this->generatedFiles['kpis'] = $fileName;
		}

	/**
	*	Génère le fichier de topologie 1er axe
	**/		
	public function buildFirstAxisTopoFile()
	{
		// Fichier de topologie
		// 10:33 03/09/2009 GHX
		// maj 28/08/2009 - Correction du bug 11271 : On remplace les espaces ou - dans le nom de l'export
		/* **************************************************************************************************** */
		/* ATTENTION NE PAS MODIFIER LE NOM DU FICHIER DE TOPO CAR DANS LE CAS D'UN COPORATE                     */
		/* ON FAIT UNE EXPRESSION REGULIERE POUR TROUVER LES FICHIERS DE TOPO GENERER PAR DATA EXPORT */
		/* **************************************************************************************************** */
		$fileName = str_replace(array(" ","-"),"_",$this->DataExportModel->getConfig('export_name')).'_topology_first_axis.csv';

		// Tableau des champs
		$wantedFields = Array();
		
		// 26/11/2009 BBX
		// Si on est dans le cas d'un Data Export pour le Corporate ou le Mixed KPI
		// et que le niveau min du Data Export n'est pas le niveau min de la famille, on génère un fichier de topologie épuré. BZ 12657
		$familyMinLevel = get_network_aggregation_min_from_family($this->DataExportModel->getConfig('family'),$this->productId);
		if((in_array($this->DataExportModel->getConfig('export_type'),Array('1','2'))) && ($this->DataExportModel->getConfig('network_aggregation') != $familyMinLevel))
		{
			// On ne récupère que les niveaux d'agrégation >= au niveau min
			$wantedFields = $this->getTopoFields();
			$wantedFields = $wantedFields[1];
		}
		// Dans les autres cas, on génère un fichier de topologie complet
		else
		{
			// On va exporter une topologie complète. On récupère donc tous les champs.
                        // 31/01/2011 OJT : bz20186 Modification de l'ordre des NA
			$query = "SELECT agregation
			FROM sys_definition_network_agregation 
			WHERE family = '".$this->DataExportModel->getConfig('family')."'
			AND axe IS NULL
			ORDER BY agregation_rank DESC";
			$result = self::$database->execute($query);
			while($values = self::$database->getQueryResults($result,1)) 
			{
				$wantedFields[] = $values['agregation'];
				$wantedFields[] = $values['agregation'].'_label';
			}
			
			// On oublie pas les coords + on_off
			// 17:10 15/09/2009 GHX
			// On a les coords uniquement pour la famille principale
			if ( $this->DataExportModel->getConfig('family') == get_main_family($this->productId == 0 ? '' : $this->productId) )
			{
                            // 10/11/2011 BBX
                            // BZ 24619 : ajout d'un contrôle sur le na min MK
                            $addCoords = true;
                            if($this->DataExportModel->getConfig('export_type') == 2)
                            {
                                $mkIdProduct    = ProductModel::getIdMixedKpi();
                                $mkMainFamily   = get_main_family($mkIdProduct);
                                $familyModel    = new FamilyModel($mkMainFamily, $mkIdProduct);
                                $mkNaMin        = $familyModel->getValue('network_aggregation_min');
                                if($mkNaMin != $this->DataExportModel->getConfig('network_aggregation'))
                                    $addCoords = false;
                            }
                            if($addCoords)
                            {
				$wantedFields[] = 'azimuth';
				// maj 15:46 09/11/2009 MPR 
				// >>>>>>>>>>>>>>>>>>>>>> Correction du bug 12581 : On export par défault les coordonnées GPS
				if( $this->DataExportModel->getConfig('add_topo_file') == 2 ){
					$wantedFields[] = 'x';
					$wantedFields[] = 'y';
				}else{
					$wantedFields[] = 'longitude';
					$wantedFields[] = 'latitude';
				}
				//<<<<<<<<<<<<<<<<<<<<<<< Fin de correction du bug 12581
                            }		
                            // FIN BZ 24619
                        }
		}
		// On ajoute l'information on_oof
		$wantedFields[] = 'on_off';
		// Fin BZ 12657
		
		// On instancie un objet TopologyDownload
		$TopologyDownload = new TopologyDownload($this->DataExportModel->getConfig('family'), $this->productId);
		$TopologyDownload->setFields($wantedFields);
		// maj 15:46 09/11/2009 MPR 
		// Début Correction du BZ12581 : On export par défault les coordonnées GPS
		// Valeurs possibles pour le paramètre ( 0 => Pas de fichier de topo généré 
		//						 1=> Fichier de topologie généré avec les coordonnées GPS
		//						 2 => Fichier de topologie généré avec les coordonnées x et y)
		if( $this->DataExportModel->getConfig('add_topo_file') == 2 )
			$TopologyDownload->setCoordsType(0);
		elseif(  $this->DataExportModel->getConfig('add_topo_file') == 1 )
			$TopologyDownload->setCoordsType(1);

                // 07/09/2010 BBX
                // DE Bypass : ajout des cellules virtuelles en mode Coporate
                if($this->DataExportModel->getConfig('export_type') == 1)
                    $TopologyDownload->setExportVirtualElement(1);


		// Fin de correction du BZ12581
		// Doit-on utiliser une topologie mappée ?
		if(getTopologyMappingInfo() && ($this->DataExportModel->getConfig('use_codeq') == '1'))
			$TopologyDownload->setMapping(1);
		$filePath = $TopologyDownload->exportTopology($fileName,false);
		
		// On déplace le fichier de topologie généré vers le répertoire des exports
		$cmd = 'mv -f '.$filePath.' '.$this->targetDir.$fileName;
		exec($cmd);

		// On inscrit le fichier dans le tableau des fichiers générés
		$this->generatedFiles['topo1'] = $fileName;
	}

	/**
	*	Génère le fichier de topologie 3ème axe
	**/		
	public function buildThirdAxisTopoFile()
	{
		// Fichier de topologie
		// 10:33 03/09/2009 GHX
		// maj 28/08/2009 - Correction du bug 11271 : On remplace les espaces ou - dans le nom de l'export
		/* **************************************************************************************************** */
		/* ATTENTION NE PAS MODIFIER LE NOM DU FICHIER DE TOPO CAR DANS LE CAS D'UN COPORATE                     */
		/* ON FAIT UNE EXPRESSION REGULIERE POUR TROUVER LES FICHIERS DE TOPO GENERER PAR DATA EXPORT */
		/* **************************************************************************************************** */
		$fileName = str_replace(array(" ","-"),"_",$this->DataExportModel->getConfig('export_name')).'_topology_third_axis.csv';

		// Tablea des champs
		$wantedFields = Array();
		
		// 26/11/2009 BBX
		// Si on est dans le cas d'un Data Export pour le Corporate ou le Mixed KPI
		// et que le niveau min du Data Export n'est pas le niveau min de la famille, on génère un fichier de topologie épuré. BZ 12657
		$familyMinLevel = get_network_aggregation_min_axe3_from_family($this->DataExportModel->getConfig('family'),$this->productId);
		if((in_array($this->DataExportModel->getConfig('export_type'),Array('1','2'))) && ($this->DataExportModel->getConfig('na_axe3') != $familyMinLevel))
		{
			// On ne récupère que les niveaux d'agrégation >= au niveau min
			$wantedFields = $this->getTopoFields();
			$wantedFields = $wantedFields[3];
		}
		// Dans les autres cas, on génère un fichier de topologie complet
		else
		{	
			// On va exporter une topologie complète. On récupère donc tous les champs.
                        // 31/01/2011 OJT : bz20186 Modification de l'ordre des NA
			$query = "SELECT agregation
			FROM sys_definition_network_agregation 
			WHERE family = '".$this->DataExportModel->getConfig('family')."'
			AND axe = 3
			ORDER BY agregation_rank DESC";
			$result = self::$database->execute($query);
			while($values = self::$database->getQueryResults($result,1)) 
			{
				$wantedFields[] = $values['agregation'];
				$wantedFields[] = $values['agregation'].'_label';
			}
			
			// On oublie pas les coords + on_off
			// 17:03 15/09/2009 GHX
			// On n'a jamais les coords dans un fichier de topo 3ieme axe
			// $wantedFields[] = 'azimuth';
			// $wantedFields[] = 'x';
			// $wantedFields[] = 'y';
		}
		// On ajoute la colonne on_off
		$wantedFields[] = 'on_off';
		// Fin BZ 12657
		
		// On instancie un objet TopologyDownload
		$TopologyDownload = new TopologyDownload($this->DataExportModel->getConfig('family'),$this->productId);
		$TopologyDownload->setFields($wantedFields);
		
                // 07/09/2010 BBX
                // DE Bypass : ajout des cellules virtuelles en mode Coporate
                if($this->DataExportModel->getConfig('export_type') == 1)
                    $TopologyDownload->setExportVirtualElement(1);

		// maj 15:46 09/11/2009 MPR 
		// Début Correction du BZ12581 : On export par défault les coordonnées GPS
		// Valeurs possibles pour le paramètre ( 0 => Pas de fichier de topo généré 
		//						 1=> Fichier de topologie généré avec les coordonnées GPS
		//						 2 => Fichier de topologie généré avec les coordonnées x et y)
		if( $this->DataExportModel->getConfig('add_topo_file') == 2 ){
			$TopologyDownload->setCoordsType(0);
		}elseif( $this->DataExportModel->getConfig('add_topo_file') == 1 ){
			$TopologyDownload->setCoordsType(1);
		}
		// Fin de correction du BZ12581
		
		// Doit-on utiliser une topologie mappée ?
		if(getTopologyMappingInfo() && ($this->DataExportModel->getConfig('use_codeq') == '1'))
			$TopologyDownload->setMapping(1);		
		$filePath = $TopologyDownload->exportTopology($fileName,true);
		
		// On déplace le fichier de topologie généré vers le répertoire des exports
		$cmd = 'mv -f '.$filePath.' '.$this->targetDir.$fileName;
		exec($cmd);

		// On inscrit le fichier dans le tableau des fichiers générés
		$this->generatedFiles['topo3'] = $fileName;
	}
	
	/**
	*	Génère les fichiers d'export
	*	@return array : tableau des fichiers générés ou false si erreur
	**/	
	public function buildFiles()
	{
        /** @var Float Heure du début de la génération des fichiers d'export */
        $startBuildFiles = microtime( true );

        //Gestion du répertoire de destination
		$targetDir = new DirectoryManagement($this->targetDir);
		if(!$targetDir->autoFix(0777)) {
			$this->error = __T('A_E_CONTEXT_DIRECTORY_NOT_WRITEABLE',$this->targetDir);
			return false;
		}
		// Génération de l'export
		$this->buildExportFile();
		// Génération du fichier de compteurs et de KPI
		if($this->DataExportModel->getConfig('add_raw_kpi_file') == 1) {
			// Fichier des compteurs
			$this->buildRawFile();
			// Fichier des Kpis
			$this->buildKpiFile();
		}
		// Génération de la topologie
		if($this->DataExportModel->getConfig('add_topo_file') >= 1) {
			// Génération de la topologie 1er axe
			$this->buildFirstAxisTopoFile();
			// La famille dispose-t-elle d'un 3ème axe ?
			if(GetAxe3($this->DataExportModel->getConfig('family'), $this->productId)) {
				// Génération de la topologie 3ème axe
				$this->buildThirdAxisTopoFile();
			}
		}
		 
        // Calcul du temps de génération et enregistrement du resultat
        sys_log_ast( 'Info', get_sys_global_parameters( 'system_name' ), NULL, 'Data Export ('.$this->DataExportModel->getConfig( 'export_name' ).') duration : '.round( microtime( true ) - $startBuildFiles , 1 ), 'support_1', '' );

		// Retour du tableau des fichiers créés
		return $this->getFiles();
	}

	/**
	*	Récupère les fichiers générés
	*	@return array : tableau des fichiers générés
	**/		
	public function getFiles()
	{
		return $this->generatedFiles;
	}

	/**
	 * 28/07/2011 MMT bz 22987
	 * some executed commands like join or awk will have probleme if values of the CSV file contain the separator
	 * In order to prevent that we code the separator in the values in the file prior to command run
	 * then we decode via decodeDelimiterInFileAfterCmd once command executed
	 *
	 * @param String $filename path of the csv file to be coded
	 */
	private function codeDelimiterInFileBeforeCmd($filename){
	   TopologyDownload::replaceInCsvValues($filename,
														 $this->DataExportModel->getConfig('field_separator'),
														 TopologyDownload::CVS_SEPARATOR_ENCODE,
														 $this->DataExportModel->getConfig('field_separator'));
	}

	/**
	 * 28/07/2011 MMT bz 22987
	 * decode file coded via codeDelimiterInFileBeforeCmd
	 *
	 * @param String $filename path of the csv file to be decoded
	 */
	private function decodeDelimiterInFileAfterCmd($filename){
		TopologyDownload::replaceInCsvValues($filename,
														 TopologyDownload::CVS_SEPARATOR_ENCODE,
														 $this->DataExportModel->getConfig('field_separator'),
														 $this->DataExportModel->getConfig('field_separator'));
	}


}
?>