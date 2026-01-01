<?php
/*
	27/07/2009 GHX
		- Correction du BZ 4459 [REC][T&A GB 2.0][B4459][F705][GTM]: changement de position légende KO pour pie
			-> Si on est sur un PIE, on ne peut pas sélectionné la légende en haut (top)
	12/08/2009 GHX
		- Ajout de l'attrbut id pour le champ ordonnee_right_name

	09/06/10 YNE/FJT : SINGLE KPI
    17/08/2010 OJT : Correction bz16864 pour DE Firefox
*/
?>
<?php
/**
*	@cb4100@
*	- Creation SLC	 29/10/2008
*
*	Cette page se fait inclure par graph.php
*	Elle est extraite de graph.php pour garder des scripts raisonablement longs
*
*	Cette page affiche les properties du graph
*/


	/* maj 04/02/2008 christophe
		On définit ici quels seront les droits de l'utilisateur courant sur le graph/rapport/dahsboard  qu'il visualise.
		- Customisateur : lecture/écriture sur tous les éléments. (droit='customisateur')
		- Administrateur : lecture/écriture sur tous les éléments sauf les éléments créés par le customisateur qui sont en lecture. (droit='client' et profil='admin')
		- Elements créés par l'utilsiateur courant : lecture/écriture sur les éléments qu'il a créé et lecture pour le reste.(droit='client' et profil='user')
		$this->allow_edit : Définit si l'utilisateur a le droit d'éditer le contenu.
	*/
	

?>


<form action="graph_save.php" method="post" style="margin:0;" onsubmit="return check_gtmForm();" name="gtmForm">
	<input type="hidden" name="id_page" id="hidden_id_page" value="<?php echo $id_page?>"/>
