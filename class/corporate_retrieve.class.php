<?php
/*
 * - 09:08 04/02/2008 Gwénaël : force le calcul de voronoi en mettant à 1 la valeur de update_coord_geo dans sys_global_parameters
 * - 12:09 30/01/2008 Gwénaël : modification pour prendre en compte la BH
 * - 12:14 07/01/2010 NSE bz 13658 : suppression du paramètre id_country non utilisé
 * - 17/05/2010 NSE : Installation standard, utilisation d'une variable globale pour les chemins vers les exécutables psql et php (PSQL_DIR et PHP_DIR)
 * - 28/07/2010 OJT : Suppression de la constante PHP_DIR (bz17016)
 * - 03/11/2011 ACS BZ 24000 PG 9.1 Cast issue remaining
 */
?>
<?php
/**
 *
 *
 */
class CorporateRetrieve {
	/**
	 * Connection à la base de données
	 *
	 * @var Ressource
	 */
	var $db_connect;
	/**
	 *
	 * @var string
	 */
	var $rep_physique_niv0;
	/**
	 * identifiant de la table de référence (edw_object_X_ref)
	 *
	 * @var integer
	 */
	var $id_group_table;
	/**
	 * Nom de la table de référence (edw_object_X_ref)
	 *
	 * @var string
	 */
	var $group_table_name;
	/**
	 * Tableau de paramètres
	 * 	array[paramètre] = value
	 *
	 * @var array
	 */
	var $parameters;
	/**
	 * Liste des KPIs
	 * 	 array[index] = value
	 *
	 * @var array
	 */
	var $kpi;
	/**
	 * Liste des RAW Counters
	 *  	array[edw_field_name][index] = value
	 *  	array[edw_field_type][index] = value
	 *  	array[edw_aggregated_field_name][index] = value
	 *
	 * @var array
	 */
	var $raw;
	
	/**
	 * Constructeur
	 *
	 * @param string $rep_physique_niveau0
	 * @param integer  $id_group_table : identifiant de la table de référence (edw_object_X_ref)
	 *
	 * 07/01/2010 NSE bz 13658 : suppression du paramètre id_country non utilisé
	 *
	 */
	function CorporateRetrieve ($database_connection, $rep_physique_niveau0, $id_group_table) {
		$this->db_connect        = $database_connection;
		$this->rep_physique_niv0 = $rep_physique_niveau0;
		$this->id_group_table    = $id_group_table;
		
		$this->group_table_name  = get_group_table_name($this->id_group_table); 
		
		// 07/01/2010 NSE bz 13658 : suppression de l'appel au paramètre id_country non utilisé par la suite
		
        $this->parameters['start']     = getmicrotime(); 
        // sauvegarde le répertoire dans lequel sont les fichiers texte à traiter
        $this->parameters['repertoire_upload']   = $this->rep_physique_niv0 . 'upload/';
        $this->parameters['repertoire_template'] = $this->rep_physique_niv0 . 'flat_file_template/';
        $this->parameters['php_file_template']   = 'php_query_template.php';
        $this->parameters['edw_object_ref']      = false; 
        		
        // collecte la liste des network aggregation deployés pour le group table courant
        $this->net_fields = $this->getNAFields('raw'); 
        // collecte la liste des time aggregation deployés pour le group table courant
        $this->time_fields = $this->getTAFields('raw');
		$this->times_fields_select = array();
		$tmp_time_fields = $this->time_fields;
		do {
			reset($tmp_time_fields);
			$ta = current($tmp_time_fields);
			$this->times_fields_select[$ta] = $tmp_time_fields;
		} while ( array_shift($tmp_time_fields) );
		__debug($this->times_fields_select, '$this->times_fields_select');

		
		$this->getKPIs();
		$this->getRAWs();
	} // End function CorporateRetrieve
	
