<?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Astellia
*
*	Composant de base version cb_4.0.0.00
*
* 08/06/2009 SPS ajout de quotes autour de l'id_user
*/
?>
<?
/*
	class alarmMailWithexcel
	Permet d'envoyer les rapports excel
*/
include_once (REP_PHYSIQUE_NIVEAU_0."class/alarmDisplayCreate.class.php");
include_once (REP_PHYSIQUE_NIVEAU_0."class/alarmDisplayCreate_twcl.class.php");
include_once (REP_PHYSIQUE_NIVEAU_0."class/alarmCreateHTML.class.php");
include_once (REP_PHYSIQUE_NIVEAU_0."class/htmlTableExcel.class.php");

class alarmMailWithexcel{

	/**
	 *Constructeur
	 *
	 * @param $database_connection
	 * @param $offset_day
	 */
	function alarmMailWithExcel ( $database_connection, $offset_day ) {

		$this->database_connection = $database_connection;
		$this->offset_day          = $offset_day;
		$this->time_to_calculate   = get_time_to_calculate($this->offset_day); // fonctions définies dans edw_function.php
		$this->compute_mode        = get_sys_global_parameters("compute_mode"); // Valeurs possibles : hourly ou daily.
		$this->mail_reply          = get_sys_global_parameters("mail_reply");
		$this->debug               = get_sys_debug('alarm_send_mail'); // Affichage du mode Debug ou non.
		$this->flag_axe3           = false;

		$this->emptySysContenuBuffer(-1);
	}


