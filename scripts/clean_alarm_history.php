<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*
*	- maj 07/06/2007 Gwénaël : suppression des alarmes BH si elle est activée
*/
?>
<?
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
<?
/*
*	16/06/2006 - Copyright Acurio
*
*	Composant de base version cb_1.2.0.2p
*
*	Parser version gb_1.0.0b
*/
?>
<?
	/*
		Permet de supprimer les alarmes et leurs résultats qui sont
		trop vieux des tables edw_alarm et edw_alarm_detail

		- maj 07 07 2006 christophe : ajout du clean history sur la table edw_alarm_log_error
	*/
	include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
	include_once($repertoire_physique_niveau0 . "php/database_connection.php");
	include_once($repertoire_physique_niveau0 . "php/edw_function.php");
?>
	<html>
	<head>
		<link rel="stylesheet" href="<?=$niveau0?>css/global_interface.css" type="text/css">
	</head>
	<body>
	<div class=texteGris>
<?

	global $database_connection;

	// On récupère les Time aggregation.
	$off_month = 	get_sys_global_parameters("offset_day") + get_sys_global_parameters("history_month") * 30; 	//converti l'interval en nombre de jours : en moyenne 30 jours par mois
	$off_week = 	get_sys_global_parameters("offset_day") + get_sys_global_parameters("history_week") * 7; 	//converti l'interval en nombre de jours : 7 jours par semaine
	$off_day = 		get_sys_global_parameters("offset_day") + get_sys_global_parameters("history_day");
	$off_hour = 	get_sys_global_parameters("offset_day") + get_sys_global_parameters("history_hour");

	$month = 	getmonth($off_month);
	$week =		getweek($off_week);
	$day =		getDay($off_day);
	$hour = 	getDay($off_day)."23";	// Exception pour la TA hour, on se base sur la dernière heure du jour.

	echo "<u>Month </u>: $month, <u>Week </u>: $week, <u>Day </u>: $day, <u>Hour </u>: $hour<br>";


	// Traitement de la TA month.
	echo "<br><b><li>Traitement de la TA month</b><br>";
	$query_month = "
		DELETE FROM edw_alarm_detail
		WHERE id_result IN (SELECT id_result FROM edw_alarm WHERE ta = 'month' AND ta_value <= '$month')
	";
	$res = pg_query($database_connection,$query_month);
	echo pg_affected_rows($res) . " = $query_month<br>";

	$query_month = "
		DELETE  FROM edw_alarm WHERE ta = 'month' AND ta_value <= '$month'
	";
	$res = pg_query($database_connection,$query_month);
	echo pg_affected_rows($res) . " = $query_month<br>";

	$query_month = "
		DELETE  FROM edw_alarm_log_error WHERE ta = 'month' AND ta_value <= '$month'
	";
	$res = pg_query($database_connection,$query_month);
	echo pg_affected_rows($res) . " = $query_month<br>";

	// Traitement de la TA week.
	echo "<br><b><li>Traitement de la TA week</b><br>";
	$query_week = "
		DELETE FROM edw_alarm_detail
		WHERE id_result IN (SELECT id_result FROM edw_alarm WHERE ta = 'week' AND ta_value <= '$week')
	";
	$res = pg_query($database_connection,$query_week);
	echo pg_affected_rows($res) . " = $query_week<br>";

	$query_week = "
		DELETE  FROM edw_alarm WHERE ta = 'week' AND ta_value <= '$week'
	";
	$res = pg_query($database_connection,$query_week);
	echo pg_affected_rows($res) . " = $query_week<br>";

	$query_week = "
		DELETE  FROM edw_alarm_log_error WHERE ta = 'week' AND ta_value <= '$week'
	";
	$res = pg_query($database_connection,$query_week);
	echo pg_affected_rows($res) . " = $query_week<br>";

	// Traitement de la TA day.
	echo "<br><b><li>Traitement de la TA day</b><br>";
	$query_day = "
		DELETE FROM edw_alarm_detail
		WHERE id_result IN (SELECT id_result FROM edw_alarm WHERE ta = 'day' AND ta_value <= '$day')
	";
	$res = pg_query($database_connection,$query_day);
	echo pg_affected_rows($res) . " = $query_day<br>";

	$query_day = "
		DELETE  FROM edw_alarm WHERE ta = 'day' AND ta_value <= '$day'
	";
	$res = pg_query($database_connection,$query_day);
	echo pg_affected_rows($res) . " = $query_day<br>";

	$query_day = "
		DELETE  FROM edw_alarm_log_error WHERE ta = 'day' AND ta_value <= '$day'
	";
	$res = pg_query($database_connection,$query_day);
	echo pg_affected_rows($res) . " = $query_day<br>";

	// Traitement de la TA hour.
	echo "<br><b><li>Traitement de la TA hour</b><br>";
	$query_hour = "
		DELETE FROM edw_alarm_detail
		WHERE id_result IN (SELECT id_result FROM edw_alarm WHERE ta = 'hour' AND ta_value <= '$hour')
	";
	$res = pg_query($database_connection,$query_hour);
	echo pg_affected_rows($res) . " = $query_hour<br>";

	$query_hour = "
		DELETE  FROM edw_alarm WHERE ta = 'hour' AND ta_value <= '$hour'
	";
	$res = pg_query($database_connection,$query_hour);
	echo pg_affected_rows($res) . " = $query_hour<br>";

	$query_hour = "
		DELETE  FROM edw_alarm_log_error WHERE ta = 'hour' AND ta_value <= '$hour'
	";
	$res = pg_query($database_connection,$query_hour);
	echo pg_affected_rows($res) . " = $query_hour<br>";


	//modif 07/06/2007 Gwénaël
		// Suppression des alarmes pour la BH si elle est activée
	$queryBH = "SELECT agregation FROM sys_definition_time_agregation WHERE on_off = 1 AND bh_list = 'bh'";
	$resBH = pg_query($database_connection,$queryBH);
	
	if( pg_num_rows($resBH) > 0) {
		$bh['month_bh'] = $month;
		$bh['week_bh'] = $week;
		$bh['day_bh'] = $day;
		foreach($bh as $ta => $ta_value) {
			echo "<br><b><li>Traitement de la TA $ta</b><br>";
			$query_bh = "
				DELETE FROM edw_alarm_detail
				WHERE id_result IN (SELECT id_result FROM edw_alarm WHERE ta = '$ta' AND ta_value <= '$ta_value')
			";
			$res_bh = pg_query($database_connection,$query_bh);
			echo pg_affected_rows($res_bh) . " = $query_bh<br>";

			$query_bh = "
				DELETE  FROM edw_alarm WHERE ta = '$ta' AND ta_value <= '$ta_value'
			";
			$res_bh = pg_query($database_connection,$query_bh);
			echo pg_affected_rows($res_bh) . " = $query_bh<br>";

			$query_bh = "
				DELETE  FROM edw_alarm_log_error WHERE ta = '$ta' AND ta_value <= '$ta_value'
			";
			$res_bh = pg_query($database_connection,$query_bh);
			echo pg_affected_rows($res_bh) . " = $query_bh<br>";
		
		}		
	}
?>
	</div>
	</body>
	</html>
