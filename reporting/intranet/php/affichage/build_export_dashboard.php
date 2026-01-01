<?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
	- maj 11/03/2008, maxime : On génère le fichier export d'un dashboard en fonction du type doc,xls ou pdf
	- maj 09/04/2008, benoit : correction du bug 6228
*	
*/
?>
<?
session_start();
session_cache_limiter('private');
include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
include_once($repertoire_physique_niveau0 . "php/edw_function.php");
switch($_GET['type_file']){

	// Export du dashboard en .PDF
	case 'pdf' :
		include_once($repertoire_physique_niveau0 . "php/edw_function.php");
		include_once($repertoire_physique_niveau0 . "pdf/fpdf153/fpdf.php");
		include_once($repertoire_physique_niveau0 . "class/acurioPDF.class.php");
		include_once($repertoire_physique_niveau0 . "pdf/complement_pdf.php");
		include_once($repertoire_physique_niveau0 . "class/htmlTablePDF.class.php");
		
		$fpdf_fontpath = $repertoire_physique_niveau0 . 'pdf/fpdf153/font/';
		define('FPDF_FONTPATH', $fpdf_fontpath);

		$pdf_repertoire_stockage = $repertoire_physique_niveau0.get_sys_global_parameters("pdf_save_dir");

		$page_title =	$_GET['page'];			// titre du PDF.
		$object_type =	$_GET['object_type'];	// type des objets à insérer dans le PDF.
		$tableau_html = $str;	// pour la caddy
		$page_dest =	(isset($_GET["page_dest"]) and $_GET['page_dest'] != "") ? $_GET["page_dest"] : "";	// page où l'on doit être redirigé.
		
		// 14/01/2008 - Modif. benoit : definition de la variable '$save_in_file' envoyée par GET qui définie si l'on doit proposer le téléchargement du fichier PDF

		if (isset($_GET['save_in_file']) && $_GET['save_in_file'] == "true") {
			$save_in_file = true;
		}
		else 
		{
			$save_in_file = false;
		}

		//echo "object_type=$object_type<br>";

		class Object_PDF {

			/*
				Constructeur de la classe
			*/
		        function  Object_PDF($object_type, $page_dest, $save_in_file = false){

		                global  $pdf_repertoire_stockage,$page_title,$pdf_image_logo,$pdf_image_logo_astellia,$tableau_pdf,$database_connection,$id_user,$niveau0,$tableau_html;

		                $this->database_connection=$database_connection;
		                $this->tableau_html=$tableau_html;

		                $this->object_type=$object_type;
		                $this->page_dest = $page_dest;

						$this->save_in_file = $save_in_file;

		                $this->get_pdf_element();

		                $this->ylong=40;
		                $orientation_pdf = 'L';

		                $this->Object_PDF = new PDF($orientation_pdf,'mm','A4');                 //orientation = P ou L, unité = mm, Taille = A4
		                $this->Object_PDF->nom_rapport_pdf = "overnetworkelement.pdf";
		                $this->Object_PDF->AliasNbPages();
						$this->Object_PDF->setDisplayMode('landscape');
		                $this->Object_PDF->SetAutoPageBreak(true);

		                // On génère un nom aléatoire afin d'éviter les conflits si beaucoup
		                // d'utilisateurs créent des fichiers PDF en même temps.
		                $date = date("Ymd_His");
		                $nom_rapport_pdf = $pdf_repertoire_stockage."pdf_" . md5(uniqid(rand(), true)) . ".pdf";
		                $this->Object_PDF->nom_rapport_pdf = $nom_rapport_pdf;
		                $this->Object_PDF->orientation_pdf = $orientation_pdf;

		                if(isset($_GET['type_PDF'])){
		                        $this->Object_PDF->titre_pdf="Trending & Aggregation Graph(s) Selection";
		                        $this->Object_PDF->setHeaderTitle("Trending & Aggregation Graph(s) Selection");
		                } else {
                                        // 17/01/2011 BBX
                                        // Table sys_pdf_mgt obsolète
                                        // BZ 20200
                                        //$result_nb = 0;
                                        /*
		                        $query=" select page_titre from sys_pdf_mgt where id_user='$id_user'  order by oid desc limit 1 ";
		                        $result = pg_query($this->database_connection, $query);
		                        $result_nb = pg_num_rows($result);
		                        if ($result_nb>0){
		                                $row = pg_fetch_array($result,0);
		                                $this->Object_PDF->titre_pdf = $row["page_titre"];
		                                $this->Object_PDF->setHeaderTitle($row["page_titre"]);
		                                if($this->page_dest != ""){
		                                        $dash = str_replace(" ","_",$row["page_titre"])."_";
		                                        $nom_rapport_pdf = $pdf_repertoire_stockage."pdf_".$dash . $date . ".pdf";
		                                        $this->Object_PDF->nom_rapport_pdf = $nom_rapport_pdf;
		                                }
		                        } else {*/
		                                $this->Object_PDF->titre_pdf = "Trending & Aggregation Pdf export";
		                                $this->Object_PDF->setHeaderTitle("Trending & Aggregation Pdf export");
		                        /*}*/

		                        // On vat chercher le dernier commentaire du dashboard.

		                        $query = "
		                                SELECT edw_comment.oid, libelle_comment
		                                        FROM edw_comment, sys_pauto_config, sys_pauto_page_name
		                                        WHERE
		                                        sys_pauto_config.id_elem = $this->id_graph AND
		                                        sys_pauto_config.id_page = sys_pauto_page_name.id_page AND
		                                        sys_pauto_page_name.id_page = edw_comment.id_elem
		                                        ORDER BY
		                                                edw_comment.oid DESC
		                        ";
								
		                        $result = pg_query($this->database_connection, $query);
		                        $result_nb = pg_num_rows($result);
		                        if($result_nb > 0){
		                                $row = pg_fetch_array($result, 0);
		                                $this->commentaire_dashboard = $row[1];
		                        }

		                }

		                $this->Object_PDF->AddPage();
		                $this->Object_PDF->SetX(5);
		                // Si il y a un commentaire sur le dashboard, alors on l'affiche sur la première page.
		                if(isset($this->commentaire_dashboard)){
		                        global $repertoire_physique_niveau0;
		                        $this->Object_PDF->SetY(35);
		                        $this->Object_PDF->SetFont('Helvetica','B',14);
		                        $this->Object_PDF->SetTextColor(0);
		                        $this->Object_PDF->SetDrawColor(200,200,200);
		                        $this->Object_PDF->Image($repertoire_physique_niveau0.'images/icones/pdf_alarm_titre_arrow.png', 10, $this->Object_PDF->GetY()-1);
		                        $this->Object_PDF->SetX(10);
		                        $this->Object_PDF->MultiCell(0,4,"        Dashboard comment :",0,'J',0);
		                        $this->Object_PDF->SetY(55);
		                        $this->Object_PDF->SetFont('Helvetica','I',10);
		                        $this->Object_PDF->SetTextColor(86,86,86);
		                        $this->Object_PDF->MultiCell(0,6,$this->commentaire_dashboard,1,'J',0);
		                        $this->Object_PDF->SetTextColor(0);
		                        $this->Object_PDF->AddPage();
		                        $this->Object_PDF->SetX(5);
		                }
		                $this->Add_content_in_Pdf();
		                $this->Generate_pdf();
		        }

		        // retourne le nom du fichier PDF.
		        function get_PDFFileName(){
		                return $this->Object_PDF->nom_rapport_pdf;
		        }

		        /*
		                Redirige le navigateur vers la page d'affichage / visualisation du document PDF.
		        */


		        function parametre_sortie()
		        {

		         return $this->Object_PDF->nom_rapport_pdf;

		        }

				function Generate_pdf(){
					
					$this->Object_PDF->Output($this->Object_PDF->nom_rapport_pdf);
					$this->Object_PDF->Close();

					// 14/01/2008 - Modif. benoit : si l'on doit proposer le téléchargement du fichier PDF, on construit la page HTML correspondante

					if ($this->save_in_file) {
						$download_label = (strpos(__T('U_PDF_FILE_DOWNLOAD'), "Undefined") === false) ? __T('U_PDF_FILE_DOWNLOAD') : "Click here to download the Pdf file";
						
						// 09/04/2008 - Modif. benoit : correction du bug 6228. Le lien renvoyant l'url du PDF était incorrecte

						echo $this->Object_PDF->nom_rapport_pdf;
						// echo str_replace(REP_PHYSIQUE_NIVEAU_0, NIVEAU_0, $this->Object_PDF->nom_rapport_pdf);
						
						// echo str_replace(REP_PHYSIQUE_NIVEAU_0, NIVEAU_0, $word->parametre_sortie);
					}
					else 
					{
						if ($this->mode_robot!="auto")
						{
							if($this->page_dest == ""){
						?>
								<script type="text/javascript">
									document.location="view_pdf.php?pdf=<?=$this->Object_PDF->nom_rapport_pdf?>";
								</script>
						<?
							} 
							else 
							{
						
								echo '<script type="text/javascript">
									document.location="'.$this->page_dest.'?pdf_file_name='.$this->get_PDFFileName().'";</script>';
						
							}
						}
						else
						{
							$this->parametre_sortie();
						}				
					}
				}

		        /*
		                Retourne le spropriétés d'un élément PDF
		        */
		        function get_pdf_element(){
		                global $id_user;

		                // Si on fait un export PDF depuis le caddy, la variable $_GET['type_PDF']est définie
		                if(isset($_GET['type_PDF'])){
							// On fait un export PDF depuis le caddy.
							$query="select * from sys_panier_mgt where id_user='$id_user' order by oid asc";
							$result = pg_query($this->database_connection, $query);
		
							$result_nb = pg_num_rows($result);
							for ($i = 0;$i < $result_nb;$i++){
								$row = pg_fetch_array($result, $i);
								// si l'objet est de type table, on ne met pas le même contenu.
								if($row["object_type"] == "table"){
									$this->tableau_pdf["object_content"][$i]= $this->tableau_html[$row["object_id"]];       // $row["object_id"];
								} else if($row["object_type"] == "builder_report"){
									$this->tableau_pdf["object_content"][$i]=urldecode($row["object_id"]);
								} else if($row["object_type"] == "alarm_export"){
									$this->tableau_pdf["object_content"][$i] = urldecode($row["object_id"]);
								} else {
									$this->tableau_pdf["object_content"][$i]=$row["object_id"];
									$this->tableau_pdf["last_comment"][$i]=$row["last_comment"];
								}
								$this->tableau_pdf["object_type"][$i] = 	$row["object_type"];
								$this->tableau_pdf["object_titre"][$i] = 	$row["object_title"];
							}
		                } else {
							
							$query="select * from sys_pdf_mgt where id_user='$id_user' and object_type='$this->object_type' order by oid asc";
		
							$result = pg_query($this->database_connection, $query);
							$result_nb = pg_num_rows($result);
							for ($i = 0;$i < $result_nb;$i++){
								$row = pg_fetch_array($result, $i);
								$this->id_graph = $row["object_graph_number"];
								$this->tableau_pdf["object_content"][$i] =      $row["object_content"];
								$this->tableau_pdf["object_type"][$i]    =      $row["object_type"];
								$this->tableau_pdf["object_titre"][$i]   =      $row["object_titre"];
								$this->tableau_pdf["object_id"][$i]      =      $row["object_graph_number"];
								$this->tableau_pdf["last_comment"][$i]   =      $row["last_comment"];
							}
		                }
		        }

			/*
				Affiche le titre de l'élément courrant.
			*/
			function display_titre($titre){
				global $repertoire_physique_niveau0;
				$this->Object_PDF->Image($repertoire_physique_niveau0.'images/icones/pdf_alarm_titre_arrow.png', 10, $this->Object_PDF->GetY()-4);
				$y=$this->Object_PDF->GetY();
				$titre_len=strlen($titre);
				$x= 20;
				$this->Object_PDF->SetFont('Arial', 'B', 12);
				$this->Object_PDF->Text($x,$y,$titre);
				// le trim(,chr(160)) est là parce que le caractère euro apparaissant dans les noms des signets dans Acrobat Reader.
				$this->Object_PDF->Bookmark(trim($titre,chr(160)));
				$this->ylong=$this->ylong+3;
				$this->Object_PDF->ln(5);
			}


			/*
				Gestion du saut de page
			*/
			function saut_de_page(){
				if ($this->ylong>190){
						$this->Object_PDF->AddPage();
						$this->ylong=0;
						$this->Object_PDF->SetX(5);
						$this->Object_PDF->SetY(40);
						$this->ylong=40;
				}
			}

			/*
				Affiche le dernier commentaire de l'élément.
			*/
			function DisplayLastComment($last_comment){
				// Si les commentaires sont activés.
				if($_SESSION["activated_faiture"]["comment"]){
					if($last_comment != "No comment..." && str_replace(" ","",$last_comment) != ""){
						$this->Object_PDF->SetFont('Helvetica','I',8);
						$this->Object_PDF->SetTextColor(86,86,86);
						$this->Object_PDF->SetDrawColor(200,200,200);
						$last_comment = strlen($last_comment) > 1000 ? substr($last_comment,0,1000)."..." : $last_comment;
						$this->Object_PDF->MultiCell(0,4,$last_comment,1,'J',0);
						$this->Object_PDF->SetTextColor(0);
					}
				}
			}


			/*
				Ajoute du contenu au PDF courrant : un graph, un tableau html ou un objet builder report.
			*/
			function Add_content_in_Pdf(){
				for ($i=0;$i<count($this->tableau_pdf["object_content"]);$i++){
					switch($this->tableau_pdf["object_type"][$i]){
						case "graph" :
						case "gis_raster" :
							$this->display_titre($this->tableau_pdf["object_titre"][$i]);
							$this->ylong=0;
							$this->Object_PDF->SetX(5);
							$this->Object_PDF->SetY(35);
							$this->DisplayLastComment($this->tableau_pdf["last_comment"][$i]);
							$this->add_image($this->tableau_pdf["object_content"][$i]);
							$this->ylong=40;
							$tmp = $i + 1;
							if($tmp<count($this->tableau_pdf["object_content"]))$this->Object_PDF->AddPage();
							break;
						  case "graph_zoomplus" :
							$this->display_titre($this->tableau_pdf["object_titre"][$i]);
							/*$this->Object_PDF->ln(10);
							$this->ylong=$this->ylong+15;*/
							$this->ylong=0;
							$this->Object_PDF->SetX(5);
							$this->Object_PDF->SetY(35);
							$this->DisplayLastComment($this->tableau_pdf["last_comment"][$i]);
							$this->add_image($this->tableau_pdf["object_content"][$i]);
							$this->ylong=40;
							$tmp = $i + 1;
							if($tmp<count($this->tableau_pdf["object_content"]))$this->Object_PDF->AddPage();
							break;
						 case "graph_dashboard_solo" :
							$this->display_titre($this->tableau_pdf["object_titre"][$i]);
							$this->ylong=0;
							$this->Object_PDF->SetX(5);
							$this->Object_PDF->SetY(35);
							$this->DisplayLastComment($this->tableau_pdf["last_comment"][$i]);
							$this->add_image($this->tableau_pdf["object_content"][$i]);
							$this->ylong=40;
							$tmp = $i + 1;
							if($tmp<count($this->tableau_pdf["object_content"]))$this->Object_PDF->AddPage();
							break;
						case "table" :
							$this->saut_de_page();
							$this->display_titre($this->tableau_pdf["object_titre"][$i]);
							$this->add_html_table($this->tableau_pdf["object_content"][$i]);
							$this->Object_PDF->ln(10);
							$this->ylong=$this->ylong+5;
							break;
						// Image d'un graph construit dans le query builder.
						case "builder_report" :
							$this->saut_de_page();
							$this->display_titre($this->tableau_pdf["object_titre"][$i]);
							$this->add_html_table($this->tableau_pdf["object_content"][$i]);
							$this->Object_PDF->ln(10);
							$this->ylong=$this->ylong+5;
					}
				}
			}

		        /*
		                Parse le tableau html avant l'affichage dans le fchier pdf
		        */
		        function  add_html_table($table){
		                $str="";
		                ////echo "<br>apres split<br>";
		                $str_len=strlen($table);
		                $delete=false;
		                for ($i=0;$i<$str_len;$i++){
		                        $char= substr($table,$i,1);
		                        if ($char=="<"){$delete=true;}
		                        if ($char==">"){$delete=false;}
		                        if ($delete==false){$str.=$char;}
		                }
		                $str=str_replace("<br>","",$str);
		                $str=str_replace("\n","",$str);
		                $str=str_replace(">>>>>","",$str);
		                $str=str_replace(">>>>","/",$str);
		                $str=str_replace(">>>","",$str);
		                $str=str_replace(">>","@",$str);
		                $str=str_replace(">","/",$str);

		                $str_array_line=explode('/',$str);

		                for ($k=0;$k<count($str_array_line);$k++){
		                        $this->str_array[$k]=explode('@',$str_array_line[$k]);
		                }
		                $this->add_pdf_table();
		        }


		        /*
		                Ajoute un tableau html
		        */
		        function add_pdf_table(){
		                $this->Object_PDF->SetX(5);

		                //calcul de la largeur max de chaque colonne.
		                $scale=2.4;
		                $this->pdf_data_array[0][0]="";

		                for ($i=0;$i<count($this->str_array);$i++){
		                        for ($j=0;$j<count($this->str_array[$i]);$j++){
		                                if ($i==0){
		                                        $this->pdf_data_array[$i][$j+1]=$this->str_array[$i][$j];
		                                } else {
		                                        $this->pdf_data_array[$i][$j]=$this->str_array[$i][$j];
		                                }
		                        }
		                }

		                for ($i=0;$i<count($this->pdf_data_array);$i++){
		                        for ($j=0;$j<count($this->pdf_data_array[$i]);$j++){
		                                $larg_cell_max[$j]=0;
		                        }
		                }

		                for ($i=0;$i<count($this->pdf_data_array);$i++){
		                        for ($j=0;$j<count($this->pdf_data_array[$i]);$j++){
		                                $larg_cell[$j]=strlen($this->pdf_data_array[$i][$j]);
		                                if ($larg_cell[$j]>$larg_cell_max[$j]){$larg_cell_max[$j]=$larg_cell[$j];}
		                        }
		                }

		                //prepare les bloc
		                $larg=ceil($larg_cell_max[0]*$scale);
		                $larg_init=ceil($larg_cell_max[0]*$scale);
		                $bloc=0;
		                $nb=0;

		                for ($k=1;$k<count($larg_cell);$k++){
		                        $larg=$larg+(ceil($larg_cell_max[$k]*$scale));
		                        $nb=$nb+1;
		                        $nb_par_bloc[$bloc]=$nb;

		                        if ($larg>175){
		                                $bloc=$bloc+1;
		                                $larg=ceil($larg_cell_max[0]*$scale);
		                                $nb=0;
		                        }
		                }

		                $q=0;

		                for ($k=0;$k<=$bloc;$k++){
		                        $depart=1;
		                        for ($q=0;$q<$k;$q++){
		                                $depart=$depart+$nb_par_bloc[$q];
		                        }
		                        $fin=$depart+$nb_par_bloc[$k];

		                        //pour chaque bloc j'affiche la premeire colonne
		                        if ($fin>$depart){
		                                for ($i=0;$i<count($this->pdf_data_array);$i++){
		                                        $this->Object_PDF->SetX(10);
		                                        if ($i==0){
		                                                $this->Object_PDF->SetTextColor(0,0,0); //couleur noire
		                                                $this->Object_PDF->SetfillColor(255, 255,255); //couleur bleu foncé
		                                                $this->Object_PDF->SetFont('Arial', '', 10);
		                                                $largeur_cell=ceil($larg_cell_max[0]*$scale);
		                                                $this->Object_PDF->Cell($largeur_cell,5,$this->pdf_data_array[$i][0],0, 0, 'C', 1);
		                                        } else {
		                                                $this->Object_PDF->SetTextColor(255,255,255); //couleur noire
		                                                $this->Object_PDF->SetfillColor(155, 155, 155); //couleur bleu foncé
		                                                $this->Object_PDF->SetFont('Arial', '', 10);

		                                                $largeur_cell=ceil($larg_cell_max[0]*$scale);
		                                                $this->Object_PDF->Cell($largeur_cell,5,$this->pdf_data_array[$i][0], $cell_border, 0, 'C', 1);
		                                        }

		                                        for ($j=$depart;$j<$fin;$j++){
		                                                if ($i==0 and $j==0){$cell_border=0;}else{$cell_border=1;}
		                                                if (($i==0 and $j>0)){
		                                                        $this->Object_PDF->SetTextColor(255,255,255); //couleur noire
		                                                        $this->Object_PDF->SetfillColor(155, 155, 155); //couleur bleu foncé
		                                                        $this->Object_PDF->SetFont('Arial', '', 10);
		                                                } else {
		                                                        $this->Object_PDF->SetTextColor(0,0,0); //couleur noire
		                                                        $this->Object_PDF->SetfillColor(255, 255,255); //couleur bleu foncé
		                                                        $this->Object_PDF->SetFont('Arial', '', 10);
		                                                }

		                                                $largeur_cell=ceil($larg_cell_max[$j]*$scale);
		                                                $this->Object_PDF->Cell($largeur_cell,5,$this->pdf_data_array[$i][$j], $cell_border, 0, 'C', 1);
		                                        }
		                                        $this->Object_PDF->ln(5);
		                                        $this->ylong=$this->ylong+5;
		                                }
		                           $this->Object_PDF->ln(5);
		                           $this->ylong=$this->ylong+5;
		                        }
		                }
		        }

		        /*
		                Ajoute une image
		                l'image est centrée horizontalement et verticalement.
		        */
		        function  add_image($image_name){
		                global  $repertoire_physique_niveau0;
		                $scale = 0.26;                // changer l'échelle pr modifier le redimentionnement des images des graph.
		                $path_of_png_file = $repertoire_physique_niveau0.get_sys_global_parameters("pdf_save_dir");
		                $file =                         $path_of_png_file.$image_name;

		                $size_image =                 getimagesize ($file);
		                $largeur_image =         ceil($size_image[0] * $scale);
		                $hauteur_image =         ceil($size_image[1] * $scale);

		                $largeurPDF = 290;
		                $hauteurPDF = 210 - ceil($this->Object_PDF->GetY() + 25); // hauteur restante.

		                $this->ylong = $this->ylong+$hauteur_image;
		                $this->saut_de_page();

		                $this->Object_PDF->SetX(10);

		                // On centre horizontalement et verticalement l'image.
		                $x = ceil(($largeurPDF / 2) - ($largeur_image / 2));
		                $y = ceil(($hauteurPDF / 2) - ($hauteur_image / 2)) + $this->Object_PDF->GetY();

		                $this->Object_PDF->SetfillColor(155, 155, 155);

		                $this->Object_PDF->Rect($x-0.5,$y-0.5,$largeur_image+1,$hauteur_image+1, "F");

		                $this->Object_PDF->SetX($x);
		                $this->Object_PDF->SetY($y);

		                $this->Object_PDF->Image($file, $x, $y,$largeur_image);

		                $y=$this->Object_PDF->GetY();
		                $this->Object_PDF->SetX(5);
		                $this->Object_PDF->SetY($y);

		                //apres tout image saut de ligne
		                $this->Object_PDF->ln($hauteur_image);

		        }
		}

		// 14/01/2008 - Modif. benoit : ajout du parametre '$save_in_file' au constructeur de la classe 'Object_PDF'

		$display_pdf = new Object_PDF($object_type, $page_dest, $save_in_file);
		
	break;
	
	// Export du dashboard en .xls
	case 'xls':
		// 20/05/2010 NSE : relocalisation du module excel dans le CB
		include_once(REP_PHYSIQUE_NIVEAU_0."modules/excel/class.writeexcel_workbook.inc.php");
		include_once(REP_PHYSIQUE_NIVEAU_0."modules/excel/class.writeexcel_worksheet.inc.php");
		
		// 11/01/2008 - Modif. benoit : en fonction de la présence ou non de la variable GET 'object_type' on cible soit la table 'sys_pdf_mgt' (export depuis les dashboards), soit la table 'sys_panier_mgt' (export depuis le panier)

		if (isset($_GET['object_type'])) {	// Export depuis le menu contextuel dans les dashboards
			
			$sql =	 " SELECT replace(object_content, '.png', '') AS object_summary, object_titre AS object_title"
					." FROM sys_pdf_mgt WHERE id_user='".$_SESSION['id_user']."' AND object_type='".$_GET['object_type']."' ORDER BY oid ASC";

			// 15/01/2008 - Modif. benoit : dans le cas de l'export Excel depuis le mode "zoomplus", on limite les resultats à une seule ligne

			if (trim($_GET['id_from_zoomplus']) != "" && $_GET['object_type'] == "graph_zoomplus") $sql .= " LIMIT 1";
		}
		else // Sélection des graphes du client courant dans le panier
		{
			$sql = "SELECT * FROM sys_panier_mgt WHERE id_user = ".$_SESSION['id_user']." AND object_type = 'graph'";
		}

		$req = pg_query($database_connection, $sql);

		if (pg_num_rows($req) > 0) {
			
			// S'il existe des graphes dans le panier, on définit un nouveau fichier Excel

			$filename = REP_PHYSIQUE_NIVEAU_0.get_sys_global_parameters("pdf_save_dir")."export_excel_".md5(uniqid(rand(), true)).".xls";
			$workbook = &new writeexcel_workbook($filename);
			$worksheet = &$workbook->addworksheet("Graph Export");
			
			$row_index = 0;
			
			while ($row = pg_fetch_array($req)) {

				// 15/01/2008 - Modif. benoit : dans le cas du mode "zoomplus", l'id du graphe est passé en parametre GET

				if (trim($_GET['id_from_zoomplus']) != "" && $_GET['object_type'] == "graph_zoomplus") 
				{
					$id_graph = $_GET['id_from_zoomplus'];
				}
				else 
				{
					$id_graph = $row['object_summary'];	
				}
					
				// A partir de l'identifiant du graphe dans le panier, on récupere ses données Excel en session

				$onglet							= $_SESSION['onglet_excel'][$id_graph];	
				$tableau_excel_export			= $_SESSION['tableau_data_excel'][$id_graph];
				$tableau_excel_export_abscisse	= $_SESSION['tableau_data_excel_abscisse'][$id_graph];
				$tableau_excel_export_ordonnee	= $_SESSION['tableau_data_excel_ordonnee'][$id_graph];		
							
				// On construit le contenu du fichier Excel à partir des données du graphe
					
				// Titre du graphe
				
				$title_style =& $workbook->addformat(array(bold => 1));		
				
				$worksheet->write($row_index, 0, $row['object_title'], $title_style);
				$row_index += 2;
				
				// Ecriture de la première cellule du tableau
				
				$worksheet->write($row_index, 0, $onglet);
				
				// Ecriture de l'abcisse
				
				$row_index_tmp = $row_index + 1;
				
				for ($i=0;$i<count($tableau_excel_export_abscisse);$i++){		
					$worksheet->write($row_index_tmp, 0, $tableau_excel_export_abscisse[$i]);
					$row_index_tmp += 1;
				}
				
				// Ecriture de l'ordonnée
				
				$col_index = 1;
				
				for ($i=0;$i<count($tableau_excel_export_ordonnee);$i++){	
					$worksheet->write($row_index, $col_index, $tableau_excel_export_ordonnee[$i]);
					$col_index += 1;
				}
				
				// Ecriture des données
				
				$col_index = 0;
				
				for ($i=0;$i<count($tableau_excel_export);$i++){
					
					$row_index_tmp = $row_index + 1;
					$col_index += 1;
					
					for ($j=0;$j<count($tableau_excel_export[$i]);$j++){					
						$worksheet->write($row_index_tmp, $col_index, $tableau_excel_export[$i][$j]);
						$row_index_tmp += 1;
					}
				}
				
				$row_index = $row_index_tmp + 2;
			}
			
			$workbook->close();
			// 14/01/2008 - Modif. benoit : si la variable GET 'save_in_file' est définie, on n'affiche pas le fichier Excel directement mais on propose un lien pour l'ouvrir / le télécharger

			if (isset($_GET['save_in_file'])) {
				
				// 15/01/2008 - Modif. benoit : remise en forme du lien de telechargement du fichier Excel

				$file_link = str_replace(REP_PHYSIQUE_NIVEAU_0, NIVEAU_0, $filename);

				$download_label = (strpos(__T('U_EXCEL_FILE_DOWNLOAD'), "Undefined") === false) ? __T('U_EXCEL_FILE_DOWNLOAD') : "Click here to download the Excel file";

			echo $file_link;
			
			}
			else 
			{
				header("Content-Type: application/x-msexcel");
				$fh=fopen($filename, "rb");
				fpassthru($fh);
				if (is_file($filename)) unlink($filename);
			}
		}
	break;
	
	// Export du dashboard en .doc
	case 'doc':
		include_once(REP_PHYSIQUE_NIVEAU_0."class/export_word.class.php");
		
		
		$tableau_html = $str;	// pour la caddy
		$page_dest = isset($_GET["page_dest"]) ? $_GET["page_dest"] : "";	// page où l'on doit être redirigé.

		// 11/01/2008 - Modif. benoit : ajout du parametre '$object_type' au constructeur de la classe 'Object_Word'

		// 14/01/2008 - Modif. benoit : ajout du parametre '$save_in_file' au constructeur de la classe 'Object_Word'
		class Object_Word {
		/*
			Constructeur de la classe
		*/
			public function __construct($type_word, $object_type, $page_dest, $save_in_file = false){

				global $page_title,$pdf_image_logo,$pdf_image_logo_astellia,$tableau_word,$database_connection,$id_user,$niveau0,$tableau_html;

				$this->database_connection=$database_connection;
				$this->tableau_html=$tableau_html;

				$this->type_word = $type_word;
				
				$this->object_type = $object_type;
				$this->page_dest = $page_dest;

				$this->export_path = REP_PHYSIQUE_NIVEAU_0.get_sys_global_parameters("pdf_save_dir");

				($save_in_file == "true") ? $this->save_in_file = true : $this->save_in_file = false;

				$this->getElements();

				$orientation = 'landscape';

				// On génère un nom aléatoire afin d'éviter les conflits si beaucoup
				// d'utilisateurs créent des fichiers Word en même temps.
				
				$date = date("Ymd_His");
				$this->nom_rapport = $this->export_path."word_" . md5(uniqid(rand(), true));
										
				if($this->type_word != "")
				{			
					$titre_word = "Trending & Aggregation Graph(s) Selection";
				} 
				else 
				{
					$query = "SELECT page_titre FROM sys_pdf_mgt WHERE id_user='$id_user' ORDER BY oid DESC LIMIT 1";
					$result = pg_query($this->database_connection, $query);

					if (pg_num_rows($result) > 0)
					{
						$row = pg_fetch_array($result,0);
						$titre_word = $row["page_titre"];

						if($this->page_dest != ""){
							$dash = str_replace(" ","_",$row["page_titre"])."_";
							$this->nom_rapport = $this->export_path."word_".$dash.$date;
						}
					} 
					else 
					{
						$titre_word = "Trending & Aggregation Word export";
					}

					// On va chercher le dernier commentaire du dashboard.

					$query =	 " SELECT edw_comment.oid, libelle_comment"
								." FROM edw_comment, sys_pauto_config, sys_pauto_page_name"
								." WHERE sys_pauto_config.id_elem = $this->id_graph"
								." AND sys_pauto_config.id_page = sys_pauto_page_name.id_page"
								." AND sys_pauto_page_name.id_page = edw_comment.id_elem"
								." ORDER BY edw_comment.oid DESC";
					
					$result = pg_query($this->database_connection, $query);
					
					if(pg_num_rows($result) > 0){
						$row = pg_fetch_array($result, 0);
						$this->commentaire_dashboard = $row[1];
					}
				}

				$this->word = new export_word($this->nom_rapport);
				$this->word->set_format_page($orientation);
				
				$this->word->set_font_header('Arial',11,'#000066');
				//$this->word->create_header($pdf_image_logo, $pdf_image_logo_astellia, $titre_word);
				$this->word->create_header(REP_PHYSIQUE_NIVEAU_0."images/bandeau/logo_operateur.jpg", REP_PHYSIQUE_NIVEAU_0."images/client/logo_client_pdf.png", $titre_word);
				$this->word->create_footer();
				
				// Si il y a un commentaire sur le dashboard, alors on l'affiche sur la première page.
				
				if(isset($this->commentaire_dashboard)){
					$this->word->add_comment($this->commentaire_dashboard);
				}

				$this->addContent();
				$this->generateWord();
			}

		        // retourne le nom du fichier Word.
		        function getFileName(){
		                return $this->nom_rapport;
		        }

			/*
			Redirige le navigateur vers la page d'affichage / visualisation du document Word.
			*/

			function parametre_sortie()
			{

				return $this->nom_rapport;//$this->Object_Word->nom_rapport;
			}

			function generateWord(){

				// 14/01/2008 - Modif. benoit : si '$this->save_in_file' est vrai, on n'affiche pas le fichier word mais on propose un lien pour le télécharger

				if ($this->save_in_file) {
					
					$this->word->save_file("/");
					$file_link = str_replace(REP_PHYSIQUE_NIVEAU_0, NIVEAU_0, $this->nom_rapport);

					$download_label = (strpos(__T('U_WORD_FILE_DOWNLOAD'), "Undefined") === false) ? __T('U_WORD_FILE_DOWNLOAD') : "Click here to download the Word file";

					// 15/01/2008 - Modif. benoit : remise en forme du lien de telechargement du fichier Word
				
				}
				else 
				{
					$this->word->generate_word_file();
				}
			}


			/*
			Retourne les elements à inserer dans le document Word
			*/
			function getElements(){
				global $id_user;

				// Si on fait un export Word depuis le caddy, la variable '$this->type_word' est définie
				if($this->type_word != ""){
					// On fait un export Word depuis le caddy.
					$query="select * from sys_panier_mgt where id_user='$id_user' order by oid asc";
					$result = pg_query($this->database_connection, $query);
					$result_nb = pg_num_rows($result);
					
					for ($i = 0;$i < $result_nb;$i++){
						$row = pg_fetch_array($result, $i);
						// si l'objet est de type table, on ne met pas le même contenu.
						if($row["object_type"] == "table"){
							$this->tableau_word["object_content"][$i]= $this->tableau_html[$row["object_id"]];       // $row["object_id"];
						} 
						else if($row["object_type"] == "builder_report"){
							$this->tableau_word["object_content"][$i] = urldecode($row["object_id"]);
						} 
						else if($row["object_type"] == "alarm_export"){
							$this->tableau_word["object_content"][$i] = urldecode($row["object_id"]);
						} 
						else {
							$this->tableau_word["object_content"][$i]=$row["object_id"];
							$this->tableau_word["last_comment"][$i]=$row["last_comment"];
						}
						$this->tableau_word["object_type"][$i] = 	$row["object_type"];
						$this->tableau_word["object_titre"][$i] = 	$row["object_title"];
					}
				} 
				else 
				{
					$query="select * from sys_pdf_mgt where id_user='$id_user' and object_type='$this->object_type' order by oid asc";
					$result = pg_query($this->database_connection, $query);
					$result_nb = pg_num_rows($result);
					for ($i = 0;$i < $result_nb;$i++){
						$row = pg_fetch_array($result, $i);
						$this->id_graph = $row["object_graph_number"];
						$this->tableau_word["object_content"][$i]	= $row["object_content"];
						$this->tableau_word["object_type"][$i]		= $row["object_type"];
						$this->tableau_word["object_titre"][$i]		= $row["object_titre"];
						$this->tableau_word["object_id"][$i]			= $row["object_graph_number"];
						$this->tableau_word["last_comment"][$i]		= $row["last_comment"];
					}
				}
			}

			/*
				Affiche le dernier commentaire de l'élément.
			*/
			function displayLastComment($last_comment){
				// Si les commentaires sont activés.
				if($_SESSION["activated_faiture"]["comment"]){
					if($last_comment != "No comment..." && str_replace(" ","",$last_comment) != ""){				
						$this->word->set_font_comment('Helvetica',10,'#868686', '#C8C8C8', 'italic');
						$last_comment = strlen($last_comment) > 1000 ? substr($last_comment,0,1000)."..." : $last_comment;
						return $last_comment;
					}
				}
				return '';
			}


			/*
			Ajoute du contenu au fichier Word courant : un graph, un tableau html ou un objet builder report.
			*/
			function addContent(){
				for ($i=0;$i<count($this->tableau_word["object_content"]);$i++){
					switch($this->tableau_word["object_type"][$i]){
						case "graph" :
							$this->word->add_img($this->export_path.$this->tableau_word["object_content"][$i], $this->tableau_word["object_titre"][$i], $this->displayLastComment($this->tableau_word["last_comment"][$i]));
						break;
						case "gis_raster" :
							$this->word->add_img($this->export_path.$this->tableau_word["object_content"][$i], $this->tableau_word["object_titre"][$i], $this->displayLastComment($this->tableau_word["last_comment"][$i]));
						break;
						
						case "graph_zoomplus" :
							$this->word->add_img($this->export_path.$this->tableau_word["object_content"][$i], $this->tableau_word["object_titre"][$i], $this->displayLastComment($this->tableau_word["last_comment"][$i]));
						break;
						
						case "graph_dashboard_solo" :
							$this->word->add_img($this->export_path.$this->tableau_word["object_content"][$i], $this->tableau_word["object_titre"][$i], $this->displayLastComment($this->tableau_word["last_comment"][$i]));
						break;
						
						case "table" :
							/*$this->saut_de_page();
							$this->display_titre($this->tableau_word["object_titre"][$i]);
							$this->add_html_table($this->tableau_word["object_content"][$i]);
							$this->Object_Word->ln(10);
							$this->ylong=$this->ylong+5;*/
						break;
						
						// Image d'un graph construit dans le query builder.
						
						case "builder_report" :
							/*$this->saut_de_page();
							$this->display_titre($this->tableau_word["object_titre"][$i]);
							$this->add_html_table($this->tableau_word["object_content"][$i]);
							$this->Object_Word->ln(10);
							$this->ylong=$this->ylong+5;*/
					}
				}
			}

		        /*
		                Parse le tableau html avant l'affichage dans le fichier Word
		        */
		        function  add_html_table($table){
		                $str="";
		   
		                $str_len=strlen($table);
		                $delete=false;
		                for ($i=0;$i<$str_len;$i++){
		                        $char= substr($table,$i,1);
		                        if ($char=="<"){$delete=true;}
		                        if ($char==">"){$delete=false;}
		                        if ($delete==false){$str.=$char;}
		                }
		                $str=str_replace("<br>","",$str);
		                $str=str_replace("\n","",$str);
		                $str=str_replace(">>>>>","",$str);
		                $str=str_replace(">>>>","/",$str);
		                $str=str_replace(">>>","",$str);
		                $str=str_replace(">>","@",$str);
		                $str=str_replace(">","/",$str);

		                $str_array_line=explode('/',$str);

		                for ($k=0;$k<count($str_array_line);$k++){
		                        $this->str_array[$k]=explode('@',$str_array_line[$k]);
		                }
		                $this->add_word_table();
		        }


		        /*
		                Ajoute un tableau html
		        */
		        function add_word_table(){
		                $this->Object_Word->SetX(5);

		                //calcul de la largeur max de chaque colonne.
		                $scale=2.4;
		                $this->word_data_array[0][0]="";

		                for ($i=0;$i<count($this->str_array);$i++){
		                        for ($j=0;$j<count($this->str_array[$i]);$j++){
		                                if ($i==0){
		                                        $this->word_data_array[$i][$j+1]=$this->str_array[$i][$j];
		                                } else {
		                                        $this->word_data_array[$i][$j]=$this->str_array[$i][$j];
		                                }
		                        }
		                }

		                for ($i=0;$i<count($this->word_data_array);$i++){
		                        for ($j=0;$j<count($this->word_data_array[$i]);$j++){
		                                $larg_cell_max[$j]=0;
		                        }
		                }

		                for ($i=0;$i<count($this->word_data_array);$i++){
		                        for ($j=0;$j<count($this->word_data_array[$i]);$j++){
		                                $larg_cell[$j]=strlen($this->word_data_array[$i][$j]);
		                                if ($larg_cell[$j]>$larg_cell_max[$j]){$larg_cell_max[$j]=$larg_cell[$j];}
		                        }
		                }

		                //prepare les bloc
		                $larg=ceil($larg_cell_max[0]*$scale);
		                $larg_init=ceil($larg_cell_max[0]*$scale);
		                $bloc=0;
		                $nb=0;

		                for ($k=1;$k<count($larg_cell);$k++){
		                        $larg=$larg+(ceil($larg_cell_max[$k]*$scale));
		                        $nb=$nb+1;
		                        $nb_par_bloc[$bloc]=$nb;

		                        if ($larg>175){
		                                $bloc=$bloc+1;
		                                $larg=ceil($larg_cell_max[0]*$scale);
		                                $nb=0;
		                        }
		                }

		                $q=0;

		                for ($k=0;$k<=$bloc;$k++){
		                        $depart=1;
		                        for ($q=0;$q<$k;$q++){
		                                $depart=$depart+$nb_par_bloc[$q];
		                        }
		                        $fin=$depart+$nb_par_bloc[$k];

		                        //pour chaque bloc j'affiche la premeire colonne
		                        if ($fin>$depart){
		                                for ($i=0;$i<count($this->word_data_array);$i++){
		                                        $this->Object_Word->SetX(10);
		                                        if ($i==0){
		                                                $this->Object_Word->SetTextColor(0,0,0); //couleur noire
		                                                $this->Object_Word->SetfillColor(255, 255,255); //couleur bleu foncé
		                                                $this->Object_Word->SetFont('Arial', '', 10);
		                                                $largeur_cell=ceil($larg_cell_max[0]*$scale);
		                                                $this->Object_Word->Cell($largeur_cell,5,$this->word_data_array[$i][0],0, 0, 'C', 1);
		                                        } else {
		                                                $this->Object_Word->SetTextColor(255,255,255); //couleur noire
		                                                $this->Object_Word->SetfillColor(155, 155, 155); //couleur bleu foncé
		                                                $this->Object_Word->SetFont('Arial', '', 10);

		                                                $largeur_cell=ceil($larg_cell_max[0]*$scale);
		                                                $this->Object_Word->Cell($largeur_cell,5,$this->word_data_array[$i][0], $cell_border, 0, 'C', 1);
		                                        }

		                                        for ($j=$depart;$j<$fin;$j++){
		                                                if ($i==0 and $j==0){$cell_border=0;}else{$cell_border=1;}
		                                                if (($i==0 and $j>0)){
		                                                        $this->Object_Word->SetTextColor(255,255,255); //couleur noire
		                                                        $this->Object_Word->SetfillColor(155, 155, 155); //couleur bleu foncé
		                                                        $this->Object_Word->SetFont('Arial', '', 10);
		                                                } else {
		                                                        $this->Object_Word->SetTextColor(0,0,0); //couleur noire
		                                                        $this->Object_Word->SetfillColor(255, 255,255); //couleur bleu foncé
		                                                        $this->Object_Word->SetFont('Arial', '', 10);
		                                                }

		                                                $largeur_cell=ceil($larg_cell_max[$j]*$scale);
		                                                $this->Object_Word->Cell($largeur_cell,5,$this->word_data_array[$i][$j], $cell_border, 0, 'C', 1);
		                                        }
		                                        $this->Object_Word->ln(5);
		                                        $this->ylong=$this->ylong+5;
		                                }
		                           $this->Object_Word->ln(5);
		                           $this->ylong=$this->ylong+5;
		                        }
		                }
		        }
		}
		
		$word = new Object_Word($_GET['type_word'], $_GET['object_type'], $page_dest, $_GET['save_in_file']);
		// str_replace(REP_PHYSIQUE_NIVEAU_0, NIVEAU_0, $word->getFileName())
		echo str_replace(REP_PHYSIQUE_NIVEAU_0, NIVEAU_0, $word->parametre_sortie().".doc");

	break;
}



?>