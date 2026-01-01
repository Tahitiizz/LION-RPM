<?php
/*
 *  @cb5.0.4.00
 *
 *  17/08/2010 NSE DE Firefox bz 17020 : bouton synchronize non fonctionnel
 *  15/09/2010 NSE bz 17019 : double clic KO sous FF pour affichage des commentaires :
 *          - sous Firefox, l'attribut comment pour les balises option ne sont pas implémentées,
 *          - utilisation de title à la place : non fonctionnel sur les raw/kpi que l'on change de colonne car les <option> ne sont aps recréés avec l'attribut title
 *          - on passe donc par un tableau associatif, comme dans le module counter activation
 */
?><?php
/*
	02/11/2009 GHX
		- Le nom du compteur a changï¿½ 
			-> "<edw_field_name>_<family>_<id product>_mk" au lieu de "<edw_field_name>_<family>_mk"
	09/12/2009 GHX
		- Correction du BZ 13176 [REC][MIXED-KPI] : plusieurs fois le mï¿½me compteur
*/
?>
<?php
// Liste des compteurs disponible dans la liste de gauche : Available counters
$availableRaws = $mixedKpiModel->getAvailableCountersDependingNaCommon($idFamily);
// Liste des compteurs dans la liste de droite : Selected counters
$selectedRaws = $mixedKpiModel->getCounters($idFamily);
// On rï¿½cupï¿½re la liste des produits pour avoir leur label
$productsInformations = getProductInformations();

$idProdMK = ProductModel::getIdMixedKpi();

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
		<!-- HELP -->
		<div class="remarque">
				<img src="<?=NIVEAU_0?>images/icones/information.png" border="0" />
			<div id="help_box_1" class="infoBox">
				<?=__T('A_H_SETUP_MIXED_KPI_ACTIVE_RAW_BY_SELECTING')?>
			</div>
		</div>
		<br />
		
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
// 17/08/2010 NSE DE Firefox bz 17020 : ajout de id
// 17/09/2010 BBX
// Ajout de l'id sur le formulaire
// BZ 17020
?>
                <form method="post" action="index.php" name="synchroValid" id="synchroValid">
                    <input type="hidden" id="idFamily" name="idFamily" value="<?=$idFamily?>" />
                    <input type="hidden" id="SynchroMsgError" name="SynchroMsgError" />
                    <input type="hidden" name="synchroResult" id="synchroResult" value="0" />
                </form>
		<form method="post" action="index.php">
			<input type="hidden" name="idFamily" value="<?=$idFamily?>" />
			<div style="width: 1065px;">
				<fieldset>
					<legend><?=__T('A_SETUP_MIXED_KPI_COUNTERS_SELECTION')?>, <?=FamilyModel::getLabel($idFamily,$idProdMK)?></legend>
					<div>
					<table>
						<tr><th colspan="3"><?=__T('A_SETUP_MIXED_KPI_ACTIVATE_RAWS')?></th></tr>
						<tr>
							<td><b><?=__T('A_SETUP_MIXED_KPI_AVAILABLE_COUNTERS');?></b>
                                <?php // 15/09/2010 NSE bz 17019 : double clic non fonctionnel ?>
								<select name="available" id="available" multiple class="dataSelector" ondblclick="$('tooltip_raws').update(commentaire_rawkpi[this.options[this.selectedIndex].value]);">
								<?php
								// >>>>>>>>>>
								// 17:26 09/12/2009 GHX
								// Correction du BZ 13176
								// On ne doit pas afficher plusieurs fois le mÃªme compteur
								$rawAlreadyDisplay = array();
								
                                                                foreach($availableRaws as $idProduct => $listRaw)
								{
									foreach ( $listRaw as $raw )
									{
										// 29/03/2010 BBX : le code compteur est maintenant composï¿½ du trigramme et du code famille
										//$counterCode = $productsInformations[$idProduct]['sdp_trigram'].'_'.$raw['family'].'_'.$raw['edw_field_name'];
										$counterCode = strtolower(MixedKpiModel::getPrefix($idProduct, $raw['family'])).$raw['edw_field_name'];
                                                                                
                                                                                $counterId = $raw['id_ligne'];
										// 29/03/2010 BBX : utilisation du trigramme au lieu du label de la famille
                                        // 15/09/2010 NSE bz 17019 : attribut comment non standard
										if ( !array_key_exists($counterCode, $selectedRaws) && !in_array($counterCode, $rawAlreadyDisplay) )
											echo '<option value="'.$idProduct.'-'.$counterId.'" title="'.htmlentities($raw['comment'], ENT_QUOTES).'">'.$productsInformations[$idProduct]['sdp_trigram'].', '.$raw['family_label'].', '.$raw['edw_field_name_label'].'</option>';
										$rawAlreadyDisplay[] = $counterCode;
									}
								}
								// <<<<<<<<<<
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
							<td> <b><?=__T('A_SETUP_MIXED_KPI_SELECTED_COUNTERS');?></b>
                                <?php // 15/09/2010 NSE bz 17019 : double clil non fonctionnel ?>
								<select name="selected" id="selected" multiple class="dataSelector" ondblclick="$('tooltip_raws').update(commentaire_rawkpi[this.options[this.selectedIndex].value]);">
								<?php
								$hiddenSelected = array();
								// 29/03/2010 BBX : utilisation du trigramme au lieu du label du produit
								foreach($selectedRaws as $infoRaw)
								{
									// Id du compteur
									$idCompteur = $infoRaw['old_id_ligne'];
									// Rï¿½cupï¿½ration du trigramme
									$productTrigram = $productsInformations[$infoRaw['sfr_sdp_id']]['sdp_trigram'];
									// Rï¿½cupï¿½ration du commentaire
									$optComment = htmlentities($infoRaw['comment'], ENT_QUOTES);
									// Construction de la ligne affichï¿½e
									$optText = $productTrigram.', '.$infoRaw['product_family_label'].', '.trim(preg_replace('/^'.$productTrigram.' '.$infoRaw['sfr_product_family'].' /', '', $infoRaw['edw_field_name_label']));
									// Valeur de l'ï¿½lï¿½ment
									$optValue = $infoRaw['sfr_sdp_id'].'-'.$idCompteur;
									// Affichage de la ligne
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
								<div id="tooltip_raws" class="infoBox">
									<?=__T("A_DATA_EXPORT_ELEMENT_INFO")?>
								</div>
							</td>
						</tr>
					</table>
					<p style="text-align: center;">
						<input type="submit" value="Save" name="saveCounters" class="bouton" />
						<input type="button" value="Synchronize" name="SynchronizeCounters" class="bouton" onclick="synchronization();" onmouseover='popalt("Synchronize the definition of counters aggregation with parent products")'/>
					</p>
					</div>
				</fieldset>
			</div>
                        <div id="div_contener">
                        </div>
		</form>
	</div>
</div>

<script>
    hideSynchroWindow();
    var commentaire_rawkpi = new Array();;
<?php
// 15/09/2010 NSE bz 17019 : création du tableau des détails des raw/kpi
foreach($availableRaws as $idProduct => $listRaw){
    foreach ( $listRaw as $raw ){
        echo "commentaire_rawkpi['".$idProduct.'-'.$raw['id_ligne']."']=\"".htmlentities($raw['comment'], ENT_QUOTES)."\";\n";
    }
}
foreach($selectedRaws as $infoRaw){
    echo "commentaire_rawkpi['".$infoRaw['sfr_sdp_id'].'-'.$infoRaw['old_id_ligne']."']=\"".htmlentities($infoRaw['comment'], ENT_QUOTES)."\";\n";
}
?>
</script>