<?php
/**
*	Classe de génération de schedules / reports / dashboards
*	
*	Exemple d'utilisation :
*	$offset_day = get_sys_global_parameters('offset_day');
*	$myExporter = new Exporter($offset_day);
*	$myExporter->exportSchedules();
*
*	et tous les schedules seront envoyés par mail à leurs destinataires.
*
*	Le "schema" des appels des fonctions est assez simple :
*	
*	exportSchedules()					elle liste tous les schedules à exporter
*		=> exportSchedule()				export d'un schedule : liste tous les rapports du schedule
*			=> exportReport()			export d'un rapport : liste tous les dashboards du rapport
*				=> exportDashboard()	génère le dashboard
*			=> getDestinataires()			va chercher les destinataires du schedule
*
*	@author	SLC - 16/04/2009
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*
*
*	03/072009 BBX : BZ 10387
*		=> Réécriture de la fonction getFirstDayWithData()
*		=> exportDashboard() : si pas de données avec l'offset day en base, recalcul avec l'offset day généré par getFirstDayWithData()
*		=> description du mode d'affichage
*		=> fermeture de balise PHP oubliée
*
*	27/07/2009 - MPR : Correction du bug 10648 - Requête SQL incorrecte table usmers au lieu de users
*
*	07/08/2009 GHX
*		- Correction du BZ 10878 [REC][T&A CB 5.0][TC#14553][TS#AC10-CB40][TP#1][Task Scheduler / Reporting]: mail en double quand plusieurs utilisateurs
*	11/08/2009 GHX
*		- Correction du BZ 10974 [REC][T&A Cb 5.0][TP#1][TS#AC10-CB40][TC#14585][Reporting] : erreur lors de la récupération des élements du slave
*	12/08/2009 GHX
*		- Correction du BZ 10996 [REC][T&A CB 5.0][TC#14455][TP#1][Mail]: pour un schedule report, pb sur l'heure remontée
*	17/08/2009 GHX
*		- (Evo) ajout de l'id du gtm au sort by sinon problème dans le cas d'un multi sort by (cad plusieurs même RAW/KPI ayant le code+legende)
*		- Si un dashboard n'a pas de donnée, on crée une image "no data found" sinon erreur JPGRAPH comme quoi il n'y a pas de données dans le graphe
*	20/08/2009 GHX
*		- Correction du BZ 10876 [REC][T&A CB 5.0][TP#1][REPORT BUILDER]: apercu PDF ne fonctionne pas si dashboard sans données
*		- Correction du BZ 10878 [REC][T&A CB 5.0][TC#14553][TS#AC10-CB40][TP#1][Task Scheduler / Reporting]: mail en double quand plusieurs utilisateurs
*		- Correction du BZ 10976 [REC][T&A Cb 5.0][TP#1][TS#AC10-CB40][TC#14585][Reporting] : lien dans le mail même si les rapports n'ont aucun résultat
*	
*	22/09/2009 BBX : 
*		- Suppression des "!!". BZ 11671
*		- Etant donné que la fonction peut être rappelée en cas de regénération, on n'affiche l'info Dashboard uniquement si elle n'a pas encore téé affichée. BZ 11671
*	15:44 10/11/2009 - MPR :
*		 	 - Début Correction du BZ 12621 - Modification du message d'erreur 
*			 	- Les causes engendrant cette erreur peuvent être :
*					-> Soit la partition /home est saturée
*			 		-> Soit les droits sur le répertoire d'archive des rapports sont incorrects
*					-> Soit OpenOffice est mal ou pas installé
*
*	25/11/2009 BBX => BZ 12989
		- Si la TA est un tableau d'heures (compute booster), on prend l'heure la plus récente
*
*	01/12/2009 BBX
*		- Si l'alarme n'a pas de résultat, on ne génère pas de PDF. BZ 11134
*	18/01/2010 NSE BZ 13789
*		- ne pas alerter l'admin si on n'a pas de données dans le graph
*	24/02/2010 NSE bz 13579 en rapport day, on doit pouvoir voir les alarmes niveau hour -> si ta YYYYMMDD
*	01/04/2010 NSE bz 14927 : Ajout du port (nécessaire si le port 80 est inaccessible)
*	27/04/2010 NSE bz 15234 : traitement du cas du tableau d'heures
*	09/06/10 YNE/FJT : SINGLE KPI
 * 15/02/2011 MMT bz 17884: les schedule sont générés même si aucun subscriber
 * 01/03/2011 MMT bz 19128: les exports d'alarmes supportent les formats doc et xls,
 *  compatbililité avec les slaves de version précédente qui génèrent toujours du PDF
 *
 * 06/06/2011 MMT DE 3rd Axis utilisation des nouvelles fonctions d'axe communes de la classe SelecteurDashboard
 * 06/07/2011 MMT Bz 19767 alarmes parfois non générées car offset day +1 en preview
 * 13/07/2011 NSE bz 22735 : utilisation de  get_adr_server() à la place de localhost car la nouvelle politique de sécurité semble l'interdire
 * 09/08/2011 MMT Bz 22600 pas de mapping topo si selection de NEs dans le selecteur du dash
 * 25/10/2011 ACS BZ 24316 No data found for weekly and montly alarm in reports
 * 25/11/2011 NSE bz 24824 : saut de ligne + modification du message pour le lien vers le pdf des dashboards.
 * 09/12/2011 ACS Mantis 837 DE HTTPS support
 * 14/12/2011 ACS BZ 25132 Correct "chmod +777" by "chmod 777"
 * 21/12/2011 ACS BZ 25191 Warnings displayed in Schedule menu
*/

class Exporter
{
    /**
     * @var string Définition de la fréquence d'envoi
     */
    protected $_frequency = '';
	
	/**
    * Construction de l'objet. Appel minimal. On crée le tableau date, on se
    * connecte à la db et on regarde le mode debug
    *
    * @param integer $offset_day
    * @param boolean $preview false
	*/
    function __construct( $offset_day, $preview = false )
    {
        // 30/08/2011 BBX
        // BZ 10387 : ajout de la propriété preview pour connaitre le mode de génération
        $this->preview = $preview;
		$this->offset_day					= $offset_day;
		// = Array ( [day] => 20080805 [day_bh] => 20080805 [hour] => 2008080523 [week] => 200832 [week_bh] => 200832 [month] => 200808 [month_bh] => 200808 ) 
		$this->date							= get_time_to_calculate($this->offset_day);
                // 31/01/2011 BBX
                // On remplace new DatabaseConnection() par Database::getConnection()
                // BZ 20450
		$this->db							= Database::getConnection();
		// 09/12/2011 ACS Mantis 837 DE HTTPS support
		$this->productModel					= new ProductModel(ProductModel::getProductId());
		
		$this->debug						= get_sys_debug('report_send_mail');
		$this->id_master_product			= $this->db->getone('select sdp_id from sys_definition_product where sdp_master=1 limit 1'); 
		// Indique si on a essayer de générer les dashboards pour la dernière date avec données
		$this->withMinOffsetDay 			= false;
		// Mode d'affichage
		$this->display_mode 				= 'landscape';
	}
	
