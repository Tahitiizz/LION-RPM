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
include_once(dirname(__FILE__)."/../php/environnement_liens.php");
include_once(REP_PHYSIQUE_NIVEAU_0."php/database_connection.php");
include_once(REP_PHYSIQUE_NIVEAU_0."class/compute.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0."class/compute_launcher.class.php");
$compute_hour=new compute_launcher("hour");
?>
