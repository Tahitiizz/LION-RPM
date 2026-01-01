<?php
/*
 * @cb50400
 *
 * 17/08/2010 NSE DE Firefox bz 16925 : filtre par famille ko : affichage infini de 'updating display'
 * 13/09/2010 NSE bz 17845 : filtre par prod ko dans graph builder
 * 20/01/2011 OJT : Correction bz 20214 Ajout du filtre produit et d'informations sur le produit
 * 14/09/2012 ACS DE Automatically select main family in Graph Builder
 */
?><?php
/*
	04/11/2009 GHX
		- Correction du BZ 12511 [CB 5.0][Graph Builder] : si 2 KPI ont le même nom seul, un seul est visible dans la liste
		- 30/11/2009 BBX : on ne filtre plus sur new_field afin d'afficher tous les kpi. BZ 13039
		
	09/06/10 YNE/FJT : SINGLE KPI
*/ 
?>
<?php
/**
*	@cb4100@
*	- Creation SLC	 29/10/2008
*
*	Cette page affiche la colonne de gauche du graph builder : avec la liste des RAW et KPI
*/

// 14/09/2012 ACS DE Automatically select main family in Graph Builder
require_once REP_PHYSIQUE_NIVEAU_0 . "php/edw_function_family.php";

// cette requête selectionne tous les raw counters dans une base
$query_get_raw = " --- get list of all RAW counters
	select distinct on (object_libelle,edw_field_name) sfr.id_ligne as id,
		'counter' as object_class,
		sfr.id_ligne as object_id,
		sfr.id_ligne as object_id_elem_in,
		0 as object_id_parent,
		case when edw_field_name_label is not null then replace(edw_field_name_label,':',' ') else replace(edw_field_name,':',' ') end as object_libelle,
		1 as object_niveau,
		2 as object_position,
		count(sdrs.id_element) as nb_ranges,
		'%d' as sdp_id,
		'%s' as sdp_label,
		sdgt.family,
		sdc.family_label
	from sys_field_reference as sfr
		left outer join sys_data_range_style as sdrs on sfr.id_ligne=sdrs.id_element and sdrs.data_type='counter'
		left outer join sys_definition_group_table as  sdgt on sdgt.edw_group_table = sfr.edw_group_table
		left outer join sys_definition_categorie as  sdc on sdc.family = sdgt.family
	where sfr.on_off=1
		and new_field<>1
		and sfr.visible=1
	group by sfr.id_ligne,sfr.edw_field_name_label,sfr.edw_field_name,sdgt.family,sdc.family_label
	order by object_libelle, edw_field_name asc
	";

// cette requête selectionne tous les kpi
// 11:00 04/11/2009 GHX
// Correction du BZ 12511
// Ajout de "sdk.id_ligne" dans le DISTINCT ON et dans le GROUP BY "sdk.id_ligne"
// 30/11/2009 BBX : on ne filtre plus sur new_field afin d'afficher tous les kpi. BZ 13039
$query_get_kpi = " --- get list of all KPIs
	select distinct on (object_libelle,kpi_name, sdk.id_ligne) sdk.id_ligne as id,
		'kpi' as object_class,
		value_type,
		sdk.id_ligne as object_id,
		sdk.id_ligne as object_id_elem_in,
		0 as object_id_parent,
		case when kpi_label is not null then replace(kpi_label, ':',' ') else replace(kpi_name,':',' ') end as object_libelle,
		1 as object_niveau,
		2 as object_position,
		count(sdrs.id_element) as nb_ranges,
		'%d' as sdp_id,
		'%s' as sdp_label,
		sdgt.family,
		sdc.family_label
	from sys_definition_kpi as sdk
		left outer join sys_data_range_style as sdrs on sdk.id_ligne=sdrs.id_element and sdrs.data_type='kpi'  
		left outer join sys_definition_group_table as  sdgt on sdgt.edw_group_table = sdk.edw_group_table
		left outer join sys_definition_categorie as  sdc on sdc.family = sdgt.family
	where sdk.on_off=1
		--and new_field=0
		and sdk.visible=1
	group by sdk.id_ligne,sdk.kpi_label,sdk.kpi_name,sdk.value_type,sdgt.family,sdc.family_label
	order by object_libelle, kpi_name asc, sdk.id_ligne
	";

// 26/04/2011 OJT : Exclusion du Produit Blanc dans la liste des éléments
$allProducts = ProductModel::getProducts( false );

