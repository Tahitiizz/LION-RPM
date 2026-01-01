<?php
include_once dirname(__FILE__)."/../../php/environnement_liens.php";
include_once(REP_PHYSIQUE_NIVEAU_0."class/DataBaseConnection.class.php");

$db = Database::getConnection();

// 08/07/2011 NSE bz 22673 : si la table sys_gis_config_palier est vide, on la remplie
if($db->getOne("SELECT COUNT(*) FROM sys_gis_config_palier")==0){
    $req = "COPY sys_gis_config_palier(id, nom, niveau, id_parent, scale, mainscale, activation_right, on_off)
        FROM '".REP_PHYSIQUE_NIVEAU_0."/SQL/5.0.p/sys_gis_config_palier.dump'
        WITH DELIMITER ';'";
    $res = $db->execute( $req );
    if($res)
        echo "sys_gis_config_palier filled\n";
    else
        echo "ERROR with sys_gis_config_palier: ".$req."\n";
}
?>
