<?php
/*
 * 17/10/2011 MMT DE Bypass temporel deplacement du test sur compute mode dans la fonction updateTimeAgregationToCompute
 * 27/10/2011 MMT Bz 24440 appel de updateTimeAgregationToBypassSourceTable sur le model de updateTimeAgregationToCompute pour RAZ apres compute
 *
*	@cb41000@
*
*	14-11-2008 - Copyright Astellia
*
*	Ce script lance les différents traitements du compute Kpi Corporate
*
*/
include dirname( __FILE__ ).'/../php/environnement_liens.php';
include_once(REP_PHYSIQUE_NIVEAU_0.'php/edw_function_family.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'php/postgres_functions.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'php/bh_functions.php');	//contient des fonctions necessaires au calcul de la BH
include_once(REP_PHYSIQUE_NIVEAU_0.'php/deploy_and_compute_functions.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'class/compute.class.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'class/computeKpi.class.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'class/deploy.class.php');

$d = microtime(true);

// instanciation de l'objet
// 19/11/2008 BBX : On ne passe plus $database_connection en paramètre car la classe utilise désormais la classe DatabaseConnection
$compute = new computeKpi();

// vérification du type de compute pour désactivation des niveau en dehors du niveau minimum
//17/10/2011 MMT DE Bypass temporel deplacement du test sur compute mode dans la fonction updateTimeAgregationToCompute
$compute->updateTimeAgregationToCompute(0);

// récupération des heures ou des jours à traiter
$compute->searchComputePeriod();
// recherche des familles depuis la bdd (famille activée)
$compute->searchAllFamilies();
// recherche l'ensemble des tables de données edw_* et w_edw_* qui existent en base.
$compute->searchExistingTableSource();

//27/10/2011 MMT Bz 24440 appel de updateTimeAgregationToBypassSourceTable sur le model de updateTimeAgregationToCompute pour RAZ apres compute
$compute->updateTimeAgregationToBypassSourceTable(0);

$listGroupTable = $compute->getAllFamilies(); 

// >>>>>>>>>>
// 14:16 25/03/2008 GHX
// modif pour le corporate, on fait un compute sur les 3 derniers jours systématiquement
//$listComputePeriod = $compute->getComputePeriod();
$offset_day = get_sys_global_parameters('offset_day');
$listComputePeriod = array();
for ( $i = 0; $i < 3; $i++ )
{
	$listComputePeriod[] = getDay($offset_day+$i);
}
// <<<<<<<<< 

// Pour débugage : restriction pour test à une seule famille [à conserver]
/*
unset($listGroupTable);
$listGroupTable[1]['family'] = 'ept';
$listGroupTable[1]['edw_group_table'] = 'edw_iu_ept_axe1';
//*/
/*
unset($listGroupTable);
$listGroupTable[2]['family'] = 'paglac';
$listGroupTable[2]['edw_group_table'] = 'edw_iu_paglac_axe1';
//*/
//__debug($listGroupTable,'$listGroupTable');

// Permet de stocker les info de chaque famille.
$listeFamilleInfo = array();
// Permet de stocker les éléments qui s'agrègent sur eux-même pôur chaque famille.
$listeSelfAgregationLevel = array();

// Boucle sur les id_group_table des familles à traiter
foreach ( $listGroupTable AS $id_group_table => $tableau_info_famille )
{
	$title = 'Traitement de la famille : '.$tableau_info_famille['family']. " (KPI)";
	displayInDemon($title,'title');
	displayInDemon(date('r'));
	
	// initialisation de la variable id_group_table
	$compute->setIdGroupTable($id_group_table, $tableau_info_famille); 
	
	// recherche des informations sur la famille
	$compute->searchFamilyInfo();
	
	// récupération et affichage des informations de la famille
	$famille_info = $compute->getFamilyInfo();
	$listeFamilleInfo[$id_group_table] = $famille_info;
	
	// récupération des KPIs liés à la famille
	$compute->getCounters();
	
	// récupération des éléments réseaux s'agrégeant sur eux-mêmes
	$compute->searchSelfAgregationLevel();
	$listeSelfAgregationLevel[$id_group_table] = $compute->getSelfAgregationLevel();
	
	// recherche des tables cibles et sources
	$compute->createTargetTables();	
	foreach ( $listComputePeriod as $period )
	{
		echo "<div style='font-family:verdana;font-weight:bold;margin:2px;'>Traitement du time : $period</div>";
		
		$compute->createSourceTables($period);
		
		$compute->createRequestDeleteTables($period);

		// Préparation des requêtes
		$compute->prepareRequest($period); 
		
		// Exécute les requêtes
		$tab_list_queries = $compute->executeRequest();
		
		// affichage des requêtes dans le démon.
		$compute->displayQueries($tab_list_queries);
	}
	
}

//27/10/2011 MMT Bz 24440 appel de updateTimeAgregationToBypassSourceTable sur le model de updateTimeAgregationToCompute pour RAZ apres compute
$compute->updateTimeAgregationToBypassSourceTable(1);

// vérification du type de compute pour désactivation des niveau en dehors du niveau minimum
//17/10/2011 MMT DE Bypass temporel deplacement du test sur compute mode dans la fonction updateTimeAgregationToCompute
$compute->updateTimeAgregationToCompute(1);
	
// nettoyage des tables dans la fonction after process ( suppression des tables w_edw*)
$compute->afterProcess($listeFamilleInfo, $listComputePeriod, $listeSelfAgregationLevel);

$f = microtime(true);
displayInDemon('<b>Durée compute KPI : '.round($f-$d, 6).' secondes.</b>');

?>
