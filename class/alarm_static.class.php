<?php
/*
	17/07/2009 GHX
		- Prise en compte du débug
		- Modif dans la fonction put_in_join_table()
	31/08/2009 GHX
		- Correction du BZ 11312 [CB 5.0][ALARM CALCULATION] : pas de résultats d'alarmes sur les familles troisieme : erreur SQL
        04/08/2010 MPR
                - Correction du BZ 17077 La sélection d'éléments réseau entraine une erreur SQL sur une famille 3ème axe
 *	25/11/2011 ACS BZ 24792 Virtual SAI of corporate are visibles in multiple GUI

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
	- maj 18/03/2008, benoit : ajout de la condition "on_off=1" (alarme activée) dans la requete de sélection des alarmes à calculer
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
*	- maj 24/05/2007 Gwénaël :  ajout d'une condition pour ne pas prendre en compte les cellules qui commencent pas virtual
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
	Classe alarm_static
	Permet de gérer le calcul des alarmes de type alertes statiques
	- maj 02/01/2007 - maxime : On limite la sélection des na value minimum d'une famille aux éléments réseau actifs (on_off=1)
	- maj 07/04/2006 et 08/04/2006 : modification pour la prise en charge du seuil limite du nombre de résultats.
	- maj 20/04/2006  ajout ligne 113
	- maj DELTA christophe 26 04 2006. cf MODIF DELTA NOUVEAU(ajout)   MODIF DELTA(mise en commentaires des modifications)
	- maj 04-08-2007 : intégration des seuils, modification du nom des tables de définition

	- maj 05/04/2007 Gwénaël : suppression de code (en commentaire) concernant le home_network (cf $this->flag_axe3)
	- maj 18/04/2007 Gwénaël : modification par rapport au troisième sur les éléments blaclisted
*/
class alarm_static extends alarm {

	/** 	Constructeur de la classe alarm_static
	*	
	*	@param object $db : objet DataBaseConnection passé à la classe
	*	@param int	$offset_day : l'offset day définit le jour pour lequel on effectue le calcul.
	*					ex: 	si nous sommes le 25/02/2009, et que l'on veut calculer les alarmes
	*						pour le 18/02/2009, alors offset_day=7
	*/
	function alarm_static($db, $offset_day)
	{
		$this->db = $db;
		$this->offset_day = $offset_day;
		$this->type_alarm = 'static';
		parent::alarm();
		$this->debug = get_sys_debug('alarm_calculation');;
		$this->clean_removed_alarm();
		$this->alarm_launcher();
		// $this->db->displayQueries();
	}


