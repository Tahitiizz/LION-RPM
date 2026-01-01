<?php

/**
 * Classe AlarmModel
 * 
 * Cette classe définit le modele de donnees des alarmes
 * 
 * @package Alarm
 * @author BAC b.audic@astellia.com
 * @version 1.0.0
 * @copyright 2008 Astellia
 *
 */

@session_start();

// include_once 'DataBaseConnectionMySQL.class.php';
include_once 'DataBaseConnectionOracle.class.php';

class AlarmModel
{
	/**
	 * Instance de la classe de connexion à la base de données
	 *
	 * @var object
	 */
	
	private $db_connection;
	
	/**
	 * Liste des messages affichés dans l'application
	 *
	 * @var array
	 */

	// 04/12/2008 - Modif. benoit : correction du bug 7754. Remplacement du message de 'A_TITRE_FENETRE_NETWORK_ELEMENT_SELECTION_ALARMES_SETUP' de "Network Element Selection" à "ID/Account Selection"

	private $messages = array(	
								'A_TASK_SCHEDULER_SCHEDULE_SETUP_LIST_TITLE_AVAILABLE_GROUP'=>'Available groups',
								'A_TASK_SCHEDULER_SCHEDULE_SETUP_LIST_TITLE_SUSCRIBED_GROUP'=>'Subscribed groups',
								'A_ALARM_FORM_LABEL_ALARM_NAME'=>'Alarm Name',
								'A_ALARM_FORM_LABEL_NETWORK_LEVEL'=>'Level',
								'A_ALARM_FORM_LABEL_TIME_RESOLUTION'=>'Time resolution',
								'A_ALARM_DESACTIVATED_INFORMATION'=>'Alarms in orange are desactivated',
								'A_PAUTO_CONFIRM_OBJECT_DELETE'=>'Are you sure you want to delete this element ?',
								'A_ALARM_ALARM_LIST'=>'Alarm List',
								'A_ALARM_GROUP_SELECTOR'=>'Group selector',
								'A_ALARM_EMAIL_SETUP_TITLE'=>'Email Sending',
								'A_ALARM_NO_ALARM_REGISTRED'=>'No alarm registered.',
								'A_ALARM_DELETE_CONFIRMATION_TITLE'=>'Deletion confirmation',
								'A_ALARM_BTN_NEW_ALARM'=>'New $1 Alarm',
								'G_PROFILE_FORM_LINK_BACK_TO_THE_LIST'=>'Back to the list',
								'A_ALARM_ALARM_PROPERTIES_LABEL'=>'Alarm properties',
								'A_ALARM_FORM_LABEL_ALARM_DESCRIPTION'=>'Alarm Description',
								'A_ALARM_FORM_LABEL_ALARM_CALCULATION_ACTIVATED'=>'Alarm calculation activated',
								'A_ALARM_FORM_LABEL_ADDITIONAL_FIELD'=>'Additional field',
								'A_ALARM_FORM_LABEL_TRIGGER'=>'Trigger',
								'A_ALARM_FORM_FIELDSET_ADDITIONAL_FIELD_LIST'=>'Additional field list',
								'A_ADVANCED_CONTEXT_TYPE_LABEL'=>'Type',
								'A_ALARM_SELECT_MAKE_A_SELECTION_LABEL'=>'Make a Selection',
								'A_ALARM_NFORMATION_TRIGGER'=>'(Triggers are linked using an \'AND\' condition)',
								'A_ALARM_FORM_FIELDSET_TRIGGER_LIST'=>'Trigger list',
								'A_ALARM_FORM_BTN_ADD_TRIGGER'=>'Add a trigger',
								'A_TITRE_FENETRE_NETWORK_ELEMENT_SELECTION_ALARMES_SETUP'=>'ID/Account Selection',
								'A_NO_NA_FOR_FAMILY_LABEL'=>'No network agregation for $1',
								'A_NO_TA_LABEL'=>'No time agregation'
							);

	/**
	 * Constructeur de la classe
	 *
	 */

	 public function __construct($db_conf_url = '')
	 {
		if(empty($_SESSION['ast_DBConnection'])) {
			$this->db_connection = new DataBaseConnectionOracle($db_conf_url);
			$_SESSION['ast_DBConnection'] = serialize($this->db_connection);
		}
		else 
		{
			$this->db_connection = unserialize($_SESSION['ast_DBConnection']);
		}
	}
		
