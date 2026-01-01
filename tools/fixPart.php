<?php
// Scripts nécessaires
include_once dirname(__FILE__).'/../php/environnement_liens.php';
include_once REP_PHYSIQUE_NIVEAU_0.'class/Partition.class.php';
include_once REP_PHYSIQUE_NIVEAU_0.'class/Partitionning.class.php';

if (count($argv) !== 2 || !in_array($argv[1], array("day", "week", "month"))) {
        echo "Usage: php fixPart.php <time_aggregation>\n";
        echo "<time_aggregation> can be day, week or month\n";
        exit;

}

// Connection à la ase
$db = Database::getConnection();

// Récupération des tables mères avec données
$query = "SELECT relname 
FROM pg_class 
WHERE relname LIKE 'edw_%_{$argv[1]}'
AND reltuples > 0";
$result = $db->execute($query);

// Boucle sur les tables maman pas bonnes
while ($row = $db->getQueryResults($result, 1)) {	
	$table = $row['relname'];
	echo "Table $table contains data \n";
	
	// Table infos
	$tableInfos = explode('_', $table );
	$groupTable = $tableInfos[0].'_'.$tableInfos[1].'_'.$tableInfos[2];
	$dataType = $tableInfos[4];
	$na = $tableInfos[5];
	$na3 = $tableInfos[6];
	$ta = isset($tableInfos[7]) ? $tableInfos[7] : '';
	
	if( isset( $tableInfos[8] ) && ( $tableInfos[8] == 'bh' ) )
		$ta .= '_bh';
	
	$timeAgregations = array("hour","day","day_bh","week","week_bh","month","month_bh");
	if( in_array( $tableInfos[6],$timeAgregations ) )
	{
		$ta = $tableInfos[6];
		if( isset( $tableInfos[7] ) && ( $tableInfos[7] == 'bh') )
			$ta .= '_bh';
		$na3 = '';
	}
	
	// Récupération des dates de la table
	$query = "SELECT DISTINCT $ta FROM ONLY $table";
	$res = $db->execute($query);
	while ($row = $db->getQueryResults($res, 1)) {
		$date = $row[$ta];

		// Création des partitions
		$partition = new Partition($table, $date, $db);
		if( !$partition->exists() ) {
			$partition->create();
		}
		// Copie des données
		echo "Copying data from $table to ".$partition->getName()." for $ta $date \n";
		// 01/09/2014 GFS - Bug 43615 - [SUP][T&A CB][#48168][SFR]:[Partitionning]: fixPart.php script does not work on Postgresql 9.1.X
		//$q = "SELECT * INTO ".$partition->getName()." FROM ONLY $table WHERE $ta = $date";
		$q = "INSERT INTO ".$partition->getName()." SELECT * FROM ONLY $table WHERE $ta = $date";
		$db->execute($q);
		$q = "VACUUM ANALYZE ".$partition->getName();
		$db->execute($q);
	}
	
	// Suppression des triggers
	$q = "SELECT tgname FROM pg_trigger t, pg_class c
	WHERE t.tgrelid = c.oid
	AND c.relname = '$table'";
	$res = $db->execute($q);
	$createLock = true;
	while ($row = $db->getQueryResults($res, 1)) {
		if (!empty($row['trig'])) {
			echo "Dropping trigger ".$row['trig']." on table $table \n";
			$q2 = "DROP TRIGGER IF EXISTS ".$row['trig']." ON $table";
			$db->execute($q2);
		}
	}
	
	// Correction de la table mère
	$q = "TRUNCATE TABLE ONLY $table";
	$db->execute($q);
	//$q = "VACUUM FULL ANALYZE $table";
	//$db->execute($q);
	
	// Création du trigger
	echo "Creating trigger ${table}_trig_lock on table $table \n";
	$q2 = "CREATE TRIGGER ${table}_trig_lock
	BEFORE INSERT OR UPDATE OR DELETE
	ON $table
	FOR EACH ROW
	EXECUTE PROCEDURE lock_data_tables()";
	$db->execute($q2);
}
?>
