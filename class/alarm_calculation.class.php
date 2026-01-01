<?php
/*
	17/07/2009 GHX
		- Prise en compte du mode débug
		
	03/11/2011 ACS BZ 24000 PG 9.1 Cast issue remaining
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
	- maj 25/02/2008, benoit : prise en compte du compute switch et de la liste des heures dans 'hour_to_compute'
	
	- maj 27/02/2008, benoit : utilisation de la fonction 'displayInDemon()' pour afficher les titres dans le demon
	
	- maj 05/03/2008, maxime : Changement de message pour le tracelog

	- maj 07/03/2008, benoit : mise en commentaires des appels à la fonction '__debug()'
	
	- maj 27/05/2008 - maxime : Modification du nom du module pour le message du tracelog
	
	- maj 15/06/2009 BBX : Ajout du préfixe t0 dans la requete de la fonction get_selection_network_elements. BZ 10049
*/
?>
<?
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
*	 maj 20/09/07 Maxime -> En mode hourly, on récupère les heures exclues pour le jour donné
*	 maj 26/06/07 Maxime -> On inclue l'exclusion de plages temporaires quand la ta = hour ou ta = day ou ta = day_bh
*	 maj 10/07/07 Maxime -> On inclue la sélection de éléements réseaux de l'alarme enregistrée en base si celle-ci est différente de 'all'
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
*	- maj 31/05/2007 Gwénaël : modification de la requete qui récupère les na afin de ne pas avoir de doublons (fonction : get_network_deployed_by_gt) sinon les alarmes sont calculées aussi en doublons
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
	Classe alarm
	Peremt de gérer le calcul des alarmes
	- maj le 04 05 2006 : delta entre la version 1191 et la version 1200 ajout de la ligne 28
	- maj 07 07 2006 christophe : modification de la fonction clean_tables pour la prise en charge du nettoyage de a table edw_alarm_log_error.
	- maj 03-08-2006 GH : modification pour la prise en compte des seuils (critical,major,minor) et de nouveaux éléments (champs additionnels partout notamment)

	- maj 05/04/2007 Gwénaël : suppression (en commentaire) des parties concernant le home_network (cf. $this->flag_axe3)
*/
class alarm {
	/*
		Constructeur
	*/
	function alarm ()
	{

		$this->debug = get_sys_debug('alarm_calculation');;
		switch ($this->type_alarm) {
			case "static":
				$this->table_alarm_definition = "sys_definition_alarm_static";
				break;
			case "dyn_alarm":
				$this->table_alarm_definition = "sys_definition_alarm_dynamic";
				break;
			case "top-worst":
				$this->table_alarm_definition = "sys_definition_alarm_top_worst";
				break;
		}
		$this->id_alarm = "alarm_id";

		// 27/02/2008 - Modif. benoit : utilisation de la fonction 'displayInDemon()' pour afficher les titres dans le demon
		//echo "<br><div class=texteRouge><u><b>Calcul des alarmes de type : $this->type_alarm</b></u></div>";
		displayInDemon("Calcul des alarmes de type : ".$this->type_alarm, 'title');

		$this->alarm_result_limit	= get_sys_global_parameters("alarm_result_limit");
		$this->top_worst_limit	= get_sys_global_parameters("alarm_top_worst_result");
		$this->offset_day		= get_sys_global_parameters("offset_day");
		$this->compute_mode	= get_sys_global_parameters("compute_mode");
		$this->init();
	}

	/*
		Initialistation du calcul.
	*/
	function init()
	{
		// echo "<div class='debug'>this->offset_day=<strong>$this->offset_day</strong></div>";
		$this->time_to_calculate	= get_time_to_calculate($this->offset_day);
		$this->family_list		= $this->get_family_list();
		if (is_array($this->family_list)) {
			foreach ($this->family_list as $family_name) {
				$this->network_aggregation_list_by_family[$family_name] = $this->get_network_deployed_by_gt($family_name);
				$this->network_aggregation_min[$family_name] = get_network_aggregation_min_from_family($family_name);
			}
		}
		
		if ($this->debug) {
			echo "<div class='debug'>this->family_list<br/>";
			print_r($this->family_list);
			echo "<hr/>";
			foreach ($this->network_aggregation_list_by_family as $key => $arr)
				echo "$key : <div style='margin-left:20px;background:#CCC;'>";print_r($arr);echo "</div>";
			echo "</div>";
		}
		
		echo "<br><u>Contenu du tableau de time to calculate</u><br>";
		var_dump($this->time_to_calculate);
		echo "<br/><br/>";
	}

