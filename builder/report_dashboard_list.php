<?php
/*
 * @cb50400
 *
 * 16/08/2010 NSE DE Firefox bz 16924 : filtre par famille ko : affichage infini de 'updating display'
 * 20/01/2011 OJT : Correction bz 20214 Ajout du filtre produit et d'informations sur le produit
 */
?><?php
/**
*	@cb4100@
*	- Creation SLC	 12/11/2008
*
*	Cette page affiche la colonne de gauche du report builder : avec la liste des dashboards et des alarmes
*
*	05/02/2009 GHX
*		- ajout d'un deuxieme "_" dans les id (html) [REFONTE CONTEXTE]
*	08/07/2009 SPS
*		- amelioration du style de la zone de filtre (correction bug 9935)
 *      28/09/2010 NSE bz 14937 et support VideoTron : id_product non transmis pour les Dash alors qu'il est attendu pour inseertion dans sys_pauto_config
 *      20/10/2010 NSE bz 18635 : ajout de id_product pour les dash admin et user
*
*/

// CAST AUTO PG 9.1
// 18/05/2011 BBX
// on va chercher tous les dashboards
// 28/09/2010 NSE bz 14937 et support VideoTron : on ajoute l'id_product
// 20/01/2011 OJT : Correction bz20214, gestion produit réferences du dashboard
$query=" --- get list of Dashboards
	SELECT spc.id_product,sppn.id_page,
		sppn.page_name
		|| CASE WHEN sppn.id_user IS NOT NULL THEN
				(SELECT ' ['||username||']' FROM users WHERE id_user=sppn.id_user)
			ELSE ''
			END
		AS page_label, sppn.share_it,
		sppn.droit,sppn.id_user,COUNT(spc.id_elem) AS nb_elem_in,
		CASE WHEN droit='customisateur' THEN 1
			ELSE CASE WHEN droit='client' AND id_user IS NULL THEN 2
			ELSE CASE WHEN droit='client' AND id_user IS NOT NULL THEN 3
		END END END AS optgroup_order,
		CASE WHEN (SELECT COUNT(DISTINCT id_product) FROM sys_pauto_config WHERE id_page IN (SELECT id_elem FROM sys_pauto_config WHERE id_page = sppn.id_page)) > 1
		    THEN 'multi product'
		    ELSE (SELECT sdp_label || '|s|' || sdp_id::text FROM sys_definition_product
			WHERE sdp_id = (SELECT DISTINCT id_product FROM sys_pauto_config WHERE id_page IN (SELECT id_elem FROM sys_pauto_config WHERE id_page = sppn.id_page LIMIT 1 )))
		END AS product_ref

	FROM sys_pauto_page_name as sppn LEFT JOIN sys_pauto_config as spc ON sppn.id_page = spc.id_page
	WHERE sppn.page_type='page'
	GROUP BY sppn.id_page,page_label,droit,id_user,sppn.share_it,spc.id_product
	ORDER BY optgroup_order,page_label
";
$dashboards = $db->getall($query);

// 26/04/2011 OJT : Exclusion du Produit Blanc dans la liste des Dash
$allProducts = ProductModel::getProducts( false );

// on va chercher les alarmes
$products = getProductInformations();

$static_alarms	= array();
$dyn_alarms	= array();
$top_alarms	= array();

// on va chercher les alarmes statiques
$query_static = " --- we get the static alarms
	SELECT DISTINCT ON (sda.alarm_id) sda.alarm_id, sda.alarm_name,'%d' as sdp_id,'%s' as sdp_label,sda.family,sdc.family_label
	FROM sys_definition_alarm_static as sda
		left outer join sys_definition_categorie as  sdc on sdc.family = sda.family
";

// on va chercher les alarmes dynamiques
$query_dyn = " --- we get the dynamic alarms
	SELECT DISTINCT ON (sda.alarm_id) sda.alarm_id, sda.alarm_name,'%d' as sdp_id,'%s' as sdp_label,sda.family,sdc.family_label
	FROM sys_definition_alarm_dynamic as sda
		left outer join sys_definition_categorie as  sdc on sdc.family = sda.family
