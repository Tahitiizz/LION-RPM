<?php
/**
 * 
 *  CB 5.2
 * 
 * 23/02/2012 NSE DE Astellia Portal Lot2
 */
?><?
/*
*       @cb4100@
*
*       04/11/2008 - Copyright Astellia
*
*       Composant de base version cb_4.1.0.0
*
*	Fichier qui génère les deux graphes présentant le nombre de pages visitées des 30 derniers jours et des 6 dernieres mois
*	On affiche également dans un tableau le nombre de connexions par utilisateurs du jour
*
*	maj 03/11/2008 - MPR : Fichiers nécessaires à la construction des graphes
*	maj 03/11/2008 - MPR :  Construction de la fonction generateXmlFile : On construit les graphes à partir de fichier xml généré
*
*	12/08/2009 GHX
*		- Correction du BZ 6652
*	09/11/2009 GHX
*		- Correction du BZ 12633 [REC][T&A Roaming] Erreur openoffice au login, Homepage quasi vide
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
	Object : affichage du traffic sur l'application

	2006-02-16        Stephane        Creation

	- maj 23/02/2006, christophe : modif sur la gnérations des graph, modif images + css.
	- maj 08/03/2006, christophe : on affiche l'historique sur 6 mois et non 12.
	- maj 08/11/2006, benoit : exclusion de l'utilisateur 'astellia_admin' des statistiques du nombre de pages vues   si l'utilisateur administrateur est différent de celui-ci
*/

session_start();
include_once("../../../../php/environnement_liens.php");
// include_once(REP_PHYSIQUE_NIVEAU_0 . "php/environnement_nom_tables.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "intranet_top.php");

$transparence_color=1;
	
// maj 03/11/2008 - MPR Fichiers nécessaires à la construction des graphes
define('URL_CHARTFROMXML',$self);
include_once(MOD_CHARTFROMXML . "class/graph.php");
include_once(MOD_CHARTFROMXML . "class/SimpleXMLElement_Extended.php");
include_once(MOD_CHARTFROMXML . "class/chartFromXML.php");
		
// on recupere les donnees envoyées
$day		= $_GET['day'];
$period	= $_GET['period'];

// a la demande de Cyrille, mais la je vois pas ce que ça fait -- stephane
$general_values  = array();

//maj 03/11/2008 - MPR : On construit les graphes à partir de fichier xml généré
/**
* Fonction qui génère un fichier xml nécessaire à la création du graphe
* @param string $x_data : Abscisse du graphe
* @param string  y_data : Ordonnée du graphe
* @param string $title : Titre du graphe
* @param string $legend : Légende du graphe
* @return string $file_xml : fichier xml généré
*/
function generateXmlFile($x_data,$y_data, $title, $legend)
{					
  $file = '<?xml version="1.0" encoding="UTF-8" ?><chart>';
  // Attributs du fichiers - Correspond aux paramètres du graphe
  $file.= ' 
		<properties>
			<title></title>
			<tabtitle>
				<text>'.$title.'</text>
			</tabtitle>
			<margin_top>50</margin_top>
			<margin_left>20</margin_left>
			<legend></legend>
			<margin_bottom>50</margin_bottom>
			<margin_color>#ffffff</margin_color>
			<margin_right>50</margin_right>
			<width>580</width>
			<height>200</height>
			<left_axis_label>Total</left_axis_label>
		</properties>
		';
	  // On définit l'axe des abscisses
		
  $file.= '<xaxis_labels interval="2">'.implode("",$x_data)."</xaxis_labels>";
  $file.= '<datas><data label="'.$legend.'" type="bar" stroke_color="blue" fill_color="blue@0.7" yaxis="left">'.implode("",$y_data).'</data></datas></chart>';
  
  $file_xml = REP_PHYSIQUE_NIVEAU_0."png_file/appli_stats_".uniqid("").".xml";
  
  // On supprime et recréé le fichier s'il existe
  exec("rm -f  $file_xml");
  exec("touch $file_xml");
		
  // Création du fichier xml
  $fp = @fopen ($file_xml, "r+");			
  
	// 15:59 09/11/2009 GHX
	// Correction du BZ 12633
	// Si impossible de créer le fichier XML on retourne false (ajout aussi du @ devant fopen)
	if ( $fp )
	{
		fputs($fp, $file);
		fclose($fp); 
		return $file_xml;
	}
	else
	{
		return false;
	}
} // End Function generateXmlFile()

?>
<html>
        <head>
                <title>Traffic Watch</title>
                <link rel="stylesheet" type="text/css" media="all" href="<?=NIVEAU_0?>css/global_interface.css">
                <script src="<?=NIVEAU_0?>js/fenetres_volantes.js"></script>
                <script src="<?=NIVEAU_0?>js/gestion_fenetre.js"></script>
                <script src="<?=NIVEAU_0?>js/fenetres_volantes.js"></script>
                <script src="<?=NIVEAU_0?>js/fonctions_dreamweaver.js"></script>
                <script type='text/javascript' src='<?=NIVEAU_0?>js/toggle_functions.js'></script>

<script>

