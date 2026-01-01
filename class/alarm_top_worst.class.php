<?
/*
*
*	25/11/2011 ACS BZ 24792 Virtual SAI of corporate are visibles in multiple GUI
*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
	- maj 04/02/2008, maxime : correction du bug 5768 / En mode daily - day, chaque résultat est inséré 24 fois (nombres d'heures) au lieu        d'une seule fois. Le résultat doit être unique pour une heure et un élément réseau donné

	- maj 18/03/2008, benoit : ajout de la condition "on_off=1" (alarme activée) dans la requete de sélection des alarmes à calculer
	
	31/08/2009 GHX
		- Correction du BZ 11312 [CB 5.0][ALARM CALCULATION] : pas de résultats d'alarmes sur les familles troisieme : erreur SQL
	29/09/2009 GHX
		- Correction du BZ 11731 [CB 5.0][Top/Worst List][Calcul] doublons dans le détail des alarmes en hour
*/
?>
<?
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
*
*	-  maj 10/07/07 Maxime -> On inclue la sélection de éléements réseaux de l'alarme enregistrée en base si celle-ci est différente de 'all'
*/
?>
<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*
*	- maj 24/05/2007 Gwénaël : changement du nom d'une variable dans la condition du if au niveau de blacklisted, un oublie lors de la dernière modif ;-)
*					ajout d'une condition pour ne pas prendre en compte les cellules qui commencent pas virtual
*/
?>
<?
/*
*	@cb21000_gsm20010@
*
*	08/12/2006 - Copyright Acurio
*
*	Composant de base version cb_2.1.0.00
*
*	Parser version gsm_20010
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
	Classe cell_list
	Permt de gérer le calcul des alarmes de type list / Top Worst Cell list
    - maj 02/01/2007 - maxime : On limite la sélection des na value minimum d'une famille aux éléments réseau actifs (on_off=1)
    - maj 04-08-2007 : intégration d'un trigger en plus du compteur/KPI de tri, modification du nom des tables de définition, ajout des champs additionnels
	- maj 18/04/2007 Gwénaël : modification par rapport au troisième sur les éléments blaclisted
*/
class top_worst extends alarm {
	/**
		Constructeur
		@param	object	$db objet database connection
		@param	int		$offset_day
	*/
	function top_worst ($db, $offset_day)
	{
		$this->db = $db;
		$this->offset_day = $offset_day;
		$this->type_alarm = 'top-worst';
		// $this->limit =		??
		$this->debug = get_sys_debug('alarm_calculation');
		parent::alarm();
		$this->clean_removed_alarm();
		$this->alarm_launcher();
		// echo $this->db->displayQueries();
	}

	/**
		Récupère les propriétés de l'alarme (nom, paramètres, déclenchement...)
		$group_id : identifiant du group_table
		$network : 	network aggregation
		$time : 	time aggregation
	*/
	function get_alarm_properties($group_id, $network, $time,$lst_alarm_exluded,$ta_value)
	{
		$where = "";
		if (count($lst_alarm_exluded[$time][$ta_value])>0)
			$where = "AND alarm_id NOT IN ('".implode("','",$lst_alarm_exluded[$time][$ta_value])."')";

		// 18/03/2008 - Modif. benoit : ajout de la condition "on_off=1" (alarme activée) dans la requete de sélection des alarmes à calculer

		$query = " --- on va chercher les propriétés de l'alarme Top/Worst pour $group_id, $network, $time, $ta_value
			SELECT alarm_id ,
				alarm_name,
				list_sort_field,
				list_sort_asc_desc,
				list_sort_field_type,
				hn_value,
				additional_field,
				additional_field_type,
				alarm_trigger_data_field,
				alarm_trigger_type,
				alarm_trigger_operand,
				alarm_trigger_value
			FROM sys_definition_alarm_top_worst
			WHERE id_group_table='$group_id'
				AND network='$network'
				AND time='$time' $where
				AND on_off = 1
			ORDER BY alarm_id;";
		$res = $this->db->getall($query);
		while ($row = array_shift($res)) {
			if (trim($row["list_sort_field"]) != "") {
				$properties[$row["alarm_id"]]["alarm_name"]	= $row["alarm_name"];
				$properties[$row["alarm_id"]]["data_field"]	= $row["list_sort_field"];
				$properties[$row["alarm_id"]]["asc_desc"]	= $row["list_sort_asc_desc"];
				$properties[$row["alarm_id"]]["data_type"]	= $row["list_sort_field_type"];
				$properties[$row["alarm_id"]]["home_net"]	= $row["hn_value"];
				$properties[$row["alarm_id"]]["trigger_data_field"]		= $row["alarm_trigger_data_field"];
				$properties[$row["alarm_id"]]["trigger_data_type"]		= $row["alarm_trigger_type"];
				$properties[$row["alarm_id"]]["trigger_data_operand"]	= $row["alarm_trigger_operand"];
				$properties[$row["alarm_id"]]["trigger_data_value"]	= $row["alarm_trigger_value"];
				$gt_axe_information = get_axe3_information($group_id);
				$properties[$row["alarm_id"]]["net_field"]			= $gt_axe_information["axe_index"];
			} else {
				$properties[$row["alarm_id"]]["additional_field"][]		= $row["additional_field"];
				$properties[$row["alarm_id"]]["additional_field_type"][]	= $row["additional_field_type"];
			}
		}
		return $properties;
	}


