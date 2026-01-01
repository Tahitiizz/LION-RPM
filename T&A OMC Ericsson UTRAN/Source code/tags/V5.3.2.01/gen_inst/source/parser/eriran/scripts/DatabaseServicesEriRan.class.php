<?php
Class DatabaseServicesEriRan extends DatabaseServices{
	public function DatabaseServicesEriRan( $dbConnection){
		parent::__construct( $dbConnection);
	}

	/**
	 * Activation/Désactivation de la collecte pour les fichier nodeb
	 * 	 
	 * */

	public function activateSourceFileByCounterEricssonUtran() {
		$query = "SELECT nms_table, edw_field_name
					FROM sys_field_reference 
					WHERE on_off = 1 AND nms_field_name NOT ILIKE 'capture_duration%' AND edw_group_table = 'edw_eriran_nodeb_axe1'";
		$result = $this->database->execute($query);
		
		$values = $this->database->getQueryResults($result);
		
		if(count($values) > 0){
			$sql_update = "UPDATE sys_definition_flat_file_lib SET on_off = 1 where flat_file_name = 'Node-B'";
		}else{
			$sql_update = "UPDATE sys_definition_flat_file_lib SET on_off = 0 where flat_file_name = 'Node-B'";
		}
		$this->database->execute($sql_update);
		
	    //
		
	}

    public function activateGlobalCounters() {
        $this->database->execute("update sys_field_reference set on_off=1, new_field=1 where on_off=0 and edw_target_field_name in (select substring(edw_target_field_name,'([[:alnum:]]*)_[0-9]*$') from sys_field_reference where on_off=1)");
    }
	
		/**
	 * 
	 * Copie les valeurs des fichiers sources vers les tables temporaires
	 */
	public function copy_into_temp_tables($params) {
		$query_ri = Parser::$query_ri;
		$aggreg_net_ri = Parser::$aggreg_net_ri;
		$time_coeff = Parser::$time_coef;
		$time_expected_ri = Parser::$capture_duration_expected;
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
						if (Tools::$debug){
							//afficher le nombre de ligne copiées
							//Le log ci-dessous est KO (Cf. 26972)
							//$nb_ligne = $this->database->getAffectedRows();
							$nb_ligne=count(file($flat_file_location));
							displayInDemon("<span style='color:green;'> - {$nb_ligne} lignes inserees pour l'entite {$todo} et le niveau {$network}</span>\n");
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
						//affichage de la table temp en mode debug dans le file_demon
						if (Tools::$debug)	$this->displayDataTable($this->table_name[$todo][$network], $query);
						
						$this->inserts[$param->specific_field][] = $this->table_name[$todo][$network];
					}
					else {
						displayInDemon("<span style='color:red;'>Entité[{$todo}] : Erreur dans la requete de COPY</span>\n");
					}
					
					$cumulatedCounters=$this->getCumulatedCounters($param,$todo);

							//pour les compteurs cumulés de ce todo
					if(count($cumulatedCounters)!=0){
						$query = "select hour, $network, ".implode(", ", $cumulatedCounters)." FROM {$this->table_name[$todo][$network]} ;";
						$result = $this->database->executeQuery($query);
						$values = $this->database->getQueryResults($result);
						if(count($values)!=0){
							foreach($values as $tabSql){//pour chaque element réseau
								foreach ($cumulatedCounters as $counter_delta => $counter_cumulated){//pour chaque compteur cumulé
									$networkElt=$tabSql[$network];
									$hour=$tabSql['hour'];
									// valeur du compteur cumulé pour l'heure précédente
									$previous_value=$this->getCounterValues($param,$todo,$counter_cumulated,$this->previousHour($hour),$networkElt);
									//si la valeur n'est pas nulle/vide
									if($previous_value!=""){
										$delta_value=$tabSql[strtolower($counter_cumulated)]-$previous_value;
										//si reset a eu lieu on garde la valeur du cpt cumulé
										if($delta_value<0) $delta_value=$tabSql[strtolower($counter_cumulated)];
										$query= "UPDATE {$this->table_name[$todo][$network]} SET $counter_delta = $delta_value Where $network='$networkElt';";
										//echo "$query \n";
										$resultat = $this->database->execute($query);
									}else{// si la valeur est null/vide on laisse le compteur delta à NULL
										$query= "UPDATE {$this->table_name[$todo][$network]} SET $counter_delta = NULL Where $network='$networkElt';";
										//echo "$query \n";
										$resultat = $this->database->execute($query);
									}
								}
							
							}
						}
						
                    }
                   	
				}
				else {
					displayInDemon("Entité[{$todo}] : Le fichier pour le COPY n'est pas présent\n");
					$this->w_astellia_tables[$param->specific_field][]=$this->table_name[$todo][$network];
				} 
			}

		}
		$this->previous_counter_values=array();
	}
	
	function getCumulatedCounters($param,$todo){
		$cumulatedCounters=array();
		foreach ($param->todo[$todo] as $counter) {
			
		   //detecter les compteur delta
            if (preg_match("/(.*)(_[0-9]{1,})*_DELTA(_[0-9]{1,})*$/i", $counter->edw_field_name,$matches)){
            	    $query_counter_activated="select edw_field_name, new_field from sys_field_reference where edw_target_field_name ilike '{$matches[1]}' and on_off=1 and new_field=0";
        			$result = $this->database->executeQuery($query_counter_activated);
					$values = $this->database->getQueryResults($result);
					//compteur cumulé activé?? sinon => specific parser
					if(count($values)!=0)
						$cumulatedCounters[$counter->edw_field_name]=$matches[1];
			}
		}
		return $cumulatedCounters;
	}
	
	
	/**
    * Renvoie la valeur du compteur pour une heure et un élément réseau donné
    * @param unknown_type $entity
    * @param unknown_type $counter
    * @param unknown_type $hour
    * @param unknown_type $networkElt
    */
   function getCounterValues($param,$todo, $counter, $hour,$networkElt){
		
		$counter=strtolower($counter);
		//echo "debug => entity $todo $todo $counter $networkElt\n";
		if(!isset($this->previous_counter_values[$todo][$counter])){
		$id_group_table=$param->id_group_table;
		$family="";
		switch ($id_group_table) {
			case "1":
				$family=array("cellb","cell");break;
			case "2":
				$family=array("iubl","iubl");break;
			case "3":
				$family=array("iurl","iurl");break;
			case "4":
				$family=array("adj","stc");break;
			case "5":
				$family=array("lac","lac");break;
			case "6":
				$family=array("rac","rac");break;
			case "7":
				$family=array("nodeb","sse");break;
			case "8":
				$family=array("rnc","rncsse");break;

		}
		if($family=="") return;

		$edw_table="edw_eriran_{$family[0]}_axe1_raw_{$family[1]}_hour";
		$w_edw_table="w_edw_eriran_{$family[0]}_axe1_raw_{$family[1]}_hour_$hour";
		/*$w_astellia_table=$this->table_name[$todo][$param->network[0]];
		$w_astellia_table= preg_replace('/(w_astellia_.+)([0-9]{10})$/','${1}'."$hour",$w_astellia_table);
		*/
		$w_astellia_table=strtolower("w_astellia_{$todo}_{$family[1]}_{$hour}");
		//recupération de la colonne du compteur
		//cas 1: l'heure est déjà computer
		$query = "select {$family[1]}, {$counter} from $edw_table where hour='$hour';";
		$result = $this->database->executeQuery($query);
		$values = $this->database->getQueryResults($result);
		if(count($values)!=0){
			foreach($values as $tabSql){			
				$this->previous_counter_values[$todo][$counter][$tabSql[$family[1]]]=$tabSql[$counter];
			}
			return $this->previous_counter_values[$todo][$counter][$networkElt];
		}
		
		
		// cas 2, l'heure est déjà Retreiver
		$query = "select * from sys_to_compute where hour = $hour;";
		$result = $this->database->executeQuery($query);
		$values = $this->database->getQueryResults($result);
		if(count($values)!=0){
			$query = "select {$family[1]}, {$counter} from $w_edw_table where hour='$hour';";
			$result = $this->database->executeQuery($query);
			$values = $this->database->getQueryResults($result);
			
			if(count($values)!=0){
				foreach($values as $tabSql){			
					$this->previous_counter_values[$todo][$counter][$tabSql[$family[1]]]=$tabSql[$counter];
				}
				return $this->previous_counter_values[$todo][$counter][$networkElt];
			}
		}
		// cas 3, l'heure est dans le meme Retreive
		
		
		$query = "select * from sys_w_tables_list where hour=$hour";
		$result = $this->database->executeQuery($query);
		$values = $this->database->getQueryResults($result);
		if(count($values)!=0){
			$query = "select {$family[1]}, {$counter} from $w_astellia_table where hour='$hour';";
			$result = $this->database->executeQuery($query);
			$values = $this->database->getQueryResults($result);
			if(count($values)!=0){
				foreach($values as $tabSql){			
					$this->previous_counter_values[$todo][$counter][$tabSql[$family[1]]]=$tabSql[$counter];
				}
				return $this->previous_counter_values[$todo][$counter][$networkElt];
			}
		}

	//si le compteur avait déjà été recupéré
	}else{
		return $this->previous_counter_values[$todo][$counter][$networkElt];
	}
	
	// cas 4
	return ;
		
		
	}
	
	
	// prend une heure en argument et renvoi l'heure précédente
	function previousHour($hour){
		$year = substr($hour, 0, 4);
		$month = substr($hour, 4, 2);
		$day = substr($hour, 6, 2);
		$onlyHour = substr($hour, 8, 2);
		$timeStamp = mktime($onlyHour, 0, 0, $month, $day, $year);
		$previousHour = date('YmdH', $timeStamp - 3600); 
		return $previousHour;
	}
	
}
?>
