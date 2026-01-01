<?php

/**
 * 
 * DAO pour la base de données PostgreSQL
 * @author g.francois
 *
 */
class DatabaseServices {
	
	/**
	 * 
	 * Objet de gestion de connexion à la base de données
	 * @var DataBaseConnection
	 */
	protected $database;
	/**
	 * 
	 * Liste des noms des tables temporaires générées pour chaque ensemble de compteurs
	 * @var array
	 */
	protected $table_name;
	/**
	 * 
	 * Liste des noms des tables temporaires générées pour chaque ensemble de compteurs suffixées par "_NA"
	 * @var array
	 */
	protected $table_name_NA;
	/**
	 * 
	 * Liste des noms des tables temporaires générées pour chaque ensemble de compteurs suffixées par "_TA"
	 * @var array
	 */
	protected $table_name_TA;
	/**
	 * 
	 * Liste des tables temporaires construites pour chaque ensemble de compteurs
	 * @var array
	 */
	protected $inserts;
	/**
	 * 
	 * Liste des tables temporaires "_na" et "_ta" construites pour chaque ensemble de compteurs
	 * @var array
	 */
	protected $w_astellia_tables;
	/**
	 * 
	 * Liste des chemins absolus des noms des fichiers temporaires générés pour chaque ensemble de compteurs.
	 * @var array
	 */
	protected $copy_files;
	/*/**
	 * 
	 * Stocke la liste des paramètres des familles
	 * @var ParametersList
	 *
	protected $params;*/
	
	/**
	 * 
	 * Constructeur
	 * @param ParametersList $params Tableau des paramètres des familles
	 * @param DatabaseConnection $dbConnection Objet de gestion des connexions à la base de données
	 */
	public function __construct(DatabaseConnection $dbConnection) {
		$this->database = $dbConnection;
		$this->w_astellia_tables = array();
		$this->inserts = array();
		//$this->params = $params;
	}
	
	/**
	 * 
	 * Récupère la liste des tables temporaires construites pour chaque ensemble de compteurs
	 */
	public function getInserts() {
		return $this->inserts;
	}

	/**
	 * 
	 * Récupère la liste des tables temporaires "_na" et "_ta" construites pour chaque ensemble de compteurs
	 */
	public function getTempTables() {
		return $this->w_astellia_tables;
	}
	
