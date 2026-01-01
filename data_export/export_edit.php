<?php
/*
	27/08/2009 GHX
		- Modification de la fonction manageForm() pour faire la vérification sur le serveur ou se trouve le produit
		- Ajout d'un champ input hidden pour avoir ID du produit
			= BZ 11306
	04/09/2009 GHX
		- Correction du BZ 1191 [CB 5.0][Data Export] le champ target dir n'est pas le même entre l'IHM et en base
	01/10/2009 GHX
		- Correction du BZ 11809 [CB 5.0][Data Export] le champ target dir n'est pas correcte sur pour le produit slave
	08/12/2009 GHX
		- La valeur add_topo_file du checkbox doit toujours être à 1
	03/03/2010 MPR
		- Correction du BZ 14338 - Choix des coordonnees (X/Y) ou GPS
	25/03/2010 NSE
		- bz 14338 : ajout de updateAddTopoFile(); pour initialiser la valeur de add_topo_file sinon, la case topo cochée n'est pas sauvegardée si on ne touche pas au menu déroulant de type de coordonnées.
    13/08/2010 OJT : Correction bz16860 + Fermeture des balise span non fermées
 * 28/11/2011 NSE bz 23633 : le lancement du script Ajax exécuté directement sur un slave distant ne fonctionne pas
 * 09/12/2011 ACS Mantis 837 DE HTTPS support
*/
?>
<?php
/*
*	@cb50000@
*
*	16/07/2009 - Copyright Astellia
*
*	IHM de gestion des Data Export - edition
*
*/
?>
<?php
session_start();
include_once dirname(__FILE__)."/../php/environnement_liens.php";

// Librairies et classes requises
require_once(REP_PHYSIQUE_NIVEAU_0.'models/DataExportModel.class.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'php/edw_function_family.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'class/select_family.class.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'class/DirectoryManagement.class.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'intranet_top.php');

// Sélection famille / produit
if(!isset($_GET["family"])){
	$select_family = new select_family($_SERVER['PHP_SELF'], $_SERVER['argv'][0], 'Export');
	exit;
}

// GET Values
$family = $_GET["family"];
$product = $_GET["product"];
$axe3 = GetAxe3($family, $product); // définit si il y a un axe3.
$msgDisplay = '';


// 14:17 01/10/2009 GHX
// Correction du BZ 11809 [CB 5.0][Data Export] le champ target dir n'est pas correcte sur pour le produit slave
$default_target_dir = REP_PHYSIQUE_NIVEAU_0;
$productsInformations = getProductInformations();
if ( array_key_exists($product, $productsInformations) )
{
	$default_target_dir = '/home/'.$productsInformations[$product]['sdp_directory'].'/';
}

// Infos produit
$masterTopo = getTopoMasterProduct();
$masterTopoId = $masterTopo['sdp_id'];

/*
*	DECLARATION DU FORMULAIRE POSTE
*/
if(isset($_POST['data_export']))
{
	// Vérification du formulaire
	if(DataExportModel::checkValues($_POST) === true)
	{
		// Mise à jour d'un export existant
		if(isset($_POST['export_id']))
			$_GET['export_id'] = $_POST['export_id'];
		// Nouvel export
		else
			$_GET['export_id'] = DataExportModel::create($family,$product);
	}
	else
	{
		// Il reste des valeurs incorrectes
		$msgDisplay = '<div id="msgDisplay" class="errorMsg" style="margin-top:20px;">'.DataExportModel::checkValues($_POST).'</div>';
		unset($_POST);
	}
}

// Edition d'un existant ou nouvel export ?
/*
*	Si $newExport est vrai, il s'agit d'un nouvel export. Les valeurs seront récupérées comme suit : DataExportModel::DEFAULT_TARGET_DIR
*	Sinon, les valeurs de l'export sont récupérées comme suit : $DataExportModel->getConfig('target_dir')
*	Donc, valorisation d'un champ : $newExport ? DataExportModel::DEFAULT_TARGET_DIR : $DataExportModel->getConfig('target_dir');
*/
$newExport = true;
if(isset($_GET['export_id'])) {
	$DataExportModel = new DataExportModel($_GET['export_id'],$product);
	if(!$DataExportModel->getError()) {
		$newExport = false;
	}
}

