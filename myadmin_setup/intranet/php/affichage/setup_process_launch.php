<?
/*
*	@cb41000@
*
*	10/12/2008 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.00
*
*	10/12/2008 BBX : modifications pour le CB 4.1 :
*	=> Utilisation des nouvelles classes et constantes
*	=> Gestion du produit
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
	Ce script lance un process de la table sys_definition_master
	Il est appelé en appuyant sur un bouton manual_launch de setup_process.php

	- creation 22/05/2006 sls
*/
session_start();
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";

// Récupération des valeurs get
$masterId = $_GET['master_id'];
$product = $_GET['product'];

// Connexion à la base du produit
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
$database = Database::getConnection($product);

// Lancement du process
$query = "UPDATE sys_definition_master SET auto = TRUE WHERE master_id = {$masterId}";
$database->execute($query);

echo 'launched';
?>