	/**
	 * 
	 * Fonction qui insere dans la table sys_field_reference_all les compteurs dynamiques non déjà présent
	 * Cela utilise les résultat de la fonction get_counters_element qui récupère la liste des compteurs dynamiques
	 *
	 *	- modif 11:09 01/07/2008 GHX : prise en compte de nouvelles colonnes dans la table sys_field_reference_all edw_agregation_function_axe1 / edw_agregation_function_axe2 / edw_agregation_function_axe3
	 *
	 * @param array $liste_counters Liste des compteurs
	 * @param String $nms_table Ensemble de compteurs
	 * @param int $id_group_table Identifiant d'une famille
	 * @param String $default_value Valeur par défaut
	 * @param String $prefix_counter Préfixe d'un compteur
	 */
	public function update_dynamic_counter_list(&$listOfCountersInSourceFiles) {
		if(!empty($listOfCountersInSourceFiles)){
			// Creation d'une table temporaire sur le modele de sys_field_reference_all
			$table_uniqid = 'w_temp_' . uniqid(rand());
			$query = 'CREATE TEMP TABLE ' . $table_uniqid . ' (LIKE sys_field_reference_all)';
			$this->database->executeQuery($query); 
			// Ecriture des compteurs dynamqieus dans un fichier
			$ligne = "";
			$fichier_uniqid = REP_PHYSIQUE_NIVEAU_0 . "upload/temp_" . uniqid(rand()) . ".txt";
			$fp = fopen($fichier_uniqid, "w+");
			
			foreach ($listOfCountersInSourceFiles as $id_group_table => $listOfCounterPerGroupTable){
				//pour chaque id_group_table
				foreach ($listOfCounterPerGroupTable as $nms_table => $listOfCounterPerNmsTable){
					//pour chaque nms_table
					$id=0;
					foreach ($listOfCounterPerNmsTable as $counter_name => $prefix_counter){
						$ligne .= $id . ";" . $nms_table . ";" . $counter_name . ";$id_group_table;SUM;SUM;NA".";0;0;".$prefix_counter."\n";
						$id++;
					}
				}
			}
			fwrite($fp, $ligne);
			fclose($fp); 
			// insertion du fichier dans la table temporaire
			$query = "
				COPY 
				" . $table_uniqid . " 
				(id_ligne, nms_table, nms_field_name, id_group_table, edw_agregation_function_axe1, edw_agregation_function_axe2, edw_agregation_function_axe3, blacklisted, default_value, prefix_counter) 
				FROM 
				'" . $fichier_uniqid . "' 
				WITH DELIMITER ';'";
			$this->database->executeQuery($query); 
			// insertion dans la table sys_field_reference_all des nouveaux compteurs
			
			//expression réguliere créée pour transformer le nms_field_name avec la forme "SDCCH call drop||2070||536872982" en un une expression réguliere "^SDCCH call drop$|^2070$|^536872982$"
			//il a fallu également échapper les caractère '(' , ')' et '+'.
			
			//'split_part(sys_field_reference.nms_field_name,\'@@\',1)';
			//$reg_expression='(\'^\'||replace(replace(replace(sys_field_reference.nms_field_name,\'||\',\'$|^\'),\')\',\'\\\\)\'),\'(\',\'\\\\(\')||\'$\')';
			$reg_expression='\'^\'||replace(replace(replace(replace(split_part(sys_field_reference.nms_field_name,\'@@\',1),\'||\',\'$|^\'),\')\',E\'\\\\)\'),\'(\',E\'\\\\(\'),\'+\',E\'\\\\+\')||\'$\'';
			$query = '
				BEGIN WORK;
				LOCK TABLE sys_field_reference_all, '.$table_uniqid.' IN EXCLUSIVE MODE;
				INSERT INTO 
				sys_field_reference_all 
				(nms_table, nms_field_name, id_group_table, edw_agregation_function_axe1, edw_agregation_function_axe2, edw_agregation_function_axe3, blacklisted, default_value, prefix_counter) 
				SELECT 
				nms_table,
				nms_field_name,
				id_group_table,
				edw_agregation_function_axe1,
				edw_agregation_function_axe2,
				edw_agregation_function_axe3,
				blacklisted,
				default_value,
				prefix_counter 
					FROM 
					' . $table_uniqid . ' 
					WHERE 
					NOT EXISTS 
					(
					 SELECT 
					 nms_field_name 
					 FROM 
					 sys_field_reference_all 
					 WHERE 
					 sys_field_reference_all.id_group_table = ' . $table_uniqid . '.id_group_table AND lower(sys_field_reference_all.nms_field_name) = lower(' . $table_uniqid . '.nms_field_name) 
					 LIMIT 1
					)
					AND NOT EXISTS
					(
					 SELECT 
					 nms_field_name 
					 FROM 
					 sys_field_reference
					 WHERE 
					 sys_field_reference.id_group_table = ' . $table_uniqid . '.id_group_table::smallint  AND (' . $table_uniqid . '.nms_table = \'unknown\' OR lower(sys_field_reference.nms_table) = lower(' . $table_uniqid . '.nms_table)) AND ' . $table_uniqid . '.nms_field_name  ~* cast(' . $reg_expression .' as text) 
					 LIMIT 1
					);
					COMMIT WORK;';
			//commentaire sur la requete: on vérifie dans un premier temps que le compteur trouvé dans le fichier source n'est pas déjà dans sys_field_reference_all et ensuite
			// qu'il n' est pas parmi les compteurs non activé de sys_field_reference. (il y a eu une verification préalable sur les compteurs activés de sys_field_reference).
			$res = $this->database->executeQuery($query);
			$lErreur = $this->database->getLastError();
			if($lErreur != '')
				// 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
				displayInDemon($lErreur.' on '.$query.';<br>'."\n", 'alert');
			elseif (Tools::$debug) {
				// 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
				displayInDemon($this->database->getAffectedRows($res).' nouveaux compteurs dynamiques inseres<br>'."\n");
			}
			unlink($fichier_uniqid);
			
		}
			

		
	}
	
	/**
	 * 
	 * Récupère la liste des fichiers collectés vérifiant la condition $fileType
	 * @param $fileType
	 */
	public function getFiles( FileTypeCondition $fileType=NULL) {
		//récupération des fichiers sources à traiter
		$condition="";
		if(isset($fileType)) $condition="AND {$fileType->getDBCondition()}";

		$query = "SELECT flat_file_location, uploaded_flat_file_name, flat_file_name, flat_file_template, prefix_counter, capture_duration,hour
			FROM sys_flat_file_uploaded_list, sys_definition_flat_file_lib
			WHERE flat_file_template = flat_file_naming_template AND on_off = 1 $condition ".//AND flat_file_template ~* '{$fichier_extension_recherche}$|{$fichier_extension_recherche}\\|'
			"ORDER BY uploaded_flat_file_name";
		Tools::$debug && displayInDemon(__METHOD__ . " query : $query");
		$res = $this->database->executeQuery($query);
		$flatFiles = new FlatFileList();
		while($values = $this->database->getQueryResults($res, 1)) {
			if (! $flatFiles->isExist($values["uploaded_flat_file_name"])) {
				$flatFile = new FlatFile();
				$flatFile->flat_file_location		= $values["flat_file_location"];
				$flatFile->uploaded_flat_file_name	= $values["uploaded_flat_file_name"];
				$flatFile->flat_file_template		= $values["flat_file_template"];
				$flatFile->prefix_counter			= $values["prefix_counter"];
				$flatFile->capture_duration			= $values["capture_duration"];
				
				$flatFile->flat_file_name 			= $values["flat_file_name"];
				$flatFile->hour 			=	$values["hour"];
				$flatFiles->add($flatFile);
				$flatFile->get_special_conf();
			}
		}
		
		Tools::$debug && displayInDemon(__METHOD__ . count($flatFiles) . " resultats");
		return $flatFiles;
	}

	/**
	 * 
	 * Fonction pour récupérer le fichier de topo valide le plus récent
	 * @param $fichier_extension_recherche
	 */
	public function getTopoFile(TopoParser $topoParserObject) {
		
		$fileType=$topoParserObject->fileType;
		$condition=$fileType->getDBCondition();

		
		
		$query = "SELECT flat_file_location, uploaded_flat_file_name, flat_file_name, flat_file_template, prefix_counter, capture_duration
			FROM sys_flat_file_uploaded_list, sys_definition_flat_file_lib
			WHERE  flat_file_template = flat_file_naming_template AND on_off = 1  AND $condition
			ORDER BY hour DESC limit 1";
		$query_archive = "SELECT flat_file_location, uploaded_flat_file_name, flat_file_name, flat_file_template, prefix_counter, capture_duration,day
			FROM sys_flat_file_uploaded_list_archive, sys_definition_flat_file_lib
			WHERE  flat_file_template = flat_file_naming_template AND on_off = 1 AND $condition
			ORDER BY hour DESC limit 5";
		Tools::$debug && displayInDemon(__METHOD__ . " query : $query");
		$res = $this->database->executeQuery($query);
		$values = $this->database->getQueryResults($res, 1);
		$invalidTopoFile=false;
		if($values!=false){
			$flatFile = new FlatFile();
			$flatFile->flat_file_location		= $values["flat_file_location"];
			$flatFile->uploaded_flat_file_name	= $values["uploaded_flat_file_name"];
			$flatFile->flat_file_template		= $values["flat_file_template"];
			$flatFile->prefix_counter			= $values["prefix_counter"];
			$flatFile->capture_duration			= $values["capture_duration"];
			//si le fichier collecté est invalide
			if(!$topoParserObject->isValidTopoFile($flatFile)){
				displayInDemon("Warning: invalid topology file collected => {$flatFile->uploaded_flat_file_name}",'alert');
				sys_log_ast("Warning", "Trending&Aggregation", "Data Collect", "The last topology file collected is invalid", "support_1", "");
				//si pas valide on passe au fichier suivant
				$flatFile=NULL;
				$invalidTopoFile=true;
			}
		}
		//si fichier collecté invalide ou pas de fichier collecté
		if(($values==false)||$invalidTopoFile){
			if($invalidTopoFile) displayInDemon("The last topology file collected is invalid");
			$res = $this->database->executeQuery($query_archive);
			$values = $this->database->getQueryResults($res);
			//aucuns fichiers archivés trouvés
			if($values==false){
				displayInDemon("Warning: no topology file in archive; searching it in archive",'alert');
				return NULL;
			}
			
			foreach ($values as $value){
				$topo_file_location=$value["flat_file_location"];
				$file_destination 	= REP_PHYSIQUE_NIVEAU_0."upload/".uniqid(rand()).".txt";
				Tools::bunzip2($topo_file_location, $file_destination);
				$flatFile = new FlatFile();
				$flatFile->flat_file_location		= $file_destination;
				$flatFile->uploaded_flat_file_name	= $value["uploaded_flat_file_name"];
				$flatFile->flat_file_template		= $value["flat_file_template"];
				$flatFile->prefix_counter			= $value["prefix_counter"];
				$flatFile->capture_duration			= $value["capture_duration"];
				//test si le fichier de topo est valide ou pas
				if(!$topoParserObject->isValidTopoFile($flatFile)){
					//si pas valide on passe au fichier suivant
					unlink($flatFile->flat_file_location);
					$flatFile=NULL;
					displayInDemon("Warning: invalid topo file in archive => {$flatFile->uploaded_flat_file_name}",'alert');
					continue;
				}
				
				//TODO MHT2 penser au cas où aucun des fichiers archivés n'est valide
				if( preg_match("/^([0-9]{4})([0-9]{2})([0-9]{2})$/", $value["day"], $matches) )
				{
					$tsLastUpd 	= mktime(0, 0, 0, $matches[2], $matches[3], $matches[1]);
					$tsNow		= time();
					$periodDay	= intval(($tsNow - $tsLastUpd) / (3600*24));
	
					$message = "Topology file is not collected since {$periodDay} days";
					
					if( $periodDay >= 10 && $periodDay < 20 )
					{
						displayInDemon("Warning: {$message}\n", "alert");
						sys_log_ast("Warning", "Trending&Aggregation", "Data Collect", $message, "support_1", "");
					}
					elseif( $periodDay >= 20 )
					{
						displayInDemon("Critical : {$message}\n", "alert");
						sys_log_ast("Critical", "Trending&Aggregation", "Data Collect", $message, "support_1", "");
					}
				}
				//on arrete lorqu'un fichier valide a été trouvé.
				break;
			}


		}
		return $flatFile;
		

	}
	/**
	 * 
 	 * Fonction qui supprime de la table sys_flat_file_uploaded_list l'heure traitée
	 * une fois le traitement des fichiers pour cette heure terminé.
	 * @param String $hour Heure des fichiers collectés
	 */
	public function clean_flat_file_uploaded_list(FlatFileList $flatFiles) {
		
		foreach ($flatFiles as $flatFile) {
			$file=$flatFile->flat_file_location;
			if(file_exists($file))
				unlink($file);
			$query_clean = "
			DELETE FROM 
			sys_flat_file_uploaded_list 
			WHERE 
			flat_file_location = '" . $file . "'";
			$result_clean = $this->database->executeQuery($query_clean);
		}

	}
	
/**
 * 
 * Supprime les fichiers uploadés portant une extension donnée
 * @param $flatfile
 */
	public function clean_flat_file_extensions_uploaded_list(FileTypeCondition $fileType) {
		
		$condition=$fileType->getDBCondition();
		
		$query = "SELECT flat_file_location, uploaded_flat_file_name, flat_file_name, flat_file_template, prefix_counter, capture_duration
			FROM sys_flat_file_uploaded_list, sys_definition_flat_file_lib
			WHERE flat_file_template = flat_file_naming_template AND on_off = 1 AND $condition".//AND flat_file_template ~* '{$fichier_extension_recherche}$|{$fichier_extension_recherche}\\|'
			"ORDER BY uploaded_flat_file_name";
		

		$res = $this->database->executeQuery($query);
		while($values = $this->database->getQueryResults($res, 1))
			$files[] = $values['flat_file_location'];
		if(is_array($files))
		foreach($files AS $file)
		{
			if(file_exists($file))
				unlink($file);
			$query_clean = "
				DELETE FROM 
				sys_flat_file_uploaded_list 
				WHERE 
				flat_file_location = '" . $file . "'";
			$result_clean = $this->database->executeQuery($query_clean);
		}
		

	}
	
	
	
/**
	 * Fonction de création des tables W et W_temp
	 * @param String $hour Heure où les fichiers sont collectés
	 * @param array $tableau_entite tableau double contenant en clé le numéro de l'entité, les types de champs à insérer, le groupe table, le network, le nom de la table, sai spécial 60
	 * @param String $fileExtension Extension des fichiers sources
	 */
	public function generic_create_table_w($hour, ParametersList $paramList) {
		$day = substr($hour, 0, 8);
		$this->table_name_TA = array();
		$this->table_name_NA = array();
		foreach($paramList AS $param) {
			foreach($param->network AS $level) {
				foreach ($param->todo as $cle_entite => $counters) {
					if(!isset($this->table_name[$cle_entite][$level]))
					{
						//Récupération de tous les compteurs de l'entité
						$fields = array();
						if (Tools::$debug) displayInDemon(__METHOD__." cle_entite=".$cle_entite);
						foreach ($counters as $counter) {
							$fields[] = "{$counter->edw_field_name} {$counter->type}";
						}
						if (!empty($fields)) {
							//Création des tables temporaires
							$this->table_name[$cle_entite][$level] 		= strtolower("w_astellia_{$cle_entite}_{$level}_{$hour}");
							if( !$this->isTableExiste( $this->table_name[$cle_entite][$level] ))
							{
								$query_create 		= "create table {$this->table_name[$cle_entite][$level]}  ({$param->field}, ".implode(", ", $fields).")";
								if (Tools::$debug) 	displayInDemon("Table copy : {$query_create}<br>\n");
								$this->database->execute($query_create);
							}
							$this->table_name_NA[$cle_entite][$level] 	= "{$this->table_name[$cle_entite][$level]}_na";
							if( !$this->isTableExiste( $this->table_name_NA[$cle_entite][$level] ))
							{
								$query_create 		= "create table {$this->table_name_NA[$cle_entite][$level]}  (min int, {$param->field}, ".implode(", ", $fields).")";
								if (Tools::$debug) 	displayInDemon("Table copy NA : {$query_create}<br>\n");
								$this->database->execute($query_create);
							}
							$this->table_name_TA[$cle_entite][$level] 	= "{$this->table_name[$cle_entite][$level]}_ta";
							if( !$this->isTableExiste( $this->table_name_TA[$cle_entite][$level] ))
							{
								$query_create 		= "create table {$this->table_name_TA[$cle_entite][$level]}  (min int, {$param->field}, ".implode(", ", $fields).")";
								if (Tools::$debug) 	displayInDemon("Table copy TA : {$query_create}<br>\n");
								$this->database->execute($query_create);
							}
							$this->insert_in_w_tables_list($this->table_name[$cle_entite][$level], $param->id_group_table, $hour, $day, $level);
						}	
					}
					// Stockage des noms des fichiers csv pré import (COPY) dans les tables w_astellia
					$this->copy_files[$cle_entite][$level] = Tools::getCopyFilePath($level, $cle_entite,$hour);
				}
			}
		}
	}
	
	/**
	* Fonction de création des tables W et W_temp
	* @param array|string $hours Heure(s) où les fichiers sont collectés
	* @param array $tableau_entite tableau double contenant en clé le numéro de l'entité, les types de champs à insérer, le groupe table, le network, le nom de la table, sai spécial 60
	* @param String $fileExtension Extension des fichiers sources
	*/
public function generic_create_table_w_by_entity($hour, Parameters $param,$entity) {
		$this->table_name_TA = array();
		$this->table_name_NA = array();
		$day = substr($hour, 0, 8);
		// pour chaque niveau réseau minimum (1 seul en général)
		foreach($param->network AS $level) {
			// TODO : on mode multi processus, il ne faudrait pas boucler sur les entités. Je propose
			// de déplacer vers une nouvelle méthode privée le code chargé de traiter une entité
			// Exemple :
			/*
			if(isset($entity)){
			$this->generic_create_table_w_for_one_entity($entity, $counters, $day, ...);
			}
			else {
			foreach entité {
			$this->generic_create_table_w_for_one_entity(...)
			}
			*/
			
			//TODO MHT2 problème avec les entité déclarée dans sfr, mais non utilisée par des fichiers exemple : defaut sur huaran
			//en multi process, ne traiter que l'entité spécifiée
			$cle_entite=$entity;	
			
			$counters=$param->todo[$cle_entite];
						
			if(!isset($this->table_name[$cle_entite][$level]))
			{
				// file_demon
				if (Tools::$debug) displayInDemon(__METHOD__." cle_entite=".$cle_entite);
						
				// noms des colonnes à créer = noms des compteurs de l'entité
				$fields = array();
				foreach ($counters as $counter) {
					$fields[] = "{$counter->edw_field_name} {$counter->type}";
				}
					
				// si l'entité courrante contient au moins un compteur (actif, Cf. lib/Parser->initiateParam())
				if (!empty($fields)) {
				// TODO : stocker "$this->table_name[$cle_entite][$level]" dans une varaible pour éviter d'aller le chercher n fois ci-dessous
					//Si on se trouve dans le cas d'un bybass day alors il faut construire la table temporaire differement
					if($param->aggregLevel == "day"){
						// creation de la table temporaire w_edw_...
						$this->table_name[$cle_entite][$level] = strtolower("w_edw_{$param->codeProduct}_{$param->family}_axe1_raw_{$level}_bypass_day_{$day}");
						if( !$this->isTableExiste( $this->table_name[$cle_entite][$level] ))
						{
							$query_create 		= "create table {$this->table_name[$cle_entite][$level]}  ({$param->field}, ".implode(", ", $fields).")";
							if (Tools::$debug) 	displayInDemon("Table copy : {$query_create}<br>\n");
							$this->database->execute($query_create);
						}
			
						// creation de la table temporaire w_edw_...NA
						$this->table_name_NA[$cle_entite][$level] = "{$this->table_name[$cle_entite][$level]}_na";
						
						if( !$this->isTableExiste( $this->table_name_NA[$cle_entite][$level] ))
						{
							$query_create 		= "create table {$this->table_name_NA[$cle_entite][$level]}  (min int, {$param->field}, ".implode(", ", $fields).")";
							if (Tools::$debug) 	displayInDemon("Table copy NA : {$query_create}<br>\n");
												$this->database->execute($query_create);
						}
											
						// creation de la table temporairew_edw_...TA
						$this->table_name_TA[$cle_entite][$level] = "{$this->table_name[$cle_entite][$level]}_ta";
						if( !$this->isTableExiste( $this->table_name_TA[$cle_entite][$level] ))
						{
							$query_create 		= "create table {$this->table_name_TA[$cle_entite][$level]}  (min int, {$param->field}, ".implode(", ", $fields).")";
							if (Tools::$debug) 	displayInDemon("Table copy TA : {$query_create}<br>\n");
							$this->database->execute($query_create);
						}
			
						// on insère dans sys_w_tables_list le nom de la table w_edw et le time aggregation...,
						// ce qui servira d'input à la step create_temp_table

						$this->insert_in_w_tables_list_bypass($this->table_name[$cle_entite][$level], $param->id_group_table, $level,$hour, $day,"day" );
					}else{
						// creation de la table temporaire w_astellia_...
							$this->table_name[$cle_entite][$level] = strtolower("w_astellia_{$cle_entite}_{$level}_{$hour}");
						if( !$this->isTableExiste( $this->table_name[$cle_entite][$level] ))
						{
							$query_create 		= "create table {$this->table_name[$cle_entite][$level]}  ({$param->field}, ".implode(", ", $fields).")";
							if (Tools::$debug) 	displayInDemon("Table copy : {$query_create}<br>\n");
							$this->database->execute($query_create);
						}
			
											// creation de la table temporaire w_astellia_...NA
						$this->table_name_NA[$cle_entite][$level] = "{$this->table_name[$cle_entite][$level]}_na";
						if( !$this->isTableExiste( $this->table_name_NA[$cle_entite][$level] ))
						{
							$query_create 		= "create table {$this->table_name_NA[$cle_entite][$level]}  (min int, {$param->field}, ".implode(", ", $fields).")";
							if (Tools::$debug) 	displayInDemon("Table copy NA : {$query_create}<br>\n");
												$this->database->execute($query_create);
						}
											
						// creation de la table temporaire w_astellia_...TA
						$this->table_name_TA[$cle_entite][$level] = "{$this->table_name[$cle_entite][$level]}_ta";
						if( !$this->isTableExiste( $this->table_name_TA[$cle_entite][$level] ))
						{
							$query_create 		= "create table {$this->table_name_TA[$cle_entite][$level]}  (min int, {$param->field}, ".implode(", ", $fields).")";
							if (Tools::$debug) 	displayInDemon("Table copy TA : {$query_create}<br>\n");
							$this->database->execute($query_create);
						}
			
						// on insère dans sys_w_tables_list le nom de la table w_astellia_...,
						// ce qui servira d'input à la step create_temp_table
						$this->insert_in_w_tables_list($this->table_name[$cle_entite][$level], $param->id_group_table, $hour, $day, $level);
					}
				}
			}
			// mémorisation du nom du fichier csv (d'extension ".sql"), qui sera importé (requête COPY) dans une table w_astellia.
			// on ne cherche pas ici à savoir l'entité $cle_entite est vraiment présente dans les PM files parsés.
			$this->copy_files[$cle_entite][$level] = Tools::getCopyFilePath($level, $cle_entite,$hour);
		}
	}
	
	/**
	 * 
	 * Nettoyage des tables temporaires _NA et _TA
	 */
	public function clean_copy_tables()	{
		foreach ($this->table_name_NA AS $todo => $temp_table) {
			foreach ($temp_table AS $level => $table) {
				postgres_drop_table(strtolower($table));
			}
		}
		foreach ($this->table_name_TA AS $todo => $temp_table) {
			foreach ($temp_table AS $level => $table) {
				postgres_drop_table(strtolower($table));
			}
		} 
		unset($this->table_name);
		unset($this->table_name_NA);
		unset($this->table_name_TA);
	}
	
	/**
	 * Fonction qui nettoie les fichiers csv (d'extension ".sql", Cf. Tools->getCopyFilePath) contenant les données insérées dans les tables
	 * Nettoie également la table temporaire captured_cells
	 */
	public function clean_copy_files() {
		$this->database->executeQuery("drop table if exists captured_cells");
		if(!isset($this->copy_files)) { return; }
		foreach($this->copy_files AS $todo => $array_level)
		{
			foreach ($array_level AS $level => $inutile) {
				if(file_exists($this->copy_files[$todo][$level]))
					unlink($this->copy_files[$todo][$level]);
			}
		}
		unset($this->copy_files);
	} 
	
	/**
	 * 
	 * Créer la table temporaire captured_cells
	 */
	public function create_captured_cells_table() {
		$query="create table captured_cells (cell text, hour bigint, day bigint, week int, month int,capture_duration int,capture_duration_expected int,capture_duration_real int)";
		$this->database->executeQuery($query);
		displayInDemon($query);
	}
	
	/**
	 * 
	 * Insère dans la table de topologie les cellules stockées dans captured_cells
	 * à condition qu'elles n'y existent pas déjà
	 * @param String $key
	 * @param String $table
	 */
	public function insertOrpheanCellsIntoTopo($key, $table) {
		$fields=",capture_duration,capture_duration_expected,capture_duration_real";
		$query="insert into $table (".$key.$fields.") select distinct t0.cell,hour,day,week,month".$fields." from captured_cells t0
			inner join (
				select
					eoar_id,
					CASE	WHEN split_part(eoar_arc_type, '|', 3) = 'bsc' THEN 'bss'
						WHEN split_part(eoar_arc_type, '|', 3) = 'pcu' THEN 'bssgprs'
					END as family
				from edw_object_arc_ref
			) as foo on foo.family = split_part('$table', '_', 3) and foo.eoar_id = t0.cell
			where t0.cell not in (select cell from $table)";
		$result=$this->database->executeQuery($query);
		displayInDemon($this->database->getAffectedRows($result)." = ".$query);
	}
	
	/**
	 * 
	 * Insère les données dans la table captured_cells
	 * @param String $key Champ specific_field (ex : "specific_field" => "cell,hour,day,week,month")
	 * @param String $table Nom de l'ensemble des compteurs
	 */
	public function insertIntoCapturedCellsTable($key, $table) {
		$query="insert into captured_cells select distinct ".$key.",capture_duration,capture_duration_expected,capture_duration_real from $table";
		$result=$this->database->executeQuery($query);
		displayInDemon($this->database->getAffectedRows($result)." = ".$query);
	}
	
	/**
	 * 
	 * Copie les valeurs des fichiers sources vers les tables temporaires
	 * Fonctionnement général :
	 *     1) insertion initiale : commande COPY insérant le contenu d'un fichier temporaite dans une table w_astellia..._NA
	 *     2) pré-agrégation réseau : insertion dans une table w_astellia..._TA avec un "group by min, <specific fields>
	 *     3) pré-agrégation temporelle : insertion dans une table w_astellia..._tmp avec un "group by <specific fields>		
	 */
	public function copy_into_temp_tables(ParametersList $params) {
		// TODO : la méthode getParamWithTodo est couteuse, surtout vu le nombre 
		// d'appel à copy_into_temp_tables en mode multi processus.	
		// Son appel pourrait être évité en insérant un niveau "famille" :
		//     =>  $this->copy_files[$id_group_table][$cle_entite][$level]
		//     au lieu de : $this->copy_files[$cle_entite][$level]
		// Conséquence ici : on bouclerait sur les familles, et sur chaque entité de la famille courante
		
		// pour chaque fichier CSV temporaire
		foreach ($this->copy_files AS $todo => $array_level) {
			//$param = $params->getParamWithTodo($todo);
			//TODO MHT2 getWithFamily semble plus rapide, le todo contient toujours la famille
			$param = $params->getWithFamily(substr($todo,0,strpos($todo,"_")));
			
			if (!isset($param)) {
				// message d'erreur
				return null;
			}
			// pour chaque niveau réseau minimum 
			foreach ($array_level AS $network => $flat_file_location) {
				if(file_exists($flat_file_location)) {
					
					// on souhaite construire la liste des formule d'agrégation et la liste des edw_field_name
					$aggregation_formula_list = array();
					$field = array();

					// pour chaque compteur
					foreach ($param->todo[$todo] as $counter) {
						$aggregation_formula_list[$counter->edw_field_name] = $counter->aggregation_formula;
						//V0.9 supprimer isset($counter->nms_field_name_in_file) and 
						if ($counter->on_off == 1) {
							$field[] = $counter->edw_field_name;
						}
					}
					// REQUETE DE COPY vers la table w_astellia_..._NA
					$query_copy = "COPY {$this->table_name_NA[$todo][$network]} (min, {$param->specific_field} ,".implode(", ", $field).") FROM '{$flat_file_location}'  with delimiter ';' NULL AS ''";

					// $res = $this->executeSqlWithError($query_copy);
					if ($this->executeSqlWithError($query_copy)) {
						
						// en mode debug, affiche le nombre de ligne copiées 
						if (Tools::$debug){
							//Le log ci-dessous est KO (Cf. 26972)
							//$nb_ligne = $this->database->getAffectedRows();
							$nb_ligne=count(file($flat_file_location));
							displayInDemon("<span style='color:green;'> - {$nb_ligne} lignes inserees pour l'entite {$todo} et le niveau {$network}</span>\n");
						}
						
						//supression des lignes correspondant aux NE desactivés
						if($param->deactivated_NE!=false){
							$deactivatedNE_list="'".implode("','", $param->deactivated_NE)."'";
							displayInDemon("Deactivated $network : $deactivatedNE_list");
							$deleteQuery="delete from {$this->table_name_NA[$todo][$network]} where $network in ($deactivatedNE_list) ;";
							$this->executeSqlWithError($deleteQuery);
						}
						
						// en mode debug, affichage de tout le contenu de la table NA dans le file_demon
						if (Tools::$debug)	$this->displayDataTable($this->table_name_NA[$todo][$network], $query_copy);	
							
						//REQUETE AGGREGATION RESEAU (w_astellia_...NA -> w_astellia_...TA)
						$aggreg_net_ri = 1;
						$query = "
							INSERT INTO {$this->table_name_TA[$todo][$network]} 	(min, {$param->specific_field}, ".implode(", ", array_keys($aggregation_formula_list)). ") 
							SELECT 		min, {$param->specific_field}, ".implode(", ", $aggregation_formula_list) . " 
							FROM   		{$this->table_name_NA[$todo][$network]}
						GROUP BY 	min, {$param->specific_field}";
						eval("\$query = \"$query\";");
						$this->executeSqlWithError($query);
						
						// en mode debug, affichage de tout le contenu de la table TA dans le file_demon
						if (Tools::$debug)	$this->displayDataTable($this->table_name_TA[$todo][$network], $query);
							
						//REQUETE AGGREGATION TEMPORELLE  (w_astellia_...TA -> w_astellia_...)
						$aggreg_net_ri = 0;
						$query = "
							INSERT INTO {$this->table_name[$todo][$network]}	({$param->specific_field}, ".implode(", ", array_keys($aggregation_formula_list)).") 
							SELECT 		{$param->specific_field}, ".implode(", ", $aggregation_formula_list)." 
							FROM 		{$this->table_name_TA[$todo][$network]}
						GROUP BY 	{$param->specific_field}";
						// eval exécute une chaîne comme un script PHP
						// TODO : sert à quoi ?
						eval("\$query = \"$query\";");			
						$this->executeSqlWithError($query);
						
						// en mode debug, affichage de tout le contenu de la table temp dans le file_demon
						if (Tools::$debug)	$this->displayDataTable($this->table_name[$todo][$network], $query);
						
						// nom de la table temporaire pour la colonne spécifique à la famille (ex : cell)
						// (utilisé par quel produit ?)
						$this->inserts[$param->specific_field][] = $this->table_name[$todo][$network];
					}
					else {
						displayInDemon("<span style='color:red;'>Entite[{$todo}] : Erreur dans la requete de COPY</span>\n");
					}
				}
				else {
					displayInDemon("Entite[{$todo}] : Le fichier pour le COPY n'est pas present\n");
					if (Tools::$debug)	displayInDemon("Entite[{$todo}] : Le fichier attendu est ".$flat_file_location."\n");
					$this->w_astellia_tables[$param->specific_field][]=$this->table_name[$todo][$network];
				} 
			}
		}
	}
	
	/**
	 * Fonction d'insertion des tables de données qui ont été génrées dans la table sys_w_tables_list
	 * 
	 * @param text $table nom de la table qui contient les données
	 * @param int $group_table identifiant du group table
	 * @param int $hour heure traitée
	 * @param int $day jour traité
	 * @param text $network niveau réseau correspondant au contenu de la table
	 */
	private function insert_in_w_tables_list($table, $group_table, $hour, $day, $network) {
		//$query = "INSERT INTO sys_w_tables_list (hour, day, table_name, group_table, network) VALUES ($hour,$day,'$table','$group_table','$network')";
		$query = "INSERT INTO sys_w_tables_list (hour, day, table_name, group_table, network) ( select $hour as hour ,$day as day,'$table' as table_name,'$group_table' as group_table,'$network' as network where not exists (select 1 from sys_w_tables_list where table_name='$table' and hour=$hour));";
		$this->database->executeQuery($query);
	} 
	
	
	/**
	 * Fonction d'insertion des tables de données qui ont été génrées dans la table sys_w_tables_list en mode bypass
	 * 
	 * @param text $table nom de la table qui contient les données
	 * @param int $group_table identifiant du group table
	 * @param int $hour heure traitée
	 * @param int $day jour traité
	 * @param text $network niveau réseau correspondant au contenu de la table
	 * @param text $ta niveau temporel
	 */
	private function insert_in_w_tables_list_bypass($table, $group_table, $network,$hour,$day, $ta)
    {
            $query = "INSERT INTO sys_w_tables_list(hour, day, table_name, group_table, network, ta)
                    VALUES({$hour}, {$day}, '{$table}', '{$group_table}', '{$network}','{$ta}')";
            
            $this->database->executeQuery($query);
    } 
	
	/**
	 * 
	 * Exécute une requête SQL
	 * @param String $query Requête SQL
	 * @return boolean Vrai si la requête est réussie, faux sinon
	 */
	public function executeSqlWithError($query) {
		$resultat = $this->database->execute($query);
		//récupération du message d'erreur
		$lErreur = $this->database->getLastError();
		if ($lErreur != '') {
			//affichage du message d'erreur
			displayInDemon($lErreur.' '.$query.';<br>'."\n", 'alert');
			return false;
		}
		elseif (Tools::$debug) {
			//affichage de la requete en mode debug
			displayInDemon($this->database->getAffectedRows($resultat).' = '.$query.'<br>'."\n");
		}
		return true;
	}
	
	/**
	 * Fonction qui test si la table $tableName existe
	 * 
	 * @param String $tableName Nom de la table
	 * @return boolean Vrai si la table existe, faux sinon
	 */
	private function isTableExiste($tableName) {
		$query = "SELECT * FROM pg_tables WHERE schemaname = 'public' AND tablename = '{$tableName}'";
		$result =  $this->database->execute($query);
		if($this->database->getNumRows() == 0)		return false;
		else										return true;
	}
	
	/**
	 * 
	 * Retourne la liste des heures où des fichiers ont été collectés
	 * @return array $tabHours
	 */
	public function getHoursCollected(FileTypeCondition $fileType=NULL) {
		$tabHours = array();
		$query = "SELECT hour, count(*) as nb FROM";
		if(isset($fileType)) {
			$condition=$fileType->getDBCondition();
			$query .= " sys_flat_file_uploaded_list, sys_definition_flat_file_lib where flat_file_template = flat_file_naming_template and ".$condition;
		}
		else {
			$query .= " sys_flat_file_uploaded_list";
		}
		$query .= " group by hour having hour IS NOT NULL ORDER BY hour ASC";
		
		$result = $this->database->executeQuery($query);
		while($row = $this->database->getQueryResults($result,1)) {
			$tabHours[$row["hour"]] = $row["nb"];
		}
		return $tabHours;
	}
	
	/**
	 * 
	 * Renvoie la liste des compteurs activés
	 * @return CounterList $counters
	 */
	public function getAllCounters(ParametersList $params) {
		$query = "SELECT id_group_table, nms_table, nms_field_name, edw_field_name, edw_field_type, default_value, edw_agregation_function, edw_agregation_formula, on_off, flat_file_position FROM sys_field_reference WHERE on_off = 1 order by nms_table, nms_field_name";
		$result = $this->database->executeQuery($query);
		$values = $this->database->getQueryResults($result);

		$counters = new CounterList();
		foreach ($values as $value) {
			$counter = new Counter();
			$counter->id_group_table = $value["id_group_table"];
			$counter->on_off = $value["on_off"];
			$counter->nms_table = $value["nms_table"];
			$nms_list = explode('||', $value["nms_field_name"]);
			foreach ($nms_list as $nms_field_name) {
				$nms_field_name=trim($nms_field_name);
				$counter->nms_field_name[] = strtolower($nms_field_name);
			}
			$counter->edw_field_name = $value["edw_field_name"];
			$counter->type = $value["edw_field_type"];
			$counter->default_value = $value["default_value"];
			$param = $params->getWithGroupTable($value["id_group_table"]);
			$counter->family = $param->family;
			if($value['edw_field_name'] == 'capture_duration_real') {
				$counter->aggregation_formula = 'AVG(capture_duration_real)';
			}
			elseif($value['edw_field_name'] == 'capture_duration') {
				$counter->aggregation_formula = 'MAX(capture_duration)';
			}
			elseif($value['default_value'] == "" || strtolower($value['edw_agregation_function']) == 'log') {
				$counter->aggregation_formula = $value['edw_agregation_formula']; 
			}
			else {
				$counter->aggregation_formula = $value['edw_agregation_function'] . "(". $value['edw_field_name'] . ")";
			}
			$counter->flat_file_position = $value["flat_file_position"];
			$counters->add($counter);
		}
		return $counters;
	}
	
	/**
	 * 
	 * Met à jour la date de la dernière MAJ de la topologie
	 * @return boolean
	 */
	public function updateLastTopologyDate() {
		// MAJ du parametre global pour stocker la date de la mise à jour de la topo
		$query="update sys_global_parameters set value='".date("Ymd")."' where parameters='topology_last_update_date'";
		return $this->executeSqlWithError($query);
	}
	
	/**
	*
	* Met à jour la date de la dernière Automatic mapping
	* @return boolean
	*/
	public function updateLastAutomappingDate() {
		// MAJ du parametre global pour stocker la date de la mise à jour de la topo
		$query="update sys_global_parameters set value='".date("Ymd")."' where parameters='automapping_last_update_date'";
		return $this->executeSqlWithError($query);
	}
	
	/**
	 * Fonction qui va mettre à jour des informations (hour,day,flat_file_uniqid)
	 * pour les fichiers secondaires issus d'un fichier principal
	 * 
	 * Le fichier principal contient les information de date alors que les fichiers secondaires n'en contiennent pas
	 * 
	 * Le lien entre le fichier principal et les fichiers secondaires se fait par les radicaux des fichiers qui sont les mêmes
	 * utiliser uniquement pour les fichiers d'Astellia
	 * 
	 */
	public function updateTimeData($repertoire_archive) {
		// suppression des fichiers déjà collectés qui viennent d'être repris
		$query = "
			DELETE FROM 
			sys_flat_file_uploaded_list_archive 
			WHERE 
			flat_file_uniqid IN 
			(
				 SELECT 
				 	t0.flat_file_uniqid 
				 FROM 
					 sys_flat_file_uploaded_list_archive t0, 
					 sys_flat_file_uploaded_list t1 
				 WHERE 
					 t0.flat_file_uniqid = t1.flat_file_uniqid 
					 AND t1.hour IS NOT NULL 
					 AND t0.modification_date != t1.modification_date
			)";
		$res = $this->database->executeQuery($query);
		
		// Stockage dans la table archive des fichiers collectés qui n'existe pas déjà
		$query = "
			INSERT INTO 
			sys_flat_file_uploaded_list_archive 
			(id_connection, hour, day, flat_file_template, flat_file_location, uploaded_flat_file_name, uploaded_flat_file_time, flat_file_uniqid, capture_duration, modification_date) 
			SELECT 
				id_connection, 
				hour,
				day,
				flat_file_template,
				'$repertoire_archive' || uploaded_flat_file_name || '.bz2',
				uploaded_flat_file_name,
				uploaded_flat_file_time,
				flat_file_uniqid,
				capture_duration,
				modification_date
			FROM 
			sys_flat_file_uploaded_list t0 
			WHERE 
				hour IS NOT NULL 
				AND flat_file_uniqid NOT IN 
				(
					 SELECT 
					 flat_file_uniqid 
					 FROM 
					 sys_flat_file_uploaded_list_archive t1 
					 WHERE
					 t0.hour = t1.hour AND
					 t0.day = t1.day AND
					 t0.flat_file_template = t1.flat_file_template
				)";
		$this->database->executeQuery($query);
		displayInDemon(__METHOD__." : ".$this->database->getAffectedRows()." references de fichiers ajoutees dans sys_flat_file_uploaded_list_archive");
	}
    
    /**
     * retourne un tableau contenant tous les id de $network_level désactivés en topo (eor_on_off=0 dans la table edw_object_ref)
     **/
    public function getDeactivatedNe($network_level)
    {
        $ids_off=array();
        $query="select eor_id from edw_object_ref where eor_on_off=0 and eor_obj_type='$network_level'";
        $result = $this->database->execute($query);
        while($values = $this->database->getQueryResults($result,1)) 
            $ids_off[]=$values["eor_id"];
        return $ids_off;
    }
	
	/**
	 * 
	 * Active les fichiers sources dont au moins un compteur est activé dans le contexte, désactive les autres
	 * @param string $before motif de reg exp en début du nms_table à chercher dans le flat_file_name
	 * @param string $after motif de reg exp en fin du nms_table à chercher dans le flat_file_name
	 * @return array Tableau des clauses à appliquer sur le champ flat_file_name de la table sys_definition_flat_file_lib lors de la réactivation des fichiers sources
	 */
	public function activateSourceFileByCounter($before=".*",$after=".*") {
		//liste de tous les types de fichiers à desactiver
		$tabSqlLike_deactivate = array();
		
		//on liste tous les types de fichiers spécifiés dans le contexte avec de compteurs activé ou pas (nms_table de sys_field_reference)
		$query = "SELECT nms_table 
					FROM sys_field_reference c, sys_definition_categorie f 
					WHERE  nms_field_name NOT ILIKE 'capture_duration%' AND rank = id_group_table AND nms_table!='0' AND nms_table IS NOT NULL
					GROUP BY family, nms_table 
					ORDER BY family, nms_table";
		
		$result = $this->database->execute($query);
		while($values = $this->database->getQueryResults($result,1)) {
			$nms_table 	= $values["nms_table"];
			//on prépare la requête de sélection des fichiers à activer
			if($values['nms_table'] != "") {
				$tabSqlLike_deactivate[] = "flat_file_name ~ E'".$before.$values['nms_table'].$after."'";
			}
		}
		
		if(!empty($tabSqlLike_deactivate)) {
			//on désactive tous les types de fichiers connu dans le contexte (nms_table de sys_field_reference)
			$sql_update = "UPDATE sys_definition_flat_file_lib SET on_off = 0"
				." WHERE (".implode(" OR ", $tabSqlLike_deactivate).");";
			$this->database->execute($sql_update);
			displayInDemon("requete de desactivation des fichiers sources : <br>{$sql_update}\n");
			
		}
		
		
		// *****************************
		//On liste les compteurs actifs (on_off = 1) et on active que les fichiers sources contenant ces compteurs.
		// *****************************
		$tabSqlLike_activate = array();
		//on liste des fichiers à activer en fonction des familles (Il faut au moin un compteur actif par type de fichier)
		$query = "	SELECT family, nms_table 
					FROM sys_field_reference c, sys_definition_categorie f 
					WHERE c.on_off = 1 AND nms_field_name NOT ILIKE 'capture_duration%' AND rank = id_group_table 
					GROUP BY family, nms_table 
					ORDER BY family, nms_table";
		$result = $this->database->execute($query);

		while($values = $this->database->getQueryResults($result,1)) {
			$nms_table 	= $values["nms_table"];
			//on prépare la requête de sélection des fichiers à activer
			if($values['nms_table'] != "") {
				$tabSqlLike_activate[] = "flat_file_name ~ E'".$before.$values['nms_table'].$after."'";
			}
		}
		
			//on active les fichiers sources qui possèdent des compteurs actifs.
		if(!empty($tabSqlLike_activate)) {
			// Bug #15396 => on laisse les fichiers out inchangés par rapport au contexte
			$sql_update = "UPDATE sys_definition_flat_file_lib SET on_off = 1"
				." WHERE (".implode(" OR ", $tabSqlLike_activate).");";
//				." AND flat_file_naming_template NOT LIKE '%.out%';";
			displayInDemon("requete d'activation des fichiers sources : <br>{$sql_update}\n");
			$this->database->execute($sql_update);
		}else {
			echo "Warning : aucun fichier source ne sera active lors du prochain retrieve<br>";
		}
	}
	
    /**
     * Fonctions qui va afficher les données d' une table, elle permet aussi d'afficher une chaine requete passé en argument
     * 
     * @param string $table,  table à afficher
     * @param string $requete, requete à afficher(optionnel)
     */
    public function displayDataTable($table, $requete=false)
    {
        //affiche requete si elle est présente
        if( $requete !== false )	displayInDemon($requete."<br>\n");

        $query  = "SELECT * FROM {$table};";
        //echo "query = {$query}<br>";
        $result = $this->database->execute($query);
        $entete = true;

        unset($values);
        unset($html);

        $values = $this->database->getQueryResults($result);
        //var_dump($values);
        if( is_array($values) )
        {
            $html = "<table cellspacing='1' cellpadding='2' border='1'>";

            foreach($values as $tabSql)
            {
                if($entete === true)
                {
                    $nbColonne = count($tabSql);
                    //echo $table;
                    $html .= "<tr><th colspan='{$nbColonne}'>{$table}</th></tr>";
                    $html .= "<tr>";
                    foreach($tabSql as $cle => $value)
                    {
                        $html .= "<th>{$cle}</th>";
                    }
                    $html .= "</tr>";
                    $entete = false;
                }

                $html .= "<tr align='right'>";
                foreach($tabSql as $value)
                {
                    $html .= "<td>{$value}</td>";
                }
                $html .= "</tr>";
            }
            $html .= "</table>";
        }

        displayInDemon($html."<br>\n");
    }
    
    /**
     * 
     * Méthode qui permet de collecter (virtuellement) un fichier pour une heure donnée. elle est à utiliser dans le cas des fichiers sources à intégrer pour plusieurs heures
     * @param $hour
     * @param $flatFile
     */
	public function insertInSysList($hour,FlatFile $flatFile ){
		//suppression de fichiers dupliqués dans sys_flat_file_uploaded_list (2 collectes avec reprise de données) bug 27889
		$query = "SELECT flat_file_location FROM sys_flat_file_uploaded_list
			WHERE flat_file_uniqid = '{$flatFile->uploaded_flat_file_name}' AND hour=$hour";
		
		$res = $this->database->executeQuery($query);
		while($values = $this->database->getQueryResults($res, 1))
			$files[] = $values['flat_file_location'];
		if(is_array($files))
		foreach($files AS $file)
		{
			if(file_exists($file))
				unlink($file);
			$query_clean = "
				DELETE FROM 
				sys_flat_file_uploaded_list 
				WHERE 
				flat_file_location = '" . $file . "'";
			$result_clean = $this->database->executeQuery($query_clean);
		}
		//----
		
		
		
		
		$day = substr($hour, 0, 8);
				
		$new_flat_file_location=str_replace(".txt", "_$hour.txt", $flatFile->flat_file_location);
		copy($flatFile->flat_file_location, $new_flat_file_location);
		
		$query = "
			INSERT INTO 
				sys_flat_file_uploaded_list 
					( hour, day, flat_file_template, flat_file_location, uploaded_flat_file_name,flat_file_uniqid, uploaded_flat_file_time, capture_duration) 
			VALUES 
					( $hour, $day, '{$flatFile->flat_file_template}', '$new_flat_file_location', '{$flatFile->uploaded_flat_file_name}','{$flatFile->uploaded_flat_file_name}', '$flatFile->hourOfUpload', 3600)";
		
		$this->executeSqlWithError($query);
	}
	
	/**
	 * 
	 * Méthode utilisée dans le cas de collecte multi timezone, permet de completeer les lignes correspondants aux collectes virtuelles.
	 * Ne fait rien dans le cas standard.
	 */
	public function completeDuplicatedFiles(){
		$query = "SELECT flat_file_location, id_connection, uploaded_flat_file_name FROM sys_flat_file_uploaded_list WHERE id_connection IS NULL;";
		$result = $this->database->executeQuery($query);
		$values = $this->database->getQueryResults($result);
		if(count($values)!=0){
	
			foreach($values as $tabSql){
				$flat_file_location=$tabSql['flat_file_location'];
				$uploaded_flat_file_name=$tabSql['uploaded_flat_file_name'];
					
				//récupérer le flat_file_location du fichier référence, seul ce dernier contient un id unique du fichier de réf
				$separator=strrpos($flat_file_location,"_");
				$flat_file_location_ref=substr($flat_file_location,0,$separator);
				$flat_file_location_ref_full=$flat_file_location_ref.".txt";
					
				$condition_refLine="flat_file_location = '$flat_file_location_ref_full' and id_connection is not NULL";
				$condition_incompleteLine="flat_file_location ~ '^$flat_file_location_ref.*' AND id_connection IS NULL";
	
				$query_ref="SELECT id_connection, modification_date, uploaded_flat_file_name FROM sys_flat_file_uploaded_list WHERE $condition_refLine;";
				$result_ref = $this->database->executeQuery($query_ref);
				$values_ref = $this->database->getQueryResults($result_ref,1);
					
				if(count($values_ref)!=0){
				//complète les champs vides
					$query_maj="UPDATE sys_flat_file_uploaded_list SET
								id_connection=".$values_ref['id_connection'].",
								modification_date='".$values_ref['modification_date']."',
								uploaded_flat_file_name='".$values_ref['uploaded_flat_file_name']."'
								WHERE $condition_incompleteLine;";
	
					$this->executeSqlWithError($query_maj);
				}
				else{
				//log en cas d'erreur si fichier de réf non trouvé
					displayInDemon("<span style='color:red'>Impossible de recuperer le fichier de reference multitimezone pour le fichier flat_file_location : $flat_file_location , uploaded_flat_file_name : $uploaded_flat_file_name</span>\n", 'alert');
				}
			}
		}
	}
	
	/**
	 * 
	 * Creer la liste des éléments réseaux désactivés
	 * @param ParametersList $params
	 */
	public function deactivatedNEPerFamily(ParametersList $params){
 
		$query_deactivatedNE="select eor_obj_type, eor_id from edw_object_ref where eor_on_off=0;";
		echo $query_deactivatedNE;
		$result = $this->database->executeQuery($query_deactivatedNE);
		$values = $this->database->getQueryResults($result);
		$deactivatedNE_list=array();
		
		if(count($values)!=0){
			displayInDemon("Certains elements reseaux sont desactives");
			//liste des éléments réseaux desactivés
			foreach($values as $tabSql){
				$deactivatedNE_list[strtolower($tabSql['eor_obj_type'])][]=$tabSql['eor_id'];
			}
			
			//mise à jour des objets parameter
			foreach ($params as $param) {
				if(is_array($deactivatedNE_list[$param->network[0]]))
					$param->deactivated_NE=$deactivatedNE_list[$param->network[0]];
			}
		}

	}
	

	/**
	 * 
	 * Retourne le tableau des types de fichiers activés pour lesquels 
	 * au moins 1 fichier vient d'être collecté.
	 * @param $fileType
	 */
	public function getFlatfilenamesForCollectedFiles(FileTypeCondition $fileType=NULL) {
		if($fileType!=NULL) $condition="AND {$fileType->getDBCondition()}";
		$query = "SELECT distinct flat_file_name
			FROM sys_flat_file_uploaded_list, sys_definition_flat_file_lib
			WHERE flat_file_template = flat_file_naming_template AND on_off = 1 $condition ;";				
		$result = $this->database->executeQuery($query);
		//en cas d'erreur
		$erreur = $this->database->getLastError();
		if($erreur != ''){
			displayInDemon("getFlatfilenamesForCollectedFiles:Error:$erreur",'alert');
			return array();
		}
		$tabFlatfileNames=array();
		while($row = $this->database->getQueryResults($result,1)) {
			$tabFlatfileNames[] = $row["flat_file_name"];
		}
		return $tabFlatfileNames;
	}
	
	
	/**
	 * 
	 * Renvoie la liste des élément réseaux collectés
	 * @param $netElemPattern
	 * @param $fileType
	 */
	public function findNetElemCollected($netElemPattern,FileTypeCondition $fileType=NULL) {
		if($fileType!=NULL) $condition="AND {$fileType->getDBCondition()}";
		$tabNE= array();
		$query = "SELECT uploaded_flat_file_name
			FROM sys_flat_file_uploaded_list, sys_definition_flat_file_lib
			WHERE flat_file_template = flat_file_naming_template AND on_off = 1 $condition ;";
		$result = $this->database->executeQuery($query);
		//en cas d'erreur
		$erreur = $this->database->getLastError();
		if($erreur != ''){
			displayInDemon("findNetElemCollected:Error:$erreur",'alert');
			return array();
		}
		
		while($row = $this->database->getQueryResults($result,1)) {
			// Si plusieurs parties du uploaded_flat_file_name matche avec le $netElemPattern  => warning
			// TODO : test non concluant avec preg_match_all à revoir (MDE)
			if(preg_match("/$netElemPattern/", $row["uploaded_flat_file_name"],$match)) {
				if(count($match)!=2) displayInDemon("Warning:l'expression reguliere '$netElemPattern' match plus d'une fois sur {$row["uploaded_flat_file_name"]}; affinez la plus");
				$tabNE[$match[1]] = 1;
			}else{
				displayInDemon("Warning:l'expression reguliere '$netElemPattern' ne match pas avec {$row["uploaded_flat_file_name"]}");
			}
		}
		return array_keys($tabNE);
	}

    
}

?>