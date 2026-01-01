<?php
/**
 * 
 *  CB 5.2
 * 
 * Met à jour la liste des utilisateurs locaux par rapport aux PAA
 * 
 * 23/02/2012 NSE DE Astellia Portal Lot2
 */
?><?php
include_once dirname(__FILE__)."/../php/environnement_liens.php";
//include_once(REP_PHYSIQUE_NIVEAU_0 . '/api/paa/PAAAuthenticationService.php');
sys_log_ast("Info",get_sys_global_parameters( 'system_name' ), __T( 'A_TRACELOG_MODULE_LABEL_COMPUTE' ),"PAA user synchronization duration: ".round($y-$x, 6),'support_1','');

$x = microtime(true);//sleep(7);
$ret = UserModel::updateLocalUsersList();
if($ret == "no user on PAA" ){
    echo 'KO';
    sys_log_ast("Critical",get_sys_global_parameters( 'system_name' ), __T( 'A_TRACELOG_MODULE_LABEL_COMPUTE' ),"Error occured during users synchronisation with PAA",'support_1','');
}
else {
    echo 'OK';
}
$y = microtime(true);
displayInDemon('<b>Durée de la récupération des utilisateurs sur le Portail : '.round($y-$x, 6).' secondes.</b>');
sys_log_ast("Info",get_sys_global_parameters( 'system_name' ), __T( 'A_TRACELOG_MODULE_LABEL_COMPUTE' ),"PAA user synchronization duration: ".round($y-$x, 6),'support_1','');
?>
