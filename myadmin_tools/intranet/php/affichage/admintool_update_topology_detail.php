<?php
/**
 * @cb5100@
 * 17/05/2010 NSE : Installation standard, utilisation d'une variable globale pour les chemins vers les exécutables psql et php (PSQL_DIR et PHP_DIR)
 * 26/07/2010 OJT : Correction BZ16937 + Suppression du PHP_DIR
 *
 * @cb50
 * 21/08/2009 GHX : Ajout d'un trim
 * 31/01/2011 MMT bz 20347 : ajout sdp_ssh_port dans appel new SSHConnection
 * 08/03/2012 SPD bz 24368 : ihm etiree a la largeur de l'ecran
 * 20/09/2012 MMT DE 5.3 Delete Topology
 */

// 20/09/2012 MMT DE 5.3 Delete Topology - ajout DeleteTopology.class
include_once(REP_PHYSIQUE_NIVEAU_0."myadmin_tools/intranet/php/traitement/DeleteTopology.class.php");

// On utilise le débug de topo au lieu du débug général
$debug = get_sys_debug('upload_topology');
 
echo '<script>document.getElementById("texteLoader").innerHTML="'.__T('A_UPLOAD_TOPOLOGY_LOADER_INFO').'";</script>';
flush();

$startUpload = microtime(true);

// 20/09/2012 MMT DE 5.3 Delete Topology - factorisation des variables communes upload/delete

$msgError = '';
$msgSuccess = __T('A_UPLOAD_TOPOLOGY_SUCCES');
$errorTitle = __T('A_ADMIN_TITLE_TOPOLOGY_ERRORS');
$error = null;
$results = array();
$product = $_GET['product'];

// 20/09/2012 MMT DE 5.3 Delete Topology - test sur action
if($action == "upload" ){

    $SSHConnection = NULL;
    $msgSuccess = __T('A_UPLOAD_TOPOLOGY_SUCCES');

    // remplace les espaces par des underscores (sinon problème dans l'upload au niveau des commandes unix)
    $file_name = str_replace(' ', '_', $file_name);
    
    /* !! Les variables $file_name / $delimiter / $axe3 sont défini dans le script "admintool_update_topology.php" (celui qui include ce fichier) !!  */
    $cmdScriptLoadFile = 'php -q /home/'.$productInformation['sdp_directory'].'/myadmin_tools/intranet/php/affichage/admintool_update_topology_load_file.php "'.$file_name.'" "'.$delimiter.'" "'.$_SESSION['id_user'].'" "'.$product.'"';
    $directoryUploadProduct = '/home/'.$productInformation['sdp_directory'].'/upload/'.$file_name;
    $cmdRemoveUploadedFile = 'rm "'.$directoryUploadProduct.'" -f'; // Cmd pour suppression du fichier uploadé

    if ( $debug )
    {
        // 06/04/2012 BBX
        // BZ 26732 : Utilisation de get_adr_server au lieu de $_SERVER['SERVER_ADDR']
	echo '<br />IP T&A : '. get_adr_server();
	echo '<br />IP produit : '. $productInformation['sdp_ip_address'];
	echo '<br />exec : '.$cmdScriptLoadFile;
    }

    // Si le produit est sur le même serveur que le CB, on fait un simple copy du fichier en local
    // 06/04/2012 BBX
    // BZ 26732 : Utilisation de get_adr_server au lieu de $_SERVER['SERVER_ADDR']
    if ( get_adr_server() == $productInformation['sdp_ip_address'] || $productInformation['sdp_ip_address'] == '127.0.0.1' || $productInformation['sdp_ip_address'] == 'localhost' )
    {
	if ( $debug )
	{
		echo '<br />Copie du fichier sur le meme serveur : /home/'.$productInformation['sdp_directory'].'<br />';
	}
	// Copie le fichier dans le répertoire upload du produit
	copy($downloadfile, $directoryUploadProduct);

	// Chargement du fichier
	exec($cmdScriptLoadFile, $results, $error);
    }
    else // sinon on le copie sur le serveur distant via ssh
    {
	// On inclut la classe qui permet de gérer le SSH, on le fait ici afin d'éviter de l'inclure si on n'en a pas besoin
	include_once(REP_PHYSIQUE_NIVEAU_0."class/SSHConnection.class.php");
	try
	{
		if ( $debug )
		{
			echo '<br />Copie du fichier sur un serveur distant : ['.$productInformation['sdp_ip_address'].'] /home/'.$productInformation['sdp_directory'].'<br />';
		}
		// 31/01/2011 MMT bz 20347 : ajout sdp_ssh_port dans appel new SSHConnection
		$SSHConnection = new SSHConnection($productInformation['sdp_ip_address'], $productInformation['sdp_ssh_user'], $productInformation['sdp_ssh_password'],$productInformation['sdp_ssh_port']);
		// 02/07/2009 BBX : correction du umask (777 => 0777). BZ 10355
		$SSHConnection->sendFile($downloadfile, $directoryUploadProduct, 0777);
		$results = $SSHConnection->exec($cmdScriptLoadFile);
	}
	catch ( Exception $e )
	{
		$error = true;
		$msgError = $e->getMessage();
	}
    }


    if ( $debug )
    {
	echo '<pre>$error : '; var_dump($error); echo '</pre>';
	echo '<pre>$results : '; print_r($results); echo '</pre>';
    }

    if ( count($results) > 0 )
    {
	// 17:30 21/08/2009 GHX
	// Ajout du trim
	$firstLine = trim(array_shift($results));

	if ( $firstLine == '-ERROR-' ) // Erreur sur le fichier chargé
	{
		$error = true;
		if ( count($results) > 0 )
		{
			foreach ( $results as $result )
			{
				$msgError .= '<li>'.$result.'</li>';
			}
		}
	}
	elseif ( $firstLine == '-OK-' ) // Le fchier a été chargé correctement en base 
	{
		$changesSummary = '';
		if ( count($results) > 0 )
		{
			foreach ( $results as $index => $result )
			{
				$changesSummary .= '<tr align="center" class="topo texteGrisPetit '.($index%2 ? 'fondGrisClair ': 'fondVide' ).'"><td>'.str_replace(';', '</td><td>',$result).'</td></tr>';
			}
		}
	}
	else // Erreur inconnue ??!!?
	{
		$error = true;
	}
    }
    
    // 20/09/2012 MMT DE 5.3 Delete Topology - traitement d'erreur specifique au upload
    if ( $error )
    {
        // 26/07/2010 OJT Correction bz16937
        // 06/04/2012 BBX
        // BZ 26732 : Utilisation de get_adr_server au lieu de $_SERVER['SERVER_ADDR']
        if (get_adr_server() == $productInformation['sdp_ip_address'] || $productInformation['sdp_ip_address'] == '127.0.0.1' || $productInformation['sdp_ip_address'] == 'localhost' )
        {
            exec( $cmdRemoveUploadedFile );
        }
        else if ( $SSHConnection != NULL )
        {
            $SSHConnection->exec( $cmdRemoveUploadedFile );
        }
        else
        {
            // Le fihcier n'a pas du être uploader, on ne fait rien
        }
        if( empty($msgError)) {
           $errorTitle = __T('A_E_UPLOAD_TOPOLOGY_ERROR_DURING_UPADTE');
        }
    }
    
}
// 20/09/2012 MMT DE 5.3 Delete Topology - traitement du delete
else if($action == "delete")
{
    $msgSuccess = __T('A_UPLOAD_TOPOLOGY_DELETE_SUCCES');
    $errorTitle = __T('A_UPLOAD_TOPOLOGY_DELETE_ERROR');
    try{
        // appel au DeleteTopology
        $delTopo  = new DeleteTopology($product);
        $delTopo->setDebug($debug);
        $delTopo->performDeletion();
    } catch (Exception $err){
        $error = true;
        $msgError = $err->getMessage();
    }
}

