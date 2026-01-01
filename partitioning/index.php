<?php
session_start();
include_once dirname(__FILE__).'/../php/environnement_liens.php';
include_once REP_PHYSIQUE_NIVEAU_0.'class/SSHConnection.class.php';

// Messages
$A_SETUP_PARTITIONING_TA_APPLICATION_LABEL               = __T('A_SETUP_PARTITIONING_TA_APPLICATION_LABEL');
$A_SETUP_PARTITIONING_CB_LABEL                           = __T('A_SETUP_PARTITIONING_CB_LABEL');
$A_SETUP_PARTITIONING_POSTGRESQL_LABEL                   = __T('A_SETUP_PARTITIONING_POSTGRESQL_LABEL');
$A_SETUP_PARTITIONING_PARTITIONING_LABEL                 = __T('A_SETUP_PARTITIONING_PARTITIONING_LABEL');
$A_SETUP_PARTITIONING_INFORMATION_LABEL                  = __T('A_SETUP_PARTITIONING_INFORMATION_LABEL');
$A_SETUP_PARTITIONING_PARTITIONED_PRODUCT                = __T('A_SETUP_PARTITIONING_PARTITIONED_PRODUCT');
$A_SETUP_PARTITIONING_NOT_PARTITIONED_PRODUCT            = __T('A_SETUP_PARTITIONING_NOT_PARTITIONED_PRODUCT');
$A_SETUP_PARTITIONING_IP_ADDRESS                         = __T('A_SETUP_PARTITIONING_IP_ADDRESS');
$A_SETUP_PARTITIONING_PARTITIONING_AVAILABLE             = __T('A_SETUP_PARTITIONING_PARTITIONING_AVAILABLE');
$A_SETUP_PARTITIONING_PARTITIONING_WARNING               = __T('A_SETUP_PARTITIONING_PARTITIONING_WARNING');
$A_SETUP_PARTITIONING_AUTOVACCUM_DISABLED                = __T('A_SETUP_PARTITIONING_AUTOVACCUM_DISABLED');
$A_SETUP_PARTITIONING_CONS_EXC_DISABLED                  = __T('A_SETUP_PARTITIONING_CONS_EXC_DISABLED');
$A_SETUP_PARTITIONING_MAX_LOCKS_INF                      = __T('A_SETUP_PARTITIONING_MAX_LOCKS_INF');
$A_SETUP_PARTITIONING_PG_VERSION_INF                     = __T('A_SETUP_PARTITIONING_PG_VERSION_INF');
$A_SETUP_PARTITIONING_CB_VERSION_INF                     = __T('A_SETUP_PARTITIONING_CB_VERSION_INF');
$A_SETUP_PARTITIONING_CONFIG_LIMIT_REACHED               = __T('A_SETUP_PARTITIONING_CONFIG_LIMIT_REACHED');
$A_SETUP_PARTITIONING_PARTITIONING_NOT_AVAILABLE         = __T('A_SETUP_PARTITIONING_PARTITIONING_NOT_AVAILABLE');
$A_SETUP_PARTITIONING_PARTITIONING_NOT_AVAILABLE_GATEWAY = __T('A_SETUP_PARTITIONING_PARTITIONING_NOT_AVAILABLE_GATEWAY');
$A_SETUP_PARTITIONING_PARTITIONING_HELP                  = __T('A_SETUP_PARTITIONING_PARTITIONING_HELP');
$A_SETUP_PARTITIONING_CONFIRM_BOX_TITLE                  = __T('A_SETUP_PARTITIONING_CONFIRM_BOX_TITLE');
$A_SETUP_PARTITIONING_CONFIRM_BOX_MESSAGE                = __T('A_SETUP_PARTITIONING_CONFIRM_BOX_MESSAGE');
$A_SETUP_PARTITIONING_CONFIRM_BOX_EMAIL                  = __T('A_SETUP_PARTITIONING_CONFIRM_BOX_EMAIL');
$A_SETUP_PARTITIONING_PARTITION_BUTTON                   = __T('A_SETUP_PARTITIONING_PARTITION_BUTTON');
$A_SETUP_PARTITIONING_CANCEL_BUTTON                      = __T('A_SETUP_PARTITIONING_CANCEL_BUTTON');