	/**
		Récupère les propriétés de l'alarme (nom, paramètres, trigger, niveau critique...) pour cela, la condition est que
		le champ 'alarm_trigger_data_field' soit NON NULL
		On ne récupère pas les champs additionnels pour lesquels le champ 'alarm_trigger_data_field' est NULL justement.
		@param $group_id	: identifiant du group_table
		@param $network	: network aggregation
		@param $time		: time aggregation
		@return tableau contenant toutes les propriétés de l'alarme.
				Le tableau principal contient comme clé l'identifiant de l'alarme
				Il contient 5 sous tableau
					["alarm_name] : nom de l'alarme
					["home_net"] : la valeur du 3eme axe
					["net_field"] : champ du 3eme axe (ex:network pour roaming)
					["criticity"] : contient des sous-tableau pour chaque criticité trouvée qui précise les triggers utilisés
					["additional_field"] qui contient les champs additionnel de l'alarme
	*/
	// maj 28/06/2007 mp on ajoute en paramètre la liste des alarmes exclues
	function get_alarm_properties($group_id, $network, $time,$lst_alarm_excluded,$ta_value)
	{
		$where="";

		if (count($lst_alarm_excluded[$time][$ta_value])>0)
			$where = "AND alarm_id NOT IN ('".implode("','",$lst_alarm_excluded[$time][$ta_value])."')";

		$query = " --- on va chercher les proprietes des alarmes
			SELECT
				alarm_id,
				alarm_name,
				alarm_trigger_data_field,
				alarm_trigger_operand,
				alarm_trigger_value,
				alarm_trigger_type,
				hn_value,
				critical_level,
				additional_field,
				additional_field_type
			FROM sys_definition_alarm_static
			WHERE	id_group_table='$group_id'
				AND	network='$network'
				AND	time='$time'
				$where
				AND on_off = 1
			ORDER BY alarm_id";
		$res = $this->db->getall($query);
		
		if ($res) {
			foreach ($res as $row) {
				// Generalité sur 1 alarme qui est indépendant du seuil
				$properties[$row["alarm_id"]]["alarm_name"]	= $row["alarm_name"];
				$properties[$row["alarm_id"]]["home_net"]	= trim($row["hn_value"]); //le home network est toujour le même car il est propre à une alarme
				$gt_axe_information = get_axe3_information($group_id); // recupere les information de l'axe pour l'id_group_table. Utile en cas de 3eme axe
				$properties[$row["alarm_id"]]["net_field"]	= $gt_axe_information["axe_index"];
				// propriétés des alarmes
				if (trim($row["alarm_trigger_data_field"]) != "") { // si la valeur n'est pas vide, on est dans le cas d'un trigger, sinon dans le cas d'un champ additionnel
					$properties[$row["alarm_id"]]["criticity"][$row["critical_level"]]["data_type"][]	= trim($row["alarm_trigger_type"]);
					$properties[$row["alarm_id"]]["criticity"][$row["critical_level"]]["data_field"][]	= trim($row["alarm_trigger_data_field"]);
					$properties[$row["alarm_id"]]["criticity"][$row["critical_level"]]["operand"][]		= trim($row["alarm_trigger_operand"]);
					$properties[$row["alarm_id"]]["criticity"][$row["critical_level"]]["value"][]		= trim($row["alarm_trigger_value"]);
				} else { // on est dans le cas de champs additionnels
					$properties[$row["alarm_id"]]['additionnal_field']["data_type"][] = trim($row["additional_field_type"]);
					$properties[$row["alarm_id"]]['additionnal_field']["data_field"][] = trim($row["additional_field"]);
				}
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
		$debug = false;
		if ($debug)	// display function call
			ECHO "<div class='debug'><div class='function_call'>get_requetes(
					alarm_properties=<strong>$alarm_properties</strong>,
					id_group_table=<strong>$id_group_table</strong>,
					network_aggregation=<strong>$network_aggregation</strong>,
					time_aggregation=<strong>$time_aggregation</strong>,
					time_value=<strong>$time_value</strong>)</div>";
		
		$group_name	= get_group_name($id_group_table);
		$table_raw	= $group_name . "_raw_" . $network_aggregation . "_" . $time_aggregation;
		$table_kpi	= $group_name . "_kpi_" . $network_aggregation . "_" . $time_aggregation;
		$raw_and_kpi	= false; // permet de savoir si l'alarme possède à la fois des kpi et des raw.

		// parcoure la liste de toutes les alarmes
		// //__debug($alarm_properties,"proprietes alarm");
		foreach ($alarm_properties as $alarm_id => $alarm_sub_properties) {
			
			if ($debug) {	// display $alarm_sub_properties
				ECHO "<table><tr><th colspan='2'>content of \$alarm_sub_properties for \$alarm_id=<strong>$alarm_id</strong></th></tr>";
				foreach ($alarm_sub_properties as $k => $v) ECHO "\n	<tr><td>$k</td><td>$v</td></tr>";
				ECHO "</table>";
			}
			
			$alarm_name		= $alarm_sub_properties["alarm_name"];
			// elements généraux à l'alarme qui servent pour le 3eme axe uniquement
			$home_net		= $alarm_sub_properties["home_net"];
			$home_net_field	= $alarm_sub_properties["net_field"];
			// parcoure la liste de chaque alarme par niveau de criticité pour la partie trigger uniquement
			// si une alarme possède 3 niveau de criticité le système va considérer que ce sont 3 alarmes différentes
			foreach ($alarm_sub_properties["criticity"] as $criticity_level => $alarm_criticity) {
				unset($select, $from, $where);
				$acknowledgment = 0; //on initialise la variable à 0 qui signifie que l'alarme n'a pas été acquitté (l'acquittement est traité dans la fenêtre de display des alarmes)
				// Cas particulier du compute mode daily ou on doit calculer pour les heures, toutes les heures d'un seul coup et non pas heure par heure.
				$compute_mode = get_sys_global_parameters("compute_mode");
				$time_value_select = $time_value;
				if ($compute_mode == "daily" && $time_aggregation == "hour") {
					$time_value_select = "t0.hour";
				}
				// Gestion du SELECT
				$select = " --- requete d'insertion des données dans edw_alarm ($network_aggregation, $time_aggregation, $time_value)
					SELECT
						'$alarm_id',
						'$criticity_level',
						'$acknowledgment',
						'$time_aggregation',
						$time_value_select, ";
				if (strpos($network_aggregation,'_')) {
					// on a un axe3
					list($na_axe1,$na_axe3) = explode('_',$network_aggregation,2);
					$select .= "
						'$na_axe1',
						t0.$na_axe1,
						'$na_axe3',
						t0.$na_axe3,
					";
				} else {
					// on a PAS d'axe3
					$na_axe1 = $network_aggregation;
					$na_axe3 = '';
					$select .= "
						'$na_axe1',
						t0.$na_axe1,
					";
				}
				$select .= "
						'$this->type_alarm'
					" ;
				// gestion du FROM et du WHERE en fonction des différents type de trigger utilisés
				// teste si on a 1 seul type de trigger (raw ou KPI) ou les 2 types auquels cal il faut faire des jointures entre les tables raw et KPI
				if (in_array("raw", $alarm_criticity["data_type"]) && in_array("kpi", $alarm_criticity["data_type"])) {
					$raw_and_kpi = true;
					$from = " FROM $table_raw t0, $table_kpi t1 ";
					$where[] = "t0.$na_axe1 = t1.$na_axe1";
					$where[] = "t0.$time_aggregation = t1.$time_aggregation";

				} else {
					$raw_and_kpi = false; // MODIF DELTA AJOUT (venant de la version 1.1.9.0)
					$type = trim($alarm_criticity["data_type"][0]); //si tous les triggers sont du même type, il suffit de prendre le premier pour avoir le type de tous les autres
					if ($type == "raw") {
						$from = " FROM $table_raw t0 ";
					} else {
						$from = " FROM $table_kpi t0 ";
					}
				}
				// //__debug(getTaQueryForCompute($time_aggregation, $time_value),"get ta query for compute");
				$where[] = " t0.$time_aggregation = '$time_value' ";
				$where[] = " t0.$na_axe1 IS NOT NULL ";
				// gestion des trigger qui ajoute des conditions à la requete
				// on n'utilise la table t1 (qui correspond à la table des KPI) que si on a des triggers raw et KPI.dans tous les autres cas, la table utilisé s'appelle t0
				for ($r = 0;$r < count($alarm_criticity["data_field"]);$r++) {
					if ($alarm_criticity["data_type"][$r] == "kpi" && $raw_and_kpi == true) {
						$where[] = "t1." . $alarm_criticity["data_field"][$r] . $alarm_criticity["operand"][$r] . $alarm_criticity["value"][$r];
					} else {
						$where[] = "t0." . $alarm_criticity["data_field"][$r] . $alarm_criticity["operand"][$r] . $alarm_criticity["value"][$r];
					}
				}

				//Gestion des elements blacklistés
				// modif 18/04/2007 Gwénaël
				// modification par rapport au troisième axe dans le cas où il y en a un le network agrégation est na_naAxe3
				// or le tableau $this->network_aggregation_min contient uniquement des na sans troisième axe
				// donc on récupère uniquement la première valeur de l'explode
				$_na = explode('_', $network_aggregation);
				$na = $_na[0];
				if ($na==$this->network_aggregation_min[$this->family]) {
					$where[]=" t0.$na_axe1 NOT IN (SELECT eor_id FROM edw_object_ref WHERE eor_obj_type='$na_axe1' AND eor_blacklisted=1)";
					//-maj 02/01/2007 - maxime : On limite la sélection des na value minimum d'une famille aux éléments réseau actifs (on_off=1)
					$where[]=" t0.$na_axe1 NOT IN (SELECT eor_id FROM edw_object_ref WHERE eor_obj_type='$na_axe1' AND eor_on_off=0) ";
					// modif 24/05/2007 Gwénaël
					// 25/11/2011 ACS BZ 24792 Virtual SAI of corporate are visibles in multiple GUI
					// il ne faut pas intégrer les cellules qui commencent par virtual, elles servent seulement pour le calcule des compteurs qui sont bypass
					$where[] = NeModel::whereClauseWithoutVirtual('t0', $na_axe1);
				}
				// maj 10/07/07 Maxime -> On inclue la sélection de éléements réseaux de l'alarme enregistrée en base si celle-ci est différente de 'all'
				// 04/08/2010 MPR : Correction du BZ 17077 La sélection d'éléments réseau entraine une erreur SQL sur une famille 3ème axe
				$condition = $this->get_selection_network_elements($alarm_id,'alarm_static',$na_axe1);

				if ($condition!="")
					$where[] = $condition;
				// Gestion de la query d'insertion des résultats
				$query = " --- on insert les resultats dans edw_alarm
					INSERT INTO edw_alarm
						(id_alarm,critical_level,acknowledgement,ta,ta_value,na,na_value,";
				
					// on teste si on a un axe3
					if (strpos($network_aggregation,'_'))
						$query .= 'a3,a3_value,';

				$query .= "alarm_type)
					$select
					$from
					WHERE ". implode("\n\t\t AND ", $where) ."
					ORDER BY " . str_replace('_',', ',$network_aggregation);
				// __debug($query,"QQQ");

				$tab_requete[$alarm_id]["query"][$criticity_level] = $query;
				$tab_requete[$alarm_id]["alarm_name"]= $alarm_name;
			}
		}
		if ($debug)	ECHO "</div>";
		return $tab_requete;
	}

	/**
		Construit et exécute la requête d'insertion des données dans la table edw_alarm_detail.
		$alarm_properties : 		tableau venant de la fonction get_properties
		$id_group_table : 		identifiant du group table.
		$na : 				network aggregation.
	*/
	function put_in_join_table($na, $alarm_properties, $id_group_table,$critical_level,$alarm_id)
	{
		
		for ($a = 0;$a < count($this->result_ids_inserted[$critical_level][$alarm_id]);$a++) {

			// 15:22 31/08/2009 GHX
			// Correction du BZ 11312
			$_na = explode('_', $na);
			$query1 = " --- put_in_join_table() >> query 1
				SELECT na_value, ta_value,ta, a3_value, a3
				FROM edw_alarm
				WHERE critical_level='$critical_level'
					AND na = '".$_na[0]."'
					".(count($_na) == 2 ? " AND a3 = '".$_na[1]."'" : "")."
					AND id_result=" . $this->result_ids_inserted[$critical_level][$alarm_id][$a];
			$res1 = $this->db->getall($query1);
			////__debug($res1,"queryl");
			if ($res1) {
				foreach ($res1 as $row1) {
					$na_value		= $row1["na_value"];
					$time_value	= $row1["ta_value"];
					$time_to_sel	= $row1["ta"];
					// 15:24 31/08/2009 GHX
					// Renomage des 2 variables
					$a3	= $row1["a3"];
					$a3_value		= $row1["a3_value"];
				}
			}

			// gestion de la partie 'Where' de la query
			// 15:23 31/08/2009 GHX
			// Correction du BZ 11312
			// Ajout du if si on a un troisieme axe
			if ( count($_na) == 2 )
			{
				$query_where = " WHERE ".$_na[0]."='$na_value' AND $a3='$a3_value' AND $time_to_sel=$time_value "; //getTaQueryForCompute($time_to_sel, $time_value);//$time_to_sel=$time_value";
			}
			else
			{
				$query_where = " WHERE $na='$na_value' AND $time_to_sel=$time_value "; //getTaQueryForCompute($time_to_sel, $time_value);//$time_to_sel=$time_value";
			}
			$compute_mode = get_sys_global_parameters("compute_mode");
			$time_value_select = $time_value;
			if ($compute_mode == "daily" && $time_aggregation == "hour")
				$time_value_select = "hour";
			$alarm_properties_criticity = $alarm_properties[$alarm_id]["criticity"][$critical_level];
			
			// insertion des triggers
			for ($p = 0;$p < count($alarm_properties_criticity["data_type"]);$p++) {

				$group_name = get_group_name($id_group_table);
		
				$query = " --- insertion dans edw_alarm_detail
					INSERT INTO edw_alarm_detail (
						id_result,
						trigger,
						trigger_operand,
						trigger_value,
						value,
						field_type
					)
					SELECT
						'{$this->result_ids_inserted[$critical_level][$alarm_id][$a]}',
						'{$alarm_properties_criticity['data_field'][$p]}',
						'{$alarm_properties_criticity['operand'][$p]}',
						{$alarm_properties_criticity['value'][$p]},
						{$alarm_properties_criticity['data_field'][$p]},
						'trigger'
					";
				if ($alarm_properties_criticity["data_type"][$p] == "raw")
						$query .= " FROM {$group_name}_raw_{$na}_$time_to_sel";
				else		$query .= " FROM {$group_name}_kpi_{$na}_$time_to_sel";
				
				// 16:03 17/07/2009 GHX
				// Exécution de la requete dans le foreach au lieu de le faire après
				$query .= $query_where;
				$this->db->execute($query);
			}
			
			if ($this->debug) echo "&nbsp;&nbsp;&nbsp;&nbsp;<u>Insertion dans edw_alarm_detail</u> : " . $query . "<br>";

			// insertion des champs additionnels
			for ($p = 0;$p < count($alarm_properties[$alarm_id]['additionnal_field']["data_field"]);$p++) {
				$query = " --- insertion des champs additionels
					INSERT INTO edw_alarm_detail (id_result,trigger,value,field_type)
					SELECT '{$this->result_ids_inserted[$critical_level][$alarm_id][$a]}',
						'{$alarm_properties[$alarm_id]['additionnal_field']['data_field'][$p]}',
						{$alarm_properties[$alarm_id]['additionnal_field']['data_field'][$p]},
						'additional' ";
				if ($alarm_properties[$alarm_id]['additionnal_field']["data_type"][$p] == "raw")
						$query .= " FROM {$group_name}_raw_{$na}_$time_to_sel";
				else		$query .= " FROM {$group_name}_kpi_{$na}_$time_to_sel";
				
				$query .= $query_where;
				$this->db->execute($query);
				if ($this->debug) echo "&nbsp;&nbsp;&nbsp;&nbsp;<u>Insertion dans edw_alarm_detail</u> : " . $query . "<br>";
			}
		}
	}

}
?>
