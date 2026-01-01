<?
/*
 * @cb531@
 * 
 * 26/03/2013 NSE : Alarm Email subject format
 * 
 */
?><?
/*
*
 * Class abstraite pour generation de fichier d'export d'alarme pour les rapports
 * chaque format d'export doit avoir sa propre classe qui ettende cette classe
 * 
 * Cette class permet egalement l'envoit de mails avec les exports d'alarme en piece jointe
 *
 * 01/03/2011 MMT Creation du fichier
 * remplacement de alarmMailWithPDF dans le cadre du bug 19128 (ajour format xls et doc pour rapports)
 *
 * 22/09/2011 ACS - BZ 23748 - correct email sending for Top/Worst list
 * 22/09/2011 ACS - BZ 19767 - In report/schedule, alarm pdf file were not generated due to a mistake in a sql where clause (cast error due to PG9.1 migration)
 *
 *
 * @cb41000@
*
*
*	08/06/2009 SPS ajout de quotes autour de l'id_user
*
*	18/06/2009 BBX
*		=> ajout de quotes pour lister les id_alarm
*		=> utilisation de la focntion getNaLabelList au lieu de la fonction get_network_aggregation_from_family
*		=> ajout de quotes sur la condition de l'id group
*	17:40 23/12/2009 SCT : BZ 13579 => incohérence dans les rapports émis => résultat d'alarmes dans l'interface mais pas dans les rapports (fonction pdfBuilder)
*	24/02/2010 NSE bz 13579 : ' mal placée dans correction précédente
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
	- maj 25/02/2008, benoit : prise en compte des valeurs de ta sous forme de tableau (modification liée au compute booster)
	- maj 27/02/2008, benoit : correction de l'initialisation de la liste des id_alarm dans la fonction 'sendMailWithPDF()'
	- maj 28/02/2008, benoit : dans la fonction 'sendMailWithPDF()', en mode daily switch on ne traite qu'une seule valeur de la liste d'heures

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
*	- maj 09/08/2007, jérémy : modification de l'expéditeur pour uniformiser tous les mails envoyés par l'application
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
	class alarmMail
	Permet d'envoyer les rapports PDF des
	alarmes à des groupes d'utilisateurs.
	last MAJ : 18 10 2005

	- maj ligne 286 l 11/04/2006 ajout de la famille dans le nom du pdf.
	- 20 et 21/04/2006 :
		> meilleure prise en charge du 3ème axe sur roaming.
		> on boucle sur tous les HN/3ème axe définit dans les tables des Alarmes statiques.
		> petite optim de l'envoi des mails >> maj de la classe libMail.class.php (nouvelles méthodes)
		> on vide la table sys_contenu_buffer afin de ne pas 'doublonner' le display de certaines alarmes.

	- maj DELTA christophe 26 04 2006. cf MODIF DELTA NOUVEAU(ajout)   MODIF DELTA(mise en commentaires des modifications)

	- maj 27/02/2007, benoit : correction du format de la date (en anglais) dans le titre du mail

	- maj 16/04/2007 Gwénaël
		>> modification concernant la date dans le sujet du mail afin qu'elle corresponde à la date des données et pas la date du jour.

*/
include_once (REP_PHYSIQUE_NIVEAU_0."class/alarmDisplayCreate.class.php");
include_once (REP_PHYSIQUE_NIVEAU_0."class/alarmDisplayCreate_twcl.class.php");
include_once (REP_PHYSIQUE_NIVEAU_0."class/alarmCreateHTML.class.php");

abstract class alarmMail{

