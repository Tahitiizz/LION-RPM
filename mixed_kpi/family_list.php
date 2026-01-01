<?php
/*
	02/11/2009 GHX
		- Ajout du bouton "Select Dashboards"
	17/11/2009 GHX
		- Correction d'un probl�me javascript quand on clique sur le bouton delete
	11/12/2009 GHX
		- Correction du BZ 13312 [CB 5.0.2.1][Setup Miwed KPI] Changing minimum TA
			-> Modification de l'ID du message display dans la fonction JS taFormTest
	26/04/2010 NSE bz 15188 
		- ajout du message d'aide pour cr�er une nouvelle famille
*/
?>
<?php 
MixedKpiModel::configureConnections();

$messageError = $mixedKpiModel->checkConfigTAInAllProducts();
?>
<script>
var laction;
function familyFormTest(theForm){
	if (laction == "delete")  {
		laction = '';
		deletefamily = '';
		return confirm("<?=__T('A_SETUP_MIXED_KPI_JS_CONFIRM_DELETE_FAMILY');?>");
	}
}
function taFormTest(theForm){
	return confirm("<?=__T('A_SETUP_MIXED_KPI_JS_CONFIRM_CHANGE_TA_MIN');?>\n<?=__T('A_E_SETUP_MIXED_KPI_LOOSE_DATA');?>");
}
</script>

<div id="container">
	<!-- titre de la page -->
	<div>
		<h1><img src="../images/titres/setup_mixed_kpi.gif" title="Setup Mixed KPI : configuration" /></h1>
	</div>
	<br />

	<div class="tabPrincipal" style="width:1050px;text-align:center;padding:10px;">

		<!-- HELP -->
		<div class="remarque">
				<img src="<?=NIVEAU_0?>images/icones/information.png" border="0" />
			<div id="help_box_1" class="infoBox">
				<u><?=__T('A_SETUP_MIXED_KPI_HELP_FAMILY_GENERAL_INFO')?></u><br />
				<ul>
				<li><?=__T('A_SETUP_MIXED_KPI_FAMILY_CONF')?></li>
				<?php // 26/04/2010 NSE bz 15188 : ajout du message d'aide pour cr�er une nouvelle famille ?>
				<li><?=__T('A_SETUP_MIXED_KPI_FAMILY_CONF_ADD')?></li>
				<li><?=__T('A_SETUP_MIXED_KPI_HELP_FAMILY_CHANGE_LABEL')?></li>
				<li><?=__T('A_SETUP_MIXED_KPI_HELP_FAMILY_SELECT_FAMILIES_FROM_PRODUCTS')?></li>
				<li><?=__T('A_SETUP_MIXED_KPI_HELP_FAMILY_SELECT_NA')?></li>
				<li><?=__T('A_SETUP_MIXED_KPI_HELP_FAMILY_NA_ERRORS_RETRIEVE')?></li>
				<li><?=__T('A_SETUP_MIXED_KPI_HELP_FAMILY_CANT_EDIT_RAW_NO_NA')?></li>
				<li><?=__T('A_SETUP_MIXED_KPI_HELP_SETUP_CONNECTION_TO_PRODUCTS')?> <img src="<?=NIVEAU_0?>images/icones/bullet_go.png" border="0" /> <a href="<?=NIVEAU_0?>myadmin_setup/intranet/php/affichage/setup_connection_index.php?product=<?=ProductModel::getIdMixedKpi()?>"><?=__T('A_SETUP_MIXED_KPI_INFO_SETUP_CO_LINK')?></a>.</li>
				</ul>
				<u><?=__T('A_SETUP_MIXED_KPI_HELP_FAMILY_MIN_TA')?></u><br />
				<ul>
				<li><?=__T('A_SETUP_MIXED_KPI_HELP_FAMILY_MODIFY_TA_MIN')?></li>
				<li><?=__T('A_SETUP_MIXED_KPI_HELP_FAMILY_TA_MIN_LOOSE_DATA')?></li>
				<li><?=__T('A_SETUP_MIXED_KPI_HELP_FAMILY_TA_MIN_WHOLE_PRODUCT')?></li>
				</ul>
				
			</div>
		</div>
		<br />
		<!-- MESSAGE -->
