<?
/*
*	03/10/2011 - Copyright Astellia
*
*	14/10/2011 ACS BZ 24186 Some icons and 1 label are not displayed
*	03/10/2011 ACS
*		- Mantis 615: DE Data reprocessing GUI
*/
?>
<?php
session_start();
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";

// Connexion à la base de données locale
$database = DataBase::getConnection();

include_once(REP_PHYSIQUE_NIVEAU_0 . "/class/DataReprocessing.class.php");


// Récupération du client type
$client_type = getClientType($_SESSION['id_user']);

$dataReprocessing = new DataReprocessing($db, $product['sdp_id']);

?>

<script type="text/javascript">
	niveau0 = "<?=NIVEAU_0?>";
	msgMode = "<?=__T('A_PROCESS_DATA_REPROCESS_MODE')?>";
	msgDate = "<?=__T('A_PROCESS_DATA_REPROCESS_DATE')?>";
	msgDateLimitReprocess = "<?=__T('A_PROCESS_DATA_REPROCESS_LIMIT')?>";
	msgDateWarningReprocess = "<?=__T('A_PROCESS_DATA_REPROCESS_WARNING')?>";
	msgDateWarningDelete = "<?=__T('A_PROCESS_DATA_DELETE_WARNING', "__nbDays__")?>";   
    msgConnection = "<?=__T('A_PROCESS_DATA_REPROCESS_CONNECTION')?>";
	warningMessageForReprocess = "<?=addslashes(__T('A_PROCESS_DATA_REPROCESS_WARNING_REPROCESS', "__dates__"))?>";
	warningMessageProcessOff = "<?=addslashes(__T('A_PROCESS_DATA_REPROCESS_PROCESS_OFF'))?>";
	warningMessageForDelete = "<?=addslashes(__T('A_PROCESS_DATA_REPROCESS_WARNING_DELETE', "__dates__", "__connections__"))?>";
	
	if (typeof(areaDisplay)=='undefined') {
		areaDisplay = new Array();
	}
	areaDisplay[<?=$product['sdp_id']?>] = "none";
</script>
<script type="text/javascript" src="<?=NIVEAU_0?>js/data_reprocessing.js"></script>

<?
if(!$db->columnExists('sys_flat_file_uploaded_list_archive','reprocess')) {
?>
	<div class="dataReprocessUnavailable"><?=__T('A_PROCESS_DATA_DE_MISSING')?></div>
<?
}
else {
?>
	<div class="dataReprocess">
		<span class="dataReprocessTitle" onclick="switchAreaDisplay(<?=$product['sdp_id']?>)"><img id="dataReprocessImg_<?=$product['sdp_id']?>" src="<?=NIVEAU_0?>images/icones/plus_alarme.gif" /> Data reprocessing</span>
		<div class="dataReprocessArea" id="dataReprocessArea_<?=$product['sdp_id']?>">
			<form method="post" id="dataReprocessForm_<?=$product['sdp_id']?>" action="">
				<input type="hidden" name="product" value="<?=$product['sdp_id']?>" />
				<div id="mode">
					<input type="radio" id="mode0_<?=$product['sdp_id']?>" name="mode" value = "0" onchange="changeMode(<?=$product['sdp_id']?>, 0);" onclick="changeMode(<?=$product['sdp_id']?>, 0);" /><label for="mode0_<?=$product['sdp_id']?>">Reprocess files</label> <img onmouseover="popalt('<?=__T('A_PROCESS_DATA_REPROCESS_PROCESS_TOOLTIP')?>')" src="<?=NIVEAU_0?>images/icones/information.png" />
					<br /><input type="radio" id="mode1_<?=$product['sdp_id']?>" name="mode" value = "1" onchange="changeMode(<?=$product['sdp_id']?>, 1);" onclick="changeMode(<?=$product['sdp_id']?>, 1);" /><label for="mode1_<?=$product['sdp_id']?>">Delete files</label> <img onmouseover="popalt('<?=__T('A_PROCESS_DATA_REPROCESS_DELETE_TOOLTIP')?>')" src="<?=NIVEAU_0?>images/icones/information.png" />
				</div>
				
				<div class="dataReprocessDates" id="dataReprocessDates_<?=$product['sdp_id']?>">
					<div class="dataReprocessDatesTitle"><span>Dates</span> <img onmouseover="popalt('<?=__T('A_PROCESS_DATA_REPROCESS_DATE_TOOLTIP')?>')" src="<?=NIVEAU_0?>images/icones/information.png" /></div>
					<div>
						<select id="dates_<?=$product['sdp_id']?>" name="dates[]" multiple="5">
							<? 
								foreach ($dataReprocessing->getAvailableDates($product['sdp_id']) as $dateLabel => $date) {
									echo "<option value=\"".$date."\">".$dateLabel."</option>";
								}
							?>
						</select>
					</div>
					<div class="clear"></div>
				</div>
				
				<div class="dataReprocessConnections" id="dataReprocessConnections_<?=$product['sdp_id']?>">
					<div class="dataReprocessConnectionsTitle">Connections</div>
					<div>
						<select id="connections_<?=$product['sdp_id']?>" name="connections[]" multiple="5">
							<?
								foreach ($dataReprocessing->getActiveConnections() as $connectionName => $connectionId) {
									echo "<option value=\"".$connectionId."\">".$connectionName."</option>";
								}
							?>
						</select>
					</div>
	
					<div class="dataReprocessSelectAllConnections">
						<input class="bouton" type="button" value="Select all connections" onclick="selectAllConnections(<?=$product['sdp_id']?>);" />
					</div>
	
				</div>
				
				<div class="clear"></div>
				
				<div class="dataReprocessExecute" id="dataReprocessExecute_<?=$product['sdp_id']?>">
					<input class="bouton" type="button" id="execute_<?=$product['sdp_id']?>" value="Execute" onclick="executeDataReprocess('<?=$product['sdp_id']?>');" />
				</div>
			</form>
		</div>
	</div>
<?
}
?>