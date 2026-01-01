<?php
include_once dirname( __FILE__ ).'/../php/xenv.inc';

if(!empty($timezonesvr))
    date_default_timezone_set($timezonesvr);

define('NIVEAU_0', $niveau0);
define('REP_PHYSIQUE_NIVEAU_0', $repertoire_physique_niveau0);
define('PSQL_DIR', $psqldir);
define('DOC_USER', '/doc/Trending&Aggregation_UserManual.pdf');
define('DOC_ADMIN', '/doc/Trending&Aggregation_AdminManual.pdf');

include_once REP_PHYSIQUE_NIVEAU_0.'/php/edw_function.php';

// Database
include_once(REP_PHYSIQUE_NIVEAU_0.'class/DataBaseConnection.class.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'class/Database.class.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'class/SSHConnection.class.php');
include_once REP_PHYSIQUE_NIVEAU_0.'partitioning/class/PartitioningActivation.class.php';

// Messages
$A_SETUP_PARTITIONING_THANK_YOU                 = __T('A_SETUP_PARTITIONING_THANK_YOU');
$A_SETUP_PARTITIONING_NO_PARTITIONING_PROCESS   = __T('A_SETUP_PARTITIONING_NO_PARTITIONING_PROCESS');
$A_SETUP_PARTITIONING_TIME_LEFT                 = __T('A_SETUP_PARTITIONING_TIME_LEFT');
$A_SETUP_PARTITIONING_SUCCESSFULLY_PARTITIONED  = __T('A_SETUP_PARTITIONING_SUCCESSFULLY_PARTITIONED');
$A_SETUP_PARTITIONING_PARTITIONING_FAILED       = __T('A_SETUP_PARTITIONING_PARTITIONING_FAILED');
$A_SETUP_PARTITIONING_PRODUCTS_BEING_PARTITIONED= __T('A_SETUP_PARTITIONING_PRODUCTS_BEING_PARTITIONED');

// Autoload
if ( !function_exists('__autoload') )
{
    function __autoload($class_name)
    {
        switch($class_name)
        {
            // Classe Date
            case 'Date':
                include_once REP_PHYSIQUE_NIVEAU_0.'class/'.$class_name . '.class.php';
            break;
            // Par défaut, appel des modèles
            default:
                include_once REP_PHYSIQUE_NIVEAU_0.'models/'.$class_name . '.class.php';
            break;
        }
    }
}

// Session
session_start();

// Partitioning information
$databaseMaster         = Database::getConnection();
$partitioningActivation = new PartitioningActivation($databaseMaster);

// Checking processus completion
if(!$partitioningActivation->checkPartitioningFile()) {
    header('Location: '.$niveau0.'index.php');
    return;
}

// Partitioning information
$productInfos           = $partitioningActivation->parsePartitioningFile();
$monitoring             = $partitioningActivation->monitorPartitioning($productInfos);
$startTime              = $partitioningActivation->getStartTime($productInfos);
$currentTime            = time();
$timeElapsed            = $currentTime - $startTime;
$nbExpectedSucces       = count($productInfos);

// Affichage
include REP_PHYSIQUE_NIVEAU_0.'php/header.php';
?>
<div id="container">
    <div style="padding:10px;font-family:Arial;font-size:10pt;font-weight:bold">
        <div style="text-align:center">
            <img src="<?=$niveau0?>/images/client/logo_client_moyen.png" alt="Trending&Aggreation" />
        </div>
        <br /><br />
        <div class="tabPrincipal" style="width:800px;text-align:left;padding:10px;">
        <?=$A_SETUP_PARTITIONING_PRODUCTS_BEING_PARTITIONED?><br />
<?php
$timeTotal = array();
$nbSuccesProduct = 0;
$refresh = 30000;
if(is_array($monitoring))
{
    foreach($monitoring as $productLabel => $percentage)
    {
        if($percentage === "true") {
            $color = "green";
            $nbSuccesProduct++;
            $percentageText = ' - '.htmlentities($productLabel).' ('.$A_SETUP_PARTITIONING_SUCCESSFULLY_PARTITIONED.')';
            // 30/06/2011 BBX
            // Lorsque le partitioning est terminé, on redirige au bout d'une seconde
            // BZ 22791
            $refresh = 1000;
        }
        elseif($percentage === "false") {
            $color = "red";
            $percentageText = ' - '.htmlentities($productLabel).' ('.$A_SETUP_PARTITIONING_PARTITIONING_FAILED.')';
            // Fetching log files
            $partitioningActivation->fetchLogFiles($productInfos);
            // Generating link
            $productModel = new ProductModel(ProductModel::getProductFromLabel($productLabel));
            $productValues = $productModel->getValues();
            $mailReply = get_sys_global_parameters('mail_reply');
            $percentageText .= __T('A_SETUP_PARTITIONING_CONTACT_SUPPORT',NIVEAU_0.'upload/'.$productValues['sdp_directory'],$mailReply);
        }
        else {
            $color = "#898989";
            $percentageText = ' - '.htmlentities($productLabel).' ('.$percentage.' %)';
            $timeTotal[] = ($percentage == 0) ? 0 : (($timeElapsed * 100) / $percentage);
        }
        echo '<div style="color:'.$color.';padding-left:10px;">'.$percentageText.'</div>';
    }
}

// Checking status
if($nbExpectedSucces == $nbSuccesProduct) {
    $partitioningActivation->deletePartitioningFile();
}

// Time left
$nbSecondsLeft  = count($timeTotal) > 0 ? (max($timeTotal)-$timeElapsed) : 0;
$nbMinutesLeft  = $nbSecondsLeft/60;
$nbHoursLeft    = $nbMinutesLeft/60;
$nbDaysLeft     = $nbHoursLeft/24;
$nbMinutesLeft  = floor($nbMinutesLeft%60);
$nbHoursLeft    = floor($nbHoursLeft%24);
$nbDaysLeft     = floor($nbDaysLeft);
$nbSecondsLeft  = floor($nbSecondsLeft%60);
?>
        <br /><br />
<?php
if(count($timeTotal) == 0)
    echo $A_SETUP_PARTITIONING_NO_PARTITIONING_PROCESS;
else
    echo $A_SETUP_PARTITIONING_TIME_LEFT;
if($nbDaysLeft > 0) echo " $nbDaysLeft day(s),";
if($nbHoursLeft > 0) echo " $nbHoursLeft"."h";
if($nbMinutesLeft > 0) echo " $nbMinutesLeft"."m";
if($nbSecondsLeft > 0) echo " $nbSecondsLeft"."s";
?>
        <br /><br />
        <?=$A_SETUP_PARTITIONING_THANK_YOU?>
        <script type="text/javascript">
            setTimeout("location.reload(true)",<?=$refresh?>);
        </script>
        </div>
    </div>
</div>
</body>
</html>
