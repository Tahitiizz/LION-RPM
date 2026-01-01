<?php
/*
 * Prompts the browser's user with download of CVS user export file for the astellia portal 
 * in order to include all T&A users in the Portal
 * 
 * uses the script genUsersCSVForPortal.php to generate the file and just does
 * error management, message display and download
 *
 * This script will not be required with Portal Lot 2 and should then be removed
 *
 * 30/08/2011 MMT - DE Portal Lot1 Creation du fichier
 * 06/05/2013 NSE - DE Phone number managed by Portal
 */

include_once(dirname(__FILE__)."/../php/environnement_liens.php");

$error = "";
// execute la generation du CSV
$cmdRet = exec("php genUsersCSVForPortal.php");
$successPostFix = "SUCCESS:";
// test le prefix de valeure de retour pour succes/echec
if(substr($cmdRet,0,strlen($successPostFix)) == $successPostFix){
	$csvFile = substr($cmdRet,strlen($successPostFix));
} else {
	$error = $cmdRet;
}
// verifie si le fichier existe bien
if(empty($error) && (empty($csvFile) || !file_exists($csvFile))){
	$error = "File was not generated";
}

// affichage  erreur / success avec lien sur force_download.php pour download du fichier
?>
<link rel="stylesheet" href="<?php echo $niveau0; ?>css/global_interface.css" type="text/css">

<?php if (!empty($error)){ ?>
	<fieldset class="texteGrisBold" style="color:red">
		<?=__T("A_TOOLS_USER_EXPORT_ERROR",$error)?>
	</fieldset>

<?php }else{ ?>

	<fieldset class="texteGrisBold">
		<?=__T("A_TOOLS_USER_EXPORT_SUCCESS")?>
		<br><br>
		<a href="<?=NIVEAU_0?>php/force_download.php?filepath=<?=$csvFile?>" >Click here to download the file</a>
                <p><b>Notice</b>: Generated file is not compatible with Portal version lower than 2.1.</p>
	</fieldset>
<?php }?>

