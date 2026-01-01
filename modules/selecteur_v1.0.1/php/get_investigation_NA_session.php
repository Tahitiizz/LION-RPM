<?php
/*
	05/01/2010 GHX
		- Correction du BZ 13343 [REC][CB 5.0][NE Favoris] : après avoir chargé les NE favoris, ils ne sont pas toujours cochés
*/
?>
<?php
/**
* Ce script permet de retourne la liste des éléments réseaux sélectionnées
* Ajouté le 02/12/2009 dans le cadre de la correction du bug 11482
*
* @author BBX
* @created 02/12/2009
* @version CB 5.0.2.1
* @since CB 5.0.2.1
*/

session_start();
include_once dirname(__FILE__).'/../../../php/environnement_liens.php';
include_once REP_PHYSIQUE_NIVEAU_0.'php/edw_function_family.php';


// Récupération du produit
$product = $_GET['product'];
// Récupération de la famille
$family = $_GET['family'];

// Récupération des niveaux d'agrégation 1er axe de la famille
$agregationLevelsInFamily = NaModel::getNaFromFamily($family,$product);

// Instanciation de notre USER
$user = new UserModel($_SESSION['id_user']);
// Récupération des favoris
$infosUser = $user->getValues();
$favorites = $infosUser['network_element_preferences'];

// Chaque élément est séparé du suivant par |s|
$networkSelection = explode('|s|',$favorites);

// Sélection retournée
$returnSelection = Array();

// On vérifie que le NE sélectionné existe sur notre produit
foreach($networkSelection as $oneNE)
{
	// Récupération du type et du code du NE
	list($na,$neCode) = explode('||',$oneNE);
	// Si le niveau d'agrégation existe sur la famille
	if(in_array($na,$agregationLevelsInFamily))
	{
		// Si le NE existe sur le produit
		if(NeModel::exists($neCode, $na, $product)) 
		{
			$neLabel = NeModel::getLabel($neCode, $na, $product);
			// 09:14 05/01/2010 GHX
			// Correction du BZ 13343 [REC][CB 5.0][NE Favoris] : après avoir chargé les NE favoris, ils ne sont pas toujours cochés
			$neLabel = ($neLabel == '') ? '('.$neCode.')' : $neLabel;
			$returnSelection[] = $na.'@'.$neCode.'@'.$neLabel;
		}
	}
}

// Retour des NE compatibles
// 08/12/2009 : ajout d'un utf8_encode
echo utf8_encode(implode('|s|', $returnSelection));
?>