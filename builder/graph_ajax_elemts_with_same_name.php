<?php
/**
 * Si la variable $setApplySameStyle n'existe pas c'est que le script a été appelé via AJAX, donc on regarde juste si on a plusieurs éléments de même
 * type qui ont le mêmes codes et mêmes labels. Sinon c'est que le fichier a été appelé via un include dans le fichier graph_ajax_set_elem_properties.php. 
 * Il faut appliquer le style de l'élément qui vient d'être sauvegarder par l'utilisateur sur les autres éléments qui ont le même codes et labels.
 *
 *	14/08/2009 GHX
 *		- Création du script
 *
 * @author GHX
 * @version CB 5.0.0.05
 * @since CB 5.0.0.05
 */

// Comme la variable n'existe pas c'est que le fichier common.inc.php n'a pas été encore include
// et on peut directement récupérer l'id du graphe courant passé en POST
if ( !isset($setApplySameStyle) )
{
	$intranet_top_no_echo = true;
	include_once('common.inc.php');
	
	// Récupère l'id du graphe courant
	$id_page = $_POST['id_page'];
	$id = '';
}
else
{
	// Si on récupère l'id du graphe à partir de la variable $graph
	$id_page = $graph['id_page'];
}

// Récupère la liste des éléments du graphes
$query = "
	SELECT
		class_object,
		data_legend,
		id_elem,
		id_product,
		id
	FROM
		sys_pauto_config AS spc,
		graph_data AS gd
	WHERE 
		spc.id = gd.id_data
		AND spc.id_page = '{$id_page}'
	ORDER BY
		id_product ASC
	";

$results = $db->getAll($query);

// Si le graphe ne contient éléments on ne va pas plus loin
if ( count($results) == 0 )
{
	die('ok');
}

// Création des requetes SQL qui permettront de récupérer le nom d'un raw ou d'un kpi en fonction de son identifiant
$query_kpi = "SELECT lower(kpi_name) AS name FROM sys_definition_kpi WHERE id_ligne = '%s'";
$query_counter = "SELECT lower(edw_field_name) AS name FROM sys_field_reference WHERE id_ligne = '%s'";

// Initalisation des tableaus qui contiendront soit la liste des raw soit la liste des kpi
$kpi = array();
$counter = array();

$currentElem = null;
$id_product = null;
$db_temp = null;
// On boucle sur la liste des éléments du graphe
foreach ( $results as $result )
{
	// Initilisation d'une connexion sur la bonne base de données
	if ( $id_product != $result['id_product'] )
	{
		$id_product = $result['id_product'];
                // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
		$db_temp = Database::getConnection($id_product);
	}
	// Récupère le nom de l'élément ...
	$name = $db_temp->getOne(sprintf(${'query_'.$result['class_object']}, $result['id_elem']));
	// ... l'ajout dans le tableau 
	$result['name'] = $name;
	// Ajout l'élément dans le tableau appropié en fonction de son type
	${$result['class_object']}[strtolower($name.$result['data_legend'])][] = $result;
	
	// On ne rentre dans cette condition uniquement si le fichier a été appelé du fichier graph_ajax_set_elem_properties.php
	if ( $id == $result['id'] )
	{
		$currentElem = $result;
	}
}

if ( isset($setApplySameStyle) )
{
	setApplySameStyle($currentElem, ${$currentElem['class_object']}, $db);
}
else
{
	// Initalisation d'un tableau qui contiendra le code HTML renvoyé à l'utilisateur
	$htmlResult = array();
	
	// Vérifie si on a des RAW qui ont le même code/label
	checkElementsWithSameName($kpi, 'KPI', $htmlResult );
	// Vérifie si on a des KPI qui ont le même code/label
	checkElementsWithSameName($counter, 'Raw', $htmlResult );
	
	if ( count($htmlResult) > 0 )
	{
		echo implode("<br />", $htmlResult);
		die();
	}
	else
	{
		die('ok');
	}
}

/**
 * Vérifie si un élément est présent plusieurs fois
 * 
 * @author GHX
 * @version CB 5.0.0.05
 * @since CB 5.0.0.05
 * @param array $elements tableau contenant la liste d'un type d'élément présents dans le graphe
 * @param string $type type des éléments contenu dans le premier table kpi ou raw
 * @param array &$htmlResult tableau de résultat contenant le code HTML qui sera renvoyé à l'utilisateur
 */
function checkElementsWithSameName ( $elements, $type, &$htmlResult )
{
	if ( count($elements) > 0 )
	{
		foreach ( $elements as $index => $elem )
		{
			if ( count($elem) > 1 )
			{
				$htmlResult[] = __T('G_GTM_BUILDER_INFO_SAME_KPI_RAW');
				return;
			}
		}
	}
} // End function checkElementsWithSameName

/**
 * Applique le style de l'élément courant aux autres qui ont le même code/label
 *
 * @param array $currentElem tableau d'information sur l'élément courant (celui dont l'utilisateur vient de faire save)
 * @param array $elements tableau des autres éléments du graphe du même type que l'élément courant
 * @param DataBaseConnection $db 
 */
function setApplySameStyle ( $currentElem, $elements , $db)
{
	$query = "
		UPDATE
			graph_data
		SET
			position_ordonnee = '{$_POST['position_ordonnee']}',
			display_type      = '{$_POST['display_type']}',
			line_design       = '{$_POST['line_design']}',
			color             = '{$_POST['color']}',
			filled_color      = '{$_POST['fill_color']}@{$_POST['fill_transparency']}'
		WHERE 
			id_data = '%s'
		";
	
	$elements = $elements[$currentElem['name'].$currentElem['data_legend']];
	
	// SI on a qu'un seul élément c'est donc l'élément courant
	if ( count($elements) > 1 )
	{
		foreach ( $elements as $el )
		{
			// On ne prend pas en compte l'élément courant
			if ( $el['id'] == $currentElem['id'] )
				continue;
			
			
			$db->execute(sprintf($query, $el['id']));
		}
	}
} // End function setApplySameStyle
?>