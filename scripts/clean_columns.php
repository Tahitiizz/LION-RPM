<?php
/**
 * @file clean_columns.php
 * @brief Netoyage des tables de données ayant un nombre trop important de
 * colonnes supprimées. Script créé dans le cadre du bug 20811.
 * Le script peut être lancé en mode forcé en ajoutant l'option 'force'
 *
 * $Author: b.berteaux $
 * $Date: 2011-06-27 17:13:56 +0200 (lun., 27 juin 2011) $
 * $Rev: 28011 $
 */

set_time_limit( 0 ); // Le nétoyage peut être très long, on désactive la limite PHP
include_once( dirname( __FILE__ ) . "/../php/environnement_liens.php" );
include_once( REP_PHYSIQUE_NIVEAU_0 . "php/database_connection.php" );
include_once( REP_PHYSIQUE_NIVEAU_0 . "php/edw_function.php" );
include_once( REP_PHYSIQUE_NIVEAU_0 . "class/Partition.class.php" );
include_once( REP_PHYSIQUE_NIVEAU_0 . "class/Partitionning.class.php" );

// Définition des constantes du script
define( 'PG_LIMIT',      1600 );
define( 'MIN_DEL_COL',   100  ); // Nombre minimum de colonnes à supprimer

/**
 * Fonction nettoyant les tables d'une famille en recréant completement celle-ci.
 * Cette opération supprime les colonnes supprimées encore en mémoire.
 *
 * @param FamilyModel $familyModel Instance de FamilyModel
 * @param Database    $database  Instance de DatabaseConnection
 * @param string      $dataType Type des données ('raw' ou 'kpi')
 * @param bollean     $force Force ou non le netoyage
 */
function cleanColumns( FamilyModel $familyModel, DatabaseConnection $database, $dataType, $force )
{
    $maxDropCol = $familyModel->getMaxNumberOfDroppedColumns( $dataType );
    $maxCol     = $familyModel->getMaxNumberOfColumns( $dataType );
    displayInDemon( "{$maxCol} columns (including {$maxDropCol} dropped) for {$dataType} tables" , 'normal' );

    // Si il reste moins de 100 colonnes de disponibles et qu'il y a au moins MIN_DEL_COL à supprimer
    // ou si le mode forcé est activer
    if( ( $maxCol >= ( PG_LIMIT - 100 ) && $maxDropCol >= MIN_DEL_COL ) || ( $force && $maxDropCol > 0 ) )
    {
        // 27/06/2011 BBX
        // Excluding partitions for this treatment
        // BZ 22721
        foreach( $familyModel->getRelatedTables( $dataType, true ) as $dataTable )
        {
            displayInDemon( "Cleaning {$dataTable}...", 'normal' ); // Logging table

            // 27/06/2011 BBX
            // Si le partitioning est activé, on reconstruit également les partitions
            // 22721
            $startTime = microtime( true );
            if($database->isPartitioned()) {
                // Cleaning current table by rebuilding it and its partitions.
                $rebuild = $database->rebuildTableWithPartitions( $dataTable );
            }
            else {
                // Cleaning current table by rebuilding it.
                $rebuild = $database->rebuildTable( $dataTable );
            }

            // Checking result of execution
            if( !$rebuild )
            {
                // Cleaning failed, log message in daemon.
                sys_log_ast('Critical', 'Trending&Aggregation', 'Clean Columns', __T( 'A_CLEANING_COLUMNS_FAILED', $dataTable ), 'support_1' );
                displayInDemon( __T( 'A_CLEANING_COLUMNS_FAILED',$dataTable), 'alert' );
            }
            else
            {
                sys_log_ast('Warning', 'Trending&Aggregation', 'Clean Columns', __T( 'A_CLEANING_COLUMNS_TABLE_INFO', $dataTable ), 'support_1' );
                $cleanDur = round( ( microtime( true ) - $startTime ), 2 );
                displayInDemon( __T( 'A_CLEANING_COLUMNS_TABLE_INFO', $dataTable )." (dur:{$cleanDur}s)" );
            }
        }
    }
}

// Test si le lancement est en mode forcé (mode CLI uniquement)
$forceLaunch = false;
if( ( count( $argv ) == 2 ) && ( $argv[1] === 'force' ) )
{
    echo "Starting clean columns script with forced mode. Confirm (y/N) : ";
    if( ( $stdin  = fopen( "php://stdin", "r" ) ) !== false )
    {
        if( strtolower( trim( fgets( $stdin ) ) ) == 'y' )
        {
            echo "\n-Forced launch confirm....\n";
            $forceLaunch = true;
        }
        else
        {
            echo "\n-Forced launch abort, starting standard procedure...\n";
        }
        sleep( 1 ); // Attente pour visualisation du message
    }
}
   
// Vérification pour chacune des familles existantes
$database = Database::getConnection();
foreach( FamilyModel::getAllFamilies() as $family => $values )
{
    displayInDemon( "<br />- Checking {$family} ({$values['label']}) family..." ); // Log d'informations dans le démon
    $familyModel = new FamilyModel( $family ); // Récupération du modèle pour la famille
    cleanColumns( $familyModel, $database, 'raw', $forceLaunch ); // Nétoyage des tables RAWs
    cleanColumns( $familyModel, $database, 'kpi', $forceLaunch ); // Nétoyage des tables KPIs
}

