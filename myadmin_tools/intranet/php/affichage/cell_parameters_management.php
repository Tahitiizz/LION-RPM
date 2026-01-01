<?php
/*
*	cb 4.0.0.03
*
*	- maj 27/05/2005 Benjamin : Modification du label du bouton, CVS => CSV. BZ6767
*
*/
?>
<?php
	
	session_start();

	include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
	include_once($repertoire_physique_niveau0 . "php/database_connection.php");
	include_once($repertoire_physique_niveau0 . "php/edw_function.php");
	include_once($repertoire_physique_niveau0 . "php/postgres_functions.php");
	
	// On inclut le bandeau haut
	
	include_once($repertoire_physique_niveau0 . "intranet_top.php");
?>

<html>
<head>
	<title>Setup Cell Parameters</title>
	 <link rel="stylesheet" type="text/css" media="all" href="<?=$niveau0?>css/global_interface.css" />
	 <script type="text/javascript" src="<?=$niveau0?>js/prototype/prototype.js"> </script>
	<script>
	<!--

		function uploadCellParameters(){

			var pars = Form.serialize('upload_obj_ref');

			var niveau0 = '<?=$niveau0?>';

			var url = niveau0+'myadmin_tools/intranet/php/affichage/cell_parameters_upload.php';
	
			new Ajax.Updater('upload_result', 'cell_parameters_upload.php', {method: 'post', parameters: pars});

		}

		function showResponse(originalRequest){
			alert(originalRequest);
		}

	//-->
	</script>
</head>
<body>

<?php

	// On vérifie d'abord si la table 'edw_object_capacity_ref' existe (normalement c'est le cas si le menu est accessible)

	$sql = "SELECT * FROM pg_tables WHERE tablename = 'edw_object_capacity_ref'";
	$req = pg_query($database_connection, $sql);

	if (pg_num_rows($req) == 0) {
?>
	<table width="550px" align="center" class="tabPrincipal" cellpadding="10px">
		<tr>
			<td align="center">
				<img src="<?=$niveau0?>images/titres/setup_cell_parameters.gif"/>
			</td>
		</tr>
		<tr>
			<td align="center" class="texteGrisBold">"Setup Cell parameters" is not available for this version</td>
		</tr>
	</table>
<?php
	}
	else 
	{
?>
	<table border="0" width="550px" cellspacing="0" cellpadding="10px" align="center">
		<tr>
			<td align="center">
				<img src="<?=$niveau0?>images/titres/setup_cell_parameters.gif"/>
			</td>
		</tr>
		<tr>
		<td align="center">
			<div class="tabPrincipal">
				<div style="padding:15px">
					<fieldset>
						<legend class="texteGrisBold">&nbsp;<img src="<?=$niveau0?>images/icones/small_puce_fieldset.gif"/>&nbsp;Download&nbsp;</legend>
						<input type="button" id="download_cell_parameters" class="bouton" onclick="document.location.href='cell_parameters_download.php'" value="Download current cell parameters"/>
					</fieldset>
				</div>
				<div style="padding:15px;padding-top:0px">
					<form enctype='multipart/form-data' id="upload_obj_ref" name='upload_obj_ref' method="post" action="cell_parameters_upload.php">
					<fieldset>
						<legend class="texteGrisBold">&nbsp;<img src="<?=$niveau0?>images/icones/small_puce_fieldset.gif"/>&nbsp;Upload&nbsp;</legend>
						
						<div style="padding:10px">
							<span style="text-align:left;float: left; margin-top: 2px; width: 35%;" class="texteGrisBold">&#149;&nbsp;CSV File to upload</span>
							<span style="float:left"><input type="file" size="30" id="csv_file" name="csv_file"/></span>
							<span style="float:left" class="texteGrisPetit">&nbsp;&nbsp;<?=((strpos(__T('A_CELL_PARAMETERS_FILE_FORMAT'), "Undefined") === false) ? __T('A_CELL_PARAMETERS_FILE_FORMAT') : "CSV File format : Cell name[delimiter]TCH number[delimiter]Half Rate (1) or Full Rate (0)")?></span>
						</div>
						
						<div style="padding:10px">
							<span style="text-align:left;float: left; margin-top: 2px; width: 35%;" class="texteGrisBold">&#149;&nbsp;Delimiter
								<br/><span class="texteGrisPetit">Each field in the CSV file must be separated with delimiter</span>
							</span>
							<span style="float:left">
								<select id="delimiter" name="delimiter">
									<option value=";" selected>;</option>
									<option value=",">,</option>
								</select>
							</span>						
						</div>

						<div style="padding:10px">
							<span style="clear:both;">
							<?php
							// maj 27/05/2005 Benjamin : Modification du label du bouton, CVS => CSV. BZ6767
							?>
								<input type="submit" id="upload_parameters" class="bouton" value="Upload CSV file"/>
							</span>						
						</div>
<!-- 						<div id="upload_result">Result</div> -->
					</fieldset>
					</form>
				</div>
			</div>
		</td>
		</tr>
	</table>

<?php
	}
?>
</body>
</html>