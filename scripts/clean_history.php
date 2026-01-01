<?php
/**
 *	@cb51400@
 *	18-03-2011 - Copyright Astellia
 *      Evolution T&A : Partionning
 *
 */
include_once(dirname(__FILE__) . "/../php/environnement_liens.php");
include_once REP_PHYSIQUE_NIVEAU_0 . 'class/Partition.class.php';
include_once REP_PHYSIQUE_NIVEAU_0 . 'class/Partitionning.class.php';
include_once REP_PHYSIQUE_NIVEAU_0 . 'class/deploy.class.php';
include_once REP_PHYSIQUE_NIVEAU_0 . 'php/edw_function_family.php';

// Fetching configuration
$database = Database::getConnection();
$offset_day = get_sys_global_parameters('offset_day');

/**
 * PARTITIONING ENABLED
 */
if($database->isPartitioned())
{
    // For all families
    displayInDemon('Clean Data History','title');
    foreach(FamilyModel::getAllFamilies() as $family => $familyInfos)
    {
        displayInDemon("Famille $family");
        // For all TA
        foreach(TaModel::getAllTa($familyInfos['id']) as $timeAggregation)
        {
            $history = get_history($family, $timeAggregation);
            if(empty($history)) $history = (int)get_sys_global_parameters('history_'.str_replace('_bh','',$timeAggregation));

            $lastDate = Partitionning::getLastDate($timeAggregation, $familyInfos['edwGroupTable']);

            // Pour hour, on récupère la dernière heure puis conversion timestamp - 24* Nombre de jours
            if($timeAggregation == 'hour') {
                $history *= 24;
            }

            $limitDate = strtotime("-{$history} ".str_replace('_bh','',$timeAggregation),$lastDate);
            switch($timeAggregation)
            {
                case 'hour':
                    $date = date('YmdH',$limitDate);
                break;
                case 'day':
                case 'day_bh':
                    $date = date('Ymd',$limitDate);
                break;
                case 'week':
                case 'week_bh':
					// 11/03/2013 GFS - BZ#31364 - [SUP][TA HPG][AVP 32586][Telus][Partionning]: All weekly history is delete on last week of the year compute
                    $date = date('oW',$limitDate);
                break;
                case 'month':
                case 'month_bh':
                    $date = date('Ym',$limitDate);
                break;
            }
            // Cleaning partitions
            $nbTables = 0;
            displayInDemon("Clean Data - ".$timeAggregation." <= $date :");
            foreach(Partitionning::getPartitionsFromDate($timeAggregation, $date, "<=", $familyInfos['edwGroupTable']) as $partition)
            {
                if($partition->exists()) {
                    $nbTables++;
                    $partition->drop();
                }
            }
            displayInDemon("$nbTables tables dropped");
        }
    }
}
/**
 * PARTITIONING DISABLED
 */
else
{
    displayInDemon('Nettoyage de historique des données','title');

    echo "<div style='font : normal 7pt Verdana, Arial, sans-serif;color : #585858;'>";

    // maj 26/02/2008 christophe : ajout d'un boucle sur les id_group_table.
    $q_liste_gt = "
            SELECT
                    t1.edw_group_table,
                    t2.family,
                    t2.rank
            FROM
                    sys_definition_group_table t1,
                    sys_definition_categorie t2
            WHERE
                    t1.id_ligne = t2.rank
                    AND t2.on_off = '1'
            ";
    $resultat = pg_query($database_connection, $q_liste_gt);
    while( $row = pg_fetch_array($resultat) )
    {
            displayInDemon('<u>Nettoyage de l\'historique de la famille <b>'.$row['family'].'</b></u> :');
            // 19/05/2011 BBX - PARTITIONING -
            // On peut désormais passer une instance de connexion
            $deploy = new deploy($database,$row['rank']);
            $deploy->clean_tables();
    }

    echo "</div>";
}
/**
 * CLEANING SA
 */
displayInDemon("Clean Source Availability Data","title");
$deploy = new deploy($database, 1);
$deploy->cleanTableSA();
?>

