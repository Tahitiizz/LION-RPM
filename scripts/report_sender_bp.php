<?php
/**
 * 
 *  CB 5.2
 * 
 * 23/02/2012 NSE DE Astellia Portal Lot2
 */
?><?php
/**
 * @file report_sender_bp.php
 *
 * Script gérant l'exécution du script report sender pour un Produit Blanc. Le 
 * script report_sender.php n'est pas directement exécuter mais lancé ici afin
 * rediriger le flux de sortie dans le démon standard;
 *
 * $Author: o.jousset $
 * $Date: 2011-09-16 11:01:01 +0200 (ven., 16 sept. 2011) $
 * $Rev: 28221 $
 */

include_once(dirname(__FILE__)."/../php/environnement_liens.php");

// Heure par défaut en cas de problème
define( 'REPORT_SENDER_DEF_HOUR', 5 );

// Lecture du paramètre global bp_report_sender_hour (par défaut 5)
$reportHour = intval( get_sys_global_parameters( 'bp_report_sender_hour', REPORT_SENDER_DEF_HOUR ) );
$fileDemon  = REP_PHYSIQUE_NIVEAU_0."file_demon/demon_".date( 'Ymd' ).".html";

// L'heure courante correspond t-elle à l'heure configurée ?
if( intval( date( 'H' ) ) === $reportHour )
{
    // Vérification que le process est bien à On
    $db    = Database::getConnection();
    $onOff = $db->getOne( "SELECT on_off FROM sys_definition_master WHERE master_name='Report Sender'" );
    
    if( $onOff == 1 )
    {
        // 22/02/2012 NSE DE Astellia Portal Lot2
        // on tente de mettre à jour la liste de utilisateurs
        $synchoHandler = popen('php '.REP_PHYSIQUE_NIVEAU_0.'/scripts/user_synchro.php', 'r');
        // attente de la fin du process pour récupérer l'erreur éventuelle
        $read = fread($synchoHandler, 2096);
        pclose($synchoHandler);
        
        exec( "php -q ".REP_PHYSIQUE_NIVEAU_0."scripts/report_sender.php >> {$fileDemon}" );
    }
}
