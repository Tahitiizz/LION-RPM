<?php
/*
	02/11/2009 GHX
		- Les KPI identiques ne peuvent sélectionnés 2 fois, 
			-> le plus simple a générer c'est de n'afficher qu'une seul le fois KPI dans la liste de gauche
			-> on fait la vérification uniquement sur le kpi_name et pas sur le label du kpi
		- Les noms des KPI ne sont modifiés contrairement au nom des Compteurs
	04/11/2009 GHX
		- Mauvaise prise en compte des doublons
	19/03/2010 NSE bz 14531
		- on ajoute l'id du produit dans les tests de façon à différencier les kpi de mêmes noms provenant de produits différents.
 *  15/09/2010 NSE bz 17019 : double clic KO sous FF pour affichage des commentaires :
 *          - sous Firefox, l'attribut comment pour les balises option ne sont pas implémentées,
 *          - utilisation de title à la place : non fonctionnel sur les raw/kpi que l'on change de colonne car les <option> ne sont aps recréés avec l'attribut title
 *          - on passe donc par un tableau associatif, comme dans le module counter activation
*/
?>
<?php
// Liste des compteurs disponible dans la liste de gauche : Available counters
$availableKpis = $mixedKpiModel->getAvailableKpisDependingNaCommon($idFamily);
// Liste des compteurs dans la liste de droite : Selected counters
$selectedKpis = $mixedKpiModel->getKpis($idFamily);
// On récupère la liste des produits pour avoir leur label
$productsInformations = getProductInformations();
?>

<script type='text/javascript' src='js/edit_counters.js'></script>

<div id="container">
	<!-- titre de la page -->
	<div>
		<h1><img src="../images/titres/setup_mixed_kpi.gif" title="Setup Mixed KPI : configuration" /></h1>
	</div>
	
	<div class="tabPrincipal" style="width:1075px;text-align:center;padding:10px;">
		<!-- BACK -->
		<div id="BackToMainPage" class="backToMain"><a href="index.php"><?=__T('A_SETUP_MIXED_KPI_BACK_TO_MAIN')?></a></div>
		<!-- MESSAGE -->
