<?php
/**
*	@cb4100@
*	- Creation SLC	 29/10/2008
*
*	Cette page renvoie le formulaire d'édition des properties d'un element
*
*	30/01/2009 GHX
*		- modification des requetes SQL pour mettre des valeurs entre cote [REFONTE CONTEXTE]
*	21/09/2009 GHX
*		- Correction du BZ 11429 [REC][T&A Cb 5.0][Dashboard] : problème d'affichage pour un graph de type 'line' avec line_design='none'
*			-> Suppression du type "none" pour les graphes de types lignes
*	30/11/2009 MPR 
*		- Correction du bug 13105 : On remplace les ' par des espaces
*	03/12/2009 GHX
*		- Correction du BZ 12207 [OMC bug clone][Dashboard display] : Label des séries d'un graphe tronquée dans la légende si label long
*			-> Ajout d'une limite de 50 caractères sur le label d'une série
*
*	09/06/10 YNE/FJT : SINGLE KPI
*/

$intranet_top_no_echo = true;
include_once('common.inc.php');


// on recupère les données envoyées
// 10:18 30/01/2009 GHX
// Suppression du formatage en INT
$id = $_POST['id'];

// on va chercher le graph pour vérifier qu'on a les droits d'écriture dessus
$query = " --- on va chercher le graph 
	SELECT * FROM sys_pauto_page_name WHERE id_page IN 
	 ( SELECT id_page FROM sys_pauto_config WHERE id= '$id' )
";
$graph = $db->getrow($query);
if (allow_write($graph)) {
	$disabled = '';
} else {
	$disabled = 'disabled="disabled"';
}



// on va chercher les infos du plot dans graph_data
$query = " --- we get the data of id_data=$id
	select * from graph_data where id_data='$id'";
$plot = $db->getrow($query);

// les champs "color" et "filled_color" sont stockés au format JPGraph : #FFFFFF@1 --> on les explose
list($plot["color"],$devnul) = explode('@',$plot["color"],2);
list($plot["filled_color"],$plot["filled_transparency"]) = explode('@',$plot["filled_color"],2);

// list of possible display_type
$display_types = array(
	"line"			=> "Line",
	"bar"				=> "Bar",
	"cumulatedbar"		=> "Cumulated Bar",
	"cumulatedline"	=> "Cumulated Line"
);

?>


