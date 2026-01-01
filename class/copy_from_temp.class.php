<?
/*
*	@cb51000@
*
*	28-06-2010 - Copyright Astellia
* 
*	Composant de base version cb_5.1.0.00
*
*	28/06/2010 NSE : Division par zéro - remplacement de l'opérateur / par //
*	15/07/2010 NSE : Suppression de l'opérateur //
 * 13/10/2011 NSE DE Bypass temporel : si la liste des heures est vide, on ne fait pa sla mise à jour
*
*/
?>
<?
/*
*	@cb41000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
*	maj 17/10/2008 - Maxime : On adapte le script pour la mise à jour de la topologie 
*	06/03/2009 SCT : bug 8906 => capture_duration et capture_duration_real supérieurs à 3600 pour le niveau minimum quand plusieurs sondes
*		- ajout d'un paramètre supplémentaire pour les conditions dans les formules de compteurs => $integration_level = 2;
*	15:35 04/06/2009 SCT => l'indexation des NA sur les tables temporaires génère des erreurs lors le nom de la table temporaire est proche de la limite de longueur Postgresql
*       08/10/2010 NSE bz 17068 : on vérifie que la table temporaire source existe avant de l'utiliser
*/
?>
<?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
	- maj 21/02/2008, benoit : suppression de l'insertion des données de la table temporaire vers la table de resultats hour. Cette insertion     est désormais réalisée dans le compute
	- maj 05/03/2008, maxime : Modification du label du message dans le TraceLog indiquant la fin du Retrieve
	- maj 08/07/2008, sebastien : Ajout du patch de la 23ème heure
*/
?>
<?php
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*/
?>
<?php
/*
*	@cb1300_iu2000b_pour_cb2000b@
*
*	19/07/2006 - Copyright Acurio
*
*	Composant de base version cb_1.3.0.0
*
*	Parser version iu_2.0.0.0
*/
?>
<?php
/*
*	16/06/2006 - Copyright Acurio
*
*	Composant de base version cb_1.2.0.2p
*
*	Parser version iub_1.1.0b
*/
?>
<?php

