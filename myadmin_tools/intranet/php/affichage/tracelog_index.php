<?
/**
 * @version 5.1
 *
 * 03/08/2010 OJT : Correction bz16852 (on n'affiche pas les logs sans module)
 * 14/11/2011 ACS BZ 24508 Table with tracelog messages is too large
 *
 */
/*
 *	@cb40000@
 *
 *	14/11/2007 - Copyright Acurio
 *
 *	Composant de base version cb_4.0.0.00
 *
 *	maj 16/04/2008 Benjamin : Message si il y a des process "Critical" BZ6290
 *	maj 16/04/2008 Benjamin : la valeur par défaut est désormais "Info" BZ6290
 *	maj 14/03/2008 - benjamin : disposition du formulaire sur 2 lignes + agrandissement des champs date et message. BZ6282
 *	maj 17:01 03/03/2008 - maxime : Evolution du TraceLog -> Ajout d'un filtre sur les messages à afficher
 * 	08/06/2009 SPS :
 *		- on limite le nombre de caractères du message affiché
 *
 *	07/07/2009 GHX
 *		- Correction du BZ 10343 [REC][T&A Cb 5.0][TRACELOG]: le choix all products n'est pas pris en compte
 *		- Correction du BZ 10344 [REC][T&A Cb 5.0][TRACELOG]: on devrait arriver sur all products au 1er affichage du Tracelog
 *	17/07/2009 GHX
 *		 -Correction du BZ 10530 [REC][T&A Cb 5.0][Tracelog]: le filtre avec le module 'Data Compute' ne fonctionne pas
 *
 *	23/07/2009 BBX : modification de la détection de messages critiques mal migrés en CB 5.0. BZ 10606
 *	09/03/2010 NSE bz 14795 ajout du paramètre week_starts_on_monday
 *	23/04/2010 NSE bz 15182 : ajout du message complet au survol du message coupé
 *  21/05/2010 OJT Ajout de la gestion des indcateurs de santé
 *  27/07/2010 OJT Correction bz16790
 *  17/08/2010 MMT
 *     - bz 16749 Firefox compatibility use getAttribute for popalt(alt_on_over)
 *     - bz 16753 changement de calendrier pour utiliser le mode datePicker
*/
?>
<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*/
?>
<?php
/**
 * Gère les données de paramétrage qui servent à connecter l'application
 * aux bases de données tiers et répertoire racine qui contient des flat file
 */

// une fonction pour trier un tableau à double dimension
// 15:21 07/07/2009 GHX
// Correction du BZ 10343 [REC][T&A Cb 5.0][TRACELOG]: le choix all products n'est pas pris en compte
// On passe le tableau par référence maintenant et invertion entre le 1 et -1 
 function array2d_sort(&$array, $colname) {
	
	$function_code = "
		if (\$a['$colname'] == \$b['$colname'])	return 0;
		return (\$a['$colname'] < \$b['$colname']) ? 1 : -1;
	";
	
	return usort($array,create_function('$a,$b',$function_code));
}


