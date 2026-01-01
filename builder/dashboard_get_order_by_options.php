<?php
/**
*	@cb4100@
*	- Creation SLC	 05/11/2008
*
*	Cette page se fait inclure par dashboard_get_properties.php ET appeler en ajax
*	Elle génère les options du menu "Default order by" pour le selecteur du dashboard
*
*	30/01/2009 GHX
*		-  modification des requêtes SQL pour mettre id_page entre cote  [REFONTE CONTEXTE]
*	14/08/2009 GHX
*		- (Evo) Modification pour prendre en compte le faite que dans un graphe on peut avoir plusieurs fois le meme KPI [code+label] identique et qu'il est considere comme un seul
*
*/

$intranet_top_no_echo = true;
include_once('common.inc.php');


/**
*	Cette fonction retourne un tableau de tous les raw / kpi qui composent les graphs, qui composent le dashboard spécifié
*
*	rq: on peut avoir des doublons de raw/kpi si le même kpi est utilisé dans plusieurs graphs du dashboard courrant.
*
*	@param int	$id_page est l'id_page du dashboard dont on cherche les composantes
*	@return array	retourne un tableau $data[sys_pauto_config.id du raw/kpi] = "label du raw/kpi dans le GTM qui le contient";
*/
function get_data_source_of_dashboard($id_page) {
	global $db;
	
	// Liste des datas (raw/kpi) contenues dans les graphs du dashboard.
	// graph_data id_data_value data_type > id@type
	// 14:55 14/08/2009 GHX
	// Modification de la requete SQL
	$query = " --- on va chercher toutes les datas (raw/kpi) des graphs du dashboard
		SELECT 
			SPC.id,
			GD.data_legend,
			SPC.id_elem,
			SPC.id_product,
			SPC.class_object,
			SPC.id_page
		FROM sys_pauto_config SPC
			JOIN graph_data AS GD ON SPC.id = GD.id_data
		WHERE SPC.id_page IN
			-- liste de tous les ID des graphs du dashboard $id_page :
			(SELECT id_elem FROM sys_pauto_config WHERE id_page='$id_page')
		ORDER BY id_page, id_product;
	";
	$data = $db->getall($query);
	
	if ($data)
	{
		// >>>>>>>>>>
		// 15:08 14/08/2009 GHX
		// Modification de toute la partie pour pouvoir prendre en compte la liste des RAW ou KPI identique code/legende

	
		// Création des requetes SQL qui permettront de récupérer le nom d'un raw ou d'un kpi en fonction de son identifiant
		$query_kpi = "SELECT lower(kpi_name) AS name FROM sys_definition_kpi WHERE id_ligne = '%s'";
		$query_counter = "SELECT lower(edw_field_name) AS name FROM sys_field_reference WHERE id_ligne = '%s'";

		
		$results = $data;
		// Initalisation des tableaus qui contiendront soit la liste des raw soit la liste des kpi
		$kpi = array();
		$counter = array();
		$currentElem = null;
		$id_product = null;
		$id_graph = null;
		$db_temp = null;
		$data = array();
		$tabSortType = array();
		$tabSortLabel = array();
		// On boucle sur la liste des éléments du graphe
		foreach ( $results as $row )
		{
			if ( $id_graph != $row['id_page'] )
			{
				if ( !is_null($id_graph) )
				{
					$tmpData = array();
					if ( count($kpi) > 0 )
					{
						foreach ( $kpi as $index => $elem )
						{
							$tmpData[] = array('id' => $elem[0]['id'], 'label' => $elem[0]['data_legend']. ' [KPI]', 'type' => 'kpi');
							$tabSortType[] = 'kpi';
							$tabSortLabel[] = $elem[0]['data_legend']. ' [KPI]';
						}
					}
					if ( count($counter) > 0 )
					{
						foreach ( $counter as $index => $elem )
						{
							$tmpData[] = array('id' => $elem[0]['id'], 'label' => $elem[0]['data_legend']. ' [COUNTER]', 'type' => 'counter');
							$tabSortType[] = 'counter';
							$tabSortLabel[] = $elem[0]['data_legend']. ' [COUNTER]';
						}
					}
					$data = array_merge($data, $tmpData);
				}
				
				$id_graph = $row['id_page'];
				$kpi = array();
				$counter = array();
			}
			
			// Initilisation d'une connexion sur la bonne base de données
			if ( $id_product != $row['id_product'] )
			{
				$id_product = $row['id_product'];
                                // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::get Connection()
				$db_temp = Database::getConnection($id_product);
			}
			// Récupère le nom de l'élément ...
			$name = $db_temp->getOne(sprintf(${'query_'.$row['class_object']}, $row['id_elem']));
			// ... l'ajout dans le tableau 
			$row['name'] = $name;
			// Ajout l'élément dans le tableau appropié en fonction de son type
			${$row['class_object']}[$name.$row['data_legend']][] = $row;
		}
		
		// Pour le dernier graphe du dashboard
		$tmpData = array();
		if ( count($kpi) > 0 )
		{
			foreach ( $kpi as $index => $elem )
			{
				$tmpData[] = array('id' => $elem[0]['id'], 'label' => $elem[0]['data_legend']. ' [KPI]', 'type' => 'kpi');
				$tabSortType[] = 'kpi';
				$tabSortLabel[] = $elem[0]['data_legend']. ' [KPI]';
			}
		}
		if ( count($counter) > 0 )
		{
			foreach ( $counter as $index => $elem )
			{
				$tmpData[] = array('id' => $elem[0]['id'], 'label' => $elem[0]['data_legend']. ' [COUNTER]', 'type' => 'counter');
				$tabSortType[] = 'counter';
				$tabSortLabel[] = $elem[0]['data_legend']. ' [COUNTER]';
			}
		}
		$data = array_merge($data, $tmpData);
		
		array_multisort($tabSortType, SORT_ASC, $tabSortLabel, SORT_ASC, $data);
		// <<<<<<<<<<
		
		// on raccourci les labels trop longs :
		foreach ($data as &$d)
		{
			if (strlen($d['label']) > 60)
				$d['label'] = substr($d['label'],0,60).'...';
		}
	}
	return $data;
}


