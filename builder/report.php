<?php
/**
*	@cb4100@
*	- Creation SLC	 12/11/2008
*
*	Page principale du Report builder
*
*	30/01/2009 GHX
*		- modification des requetes SQL pour mettre des valeurs entre cote [REFONTE CONTEXTE]
*	02/02/2009 GHX
*		- Suppression de la colonne internal_id [REFONTE CONTEXTE]
*	05/02/2009 GHX
*		- La requete qui récupère les labels n'était pas exécuter sur la bonne base (variable incorrect)
*	03/06/2009 SPS
*		- on remplace tous les alt des images par onmouseover=popalt() : correction bug 9597
*
*	17/06/2009 BBX : ajout du produit dans le liens de visualisation des alarmes. BZ 9708
*
*	15/07/2009 GHX
*		- Correction du BZ 10570 [REC][T&A Cb 5.0][Report Builder]: pas de message si le rapport n'est pas configuré
*			-> Affichage d'un message pour dire si le rapport est correctement configuré ou non
*
*	23/07/2009 BBX : ajout du paramètre resizable sur la popup de configuration du sélecteur. BZ 9938
 *      20/09/2010 NSE bz 17820 : edit graph au lieu de report properties
*   20/01/2011 OJT : Correction bz 20214 Ajout d'informations sur le produit (label)
*/

include_once('common.inc.php');

// 10:34 30/01/2009
// suppression du formatage en INT
$id_page = $_GET['id_page'];

// current URL
$current_url = $niveau0.'builder/report.php';
if ($id_page > 0) $current_url .= '?id_page='.$id_page; 
$_SESSION['current_url'] = $current_url;

?>

<script type="text/javascript">
var id_page = '<?php echo $id_page?>';
</script>

<script type="text/javascript" src="js/common.js" charset='iso-8859-1'></script>
<script type="text/javascript" src="js/report_builder.js" charset='iso-8859-1'></script>
<script type="text/javascript" src="js/report_builder_elements.js" charset='iso-8859-1'></script>


<?php



if ($id_page != '0' && !empty($id_page)) {
	// on va chercher les infos du rapport
	$query = " --- we get the report infos
		select * from sys_pauto_page_name as sppn where id_page='$id_page'";
	$report = $db->getrow($query);
	
	// est-ce que le visiteur a les droits d'écriture sur le rapport ?
	if (allow_write($report)) {
		$disabled = '';
	} else {
		$disabled = "disabled='disabled'";
	}
	
	// on va chercher les différents dash/alarms qui constituent le rapport
	$query = "	--- get the dashs/alarms of the report $id_page
		SELECT	spc.*,sdp.sdp_label
		FROM sys_pauto_config as spc
			LEFT JOIN sys_definition_product AS sdp ON spc.id_product = sdp.sdp_id
		WHERE spc.id_page = '$id_page'
		GROUP BY spc.id,spc.id_product,spc.id_elem,spc.class_object,spc.id_page,spc.ligne,sdp.sdp_label
		ORDER BY spc.ligne asc";
	$elements = $db->getall($query);
}

$products = getProductInformations(); // Lecture des informations produits

?>

<link rel="stylesheet" type="text/css" id="builder_sheet" href="common.css"/>