// maj 04/03/2008 - maxime :  Ajout d'un filtre dans le TraceLog ce qui implique l'ajout des paramètres severity / module / message / date
// maj 27/05/2008 - maxime : Modification de la fonction print_log_ast : Correction du bug 6762
// maj 29/01/2009 - stephane : gestion multi-produit
// maj 29/01/2009 - stephane : cette fonction est sortie de edw_functions.php pour être mise ici
/*
* fonction d'affichage de la log au format HTML
* @param : $table_champ_log contient la liste des champs de la log à affciher
* @param : $limit :limite du nombre de lignes à retourner
* @param : $level : correspond au champ type message qui contient le niveau de support associé au message
* @param : $severity : correspond  au niveau de sévérité du ou des messages recherchés
* @param : $module : correspond à la valeur de la liste déroulante module qui contient le module du ou des messages recherchés
* @param : $message : correspond au champ input text message qui correspondant à une partie ou au contenu du ou des  messages recherchés
* @param : $date : correspond au champ input text date qui contient toute ou partie de la date du ou des messages recherchés
* @param : $id_product : id du produit sur lequel on cherche les logs. =0 si on cherche sur tous les produits
*/
function print_log_ast($limit, $level = "support_1",$severity = '',$_module = '', $message = '',$date = '', $id_product = 0) {

	global $module, $products;

	$severity_color_display["Critical"]	= "#FF0000";
	$severity_color_display["Major"]	= "#FF00AA";
	$severity_color_display["Warning"]	= "#FF6600";
	$severity_color_display["Info"]		= "";
	
	$condition = "";
	
    // 03/08/2010 OJT : Correction bz16852
    $condition_tmp[] = " trim(module) != ''";

	// Traitement de la date 
	if ($date != '') {		
		$date = str_replace('*','%',trim($date,'*'));
		$condition_tmp[] = " message_date ILIKE '%$date%'";
	}
	
	// Traitement des messages recherchés directement 
	// Principe : Le caractère * correspond à un % dans un LIKE ou un ILIKE ( Ne prend pas en compte la casse )
	if ($message != '') {
		$message = str_replace('*','%',trim($message,'*'));
		$condition_tmp[] = " message ILIKE '%$message%'";				
	}
	
	// Traitement des différents niveaux (par défault 'support_1') des messages
	if ($level != 'ALL') {
		$condition_tmp[] = " type_message='$level'";
	} 
	
	// Traitement de la sévérité du ou des messages d'informations 
	if ($severity != "") {
		if ($severity != 'All')
			$condition_tmp[] = " severity = '$severity'";
	} else
		$condition_tmp[] = " severity = 'Critical'";
	
	if ( $_module != "" and $_module != 'All' )
		$condition_tmp[]  = " module ILIKE '$_module'";

	// On construit la condition WHERE de la requête en fonction des champs utilisés dans le filtre
	// 09:35 17/07/2009 GHX
	// Correction du BZ 10530 [REC][T&A Cb 5.0][Tracelog]: le filtre avec le module 'Data Compute' ne fonctionne pas
	// Modification de la créatoin de la condition de la requete SQL
	if (count($condition_tmp)>0)
		$condition = "WHERE ".implode(' and ', $condition_tmp);
	
        // maj 12/07/2010 - MPR : Correction du bz 6835 - On ne trie plus les éléments par oid mais par date puis par oid
        $order_by = "ORDER BY message_date DESC, oid DESC";

	if ($id_product == 0) {
		// on cherche sur TOUS les produits
		$res = array();
		// on cherche sur toutes les bases
		foreach ($products as $prod) {
                        // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
			$database	= Database::getConnection($prod['sdp_id']);
                        // maj 12/07/2010 - MPR : Correction du bz 6835 - On ne trie plus les éléments par oid mais par date puis par oid
			$query	= "SELECT *,'{$prod['sdp_label']}' as sdp_label FROM sys_log_ast $condition $order_by limit $limit";
			$res_prod	= $database->getAll($query);
			if ($res_prod)
				$res = array_merge($res,$res_prod);
		}
		
		// on ordonne le tableau général
		array2d_sort($res,'message_date');
		// on en prend que les premiers elements
		$res = array_slice($res,0,$limit);
	} else {
                // maj 12/07/2010 - MPR : Correction du bz 6835 - On ne trie plus les éléments par oid mais par date puis par oid
		$query	= "SELECT * FROM sys_log_ast $condition $order_by limit $limit";
                // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
		$database	= Database::getConnection($id_product);
		$res		= $database->getAll($query);
	}

	
	// On vérifie qu'il y a au moins un résultat
	if ($res) {
	
		// Construction du header du tableau des résultats
		echo "<tr>
				<th bgcolor='#eeeeee'>Date</th>
				<th bgcolor='#eeeeee'>Severity</th>
				<th bgcolor='#eeeeee'>Message</th>
				<th bgcolor='#eeeeee' class='col_product'>Product</th>
				<th bgcolor='#eeeeee'>Module</th>
			</tr>";

		// on affiche les données
		$sys_module = get_sys_global_parameters("module");
		$txt = '';
		$impair = true;
		foreach ($res as $row) {
			if ($impair)	$impair = false;
			else			$impair = true;
			$classTR = ($impair) ? 'fondGrisClair' : '';
			
			/* 08/06/2009 SPS : on limite le nombre de caractères du message affiché */
			// 23/04/2010 NSE bz 15182 : ajout du message complet au survol du message coupé
			// + modification longueur message affiché (pour éviter retour à la ligne)
			// 18/10/2011 ACS BZ 24253: Double quotes are not supported in tracelog message
			if (strlen($row['message']) > 95){ $message = substr($row['message'],0,90)." ...";$title=htmlspecialchars($row['message']);}
			else $message = $row['message'];
			// 23/04/2010 NSE bz 15182 : on ajoute le title sur la cellule du tableau
			// 14/11/2011 ACS BZ 24508 Table with tracelog messages is too large
			$txt .= "
				<tr class='texteGris $classTR' style='color:{$severity_color_display[$row['severity']]};' align='left'>
					<td nowrap='nowrap'>{$row['message_date']}&nbsp;</td>
					<td nowrap='nowrap'>{$row['severity']}&nbsp;</td>
					<td".(isset($title)?" title=\"$title\"":'').">{$message}&nbsp;</td>
					<td class='col_product'>{$row['sdp_label']}&nbsp;</td>
					<td>".(($row['module'] == $sys_module) ? __T('A_TRACELOG_MODULE_LABEL_COMPUTE') : $row['module'])."&nbsp;</td>
				</tr>	
			";
			// 23/04/2010 NSE bz 15182 : on désaffecte la variable
			if(isset($title))
				unset($title);
		}

		$txt = str_replace(' align="center">Info&nbsp;<','>Info&nbsp;<',$txt);
		echo $txt;
		
	} else {
		echo "<tr align='center'><td class='texteGris'  style='color:#FF0000'> <td>No Results</td></tr>";
	}
}