	/**
	*	We export one alarm
	*  01/03/2011 MMT bz 19128 renomage de la fonction, ajout du param $format_extention
	*	@param string $id_alarm : id de l'alarme dont on veut générer le PDF
	*	@param string $alarm_type : type de l'alarme : alarm_static, ...
	*	@param int      $id_product : id du produit de l'alarme à exporter
	 * @param $format_extention : extention du format d'export (pdf, xls ou doc)
	*	@return string retourne l'url du fichier généré
	*/
	function exportAlarm($id_alarm,$alarm_type,$id_product,$format_extention)
	{
		include_once(REP_PHYSIQUE_NIVEAU_0."class/alarmMail.class.php");
	
		// we get the alarm
		$query = " --- we get the $alarm_type $id_alarm in product $id_product
			select *
			from sys_definition_$alarm_type
			where alarm_id='$id_alarm' ";
		if ($this->debug and !$test) displayInDemon($this->db->pretty($query));
		
		if ($id_product == $this->id_master_product) {
			$alarm = $this->db->getrow($query);
		} else {
			$slave = $this->db->getrow("select * from sys_definition_product where sdp_id=$id_product");
                        // 31/01/2011 BBX
                        // On remplace new DatabaseConnection() par Database::getConnection()
                        // BZ 20450
			$slave_db = Database::getConnection($id_product);
			$alarm = $slave_db->getrow($query);
			unset($slave_db);
		}

		if (!$alarm) {
			if (!$test) {
				displayInDemon("No $alarm_type found with id: $id_alarm",'title');
				// 22/09/2009 BBX : suppression des "!!". BZ 11671
				$this->msg .= "\n  No $alarm_type found with id: $id_alarm\n";
			}
			return;
		}
		$this->alarm_name = $alarm['alarm_name'];
		if (!$test) {
			displayInDemon("Alarm {$alarm['alarm_name']}",'list');
			$this->msg .= "\n  Alarm {$alarm['alarm_name']}";	
		}
		
		$na	= $alarm['network'];
                $a3     = '';
		$ta	= $alarm['time'];
		// 25/10/2011 ACS BZ 24316 No data found for weekly and montly alarm in reports
		if ($ta == 'week') {
			$ta_value = getWeek($this->offset_day);
		}
		else if ($ta == 'month') {
			$ta_value = getMonth($this->offset_day);
		}
		else {
			$ta_value = getDay($this->offset_day);
		}
		
                // Test existance 3ème axe dans le nom du na (bz19873)
                $pos = strrpos( $na, '_' ); // On prend la dernière occurence
                if( $pos !== false )
                {
                    $a3 = substr( $na, $pos + 1 );
                    $na = substr( $na, 0, $pos );
                }
                else
                {
                    // Si aucune occurence de '_', on laisse $na et $a3 intact
                }

		// 01/12/2009 BBX : bz 11134 Si l'alarme n'a pas de résultat, on ne génère pas de PDF
		// 24/02/2010 NSE : bz 13579 En rapport day, on doit pouvoir voir les alarmes niveau hour -> si ta YYYYMMDD
                // 31/01/2011 OJT : bz 19873 Gestion des alarmes 3èxe axe
                // 31/01/2011 BBX : bz 20450 On remplace new DatabaseConnection() par Database::getConnection()
                // 08/06/2011 BBX -PARTITIONING-
                // Correction des casts
		$db_temp = Database::getConnection($id_product);
		$query_check_results = "SELECT * FROM edw_alarm
		WHERE id_alarm = '$id_alarm'
		AND na = '$na'
                AND (a3 = '$a3' OR a3 IS NULL)
		AND ta = '$ta'
		AND ta_value::text ".($ta=='hour'?(sizeof($ta_value)==8?"= '$ta_value'":"ilike '$ta_value%'"):"= '$ta_value'");
		$db_temp->execute($query_check_results);
		if($db_temp->getNumRows() == 0){
			return false;
		}
		// FIN BZ 11134

		// on compose le nom final du fichier : schedule_report_alarm_na_top_ta_tavalue_mode.ext
		// 01/03/2011 MMT bz 19128 extrait l'extention du nom du fichier
		// il faut attendre avant de la connaitre: si c'est un slave qui ne supporte pas xls ou doc
		$file_name_no_ext = "$this->schedule_name $this->report_name $this->alarm_name";
		if ($id_product != $this->id_master_product)
			$file_name_no_ext .= " in ".$this->db->getone("select sdp_label from sys_definition_product where sdp_id=$id_product");
		$file_name_no_ext .= " $na";
		if ($axe3)
			$file_name_no_ext .= " $axe3 $axe3_2";
		$file_name_no_ext .= " $ta $ta_value";
		$file_name_no_ext = str_replace(" ","_",$file_name_no_ext);
		
		$file_path = alarmMail::getReportFilesDirectory();
		
		// table de correspondance entre le type de l'alarme inscrit dans sys_pauto_config.class_objet
		// et le type de l'alarme inscrit dans edw_alarm.alarm_type
		$alarm_type_translaction = array(
			'alarm_static'		=> 'static',
			'alarm_dynamic'	=> 'dyn_alarm',
			'alarm_top_worst'	=> 'top-worst',
		);
		
		$sql_selected_alarm = "	and t2.id_alarm='$id_alarm'\n	and t2.alarm_type='{$alarm_type_translaction[$alarm_type]}' ";

		if ( $id_product == $this->id_master_product) {
			// on genere le fichier de l'alarme en local utilisant la classe appropriée au format

			if($format_extention == 'pdf'){
				include_once(REP_PHYSIQUE_NIVEAU_0."class/alarmMailPdf.class.php");
				$alarmMail = new alarmMailPdf($this->offset_day);
				$alarmMail->setHeader("Alarm report (".$alarm['alarm_name'].")");
			}
			elseif($format_extention == 'xls')
			{
				include_once(REP_PHYSIQUE_NIVEAU_0."class/alarmMailExcel.class.php");
				$alarmMail = new alarmMailExcel($this->offset_day);
			}
			else if($format_extention == 'doc')
			{
				include_once(REP_PHYSIQUE_NIVEAU_0."class/alarmMailWord.class.php");
				$alarmMail = new alarmMailWord($this->offset_day);
			} else {
				$this->msg .= "\n  Unsupported export format : '$format_extention' \n";
				return;

			}
			if(!empty($alarmMail)){

			    $file_name = $alarmMail->generateFile(
				$alarm['alarm_name'],	// $title
					$file_name_no_ext.'.'.$format_extention,
				$na,
				$ta,
				$ta_value,
				$alarm_type,
				$sql_selected_alarm,
				1
			    );
			}
		} else {
			// 01/03/2011 MMT bz 19128 ajout support export xls + pdf + compatibilité slave ancienne version
			// ----------------------------------------- SLAVE --------------------
			// on genere le fichier de l'alarme sur le slave
			include_once(REP_PHYSIQUE_NIVEAU_0.'class/SSHConnection.class.php');
			// on se connecte au slave
			
			//test if the slave is on a remote system (we'll have to execute commands throught ssh)
			$isRemoteProduct = ( get_adr_server() !== $slave['sdp_ip_address'] );
				
			//test if the slave supports XLS and DOC alarm report generation (fix of bug 19128)
			// if not, the generated file will be PDF
			$isAlarmGenPDFonly = (!self::doesProductSupportXlsAndDocAlarmReporting($id_product));

			// 0- prepare command to execute on slave product script
			if($isAlarmGenPDFonly){
				// old script
				$scriptName = "alarm_generate_pdf.php";
			} else {
				// new script in 5.0.5 version
				$scriptName = "alarm_generate_exportfile.php";
			}
			// log info in demon
                        // Masquage du message dans Report Preview
                        if ($this->debug and !$test){
                            $demonMsg = "Generate alarm export from slave '{$slave['sdp_label']}' ";
                            if($isRemoteProduct){
                                    $demonMsg .=" (remote server, use SSH) ";
                            } else {
                                    $demonMsg .=" (local server) ";
                            }
                            $demonMsg .= " using script '$scriptName' ";
                            if($isAlarmGenPDFonly){
                                    $demonMsg .= " (older CB version) which only support PDF format";
                            }
                            displayInDemon("<br>$demonMsg<br>");
                        }
                        
			$cmd= "php /home/{$slave['sdp_directory']}/scripts/".$scriptName
				." offset_day=$this->offset_day"
				." id_alarm=$id_alarm"
				." alarm_type=$alarm_type"
				." id_product=$id_product"
				." na=$na"
				." ta=$ta"
				." ta_value=$ta_value"
				." sql_selected_alarm=".urlencode($sql_selected_alarm)
				." alarm_name=".urlencode($alarm['alarm_name'])
				." format=".$format_extention // ignored in old script
				." 2>&1"; // redirect the stderr to stdout so we can capture possible errors

			// 1- execute command on the slave system
			if( $isRemoteProduct ){
				try {
					$ssh = new SSHConnection($slave['sdp_ip_address'], $slave['sdp_ssh_user'], $slave['sdp_ssh_password'], $slave['sdp_ssh_port'], 1);
				} catch ( Exception $e ) {
					$msg = "Cannot connect to product {$slave['sdp_label']} via SSH: ".$e->getMessage();
					return '!! '.$msg;
				}
				
				$cmdArrayRet = $ssh->exec($cmd);
				$cmdRet = implode("", $cmdArrayRet);

			} else {
				$cmdRet = exec($cmd);
			}

			// 2- analyse result and get filename
			if($isAlarmGenPDFonly){
				// le slave utilise le script alarm_generate_pdf.php qui genère toujours un PDF avec un nom fixe
				$slaveFile = "export_{$alarm_type}_$id_alarm.pdf";
				$format_extention = "pdf";

			} else {
				// le script alarm_generate_exportfile.php renvoit OK:<nom du fichier généré> si succes ou message d'erreur sinon
				$successPostFix = "OK:";
				if(substr($cmdRet,0,strlen($successPostFix)) == $successPostFix){
					//success
					$slaveFile = substr($cmdRet,strlen($successPostFix));
				} 
                                // 19/07/2012 BBX
                                // BZ 26669 : S'il y a une erreur, on essaie tout de même de récupérer le fichier
                                elseif(substr_count($cmdRet, 'OK:') > 0) {
                                    list($errorMsg, $slaveFile) = explode('OK:',$cmdRet);
                                    echo '<div style="font-size:7pt">File generated with error : '.$errorMsg.'</div>';
                                }
                                else {
					//error
					$msg = "Error while getting slave '{$slave['sdp_label']}' alarm '{$alarm['alarm_name']}' file format '$format_extention':";
					$msg .= "<br>".$cmdRet."<br>";
					return '!! '.$msg;
				}
			}
			// add extention
			$file_name = $file_name_no_ext.'.'.$format_extention;

			// 3- retrive the filename from product to local dir and rename it to proper name
			$slave_file_path = "/home/{$slave['sdp_directory']}/report_files/";
			if( $isRemoteProduct ){
				try
				{
					// on recupere le fichier en le copiant au bon endroit
					$ssh->getFile($slave_file_path.$slaveFile,$file_path.$file_name);
				}
				catch ( Exception $e )
				{
					$msg = $e->getMessage();
					return '!! '.$msg;
				}
			} else {
				// local copy
				$cpCmd = "cp -f ".$slave_file_path.$slaveFile." ".$file_path.$file_name;
				$cmdRet = exec($cpCmd);
			}

		}
		$file_url = str_replace(REP_PHYSIQUE_NIVEAU_0,NIVEAU_0,$file_path).$file_name;
		return $file_url;
	}
	
