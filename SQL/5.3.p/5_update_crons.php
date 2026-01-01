<?php
/**
 * 05/12/2011 BZ 24843 BBX
 * Modification de la cron save_database
 * Désormais on essaie de sauvegarder toutes les 10 minutes au lieu de toutes les 30 minutes
 * Penser à adapter ce script dans 5.1.p lors du merge notemment pour les chemins PHP
 */
include_once(dirname(__FILE__)."/../../php/environnement_liens.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/edw_function.php");

$cronString = '0,30 12-23 * * 0 php -q '.REP_PHYSIQUE_NIVEAU_0.'scripts/save_database.php &> /dev/null';
$newCronString = '*/10 12-23 * * 0 php -q '.REP_PHYSIQUE_NIVEAU_0.'scripts/save_database.php &> /dev/null';
$cronFile   = file('/var/spool/cron/astellia');

foreach($cronFile as $offset => $line)
{
    $line = trim($line);
    if($line == $cronString) {
        $cronFile[$offset] = $newCronString;
    }else{
    	$cronFile[$offset] = $line;
    }
}

file_put_contents('/var/spool/cron/astellia', implode("\n",$cronFile));
?>
