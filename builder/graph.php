<?php
/**
 * @cb5100@
 *
 * 23/07/2010 OJT : Correction BZ 16635 (correction d'une erreur JS)
 * 21/09/2010 OJT : Correction BZ 17518 (utilisation du label du Raw/Kpi plutot que le Data Legend)
 * 14/09/2012 ACS DE Automatically select main family in Graph Builder
 */
/**
*	@cb4100@
*	- Creation SLC	 29/10/2008
*
*	Page principale du Graph builder
*
*	30/01/2009 GHX
*		- modification des requêtes SQL pour mettre id_page, id_elem entre cote [REFONTE CONTEXTE]
*	02/02/2009 GHX
*		- Suppression de la colonne internal_id [REFONTE CONTEXTE]
*	03/06/2009 SPS 
*		- on remplace tous les alt des images par onmouseover=popalt() : correction bug 9597
*	12/06/2009 GHX
*		- Remplacement des "\n" par un "<br />" sinon erreur JS
*	30/06/2009 MPR
*		- Remplacement des "\n" par un "<br />" sinon erreur JS (il en restait un)
*		- Correction du bug 9775 : On check si le gis doit être actif ou non
*	08/07/2009 SPS
*		- correction bug 10445 :on supprime l'affichage du tooltip sur l'evenement onmouseover car il reste affiche lors d'une suppression 
*	05/08/2009 GHX
*		- Correction du BZ 6038
*			-> Ajout d'une balise div contenant un message présicant qu'on a plusieurs cumulated bar sur différents axes
*			-> Appel à la fonction hasCumulatedBar() en javascript
*	05/08/2009 - CCT1 : ajout de l'image titre. correction BZ 10290 
*	06/08/2009 GHX
*		- Correction du BZ 10901
*	14/08/2009 GHX
*		- Ajout de 2 divs supplémentaires par éléments présents dans le graphe pour connaitre si c'est un type raw ou kpi et leur nom (qui est différent du data_legend)
*		- (Evo) Modification pour prendre en compte le faite que dans un graphe on peut avoir plusieurs fois le meme KPI [code+label] identique et qu'il est considere comme un seul
*	17/08/2009 GHX
*		- Modification sur la détection du GIS car mal prise en compte
*	28/08/2009 GHX
*		- Modification d'une variable par une autre l'ancienne nom n'existe plus
*
*	09/06/10 YNE/FJT : SINGLE KPI
*
*       09/07/2010 - MPR : Correction du BZ 16620
*           Modification inverse les valeurs doivent être block et non none
*           sinon au premier chargement d'un graphe de type 'graph', impossible de configurer la couleur des raw/kpi
*
*/

include_once('common.inc.php');

// Valeurs min/max pour la taille des graphes.
$graph_width_default	= 900;
$graph_width_minimum	= 700;
$graph_height_default	= 450;
$graph_height_minimum	= 350;

// 10:07 30/01/2009 GHX
// Suppression du format en INT
$id_page = $_GET['id_page'];

// current URL
$current_url = $niveau0.'builder/graph.php';
if ($id_page != '0') $current_url .= '?id_page='.$id_page; 
$_SESSION['current_url'] = $current_url;

?>

<script type="text/javascript">
var graph_width_minimum = <?php echo $graph_width_minimum; ?>;
var graph_height_minimum = <?php echo $graph_height_minimum; ?>;
var id_page = '<?php echo $id_page?>';
</script>

<script type="text/javascript" src="js/common.js" charset='iso-8859-1'></script>
<script type="text/javascript" src="js/graph_builder.js" charset='iso-8859-1'></script>
<script type="text/javascript" src="js/graph_builder_elements.js" charset='iso-8859-1'></script>


<?php