	/**
	 * Renvoie sous forme de tableau toutes les connexions
	 * 
	 * @return array
	 */
	function getAllConnections () {
			
		$query = "
			SELECT id_connection, connection_name, connection_ip_address, connection_login, connection_password, connection_type,id_region, connection_directory
			FROM sys_definition_connection 
			WHERE on_off=1
			";
		$result_connexion = $this->sql($query);
		$nombre_connexion = pg_numrows($result_connexion);
		if ( $nombre_connexion > 0 ) {
			$connections = array();
			for ( $i = 0; $i < $nombre_connexion; $i++ ) {
				$row = pg_fetch_array($result_connexion, $i);
				$id_connection = $row['id_connection'];
				$connections[$id_connection]['name']       = $row['connection_name'];
				$connections[$id_connection]['ip_address'] = $row['connection_ip_address'];
				$connections[$id_connection]['login']      = $row['connection_login'];
				$connections[$id_connection]['password']   = $row['connection_password'];
				$connections[$id_connection]['type']       = $row['connection_type'];
				$connections[$id_connection]['directory']  = $row['connection_directory'];
				$connections[$id_connection]['id_region']  = $row['id_region'];
			}
			
			// __debug($connections, 'getAllConnections()');
			return $connections;
		}
		
		return null;
	} // End function getConnectionProperties
	
	/**
	 * fonction qui collecte les KPI pour un group table identifiant de group table
	 * 
	 */
	function getKPIs () {
		global $database_connection;

		$query = "
			SELECT DISTINCT  t0.kpi_name 
			FROM  sys_definition_kpi t0, sys_definition_group_table t1 
			WHERE t1.edw_group_table = t0.edw_group_table 
				AND t0.on_off = '1' 
				AND t0.new_field! = '1' 
				AND t1.id_ligne = '".$this->id_group_table."'
			";

		$resultat_compteurs = $this->sql($query);
		$nombre_compteurs = pg_numrows($resultat_compteurs);

		for ( $i = 0; $i < $nombre_compteurs; $i++ ) {
			$row = pg_fetch_array($resultat_compteurs, $i);
			$this->kpi[$i] = $row['kpi_name']; //nom des KPI
		}
	} // End function getKPIs
	
	
	/**
	 * fonction qui collecte les compteurs pour un type d'element donne et un identifiant de group table
	 * Un lib element peut en effet appartenir à 1 ou plusieurs group table
	 */
	function getRAWs () {
		// 03/11/2011 ACS BZ 24000 PG 9.1 Cast issue remaining
		$query = "
			SELECT DISTINCT t0.edw_target_field_name, t0.edw_field_type, t0.edw_agregation_formula
			FROM sys_field_reference t0
			WHERE t0.on_off = '1' 
				AND t0.id_group_table = ".$this->id_group_table."
			";
		
		$resultat_compteurs = $this->sql($query);
		$nombre_compteurs = pg_numrows($resultat_compteurs);
		
		for ( $i = 0; $i < $nombre_compteurs; $i++ ) {
			$row = pg_fetch_array($resultat_compteurs, $i);
			$this->raw['edw_field_name'][$i]            = $row['edw_target_field_name']; //nom des compteurs utilisés dans la BDD
			$this->raw['edw_field_type'][$i]            = $row['edw_field_type']; //type de donées (int, float, text)
			$this->raw['edw_aggregated_field_name'][$i] = $row['edw_agregation_formula']; //nom présent dans la BDD externe            
		}
	} // End function getRAWs