	/**
	 * Construit le tableau contenant la liste des alarmes à envoyer en fonction du paramètre offset_day
	 *
	 *	- maj 16/04/2007 Gwénaël
	 *			>> suppression de la condition su le time_agregation = DAY
	 *
	 * @eturn Array
	 */
	function getAlarms() {

		$this->tabVide = true;
		$alarm_type = array('alarm_static','alarm_dynamic','alarm_top_worst');
		for ($i=0;$i<count($alarm_type);$i++) {

			// On construit la requête qui récupère la liste des id_alarmes.
			$query_alarm = "
				SELECT
					alarm_id,
					family,
					time as ta,
					network as na,
					id_group_table,
					hn_value
				FROM
					sys_definition_".$alarm_type[$i]." t3,
					sys_alarm_email_sender t4
				WHERE
					t3.alarm_id = t4.id_alarm
					AND	alarm_type = '".$alarm_type[$i]."'
					AND (

			";

			// On construit les autres conditions.
			$find = false;
			foreach($this->time_to_calculate as $time_aggregation => $time_to_calculate) {
				if($find) $query_alarm .= " OR ";
				$query_alarm .= " t4.time_aggregation = '$time_aggregation' ";
				$find = true;

				// 27/02/2007 - Modif. benoit : on utilise la fonction 'getTaValueToDisplayV2_en()' au lieu de 'getTaValueToDisplayV2()' pour formater la date en anglais dans le titre du mail

				// modif 16/04/2007 Gwénaël
					// modif afin que les alarmes en mode hour/week/month ont pour sujet des mails la date des données.
				// if($time_aggregation == 'hour') $this->ta_value_day = getTaValueToDisplayV2_en("hour", $this->time_to_calculate["hour"],"-");
				// elseif($time_aggregation == 'day') $this->ta_value_day = getTaValueToDisplayV2_en("day", $this->time_to_calculate["day"],"-");
				$this->ta_value_day = getTaValueToDisplayV2_en($time_aggregation, $time_to_calculate, "-");
			}
			$query_alarm .= ") GROUP BY alarm_id,alarm_name,family,time,network, id_group_table, hn_value ";
			$result = pg_query($this->database_connection, $query_alarm);
			if($this->debug) echo "<br><u>Query de la liste des alarmes à envoyer :</u>".$query_alarm."<br>";


			/*
			On construit le tableau contenant la liste des alarmes par famille / TA et NA.
			Structure du tableau :
			$tab[nom de la famille][time aggregation][network aggregation][indice] = identifiant de l'alarme
			*/
			$nombre_resultat = pg_num_rows($result);
			if($this->debug) echo "Il y a <b>".$nombre_resultat."</b> résultats pour ".$alarm_type[$i].".<br>";
			if($nombre_resultat > 0) {
				for ($k = 0;$k < $nombre_resultat;$k++) {
					$row = pg_fetch_array($result, $k);
					$tab_alarms[$row["ta"]][$row["family"]][$row["na"]][$alarm_type[$i]][] = $row["alarm_id"];
					if(get_axe3($row["family"])) {
						$this->flag_axe3 = true;
					}
				}
				$this->tabVide = false;
			}
			else {
				echo "<b>Il n'y a aucune " . $alarm_type[$i] . " pour ce compute mode.</b><br>";
			}
		}

		if($this->debug){
			echo "<br><b> Tableau des alarmes </b><pre>";
			var_dump($tab_alarms);
			echo "</pre><br>";
		}

		return $tab_alarms;
	}

	/**
	 * Enregistre le fichier excel pour le user donnée dans sys_content_buffer
	 *
	 * @param $id_group
	 * @param $excel_file_name
	 */
	function saveexcelforUser($id_group, $excel_file_name){

		$date = date("Y/m/d h:i:s ");
		$timestamp = date("Y/m/d h:i:s ");

		$query_insert = "
		INSERT INTO sys_content_buffer (
				content_id,
				path,
				type,
				format,
				date_of_generation,
				sent,
				sent_to_group,
				date_sent
			)
			VALUES (
				$id_group,
				'$excel_file_name',
				'alarm',
				'excel',
				'$timestamp',
				0,
				0,
				'$date'
			)
		";
		pg_query($this->database_connection,$query_insert);

	}

	/**
	 * Retourne la liste des id des alarmes auxquelles le groupe est 'abonné'
	 *
	 * @param $id_group
	 * @param $tab : contient la liste des id des alarmes qui ont été générées.
	 * @param $alarm_type
	 *
	 * @return Array
	 */
	function getListeIdAlarmes($id_group, $tab, $alarm_type){
		$query = "
			SELECT id_alarm FROM sys_alarm_email_sender
			WHERE id_group = $id_group
			AND alarm_type='$alarm_type'
		";
		$result = pg_query($this->database_connection,$query);
		$nombre_resultat = pg_num_rows($result);
		$liste_result = "";
		if($nombre_resultat > 0){
			for ($i = 0;$i < $nombre_resultat;$i++){
			$row = pg_fetch_array($result, $i);
				if(isset($tab[$row["id_alarm"]])){
					$liste_result .= $row["id_alarm"].",";
				}
			}
		}
		$liste_result = substr($liste_result,0,strlen($liste_result)-1);
		return $liste_result;
	}

	/**
	 * @param int $id_user
	 * 08/06/2009 SPS ajout de quotes autour de l'id_user	 
	 */
	function emptySysContenuBuffer($id_user){
		$query_delete = " delete FROM SYS_CONTENU_BUFFER where id_user='$id_user'";
		pg_query($this->database_connection,$query_delete);
	}

	/**
	 * Compte le nombre de résultats pour une liste d'id alarme passée en paramètre.
	 *
	 * 08/06/2009 SPS ajout de quotes autour de l'id_user
	 *    	 
	 * @param $liste_id_alarmes
	 * @return
	 */
	function alarmResultCount($liste_id_alarmes){
		$query = "
			SELECT id_page
			FROM sys_contenu_buffer
			WHERE id_user = '-1'
				AND object_type = 'alarm_export'
		";
		//echo "<br>$query<br>";
		$result = pg_query($this->database_connection,$query);
		$nombre_resultat = pg_num_rows($result);
		$nombre_total = 0;
		if($nombre_resultat > 0) {
			for ($i = 0;$i < $nombre_resultat;$i++) {
				$row = pg_fetch_array($result, $i);
				$nombre_total += $row[0];
			}
		}
		return $nombre_total;
	}

	/**
	 *
	 * @param $database_connection
	 * @param $title
	 * @param $file_name
	 * @param $dir_saving_excel_file
	 * @param $na
	 * @param $ta
	 * @param $ta_value
	 * @param $alarm_type
	 * @param $sql_filter
	 * @param $header
	 * @param $isReport
	 *
	 * @return string
	 */
	function excelBuilder($database_connection,$title,$file_name,$dir_saving_excel_file,$na,$ta,$ta_value,$alarm_type,$sql_filter,$header,$isReport) {

		if ($alarm_type == 'alarm_top_worst') {
			$alarm_screen = new alarmDisplayCreate_twcl($na, $ta, $ta_value, 'none', '');
			$critical_level = array('twcl');
		}
		else {
			$alarm_screen = new alarmDisplayCreate($database_connection, $na, $ta, $ta_value, 'none', 'history', 'condense', '');
			$critical_level = array('critical','major','minor');
		}

		for ($j=0;$j<count($critical_level);$j++) {

			unset ($queries);

			$alarm_screen->getAlarmQuery($critical_level[$j]);

			$my_query_select = $alarm_screen->query_select;
			$my_query_from = $alarm_screen->query_from;
			$my_query_where = $alarm_screen->query_where . $sql_filter;
			$my_query_order_by = $alarm_screen->query_order_by;

			// en compute mode daily, on récupère toutes les alarmes de la journée
			if (($ta == 'hour') and (($this->compute_mode == 'daily') or ($isReport))) {
				$partie_a_changer = "ta_value = '$ta_value'";
				$partie_changee = "ta_value LIKE '".substr($ta_value,0,-2)."%'";
				if ($isReport) $partie_changee .= " AND ta_value<='$ta_value'";
				$my_query_where = str_replace($partie_a_changer,$partie_changee,$my_query_where);
			}

			$queries[$critical_level[$j]]['query_select'] = $my_query_select;
			$queries[$critical_level[$j]]['query_from'] = $my_query_from;
			$queries[$critical_level[$j]]['query_where'] = $my_query_where;
			$queries[$critical_level[$j]]['query_order_by'] = $my_query_order_by;

			$excel = new alarmCreateHTML($critical_level[$j],'condense','excel',$queries,$title);

			// on affiche la requête de construction du tableau HTML
			if(($this->debug) and (!get_sys_debug('alarm_export_pdf')))
				echo "<br>$my_query_select $my_query_from $my_query_where $my_query_order_by<br><br>";
		}

		$query_search = "SELECT object_type,object_source,object_title,id_page FROM sys_contenu_buffer WHERE object_id=0 AND id_user = -1";
		$result_search = pg_query($query_search);

		if (pg_num_rows($result_search) > 0) {
			
			$excel_filename	= $file_name;

			$header_title	= 'Reporting : Alarm';
			$save_file = true;
			
			$excel_filepath	=  REP_PHYSIQUE_NIVEAU_0.get_sys_global_parameters("report_files_dir");

			$header_img		= array("operator" => REP_PHYSIQUE_NIVEAU_0.get_sys_global_parameters("pdf_logo_operateur"), "client" => REP_PHYSIQUE_NIVEAU_0.get_sys_global_parameters("pdf_logo_dev"));
			$sous_mode		= 'condense';
			
			$html_to_excel = new Excel_HTML_Table($excel_filepath, $excel_filename, $header_img, $header_title, $sous_mode, $save_file);		

			unset ($html);
			while ($row_search = pg_fetch_array($result_search)) {
				$html[]=array($row_search['object_title'],$row_search['object_source'],$row_search['id_page']);
				print "<div class='texteGrisBoldPetit'>".$row_search['object_title']."</div>";
			}
			
			if (count($html)) 
				$html_to_excel->writeContent($html);
			
		}
		
		return $excel_filename;
	}


	/**
	 * Construit le display et le excel à envoyer (on les enregistre dans la table sys_content_buffer).
	 *
	 * @param  Array $tab_alarms
	 */
	function sendMailWithexcel($tab_alarms){
		global $id_user,$repertoire_physique_niveau0;

		$id_user_old = $id_user;
		$id_user = -1;

		echo "<u>Valeur de l'offset_day</u> : $this->offset_day<br>";
		echo "<b>(Contenu de time to calculate )</b> ".var_dump ($this->time_to_calculate)."<br><br>";

		if($this->tabVide == false){

			$dir_excel = 				get_sys_global_parameters("pdf_save_dir");
			$dir_saving_excel_file = 	$repertoire_physique_niveau0.$dir_excel;
			$nom_appli = 			get_sys_global_parameters("system_name");

			// On parcourt le tableau des alarmes.
			foreach($tab_alarms as $ta_lib => $family_tab) {

				echo "<ul><u><b>Traitement de la TA $ta_lib.</b></u><ul>";

				foreach($family_tab as $family => $na) {

					echo "<br><u><b>Traitement de la famille $family.</b></u><ul>";

					foreach($na as $na_lib => $alarm_type_tab) {

						echo "<br><b>></b> Traitement de la <u>NA $na_lib.</u><ul>";

						foreach($alarm_type_tab as $alarm_type => $element) {

							echo "<br><b>-</b> Traitement des alarmes de type <u>$alarm_type.</u><ul><br>Liste des id alarmes : ";

							// On construit un tableau avec les id alarmes.
							for($i=0; $i < count($element); $i++) {
								if ($i) echo ", ";
								$tab_liste_id_alarm[$element[$i]] = $element[$i];
								$liste_id_alarm  .= $element[$i].",";
								echo $element[$i];
							}
							echo "<br>";

							// On génère le display pour toutes les alarmes (enregistrement dans sys_contenu_buffer).
							if(isset($this->time_to_calculate[$ta_lib])) {
								$tab_values[$ta_lib] = $this->time_to_calculate[$ta_lib];
							}
							else {
								global $edw_day;
								switch($ta_lib){
									case "day" :	$tab_values[$ta_lib] = $edw_day;
													break;
									case "week" :	$tab_values[$ta_lib] = getweek($this->offset_day);
													break;
									case "month" :	$tab_values[$ta_lib] = getmonth($this->offset_day);
													break;
								}
							}

							$tab_values[$na_lib] = "";

							$family_label = get_family_information_from_family($family);
							$family_label = $family_label['family_label'];
							$na_label = get_network_aggregation_from_family($family);
							$na_label = $na_label[$na_lib];
							if ($alarm_type == 'alarm_static')$alarm_type_label = 'Static Alarm';
							if ($alarm_type == 'alarm_dynamic')$alarm_type_label = 'Dynamic Alarm';
							if ($alarm_type == 'alarm_top_worst')$alarm_type_label = 'Top/Worst Cell List';

							if($this->flag_axe3) {
								$title = "$alarm_type_label ".getTaValueToDisplayV2($ta_lib, $tab_values[$ta_lib],".")." ".getTaLabel($ta_lib)." on $na_label";
							}
							else {
								$title = "$alarm_type_label ".getTaValueToDisplayV2($ta_lib, $tab_values[$ta_lib],".")." ".getTaLabel($ta_lib)." on $na_label";
							}

							$alarm_type_tab['alarm_static'] = 'static';
							$alarm_type_tab['alarm_dynamic'] = 'dyn_alarm';
							$alarm_type_tab['alarm_top_worst'] = 'top-worst';

							$liste_id_alarm = substr($liste_id_alarm,0,strlen($liste_id_alarm)-1);	// on enlève la dernière , .

							// On construit la condition pour sélectionner les groupe concerné par l'alarme.
							$complement_query = " ";
							$compteur = 0;
							foreach ($this->time_to_calculate as $libellle_time_aggregation=>$ta_value_TTC){
								if($compteur == 0) $complement_query .= " AND (";
								if($compteur != 0) $complement_query .= " OR ";
								$complement_query .= " time_aggregation = '$libellle_time_aggregation' ";
								$compteur++;
							}
							$complement_query .= ")";

							// On enregistre le excel pour chaque groupe 'abonné'.
							// on sélectionne les groupe qui sont abonné à l'alarme courrante et dont le champ time_aggregation (ici = fréquence d'envoi) et égal
							// à la TA de time to calculate.
							$query_group_list = "
								SELECT id_group, group_name FROM sys_user_group
									WHERE id_group IN (
										SELECT id_group FROM sys_alarm_email_sender
										WHERE alarm_type = '$alarm_type'
											AND id_alarm IN ($liste_id_alarm)
											$complement_query
									)
									GROUP BY id_group, group_name
							";
							if($this->debug) echo "<br>&nbsp;<u>Query qui récupère la liste des groupes à mailer:</u> <br>$query_group_list<br>";

							$result_group_list = pg_query($this->database_connection,$query_group_list);
							$liste_id_alarmes_du_groupe_precedent = "";
							$nombre_resultat_group_list = pg_num_rows($result_group_list);

							if($nombre_resultat_group_list > 0){

								for ($i = 0;$i < $nombre_resultat_group_list;$i++){

									$row = pg_fetch_array($result_group_list, $i);

									if ($this->debug) echo "<br><hr>";

									// On récupère la liste des alarmes auxquelles le groupe est abonné.
									$liste_id_alarmes_du_groupe = $this->getListeIdAlarmes($row["id_group"], $tab_liste_id_alarm, $alarm_type);
									echo "<br>Le groupe <b>".$row["group_name"]."</b> est abonné aux alarmes suivantes : ".$liste_id_alarmes_du_groupe."<br>";

									// On génère le fichier excel.

									$file_name = $alarm_type."_".$family."_".$na_lib."_".$ta_lib."_".getTaValueToDisplayV2($ta_lib, $tab_values[$ta_lib],"_")."_".str_replace(" ", "_",$row["group_name"]).".xls";
						
									$header = "Alarm report (".$row['group_name'].")";

									$sql_filter = " AND id_alarm in (SELECT id_alarm FROM sys_alarm_email_sender WHERE alarm_type = '$alarm_type' AND id_group = ".$row["id_group"].")"
												." AND alarm_type = '".$alarm_type_tab[$alarm_type]."'"
												." AND id_alarm in ($liste_id_alarm)";

									$excel_file_name = $this->Builder($this->database_connection,$title,$file_name,$dir_saving_excel_file,'',$ta_lib,$tab_values[$ta_lib],$alarm_type,$sql_filter,$header,0);
									$excel_file_name = $this->Builder($this->database_connection,$title,$file_name,$dir_saving_excel_file,'',$ta_lib,$tab_values[$ta_lib],$alarm_type,$sql_filter,$header,0);

									$nb_resultat_des_alarmes = $this->alarmResultCount($liste_id_alarmes_du_groupe);
									if ($nb_resultat_des_alarmes)
										echo "<i>$nb_resultat_des_alarmes alarme(s) trouvée(s).</i><br>";
									else
										echo "<i>Aucune alarme trouvée.</i><br>";

									$this->emptySysContenuBuffer(-1);

									if(strlen(trim($excel_file_name)) > 4){

										// On enregistre le fichier excel pour le groupe dans sys_content_buffer.
										$this->saveexcelforUser($row["id_group"], $excel_file_name);

										// On construit les emails pour chaque utilisateur du groupe courrant et on attache le fichier excel que
										// l'on vient de créer.
										$query_list_user = "
											SELECT user_mail FROM users
											WHERE id_user IN (
												SELECT id_user FROM sys_user_group
												WHERE id_group = ".$row["id_group"]."
											)
											";
										$result_list_user = pg_query($this->database_connection,$query_list_user);
										$nombre_resultat_list_user = pg_num_rows($result_list_user);
										if($nombre_resultat_list_user > 0){
											for ($j = 0;$j < $nombre_resultat_list_user;$j++){

												$row = pg_fetch_array($result_list_user, $j);
												$user_mail = $row["user_mail"];
												if (!isset($mail_list[$user_mail])) {
													echo "<ul><li>création d'un mail pour l'utilisateur : <b>" . $user_mail . "</b></ul>";
													$mail_list[$user_mail] = new Mail();
													$mail_list[$user_mail]->From($nom_appli."<$this->mail_reply>");
													$mail_list[$user_mail]->ReplyTo($this->mail_reply);
													$mail_list[$user_mail]->To($user_mail);
													$mail_list[$user_mail]->Body("This mail was autogenerated, please do not reply");
													$nb_total[$user_mail] = 0;
												}

												// positionne le excel comme pièce attaché dans le mail de l'utilisateur
												$excel_attach = $dir_saving_excel_file.$excel_file_name;
												// Si la liste des alarmes du groupe précédent est égale à celle du groupe courrant alors, on n'a pas
												// besoins d'attacher le excel.
												if($liste_id_alarmes_du_groupe_precedent != array($liste_id_alarmes_du_groupe,$user_mail)){
													if(!$mail_list[$user_mail]->AttachFileExist($excel_attach)){
														$mail_list[$user_mail]->Attach($excel_attach);
														echo "<ul><li>Fichier  : " . $excel_attach . " attaché au mail <b>$user_mail</b></ul>";
														// On stock le nombre de résultats des alarmes courrantes.
														$nb_total[$user_mail] += $nb_resultat_des_alarmes;
													}
													else {
														echo "<ul><li><i>le fichier  : " . $excel_attach . " a déjà été attaché au mail <b>$user_mail</b></i></ul>";
													}
												}
												else {
													echo "<ul><li>le excel $excel_file_name pour <b>$user_mail</b> n'a pas été attaché car un excel avec les mêmes alarmes est déja attaché (mais pour un autre groupe).</ul>";
												}
											}
										}
									}
									$liste_id_alarmes_du_groupe_precedent = array($liste_id_alarmes_du_groupe,$user_mail);
								}
							}
							unset ($tab_values);
							unset ($liste_id_alarm);
							unset ($tab_liste_id_alarm);
							echo "</ul>";
						}
						echo "</ul>";
					}
					echo "<br>///////////////////////////////////////////////////////////////<br></ul>";
				}
				echo "</ul></ul>";
			}

			// On envoie les mails.
			// On récupère tous les utilisateurs qui appartiennent à un groupe.
			$query="
				SELECT DISTINCT user_mail FROM users
				WHERE id_user IN(
					SELECT id_user FROM sys_user_group
				)
			";
			$result = pg_query($this->database_connection,$query);
			$nombre_resultat = pg_num_rows($result);
			if($nombre_resultat > 0){

				// 27/02/2007 - Modif. benoit : si '$this->ta_value_day' est inexistant, on utilisera la valeur courante de la date formatée en anglais et non en francais

				$date = isset($this->ta_value_day) ? $this->ta_value_day : date("Y-m-d");
				for ($j = 0;$j < $nombre_resultat;$j++){
					$row = pg_fetch_array($result, $j);
					if (isset($mail_list[$row["user_mail"]])) {
						$mail_list[$row["user_mail"]]->Subject($nom_appli." Alarm (".$nb_total[$row["user_mail"]]." results) $date ");// system_name dans SGP
						$mail_list[$row["user_mail"]]->Send();
						print "<b>mail envoyé à " . $row["user_mail"] . "</b><br>";
					}
				}
			}
		}
		$id_user = $id_user_old;
	}//fin function sendMailWtihexcel

} // fin class
?>
