<?php
/*
	27/08/2009 GHX
		- Correction du BZ 109710
		- Optimisation du code pour tester la présence des fichiers sur un slave distant
	26/10/2009  MPR 
		- Correction du bug 7981 : Ajout d'un message indiquant que les caractères spéciaux suivants sont remplacer par des _
 *      14/09/2010 NSE bz 17838 : mauvais dimensionnement de la liste des topo uploadées : Firefox ne gère pas overflow sur fieldset, ajout d'une div
 *      15/09/2010 NSE bz 17046 : suppression du test sur le type
 *      17/09/2010 NSE bz 17983 : utilisation de force_download
 *      17/09/2010 NSE bz 17838 : la liste des fichiers uploadés est visibles au-dessus du fiedlset pendant le déroulement sous FF
 *11:44 13/10/2010 SCT : BZ 18420 => message d'alerte lors de l'upload de topology
 *		- ajout de l'appel à la méthode "getTimeToCompute" qui retourne les heures, jours, ... à computer
 *		- prise en compte du nombre d'éléments à computer lorsqu'un retrieve a été réalisé sans compute
 *      31/01/2011 MMT bz 20347 : ajout sdp_ssh_port dans appel new SSHConnection
 *      18/03/2011 MMT bz 20191 renomage de fichier si caractères spéciaux apres reopen
 *		09/12/2011 ACS Mantis 837 DE HTTPS support
 *      20/09/2012 MMT DE 5.3 Delete Topology
 * 07/05/2013 : ajout du caractère de séparation tabulation
 * 22/05/2013 : WebService Topology
*/
?>
<?php
session_start();
include_once(dirname(__FILE__).'/../../../../php/environnement_liens.php');
include_once(REP_PHYSIQUE_NIVEAU_0."intranet_top.php");
include_once(REP_PHYSIQUE_NIVEAU_0."class/select_family.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0."class/SSHConnection.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0."class/api/TrendingAggregationApi.class.php");

// Choix du produit seulement, il n'y a pas de choix de la famille
if ( !isset($_GET["product"]) )
{
	$select_family = new select_family($_SERVER['PHP_SELF'], $_SERVER['argv'][0], 'Upload Topology', false, '', 2);
	exit;
}

// Récupère les informations sur les produits
// 24/09/2012 MMT DE 5.3 Delete Topology ajout de $productModel pour test sur version
$productModel = new ProductModel($_GET["product"]);

$productsInformations = getProductInformations();
$productInformation = $productsInformations[$_GET["product"]];

// 05/07/2010 BBX
// On n'autorise plus la mise à jour de topo entre un retrieve et un compute
// BZ 12974
if(!isset($_GET['continue']) && !isset($_GET['action_topo']))
{
    // Gathering needed data
    $lastRetrieve = MasterModel::getLastCompleteRetrieve($_GET["product"]);
    $lastCompute = MasterModel::getLastCompleteCompute($_GET["product"]);
	// 11:44 13/10/2010 SCT : BZ 18420 => message d'alerte lors de l'upload de topology
	// - ajout de l'appel à la méthode "getTimeToCompute" qui retourne les heures, jours, ... à computer
	$arrayTimeToCompute = MasterModel::getTimeToCompute($_GET["product"]);
    $lastRetrieveDate = isset($lastRetrieve['date']) ? $lastRetrieve['date'] : 0;
    $lastComputeDate = isset($lastCompute['date']) ? $lastCompute['date'] : 0;
	

    // Checked Processes
    $checkedProcesses = MasterModel::getCheckedProcesses($_GET["product"]);

    // Running Processes
    $runningProcesses = MasterModel::getRunningProcesses($_GET["product"]);

    // Afficher erreurs ?
    $showError = false;

    // Processus cochés ou en cours ?
    if((count($checkedProcesses) > 0) || (count($runningProcesses) > 0))
    {
        echo '<div class="errorMsg">'.__T('A_UPLOAD_TOPO_FORBIDDEN_PROCESS_RUNNING').'</div>';
        $showError = true;
    }

    // Retrieve lancé sans compute derrière ?
	// 11:44 13/10/2010 SCT : BZ 18420 => message d'alerte lors de l'upload de topology
	//	- ajout de l'appel à la méthode "getTimeToCompute" qui retourne les heures, jours, ... à computer
	//	- prise en compte du nombre d'éléments à computer lorsqu'un retrieve a été réalisé sans compute
    if(count($arrayTimeToCompute) > 0)
    {
        echo '<div class="errorMsg">'.__T('A_UPLOAD_TOPO_FORBIDDEN_RETRIEVE_COMPUTE').'</div>';
        $showError = true;
    }

    // Continue anyway ?
    if($showError)
    {
        echo '<div class="infoBox" style="width:250px;margin:auto;text-align:center;">';
        echo __T('A_UPLOAD_TOPO_FORBIDDEN_CONTINUE_ANYWAY').'<br /><br />';
        echo '<input class="bouton" type="button" onclick="document.location.href=\'?continue=yes&product='.$_GET["product"].'\'" value="&nbsp;&nbsp;'.__T('A_COMMOM_MESSAGE_YES').'&nbsp;&nbsp;" />';
        echo '&nbsp;&nbsp;<input class="bouton" type="button" onclick="document.location.href=\''.NIVEAU_0.'/intranet_homepage_admin.php\'" value="&nbsp;&nbsp;'.__T('A_COMMOM_MESSAGE_NO').'&nbsp;&nbsp;" />';
        echo '</div>';
        return;
    }
}
// FIN BZ 12974

