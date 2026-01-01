<?php
Class DatabaseServicesEriBss extends DatabaseServices{
	public function DatabaseServicesEriBss( $dbConnection){
		parent::__construct( $dbConnection);
	}
	

	

	/**
	 * MISE A JOUR DES EXPRESSIONS REGULIERES DE sys_definition_flat_file_lib
	 * 	 
	 * */
	public function updateFlatFileLibNamingTemplate()
	{
		displayInDemon("<br><span style='color:blue;'>MISE A JOUR DES EXPRESSIONS REGULIERES DE sys_definition_flat_file_lib A PARTIR DES PARAMETRES DE sys_global_parameters</span><br>\n");
		//elements réseaux nouveaux
		$recentlyAddedNE=array();
		
			
		$parameter="specif_eribss_expreg_bsc";
		$flat_file_name="ASN1";
		
		$system_name = get_sys_global_parameters("system_name");
		
		//recupération de la liste des BSC
		$ne_list=strtoupper(get_sys_global_parameters($parameter));
		// comparaison avec
		$query = "select flat_file_naming_template from sys_definition_flat_file_lib where flat_file_name='$flat_file_name';";
		$result = $this->database->execute($query);
		$values = $this->database->getQueryResults($result);
		$current_template=$values[0]["flat_file_naming_template"];
		$exploded_current_template=explode("_",$current_template);
		$current_ne_list=trim($exploded_current_template[1],"()");
		$templateBase=$exploded_current_template[0];
		//correction bug
		$templateBase=str_replace("[A-Z]", "C", $templateBase);
		
		$new_template="{$templateBase}_($ne_list)";
			
			
		if($new_template!=$current_template){
			//S'il y a changement de valeur de parametre
			//est ce que le nouveau parametre est valide?
			if((preg_match("/^([[:alnum:]]|[|])+$/", $ne_list))||($ne_list=="")){

						//mise a jour des tables-------------------------
				$query = "UPDATE sys_definition_flat_file_lib SET flat_file_naming_template = '$new_template' WHERE flat_file_naming_template = '$current_template';";
				$result = $this->database->execute($query);
				echo $query."\n";
			
				$query = "UPDATE sys_flat_file_uploaded_list_archive SET flat_file_template = '$new_template' WHERE flat_file_template='$current_template';";
				$result = $this->database->execute($query);
				echo $query."\n";
			
				$query = "UPDATE sys_flat_file_uploaded_list SET flat_file_template = '$new_template' WHERE flat_file_template='$current_template';";
				$result = $this->database->execute($query);
				echo $query."\n";
				
				//affichage dans trace logs
	            if($ne_list=="") $ne_list="default";
	            if($current_ne_list=="") $current_ne_list="default";
            
				if(($current_ne_list!="default")&&($current_ne_list!=$ne_list)){ 
					$message = "Global Parameter List of BSC to collect has been successfully modified from {$current_ne_list} to {$ne_list} since last Collect";
					sys_log_ast("Warning", $system_name, "Data Collect", $message, "support_1", "");
					
					//quels sont les noeuds ajoutés
					$recentlyAddedNE_temp=array();
					$recentlyAddedNE_temp=array_diff(explode("|", $ne_list), explode("|", $current_ne_list));
					$recentlyAddedNE=array_merge($recentlyAddedNE_temp,$recentlyAddedNE);
				}

			 }else{ //nouveau parametre est invalide
			 	$message = "Global Parameter List of BSC to collect\  value invalid ({$ne_list}).Characters allowed: alphanumeric and pipe (|).Last valid value will be used";
			 	sys_log_ast("Critical", $system_name, "Data Collect", $message, "support_1", "");

			 }

		}else{
			$message = "Global Parameter List of BSC to collect has not been modified since last Collect";
			displayInDemon($message,'normal');
		}
		
		if(count($recentlyAddedNE)!=0){
			$query="update sys_global_parameters set value='".implode("|", $recentlyAddedNE)."' where parameters='specif_recentlyAddedNE'";
			$result = $this->database->execute($query);
			echo $query."\n";
		}

	}

	
	
	function clean_asn1_file_uploaded(){
			$query = "
			SELECT 
			flat_file_location 
			FROM 
			sys_flat_file_uploaded_list 
			WHERE 
			flat_file_template like 'C[0-9]{8}.[0-9]{4}[-][0-9]{8}.[0-9]{4}_%'";
		$res = $this->database->executeQuery($query);
		while($values = $this->database->getQueryResults($res, 1))
			$files[] = $values['flat_file_location'];
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
	 * 
	 * Enter description here ...
	 */
	function keepFilesOftheLast2HourCollected($new_ne_list){
		$query = "
			SELECT 
			distinct hour 
			FROM 
		sys_flat_file_uploaded_list order by hour desc";
		$result = $this->database->executeQuery($query);
		$count=0;
		$savedHours=array();
		while($values = $this->database->getQueryResults($result,1)) {
			$count++;
			$hour=$values['hour'];
			if($count<3) {
				//on ne suprimme pas les 2 derniers heures
				$savedHours[]=$hour;
				continue;
			}
			
			//est que l'heure a déjà été traité??
			$query_Exist="select hour from edw_eribss_bss_axe1_kpi_bsc_hour where hour=$hour limit 1";
			$res = $this->database->executeQuery($query_Exist);
			$value=$this->database->getQueryResults($res);
			
			if(count($value)!=0){
				//heure déjà retreivé
				break;
				
			}else{
				//heure non encore retreivé
				$savedHours[]=$hour;
			}
		}
		if(count($savedHours)>0){
				$query_clean = "
				DELETE FROM 
				sys_flat_file_uploaded_list 
				WHERE 
				hour not in (". implode(",",$savedHours).")";
			$this->database->executeQuery($query_clean);
			
			//message à tracer
	
			$message="Following nodes have been added:".  $new_ne_list.". To limit data reprocessing, only ". implode(",",$savedHours) ." will be integrated";
			sys_log_ast("Info", "Trending&Aggregation", "Data Collect", $message, "support_1", "");
			displayInDemon($message);	
		}

	}
	
	public function emptyRecentlyAddedNEParameter() {
		// MAJ du parametre global pour stocker la date de la mise à jour de la topo
		$query="update sys_global_parameters set value=NULL where parameters='specif_recentlyAddedNE'";
		echo "$query\n";
		return $this->executeSqlWithError($query);
		
	}
	
	public function setGlobalParameterValue($parameter, $value){
		 $query ="UPDATE sys_global_parameters SET value = '$value' where parameters = '$parameter';";
         $result = $this->database->execute($query);
	}
	
	public function getHoursOfAsn1FilesCollected() {
		///ERIBSS ASN.1 initialisation parametre  'specif_eribss_expreg_bsc' ou pas.
		$query = "SELECT DISTINCT hour FROM sys_flat_file_uploaded_list WHERE hour IS NOT NULL AND flat_file_template like 'C[0-9]{8}.[0-9]{4}[-][0-9]{8}.[0-9]{4}%';";
		$result = $this->database->execute($query);
		$nombre_hour = $this->database->getNumRows();
		return $nombre_hour;
	}
	
	/**
	 * 
	 * Copie les valeurs des fichiers sources vers les tables temporaires
	 */
	public function copy_into_temp_tables($params) {

		foreach ($this->copy_files AS $todo => $array_level) {
			$param = $params->getParamWithTodo($todo);
			if (!isset($param)) {
				// message d'erreur
				return null;
			}
			foreach ($array_level AS $network => $flat_file_location) {
				if(file_exists($flat_file_location)) {
					$aggregation_formula_list = array();
					$field = array();
					foreach ($param->todo[$todo] as $counter) {
						$aggregation_formula_list[$counter->edw_field_name] = $counter->aggregation_formula;
						//V0.9 supprimer isset($counter->nms_field_name_in_file) and 
						if ($counter->on_off == 1) {
							$field[] = $counter->edw_field_name;
						}
					}
					//REQUETE DE COPY
					$query_copy = "COPY {$this->table_name_NA[$todo][$network]} (min, {$param->specific_field} ,".implode(", ", $field).") FROM '{$flat_file_location}'  with delimiter ';' NULL AS ''";
//					$res = $this->executeSqlWithError($query_copy);
					if ($this->executeSqlWithError($query_copy)) {
						
						//afficher le nombre de ligne copiées
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
						//affichage de la table NA en mode debug dans le file_demon
						if (Tools::$debug)	$this->displayDataTable($this->table_name_NA[$todo][$network], $query_copy);	
							
						//REQUETE AGGREGATION RESEAU
						$aggreg_net_ri = 1;
						$query = "
							INSERT INTO {$this->table_name_TA[$todo][$network]} 	(min, {$param->specific_field}, ".implode(", ", array_keys($aggregation_formula_list)). ") 
							SELECT 		min, {$param->specific_field}, ".implode(", ", $aggregation_formula_list) . " 
							FROM   		{$this->table_name_NA[$todo][$network]}
						GROUP BY 	min, {$param->specific_field}";
						eval("\$query = \"$query\";");
						$this->executeSqlWithError($query);
						
						//affichage de la table TA en mode debug dans le file_demon
						if (Tools::$debug)	$this->displayDataTable($this->table_name_TA[$todo][$network], $query);
							
						//REQUETE AGGREGATION TEMPORELLE
						$aggreg_net_ri = 0;
						$query = "
							INSERT INTO {$this->table_name[$todo][$network]}	({$param->specific_field}, ".implode(", ", array_keys($aggregation_formula_list)).") 
							SELECT 		{$param->specific_field}, ".implode(", ", $aggregation_formula_list)." 
							FROM 		{$this->table_name_TA[$todo][$network]}
						GROUP BY 	{$param->specific_field}";
						eval("\$query = \"$query\";");			
						$this->executeSqlWithError($query);
						
						
						// specifique Ericsson BSS : on suprime les target cells n'ayant pas de BSC
						$deleteQuery="delete from {$this->table_name[$todo][$network]} where cell not in (select eoar_id from edw_object_arc_ref where eoar_arc_type ='cell|s|bsc');";
						if($param->id_group_table==1)
							$this->executeSqlWithError($deleteQuery);
						
						
						//affichage de la table temp en mode debug dans le file_demon
						if (Tools::$debug)	$this->displayDataTable($this->table_name[$todo][$network], $query);
						
						$this->inserts[$param->specific_field][] = $this->table_name[$todo][$network];
					}
					else {
						displayInDemon("<span style='color:red;'>Entité[{$todo}] : Erreur dans la requete de COPY</span>\n");
					}
				}
				else {
					displayInDemon("Entité[{$todo}] : Le fichier pour le COPY n'est pas présent\n");
					$this->w_astellia_tables[$param->specific_field][]=$this->table_name[$todo][$network];
				} 
			}
		}
	}
	
	


}
?>