<form id="elem_prop_form__<?php echo $id ?>" action="graph_ajax_get_elem_properties.php" method="post" style="margin:0"
	onsubmit="setTimeout('submit_elem_prop(\'<?php echo $id ?>\')',10);return false;"
	>
	
	<table cellpadding="2">
		<tr>
			<td class="fieldname"><?php echo __T('G_GDR_BUILDER_DATA_LEGEND')?></td>
			<!-- maj 30/11/2009 MPR - Correction du bug 13105 : On remplace les ' par des espaces --> 
			<td colspan="2"><input <?php echo $disabled ?> name="data_legend" value="<?php echo str_replace("'", " ", $plot["data_legend"] )?>" style="width:360px;" maxlength="50" /></td>
		</tr>
		<tr class='hide_if_pie'>
			<td class="fieldname"><?php echo __T('G_GDR_BUILDER_DISPLAY_AS')?></td>
			<td>
				<select <?php echo $disabled ?> name="display_type" id="display_type__<?php echo $id ?>" onchange="update_line_design('<?php echo $id ?>');">
				<?php foreach ($display_types as $val => $label) {
						echo "<option id='display_type__{$val}__{$id}' value='$val'";
						if ($val == $plot["display_type"]) echo " selected='selected'";
						echo ">$label</option>";
					}
				?>
				</select>
			</td>
			<td>
				<span id="line_design__<?php echo $id ?>" 
				<?php if (($plot["display_type"] != 'line') && ($plot["display_type"] != 'cumulatedline')) { echo "style='display:none;'";} ?>
				>
					&nbsp;&nbsp;<?php echo __T('G_GDR_BUILDER_LINE_DESIGN')?>
						<select name="line_design" <?php echo $disabled ?>>
							<option value="square"	<?php if ($plot["line_design"] == 'square') echo "selected='selected'"; ?>><?php echo __T('G_GDR_BUILDER_SQUARE')?></option>
							<option value="circle"	<?php if ($plot["line_design"] == 'circle') echo "selected='selected'"; ?>><?php echo __T('G_GDR_BUILDER_CIRCLE')?></option>
						</select>
				</span>
			</td>
		</tr>
		<tr class='hide_if_pie_and_single_kpi'>
			<td class="fieldname"><?php echo __T('G_GDR_BUILDER_POSITION_ON_YAXIS')?></td>
			<td>
				<input <?php echo $disabled ?> type="radio" name="position_ordonnee" value="left" id="pos_Yaxis__<?php echo $id ?>_left" <?php if ($plot["position_ordonnee"]=='left') { echo "checked='checked'";} ?>/>
					<label for="pos_Yaxis__<?php echo $id ?>_left"><?php echo __T('G_GDR_BUILDER_LEFT_FRONT')?></label>
			</td>
			<td>
				&nbsp;<input <?php echo $disabled ?> type="radio" name="position_ordonnee" value="right" id="pos_Yaxis__<?php echo $id ?>_right" <?php if ($plot["position_ordonnee"]=='right') { echo "checked='checked'";} ?>/>
					<label for="pos_Yaxis__<?php echo $id ?>_right"><?php echo __T('G_GDR_BUILDER_RIGHT_BACK')?></label>
			</td>
		</tr>
		<tr class='hide_if_pie_and_single_kpi'>
			<td class="fieldname"><?php echo __T('G_GDR_BUILDER_STROKE_COLOR')?></td>
			<td>
				<input <?php echo $disabled ?> type="button" class="colorPickerBtn" name="color_btn" id="color_btn__<?php echo $id ?>" style="background-color:<?php echo $plot["color"] ?>;cursor:pointer;"/>
				<input <?php echo $disabled ?> type='hidden' name='color' id='color__<?php echo $id ?>' value='<?php echo $plot["color"] ?>'/>
			</td>
		</tr>
		<tr class='hide_if_single_kpi'>
			<td class="fieldname"><?php echo __T('G_GDR_BUILDER_FILL_COLOR')?></td>
			<td>
				<input <?php echo $disabled ?> type="button" class="colorPickerBtn" name="fill_color_btn" id="fill_color_btn__<?php echo $id ?>" style="background-color:<?php echo $plot["filled_color"] ?>;cursor:pointer;"/>
				<input <?php echo $disabled ?> type='hidden' name='fill_color' id='fill_color__<?php echo $id ?>' value='<?php echo $plot["filled_color"] ?>'/>
			</td>
			<td>
				&nbsp;&nbsp;<?php echo __T('G_GDR_BUILDER_TRANSPARENCY')?>
				<select name="fill_transparency" <?php echo $disabled ?>>
				<?php for ($i=0; $i<11; $i++) {
						if (($plot["filled_transparency"] * 10) == $i) {
							echo "<option value='".($i / 10)."' selected='selected'>".($i*10)."%</option>";
						} else {
							echo "<option value='".($i / 10)."'>".($i*10)."%</option>";
						}
				}?>
				</select>
			</td>
		</tr>
		<tr><td></td><td>
			<?php if (!$disabled) { ?><input type="submit" value="<?php echo __T('G_GDR_BUILDER_SAVE')?>"/><?php } ?>
			<input type="reset" value="<?php echo __T('G_GDR_BUILDER_CLOSE')?>" onclick="prop = $('elem_prop__<?php echo $id ?>');prop.style.display='none';var myImg = prop.parentNode.getElementsByClassName('info')[0].getElementsByTagName('IMG')[0];myImg.src = myImg.src.replace(/.png/gi,'_off.png');" /></td></tr>
	</table>


</form>