	/*
	* fonction qui va supprimer les résultats pour des alarmes qui n'existe plus
	*/
	function clean_removed_alarm()
	{
		$this->msg_head[] = "***** suppression des resultats pour des alarmes qui n'existent plus";
		$query_removed_detail = " --- edw_alarm_detail : suppression des resultats d'alarmes qui n'existent plus
			DELETE FROM edw_alarm_detail
			WHERE id_result IN (
				SELECT distinct id_result
				FROM edw_alarm
				WHERE alarm_type='$this->type_alarm'
					AND id_alarm NOT IN
						(SELECT DISTINCT alarm_id FROM $this->table_alarm_definition)
				) ";
		$this->db->execute($query_removed_detail);
		$result_nb = $this->db->getAffectedRows();
		$this->msg_head[] = $result_nb . "=" . $query_removed_detail;
		$query_removed_result = " --- edw_alarm : suppression des resultats d'alarmes qui n'existent plus
			DELETE FROM edw_alarm
			WHERE alarm_type='$this->type_alarm'
				AND id_alarm NOT IN (select distinct alarm_id FROM $this->table_alarm_definition)";
		$this->db->execute($query_removed_result);
		$this->msg_head[] = $result_nb . "=" . $query_removed_result;
	}

	/*
		Retourne la liste des identifiants id_result de la table edw_alarm
		venant d'être insérés.
	*/
	function get_ids_inserted($seuil, $id_alarm)
	{
		if ($this->debug) {
			echo "<div class='debug'><div class='function_call'>get_ids_inserted(seuil=<strong>$seuil</strong>, id_alarm=<strong>$id_alarm</strong>)</div></div>";
		}
		
		unset($this->result_ids_inserted[$seuil]);
		if ($seuil != "") {
			// cas static ou dynamic
			$query = " --- recherche les ids ajoutés dans edw_alarm par la dernière alarme static ou dynamique
				SELECT id_result
				FROM edw_alarm
				WHERE id_alarm='$id_alarm'
					AND critical_level='$seuil'
					AND id_result > '$this->old_result_id' ";
			$res = $this->db->getall($query);
			if ($res)
				foreach ($res as $row)
					$this->result_ids_inserted[$seuil][$id_alarm][] = $row['id_result'];
		} else {
			// cas Top/Worst
			$query = " --- recherche les ids ajoutés dans edw_alarm par la derniere alarme Top-Worst
				SELECT id_result from edw_alarm where id_alarm='$id_alarm' and critical_level IS NULL AND id_result > '$this->old_result_id' ";
			$res = $this->db->getall($query);
			if ($res)
				foreach ($res as $row)
					$this->result_ids_inserted[$id_alarm][] = $row['id_result'];
		}
	}

	/*
		On initialise les id de resultats pour chaque alarme
	*/
	function init_id_result()
	{
		$query_id = "SELECT MAX(id_result) FROM edw_alarm";
		$this->old_result_id = $this->db->getone($query_id);
		if ($this->old_result_id == "")
			$this->old_result_id = 0;
	}

