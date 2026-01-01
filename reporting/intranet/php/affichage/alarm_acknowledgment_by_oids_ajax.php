<?php

/**
 * Acknowledge alarms by oids
 * RQ 4889
 * @global string $_GET['product']
 * @global string[] $_GET['oids']
 */

session_start();
include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
include_once($repertoire_physique_niveau0 . "php/database_connection.php");
global $database_connection;

$cnx = Database::getConnection($_GET['product']);
$oids	= $_GET['oids'];

if (is_array($oids) && sizeof($oids) > 0 && $oids[0] != "") {
	$provider = new AlarmDbProvider($cnx);
	if (!$provider->updateAckByOids($oids)) {
		header("HTTP/1.0 500 Internal error");
	};
} else {
	header("HTTP/1.0 400 Bad request");
}

echo sizeof($oids) . " alarm(s) is(are) successfully acknownledged";

?>
