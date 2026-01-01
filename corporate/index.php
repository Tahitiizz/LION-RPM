<?php
/*
	19/11/2009 GHX
		- Un produit Mixed KPI ne peut etre un activé comme un Corporate
	30/11/2009 BBX
		- Contrôle de la clé. BZ 13099
*/
?>
<?
/*
*	@cb50100@
*
*	08/08/2009 - Copyright Astellia
*
*	IHM de configuration Corporate
*
*/
?>
<?php
session_start();

// 06/04/2012 BBX
// BZ 26700 : Pas de time limit sur cette page afin d'éviter les timeout
// Dans le cas d'une activation trop longue
set_time_limit(0);

include_once dirname(__FILE__)."/../php/environnement_liens.php";
include_once(REP_PHYSIQUE_NIVEAU_0.'class/deploy.class.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'php/edw_function_family.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'class/select_family.class.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'class/SSHConnection.class.php');

// Librairies et classes requises
include_once(REP_PHYSIQUE_NIVEAU_0.'intranet_top.php');

// Sélection famille / produit
if(!isset($_GET["product"])){
	$select_family = new select_family($_SERVER['PHP_SELF'], $_SERVER['argv'][0], 'Export');
	exit;
}

// GET Values
$product = $_GET["product"];

// Variable de message OK
$okMsg = '';
// Variable de message Error
$errorMsg = '';

// Messages à placer dans message_display
$A_SETUP_CORPORATE_PAGE_TITLE			= __T('A_SETUP_CORPORATE_PAGE_TITLE');
$A_SETUP_CORPORATE_INFO_ACTIVATION		= __T('A_SETUP_CORPORATE_INFO_ACTIVATION');
$A_SETUP_CORPORATE_ACTIVATION_BUTTON	= __T('A_SETUP_CORPORATE_ACTIVATION_BUTTON');
$A_SETUP_CORPORATE_CONFIRM_ACTIVATION	= __T('A_SETUP_CORPORATE_CONFIRM_ACTIVATION');
$A_SETUP_CORPORATE_ACTIVATION_SUCCESS	= __T('A_SETUP_CORPORATE_ACTIVATION_SUCCESS');
$A_SETUP_CORPORATE_ACTIVATION_ERROR		= __T('A_SETUP_CORPORATE_ACTIVATION_ERROR');
$A_SETUP_CORPORATE_INFO_FAMILY_CONF		= __T('A_SETUP_CORPORATE_INFO_FAMILY_CONF');
$A_SETUP_CORPORATE_LABEL_FAMILY			= __T('A_SETUP_CORPORATE_LABEL_FAMILY');
$A_SETUP_CORPORATE_LABEL_TA_MIN			= __T('A_SETUP_CORPORATE_LABEL_TA_MIN');
$A_SETUP_CORPORATE_LABEL_NA_MIN			= __T('A_SETUP_CORPORATE_LABEL_NA_MIN');
$A_SETUP_CORPORATE_LABEL_NA_MIN_AXE3	= __T('A_SETUP_CORPORATE_LABEL_NA_MIN_AXE3');
$A_SETUP_CORPORATE_LABEL_SUPER_NET		= __T('A_SETUP_CORPORATE_LABEL_SUPER_NET');
$A_SETUP_CORPORATE_LABEL_DATA_TYPE		= __T('A_SETUP_CORPORATE_LABEL_DATA_TYPE');
$A_SETUP_CORPORATE_SAVE_SUCCESS			= __T('A_SETUP_CORPORATE_SAVE_SUCCESS');
$A_SETUP_CORPORATE_SAVE_ERROR			= __T('A_SETUP_CORPORATE_SAVE_ERROR');
$A_SETUP_CORPORATE_INFO_SETUP_CO_LINK	= __T('A_SETUP_CORPORATE_INFO_SETUP_CO_LINK');
$A_SETUP_CORPORATE_CONFIRM_DEPLOY		= __T('A_SETUP_CORPORATE_CONFIRM_DEPLOY');
$A_SETUP_CORPORATE_CONFIRM_NEW_SN		= __T('A_SETUP_CORPORATE_CONFIRM_NEW_SN');
$A_SETUP_CORPORATE_SN_CANNOT_BEGIN_NUM	= __T('A_SETUP_CORPORATE_SN_CANNOT_BEGIN_NUM');
$A_SETUP_CORPORATE_SN_INCORRECT			= __T('A_SETUP_CORPORATE_SN_INCORRECT');
$A_SETUP_CORPORATE_ERROR_DATA_CHECK		= __T('A_SETUP_CORPORATE_ERROR_DATA_CHECK');
$A_SETUP_CORPORATE_ERROR_NO_CONNECTION	= __T('A_SETUP_CORPORATE_ERROR_NO_CONNECTION');
$A_SETUP_CORPORATE_PLEASE_SAVE			= __T('A_SETUP_CORPORATE_PLEASE_SAVE');
$A_E_SETUP_CORPORATE_MIXED_KPI			= __T('A_E_SETUP_CORPORATE_MIXED_KPI');

// Récupération des infos produit
$Product = new ProductModel($product);
$productInfos = $Product->getValues();

// Classe du bouton Save
$saveBtnClass = 'bouton';

// Javascript supplémentaire
$DynamicJS = '';

// Activation du corporate
if(isset($_POST['activation']) && ($_POST['activation'] == 1)) 
{
	// 06/11/2009 BBX : amélioration de la visibilité sur l'obligation de sauvegarder. BZ 12467
	if(CorporateModel::activate($product))
	{
		// L'activation à réussie, on affiche le message de succès
		$okMsg = $A_SETUP_CORPORATE_ACTIVATION_SUCCESS;
		// On passe le bouton Save en rouge
		$saveBtnClass = 'boutonRouge';
		// On n'autorise pas de partir avant de sauver !!
		$DynamicJS = "document.body.onunload = function() {
			alert('".$A_SETUP_CORPORATE_PLEASE_SAVE."');
			document.location.href = '".$_SERVER['REQUEST_UR']."';
		};";
	}
	else 
	{
		// L'activation à échouée, on affiche le message d'erreur
		$errorMsg = $A_SETUP_CORPORATE_ACTIVATION_ERROR;
	}
}

