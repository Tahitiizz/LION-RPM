<?
/*

 *	25/11/2011 ACS BZ 24792 Virtual SAI of corporate are visibles in multiple GUI


*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
	- maj 29/11/2007, maxime : nouveau mode de calcul (ref: L290)

	- maj 07/03/2008, benoit : mise en commentaires des appels à la fonction '__debug()'

	- maj 18/03/2008, benoit : ajout de la condition "on_off=1" (alarme activée) dans la requete de sélection des alarmes à calculer
	
	26/08/2009 GHX
		- Correction d'un bug si on sélectionne une liste d'élément réseaux sur lesquelles on fait le calcule d'alarme
	31/08/2009 GHX
		- Correction du BZ 11312 [CB 5.0][ALARM CALCULATION] : pas de résultats d'alarmes sur les familles troisieme : erreur SQL
        04/08/2010 MPR
                - Correction du BZ 17077 La sélection d'éléments réseau entraine une erreur SQL sur une famille 3ème axe

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
*	- maj 24/05/2007 Gwénaël : ajout d'une condition pour ne pas prendre en compte les cellules qui commencent pas virtual
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
*	@cb20100_iu2030@
*
*	24/10/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.1.00
*
*	Parser version iu_2.0.3.0
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
*	@cb1300p_gb100b_060706@
*
*	06/07/2006 - Copyright Acurio
*
*	Composant de base version cb_1.3.0.0p
*
*	Parser version gb_1.0.0b
*/
?>
<?php
/*
*	16/06/2006 - Copyright Acurio
*
*	Composant de base version cb_1.2.0.2p
*
*	Parser version gb_1.0.0b
*/
?>
<?php
/*
	-maj 02/01/2007 - maxime : On limite la sélection des na value minimum d'une famille aux éléments réseau actifs (on_off=1)
	- maj 08/04/2006 : modification pour la prise en charge
		du seuil limite du nombre de résultats.
	- maj DELTA christophe 25 04 2006. cf MODIF DELTA NOUVEAU(ajout)   MODIF DELTA(mise en commentaires des modifications)
	- maj 04-08-2007 : intégration d'un trigger en plus du compteur/KPI qui sert de trigger 'dynamique', modification du nom des tables de définition,
	- 23-11-2006 : suppression des arrondis (round) dans le calcul des éléments additionnels. C'est le display qui se charge de tronquer les valeurs si nécessaire
	- maj 18/04/2007 Gwénaël : modification par rapport au troisième sur les éléments blaclisted
*/
class dyn_alarm extends alarm {
	/**
	*	Constructeur
	*
	*	@param object  $db est l'objet database connection.
	*	@param int	$offset_day
	*/
	function dyn_alarm($db, $offset_day)
	{
		$this->db = $db;
		$this->offset_day = $offset_day;
		$this->type_alarm = 'dyn_alarm';
		$this->debug = get_sys_debug('alarm_calculation');
		parent::alarm();
		$this->clean_removed_alarm();
		$this->alarm_launcher();
		// echo $this->db->displayQueries();
	}

	
	/**
		Récupère les propriétés de l'alarme (nom, paramètres, déclenchement...)
		$group_id	: identifiant du group_table
		$network	: network aggregation
		$time	: time aggregation
	*/
	function get_alarm_properties($group_id, $network, $time,$lst_alarm,$ta_value)
	{
		// 18/03/2008 - Modif. benoit : ajout de la condition "on_off=1" (alarme activée) dans la requete de sélection des alarmes à calculer  
		$query = "
			SELECT
				alarm_id,
				alarm_name,
				alarm_field,
				alarm_field_type,
				alarm_threshold,
				additional_field,
				additional_field_type,
				hn_value,
				alarm_time_frame,
				discontinuous,
				alarm_trigger_data_field,
				alarm_trigger_type,
				alarm_trigger_operand,
				alarm_trigger_value,
				critical_level
			FROM sys_definition_alarm_dynamic
			WHERE id_group_table='$group_id'
				AND	network='$network'
				AND	time='$time'
				AND	on_off = 1
			ORDER BY alarm_id";

		$res = $this->db->getall($query);
		$this->alarm_dyn_excluded = $this->get_alarm_dyn_excluded($time,$ta_value);
		
		if ($res) {
			foreach ($res as $row) {
				// Generalité sur 1 alarme qui est indépendant du seuil
				// En mode continu on conserve l'ensemble des alarmes à calculer // On exclut les plages temporelles uniquement lors de leurs sélections dans la fonction get_dyn_alarm_times()
				if (trim($row["discontinuous"])==0) {
					$properties[$row["alarm_id"]]["alarm_name"]		= $row["alarm_name"];
					$properties[$row["alarm_id"]]["home_net"]		= trim($row["hn_value"]); //le home network est toujour le même car il est propre à une alarme
					$properties[$row["alarm_id"]]["discontinuous"]	= trim($row["discontinuous"]);
					$properties[$row["alarm_id"]]["alarm_time_frame"]	= trim($row["alarm_time_frame"]);
					$gt_axe_information = get_axe3_information($group_id); // recupere les information de l'axe pour l'id_group_table. Utile en cas de 3eme axe
					$properties[$row["alarm_id"]]["net_field"]		= $gt_axe_information["axe_index"];
	
					if (trim($row["alarm_field"]) != "") { // si la valeur n'est pas vide, on est dans le cas d'un trigger, sinon dans le cas d'un champ additionnel
						$properties[$row["alarm_id"]]["criticity"][$row["critical_level"]]["data_type"]		= trim($row["alarm_field_type"]);
						$properties[$row["alarm_id"]]["criticity"][$row["critical_level"]]["data_field"]		= trim($row["alarm_field"]);
						$properties[$row["alarm_id"]]["criticity"][$row["critical_level"]]["data_value"]	= trim($row["alarm_threshold"]);
						// le trigger étant optionnel, il n'existe pas forcément
						$properties[$row["alarm_id"]]["criticity"][$row["critical_level"]]["trigger_data_type"]		= trim($row["alarm_trigger_type"]);
						$properties[$row["alarm_id"]]["criticity"][$row["critical_level"]]["trigger_data_field"]		= trim($row["alarm_trigger_data_field"]);
						$properties[$row["alarm_id"]]["criticity"][$row["critical_level"]]["trigger_data_value"]		= trim($row["alarm_trigger_value"]);
						$properties[$row["alarm_id"]]["criticity"][$row["critical_level"]]["trigger_data_operand"]	= trim($row["alarm_trigger_operand"]);
					} else { // on est dans le cas de champs additionnels
						$properties[$row["alarm_id"]]['additionnal_field']["data_type"][] = trim($row["additional_field_type"]);
						$properties[$row["alarm_id"]]['additionnal_field']["data_field"][] = trim($row["additional_field"]);
					}
				} else { // En mode discontinu on exclut les alarmes directement
					if (!@in_array($row["alarm_id"],$this->alarm_dyn_excluded)) {
						$properties[$row["alarm_id"]]["alarm_name"]		= $row["alarm_name"];
						$properties[$row["alarm_id"]]["home_net"]		= trim($row["hn_value"]); //le home network est toujour le même car il est propre à une alarme
						//__debug(trim($row["discontinuous"]),"discontinuous");
						$properties[$row["alarm_id"]]["discontinuous"]	= trim($row["discontinuous"]);
						$properties[$row["alarm_id"]]["alarm_time_frame"]	= trim($row["alarm_time_frame"]);
						$gt_axe_information = get_axe3_information($group_id); // recupere les information de l'axe pour l'id_group_table. Utile en cas de 3eme axe
						$properties[$row["alarm_id"]]["net_field"]		= $gt_axe_information["axe_index"];
	
						if (trim($row["alarm_field"]) != "") { // si la valeur n'est pas vide, on est dans le cas d'un trigger, sinon dans le cas d'un champ additionnel
							$properties[$row["alarm_id"]]["criticity"][$row["critical_level"]]["data_type"]		= trim($row["alarm_field_type"]);
							$properties[$row["alarm_id"]]["criticity"][$row["critical_level"]]["data_field"]		= trim($row["alarm_field"]);
							$properties[$row["alarm_id"]]["criticity"][$row["critical_level"]]["data_value"]	= trim($row["alarm_threshold"]);
							// le trigger étant optionnel, il n'existe pas forcément
							$properties[$row["alarm_id"]]["criticity"][$row["critical_level"]]["trigger_data_type"]		= trim($row["alarm_trigger_type"]);
							$properties[$row["alarm_id"]]["criticity"][$row["critical_level"]]["trigger_data_field"]		= trim($row["alarm_trigger_data_field"]);
							$properties[$row["alarm_id"]]["criticity"][$row["critical_level"]]["trigger_data_value"]		= trim($row["alarm_trigger_value"]);
							$properties[$row["alarm_id"]]["criticity"][$row["critical_level"]]["trigger_data_operand"]	= trim($row["alarm_trigger_operand"]);
						} else { // on est dans le cas de champs additionnels
							$properties[$row["alarm_id"]]['additionnal_field']["data_type"][] = trim($row["additional_field_type"]);
							$properties[$row["alarm_id"]]['additionnal_field']["data_field"][] = trim($row["additional_field"]);
						}
					}
				}
			}
		}
		return $properties;
	}