// User
$userModel  = new UserModel($_SESSION['id_user']);
$userInfo   = $userModel->getValues();

// Traitement du lancement du processus
if(isset($_POST['partition']))
{
    // Proceeding with partitioning
    $databaseMaster = Database::getConnection();
    $partitioningActivation = new PartitioningActivation($databaseMaster);
    $productInfos = $partitioningActivation->gatherPartitioningInformation(array_keys($_POST['partition']), $_POST['email']);
    
    // Vérification des Processus en cours
    // 10/08/2011 BBX BZ 23353
    $processes = $partitioningActivation->checkProcesses($productInfos);
    if($processes) 
    {
        $errorMessage = __T('A_SETUP_PARTITIONING_PROCESS_RUNNING',$processes);
    }
    else 
    {
        $partitioningActivation->createPartitioningFile();
        $partitioningActivation->writePartitioningFile($productInfos);
        $partitioningActivation->preparePartitioning($productInfos);
        // Redirecting to index
        header("Location: ".NIVEAU_0."index.php");
        return;
    } 
}

// Bandeau
include_once REP_PHYSIQUE_NIVEAU_0.'intranet_top.php';
?>

<!--[if IE 6]>
<style type="text/css">
#partitioning_loader_background {
    position:absolute;
    left:0px;
    top:expression(documentElement.scrollTop + 0);
    height:expression(screen.height);
}
</style>
<![endif]-->

<script type="text/javascript">
/**
 * Updates Partition button status according to the applciations checked
 */
function updateButton()
{
    var nbChecked = 0;
    var form = $('partition_form');
    var boxes = form.getInputs('checkbox');
    boxes.each(function(c) {
        if(c.checked) nbChecked++;
    });
    if(nbChecked == 0) {
        $('partition_button').disabled = true;
    }
    else {
        $('partition_button').disabled = false;
    }
}

/**
 * Displays confirm window
 */
function confirmPartitioning()
{
    // Display background + confirmbox
    $('partitioning_loader_background').setStyle({display:'block'});
    $('confirm_box').setStyle({display:'block'});
    return false;
}

/**
 * Cancels Partitioning
 */
function cancelPartitioning()
{
    $('confirm_box').setStyle({display:'none'});
    $('partitioning_loader_background').setStyle({display:'none'});
}

/**
 * Launches Partitioning
 */
function launchPartitioning()
{
    // Si l'email est saisi
    if($('confirm_checkbox_email').checked)
    {
        // 29/06/2011 BBX
        // Ajout controle email
        // BZ 22628
        var reg = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;
        var address = $('confirm_email').value;
        if(reg.test(address) == false) {
            alert('Invalid Email Address');
            return false;
        }
        $('partition_email').value = $('confirm_email').value;
    }
    $('partition_form').submit();
}

/**
 * Lance la mise à jour du bouton au chargement de la page
 */
document.observe("dom:loaded", function() {
    updateButton();
});
</script>

<link rel="stylesheet" href="css/style.css" type="text/css" />

<!-- Partition confirm background -->
<div id="partitioning_loader_background">&nbsp;</div>

<div id="container">
    <div style="text-align:center;font-weight:bold">
        <img src="<?=NIVEAU_0?>/images/titres/setup_partitioning.gif" alt="Partitioning" />
    </div>
    <br />

    <!-- Main interface -->
    <div class="tabPrincipal" style="width:800px;text-align:center;padding:10px;">
<?php
    // Ajout de la possibilité d'afficher des erreurs
    // 10/08/2011 BBX BZ 23353
    if(!empty($errorMessage)) {
        echo ' <!-- ERRORS -->';
        echo '<div class="errorMsg">'.$errorMessage.'</div>';
    }