	/*
		Permet de supprimmer les anciens enregistrements calculés dans les tables
		edw_alarm et edw_alarm_detail.
	*/
	function clean_tables($time_aggregation, $time_value)
	{
		if ($this->debug) echo "<div class='debug'>clean_tables(time_aggregation=<strong>$time_aggregation</strong>, time_value=<strong>$time_value</strong>)</div>";
		// 25/02/2008 - Modif. benoit : prise en compte d'une liste de valeurs de ta (sert pour les listes d'heures dans le compute booster)

		if (is_array($time_value))
				$chaine_query_ta_value = " ta_value IN (".(implode(", ", $time_value)).")";
		else		$chaine_query_ta_value = " ta_value = ".$time_value;
	
		// Cas spécifique de la time aggregation HOUR
		$compute_mode = $this->compute_mode; // Valeurs possibles : hourly ou daily.
		$compute_processing = get_sys_global_parameters("compute_processing"); // Valeurs possibles : hour ou day.
		
		// si on est en compute mode = hourly et compute processing = hour, on supprime les enregistrements
		// de l'heure qui vat être calculée.
		// si on est en compute mode = daily  on supprime tous les enregistrement du jour.
		
		// 25/02/2008 - Modif. benoit : ajout d'une condition sur le compute switch pour la suppression de l'ensemble des heures de la journée
		// 03/11/2011 ACS BZ 24000 PG 9.1 Cast issue remaining
		if (($compute_mode == "daily") && ($time_aggregation == "hour") && (get_sys_global_parameters('compute_switch') != "hourly")) 
		{
			$new_time_value = substr($time_value, 0, -2);
			$chaine_query_ta_value = " ta_value::text  like '$new_time_value%'"; // on supprime toutes le heures de la journée
		}
		
		// Suppression des alarmes dans edw_alarm_detail
		$query = " --- suppression des alarmes dans edw_alarm_detail
			DELETE FROM edw_alarm_detail
			WHERE id_result IN (
				SELECT id_result FROM edw_alarm
				WHERE $chaine_query_ta_value
					AND ta = '$time_aggregation'
					AND alarm_type='$this->type_alarm') ";
		$res = $this->db->execute($query);
		
		// Suppresion des alarmes dans edw_alarm
		$query = " --- suppression des alarmes dans edw_alarm
			DELETE FROM edw_alarm
			WHERE $chaine_query_ta_value
				AND ta = '$time_aggregation'
				AND alarm_type='$this->type_alarm' ";
		$res = $this->db->execute($query);

		// Suppression dans edw_alarm_log_error.
		$query = " --- suppression dans edw_alarm_log_error
			DELETE FROM edw_alarm_log_error
			WHERE type='$this->type_alarm'
				AND ta = '$time_aggregation'
				AND $chaine_query_ta_value";
		$res = $this->db->execute($query);
	}

	/**
	* fonction qui retourne pour la liste des famille présente dans la table de définition des alarmes
	* @param : $table_alarm_definition qui est la table dans laquelle trouver la definition des alarmes suivant le type d'alarme
	* @return :tableau contenant la liste des familles ou FALSE
	*/
	function get_family_list ()
	{
		$query = "SELECT distinct family,id_group_table FROM $this->table_alarm_definition WHERE on_off=1";
		$result = $this->db->getall($query);
		if ($result) {
			foreach ($result as $row)
				$family_list[$row["id_group_table"]] = $row["family"];
			return $family_list;
		} else {
			echo "<div class=texteRouge>";
			$this->msg_head[] = "Aucune famille trouvée dans la table $this->table_alarm_definition pour les alarmes de type : $this->type_alarm";
			echo "</div>";
			$val = "";
			return $val;
		}
	}

	/**
	* fonction qui retourne pour group table la liste des network aggregation deployés à partir du nom d'une famille
	* @parameter : nomd'une famille
	* @return : array contentant pour chaque gt, son nom et les network aggregation deployés ou FALSE
	*/
	function get_network_deployed_by_gt ($family)
	{
		// modif 31/05/2007 Gwénaël
			// modification sur la condition afin de ne pas avoir de doublons pour les familles du troisièmes
			// de plus la modif prend en considération si deux na d'axe1 commence pareil comme pour la famille Apname   : apname et apnamegroup
		$query = "
			SELECT
				distinct t0.edw_group_table,
				t0.id_ligne,
				t1.network_agregation,
				t2.agregation_rank
			FROM
				sys_definition_group_table t0,
				sys_definition_group_table_network t1,
				sys_definition_network_agregation t2
			WHERE t0.family = '$family'
				AND t0.id_ligne = t1.id_group_table
				AND t0.family = t2.family
				AND split_part(t1.network_agregation , '_', 1) = t2.agregation
				AND t2.axe IS NULL
			ORDER BY t2.agregation_rank asc";

		$result = $this->db->getall($query);
		if ($result) {
			foreach ($result as $row) {
				$id_group_table		= $row["id_ligne"];
				$group_table			= $row["edw_group_table"];
				$network_agregation	= $row["network_agregation"];
				$group_table_info[$id_group_table]["name"] = $group_table; //pour un identifiant de group table ne corrspond qu'un seul nom
				$group_table_info[$id_group_table]["network_aggregation"][] = $network_agregation;
			}
			return $group_table_info;
		} else {
			echo "<div class=texteRouge>";
			print 'Aucune Network aggregation déployée pour la famille ' . $family;
			echo "</div>";
			return false;
		}
	}