	/* On recherche directement les alarmes  à exclure si l'alarme est en mode discontinu */
	function get_alarm_dyn_excluded($ta,$ta_value)
	{
		// On récupère les alarmes qui ne devront pas être calculée en mode hour
		$condition = "";
		if ( $ta == 'hour' ) {
			$day =  $this->get_num_day_of_week( substr($ta_value,0,8) );
			$hour = substr($ta_value,8,2);
			$condition = "and ta_value='$hour' and ta = '$ta' and id_parent IN ( SELECT id FROM sys_definition_alarm_exclusion WHERE ta = 'day' and ta_value = '$day' and type_alarm='alarm_dynamic' )";
		} else {
			//  On récupère les alarmes qui ne devront pas être calculée en mode day
			$condition = "and ta_value='".$this->get_num_day_of_week($ta_value)."'";
		}
		$query = "
			SELECT distinct id_alarm
			FROM sys_definition_alarm_exclusion
			WHERE ta = '$ta'
				$condition
				AND type_alarm='alarm_dynamic' ";
		// __debug($query,"query excluded dynamic");
		$res = $this->db->getall($query);
		while ($row = array_shift($res))
			if ($row["id_alarm"]!=NULL)
				$alarm_to_exclude[] = $row["id_alarm"];

		return $alarm_to_exclude;
	}