	/**
	 * Fonction qui va créer les requete permettant de collecter des données d'un serveur de région vers un serveur Corporate
	 * depuis une table source vers une table cible
	 *
	 * @param string $id_region
	 * @return array
	 */
	function getSourceTargetTable ($id_region) {
		$array_compteur_type = array( 0 => 'raw', 1 => 'kpi' );
		
		if ( !empty($this->group_table_name) ) {
			foreach ( $array_compteur_type as $compteur_type ) {
				switch ( $compteur_type ) {
					case 'raw':
						$liste_counters = $this->raw['edw_field_name'];
						break;
					case 'kpi':
						$liste_counters = $this->kpi;
						break;
				}
				__debug($this->time_fields, '$this->time_fields');
				if ( count($liste_counters) > 0 && count($this->net_fields) > 0 ) {
					foreach ( $this->net_fields as $network_agregation ) {
						foreach ( $this->time_fields as $time_agregation ) {
							$table        = $this->group_table_name . '_' . $compteur_type . '_' . $network_agregation . '_' . $time_agregation;
							$table_temp   = 'temp' . uniqid("");
							$fichier_temp = $this->parameters['repertoire_upload'] . $table_temp;							
							$query_source[0] = "SELECT '" . $id_region . "_'||" . $network_agregation . "," . implode(',',$this->times_fields_select[$time_agregation]) . "," . implode(",", $liste_counters) . " FROM " . $table . " WHERE " . $network_agregation . " IS NOT NULL ";
							$query_cible[0]  = "DELETE FROM " . $table . " WHERE " . $network_agregation . " LIKE '" . $id_region . "%'";
							$query_cible[1]  = "COPY " . $table . " (" . $network_agregation . "," . implode(',',$this->times_fields_select[$time_agregation]) . "," . implode(",", $liste_counters) . ")" . " FROM '" . $fichier_temp . "' WITH DELIMITER ';' NULL AS ''";
							
							$query_liste['source'][$time_agregation][$table_temp] = $query_source;
							$query_liste['cible'][$time_agregation][$table_temp]  = $query_cible;
						}
					}
				}
				else {
					print "Aucun $compteur_type pour le group table $group_table_name ou Aucun niveau d'aggregation trouvé\n<br>";
				}
			}
		}
		else {
			$query_liste = false;
			print "Aucun group table n'a été trouvé";
		}
		
		// __debug($query_liste, 'getSourceTargetTable');
		return $query_liste;
	} // End function getSourceTargetTable
	
	/**
	 *
	 * - 12:09 30/01/2008 Gwénaël :  modification pour prendre en compte la BH
	 *	 
	 * @param array $query_list
	 * @param array $datesComputes
	 * @return array
	 */
	function queryFilter( $query_liste, $datesComputes ) {
		$location = $this->parameters['repertoire_upload'];
		__debug($datesComputes ,'$datesComputes ');
		if ( count($query_liste['source']) > 0) {
			foreach ( array_keys($query_liste['source']) as $time ) {
				switch ( $time ) {
					case 'hour':					
					case 'day':
						foreach ( $query_liste['source'][$time] as $key => $query ) {
							$query_filter_liste['source'][$key] = $query[0] . " AND day IN ('".implode("','", $datesComputes['day'])."');";
							$query_filter_liste['cible'][$key]  = $query_liste['cible'][$time][$key][0] . " AND day IN ('".implode("','", $datesComputes['day'])."');";
							$query_filter_liste['cible'][$key] .= $query_liste['cible'][$time][$key][1];
						}
						break;
					
					case 'week':
						if ( isset($datesComputes['week']) ) {
							foreach ( $query_liste['source'][$time] as $key => $query ) {
								$query_filter_liste['source'][$key] = $query[0] . " AND week IN ('".implode("','", $datesComputes['week'])."');";
								$query_filter_liste['cible'][$key]  = $query_liste['cible'][$time][$key][0] . " AND week IN ('".implode("','", $datesComputes['week'])."');";
								$query_filter_liste['cible'][$key] .= $query_liste['cible'][$time][$key][1];
							}
						}
						break;
					
					case 'month':
						if ( isset($datesComputes['month']) ) {
							foreach ( $query_liste['source'][$time] as $key => $query ) {
								$query_filter_liste['source'][$key] = $query[0] . " AND month IN ('".implode("','", $datesComputes['month'])."');";
								$query_filter_liste['cible'][$key]  = $query_liste['cible'][$time][$key][0] . " AND month IN ('".implode("','", $datesComputes['month'])."');";
								$query_filter_liste['cible'][$key] .= $query_liste['cible'][$time][$key][1];
							}
						}
						break;
						
					case 'day_bh':
						foreach ( $query_liste['source'][$time] as $key => $query ) {
							$query_filter_liste['source'][$key] = $query[0] . " AND day_bh IN ('".implode("','", $datesComputes['day'])."');";
							$query_filter_liste['cible'][$key]  = $query_liste['cible'][$time][$key][0] . " AND day_bh IN ('".implode("','", $datesComputes['day'])."');";
							$query_filter_liste['cible'][$key] .= $query_liste['cible'][$time][$key][1];
						}
						break;
					
					case 'week_bh':
						if ( isset($datesComputes['week']) ) {
							foreach ( $query_liste['source'][$time] as $key => $query ) {
								$query_filter_liste['source'][$key] = $query[0] . " AND week_bh IN ('".implode("','", $datesComputes['week'])."');";
								$query_filter_liste['cible'][$key]  = $query_liste['cible'][$time][$key][0] . " AND week_bh IN ('".implode("','", $datesComputes['week'])."');";
								$query_filter_liste['cible'][$key] .= $query_liste['cible'][$time][$key][1];
							}
						}
						break;
					
					case 'month_bh':
						if ( isset($datesComputes['month']) ) {
							foreach ( $query_liste['source'][$time] as $key => $query ) {
								$query_filter_liste['source'][$key] = $query[0] . " AND month_bh IN ('".implode("','", $datesComputes['month'])."');";
								$query_filter_liste['cible'][$key]  = $query_liste['cible'][$time][$key][0] . " AND month_bh IN ('".implode("','", $datesComputes['month'])."');";
								$query_filter_liste['cible'][$key] .= $query_liste['cible'][$time][$key][1];
							}
						}
						break;
				}
			}
		}
		
		// __debug($query_filter_liste, 'queryFilter()');
		return $query_filter_liste;
	} // End function queryFilter
	
