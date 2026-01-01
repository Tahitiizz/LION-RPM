<?php

/**
 * Dans le cadre de la step "create_temp_table", cette classe regroupe les méthodes 
 * du CB redéfinies côté parser.
 */
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/create_temp_table_generic.class.php");

abstract class CreateTempTableCB extends create_temp_table {
	
	
	/**
	*
	* Flag permettant de demander du mono processus
	* (débrayage du parallélisme)
	*/
	protected $singleProcess;
			
	/**
	 * 
	 * Table temporaire à traiter
	 */
	protected $tempTableCondition;
	
	/**
	 * Constructeur.
	 * Le constructeur parent fait appel aux fonctions génériques du Composant de Base.
	 */
	public function __construct($tempTableCondition,$single_process_mode=TRUE)
	{
		$this->tempTableCondition=$tempTableCondition;
				
		// Connexion à la base de données locale
		$this->database = new DatabaseConnection();
		
		//single_process_mode suivant l'instanciation par la fonction execute
		if(!isset($single_process_mode)){
			$this->singleProcess=TRUE;
		}
		else{
			$this->singleProcess=$single_process_mode;	
		}
		
		// l'appel au constructeur parent execute la méthode parent updateObjectRef
		// (même si la méthode enfant existe), et donc provoque l'execution des 
		// étapes 3 à 5 pour chaque processus, ce qu'on souhaite éviter pour de 
		// meilleures performances. A la place, on crée les méthodez "stepOneAndTwo"
		// et "stepThreeToFive".
		//parent::__construct();	
		// => c'était dû au paramètre "singleProcess" manquant dans le constructeur de "create_temp_table_omc"
        //$this->init();
        
		parent::__construct();		
	}
	
	/**
	 * Surcharge de la méthode "init" du CB, visant à éviter les TRUNCATE dans les tables 
	 * temporaires de topo.
	 */
    function init()
    {    	
    	// test multi_process mode
       	// => pas de "if($this->singleProcess){" pour éviter de purger les tables 
       	// temporaires de topo : notre dernier objet CreateTemptable, créé en fin 
       	// de « execute() » a besoin de ces tables (il est créé en mode mono process 
       	// afin que "updateObjectRef" fasse bien les étapes 3 à 5.
       		
		global $database_connection;
		
		// surcharge du CB (Cf. méthode complémentaire : "initTempTopoTables")
		// pg_query($database_connection, "TRUNCATE edw_object;");
		// pg_query($database_connection, "TRUNCATE edw_object_arc;");
		
		$global_parameters = edw_LoadParams();
		$this->sep_axe3 = $global_parameters["sep_axe3"];
		// 07/01/2010 NSE bz 13658 : suppression du paramètre id_country non utilisé 
        $this->system_name = $global_parameters["system_name"];
        $this->compute_mode = $global_parameters["compute_mode"];
		$this->net_min_main_family = get_network_aggregation_min_from_family( get_main_family() );
    }
		
    /**
     * Purge des tables temporaires de topo.
     * Dans le CB, cette purge à lieu dans la méthode "init".
     */
    protected static function initTempTopoTables() {
  			global $database_connection;
  			pg_query($database_connection, "TRUNCATE edw_object;");
  			pg_query($database_connection, "TRUNCATE edw_object_arc;");
    }

	
	/**
	 * On redéfinit ici une méthode du CB (Cf. class\create_temp_table_generic.class.php).
	 * Cette méthode retourne la liste des groupes de tables pour un niveau réseau minimum donné.
	 * @see class/create_temp_table::get_group_table_from_w_table_list()
	 */
    function get_group_table_from_w_table_list()
    {
        //test multi_process mode
       	if($this->singleProcess){
        	return parent::get_group_table_from_w_table_list();
        }
        global $database_connection;

        $query = "SELECT distinct group_table FROM sys_w_tables_list";
        if(isset($this->tempTableCondition)){
        	$query .= " WHERE {$this->tempTableCondition->getDBGroupTableCondition()}";
        }
        $res = pg_query($database_connection, $query);
        $nombre_resultat = pg_num_rows($res);
        if ($nombre_resultat > 0) {
        	for ($i = 0;$i < $nombre_resultat;$i++) {
        		$row = pg_fetch_array($res);
        		$groups[$i] = $row[0];
        	}
        	//__debug($groups,"groups");
        	return $groups;
        } else {
        	return false;
        }
    }
   

