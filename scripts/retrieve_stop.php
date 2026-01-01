<?php
/**
 * 15/11/2011 BBX
 * BZ 23222 : ajout de ce script pour gérer proprement la journalisation
 * de la fin du retrieve. Auparavant, celui-ci était réalisé à la fin de l'étape
 * "copy_from_temp" ce qui ne permettait pas de comptabiliser "specific_parser"
 */
include_once dirname(__FILE__) . '/../php/environnement_liens.php';
include_once REP_PHYSIQUE_NIVEAU_0 . 'php/edw_function.php';

// Fetching required information
$systemName = get_sys_global_parameters('system_name');
$message    = __T('A_COPY_FROM_TEMP_MSG_TRACELOG_END_RETRIEVE');
$module     = __T('A_TRACELOG_MODULE_LABEL_COLLECT');

// End message
sys_log_ast("Info", $systemName, $module, $message, "support_1", "");
?>