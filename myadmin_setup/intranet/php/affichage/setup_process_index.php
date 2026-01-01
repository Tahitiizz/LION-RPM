<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*
* 	06/04/2009 - SPS : 
*		- adaptation pour IE8
*	02/06/2009 - SPS : 
*		- pour la fonction UpdateProcess, on cache les elements precedents, et on affiche l'element pendant 6 sec (correction bug 9804)
*
*	01/07/2009 BBX : Ajout d'un contrôle sur les valeurs numériques. BZ 9857
*	09/07/2009 MPR : Correction du bug 9804
*			-> Message d'erreur affiché en même temps que le message d'info (mdoification de la fonction js updateProcess()
*	06/08/2009 GHX
*		- Correction du BZ 10708[REC][Task Scheduler / Process] : répercution menu daily/hourly sur time periode
*	03/10/2011 ACS
*		- Mantis 615: DE Data reprocessing GUI
*	14/09/2012 ACS DE Improve configuration of Task Scheduler
*/
?>
<?php
session_start();
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";

// Connexion à la base de données locale
// 31/01/2011 BBX BZ 20450 : On remplace new DatabaseConnection() par Database::getConnection()
$database = DataBase::getConnection();

// Librairies et classes nécessaires
include_once(REP_PHYSIQUE_NIVEAU_0.'/php/deploy_and_compute_functions.php');

// Intranet top + menu contextuel
include_once(REP_PHYSIQUE_NIVEAU_0 . "/intranet_top.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "/php/menu_contextuel.php");

include_once(REP_PHYSIQUE_NIVEAU_0 . "/class/DataReprocessing.class.php");


// 27/07/2010 OJT : Correction bz16899, mise en commentaire du contrôle d'accès
/*// Contrôle d'accès
$userModel = new UserModel($_SESSION['id_user']);
$query = "SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu LIKE 'Process'";
$result = $database->getRow($query);
$idMenu = ($result['id_menu'] == '') ? 0 : $result['id_menu'];
if(!$userModel->userAuthorized($idMenu)) {
	include_once(REP_PHYSIQUE_NIVEAU_0."intranet_top.php");
	echo '<div class="errorMsg">'.__T('A_USER_NOT_ALLOWED_TO_ACCESS').'</div>';
	exit;
}*/

// Récupération du client type
$client_type = getClientType($_SESSION['id_user']);

// Récupération des infos des produits actifs
$query = "SELECT * FROM sys_definition_product WHERE sdp_on_off = 1 ORDER BY sdp_id";
$productsInfos = $database->getAll($query);

// Si le master est un Produit Blanc, on le positionne en dernier (ergonomie)
if( ProductModel::isBlankProduct( ProductModel::getIdMaster() ) )
{
    // On ajoute à la fin le premier élément dépillé par array_shift
    $productsInfos []= array_shift( $productsInfos );
}

// MESSAGE A METTRE EN BASE
$A_PROCESS_TIME_PERIOD_CANNOT_BE_NULL 	= __T('A_PROCESS_TIME_PERIOD_CANNOT_BE_NULL');
$A_PROCESS_ERROR_FAILED					= __T('A_PROCESS_ERROR_FAILED');
$A_JS_FORMS_FILL_IN_REQUIRED_FIELDS		= __T('A_JS_FORMS_FILL_IN_REQUIRED_FIELDS');
?>
<style type="text/css">
.processContainer {
	text-align:center;
	margin:0 10%;
}
.processBox {
	position:relative;
	width:450px;
	text-align:justify;
	font : normal 8pt Verdana, Arial, sans-serif;
	color : #585858;
	border:1px solid #7F9DB9;
	margin:40px 10px 10px 10px;
	padding-bottom: 5px;
	background-image : url('<?=NIVEAU_0?>images/fonds/fond_selecteur.gif');
	zoom: 1;
	/* 06/04/2009 - modif SPS : adaptation pour IE8*/
	float:left;
}
.processBoxTab {
	position:absolute;
	top:-27px;
	left:-1px;
	height:20px;
	padding-top:5px;
	padding-left:5px;
	padding-right:5px;
	background-color:#CCCCCC;
	border:1px solid #7F9DB9;
	font-weight:bold;
}
.helpArea {
	width:600px;
	margin:auto;
	border-bottom:1px solid #7F9DB9;
}
</style>

<script type="text/javascript">
/*******************
* Variables globales
*******************/
<?php
foreach($productsInfos as $product) 
{
	// Déclaration des variables globales des listeners de chaque produit
	echo "var _listener_{$product['sdp_id']} = '';\n";
}
?>
var ProductToProcess = null; 

/*******************
* Si les minutes utps > 59, on reformatte les champs.
* On contrôle également la non nullité de la période.
* @param string : id produit
*******************/
function UtpsHourAutoFormat(idProduct, idProcess)
{
	// Traitement des minutes
	if(parseInt($('time_period_minute_'+idProduct+'_'+idProcess).value) > 59) {
		$('time_period_hour_'+idProduct+'_'+idProcess).value = 
			parseInt($('time_period_hour_'+idProduct+'_'+idProcess).value)
			+ Math.floor($('time_period_minute_'+idProduct+'_'+idProcess).value / 60);
		$('time_period_minute_'+idProduct+'_'+idProcess).value %= 60;
	}
	// On vérifie que la valeur globale (heure + minutes) d'utps n'est pas nulle
	if(!parseInt($('time_period_hour_'+idProduct+'_'+idProcess).value) && !parseInt($('time_period_minute_'+idProduct+'_'+idProcess).value)) {
		changeUtps(idProduct, idProcess);
	}
	// On re-verifie
	if(!parseInt($('time_period_hour_'+idProduct+'_'+idProcess).value) && !parseInt($('time_period_minute_'+idProduct+'_'+idProcess).value)) {
		$('time_period_minute_'+idProduct+'_'+idProcess).value = '1';
		alert('<?=$A_PROCESS_TIME_PERIOD_CANNOT_BE_NULL?>');
		$('time_period_minute_'+idProduct+'_'+idProcess).focus();
	}
}

/*******************
* 01/07/2009 BBX
* Cette procédure permet de contrôler la saisie des valeurs numériques
* @param int : id produit
*******************/
function checkForm(idProduct)
{
	// Récupération des valeurs saisies
	var allInputs = $('form_'+idProduct).getInputs('text');
	// Parcours des valeurs
	for(i = 0; i < allInputs.length; i++) {
		// Si la valeur n'est pas numérique ou pas de valeur, on renvoi faux
		if((isNaN(allInputs[i].value)) || (allInputs[i].value.strip() == '')) {
			allInputs[i].select();
			alert('<?=$A_JS_FORMS_FILL_IN_REQUIRED_FIELDS?>');
			return false;
		}
	}
	// 14/09/2012 ACS DE Improve configuration of Task Scheduler
	var productArray = ProductToProcess[idProduct];
	for (var p = 0; p < productArray.length; p++) {
		if (!isOffsetOk(idProduct, productArray[p])) {
			$('time_period_hour_' + idProduct + '_' + productArray[p]).select();
			alert('<?= __T('A_TASK_SCHEDULER_OFFSET_CHECK') ?>');
			return false;
		}
	}

	// Si tout est ok on renvoie true
	return true;
}

// 14/09/2012 ACS DE Improve configuration of Task Scheduler
function isOffsetOk(idProduct, idProcess) {
	time = Number(60 * $('time_period_hour_' + idProduct + '_' + idProcess).value) + Number($('time_period_minute_' + idProduct + '_' + idProcess).value);
	offset = Number(60 * $('offset_period_hour_' + idProduct + '_' + idProcess).value) + Number($('offset_period_minute_' + idProduct + '_' + idProcess).value);
	
	return time > offset;
}

var _timeControlInfo = false;
var _timeControlError = false;

/**
 * Cette procédure permet de mettre un jour les process d'un produit
 *
 * @param int idProduit Identifiant du produit
 * @param boolean flagCtrl Indique si la varification des champs doit être effectuée
 */
function updateProcess(idProduct, flagCtrl )
{
	// 01/07/2009 BBX : Ajout d'un contrôle sur les valeurs numériques. BZ 9857
	if( flagCtrl && !checkForm(idProduct)) {
		return false;
	}
	
	// maj 09/07/2009  MPR : On désactive le bouton save pendant le traitement ajax
	$('save_'+idProduct).disable();
	// maj 09/07/2009 : MPR - On masque les messages error ou info
	$('info_'+idProduct).style.display = 'none';
	$('error_'+idProduct).style.display = 'none';
	
	var params = Form.serialize('form_'+idProduct);
	new Ajax.Request('setup_process_update.php',{
		method: 'post',
		postBody: params,
		onComplete: function(data) {
			var response = eval('(' + data.responseText + ')');			
			if (response.message_type == "error") {
				$('info_'+idProduct).style.display = 'none';
				$('error_'+idProduct).update(response.message_alert);
				$('error_'+idProduct).style.display = 'block';
				
				if(!_timeControlError) {
					_timeControlError = true;
					setTimeout("new Effect.Fade($('error_"+idProduct+"'));_timeControlError=false;",6000);
				}
				
			}
			else {
				$('error_'+idProduct).style.display = 'none';
				$('info_'+idProduct).update(response.message_alert);
				$('info_'+idProduct).style.display = 'block';
				
				if(!_timeControlInfo) {
					_timeControlInfo = true;
					setTimeout("new Effect.Fade($('info_"+idProduct+"'));_timeControlInfo=false;",6000);
				}
				
			}
			$('save_'+idProduct).enable();
		}
	});
}

/*******************
* Cette procédure gère un bouton de process
* @param int : id produit
* @param int : id process
* @param string : 	action (ready / waiting)
*******************/
function manageButton(idProduct,idProcess,action)
{
<?php
// 28/08/2012 BBX
// BZ 28539 : test de l'existance du process avant d'essayer de gérer le bouton
?>
    if($('button_launch_'+idProduct+'_'+idProcess)) 
    {
	if(action == 'ready') {
		$('button_launch_'+idProduct+'_'+idProcess).update('<img src="<?=NIVEAU_0?>images/icones/bt_play1.gif" border="0" width="20" height="20" onclick="launchProcess(\''+idProduct+'\',\''+idProcess+'\')" style="cursor:pointer;" />');
	}
	else {
		$('button_launch_'+idProduct+'_'+idProcess).update('<img src="<?=NIVEAU_0?>images/icones/bt_compute.gif" border="0" width="20" height="20" />');
	}
    }
}

/*******************
* Cette procédure lance un process sur un produit
* @param int	id produit
* @param int	id process
*******************/
function launchProcess(idProduct,idProcess) 
{
	// Si confirmation, lancement via ajax
	new Ajax.Request('setup_process_launch.php',{
		method:'get',
		parameters:'product='+idProduct+'&master_id='+idProcess,
		onSuccess:function(transport) {
			var response = transport.responseText;
			if (response.slice(0,8) == 'launched') {
				// on change le bouton
				manageButton(idProduct,idProcess,'waiting');
				// On lance le timer qui verifie l'etat des process
				processCheck();
			}
			else alert("<?=$A_PROCESS_ERROR_FAILED?>"+response);
		}
	});
}

/*******************
* Cette procédure va vérifier l'état des process d'un produit
* @param int : 	id produit
*******************/
function processListener(idProduct)
{
	new Ajax.Request('setup_process_check.php',{
		method:'get',
		parameters:'product='+idProduct,
		onSuccess:function(transport) {
			// On passe tous les boutons en mode ready
			var productArray = ProductToProcess[idProduct];
			for(var p = 0; p < productArray.length; p++) {
				manageButton(idProduct,productArray[p],'ready');
			}
			if(transport.responseText != 'no running process found') {
				// Pour tous les process en attente, on met le bouton en mode waiting
				if(transport.responseText.include(',')) {
					var processArray = transport.responseText.split(',');
					for(var idProcess in processArray) {
						manageButton(idProduct,idProcess,'waiting');
					}
				}
				else {
					var idProcess = transport.responseText;
					manageButton(idProduct,idProcess,'waiting');
				}
			}
			// Si aucun process en cours, on stoppe le listener
			else eval('_listener_'+idProduct+'.stop()');
		}
	});
}

/*******************
* Cette procédure lance un listener par produit
* @param int : 	id produit
*******************/
function processCheck()
{
	<?php
	foreach($productsInfos as $product) 
	{
		// Création d'un listener pour chaque produit
		echo <<<END
		_listener_{$product['sdp_id']} = new PeriodicalExecuter(function() {processListener('{$product['sdp_id']}')}, 5);
END;
	}
	?>
}

/**
 * Lancer l'envoi des rapports manuellement (pour un produit blanc uniquement)
 * Cette fonction appelle le script report_sender via un appel A.J.A.X.
 */
function bpLaunchReportSender( productId )
{
    // Test  si le bouton est bien
    if( $( 'bpReportLauncher' ) )
    {
        // Une demande est-elle déjà en cours ?
        if( $( 'bpReportLauncher' ).src.endsWith( "bt_play1.gif" ) )
        {
            // Remplacement de l'image par le gif animé
            $( 'bpReportLauncher' ).src = $( 'bpReportLauncher' ).src.replace( "bt_play1", "bt_compute" );

            $( 'info_' + productId ).hide(); // Efface les éventuelles erreurs
            $( 'error_' + productId ).hide(); // Efface les éventuelles erreurs

            new Ajax.Request( "<?=NIVEAU_0?>scripts/report_sender.php",
            {
                method:"post",
                parameters:"date=" + $F( 'bpReportDate' ),
                onSuccess: function( res )
                {
                    if( res.responseText.length > 0 )
                    {
                        $( 'error_' + productId ).update( res.responseText );
                        $( 'error_' + productId ).show();
                    }
                    else
                    {
                        $( 'info_' + productId ).update( 'Reports have been sent' );
                        $( 'info_' + productId ).show();
                    }
                    $( 'bpReportLauncher' ).src = $( 'bpReportLauncher' ).src.replace( "bt_compute", "bt_play1" );
                }
            });
        }
        else
        {
            // Already launch, in progress...
        }
    }
}

/*******************
* Listeners :
* Le script va lancer la fonction qui lance les listeners de chaque produit
* Afin de surveiller l'état des process
*******************/
<?php
// Si l'admin est un customisateur, on gère les boutons de lancement manuel
// 5451: Lancement manuel des process : suppression de la condition sur l'utilisateur
?>
document.observe("dom:loaded", function() {
    // Création du tableau de combinaions Produits => Process
    ProductToProcess = new Array();
    <?php
    //03/10/2014 - FGD - Bug 44347 - [IS][GPRS6.2][T&AGW_5.3.2.04] admin user is not able to save task scheduler process (only astellia_admin)
    foreach($productsInfos as $product)
    {
        // Connexion au produit
        $db = DataBase::getConnection($product['sdp_id']);
        // Récupère les process disponible sur le produit
        $query = "SELECT * FROM sys_definition_master WHERE visible = 1 ORDER BY ordre";
        $availableProcesses = $db->getAll($query);
        // Construction du tableau de relations
        echo "var ProcessArray = new Array();\n";				
        foreach($availableProcesses as $process) {
                // On crée le tableau de correspondance
                echo "ProcessArray.push({$process['master_id']});\n";
        }
        // Mémorisation du tableau
        echo "ProductToProcess[{$product['sdp_id']}] = ProcessArray;\n";				
    }

    foreach($productsInfos as $product) 
    {
        // Récupération du statut de chaque process, pour chaque produit
        echo "processListener('{$product['sdp_id']}');\n";
    }
    ?>
    // Lance les listener
    processCheck();
});

// 14/09/2012 ACS DE Improve configuration of Task Scheduler
function getInfo(idProduct, idProcess) {
	
	if (!isOffsetOk(idProduct, idProcess)) {
		result = "<?= __T('A_TASK_SCHEDULER_OFFSET_CHECK') ?>";
	}
	else {
		time = Number(60 * $('time_period_hour_' + idProduct + '_' + idProcess).value) + Number($('time_period_minute_' + idProduct + '_' + idProcess).value);
		offset = Number(60 * $('offset_period_hour_' + idProduct + '_' + idProcess).value) + Number($('offset_period_minute_' + idProduct + '_' + idProcess).value);

		result = "<?= __T(A_TASK_SCHEDULER_EXECUTION_TIMES) ?>";
		execDate = new Date();
		execDate.setHours(0);
		execDate.setMinutes(0);
		execDate.setSeconds(0);
		
		execDate.setTime(execDate.getTime() + offset * 60 * 1000);
		
		for (i = 0 ; i < 5 ; i++) {
			result += "<br />" + displayDate(execDate);
			execDate.setTime(execDate.getTime() + time * 60 * 1000);
		}
	}
	
	return result;
}

function displayDate(execDate) {
	var hour = execDate.getHours().toString();
	if (hour.length == 1) {hour = "0" + hour;}
	var minute = execDate.getMinutes().toString();
	if (minute.length == 1) {minute = "0" + minute;}
	var day = execDate.getDate().toString();
	if (day.length == 1) {day = "0" + day;}
	var month = (execDate.getMonth() + 1).toString();
	if (month.length == 1) {month = "0" + month;}
	var year = execDate.getFullYear().toString();
	return hour + "h" + minute + " " + day + "/" + month + "/" + year;
}

</script>

<center>
	<img src="<?=NIVEAU_0?>images/titres/process_setup_interface.gif"/>	
</center>

	<br />
	<div class="helpArea">
		<div>
			<a href="#" onclick="Effect.toggle('help_box_1', 'slide');" onmouseover="popalt('<?=__T('A_PROFILE_MANAGEMENT_HIDE_DISPLAY_HELP')?>')">
				<img src="<?=NIVEAU_0?>images/icones/information.png" border="0" />
				<span class="texteGris" style="color:#7F9DB9;font-weight:bold;">Help</span>
			</a>
			<div id="help_box_1" class="infoBox" style="display:none;">
				<?=__T('A_SETUP_PROCESS_HELP')?>
			</div>
		</div>
	</div>
	<br />
	<div class="processContainer">
	
	<?php
        
        // 21/11/2011 BBX
        // BZ 24764 : correction des messages "Notice" PHP
        if(!isset($style)) $style = null;
        
	// Affiche d'une box process pour chaque produit
	foreach($productsInfos as $product) 
	{		
		// Connexion au produit
        // 31/01/2011 BBX BZ 20450 : On remplace new DatabaseConnection() par Database::getConnection()
		$db = DataBase::getConnection($product['sdp_id']);
		
		// Récupère les process disponible sur le produit
		$query = "SELECT * FROM sys_definition_master WHERE visible = 1 ORDER BY ordre";
		$availableProcesses = $db->getAll($query);
        
        // Lecture du compute mode du produit
        $computeMode = get_sys_global_parameters('compute_mode', 'hourly', $product['sdp_id']);
        // 21/06/2012 ACS BZ 27393 Interface issue when compute booster is running
        // On vérifie que le compute booster n'est pas activé.
        if ($computeMode === 'daily' && get_sys_global_parameters('compute_switch', '', $product['sdp_id']) == 'hourly') {
        	$computeMode = 'hourly';
        }
        
		// Création de la box
		$master = ($product['sdp_master'] == 1) ? ' [master]' : '';
		$masterTopo = ($product['sdp_master_topo'] == 1) ? ' [topology master]' : '';

        // Gestion des processus pour un Produit Blanc
        if( ProductModel::isBlankProduct( $product['sdp_id'] ) )
        {
            // On récupère dans la liste des process uniquement le process 'Report Sender'
            $bpProcess   = null;
            $i           = 0;
            $nbProcesses = count( $availableProcesses );
            while( $i < $nbProcesses && $availableProcesses[$i]['master_name'] !== 'Report Sender' ) $i++;

            // Si le process spécifique au Produit Blanc a bien été trouvé
            if( $i < $nbProcesses )
            {
                $processChecked = '';
                $processId      = $availableProcesses[$i]['master_id'];
                $processComment = htmlentities( $availableProcesses[$i]['commentaire'] );
                $processState   = intval( $availableProcesses[$i]['on_off'] );
                // 5451: Lancement manuel des process : suppression de la condition sur l'utilisateur
                $cssCustomStyle = '';
                $cssDateStyle   = '';
                if( $processState === 1 ){
                    $processChecked = 'checked="checked"';
                }

                echo <<<END
                <div class="processBox" id="process_box_{$product['sdp_id']}">
                    <div class="processBoxTab"{$style}>{$product['sdp_label']}{$master}</div>
                    <form id="form_{$product['sdp_id']}" action="setup_process_update.php" method="post">
                        <input type="hidden" name="product" value="{$product['sdp_id']}" />
                        <input type="hidden" name="process_id" value="{$processId}" />
                        <table cellpadding="0" width="100%" border="0" style="margin:5px 0px;">
                            <tr>
                                <th align="center" valign="middle">Name</th>
                                <th align="center" valign="middle">Date</th>
                                <th align="center" valign="middle">On</th>
                                <th align="center" valign="middle">Manual launch</th>
                            </tr>
                            <tr>
                                <td align="center">Report Sender</td>
                                <td align="center">
                                    Date <img src="{$niveau0}images/icones/information.png" style="margin-top:5px;" border="0" onmouseover="popalt('{$processComment}');"/>:
                                    <input
                                        {$cssDateStyle}
                                        id="bpReportDate"
                                        type="text"
                                        size="10"
                                        value="yyyy/mm/dd"
                                        onfocus="if(this.value=='yyyy/mm/dd')this.value='';"/>
                                </td>
                                <td align="center">
                                    <input value="1" type="checkbox" name="process[{$processId}][on_off]" {$processChecked} />
                                </td>
                                <td align="center">
                                    <img
                                        id="bpReportLauncher"
                                        src="{$niveau0}images/icones/bt_play1.gif"
                                        border="0" width="20" height="20"
                                        onclick="bpLaunchReportSender( {$product['sdp_id']} );"
                                        style="{$cssCustomStyle}"
                                    />
                                </td>
                            </tr>
                            <tr>
                                <td colspan="4" align="center" valign="middle">
                                    <input type="button" id="save_{$product['sdp_id']}" class="bouton" value="Save" onclick="updateProcess('{$product['sdp_id']}', false )" />
                                </td>
                            </tr>
                        </table>
                    </form>
                    <center>
                        <div id="info_{$product['sdp_id']}" class="okMsg" style="display:none;"></div>
                        <div id="error_{$product['sdp_id']}" class="errorMsg" style="display:none;"></div>
                    </center>
                </div>
END;
            }
        } // Fin gestion spécifique Produit Blanc
        else
        {
		echo <<<END
		<div class="processBox" id="process_box_{$product['sdp_id']}">
			<div class="processBoxTab"{$style}>{$product['sdp_label']}{$master}{$masterTopo}</div>
			<form id="form_{$product['sdp_id']}" action="setup_process_update.php" method="post">
			<input type="hidden" name="product" value="{$product['sdp_id']}" />
			<table cellpadding="0" width="100%" border="0">
				<tr>
					<th align="center" height="30" valign="middle">Name</th>
					<th align="center" width="110" height="30" valign="middle">Time period</th>
					<th align="center" width="110" height="30" valign="middle">Offset</th>
					<th align="center" width="20" height="30" valign="middle">On</th>
END;
                // 5451: Lancement manuel des process : suppression de la condition sur l'utilisateur
		// on affiche le lanceur de process manuel
		echo '<th align="center">Manual launch</th>';
		echo '</tr>';
		
		// Affichage des lignes
		foreach($availableProcesses as $process) 
		{
            // 05/07/2011 OJT : correction bz21870. Exclusion des Master de type
            // hourly pour les produits avec le compute mode daily
            if( ( $computeMode === 'daily' ) && ( stripos( $process['master_name'], 'hourly' ) !== FALSE ) )
            {
                continue; // Passe à l'itération suivante
            }
            
			// Récupération de la time period
			$TPhours = floor($process['utps']/60);
			$TPminutes = (($process['utps']/60)-$TPhours)*60;
			
			// Récupération de l'offset
			$Offhours = floor($process['offset_time']/60);
			$Offminutes = (($process['offset_time']/60)-$Offhours)*60;
			
			// On selectione l'option qu'il faut
			if ($process['utps'] == '1440') {
				$utps_selected = 'D';
			} elseif ($process['utps'] == '60') {
				$utps_selected = 'H';
			} else {
				$utps_selected = 'O';
			}
			
			// 14/09/2012 ACS DE Improve configuration of Task Scheduler
			// DELETE

			// On
			$checked = ($process['on_off'] == 1) ? ' checked' : '';

			// Affichage de la ligne
			// 17:09 06/08/2009 GHX
			// Correction du BZ 10708
			// Ajout de l'id du process dans les ID des balises HTML et dans la fonctoin changeUtps()
			// 14/09/2012 ACS DE Improve configuration of Task Scheduler
			// DELETE
			echo <<<END
				<tr>
					<td align="left" height="35" valign="middle">
						<table width="100%">
							<tr>
								<td>
									{$process['master_name']}
								</td>
							</tr>
						</table>
					</td>
					<td align="center" height="35" valign="middle">
						<input type="text" id="time_period_hour_{$product['sdp_id']}_{$process['master_id']}" name="process[{$process['master_id']}][time_period][hour]" style="width:20px;text-align:right;" value="{$TPhours}" />h
						&nbsp;
						<input type="text" id="time_period_minute_{$product['sdp_id']}_{$process['master_id']}" name="process[{$process['master_id']}][time_period][minute]" style="width:20px;text-align:right;" value="{$TPminutes}" onblur="UtpsHourAutoFormat('{$product['sdp_id']}', '{$process['master_id']}')" />mn
					</td>
					<td align="center" height="35" valign="middle">
						<input type="text" id="offset_period_hour_{$product['sdp_id']}_{$process['master_id']}" name="process[{$process['master_id']}][offset][hour]" style="width:20px;text-align:right;" value="{$Offhours}" />h
						&nbsp;
						<input type="text" id="offset_period_minute_{$product['sdp_id']}_{$process['master_id']}" name="process[{$process['master_id']}][offset][minute]" style="width:20px;text-align:right;" value="{$Offminutes}" />mn
					</td>
					<td align="center" height="35" valign="middle">
						<input type="checkbox" value="1" name="process[{$process['master_id']}][on_off]"{$checked} />
					</td>
					
END;
			// 5451: Lancement manuel des process : suppression de la condition sur l'utilisateur
                        // on affiche le lanceur de process manuel
				echo <<<END
					<td id="button_launch_{$product['sdp_id']}_{$process['master_id']}" align="center" valign="middle" height="35" valign="middle">
						<!-- Let`s Ajax functions determine what to display here -->
					</td>
END;
					// 14/09/2012 ACS DE Improve configuration of Task Scheduler
?>
					<td align="center" height="35" valign="middle" width="30">
						<img src="<?=NIVEAU_0?>images/icones/information.png" border="0" onmouseover="popalt(getInfo(<?=$product['sdp_id']?>, <?=$process['master_id']?>))"/>
					</td>
<?
			echo '</tr>';
		}
		echo <<<END
				<tr>
					<td colspan="5" align="center" valign="middle">
						<input type="button" id="save_{$product['sdp_id']}" class="bouton" value="Save" onclick="updateProcess('{$product['sdp_id']}', true)" />
					</td>
				</tr>
			</table>
			</form>
END;
			// 11/10/2011 ACS Mantis 615: DE Data reprocessing GUI
			// 30/06/2014 NSE Mantis 5450: Reprocess des données autorisé pour Client admin
                        include(REP_PHYSIQUE_NIVEAU_0.'myadmin_setup/intranet/php/affichage/setup_data_reprocessing.php');
					
			echo <<<END
			<center>
				<div id="info_{$product['sdp_id']}" class="okMsg" style="display:none;"></div>
				<div id="error_{$product['sdp_id']}" class="errorMsg" style="display:none;"></div>
			</center>
		</div>		
END;
		// 11/10/2011 ACS Mantis 615: DE Data reprocessing GUI
		$nbProduct++;
		if ($nbProduct % 2 == 0) {
			echo <<<END
				<div class="clear"></div>
END;
		}
        }
    }
	?>
	</div>
</body>
</html>