	/**
	 * Retourne la liste des alarmes d'une famille et d'un type donné
	 *
	 * @param string $family famille des alarmes recherchées
 	 * @param string $type type des alarmes recherchées
	 * @return array la liste des alarmes recherchées
	 */

	function getAlarmList($family, $type)
	{
		$sql = " -- getAlarmList($family, $type)
				SELECT DISTINCT alarm_id, alarm_name, network, time, on_off, (
					SELECT CASE WHEN (COUNT(id_group) > 0) THEN true ELSE false END
					FROM sys_alarm_email_sender
					WHERE id_alarm = alarm_id
						AND time_aggregation = time
						AND alarm_type = 'alarm_$type'
					) AS send_mail
				FROM sys_def_alarm_$type
				WHERE family = '$family'
				ORDER BY alarm_id ";
		// le probleme de la requête précédente, c'est qu'Oracle n'aime pas true et false comme valeurs

		$sql = " -- getAlarmList($family, $type)
				SELECT DISTINCT alarm_id, alarm_name, network, time, on_off, (
					SELECT CASE WHEN (COUNT(id_group) > 0) THEN 1 ELSE 0 END
					FROM sys_alarm_email_sender
					WHERE id_alarm = alarm_id
						AND time_aggregation = time
						AND alarm_type = 'alarm_$type'
					) AS send_mail
				FROM sys_def_alarm_$type
				-- WHERE family = '$family'
				ORDER BY alarm_id ";

		// echo "<pre>".$sql;exit;
		$req = $this->db_connection->executeQuery($sql);
		return $this->db_connection->getQueryResults($req);
	}
	
	/**
	 * Retourne la liste des groupes de destinataires de l'alarme
	 *
	 * @param integer $alarm_id identifiant de l'alarme
	 * @param string $alarm_type type de l'alarme
	 * @return array la liste des groupes destinataires de l'alarme
	 */

	function getAlarmSuscribers($alarm_id, $alarm_type)
	{
		//$sql = "SELECT DISTINCT saes.id_group AS id, sug.group_name AS name FROM sys_alarm_email_sender saes, sys_user_group sug WHERE saes.id_alarm=".$alarm_id." AND saes.alarm_type='alarm_".$alarm_type."' AND saes.id_group = sug.id_group ORDER BY sug.group_name";

		$sql = " -- alarmModel->getAlarmSuscribers($alarm_id, $alarm_type)
				SELECT DISTINCT saes.id_group AS id,
					agr.AGR_NAME AS name
				FROM sys_alarm_email_sender saes, alk_group agr
				WHERE saes.id_alarm='$alarm_id'
					AND saes.alarm_type='alarm_$alarm_type'
					AND saes.id_group = agr.AGR_ID
				ORDER BY agr.AGR_NAME ";

		$req = $this->db_connection->executeQuery($sql);
		return $this->db_connection->getQueryResults($req);
	}
	
	/**
	 * Permet de sauvegarder la sélection de groupes de destinataires attachés à une alarme
	 *
	 * @param integer $alarm_id identifiant de l'alarme
	 * @param string $alarm_ta valeur de l'agregation temporelle de l'alarme
	 * @param string $alarm_type type de l'alarme
	 * @param array $suscribers_list liste des destinataires de l'alarme
	 * @return boolean état de l'insertion des destinataires de l'alarme dans la BD
	 */

	function saveAlarmSuscribers($alarm_id, $alarm_ta, $alarm_type, $suscribers_list)
	{		
		$insert_ok = true;	// etat de l'insertion

		// On supprime tout d'abord la liste des destinataires déja défini pour l'alarme
		$sql = " -- alarmModel->saveAlarmSuscribers($alarm_id, $alarm_ta, $alarm_type, $suscribers_list)
				DELETE FROM sys_alarm_email_sender
				WHERE id_alarm = '$alarm_id'
					AND time_aggregation = '$alarm_ta'
					AND alarm_type = 'alarm_$alarm_type' ";
		$this->db_connection->executeQuery($sql);

		//echo $sql;

		// On (re)insere ensuite la liste des destinataires sélectionnés pour l'alarme
		for ($i=0; $i < count($suscribers_list); $i++) {
			$sql = " -- alarmModel->saveAlarmSuscribers($alarm_id, $alarm_ta, $alarm_type, $suscribers_list)
					INSERT INTO sys_alarm_email_sender (id_alarm, id_group, time_aggregation, alarm_type)
					VALUES ('$alarm_id', '{$suscribers_list[$i]}', '$alarm_ta', 'alarm_$alarm_type') ";
			$insert_ok = $this->db_connection->executeQuery($sql); 
		}
		
		return $insert_ok;
	}
	
