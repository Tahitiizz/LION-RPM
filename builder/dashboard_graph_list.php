<?php
/**
 * @cb4100@
 * - Creation SLC	 29/10/2008
 *
 * Cette page affiche la colonne de gauche du graph builder : avec la liste des RAW et KPI
 *
 *
 * 29/01/2009 GHX
 *		- modification des requêtes SQL pour mettre id_user entre cote au niveau des inserts  [REFONTE CONTEXTE]
 * 23/09/2009 GHX
 *		- Correction du BZ 11698
 *			-> GTM => Graph
 *
 * 09/06/10 YNE/FJT : SINGLE KPI
 * 20/01/2011 OJT : Correction bz 20214 Ajout du filtre produit et d'informations sur le produit
 */

// 26/04/2011 OJT : Exclusion du Produit Blanc dans la liste des Dash
$productsInfos = ProductModel::getProducts( false );


// on va chercher tous les graphs
// 20/01/2011 OJT : Correction bz20214, gestion produit réference du graph
$query=" --- get list of graphs
		SELECT T1.id_page, 
			T1.page_name
			|| CASE WHEN T1.id_user IS NOT NULL AND T1.id_user <> '{$user_info['id_user']}' THEN 
					(SELECT ' ['||username||']' FROM users WHERE id_user=T1.id_user) 
					ELSE '' 
				END
			AS page_label, T1.share_it,
			T1.droit,T1.id_user,COUNT(T3.id_elem) AS nb_elem_in,
			CASE WHEN droit='customisateur' THEN 1
				ELSE CASE WHEN droit='client' AND id_user IS NULL THEN 2
				ELSE CASE WHEN droit='client' AND id_user IS NOT NULL AND id_user <> '{$user_info['id_user']}' THEN 3
				ELSE CASE WHEN droit='client' AND id_user IS NOT NULL AND id_user = '{$user_info['id_user']}' THEN 4
			END END END END AS optgroup_order,
			CASE WHEN (SELECT COUNT(DISTINCT id_product) FROM sys_pauto_config WHERE id_page = T1.id_page) > 1
            THEN 'multi product'
            ELSE (SELECT sdp_label FROM sys_definition_product
                    WHERE sdp_id = (SELECT DISTINCT id_product FROM sys_pauto_config WHERE id_page = T1.id_page LIMIT 1))
            END AS product,
			gi.object_type
		FROM sys_pauto_page_name T1
			LEFT JOIN sys_pauto_config T3 ON T1.id_page = T3.id_page
			JOIN graph_information as gi ON T1.id_page = gi.id_page 
		WHERE 
				T1.page_type='gtm' 
		GROUP BY T1.id_page,page_label,droit,id_user,T1.share_it,gi.object_type
		ORDER BY optgroup_order,page_label
";
$graphs = $db->getall($query);

$graphs_dump = "<table><tr>";
if( count( $graphs ) > 0 ) // Test si au moins un résultat est retourné
{
    foreach ($graphs[0] as $col => $val)
    {
	    $graphs_dump .= "<th>$col</th>";
    }
}
$graphs_dump .= "</tr>";
foreach ($graphs as $graph) {
	$graphs_dump .= "<tr>";
	foreach ($graph as $val) {
		$graphs_dump .= "<td>$val</td>";
	}
	$graphs_dump .= "</tr>";
}
$graphs_dump .= "</table>";

?>


<!-- FILTER -->
<div id="list_filter">
	<h3>
		<a href="#display all filters" onclick="display_all_filters();return false;">
            <img src='images/arrow_right.png' id='display_all_filters_img' alt="+" title='Open/Close filters' width='16' height='16' align='right' />
        </a>
		Graph Filter
	</h3>
	<?php //11/09/2014 - FGD - Bug 43793 - [REC][CB 5.3.3.02][TC #TA-56730][IE 11 Compatibility] Dashboards/Graphs/Alarms are NOT displayed anymore when clicking "x" at the end of input field. ?>
	<input id="filter" onmouseup="setTimeout(function(){<?php foreach ($level_labels as $i => $label) { echo "filter_list('li_$i');" ;} ?>}, 0)" onkeyup="<?php foreach ($level_labels as $i => $label) { echo "filter_list('li_$i');" ;} ?>" />
    <div id='all_filters' style='display:none;'>
        <div>
	<!-- filter checkboxes -->
        <div id="dashTypeEltFilter" class="dashFilters" style="margin-top:8px;">
            <strong style="font-size:11px;"><?php echo __T('G_GDR_BUILDER_TYPE_OF_ELEMENTS')?></strong>
            <?php
                foreach ( $level_labels as $i => $label )
                {
            ?>
                    <br />
                    &nbsp;&nbsp;
                    <input
                        type='checkbox' name='<?php echo "show_$i"; ?>' id='<?php echo "show_$i"; ?>' checked='checked'
                        onclick="setTimeout('check_show(\'<?php echo "show_$i"; ?>\',\'<?php echo "li_$i"; ?>\')',10);return true;"
                    />
                    <label for='<?php echo "show_$i"; ?>'><?php echo $label." Graphs"; ?></label>
            <?php
		}
	?>
        </div>
	
        <!-- product filter checkboxes -->
        <div id="productsListFilter" class="dashFilters" style="margin-top:5px;<?php if (count($productsInfos)==1) echo "display:none;"; ?>">
            <strong style="font-size:11px;"><?php echo __T('G_GDR_BUILDER_PRODUCT_LIST')?></strong>
            <?php
                foreach ( $productsInfos as $prod )
                {
            ?>
                 <br />
                 &nbsp;&nbsp;
                 <input
                        type="checkbox" name="show_sdp_<?php echo $prod['sdp_id'] ?>" id="show_sdp_<?php echo $prod['sdp_id'] ?>"
                        checked="checked" onclick="setTimeout('check_show(\'show_sdp_<?php echo $prod['sdp_id'] ?>\',\'\')',10);return true;"
                 />
                 <label for="show_sdp_<?php echo $prod['sdp_id'] ?>"><?php echo $prod['sdp_label']?></label>
            <?php
                }
            ?>