$products = getProductInformations();
/* exemple :

$product[1] =  array(12) {
	["sdp_id"]			=> "1"
	["sdp_label"]		=> "IU 1 label"
	["sdp_ip_address"]	=> "10.49.0.3"
	["sdp_directory"]	=> "cb41000_iu40014_dev1"
	["sdp_db_name"]	=> "cb41000_iu40014_dev1"
	["sdp_db_port"]	=> "5432"
	["sdp_db_login"]	=> "postgres"
	["sdp_db_password"]	=> NULL
	["sdp_ssh_user"]	=> NULL
	["sdp_ssh_password"]=> NULL
	["sdp_on_off"]		=> "1"
	["sdp_master"]		=> "1"
}
*/


// les raws seront tous mis dans le tableau $raws
$raws = array();

// les kpis seront tous mis dans le tableau $kpis
$kpis = array();

// le tableau des gis activés
$activated_gis = array();

// on boucle sur tous les produits
foreach ($allProducts as $prod) {
    // On se connecte sur la base du produit (en testant si la connexion est valide, bz20995)
    $db_temp = DataBase::getConnection($prod['sdp_id']);
    if( $db_temp->getCnx() )
    {
		// on prepare les sql
		$query_raw	= sprintf($query_get_raw,$prod['sdp_id'],$prod['sdp_label']);
		$query_kpi	= sprintf($query_get_kpi,$prod['sdp_id'],$prod['sdp_label']);
		// on fait les requêtes
		$raws	    = array_merge($raws,$db_temp->getall($query_raw));
		$kpis		= array_merge($kpis,$db_temp->getall($query_kpi));
		// on profite qu'on a une cnx a la db du produit pour regarder si son gis est activé
		$activated_gis[$prod['sdp_label']] = intval($db_temp->getone("select value from sys_global_parameters where parameters='gis' "));
	
		// on ferme la connexion à la db
		$db_temp->close();
    }
	unset($db_temp);
}

// on trie les raws / kpis en fonction du libelle $raws[i]['object_libelle']
// la fonction de comparaison entre la row1 et la row2 de l'array :
function compare_labels($r1,$r2) {
	return strcasecmp($r1['object_libelle'],$r2['object_libelle']);
}
usort($raws,"compare_labels");
usort($kpis,"compare_labels");

// on compose le tableau des familles, de la forme $families[product_label][family] = family_label
$families = array();
// look for all families in the raw counters
foreach ($raws as $raw) {
	// we make sure the $families[product_label] array exists
	if (!array_key_exists($raw['sdp_label'],$families))
		$families[$raw['sdp_label']] = array();
	// we make sure the $families[product_label][family] value exists
	if (!array_key_exists($raw['family'],$families[$raw['sdp_label']]))
		$families[$raw['sdp_label']][$raw['family']] = $raw['family_label'];
}
// look for all families in the kpis
foreach ($kpis as $kpi) {
	// we make sure the $families[product_label] array exists
	if (!array_key_exists($kpi['sdp_label'],$families))
		$families[$kpi['sdp_label']] = array();
	// we make sure the $families[product_label][family] value exists
	if (!array_key_exists($kpi['family'],$families[$kpi['sdp_label']]))
		$families[$kpi['sdp_label']][$kpi['family']] = $kpi['family_label'];
}
// on trie les familles par ordre alpha de label
foreach ($families as $fam_name => $fam) {
	asort($fam);
	$families[$fam_name] = $fam;
}
// on trie les produits par order alpha de produit
ksort($families);

?>


