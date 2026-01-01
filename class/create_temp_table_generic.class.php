<?
/**
 * @cb51500@
 * 
 * 10/10/2011 NSE DE Bypass temporel
 * 21/10/2011 NSE bz 24329 : pb lors de l'intégration du fichier day en même temps que l'heure 23
 *                          -> séparation de la liste des tables en 2 tableaux, l'un pour le bypass, l'autre pour le cas normal
 * 21/10/2011 NSE : nettoyage démon
 */
?><?
/*
*	@cb41000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
*	maj 17/10/2008 - Maxime : On adapte le script pour la mise à jour de la topologie 
*
*	18/09/2009 GHX
*		- Correction du BZ 11499 [REC][T&A CB 5.0][PARSER MOTOROLA] Update de la topologie via le retrieve
*			-> Suppression des éléments déjà présent dans la table edw_object et qui sont dans la table de données w_% à traiter
*	11:02 08/10/2009 SCT : ajout de la fonction "setColorOnNE" pour la mise en place de couleurs par défaut sur les NA activés par "allow_color" de "sys_definition_network_agregation"
*	14:33 14/10/2009 SCT : ajout de la fonction "createSpecificQueryDefaultValue" pour la mise en place d'une valeur par défaut pour les éléments réseau
*	11:24 26/10/2009 SCT : BZ 12146 => couleur par défaut non fonctionnel lorsque plusieurs NA configurés
*	22/10/2009 GHX
*		- Correction du BZ 12240 [CB 5.0][Retrieve] probleme SQL si une famille n'a qu'un seul NA
*	11:34 27/10/2009 SCT : BZ 12024 => problème de valeur par défaut pour les éléments 3ème axe => modification de la fonction "createSpecificQueryDefaultValue"
*	11:42 27/10/2009 SCT : BZ 6424 => Création de la topologie des stats Core (par correction du BZ 12024)
*	10:19 28/10/2009 SCT : BZ 6424 => mise à jour des niveaux hnall et allhnall si non définis
*		- ajout de la fonction createSpecificQueryAddingNonExistingNE => permet d'ajouter un NE dans les tables de ref s'il n'existe pas
*		- exécution de la méthode "insertNonExistingNEInRef" si elle est définie dans le script parser.
*		- dans le cas d'un tableau définissant les valeurs de NE par défaut et si le niveau n'est pas défini, on effectue un CASE WHEN pour remplacer la valeur nulle (NE non défini) par la valeur par défaut
*	12:20 07/01/2010 NSE bz 13658 : suppression du paramètre id_country non exploité
 *      08/10/2010 NSE bz 17068 : on vérifie que la table temporaire source existe avant de l'utiliser
 *      24/11/2010 NSE bz 19335 : suppression d'un warning si tableau vide
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
*	maj 05/03/2008 - Lorsqu'aucune donnée n'est intégrée, on fait passé le message du tracelog en warning plutôt qu'en information
*/
?>
<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*/
?>
<?
/*
*	@cb21002@
*
*	23/02/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.0.02
*/
?>
<?
/*
*	@cb21000@
*
*	08/12/2006 - Copyright Acurio
*
*	Composant de base version cb_2.1.0.00
*/
?>
<?
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
/**
 *
 * @package retrieve flat file
 * Gere la creation des tables pour chaque group table à partir des tables contenant les données issues des fichiers texte
 *
 * 04/11/2005 : modification GH pour utiliser les fonctions d'aggregation des compteurs lors de l'insertion dans la table temp hour
 *
 * 22/02/2006 : prise en compte de plusieurs niveaux dans les fichiers sources pour générer des tables temporaires
 *
 * 20/04/2006 : si plusieurs group table, la fonction $this->get_network_level_by_group_table_from_w_table_list ne retournait les valeurs que pour le premier Group table
 *
 * 13/05/2006 : truncate de la table edw_object_x car conserver l'historique n'est pas utile
 * 				simplifcation de l'integration des données car la macrodiversité est géree lors de l'integration des données
 *
 * 2006-05-29 : utilisation des requetes sans le NOT IN (identique à ce qui a été fait pour les parsers GSM et Roaming)
 *
 * 28-02-2007 GH : modification pour que la requête n°5 prenne même les éléments de réseau inactif (on_off=0). Retour en arrière par rapport à la modif de Maxime qui dû reste n'a pas été notée
 * 				   suppression de la mise à on_off=0 pour les éléments non présents dans les fichiers sources et présents dans la topologie
 * 07/01/2010 NSE bz 13658 : suppression du paramètre id_country non utilisé dans l'appel de la fonction copy_temp_table_to_object_table()
 */

