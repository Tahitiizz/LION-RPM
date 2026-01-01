<?php

/**
 * Format une taille d'une fichier pass� en octect en format lisible (ex : 452120  = 1040
 *
 * @param int $size
 * @return string
 */
function formatSize ( $size, $precision = 1 )
{
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
  
    $size = max($size, 0);
    $pow = floor(($size ? log($size) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
  
    $size /= pow(1024, $pow);
  
    return round($size, $precision) . ' ' . $units[$pow]; 
}
/**
 * Fonction checkProcessEncours qui identifie si un process est en cours
 * @return string/boolean (String = Process en cours / False si aucun process en cours)
 */
function checkProcessEncoursOnMixedKpi()
{
    $prod = new ProductModel( ProductModel::getIdMixedKpi() );
    $check = $prod->isProcessRunning();

    return $check;
}
/**
 * Retourne la liste des NA communs � diff�rents produits.
 *
 * @param array $products : liste des produits
 * @return array 
 */
function naLevelsInCommon($products){
	// liste des NA communs aux diff�rents produits
	foreach ($products as $p) {
		$na_levels_plat = array();
		//r�cup�re la liste des na par famille pour le produit 
		$na_levels_family = getNaLabelListForProduct('na','',$p['sdp_id']);
		//r�cup�re les na sans distinction de famille
		foreach($na_levels_family as $nalf)
			$na_levels_plat = array_merge($na_levels_plat,$nalf);
		$na_levels[] = $na_levels_plat;
	}
	
	// on prend les na_levels du premier produit
	$na_levels_in_common = $na_levels[0];
__debug($na_levels[0],'na_levels[0]');
	if (!is_array($na_levels_in_common)	)	return false;
	
	// on boucle sur tous les produits pour trouver tous les NA levels communs
	for ($i=1; $i<sizeof($na_levels); $i++) {
		if (!is_array($na_levels[$i])) return false;
		if(!empty($na_levels[$i])){  // afin de ne pas comparer � mixed KPI qui est vide
			__debug($na_levels[$i],'na_levels['.$i.']');
			$na_levels_in_common = array_uintersect_assoc( $na_levels_in_common, $na_levels[$i],'strtoupper' );
		}
	}
	
	// si on n'a rien :
	if (sizeof($na_levels_in_common) == 0) {
		return false;
	} else {
		// on renvoie la liste
		return $na_levels_in_common;
	}
}
?>