   /**
	* 
	* On redéfinit ici une méthode du CB (Cf. class\create_temp_table_generic.class.php).
	* Fonction qui retourne les heures à traiter pour le group table en cours.
	* @param identifiant du group table
	* @param identifiant du niveau réseau
	* @return array $hours contenant la liste des différentes heures à traiter
	*/
    function get_hours($group_table_param, $level)
    {
    	//test multi_process mode
        if($this->singleProcess){
        	return parent::get_hours($group_table_param, $level);
        }
        
    	global $database_connection;

        $query = "SELECT distinct hour FROM sys_w_tables_list WHERE group_table='$group_table_param' and network='$level'";
        if(isset($this->tempTableCondition)){
			$query .= " AND {$this->tempTableCondition->getDBHourCondition()}";
		}
        $res = pg_query($database_connection, $query);
        $nombre_resultat = pg_num_rows($res);
        if ($nombre_resultat > 0) {
            for ($i = 0;$i < $nombre_resultat;$i++) {
                $row = pg_fetch_array($res);
                $hours[$i] = $row[0];
            }
            return $hours;
        } else {
            return false;
        }
    }

   /**
	* On redéfinit ici une méthode du CB (Cf. class\create_temp_table_generic.class.php).
	* Fonction qui récupère pour chaque group table les niveaux d'aggregations venant des fichiers sources
	* à priori on en a 1 seul mais dans le cas des bypass, il se peut qu'on en ait plusieurs
	*
	*/
    function get_network_level_by_group_table_from_w_table_list()
    {
    	//test multi_process mode
        if($this->singleProcess){
        	return parent::get_network_level_by_group_table_from_w_table_list();
        }
        
        global $database_connection;

        // 24/11/2010 NSE bz 19335 : suppression d'un warning si tableau vide
        if(!empty ($this->list_group_table) && is_array($this->list_group_table))
          foreach ($this->list_group_table as $group_table) {
            $query = "SELECT distinct network FROM sys_w_tables_list where group_table='$group_table'";
            if(isset($this->tempTableCondition)){
            	$query .= " AND {$this->tempTableCondition->getDBNetworkMinLevelCondition()}";
            }
            $res = pg_query($database_connection, $query);
            $nombre_resultat = pg_num_rows($res);
            if ($nombre_resultat > 0) {
                for ($i = 0;$i < $nombre_resultat;$i++) {
                    $row = pg_fetch_array($res);
                    $levels[$group_table][$i] = $row[0];
                }

            }
          }
          return $levels;
    } 
    
   /**
    * Fonction qui met à jour la table object_ref à partir de la table object
    * cette fonction fait appel à une fonction specifique au parser à traiter
    * @param $day : jour en cours à traiter
    * @param $id_group_table : identifiant du group table
    *
    */    
    function updateObjectRef($day, $id_group_table)
    {    	
    	//echo "updateObjectRef enfant";
    	//var_dump($this->singleProcess);
    	//test multi_process mode
    	if($this->singleProcess){
    		return parent::updateObjectRef($day, $id_group_table);
    	}
        
    	// pas d'autre code ensuite, pour éviter que les étapes 3 à 5 soient lancées une fois par processus
    	// create_temp_table enfant : à la plave, les traitement sont faits via
    	// la méthode "parentUpdateObjectRef".
    }

    
    /*
     * MHT2 24/10/2012 : surcharge pour éviter accès simultanés
    * Fonction qui mets à jour la table object à partir de la table hour
    *
    * @param $source_table : table horaire du group table
    * @param $id_group_table : identifiant du group table
    * @param  $edw_day : jour à traiter
    *
    * 07/01/2010 NSE bz 13658 : suppression du paramètre id_country non exploité dans la fonction
    *
    */
    