<!-- on centre l'interface avec ce div conteneur -->
<div id="page">

	<div id="element_list">
		<?php	// on inclu la liste des Dashboards et Alarmes
				include('report_dashboard_list.php');
		?>
	</div>
	
	<div id="builder" class="texteGris">
		<img src="../images/titres/pauto_report_builder.gif" width="387" height="42" alt="Report Builder" />
		
		<div id="flash_container"><div id="flash_msg" style="display:none;"></div></div>
		
		<?php 
		/* 
			13:14 15/07/2009 GHX
			Correction du BZ10570 [REC][T&A Cb 5.0][Report Builder]: pas de message si le rapport n'est pas configuré
		*/
		?>
		<div id="report_not_configured" style="display:none;"><?php echo __T('G_GDR_BUILDER_REPORT_NOT_CONFIGURED'); ?></div>
		
		<div id="gtm_list">
			
			<?php if ($id_page) {?>
				<?php
					// gestion du bouton "supprimer" du rapport
					if ($disabled=='') {
						// on verifie que le report n'appartient pas à un schedule
                                                // 22/11/2012 BBX
                                                // BZ 30306 : correction de la requête qui regarde si le rapport est utilisé dans un schedule
                                                $sql = " --- on va chercher les schedules auxquels appartiennent ce rapport
                                                        SELECT * FROM sys_report_schedule WHERE string_to_array(report_id,',') @> ARRAY['$id_page']";
						$schedules = $db->getall($sql);
						if ($schedules) {
							foreach ($schedules as $sched) {
								$sched_msg .= "<br/> - {$sched['schedule_name']}";
							}
							echo "<div class='del'><img src='images/error.png' onmouseover=\"popalt('This report belongs to these schedules :".$sched_msg."')\" width='16' height='16'/></div>";
						} else {
							echo "<div class='del' onclick=\"delete_report('$id_page');\">
							<form action='report_delete.php' method='post' id='delete_gtm_form' style='margin:0;padding:0;display:inline;'><input type='hidden' name='id_page' value='$id_page'/></form><img src='images/delete.png' onmouseover=\"popalt('".__T('G_GDR_BUILDER_DELETE_THE_REPORT')."')\" width='16' height='16'/></div>";
						}

					}
				// 20/09/2010 NSE bz 17820 : edit graph au lieu de report properties
                                ?>
				<div class="info" onclick="get_properties();"><img src='images/application_edit_off.png' onmouseover="popalt('<?php echo __T('G_GDR_BUILDER_EDIT_REPORT_PROPERTIES'); ?>')" width="16" height="16"/></div>
			<?php } ?>
	
			<?php	// menu déroulant listant TOUS les rapports
					include('common_main_list.php');
					current_list('report');
			?>
			
			<!-- properties du report -->
			<div class="properties" id="gtm_properties" <?php if ($_GET['properties'] == 'open') echo "style='display:block'"; ?>>
				<form action="report_save.php" method="post" style="margin:0;" onsubmit="return check_reportForm();" name="gtmForm">
					<input type="hidden" name="id_page" id="hidden_id_page" value="<?php echo $id_page?>"/>
					<table>
						<tr>
							<td class="fieldname">&nbsp;<?php echo __T('G_GDR_BUILDER_TITLE')?>&nbsp;</td>
							<td><input name="page_name" id="page_name" value="<?php echo $report["page_name"]?>" <?=$disabled?> style="width:340px;"/></td>
						</tr>
						<tr>
							<td></td>
							<td>
								<?php if ($id_page != '0' && !empty($id_page)) { ?>
									<?php if ($disabled == '') { ?>
										<input type="submit" value="<?php echo __T('G_GDR_BUILDER_SAVE')?>"/>
									<?php } ?>
								<?php } else { ?>
									<input type="submit" value="<?php echo __T('G_GDR_BUILDER_CREATE_NEW_REPORT')?>"/>
								<?php } ?>
								<input type="reset" onclick="get_properties();" value="<?php echo __T('G_GDR_BUILDER_CLOSE')?>"/>
							</td>
						</tr>
					</table>
				</form>

			</div>
		</div>
	
		<?php if ($id_page != '0' && !empty($id_page)) { ?>
			<div id="inside_gtm">
				<h2 class="blue"><?php echo __T('G_GDR_BUILDER_DASHBOARDS_AND_ALARMS_IN_THAT_REPORT')?></h2>
				
				<div style="text-align:right;margin-right:42px;">
					<img src='images/information_off.png' id='show_builder_information' onmouseover="popalt('<?php echo __T('G_GDR_BUILDER_USING_THE_GRAPH_BUILDER')?>')" width='16' height='16' style='cursor:help;' onclick='show_hide_information();'/>
				</div>

				<div id="builder_information" style="display:none;" class="help"><?php echo __T('G_GDR_BUILDER_HELP_DRAG_TO_RE_ORDER')?></div>
				<!-- liste des data (dash/alarmes) qui sont dans le rapport -->
					<ul id="gtm_elements" class="sortable">
						<?php
							if ($elements) {
								foreach ($elements as $elem)
                                {
                                    $dashAlarmProductLabel = ''; // Réference produit de l'élément
									
									// on va chercher les infos concernant les éléments
									if ($elem['class_object'] == 'page') {
										$elem['label'] = $db->getone("select page_name from sys_pauto_page_name where id_page='".$elem['id_elem']."'");
										
										// 10:51 15/07/2009 GHX
										// Correction du BZ 10570
										// Exécution d'une requete pour savoir si le sélecteur du dashboard est configuré
										$db->execute("
											SELECT * 
											FROM sys_definition_selecteur
											WHERE 
												sds_report_id = '{$id_page}'
												AND sds_id_page = '{$elem['id_elem']}'
											");
										$isConfigured = $db->getNumRows();
									} else {
										switch ($elem['class_object']) {
											case 'alarm_static':
												$query = " --- we get name of alarm \n select alarm_name from sys_definition_alarm_static where alarm_id='".$elem['id_elem']."'";
											break;
											case 'alarm_dynamic':
												$query = " --- we get name of alarm \n select alarm_name from sys_definition_alarm_dynamic where alarm_id='".$elem['id_elem']."'";
											break;
											case 'alarm_top_worst':
												$query = " --- we get name of alarm \n select alarm_name from sys_definition_alarm_top_worst where alarm_id='".$elem['id_elem']."'";
											break;
										}
                                                                                // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
										$db_temp = Database::getConnection($elem['id_product']);
										// 11:37 05/02/2009 GHX
										// La requete n'était pas fait sur la bonne base
										$elem['label'] = $db_temp->getone($query);
									}
									
									
									
									echo "
										<li id='gtm_element__{$elem['id']}'>
											<div style='padding-bottom:1px;'>
												<div class='icon'><img src='images/".(($elem['class_object']=='page')?'dashboard':'alarm').".png' onmouseover=\"popalt('".(($elem['class_object']=='page')?__T('G_GDR_BUILDER_DASHBOARD'):__T('G_GDR_BUILDER_ALARM_FROM',$elem['sdp_label']))."')\" width='16' height='16'/></div>
											";
									
									if ($disabled=='') {
											echo "<div class='del' onclick=\"delete_element('gtm_element__{$elem['id']}');\"><img src='images/delete.png' onmouseover=\"popalt('".__T('G_GDR_BUILDER_DELETE')."')\" width='16' height='16'/></div>";
									}
									
                                    // Dans la cas d'un Dashboard
									if ($elem['class_object']=='page')
                                    {
                                        // Lecture du produit du dash en cours (bz20214)
                                        $dashProduct = DashboardModel::getDashboardProducts( $elem['id_elem'] );
                                        $dashAlarmProductLabel = 'multi product';
                                        if( count( $dashProduct ) === 1 )
                                        {
                                            $dashAlarmProductLabel = $products[$dashProduct[0]]['sdp_label'];
                                        }

										// 11:26 15/07/2009 GHX
										// Correction du BZ10570
										// Ajout d'un message comme quoi le dashboard n'est pas configuré
										// 23/07/2009 BBX : ajout du paramètre resizable. BZ 9938
                                                                                // 12/10/2010 NSE 18412 : ajout d'un test JS avant redirection vers lien href : isDashElementDragging
										echo "	<div class='info'>
													<a	href='window.open(setup_report_detail.php?id_page={$elem['id_elem']}&report_id={$id_page})'
														onclick='setIdDashEditSelecteur(\"{$elem['id']}\");window.open(\"setup_report_detail.php?id_page={$elem['id_elem']}&report_id={$id_page}\",\"\",\"resizable=yes,menubar=no, status=no, scrollbars=yes, menubar=no, width=900, height=300\"); return false;'>
														<img src='images/application_edit_off.png' onmouseover=\"popalt('".__T('G_GDR_BUILDER_DETAILS')."')\"/>
													</a>
												</div>
												<div class='label'><a onclick='return isDashElementDragging( this );' href='dashboard.php?id_page={$elem['id_elem']}'>{$elem['label']}</a> ".($isConfigured ? "" : "<span class=\"dash_not_configured\">(".__T('G_GDR_BUILDER_DASHBOARD_NOT_CONFIGURED').")</span>")."</div>
												";
									}

                                    // Dans le cas d'une Alarme
                                    else
                                    {
                                        $dashAlarmProductLabel = $products[$elem['id_product']]['sdp_label'];

										// 17/06/2009 BBX : ajout du produit. BZ 9708
										echo "	<div class='info'><a href='../pauto/intranet/php/affichage/pauto_report_alarm_view.php?alarm_id={$elem['id_elem']}&product={$elem['id_product']}&alarm_type={$elem['class_object']}' target='_blank'><img src='images/application_edit_off.png' onmouseover=\"popalt('".__T('G_GDR_BUILDER_DETAILS')."')\"/></a></div>
												<div class='label'>{$elem['label']}</div>
												";
									}
										echo "
											</div>
                                        <div class='product'>{$dashAlarmProductLabel}</div>
											<div id='elem_prop__{$elem['id']}' class='properties'></div>
										</li>";
								}
							} else {
								echo "<style type='text/css'>
										#inside_gtm {display: none;}
										#nothing_inside {display:block;}
									</style>";
							}
						?>
					</ul>
				</div>
				
				<div id="nothing_inside" class="help"><?php echo __T('G_GDR_BUILDER_HELP_CLICK_TO_ADD_DASH_ALARM')?></div>

				<style type="text/css" id='na_level_css'>div.na_levels {display:none;}</style>
				
				<?php if ($elements) { ?> <script type="text/javascript">  make_elements_sortable(); /*get_na_levels_in_common(); */</script><?php } ?>
				
		<?php } ?>
		
		<!-- boutons du bas : 	NEW	SHARE  COPY -->
		<br />
		&nbsp;&nbsp;
		<form action="report.php" method="get" style="margin:0;display:inline;">
			<input type="hidden" name="properties" value="open"/>
			<input type="submit" style="width: 70px;" class="bouton" value="<?php echo __T('G_GDR_BUILDER_NEW')?>" />
		</form>&nbsp;&nbsp;

		<?php if ($id_page != '0' && !empty($id_page)) { ?>
			<!-- bouton COPY -->
			<!-- form action="report_copy.php" method="post" style="margin:0;display:inline;">
				<input type="hidden" name="id_page" value="<?php echo $id_page ?>"/>
				<input type="submit" style="width: 70px;" class="bouton" value="<?php echo __T('G_GDR_BUILDER_COPY')?>"/>
			</form>&nbsp;&nbsp;
			-->
			
			<!-- bouton PREVIEW -->			
			<input type="button" style="width:120px;" class="bouton" value="<?php echo __T('G_GDR_BUILDER_PREVIEW_IN_PDF')?>" onclick="window.open('report_preview.php?id_report=<?=$id_page ?>','','status=yes,location=yes,resizable=yes,directories=yes,scrollbars=yes,width=800,height=600')" />&nbsp;&nbsp;
		<?php } ?>
				
	
	</div>
</div>


<br clear="both"/>
<br/>
<?php
//	echo $db->displayQueries();
?>

<style type="text/css">
</style>

<script type='text/javascript'>
function adaptLayoutToWindowHeight() {
	// hauteur de la fenêtre
	var docHeight = document.viewport.getHeight();
	// hauteur du haut de #page
	var pageTop = $('page')['offsetTop'];
	// remaining height
	var remainingHeight = docHeight - pageTop - 10;
	$('page').style.height = remainingHeight + 'px'; 
	// hauteur du haut de #gtm_elements_list
	var elemTop = $('gtm_elements_list')['offsetTop'];
	var elemsHeight = docHeight - pageTop - elemTop - 18;
	$('gtm_elements_list').style.height = elemsHeight + 'px'; 

}

adaptLayoutToWindowHeight();
displayReportNotConfigured();

// 18/11/2011 BBX
// BZ 21951 : resize auto de la fenêtre
window.onresize = function() { adaptLayoutToWindowHeight(); }
</script>

