<?php
/*
	18/05/2009 GHX
		- Remplacement de sdd_is_homepage par sdd_id_homepage dans le HTML (ANNULE)
	07/07/2009 GHX
		- Ajout d'une condition pour ne pas cocher la homepage par défaut quand on crée un nouveau dashbaord
	25/08/2009 GHX
		- Affichage des sous-menus 
	16/02/2010 NSE bz 14281 
		- Ajout du paramètre pour la période max autorisé pour le GTM à la fonction check_gtmForm()
	25/02/2010 NSE bz 14508
		- ajout du ! dans le commentaire
*/
?>
<?php
/**
*	@cb4100@
*	- Creation SLC	 31/10/2008
*
*	Cette page se fait inclure par dashboard.php
*	Elle est extraite de dashboard.php pour garder des scripts raisonablement longs
*
*	Cette page affiche les properties du dashboard
*/

// menu link (on sélectionne tous les menu user parent.)
if ($client_type == 'customisateur') {
	// 11:16 25/08/2009 GHX
	// Modification de la requete SQL on va chercher aussi les sous-menus
        // 08/06/2011 BBX -PARTITIONING-
        // Correction des casts
	$query = "	--- on va chercher les menus et sous-menus
		SELECT 	
			tmp.id_menu AS id_menu, 
			tmp.libelle_menu AS libelle_menu,
			CASE WHEN mdi.id_page IS NULL THEN mdi.id_menu  ELSE NULL END AS id_sub_menu,
			CASE WHEN mdi.id_page IS NULL THEN mdi.libelle_menu  ELSE NULL END AS libelle_sub_menu
		FROM
			(
				SELECT id_menu,libelle_menu FROM menu_deroulant_intranet 
				WHERE is_profile_ref_user=1 AND id_menu_parent='0' AND droit_affichage='customisateur'
				
				UNION
				
				SELECT id_menu,libelle_menu FROM menu_deroulant_intranet 
				WHERE is_profile_ref_user=1 AND id_menu_parent='0' AND menu_client_default='1'
			) AS tmp left join 
		menu_deroulant_intranet AS mdi on (tmp.id_menu = mdi.id_menu_parent)		
		ORDER BY tmp.libelle_menu, mdi.libelle_menu
	";
} else {
	// maj 10/03/2008 christophe : Si le profil de l'utilisateur est de type 'client', on affiche seulement le menu dont le champ menu_client_default=1 de menu_deroulant_intranet
        // 07/06/2011 BBX -PARTITIONING-
        // Correction des casts
	$query = " --- on va chercher les menus
		SELECT id_menu,libelle_menu, null AS id_sub_menu, null  AS libelle_sub_menu
		FROM menu_deroulant_intranet 
		WHERE is_profile_ref_user=1
			AND id_menu_parent='0'
			AND menu_client_default='1'
		ORDER BY libelle_menu
	";
}
$menu_link = $db->getall($query);

// echo $db->displayQueries();
//__debug($menu_link);

if ($debug) {
	echo "\n<div class='debug'><table><tr><th colspan='2'>contenu de \$dash</th></tr>";
	foreach ($dash as $key => $val) echo "\n<tr><td>$key</td><td>$val</td></tr>";
	echo "\n</table></div>";
}

// 16/02/2010 NSE bz 14281 : ajout du paramètre pour la période max pour le GTM à la fonction check_gtmForm()
?>
<!-- 14/02/2011 OJT : DE Selecteur, utilisation de getMaxHistory pour la limite de la période -->
<form action="dashboard_save.php" method="post" style="margin:0;" onsubmit="return check_gtmForm(<?= ProductModel::getMaxHistory();?>);" name="gtmForm">
	<input type="hidden" name="id_page" id="hidden_id_page" value="<?php echo $id_page?>"/>
