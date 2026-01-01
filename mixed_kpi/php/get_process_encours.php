<?php
/* 
* On check si un process est en cours pour la éviter la synchronisation des compteurs
*/
include_once(dirname(__FILE__)."/../../php/environnement_liens.php");
include_once(REP_PHYSIQUE_NIVEAU_0."mixed_kpi/php/functions.php");

$process_en_cours = checkProcessEncoursOnMixedKpi();

if(!$process_en_cours)
    echo "false";
else
    echo $process_en_cours;
?>
