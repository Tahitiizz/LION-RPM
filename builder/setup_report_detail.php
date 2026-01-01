<?php
/*
	15/07/2009 GHX
		- On n'appelle plus la même fonction pour fermer la fenetre d'édition du sélecteur
	05/08/2009 GHX
		- Correction du BZ 10816 [SUP][V3.0][9527][EMTEL]: probleme sur les sélecteurs de dashboards intégré dans un rapport
   06/06/2011 MMT
      - DE 3rd Axis correction bug 10816 appliquée au 3eme axe
   27/07/2011 MMT Bz 22896 add 3rd axis preferences session variable
*/
?>
<?php
/**
*	Page affichant un sélecteur à configurer pour les rapports
*	Refonte totale pour le cb41000
*
*
*	@author	BBX - 27/11/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*/
session_start();
include_once '../php/environnement_liens.php';


// Librairies et classes requises
require_once(REP_PHYSIQUE_NIVEAU_0.'php/edw_function_family.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'/models/DashboardModel.class.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'/models/SelecteurModel.class.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'/models/GTMModel.class.php');
require_once(MOD_SELECTEUR.'php/selecteur.class.php');
require_once(MOD_SELECTEUR.'php/SelecteurEdit.class.php');

$arborescence = 'Configure filter';
include_once(REP_PHYSIQUE_NIVEAU_0.'php/header.php');

// Récupération des informations
$id_dashboard	= $_GET['id_page'];
$id_report	= $_GET['report_id'];

// Recherche de l'id_selecteur
$id_selecteur = SelecteurModel::getSelecteurId($id_report,$id_dashboard);

// 17:24 05/08/2009 GHX
// Correction du BZ 10816
$selecteurModel = new  SelecteurModel($id_selecteur);
$_SESSION['TA']['network_element_preferences'] = $selecteurModel->getValue('nel_selecteur');
//06/06/2011 MMT DE 3rd Axis correction bug 10816 appliquée au 3eme axe
$_SESSION['TA']['selecteur']['ne_axeN'] = $selecteurModel->getValue('axe3_2');
// 27/07/2011 MMT Bz 22896 add 3rd axis preferences session variable
$_SESSION['TA']['ne_axeN_preferences'] = $_SESSION['TA']['selecteur']['ne_axeN'];
/*
if(!empty($selecteurModel->getValue('nel_selecteur')) && !empty($selecteurModel->getValue('axe3_2'))){
	$_SESSION['TA']['network_element_preferences'] = $selecteurModel->getValue('nel_selecteur')."|s|".$selecteurModel->getValue('axe3_2');
} else {
	$_SESSION['TA']['network_element_preferences'] = $selecteurModel->getValue('nel_selecteur').$selecteurModel->getValue('axe3_2');
}
 * */
 

// Appel du sélecteur
$selecteur = new SelecteurEdit($id_dashboard,$id_selecteur);
$selecteur->setFixedHourEnable( true ); // Le mode Fixed Hour est activé

// Si l'appel est correct, continue
if(!$selecteur->getError()) {
	// Enregistrement du sélecteur
	if( isset( $_POST['selecteur'] ) )
    {
        // Fixe Hour Report - On modifie la valeur du fh_mode pour que la valeur
        // puisse être directement insérée en base
        if( isset( $_POST['selecteur']['fh_mode' ] ) )
        {
            $_POST['selecteur']['fh_mode' ] = 't';
        }
        else
        {
            $_POST['selecteur']['fh_mode' ] = 'f';
        }
		$selecteur->getSelecteurFromArray($_POST['selecteur']);
		$selecteur->save();
		$selecteur->setAsReportFilter($id_report);
		// 11:52 15/07/2009 GHX
		// On n'appelle plus la même fonction pour fermer la fenetre d'édition
		$selecteur->closeFromReport();
	}	
	$selecteur->build();
}
?>