	/** 
	 *
	 * @param string $query : requête SQL
	 * @param string $filename : nom du fichier
	 */
	function loadDataFromFile ( $query, $filename ) {
		__debug($query, '$query');
		if ( $this->sql($query) ) {
			$filename = $this->parameters['repertoire_upload'] . $filename;
			$commande = 'cat ' . $filename . ' | wc -l';
			$array_res = $this->cmd($commande, true);
			print $array_res[0] . ' lignes insérées<br>';
			// unlink($filename);
		}
	} // End function loadDataFromFile

	/**
	* Fonction qui execute une query sur un serveur distant, utlise la connexion ssh pour envoyer le fichier qui contient la requête à exécuter
	* 
	* @param string $filename
	* @param string $query
	* @param string $ip
	* @param string $login
	* @param string $directory
	* @return flag d'execution de la requete
	*/
	function copyServer2Server ( $filename, $query, $ip, $login, $directory ) {
		
		// *********** ETAPE 1 ********** //
		// Dans le fichier template, remplace $query_to_be_executed par la requete complete
		$php_file = $this->parameters['repertoire_template'] . $this->parameters['php_file_template'];
		$array_php_file_target = file($php_file);
		$array_php_file_target = str_replace('$query_to_be_executed', $query, $array_php_file_target);
		
		// *********** ETAPE 2 ********** //
		// Écrit le resultat dans un nouveau fichier php
		$php_file_target_region = $directory .'upload/'. $filename . ".php";
		$php_file_target_corporate = $this->parameters['repertoire_upload'] . $filename . ".php";
		$fp = fopen($php_file_target_corporate, "w+");
		for ($i = 0; $i < count($array_php_file_target);$i++) {
			fwrite($fp, $array_php_file_target[$i]);
		}
		fclose($fp);
		
		// *********** ETAPE 3 ********** //
		// Copie via SSH le fichier php à exécuter sur le serveur distant
		$cmd_copie = "scp -B $php_file_target_corporate $login@$ip:$php_file_target_region";
		unset($array_result);
		unset($output);
		$array_result = array();
		$array_result = $this->cmd($cmd_copie, true);
		unlink($php_file_target_corporate);
		if ( $array_result === false ) {
			print 'La connection n\'a pas pu être établie<br>';
			return 0;
		}
		
		// *********** ETAPE 4 ********** //
		// Execute le fichier php sur le serveur distant
		print 'Query : ' . $query . '<br>'; 
		$fichier_cible_region = $directory.'upload/'. $filename . ".bz2";
		$fichier_cible_corporate = $this->parameters['repertoire_upload'] . $filename . ".bz2";
		$cmd_execute   = "ssh $login@$ip 'php -q $php_file_target_region | bzip2 > " . $fichier_cible_region . "'";
		$this->cmd($cmd_execute);		
		$this->cmd("ssh $login@$ip 'rm $php_file_target_region'"); // supprime le fichier sur le serveur distant
		
		// *********** ETAPE 5 ********** //
		// Copie le fichier resultat vers le serveur central
		$cmd_copie = "scp $login@$ip:$fichier_cible_region $fichier_cible_corporate";
		$this->cmd($cmd_copie);
		$this->cmd("ssh $login@$ip 'rm $fichier_cible_region'"); // supprime le fichier sur le serveur distant
		
		// *********** ETAPE 6 ********** //
		// Dezippe le fichier sur le serveur local
		$cmd_dezippe = 'bunzip2 ' . $fichier_cible_corporate;
		$this->cmd($cmd_dezippe);
		
		return 1;
	} // End function copyServer2Server