class create_temp_table
{
    function create_temp_table()
    {
        global $database_connection;

        $this->init();
        $this->list_group_table = $this->get_group_table_from_w_table_list();
        $this->list_network_level_by_group_table = $this->get_network_level_by_group_table_from_w_table_list();
		
        // 10:26 28/10/2009 SCT : BZ 6424 => exécution de la fonction "insertNonExistingNEInRef" si elle est définie dans le fichier create_temp_table_XXXX.class.php
        if(method_exists($this, 'insertNonExistingNEInRef'))
                $this->insertNonExistingNEInRef(date('Ymd', time()));
		
        if($this->list_group_table)
        {
		
            // On boucle sur tous les groupes déployés
            foreach ($this->list_group_table as $group_table) {
                $this->init_group_table_variables($group_table);
                // specifique au parser et qui sert à joindre les tables pour avoir une table par group table
                $this->get_join($group_table);

                echo "<font color=blue>******* Traitement du group_table $group_table - $this->family_label ********</font><br>";
                foreach ($this->list_network_level_by_group_table[$group_table] as $level) {
				

                    $this->list_hours = $this->get_hours($group_table, $level);
                    $net_min = $this->net_min;

                    $this->na[$level] = array("$level");
                    if( get_axe3($this->family) ){

                        $this->na[$level] = explode("_", $level);

                    }
					
                    foreach ($this->list_hours as $hour) {
						
                        echo "<font color=blue> $hour </font><br>";
                        $day = substr($hour, 0, 8);
                        // 21/10/2011 NSE bz 24329 : récupération de 2 tableaux (1 pour le bypass et 1 normal)
                        $list_tables = $this->get_tables_list($group_table, $hour, $level);
                        //print_r($list_tables);
                        // cree une table temporaire qui réalise la jointure des tables appartenant au même group table
                        $temp_table_hour_without_network_join = $this->create_group_table_temp_table($list_tables['normal'], $hour, $level);
                        // 10/10/2011 NSE DE Bypass temporel : on demande traitement des tables de bypass
                        $temp_table_hour_without_network_join_bypass = $this->create_group_table_temp_table($list_tables['bypass'], $hour, $level);
                        //echo "table normale : $temp_table_hour_without_network_join <br>";
                        //echo "table de bypass : $temp_table_hour_without_network_join_bypass <br>";
                        // on crée une nouvelle table w_edw si on a une table temporaire de bypass
                        if(!empty ($temp_table_hour_without_network_join_bypass)){
                            echo "Bypass $hour, $level<br>";
                            $temp_table_hour_with_network_join_bypass = "w_" . $this->group_name . "_raw_" . $level . "_bypass_" . substr($temp_table_hour_without_network_join_bypass,strrpos($temp_table_hour_without_network_join_bypass,'bypass_')+7);
                        }
                        $temp_table_hour_with_network_join = "w_" . $this->group_name . "_raw_" . $level . "_" . $this->time_min . "_" . $hour;

                        // mise à jour d'object_ref et object_1_ref					
                        // 08/10/2010 NSE bz 17068 : si la table source existe
                        if(!empty($temp_table_hour_without_network_join) || !empty($temp_table_hour_without_network_join_bypass)){
                            echo "Insertion dans la table hour temp\n<br>";
                            // genère la table hour à partir de la table hour et d'une jointure sur la table object_ref mise à jour
                            if(!empty($temp_table_hour_without_network_join))
                                $this->generate_hour_table($hour, $temp_table_hour_with_network_join,$temp_table_hour_without_network_join, $group_table, $level, $net_min);
                            // 10/10/2011 NSE DE Bypass temporel : 
                            if(!empty($temp_table_hour_without_network_join_bypass))
                                $this->generate_hour_table($hour, $temp_table_hour_with_network_join_bypass,$temp_table_hour_without_network_join_bypass, $group_table, $level, $net_min);

                            // Insertion dans les tables temporaires de topo
                            if ($this->net_min == $level) {
                                echo "Insertion dans la table object du jour\n<br>";
                                if(!empty($temp_table_hour_without_network_join))
                                    $this->copy_temp_table_to_object_table($temp_table_hour_with_network_join, $group_table, $day);
                                if(!empty($temp_table_hour_without_network_join_bypass))
                                    $this->copy_temp_table_to_object_table($temp_table_hour_with_network_join_bypass, $group_table, $day);

                            }

                            // insère dans sys_to_compute lh'eure et le jour à traiter
                            // 2011/10/13 NSE DE Bypass temporel : pas de compute hourly nécessaire si bypass day
                            $this->insert_into_sys_to_compute($day, $hour, empty($temp_table_hour_without_network_join)&&(TaModel::IsTABypassedForFamily('day', FamilyModel::getFamilyFromIdGroupTable($group_table))==1));
                            // ajoute la table hour à la liste des tables pour qu'elles soient toutes dropppées
                            if(!empty($temp_table_hour_without_network_join))
                                array_push($list_tables, $temp_table_hour_without_network_join);
                            if(!empty($temp_table_hour_without_network_join_bypass))
                                array_push($list_tables, $temp_table_hour_without_network_join_bypass);
                            //$this->drop_tables($list_tables);
                            // 28/11/2011 BBX
                            // BZ 24870 : correction de la suppression des tables temporaires
                            $this->drop_tables( $this->formatLstTables( $list_tables ) );

                            $query_clean_w_tables_list = "DELETE FROM sys_w_tables_list WHERE hour=$hour and group_table='$group_table' and network='$level'";
                            pg_query($database_connection, $query_clean_w_tables_list);
                        }
                    }
                }
            }
        }
        else
        {
            print "No Group table to manage<br>";
        }
        echo "Update Object Reference\n<br>";
        $this->updateObjectRef($day, $group_table);
        // 09:43 08/10/2009 SCT : ajout d'une méthode pour le remplissage de la couleur sur le 3ème axe
        $this->setupDefaultColorAxis(0);
		
    }

    /**
     * Méthode qui récupère l'ensemble des tables à supprimer
     * Ajouté pour le BZ 24870 (28/11/2011 BBX)
     * @param type $lstTables
     * @return type 
     */
    function formatLstTables( $lstTables )
    {
        $tab = array();

        foreach( $lstTables as $table )
        {
            if( is_array( $table ) ) {
                foreach( $table as $t) $tab[] = $t['table_name'];
            }
            else $tab[] = $table;
        } 

        return $tab;
    }

    /*
	* fonction d'initialisation
	*
	*/
    function init()
    {
		global $database_connection;
		
		pg_query($database_connection, "TRUNCATE edw_object;");
		pg_query($database_connection, "TRUNCATE edw_object_arc;");
		
		$global_parameters = edw_LoadParams();
		$this->sep_axe3 = $global_parameters["sep_axe3"];
		// 07/01/2010 NSE bz 13658 : suppression du paramètre id_country non utilisé 
        $this->system_name = $global_parameters["system_name"];
        $this->compute_mode = $global_parameters["compute_mode"];
		$this->net_min_main_family = get_network_aggregation_min_from_family( get_main_family() );
    }

    /*
* fonction d'initialisation de toutes les informations nécessaires au script et propre à chaque group table
*
* @param $group_table_param : identifiant du group table
*/
    function init_group_table_variables($group_table_param)
    {
        $gt_info = get_axe3_information_from_gt($group_table_param);
        $family = $gt_info["family"];
        $family_info = get_family_information_from_family($family);
		$this->family=$family;
        $this->family_label = $family_info['family_label'];
        $this->id_group_table = $group_table_param;
        $this->group_name = get_group_name($group_table_param);
        $this->net_min = get_min_net($group_table_param, "raw");
		$this->net_min_axe3 = get_network_aggregation_min_axe3_from_family($family);
		//__debug($this->net_min);
		
        $this->time_min = get_min_time($group_table_param, "raw");
        foreach ($this->list_network_level_by_group_table[$group_table_param] as $level) {
            $this->base_table[$level] = $this->group_name . "_raw_" . $level . "_" . $this->time_min;
        }
		
        // recupere les agregation network deployées
        $net_fields = select_net_fields($group_table_param, "raw", -1);		
		$this->net_fields = array();
		if( get_axe3($this->family) ){
			
			foreach($net_fields as $net_combi)
			{
				$net_tmp = explode("_", $net_combi);
				
				foreach($net_tmp as $net){
					if( !in_array($net,$this->net_fields) ){
						$this->net_fields[] = $net;
					}
				}
			}
			
		} else {
			$this->net_fields = $net_fields;
		}		
		
        // recupere les time agregation deployées
        $this->time_fields = select_time_fields($group_table_param, "raw", -1);
        // recupere les compteurs de field_reference
        $raw_counters_field_reference_information = get_raw_counters_all_information($group_table_param);
        $this->raw_counters_field_reference = $raw_counters_field_reference_information["edw_target_field"];
    }

