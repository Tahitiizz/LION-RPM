<?
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
*

	- maj 10/08/2007, benoit : lors de la definition de l'url, modification du masque de recherche de la             fonction 'str_replace()' en changeant la valeur "defaut" de "selecteur_scenario" par "normal" (le scenario     "defaut" n'existe plus)

	- maj 31/07/2007, christophe : modification de l'appel pour la navigation dans le graphe (suppression du         onclick).

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
*	@cb20100_iu2030@
*
*	24/10/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.1.00
*
*	Parser version iu_2.0.3.0
*/
?>
<?
/*
*	@cb2000b_iu2000b@
*
*	20/07/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.0.0
*
*	Parser version iu_2.0.0.0
*/
?>
<?

	$transparence_color=true;
	include_once($repertoire_physique_niveau0 . "graphe/jpgraph.php");
	include_once($repertoire_physique_niveau0 . "graphe/jpgraph_line.php");
	include_once($repertoire_physique_niveau0 . "graphe/jpgraph_scatter.php");
	include_once($repertoire_physique_niveau0 . "graphe/jpgraph_bar.php");
	include_once($repertoire_physique_niveau0 . "graphe/jpgraph_canvas.php");
	include_once($repertoire_physique_niveau0 . "graphe/jpgraph_pie.php");
	include_once($repertoire_physique_niveau0 . "graphe/jpgraph_pie3d.php");
	include_once($repertoire_physique_niveau0 . "graphe/jpgraph_error.php");

	/*
		Permet d'afficher le graph dans alarm history.
		- maj 26 09 2006 christophe : correction du libellé d'un titre.
		- maj 04 10 2006 xavier : correction du libellé d'un autre titre. :p
		- maj 05  10 2006 christophe :
			> modification de la taille de l'image du Pie (certains éléments était rognés)
			> modification de l'alignement de du label de l'axe x.
		- maj 17 11 2006 christophe : j'ai mis la transparence des couleurs du graph à 0 afin que les couleurs
		correspondent à celles qui sont affichées dans le tableau des résultats et dur le camembert.
	*/
	/**
	*
	*	@param array	$nb_alarm_result : un tableau qui contient les valeurs par date, pour chaque seuil	$nb_alarm_result[seuil][date] = valeur
	*	@param array	$nb_alarm_result_total : tableau qui contient les nombres de resultats par seuil	$nb_alarm_result[seuil] = nombre
	*	@param string	$ta : time aggregation	$ta = 'day'
	*	@param int	$period : semble contenir la période (probablement le nb de valeur dans chaque seuil : sizeof($nb_alarm_result[seuil])	(comprendre ce que signifie -1 comme valeur passée)
	*	@param int	$ta_value : semble contenir la ta value (ex: 20080611). Ne semble aucunement utilisé dans la classe (en dehors de $this->ta_value = $ta_value).
	*/
	class alarmGraphHistory
	{

		function  alarmGraphHistory($nb_alarm_result,$nb_alarm_result_total,$ta,$period,$ta_value)
		{
			// Propriétés.
			$this->nb_alarm_result = $nb_alarm_result;
			$this->nb_alarm_result_total = $nb_alarm_result_total;
			$this->ta = $ta;
			$this->ta_value = $ta_value;
			$this->period = $period + 1;

			$this->transparence = "";		// Transparence des couleurs.
			$this->rand = rand();			// Utilisé pour avoir des noms uniques pour les images.

			// On récupère la liste des couleurs pour chaque seuil.
			$this->getColors();

			$this->url_navigation = $_SESSION["url_alarme_courante"];

			if($this->ok_to_display()){
				$this->getGraph();	// création de l'image du graph.
				$this->getPie();	// création de l'image du camembert.
			} else {
				$this->getDefault("No result.");
			}
			$this->displayElements();	// affichage des éléments.
		}


		/*
			Initialisation des couleurs de chaque seuil.
		*/
		function getColors(){
			$this->alarmColor['critical'] = get_sys_global_parameters("alarm_critical_color");
			$this->alarmColor['major'] = 	get_sys_global_parameters("alarm_major_color");
			$this->alarmColor['minor'] = 	get_sys_global_parameters("alarm_minor_color");
		}

		/*
			Vérifie si il y a des résultats.
			Si oui retourne true, false sinon.
			(si toutes les valeurs sont égales à 0, c'est qu'il n'y a pas de résultats)
		*/
		function ok_to_display() {
			if (is_array($this->nb_alarm_result))
				foreach ($this->nb_alarm_result as $seuil=>$liste_resultats)
					foreach ($liste_resultats as $date=>$valeur)
						if ($valeur > 0)
							return true;
			return false;
		}

		/*
			Affiche une image avec un message.
		*/
		function getDefault($message) {
			global $repertoire_physique_niveau0, $niveau0;

			$g = new CanvasGraph(900,100,'auto');
			$g->SetMargin(5,11,6,11);
			//$g->InitFrame();	// bordures noires.
			$t = new Text($message,450,40);
			$t->SetFont(FF_FONT2,FS_BOLD,40);
			$t->Align('center','top');
			$t->ParagraphAlign('center');
			$t->Stroke($g->img);

			$nom_image = "audit_report_histo_alarm_no_result_$this->rand.png";
			$image_name_stock = $repertoire_physique_niveau0 . "png_file/$nom_image";
			$image_name = 		$niveau0 . "png_file/$nom_image";

			$g->Stroke($image_name_stock);

			$this->graph_image = "<img  src=\"$image_name\"  border=0>";
			$this->graph_title = "ALARM HISTORY OVERVIEW";
		}

		/*
			Construit l'image du graph.
		*/
		function getGraph(){
			global $repertoire_physique_niveau0, $niveau0;

			//Création du graph.
			$graph = new Graph(600,300,"auto");
			$graph->SetScale("textlin");

			$graph->img->SetMargin(40,80,30,90);

			// Création des barres.
			$i=0;
			foreach ($this->nb_alarm_result as $seuil=>$liste_resultats)
			{
				unset($array_date);
				unset($array_result);
				unset($data_extra);
				unset($target);
				unset($link);

				$j = 0;
				foreach ($liste_resultats as $date=>$valeur)
				{
					$date_label =	 	getTaValueToDisplay($this->ta, $date); // cf edw_function.php pour cette fonction.
					$array_date[] = 	$date_label;
					$array_result[] = 	$valeur;
					$params = "&period=".$this->period."&date=".$date."&ta_level=".$this->ta;
					// 31/07/2007 christophe : modification de l'appel pour la navigation dans le graphe (suppression du onclick).
					$link = " window.location='".$this->url_navigation .$params."' ";
					$data_extra[$j][0] = $link;
					$data_extra[$j][1] = ucfirst($seuil) . " on ".$date_label;
					$data_extra[$j][2] = $valeur." results.";
					$target[$j] = "fff";

					$j++;
				}

				$bplot[$i] = new BarPlot($array_result);
				$bplot[$i]->SetFillColor($this->alarmColor[$seuil].$this->transparence);
				$bplot[$i]->SetLegend(ucfirst($seuil));
				$bplot[$i]->SetCSIMTargets($target,$data_extra);
				$i++;
			}

			$graph->SetScale("textlin");
			$graph->img->SetAntiAliasing();
			$graph->yscale->SetAutoMin(0);
	
			$graph->SetMarginColor("#ffffff");
			$graph->SetColor("#ffffff");
			$graph->SetFrame(false);
			$graph->SetBackgroundGradient('blue','red',GRAD_HOR,BGRAD_PLOT);

			// Création du groupe de barres.
			$gbplot = new AccBarPlot($bplot);
			$gbplot->SetWidth(0.5);
			$graph->Add($gbplot);

			// Configuration du tyle d'affichage du graph.
			$graph->xaxis->title->Set(ucfirst($this->ta));
			$graph->xaxis->SetTitlemargin(28);

			// Si la période est >= à 30, on affiche un label sur 2 pour plus de lisibilité.
			if($this->period >= 30)
				$graph->xaxis->SetTextLabelInterval(2);

			$graph->title->SetFont(FF_FONT1,FS_BOLD);
			$graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);

			$graph->legend->SetFillColor('#fafafa@0.5');
			$graph->legend->SetLayout(LEGEND_VER);
			$graph->legend->Pos(0.01,0.5,"right","center");
			$graph->legend->Setshadow(false);

			/*
			$graph->tabtitle->Set("Distribution by date");
			$graph->tabtitle->SetCorner(2); // MODIF DELTA
			$graph->tabtitle->SetTabAlign('left');
			$graph->tabtitle->SetColor('black','whitesmoke','snow3'); // MODIF DELTA
			$graph->tabtitle->SetFont(FF_VERDANA,FS_NORMAL,8);
			// */

			$graph->ygrid->SetFill(true,'#EFEFEF@0.7','#FFFFFF@0.9');
			$graph->xgrid->Show();
			$graph->xgrid->SetColor('gray');
			$graph->xaxis->SetTickLabels($array_date);

			$graph->xaxis->SetFont(FF_ARIAL,FS_NORMAL,7);
			$graph->xaxis->SetLabelAngle(60);

			$nom_image = "audit_report_histo_alarm_$this->rand.png";
			$nom_image_sans_extension = "audit_report_histo_alarm_$this->rand";
			$image_name_stock = $repertoire_physique_niveau0 . "png_file/$nom_image";
			$image_name = 		$niveau0 . "png_file/$nom_image";

			$graph->Stroke($image_name_stock);

			$htmlcode_map = $graph->GetHTMLImageMap($nom_image_sans_extension);

			// On récupère les balises map nécessaire à la navigation.
			echo ($htmlcode_map);

			$this->graph_image = "<img  src=\"$image_name\" ismap usemap=\"#$nom_image_sans_extension\" border=0>";
			$this->graph_title = "ALARM HISTORY OVERVIEW";
		}

		/*
			Construit l'image du camembert.
		*/
		function getPie(){
			global $repertoire_physique_niveau0, $niveau0;

			$j = 0;
			foreach($this->nb_alarm_result_total as $seuil=>$nb)
			{
				$data[] = $nb;
				$this->array_color_pie[] = $this->alarmColor[$seuil];
				$j++;
			}
			$graphpie = new PieGraph(320,300,"auto");
			$graphpie->SetShadow();



			$graphpie->title->Set("Distribution over the \n whole period");
			$graphpie->title->SetFont(FF_FONT1,FS_BOLD);
			$graphpie->SetMarginColor("#ffffff");
			$graphpie->SetColor("#ffffff");
			$graphpie->SetFrame(false);
			$graphpie->img->SetAntiAliasing();
			$graphpie->img->SetMargin(20,20,20,20);
			$graphpie->SetBackgroundGradient('blue','red',GRAD_HOR,BGRAD_PLOT);

			$p1 = new PiePlot3D($data);
			//$p1->ExplodeSlice(false);
			$p1->SetCenter(0.5);
			$p1->SetSliceColors($this->array_color_pie);

			$p1->SetAngle(40);
			$p1->SetSize(0.4);
			//$p1->SetAntiAlias();

			$nom_image = "audit_report_histo_alarm_pie_$this->rand.png";
			$image_name_stock_pie = $repertoire_physique_niveau0 . "png_file/$nom_image";
			$image_name_pie = 		$niveau0 . "png_file/$nom_image";

			$graphpie->Add($p1);
			$graphpie->Stroke($image_name_stock_pie);

			$this->pie_image = "<img  src=\"$image_name_pie\" border=0>";
		}

		/*
			Affiche l'ensembre html avec les images du graph et du pie.
		*/
		function displayElements(){
			global $niveau0;
			$path_skin = $niveau0."images/icones/";
			?>
			<link rel="stylesheet" href="<?=$niveau0?>css/graph_style.css" type="text/css">
			<table border='0' align='center' cellpadding='0' cellspacing='0'>
				<!-- Affichage du titre du graph et des icones associées. -->
				<tr>
					<td valign='bottom'>
						<table cellpadding='0' cellspacing='0' border='0'>
							<tr>
								 <!-- Affichage du titre. -->
								<td valign="top">
									<div class='titreGraph' style="padding:4px;">
										<span><img src='<?=$niveau0?>images/graph/puce_graph.gif'></span>
										<span class='texteGraphGrisBold'><?=$this->graph_title?>&nbsp;</span>
										<span class='texteGraphGris'> OVER TIME (<?=ucfirst($this->ta)?>)</span>
									</div>
								</td>
							</tr>
			            </table>
					</td>
				</tr>
			    <!-- Affichage de l'image du graph. -->
				<tr>
					<td align=center>
						<table cellpadding='4' cellspacing='0' class='fondGraph'>
							<tr>
								<td align='center' valign='middle'>
									<table cellpadding='0' cellspacing='0' class='contourImage'>
										<tr>
											<td><?=$this->graph_image?></td>
											<td><?=$this->pie_image?></td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>

		<?
		}
	}

?>
