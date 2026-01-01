<?php
/**
*	@cb4100@
*	- Creation SLC	 29/10/2008
*
*	Ajoute l'element au graph et renvoie le <li> de l'element
*
*	30/01/2009 GHX
*		- modification des requetes SQL pour mettre des valeurs entre cotes [REFONTE CONTEXTE]
*		- nouveau format de l'ID id pour la table sys_pauto_config [REFONTE CONTEXTE]
*	02/02/2009 GHX
*		- Appel à la fonction qui génére un unique ID generateUniqId() [REFONTE CONTEXTE]
*		- Suppression de la colonne internal_id [REFONTE CONTEXTE]
*	05/02/2009 GHX
*		- Correction pour savoir si l'élément est déjà dans le graphe [REFONTE CONTEXTE]
*	07/07/2009 GHX
*		- Correction du BZ10450 [REC][T&A Cb 5.0][GTM Builder] : pour la construction d'un pie, le choix du raw/kpi de split n'est pas enregistré
*		- Correction d'un petit bug pour préciser le sort by quand il n'y en a pas
*	06/08/2009 GHX
*		- Correction BZ 10901
*	14/08/2009 GHX
*		- Modification des requetes SQL qui récupère le label des RAW/KPI afin de récupérer aussi leur nom
*		- Ajout du nom et du type dans le code HTML dans des balises qui ne sont pas affichées
*		- (Evo) Modification pour prendre en compte le faite que dans un graphe on peut avoir plusieurs fois le meme KPI [code+label] identique et qu'il est considere comme un seul
*	30/11/2009 MPR
*		- Correction du bug 13105 : On supprime les ' dans la légende du raw ou kpi concerné
*	28/01/2015 JLG
*		- Correction du bug 32204 : suppression de la taille du label d'un élément pour éviter un retour à la ligne du label
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

// on va chercher la valeur max de ligne pour les courbes actuellement dans le graph
$query = " --- get max(ligne) for page $id_page
	select ligne from sys_pauto_config where id_page='$id_page' order by ligne desc limit 1";
$ligne = intval($db->getone($query));
$ligne++;


// on ajoute la nouvelle courbe dans le graph
// on va donc d'abord chercher la prochaine valeur de id
// 10:13 30/01/2009 GHX
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
	values ('$next_id','$id_elem','$class_object','$id_page',$ligne,'$id_product')
";
$db->execute($query);

// on insert les valeurs par défaut dans graph_data

// on insert dans la table
$query = " --- we insert graph data
	insert into graph_data (id_data,data_legend,position_ordonnee,display_type,line_design,color,filled_color)
	values ('$next_id','{$data_info['data_legend']}','left','line','square','#FFFFFF','#1414E4@0.5')";
$db->execute($query);

// >>>>>>>>>>
// 09:33 07/07/2009 GHX
// Correction du BZ10450 [REC][T&A Cb 5.0][GTM Builder] : pour la construction d'un pie, le choix du raw/kpi de split n'est pas enregistré
$queryObjectType = "
	SELECT object_type
	FROM graph_information
	WHERE id_page = '{$id_page}'
";
$resultObjectType = $db->getOne($queryObjectType);
// On vérifie si le graph est de type PIE si oui ...
if ( $resultObjectType == 'pie3D' )
{
	// ... et que c'est le premier élément que l'on insert pour ce PIE ...
	if ( $ligne == 1 )
	{
		// ... on décide que c'est cet élément qui sert de split
		$queryUpdateSplitBy = "
				UPDATE 
					graph_information 
				SET 
					pie_split_type = 'first_axis',
					pie_split_by = '{$next_id}'
				WHERE 
					id_page = '{$id_page}'
			";
		$db->execute($queryUpdateSplitBy);
	}
}
// <<<<<<<<<<

// 09:50 07/07/2009 GHX
// Correction d'un bug dans le cas ou l'on vient juste de créé le graphe et que l'on inserte un élément
// on le considère que c'est Sort By par défaut sinon on se retrouve avec la valeur "there is no data in your graph" au lieu d'un ID venant de sys_pauto_config
if ( $ligne == 1 )
{
	$queryUpdateDefaultOrderby= "
		UPDATE 
			graph_information 
		SET 
			default_orderby = '{$next_id}'
		WHERE 
			id_page = '{$id_page}'
	";
	$db->execute($queryUpdateDefaultOrderby);
	
	// 14:58 06/08/2009 GHX
	// Correction du BZ 10901
	$queryUpdateGisBasedOn= "
		UPDATE 
			graph_information 
		SET 
			gis_based_on = '{$next_id}'
		WHERE 
			id_page = '{$id_page}'
			AND gis = 1
	";
	$db->execute($queryUpdateGisBasedOn);
}

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
			<div class='label'>{$data_info['data_legend']}</div>
			<div class='name' style='display:none'>{$data_info['name']}</div>
			<div class='type' style='display:none'>{$class_object}</div>
		</div>
		<div class='na_levels'>$elem_na_levels</div>
		<div id='elem_prop__$next_id' class='properties'></div>
	</li>
";
?>