	/**
	*	We export one dashboard
	*	
	*	@param string	$id_report id du rapport dans lequel se trouve le dashboard à exporter (important pour retrouver le selecteur par defaut)
	*	@param string	$id_dashboard id du dashboard à générer
	*	@param bool	$test = false normalement. si = true, on est en mode test : on ne va rien générer, on recherche simplement s'il y a des valeurs dans ce dashboard.
	*	@return string	retourne l'url du fichier généré (pdf, doc, xls ...) pour le dashboard
	*/
	function exportDashboard($id_report,$id_dashboard,$test = false) 
	{
		if ($this->debug and !$test) displayInDemon(__CLASS__.'->'.__FUNCTION__."(<strong>$id_report,$id_dashboard</strong>)");

		// 03/07/2009 BBX
		// Bufferisation de l'affichage
		ob_start();
		
		// we get the dashboard
		$query = " --- we get the dashboard $id_dashboard
			select *
			from sys_pauto_page_name
			where page_type='page'
				and id_page='$id_dashboard' ";
		if ($this->debug and !$test) displayInDemon($this->db->pretty($query));
		
		$dashboard = $this->db->getrow($query);
		if (!$dashboard) {
			if (!$test) {
				displayInDemon("No dashboard found with id: $id_dashboard",'title');
				// 22/09/2009 BBX : suppression des "!!". BZ 11671
				$this->msg .= "\n  No dashboard found with id: $id_dashboard\n";
			}
			return;
		}
		$this->dashboard_name = $dashboard['page_name'];
		if (!$test) {
			displayInDemon("Dashboard {$dashboard['page_name']}",'list');
			// 22/09/2009 BBX : étant donné que la fonction peut être rappelée en cas de regénération, on n'affiche l'info Dashboard uniquement si elle n'a pas encore téé affichée. BZ 11671
			$dashboardInfoMsg = "\n  Dashboard {$dashboard['page_name']}";
			if(substr_count($this->msg, $dashboardInfoMsg) == 0)			
				$this->msg .= $dashboardInfoMsg;	
		}
		
		// SELECTEUR
		// on chercher l'id_selecteur
		$id_selecteur = SelecteurModel::getSelecteurId($id_report,$id_dashboard);
		if (!$id_selecteur) {
			if (!$test) {
				displayInDemon("No default selector is defined for that dashboard in that report.");
				// 22/09/2009 BBX : suppression des "!!". BZ 11671
				$this->msg .= "\n  No default selector is defined for that dashboard in that report.\n";
			}
			return;
		}
		// on instantie le sélecteur ... pour pouvoir lire ses valeurs par la suite
		$selecteur = new SelecteurModel($id_selecteur);
	
		// normalisation des valeurs récupérées par le selecteur
		// on change counter@... en raw@... dans sort_by, parce que sinon ça va faire planter au niveau de l'objet $dash_data
		$selecteur->setValue('sort_by', str_replace('counter@','raw@',$selecteur->getValue('sort_by')));
		$selecteur->setValue('filter_id', str_replace('counter@','raw@',$selecteur->getValue('filter_id')));
		// on change le mode ot / one en overtime / overnetwork
		if ($selecteur->getValue('mode') == 'ot')	$selecteur->setValue('mode','overtime');
		if ($selecteur->getValue('mode') == 'one')	$selecteur->setValue('mode','overnetwork');
		
		// valeurs du selecteurs :
		// echo display_array($selecteur->getValues(),"Valeurs du selecteur $id_selecteur",'orange');
	
		$selectDash = new SelecteurDashboard($id_dashboard);
		
		// GTMs du DASHBOARD
		
		// 1 - Définition des éléments du dashboard via la classe 'DashboardData'
		$dash_data = new DashboardData();
		$dash_data->setDebug(get_sys_debug('dashboard_display'));
		
		// Définition du menu du dashboard -- on s'en fiche non ??
		// $dash_data->setIdMenu($id_menu_encours);
		
		// Définition du produit maitre
		$dash_data->setMasterTopo();
		
		// Définition des valeurs qui vont être utilisées via les setters de la classe
		// 1.1 - Définition des valeurs de TA
		$ta = $selecteur->getValue('ta_level');
		$this->ta = $ta;		// on crée $this->ta juste pour repasser la valeur de ta à getFirstDayWithData() pour optimiser les calculs

        // Mise à jour de la ta_value dans le cas du mode Fixed Hour
        if( $selecteur->getValue( 'fh_mode' ) )
        {
            $tmpIdProd = ProductModel::getIdProductFromModule( $selecteur->getValue( 'fh_product_bh' ) );
            if( $tmpIdProd )
            {
                $famModel = new FamilyModel( $selecteur->getValue( 'fh_family_bh' ), $tmpIdProd );
                $ta_value = $famModel->getBHValueFromDay( $this->date['day'], $selecteur->getValue( 'fh_na' ), $selecteur->getValue( 'fh_ne' ), $selecteur->getValue( 'fh_na_axe3' ), $selecteur->getValue( 'fh_ne_axe3' ) );
                if( $ta_value === false )
                {
                    $ta_value = null;

                    // Ecriture d'un Warning dans le tracelog (le mode Fixed Hour n'a pas pu être utilisé)
                    $sysName = get_sys_global_parameters( 'system_name' );
                    $modName = __T( 'A_TRACELOG_MODULE_LABEL_COMPUTE' );
                    $dash    = new DashboardModel( $id_dashboard );
                    sys_log_ast( 'Warning', $sysName, $modName, "Unable to use Fixed hour from BH mode for Dashboard \'{$dash->getName()}\', standard mode will be used", 'support_1' );
                }
                else
                {
                    // Initialisation des informations dans l'objet DashboardData
                    $dash_data->setFixedHourInfo( $selecteur->getValue( 'fh_na' ), $selecteur->getValue( 'fh_ne' ), $selecteur->getValue( 'fh_na_axe3' ), $selecteur->getValue( 'fh_ne_axe3' ), $selecteur->getValue( 'fh_family_bh' ), $tmpIdProd );
                }
            }
        }
        else
        {
		$ta_value = $this->date[$ta];
        }
		
		// 25/11/2009 BBX => BZ 12989
		// Si la TA est un tableau d'heures (compute booster), on prend l'heure la plus récente
		if(is_array($ta_value)) {
			$ta_value = max($ta_value);
		}
		// Fin BZ 12989
		
		// 11:30 20/08/2009 GHX
		// Correction du BZ 10876
		// Si on n'a pas d'heure dans le tableau $this->date c'est en fonction du paramétrage compute_mode <-> compute_processing <-> hour_to_compute 
		if ( empty($ta_value) )
		{
			if (  $ta == 'hour' )
			{
				$ta_value = $this->date['day'].'23';
			}
			else 
			{
				// 20/08/2009 GHX : Au cas ou le paramètrage en base ne permet de récupérer les valeurs
                // 08/06/2011 OJT : bz22400, gestion de la TA Week dans ce cas de figure
				if ( isset($this->date['hour']) )
				{
					// 27/04/2010 NSE bz 15234 : traitement du cas du tableau d'heures
					if( is_array( $this->date['hour'] ) )
                    {
						$tmpDay = substr( max( $this->date['hour'] ), 0, 8 );
                    }
					else
                    {
                        $tmpDay = substr( $this->date['hour'], 0, 8 );
                    }

                    switch( $ta )
                    {
                        case 'day' :
						case 'day_bh' :
                            $ta_value = date( "Ymd", strtotime( $tmpDay." -1 day" ) );
                            break;

                        case 'week' :
						case 'week_bh' :
							// 11/03/2013 GFS - BZ#31364 - [SUP][TA HPG][AVP 32586][Telus][Partionning]: All weekly history is delete on last week of the year compute
                            $ta_value = date( "oW", strtotime( $tmpDay." -1 week" ) );
                            break;

                        case 'month' :
						case 'month_bh' :
                            $ta_value = date( "Ym", strtotime( $tmpDay." -1 month" ) );
                            break;
				}
				}
				else
				{
					switch ( $ta )
					{
						case 'hour' :
							$ta_value = date( "Ymd", strtotime( "last hour" ) );
							break;

						case 'day' :
						case 'day_bh' :
							$ta_value = date( "Ymd", strtotime( "last day" ) );
							break;

						case 'week' :
						case 'week_bh' :
							// 11/03/2013 GFS - BZ#31364 - [SUP][TA HPG][AVP 32586][Telus][Partionning]: All weekly history is delete on last week of the year compute
							$ta_value = date( "oW", strtotime( "last week" ) );
							break;

						case 'month' :
						case 'month_bh' :
							$ta_value = date( "Ym", strtotime( "last month" ) );
							break;
					}
				}
			}
			
            // On ne mémorise plus la ta_value, elle peut évoluer entre chaque Dash
			//$this->date[$ta] = $ta_value;
		}
		
		// ?? $ta_value = getTaValueToDisplayReverse($ta, $ta_value, "/");
		$dash_data->setTA($ta, $ta_value, $selecteur->getValue('period'));
		
		$ta_value_reverse = getTaValueToDisplay($ta, $ta_value, "/");

		// Définition de la ta minimale
		$ta_list = $selectDash->getTaArray();
		$ta_list = array_keys($ta_list[0]);
		$dash_data->setTAMin($ta_list[0]);
		
		// Gestion de la bh
		if (!(strpos($ta, "bh") === false)) {		
			// Note : temporaire. On pourra avoir par la suite plusieurs bh
			$dash_data->setBH(array("bh"));
			$dash_data->setBHLabel(array('bh' => 'BH'));
		}
		
		// 1.2 - Définition des valeurs de NA
		// Définition des chemins des na axe1
		$na_axe1_paths = $selectDash->getNaParent(1);
		$dash_data->setPaths($na_axe1_paths);
		
		// Définition de la na axe1 affiché en abcisse (par défaut, la meme que celle du sélecteur sauf si une seule valeur est sélectionnée -> cf. traitement des ne axe1 ci-dessous)
		$na_axe1_abcisse = $selecteur->getValue('na_level');

		// Définition de la na d'axe1 à utiliser
		$dash_data->setNaAxe1($selecteur->getValue('na_level'));
		
		// Définition des ne d'axe1
		if ($selecteur->getValue('nel_selecteur')) {
			// 06/06/2011 MMT DE 3rd Axis factorisation: utilisation de getNeSelectionArrayFromStringValue
			$ne1 = $selectDash->getNeSelectionArrayFromStringValue($selecteur->getValue('nel_selecteur'),$na_axe1_abcisse);
			if (count($ne1) > 0)
				$dash_data->setNeAxe1($ne1);
		}
		
		// Définition de la na axe1 minimale
		$na_min = array_values($na_axe1_paths);
		$dash_data->setNaMinAxe1($na_min[count($na_min)-1]);

		// 09/08/2011 MMT Bz 22600 pas de mapping topo si selection de NEs dans le selecteur du dash
		if (count($ne1) > 0)
		{
			if ( $equivalentNeAxe1 = NeModel::getMapping($ne1) ){
					$dash_data->setEquivalentNeAxe1( $equivalentNeAxe1);
			}
		}
      else
      {
			// fin 22600
			 // 30/11/2010 BBX
			 // Prise en compte du mapping même sans sélection de NE
			 // BZ 17929
			 $dashModel = new DashboardModel($id_dashboard);
			 $defaultNel = NeModel::getNeFromProducts($na_axe1_abcisse, $dashModel->getInvolvedProducts());
          if ($equivalentNeAxe1 = NeModel::getMapping($defaultNel))
				  $dash_data->setEquivalentNeAxe1($equivalentNeAxe1);
			 // Fin BZ 17929
		 }

		// Note : à définir ou à supprimer
		//$dash_data->setEquivalentNeAxe1(array(3 => array('rnc' => array('TAG10_306' => 'TOTO'))));
		$dash_data->setNaAbcisseAxe1($na_axe1_abcisse);
		
		// Récupération des labels des na d'axe 1
		$na_label = $selectDash->getNALevels(1);
		
		// Définition de / des axe(s) N et de sa / ses valeurs
		$axeN_path = $selectDash->getNaAndNeAxeNPath($selecteur->getValue('axe3'), $selecteur->getValue('axe3_2'));
		
		if (count($axeN_path['na_axeN']) > 0) {
			$dash_data->setNaAxeN($axeN_path['na_axeN']);

			// 06/06/2011 MMT DE 3rd Axis utilisation des nouvelles fonctions d'axe communes
			$na_axe3_paths = $selectDash->getSourcePathToNa($selecteur->getValue('axe3'),3);
			$dash_data->setNaAbcisseAxeN($selecteur->getValue('axe3'));
			$dash_data->setPathsAxeN($na_axe3_paths);
			// Définition de la ne d'axeN sélectionnées (si elle existe)
			if (count($axeN_path['ne_axeN']) > 0){
				$dash_data->setNeAxeN($axeN_path['ne_axeN']);
			}
		
			// On complète le tableau de labels des na avec ceux de l'axe N
			$na_label = array_merge($na_label, $axeN_path['na_axeN_label']);
		}
		
		// Définition des labels axe 1 + axe N
		$dash_data->setNaLabel($na_label);
			
		// 1.3 - Définition des autres valeurs
		$dash_data->setMode(	$selecteur->getValue('mode'));
		$dash_data->setTop(		$selecteur->getValue('top'));
		
		if ($selecteur->getValue('sort_by') && ($selecteur->getValue('sort_by') != "none") && (str_replace('@', '', $selecteur->getValue('sort_by')) != ""))
		{
			// 17:15 17/08/2009 GHX
			// On récupère l'id du GTM dans lequel se trouve le sort by
			$idGtm = $this->db->getOne("
				SELECT id_page
				FROM sys_pauto_config 
				WHERE class_object||'@'||id_elem||'@'||id_product = '".$selecteur->getValue('sort_by')."'
				AND id_page IN (SELECT id_elem FROM sys_pauto_config WHERE id_page = '".$id_dashboard."')
				");
			// Et on l'ajoute au sort by
			$dash_data->setSortByFromSelector($selecteur->getValue('sort_by').'@'.$idGtm, $selecteur->getValue('order'));
		}
		
		if ($selecteur->getValue('filter_id'))
			$dash_data->setFilterFromSelector($selecteur->getValue('filter_id'), $selecteur->getValue('filter_operande'), $selecteur->getValue('filter_value'));
		
		// Définition des éléments
		$dash_data->getElements($id_dashboard);
		
		// on vérifie qu'il y a bien des datas
		$no_data = 1;
		foreach ($dash_data->dashElements as $dashElement)
			if ($dashElement[0] != 'no_data')
				$no_data = 0;

                // 30/08/2011 BBX
                // BZ 10387 : recherche de la dernière date avec des données
                // uniquement en mode preview
		if ($no_data && $this->preview) 
		{
			// 03/07/2009 BBX
			// Si pas de données, on essaie de générer les dashboards depuis la dernière date avec données si pas déjà fait.
			// 01/08/2013 GFS - Bug 33751 - [REC][CB 5.1.6.45][TC #TA-61827][Report preview] The data is displayed wrong with overnetwork mode in report preview
			if (!$this->withMinOffsetDay && $selecteur->getValue('mode') == 'overtime') {
				$minOffsetDay = $this->getFirstDayWithData($id_report);
				// Si on a trouvé une date avec des données, on va retenter la génération avec cette date
				if($minOffsetDay != -1)
                {
					// On vide le buffer (et on eteind la temporisation)
					ob_end_clean();
					// Mise à jour du offset day avec la nouvelle valeur calculée
					$this->offset_day = $minOffsetDay;
					// Mise à jour de la date avec le nouvel offset day
					$this->date	= get_time_to_calculate($this->offset_day);
					// On informe l'objet que la génération depuis la dernière date avec données est faite.
					$this->withMinOffsetDay = true;
					// Regénération :)
					return $this->exportDashboard($id_report,$id_dashboard,$test);
				}
			}
			// FIN BBX
			
			if (!$test) {
				displayInDemon("This dashboard contains no data");
				// 16:02 12/08/2009 GHX
				// Correction du BZ 10996
				// 22/09/2009 BBX : suppression des "!!". BZ 11671
				$this->msg .= "\n  This dashboard contains no data for $ta ".Date::getSelecteurDateFormatFromDate($ta,$ta_value,'/')."\n";
			}

            // 2011/09/05 OJT : Ajout de la fin de temporisation
            ob_end_flush(); // Fin de la temprosation
			return false;
		}
		
		// si on est en mode test, on retourne true pour dire qu'on a bien trouvé des données.
		if ($test) return true;
			
		// Création des GTMs (XML -> IMG -> HTML)
		
		// Avant de lancer la création, on initialise le tableau d'export des GTMs
		$dash_export = array();
		$mode = $dash_data->getGTMMode();
		// On boucle sur les résultats de la définition des éléments
		foreach (($dash_data->DisplayResults()) as $gtm_id => $gtm_values) {
			
			// affiche les valeurs du GTM
			// echo "<div style='border:2px solid orange;padding:4px;'>$gtm_id => <pre>"; print_r($gtm_values);	echo "</pre></div>";
			
			$gtm_xml = new GtmXml($gtm_id);
			// 19/11/2009 MPR - Extrapolation des données - On récupère la config du sélecteur (nécessaire pour extrapoler les données à partir d'une ta en base)
			$gtm_xml->setGTMNa( $selecteur->getValue('na_level') );
			if( $selecteur->getValue('axe3') ){
				$gtm_xml->setGTMNaAxe3( $selecteur->getValue('axe3') );
			}
			
			$gtm_xml->setGTMTa($selecteur->getValue('ta_level') );
			$gtm_xml->setGTMTaValue($selecteur->getValue('date') );	
			$gtm_xml->setGTMMode( $mode ); 
			for ($i=0; $i < count($gtm_values); $i++)	{
				// Nom des fichiers du GTM (xml et png)
				$gtm_name = $gtm_values[$i]['name'];
				$_ne = $gtm_values[$i]['ne'];
				
				// Création du XML
				$gtm_xml_sub = clone $gtm_xml;
				$gtm_xml_sub->setGTMType($dash_data->getGTMType($gtm_id));
				// Single KPI
				if ($dash_data->getGTMType($gtm_id)=="singleKPI"){
					$gtm_xml_sub->setGTMNeTab($_ne);
				
				}else{
					$gtm_xml_sub->setGTMNe($_ne[$i]);
				}
				$gtm_xml_sub->setGTMProperties();
		
				$gtm_xml_sub->setGTMTabTitle($gtm_values[$i]['title']);
				$gtm_xml_sub->setGTMXAxis($gtm_values[$i]['xaxis']);
				$gtm_xml_sub->setGTMData($gtm_values[$i]['data']);
					
				if (count($gtm_values[$i]['bh_data']) > 0)
					$gtm_xml_sub->setGTMBHData($gtm_values[$i]['bh_data']);
		
				$gtm_xml_sub->setGTMDataLink($gtm_values[$i]['link']);		
				$gtm_xml_sub->setGTMSplitBy($dash_data->getSplitBy($gtm_id));
		
				// 11:21 03/08/2009 GHX
				// Evolution activation du AUTO SCALE par défaut
				$gtm_xml_sub->setGTMAutoScaleY(1);
				$gtm_xml_sub->setGTMAutoScaleY2(1);
			
				$gtm_xml_sub->Build();
		
				$chart_url = REP_PHYSIQUE_NIVEAU_0.'png_file/'.$gtm_name.'.xml';
		
				$gtm_xml_sub->SaveXML($chart_url);
				
				// Création de l'image du GTM pour les formats doc et pdf (on a pas besoin des images pour le format .xls)
				if (($this->ext == 'doc') or ($this->ext == 'pdf')) {
				
					$img_url = REP_PHYSIQUE_NIVEAU_0.'png_file/'.$gtm_name.'.png';
					
					// On crée l'objet en chargeant le fichier de données XML
					$my_gtm = new chartFromXML($chart_url);
			
					// Modification des urls afin de stocker l'ensemble des fichiers (xml + png) dans le dossier "png_file" de l'application
					$my_gtm->setBaseUrl(NIVEAU_0.'/png_file/');
						
					$my_gtm->setBaseDir(REP_PHYSIQUE_NIVEAU_0.'png_file/');
					$my_gtm->setHTMLURL(NIVEAU_0);
						
					// on charge les valeurs par défaut (depuis un autre fichier XML)
					$my_gtm->loadDefaultXML(MOD_CHARTFROMXML . "class/chart_default.xml");
					
					// 18:39 17/08/2009 GHX
					// Si le dashboard n'a pas de données, on crée une image avec le message no data found
					// car sinon erreur JPGRAPH comme quoi on n'a pas donnée dans le graphe
					if ( count($gtm_values[$i]['data']) > 0 )
					{
						// test de création du cadre du GTM
						$my_gtm->saveImage($gtm_name.".png");
					}
					else
					{
						$my_gtm->createImageNoData($gtm_name.".png", $gtm_values[$i]['msg']);
					}
				}

				// Sauvegarde des GTMs crées dans un tableau pour les exports Word et PDF	
                // BBX Evolution export Aircell
				$dash_export[] = array(	'titre'	=> $dash_data->getDashName().' / '.$gtm_values[$i]['properties']['title']['gtm'],
									'image'	=> $img_url,
									'xml'	=> $chart_url);
			}
		}
	
		ob_end_flush();
		
        return $dash_export;
	}

	/**
	*	We export one report
	*
	*	@param string $id_report id du rapport à exporter
	*	@return void	
	*/
       function exportReport($id_report) {

        if ($this->debug)
            displayInDemon(__CLASS__ . '->' . __FUNCTION__ . "(<strong>$id_report</strong>)");

        // on va chercher le rapport
        $query = " --- we get the report $id_report
			SELECT *
			FROM sys_pauto_page_name
			WHERE id_page = '$id_report'
				AND page_type='report'
			LIMIT 1 ";
        if ($this->debug)
            displayInDemon($this->db->pretty($query));
        $report = $this->db->getrow($query);
        if (!$report) {
            displayInDemon("No report found with id: $id_report", 'title');
            // 22/09/2009 BBX : suppression des "!!". BZ 11671
            $this->msg .= "\n\n  No report found with id: $id_report";
            return;
        }
        $this->report_name = $report['page_name'];
        displayInDemon("Report {$report['page_name']}", 'title');
        $this->msg .= "\n\nReport : {$report['page_name']}";
        if ($this->debug)
            displayInDemon(display_array($report, "Report $id_report", '#C9F'));

        // on va chercher les elements du reports
        $query = " --- get the dashs/alarms of the report $id_report
			SELECT *
			FROM sys_pauto_config
			WHERE id_page = '$id_report'
			ORDER BY ligne ASC";
        if ($this->debug)
            displayInDemon($this->db->pretty($query));
        $elements = $this->db->getall($query);
        if (!$elements) {
            displayInDemon("No dashboard or alarm found for that report.");
            // 22/09/2009 BBX : suppression des "!!". BZ 11671
            $this->msg .= "\n  No dashboard or alarm found for that report.";
            return;
        }
        if ($this->debug)
            displayInDemon(display_2Darray($elements, 'elements du rapport', '#F9F'));


        // Preparation des export de dash dans un seul fichier
        $astelliaLogo = get_sys_global_parameters('pdf_logo_dev');
        $clientLogo = get_sys_global_parameters('pdf_logo_operateur');
        $dash_export = array();

        // on boucle sur tous les elements du rapport et on les génère
        foreach ($elements as $element) {
            if ($element['class_object'] == 'page') {
                // 27/04/2011 BBX
                // Modification de la génération des exports
                // Suite à la demande d'Aircel
                // La méthode retourne désormais une liste de graphes
                $dash = $this->exportDashboard($id_report, $element['id_elem']);
                if (is_array($dash))
                    $dash_export = array_merge($dash_export, $dash);
            }
            else {
				$productModel = new ProductModel($element['id_product']);
				
                // element = alarm
                // 01/03/2011 MMT bz 19128 use new exportAlarm method
                $file = $this->exportAlarm($element['id_elem'], $element['class_object'], $element['id_product'], $this->ext);
                // si le nom du fichier retourné commence par !! alors c'est qu'il y a eu une erreur
                if ($file and ($file != NIVEAU_0 . 'report_files/') and (substr($file, 0, 2) != '!!')) {
                    displayInDemon("<a href='$file'>{$this->getFileDownloadLabelFromExtention($file)}</a>");
                    // Correction du bug 10976 - Check sur l'existence du fichier
					// 09/12/2011 ACS Mantis 837 DE HTTPS support
                    if (urlExists($productModel->getCompleteUrl($file))) {
                        // maj 13/09/2010 - MPR : Correction du bz16214
                        // Ajout du paramètre d'entrée "true" permettant de récupérer l'IP publique
                        $this->msg .= "\n ".$productModel->getCompleteUrl($file)."\n";
                    }
                    $this->files[] = $file;
                } else {
                    if (substr($file, 0, 2) == '!!') {
                        displayInDemon($file);
                        $this->msg .= "\n  $file\n";
                    } else {
                        displayInDemon("No data found for that alarm.");
                        // 22/09/2009 BBX : suppression des "!!". BZ 11671
                        $this->msg .= "\n  No data found for that alarm\n";
                    }
                }
            }
        }

        // 27/04/2011 BBX
        // Modification de la génération des exports
        // Suite à la demande d'Aircel
        // Désormais tous les dashboards sont générés dans le même fichier
        if (!empty($dash_export)) {
            $dashboard_export = array(
                'titre' => $report['page_name'],
                'data' => $dash_export,
            );

            // Export des fichier word et pdf
            $DashboardExport = new DashboardExport(
                            $dashboard_export, // tableau des Dashs
                            $this->display_mode, // format du fichier = 'landscape', 'portrait', ...
                            REP_PHYSIQUE_NIVEAU_0 . 'report_files', // dir de sauvegarde
                            'export_', // prefix du fichier
                            REP_PHYSIQUE_NIVEAU_0 . $astelliaLogo,
                            REP_PHYSIQUE_NIVEAU_0 . $clientLogo,
                            REP_PHYSIQUE_NIVEAU_0 . '/images/icones/pdf_alarm_titre_arrow.png'
            );

            // Export Word
            if ($this->ext == 'doc')
                $filePath = $DashboardExport->wordExport();
            // Export Pdf
            if ($this->ext == 'pdf')
                $filePath = $DashboardExport->pdfExport();
            // Export xls
            if ($this->ext == 'xls')
                $filePath = $DashboardExport->excelExport();

            // 18/01/2010 NSE bz 13789
            if (!empty($filePath)) {
                // Construction du nom du fichier:
                // Schedule name
                $file_name = $this->schedule_name;
                // Report name
                $file_name .= '_' . $this->report_name;

                // 29/06/2011 OJT : bz22744, utilisation du schedule frequency
                switch (strtolower($this->_frequency)) {
                    case "day" :
                        $file_name .= "_daily";
                        break;

                    case "week" :
                        $file_name .= "_weekly";
                        break;

                    case "month" :
                        $file_name .= "_monthly";
                        break;

                    default:
                        // Si la fréquence est vide ou inconnue, on ne
                        // la gère pas (cas de preview par exemple)
                        break;
                }

                // Date
                $file_name .= '_' . date('YmdHi');
                // Extension
                $file_name .= '.' . $this->ext;
                // Replacement des espaces par "_"
                $file_name = str_replace(" ", "_", $file_name);

                // Renommage fichier
                $file_folder = substr($filePath, 0, strrpos($filePath, '/'));
                if (!@rename($filePath, $file_folder . '/' . $file_name)) {
                    displayInDemon(__T('A_SCHEDULE_ERROR_RENAME_FILE', "$file_name"));
                    $this->msg .= __T('A_SCHEDULE_ERROR_RENAME_FILE', "$file_name");
                }

                // Construction du chemin du fichier
                $file_url = str_replace(REP_PHYSIQUE_NIVEAU_0, NIVEAU_0, $file_folder) . '/' . $file_name;
                $this->files[] = $file_url;
                // 25/11/2011 NSE bz 24824 : saut de ligne + modification du message
				// 09/12/2011 ACS Mantis 837 DE HTTPS support
				$completeUrl = $this->productModel->getCompleteUrl($file_url);
                if (urlExists($completeUrl)) {
	                displayInDemon("<br /><a href='$completeUrl'>" . __T('U_PDF_FILE_DOWNLOAD_ALL_EXPORT') . "</a>");
                    $this->msg .= "\n  ".$completeUrl."\n";
				}
            }
        }
        // Fin modif Aircel
    }


	/**
	 * 01/03/2011 MMT bz 19128
	 * get the download link label from the file name, checking from its extention (xls, pdf or doc)
	 * @param String $fileName
	 * @return String link label
	 */
	private function getFileDownloadLabelFromExtention($fileName){

		// get the file extention
		$format_ext = strtolower(substr($fileName, -3));

		if($format_ext == 'pdf'){
			$linkLabel = __T('U_PDF_FILE_DOWNLOAD');
		}
		elseif($format_ext == 'xls')
		{
			$linkLabel = __T('U_EXCEL_FILE_DOWNLOAD');
		}
		else if($format_ext == 'doc')
		{
			$linkLabel = __T('U_WORD_FILE_DOWNLOAD');
		}
		else
		{
			$linkLabel = "Click here to download the '$format_ext' file";
		}
		return $linkLabel;
	}


	/**
	*	On exporte un schedule (qui est constitué de plusieurs rapports)
	*
	*	@param string $id_schedule id du schedule qu'on est en train d'exporter
	*	@return void	cette fonction fait appel à exportReport() pour tous les rapports du schedule, puis fait la liste des destinataires et envoie les messages
	*/
	function exportSchedule($id_schedule, $period )
    {
		if ($this->debug) displayInDemon(__CLASS__.'->'.__FUNCTION__."(<strong>$id_schedule</strong>)");
		
		// on va chercher le schedule
		$schedule = $this->db->getrow("SELECT * FROM sys_report_schedule WHERE schedule_id='$id_schedule' ");
		if (!$schedule) {
			displayInDemon("No schedule found with id: $id_schedule");
			return;
		}
		$this->schedule_name = $schedule['schedule_name'];
        $this->_frequency    = $period;
		$this->display_mode	 = $schedule['display_mode'];
		$this->ext           = $schedule['type_files'];		// extention demandée pour le schedule : pdf, doc, ...

		if ($this->ext == 'word') $this->ext = 'doc';
		if ($this->ext == 'excel') $this->ext = 'xls';
		displayInDemon("Schedule {$schedule['schedule_name']}",'title');
		if ($this->debug) displayInDemon(display_array($schedule,"the schedule $id_schedule",'#99F'));
		
		// on va chercher tous les rapports de ce schedule
		$query = " --- we get the reports of schedule $id_schedule
			SELECT *
			FROM sys_pauto_page_name
			WHERE id_page IN ('".str_replace(',',"','",$schedule['report_id'])."')
				AND page_type='report'
			ORDER BY id_page
			";
		if ($this->debug) displayInDemon($this->db->pretty($query));
		$reports = $this->db->getall($query);
		if (!$reports) {
			displayInDemon("No report found with id in: {$schedule['report_id']} in that schedule.");
			return;
		} 
		if ($this->debug) displayInDemon(display_2Darray($reports,"Reports from schedule $id_schedule",'#C9F'));
		
		// en prévoyance des mails à envoyer, on initialise la liste des fichiers et le message du mail 
		$this->msg	= __T("G_MAIL_FILES_KEPT_10_DAYS",get_sys_global_parameters('report_files_history'))."\n\n";
		$this->msg	.= "Schedule {$schedule['schedule_name']}\n";
		$this->files	= array();

		// on lance la composition des rapports du schedule
		foreach ($reports as $report)
			$this->exportReport($report['id_page']);
		
		// on verifie que l'envoie des mails est activé
		if (get_sys_global_parameters("automatic_email_activation")) {
			// maintenant on envoie les mails
			$system_name	=  get_sys_global_parameters("system_name");
			$mail_reply	=  get_sys_global_parameters("mail_reply");
			$m = new Mail();
			$m->From("$system_name <$mail_reply>");
			$m->ReplyTo($mail_reply);
			$m->Subject("$system_name Reporting ({$schedule['schedule_name']})");
	
			// cas des fichiers attachés
			if ($schedule['join_into_mail'] == 1) {
				foreach ($this->files as $file) {
					$file = str_replace(NIVEAU_0,REP_PHYSIQUE_NIVEAU_0,$file);
					$m->Attach($file);
				}
			
			// cas des fichiers non attachés
			} else {
				// si on archive les fichiers sur le serveur, on crée une archive contenant tous les fichiers du rapport
				$name_of_archive = "$this->schedule_name files $this->ext {$schedule['period']} {$this->date[$schedule['period']]}.zip";
				$name_of_archive = str_replace(' ','_',$name_of_archive);

				// Création de l'archive
				$files_to_zip = $this->files;
				
				$files_to_join = array();
				
				foreach ($files_to_zip as $f){
				
					$f = ltrim(strrchr($f,'/'), '/');
					
					// maj 28/08/2009 - Correction du bug 11266 : pas de lien vers les rapports month dans le mail 
					// 		    	   - On génère l'archive à partir des fichiers qui existent
					if( file_exists($f) );
						$files_to_join[] = $f;
					
				}
				
				// 14/12/2011 ACS BZ 25132 Correct "chmod +777" by "chmod 777"
				unset($output);
				$cmd = "cd ".REP_PHYSIQUE_NIVEAU_0."report_files;chmod 777 *;zip $name_of_archive ".implode(' ',$files_to_join).";";
				
				$result = exec($cmd);

				// 16:28 20/08/2009 GHX
				// Correction du BZ 10976
				// Modification de la vérification pour tester la présence du fichier
				// 09/12/2011 ACS Mantis 837 DE HTTPS support
				$handle = @fopen($this->productModel->getCompleteUrl("report_files/$name_of_archive"), 'r');
				// Correction du bug 10976
				if ($handle) {
					// maj 13/09/2010 - MPR : Correction du bz16214
					// 09/12/2011 ACS Mantis 837 DE HTTPS support
					// Ajout du paramètre d'entrée "true" permettant de récupérer l'IP publique
					displayInDemon("Archive file available at ".$this->productModel->getCompleteUrl("report_files/$name_of_archive", true));			
					$this->msg .= "\nYou can download a zip archive of that schedule here:"
						."\n  ".$this->productModel->getCompleteUrl("report_files/$name_of_archive", true);
					fclose($handle);
				}
			}

			$this->msg .= "\n\n-- \n".__T("G_MAIL_AUTOGENERATED_DO_NOT_REPLY");
			$m->Body($this->msg);
			
			// on cherche les destinataires
			// 16:28 07/08/2009 GHX
			// Correction du BZ 10878
			$destinataires = array_unique($this->getDestinataires($id_schedule));
	
			// on envoie les mails aux destinataires
			foreach ($destinataires as $dest) {
				// 11:39 20/08/2009 GHX
				// Correction du BZ 10878
				$mail = clone $m;
				$mail->To($dest);
				$mail->Send();
				displayInDemon("<div>Schedule sent to $dest</div>");			
			}
		}
	}
	
	/**
	*	On cherche la liste des destinataires d'un schedule
	*
	*	@param string	$id_schedule id du schedule dont on cherche les destinataires
	*	@return array	tableau des destinataires
	*/
	function getDestinataires($id_schedule) {
		if ($this->debug) displayInDemon(__CLASS__.'->'.__FUNCTION__."(<strong>$id_schedule</strong>)");

		$destinataires = array();
		
		// on cherche tous les destinataires, tous types (group, user, email) confondus
		$query = " --- getDestinataires($id_schedule)
			select * from sys_report_sendmail where schedule_id='$id_schedule' and on_off=1 ";
		if ($this->debug) displayInDemon($this->db->pretty($query));
		$get_destinataires = $this->db->getall($query);
		
		// on ne devrait jamais avoir ce cas, car on a selectionné les schedules en vérifiant qu'ils ont bien des destinataires, mais bon ...
		if (!$get_destinataires) {
			displayInDemon("The schedule $id_schedule does not have recipients");
		  // 15/02/2011 MMT bz 17884: un seul return avec tableau vide si aucun subscriber
		  // pour cette fonction pour eviter les warning
		} else {
		    if ($this->debug) displayInDemon(display_2Darray($get_destinataires,"Recipients of schedule $id_schedule",'orange'));
		
		    // on analyse chaque destinaire en fonction de son type email, user, group
		    foreach ($get_destinataires as $dest) {
			switch ($dest['mailto_type']) {
				case 'email':
					$destinataires[] = $dest['mailto'];
				break;
				
				case 'user' :
					$get_user_email = $this->db->getone("select user_mail from users where id_user='{$dest['mailto']}' ");
					if ($get_user_email)
						$destinataires[] = $get_user_email;
				break;
			
				case 'group' :
					// 27/07/2009 - MPR : Correction du bug 10648 - Requête SQL incorrecte table usmers au lieu de users
					$get_users = $this->db->getall(" --- fetch all users from group {$dest['mailto']}
						select user_mail from users where id_user in
							(select id_user from sys_user_group where id_group='{$dest['mailto']}') ");
					if ($get_users)
						foreach ($get_users as $gu)
							$destinataires[] = $gu['user_mail'];
				break;
			}
                    }
		}
		
		// on retourne le tableau des destinataires
		return $destinataires;
	}
	
	/**
	*	02/07/2009 BBX : réécriture de la fonction
	*	récupération du offset day le plus récent avec des données
	*
	*	@param string	$id_report id du schedule dont on cherche les destinataires
	*	@return int		$this->offset_day offset day le plus petit avec des données ou (-1) ou false
	*/
	function getFirstDayWithData($id_report) 
	{
		// Affichage des infos dans le démon + contrôle sur les éléments présents du rapport
		$query = "--- get the dashs/alarms of the report $id_report
			SELECT *
			FROM sys_pauto_config
			WHERE id_page = '$id_report'
			ORDER BY ligne ASC";
		if ($this->debug) @displayInDemon($this->db->pretty($query));
		$elements = $this->db->getall($query);
		if (!$elements) {
			return false;
		} 
		if ($this->debug) displayInDemon(display_2Darray($elements,'elements du rapport','#F9F'));

		// Récupération de la configuration des dashboard du rapport
		$queryDashboards = "SELECT sds_id_selecteur, sds_id_page FROM sys_definition_selecteur 
		WHERE sds_report_id = '$id_report'";
		$allDashboards = $this->db->getall($queryDashboards);
		
		// Si pas de Dashboards dans le rapport, on renvoie false
		if(count($allDashboards) == 0) 
		{
			@displayInDemon("No dashboard found for that report.");
			// 22/09/2009 BBX : suppression des "!!". BZ 11671
			$this->msg .= "\n  No dashboard found for that report.";
			return false;
		}

		// On boucle sur tous les dashboards pour récupérer la date la plus récente
		$minOffsetDay = -1;
		foreach($allDashboards as $dashInfos)
		{
			// Récupération de l'id du Dashboard
			$idDashboard = $dashInfos['sds_id_page'];
			// Récupération de l'id du sélecteur du Dashboard
			$idSelecteur = $dashInfos['sds_id_selecteur'];
			// Instanciation d'un objet sélecteur
			$selecteur = new SelecteurModel($idSelecteur);
			// Récupération du niveau d'agrégation réseau
			$Na = $selecteur->getValue('na_level');
			// Récupération du niveau d'agrégation temporel
			$Ta = $selecteur->getValue('ta_level');
			// On interroge le script Ajax du sélecteur afin de récupérer la date la plus récente avec des données
			// 01/04/2010 NSE bz 14927 : Ajout du port (nécessaire si le port 80 est inaccessible)
            // 13/07/2011 NSE bz 22735 : utilisation de  get_adr_server() à la place de localhost car la nouvelle politique de sécurité semble l'interdire
			// 09/12/2011 ACS Mantis 837 DE HTTPS support
			$lastDate = file_get_contents($this->productModel->getCompleteUrl(URL_SELECTEUR.'php/selecteur.ajax.php?action=1&na='.$Na.'&ta='.$Ta.'&id_page='.$idDashboard));
			if($lastDate != '') {
				list($datePart,$hour) = explode('|',$lastDate);
				// Converstion de la date reçue en date US
				switch($Ta)
				{
					/*
					*	heure, jour, jour busy hour : jour dd/mm/yyy => YYYYMMDD
					*/
					case 'hour':
					case 'day':
					case 'day_bh':
						// Conversion du jour en US
						$lastDay = Date::getDateFromSelecteurFormat('day',$datePart);
					break;
					/*
					*	semaine : WWW-YYYY => YYYYWW => dernier jour de la semaine YYYYMMDD
					*/
					case 'week':
					case 'week_bh':
						// Conversion de la semaine en US
						$usWeek = Date::getDateFromSelecteurFormat('week',$datePart);	
						$lastDay = Date::getLastDayFromWeek($usWeek);
					break;
					/*
					*	mois : MM/YYYY => YYYYMM => dernier jour du mois YYYYMMDD
					*/
					case 'month':
					case 'month_bh':
						// Conversion de la semaine en US
						$usMonth = Date::getDateFromSelecteurFormat('month',$datePart);		
						$lastDay = Date::getLastDayFromMonth($usMonth);
					break;
				}
				// Calcul du offsetDay
				$currentOffsetDay = Date::getOffsetDayFromDay($lastDay);
				if($minOffsetDay == -1) $minOffsetDay = $currentOffsetDay;
				// Test de l'offset Day
				if($currentOffsetDay < $minOffsetDay) {
					$minOffsetDay = $currentOffsetDay;
				}
			}
			// Destruction du sélecteur
			unset($selecteur);
		}

		// Mémorisation du Offset Day
		// 06/07/2011 MMT Bz 19767 alarmes parfois non générées car offset day +1 en preview
		if($minOffsetDay != -1){
			$this->offset_day = $minOffsetDay;
		}
		
		// retour de l'offset Day
		return $this->offset_day;
	}
	
	/**
	*	On va chercher la liste des schedules à exporter
	*
	*	@return void à la fin, cette fonction appelle exportSchedule() pour chaque schedule trouvé
	*/
	function exportSchedules() {

		// légende des codes couleurs du debug
		$legende = "
			<table cellpadding='2' cellspacing='2' border='0'>
				<tr>
				<th>légende :</th>
				<th style='background:#99F;'>schedules</th>
				<th style='background:#C9F;'>reports</th>
				<th style='background:#F9F;'>dashboards</th>
				<th style='background:#F99;'>GTMs</th>
				</tr>
			</table>";
		if ($this->debug) displayInDemon($legende);
		
		displayInDemon("Export schedules",'title');
		if ($this->debug) displayInDemon(__CLASS__.'->'.__FUNCTION__.'()');
		
		// on va chercher tous les schedules
		// 19/09/2014 - FGD - Bug 44061 - [SUP][TA Gateway][AVP 48877][Zain KW]: Weekly report scheduled on Sunday not sent
		$currentWeekDay = date('w');
		if($currentWeekDay==0){
			$currentWeekDay = 7;				
		}
		$query = " --- we get all schedules
			SELECT *
			FROM sys_report_schedule
			WHERE
				(	period='hour'
					OR period='day'
					OR (period='week' AND day=" . $currentWeekDay . ")
					OR (period='month' AND day=" . date('d') . ")
				)
				AND on_off=1
			";
		// 15/02/2011 MMT bz 17884: enlève la condition sur la présence de subscriber

		$schedules = $this->db->getall($query);
		if ($this->debug) displayInDemon($this->db->pretty($query));
		if (!$schedules) {
			displayInDemon("No schedule found!");
			return;
		}
		if ($this->debug) displayInDemon(display_2Darray($schedules,"List of schedules",'#99F'));
		
		// on lance la génération de chaque schedule trouvé
		foreach ($schedules as $schedule)
        {
			$this->exportSchedule($schedule['schedule_id'], $schedule['period'] );
        }
	}
	


	/**
	 * 01/03/2011 MMT bz 19128
	 *	Return list of slaves that do not support alarm reporting in Xls or Doc Format
	 * but only PDF, this is based on the value of alarm_reporting_nonPdfFormat_enabled global parameter
	 * @return array of  sys_definition_product rows with, for each slave every table column value
	 */
	// 21/12/2011 ACS BZ 25191 Warnings displayed in Schedule menu
	static public function getUnsupportedXlsAndDocAlarmReportingSlaves(){

		$ret = array();
		$products = ProductModel::getActiveProducts(true);
		foreach ($products as $row){
			$slaveId = $row['sdp_id'];
			if(!self::doesProductSupportXlsAndDocAlarmReporting($slaveId)){
				$ret[] = $row;
			}
		}
		return $ret;
	}


	/**
	 * 01/03/2011 MMT bz 19128
	 *	Return true if the product supports alarm reporting in Xls and Doc Format
	 * this is based on the value of alarm_reporting_nonPdfFormat_enabled global parameter
	 * @param $product_id
	 * @return boolean true if the product supports alarm reporting in Xls and Doc Format
	 */
	static public function doesProductSupportXlsAndDocAlarmReporting($product_id){

		$db = Database::getConnection($product_id);
		$paramValue = $db->getOne("select value from sys_global_parameters where parameters = 'alarm_reporting_nonPdfFormat_enabled'");
		return ($paramValue == 1);
	}

}
?>