	/**
	 *Constructeur
	 *
	 * @param $offset_day
	 */
	public function __construct($offset_day) {

		$this->product			= '';	// cette variable sera peut-être passée en paramètre plus tard
                // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::get Connection()
		$this->db			= Database::getConnection($this->product);
		$this->offset_day		= $offset_day;
		$this->time_to_calculate	= get_time_to_calculate($this->offset_day); // fonctions définies dans edw_function.php
		$this->compute_mode	= get_sys_global_parameters("compute_mode"); // Valeurs possibles : hourly ou daily.
		$this->mail_reply		= get_sys_global_parameters("mail_reply");
		$this->debug			= get_sys_debug('alarm_send_mail'); // Affichage du mode Debug ou non.
		$this->flag_axe3		= false;

		$this->createFilePath = self::getReportFilesDirectory();

		$this->emptySysContenuBuffer(-1);
	}

	/**
	 * sets the output file folder path
	 * @param String $path
	 */
	public function setCreateFilePath($path){
		$this->createFilePath = $path;
	}


	/**
	 * return the export format file extention ('pdf', 'xls', 'doc')
	 * @return string export file extention
	 */
	public abstract function getExportFileExtention();


	/**
	 * return the export format name ('pdf', 'Excel', 'Word')
	 * @return string export format name
	 */
	public abstract function getExportFileFormat();

	/**
	 * generate file from DB buffer table query results and return its name
	 * @return string generated file name
	 */
	protected abstract function createFileFromBufferResults($file_name,$result_search);

	/**
	 * Return the exported report files directory
	 * This does not apply to mail attachement
	 * @return string folder
	 */
	public static function getReportFilesDirectory(){
		return REP_PHYSIQUE_NIVEAU_0.get_sys_global_parameters("report_files_dir");
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

		$this->tabVide	= true;
		$alarm_type	= array('alarm_static','alarm_dynamic','alarm_top_worst');
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
			foreach ($this->time_to_calculate as $time_aggregation => $time_to_calculate) {

				if ($find) $query_alarm .= " OR ";
				$query_alarm .= " t4.time_aggregation = '$time_aggregation' ";
				$find = true;

				// 27/02/2007 - Modif. benoit : on utilise la fonction 'getTaValueToDisplayV2_en()' au lieu de 'getTaValueToDisplayV2()' pour formater la date en anglais dans le titre du mail

				// modif 16/04/2007 Gwénaël
					// modif afin que les alarmes en mode hour/week/month ont pour sujet des mails la date des données.
				// if($time_aggregation == 'hour') $this->ta_value_day = getTaValueToDisplayV2_en("hour", $this->time_to_calculate["hour"],"-");
				// elseif($time_aggregation == 'day') $this->ta_value_day = getTaValueToDisplayV2_en("day", $this->time_to_calculate["day"],"-");

				// 25/02/2008 - Modif. benoit : si '$time_to_calculate' est un tableau, cela signifie qu'on est en présence d'une liste d'heures dans 'hour_to_compute'. Puisqu'ici on cherche seulement le jour et que toutes les heures de la liste portent sur le même jour alors on résume celui-ci à une seule valeur

				if (is_array($time_to_calculate)) {
					$time_to_calculate = $time_to_calculate[0];
				}

				$this->ta_value_day = getTaValueToDisplayV2_en($time_aggregation, $time_to_calculate, "-");
			}
			$query_alarm .= ") GROUP BY alarm_id,alarm_name,family,time,network, id_group_table, hn_value ";
			$result = $this->db->getall($query_alarm);
			if ($this->debug) echo "<br><u>Query de la liste des alarmes à envoyer :</u>".$query_alarm."<br>";

			/*
			On construit le tableau contenant la liste des alarmes par famille / TA et NA.
			Structure du tableau :
			$tab[nom de la famille][time aggregation][network aggregation][indice] = identifiant de l'alarme
			*/
			$nombre_resultat = count($result);
			if ($this->debug) echo "Il y a <b>".$nombre_resultat."</b> résultats pour ".$alarm_type[$i].".<br>";
			if ($nombre_resultat > 0) {
				foreach ($result as $row) {
					$tab_alarms[$row["ta"]][$row["family"]][$row["na"]][$alarm_type[$i]][] = $row["alarm_id"];
					if (get_axe3($row["family"]))
						$this->flag_axe3 = true;
				}

				$this->tabVide = false;
			} else {
				echo "<b>Il n'y a aucune " . $alarm_type[$i] . " pour ce compute mode.</b><br>";
			}
		}