if ($id_page != '0' && !empty($id_page)) {
	// on va chercher les infos du graph
	$query = " --- we get the graph infos
		select sppn.*, gi.*
		from sys_pauto_page_name as sppn, graph_information as gi
		where sppn.id_page= '$id_page'
			and gi.id_page= '$id_page'";
	// __debug($query,"QUERY");
	$graph = $db->getrow($query);
	
	// est-ce que le visiteur a les droits d'écriture sur le graph ?
	if (allow_write($graph)) {
		$disabled = '';
	} else {
		$disabled = "disabled='disabled'";
	}
	
	// on explode $graph["object_type"] s'il contient un @
	list($graph["object_type"],$graph["pie"]) = explode("@",$graph["object_type"],2);
	
	// on va chercher les différentes courbes qui constituent le graph
	$query = "	--- get the plots of the graph $id_page
		SELECT	spc.*,sdp.sdp_label,
				graph_data.data_legend as label
		FROM sys_pauto_config as spc
			JOIN graph_data ON spc.id = graph_data.id_data
			JOIN sys_definition_product AS sdp ON spc.id_product = sdp.sdp_id
		WHERE spc.id_page = '$id_page'
		GROUP BY spc.id,spc.id_product,spc.id_elem,spc.class_object,spc.id_page,spc.ligne,graph_data.data_legend,sdp.sdp_label
		ORDER BY spc.ligne asc";
	$elements = $db->getall($query);
	
	$na_levels = getNaLabelList('na');
	
	// Correction du bug 9775 : On check si le gis doit être actif ou non	
	$gis_active = false;
	$disabled_gis = "";
	// 13:59 06/08/2009 GHX
	// Correction du BZ 10901
	if ( count($elements) > 0 )
	{
		$i = 0;
		while( !$gis_active and $i < count($elements) )
		{
			// 15:46 17/08/2009 GHX
			// La clé n'était pas bonne id_prod au lieu de id_product
			$gis_param_activation = get_sys_global_parameters('gis',0,$elements[$i]['id_product']);
			if( $gis_param_activation )
			{
				$gis_active = true;	
			}else{
				$i++;
			}
		}
	}
	else
	{
		$productsInformations = getProductInformations();
		foreach ( array_keys($productsInformations) as $idProduct )
		{
			if( get_sys_global_parameters('gis', 0, $idProduct) )
			{
				$gis_active = true;	
				continue;
			}
		}
	}
	if(!$gis_active){
		$disabled_gis = "disabled='disabled'";
	}
} else {
	// valeur par défaut du graph
	$id_page = '0';
	$graph = array(
		'object_type'		=> 'graph',
		'asc_desc'			=> 1,
		'gis'				=> 0,
		'position_legende'	=> 'top',
		'graph_width'		=> $graph_width_default,
		'graph_height'		=> $graph_height_default,
		'ordonnee_left_name'	=> 'Data',
	);
	
	// 12:33 06/08/2009 GHX
	// Si on crée un graphe et qu'aucun des produits n'a le GIS d'activé on le désactive
	$disabled = '';
	$gis_active = false;
	$disabled_gis = "";
	$productsInformations = getProductInformations();
	foreach ( array_keys($productsInformations) as $idProduct )
	{
		if( get_sys_global_parameters('gis', 0, $idProduct) )
		{
			$gis_active = true;	
			continue;
		}
	}
	if(!$gis_active)
	{
		$disabled_gis = "disabled='disabled'";
	}
}
?>

<link rel="stylesheet" type="text/css" id="builder_sheet" href="common.css"/>

<script src="js/color_picker.js"></script>
<div id="color_picker_container" style="position:absolute;display:none;z-index:2000;"></div>