	/**
	* fonction qui execute la requete d'insertion des resultats d'alarme et qui vérifie si le nombre de résultats n'est pas > au nombre maximum autorisé
	* @param : $query - requete d'insertion des resultats d'alarme
	* @return :commit/abort pour valider si les insertions ont été effectuées ou non en fonction du nombre de résultats
	*/
	function execute_requete($alarm_id, $alarm_properties, $time_aggregation, $time_value, $network_aggregation, $seuil, $requete)
	{
		if ($this->debug) {
			echo "<div class='debug'><div class='function_call'>execute_requete(alarm_id=<strong>$alarm_id</strong>, alarm_properties=<strong>$alarm_properties</strong>, time_aggregation=<strong>$time_aggregation</strong>, time_value=<strong>$time_value</strong>, network_aggregation=<strong>$network_aggregation</strong>, seuil=<strong>$seuil</strong>, requete=<strong>$requete</strong>);</div>";
		}
		
		$this->db->execute("BEGIN;");
		$this->db->execute($requete);	// insertion
		$main_query_error = $this->db->getLastError();
		if ($main_query_error != '') {
			$this->db->execute("ABORT;");
			$commit_abort = 'abort';
			print $main_query_error . "=" . $requete . "<br>";
		} else {
			$result_nb = $this->db->getAffectedRows();
			if ($this->debug) echo "result_nb=<strong>$result_nb</strong><br/>alarm_result_limit=<strong>$this->alarm_result_limit</strong>";
			if ($result_nb > $this->alarm_result_limit) {
				$this->db->execute("ABORT;");
				$commit_abort = 'abort';
				$alarm_name = $alarm_properties[$alarm_id]["alarm_name"];

				$this->msg[$network_aggregation][count($this->msg[$network_aggregation])-1].= "     >><strong>ABORT : $result_nb résultats > $this->alarm_result_limit (limite)</strong>";
				$nom_alarme = $fields["alarm_name"];
				
				// maj 05/03/2008  - maxime : Changement de message pour le tracelog
				$message = __T('A_ALARM_CALCULATION_MSG_TRACELOG_ALARM_NOT_INSERTED',$alarm_name,$seuil,$result_nb,$network_aggregation,$time_aggregation,$time_value);
				sys_log_ast("Critical", get_sys_global_parameters("system_name"), __T("A_TRACELOG_MODULE_LABEL_ALARM"), $message, "support_1", "");
				$this->insert_alarm_log_error($alarm_id, $time_aggregation, $time_value, $network_aggregation, $result_nb, $hn, $hn_value, $this->type_alarm, false, $seuil);
			} else {
				$commit_abort = 'commit';
				$this->db->execute("COMMIT;");
				$this->msg[$network_aggregation][count($this->msg[$network_aggregation])-1].= $result_nb . " valeurs insérées";
			}

			if ($this->debug) echo "&nbsp;&nbsp;&nbsp;&nbsp;<u>Insertion dans edw_alarm</u> : " . $requete . "<br>";
			if ($main_query_error != '') {
				echo "<div class=texteRouge>";
				echo $main_query_error. " on " . $requete;
				echo "</div>";
			}
		}
		if ($this->debug) echo '</div>';
		return $commit_abort;
	}

	/**
	*Fonction get_selection_network_elements($id_alarm,$type_alarm)
	* Retourne une condition in ou not in ou vide permettant de faire une sélection sur les éléments réseaux pour l'alarme donnée
	* @param int $id_alarm n° de l'alarme
	* @param text $type_alarm type de l'alarme
	*@param text $network_aggregation niveau d'aggrégation réseau de l'alarme
	* @return condition incluse dans un where pdt les insertions des alarmes qui ont sautées
	*/
	function get_selection_network_elements($id_alarm,$type_alarm,$network_aggregation){

		$query = "
			SELECT lst_alarm_compute,not_in
			FROM sys_definition_alarm_network_elements
			WHERE type_alarm = '$type_alarm'
				AND id_alarm = '$id_alarm' ";
		$res = $this->db->getall($query);

		if ($res) {
			foreach ($res as $row) {
				$condition = "";
				if ($row['lst_alarm_compute']!='all' and $row['lst_alarm_compute']!=NULL) {
					if ($this->type_alarm=="dyn_alarm")
						$condition.= "i1.";
					// 15/06/2009 BBX : Ajout du préfixe t0. BZ 10049
					if ($row['not_in']==0)
						$condition.= "t0.$network_aggregation IN ('";
					else
						$condition.= "t0.$network_aggregation NOT IN ('";
					$condition.= implode("','",explode("||",$row['lst_alarm_compute']))."')";// On remplace le delimiter par une ,
				}
			}
		}
		return $condition;
	}

