<?php
/*
	16/07/2009 GHX
		- Modification pour afficher le label des raw/kpi au lieu de leur nom + ajout du type de l'élément raw ou kpi
	26/08/2009 GHX
		- Limitation du nombre de caractères pour le tooltip et encodage des labels
*/
?>
<?php
/**
*	Ce script est appelé via AJAX par investigation_NA.js.
*	Il recherche la liste des élements actuellement selectionnés dans le selecteur de raw/kpi
*
*	L'objet de ce script est principalement de récupérer les LABELs des éléments sélectionnés.
*
*	@author	SPS - 29/05/2009
*	@version	CB 5.0.1.0.0
*	@since	CB 5.0.0.0
*
*/
	
include_once dirname(__FILE__)."/../../../php/environnement_liens.php";
//	include_once("../../../php/database_connection.php");

	// on récupère le contenu de la sélection courante et le séparateur.
	$current_selection	= $_POST['current_selection'];
	$separator			= $_POST['separator'];
	$labels_only		= $_POST['labels_only'];
	// 16:59 10/07/2009 GHX
	// Mauvaise nom de variable id_prod au lieu de product
	$id_product 		= ( isset($_GET['product']) ) ? $_GET['product'] : $_POST['product'];
	$tab = explode($separator,$current_selection);
	
        // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
	$db = Database::getConnection($id_product);
	
	$html='';
	$htmlTruncate = false;
	foreach($tab as $value)
	{	$v = explode("@",$value);
		$type = $v[0];
		$id_elem = $v[1];
		$name_elem = $v[2];
		
		
		
		if ($type == "raw") {
			$query = "SELECT  sfr.id_ligne, sfr.edw_field_name_label AS name
				FROM sys_field_reference sfr, sys_definition_categorie sdc
				WHERE sfr.id_group_table = sdc.rank
				AND sfr.id_ligne = '".$id_elem."'";
		}
		if ($type == "kpi") {
			$query = "SELECT  sdk.id_ligne, sdk.kpi_label AS name
					FROM sys_definition_kpi sdk
					WHERE sdk.id_ligne = '".$id_elem."'";
		}
			
		$res = $db->getRow( $query );
		if ($res['name']) {
			$label = $res['name'];
			
		}
		else {
		
			$label = "($name_elem)";
		}

		$label .= ' ['.$type.']';
		
		/*
			On retourne la liste des éléments
			Note : vous pouvez utilisez la classe css boutonNeSelectionDeleteElement (cf fichier css) pour afficher une image de suppression.
			L'apple à la fonction saveInNeSelection(valeur) supprimera la valeur de la sélection courante.
		*/
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
				<li id='li_$value' style='cursor:pointer;'>".utf8_encode($label)."
					<input type='button' class='boutonNeSelectionDeleteElement' title='".__T('SELECTEUR_DELETE_FROM_CURRENT_SELECTION')."' 
						onclick=\"saveInNeSelection('$value'); $('li_$value').remove();\" />
				</li>";
		}
	}
	
	// on enlève le ", " à la fin
	// if ($labels_only) $html = substr($html,0,strlen($html)-2);
	
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