	/**
	 * Retourne les groupes disponibles dans l'application
	 *
	 * @return array la liste des groupes de l'application
	 */

	function getApplicationGroups()
	{
		//$sql = "SELECT DISTINCT id_group AS id, group_name AS name FROM sys_user_group WHERE on_off = 1 ORDER BY group_name ASC";
		$sql = " -- alarmModel->getApplicationGroups()
			SELECT AGR_ID AS id, AGR_NAME AS name FROM alk_group ORDER BY name";
		$req = $this->db_connection->executeQuery($sql);
		return $this->db_connection->getQueryResults($req);
	}
	
	/**
	 * Retourne les propirétés d'une alarme
	 *
	 * @param integer $alarm_id identifiant de l'alarme
	 * @param string $family famille de l'alarme
	 * @param string $type type de l'alarme
	 * @return array les propriétés de l'alarme
	 */

	function getAlarmProperties($alarm_id, $family, $type = "static")
	{
		// Note : peut être compléter la requete avec une sous-requete sur la table des elements réseaux

		$sql = " -- alarmModel->getAlarmProperties($alarm_id, $family, $type)
			SELECT * FROM sys_def_alarm_$type WHERE alarm_id = '$alarm_id' -- AND family = '$family' ";
		$req = $this->db_connection->executeQuery($sql);
		return $this->db_connection->getQueryResults($req, "one");
	}
	
	/**
	 * Retourne les triggers définis pour une alarme
	 *
	 * @param integer $alarm_id identifiant de l'alarme
	 * @param string $family famille de l'alarme
	 * @param string $type type de l'alarme
	 * @return array la liste des triggers de l'alarme
	 */

	function getAlarmTrigger($alarm_id, $family, $type = "static")
	{
		$sql = " -- alarmModel->getAlarmTrigger($alarm_id, $family, $type)
				SELECT DISTINCT alarm_trigger_data_field, alarm_trigger_operand, alarm_trigger_type, alarm_trigger_value, critical_level
				FROM sys_def_alarm_$type
				WHERE alarm_id = '$alarm_id'
				--	AND family = '$family'
					AND alarm_trigger_data_field IS NOT NULL ";	// AND on_off = 1";
		$req = $this->db_connection->executeQuery($sql);
		return $this->db_connection->getQueryResults($req);
	}
	
	/**
	 * Retourne les champs additionnels d'une alarme
	 *
	 * @param integer $alarm_id identifiant de l'alarme
	 * @param string $family famille de l'alarme
	 * @param string $type type de l'alarme
	 * @return array la liste des champs additionnels de l'alarme
	 */

	function getAlarmAdditionnalFields($alarm_id, $family, $type = "static")
	{
		$sql = " -- alarmModel->getAlarmAdditionnalFields($alarm_id, $family, $type)
				SELECT DISTINCT additional_field AS id, additional_field_type AS type
				FROM sys_def_alarm_$type
				WHERE alarm_id = '$alarm_id'
				--	AND family = '$family'
					AND alarm_trigger_data_field IS NULL ";// AND on_off = 1";
		
		$req = $this->db_connection->executeQuery($sql);
		return $this->db_connection->getQueryResults($req);		
	}
	
	/**
	 * Retourne les valeurs des triggers d'une alarme en fonction d'un niveau de criticité sélectionné
	 *
	 * @param integer $alarm_id identifiant de l'alarme
	 * @param string $family famille de l'alarme
	 * @param string $criticity le niveau de criticité de l'alarme
	 * @param string $type type de l'alarme
	 * @return array les valeurs des triggers de l'alarme
	 */

