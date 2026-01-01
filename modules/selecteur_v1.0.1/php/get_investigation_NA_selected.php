<?php
/*
	26/08/2009 GHX
		- Ajout d'un utf8_encode sur le label des éléments réseaux
		- On limite le nombre d'éléments affichés pour le tooltip sinon trop long pour avoir le meme format que le sélecteur des dashboards
	31/08/2009 GHX
		- Corrrection du BZ 11272 [REC][T&A CB 5.0][INVESTIGATION DASHBOARD]: caractères accentués sous forme de carrés
			-> Utilisation de  htmlentities($label, ENT_COMPAT, 'utf-8') au lieu de utf8_encode($label)
	24/09/2009 GHX
		-> Utilisation de  htmlentities($label) au lieu de htmlentities($label, ENT_COMPAT, 'utf-8')
*/
?>
<?php
/**
*	Ce script est appelé via AJAX par investigation_NA.js.
*	Il recherche la liste des élements actuellement selectionnés dans le network element selecteur (nelsel) 
*
*	L'objet de ce script est principalement de récupérer les LABELs des éléments sélectionnés.
*
*	@author	SPS - 29/05/2009
*	@version	CB 5.0.0.0
*	@since	CB 5.0.0.0
*
*/
	
//	include_once("../../../php/database_connection.php");
include_once dirname(__FILE__)."/../../../php/environnement_liens.php";

	// on récupère le contenu de la sélection courante et le séparateur.
	$current_selection	= $_POST['current_selection'];
	$separator			= $_POST['separator'];
	$labels_only		= $_POST['labels_only'];
	
	if ($separator) $tab = explode($separator,$current_selection);
	else $tab = $current_selection;
	
	$html='';
	$htmlTruncate = false;
	foreach($tab as $value)
	{ 
		$v = explode("@",$value);
		$id_elem = $v[1];
		// 08/12/2009 BBX : ajout d'un utf8_decode. BZ 11482
		$label_elem = utf8_decode($v[2]);
		
		$query = "SELECT eor_label FROM  edw_object_ref WHERE eor_id='$id_elem'";
		
		// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
		$db = Database::getConnection($id_product);	
		$res = $db->getRow( $query );
		if ($res['eor_label']) {
			
			$label = $res['eor_label'];
		}
		else {
			$label = $label_elem;
		}
		
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
				$html .= htmlentities($label).', ';
			}
		} else {
				
			$html .= "
				<li id='li_$value' style='cursor:pointer;'>". htmlentities($label)."
					<input type='button' class='boutonNeSelectionDeleteElement' title='".__T('SELECTEUR_DELETE_FROM_CURRENT_SELECTION')."' 
						onclick=\"saveInNeSelection('$value'); $('li_$value').remove();\" />
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
