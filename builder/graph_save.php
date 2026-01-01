<?php
/**
 * Cette page sauvegarde, ou crée, un graph (création SLC 29/10/2008)
 *
 * $Author: o.jousset $
 * $Date: 2012-02-06 18:07:49 +0100 (lun., 06 fÃ©vr. 2012) $
 * $Revision: 63556 $
 *
 *	29/01/2009 GHX
 *		- modification des requêtes SQL pour mettre id_user & id_page entre cote au niveau des inserts  [REFONTE CONTEXTE]
 *	30/01/2009 GHX
 *		- modification des requêtes SQL pour mettre id_user & id_page entre cote au niveau des inserts  [REFONTE CONTEXTE]
 *		- nouveau format pour l'id_page[REFONTE CONTEXTE]
 *	02/02/2009 GHX
 *		- Appel à la fonction qui génére un unique ID generateUniqId() [REFONTE CONTEXTE]
 *		- Suppression de la colonne internal_id [REFONTE CONTEXTE]
 *	28/10/2009 BBX :
 *		- si $_POST['gis'] n'existe pas, on force à 0. BZ 12357
 *	09/06/2010 YNE/FJT : SINGLE KPI
 *  22/09/2011 OJT : bz23828, gestion des ' et " (initilalisation des variables au début du script)
 *  04/11/2011 ACS BZ 24512 Impossible to modify graph name
 */

$intranet_top_no_echo = true;
include_once('common.inc.php');

// On recupère les données passées au script
// 30/01/2009 GHX : suppression du formatage en INT
// 22/09/2011 OJT : bz23828, gestion des ' et ' par pg_escape_string
$id_page         = $_POST['id_page'];
$gtmName         = pg_escape_string( stripslashes( $_POST['page_name'] ) );
$gtmDefinition   = pg_escape_string( stripslashes( $_POST['definition'] ) );
$gtmTrSh         = pg_escape_string( stripslashes( $_POST['troubleshooting'] ) );
$gtmScale        = $_POST['scale'];
$gtmObjectType   = $_POST['object_type'];
$gtmPosLegend    = $_POST['position_legende'];
$gtmWidth        = $_POST['graph_width'];
$gtmHeight       = $_POST['graph_height'];
$gtmOrdLeftName  = $_POST['ordonnee_left_name'];
$gtmOrdRightName = $_POST['ordonnee_right_name'];
$gtmDefOrderBy   = $_POST['default_orderby'];
$gtmDefAscDesc   = intval( $_POST['default_asc_desc'] );
$gtmGis          = 0;

// Initialisation du GIS si présent
if( isset( $_POST['gis'] ) )
{
    $gtmGis = $_POST['gis'];
}

// On impose le 'top' pour la legende pour les graphes SingleKPI
if ( $gtmObjectType == 'singleKPI' )
{
	$gtmPosLegend = 'top';
}

// Si il s'agit d'une mise à jour du Graph
if ( $id_page != '0' && !empty( $id_page ) )
{
	// On verifie qu'on a les droits d'écriture dessus
	$query = "SELECT * FROM sys_pauto_page_name WHERE id_page='{$id_page}'";
	$graph = $db->getrow($query);
	if (!allow_write($graph)) {
		echo __T('G_GDR_BUILDER_ERROR_YOU_DONT_HAVE_THE_RIGHT_TO_CHANGE_THAT_GTM');
		exit;
	}

	// 04/11/2011 ACS BZ 24512 Impossible to modify graph name
	// on update le page name
	$query = "UPDATE sys_pauto_page_name SET page_name='{$gtmName}' WHERE id_page='{$id_page}'";
	$db->execute($query);
	
    // 30/12/2010 BBX :  bz19130, en cas de SingleKpi, le scale doit toujours être de type "textlin"
    if( $gtmObjectType == 'singleKPI' )
    {
        $gtmScale = 'textlin';
    }

	// On update les autres champs du graph
    // 30/07/2009 BBX : bz10431, si le GIS n'est pas transmis, on ne le met pas à jour.
	$query = "UPDATE graph_information SET
                definition          = '{$gtmDefinition}',
                troubleshooting     = '{$gtmTrSh}',
                scale               = '{$gtmScale}',
                object_type         = '{$gtmObjectType}',";
	if(isset($_POST['gis'])) $query .= "gis = '{$gtmGis}',";
	$query .= "gis_based_on         = '{$_POST['gis_based_on']}',
                position_legende    = '{$gtmPosLegend}',
                graph_width         = '{$gtmWidth}',
                graph_height        = '{$gtmHeight}',
                ordonnee_left_name  = '{$gtmOrdLeftName}',
                ordonnee_right_name	= '{$gtmOrdRightName}',
                default_orderby		= '{$gtmDefOrderBy}',
                default_asc_desc    = {$gtmDefAscDesc},
                pie_split_by        = '{$_POST['pie_split_by']}',
                pie_split_type      = '{$_POST['pie_split_type']}'
            WHERE id_page='{$id_page}'";
	$db->execute($query);	
} 

// Si il s'agit d'un nouveau graph
else
{
	// On va chercher le prochain id_page
	// 30/01/2009 GHX : nouveau formatage pour l'ID, ce n'est plus un MAX+1
	// 02/02/2009 GHX : appel à la fonction qui génére un unique ID
	$id_page = generateUniqId( 'sys_pauto_page_name' );
		
	// ... dans sys_pauto_page_name
	if (($client_type == 'client') && ($user_info['profile_type'] == 'user')) {
		$temp_id_user = "'".$user_info['id_user']."'";
	} else {
		$temp_id_user = 'NULL';
	}
	$query = "INSERT INTO sys_pauto_page_name (id_page,page_name,droit,page_type,id_user,share_it)
                VALUES ('{$id_page}','{$gtmName}','{$client_type}','gtm',{$temp_id_user},0)";
	$db->execute($query);

	// ... dans graph_info
	// 28/10/2009 BBX : si $_POST['gis'] n'existe pas, on force à 0. BZ 12357
	$query = "INSERT INTO graph_information
            (id_page,definition,troubleshooting,scale,object_type,gis,position_legende,graph_width,
            graph_height,ordonnee_left_name,ordonnee_right_name,default_orderby,default_asc_desc)
		VALUES
			('$id_page',
			'{$gtmDefinition}',
			'{$gtmTrSh}',
			'{$gtmScale}',
			'{$gtmObjectType}',
			'{$gtmGis}',
			'{$gtmPosLegend}',
			'{$gtmWidth}',
			'{$gtmHeight}',
			'{$gtmOrdLeftName}',
			'{$gtmOrdRightName}',
			'{$gtmDefOrderBy}',
			{$gtmDefAscDesc})";
	$db->execute($query);	
}

// On renvoie vers la page d'edition du graph.
header( "Location: {$niveau0}builder/graph.php?id_page={$id_page}" );