	function getAlarmCriticityValues($alarm_id, $family, $criticity, $type = "static")
	{
		$sql = " -- alarmModel->getAlarmCriticityValues($alarm_id, $family, $criticity, $type)
				SELECT DISTINCT alarm_trigger_data_field AS id, alarm_trigger_operand AS operand,
					alarm_trigger_type AS type,
					alarm_trigger_value AS value
				FROM sys_def_alarm_$type
				WHERE alarm_id = '$alarm_id'
				--	AND family = '$family'
					AND critical_level = '$criticity'
					AND alarm_trigger_data_field IS NOT NULL";// AND on_off = 1";
		$req = $this->db_connection->executeQuery($sql);

		$results = $this->db_connection->getQueryResults($req);

		if (count($results) == 0) {
			$sql = " -- alarmModel->getAlarmCriticityValues($alarm_id, $family, $criticity, $type)
					SELECT DISTINCT alarm_trigger_data_field AS id,
						alarm_trigger_type AS type
					FROM sys_def_alarm_$type
					WHERE alarm_id = '$alarm_id'
				--		AND family = '$family'
						AND alarm_trigger_data_field IS NOT NULL";// AND on_off = 1";
			$req = $this->db_connection->executeQuery($sql);
			$results = $this->db_connection->getQueryResults($req);
		}

		return $results;	
	}
	
	/**
	 * Retourne les agregations reseaux d'une famille de l'application
	 *
	 * @param string $family famille des na recherchées
 	 * @return array la liste des na de la famille
	 */

	function getNetworkAgregation($family)
	{
		/*$sql = "SELECT DISTINCT agregation, agregation_label FROM sys_definition_network_agregation WHERE family = '".$family."' ORDER BY agregation_rank DESC";
		$req = $this->db_connection->executeQuery($sql);
		return $this->db_connection->getQueryResults($req);*/

		return array(array('agregation'=>'Global', 'agregation_label'=>'Global'), array('agregation'=>'Account', 'agregation_label'=>'Account'), array('agregation'=>'Sub-account', 'agregation_label'=>'Sub-account'));
	}
	
	/**
	 * Retourne les agregations temporelles de l'application
	 *
	 * @return array la liste des agregations temporelles de l'application
	 */

	function getTimeAgregation()
	{
		/*$sql = "SELECT DISTINCT agregation, agregation_label FROM sys_definition_time_agregation ORDER BY agregation_rank ASC";
		$req = $this->db_connection->executeQuery($sql);
		return $this->db_connection->getQueryResults($req);*/

		return array(array('agregation'=>'day', 'agregation_label'=>'Day'), array('agregation'=>'week', 'agregation_label'=>'Week'), array('agregation'=>'month', 'agregation_label'=>'Month'));
	}

	/**
	 * Retourne le libellé d'une na sélectionnée
	 *
	 * @param string $na nom de la na
	 * @param string $family famille de la na
	 * @return array libellé de la na sélectionnée
	 */
	
	function getNALabel($na, $family)
	{
		/*$sql = "SELECT agregation_label FROM sys_definition_network_agregation WHERE agregation = '".$na."' AND family = '".$family."'";
		$req = $this->db_connection->executeQuery($sql);
		return $this->db_connection->getQueryResults($req, "one");*/

		return array('agregation_label' => $na);
	}

	function getTALabel($ta)
	{
		/*$sql = "SELECT agregation_label FROM sys_definition_time_agregation WHERE agregation = '".$ta."'";
		$req = $this->db_connection->executeQuery($sql);
		return $this->db_connection->getQueryResults($req, "one");*/

		return array('agregation_label' => ucfirst($ta));
	}
	
	/**
	 * Permet de supprimer une alarme de la BD de l'application
	 *
	 * @param integer $alarm_id identifiant de l'alarme
	 * @param string $alarm_type type de l'alarme
	 * @param string $alarm_family famille de l'alarme
	 * @param string $alarm_ta valeur de la ta de l'alarme
	 * @return boolean etat de la suppression de l'alarme dans la BD
	 */

	function dropAlarm($alarm_id, $alarm_type, $alarm_family, $alarm_ta, $del_sender = true)
	{
		$deletion_state = false;
		
		// Suppression des lignes de la table 'sys_alarm_email_sender' relatives à l'envoi des mails
		
		if ($del_sender) {
			$sql = " --  alarmModel->dropAlarm($alarm_id, $alarm_type, $alarm_family, $alarm_ta, $del_sender)
					DELETE FROM sys_alarm_email_sender
					WHERE id_alarm = '$alarm_id'
						AND time_aggregation = '$alarm_ta'
						AND alarm_type = 'alarm_$alarm_type' ";
			$deletion_state = $this->db_connection->executeQuery($sql);			
		}

		// Suppression des valeurs de na de l'alarme définies dans la table 'sys_def_alarm_net_elts'
		$sql = " --  alarmModel->dropAlarm($alarm_id, $alarm_type, $alarm_family, $alarm_ta, $del_sender)
				DELETE FROM sys_def_alarm_net_elts
				WHERE id_alarm = '$alarm_id'
					AND type_alarm = 'alarm_$alarm_type' ";
		$deletion_state = $this->db_connection->executeQuery($sql);
		
		// Suppression de la définition de l'alarme
		$sql = " -- alarmModel->dropAlarm($alarm_id, $alarm_type, $alarm_family, $alarm_ta, $del_sender)
				DELETE FROM sys_def_alarm_$alarm_type
				WHERE alarm_id = '$alarm_id'
				--	AND family = '$alarm_family' ";
		$deletion_state = $this->db_connection->executeQuery($sql);

		return $deletion_state;
	}
	
