<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*/
?>
<?

/*

encode et verifie une clé en utilisant MD5 --> pas bien.

2006-02-14	Stephane	Creation

*/


	session_start();
	include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
	include_once($repertoire_physique_niveau0 . "php/database_connection.php");
	include_once($repertoire_physique_niveau0 . "php/environnement_donnees.php");
	include_once($repertoire_physique_niveau0 . "php/environnement_nom_tables.php");

	global $database_connection, $niveau0;


function getmicrotime() {
 $mtime = microtime();
 $mtime = explode(" ",$mtime);
 $mtime = $mtime[1] + $mtime[0];
 return ($mtime);
 }

// debut du chrono
$chrono = getmicrotime();



/*

Object : key encode et key decode

2006-02-14	Stephane	Creation

*/

$nb_max	= 15;
$nb_jours_max = 186; // 186 jours = 6 mois




function key_encode($nb,$date,$na) {
	//	$nb		: nb d'elements autorisés
	//	$date	: date de validité de la clé  (exemple : 20060214)
	//	$na		: network agregation
	return md5($nb.'@'.$date.'@'.$na);
}

function key_decode($key) {
	// $key		: clé à décoder

	global $nb_max,$nb_jours_max,$database_connection;

	// on construit le tableau de tous les NA possibles
	$query = "
		SELECT DISTINCT agregation
		FROM sys_definition_network_agregation
		WHERE on_off = 1
		limit 1
	";
	$na_array	= array();
	$result		= pg_query($database_connection, $query);
	$nb_na		= pg_num_rows($result);
	if ($nb_na) {
		for ($i = 0; $i < $nb_na; $i++) {
			$row 		= pg_fetch_array($result,$i);
			$na_array[$row['agregation']]	= $row['agregation'];
		}
	}

	var_dump($na_array);
	// exit;


	$c = 0;

	for ($nb = 1; $nb <= $nb_max; $nb++) {
		for ($jour = 0; $jour < $nb_jours_max; $jour++) {
			$date = date('Ymd',mktime(1,0,0,date('m'),date('d')+$jour,date('Y')));
			foreach ($na_array as $na) {
				if (key_encode($nb,$date,$na) == $key) {
					echo "<h2>$c</h2>";
					return array($nb,$date,$na);
				} else {
					$c++;
				}
			}
		}
	}
	echo "<h2>BAD KEY  !!!<br />$c</h2>";
}





$nb		= 15;
$date	= 20060128;
$na		= 'msc';


echo "key_encode($nb,$date,'$na') --> ".key_encode($nb,$date,$na).'<hr />';
var_dump(key_decode(key_encode($nb,$date,$na)));



// fin du chrono
$chrono = getmicrotime() - $chrono;
// affichage du résultat avec 4 decimales
printf( "%.4f", $chrono);


?>
