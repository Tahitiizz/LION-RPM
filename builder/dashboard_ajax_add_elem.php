<?php
/**
*	@cb4100@
*	- Creation SLC	 29/10/2008
*
*	Ajoute l'element au Dashboard et renvoie le <li> de l'element
*
*	29/01/2009 GHX
*		- modification des requêtes SQL pour mettre id_user & id_page entre cote au niveau des inserts  [REFONTE CONTEXTE]
*	30/01/09 GHX
*		- modification du nouveau format pour id de la table sys_pauto_config[REFONTE CONTEXTE]
*	02/02/2009 GHX
*		- Appel à la fonction qui génére un unique ID generateUniqId() [REFONTE CONTEXTE]
*		- Suppression de la colonne internal_id [REFONTE CONTEXTE]
*	05/02/2009 GHX
*		- Correction pour savoir si l'élément est déjà dans le graphe [REFONTE CONTEXTE]
*
*	09/06/10 YNE/FJT : SINGLE KPI
*   20/01/2011 OJT : Correction bz 20214 Ajout d'informations sur le produit (label)
*/

$intranet_top_no_echo = true;
include_once('common.inc.php');

// On recupère les données
// 30/01/2009 GHX : Suppression du formatage en INT
// 20/01/2011 OJT : Ajout de l'id produit
$id_page		= $_POST['id_page'];
$id_elem		= $_POST['id_elem'];
$id_product		= $_POST['id_product'];

// on va chercher le Dashboard
$query = " --- on va chercher le Dashboard $id_page
	SELECT * FROM sys_pauto_page_name WHERE id_page='$id_page'";
$dash = $db->getrow($query);
if (!allow_write($dash)) {
	echo __T('G_GDR_BUILDER_ERROR_YOU_DONT_HAVE_THE_RIGHT_TO_CHANGE_THAT_DASHBOARD');
	exit;
}

// on verifie que ce graph n'est pas déjà dans le Dashboard
$query = " --- check if graph already in Dashboard
	SELECT id FROM sys_pauto_config WHERE id_elem='$id_elem' AND id_page='$id_page'
";
$check_data = $db->getone($query);
// 11:30 05/02/2009 GHX
// Changement du format de l'id
if (!empty($check_data)) {
	echo __T('G_GDR_BUILDER_ERROR_THIS_GTM_IS_ALREADY_INSIDE_THAT_DASHBOARD',$class_object);
	exit;
}

// on va chercher la valeur max de ligne pour ce dashboard
$query = " --- get max(ligne) for page $id_page
	select ligne from sys_pauto_config where id_page='$id_page' order by ligne desc limit 1";
$ligne = $db->getone($query);
$ligne++;
if ( empty($ligne) ) $ligne = 1;

// on ajoute le nouveau graph dans le dashboard
// on va donc d'abord chercher la prochaine valeur de id
// 09:52 30/01/2009 GHX
// Nouveau format pour l'ID, ce n'est plus un serial
// 14:23 02/02/2009 GHX
// Appel à la fonction qui génére un unique ID
$next_id = generateUniqId('sys_pauto_config');

// on a besoin d'infos du Graph : page_name, internal_id, graph_level, object_type
// on a besoin du graph_label et internal_id, du graph_level et du object_type (graph / pie@...)
$query = " --- we fetch info of the graph $id_elem
	select gi.object_type,

		-- calcul du label :
		sppn.page_name
		|| CASE WHEN sppn.id_user IS NOT NULL AND sppn.id_user <> '{$user_info['id_user']}' THEN 
			(SELECT ' ['||username||']' FROM users WHERE id_user=sppn.id_user) 
			ELSE '' 
			END
		AS graph_label,
        (select sdp_label from sys_definition_product where sdp_id=$id_product) as sdp_label,
		-- calcul du niveau de droit du graph :
		CASE WHEN sppn.droit='customisateur' THEN 1
			ELSE CASE WHEN sppn.droit='client' AND sppn.id_user IS NULL THEN 2
			ELSE CASE WHEN sppn.droit='client' AND sppn.id_user IS NOT NULL AND sppn.id_user <> '{$user_info['id_user']}' THEN 3
			ELSE CASE WHEN sppn.droit='client' AND sppn.id_user IS NOT NULL AND sppn.id_user = '{$user_info['id_user']}' THEN 4
		END END END END AS graph_level

	from sys_pauto_page_name sppn
		join graph_information gi on sppn.id_page=gi.id_page
	where sppn.id_page='$id_elem'";
$elem = $db->getrow($query);

// on insert le plot
$query = " --- insert new graph into dashboard
	insert into sys_pauto_config (id,id_elem,class_object,id_page,ligne)
	values ('$next_id','$id_elem','graph','$id_page',$ligne)
";
$db->execute($query);



//
// a partir de maintenant on compose le HTML qui doit être retourné (avec icone, label, ... de l'élément)
//

// on calcule les na levels en commun (axe1 et axe3)
$na_levels_in_common = getNALabelsInCommon($id_elem,'na');
$na_axe3_levels_in_common = getNALabelsInCommon($id_elem,'na_axe3');

if (is_array($na_levels_in_common))
	$elem_na_levels = implode(', ',$na_levels_in_common);
if (($na_levels_in_common) and ($na_axe3_levels_in_common != ''))
	$elem_na_levels .= ', '; 
if ($na_axe3_levels_in_common)
	$elem_na_levels .= '<span class="axe3">'.implode(', ',$na_axe3_levels_in_common).'</span>';

// 24/03/09 YNE
// add single KPI icone
switch($elem['object_type']){
	case 'graph' : $image = "bar"; break;
	case 'pie3D' : $image = "pie"; break;
	case 'singleKPI' : $image = "single"; break;
	default : $image = "bar";
}

// Lecture du label produit (bz20214)
$productLabel = $elem['sdp_label'];
if( strlen( trim( $productLabel ) ) === 0 )
{
    $productLabel = "multi product";
}

// on compose l'HTML qui va être ajouté dans la page
// 12/10/2010 NSE 17840 : ajout d'un test JS avant redirection vers lien href : isDashElementDragging
echo "
	<li id='gtm_element__$id_elem'>
		<div style='padding-bottom:1px;'>
			<div class='icon'><img src='images/chart_{$image}_{$elem['graph_level']}.png' alt='".$level_labels[$elem['graph_level']]." GTM' width='16' height='16'/></div>
			<div class='del' onclick=\"delete_element('gtm_element__$id_elem');\"><img src='images/delete.png' alt='".__T('G_GDR_BUILDER_DELETE')."' width='16' height='16'/></div>
			<div class='label'><a onclick='return isDashElementDragging( this );' href='graph.php?id_page=$id_elem'>{$elem['graph_label']}</a></div>
		</div>
		<div class='na_levels'>$elem_na_levels</div>
        <div class='product'>{$productLabel}</div>
		<div id='elem_prop__$id_elem' class='properties'></div>
	</li>
";

?>