/*
*	ENREGISTREMENT DU FORMULAIRE POSTE
*/
if(isset($_POST['data_export']))
{
	if(!$DataExportModel->getError())
	{
		// Gestion des champs "normaux"
		foreach($_POST as $key => $value) {
			$DataExportModel->setConfig($key,$value);
		}
		// Gestion des checkbox
		if(!isset($_POST['add_topo_file'])) $DataExportModel->setConfig('add_topo_file',0);
		if(!isset($_POST['add_raw_kpi_file'])) $DataExportModel->setConfig('add_raw_kpi_file',0);
		if(!isset($_POST['use_code'])) $DataExportModel->setConfig('use_code',0);
		if(!isset($_POST['generate_hour_on_day'])) $DataExportModel->setConfig('generate_hour_on_day',0);
		if(!isset($_POST['select_parents'])) $DataExportModel->setConfig('select_parents',0);
		if(!isset($_POST['use_code_na'])) $DataExportModel->setConfig('use_code_na',0);
		if(!isset($_POST['use_codeq'])) $DataExportModel->setConfig('use_codeq',0);
		if(!isset($_POST['na_axe3'])) $DataExportModel->setConfig('na_axe3','');
		// Sauvegarde de l'id produit
		$DataExportModel->setConfig('id_product',$product);
		// Enregistrement des Raws
		$DataExportModel->setRawList(explode('|',$_POST['hidden_counters_selected']));
		// Enregistrement des Kpis
		$DataExportModel->setKpiList(explode('|',$_POST['hidden_kpis_selected']));
		// Message ok
		$msgDisplay = '<div id="msgDisplay" class="okMsg" style="margin-top:20px;">'.__T('A_DATA_EXPORT_SAVE_OK').'</div>';
	}
	// Message pas ok
	else $msgDisplay = '<div id="msgDisplay" class="errorMsg" style="margin-top:20px;">'.__T('A_DATA_EXPORT_SAVE_NOK').'</div>';
}
?>
<script type="text/javascript">
/****
* 24/10/2008 BBX : permet de transvaser des éléments d'une liste à une autre
* @param int : sens
****/
function move_elements(sens,maitre,slave)
{
	// Fonction qui bouge les éléments
	function move(idz1,idz2)
	{
		if($(idz1).options.selectedIndex != -1)
		{
			var array_to_remove = new Array();
			for(var i = 0; i < $(idz1).options.length; i++)
			{
				if($(idz1).options[i].selected)
				{
					$(idz2)[$(idz2).options.length] = new Option($(idz1).options[i].text, $(idz1).options[i].value);
					$(idz1).options[i] = null;
					i--;
				}
			}
		}
	}
	// Id des zones
	var id_zone_maitre = maitre;
	var id_zone_esclave = slave;
	var id_input_maitre = 'hidden_'+maitre;
	var id_input_esclave = 'hidden_'+slave;

	// Selon le sens
	if(sens == 1) {
		move(id_zone_maitre,id_zone_esclave);
	}
	else {
		move(id_zone_esclave,id_zone_maitre);
	}
	// Sauvegarde des elements de la zone esclave
	$(id_input_esclave).value = '';
	for(var i = 0; i < $(id_zone_esclave).options.length; i++)
	{
		var sep = (i == 0) ? '' : '|';
		$(id_input_esclave).value += sep+$(id_zone_esclave).options[i].value;
	}
	// Sauvegarde des elements de la zone maîte
	$(id_input_maitre).value = '';
	for(var i = 0; i < $(id_zone_maitre).options.length; i++)
	{
		var sep = (i == 0) ? '' : '|';
		$(id_input_maitre).value += sep+$(id_zone_maitre).options[i].value;
	}
}

/****
* gère la checkbox "generate one file on day"
* @param int : sens
****/
function manageCheckboxHourOnDay()
{
	if($('time_aggregation').value != 'hour')
	{
		$('generate_hour_on_day').checked = false;
		$('div_generate_hour_on_day').setStyle({display:'none'});
	}
	else
	{
		<?php
		$checked = (($newExport ? DataExportModel::DEFAULT_GENERATE_HOUR_ON_DAY : $DataExportModel->getConfig('generate_hour_on_day')) == 1) ?  'true' : 'false';
		?>
		$('generate_hour_on_day').checked = <?=$checked ?>;
		$('div_generate_hour_on_day').setStyle({display:'block'});
	}
}

// Correction du BZ 14338 - Choix des coordonnees (X/Y) ou GPS
/****
* gere la liste déroulante des coordonnees
*
****/
function updateAddTopoFile()
{
	$('add_topo_file').value = $F('type_coordinates');
}