<?php
if ( isset($message)&&!empty($message) )
{
	?>
		<div class="remarque">
			<div id="message_box_1" class="okMsg">
				<?=$message?>
			</div>
		</div>
		<br />
	<?php
}

// 20/12/2010 BBX
// On r�cup�re la liste des produits dasactiv�s
// BZ 18510
$inactiveProducts = ProductModel::getInactiveProducts();

// on r�cup�re le liste des familles du produit
$familyList = getFamilyList(ProductModel::getIdMixedKpi());
$notConfiguredFamilies=0;
// pour chaque famille
foreach($familyList as $family => $familyLabel){
	if(FamilyModel::nbNA($family,ProductModel::getIdMixedKpi())==0){
		$notConfiguredFamilies++;
	}
}
if($notConfiguredFamilies>0){
	$messageError .= __T('E_SETUP_MIXED_KPI_SELECT_NA_FOR_ALL_FAMILIES');
}
if(isset($messageError)&&!empty($messageError))
{
	?>
		<div class="remarque">
			<div class="errorMsg">
				<?php echo $messageError; ?>
			</div>
		</div>
		<br />
	<?php
}
?>	
		<div>
			<form method="post" action="index.php" onSubmit="return taFormTest(this);"  style="float:left">
				<?=__T('A_SETUP_MIXED_KPI_LABEL_TA_MIN')?>:
				<select name="ta_min">
				<?php
				// R�cup�re la liste des produits
				$products = ProductModel::getActiveProducts();
				// Choix entre Day et Hour (si disponible)
				// pour chaque produit, regarder si hour est le niveau le plus bas
				$ta_min='day';
				$idMK = ProductModel::getIdMixedKpi();
				foreach ($products as $p)
				{
					$get_ta_min = get_ta_min($p['sdp_id']);
					if ($get_ta_min == 'hour' && $p['sdp_id'] != $idMK )
					{
						$ta_min = $get_ta_min;
					}
				}
				// le niveau le plus bas configur� actuellement sur le produit Mixd KPI
				$taminprod = $mixedKpiModel->getTaMin();
				if ( $ta_min == 'hour' )
				{
					$selected = ($ta_min == $taminprod) ? ' selected="selected"' : '';
					echo '<option value="hour"'.$selected.'>'.getTaLabel('hour').'</option>';
				}
				$selected = ('day' == $taminprod) ? ' selected="selected"' : '';
				echo '<option value="day"'.$selected.'>'.getTaLabel('day').'</option>';
				?>
				</select>
				<input type="submit" value="<?=__T('A_SETUP_MIXED_KPI_BT_SAVE_TA_MIN');?>" name="submit" class="bouton" title="<?=__T('A_SETUP_MIXED_KPI_BT_SAVE_TA_MIN')?>" />
			</form>
			<form action="index.php" method="post" style="float:right">
				<input type="submit" name="selectDashboards" value="<?php echo __T('A_SETUP_MIXED_KPI_BT_SELECT_DASHBOARDS'); ?>" class="bouton" title="<?=__T('A_SETUP_MIXED_KPI_BT_SELECT_DASHBOARDS')?>" />
			</form>
		</div>
		<br />
		<div>
			<fieldset>
				<legend align=top> <?=__T('A_SETUP_MIXED_KPI_FAMILY_LIST');?></legend>
				<form action="index.php" method="post">
						<p style="text-align: center;"><input type="submit" name="addfamily" value="<?=__T('A_SETUP_MIXED_KPI_BT_ADD_FAMILY')?>" class="bouton" title="<?=__T('A_SETUP_MIXED_KPI_BT_ADD_FAMILY')?>" /></p>
				</form>
				<table width="100%">
					<tr>
						<th align="left"><?=__T('A_SETUP_MIXED_KPI_FAMILY_NAME')?></th>
						<th align="left"><?=__T('A_E_SETUP_MIXED_KPI_NA_MIN')?></th>
						<th align="left"><?=__T('A_SETUP_MIXED_KPI_ACTIONS')?></th>
					</tr>
	<?php
		// pour chaque famille
		foreach($familyList as $family => $familyLabel){

                    // 20/12/2010 BBX
                    // R�cup�ration et test des produits li�s
                    // BZ 18510
                    $blockedFamily = false;
                    foreach($mixedKpiModel->getFamiliesByProduct($family) as $pId => $famInfos)
                    {
                        foreach($inactiveProducts as $p)
                        {
                            if($p['sdp_id'] == $pId)
                                $blockedFamily = $p['sdp_label'];
                        }
                    }

	?>			
					<form method="post" action="index.php" onSubmit="return familyFormTest(this);"><tr>
						<td style="width: 25%;"><?=$familyLabel?></td>
						<td style="width: 25%;"><?=getNetworkLabel(get_network_aggregation_min_from_family($family,ProductModel::getIdMixedKpi()),$family,ProductModel::getIdMixedKpi())?></td>
                                                <?php
                                                // 20/12/2010 BBX
                                                // Ajout du cas o� l'�dition est bloqu�e
                                                // Lorsque des produits li�s � la famille
                                                // Sont d�sactiv�s
                                                // BZ 18510
                                                if($blockedFamily) {
                                                ?>
						<td style="width: 50%;" style="text-align: right;">
                                                    <div style="color:red;font-weight:bold"><?=__T('A_SETUP_MIXED_KPI_FAMILY_LOCKED',$blockedFamily)?></div>
                                                </td>
                                                <?php } else { ?>
						<td style="width: 50%;" style="text-align: right;">
							<ul class="actions">
								<li><a href="index.php?editFamily=1&idFamily=<?=$family?>" title="<?=__T('A_SETUP_MIXED_KPI_BT_ACTION_EDIT_FAMILY')?>"><?=__T('A_SETUP_MIXED_KPI_BT_ACTION_EDIT_FAMILY')?></a></li>
								<li><a href="index.php?editna=1&idFamily=<?=$family?>" title="<?=__T('A_SETUP_MIXED_KPI_BT_ACTION_EDIT_NA')?>"><?=__T('A_SETUP_MIXED_KPI_BT_ACTION_EDIT_NA')?></a></li>
								<li><?php
									if(FamilyModel::nbNA($family,ProductModel::getIdMixedKpi())!=0){
										echo '<a href="index.php?editraw=1&idFamily='.$family.'" title="'.__T('A_SETUP_MIXED_KPI_BT_ACTION_EDIT_RAW').'">'.__T('A_SETUP_MIXED_KPI_BT_ACTION_EDIT_RAW').'</a>';
									}
									else{
										echo __T('A_SETUP_MIXED_KPI_BT_ACTION_EDIT_RAW');
									}
									echo '</li> <li>';
									if(FamilyModel::nbNA($family,ProductModel::getIdMixedKpi())!=0){
										echo '<a href="index.php?editkpi=1&idFamily='.$family.'" title="'.__T('A_SETUP_MIXED_KPI_BT_ACTION_EDIT_KPI').'">'.__T('A_SETUP_MIXED_KPI_BT_ACTION_EDIT_KPI').'</a>';
									}
									else{
										echo __T('A_SETUP_MIXED_KPI_BT_ACTION_EDIT_KPI');
									}
								?></li>
								<li><input type="hidden" name="idFamily" value="<?=$family?>" />
								<input type="hidden" name="deletefamily" value="" />
								<input type="image" src="<?=NIVEAU_0?>images/icones/drop.gif" title="<?=__T('A_SETUP_MIXED_KPI_BT_ACTION_DELETE')?>" onclick="laction='delete';" style="border: none;margin-left: 50px;" /></li>
							</ul>
						</td>
                                                <?php } ?>
					</tr></form><?php
			}?>
				</table>
			</fieldset>
		</div>
	</div>
</div>