<?php
/**
 *  Script permettant de test la récupération des indicateurs de santé
 *
 * $Author: o.jousset $
 * $Date: 2010-05-26 10:50:51 +0200 (mer., 26 mai 2010) $
 * $Revision: 26465 $
 */
    session_start();
    include_once( dirname(__FILE__).'/../../php/environnement_liens.php' );
    include_once( REP_PHYSIQUE_NIVEAU_0.'class/HealthIndicator.class.php' );

    if( isset( $_SESSION['generationTimes'] ) === FALSE )
    {
        $_SESSION['generationTimes'] = array();
    }
    else
    {
        // ok
    }

    $start = microtime( true );
    $hi = new HealthIndicator( HI_CALL_MODE_IHM );
    $hi_perf_last_collect_duration = $hi->getLastCollectDuration();
    $hi_perf_last_retrieve_duration = $hi->getLastRetrieveDuration();
    $hi_perf_last_compute_raw_duration = $hi->getLastComputeRawDuration();
    $hi_perf_last_compute_kpi_duration = $hi->getLastComputeKpiDuration();
    $hi_perf_last_compute_all_duration = $hi->getLastComputeAllDuration();
    $hi_perf_nb_wait_file = $hi->getNbWaitFiles();
    $hi_perf_family_history = $hi->getFamilyHistory();
    $hi_perf_nb_last_day_collected_files = $hi->getNbLastDayCollectedFiles();
    $hi_perf_nb_raw = $hi->getNbRaw();
    $hi_perf_nb_mapped_raw = $hi->getNbMappedRaw();
    $hi_perf_nb_custom_kpi = $hi->getNbCustomKpi();
    $hi_perf_nb_client_kpi = $hi->getNbClientKpi();
    $perfDuration = round( ( microtime( true ) - $start ), 2 );
    $hi_alarms_nb_static_alarms = $hi->getNbStaticAlarms();
    $hi_alarms_nb_dyn_alarms = $hi->getNbDynAlarms();
    $hi_alarms_nb_tw_alarms = $hi->getNbTwAlarms();
    $hi_alarms_last_compute_alarms_duration = $hi->getLastComputeAlarmsDuration();
    $alarmsDuration = round( ( microtime( true ) - $start ) - $perfDuration, 2 );
    $hi_dataexports_nb_data_exports = $hi->getNbDataExports();
    $hi_dataexports_last_generation_duration = $hi->getLastDataExportsGenerationDuration();
    $deDuration = round( ( microtime( true ) - $start ) - $perfDuration - $alarmsDuration, 2 );
    $hi_topo_nb_ne = $hi->getNbNe();
    $hi_topo_nb_ne_first_axis = $hi->getNbFirstAxisNe();
    $hi_topo_nb_ne_third_axis = $hi->getNbThirdAxisNe();
    $topoDuration = round( ( microtime( true ) - $start ) - $perfDuration - $alarmsDuration - $deDuration, 2 );
    $hi_storage_disc_space = $hi->getDiscSpace();
    $hi_others_nb_accounts = $hi->getNbAccounts();
    $hi_others_nb_connected_users_last_day = $hi->getNbConnectedUsersLastDay();
    $hi_others_nb_pages_last_day = $hi->getNbPagesLastDay();
    $othersDuration = round( ( microtime( true ) - $start ) - $perfDuration - $alarmsDuration - $deDuration - $topoDuration, 2 );
    $duration = round( ( microtime( true ) - $start ), 2 );
    $durationStr = 'Generate in <b>'.$duration.' s</b>
                        on '.date( 'd/m/Y, H:i:s' ).'
                        (average <b>'.( round( array_sum( $_SESSION['generationTimes'] ) / count( $_SESSION['generationTimes'] ), 3 ) ) .' s</b>
                        on <b>'.count( $_SESSION['generationTimes'] ).' iterations</b>,
                        min '.min( $_SESSION['generationTimes'] ).' s,
                        max '.max( $_SESSION['generationTimes'] ).'s )';
    array_push( $_SESSION['generationTimes'], $duration );
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <title>Test des indicateurs de sante T&amp;A</title>
        <meta http-equiv="content-type" content="text/html;charset=utf-8" />
        <meta http-equiv="Content-Style-Type" content="text/css" />
        <meta http-equiv="refresh" content="10;url=index.php" />
        <style type="text/css">
            html,body{font : normal 11px Verdana, Arial, sans-serif;}
            h1{margin:0px;}
            div#hiInfo{margin-bottom: 5px;}
            table{
                margin: 5px;
                font-variant: small-caps;
                padding:0px;
                border: 3px solid gray;
            }
            tr{
                padding:0px;
                margin:0px;
            }
            td{
                margin:0px 0px 0px 0px;
                padding:5px;
                border-bottom:1px dotted #DDDDDD;
            }
            td.hi{
                text-align:right;
                font-weight:bold;
                border-right:1px solid gray !important;
            }
            td.tdHeader{
                font-weight: bold;
                text-align: center;
                border-bottom:1px solid gray !important;
                background-color: #DDDDDD;
            }
        </style>
    </head>
    <body>
        <h1>Health Indicator</h1>
        <div id="hiInfo">
            <?php echo $durationStr; ?>
        </div>
        <div style="float:left;">
            <table cellpadding="0" cellspacing="0">
                <tr>
                    <td colspan="2" class="tdHeader">
                        Hi Perf (<?php echo $perfDuration; ?> s)
                    </td>
                </tr>
                <tr>
                    <td class="hi">last_collect_duration</td>
                    <td><?php echo $hi_perf_last_collect_duration; ?></td>
                </tr>
                <tr>
                    <td class="hi">last_retrieve_duration</td>
                    <td><?php echo $hi_perf_last_retrieve_duration; ?></td>
                </tr>
                <tr>
                    <td class="hi">last_compute_raw_duration</td>
                    <td><?php echo $hi_perf_last_compute_raw_duration; ?></td>
                </tr>
                <tr>
                    <td class="hi">last_compute_kpi_duration</td>
                    <td><?php echo $hi_perf_last_compute_kpi_duration; ?></td>
                </tr>
                <tr>
                    <td class="hi">last_compute_all_duration</td>
                    <td><?php echo $hi_perf_last_compute_all_duration; ?></td>
                </tr>
                <tr>
                    <td class="hi">nb_wait_file</td>
                    <td><?php echo $hi_perf_nb_wait_file; ?></td>
                </tr>
                <tr>
                    <td class="hi">family_history</td>
                    <td><?php print_array( $hi_perf_family_history ); ?></td>
                </tr>
                <tr>
                    <td class="hi">nb_last_day_collected_files</td>
                    <td><?php echo $hi_perf_nb_last_day_collected_files; ?></td>
                </tr>
                <tr>
                    <td class="hi">nb_raw</td>
                    <td><?php print_array( $hi_perf_nb_raw ); ?></td>
                </tr>
                <tr>
                    <td class="hi">nb_mapped_raw</td>
                    <td><?php print_array( $hi_perf_nb_mapped_raw ); ?></td>
                </tr>
                <tr>
                    <td class="hi">nb_custom_kpi</td>
                    <td><?php print_array( $hi_perf_nb_custom_kpi ); ?></td>
                </tr>
                <tr>
                    <td class="hi">nb_client_kpi</td>
                    <td><?php print_array( $hi_perf_nb_client_kpi ); ?></td>
                </tr>
            </table>
        </div>
        <div style="float:left;">
            <table cellpadding="0" cellspacing="0">
                <tr>
                    <td colspan="2" class="tdHeader">
                        Hi Alarms (<?php echo $alarmsDuration; ?> s)
                    </td>
                </tr>
                <tr>
                    <td class="hi">nb_static_alarms</td>
                    <td><?php echo $hi_alarms_nb_static_alarms; ?></td>
                </tr>
                <tr>
                    <td class="hi">nb_dyn_alarms</td>
                    <td><?php echo $hi_alarms_nb_dyn_alarms; ?></td>
                </tr>
                <tr>
                    <td class="hi">nb_tw_alarms</td>
                    <td><?php echo $hi_alarms_nb_dyn_alarms; ?></td>
                </tr>
                <tr>
                    <td class="hi">last_compute_alarms_duration</td>
                    <td><?php echo print_array( $hi_alarms_last_compute_alarms_duration ); ?></td>
                </tr>
            </table>
            <table cellpadding="0" cellspacing="0">
                <tr>
                    <td colspan="2" class="tdHeader">
                        Hi Data Exports (<?php echo $deDuration; ?> s)
                    </td>
                </tr>
                <tr>
                    <td class="hi">nb_data_exports</td>
                    <td><?php echo $hi_dataexports_nb_data_exports; ?></td>
                </tr>
                <tr>
                    <td class="hi">last_generation_duration</td>
                    <td><?php echo print_array( $hi_dataexports_last_generation_duration ); ?></td>
                </tr>
            </table>
            <table cellpadding="0" cellspacing="0">
                <tr>
                    <td colspan="2" class="tdHeader">
                        Hi Topo (<?php echo $topoDuration; ?> s)
                    </td>
                </tr>
                <tr>
                    <td class="hi">nb_ne</td>
                    <td><?php echo $hi_topo_nb_ne; ?></td>
                </tr>
                <tr>
                    <td class="hi">nb_ne_first_axis</td>
                    <td><?php echo $hi_topo_nb_ne_first_axis; ?></td>
                </tr>
                <tr>
                    <td class="hi">nb_ne_third_axis</td>
                    <td><?php echo $hi_topo_nb_ne_third_axis; ?></td>
                </tr>
            </table>
            <table cellpadding="0" cellspacing="0">
                <tr>
                    <td colspan="2" class="tdHeader">
                        Hi Others (<?php echo $othersDuration; ?> s)
                    </td>
                </tr>
                <tr>
                    <td class="hi">disc_space</td>
                    <td><?php echo print_array( $hi_storage_disc_space ); ?></td>
                </tr>
                <tr>
                    <td class="hi">nb_accounts</td>
                    <td><?php echo $hi_others_nb_accounts; ?></td>
                </tr>
                <tr>
                    <td class="hi">nb_connected_users_last_day</td>
                    <td><?php echo $hi_others_nb_connected_users_last_day; ?></td>
                </tr>
                <tr>
                    <td class="hi">nb_pages_last_day</td>
                    <td><?php echo $hi_others_nb_pages_last_day; ?></td>
                </tr>
            </table>
        </div>
    </body>
</html>
<?php
    function print_array( $a )
    {
        foreach( $a as $b )
        {
            echo $b."<br />";
        }
    }