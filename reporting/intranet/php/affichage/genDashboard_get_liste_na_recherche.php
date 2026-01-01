<?php
/*
	Utillisé dans le moteur de recherche des éléments réseaux.
	
	17/09/2009 GHX
		- Modification de la requete SQL qui recherche les NE
 *  30/09/2010 NSE bz 18165 : ne pas afficher les cellules virtuelles.
 *	25/11/2011 ACS BZ 24792 Virtual SAI of corporate are visibles in multiple GUI
*/

session_start();

include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
include_once($repertoire_physique_niveau0 . "php/database_connection.php");
include_once($repertoire_physique_niveau0 . "php/edw_function.php");
include_once($repertoire_physique_niveau0 . "php/edw_function_family.php");

global $database_connection;


header('Content-Type: text/xml;charset=utf-8');
echo(utf8_encode("<?xml version='1.0' encoding='UTF-8' ?><options>"));

if (isset($_GET['debut'])) {
    $debut = utf8_decode($_GET['debut']);
} else {
    $debut = "";
}

$na = (isset($_GET['na'])) ? $_GET['na'] : null ;
$family = (isset($_GET['family'])) ? $_GET['family'] : null ;

$table_object_ref = get_object_ref_from_family($family); // cf edw_function_family
$na_min = get_network_aggregation_min_from_family($family);

$na_label = $na.'_label';
$debut_q = strtolower($debut).'%';

$MAX_RETURN = 10;

// 14:19 17/09/2009 GHX
// Modification de la requete SQL pour quelle fonctionne avec le nouveau model de topologie
// 30/09/2010 NSE bz 18165 : ne pas afficher les cellules virtuelles.
// 25/11/2011 ACS BZ 24792 Virtual SAI of corporate are visibles in multiple GUI
$q = "
	SELECT 
		eor_id AS value, 
		eor_label AS label
	FROM
		(
			SELECT
				eor_id,
				CASE WHEN eor_label IS NULL OR eor_label = ''
					THEN eor_id
					ELSE eor_label
				END AS eor_label
			FROM
				edw_object_ref
			WHERE
				eor_obj_type = '$na'
			ORDER BY eor_id, eor_label DESC
		) tmp
	WHERE
		lower(eor_label) LIKE '$debut_q'
        AND ".NeModel::whereClauseWithoutVirtual()."
	ORDER BY 
		label ASC
	LIMIT $MAX_RETURN
";
pg_query($database_connection, $q);
$result = pg_query($database_connection, $q);
$nombre_resultat = pg_num_rows($result);
if ($nombre_resultat > 0)
{
	for ($i = 0;$i < $nombre_resultat;$i++) {
        $row = pg_fetch_array($result, $i);
        $value = $row["value"];
        $label = $row["label"];
		echo(utf8_encode("<option>".$label.'|ss|'.$value."</option>"));
	}
}

echo("</options>");
?>