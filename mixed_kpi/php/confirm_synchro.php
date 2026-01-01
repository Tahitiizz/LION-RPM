<?
/*
 * @author mpr
 * Fichier qui synchronise les compteurs Mixed KPI *
 */
include_once( dirname(__FILE__)."../../../php/environnement_liens.php" );
include_once(REP_PHYSIQUE_NIVEAU_0."mixed_kpi/class/SynchronizeCounters.class.php");

$family = $_POST['idFamily'];

$synchro = new SynchronizeCounters($family);
echo $synchro->confirmSynchro();
?>
