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
	//require_once($repertoire_physique_niveau0 .'class/htmlTablePDF.class.php');

	/*
		Cette classe permet de générer un fichier PDF
		contenant tous les tableaux des alarmes affichées.

		- maj 08 04 2006 christophe : modif ligne 157 pr prise en compte des alarmes avec un nb de résultats trop élevé.
		- maj 12 04 2006 christophe : ligne 160 fixation bug pour le bon positionnment de la flèche à côté des titres.

		- maj le 04 05 2006 : delta entre la version 1191 et la version 1200 (écrasement fichier de la version 1200)
	*/

	class alarmPDFfileCreate{

		/*
			CONSTRUCTEUR de la classe
			nom_pdf : nom du fichier pdf créé.
			titre_pdf : titre du fichier pdf.
			id_user : identifiant de l'utilisateur courrant.
		*/
		function alarmPDFfileCreate($id_user, $database_connection, $selecteur_general_values, $alarm_type, $liste_id_alarme){
			$this->id_user = $id_user;
			$this->titre_pdf = "";
			$this->nom_pdf = "";
			$this->alarm_type = $alarm_type;
			$this->liste_id_alarme = $liste_id_alarme;
			$this->selecteur_general_values = $selecteur_general_values;
			$this->database_connection = $database_connection;
			$this->pdf_ta = $ta;
			$this->pdf_na = $na;
			$this->debug = false; 								// Permet d'activer / désactiver l'affichage du débugage.

			$this->titre_pdf = $this->generatePDFTitle();			// créé le titre du PDF.
			if($this->debug) echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Titre du fichier PDF :  $this->titre_pdf <br>";
		}



		/*
			Créé le titre du fichier PDF à partir des valaeurs du selecteur.
		*/
		function generatePDFTitle(){
			// On récupère les conditions venant du sélecteur.
			// on récupère la liste des NA et des TA et on vérifie si elles sont présentes dans les paramètres venant du sélecteur.
			$query_liste_ta = " select agregation,  agregation_label from sys_definition_time_agregation ";
			$result = pg_query($this->database_connection,$query_liste_ta);
			$nombre_resultat=pg_num_rows($result);
			if($nombre_resultat > 0){
				for ($i = 0;$i < $nombre_resultat;$i++){
					$row = pg_fetch_array($result, $i);
					$ta_array[] = $row["agregation"];
					$ta_array_label[$row["agregation"]] = ($row["agregation_label"] != "")? $row["agregation_label"] : $row["agregation"];
				}
			}

			$query_liste_na = " select agregation, agregation_label from sys_definition_network_agregation ";
			$result = pg_query($this->database_connection,$query_liste_na);
			$nombre_resultat=pg_num_rows($result);
			if($nombre_resultat > 0){
				for ($i = 0;$i < $nombre_resultat;$i++){
					$row = pg_fetch_array($result, $i);
					$na_array[] = $row["agregation"];
					$na_array_label[$row["agregation"]] = ($row["agregation_label"] != "")? $row["agregation_label"] : $row["agregation"];
				}
			}
			// On construit la chaine des conditions venant du sélecteur à inclure dans la requête de sélection des alarmes.
			$time_aggregation = "";
			$network_aggregation = "";

			// On vérifie si il y a une 3ème axe.
			$this->flag_axe3 = false;
			if(isset($this->selecteur_general_values["home_network"])){
				$this->flag_axe3 = true;
			}

			// On parcours le tableau de TA et des NA et on récupère les paramètres du sélecteur.
			for($i=0; isset($ta_array[$i]); $i++){
				if(isset($this->selecteur_general_values[$ta_array[$i]])){
					$time_aggregation = $ta_array[$i];
					$this->time_aggregation = $ta_array[$i];
					$time_aggregation_value = $this->selecteur_general_values[$ta_array[$i]];
				}
			}
			for($i=0; isset($na_array[$i]); $i++){
				if(isset($this->selecteur_general_values[$na_array[$i]])) $network_aggregation = $na_array[$i];
			}
			if($this->debug) echo "<u>Time aggregation</u> : ".$time_aggregation. ", <u>Network aggregation</u> : ".$network_aggregation.". (données venant du sélecteur)<br>";

			$this->ta_value = getTaValueToDisplay($time_aggregation,$time_aggregation_value);

			$this->ta_value_to_display = getTaValueToDisplay($time_aggregation,$time_aggregation_value);

			// Si le fichier PDF est créé à partir du sélecteur et que l'on affiche les données hour de toute une journée
			// (hour est sélectionné dans le sélecteur) alors on enlève les heures, de plus l'identifiant de l'utilisateur doit être différent de -1
			// (sinon c'est que le PDF est créé à partir de la classe alarmMailWithPDF)
			// :!\ prévoir le cas où le fichier PDF est créé depuis la classe d'envoi de salarmes avec le compute mode = daily car si le compute mode
			// est daily et la time aggreagztion = hour alors on calcul les alarmes horaires de toutes la journée donc le titre du pdf ne doit
			// pas comporter d'heure dans la titre
			$ta_value_totre_pdf = $this->ta_value_to_display;
			$compute_mode = get_sys_global_parameters("compute_mode");
			if(($this->id_user != -1 && $time_aggregation == "hour") || ($this->id_user == -1 && $compute_mode == "daily")) $ta_value_totre_pdf = substr($ta_value_totre_pdf,0,10);

			$titre_pdf = getAlarmTypeLabel($this->alarm_type)." ".$ta_value_totre_pdf." ".getTaLabel($time_aggregation)." on ".strtoupper($network_aggregation);

			return ($titre_pdf);
		}


		/*
			Créer le fichier PDF dans le répertoire PDF par défaut.
				>> les paramètres du fichier PDF comme le répertoire de stockage par défaut,
				les images des en-têtes et pieds de page sont stockées dans la table
				SYS_GLOBAL_PARAMETERS
		*/
		function generatePDF(){

			global $path_skin, $repertoire_physique_niveau0;
			// Classe utilisée pour la génération des fichiers PDF.
			include_once($repertoire_physique_niveau0 . "pdf/fpdf153/fpdf.php");
			include_once($repertoire_physique_niveau0 . "class/acurioPDF.class.php");
			include_once($repertoire_physique_niveau0 . "class/htmlTablePDF.class.php");

			$fpdf_fontpath = $repertoire_physique_niveau0 . 'pdf/fpdf153/font/';
			define('FPDF_FONTPATH', $fpdf_fontpath);

			// Configuration du fichier PDF.
			$dir_pdf = get_sys_global_parameters("pdf_save_dir");
			$dir_saving_pdf_file = $repertoire_physique_niveau0.$dir_pdf;

			$id_user = $this->id_user;
			// On vat chercher la liste des tableaux html à afficher.
			$alarm_list = "";
			// Si on précise une liste précise d'alarmes à afficher, on exclut les alarmes qui n'ont pas de résultats.
			if($this->liste_id_alarme != 0){
				// Quand on génère un pdf pour l'envoie par émail, on exclut les alarmes qui n'ont pas de résultats (contrairement à l'affichage). Le cham
				// object_title de la table sys_contenu_buffer est formé par titre_alarme@nombre_résultats. Voilà pourquoi je rajoute à la
				// query : and object_title NOT LIKE '%@0'
				$alarm_list = " and object_id IN($this->liste_id_alarme) and object_title NOT LIKE '%@0' ";
			}
			$query = " SELECT *,oid FROM sys_contenu_buffer WHERE id_user=$id_user and object_contenu_type='html' and object_type='alarm' $alarm_list order by id_contenu desc ";
			$result = pg_query($this->database_connection,$query);
			$nombre_resultat=pg_num_rows($result);

			if($nombre_resultat > 0){
				$pdf=new PDF_HTML_Table();
				$titre_pdf_file_header = $this->generatePDFTitle();//getAlarmTypeLabel($this->alarm_type)." ".getTaLabel($this->time_aggregation)." ".$this->ta_value;
				$pdf->setHeaderTitle($titre_pdf_file_header);		// Titre affiché dans le header de chaque page.
				$pdf->setFooterDate($this->ta_value_to_display);	// Format de la date affichée dans le footer
				$pdf->SetAutoPageBreak(true);
				$pdf->AliasNbPages();
				$pdf->AddPage('L');
				$pdf->SetFont('Arial','',6);
				for ($i = 0;$i < $nombre_resultat;$i++){
					$row = pg_fetch_array($result, $i);
					// On affiche le titre du tableau d'alarmes.
					$object_title = explode("@",$row["object_title"]);
					$titre_alarme = $object_title[0];
					$nb_resultats = $object_title[1];
					$nb_total_ligne = $object_title[2];
					$pdf->setX(10);
					$pdf->SetFont('Arial','B',10);
					if($pdf->GetY() > 190) $pdf->AddPage('L');
					$pdf->Image($repertoire_physique_niveau0.'images/icones/pdf_alarm_titre_arrow.png', $pdf->GetX(), $pdf->GetY());
					$pdf->setX(15);
					$pdf->SetTextColor(0);
					$pdf->Cell(0,8,$titre_alarme.'  ('.$nb_resultats.'  results)',0,0,'L');
					// le trim(,chr(160)) est là parce que le caractère euro apparaissant dans les noms des signets dans Acrobat Reader.
					$pdf->Bookmark(trim($titre_alarme.'  ('.$nb_resultats.'  results)',chr(160)));
					$tabHTMLbrut = $row["object_source"];
					$pdf->WriteHTML("<br>$tabHTMLbrut<br>",$nb_total_ligne,$this->alarm_type, $this->id_user, $this->flag_axe3);
				}
				if(!isset($this->PDFfileName)){
					echo "PDFfileName property is not defined, please call the setPDFfileName function befor generatePDF function.";
					exit;
				}
				$this->nom_pdf = $this->PDFfileName;
				$pdf->Output($dir_saving_pdf_file.$this->nom_pdf,'F');	// paramètre F pour forcer l'enregistrement sur le serveur.
				?>
					<input type="hidden" name="nomPDF" id="nomPDF" value="<?=$this->nom_pdf?>">
				<?
			}
		}


		/*
			Retourne le nom du fichier PDF créé.
		*/
		function getPDFfileName(){
			return $this->nom_pdf;
		}

		/*
			Initialise le nom du PDF
			generatePDFfileName
		*/
		function setPDFfileName($pdf){
			$this->PDFfileName = $pdf;
			/*
			if($this->liste_id_alarme == 0){
				return("alarms_".$this->alarm_type ."_".rand().".pdf");
			} else {
				$date = date("Ymd");
				return("alarms_".$this->alarm_type ."_".$this->pdf_na."_".$this->pdf_ta."_".$date."_".rand().".pdf");
			}
			*/
		}



	} // end of class
?>
