<?
/*
*	@cb41000@
*
*	03/11/2008 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.0
*
*	maj 03/11/2008 - MPR : On génère un fichier xml pour créer le graphe
*
*	09/11/2009 GHX
*		- Correction du BZ 12633 [REC][T&A Roaming] Erreur openoffice au login, Homepage quasi vide
*
*	18/11/2009 BBX : 
*		- On test si le fichier xml a pu se créé avant d'appeler la génération du graphe. BZ 12633
*/
?>
<?
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14

	- maj 09/08/2007, benoit : dans le lien vers 'connection_watch_index.php', remplacement du scenario du           selecteur à "defaut" par "normal"

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
*	@cb1300_iu2000b_pour_cb2000b@
*
*	19/07/2006 - Copyright Acurio
*
*	Composant de base version cb_1.3.0.0
*
*	Parser version iu_2.0.0.0
*/
?>
<?
/*
	- maj 25/04/2006, christophe : DELTA cf MODIF DELTA NOUVEAU(ajout). MODIF DELTA (mise en commentaires des
	  modifications)
      >>>> le fichier a entièrement été modifié.

	- maj 08/11/2006, benoit : exclusion de l'utilisateur 'astellia_admin' des statistiques du nombre de pages vues   si l'utilisateur administrateur est différent de celui-ci

*/
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
$db_connect = Database::getConnection();
$transparence_color=1;
	
?>

<fieldset>
	<legend class="texteGrisBold" style='font-size:10px;'>
		&nbsp;
		<img src="<?=NIVEAU_0?>images/icones/small_puce_fieldset.gif"/>
		&nbsp;Application Statistics
		&nbsp;
	</legend>

	<div align="left">
		<?
			$user_statistics = get_sys_global_parameters("user_statistics");
			if (!$user_statistics) {
				$homepage_display=true;
				include(REP_PHYSIQUE_NIVEAU_0."myadmin_user/intranet/php/affichage/user_activity_result.php");
				
				echo "$graphe";
				
			}else{
				$day = date('d-m-Y');

				// on va faire la premiere requête sur les 30 derniers jours
				$day_end        = substr($day,6,4).substr($day,3,2).substr($day,0,2);

				// on remonte de 30 jours, en créant le tableau $graph_data
				$graph_data        = array();
				for ($i = 30;$i >= 0; $i--) {
						$graph_data[date('d-m-y',mktime(1,0,0,substr($day_end,4,2),substr($day_end,6,2)-$i,substr($day_end,0,4)))] = 0;
				}
				$day_30        = date('Ymd',mktime(1,0,0,substr($day_end,4,2),substr($day_end,6,2)-30,substr($day_end,0,4)));

				// 08/11/2006 - Modif. benoit : on exlut astellia_admin des statistiques du nombre de pages vues si il n'est pas connecté

                                // 07/06/2011 BBX -PARTITIONING-
                                // Correction des casts
				($_SESSION['id_user'] != 1) ? $exclude_admin = " AND id_user != '1' " : $exclude_admin = "";

				$query = "
						SELECT count(*) as nb,access_day
						FROM track_pages
						WHERE access_day >= '$day_30'
						AND access_day <= '$day_end'
						$exclude_admin
						GROUP BY access_day
						ORDER BY access_day
						";
				$result   = $db_connect->getAll($query);
				$nb_result  = count ($result);

				if ($nb_result) {

					foreach ($result as $row )
						$graph_data[substr($row['access_day'],6,2).'-'.substr($row['access_day'],4,2).'-'.substr($row['access_day'],2,2)] = $row['nb'];

					$data_x = array();
					$data_y = array();
					$i = 0;
					foreach ($graph_data as $key => $val) {
						$i++;
						
						// maj 03/11/08  - MPR : Préparation du fichier xml
						$data_x[] = '<label ta_level="'.$key.'_2" color="#0000FF">'.$key.'</label>';
						
						if($val !== '' and $val !== null) {
							$data_y[] = '<value data_values="'.$key.'_data_2">'.$val.'</value>';
						} else {
							$data_y[] = '<value data_values="'.$key.'_data_2">0</value';
						}
					}
				
					
					$file = '<?xml version="1.0" encoding="UTF-8" ?><chart>';
					$file.= ' 
									<properties>
										<tabtitle>
											<text>'.__T("A_APPLICATION_STATS_LABEL_TRAFFIC").'</text>
										</tabtitle>
										<margin_top>50</margin_top>
										<margin_left>0</margin_left>
										<margin_bottom>0</margin_bottom>				
										<margin_color>#ffffff</margin_color>
										<margin_right>0</margin_right>
										<width>600</width>
										<height>155</height>
										<left_axis_label></left_axis_label>
									</properties>
									';
					// On définit l'axe des abscisses
					// maj 03/11/08 - MPR : Génération du fichier xml
					$file.= '<xaxis_labels interval="2">'.implode("",$data_x)."</xaxis_labels>";
					$file.= '<datas><data label="Nb Pages" type="bar" stroke_color="blue" fill_color="blue@0.7" yaxis="left">'.implode("",$data_y).'</data></datas></chart>';

					
					$file_xml = REP_PHYSIQUE_NIVEAU_0."png_file/appli_stats_".uniqid("").".xml";
					exec("rm -f  $file_xml");
					exec("touch $file_xml");
					
					// Création du fichier xml
					$fp = @fopen ($file_xml, "r+");
					if ( $fp )
					{
						// 18/11/2009 BBX : On récupère le statut de l'écriture dans le fichier. BZ 12633
						$execCtrl = @fputs($fp, $file);
						@fclose($fp); 
						
						// 18/11/2009 BBX : On ne génère le graphe que si le fichier xml existe. BZ 12633
						if($execCtrl)
						{						
							//   maj 03/11/08 - MPR : génération du graphe à partir du fichier xml créé précédemment							
							$myGraph = new chartFromXML($file_xml);
							$myGraph->loadDefaultXML(MOD_CHARTFROMXML."class/chart_default.xml");
							$myGraph->setBaseDir(REP_PHYSIQUE_NIVEAU_0.'png_file/');
							// on définit l'url des images sauvées
							$myGraph->setBaseUrl(NIVEAU_0.'png_file/');
							$graphe = $myGraph->getHTML();				 
							echo $graphe;
						}
					}
					else
					{	
						// 15:27 09/11/2009 GHX
						// Correction du BZ 12633
						// Si on n'a pas pu créer le fichier XML, on affiche un message pour dire qu'il est impossible de créer le graphe
						echo "<div class='errorMsg'>".__T('A_E_UNABLE_CREATE_GRAPH')."</div>";
					}

				} else  {
						echo "No visitor found.";
				}
			}
		?>
	</div>
	<div align="right" class="texteGris">
			<?php
										
			if (!$user_statistics) {
				// 09/08/2007 - Modif. benoit : remplacement de la valeur "defaut" du scenario du selecteur par "normal"
				?>
				<a href="<?=NIVEAU_0?>myadmin_user/intranet/php/affichage/connection_watch_index.php" target="_parent" style='font-size:10px;'>>> More...</a>
	<?php	}else{ ?>
				<a href="<?=NIVEAU_0?>myadmin_user/intranet/php/affichage/traffic_watch.php" target="_parent" style='font-size:10px;'>>> More...</a>
	<?php	} ?>
	</div>
</fieldset>