	/**
		Construit et exécute la requête d'insertion des données dans la table edw_alarm.
		$alarm_properties : 		tableau venant de la fonction get_properties
		$id_group_table : 		identifiant du group table.
		$network_aggregation : 	network aggregation.
		$time_aggregation :		time aggregation.
		$time_value : 			time value.
	*/
	function get_requetes($alarm_properties, $id_group_table, $network_aggregation, $time_aggregation, $time_value)
	{
		$group_name	= get_group_name($id_group_table);
		$table_raw	= $group_name . "_raw_" . $network_aggregation . "_" . $time_aggregation;
		$table_kpi	= $group_name . "_kpi_" . $network_aggregation . "_" . $time_aggregation;
		
		if (strpos($network_aggregation,'_')) {
			// on a un axe3
			list($na_axe1,$na_axe3) = explode('_',$network_aggregation,2);
		} else {
			// on a PAS d'axe3
			$na_axe1 = $network_aggregation;
			$na_axe3 = '';
		}

		foreach($alarm_properties as $alarm_id => $alarm_sub_properties) {
			unset($select, $from, $where, $order_by);
			$alarm_name=$alarm_sub_properties["alarm_name"];
			// Cas particulier du compute mode daily.
			$compute_mode = get_sys_global_parameters("compute_mode");
			$time_value_select = $time_value;
			if ($compute_mode == "daily" && $time_aggregation == "hour")
				$time_value_select = "t0.hour";
			// Gestion de la partie Select
			if ($na_axe3)
				$select = "select  '$alarm_id','$time_aggregation',$time_value_select,'$na_axe1',t0.$na_axe1,'$na_axe3',t0.$na_axe3,'$this->type_alarm' ";
			else
				$select = "select  '$alarm_id','$time_aggregation',$time_value_select,'$na_axe1',t0.$na_axe1,'$this->type_alarm' ";

			// Gestion de la partie FROM et WHERE
			// teste si le trigger (s'il existe) et la data de tri sont du même type (raw ou KPI)
			if ($alarm_sub_properties["trigger_data_field"] != null && $alarm_sub_properties["trigger_data_type"] != $alarm_sub_properties["data_type"]) {
				$raw_and_kpi = true;
				$from = " FROM $table_raw t0, $table_kpi t1 ";
				$where[] = " t0.$na_axe1 = t1.$na_axe1 ";
				$where[] = " t0.$time_aggregation = t1.$time_aggregation ";

			} else {
				$raw_and_kpi = false; // MODIF DELTA AJOUT (venant de la version 1.1.9.0)
				$type = $alarm_sub_properties["data_type"];
				if ($type == "raw") {
					$from = " FROM $table_raw t0 ";
				} else {
					$from = " FROM $table_kpi t0 ";
				}
			}

			// maj 16:26 04/02/2008 maxime : correction du bug 5768 / En mode daily - day, chaque résultat est inséré 24 fois (nombres d'heures) au lieu d'une seule fois. 
			// Le résultat doit être unique pour une heure et un élément réseau donné
			$where[] = " t0.$time_aggregation = '$time_value' ";
			$where[] = " t0.$na_axe1 IS NOT NULL ";
			if ($alarm_sub_properties["data_type"] == "kpi" && $raw_and_kpi == true) {
				$where[] = " t1.{$alarm_sub_properties['data_field']} IS NOT NULL";
			} else {
				$where[] = " t0.{$alarm_sub_properties['data_field']} IS NOT NULL";
			}
			// Gestion du trigger s'il existe
			if ($alarm_sub_properties["trigger_data_field"] != null) {
				if ($alarm_sub_properties["trigger_data_type"] == "kpi" && $raw_and_kpi == true) {
					$where[] = "t1." . $alarm_sub_properties["trigger_data_field"] . $alarm_sub_properties["trigger_data_operand"] . $alarm_sub_properties["trigger_data_value"];
				} else {
					$where[] = "t0." . $alarm_sub_properties["trigger_data_field"] . $alarm_sub_properties["trigger_data_operand"] . $alarm_sub_properties["trigger_data_value"];
				}
			}

			//Gestion des éléments blacklistés
			// modif 18/04/2007 Gwénaël
				// modification par rapport au troisième axe dans le cas où il y en a un le network agrégation est na_naAxe3
				// or le tableau $this->network_aggregation_min contient uniquement des na sans troisième axe
				// donc on récupère uniquement la première valeur de l'explode
			// modif 24/05/2007 Gwénaël
				// changement de $network_aggregation par $na dans le if  (oublie ;-)
			if ($na_axe1==$this->network_aggregation_min[$this->family]) {
				$where[]= "t0.$na_axe1 NOT IN (SELECT eor_id FROM edw_object_ref WHERE eor_obj_type='$na_axe1' AND eor_blacklisted=1) ";
				// Maj 02/01/2007 - maxime : On limite la sélection des na value min d'une famille aux éléments actifs (on_off = 1)
				$where[]="t0.$na_axe1 NOT IN (SELECT eor_id FROM edw_object_ref WHERE eor_obj_type='$na_axe1' AND eor_on_off=0) ";
				// modif 24/05/2007 Gwénaël
				// 25/11/2011 ACS BZ 24792 Virtual SAI of corporate are visibles in multiple GUI
				// il ne faut pas intégrer les cellules qui commencent par virtual, elles servent seulement pour le calcule des compteurs qui sont bypass
				$where[] = NeModel::whereClauseWithoutVirtual('t0', $na_axe1);
			}

			// maj 10/07/07 Maxime -> On inclue la sélection de éléements réseaux de l'alarme enregistrée en base si celle-ci est différente de 'all'
			$condition = $this->get_selection_network_elements($alarm_id,'alarm_top_worst',$na_axe1);
			if($condition!="")
				$where[] = $condition;

			// gestion du order by
			$order_by = " ORDER BY " . $alarm_sub_properties["data_field"] . " " . $alarm_sub_properties["asc_desc"] . " limit ".$this->top_worst_limit;
			// generation de la requete complete
			$query = " --- Insertion dans edw_alarm $id_group_table, $network_aggregation, $time_aggregation, $time_value
				INSERT INTO edw_alarm (id_alarm,ta,ta_value,na,na_value,".($na_axe3 ? 'a3,a3_value,':'')."alarm_type)
				$select
				$from
				WHERE "
					.implode("\n		AND ", $where)
					."\n		".$order_by;
			$tab_requete[$alarm_id]["query"][$criticity_level] = $query;
			$tab_requete[$alarm_id]["alarm_name"]= $alarm_name;
		}
		return $tab_requete;
	}