// Si l'utilisateur à cliquer sur le bouton "submit"
if ( isset($_GET['action_topo']) )
{
    // 20/09/2012 MMT DE 5.3 Delete Topology - ajout condition sur action specifique
    if ($_GET['action_topo'] == "upload")
    {
		// Vérification sur le fichier uploadé par l'utilsateur
		$file_name  = $_FILES['downloadfile']['name'];
		$file_size  = $_FILES['downloadfile']['size'];
		$file_type  = $_FILES['downloadfile']['type'];
		$file_error = $_FILES['downloadfile']['error'];
		$msgError = null;


		// 18/03/2011 MMT bz 20191 renomage de fichier si caractères spéciaux
		$file_name = getCommandSafeFileName($file_name);

		/*
		 * Le fichier est trop volumineux
		 * 	- 1 : excède le poids autorisé par la directive upload_max_filesize de php.ini 
		 * 	- 2 : excède le poids autorisé par le champ MAX_FILE_SIZE s'il a été donné 
		 */
		if ( $file_error == 1 ||  $file_error == 2 )
		{
			$msgError = __T('A_UPLOAD_TOPOLOGY_FILE_IS_TOO_BIG');
		}
		elseif ( $file_error == 3 ) // Le fichier n'a été uploadé que partiellement 
		{
			$msgError = __T('A_E_UPLOAD_TOPOLOGY_FILE_PARTIAL');
		}
		elseif ( $file_error == 4 ) // Aucun fichier n'a été uploadé 
		{
			$msgError = __T('A_E_UPLOAD_TOPOLOGY_FILE_MISSING');
		}
		elseif ( $file_size == 0 )
		{
			$msgError = __T('A_E_UPLOAD_TOPOLOGY_FILE_IS_EMPTY');
		}
    }
    // 20/09/2012 MMT DE 5.3 Delete Topology - ajout condition sur action
    else if ($_GET['action_topo'] == "delete")
    {
        $arrayTimeToCompute = MasterModel::getTimeToCompute($_GET["product"]);
        
        if(count(MasterModel::getRunningProcesses($_GET["product"])) > 0){
            $msgError = __T("E_UPLOAD_TOPOLOGY_DELETE_PROCESS_RUNNING");
        }
        else if (count($arrayTimeToCompute) > 0)
        {
            //prevent Delete between retrieve and Compute hour
            if(get_sys_global_parameters("compute_mode","hourly",$_GET["product"]) == "hourly"){
                foreach ($arrayTimeToCompute as $comVal) {
                    if($comVal['timeType'] == "hour"){
                        $msgError = __T("E_UPLOAD_TOPOLOGY_DELETE_FORBIDDEN_RETRIEVE_COMPUTE");
                        break;
                    }
                }
            } else {
                $msgError = __T("E_UPLOAD_TOPOLOGY_DELETE_FORBIDDEN_RETRIEVE_COMPUTE");
            }
        }
    }

        // 13/08/2010 OJT : Correction bz17046, ajout d'un nouveau filetype pour compatibilité Firefox
        // 15/09/2010 NSE bz 17046 : suppression du test sur le type
	
	// S'il y a un message d'erreur on affiche un alert javascript
	if ( $msgError != null )
	{
		?>
		<script>alert('<?php echo $msgError; ?>');</script>
		<?php
	}
	else // sinon on exécute le script qui charge le fichier en base
	{
		$delimiter = $_POST['delimiter'];
		$join      = $_POST['join'];
		$product   = $_GET['product'];
        // 20/09/2012 MMT DE 5.3 Delete Topology - ajout condition sur action specifique
        $action    = $_GET['action_topo'];

		include('admintool_update_topology_detail.php');
		exit;
	}
}