	/**
	* Fonction get_num_day_of_week($date)
	* Retourne le n° du jour de la semaine d'une date passée en paramètre (ex 2 pour mardi, ...)
	* @param int $date date (ex:20070628)
	*@return numéro du jour de la semaine $date
	*/
	function get_num_day_of_week($date){
		$time_exclusion_day = explode(";",__T('A_ALARM_DAY_OF_WEEK'));
		// On récupère le nom du jour puis son n°
		//__debug($date,"date");
		$name = date("l", mktime(0, 0, 0,substr($date,-4,2),  substr($date,-2,2), substr($date,0,-4))); // On récupère le nom du jour à partir de la date décomposée en 3 parties
		//__debug($name,"name");
		$num = array_keys($time_exclusion_day,$name);
		return $num[0];
	}

	/*
	Retourne la liste des périodes d'exclusion pour les alarmes static exclues
	*/
	function get_alarm_to_exclude($ta,$ta_value){
		$type = $this->type_alarm;
			if($this->type_alarm=="top-worst")
				$type = "top_worst";

		if ($ta=='hour'){
			// 20 09 2007 maxime - En mode hourly, on récupère les heures exclues pour le jour donné
			$day =  $this->get_num_day_of_week( substr($ta_value,0,8) );
			$hour = substr($ta_value,8,2);
			$condition = "'$hour'  and id_parent IN ( SELECT id FROM sys_definition_alarm_exclusion WHERE ta = 'day' and ta_value = '$day' and type_alarm='alarm_".$type."' )";
		} else
			$condition = $this->get_num_day_of_week($ta_value);

		$query = "SELECT distinct id_alarm FROM sys_definition_alarm_exclusion
				  WHERE ta = '$ta' and ta_value=$condition and type_alarm='alarm_".$type."'";

		$res = $this->db->getall($query);
		if ($res)
			foreach ($res as $row)
				if ($row["id_alarm"]!=NULL)
					$alarm_to_exclude[] = $row["id_alarm"];
		return $alarm_to_exclude;
	}

	/*
	* Fonction get_ta_to_calculate()
	* Retourne les périodes temporelles à calculer qui récupère les ta à calculer
	*/
	function get_ta_to_calculate(){
		$offset_day	= get_sys_global_parameters("offset_day");
		$hour		= get_sys_global_parameters("hour_to_compute");
		
		$t = array("00","01","02","03","04","05","06","07","08","09","10", "11","12","13","14","15","16","17","18","19","20","21","22","23");
		
		if ($this->time_to_calculate["hour"]!=NULL) {
			if ($this->time_to_calculate['day']!=NULL) {
				
				// 25/02/2008 - Modif. benoit : dans le cas d'un compute switch, on ne traite que les heures de 'hour_to_compute'
				if (get_sys_global_parameters('compute_switch') == "hourly") {
					$this->ta_to_calculate["hour"] = explode(get_sys_global_parameters('sep_axe3'), $hour);
				} else {
					// Traitement de toutes les heures du jour
					foreach($t as $val)
						$this->ta_to_calculate['hour'][] = $this->time_to_calculate['day'].$val;
				}
			} else {
				// 25/02/2008 - Modif. benoit : on gère à présent les listes d'heures dans 'hour_to_compute'
				$this->ta_to_calculate["hour"] = explode(get_sys_global_parameters('sep_axe3'), $hour);
			}
		}
		
		if ($this->time_to_calculate['day']		!=NULL) $this->ta_to_calculate['day']		= $this->time_to_calculate["day"];
		if ($this->time_to_calculate["day_bh"]	!=NULL) $this->ta_to_calculate['day_bh']		= $this->time_to_calculate["day_bh"];
		if ($this->time_to_calculate["week"]		!=NULL) $this->ta_to_calculate['week']		= $this->time_to_calculate["week"];
		if ($this->time_to_calculate["week_bh"]	!=NULL) $this->ta_to_calculate['week_bh']	= $this->time_to_calculate["week_bh"];
		if ($this->time_to_calculate["month"]	!=NULL) $this->ta_to_calculate['month']		= $this->time_to_calculate["month"];
		if ($this->time_to_calculate["month_bh"]	!=NULL) $this->ta_to_calculate['month_bh']	= $this->time_to_calculate["month_bh"];
	}
	