	/**
	 * Retourne les niveaux de criticité existants dans l'application
	 *
	 * @return la liste des niveaux de criticité
	 */

	function getCriticityLevels()
	{
		// Note : recupérer les couleurs via une requete en base
		
		/*return array(	
						array('id'=>"critical", 'label'=>"Critical", 'color'=>"#FF0000"),
						array('id'=>"major", 'label'=>"Major", 'color'=>"#FAB308"),
						array('id'=>"minor", 'label'=>"Minor", 'color'=>"#F7FA08")
					);*/

		return (array(array('id'=>"critical", 'label'=>"", 'color'=>"")));
	}
	
	/**
	 * Retourne les opérandes des triggers disponbiles dans l'application
	 *
	 * @return array la liste des opérandes disponibles
	 */

	function getTriggerOperands()
	{
		//return array('none', '=', '<=', '>=', '<', '>');

		return array('=');
	}
	
	/**
	 * Retourne les types de triggers disponibles dans l'application
	 *
	 * @return la liste des types de triggers disponibles
	 */

	function getTriggerTypes()
	{
		//return array(array('id'=>"kpi", 'label'=>"KPI"), array('id'=>"raw", 'label'=>"Raw Counter"));

		return array(array('id'=>"qos", 'label'=>"Qos"));
	}
	
	/**
	 * Retourne la liste des valeurs d'un type de trigger sélectionné
	 *
	 * @param string $family famille auquel appartient le type
	 * @param string $type nom du type
 	 * @return array liste des valeurs du type de triggers
	 */

	function getTriggerTypeValues($family, $type = '')
	{
		$type_values = array();

		/*$raws = $this->getRaws($family);

		for ($i=0; $i < count($raws); $i++) {
			$type_values['raw'][$raws[$i]['id']] = array('label' => $raws[$i]['label'], 'label_complete' => $raws[$i]['label_complete']);
		}

		$kpis = $this->getKpis($family);

		for ($i=0; $i < count($kpis); $i++) {
			$type_values['kpi'][$kpis[$i]['id']] = array('label' => $kpis[$i]['label'], 'label_complete' => $kpis[$i]['label_complete']);
		}*/

		$qos = $this->getKpis($family);
		
		//echo "<pre>";print_r($qos);exit;

		for ($i=0; $i < count($qos); $i++) {
			$type_values['qos'][$qos[$i]['id']] = array('label' => $qos[$i]['name'], 'label_complete' => $qos[$i]['name']);
		}

		return $type_values;
	}
	
	/**
	 * Retourne la liste des valeurs de type "raw"
	 *
	 * @param string $family famille auquelles appartiennent les valeurs de type "raw"
	 * @return array la liste des valeurs du type "raw"
	 */

	function getRaws($family)
	{
		$sql = " --   alarmModel->getRaws($family)
				SELECT DISTINCT edw_field_name AS id,
					edw_field_name_label AS label_complete,
					CASE WHEN (LENGTH(edw_field_name_label) > 52) THEN
						CONCAT(SUBSTRING(edw_field_name_label,1,52), '...')
					ELSE	edw_field_name_label END AS label
				FROM sys_field_reference
				WHERE edw_group_table = (
					SELECT edw_group_table FROM sys_definition_group_table -- WHERE family = '$family'
					)
					AND visible = 1
				ORDER BY edw_field_name_label ASC ";

		$req = $this->db_connection->executeQuery($sql);
		return $this->db_connection->getQueryResults($req);		
	}
	

	/**
	 * Retourne la liste des valeurs de type "kpi"
	 *
	 * @param string $family famille auquelles appartiennent les valeurs de type "kpi"
	 * @return array la liste des valeurs du type "kpi"
	 */	

