<?php
/*
	03/08/2009 GHX
		- Correction du BZ10799 
			-> R��criture compl�te du fichier
        03/09/2010 MPR - BZ 17685 : Changement du curseur hand par pointer
 *      15/02/2011 NSE DE Query Builder :
 *          - limitation du nombre de r�sultats affich�s (1000)
 *          - pas de discr�tisation de la l�gende
 *          - l�gende affich�e si moins de 70 r�sultats affich�s
*/
?>
<?
/*
*	@cb41000@
*
*	Composant de base version cb_4.1.0.00
* 
*	- 25/11/2008 - SLC - gestion multi-produit
* 
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
<?php
	/*
		- maj 09 05 2006 christophe : on vat chercher le nom de la query pour le mettre comme nom du graph dans le caddy (ajout ligne 59)
		- maj 19 05 2006 christophe : ajout du titre de la query si elle a �t� enregistr�e.
		- maj 04 10 2006 christophe : caddy devient' cart'.
		- maj  02/12/2009 - MPR : Correction du bug 6068 - Ajout de transparence � 50% afin de pouvoir visualiser tous les raw/kpi � afficher 
	*/
// 15/02/2011 NSE DE Query Builder : augmentation de la limite
set_time_limit(600);

session_start();
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";
$transparence_color=true;

include_once(MOD_CHARTFROMXML . "class/graph.php");
include_once(MOD_CHARTFROMXML . "class/SimpleXMLElement_Extended.php");
include_once(MOD_CHARTFROMXML . "class/chartFromXML.php");

// gestion multi-produit - 20/11/2008 - SLC
include_once('connect_to_product_database.php');

if ($debug) {
	echo "<table>";
	foreach ($_POST as $k => $v) {
		echo "<tr><td>$k</td><td>$v</td></tr>";
	}
	echo "</table>";
}

// *********************************************  COLLECTE DES DONNEES DE PARAMETRE DU GRAPHE  ***********************
// *******************************************************************************************************************
// collecte des donn�es du formulaire de choix des param�tres du graphe
$save_and_display = $_POST["info_save"] ; //information qui permet de savoir si on a cliqu� sur "display" ou "Save and Display"

// valeur num�rique pour l'abscisse qui permet de d�terminer la don�es en abscisse.
// il se peut que l'utilisateur ne choisisse aucun abscisse dans ce cas la valeur est vide
$builder_report_abscisse = $_POST["abscisse"];
if ($builder_report_abscisse == "")
	$builder_report_abscisse = 10000; //valeur bidon - Important car si on garde "", un teste avec 0 donne une �galit� alors que ce n'est pas le cas


// collecte les param�tres du graphe
unset($array_data_displayed);
for ($i = 0;$i < $nombre_donnees_graphe;$i++) {
	if ($_POST["graphe_type$i"] != 'no') { // si c'est no cela signifie qu'on doit pas affciher la donn�e
		$array_data_displayed[$i]=$i; //stocke la liste des donn�es � affciher.
		$builder_report_graph_data_type[$i] = $_POST["graphe_type$i"];
		$builder_report_graph_data_color[$i] = $_POST["color_data$i"];
		$builder_report_graph_data_position[$i] = $_POST["position$i"];
		$builder_report_graph_legend[$i] = $_POST["entete$i"];		    
	}
}


// teste si les donn�es doivent �tre sauvegard�e
if ($save_and_display == 'save') {
	$query = "DELETE FROM forum_data_queries where id_query='$id_query'"; //plut�t que de mettre � jour, on efface les donn�e - c'est dans ce cas, plus simple
	$db_prod->execute($query);
	for ($i = 0;$i < $nombre_donnees_graphe;$i++) {
		if ($builder_report_abscisse == $i) 	$valeur_abscisse = "yes";
		else							$valeur_abscisse = "no";
		$data_type	= $builder_report_graph_data_type[$i];
		$data_color	= $builder_report_graph_data_color[$i];
		$data_position	= $builder_report_graph_data_position[$i];
		$query = " --- insert les donn�es � sauvegarder dans forum_data_queries
			INSERT into forum_data_queries
			(id_query, data_name, data_abscisse, data_display_type, data_color, data_position)
			VALUES
			('$id_query','$tableau_entete[$i]','$valeur_abscisse','$data_type','$data_color','$data_position')	";
		$db_prod->execute($query);
	} 
} 