	/**
	*	Fonction get_alarm_to_calculate($ta,$id_group_table)
	*	Fonction qui récupère la liste des alarmes qui devront être calculées
	* 	@param string $ta Time Agregation
	*	@param int $id_group_table Id Group Table
	*/
	function get_alarm_to_calculate($ta,$id_group_table)
	{
		$query = " --- on va chercher la liste des alarmes &agrave; calculer
			SELECT distinct alarm_id
			FROM $this->table_alarm_definition
			WHERE time = '$ta'
				AND id_group_table = $id_group_table";

		if ($this->debug) {
			echo "<div class='debug'><div class='function_call'>get_alarm_to_calculate(ta=<strong>$ta</strong>,id_group_table=<strong>$id_group_table</strong>)</div>";
			echo "<strong>Requete SQL :</strong> <pre>$query</pre>";
		}
				
		$res = $this->db->getall($query);
		while ($row = array_shift($res))
			$alarm_calculated[] = $row['alarm_id'];

		if ($this->debug) {
			print_r($alarm_calculated);
			echo "</div>";
		}

		return $alarm_calculated;
	}

	/*
	Fonction qui retourne l'ensemble des informations des alarmes à calculer
	*/
	function get_informations_alarms_calculated($ta,$ta_value,$id_group_table,$network) {
		// __debug($this->alarm_to_exclude[$ta][$ta_value]);
		$this->alarm_properties = $this->get_alarm_properties($id_group_table, $network, $ta,$this->alarm_to_exclude,$ta_value);

		// __debug($alarm_properties,"propr");
		if (count($this->alarm_properties) > 0 ) {
			////__debug(array_keys($this->alarm_properties),"$ta / $ta_value");
			$array_id_alarm = array_keys($this->alarm_properties);
			$tab_requete = $this->get_requetes($this->alarm_properties, $id_group_table, $network, $ta, $ta_value);
			// du fait qu'il y a plusieurs seuils, on peut retourner jusqu'à 3 requetes ce qui explique la boucle
			foreach ($tab_requete as $alarm_id => $requete_avec_seuil) {

				$alarm_name = $requete_avec_seuil["alarm_name"];
				$this->msg[$network][count($this->msg[$network])] = "<br/>*** Alarme : " . $alarm_name . " - (id $alarm_id)";
				$this->init_id_result();

				// On excécute les requêtes pour obtenir les résultats des alarmes
				foreach ($requete_avec_seuil["query"] as $seuil => $requete) {
					if($this->type_alarm!="top-worst"){
						$this->msg[$network][count($this->msg[$network])] = "&nbsp;&nbsp;&nbsp;&nbsp;$seuil : ";
					}else{
						$this->msg[$network][count($this->msg[$network])] = "&nbsp;&nbsp;&nbsp;&nbsp;";
					}
					$commit_abort = $this->execute_requete($alarm_id, $this->alarm_properties, $ta, $ta_value, $network, $seuil, $requete);
					if ($commit_abort == 'commit') {
						$this->get_ids_inserted($seuil, $alarm_id);
						$this->put_in_join_table($network, $this->alarm_properties, $id_group_table, $seuil, $alarm_id);
					}
				}
			}
		}
	}


