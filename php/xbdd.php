<?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
* 
* 	- 28/12/2007 Gwénaël : restructuration du fichier dans l'optique d'une meilleure lisibilité et maintenance
* 
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
<?php
// include dirname( __FILE__ ).'/xbdd.inc';
// $AHost = "localhost";
// $Aport = "5432";
// $AUser = "postgres";
// $APass = "";
// $database_connection = pg_connect("port=$Aport dbname=$DBName user=$AUser password=$APass");

// include( dirname( __FILE__ )."/environnement_liens.php");
// maj 17/03/2010 - MPR : Suppression du pg_connect et appel à la classe static Database 
$globalInstanceOfDatabase = Database::getConnection(0);
$database_connection = $globalInstanceOfDatabase->getCnx();

?>