session_start();
require_once( dirname(__FILE__). "/../../../../php/environnement_liens.php");
require_once( REP_PHYSIQUE_NIVEAU_0 . "intranet_top.php");
require_once( REP_PHYSIQUE_NIVEAU_0.'class/DownloadLog.class.php' );

$products = ProductModel::getActiveProducts();

// on regarde si un produit a été définit
if (isset($_GET['id_product']))	$id_product = $_GET['id_product'];
if (isset($_POST['id_product']))	$id_product = $_POST['id_product'];

// on prend le produit master si aucun produit n'est définit
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
$db = Database::getConnection();
// 15:29 07/07/2009 GHX
// Correction du BZ 10344 [REC][T&A Cb 5.0][TRACELOG]: on devrait arriver sur all products au 1er affichage du Tracelog
// On ne sélectionne plus le master par défaut mais uniqument si on a un seul produit
if ( count($products) == 1) $id_product = $db->getone("select sdp_id from sys_definition_product where sdp_master=1 limit 1");

// on se connecte à la db du produit
if ($id_product != 0)
    // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
    $db_product = Database::getConnection ($id_product);
	
	
	
/******************************
* 16/11/2009 BBX : Ajout du code pour Download Log
******************************/	
// Définition des valeurs par défaut
$DOWNLOADLOG = Array();

// Date / Period
$DOWNLOADLOG['date']['date_debut'] = date('d/m/Y');
$DOWNLOADLOG['date']['date_fin'] = date('d/m/Y');
$DOWNLOADLOG['date']['until'] = false;

// Content
$DOWNLOADLOG['content']['application_daemon']['value'] = DownloadLog::isLogSelected( 'application_daemon', 1 );
$DOWNLOADLOG['content']['application_daemon']['label'] = __T('A_DOWNLOAD_LOG_APPLICATION_DAEMON_LABEL'); 
$DOWNLOADLOG['content']['tracelog']['value'] = DownloadLog::isLogSelected( 'tracelog', 1 );
$DOWNLOADLOG['content']['tracelog']['label'] = __T('A_DOWNLOAD_LOG_TRACELOG_LABEL'); 
$DOWNLOADLOG['content']['topology_daemon']['value'] = DownloadLog::isLogSelected( 'topology_daemon', 1 );
$DOWNLOADLOG['content']['topology_daemon']['label'] = __T('A_DOWNLOAD_LOG_TOPOLOGY_DAEMON_LABEL'); 
$DOWNLOADLOG['content']['version_history']['value'] = DownloadLog::isLogSelected( 'version_history', 1 );
$DOWNLOADLOG['content']['version_history']['label'] = __T('A_DOWNLOAD_LOG_VERSION_HISTORY_LABEL'); 
$DOWNLOADLOG['content']['global_parameters']['value'] = DownloadLog::isLogSelected( 'global_parameters', 1 );
$DOWNLOADLOG['content']['global_parameters']['label'] = __T('A_DOWNLOAD_LOG_GLOBAL_PARAMETERS_LABEL');
$DOWNLOADLOG['content']['health_indicator']['value'] = DownloadLog::isLogSelected( 'health_indicator', 1 );
$DOWNLOADLOG['content']['health_indicator']['label'] = __T('A_DOWNLOAD_LOG_HEALTH_INDICATOR_LABEL');
$DOWNLOADLOG['content']['file_permissions']['value'] = DownloadLog::isLogSelected( 'file_permissions', 0 );
$DOWNLOADLOG['content']['file_permissions']['label'] = __T('A_DOWNLOAD_LOG_FILE_PERMISSIONS_LABEL');