<!-- on centre l'interface avec ce div conteneur -->
<div id="page">

	<div id="element_list">
		<?php	// on inclu la liste des RAW/KPI
				include('graph_rawkpi_list.php');
		?>
	</div>
	
	<div id="builder" class="texteGris">
		<!-- 05/08/2009 - CCT1 : ajout de l'image titre. correction BZ 10290  -->
		<div class="h1_title"><img src="<?=NIVEAU_0?>images/titres/graph_builder.gif"/></div>
		
		<div id="flash_container">
		<div id="flash_msg" style="display:none;"></div>
		</div>
		
		<div id="errorCumulatedBar" class="errorMsg" style="display:none;margin:5px 40px;padding:3px 3px 3px 3px;text-align: left;" ><?php echo __T('G_GTM_BUILDER_INFO_CUMULATEDBAR'); ?></div>
		
		<!-- 09:22 14/08/2009 GHX : ajout du message suivant -->
		<div id="elementsWithSameNames" class="infoBox" style="display:none;margin:5px 40px;padding:3px 3px 3px 3px;text-align: left;" ></div>
		<!-- 10/03/2010 YNE : Ajout du message lorsque l'on a plusieurs éléments en mode Single KPI -->
		<div id="elementsMultiple" class="infoBox" style="display:none;margin:5px 40px;padding:3px 3px 3px 3px;text-align: left;" ></div>
		<div id="gtm_list">
			
			<?php if ($id_page != '0' && !empty($id_page)) {?>
				<?php
					// gestion du bouton "supprimer" / "warning" du graph
					$warning = false;
					$warning_msg = '';
					
					// on regarde si le graph est inclu dans des dashboards
					$query = " --- on va chercher les dashboards qui contiennent le graph $id_page
							SELECT page_name FROM sys_pauto_page_name 
							WHERE id_page IN 
							(SELECT id_page FROM sys_pauto_config WHERE id_elem= '$id_page')";
					$dashboards = $db->getall($query);
					if (sizeof($dashboards) > 0) {
						$warning = true;
						$warning_msg = __T('G_GDR_BUILDER_THIS_ITEM_BELONGS_TO_THESE_DASHBOARDS');
						foreach ($dashboards as $dash) {
							$warning_msg .= "<br/> - {$dash['page_name']}";
						}
					}
					unset($dashboards);
							
					if ($warning) {
						echo "<div class='del'><img src='images/error.png' onmouseover=\"popalt('$warning_msg')\" width='16' height='16'/></div>";
					} else {
						if ($disabled=='') {
							echo "<div class='del' onclick=\"delete_gtm('$id_page');\">
							<form action='graph_delete.php' method='post' id='delete_gtm_form' style='margin:0;padding:0;display:inline;'><input type='hidden' name='id_page' value='$id_page'/></form><img src='images/delete.png' onmouseover=\"popalt('".__T('G_GDR_BUILDER_DELETE_THE_WHOLE_GRAPH')."')\" width='16' height='16'/></div>";
						}
					}
				?>
				<div class="info" onclick="get_GTM_properties();"><img src='images/application_edit_off.png' onmouseover="popalt('<?php echo __T('G_GDR_BUILDER_EDIT_GRAPH_PROPERTIES'); ?>')" width="16" height="16"/></div>
			<?php } ?>
	
			<?php	// menu déroulant listant TOUS les graphs
					include('common_main_list.php');
					current_list('graph');
			?>
			
			<!-- properties du graph -->
			<div class="properties" id="gtm_properties" <?php if ($_GET['properties'] == 'open') echo "style='display:block'"; ?>>
				<?php include("graph_get_properties.php"); ?>
			</div>
		</div>
	
		<?php if ($id_page != '0' && !empty($id_page)) { ?>
			<div id="inside_gtm">
				<h2 class="blue"><?php echo __T('G_GDR_BUILDER_THE_DATA_INSIDE_YOUR_GTM'); ?></h2>
				
				<!-- liste des NA levels en commun -->
				<div id="na_levels_in_common_master_div">
                    <div>
                        <img src='images/transmit_blue.png' onmouseover="popalt('<?php echo __T('G_GDR_BUILDER_NETWORK_AGGREGATION_LEVELS'); ?>');" width='16' height='16' align='absmiddle'/> <?php echo __T('G_GDR_BUILDER_NETWORK_AGGREGATION_LEVELS_IN_COMMON'); ?> <small style='color:purple;'>(<?php echo __T('G_GDR_BUILDER_THIRD_AXIS_IN_PURPLE'); ?>)</small>
                        <ul id="na_levels_in_common"></ul>
                    </div>
				</div>
					
					<!-- liste des data (raw/kpi) qui sont dans le graph -->
					<div style="text-align:right;margin-right:42px;">
						<img src='images/information_off.png' id='show_builder_information' onmouseover="popalt('<?php echo __T('G_GDR_BUILDER_USING_THE_GRAPH_BUILDER')?>')" width='16' height='16' style='cursor:help;' onclick='show_hide_information();'/>
						<img id="show_na_levels" src='images/transmit_blue_off.png' onmouseover="popalt('<?php echo __T('G_GDR_BUILDER_SHOW_NA_LEVELS')?>')" width='16' height='16' style='cursor:pointer;' onclick='show_hide_na_levels();'/>
					</div>
					<div id="builder_information" style="display:none;" class="help"><?php echo __T('G_GDR_BUILDER_HELP_DRAG_TO_RE_ORDER')?></div>
					<ul id="gtm_elements" class="sortable">
						<?php
							if ($elements) {
								foreach ($elements as $elem) {
									
									// on va chercher les infos spécifiques aux data (raw/kpi)
                                    // 21/09/2010 OJT : Utilisation de la nouvelle méthode de connexion
									$db_temp = DataBase::getConnection( $elem['id_product'] );
									
									// on cherche le range
									$query = " --- Look for a range for {$elem['class_object']} {$elem['id_elem']}
										select count(sdrs.id_element) as nb_ranges
										from sys_data_range_style as sdrs
										where 	sdrs.id_element= '{$elem['id_elem']}'
											and 	sdrs.data_type='{$elem['class_object']}'";
									$nb_ranges = $db_temp->getone($query);
									
                                    // on cherche le label réel du KPI ou du RAW (correction bz17518, 21/09/2010 OJT)
                                    if ($elem['class_object']=='kpi'){
                                        $query = "SELECT kpi_label as label FROM sys_definition_kpi WHERE id_ligne='{$elem['id_elem']}';";
                                    }
                                    else{
                                        $query = "SELECT edw_field_name_label as label FROM sys_field_reference WHERE id_ligne='{$elem['id_elem']}'";
                                    }
                                    $realLabelName = $db_temp->getone($query);
                                    if( $realLabelName === FALSE ){
                                        $realLabelName = $elem['label']; // Si la requête échoue on garde le Data Legend
                                    }

									// on cherche si le kpi est client
									$is_client = '';
									if ($elem['class_object']=='kpi') {
										$query = " --- get value_type for KPI {$elem['id_elem']}
											SELECT value_type FROM sys_definition_kpi WHERE id_ligne= '{$elem['id_elem']}'";
										$value_type = $db_temp->getone($query);
										if ($value_type == 'client') $is_client = '_client';
									}
									
									// on cherche la famille
									if ($elem['class_object'] == 'counter') {
										$query = " --- get family of counter {$elem['id_elem']}
										SELECT family FROM sys_definition_group_table WHERE edw_group_table IN
											(SELECT edw_group_table FROM sys_field_reference WHERE id_ligne = '{$elem['id_elem']}')";
										// 13:23 14/08/2009 GHX
										// Ajout de la requete
										$queryName = "SELECT lower(edw_field_name) AS name FROM sys_field_reference WHERE id_ligne = '{$elem['id_elem']}'";
									} else {
										$query = " --- get family of kpi {$elem['id_elem']}
										SELECT family FROM sys_definition_group_table WHERE edw_group_table IN
											(SELECT edw_group_table FROM sys_definition_kpi WHERE id_ligne = '{$elem['id_elem']}')";
										// 13:23 14/08/2009 GHX
										// Ajout de la requete
										$queryName = "SELECT lower(kpi_name) AS name FROM sys_definition_kpi WHERE id_ligne = '{$elem['id_elem']}'";
									}
									$elem['family'] = $db_temp->getone($query);
									// 13:23 14/08/2009 GHX
									// Ajout de la requete
									$elem['name'] = $db_temp->getone($queryName);
									
									// on récupère les na_levels disponibles (axe 1)
									// $na_levels = getNaLabelListForProduct('na','',$elem['id_product']);
									$na_levels = getNaLabelListForProduct('na','',$elem['id_product']);
									// on récupère les na_levels disponibles (axe 3)
									$na_levels_axe3 = getNaLabelListForProduct('na_axe3','',$elem['id_product']);
									
									// 11:45 30/01/2009 GHX
									// Ajout d'un deuxieme "_" pour séparer l'id du RAW/KPI
									echo "
										<li id='gtm_element__{$elem['id']}'>
											<div style='padding-bottom:1px;'>
												<div class='icon'><img src='images/brick_{$elem['class_object']}$is_client".(($nb_ranges>0)?'_ranged':'').".png' onmouseover=\"popalt('".(($is_client)?__T('G_GDR_BUILDER_CLIENT_'):'')."{$elem["class_object"]}".(($nb_ranges>0)?__T('G_GDR_BUILDER_WITH_RANGE'):'')." from {$elem['sdp_label']}')\" width='16' height='16'/></div>
											";
									
									$warning = false;
									$warning_msg = '';
									// on verifie que la courbe n'est pas utilisée dans le order by 
									if ($graph['object_type']=='pie') {	// uniquement si on a un graph de type pie
										if ($graph['orderby'] == $elem['id']) {
											$warning = true;
											$warning_msg = __T('G_GDR_BUILDER_ELEMENT_USED_FOR_ORDER_OPTION');
										}
									}
									
									// on verifie que la data n'est pas utilisée par le GIS
									if (($graph['gis']==1) and ($graph['gis_based_on'] == $elem['id'])) {
										$warning = true;
										// 15:58 12/06/2009 GHX
										// Remplacement du "\n" par un "<br />" sinon erreur JS
										$warning_msg .= ($warning_msg == '' ? "" : "<br />").__T('G_GDR_BUILDER_ELEMENT_USED_FOR_GIS_OPTION');
									}
									
									// on verifie que la data n'est pas utilisée comme overnetwork_default_sortby
									// 11:28 28/08/2009 GHX
									// Modification overnetwork_default_orderby par default_orderby
									if ($graph['default_orderby']== $elem['id']) {
										$warning = true;
										// 15:58 12/06/2009 GHX
										// Remplacement du "\n" par un "<br />" sinon erreur JS
										$warning_msg .= ($warning_msg == '' ? "" : "<br />").__T('G_GDR_BUILDER_ELEMENT_USED_FOR_OVERNETWORK_DEFAULT_SORTBY');
									}
									
									// on vérifie si l'élément est utilisé comme tri overtime dans un ou des dashboard
									$query = " --- on recherche les noms des Dashboards dont sdd_sort_by_id={$elem["id"]}
										select page_name from sys_pauto_page_name where id_page IN
											(select sdd_id_page from sys_definition_dashboard where sdd_sort_by_id = '{$elem["id"]}')
										";
									$dashboards = $db->getall($query);
									if (sizeof($dashboards) > 0) {
										$warning = true;
										// 30/06/2009 MPR - Remplacement des "\n" par un "<br />" sinon erreur JS 
										$warning_msg .= "<br />".__T('G_GDR_BUILDER_ELEMENT_USED_IN_THE_FOLLOWING_DASHBOARDS');
										foreach ($dashboards as $dash)
											// 15:58 12/06/2009 GHX
											// Remplacement du "\n" par un "<br />" sinon erreur JS
											$warning_msg .= ($warning_msg == '' ? "" : "<br />")." - ".$dash["page_name"];
									}
									
									$warning_msg = trim($warning_msg);
			
									if ($warning) {
										echo "<div class='del'><img src='images/error.png' onmouseover=\"popalt('$warning_msg')\" width='16' height='16'/></div>";
									} else {
										if ($disabled=='') {
											// 11:35 30/01/2009 GHX
											// Ajout d'un deuxieme "_" pour séparer id du RAW/KPI
											/**
											*	08/07/2009 SPS
											*		- on supprime l'affichage du tooltip sur l'evenement onmouseover car il reste affiche lors d'une suppression (correction bug 10445)
											*/
											//echo "<div class='del' onclick=\"delete_element('gtm_element__{$elem['id']}');\"><img src='images/delete.png' onmouseover=\"popalt('".__T('G_GDR_BUILDER_DELETE')."')\" width='16' height='16'/></div>";
											echo "<div class='del' onclick=\"delete_element('gtm_element__{$elem['id']}');\"><img src='images/delete.png' alt='".__T('G_GDR_BUILDER_DELETE')."' width='16' height='16'/></div>";
										}
									}
									
									// on calcule les na levels en commun (axe1 et axe3)
									if (is_array($na_levels[$elem['family']]))
										$elem_na_levels = implode(', ',$na_levels[$elem['family']]);
									if (($na_levels_axe3[$elem['family']]) and ($elem_na_levels != ''))
										$elem_na_levels .= ', '; 
									if ($na_levels_axe3[$elem['family']])
										$elem_na_levels .= '<span class="axe3">'.implode(', ',$na_levels_axe3[$elem['family']]).'</span>';
									
									// 14/08/2009 GHX : Ajout de 2 divs supplémentaires class='name' &  class='type'
                                    // 28/01/2011 OJT : Correction d'un décalage du nom des RAWs/KPIs
									echo "
												<div class='info' onclick=\"get_data_properties('elem_prop__{$elem['id']}');\"><img src='images/application_edit_off.png' onmouseover=\"popalt('".__T('G_GDR_BUILDER_DETAILS')."')\"/></div>
												<div class='product'>{$elem['sdp_label']}</div>
                                                <div class='label'>{$realLabelName}</div>
												<div class='name' style='display:none'>{$elem['name']}</div>
												<div class='type' style='display:none'>{$elem['class_object']}</div>
											</div>
											<div class='na_levels'>$elem_na_levels</div>
											<div id='elem_prop__{$elem['id']}' class='properties'></div>
									</li>";
								}
							} else { ?>
								<style type='text/css'>
									#inside_gtm {display:none;}
									#nothing_inside {display:block;}
								</style>
						<?php } ?>
					</ul>
				</div>

				<div id="nothing_inside" class="help"><?php echo __T('G_GDR_BUILDER_HELP_CLICK_TO_ADD_RAWKPI')?></div>
				
				<style type="text/css" id='na_level_css'>div.na_levels {display:none;}</style>
				
				<?php if ($elements) { ?> 
					<script type="text/javascript">
						make_elements_sortable();
						get_na_levels_in_common();
						// 09:36 05/08/2009 GHX
						hasCumulatedBar();
						// 11:09 14/08/2009 GHX
						checkElementsWithSameName();
					</script><?php } ?>
				
		<?php } ?>
		
		<!-- boutons du bas : 	NEW	SHARE  COPY -->
		<br />
		
		<form action="graph.php" method="get" style="margin-left:2px;margin-right:2px;display:inline;">
			<input type="hidden" name="properties" value="open"/>
			<input type="submit" style="width: 70px;" class="bouton" value="<?php echo __T('G_GDR_BUILDER_NEW')?>" />
		</form>
	

		<?php if (($user_info['profile_type']=='user') and ($graph["droit"]=='client') and ($graph["id_user"]==$user_info["id_user"])) {
			if ($graph["share_it"] == 1) { ?>
				<!-- bouton UN-SHARE -->
				<form action="common_share.php" method="post" style="margin-right:2px;display:inline;">
					<input type="hidden" name="type" value="graph"/>
					<input type="hidden" name="id_page" value="<?php echo $id_page ?>"/>
					<input type="hidden" name="unshare" value="1"/>
					<input type="submit" style="width: 90px;" class="bouton" value="<?php echo __T('G_GDR_BUILDER_UNSHARE')?>"/>
				</form>
			<?php } else { ?>
				<!-- bouton SHARE -->
				<form action="common_share.php" method="post" style="margin-right:2px;display:inline;">
					<input type="hidden" name="type" value="graph"/>
					<input type="hidden" name="id_page" value="<?php echo $id_page ?>"/>
					<input type="submit" style="width: 70px;" class="bouton" value="<?php echo __T('G_GDR_BUILDER_SHARE')?>"/>
				</form>
			<?php } ?>
		<?php } ?>

		<!-- bouton COPY -->
		<?php if ($id_page != '0' && !empty($id_page)) { ?>
			<form action="graph_copy.php" method="post" style="margin-right:2px;display:inline;">
				<input type="hidden" name="id_page" value="<?php echo $id_page ?>"/>
				<input type="submit" style="width: 70px;" class="bouton" value="<?php echo __T('G_GDR_BUILDER_COPY')?>"/>
			</form>
		<?php } ?>

	</div>
