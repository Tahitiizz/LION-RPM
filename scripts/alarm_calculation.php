<?php
/**
* Script qui permet le lancement du calcul des alarmes
*
* Permet de lancer le calcul des les alarmes
* @author BBX
* @version CB 5.1.0.00
* @package Alarmes
* @since CB 5.1.0.00
* @creation 07/06/2010
*/
?>
<?php
// SESSION
session_start();

// TIME LIMIT
set_time_limit(0);

// INCLUDES
include_once(dirname(__FILE__).'/../php/environnement_liens.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'php/edw_function_family.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'class/AlarmCompute.class.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'class/AlarmCalculation.class.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'class/AlarmCalculationStatic.class.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'class/AlarmCalculationDynamic.class.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'class/AlarmCalculationTopWorst.class.php');

// HEADER HTML
if(isset($_SERVER["HTTP_USER_AGENT"])) {
    $onload = '';
    $arborescence = 'Alarm Calculation';
    include_once(REP_PHYSIQUE_NIVEAU_0.'php/header.php');
}

// TITRE
displayInDemon('Alarm Calculation','title');

// CALCULATION

// Instance compute Alarm
$alarmCompute = new AlarmCompute();

// Configuration de l'objet
$alarmCompute->setSeparator(get_sys_global_parameters('sep_axe3'));
$alarmCompute->setOffsetDay(get_sys_global_parameters('offset_day'));
$alarmCompute->setComputeSwitch(get_sys_global_parameters('compute_switch'));
$alarmCompute->setHourToCompute(get_sys_global_parameters('hour_to_compute'));
$alarmCompute->setMaxResults(get_sys_global_parameters('alarm_result_limit'));
$alarmCompute->setFirstDayOfWeek(get_sys_global_parameters('week_starts_on_monday'));

// Configuration du mode débug
if(get_sys_debug('alarm_calculation') == 1)
    $alarmCompute->setDebug(true);

// Instances AlarmCalculation
$alarmCalculationStatic = new AlarmCalculationStatic();
$alarmCalculationDynamic = new AlarmCalculationDynamic();
$alarmCalculationTopWorst = new AlarmCalculationTopWorst();

// Ajout des types d'alarmes à calculer
$alarmCompute->addCalculationType($alarmCalculationStatic);
$alarmCompute->addCalculationType($alarmCalculationDynamic);
$alarmCompute->addCalculationType($alarmCalculationTopWorst);

// Nettoyage des résultats précédents
$before = microtime(true);
$nbDeletedResults = $alarmCompute->cleanResults();

// Vacuum
$alarmCompute->vacuum();

$after = microtime(true);
$total = round($after - $before,2);

// Affichage démon
if($nbDeletedResults !== false) displayInDemon(htmlentities('Nettoyage des résultats... '.$nbDeletedResults.' résutat(s) supprimé(s) en ').$total.'s','list');
else displayInDemon(htmlentities('Impossible de nettoyer les résultats des alarmes').'<br />','alert');

// Lancement du calcul
try {
    $before = microtime(true);
    $compute = $alarmCompute->computeAlarm();

    // Vacuum
    $alarmCompute->vacuum(1);

    $after = microtime(true);
    $total = round($after - $before,2);
    // Affichage démon
    displayInDemon(htmlentities('Calcul réalisé en ').$total.'s','list');
}
catch (Exception $e) {
    displayInDemon(htmlentities($e->getMessage()).'<br />','alert');
}

// Mise à jour des ranks et du calculation time
$before = microtime(true);
$updateRank = $alarmCompute->updateRank();
$after = microtime(true);
$total = round($after - $before,2);

// Affichage démon
if($updateRank !== false) displayInDemon(htmlentities('Mise à jour des ranks réalisé en ').$total.'s','list');
else displayInDemon(htmlentities('Impossible de mettre à jour les ranks').'<br />','alert');

// Vacuum
$before = microtime(true);
$vacuum = $alarmCompute->vacuum();
$after = microtime(true);
$total = round($after - $before,2);

// Affichage démon
if($vacuum !== false) displayInDemon(htmlentities('Vacuum des résultats réalisé en ').$total.'s','list');
else displayInDemon(htmlentities('Le vacuum des résultats a échoué').'<br />','alert');

// FIN PAGE
if(isset($_SERVER["HTTP_USER_AGENT"])) {
    echo '</body>';
    echo '</html>';
}
?>