	/**
		Construit et exécute la requête d'insertion des données dans la table edw_alarm_detail.
		$alarm_properties : 		tableau venant de la fonction get_properties
		$id_group_table : 		identifiant du group table.
		$na : 				network aggregation.
		* Le seuil n'est pas utilisé pour les top/worst mais c'est la même fonction qui est appelée pour les 3 types d'alarmes
	*/
	function put_in_join_table($na, $alarm_properties, $id_group_table,$seuil,$alarm_id)
	{
		if ($this->debug) {
			echo "<div class='debug'><div class='function_call'>put_in_join_table(na=<strong>$na</strong>, alarm_properties=<strong>$alarm_properties</strong>, id_group_table=<strong>$id_group_table</strong>, seuil=<strong>$seuil</strong>, alarm_id=<strong>$alarm_id</strong>)</div>";
			print_r($this->result_ids_inserted[$alarm_id]);
			echo "</div>";
		}

		if (strpos($na,'_')) {
			// on a un axe3
			list($na_axe1,$na_axe3) = explode('_',$na,2);
		} else {
			// on a PAS d'axe3
			$na_axe1 = $na;
			$na_axe3 = '';
		}
		
		for ($a = 0;$a < count($this->result_ids_inserted[$alarm_id]);$a++) {
			$query1 = " --- on va retrouver les resultats dans edw_alarm
				SELECT na_value, ta_value,ta, a3_value, a3
				FROM edw_alarm
				WHERE na = '$na_axe1'
					AND id_result=" . $this->result_ids_inserted[$alarm_id][$a];
			$res1 = $this->db->getall($query1);
			while ($row1 = array_shift($res1)) {
				$na_value		= $row1["na_value"];
				$time_value	= $row1["ta_value"];
				$time_to_sel	= $row1["ta"];
				$hn_value		= $row1["a3_value"];
				$hn_to_sel	= $row1["a3"];
			}
			// gestion de la partie 'Where' de la query
			$query_where = "
				WHERE $na_axe1='$na_value'
					AND $time_to_sel = $time_value "; //getTaQueryForCompute($time_to_sel, $time_value);//$time_to_sel=$time_value";
			// 16:11 31/08/2009 GHX
			// Correction du BZ 11312
			// Ajout de la condition si on a un troisieme axe
			if ( !empty($na_axe3) )
			{
				$query_where .= " AND $na_axe3='$hn_value'";
			}
			$alarm_sub_properties	= $alarm_properties["$alarm_id"];
			$group_name			= get_group_name($id_group_table);
			$compute_mode		= get_sys_global_parameters("compute_mode");
			$time_value_select		= $time_value;
			if ($compute_mode == "daily" && $time_aggregation == "hour")
				$time_value_select = "hour";

			// insertion du raw/kpi de tri
			$query = " --- on insert les details dans edw_alarm_detail pour $na, $id_group_table, $alarm_id
				INSERT INTO edw_alarm_detail (id_result,trigger,value,field_type)
				SELECT '" . $this->result_ids_inserted[$alarm_id][$a] . "','" . $alarm_sub_properties["data_field"] . "'," . $alarm_sub_properties["data_field"] . ",'trigger'";
			if ($alarm_sub_properties["data_type"] == "raw") {
				$query .= " FROM {$group_name}_raw_{$na}_$time_to_sel ";
			} else {
				$query .= " FROM {$group_name}_kpi_{$na}_$time_to_sel ";
			}
			$query .= $query_where;
			$this->db->execute($query);
			if ($this->debug) echo "&nbsp;&nbsp;&nbsp;&nbsp;<u>Insertion dans edw_alarm_detail</u> : " . $query . "<br>";

			// insertion du trigger (s'il existe) dans la table  edw_alarm_detail
			if ($alarm_sub_properties["trigger_data_field"] != null) {
				$query = "
					INSERT INTO edw_alarm_detail (id_result,trigger,trigger_operand,trigger_value,value,field_type)
					SELECT '" . $this->result_ids_inserted[$alarm_id][$a] . "','" . $alarm_sub_properties["trigger_data_field"] . "','" . $alarm_sub_properties["trigger_data_operand"] . "','" . $alarm_sub_properties["trigger_data_value"] . "'," . $alarm_sub_properties["trigger_data_field"] . ",'trigger'";
				if ($alarm_sub_properties["trigger_data_type"] == "raw") {
					$query .= " FROM {$group_name}_raw_{$na}_$time_to_sel ";
				} else {
					$query .= " FROM {$group_name}_kpi_{$na}_$time_to_sel ";
				}
				$query .= $query_where;
				$this->db->execute($query);
				if ($this->debug) echo "&nbsp;&nbsp;&nbsp;&nbsp;<u>Insertion dans edw_alarm_detail</u> : " . $query . "<br>";
			}

			// insertion des champs additionnels
			for ($p = 0;$p < count($alarm_sub_properties['additional_field']);$p++) {
				$query = "
					INSERT INTO edw_alarm_detail (id_result,trigger,value,field_type)
					SELECT '" . $this->result_ids_inserted[$alarm_id][$a] . "','" . $alarm_sub_properties['additional_field'][$p] . "'," . $alarm_sub_properties['additional_field'][$p] . ",'additional'";
				if ($alarm_sub_properties['additional_field_type'][$p] == "raw") {
					$query .= " FROM {$group_name}_raw_{$na}_$time_to_sel ";
				} else {
					$query .= " FROM {$group_name}_kpi_{$na}_$time_to_sel ";
				}
				$query .= $query_where;
				$this->db->execute($query);
				if ($this->debug) echo "&nbsp;&nbsp;&nbsp;&nbsp;<u>Insertion dans edw_alarm_detail</u> : $query <br>";
			}
		}
		
		// 16:08 29/09/2009 GHX
		// Correction du BZ 11731
		// On purge le tableau pour éviter d'exécuter plusieurs fois les mêmes requetes
		$this->result_ids_inserted = array();
	}
}

