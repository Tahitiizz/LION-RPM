<?php
/*
 *	@cb50412@
 *
 *	03/01/2011 - Copyright Astellia
 *
 *	Ajax appelé depuis la page d'accueil de l'application pour la vérification des paramètres SSH de chaque serveur slave
 *  Script développé dans le cadre de la correction du BZ 19673
 *
 */
?>
<?php
//session_start();
include dirname( __FILE__ ).'/php/environnement_liens.php';

// Librairies et classes requises
include REP_PHYSIQUE_NIVEAU_0.'class/CheckConfig.class.php';
include REP_PHYSIQUE_NIVEAU_0.'class/SSHConnection.class.php';

$sshError = CheckConfig::SSH();
if ( $sshError !== true )
{
    echo $sshError;
}
?>