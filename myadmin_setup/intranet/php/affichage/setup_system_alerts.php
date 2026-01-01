<?
/*
*	@cb41000@
*
*	09/12/2008 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.00
*
*	09/12/2008 BBX : modification du script pour le CB 4.1
*	=> Utilisation des nouvelles variables globales
*	=> Utilisation de la classe DatabaseConnection
*	=> Contrôle d'accès
*	=> Gestion du produit
*
*       - maj 02/08/2010 - MPR : Correction du bz17154 - Ajout d'une icone d'information pour Data Granularity
 *
 *      17/09/2010 NSE bz 17977 : case de message d'erreur vide.
 *      17/09/2010 NSE bz 18027 : validation possible malgré erreurs (mais pas de sauvegarde)
 *      21/09/2010 NSE bz 18033 : cumul des erreurs sur delay et exclusion.
*/
?>
<?php
/**
* 
* @cb40012@
* 
* 	14/04/2008 - Copyright Astellia
* 
* 	Composant de base version cb_4.0.0.12
*
*	- maj 14/03/2008 : benjamin : désactivation de tous les champs input du formulaire "flat_file_form". BZ6243
*   06/04/2009 - modif SPS : adaptation style pour IE8 et gestion de la zone de message 
*
*/
?>
<?php 
				
session_start();
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";

// Librairies et classes nécessaires
require_once(REP_PHYSIQUE_NIVEAU_0.'/models/ProfileModel.class.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'/models/UserModel.class.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'/class/select_family.class.php');

// Connexion à la base de données locale
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
$database = Database::getConnection();

