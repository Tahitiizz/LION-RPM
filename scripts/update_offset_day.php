<?
/*
*	@cb50000@
*
*	06/08/2009 - Copyright Astellia
*
*	Mise à jour de l'offset Day
*
*	Attention !!!!!!! Chaque éxécution de ce script va incrémenter l'offset day de 1
*
*/
?>
<?php
// Librairies et classes requises
include_once dirname(__FILE__)."/../php/environnement_liens.php";

// Connexion à la base de données
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
$database = Database::getConnection();

// Récupération de l'offset day
$query = "SELECT value FROM sys_global_parameters
WHERE parameters = 'offset_day'";
$offsetDay = $database->getOne($query);

// Calcul de la nouvelle valeur
$offsetDay = ($offsetDay == '') ? 1 : $offsetDay+1;

// Requête de mise à jour
$query = "UPDATE sys_global_parameters
SET value = '{$offsetDay}'
WHERE parameters = 'offset_day'";
$database->execute($query);
?>