/**
 * Affiche la liste des fichiers de topologies qui ont été uploadés pour un produit donné
 *
 * @param int $product identifiant du produit
 */
function displayFileArchives ( $product )
{

	// maj 15:40 09/07/2009 : MPR - Correction du bug 10365 : Affichage des fichiers archivés du produit master ou slave (local ou distant)
	// 09/12/2011 ACS Mantis 837 DE HTTPS support
	$productModel = new ProductModel($product);
	$productInformation = $productModel->getValues();

        // 06/07/2010 BBX : utilisation de getConnection au lieu de new DatabaseConnection
	$db = Database::getConnection($product);
	// maj 13/08/2009 - MPR : Modification de la condition qui exclu les fichiers chargés par le retrieve ( on conserve les fichiers de topo en auto)
	//CB 5.3.1 WebService Topology
        $query = "
		SELECT
			t0.file_name,
			t0.uploaded_time,
                        CASE WHEN (t1.username IS NULL) THEN (
                            CASE WHEN (t0.id_user = '" . uploadFileInterface::sfuaIdUserAsm . "') 
                                THEN '" . uploadFileInterface::sfuaIdUserAsm . "' 
                            ELSE 'auto' END)
                        ELSE t1.username END
		FROM
			sys_file_uploaded_archive t0 LEFT OUTER JOIN users t1 ON (t0.id_user = t1.id_user )
		WHERE 
			t0.file_name NOT ILIKE 'temp_topo_%' AND t0.file_name NOT ILIKE 'temp_topo%'
		ORDER BY t0.uploaded_time DESC
	";  
	

	$results = $db->getAll($query);
	
	
	// maj 15:40 09/07/2009 : MPR - Correction du bug 10365 : Affichage des fichiers archivés du produit master ou slave (local ou distant)
	// On remplace REP_PHYSIQUE_NIVEAU_0 par $productInformation['sdp_directory']
	// 15:21 27/08/2009 GHX
	// Optimisation du code pour faire qu'une seule fois la connexion SSH 
	if( get_adr_server() != $productInformation['sdp_ip_address'] )
	{
		try
		{
			// Création d'une connexion ssh pour les produits slaves distants
			// 31/01/2011 MMT bz 20347 : ajout ssh_port
			$connexionSSH = new SSHConnection( $productInformation['sdp_ip_address'], $productInformation['sdp_ssh_user'], $productInformation['sdp_ssh_password'],$productInformation['sdp_ssh_port'],1);
			foreach ( $results as $i => $oneLine )
			{
				try
				{
					if( !$connexionSSH->fileExists( '/home/'.$productInformation['sdp_directory'].'/file_archive/'.$oneLine["file_name"]) ){
						unset($results[$i]);
					}
				}
				catch ( Exception $e )
				{
					unset($results[$i]);
				}
			}
		}
		catch ( Exception $e )
		{
			$results = array();
		}
	}
	else
	{ 
		foreach ( $results as $i => $oneLine )
		{
			if ( !file_exists('/home/'.$productInformation['sdp_directory'].'/file_archive/'.$oneLine["file_name"]) )
			{
				unset($results[$i]);
			}
		}
	}
	
	
	if ( count($results) > 0 )
	{
		//02/09/2014 - FGD - Bug 43496 - [REC][CB 5.3.3.01][TC #TA-56735][FireFox 31 compatibility][Upload topology] Upload frame is overflow when clicking ‘list of uploaded files’
		echo '
			<table cellpadding="2" cellspacing="2" border="0" width="577" style="display:block;">
				<tr>
					<td class="texteGrisBold" align="center">Upload time</td>
					<td class="texteGrisBold" align="center">File</td>
					<td class="texteGrisBold" align="center">User</td>
				</tr>
			';
		foreach ( $results as $i => $oneLine )
		{	
			$css_class_tr = (($i % 2) == 0) ? "fondGrisClair" : "fondBlanc"; 
			?>
			<tr class="<?= $css_class_tr ?>" onmouseover="javascript:this.className='fondOrange';" onmouseout="javascript:this.className='<?php echo  $css_class_tr; ?>';" >
				<td class="texteGris" nowrap="nowrap"><?php echo str_replace('-','/',$oneLine["uploaded_time"]); ?></td>
				<td class="texteGris"><?php // 17/09/2010 NSE bz 17983 : utilisation de force_download ?>
					<?
					// 09/12/2011 ACS Mantis 837 DE HTTPS support
					$filePath = $productModel->getCompleteUrl("file_archive/".$oneLine["file_name"]);
					?>
					<a href="<?php echo $productModel->getCompleteUrl("php/force_download.php?filepath=".$filePath);?>" style="text-decoration:underline;">
						<?php echo ereg_replace('-20[0-9]{12}\.','.',$oneLine["file_name"]); ?>
					</a>
				</td>
				<td class="texteGris"><?php echo $oneLine["username"]; ?></td>
			</tr>
			<?php
		}
		echo '</table>';
	}
	else
	{
		echo "<div class='texteGrisBold' style=\"text-align:center;margin-top:10px\">".__T('A_UPLOAD_TOPO_NO_FILE_ARCHIVE')."</div>";
	}
}

