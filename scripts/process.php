<?php
/**
 * Launch and manage T&A processes
 * This script can be launched manually or periodically (cron, etc...)
 * 05/02/2013 BBX
 * DE Optims T&A
 */
session_start();
include dirname(__FILE__) . '/../php/environnement_liens.php';
include REP_PHYSIQUE_NIVEAU_0 . 'class/processes/Master.class.php';
include REP_PHYSIQUE_NIVEAU_0 . 'class/processes/Family.class.php';
include REP_PHYSIQUE_NIVEAU_0 . 'class/processes/Step.class.php';
include REP_PHYSIQUE_NIVEAU_0 . 'class/processes/MasterLauncher.class.php';
include REP_PHYSIQUE_NIVEAU_0 . 'class/processes/FamilyLauncher.class.php';
include REP_PHYSIQUE_NIVEAU_0 . 'class/processes/StepLauncher.class.php';

//Launch master
$master = new MasterLauncher($argv[1]);
$master->go();

//Is another Master to launch ? (for computes process)
if($argv[1] == 11 || $argv[1] == 12){
 
    $query = "SELECT master_id FROM sys_definition_master WHERE auto = 't' LIMIT 1";
    $result = Database::getConnection()->execute($query);
    while ( $row = Database::getConnection($idProduct)->getQueryResults($result, 1) )
    {
        Database::getConnection($idProduct)->execute("UPDATE sys_definition_master SET auto = false WHERE master_id = '{$row['master_id']}'");
        Database::getConnection($idProduct)->execute("INSERT INTO sys_process_encours (process,utps,date,encours,done) VALUES ('{$row['master_id']}', '".$row['utps']."', ".date('YmdHi',time()).", 1, 0);");
        $master = new MasterLauncher($row['master_id']);
        $master->go();
    }
}

?>
