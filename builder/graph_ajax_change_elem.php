<?php
/**
*	- Creation YNE	 22/03/2010 - merge 09/06/2010
*
* Supprime les éléments déjà existants pour le GTM, cette fonctionnalité ne s'applique que lorsque l'on est en single KPI
*	Ajoute l'element au graph et renvoie le <li> de l'element
*/

$intranet_top_no_echo = true;
include_once('common.inc.php');


// on recupère les données
// 10:10 30/01/2009 GHX
// Suppression du formatage en INT
$id_page		= $_POST['id_page'];
$id_elem		= $_POST['id_elem'];
$id_product	= intval($_POST['id_product']);
$class_object	= $_POST['class_object'];

// on va chercher le graph
$query = " --- on va chercher le graph $id_page
	SELECT * FROM sys_pauto_page_name WHERE id_page= '$id_page'";
$graph = $db->getrow($query);
if (!allow_write($graph)) {
	echo __T('G_GDR_BUILDER_ERROR_YOU_DONT_HAVE_THE_RIGHT_TO_CHANGE_THAT_GTM');
	exit;
}

// on verifie que ce raw/kpi n'est pas déjà dans le graph
$query = " --- check if raw/kpi already in graph
	SELECT id FROM sys_pauto_config
	WHERE	id_elem='$id_elem'
		AND	id_product=$id_product
		AND	id_page='$id_page'
		AND	class_object='$class_object'
";
$check_data = $db->getone($query);
// 11:30 05/02/2009 GHX
// Changement du format de l'id
if (!empty($check_data)) {
	echo __T('G_GDR_BUILDER_ERROR_THIS_IS_ALREADY_INSIDE_THAT_GTM',$class_object);
	exit;
}

// On supprime les éléments déjà existants pour ce graph
// On récupère la liste des éléments déjà définit pour ce graph
$query = "SELECT id FROM sys_pauto_config WHERE id_page= '$id_page'";
foreach($db->getAll($query) as $serie){
	$id = $serie['id'];
	// tout d'abord avec les éléments contenus dans la table graph_data
	$query = " --- delete all elements in graph_data
		DELETE FROM graph_data WHERE id_data= '$id'";
	$db->execute($query);

	// puis avec les éléments contenus dans la table sys_pauto_config
	$query = " --- delete all previous elements in graph
		DELETE FROM sys_pauto_config WHERE id='$id'";
	$db->execute($query);
}

// on ajoute la nouvelle courbe dans le graph
// Nouveau format pour l'ID, ce n'est plus géré par un serial
// 14:23 02/02/2009 GHX
// Appel à la fonction qui génére un unique ID
$next_id = generateUniqId('sys_pauto_config');

// 09:15 14/08/2009 GHX
// Modification des 2 requetes suivantes
// -> Pour ajouter un deuxieme champ dans le SELECT
// -> Modifier le premier champ des RAW afin de récupérer le label au lieu du nom
// on a besoin de la data_legend 
if ($class_object == 'counter') {
	$query = " --- we fetch the name of the raw counter
		select edw_field_name_label as data_legend, edw_field_name AS name from sys_field_reference where id_ligne='$id_elem'";
} else {
	$query  = " --- we fetch the name of the kpi
		select kpi_label as data_legend, kpi_name AS name from sys_definition_kpi where id_ligne='$id_elem'";
}
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
$db_temp = Database::getConnection($id_product);
$data_info = $db_temp->getrow($query);

// on va chercher le label du produit
$data_info['sdp_label'] = $db->getone("select sdp_label from sys_definition_product where sdp_id='$id_product'");

// maj 30/11/2009 MPR - Correction du bug 13105 : On supprime les ' dans la légende du raw ou kpi concerné
$data_info['data_legend'] = str_replace("'"," ",$data_info['data_legend']);


// on insert le plot
$query = " --- insert new plot in graph
	insert into sys_pauto_config (id,id_elem,class_object,id_page,ligne,id_product)
	values ('$next_id','$id_elem','$class_object','$id_page',1,'$id_product')
";
$db->execute($query);
// on insert dans la table
$query = " --- we insert graph data
	insert into graph_data (id_data,data_legend,position_ordonnee,display_type,line_design,color,filled_color)
	values ('$next_id','{$data_info['data_legend']}','left','line','square','#FFFFFF','#1414E4@0.5')";
$db->execute($query);