	function getKpis($family)
	{
		/*$sql =	 " SELECT DISTINCT kpi_name AS id, kpi_label AS label_complete,"
				." CASE WHEN (LENGTH(kpi_label) > 52) THEN CONCAT(SUBSTRING(kpi_label,1,52), '...') ELSE kpi_label END AS label"
				." FROM sys_definition_kpi"
				." WHERE edw_group_table = (SELECT edw_group_table FROM sys_definition_group_table WHERE family = '".$family."')"
				." AND on_off = 1 AND visible = 1 ORDER BY kpi_label ASC";*/

		/*$sql = 'SELECT ID AS id, NAME AS label_complete, CASE WHEN (LENGTH(NAME) > 52) THEN CONCAT(SUBSTRING(NAME,1,52), \'...\') ELSE NAME END AS label
		FROM
			(
			SELECT
				s1.ASRV_ID AS ID,
				CONCAT(
					IF(s4.ASRV_SHORT_NAME IS NULL,"",CONCAT(s4.ASRV_SHORT_NAME,"/")),
					IF(s3.ASRV_SHORT_NAME IS NULL,"",CONCAT(s3.ASRV_SHORT_NAME,"/")),
					IF(s2.ASRV_SHORT_NAME IS NULL,"",CONCAT(s2.ASRV_SHORT_NAME,"/")),
					s1.ASRV_SHORT_NAME
					) AS NAME,
		CONCAT(
			IFNULL(s4.ASRV_ROW,""),
			IFNULL(s3.ASRV_ROW,""),
			IFNULL(s2.ASRV_ROW,""),
			IFNULL(s1.ASRV_ROW,"")) AS NORDER
			FROM
				alk_services s1
				LEFT JOIN alk_services s2 ON (s1.ASRV_PARENT = s2.ASRV_ID)
				LEFT JOIN alk_services s3 ON (s2.ASRV_PARENT = s3.ASRV_ID)
				LEFT JOIN alk_services s4 ON (s3.ASRV_PARENT = s4.ASRV_ID)
			-- WHERE s1.ASRV_LEVEL = 4 -- POSSIBILITE DE NE PRENDRE QU\'UN SEUL NIVEAU
			ORDER BY NORDER
			)sr;';*/

			// mySQL version
			$sql = 'SELECT ID, NAME
			FROM
				(
				SELECT
					s1.ASRV_ID AS ID,
					CONCAT(
						IF(s4.ASRV_SHORT_NAME IS NULL,"",CONCAT(s4.ASRV_SHORT_NAME,"/")),
						IF(s3.ASRV_SHORT_NAME IS NULL,"",CONCAT(s3.ASRV_SHORT_NAME,"/")),
						IF(s2.ASRV_SHORT_NAME IS NULL,"",CONCAT(s2.ASRV_SHORT_NAME,"/")),
						s1.ASRV_SHORT_NAME
						) AS NAME,
			CONCAT(
				IFNULL(s4.ASRV_ROW,""),
				IFNULL(s3.ASRV_ROW,""),
				IFNULL(s2.ASRV_ROW,""),
				IFNULL(s1.ASRV_ROW,"")) AS NORDER
				FROM
					alk_services s1
					LEFT JOIN alk_services s2 ON (s1.ASRV_PARENT = s2.ASRV_ID)
					LEFT JOIN alk_services s3 ON (s2.ASRV_PARENT = s3.ASRV_ID)
					LEFT JOIN alk_services s4 ON (s3.ASRV_PARENT = s4.ASRV_ID)
				-- WHERE s1.ASRV_LEVEL = 4 -- POSSIBILITE DE NE PRENDRE QU\'UN SEUL NIVEAU
				-- WHERE s1.ASRV_ENABLED!=0
				ORDER BY NORDER
				)sr;';

			// Oracle version
			$sql = " --   alarmModel->getKpis($family)
			SELECT ID, NAME
			FROM
				(
				SELECT
					s1.ASRV_ID AS ID,

					DECODE (s4.ASRV_SHORT_NAME, NULL,'',CONCAT(s4.ASRV_SHORT_NAME,'/'))
					|| DECODE (s3.ASRV_SHORT_NAME, NULL,'',CONCAT(s3.ASRV_SHORT_NAME,'/'))
					|| DECODE (s2.ASRV_SHORT_NAME, NULL,'',CONCAT(s2.ASRV_SHORT_NAME,'/'))
					|| s1.ASRV_SHORT_NAME AS NAME,

					s4.ASRV_ROW || s3.ASRV_ROW || s2.ASRV_ROW || s1.ASRV_ROW AS NORDER

				FROM
					alk_services s1
					LEFT JOIN alk_services s2 ON (s1.ASRV_PARENT = s2.ASRV_ID)
					LEFT JOIN alk_services s3 ON (s2.ASRV_PARENT = s3.ASRV_ID)
					LEFT JOIN alk_services s4 ON (s3.ASRV_PARENT = s4.ASRV_ID)
				-- WHERE s1.ASRV_LEVEL = 4 -- POSSIBILITE DE NE PRENDRE UN SEUL NIVEAU
				-- WHERE s1.ASRV_ENABLED!=0
				ORDER BY NORDER
				) sr ";
		
		$req = $this->db_connection->executeQuery($sql);
		return $this->db_connection->getQueryResults($req);	
	}
	
