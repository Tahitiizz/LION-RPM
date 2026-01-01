<?
/*
*
* 08/06/2011 MMT bz 22474 la div ne s'ajuste pas au contenu, utilisation d'une table comme pour les alarmes
*
* @cb50000@
*
*	16/07/2009 - Copyright Astellia
*
*	IHM de gestion des Data Export - index
 * 16/12/2011 ACS BZ 25158 Back button missing for 3axis slave products
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
include_once(REP_PHYSIQUE_NIVEAU_0.'intranet_top.php');

// Sélection famille / produit
if(!isset($_GET["family"])){
	$select_family = new select_family($_SERVER['PHP_SELF'], $_SERVER['argv'][0], 'Export');
	exit;
}

// GET Values
$family = $_GET["family"];
$product = $_GET["product"];
$exportNameMaxLength = 50;

// Infos produit
$productInformation = getProductInformations($product);
$productLabel = $productInformation[$product]['sdp_label'];

// Suppression d'un export
if(isset($_GET['action']) && isset($_GET['export_id']) && ($_GET['action'] == 'del'))
{
	$DataExportModel = new DataExportModel($_GET['export_id'],$product);
	$DataExportModel->deleteExport();
	unset($DataExportModel);
}

// Récupération des exports
$exportArray = DataExportModel::getExportList($family,$product);
?>

<div id="container" style="width:100%;text-align:center">
	<img src="<?=NIVEAU_0?>images/titres/export_setup_interface.gif" />
	<br /><br />
	<!-- 08/06/2011 MMT bz 22474 la div ne s'ajuste pas au contenu, utilisation d'une table comme pour les alarmes-->
	<table class="tabPrincipal" width="600px" align="center" cellpadding="8px">
		<tbody><tr><td style="text-align:center;">

		<!-- Family Information -->
		<div class="texteGris" style="height:20px;position:relative;text-align:center;">
		<fieldset style="height:100%;padding-top:5px;">
			<?
				$family_information = get_family_information_from_family($family,$product);
				echo __T('A_TASK_SCHEDULER_DATA_EXPORT_TITLE_CURRENT_FAMILY').' : '.(ucfirst($family_information['family_label']));
				echo '&nbsp;('.$productLabel.')';
				// 16/12/2011 ACS BZ 25158 Back button missing for 3axis slave products
			?>
			<div style="position:absolute;top:5px;right:5px;">
				<a href="index.php?product=<?=$product?>" target="_top">
					<img src="<?=NIVEAU_0?>images/icones/change.gif" onMouseOver="popalt('Change family');" onMouseOut="kill()" border="0" />
				</a>
			</div>
		</fieldset>
		</div>
		<!-- END Family Information -->

		<br /><br />

		<!-- New Export -->
		<input type="button"
				onclick="window.location='export_edit.php?family=<?=$family?>&product=<?=$product?>'"
				class="bouton"
				name="parameter"
				value="<?=__T('A_TASK_SCHEDULER_DATA_EXPORT_BTN_NEW_EXPORT')?>" />
		<br /><br />
		<!-- END New Export -->

		<!-- Export list -->
		<fieldset>
			<legend class="texteGrisBold">
				&nbsp;
				<img src="<?=NIVEAU_0?>images/icones/small_puce_fieldset.gif"/>
				&nbsp;
				<?=__T('A_TASK_SCHEDULER_DATA_EXPORT_TITLE_INDEX')?>
				&nbsp;
			</legend>

		<?php
		// Si il y a des exports, on les liste
		if(count($exportArray) > 0)
		{
		?>
			<table width="550" valign="middle" cellpadding="2" cellspacing="1">
				<tr>
					<th class="texteGrisBold" width="200px">
						<?=__T('A_TASK_SCHEDULER_DATA_EXPORT_LABEL_EXPORT_NAME')?>
					</th>
					<th class="texteGrisBold">
						<?=__T('A_TASK_SCHEDULER_DATA_EXPORT_LABEL_NA')?>
					</th>
					<th class="texteGrisBold" width="100px">
						<?=__T('A_TASK_SCHEDULER_DATA_EXPORT_LABEL_PERIODICITY')?>
					</th>
					<th class="texteGrisBold" nowrap="nowrap"  width="200px">
						<?=__T('A_TASK_SCHEDULER_DATA_EXPORT_TARGET_FILE')?>
					</th>
					<th>
						&nbsp;
					</th>
					<th>
						&nbsp;
					</th>
				</tr>
			<?php
			$i = 0;
			foreach($exportArray as $exportId => $exportData)
			{
				$bgColor = ($i%2 == 0) ? '#DDDDDD' : '#ffffff';
				switch($exportData['time_aggregation'])
				{
					case 'hour':
						$exportData['time_aggregation'] = 'Hourly';
						break;
					case 'day':
					case 'day_bh':
						$exportData['time_aggregation'] = 'Daily';
						break;
					case 'week':
					case 'week_bh':
						$exportData['time_aggregation'] = 'Weekly';
						break;
					case 'month':
					case 'month_bh':
						$exportData['time_aggregation'] = 'Monthly';
						break;
				}
			?>
				<tr class="texteGris">
					<td align="left" style="background-color:<?=$bgColor?>">
                                                <?php
                                                // 13/04/2012 BBX
                                                // BZ 19984 : on insère des retours chariots dans les nom d'exports trop longs
                                                $exportName = $exportData['export_name'];
                                                if( strlen( $exportData['export_name'] ) > $exportNameMaxLength )
                                                {
                                                    $exportName = '';
                                                    foreach( str_split( $exportData['export_name'], $exportNameMaxLength ) as $splitPart )
                                                        $exportName .= htmlentities( $splitPart ).'<br />';
                                                }
                                                ?>
						<?=$exportName?>
					</td>
					<td align="center" style="background-color:<?=$bgColor?>">
						<?=$exportData['network_aggregation']?>
					</td>
					<td align="center" style="background-color:<?=$bgColor?>">
						<?=$exportData['time_aggregation']?>
					</td>
					<td align="center" style="background-color:<?=$bgColor?>">
						<?=$exportData['target_file']?>
					</td>
					<td>
						<a onclick="return confirm('<?=__T('A_JS_TASK_SCHEDULER_DATA_EXPORT_CONFIRM_EXPORT_DELETE',$exportData['export_name'])?>');" href="index.php?family=<?=$family?>&action=del&export_id=<?=$exportId?>&product=<?=$product?>">
							<img src="<?=NIVEAU_0?>images/icones/drop.gif" border="0" alt="Delete export" />
						</a>
					</td>
					<td>
						<a title="Export Setup" href="export_edit.php?family=<?=$family?>&export_id=<?=$exportId?>&product=<?=$product?>">
							<img src="<?=NIVEAU_0?>images/icones/A_more.gif" border="0" alt="Schedule and Subscribers" />
						</a>
					</td>
				</tr>
			<?php
				$i++;
			}
			?>
			</table>
		<?php
		}
		// S'il n'y a pas d'exports, on affiche un message informant qu'il n'y a pas d'exports
		else
		{
		?>
			<div class="texteGrisBold"><?=__T('A_E_TASK_SCHEDULER_DATA_EXPORT_NO_EXPORT_CREATED')?></div>
		<?php
		}
		?>

		</fieldset>
		<!-- END Export list -->
		<!-- 08/06/2011 MMT bz 22474 la div ne s'ajuste pas au contenu, utilisation d'une table comme pour les alarmes-->
		</td></tr></tbody>
	</table>
</div>
</body>
</html>