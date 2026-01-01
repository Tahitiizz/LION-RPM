<?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
	- maj 15/04/2008, benoit : correction du bug 6346
	- maj 15/04/2008, benoit : dans le cas du compute switch, on récupère la liste des heures à computer pour le message d'information
	- maj 15/04/2008, benoit : ajout de la fonction 'removeComputeSwitch()' permettant de remettre le compute dans son état d'origine si celui    doit être arrêté et qu'un compute swicth avait eu lieu
	- maj 15/04/2008, benoit : après suppression des tables et le stop des process, on restaure le compute_mode original si un compute_switch à   eu lieu via la fonction 'removeComputeSwitch()'
	- maj 27/05/2008 - maxime : On récupère par défault, le module enregistré en base dans sys_definition_messages_display
	- maj 22/04/2013 - GFS - Bug 33258 - [SUP][TA Huawei UTRAN][AVP NA][TELUS][robustesse]: Upload temporary files are not deleted during "maximum execution time"
*
*/
?>
<?php

	set_time_limit(36000);

	include_once(dirname(__FILE__)."/../php/environnement_liens.php");
	include_once(REP_PHYSIQUE_NIVEAU_0 . "php/database_connection.php");
	include_once(REP_PHYSIQUE_NIVEAU_0 . "php/edw_function.php");
	include_once(REP_PHYSIQUE_NIVEAU_0 . "php/postgres_functions.php");
    include_once REP_PHYSIQUE_NIVEAU_0 . 'class/libMail.class.php';

	// On verifie dans 'sys_process_en_cours' si un process est en cours

	$sql = "SELECT * FROM sys_process_encours WHERE encours = 1 AND done = 0";
	$req = pg_query($database_connection, $sql);

	if (pg_num_rows($req) > 0) {	// Un process est en cours
		
		$row = pg_fetch_array($req, 0);

		// On regarde si la différence entre la date de lancement du process et la date actuelle n'est pas supérieure au temps maximal d'execution autorisé pour un process (parametre 'max_process_execution_time' dans 'sys_global_parameters')

		$process_time = mktime(substr($row['date'], 8, 2), substr($row['date'], 10, 2), 0, substr($row['date'], 4, 2), substr($row['date'], 6, 2), substr($row['date'], 0, 4));

		if (((time()-$process_time)/60) > get_sys_global_parameters('max_process_execution_time')) {

			// 15/09/2011 BBX
            // BZ 23158 : on envoie un email pour prévenir que ça a crashé
            $email = get_sys_global_parameters('astellia_alert_recipient');
			if(!empty($email)) {
				// Product Information
				$db = Database::getConnection();
				$dbName = $db->getDbName();
				$productId = ProductModel::getProductFromDatabase($dbName, get_adr_server());                            
				$productModel  = new ProductModel($productId);
				$productValues = $productModel->getValues();
				$productLabel  = $productValues['sdp_label'];
                            
				// Product Final Name
				$customName = get_sys_global_parameters('application_custom_name');
				if(!empty($customName))
					$productLabel .= ' - '.$customName;
                            
				// Mail body
				$message = __T('A_PROCESS_KILLED_MAIL_ALERT',$productLabel);
                            
				// Mail sending
				$systemName = get_sys_global_parameters('system_name');
				$mailReply  = get_sys_global_parameters('mail_reply');
				$mail = new Mail('html');
				$mail->From("$systemName <$mailReply>");
				$mail->ReplyTo($mailReply);
				$mail->To($email);
				$mail->Subject("Crash on $productLabel");
				$mail->Body($message);
				$mail->Send();
			}  
            // FIN BZ 23158
			// 22/04/2013 - GFS - Bug 33258 - [SUP][TA Huawei UTRAN][AVP NA][TELUS][robustesse]: Upload temporary files are not deleted during "maximum execution time"
			purgeDirFromOldFiles(REP_PHYSIQUE_NIVEAU_0 . "upload/", 0);
			// On recupere les heures à réinserer suivant le type de process

			if ($row['process'] == 10) {	// Process "retrieve"

				$reinsert_msg = getRetrieveHoursAsMsg($database_connection);

				$message = (strpos(__T('A_RETRIEVE_PROCESS_TIME_EXCEEDED', $reinsert_msg), "Undefined") === false) ? __T('A_RETRIEVE_PROCESS_TIME_EXCEEDED', $reinsert_msg) : "Maximum execution time of Retrieve process exceeded.".$reinsert_msg;
			}
			else	// Process "compute"
			{
				$compute_mode		= get_sys_global_parameters('compute_mode');		// "hourly" ou "daily"
				$compute_processing	= get_sys_global_parameters('compute_processing');	// "hour" ou "day"
				$compute_switch		= get_sys_global_parameters('compute_switch');

				// 15/04/2008 - Modif. benoit : dans le cas du compute switch, on récupère la liste des heures à computer

				//if ($compute_mode == "hourly" && $compute_processing == "hour") {

				if (($compute_mode == "hourly" || $compute_switch == "hourly") && $compute_processing == "hour") {
					
					$first_value	= explode(get_sys_global_parameters('sep_axe3'), get_sys_global_parameters('hour_to_compute'));				
					$ta_value		= implode(", ", array_map("getHourLabel", $first_value));				
				}
				else
				{
					$first_value	= getDay(get_sys_global_parameters('offset_day'));
					$ta_value		= getTaValueToDisplay("day", $first_value);
				}

				$reload_msg = getComputeValuesToReload($database_connection, $compute_processing, $first_value);

				$message = (strpos(__T('A_COMPUTE_PROCESS_TIME_EXCEEDED', $ta_value, $reload_msg), "Undefined") === false) ? __T('A_COMPUTE_PROCESS_TIME_EXCEEDED', $ta_value, $reload_msg) : "Maximum execution time of Data Compute (".$ta_value.") exceeded.".$reload_msg;
			}

			// On insere dans le tracelog et dans le demon l'information "temps d'execution max. d'un process dépassé"
			// maj 27/05/2008 - maxime : On récupère par défault, le module enregistré en base dans sys_definition_messages_display
			sys_log_ast("Critical", get_sys_global_parameters("product_name"), __T("A_TRACELOG_MODULE_LABEL_CHECK_PROCESS_EXECUTION_TIME"), $message, "support_1", "");

			exec('echo "<font color=red><b>'.$message.'</b></font><br>" >> '.$repertoire_physique_niveau0.'file_demon/demon_'.date("Ymd").'.html');
		
			// Une fois l'information insérée, on supprime les tables temporaires et on stoppe les process pour débloquer le systeme

			pg_query("BEGIN");	// initialisation de la transaction
			
			trunkTempTables($database_connection);
			stopProcess($database_connection);

			// 15/04/2008 - Modif. benoit : après suppression des tables et le stop des process, on restaure le compute_mode original si un compute_switch à eu lieu via la fonction 'removeComputeSwitch()'

			removeComputeSwitch($database_connection);

			pg_query("COMMIT");	// on valide la transaction
		}
	}

	function getRetrieveHoursAsMsg($database_connection)
	{
		$reinsert_msg	= "";
		$retrieve_hours = array();

		// Selection des heures dans 'sys_flat_file_uploaded_list'

		$sql = "SELECT DISTINCT hour FROM sys_flat_file_uploaded_list WHERE hour IS NOT NULL";
		$req = pg_query($database_connection, $sql);

		if (pg_num_rows($req) > 0) {
			while ($row = pg_fetch_array($req)) {
				$retrieve_hours[substr($row['hour'], 0, 8)][] = substr($row['hour'], 8, 2);
			}
		}

		// Selection des heures dans 'sys_w_tables_list'

		$sql = "SELECT DISTINCT hour FROM sys_w_tables_list WHERE hour IS NOT NULL";
		$req = pg_query($database_connection, $sql);

		if (pg_num_rows($req) > 0) {
			while ($row = pg_fetch_array($req)) {
				$day = substr($row['hour'], 0, 8);
				if (!isset($retrieve_hours[$day]) || !in_array(substr($row['hour'], 8, 2), $retrieve_hours[$day])) $retrieve_hours[$day][] = substr($row['hour'], 8, 2);
			}
		}

		// Selection des heures dans 'sys_to_compute'

		$sql = "SELECT DISTINCT hour FROM sys_to_compute WHERE hour IS NOT NULL AND (newtime = 1 OR newtime = 2)";
		$req = pg_query($database_connection, $sql);

		if (pg_num_rows($req) > 0) {
			while ($row = pg_fetch_array($req)) {
				$day = substr($row['hour'], 0, 8);
				if (!isset($retrieve_hours[$day]) || !in_array(substr($row['hour'], 8, 2), $retrieve_hours[$day])) $retrieve_hours[$day][] = substr($row['hour'], 8, 2);
			}
		}

		// On convertit le tableau d'heures à réinserer en message

		if (count($retrieve_hours) > 0) {			
			foreach ($retrieve_hours as $key=>$value) {
				sort($value);
				$reinsert_msg .= " Reload hour".((count($value) > 1)?"s":"")." ".(implode(", ", $value))." of ".getTaValueToDisplay("day", $key).".";
			}
		}	
		return $reinsert_msg;
	}

	function getComputeValuesToReload($database_connection, $time, $first_value)
	{
		$reload_msg = "";
		
		if ($time == "hour") {
			for ($i=0; $i < count($first_value); $i++) {
				$reload_hours[substr($first_value[$i], 0, 8)][] = substr($first_value[$i], 8, 2);
			}		
		}
		else {
			$reload_days = array($first_value);
		}

		// Selection des valeurs '$time' dans 'sys_to_compute'

		$sql = "SELECT DISTINCT $time FROM sys_to_compute WHERE $time IS NOT NULL AND (newtime = 1 OR newtime = 2)";
		$req = pg_query($database_connection, $sql);

		if (pg_num_rows($req) > 0) {
			while ($row = pg_fetch_array($req)) {		
				if ($time == "hour") {
					$day = substr($row['hour'], 0, 8);
					if (!isset($reload_hours[$day]) || !in_array(substr($row['hour'], 8, 2), $reload_hours[$day])) $reload_hours[$day][] = substr($row['hour'], 8, 2);					
				}
				else 
				{
					if (!in_array($row[$time], $reload_days)) $reload_days[] = $row[$time];
				}
			}
		}

		// On convertit le tableau d'heures à rejouer en message

		if (count($reload_hours) > 0) {			
			foreach ($reload_hours as $key=>$value) {
				if(count($value)>1) sort($value);
				$reload_msg .= " Reload hour".((count($value) > 1)?"s":"")." ".(implode(", ", $value))." of ".getTaValueToDisplay("day", $key).".";
			}
		}
		
		// On convertit le tableau de jours à rejouer en message

		if (count($reload_days) > 0) {
			if(count($reload_days)>1) sort($reload_days);
			$reload_msg = " Reload day".((count($reload_days) > 1)?"s":"")." ".(implode(", ", array_map("getDayLabel", $reload_days))).".";	
		}

		return $reload_msg;
	}

	function getDayLabel($day_value)
	{
		return getTaValueToDisplay("day", $day_value);
	}

	function getHourLabel($hour_value)
	{
		return getTaValueToDisplay("hour", $hour_value);
	}

	function trunkTempTables($database_connection)
	{
        // On récupere le nom des tables à supprimer
        
		$sql = "SELECT table_name FROM sys_w_tables_list";
        $req = pg_query($database_connection, $sql);

		if (pg_num_rows($req) > 0) {
			while ($row = pg_fetch_array($req)) {
				
				postgres_drop_table($row['table_name']);			
				
				// On supprime les lignes de sys_w_table_list (pour la vider)
				
				$sql2 = "DELETE FROM sys_w_tables_list WHERE table_name='".$row['table_name']."'";
				pg_query($database_connection, $sql2);				
			}
		}
        
		// On efface toutes les tables du type 'w_%'
        
		$sql = "SELECT tablename FROM pg_tables WHERE schemaname='public' AND tablename LIKE 'w_%'";
        $req = pg_query($database_connection, $sql);

		if (pg_num_rows($req) > 0) {
			while ($row = pg_fetch_array($req)) {	
				postgres_drop_table($row['tablename']);			
			}			
		}
	}

	function stopProcess($database_connection)
	{
        $requetes = array();
        
		$requetes[0] = "TRUNCATE sys_process_encours";
        $requetes[1] = "TRUNCATE TABLE sys_requetes";
        $requetes[2] = "TRUNCATE TABLE sys_step_track";
        $requetes[3] = "TRUNCATE TABLE sys_family_track";
        $requetes[4] = "TRUNCATE TABLE sys_flat_file_uploaded_list";
        $requetes[5] = "TRUNCATE TABLE sys_crontab";

		// 15/04/2008 - Modif. benoit : correction du bug 6346. Lors du stop des process, on ne fait pas de maj de la table 'sys_definition_master' afin que les process puissent se relancer

        //$requetes[6] = "UPDATE sys_definition_master SET on_off='0'";
        //$requetes[7] = "TRUNCATE TABLE sys_to_compute";

		$requetes[6] = "TRUNCATE TABLE sys_to_compute";
        
		foreach($requetes as $req) {
            pg_query($database_connection, $req);
        }		
	}

	// 15/04/2008 - Modif. benoit : ajout de la fonction ci-dessous permettant de remettre le compute dans son état d'origine si celui doit être arrêté et qu'un compute swicth avait eu lieu

	function removeComputeSwitch($database_connection)
	{
		if (trim(get_sys_global_parameters('compute_switch')) != "") {

			// Restauration de la valeur de 'compute_mode' (valeur de 'compute_switch')
			
			$sql = "UPDATE sys_global_parameters SET value='".get_sys_global_parameters('compute_switch')."' WHERE parameters='compute_mode'";
			pg_query($database_connection, $sql);

			// RAZ de la valeur de 'compute_switch'

			$sql = "UPDATE sys_global_parameters SET value = NULL WHERE parameters='compute_switch'";
			pg_query($database_connection, $sql);
		}
	}