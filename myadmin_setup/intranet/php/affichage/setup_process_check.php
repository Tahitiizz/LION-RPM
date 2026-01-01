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
	Ce script recupere la liste des process en cours de la table sys_definition_master
	Il est appelé en tache de fond dans la page setup_process.php

	- creation 22/05/2006 sls
*/
session_start();
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";

// Récupération des valeurs get
$product = $_GET['product'];

// Connexion à la base du produit
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
$database = Database::getConnection($product);

// On va chercher la liste des process
$query = "SELECT master_id FROM sys_definition_master WHERE auto = TRUE";
$processes = $database->getAll($query);
if (count($processes) == 0) {
	// aucun process en cours
	echo "no running process found";
	exit();
}
// On liste les process en cours
$processArray = Array();
foreach($processes as $row) {
	$processArray[] = $row['master_id'];
}
echo implode(',',$processArray);
?>