    function copy_temp_table_to_object_table($source_table, $id_group_table, $edw_day)
    {
    	
    	//test multi_process mode
    	if($this->singleProcess){
    		parent::copy_temp_table_to_object_table($source_table, $id_group_table, $edw_day);
    	}
    	else{
	    	global $database_connection;
	    
	    	$table_object_ref = "edw_object_ref";
	    	$table_object = "edw_object";
	    
	    	$table_object_arc = "edw_object_arc";
	    
	    
	    	// prend en compte les agregations network qui sont présentes dans la table source à partir des aggregations deployées
	    	$net_fields = check_columns_in_table($this->net_fields, $source_table);
	    
	    	__debug($net_fields,"net fields");
	    
	    	//---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
	    	print "<font color=red>Etape n°2 : Maj des tables edw_object et edw_object_arc a partir de w_astellia_X</font><br>";
	    	//---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
	    	printdate();
	    	// Insertion des données dans la table edw_object
	    	$sql = "BEGIN WORK;
	    			LOCK TABLE $table_object_arc,$table_object,$source_table IN EXCLUSIVE MODE;
	    			INSERT INTO $table_object (eo_date, eo_id, eo_obj_type) ";
	    
	    	$select = array();
	    	$select_arc = array();
	    	$delete_arc = array();
	    
	    	__debug($net_fields,"net_fields");
	    
	    
	    	foreach($net_fields as $net){
	    
	    		$select[] = "SELECT DISTINCT '$edw_day', $net, '$net'
	    						 FROM $source_table 
	    						 LEFT JOIN ( SELECT eo_id, eo_obj_type FROM $table_object WHERE eo_obj_type = '$net' ) eo
	    						 ON $net = eo_id AND eo_obj_type = '$net'
	    						 WHERE $net is not null and eo_id IS NULL";
	    			
	    		$child = $this->getChild($net,$net_fields);
	    			
	    		//__debug($child,"child");
	    		if( $child !=="" ){
	    			$select_arc[] = "
	    								SELECT DISTINCT $child, $net, '$child|s|$net'
	    								FROM $source_table 
	    								LEFT JOIN ( SELECT eoa_id, eoa_id_parent, eoa_arc_type FROM $table_object_arc WHERE eoa_arc_type = '$child|s|$net' ) eo ON $child = eoa_id 
	    											AND $net = eoa_id_parent 
	    											AND eoa_arc_type = '$child|s|$net'
	    								WHERE $child IS NOT NULL  
	    									AND $net IS NOT NULL
	    									AND eoa_id IS NULL
	    
	    								";
	    
	    			// 15:14 18/09/2009 GHX
	    			// Correction du BZ 11499
	    			$delete_arc[] = "
	    						SELECT DISTINCT $child, '$child|s|$net'
	    						FROM $source_table 
	    						LEFT JOIN  edw_object_arc  ON ($child = eoa_id AND $net != eoa_id_parent)
	    						WHERE $child IS NOT NULL  
	    						AND $net IS NOT NULL
	    						and eoa_arc_type = '$child|s|$net'
	    					";
	    		}
	    			
	    	}
	    
	    	$selection = implode(" UNION ",$select);
	    	$selection_arc = implode(" UNION ",$select_arc);
	    	$deletion_arc = implode(" UNION ",$delete_arc);
	    
	    	$sql.= $selection." RETURNING edw_object.eo_id;";
	    	//__debug($sql,"SQL INSERT INTO edw_object");
	    
	    	if($selection !== ""){
	    		
	    		
	    		$res = pg_query($database_connection, $sql);
	    		$nb_res=pg_affected_rows($res);
	    		$res_error=pg_last_error();
	    		pg_query($database_connection, "COMMIT WORK;");
	    		
	    		if ($res_error != '')
	    		echo $res_error . " on " . $sql . "\n<br>";
	    		else
	    		echo $nb_res . " = " . $sql . "\n<br>";
	    	}
	    
	    	pg_query($database_connection, "VACUUM VERBOSE ANALYZE $table_object");
	    	 
	    
	    	// Mise à jour de la table edw_object_arc
	    	if( $selection_arc!=="" ){
	    		// 15:16 18/09/2009 GHX
	    		// Correctoin du BZ 11499
	    		$sql = "BEGIN WORK;
	    				LOCK TABLE $table_object_arc In EXCLUSIVE MODE;
	    				DELETE FROM $table_object_arc WHERE ROW(eoa_id,eoa_arc_type) IN ($deletion_arc);";
	    		$res_3 = pg_query($database_connection, $sql);
	    		$res_3_error=pg_last_error();
	    		pg_query($database_connection, "COMMIT WORK;");
	    		if ($res_3_error != '') {
	    			echo $res_3_error . " on " . $sql . "\n<br>";
	    		}
	    			
	    		$sql = "BEGIN WORK;
	    				LOCK TABLE $table_object_arc, $table_object,$source_table IN EXCLUSIVE MODE;
	    				INSERT INTO $table_object_arc(eoa_id,eoa_id_parent,eoa_arc_type) ";
	    		$sql.= $selection_arc." RETURNING edw_object_arc.eoa_id;";
	    			
	    		//__debug($sql,"SQL INSERT INTO $table_object_arc");
	    
	    		$res_2 = pg_query($database_connection, $sql);
	    		$nb_res_2=pg_affected_rows($res_2);
	    		$res_2_error=pg_last_error();
	    		pg_query($database_connection, "COMMIT WORK;");
	    		
	    		if ($res_2_error != '')
	    		echo $res_2_error . " on " . $sql . "\n<br>";
	    		else
	    		echo $nb_res_2 . " = " . $sql . "\n<br>";
	    	}
	    
	    	// $this->MAJ_objecref_specific_default_values($id_group_table, $table_object_ref, $table_object, $edw_day);
	    
	    	pg_query($database_connection, "VACUUM VERBOSE ANALYZE $table_object_arc");
	    	printdate();
    	}	
    }
    
    
    
