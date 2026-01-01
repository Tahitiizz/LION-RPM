<?php

include_once dirname(__FILE__)."/../../php/environnement_liens.php";
include_once(REP_PHYSIQUE_NIVEAU_0."class/DataBaseConnection.class.php");

// 20/08/2012 BBX
// BZ 28452 : correction du dossier de topology auto sur le MK
if(MixedKpiModel::isMixedKpi(ProductModel::getProductId()))
{
    $database = DataBase::getConnection();
    $query = "UPDATE sys_global_parameters 
        SET value = '".REP_PHYSIQUE_NIVEAU_0."topology/'
        WHERE parameters = 'topology_file_location'
        AND value != '".REP_PHYSIQUE_NIVEAU_0."topology/'";
    $database->execute($query);   
}
?>