$endUpload = microtime(true);
$timeupload = $endUpload-$startUpload;

?>
<style>
.tabPrincipal {
	padding:10px 10px 0 10px;
	margin:0 auto;width:90%;
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
</style>
<div class="tabPrincipal" >
	<fieldset id="changeProduct">
	<legend class="texteGrisBold">&nbsp;<img src="<?=$niveau0?>images/icones/small_puce_fieldset.gif"/>&nbsp;<?=__T('G_CURRENT_PRODUCT')?>&nbsp;</legend>
	<div>
		<?
			echo '<span class="texteGris">'.$productInformation['sdp_label'].'</span>';
				?>
	</div>
	</fieldset>

<?php
// S'il y a eu une erreur lors du chargement du fichier
// 20/09/2012 MMT DE 5.3 Delete Topology - deplacement des cas specifiques upload
if ( $error ){
?>
		<div class="errorMsg" style="margin:50px auto;">
			<h3 style="text-align:center;">
				<?php echo $errorTitle; ?>
			</h3>
			<ul style="list-style-type:none;margin-left:5px">
				<?php echo $msgError; ?>
			</ul>
		</div>
	<?php	
}
else
{
	?>
		<div class="okMsg" style="margin:50px auto;">
			<p style="text-align:center">
				<?php 
					echo $msgSuccess; 
					if ( $debug )
					{
						printf("<br />Time = %02d:%02d:%02d", ($timeupload/3600), (($timeupload%3600)/60), (($timeupload%3600)%60));
					} 
				?>				
			</p>	
		</div>
	<?php	
	if ( $changesSummary != '' )
	{
		?>
		
		<div style="margin:50px auto 20px auto; text-align:center">
		<span class='texteGrisBold'><?php echo __T('A_UPLOAD_TOPOLOGY_TITLE_CHANGE_SUMMARY'); ?></span>
		<br/>
		<table class="tabPrincipalClair" style="border:none; margin: 0 auto;">
			<!-- ENTETE DU TABLEAU-->
			<tr style="background-color:#B4B4B4">
				<td align="center" class='texteGrisBold'><?php echo __T('A_UPLOAD_TOPOLOGY_TITLE_COL_NETWORK_LEVEL'); ?></td>
				<td align="center" class='texteGrisBold'><?php echo __T('A_UPLOAD_TOPOLOGY_TITLE_COL_NETWORK_VALUE'); ?></td>
				<td align="center" class='texteGrisBold'><?php echo __T('A_UPLOAD_TOPOLOGY_TITLE_COL_CHANGE_INFO'); ?></td>
				<td align="center" class='texteGrisBold'><?php echo __T('A_UPLOAD_TOPOLOGY_TITLE_COL_OLD_VALUE'); ?></td>
				<td align="center" class='texteGrisBold'><?php echo __T('A_UPLOAD_TOPOLOGY_TITLE_COL_NEW_VALUE'); ?></td>
			</tr>
			<?php echo $changesSummary; ?>
		</table>
		</div>
		<?php
	}

        // 06/07/2010 BBX
        // Si la topo a été forcée, on log celà dans le tracelog
        // BZ 12974
        if(isset($_POST['force']) && ($_POST['force'] == 1))
        {
            $message = __T('A_UPLOAD_TOPO_UPDATE_FORCED',$productInformation['sdp_label']);
            sys_log_ast('Warning', 'Trending&Aggregation', 'Topology', $message, 'support_1', '');
        }
}
?>
</div>