	/**
	 * Retourne les valeurs des na (appelées également ne) d'une famille
	 *
	 * @param string $na nom de la na sélectionnée
	 * @param string $family famille de la na
	 * @return array liste des ne
	 */

	function getNE($na, $family = '')
	{
		// Note : remplacer edw_object_1_ref par une recherche de la table de référence en fonction de la famille dans la BD

		/*$sql =	 " SELECT DISTINCT ".$na." AS id,"
				." CASE WHEN ".$na."_label IS NULL THEN CONCAT('(',".$na.",')') ELSE ".$na."_label END AS label"
				." FROM edw_object_1_ref WHERE $na is NOT NULL ORDER BY ".$na;
		
		$req = $this->db_connection->executeQuery($sql);
		$res = $this->db_connection->getQueryResults($req);*/

		switch ($na) {
			case 'Global' :
				
				return array(array('id'=>"all_".$na, 'label' => "All values"));

			break;
			
			case 'Account' :

				$sql =	" --   alarmModel->getNE($na, $family)
					SELECT grp_group AS id, grp_name AS label
					FROM groups
					WHERE grp_group!=0
						AND grp_subgroup=0
					ORDER BY label ";
		
				$req = $this->db_connection->executeQuery($sql);
				$res = $this->db_connection->getQueryResults($req);

			break;

			case 'Sub-account' :

				$sql =	" --   alarmModel->getNE($na, $family)
					SELECT g1.grp_group || '_' || g2.grp_subgroup   AS id,
						'[' || g1.grp_name || ']' || g2.grp_name   AS label
					FROM groups g1
						LEFT JOIN groups g2 ON (g1.grp_group=g2.grp_group AND g2.grp_subgroup>0)
					WHERE g1.grp_group!=0
						AND g1.grp_subgroup=0
						AND g2.grp_subgroup IS NOT NULL
					ORDER BY label ";

				$req = $this->db_connection->executeQuery($sql);
				$res = $this->db_connection->getQueryResults($req);
			
			break;
		}
		return array_merge(array(array('id'=>"all_".$na, 'label' => "All values")), $res);
	}
	
	/**
	 * Retourne la liste des ne d'une alarme
	 *
	 * @param integer $alarm_id identifiant de l'alarme
	 * @param string $type type de l'alarme
	 * @return array liste des ne de l'alarme
	 */

	function getAlarmNetworkElements($alarm_id, $type = 'static')
	{
		$sql = " --   alarmModel->getAlarmNetworkElements($alarm_id, $type)
			SELECT * FROM sys_def_alarm_net_elts WHERE id_alarm = '$alarm_id' AND type_alarm = 'alarm_$type' ";
		$req = $this->db_connection->executeQuery($sql);		
		$res = $this->db_connection->getQueryResults($req, "one");
		
		return $res;
	}
	
	/**
	 * Sauvegarde de la définition d'une alarme dans la BD de l'application
	 *
	 * @param integer $a_id identifiant de l'alarme
	 * @param string $a_type type de l'alarme
	 * @param string $a_family famille de l'alarme
	 * @param string $a_name nom de l'alarme
	 * @param string $a_desc description de l'alarme
	 * @param string $a_na agregation réseau de l'alarme
	 * @param array $a_ne liste des ne définies pour l'alarme
	 * @param string $a_ta agregation temporelle de l'alarme
	 * @param boolean $a_activate état de l'activation de l'alarme
	 * @param array $a_triggers liste des triggers de l'alarme
	 * @param array $a_additionnals liste des champs additionnels de l'alarme
	 * @return boolean état de l'insertion de l'alarme dans la BD
	 */

