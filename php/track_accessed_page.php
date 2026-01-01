<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*/
?>
<?php

include_once($repertoire_physique_niveau0."php/environnement_liens.php");
include_once($repertoire_physique_niveau0."php/database_connection.php");
include_once($repertoire_physique_niveau0."php/edw_function.php");

$page_accedee = $PHP_SELF;
$identifiant_user = $id_user;
$access_time = date("r");
$access_day = getday(0);
if ($page_accedee != "" and $identifiant_user != "") {
    $query = "INSERT INTO track_pages (page,id_user,access_time, access_day) VALUES ('$page_accedee','$id_user','$access_time','$access_day')";
    pg_query($database_connection, $query);
}

?>