function update_hour(obj, hour){
        toggle('div_clock');
        var hour_to_display = (hour < 10) ? "0"+hour: hour;
        document.getElementById(obj).value = hour_to_display+":00 ";
}

function change_clock(){
        if(document.getElementById('am').checked == true){
                document.getElementById('am_clock').style.display = '';
                document.getElementById('pm_clock').style.display = 'none';
        } else {
                document.getElementById('am_clock').style.display = 'none';
                document.getElementById('pm_clock').style.display = '';
        }
}

</script>


</head>
<body>

<table cellpadding="5" cellspacing="5" border="0" align="center">
        <!-- Image Titre -->
        <tr>
           <td align="center"><img src="<?=NIVEAU_0?>images/titres/traffic_watch.gif"/></td>
        </tr>
        <!-- Contenu -->
        <tr>
			<td>
                <table cellpadding="4" cellspacing="2" border="0" class="tabPrincipal" align="center" width="640">
                    <tr>
                        <td class="texteGrisBold" align="center">
		<?

		// on affiche le selecteur
		include('traffic_watch_selecteur.php');

		if($selecteur_general_values !== null){
            $day = $selecteur_general_values['date'];
        }
				
		if (!$day) $day = date('d-m-Y');

		// on va faire la premiere requête sur les 30 derniers jours
		$day_end = substr($day,6,4).substr($day,3,2).substr($day,0,2);

		// on remonte de 30 jours, en créant le tableau $graph_data
		$graph_data        = array();
		for ($i = 30;$i >= 0; $i--) {
		
			$index = date('d-m-y',mktime(1,0,0,substr($day_end,4,2),substr($day_end,6,2)-$i,substr($day_end,0,4)));
			$graph_data[$index] = 0;
			
		}
		$day_30 = date('Ymd',mktime(1,0,0,substr($day_end,4,2),substr($day_end,6,2)-30,substr($day_end,0,4)));

		// 08/11/2006 - Modif. benoit : on exlut astellia_admin des statistiques du nombre de pages vues si il n'est pas connecté

		// 14:20 12/08/2009 GHX
		// correction du BZ 6652
		(getClientType($_SESSION['id_user']) == 'client') ? $exclude_admin = " AND u.visible = 1 " : $exclude_admin = "";
		
                // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
		$database_connection = Database::getConnection();
		$query = "
				SELECT count(*) as nb,access_day
				FROM track_pages, users AS u
				WHERE access_day >= '$day_30'
						AND access_day <= '$day_end'
				AND track_pages.id_user = u.id_user
				$exclude_admin
				GROUP BY access_day
				ORDER BY access_day
				";
		$result                = $database_connection->getAll($query);
		$nb_result        = count($result);

		if ($nb_result) {

			foreach ($result as $row ) {
				
				$index = substr($row['access_day'],6,2).'-';
				$index.= substr($row['access_day'],4,2).'-';
				$index.= substr($row['access_day'],2,2);
				
				$graph_data[$index] = $row['nb'];
			}

			$data_x = array();
			$data_y = array();
			$i = 0;
			foreach ($graph_data as $key => $val) {

				$data_x[] = '<label ta_level="'.$key.'_2" color="#0000FF">'.$key.'</label>';

				if($val !== null and $val !== ''){
					$data_y[] = '<value data_values="'.$key.'_data_2">'.$val.'</value>';
				}else{
					$data_y[] = '<value data_values="'.$key.'_data_2">0</value>';
				}
			}
			
			// maj 03/11/2008 : MPR : Generation du fichier xml pour creer le graphe
			// 15:27 09/11/2009 GHX
			// Correction du BZ 12633
			// Modification de la fonction generateXmlFile pour quelle retourne FALSE si le fichier XML n'a pas été créé
			if ( $file_xml = generateXmlFile($data_x, $data_y, __T("A_APPLICATION_STATS_LABEL_TRAFFIC"), "Nb Pages") )
			{
				// 03/11/2008 : MPR : Création du graphe
				$myGraph = new chartFromXML($file_xml);
				
				$myGraph->loadDefaultXML(MOD_CHARTFROMXML."class/chart_default.xml");
				
				// On précise dans quel répertoire est enregistré l'image par défault png_file
				$myGraph->setBaseDir(REP_PHYSIQUE_NIVEAU_0.'/png_file/');
				
				// on définit l'url des images sauvées
				$myGraph->setBaseUrl(NIVEAU_0.'/png_file/');

				$graphe = $myGraph->getHTML();
	 
				echo $graphe."<br/><br/>";
			}
			else
			{
				// 15:27 09/11/2009 GHX
				// Correction du BZ 12633
				// Si on n'a pas pu créer le fichier XML, on affiche un message pour dire qu'il est impossible de créer le graphe
				echo "<div class='errorMsg'>".__T('A_E_UNABLE_CREATE_GRAPH')."</div>";
			}
		} else { ?>
			<p><strong>No visitor found from <?= $day_30 ?> to <?= $day_end ?>.</strong></p>
		<?
		}

		// on fait la requete sur les 6 derniers mois
		// on remonte de 6 mois, en créant le tableau $graph_data
		unset($graph_data);
		$graph_data        = array();
		for ($i = 6;$i >= 0; $i--) {
				$graph_data[date('m-Y',mktime(1,0,0,substr($day_end,4,2)-$i,substr($day_end,6,2),substr($day_end,0,4)))] = 0;
		}
		$month_6 = date('Ym',mktime(1,0,0,substr($day_end,4,2)-6,substr($day_end,6,2),substr($day_end,0,4)));

		// 08/11/2006 - Modif. benoit : on exlut astellia_admin des statistiques du nombre de pages vues si il n'est pas connecté

		$query = "
				SELECT count(*) as nb,SUBSTR(access_day,1,6) as access_month
				FROM track_pages, users AS u
				WHERE SUBSTR(access_day,1,6) >= '$month_6'
						AND SUBSTR(access_day,1,6) <= '".substr($day_end,0,6)."'
				AND track_pages.id_user = u.id_user
				$exclude_admin
				GROUP BY access_month
				ORDER BY access_month
				";
		

		$result                = $database_connection->getAll($query);
		$nb_result        = count($result);

		if ($nb_result) {

				foreach ( $result as $row ) {
						$graph_data[substr($row['access_month'],4,2).'-'.substr($row['access_month'],0,4)] = $row['nb'];
				}

				$data_x = array();
				$data_y = array();
				foreach ($graph_data as $key => $val) {
					$data_x[] = '<label ta_level="'.$key.'_3" color="#0000FF">'.$key.'</label>';
					
					if($val !== '' and $val !== null)
					{
						$data_y[] =   '<value data_values="'.$key.'_data_3">'.$val.'</value>';
					}else
					{
						$data_y[] =   '<value data_values="'.$key.'_data_3">0</value>';
					}
				}
				
				//maj 03/11/2008 - MPR : On construit les graphes à partir de fichier xml généré
				// 15:27 09/11/2009 GHX
				// Correction du BZ 12633
				// Modification de la fonction generateXmlFile pour quelle retourne FALSE si le fichier XML n'a pas été créé
				if ( $file_xml = generateXmlFile($data_x, $data_y, __T("A_APPLICATION_STATS_LABEL_TRAFFIC_MONTH"),"Nb Pages") )
					{
					// 03/11/2008 : MPR : Création du graphe
					$myGraph = new chartFromXML($file_xml);
					
					$myGraph->loadDefaultXML(MOD_CHARTFROMXML."class/chart_default.xml");
					
					// On précise dans quel répertoire est enregistré l'image par défault png_file
					$myGraph->setBaseDir(REP_PHYSIQUE_NIVEAU_0.'/png_file/');
					
					// on définit l'url des images sauvées
					$myGraph->setBaseUrl(NIVEAU_0.'/png_file/');

					$graphe = $myGraph->getHTML();
		 
					echo $graphe;
				}
				else
				{
					// 15:27 09/11/2009 GHX
					// Correction du BZ 12633
					// Si on n'a pas pu créer le fichier XML, on affiche un message pour dire qu'il est impossible de créer le graphe
					echo "<div class='errorMsg'>".__T('A_E_UNABLE_CREATE_GRAPH')."</div>";
				}
					
			} else { ?>

				<p><strong>No visitor found from <?= $month_12 ?> to <?= substr($day_end,0,6) ?>.</strong></p>

<?        }

		// 08/11/2006 - Modif. benoit : on exlut astellia_admin des statistiques du nombre de pages vues si il n'est pas connecté

		// on affiche la liste des visiteurs de la journée
                // 20/02/2012 NSE DE Astellia Portal Lot2 : suppression de user_prenom
		$query = "
				SELECT count(*) as nb,u.username
				FROM track_pages, users AS u
				WHERE track_pages.access_day = '$day_end'
				AND track_pages.id_user = u.id_user
				$exclude_admin
				GROUP BY u.username
				ORDER BY u.username
				";
		
		$result = $database_connection->getAll($query);
		$nb_result = count($result);

		if ($nb_result) {
								?>

								<fieldset>
									<legend class="texteGrisBold">
									&nbsp;
									<img src="<?=NIVEAU_0?>images/icones/small_puce_fieldset.gif" />
									&nbsp;
									Page views per user on <?= $day ?>
									&nbsp;
									</legend>
									<table cellspacing="2" cellpadding="2">
										<tr>
												<td align=center><font class=texteGrisBold>Name</font></td>
												<td align=center><font class=texteGrisBold>Page views</font></td>
										</tr>

		<?
		foreach ( $result as $row ) {
				
				?>
										<tr <?= ($i % 2 ? 'class="fondGrisClair"':'') ?> onMouseOver="javascript:this.className='fondOrange'" onMouseOut="javascript:this.className='<?= ($i % 2 ? 'fondGrisClair':'fondVide') ?>'">
												<td nowrap  align="left"  class=texteGris style="color:;"><?=$row["username"]?></td>
												<td nowrap  align="right"  class=texteGris style="color:;"><?=$row["nb"]?></td>
										</tr>
		<? } ?>
									</table>
								</fieldset>
	<? } else { ?>
								<p><strong>No visitor found from <?= $day ?>.</strong></p>
	<? } ?>
							</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>
