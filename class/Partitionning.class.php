<?php
/**
 *	@cb51400@
 *      @author BBX / MPR
 *	18-03-2011 - Copyright Astellia
 *      Evolution T&A : Partionning
 *
 *      This static class will fetch Partitions
 */
class Partitionning
{
    // Not constructable
    private function __construct() {
        // Nothing
    }

    // Not desctructable
    private function  __destruct() {
        // Nothing
    }

    // Not clonable
    private function __clone() {
        // Nothing
    }

     /**
     * Returns an array of "Partition" objects matching parameters
     * @param string $timeAggregation
     * @param integer $date
     * @param string $operator
     * @param string $groupTable
     * @return SplObjectStorage de Partition
     */
    public static function getExpectedPartitionsFromDate( $timeAggregation, $date, $groupTable = 'edw' )
    {
        // Will store partitions
        $partitions = new SplObjectStorage();
        // Local database Connection
        $database = Database::getConnection();

        $query = "  SELECT
                        t.tablename as parent, t.tablename || '_{$date}' as partition
                    FROM
                        pg_tables t, pg_class c
                    WHERE t.tablename = c.relname
                        AND t.schemaname = 'public'
                        AND tablename like '{$groupTable}_%_{$timeAggregation}'
                        AND c.relfilenode NOT IN (SELECT inhrelid FROM pg_inherits)
                    ORDER BY parent, partition;";
        
        $result = $database->execute($query);
        // Adding partitions
        while( $row = $database->getQueryResults($result, 1) )
        {
            $partitions->attach(new Partition($row['parent'], $date, $database));
        }

        return $partitions;
    }
    /**
     * Returns an array of "Partition" objects matching parameters
     * @param string $timeAggregation
     * @param integer $date
     * @param string $operator
     * @param string $groupTable
     * @return SplObjectStorage de Partition
     */
    public static function getPartitionsFromDate($timeAggregation, $date, $operator = '=', $groupTable = 'edw')
    {
        // Will store partitions
        $partitions = new SplObjectStorage();
        // Local database Connection
        $database = Database::getConnection();
        // Query
        $query = "  SELECT
                        c1.relname AS parent,
                        c2.relname AS partition,
                        substring(c2.relname from '[0-9]+$') AS partdate
                    FROM
                        pg_inherits i, pg_class c1, pg_class c2
                    WHERE i.inhparent = c1.oid
                        AND i.inhrelid = c2.oid
                        AND c1.relname LIKE '{$groupTable}_%_{$timeAggregation}%'
                        AND c2.relname LIKE '{$groupTable}_%_{$timeAggregation}_%'
                        AND substring(c2.relname from '[0-9]+$') {$operator} '{$date}'
                    ORDER BY c1.relname, c2.relname";
        
        $result = $database->execute($query);
        // Adding partitions
        while($row = $database->getQueryResults($result, 1)) {
            $partitions->attach(new Partition($row['parent'], $row['partdate'], $database));
        }
        // Returns Partitions
        return $partitions;
    }

    /**
     * Returns all partitions of a data table
     * @param string $dataTable
     * @return SplObjectStorage de Partition
     */
    public static function getPartitionFromDataTable($dataTable)
    {
        // Will store partitions
        $partitions = new SplObjectStorage();
        // Local database Connection
        $database = Database::getConnection();
        // Query
        $query = "  SELECT
                        substring(c2.relname from '[0-9]+$') AS partdate
                    FROM
                        pg_inherits i, pg_class c1, pg_class c2
                    WHERE i.inhparent = c1.oid
                        AND i.inhrelid = c2.oid
                        AND c1.relname = '{$dataTable}'
                    ORDER BY c2.relname DESC";
        $result = $database->execute($query);
        // Adding partitions
        while($row = $database->getQueryResults($result, 1)) {
            $partitions->attach(new Partition($dataTable, $row['partdate'], $database));
        }
        // Returns Partitions
        return $partitions;
    }

    public static function getLastDate( $timeAggregation, $groupTable = 'edw'  )
    {
        $database = DataBase::getConnection();

        switch( $timeAggregation )
        {
            case 'hour':
                $query = "SELECT substring(c1.relname from '[0-9]+$')::bigint AS partdate
                    FROM pg_inherits i, pg_class c1
                    WHERE i.inhrelid = c1.oid
                        AND c1.relname LIKE '{$groupTable}_%_{$timeAggregation}%'
                    ORDER BY partdate DESC
                    LIMIT 1;";

                $lastDay = $database->getOne($query);
                $lastDay = ( !$lastDay  ) ? date("YmdH") : $lastDay;
                return strtotime( Date::convertAstelliaHourToUsHour( $lastDay) );
            break;
            default:
                $offset_day = get_sys_global_parameters('offset_day');
                return strtotime("-{$offset_day} day");
            break;
        }
        
        return ;
    }
}
?>