// Correction du BZ 14338 - Choix des coordonnees (X/Y) ou GPS
/****
* gère la checkbox "add_topo_file"
*
****/
function manageTypeCoordinates()
{
	if( $F('add_topo_file') == null )
	{
		$('type_coordinates').disable();
	}
	else
	{
		$('type_coordinates').enable();
	}
}
/****
* Récupère une info raw ou kpi
****/
function getElementInfo(selObj,type,idDivCible)
{
	if(selObj.selectedIndex > -1) {
		var idElem = selObj.options[selObj.selectedIndex].value;
                // 28/11/2011 NSE bz 23633 : le passage des paramètres se fait maintenant en GET
                // 09/05/2012 NSE reopen bz 23633 : on revient au POST pour compatibilité cb 5.0
		new Ajax.Request('export_ajax.php',{
			method:'post',
			parameters:'action=getElementInfo&idElem='+idElem+'&type='+type+'&family=<?=$family?>&product=<?=$product?>',
			onSuccess: function(res) {
				$(idDivCible).update(res.responseText);
			}
		});
	}
}

/****
* Code JS à exécuter lors de la soumission du formulaire
****/
function manageForm(formId)
{
	// Variable de controle
	var isFormOk = true;
	// Verification de la saisie
	// 19:44 27/08/2009 GHX
	// La verification se fait toujours sur le serveur ou se trouve le produit pour pouvoir le verif sur le target dir
        // 28/11/2011 NSE bz 23633 : la vérification du formulaire ne se fait plus pas le lancement direct 
        // du script export_ajax.php en Ajax car ça ne fonctionne pas sur un slave distant. 
        // On passe par un script local qui fait un file_get_contents sur le export_ajax.php du slave.
		// 09/12/2011 ACS Mantis 837 DE HTTPS support
        new Ajax.Request('check_form.php',{
		method:'post',
		asynchronous:false,
		parameters:'productId=<?= $product ?>&action=checkForm&'+$(formId).serialize(),
		onSuccess: function(res) {
			if(res.responseText != 'OK') {
				alert(res.responseText);
				isFormOk = false;
			}
		}
	});
	return isFormOk;
}

/****
* Génération du fichier d'export
****/
function generateFile()
{
	$('generate_file_indicator').value = 1;
}

