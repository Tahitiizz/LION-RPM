<?php
/**
 * @version CB 5.1.4.00
 * @author  BBX
 * This script will be called before compute on a partitioned database.
 * It will create every needed partition for calculation.
 */
include_once(dirname(__FILE__) . "/../php/environnement_liens.php");
include_once REP_PHYSIQUE_NIVEAU_0 . 'class/Partition.class.php';
include_once REP_PHYSIQUE_NIVEAU_0 . 'class/Partitionning.class.php';

/**
 * Fonction qui éxécute la création / nettoyage d'une partition
 * @param string $timeAggregation
 * @param string $dateToCalculate
 */
function managePartitions($timeAggregation,$dateToCalculate)
{
    displayInDemon("Treatment of $timeAggregation : $dateToCalculate");
    $nbTablesTruncated  = 0;
    $nbTablesCreated    = 0;
    $nbTotalTables      = 0;
    foreach(Partitionning::getExpectedPartitionsFromDate($timeAggregation, $dateToCalculate) as $partition)
    {
        if( $partition->exists() )
        {
            $partition->truncate();
            $nbTablesTruncated++;
        }
        else
        {
            $partition->create();
            $nbTablesCreated++;
        }
        $nbTotalTables++;
    }
    displayInDemon("Number of created partitions : $nbTablesCreated / $nbTotalTables");
    displayInDemon("Number of truncated partitions : $nbTablesTruncated / $nbTotalTables");
}

// Fetching dates
// 23/11/2012 BBX
// BZ 30587 : on se base désormais sur la date indiquée en base
$dayToCompute       = get_sys_global_parameters('day_to_compute');
$offset_day         = Date::getOffsetDayFromDay($dayToCompute);
$timeToCalculate    = get_time_to_calculate($offset_day);

// Partitionning
foreach($timeToCalculate as $timeAggregation => $dateToCalculate)
{
    // If list of dates is provided
    if(is_array($dateToCalculate))
    {
        foreach($dateToCalculate as $date) {
            managePartitions($timeAggregation,$date);
        }
    }
    // If one date is provided
    else {
        managePartitions($timeAggregation,$dateToCalculate);
    }
}
?>