<?php
if ( isset($message) && !empty($message) )
{
	?>
		<div class="remarque">
			<div id="message_box_1" class="okMsg">
				<?=$message?>
			</div>
		</div>
	<?php
}
if ( isset($messageError) && !empty($messageError) )
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
		<form method="post" action="index.php">
			<input type="hidden" name="idFamily" value="<?=$idFamily?>" />
			<div style="width: 1065px;">
				<fieldset>
					<legend><?=__T('A_SETUP_MIXED_KPI_KPIS_SELECTION')?>, <?=FamilyModel::getLabel($idFamily,ProductModel::getIdMixedKpi())?></legend>
					<div>
					<table>
						<tr><th colspan="3"><?=__T('A_SETUP_MIXED_KPI_ACTIVATE_KPIS')?></th></tr>
						<tr>
							<td><?=__T("A_SETUP_MIXED_KPI_AVAILABLE_KPIS")?>
                                <?php // 15/09/2010 NSE bz 17019 : double clic non fonctionnel ?>
								<select name="available" id="available" multiple class="dataSelector" ondblclick="$('tooltip_raws').update(commentaire_rawkpi[this.options[this.selectedIndex].value]);">
									<?php
									// Les KPI identiques ne peuvent sélectionnés 2 fois, 
									// le plus simple a générer c'est de n'afficher qu'une seul le fois KPI dans la liste de gauche
									$doublons = array();
									foreach($availableKpis as $idProduct => $listKpi)
									{
										foreach ( $listKpi as $kpi )
										{
											// 10:41 04/11/2009 GHX
											// On ne prenait pas bien en compte les doublons
											// 19/03/2010 NSE bz 14531 : on ajoute l'id du produit dans les tests de façon à différencier les kpi de mêmes noms provenant de produits différents.
											if ( !in_array(strtolower($kpi['edw_field_name'].'-'.$idProduct), $doublons) )
											{
												// 19/03/2010 NSE bz 14531 : on ajoute l'id du produit dans les tests de façon à différencier les kpi de mêmes noms provenant de produits différents.
												if ( !array_key_exists(strtolower($kpi['edw_field_name'].'-'.$idProduct), $selectedKpis) )
												{
                                                    // 15/09/2010 NSE bz 17019 : attribut comment non standard
													echo '<option value="'.$idProduct.'-'.$kpi['family'].'-'.$kpi['edw_field_name'].'" title="'.htmlentities($kpi['comment'], ENT_QUOTES).'">'.$productsInformations[$idProduct]['sdp_label'].', '.$kpi['family_label'].', '.$kpi['edw_field_name_label'].'</option>';
												}
												// 19/03/2010 NSE bz 14531 : on ajoute l'id du produit dans les tests de façon à différencier les kpi de mêmes noms provenant de produits différents.
												$doublons[] = strtolower($kpi['edw_field_name'].'-'.$idProduct);
											}
										}
									}
									?>
								</select>
							</td>
							<td>
								<br />
								<button type="button" style="width:14px;height:15px;border:0;cursor:pointer" onclick="move_elements(1,'available','selected')">
									<img src="<?=NIVEAU_0?>images/calendar/right1.gif" border="0" />
								</button>
								<br /><br />
								<button type="button" style="width:14px;height:15px;border:0;cursor:pointer" onclick="move_elements(2,'available','selected')">
									<img src="<?=NIVEAU_0?>images/calendar/left1.gif" border="0" />
								</button>
							</td>
							<td><?=__T("A_SETUP_MIXED_KPI_SELECTED_KPIS")?>
                                <?php // 15/09/2010 NSE bz 17019 : double clic non fonctionnel ?>
								<select name="selected" id="selected" multiple class="dataSelector" ondblclick="$('tooltip_raws').update(commentaire_rawkpi[this.options[this.selectedIndex].value]);">
									<?php
									$hiddenSelected = array();
									foreach($selectedKpis as $infoKpi)
									{
										// Si pas d'identifiant produit c'est que le KPI a été créé via l'IHM KPI Builder il ne doit donc pas être affiché ici
										if ( empty($infoKpi['sfr_sdp_id']) )
											continue;
										
										$productLabel = $productsInformations[$infoKpi['sfr_sdp_id']]['sdp_label'];
										$optValue = $infoKpi['sfr_sdp_id'].'-'.$infoKpi['sfr_product_family'].'-'.$infoKpi['edw_field_name'];
										$optComment = htmlentities($infoKpi['comment'], ENT_QUOTES);
										$optText = $productLabel.', '.$infoKpi['product_family_label'].', '.preg_replace('/ - '.$productLabel.'$/', '', $infoKpi['edw_field_name_label']);
										// 15/09/2010 NSE bz 17019 : attribut comment non standard
										echo '<option value="'.$optValue.'" title="'.$optComment.'">'.$optText.'</option>';
										
										$hiddenSelected[] = $optValue;
									}
									?>
								</select>
								<input type="hidden" id="hidden_selected" name="hidden_selected" value="<?php echo implode('|', $hiddenSelected); ?>" />			
							</td>
						</tr>
						<tr>
							<td colspan="3">
								<div id="tooltip_raws" class="infoBox" style="text-align:left;font-size:7pt;">
									<?=__T("A_DATA_EXPORT_ELEMENT_INFO")?>
								</div>
							</td>
						</tr>
					</table>
					<p style="text-align: center;"><input type="submit" value="Save" name="saveKpis" class="bouton" /></p>
					</div>
				</fieldset>
			</div>
		</form>
	</div>
</div>
<script>
    var commentaire_rawkpi = new Array();;
<?php
// 15/09/2010 NSE bz 17019 : création du tableau des détails des raw/kpi
foreach($availableKpis as $idProduct => $listKpi){
    foreach ( $listKpi as $kpi ){
        echo "commentaire_rawkpi['".$idProduct.'-'.$kpi['family'].'-'.$kpi['edw_field_name']."']=\"".htmlentities($kpi['comment'], ENT_QUOTES)."\";\n";
    }
}
foreach($selectedKpis as $infoKpi){
    echo "commentaire_rawkpi['".$infoKpi['sfr_sdp_id'].'-'.$infoKpi['sfr_product_family'].'-'.$infoKpi['edw_field_name']."']=\"".htmlentities($infoKpi['comment'], ENT_QUOTES)."\";\n";
}
?>
</script>