// 29/03/2011 NSE Merge 5.0.5 -> 5.1.1 Suppression topology_statistics, partition_statistics

// Comments
$DOWNLOADLOG['content']['application_daemon']['comment'] = __T('A_DOWNLOAD_LOG_APPLICATION_DAEMON_COMMENT');
$DOWNLOADLOG['content']['tracelog']['comment'] = __T('A_DOWNLOAD_LOG_TRACELOG_COMMENT');
$DOWNLOADLOG['content']['topology_daemon']['comment'] = __T('A_DOWNLOAD_LOG_TOPOLOGY_DAEMON_COMMENT');
$DOWNLOADLOG['content']['version_history']['comment'] = __T('A_DOWNLOAD_LOG_VERSION_HISTORY_COMMENT');
$DOWNLOADLOG['content']['global_parameters']['comment'] = __T('A_DOWNLOAD_LOG_GLOBAL_PARAMETERS_COMMENT');
$DOWNLOADLOG['content']['health_indicator']['comment'] = __T('A_DOWNLOAD_LOG_HEALTH_INDICATOR_COMMENT');
$DOWNLOADLOG['content']['file_permissions']['comment'] = __T('A_DOWNLOAD_LOG_FILE_PERMISSIONS_COMMENT');

/******************************
* Fin Download Log
******************************/	
?>

<style type="text/css">
table.tracelog tr:hover {
	background-color:#FFCA43; 
}
<?php
// 16/11/2009 BBX : ajout du style pour le download log
?>
#downloadLog {
	width:450px;
	float:left;	
}
#tracelog_frame {
    margin:0 auto 5px auto; /* 13/08/2010 OJT : Correction bz16765 pour DE Firefox */
	width:900px;
	position:relative;
}
#filter_frame {
	float:left;
	width:420px; /* 13/08/2010 OJT : Correction bz16765 pour DE Firefox */
}
#calendar_selecteur_date_debut, #calendar_selecteur_date_fin {
	cursor:pointer;
}
<?php 	if ($id_product != 0) echo ".col_product {display:none;}";	?>

</style>

<script src="<?=NIVEAU_0?>js/myadmin_omc.js"></script>
<!-- 18/08/2010 bz 16753 detruit import calendrier precedents -->

<script>
/****
*	Fonction qui demande la génération du log puis propose son téléchargement
****/
function generateLog()
{
	// Récupération du formulaire
	var formValues = $('downloadLogForm').serialize();
	// Animation
	$('download_image').style.display = 'block';
	$('download_button').style.display = 'none';
	// Envoie des valeurs au script de génération
	new Ajax.Request('download_log.php',{
		method:'post',
		parameters:formValues+'&ran'+Math.random()+'='+Math.random(),
		onSuccess: function(res) {
			// Test données reçues
			if(res.responseText != '') 
			{
				// Test de l'url
				new Ajax.Request('download_log.php',{
					method:'post',
					parameters:'url='+res.responseText,
					onSuccess: function(url) {
						if(url.responseText == 'OK') {
							// Vers le fichier
							document.location.href = res.responseText+'?uniq'+Math.random()+'='+Math.random();
						}
						else if(url.responseText == 'SSH_error'){
							alert('<?=__T('A_E_SETUP_PRODUCTS_SSH_MUST_EXIST')?>');
						}
						else {
							// Le fichier n'a pas été généré
							alert('<?=__T('A_DOWNLOAD_LOG_ERROR_OCCURED')?>');
						}
					}
				});
				
			}
			else {
				// Rien à générer
				alert('<?=__T('A_DOWNLOAD_LOG_NOTHING_TO_EXPORT')?>');
			}
			// Fin animation
			$('download_image').style.display = 'none';
			$('download_button').style.display = 'block';
		}
	});
}