	/**
	 * Récupère les dates dans la table sys_process_encours, on récupéréra ensuite uniquement les données pour ces dates
	 *
	 * @param string $ip
	 * @param string $login
	 * @param string $directory
	 */
	function getComputedDates ( $ip, $login, $directory ) {
		$offset_day = get_sys_global_parameters('offset_day');
		$dates = array();
		//on récupere en fait 3 jours de données pour faire une reprise automatique des jours précédents
		$dates['day'][] = getDay($offset_day);
		$dates['day'][] = getDay($offset_day + 1);
		$dates['day'][] = getDay($offset_day + 2);
		
		$week_now     = getweek($offset_day - 1);
		$week_offset  = getweek($offset_day);
		$week_history = getweek($offset_day + 8); //on tombe forcément dans la semaine précédente
		if ( $week_now != $week_offset ) {
			$weeks = array_map("GetweekFromAcurioDay", $dates['day']);
			$weeks = array_unique($weeks);
			$dates['week'] = $weeks;
		}
		
		$month_now     = getmonth($offset_day - 1);
		$month_offset  = getmonth($offset_day);
		$month_history = getmonth($offset_day + 40); //on tombe forcément dans le mois précédent
		if ( $month_now != $month_offset ) {
			$months = array();
			foreach ( $dates['day'] as $date ) {
				$months[] = substr($date, 0 ,6);
			}
			$months = array_unique($months);
			$dates['month'] = $months;
		}
		return $dates;
		
		// SERVIRA PLUS TARD AVEC UN PROCHAIN CB........(on ne sait pas encore quand!!)
		// $query = "SELECT computed_date FROM sys_process_encours WHERE encours = 0 AND char_length(computed_date) = 8";
		// $filename = 'temp' . uniqid("");
		// $status = $this->copyServer2Server ( $filename, $query, $ip, $login, $directory );
		// if ( $status ) {
			// $dates = array();
			// $handle = fopen($this->parameters['repertoire_upload'].$filename, "r");
			// while ( ($row = fgetcsv($handle, 1000, ';')) !== false ) {
			   // $dates[] = $row;
			// }
			// fclose($handle);
			// if ( count($dates) == 0 )
				// return false;
			// else
				// return $dates;
		// }
		// else {
			// return false;
		// }
	} // End function getComputedDates
	
	/**
	 * Supprime les dates de la table sys_process_encours pour lesquels on a récupérer les données
	 *
	 * @param array $datesComputes
	 * @param string $ip
	 * @param string $login
	 * @param string $directory
	 */
	function delComputedDates ( $datesComputes, $ip, $login, $directory ) {
		// SERVIRA PLUS TARD AVEC UN PROCHAIN CB .......(on ne sait pas encore quand!!)
		// $query = "UPDATE sys_process_encours SET computed_date = NULL WHERE computed_date IN ('".implode("','", $datesComputes)."')";
		// $filename = 'temp' . uniqid("");
		// $status = $this->copyServer2Server ( $filename, $query, $ip, $login, $directory );
	} // End function delComputedDates
	
