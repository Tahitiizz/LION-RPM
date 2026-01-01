<?
/*
*	@cb41000@
*
*	09/12/2008 - Copyright Acurio
*
*	Composant de base version cb_4.1.0.00
*
*	09/12/2008 BBX : refonte du script pour le CB 4.1
*	=> Ce script est désormais appelé via Ajax
*	=> Déploiement d'un élement
*	=> Utilisation des nouvelles classes, constantes
*	=> Gestion du produit
*
*	31/03/2009 BBX : ajout d'un contôle sur un process
 * 31/05/2011 NSE bz 22349 : suite à la factorisation du code pour la correction du bz, utilisation des modèles FamilyModel et NaModel
*/
?>
<?php
session_start();
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";

// Librairies et classes nécessaires
include_once(REP_PHYSIQUE_NIVEAU_0.'/php/deploy_and_compute_functions.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'/class/deploy.class.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'/php/traitement_chaines_de_caracteres.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'/php/edw_function_family.php');

// Récupération des données transmises via l'URL.
$family = $_POST["family"];
$product = $_POST["product"];
$na = $_POST["na"];

// Connexion à la base de données du produit
$database = Database::getConnection($product);

// Récupération des infos de la famille
// 31/05/2011 NSE bz 22349 : Utilisation du modèle
$familyModel = new FamilyModel($family, $product);
$familyInfos = $familyModel->getValues();

// Récupération des infos du nouveau NA
// 31/05/2011 NSE bz 22349 : Utilisation du modèle
// 09/09/2011 BBX BZ 23641 : ajout de l'id produit
$NaInfos = NaModel::getNaInfo($family,$na,$product);

// Ajout 31/03/2009 BBX
// On regarde si un process est en cours
$queryProcess = "SELECT * FROM sys_process_encours WHERE encours = 1";
if(count($database->getAll($queryProcess)) > 0) {
    echo 'PROCESS';
    exit;
}

if((count($familyInfos) != 0) && (count($NaInfos) != 0))
{
    // 31/05/2011 NSE bz 22349 : utilisation des méthodes créées
    $execCtrl = NaModel::createAgregationPath($family,$familyInfos,$NaInfos['agregation'],$NaInfos['source_default'],$NaInfos['agregation_label'],$product);

    // S'il n'y a pas eu d'échecs dans les requêtes, on peut lancer le déploiement
    if($execCtrl)
    {
        $execCtrl = NaModel::deployAgregationPath($family,$familyInfos,$product);
        if($execCtrl)
            echo 'OK';
        else {
            echo "Error occured during Network Agregation deployement";
        }
    }
    else {
        echo "Error occured during Network Agregation creation";
    }
}
// 09/09/2011 BBX BZ 23641 : ajout d'un else
else {
    echo "Error occured during Network Agregation deployement";
}
?>