";

// on va chercher les alarmes dynamiques
$query_top = " --- we get the dynamic alarms
	SELECT DISTINCT ON (sda.alarm_id) sda.alarm_id, sda.alarm_name,'%d' as sdp_id,'%s' as sdp_label,sda.family,sdc.family_label
	FROM sys_definition_alarm_top_worst as sda
		left outer join sys_definition_categorie as  sdc on sdc.family = sda.family
";

// on boucle sur tous les produits
foreach ($products as $prod) {
	// on se connecte sur la base du produit
        // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
	$db_temp = Database::getConnection($prod['sdp_id']);
	// on fait les requêtes
	$static_alarms	= array_merge($static_alarms,$db_temp->getall(sprintf($query_static,$prod['sdp_id'],$prod['sdp_label'])));
	$dyn_alarms	= array_merge($dyn_alarms,$db_temp->getall(sprintf($query_dyn,$prod['sdp_id'],$prod['sdp_label'])));
	$top_alarms	= array_merge($top_alarms,$db_temp->getall(sprintf($query_top,$prod['sdp_id'],$prod['sdp_label'])));
	// on ferme la connexion à la db
	$db_temp->close();
	unset($db_temp);
}

// on trie les alarmes en fonction du libelle $static_alarms[i]['alarm_name']
// la fonction de comparaison entre la row1 et la row2 de l'array :
function compare_names($r1,$r2) {
	return strcasecmp($r1['alarm_name'],$r2['alarm_name']);
}
usort($static_alarms,"compare_names");
usort($dyn_alarms,"compare_names");
usort($top_alarms,"compare_names");

// on compose le tableau des familles, de la forme $families[product_label][family] = family_label
$families = array();
// look for all families in the static alarms
foreach ($static_alarms as $alarm) {
	// we make sure the $families[product_label] array exists
	if (!array_key_exists($alarm['sdp_label'],$families))
		$families[$alarm['sdp_label']] = array();
	// we make sure the $families[product_label][family] value exists
	if (!array_key_exists($alarm['family'],$families[$alarm['sdp_label']]))
		$families[$alarm['sdp_label']][$alarm['family']] = $alarm['family_label'];
}
// look for all families in the dynamic alarms
foreach ($dyn_alarms as $alarm) {
	// we make sure the $families[product_label] array exists
	if (!array_key_exists($alarm['sdp_label'],$families))
		$families[$alarm['sdp_label']] = array();
	// we make sure the $families[product_label][family] value exists
	if (!array_key_exists($alarm['family'],$families[$alarm['sdp_label']]))
		$families[$alarm['sdp_label']][$alarm['family']] = $alarm['family_label'];
}
// look for all families in the top worst lists
foreach ($top_alarms as $alarm) {
	// we make sure the $families[product_label] array exists
	if (!array_key_exists($alarm['sdp_label'],$families))
		$families[$alarm['sdp_label']] = array();
	// we make sure the $families[product_label][family] value exists
	if (!array_key_exists($alarm['family'],$families[$alarm['sdp_label']]))
		$families[$alarm['sdp_label']][$alarm['family']] = $alarm['family_label'];
}