	/*
		Fonction qui gère le lancement de tous les calculs
	*/
	function alarm_launcher()
	{
		$deb = getmicrotime();
		// On Supprime les alarmes qui ne doivent pas être calculées
		$this->get_ta_to_calculate(); // on récupère tous les time agregation selon le mode du compute (équivaut à time_to_compute)
		print  implode("<br/>",$this->msg_head);
		
		foreach ($this->time_to_calculate as $ta=>$ta_value)
			$this->clean_tables($ta,$ta_value);

		foreach ($this->ta_to_calculate as $ta=>$ta_value) {
			
			if ($ta=='hour' and $this->type_alarm!="dyn_alarm") {
				foreach ($ta_value as $v) // On parcourt toutes les heures pour retirer les heures exclues pour chaque alarme
					$this->alarm_to_exclude[$ta][$v] = $this->get_alarm_to_exclude($ta,$v); // Tableau contenant les id des alarmes qui ne devront pas être calculées
			} else {
				if (($ta == 'day' or $ta == 'day_bh') and $this->type_alarm!="dyn_alarm")
					$this->alarm_to_exclude[$ta][$ta_value] = $this->get_alarm_to_exclude($ta,$ta_value);
			}
			if ($this->debug)
				$this->db->execute(" --- alarm_launcher() -> alarms excluded for $ta=$ta_value \n select 1;");

			// ECHO "<div class='debug'>\$this->family_list = $this->family_list <br/>".gettype($this->family_list)."</div>";
			
			// On parcourt toutes les familles
			if ((is_array($this->family_list)) and (count($this->family_list)>0)) {
				foreach ($this->family_list as $k=>$family) {
					$id_group_table = $k;
					$this->family = $family;
					$information_data = $this->network_aggregation_list_by_family[$family][$id_group_table];// Raccourci
					
/*					ECHO "<table class='debug'><tr><th colspan='2'>\$information_data[]</th></tr>";
					foreach ($information_data as $k => $v) ECHO "\n	<tr><td>$k</td><td>$v</td></tr>";
						ECHO "<tr><td></td><td><table border='1'>";
						foreach ($information_data['network_aggregation'] as $k => $v) ECHO "\n	<tr><td>$k</td><td>$v</td></tr>";
						ECHO "</table></td></tr>";
					ECHO "\n	</table>";
*/					
					if (count($information_data["network_aggregation"])>0) {
						////__debug($information_data["network_aggregation"],"network");
						unset($tab_display);
						$tab_display = array();
						foreach ($information_data["network_aggregation"] as $network_aggregation) {
							
							if ($this->debug) {
								echo "
									<div class='debug'>
									<table cellspacing='0' border='1'>
										<tr><td>ta</td>				<td>$ta</td></tr>
										<tr><td>ta_value</td>			<td>$ta_value</td></tr>
										<tr><td>network_aggregation</td>	<td>$network_aggregation</td></tr>
										<tr><td>id_group_table</td>		<td>$id_group_table</td></tr>
										<tr><td>excluded</td>			<td style='background:#9F9;'>{$this->alarm_to_exclude[$ta][$ta_value]}</td></tr>
										<tr><td></td><td></td></tr>
									</table>
									</div>";
							}
							
							if ($ta!='hour') {
								// On enregistre les id des alarmes à calculer
								$alarm_calculate[$ta][$ta_value] = $this->get_alarm_to_calculate($ta,$id_group_table);
								if ($this->type_alarm!="dyn_alarm") {
									//ECHO "<div class='debug' style='background:pink;'>";PRINT_R($alarm_calculate[$ta][$ta_value]);ECHO "</div>";
									//ECHO "<div class='debug' style='background:#6F6;'>";PRINT_R($this->alarm_to_exclute[$ta][$ta_value]);ECHO "</div>";
									////__debug($alarm_calculate[$ta][$ta_value],"alarm calculate");
									if (count($alarm_calculate[$ta][$ta_value])>0) {
										if (count($this->alarm_to_exclude[$ta][$ta_value])>0)
											$this->alarm_to_calculate[$ta][$ta_value] = array_diff($alarm_calculate[$ta][$ta_value],$this->alarm_to_exclude[$ta][$ta_value]);
										else
											$this->alarm_to_calculate[$ta][$ta_value] = $alarm_calculate[$ta][$ta_value];
									}
									//ECHO "<div class='debug' style='background:#FDD;'>";PRINT_R($this->alarm_to_calculate[$ta][$ta_value]);ECHO "</div>";
								} else {
									$this->alarm_to_calculate[$ta][$ta_value] = $alarm_calculate[$ta][$ta_value];
								}
								// __debug($this->alarm_to_calculate[$ta][$ta_value],"$ta / $ta_value");
								// On enregistre les id des alarmes à calculer  // On initialise alarm_properties
								$this->get_informations_alarms_calculated($ta,$ta_value,$id_group_table,$network_aggregation);
								if (count($this->msg[$network_aggregation])>0) {
									 print "<br/>&nbsp;&nbsp; <u>Traitement du Time</u> : " . $ta . " - " . $ta_value;
									 print "--> <u>...de la Famille</u> : " . $family . "&nbsp";
									 print "&nbsp;&nbsp;&nbsp;&nbsp;<u>Network Agregation</u> : " . $network_aggregation."<br>";
									echo implode("<br/>",$this->msg[$network_aggregation])."<br/>";// on affiche chaque alarme avec les informations nécessaires
									unset($this->msg[$network_aggregation]);
								}
							} // On initialise alarm_properties
							elseif ($this->type_alarm=="dyn_alarm" and $this->compute_mode!='daily') {
								foreach ($ta_value as $v) {
									$alarm_calculate[$ta][$v] = $this->get_alarm_to_calculate($ta,$id_group_table);
									if ($alarm_to_exclude[$ta][$v]!=NULL)
										$this->alarm_to_calculate[$ta][$v] = array_diff($alarm_calculate[$ta][$v],$alarm_to_exclude[$ta][$v]);
									else
										$this->alarm_to_calculate[$ta][$v] =$alarm_calculate[$ta][$v];

									// On enregistre les id des alarmes à calculer  // On initialise alarm_properties
									$this->get_informations_alarms_calculated($ta,$v,$id_group_table,$network_aggregation);

									if (count($this->msg[$network_aggregation])>0) {
										print "<br>&nbsp;&nbsp; <u>Traitement du Time</u> : " . $ta . " - " . $v;
										print "--> <u>...de la Famille</u> : " . $family. "&nbsp; //  <u>... du group table</u> : " . $information_data["name"] . " - id : " . $id_group_table;
										print "&nbsp;&nbsp;&nbsp;&nbsp;<u>Network Agregation</u> : " . $network_aggregation."<br/>";
										echo implode("<br/>",$this->msg[$network_aggregation])."<br/>";
										unset($this->msg[$network_aggregation]);
									}
								}
							} elseif ($this->type_alarm!="dyn_alarm") {
								foreach ($ta_value as $v) {
									$alarm_calculate[$ta][$v] = $this->get_alarm_to_calculate($ta,$id_group_table);
									if ($alarm_to_exclude[$ta][$v]!=NULL)
										$this->alarm_to_calculate[$ta][$v] = array_diff($alarm_calculate[$ta][$v],$alarm_to_exclude[$ta][$v]);
									else
										$this->alarm_to_calculate[$ta][$v] =$alarm_calculate[$ta][$v];
									// On enregistre les id des alarmes à calculer  // On initialise alarm_properties
									$this->get_informations_alarms_calculated($ta,$v,$id_group_table,$network_aggregation);

									if (count($this->msg[$network_aggregation])>0) {
										print "<br>&nbsp;&nbsp; <u>Traitement du Time</u> : " . $ta . " - " . $v;
										print "--> <u>...de la Famille</u> : " . $family. "&nbsp; //  <u>... du group table</u> : " . $information_data["name"] . " - id : " . $id_group_table;
										print "&nbsp;&nbsp;&nbsp;&nbsp;<u>Network Agregation</u> : " . $network_aggregation."<br/>";
										echo implode("<br/>",$this->msg[$network_aggregation])."<br/>";
										unset($this->msg[$network_aggregation]);
									}
								}
							}
						}
					}
				}
			}
		}
	}


