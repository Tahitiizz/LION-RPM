<?php
/*
   14/01/2011 DE Xpert MMT
 * Launch Xpert Viewer using parameters from Xpert links from GTM
 * Mostly Copied from LaunchAA.php
 *
*/
// 14/12/2011 BBX
// BZ 25128 : correction warning PHP
session_start();

if ( $repertoire_physique_niveau0 == "" ) {
    $msg_erreur = urlencode("Session time expired.");
    $file = "../../index.php?error=$msg_erreur";
    header("Location:$file");
}

include_once("../../php/environnement_liens.php");
//include_once(REP_PHYSIQUE_NIVEAU_0 . "php/database_connection.php");
include_once(REP_PHYSIQUE_NIVEAU_0."class/Xpert/XpertManager.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0."class/Xpert/XpertLauncher.class.php");

?>
<link rel="stylesheet" href="<?php echo NIVEAU_0; ?>css/global_interface.css" type="text/css">
<div class="tabPrincipal">
<?php

// get Required info from URL
$ne =			$_GET['ne'];
$ta =			$_GET['ta'];
$ta_value = $_GET['ta_value'];
$period =	$_GET['period'];
$product =	$_GET['product'];

// create Xpert instances
$XpertMger = XpertManager::getInstance();
$XpertLauncher = new XpertLauncher($XpertMger,$ne,$product,$ta,$ta_value,$period);

$XpertLauncher->loadNetworkElementAndFindMatchingXpert();
//display errors if any
if($XpertLauncher->hasEncounteredErrors()){
	echo "<fieldset class='texteGrisBold'><legend style='color:red'>&nbsp;Error&nbsp;</legend>";
	foreach ($XpertLauncher->getErrorMessages() as $msg){
		echo "<div >".$msg."</div>";
	}
}else{
	// redirect to the Xpert view via javascript
	$xpertUrl = $XpertLauncher->getXpertStatisticLinkUrl();
	$jsAction = "self.window.location = '".$xpertUrl."';";
	if($XpertMger->isDebugOn()){
		// if debug mode, do not redirect but add a link
		echo "<br>load Network Element And Find Matching Xpert successfull<br>
		      <br>URL to Xpert :$xpertUrl
		      <br><a href=\"javascript:$jsAction\">Click here to redirect</a><br>";
	}else{
		echo "<script language='javascript'>".$jsAction."</script>";
	}
}

?>
</div>