// on trie les familles par ordre alpha de label
foreach ($families as $fam_name => $fam_array) {
	asort($fam_array);
	$families[$fam_name] = $fam_array;
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
	Dashboards and alarms filter
	</h3>
	<?php //11/09/2014 - FGD - Bug 43793 - [REC][CB 5.3.3.02][TC #TA-56730][IE 11 Compatibility] Dashboards/Graphs/Alarms are NOT displayed anymore when clicking "x" at the end of input field. ?>
	<input id="filter" onmouseup="setTimeout(function(){filter_list('li_ast_dash');filter_list('li_admin_dash');filter_list('li_user_dash');filter_list('li_alarm_static');filter_list('li_alarm_dynamic');filter_list('li_alarm_top');}, 0)" onkeyup="filter_list('li_ast_dash');filter_list('li_admin_dash');filter_list('li_user_dash');filter_list('li_alarm_static');filter_list('li_alarm_dynamic');filter_list('li_alarm_top');" />

    <!-- 08/07/2009 SPS : amelioration du style de la zone de filtre (correction bug 9935) -->
	<div id='all_filters' style='display:none;'>
        <div>
		<!-- Dashboards checkboxes -->
		<div class="product_filter" style="margin-top:5px;">
			<strong style="font-size:11px;"><?php echo __T('G_GDR_BUILDER_DASHBOARDS')?></strong>
            <br/>
            &nbsp;&nbsp;<input type="checkbox" name="show_ast_dash" id="show_ast_dash" checked="checked" onclick="setTimeout('check_show(\'show_ast_dash\',\'li_ast_dash\')',10);return true;" />
			<label for="show_ast_dash"><?php echo __T('G_GDR_BUILDER_ASTELLIA')?></label>
            <br/>
            &nbsp;&nbsp;<input type="checkbox" name="show_admin_dash" id="show_admin_dash" checked="checked" onclick="setTimeout('check_show(\'show_admin_dash\',\'li_admin_dash\')',10);return true;" />
			<label for="show_admin_dash"><?php echo __T('G_GDR_BUILDER_ADMIN')?></label>
            <br/>
            &nbsp;&nbsp;<input type="checkbox" name="show_user_dash" id="show_user_dash" checked="checked" onclick="setTimeout('check_show(\'show_user_dash\',\'li_user_dash\')',10);return true;" />
			<label for="show_user_dash"><?php echo __T('G_GDR_BUILDER_USERS')?></label>
		</div>
		<!-- Alarms checkboxes -->
		<div class="product_filter" style="margin-top:5px;">
			<strong style="font-size:11px;"><?php echo __T('G_GDR_BUILDER_ALARMS')?></strong>
		<!-- family selector -->
            <select id="family_list" onchange="update_family_filter();" style='font-size:11px;'>
				<option value='all'>Display all families</option>
				<?php
					foreach ($families as $product_label => $product_arr) {
						if (is_array($product_arr)) {
							echo "\n<optgroup label='$product_label'>";
								foreach ($product_arr as $fam=>$fam_label)
									echo "\n\t<option value='fam_".preg_replace('/[^a-zA-Z0-9]/', '', $product_label)."_$fam'>$fam_label</option>";
							echo "\n</optgroup>";
						}
					}
				?>
			</select>
            <br/>
			&nbsp;&nbsp;<input type="checkbox" name="show_alarm_static" id="show_alarm_static" checked="checked" onclick="setTimeout('check_show(\'show_alarm_static\',\'li_alarm_static\')',10);return true;" />
			<label for="show_alarm_static"><?php echo __T('G_GDR_BUILDER_STATIC')?></label>
            <br/>
			&nbsp;&nbsp;<input type="checkbox" name="show_alarm_dynamic" id="show_alarm_dynamic" checked="checked" onclick="setTimeout('check_show(\'show_alarm_dynamic\',\'li_alarm_dynamic\')',10);return true;" />
			<label for="show_alarm_dynamic"><?php echo __T('G_GDR_BUILDER_DYNAMIC')?></label>
            <br/>
			&nbsp;&nbsp;<input type="checkbox" name="show_alarm_top" id="show_alarm_top" checked="checked" onclick="setTimeout('check_show(\'show_alarm_top\',\'li_alarm_top\')',10);return true;" />
			<label for="show_alarm_top"><?php echo __T('G_GDR_BUILDER_TOP_WORST')?></label>
		</div>

        <!--Product filter checkboxes -->
        <div id="productsListFilter" class="reportFilters" style="margin-top:5px;<?php if (count($allProducts)==1) echo "display:none;"; ?>">
            <strong style="font-size:11px;"><?php echo __T('G_GDR_BUILDER_PRODUCT_LIST')?></strong>
            <?php
                foreach ( $allProducts as $prod )
                {
            ?>
                 <br />
                 &nbsp;&nbsp;<input type="checkbox" name="show_sdp_<?php echo $prod['sdp_id'] ?>" id="show_sdp_<?php echo $prod['sdp_id'] ?>" checked="checked" onclick="setTimeout('check_show(\'show_sdp_<?php echo $prod['sdp_id'] ?>\',\'\')',10);return true;"/>
                 <label for="show_sdp_<?php echo $prod['sdp_id'] ?>"><?php echo $prod['sdp_label']?></label>
            <?php
                }
            ?>
		</div>
        <div id='updating_families' align='center' style='color:red;display:none;margin-top:6px;'><img src='images/ajax-loader.gif' alt='wait...' width='16' height='16' align='absmiddle'/> Updating display</div>
		</div>
	</div>
</div>


<!-- on instantie les règles CSS pour pouvoir les modifier ensuite -->
<style type="text/css" id="inline_products_css">
<?php
    // 16/08/2010 NSE DE Firefox bz 16924 on remplace UL UL LI par ul li ul li et on homogénéise la casse.
    //Sous IE, il faut que les balises html soient en majuscules
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

	foreach ($families as $product_label => $product_arr)
        if (is_array($product_arr))
            foreach ($product_arr as $fam => $fam_label)
                echo $gtm_elements_list_family.preg_replace('/[^a-zA-Z0-9]/', '', $product_label)."_$fam {}\n";

    foreach ($allProducts as $prod) {
		echo $gtm_elements_list_family.$prod['sdp_id'].' {display:inherit;}';
		echo $gtm_elements_list_prod.$prod['sdp_id'].' {display:inherit;}';
    }

?>
</style>


<script type="text/javascript">

// lorsque l'on change le filtre de familles, cette fonction est appelée pour effacer toutes les alarmes
function hide_all_families() {
<?php	// 16/08/2010 NSE DE Firefox bz 16924
    foreach ($families as $product_label => $product_arr)
			if (is_array($product_arr))
				foreach ($product_arr as $fam => $fam_label)
					echo "\n	getStyleRule('inline_products_css','$gtm_elements_list_family".preg_replace('/[^a-zA-Z0-9]/', '', $product_label)."_$fam').style.display = 'none';";
?>
}

// quand on choisi "all families" dans le menu filtre familles, on affiche toutes les familles
function show_all_families() {
<?php	// 16/08/2010 NSE DE Firefox bz 16924
    foreach ($families as $product_label => $product_arr)
				if (is_array($product_arr))
					foreach ($product_arr as $fam => $fam_label)
						echo "\n	getStyleRule('inline_products_css','$gtm_elements_list_family".preg_replace('/[^a-zA-Z0-9]/', '', $product_label)."_$fam').style.display = '';";
	?>

	$('updating_families').style.display = 'none';	// we hide waiting icon
}


// fonction appelée lorsque l'on change le filtre par famille
function update_family_filter()
{
	// we show waiting icon
	$('updating_families').style.display = 'block';
	// on cache toutes les familles
	hide_all_families();
	// on affiche que celle choisie dans le selecteur
	var selectedFam = $F('family_list');
	if(selectedFam == 'all') {
		show_all_families();
	}
	else {
            // 16/08/2010 NSE DE Firefox bz 16924
		selectedFam = '<?=$gtm_elements_list_family?>'+selectedFam.slice(4);
		getStyleRule('inline_products_css',selectedFam).style.display = 'block';
	}
	// we hide waiting icon
	$('updating_families').style.display = 'none';
}

</script>


<!-- liste des dashs / alarms -->
<div id="gtm_elements_list" style="height:400px;overflow:scroll;">
	<h3><?php echo __T('G_GDR_BUILDER_DASHBOARDS_AND_ALARMS')?></h3>
	<ul>
		<li id="li_ast_dash">
			<a class='js_20090116'><img src='images/arrow_down.png' alt='open' width='16' height='16' align='absmiddle' /> <?php echo __T('G_GDR_BUILDER_ASTELLIA_DASHBOARDS')?></a>
			<ul>
				<?php
                                // 28/09/2010 NSE bz 14937 et support VideoTron : on ajoute l'id_product
				foreach ($dashboards as $dash)
                {
                    // 28/01/2011 BBX
                    // Affichage des graphes uniquement sur les produits désactivés
                    // BZ 20414
                    // On réaffiche les éléments basés sur des produits désactivés
                    // Mais ils ne seront pas sélectionnables
                    // BZ 20498
                    $disabledProduct = false;
                    $dashboardModel = new DashboardModel($dash['id_page']);
                    foreach($dashboardModel->getInvolvedProducts() as $p) {
                        if(!ProductModel::isActive($p)) {
                            $disabledProduct = true;
                        }
                    }

					if ($dash['optgroup_order']==1)
                    {
                        $productRefLabel = $dash['product_ref'];
                        $productRefId    = null;
                        if( ( $pos = strpos( $productRefLabel, '|s|' ) ) !== false ){
                            $productRefId = substr( $productRefLabel, $pos + 3  );
                            $productRefLabel = substr( $productRefLabel, 0, $pos );
                        }

                        // Affichage standard
                        if(!$disabledProduct)
                        {
                            echo "<li id='list_dash__{$dash['id_page']}__{$productRefId}' class='prod_{$productRefId}'>".
                                    "<nobr>".
                                        "<img src='images/dashboard.png' alt='".__T('G_GDR_BUILDER_DASHBOARD')."' width='16' height='16' align='absmiddle' vspace='1'/>".
                                        "&nbsp;{$dash['page_label']} ({$productRefLabel})".
                                    "</nobr>".
                                  "</li>";
                        }
                        // On réaffiche les éléments basés sur des produits désactivés
                        // Mais ils ne seront pas sélectionnables
                        // BZ 20498
                        else
                        {
                            echo "<li id='disabled_element__{$dash['id_page']}__{$productRefId}' class='prod_{$productRefId} disabled_element'>".
                                    "<nobr>".
                                        "<img src='images/dashboard.png' alt='".__T('G_GDR_BUILDER_DASHBOARD')."' width='16' height='16' align='absmiddle' vspace='1'/>".
                                        "&nbsp;{$dash['page_label']} ($productRefLabel is disabled)".
                                    "</nobr>".
                                  "</li>";
                        }
                    }
                }
				?>
			</ul>
		</li>
		<li id="li_admin_dash">
			<a class='js_20090116'><img src='images/arrow_down.png' alt='open' width='16' height='16' align='absmiddle' /> <?php echo __T('G_GDR_BUILDER_ADMINISTRATOR_DASHBOARDS')?></a>
			<ul>
				<?php
                                // 20/10/2010 NSE bz 18635 : ajout de id_product pour les dash admin et user
				foreach ($dashboards as $dash)
                {
                    // 28/01/2011 BBX
                    // Affichage des graphes uniquement sur les produits désactivés
                    // BZ 20414
                    // On réaffiche les éléments basés sur des produits désactivés
                    // Mais ils ne seront pas sélectionnables
                    // BZ 20498
                    $disabledProduct = false;
                    $dashboardModel = new DashboardModel($dash['id_page']);
                    foreach($dashboardModel->getInvolvedProducts() as $p) {
                        if(!ProductModel::isActive($p)) {
                            $disabledProduct = true;
                        }
                    }

					if ($dash['optgroup_order']==2)
                    {
                        $productRefLabel = $dash['product_ref'];
                        $productRefId    = null;
                        if( ( $pos = strpos( $productRefLabel, '|s|' ) ) !== false ){
                            $productRefId = substr( $productRefLabel, $pos + 3  );
                            $productRefLabel = substr( $productRefLabel, 0, $pos );
                        }

                        // Affichage standard
                        if(!$disabledProduct)
                        {
                            echo "<li id='list_dash__{$dash['id_page']}__{$productRefId}' class='prod_{$productRefId}'>".
                                "<nobr>".
                                    "<img src='images/dashboard.png' alt='".__T('G_GDR_BUILDER_DASHBOARD')."' width='16' height='16' align='absmiddle' vspace='1'/>".
                                    "&nbsp;{$dash['page_label']} ({$productRefLabel})".
                                "</nobr>".
                            "</li>";
                        }
                        // On réaffiche les éléments basés sur des produits désactivés
                        // Mais ils ne seront pas sélectionnables
                        // BZ 20498
                        else
                        {
                            echo "<li id='disabled_element__{$dash['id_page']}__{$productRefId}' class='prod_{$productRefId} disabled_element'>".
                                "<nobr>".
                                    "<img src='images/dashboard.png' alt='".__T('G_GDR_BUILDER_DASHBOARD')."' width='16' height='16' align='absmiddle' vspace='1'/>".
                                    "&nbsp;{$dash['page_label']} ({$productRefLabel} is disabled)".
                                "</nobr>".
                            "</li>";
                        }
                    }
                }
				?>
			</ul>
		</li>
		<li id="li_user_dash">
			<a class='js_20090116'><img src='images/arrow_down.png' alt='open' width='16' height='16' align='absmiddle' /> <?php echo __T('G_GDR_BUILDER_USERS_DASHBOARDS')?></a>
			<ul>
				<?php
                                // 20/10/2010 NSE bz 18635 : ajout de id_product pour les dash admin et user
				foreach ($dashboards as $dash)
                {
                    // 28/01/2011 BBX
                    // Affichage des graphes uniquement sur les produits désactivés
                    // BZ 20414
                    // On réaffiche les éléments basés sur des produits désactivés
                    // Mais ils ne seront pas sélectionnables
                    // BZ 20498
                    $disabledProduct = false;
                    $dashboardModel = new DashboardModel($dash['id_page']);
                    foreach($dashboardModel->getInvolvedProducts() as $p) {
                        if(!ProductModel::isActive($p)) {
                            $disabledProduct = true;
                        }
                    }

                    $productRefLabel = $dash['product_ref'];
                    $productRefId    = null;
                    if( ( $pos = strpos( $productRefLabel, '|s|' ) ) !== false ){
                        $productRefId = substr( $productRefLabel, $pos + 3  );
                        $productRefLabel = substr( $productRefLabel, 0, $pos );
                    }

					if ($dash['optgroup_order']==3)
                    {
                        // Affichage standard
                        if(!$disabledProduct)
                        {
                            echo "<li id='list_dash__{$dash['id_page']}__{$productRefId}' class='prod_{$productRefId}'>".
                                "<nobr>".
                                    "<img src='images/dashboard.png' alt='".__T('G_GDR_BUILDER_DASHBOARD')."' width='16' height='16' align='absmiddle' vspace='1'/>".
                                    "&nbsp;{$dash['page_label']} ({$productRefLabel})".
                                "</nobr>".
                            "</li>";
                        }
                        // On réaffiche les éléments basés sur des produits désactivés
                        // Mais ils ne seront pas sélectionnables
                        // BZ 20498
                        else
                        {
                            echo "<li id='disabled_element__{$dash['id_page']}__{$productRefId}' class='prod_{$productRefId} disabled_element'>".
                                "<nobr>".
                                    "<img src='images/dashboard.png' alt='".__T('G_GDR_BUILDER_DASHBOARD')."' width='16' height='16' align='absmiddle' vspace='1'/>".
                                    "&nbsp;{$dash['page_label']} ({$productRefLabel} is disabled)".
                                "</nobr>".
                            "</li>";
                        }
                    }
                }
				?>
			</ul>
		</li>

		<li id="li_alarm_static">
			<a class='js_20090116'><img src='images/arrow_down.png' alt='open' width='16' height='16' align='absmiddle' /> <?php echo __T('G_GDR_BUILDER_ALARM_STATIC')?></a>
			<ul>
				<?php
				foreach ($static_alarms as $alarm)
					echo "\n<li id='list_static__{$alarm['alarm_id']}__{$alarm['sdp_id']}' class='prod_{$alarm['sdp_id']} family_".preg_replace('/[^a-zA-Z0-9]/', '', $alarm['sdp_label'])."_{$alarm['family']}'><nobr><img src='images/alarm.png' width='16' height='16' align='absmiddle' vspace='1'/>&nbsp;{$alarm['alarm_name']} ({$alarm['sdp_label']})</nobr></li>";
				?>
			</ul>
		</li>

		<li id="li_alarm_dynamic">
			<a class='js_20090116'><img src='images/arrow_down.png' alt='open' width='16' height='16' align='absmiddle' /> <?php echo __T('G_GDR_BUILDER_ALARM_DYNAMIC')?></a>
			<ul>
				<?php
				foreach ($dyn_alarms as $alarm)
					echo "\n<li id='list_dynamic__{$alarm['alarm_id']}__{$alarm['sdp_id']}' class='prod_{$alarm['sdp_id']} family_".preg_replace('/[^a-zA-Z0-9]/', '', $alarm['sdp_label'])."_{$alarm['family']}'><nobr><img src='images/alarm.png' width='16' height='16' align='absmiddle' vspace='1'/>&nbsp;{$alarm['alarm_name']} ({$alarm['sdp_label']})</nobr></li>";
				?>
			</ul>
		</li>

		<li id="li_alarm_top">
			<a class='js_20090116'><img src='images/arrow_down.png' alt='open' width='16' height='16' align='absmiddle' /> <?php echo __T('G_GDR_BUILDER_ALARM_TOPWORST')?></a>
			<ul>
				<?php
				foreach ($top_alarms as $alarm)
					echo "\n<li id='list_top__{$alarm['alarm_id']}__{$alarm['sdp_id']}' class='prod_{$alarm['sdp_id']} family_".preg_replace('/[^a-zA-Z0-9]/', '', $alarm['sdp_label'])."_{$alarm['family']}'><nobr><img src='images/alarm.png' width='16' height='16' align='absmiddle' vspace='1'/>&nbsp;{$alarm['alarm_name']} ({$alarm['sdp_label']})</nobr></li>";
				?>
			</ul>
		</li>


	</ul>
</div>


<script type="text/javascript" src="js/left_list_manager.js"></script>

<script type="text/javascript">

var groups = $$('a.js_20090116');
// alert(groups);
nb_groups = groups.length;
for (i=0; i<nb_groups; i++)	groups[i].onclick	= show_hide_nextSibling;

// get list of all dashboards and alarms
var elems_lists = new Array();
elems_lists['li_ast_dash']			= $('li_ast_dash').getElementsByTagName('LI');
elems_lists['li_admin_dash']		= $('li_admin_dash').getElementsByTagName('LI');
elems_lists['li_user_dash']		= $('li_user_dash').getElementsByTagName('LI');
elems_lists['li_alarm_static']		= $('li_alarm_static').getElementsByTagName('LI');
elems_lists['li_alarm_dynamic']		= $('li_alarm_dynamic').getElementsByTagName('LI');
elems_lists['li_alarm_top']		= $('li_alarm_top').getElementsByTagName('LI');

var elems_nb = new Array();
elems_nb['li_ast_dash']		= elems_lists['li_ast_dash'].length;
elems_nb['li_admin_dash']	= elems_lists['li_admin_dash'].length;
elems_nb['li_user_dash']		= elems_lists['li_user_dash'].length;
elems_nb['li_alarm_static']	= elems_lists['li_alarm_static'].length;
elems_nb['li_alarm_dynamic']	= elems_lists['li_alarm_dynamic'].length;
elems_nb['li_alarm_top']		= elems_lists['li_alarm_top'].length;

// on associe l'ajout d'un element au GTM au click sur un raw/kpi de la liste de gauche
for (i=0; i<elems_nb['li_ast_dash']; i++)		elems_lists['li_ast_dash'][i].onclick		= add_element_to_report;
for (i=0; i<elems_nb['li_admin_dash']; i++)	elems_lists['li_admin_dash'][i].onclick	= add_element_to_report;
for (i=0; i<elems_nb['li_user_dash']; i++)		elems_lists['li_user_dash'][i].onclick		= add_element_to_report;
for (i=0; i<elems_nb['li_alarm_static']; i++)	elems_lists['li_alarm_static'][i].onclick	= add_element_to_report;
for (i=0; i<elems_nb['li_alarm_dynamic']; i++)	elems_lists['li_alarm_dynamic'][i].onclick	= add_element_to_report;
for (i=0; i<elems_nb['li_alarm_top']; i++)		elems_lists['li_alarm_top'][i].onclick		= add_element_to_report;

</script>

