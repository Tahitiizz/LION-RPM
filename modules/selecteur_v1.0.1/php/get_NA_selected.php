<?php
/*
	22/06/2009 GHX
		- Modification de l'affichage, il n'y avait pas d'explode sur le || donc on n'avait jamais le label d'un élément réseau
	27/07/2009 GHX
		- Si on dépasse le nombre de caractères maxi on met des 3 petits points + le nombre d'éléments sélectionnés
	26/08/2009 GHX
		- Ajout de l'encodage des labels
 
 *  20/09/2012 MMT DE 5.3 Delete Topology - test si l'element existe dans un produit sinon on affiche rien
*/
?>
<?php
/**
*	Ce script est appelé via AJAX par dashboard_NA.js.
*	Il recherche la liste des élements actuellement selectionnés dans le network element selecteur (nelsel) 
*
*	L'objet de ce script est principalement de récupérer les LABELs des éléments sélectionnés.
*
*	@author	SLC - 26/09/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
* 
* 	29/05/2009 - SPS : utilisation de la classe de connexion DatabaseConnection
*
*/

include_once dirname(__FILE__)."/../../../php/environnement_liens.php";

	// on récupère le contenu de la sélection courante et le séparateur.
	// 09:25 26/08/2009 GHX
	// BZ 11230
	$current_selection	= $_POST['current_selection'];
	$separator			= $_POST['separator'];
	$labels_only		= $_POST['labels_only'];
	// 08/12/2009 BBX : ajout de l'id produit. BZ 11482
	$id_product			= $_POST['product'];
	
	$tab = $current_selection;
	if ($separator) $tab = explode($separator,$current_selection);

	$html='';
	$htmlTruncate = false;
	foreach($tab as $value)
	{
		if ( empty($value) )
			continue;
		
		$tmp = explode('||', $value);

		$neLabel = '';
        // 20/09/2012 MMT DE 5.3 Delete Topology - test si l'element existe dans un produit sinon on affiche rien
		$neExists = false;
		if(empty($id_product))
		{
			foreach(ProductModel::getActiveProducts() as $prod)
			{
				// Produit courant
				$product = $prod['sdp_id'];
				// Si le NE existe sur le produit
				if(NeModel::exists($tmp[1], $tmp[0], $product)) 
				{
                    $neExists = true;
					if(empty($neLabel))
						$neLabel = NeModel::getLabel($tmp[1], $tmp[0], $product);

				}
			}
		}
		else
		{
            // 20/09/2012 MMT DE 5.3 Delete Topology - test si l'element existe dans un produit sinon on affiche rien
            $neExists = NeModel::exists($tmp[1], $tmp[0], $id_product);
			$neLabel = NeModel::getLabel($tmp[1], $tmp[0], $id_product);
		}
        // 20/09/2012 MMT DE 5.3 Delete Topology - test si l'element existe dans un produit sinon on affiche rien
        if($neExists){
		if (!empty($neLabel)) {
			$label = $neLabel;
		}
		else {
			$label = "(".$tmp[1].")";
		}

		$label .= ' ['.$tmp[0].']';

		/*
			On retourne la liste des éléments
			Note : vous pouvez utilisez la classe css boutonNeSelectionDeleteElement (cf fichier css) pour afficher une image de suppression.
			L'apple à la fonction saveInNeSelection(valeur) supprimera la valeur de la sélection courante.
		*/
		// 11:25 26/08/2009 GHX
		// Ajout du utf8_encode
		if ($labels_only) {
			if ( strlen($html.$label) > 100 )
			{
				$htmlTruncate = true;
				break;
			}
			else
			{
				$html .= utf8_encode($label).', ';
			}
		} else {
			$html .= "
				<li id='li_{$tmp[0]}_{$tmp[1]}' style='cursor:pointer;'>".utf8_encode($label)."
					<input type='button' class='boutonNeSelectionDeleteElement' title='".__T('SELECTEUR_DELETE_FROM_CURRENT_SELECTION')."' 
						onclick=\"saveInNeSelection('$value'); $('li_{$tmp[0]}_{$tmp[1]}').remove();\" />
				</li>";
		}
	}
    }
	
	// on enlève le ", " à la fin
	if ($labels_only)
	{
		$html = substr($html,0,strlen($html)-2);
		// 16:07 27/07/2009 GHX
		// Si on dépasse le nombre de caractères maxi on met des 3 petits points
		if ( $htmlTruncate )
		{
			$html .= ' ... '.__T('U_SELECTEUR_NUMBER_NE_SELECTED', count($tab));
		}
	}
	
	echo $html;
	
?>