	/**
	 * Fonction qui copie et integre la base de reference distante
	 * 
	 * @param string $filename
	 * @param string $id_region
	 */
	function loadAndIntegrateRemoteReferenceTable ( $filename_fields, $filename, $id_region ) {
		__debug($id_region);
		// *********** ETAPE 1 ********** //
		// Récupère la liste des champs de la table référence distante
		$cmd = 'cat '.$this->parameters['repertoire_upload'].$filename_fields;
		$fields = $this->cmd($cmd, true);
		unlink($this->parameters['repertoire_upload'].$filename_fields);
		__debug($fields, '$fields');
		
		// *********** ETAPE 1 ********** //
		// Crée une table temporaire identique à la table de reference (mêmes colonnes)
		$temp_table       = 'tmp' . uniqid("");
		$object_ref_table = 'edw_object_'.$this->id_group_table.'_ref';
		$create_table     = 'CREATE temp TABLE ' . $temp_table . " (LIKE $object_ref_table)";
		$this->sql($create_table); 

		// *********** ETAPE 2 ********** //
		// Integre le fichier dans une table temporaire
		$query = "COPY ".$temp_table." (".implode(",", $fields).") FROM '" . $this->parameters['repertoire_upload'] . $filename . "' WITH DELIMITER ';' NULL AS ''";
		$res = $this->sql($query);
		if ( $res ) {
			echo pg_cmdtuples($res) . '=' . $query . "\n<br>";
			unlink($this->parameters['repertoire_upload'] . $filename);
		} 
		
		// *********** ETAPE 3 ********** //
		// Ajoute devant chaque colonne de la table de référence, l'identifiant du pays
		$array_net_fields = $this->net_fields;
		// la requete est un peu bancale car le rank n'est à priori par forcement l'id du group table mais cela marche pour le trial SFR IU
		$query = "SELECT network_aggregation_min FROM sys_definition_categorie WHERE rank=" . $this->id_group_table;
		$res = $this->sql($query);
		$row = pg_fetch_array($res, 0);
		$min_net = $row[0];
		// ajoute à la liste des network aggregation le niveau minimum car c'est lui qui sert pour l'insertion des nouveaux éléments
		if ( !in_array($min_net, $array_net_fields) )
			array_unshift($array_net_fields, $min_net);
		
		// sinon on ne mets pas devant le niveau minimum le code pays, il se peut qu'entre les serveurs fils ont ait des doublons au niveau des noms
		$query = "UPDATE $temp_table SET ";
		// >>>>>>>>>>
		// modif 26/03/2008 GHX
		// modif pour prendre en compte le troisième axe
		$used = array(); // contient la liste des NA déjà utilisé dans la requête
		foreach ($array_net_fields as $net_field ) {
			if ( substr_count($net_field, '_' ) == 0 ) {
				if ( in_array($net_field, $used) )
					continue;
				$query .= $net_field . "=CASE WHEN $net_field IS NOT NULL AND $net_field<>'' THEN '$id_region'||'_'||" . $net_field . " ELSE $net_field END,";
				$query .= $net_field . "_label=CASE WHEN " . $net_field . "_label IS NOT NULL AND " . $net_field . "_label<>'' THEN '$id_region'||'_'||" . $net_field . "_label ELSE " . $net_field . "_label END,";
			}
			else {
				$sub_net_field = explode('_', $net_field);
				$query .= $net_field . "=CASE WHEN $net_field IS NOT NULL AND $net_field<>'' THEN '$id_region'||'_'||" . $net_field . " ELSE $net_field END,";
				if ( !in_array($sub_net_field[0], $used) ) {
					$query .= $sub_net_field[0] . "=CASE WHEN ". $sub_net_field[0] ." IS NOT NULL AND ". $sub_net_field[0] ."<>'' THEN '$id_region'||'_'||". $sub_net_field[0] ." ELSE ". $sub_net_field[0] ." END,";
					$query .= $sub_net_field[0] . "_label=CASE WHEN " . $sub_net_field[0] . "_label IS NOT NULL AND " . $sub_net_field[0] . "_label<>'' THEN '$id_region'||'_'||" . $sub_net_field[0] . "_label ELSE " . $sub_net_field[0] . "_label END,";			
					array_push($used, $sub_net_field[0]);
				}
				if ( !in_array($sub_net_field[1], $used) ) {
					$query .= $sub_net_field[1] . "=CASE WHEN ". $sub_net_field[1] ." IS NOT NULL AND ". $sub_net_field[1] ."<>'' THEN '$id_region'||'_'||". $sub_net_field[1] ." ELSE ". $sub_net_field[1] ." END,";
					$query .= $sub_net_field[1] . "_label=CASE WHEN " . $sub_net_field[1] . "_label IS NOT NULL AND " . $sub_net_field[1] . "_label<>'' THEN '$id_region'||'_'||" . $sub_net_field[1] . "_label ELSE " . $sub_net_field[1] . "_label END,";			
					array_push($used, $sub_net_field[1]);
				}
			}
			array_push($used, $net_field);
		}
		// <<<<<<<<<
		$query = substr($query, 0, -1); //enleve la derniere virgule
		$this->sql($query);
		print $query . '<br>'; 

		// *********** ETAPE 4 ********** //
		// Supprime les elements dans object_ref présents dans la table qui a été remontée
		// puis insere les données issues de la table temporaire
		$query = "DELETE FROM $object_ref_table WHERE $min_net IN (SELECT $min_net FROM $temp_table)";
		$res = $this->sql($query);
		if ( $res )
			echo pg_cmdtuples($res) . '=' . $query . '<br>';
		
		$query = "INSERT INTO $object_ref_table SELECT * FROM  $temp_table WHERE $min_net IS NOT NULL";
		$res = $this->sql($query);
		if ( $res )
			echo pg_cmdtuples($res) . '=' . $query . '<br>';
			
		// modif 09:08 04/02/2008 Gwénaël
			// Force le calcul de voronoi
		$query = "UPDATE sys_global_parameters SET value = 1 WHERE parameters = 'update_coord_geo'";
		$this->sql($query);
	} // End function loadAndIntegrateRemoteReferenceTable
	
