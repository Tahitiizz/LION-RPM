<?php
/**
 * This script prevents the corruption of table sys_definition_group_table_network
 * when using Corporate.
 * This script was created to fix bug BZ 22869
 */
include_once dirname(__FILE__)."/../../php/environnement_liens.php";

// Database Connection
$db = Database::getConnection();

// If Corporate
if(CorporateModel::isCorporate())
{
    // If using Postgresql > 8.2
    if($db->getVersion() > 8.2)
    {
        // sys_definition_group_table_network rebuilding
        NaModel::buildGroupTableNetwork();
    }
}
?>