// 20/09/2012 MMT DE 5.3 Delete Topology - ajout function de suppression + css 
?>
<script language="javascript">
    function runDelete(){
        if(confirm('<?=__T('A_UPLOAD_TOPO_DELETE_CONFIRM')?>')){
            window.location='admintool_update_topology.php?action_topo=delete&product=<?php echo $_GET['product']; ?>';
        };
}
</script>
<style>
.tabPrincipal {
	padding:10px 10px 0 10px;
}
p {
	padding-bottom:20px;
}
p.fieldTopo label {
	display: block;
	float: left;
	width:220px;
	margin-right:10px;
}
p.fieldTopo input {
	display: block;
	float: left;
}
fieldset#changeProduct div {
	text-align:center;
	margin: 4px;
}
fieldset#changeProduct div img {
	margin-left:2px;
	margin-bottom:-4px;
}
.fileArchive {
	margin-top:5px;
	margin-bottom:5px;
}
.buttonContainer {
	margin-top:15px;
    margin-bottom:5px;
    text-align:center
}
</style>
<!-- on centre l'interface avec ce div conteneur -->
<div style="margin:0 auto;width:650px;">
	<div style="margin:0 auto;width:387px;">
		<img  src="<?=$niveau0?>images/titres/upload_topo_titre.gif" width="387" height="42" alt="Upload topology" />
	</div>
	<div class="tabPrincipal">
		<fieldset id="changeProduct">
			<legend class="texteGrisBold">&nbsp;<img src="<?=$niveau0?>images/icones/small_puce_fieldset.gif"/>&nbsp;<?=__T('G_CURRENT_PRODUCT')?>&nbsp;</legend>
			<div>
				<?
					echo '<span class="texteGris">'.$productInformation['sdp_label'].'</span>';
					if ( count($productsInformations) > 1)
					{
						?>
							<a href="<? echo(str_replace("&product=".$_GET['product'], '',$_SERVER['PHP_SELF'])); ?>" target="_top">
								<img src="<?=$niveau0?>images/icones/change.gif" onMouseOver="popalt('Change product');style.cursor='help';" border="0"/>
							</a>
						<?php
					}
				?>
			</div>
		</fieldset>
        <!-- 20/09/2012 MMT DE 5.3 Delete Topology - ajout du panneau -->
        <br>
        <fieldset >
			<legend class="texteGrisBold">&nbsp;<img src="<?=$niveau0?>images/icones/small_puce_fieldset.gif"/>&nbsp;<?=__T('A_UPLOAD_TOPO_DELETE_FRAME_TITLE')?>&nbsp;</legend>
			
				<label class="texteGrisPetit"><?=__T('A_UPLOAD_TOPO_DELETE_INFO_MESSAGE')?></label>
                <div class="buttonContainer">
                    <input type='button' value='<?=__T('A_UPLOAD_TOPO_DELETE_SUBMIT')?>' class="bouton" onclick="javascript:runDelete();"
                           <?
                           // delete topology available only for slave at 5.3 version
                           if(!$productModel->isCbVersionGreaterOrEqualThan(get_sys_global_parameters("topology_delete_minimum_version"))){?>
                               disabled="1" title="<?=__T('A_UPLOAD_TOPOLOGY_DELETE_SLAVE_VERSION')?>"
                           <?}?>
                           />
                </div>         
			
		</fieldset>
        <br>
        <fieldset>
			<legend class="texteGrisBold">&nbsp;<img src="<?=$niveau0?>images/icones/small_puce_fieldset.gif"/>&nbsp;<?=__T('A_UPLOAD_TOPO_UPLOAD_FRAME_TITLE')?>&nbsp;</legend>
			
            <form enctype='multipart/form-data' name='upload_obj_ref' method='post' action='admintool_update_topology.php?action_topo=upload&product=<?php echo $_GET['product']; ?>'>
			<div><?php // 17/09/2010 NSE bz 17838 : suppression de l'effet de déroulement non compatible avec FF ?>
                            <a style="font-weight:bold;" href="" onclick="if($('filesArchivesContainer').style.display=='none'){$('filesArchivesContainer').style.display='block';}else{$('filesArchivesContainer').style.display='none';}; return false;">
					<?php echo __T('A_UPLOAD_TOPO_UPLOADED_LIST'); ?>

					<img src="<?=$niveau0?>/images/icones/fleche_download.gif" style="border:0px;"/>
				</a>
				<?php
				// 15:15 27/08/2009 GHX
				// Correction du BZ 10970
				// Ajout d'un message comme quoi on affiche les 20 derniers fichiers uploadés
				// Modification du style dy fieldset
				// maj 26/10/2009 : MPR : Correction du bug 7981 : Ajout d'un message indiquant que les caractères spéciaux suivants sont remplacer par des _
                // 14/09/2010 NSE bz 17838 : Firefox ne gère pas overflow sur fieldset, ajout d'une div
				?>
				<fieldset class="fileArchive texteGris" id="filesArchivesContainer" style="display:none; height: 200px;">
                                    <div style="height: 200px; overflow: auto;">
                                        <div style="margin-bottom:8px;font-style:italic"><?php echo __T('A_UPLOAD_TOPO_INFO_LAST_FILES_UPLOADED'); ?></div>
					<?php displayFileArchives($_GET["product"]); ?>
                                    </div>
				</fieldset>
			</div>
			<p class="fieldTopo">
				<label class="texteGrisBold" for="downloadfile"><?php echo __T('A_UPLOAD_TOPO_CSV_TO_UPLOAD')?>
				<img src="<?=$niveau0."images/icones/cercle_info.gif"?>" onmouseover="popalt('<?php echo __T('A_UPLOAD_TOPO_MSG_INFO')?>');" />
				</label>

				<input type='file' id="downloadfile" name='downloadfile' size='30'>
			</p>
			<p class="fieldTopo">
				<label class="texteGrisBold" for="delimiter"><?php echo __T('A_UPLOAD_TOPO_DELIMITER')?><br /><span class='texteGrisPetit'><?=__T('A_UPLOAD_TOPO_DELIMITER_INFO')?></span></label>
				<!-- 07/05/2013 : ajout du caractère de séparation tabulation -->
                                <select id="delimiter" name='delimiter'>
					<option value=';'>&nbsp;;&nbsp;</option>
					<option value=','>&nbsp;,&nbsp;</option>
					<option value='tab'>&nbsp;tab&nbsp;</option>
				 </select>
			</p>
			<p class="fieldTopo">
				<label class="texteGrisBold" for="delimiter"><?php echo __T('A_UPLOAD_TOPO_HEADER')?><br /><span class='texteGrisPetit'><?=__T('A_UPLOAD_TOPO_FIRST_LINE_INFO')?></span></label>
				<input type="checkbox" name="header" value="1" disabled="disabled" checked="checked">
			</p>
                <!-- 20/09/2012 MMT DE 5.3 Delete Topology - ajout du panneau sur upload et reformatage -->
                <div class="buttonContainer">
				<br />
				<input type='hidden' name='op' value='dbconfirm' />
                                <?php
                                // 06/07/2010 BBX
                                // Ajout du paramètre "force" pour savoir si la mise à jour est forcée ou non
                                // BZ 12974
                                ?>
                                <input type="hidden" name="force" value="<?=(isset($_GET['continue']) ? '1' : '0')?>" />
				<input type='submit' name='submit' value='<?=__T('A_UPLOAD_TOPO_SUBMIT')?>' class="bouton"/>
                </div>
		</form>
            </fieldset>
            <br>
	</div>
</div>
<br clear="both"/>
<br/>