	/**
                Construit et exécute la requête d'insertion des données dans la table edw_alarm.
                $alarm_properties :         tableau venant de la fonction get_properties
                $id_group_table :                 identifiant du group table.
                $network_aggregation :         network aggregation.
                $time_aggregation :        time aggregation.
                $time_value :                 time value.
         */
	function get_requetes($alarm_properties, $id_group_table, $network_aggregation, $time_aggregation, $time_value)
	{
		// on construit le nom des tables dans laquelles on va aller chercher les données
		$group_name = get_group_name($id_group_table);
		$table_raw = $group_name . "_raw_" . $network_aggregation . "_" . $time_aggregation;
		$table_kpi = $group_name . "_kpi_" . $network_aggregation . "_" . $time_aggregation;
		
		if (strpos($network_aggregation,'_')) {
			// on a un axe3
			list($na_axe1,$na_axe3) = explode('_',$network_aggregation,2);
		} else {
			// on a PAS d'axe3
			$na_axe1 = $network_aggregation;
			$na_axe3 = '';
		}
		
		// on boucle sur chaque alarme
		//__debug($alarm_properties,"count alarm propri");
		foreach ($alarm_properties as $alarm_id => $alarm_sub_properties) {
			$alarm_name			= $alarm_sub_properties["alarm_name"];
			$home_network		= $alarm_sub_properties["net_field"]; //champ correspodant au home_network
			$home_network_value	= $alarm_sub_properties["home_net"];
			$discontinuous			= $alarm_sub_properties["discontinuous"];
			$time_frame_continu	= "";
			if (!$discontinuous)
				$time_frame_continu = 500;
			$time_frame			= $alarm_sub_properties["alarm_time_frame"];
			// parcoure la liste de chaque alarme par niveau de criticité pour la partie trigger uniquement
			// si une alarme possède 3 niveau de criticité le système va considérer que ce sont 3 alarmes différentes
			//__debug($alarm_sub_properties["criticity"],"count criticty");
			
			foreach ($alarm_sub_properties["criticity"] as $criticity_level => $alarm_criticity) {
				$condition_field		= $alarm_criticity["data_field"]; //compteur ou KPI qui sert de condition pour l'écart type
				$condition_field_type	= $alarm_criticity["data_type"];
				$nbr_stddev			= $alarm_criticity["data_value"];
				$trigger_field			= $alarm_criticity["trigger_data_field"];
				$trigger_field_type		= $alarm_criticity["trigger_data_type"];
				$trigger_operand		= $alarm_criticity["trigger_data_operand"];
				$trigger_value			= $alarm_criticity["trigger_data_value"];
				// $id_alarm = $tab["alarm_id"];
				// $hn_to_sel = $tab["hn_to_sel"];
				// détermine les tables sources qui vont servir au calcul de l'alarme.
				$offset_day			= $this->offset_day;
				// En fonction de la valeur de time selection, on affecte des valeur différente pour les requêtes (syntaxe SQL) :
				// Exemples :
				// day :                 select to_char('20050813'::date - '5 days'::interval,'YYYYMMDD')        >>>        affiche "20050808"
				// week :         select to_char('20050813'::date - '5 weeks'::interval,'YYYYWW')        >>>         affiche "200528"
				// month :         select to_char('20050813'::date - '5 months'::interval,'YYYYMM')        >>>        affiche "200503"
				switch ($time_aggregation) {
					case "hour" :
						$date_format = "YYYYMMDDHH";
						$interval_value = $time_frame . "hours";
						break;
					case ($time_aggregation == "day" || $time_aggregation == "day_bh") :
						$date_format = "YYYYMMDD";
						$interval_value = $time_frame . "days";
						break;
					case ($time_aggregation == "week" || $time_aggregation == "week_bh") :
						$date_format = "YYYYWW";
						$interval_value = $time_frame . "weeks";
						break;
					case ($time_aggregation == "month" || $time_aggregation == "month_bh") :
						$date_format = "YYYYMM";
						$interval_value = $time_frame . "months";
						break;
				}
				
				// ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
				// maj 29/11/2007 - maxime - cb40001 -> Nouveau mode de calcul 
				// Le critère de déclenchement d’une alarme dynamique est le pourcentage de dépassement de la valeur moyenne sur une période de 14 TA 
				// par rapport à la valeur de la donnée à la date de calcul. 
				
				// - mode de calcul : l'alarme saute si valeur du jour n  - moyenne des 14 jours n-1 / moyenne des 14 jours n-1* 100  > threshold
				// ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
				
				// $time_frame=14; //on fige -> cette valeur est enregistrée dans la table SYS_GLOBAL_PARAMETERS : offset_day
				// AJOUTER DANS LA REQUETE LES CHAMPS ADDITIONNELS
				// le principe de la requete est :
				// de calculer la moyenne des n-1 derniers jours en fonction des jours exclus
				// d'ajouter en condition valeur du jour n  - moyenne des 14 jours n-1 / moyenne des 14 jours n-1* 100  > threshold (alarmes qui sautent)

				// il ne faut pas prendre en compte dans l'AVG le jour donnée car cela fausserait la comparaison avec la valeur en question

				
				/*
				Gestion de la partie FROM de la requte.
				la partie From contient 2 tables construite sur la base de requpetes:
						- i2 qui est intégrée directement dans la requete. Elle récupère la moyenne des 14 derniers jours
						- i1 qui est determinéee ci-dessous et qui utilise s'il existe le trigger et donc la table qui contient le trigger.
						  si le trigger et la condition n'ont pas le même type alors il faut faire des jointures entre les tables raw et kpi
				*/

				// si le trigger existe a le type est différent du compteur/KPI de condition, il faut generer des jointures entres les tables
				$field_axe3 = '';
				if ($na_axe3) $field_axe3 = " t1.$na_axe3,";
				
				if ($trigger_field != null && $condition_field_type != $trigger_field_type) {
					$from = " (SELECT t1.$na_axe1,{$field_axe3}t1.$time_aggregation,$condition_field FROM $table_raw t1,$table_kpi t2 WHERE $trigger_field$trigger_operand$trigger_value AND t1.$na_axe1=t2.$na_axe1 and t1.$time_aggregation=t2.$time_aggregation and t1.$time_aggregation='$time_value')";

				} elseif ($trigger_field != null && $condition_field_type == $trigger_field_type) {
					if ($condition_field_type == 'raw')		$table_t1 = $table_raw;
					else 								$table_t1 = $table_kpi;
			
					$from = "(SELECT t1.$na_axe1,{$field_axe3}t1.$time_aggregation,$condition_field FROM $table_t1 t1 WHERE $trigger_field$trigger_operand$trigger_value and t1.$time_aggregation='$time_value')";

				} else {
					if ($condition_field_type == 'raw') {
						$table_i0 = $table_raw;
						$from = "(SELECT t1.$na_axe1,{$field_axe3}t1.$time_aggregation,$condition_field FROM $table_raw t1 WHERE t1.$time_aggregation='$time_value')";
					} else {
						$table_i0 = $table_kpi;
						$from = "(SELECT t1.$na_axe1,{$field_axe3}t1.$time_aggregation,$condition_field FROM $table_kpi t1 WHERE t1.$time_aggregation='$time_value')";
					}
				}

				if ($condition_field_type == 'raw')		$table_i0 = $table_raw;
				else						$table_i0 = $table_kpi;
				
				// maj 10/07/07 Maxime -> On inclue la sélection de éléments réseaux de l'alarme enregistrée en base si celle-ci est différente de 'all'
				$selection_na_values="";

                                // 04/08/2010 MPR : Correction du BZ 17077 La sélection d'éléments réseau entraine une erreur SQL sur une famille 3ème axe
				$condition = $this->get_selection_network_elements($alarm_id,'alarm_dynamic',$na_axe1);
				if ($condition!="")
					// 17:57 26/08/2009 GHX
					// Suppression du prefixe t0. sinon erreur SQL
					$selection_na_values = " and ".str_replace('t0.', '', $condition);

				$query = " --- INSERT for $alarm_name (dynamic alarm) 
					INSERT INTO edw_alarm
						(id_alarm,critical_level,acknowledgement,ta,ta_value,na,na_value,";
				
				// on teste si on a un axe3
				if (strpos($network_aggregation,'_'))		$query .= 'a3,a3_value,';
				
				$query .= "alarm_type)
					SELECT '$alarm_id','$criticity_level','0','$time_aggregation',$time_value,'$na_axe1',i1.$na_axe1,";

				// on teste si on a un axe3
				if (strpos($network_aggregation,'_'))		$query .= " '$na_axe3', i1.$na_axe3,";
				
				// 16:57 31/08/2009 GHX
				// Correction du BZ 11312
				$query .= " '$this->type_alarm'
					FROM
						$from  i1 ,
						(SELECT
							$na_axe1,
							".(strpos($network_aggregation,'_') ? "$na_axe3," : '')."
							$time_value,
							AVG($condition_field) as moyenne
							$axe_index_in_query
						FROM $table_i0 i0
						WHERE $time_aggregation IN (" . implode(',', get_dyn_alarm_times($discontinuous, $time_aggregation, $time_value, $interval_value, $date_format, $time_frame, $this->debug,$alarm_id,$time_frame_continu)) . ")
							$hn_in_query
						GROUP BY i0.$na_axe1 ".(strpos($network_aggregation,'_') ? ",$na_axe3" : '')." $axe_index_in_query_group_by
						) i2

					WHERE i2.$na_axe1 = i1.$na_axe1
					".(strpos($network_aggregation,'_') ? "AND i2.$na_axe3 = i1.$na_axe3" : '')."
					$hn_in_query_jointure
					$selection_na_values
				";

				// Gestion des éléments blacklistés
				// modif 18/04/2007 Gwénaël
					// modification par rapport au troisième axe dans le cas où il y en a un le network agrégation est na_naAxe3
					// or le tableau $this->network_aggregation_min contient uniquement des na sans troisième axe
					// donc on récupère uniquement la première valeur de l'explode
				if ($na_axe1 == $this->network_aggregation_min[$this->family]) {
					$query.= "\n	AND i1.$na_axe1 NOT IN (SELECT eor_id FROM edw_object_ref WHERE eor_obj_type='$na_axe1' AND eor_blacklisted=1) ";
					//-maj 02/01/2007 - maxime : On limite la sélection des na value minimum d'une famille aux éléments réseau actifs (on_off=1)
					$query.= "\n	AND i1.$na_axe1 NOT IN (SELECT eor_id FROM edw_object_ref WHERE eor_obj_type='$na_axe1' AND eor_on_off=0) ";
					// modif 24/05/2007 Gwénaël
					// 25/11/2011 ACS BZ 24792 Virtual SAI of corporate are visibles in multiple GUI
					// il ne faut pas intégrer les cellules qui commencent par virtual, elles servent seulement pour le calcule des compteurs qui sont bypass
					$where[] = NeModel::whereClauseWithoutVirtual('t0', $na_axe1);
				}
				// On ajoute le nouveau mode de calcul dans le Having
				$query .= "GROUP BY i1.$condition_field, i1.$na_axe1,";
				if ($na_axe3) $query .= " i1.$na_axe3,";
				$query .=" moyenne
						  HAVING  ( (i1.$condition_field - moyenne) / moyenne * 100 )::float8 > $nbr_stddev 
						  and moyenne is not null";
				//HAVING ABS( ( 1 + ABS( $nbr_stddev/100 ) ) * moyenne ) < ABS( i1.$condition_field ) 
				$tab_requete[$alarm_id]["query"][$criticity_level] = $query;
				$tab_requete[$alarm_id]["alarm_name"] = $alarm_name;
			}
		}
		return $tab_requete;
	}



	function put_in_join_table($na, $alarm_properties, $id_group_table, $critical_level, $alarm_id)
	{
		$nb = 0;
		$group_name = get_group_name($id_group_table);

		if ($this->debug) echo "<b><u>Insertion dans les tables _join : </u></b>.<br>";

		for ($a = 0;$a < count($this->result_ids_inserted[$critical_level][$alarm_id]);$a++) {
		
			// 15:22 31/08/2009 GHX
			// Correction du BZ 11312
			$_na = explode('_', $na);
			$query1 = "
				SELECT 
					na_value,
					ta_value,
					ta,
					a3_value,
					a3,
					critical_level
				FROM 
					edw_alarm 
				WHERE
					critical_level='$critical_level' 
					AND na = '".$_na[0]."'
					".(count($_na) == 2 ? " AND a3 = '".$_na[1]."'" : "")."
					AND id_result=" . $this->result_ids_inserted[$critical_level][$alarm_id][$a];
			// __debug(get_sys_global_parameters('module'),"module $p");
			if ($this->debug) echo "<u>Query de récupération des data sur _RESULT </u>" . $query1 . "<br>";
			$res1 = $this->db->getall($query1);
			while ($row1 = array_shift($res1)) {
				$na_value		= $row1["na_value"];
				$time_value	= $row1["ta_value"];
				$time_to_sel	= $row1["ta"];
				$a3_value		= $row1["a3_value"];
				$a3	= $row1["a3"];
				$critical_level	= $row1["critical_level"];
			}
			$alarm_properties_criticity = $alarm_properties[$alarm_id]["criticity"][$critical_level];

			$condition_field		= $alarm_properties_criticity["data_field"];
			$condition_field_type	= $alarm_properties_criticity["data_type"];
			$nbr_stddev			= $alarm_properties_criticity["data_value"];
			$trigger_field			= $alarm_properties_criticity["trigger_data_field"];
			$trigger_field_type		= $alarm_properties_criticity["trigger_data_type"];
			$trigger_operand		= $alarm_properties_criticity["trigger_data_operand"];
			$trigger_value			= $alarm_properties_criticity["trigger_data_value"];

			$network_aggregation	= $na;
			$time_aggregation		= $time_to_sel;
			$time_frame			= $alarm_properties[$alarm_id]["alarm_time_frame"];
			$discontinuous			= $alarm_properties[$alarm_id]["discontinuous"];
			$time_frame_continu	= "";
			// Pour le mode continu on récupère un intervalle de temps de 500 jours)
			if (!$discontinuous)
				$time_frame_continu = 500;

			$home_network_value	= $hn_value;

			$table_raw = $group_name . "_raw_" . $network_aggregation . "_" . $time_aggregation;
			$table_kpi = $group_name . "_kpi_" . $network_aggregation . "_" . $time_aggregation;

			switch ($time_aggregation) {
				case "hour" :
					$date_format = "YYYYMMDDHH";
					$interval_value = $time_frame . "hours";
					break;
				case ($time_aggregation == "day" || $time_aggregation == "day_bh") :
					$date_format = "YYYYMMDD";
					$interval_value = $time_frame . "days";
					break;
				case ($time_aggregation == "week" || $time_aggregation == "week_bh") :
					$date_format = "YYYYWW";
					$interval_value = $time_frame . "weeks";
					break;
				case ($time_aggregation == "month" || $time_aggregation == "month_bh") :
					$date_format = "YYYYMM";
					$interval_value = $time_frame . "months";
					break;
			}
			// Recupération des éléments associés à la condition de l'alarme dynamique
			if ($condition_field_type == 'raw') {
				$table_i0 = $table_raw;
				// 16:27 31/08/2009 GHX
				// Correction du BZ 11312
				if ( empty($a3) )
				{
					$from = "(SELECT t1.$network_aggregation,t1.$time_aggregation,$condition_field FROM $table_raw t1 WHERE t1.$time_aggregation='$time_value' and t1.$network_aggregation = '$na_value')";
				}
				else
				{
					$from = "(SELECT t1.".$_na[0].",t1.$a3,t1.$time_aggregation,$condition_field FROM $table_raw t1 WHERE t1.$time_aggregation='$time_value' and t1.".$_na[0]." = '$na_value' AND t1.$a3 = '$a3_value')";
				}
			} else {
				$table_i0 = $table_kpi;
				// 16:27 31/08/2009 GHX
				// Correction du BZ 11312
				if ( empty($a3) )
				{
					$from = "(SELECT t1.$network_aggregation,t1.$time_aggregation,$condition_field FROM $table_kpi t1 WHERE t1.$time_aggregation='$time_value' and t1.$network_aggregation = '$na_value')";
				}
				else
				{
					$from = "(SELECT t1.".$_na[0].",t1.$a3,t1.$time_aggregation,$condition_field FROM $table_kpi t1 WHERE t1.$time_aggregation='$time_value' and t1.".$_na[0]." = '$na_value' AND t1.$a3 = '$a3_value')";
				}
			}
			// 16:48 31/08/2009 GHX
			// Correction du BZ 11312
			$query = "
				INSERT INTO edw_alarm_detail(id_result,trigger,trigger_operand,trigger_value,value,additional_details,field_type)
				SELECT '" . $this->result_ids_inserted[$critical_level][$alarm_id][$a] . "','" . $condition_field . "',' > '," . $nbr_stddev . "," . $condition_field . ",moyenne|| '@' || ABS(($condition_field - moyenne)/moyenne)*100::float8,'trigger'
				FROM $from  i1 ,
					(SELECT
						".$_na[0].",
						".(empty($a3) ? "" : $a3.",")."
						$time_value,
						AVG($condition_field) as moyenne
						$axe_index_in_query
					FROM $table_i0
					WHERE $time_aggregation IN (" . implode(',', get_dyn_alarm_times($discontinuous, $time_aggregation, $time_value, $interval_value, $date_format, $time_frame, $this->debug,$alarm_id,$time_frame_continu)) . ")
					GROUP BY ".$_na[0]." ".(empty($a3) ? "" : ",".$a3)."
				) i2

				WHERE i2.".$_na[0]." = i1.".$_na[0]."
				".(empty($a3) ? "" :  "AND i2.$a3 = i1.$a3 ")."
						
				GROUP BY i1.$condition_field, moyenne
				HAVING moyenne is not null
                        ";
			if ($this->debug) {
				echo "<hr/>ALARM ".$alarm_properties[$alarm_id]["alarm_name"]."<br>";
				print_r($query);
				echo "<hr/>";
			}
			// __debug(get_sys_global_parameters('module'),"module $p");
			$this->db->execute($query);
			// Recupération des éléments associés au trigger de l'alarme dynamique s'il existe
			if ($trigger_field != null) {
				if ($trigger_field_type == 'raw')	$table_i1 = $table_raw;
				else							$table_i1 = $table_kpi;

				// 16:48 31/08/2009 GHX
				// Correction du BZ 11312
				$query = "
					INSERT INTO edw_alarm_detail
						(id_result,trigger,trigger_operand,trigger_value,value,field_type)
					SELECT '" . $this->result_ids_inserted[$critical_level][$alarm_id][$a] . "','" . $trigger_field . "','$trigger_operand','" . $trigger_value . "'," . $trigger_field . ",'trigger'
					FROM
						$table_i1  i1
					WHERE i1.".$_na[0]." ='$na_value'
						".(empty($a3) ? "" :  "AND i1.$a3 = '$a3_value' ")."
						AND i1.$time_aggregation='$time_value'
						$hn_in_query
				";
				if ($this->debug) echo "<u><b>Query insert dans EDW_ALARM_DETAIL :</b></u>" . $query . "<br>";
				// __debug(get_sys_global_parameters('module'),"module $p");
				$this->db->execute($query);
			}
			// Récupération des champs additionnels
			// insertion des champs additionnels
			// gestion de la partie 'Where' de la query
			$query_where = " WHERE " .$_na[0] . "='" . $na_value . "' ".(empty($a3) ? "" :  "AND i1.$a3 = '$a3_value' ")." and   $time_to_sel = $time_value "; //getTaQueryForCompute($time_to_sel, $time_value);//$time_to_sel=$time_value";
			$query_where .= $hn_in_query; //cas de l'axe 3 (vide si pas d'axe3)
			for ($p = 0;$p < count($alarm_properties[$alarm_id]['additionnal_field']["data_field"]);$p++) {
				// __debug(get_sys_global_parameters('module'),"module $p");
				$query = "
					INSERT INTO edw_alarm_detail (id_result,trigger,value,field_type)
					SELECT '" . $this->result_ids_inserted[$critical_level][$alarm_id][$a] . "','" . $alarm_properties[$alarm_id]['additionnal_field']["data_field"][$p] . "'," . $alarm_properties[$alarm_id]['additionnal_field']["data_field"][$p] . ",'additional'";
				if ($alarm_properties[$alarm_id]['additionnal_field']["data_type"][$p] == "raw") {
					$query .= " FROM " . $group_name . "_raw_" . $na . "_" . $time_to_sel;
				} else {
					$query .= " FROM " . $group_name . "_kpi_" . $na . "_" . $time_to_sel;
				}
				$query .= $query_where;
				$this->db->execute($query);
				if ($this->debug) echo "&nbsp;&nbsp;&nbsp;&nbsp;<u>Insertion dans edw_alarm_detail</u> : " . $query . "<br>";
			}
		}
	}
}

?>