	/**
	 * Met à jour (en forcant) le niveau d'agrégation Corporate qui est le niveau maximum pour chaque famille
	 * Pour le moment na = corporate et na_label = Corporate et il n'y a pas moyen de le changer.
	 *
	 * Le label des niveaux Corporates peuvent être changer via le fichier corporate_label.ini
	 *
	 */
	function updateNACorporate () {
		// Récupère le label du niveau Corporate contenu dans le fichier parser/MODULE/scripts/corporate_label.ini
		$file_label = $this->rep_physique_niv0 . 'parser/'.get_sys_global_parameters('module').'/config/corporate_label.ini';
		// >>>>>>>>>>
		// modif 26/03/2008 GHX
		// si le fichier n'existe pas on met une valeur par défaut
		if ( file_exists($file_label) ) {
			$labels = parse_ini_file($file_label, TRUE);
			$label = $labels[getFamilyFromIdGroup($this->id_group_table)]['label'];
		}
		else
			$label = 'Corporate';
		// <<<<<<<<<
		
		// récupère le niveau maximum de la famille
		$query = "SELECT network_agregation
          FROM sys_definition_group_table_network
          WHERE id_group_table = '".$this->id_group_table."'
			AND data_type = 'raw'
		ORDER BY rank DESC LIMIT 1";
        $res = $this->sql($query);
        list($na_max) = pg_fetch_row($res);
        
		// >>>>>>>>>>
		// modif 26/03/2008 GHX
		// modif pour prendre en compte le troisième axe
		// Met à jour le na et na_label
		if ( substr_count($na_max, '_' ) == 0 )
			$query_update = "UPDATE edw_object_".$this->id_group_table."_ref SET ".$na_max." = 'corporate', ".$na_max."_label = '".$label."'"; 
		else
			$query_update = "UPDATE edw_object_".$this->id_group_table."_ref SET ".substr(strrchr($na_max, '_'), 1 )." = 'corporate', ".substr(strrchr($na_max, '_'), 1 )."_label = '".$label."'"; 
		// <<<<<<<<<
		
		$this->sql($query_update);
	} // End function updateNACorporate
	