   /**
    * MHT2 19/10/2012 : surcharge de la méthode du cb pour corriger les insertion multiples en cas de multiprocess
    * fonction qui insère dans sys_to_compute lh'eure et le jour qui devront être traités
    * @param $day :jour à traiter
    * @param $hour : heure à traiter
    *
    * 13/10/2011 NSE DE Bypass temporel : ajout d'un paramètre pour ne pas insérer de compute hour en cas de bypass day
    */
    
    function insert_into_sys_to_compute($day, $hour, $bypass=false)
    {
    	//test multi_process mode
    	if($this->singleProcess){
    		parent::insert_into_sys_to_compute($day, $hour, $bypass=false);
    	}
    	else{
	    	global $database_connection;
	    	
	    	$offset = Date::getOffsetDayFromDay($day);
	    	if(!$bypass){
	    		// verifie si l'heure qu'on doit insérer l'a déjà été   		
	    			$query  = "BEGIN WORK;
	    						LOCK TABLE sys_to_compute IN EXCLUSIVE MODE;
	    						INSERT INTO sys_to_compute (day,offset_day,hour,time_type,newtime) SELECT '$day','$offset','$hour','hour',1
	    						WHERE NOT EXISTS (SELECT 1 FROM sys_to_compute WHERE time_type='hour' and hour='$hour')
	    						RETURNING sys_to_compute.id
	    						;";
	    			$res = pg_query($database_connection, $query);
	    			$res2=pg_query($database_connection,"COMMIT WORK;");
	    			
	    			if (pg_affected_rows($res) == 0) {
	    				//si l'heure existe déjà dans sys_to_compute, on remets juste le flag de traitement à 1
	    				$query = "UPDATE sys_to_compute set newtime=1 where hour='$hour' and time_type='hour'";
	    				print $query . "<br>";
	    				pg_query($database_connection, $query);
	    			}
	    			else{
	    				//sinon l'heure a été insérée
	    				print "**** Insertion dans sys_to_compute de l'heure *****<br>";
	    				print $query . "<br>";
	    			}
	    	}
	    	// si on a aucun résultat présent alors on inséère l'heure et le jour correspondant
	    	$query  = "BEGIN WORK;
	    	    						LOCK TABLE sys_to_compute IN EXCLUSIVE MODE;
	    	    						INSERT INTO sys_to_compute (day,offset_day,time_type,newtime) SELECT '$day','$offset','day',1
	    	    						WHERE NOT EXISTS (SELECT 1 FROM sys_to_compute WHERE time_type='day' and day='$day')
	    	    						RETURNING sys_to_compute.id
	    	    						;";
	    	
	    	$res = pg_query($database_connection, $query);
	    	$res2=pg_query($database_connection,"COMMIT WORK;");
	    	if (pg_affected_rows($res) == 0) {
	    		//si le jour existe déjà dans sys_to_compute, on remets juste le flag de traitement à 1
	    		$query = "UPDATE sys_to_compute set newtime=1 where day='$day' and time_type='day'";
	    		print $query . "<br>";
	    		pg_query($database_connection, $query);
	    	} else{
	    		//sinon le jour a été inséré
	    		print "**** Insertion dans sys_to_compute du jour *****<br>";
	    		print $query . "<br>";
	    	}
    	}	
    }
    
    
    