	function saveAlarmDefinition($a_id, $a_type, $a_family, $a_name, $a_desc, $a_na, $a_ne, $a_ta, $a_activate, $a_triggers, $a_additionnals = array())
	{
		// Si l'alarme est déja défini, on la supprime avant de la recréer avec ses nouvelles caractéristiques

		if ($a_id != '') {
			$this->dropAlarm($a_id, $a_type, $a_family, $a_ta, false);
		}
		else // Sinon, on définit un identifiant pour la nouvelle alarme
		{
			$sql = " --   alarmModel->saveAlarmDefinition()
				SELECT MAX(alarm_id)+1 AS alarm_id FROM sys_def_alarm_".$a_type;
			$req = $this->db_connection->executeQuery($sql);
			$res = $this->db_connection->getQueryResults($req, "one");
			
			$a_id = ($res['alarm_id'] != "") ? $res['alarm_id'] : 1;
		}

		// (Re)Creation de l'alarme

		$creation_state = false;

		// ** Insertion des lignes définissant les triggers dans 'sys_def_alarm_[type]'

		for ($i=0; $i < count($a_triggers); $i++) {
			foreach ($a_triggers[$i] as $criticity => $trigger) {
				//$sql = "INSERT INTO sys_def_alarm_".$a_type." (alarm_id, alarm_name, alarm_trigger_data_field, alarm_trigger_operand, alarm_trigger_type, id_group_table, network, time, family, alarm_trigger_value, critical_level, on_off) VALUES (".$a_id.", '".$a_name."', '".$trigger['data_field']."', '".$trigger['operand']."', '".$trigger['type']."', (SELECT id_ligne FROM sys_definition_group_table WHERE family = '".$a_family."' LIMIT 1), '".$a_na."', '".$a_ta."', '".$a_family."', '".$trigger['value']."', '".$criticity."', ".$a_activate.")";

				$sql = " --   alarmModel->saveAlarmDefinition()
					INSERT INTO sys_def_alarm_$a_type (alarm_id, alarm_name, alarm_trigger_data_field, alarm_trigger_operand, alarm_trigger_type, id_group_table, network, time, family, alarm_trigger_value, description, critical_level, on_off)
					VALUES (".$a_id.", '".$a_name."', '".$trigger['data_field']."', '".$trigger['operand']."', '".$trigger['type']."', 0, '".$a_na."', '".$a_ta."', '".$a_family."', '".$trigger['value']."', '".$a_desc."', '".$criticity."', ".$a_activate.")";

				//echo $sql."<br/>";

				$creation_state = $this->db_connection->executeQuery($sql);
			}
		}

		// ** Insertion des lignes définissant les champs additionnels dans 'sys_def_alarm_[type]'

		// Note : les champs additionnels n'étant pas utilisés pour l'instant, on ne traite pas leur insertion en base

		// ** Insertion des lignes définissant les valeurs de na de l'alarme dans la table 'sys_def_alarm_net_elts'
		
		if ($creation_state) {
			$sql = " --   alarmModel->saveAlarmDefinition()
				INSERT INTO sys_def_alarm_net_elts (id_alarm, type_alarm, not_in, lst_alarm_compute)
				VALUES ($a_id, 'alarm_$a_type', 0, '$a_ne') ";

			//echo $sql."<br/>";
			$creation_state = $this->db_connection->executeQuery($sql);			
		}

		return $creation_state;		
	}
	
	/**
	 * Permet de transcrire les messages de l'application à partir de leurs identifiants
	 *
	 * @param string $word_id identifiant du message
	 * @return string le message traduit
	 */

	function translate($word_id)
	{
		if($this->messages[$word_id] == ""){
			return '<b style="color:red">'.$word_id.'</b>';
		}
		else 
		{
			$txt = $this->messages[$word_id];

			// Si le nombre d'arguments est supérieur à 1 (le premier étant l'id du message)
			// on remplace les arguments dans le texte
			$numArgs = func_num_args();
			if ( $numArgs > 1 ) {
				$arg_list = func_get_args();
				// remplace tous les arguments dans le texte
				for ( $i = 1; $i < $numArgs; $i++ )
					$txt = str_replace('$'.$i, (string) $arg_list[$i], $txt);
			}
			// Supprime les arguments dans le texte qui sont en trop
			$txt = ereg_replace("(\\$[0-9]*)", "", $txt);

			return $txt;
		}
	}
}

?>