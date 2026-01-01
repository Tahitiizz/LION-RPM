<?php
/*
 * gestion de disponibilité des modules du CB
 * 
 */

include_once dirname(__FILE__)."/../../php/environnement_liens.php";
include_once(REP_PHYSIQUE_NIVEAU_0."class/DataBaseConnection.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0."class/CbCompatibility.class.php");

$db = Database::getConnection();

// Création de la table de gestion de disponibilité des modules
// uniquement si elle n'existe pas déjà
if(!$db->doesTableExist('sys_definition_module_availability')){
    $req = "CREATE TABLE sys_definition_module_availability
(
  sdma_code text NOT NULL primary key,
  sdma_label text,
  sdma_on_off integer,
  sdma_comment text
)
WITH (
  OIDS=FALSE
);";
    $res = $db->execute( $req );
    if($res){
        // CREATE INDEX id_sys_definition_module_availability1 ON sys_definition_module_availability USING btree (sdma_code );
        $res = $db->execute("COMMENT ON TABLE sys_definition_module_availability IS 'Mémorise la présence d''un module pour le CB courant.';");
        echo "Table 'sys_definition_module_availability' created.\n";
    }
    else
        echo "ERROR while creating table 'sys_definition_module_availability'.\n '{$res}' returned by query: '{$req}'\n";
}
//else
//    echo "Table 'sys_definition_module_availability' already exists.\n";


// Déclaration des modules supportés par le CB

// Module de Compatibilité Master 5.1 / slave 5.0
CbCompatibility::addModule('master51_slave50_compat','Master 5.1 / Slave 5.0 compatibility','Compatibility between a Master with CB 5.1.5 and a slave with CB 5.0.5.10 or higher');
// 12/12/2011 NSE DE : new parameters in AA links contextual filters
CbCompatibility::addModule('code_case_AALinks','Code or label and case translation in AA Links','New parameters for Choice between code and label, and case translation (upper/lower) in contextual filters for AA Links');
// 06/12/2011 ACS DE HTTPS support
CbCompatibility::addModule("https_support", "HTTPS support", "HTTPS available in the T&A application");


?>
