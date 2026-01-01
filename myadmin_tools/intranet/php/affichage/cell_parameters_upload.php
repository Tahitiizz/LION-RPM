<?php
/* cb 4.0.0.03
*
*	- maj 27/05/2008 Benjamin : modification du message d'information sur l'upload de cellules inexistantes. BZ6768
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
	<title>Cell Parameters Upload</title>
	 <link rel="stylesheet" type="text/css" media="all" href="<?=$niveau0?>css/global_interface.css" />
</head>
<body>

<?php

	$delimiter = $_POST['delimiter'];

	$file_name = $_FILES['csv_file']['name'];
	$file_size = $_FILES['csv_file']['size'];
	$file_type = $_FILES['csv_file']['type'];

	// On verifie que le fichier à uploader est correct

	if ($file_size == 0)
	{
		?>
		<script>
			alert('Error empty file');
			window.history.go(-1);
		</script>
		<?
	}
	elseif ( $file_type != "text/plain" && $file_type != "application/octet-stream" && $file_type != "application/vnd.ms-excel" )
	{
		?>
		<script>
			alert('Error file type');
			window.history.go(-1);
		</script>
		<?
	}

	// On uploade le fichier après avoir remplacé les espaces dans le nom du fichier par des '_' (sinon problemes sur les systemes unix)

	$file_name = str_replace(' ', '_', $file_name);
	$destination = $repertoire_physique_niveau0 . "upload/".$file_name;
	
	if (!copy($_FILES['csv_file']['tmp_name'], $destination))
	{
		$error = "Error during the upload of csv file";
	}
	else // Si la copie se déroule bien, on va inserer les données du fichier en base
	{
		// On verifie la première ligne du fichier csv. Si c'est le header, on le supprime

		$lines = file($destination);

		if (trim($lines[0]) == "cell".$delimiter."nb_tch".$delimiter."hr_or_fr") {
			$lines = array_slice($lines, 1);
			
			$handle = fopen($destination, 'w+');
			fwrite($handle, implode('', $lines));
			fclose($handle);
		}

		// On verifie que le delimiteur utilisé est bien celui utilisé dans le fichier et que le nombre de champs du fichier est correct

		if (strpos($lines[0], $delimiter) === false) {	// Le delimiteur n'existe pas
			$error = (strpos(__T('A_E_UPLOAD_TOPO_DELIMITER_NOT_VALID'), "Undefined") === false) ? __T('A_E_UPLOAD_TOPO_DELIMITER_NOT_VALID') : "The delimiter is not valid";
		}
		else if (count(explode($delimiter, $lines[0])) != 3) {	// Le nombre de champs est différent de celui attendu
			$error = "Invalid number of fields (".count(explode($delimiter, $lines[0]))." find, 3 expected)";
		}
		else
		{
			// On insere le contenu du fichier dans la table 'edw_object_capacity_ref'

			$cmd_sql = array();

			$cmd_sql[] = "BEGIN;";	// debut de la transcation
			
			// Creation d'une table temporaire qui va contenir les données du fichier csv

			$cmd_sql[] = "CREATE TEMP TABLE edw_object_capacity_ref_temp ON COMMIT DROP AS SELECT * FROM edw_object_capacity_ref;";
			$cmd_sql[] = "TRUNCATE edw_object_capacity_ref_temp;";
			$cmd_sql[] = "COPY edw_object_capacity_ref_temp FROM '".$destination."' USING DELIMITERS '".$delimiter."';";
						
			// Mise à jour des lignes existantes dans 'edw_object_capacity_ref'

			$cmd_sql[] = " UPDATE edw_object_capacity_ref SET nb_tch = tmp.nb_tch, hr_or_fr = tmp.hr_or_fr"
						." FROM edw_object_capacity_ref_temp tmp"
						." WHERE tmp.cell = edw_object_capacity_ref.cell;";

			// Insertion des nouvelles lignes dans 'edw_object_capacity_ref'

			$cmd_sql[] = " INSERT INTO edw_object_capacity_ref(cell, nb_tch, hr_or_fr)"
						." SELECT cell, nb_tch, hr_or_fr FROM edw_object_capacity_ref_temp"
						." WHERE cell NOT IN (SELECT DISTINCT cell FROM edw_object_capacity_ref);";
			
			$cmd_sql[] = "COMMIT;";	// Validation de la transaction et suppression de la table temporaire

			for ($i=0; $i < count($cmd_sql); $i++) {
				if(!@pg_query($database_connection, $cmd_sql[$i])){
					$error = (strpos(__T('A_E_UPLOAD_TOPO_MAJ_DATA'), "Undefined") === false) ? __T('A_E_UPLOAD_TOPO_MAJ_DATA') : "Error during the update of data";
				}
			}
			
			// On verfie qu'il n'existe pas dans la table 'edw_object_capacity_ref' des cellules non référencées dans 'edw_object_1_ref'

			$cells_not_exist = array();

			$sql = "SELECT cell FROM edw_object_capacity_ref WHERE cell NOT IN (SELECT cell FROM edw_object_1_ref)";
			$req = pg_query($database_connection, $sql);

			while ($row = pg_fetch_array($req)) {
				$cells_not_exist[] = $row['cell'];
			}

			// Si des cellules non référencées existent, on les supprime

			if (count($cells_not_exist) > 0) {
				$sql = "DELETE FROM edw_object_capacity_ref WHERE cell NOT IN (SELECT cell FROM edw_object_1_ref)";
				$req = pg_query($database_connection, $sql);				
			}

		}
	}

	// Affichage du resultat de l'insertion

	if (isset($error) && $error != "") {	// Il existe des erreurs, on les affiche
?>		
		<table width="550" align="center" cellpadding="10px" cellspacing="0px">
			<tr>
				<td align="center">
					<img src="<?=$niveau0?>images/titres/setup_cell_parameters.gif"/>
				</td>
			</tr>
			<tr class="tabPrincipal">
				<td align="center" class="texteGrisBold">An error occurred during the update of the data.</td>
			</tr>
			<tr class="tabPrincipal">
				<td class="texteGrisBold">
					<?=$error?>
				</td>
			</tr>
		</table>
<?php
	}
	else // L'insertion des données du fichier a été correctement réalisée, on affiche les données de la table 'edw_object_capacity_ref'
	{
?>
		<table width="550" align="center" cellpadding="10px" cellspacing="0px">
			<tr>
				<td align="center">
					<img src="<?=$niveau0?>images/titres/setup_cell_parameters.gif"/>
				</td>
			</tr>			
			<tr class="tabPrincipal">
				<td align="center" class="texteGrisBold">Cell parameters uploaded successfully!</td>
			</tr>
			<?php
				
				// On regarde s'il existe des cellules non insérées. Dans ce cas, on informe l'utilisateur

				if (count($cells_not_exist) > 0) {
			?>
			<tr class="tabPrincipal">
				<td align="center" class="texteGrisPetit" style="color:red">
				<?php

					$some = ((count($cells_not_exist) > 1) ? "s" : "");
					// maj 27/05/2008 Benjamin : modification du message d'information sur l'upload de cellules inexistantes. BZ6768
					//echo "Information : Cell".$some." ".implode(", ", $cells_not_exist)." not exist".$some." in topology and ".(($some == "s") ? "are" : "is")." not inserted.";
					echo "Information : Non-existent cell(s) in topology have not been inserted.<br />( List of concerned Cell(s): ".implode(", ", $cells_not_exist)." )";
				?>
				</td>
			</tr>
			<?php
				}
			?>
			<tr class="tabPrincipal"><td>
			<?php

				$sql = "SELECT * FROM edw_object_capacity_ref ORDER BY cell ASC";
				$req = pg_query($database_connection, $sql);

				if (pg_num_rows($req) == 0) {
					echo "No cell parameters";
				}
				else 
				{
					echo '<div style="width:100%;height:250px;overflow:auto" class="fondGrisClair">';
					echo '<table class="texteGris" width="100%"><tr align="center"><td class="texteGrisBold" style="background-color:#b4b4b4;">Cell</td><td class="texteGrisBold" style="background-color:#b4b4b4;">Number of TCH</td><td class="texteGrisBold" style="background-color:#b4b4b4;">Half Rate / Full Rate</td></tr>';

					while ($row = pg_fetch_array($req)) {			
						echo '<tr align="center"><td class="texteGrisPetit">'.$row['cell'].'</td><td class="texteGrisPetit">'.$row['nb_tch'].'</td><td class="texteGrisPetit">'.(($row['hr_or_fr'] == 1) ? "Half rate" : "Full rate").'</td></tr>';
					}

					echo '</table></div>';
				}
			?>
			</td></tr>
		</table>
<?php
	}
?>
</body>
</html>