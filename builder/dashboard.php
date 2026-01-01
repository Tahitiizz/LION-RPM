<?php
/**
*	@cb4100@
*	- Creation SLC	 29/10/2008
*
*	Page principale du GTM builder
*
*
*	29/01/2009 GHX
*		- modification des requêtes SQL pour mettre id_user & id_page entre cote au niveau des inserts  [REFONTE CONTEXTE]
*		- correction d'un bug, c'était pas le bon fichier qui était appelé pour les boutons "share" et "unshare"
*	30/01/2009 GHX
*		- modification des requêtes SQL pour mettre id entre cote au niveau des inserts  [REFONTE CONTEXTE]
*	03/06/2009 SPS
*		- on remplace tous les alt des images par onmouseover=popalt() : correction bug 9597
*	26/03/2010 OJT
*		- correction bz 14761  : Ajout des charset iso-8859-1 pour l'inclusion des scripts JavaScript
*
*	09/06/10 YNE/FJT : SINGLE KPI
*
*   09/06/2010 MPR
*              Correction du BZ15686 - Après avoir configuré un dash en homepage logout / login et bloquage de l'application
*                                      -> un msg d'erreur apparait "Can't load current dashboard. Please check that this dashboard and its call are correctly configured"
*   20/01/2011 OJT : Correction bz 20214 Ajout d'informations sur le produit (label)
*	03/11/2011 ACS BZ 23242 Wrong GUI of dashboard screen on Firefox and Chrome
*/

include_once('common.inc.php');

?>

<script type="text/javascript">
var id_page = '<?php echo $id_page; ?>';
</script>

<script type="text/javascript" src="js/common.js" charset='iso-8859-1'></script>
<!-- 26/03/2010 OJT bz 14761 : ajout des charset iso-8859-1 -->
<script type="text/javascript" src="js/dashboard_builder.js" charset='iso-8859-1'></script>
<script type="text/javascript" src="js/dashboard_builder_elements.js" charset='iso-8859-1'></script>

<?php

// id du dashboard en cours
// 09:42 30/01/2009 GHX
// Suppression du formatage en INT
$id_page = $_GET['id_page'];


// current URL
$current_url = $niveau0.'builder/dashboard.php';
if ($id_page != '0' && !empty($id_page)) $current_url .= '?id_page='.$id_page; 
$_SESSION['current_url'] = $current_url;


if ($id_page != '0' && !empty($id_page)) {
	// on va chercher les infos du Dashboard
	$query = " --- we get the dashboard infos
		select sppn.*, sdd.*
		from sys_pauto_page_name as sppn, sys_definition_dashboard as sdd
		where sppn.id_page='$id_page'
			and sdd.sdd_id_page='$id_page'";
	$dash = $db->getrow($query);
	
	if (!$dash) { echo "<div class='error'>Error: dashboard not found.</div>"; exit; }
	
	// est-ce que le visiteur a les droits d'écriture sur le Dashboard ?
	if (allow_write($dash)) {
		$disabled = '';
	} else {
		$disabled = "disabled='disabled'";
	}
	
	// on va chercher les différents graphs qui constituent le Dashboard
	$query = "	--- get the graphs of the Dashboard '$id_page'
		SELECT spc.*,sppn.droit,sppn.share_it, gi.object_type,

			-- calcul du label :
			sppn.page_name
			|| CASE WHEN sppn.id_user IS NOT NULL AND sppn.id_user <> '{$user_info['id_user']}' THEN 
				(SELECT ' ['||username||']' FROM users WHERE id_user=sppn.id_user) 
				ELSE '' 
				END
			AS graph_label,

			-- calcul du niveau de droit du graph :
			CASE WHEN sppn.droit='customisateur' THEN 1
				ELSE CASE WHEN sppn.droit='client' AND sppn.id_user IS NULL THEN 2
				ELSE CASE WHEN sppn.droit='client' AND sppn.id_user IS NOT NULL AND sppn.id_user <> '{$user_info['id_user']}' THEN 3
				ELSE CASE WHEN sppn.droit='client' AND sppn.id_user IS NOT NULL AND sppn.id_user = '{$user_info['id_user']}' THEN 4
			END END END END AS graph_level
			
		FROM sys_pauto_config as spc
			JOIN sys_pauto_page_name as sppn ON spc.id_elem=sppn.id_page
			JOIN graph_information as gi ON spc.id_elem=gi.id_page

		WHERE spc.id_page = '$id_page'

		ORDER BY ligne asc
	";
	$elements = $db->getall($query);
	
	
	
	$na_levels = getNaLabelList('na');

} else {
	// id_page = 0, on a pas de dashboard spécifié, donc on se fixe des valeurs par défaut
	$id_page = 0;
	$dash = array(
		'sdd_sort_by_order'					=> 'desc',
		'sdd_selecteur_date_config'			=> 1,
		'sdd_selecteur_default_period'			=> 30,
		'sdd_selecteur_default_top_overtime'	=> 3,
		'sdd_selecteur_default_top_overnetwork'	=> 40,
	);
}