// Intranet top + menu contextuel
include_once(REP_PHYSIQUE_NIVEAU_0 . "/intranet_top.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "/php/menu_contextuel.php");

// 27/07/2010 OJT : Correction bz16899, mise en commentaire du contrôle d'accès
/*// Contrôle d'accès
$userModel = new UserModel($_SESSION['id_user']);
$query = "SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu ILIKE 'Setup System Alerts'";
$result = $database->getRow($query);
$idMenu = ($result['id_menu'] == '') ? 0 : $result['id_menu'];
if(!$userModel->userAuthorized($idMenu)) {
	include_once(REP_PHYSIQUE_NIVEAU_0."intranet_top.php");
	echo '<div class="errorMsg">'.__T('A_USER_NOT_ALLOWED_TO_ACCESS').'</div>';
	exit;
}*/

// Sélection du produit
if(!isset($_GET["product"])){
	$select_family = new select_family($_SERVER['PHP_SELF'], $_SERVER['argv'][0], 'System Alerts',2);
	exit;
}

// Récupération des valeurs transmises via l'url
$product = $_GET["product"];

// Connexion à la base du produit
$database = DataBase::getConnection($product);

// Definition des constantes de messages
$_A_SETUP_SYSTEM_ALERTS_ACTIVATE_LABEL 			= __T('A_SETUP_SYSTEM_ALERTS_ACTIVATE_LABEL');
$_A_SETUP_SYSTEM_ALERTS_NO_DATA_FOR_FLAT_FILE 	= __T('A_SETUP_SYSTEM_ALERTS_NO_DATA_FOR_FLAT_FILE');
$_A_SETUP_SYSTEM_ALERTS_ERROR_TEMPO_NAN 		= __T('A_SETUP_SYSTEM_ALERTS_ERROR_TEMPO_NAN');
$_A_SETUP_SYSTEM_ALERTS_ERROR_EXCLU_FMT 		= __T('A_SETUP_SYSTEM_ALERTS_ERROR_EXCLU_FMT');
$_A_SETUP_SYSTEM_ALERTS_ERROR_EXCLU_INVALID 	= __T('A_SETUP_SYSTEM_ALERTS_ERROR_EXCLU_INVALID');
$_A_SETUP_SYSTEM_ALERTS_ERROR_EXCLU_INTERVAL 	= __T('A_SETUP_SYSTEM_ALERTS_ERROR_EXCLU_INTERVAL');
$_A_SETUP_SYSTEM_ALERTS_UPDATE_SUCCESS 			= __T('A_SETUP_DATA_FILES_UPDATE_SUCCESS'); // 13/10/2010 OJT : Correction bz18461
$_A_SETUP_SYSTEM_ALERTS_UPDATE_FAIL 			= __T('A_SETUP_SYSTEM_ALERTS_UPDATE_FAIL');
$_A_SETUP_SYSTEM_ALERTS_FORM_LABELS 			= __T('A_SETUP_SYSTEM_ALERTS_FORM_LABELS');
$_A_SETUP_SYSTEM_ALERTS_FORM_LABELS_WITH_SA 	= __T('A_SETUP_SYSTEM_ALERTS_FORM_LABELS_WITH_SA');
$_A_SETUP_SYSTEM_ALERTS_TIME_LABELS 			= __T('A_SETUP_SYSTEM_ALERTS_TIME_LABELS');
$_A_SETUP_SYSTEM_ALERTS_BT_RESET				= __T('A_SETUP_SYSTEM_ALERTS_BT_RESET');
$_A_SETUP_SYSTEM_ALERTS_BT_SAVE 				= __T('A_SETUP_SYSTEM_ALERTS_BT_SAVE');
$_A_SETUP_SYSTEM_ALERTS_INFO_EXCLU_FMT 			= __T('A_SETUP_SYSTEM_ALERTS_INFO_EXCLU_FMT');
$_A_SETUP_DATA_FILES_TABLE_TITLE_SA				= __T('A_SETUP_DATA_FILES_TABLE_TITLE_SA');
$_A_SETUP_DATA_FILES_DATA_COLLECTION_FREQUENCY_VALUES 	= __T('A_SETUP_DATA_FILES_DATA_COLLECTION_FREQUENCY_VALUES');
$_A_SETUP_DATA_FILES_GRANULARITY_INFO 					= __T('A_SETUP_DATA_FILES_GRANULARITY_INFO');
$_A_SETUP_SYSTEM_ALERTS_ERROR_DATA_CHUNKS_NAN 			= __T('A_SETUP_SYSTEM_ALERTS_ERROR_DATA_CHUNKS_NAN');
$_A_SETUP_SYSTEM_ALERTS_ERROR_DATA_CHUNKS_VALUE_EXCEEDED= __T('A_SETUP_SYSTEM_ALERTS_ERROR_DATA_CHUNKS_VALUE_EXCEEDED');
$_A_SETUP_SYSTEM_ALERTS_ACTIVATION_SYSTEM_ALERTS 		= __T('A_SETUP_SYSTEM_ALERTS_ACTIVATION_SYSTEM_ALERTS');
?>
<html>
<head>
	<title><?=$_A_SETUP_DATA_FILES_TITLE?></title>
	<script type="text/javascript">
	<!--
<?php
// On redéfinit les constantes utilisées par les fonctions JS	
echo "var _A_SETUP_SYSTEM_ALERTS_ERROR_TEMPO_NAN = '".$_A_SETUP_SYSTEM_ALERTS_ERROR_TEMPO_NAN."';\n";
echo "var _A_SETUP_SYSTEM_ALERTS_ERROR_EXCLU_FMT = '".$_A_SETUP_SYSTEM_ALERTS_ERROR_EXCLU_FMT."';\n";
echo "var _A_SETUP_SYSTEM_ALERTS_ERROR_EXCLU_INVALID = '".$_A_SETUP_SYSTEM_ALERTS_ERROR_EXCLU_INVALID."';\n";
echo "var _A_SETUP_SYSTEM_ALERTS_ERROR_EXCLU_INTERVAL = '".$_A_SETUP_SYSTEM_ALERTS_ERROR_EXCLU_INTERVAL."';\n";
echo "var _A_SETUP_SYSTEM_ALERTS_UPDATE_SUCCESS = '".$_A_SETUP_SYSTEM_ALERTS_UPDATE_SUCCESS."';\n";
echo "var _A_SETUP_SYSTEM_ALERTS_UPDATE_FAIL = '".$_A_SETUP_SYSTEM_ALERTS_UPDATE_FAIL."';\n";
echo "var _A_SETUP_SYSTEM_ALERTS_TIME_LABELS = ('".$_A_SETUP_SYSTEM_ALERTS_TIME_LABELS."').split(';');\n";
echo "var _A_SETUP_SYSTEM_ALERTS_ERROR_DATA_CHUNKS_NAN = '{$_A_SETUP_SYSTEM_ALERTS_ERROR_DATA_CHUNKS_NAN}';\n";
echo "var _A_SETUP_SYSTEM_ALERTS_ERROR_DATA_CHUNKS_VALUE_EXCEEDED = '{$_A_SETUP_SYSTEM_ALERTS_ERROR_DATA_CHUNKS_VALUE_EXCEEDED}';\n";
?>
/********************
* Active / Désactives les alarmes système via Ajax
********************/

function updateAlarmSystemStatus(status)
{
	var activate = 0;
	if (status == true) activate = 1; 

	// Appel du script 'setup_system_alerts_activation.php' pour sauvegarder le statut d'activation des alarmes systemes	
	new Ajax.Request('setup_system_alerts_activation.php', {
		method: 'get', 
		parameters: 'alarm_systems_activation='+activate+"&product=<?=$product?>"
	});

	// Suppression de la zone de message de maj
	/* 06/04/2009 - modif SPS :gestion de la zone de message*/
	$('update_result').update("");
	$('update_result').hide();

	// On grise le titre
    // 15/09/2010 OJT : Correction bz16854, on applique un style CSS pour simuler le disabled
    if( $('currentProductLabel') != null )
    {
        if( activate == 0 ){
            $('currentProductLabel').className = "texteGrisBoldDisabled";
        }
        else{
            $('currentProductLabel').className = "texteGrisBold";
        }
    }

	// maj 14/03/2008 : benjamin : désactivation de tous les champs input du formulaire "flat_file_form". BZ6243
	var f = $("flat_file_form");
	if(f) 
	{
		var champs = f.getElementsByTagName("input");
		for(var i=0, n=champs.length; i<n; i++)
                {
                    //$("exclusion_"+i).disabled = ($F("period_"+i) == "day");
                    // Le paramètre data chunks n'est pas à désactiver
                    pos = champs[i].name.indexOf( "data_chunks_" );
                    pos2 = champs[i].id.indexOf( "save_" );
                    pos3 = champs[i].id.indexOf( "reset_" );
                    pos4 = champs[i].id.indexOf( "activate_alarm_system" );

                    if (pos == -1 && pos2 == -1 && pos3 == -1 && pos4 == -1)
                    {
                        champs[i].disabled = !(status);
                    }
                }

                // On désactive également les select pour être cohérents
                var champs = f.getElementsByTagName("select");
		for(var i=0, n=champs.length; i<n; i++)
                {
                    // Le paramètre data collection frequency et granularity ne sont pas à désactiver
                    pos = champs[i].name.indexOf( "data_collection_freq_" );
                    pos2 = champs[i].name.indexOf( "granularity_" );
                    if (pos == -1 && pos2 == -1)
                    {
                        champs[i].disabled = !(status);
                    }
                }

                // Correction du BZ 16159 - On vérifie que period type est bien hour sinon on désactive le champs alarm exclusion
                var id_list = $F('id_list').split(";");
                if( !status )
                {
                    for(i = 0; i < id_list.length; i++)
                    {
                        $("exclusion_"+id_list[i]).disabled = !(status);
                    }
                }
                else
                {
                    for(i = 0; i < id_list.length; i++)
                    {
                        pos2 = champs[i].name.indexOf( "granularity_" );

                        $("exclusion_"+id_list[i]).disabled = ($F('period_'+id_list[i]) == "day");
                    }
                }

                if( $F("activate_sa") == 0 && !status )
                {

                    $('save_btn').disabled  = "disabled";
                    $('reset_btn').disabled = "disabled";
                }
                else
                {

                     $('save_btn').disabled  = "";
                     $('reset_btn').disabled = "";
                }
	}
}

/********************
* Remet le formulaire à Zéro
********************/
function resetForm()
{
	// Reset du formulaire	
	$('flat_file_form').reset();

	// Suppression de la zone de message de maj
	/* 06/04/2009 - modif SPS :gestion de la zone de message*/
	$('update_result').update("");
	$('update_result').hide();
	
	// Changement des valeurs associées à la période en fonction de sa valeur initiale
	var id_list = $F('id_list').split(';');
	
	for (var i=0;i<id_list.length;i++)
	{
		changePeriod($F('period_'+id_list[i]), id_list[i]);
                // 21/09/2010 NSE bz 18033 : ajout d'un paramètre
		validateTemporization($('tempo_'+id_list[i]), id_list[i],null);
	}
}

/********************
* Modification de la période
********************/
function changePeriod(period_value, line_id)
{

	$("exclusion_"+line_id).disabled = (period_value == "day");

	// On change le label de la temporization
	(period_value == "hour") ? period_label = _A_SETUP_SYSTEM_ALERTS_TIME_LABELS[0] : period_label = _A_SETUP_SYSTEM_ALERTS_TIME_LABELS[1];

	$('tempo_'+line_id+'_label').innerHTML = "&nbsp;"+period_label.toLowerCase()+"(s)";

	// En mode "day", on desactive la verification sur les valeurs d'exclusion	
	if (period_value == "day") {

		showHideErrors('error_sys_alerts_'+line_id, line_id, "");
            // 21/09/2010 NSE bz 18033 : ajout d'un paramètre
		validateTemporization('tempo_'+line_id, line_id,null);
	}
	else	// En mode "hour", on reactive la verification
	{
            // 21/09/2010 NSE bz 18033 : ajout d'un paramètre
		validateExclusion($('exclusion_'+line_id), line_id,null);
	}
}

/********************
* Vérification de la tempo
*
* 21/09/2010 NSE bz 18033 : ajout du paramètre error pour cumuler l'affichage des erreurs tempo et exclusion.
********************/
function validateTemporization(tempo, line_id, error)
{
    // 21/09/2010 NSE bz 18033 : mémorisation de la valeur passée dans error
    var error0=error;
    if(error0==null)
        error='';
    if($F(tempo) == ""){
        // 17/09/2010 NSE bz 18027 : ajout du message d'erreur
        showHideErrors('error_sys_alerts_'+line_id, line_id, error+_A_SETUP_SYSTEM_ALERTS_ERROR_TEMPO_NAN+"\n");
        return;
    }

    if (isNaN($F(tempo)) || !RegExp(/^\d+$/).test($F(tempo)) || ($F(tempo) < 1))// la temporization doit être un entier supérieur à 1
    {
        // 21/09/2010 NSE bz 18033 : cumul des erreurs
        error = error+_A_SETUP_SYSTEM_ALERTS_ERROR_TEMPO_NAN+"\n";
    }

    // On active / désactive les erreurs sur la temporization
    showHideErrors('error_sys_alerts_'+line_id, line_id, error);
    // 21/09/2010 NSE bz 18033 : si l'appel à validateTemporization n'a pas été fait à partir de validateExclusion, on lance validateExclusion
    if(error0==null)
        validateExclusion($('exclusion_'+line_id),line_id,error);
}

// 11/06/2010 - MPR : DE Source Availability - Contrôle sur la valeur de Data Chunks
/********************
* Vérification de Data Chunks
********************/
function validateDataChunks( line_id, data_chunks )
{

        var error = "";
        var value = 0;
        var gran = null;
        var freq = null;

        value = parseFloat($F(data_chunks));

	if ( isNaN($F(data_chunks)) || !RegExp(/^\d+$/).test(value) || (value < 1) )//Data Chunks doit être un entier supérieur à 1
	{
		error = _A_SETUP_SYSTEM_ALERTS_ERROR_DATA_CHUNKS_NAN+"\n";
	}
        // On test la concordance frequence/granularité/chunk
        gran = $('granularity_'+line_id).value;
        freq = $('data_collection_freq_'+line_id).value;

        if ((gran=='day' && freq == '24' && value > 1) ||
                (gran=='day' && freq == '1' && value > 24) ||
                (gran=='hour' && freq == '24' && value > 24) ||
                (gran=='hour' && freq == '1' && value > 24) ||
                (gran=='hour' && freq == '0.25' && value > 96)
                )
        {
            error = _A_SETUP_SYSTEM_ALERTS_ERROR_DATA_CHUNKS_VALUE_EXCEEDED+"\n";
        }

	// On active / désactive les erreurs sur la temporization
	showHideErrors('error_sys_alerts_'+line_id, line_id, error);

}


/********************
* Vérification de l'exclusion
*
* 21/09/2010 NSE bz 18033 : ajout du paramètre error pour cumuler l'affichage des erreurs tempo et exclusion.
********************/
function validateExclusion(exclusion, line_id,error)
{
    // 21/09/2010 NSE bz 18033 : mémorisation de la valeur passée dans error
    var error0 = error;
    if(error0==null)
        error='';
    // 21/09/2010 NSE bz 18033 : ajout de l'erreur (pour récupérer le message passé en paramètre)
    if ($F(exclusion) == ""){
            showHideErrors('error_sys_alerts_'+line_id, line_id, error);
            return;
    }

    // On splitte les valeurs d'exclusion (séparées par des points-virgules)
    var exclusion_values = $F(exclusion).split(';');

    for (var i=0;i<exclusion_values.length;i++)
    {
            if (exclusion_values[i].indexOf("-") != -1)	// Cas d'intervalles de valeurs (ex : 0-12)
            {
                    var reg = RegExp(/^\d+-{1}\d+$/);

                    if (!reg.test(exclusion_values[i]))	// On verifie le format (XX-YY)
                    {
                            error += _A_SETUP_SYSTEM_ALERTS_ERROR_EXCLU_FMT+"\n";
                    }
                    else
                    {
                            // On verifie que les 2 limites de l'intervalle sont valides
                            var values_sep = exclusion_values[i].split('-');

                            if (!isValidHour(values_sep[0]) || !isValidHour(values_sep[1])){
                                    error += _A_SETUP_SYSTEM_ALERTS_ERROR_EXCLU_INVALID+"\n";
                            }
                            else
                            {
                                    // On verifie egalement que la valeur de depart est inférieure à la valeur d'arrivée
                                    if (Number(values_sep[0]) > Number(values_sep[1])) error += _A_SETUP_SYSTEM_ALERTS_ERROR_EXCLU_INTERVAL+"\n";
                            }
                    }
            }
            else // Une valeur : on verifie qu'elle est valide
            {
                    if (!isValidHour(exclusion_values[i])) error += _A_SETUP_SYSTEM_ALERTS_ERROR_EXCLU_INVALID+"\n";
            }
    }

    // On active / désactive les erreurs sur l'exclusion
    showHideErrors('error_sys_alerts_'+line_id, line_id, error);
    // 21/09/2010 NSE bz 18033 : si l'appel à validateExclusion n'a pas été fait à partir de validateTemporization, on lance validateTemporization
    if(error0==null)
        validateTemporization($('tempo_'+line_id),line_id, error);
}

/********************
* Vérification de l'heure
********************/
function isValidHour(hour){
	return (!isNaN(hour) && hour >= 0 && hour <= 23) ? true : false;
}

/********************
* Affiche / cache les erreurs
********************/
function showHideErrors(target, line, errors)
{	
	errors = errors.split('\n');			
	var errors_log = Array();

	for (var i=0;i<errors.length;i++)
	{
		if(errors[i] != "") errors_log.push(errors[i]);
	}
	
	if (errors_log.length > 0)
	{
        // 14/09/2010 OJT : Correction bz17844, mise du display a vide au lieu de block
		$('error_row_'+line).style.display = '';
		$(target).innerHTML = errors_log.join('<br />');
	}
	else
	{
		$(target).update("&nbsp;");
		if (($('error_sys_alerts_'+line).innerHTML == "&nbsp;") )
		{
			$('error_row_'+line).style.display = 'none';
		}
	}	
	$('save_btn').disabled = getButtonsStatus();
}

/********************
* Retourne le statut des boutons
********************/
function getButtonsStatus()
{
	var id_list = $F('id_list').split(';');
	
	var disable = false;

	for (var i=0;i<id_list.length;i++)
	{
        // 17/09/2010 NSE bz 18027 : affiché avec '' et non block
		if ($('error_row_'+id_list[i]).style.display == "") 
            disable = true;
	}
	return disable;
}

/********************
* Poste le formulaire via ajax
********************/
function sendData()
{
	var url		= 'setup_system_alerts_update.php';
	var pars	= Form.serialize('flat_file_form')+"&product=<?=$product?>";

	//var target	= 'update_result';
	var myAjax	= new Ajax.Request(url, {
		method: 'get', 
		parameters: pars, 
		onComplete: function(data) {

			callback(data);
			/* 06/04/2009 - modif SPS :gestion de la zone de message*/
			$('update_result').show();
		}
	});
}

/********************
* Sera éxécutée après la requête Ajax qui poste le formulaire
********************/
function callback(data)
{
	if(data.responseText == "success"){
		$('update_result').className = 'okMsg';
		$('update_result').update(_A_SETUP_SYSTEM_ALERTS_UPDATE_SUCCESS);
	}
	else if(data.responseText == "failure"){
		$('update_result').className = 'errorMsg';
                // 17/09/2010 NSE bz 17977 : ajout du message d'erreur
                $('update_result').update(_A_SETUP_SYSTEM_ALERTS_UPDATE_FAIL);
	}
	else{
		alert(data.responseText);
        // 11/02/2011 BBX BZ 19187 : Vu que la sauvegarde est effectué ici aussi, on affiche succes
        $('update_result').className = 'okMsg';
		$('update_result').update(_A_SETUP_SYSTEM_ALERTS_UPDATE_SUCCESS);
}
}
//-->
	</script>


<style>

th {
	font-weight:bold;
	font-size:9pt;
	padding:5px;
	border-top:1px solid #898989;
	border-left:1px solid #898989;
        border-collapse:collapse;
	/*border-top:4px solid #aabcfe;*/
}

</style>
</head>
<body>
	<table border="0" width="800px" cellspacing="0" cellpadding="10px" align="center">
		<tr>
			<td align="center">
				<img alt="" src="<?=NIVEAU_0?>images/titres/setup_system_alerts.gif"/>
			</td>
		</tr>
		<tr>
		<td align="center" class="tabPrincipal">
			<div style="position:relative">
				<?php
                // 14/09/2010 OJT : Correction bz17839, si mono_produit, on
                // n'affiche pas le retour au choix du produit
                if( count( ProductModel::getActiveProducts() ) > 1 )
                {
                    // 06/04/2009 SPS : adaptation style pour IE8
                    // 15/09/2010 OJT : Correction bz16854, utilisation d'une class CSS pour simuler le disabled
                    $infosProduct = getProductInformations( $product );
                    $defaultCurrentProductLabelClassName = 'texteGrisBold';
                    if( get_sys_global_parameters( 'alarm_systems_activation', '', $product ) != 1 ){
                        $defaultCurrentProductLabelClassName = 'texteGrisBoldDisabled';
                    }
				?>
                    <div id="system_alerts_configuration">
                        <!-- 06/04/2009 - modif SPS : adaptation style pour IE8 -->
                        <div class="infoBox" style="float:right;margin-bottom:5px;">
                            <table>
                                <tr>
                                    <td>
                                        <span id="currentProductLabel" class="<?php echo $defaultCurrentProductLabelClassName; ?>">Current Product&nbsp;:&nbsp;<?=$infosProduct[$product]['sdp_label']?></span>
                                    </td>
                                    <td>
                                        <a href="<?php echo basename(__FILE__); ?>">
                                            <img src="<?=NIVEAU_0?>images/icones/change.gif" border="0" alt="" onmouseover="popalt('<?=__T('A_U_CHANGE_PRODUCT')?>')" />
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
			<?php
                }
				/* 06/04/2009 - modif SPS : adaptation style pour IE8 */
			?>
			<br style="clear:both;"/>
		<?php
                                // 10/06/2010 - DE Source Availability
			$sql = "SELECT * FROM sys_definition_flat_file_lib WHERE on_off = 1 ORDER BY id_flat_file DESC";
			$req = $database->execute($sql);
                                $nb_results = $database->getNumRows();
                                if ($nb_results == 0) {

				echo $_A_SETUP_SYSTEM_ALERTS_NO_DATA_FOR_FLAT_FILE;
			}
			else 
			{
                                    $activate_sa = ( get_sys_global_parameters("activation_source_availability", 0, $product) ) ? true : false;
                                    $activate_sys_alerts =  (get_sys_global_parameters("alarm_systems_activation", 0, $product) ) ? true : false;
                                    $table_header = ($activate_sa) ? explode(";",$_A_SETUP_SYSTEM_ALERTS_FORM_LABELS_WITH_SA) : explode(";", $_A_SETUP_SYSTEM_ALERTS_FORM_LABELS);
                                    $disable_system_alerts = ( $activate_sys_alerts ) ? "" : "disabled";
                                    $disable_save = ( !$activate_sa && !$activate_sys_alerts ) ? "disabled" : "";
		?>
                                <div align="center">
			<form id="flat_file_form" name="flat_file_form" method="get" action="setup_system_alerts_update.php">
                            <table border="0" cellspacing="0" cellpadding="2.5px" >
                                            <tr class="texteGrisBold" >
                                                <th></th>
                                                <?=($activate_sa) ? "<th colspan='3' align='center' >{$_A_SETUP_DATA_FILES_TABLE_TITLE_SA}</td>" : ""; ?>
                                                <th colspan="3"  align="center" style = "border-right:1px solid #898989;border-collapse:collapse;" >System Alerts</th>
				</tr>
                                            <tr class="texteGrisBold" align="center" >
			<?php
                                                    // Construction du Header du Tableau
                                                    $nb = count($table_header);
                                                    for($i=0;$i < $nb - 1; $i++ )
                                                    {
                                                        $icone_info = "";
                                                        // maj 02/08/2010 - MPR : Correction du bz17154 - Ajout d'une icone d'information pour Data Granularity
                                                        if($i == 1)
                                                        {
                                                            $icone_info = '<img alt="" src="'.NIVEAU_0.'/images/icones/cercle_info.gif" onmouseover="popalt(\''.$_A_SETUP_DATA_FILES_GRANULARITY_INFO.'\')" />';
                                                    }
                                                        echo "<th width='150px'>{$table_header[$i]}&nbsp;{$icone_info}</th>";
                                                    }

                                                     echo "<th width='150px' style = 'border-right:1px solid #898989;border-collapse:collapse;'>{$table_header[$nb-1]}&nbsp;<img src='".NIVEAU_0."/images/icones/cercle_info.gif' onmouseover='popalt(\"{$_A_SETUP_SYSTEM_ALERTS_INFO_EXCLU_FMT}\")' /></th>";
                                                  ?>
                                            </tr>
                                            <?php


                                            // Récupération du commentaire pour l'icone d'info pour activer System Alerts
                                            $query = "SELECT comment FROM sys_global_parameters WHERE parameters = 'alarm_systems_activation'";
                                            $comment_alert_sys_activation = $database->getOne($query);

                                            if( $activate_sa )
                                            {
                                                // Récupération des valeurs possibles de Data Collection Frequency
                                                // array(0.25=>'15mn';1=>'Hour';24=>'Day') (L'unité est l'heure d'où un jour = 24h)
                                                $data_collect_freq_v = explode(";",$_A_SETUP_DATA_FILES_DATA_COLLECTION_FREQUENCY_VALUES);
                                                $data_collect_freq_values = array();
                                                foreach($data_collect_freq_v as $v)
                                                {
                                                    $t = explode("|",$v);
                                                    $data_collect_freq_values[ $t[0] ] = $t[1];
                                                }
                                            }

                                            $ta_labels = explode(";", $_A_SETUP_SYSTEM_ALERTS_TIME_LABELS);

                                            $id_list = array();
                                            $line = 0;
                                            // pour chaque type de fichier
                                            $style_1 = "background-color:#ddd";
                                            $style_2 = "background-image : url('../images/fonds/fond_selecteur.gif')";
                                             $i=1;
                                            $border_right = "";
                                            while ($row = $database->getQueryResults($req,1))
                                            {
                                                    if( $i == $nb_results )
                                                    {
                                                        $border_right = "border-bottom:1px solid #898989;border-collapse:collapse;";
                                                    }
                                                    $color = (is_int($line/2)) ?  $style_1 : $style_2 ;
                                                    $line++;
                                                    $line_id            = $row['id_flat_file'];

                                                    $flat_file_name     = $row['flat_file_name'];
                                                    $period		= $row['period_type'];

                                                    $temporization      = $row['alarm_missing_file_temporization'];

                                                    $id_list[]          = $line_id;

					// Ligne d'affichage des données
                                                    echo '<tr id="line_'.$line_id.'" style="'.$color.';text-align:center;">';
                                                    // Type de fichiers
                                                    echo '<th style="'.$border_right.'" id="file_'.$line_id.'" class="texteGris" style="font:8pt">'.$flat_file_name.'</th>';
					
                                                    // Paramètres Source Availability
                                                    if( $activate_sa )
                                                    {
                                                        $granularity        = $row['granularity'];
                                                        $data_collect_freq  = $row['data_collection_frequency'];
                                                        $data_chunks        = $row['data_chunks'];
					
					
                                                        // Granularity
                                                        echo '<th style="'.$border_right.'">';
                                                        echo '<select id="granularity_'.$line_id.'" name="granularity_'.$line_id.'" class="zoneTexteStyleXP" onChange="validateDataChunks( '.$line_id.', data_chunks_'.$line_id.' )">';
                                                        foreach($ta_labels as $label )
                                                        {
                                                            echo '<option value="'.strtolower($label).'" '.(($granularity == strtolower($label) ) ? "selected" : "").'>'.$label.'</option>';
                                                        }
                                                        echo '</select>';
                                                        echo '</th>';

                                                        // Data Chunks Frequency
                                                        echo '<th style="'.$border_right.'">';
                                                        echo'<select id="data_collection_freq_'.$line_id.'" class="zoneTexteStyleXP" name="data_collection_freq_'.$line_id.'" class="zoneTexteStyleXP" onChange="validateDataChunks( '.$line_id.', data_chunks_'.$line_id.' );">';
                                                        foreach($data_collect_freq_values as $k=>$v)
                                                        {
                                                            echo '<option value="'.$k.'" '.(($data_collect_freq == $k) ? "selected" : "").'>'.$v.'</option>';
                                                        }
                                                        echo '</select>';
                                                        echo '</th>';

                                                        echo "<th style='{$border_right}'>";
                                                        echo "<input type='text' name='data_chunks_{$line_id}' id='data_chunks_{$line_id}' class='zoneTexteStyleXP' value='{$data_chunks}' name='data_chunks_{$line_id}' size='2' onKeyUp='validateDataChunks( {$line_id}, this );'/> </th>";
                                                        echo "</th>";
                                                    }

                                                    // Paramètres System Alerts
                                                    // Période
                                                    echo '<th style="'.$border_right.'">';
                                                    echo '<select id="period_'.$line_id.'" name="period_'.$line_id.'" class="zoneTexteStyleXP" onChange="changePeriod(this.value, '.$line_id.')" '.$disable_system_alerts.'>';
                                                    foreach($ta_labels as $label )
                                                    {
                                                        echo '<option value="'.strtolower($label).'" '.(($period == strtolower($label) ) ? "selected='selected'" : "").'>'.$label.'</option>';
                                                    }
                                                    echo '</select>';
                                                    echo '</th>';
					if ($period == "day") $temporization /= 24;
					
                                                    // Temporisation
                                                    echo '<th style="text-align:left;'.$border_right.'padding-left:20px;"><input type="text" id="tempo_'.$line_id.'" name="tempo_'.$line_id.'" class="zoneTexteStyleXP" size="4" value="'.$temporization.'" onKeyUp="validateTemporization(this, '.$line_id.',null)" '.$disable_system_alerts.'/>
                                                            <span id="tempo_'.$line_id.'_label" name="tempo_'.$line_id.'_label" class="texteGris" style="font:8pt">&nbsp;'.strtolower($period).'(s)</span></th>';
					
                                                    // Exclusion des Heures
                                                    echo '<th style="'.$border_right.'border-right:1px solid #898989;border-collapse:collapse;">
                                                            <input type="text" id="exclusion_'.$line_id.'" name="exclusion_'.$line_id.'"  class="zoneTexteStyleXP" value="'.$row['exclusion'].'" onblur="validateExclusion(this, '.$line_id.',null)" '.(($period == "day") ? "disabled" : "").' '.$disable_system_alerts.'/>
                                                          </th>';
					echo '</tr>';

					// Ligne d'affichage des erreurs
					echo '<tr id="error_row_'.$line_id.'" style="display:none">';
                                                    echo '	<td colspan="7" align="center"><span id="error_sys_alerts_'.$line_id.'" class="texteGris" style="color:red;">&nbsp;</span></td>';
					echo '</tr>';

                                                    $i++;
				}

				echo '<input type="hidden" id="activate_sa" value="'.(($activate_sa) ? 1 : 0).'" />';
				echo '<input type="hidden" id="activate_sys_alerts" value="'.(($activate_sys_alerts) ? 1 : 0).'" />';
				echo '<input type="hidden" id="id_list" value="'.implode(";", $id_list).'"/>';
			?>
                                    </table>
                                    <br />
                                    <div id="system_alerts_activation" >
                                        <p align="left" class="texteGris">
                                            <input type="checkbox" id="activate_alarm_system" <?=(get_sys_global_parameters('alarm_systems_activation','',$product) == 1) ? "checked" : ""?> onClick="updateAlarmSystemStatus(this.checked)"/>
                                            <label for="activate_alarm_system">
                                                &nbsp;<?=$_A_SETUP_SYSTEM_ALERTS_ACTIVATE_LABEL?>
                                                &nbsp;<img alt="" src="<?=NIVEAU_0?>/images/icones/cercle_info.gif" onmouseover="popalt('<?=$_A_SETUP_SYSTEM_ALERTS_ACTIVATION_SYSTEM_ALERTS?>')" />
                                            </label>
                                         </p>
                                    </div>
                                    <div align="center">
					<input type="button" id="reset_btn" class="bouton" value="<?=$_A_SETUP_SYSTEM_ALERTS_BT_RESET?>" onClick="resetForm()" <?=$disable_save?> />
					<input type="button" id="save_btn" class="bouton" value="<?=$_A_SETUP_SYSTEM_ALERTS_BT_SAVE?>" onClick="sendData()" <?=$disable_save?> />
					<div id="update_result" style="margin-top:5px" class="texteGris"></div>
                                    </div>
			</form>
                                </div>
                            </div>
		<?php
			}
		?>
		</td>
		</tr>
	</table>

</body>
</html>