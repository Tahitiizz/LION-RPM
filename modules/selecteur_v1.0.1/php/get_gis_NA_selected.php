<?php
/*
	 MPR 02/09/2009
		- Correction du bug 11339 - Utilisation d'un explode avec @ afin de récupérer les id et labels des éléments réseau sélectionnés
	 22/09/2010 NSE bz 11339 : liste de NE mal initialisée au premier lancement du Gis
*/
?>
<?php
/**
*	Ce script est appelé via AJAX par gis_NA.js.
*	Il recherche la liste des élements actuellement selectionnés dans le network element selecteur (nelsel) 
*
*	L'objet de ce script est principalement de récupérer les LABELs des éléments sélectionnés.
*
*	@author	MPR - 25/08/2009
*	@version	CB 5.0.0.0
*	@since	CB 5.0.0.0
*
*/
	
//	include_once("../../../php/database_connection.php");
include_once dirname(__FILE__)."/../../../php/environnement_liens.php";

	// on récupère le contenu de la sélection courante et le séparateur.
    // 22/09/2010 NSE bz 11339 : $_REQUEST au lieu de $_POST
	$current_selection	= $_REQUEST['current_selection'];
	$labels_only		= $_REQUEST['labels_only'];
	
	
	$tab = explode("|s|", $current_selection);
	
	$html='';
	$htmlTruncate = false;
	
	foreach($tab as $value)
	{
		// maj 02/09/2009 - Correction du bug 11339 - Utilisation d'un explode avec @ afin de récupérer les id et labels des éléments réseau sélectionnés
		$v = explode("@",$value);
	
		$label = ($v[2] != "") ? $v[2] : "(".$v[1].")";
		// $label = $label_elem;
		
		$label .= ' ['.$v[0].']';
				
		/*
			On retourne la liste des éléments
			Note : vous pouvez utilisez la classe css boutonNeSelectionDeleteElement (cf fichier css) pour afficher une image de suppression.
			L'apple à la fonction saveInNeSelection(valeur) supprimera la valeur de la sélection courante.
		*/
		// 11:07 26/08/2009 GHX
		// Ajout du utf8_encode
		if ($labels_only) {
			if ( strlen($html.$label) > 100 )
			{
				$htmlTruncate = true;
				break;
			}
			else
			{
				$html .= htmlentities($label, ENT_QUOTES, 'utf-8').', ';
			}
		} else {
				
			$html .= "
				<li id='li_$value' style='cursor:pointer;'>".htmlentities($label, ENT_QUOTES, 'utf-8')."
					<input type='button' class='boutonNeSelectionDeleteElement' title='".__T('SELECTEUR_DELETE_FROM_CURRENT_SELECTION')."' 
						onclick=\"saveInNeSelection('$value'); $('li_$value').remove();saveCurrentSelection();\" />
				</li>";
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
		
	// $html = "QS: ".$_SERVER["QUERY_STRING"];
	echo $html;
	
?>