<!-- FILTER -->
<div id="list_filter">
	<h3>
		<a href="#display all filters" onclick="display_all_filters();return false;">
            <img src='images/arrow_right.png' id='display_all_filters_img' alt="+" title='Open/Close filters' width='16' height='16' align='right' />
        </a>
		<?php echo __T('G_GDR_BUILDER_RAW_COUNTERS_AND_KPI_FILTER')?>
	</h3>
	<?php //11/09/2014 - FGD - Bug 43793 - [REC][CB 5.3.3.02][TC #TA-56730][IE 11 Compatibility] Dashboards/Graphs/Alarms are NOT displayed anymore when clicking "x" at the end of input field. ?>
	<input id="filter" onmouseup="setTimeout(function(){filter_list('li_raws');filter_list('li_kpis');}, 0)" onkeyup="filter_list('li_raws');filter_list('li_kpis');" />
	<div id='all_filters' style='display:none;'>
        <div>
		<!-- raw / kpi filter checkboxes -->
        <div class="product_filter">
		<strong style="font-size:11px;"><?php echo __T('G_GDR_BUILDER_TYPE_OF_ELEMENTS')?></strong>
		<br />&nbsp;&nbsp;<input type="checkbox" name="show_raw" id="show_raw" checked="checked" onclick="setTimeout('check_show(\'show_raw\',\'li_raws\')',10);return true;" />
			<label for="show_raw"><?php echo __T('G_GDR_BUILDER_RAW_COUNTERS')?></label>
		<br />&nbsp;&nbsp;<input type="checkbox" name="show_kpi" id="show_kpi" checked="checked" onclick="setTimeout('check_show(\'show_kpi\',\'li_kpis\')',10);return true" />
			<label for="show_kpi"><?php echo __T('G_GDR_BUILDER_KPI')?></label>
        </div>
		
		<!-- product filter checkboxes -->
		<div id="productsListFilter" class="product_filter" style="margin-top:5px;<?php if (count($allProducts)==1) echo "display:none;"; ?>">
			<strong style="font-size:11px;"><?php echo __T('G_GDR_BUILDER_PRODUCT_LIST')?></strong>
			<?php foreach ($allProducts as $prod) { ?>
				<br />&nbsp;&nbsp;<input type="checkbox" name="show_sdp_<?php echo $prod['sdp_id'] ?>" id="show_sdp_<?php echo $prod['sdp_id'] ?>" checked="checked" onclick="setTimeout('check_show(\'show_sdp_<?php echo $prod['sdp_id'] ?>\',\'\')',10);return true" />
					<label for="show_sdp_<?php echo $prod['sdp_id'] ?>"><?php echo $prod['sdp_label']?></label>
			<?php } ?>
		</div>
		
		<!-- Type of display -->
		<div class="product_filter" style="margin-top:5px;">
            <!-- 14/09/2012 ACS DE Automatically select main family in Graph Builder -->
			<strong style="font-size:11px;"><?php echo __T('G_GDR_BUILDER_TYPE_OF_DISPLAY')?></strong>
			<br />&nbsp;&nbsp;<input type="radio" name="display_type" value="no sort" id="display_type_no_sort" onclick="setTimeout('update_type_of_display()',1);return true;" />
				<label for="display_type_no_sort"><?php echo __T('G_GDR_BUILDER_NO_SORT')?></label>
			<br />&nbsp;&nbsp;<input type="radio" name="display_type" value="by family" id="display_type_by_family" checked="checked" onclick="setTimeout('update_type_of_display()',1);return true;" />
				<label for="display_type_by_family"><?php echo __T('G_GDR_BUILDER_BY_FAMILY')?></label>

				<select id="family_list" onchange="update_family_filter();" style="display:none;">
					<?php
                    // 14/09/2012 ACS DE Automatically select main family in Graph Builder
                    $idMasterTopo = ProductModel::getIdMasterTopo();
					$mainFamily = get_main_family($idMasterTopo);
					foreach ($families as $product_label => $product_arr) {
						if (is_array($product_arr)) {
							echo "\n<optgroup label='$product_label'>";
                                foreach ($product_arr as $fam=>$fam_label){
                                    $fam_selected = "";
                                    if($fam == $mainFamily){
                                        $fam_selected = " selected ";
                                    } 
                                    echo "\n\t<option value='fam_".preg_replace('/[^a-zA-Z0-9]/', '', $product_label)."_$fam' $fam_selected>$fam_label</option>";
                                }
							echo "\n</optgroup>";
						}
					}
					?>
				</select>
				<div id='updating_families' align='center' style='color:red;display:none;'><img src='images/ajax-loader.gif' alt='wait...' width='16' height='16' align='absmiddle'/> Updating display</div>
			</div>
		</div>
	</div>
</div>


