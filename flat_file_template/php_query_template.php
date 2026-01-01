<?
set_time_limit(36000);
include dirname( __FILE__ ).'/../php/environnement_liens.php';
$sql = "$query_to_be_executed";
$result = pg_query($database_connection, $sql);

$nombre_resultat = pg_num_rows($result);
$nombre_fields = pg_num_fields($result);
for ( $i = 0; $i < $nombre_resultat; $i++) {
    $row = pg_fetch_array($result, $i);
    echo $row[0];
    for ( $j =1 ; $j < $nombre_fields; $j++ ) {
        echo ";".$row[$j];
    }
    echo "\n";
}
?>