/*
* 2006-02-23 : Modification suite à la méthode modifiée pour la récupération de données
*
* * 2006-05-29 : ajout de timestamp entre les requetes
*
* 2006-08-03 :prise en compte du flag 'new_time' dans la table sys_to_compute
* 2006-10-09 : ajout d'un index sur le niveau minimum dans la table temporaire qui sert pour le compute daily. Cet index est important pour le calcul BH
* 19-04-2007 GH :
* 				Modification du message 'End Retrieve Process' qui s'affichait autant de fois qu'il y avait d'heures à traiter
* 				Modification importante de l'insertion des données 'hour' dans la table du jour. Là où avant on faisait un SELECT * INTO et on recréait systèmatiquement la table, maintenant on fait un DELETE puis un INSERT ce qui est bcp plus rapide
*
*/
class copy_from_temp {
    function copy_from_temp ()
    {
        global $database_connection;

        $this->init();
        $this->list_group_table = $this->get_group_table();
        $list_hours = $this->get_hours();
        foreach ($this->list_group_table as $group_table) {
            echo "<font color=blue> group table $group_table</font><br>";
            $this->init_group_table_variables($group_table);
			
			$family = getFamilyFromIdGroup($group_table);

            $list_network_level_base = $this->get_network_level_base($group_table, $family);
            $array_day_to_compute = array();
            if ($list_hours != false) {
                foreach($list_hours as $hour) {
                    print "<font color=blue>" . $hour . "</font><br>";
                    $day_to_compute = substr($hour, 0, 8);
                    // test qui va permettre de savoir si c'est la premiere fois qu'on rencontre ce jour
                    // le flag sert à indiquer s'il faut récupérer ou non les éléments pour la table temp du jour contenant toutes les heures de la journée
                    if (!in_array($day_to_compute, $array_day_to_compute)) {
                        $array_day_to_compute[$day_to_compute] = $day_to_compute; //stocke laliste des jours qui sont à traiter
                    }
                    foreach ($list_network_level_base as $level) {
						
                        $this->base_table = $this->group_name . "_raw_" . $level . "_" . $this->time_min;
                        $this->base_table_temp = "w_" . $this->group_name . "_raw_" . $level . "_" . $this->time_min;
                        $base_table_temp_hour = $this->base_table_temp . "_" . $hour;
						// 15:20 08/07/2008 SCT : insertion de l'heure dans la table day contenant les heures de toute une journée
                        $retour_existance_table = $this->table_day_creation($level, $this->base_table, $this->base_table_temp . "_" . $day_to_compute,$family);
                        // dans les cas de la création de la table $this->base_table, on copie les heures pour le jour concerné dans cette table (22-10-2007 SCT)
                        if($retour_existance_table==false) {
                            $this->table_day_1st_hour_creation($this->base_table_temp . "_" . $day_to_compute, $this->base_table, $day_to_compute);
                        }

						// 21/02/2008 - Modif. benoit : suppression de l'insertion des données de la table temporaire vers la table de resultats hour. Cette insertion est désormais réalisée dans le compute

						// insère la table temp dans la table hour
						//$this->insertion_table_hour($this->base_table, $base_table_temp_hour, $hour, $level);
                        // 08/10/2010 NSE bz 17068 : on vérifie que la table existe
                        $query="select tablename from pg_tables WHERE  tablename='$base_table_temp_hour'";
                        $result = pg_query($database_connection, $query);
                        if(pg_numrows($result)>0){
                            // insère dans la table hour depuis la table historique pour l'heure en question
                            $this->insertion_table_hour($this->base_table_temp . "_" . $day_to_compute, $base_table_temp_hour, $hour, $level, $family);
                            // si on est dans le cas d'un seul compute par jour pour tous les niveaux d'aggregation temporel
                            // alors la table ne contenant qu'un heure ne sert à rien et peut-être effacée
                            if ($this->compute_mode == "daily") {
                                postgres_drop_table($base_table_temp_hour);
                                // on supprime de sys_to_compute l'entree de type hour dont on ne va plus se servir
                                // on stocke l'heure à supprimer pour la supprimer toute à la fin car la liste des heures est utilisée par chaque group table
                                $this->liste_hour_to_delete[$hour] = $hour;
                            }
                        }
                    }
                }

				//Vacuum la table du jour et mets à jour la table sys_to_compute
                foreach ($array_day_to_compute as $day_to_compute) {
					$sql="VACUUM ANALYZE ".$this->base_table_temp . "_" . $day_to_compute;
					print $sql."<br>";
					pg_query($database_connection, $sql);
                    // Remets à 0 le flag pour traiter ou non le jour dans sys_to_compute
                    $query = "UPDATE sys_to_compute set newtime=0 WHERE day='$day_to_compute'";
                    pg_query($database_connection, $query);
                }
            } else {
                echo "No hours to be prepared for compute<br>";
            }
        }
        // 13/10/2011 NSE DE Bypass temporel : si la liste des heures est vide, on ne fait pa sla mise à jour
        if(!empty($list_hours)){
            // Remets à 0 le flag pour traiter ou non l'heure dans sys_to_compute une fois que tous les group table ont été traités
            $query = "UPDATE sys_to_compute set newtime=0 WHERE hour IN ('" . implode("','", $list_hours) . "')";
            print $query . "<br>";
            pg_query($database_connection, $query);
        }
        // on finit par supprimer la liste des heures lorsqu'on est en mode daily
        if ($this->compute_mode == "daily" and count($this->liste_hour_to_delete) > 0) {
            $query = "DELETE FROM sys_to_compute WHERE time_type='hour' AND hour IN (" . implode(',', $this->liste_hour_to_delete) . ')';
            pg_query($database_connection, $query);
        }
	// 15/11/2011 BBX
        // BZ 23222 : suppression du message de fin de retrieve
    }

    /*
        * fonction d'initialisation
        *
        */
    function init()
    {
		global $database_connection;		
		
        $global_parameters = edw_LoadParams();
        $this->system_name = $global_parameters["system_name"];
        $this->compute_mode = $global_parameters["compute_mode"];
    }

    /*
* fonction qui récupère la liste des group tables présents
* Il y en a forcément sinon l'appli aurait retourné des erreurs lors des scripts précédents
* @return array $groups contenant les identifiants des group table ou FALSE si rien n'est trouvé
*/
    function get_group_table()
    {
        global $database_connection;

        $query = "SELECT id_ligne FROM sys_definition_group_table WHERE raw_on_off=1 ORDER BY id_ligne";
        $res = pg_query($database_connection, $query);
        $nombre_resultat = pg_num_rows($res);
        if ($nombre_resultat > 0) {
            for ($i = 0;$i < $nombre_resultat;$i++) {
                $row = pg_fetch_array($res);
                $groups[$i] = $row[0];
            }
            return $groups;
        } else {
            return false;
        }
    }