// Configuration des familles
if(isset($_POST['configuration']) && ($_POST['configuration'] == 1))
{
	// Variable de contrôle
	$check = true;
	// Mise à jour des infos famille dans le table de conf du corporate
	$check &= CorporateModel::updateConf($_POST,$product);
	// Gestion du message
	$check ? $okMsg = $A_SETUP_CORPORATE_SAVE_SUCCESS : $errorMsg = $A_SETUP_CORPORATE_SAVE_ERROR;
	
        if(!$check && !empty(CorporateModel::$errorMsg)) {
            $errorMsg .= '<br />'.CorporateModel::$errorMsg;
        }
	
	// Si la sauvegarde est OK, on regénère les Data Export
	if($check)
	{
		if(!CorporateModel::sendDataExport($product)) 
		{
			$affiliateFailed = CorporateModel::$affiliateFailed;
			if(!empty($affiliateFailed))
			{
				// TODO : message display
				// Conf affiliate incorrecte
				$errorMsg = __T('A_SETUP_CORPORATE_ERROR_DATA_EXPORT',$affiliateFailed);			
			}
			else
			{
				// Pas de connexion
				$errorMsg = $A_SETUP_CORPORATE_ERROR_NO_CONNECTION;	
			}
		}
		
		// 30/11/2009 BBX : 
		// Contrôle de la clé. BZ 13099
		$key = new Key();
		$key->Decrypt(get_sys_global_parameters('key',0,$product));
		$naInKey = $key->getNaKey();
		// Si le NA n'existe plus
		if(!$key->checkNaExistInProduct($naInKey,$product)) {
			if($errorMsg != '')
				$errorMsg .= '<br />';
			$errorMsg .= __T('A_E_SETUP_CORPORATE_UPDATE_KEY',$naInKey);
		}
		// FIN BZ 13099
	}
}