	/**
	 * Retourne les niveaux de network pour le groupe et le type passés en paramétres.
	 * si $op=1, retourne les niveaux X déployer.
	 * si $op=-1, retourne tous les niveaux déployés
	 *
	 * @param string $data_type
	 * @param bool $op
	 * @return array
	 */
	function getNAFields ( $data_type ) {
		$query = "
			SELECT DISTINCT network_agregation, rank
			FROM sys_definition_group_table_network sdgtn, sys_definition_network_agregation sdna
			WHERE split_part(sdgtn.network_agregation, '_', 1) = sdna.agregation
				AND sdna.corporate IS NULL
				AND data_type = '$data_type'
				AND id_group_table = '".$this->id_group_table."'";
		$query .= " ORDER BY rank, network_agregation";
		
		$res = $this->sql($query);
		while ( $row = pg_fetch_row($res) ) {
			$nets[] = $row[0];
		}
		
		$nets = array_unique($nets);
		__debug($nets, '$nets');
		return $nets;
	} // End function getNAFields
	
	/**
	 * Récupère les TA déployés
	 *
	 * - 12:08 30/01/2008 Gwénaël : modification de la requête pour prendre en compte la BH
	 *
	 * @param string $data_type
	 */
	function getTAFields ( $data_type ) {
		$query = "
			SELECT time_agregation 
			FROM sys_definition_group_table_time
            WHERE data_type = '".$data_type."'
	            AND id_group_table = '".$this->id_group_table."' 
				AND on_off = 1
			ORDER BY id_ligne";
	    $res = $this->sql($query);
	    while ( $row = pg_fetch_array($res) ) {
	        $fields[] = $row[0];
	    }
	    return $fields;
	} // End function getTAFields 
	
	/**
	 * Exécute une requete SQL et renvoie le résultat
	 *
	 * @param string $query
	 * @param Ressource $result
	 */
	function sql ( $query ) {
		// __debug($query, '$query');
		
		$result = @pg_query($this->db_connect, $query);
		
		if ( !$result ) {
			$_ = debug_backtrace();
			$f = null;
			while ( $d = array_pop($_) ) {
				if ( (strtolower($d['function']) == 'sql') ) break;
				$f = $d;
			}	
			echo '<br /><u>$query :</u> <span style="font: 9pt Verdana;">[function : <code>'.$f['class']. '::'.$f['function'].'()</code> - line <code>'.$d['line'].'</code>]</span><br />';
			echo '<span style="color:red"><pre>'.str_replace(array("<", ">"), array('&lt;','&gt;'), $query).'</pre>';
			echo pg_last_error().'</span><br />';			
			return null;
		}
		
		return $result;
	} // End function sql
	
	/**
	 * Exécute une commande et renvoie le résultat si demandé. Si la commande ne c'est pas exécuté renvoi false
	 * Si le résultat n'est pas demandé et que la commande c'est bien exécute, true est renvoyé
	 *
	 * @param string $cmd
	 * @param boolean [optionnal] $result renvoi un résultat si vrai(false by default)
	 * @return mixed
	 */
	function cmd ( $cmd, $result = false ) {
		// __debug($cmd, 'cmd()');
		
		exec($cmd, $r, $e);
		
		if ( $e ) {
			echo '<div style="color:red"><u>ERREUR EXEC :</u><pre>'.$cmd.'</pre></div>';
			return false;
		}
		elseif ( $result ) {
			return $r;
		}
		else {
			return true;
		}
	} // End function cmd
	
} // End class CorporateRetrieve
?>