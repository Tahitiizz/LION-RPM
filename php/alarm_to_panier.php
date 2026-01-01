<?php
/*
	17/07/2009 GHX
		- Correction du BZ 9547 [REC][T&A CB 5.0][caddy]: pas d'export des alarmes
			-> Utilisation de DatabaseConnection()
			-> Problème de fichier inclu
	22/09/2009 GHX
		- Correction du BZ 11272 [REC][T&A CB 5.0][INVESTIGATION DASHBOARD]: caractères accentués sous forme de carrés
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

session_start();

// on force l'encodage des caractères en ISO (caractères spéciaux)
header('Content-Type: text/html; charset=ISO-8859-1');

include_once dirname(__FILE__)."/environnement_liens.php";

$id_user = $_GET['id_user'];
// 16:40 22/09/2009 GHX
// Correction du BZ 11272
// Ajout du utf8_decode
$monTableauHtml = utf8_decode($_SESSION['alarm_to_panier']);
$monTitre = utf8_decode(urldecode($_GET['titre']));
$from = $_GET['from'];

// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::get Connection()
$db = Database::getConnection();

$query = "INSERT INTO sys_panier_mgt
		(id_user,object_type,object_id,object_title,object_page_from)
		VALUES ('$id_user','alarm_export','$monTableauHtml','$monTitre','$from');";
$db->execute($query);
?>