	// Insère des données dans la table edw_alarm_log_error
	function insert_alarm_log_error($id_alarm, $ta, $ta_value, $na, $nb_result, $hn, $hn_value, $type,$flag_axe3,$seuil) {
		
		if ($this->debug)
			echo "<div class='function_call'>insert_alarm_log_error(id_alarm=<strong>$id_alarm</strong>, ta=<strong>$ta</strong>, ta_value=<strong>$ta_value</strong>, na=<strong>$na</strong>, nb_result=<strong>$nb_result</strong>, hn=<strong>$hn</strong>, hn_value=<strong>$hn_value</strong>, type=<strong>$type</strong>, flag_axe3=<strong>$flag_axe3</strong>, seuil=<strong>$seuil</strong>)</div>";
		
		if ($flag_axe3) {
			$insert = " --- ajoute l'erreur dans edw_alarm_log_error
				INSERT INTO edw_alarm_log_error (id_alarm, ta, ta_value, na, nb_result, type,critical_level,a3,a3_value)
					VALUES
					('$id_alarm','$ta','$ta_value','$na','$nb_result', '$type','$seuil','$hn','$hn_value')
			";
		} else {
			$insert = " --- ajoute l'erreur dans edw_alarm_log_error
				INSERT INTO edw_alarm_log_error (id_alarm, ta, ta_value, na, nb_result, type,critical_level)
					VALUES
					('$id_alarm','$ta','$ta_value','$na','$nb_result', '$type','$seuil')
			";
		}
		$result = $this->db->execute($insert);
	}


}
?>