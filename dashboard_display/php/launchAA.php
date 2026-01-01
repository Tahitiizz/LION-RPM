<?php
/*
	28/05/2009 GHX
		- Remplacement des variables globales par les constantes
		- Suppressin du paramètre passé au constructeur

   09/06/2011 MMT bz 22322 besoin include NeModel.class pour faire du revert mapping

*/
?>
<?php
session_start();

if ( $repertoire_physique_niveau0 == "" ) {
    $msg_erreur = urlencode("Session time expired.");
    $file = "../../index.php?error=$msg_erreur";
    header("Location:$file");
}

include_once("../../php/environnement_liens.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/database_connection.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/edw_function.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/edw_function_family.php");
//09/06/2011 MMT bz 22322 besoin include NeModel.class pour faire du revert mapping
include_once(REP_PHYSIQUE_NIVEAU_0 . "models/NeModel.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/LinkToAA.class.php");

// Créer une instance pour créer le lien vers AA
$linkAA = new LinkToAA();
// Définit les paramètres récupérés dans l'url
$linkAA->setParameters($_GET['value']);
// Lance AA
?>
<link rel="stylesheet" href="<?php echo NIVEAU_0; ?>css/global_interface.css" type="text/css">
<div class="tabPrincipal">
<?php
$linkAA->launch();
?>
</div>