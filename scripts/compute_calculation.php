<?php
/**
 * 
 *  CB 5.3.1
 * 
 * 22/05/2013 : T&A Optimizations
 */
// Includes
include dirname( __FILE__ ).'/../php/environnement_liens.php';

// Tests préliminaires
if(!isset($env_queries)) exit;

// Database
$database = Database::getConnection();

// Tableau des résultats
$queryResults = array();

// Temps avant éxécution
$start_time = time();

// Récupération des requêtes dans le fichier et test (bz25900)
$queries = unserialize( file_get_contents( $env_queries ) );
if(!is_array($queries)) exit;

// Exécution des requêtes reçues
foreach($queries as $name => $query)
{
    // Debug 3 : les requêtes ne sont pas éxécutées
    if($env_debug == 3) {
        $queryResults[$name] = "No execution";
    }
    // La requête est éxécutée mais n'a pas fonctionnée
    elseif(!$database->execute($query)) {
        $queryResults[$name] = "<span style='color:#FF0000'>".$database->getLastError()."</span>";
    }
    // La requête s'est correctement éxécutée
    else {
        $queryResults[$name] = $database->getAffectedRows();
    }
}

// Mémorisation du temps d'éxécution
$queryResults['execution_time'] = time() - $start_time;

// Retour des résultats
$f = fopen($env_temp_file,'a+');
foreach( $queryResults as $k => $q_result ) {
    fwrite($f, $env_index.";".$k.";".$q_result."\n");
}
fclose($f);

// Suppression du fichier contenant les requêtes serialisées
unlink( $env_queries );
?>