<table>
	<tr>
		<td class="fieldname"><?php echo __T('G_GDR_BUILDER_DASHBOARD_TITLE')?></td>
		<td><input name="page_name" id="page_name" value="<?php echo $dash["page_name"]?>" <?=$disabled?> style="width:340px;"/></td>
	</tr>
	<tr>
		<td class="fieldname"><?php echo __T('G_GDR_BUILDER_MODE')?></td>
		<td><select name="sdd_mode" <?=$disabled?> style="width:206px;">
				<option value='overtime'><?php echo __T('G_GDR_BUILDER_OVERTIME')?></option>
				<option value="overnetwork" <?php if ($dash['sdd_mode'] == 'overnetwork') echo "selected='selected'";?>><?php echo __T('G_GDR_BUILDER_OVER_NETWORK_ELEMENTS')?></option>
				<option value="bimode" <?php if ($dash['sdd_mode'] == 'bimode') echo "selected='selected'";?>><?php echo __T('G_GDR_BUILDER_OVERTIME_OVER_NETWORK_ELEMENTS')?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="fieldname"><?php echo __T('G_GDR_BUILDER_MENU_LINK')?></td>
		<td><select name="id_menu" <?=$disabled?> style="width:206px;">
			<?php
				// on va chercher l'id du menu_parent
				$dash['sdd_id_menu_parent'] = $db->getone("SELECT id_menu_parent FROM menu_deroulant_intranet WHERE id_menu='{$dash['sdd_id_menu']}'");
				// on affiche les menus disponibles
				// 11:16 25/08/2009 GHX
				// Affichage des sous-menus s'ils existent
				$lastParentMenu = null;
				foreach ($menu_link as $men) {
					if ( $lastParentMenu != $men['id_menu'] )
						echo "<option value='{$men['id_menu']}'".(($men['id_menu']==$dash['sdd_id_menu_parent'])?' selected="selected"':'').">{$men['libelle_menu']}</option>";
					
					if ( $men['id_sub_menu'] != '' )
						echo "<option value='{$men['id_sub_menu']}'".(($men['id_sub_menu']==$dash['sdd_id_menu_parent'])?' selected="selected"':'').">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{$men['libelle_sub_menu']}</option>";

					$lastParentMenu = $men['id_menu'];
				}
			?>
			</select>
			&nbsp;&nbsp;<input name="sdd_is_online" type='checkbox' value='1' id='sdd_is_online' <?=$disabled?> <?php if ($dash['sdd_is_online']==1) echo "checked='checked'"; ?>/> <label for="sdd_is_online"><?php echo __T('G_GDR_BUILDER_ONLINE')?></label>
			
			<?php if ($client_type == 'customisateur') {
				$id_homepage = get_sys_global_parameters('id_homepage', null);
				$homepage_default_mode = get_sys_global_parameters('mode_homepage', 'overtime');
				?>
				<div>
					<input name="sdd_is_homepage" type='checkbox' value='1' id='sdd_is_homepage' <?=$disabled?> <?php if ($id_page==$id_homepage && ($id_page != null || $id_page != 0)) echo "checked='checked'"; ?>
							onclick="setTimeout('click_is_homepage();',20);" /> <label for="sdd_is_homepage"><?php echo __T('G_GDR_BUILDER_SET_AS_HOMEPAGE')?></label>
					<div id="homepage_default_mode_div" style="display:<?php echo (($id_homepage == $id_page && ($id_page != null || $id_page != 0))?'inline':'none') ?>;">
						<?php echo __T('G_GDR_BUILDER_HOMEPAGE_DEFAULT_MODE')?>
						<select name="homepage_default_mode">
							<option value="overtime"><?php echo __T('G_GDR_BUILDER_OVERTIME')?></option>
							<option value="overnetwork" <?php if ($homepage_default_mode=='overnetwork') echo "selected='selected'"; ?>><?php echo __T('G_GDR_BUILDER_OVER_NETWORK_ELEMENTS')?></option>
						</select>
					</div>
				</div>
			<?php } ?>
		</td>
	</tr>
	<tr><td>&nbsp;<br/><strong style="color:black;"><big><?php echo __T('G_GDR_BUILDER_SELECTOR')?></big></strong></td></tr>
	<tr>
		<td class="fieldname"><?php echo __T('G_GDR_BUILDER_DEFAULT_ORDER_BY')?></td>
		<td><select name="sdd_sort_by_id" id="sdd_sort_by_id" <?=$disabled?>>
			<?php include("dashboard_get_order_by_options.php"); ?>
			</select>
		</td>
	</tr>
	<tr>
		<td class="fieldname"><?php echo __T('G_GDR_BUILDER_SORTING_ORDER')?></td>
		<td>
			<input name="sdd_sort_by_order" type="radio" value='asc' id='sdd_sort_by_order_asc' <?=$disabled?> <?php if ($dash['sdd_sort_by_order'] == 'asc') echo "checked='checked'"; ?>/> <label for="sdd_sort_by_order_asc"><?php echo __T('G_GDR_BUILDER_ASC')?></label>
			&nbsp;&nbsp;&nbsp;
			<input name="sdd_sort_by_order" type="radio" value='desc' id='sdd_sort_by_order_desc' <?=$disabled?> <?php if ($dash['sdd_sort_by_order'] == 'desc') echo "checked='checked'"; ?>/> <label for="sdd_sort_by_order_desc"><?php echo __T('G_GDR_BUILDER_DESC')?></label>
		</td>
	</tr>
	<tr>
		<td class="fieldname"><?php echo __T('G_GDR_BUILDER_PERIOD')?></td>
		<!-- 16/01/2010 NSE bz 14281 : ajout de l'id pour l'utilisation de la fonciton rouge() + bz 14508 ajout du ! dans le commentaire -->
		<td><input name="sdd_selecteur_default_period" id="sdd_selecteur_default_period" value='<?php echo $dash['sdd_selecteur_default_period'] ?>' <?=$disabled?> style="width:30px;"/></td>
	</tr>
	<tr>
		<td class="fieldname"><?php echo __T('G_GDR_BUILDER_TOP_OVER_TIME')?></td>
		<td><input name="sdd_selecteur_default_top_overtime" value='<?php echo $dash['sdd_selecteur_default_top_overtime'] ?>' <?=$disabled?> style="width:30px;"/></td>
	</tr>
	<tr>
		<td class="fieldname"><?php echo __T('G_GDR_BUILDER_TOP_OVER_NETWORK')?></td>
		<td><input name="sdd_selecteur_default_top_overnetwork" value='<?php echo $dash['sdd_selecteur_default_top_overnetwork'] ?>' <?=$disabled?> style="width:30px;"/></td>
	</tr>
	<tr>
		<td class="fieldname"><?php echo __T('G_GDR_BUILDER_NETWORK_AGGREGATION')?></td>
		<td><select name="sdd_selecteur_default_na" id="sdd_selecteur_default_na" <?=$disabled?>>
			<option value='<?php echo $dash['sdd_selecteur_default_na'] ?>'><?php echo $dash['sdd_selecteur_default_na'] ?></option>
			</select>
		</td>
	</tr>
	<!-- tr>
		<td class="fieldname"><?php echo __T('G_GDR_BUILDER_NETWORK_AGGREGATION_FOR_AXE_3')?></td>
		<td><input name="sdd_selecteur_default_na_axe3" value='<?php echo $dash['sdd_selecteur_default_na_axe3'] ?>' <?=$disabled?> style="width:30px;"/></td>
	</tr -->
	<tr>
		<td></td>
		<td>
			<?php if ($id_page!='0' && !empty($id_page)) { ?>
				<?php if ($disabled == '') { ?>
					<input type="submit" value="<?php echo __T('G_GDR_BUILDER_SAVE')?>"/>
				<?php } ?>
			<?php } else { ?>
				<input type="submit" value="<?php echo __T('G_GDR_BUILDER_CREATE_NEW_DASHBOARD')?>"/>
			<?php } ?>
			<input type="reset" onclick="get_dashboard_properties();" value="<?php echo __T('G_GDR_BUILDER_CLOSE')?>"/>
		</td>
	</tr>
</table>
</form>