   /**
    * On redéfinit ici une méthode du CB (Cf. class\create_temp_table_generic.class.php) :
    * l'objectif est d'avoir un nom de table temporaire dépendant de la famille, et ainsi 
    * éviter le conflit si deux familles ayant le même niveau réseau réseau minimum (ex : cell)
    * sont traitées simultanément.
    * Fonction qui genere une table resultat à partir de toutes les tables d'un group table (en génral ces tables sont jointes)
    * @param array $tables : liste des tables
    * @param $hour : heure à traiter
     * @param $level NA
    * @return text $temp_table : nom de la table résultat
     * 
     * 10/10/2011 NSE DE Bypass temporel : traitement des tables normales ou de bypass
     * 21/10/2011 NSE bz 24329 : suppression du paramètre $bypass. La méthode est maintenant lancée avec 2 tableaux différents.
     *                           pb : avec 1 seul tableau, on mélangeait les tables de bypass et les normal. Les tests sur $t n'étaient plus bons.
    */

    function create_group_table_temp_table($tables, $hour, $level)
    {
    	//test multi_process mode
        if($this->singleProcess){
        	return parent::create_group_table_temp_table($tables, $hour, $level);
        }
        // tag permettant d'avoir des noms de tables différents si familles différentes
        $familyTag = "{$this->tempTableCondition->id_group_table}_";
        
        global $database_connection;
        // plutôt que de joindre plusieurs tables en même temps,
        // on ne fait des jointures que par groupe de 2 pour aboutir à une table finale
        for($t = 0;$t < count($tables);$t++) {
            // echo "table $t : ".$tables[$t]['table_name']."<br>";
            // 10/10/2011 NSE DE Bypass temporel : traitement des tables normales ou de bypass
            // Cree un index uniquement si le join est different de vide
            if ($this->jointure[$level] <> "") {
            	//problème en cas de parallélisation car uniqid se base sur l'heure courantesi pas de prefix -> potentiellement le même nom d'index.
                $query_index = "CREATE INDEX index_" . uniqid(rand()) . " ON " . $tables[$t]['table_name'] . " " . $this->jointure[$level];
                pg_query($database_connection, $query_index);
            }
            if ($t == 0) {
                echo "h : $hour / level : $level ";
                $temp_table_precedente = $tables[$t]['table_name'];
                $temp_table = "temp_table_" . $familyTag . $level;
                if($tables[$t]['bypass']==1)
                    $temp_table .= '_bypass'.'_'.$tables[$t]['ta'].'_'.Date::getTaValueFromHour($tables[$t]['ta'],$hour);
                else
                    $temp_table .= "_" . $hour;
                if ($this->jointure[$level] == "") { // cela signifie qu'il n'y a qu'une seule table donc il suffit de renommer la table
                    // 08/10/2010 NSE bz 17068 : on vérifie que la table existe
                    $query="select tablename from pg_tables WHERE  tablename='$temp_table_precedente'";
                    $result = pg_query($database_connection, $query);
                    if(pg_numrows($result)>0){
                        $query="ALTER TABLE ".$temp_table_precedente." RENAME TO ".$temp_table;
                        pg_query($database_connection, $query);
                        //echo "l.495 ".$query."<br>";
                        //TODO MHT2 dans le cas ou il y a une seule table, penser à ajouter les compteurs manquants des autres entités
                        //créer les colonnes pour les compteurs manquants, car toutes les tables w_astellia pour chaque entité n'ont pas été créées
                        $list_field_table_source = list_fields($temp_table);
                        $array_list_field = array_intersect($this->raw_counters_field_reference, $list_field_table_source);
                        unset($list_field_table_source);
                        $raw_counters_list = get_raw_counters_all_information($this->tempTableCondition->id_group_table);
                        $raw_counters_edw_target = $raw_counters_list["edw_target_field"];
                        $raw_counters_field_type = $raw_counters_list["edw_field_type"];
                        $fields_to_add=array_diff($raw_counters_edw_target,$array_list_field);
                        
                        $columns_to_add=array();
                        foreach($fields_to_add as $field){
                        	$key=array_search($field,$raw_counters_edw_target);
                        	$columns_to_add[]=$raw_counters_edw_target[$key]." ".$raw_counters_field_type[$key];
                        }
                        
                        unset($fields_to_add,$array_list_field,$raw_counters_field_type,$raw_counters_edw_target);
                        
                        //requête ajout de colonnes manquantes
                        foreach($columns_to_add as $column){
                        	//TODO MHT2 voir pour la default value de chaque colonne
                        	$query = "ALTER TABLE " . $temp_table . " ADD COLUMN ".$column.";";
                        	//echo "add column $column ".$query.'<br>';
                        	pg_query($database_connection, $query);
                        }
                        
                        unset($columns_to_add);
                    }
                }
            } elseif ($t == count($tables)-1) { 
                $temp_table = "temp_table_" . $familyTag . $level . "_" . $hour;
                $list_field_temp_table_precedente = list_fields($temp_table_precedente);
                $list_field_table_source = list_fields($tables[$t]['table_name']);

                //__debug($list_field_temp_table_precedente,"list_field_temp_table_precedente");

                $array_list_field = array_intersect($this->raw_counters_field_reference, array_merge($list_field_temp_table_precedente, $list_field_table_source));
                unset($list_field_temp_table_precedente,$list_field_table_source);

                // 19/05/2011 BBX - PARTITIONING -
                // Si le SGBD est en version >= 9.1 alors nous allons utiliser
                // des tables de type UNLOGGED afin de gagner en perf
                $unlogged = "";
                $db = Database::getConnection();
                if($db->getVersion() >= 9.1) {
                    $unlogged = "UNLOGGED ";
                }

                $query = "SELECT " . $this->specific_fields[$level] . ",".implode(",",$array_list_field) . " INTO " . $unlogged . $temp_table . " FROM " . $temp_table_precedente . " FULL OUTER JOIN " . $tables[$t]['table_name'] . " using " . $this->jointure[$level];
                //echo "l.518 ".$query.'<br>';
                pg_query($database_connection, $query);
                
                //créer les colonnes pour les compteurs manquants, car toutes les tables w_astellia pour chaque entité n'ont pas été créées
                $raw_counters_list = get_raw_counters_all_information($this->tempTableCondition->id_group_table);
                $raw_counters_edw_target = $raw_counters_list["edw_target_field"];
                $raw_counters_field_type = $raw_counters_list["edw_field_type"];
                $fields_to_add=array_diff($raw_counters_edw_target,$array_list_field);
                
                $columns_to_add=array();
				foreach($fields_to_add as $field){
					$key=array_search($field,$raw_counters_edw_target);
					$columns_to_add[]=$raw_counters_edw_target[$key]." ".$raw_counters_field_type[$key];
				}

				unset($fields_to_add,$array_list_field,$raw_counters_field_type,$raw_counters_edw_target);
				
				//requête ajout de colonnes manquantes
				foreach($columns_to_add as $column){
					//TODO MHT2 voir pour la default value de chaque colonne
					$query = "ALTER TABLE " . $temp_table . " ADD COLUMN ".$column.";";
					//echo "add column $column ".$query.'<br>';
					pg_query($database_connection, $query);
				}
				
				unset($columns_to_add);
                $temp_table_precedente = $temp_table;
            } else {
                $temp_table = "temp_" . $familyTag . uniqid(rand());
                $list_field_temp_table_precedente = list_fields($temp_table_precedente);
                $list_field_table_source = list_fields($tables[$t]['table_name']);
                // //__debug($list_field_table_source,"list_field_table_source");
                // //__debug($list_field_temp_table_precedente,"list_field_temp_table_precedente");
                // merge les colonnes des 2 tables qui doivent être jointe
                
                $array_list_field = array_intersect($this->raw_counters_field_reference, array_merge($list_field_temp_table_precedente, $list_field_table_source));
                unset($list_field);
                //dans ce cas, il faut introduire le champ uniqid car il permet d'avoir un jointure sur des données issues de sources indetiques.
                $query = "SELECT " . $this->specific_fields[$level] . ",".implode(",",$array_list_field) . " INTO TEMP " . $temp_table . " FROM " . $temp_table_precedente . " FULL OUTER JOIN " . $tables[$t]['table_name'] . " using " . $this->jointure[$level];
                //echo "l.534 ".$query.'<br>';
                pg_query($database_connection, $query);
                $temp_table_precedente = $temp_table;
                if ($this->jointure <> "") {
                    $query_index = "CREATE INDEX index_" . uniqid(rand()) . " ON " . $temp_table . " " . $this->jointure[$level];
                    pg_query($database_connection, $query_index);
                    //echo $query.'<br>';
                }
            }
        }
        // 08/10/2010 NSE bz 17068 : si la table n'existe pas, on retourne '' au lieu du nom de la table
        $query="select tablename from pg_tables WHERE  tablename='$temp_table'";
        $result = pg_query($database_connection, $query);
        if(pg_numrows($result)==0){
            return '';
        }
        foreach($this->na[$level] as $index){
                $query = "CREATE INDEX index_" . uniqid(rand()) . " ON " . $temp_table . " (" . $index . ")";
                pg_query($database_connection, $query);
            }
            //echo "create_group_table_temp_table - temp_table : $temp_table<br>";
        return $temp_table;
    }
}
?>