<?php
/**
 * @cb5100@
 * 06/07/2010 - Copyright Astellia
 *
 *  - 06/07/2010 OJT : Réécriture du script
 *
 */
    session_start();
    include_once dirname(__FILE__).'/../../../../php/environnement_liens.php';
    include_once REP_PHYSIQUE_NIVEAU_0.'class/KpiFormula.class.php';

    // Test si tous les paramètres GET ont été envoyés
    if( !isset( $_GET['product'] ) || !isset( $_GET['id'] ) || !isset( $_GET['on_off'] ) || !isset( $_GET['family'] ) ){
        die( 'Error during KPI activation, missing arguments' );
    }

    /** @var DataBaseConnection $database */
    $database = DataBase::getConnection( $_GET['product'] );

    /** @var String $idLigne */
    $idLigne = trim ( $_GET['id'] );

    /** @var Integer $onOff */
    $onOff = intval( $_GET['on_off'] );

    // Si le KPI est à activer, il y a quelques tests a effectuer avant
    if( $onOff === 1 )
    {
        $maxKpi = intval( get_sys_global_parameters( 'maximum_mapped_counters', 1570, $_GET['product'] ) );

        // Vérifier que le nombre max de KPI n'est pas atteint
        $nbKpi = KpiModel::getNbActiveKpi( $database, $_GET['family'] );
        if( $nbKpi >= $maxKpi ){
           die( __T( 'A_E_CONTEXT_MOUNT_LIMIT_NB_COUNTER_EXCEEDED', $maxKpi, 'kpi', $_GET['family'], $nbKpi ) );
        }
       
        // Vérifier si le KPI utilise une fonction ErlangB
        // 16:38 14/10/2010 SCT : BZ 18518 => Vérification de la formule KPI avant activation depuis KPI_Builder
        // DEBUT BUG 18518
        $kpiFormula = KpiModel::getFormulaKpi($database, $idLigne);
        if( ( KpiFormula::isFormulaUsingErlangB( $kpiFormula ) ) && ( KpiFormula::getNbKpiUsingErlangB( $database ) >= get_sys_global_parameters( 'max_kpi_using_erlang', 1, $_GET['product'] ) ) )
        {
            // On ne peut pas l'activer
            die( 'Error during KPI activation. The maximum number of Kpi using the erlang function in their formula has been reached.' );
        }
        // recherche de la validation de la formule
        if(!KpiFormula::checkFormula($database, $kpiFormula, $_GET['family'], $_GET['product']))
        {
            // On ne peut pas l'activer
            die( 'Error during KPI activation. Kpi formula is not correct.' );
        }
    }
    else // cas de la désactivation
    {
        $leKpi = new KpiModel();
        $kpiName = $leKpi->getNameFromId($idLigne, $database);
        if(KpiModel::isKpiLockedBh($_GET['product'], $kpiName, $_GET['family']))
            die('Error during KPI desactivation. Kpi is used as Busy Hour for this family.');
        // FIN BUG 18518
    }
    // On exécute la requête final
	$database->execute( 'UPDATE sys_definition_kpi SET on_off='.$onOff.' WHERE id_ligne=\''.$idLigne.'\'' );
?>