/****
*	Fonction qui gère la case "until" et le champ de date de droite
****/
// 17/08/2010 MMT bz 16753 changement de calendrier pour utiliser le mode datePicker
// doit lancer la fonction apres chargement de la page
function manageUntil(disable)
{

	if($('until').checked || disable)
	{
		$('date_fin').disabled = true;
		$('calendar_selecteur_date_fin').style.display = 'none';
	}
	else
	{
		$('date_fin').disabled = false;
		$('calendar_selecteur_date_fin').style.display = 'inline';
	}
}

Event.observe(window, 'load', function() {
   manageUntil(true);
});

// fin modif 17/08/2010 MMT bz 16753


</script>

<div id="container" style="width:100%;text-align:center;">
	<p align="center">
        <img src="<?=NIVEAU_0?>images/titres/trace_log_titre.gif"/>
    </p>
	<div id="tracelog_frame" align="center" class="texteGrisPetit">
		<fieldset style="width:100%;padding-top:5px;">
            <!-- 13/08/2010 OJT : Correction bz16765 pour DE Firefox, modification des marges et des width -->
			<fieldset class="tabPrincipal" style="width:890px;">
				<!-- 16/11/2009 BBX : réorganisation de l'affichage du formulaire du filter du tracelog pour insérer le downloadlog. -->
				<div id="filter_frame">	
					<fieldset style="margin:5px 5px 5px 5px;">
						<legend class="texteGrisBold">Filter&nbsp;</legend>
						<br/>
						<form id="frm_search" method="post" action="tracelog_index.php" class="texteGrisBold">
							<?php
								// maj 14/03/2008 - benjamin : disposition du formulaire sur 2 lignes + agrandissement des champs date et message. BZ6282	
							?>
							<table width="100%" cellpadding="0" cellspacing="0" border="0">
								<?php if (count($products) > 1) { ?>
								<tr>
									<td colspan="3">
										<label for="product">Product&nbsp;</label>
										<br />
										<select name="id_product">
												<option value="0">All products</option>
												<?php
													foreach ($products as $prod) {
														echo "<option value='{$prod['sdp_id']}'";
														if ($prod['sdp_id'] == $id_product) echo " selected='selected'";
														echo ">{$prod['sdp_label']}</option>";
													}
												?>
										</select>
									</td>
								</tr>
								<?php } ?>
								<tr>
									<td>
										<label for="date">Date&nbsp;</label>
										<img src="<?=NIVEAU_0."images/icones/cercle_info.gif"?>" onmouseover="popalt('<?=__T('A_TRACELOG_INTERFACE_INFO_DATE')?>')" />
										<br />
										<input type="text" size="10" name="date" value="<?= $_POST['date'] ?>" />
									</td>
									<td>
										<label for="severity">Severity&nbsp;</label>
										<br />
										<select name="severity">
										<?
										$severities = get_element_log_ast("severity");
										$severities[] = 'All';
										array_multisort($severities);
										foreach($severities as $severity)
										{
											$selected = "";
											if( ( isset( $_POST['parameter'] ) and $severity == $_POST['severity']) )
											$selected = "selected = 'selected'";
											// maj 16/04/2008 Benjamin : la valeur par défaut est désormais "Info"
											elseif( !isset( $_POST['parameter'] ) and $severity == 'Info'){
											$selected = "selected = 'selected'";
											}
											echo "<option value='$severity' $selected >$severity</option>";
										}
										?>
										</select>
									</td>
									<td>
										<label for="module">Module&nbsp;</label>
										<br />
										<select name="module">
										<?
										$_modules = get_element_log_ast("module");
										array_multisort($_modules);
										echo (isset( $_POST['parameter']) and 'All' == $_POST['module']) ? "<option value='All' selected='selected'>All</option>" : "<option value='All'>All</option>";
										foreach($_modules as $_module)
										{
											$selected = "";
											if( isset( $_POST['parameter']) and $_module == $_POST['module'] ){
												$selected = "selected = 'selected'";
											}
											echo "<option value='$_module' $selected>$_module</option>";
										}
										?>
										</select>
									</td>
								</tr>
								<tr>
									<td colspan="3">
										<label for="msg">Message&nbsp;</label>
										<img src="<?=NIVEAU_0."images/icones/cercle_info.gif"?>" onmouseover="popalt('<?=__T('A_TRACELOG_INTERFACE_INFO_MESSAGE')?>')" />
										<br />
										<input type="text" size="57" name="msg" value="<?= $_POST['msg'] ?>" />
									</td>
								</tr>
								<tr>
									<td colspan="2">
										<label for="limit">Limit</label>
										<br />
										<select name="limit">
										<?
										$limits = array(100,200,300,400,500,600,700,800,900,1000);
										foreach($limits as $limit){
											$selected= "";
											if( ( !isset( $_POST['limit'] ) and $limit == 500) or (isset( $_POST['limit']) and $limit == $_POST['limit']) )
												$selected = "selected";
											
											echo "<option value='$limit' $selected >$limit</option>";
										}
										// Boucle for avec pas de 100
										?>
										</select>
									</td>
									<td align="right" valign="bottom">
										<input type="submit" class="bouton" name="parameter" value="Refresh" />
										&nbsp;&nbsp;&nbsp;&nbsp;
									</td>
								</tr>
							</table>
						</form>
					</fieldset>
				</div>
				<?php
				// 16/11/2009 BBX : fin réorganisation
				?>
				
				<?php
				// 16/11/2009 BBX : Ajout du module Downloadlog
				?>
				<div id="downloadLog">
				
					<form id="downloadLogForm">
                    <!-- 13/08/2010 OJT : Correction bz16765 pour DE Firefox, modification des marges -->
					<fieldset style="margin:5px 5px 5px 5px;">
						
						<legend class="texteGrisBold">
							<?=__T('A_DOWNLOAD_LOG_FRAME_LABEL')?>&nbsp;
                    <!-- 17/08/2010 MMT bz 16749 Firefox compatibility use getAttribute for popalt(alt_on_over) -->
							<img src="<?=NIVEAU_0?>images/icones/cercle_info.gif" 
								border="0" 
								onmouseover="popalt(this.getAttribute('alt_on_over'))"
								onmouseout="kill()"
								alt_on_over="<?=__T('A_DOWNLOAD_LOG_GLOBAL_COMMENT')?>" />&nbsp;
						</legend>
						
						<fieldset style="padding:5px;">
						
							<legend class="texteGrisBold"><?=__T('A_DOWNLOAD_LOG_DATE_PERIOD_LABEL')?>&nbsp;</legend>
							
							<!-- 09/03/2010 NSE bz 14795 ajout du paramètre week_starts_on_monday -->
							<input type="hidden" id="week_starts_on_monday" name="week_starts_on_monday" value="1" />
							<table width="100%" cellpadding="5">
								<tr>
									<td>

                              <?php
                                 // 17/08/2010 MMT bz 16753 Utilise JQueryui datePicker pour compatibilité navigateurs
                                 include_once(REP_PHYSIQUE_NIVEAU_0."modules/datePicker_v1.0.0/class/DatePicker.class.php");

                                 $dpDebut = new DatePicker("date_debut");
                                 $dpDebut->setDate($DOWNLOADLOG['date']['date_debut']);
                                 $dpDebut->setInputReadOnly();
                                 echo $dpDebut->generateHTML();
                                 ?>


									</td>
									<td>
                              <!-- 17/08/2010 MMT bz 16749 Firefox compatibility use getAttribute for popalt(alt_on_over) -->
										<input id="until" 
											name="until" 
											type="checkbox" 
											value="1" 
											onmouseup="manageUntil()"
											onmouseover="popalt(this.getAttribute('alt_on_over'))"
											onmouseout="kill()"
											alt_on_over="<?=__T('A_DOWNLOAD_LOG_UNTIL_COMMENT')?>" />
										<label for="until" 
											onmouseup="manageUntil()"
											onmouseover="popalt(this.getAttribute('alt_on_over'))"
											onmouseout="kill()"
											alt_on_over="<?=__T('A_DOWNLOAD_LOG_UNTIL_COMMENT')?>"><?=__T('A_DOWNLOAD_LOG_UNTIL_LABEL')?></label>
                              <!-- 17/08/2010 MMT bz 16749 End of change -->
									</td>
									<td>
                              <?php

                                 // 17/08/2010 MMT bz 16753 Utilise JQueryui datePicker pour compatibilité navigateurs

                                 $dpFin = new DatePicker("date_fin","calendar_selecteur_date_fin");
                                 $dpFin->setDate($DOWNLOADLOG['date']['date_fin']);
                                 $dpFin->setInputReadOnly();
                                 echo $dpFin->generateHTML();
                                 ?>


									</td>
								</tr>
							</table>

						</fieldset>

						<fieldset style="padding:5px;">
						
							<legend class="texteGrisBold"><?=__T('A_DOWNLOAD_LOG_CONTENT_LABEL')?>&nbsp;</legend>
							
							<table width="100%">
								<tr>
								<?php
								$i = 0;
								foreach($DOWNLOADLOG['content'] as $id => $content)
								{
									$checked = ($content['value'] == 1) ? 'checked' : '';
									if($i%2 == 0)	echo '</tr><tr>';
								?>
									<td width="50%">
										<input id="<?=$id?>" name="<?=$id?>" onmouseover="popalt('<?=$content['comment']?>')" onmouseout="kill()" type="checkbox" value="1" <?=$checked?> />
										<label for="<?=$id?>" onmouseover="popalt('<?=$content['comment']?>')" onmouseout="kill()"><?=$content['label']?></label>
									</td>
								<?php
									$i++;
								}			
								
								?>
								</tr>
							</table>
						
						</fieldset>

						<div style="position:relative">
							<br />
							<table width="100%">
								<tr>
									<td>
										<?php
										// choix du produit si multiproduit
										if(count(getProductInformations()) > 1)
										{
										?>
										<select name="product">					
											<option value="all"><?=__T('A_DOWNLOAD_LOG_ALL_PRODUCTS')?></option>
											<?php
											foreach ($products as $prod) {
												echo "<option value='{$prod['sdp_id']}'";
												if ($prod['sdp_id'] == $id_product) echo " selected='selected'";
												echo ">{$prod['sdp_label']}</option>";
											}
											?>
										</select>
										<?php
										}
										?>
									</td>
									<td width="60%">
										<input id="download_button" class="bouton" value="<?=__T('A_DOWNLOAD_LOG_DOWNLOAD_BUTTON')?>" type="button" onclick="generateLog()" />
										<div id="download_image" style="display:none">
											<img src="<?=NIVEAU_0?>images/animation/indicator_snake.gif" border="0" />
											&nbsp;
											<span class="texteGris"><?=__T('A_DOWNLOAD_LOG_GENERATING_MESSAGE')?></span>
										</div>
									</td>
								</tr>
							</table>


						</div>
						
					</fieldset>
					
					</form>
					
				</div>				
				<?php
				// 16/11/2009 BBX : Fin downloadlog
				?>

			</fieldset>

			<br/>
			<br/>

			<div id="results">
				<?php

					// maj 16/04/2008 Benjamin : la valeur par défaut est désormais "Info"
					$severity	= isset($_POST['severity'])	? $_POST['severity']	: 'Info';
					$_module	= isset($_POST['module'])		? $_POST['module']	: 'All';
					$limit	= isset($_POST['limit'])		? $_POST['limit']	: 500;
					$msg		= isset($_POST['msg'])		? $_POST['msg']		: '';
					$date	= isset($_POST['date'])		? $_POST['date']	: '';
					
					// maj 16/04/2008 Benjamin : Message si il y a des process "Critical"
					if ($severity != 'All') {
						$query = "
							SELECT *
							FROM sys_log_ast
							WHERE severity = 'Critical'
								".(($date != '') ? "AND message_date like '%{$date}%'" : "")."
							ORDER BY message_date DESC
							LIMIT {$limit}";
						// 23/07/2009 BBX : modification de la détection de messages critiques mal migrés en CB 5.0. BZ 10606
						$has_critical = false;
						if ($id_product == 0) {
							// TOUS les produits sont sélectionnés, donc on doit chercher sur TOUS les produits
							foreach ($products as $prod) {
                                                                // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
								$db_temp = Database::getConnection($prod['sdp_id']);
								$criticals = $db_temp->getall($query);
								unset($db_temp);
								if (count($criticals) > 0) {
									$has_critical = true;
									break;
								}
							}
						} else {
							// UN SEUL produit est spécifié
							$criticals = $db_product->getall($query);
							if (count($criticals) > 0) $has_critical = true;
						}
						if ( $has_critical && strtolower($severity) != 'critical' )
							echo '<div class="texteRouge">'.__T('A_TRACELOG_INTERFACE_CRITICAL_PROCESSES_LOGGED').'</div>';
					}
				?>

				<table cellspacing="1" width="100%" cellpadding="1" align="center" class='tracelog texteGris'>
					<?php print_log_ast($limit, "support_1", $severity, $_module, $msg, $date, $id_product); ?>
				</table>
				
				<br/>
			</div>
		</fieldset>
	</div>

</div>

</body>
</html>