<!-- liste des RAW / KPI -->
<div id="gtm_elements_list" style="height:400px;overflow:scroll;">
	<h3><?php echo __T('G_GDR_BUILDER_GTM_ELEMENTS')?></h3>
	<ul>
		<li id="li_raws">
			<a class='js_20090116'><img src='images/arrow_down.png' alt='open' width='16' height='16' align='absmiddle' /> <?php echo __T('G_GDR_BUILDER_RAW_COUNTERS')?></a>
			<ul>
			<?php 
			foreach ($raws as $raw){
                // Affichage standard
                if(ProductModel::isActive($raw['sdp_id']))
                {
					echo "\n<li id='list_counter__{$raw['id']}__{$raw['sdp_id']}' class='prod_{$raw['sdp_id']} family_".preg_replace('/[^a-zA-Z0-9]/', '', $raw['sdp_label'])."_{$raw['family']}'>".
							 "<nobr>".
								 "<img src='images/brick_counter".(($raw["nb_ranges"]>0)?'_ranged':'').".png' alt='Raw counter".(($raw["nb_ranges"]>0)?' with range':'')." from {$raw['sdp_label']}' width='16' height='16' align='absmiddle' vspace='1'/>".
								 "&nbsp;{$raw['object_libelle']} ({$raw['sdp_label']})".
							 "</nobr>".
						 "</li>";	
				}
                // On réaffiche les éléments basés sur des produits désactivés
                // Mais ils ne seront pas sélectionnables
                // BZ 20498
                else
                {
                	echo "\n<li id='disabled_element__{$raw['id']}__{$raw['sdp_id']}' class='prod_{$raw['sdp_id']} disabled_element family_".preg_replace('/[^a-zA-Z0-9]/', '', $raw['sdp_label'])."_{$raw['family']}'>".
                            "<nobr>".
                              "<img src='images/brick_counter".(($raw["nb_ranges"]>0)?'_ranged':'').".png' alt='Raw counter".(($raw["nb_ranges"]>0)?' with range':'')." from {$raw['sdp_label']}' width='16' height='16' align='absmiddle' vspace='1'/>".
                              "&nbsp;{$raw['object_libelle']} ({$raw['sdp_label']} is disabled)".
                            "</nobr>".
                          "</li>";
                }
			}
			?>
			</ul>
		</li>
		
		<li id="li_kpis">
			<a class='js_20090116'><img src='images/arrow_down.png' alt='open' width='16' height='16' align='absmiddle' /> <?php echo __T('G_GDR_BUILDER_KPIS')?></a>
			<ul>
			<?php 
			foreach ($kpis as $kpi){
            	// Affichage standard
                if(ProductModel::isActive($kpi['sdp_id']))
                {
					echo "\n<li id='list_kpi__{$kpi['id']}__{$kpi['sdp_id']}' class='prod_{$kpi['sdp_id']} family_".preg_replace('/[^a-zA-Z0-9]/', '', $kpi['sdp_label'])."_{$kpi['family']}'>".
							 "<nobr>".
								 "<img src='images/brick_kpi".(($kpi['value_type']=='client')?'_client':'').(($kpi["nb_ranges"]>0)?'_ranged':'').".png' alt='".(($kpi['value_type']=='client')?'client ':'')."KPI".(($kpi["nb_ranges"]>0)?' with range':'')." from {$kpi['sdp_label']}' width='16' height='16' align='absmiddle' vspace='1'/>".
								 "&nbsp;{$kpi['object_libelle']} ({$kpi['sdp_label']})".
							 "</nobr>".
						 "</li>";	
				}
                // On réaffiche les éléments basés sur des produits désactivés
                // Mais ils ne seront pas sélectionnables
                // BZ 20498
                else
                {
					echo "\n<li id='disabled_element__{$kpi['id']}__{$kpi['sdp_id']}' class='prod_{$kpi['sdp_id']} disabled_element family_".preg_replace('/[^a-zA-Z0-9]/', '', $kpi['sdp_label'])."_{$kpi['family']}'>".
                              "<nobr>".
                                 "<img src='images/brick_kpi".(($kpi['value_type']=='client')?'_client':'').(($kpi["nb_ranges"]>0)?'_ranged':'').".png' alt='".(($kpi['value_type']=='client')?'client ':'')."KPI".(($kpi["nb_ranges"]>0)?' with range':'')." from {$kpi['sdp_label']}' width='16' height='16' align='absmiddle' vspace='1'/>".
                                 "&nbsp;{$kpi['object_libelle']} ({$kpi['sdp_label']} is disabled)".
                              "</nobr>".
                           "</li>";
                }
			}
			?>
			</ul>
		</li>
	</ul>
</div>






<style type="text/css" id="inline_products_css">
<?php
// 17/08/2010 NSE DE Firefox bz 16924 on remplace UL UL LI par ul li ul li et on homogénéise la casse.
//Sous IE, il faut que les balises html soient en majuscules
// 13/09/2010 NSE bz 17845 : ajout de prod_
// 06/10/2014 - FGD - Bug 43757 - [REC][CB 5.3.3.02][TC #TA-56775][IE 11 Compatibility] Missing family's combox in Graph builder GUI
//Added an IE detection method for IE11
if(isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'],'MSIE') || strpos($_SERVER['HTTP_USER_AGENT'],'Trident/7.0; rv:11.0'))){
    $gtm_elements_list_family="#gtm_elements_list UL LI UL LI.family_";
    $gtm_elements_list_prod="#gtm_elements_list UL LI UL LI.prod_";
}
else{
    $gtm_elements_list_family="#gtm_elements_list ul li ul li.family_";
    $gtm_elements_list_prod="#gtm_elements_list ul li ul li.prod_";
}

