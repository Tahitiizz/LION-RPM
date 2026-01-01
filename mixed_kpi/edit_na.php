<?php
/*
	02/11/2009 GHX
		- Ajout d'une fonctionnalitée JS qui permet de ne plus désélectionner un NA qui est la source d'un autre NA
			-> N'EST PAS FONCTIONNEL SOUS IE donc code supprimé
	10/12/2009 GHX
		- Correction BZ 13185 [REC][MIXED-KPI] : affichage de NA vides pour "NA source for Aggregation"
			-> Ajout de 2 array_key_exists
	04/01/2010 GHX
		- Correction du BZ 13612 Problème de modification des NA lors d'un ajout de famille
	17/03/2010 NSE bz 14532 
		- on supprime la Na courante de sa liste des Na source si elle n'est pas min level	
*/
?>
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
		<div class="remarque">
				<img src="<?=NIVEAU_0?>images/icones/information.png" border="0" />
			<div id="help_box_1" class="infoBox">
				<?=__T('A_SETUP_MIXED_KPI_CAN_NOT_DESELECT_MIN_LEVEL')?><br />
				<?=__T('A_SETUP_MIXED_KPI_CHANGE_MIN_LEVEL_LOOSE_DATA')?>
			</div>
		</div>
		<br />
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
		<form name="formNa" method="post" action="index.php" onsubmit="return manageCheckBoxes()">
			<input type="hidden" name="idFamily" value="<?=$idFamily?>" />
			<div>
				<fieldset>
					<legend><?=__T('A_SETUP_MIXED_KPI_NA_SELECTION')?>, <?=FamilyModel::getLabel($idFamily,ProductModel::getIdMixedKpi())?></legend>
					<div>
						<p style="text-align: center;"><input type="submit" id="editFamilies" name="editFamilies" value="<?=__T('A_SETUP_MIXED_KPI_BT_EDIT_CURRENT_FAMILY')?>" class="bouton" /></p>
						<table width="100%">
							<tr>
								<th width="150"><?=__T('A_SETUP_MIXED_KPI_LABEL_LEVEL')?></th>				
								<th><?=__T('A_SETUP_MIXED_KPI_LABEL_LEVEL_MIN')?></th>
								<th><?=__T('A_SETUP_MIXED_KPI_LABEL_LEVEL_USED')?></th>
								<th><?=__T('A_SETUP_MIXED_KPI_LABEL_AGGREGATION')?></th>
							</tr>
					<?php				
					// récupère la liste des produits
					$products = ProductModel::getActiveProducts(); 
					// liste des NA communs aux différents produits
					if(isset($selectedFamilies))
						$na_levels_in_common = FamilyModel::getCommonNaBetweenFamilyAndProducts($selectedFamilies);
					else{
						$na_levels_in_common = $mixedKpiModel->getCommonNaBetweenFamilyAndProducts($idFamily);
					}	
				?>
					<script>
					// na est de la forme used_[na], c'est pourquoi on utilise na.substr(5)
					function selectionner_na(na,label)
					{
					<?php
					// pour chaque Na
					foreach($na_levels_in_common as $aggregCode => $aggregLabel)
						echo 'document.getElementById(\'used_'.$aggregCode.'\').disabled=false;
						document.getElementById(\''.$aggregCode.'_aggregation\').disabled=false;'."
						// 17/03/2010 NSE bz 14532 : on supprime la Na courante de sa liste des Na source si elle n'est pas min level
						if(!document.getElementById('min_".$aggregCode."').checked){
							supprimeElement('".$aggregCode."_aggregation', '".$aggregCode."', label);
						}
";
					?>
						document.getElementById(na).checked=true;
						document.getElementById(na).disabled=true;
						getFieldValue(na.substr(5),label);
						for(i=0;i<document.getElementById(na.substr(5)+'_aggregation').options.length;i++){
							if(document.getElementById(na.substr(5)+'_aggregation').options[i].value == na.substr(5)){
								document.getElementById(na.substr(5)+'_aggregation').options[i].selected=true;
							}
						}
						document.getElementById(na.substr(5)+'_aggregation').disabled=true;
						
						// 10/12/2009 BBX
						// Il faut vérifier les NA source. BZ 13124
						isSource();
					}
					
					function getFieldValue(cible,label)
					{
						// on cache le select correspondant a l'element dont la case n'est pas cochee
						if(document.getElementById('used_'+cible).checked==true)
							document.getElementById(cible+'_aggregation').style.visibility='visible';
						else
							document.getElementById(cible+'_aggregation').style.visibility='hidden';
							
					<?php
					// pour chaque NA
					foreach($na_levels_in_common as $aggregCode => $aggregLabel)
						echo "
					// si le NA est selectionne (case cochee)
					if(document.getElementById('used_'+cible).checked){
						// on regarde si le NA est deja present dans les select
						trouve=0;
						for(i=0;i<document.getElementById('".$aggregCode."_aggregation').options.length;i++){
							if(document.getElementById('".$aggregCode."_aggregation').options[i].value == cible){
								trouve=1;
							}
						}
						// s'il n'est pas encore dans les select, on l'y ajoute
						// et si le select n'appartient pas au niveau selectionne
						if(trouve==0){
							document.getElementById('".$aggregCode."_aggregation').options.length +=1;
							taille = document.getElementById('".$aggregCode."_aggregation').options.length;
							document.getElementById('".$aggregCode."_aggregation').options[taille-1].value = cible;
							document.getElementById('".$aggregCode."_aggregation').options[taille-1].text = label;
						}
					}
					// si le NA n'est pas selectionne (case non cochee)
					else
					{
						supprimeElement('".$aggregCode."_aggregation', cible, label);
					}
					";
				?>
						// 17/03/2010 NSE bz 14532 on supprime la Na courante de sa liste des Na source si elle n'est pas min level
						if(!document.getElementById('min_'+cible).checked)
							supprimeElement(cible+'_aggregation', cible, label);
				
						// 10/12/2009 BBX
						// Il faut vérifier les NA source. BZ 13124
						isSource();
					}
					
					// 17/03/2010 NSE fonction pour la suppression d'un élément d'un select
					function supprimeElement(idSelect, cible, label){
						// on le supprime des select en decallant les elements du tableau
						// on cherche l'element a enlever et on le supprime
						trouve=0;
						for(i=0;i<document.getElementById(idSelect).options.length-1;i++){
							if(trouve==1 || document.getElementById(idSelect).options[i].value == cible){
								document.getElementById(idSelect).options[i].value = document.getElementById(idSelect).options[i+1].value;
								document.getElementById(idSelect).options[i].text = document.getElementById(idSelect).options[i+1].text;
								// si l'element courant etait selectionne, il ne doit plus l'etre suite au decallage.
								if(document.getElementById(idSelect).options[i].selected)
									document.getElementById(idSelect).options[i].selected=false;
								// si l'element suivant etait selectionne, c'est maintenant l'element courant qui doit l'etre, suite au décallage.
								if(document.getElementById(idSelect).options[i+1].selected)
									document.getElementById(idSelect).options[i].selected=true;
								trouve=1;
							}
						}
						// si on ne l'a pas trouve, on supprime le dernier element s'il correspond
						if(trouve==1 || document.getElementById(idSelect).options[document.getElementById(idSelect).options.length-1].value == cible){
							document.getElementById(idSelect).options[document.getElementById(idSelect).options.length-1].value = '';
							document.getElementById(idSelect).options[document.getElementById(idSelect).options.length-1].text = '';
							document.getElementById(idSelect).options.length -=1;
						}
					}
					</script>
				<?php
				if ( !isset($naLabelList) )
				{
					$naLabelList = getPathNetworkAggregation ( ProductModel::getIdMixedKpi(), $idFamily, 1, $ChildToParent = false );
				}
				$naMin = get_network_aggregation_min_from_family($idFamily,ProductModel::getIdMixedKpi());
				$warningConfig=false;
				foreach($na_levels_in_common as $aggregCode => $aggregLabel) 
				{
					?>
						<tr>
							<!-- FAMILLE -->
							<td align="left"><?=$aggregLabel?></td>
							<!-- FAMILLE MIN -->
							<td><input type="radio" style="border: none;background: none;" name="family_min" id="min_<?=$aggregCode?>" value="<?=$aggregCode?>"<?=$aggregCode==$naMin?' checked':''?> onClick="if(this.checked) selectionner_na('used_<?=$aggregCode?>','<?=$aggregLabel?>')" /></td>
							<!-- FAMILLE USED -->
							<td><input type="checkbox" style="border: none;background: none;" name="family_used[]" value="<?=$aggregCode?>"<?=$aggregCode==$naMin?' checked disabled':(isset($naLabelList[$aggregCode])?' checked':'')?> id="used_<?=$aggregCode?>" onclick="getFieldValue('<?=$aggregCode?>','<?=$aggregLabel?>')" /></td>
							<!-- AGGREGATION -->
							<td>
								<select name="<?=$aggregCode?>_aggregation" id="<?=$aggregCode?>_aggregation"<?=$aggregCode==$naMin||isset($naLabelList[$aggregCode])?($aggregCode==$naMin?' disabled':''):' style="visibility: hidden"'?> onchange="isSource()">
									<option value=""><?=__T('A_SETUP_MIXED_KPI_CHOOSE_AGGREGATION')?></option>
									<?php
									// 13:52 10/12/2009 GHX
									// Correction du BZ 13185
									// Ajout des 2 array_key_exists
									// 17/03/2010 NSE ajout de  && !empty($na_levels_in_common) pour éviter un Warning dans le code
									if(!empty($naMin) && !empty($na_levels_in_common) && array_key_exists($naMin, $na_levels_in_common) )
										echo '<option value="'.$naMin.'"'.($aggregCode==$naMin||$naLabelList[$aggregCode][0]==$naMin?' selected':'').'>'.$na_levels_in_common[$naMin].'</option>';
									// 17/03/2010 NSE ajout du if !empty() pour éviter un Warning dans le code
									if(!empty($naLabelList))
										foreach($naLabelList as $na => $source){
											// 17/03/2010 NSE bz 14532 on n'affiche pas la Na courante dans sa liste des Na source (elle n'est pas min level)
											if($na!=$aggregCode){
												if ( $na != $naMin && array_key_exists($na, $na_levels_in_common) )
													echo '<option value="'.$na.'"'.($na==$naLabelList[$aggregCode][0]?' selected':'').'>'.$na_levels_in_common[$na].'</option>';
												else
													$warningConfig = true;
											}
									} ?>
								</select>						
							</td>
						</tr>
					<?php
				}
				?>					
						</table>
						<p style="text-align: center;"><input type="submit" value="<?=__T('A_SETUP_MIXED_KPI_BT_SAVE_EDIT_NA');?>" id="saveNa" name="saveNa" class="bouton" /></p>
					</div>
				</fieldset>
			</div>
		</form>
	</div>
</div>

<?php
// 10/12/2009 BBX
// Ajout de la fonction JS isSource qui permet d'interdire de décocher des NA qui sont sources d'autres NA.
// BZ 13124
?>
<script>
/*****
*	Cette fonction permet d'interdire de décocher des NA qui sont sources d'autres NA.
*****/
function isSource()
{
	var oneSource = '';
	var isSource = false;
<?php
	foreach($na_levels_in_common as $aggregCode => $aggregLabel) 
	{
?>
		isSource = false;
<?php
		foreach($na_levels_in_common as $sourceCode => $sourceLabel)
		{
?>
			oneSource = $('<?=$sourceCode?>_aggregation').options[$('<?=$sourceCode?>_aggregation').selectedIndex].value;
			if(('<?=$aggregCode?>' == oneSource) && ($('used_<?=$sourceCode?>').checked)) {
				isSource = true;
			}
<?php
		}
?>
		$('used_<?=$aggregCode?>').disabled = isSource;	
<?php
	}	
?>
}

/*****
*	Cette fonction permet de réactiver les checkbox au moment de poster le formulaire.
*****/
function manageCheckBoxes()
{
<?php
	foreach($na_levels_in_common as $aggregCode => $aggregLabel) 
	{
?>
		$('used_<?=$aggregCode?>').disabled = false;
<?php
	}	
?>
	return true;
}

isSource();
</script>
<?php
// FIN BZ 13124

// 11:35 04/01/2010 GHX (bonne année 2010)
// Correction du BZ 13612
if ( $warningConfig )
{
	?>
	<script>
	$('saveNa').className = 'boutonRouge';
	$("BackToMainPage").getElementsBySelector('a')[0].observe('click', function(event){
		alert('<?php echo __T('A_E_SETUP_MIXED_KPI_SAVE_CONFIG_NA'); ?>');
		this.href="#";
	});
	$("editFamilies").observe('click', function(event){
		alert('<?php echo __T('A_E_SETUP_MIXED_KPI_SAVE_CONFIG_NA'); ?>');
		this.disable();
		// setTimeout('alert("ooo")', 1);
		setTimeout('$("editFamilies").enable();', 0);
	});
	</script>
	<?php
}
// Fin correction BZ 13612
?>