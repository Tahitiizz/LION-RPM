<?php
/*
	02/11/2009 GHX
		- Les familles avec du troisieme axe ne doivent pas apparaitre
		
	02/12/2009 BBX
		- Ajout de la fonction checkValues() à la validation du formulaire. BZ 13169
	23/04/2010 NSE bz 15057 : 
		- ajout du trigramme devant le nom du produit
*/
?>
<script type='text/javascript' src='js/family_add.js'></script>

<script type="text/javascript">
// Préparation des mesages JS
// Dans le cadre du bug 13169
var A_SETUP_MIXED_KPI_FAMILY_LABEL_NOT_CORRECT = "<?=__T('A_SETUP_MIXED_KPI_FAMILY_LABEL_NOT_CORRECT')?>";
</script>

<div id="container">
	<!-- titre de la page -->
	<div>
		<h1><img src="../images/titres/setup_mixed_kpi.gif" title="Setup Mixed KPI : configuration" /></h1>
	</div>
	<br />

	<div class="tabPrincipal" style="width:1050px;text-align:center;padding:10px;">
		<!-- BACK -->
		<div id="BackToMainPage" class="backToMain"><a href="index.php"><?=__T('A_SETUP_MIXED_KPI_BACK_TO_MAIN')?></a></div>
		<!-- HELP -->
		<?php if(isset($idFamily)){?>
		<div class="remarque">
				<img src="<?=NIVEAU_0?>images/icones/information.png" border="0" />
			<div id="help_box_1" class="infoBox">
				<?=__T('A_SETUP_MIXED_KPI_DESELECT_FAMILY_WITH_RAW_KPI')?>
			</div>
		</div>
		<br /><?php
	}?>
		<!-- MESSAGE -->
<?php
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
			<fieldset>
			<legend align="top"> <?=__T('A_SETUP_MIXED_KPI_EDIT_FAMILY')?></legend>
				<form action="index.php" method="post" onsubmit="return checkValues(this);">
					<p><?=__T('A_SETUP_MIXED_KPI_FAMILY_LABEL')?> <input type="text" name="familyName" value="<?=isset($idFamily)?FamilyModel::getLabel($idFamily,ProductModel::getIdMixedKpi()):__T('A_SETUP_MIXED_KPI_FAMILY_LABEL')?>" /></p>
					<div id="selectFamilies">
						<p class="titre"><?=__T('A_SETUP_MIXED_KPI_SELECT_NA_FAMILIES')?></p>
						<!-- liste des NA levels en commun -->
						<br /><div id="list_na_levels">
								<img src='../builder/images/transmit_blue.png' onmouseover="popalt('<?php echo __T('G_GDR_BUILDER_NETWORK_AGGREGATION_LEVELS'); ?>')" width='16' height='16' align='absmiddle'/> <?php echo __T('G_GDR_BUILDER_NETWORK_AGGREGATION_LEVELS_IN_COMMON'); ?>
								<ul id="na_levels_in_common">
									<br/>
								</ul>
						</div>
						
						<div id="icons"><img id="show_na_levels" src="../builder/images/transmit_blue_off.png" onmouseover="popalt('<?=__T('G_GDR_BUILDER_SHOW_NA_LEVELS')?>')" onclick="show_hide_na_levels();" width="16" height="16"></div>
						
						<div id="productList">
<?php
$familiesWithsCounters = array();
$familiesWithsKpis = array();
// si on modifie une famille existante
if ( isset($idFamily) )
{
	echo '<input type="hidden" name="idFamily" value="'.$idFamily.'" />';
	$query_na = "SELECT * FROM sys_definition_mixedkpi WHERE sdm_id='{$idFamily}'";
        // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
	$database = Database::getConnection(ProductModel::getIdMixedKpi());
	$result = $database->execute($query_na);
	// on construit le tableau des familles sélectionnées par produit à partir de la BD
	while ( $network = $database->getQueryResults($result,1) )
	{
		$selectedFamilies[$network['sdm_sdp_id']][] = $network['sdm_family'];
	}
	
	$familiesWithsCounters = $mixedKpiModel->getFamiliesWithCountersByProduct($idFamily);
	$familiesWithsKpis = $mixedKpiModel->getFamiliesWithKpisByProduct($idFamily);
}
// pour chaque produit, on affiche la liste des familles
$products = ProductModel::getActiveProducts();
$nbProduct = 0;
foreach ( $products as $p )
{
	// sauf pour le produit Mixed KPI
	if ( $p['sdp_id'] != ProductModel::getIdMixedKpi() && !ProductModel::isBlankProduct( $p['sdp_id'] ) )
	{
		// 23/04/2010 NSE bz 15057 : ajout du trigramme
		echo '<div class="productDetails"><b>'.$p['sdp_trigram'].', '.$p['sdp_label'].'</b><ul>';
		$naLabelList = getNaLabelListForProduct('na','',$p['sdp_id']);
		foreach ( $naLabelList as $famille => $naList )
		{
			// 11:01 02/11/2009 GHX
			// Si la famille possède un troisieme axe, elle ne doit pas apparaitre
			if ( get_axe3($famille, $p['sdp_id'])  )
				continue;
			
			$disabled = '';
			if ( array_key_exists($p['sdp_id'], $familiesWithsCounters) )
			{
				if ( in_array($famille, $familiesWithsCounters[$p['sdp_id']]) )
					$disabled = ' disabled';
			}
			elseif ( array_key_exists($p['sdp_id'], $familiesWithsKpis) )
			{
				if ( in_array($famille, $familiesWithsKpis[$p['sdp_id']]))
					$disabled = ' disabled';
			}
			if(!empty($disabled)){
				echo '<input type="hidden" value="'.$p['sdp_id'].'-'.$famille.'" name="familybox[]" />';
			}
			echo '<li>';
			echo '<input type="checkbox" style="border: none;background:none;" onclick="get_na_levels_in_common()" value="'.$p['sdp_id'].'-'.$famille.'" name="familybox[]" id="familybox'.$p['sdp_id'].'-'.$famille.'"'.( isset($selectedFamilies[$p['sdp_id']]) && in_array($famille,$selectedFamilies[$p['sdp_id']]) ?' checked' : '' ).' '.$disabled.' />';
			// Popalt
			$msg = __T('A_SETUP_MIXED_KPI_CHECKBOX_CHOOSE_FAMILY');
			$popalt = 'onMouseOver="popalt(\''.$msg.'\')" onMouseOut="kill()"';
			echo '<label for="familybox'.$p['sdp_id'].'-'.$famille.'" '.$popalt.' style="cursor:help">'.FamilyModel::getLabel($famille,$p['sdp_id']).'</label>';
			
			echo '<div class="na_levels">'.implode(', ',$naList).'</div >';
			echo '</li>';
		}
		echo '</ul></div>';
		$nbProduct++;
		if($nbProduct % 3 == 0)
			echo '<div class="productLineSeparator"></div>';
	}
}
?>
						</div>
						<style type="text/css" id='na_level_css'>div.na_levels {display:none;} li{list-style: none;}</style>
					</div>
					<p style="text-align: center;"><input type="submit" name="newfamily" value="<?=__T('A_SETUP_MIXED_KPI_BT_FAMILY_SAVE')?>" class="bouton" title="<?=__T('A_SETUP_MIXED_KPI_BT_FAMILY_SAVE')?>" class="boutton" /></p>
				</form>
			</fieldset>
		</div>
	</div>
</div>
<?php
if ( isset($idFamily) )
{
	?>
	<script>get_na_levels_in_common();</script>
	<?php
}
?>