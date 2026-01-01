<?php
/*
	04/08/2009 GHX
		- Correction du BZ 10711 [REC][Task Scheduler / Process] : pas de message "PROCESS : End Retrieve" si retrieve sans données
*/
?>
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
*	@cb1300_iu2000b_pour_cb2000b@
*
*	19/07/2006 - Copyright Acurio
*
*	Composant de base version cb_1.3.0.0
*
*	Parser version iu_2.0.0.0
*/
?>
<?php
/*
* 26-02-2006 : ajout de l'include edw_function_family.php afin de pouvoir appeler dans le copy_from_temp.class.php une fonction de ce fichier
*
*/
include_once($repertoire_physique_niveau0 . "php/database_connection.php");
include_once($repertoire_physique_niveau0 . "php/edw_function.php");
include_once($repertoire_physique_niveau0 . "php/edw_function_family.php");
include_once($repertoire_physique_niveau0 . "php/postgres_functions.php");
include_once($repertoire_physique_niveau0 . "php/deploy_and_compute_functions.php");
include_once($repertoire_physique_niveau0 . "class/copy_from_temp.class.php");

$query="SELECT * FROM sys_to_compute WHERE newtime=1";
$res=pg_query($database_connection,$query);
if (pg_num_rows($res) > 0) {
    $copy_from_temp = new copy_from_temp();
} else {
    // 15/11/2011 BBX
    // BZ 23222 : suppression du message de fin de retrieve
    print "No new hour/day to be managed<br>";
}

?>
