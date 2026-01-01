<?php
/*
	02/11/2009 GHX
		- Le produit Mixed KPI peut être activé sur du mono-produit (suppression de la condition)
	18/11/2009 GHX
		- Modification de la commande grep qui récupère la taille de la partition data de postgres
		- Ajout de l'image pour le titre
	26/11/2009 GHX
		- Correction du BZ 13081 [Mixed KPI] : activation mixed kpi ne plus prendre en compte template1 comme sur une installe de base
        06/05/2010 MPR
 *              - Correction du bz 15187 - Message d'erreur explicite indiquant ce qui a planté
*/
?>
<?php
/*
 * Cette page permet de créer le produit Mixed KPI. 
 * L'utilisateur doit cliquer sur un bouton pour lancer la création du produit Mixed KPI  mais avant il arrive sur une page de confirmation.
 *
 * @author GHX
 * @date 05/10/2009
 * @version CB 5.0.1.00
 * @since CB 5.0.1.00
 */

$oneProduct = false;
$confirmation = false;
$activation = false;

$products = ProductModel::getProducts();

if ( isset($_POST['activation']) && ($_POST['activation'] == 1) ) // L'utilisateur doit confirmer l'activation du produit MK
{
	$confirmation = true;
	$disabledButton = '';
	
	/*
		Vérification de l'espace disque pour la partition data et la partition /home
		
		On part sur le principe que l'espace disque de la base de données du produit Mixed KPI sera la même
		que celui du master. Pour ne pas prendre de risque dans le cas d'une grosse volumétrie on regarde si on a le double
		de l'espace disque de la base du master de libre sur la partition des données.
	*/
        // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
	$db = Database::getConnection();
	// Récupère l'espace disque occupée par la base de données du master
	$sizeBDDMaster = $db->getSize();
	
	// ATTENTION df retourne l'espace disque en KB et non en Octect alors que la fonction DatabaseConnection::getSize() la retourne en octect
	// 18/11/2009 GHX
	// Modification de la commande GREP en effet sur certains serveurs, les partitions ne sont pas exactement les mêmes
  $cmdData = 'df | grep -E "/usr/local/pgsql(/data)?$" | awk \'{print $3";"$4}\'';
	$sizePartitionData = exec($cmdData);
	// $sizePartitionData[0] = espace occupée par toutes les bases
	// $sizePartitionData[1] = espace libre sur la partition /usr/local/pgsql/data
	$sizePartitionData = explode(';', $sizePartitionData);
	
	// Récupère l'espace disque occupé du répertoire de l'application ATTENTION : comprend aussi le répertoire png_file, upload, flat_file_upload_archive...
	$cmdRep = "du -s ".REP_PHYSIQUE_NIVEAU_0;
	$sizeRep = exec($cmdRep);
	
	$cmdHome = 'df | grep "/home$" | awk \'{print $3";"$4}\'';
	$sizePartitionHome = exec($cmdHome);
	// $sizePartitionHome[0] = espace occupée 
	// $sizePartitionHome[1] = espace libre sur la partition /home
	$sizePartitionHome = explode(';', $sizePartitionHome);
	
	/*
		Vérification si "template1" est utilisé
	*/
	$errorTemplate1 = false;
	// >>>>>>>>>> 
	// 15:52 26/11/2009 GHX
	// Correction du BZ 13081
	// On utilise le même principe que pour installe de base pour l'activation du produit Mixed KPI
	/*
	$db->execute("SELECT * FROM pg_stat_activity WHERE datname = 'template1'");
	$nbOfUseTemplate1 = $db->getNumRows();
	if ( $nbOfUseTemplate1 > 0 )
	{
		$errorTemplate1 = true;
		$disabledButton = ' disabled="disabled"';
	}
	*/
	// <<<<<<<<<<
}
elseif ( isset($_POST['confirmation']) && ($_POST['confirmation'] == 1) ) // Création du produit Mixed KPI
{
	$activation = true;
	$installOK = true;
	$createMK = new CreateMixedKpi();
        $status = $createMK->installViaCron();
	// Création de la base de données du produit Mixed KPI
	if ( $status == "true" )
	{
		// 25/03/2010 BBX
		// Création des trigrammes par défaut
		ProductModel::generateTrigrams();

	}
	else
	{
            $installOK = false;
}
}
?>
<div id="container" style="width:100%;text-align:center">
  <!-- titre de la page -->
	<div>
    <h1><img src="../images/titres/setup_mixed_kpi.gif" title="Setup Mixed KPI : configuration" /></h1>
	</div>
	<br />
	<?php
	if ( $oneProduct == true ) // On n'est pas en multi produit donc on ne peut pas installer le produit Mixed KPI
	{
		echo '<div class="infoBox">'.__T('A_SETUP_MIXED_ONE_PRODUCT').'</div>';
	}
	elseif ( $confirmation === true ) // L'utilisateur doit confirmer la création du produit MK
	{
		?>
		<form action="index.php" method="post" />
			<div class="tabPrincipal" style="width:600px;text-align:center;padding:10px;">
				<!-- HELP -->
				<div style="text-align:left">
					<img src="<?php echo NIVEAU_0; ?>images/icones/information.png" border="0" />
					<div id="help_box_1" class="infoBox">
						<?php echo __T('A_SETUP_MIXED_KPI_INFO_CONFIRMATON'); ?>
						<br />
						<br />
						<div>
							<?php echo __T('A_SETUP_MIXED_KPI_INFO_DISK_SPACE'); ?>
							<dt><?php echo __T('A_SETUP_MIXED_KPI_TITLE_DISK_SPACE_BDD'); ?></dt>
								<dd><?php echo __T('A_SETUP_MIXED_KPI_LABEL_FREE', formatSize($sizePartitionData[1]*1024)); ?></dd>
								<dd><?php echo __T('A_SETUP_MIXED_KPI_LABEL_RECOMMEND', formatSize($sizeBDDMaster*2, 0)); ?></dd>
							<dt><?php echo __T('A_SETUP_MIXED_KPI_TITLE_DISK_SPACE_FILE'); ?></dt>
								<dd><?php echo __T('A_SETUP_MIXED_KPI_LABEL_FREE', formatSize($sizePartitionHome[1]*1024)); ?></dd>
								<dd><?php echo __T('A_SETUP_MIXED_KPI_LABEL_RECOMMEND', formatSize($sizeRep*2*1024, 0)); ?></dd>
						</div>
						<br />
						<?php echo __T('A_SETUP_MIXED_KPI_WARNING_ACTIVATION_TAKE_FEW_MINUTES'); ?>
					</div>
				</div>				
				<br />
				<?php
				if ( $errorTemplate1 )
				{
					echo '<div class="errorMsg" style="display:block;">'.__T('A_E_SETUP_MIXED_KPI_ERROR_TEMPLATE1', $nbOfUseTemplate1).'</div><br />';
				}
				?>
				<!-- SUBMIT -->
				<input type="hidden" name="confirmation" value="1" />
				<input type="submit" class="bouton" value="<?php echo __T('A_SETUP_MIXED_KPI_BUTTON_CONFIRMATION_ACTIVATION'); ?>" <?php echo $disabledButton; ?>/>
			</div>
		</form>
		<?php
	}
	elseif ( $activation === true ) // L'installaton est finie on affiche soit un message d'erreur soit on fait une redirection vers la page de configuration du produit Mixed KPI
	{
		if ( $installOK === true )
		{
			// On recharge la page pour arriver sur la page de configuration
			echo '<script>document.location.href = "'.$_SERVER["PHP_SELF"].'";</script>';
		}
		else
		{
                    // Correction du bz 15187 - Message d'erreur explicite indiquant ce qui a planté
			echo '<div class="tabPrincipal" style="width:600px;text-align:center;padding:10px;">
				<div class="errorMsg">'. __T('A_E_SETUP_MIXED_KPI_ERROR_DURING_ACTIVATION',$status).'</div>
			</div>
			';
		}
	}
	else // Affichage du bouton pour créer le produit MK
	{
		?>
		<form action="index.php" method="post" />
			<div class="tabPrincipal" style="width:600px;text-align:center;padding:10px;">
				<!-- HELP -->
				<div style="text-align:left">
					<img src="<?php echo NIVEAU_0; ?>images/icones/information.png" border="0" />
					<div id="help_box_1" class="infoBox" style="display:block;">
						<?php echo __T('A_SETUP_MIXED_KPI_INFO_ACTIVATION'); ?>
					</div>
				</div>
				<br />
				<!-- SUBMIT -->
				<input type="hidden" name="activation" value="1" />
				<input type="submit" class="bouton" value="<?php echo __T('A_SETUP_MIXED_KPI_BUTTON_ACTIVATION');?>" />
			</div>
		</form>
		<?php
	}
	?>
</div>
</body>
</html>