<table>
	<tr>
		<td class="fieldname"><?php echo __T('G_GDR_BUILDER_TITLE')?></td>
		<td><input name="page_name" id="page_name" value="<?php echo $graph["page_name"]?>" <?=$disabled?> style="width:380px;"/></td>
	</tr>
	<tr>
		<td class="fieldname"><?php echo __T('G_GDR_BUILDER_DEFINITION')?></td>
		<td><textarea <?=$disabled?> class="zoneTexteStyleXP" rows="4" cols="60" name="definition" id="definition" style="width:382px;"><?=$graph['definition']?></textarea></td>
	</tr>
	<tr>
		<td class="fieldname"><?php echo __T('G_GDR_BUILDER_TROUBLESHOOTING')?></td>
		<td><textarea <?=$disabled?> class="zoneTexteStyleXP" rows="4" cols="60" name="troubleshooting" id="troubleshooting" style="width:382px;"><?=$graph['troubleshooting']?></textarea></td>
	</tr>
	<tr>
		<td class="fieldname"><?php echo __T('G_GDR_BUILDER_GRAPH_TYPE')?></td>
		<td>
			<div style="float:left;<?php if ($graph['object_type']=='graph') echo "background:#CCC;"; ?>width:90px;">
				<input type="radio" name="object_type" value="graph" id="object_type_graph" <?=$disabled?> <?php if ($graph['object_type']=='graph') echo "checked='checked'"; ?> onclick="change_object_type();"/>
				<label for="object_type_graph"> <img src='images/chart_bar.png' alt='<?php echo __T('G_GDR_BUILDER_GRAPH')?>' align='absmiddle' style='position:relative;top:1px;' width='16' height='16'/><?php echo __T('G_GDR_BUILDER_GRAPH')?></label>
			</div>
			<div style="width:80px;float:left;<?php if ($graph['object_type']=='pie3D') echo "background:#CCC;"; ?>">
				<input type="radio" name="object_type" value="pie3D" id="object_type_pie" <?=$disabled?> <?php if ($graph['object_type']=='pie3D') echo "checked='checked'"; ?> onclick="change_object_type();"/>
				<label for="object_type_pie"> <img src='images/chart_pie.png' alt='<?php echo __T('G_GDR_BUILDER_PIE')?>' align='absmiddle' style='position:relative;top:1px;' width='16' height='16'/><?php echo __T('G_GDR_BUILDER_PIE')?></label>
			</div>
            <!-- 17/08/2010 OJT : Ajout du float:left; -->
			<div style="width:120px;float:left;<?php if ($graph['object_type']=='singleKPI') echo "background:#CCC;"; ?>">
				<input type="radio" name="object_type" value="singleKPI" id="object_type_single_kpi" <?=$disabled?> <?php if ($graph['object_type']=='singleKPI') echo "checked='checked'"; ?> onclick="change_object_type();"/>
				<label for="object_type_single_kpi"> <img src='images/chart_single.png' alt='<?php echo __T('G_GDR_BUILDER_SINGLE_KPI')?>' align='absmiddle' style='position:relative;top:1px;' width='16' height='16'/><?php echo __T('G_GDR_BUILDER_SINGLE_KPI')?></label>
			</div>
			
			<!-- options des graphs -->
            <?php
                // 17/08/2010 OJT : Correction bz16864 pour DE Firefox
                $gtmGraphOptionStyle = "background-color:#CCC;padding:2px;clear:both;";
                if( $graph['object_type'] != 'graph' ){
                    $gtmGraphOptionStyle .= "display:none;";
                }
            ?>
			<div id="gtm_graph_option" style="<?php echo $gtmGraphOptionStyle; ?>">
				<div style="float:left;padding-top:4px;"><label><?=__T('G_PAUTO_GTM_SCALE_TYPE_LABEL')?>:</label></div>
				<div style="margin-left:88px;">
					<input  <?=$disabled?> type="radio" <?php if (($graph['scale']=='textlin') || ($graph['scale']=='')) echo "checked='checked'";?> value="textlin" name="scale" id="scale_linear"/>
					<label for="scale_linear"><?=__T('G_PAUTO_GTM_SCALE_TYPE_LINEAR_LABEL')?></label>
					&nbsp;
					<input  <?=$disabled?> type="radio" <?php if ($graph['scale']=='textlog') echo "checked='checked'";?> value="textlog" name="scale" id="scale_db"/>
					<label for="scale_db"><?=__T('G_PAUTO_GTM_SCALE_TYPE_DB_LABEL')?></label>
				</div>
			</div>
			
			<!-- options des graph pie -->
            <?php
                // 17/08/2010 OJT : Correction bz16864 pour DE Firefox
                $gtmPieOptionStyle = "background-color:#CCC;margin-left:90px;padding:2px;clear:both;";
                if( $graph['object_type'] != 'pie3D' ){
                    $gtmPieOptionStyle .= "display:none;";
                }
            ?>
			<div id="gtm_pie_option" style="<?php echo $gtmPieOptionStyle; ?>">
				<label><?php echo __T('G_GDR_BUILDER_SPLIT_BY')?></label>
				<input  <?=$disabled?> type="radio" <?php if ($graph['pie_split_type']=='first_axis') echo "checked='checked'";?> value="first_axis" name="pie_split_type" id="split_by_first_axis"/>
					<label for="split_by_first_axis"><?php echo __T('G_GDR_BUILDER_FIRST_AXIS')?></label>
				&nbsp;
				<input  <?=$disabled?> type="radio"	<?php if ($graph['pie_split_type']=='third_axis') echo "checked='checked'";?> value="third_axis" name="pie_split_type" id="split_by_third_axis"/>
					<label for="split_by_third_axis" id="label_split_by_third_axis"><?php echo __T('G_GDR_BUILDER_THIRD_AXIS')?></label>
				
				<!-- Ajout christophe le 09/06/08 : quand l'utilisateur choisit le type PIE, il sélectionne la série à utiliser comme split by -->
				<br />
				<?php echo __T('G_GDR_BUILDER_WITH')?>
				<select <?php echo $disabled ?> name="pie_split_by" id='pie_split_by'>
					<option class="texteRouge"><?php echo __T('G_GDR_BUILDER_THERE_IS_NO_DATA_IN_YOUR_GRAPH')?></option>
				</select>
				<input type="hidden" id="hidden_pie_split_by" name="hidden_pie_split_by" value="<?php echo $graph["pie_split_by"];?>"/>

			</div>
		</td>
	</tr>
	<tr>
		<td class="fieldname"><?php echo __T('G_GDR_BUILDER_YAXIS_LABEL')?></td>
		<td>
			<table cellpadding="0" cellspacing="0" border="0">
				<tr>
					<td width="92"><?php echo __T('G_GDR_BUILDER_LEFT')?><upper>*</upper></td>
					<td width="110"><input name="ordonnee_left_name" id="ordonnee_left_name" value="<?php echo $graph["ordonnee_left_name"]?>" <?=$disabled?> style="width:90px;"/></td>
					<td width="50" id="td_title_builder_ordonnee_right"><?php echo __T('G_GDR_BUILDER_RIGHT')?></td>
 					<td id="td_input_builder_ordonnee_right"><input id="ordonnee_right_name" name="ordonnee_right_name" value="<?php echo $graph["ordonnee_right_name"]?>" <?=$disabled?> style="width:90px;"/></td>
				</tr>
			</table> 
		</td>
	</tr>
	<tr>
		<td class="fieldname"><?php echo __T('G_GDR_BUILDER_GRAPH_SIZE')?></td>
		<td>
			<table cellpadding="0" cellspacing="0" border="0">
				<tr>
					<td width="92"><?php echo __T('G_GDR_BUILDER_WIDTH')?><up>*</up></td>
					<td width="110"><input name="graph_width" id="graph_width" value="<?php echo $graph["graph_width"] ?>" <?=$disabled?> size="3"/></td>
					<td width="50"><?php echo __T('G_GDR_BUILDER_HEIGHT')?></td>
					<td><input name="graph_height" id="graph_height" value="<?php echo $graph["graph_height"]?>" <?=$disabled?> size="3"/></td>
				</tr>
			</table>
			<div><small><?php echo __T('G_GDR_BUILDER_MINIMUM_SIZE_WIDTH_HEIGHT',$graph_width_minimum,$graph_height_minimum)?></small></div>
		</td>
	</tr>
	<tr>
		<td class="fieldname"><?php echo __T('G_GDR_BUILDER_LEGENDS_POSITION')?></td>
		<td>
			<div style="float:left;">
				<input <?=$disabled?> <?php if ($graph['object_type']!='graph') echo 'disabled="disabled"';?> name="position_legende" id="position_legende_top" type="radio" value="top" <?php if ($graph["position_legende"]=='top' || $graph['object_type']=='singleKPI') echo "checked='checked'"; ?>/> <label for="position_legende_top"><?php echo __T('G_GDR_BUILDER_TOP')?></label>
			</div>
			<div style="margin-left:90px;">
				<input <?=$disabled?> <?php if ($graph['object_type']=='singleKPI') echo 'disabled="disabled"';?> name="position_legende" id="position_legende_right" type="radio" value="right" <?php if ($graph["position_legende"]=='right') echo "checked='checked'"; ?>/> <label for="position_legende_right"><?php echo __T('G_GDR_BUILDER_RIGHT')?></label>
			</div>
		</td>
	</tr>
	<tr>
		<td class="fieldname"><?php echo __T('G_GDR_BUILDER_GIS')?></td>
		<td>
			<div style="float:left;">
				<input <?=($disabled !== "") ? $disabled : $disabled_gis?> name="gis" value="0" type="radio" id="gis_0" <?php if ($graph["gis"]==0 or ($disabled_gis !== "" and $disabled !== "")) echo "checked='checked'"; ?> onclick="change_gis();"/> <label for="gis_0"><?php echo __T('G_GDR_BUILDER_OFF')?></label>
			</div>
            <?php
                // 17/08/2010 OJT : Correction bz16864 pour DE Firefox
                $inputGis1Style = "margin-left:90px;width:80px;";
                if ( $graph["gis"] == 1 ){
                    $inputGis1Style .= "background:#CCC;";
                }
            ?>
			<div style="<?php echo $inputGis1Style; ?>">
				<input <?=($disabled !== "") ? $disabled : $disabled_gis?> name="gis" value="1" type="radio" id="gis_1" <?php if ($graph["gis"]==1 and $disabled_gis == "") echo "checked='checked'"; ?> onclick="change_gis();"/> <label for="gis_1"><?php echo __T('G_GDR_BUILDER_ON')?></label>
			</div>
            <?php
                // 17/08/2010 OJT : Correction bz16864 pour DE Firefox
                $gisBaseOnDivStyle = "margin-left:90px;background:#CCC;padding:2px;";
                if( $graph["gis"] == 0 || $disabled_gis !== "" ){
                    $gisBaseOnDivStyle .= "display:none;";
                }
            ?>
			<div id="gis_based_on_div" style="<?php echo $gisBaseOnDivStyle; ?>">
				<?php echo __T('G_GDR_BUILDER_BASED_ON')?> 
				<select <?php echo ($disabled !== "") ? $disabled : $disabled_gis ?> name="gis_based_on" id="gis_based_on">
					<option class="texteRouge"><?php echo __T('G_GDR_BUILDER_THERE_IS_NO_DATA_IN_YOUR_GRAPH')?></option>
				</select>
				<input type="hidden" id="hidden_gis_based_on" name="hidden_gis_based_on" value="<?php echo $graph["gis_based_on"];?>"/>
			</div>
		</td>
	</tr>
	<tr>
		<td class="fieldname"><?php echo __T('G_GDR_BUILDER_DEFAULT_ORDER_BY')?></td>
		<td>
				<select <?php echo $disabled ?> name="default_orderby" id='default_orderby'>
					<option class="texteRouge"><?php echo __T('G_GDR_BUILDER_THERE_IS_NO_DATA_IN_YOUR_GRAPH')?></option>
				</select>
				<input type="hidden" id="hidden_default_orderby" name="hidden_default_orderby" value="<?php echo $graph["default_orderby"];?>"/>

				<select <?php echo $disabled ?> name='default_asc_desc' id='default_asc_desc' class='texteGris'>
					<option value="1" <?php if ($graph["default_asc_desc"]==1) echo  "selected='selected'"; ?>><?php echo __T('G_GDR_BUILDER_ASC')?></option>
					<option value="0" <?php if ($graph["default_asc_desc"]==0) echo  "selected='selected'"; ?>><?php echo __T('G_GDR_BUILDER_DESC')?></option>
				</select>
		</td>
	</tr>
	<tr>
		<td></td>
		<td>
			<?php if ($id_page!='0' && !empty($id_page)) { ?>
				<?php if ($disabled == '') { ?>
					<input type="submit" value="<?php echo __T('G_GDR_BUILDER_SAVE')?>"/>
				<?php } ?>
			<?php } else { ?>
				<input type="submit" value="<?php echo __T('G_GDR_BUILDER_CREATE_NEW_GTM')?>"/>
			<?php } ?>
			<input type="reset" onclick="get_GTM_properties();" value="<?php echo __T('G_GDR_BUILDER_CLOSE')?>"/>
		</td>
	</tr>
</table>
</form>

