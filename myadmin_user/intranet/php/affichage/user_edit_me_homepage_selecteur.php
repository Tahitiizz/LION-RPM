<?php
/**
*  19/02/2010 NSE bug : suppression de la correction précédente pour le bug 14386.
*  27/07/2011 MMT Bz 22896 add 3rd axis preferences session variable
*/
/**
*	Page affichant un sélecteur à configurer
*	Refonte totale pour le cb41000
*
*
*	@author	BBX - 26/11/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*/
session_start();
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";

// Librairies et classes requises
require_once(REP_PHYSIQUE_NIVEAU_0."php/edw_function_family.php");
require_once(REP_PHYSIQUE_NIVEAU_0.'/models/DashboardModel.class.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'/models/SelecteurModel.class.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'/models/GTMModel.class.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'/models/UserModel.class.php');
require_once(MOD_SELECTEUR."php/selecteur.class.php");
require_once(MOD_SELECTEUR."php/SelecteurEdit.class.php");

$arborescence = 'Configure filter';
include_once(REP_PHYSIQUE_NIVEAU_0.'php/header.php');

// Récupération des informations
$id_dashboard = isset($_GET['id_page']) ? $_GET['id_page'] : 0;
$id_selecteur = isset($_GET['id_selecteur']) ? $_GET['id_selecteur'] : 0;

// Si l'id sélecteur est indéfini, on regarde si le user a déjà définit sa homepage
$UserModel = new UserModel($_SESSION['id_user']);
$UserValues = $UserModel->getValues();
if($UserValues['homepage'] != '') {
	// On regarde si cette homepage correspond au dashboard demandé
	$SelecteurModel = new SelecteurModel($UserValues['homepage']);
	if(!$SelecteurModel->getError())
    {
        // 01/07/2011 OJT : Correction 22755, mise en session des valeurs 3ème axe
        $_SESSION['TA']['selecteur']['ne_axeN'] = $SelecteurModel->getValue('axe3_2');
		  // 27/07/2011 MMT Bz 22896 add 3rd axis preferences session variable
		  $_SESSION['TA']['ne_axeN_preferences'] = $_SESSION['TA']['selecteur']['ne_axeN'];
		$SelecteurValues = $SelecteurModel->getValues();
		$SelecteurValues['id_page'];
		if($SelecteurValues['id_page'] == $id_dashboard) {
			// Le dashboard correspond, on récupère donc l'id du sélecteur afin de retrouver le pramétrage
			$id_selecteur = $UserValues['homepage'];
		}
	}
}

// Appel du sélecteur
$selecteur = new SelecteurEdit($id_dashboard,$id_selecteur,'nelsel');

// Si l'appel est correct, on continue
if(!$selecteur->getError()) {
	// Enregistrement du sélecteur
	if(isset($_POST['selecteur'])) {
		$selecteur->getSelecteurFromArray($_POST['selecteur']);
		$selecteur->save();
		$selecteur->setAsUserHomepage($_SESSION['id_user']);
		$selecteur->close();
	}	
	$selecteur->build();
}
?>