foreach ($allProducts as $prod) {
	echo $gtm_elements_list_family.$prod['sdp_id'].' {display:inherit;}';
	echo $gtm_elements_list_prod.$prod['sdp_id'].' {display:inherit;}';
}
?>
</style>


<style type="text/css">
#family_list {	margin:0 0 0 30px;	font-size:10px; }
</style>


<!-- on instantie les règles CSS pour pouvoir les modifier ensuite -->
<style type="text/css" id="family_list_css">
<?php	// 17/08/2010 NSE DE Firefox bz 16925
    foreach ($families as $product_label => $product_arr)
        if (is_array($product_arr))
            foreach ($product_arr as $fam => $fam_label)
                echo $gtm_elements_list_family.preg_replace('/[^a-zA-Z0-9]/', '', $product_label)."_$fam {}\n";	?>
</style>


<script type="text/javascript">

// lorsque l'on change le menu "Type of display >> By family" cette fonction est appelée pour effacer tous les raw/kpis
function hide_all_families() {
<?php	// 17/08/2010 NSE DE Firefox bz 16925
    foreach ($families as $product_label => $product_arr)
        if (is_array($product_arr))
            foreach ($product_arr as $fam => $fam_label)
                echo "\n	getStyleRule('family_list_css','$gtm_elements_list_family".preg_replace('/[^a-zA-Z0-9]/', '', $product_label)."_$fam').style.display = 'none';";
?>
}

// quand on repasse en "Type of display >> no sort" on affiche toutes les familles
function show_all_families() {
<?php	// 17/08/2010 NSE DE Firefox bz 16925
    foreach ($families as $product_label => $product_arr)
        if (is_array($product_arr))
            foreach ($product_arr as $fam => $fam_label)
                    echo "\n	getStyleRule('family_list_css','$gtm_elements_list_family".preg_replace('/[^a-zA-Z0-9]/', '', $product_label)."_$fam').style.display = '';";
?>

	$('updating_families').style.display = 'none';	// we hide waiting icon
}


// fonction appelée lorsque l'on change le menu "Type of display >> By family"
function update_family_filter()
{
	// on cache toutes les familles
	hide_all_families();
	
	// on affiche que celle choisie dans le selecteur
	var selectedFam = $F('family_list');

    // Test si des familles existent dans la liste déroulante
    if( selectedFam != null )
    {
        // 17/08/2010 NSE DE Firefox bz 16925
        selectedFam = '<?=$gtm_elements_list_family?>'+selectedFam.slice(4);
        getStyleRule('family_list_css',selectedFam).style.display = '';
        $('family_list').style.display = 'block';
    }
    $('updating_families').style.display = 'none';	// we hide waiting icon
}

// action appelée quand on clique sur "Type of display >> By family"
function update_type_of_display() {
	$('updating_families').style.display = 'block';	// we show waiting icon
	if ($('display_type_no_sort').checked) {
		// no sort
		$('family_list').style.display = 'none';
		setTimeout('show_all_families();',100);
	} else {
		// by family
		setTimeout('update_family_filter();',100);
	}
}


</script>


<script type="text/javascript" src="js/left_list_manager.js"></script>

<script type="text/javascript">

// on accroche l'action show_hide_nextSibling à tous les titres de listes
var groups = $$('a.js_20090116');
nb_groups = groups.length;
for (i=0; i<nb_groups; i++)	groups[i].onclick	= show_hide_nextSibling;

// initiate
check_show('show_raw','li_raws');
check_show('show_kpi','li_kpis');

// get list of all raws and kpis
var elems_lists = new Array();
elems_lists['li_raws'] = $('li_raws').getElementsByTagName('LI');
elems_lists['li_kpis'] = $('li_kpis').getElementsByTagName('LI');

var elems_nb = new Array();
elems_nb['li_raws'] = elems_lists['li_raws'].length;
elems_nb['li_kpis'] = elems_lists['li_kpis'].length;

// on associe l'ajout d'un element au graph au click sur un raw/kpi de la liste de gauche
for (i=0; i<elems_nb['li_raws']; i++)	elems_lists['li_raws'][i].onclick	= add_element_to_GTM;
for (i=0; i<elems_nb['li_kpis']; i++)	elems_lists['li_kpis'][i].onclick	= add_element_to_GTM;

// tableau des gis activés
var activated_gis = new Array();
<?php
foreach ($activated_gis as $gis_sdp_label => $on_off)
	echo "\n	activated_gis[\"$gis_sdp_label\"] = $on_off; ";

?>


</script>

