<?php
/*
	28/07/2009 GHX
		- Modification du nom de la variable de session appelée
    06/06/2011 MMT
      - DE 3rd Axis choisit la variable de session concernée en fonction de l'axe

    27/07/2011 MMT Bz 22896 add 3rd axis preferences session variable
*/
?>
<?php
/**
* Ce script permet de retourne la liste des éléments réseaux sélectionnées correspondantes avec les niveaux actives dans la sélectionne
* des éléments réseaux et ce qu'il y en SESSION
*
* @author GHX
* @created 23/06/2009
* @version CB 4.1.0.0
* @since CB 4.1.0.0
*/

session_start();
include_once dirname(__FILE__)."/../../../php/environnement_liens.php";
require_once(REP_PHYSIQUE_NIVEAU_0."php/edw_function_family.php");
require_once(REP_PHYSIQUE_NIVEAU_0.'/models/DashboardModel.class.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'/models/GTMModel.class.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'/models/InvestigationModel.class.php');

$separator = $_GET['separator'];
$activeNa = $_GET['current_na'];
$idDashboard = $_GET['id_page'];
// 06/06/2011 MMT DE 3rd Axis add axe parameter
$axe = $_GET['axe'];

$dashModel = new DashboardModel($idDashboard);
$na2na = $dashModel->getNa2Na($axe);

// 06/06/2011 MMT DE 3rd Axis choisit la variable de session concernée en fonction de l'axe
if($axe == 3){
	// 27/07/2011 MMT Bz 22896 add 3rd axis preferences session variable
	$sessionVar = $_SESSION['TA']['ne_axeN_preferences'];
} else {
	// pour axe 1 on prend dans les preferences
	$sessionVar = $_SESSION['TA']['network_element_preferences'];
}

// Récupère la liste de tous les éléments sélectionnées
$currentSelectionTmp = explode($separator, $sessionVar);
$currentSelection = array();

foreach ( $currentSelectionTmp as $select )
{
	$_ = explode('||', $select);
	// Si le niveau d'agrégation fait parti de la liste des éléments réseaux du dashboard
	if ( array_key_exists($_[0], $na2na) )
	{
		if ( in_array($_[0], $na2na[$activeNa]) )
		{
			// Garde uniquement les éléments dont le niveau d'agrégation est visible dans la sélection des éléments réseaux
			$currentSelection[] = $select;
		}
	}
}

// 06/06/2011 MMT DE 3rd Axis choisit la variable de session concernée en fonction de l'axe
if($axe == 3){
	$_SESSION['TA']['selecteur']['ne_axeN'] = implode($separator, $currentSelection);
} else {
	$_SESSION['TA']['selecteur']['ne_axe1'] = implode($separator, $currentSelection);;
}

echo implode($separator, $currentSelection);
?>