		if ($this->debug) {
			echo "<br><b> Tableau des alarmes </b><pre>";
			var_dump($tab_alarms);
			echo "</pre><br>";
		}

		return $tab_alarms;
	}

	/**
	 * Enregistre le fichier PDF pour le user donnée dans sys_content_buffer
	 *
	 * @param $id_group
	 * @param $pdf_file_name
	 */
	function saveFileforUser($id_group, $file_name){

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
				'$id_group',
				'$file_name',
				'alarm',
				'{$this->getExportFileFormat()}',
				'$timestamp',
				0,
				0,
				'$date'
			)
		";
		$this->db->execute($query_insert);

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
	protected function getListeIdAlarmes($group_info, $tab, $alarm_type){
		$id_group = $group_info["id_group"];
		$query = "
			SELECT id_alarm
			FROM sys_alarm_email_sender
			WHERE id_group = '$id_group'
				AND alarm_type='$alarm_type'
		";

		$result = $this->db->getall($query);
		$nombre_resultat = count($result);
		$liste_result = "";
		if ($nombre_resultat > 0)
			foreach ($result as $row)
				if (isset($tab[$row["id_alarm"]]))
					$liste_result .= $row["id_alarm"].",";

		$liste_result = substr($liste_result,0,strlen($liste_result)-1);
		return $liste_result;
	}

	/**
	 *
	 * 08/06/2009 SPS ajout de quotes autour de l'id_user
	 * @param int $id_user
	 */
	function emptySysContenuBuffer($id_user){
		$query_delete = " delete FROM SYS_CONTENU_BUFFER where id_user='$id_user' ";
		$this->db->execute($query_delete);
	}

	/**
	 * Compte le nombre de résultats pour une liste d'id alarme passée en paramètre.
	 *
	 * 08/06/2009 SPS ajout de quotes autour de l'id_user
	  * 18/06/2009 BBX : modification de la fonction afin qu'elle remplisse son rôle : compter le nombre de résultats d'alarmes
	 *
         * 14/12/2012 BBX BZ 31048 : on compte uniquement sur la date donnée
         * 
         * 26/03/2013 NSE : ajout du paramètre $byCritic => possibilité de compter les alarmes par criticité
         * 
	 * @param $liste_id_alarmes
         * @param $taValue
         * @param $byCritic permet de spécifier si on veut récupérer le nombre d'alarme par criticité
	 * @return si $byCritic=true : array('critical'=>x,'major'=>y,'minor'=>z), sinon : nb alarme total
	 */
	function alarmResultCount($liste_id_alarmes, $taValue = '', $byCritic=false)
        {
            $condition = "";
            if(!empty($taValue)) {
                $condition = " AND ta_value = ".$taValue;
            }
            if(!$byCritic){
		$query = "SELECT id_result FROM edw_alarm
                          WHERE id_alarm IN ('".str_replace(",","','",$liste_id_alarmes)."')
                          $condition";
		echo "<br>$query<br>";
		$this->db->execute($query);
		return $this->db->getNumRows();
            }
            else{
                $query = "SELECT count(critical_level), critical_level FROM edw_alarm
                          WHERE id_alarm IN ('".str_replace(",","','",$liste_id_alarmes)."')
                          $condition
                        GROUP BY critical_level";
		echo "<br>$query<br>";
		$result_criticity = $this->db->getAll($query);
                foreach ($result_criticity as $row){
                    $res[$row['critical_level']] = $row['count'];
                }
                return $res;
            }
	}

	/**
	 *
	 * @param $title
	 * @param $file_name
	 * @param $dir_saving_pdf_file
	 * @param $na
	 * @param $ta
	 * @param $ta_value
	 * @param $alarm_type		spécifie si on a une alarme statique, dynamiques, top-worst
	 * @param $sql_filter		impose une contraine supplémentaire sur l'alarme (ex: que l'alarme soit bien dans sys_alarm_email_sender)
	 * @param $header			en-tête du pdf (titre)
	 * @param $isReport
	 *
	 * @return string
	 */
	public function generateFile($title,$file_name,$na,$ta,$ta_value,$alarm_type,$sql_filter,$isReport) {

		if ($this->debug) echo "<div class='debug' style='color:black;'><div class='function_call'>\$this->generateFile(
			<div style='margin-left:20px;'>
				title=<strong>$title</strong>,<br/>
				file_name=<strong>$file_name</strong>,<br/>
				na=<strong>$na</strong>,<br/>
				ta=<strong>$ta</strong>,<br/>
				ta_value=<strong>$ta_value</strong>,<br/>
				alarm_type=<strong>$alarm_type</strong>,<br/>
				sql_filter=<strong>$sql_filter</strong>,<br/>
				isReport=<strong>$isReport</strong></div>
			)</div>";

		$createQueries = $this->getCreateHtmlQueries($na,$ta,$ta_value,$alarm_type,$sql_filter,$isReport);

		foreach ($createQueries as $queries) {
			$keys = array_keys($queries);
			if(!empty($keys)){
				$mode = $keys[0];
			} else {
				$mode = '';
			}

                        $createHtml = new alarmCreateHTML($mode,'condense',$this->getExportFileFormat(),$queries,$title);
		}

                // 18/08/2011 BBX
                // Correction du cast sur la requête
                // BZ 19767
		$query_search = "
			SELECT object_type,object_source,object_title,id_page
			FROM sys_contenu_buffer
			WHERE object_id=0
				AND id_user = '-1'";
		$result_search = $this->db->getall($query_search);

		if ($result_search) {
			$out_file_name = $this->createFileFromBufferResults($file_name,$result_search);
		}

		if ($this->debug)
			echo "<div class='function_call'>return: $out_file_name</div></div>";

		return $out_file_name;
	}


	protected function getCreateHtmlQueries($na,$ta,$ta_value,$alarm_type,$sql_filter,$isReport){

		$ret = array();
		if ($alarm_type == 'alarm_top_worst') {
			// 23/06/2009 BBX : ajout du paramètre  na_axe3 manquant (après $na)
			$alarm_screen = new alarmDisplayCreate_twcl($this->product, $na, '', $ta, $ta_value, 'none', '');
			$critical_levels = array('twcl');
		} else {
			$alarm_screen = new alarmDisplayCreate('', $na, $ta, $ta_value, 'none', 'history', 'condense', null, $this->product);
			$critical_levels = array('critical','major','minor');
		}

		// on boucle sur tous les niveaux de criticité
		foreach ($critical_levels as $critical_level) {

			if ($this->debug) echo "<div><u><b>$critical_level</b></u></div>";
			$alarm_screen->getAlarmQuery($critical_level);
 
			$my_query_select		= $alarm_screen->query_select;
			$my_query_from		= $alarm_screen->query_from;
			$my_query_where		= $alarm_screen->query_where.$sql_filter;
			$my_query_order_by	= $alarm_screen->query_order_by;

			// en compute mode daily, on récupère toutes les alarmes de la journée
			if (($ta == 'hour') and (($this->compute_mode == 'daily') or ($isReport))) {
				$partie_a_changer = "ta_value = '$ta_value'";
				// 22/09/2011 ACS - BZ 19767 - PG9.1: retrieve ta_value as a string in order to compare to another string
				$partie_changee = "ta_value::text LIKE '".substr($ta_value,0,-2)."%'";
				// 17:40 23/12/2009 SCT : BZ 13579 => incohérence dans les rapports émis => résultat d'alarmes dans l'interface mais pas dans les rapports
				// 24/02/2010 NSE bz 13579 ' mal placée
				if ($isReport) $partie_changee .= " AND ta_value<='".$ta_value."24'";
				$my_query_where = str_replace($partie_a_changer,$partie_changee,$my_query_where);
			}
			$queries = array();
			$queries[$critical_level]['query_select']		= $my_query_select;
			$queries[$critical_level]['query_from']		= $my_query_from;
			$queries[$critical_level]['query_where']		= $my_query_where;
			$queries[$critical_level]['query_order_by']	= $my_query_order_by;

			// on affiche la requête de construction du tableau HTML
			//if (($this->debug) and (!get_sys_debug('alarm_export_pdf')))
			//	echo "<br>$my_query_select $my_query_from $my_query_where $my_query_order_by<br><br>";

			$ret[] = $queries;

		}
		if ($this->debug) {
				echo "<pre>";
				print_r($ret);
				echo "</pre>";
			}
		return $ret;
	}







	/**
	 * Construit le display et le PDf à envoyer (on les enregistre dans la table sys_content_buffer).
	 *
	 * @param  Array $tab_alarms
	 */
	public function sendMails($tab_alarms){
		global $id_user;

		$id_user_old = $id_user;
		$id_user = -1;

		echo "<u>Valeur de l'offset_day</u> : $this->offset_day<br>";
		echo "<b>(Contenu de time to calculate )</b> ".var_dump ($this->time_to_calculate)."<br><br>";

		if ($this->tabVide == false) {

			$dir_saving_file = REP_PHYSIQUE_NIVEAU_0.get_sys_global_parameters("pdf_save_dir");

			// pour les mails on utilise le repertoire temporaire
			$this->setCreateFilePath($dir_saving_file);
			$nom_appli		= get_sys_global_parameters("system_name");

			// On parcourt le tableau des alarmes.
			foreach ($tab_alarms as $ta_lib => $family_tab) {

				echo "<ul><u><b>Traitement de la TA $ta_lib.</b></u><ul>";

				foreach ($family_tab as $family => $na) {

					echo "<br><u><b>Traitement de la famille $family.</b></u><ul>";

					foreach ($na as $na_lib => $alarm_type_tab) {

						echo "<br><b>></b> Traitement de la <u>NA $na_lib.</u><ul>";

						foreach ($alarm_type_tab as $alarm_type => $element) {

							echo "<br><b>-</b> Traitement des alarmes de type <u>$alarm_type.</u><ul><br>Liste des id alarmes : ";

							// On construit un tableau avec les id alarmes.
							// 27/02/2008 - Modif. benoit : pour initier la liste des id_alarm on ne se sert plus de la boucle mais l'on fait un implode des clés du tableau '$tab_liste_id_alarm' à la fin de celle-ci
							for ($i=0; $i < count($element); $i++) {
								if ($i) echo ", ";
								$tab_liste_id_alarm[$element[$i]] = $element[$i];
								//$liste_id_alarm  .= $element[$i].",";
								echo $element[$i];
							}
							echo "<br>";

							// 18/06/2009 BBX : ajout de quotes pour lister les id_alarm
							$liste_id_alarm = "'".implode("','", array_keys($tab_liste_id_alarm))."'";

							// On génère le display pour toutes les alarmes (enregistrement dans sys_contenu_buffer).
							if (isset($this->time_to_calculate[$ta_lib])) {
								// 25/02/2008 - Modif. benoit : pour gérer les listes d'heures, on stocke les valeurs de ta dans un tableau et on boucle sur les elements contenus dans celui-ci

								if (is_array($this->time_to_calculate[$ta_lib])) {

									// 28/02/2008 - Modif. benoit : en mode daily, il n'est pas nécessaire de disposer de toutes les valeurs de la liste d'heures vu que celles-ci ne sont pas prise en compte (en réalité, on ne se sert que du type de ta). On limite donc le tableau à une seule valeur
									if ($this->compute_mode == "daily") {
										$tab_values[$ta_lib][0] = $this->time_to_calculate[$ta_lib][0];
									} else {
										// compute_mode "hourly"
										$tab_values[$ta_lib] = $this->time_to_calculate[$ta_lib];
									}

								} else {
									$tab_values[$ta_lib][0] = $this->time_to_calculate[$ta_lib];
								}

								//$tab_values[$ta_lib] = $this->time_to_calculate[$ta_lib];
							} else {
								global $edw_day;

								// 25/02/2008 - Modif. benoit : les valeurs de ta autres que les listes d'heures sont contenues dans une chaine. Pour uniformiser l'ensemble, on convertit celle-ci en tableau

								switch ($ta_lib) {
									case "day" :
										$tab_values[$ta_lib][0] = $edw_day;
										break;
									case "week" :
										$tab_values[$ta_lib][0] = getweek($this->offset_day);
										break;
									case "month" :
										$tab_values[$ta_lib][0] = getmonth($this->offset_day);
										break;
								}
							}

							$tab_values[$na_lib] = "";

							$family_label	= get_family_information_from_family($family);

							//echo "<pre>";print_r($family_label);exit;

							$family_label	= $family_label['family_label'];

							// 18/06/2009 BBX : utilisation dela fonction 5.0
							//$na_label		= get_network_aggregation_from_family($family);
							$na_label		= getNaLabelList($family);

							$na_label		= $na_label[$na_lib];
							if ($alarm_type == 'alarm_static')		$alarm_type_label = 'Static Alarm';
							if ($alarm_type == 'alarm_dynamic')		$alarm_type_label = 'Dynamic Alarm';
							if ($alarm_type == 'alarm_top_worst')	$alarm_type_label = 'Top/Worst Cell List';

							// 25/02/2008 - Modif. benoit : on boucle sur les différentes valeurs de ta
							for ($k=0; $k < count($tab_values[$ta_lib]); $k++) {

								if($this->flag_axe3) {
									$title = "$alarm_type_label ".getTaValueToDisplayV2($ta_lib, $tab_values[$ta_lib][$k],".")." ".getTaLabel($ta_lib)." on $na_label";
								} else {
									$title = "$alarm_type_label ".getTaValueToDisplayV2($ta_lib, $tab_values[$ta_lib][$k],".")." ".getTaLabel($ta_lib)." on $na_label";
								}

								// 22/09/2011 ACS - BZ 23748 - Create a new array to contain this list of names (don't modify the $alarm_type_tab because it is used in the foreach loop)
								$alarm_type_name['alarm_static'] = 'static';
								$alarm_type_name['alarm_dynamic'] = 'dyn_alarm';
								$alarm_type_name['alarm_top_worst']	= 'top-worst';

								// 27/02/2008 - Modif. benoit : suppression du tronquage de la chaine '$liste_id_alarm'. Celle-ci est bien initialisé plus haut

								//$liste_id_alarm = substr($liste_id_alarm,0,strlen($liste_id_alarm)-1);	// on enlève la dernière ,

								// On construit la condition pour sélectionner les groupe concerné par l'alarme.
								$complement_query = " ";
								$compteur = 0;
								foreach ($this->time_to_calculate as $libellle_time_aggregation=>$ta_value_TTC) {
									if ($compteur == 0)	$complement_query .= " AND (";
									if ($compteur != 0)	$complement_query .= " OR ";
									$complement_query .= " time_aggregation = '$libellle_time_aggregation' ";
									$compteur++;
								}
								$complement_query .= ")";

								// On enregistre le PDF pour chaque groupe 'abonné'.
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
								if ($this->debug) echo "<br>&nbsp;<u>Query qui récupère la liste des groupes à mailer:</u> <br>$query_group_list<br>";

								$result_group_list = $this->db->getall($query_group_list);
								$liste_id_alarmes_du_groupe_precedent = "";

								echo $this->db->displayQueries();
								echo "<pre>";print_r($result_group_list);echo "</pre>";

								if ($result_group_list) {
									foreach ($result_group_list as $row){

										if ($this->debug) echo "<br><hr>";

										// On récupère la liste des alarmes auxquelles le groupe est abonné.
										$liste_id_alarmes_du_groupe = $this->getListeIdAlarmes($row, $tab_liste_id_alarm, $alarm_type);
										echo "<br>Le groupe <b>".$row["group_name"]."</b> est abonné aux alarmes suivantes : ".$liste_id_alarmes_du_groupe."<br>";

										// 25/02/2008 - Modif. benoit : ajout d'un titre pour préciser quelle valeur de TA l'on traite
										echo "<br/>- Traitement de la TA value ".$tab_values[$ta_lib][$k]."<br/><br/>";

										// On génère le fichier PDF.
										
										$file_name = $alarm_type."_".$family."_".$na_lib."_".$ta_lib."_".getTaValueToDisplayV2($ta_lib, $tab_values[$ta_lib][$k],"_")."_".str_replace(" ", "_",$row["group_name"]).".".$this->getExportFileExtention();

										// le sql_filter impose le fait que les alarmes calculées soient bien dans la liste des alarmes à envoyer (sys_alarm_email_sender)
										$sql_filter = " AND id_alarm in (SELECT id_alarm FROM sys_alarm_email_sender WHERE alarm_type = '$alarm_type' AND id_group = '{$row["id_group"]}' )"
													." AND alarm_type = '".$alarm_type_name[$alarm_type]."'"
													." AND id_alarm in ($liste_id_alarm)";

										$file_name = $this->generateFile($title,$file_name,'',$ta_lib,$tab_values[$ta_lib][$k],$alarm_type,$sql_filter,0);

										if ($this->debug) echo "<i>Fichier {$this->getExportFileExtention()} généré : $file_name</i><br/>";

                                                                                // 26/03/2013 NSE : Alarm Email subject format
                                                                                // récupération du détail du nombre d'alarmes critiques, mjeures, mineures
										$nb_resultat_des_alarmes_detail = $this->alarmResultCount($liste_id_alarmes_du_groupe, $this->time_to_calculate[$ta_lib],true);
                                                                                $nb_resultat_des_alarmes = array_sum($nb_resultat_des_alarmes_detail);;
										if ($nb_resultat_des_alarmes)
											echo "<i>$nb_resultat_des_alarmes alarme(s) trouv&eacute;e(s).</i><br>";
										else
											echo "<i>Aucune alarme trouv&eacute;e.</i><br>";

										$this->emptySysContenuBuffer(-1);

										if(strlen(trim($file_name)) > 4){

											// On enregistre le fichier PDF pour le groupe dans sys_content_buffer.
											$this->saveFileforUser($row["id_group"], $file_name);

											// 18/06/2009 BBX : ajout de quotes sur la condition de l'id group
											// On construit les emails pour chaque utilisateur du groupe courrant et on attache le fichier PDF que
											// l'on vient de créer.
											$query_list_user = "
												SELECT user_mail FROM users
												WHERE id_user IN (
													SELECT id_user FROM sys_user_group
													WHERE id_group = '".$row["id_group"]."'
												)
												";
											$result_list_user = $this->db->getall($query_list_user);

											if ($result_list_user){
												foreach ($result_list_user as $row){

													$user_mail = $row["user_mail"];
													if (!isset($mail_list[$user_mail])) {
														echo "<ul><li>création d'un mail pour l'utilisateur : <b>" . $user_mail . "</b></ul>";
														$mail_list[$user_mail] = new Mail();
														$mail_list[$user_mail]->From($nom_appli."<$this->mail_reply>");
														$mail_list[$user_mail]->ReplyTo($this->mail_reply);
														$mail_list[$user_mail]->To($user_mail);
														$mail_list[$user_mail]->Body("This mail was autogenerated, please do not reply");
														$nb_total[$user_mail] = array('critical'=>0,'major'=>0,'minor'=>0);
													}

													// positionne le fichier comme pièce attaché dans le mail de l'utilisateur
													$file_attach = $dir_saving_file.$file_name;
													// Si la liste des alarmes du groupe précédent est égale à celle du groupe courrant alors, on n'a pas
													// besoins d'attacher le PDF.
													if ($liste_id_alarmes_du_groupe_precedent != array($liste_id_alarmes_du_groupe,$user_mail)) {
														if (!$mail_list[$user_mail]->AttachFileExist($file_attach)) {
															$mail_list[$user_mail]->Attach($file_attach);
															echo "<ul><li>Fichier  : " . $file_attach . " attaché au mail <b>$user_mail</b></ul>";
															// On stock le nombre de résultats des alarmes courrantes.
															$nb_total[$user_mail]['critical'] += $nb_resultat_des_alarmes_detail['critical'];
															$nb_total[$user_mail]['major'] += $nb_resultat_des_alarmes_detail['major'];
															$nb_total[$user_mail]['minor'] += $nb_resultat_des_alarmes_detail['minor'];
														} else {
															echo "<ul><li><i>le fichier  : " . $file_attach . " a déjà été attaché au mail <b>$user_mail</b></i></ul>";
														}
													} else {
														echo "<ul><li>le fichier $file_name pour <b>$user_mail</b> n'a pas été attaché car un fichier avec les mêmes alarmes est déja attaché (mais pour un autre groupe).</ul>";
													}
												}
											}
										}
										$liste_id_alarmes_du_groupe_precedent = array($liste_id_alarmes_du_groupe,$user_mail);
									}
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
				WHERE id_user IN (
					SELECT id_user FROM sys_user_group
				)
			";
			$result = $this->db->getall($query);
			
			if ($result) {
				// 27/02/2007 - Modif. benoit : si '$this->ta_value_day' est inexistant, on utilisera la valeur courante de la date formatée en anglais et non en francais
				$date = isset($this->ta_value_day) ? $this->ta_value_day : date("Y-m-d");

				echo '<pre>';
				print_r($nb_total);
				echo '</pre>';

                                // 26/03/2013 NSE : Alarm Email subject format
                                // Initialisation du message
                                // Format du subject
                                $subjectFormat = get_sys_global_parameters( "alarm_email_subject_format" );
                                // Remplacement des éléments
                                $subjectFormat = str_replace( "[APP_NAME]", $nom_appli, $subjectFormat );
                                $subjectFormat = str_replace( "[IP]", get_adr_server(), $subjectFormat );
                                $subjectFormat = str_replace( "[DATE]", $date, $subjectFormat );

				foreach ($result as $row){
					if (isset($mail_list[$row["user_mail"]])) {
                                            
                                            // Définition du message
                                            $subjectFormat = str_replace( "[NB_ALA]", array_sum($nb_total[$row["user_mail"]]), $subjectFormat );
                                            $subjectFormat = str_replace( "[NB_CRI]", $nb_total[$row["user_mail"]]['critical'], $subjectFormat );
                                            $subjectFormat = str_replace( "[NB_MAJ]", $nb_total[$row["user_mail"]]['major'], $subjectFormat );
                                            $subjectFormat = str_replace( "[NB_MIN]", $nb_total[$row["user_mail"]]['minor'], $subjectFormat );
                                            displayInDemon( "E-mail subject is [$subjectFormat]", 'list' );
                                            
						$mail_list[$row["user_mail"]]->Subject($subjectFormat);// system_name dans SGP
						$mail_list[$row["user_mail"]]->Send();
						print "<b>mail envoyé à " . $row["user_mail"] . "</b><br>";
					}
				}
			}
		}
		$id_user = $id_user_old;
	}//fin function sendMailWtihPdf

} // fin class
?>
