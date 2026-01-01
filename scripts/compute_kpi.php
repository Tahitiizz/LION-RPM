<?php
/*
* 17/10/2011 MMT DE Bypass temporel deplacement du test sur compute mode dans la fonction updateTimeAgregationToCompute
* 27/10/2011 MMT Bz 24440 appel de updateTimeAgregationToBypassSourceTable sur le model de updateTimeAgregationToCompute pour RAZ apres compute
 * 
*	@cb41000@
*
*	14-11-2008 - Copyright Astellia
*
*	Ce script lance les différents traitements du compute Kpi
*
*/
include dirname( __FILE__ ).'/../php/environnement_liens.php';
include_once(REP_PHYSIQUE_NIVEAU_0.'php/edw_function_family.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'php/postgres_functions.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'php/bh_functions.php');	//contient des fonctions necessaires au calcul de la BH
include_once(REP_PHYSIQUE_NIVEAU_0.'php/deploy_and_compute_functions.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'class/Partition.class.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'class/Partitionning.class.php');
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
$listComputePeriod = $compute->getComputePeriod();

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

/**
 * 20/05/2011 OJT/BBX
 * Gestion du compute en // par famille
 */

// Permet de stocker les info de chaque famille.
$listeFamilleInfo = array();
// Permet de stocker les éléments qui s'agrègent sur eux-même pôur chaque famille.
$listeSelfAgregationLevel = array();

$listHandle = array(); // Liste des handles de process créés

// Boucle sur les id_group_table des familles à traiter
foreach ( $listGroupTable AS $id_group_table => $tableau_info_famille )
{
    $descriptorspec = array();
    $pipes          = array();
    $env            = array( "env_id_group_table" => $id_group_table );
    displayInDemon( "Launch compute kpi process for group table $id_group_table" );
    $procFile = dirname( $PHP_SELF )."/compute_kpi_proc.php";
    $h = proc_open("php $procFile", $descriptorspec, $pipes, NULL, $env  );

    // Test si le process s'est bien lancé
    if( $h !== false )
    {
        $listHandle[]= $h;
    }
    else
    {
        displayInDemon( 'Error lors du lancement du group table '.$id_group_table, 'alert' );
    }
}
displayInDemon( 'All processes started, now check end execution every milliseconds...' );
$listPidExecuted = array();
$nbProcesses     = count( $listHandle );
while( count( $listPidExecuted ) !== $nbProcesses )
{
    foreach( $listHandle as $oneHandle )
    {
        $a = proc_get_status( $oneHandle ); // Lecture de l'état du process
        //
        // Si le process vient de se terminer
        if( $a['running'] == false && !in_array( $a['pid'], $listPidExecuted ) )
        {
            $listPidExecuted[] = intval( $a['pid'] );
            displayInDemon( "{$a['pid']} est terminee" );
        }
    }
    usleep( 1000 ); // On attend 1ms avant le prochain test
}
displayInDemon( 'Process execution ended' );

//27/10/2011 MMT Bz 24440 appel de updateTimeAgregationToBypassSourceTable sur le model de updateTimeAgregationToCompute pour RAZ apres compute
$compute->updateTimeAgregationToBypassSourceTable(1);

// vérification du type de compute pour désactivation des niveau en dehors du niveau minimum
//17/10/2011 MMT DE Bypass temporel deplacement du test sur compute mode dans la fonction updateTimeAgregationToCompute
$compute->updateTimeAgregationToCompute(1);

$f = microtime( true );
displayInDemon( '<b>Durée compute KPI : '.round( $f - $d, 6 ).' secondes.</b>' );
sys_log_ast( 'Info', get_sys_global_parameters( 'system_name' ), __T( 'A_TRACELOG_MODULE_LABEL_COMPUTE' ), 'Compute KPI Duration : '.round( $f - $d , 1 ).' | '.$compute->getComputePeriods().' period(s)', 'support_1', '' );
?>