    /*
	* function qui retourne les heures à traiter pour le group table en cours
	* @param identifiant du group table
	* @param identifiant du niveau réseau
	* @return array $hours contenant la liste des différentes heures à traiter
	*/
    function get_hours($group_table_param, $level)
    {
        global $database_connection;

        $query = "SELECT distinct hour FROM sys_w_tables_list WHERE group_table='$group_table_param' and network='$level'";
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
    * collecte la liste des tables présentes dans sys_w_tables_list pour une heure et un group table
    * il peut y en avoir 1 ou plusieurs suivant si un group table est composé de plusieurs types de fichiers différents
    * @param $group_table_param identifiant du group table
    * @param $hour : heure à traiter
    * @return array $tables : liste des tables à traiter
     * 
     * 10/10/2011 NSE DE Bypass temporel : ajout de l'information sur la Ta Bypassée dans le tableau retourné
    */

    function get_tables_list($group_table_param, $hour, $level)
    {
        global $database_connection;

        $query = "select table_name, ta, group_table from sys_w_tables_list where hour=$hour and group_table='$group_table_param' and network='$level'";
        $res = pg_query($database_connection, $query);
        $nombre_resultat = pg_num_rows($res);
        if ($nombre_resultat > 0) {
           // Suppression des tables vides
           // $tablestodelete = array();
            for ($i = 0;$i < $nombre_resultat;$i++) {
                $row = pg_fetch_array($res);
            //    $queryTb = "SELECT * FROM ".$row["table_name"];
            //    $resTb = pg_query($database_connection, $queryTb);
            //    if(pg_num_rows($resTb) > 0){
                    // 10/10/2011 NSE DE Bypass temporel : ajout de l'information sur la Ta Bypassée
                    // 21/10/2011 NSE bz 24329 : on crée 2 tableaux différents selon le cas
                    $TaBypassed = TaModel::IsTABypassedForFamily($row["ta"], FamilyModel::getFamilyFromIdGroupTable($row['group_table']));
                    if($TaBypassed==1)
                        $bypassnormal = 'bypass';
                    else
                        $bypassnormal = 'normal';
                    $tables[$bypassnormal][]['table_name'] = $row["table_name"];
                    $tables[$bypassnormal][sizeof($tables[$bypassnormal])-1]['ta'] = $row["ta"];
                    $tables[$bypassnormal][sizeof($tables[$bypassnormal])-1]['bypass'] = $TaBypassed;
              //  }
              //  else{
              //      echo "Table vide ".$row["table_name"]." ignorée et supprimée<br>";
              //     $tablestodelete[] = $row["table_name"];
              //      $query_clean_w_tables_list = "DELETE FROM sys_w_tables_list 
              //          WHERE hour=$hour 
              //          AND table_name= '".$row["table_name"]."'
              //          AND group_table='$group_table_param' 
              //          AND network='$level'";
              //      pg_query($database_connection, $query_clean_w_tables_list);
              //  }
            }
           // $this->drop_tables($tablestodelete);
            return $tables;
        } else {
            return false;
        }
    }

    /*
	* fonction qui récupère pour chaque group table les niveaus d'aggregations venant des fichiers sources
	* à priori on en a 1 seul mais dans le cas des bypass, il se peut qu'on en ait plusieurs
	*
	*/
    function get_network_level_by_group_table_from_w_table_list()
    {
        global $database_connection;

        // 24/11/2010 NSE bz 19335 : suppression d'un warning si tableau vide
        if(!empty ($this->list_group_table) && is_array($this->list_group_table))
          foreach ($this->list_group_table as $group_table) {
            $query = "SELECT distinct network FROM sys_w_tables_list where group_table='$group_table'";
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
    /*
* fonction qui récupère la liste des group tables à partir de la table contenant la liste des tables temporaires créées à partir des fichiers texte
* @return array $groups contenant les identifiants des group table ou FALSE si rien n'est trouvé
*/
    function get_group_table_from_w_table_list()
    {
        global $database_connection;

        $query = "SELECT distinct group_table FROM sys_w_tables_list";
	
		
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

    /*function qui drop les tables qui ont servi à creer la table du group table
	* @param array : $tables liste des tables à dropper
	*/

    function drop_tables($tables)
    {
        foreach ($tables as $table_name) {
            postgres_drop_table($table_name);
        }
    }

   /*
    * fonction qui insère dans sys_to_compute lh'eure et le jour qui devront être traités
    * @param $day :jour à traiter
    * @param $hour : heure à traiter
    * 
    * 13/10/2011 NSE DE Bypass temporel : ajout d'un paramètre pour ne pas insérer de compute hour en cas de bypass day
    */

    function insert_into_sys_to_compute($day, $hour, $bypass=false)
    {
        global $database_connection;
        if(!$bypass){
            // verifie si l'heure qu'on doit insérer l'a déjà été
            $query = "SELECT * FROM sys_to_compute WHERE time_type='hour' and hour=" . $hour;
            $res = pg_query($database_connection, $query);
            // si on a aucun résultat présent alors on inséère l'heure et le jour correspondant
            if (pg_num_rows($res) == 0) {
                print "**** Insertion dans sys_to_compute de l'heure *****<br>";
                // 12/12/2012 BBX
                // BZ 30489 : utilisation de la classe Date
                $offset = Date::getOffsetDayFromDay($day);
                $query = "INSERT INTO sys_to_compute (day,offset_day,hour,time_type,newtime) VALUES ('$day','$offset','$hour','hour',1)";
                print $query . "<br>";
                pg_query($database_connection, $query);
            } else{
                //si l'heure existe déjà dans sys_to_compute, on remets juste le falg de traitement à 1
                $query = "UPDATE sys_to_compute set newtime=1 where hour='$hour' and time_type='hour'";
                print $query . "<br>";
                pg_query($database_connection, $query);

            }
        }
        // verifie si le jour qu'on doit insérer l'a déjà été
        $query = "SELECT * FROM sys_to_compute WHERE time_type='day' and day=" . $day;
        $res = pg_query($database_connection, $query);
        // si on a aucun résultat présent alors on inséère l'heure et le jour correspondant
        if (pg_num_rows($res) == 0) {
            print "**** Insertion dans sys_to_compute du jour *****<br>";
            // 12/12/2012 BBX
            // BZ 30489 : utilisation de la classe Date
            $offset = Date::getOffsetDayFromDay($day);
            $query = "INSERT INTO sys_to_compute (day,offset_day,time_type,newtime) VALUES ('$day','$offset','day',1)";
            print $query . "<br>";
            pg_query($database_connection, $query);
        } else{
            //si le jour existe déjà dans sys_to_compute, on remets juste le flag de traitement à 1
            $query = "UPDATE sys_to_compute set newtime=1 where day='$day' and time_type='day'";
            print $query . "<br>";
            pg_query($database_connection, $query);
        }
    }
    /*
    * fonction qui genere une table resultat à partir de toutes les tables d'un group table (en génral ces tables sont jointes)
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
        global $database_connection;
        // plutôt que de joindre plusieurs tables en même temps,
        // on ne fait des jointures que par groupe de 2 pour aboutir à une table finale
        for($t = 0;$t < count($tables);$t++) {
            // echo "table $t : ".$tables[$t]['table_name']."<br>";
            // 10/10/2011 NSE DE Bypass temporel : traitement des tables normales ou de bypass
            // Cree un index uniquement si le join est different de vide
            if ($this->jointure[$level] <> "") {
                $query_index = "CREATE INDEX index_" . uniqid("") . " ON " . $tables[$t]['table_name'] . " " . $this->jointure[$level];
                pg_query($database_connection, $query_index);
            }
            if ($t == 0) {
                echo "h : $hour / level : $level ";
                $temp_table_precedente = $tables[$t]['table_name'];
                $temp_table = "temp_table_" . $level;
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
                    }
                }
            } elseif ($t == count($tables)-1) { 
                $temp_table = "temp_table_" . $level . "_" . $hour;
                $list_field_temp_table_precedente = list_fields($temp_table_precedente);
                $list_field_table_source = list_fields($tables[$t]['table_name']);

                //__debug($list_field_temp_table_precedente,"list_field_temp_table_precedente");

                $array_list_field = array_intersect($this->raw_counters_field_reference, array_merge($list_field_temp_table_precedente, $list_field_table_source));
                unset($list_field);

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


                $temp_table_precedente = $temp_table;
            } else {
                $temp_table = "temp_" . uniqid("");
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
                    $query_index = "CREATE INDEX index_" . uniqid("") . " ON " . $temp_table . " " . $this->jointure[$level];
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
                $query = "CREATE INDEX index_" . uniqid("") . " ON " . $temp_table . " (" . $index . ")";
                pg_query($database_connection, $query);
            }
	//echo "temp_table : $temp_table<br>";
        return $temp_table;
    }

    /*
	* fonction qui génère la table hour en joignat la table hour temporaire (celle qui ne contient pas les éléments réseau)
	* avec la table object_ref
	* la fonction ne renvoie rien car la table générée à un nom qui est connu du fait de la syntaxe utilisee
	* @param $hour : heure à traiter
	* @param $target_table : table résultat
	* @param $source_table : table source horaire sans les éléments réseau
	* @param $group_table_param : identifiant du group table
	*/
    function generate_hour_table($hour, $target_table, $source_table, $group_table_param, $level)
    {
        global $database_connection, $repertoire_physique_niveau0;
        
		//---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
		print "<font color=red>Etape n°1 - Intégration des données dans la table w_edw_ du jour</font><br>";
        //---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
		printdate();
		// si la table exite il faut la supprimer car on a fait une reprise des fichiers donc la table sera réinitialisée
        if (check_table_in_database($target_table)) {
            $query = "DROP TABLE " . $target_table;
            $res = @pg_query($database_connection, $query);
        }
				
        if ( $level == $this->net_min) {
			
			$list_fields_source = list_fields( $source_table );
			
			$join = "";
			$select = "";
			$select_tmp = array();
			$na_min = explode("_",$this->net_min);
			$nb_join = 0;
			$tab_child['net'][0] = $na_min[0];
			$tab_child['join'][0] = $na_min[0];

			
			if ( get_axe3($this->family) ){
			
				$tab_child['net'][1] = $na_min[1];
				$tab_child['join'][1] = $na_min[1];
				$nb_join++;
			}
			


			$lst_fields = $list_fields_source;
			
			// __debug($list_fields_source,"list_fields_source");
			
			foreach($this->net_fields as $k=>$net){
			
				if( !in_array($net, $tab_child['net']) 	){
				
					// __debug($lst_fields,"fields in table -> insert $net");

					if( !in_array( $net, $list_fields_source ) ){
					
					$child = $this->getChild( $net,$this->net_fields );	
					__debug($child,"child $net");
					
					$join.= " LEFT JOIN ( SELECT DISTINCT eoar_id, eoar_id_parent FROM edw_object_arc_ref WHERE eoar_arc_type = '$child|s|$net') arc_$nb_join ON ( ";
					
					
					$child_pos = array_keys($tab_child['net'], $child);
					$child_ref = $tab_child['join'][ $child_pos[0] ];
					
					if($child_ref !== "" and $child_ref !== null){ // la colonne précédemment créée puisqu'elle n'existe pas dans la table temp_
						
						__debug($child_ref,"child ref");
						__debug("<hr>");
						
						$join.= "$child_ref = arc_$nb_join.eoar_id )";
					}else{
						$join.= "$child = arc_$nb_join.eoar_id )";
					}

					// 11:49 28/10/2009 SCT : BZ 6424 => mise à jour des niveaux hnall et allhnall si non définis
					// 	- dans le cas d'un tableau définissant les valeurs de NE par défaut et si le niveau n'est pas défini, on effectue un CASE WHEN pour remplacer la valeur nulle (NE non défini) par la valeur par défaut
					if(isset($this->tableauNEDefaultValue[$net]))
						$select_tmp[$nb_join] = "CASE WHEN arc_".$nb_join.".eoar_id_parent IS NULL THEN '".$this->tableauNEDefaultValue[$net]."' ELSE arc_".$nb_join.".eoar_id_parent END as \"$net\"";
					else
						$select_tmp[$nb_join] = "arc_".$nb_join.".eoar_id_parent as \"$net\"";

					$tab_child['join'][$nb_join] = "arc_".$nb_join.".eoar_id_parent";
					
					
					$tab_child['net'][$nb_join] = $net;
					

					$nb_join++;
					}
				}	
			}
			__debug($tab_child,"tab child");
			// 15:46 22/10/2009 GHX
			// Correction du BZ 12240
			// Si le tableau $select_tmp est vide, implode returne rien cependant la requete SQL est incorrecte car on se retrouve avec une virgule en trop dans le sélect
			if ( count( $select_tmp ) > 0 ) 
				$select = ", ".implode(",", $select_tmp);

			
        } else {
			
			$list_fields_source = list_fields( $source_table );
			$join = "";
			$select = "";
        }
		
		// 19/05/2011 BBX - PARTITIONING -
                // Si le SGBD est en version >= 9.1 alors nous allons utiliser
                // des tables de type UNLOGGED afin de gagner en perf
                $unlogged = "";
                $db = Database::getConnection();
                if($db->getVersion() >= 9.1) {
                    $unlogged = "UNLOGGED ";
                }
		
		$query_insert = " SELECT t0." . implode(",t0.", $list_fields_source) . " $select			
						INTO " . $unlogged . $target_table . "
						FROM " . $source_table . " t0 $join";

		// __debug($query_insert,"query_insert");
		$res = pg_query($database_connection, $query_insert);
		
		
		
				
		$_module = __T("A_TRACELOG_MODULE_LABEL_COLLECT");
        if (pg_last_error() != '') {
            echo pg_last_error() . " on " . $query_insert . "\n<br>";
            $message = "Problem on the integration of data collected for $hour - $this->family_label";
            sys_log_ast("Critical", $this->system_name, $_module, $message, "support_1", "");
            $message = pg_last_error() . " on " . $query_insert;
            sys_log_ast("Critical", $this->system_name, $_module, $message , "support_2", "");
        } else {
            // Cree un index sur le niveau d'aggregation minimum car il est utilise pour la reprise de données
            //$query = "CREATE INDEX index_" . uniqid("") . " ON " . $target_table . " (" . $level . ")";

            // 17/09/2010 BBX
            // Correction de la définition des colonnes à indéxer
            // BZ 13939
            $query = "CREATE INDEX index_" . uniqid("") . " ON " . $target_table . " (" . str_replace('_', ',', $level) . ")";

            @pg_query($database_connection, $query);
            $query = "SELECT count(*) FROM " . $target_table;
            $res = pg_query($database_connection, $query); //$res va ecraser le précédent $res
            $row = pg_fetch_array($res, 0);
            $nombre_insertion = $row[0];

            echo $nombre_insertion . " = " . $query_insert . "\n<br>";
			$message = __T('A_COPY_FROM_TEMP_TABLE_GEN_NB_DATA_INSERTED',$nombre_insertion,$hour,$this->family_label);
			
            
            if ( $nombre_insertion > 0 )
				sys_log_ast("Info", $this->system_name,$_module, $message, "support_1", "");
			else
				sys_log_ast("Warning", $this->system_name, $_module, $message, "support_1", "");
        }
		printdate();
    }

	
	function getChild($na, $nets, $child=true){
	
		global $database_connection;
		
		$field_1 = "level_source";
		$field_2 = "agregation";
		
		if( !$child ){ // On récupère le parent
			$field_1 = "agregation";
			$field_2 = "level_source";
		} 
		
		$query = "
				  SELECT DISTINCT $field_1 
				  FROM sys_definition_network_agregation 
				  WHERE $field_1 IN ('".implode("','",$nets)."') 
				  AND $field_2 = '$na' 
				  AND $field_1 <> $field_2 
				  AND family = '$this->family'
				  LIMIT 1
				 ";
			
		$res = pg_query($database_connection,$query);
		$na_child = "";
		if(pg_numrows($res)>0){
			while( $row = pg_fetch_array($res) )
			{
				$na_child = $row[$field_1];
			}
		}
		
		__debug($na_child,"child");
		
		return $na_child;
	}
    /*
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
        global $database_connection;

        $table_object_ref = "edw_object_ref";
        $table_object = "edw_object";
        
		$table_object_arc = "edw_object_arc";
		
		
        // prend en compte les agregations network qui sont présentes dans la table source à partir des aggregations deployées
        $net_fields = check_columns_in_table($this->net_fields, $source_table);

		__debug($net_fields,"net fields");
	
		//---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
		print "<font color=red>Etape n°2 : Maj des tables edw_object et edw_object_arc à partir de w_astellia_X</font><br>";
		//---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
		printdate();
		// Insertion des données dans la table edw_object
		$sql = "INSERT INTO $table_object (eo_date, eo_id, eo_obj_type) ";
		
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
		
		$sql.= $selection;
		//__debug($sql,"SQL INSERT INTO edw_object");
		
		if($selection !== ""){
			$res = pg_query($database_connection, $sql);
			if (pg_last_error() != '') {
	            echo pg_last_error() . " on " . $sql . "\n<br>";
	        } else
	            echo pg_affected_rows($res) . " = " . $sql . "\n<br>";
		}
		
        pg_query($database_connection, "VACUUM VERBOSE ANALYZE $table_object");
       
		
		// Mise à jour de la table edw_object_arc		
		if( $selection_arc!=="" ){
			// 15:16 18/09/2009 GHX
			// Correctoin du BZ 11499
			$sql = "DELETE FROM $table_object_arc WHERE ROW(eoa_id,eoa_arc_type) IN ($deletion_arc)";
			$res_3 = pg_query($database_connection, $sql);
			if (pg_last_error() != '') {
	            echo pg_last_error() . " on " . $sql . "\n<br>";
	        }	
			
			$sql = "INSERT INTO $table_object_arc(eoa_id,eoa_id_parent,eoa_arc_type) ";
			$sql.= $selection_arc;
			
			//__debug($sql,"SQL INSERT INTO $table_object_arc");
		
			$res_2 = pg_query($database_connection, $sql);
			if (pg_last_error() != '') {
	            echo pg_last_error() . " on " . $sql . "\n<br>";
	        } else
	            echo pg_affected_rows($res_2) . " = " . $sql . "\n<br>";
		}
		
		// $this->MAJ_objecref_specific_default_values($id_group_table, $table_object_ref, $table_object, $edw_day);
		
		pg_query($database_connection, "VACUUM VERBOSE ANALYZE $table_object_arc");
		printdate();
    }

	
	/**
	* Fonction qui génère les requêtes spécifiques qui indique les valeurs par défault de certains niveaux d'agrégation réseau
	* @param $na_child : niveau d'agrégation enfant
	* @param $na_parent : niveau d'agrégation parent
	* @param $na_parent_value : valeur par défault du parent
	* @param $na_parent_label_value : valeur par défault du label du parent
	* @param $day : jour calculé
	* @return $sql : Requête qui met à jour les niveaux d'agrégation avec les valeurs par défault voulues
	*/
	function create_specific_query($na_child,$na_parent,$na_parent_value,$na_parent_label_value, $day){
	
		global $database_connection; 
		
		$sql = "";
		
		// On vérifie que le na_parent n'existe pas en base
		
		$query = "SELECT eor_id,eor_obj_type FROM edw_object_ref WHERE eor_obj_type = '$na_parent' and eor_id is not null UNION 
						  SELECT eo_id,eo_obj_type FROM edw_object WHERE eo_obj_type = '$na_parent' and eo_id is not null  LIMIT 1;";
				
		$res_1 = pg_query($database_connection,$query);
		
		if( pg_numrows($res_1) > 0){
			
			$query = "DELETE FROM edw_object_ref WHERE eor_obj_type = '$na_parent'";
			pg_query($database_connection,$query);
		}
		
		// On vérifie qu'il y a bien un na_child en base
		
		$query = "SELECT eor_id,eor_obj_type FROM edw_object_ref WHERE eor_obj_type = '$na_child' and eor_id is not null
				UNION
				
				SELECT eo_id,eo_obj_type FROM edw_object WHERE eo_obj_type = '$na_child' and eo_id is not null 
				
		limit 1;";
		$res_2 = pg_query($database_connection,$query);
	
		if( pg_numrows($res_2) > 0){
		
			// Insertion du na_parent dans la table edw_object
			$sql = "INSERT INTO edw_object_ref (eor_date,eor_obj_type,eor_id, eor_label)
					VALUES('$day','$na_parent','$na_parent_value','$na_parent_label_value');
			";
			
			__debug($sql,"sql");
			
			
			$sql.= "DELETE FROM edw_object_arc_ref WHERE eoar_arc_type = '$na_child|s|$na_parent';";
			
			// Insertion de l'arc na|s|na_parent dans la table edw_object_arc
			$sql.= "INSERT INTO edw_object_arc_ref (eoar_id, eoar_id_parent,eoar_arc_type)
					
					SELECT DISTINCT ON(eor_id) eor_id,'$na_parent_value','$na_child|s|$na_parent' FROM edw_object_ref WHERE eor_obj_type = '$na_child'
							UNION 

					SELECT DISTINCT on(eo_id) eo_id,'$na_parent_value', '$na_child|s|$na_parent'  FROM edw_object WHERE eo_obj_type = '$na_child'
					
			";
		
		}
	
		return $sql;
		
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
        global $database_connection;

        $table_object_ref = "edw_object_ref";
        $table_object_ref_params = "edw_object_ref_parameters";
        $table_object = "edw_object";
		
		$table_object_arc_ref = "edw_object_arc_ref";
		$table_object_arc = "edw_object_arc";
				
		// $query = "SELECT * FROM edw_object_arc";
		// $res = pg_query($database_connection,$query);
		
		// while($row = pg_fetch_array($res)) {
					
			// __debug($row,"row");
			
		// }
		
		
		//---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
        print "<font color=red>Etape n°3 - Maj des tables edw_object_ref et edw_object_arc depuis edw_object et edw_object_arc_ref </font><br>";
		//---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
		printdate();
		
        $sql = "INSERT INTO $table_object_ref (eor_date,eor_id,eor_label,eor_obj_type)
				SELECT DISTINCT eo_date, eo_id, null as eo_label, eo_obj_type FROM $table_object 
				LEFT JOIN $table_object_ref ON eo_id = eor_id AND eo_obj_type = eor_obj_type
				WHERE eor_id IS NULL
		";
		
		$sql2 = "INSERT INTO $table_object_ref_params (eorp_id)
				SELECT DISTINCT eo_id FROM $table_object 
				LEFT JOIN $table_object_ref_params ON eorp_id = eo_id 
				WHERE eorp_id IS NULL AND eo_obj_type = '$this->net_min_main_family'
		";
		
		
		$res_2 = pg_query($database_connection, $sql2);
		
		if (pg_last_error() != '')
            echo pg_last_error() . " " . $sql . "<br>";
		 else
            echo pg_affected_rows($res_2) . " = " . $sql . "\n<br>". pg_affected_rows($res_2) . " insérés dans la table " . $table_object_ref_params . "<br>";	
			
        $res_1 = pg_query($database_connection, $sql);
        
        if (pg_last_error() != '')
            echo pg_last_error() . " " . $sql . "<br>";
        else
            echo pg_affected_rows($res_1) . " = " . $sql . "\n<br>". pg_affected_rows($res_1) . " insérés dans la table " . $table_object_ref . "<br>";
			
		
		
		printdate();		
		$query = "DELETE FROM $table_object_arc_ref 
				  WHERE EXISTS (
							SELECT eoar_id FROM $table_object_arc 
							WHERE eoar_arc_type = eoa_arc_type AND eoar_id = eoa_id
							)
				";
		
		$res_2 = pg_query( $database_connection, $query );
		printdate();
		
		$sql = "INSERT INTO $table_object_arc_ref (eoar_id, eoar_id_parent, eoar_arc_type)
				SELECT DISTINCT eoa_id, eoa_id_parent, eoa_arc_type FROM $table_object_arc 
				LEFT JOIN $table_object_arc_ref ON eoa_id = eoar_id AND eoa_arc_type = eoar_arc_type
				WHERE eoar_id IS NULL
		";
		print $sql."<br>";
		$res_3 = pg_query($database_connection, $sql);
        if (pg_last_error() != '') {
            echo pg_last_error() . " on " . $sql . "\n<br/><br/>";
        } else
            echo pg_affected_rows($res_3) . " = " . $sql . "\n<br/>".pg_affected_rows($res_1) . " lignes insérés dans la table " . $table_object_arc_ref . "<br/><br/>";
		
		printdate();
		//---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
        // Remets à on_off=1 et reinitialise delete_counter=0 pour les cellules dont on_off était à 0 et qui sont présente dans la table object du jour
        print "<font color=red>Etape n°4 - Maj on_off + delete_counter pour les éléments actifs</font><br>";
		//---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
		printdate();
		
		// 18/06/2009 BBX :
		// On conserve la valeur on-off des cellules
		$keepOnOffValue = true;		
		if(!$keepOnOffValue) 
		{
			// Mise à jour du on_off
			$sql = "UPDATE $table_object_ref SET eor_on_off=1 WHERE eor_on_off = 0 AND EXISTS (SELECT eo_id FROM $table_object WHERE eo_date = '$day' and eor_id = eo_id)";

			$res_4 = pg_query($database_connection, $sql);
			 if (pg_last_error() != '') {
				echo pg_last_error() . " on " . $sql . "\n<br>";
			} else
				echo pg_affected_rows($res_4) . " = " . $sql . "\n<br/><br/>";

			
			// Mise à jour du delete_counter
			$sql = "UPDATE $table_object_ref_params SET eorp_delete_counter=0 WHERE EXISTS (SELECT eo_id FROM $table_object WHERE eo_date = '$day' and eorp_id = eo_id and eo_obj_type = '$this->net_min_main_family')";
			
			$res_5 = pg_query($database_connection, $sql);
			
			if (pg_last_error() != '') {
				echo  "<br/>".g_last_error() . " on " . $sql . "\n<br/>";
			} else
				echo  "<br/>".pg_affected_rows($res_5) . " = " . $sql . "\n<br/>";
		}
		else
		{
			print "Désactivé<br />";
		}
			
        // incrémente le compteur de 1 les cellules non présentes dans la table edw_object du jour
        printdate();
		//---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
		
		print "<font color=red>Etape n°5 - Incrémentation du delete_counter pour les éléments inactifs</font><br>";
		//---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
		 printdate();
        $sql = "UPDATE $table_object_ref_params SET eorp_delete_counter=eorp_delete_counter+1 WHERE NOT EXISTS ( SELECT eo_id FROM $table_object where eo_date='$day' and eo_id = eorp_id and eo_obj_type = '$this->net_min_main_family' )";
		
        $res_t = pg_query($database_connection, $sql);

		 if (pg_last_error() != '')
            echo "<br/>".pg_last_error() . " " . $sql . "<br/>";
        else
            echo "<br/>".pg_affected_rows($res_t) . " = $sql<br/>";
		
		
		// supprime toutes les cellules dont le compteur à atteint 720 ce qui signifie que pendant 720 fois les cellules ne sont pas remontées.
        // comme on est en mode horaire 720 représente 30 jours
		$sql_ref = "DELETE FROM $table_object_ref WHERE eor_obj_type = '$this->net_min_main_family' 
				AND eor_on_off = 0 
				AND EXISTS (
					SELECT eorp_id FROM $table_object_ref_params 
					WHERE eorp_delete_counter>=720 AND eorp_id = eor_id
				)
		";
		
		
		
        $sql_params = "DELETE FROM $table_object_ref_params WHERE eorp_delete_counter>=720 
				AND EXISTS (
					SELECT eor_id FROM $table_object_ref 
					WHERE eor_on_off = 0 AND eor_obj_type = '$this->net_min_main_family' AND eorp_id = eor_id 
				)
		";
        $res_t = pg_query($database_connection, $sql_ref);
        if (pg_last_error() != '')
            echo pg_last_error() . " " . $sql . "<br/>";
        else
            echo pg_affected_rows($res_t) . " éléments supprimés du fait de leur non présence pendant trop longtemps<br>";
			
		$res_t = pg_query($database_connection, $sql_params);
        if (pg_last_error() != '')
            echo pg_last_error() . " " . $sql . "<br>";
        else
            echo pg_affected_rows($res_t) . " éléments supprimés du fait de leur non présence pendant trop longtemps<br>";
			
        // Mets à jour specifiquement en fonction du parser
        $this->MAJ_objectref_specific($day);
		pg_query($database_connection, "VACUUM VERBOSE ANALYZE $table_object_ref");
    }
    /**
	* Fonction permet la mise en place d'une couleur par défaut sur les axes qui sont identifiés par la table sys_definition_network_agregation
	* @param $typeRandomColor : le type de fonction random à utiliser
	*	
	*
	*/
	function setupDefaultColorAxis($typeRandomColor=0)
	{
		global $database_connection;

		// recherche des NA dont le champ "allow_color" est positionné sur 1
		$tableauNaColorAllowed = getAllowColorNA();
		
		// dans le cas où le tableau n'est pas vide, on traite l'ensemble des NA
		if(count($tableauNaColorAllowed) > 0)
		{
			echo 'Des NA sont activés pour la définition automatique de couleur : '.implode(', ', $tableauNaColorAllowed).'<br>';
			switch(intval($typeRandomColor))
			{
				// cas 0 (ou par défaut) : la couleur est sélectionnée parmi un tableau en contenant les couleurs prédéfinies. On vérifie le pourcentage d'utilisation de chaque couleur afin d'avoir une bonne répartition
				case 0:
				default:
					// initialisation du tableau de couleur
					$tableauDefinedColor = array(
											'gris fonce' => '#666666', 
											'bleu fonce' => '#000099', 
											'vert fonce' => '#009900', 
											'rouge fonce' => '#990000', 
											'jaune fonce' => '#999900', 
											'bleu ciel fonce' => '#009999', 
											'violet' => '#990099', 
											'orange0' => '#FF6600', 
											'orange1' => '#FF9900', 
											'gris' => '#AAAAAA', 
											'vert' => '#0000FF', 
											'bleu' => '#00FF00',
											'rouge' => '#FF0000', 
											'jaune' => '#FFFF00', 
											'bleu ciel' => '#00FFFF', 
											'rose' => '#FF00FF');
					// nombre total d'éléments dans la liste pour les NA
					$query = '
						SELECT
							eor_obj_type,
							eor_id
						FROM 
							edw_object_ref
						WHERE 
							eor_obj_type IN (\''.implode("','", $tableauNaColorAllowed).'\')';
					$res_0 = pg_query($database_connection, $query);
					$nombreTotalNEToUpdate = pg_numrows($res_0);
					// 11:25 26/10/2009 SCT : BZ 12146 => couleur par défaut non fonctionnel lorsque plusieurs NA configurés
					// récupération de la liste des NE dont la couleur est vide et appartenant aux NA activés
					$query = '
						SELECT
							eor_obj_type,
							eor_id
						FROM 
							edw_object_ref
						WHERE 
							eor_color IS NULL
							AND eor_obj_type IN (\''.implode("','", $tableauNaColorAllowed).'\')
						ORDER BY eor_obj_type ASC';
					$res_1 = pg_query($database_connection, $query);

					$tableauNEColorNull = array();
					if(pg_numrows($res_1) > 0)
					{
						while($row = pg_fetch_array($res_1))
						{
							$tableauNEColorNull[] = array(
														'type' => $row['eor_obj_type'],
														'id' => $row['eor_id']);
						}
					}

					// 11:25 26/10/2009 SCT : BZ 12146 => couleur par défaut non fonctionnel lorsque plusieurs NA configurés
					// récupération du nombre d'élément présent dans edw_object_ref possédant une couleur définie
					$query_statistique = "
						SELECT 
							eor_color,
							COUNT(*) AS compteur
						FROM 
							edw_object_ref
						WHERE
							eor_color IN ('".implode("','", $tableauDefinedColor)."')
						GROUP BY
							eor_color";
					$res_2 = pg_query($database_connection, $query_statistique);

					$tableauNEColorStat = array();
					$nombreTotal = pg_numrows($res_2);
					if($nombreTotal > 0)
					{
						while($row = pg_fetch_array($res_2))
						{
							$tableauNEColorStat[$row['eor_color']] = $row['compteur'];
						}
					}

					// on crée les index de couleur qui n'existe pas
					foreach($tableauDefinedColor AS $valeurTableauDefinedColor)
					{
						if(!isset($tableauNEColorStat[$valeurTableauDefinedColor]))
							$tableauNEColorStat[$valeurTableauDefinedColor] = 0;
					}
					// on trie le tableau sur les valeurs
					asort($tableauNEColorStat);
					// nombre de NE normalement prévu par couleur
					$nombreNEParCouleur = $nombreTotalNEToUpdate / count($tableauDefinedColor);
					// dans le cas où il existe des NE dont la couleur est nulle
					if(count($tableauNEColorNull) > 0)
					{
						echo 'Des éléments réseaux sont présents dans edw_object_ref possédant une couleur NULL : '.count($tableauNEColorNull).'<br>';
						list($couleurAAppliquer, $nombreElementAyantCetteCouleur) = each($tableauNEColorStat);
						// on mélange le tableau
						shuffle($tableauNEColorNull);
						// on effectue le traitement en parcourant le tableau
						foreach($tableauNEColorNull AS $ssTableauNEColorNull)
						{
							// construction de la requête de sélection de la couleur
							$queryUpdate = '
								UPDATE 
									edw_object_ref
								SET
									eor_color = \''.$couleurAAppliquer.'\'
								WHERE 
									eor_id = \''.$ssTableauNEColorNull['id'].'\'
									AND eor_obj_type = \''.$ssTableauNEColorNull['type'].'\'';
							$res_t = pg_query($database_connection, $queryUpdate);
							if (pg_last_error() != '')
								echo "<br/>".pg_last_error() . " " . $sql . "<br/>";

							// vérification si besoin de changement de couleur
							$nombreElementAyantCetteCouleur ++;
							if($nombreElementAyantCetteCouleur > $nombreNEParCouleur - 1)
							{
								list($nouvelleCouleurAAppliquer, $nombreElementAyantCetteCouleur) = each($tableauNEColorStat);
								if(!empty($nouvelleCouleurAAppliquer))
									$couleurAAppliquer = $nouvelleCouleurAAppliquer;
							}
						}
					}
					break;
			}
			// création d'un tableau contenant l'ensemble des couleur
		}
	} // end setupDefaultColorAxis()

	/**
	 * Fonction qui génère les requêtes spécifiques pour mettre un parent par défaut lorsqu'il n'existe pas
	 * FONCTION A METTRE DANS LE CB => CREATE_TEMP_TABLE_GENERIC.CLASS.PHP
	 * @param $na_child : niveau d'agrégation enfant
	 * @param $na_parent : niveau d'agrégation parent
	 * @param $na_parent_value : valeur par défault du parent
	 * @param $na_parent_label_value : valeur par défault du label du parent
	 * @param $day : jour calculé
	 * @return $sql : Requête qui met à jour les niveaux d'agrégation avec les valeurs par défault voulues
	 * BZ 12024 => mise en place d'une valeur par défaut
	 */
	function createSpecificQueryDefaultValue($na_child, $na_parent, $na_parent_value, $na_parent_label_value, $day)
	{
		global $database_connection; 
		
		$sql = "";
		// On vérifie que le na_parent n'existe pas en base
		$query = "
			SELECT 
				eor_id,
				eor_obj_type 
			FROM 
				edw_object_ref 
			WHERE 
				eor_obj_type = '$na_parent' 
				AND eor_id IS NOT NULL 
			UNION 
			SELECT 
				eo_id,
				eo_obj_type 
			FROM 
				edw_object 
			WHERE 
				eo_obj_type = '$na_parent' 
				AND eo_id IS NOT NULL 
			LIMIT 1;";
				
		$res_1 = pg_query($database_connection,$query);
		if(pg_numrows($res_1) > 0)
		{
			//$query = "DELETE FROM edw_object_ref WHERE eor_obj_type = '$na_parent'";
			// il existe, pas besoin de le supprimer
			//pg_query($database_connection,$query);
		}

		
		// On vérifie qu'il y a bien un na_child en base
		$query = "
			SELECT 
				eor_id,
				eor_obj_type 
			FROM 
				edw_object_ref 
			WHERE 
				eor_obj_type = '$na_child' 
				AND eor_id IS NOT NULL
			UNION
			SELECT 
				eo_id,
				eo_obj_type 
			FROM 
				edw_object 
			WHERE 
				eo_obj_type = '$na_child' 
				AND eo_id IS NOT NULL 
				
		limit 1;";
		$res_2 = pg_query($database_connection, $query);
		if(pg_numrows($res_2) > 0)
		{
			$sql = '';
			// Dans le cas où le NA parent n'existe pas, on l'ajoute dans la table edw_object_ref
			// Insertion du na_parent dans la table edw_object
			if(pg_numrows($res_1) == 0)
				$sql .= "
					INSERT INTO 
						edw_object_ref 
							(eor_date, eor_obj_type, eor_id, eor_label)
					VALUES
							('$day', '$na_parent', '$na_parent_value', '$na_parent_label_value');
				";
			
			__debug($sql,"sql");
			
			// 11:34 27/10/2009 SCT : BZ 12024 => problème de valeur par défaut pour les éléments 3ème axe
			// 11:42 27/10/2009 SCT : BZ 6424 => Création de la topologie des stats Core (par correction du BZ 12024)
			//$sql .= "DELETE FROM edw_object_arc_ref WHERE eoar_arc_type = '$na_child|s|$na_parent';";
			
			// 11:34 27/10/2009 SCT : BZ 12024 => problème de valeur par défaut pour les éléments 3ème axe
			// 11:42 27/10/2009 SCT : BZ 6424 => Création de la topologie des stats Core (par correction du BZ 12024)
			// Mise à jour des na_child qui n'ont pas de na_parent
			$sql .= "
				INSERT INTO 
					edw_object_arc_ref 
						(eoar_id, eoar_id_parent,eoar_arc_type)
						SELECT DISTINCT 
							ON(eor_id) eor_id, 
							'$na_parent_value', 
							'$na_child|s|$na_parent' 
						FROM
							edw_object_ref
						WHERE 
							eor_obj_type = '$na_child'
							AND eor_id NOT IN 
							(
								SELECT 
									eor_id
								FROM
									edw_object_ref
								RIGHT JOIN 
									edw_object_arc_ref AS B 
								ON 
								(
									eor_id = B.eoar_id 
									AND eoar_arc_type = '$na_child|s|$na_parent'
								)
								WHERE eor_obj_type = '$na_child'
							)
			";
		}
		return $sql;
		
	}
	/**
	 * Fonction qui génère les requêtes spécifiques pour ajouter un NE en topo s'il n'existe pas
	 * FONCTION A METTRE DANS LE CB => CREATE_TEMP_TABLE_GENERIC.CLASS.PHP
	 * @param $na : niveau d'agrégation
	 * @param $na_value : valeur par défault
	 * @param $na_label_value : valeur par défault du label
	 * @param $day : jour calculé
	 * @return $sql : Requête qui met à jour les niveaux d'agrégation avec les valeurs par défault voulues
	 * BZ 6424 => mise en place d'une valeur par défaut
	 */
	function createSpecificQueryAddingNonExistingNE($na, $na_value, $na_label_value, $day)
	{
		global $database_connection; 
		
		$sql = "";
		// On vérifie que le na n'existe pas en base
		$query = "
			SELECT 
				eor_id,
				eor_obj_type 
			FROM 
				edw_object_ref 
			WHERE 
				eor_obj_type = '$na' 
				AND eor_id IS NOT NULL 
			UNION 
			SELECT 
				eo_id,
				eo_obj_type 
			FROM 
				edw_object 
			WHERE 
				eo_obj_type = '$na' 
				AND eo_id IS NOT NULL 
			LIMIT 1;";
				
		$res_1 = pg_query($database_connection,$query);

		
		$sql = '';
		// Dans le cas où le NA n'existe pas, on l'ajoute dans la table edw_object_ref
		// Insertion du na dans la table edw_object
		if(pg_numrows($res_1) == 0)
			$sql .= "
				INSERT INTO 
					edw_object_ref 
						(eor_date, eor_obj_type, eor_id, eor_label)
				VALUES
						('$day', '$na', '$na_value', '$na_label_value');
			";
		
		__debug($sql,"sql");
			
		return $sql;
		
	}

}

?>