</div>

<br clear="both"/>
<br/>
<style type="text/css" id="css_hide_if_pie">
.hide_if_pie {}
.hide_if_pie select optgroup{
	color: #C0C0C0;
}
</style>
<style type="text/css" id="css_hide_if_pie_and_single_kpi">
.hide_if_pie_and_single_kpi {}
</style>
<style type="text/css" id="css_hide_if_single_kpi">
.hide_if_single_kpi {}
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

// 18/11/2011 BBX
// BZ 21951 : resize auto de la fenêtre
window.onresize = function() { adaptLayoutToWindowHeight(); }

// on affiche ou cache les informations des courbes si le graphe est de type graph/pie
// 17/08/2010 OJT : Correction bz16864 pour DE Firefox, gestion des styles 'table-row'
if ($('object_type_graph').checked) {
	// type = graph
        // maj 09/07/2010 - MPR : Correction du BZ 16620
        // Modification inverse les valeurs doivent être block et non none
        // sinon au premier chargement d'un graphe de type 'graph', impossible de configurer la couleur des raw/kpi
	getStyleRule('css_hide_if_pie','.hide_if_pie').style.display = 'table-row';
	getStyleRule('css_hide_if_pie_and_single_kpi','.hide_if_pie_and_single_kpi').style.display = 'table-row';
	getStyleRule('css_hide_if_single_kpi','.hide_if_single_kpi').style.display = 'table-row';
}
else if($('object_type_single_kpi').checked){
 // Hide colors elements for single kpi mode
	getStyleRule('css_hide_if_pie','.hide_if_pie').style.display = 'table-row';
	getStyleRule('css_hide_if_pie_and_single_kpi','.hide_if_pie_and_single_kpi').style.display = 'none';
	getStyleRule('css_hide_if_single_kpi','.hide_if_single_kpi').style.display = 'none';
}
else {
	// type = pie
	getStyleRule('css_hide_if_pie','.hide_if_pie').style.display = 'none';
	getStyleRule('css_hide_if_pie_and_single_kpi','.hide_if_pie_and_single_kpi').style.display = 'none';
 	getStyleRule('css_hide_if_single_kpi','.hide_if_single_kpi').style.display = 'table-row';
}

// 14/09/2012 ACS DE Automatically select main family in Graph Builder
display_all_filters();
update_family_filter();


</script>