</div>
        </div>
    </div>
</div>

<!-- liste des GTMs -->
<div id="gtm_elements_list" style="height:400px;overflow:scroll;">
	<h3>List of Graphs</h3>
	<ul>
		<?php
        foreach ($level_labels as $i => $label)
        {
			echo "<li id='li_$i'><a class='js_20090116'><img src='images/arrow_down.png' alt='open' width='16' height='16' align='absmiddle' /> $label Graphs</a><ul>";
			foreach ($graphs as $graph)
            {
			if ($graph['optgroup_order'] == $i)
                {
                    // 28/01/2011 BBX
                    // Affichage des graphes uniquement sur les produits désactivés
                    // BZ 20414
                    // On réaffiche les éléments basés sur des produits désactivés
                    // Mais ils ne seront pas sélectionnables
                    // BZ 20498
                    $disabledProduct = false;
                    $gtmModel = new GTMModel($graph['id_page']);
                    foreach($gtmModel->getGTMProducts() as $p) {
                        if(!ProductModel::isActive($p)) {
                            $disabledProduct = true;
                        }
                    }

						//if (($i!=3) or ($graph['share_it']==1))	// user graphs have to be shared to show up
                    if ( $graph['nb_elem_in'] > 0 ) // un graph vide ne doit pas apparaitre
                    {
 						// 24/03/09 YNE
 						// add single KPI icone
 						switch($graph['object_type']){
 							case 'graph' : $image = "bar"; break;
 							case 'pie3D' : $image = "pie"; break;
 							case 'singleKPI' : $image = "single"; break;
 							default : $image = "bar";
 						}

                        // Get product Id
                        $idProduct = 0; // In case of multi product, 0 will be used
                        $u = 0;
                        while( $u < count( $productsInfos ) && $productsInfos[$u]['sdp_label'] != $graph['product'] )
                        {
                            $u++;
 					    }
                        if( $u < count( $productsInfos ) )
                        {
                            $idProduct = $productsInfos[$u]['sdp_id'];
                        }
                        // Affichage standard
                        if(!$disabledProduct)
                        {
                            echo "<li id='gtmlist__{$graph['id_page']}__{$idProduct}' class='prod_{$idProduct}'>".
                                   "<nobr>".
                                        "<img src='images/chart_{$image}_{$i}.png' alt='GTM' width='16' height='16' align='absmiddle' vspace='1'/>".
                                        "&nbsp;{$graph['page_label']} ({$graph['product']})".
                                    "</nobr>".
                                 "</li>";
                        }
                        // On réaffiche les éléments basés sur des produits désactivés
                        // Mais ils ne seront pas sélectionnables
                        // BZ 20498
                        else
                        {
                            echo "<li id='disabled_element__{$graph['id_page']}__{$idProduct}' class='prod_{$idProduct} disabled_element'>".
                                   "<nobr>".
                                        "<img src='images/chart_{$image}_{$i}.png' alt='GTM' width='16' height='16' align='absmiddle' vspace='1'/>".
                                        "&nbsp;{$graph['page_label']} ({$graph['product']} is disabled)".
                                    "</nobr>".
                                 "</li>";
                        }
                    }
                }
            }
			echo "</ul></li>";
		}
		?>
	</ul>
</div>

<?php unset($graphs); ?>

<script type="text/javascript" src="js/left_list_manager.js"></script>
<script type="text/javascript">
// on accroche l'action show_hide_nextSibling à tous les titres de listes
var groups = $$('a.js_20090116');
nb_groups = groups.length;
for (i=0; i<nb_groups; i++)	groups[i].onclick	= show_hide_nextSibling;

var elems_lists = new Array();
var elems_nb = new Array();

<?php 
foreach ($level_labels as $i => $label) {
	echo "\n check_show('show_$i','li_$i');";
	echo "\n elems_lists['li_$i'] = $('li_$i').getElementsByTagName('LI');";
	echo "\n elems_nb['li_$i'] = elems_lists['li_$i'].length;";
	// on associe l'ajout d'un element au dashboard au click sur un graph de la liste de gauche
	echo "\n for (i=0; i<elems_nb['li_$i']; i++)	elems_lists['li_$i'][i].onclick	= add_element_to_dash;";
}
?>
</script>

<style type="text/css" id="inline_products_css">
<?php
// 17/08/2010 NSE DE Firefox bz 16924 on remplace UL UL LI par ul li ul li et on homogénéise la casse.
// Sous IE, il faut que les balises html soient en majuscules
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

foreach ($productsInfos as $prod) {
	echo $gtm_elements_list_family.$prod['sdp_id'].' {display:inherit;}';
	echo $gtm_elements_list_prod.$prod['sdp_id'].' {display:inherit;}';
}
?>
</style>
