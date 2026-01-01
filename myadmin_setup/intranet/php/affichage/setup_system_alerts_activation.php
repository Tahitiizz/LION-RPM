<?
/*
*	@cb41000@
*
*	09/12/2008 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.00
*
*	09/12/2008 BBX : modification du script pour le CB 4.1
*	=> Utilisation des nouvelles variables globales
*	=> Utilisation de la classe DatabaseConnection
*	=> Gestion du produit
*/
?>
<?php
session_start();
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";

// Récupération des variables
$product = $_GET['product'];
$alarm_systems_activation = $_GET['alarm_systems_activation'];

// Si toutes les valeurs nécessaires sont reçues
if (isset($_GET['alarm_systems_activation']) && isset($_GET['product'])) {	
	// Connexion à la base du produit
        // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
	$database = Database::getConnection($product);
	// Mise à jour du statut
	$sql = "UPDATE sys_global_parameters SET value='".$alarm_systems_activation."' 
	WHERE parameters = 'alarm_systems_activation'";
	$database->execute($sql);
}
?>
	