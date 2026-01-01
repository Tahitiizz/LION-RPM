<?php

include_once dirname(__FILE__)."/../../php/environnement_liens.php";
include_once(REP_PHYSIQUE_NIVEAU_0."class/DataBaseConnection.class.php");

// 14/06/2011 OJT : Correction bz22545, gestion des doublons Selecteur/User Homepage
// Recherche des doublons dans la table sys_definition_selecteur
$db = Database::getConnection();
$duplicates = array();
$dupRes     = $db->execute( "SELECT sds_id_selecteur,COUNT(sds_id_selecteur) as nb FROM sys_definition_selecteur GROUP BY sds_id_selecteur;" );
while( $dup = $db->getQueryResults( $dupRes, 1 ) )
{
    if( intval( $dup["nb"] ) > 1 )
    {
        // Il y des doublons
        $duplicates []= $dup["sds_id_selecteur"];
    }
}

// Si il y des doublons de détectés
if( count( $duplicates ) > 0 )
{
    // Pour tous les doublons, on supprime les entrées correspondantes dans user
    // 14/12/2012 BBX
    // BZ 21721 : correction du cast
    $inClause = implode( "','", $duplicates );
    $db->execute( "UPDATE users SET homepage=null WHERE homepage IN('{$inClause}')" );

    // Mise à jour de la séquence PostgreSql avec la nouvelle valeur max
    $newMax = $db->getOne( "SELECT max(sds_id_selecteur)+1 FROM sys_definition_selecteur;" );
    $db->execute( "ALTER SEQUENCE sys_definition_selecteur_sds_id_selecteur_seq RESTART WITH {$newMax}" );

    // Mise à jour des doublons
    $db->execute( "UPDATE sys_definition_selecteur SET sds_id_selecteur=nextval('sys_definition_selecteur_sds_id_selecteur_seq') WHERE sds_id_selecteur IN({$inClause})" );
}

// Suppression des lignes inutilisées
// 07/09/2011 BBX
// Correction d'un cast
// BZ 23650
$db->execute( "DELETE FROM sys_definition_selecteur
                        WHERE sds_report_id IS NULL
                        AND sds_id_selecteur::text NOT IN
                        (SELECT homepage FROM users WHERE homepage IS NOT NULL)" );

// Mise à NULL des homepage ne pointant vers rien
// 07/09/2011 BBX
// Correction d'un cast
// BZ 23650
// 14/12/2012 BBX
// BZ 21721 : correction de la condition de suppression des homepages
$db->execute( "UPDATE users SET homepage=null 
                WHERE homepage::text NOT IN ('1','-1')
                AND homepage::text NOT IN (SELECT sds_id_selecteur::text FROM sys_definition_selecteur WHERE sds_report_id IS NULL)" );