/*************
*	Affiche le produit courant + bouton changement produit
*	@param string : label produit
*************/
function productHeader($productLabel)
{
?>
	<div class="texteGris" style="height:35px;position:relative;text-align:center;">				
		<fieldset>				
			<div style="height:20px;padding-top:5px;">
				<?=__T('G_CURRENT_PRODUCT')?> : <?=$productLabel?>
			</div>
			<div style="position:absolute;top:5px;right:5px;">						
				<a href="<?php echo basename(__FILE__); ?>">
					<img src="<?=NIVEAU_0?>images/icones/change.gif" border="0" onmouseover="popalt('<?=__T('A_U_CHANGE_PRODUCT')?>')" />
				</a>					
			</div>					
		</fieldset>				
	</div>
<?php
}
?>

<style>
table {
	color:#666699;
	font-family:Arial;
	font-size:8pt;
	border-collapse:collapse;
}
table label {
	color:#666699;
	font-family:Arial;
	font-size:8pt;
}
table input {
	border: #7F9DB9 1px solid;
	font-size:8pt;
	color: #666699;
	background-color: #ffffff;
}
table select {
	color:#666699;
	font-family:Arial;
	font-size:8pt;
	border: #7F9DB9 1px solid;
	background-color: #ffffff;
}
th {
	background-color:#b9c9fe;
	font-weight:bold;
	font-size:9pt;
	padding:5px;
	border-bottom:1px solid white;
	border-top:4px solid #aabcfe;
}
td {
	background-color:#e8edff;
	padding:5px;
	border-bottom:1px solid white;
}
</style>

<script type="text/javascript">
/****
* Cette fonction gère la valeur Super Network
****/
function checkSuperNetwork(idSN) {
	if($(idSN).value != '') {
		$(idSN).setStyle({backgroundColor:'#ffffff'});
	}
	else {
		$(idSN).setStyle({backgroundColor:'#eeeeee'});
	}
}

/****
* Cette fonction gère un changement de Super Network
****/
function warningSuperNetwork(idSN) 
{
	test1 = new RegExp("^[0-9]","gi");
	test2 = new RegExp("[^a-zA-Z0-9 _-]","gi");
	if(test1.test($(idSN).value)) {
		alert('<?=$A_SETUP_CORPORATE_SN_CANNOT_BEGIN_NUM?>');
		$(idSN).value = $('mem_'+idSN).value;
		checkSuperNetwork(idSN);
		return false;
	}
	if(test2.test($(idSN).value)) {
		alert('<?=$A_SETUP_CORPORATE_SN_INCORRECT?>');
		$(idSN).value = $('mem_'+idSN).value;
		checkSuperNetwork(idSN);
		return false;
	}

	if(($('mem_'+idSN).value != '') && ($(idSN).value != $('mem_'+idSN).value)) {
		if(confirm('<?=$A_SETUP_CORPORATE_CONFIRM_NEW_SN?>')) {
			return true;
		}
		else {
			$(idSN).value = $('mem_'+idSN).value;
			$(idSN).setStyle({backgroundColor:'#ffffff'});
			return false;
		}
	}
}

/****
* Cette fonction gère la soumission du formulaire
****/
function checkConfiguration() 
{
<?php
	// Contrôle des données
	foreach(getFamilyList($product) as $family => $familyLabel) 
	{
?>
		if(!$('export_raw_<?=$family?>').checked && !$('export_kpi_<?=$family?>').checked) {
			alert('<?=$A_SETUP_CORPORATE_ERROR_DATA_CHECK?><?=$familyLabel?>');
			return false;
		}
<?php
	}
?>
	return confirm('<?=$A_SETUP_CORPORATE_CONFIRM_DEPLOY?>');
}

/****
* Code JS à éxécuter à la fin du chargement de la page
****/
document.observe("dom:loaded", function() {
	// Gestion des messages
	if($('okMsg')) {
		setTimeout("new Effect.Fade('okMsg', { duration: 1.0 });",5000);
	}
	if($('errorMsg')) {
		//setTimeout("new Effect.Fade('errorMsg', { duration: 1.0 });",5000);
	}
});

// 06/11/2009 BBX : amélioration de la visibilité sur l'obligation de sauvegarder. BZ 12467
<?=$DynamicJS?>
</script>

<div id="container" style="width:100%;text-align:center">
	
	<!-- titre de la page -->
	<div>
		<img src="<?=NIVEAU_0?>images/titres/setup_corporate_titre.gif" alt="<?=$A_SETUP_CORPORATE_PAGE_TITLE?>" border="0" />
	</div>
	<br />