// On r�cup�re le nom de la query courrante.
if (isset($_POST["id_query"])) {
	$id_query = $_POST["id_query"];
	$date_nom_query = date(" d-m-Y h:i:s");
	if (trim($id_query) != "") {
		$query_nom = " SELECT texte FROM report_builder_save WHERE id_query='$id_query' ";
		$row = $db_prod->getrow($query_nom);
		$query_name = $row["texte"].$date_nom_query;
		$nom_query = " - ".$row["texte"];
	} else {
		$query_name = "No name specified ".$date_nom_query;
		$nom_query = "";
	}
}

?>
<html>
<head>
<title>Graph Result</title>
<script src="<?=NIVEAU_0?>js/prototype/prototype.js"></script>
<script src="<?=NIVEAU_0?>js/fonction_ontop.js"></script>
<script src="<?=NIVEAU_0?>js/caddy_management.js"></script>
<script src="<?=NIVEAU_0?>js/fenetres_volantes.js"></script>
<link rel="stylesheet" type="text/css" media="all" href="<?=NIVEAU_0?>css/global_interface.css" />
</head>
<body>
	<table width="100%" border="0" height="100%" cellspacing="0" cellpadding="0">
		<tr>
			<td align="center" valign="center">
			<?php
				if ($display == 1 and count($array_data_displayed)>0) { // valeur pass�e dans l'URL d'affichage du graphe
					// *********************************************  GENERATION DU GRAPHE  **********************************************
					// *******************************************************************************************************************

					/*
						Cr�ation d'un fichier XML contenant les donn�es affich�s sur le graphe
						pour pouvoir faire l'export Excel depuis un caddy
					*/
					$dom = new DOMDocument();
					$dom->formatOutput = true;
					$dom_chart = $dom->createElement('chart');
					$dom->appendChild($dom_chart);
					
					
					/*
						1 : Propri�t�s du graphe dans le ficher XML
					*/
					$dom_pptes = $dom->createElement('properties');
						// > title
					$dom_tabtitle = $dom->createElement('tabtitle');
					$dom_tabtitl_text = $dom->createElement('text', 'Graph Result'.$nom_query);
					$dom_tabtitle->appendChild($dom_tabtitl_text);
					$dom_pptes->appendChild($dom_tabtitle);
						// > Dimension  du graphe
					$dom_pptes->appendChild($dom->createElement('width', 880));
					$dom_pptes->appendChild($dom->createElement('height', 380));
						// > Position de la l�gende
					$dom_pptes->appendChild($dom->createElement('legend_position', 'top'));
						// Nom de l'ordonn�e de gauche
					$dom_pptes->appendChild($dom->createElement('left_axis_label', ''));
						// Nom de l'ordonn�e de droite
					$dom_pptes->appendChild($dom->createElement('right_axis_label', ''));
						// Type de graphe
					$dom_pptes->appendChild($dom->createElement('type', 'graph'));
						// Type slace
					$dom_pptes_scale = $dom->createElement('scale', 'textlin');
					$dom_pptes_scale->setAttribute("autoY", 1);
					$dom_pptes_scale->setAttribute("autoY2", 1);
					$dom_pptes->appendChild($dom_pptes_scale);
						// > Graphe name
					$dom_graph_name = $dom->createElement('graph_name', $query_name.' (from builder report)');
					$dom_pptes->appendChild($dom_graph_name);
					
					$dom_chart->appendChild($dom_pptes);
					
					/*
						2 : L'abscisse du fichier XML
					*/
					$dom_xaxis = $dom->createElement('xaxis_labels');
					if ($abscisse == "")
					{
						$builder_report_abscisse = 0;
					}
                                        // 15/02/2011 NSE DE Query Builder : la l�gende n'est affich�e que s'il y a moins de 70 r�sultats affich�s
                                        // 13/04/2011 BBX
                                        // Affichage d'un message lorsque la lagende n'est pas affich�e
                                        // BZ 21810
                                        $bottomMessage = '';
                                        if(count($tableau_data_export_excel[0][$builder_report_abscisse])<70)
                                        {
                                            foreach ( $tableau_data_export_excel[0][$builder_report_abscisse] as $value )
                                            {
                                                    $dom_xaxis->appendChild($dom->createElement('label', $value));
                                            }
                                        }
                                        else
                                        {
                                            foreach ( $tableau_data_export_excel[0][$builder_report_abscisse] as $value )
                                            {
                                                    $dom_xaxis->appendChild($dom->createElement('label', ''));
                                            }
                                            $bottomMessage = '<div class="infoBox">'.__T('U_QUERY_BUILDER_GRAPH_NO_LEGEND').'</div>';
                                        }
					$dom_chart->appendChild($dom_xaxis);
					
					/*
						3 : Les donn�es du fichiers XML
					*/
					$dom_datas = $dom->createElement('datas');
					// Boucle sur les donn�es affich�es
					foreach ($array_data_displayed as $key=>$data_displayed)
					{
						$dom_datas_data = $dom->createElement('data');
						$dom_datas_data->setAttribute("label", $builder_report_graph_legend[$key]);
						$dom_datas_data->setAttribute("line_design", 'none');
						$dom_datas_data->setAttribute("yaxis", $builder_report_graph_data_position[$key]);
						
						switch ( $builder_report_graph_data_type[$key] )
						{
							case 'line': 
								$dom_datas_data->setAttribute("fill_color", $builder_report_graph_data_color[$key].'@1');
								$dom_datas_data->setAttribute("stroke_color", $builder_report_graph_data_color[$key]);
								$dom_datas_data->setAttribute("type", 'line');
								break;
							case 'bar':
								// maj  02/12/2009 - MPR : Correction du bug 6068 - Ajout de transparence � 50% afin de pouvoir visualiser tous les raw/kpi � afficher 
								$dom_datas_data->setAttribute("fill_color", $builder_report_graph_data_color[$key].'@0.5');
								$dom_datas_data->setAttribute("stroke_color", $builder_report_graph_data_color[$key]);
								$dom_datas_data->setAttribute("type", 'bar');
								break;
							case 'cumulated':
								// maj  02/12/2009 - MPR : Correction du bug 6068 - Ajout de transparence � 50% afin de pouvoir visualiser tous les raw/kpi � afficher 
								$dom_datas_data->setAttribute("fill_color", $builder_report_graph_data_color[$key].'@0.5');
								$dom_datas_data->setAttribute("stroke_color", $builder_report_graph_data_color[$key]);
								$dom_datas_data->setAttribute("type", 'cumulatedbar');
								break;
						}
						
						// Boucles sur les valeurs de la donn�e � afficher
                                                // 15/02/2011 NSE DE Query Builder : on limite le nombre de r�sultats affich�s
                                                $cptvalue=0;
						foreach ( $tableau_data_export_excel[0][$key] as $value )
						{
                                                    // on n'affiche que 1000 valeurs
							if($cptvalue>get_sys_global_parameters('query_builder_nb_result_limit',1000))
                                                            break;
							$dom_datas_data->appendChild($dom->createElement('value', $value));
                                                        $cptvalue++;
						}
						$dom_datas->appendChild($dom_datas_data);
					}
					$dom_chart->appendChild($dom_datas);
					
					$nom = uniqid('query_builder',true);
					
					$chart_url = REP_PHYSIQUE_NIVEAU_0.'png_file/'.$nom.'.xml';
					
					$dom->save($chart_url);
					
					/*
						4 :  Construction du graphe
					*/
					// On cr�e l'objet en chargeant le fichier de donn�es XML
					$my_gtm = new chartFromXML($chart_url);
                                        // 15/02/2011 NSE DE Query Builder : pas de discr�tisation, on veut afficher tous les �l�ments de l'axe X
                                        // initialisation � 1 de l'interval pour tous les afficher
                                        $my_gtm->setAbscisseInterval(1);
					// Modification des urls afin de stocker l'ensemble des fichiers (xml + png) dans le dossier "png_file" de l'application
					$my_gtm->setBaseUrl(NIVEAU_0.'/png_file/');
					$my_gtm->setBaseDir(REP_PHYSIQUE_NIVEAU_0.'png_file/');
					$my_gtm->setHTMLURL(NIVEAU_0);
					
					// on charge les valeurs par d�faut (depuis un autre fichier XML)
					$my_gtm->loadDefaultXML(MOD_CHARTFROMXML . "class/chart_default.xml");
					echo $my_gtm->getHTML($nom);

                                        // 13/04/2011 BBX
                                        // Affichage d'un message lorsque la lagende n'est pas affich�e
                                        // BZ 21810
                                        echo $bottomMessage;
				} else {
					?>
					<font class="texteGrisBold">You must select at least 1 data to be displayed</font>
					<?
				}
				?>
			</td>
		</tr>
		<tr>
			<td align="center">
			<?
				if (isset($nom)) {
					global $path_skin,$id_user;
                                        // 03/09/2010 MPR - BZ 17685 : Changement du curseur hand par pointer
					?>
					<img src="<?=NIVEAU_0?>images/icones/caddy_icone_gf.gif" align="middle" border="0"  onMouseOver="popalt('Add to the  Cart');style.cursor='pointer';" onMouseOut="kill()" onClick="caddy_update('<?=NIVEAU_0?>','<?=$id_user?>','Builder report','graph','<?=$query_name?> (from builder report)','<?=$nom?>.png','Builder report graph','')">
				<?
				}
				?>
			</td>
		</tr>
	</table>
</body>
</html>
