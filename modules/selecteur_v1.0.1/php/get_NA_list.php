<?php
/*
	20/07/2009 GHX
		- Modification du fichier include
		- Utilisation de la classe DatabaseConnection
		- Appel des bons paramètres passés en GET
		- Prise en compte de l'id produit
		- Echapement des cotes dans les labels sinon erreur JS
	25/11/2011 ACS BZ 24792 Virtual SAI of corporate are visibles in multiple GUI
*/
?>
<?php
/**
*	Ce fichier est appelé via ajax pour donner la liste des network elements en fonction d'un na_level
*	C'est ce fichier qui permet de peupler les accordéons du network element selecteur
*
*	@author	SLC - 26/09/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*/

// 11:41 20/07/2009 GHX
// On include plutot environnement_liens au lieu de database_connection pour avoir accès aux fonctions __debug , __T ...
include_once("../../../php/environnement_liens.php");

global $database_connection;

// on récupère le paramètre unique : $na
$idProduct = $_GET['product'];
$na = $_GET['idT'];
$html = '';

// 25/11/2011 ACS BZ 24792 Virtual SAI of corporate are visibles in multiple GUI
// on va chercher les network elements
$query = "
	SELECT DISTINCT 
		eor_id,
		CASE WHEN eor_label IS NULL 
		THEN '('||eor_id||')'
		ELSE eor_label END 
	FROM
		edw_object_ref
	WHERE
		eor_id IS NOT NULL
		AND ".NeModel::whereClauseWithoutVirtual()."
		AND	eor_obj_type = '$na'
	--	AND	eor_on_off=1
	ORDER BY
		eor_label
";

// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
$db = Database::getConnection($idProduct);
$result = $db->execute($query);

if($db->getNumRows() > 0){
	
	// on génère l'HTML
	while ( $row = $db->getQueryResults($result, 1) )
	{
		$html .= "
		<input type='checkbox' id='{$row['eor_id']}' value='{$row['eor_id']}'
			onclick=\"saveInNeSelection('{$row['eor_id']}','".str_replace("'", "\'", $row['eor_label'])."');\" />
		<label for='{$row['eor_id']}'>{$row['eor_label']}</label>
		<br />";
	}
}
else
{
	$html = __T('SELECTEUR_NO_VALUE_FOUND');
}

echo $html;