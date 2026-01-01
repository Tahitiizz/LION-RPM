<?php
/*
 * 09/06/2011 MMT bz 22322 besoin include NeModel.class pour faire du revert mapping
 */
session_start();

if ( $repertoire_physique_niveau0 == "" ) {
    $msg_erreur = urlencode("Session time expired.");
    $file = "../../../../index.php?error=$msg_erreur";
    header("Location:$file");
}

include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
include_once($repertoire_physique_niveau0 . "php/database_connection.php");
include_once($repertoire_physique_niveau0 . "php/edw_function.php");
include_once($repertoire_physique_niveau0 . "php/edw_function_family.php");
//09/06/2011 MMT bz 22322 besoin include NeModel.class pour faire du revert mapping
include_once($repertoire_physique_niveau0 . "models/NeModel.class.php");
include_once($repertoire_physique_niveau0 . "class/LinkToAA.class.php");

// Créer une instance pour créer le lien vers AA
$linkAA = new LinkToAA($database_connection);
// Définit les paramètres récupérés dans l'url
$linkAA->setParameters($_GET['value']);
// Lance AA
?>
<link rel="stylesheet" href="<?php echo $niveau0; ?>css/global_interface.css" type="text/css">
<div class="tabPrincipal">
<?php
$linkAA->launch();
?>
</div>