$products = getProductInformations(); // Lecture des informations produits

?>

<link rel="stylesheet" type="text/css" href="common.css"/>

<!-- on centre l'interface avec ce div conteneur -->
<div id="page">

	<div id="element_list">
		<?php	// on inclue la liste des graphs
				include('dashboard_graph_list.php');
		?>
	</div>
	
	<?php /* echo $dashs_dump */ ?>
	
	<div id="builder" class="texteGris">
		<img src="<?php echo NIVEAU_0;?>/images/titres/pauto_page_builder.gif" width="387" height="42" onmouseover="popalt('Dashboard and view builder')" />
		
		<div id="flash_container"><div id="flash_msg" style="display:none;"></div></div>
		
		<div id="gtm_list">
			
			<!-- bouton delete Dashboard -->
			<?php if  ($id_page != '0' && !empty($id_page)) { 

					// gestion du bouton "supprimer" / "warning" du dashboard
					$warning = false;
					$warning_msg = '';
					
					// on regarde si le dashboard est inclu dans des rapports
					$query = " --- on va chercher les rapports qui contiennent le dashboard $id_page
							SELECT page_name FROM sys_pauto_page_name 
							WHERE id_page IN 
							(SELECT id_page FROM sys_pauto_config WHERE id_elem='$id_page')";
					$reports = $db->getall($query);
					if (sizeof($reports) > 0) {
						$warning = true;
						$warning_msg = __T('G_GDR_BUILDER_THIS_ITEM_BELONGS_TO_THESE_REPORTS');
						foreach ($reports as $rep) {
							$warning_msg .= "<br/> - {$rep['page_name']}";
						}
                                                $warning_msg .= "<br />";
					}

                                        unset($reports);
                                        // Correction du BZ15686 - Après avoir configuré un dash en homepage logout / login et bloquage de l'application
                                        //                          -> un msg d'erreur apparait "Can't load current dashboard. Please check that this dashboard and its call are correctly configured"
                                        // Contrôle pour savoir si le dashboard est configuré en homepage
                                        // 07/06/2011 BBX -PARTITIONING-
                                        // Correction des casts
                                        $query = "--- On vérifie que le dashboard $id_page n'est pas configuré en homepage pour un ou plusieurs users
                                                    SELECT username
                                                    FROM sys_definition_selecteur , users
                                                    WHERE sds_id_selecteur::text = homepage
                                                          AND sds_id_page = '{$id_page}';";
                                        $reports = $db->getAll($query);
                                        if (sizeof($reports) > 0)
                                        {
						$warning = true;
						$warning_msg .= __T('G_GDR_BUILDER_THIS_ITEM_IS_DEFINED_AS_USER_HOMEPAGE');
						foreach ($reports as $rep) {
							$warning_msg .= "<br/> - {$rep['username']}";
						}
                                                $warning_msg .= "<br />";

					}
					unset($reports);

                                        // Correction du BZ15686 - Après avoir configuré un dash en homepage logout / login et bloquage de l'application
                                        //                          -> un msg d'erreur apparait "Can't load current dashboard. Please check that this dashboard and its call are correctly configured"
                                        // Contrôle pour savoir si le dashboard est configuré en homepage utilisateur par défaut
                                        $query = "--- On vérifie que le dashboard $id_page n'est pas configuré en la homepage user par défaut
                                                    SELECT parameters
                                                    FROM sys_global_parameters
                                                    WHERE value = '{$id_page}';";
                                        $reports = $db->execute($query);
                                        if ( $db->getNumrows() > 0)
                                        {
						$warning = true;
						$warning_msg .= __T('G_GDR_BUILDER_THIS_ITEM_IS_DEFINED_AS_USER_HOMEPAGE_DEFAULT');
					}
					unset($reports);

					if ($warning) {
						echo "<div class='del'><img src='images/error.png' onmouseover=\"popalt('$warning_msg')\" width='16' height='16'/></div>";
					} else {
						if ($disabled=='') {
							echo "<div class='del' onclick=\"delete_dashboard('$id_page');\">
							<form action='dashboard_delete.php' method='post' id='delete_gtm_form' style='margin:0;padding:0;display:inline;'><input type='hidden' name='id_page' value='$id_page'/></form><img src='images/delete.png' onmouseover=\"popalt('".__T('G_GDR_BUILDER_DELETE_THE_WHOLE_DASHBOARD')."')\" width='16' height='16'/></div>";
						}
					} ?>
					<div class="info" onclick="get_dashboard_properties();"><img src='images/application_edit_off.png' onmouseover="popalt('<?php echo __T('G_GDR_BUILDER_EDIT_GRAPH_PROPERTIES'); ?>')" width="16" height="16"/></div>
			<?php } ?>
	
			<!-- menu déroulant listant tous les dashboards -->
			<?php	// menu déroulant listant les dashboards
					include('common_main_list.php');
					current_list('page');
			?>
			
			<!-- properties du Dash -->
			<div class="properties" id="gtm_properties" <?php if ($_GET['properties'] == 'open') echo "style='display:block'"; ?>>
				<?php include("dashboard_get_properties.php"); ?>
			</div>
		</div>
	
		<?php if ($id_page != '0' && !empty($id_page)) { ?>
			<div id="inside_gtm">
				<h2 class="blue"><?php echo __T('G_GDR_BUILDER_GTMS_IN_YOUR_DASHBOARD')?></h2>
				
				<!-- liste des NA levels en commun -->
				<div id="list_na_levels">
						<img src='images/transmit_blue.png' onmouseover="popalt('<?php echo __T('G_GDR_BUILDER_NETWORK_AGGREGATION_LEVELS'); ?>')" width='16' height='16' align='absmiddle'/> <?php echo __T('G_GDR_BUILDER_NETWORK_AGGREGATION_LEVELS_IN_COMMON'); ?>
						<ul id="na_levels_in_common">
							<br/>
						</ul>
				</div>
				
				<!-- liste des GTMs qui sont dans le Dashboard -->
					<div style="text-align:right;margin-right:42px;">
						<img src='images/information_off.png' id='show_builder_information' onmouseover="popalt('<?php echo __T('G_GDR_BUILDER_USING_THE_GRAPH_BUILDER')?>')" width='16' height='16' style='cursor:help;' onclick='show_hide_information();'/>
						<img id="show_na_levels" src='images/transmit_blue_off.png' onmouseover="popalt('<?php echo __T('G_GDR_BUILDER_SHOW_NA_LEVELS')?>')" width='16' height='16' style='cursor:pointer;' onclick='show_hide_na_levels();'/>
					</div>
					<div id="builder_information" class="help" style="display:none;"><?php echo __T('G_GDR_BUILDER_HELP_DRAG_TO_RE_ORDER')?></div>
					<ul id="gtm_elements" class="sortable">
						<?php
							if ($elements) {
								foreach ($elements as $elem) {
									$is_client = '';
									
                                    // Lecture du produit du graph en cours (bz20214)
                                    $gtmModel = new GTMModel( $elem['id_elem'] );
                                    $gtmProduct = $gtmModel->getGTMProducts();
                                    $gtmProductLabel = 'multi product';
                                    if( count( $gtmProduct ) === 1 )
                                    {
                                        $gtmProductLabel = $products[$gtmProduct[0]]['sdp_label'];
                                    }

									// 24/03/09 YNE
 									// add single KPI icone
 									switch($elem['object_type']){
 										case 'graph' : $image = "bar"; break;
 										case 'pie3D' : $image = "pie"; break;
 										case 'singleKPI' : $image = "single"; break;
 										default : $image = "bar";
									}

									echo "
										<li id='gtm_element__{$elem['id_elem']}'>
											<div style='padding-bottom:1px;'>
												<div class='icon'><img src='images/chart_{$image}_{$elem['graph_level']}.png' onmouseover=\"popalt('".$level_labels[$elem['graph_level']]." GTM')\" width='16' height='16'/></div>
											";
									
									$warning = false;
									$warning_msg = '';

									// on vérifie si un élément du GTM est utilisé comme tri overtime dans le dashboard
									// j'ai mon GTM  $elem['id_page']
									$query = " --- on cherche si une data du graph {$elem['id_elem']} est clef de tri du Dashboard $id_page
										select id from sys_pauto_config
										where id_page='{$elem['id_elem']}'
											and id='".$dash['sdd_sort_by_id']."'";
									$dashboards = $db->getall($query);
									if (sizeof($dashboards) > 0) {
										$warning = true;
										$warning_msg .= __T('G_GDR_BUILDER_THIS_GTMS_DATA_IS_USED_FOR_THE_ORDER_BY_OF_THIS_DASHBOARD');
									}
									
									if ($warning) {
										echo "<div class='del'><img src='images/error.png' onmouseover=\"popalt('$warning_msg')\" width='16' height='16'/></div>";
									} else {
										if ($disabled=='') {
											echo "<div class='del' onclick=\"delete_element('gtm_element__{$elem['id_elem']}');\"><img src='images/delete.png'onmouseover=\"popalt('".__T('G_GDR_BUILDER_DELETE')."')\" width='16' height='16'/></div>";
										}
									}

									// on calcule les na levels en commun (axe1 et axe3)
									$na_levels_in_common = getNALabelsInCommon($elem['id_elem'],'na');
									$na_axe3_levels_in_common = getNALabelsInCommon($elem['id_elem'],'na_axe3');
									
									if (is_array($na_levels_in_common))
										$elem_na_levels = implode(', ',$na_levels_in_common);
									if (($na_levels_in_common) and ($na_axe3_levels_in_common != ''))
										$elem_na_levels .= ', '; 
									if ($na_axe3_levels_in_common)
										$elem_na_levels .= '<span class="axe3">'.implode(', ',$na_axe3_levels_in_common).'</span>';

                                    // 14/09/2010 OJT : Correction bz17840, ajout d'un test JS avant redirection ver lien href
                                    // 10/10/2011 BBX : placement correct du div de fermeture. BZ 23242
                                    // 03/11/2011 ACS BZ 23242 add div with clear property
									echo "<div class='label'><a onclick='return isDashElementDragging( this );' href='graph.php?id_page={$elem['id_elem']}'>{$elem['graph_label']}</a></div>
											
											<div class='na_levels'>$elem_na_levels</div>
                                                                                            <div class='product'>{$gtmProductLabel}</div>
											<div id='elem_prop__{$elem['id_elem']}' class='properties'></div>
                                            <div class='clear'></div>
                                          </div>
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
				
				<div id="nothing_inside" class="help"><?php echo __T('G_GDR_BUILDER_HELP_CLICK_TO_ADD_GRAPH')?></div>

				<style type="text/css" id='na_level_css'>div.na_levels {display:none;}</style>

				<?php if ($elements) { 
					?> 
					<script type="text/javascript"> 
						make_elements_sortable();  
						get_na_levels_in_common();
					</script>
				<?php } ?>
				
		<?php } ?>
		
		<!-- boutons du bas : 	NEW	SHARE  COPY -->
		<br />
		&nbsp;&nbsp;
		<form action="dashboard.php" method="get" style="margin:0;display:inline;">
			<input type="hidden" name="properties" value="open"/>
			<input type="submit" style="width: 70px;" class="bouton" value="<?php echo __T('G_GDR_BUILDER_NEW')?>" />
		</form>&nbsp;&nbsp;
	

		<?php if (($user_info['profile_type']=='user') and ($dash["droit"]=='client') and ($dash["id_user"]==$user_info["id_user"])) {
			if ($dash["share_it"] == 1) { ?>
				<!-- bouton UN-SHARE -->
				<form action="common_share.php" method="post" style="margin:0;display:inline;">
					<input type="hidden" name="type" value="dashboard"/>
					<input type="hidden" name="id_page" value="<?php echo $id_page ?>"/>
					<input type="hidden" name="unshare" value="1"/>
					<input type="submit" style="width: 90px;" class="bouton" value="<?php echo __T('G_GDR_BUILDER_UNSHARE')?>"/>
				</form>&nbsp;&nbsp;
			<?php } else { ?>
				<!-- bouton SHARE -->
				<form action="common_share.php" method="post" style="margin:0;display:inline;">
					<input type="hidden" name="type" value="dashboard"/>
					<input type="hidden" name="id_page" value="<?php echo $id_page ?>"/>
					<input type="submit" style="width: 70px;" class="bouton" value="<?php echo __T('G_GDR_BUILDER_SHARE')?>"/>
				</form>&nbsp;&nbsp;
			<?php } ?>
		<?php } ?>

		<!-- bouton COPY -->
		<?php if ($id_page != '0' && !empty($id_page)) { ?>
			<form action="dashboard_copy.php" method="post" style="margin:0;display:inline;">
				<input type="hidden" name="id_page" value="<?php echo $id_page ?>"/>
				<input type="submit" style="width: 70px;" class="bouton" value="<?php echo __T('G_GDR_BUILDER_COPY')?>"/>
			</form>&nbsp;&nbsp;
		<?php } ?>
	
	</div>
</div>

<br clear="both"/>
<br/>

<?php	if ($debug)	echo $db->displayQueries();	?>

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
// 18/11/2011 BBX
// BZ 21951 : resize auto de la fenêtre
window.onresize = function() { adaptLayoutToWindowHeight(); }
</script>