// 09:50 07/07/2009 GHX
// Correction d'un bug dans le cas ou l'on vient juste de créé le graphe et que l'on inserte un élément
// on le considère que c'est Sort By par défaut sinon on se retrouve avec la valeur "there is no data in your graph" au lieu d'un ID venant de sys_pauto_config
$queryUpdateDefaultOrderby= "UPDATE graph_information SET default_orderby = '{$next_id}' WHERE id_page = '{$id_page}'";
$db->execute($queryUpdateDefaultOrderby);

$queryUpdateDefaultOrderby= "UPDATE graph_information SET gis_based_on = '{$next_id}' WHERE id_page = '{$id_page}' AND gis = 1";
$db->execute($queryUpdateDefaultOrderby);

//
// a partir de maintenant on compose le HTML qui doit être retourné (avec icone, label, ... de l'élément)
//

// on regarde si le raw/kpi possède un range --> pour afficher la bonne icone
$has_range = '';
$query = " --- check if counter/kpi has range
	select count(*) from  sys_data_range_style where data_type='$class_object' and id_element='$id_elem'";
$nb_ranges = $db_temp->getone($query);
if ($nb_ranges > 0) $has_range = '_ranged';

// si on a un kpi, on regarde si c'est un 'client kpi' --> pour afficher la bonne icone
$is_client = '';
if ($class_object == 'kpi') {
	$query = " --- check if kpi is client
		select value_type from sys_definition_kpi where id_ligne='$id_elem'";
	$check_client = $db_temp->getone($query);
	if ($check_client == 'client') $is_client = '_client';
}

// on va chercher les na_levels
if ($class_object == 'counter') {
	$query = " --- get families of $class_object $id_elem
		SELECT family FROM sys_definition_group_table WHERE edw_group_table IN
			(SELECT edw_group_table FROM sys_field_reference WHERE id_ligne='$id_elem')
	";
} else {
	$query = " --- get families of $class_object $id_elem
		SELECT family FROM sys_definition_group_table WHERE edw_group_table IN
			(SELECT edw_group_table FROM sys_definition_kpi WHERE id_ligne='$id_elem')
	";
}
$family = $db_temp->getone($query);

// on récupère les na_levels disponibles (axe 1)
$na_levels = getNaLabelListForProduct('na','',$id_product);
// on récupère les na_levels disponibles (axe 3)
$na_levels_axe3 = getNaLabelListForProduct('na_axe3','',$id_product);

// on calcule les na levels en commun (axe1 et axe3)
if (is_array($na_levels[$family]))
	$elem_na_levels = implode(', ',$na_levels[$family]);
if (($na_levels_axe3[$family]) and ($elem_na_levels != ''))
	$elem_na_levels .= ', '; 
if ($na_levels_axe3[$family])
	$elem_na_levels .= '<span class="axe3">'.implode(', ',$na_levels_axe3[$family]).'</span>';


// __debug($query);

// on compose l'HTML qui va être ajouté dans la page
// 09:14 14/08/2009 GHX
// Ajout de 2 informations supplémentaires comme le nom de l'élément (différent du label) et son type (counter ou kpi)
echo "
	<li id='gtm_element__$next_id'>
		<div style='padding-bottom:1px;'>
			<div class='icon'><img src='images/brick_$class_object$is_client$has_range.png' alt='".(($is_client)?__T('G_GDR_BUILDER_CLIENT_'):'')."$class_object".(($has_range)?__T('G_GDR_BUILDER_WITH_RANGE'):'')."' width='16' height='16'/></div>
			<div class='del' onclick=\"delete_element('gtm_element__$next_id');\"><img src='images/delete.png' alt='".__T('G_GDR_BUILDER_DELETE')."' width='16' height='16'/></div>
			<div class='info' onclick=\"get_data_properties('elem_prop__$next_id');\"><img src='images/application_edit_off.png' alt='".__T('G_GDR_BUILDER_DETAILS')."' width='16' height='16'/></div>
			<div class='product'>{$data_info['sdp_label']}</div>
			<div class='label' style='width: 450px;'>{$data_info['data_legend']}</div>
			<div class='name' style='display:none'>{$data_info['name']}</div>
			<div class='type' style='display:none'>{$class_object}</div>
		</div>
		<div class='na_levels'>$elem_na_levels</div>
		<div id='elem_prop__$next_id' class='properties'></div>
	</li>
";
?>