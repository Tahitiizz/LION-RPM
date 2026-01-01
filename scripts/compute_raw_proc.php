<?php
/**
 * @file compute_raw_proc.php
 * @brief Ce script lance les différents traitements du compute Raw pour une seule famille
 *
 * $Author: b.berteaux $
 * $Date: 2011-06-17 18:21:23 +0200 (ven., 17 juin 2011) $
 * $Rev: 27981 $
 */
    include dirname( __FILE__ ).'/../php/environnement_liens.php';
    include_once(REP_PHYSIQUE_NIVEAU_0.'php/edw_function_family.php');
    include_once(REP_PHYSIQUE_NIVEAU_0.'php/postgres_functions.php');
    include_once(REP_PHYSIQUE_NIVEAU_0.'php/bh_functions.php');	//contient des fonctions necessaires au calcul de la BH
    include_once(REP_PHYSIQUE_NIVEAU_0.'php/deploy_and_compute_functions.php');
    include_once(REP_PHYSIQUE_NIVEAU_0.'class/compute.class.php');
    include_once(REP_PHYSIQUE_NIVEAU_0.'class/computeRaw.class.php');
    include_once(REP_PHYSIQUE_NIVEAU_0.'class/Partition.class.php');
    include_once(REP_PHYSIQUE_NIVEAU_0.'class/Partitionning.class.php');

    ob_start(); // Enclanchement de la temporisation de sortie (évite le mélange entre les famille)

    // instanciation de l'objet
    // 19/11/2008 BBX : On ne passe plus $database_connection en paramètre car la classe utilise désormais la classe DatabaseConnection
    $compute = new computeRaw();

    // vérification du type de compute pour désactivation des niveau en dehors du niveau minimum
    //if($compute->getComputeMode() == 'hourly')
            //$compute->updateTimeAgregationToCompute(0);

    // récupération des heures ou des jours à traiter
    $compute->searchComputePeriod();
    // recherche des familles depuis la bdd (famille activée)
    $compute->searchAllFamilies();
    // recherche l'ensemble des tables de données edw_* et w_edw_* qui existent en base.
    $compute->searchExistingTableSource();

    $listGroupTable = $compute->getAllFamilies(); 
    $listComputePeriod = $compute->getComputePeriod();

    $id_group_table = $env_id_group_table; // récupération de la variable d'environnement
    $tableau_info_famille = $listGroupTable[$env_id_group_table];

    $title = 'Traitement de la famille : '.$tableau_info_famille['family']. " (COUNTER)";
    displayInDemon($title,'title');
    displayInDemon(date('r'));

    // initialisation de la variable id_group_table
    $compute->setIdGroupTable($id_group_table );

    // recherche des informations sur la famille
    $compute->searchFamilyInfo();

    // récupération et affichage des informations de la famille
    $famille_info = $compute->getFamilyInfo();

    // récupération des compteurs RAW liés à la famille
    $compute->getCounters();

    // récupération des éléments réseaux s'agrégeant sur eux-mêmes
    $compute->searchSelfAgregationLevel();

    // recherche des tables cibles et sources
    $compute->createTargetTables();

    // Si on a switché de hourly vers daily, on doit récupérer la liste de toutes les heures qui ont déjà été insérées.
    if ( get_sys_global_parameters('compute_switch') == 'hourly' )
            $compute->reloadHourHistory();

    foreach ( $listComputePeriod as $period )
    {
        echo "<div style='font-family:verdana;font-weight:bold;margin:2px;'>Traitement du time : $period</div>";

        // maj 16/07/2008 BBX : Mise à jour de l'offset du compute. BZ7078
        $compute->updateComputeOffset();

        $compute->createSourceTables($period);

        // 27/05/2011 BBX -PARTITIONING-
        // La la BDD n'est pas partitionnée, on éxécute les DELETE
        if(!$compute->isPartitioned())
            $compute->createRequestDeleteTables($period);

        // Préparation des requêtes
        $compute->prepareRequest($period);

        // Exécute les requêtes
        $tab_list_queries = $compute->executeRequest();

        // affichage des requêtes dans le démon.
        $compute->displayQueries($tab_list_queries);
    }

    ob_end_flush(); // le process est terminé, on affiche tout