/****
* Code JS à éxécuter à la fin du chargement de la page
****/
document.observe("dom:loaded", function() {
	// Gestion de la case "generate one file on day"
	manageCheckboxHourOnDay();
	manageTypeCoordinates();
	// Gestion des messages
	if($('msgDisplay')) {
		setTimeout("new Effect.Fade('msgDisplay', { duration: 1.0 });",5000);
	}
<?php
// Demande de génération du fichier d'export
if(isset($_POST['generate_file_indicator']) && ($_POST['generate_file_indicator'] == 1)) {
?>
	// Generate file : ouverture d'une popup sur data_export.php
	window.open("export_file.php?product=<?=$product?>&family=<?=$family?>&export_id=<?=$_GET['export_id']?>","","menubar=no, status=no, scrollbars=yes, menubar=no, width=400, height=110");

<?php
}
?>
});
</script>
<style type="text/css">
.dataSelector {
	width:250px;
	height:300px;
	font-size:8pt;
	font-family:Verdana;
}
</style>
<div id="container" style="width:100%;text-align:center">
	<img alt="" src="<?=NIVEAU_0?>images/titres/export_setup_interface.gif" />
	<br /><br />
	<div class="tabPrincipal" style="margin:auto;width:600px;text-align:center;padding:10px;">

		<!-- Back to List -->
		<div class="texteGris" style="position:relative;text-align:center;">
		<fieldset style="height:100%;padding-top:5px;">
			<a href="index.php?family=<?=$family?>&product=<?=$product?>">
				<b><?=__T('G_PROFILE_FORM_LINK_BACK_TO_THE_LIST')?></b>
			</a>
		</fieldset>
		</div>
		<!-- END Back to List -->

		<!-- Msg display -->
		<?=$msgDisplay?>

		<!-- FORM Begin -->
		<form id="data_export_form" name="data_export_form" action="export_edit.php?family=<?=$family?>&product=<?=$product?>" method="post" onsubmit="return manageForm('data_export_form')">
			<input type="hidden" value="1" name="data_export" />
			<input type="hidden" value="<?php echo $product; ?>" name="product" />
			<input type="hidden" value="0" name="generate_file_indicator" id="generate_file_indicator" />
			<?php
			// S'il s'agit d'un export existant, on précise au formulaire son id
			if(!$newExport) echo '<input type="hidden" name="export_id" value="'.$_GET['export_id'].'" />';
			?>

			<!-- Export Configurations -->
			<fieldset>
				<legend>
					<img alt="" src="<?=NIVEAU_0?>images/icones/small_puce_fieldset.gif" />
					&nbsp;<span class='texteGrisBold'><?=__T('A_TASK_SCHEDULER_DATA_EXPORT_TITLE_EXPORT_CONFIG')?></span>
				</legend>
				<table class="texteGris" width="550" cellpadding="3" cellspacing="0" border="0">
					<!-- Export Name -->
					<tr>
						<td align="left">
							<?=__T('A_TASK_SCHEDULER_DATA_EXPORT_LABEL_EXPORT_NAME')?>*
						</td>
						<td align="left">
							<input type="text" name="export_name" id="export_name" size="50" value="<?=($newExport ? DataExportModel::DEFAULT_EXPORT_NAME : $DataExportModel->getConfig('export_name'))?>" />
						</td>
					</tr>
					<!-- Target Dir -->
					<tr>
						<td align="left">
							<?=__T('A_TASK_SCHEDULER_DATA_EXPORT_LABEL_TARGET_DIR')?>
						</td>
						<td align="left">
							<input type="hidden" name="target_dir" id="target_dir" size="50" value="<?=($newExport ? $default_target_dir.'upload/export_files/' : $DataExportModel->getConfig('target_dir'))?>" />
							<input type="text" name="target_dir_disabled" id="target_dir_disabled" size="50" disabled="disabled" value="<?=($newExport ? $default_target_dir.'upload/export_files/' : $DataExportModel->getConfig('target_dir'))?>" />
						</td>
					</tr>
					<!-- Target File -->
					<tr>
						<td align="left">
							<?=__T('A_TASK_SCHEDULER_DATA_EXPORT_LABEL_TARGET_FILE')?>*
						</td>
						<td align="left">
							<input type="text" name="target_file" id="target_file" size="50" value="<?=($newExport ? DataExportModel::DEFAULT_TARGET_FILE : $DataExportModel->getConfig('target_file'))?>" />
							<div class='texteGrisPetit'><?=__T('A_TASK_SCHEDULER_DATA_EXPORT_COMMENT_DATE_ADDED_TO_TARGET_FILE')?></div>
						</td>
					</tr>
					<!-- Field Separator -->
					<tr>
						<td align="left">
							<label for="field_separator"><?=__T('A_TASK_SCHEDULER_DATA_EXPORT_LABEL_FIELD_SEPARATOR')?></label>
						</td>
						<td align="left">
							<?php
							// Tableau des séparateurs de champ
							$fieldSeparatorArray = Array(';',',');
							// Séparateur de champ sélectionné
							$selectedFieldSeparator = ($newExport ? DataExportModel::DEFAULT_FIELD_SEPARATOR : $DataExportModel->getConfig('field_separator'));
							?>
							<select name="field_separator">
							<?
								foreach($fieldSeparatorArray as $separator)
								{
									$selected = ($separator == $selectedFieldSeparator) ? ' selected="selected"' : '';
									echo '<option value="'.$separator.'"'.$selected.'>'.$separator.'</option>';
								}
							?>
							</select>
						</td>

					</tr>
				</table>
			</fieldset>
			<!-- END Export Configurations -->

			<br />

			<!-- File content options -->
			<fieldset>
				<legend>
					<img alt="" src="<?=NIVEAU_0?>images/icones/small_puce_fieldset.gif" />
					&nbsp;<span class='texteGrisBold'><?=__T('A_TASK_SCHEDULER_DATA_EXPORT_TITLE_FILE_CONTENT')?></span>
				</legend>
				<table class="texteGris" width="550" cellpadding="3" cellspacing="0" border="0">
					<!-- Use Counters and Kpis Codes in the export -->
					<?php
						// Popalt
						$msg = __T('A_TASK_SCHEDULER_DATA_EXPORT_LABEL_RAW_KPI_CODES');
						$popalt = 'onMouseOver="popalt(\''.$msg.'\')" onMouseOut="kill()"';
						// Coché ou non
						$checked = '';
						if(($newExport ? DataExportModel::DEFAULT_USE_CODE : $DataExportModel->getConfig('use_code')) == 1)
							$checked = ' checked="checked"';
					?>
					<tr>
						<td align="left">
							<input type="checkbox" id="use_code" name="use_code" value="1"<?=$checked?> />
							<label for="use_code" <?=$popalt?> style="cursor:help"><?=__T('A_TASK_SCHEDULER_DATA_EXPORT_USE_CODE')?></label>
						</td>
					</tr>
					<tr>
					<!-- Add Network Topology Reference In Data Export File -->
						<td align="left">
							<?php
							// Popalt
							$msg = __T('A_TASK_SCHEDULER_DATA_EXPORT_LABEL_ADD_PARENTS');
							$popalt = 'onMouseOver="popalt(\''.$msg.'\')" onMouseOut="kill()"';
							// Coché ou non
							$checked = (($newExport ? DataExportModel::DEFAULT_SELECT_PARENTS : $DataExportModel->getConfig('select_parents')) == 1) ?  'checked' : '';
							?>
							<input type="checkbox" name="select_parents" id="select_parents" value="1" <?=$checked?> />
							<label for="select_parents" <?=$popalt?> style="cursor:help"><?=__T('A_TASK_SCHEDULER_DATA_EXPORT_LABEL_SELECTION_PARENTS')?></label>
						</td>
					</tr>
					<!-- Use code network elements -->
					<tr>
						<td align="left">
							<?php
							// Popalt
							$msg = __T('A_TASK_SCHEDULER_DATA_EXPORT_LABEL_USE_CODE_NA');
							$popalt = 'onMouseOver="popalt(\''.$msg.'\')" onMouseOut="kill()"';
							// Coché ou non
							$checked = (($newExport ? DataExportModel::DEFAULT_USE_CODE_NA : $DataExportModel->getConfig('use_code_na')) == 1) ?  'checked' : '';
							?>
							<input type="checkbox" name="use_code_na" id="use_code_na" value="1" <?=$checked?> />
							<label for="use_code_na" <?=$popalt?> style="cursor:help"><?=__T('A_TASK_SCHEDULER_DATA_EXPORT_LABEL_USE_CODE_NETWORKS')?></label>
						</td>
					</tr>
					<!-- Use mapped codes for network elements -->
					<?php
					if(getTopologyMappingInfo() && ($masterTopoId != $product))
					{
						// Popalt
						$msg = __T('A_TASK_SCHEDULER_DATA_EXPORT_USE_CODEQ_HELP');
						$popalt = 'onMouseOver="popalt(\''.$msg.'\')" onMouseOut="kill()"';
						// Coché ou non
						$checked = '';
						if(($newExport ? DataExportModel::DEFAULT_USE_CODEQ : $DataExportModel->getConfig('use_codeq')) == 1)
							$checked = ' checked="checked"';
					?>
					<tr>
						<td align="left">
							<input type="checkbox" id="use_codeq" name="use_codeq" value="1"<?=$checked?> />
							<label for="use_codeq" <?=$popalt?> style="cursor:help"><?=__T('A_TASK_SCHEDULER_DATA_EXPORT_USE_CODEQ')?></label>
						</td>
					</tr>
					<?php
					}
					?>
				</table>
			</fieldset>
			<!-- END File content options -->

			<br />

			<!-- Additionnal files -->
			<fieldset>
				<legend>
					<img alt="" src="<?=NIVEAU_0?>images/icones/small_puce_fieldset.gif" />
					&nbsp;<span class='texteGrisBold'><?=__T('A_TASK_SCHEDULER_DATA_EXPORT_TITLE_ADDITIONNAL_FILES')?></span>
				</legend>
				<table class="texteGris" width="550" cellpadding="3" cellspacing="0" border="0">
					<!-- Add topology file -->
					<?php
						// Coché ou non
						$checked = '';
						// maj 15:46 09/11/2009 MPR
						// Début Correction du BZ12581 : On export par défault les coordonnées GPS
						// Valeurs possibles pour le paramètre ( 0 => Pas de fichier de topo généré
						//						 1=> Fichier de topologie généré avec les coordonnées GPS
						//						 2 => Fichier de topologie généré avec les coordonnées x et y)
                                                // 21/11/2011 BBX
                                                // BZ 24764 : correction des messages "Notice" PHP
						$value = ($newExport) ? DataExportModel::DEFAULT_ADD_TOPO_FILE : $DataExportModel->getConfig('add_topo_file');
						// __debug($value,"VALUUUUUUUEEEEEEEEE");
						// 14:06 08/12/2009 GHX
						// La valeur de add_topo_file doit toujours être a 1

						if( $value >= 1 )
							$checked = ' checked="checked"';

						// Message d'aide
						$msg = __T('A_TASK_SCHEDULER_DATA_EXPORT_TOPOLOGY_FILE_HELP');
						$popalt = 'onMouseOver="popalt(\''.$msg.'\')" onMouseOut="kill()"';

					?>
					<tr>
						<td align="left" >
							<!-- 25/03/2010 NSE bz 14338 : ajout de updateAddTopoFile(); pour initialiser la valeur de add_topo_file -->
							<input type="checkbox" id="add_topo_file" name="add_topo_file" value="<?=$value?>"<?=$checked?> onclick="manageTypeCoordinates();updateAddTopoFile();" />
							<!-- Fin de correction du BZ12581 -->
							<label for="add_topo_file" <?=$popalt?> style="cursor:help"><?=__T('A_TASK_SCHEDULER_DATA_EXPORT_ADD_TOPOLOGY_FILE')?></label>
							<!-- Correction du BZ 14338 - Choix des coordonnees (X/Y) ou GPS -->
                            <!-- 13/08/2010 OJT : Correction bz16860 pour DE Firefox -->
							<select id="type_coordinates" name='type_coordinates' onchange='updateAddTopoFile();'>
								<option value='1' <?=( ( $value==1 ) ? "selected='selected'": "")?>>GPS</option>
								<option value='2' <?=( ( $value==2 ) ? "selected='selected'": "")?>>X/Y</option>
							</select>
						</td>
					</tr>
					<tr>
					<!-- Add KPI/Counters Informations file -->
					<?php

						// Coché ou non
						$checked = '';
						if(($newExport ? DataExportModel::DEFAULT_ADD_RAW_KPI_FILE : $DataExportModel->getConfig('add_raw_kpi_file')) == 1)
							$checked = ' checked="checked"';
						// Message d'aide
						$msg = __T('A_TASK_SCHEDULER_DATA_EXPORT_ADD_RAW_KPI_HELP');
						$popalt = 'onMouseOver="popalt(\''.$msg.'\')" onMouseOut="kill()"';
					?>
						<td align="left">
							<input type="checkbox" id="add_raw_kpi_file" name="add_raw_kpi_file" value="1"<?=$checked?> />
							<label for="add_raw_kpi_file" <?=$popalt?> style="cursor:help"><?=__T('A_TASK_SCHEDULER_DATA_EXPORT_ADD_RAW_KPI_FILE')?></label>
						</td>
					</tr>
				</table>
			</fieldset>
			<!-- END Additionnal files -->

			<br />

			<!-- Aggregation Levels -->
			<fieldset>
				<legend>
					<img alt="" src="<?=NIVEAU_0?>images/icones/small_puce_fieldset.gif" />
					&nbsp;<span class='texteGrisBold'><?=__T('A_TASK_SCHEDULER_DATA_EXPORT_TITLE_AGGREGATION_LEVEL')?></span>
				</legend>
				<table class="texteGris" width="550" cellpadding="3" cellspacing="0" border="0">
					<!-- Time Aggregation -->
					<tr>
						<td align="left">
							<?=__T('A_TASK_SCHEDULER_DATA_EXPORT_LABEL_TIME_AGGREGATION')?>
						</td>
						<td align="left">
							<select name="time_aggregation" id="time_aggregation" onchange="manageCheckboxHourOnDay()">
							<?php
							// Valeur sélectionnée
							$selectedTa = ($newExport ? DataExportModel::DEFAULT_TIME_AGGREGATION : $DataExportModel->getConfig('time_aggregation'));
							$popalt = 'onMouseOver="popalt(\''.__T('A_TASK_SCHEDULER_DATA_EXPORT_TOOLTIP_GENERATE_ONE_FILE_HOUR').'\')" onMouseOut="kill()"';
							foreach(getTaList('',$product) as $ta => $taLabel)
							{
								$selected = ($ta == $selectedTa) ? ' selected="selected"' : '';
								echo '<option value="'.$ta.'"'.$selected.'>'.$taLabel.'</option>';
							}
							?>
							</select>
						</td>
						<td align="left" width="225">
							<?php
							$checked = (($newExport ? DataExportModel::DEFAULT_GENERATE_HOUR_ON_DAY : $DataExportModel->getConfig('generate_hour_on_day')) == 1) ?  'checked' : '';
							?>
							<div id="div_generate_hour_on_day">
								<input name="generate_hour_on_day" id="generate_hour_on_day" type="checkbox" value="1" <?=$checked?> />
								<label for="generate_hour_on_day" <?=$popalt?> style="cursor:help"><?=__T('A_TASK_SCHEDULER_DATA_EXPORT_LABEL_GENERATE_HOURS_ON_DAY')?></label>
							</div>
						</td>
					</tr>
					<!-- Network Aggregation -->
					<tr>
						<td align="left">
							<?=__T('A_TASK_SCHEDULER_DATA_EXPORT_LABEL_NETWORK_AGGREGATION')?>
						</td>
						<td align="left">
							<select name="network_aggregation" onchange="">
							<?php
							// Liste de NA
							$naList = getNaLabelList('na',$family,$product);
							// Valeur sélectionnée
							$selectedNa = ($newExport ? DataExportModel::DEFAULT_NETWORK_AGGREGATION : $DataExportModel->getConfig('network_aggregation'));
							foreach($naList[$family] as $na => $naLabel)
							{
								$selected = ($na == $selectedNa) ? ' selected="selected"' : '';
								echo '<option value="'.$na.'"'.$selected.'>'.$naLabel.'</option>';
							}
							?>
							</select>
						</td>
						<td></td>
					</tr>
					<!-- Network Aggregation 3d Axis -->
					<?php
					if($axe3)
					{
						// On récupère la liste des NA 3ème axe pour la famille.
						$na_list = getNaLabelList('na_axe3',$family, $product);
						// On va chercher le label 3ème axe dans sys_definition_gt_axe, col external_reference.
						$axe_information = get_axe3_information_from_family($family, $product);
					?>
					<tr>
						<td align="left">
							<label for="na_axe3"><?=trim($axe_information['axe_type_label'][0])?></label>
						</td>
						<td align="left">
							<select name="na_axe3">
							<?
							// Selection le 3ieme axe
							$selectedNa = ($newExport ? DataExportModel::DEFAULT_NA_AXE3 : $DataExportModel->getConfig('na_axe3'));
							foreach($na_list[$family] as $id=>$label)
							{
								$selected = ( $selectedNa == $id ) ? ' selected = "selected"' : '';
								echo '<option value="'.$id.'" name="'.$id.'"'.$selected.'>'.$label.'</option>';
							}
							?>
							</select>
						</td>
						<td></td>
					</tr>
					<?php
					}
					?>


				</table>
			</fieldset>
			<!-- END Aggregation Levels -->

			<br />

			<!-- Counters -->
			<?php
			$rawsArray = Array();
			if($newExport) {
				// Tableau des Raws
				$rawsArray = DataExportModel::getAllRaws($family,$product);
			}
			else {
				// Tableau des Raws
				$rawsArray = $DataExportModel->getAvailableRaws();
			}
			// Affichage de la sélection de Raws uniquement s'il existe des Raws pour la famille
			if((!$newExport && (count($DataExportModel->getRaws()) > 0)) || (count($rawsArray) > 0))
			{
			?>
			<fieldset>
				<legend>
					<img alt="" src="<?=NIVEAU_0?>images/icones/small_puce_fieldset.gif" />
					&nbsp;<span class='texteGrisBold'><?=__T('A_TASK_SCHEDULER_DATA_EXPORT_LIST_TITLE_COUNTERS')?></span>
				</legend>
				<table width="550" cellpadding="0" cellspacing="0" border="0">
					<tr>
						<td align="center" valign="middle">
							<br />
							<select name="counters" id="counters" multiple class="dataSelector" ondblclick="getElementInfo(this,'raw','tooltip_raws')">
								<?php
								// S'il s'agit d'un nouvel export, on affiche tous les Raws
								foreach($rawsArray as $rawId => $rawLabel)
									echo '<option value="'.$rawId.'">'.$rawLabel.'</option>';
								?>
							</select>
						</td>
						<td align="center" valign="middle" width="25">
							<br />
							<button type="button" style="width:14px;height:15px;border:0;cursor:pointer" onclick="move_elements(1,'counters','counters_selected')">
								<img alt="" src="<?=NIVEAU_0?>images/calendar/right1.gif" border="0" />
							</button>
							<br /><br />
							<button type="button" style="width:14px;height:15px;border:0;cursor:pointer" onclick="move_elements(2,'counters','counters_selected')">
								<img alt="" src="<?=NIVEAU_0?>images/calendar/left1.gif" border="0" />
							</button>
						</td>
						<td align="center" valign="middle">
							<br />
							<select name="counters_selected" id="counters_selected" multiple class="dataSelector" ondblclick="getElementInfo(this,'raw','tooltip_raws')">
								<?php
								$counterIds = Array();
								if(!$newExport) {
									// S'il s'agit d'un export édité, on affiche les Raws sélectionnés
									foreach($DataExportModel->getRaws() as $rawId => $rawLabel) {
										echo '<option value="'.$rawId.'">'.$rawLabel.'</option>';
										$counterIds[] = $rawId;
									}
								}
								?>
							</select>
							<input type="hidden" id="hidden_counters" name="hidden_counters" value="" />
							<input type="hidden" id="hidden_counters_selected" name="hidden_counters_selected" value="<?=implode("|",$counterIds)?>" />
						</td>
					</tr>
					<tr>
						<td colspan="3">
							<br />
							<div id="tooltip_raws" class="infoBox" style="width:550px;text-align:left;font-size:7pt;">
								<?=__T("A_DATA_EXPORT_ELEMENT_INFO")?>
							</div>
						</td>
					</tr>
				</table>
			</fieldset>
			<?php
			}
			?>
			<!-- Counters END -->

			<br />

			<!-- KPIs -->
			<?php
			$kpisArray = Array();
			if($newExport) {
				// Tableau des KPIs
				$kpisArray = DataExportModel::getAllKpis($family,$product);
			}
			else {
				// Tableau des KPI

				$kpisArray = $DataExportModel->getAvailableKpis();
			}
			// Affichage de la sélection de Kpis uniquement s'il existe des Kpis pour la famille
			if((!$newExport && (count($DataExportModel->getKpis()) > 0)) || (count($kpisArray) > 0))
			{
			?>
			<fieldset>
				<legend>
					<img alt="" src="<?=NIVEAU_0?>images/icones/small_puce_fieldset.gif" />
					&nbsp;<span class='texteGrisBold'><?=__T('A_TASK_SCHEDULER_DATA_EXPORT_TITLE_KPIS')?></span>
				</legend>
				<table width="550" cellpadding="0" cellspacing="0" border="0">
					<tr>
						<td align="center" valign="middle">
							<br />
							<select name="kpis" id="kpis" multiple class="dataSelector" ondblclick="getElementInfo(this,'kpi','tooltip_kpis')">
								<?php
								// Affichage des Kpis disponibles
								foreach($kpisArray as $kpiId => $kpiLabel)
									echo '<option value="'.$kpiId.'">'.$kpiLabel.'</option>';
								?>
							</select>
						</td>
						<td align="center" valign="middle" width="25">
							<br />
							<button type="button" style="width:14px;height:15px;border:0;cursor:pointer" onclick="move_elements(1,'kpis','kpis_selected')">
								<img alt="" src="<?=NIVEAU_0?>images/calendar/right1.gif" border="0" />
							</button>
							<br /><br />
							<button type="button" style="width:14px;height:15px;border:0;cursor:pointer" onclick="move_elements(2,'kpis','kpis_selected')">
								<img alt="" src="<?=NIVEAU_0?>images/calendar/left1.gif" border="0" />
							</button>
						</td>
						<td align="center" valign="middle">
							<br />
							<select name="kpis_selected" id="kpis_selected" multiple class="dataSelector" ondblclick="getElementInfo(this,'kpi','tooltip_kpis')">
								<?php
								$kpiIds = Array();
								if(!$newExport) {
									// S'il s'agit d'un export édité, on affiche les Kpis sélectionnés
									foreach($DataExportModel->getKpis() as $kpiId => $kpiLabel) {
										echo '<option value="'.$kpiId.'">'.$kpiLabel.'</option>';
										$kpiIds[] = $kpiId;
									}
								}
								?>
							</select>
							<input type="hidden" id="hidden_kpis" name="hidden_kpis" value="" />
							<input type="hidden" id="hidden_kpis_selected" name="hidden_kpis_selected" value="<?=implode("|",$kpiIds)?>" />
						</td>
					</tr>
					<tr>
						<td colspan="3">
							<br />
							<div id="tooltip_kpis" class="infoBox" style="width:550px;text-align:left;font-size:7pt;">
								<?=__T("A_DATA_EXPORT_ELEMENT_INFO")?>
							</div>
						</td>
					</tr>
				</table>
			</fieldset>
			<?php
			}
			?>
			<!-- KPIs END -->

			<br />

			<!-- Form Buttons -->
			<div style="text-align:center;">
				<input type="submit" class="bouton" value="Save" name="submit" />
					&nbsp;
				<input type="submit" class="bouton" value="Generate File" name="generate_file" onmousedown="generateFile()" />
			</div>
			<!-- Form Buttons -->

		</form>
		<!-- FORM END -->


	</div>
</div>
</body>
</html>
