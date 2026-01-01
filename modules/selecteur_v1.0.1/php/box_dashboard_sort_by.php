<?php
/**
*	Ce fichier génère la boite "Sort by" du sélecteur.
*
*	Les différents éléments de cette boite sont :
*		- sort by
*		- filter
*
*	@author	SLC - 26/09/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
 *
 *
 *
 *    17/08/2010 MMT
*     - bz 16749 Firefox compatibility use getAttribute for popalt(alt_on_over)
 *
*/

//		==========	DATA		==========

//	Les données qui servent à alimenter la boite "Sort by" du selecteur

// Sort By : données permettant la génération du menu sort by
$sort_by_groups = $selecteur_values;
$maxchar = 40;

//		==========	AFFICHAGE selecteur		==========

/**
*	Cette fonction extrait le raw ou kpi de l'élément donné
*	ex : "CS_CALLS_NB@kpi@asc@853@Nb of CS calls initialized@2" => "kpi"
*
*	@author	SLC - 26/09/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*	@param	string		$value : exemple "CS_CALLS_NB@kpi@asc@853@Nb of CS calls initialized@2"
*	@return	string		"kpi" || "counter"
*/
function getClassFromSortByValue($value)
{
	$vals = explode('@',$value);
	return $vals[0];
}
// test:	echo getClassFromSortByValue("CS_CALLS_NB@kpi@asc@853@Nb of CS calls initialized@2");	// should reply "kpi"

?>


<!--		===== SORT BY =====		-->
<select class="selecteur" name="selecteur[sort_by]" id="selecteur_sort_by" class="zoneTexteStyleXP"
	onchange="changeSortBy(this)" onkeyup="changeSortBy(this)">
	<option value="none"><?= __T('SELECTEUR_NONE')?></option>
	<?php foreach ($sort_by_groups as $label => $options) { 
		$label = (strlen($label) > $maxchar) ? substr($label,0,$maxchar).'...' : $label;
	?>
		<optgroup label="<?=$label?>">
			<?php foreach ($options as $value => $label2 ) { 
				$label2 = (strlen($label2) > $maxchar) ? substr($label2,0,$maxchar).'...' : $label2;
			?>
				<option value="<?=$value?>" <?php if ($value == $this->selecteur['sort_by']) { ?> selected="selected" <?php } ?> class="<?= getClassFromSortByValue($value) ?>"><?=$label2?></option>
			<?php } ?>
		</optgroup>
	<?php } ?>
</select>


<!--  Affichage du nom du graph auqel appartient le kpi / raw sélectionné dans le sort by -->
<div class="selecteur" id="selecteur_sort_by_title" class="texteGrisMini"></div>


<!-- asc / desc -->
<select class="selecteur" name="selecteur[order]" class="zoneTexteStyleXP">
	<option value="asc"><?= __T('SELECTEUR_ASC')?></option>
	<option value="desc" <?php if ($this->selecteur['order'] == 'desc') { ?> selected="selected"<?php } ?>><?= __T('SELECTEUR_DESC')?></option>
</select>


<!-- filter -->
<div class="selecteur">
    <!-- 17/08/2010 MMT bz 16749 Firefox compatibility use getAttribute for popalt(this.alt_on_over) -->
	<img id="selecteur_filter_btn" align="absmiddle" border="0"	style="cursor:pointer"
		onmouseover="popalt(this.getAttribute('alt_on_over'));"
		onmouseout="kill()"
		alt_on_over="<?= __T('SELECTEUR_RAW_KPI_FILTER') ?>"
		onclick="openKpiSelection('<?= __T('SELECTEUR_RAW_KPI_FILTER') ?>')"
		src="<?=$niveau0?>images/icones/kpi_filter_<?php if ($this->selecteur['filter_name']) { ?>on<?php } else { ?>off<?php } ?>.png" />
</div>




<!--		===== FILTER	=====	-->

<input type="hidden" id="selecteur_filter_name"		 name="selecteur[filter_id]"		value="<?=$this->selecteur['filter_id']?>"		/>
<input type="hidden" id="selecteur_filter_operande"	 name="selecteur[filter_operande]"	value="<?=$this->selecteur['filter_operande']?>"	/>
<input type="hidden" id="selecteur_filter_value"		 name="selecteur[filter_value]"		value="<?=$this->selecteur['filter_value']?>"		/>

<script type="text/javascript" src="<?=URL_SELECTEUR?>js/dashboard_sort_by.js"></script>

<div id="div_kpi_filter" style="display:none;">
	<table cellpadding="2" cellspacing="5" align="center" width="100%" rowspan="7">
		<tr>
			<!--	filter_name	-->
			<td>
				<select id="widget_selecteur_filter_name" name="widget_selecteur_filter_name" class="zoneTexteStyleXP" onChange="update_title_filter(this);">
					<?php foreach ($sort_by_groups as $label => $options) { ?>
						<optgroup label="<?=$label?>">
							<?php foreach ($options as $value => $label2 ) { ?>
								<option value="<?=$value?>" <?php if ($value == $this->selecteur['filter_id']) { ?> selected="selected" <?php } ?> class="<?= getClassFromSortByValue($value) ?>"><?=$label2?></option>
							<?php } ?>
						</optgroup>
					<?php } ?>
				</select>
			</td>

			<!--	filter_operande	-->
			<td>
				<select id="widget_selecteur_filter_operande" name="widget_selecteur_filter_operande" size="1" class="zoneTexteStyleXP">
					<option value="none"><?= __T('SELECTEUR_NONE')?></option>
					<?php
						$operandes = array('=','&lt;=','&gt;=','&lt;','&gt;');
						foreach ($operandes as $op) {
							echo "\n<option value='$op'";
							if ($op == htmlentities($this->selecteur[filter_operande]))	echo " selected='selected'";
							echo ">$op</option>";
						}	?>
				</select>
			</td>

			<!--	filter_value	-->
			<td>
				<!-- modif 28/03/2008 christophe : correction bug BZ 3870 : on élargit la zone de saisie de la valeur du filtre.  -->
				<input id="widget_selecteur_filter_value" name="widget_selecteur_filter_value" value="<?=$this->selecteur['filter_value']?>" size="10" class="zoneTexteStyleXP"
					onKeyDown="if(event.keyCode==13){return false}" />
			</td>
		</tr>
		
		<!--	filter_title	-->
		<tr>
			<td colspan="3">
				<div id="widget_selecteur_filter_title" class="texteGrisMini"></div>
			</td>
		</tr>
		
		<!--	boutons de validation	-->
		<tr>
			<td colspan="3" align="left">
				<input type="button" value=" Ok" class="bouton" onclick="checkKpiFilterIsCorrect()">
				&nbsp;&nbsp;
				<input type='button' id='kill_filter_btn' value='Reset' class='bouton' onclick="removeKpiFilter('<?=$niveau0?>');closeKpiselection();">
			</td>
		</tr>
	</table>
</div>

<script type="text/javascript">
	update_title_filter($('widget_selecteur_filter_name'));
	update_btn();
</script>
