<?
/* 
 * @author mpr
 * Fichier qui va identifié le nombre de compteurs à mettre à jour* 
 */
include_once( dirname(__FILE__)."../../../php/environnement_liens.php" );
include_once(REP_PHYSIQUE_NIVEAU_0."mixed_kpi/class/SynchronizeCounters.class.php");

$family = $_POST['idFamily'];

$synchro = new SynchronizeCounters( $family );
echo $synchro->prepareSynchro();

?>