<?php

	/**********************************************************************************************/

	// Si le corporate est activé
	if(CorporateModel::isCorporate($product))
	{
?>
	<form action="index.php?product=<?=$product?>" method="post" onsubmit="return checkConfiguration()" />
		<div class="tabPrincipal" style="width:680px;text-align:center;padding:10px;">
<?php
	// choix du produit si multiproduit
	if(count(getProductInformations()) > 1)
	{
		productHeader($productInfos['sdp_label']);
	}
?>
<?php
		// Gestion des messages
		if(!empty($okMsg))
			echo '<div id="okMsg" class="okMsg">'.$okMsg.'</div>';
		if(!empty($errorMsg))
			echo '<div id="errorMsg" class="errorMsg">'.$errorMsg.'</div>';
			
?>
			<!-- HELP -->
			<div style="text-align:left">
					<img src="<?=NIVEAU_0?>images/icones/information.png" border="0" />
				<div id="help_box_1" class="infoBox" style="display:block;">
					<?=$A_SETUP_CORPORATE_INFO_FAMILY_CONF?>
					<center>
						<img src="<?=NIVEAU_0?>images/icones/bullet_go.png" border="0" />
						<a href="<?=NIVEAU_0?>myadmin_setup/intranet/php/affichage/setup_connection_index.php?product=<?=$product?>">
							<?=$A_SETUP_CORPORATE_INFO_SETUP_CO_LINK?>
						</a>
					</center>
				</div>
			</div>
			<br />

			<!-- FAMILY LIST -->
			<div>
				<table width="100%">
					<tr>
						<th align="left"><?=$A_SETUP_CORPORATE_LABEL_TA_MIN?></th>
						<th align="right" width="100">
							<select name="ta_min" style="width:100%">
<?php
							// Affichage des TA disponible dans le contexte d'origine
							foreach(CorporateModel::getCtxTimeAggregations($product) as $ta => $taLabel) 
							{
								$selected = ($ta == CorporateModel::getTaMin($product)) ? ' selected="selected"' : '';
								echo '<option value="'.$ta.'"'.$selected.'>'.$taLabel.'</option>';
							}
?>						
							</select>
						</th>
					</tr>
				</table>
				<br />
				<table width="100%">
					<tr>
						<th width="150"><?=$A_SETUP_CORPORATE_LABEL_FAMILY?></th>				
						<th><?=$A_SETUP_CORPORATE_LABEL_NA_MIN?></th>
						<th><?=$A_SETUP_CORPORATE_LABEL_NA_MIN_AXE3?></th>
						<th><?=$A_SETUP_CORPORATE_LABEL_SUPER_NET?></th>
						<th width="100"><?=$A_SETUP_CORPORATE_LABEL_DATA_TYPE?></th>
					</tr>
<?php
				// Affichage de la configuration pour toutes les familles
				$i = 0;
				foreach(getFamilyList($product) as $family => $familyLabel) 
				{
					// Couleur de fond
					$bgColor = ($i%2 == 0) ? '#DDDDDD' : '#ffffff';
?>
					<tr>
						<!-- FAMILLE -->
						<td align="left" style="font-size:7pt;"><?=$familyLabel?></td>
						<!-- NA MIN -->
						<td>
							<select name="<?=$family?>[na_min]" style="width:100%">
<?php
							// Affichage des NA disponibles dans le contexte d'origine pour la famille
							foreach(CorporateModel::getCtxNetworkAggregations($family,0,$product) as $na => $naLabel) 
							{
								$selected = ($na == CorporateModel::getFamilyInfo('na_min',$family,$product)) ? ' selected="selected"' : '';
								echo '<option value="'.$na.'"'.$selected.'>'.$naLabel.'</option>';
							}
?>						
							</select>						
						</td>
						<!-- NA MIN AXE 3 -->
						<td>
<?php
							// Tableau des NA axe3 pour la famille
							$axe3NAArray = CorporateModel::getCtxNetworkAggregations($family,1,$product);
							$disabled = (count($axe3NAArray) == 0) ? ' disabled' : '';
?>
							<select name="<?=$family?>[na_min_axe3]" style="width:100%"<?=$disabled?>>
<?php
							// Affichage des TA disponible dans le contexte d'origine
							foreach(CorporateModel::getCtxNetworkAggregations($family,1,$product) as $na => $naLabel) 
							{
								$selected = ($na == CorporateModel::getFamilyInfo('na_min_axe3',$family,$product)) ? ' selected="selected"' : '';
								echo '<option value="'.$na.'"'.$selected.'>'.$naLabel.'</option>';
							}
?>						
							</select>						
						</td>
						<!-- SUPER NETWORK -->
						<td>
<?php
							// Récupération du super network
							$super_network = utf8_decode(CorporateModel::getFamilyInfo('super_network',$family,$product));
							$background = empty($super_network) ? '#eeeeee' : '#ffffff';
?>
							<input type="hidden" id="mem_super_network_<?=$family?>" value="<?=$super_network?>" />
							<input type="text" id="super_network_<?=$family?>" name="<?=$family?>[super_network]" 
							value="<?=$super_network?>"
							maxlength="10"
							style="width:100px;background-color:<?=$background?>"
							onkeyup="checkSuperNetwork(this.id)"
							onchange = "warningSuperNetwork(this.id)" />
						</td>
						<!-- DATA TYPE -->
						<td align="left">
<?php
							// Récupération des valeurs sélectionnées
							$checkedRaw = CorporateModel::getFamilyInfo('export_raw',$family,$product)? ' checked' : '';
							$checkedKpi = CorporateModel::getFamilyInfo('export_kpi',$family,$product) ? ' checked' : '';
?>
							<input id="export_raw_<?=$family?>" name="<?=$family?>[export_raw]" value="1" type="checkbox"<?=$checkedRaw?> />
							<label for="export_raw_<?=$family?>">Counters</label>
							<br />
							<input id="export_kpi_<?=$family?>" name="<?=$family?>[export_kpi]" value="1" type="checkbox"<?=$checkedKpi?> />
							<label for="export_kpi_<?=$family?>">Kpis</label>
						</td>
					</tr>
<?php
					$i++;
				}
?>					
				</table>
			</div>
			<br />

			<!-- SUBMIT -->
			<input type="hidden" name="configuration" value="1" />
			<input type="submit" class="<?=$saveBtnClass?>" value="Save" onclick="document.body.onunload = null" />

		</div>
	</form>
<?php	
	}
	
	/**********************************************************************************************/
	
	// Si le corporate n'est pas activé
	else
	{
?>
	<form action="index.php?product=<?=$product?>" method="post" onsubmit="return confirm('<?=$A_SETUP_CORPORATE_CONFIRM_ACTIVATION?>');" />
		<div class="tabPrincipal" style="width:600px;text-align:center;padding:10px;">
<?php
	// choix du produit si multiproduit
	if(count(getProductInformations()) > 1)
	{
		productHeader($productInfos['sdp_label']);
	}
?>	
<?php
		// 14:54 19/11/2009 GHX
		// Un produit Mixed KPI ne peut etre un activé comme un Corporate
		if ( MixedKpiModel::isMixedKpi($product) )
		{
			echo '<div id="errorMsg" class="errorMsg">'.$A_E_SETUP_CORPORATE_MIXED_KPI.'</div>';
		}
		else
		{
			// Gestion des messages
			if(!empty($okMsg))
				echo '<div id="okMsg" class="okMsg">'.$okMsg.'</div>';
			if(!empty($errorMsg))
				echo '<div id="errorMsg" class="errorMsg">'.$errorMsg.'</div>';
?>
			<!-- HELP -->
			<div style="text-align:left">
					<img src="<?=NIVEAU_0?>images/icones/information.png" border="0" />
				<div id="help_box_1" class="infoBox" style="display:block;">
					<?=$A_SETUP_CORPORATE_INFO_ACTIVATION?>
				</div>
			</div>
			<br />
			
			<!-- SUBMIT -->
			<input type="hidden" name="activation" value="1" />
			<input type="submit" class="bouton" value="<?=$A_SETUP_CORPORATE_ACTIVATION_BUTTON?>" />
<?php
		}
?>
		</div>
	</form>
	
<?php	
	}
?>
	
</div>

</body>
</html>