?>
        <form action="index.php" name="partition_form" id="partition_form" method="post" onsubmit="return confirmPartitioning()">
        <center>
        <fieldset>
            <table width="100%">
                <tr>
                    <th></th>
                    <th><?=$A_SETUP_PARTITIONING_TA_APPLICATION_LABEL?></th>
                    <th><?=$A_SETUP_PARTITIONING_CB_LABEL?></th>
                    <th><?=$A_SETUP_PARTITIONING_POSTGRESQL_LABEL?></th>
                    <th><?=$A_SETUP_PARTITIONING_PARTITIONING_LABEL?></th>
                    <th><?=$A_SETUP_PARTITIONING_INFORMATION_LABEL?></th>
                </tr>
<?php
// Recupere la liste des produits
// 30/06/2011 BBX
// Ne récupère que les produits actifs
// BZ 22775
foreach(ProductModel::getActiveProducts() as $product)
{
    // Product model instance
    $productModel = new ProductModel($product['sdp_id']);
    // Database instance for Product
    $databaseProduct = Database::getConnection($product['sdp_id']);
    // Partitioning information
    $partitioningInfo = $A_SETUP_PARTITIONING_NOT_PARTITIONED_PRODUCT;
    if($databaseProduct->isPartitioned())
        $partitioningInfo =  $A_SETUP_PARTITIONING_PARTITIONED_PRODUCT;
    // Application information
    $postgresqlParametersInformation = $databaseProduct->getConfigInformation();
    $postgresqlParametersInformation[$A_SETUP_PARTITIONING_IP_ADDRESS] = $product['sdp_ip_address'];
    $postgresqlParametersInformation['Port'] = $product['sdp_db_port'];
    $messageConfigInfo = "";
    foreach($postgresqlParametersInformation as $param => $value) {
        $messageConfigInfo .= '<li>'.$param.' : '.$value.'</li>';
    }
    // Partitioning Possible
    $partitioningAllowed = true;
    $icon = NIVEAU_0.'images/icones/accept.png';
    $infoMessage = $A_SETUP_PARTITIONING_PARTITIONING_AVAILABLE;
    try {
        // Testing needed elements
        $partitioningActivation = new PartitioningActivation($databaseProduct);
        $partitioningParameters = $partitioningActivation->checkPgParameters();
        $postgresqlVersionIsOk = $partitioningActivation->checkVersion();
        $baseComponentVersionOk = (str_replace('.','',$productModel->getCBVersion()) >= 51400);
        $pgConfigLimitReached = $partitioningActivation->isPostgresqlConfigurationLimitReached();
    }
    catch (Exception $e) {
        // Just in case
        echo '<div class="errorMsg">'.$e->getMessage().'</div>';
    }
    // If partitioning is not possible (bz24540, exclusion du Gateway)
    if( ProductModel::isBlankProduct( $product['sdp_id'] ) )
    {
        $partitioningAllowed = false;
        $icon                = NIVEAU_0.'images/icones/exclamation.png';
        $infoMessage         = $A_SETUP_PARTITIONING_PARTITIONING_NOT_AVAILABLE_GATEWAY;
    }
    else if(is_array($partitioningParameters) || !$postgresqlVersionIsOk || !$baseComponentVersionOk || $pgConfigLimitReached)
    {
        $partitioningAllowed = false;
        $icon = NIVEAU_0.'images/icones/exclamation.png';
        $subInfoMessages = array();
        if(is_array($partitioningParameters))
        {
            foreach($partitioningParameters as $parameters => $value)
            {
                switch($parameters)
                {
                    case 'autovacuum':
                        $subInfoMessages[] = $A_SETUP_PARTITIONING_AUTOVACCUM_DISABLED;
                    break;
                    case 'constraint_exclusion':
                        $subInfoMessages[] = $A_SETUP_PARTITIONING_CONS_EXC_DISABLED;
                    break;
                    case 'max_locks_per_transaction':
                        $subInfoMessages[] = $A_SETUP_PARTITIONING_MAX_LOCKS_INF.$value;
                    break;
                }
            }
        }
        if(!$postgresqlVersionIsOk)
            $subInfoMessages[] = $A_SETUP_PARTITIONING_PG_VERSION_INF;
        if(!$baseComponentVersionOk)
            $subInfoMessages[] = $A_SETUP_PARTITIONING_CB_VERSION_INF;
        if($pgConfigLimitReached)
            $subInfoMessages[] = $A_SETUP_PARTITIONING_CONFIG_LIMIT_REACHED;
        $infoMessage = $A_SETUP_PARTITIONING_PARTITIONING_NOT_AVAILABLE."<li>".implode("</li><li>",$subInfoMessages)."</li>";
    }
    // If partitioning is possible but with warning
    elseif($partitioningActivation->getNbPartitionedDb() > 1)
    {
        $icon = NIVEAU_0.'images/icones/error.png';
        $infoMessage = $A_SETUP_PARTITIONING_PARTITIONING_WARNING;
    }
    // Affichage
    echo '
                <tr>
                    <td>';
                    // Checkbox uniquement si l'application n'est ni partitionnée
                    // Ni dans l'impossibilité d'être partitionnée
                    if($partitioningAllowed && !$databaseProduct->isPartitioned())
                        echo '<input type="checkbox" name="partition['.$product['sdp_id'].']" onclick="updateButton()" checked />';
                    echo '</td>
                    <td class="product_infos" onmouseover="popalt(\''.$messageConfigInfo.'\')">'.$product['sdp_label'].'</td>
                    <td>'.$productModel->getCBVersion().'</td>
                    <td>'.$databaseProduct->getVersion().'</td>
                    <td>'.$partitioningInfo.'</td>
                    <td>';
                    // Icone uniquement si l'application n'est aps partitionnée
                    if(!$databaseProduct->isPartitioned())
                        echo '<img src="'.$icon.'" alt="" onmouseover="popalt(\''.$infoMessage.'\')" />';
                    echo '</td>
                </tr>';
}
?>
            </table>
            <input type="hidden" id="partition_email" name="email" value="" />
            <div class="infoBox" style="text-align:left;margin-top:20px">
                <?=$A_SETUP_PARTITIONING_PARTITIONING_HELP?>
            </div>
            <div style="width:100%;text-align:right;margin-top:20px">
                <input id="partition_button" name="partition_launch" 
                       type="submit" class="bouton"
                       value="<?=$A_SETUP_PARTITIONING_PARTITION_BUTTON?>" />
            </div>
        </fieldset>
        </center>
        </form>
    </div>

    <!-- Partition confirm window -->
    <div id="confirm_box" class="infoBox" style="display:none">
        <div id="confirm_box_title"><?=$A_SETUP_PARTITIONING_CONFIRM_BOX_TITLE?></div>
        <div id="confirm_box_message">
            <?=$A_SETUP_PARTITIONING_CONFIRM_BOX_MESSAGE?>
        </div>
        <div id="confirm_box_email">
            <input id="confirm_checkbox_email" name="confirm_checkbox_email" type="checkbox" />
            <label for="confirm_checkbox_email"><?=$A_SETUP_PARTITIONING_CONFIRM_BOX_EMAIL?></label>
            <input id="confirm_email" type="text" value="<?=$userInfo['user_mail']?>" />
        </div>
        <div id="confirm_box_button">
            <input type="button" class="bouton" 
                   value="<?=$A_SETUP_PARTITIONING_PARTITION_BUTTON?>"
                   onclick="launchPartitioning()" />
            <input type="button" class="bouton" 
                   value="<?=$A_SETUP_PARTITIONING_CANCEL_BUTTON ?>"
                   onclick="cancelPartitioning()" />
        </div>
    </div>
</div>

</body>
</html>