/**
*	Cette fonction retourne les <option>s du menu "default order by" du dashboard
*
*	@param int	$id_page est l'id_page du dashboard en cours
*	@param int	$sdd_sort_by_id est l'id du raw/kpi actuellement utilisé par le dashboard pour le "default order by"
*	@return string	retourne les <option>s du <select>
*/
function sdd_sort_by_id_options($id_page,$sdd_sort_by_id) {
	$data = get_data_source_of_dashboard($id_page);
	$html = '';
	if ($data) {
		foreach ($data as $d) {
			$html .= "<option value='{$d['id']}' ".(($d['id']==$sdd_sort_by_id)?"selected='selected'":'').">{$d['label']}</option>";
		}
	} else {
		$html .= "<option>".__T('G_GDR_BUILDER_YOU_NEED_TO_ADD_A_GTM_TO_YOUR_DASHBOARD')."</option>";
	}
	return $html;
}


// cas où on a PAS inclus la page, (on est donc dans le cas ajax)
if ($_POST['id_page']) {
	
	// 10:00 30/01/2009 GHX
	// Suppression du formatage en INT
	$id_page				= $_POST['id_page'];
	$id_rawkpi_selected		= $_POST['id_rawkpi_selected'];
	
	$data = get_data_source_of_dashboard($id_page);
	$txt = '';
	if ($data)
		foreach ($data as $d)
			$txt .= $d['id'].'|sep1|'.$d['label'].'|sep2|';
	
	$txt = substr($txt,0,-6);
	echo $txt;

} else {
	// cas inclusion depuis la page dashboard_get_properties.php
	$id_rawkpi_selected = $dash['sdd_sort_by_id'];
	echo sdd_sort_by_id_options($id_page,$id_rawkpi_selected);
}


?>