    /*
	* fonction qui va recupérer la liste des group table qui ne s'aggregent pas à partir d'une autre source
	* pour cela le niveau rank est identifié par -1 soit par soit par le fait que le rank corresponde au même numero de ligne
	*/
	// 14/11/2008 BBX : ajout du paramètre family
    function get_network_level_base($id_group_table,$family)
    {
        global $database_connection;

        $query = "SELECT network_agregation FROM sys_definition_group_table_network WHERE data_type='raw' and (rank=-1 or rank=id_ligne) and id_group_table='$id_group_table'";

        $res = pg_query($database_connection, $query);
        $nombre_resultat = pg_num_rows($res);
        if ($nombre_resultat > 0) {
            for ($i = 0;$i < $nombre_resultat;$i++) {
			
                $row = pg_fetch_array($res);
				
				// maj maxime - On éclate les combinaisons pour les familles 3ème axe
                if(get_axe3($family)){
					$this->na[ $row[0] ] = explode("_",$row[0]);
				}else{
					$this->na[ $row[0] ] = $row[0];
				}
				
				$network_agregation[$i] = $row[0];
            }
            return $network_agregation;
        } else {
            return false;
        }
    }

    /*
        * function qui retourne les heures à traiter à partir de la table sys_to_compute. Ne prends que les nouvelles heures à traiter
        * @return array $hours contenant la liste des différentes heures à traiter
        */
    function get_hours()
    {
        global $database_connection;

        $query = "SELECT distinct hour FROM sys_to_compute WHERE time_type='hour' and newtime=1";
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

    /*
* fonction d'initialisation de toutes les informations nécessaires au script et propre à chaque group table
*
* @param $group_table_param : identifiant du group table
*/
    function init_group_table_variables($group_table_param)
    {
        $this->group_name = get_group_name($group_table_param);
        $gt_info = get_axe3_information_from_gt($group_table_param);
        $family = $gt_info["family"];
        $this->family = $family;
        $this->net_min = get_min_net($group_table_param, "raw");
        $this->time_min = get_min_time($group_table_param, "raw");
        $this->time_fields = select_time_fields($group_table_param, "raw", "-1");
        $this->net_fields = select_net_fields($group_table_param, "raw", "-1");
        $this->raw_cpts = get_raw_counters_all_information($group_table_param);
    }

    /*
        * fonction qui supprime puis insère dans la table edw qui contient les heures l'heure à traiter
        *@param  $base_table_temp_hour : table temporaire qui contient l'heure à traiter
        *@param $hour: heure à traiter
        */
    function insertion_table_hour($table_cible, $table_source, $hour, $level, $family)
    {
        global $database_connection;
        printdate();
        // Efface de la table hour toutes les données de l'heure considérée
        print "*** Suppression des données de la table hour pour l'heure $hour<br>";
        $query = "DELETE FROM $table_cible where hour='$hour'";
        $res = pg_query($database_connection, $query);
        $nombre_tuples_delete = pg_affected_rows($res);
        echo $nombre_tuples_delete . "=" . $query . "<br>";
        printdate();
		
		__debug($level,"level");
		__debug($this->net_min,"net_min");
		
        if ($level == $this->net_min) {
		
		
			if( get_axe3($family) ){
				$liste_network_aggregation = array();
				foreach($this->net_fields as $net){
				
						$na = explode("_",$net);
						if(!in_array( $na[0], $liste_network_aggregation) )
							$liste_network_aggregation[] = $na[0];
							
						if(!in_array( $na[1], $liste_network_aggregation) )
							$liste_network_aggregation[] = $na[1];
					
				}
			}else{
			
				$liste_network_aggregation = $this->net_fields;
			}
			
		} else {
		
            $liste_network_aggregation[] = $level;
			
        }
		

		__debug($liste_network_aggregation," net $family / $level");
        // Insertion des donnée "utiles" de la table hour pour l'heure considérée
        print "*** Insertion des données de la table hour pour l'heure $hour<br>";
        // 08/10/2010 NSE bz 17068 : on vérifie qu'il y a des raw d'activé pour la famille
        $query = "INSERT INTO $table_cible (" . implode(", ", $this->time_fields) . ","
         . implode(", ", $liste_network_aggregation) . (!empty($this->raw_cpts["edw_target_field"])?","
         . implode(", ", $this->raw_cpts["edw_target_field"]):"") . ")
                                SELECT " . implode(", ", $this->time_fields) . ","
         . implode(", ", $liste_network_aggregation)
         . (!empty($this->raw_cpts)?","
         . implode(", ", $this->raw_cpts["edw_agregation_field"]):"") . "
                                FROM " . $table_source;
        //
        if(!empty($this->raw_cpts))
            $keys = array_keys($this->raw_cpts["edw_field_type"], "text");

        if (count($keys) > 0) {
            unset($group_by_fields);
            for($k = 0;$k < count($keys);$k++)
            $group_by_fields[] = $this->raw_cpts["edw_target_field"][$keys[$k]];
            $query .= " GROUP BY " . implode(", ", $group_by_fields) . ","
             . implode(", ", $this->time_fields) . "," . implode(", ", $liste_network_aggregation);
        } else
            $query .= " GROUP BY " . implode(", ", $this->time_fields) . "," . implode(", ", $liste_network_aggregation);
        // modification liée au RI
        // le niveau minimum correspond au niveau qui est intégré. Dans IUB, il y a 2 niveaux intégrés à partir des sources cell et rnc.
        $time_expected_ri = get_sys_global_parameters("capture_duration");
        // RI liée aux nombre de sources (sondes) lorsqu'il y a une macrodiversite =plusieurs éléments distincs arrivent d'une même sonde
        $nb_sondes_ri = get_sys_global_parameters("nb_sources_expected");
        
		// maj maxime : Modification du $level pour les familles 3ème axe
		$_na = explode("_",$level);
		
		$min_net_ri = "'" . $_na[0] . "'"; //niveau minimum utilise lors de l'integration des données sources
        $network_ri = $min_net_ri; //le niveau d'agregation cible est égale au niveau minimum lors de l'integration de données sources
		$network = $_na[0]; //cette variable est la même que celle utilisée dans le compute. C'est l'agregation network cible. Elle est utilisée dans certains cas pour le bypass de compteurs.
		$network_source="'".$network."'";	//cette variable est la même que celle utilisée dans le compute. C'est l'agregation network source. Elle est utilisée dans certains cas pour le bypass de compteurs.
        $network_ri_dynamic = $_na[0]; //variable qui contient la colonne qui sert de parametre dans la fonction pl/sql
        $table_object_ri = "'edw_object_ref'"; //table object_ref
		
		$net_axe3 = "''";
		$arc_type = "''";
		
		if( get_axe3($family) ){
			$net_axe3 = $_na[1]; // 
			$arc_type = "'".$_na[0]."|s|".$_na[1]."'";
        }
		
		$time_coeff = 1; //pour le niveau hour, le coeff de temps vaut 1
        $aggreg_net_ri = 1; //precise que les calculs qui sont fait sont des aggregation réseau (la source et la cible ayant le meme time aggregation)
        $hour_ri = "hour"; //nom de la colonne qui sert dans la fonction CASE WHEN $aggreg_net_ri=1 THEN ri_calculation_sonde($hour_ri)::float4 ELSE SUM(capture_duration) END présent dans sys_field_reference
		// 10:02 06/03/2009 SCT : bug 8906 => ajout d'un paramètre supplémentaire pour les conditions dans les formules de compteurs
		$integration_level = 2;
		// 13/11/2008 BBX : ajout de la variable de la requête du calcul ri
		$query_ri = 1;
		
        eval("\$query = \"$query\";"); //on evalue les fonctions d'aggregation au cas oùles fonctions sont tapées en dur et contiennnent des variables	(exemple du RI)
        $res = pg_query($database_connection, $query);
        $nombre_tuples_inseres = pg_affected_rows($res);
        echo $nombre_tuples_inseres . "=" . $query . "<br>";
        printdate();
    }

    /**
     * fonction qui va vérifier si la table day (contenant les heures d'1 jour) existe et si les colonnes sont identiques à la table de niveau minimum.
     * Si la table n'existe pas, alors elle est crée sur le modèle de la table de niveau minimum
     * Si la structure ne correspond pas (il y a eu un ajout/suppression de compteurs depuis qu'elle a été créée) alors on ajoute les compteurs manquants
     * 15:21 08/07/2008 SCT : ajout d'un paramètre de retour indiquant si la table existait déjà
     */
    function table_day_creation($level, $table_reference, $table_du_jour, $family)
    {
        global $database_connection;
        // si la table n'existe pas, on la créée
        if (!check_table_in_database($table_du_jour)) {

            // 19/05/2011 BBX - PARTITIONING -
            // Si le SGBD est en version >= 9.1 alors nous allons utiliser
            // des tables de type UNLOGGED afin de gagner en perf
            $unlogged = "";
            $db = Database::getConnection();
            if($db->getVersion() >= 9.1) {
                $unlogged = "UNLOGGED ";
            }

            $sql = "CREATE " . $unlogged . "TABLE " . $table_du_jour . " (LIKE " . $table_reference.")";
			print $sql."<br>";
            pg_query($database_connection, $sql);
            $query_index = " CREATE INDEX " . $table_du_jour . "_index ON " . $table_du_jour . " (hour)";
			printdate();
            pg_query($database_connection, $query_index);
            // cree un index sur le level pour le calcul de la BH
			
			__debug($family,"family");
			//maj maxime  - On éclate la combinaison na _ na 3ème axe
			
			$this->na[$level] = explode("_",$level);			
			
			foreach($this->na[$level] as $na){
				// 15:16 04/06/2009 SCT : remplacement de "$table_du_jour .uniqid("")" par "uniqid("").uniqid("")" => bug lorsque les noms de table sont trop long
				// $query_index2 = " CREATE INDEX " . $table_du_jour .uniqid(""). "_index_na ON " . $table_du_jour . " ($na)";
				$query_index2 = " CREATE INDEX idx_" . uniqid("").uniqid(""). "_index_na ON " . $table_du_jour . " ($na)";
				__debug($query_index2,"query_index 2");
	            pg_query($database_connection, $query_index2);
			}
			
            $retour_existance_table = false;
        } else {
            // on vérifie la structure des 2 tables et on la met à jour si nécessaire
            $liste_colonne_table_ref = list_fields($table_reference);
            $liste_colonne_table_jour = list_fields($table_du_jour);
            $array_result = array_diff($liste_colonne_table_ref, $liste_colonne_table_jour); //cherche les colonnes présentes dans la référence et absente de la table du jour. Si en revanche il y a + de colonnes dans la table du jour, cela n'a pas d'importance.
            foreach ($array_result as $nom_compteur) 
            {
                // 12/10/2011 BBX
                // 22256 : application du même type que celui de la table de référence
                $db     = Database::getConnection();
                $type   = $db->getFieldType($table_reference, $nom_compteur);
                
                $sql = "ALTER TABLE " . $table_du_jour . " ADD COLUMN " . $nom_compteur . " ".$type;
				print "colonne ".$nom_compteur." ajoutée dans la table ".$table_du_jour."<br>";
                pg_query($database_connection, $sql);
            }
            $retour_existance_table = true;
        }
        return $retour_existance_table;
    }

    /*
        * SCT 22-10-2007
        * fonction qui intègre les données de la table source pour une journée.
        * - Dans le cas de la 1ère heure de la journée, l'insertion sera nulle (la table historique ne contient aucun enregistrement pour le jour : 1er retrieve)
        * - Dans le cas de la 23ème heure (intégration d'un fichier en retard après le compute daily), on récupère l'ensemble des données de la journée
        *
        * @param $table_cible : table temporaire dans laquelle on insère les données de la journée
        * @param $table_source : table source contenant les données pour l'ensemble de la journée (1ère heure = table vide, 23ème heure = table renseignée)
        * @param $day_to_copy : jour à traiter
        */
    function table_day_1st_hour_creation($table_cible, $table_source, $day_to_copy)
    {
        global $database_connection;
        printdate(); 
 
        // Insertion des donnée "utiles" de la table source vers la table cible pour le jour considéré
        print "*** Insertion des données de la table $table_source vers $table_cible pour le jour $day_to_copy<br>";
        $query = "INSERT INTO $table_cible SELECT * FROM $table_source WHERE day = '$day_to_copy'";
        $res = pg_query($database_connection, $query);
        $nombre_tuples_inseres = pg_cmdtuples($res);
        echo $nombre_tuples_inseres . "=" . $query . "<br>";
        printdate();
    } 
}

?>
